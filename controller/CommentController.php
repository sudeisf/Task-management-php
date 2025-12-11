<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class CommentController
{
    private $commentModel;
    private $taskModel;
    private $activityModel;
    private $currentUser;

    public function __construct()
    {
        $this->commentModel = new Comment();
        $this->taskModel = new Task();
        $this->activityModel = new Activity();
        $this->currentUser = Auth::user();
    }

    // Store new comment
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $task_id = (int)($_POST['task_id'] ?? 0);
        $comment_text = trim($_POST['comment'] ?? '');

        if (!$task_id || empty($comment_text)) {
            $_SESSION['error'] = "Task ID and comment are required.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
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

        if (!$this->canCommentOnTask($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to comment on this task.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        // Validate comment length
        if (strlen($comment_text) > 1000) {
            $_SESSION['error'] = "Comment must be less than 1000 characters.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        // Create comment
        $commentData = [
            'task_id' => $task_id,
            'user_id' => $this->currentUser['id'],
            'comment' => htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8')
        ];

        if ($newCommentId = $this->commentModel->create($commentData)) {
            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                'comment_added',
                $task_id,
                "Added comment to task: " . substr($comment_text, 0, 50) . "..."
            );

            // Create notification for task assignee if not the commenter
            if ($task['assigned_to'] && $task['assigned_to'] != $this->currentUser['id']) {
                $this->createNotification(
                    $task['assigned_to'],
                    $task_id,
                    "New comment on task: {$task['title']}"
                );
            }

            // Create notification for task creator if not the commenter
            if ($task['created_by'] != $this->currentUser['id']) {
                $this->createNotification(
                    $task['created_by'],
                    $task_id,
                    "New comment on your task: {$task['title']}"
                );
            }

            $_SESSION['success'] = "Comment added successfully!";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id#comment-$newCommentId");
        } else {
            $_SESSION['error'] = "Failed to add comment.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
        }
        exit;
    }

    // Update comment
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $task_id = (int)($_POST['task_id'] ?? 0);
        $comment_text = trim($_POST['comment'] ?? '');

        if (!$comment_id || !$task_id || empty($comment_text)) {
            $_SESSION['error'] = "Comment ID, task ID, and comment text are required.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->commentModel->canModify($comment_id, $this->currentUser['id'], $userRole)) {
            $_SESSION['error'] = "You don't have permission to edit this comment.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        // Validate comment length
        if (strlen($comment_text) > 1000) {
            $_SESSION['error'] = "Comment must be less than 1000 characters.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        if ($this->commentModel->update($comment_id, htmlspecialchars($comment_text, ENT_QUOTES, 'UTF-8'), $this->currentUser['id'])) {
            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                'comment_updated',
                $task_id,
                "Updated comment on task"
            );

            $_SESSION['success'] = "Comment updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update comment.";
        }

        header("Location: ../controller/TaskController.php?action=show&id=$task_id");
        exit;
    }

    // Delete comment
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $comment_id = (int)($_POST['comment_id'] ?? 0);
        $task_id = (int)($_POST['task_id'] ?? 0);

        if (!$comment_id || !$task_id) {
            $_SESSION['error'] = "Comment ID and task ID are required.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->commentModel->canModify($comment_id, $this->currentUser['id'], $userRole)) {
            $_SESSION['error'] = "You don't have permission to delete this comment.";
            header("Location: ../controller/TaskController.php?action=show&id=$task_id");
            exit;
        }

        if ($this->commentModel->delete($comment_id, $userRole === 'admin' || $userRole === 'manager' ? null : $this->currentUser['id'])) {
            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                'comment_deleted',
                $task_id,
                "Deleted comment from task"
            );

            $_SESSION['success'] = "Comment deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete comment.";
        }

        header("Location: ../controller/TaskController.php?action=show&id=$task_id");
        exit;
    }

    // Get comments for a task (AJAX endpoint)
    public function getComments()
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

        if (!$this->canCommentOnTask($task, $userRole)) {
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        $comments = $this->commentModel->getByTask($task_id);
        $commentsArray = [];

        while ($comment = $comments->fetch_assoc()) {
            $commentsArray[] = [
                'id' => $comment['id'],
                'comment' => $comment['comment'],
                'created_at' => $comment['created_at'],
                'user' => [
                    'id' => $comment['user_id'],
                    'name' => $comment['full_name'] ?? 'Unknown User',
                    'email' => $comment['email'] ?? '',
                    'avatar' => $comment['profile_picture'] ?? null
                ],
                'can_edit' => $this->commentModel->canModify($comment['id'], $this->currentUser['id'], $userRole)
            ];
        }

        echo json_encode(['comments' => $commentsArray]);
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

    private function canCommentOnTask($task, $userRole)
    {
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }

        return $task['assigned_to'] == $this->currentUser['id'] ||
               $task['created_by'] == $this->currentUser['id'];
    }

    private function createNotification($user_id, $task_id, $message)
    {
        // This would typically use a Notification model
        // For now, we'll skip this as the Notification model isn't complete yet
        return true;
    }
}

// Handle routing
$action = $_GET['action'] ?? 'index';

$controller = new CommentController();

switch ($action) {
    case 'store':
        $controller->store();
        break;

    case 'update':
        $controller->update();
        break;

    case 'delete':
        $controller->delete();
        break;

    case 'get_comments':
        $controller->getComments();
        break;

    default:
        header("Location: ../controller/TaskController.php?action=index");
        break;
}
