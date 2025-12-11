<?php

/**
 * Routes Configuration
 * This file defines the routing structure for the Task Management System
 */

// Base URL for the application
define('BASE_URL', 'http://localhost:8000');

// Route definitions
$routes = [
    // Authentication routes
    'login' => 'controller/AuthController.php?action=login',
    'register' => 'controller/AuthController.php?action=register',
    'logout' => 'controller/AuthController.php?action=logout',
    'forgot-password' => 'controller/AuthController.php?action=forgot_password',

    // Dashboard routes
    'dashboard' => 'controller/DashboardController.php?action=index',
    'dashboard/stats' => 'controller/DashboardController.php?action=get_stats',
    'dashboard/activities' => 'controller/DashboardController.php?action=get_recent_activities',

    // Task routes
    'tasks' => 'controller/TaskController.php?action=index',
    'tasks/create' => 'controller/TaskController.php?action=create',
    'tasks/store' => 'controller/TaskController.php?action=store',
    'tasks/show' => 'controller/TaskController.php?action=show&id=',
    'tasks/edit' => 'controller/TaskController.php?action=edit&id=',
    'tasks/update' => 'controller/TaskController.php?action=update&id=',
    'tasks/delete' => 'controller/TaskController.php?action=delete&id=',
    'tasks/change-status' => 'controller/TaskController.php?action=change_status&id=',
    'tasks/search' => 'controller/TaskController.php?action=search',

    // Comment routes
    'comments/store' => 'controller/CommentController.php?action=store',
    'comments/update' => 'controller/CommentController.php?action=update',
    'comments/delete' => 'controller/CommentController.php?action=delete',
    'comments/get' => 'controller/CommentController.php?action=get_comments',

    // Attachment routes
    'attachments/upload' => 'controller/AttachmentController.php?action=upload',
    'attachments/download' => 'controller/AttachmentController.php?action=download&id=',
    'attachments/delete' => 'controller/AttachmentController.php?action=delete',
    'attachments/get' => 'controller/AttachmentController.php?action=get_attachments',

    // Admin routes
    'admin' => 'controller/AdminController.php?action=index',
    'admin/users' => 'controller/AdminController.php?action=users',
    'admin/users/create' => 'controller/AdminController.php?action=create_user',
    'admin/users/store' => 'controller/AdminController.php?action=store_user',
    'admin/users/edit' => 'controller/AdminController.php?action=edit_user&id=',
    'admin/users/update' => 'controller/AdminController.php?action=update_user&id=',
    'admin/users/delete' => 'controller/AdminController.php?action=delete_user&id=',
    'admin/tasks' => 'controller/AdminController.php?action=tasks',
    'admin/settings' => 'controller/AdminController.php?action=settings',
    'admin/activities' => 'controller/AdminController.php?action=activity_logs',

    // Report routes
    'reports' => 'controller/ReportController.php?action=index',
    'reports/tasks' => 'controller/ReportController.php?action=task_report',
    'reports/users' => 'controller/ReportController.php?action=user_report',
    'reports/productivity' => 'controller/ReportController.php?action=productivity_report',
    'reports/overdue' => 'controller/ReportController.php?action=overdue_report',
];

/**
 * Get route URL
 */
function route($name, $params = [])
{
    global $routes;

    if (!isset($routes[$name])) {
        return BASE_URL . '/';
    }

    $url = BASE_URL . '/' . $routes[$name];

    // Replace parameters in URL
    foreach ($params as $key => $value) {
        $url = str_replace('{' . $key . '}', $value, $url);
    }

    return $url;
}

/**
 * Get current route name
 */
