<?php

require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Notification.php';

class CommentService
{
    private $commentModel;
    private $taskModel;
    private $notificationModel;

    public function __construct()
    {
        $this->commentModel = new Comment();
        $this->taskModel = new Task();
        $this->notificationModel = new Notification();
    }

    public function handleCreate($taskId, $userId, $text)
    {
        $task = $this->taskModel->find($taskId);
        if (!$task) return ['error' => 'Task not found'];

        $data = ['task_id' => $taskId, 'user_id' => $userId, 'comment' => htmlspecialchars($text, ENT_QUOTES, 'UTF-8')];
        if ($id = $this->commentModel->create($data)) {
            $this->notifyParticipants($task, $userId, "New comment on: " . $task['title']);
            return ['success' => true, 'id' => $id];
        }
        return ['error' => 'Failed to create'];
    }

    private function notifyParticipants($task, $userId, $msg)
    {
        if ($task['assigned_to'] && $task['assigned_to'] != $userId) {
            $this->notificationModel->create(['user_id' => $task['assigned_to'], 'task_id' => $task['id'], 'message' => $msg, 'is_read' => 0]);
        }
        if ($task['created_by'] != $userId) {
            $this->notificationModel->create(['user_id' => $task['created_by'], 'task_id' => $task['id'], 'message' => $msg, 'is_read' => 0]);
        }
    }
}
