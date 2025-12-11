<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Dashboard.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class DashboardController
{
    private $taskModel;
    private $activityModel;
    private $dashboardModel;
    private $currentUser;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->activityModel = new Activity();
        $this->dashboardModel = new Dashboard();
        $this->currentUser = Auth::user();
    }

    // Show main dashboard
    public function index()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);

        // Get dashboard statistics
        $stats = $this->dashboardModel->getStatistics($this->currentUser['id'], $userRole);

        // Get recent tasks
        $recentTasks = $this->taskModel->getRecent(5, $this->currentUser['id'], $userRole);

        // Get recent activities
        $recentActivities = $this->activityModel->getRecent(10, $this->currentUser['id'], $userRole);

        // Get overdue tasks
        $overdueTasks = $this->taskModel->getOverdue($this->currentUser['id'], $userRole, 5);

        // Get priority distribution
        $priorityStats = $this->taskModel->getPriorityDistribution($this->currentUser['id'], $userRole);

        // Load dashboard views
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/dashboard/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Get statistics data (for AJAX if needed)
    public function getStats()
    {
        header('Content-Type: application/json');

        $userRole = $this->getUserRole($this->currentUser['id']);
        $stats = $this->dashboardModel->getStatistics($this->currentUser['id'], $userRole);

        echo json_encode($stats);
        exit;
    }

    // Get recent activities data
    public function getRecentActivities()
    {
        header('Content-Type: application/json');

        $userRole = $this->getUserRole($this->currentUser['id']);
        $activities = $this->activityModel->getRecent(10, $this->currentUser['id'], $userRole);

        echo json_encode($activities);
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
        return strtolower($result['name'] ?? 'member');
    }
}

// Handle routing
$action = $_GET['action'] ?? 'index';

$controller = new DashboardController();

switch ($action) {
    case 'index':
        $controller->index();
        break;

    case 'get_stats':
        $controller->getStats();
        break;

    case 'get_recent_activities':
        $controller->getRecentActivities();
        break;

    default:
        $controller->index();
        break;
}
