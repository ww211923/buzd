<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>DeepSeek AI + 宝塔控制台</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- 头部 -->
        <header class="header">
            <h1>🤖 AI 控制台</h1>
            <div class="header-actions">
                <button class="header-btn" onclick="clearChat()" title="清空对话">🗑️</button>
                <button class="header-btn" onclick="openSettings()" title="设置">⚙️</button>
            </div>
        </header>

        <!-- 服务器选择 -->
        <div class="server-selector">
            <div class="server-tabs" id="serverTabs">
                <button class="add-server-btn" onclick="openSettings()">+ 添加服务器</button>
            </div>
        </div>

        <!-- 状态栏 -->
        <div class="status-bar" id="statusBar">
            <div class="status-chip">
                <div class="value" id="cpuValue">--</div>
                <div class="label">CPU</div>
            </div>
            <div class="status-chip">
                <div class="value" id="memValue">--</div>
                <div class="label">内存</div>
            </div>
            <div class="status-chip">
                <div class="value" id="uptimeValue">--</div>
                <div class="label">运行</div>
            </div>
        </div>

        <!-- 聊天区域 -->
        <div class="chat-container" id="chatContainer">
            <div class="welcome">
                <div class="welcome-icon">🚀</div>
                <h2>DeepSeek AI + 宝塔</h2>
                <p>说出您的需求，AI 帮您完成服务器管理</p>
                <p style="font-size: 0.8rem; margin-top: 10px; opacity: 0.7;">例如："帮我创建一个网站，域名为 test.com"</p>
            </div>
        </div>

        <!-- 快捷命令 -->
        <div class="quick-commands">
            <button class="quick-cmd" onclick="quickCommand('查看系统状态')">📊 状态</button>
            <button class="quick-cmd" onclick="quickCommand('帮我创建一个网站，域名为 example.com')">🌐 建站</button>
            <button class="quick-cmd" onclick="quickCommand('查看网站列表')">📋 网站</button>
            <button class="quick-cmd" onclick="quickCommand('帮我执行命令：free -m')">💻 命令</button>
        </div>

        <!-- 输入区域 -->
        <div class="input-area">
            <div class="input-wrapper">
                <textarea id="userInput" placeholder="说出您的需求..." rows="1" oninput="autoResize(this)"></textarea>
                <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="20" height="20">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                    </svg>
                </button>
            </div>
            <div class="input-info">
                <span id="charCount">0 字符</span>
                <span id="responseTime"></span>
            </div>
        </div>
    </div>

    <!-- 设置面板 -->
    <div class="settings-overlay" id="settingsOverlay" onclick="closeSettings(event)">
        <div class="settings-panel" onclick="event.stopPropagation()">
            <div class="settings-header">
                <h3>⚙️ 配置</h3>
                <button class="settings-close" onclick="closeSettings()">✕</button>
            </div>

            <div class="form-group">
                <label>DeepSeek API Key</label>
                <input type="password" id="apiKeyInput" placeholder="sk-xxxxxxxxxxxxxxxx">
                <small style="color: #666; font-size: 0.8rem; margin-top: 5px; display: block;">从 platform.deepseek.com 获取</small>
            </div>

            <div class="form-group">
                <label>宝塔面板地址</label>
                <input type="text" id="panelUrl" placeholder="https://your-server:8888" value="https://38.207.177.103:35957">
            </div>

            <div class="form-group">
                <label>宝塔 API Key</label>
                <input type="password" id="btApiKey" placeholder="宝塔 API Key" value="BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu">
            </div>

            <button class="settings-btn primary" onclick="testAndSave()">保存并连接</button>
            <button class="settings-btn secondary" onclick="closeSettings()">取消</button>
        </div>
    </div>

    <!-- Toast 提示 -->
    <div class="toast" id="toast"></div>

    <script>
        let currentServer = null;
        let servers = [];
        let chatHistory = [];

        // 加载设置
        function loadSettings() {
            const savedApiKey = localStorage.getItem('deepseek_api_key');
            if (savedApiKey) document.getElementById('apiKeyInput').value = savedApiKey;

            const savedServers = localStorage.getItem('baota_servers');
            if (savedServers) {
                servers = JSON.parse(savedServers);
                renderServers();
                if (servers.length > 0) {
                    selectServer(0);
                }
            }

            const savedHistory = localStorage.getItem('chat_history');
            if (savedHistory) {
                chatHistory = JSON.parse(savedHistory);
                renderHistory();
            }
        }

        // 渲染历史记录
        function renderHistory() {
            if (chatHistory.length === 0) return;

            const welcome = document.querySelector('.welcome');
            if (welcome) welcome.remove();

            chatHistory.forEach(msg => {
                addMessage(msg.role, msg.content, false);
            });
        }

        // 渲染服务器列表
        function renderServers() {
            const tabs = document.getElementById('serverTabs');
            let html = '';

            servers.forEach((server, index) => {
                const isActive = currentServer === index;
                html += `
                    <button class="server-tab ${isActive ? 'active' : ''}" onclick="selectServer(${index})">
                        <span class="dot"></span>
                        ${server.name}
                    </button>
                `;
            });

            html += '<button class="add-server-btn" onclick="openSettings()">+ 添加</button>';
            tabs.innerHTML = html;
        }

        // 选择服务器
        function selectServer(index) {
            currentServer = index;
            renderServers();
            refreshStatus();
        }

        // 刷新状态
        async function refreshStatus() {
            if (currentServer === null || !servers[currentServer]) return;

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
                    document.getElementById('cpuValue').textContent = result.data.cpuRealUsed + '%';
                    const memPercent = Math.round((result.data.memRealUsed / result.data.memTotal) * 100);
                    document.getElementById('memValue').textContent = memPercent + '%';
                    document.getElementById('uptimeValue').textContent = result.data.time || '--';
                }
            } catch (error) {
                console.error('获取状态失败:', error);
            }
        }

        // 快捷命令
        function quickCommand(cmd) {
            document.getElementById('userInput').value = cmd;
            sendMessage();
        }

        // 自动调整输入框高度
        function autoResize(el) {
            el.style.height = 'auto';
            el.style.height = Math.min(el.scrollHeight, 120) + 'px';
            document.getElementById('charCount').textContent = el.value.length + ' 字符';
        }

        // 发送消息
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const message = input.value.trim();
            if (!message) return;

            const deepseekApiKey = localStorage.getItem('deepseek_api_key');
            if (!deepseekApiKey) {
                showToast('请先配置 DeepSeek API Key', 'error');
                openSettings();
                return;
            }

            if (currentServer === null) {
                showToast('请先添加宝塔服务器', 'error');
                openSettings();
                return;
            }

            // 添加用户消息
            addMessage('user', message);
            chatHistory.push({ role: 'user', content: message });
            input.value = '';
            autoResize(input);

            // 添加加载动画
            const loadingEl = document.createElement('div');
            loadingEl.className = 'loading-dots';
            loadingEl.id = 'loading';
            loadingEl.innerHTML = '<span></span><span></span><span></span>';
            document.getElementById('chatContainer').appendChild(loadingEl);
            scrollToBottom();

            const server = servers[currentServer];
            const startTime = Date.now();

            try {
                // 调用 DeepSeek AI
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'chat',
                        deepseek_api_key: deepseekApiKey,
                        panel_url: server.url,
                        api_key: server.apiKey,
                        message: message,
                        history: chatHistory.slice(-10)
                    })
                });

                const result = await response.json();
                loadingEl.remove();

                if (result.success) {
                    // 添加 AI 响应
                    addMessage('ai', result.message);
                    chatHistory.push({ role: 'assistant', content: result.message });

                    // 如果有执行结果，显示
                    if (result.execution_results && result.execution_results.length > 0) {
                        result.execution_results.forEach(execResult => {
                            if (execResult.success) {
                                setTimeout(() => {
                                    addMessage('ai', '🔧 ' + execResult.message);
                                }, 500);
                            }
                        });
                    }

                    // 保存历史
                    localStorage.setItem('chat_history', JSON.stringify(chatHistory.slice(-50)));
                } else {
                    addMessage('ai', '❌ ' + result.message);
                }

                const endTime = Date.now();
                document.getElementById('responseTime').textContent = ((endTime - startTime) / 1000).toFixed(1) + 's';

            } catch (error) {
                loadingEl.remove();
                showToast('请求失败: ' + error.message, 'error');
            }
        }

        // 添加消息
        function addMessage(role, content, saveHistory = true) {
            const welcome = document.querySelector('.welcome');
            if (welcome) welcome.remove();

            const msg = document.createElement('div');
            msg.className = `message ${role === 'user' ? 'user-message' : 'ai-message'}`;

            if (role === 'user') {
                msg.innerHTML = `<div class="message-content">${escapeHtml(content)}</div>`;
            } else {
                msg.innerHTML = `<div class="message-content">${formatContent(content)}</div>`;
            }

            document.getElementById('chatContainer').appendChild(msg);
            scrollToBottom();

            if (saveHistory && (role === 'user' || role === 'assistant')) {
                chatHistory.push({ role, content });
                if (chatHistory.length > 50) {
                    chatHistory = chatHistory.slice(-50);
                }
                localStorage.setItem('chat_history', JSON.stringify(chatHistory));
            }
        }

        // 格式化内容
        function formatContent(text) {
            text = escapeHtml(text);
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
            text = text.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
            text = text.replace(/\n/g, '<br>');
            return text;
        }

        // HTML 转义
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 滚动到底部
        function scrollToBottom() {
            const container = document.getElementById('chatContainer');
            container.scrollTop = container.scrollHeight;
        }

        // 清空对话
        function clearChat() {
            chatHistory = [];
            localStorage.removeItem('chat_history');
            const container = document.getElementById('chatContainer');
            container.innerHTML = `
                <div class="welcome">
                    <div class="welcome-icon">🚀</div>
                    <h2>DeepSeek AI + 宝塔</h2>
                    <p>说出您的需求，AI 帮您完成服务器管理</p>
                </div>
            `;
            document.getElementById('responseTime').textContent = '';
        }

        // 设置面板
        function openSettings() {
            document.getElementById('settingsOverlay').classList.add('active');
        }

        function closeSettings(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('settingsOverlay').classList.remove('active');
        }

        // 测试并保存
        async function testAndSave() {
            const deepseekApiKey = document.getElementById('apiKeyInput').value.trim();
            const panelUrl = document.getElementById('panelUrl').value.trim();
            const btApiKey = document.getElementById('btApiKey').value.trim();

            if (!deepseekApiKey) {
                showToast('请输入 DeepSeek API Key', 'error');
                return;
            }

            if (panelUrl && btApiKey) {
                try {
                    showToast('正在测试连接...', 'info');

                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'test_connection',
                            panel_url: panelUrl,
                            api_key: btApiKey
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // 保存 DeepSeek Key
                        localStorage.setItem('deepseek_api_key', deepseekApiKey);

                        // 保存服务器
                        const serverName = panelUrl.split('//')[1]?.split(':')[0] || '服务器';
                        const server = { name: serverName, url: panelUrl, apiKey: btApiKey };

                        const existingIndex = servers.findIndex(s => s.url === panelUrl);
                        if (existingIndex >= 0) {
                            servers[existingIndex] = server;
                        } else {
                            servers.push(server);
                        }

                        localStorage.setItem('baota_servers', JSON.stringify(servers));
                        renderServers();
                        selectServer(existingIndex >= 0 ? existingIndex : servers.length - 1);

                        showToast('配置成功！', 'success');
                        closeSettings();

                        // 自动获取状态
                        refreshStatus();
                    } else {
                        showToast('宝塔连接失败: ' + result.message, 'error');
                    }
                } catch (error) {
                    showToast('连接失败: ' + error.message, 'error');
                }
            } else {
                showToast('请填写完整信息', 'error');
            }
        }

        // Toast 提示
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = `toast show ${type}`;
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        // 回车发送
        document.getElementById('userInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // 初始化
        loadSettings();
    </script>
</body>
</html>
