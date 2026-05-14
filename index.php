<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DeepSeek 在线对话</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>🤖 DeepSeek 对话助手</h1>
            <div class="header-controls">
                <select id="modelSelect" class="model-select">
                    <option value="deepseek-v4-pro">DeepSeek V4 Pro (深度思考)</option>
                    <option value="deepseek-v4-flash">DeepSeek V4 Flash (快速响应)</option>
                </select>
                <label class="thinking-toggle">
                    <input type="checkbox" id="thinkingToggle" checked>
                    <span>深度思考</span>
                </label>
                <button id="clearBtn" class="btn-clear">清空对话</button>
                <button id="settingsBtn" class="btn-settings">⚙️</button>
            </div>
        </header>

        <div id="chatContainer" class="chat-container">
            <div class="welcome-message">
                <div class="welcome-icon">💬</div>
                <h2>欢迎使用 DeepSeek 对话助手</h2>
                <p>请在设置中配置您的 API Key 开始对话</p>
                <p class="version">支持 DeepSeek V4 模型 · PHP 7.3 + Nginx</p>
            </div>
        </div>

        <div class="input-container">
            <div class="input-wrapper">
                <textarea id="userInput" placeholder="输入您的问题，按 Enter 发送..." rows="1"></textarea>
                <button id="sendBtn" class="send-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor">
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

    <div id="settingsPanel" class="settings-panel hidden">
        <div class="settings-content">
            <h3>⚙️ 设置</h3>
            <div class="form-group">
                <label for="apiKeyInput">DeepSeek API Key</label>
                <input type="password" id="apiKeyInput" placeholder="sk-xxxxxxxxxxxxxxxx">
                <small>从 <a href="https://platform.deepseek.com/api_keys" target="_blank">DeepSeek 平台</a> 获取 API Key</small>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="saveApiKey" checked>
                    <span>在本地保存 API Key</span>
                </label>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="showThinking" checked>
                    <span>显示思考过程</span>
                </label>
            </div>
            <button id="saveSettings" class="btn-save">保存设置</button>
            <button id="closeSettings" class="btn-close">关闭</button>
        </div>
    </div>

    <div id="errorToast" class="error-toast hidden"></div>

    <script>
        let messages = [];
        let isGenerating = false;
        let currentAbortController = null;

        const chatContainer = document.getElementById('chatContainer');
        const userInput = document.getElementById('userInput');
        const sendBtn = document.getElementById('sendBtn');
        const clearBtn = document.getElementById('clearBtn');
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsPanel = document.getElementById('settingsPanel');
        const apiKeyInput = document.getElementById('apiKeyInput');
        const saveSettingsBtn = document.getElementById('saveSettings');
        const closeSettingsBtn = document.getElementById('closeSettings');
        const modelSelect = document.getElementById('modelSelect');
        const thinkingToggle = document.getElementById('thinkingToggle');
        const charCount = document.getElementById('charCount');
        const responseTime = document.getElementById('responseTime');
        const errorToast = document.getElementById('errorToast');
        const showThinkingCheckbox = document.getElementById('showThinking');

        function loadSettings() {
            const savedApiKey = localStorage.getItem('deepseek_api_key');
            if (savedApiKey) {
                apiKeyInput.value = savedApiKey;
            }
            const savedThinking = localStorage.getItem('deepseek_show_thinking');
            if (savedThinking !== null) {
                showThinkingCheckbox.checked = savedThinking === 'true';
            }
        }

        function saveSettings() {
            const apiKey = apiKeyInput.value.trim();
            if (apiKey) {
                if (document.getElementById('saveApiKey').checked) {
                    localStorage.setItem('deepseek_api_key', apiKey);
                }
            }
            localStorage.setItem('deepseek_show_thinking', showThinkingCheckbox.checked);
            showToast('设置已保存', 'success');
            settingsPanel.classList.add('hidden');
        }

        function showToast(message, type = 'error') {
            errorToast.textContent = message;
            errorToast.className = 'error-toast ' + type;
            errorToast.classList.remove('hidden');
            setTimeout(() => {
                errorToast.classList.add('hidden');
            }, 3000);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatMarkdown(text) {
            text = escapeHtml(text);
            text = text.replace(/```(\w+)?\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
            text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');
            text = text.replace(/\n/g, '<br>');
            return text;
        }

        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        function createMessageElement(role, content, thinking = '') {
            const div = document.createElement('div');
            div.className = `message ${role === 'user' ? 'user-message' : 'ai-message'}`;

            if (role === 'user') {
                div.innerHTML = `<div class="message-content">${formatMarkdown(content)}</div>`;
            } else {
                const showThinking = localStorage.getItem('deepseek_show_thinking') !== 'false';
                let thinkingHtml = '';
                if (thinking && showThinking) {
                    thinkingHtml = `
                        <div class="thinking-section">
                            <button class="thinking-toggle-btn" onclick="toggleThinking(this)">
                                🧠 思考过程 <span class="toggle-icon">▼</span>
                            </button>
                            <div class="thinking-content hidden">${formatMarkdown(thinking)}</div>
                        </div>
                    `;
                }
                div.innerHTML = `
                    ${thinkingHtml}
                    <div class="message-content">${formatMarkdown(content)}</div>
                `;
            }

            return div;
        }

        window.toggleThinking = function(btn) {
            const content = btn.nextElementSibling;
            const icon = btn.querySelector('.toggle-icon');
            content.classList.toggle('hidden');
            icon.textContent = content.classList.contains('hidden') ? '▼' : '▲';
        };

        function createLoadingElement() {
            const div = document.createElement('div');
            div.className = 'message ai-message loading-message';
            div.id = 'loadingIndicator';
            div.innerHTML = `
                <div class="loading-dots">
                    <span></span><span></span><span></span>
                </div>
            `;
            return div;
        }

        function removeWelcomeMessage() {
            const welcome = chatContainer.querySelector('.welcome-message');
            if (welcome) {
                welcome.remove();
            }
        }

        async function sendMessage() {
            const userMessage = userInput.value.trim();
            if (!userMessage || isGenerating) return;

            const apiKey = localStorage.getItem('deepseek_api_key');
            if (!apiKey) {
                showToast('请先在设置中配置 API Key');
                settingsPanel.classList.remove('hidden');
                return;
            }

            removeWelcomeMessage();

            messages.push({ role: 'user', content: userMessage });

            const userMsgEl = createMessageElement('user', userMessage);
            chatContainer.appendChild(userMsgEl);

            userInput.value = '';
            charCount.textContent = '0 字符';
            responseTime.textContent = '';

            const loadingEl = createLoadingElement();
            chatContainer.appendChild(loadingEl);
            scrollToBottom();

            isGenerating = true;
            sendBtn.disabled = true;

            const model = modelSelect.value;
            const enableThinking = thinkingToggle.checked && model === 'deepseek-v4-pro';
            const startTime = Date.now();

            currentAbortController = new AbortController();

            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        api_key: apiKey,
                        messages: messages,
                        model: model,
                        thinking: enableThinking
                    }),
                    signal: currentAbortController.signal
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || `请求失败: ${response.status}`);
                }

                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let fullContent = '';
                let fullThinking = '';
                let isThinking = false;
                let buffer = '';

                loadingEl.remove();

                const aiMsgEl = createMessageElement('ai', '');
                chatContainer.appendChild(aiMsgEl);
                const contentEl = aiMsgEl.querySelector('.message-content');

                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;

                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';

                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            const data = line.slice(6);
                            if (data === '[DONE]') continue;

                            try {
                                const parsed = JSON.parse(data);
                                const delta = parsed.choices?.[0]?.delta;

                                if (delta?.content) {
                                    fullContent += delta.content;
                                    contentEl.innerHTML = formatMarkdown(fullContent);
                                    scrollToBottom();
                                }

                                if (delta?.thinking) {
                                    isThinking = true;
                                    fullThinking += delta.thinking;
                                }
                            } catch (e) {
                                console.log('解析响应数据失败');
                            }
                        }
                    }
                }

                if (buffer.startsWith('data: ')) {
                    const data = buffer.slice(6);
                    if (data !== '[DONE]') {
                        try {
                            const parsed = JSON.parse(data);
                            if (parsed.choices?.[0]?.delta?.content) {
                                fullContent += parsed.choices[0].delta.content;
                            }
                        } catch (e) {}
                    }
                }

                if (isThinking) {
                    const showThinking = localStorage.getItem('deepseek_show_thinking') !== 'false';
                    const thinkingSection = document.createElement('div');
                    thinkingSection.className = 'thinking-section';
                    thinkingSection.innerHTML = `
                        <button class="thinking-toggle-btn" onclick="toggleThinking(this)">
                            🧠 思考过程 <span class="toggle-icon">▼</span>
                        </button>
                        <div class="thinking-content hidden">${formatMarkdown(fullThinking)}</div>
                    `;
                    aiMsgEl.insertBefore(thinkingSection, contentEl);
                }

                messages.push({ role: 'assistant', content: fullContent });

                const endTime = Date.now();
                responseTime.textContent = `⏱️ 响应时间: ${((endTime - startTime) / 1000).toFixed(1)}s`;

            } catch (error) {
                loadingEl.remove();
                if (error.name === 'AbortError') {
                    showToast('请求已取消');
                } else {
                    showToast(error.message);
                    messages.pop();
                }
            } finally {
                isGenerating = false;
                sendBtn.disabled = false;
                currentAbortController = null;
            }
        }

        function clearChat() {
            messages = [];
            chatContainer.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">💬</div>
                    <h2>欢迎使用 DeepSeek 对话助手</h2>
                    <p>请在设置中配置您的 API Key 开始对话</p>
                    <p class="version">支持 DeepSeek V4 模型 · PHP 7.3 + Nginx</p>
                </div>
            `;
            responseTime.textContent = '';
        }

        userInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            charCount.textContent = this.value.length + ' 字符';
        });

        userInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        sendBtn.addEventListener('click', sendMessage);

        clearBtn.addEventListener('click', clearChat);

        settingsBtn.addEventListener('click', () => {
            settingsPanel.classList.toggle('hidden');
        });

        closeSettingsBtn.addEventListener('click', () => {
            settingsPanel.classList.add('hidden');
        });

        saveSettingsBtn.addEventListener('click', saveSettings);

        modelSelect.addEventListener('change', function() {
            if (this.value === 'deepseek-v4-flash') {
                thinkingToggle.disabled = true;
                thinkingToggle.checked = false;
            } else {
                thinkingToggle.disabled = false;
                thinkingToggle.checked = true;
            }
        });

        settingsPanel.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });

        loadSettings();
    </script>
</body>
</html>
