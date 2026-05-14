# DeepSeek 在线对话系统 - 项目规范

## 1. 项目概述

**项目名称**: DeepSeek Chat
**项目类型**: PHP 在线对话系统
**核心功能**: 使用 DeepSeek V4 API 实现智能对话
**运行环境**: PHP 7.3 + Nginx

## 2. API 规范

**API 基础地址**: `https://api.deepseek.com`
**API 端点**: `/chat/completions`
**认证方式**: Bearer Token

**可用模型**:
- `deepseek-v4-flash` - 快速响应模型
- `deepseek-v4-pro` - 专业深度思考模型

**请求格式**:
```json
{
  "model": "deepseek-v4-pro",
  "messages": [
    {"role": "system", "content": "系统提示词"},
    {"role": "user", "content": "用户消息"}
  ],
  "thinking": {"type": "enabled"},
  "reasoning_effort": "high",
  "stream": true
}
```

## 3. 功能规范

### 3.1 核心功能
- [x] 用户消息发送
- [x] AI 响应流式显示
- [x] 对话历史记录（会话级）
- [x] 模型切换功能
- [x] 思考模式显示（可折叠）
- [x] 响应时间显示
- [x] 错误处理和提示

### 3.2 用户界面
- [x] 简洁现代的聊天界面
- [x] 消息气泡展示
- [x] 加载动画
- [x] 清空对话按钮
- [x] API Key 配置界面

### 3.3 交互功能
- [x] 实时流式响应
- [x] 思考过程展示
- [x] 打字机效果
- [x] 自动滚动到底部
- [x] 回车发送消息

## 4. 技术架构

### 4.1 目录结构
```
/workspace/
├── index.php              # 主页面
├── api.php                # API 请求处理
├── assets/
│   └── style.css          # 样式文件
├── config/
│   └── config.php.example # 配置示例
├── nginx.conf             # Nginx 配置
└── SPEC.md                # 规范文档
```

### 4.2 前端技术
- 原生 HTML5 + CSS3 + JavaScript
- Fetch API 进行异步请求
- Server-Sent Events (SSE) 实现流式响应

### 4.3 后端技术
- PHP 7.3
- cURL 扩展（流式请求）
- JSON 数据处理

## 5. 界面设计

### 5.1 布局
- 顶部导航栏（标题、模型选择、清空按钮）
- 主聊天区域（消息列表）
- 底部输入区域（输入框、发送按钮）
- 设置面板（API Key 配置）

### 5.2 颜色方案
- 主色调: #0066CC (DeepSeek 蓝)
- 用户消息: #DCF8C6
- AI 消息: #FFFFFF
- 背景色: #F0F2F5
- 文字色: #333333

## 6. 安全考虑

- API Key 仅在客户端临时存储
- 支持 HTTPS 加密传输
- 输入内容转义防止 XSS

## 7. 错误处理

- API Key 未配置提示
- 网络错误提示
- API 返回错误展示
- 超时处理（120秒）
