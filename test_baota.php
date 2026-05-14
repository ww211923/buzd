<?php
require_once __DIR__ . '/BaoTaAPI.php';

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>宝塔 API 测试</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 10px 0; }
    pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; overflow-x: auto; }
    h2 { color: #333; }
</style></head><body>";
echo "<h1>🔧 宝塔 API 测试工具</h1>";

$panel_url = 'https://38.207.177.103:35957';
$api_key = 'BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu';
$api_secret = ''; // 用户说不需要这个

echo "<div class='info'>";
echo "<strong>测试配置：</strong><br>";
echo "面板地址: $panel_url<br>";
echo "API Key: $api_key<br>";
echo "API Secret: " . ($api_secret ?: '空') . "<br>";
echo "</div>";

echo "<h2>测试 1: 获取面板信息</h2>";
$baota = new BaoTaAPI($panel_url, $api_key, $api_secret);

echo "<h3>尝试方式 A: API Key + Secret 签名</h3>";
$result = $baota->getPanelInfo();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

if (isset($result['code']) && $result['code'] === 1) {
    echo "<p class='success'>✅ 方式A成功！</p>";
} else {
    echo "<p class='error'>❌ 方式A失败: " . ($result['msg'] ?? '未知错误') . "</p>";

    echo "<h3>尝试方式 B: 仅使用面板地址登录获取Cookie</h3>";
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $panel_url . '/site?action=GetPanelInfo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "<p>HTTP状态码: $http_code</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h2>测试 2: 获取系统状态</h2>";
$result = $baota->getSystemTotal();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>测试 3: 获取网站列表</h2>";
$result = $baota->getSites();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>测试 4: 获取数据库列表</h2>";
$result = $baota->getDatabaseList();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>测试 5: 执行 Shell 命令</h2>";
$result = $baota->executeCommand('echo "Hello BT Panel"; uname -a; free -m');
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "</body></html>";
