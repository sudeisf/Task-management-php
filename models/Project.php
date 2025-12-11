<?php

require_once __DIR__ . '/../core/Database.php';

class Project
{
    private $db;
    private $table = "projects";

    public function __construct()
    {
        $this->db = new Database();
    }

    // ==================== CREATE ====================
    
    public function create($data)
    {
        $sql = "INSERT INTO $this->table (name, description, status, start_date, end_date, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $this->db->prepare($sql);
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['created_by']
        ];
        
        if ($this->db->execute($params)) {
            return $this->db->getLastInsertId();
        }
        
        return false;
    }

    // ==================== READ ====================
    
    public function find($id)
    {
        $sql = "SELECT p.*, u.full_name as creator_name,
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                COUNT(DISTINCT pu.user_id) as team_size
                FROM $this->table p
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN tasks t ON p.id = t.project_id
                LEFT JOIN project_users pu ON p.id = pu.project_id
                WHERE p.id = ?
                GROUP BY p.id";
        
        $this->db->prepare($sql);
        $this->db->execute([$id]);
        return $this->db->getRow();
    }

    public function all($filters = [])
    {
        $sql = "SELECT p.*, u.full_name as creator_name,
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                COUNT(DISTINCT pu.user_id) as team_size
                FROM $this->table p
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN tasks t ON p.id = t.project_id
                LEFT JOIN project_users pu ON p.id = pu.project_id
                WHERE 1=1";
        
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
        
        $this->db->prepare($sql);
        $this->db->execute($params);
        return $this->db->getRows();
    }

    // Get projects assigned to a manager
    public function getByManager($managerId)
    {
        $sql = "SELECT p.*, u.full_name as creator_name,
                COUNT(DISTINCT t.id) as total_tasks,
                COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                COUNT(DISTINCT pu.user_id) as team_size
                FROM $this->table p
                LEFT JOIN users u ON p.created_by = u.id
                LEFT JOIN tasks t ON p.id = t.project_id
                LEFT JOIN project_users pu ON p.id = pu.project_id
                WHERE p.id IN (
                    SELECT project_id FROM project_users 
                    WHERE user_id = ? AND role_in_project = 'manager'
                )
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        
        $this->db->prepare($sql);
        $this->db->execute([$managerId]);
        return $this->db->getRows();
    }

    // Get projects where user is a member
    public function getByMember($memberId)
    {
        $sql = "SELECT DISTINCT p.*, u.full_name as creator_name
                FROM $this->table p
                LEFT JOIN users u ON p.created_by = u.id
                INNER JOIN tasks t ON p.id = t.project_id
                WHERE t.assigned_to = ?
                ORDER BY p.created_at DESC";
        
        $this->db->prepare($sql);
        $this->db->execute([$memberId]);
        return $this->db->getRows();
    }

    // ==================== UPDATE ====================
    
    public function update($id, $data)
    {
        $sql = "UPDATE $this->table 
                SET name = ?, description = ?, status = ?, start_date = ?, end_date = ?
                WHERE id = ?";
        
        $this->db->prepare($sql);
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $id
        ];
        
        return $this->db->execute($params);
    }

    // ==================== DELETE ====================
    
    public function delete($id)
    {
        $sql = "DELETE FROM $this->table WHERE id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$id]);
    }

    // ==================== PROJECT USERS ====================
    
    // Assign user to project
    public function assignUser($projectId, $userId, $role = 'member')
    {
        $sql = "INSERT INTO project_users (project_id, user_id, role_in_project) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE role_in_project = ?";
        
        $this->db->prepare($sql);
        return $this->db->execute([$projectId, $userId, $role, $role]);
    }

    // Remove user from project
    public function removeUser($projectId, $userId)
    {
        $sql = "DELETE FROM project_users WHERE project_id = ? AND user_id = ?";
        $this->db->prepare($sql);
        return $this->db->execute([$projectId, $userId]);
    }

    // Get project team members
    public function getTeamMembers($projectId)
    {
        $sql = "SELECT u.id, u.full_name, u.email, pu.role_in_project, pu.assigned_at
                FROM project_users pu
                JOIN users u ON pu.user_id = u.id
                WHERE pu.project_id = ?
                ORDER BY pu.role_in_project, u.full_name";
        
        $this->db->prepare($sql);
        $this->db->execute([$projectId]);
        return $this->db->getRows();
    }

    // Get managers of a project
    public function getManagers($projectId)
    {
        $sql = "SELECT u.id, u.full_name, u.email
                FROM project_users pu
                JOIN users u ON pu.user_id = u.id
                WHERE pu.project_id = ? AND pu.role_in_project = 'manager'
                ORDER BY u.full_name";
        
        $this->db->prepare($sql);
        $this->db->execute([$projectId]);
        return $this->db->getRows();
    }

    // Get members (non-managers) of a project
    public function getMembers($projectId)
    {
        $sql = "SELECT u.id, u.full_name, u.email
                FROM project_users pu
                JOIN users u ON pu.user_id = u.id
                WHERE pu.project_id = ? AND pu.role_in_project = 'member'
                ORDER BY u.full_name";
        
        $this->db->prepare($sql);
        $this->db->execute([$projectId]);
        return $this->db->getRows();
    }

    // Check if user has access to project
    public function hasAccess($projectId, $userId, $userRole)
    {
        // Admins have access to all projects
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
        
        // For members, also check if they have any tasks in the project
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

    // ==================== STATISTICS ====================
    
    public function getStatistics($projectId = null)
    {
        if ($projectId) {
            // Single project stats
            $sql = "SELECT 
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'todo' THEN t.id END) as todo_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'in_progress' THEN t.id END) as in_progress_tasks,
                    COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                    COUNT(DISTINCT pu.user_id) as team_size,
                    COUNT(DISTINCT c.id) as total_comments
                    FROM projects p
                    LEFT JOIN tasks t ON p.id = t.project_id
                    LEFT JOIN project_users pu ON p.id = pu.project_id
                    LEFT JOIN comments c ON t.id = c.task_id
                    WHERE p.id = ?";
            
            $this->db->prepare($sql);
            $this->db->execute([$projectId]);
            return $this->db->getRow();
        } else {
            // System-wide stats
            $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_projects,
                    COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_projects,
                    COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.id END) as completed_projects,
                    COUNT(DISTINCT t.id) as total_tasks,
                    COUNT(DISTINCT pu.user_id) as total_team_members
                    FROM projects p
                    LEFT JOIN tasks t ON p.id = t.project_id
                    LEFT JOIN project_users pu ON p.id = pu.project_id";
            
            $this->db->prepare($sql);
            $this->db->execute([]);
            return $this->db->getRow();
        }
    }
}
