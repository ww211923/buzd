<?php
// 设置更长的执行时间
set_time_limit(300);
ini_set('memory_limit', '512M');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/includes/BaoTaAPI.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'test_connection':
            handleTestConnection($input);
            break;
            
        case 'get_status':
            handleGetStatus($input);
            break;
            
        case 'chat':
            handleChat($input);
            break;
            
        case 'list_sites':
            handleListSites($input);
            break;
            
        case 'list_dbs':
            handleListDatabases($input);
            break;
            
        case 'exec_cmd':
            handleExecCmd($input);
            break;
            
        case 'create_site':
            handleCreateSite($input);
            break;
            
        case 'list_files':
            handleListFiles($input);
            break;
            
        case 'read_file':
            handleReadFile($input);
            break;
            
        case 'write_file':
            handleWriteFile($input);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => '未知操作']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '错误: ' . $e->getMessage()]);
}

function handleTestConnection($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->testConnection();
    echo json_encode(['success' => $result, 'message' => $result ? '连接成功' : '连接失败']);
}

function handleGetStatus($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->getSystemTotal();
    echo json_encode(['success' => isset($result['cpuRealUsed']), 'data' => $result]);
}

function handleListSites($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->getSites();
    echo json_encode(['success' => true, 'data' => $result]);
}

function handleListDatabases($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->getDatabaseList();
    echo json_encode(['success' => true, 'data' => $result]);
}

function handleExecCmd($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->executeCommand($input['cmd']);
    echo json_encode(['success' => isset($result['code']) && $result['code'] == 1, 'data' => $result]);
}

function handleCreateSite($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->addSite(
        $input['domain'],
        $input['domain'],
        '/www/wwwroot/' . $input['domain'],
        $input['php'] ?? '73'
    );
    echo json_encode(['success' => isset($result['code']) && $result['code'] == 1, 'data' => $result]);
}

function handleListFiles($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->getFilesList($input['path'] ?? '/www/wwwroot');
    echo json_encode(['success' => true, 'data' => $result]);
}

function handleReadFile($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->readFile($input['path']);
    echo json_encode(['success' => isset($result['code']) && $result['code'] == 1, 'data' => $result]);
}

function handleWriteFile($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $result = $baota->writeFile($input['path'], $input['content']);
    echo json_encode(['success' => isset($result['code']) && $result['code'] == 1, 'data' => $result]);
}

function handleChat($input) {
    $baota = new BaoTaAPI($input['panel_url'], $input['api_key']);
    $deepseekKey = $input['deepseek_key'] ?? '';
    $userMsg = $input['message'] ?? '';
    
    $systemInfo = $baota->getSystemTotal();
    $sites = $baota->getSites();
    
    $context = "当前服务器状态：\n";
    if (isset($systemInfo['cpuRealUsed'])) {
        $context .= "- CPU: {$systemInfo['cpuRealUsed']}% ({$systemInfo['cpuNum']}核)\n";
        $context .= "- 内存: {$systemInfo['memRealUsed']}MB / {$systemInfo['memTotal']}MB\n";
        $context .= "- 系统: {$systemInfo['system']}\n";
        $context .= "- 运行时间: {$systemInfo['time']}\n";
    }
    
    if (isset($sites['data'])) {
        $context .= "\n现有网站 (" . count($sites['data']) . "个):\n";
        foreach ($sites['data'] as $site) {
            $context .= "- {$site['name']} (PHP {$site['php_version']})\n";
        }
    }
    
    $response = callDeepSeek($deepseekKey, $context, $userMsg, $baota);
    echo json_encode($response);
}

function callDeepSeek($apiKey, $context, $userMsg, $baota) {
    if (empty($apiKey)) {
        return ['success' => false, 'message' => '请先配置DeepSeek API Key'];
    }
    
    $prompt = <<<PROMPT
你是一个专业的宝塔面板服务器管理助手。你可以：
1. 获取系统状态和信息
2. 列出、创建、删除网站
3. 管理数据库
4. 操作文件（查看、编辑、删除）
5. 执行Shell命令
6. 管理PHP版本

当前服务器信息：
$context

用户的问题是：$userMsg

请分析用户需求，并在下方回复。如果需要执行操作，请直接使用【】标记说明，例如：
【查看系统状态】
【列出网站列表】
【执行命令：ls -la】
【创建网站：example.com】
【查看文件：/www/wwwroot/index.html】
【写入文件：/www/wwwroot/test.html 内容：Hello World】

注意：不要用Markdown格式，直接用自然语言回复。
PROMPT;

    $ch = curl_init('https://api.deepseek.com/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => '你是一个专业的宝塔面板服务器管理助手。'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'message' => 'DeepSeek请求失败: ' . $error];
    }
    
    $data = json_decode($result, true);
    if (!isset($data['choices'][0]['message']['content'])) {
        return ['success' => false, 'message' => 'DeepSeek返回无效数据: ' . $result];
    }
    
    $aiText = $data['choices'][0]['message']['content'];
    $executed = executeCommands($aiText, $baota);
    
    return [
        'success' => true,
        'message' => $aiText,
        'executed' => $executed
    ];
}

function executeCommands($aiText, $baota) {
    $results = [];
    $aiText = preg_replace_callback('/【([^】]+)】/', function($matches) use ($baota, &$results) {
        $cmd = trim($matches[1]);
        $result = tryExecuteCommand($cmd, $baota);
        $results[] = $result;
        return '';
    }, $aiText);
    
    return $results;
}

function tryExecuteCommand($cmd, $baota) {
    if (strpos($cmd, '查看系统状态') !== false || strpos($cmd, '系统状态') !== false) {
        $r = $baota->getSystemTotal();
        return ['cmd' => $cmd, 'success' => true, 'result' => $r];
    }
    
    if (strpos($cmd, '列出网站列表') !== false || strpos($cmd, '查看网站') !== false) {
        $r = $baota->getSites();
        return ['cmd' => $cmd, 'success' => true, 'result' => $r];
    }
    
    if (preg_match('/执行命令：(.+)/', $cmd, $m)) {
        $r = $baota->executeCommand($m[1]);
        return ['cmd' => $cmd, 'success' => true, 'result' => $r];
    }
    
    if (preg_match('/创建网站：(.+)/', $cmd, $m)) {
        $domain = $m[1];
        $r = $baota->addSite($domain, $domain, '/www/wwwroot/'.$domain, '73');
        return ['cmd' => $cmd, 'success' => true, 'result' => $r];
    }
    
    if (preg_match('/查看文件：(.+)/', $cmd, $m)) {
        $r = $baota->readFile($m[1]);
        return ['cmd' => $cmd, 'success' => true, 'result' => $r];
    }
    
    if (preg_match('/写入文件：(.+?) 内容：(.+)/s', $cmd, $m)) {
        $r = $baota->writeFile($m[1], $m[2]);
        return ['cmd' => $cmd, 'success' => true, 'result' => $r];
    }
    
    return ['cmd' => $cmd, 'success' => false, 'result' => '未识别的命令'];
}
