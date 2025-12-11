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

        if ($format === 'csv') {
            $this->exportTaskReportCSV($reportData);
        } else {
            $this->showTaskReport($reportData);
        }
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

        if ($format === 'csv') {
            $this->exportUserReportCSV($reportData);
        } else {
            $this->showUserReport($reportData);
        }
    }

    // Generate productivity report
    public function generateProductivityReport()
    {
        $userRole = $this->getUserRole($this->currentUser['id']);
        
        // Check format first
        $format = $_GET['format'] ?? 'html';

        $filters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'period' => $_GET['period'] ?? 'monthly'
        ];

        // Get productivity data
        $productivityData = $this->getProductivityData($filters, $userRole);

        $reportData = [
            'title' => 'Productivity Report',
            'generated_at' => date('Y-m-d H:i:s'),
            'generated_by' => $this->currentUser['name'],
            'filters' => $filters,
            'data' => $productivityData
        ];

        if ($format === 'csv') {
            $this->exportProductivityReportCSV($reportData);
        } else {
            $this->showProductivityReport($reportData);
        }
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

        if ($format === 'csv') {
            $this->exportOverdueReportCSV($reportData);
        } else {
            $this->showOverdueReport($reportData);
        }
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

    // Show productivity report
    private function showProductivityReport($reportData)
    {
        extract($reportData);
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/reports/productivity_report.php';
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

    // Export task report to CSV
    private function exportTaskReportCSV($reportData)
    {
        // Clean any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="task_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        fputcsv($output, ['Task ID', 'Title', 'Description', 'Status', 'Priority', 'Category', 'Assigned To', 'Created By', 'Deadline', 'Created At']);

        // CSV data
        $tasks = $reportData['tasks'] ?? [];
        if (is_array($tasks) && !empty($tasks)) {
            foreach ($tasks as $task) {
                if (!is_array($task)) continue;
                
                fputcsv($output, [
                    $task['id'] ?? '',
                    $task['title'] ?? '',
                    $task['description'] ?? '',
                    $task['status'] ?? '',
                    $task['priority_name'] ?? '',
                    $task['category_name'] ?? '',
                    $task['assignee_name'] ?? '',
                    $task['creator_name'] ?? '',
                    $task['deadline'] ?? '',
                    $task['created_at'] ?? ''
                ]);
            }
        }

        fclose($output);
        exit;
    }

    // Export user report to CSV
    private function exportUserReportCSV($reportData)
    {
        // Clean any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="user_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        fputcsv($output, ['User ID', 'Name', 'Email', 'Role', 'Tasks Created', 'Tasks Assigned', 'Tasks Completed', 'Comments Made', 'Files Uploaded', 'Last Activity']);

        // CSV data
        $users = $reportData['users'] ?? [];
        if (is_array($users) && !empty($users)) {
            foreach ($users as $user) {
                if (!is_array($user)) continue;
                
                fputcsv($output, [
                    $user['id'] ?? '',
                    $user['full_name'] ?? '',
                    $user['email'] ?? '',
                    $user['role'] ?? '',
                    $user['tasks_created'] ?? 0,
                    $user['tasks_assigned'] ?? 0,
                    $user['tasks_completed'] ?? 0,
                    $user['comments_count'] ?? 0,
                    $user['attachments_count'] ?? 0,
                    $user['last_activity'] ?? ''
                ]);
            }
        }

        fclose($output);
        exit;
    }

    // Export productivity report to CSV
    private function exportProductivityReportCSV($reportData)
    {
        // Clean any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="productivity_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        fputcsv($output, ['Period', 'Tasks Created', 'Tasks Completed', 'Completion Rate', 'Average Tasks per Day']);

        // CSV data
        $data = $reportData['data'] ?? [];
        if (is_array($data) && !empty($data)) {
            foreach ($data as $period => $metrics) {
                if (!is_array($metrics)) continue;
                
                fputcsv($output, [
                    $period,
                    $metrics['created'] ?? 0,
                    $metrics['completed'] ?? 0,
                    $metrics['completion_rate'] ?? 0,
                    $metrics['avg_per_day'] ?? 0
                ]);
            }
        }

        fclose($output);
        exit;
    }

    // Export overdue report to CSV
    private function exportOverdueReportCSV($reportData)
    {
        // Clean any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="overdue_report_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        fputcsv($output, ['Task ID', 'Title', 'Assigned To', 'Created By', 'Deadline', 'Days Overdue']);

        // CSV data
        $tasks = $reportData['tasks'] ?? [];
        if (is_array($tasks) && !empty($tasks)) {
            foreach ($tasks as $task) {
                if (!is_array($task)) continue;
                
                $daysOverdue = floor((time() - strtotime($task['deadline'])) / (60 * 60 * 24));

                fputcsv($output, [
                    $task['id'] ?? '',
                    $task['title'] ?? '',
                    $task['assignee_name'] ?? '',
                    $task['creator_name'] ?? '',
                    $task['deadline'] ?? '',
                    $daysOverdue
                ]);
            }
        }

        fclose($output);
        exit;
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

    private function getProductivityData($filters, $userRole)
    {
        $data = [];

        // This is a simplified implementation
        // In a real application, you'd calculate productivity metrics over time

        $dateFrom = strtotime($filters['date_from']);
        $dateTo = strtotime($filters['date_to']);

        // Generate sample data (replace with real calculations)
        $currentDate = $dateFrom;
        while ($currentDate <= $dateTo) {
            $dateStr = date('Y-m-d', $currentDate);

            // Get tasks created on this date
            $createdTasks = $this->getTasksCreatedOnDate($dateStr, $this->currentUser['id'], $userRole);

            // Get tasks completed on this date
            $completedTasks = $this->getTasksCompletedOnDate($dateStr, $this->currentUser['id'], $userRole);

            $data[$dateStr] = [
                'created' => $createdTasks,
                'completed' => $completedTasks,
                'completion_rate' => $createdTasks > 0 ? round(($completedTasks / $createdTasks) * 100, 1) : 0,
                'avg_per_day' => 1 // Simplified
            ];

            $currentDate = strtotime('+1 day', $currentDate);
        }

        return $data;
    }

    private function getTasksCreatedOnDate($date, $userId, $userRole)
    {
        $sql = "SELECT COUNT(*) as count FROM tasks WHERE DATE(created_at) = ?";
        $params = [$date];

        if ($userRole !== 'admin' && $userRole !== 'manager') {
            $sql .= " AND created_by = ?";
            $params[] = $userId;
        }

        $db = new Database();
        $db->prepare($sql);
        $db->execute($params);
        $result = $db->getRow();
        return $result['count'] ?? 0;
    }

    private function getTasksCompletedOnDate($date, $userId, $userRole)
    {
        $sql = "SELECT COUNT(*) as count FROM tasks WHERE DATE(updated_at) = ? AND status = 'completed'";
        $params = [$date];

        if ($userRole !== 'admin' && $userRole !== 'manager') {
            $sql .= " AND (assigned_to = ? OR created_by = ?)";
            $params = array_merge($params, [$userId, $userId]);
        }

        $db = new Database();
        $db->prepare($sql);
        $db->execute($params);
        $result = $db->getRow();
        return $result['count'] ?? 0;
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

    case 'productivity_report':
        $controller->generateProductivityReport();
        break;

    case 'overdue_report':
        $controller->generateOverdueReport();
        break;

    default:
        $controller->index();
        break;
}
