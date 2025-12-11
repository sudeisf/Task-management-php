<?php

require_once __DIR__ . '/../core/Database.php';

class Permission
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // ==================== PERMISSION CHECKS ====================
    
    /**
     * Check if user can perform an action on a resource
     * 
     * @param int $userId User ID
     * @param string $action 'create', 'read', 'update', 'delete'
     * @param string $resourceType 'project', 'task', 'user', 'report', etc.
     * @return bool
     */
    public function can($userId, $action, $resourceType)
    {
        // Get user role
        $userRole = $this->getUserRole($userId);
        
        if (!$userRole) {
            return false;
        }
        
        // Admins can do everything
        if ($userRole === 'admin') {
            return true;
        }
        
        // Check permission in database
        $sql = "SELECT rp.* FROM role_permissions rp
                JOIN roles r ON rp.role_id = r.id
                WHERE r.name = ? AND rp.resource_type = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$userRole, $resourceType]);
        $permissions = $this->db->getRows();
        
        foreach ($permissions as $perm) {
            switch ($action) {
                case 'create':
                    if ($perm['can_create']) return true;
                    break;
                case 'read':
                    if ($perm['can_read']) return true;
                    break;
                case 'update':
                    if ($perm['can_update']) return true;
                    break;
                case 'delete':
                    if ($perm['can_delete']) return true;
                    break;
            }
        }
        
        return false;
    }

    /**
     * Check if user can manage projects
     */
    public function canManageProjects($userId)
    {
        return $this->can($userId, 'create', 'project');
    }

    /**
     * Check if user can create tasks
     */
    public function canCreateTasks($userId)
    {
        return $this->can($userId, 'create', 'task');
    }

    /**
     * Check if user can manage users
     */
    public function canManageUsers($userId)
    {
        return $this->can($userId, 'create', 'user');
    }

    /**
     * Check if user can view reports
     */
    public function canViewReports($userId)
    {
        return $this->can($userId, 'read', 'report');
    }

    // ==================== PROJECT-SPECIFIC PERMISSIONS ====================
    
    /**
     * Check if user can access a specific project
     */
    public function canAccessProject($userId, $projectId)
    {
        $userRole = $this->getUserRole($userId);
        
        // Admins can access all projects
        if ($userRole === 'admin') {
            return true;
        }
        
        // Check if user is assigned to project
        $sql = "SELECT COUNT(*) as count FROM project_users 
                WHERE project_id = ? AND user_id = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$projectId, $userId]);
        $result = $this->db->getRow();
        
        if ($result['count'] > 0) {
            return true;
        }
        
        // For members, check if they have tasks in the project
        if ($userRole === 'member') {
            $sql = "SELECT COUNT(*) as count FROM tasks 
                    WHERE project_id = ? AND assigned_to = ?";
            
            $this->db->prepare($sql);
            $this->db->execute([$projectId, $userId]);
            $result = $this->db->getRow();
            
            return $result['count'] > 0;
        }
        
        return false;
    }

    /**
     * Check if user is a manager of a specific project
     */
    public function isProjectManager($userId, $projectId)
    {
        $sql = "SELECT COUNT(*) as count FROM project_users 
                WHERE project_id = ? AND user_id = ? AND role_in_project = 'manager'";
        
        $this->db->prepare($sql);
        $this->db->execute([$projectId, $userId]);
        $result = $this->db->getRow();
        
        return $result['count'] > 0;
    }

    /**
     * Check if user can manage tasks in a project
     * (Admin or project manager)
     */
    public function canManageProjectTasks($userId, $projectId)
    {
        $userRole = $this->getUserRole($userId);
        
        if ($userRole === 'admin') {
            return true;
        }
        
        return $this->isProjectManager($userId, $projectId);
    }

    // ==================== TASK-SPECIFIC PERMISSIONS ====================
    
    /**
     * Check if user can view a specific task
     */
    public function canViewTask($userId, $taskId)
    {
        $userRole = $this->getUserRole($userId);
        
        // Admins can view all tasks
        if ($userRole === 'admin') {
            return true;
        }
        
        // Get task details
        $sql = "SELECT t.*, p.id as project_id FROM tasks t
                JOIN projects p ON t.project_id = p.id
                WHERE t.id = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$taskId]);
        $task = $this->db->getRow();
        
        if (!$task) {
            return false;
        }
        
        // Managers can view tasks in their projects
        if ($userRole === 'manager') {
            return $this->isProjectManager($userId, $task['project_id']);
        }
        
        // Members can only view their assigned tasks
        if ($userRole === 'member') {
            return $task['assigned_to'] == $userId;
        }
        
        return false;
    }

    /**
     * Check if user can edit a specific task
     */
    public function canEditTask($userId, $taskId)
    {
        $userRole = $this->getUserRole($userId);
        
        // Admins can edit all tasks
        if ($userRole === 'admin') {
            return true;
        }
        
        // Get task details
        $sql = "SELECT t.*, p.id as project_id FROM tasks t
                JOIN projects p ON t.project_id = p.id
                WHERE t.id = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$taskId]);
        $task = $this->db->getRow();
        
        if (!$task) {
            return false;
        }
        
        // Managers can edit tasks in their projects
        if ($userRole === 'manager') {
            return $this->isProjectManager($userId, $task['project_id']);
        }
        
        // Members cannot edit tasks (only update status)
        return false;
    }

    /**
     * Check if user can update task status
     */
    public function canUpdateTaskStatus($userId, $taskId)
    {
        $userRole = $this->getUserRole($userId);
        
        // Admins can update any task status
        if ($userRole === 'admin') {
            return true;
        }
        
        // Get task details
        $sql = "SELECT t.*, p.id as project_id FROM tasks t
                JOIN projects p ON t.project_id = p.id
                WHERE t.id = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$taskId]);
        $task = $this->db->getRow();
        
        if (!$task) {
            return false;
        }
        
        // Managers can update status of tasks in their projects
        if ($userRole === 'manager') {
            return $this->isProjectManager($userId, $task['project_id']);
        }
        
        // Members can update status of their assigned tasks
        if ($userRole === 'member') {
            return $task['assigned_to'] == $userId;
        }
        
        return false;
    }

    // ==================== HELPER METHODS ====================
    
    private function getUserRole($userId)
    {
        $sql = "SELECT r.name FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$userId]);
        $result = $this->db->getRow();
        
        return $result['name'] ?? null;
    }

    /**
     * Get all permissions for a role
     */
    public function getRolePermissions($roleName)
    {
        $sql = "SELECT rp.* FROM role_permissions rp
                JOIN roles r ON rp.role_id = r.id
                WHERE r.name = ?";
        
        $this->db->prepare($sql);
        $this->db->execute([$roleName]);
        return $this->db->getRows();
    }

    /**
     * Get user's effective permissions
     */
    public function getUserPermissions($userId)
    {
        $userRole = $this->getUserRole($userId);
        
        if (!$userRole) {
            return [];
        }
        
        return $this->getRolePermissions($userRole);
    }
}
