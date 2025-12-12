<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Priority.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Permission.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class TaskController
{
    private $taskModel;
    private $userModel;
    private $priorityModel;
    private $commentModel;
    private $attachmentModel;
    private $notificationModel;
    private $projectModel;
    private $permissionModel;
    private $currentUser;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->userModel = new User(require_once __DIR__ . '/../config/db.php');
        $this->priorityModel = new Priority();
        $this->commentModel = new Comment();
        $this->attachmentModel = new Attachment();
        $this->notificationModel = new Notification();
        $this->projectModel = new Project();
        $this->permissionModel = new Permission();
        $this->currentUser = Auth::user();
    }

    // Display tasks list
    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        // Get user role for permissions
        $userRole = $this->getUserRole($this->currentUser['id']);

        // Build filters from GET parameters
        $filters = $this->buildFilters();

        // Check for CSV Export
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportToCsv($filters, $userRole);
            return;
        }

        // Check if accessing "My Tasks"
        if (isset($_GET['my_tasks']) && $_GET['my_tasks'] === 'true') {
            
            if ($userRole === 'manager') {
                // Manager: Show tasks in projects they manage
                $managedProjects = $this->projectModel->getByManager($this->currentUser['id']);
                
                if (empty($managedProjects)) {
                    $tasks = [];
                    $totalTasks = 0;
                } else {
                    $projectIds = array_column($managedProjects, 'id');
                    $tasks = $this->taskModel->getByProjects($projectIds, $filters, $perPage, $offset);
                    // Note: getCount might need update to support projectIds, but for now we'll accept approximate or fix later if needed
                    // A better way is to filter getCount via a custom query or strict logical verification
                    // For now, let's use count($tasks) if page 1 and < perPage, else we might default to 0 or fix getCount.
                    // Actually, simpler: TaskModel::assignedTo was used before.
                    // We can't reuse getCount easily without modification. 
                    // Let's implement a workaround or accept we show what we have.
                    // To be robust: update getCount too? Or just simple count since managers won't have infinite tasks usually.
                    // Actually, let's just fetch all project tasks count for pagination.
                    // We'll leave totalTasks approximate for this specific filtered view or just fetch all without limit to count (expensive but safe).
                    $allTasksCount = $this->taskModel->getByProjects($projectIds, $filters); 
                    $totalTasks = count($allTasksCount);
                }
                
            } elseif ($userRole === 'member') {
                // Member: Show only tasks assigned to them
                $tasks = $this->taskModel->assignedTo($this->currentUser['id'], $filters, $perPage, $offset);
                $totalTasks = $this->taskModel->getCount($filters, $this->currentUser['id'], $userRole);
                
            } else {
                // Admin: Redirect to All Tasks in admin menu
                header("Location: " . BASE_URL . "/controller/AdminController.php?action=all_tasks");
                exit;
            }
            
        } else {
            // Direct access to tasks without my_tasks=true
            // Allow if there are filters (status, search, etc.) or show_all for managers/admins
            $hasFilters = !empty($filters);
            $showAll = isset($_GET['show_all']) && $_GET['show_all'] === 'true';
            
            if ($userRole === 'admin') {
                // Admin can access all tasks
                $tasks = $this->taskModel->all($filters, $perPage, $offset);
                $totalTasks = $this->taskModel->getCount($filters);
            } elseif ($userRole === 'manager' && ($hasFilters || $showAll)) {
                // Manager can access filtered tasks or all tasks
                $managedProjects = $this->projectModel->getByManager($this->currentUser['id']);
                
                if (empty($managedProjects)) {
                    $tasks = [];
                    $totalTasks = 0;
                } else {
                    $projectIds = array_column($managedProjects, 'id');
                    $tasks = $this->taskModel->getByProjects($projectIds, $filters, $perPage, $offset);
                    $allTasksCount = $this->taskModel->getByProjects($projectIds, $filters);
                    $totalTasks = count($allTasksCount);
                }
            } else {
                // No filters and not admin - redirect to projects (project-first approach)
                header("Location: " . BASE_URL . "/controller/ProjectController.php");
                exit;
            }
        }

        $totalPages = ceil($totalTasks / $perPage);

        // Get additional data for filters
        $priorities = $this->priorityModel->all();
        $users = ($userRole === 'admin' || $userRole === 'manager') ? $this->userModel->getAll() : null;

        // Load view
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/tasks/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Show create task form
    public function create()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);
        $projectId = $_GET['project_id'] ?? null;

        // Managers and admins can create tasks
        if (!$this->permissionModel->canCreateTasks($this->currentUser['id'])) {
            $_SESSION['error'] = "You don't have permission to create tasks.";
            header("Location: ?action=index");
            exit;
        }

        // If project_id is provided, validate access
        if ($projectId) {
            if (!$this->projectModel->hasAccess($projectId, $this->currentUser['id'], $userRole)) {
                $_SESSION['error'] = "You don't have access to this project.";
                header("Location: " . BASE_URL . "/controller/ProjectController.php");
                exit;
            }
            $project = $this->projectModel->find($projectId);
        } else {
            // For backward compatibility, get first project or require selection
            $projects = ($userRole === 'admin') ? 
                $this->projectModel->all() : 
                $this->projectModel->getByManager($this->currentUser['id']);
            
            if (empty($projects)) {
                $_SESSION['error'] = "No projects available. Please create a project first.";
                header("Location: " . BASE_URL . "/controller/ProjectController.php");
                exit;
            }
            $project = null;
        }

        $priorities = $this->priorityModel->all();

        // Self-healing: Seed defaults if empty
        if (empty($priorities)) {
            $this->seedPriorities();
            $priorities = $this->priorityModel->all();
        }
        
        // Get users for assignment (project team members if in project context)
        if ($projectId) {
            $users = $this->projectModel->getMembers($projectId);
        } else {
            $users = ($userRole === 'admin' || $userRole === 'manager') ? $this->userModel->getAll() : null;
        }

        // Get available projects for selection
        $projects = ($userRole === 'admin') ? 
            $this->projectModel->all() : 
            $this->projectModel->getByManager($this->currentUser['id']);

        // Get form data from session if it exists (for validation errors)
        $formData = $_SESSION['form_data'] ?? [];
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['form_data'], $_SESSION['errors']);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/tasks/create.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Store new task
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        // Only managers and admins can create tasks
        if (!$this->permissionModel->canCreateTasks($this->currentUser['id'])) {
            $_SESSION['error'] = "You don't have permission to create tasks.";
            header("Location: ?action=index");
            exit;
        }

        // Validate and sanitize input
        $data = $this->validateTaskData($_POST);

        if (!$data) {
            // Store form data in session for repopulation
            $_SESSION['form_data'] = $_POST;
            $projectId = $_POST['project_id'] ?? '';
            header("Location: ?action=create" . ($projectId ? "&project_id=$projectId" : ""));
            exit;
        }

        // Validate project access
        if (!$this->projectModel->hasAccess($data['project_id'], $this->currentUser['id'], $userRole)) {
            $_SESSION['error'] = "You don't have access to this project.";
            header("Location: " . BASE_URL . "/controller/ProjectController.php");
            exit;
        }

        $data['created_by'] = $this->currentUser['id'];

        // If user is not admin/manager, they can only assign to themselves
        if ($userRole !== 'admin' && $userRole !== 'manager') {
            $data['assigned_to'] = $this->currentUser['id'];
        }

        if ($taskId = $this->taskModel->create($data)) {
            // Create notification if task is assigned to someone
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $this->currentUser['id']) {
                $this->notificationModel->createTaskAssignmentNotification(
                    $taskId,
                    $data['assigned_to'],
                    $this->currentUser['id']
                );
            }
            
            $_SESSION['success'] = "Task created successfully!";
            header("Location: ?action=show&id=$taskId");
        } else {
            $_SESSION['error'] = "Failed to create task.";
            $projectId = $data['project_id'] ?? '';
            header("Location: ?action=create" . ($projectId ? "&project_id=$projectId" : ""));
        }
        exit;
    }

    // Show task details
    public function show($id)
    {
        $task = $this->taskModel->find($id);

        if (!$task) {
            $_SESSION['error'] = "Task not found.";
            header("Location: ?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        // Check if user can view this task
        if (!$this->canViewTask($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to view this task.";
            header("Location: ?action=index");
            exit;
        }

        // Fetch additional data
        $comments = $this->commentModel->getByTask($id);
        $attachments = $this->attachmentModel->getByTask($id);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/tasks/show.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Show edit task form
    public function edit($id)
    {
        $task = $this->taskModel->find($id);

        if (!$task) {
            $_SESSION['error'] = "Task not found.";
            header("Location: ?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canEditTask($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to edit this task.";
            header("Location: ?action=index");
            exit;
        }

        $priorities = $this->priorityModel->all();
        $users = ($userRole === 'admin' || $userRole === 'manager') ? $this->userModel->getAll() : null;

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/tasks/edit.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Update task
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $task = $this->taskModel->find($id);

        if (!$task) {
            $_SESSION['error'] = "Task not found.";
            header("Location: ?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canEditTask($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to edit this task.";
            header("Location: ?action=index");
            exit;
        }

        // Validate and sanitize input
        $data = $this->validateTaskData($_POST);

        if (!$data) {
            header("Location: ?action=edit&id=$id");
            exit;
        }

        // If user is not admin/manager, they can only assign to themselves
        if ($userRole !== 'admin' && $userRole !== 'manager') {
            $data['assigned_to'] = $this->currentUser['id'];
        }

        if ($this->taskModel->update($id, $data)) {
            // Create notification if assignment changed
            if (!empty($data['assigned_to']) && $data['assigned_to'] != $task['assigned_to'] && $data['assigned_to'] != $this->currentUser['id']) {
                $this->notificationModel->createTaskAssignmentNotification(
                    $id,
                    $data['assigned_to'],
                    $this->currentUser['id']
                );
            }
            
            $_SESSION['success'] = "Task updated successfully!";
            header("Location: ?action=show&id=$id");
        } else {
            $_SESSION['error'] = "Failed to update task.";
            header("Location: ?action=edit&id=$id");
        }
        exit;
    }

    // Delete task
    public function delete($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $task = $this->taskModel->find($id);

        if (!$task) {
            $_SESSION['error'] = "Task not found.";
            header("Location: ?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canDeleteTask($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to delete this task.";
            header("Location: ?action=index");
            exit;
        }

        if ($this->taskModel->delete($id)) {
            $_SESSION['success'] = "Task deleted successfully!";
        } else {
            $_SESSION['error'] = "Failed to delete task.";
        }

        header("Location: ?action=index");
        exit;
    }

    // Change task status
    public function changeStatus($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $status = $_POST['status'] ?? '';
        $validStatuses = ['todo', 'in_progress', 'completed'];

        if (!in_array($status, $validStatuses)) {
            $_SESSION['error'] = "Invalid status.";
            header("Location: ?action=show&id=$id");
            exit;
        }

        $task = $this->taskModel->find($id);

        if (!$task) {
            $_SESSION['error'] = "Task not found.";
            header("Location: ?action=index");
            exit;
        }

        $userRole = $this->getUserRole($this->currentUser['id']);

        if (!$this->canChangeStatus($task, $userRole)) {
            $_SESSION['error'] = "You don't have permission to change this task's status.";
            header("Location: ?action=show&id=$id");
            exit;
        }

        if ($this->taskModel->updateStatus($id, $status)) {
            // Create notification if task is completed
            if ($status === 'completed' && $task['status'] !== 'completed') {
                $this->notificationModel->createTaskCompletionNotification(
                    $id,
                    $this->currentUser['id']
                );
            }
            
            // Auto-update project status based on task completion
            if (!empty($task['project_id'])) {
                $this->projectModel->updateProjectStatus($task['project_id']);
            }
            
            $_SESSION['success'] = "Task status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update task status.";
        }

        header("Location: ?action=show&id=$id");
        exit;
    }

    // Search tasks
    public function search()
    {
        // Redirect to index with search param to leverage unified filtering
        $query = trim($_GET['q'] ?? '');
        header("Location: ?action=index&search=" . urlencode($query));
        exit;
    }

    // Bulk Update
    public function bulk_update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $taskIds = $_POST['task_ids'] ?? [];
        $status = $_POST['status'] ?? '';
        
        if (empty($taskIds) || empty($status)) {
            $_SESSION['error'] = "No tasks selected or invalid status.";
            header("Location: ?action=index");
            exit;
        }

        $count = 0;
        foreach ($taskIds as $id) {
            $task = $this->taskModel->find($id);
            // Permission check can go here
            if ($task && $this->canChangeStatus($task, $this->getUserRole($this->currentUser['id']))) {
                if ($this->taskModel->updateStatus($id, $status)) {
                    // Create notification if task is completed
                    if ($status === 'completed' && $task['status'] !== 'completed') {
                        $this->notificationModel->createTaskCompletionNotification(
                            $id,
                            $this->currentUser['id']
                        );
                    }
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $_SESSION['success'] = "$count tasks updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update selected tasks.";
        }

        header("Location: ?action=index");
        exit;
    }

    // Bulk Delete
    public function bulk_delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=index");
            exit;
        }

        $taskIds = $_POST['task_ids'] ?? [];
        
        if (empty($taskIds)) {
            $_SESSION['error'] = "No tasks selected.";
            header("Location: ?action=index");
            exit;
        }

        $count = 0;
        foreach ($taskIds as $id) {
            $task = $this->taskModel->find($id);
            if ($task && $this->canDeleteTask($task, $this->getUserRole($this->currentUser['id']))) {
                if ($this->taskModel->delete($id)) {
                    $count++;
                }
            }
        }

        if ($count > 0) {
            $_SESSION['success'] = "$count tasks deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete selected tasks.";
        }

        header("Location: ?action=index");
        exit;
    }
    
    // Export to CSV
    private function exportToCsv($filters, $userRole)
    {
        // Fetch ALL matching tasks (no pagination)
        if ($userRole === 'admin' || $userRole === 'manager') {
            $tasks = $this->taskModel->all($filters);
        } else {
            $tasks = $this->taskModel->assignedTo($this->currentUser['id'], $filters);
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tasks_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header row
        fputcsv($output, ['ID', 'Title', 'Status', 'Priority', 'Category', 'Assigned To', 'Due Date', 'Created At']);
        
        foreach ($tasks as $task) {
            fputcsv($output, [
                $task['id'],
                $task['title'],
                ucfirst(str_replace('_', ' ', $task['status'])),
                ucfirst($task['priority_name']),
                $task['category_name'],
                $task['assignee_name'] ?? 'Unassigned',
                $task['deadline'],
                $task['created_at']
            ]);
        }
        
        fclose($output);
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

    private function buildFilters()
    {
        $filters = [];

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['priority_id'])) {
            $filters['priority_id'] = (int)$_GET['priority_id'];
        }

        if (!empty($_GET['assigned_to'])) {
            $filters['assigned_to'] = (int)$_GET['assigned_to'];
        }

        if (!empty($_GET['deadline_from'])) {
            $filters['deadline_from'] = $_GET['deadline_from'];
        }

        if (!empty($_GET['deadline_to'])) {
            $filters['deadline_to'] = $_GET['deadline_to'];
        }

        if (!empty($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }

        return $filters;
    }

    private function validateTaskData($data)
    {
        $errors = [];

        // Required: project_id
        if (empty($data['project_id'])) {
            $errors['project_id'] = "Project is required.";
        } else {
            $data['project_id'] = (int)$data['project_id'];
        }

        // Required fields
        if (empty(trim($data['title'] ?? ''))) {
            $errors['title'] = "Title is required.";
        }

        // Title length
        if (strlen($data['title'] ?? '') > 255) {
            $errors['title'] = "Title must be less than 255 characters.";
        }

        // Description length
        if (strlen($data['description'] ?? '') > 1000) {
            $errors['description'] = "Description must be less than 1000 characters.";
        }

        // Validate deadline format if provided
        if (!empty($data['deadline'])) {
            $deadline = date('Y-m-d', strtotime($data['deadline']));
            if ($deadline === '1970-01-01' || $deadline === false) {
                $errors['deadline'] = "Invalid deadline date.";
            }
            $data['deadline'] = $deadline;
        }

        // Validate priority
        if (!empty($data['priority_id'])) {
            $data['priority_id'] = (int)$data['priority_id'];
            if ($data['priority_id'] < 1 || $data['priority_id'] > 3) {
                $errors['priority_id'] = "Invalid priority level.";
            }
        }

        // Validate assigned_to
        if (!empty($data['assigned_to'])) {
            $data['assigned_to'] = (int)$data['assigned_to'];
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            return false;
        }

        return [
            'project_id' => $data['project_id'],
            'title' => htmlspecialchars(trim($data['title'])),
            'description' => htmlspecialchars(trim($data['description'] ?? '')),
            'priority_id' => $data['priority_id'] ?? 2,
            'status' => !empty($data['status']) ? $data['status'] : 'todo',
            'deadline' => $data['deadline'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null
        ];
    }

    private function canCreateTask($userRole)
    {
        return in_array($userRole, ['admin', 'manager', 'member']);
    }

    private function canViewTask($task, $userRole)
    {
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }

        return $task['assigned_to'] == $this->currentUser['id'] || $task['created_by'] == $this->currentUser['id'];
    }

    private function canEditTask($task, $userRole)
    {
        if ($userRole === 'admin') {
            return true;
        }

        if ($userRole === 'manager') {
            return true; // Managers can edit all tasks
        }

        // Members can only edit their own tasks or tasks assigned to them
        return $task['created_by'] == $this->currentUser['id'];
    }

    private function canDeleteTask($task, $userRole)
    {
        if ($userRole === 'admin') {
            return true;
        }

        if ($userRole === 'manager') {
            return true; // Managers can delete all tasks
        }

        // Members can only delete their own created tasks
        return $task['created_by'] == $this->currentUser['id'];
    }

    private function canChangeStatus($task, $userRole)
    {
        if ($userRole === 'admin' || $userRole === 'manager') {
            return true;
        }

        // Members can change status of tasks assigned to them or created by them
        return $task['assigned_to'] == $this->currentUser['id'] || $task['created_by'] == $this->currentUser['id'];
    }

    private function seedPriorities()
    {
        $defaults = [
            ['name' => 'low', 'weight' => 1],
            ['name' => 'medium', 'weight' => 2],
            ['name' => 'high', 'weight' => 3]
        ];

        foreach ($defaults as $data) {
            $this->priorityModel->create($data);
        }
    }
}

// Handle routing
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

$controller = new TaskController();

switch ($action) {
    case 'index':
        $controller->index();
        break;

    case 'create':
        $controller->create();
        break;

    case 'store':
        $controller->store();
        break;

    case 'show':
        if ($id) {
            $controller->show($id);
        } else {
            header("Location: ?action=index");
        }
        break;

    case 'edit':
        if ($id) {
            $controller->edit($id);
        } else {
            header("Location: ?action=index");
        }
        break;

    case 'update':
        if ($id) {
            $controller->update($id);
        } else {
            header("Location: ?action=index");
        }
        break;

    case 'delete':
        if ($id) {
            $controller->delete($id);
        } else {
            header("Location: ?action=index");
        }
        break;

    case 'change_status':
        if ($id) {
            $controller->changeStatus($id);
        } else {
            header("Location: ?action=index");
        }
        break;

    case 'search':
        $controller->search();
        break;
        
    case 'bulk_update':
        $controller->bulk_update();
        break;

    case 'bulk_delete':
        $controller->bulk_delete();
        break;

    default:
        header("Location: ?action=index");
        break;
}
