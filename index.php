<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeepSeek + 宝塔 AI 控制台</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height:100vh;
            display:flex;
            flex-direction:column;
        }
        .header {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.3);
            padding:12px 16px;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .header h1 {
            color:white;
            font-size:1.2rem;
        }
        .btn {
            padding:10px 16px;
            border:none;
            border-radius:8px;
            cursor:pointer;
            font-weight:600;
            transition:all 0.3s;
        }
        .btn-primary {
            background: white;
            color:#667eea;
        }
        .btn-success {
            background: #48bb78;
            color:white;
        }
        .container {
            flex:1;
            display:flex;
            flex-direction:column;
            max-width:1000px;
            margin:0 auto;
            width:100%;
            padding:16px;
        }
        .status {
            background: rgba(255,255,255,0.2);
            border:1px solid rgba(255,255,255,0.3);
            border-radius:12px;
            padding:16px;
            margin-bottom:12px;
            color:white;
            display:grid;
            grid-template-columns: repeat(3, 1fr);
            gap:12px;
        }
        .status-item { text-align:center; }
        .status-value { font-size:1.5rem; font-weight:700; }
        .status-label { font-size:0.85rem; opacity:0.9; }
        .chat {
            flex:1;
            background: rgba(255,255,255,0.95);
            border-radius:16px;
            padding:16px;
            overflow-y:auto;
            margin-bottom:12px;
        }
        .message {
            padding:12px 16px;
            border-radius:12px;
            margin-bottom:10px;
            max-width:85%;
            animation:slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { opacity:0; transform: translateY(10px); }
            to { opacity:1; transform: translateY(0); }
        }
        .user {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color:white;
            margin-left:auto;
        }
        .ai {
            background: #f0f4ff;
            color:#333;
        }
        .system {
            background: #fff8e1;
            color:#856404;
            font-size:0.9rem;
        }
        .input-area {
            display:flex;
            gap:10px;
            background: rgba(255,255,255,0.95);
            padding:12px;
            border-radius:12px;
        }
        #userInput {
            flex:1;
            padding:12px 16px;
            border:2px solid #eee;
            border-radius:8px;
            font-size:1rem;
            outline:none;
            resize:none;
            height:48px;
        }
        #userInput:focus {
            border-color:#667eea;
        }
        .send-btn {
            padding:12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color:white;
            border:none;
            border-radius:8px;
            font-weight:600;
            cursor:pointer;
        }
        .quick-commands {
            display:flex;
            gap:8px;
            overflow-x:auto;
            padding:8px 0;
        }
        .quick-cmd {
            padding:8px 14px;
            background: rgba(255,255,255,0.2);
            border:1px solid rgba(255,255,255,0.3);
            border-radius:20px;
            color:white;
            font-size:0.9rem;
            cursor:pointer;
            white-space:nowrap;
            flex-shrink:0;
        }
        .modal {
            position: fixed;
            top:0; left:0; right:0; bottom:0;
            background: rgba(0,0,0,0.5);
            display:none;
            align-items:center;
            justify-content:center;
            z-index:1000;
            padding:16px;
        }
        .modal.active { display:flex; }
        .modal-content {
            background: white;
            border-radius:16px;
            padding:24px;
            width:100%;
            max-width:450px;
            max-height:80vh;
            overflow-y:auto;
        }
        .form-group { margin-bottom:16px; }
        .form-group label { display:block; font-weight:600; margin-bottom:6px; color:#333; }
        .form-group input {
            width:100%;
            padding:12px;
            border:2px solid #eee;
            border-radius:8px;
            font-size:1rem;
        }
        .form-group input:focus {
            border-color:#667eea;
            outline:none;
        }
        .pre { background:#1a1a2e; color:#d4d4d4; padding:12px; border-radius:8px; font-size:0.85rem; overflow-x:auto; }
        .code { font-family:monospace; background:#eee; padding:2px 6px; border-radius:4px; font-size:0.9em; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🤖 DeepSeek + 宝塔 AI</h1>
        <button class="btn btn-primary" onclick="openSettings()">⚙️ 设置</button>
    </div>
    
    <div class="container">
        <div class="status" id="statusBar">
            <div class="status-item">
                <div class="status-value" id="cpuValue">--</div>
                <div class="status-label">CPU</div>
            </div>
            <div class="status-item">
                <div class="status-value" id="memValue">--</div>
                <div class="status-label">内存</div>
            </div>
            <div class="status-item">
                <div class="status-value" id="uptimeValue">--</div>
                <div class="status-label">运行</div>
            </div>
        </div>
        
        <div class="quick-commands">
            <div class="quick-cmd" onclick="quickCmd('查看系统状态')">📊 状态</div>
            <div class="quick-cmd" onclick="quickCmd('查看网站列表')">🌐 网站</div>
            <div class="quick-cmd" onclick="quickCmd('执行命令：ls -la /www/wwwroot')">💻 命令</div>
            <div class="quick-cmd" onclick="quickCmd('查看文件：/www/wwwroot/38.207.177.103/index.html')">📁 文件</div>
            <div class="quick-cmd" onclick="quickCmd('创建网站：test.example.com')">➕ 建站</div>
        </div>
        
        <div class="chat" id="chat">
            <div class="message system">
                👋 欢迎！我是您的宝塔AI助手。告诉我您的需求，我会帮您管理服务器。
                <br><br>
                <div>例如：</div>
                <div>• "查看当前服务器状态"</div>
                <div>• "创建一个新网站，域名是 test.com"</div>
                <div>• "列出所有数据库"</div>
                <div>• "执行 free -m 命令"</div>
            </div>
        </div>
        
        <div class="input-area">
            <input id="userInput" placeholder="告诉我您的需求..." onkeypress="if(event.key==='Enter')sendMessage()">
            <button class="send-btn" onclick="sendMessage()">发送</button>
        </div>
    </div>
    
    <div class="modal" id="settingsModal">
        <div class="modal-content">
            <h2 style="margin-bottom:16px;">⚙️ 系统设置</h2>
            <div class="form-group">
                <label>DeepSeek API Key</label>
                <input type="password" id="deepseekKey" placeholder="sk-...">
            </div>
            <div class="form-group">
                <label>宝塔面板地址</label>
                <input type="text" id="panelUrl" value="https://38.207.177.103:35957" placeholder="https://your-server:port">
            </div>
            <div class="form-group">
                <label>宝塔 API Key</label>
                <input type="password" id="panelKey" value="BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu" placeholder="API密钥">
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button class="btn btn-success" onclick="testAndSave()" style="flex:1;">测试并保存</button>
                <button class="btn btn-primary" onclick="closeSettings()" style="flex:1;">关闭</button>
            </div>
        </div>
    </div>
    
    <script>
        let config = {
            panelUrl: 'https://38.207.177.103:35957',
            panelKey: 'BjdFTLZ2jbQ856A4wjl55lnA7uOInsTu',
            deepseekKey: ''
        };
        
        function loadConfig() {
            const saved = localStorage.getItem('bt_ai_config');
            if (saved) {
                config = JSON.parse(saved);
                document.getElementById('deepseekKey').value = config.deepseekKey;
                document.getElementById('panelUrl').value = config.panelUrl;
                document.getElementById('panelKey').value = config.panelKey;
            }
            refreshStatus();
        }
        
        function openSettings() { document.getElementById('settingsModal').classList.add('active'); }
        function closeSettings() { document.getElementById('settingsModal').classList.remove('active'); }
        
        async function testAndSave() {
            config.deepseekKey = document.getElementById('deepseekKey').value;
            config.panelUrl = document.getElementById('panelUrl').value;
            config.panelKey = document.getElementById('panelKey').value;
            localStorage.setItem('bt_ai_config', JSON.stringify(config));
            
            addMessage('system', '🔗 正在测试宝塔连接...');
            
            const r = await api('test_connection');
            if (r.success) {
                addMessage('system', '✅ 宝塔连接成功！正在获取系统状态...');
                await refreshStatus();
                addMessage('system', '✅ 已准备好为您服务！');
            } else {
                addMessage('system', '❌ 宝塔连接失败，请检查配置');
            }
            closeSettings();
        }
        
        async function refreshStatus() {
            const r = await api('get_status');
            if (r.success && r.data) {
                document.getElementById('cpuValue').textContent = r.data.cpuRealUsed + '%';
                const memPct = Math.round((r.data.memRealUsed / r.data.memTotal) * 100);
                document.getElementById('memValue').textContent = memPct + '%';
                document.getElementById('uptimeValue').textContent = r.data.time;
            }
        }
        
        async function api(action, data = {}) {
            const resp = await fetch('api.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action,
                    panel_url: config.panelUrl,
                    api_key: config.panelKey,
                    deepseek_key: config.deepseekKey,
                    ...data
                })
            });
            return await resp.json();
        }
        
        function quickCmd(cmd) {
            document.getElementById('userInput').value = cmd;
            sendMessage();
        }
        
        function addMessage(type, content) {
            const chat = document.getElementById('chat');
            const div = document.createElement('div');
            div.className = `message ${type}`;
            
            if (typeof content === 'object') {
                content = '<pre class="pre">' + JSON.stringify(content, null, 2) + '</pre>';
            }
            
            div.innerHTML = content;
            chat.appendChild(div);
            chat.scrollTop = chat.scrollHeight;
        }
        
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const text = input.value.trim();
            if (!text) return;
            
            addMessage('user', text);
            input.value = '';
            
            if (!config.deepseekKey) {
                addMessage('ai', '⚠️ 请先在设置中配置 DeepSeek API Key');
                openSettings();
                return;
            }
            
            addMessage('system', '🧠 正在处理您的请求...');
            
            try {
                const r = await api('chat', { message: text });
                if (r.success) {
                    addMessage('ai', r.message);
                    if (r.executed && r.executed.length > 0) {
                        addMessage('system', '🔧 已执行操作：');
                        for (const ex of r.executed) {
                            addMessage('system', `• <span class="code">${ex.cmd}</span>`);
                            if (ex.result) addMessage('system', ex.result);
                        }
                    }
                    refreshStatus();
                } else {
                    addMessage('ai', '❌ ' + r.message);
                }
            } catch (e) {
                addMessage('ai', '❌ 请求出错: ' + e.message);
            }
        }
        
        loadConfig();
    </script>
</body>
</html>
