<?php
/**
 * 任务队列管理器
 * 处理长时间运行的任务
 */

class TaskManager {
    private $taskDir;

    public function __construct() {
        $this->taskDir = __DIR__ . '/../tasks';
        if (!is_dir($this->taskDir)) {
            mkdir($this->taskDir, 0777, true);
        }
    }

    /**
     * 创建新任务
     */
    public function createTask($type, $data = []) {
        $taskId = 'task_' . uniqid() . '_' . time();
        $taskData = [
            'id' => $taskId,
            'type' => $type,
            'status' => 'pending',
            'progress' => 0,
            'data' => $data,
            'created_at' => time(),
            'updated_at' => time(),
            'logs' => [],
            'result' => null,
            'error' => null
        ];

        $this->saveTask($taskId, $taskData);
        return $taskId;
    }

    /**
     * 保存任务状态
     */
    public function saveTask($taskId, $taskData) {
        $taskData['updated_at'] = time();
        $file = $this->taskDir . '/' . $taskId . '.json';
        file_put_contents($file, json_encode($taskData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }

    /**
     * 获取任务
     */
    public function getTask($taskId) {
        $file = $this->taskDir . '/' . $taskId . '.json';
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }
        return null;
    }

    /**
     * 更新任务进度
     */
    public function updateProgress($taskId, $progress, $message = '') {
        $task = $this->getTask($taskId);
        if ($task) {
            $task['progress'] = $progress;
            if ($message) {
                $task['logs'][] = [
                    'time' => date('Y-m-d H:i:s'),
                    'message' => $message
                ];
            }
            $this->saveTask($taskId, $task);
        }
    }

    /**
     * 标记任务完成
     */
    public function markComplete($taskId, $result) {
        $task = $this->getTask($taskId);
        if ($task) {
            $task['status'] = 'completed';
            $task['progress'] = 100;
            $task['result'] = $result;
            $this->saveTask($taskId, $task);
        }
    }

    /**
     * 标记任务失败
     */
    public function markFailed($taskId, $error) {
        $task = $this->getTask($taskId);
        if ($task) {
            $task['status'] = 'failed';
            $task['error'] = $error;
            $this->saveTask($taskId, $task);
        }
    }

    /**
     * 添加日志
     */
    public function addLog($taskId, $message) {
        $task = $this->getTask($taskId);
        if ($task) {
            $task['logs'][] = [
                'time' => date('Y-m-d H:i:s'),
                'message' => $message
            ];
            $this->saveTask($taskId, $task);
        }
    }

    /**
     * 执行任务（后台运行）
     */
    public function executeTask($taskId, $callback) {
        $task = $this->getTask($taskId);
        if (!$task) return;

        $task['status'] = 'running';
        $this->saveTask($taskId, $task);

        try {
            $result = $callback($this, $taskId, $task['data']);
            $this->markComplete($taskId, $result);
        } catch (Exception $e) {
            $this->markFailed($taskId, $e->getMessage());
        }
    }

    /**
     * 清理旧任务（保留7天）
     */
    public function cleanOldTasks() {
        $files = glob($this->taskDir . '/*.json');
        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > 7 * 24 * 3600) {
                unlink($file);
            }
        }
    }
}
