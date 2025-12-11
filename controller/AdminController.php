<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Dashboard.php';

Session::start();

// Check authentication and admin access
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class AdminController
{
    private $userModel;
    private $taskModel;
    private $activityModel;
    private $dashboardModel;
    private $currentUser;

    public function __construct()
    {
        $this->userModel = new User(require_once __DIR__ . '/../config/db.php');
        $this->taskModel = new Task();
        $this->activityModel = new Activity();
        $this->dashboardModel = new Dashboard();
        $this->currentUser = Auth::user();

        // Check if user is admin
        if (!$this->isAdmin()) {
            $_SESSION['error'] = "Access denied. Admin privileges required.";
            header("Location: ../controller/DashboardController.php?action=index");
            exit;
        }
    }

    // Admin dashboard
    public function index()
    {
        // Get system statistics
        $stats = $this->getSystemStats();

        // Get recent activities
        $recentActivities = $this->activityModel->getRecent(15);

        // Get recent users
        $recentUsers = $this->userModel->getAll();

        // Load admin dashboard view
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/dashboard.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // User management
    public function users()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        // Get all users
        $users = $this->userModel->getAll();
        $totalUsers = $users->num_rows;

        // Convert to array for pagination
        $usersArray = [];
        while ($user = $users->fetch_assoc()) {
            $usersArray[] = $user;
        }

        $totalPages = ceil($totalUsers / $perPage);
        $paginatedUsers = array_slice($usersArray, $offset, $perPage);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/users.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Create user form
    public function createUser()
    {
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/user_form.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Store new user
    public function storeUser()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=users");
            exit;
        }

        $fullName = htmlspecialchars(trim($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $roleName = $_POST['role'] ?? 'member';
        $status = $_POST['status'] ?? 'active';

        // Validate input
        if (!$fullName || !$email || !$password) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: ?action=create_user");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: ?action=create_user");
            exit;
        }

        // Password validation
        $passwordErrors = [];
        if (strlen($password) < 8) $passwordErrors[] = "at least 8 characters";
        if (!preg_match('/[A-Z]/', $password)) $passwordErrors[] = "one uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $passwordErrors[] = "one lowercase letter";
        if (!preg_match('/[0-9]/', $password)) $passwordErrors[] = "one number";
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) $passwordErrors[] = "one special character";

        if (!empty($passwordErrors)) {
            $_SESSION['error'] = "Password must contain " . implode(', ', $passwordErrors) . ".";
            header("Location: ?action=create_user");
            exit;
        }

        // Validate role
        $validRoles = ['admin', 'manager', 'member'];
        if (!in_array($roleName, $validRoles)) {
            $_SESSION['error'] = "Invalid role selected.";
            header("Location: ?action=create_user");
            exit;
        }

        if ($this->userModel->create($fullName, $email, $password, $roleName)) {
            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                null,
                'user_registered',
                "Created user account for: $fullName ($email)"
            );

            $_SESSION['success'] = "User created successfully!";
            header("Location: ?action=users");
        } else {
            $_SESSION['error'] = "Email already exists or user creation failed.";
            header("Location: ?action=create_user");
        }
        exit;
    }

    // Edit user form
    public function editUser($userId)
    {
        $user = $this->userModel->getById($userId);

        if (!$user) {
            $_SESSION['error'] = "User not found.";
            header("Location: ?action=users");
            exit;
        }

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/user_form.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Update user
    public function updateUser($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=users");
            exit;
        }

        $user = $this->userModel->getById($userId);

        if (!$user) {
            $_SESSION['error'] = "User not found.";
            header("Location: ?action=users");
            exit;
        }

        $fullName = htmlspecialchars(trim($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $roleName = $_POST['role'] ?? 'member';
        $status = $_POST['status'] ?? 'active';
        $changePassword = isset($_POST['change_password']);
        $newPassword = $_POST['new_password'] ?? '';

        // Validate input
        if (!$fullName || !$email) {
            $_SESSION['error'] = "Name and email are required.";
            header("Location: ?action=edit_user&id=$userId");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: ?action=edit_user&id=$userId");
            exit;
        }

        // Validate role
        $validRoles = ['admin', 'manager', 'member'];
        if (!in_array($roleName, $validRoles)) {
            $_SESSION['error'] = "Invalid role selected.";
            header("Location: ?action=edit_user&id=$userId");
            exit;
        }

        // Handle password change
        if ($changePassword && !empty($newPassword)) {
            $passwordErrors = [];
            if (strlen($newPassword) < 8) $passwordErrors[] = "at least 8 characters";
            if (!preg_match('/[A-Z]/', $newPassword)) $passwordErrors[] = "one uppercase letter";
            if (!preg_match('/[a-z]/', $newPassword)) $passwordErrors[] = "one lowercase letter";
            if (!preg_match('/[0-9]/', $newPassword)) $passwordErrors[] = "one number";
            if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) $passwordErrors[] = "one special character";

            if (!empty($passwordErrors)) {
                $_SESSION['error'] = "New password must contain " . implode(', ', $passwordErrors) . ".";
                header("Location: ?action=edit_user&id=$userId");
                exit;
            }
        }

        // Update user
        if ($this->userModel->update($userId, $fullName, $email, $roleName)) {
            // Change password if requested
            if ($changePassword && !empty($newPassword)) {
                $this->userModel->changePassword($userId, $newPassword);
            }

            // Log activity
            $this->activityModel->log(
                $this->currentUser['id'],
                null,
                'user_updated',
                "Updated user: $fullName ($email)"
            );

            $_SESSION['success'] = "User updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update user.";
        }

        header("Location: ?action=users");
        exit;
    }

    // Delete user
    public function deleteUser($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?action=users");
            exit;
        }

        $user = $this->userModel->getById($userId);

        if (!$user) {
            $_SESSION['error'] = "User not found.";
            header("Location: ?action=users");
            exit;
        }

        // Prevent deleting self
        if ($userId == $this->currentUser['id']) {
            $_SESSION['error'] = "You cannot delete your own account.";
            header("Location: ?action=users");
            exit;
        }

        // Note: In a real application, you'd want to handle cascading deletes
        // or soft deletes. For now, we'll assume the database handles this.

        // Log activity before deletion
        $this->activityModel->log(
            $this->currentUser['id'],
            null,
            'user_deleted',
            "Deleted user account: {$user['full_name']} ({$user['email']})"
        );

        // Delete user (this would need to be implemented in User model)
        // For now, we'll just redirect with a message
        $_SESSION['success'] = "User deletion not implemented yet.";
        header("Location: ?action=users");
        exit;
    }

    // Task management overview
    public function tasks()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        // Get all tasks for admin view
        $tasks = $this->taskModel->all([], $perPage, $offset);
        $totalTasks = $this->taskModel->getCount();
        $totalPages = ceil($totalTasks / $perPage);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/tasks.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // System settings (placeholder)
    public function settings()
    {
        $settings = [
            'site_name' => 'Task Management System',
            'allow_registration' => true,
            'default_role' => 'member',
            'items_per_page' => ITEMS_PER_PAGE,
            'max_file_size' => MAX_FILE_SIZE
        ];

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/settings.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Activity logs
    public function activityLogs()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        $activities = $this->activityModel->getAll($perPage, $offset);
        $totalActivities = $this->activityModel->getCount();
        $totalPages = ceil($totalActivities / $perPage);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/admin/activity_logs.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Private helper methods

    private function isAdmin()
    {
        // This is a simplified check - in real app you'd cache user role
        $sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$this->currentUser['id']]);
        $result = $db->getRow();
        return ($result['name'] ?? '') === 'admin';
    }

    private function getSystemStats()
    {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'total_activities' => 0,
            'storage_used' => 0
        ];

        // User statistics
        $users = $this->userModel->getAll();
        $stats['total_users'] = $users->num_rows;

        // Count active users
        $activeUsers = 0;
        mysqli_data_seek($users, 0); // Reset result pointer
        while ($user = $users->fetch_assoc()) {
            if ($user['status'] === 'active') {
                $activeUsers++;
            }
        }
        $stats['active_users'] = $activeUsers;

        // Task statistics
        $taskStats = $this->taskModel->getStatistics();
        $stats['total_tasks'] = $taskStats['total'] ?? 0;

        // Activity count
        $stats['total_activities'] = $this->activityModel->getCount();

        return $stats;
    }
}

// Handle routing
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

$controller = new AdminController();

switch ($action) {
    case 'index':
        $controller->index();
        break;

    case 'users':
        $controller->users();
        break;

    case 'create_user':
        $controller->createUser();
        break;

    case 'store_user':
        $controller->storeUser();
        break;

    case 'edit_user':
        if ($id) {
            $controller->editUser($id);
        } else {
            header("Location: ?action=users");
        }
        break;

    case 'update_user':
        if ($id) {
            $controller->updateUser($id);
        } else {
            header("Location: ?action=users");
        }
        break;

    case 'delete_user':
        if ($id) {
            $controller->deleteUser($id);
        } else {
            header("Location: ?action=users");
        }
        break;

    case 'tasks':
        $controller->tasks();
        break;

    case 'settings':
        $controller->settings();
        break;

    case 'activity_logs':
        $controller->activityLogs();
        break;

    default:
        $controller->index();
        break;
}
