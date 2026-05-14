<?php
require_once __DIR__ . '/includes/BaoTaAPI.php';

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>宝塔 API 测试</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    .info { background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 10px 0; }
    pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; overflow-x: auto; max-height: 400px; overflow-y: auto; }
    h2 { color: #333; border-bottom: 2px solid #0066CC; padding-bottom: 10px; }
    .status-card { display: inline-block; background: #f8f9fa; padding: 15px 25px; border-radius: 10px; margin: 5px; text-align: center; }
    .status-card .value { font-size: 2rem; font-weight: bold; color: #0066CC; }
    .status-card .label { font-size: 0.9rem; color: #666; }
</style></head><body>";
echo "<h1>🔧 宝塔 API 测试工具</h1>";

// 使用正确的配置
$panel_url = 'https://38.207.177.103:35957';
$api_key = 'BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu';

echo "<div class='info'>";
echo "<strong>📋 当前配置：</strong><br>";
echo "面板地址: $panel_url<br>";
echo "API Key: $api_key<br>";
echo "</div>";

$baota = new BaoTaAPI($panel_url, $api_key);

echo "<h2>1️⃣ 获取系统状态</h2>";
$result = $baota->getSystemTotal();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

if (isset($result['cpuRealUsed'])) {
    echo "<div class='info'>";
    echo "<strong>📊 系统信息：</strong><br>";
    echo "<div class='status-card'><div class='value'>" . $result['cpuRealUsed'] . "%</div><div class='label'>CPU使用率</div></div>";
    echo "<div class='status-card'><div class='value'>" . $result['memRealUsed'] . "MB</div><div class='label'>已用内存</div></div>";
    echo "<div class='status-card'><div class='value'>" . $result['memTotal'] . "MB</div><div class='label'>总内存</div></div>";
    echo "<div class='status-card'><div class='value'>" . $result['version'] . "</div><div class='label'>面板版本</div></div>";
    echo "</div>";
}

echo "<h2>2️⃣ 获取网站列表</h2>";
$result = $baota->getSites();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>3️⃣ 获取数据库列表</h2>";
$result = $baota->getDatabaseList();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>4️⃣ 执行 Shell 命令</h2>";
$cmd_result = $baota->executeCommand('echo "Hello BT Panel API"; uname -a; echo "---"; free -m; echo "---"; df -h');
echo "<pre>" . json_encode($cmd_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>5️⃣ 获取 PHP 版本</h2>";
$result = $baota->getPhpVersion();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>6️⃣ 获取磁盘信息</h2>";
$result = $baota->getDiskInfo();
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>7️⃣ 获取面板日志</h2>";
$result = $baota->getLogs(10);
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "</body></html>";
