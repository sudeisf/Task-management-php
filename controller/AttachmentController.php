<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/AttachmentService.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../core/Uploader.php';

class AttachmentController extends Controller
{
    private $attachmentService;
    private $attachmentModel;
    private $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->attachmentService = new AttachmentService();
        $this->attachmentModel = new Attachment();
        $this->activityModel = new Activity();
    }

    public function upload()
    {
        $taskId = $this->post('task_id');
        $projectId = $this->post('project_id');

        if ($taskId) {
            $res = $this->attachmentService->handleUpload($_FILES['attachment'] ?? null, $taskId, $this->currentUser['id'], $this->currentUser['role']);
            if (isset($res['error'])) $this->errorRedirect($res['error'], "../controller/TaskController.php?action=show&id=$taskId");
            $this->activityModel->log($this->currentUser['id'], $taskId, 'file_uploaded', "Uploaded: " . $res['original_name']);
            $this->successRedirect("Uploaded", "../controller/TaskController.php?action=show&id=$taskId");
        } elseif ($projectId) {
            $res = $this->attachmentService->handleProjectUpload($_FILES['attachment'] ?? null, $projectId, $this->currentUser['id'], $this->currentUser['role']);
            if (isset($res['error'])) $this->errorRedirect($res['error'], "../controller/ProjectController.php?action=show&id=$projectId");
            $this->activityModel->log($this->currentUser['id'], null, 'file_uploaded_to_project', "Uploaded: " . $res['original_name'] . " to project $projectId");
            $this->successRedirect("Uploaded", "../controller/ProjectController.php?action=show&id=$projectId");
        } else {
            $this->errorRedirect("Target ID required");
        }
    }

    public function download()
    {
        $id = $this->query('id');
        $a = $this->attachmentModel->find($id);
        if (!$a) $this->errorRedirect("Not found");

        // Simple access check (could be moved to service if more complex)
        $t = (new Task())->find($a['task_id']);
        if (!$this->attachmentService->canAccessTask($t, $this->currentUser['id'], $this->currentUser['role'])) $this->errorRedirect("Denied");

        $path = UPLOAD_PATH . '/' . $a['file_path'];
        if (!file_exists($path)) $this->errorRedirect("File missing");

        $mime = function_exists('mime_content_type') ? mime_content_type($path) : 'application/octet-stream';
        
        // Clear any previous output (e.g. notices/whitespace)
        if (ob_get_level()) ob_end_clean();
        
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . basename($a['file_name']) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function delete()
    {
        $id = $this->post('attachment_id');
        $taskId = $this->post('task_id');
        $projectId = $this->post('project_id');
        
        $a = $this->attachmentModel->find($id);
        if (!$a || !$this->attachmentModel->canDelete($a, $this->currentUser['id'], $this->currentUser['role'])) {
            $redirect = $projectId ? "../controller/ProjectController.php?action=show&id=$projectId" : "../controller/TaskController.php?action=show&id=$taskId";
            $this->errorRedirect("Denied/Not found", $redirect);
        }

        $isProject = !empty($a['project_id']);
        $targetId = $isProject ? $a['project_id'] : $a['task_id'];
        $redirect = $isProject ? "../controller/ProjectController.php?action=show&id=$targetId" : "../controller/TaskController.php?action=show&id=$targetId";

        if ($this->attachmentModel->delete($id, $this->currentUser['id'], $this->currentUser['role'])) {
            $action = $isProject ? 'file_deleted_from_project' : 'file_deleted';
            $this->activityModel->log($this->currentUser['id'], $isProject ? null : $targetId, $action, "Deleted: " . $a['file_name']);
            $this->successRedirect("Deleted", $redirect);
        }
        $this->errorRedirect("Failed", $redirect);
    }

    public function get_attachments()
    {
        header('Content-Type: application/json');
        $taskId = $this->query('task_id');
        $t = (new Task())->find($taskId);
        if (!$t || !$this->attachmentService->canAccessTask($t, $this->currentUser['id'], $this->currentUser['role'])) {
            echo json_encode(['error' => 'Denied']); exit;
        }

        $list = $this->attachmentModel->getByTask($taskId);
        $res = [];
        $uploader = new Uploader();
        foreach ($list as $a) {
            $info = $uploader->getFileInfo($a['file_path']);
            $res[] = [
                'id' => $a['id'], 'file_name' => $a['file_name'], 'created_at' => $a['created_at'],
                'file_size' => $info['size'] ?? 0, 'icon_class' => $this->attachmentModel->getFileIconClass($a['file_path']),
                'uploader_name' => $a['uploader_name'] ?? 'System',
                'can_delete' => ($this->currentUser['role'] === 'admin' || $this->currentUser['role'] === 'manager' || $a['uploaded_by'] == $this->currentUser['id'])
            ];
        }
        echo json_encode(['attachments' => $res]); exit;
    }
}

$action = $_GET['action'] ?? 'index';
$controller = new AttachmentController();

$methodName = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $action))));

if (method_exists($controller, $methodName)) {
    $controller->$methodName();
} else {
    header("Location: ../controller/TaskController.php");
}
