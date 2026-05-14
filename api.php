<?php
// 设置更长的执行时间和内存限制
set_time_limit(300); // 5分钟
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/includes/BaoTaAPI.php';
require_once __DIR__ . '/includes/AICommandParser.php';
require_once __DIR__ . '/includes/TaskManager.php';

$taskManager = new TaskManager();
$taskManager->cleanOldTasks();

$input = json_decode(file_get_contents('php://input'), true);

// 如果是 GET 请求（用于任务轮询）
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['task_id'])) {
    $taskId = $_GET['task_id'];
    $task = $taskManager->getTask($taskId);
    if ($task) {
        echo json_encode(['success' => true, 'task' => $task]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    }
    exit();
}

if (empty($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Action is required']);
    exit();
}

$action = $input['action'];

switch ($action) {
    case 'test_connection':
        handleTestConnection($input);
        break;

    case 'chat':
        handleChat($input, $taskManager);
        break;

    case 'execute_ai_command':
        handleAICommand($input, $taskManager);
        break;

    case 'get_server_status':
        handleServerStatus($input);
        break;

    case 'get_all_info':
        handleGetAllInfo($input);
        break;

    case 'direct_api':
        handleDirectAPI($input);
        break;

    case 'get_task_status':
        handleGetTaskStatus($input, $taskManager);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleTestConnection($input) {
    if (empty($input['panel_url']) || empty($input['api_key'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->testConnection();
    echo json_encode(['success' => $result, 'message' => $result ? '连接成功' : '连接失败']);
}

function handleChat($input, $taskManager) {
    if (empty($input['panel_url']) || empty($input['api_key'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    if (empty($input['message'])) {
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit();
    }

    if (empty($input['deepseek_api_key'])) {
        echo json_encode(['success' => false, 'message' => 'DeepSeek API Key is required']);
        exit();
    }

    $userMessage = $input['message'];
    $history = $input['history'] ?? [];

    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);

    $systemPrompt = "你是一个专业的服务器运维助手，可以通过宝塔面板 API 管理服务器。

可用的功能：
1. 网站管理：创建、删除、重启、启动、停止网站
2. 数据库管理：创建、删除数据库
3. 文件管理：浏览、读取、写入、删除文件
4. 系统信息：CPU、内存、磁盘、网络状态
5. Shell命令：执行任意Linux命令

当用户提出操作需求时：
- 如果可以执行，直接调用相应的 API
- 如果需要更多信息，询问用户
- 如果操作成功，返回结果
- 如果操作失败，返回错误原因

示例：
用户：帮我创建一个网站，域名为 test.com
助手：好的，我来为您创建网站 test.com

执行结果：✅ 网站创建成功！
- 域名: test.com
- 路径: /www/wwwroot/test.com
- PHP版本: 74

---

当前服务器信息：";

    $systemInfo = $baota->getSystemTotal();
    if (isset($systemInfo['cpuRealUsed'])) {
        $systemPrompt .= "- 操作系统: {$systemInfo['system']}\n";
        $systemPrompt .= "- CPU: {$systemInfo['cpuRealUsed']}% ({$systemInfo['cpuNum']}核)\n";
        $systemPrompt .= "- 内存: {$systemInfo['memRealUsed']}MB / {$systemInfo['memTotal']}MB\n";
    }

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];

    foreach ($history as $h) {
        $messages[] = $h;
    }

    $messages[] = ['role' => 'user', 'content' => $userMessage];

    // 创建任务用于进度跟踪
    $taskId = $taskManager->createTask('chat', ['message' => $userMessage]);
    $taskManager->updateProgress($taskId, 10, '正在连接 DeepSeek AI...');

    try {
        $ch = curl_init('https://api.deepseek.com/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'deepseek-v4-pro',
                'messages' => $messages,
                'stream' => false,
                'thinking' => ['type' => 'disabled']
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $input['deepseek_api_key']
            ],
            CURLOPT_TIMEOUT => 180, // 3分钟超时
            CURLOPT_CONNECTTIMEOUT => 30
        ]);

        $taskManager->updateProgress($taskId, 30, '正在获取 AI 响应...');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $taskManager->updateProgress($taskId, 60, '解析 AI 响应...');
        $result = json_decode($response, true);

        if (isset($result['choices'][0]['message']['content'])) {
            $aiResponse = $result['choices'][0]['message']['content'];

            $parser = new AICommandParser($baota);
            $commands = $parser->extractCommands($aiResponse);

            $executionResults = [];
            if (!empty($commands)) {
                $taskManager->updateProgress($taskId, 80, '正在执行宝塔操作...');
                foreach ($commands as $cmd) {
                    $execResult = $parser->parseAndExecute($cmd);
                    $executionResults[] = $execResult;
                }
            }

            $taskManager->updateProgress($taskId, 100, '完成');

            echo json_encode([
                'success' => true,
                'message' => $aiResponse,
                'execution_results' => $executionResults,
                'raw_response' => $result,
                'task_id' => $taskId
            ]);
        } else {
            $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'DeepSeek API 请求失败';
            $taskManager->markFailed($taskId, $errorMsg);
            echo json_encode([
                'success' => false,
                'message' => $errorMsg,
                'error' => $result
            ]);
        }
    } catch (Exception $e) {
        $taskManager->markFailed($taskId, $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => '请求异常: ' . $e->getMessage()
        ]);
    }
}

function handleAICommand($input, $taskManager) {
    if (empty($input['panel_url']) || empty($input['api_key'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    if (empty($input['command'])) {
        echo json_encode(['success' => false, 'message' => 'Command is required']);
        exit();
    }

    $taskId = $taskManager->createTask('command', ['command' => $input['command']]);
    $taskManager->updateProgress($taskId, 10, '正在连接宝塔面板...');

    try {
        $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
        $parser = new AICommandParser($baota);

        $taskManager->updateProgress($taskId, 30, '正在执行命令...');
        $result = $parser->parseAndExecute($input['command']);

        $taskManager->updateProgress($taskId, 100, '完成');
        $taskManager->markComplete($taskId, $result);

        echo json_encode($result + ['task_id' => $taskId]);
    } catch (Exception $e) {
        $taskManager->markFailed($taskId, $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'task_id' => $taskId
        ]);
    }
}

function handleServerStatus($input) {
    if (empty($input['panel_url']) || empty($input['api_key'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $parser = new AICommandParser($baota);
    $result = $parser->parseAndExecute('查看系统状态');

    echo json_encode($result);
}

function handleGetAllInfo($input) {
    if (empty($input['panel_url']) || empty($input['api_key'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->getAllInfo();
    echo json_encode(['success' => true, 'data' => $result]);
}

function handleDirectAPI($input) {
    if (empty($input['panel_url']) || empty($input['api_key'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    if (empty($input['bt_action'])) {
        echo json_encode(['success' => false, 'message' => 'BT action is required']);
        exit();
    }

    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $data = $input['data'] ?? [];

    $result = $baota->makeCustomRequest($input['bt_action'], $data);
    echo json_encode(['success' => true, 'data' => $result]);
}

function handleGetTaskStatus($input, $taskManager) {
    if (empty($input['task_id'])) {
        echo json_encode(['success' => false, 'message' => 'Task ID required']);
        exit();
    }

    $task = $taskManager->getTask($input['task_id']);
    if ($task) {
        echo json_encode(['success' => true, 'task' => $task]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task not found']);
    }
}
