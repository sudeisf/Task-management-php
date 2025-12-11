-- Sample Data for Task Management System
-- Run this after creating the database schema

USE task_manager;

-- Sample Users (passwords are 'password123' hashed)
INSERT INTO users (full_name, email, password, role_id, phone, bio, status) VALUES
('System Administrator', 'admin@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, '+1-555-0101', 'System administrator with full access', 'active'),
('Project Manager', 'manager@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, '+1-555-0102', 'Project manager overseeing team tasks', 'active'),
('John Developer', 'john@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '+1-555-0103', 'Frontend developer specializing in React', 'active'),
('Jane Designer', 'jane@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '+1-555-0104', 'UI/UX designer with 5 years experience', 'active'),
('Bob Tester', 'bob@taskmanager.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, '+1-555-0105', 'Quality assurance specialist', 'active');

-- Sample Categories
INSERT INTO categories (name, description) VALUES
('Development', 'Software development and coding tasks'),
('Design', 'UI/UX design and graphics tasks'),
('Testing', 'Quality assurance and testing tasks'),
('Documentation', 'Technical writing and documentation'),
('Research', 'Research and analysis tasks'),
('Maintenance', 'System maintenance and support'),
('Planning', 'Project planning and management');

-- Sample Tasks
INSERT INTO tasks (title, description, category_id, priority_id, status, deadline, created_by, assigned_to) VALUES
('Implement User Authentication System', 'Complete the login and registration system with proper validation, password hashing, and session management.', 1, 3, 'completed', '2024-12-15', 1, 3),
('Design Dashboard Interface', 'Create a responsive dashboard layout with Bootstrap components, statistics cards, and navigation menu.', 2, 2, 'in_progress', '2024-12-20', 1, 4),
('Database Schema Optimization', 'Review and optimize database queries, add proper indexing, and ensure data integrity constraints.', 1, 2, 'todo', '2024-12-22', 1, 3),
('API Documentation', 'Write comprehensive API documentation with examples, endpoints, and authentication details.', 4, 1, 'todo', '2024-12-25', 2, 3),
('User Acceptance Testing', 'Conduct thorough user acceptance testing for the new task management features.', 3, 3, 'todo', '2024-12-28', 2, 5),
('Security Audit', 'Perform security audit on user inputs, authentication system, and data validation.', 1, 3, 'todo', '2024-12-30', 1, 1),
('Performance Optimization', 'Optimize application performance, reduce page load times, and improve user experience.', 1, 2, 'todo', '2025-01-05', 2, 3),
('Mobile Responsiveness', 'Ensure all pages are fully responsive and work properly on mobile devices.', 2, 2, 'todo', '2025-01-10', 1, 4),
('Backup System Implementation', 'Implement automated backup system for database and user files.', 6, 2, 'todo', '2025-01-15', 1, 1),
('Training Documentation', 'Create user training materials and video tutorials for the new system.', 4, 1, 'todo', '2025-01-20', 2, 4);

-- Sample Comments
INSERT INTO comments (task_id, user_id, comment) VALUES
(1, 3, 'Authentication system has been implemented with bcrypt password hashing and secure session management.'),
(2, 4, 'Working on the dashboard design. Will include statistics cards and recent activity feed.'),
(2, 1, 'Please ensure the dashboard is fully responsive and follows our design guidelines.'),
(3, 3, 'Database optimization completed. Added indexes on frequently queried columns.'),
(4, 3, 'API documentation structure is ready. Need to add detailed examples for each endpoint.');

-- Sample Attachments (file paths would need to exist in uploads folder)
INSERT INTO attachments (task_id, uploaded_by, file_path, file_name) VALUES
(2, 4, 'uploads/design_mockup.png', 'dashboard_mockup.png'),
(4, 3, 'uploads/api_docs.pdf', 'api_documentation.pdf'),
(10, 4, 'uploads/training_guide.pdf', 'user_training_guide.pdf');

-- Sample Activity Logs
INSERT INTO activity_logs (user_id, task_id, action, details) VALUES
(1, 1, 'task_created', 'Created task: Implement User Authentication System'),
(3, 1, 'task_completed', 'Marked task as completed'),
(1, 2, 'task_created', 'Created task: Design Dashboard Interface'),
(4, 2, 'task_updated', 'Updated task status to in_progress'),
(1, 3, 'task_created', 'Created task: Database Schema Optimization'),
(2, 4, 'task_created', 'Created task: API Documentation'),
(3, 4, 'comment_added', 'Added comment to API Documentation task'),
(1, 5, 'task_created', 'Created task: User Acceptance Testing'),
(2, 6, 'task_created', 'Created task: Security Audit');

-- Sample Notifications
INSERT INTO notifications (user_id, task_id, message, is_read) VALUES
(3, 1, 'Task "Implement User Authentication System" has been marked as completed', 0),
(4, 2, 'You have been assigned to task "Design Dashboard Interface"', 0),
(3, 4, 'New comment on task "API Documentation"', 0),
(5, 5, 'You have been assigned to task "User Acceptance Testing"', 0),
(1, 6, 'Task "Security Audit" requires your attention', 0);

-- Additional sample tasks for better demonstration
INSERT INTO tasks (title, description, category_id, priority_id, status, deadline, created_by, assigned_to) VALUES
('Code Review Process', 'Establish code review guidelines and implement pull request workflow.', 7, 1, 'completed', '2024-12-10', 2, 3),
('Error Logging System', 'Implement comprehensive error logging and monitoring system.', 1, 2, 'in_progress', '2024-12-18', 1, 3),
('User Feedback Collection', 'Create system for collecting and analyzing user feedback.', 5, 1, 'todo', '2025-01-08', 2, 4),
('Integration Testing', 'Perform integration testing between all system components.', 3, 2, 'todo', '2025-01-12', 2, 5),
('Deployment Automation', 'Set up automated deployment pipeline for staging and production.', 6, 3, 'todo', '2025-01-18', 1, 1);

-- More comments for demonstration
INSERT INTO comments (task_id, user_id, comment) VALUES
(11, 3, 'Code review guidelines have been documented and shared with the team.'),
(12, 3, 'Error logging system is now capturing all PHP errors and exceptions.'),
(12, 1, 'Please ensure sensitive information is not logged in error messages.'),
(13, 4, 'User feedback form design is complete and ready for implementation.'),
(15, 1, 'Deployment automation will include database migrations and rollback capabilities.');

-- More activity logs
INSERT INTO activity_logs (user_id, task_id, action, details) VALUES
(2, 11, 'task_completed', 'Completed code review process implementation'),
(1, 12, 'task_updated', 'Updated error logging task status'),
(4, 13, 'comment_added', 'Added feedback on user feedback system'),
(3, 14, 'task_created', 'Created integration testing task'),
(1, 15, 'task_created', 'Created deployment automation task');

-- More notifications
INSERT INTO notifications (user_id, task_id, message, is_read) VALUES
(3, 11, 'Task "Code Review Process" has been completed', 1),
(3, 12, 'Task "Error Logging System" status updated to in_progress', 0),
(4, 13, 'Task "User Feedback Collection" is now assigned to you', 0),
(5, 14, 'New task assigned: Integration Testing', 0),
(1, 15, 'Deployment automation task created', 1);
