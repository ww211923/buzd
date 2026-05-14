<?php
require_once __DIR__ . '/includes/BaoTaAPI.php';
require_once __DIR__ . '/includes/AICommandParser.php';

echo "=== DeepSeek + 宝塔 API 测试 ===\n\n";

// 配置
$panel_url = 'https://38.207.177.103:35957';
$api_key = 'BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu';

echo "1. 测试 BaoTaAPI 类初始化...\n";
$baota = new BaoTaAPI($panel_url, $api_key);
echo "   ✓ 初始化成功\n\n";

echo "2. 测试获取系统状态...\n";
$result = $baota->getSystemTotal();
if (isset($result['cpuRealUsed'])) {
    echo "   ✓ 系统状态获取成功\n";
    echo "   CPU: {$result['cpuRealUsed']}%\n";
    echo "   内存: {$result['memRealUsed']}MB / {$result['memTotal']}MB\n\n";
} else {
    echo "   ✗ 获取失败\n";
    echo "   结果: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n\n";
}

echo "3. 测试获取网站列表...\n";
$result = $baota->getSites();
echo "   结果: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n\n";

echo "4. 测试 AICommandParser...\n";
$parser = new AICommandParser($baota);
$result = $parser->parseAndExecute('查看系统状态');
echo "   结果: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n\n";

echo "5. 测试执行命令...\n";
$result = $baota->executeCommand('ls -la');
echo "   结果: " . json_encode($result, JSON_UNESCAPED_UNICODE) . "\n\n";

echo "=== 测试完成 ===\n";
