-- =====================================================
-- MULTI-ROLE PROJECT MANAGEMENT SYSTEM - MIGRATION
-- =====================================================
-- This migration adds project support to the task management system
-- Run this after backing up your database

USE task_manager;

-- =====================================================
-- PROJECTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'on_hold', 'completed', 'archived') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- PROJECT USERS (Many-to-Many: Projects <-> Users)
-- =====================================================
CREATE TABLE IF NOT EXISTS project_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role_in_project ENUM('manager', 'member') NOT NULL DEFAULT 'member',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_project_user (project_id, user_id),
    INDEX idx_project (project_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role_in_project)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ROLE PERMISSIONS (Granular RBAC)
-- =====================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_name VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50) NOT NULL, -- 'project', 'task', 'user', 'report', etc.
    can_create BOOLEAN DEFAULT FALSE,
    can_read BOOLEAN DEFAULT FALSE,
    can_update BOOLEAN DEFAULT FALSE,
    can_delete BOOLEAN DEFAULT FALSE,
    
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_name, resource_type),
    INDEX idx_role (role_id),
    INDEX idx_resource (resource_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- UPDATE TASKS TABLE - Add project_id
-- =====================================================
ALTER TABLE tasks 
ADD COLUMN project_id INT NULL AFTER id,
ADD INDEX idx_project_id (project_id);

-- Add foreign key constraint (will be added after creating default project)
-- ALTER TABLE tasks ADD FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- =====================================================
-- SEED DEFAULT PERMISSIONS
-- =====================================================

-- Get role IDs
SET @admin_role_id = (SELECT id FROM roles WHERE name = 'admin' LIMIT 1);
SET @manager_role_id = (SELECT id FROM roles WHERE name = 'manager' LIMIT 1);
SET @member_role_id = (SELECT id FROM roles WHERE name = 'member' LIMIT 1);

-- Admin Permissions (Full Access)
INSERT INTO role_permissions (role_id, permission_name, resource_type, can_create, can_read, can_update, can_delete) VALUES
(@admin_role_id, 'manage_projects', 'project', TRUE, TRUE, TRUE, TRUE),
(@admin_role_id, 'manage_tasks', 'task', TRUE, TRUE, TRUE, TRUE),
(@admin_role_id, 'manage_users', 'user', TRUE, TRUE, TRUE, TRUE),
(@admin_role_id, 'view_reports', 'report', TRUE, TRUE, TRUE, TRUE),
(@admin_role_id, 'manage_system', 'system', TRUE, TRUE, TRUE, TRUE);

-- Manager Permissions (Project-scoped)
INSERT INTO role_permissions (role_id, permission_name, resource_type, can_create, can_read, can_update, can_delete) VALUES
(@manager_role_id, 'view_assigned_projects', 'project', FALSE, TRUE, FALSE, FALSE),
(@manager_role_id, 'manage_project_tasks', 'task', TRUE, TRUE, TRUE, TRUE),
(@manager_role_id, 'assign_tasks', 'task', FALSE, TRUE, TRUE, FALSE),
(@manager_role_id, 'view_team_reports', 'report', FALSE, TRUE, FALSE, FALSE),
(@manager_role_id, 'manage_comments', 'comment', TRUE, TRUE, TRUE, TRUE);

-- Member Permissions (Task-scoped)
INSERT INTO role_permissions (role_id, permission_name, resource_type, can_create, can_read, can_update, can_delete) VALUES
(@member_role_id, 'view_assigned_tasks', 'task', FALSE, TRUE, TRUE, FALSE),
(@member_role_id, 'update_task_status', 'task', FALSE, TRUE, TRUE, FALSE),
(@member_role_id, 'add_comments', 'comment', TRUE, TRUE, TRUE, FALSE),
(@member_role_id, 'upload_files', 'attachment', TRUE, TRUE, FALSE, FALSE);

-- =====================================================
-- CREATE DEFAULT PROJECT (for existing tasks)
-- =====================================================

-- Get first admin user
SET @first_admin = (SELECT id FROM users WHERE role_id = @admin_role_id LIMIT 1);

-- If no admin exists, use first user
IF @first_admin IS NULL THEN
    SET @first_admin = (SELECT id FROM users ORDER BY id ASC LIMIT 1);
END IF;

-- Create default project
INSERT INTO projects (name, description, status, created_by) VALUES
('Default Project', 'Default project for existing tasks', 'active', COALESCE(@first_admin, 1));

-- Get the default project ID
SET @default_project_id = LAST_INSERT_ID();

-- Assign all existing tasks to default project
UPDATE tasks SET project_id = @default_project_id WHERE project_id IS NULL;

-- Now add the foreign key constraint
ALTER TABLE tasks 
ADD CONSTRAINT fk_tasks_project 
FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE;

-- Make project_id NOT NULL now that all tasks have a project
ALTER TABLE tasks MODIFY project_id INT NOT NULL;

-- =====================================================
-- CREATE SAMPLE PROJECTS (Optional - for testing)
-- =====================================================

INSERT INTO projects (name, description, status, start_date, end_date, created_by) VALUES
('Website Redesign', 'Complete redesign of company website with modern UI/UX', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), COALESCE(@first_admin, 1)),
('Mobile App Development', 'Native mobile application for iOS and Android', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 180 DAY), COALESCE(@first_admin, 1)),
('Marketing Campaign Q1', 'Q1 marketing initiatives and campaigns', 'active', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), COALESCE(@first_admin, 1));

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================

SELECT 'Migration completed successfully!' AS status;
SELECT COUNT(*) AS total_projects FROM projects;
SELECT COUNT(*) AS total_permissions FROM role_permissions;
SELECT COUNT(*) AS tasks_with_projects FROM tasks WHERE project_id IS NOT NULL;
