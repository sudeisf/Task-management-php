<?php

require_once __DIR__ . '/../core/Uploader.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Project.php';

class AttachmentService
{
    private $uploader;
    private $attachmentModel;
    private $taskModel;
    private $projectModel;

    public function __construct()
    {
        $this->uploader = new Uploader();
        $this->attachmentModel = new Attachment();
        $this->taskModel = new Task();
        $this->projectModel = new Project();
    }

    public function handleUpload($file, $taskId, $userId, $userRole)
    {
        if (!$file || empty($file['name'])) return ['error' => 'No file uploaded'];

        $task = $this->taskModel->find($taskId);
        if (!$task) return ['error' => 'Task not found'];
        
        if (!$this->canAccessTask($task, $userId, $userRole)) return ['error' => 'Permission denied'];

        $res = $this->uploader->uploadFile($file);
        if (!$res) return ['error' => implode(" ", $this->uploader->getErrors())];

        $data = [
            'task_id' => $taskId,
            'uploaded_by' => $userId,
            'file_path' => $res['file_path'],
            'file_name' => $res['original_name']
        ];

        if ($this->attachmentModel->create($data)) return ['success' => true, 'data' => $data, 'original_name' => $res['original_name']];
        
        $this->uploader->deleteFile($res['file_path']);
        return ['error' => 'Database save failed'];
    }

    public function handleProjectUpload($file, $projectId, $userId, $userRole)
    {
        if (!$file || empty($file['name'])) return ['error' => 'No file uploaded'];

        $project = $this->projectModel->find($projectId);
        if (!$project) return ['error' => 'Project not found'];
        
        if (!$this->projectModel->hasAccess($projectId, $userId, $userRole)) return ['error' => 'Permission denied'];

        $res = $this->uploader->uploadFile($file);
        if (!$res) return ['error' => implode(" ", $this->uploader->getErrors())];

        $data = [
            'project_id' => $projectId,
            'uploaded_by' => $userId,
            'file_path' => $res['file_path'],
            'file_name' => $res['original_name']
        ];

        if ($this->attachmentModel->create($data)) return ['success' => true, 'data' => $data, 'original_name' => $res['original_name']];
        
        $this->uploader->deleteFile($res['file_path']);
        return ['error' => 'Database save failed'];
    }

    public function canAccessTask($task, $userId, $userRole)
    {
        return ($userRole === 'admin' || $userRole === 'manager' || $task['assigned_to'] == $userId || $task['created_by'] == $userId);
    }
}
