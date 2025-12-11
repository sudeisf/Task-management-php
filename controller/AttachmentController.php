<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Uploader.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class AttachmentController
{
    private $attachmentModel;
    private $taskModel;
    private $activityModel;
    private $uploader;
    private $currentUser;

    public function __construct()
    {
        $this->attachmentModel = new Attachment();
        $this->taskModel = new Task();
        $this->activityModel = new Activity();
        $this->uploader = new Uploader();
        $this->currentUser = Auth::user();
    }

    // Upload attachment
    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../controller/TaskController.php?action=index");
            exit;
        }

        $task_id = (int)($_POST['task_id'] ?? 0);

        if (!$task_id) {
            $_SESSION['error'] = "Task ID is required.";
            header("Location: ../controller/TaskController.php?action=index");
            exit;
        }

        // Check if task exists and user has access
        $task = $this->taskModel->find($task_id);
        if (!$task) {
            $_SESSION['error'] = "Task not found.";
            header("Location: ../controller/TaskController.php?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canUploadToTask($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to upload files to this task.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        // Check if file was uploaded
        if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE) {
            $_SESSION['error'] = "No file was selected for upload.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        // Upload file
        $uploadResult = $this->uploader->uploadFile($_FILES['attachment']);

        if (!$uploadResult) {
            $errors = $this->uploader->getErrors();
            $_SESSION['error'] = implode(" ", $errors);
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        // Save attachment to database
        $attachmentData = [
            'task_id' => $task_id,
            'uploaded_by' => $this->currentUser['id'],
            'file_path' => $uploadResult['file_path'],
            'file_name' => $uploadResult['original_name']
        ];

        if ($this->attachmentModel->create($attachmentData)) {
            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                'file_uploaded',
                $task_id,
                "Uploaded file: {$uploadResult['original_name']}"
            );

            $_SESSION['success'] = "File uploaded successfully!";
        } else {
            // Delete uploaded file if database save failed
            $this->uploader->deleteFile($uploadResult['file_path']);
            $_SESSION['error'] = "Failed to save attachment information.";
        }

        header("Location: ../controller/TaskController.php?action=show&id=$task_id");
        exit;
    }

    // Download attachment
    public function download()
    {
        $attachment_id = (int)($_GET['id'] ?? 0);

        if (!$attachment_id) {
            $_SESSION['error'] = "Attachment ID is required.";
            header("Location: ../controller/TaskController.php?action=index");
            exit;
        }

        $attachment = $this->attachmentModel->find($attachment_id);

        if (!$attachment) {
            $_SESSION['error'] = "Attachment not found.";
            header("Location: ../controller/TaskController.php?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canDownloadAttachment($attachment, $userRole)) {
            $_SESSION['error'] = "You don't have permission to download this file.";
            header("Location: ../controller/TaskController.php?action=show&id={$attachment['task_id']}");
            exit;
        }

        $filePath = UPLOAD_PATH . '/' . $attachment['file_path'];

        if (!file_exists($filePath)) {
            $_SESSION['error'] = "File not found on server.";
            header("Location: ../controller/TaskController.php?action=show&id={$attachment['task_id']}");
            exit;
        }

        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $attachment['file_name'] . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        // Clear output buffer
        ob_clean();
        flush();

        // Output file
        readfile($filePath);
        exit;
    }

    // Delete attachment
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ../controller/TaskController.php?action=index");
            exit;
        }

        $attachment_id = (int)($_POST['attachment_id'] ?? 0);
        $task_id = (int)($_POST['task_id'] ?? 0);

        if (!$attachment_id || !$task_id) {
            $_SESSION['error'] = "Attachment ID and task ID are required.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        $attachment = $this->attachmentModel->find($attachment_id);

        if (!$attachment) {
            $_SESSION['error'] = "Attachment not found.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->attachmentModel->canDelete($attachment, $this->currentUser['id'], $userRole)) {
            $_SESSION['error'] = "You don't have permission to delete this attachment.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        if ($this->attachmentModel->delete($attachment_id, $this->currentUser['id'], $userRole)) {
            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                'file_deleted',
                $task_id,
                "Deleted file: {$attachment['file_name']}"
            );

            $_SESSION['success'] = "Attachment deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete attachment.";
        }

        header("Location: ../controller/TaskController.php?action=show&id=$task_id");
        exit;
    }

    // Get attachments for a task (AJAX endpoint)
    public function getAttachments()
    {
        header('Content-Type: application/json');

        $task_id = (int)($_GET['task_id'] ?? 0);

        if (!$task_id) {
            echo json_encode(['error' => 'Task ID is required']);
            exit;
        }

        // Check if task exists and user has access
        $task = $this->taskModel->find($task_id);
        if (!$task) {
            echo json_encode(['error' => 'Task not found']);
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canUploadToTask($task, $userRole)) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        $attachments = $this->attachmentModel->getByTask($task_id);
        $attachmentsArray = [];

        foreach ($attachments as $attachment) {
            $fileInfo = $this->uploader->getFileInfo($attachment['file_path']);

            $attachmentsArray[] = [
                'id' => $attachment['id'],
                'file_name' => $attachment['file_name'],
                'file_path' => $attachment['file_path'],
                'created_at' => $attachment['created_at'],
                'file_size' => $fileInfo ? $fileInfo['size'] : 0,
                'file_type' => $fileInfo ? $fileInfo['type'] : 'unknown',
                'extension' => $fileInfo ? $fileInfo['extension'] : 'unknown',
                'icon_class' => $this->attachmentModel->getFileIconClass($attachment['file_path']),
                'uploader' => [
                    'id' => $attachment['uploaded_by'],
                    'name' => $attachment['uploader_name'] ?? 'Unknown User'
                ],
                'can_delete' => ($userRole === 'admin' || $userRole === 'manager' ||
                               $attachment['uploaded_by'] == $this->currentUser['id'])
            ];
        }

        echo json_encode(['attachments' => $attachmentsArray]);
        exit;
    }

    // Private helper methods

    private function getUserRole($userId)
    {
        // This is a simplified version - in real app you'd cache this
        $sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$userId]);
        $result = $db->getRow();
        return $result['name'] ?? 'member';
    }

    private function canUploadToTask($task, $userRole)
    {
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }

        return $task['assigned_to'] == $this->currentUser['id'] ||
               $task['created_by'] == $this->currentUser['id'];
    }

    private function canDownloadAttachment($attachment, $userRole)
    {
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }

        // Users can download attachments from tasks they're assigned to or created
        $task = $this->taskModel->find($attachment['task_id']);
        return $task && ($task['assigned_to'] == $this->currentUser['id'] ||
                        $task['created_by'] == $this->currentUser['id']);
    }
}

// Handle routing
$action = $_GET['action'] ?? 'index';

$controller = new AttachmentController();

switch ($action) {
    case 'upload':
        $controller->upload();
        break;

    case 'download':
        $controller->download();
        break;

    case 'delete':
        $controller->delete();
        break;

    case 'get_attachments':
        $controller->getAttachments();
        break;

    default:
        header("Location: ../controller/TaskController.php?action=index");
        break;
}