function currentRoute()
{
    $requestUri = $_SERVER['REQUEST_URI'];
    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';

    // Remove base path from request URI
    if ($basePath && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    // Remove query string
    $requestUri = explode('?', $requestUri)[0];

    // Remove leading/trailing slashes
    $requestUri = trim($requestUri, '/');

    // If empty, it's the home route
    if (empty($requestUri)) {
        return 'dashboard';
    }

    global $routes;

    // Find matching route
    foreach ($routes as $name => $path) {
        // Remove action parameter from path for comparison
        $pathWithoutAction = str_replace('?action=', '/', $path);
        $pathWithoutAction = str_replace('&', '/', $pathWithoutAction);

        if (strpos($requestUri, $pathWithoutAction) === 0) {
            return $name;
        }
    }

    return null;
}

/**
 * Check if current route matches
 */
function isCurrentRoute($routeName)
{
    return currentRoute() === $routeName;
}

/**
 * Get route parameters
 */
function routeParams()
{
    $params = [];

    // Get query parameters
    if (!empty($_GET)) {
        $params = array_merge($params, $_GET);
    }

    // Get route parameters from URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $basePath = parse_url(BASE_URL, PHP_URL_PATH) ?: '';

    if ($basePath && strpos($requestUri, $basePath) === 0) {
        $requestUri = substr($requestUri, strlen($basePath));
    }

    $pathParts = explode('/', trim($requestUri, '/'));

    // Remove query string from last part
    $lastPart = end($pathParts);
    if (strpos($lastPart, '?') !== false) {
        $pathParts[count($pathParts) - 1] = explode('?', $lastPart)[0];
    }

    return $params;
}

/**
 * Redirect to route
 */
function redirectToRoute($routeName, $params = [])
{
    $url = route($routeName, $params);
    header("Location: $url");
    exit;
}

/**
 * Generate breadcrumb navigation
 */
function generateBreadcrumbs()
{
    $breadcrumbs = [];
    $currentRoute = currentRoute();

    // Define breadcrumb hierarchy
    $breadcrumbMap = [
        'dashboard' => ['Dashboard'],
        'tasks' => ['Dashboard' => 'dashboard', 'Tasks'],
        'tasks/create' => ['Dashboard' => 'dashboard', 'Tasks' => 'tasks', 'Create Task'],
        'tasks/edit' => ['Dashboard' => 'dashboard', 'Tasks' => 'tasks', 'Edit Task'],
        'tasks/show' => ['Dashboard' => 'dashboard', 'Tasks' => 'tasks', 'Task Details'],
        'admin' => ['Dashboard' => 'dashboard', 'Admin'],
        'admin/users' => ['Dashboard' => 'dashboard', 'Admin' => 'admin', 'Users'],
        'admin/tasks' => ['Dashboard' => 'dashboard', 'Admin' => 'admin', 'Tasks'],
        'reports' => ['Dashboard' => 'dashboard', 'Reports']
    ];

    if (isset($breadcrumbMap[$currentRoute])) {
        $crumbs = $breadcrumbMap[$currentRoute];
        $breadcrumbs[] = ['title' => 'Home', 'url' => route('dashboard'), 'active' => false];

        $tempCrumbs = [];
        foreach ($crumbs as $title => $route) {
            if (is_string($title)) {
                // This is a link
                $tempCrumbs[] = ['title' => $title, 'url' => route($route), 'active' => false];
            } else {
                // This is the current page
                $tempCrumbs[] = ['title' => $route, 'url' => null, 'active' => true];
            }
        }

        $breadcrumbs = array_merge($breadcrumbs, $tempCrumbs);
    }

    return $breadcrumbs;
}

/**
 * Check if user has access to route
 */
function hasRouteAccess($routeName)
{
    // Define route permissions
    $routePermissions = [
        'admin' => ['admin'],
        'admin/users' => ['admin'],
        'admin/tasks' => ['admin', 'manager'],
        'admin/settings' => ['admin'],
        'admin/activities' => ['admin', 'manager'],
        'reports/users' => ['admin', 'manager']
    ];

    if (!isset($routePermissions[$routeName])) {
        return true; // Public route
    }

    // Check user role
    $userRole = getCurrentUserRole();
    return in_array($userRole, $routePermissions[$routeName]);
}

/**
 * Get current user role (helper function)
 */
function getCurrentUserRole()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    // This would typically be cached or retrieved from session
    $sql = "SELECT r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
    $db = new Database();
    $db->prepare($sql);
    $db->execute([$_SESSION['user_id']]);
    $result = $db->getRow();
    return $result['name'] ?? 'member';
}
