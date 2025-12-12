<?php

require_once __DIR__ . '/../core/Database.php';

class Activity
{
    private $db;
    private $table = "activity_logs";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Log activity
    public function log($user_id, $task_id, $action, $details = null)
    {
        $sql = "INSERT INTO $this->table (user_id, task_id, action, details) VALUES (?, ?, ?, ?)";

        $this->db->prepare($sql);
        return $this->db->execute([$user_id, $task_id, $action, $details]);
    }

    // Get all activities with pagination
    public function getAll($limit = null, $offset = null, $filters = [])
    {
        $sql = "SELECT a.*, u.full_name, u.email, t.title as task_title
                FROM $this->table a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['task_id'])) {
            $sql .= " AND a.task_id = ?";
            $params[] = $filters['task_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND a.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $sql .= " ORDER BY a.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get activities for a specific user
    public function getByUser($user_id, $limit = null, $offset = null)
    {
        $sql = "SELECT a.*, t.title as task_title
                FROM $this->table a
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE a.user_id = ?
                ORDER BY a.created_at DESC";

        $params = [$user_id];

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get activities for a specific task
    public function getByTask($task_id, $limit = null, $offset = null)
    {
        $sql = "SELECT a.*, u.full_name, u.email
                FROM $this->table a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.task_id = ?
                ORDER BY a.created_at DESC";

        $params = [$task_id];

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get recent activities
    public function getRecent($limit = 10, $user_id = null, $user_role = null)
    {
        $sql = "SELECT a.*, u.full_name, u.email, t.title as task_title
                FROM $this->table a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE 1=1";

        $params = [];

        // Restrict to user's activities if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND a.user_id = ?";
            $params[] = $user_id;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ?";
        $params[] = $limit;

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get activity statistics
    public function getStatistics($user_id = null, $user_role = null, $period = '30')
    {
        $stats = [
            'total_activities' => 0,
            'today_activities' => 0,
            'week_activities' => 0,
            'by_action' => [],
            'by_user' => []
        ];

        // Base query
        $baseSql = "SELECT COUNT(*) as count FROM $this->table WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)";
        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $baseSql .= " AND user_id = ?";
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

        // Activities by action type
        $actionSql = "SELECT action, COUNT(*) as count FROM $this->table WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)";
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $actionSql .= " AND user_id = ?";
        }
        $actionSql .= " GROUP BY action ORDER BY count DESC";

        $this->db->prepare($actionSql);
        $this->db->execute($params);
        $result = $this->db->getResult();
        while ($row = $result->fetch_assoc()) {
            $stats['by_action'][$row['action']] = $row['count'];
        }

        // Most active users (only for admin/manager)
        if ($user_role === 'admin' || $user_role === 'manager') {
            $userSql = "SELECT u.full_name, COUNT(a.id) as count
                        FROM $this->table a
                        LEFT JOIN users u ON a.user_id = u.id
                        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
                        GROUP BY a.user_id, u.full_name
                        ORDER BY count DESC LIMIT 10";

            $this->db->prepare($userSql);
            $this->db->execute();
            $result = $this->db->getResult();
            while ($row = $result->fetch_assoc()) {
                $stats['by_user'][$row['full_name']] = $row['count'];
            }
        }

        return $stats;
    }

    // Get activity count
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM $this->table WHERE 1=1";
        $params = [];

        // Apply same filters as in getAll() method
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['task_id'])) {
            $sql .= " AND task_id = ?";
            $params[] = $filters['task_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= " AND action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    // Search activities
    public function search($query, $user_id = null, $user_role = null, $limit = null, $offset = null)
    {
        $sql = "SELECT a.*, u.full_name, u.email, t.title as task_title
                FROM $this->table a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE (a.action LIKE ? OR a.details LIKE ? OR u.full_name LIKE ? OR t.title LIKE ?)";

        $params = ["%$query%", "%$query%", "%$query%", "%$query%"];

        // Restrict to user's activities if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND a.user_id = ?";
            $params[] = $user_id;
        }

        $sql .= " ORDER BY a.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get activity feed for dashboard
    public function getActivityFeed($user_id = null, $user_role = null, $limit = 20)
    {
        $sql = "SELECT a.*, u.full_name, u.email, u.profile_picture,
                       t.title as task_title, t.status as task_status
                FROM $this->table a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE 1=1";

        $params = [];

        // Restrict to user's tasks and activities if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (a.user_id = ? OR (t.assigned_to = ? OR t.created_by = ?))";
            $params = [$user_id, $user_id, $user_id];
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ?";
        $params[] = $limit;

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get action types for filtering
    public function getActionTypes()
    {
        $actions = [
            'task_created' => 'Task Created',
            'task_updated' => 'Task Updated',
            'task_completed' => 'Task Completed',
            'task_deleted' => 'Task Deleted',
            'comment_added' => 'Comment Added',
            'comment_updated' => 'Comment Updated',
            'comment_deleted' => 'Comment Deleted',
            'file_uploaded' => 'File Uploaded',
            'file_deleted' => 'File Deleted',
            'user_login' => 'User Login',
            'user_logout' => 'User Logout',
            'user_registered' => 'User Registered',
            'user_updated' => 'User Updated'
        ];

        return $actions;
    }

    // Clean old activities (for maintenance)
    public function cleanOldActivities($days = 365)
    {
        $sql = "DELETE FROM $this->table WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $this->db->prepare($sql);
        return $this->db->execute([$days]);
    }
}
