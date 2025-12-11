<?php

require_once __DIR__ . '/../core/Database.php';

class Comment
{
    private $db;
    private $table = "comments";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Create new comment
    public function create($data)
    {
        $sql = "INSERT INTO $this->table (task_id, user_id, comment) VALUES (?, ?, ?)";
        $this->db->prepare($sql);
        $params = [$data['task_id'], $data['user_id'], $data['comment']];

        if ($this->db->execute($params)) {
            return $this->db->getLastInsertId();
        }

        return false;
    }

    // Get comment by ID
    public function find($id)
    {
        $sql = "SELECT c.*, u.full_name, u.email, u.profile_picture
                FROM $this->table c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.id = ?";

        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
    }

    // Get comments by task ID with pagination
    public function getByTask($task_id, $limit = null, $offset = null)
    {
        $sql = "SELECT c.*, u.full_name, u.email, u.profile_picture
                FROM $this->table c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.task_id = ?
                ORDER BY c.created_at DESC";

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

    // Get comments by user ID
    public function getByUser($user_id, $limit = null, $offset = null)
    {
        $sql = "SELECT c.*, t.title as task_title, u.full_name, u.email
                FROM $this->table c
                LEFT JOIN tasks t ON c.task_id = t.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.user_id = ?
                ORDER BY c.created_at DESC";

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

    // Update comment
    public function update($id, $comment, $user_id)
    {
        $sql = "UPDATE $this->table SET comment = ? WHERE id = ? AND user_id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$comment, $id, $user_id]);
    }

    // Delete comment
    public function delete($id, $user_id = null)
    {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $params = [$id];

        // If user_id is provided, ensure user can only delete their own comments
        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }

        $this->db->prepare($sql);
        return $this->db->execute($params);
    }

    // Get comment count for a task
    public function getCountByTask($task_id)
    {
        $sql = "SELECT COUNT(*) as count FROM $this->table WHERE task_id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$task_id]);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    // Get recent comments across all tasks
    public function getRecent($limit = 10, $user_id = null, $user_role = null)
    {
        $sql = "SELECT c.*, t.title as task_title, u.full_name, u.email, u.profile_picture
                FROM $this->table c
                LEFT JOIN tasks t ON c.task_id = t.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE 1=1";

        $params = [];

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to = ? OR t.created_by = ? OR c.user_id = ?)";
            $params = [$user_id, $user_id, $user_id];
        }

        $sql .= " ORDER BY c.created_at DESC LIMIT ?";
        $params[] = $limit;

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Search comments
    public function search($query, $user_id = null, $user_role = null, $limit = null, $offset = null)
    {
        $sql = "SELECT c.*, t.title as task_title, u.full_name, u.email
                FROM $this->table c
                LEFT JOIN tasks t ON c.task_id = t.id
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.comment LIKE ?
                AND t.id IS NOT NULL"; // Ensure task still exists

        $params = ["%$query%"];

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to = ? OR t.created_by = ? OR c.user_id = ?)";
            $params = array_merge($params, [$user_id, $user_id, $user_id]);
        }

        $sql .= " ORDER BY c.created_at DESC";

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

    // Check if user can edit/delete comment
    public function canModify($comment_id, $user_id, $user_role)
    {
        if ($user_role === 'admin' || $user_role === 'manager') {
            return true;
        }

        $comment = $this->find($comment_id);
        return $comment && $comment['user_id'] == $user_id;
    }

    // Get comment statistics
    public function getStatistics($user_id = null, $user_role = null)
    {
        $stats = [
            'total_comments' => 0,
            'today_comments' => 0,
            'week_comments' => 0,
            'avg_comments_per_task' => 0
        ];

        // Base query
        $baseSql = "SELECT COUNT(*) as count FROM $this->table WHERE 1=1";
        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $baseSql .= " AND user_id = ?";
            $params = [$user_id];
        }

        // Total comments
        $this->db->prepare($baseSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['total_comments'] = $result['count'] ?? 0;

        // Today's comments
        $todaySql = $baseSql . " AND DATE(created_at) = CURDATE()";
        $this->db->prepare($todaySql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['today_comments'] = $result['count'] ?? 0;

        // This week's comments
        $weekSql = $baseSql . " AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
        $this->db->prepare($weekSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['week_comments'] = $result['count'] ?? 0;

        return $stats;
    }
}
