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


    // Get recent activities
    public function getRecent($limit = 10, $user_id = null, $user_role = null)
    {
        $sql = "SELECT a.*, u.full_name, u.email, t.title as task_title
                FROM $this->table a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN tasks t ON a.task_id = t.id
                WHERE 1=1";

        $params = [];

        // ALWAYS restrict to user's activities as per user request
        if ($user_id) {
            $sql .= " AND a.user_id = ?";
            $params[] = $user_id;
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT ?";
        $params[] = $limit;

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
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
}
