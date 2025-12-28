<?php

require_once __DIR__ . '/../core/Database.php';

class Notification
{
    private $db;
    private $table = "notifications";

    public function __construct()
    {
        $this->db = new Database();
    }

    // Create notification
    public function create($data)
    {
        // Check if new columns exist (backward compatibility)
        $hasNewColumns = $this->checkNewColumnsExist();
        
        if ($hasNewColumns) {
            $sql = "INSERT INTO $this->table (user_id, task_id, project_id, type, message, is_read)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [
                $data['user_id'],
                $data['task_id'] ?? null,
                $data['project_id'] ?? null,
                $data['type'] ?? 'general',
                $data['message'],
                $data['is_read'] ?? 0
            ];
        } else {
            // Old schema without project_id and type columns
            $sql = "INSERT INTO $this->table (user_id, task_id, message, is_read)
                    VALUES (?, ?, ?, ?)";
            $params = [
                $data['user_id'],
                $data['task_id'] ?? null,
                $data['message'],
                $data['is_read'] ?? 0
            ];
        }

        $this->db->prepare($sql);
        if ($this->db->execute($params)) {
            return $this->db->getLastInsertId();
        }

        return false;
    }
    
    // Check if new columns (project_id, type) exist in notifications table
    private function checkNewColumnsExist()
    {
        static $hasNewColumns = null;
        
        if ($hasNewColumns === null) {
            try {
                $sql = "SHOW COLUMNS FROM $this->table LIKE 'project_id'";
                $this->db->prepare($sql);
                $this->db->execute([]);
                $result = $this->db->getRow();
                $hasNewColumns = !empty($result);
            } catch (Exception $e) {
                $hasNewColumns = false;
            }
        }
        
        return $hasNewColumns;
    }

    // Get notifications for user
    public function getByUser($user_id, $limit = null, $offset = null, $onlyUnread = false)
    {
        $hasNewColumns = $this->checkNewColumnsExist();
        
        if ($hasNewColumns) {
            $sql = "SELECT n.*, t.title as task_title, p.name as project_name
                    FROM $this->table n
                    LEFT JOIN tasks t ON n.task_id = t.id
                    LEFT JOIN projects p ON n.project_id = p.id
                    WHERE n.user_id = ?";
        } else {
            $sql = "SELECT n.*, t.title as task_title
                    FROM $this->table n
                    LEFT JOIN tasks t ON n.task_id = t.id
                    WHERE n.user_id = ?";
        }

        $params = [$user_id];

        if ($onlyUnread) {
            $sql .= " AND n.is_read = 0";
        }

        $sql .= " ORDER BY n.created_at DESC";

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
        $this->db->execute($params);
        $rows = $this->db->getRows();
        
        if (!$hasNewColumns && !empty($rows)) {
            foreach ($rows as &$row) {
                if (!isset($row['type'])) {
                    $msg = strtolower($row['message']);
                    if (strpos($msg, 'overdue') !== false) {
                        $row['type'] = (strpos($msg, 'project') !== false) ? 'project_overdue' : 'task_overdue';
                    } elseif (strpos($msg, 'assigned') !== false) {
                        $row['type'] = (strpos($msg, 'project') !== false) ? 'project_assignment' : 'task_assignment';
                    } else {
                        $row['type'] = 'general';
                    }
                }
            }
        }
        
        return $rows;
    }

    // Get notification by ID
    public function find($id)
    {
        $hasNewColumns = $this->checkNewColumnsExist();
        
        if ($hasNewColumns) {
            $sql = "SELECT n.*, t.title as task_title, p.name as project_name
                    FROM $this->table n
                    LEFT JOIN tasks t ON n.task_id = t.id
                    LEFT JOIN projects p ON n.project_id = p.id
                    WHERE n.id = ?";
        } else {
            $sql = "SELECT n.*, t.title as task_title
                    FROM $this->table n
                    LEFT JOIN tasks t ON n.task_id = t.id
                    WHERE n.id = ?";
        }

        $this->db->prepare($sql);
        $this->db->execute([$id]);
        $this->db->execute([$id]);
        $row = $this->db->getRow();

        if (!$hasNewColumns && $row && !isset($row['type'])) {
            $msg = strtolower($row['message']);
            if (strpos($msg, 'overdue') !== false) {
                $row['type'] = (strpos($msg, 'project') !== false) ? 'project_overdue' : 'task_overdue';
            } elseif (strpos($msg, 'assigned') !== false) {
                $row['type'] = (strpos($msg, 'project') !== false) ? 'project_assignment' : 'task_assignment';
            } else {
                $row['type'] = 'general';
            }
        }

        return $row;
    }

    // Mark notification as read
    public function markAsRead($id, $user_id)
    {
        $sql = "UPDATE $this->table SET is_read = 1 WHERE id = ? AND user_id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$id, $user_id]);
    }

    // Mark all notifications as read for user
    public function markAllAsRead($user_id)
    {
        $sql = "UPDATE $this->table SET is_read = 1 WHERE user_id = ? AND is_read = 0";
        $this->db->prepare($sql);
        return $this->db->execute([$user_id]);
    }

    // Delete notification
    public function delete($id, $user_id)
    {
        $sql = "DELETE FROM $this->table WHERE id = ? AND user_id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$id, $user_id]);
    }

    // Get unread count for user
    public function getUnreadCount($user_id)
    {
        $sql = "SELECT COUNT(*) as count FROM $this->table WHERE user_id = ? AND is_read = 0";
        $this->db->prepare($sql);
        $this->db->execute([$user_id]);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    // =========================================================================
    // ASSIGNMENT NOTIFICATIONS
    // =========================================================================

    /**
     * Create task assignment notification (for members)
     */
    public function createTaskAssignmentNotification($task_id, $assigned_to_user_id, $assigned_by_user_id)
    {
        require_once __DIR__ . '/Task.php';
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task) return false;

        $assignerName = $this->getUserName($assigned_by_user_id);

        $message = "📋 You have been assigned to task: '{$task['title']}'";
        if ($assignerName) {
            $message .= " by {$assignerName}";
        }

        return $this->create([
            'user_id' => $assigned_to_user_id,
            'task_id' => $task_id,
            'project_id' => $task['project_id'] ?? null,
            'type' => 'task_assignment',
            'message' => $message
        ]);
    }

    /**
     * Create project assignment notification (for managers)
     */
    public function createProjectAssignmentNotification($project_id, $assigned_to_user_id, $assigned_by_user_id)
    {
        require_once __DIR__ . '/Project.php';
        $projectModel = new Project();
        $project = $projectModel->find($project_id);

        if (!$project) return false;

        $assignerName = $this->getUserName($assigned_by_user_id);

        $message = "📁 You have been assigned as manager to project: '{$project['name']}'";
        if ($assignerName) {
            $message .= " by {$assignerName}";
        }

        return $this->create([
            'user_id' => $assigned_to_user_id,
            'project_id' => $project_id,
            'type' => 'project_assignment',
            'message' => $message
        ]);
    }

    // =========================================================================
    // OVERDUE NOTIFICATIONS
    // =========================================================================

    /**
     * Create overdue task notification
     * Call this when viewing/checking tasks that are past deadline
     */
    public function createTaskOverdueNotification($task_id, $user_id)
    {
        require_once __DIR__ . '/Task.php';
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task || empty($task['deadline'])) return false;

        $daysOverdue = floor((time() - strtotime($task['deadline'])) / (60 * 60 * 24));
        
        if ($daysOverdue <= 0) return false; // Not overdue

        // Check if already sent today
        if ($this->hasNotificationToday($user_id, $task_id, 'task_overdue', false)) {
            return false;
        }

        $message = "⚠️ Task '{$task['title']}' is {$daysOverdue} day" . ($daysOverdue > 1 ? 's' : '') . " overdue!";

        return $this->create([
            'user_id' => $user_id,
            'task_id' => $task_id,
            'project_id' => $task['project_id'] ?? null,
            'type' => 'task_overdue',
            'message' => $message
        ]);
    }

    /**
     * Create overdue project notification
     * Call this when viewing/checking projects that are past deadline
     */
    public function createProjectOverdueNotification($project_id, $user_id)
    {
        require_once __DIR__ . '/Project.php';
        $projectModel = new Project();
        $project = $projectModel->find($project_id);

        if (!$project || empty($project['end_date'])) return false;

        $daysOverdue = floor((time() - strtotime($project['end_date'])) / (60 * 60 * 24));
        
        if ($daysOverdue <= 0) return false; // Not overdue

        // Check if already sent today
        if ($this->hasNotificationToday($user_id, $project_id, 'project_overdue', true)) {
            return false;
        }

        $message = "⚠️ Project '{$project['name']}' is {$daysOverdue} day" . ($daysOverdue > 1 ? 's' : '') . " overdue!";

        return $this->create([
            'user_id' => $user_id,
            'project_id' => $project_id,
            'type' => 'project_overdue',
            'message' => $message
        ]);
    }

    /**
     * Check and create overdue notifications for a user's tasks
     * Call this on dashboard load or task list view
     */
    public function checkAndNotifyOverdueTasks($user_id, $user_role)
    {
        $today = date('Y-m-d');
        
        if ($user_role === 'admin') {
            // Admin sees all overdue tasks
            $sql = "SELECT t.id, t.assigned_to FROM tasks t 
                    WHERE t.status != 'completed' 
                    AND t.deadline < ? 
                    AND t.deadline IS NOT NULL";
            $this->db->prepare($sql);
            $this->db->execute([$today]);
        } elseif ($user_role === 'manager') {
            // Manager sees overdue tasks in their projects
            $sql = "SELECT t.id, t.assigned_to FROM tasks t 
                    JOIN project_users pu ON t.project_id = pu.project_id 
                    WHERE pu.user_id = ? AND pu.role_in_project = 'manager'
                    AND t.status != 'completed' 
                    AND t.deadline < ? 
                    AND t.deadline IS NOT NULL";
            $this->db->prepare($sql);
            $this->db->execute([$user_id, $today]);
        } else {
            // Member sees only their assigned overdue tasks
            $sql = "SELECT t.id, t.assigned_to FROM tasks t 
                    WHERE t.assigned_to = ? 
                    AND t.status != 'completed' 
                    AND t.deadline < ? 
                    AND t.deadline IS NOT NULL";
            $this->db->prepare($sql);
            $this->db->execute([$user_id, $today]);
        }
        
        $tasks = $this->db->getRows();
        
        foreach ($tasks as $task) {
            $this->createTaskOverdueNotification($task['id'], $user_id);
        }
    }

    /**
     * Check and create overdue notifications for projects
     * Call this on dashboard load
     */
    public function checkAndNotifyOverdueProjects($user_id, $user_role)
    {
        $today = date('Y-m-d');
        
        if ($user_role === 'admin') {
            // Admin sees all overdue projects
            $sql = "SELECT p.id FROM projects p 
                    WHERE p.status NOT IN ('completed', 'archived') 
                    AND p.end_date < ? 
                    AND p.end_date IS NOT NULL";
            $this->db->prepare($sql);
            $this->db->execute([$today]);
        } elseif ($user_role === 'manager') {
            // Manager sees overdue projects they manage
            $sql = "SELECT p.id FROM projects p 
                    JOIN project_users pu ON p.id = pu.project_id 
                    WHERE pu.user_id = ? AND pu.role_in_project = 'manager'
                    AND p.status NOT IN ('completed', 'archived') 
                    AND p.end_date < ? 
                    AND p.end_date IS NOT NULL";
            $this->db->prepare($sql);
            $this->db->execute([$user_id, $today]);
        } else {
            // Members don't get project overdue notifications
            return;
        }
        
        $projects = $this->db->getRows();
        
        foreach ($projects as $project) {
            $this->createProjectOverdueNotification($project['id'], $user_id);
        }
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Check if a notification was already sent today
     */
    public function hasNotificationToday($userId, $entityId, $type, $isProject = false)
    {
        $hasNewColumns = $this->checkNewColumnsExist();
        
        if ($hasNewColumns) {
            $entityColumn = $isProject ? 'project_id' : 'task_id';
            
            $sql = "SELECT COUNT(*) as count 
                    FROM $this->table 
                    WHERE user_id = ? 
                    AND $entityColumn = ? 
                    AND type = ?
                    AND DATE(created_at) = CURDATE()";
            
            $this->db->prepare($sql);
            $this->db->execute([$userId, $entityId, $type]);
        } else {
            // Fallback: check by message content for old schema
            $typeKeyword = str_contains($type, 'overdue') ? 'overdue' : 'assigned';
            
            if ($isProject) {
                // For project notifications in old schema, task_id is NULL
                $sql = "SELECT COUNT(*) as count 
                        FROM $this->table 
                        WHERE user_id = ? 
                        AND task_id IS NULL 
                        AND message LIKE ?
                        AND DATE(created_at) = CURDATE()";
                $params = [$userId, "%{$typeKeyword}%"];
            } else {
                // For task notifications
                $sql = "SELECT COUNT(*) as count 
                        FROM $this->table 
                        WHERE user_id = ? 
                        AND task_id = ? 
                        AND message LIKE ?
                        AND DATE(created_at) = CURDATE()";
                $params = [$userId, $entityId, "%{$typeKeyword}%"];
            }
            
            $this->db->prepare($sql);
            $this->db->execute($params);
        }
        
        $result = $this->db->getRow();
        return ($result['count'] ?? 0) > 0;
    }

    // Get notification statistics
    public function getStatistics($user_id = null)
    {
        $stats = [
            'total_notifications' => 0,
            'unread_notifications' => 0,
            'today_notifications' => 0,
            'week_notifications' => 0
        ];

        $baseSql = "SELECT COUNT(*) as count FROM $this->table WHERE 1=1";
        $params = [];

        if ($user_id) {
            $baseSql .= " AND user_id = ?";
            $params = [$user_id];
        }

        // Total notifications
        $this->db->prepare($baseSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['total_notifications'] = $result['count'] ?? 0;

        // Unread notifications
        $unreadSql = $baseSql . " AND is_read = 0";
        $this->db->prepare($unreadSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['unread_notifications'] = $result['count'] ?? 0;

        // Today's notifications
        $todaySql = $baseSql . " AND DATE(created_at) = CURDATE()";
        $this->db->prepare($todaySql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['today_notifications'] = $result['count'] ?? 0;

        // This week's notifications
        $weekSql = $baseSql . " AND YEARWEEK(created_at) = YEARWEEK(CURDATE())";
        $this->db->prepare($weekSql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        $stats['week_notifications'] = $result['count'] ?? 0;

        return $stats;
    }

    // Clean old notifications (for maintenance)
    public function cleanOldNotifications($days = 30)
    {
        $sql = "DELETE FROM $this->table WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY) AND is_read = 1";
        $this->db->prepare($sql);
        return $this->db->execute([$days]);
    }

    // Get notification count
    public function getCount($user_id = null, $onlyUnread = false)
    {
        $sql = "SELECT COUNT(*) as count FROM $this->table WHERE 1=1";
        $params = [];

        if ($user_id) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
        }

        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    private function getUserName($user_id)
    {
        $sql = "SELECT full_name FROM users WHERE id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$user_id]);
        $result = $this->db->getRow();
        return $result['full_name'] ?? null;
    }
}
