<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Auth.php';
require_once __DIR__ . '/../../helpers/functions.php';

Session::start();

// Check if user is authenticated for protected pages
if (!Auth::check()) {
    header("Location: " . BASE_URL . "/views/auth/login.php");
    exit;
}

// Get current user info
$currentUser = Auth::user();
$userRole = $currentUser['role'] ?? null;


// Get notification count
$notificationCount = 0;
if ($currentUser) {
    require_once __DIR__ . '/../../models/Notification.php';
    $notificationModel = new Notification();
    $notificationCount = $notificationModel->getUnreadCount($currentUser['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Task Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts: Rubik for headings, Inter for body -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Rubik:wght@500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/public/css/style.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/public/css/dashboard.css" rel="stylesheet">

    <!-- jQuery (for Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm" style="z-index: 2000;">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/controller/DashboardController.php?action=index">
                <i class="bi bi-check2-square"></i> <?php echo APP_NAME; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if ($currentUser): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/DashboardController.php?action=index">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>

                            <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php">
                                <i class="bi bi-folder2-open"></i> Projects
                            </a>
                        </li>

                        <?php if (hasPermission($userRole, 'manage_users')): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-gear"></i> Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=index">
                                        <i class="bi bi-speedometer2"></i> System Dashboard
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=users">
                                        <i class="bi bi-people"></i> Users
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks">
                                        <i class="bi bi-list-task"></i> All Tasks
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=activity_logs">
                                        <i class="bi bi-activity"></i> Activity Logs
                                    </a></li>
                                </ul>
                            </li>
                        <?php endif; ?>

                        <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=index">
                                    <i class="bi bi-graph-up"></i> Reports
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>

                <?php if ($currentUser): ?>
                    <ul class="navbar-nav">
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static">
                                <i class="bi bi-bell"></i>
                                <?php if ($notificationCount > 0): ?>
                                    <span class="badge bg-danger"><?php echo $notificationCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="z-index: 2001; min-width: 300px;">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <?php 
                                // Get recent notifications for dropdown
                                require_once __DIR__ . '/../../models/Notification.php';
                                $notifModel = new Notification();
                                $recentNotifications = $notifModel->getByUser($currentUser['id'], 5, null, true);
                                ?>
                                <?php if (is_array($recentNotifications) && !empty($recentNotifications)): ?>
                                    <?php foreach ($recentNotifications as $notif): ?>
                                        <li>
                                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>/controller/NotificationController.php">
                                                <i class="bi bi-<?php echo getActivityIcon($notif['type']); ?> me-2"></i>
                                                <?php echo htmlspecialchars(substr($notif['message'], 0, 50)) . (strlen($notif['message']) > 50 ? '...' : ''); ?>
                                                <br><span class="text-muted" style="font-size: 0.75rem;"><?php echo timeAgo($notif['created_at']); ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><span class="dropdown-item-text">No new notifications</span></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="<?php echo BASE_URL; ?>/controller/NotificationController.php">View all notifications</a></li>
                            </ul>
                        </li>

                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" data-bs-display="static">
                                <?php if (!empty($currentUser['avatar'])): ?>
                                    <img src="<?php echo BASE_URL; ?>/public/uploads/avatars/<?php echo htmlspecialchars($currentUser['avatar']); ?>" 
                                         alt="Profile" 
                                         class="rounded-circle me-2" 
                                         style="width: 32px; height: 32px; object-fit: cover;">
                                <?php else: ?>
                                    <i class="bi bi-person-circle me-2"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($currentUser['name']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="z-index: 2001;">
                                <li><h6 class="dropdown-header"><?php echo htmlspecialchars($currentUser['name']); ?></h6></li>
                                <li><span class="dropdown-item-text small text-muted"><?php echo getRoleDisplayName($userRole); ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/ProfileController.php"><i class="bi bi-person"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/ProfileController.php?action=edit"><i class="bi bi-gear"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/AuthController.php?action=logout">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php
    $flashMessage = getFlashMessage();
    if ($flashMessage):
    ?>
    <div class="container-fluid mt-3">
        <div class="alert alert-<?php echo $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Container -->
    <div class="container-fluid">
        <div class="row">
            <?php if ($currentUser): ?>
                <!-- Sidebar -->
                <nav class="col-md-2 d-none d-md-block bg-light sidebar">
                    <div class="sidebar-sticky">
                        <ul class="nav flex-column">
                            <?php if ($userRole === 'admin'): ?>
                                <!-- Admin Sidebar -->
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=index">
                                        <i class="bi bi-speedometer2"></i> Dashboard
                                    </a>
                                </li>
                                
                                <!-- Projects Section -->
                                <li class="nav-item mt-3">
                                    <small class="text-muted px-3 text-uppercase fw-bold">Projects</small>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php?action=create">
                                        <i class="bi bi-plus-circle"></i> Create Project
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php?status=planning">
                                        <i class="bi bi-circle"></i> To Do
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php?status=in_progress">
                                        <i class="bi bi-arrow-repeat"></i> In Progress
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php?status=completed">
                                        <i class="bi bi-check-circle"></i> Complete
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php">
                                        <i class="bi bi-folder2-open"></i> All Projects
                                    </a>
                                </li>
                                
                                <!-- Management Section -->
                                <li class="nav-item mt-3">
                                    <small class="text-muted px-3 text-uppercase fw-bold">Management</small>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=users">
                                        <i class="bi bi-people"></i> Manage Users
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks">
                                        <i class="bi bi-list-task"></i> All Tasks
                                    </a>
                                </li>
                            <?php else: ?>
                                <!-- Sidebar for Manager/Member -->
                                <li class="nav-item">
                                    <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/DashboardController.php?action=index">
                                        <i class="bi bi-house-door"></i> Dashboard
                                    </a>
                                </li>

                                <?php if ($userRole === 'manager'): ?>
                                    <!-- Manager: Show Projects -->
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/ProjectController.php">
                                            <i class="bi bi-folder2-open"></i> Projects
                                        </a>
                                    </li>
                                    
                                    <!-- Manager: Tasks Section -->
                                    <li class="nav-item mt-3">
                                        <small class="text-muted px-3 text-uppercase fw-bold">Tasks</small>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=create">
                                            <i class="bi bi-plus-circle"></i> Create Task
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?status=todo">
                                            <i class="bi bi-circle"></i> To Do
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?status=in_progress">
                                            <i class="bi bi-arrow-repeat"></i> In Progress
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?status=completed">
                                            <i class="bi bi-check-circle"></i> Completed
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?show_all=true">
                                            <i class="bi bi-list-task"></i> All Tasks
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?my_tasks=true">
                                            <i class="bi bi-list-task"></i> My Tasks
                                        </a>
                                    </li>
                                <?php elseif ($userRole === 'member'): ?>
                                    <!-- Member: Show Task Status Filters -->
                                    <li class="nav-item mt-3">
                                        <small class="text-muted px-3 text-uppercase fw-bold">My Tasks</small>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?my_tasks=true&status=todo">
                                            <i class="bi bi-circle"></i> To Do
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?my_tasks=true&status=in_progress">
                                            <i class="bi bi-arrow-repeat"></i> In Progress
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?my_tasks=true&status=completed">
                                            <i class="bi bi-check-circle"></i> Completed
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="<?php echo BASE_URL; ?>/controller/TaskController.php?my_tasks=true">
                                            <i class="bi bi-list-task"></i> All My Tasks
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </nav>

                <!-- Main content area -->
                <main class="col-md-10 ms-sm-auto px-md-4">
                    <!-- Breadcrumb -->
                    <?php
                    $currentRoute = $_GET['action'] ?? 'index';
                    $breadcrumbs = [
                        ['title' => 'Dashboard', 'url' => BASE_URL . '/controller/DashboardController.php?action=index', 'active' => false],
                        ['title' => ucfirst(str_replace('_', ' ', $currentRoute ?? 'dashboard')), 'url' => null, 'active' => true]
                    ];
                    ?>
                    <nav aria-label="breadcrumb" class="mt-3">
                        <ol class="breadcrumb">
                            <?php foreach ($breadcrumbs as $crumb): ?>
                                <?php if ($crumb['url']): ?>
                                    <li class="breadcrumb-item">
                                        <a href="<?php echo $crumb['url']; ?>"><?php echo $crumb['title']; ?></a>
                                    </li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?php echo $crumb['title']; ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
            <?php else: ?>
                <!-- Full width for auth pages -->
                <main class="col-12">
            <?php endif; ?>
