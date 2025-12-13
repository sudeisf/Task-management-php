<?php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'task_manager');

// Application Configuration
define('APP_NAME', 'Task Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost:8000');
define('BASE_URL', 'http://localhost:8000');

// File Upload Configuration
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip']);

// Pagination Configuration
define('ITEMS_PER_PAGE', 10);
define('MAX_PAGES_DISPLAY', 5);

// Date/Time Configuration
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_DATETIME_FORMAT', 'M d, Y H:i');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MANAGER', 'manager');
define('ROLE_MEMBER', 'member');

// Task Status
define('STATUS_TODO', 'todo');
define('STATUS_IN_PROGRESS', 'in_progress');
define('STATUS_COMPLETED', 'completed');

// Task Priorities
define('PRIORITY_LOW', 'low');
define('PRIORITY_MEDIUM', 'medium');
define('PRIORITY_HIGH', 'high');

// Session Configuration
define('SESSION_LIFETIME', 7200); // 2 hours
define('SESSION_NAME', 'task_manager_session');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LENGTH', 32);

// Email Configuration (for future use)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('FROM_EMAIL', 'noreply@taskmanager.com');
define('FROM_NAME', APP_NAME);

// Error Reporting (set to false in production)
define('DEBUG_MODE', true);

// Time Zone
date_default_timezone_set('UTC');
