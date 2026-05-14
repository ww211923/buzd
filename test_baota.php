<?php
require_once __DIR__ . '/includes/BaoTaAPI.php';

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>宝塔 API 测试</title>";
echo "<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { background: #f0f0f0; padding: 15px; border-radius: 8px; margin: 10px 0; }
    pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 8px; overflow-x: auto; max-height: 400px; }
    h2 { color: #333; border-bottom: 2px solid #0066CC; padding-bottom: 10px; }
</style></head><body>";
echo "<h1>🔧 宝塔 API 测试工具</h1>";

$panel_url = 'https://38.207.177.103:35957';
$api_key = 'BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu';

echo "<div class='info'>";
echo "<strong>📋 当前配置：</strong><br>";
echo "面板地址: $panel_url<br>";
echo "API Key: $api_key<br>";
echo "</div>";

// ==================== 方式1: 直接请求测试 ====================
echo "<h2>方式1: 直接 cURL 请求 (http_build_query)</h2>";

$cookie_file = __DIR__ . '/.cookie_test1';
touch($cookie_file);

$now_time = time();
$request_token = md5($now_time . '' . $api_key);

$p_data = http_build_query([
    'request_token' => $request_token,
    'request_time' => $now_time,
    'table' => 'sites',
    'limit' => 5
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $panel_url . '/data?action=getData');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $p_data);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP: $http_code | Token: $request_token</p>";
echo "<pre>" . $result . "</pre>";

// ==================== 方式2: JSON 格式请求 ====================
echo "<h2>方式2: JSON 格式请求</h2>";

$cookie_file2 = __DIR__ . '/.cookie_test2';
touch($cookie_file2);

$now_time2 = time();
$request_token2 = md5($now_time2 . '' . $api_key);

$json_data = json_encode([
    'request_token' => $request_token2,
    'request_time' => $now_time2,
    'table' => 'sites',
    'limit' => 5
]);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $panel_url . '/data?action=getData');
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch2, CURLOPT_COOKIEJAR, $cookie_file2);
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookie_file2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch2, CURLOPT_TIMEOUT, 30);

$result2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "<p>HTTP: $http_code2 | Token: $request_token2</p>";
echo "<pre>" . $result2 . "</pre>";

// ==================== 方式3: 先访问面板获取Cookie ====================
echo "<h2>方式3: 先获取面板Cookie</h2>";

$cookie_file3 = __DIR__ . '/.cookie_test3';
touch($cookie_file3);

// 先访问面板首页
$ch3a = curl_init();
curl_setopt($ch3a, CURLOPT_URL, $panel_url);
curl_setopt($ch3a, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3a, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch3a, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch3a, CURLOPT_COOKIEJAR, $cookie_file3);
curl_setopt($ch3a, CURLOPT_COOKIEFILE, $cookie_file3);
curl_setopt($ch3a, CURLOPT_TIMEOUT, 30);
$home_result = curl_exec($ch3a);
curl_close($ch3a);

echo "<p>首页访问结果: " . strlen($home_result) . " bytes</p>";
echo "<pre>" . substr($home_result, 0, 500) . "...</pre>";

// 再用获取的Cookie请求API
$now_time3 = time();
$request_token3 = md5($now_time3 . '' . $api_key);

$p_data3 = http_build_query([
    'request_token' => $request_token3,
    'request_time' => $now_time3,
    'table' => 'sites',
    'limit' => 5
]);

$ch3b = curl_init();
curl_setopt($ch3b, CURLOPT_URL, $panel_url . '/data?action=getData');
curl_setopt($ch3b, CURLOPT_POST, true);
curl_setopt($ch3b, CURLOPT_POSTFIELDS, $p_data3);
curl_setopt($ch3b, CURLOPT_COOKIEJAR, $cookie_file3);
curl_setopt($ch3b, CURLOPT_COOKIEFILE, $cookie_file3);
curl_setopt($ch3b, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch3b, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch3b, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch3b, CURLOPT_TIMEOUT, 30);

$result3 = curl_exec($ch3b);
$http_code3 = curl_getinfo($ch3b, CURLINFO_HTTP_CODE);
curl_close($ch3b);

echo "<p>API请求 HTTP: $http_code3</p>";
echo "<pre>" . $result3 . "</pre>";

// ==================== 方式4: BaoTaAPI 类 ====================
echo "<h2>方式4: BaoTaAPI 类</h2>";
$baota = new BaoTaAPI($panel_url, $api_key);
$result4 = $baota->getSystemTotal();
echo "<pre>" . json_encode($result4, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "<h2>方式5: 获取网站列表</h2>";
$result5 = $baota->getSites();
echo "<pre>" . json_encode($result5, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

echo "</body></html>";
