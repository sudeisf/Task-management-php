<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/CommentService.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Task.php';

class CommentController extends Controller
{
    private $commentService;
    private $commentModel;
    private $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->commentService = new CommentService();
        $this->commentModel = new Comment();
        $this->activityModel = new Activity();
    }

    public function store()
    {
        $taskId = $this->post('task_id');
        $text = $this->post('comment');
        if (!$taskId || empty($text)) $this->errorRedirect("Missing data", "../controller/TaskController.php?action=show&id=$taskId");

        $res = $this->commentService->handleCreate($taskId, $this->currentUser['id'], $text);
        if (isset($res['error'])) $this->errorRedirect($res['error'], "../controller/TaskController.php?action=show&id=$taskId");

        $this->activityModel->log($this->currentUser['id'], $taskId, 'comment_added', "Added comment");
        $this->successRedirect("Comment added", "../controller/TaskController.php?action=show&id=$taskId#comment-".$res['id']);
    }

    public function update()
    {
        $id = $this->post('comment_id');
        $taskId = $this->post('task_id');
        $text = $this->post('comment');
        if (!$this->commentModel->canModify($id, $this->currentUser['id'], $this->currentUser['role'])) {
            $this->errorRedirect("Denied", "../controller/TaskController.php?action=show&id=$taskId");
        }

        if ($this->commentModel->update($id, htmlspecialchars($text, ENT_QUOTES, 'UTF-8'), $this->currentUser['id'])) {
            $this->activityModel->log($this->currentUser['id'], $taskId, 'comment_updated', "Updated comment");
            $this->successRedirect("Updated", "../controller/TaskController.php?action=show&id=$taskId");
        }
        $this->errorRedirect("Failed", "../controller/TaskController.php?action=show&id=$taskId");
    }

    public function delete()
    {
        $id = $this->post('comment_id');
        $taskId = $this->post('task_id');
        if (!$this->commentModel->canModify($id, $this->currentUser['id'], $this->currentUser['role'])) {
            $this->errorRedirect("Denied", "../controller/TaskController.php?action=show&id=$taskId");
        }

        if ($this->commentModel->delete($id, ($this->currentUser['role'] === 'admin' || $this->currentUser['role'] === 'manager') ? null : $this->currentUser['id'])) {
            $this->activityModel->log($this->currentUser['id'], $taskId, 'comment_deleted', "Deleted comment");
            $this->successRedirect("Deleted", "../controller/TaskController.php?action=show&id=$taskId");
        }
        $this->errorRedirect("Failed", "../controller/TaskController.php?action=show&id=$taskId");
    }

    public function get_comments()
    {
        header('Content-Type: application/json');
        $taskId = $this->query('task_id');
        $comments = $this->commentModel->getByTask($taskId);
        $res = [];
        while ($c = $comments->fetch_assoc()) {
            $res[] = [
                'id' => $c['id'], 'comment' => $c['comment'], 'created_at' => $c['created_at'],
                'user_name' => $c['full_name'] ?? 'Unknown',
                'can_edit' => $this->commentModel->canModify($c['id'], $this->currentUser['id'], $this->currentUser['role'])
            ];
        }
        echo json_encode(['comments' => $res]); exit;
    }
}

$action = $_GET['action'] ?? 'index';
$controller = new CommentController();

$methodName = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $action))));

if (method_exists($controller, $methodName)) {
    $controller->$methodName();
} else {
    header("Location: ../controller/TaskController.php");
}
