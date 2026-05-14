<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/BaoTaAPI.php';
require_once __DIR__ . '/AICommandParser.php';

$input = json_decode(file_get_contents('php://input'), true);

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

    case 'execute_ai_command':
        handleAICommand($input);
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

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function handleTestConnection($input) {
    if (empty($input['panel_url']) || empty($input['api_key']) || empty($input['api_secret'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $baota = new BaoTaAPI(
        $input['panel_url'],
        $input['api_key'],
        $input['api_secret']
    );

    $result = $baota->testConnection();
    echo json_encode(['success' => $result, 'message' => $result ? '连接成功' : '连接失败']);
}

function handleAICommand($input) {
    if (empty($input['panel_url']) || empty($input['api_key']) || empty($input['api_secret'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    if (empty($input['command'])) {
        echo json_encode(['success' => false, 'message' => 'Command is required']);
        exit();
    }

    $baota = new BaoTaAPI(
        $input['panel_url'],
        $input['api_key'],
        $input['api_secret']
    );

    $parser = new AICommandParser($baota);
    $result = $parser->parseAndExecute($input['command']);

    echo json_encode($result);
}

function handleServerStatus($input) {
    if (empty($input['panel_url']) || empty($input['api_key']) || empty($input['api_secret'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $baota = new BaoTaAPI(
        $input['panel_url'],
        $input['api_key'],
        $input['api_secret']
    );

    $parser = new AICommandParser($baota);
    $result = $parser->parseAndExecute('查看系统状态');

    echo json_encode($result);
}

function handleGetAllInfo($input) {
    if (empty($input['panel_url']) || empty($input['api_key']) || empty($input['api_secret'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    $baota = new BaoTaAPI(
        $input['panel_url'],
        $input['api_key'],
        $input['api_secret']
    );

    $result = $baota->getAllInfo();
    echo json_encode(['success' => true, 'data' => $result]);
}

function handleDirectAPI($input) {
    if (empty($input['panel_url']) || empty($input['api_key']) || empty($input['api_secret'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit();
    }

    if (empty($input['bt_action'])) {
        echo json_encode(['success' => false, 'message' => 'BT action is required']);
        exit();
    }

    $baota = new BaoTaAPI(
        $input['panel_url'],
        $input['api_key'],
        $input['api_secret']
    );

    $method = $input['method'] ?? 'POST';
    $data = $input['data'] ?? [];

    $result = $baota->makeRequest($input['bt_action'], $data, $method);
    echo json_encode(['success' => true, 'data' => $result]);
}
