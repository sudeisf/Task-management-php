<?php

require_once __DIR__ . '/../core/Database.php';

class Dashboard
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Get dashboard statistics
    public function getStatistics($user_id = null, $user_role = null)
    {
        // Task statistics
        $taskStats = $this->getTaskStatistics($user_id, $user_role);

        // User statistics (for admin/manager)
        $userStats = [];
        if ($user_role === 'admin' || $user_role === 'manager') {
            $userStats = $this->getUserStatistics();
        }

        // Activity statistics
        $activityStats = $this->getActivityStatistics($user_id, $user_role);

        return array_merge($taskStats, $userStats, $activityStats);
    }

    // Get task-related statistics
    private function getTaskStatistics($user_id, $user_role)
    {
        $stats = [
            'total' => 0,
            'todo' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'due_today' => 0,
            'due_this_week' => 0,
            'completion_rate' => 0
        ];

        // Base query for task statistics
        $sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='todo' THEN 1 ELSE 0 END) as todo,
            SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN deadline < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN deadline = CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as due_today,
            SUM(CASE WHEN deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'completed' THEN 1 ELSE 0 END) as due_this_week
            FROM tasks WHERE 1=1";

        $params = [];

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (assigned_to=? OR created_by=?)";
            $params = [$user_id, $user_id];
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        $result = $this->db->getRow();

        if ($result) {
            $stats = array_merge($stats, $result);

            // Calculate completion rate
            $total = (int)$result['total'];
            $completed = (int)$result['completed'];
            $stats['completion_rate'] = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        }

        return $stats;
    }

    // Get user statistics (for admin/manager)
    private function getUserStatistics()
    {
        $stats = [
            'total_users' => 0,
            'active_users' => 0,
            'inactive_users' => 0,
            'admin_users' => 0,
            'manager_users' => 0,
            'member_users' => 0
        ];

        // User statistics
        $sql = "SELECT
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
            SUM(CASE WHEN role_id = (SELECT id FROM roles WHERE name = 'admin') THEN 1 ELSE 0 END) as admin_users,
            SUM(CASE WHEN role_id = (SELECT id FROM roles WHERE name = 'manager') THEN 1 ELSE 0 END) as manager_users,
            SUM(CASE WHEN role_id = (SELECT id FROM roles WHERE name = 'member') THEN 1 ELSE 0 END) as member_users
            FROM users";

        $this->db->prepare($sql);
        $this->db->execute();
        $result = $this->db->getRow();

        if ($result) {
            $stats = array_merge($stats, $result);
        }

        return $stats;
    }

    // Get activity statistics
    private function getActivityStatistics($user_id, $user_role)
    {
        $stats = [
            'total_activities' => 0,
            'today_activities' => 0,
            'week_activities' => 0,
            'recent_comments' => 0,
            'recent_assignments' => 0
        ];

        // Base activity query
        $baseSql = "SELECT COUNT(*) as count FROM activity_logs WHERE 1=1";
        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $baseSql .= " AND user_id=?";
            $params = [$user_id];
        }

        // Total activities
        $this->db->prepare($baseSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['total_activities'] = $result['count'] ?? 0;

        // Today's activities
        $todaySql = $baseSql . " AND DATE(created_at) = CURDATE()";
        $this->db->prepare($todaySql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['today_activities'] = $result['count'] ?? 0;

        // This week's activities
        $weekSql = $baseSql . " AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
        $this->db->prepare($weekSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['week_activities'] = $result['count'] ?? 0;

        // Recent comments count (last 7 days)
        $commentSql = "SELECT COUNT(*) as count FROM comments WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $commentSql .= " AND user_id=?";
        }
        $this->db->prepare($commentSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['recent_comments'] = $result['count'] ?? 0;

        return $stats;
    }

    // Get tasks completion trend (last 30 days)
    public function getCompletionTrend($user_id = null, $user_role = null)
    {
        $sql = "SELECT
            DATE(created_at) as date,
            COUNT(*) as created,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM tasks
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";

        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (assigned_to=? OR created_by=?)";
            $params = [$user_id, $user_id];
        }

        $sql .= " GROUP BY DATE(created_at) ORDER BY date DESC";

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get category distribution
    public function getCategoryDistribution($user_id = null, $user_role = null)
    {
        $sql = "SELECT
            c.name,
            COUNT(t.id) as count
            FROM categories c
            LEFT JOIN tasks t ON c.id = t.category_id AND t.status != 'completed'";

        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to=? OR t.created_by=?)";
            $params = [$user_id, $user_id];
        }

        $sql .= " GROUP BY c.id, c.name ORDER BY count DESC";

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get productivity metrics
    public function getProductivityMetrics($user_id = null, $user_role = null)
    {
        $metrics = [
            'avg_completion_time' => 0,
            'tasks_per_day' => 0,
            'efficiency_score' => 0
        ];

        // This would require more complex queries with activity logs
        // For now, return basic metrics

        return $metrics;
    }
}
