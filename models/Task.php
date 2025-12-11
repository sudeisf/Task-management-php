<?php

require_once __DIR__ . '/../core/Database.php';

class Task
{
    private $db;
    private $table = "tasks";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Create task with enhanced fields
    public function create($data)
    {
        $sql = "INSERT INTO $this->table
                (project_id, title, description, category_id, priority_id, status, deadline, created_by, assigned_to)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->prepare($sql);
        $params = [
            $data['project_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['category_id'] ?? null,
            $data['priority_id'] ?? 2, // medium by default
            $data['status'] ?? 'todo',
            $data['deadline'] ?? null,
            $data['created_by'],
            $data['assigned_to'] ?? null
        ];

        if ($this->db->execute($params)) {
            return $this->db->getLastInsertId();
        }
        return false;
    }

    // Update task with enhanced fields
    public function update($id, $data)
    {
        $sql = "UPDATE $this->table SET
                title=?, description=?, category_id=?, priority_id=?, status=?, deadline=?, assigned_to=?
                WHERE id=?";

        $this->db->prepare($sql);
        $params = [
            $data['title'],
            $data['description'] ?? null,
            $data['category_id'] ?? null,
            $data['priority_id'] ?? 2,
            $data['status'] ?? 'todo',
            $data['deadline'] ?? null,
            $data['assigned_to'] ?? null,
            $id
        ];

        return $this->db->execute($params);
    }

    // Update task status only
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE $this->table SET status=? WHERE id=?";
        $this->db->prepare($sql);
        return $this->db->execute([$status, $id]);
    }

    // Delete task
    public function delete($id)
    {
        $sql = "DELETE FROM $this->table WHERE id=?";
        $this->db->prepare($sql);
        return $this->db->execute([$id]);
    }

    // Get task by ID with full details
    public function find($id)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       proj.name as project_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN projects proj ON t.project_id = proj.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE t.id=?";

        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
    }

    // Get tasks for a specific user (assigned to them)
    public function assignedTo($user_id, $filters = [], $limit = null, $offset = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE t.assigned_to=?";

        $params = [$user_id];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND t.status=?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority_id'])) {
            $sql .= " AND t.priority_id=?";
            $params[] = $filters['priority_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id=?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['deadline_from'])) {
            $sql .= " AND t.deadline >= ?";
            $params[] = $filters['deadline_from'];
        }

        if (!empty($filters['deadline_to'])) {
            $sql .= " AND t.deadline <= ?";
            $params[] = $filters['deadline_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $params[] = "%" . $filters['search'] . "%";
            $params[] = "%" . $filters['search'] . "%";
        }

        $sql .= " ORDER BY t.created_at DESC";

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

    // Get tasks created by a specific user
    public function createdBy($user_id, $limit = null, $offset = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE t.created_by=?
                ORDER BY t.created_at DESC";

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

    // Get all tasks (admin/manager view)
    public function all($filters = [], $limit = null, $offset = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND t.status=?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority_id'])) {
            $sql .= " AND t.priority_id=?";
            $params[] = $filters['priority_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id=?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to=?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND t.created_by=?";
            $params[] = $filters['created_by'];
        }

        if (!empty($filters['deadline_from'])) {
            $sql .= " AND t.deadline >= ?";
            $params[] = $filters['deadline_from'];
        }

        if (!empty($filters['deadline_to'])) {
            $sql .= " AND t.deadline <= ?";
            $params[] = $filters['deadline_to'];
        }

        $sql .= " ORDER BY t.created_at DESC";

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

    // Search tasks
    public function search($query, $user_id = null, $user_role = null, $limit = null, $offset = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE (t.title LIKE ? OR t.description LIKE ?)";

        $params = ["%$query%", "%$query%"];

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to=? OR t.created_by=?)";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        $sql .= " ORDER BY t.created_at DESC";

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

    // Get tasks statistics
    public function getStatistics($user_id = null, $user_role = null)
    {
        $stats = [
            'total' => 0,
            'todo' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'due_today' => 0,
            'due_this_week' => 0
        ];

        // Base query
        $base_sql = "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status='todo' THEN 1 ELSE 0 END) as todo,
            SUM(CASE WHEN status='in_progress' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN deadline < CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as overdue,
            SUM(CASE WHEN deadline = CURDATE() AND status != 'completed' THEN 1 ELSE 0 END) as due_today,
            SUM(CASE WHEN deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'completed' THEN 1 ELSE 0 END) as due_this_week
            FROM $this->table WHERE 1=1";

        $params = [];

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $base_sql .= " AND (assigned_to=? OR created_by=?)";
            $params = [$user_id, $user_id];
        }

        $this->db->prepare($base_sql);
        $this->db->execute($params);
        $result = $this->db->getRow();

        return $result ?: $stats;
    }

    // Get tasks by priority distribution
    public function getPriorityDistribution($user_id = null, $user_role = null)
    {
        $sql = "SELECT p.name, COUNT(t.id) as count
                FROM priority_levels p
                LEFT JOIN $this->table t ON p.id = t.priority_id AND t.status != 'completed'";

        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to=? OR t.created_by=?)";
            $params = [$user_id, $user_id];
        }

        $sql .= " GROUP BY p.id, p.name ORDER BY p.weight DESC";

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get recent tasks
    public function getRecent($limit = 5, $user_id = null, $user_role = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE 1=1";

        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to=? OR t.created_by=?)";
            $params = [$user_id, $user_id];
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT ?";
        $params[] = $limit;

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get tasks count for pagination
    public function getCount($filters = [], $user_id = null, $user_role = null)
    {
        $sql = "SELECT COUNT(*) as count FROM $this->table WHERE 1=1";
        $params = [];

        // Apply same filters as in all() method
        if (!empty($filters['status'])) {
            $sql .= " AND status=?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority_id'])) {
            $sql .= " AND priority_id=?";
            $params[] = $filters['priority_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND category_id=?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND assigned_to=?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND created_by=?";
            $params[] = $filters['created_by'];
        }

        if (!empty($filters['deadline_from'])) {
            $sql .= " AND deadline >= ?";
            $params[] = $filters['deadline_from'];
        }

        if (!empty($filters['deadline_to'])) {
            $sql .= " AND deadline <= ?";
            $params[] = $filters['deadline_to'];
        }

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (assigned_to=? OR created_by=?)";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    // Get overdue tasks
    public function getOverdue($user_id = null, $user_role = null, $limit = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                WHERE t.deadline < CURDATE() AND t.status != 'completed'";

        $params = [];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to=? OR t.created_by=?)";
            $params = [$user_id, $user_id];
        }

        $sql .= " ORDER BY t.deadline ASC";

        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get tasks by project
    public function getByProject($projectId, $filters = [], $limit = null, $offset = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM $this->table t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE t.project_id = ?";

        $params = [$projectId];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND t.status=?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority_id'])) {
            $sql .= " AND t.priority_id=?";
            $params[] = $filters['priority_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to=?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ?)";
            $params[] = "%" . $filters['search'] . "%";
            $params[] = "%" . $filters['search'] . "%";
        }

        $sql .= " ORDER BY t.created_at DESC";

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
}
