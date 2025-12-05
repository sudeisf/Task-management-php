-- -----------------------------------------------------
-- TASK MANAGEMENT SYSTEM - DATABASE SCHEMA (UPGRADED)
-- -----------------------------------------------------

CREATE DATABASE IF NOT EXISTS task_manager;
USE task_manager;

-- -----------------------------------------------------
-- ROLES TABLE (More scalable than ENUM)
-- -----------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,     -- admin, manager, member
    description TEXT
);

-- Insert default roles
INSERT INTO roles (name, description) VALUES
('admin', 'Full access to the system'),
('manager', 'Manages teams and tasks'),
('member', 'Regular user with limited access');

-- -----------------------------------------------------
-- USERS TABLE (Enhanced)
-- -----------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,                             -- FK instead of ENUM
    profile_picture VARCHAR(255),
    phone VARCHAR(20),
    bio TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- -----------------------------------------------------
-- PRIORITY LEVELS TABLE (Dynamic, instead of ENUM)
-- -----------------------------------------------------
CREATE TABLE priority_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,      -- low, medium, high
    weight INT NOT NULL             -- 1, 2, 3 (makes sorting easy)
);

-- Insert default priority levels
INSERT INTO priority_levels (name, weight) VALUES
('low', 1),
('medium', 2),
('high', 3);

-- -----------------------------------------------------
-- CATEGORIES TABLE
-- -----------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- TASKS TABLE (Enhanced with relationships)
-- -----------------------------------------------------
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    priority_id INT DEFAULT 2,               -- medium by default
    status ENUM('todo', 'in_progress', 'completed') DEFAULT 'todo',
    deadline DATE,
    created_by INT NOT NULL,
    assigned_to INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (priority_id) REFERENCES priority_levels(id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- COMMENTS TABLE
-- -----------------------------------------------------
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- ATTACHMENTS TABLE
-- -----------------------------------------------------
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- ACTIVITY LOGS TABLE
-- -----------------------------------------------------
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

-- -----------------------------------------------------
-- NOTIFICATIONS TABLE
-- -----------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    task_id INT NULL,
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- TEAMS TABLE (Optional)
-- -----------------------------------------------------
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- -----------------------------------------------------
-- TEAM MEMBERS TABLE (Optional)
-- -----------------------------------------------------
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'member') DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------
-- END OF FILE
-- -----------------------------------------------------
