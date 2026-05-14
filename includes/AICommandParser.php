<?php
class AICommandParser {
    private $baota;

    public function __construct($baota) {
        $this->baota = $baota;
    }

    public function parseAndExecute($userCommand) {
        $command = strtolower(trim($userCommand));
        $results = [];

        if (strpos($command, '状态') !== false || strpos($command, '系统信息') !== false || $command === 'status' || $command === 'info') {
            return $this->getSystemStatus();
        }

        if (strpos($command, '创建网站') !== false || strpos($command, '新建网站') !== false || strpos($command, '添加网站') !== false) {
            return $this->createWebsite($userCommand);
        }

        if (strpos($command, '删除网站') !== false) {
            return $this->deleteWebsite($userCommand);
        }

        if (strpos($command, '重启网站') !== false || strpos($command, '重载网站') !== false) {
            return $this->restartWebsite($userCommand);
        }

        if (strpos($command, '停止网站') !== false) {
            return $this->stopWebsite($userCommand);
        }

        if (strpos($command, '启动网站') !== false) {
            return $this->startWebsite($userCommand);
        }

        if (strpos($command, '网站列表') !== false || strpos($command, '所有网站') !== false) {
            return $this->listWebsites();
        }

        if (strpos($command, '数据库列表') !== false || strpos($command, '所有数据库') !== false) {
            return $this->listDatabases();
        }

        if (strpos($command, '创建数据库') !== false || strpos($command, '新建数据库') !== false) {
            return $this->createDatabase($userCommand);
        }

        if (strpos($command, '删除数据库') !== false) {
            return $this->deleteDatabase($userCommand);
        }

        if (strpos($command, '查看文件') !== false || strpos($command, '列出文件') !== false) {
            return $this->listFiles($userCommand);
        }

        if (strpos($command, '读取文件') !== false || strpos($command, '查看文件内容') !== false) {
            return $this->readFileContent($userCommand);
        }

        if (strpos($command, '写入文件') !== false || strpos($command, '创建文件') !== false || strpos($command, '编辑文件') !== false) {
            return $this->writeFile($userCommand);
        }

        if (strpos($command, '删除文件') !== false) {
            return $this->deleteFile($userCommand);
        }

        if (strpos($command, '执行命令') !== false || strpos($command, '运行命令') !== false || strpos($command, 'shell') !== false) {
            return $this->executeCommand($userCommand);
        }

        if (strpos($command, 'php版本') !== false || strpos($command, 'PHP版本') !== false) {
            return $this->getPhpVersions();
        }

        if (strpos($command, '切换php') !== false || strpos($command, '更改php') !== false) {
            return $this->changePhpVersion($userCommand);
        }

        if (strpos($command, '磁盘') !== false || strpos($command, '硬盘') !== false) {
            return $this->getDiskInfo();
        }

        if (strpos($command, '计划任务') !== false || strpos($command, '定时任务') !== false) {
            return $this->manageCrontab($userCommand);
        }

        if (strpos($command, 'docker') !== false || strpos($command, '容器') !== false) {
            return $this->manageDocker($userCommand);
        }

        return $this->getHelp();
    }

    private function getSystemStatus() {
        $result = $this->baota->getSystemTotal();
        if (isset($result['code']) && $result['code'] !== 1) {
            return ['success' => false, 'message' => '获取系统状态失败: ' . ($result['msg'] ?? '未知错误')];
        }

        $network = $this->baota->getNetwork();
        $disk = $this->baota->getDiskInfo();

        $status = "📊 **系统状态报告**\n\n";
        $status .= "**服务器信息:**\n";
        $status .= "- 操作系统: {$result['system']}\n";
        $status .= "- 面板版本: {$result['version']}\n";
        $status .= "- 运行时间: {$result['time']}\n\n";

        $status .= "**CPU 信息:**\n";
        $status .= "- 核心数: {$result['cpuNum']} 核\n";
        $status .= "- 使用率: {$result['cpuRealUsed']}%\n\n";

        $status .= "**内存信息:**\n";
        $status .= "- 总内存: {$result['memTotal']} MB\n";
        $status .= "- 已使用: {$result['memRealUsed']} MB\n";
        $status .= "- 可用: {$result['memFree']} MB\n";
        $status .= "- 缓存: {$result['memCached']} MB\n\n";

        if (isset($network['down']) && isset($network['up'])) {
            $status .= "**网络流量:**\n";
            $status .= "- 下载: {$network['down']} KB/s\n";
            $status .= "- 上传: {$network['up']} KB/s\n\n";
        }

        if (!empty($disk) && is_array($disk)) {
            $status .= "**磁盘信息:**\n";
            foreach ($disk as $partition) {
                if (isset($partition['path'])) {
                    $size = $partition['size'] ?? [];
                    $status .= "- {$partition['path']}: {$size[0]}/{$size[1]} 可用 (使用 {$size[3]})\n";
                }
            }
        }

        return ['success' => true, 'message' => $status, 'data' => $result];
    }

