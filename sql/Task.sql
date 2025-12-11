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
-- SAMPLE DATA
-- -----------------------------------------------------

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Development', 'Software development tasks'),
('Design', 'UI/UX and graphic design tasks'),
('Testing', 'Quality assurance and testing tasks'),
('Documentation', 'Documentation and knowledge base tasks'),
('Maintenance', 'System maintenance and updates'),
('Research', 'Research and analysis tasks');

-- Insert sample users (passwords are hashed for 'password123')
INSERT INTO users (full_name, email, password, role_id, phone, bio) VALUES
('Admin User', 'admin@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '+1234567890', 'System administrator'),
('Manager User', 'manager@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '+1234567891', 'Project manager'),
('John Doe', 'john@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '+1234567892', 'Frontend developer'),
('Jane Smith', 'jane@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '+1234567893', 'Backend developer');

-- Insert sample tasks
INSERT INTO tasks (title, description, category_id, priority_id, status, deadline, created_by, assigned_to) VALUES
('Implement user authentication', 'Complete the login and registration system with proper validation', 1, 3, 'completed', '2024-12-15', 1, 3),
('Design dashboard layout', 'Create responsive dashboard design with Bootstrap components', 2, 2, 'in_progress', '2024-12-20', 1, 4),
('Write API documentation', 'Document all REST API endpoints with examples', 4, 1, 'todo', '2024-12-25', 2, 3),
('Database optimization', 'Optimize database queries and add proper indexing', 1, 2, 'todo', '2024-12-22', 1, 4),
('User testing phase', 'Conduct comprehensive user testing for new features', 3, 3, 'todo', '2024-12-28', 2, 3);

-- Insert sample comments
INSERT INTO comments (task_id, user_id, comment) VALUES
(1, 3, 'Authentication system is now fully implemented and tested.'),
(2, 4, 'Working on the responsive design components.'),
(2, 1, 'Please make sure to follow the existing design patterns.'),
(3, 3, 'API documentation structure is ready for review.');

-- Insert sample activity logs
INSERT INTO activity_logs (user_id, task_id, action, details) VALUES
(1, 1, 'task_completed', 'Marked task as completed'),
(4, 2, 'task_updated', 'Updated task status to in_progress'),
(1, 2, 'comment_added', 'Added comment to task'),
(3, 3, 'task_created', 'Created new documentation task');

-- Insert sample notifications
INSERT INTO notifications (user_id, task_id, message, is_read) VALUES
(3, 1, 'Your task "Implement user authentication" has been completed', 0),
(4, 2, 'New task assigned: "Design dashboard layout"', 0),
(3, 3, 'You have a new comment on task "Write API documentation"', 0),
(4, 4, 'Task deadline approaching: "Database optimization"', 0);

-- -----------------------------------------------------
-- END OF FILE
-- -----------------------------------------------------
