<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Priority.php';
require_once __DIR__ . '/../models/Comment.php';
require_once __DIR__ . '/../models/Attachment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Project.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Database.php';

class TaskService
{
    private $taskModel;
    private $userModel;
    private $projectModel;
    private $notificationModel;
    private $permissionModel;

    public function __construct()
    {
        $this->taskModel = new Task();
        $this->userModel = new User(require __DIR__ . '/../config/db.php');
        $this->projectModel = new Project();
        $this->notificationModel = new Notification();
        $this->permissionModel = new Permission();
    }

    public function getTasksForIndex($userId, $userRole, $filters, $perPage, $offset, $isMyTasks = false)
    {
        if ($isMyTasks) {
            if ($userRole === 'manager') {
                $managedProjects = $this->projectModel->getByManager($userId);
                if (empty($managedProjects)) return [[], 0];
                $projectIds = array_column($managedProjects, 'id');
                $tasks = $this->taskModel->getByProjects($projectIds, $filters, $perPage, $offset);
                $total = count($this->taskModel->getByProjects($projectIds, $filters));
                return [$tasks, $total];
            } else { // member
                $tasks = $this->taskModel->assignedTo($userId, $filters, $perPage, $offset);
                $total = $this->taskModel->getCount($filters, $userId, $userRole);
                return [$tasks, $total];
            }
        }

        if ($userRole === 'admin') {
            $tasks = $this->taskModel->all($filters, $perPage, $offset);
            $total = $this->taskModel->getCount($filters);
            return [$tasks, $total];
        }

        if ($userRole === 'manager' && (!empty($filters) || (isset($_GET['show_all']) && $_GET['show_all'] === 'true'))) {
            $managedProjects = $this->projectModel->getByManager($userId);
            if (empty($managedProjects)) return [[], 0];
            $projectIds = array_column($managedProjects, 'id');
            $tasks = $this->taskModel->getByProjects($projectIds, $filters, $perPage, $offset);
            $total = count($this->taskModel->getByProjects($projectIds, $filters));
            return [$tasks, $total];
        }

        return null; // Redirect case
    }

    public function validateTask($data)
    {
        $validator = new Validator();
        $rules = [
            'project_id' => 'required|integer|exists:projects,id',
            'title' => 'required|max:255',
            'description' => 'max:1000',
            'priority_id' => 'required|integer|in:1,2,3'
        ];

        if (!empty($data['assigned_to'])) {
            $rules['assigned_to'] = 'integer|exists:users,id';
        }

        if (!$validator->validate($data, $rules)) {
            return ['errors' => $validator->getErrors(), 'data' => false];
        }

        $sanitized = $validator->getSanitizedData();
        
        if (!empty($sanitized['assigned_to'])) {
            if ($this->getUserRole($sanitized['assigned_to']) !== 'member') {
                 return ['errors' => ['assigned_to' => ["Tasks can only be assigned to Members."]], 'data' => false];
            }
        }

        $errors = [];
        $today = date('Y-m-d');
        $deadline = null;
        
        // Validate deadline
        if (!empty($data['deadline'])) {
            $deadline = date('Y-m-d', strtotime($data['deadline']));
            
            // Check if deadline is in the past
            if ($deadline < $today) {
                $errors['deadline'] = ['Task deadline cannot be in the past.'];
            }
            
            // Check if deadline is after project's end date
            $project = $this->projectModel->find($sanitized['project_id']);
            if ($project && !empty($project['end_date'])) {
                $projectEndDate = date('Y-m-d', strtotime($project['end_date']));
                if ($deadline > $projectEndDate) {
                    $errors['deadline'] = ['Task deadline cannot be after the project deadline (' . date('M d, Y', strtotime($projectEndDate)) . ').'];
                }
            }
        }
        
        if (!empty($errors)) {
            return ['errors' => $errors, 'data' => false];
        }

        $result = [
            'project_id' => (int)$sanitized['project_id'],
            'title' => $sanitized['title'],
            'description' => $sanitized['description'] ?? '',
            'priority_id' => (int)($sanitized['priority_id'] ?? 2),
            'status' => $sanitized['status'] ?? 'todo',
            'deadline' => $deadline,
            'assigned_to' => !empty($sanitized['assigned_to']) ? (int)$sanitized['assigned_to'] : null
        ];

        return ['errors' => [], 'data' => $result];
    }

    public function getUserRole($userId)
    {
        $sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
        $db = new Database();
        $db->prepare($sql);
        $db->execute([$userId]);
        $result = $db->getRow();
        return $result['name'] ?? 'member';
    }
}
