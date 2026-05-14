<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeepSeek + 宝塔面板 AI 控制系统</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .mode-switch {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
        }

        .mode-btn {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .mode-btn.active {
            border-color: #0066CC;
            background: #0066CC;
            color: white;
        }

        .mode-btn:hover:not(.active) {
            border-color: #0066CC;
        }

        .server-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            color: white;
        }

        .server-panel h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .server-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .server-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .server-item:hover {
            background: rgba(255, 255, 255, 0.25);
        }

        .server-item.active {
            background: rgba(255, 255, 255, 0.3);
            border: 2px solid white;
        }

        .server-item .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #ccc;
        }

        .server-item .status-dot.connected {
            background: #4CAF50;
        }

        .server-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .status-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }

        .status-card .value {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .status-card .label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .command-hints {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }

        .command-hints h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .hint-item {
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
            font-size: 0.9rem;
            color: #666;
            cursor: pointer;
            transition: all 0.3s;
        }

        .hint-item:hover {
            background: #e3f2fd;
            color: #0066CC;
        }

        .hint-item code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            color: #d63384;
        }

        .baota-settings {
            background: #fff3cd;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
        }

        .baota-settings h4 {
            color: #856404;
            margin-bottom: 10px;
        }

        .baota-settings ol {
            margin-left: 20px;
            color: #856404;
        }

        .baota-settings li {
            margin-bottom: 8px;
        }

        .ai-mode .message-content {
            white-space: pre-wrap;
        }

        .ai-mode .message-content code {
            display: block;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 10px 0;
        }

        .system-message {
            background: linear-gradient(135deg, #00b894 0%, #00cec9 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            text-align: center;
        }

        .tab-container {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .tab-btn {
            padding: 12px 24px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1rem;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: #0066CC;
            border-bottom-color: #0066CC;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }

        .quick-action-btn {
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }

        .quick-action-btn:hover {
            border-color: #0066CC;
            background: #f0f7ff;
        }

        .quick-action-btn .icon {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .quick-action-btn .label {
            font-size: 0.85rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🤖 DeepSeek + 宝塔 AI 控制台</h1>
            <div class="header-controls">
                <button id="refreshStatus" class="btn-clear" onclick="refreshServerStatus()">🔄 刷新状态</button>
                <button id="clearBtn" class="btn-clear" onclick="clearChat()">清空对话</button>
                <button id="settingsBtn" class="btn-settings" onclick="toggleSettings()">⚙️</button>
            </div>
        </header>

        <div class="mode-switch">
            <button class="mode-btn active" id="aiModeBtn" onclick="switchMode('ai')">
                <span>🤖</span> AI 智能模式
            </button>
            <button class="mode-btn" id="directModeBtn" onclick="switchMode('direct')">
                <span>⚡</span> 直接命令模式
            </button>
        </div>

        <div id="serverPanel" class="server-panel">
            <h3>🖥️ 已配置的服务器</h3>
            <div id="serverList" class="server-list">
                <div class="server-item" onclick="addNewServer()">
                    <span class="status-dot"></span>
                    <span>+ 添加新服务器</span>
                </div>
            </div>
            <div id="serverStatus" class="server-status" style="display: none;">
                <div class="status-card">
                    <div class="value" id="cpuValue">--</div>
                    <div class="label">CPU 使用率</div>
                </div>
                <div class="status-card">
                    <div class="value" id="memValue">--</div>
                    <div class="label">内存使用</div>
                </div>
                <div class="status-card">
                    <div class="value" id="diskValue">--</div>
                    <div class="label">磁盘使用</div>
                </div>
                <div class="status-card">
                    <div class="value" id="uptimeValue">--</div>
                    <div class="label">运行时间</div>
                </div>
            </div>
        </div>

        <div id="chatContainer" class="chat-container ai-mode">
            <div class="welcome-message">
                <div class="welcome-icon">🚀</div>
                <h2>DeepSeek + 宝塔 AI 控制台</h2>
                <p>通过 AI 智能助手管理您的宝塔面板服务器</p>
                <div class="command-hints">
                    <h4>💡 快捷指令</h4>
                    <div class="hint-item" onclick="insertCommand('查看系统状态')">📊 查看系统状态</div>
                    <div class="hint-item" onclick="insertCommand('创建网站 域名:example.com')">🌐 创建网站</div>
                    <div class="hint-item" onclick="insertCommand('查看网站列表')">📋 网站列表</div>
                    <div class="hint-item" onclick="insertCommand('查看数据库列表')">🗄️ 数据库列表</div>
                    <div class="hint-item" onclick="insertCommand('查看文件 /www/wwwroot')">📁 查看文件</div>
                    <div class="hint-item" onclick="insertCommand('执行命令:ls -la')">💻 执行命令</div>
                </div>
            </div>
        </div>

        <div class="input-container">
            <div class="input-wrapper">
                <textarea id="userInput" placeholder="输入 AI 指令，例如：帮我创建一个网站，域名为 example.com" rows="1"></textarea>
                <button id="sendBtn" class="send-btn" onclick="sendMessage()">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
            <div class="input-info">
                <span id="charCount">0 字符</span>
                <span id="responseTime"></span>
                <span id="modeIndicator">🤖 AI 模式</span>
            </div>
        </div>
    </div>

    <div id="settingsPanel" class="settings-panel hidden">
        <div class="settings-content">
            <div class="tab-container">
                <button class="tab-btn active" onclick="switchTab('deepseek')">DeepSeek API</button>
                <button class="tab-btn" onclick="switchTab('baota')">宝塔服务器</button>
                <button class="tab-btn" onclick="switchTab('help')">使用帮助</button>
            </div>

            <div id="deepseekTab" class="tab-content active">
                <h3>🔑 DeepSeek API 配置</h3>
                <div class="form-group">
                    <label for="apiKeyInput">API Key</label>
                    <input type="password" id="apiKeyInput" placeholder="sk-xxxxxxxxxxxxxxxx">
                </div>
                <div class="form-group">
                    <label for="modelSelect">选择模型</label>
                    <select id="modelSelect" class="model-select" style="width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 10px;">
                        <option value="deepseek-v4-pro">DeepSeek V4 Pro (深度思考)</option>
                        <option value="deepseek-v4-flash">DeepSeek V4 Flash (快速响应)</option>
                    </select>
                </div>
            </div>

            <div id="baotaTab" class="tab-content">
                <h3>🖥️ 宝塔服务器配置</h3>
                <div class="form-group">
                    <label for="serverName">服务器名称</label>
                    <input type="text" id="serverName" placeholder="例如：生产服务器">
                </div>
                <div class="form-group">
                    <label for="panelUrl">面板地址</label>
                    <input type="text" id="panelUrl" placeholder="https://your-server-ip:8888">
                </div>
                <div class="form-group">
                    <label for="btApiKey">API Key</label>
                    <input type="password" id="btApiKey" placeholder="在宝塔面板设置中获取">
                </div>
                <button id="testConnection" class="btn-save" onclick="testBtConnection()">🔗 测试连接</button>
                <button id="addServer" class="btn-save" onclick="saveServer()" style="background: #28a745;">➕ 添加服务器</button>
            </div>

            <div id="helpTab" class="tab-content">
                <h3>📖 使用帮助</h3>
                <div class="baota-settings">
                    <h4>⚠️ 宝塔面板 API 配置步骤</h4>
                    <ol>
                        <li>登录宝塔面板 → 点击右上角「设置」</li>
                        <li>找到「API 接口」选项，勾选「开启API接口」</li>
                        <li>点击「API密钥管理」→ 「生成密钥」</li>
                        <li>复制生成的 <strong>Key</strong> 和 <strong>Secret</strong></li>
                        <li>在下方填入面板地址、Key 和 Secret</li>
                    </ol>
                </div>
                <div class="form-group">
                    <label>🤖 AI 智能指令示例</label>
                    <div class="hint-item" onclick="insertCommand('查看系统状态')">📊 查看系统状态</div>
                    <div class="hint-item" onclick="insertCommand('帮我创建一个网站，域名为 test.com')">🌐 创建网站</div>
                    <div class="hint-item" onclick="insertCommand('查看所有网站的列表')">📋 网站列表</div>
                    <div class="hint-item" onclick="insertCommand('帮我创建一个数据库，名字叫 myapp')">🗄️ 创建数据库</div>
                    <div class="hint-item" onclick="insertCommand('查看 /www/wwwroot 目录下的文件')">📁 浏览文件</div>
                    <div class="hint-item" onclick="insertCommand('读取文件 /www/wwwroot/index.html 的内容')">📄 读取文件</div>
                    <div class="hint-item" onclick="insertCommand('帮我重启 nginx 服务')">🔄 重启服务</div>
                    <div class="hint-item" onclick="insertCommand('执行命令：free -m')">💻 Shell 命令</div>
                </div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>
                    <input type="checkbox" id="saveSettings" checked>
                    <span>保存配置到本地</span>
                </label>
            </div>
            <button id="saveSettingsBtn" class="btn-save" onclick="saveAllSettings()">💾 保存设置</button>
            <button id="closeSettings" class="btn-close" onclick="toggleSettings()">关闭</button>
        </div>
    </div>

    <div id="errorToast" class="error-toast hidden"></div>

    <script>
        let currentMode = 'ai';
        let currentServer = null;
        let servers = [];
        let messages = [];

        const chatContainer = document.getElementById('chatContainer');
        const userInput = document.getElementById('userInput');
        const errorToast = document.getElementById('errorToast');

        function loadSettings() {
            const savedApiKey = localStorage.getItem('deepseek_api_key');
            if (savedApiKey) document.getElementById('apiKeyInput').value = savedApiKey;

            const savedServers = localStorage.getItem('baota_servers');
            if (savedServers) {
                servers = JSON.parse(savedServers);
                renderServerList();
            }

            const savedModel = localStorage.getItem('deepseek_model');
            if (savedModel) document.getElementById('modelSelect').value = savedModel;
        }

        function saveAllSettings() {
            const apiKey = document.getElementById('apiKeyInput').value.trim();
            if (apiKey) localStorage.setItem('deepseek_api_key', apiKey);

            const model = document.getElementById('modelSelect').value;
            localStorage.setItem('deepseek_model', model);

            localStorage.setItem('baota_servers', JSON.stringify(servers));

            showToast('设置已保存', 'success');
        }

        function saveServer() {
            const name = document.getElementById('serverName').value.trim();
            const url = document.getElementById('panelUrl').value.trim();
            const apiKey = document.getElementById('btApiKey').value.trim();

            if (!name || !url || !apiKey) {
                showToast('请填写服务器名称、面板地址和API Key');
                return;
            }

            servers.push({ name, url, apiKey });
            localStorage.setItem('baota_servers', JSON.stringify(servers));
            renderServerList();

            document.getElementById('serverName').value = '';
            document.getElementById('panelUrl').value = '';
            document.getElementById('btApiKey').value = '';

            showToast('服务器添加成功', 'success');
        }

        async function testBtConnection() {
            const url = document.getElementById('panelUrl').value.trim();
            const apiKey = document.getElementById('btApiKey').value.trim();

            if (!url || !apiKey) {
                showToast('请填写面板地址和API Key');
                return;
            }

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'test_connection',
                        panel_url: url,
                        api_key: apiKey
                    })
                });

                const result = await response.json();
                showToast(result.message || (result.success ? '连接成功' : '连接失败'), result.success ? 'success' : 'error');
            } catch (error) {
                showToast('连接测试失败: ' + error.message);
            }
        }

        function renderServerList() {
            const serverList = document.getElementById('serverList');
            let html = '';

            servers.forEach((server, index) => {
                const isActive = currentServer === index;
                html += `
                    <div class="server-item ${isActive ? 'active' : ''}" onclick="selectServer(${index})">
                        <span class="status-dot ${isActive ? 'connected' : ''}"></span>
                        <span>${server.name}</span>
                    </div>
                `;
            });

            html += `
                <div class="server-item" onclick="addNewServer()">
                    <span class="status-dot"></span>
                    <span>+ 添加新服务器</span>
                </div>
            `;

            serverList.innerHTML = html;

            if (currentServer !== null) {
                document.getElementById('serverStatus').style.display = 'grid';
            }
        }

        function selectServer(index) {
            currentServer = index;
            renderServerList();
            refreshServerStatus();
        }

        function addNewServer() {
            toggleSettings();
            switchTab('baota');
        }

        async function refreshServerStatus() {
            if (currentServer === null || !servers[currentServer]) {
                showToast('请先选择或添加服务器');
                return;
            }

            const server = servers[currentServer];

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'execute_ai_command',
                        panel_url: server.url,
                        api_key: server.apiKey,
                        command: '查看系统状态'
                    })
                });

                const result = await response.json();

                if (result.success && result.data) {
                    const sys = result.data;
                    document.getElementById('cpuValue').textContent = sys.cpuRealUsed + '%';
                    document.getElementById('memValue').textContent = Math.round((sys.memRealUsed / sys.memTotal) * 100) + '%';
                    document.getElementById('uptimeValue').textContent = sys.time || '--';
                }
            } catch (error) {
                console.error('Failed to get status:', error);
            }
        }

        function switchMode(mode) {
            currentMode = mode;

            document.getElementById('aiModeBtn').classList.toggle('active', mode === 'ai');
            document.getElementById('directModeBtn').classList.toggle('active', mode === 'direct');
            document.getElementById('modeIndicator').textContent = mode === 'ai' ? '🤖 AI 模式' : '⚡ 命令模式';

            chatContainer.classList.toggle('ai-mode', mode === 'ai');

            if (mode === 'ai') {
                userInput.placeholder = '输入 AI 指令，例如：帮我创建一个网站，域名为 example.com';
            } else {
                userInput.placeholder = '输入宝塔命令，例如：查看系统状态';
            }
        }

        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

            event.target.classList.add('active');
            document.getElementById(tab + 'Tab').classList.add('active');
        }

        function toggleSettings() {
            document.getElementById('settingsPanel').classList.toggle('hidden');
        }

        function insertCommand(cmd) {
            userInput.value = cmd;
            userInput.focus();
        }

        function showToast(message, type = 'error') {
            errorToast.textContent = message;
            errorToast.className = 'error-toast ' + type;
            errorToast.classList.remove('hidden');
            setTimeout(() => errorToast.classList.add('hidden'), 3000);
        }

        function formatMarkdown(text) {
            text = escapeHtml(text);
            text = text.replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
            text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            text = text.replace(/\|(.+)\|/g, function(match) {
                const cells = match.split('|').filter(c => c.trim());
                return '<div class="table-row">' + cells.map(c => '<span>' + c.trim() + '</span>').join('') + '</div>';
            });
            text = text.replace(/\n/g, '<br>');
            return text;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function removeWelcomeMessage() {
            const welcome = chatContainer.querySelector('.welcome-message');
            if (welcome) welcome.remove();
        }

        function addMessage(role, content, isSystem = false) {
            removeWelcomeMessage();

            const msgDiv = document.createElement('div');
            msgDiv.className = `message ${role === 'user' ? 'user-message' : 'ai-message'}`;

            if (isSystem) {
                msgDiv.className = 'system-message';
                msgDiv.innerHTML = content;
            } else {
                msgDiv.innerHTML = `<div class="message-content">${formatMarkdown(content)}</div>`;
            }

            chatContainer.appendChild(msgDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;

            return msgDiv;
        }

        function addLoadingMessage() {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'message ai-message loading-message';
            loadingDiv.id = 'loadingIndicator';
            loadingDiv.innerHTML = '<div class="loading-dots"><span></span><span></span><span></span></div>';
            chatContainer.appendChild(loadingDiv);
            chatContainer.scrollTop = chatContainer.scrollHeight;
            return loadingDiv;
        }

        async function sendMessage() {
            const userMessage = userInput.value.trim();
            if (!userMessage) return;

            const apiKey = localStorage.getItem('deepseek_api_key');
            if (!apiKey) {
                showToast('请先配置 DeepSeek API Key');
                toggleSettings();
                return;
            }

            if (currentServer === null && currentMode === 'ai') {
                showToast('请先配置并选择宝塔服务器');
                toggleSettings();
                return;
            }

            addMessage('user', userMessage);
            userInput.value = '';
            document.getElementById('charCount').textContent = '0 字符';

            const loadingEl = addLoadingMessage();
            const startTime = Date.now();

            try {
                let result;

                if (currentMode === 'ai') {
                    const server = servers[currentServer];

                    const aiResponse = await fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'execute_ai_command',
                            panel_url: server.url,
                            api_key: server.apiKey,
                            command: userMessage
                        })
                    });

                    const aiResult = await aiResponse.json();

                    loadingEl.remove();

                    if (aiResult.success) {
                        addMessage('ai', aiResult.message);
                    } else {
                        addMessage('ai', '❌ 操作失败: ' + aiResult.message);
                    }
                } else {
                    const server = servers[currentServer];

                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'execute_ai_command',
                            panel_url: server.url,
                            api_key: server.apiKey,
                            command: userMessage
                        })
                    });

                    const result = await response.json();
                    loadingEl.remove();

                    if (result.success) {
                        addMessage('ai', result.message);
                    } else {
                        addMessage('ai', '❌ 错误: ' + result.message);
                    }
                }

                const endTime = Date.now();
                document.getElementById('responseTime').textContent = `⏱️ ${((endTime - startTime) / 1000).toFixed(1)}s`;

            } catch (error) {
                loadingEl.remove();
                showToast('请求失败: ' + error.message);
            }
        }

        function clearChat() {
            messages = [];
            chatContainer.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">🚀</div>
                    <h2>DeepSeek + 宝塔 AI 控制台</h2>
                    <p>通过 AI 智能助手管理您的宝塔面板服务器</p>
                </div>
            `;
            document.getElementById('responseTime').textContent = '';
        }

        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            document.getElementById('charCount').textContent = this.value.length + ' 字符';
        });

        userInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        document.getElementById('settingsPanel').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        loadSettings();
    </script>
</body>
</html>
