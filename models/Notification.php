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
        $sql = "INSERT INTO $this->table (user_id, task_id, message, is_read)
                VALUES (?, ?, ?, ?)";

        $this->db->prepare($sql);
        $params = [
            $data['user_id'],
            $data['task_id'] ?? null,
            $data['message'],
            $data['is_read'] ?? 0
        ];

        if ($this->db->execute($params)) {
            return $this->db->getLastInsertId();
        }

        return false;
    }

    // Get notifications for user
    public function getByUser($user_id, $limit = null, $offset = null, $onlyUnread = false)
    {
        $sql = "SELECT n.*, t.title as task_title
                FROM $this->table n
                LEFT JOIN tasks t ON n.task_id = t.id
                WHERE n.user_id = ?";

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
        return $this->db->getRows();
    }

    // Get notification by ID
    public function find($id)
    {
        $sql = "SELECT n.*, t.title as task_title
                FROM $this->table n
                LEFT JOIN tasks t ON n.task_id = t.id
                WHERE n.id = ?";

        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
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

    // Create task assignment notification
    public function createTaskAssignmentNotification($task_id, $assigned_to_user_id, $assigned_by_user_id)
    {
        // Get task details
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task) return false;

        // Get assigner name
        $assignerName = $this->getUserName($assigned_by_user_id);

        $message = "You have been assigned to task: '{$task['title']}'";
        if ($assignerName) {
            $message .= " by {$assignerName}";
        }

        return $this->create([
            'user_id' => $assigned_to_user_id,
            'task_id' => $task_id,
            'message' => $message
        ]);
    }

    // Create task completion notification
    public function createTaskCompletionNotification($task_id, $completed_by_user_id)
    {
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task) return false;

        $completerName = $this->getUserName($completed_by_user_id);

        // Notify task creator if different from completer
        if ($task['created_by'] != $completed_by_user_id) {
            $message = "Task '{$task['title']}' has been completed";
            if ($completerName) {
                $message .= " by {$completerName}";
            }

            $this->create([
                'user_id' => $task['created_by'],
                'task_id' => $task_id,
                'message' => $message
            ]);
        }

        // Notify other assignees if this was a team task
        // This would be more complex in a real multi-user system
    }

    // Create due date reminder notification
    public function createDueDateReminder($task_id, $user_id)
    {
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task) return false;

        $daysUntilDue = floor((strtotime($task['deadline']) - time()) / (60 * 60 * 24));

        if ($daysUntilDue >= 0) {
            $message = "Task '{$task['title']}' is due ";

            if ($daysUntilDue === 0) {
                $message .= "today";
            } elseif ($daysUntilDue === 1) {
                $message .= "tomorrow";
            } else {
                $message .= "in {$daysUntilDue} days";
            }

            return $this->create([
                'user_id' => $user_id,
                'task_id' => $task_id,
                'message' => $message
            ]);
        }

        return false;
    }

    // Create overdue task notification
    public function createOverdueNotification($task_id, $user_id)
    {
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task) return false;

        $daysOverdue = floor((time() - strtotime($task['deadline'])) / (60 * 60 * 24));

        $message = "Task '{$task['title']}' is {$daysOverdue} day" . ($daysOverdue > 1 ? 's' : '') . " overdue";

        return $this->create([
            'user_id' => $user_id,
            'task_id' => $task_id,
            'message' => $message
        ]);
    }

    // Create comment notification
    public function createCommentNotification($task_id, $comment_user_id)
    {
        $taskModel = new Task();
        $task = $taskModel->find($task_id);

        if (!$task) return false;

        $commenterName = $this->getUserName($comment_user_id);

        // Notify task assignee if different from commenter
        if ($task['assigned_to'] && $task['assigned_to'] != $comment_user_id) {
            $message = "New comment on task '{$task['title']}'";
            if ($commenterName) {
                $message .= " by {$commenterName}";
            }

            $this->create([
                'user_id' => $task['assigned_to'],
                'task_id' => $task_id,
                'message' => $message
            ]);
        }

        // Notify task creator if different from commenter and assignee
        if ($task['created_by'] != $comment_user_id && $task['created_by'] != $task['assigned_to']) {
            $message = "New comment on your task '{$task['title']}'";
            if ($commenterName) {
                $message .= " by {$commenterName}";
            }

            $this->create([
                'user_id' => $task['created_by'],
                'task_id' => $task_id,
                'message' => $message
            ]);
        }
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

        // Base query
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
            $params[] = [$user_id];
        }

        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
            if (!$user_id) {
                $params = [0];
            }
        }

        $this->db->prepare($sql);
        $this->db->execute($params);
        $result = $this->db->getRow();
        return $result['count'] ?? 0;
    }

    // Private helper methods

    private function getUserName($user_id)
    {
        $sql = "SELECT full_name FROM users WHERE id = ?";
        $this->db->prepare($sql);
        $this->db->execute([$user_id]);
        $result = $this->db->getRow();
        return $result['full_name'] ?? null;
    }
}
