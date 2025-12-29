<?php

/**
 * Common utility functions for the Task Management System
 */

/**
 * Sanitize string input
 */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random string
 */
function randomString($length = 10)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Format date for display
 */
function formatDate($date, $format = null)
{
    if (!$date) return '';

    $format = $format ?: DISPLAY_DATE_FORMAT;
    $timestamp = strtotime($date);

    return date($format, $timestamp);
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime, $format = null)
{
    if (!$datetime) return '';

    $format = $format ?: DISPLAY_DATETIME_FORMAT;
    $timestamp = strtotime($datetime);

    return date($format, $timestamp);
}

/**
 * Calculate time difference
 */
function timeAgo($datetime)
{
    if (!$datetime) return '';

    $timestamp = strtotime($datetime);
    $now = time();
    $diff = $now - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($datetime);
    }
}

/**
 * Check if date is overdue
 */
function isOverdue($date)
{
    if (!$date) return false;

    $today = date('Y-m-d');
    $deadline = date('Y-m-d', strtotime($date));

    return $deadline < $today;
}

/**
 * Check if date is due today
 */
function isDueToday($date)
{
    if (!$date) return false;

    $today = date('Y-m-d');
    $deadline = date('Y-m-d', strtotime($date));

    return $deadline === $today;
}

/**
 * Check if date is due this week
 */
function isDueThisWeek($date)
{
    if (!$date) return false;

    $today = strtotime('today');
    $deadline = strtotime($date);
    $weekFromNow = strtotime('+7 days');

    return $deadline >= $today && $deadline <= $weekFromNow;
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'todo':
            return 'badge bg-secondary';
        case 'in_progress':
            return 'badge bg-primary';
        case 'completed':
            return 'badge bg-success';
        default:
            return 'badge bg-light';
    }
}

/**
 * Get priority badge class
 */
function getPriorityBadgeClass($priority)
{
    switch (strtolower($priority)) {
        case 'low':
            return 'badge bg-info';
        case 'medium':
            return 'badge bg-warning';
        case 'high':
            return 'badge bg-danger';
        default:
            return 'badge bg-light';
    }
}

/**
 * Get priority weight for sorting
 */
