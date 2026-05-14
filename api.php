<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['api_key'])) {
    http_response_code(400);
    echo json_encode(['error' => 'API key is required']);
    exit();
}

$apiKey = $input['api_key'];
$messages = $input['messages'] ?? [];
$model = $input['model'] ?? 'deepseek-v4-pro';
$thinking = $input['thinking'] ?? false;

if (empty($messages)) {
    http_response_code(400);
    echo json_encode(['error' => 'Messages are required']);
    exit();
}

$requestData = [
    'model' => $model,
    'messages' => $messages,
    'stream' => true
];

if ($thinking && $model === 'deepseek-v4-pro') {
    $requestData['thinking'] = ['type' => 'enabled'];
    $requestData['reasoning_effort'] = 'high';
}

$ch = curl_init('https://api.deepseek.com/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_WRITEFUNCTION => function($curl, $data) {
        echo $data;
        flush();
        ob_flush();
        return strlen($data);
    },
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_TIMEOUT => 120,
    CURLOPT_HTTP200ALIASES => [200]
]);

curl_exec($ch);

if (curl_errno($ch)) {
    $error = curl_error($ch);
    http_response_code(500);
    echo json_encode(['error' => 'API request failed: ' . $error]);
}

curl_close($ch);
