<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/TaskService.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Priority.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../services/AttachmentService.php';

class TaskController extends Controller
{
    private $taskService;
    private $taskModel;
    private $userModel;
    private $priorityModel;
    private $commentModel;
    private $attachmentModel;
    private $notificationModel;
    private $projectModel;
    private $permissionModel;
    private $attachmentService;
    private $activityModel;

    public function __construct()
    {
        parent::__construct();
        $this->taskService = new TaskService();
        $this->taskModel = new Task();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
        $this->priorityModel = new Priority();
        $this->commentModel = new Comment();
        $this->attachmentModel = new Attachment();
        $this->notificationModel = new Notification();
        $this->projectModel = new Project();
        $this->permissionModel = new Permission();
        $this->attachmentService = new AttachmentService();
        $this->activityModel = new Activity();
    }

    public function index()
    {
        $page = $this->query('page', 1);
        $perPage = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $perPage;
        $filters = $this->buildFilters();

        $result = $this->taskService->getTasksForIndex(
            $this->currentUser['id'],
            $this->currentUser['role'],
            $filters,
            $perPage,
            $offset,
            $this->query('my_tasks') === 'true'
        );

        if (!$result) $this->redirect(BASE_URL . "/controller/ProjectController.php");

        list($tasks, $totalTasks) = $result;

        $this->view('tasks/index', [
            'tasks' => $tasks,
            'totalTasks' => $totalTasks,
            'totalPages' => ceil($totalTasks / $perPage),
            'priorities' => $this->priorityModel->all(),
            'users' => in_array($this->currentUser['role'], ['admin', 'manager']) ? $this->userModel->getByRole('member') : null,
            'page' => $page
        ]);
    }

    public function create()
    {
        $projectId = $this->query('project_id');
        if (!$this->permissionModel->canCreateTasks($this->currentUser['id'])) $this->errorRedirect("You don't have permission to create tasks.");

        if ($projectId && !$this->projectModel->hasAccess($projectId, $this->currentUser['id'], $this->currentUser['role'])) {
            $this->errorRedirect("You don't have access to this project.", BASE_URL . "/controller/ProjectController.php");
        }

        $project = $projectId ? $this->projectModel->find($projectId) : null;
        if (!$projectId) {
            $projects = ($this->currentUser['role'] === 'admin') ? $this->projectModel->all() : $this->projectModel->getAssigned($this->currentUser['id']);
            if (empty($projects)) $this->errorRedirect("No projects available. Please create a project first.", BASE_URL . "/controller/ProjectController.php");
        }

        $priorities = $this->priorityModel->all();

        $users = null;
        if ($projectId) {
            if ($this->currentUser['role'] === 'member') {
                $users = $this->projectModel->getMembers($projectId);
            } else {
                $users = $this->userModel->getByRole('member');
            }
        } else {
            $users = ($this->currentUser['role'] === 'admin' || $this->currentUser['role'] === 'manager') ? $this->userModel->getByRole('member') : null;
        }

        $projects = ($this->currentUser['role'] === 'admin') ?
            $this->projectModel->all() :
            $this->projectModel->getAssigned($this->currentUser['id']);

        $this->view('tasks/create', [
            'project' => $project,
            'priorities' => $priorities,
            'users' => $users,
            'projects' => $projects,
            'formData' => $_SESSION['form_data'] ?? [],
            'errors' => $_SESSION['errors'] ?? []
        ]);
        unset($_SESSION['form_data'], $_SESSION['errors']);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("?action=index");
        }

        if (!$this->permissionModel->canCreateTasks($this->currentUser['id'])) $this->errorRedirect("You don't have permission to create tasks.");

        $val = $this->taskService->validateTask($_POST);
        if (!$val['data']) {
            $_SESSION['errors'] = $val['errors'];
            $_SESSION['form_data'] = $_POST;
            $projectId = $_POST['project_id'] ?? '';
            $this->redirect("?action=create" . ($projectId ? "&project_id=$projectId" : ""));
        }

        $data = $val['data'];
        if (!$this->projectModel->hasAccess($data['project_id'], $this->currentUser['id'], $this->currentUser['role'])) $this->errorRedirect("You don't have access to this project.", BASE_URL . "/controller/ProjectController.php");

        $data['created_by'] = $this->currentUser['id'];
        if (!in_array($this->currentUser['role'], ['admin', 'manager'])) $data['assigned_to'] = $this->currentUser['id'];

