<?php

require_once __DIR__ . '/../core/Database.php';

class Search
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // Advanced search for tasks
    public function searchTasks($query, $filters = [], $user_id = null, $user_role = null, $limit = null, $offset = null)
    {
        $sql = "SELECT t.*,
                       c.name as category_name,
                       p.name as priority_name,
                       creator.full_name as creator_name,
                       assignee.full_name as assignee_name
                FROM tasks t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN priority_levels p ON t.priority_id = p.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users assignee ON t.assigned_to = assignee.id
                WHERE 1=1";

        $params = [];

        // Text search
        if (!empty($query)) {
            $sql .= " AND (t.title LIKE ? OR t.description LIKE ? OR c.name LIKE ? OR creator.full_name LIKE ? OR assignee.full_name LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority_id'])) {
            $sql .= " AND t.priority_id = ?";
            $params[] = $filters['priority_id'];
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = ?";
            $params[] = $filters['assigned_to'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND t.created_by = ?";
            $params[] = $filters['created_by'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND t.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND t.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['deadline_from'])) {
            $sql .= " AND t.deadline >= ?";
            $params[] = $filters['deadline_from'];
        }

        if (!empty($filters['deadline_to'])) {
            $sql .= " AND t.deadline <= ?";
            $params[] = $filters['deadline_to'];
        }

        // Restrict to user's tasks if not admin/manager
        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (t.assigned_to = ? OR t.created_by = ?)";
            $params[] = $user_id;
            $params[] = $user_id;
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtoupper($filters['sort_order'] ?? 'DESC');

        $allowedSortFields = ['created_at', 'updated_at', 'deadline', 'priority_id', 'status', 'title'];
        $allowedSortOrders = ['ASC', 'DESC'];

        if (in_array($sortBy, $allowedSortFields) && in_array($sortOrder, $allowedSortOrders)) {
            if ($sortBy === 'priority_id') {
                $sql .= " ORDER BY p.weight $sortOrder, t.created_at DESC";
            } else {
                $sql .= " ORDER BY t.$sortBy $sortOrder";
            }
        } else {
            $sql .= " ORDER BY t.created_at DESC";
        }

        // Pagination
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
        return $this->db->getResult();
    }

    // Get search suggestions
    public function getSuggestions($query, $type = 'all', $user_id = null, $user_role = null)
    {
        $suggestions = [];

        if (empty($query)) {
            return $suggestions;
        }

        $limit = 5;
        $searchTerm = "$query%";

        // Task title suggestions
        if ($type === 'all' || $type === 'tasks') {
            $sql = "SELECT DISTINCT title as suggestion, 'task' as type FROM tasks WHERE title LIKE ?";
            $params = [$searchTerm];

            if ($user_role !== 'admin' && $user_role !== 'manager') {
                $sql .= " AND (assigned_to = ? OR created_by = ?)";
                $params = array_merge($params, [$user_id, $user_id]);
            }

            $sql .= " ORDER BY title LIMIT ?";

            $this->db->prepare($sql);
            $this->db->execute(array_merge($params, [$limit]));
            $result = $this->db->getResult();

            while ($row = $result->fetch_assoc()) {
                $suggestions[] = $row;
            }
        }

        // User name suggestions
        if ($type === 'all' || $type === 'users') {
            $sql = "SELECT DISTINCT full_name as suggestion, 'user' as type FROM users WHERE full_name LIKE ? ORDER BY full_name LIMIT ?";
            $this->db->prepare($sql);
            $this->db->execute([$searchTerm, $limit]);
            $result = $this->db->getResult();

            while ($row = $result->fetch_assoc()) {
                $suggestions[] = $row;
            }
        }

        // Category suggestions
        if ($type === 'all' || $type === 'categories') {
            $sql = "SELECT DISTINCT name as suggestion, 'category' as type FROM categories WHERE name LIKE ? ORDER BY name LIMIT ?";
            $this->db->prepare($sql);
            $this->db->execute([$searchTerm, $limit]);
            $result = $this->db->getResult();

            while ($row = $result->fetch_assoc()) {
                $suggestions[] = $row;
            }
        }

        return $suggestions;
    }

    // Get search statistics
    public function getSearchStats($query, $user_id = null, $user_role = null)
    {
        $stats = [
            'total_results' => 0,
            'by_status' => [],
            'by_priority' => [],
            'by_category' => []
        ];

        if (empty($query)) {
            return $stats;
        }

        // Total results count
        $sql = "SELECT COUNT(*) as count FROM tasks WHERE (title LIKE ? OR description LIKE ?)";
        $params = ["%$query%", "%$query%"];

        if ($user_role !== 'admin' && $user_role !== 'manager') {
            $sql .= " AND (assigned_to = ? OR created_by = ?)";
            $params = array_merge($params, [$user_id, $user_id]);
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['total_results'] = $result['count'] ?? 0;

        // Results by status
        $sql = "SELECT status, COUNT(*) as count FROM tasks WHERE (title LIKE ? OR description LIKE ?) GROUP BY status";
        $this->db->prepare($sql);
        $this->db->execute(["%$query%", "%$query%"]);
        $result = $this->db->getResult();
        while ($row = $result->fetch_assoc()) {
            $stats['by_status'][$row['status']] = $row['count'];
        }

        // Results by priority
        $sql = "SELECT p.name, COUNT(t.id) as count
                FROM tasks t
                JOIN priority_levels p ON t.priority_id = p.id
                WHERE (t.title LIKE ? OR t.description LIKE ?)
                GROUP BY p.id, p.name";
        $this->db->prepare($sql);
        $this->db->execute(["%$query%", "%$query%"]);
        $result = $this->db->getResult();
        while ($row = $result->fetch_assoc()) {
            $stats['by_priority'][$row['name']] = $row['count'];
        }

        // Results by category
        $sql = "SELECT c.name, COUNT(t.id) as count
                FROM tasks t
                LEFT JOIN categories c ON t.category_id = c.id
                WHERE (t.title LIKE ? OR t.description LIKE ?)
                GROUP BY c.id, c.name";
        $this->db->prepare($sql);
        $this->db->execute(["%$query%", "%$query%"]);
        $result = $this->db->getResult();
        while ($row = $result->fetch_assoc()) {
            $categoryName = $row['name'] ?: 'Uncategorized';
            $stats['by_category'][$categoryName] = $row['count'];
        }

        return $stats;
    }

    // Save search query (for analytics)
    public function saveSearchQuery($query, $user_id, $results_count)
    {
        $sql = "INSERT INTO search_queries (query, user_id, results_count, created_at) VALUES (?, ?, ?, NOW())";
        $this->db->prepare($sql);
        return $this->db->execute([$query, $user_id, $results_count]);
    }

    // Get popular search terms
    public function getPopularSearches($limit = 10)
    {
        $sql = "SELECT query, COUNT(*) as count FROM search_queries
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY query ORDER BY count DESC LIMIT ?";
        $this->db->prepare($sql);
        $this->db->execute([$limit]);
        return $this->db->getResult();
    }

    // Advanced filtering options
    public function getFilterOptions($user_id = null, $user_role = null)
    {
        $options = [
            'statuses' => ['todo', 'in_progress', 'completed'],
            'priorities' => [],
            'categories' => [],
            'users' => []
        ];

        // Get priorities
        $sql = "SELECT id, name FROM priority_levels ORDER BY weight ASC";
        $this->db->prepare($sql);
        $this->db->execute();
        $result = $this->db->getResult();
        while ($row = $result->fetch_assoc()) {
            $options['priorities'][] = $row;
        }

        // Get categories
        $sql = "SELECT id, name FROM categories ORDER BY name ASC";
        $this->db->prepare($sql);
        $this->db->execute();
        $result = $this->db->getResult();
        while ($row = $result->fetch_assoc()) {
            $options['categories'][] = $row;
        }

        // Get users (only for admin/manager)
        if ($user_role === 'admin' || $user_role === 'manager') {
            $sql = "SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name ASC";
            $this->db->prepare($sql);
            $this->db->execute();
            $result = $this->db->getResult();
            while ($row = $result->fetch_assoc()) {
                $options['users'][] = $row;
            }
        }

        return $options;
    }
}