function getPriorityWeight($priority)
{
    switch (strtolower($priority)) {
        case 'low':
            return 1;
        case 'medium':
            return 2;
        case 'high':
            return 3;
        default:
            return 0;
    }
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...')
{
    if (strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Generate slug from string
 */
function generateSlug($string)
{
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

/**
 * Get file extension
 */
function getFileExtension($filename)
{
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Format file size
 */
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Check if user has permission
 */
function hasPermission($userRole, $permission)
{
    $permissions = [
        'admin' => ['create', 'read', 'update', 'delete', 'manage_users', 'manage_tasks'],
        'manager' => ['create', 'read', 'update', 'delete', 'manage_tasks'],
        'member' => ['create', 'read', 'update']
    ];

    return isset($permissions[$userRole]) && in_array($permission, $permissions[$userRole]);
}

/**
 * Get user role display name
 */
function getRoleDisplayName($role)
{
    $roles = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'member' => 'Member'
    ];

    return $roles[$role] ?? ucfirst($role ?? 'unknown');
}

/**
 * Generate CSRF token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get current URL
 */
function currentUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Redirect to URL
 */
function redirect($url, $message = null, $type = 'info')
{
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage($type = null)
{
    $types = $type ? [$type] : ['success', 'error', 'warning', 'info'];

    foreach ($types as $msgType) {
        if (isset($_SESSION[$msgType])) {
            $message = $_SESSION[$msgType];
            unset($_SESSION[$msgType]);
            return ['type' => $msgType, 'message' => $message];
        }
    }

    return null;
}

/**
 * Display validation error summary
 */
function displayValidationErrors($errors)
{
    if (empty($errors)) return '';

    $output = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    $output .= '<h6><i class="bi bi-exclamation-triangle me-2"></i>Please fix the following errors:</h6>';
    $output .= '<ul class="mb-0 small">';

    foreach ($errors as $field => $fieldErrors) {
        foreach ($fieldErrors as $error) {
            $output .= '<li>' . htmlspecialchars($error) . '</li>';
        }
    }

    $output .= '</ul>';
    $output .= '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    $output .= '</div>';

    return $output;
}

/**
 * Set flash message
 */
function setFlashMessage($message, $type = 'info')
{
    $_SESSION[$type] = $message;
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}



/**
 * Calculate percentage
 */
function calculatePercentage($value, $total)
{
    if ($total == 0) return 0;
    return round(($value / $total) * 100, 1);
}

/**
 * Get task progress percentage
 */
function getTaskProgress($tasks)
{
    if (empty($tasks)) return 0;

    $completed = 0;
    $total = count($tasks);

    foreach ($tasks as $task) {
        if (is_array($task) && isset($task['status']) && $task['status'] === 'completed') {
            $completed++;
        } elseif (is_object($task) && isset($task->status) && $task->status === 'completed') {
            $completed++;
        }
    }

    return calculatePercentage($completed, $total);
}


/**
 * Build URL with parameters
 */
function buildUrl($baseUrl, $params = [])
{
    if (empty($params)) return $baseUrl;

    $queryString = http_build_query($params);
    $separator = strpos($baseUrl, '?') === false ? '?' : '&';

    return $baseUrl . $separator . $queryString;
}

/**
 * Get base URL
 */
function baseUrl()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = dirname($_SERVER['SCRIPT_NAME']);

    return $protocol . '://' . $host . $script;
}

/**
 * Get activity icon based on notification type or action string
 */
function getActivityIcon($action)
{
    $action = strtolower($action);

    // Exact match for type codes
    $icons = [
        'task_assigned' => 'bi-person-check',
        'task_assignment' => 'bi-person-check',
        'project_assignment' => 'bi-briefcase',
        'task_overdue' => 'bi-exclamation-circle',
        'project_overdue' => 'bi-exclamation-triangle',
        'task_completed' => 'bi-check-circle',
        'task_updated' => 'bi-pencil',
        'task_created' => 'bi-plus-circle',
        'comment_added' => 'bi-chat',
        'project_created' => 'bi-folder-plus',
        'project_updated' => 'bi-folder',
        'user_registered' => 'bi-person-plus',
        'status_changed' => 'bi-arrow-repeat',
        'deadline_approaching' => 'bi-clock',
        'attachment_uploaded' => 'bi-paperclip'
    ];

    if (isset($icons[$action])) {
        return $icons[$action];
    }

    // Partial matches for action strings
    if (strpos($action, 'created') !== false) return 'bi-plus-circle';
    if (strpos($action, 'updated') !== false) return 'bi-pencil';
    if (strpos($action, 'completed') !== false) return 'bi-check-circle';
    if (strpos($action, 'comment') !== false) return 'bi-chat';
    if (strpos($action, 'attachment') !== false) return 'bi-paperclip';

    return 'bi-bell';
}

/**
 * Get activity type for CSS classes (task, comment, user, etc.)
 */
function getActivityType($action)
{
    $action = strtolower($action);
    if (strpos($action, 'task') !== false) return 'task';
    if (strpos($action, 'comment') !== false) return 'comment';
    if (strpos($action, 'attachment') !== false) return 'attachment';
    if (strpos($action, 'user') !== false) return 'user';
    if (strpos($action, 'project') !== false) return 'project';
    return 'task';
}

/**
 * Alias for getActivityType (used in some views)
 */
function getActivityTypeClass($action)
{
    return getActivityType($action);
}

/**
 * Get display text for activity action
 */
function getActivityActionText($action)
{
    $actions = [
        'task_created' => 'created task',
        'task_updated' => 'updated task',
        'task_completed' => 'completed task',
        'comment_added' => 'added comment to',
        'attachment_uploaded' => 'uploaded attachment to',
        'project_created' => 'created project',
        'project_updated' => 'updated project'
    ];

    return $actions[$action] ?? str_replace('_', ' ', $action);
}

/**
 * Get file icon based on extension
 */
function getFileIcon($filename)
{
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $icons = [
        'pdf' => 'bi-file-earmark-pdf',
        'doc' => 'bi-file-earmark-word',
        'docx' => 'bi-file-earmark-word',
        'xls' => 'bi-file-earmark-excel',
        'xlsx' => 'bi-file-earmark-excel',
        'ppt' => 'bi-file-earmark-ppt',
        'pptx' => 'bi-file-earmark-ppt',
        'txt' => 'bi-file-earmark-text',
        'jpg' => 'bi-file-earmark-image',
        'jpeg' => 'bi-file-earmark-image',
        'png' => 'bi-file-earmark-image',
        'gif' => 'bi-file-earmark-image',
        'zip' => 'bi-file-earmark-zip',
        'rar' => 'bi-file-earmark-zip'
    ];

    return $icons[$ext] ?? 'bi-file-earmark';
}
