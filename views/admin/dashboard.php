<?php
/**
 * Admin Dashboard View
 * Comprehensive overview for administrators
 */

// Assuming these variables are passed from the controller
// $stats - system statistics
// $todoTasks - recent todo tasks
// $inProgressTasks - recent in progress tasks
// $completedTasks - recent completed tasks
// $recentActivities - recent system activities
?>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header mb-4">
        <h1 class="dashboard-title">Admin Dashboard</h1>
        <p class="dashboard-subtitle">System-wide overview and statistics</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <!-- Total Users Card -->
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h2 class="mb-0"><?php echo $stats['total_users'] ?? 0; ?></h2>
                            <small class="text-success">
                                <i class="bi bi-person-check"></i> 
                                <?php echo $stats['active_users'] ?? 0; ?> active
                            </small>
                        </div>
                        <div class="stats-icon bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Projects Card -->
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-2">Projects</h6>
                            <h2 class="mb-0"><?php echo $stats['total_projects'] ?? 0; ?></h2>
                            <div class="mt-2">
                                <small class="text-secondary d-block">
                                    <i class="bi bi-circle"></i> 
                                    <?php echo $stats['planning_projects'] ?? 0; ?> To Do
                                </small>
                                <small class="text-primary d-block">
                                    <i class="bi bi-arrow-repeat"></i> 
                                    <?php echo $stats['in_progress_projects'] ?? 0; ?> In Progress
                                </small>
                                <small class="text-success d-block">
                                    <i class="bi bi-check-circle"></i> 
                                    <?php echo $stats['completed_projects'] ?? 0; ?> Completed
                                </small>
                            </div>
                        </div>
                        <div class="stats-icon bg-info bg-opacity-10 text-info rounded-circle p-3">
                            <i class="bi bi-folder2-open fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Tasks Card -->
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Total Tasks</h6>
                            <h2 class="mb-0"><?php echo $stats['total_tasks'] ?? 0; ?></h2>
                            <small class="text-warning">
                                <i class="bi bi-hourglass-split"></i> 
                                <?php echo $stats['pending_tasks'] ?? 0; ?> pending
                            </small>
                        </div>
                        <div class="stats-icon bg-warning bg-opacity-10 text-warning rounded-circle p-3">
                            <i class="bi bi-list-task fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Activities Card -->
        <div class="col-md-3">
            <div class="card stats-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-2">Activities</h6>
                            <h2 class="mb-0"><?php echo $stats['total_activities'] ?? 0; ?></h2>
                            <small class="text-secondary">
                                <i class="bi bi-clock-history"></i> 
                                Total logged
                            </small>
                        </div>
                        <div class="stats-icon bg-secondary bg-opacity-10 text-secondary rounded-circle p-3">
                            <i class="bi bi-activity fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task Status Overview -->
    <div class="row g-4 mb-4">
        <!-- To Do Tasks -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-circle text-secondary"></i> To Do
                        </h5>
                        <span class="badge bg-secondary"><?php echo $stats['todo_count'] ?? 0; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($todoTasks) && count($todoTasks) > 0): ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach (array_slice($todoTasks, 0, 5) as $task): ?>
                                <li class="mb-3 pb-3 border-bottom">
                                    <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars(substr($task['title'], 0, 40)) . (strlen($task['title']) > 40 ? '...' : ''); ?></h6>
                                                <small class="text-muted">
                                                    <?php if (!empty($task['project_name'])): ?>
                                                        <i class="bi bi-folder2"></i> <?php echo htmlspecialchars($task['project_name']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge badge-priority-<?php echo $task['priority_name']; ?>">
                                                <?php echo ucfirst($task['priority_name']); ?>
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks&status=todo" 
                               class="btn btn-sm btn-outline-secondary">
                                View All To Do Tasks <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6"></i>
                            <p class="mt-2 mb-0">No tasks to do</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- In Progress Tasks -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-arrow-repeat text-primary"></i> In Progress
                        </h5>
                        <span class="badge bg-primary"><?php echo $stats['in_progress_count'] ?? 0; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($inProgressTasks) && count($inProgressTasks) > 0): ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach (array_slice($inProgressTasks, 0, 5) as $task): ?>
                                <li class="mb-3 pb-3 border-bottom">
                                    <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars(substr($task['title'], 0, 40)) . (strlen($task['title']) > 40 ? '...' : ''); ?></h6>
                                                <small class="text-muted">
                                                    <?php if (!empty($task['project_name'])): ?>
                                                        <i class="bi bi-folder2"></i> <?php echo htmlspecialchars($task['project_name']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge badge-priority-<?php echo $task['priority_name']; ?>">
                                                <?php echo ucfirst($task['priority_name']); ?>
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks&status=in_progress" 
                               class="btn btn-sm btn-outline-primary">
                                View All In Progress <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6"></i>
                            <p class="mt-2 mb-0">No tasks in progress</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Completed Tasks -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-check-circle text-success"></i> Complete
                        </h5>
                        <span class="badge bg-success"><?php echo $stats['completed_count'] ?? 0; ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($completedTasks) && count($completedTasks) > 0): ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach (array_slice($completedTasks, 0, 5) as $task): ?>
                                <li class="mb-3 pb-3 border-bottom">
                                    <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>" 
                                       class="text-decoration-none text-dark">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 text-decoration-line-through text-muted">
                                                    <?php echo htmlspecialchars(substr($task['title'], 0, 40)) . (strlen($task['title']) > 40 ? '...' : ''); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php if (!empty($task['project_name'])): ?>
                                                        <i class="bi bi-folder2"></i> <?php echo htmlspecialchars($task['project_name']); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge badge-priority-<?php echo $task['priority_name']; ?>">
                                                <?php echo ucfirst($task['priority_name']); ?>
                                            </span>
                                        </div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks&status=completed" 
                               class="btn btn-sm btn-outline-success">
                                View All Completed <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6"></i>
                            <p class="mt-2 mb-0">No completed tasks</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activities -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Recent Activities
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($recentActivities) && count($recentActivities) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($recentActivities, 0, 10) as $activity): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($activity['user_name'] ?? 'System'); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($activity['action'] ?? 'Activity'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($activity['description'] ?? ''); ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo isset($activity['created_at']) ? timeAgo($activity['created_at']) : ''; ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=activity_logs" 
                               class="btn btn-sm btn-outline-secondary">
                                View All Activities <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-inbox display-6"></i>
                            <p class="mt-2 mb-0">No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stats-card {
    transition: transform 0.2s;
}

.stats-card:hover {
    transform: translateY(-5px);
}

.stats-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
