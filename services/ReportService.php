<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../core/Database.php';

class ReportService
{
    private $taskModel;
    private $userModel;
    private $activityModel;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
        $this->activityModel = new Activity();
    }

    public function calculateTaskStats($tasks)
    {
        $stats = [
            'total_tasks' => 0,
            'completed_tasks' => 0,
            'in_progress_tasks' => 0,
            'todo_tasks' => 0,
            'overdue_tasks' => 0,
            'completion_rate' => 0
        ];

        $total = is_array($tasks) ? count($tasks) : 0;
        $stats['total_tasks'] = $total;

        if ($total > 0) {
            foreach ($tasks as $task) {
                switch ($task['status']) {
                    case 'completed': $stats['completed_tasks']++; break;
                    case 'in_progress': $stats['in_progress_tasks']++; break;
                    case 'todo': $stats['todo_tasks']++; break;
                }
                if ($task['deadline'] && strtotime($task['deadline']) < time() && $task['status'] !== 'completed') {
                    $stats['overdue_tasks']++;
                }
            }
            $stats['completion_rate'] = round(($stats['completed_tasks'] / $total) * 100, 1);
        }
        return $stats;
    }

    public function getUserActivityData($filters)
    {
        $users = [];
        $res = $this->userModel->getAll();
        
        // Handle both mysqli_result and potentially array (if getAll changed)
        $userData = [];
        if ($res instanceof mysqli_result) {
            while ($row = $res->fetch_assoc()) $userData[] = $row;
        } else {
            $userData = $res ?: [];
        }

        foreach ($userData as $u) {
            $u['tasks_created'] = count($this->taskModel->createdBy($u['id']) ?: []);
            $u['tasks_assigned'] = count($this->taskModel->assignedTo($u['id']) ?: []);
            $u['tasks_completed'] = 0;
            $assigned = $this->taskModel->assignedTo($u['id']) ?: [];
            foreach ($assigned as $t) if ($t['status'] === 'completed') $u['tasks_completed']++;
            
            $u['comments_count'] = $this->getCount('comments', 'user_id', $u['id']);
            $u['attachments_count'] = $this->getCount('attachments', 'uploaded_by', $u['id']);
            
            $last = $this->activityModel->getByUser($u['id'], 1);
            $u['last_activity'] = $last[0]['created_at'] ?? null;
            $users[] = $u;
        }
        return $users;
    }

    public function calculateUserStats($users)
    {
        $stats = ['total_users' => count($users), 'active_users' => 0, 'total_tasks_created' => 0, 'total_tasks_completed' => 0, 'total_comments' => 0, 'total_attachments' => 0];
        foreach ($users as $u) {
            if ($u['status'] === 'active') $stats['active_users']++;
            $stats['total_tasks_created'] += $u['tasks_created'];
            $stats['total_tasks_completed'] += $u['tasks_completed'];
            $stats['total_comments'] += $u['comments_count'];
            $stats['total_attachments'] += $u['attachments_count'];
        }
        return $stats;
    }

    private function getCount($table, $col, $val)
    {
        $db = new Database();
        $db->prepare("SELECT COUNT(*) as count FROM $table WHERE $col = ?");
        $db->execute([$val]);
        return $db->getRow()['count'] ?? 0;
    }
}
