<?php
require_once __DIR__ . '/includes/BaoTaAPI.php';

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>宝塔 API 测试</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    .success { color: green; font-weight: bold; font-size: 1.2em; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 10px 0; }
    pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; overflow-x: auto; max-height: 400px; }
    h2 { color: #333; border-bottom: 2px solid #28a745; padding-bottom: 10px; }
</style></head><body>";
echo "<h1>✅ 宝塔 API 测试工具</h1>";

$panel_url = 'https://38.207.177.103:35957';
$api_key = 'BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu';

echo "<div class='info'>";
echo "<strong>📋 当前配置：</strong><br>";
echo "面板地址: $panel_url<br>";
echo "API Key: $api_key<br>";
echo "<br><strong>🔑 签名算法：</strong>md5(request_time + md5(API_KEY)) ✅ 已修复<br>";
echo "</div>";

$baota = new BaoTaAPI($panel_url, $api_key);

echo "<h2>1️⃣ 获取系统状态</h2>";
$result = $baota->getSystemTotal();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

if (isset($result['cpuRealUsed'])) {
    echo "<p class='success'>✅ 系统状态获取成功！</p>";
}

echo "<h2>2️⃣ 获取网站列表</h2>";
$result = $baota->getSites();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>3️⃣ 获取数据库列表</h2>";
$result = $baota->getDatabaseList();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>4️⃣ 执行 Shell 命令</h2>";
$result = $baota->executeCommand('uname -a && free -m');
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>5️⃣ 获取 PHP 版本</h2>";
$result = $baota->getPhpVersion();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>6️⃣ 获取磁盘信息</h2>";
$result = $baota->getDiskInfo();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>7️⃣ 面板日志</h2>";
$result = $baota->getLogs(5);
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>8️⃣ 连接测试</h2>";
$connected = $baota->testConnection();
if ($connected) {
    echo "<p class='success'>✅ 宝塔面板连接成功！</p>";
} else {
    echo "<p class='error'>❌ 连接失败</p>";
}

echo "</body></html>";
