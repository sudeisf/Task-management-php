-- -----------------------------------------------------
-- TASK MANAGEMENT SYSTEM - CONSOLIDATED SCHEMA
-- -----------------------------------------------------
-- This file contains the complete database schema and
-- ESSENTIAL SYSTEM DATA (Roles, Priorities).
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS task_manager;
USE task_manager;

-- -----------------------------------------------------
-- ROLES TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,     -- admin, manager, member
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default roles (REQUIRED)
INSERT INTO roles (name, description) VALUES
('admin', 'Full access to the system'),
('manager', 'Manages teams and tasks'),
('member', 'Regular user with limited access');

-- -----------------------------------------------------
-- USERS TABLE (Enhanced with avatar)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    profile_picture VARCHAR(255),
    avatar VARCHAR(255),                  -- Added from add_avatar_column.sql
    phone VARCHAR(20),
    bio TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- PRIORITY LEVELS TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS priority_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,      -- low, medium, high
    weight INT NOT NULL             -- 1, 2, 3 (makes sorting easy)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default priority levels (REQUIRED)
INSERT INTO priority_levels (name, weight) VALUES
('low', 1),
('medium', 2),
('high', 3);

-- -----------------------------------------------------
-- PROJECTS TABLE (From migration_projects.sql)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    status ENUM('active', 'on_hold', 'completed', 'archived', 'planning', 'in_progress') DEFAULT 'active', -- Merged statuses
    start_date DATE,
    end_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- PROJECT USERS (From migration_projects.sql)
-- -----------------------------------------------------
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

-- -----------------------------------------------------
-- ROLE PERMISSIONS (From migration_projects.sql)
-- -----------------------------------------------------
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

-- Insert default permissions (REQUIRED)
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

-- -----------------------------------------------------
-- TASKS TABLE (Enhanced with project_id)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,                   -- Added from migration_projects.sql (made NOT NULL for strictness)
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority_id INT DEFAULT 2,               -- medium by default
    status ENUM('todo', 'in_progress', 'completed') DEFAULT 'todo',
    deadline DATE,
    created_by INT NOT NULL,
    assigned_to INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (priority_id) REFERENCES priority_levels(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_project_id (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- COMMENTS TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- ATTACHMENTS TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NULL,
    task_id INT NULL,
    uploaded_by INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- ACTIVITY LOGS TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- NOTIFICATIONS TABLE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NULL,
    project_id INT NULL,              -- Added for project notifications
    type VARCHAR(50) DEFAULT 'general', -- e.g., task_assignment, project_assignment, overdue
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- TEAMS TABLE (Legacy/Optional - kept for compatibility)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------
-- TEAM MEMBERS TABLE (Legacy/Optional - kept for compatibility)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
