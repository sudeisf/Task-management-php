<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../services/AdminService.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Priority.php';

class AdminController extends Controller
{
    private $adminService;
    private $userModel;
    private $taskModel;
    private $activityModel;
    private $priorityModel;

    public function __construct()
    {
        parent::__construct();
        if ($this->currentUser['role'] !== 'admin') $this->errorRedirect("Access denied", BASE_URL . "/controller/DashboardController.php");
        
        $this->adminService = new AdminService();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
        $this->taskModel = new Task();
        $this->activityModel = new Activity();
        $this->priorityModel = new Priority();
    }

    public function users()
    {
        $page = $this->query('page', 1);
        $perPage = ITEMS_PER_PAGE;
        $users = $this->userModel->getAll();
        $usersArray = [];
        while ($u = $users->fetch_assoc()) $usersArray[] = $u;
        $totalUsers = count($usersArray);

        $this->view('admin/users', [
            'paginatedUsers' => array_slice($usersArray, ($page - 1) * $perPage, $perPage),
            'totalUsers' => $totalUsers,
            'totalPages' => ceil($totalUsers / $perPage),
            'page' => $page
        ]);
    }

    public function createUser() { $this->view('admin/user_form'); }

    public function storeUser()
    {
        $val = $this->adminService->validateUser($_POST, true);
        if (!$val['data']) {
            $_SESSION['errors'] = $val['errors'];
            $_SESSION['form_data'] = $_POST;
            $this->errorRedirect("Please fix the errors below.", "?action=create_user");
        }

        $d = $val['data'];
        if ($this->userModel->create($d['full_name'], $d['email'], $d['password'], $d['role'])) {
            $this->adminService->logActivity($this->currentUser['id'], 'user_registered', "Created: ".$d['email']);
            $this->successRedirect("User created", "?action=users");
        }
        $this->errorRedirect("Failed or email exists", "?action=create_user");
    }

    public function editUser($userId)
    {
        $user = $this->userModel->getById($userId);
        if (!$user) $this->errorRedirect("Not found", "?action=users");
        $this->view('admin/user_form', ['user' => $user]);
    }

    public function updateUser($userId)
    {
        $user = $this->userModel->getById($userId);
        if (!$user) $this->errorRedirect("Not found", "?action=users");

        $val = $this->adminService->validateUser($_POST, false);
        if (!$val['data']) {
            $_SESSION['errors'] = $val['errors'];
            $this->errorRedirect("Please fix the errors below.", "?action=edit_user&id=$userId");
        }

        $d = $val['data'];
        if ($this->userModel->update($userId, $d['full_name'], $d['email'], $d['role'], $d['status'])) {
            if (isset($_POST['change_password']) && !empty($_POST['new_password'])) $this->userModel->changePassword($userId, $_POST['new_password']);
            $this->adminService->logActivity($this->currentUser['id'], 'user_updated', "Updated: ".$d['email']);
            $this->successRedirect("User updated", "?action=users");
        }
        $this->errorRedirect("Update failed", "?action=edit_user&id=$userId");
    }

    public function deleteUser($userId)
    {
        if ($userId == $this->currentUser['id']) $this->errorRedirect("Cannot delete self", "?action=users");
        $user = $this->userModel->getById($userId);
        if ($user) $this->adminService->logActivity($this->currentUser['id'], 'user_deleted', "Deleted: ".$user['email']);
        $this->successRedirect("Deletion not fully implemented in model yet", "?action=users");
    }

    public function allTasks()
    {
        $page = $this->query('page', 1);
        $perPage = ITEMS_PER_PAGE;
        $filters = $this->buildFilters();
        $totalTasks = $this->taskModel->getCount($filters);
        
        $this->view('admin/all_tasks', [
            'tasks' => $this->taskModel->all($filters, $perPage, ($page - 1) * $perPage),
            'totalTasks' => $totalTasks,
            'totalPages' => ceil($totalTasks / $perPage),
            'priorities' => $this->priorityModel->all(),
            'users' => $this->userModel->getAll(),
            'page' => $page
        ]);
    }

    public function activityLogs()
    {
        $page = $this->query('page', 1);
        $perPage = ITEMS_PER_PAGE;
        $totalActivities = $this->activityModel->getCount();
        
        $this->view('admin/activity_logs', [
            'activities' => $this->activityModel->getAll($perPage, ($page - 1) * $perPage),
            'totalActivities' => $totalActivities,
            'totalPages' => ceil($totalActivities / $perPage),
            'page' => $page
        ]);
    }

    public function settings() { $this->view('admin/settings'); }

    private function buildFilters()
    {
        $f = [];
        if ($v = $this->query('status')) $f['status'] = $v;
        if ($v = $this->query('priority_id')) $f['priority_id'] = (int)$v;
        if ($v = $this->query('assigned_to')) $f['assigned_to'] = (int)$v;
        if ($v = $this->query('search')) $f['search'] = trim($v);
        return $f;
    }
}

$action = $_GET['action'] ?? 'users';
$id = $_GET['id'] ?? null;
$controller = new AdminController();

// Convert snake_case action to camelCase method name
$methodName = str_replace(' ', '', lcfirst(ucwords(str_replace('_', ' ', $action))));

if (method_exists($controller, $methodName)) {
    $controller->$methodName($id);
} else {
    $controller->users();
}