    private function createWebsite($command) {
        $domain = $this->extractDomain($command);
        $path = $this->extractPath($command);
        $php = $this->extractPhpVersion($command);

        if (!$domain) {
            return ['success' => false, 'message' => '❌ 无法识别域名，请提供完整的域名信息。'];
        }

        if (!$path) {
            $path = '/www/wwwroot/' . str_replace(['http://', 'https://', 'www.'], '', $domain);
        }

        $result = $this->baota->addSite($domain, $domain, $path, $php);

        if (isset($result['code']) && $result['code'] === 1) {
            return [
                'success' => true,
                'message' => "✅ 网站创建成功！\n\n📋 **网站信息:**\n- 域名: {$domain}\n- 路径: {$path}\n- PHP版本: {$php}\n- 网站ID: " . ($result['siteId'] ?? ''),
                'data' => $result
            ];
        }

        return ['success' => false, 'message' => '❌ 网站创建失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function deleteWebsite($command) {
        $siteName = $this->extractSiteName($command);

        if (!$siteName) {
            return ['success' => false, 'message' => '❌ 请提供要删除的网站名称。'];
        }

        $sites = $this->baota->getSites();
        $siteId = $this->findSiteId($siteName, $sites);

        if (!$siteId) {
            return ['success' => false, 'message' => "❌ 找不到网站: {$siteName}"];
        }

        $result = $this->baota->deleteSite($siteId);

        if (isset($result['code']) && $result['code'] === 1) {
            return ['success' => true, 'message' => "✅ 网站 {$siteName} 已删除", 'data' => $result];
        }

        return ['success' => false, 'message' => '❌ 删除失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function restartWebsite($command) {
        $siteName = $this->extractSiteName($command);
        if (!$siteName) {
            return ['success' => false, 'message' => '❌ 请提供要重启的网站名称。'];
        }

        $result = $this->baota->restartSite($siteName);
        return $this->formatResult($result, "网站 {$siteName} 已重启");
    }

    private function stopWebsite($command) {
        $siteName = $this->extractSiteName($command);
        if (!$siteName) {
            return ['success' => false, 'message' => '❌ 请提供要停止的网站名称。'];
        }

        $result = $this->baota->stopSite($siteName);
        return $this->formatResult($result, "网站 {$siteName} 已停止");
    }

    private function startWebsite($command) {
        $siteName = $this->extractSiteName($command);
        if (!$siteName) {
            return ['success' => false, 'message' => '❌ 请提供要启动的网站名称。'];
        }

        $result = $this->baota->startSite($siteName);
        return $this->formatResult($result, "网站 {$siteName} 已启动");
    }

    private function listWebsites() {
        $result = $this->baota->getSites();

        if (!isset($result['data']) || empty($result['data'])) {
            return ['success' => true, 'message' => "📋 当前没有网站。\n\n可以使用「创建网站」命令来添加新网站。"];
        }

        $message = "📋 **网站列表** (共 " . count($result['data']) . " 个)\n\n";
        $message .= "| 域名 | 路径 | 状态 | 添加时间 |\n";
        $message .= "|------|------|------|----------|\n";

        foreach ($result['data'] as $site) {
            $status = ($site['status'] == '1') ? '🟢 运行中' : '🔴 已停止';
            $message .= "| {$site['name']} | {$site['path']} | {$status} | {$site['addtime']} |\n";
        }

        return ['success' => true, 'message' => $message, 'data' => $result['data']];
    }

    private function listDatabases() {
        $result = $this->baota->getDatabaseList();

        $message = "🗄️ **数据库列表**\n\n";

        if (isset($result['data']) && !empty($result['data'])) {
            $message .= "| 数据库名 | 用户 | 编码 | 添加时间 |\n";
            $message .= "|----------|------|------|----------|\n";
            foreach ($result['data'] as $db) {
                $message .= "| {$db['name']} | {$db['username']} | {$db['code']} | {$db['addtime']} |\n";
            }
        } else {
            $message .= "当前没有数据库。\n";
        }

        return ['success' => true, 'message' => $message, 'data' => $result];
    }

    private function createDatabase($command) {
        $name = $this->extractDatabaseName($command);

        if (!$name) {
            return ['success' => false, 'message' => '❌ 请提供数据库名称。'];
        }

        $result = $this->baota->addDatabase($name);

        if (isset($result['code']) && $result['code'] === 1) {
            return [
                'success' => true,
                'message' => "✅ 数据库创建成功！\n\n📋 **数据库信息:**\n- 名称: {$name}\n- 用户: {$name}\n- 编码: utf8mb4\n- 密码: (已在面板中生成)",
                'data' => $result
            ];
        }

        return ['success' => false, 'message' => '❌ 数据库创建失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function deleteDatabase($command) {
        $name = $this->extractDatabaseName($command);

        if (!$name) {
            return ['success' => false, 'message' => '❌ 请提供要删除的数据库名称。'];
        }

        $result = $this->baota->deleteDatabase($name);
        return $this->formatResult($result, "数据库 {$name} 已删除");
    }

    private function listFiles($command) {
        $path = $this->extractFilePath($command);
        if (!$path) {
            $path = '/www/wwwroot';
        }

        $result = $this->baota->getFilesList($path);

        $message = "📁 **文件列表:** `{$path}`\n\n";

        if (isset($result['data']) && !empty($result['data'])) {
            foreach ($result['data'] as $file) {
                $icon = ($file['type'] === 'dir') ? '📁' : '📄';
                $size = isset($file['size']) ? $this->formatSize($file['size']) : '';
                $message .= "{$icon} {$file['name']} {$size}\n";
            }
        } else {
            $message .= "目录为空或无法访问。\n";
        }

        return ['success' => true, 'message' => $message, 'data' => $result];
    }

    private function readFileContent($command) {
        $path = $this->extractFilePath($command);

        if (!$path) {
            return ['success' => false, 'message' => '❌ 请提供文件路径。'];
        }

        $result = $this->baota->readFile($path);

        if (isset($result['code']) && $result['code'] === 1) {
            $content = $result['data'] ?? '';
            $preview = strlen($content) > 1000 ? substr($content, 0, 1000) . "\n\n... (文件过长，已截断)" : $content;
            return [
                'success' => true,
                'message' => "📄 **文件内容:** `{$path}`\n\n```\n{$preview}\n```",
                'data' => ['path' => $path, 'content' => $content]
            ];
        }

        return ['success' => false, 'message' => '❌ 读取文件失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function writeFile($command) {
        preg_match('/文件路径[:：]\s*([^\s]+)/i', $command, $pathMatch);
        preg_match('/内容[:：]\s*(.+)/i', $command, $contentMatch);

        $path = $pathMatch[1] ?? null;
        $content = $contentMatch[1] ?? '';

        if (!$path) {
            preg_match('/在\s+(.+?)\s+写入/i', $command, $pathMatch);
            $path = $pathMatch[1] ?? null;
        }

        if (!$path) {
            return ['success' => false, 'message' => '❌ 请提供完整的文件路径和内容。'];
        }

        $fileContent = $this->baota->readFile($path);
        if (isset($fileContent['code']) && $fileContent['code'] === 1) {
            $content = $fileContent['data'] ?? '';
        }

        $result = $this->baota->writeFile($path, $content);

        if (isset($result['code']) && $result['code'] === 1) {
            return ['success' => true, 'message' => "✅ 文件已写入: `{$path}`", 'data' => ['path' => $path]];
        }

        return ['success' => false, 'message' => '❌ 写入文件失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function deleteFile($command) {
        $path = $this->extractFilePath($command);

        if (!$path) {
            return ['success' => false, 'message' => '❌ 请提供要删除的文件路径。'];
        }

        $result = $this->baota->deleteFile($path);
        return $this->formatResult($result, "文件 {$path} 已删除");
    }

    private function executeCommand($command) {
        preg_match('/命令[:：]\s*(.+)/i', $command, $cmdMatch);
        preg_match('/执行\s+(.+)/i', $command, $execMatch);

        $cmd = $cmdMatch[1] ?? $execMatch[1] ?? null;

        if (!$cmd) {
            preg_match('/shell[:：]\s*(.+)/i', $command, $shellMatch);
            $cmd = $shellMatch[1] ?? null;
        }

        if (!$cmd) {
            return ['success' => false, 'message' => '❌ 请提供要执行的命令。'];
        }

        $result = $this->baota->executeCommand($cmd);

        if (isset($result['code']) && $result['code'] === 1) {
            $output = $result['msg'] ?? '';
            return [
                'success' => true,
                'message' => "✅ **命令执行成功**\n\n```bash\n{$cmd}\n```\n**输出:**\n```\n{$output}\n```",
                'data' => ['command' => $cmd, 'output' => $output]
            ];
        }

        return ['success' => false, 'message' => '❌ 命令执行失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function getPhpVersions() {
        $result = $this->baota->getPhpVersion();

        $message = "🐘 **PHP 版本信息**\n\n";

        if (isset($result['data']) && !empty($result['data'])) {
            foreach ($result['data'] as $php) {
                $status = ($php['status'] == 'run') ? '🟢 运行中' : '🔴 已停止';
                $message .= "- PHP {$php['version']}: {$status}\n";
            }
        } else {
            $message .= "未找到 PHP 版本信息。\n";
        }

        return ['success' => true, 'message' => $message, 'data' => $result];
    }

    private function changePhpVersion($command) {
        preg_match('/php[\s-]?(\d+[\.\d]*)/i', $command, $versionMatch);
        $version = $versionMatch[1] ?? '74';

        preg_match('/网站[:：]\s*([^\s]+)/i', $command, $siteMatch);
        $siteName = $siteMatch[1] ?? null;

        if (!$siteName) {
            preg_match('/为\s+(.+?)\s+切换/i', $command, $siteMatch);
            $siteName = $siteMatch[1] ?? null;
        }

        if (!$siteName) {
            return ['success' => false, 'message' => '❌ 请提供网站名称。'];
        }

        $result = $this->baota->setPhpVersion($siteName, $version);
        return $this->formatResult($result, "网站 {$siteName} PHP 版本已切换到 {$version}");
    }

    private function getDiskInfo() {
        $result = $this->baota->getDiskInfo();

        $message = "💾 **磁盘信息**\n\n";

        if (!empty($result) && is_array($result)) {
            foreach ($result as $partition) {
                if (isset($partition['path'])) {
                    $size = $partition['size'] ?? [];
                    $inodes = $partition['inodes'] ?? [];
                    $message .= "📍 **{$partition['path']}**\n";
                    $message .= "- 容量: {$size[0]} (已用 {$size[1]}, 可用 {$size[2]}, {$size[3]})\n";
                    $message .= "- Inode: {$inodes[0]} (已用 {$inodes[1]}, 可用 {$inodes[2]}, {$inodes[3]})\n\n";
                }
            }
        } else {
            $message .= "无法获取磁盘信息。\n";
        }

        return ['success' => true, 'message' => $message, 'data' => $result];
    }

    private function manageCrontab($command) {
        if (strpos($command, '列表') !== false || strpos($command, '查看') !== false) {
            $result = $this->baota->getCrontabList();

            $message = "⏰ **计划任务列表**\n\n";

            if (isset($result['data']) && !empty($result['data'])) {
                foreach ($result['data'] as $task) {
                    $status = ($task['status'] == '1') ? '🟢 启用' : '🔴 禁用';
                    $message .= "- {$task['name']} ({$task['type']}) {$status}\n";
                }
            } else {
                $message .= "当前没有计划任务。\n";
            }

            return ['success' => true, 'message' => $message, 'data' => $result];
        }

        return ['success' => false, 'message' => '❌ 请说明要进行的操作，如「查看计划任务」或「添加计划任务」。'];
    }

    private function manageDocker($command) {
        if (strpos($command, '容器列表') !== false || strpos($command, '容器') !== false) {
            $result = $this->baota->getDockerContainers();

            $message = "🐳 **Docker 容器列表**\n\n";

            if (isset($result['data']) && !empty($result['data'])) {
                foreach ($result['data'] as $container) {
                    $status = ($container['state'] == 'running') ? '🟢 运行中' : '🔴 已停止';
                    $message .= "- {$container['names']}: {$status}\n";
                }
            } else {
                $message .= "当前没有容器。\n";
            }

            return ['success' => true, 'message' => $message, 'data' => $result];
        }

        return ['success' => false, 'message' => '❌ 请说明要进行的 Docker 操作。'];
    }

    private function getHelp() {
        $help = "🤖 **可用命令列表**\n\n";

        $help .= "**系统操作:**\n";
        $help .= "- 查看系统状态 / 系统信息\n";
        $help .= "- 查看磁盘信息\n";
        $help .= "- 查看 PHP 版本\n\n";

        $help .= "**网站管理:**\n";
        $help .= "- 创建网站 [域名]\n";
        $help .= "- 删除网站 [域名]\n";
        $help .= "- 重启网站 [域名]\n";
        $help .= "- 停止网站 [域名]\n";
        $help .= "- 启动网站 [域名]\n";
        $help .= "- 网站列表\n";
        $help .= "- 切换 PHP 版本 [网站] PHP[版本号]\n\n";

        $help .= "**数据库管理:**\n";
        $help .= "- 创建数据库 [名称]\n";
        $help .= "- 删除数据库 [名称]\n";
        $help .= "- 数据库列表\n\n";

        $help .= "**文件管理:**\n";
        $help .= "- 查看文件 [路径]\n";
        $help .= "- 读取文件 [路径]\n";
        $help .= "- 写入文件 [路径] 内容:xxx\n";
        $help .= "- 删除文件 [路径]\n\n";

        $help .= "**其他:**\n";
        $help .= "- 执行命令 shell:xxx\n";
        $help .= "- Docker 容器列表\n";
        $help .= "- 计划任务列表\n";

        return ['success' => true, 'message' => $help];
    }

    private function formatResult($result, $successMsg) {
        if (isset($result['code']) && $result['code'] === 1) {
            return ['success' => true, 'message' => "✅ {$successMsg}", 'data' => $result];
        }
        return ['success' => false, 'message' => '❌ 操作失败: ' . ($result['msg'] ?? '未知错误')];
    }

    private function extractDomain($command) {
        preg_match('/(?:创建|新建|添加)?\s*(?:网站|站点)\s*(?:名为|叫)?\s*([a-zA-Z0-9\-\.]+(?:\.(?:com|cn|net|org|info|xyz|top|cc|io|me|co| biz|info|mobi|name|tv|la|ru|us|in|me|cc|co|bz|hn|vc|ag|mg|cm|gs|ki|mu|nu|sc|tc|tm|to|ug|ve|vg|ws)))/i', $command, $matches);
        if (empty($matches)) {
            preg_match('/([a-zA-Z0-9\-]+\.(?:com|cn|net|org|info|xyz|top|cc|io|me|co|biz))/i', $command, $matches);
        }
        return $matches[1] ?? null;
    }

    private function extractPath($command) {
        preg_match('/路径[:：]\s*([^\s]+)/i', $command, $matches);
        if (empty($matches)) {
            preg_match('/在\s+(\/[^\s]+)\s+/i', $command, $matches);
        }
        return $matches[1] ?? null;
    }

    private function extractPhpVersion($command) {
        preg_match('/php[\s-]?(\d+)/i', $command, $matches);
        return $matches[1] ?? '74';
    }

    private function extractSiteName($command) {
        preg_match('/(?:网站|站点)[:：]?\s*([^\s]+)/i', $command, $matches);
        if (empty($matches)) {
            preg_match('/对\s+([^\s]+)\s+/i', $command, $matches);
        }
        return $matches[1] ?? null;
    }

    private function extractDatabaseName($command) {
        preg_match('/(?:数据库|库)[:：]?\s*([^\s]+)/i', $command, $matches);
        return $matches[1] ?? null;
    }

    private function extractFilePath($command) {
        preg_match('/(?:路径|文件)[:：]\s*([^\s]+)/i', $command, $matches);
        if (empty($matches)) {
            preg_match('/在\s+(\/[^\s]+)/i', $command, $matches);
        }
        return $matches[1] ?? null;
    }

    private function findSiteId($siteName, $sites) {
        if (!isset($sites['data']) || empty($sites['data'])) {
            return null;
        }

        foreach ($sites['data'] as $site) {
            if ($site['name'] === $siteName || strpos($site['name'], $siteName) !== false) {
                return $site['id'];
            }
        }
        return null;
    }

    private function formatSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < 4) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