        if ($taskId = $this->taskModel->create($data)) {
            // Handle file upload
            if (isset($_FILES['attachment']) && !empty($_FILES['attachment']['name'])) {
                $res = $this->attachmentService->handleUpload($_FILES['attachment'], $taskId, $this->currentUser['id'], $this->currentUser['role']);
                if (isset($res['error'])) {
                    setFlashMessage("Task created but file upload failed: " . $res['error'], 'warning');
                } else {
                    $this->activityModel->log($this->currentUser['id'], $taskId, 'file_uploaded', "Uploaded file during task creation");
                }
            }

            if (!empty($data['assigned_to']) && $data['assigned_to'] != $this->currentUser['id']) {
                $this->notificationModel->createTaskAssignmentNotification($taskId, $data['assigned_to'], $this->currentUser['id']);
            }
            if (!empty($data['assigned_to'])) $this->projectModel->assignUser($data['project_id'], $data['assigned_to'], 'member');
            $this->projectModel->updateProjectStatus($data['project_id']);
            $this->successRedirect("Task created successfully!", "?action=show&id=$taskId");
        }
        $projectId = $data['project_id'] ?? '';
        $this->errorRedirect("Failed to create task.", "?action=create" . ($projectId ? "&project_id=$projectId" : ""));
    }

    public function show($id)
    {
        if (!$this->permissionModel->canViewTask($this->currentUser['id'], $id)) $this->errorRedirect("You don't have permission to view this task.");
        $task = $this->taskModel->find($id);
        if (!$task) $this->errorRedirect("Task not found.");

        $this->view('tasks/show', [
            'task' => $task,
            'comments' => $this->commentModel->getByTask($id),
            'attachments' => $this->attachmentModel->getByTask($id),
            'canUpdateStatus' => $this->permissionModel->canUpdateTaskStatus($this->currentUser['id'], $id),
            'userRole' => $this->currentUser['role']
        ]);
    }

    public function edit($id)
    {
        if (!$this->permissionModel->canEditTask($this->currentUser['id'], $id)) $this->errorRedirect("You don't have permission to edit this task.");
        $task = $this->taskModel->find($id);
        if (!$task) $this->errorRedirect("Task not found.");

        $this->view('tasks/edit', [
            'task' => $task,
            'priorities' => $this->priorityModel->all(),
            'users' => in_array($this->currentUser['role'], ['admin', 'manager']) ? $this->userModel->getByRole('member') : null,
            'canUpdateStatus' => $this->permissionModel->canUpdateTaskStatus($this->currentUser['id'], $id)
        ]);
    }

    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("?action=index");
        }

        if (!$this->permissionModel->canEditTask($this->currentUser['id'], $id)) $this->errorRedirect("You don't have permission to edit this task.");
        $task = $this->taskModel->find($id);
        if (!$task) $this->errorRedirect("Task not found.");

        $val = $this->taskService->validateTask($_POST);
        if (!$val['data']) {
            $_SESSION['errors'] = $val['errors'];
            $this->redirect("?action=edit&id=$id");
        }

        $data = $val['data'];

        // Restriction: Admins and Managers cannot change status
        if (in_array($this->currentUser['role'], ['admin', 'manager'])) {
            $data['status'] = $task['status'];
        } else {
            $data['assigned_to'] = $this->currentUser['id'];
        }

        if ($this->taskModel->update($id, $data)) {
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $task['assigned_to'] && $data['assigned_to'] != $this->currentUser['id']) {
                $this->notificationModel->createTaskAssignmentNotification($id, $data['assigned_to'], $this->currentUser['id']);
            }
            if (!empty($data['assigned_to'])) $this->projectModel->assignUser($data['project_id'], $data['assigned_to'], 'member');
            if (!empty($data['assigned_to'])) $this->projectModel->assignUser($data['project_id'], $data['assigned_to'], 'member');
            $this->projectModel->updateProjectStatus($data['project_id']);

            // Handle file upload
            if (isset($_FILES['attachment']) && !empty($_FILES['attachment']['name'])) {
                $res = $this->attachmentService->handleUpload($_FILES['attachment'], $id, $this->currentUser['id'], $this->currentUser['role']);
                if (isset($res['error'])) {
                    setFlashMessage("Task updated but file upload failed: " . $res['error'], 'warning');
                } else {
                    $this->activityModel->log($this->currentUser['id'], $id, 'file_uploaded', "Uploaded file during task update");
                }
            }

            $this->successRedirect("Task updated successfully!", "?action=show&id=$id");
        }
        $this->errorRedirect("Failed to update task.", "?action=edit&id=$id");
    }

    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("?action=index");
        }

        if (!in_array($this->currentUser['role'], ['admin', 'manager']) && !$this->permissionModel->canEditTask($this->currentUser['id'], $id)) {
            $this->errorRedirect("You don't have permission to delete this task.");
        }
        $task = $this->taskModel->find($id);
        if (!$task) $this->errorRedirect("Task not found.");

        if ($this->taskModel->delete($id)) {
            if (!empty($task['project_id'])) $this->projectModel->updateProjectStatus($task['project_id']);
            $this->successRedirect("Task deleted successfully!", "?action=index");
        }
        $this->errorRedirect("Failed to delete task.");
    }

    public function changeStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect("?action=index");
        }

        $status = $this->post('status');
        if (!in_array($status, ['todo', 'in_progress', 'completed'])) $this->errorRedirect("Invalid status.", "?action=show&id=$id");
        if (!$this->permissionModel->canUpdateTaskStatus($this->currentUser['id'], $id)) $this->errorRedirect("You don't have permission to change this task's status.", "?action=show&id=$id");
        $task = $this->taskModel->find($id);
        if (!$task) $this->errorRedirect("Task not found.");

        if ($this->taskModel->updateStatus($id, $status)) {
            if (!empty($task['project_id'])) $this->projectModel->updateProjectStatus($task['project_id']);
            $this->successRedirect("Task status updated successfully!", "?action=show&id=$id");
        }
        $this->errorRedirect("Failed to update task status.", "?action=show&id=$id");
    }


    public function search()
    {
        $this->redirect("?action=index&search=" . urlencode(trim($this->query('q', ''))));
    }

    private function buildFilters()
    {
        $filters = [];
        $keys = ['status', 'priority_id', 'assigned_to', 'deadline_from', 'deadline_to', 'search'];
        foreach ($keys as $k) {
            if ($val = $this->query($k)) $filters[$k] = ($k === 'search') ? trim($val) : $val;
        }
        return $filters;
    }
}

$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;
$controller = new TaskController();

$methodName = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $action))));

if (method_exists($controller, $methodName)) {
    $controller->$methodName($id);
} else {
    $controller->index();
}
