<?php

// Start output buffering to prevent any accidental output before CSV headers
ob_start();

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Dashboard.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class ReportController
{
    private $taskModel;
    private $userModel;
    private $activityModel;
    private $dashboardModel;
    private $currentUser;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->userModel = new User(require_once __DIR__ . '/../config/db.php');
        $this->activityModel = new Activity();
        $this->dashboardModel = new Dashboard();
        $this->currentUser = Auth::user();
    }

    // Reports index page
    public function index()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/reports/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Generate task report
    public function generateTaskReport()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);
        
        // Check format first - if CSV, we need to export immediately before any output
        $format = $_GET['format'] ?? 'html';

        // Get filters from request
        $filters = [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'status' => $_GET['status'] ?? null,
            'priority_id' => $_GET['priority_id'] ?? null,
            'category_id' => $_GET['category_id'] ?? null,
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'created_by' => $_GET['created_by'] ?? null
        ];

        // Get tasks based on role
        if ($userRole === 'admin' || $userRole === 'manager') {
            $tasks = $this->taskModel->all($filters);
        } else {
            $tasks = $this->taskModel->all($filters, null, null, $this->currentUser['id'], $userRole);
        }

        // Calculate statistics
        $stats = $this->calculateTaskStats($tasks, $filters);

        $reportData = [
            'title' => 'Task Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'filters' => $filters,
            'tasks' => $tasks,
            'statistics' => $stats
        ];

        $this->showTaskReport($reportData);
    }

    // Generate user activity report
    public function generateUserReport()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);
        
        // Check format first
        $format = $_GET['format'] ?? 'html';

        // Only admins and managers can view user reports
        if ($userRole !== 'admin' && $userRole !== 'manager') {
            $_SESSION['error'] = "Access denied.";
            header("Location: ?action=index");
            exit;
        }

        $filters = [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'user_id' => $_GET['user_id'] ?? null
        ];

        // Get users and their activity
        $users = $this->getUserActivityData($filters);

        // Calculate statistics
        $stats = $this->calculateUserStats($users, $filters);

        $reportData = [
            'title' => 'User Activity Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'filters' => $filters,
            'users' => $users,
            'statistics' => $stats
        ];

        $this->showUserReport($reportData);
    }


    // Generate overdue tasks report
    public function generateOverdueReport()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);
        
        // Check format first
        $format = $_GET['format'] ?? 'html';

        $overdueTasks = $this->taskModel->getOverdue($this->currentUser['id'], $userRole);

        $reportData = [
            'title' => 'Overdue Tasks Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'tasks' => $overdueTasks,
            'total_overdue' => is_array($overdueTasks) ? count($overdueTasks) : 0
        ];

        $this->showOverdueReport($reportData);
    }

    // Show task report
    private function showTaskReport($reportData)
    {
        extract($reportData);
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/reports/task_report.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Show user report
    private function showUserReport($reportData)
    {
        extract($reportData);
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/reports/user_report.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }


    // Show overdue report
    private function showOverdueReport($reportData)
    {
        extract($reportData);
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/reports/overdue_report.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }


    // Private helper methods

    private function getUserRole($userId)
    {
        $sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$userId]);
        $result = $db->getRow();
        return $result['name'] ?? 'member';
    }

    private function calculateTaskStats($tasks, $filters)
    {
        $stats = [
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'todo_tasks' => 0,
            'overdue_tasks' => 0,
            'completion_rate' => 0
        ];

        $totalTasks = is_array($tasks) ? count($tasks) : 0;
        $stats['total_tasks'] = $totalTasks;

        if ($totalTasks > 0) {
            foreach ($tasks as $task) {
                switch ($task['status']) {
                    case 'completed':
                        $stats['completed_tasks']++;
                        break;
                    case 'in_progress':
                        $stats['in_progress_tasks']++;
                        break;
                    case 'todo':
                        $stats['todo_tasks']++;
                        break;
                }

                // Check if overdue
                if ($task['deadline'] && strtotime($task['deadline']) < time() && $task['status'] !== 'completed') {
                    $stats['overdue_tasks']++;
                }
            }

            $stats['completion_rate'] = round(($stats['completed_tasks'] / $totalTasks) * 100, 1);
        }

        return $stats;
    }

    private function getUserActivityData($filters)
    {
        $users = [];

        // Get all users
        $allUsers = $this->userModel->getAll();

        while ($user = $allUsers->fetch_assoc()) {
            // Get user statistics
            $userStats = $this->getUserStats($user['id'], $filters);
            $users[] = array_merge($user, $userStats);
        }

        return $users;
    }

    private function getUserStats($userId, $filters)
    {
        $stats = [
            'tasks_created' => 0,
            'tasks_assigned' => 0,
            'tasks_completed' => 0,
            'comments_count' => 0,
            'attachments_count' => 0,
            'last_activity' => null
        ];

        // Tasks created
        $createdTasks = $this->taskModel->createdBy($userId);
        $stats['tasks_created'] = is_array($createdTasks) ? count($createdTasks) : 0;

        // Tasks assigned
        $assignedTasks = $this->taskModel->assignedTo($userId);
        $stats['tasks_assigned'] = is_array($assignedTasks) ? count($assignedTasks) : 0;

        // Tasks completed
        $completedCount = 0;
        if (is_array($assignedTasks)) {
            foreach ($assignedTasks as $task) {
                if ($task['status'] === 'completed') {
                    $completedCount++;
                }
            }
        }
        $stats['tasks_completed'] = $completedCount;

        // Comments count
        $comments = $this->getCommentsCount($userId);
        $stats['comments_count'] = $comments;

        // Attachments count
        $attachments = $this->getAttachmentsCount($userId);
        $stats['attachments_count'] = $attachments;

        // Last activity
        $lastActivity = $this->activityModel->getByUser($userId, 1);
        if (is_array($lastActivity) && !empty($lastActivity)) {
            $stats['last_activity'] = $lastActivity[0]['created_at'];
        }

        return $stats;
    }

    private function getCommentsCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM comments WHERE user_id = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$userId]);
        $result = $db->getRow();
        return $result['count'] ?? 0;
    }

    private function getAttachmentsCount($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM attachments WHERE uploaded_by = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$userId]);
        $result = $db->getRow();
        return $result['count'] ?? 0;
    }

    private function calculateUserStats($users, $filters)
    {
        $stats = [
            'total_users' => count($users),
            'active_users' => 0,
            'total_tasks_created' => 0,
            'total_tasks_completed' => 0,
            'total_comments' => 0,
            'total_attachments' => 0
        ];

        foreach ($users as $user) {
            if ($user['status'] === 'active') {
                $stats['active_users']++;
            }
            $stats['total_tasks_created'] += $user['tasks_created'];
            $stats['total_tasks_completed'] += $user['tasks_completed'];
            $stats['total_comments'] += $user['comments_count'];
            $stats['total_attachments'] += $user['attachments_count'];
        }

        return $stats;
    }

}

// Handle routing
$action = $_GET['action'] ?? 'index';

$controller = new ReportController();

switch ($action) {
    case 'index':
        $controller->index();
        break;

    case 'task_report':
        $controller->generateTaskReport();
        break;

    case 'user_report':
        $controller->generateUserReport();
        break;


    case 'overdue_report':
        $controller->generateOverdueReport();
        break;

    default:
        $controller->index();
        break;
}
