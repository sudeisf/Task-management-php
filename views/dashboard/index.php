<?php
/**
 * Dashboard Index View
 * Main dashboard page showing statistics, recent tasks, and activities
 */

// Include configuration constants
require_once __DIR__ . '/../../config/constants.php';

// Check if user is authenticated
require_once __DIR__ . '/../../core/Auth.php';
if (!Auth::check()) {
    header("Location: " . BASE_URL . "/views/auth/login.php");
    exit;
}

// Include header
require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $stats - dashboard statistics
// $recentTasks - recent tasks array
// $recentActivities - recent activities array
// $overdueTasks - overdue tasks array
// $priorityDistribution - priority distribution data
?>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Dashboard</h1>
        <p class="dashboard-subtitle">Welcome back! Here's an overview of your tasks and activities.</p>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-icon primary">
                <i class="bi bi-list-task"></i>
            </div>
            <div class="stats-number"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stats-label">Total Tasks</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon warning">
                <i class="bi bi-circle"></i>
            </div>
            <div class="stats-number"><?php echo $stats['todo'] ?? 0; ?></div>
            <div class="stats-label">To Do</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon primary">
                <i class="bi bi-play-circle"></i>
            </div>
            <div class="stats-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
            <div class="stats-label">In Progress</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-number"><?php echo $stats['completed'] ?? 0; ?></div>
            <div class="stats-label">Completed</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stats-number"><?php echo $stats['overdue'] ?? 0; ?></div>
            <div class="stats-label">Overdue</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon warning">
                <i class="bi bi-calendar-event"></i>
            </div>
            <div class="stats-number"><?php echo $stats['due_today'] ?? 0; ?></div>
            <div class="stats-label">Due Today</div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="quick-actions-header">
            <h3 class="quick-actions-title">Quick Actions</h3>
        </div>
        <div class="quick-actions-grid">
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=create" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-plus-circle"></i>
                </div>
                <span class="quick-action-label">Create Task</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-list-task"></i>
                </div>
                <span class="quick-action-label">View Tasks</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index&status=todo" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-circle"></i>
                </div>
                <span class="quick-action-label">To Do Tasks</span>
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index&status=in_progress" class="quick-action-btn">
                <div class="quick-action-icon">
                    <i class="bi bi-play-circle"></i>
                </div>
                <span class="quick-action-label">In Progress</span>
            </a>
        </div>
    </div>

    <!-- Main Dashboard Grid -->
    <div class="dashboard-grid">
        <!-- Recent Tasks -->
        <div class="tasks-overview">
            <div class="tasks-header">
                <h3 class="tasks-title">Recent Tasks</h3>
                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index" class="tasks-view-all">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="tasks-list">
                <?php if (!empty($recentTasks)): ?>
                    <?php foreach ($recentTasks as $task): ?>
                        <div class="task-item">
                            <div class="task-checkbox <?php echo ($task['status'] === 'completed') ? 'checked' : ''; ?>"
                                 data-task-id="<?php echo $task['id']; ?>"
                                 onclick="toggleTaskStatus(<?php echo $task['id']; ?>)">
                            </div>
                            <div class="task-content">
                                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                   class="task-title <?php echo ($task['status'] === 'completed') ? 'completed' : ''; ?>">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                                <div class="task-meta">
                                    <span class="badge badge-priority-<?php echo $task['priority_name']; ?>">
                                        <?php echo ucfirst($task['priority_name']); ?>
                                    </span>
                                    <span class="badge badge-status-<?php echo $task['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                    <?php if ($task['deadline']): ?>
                                        <span class="task-due <?php echo (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'overdue' : ''; ?>">
                                            <i class="bi bi-calendar"></i>
                                            Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-inbox display-4 mb-3"></i>
                        <p>No tasks found. <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=create">Create your first task</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activities & Overdue Tasks -->
        <div class="activities-card">
            <div class="activities-header">
                <h3 class="activities-title">Recent Activities</h3>
            </div>
            <ul class="activities-list">
                <?php if (!empty($recentActivities)): ?>
    <?php foreach ($recentActivities as $activity): ?>
        <li class="activity-item">
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $activity['task_id']; ?>#comments-section" class="text-decoration-none text-dark d-block">
                <div class="activity-content">
                    <?php echo htmlspecialchars($activity['action']); ?>
                    <?php if (!empty($activity['details'])): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($activity['details']); ?></small>
                    <?php endif; ?>
                </div>
                <div class="activity-meta mt-1">
                    <div class="activity-icon <?php echo getActivityType($activity['action']); ?>">
                        <i class="bi <?php echo getActivityIcon($activity['action']); ?>"></i>
                    </div>
                    <span class="ms-4 ps-2 text-muted small"><?php echo timeAgo($activity['created_at']); ?></span>
                </div>
            </a>
        </li>
    <?php endforeach; ?>
                <?php else: ?>
                    <li class="activity-item">
                        <div class="activity-content text-muted">
                            <i class="bi bi-info-circle me-2"></i>
                            No recent activities
                        </div>
                    </li>
                <?php endif; ?>
            </ul>

            <!-- Overdue Tasks Section -->
            <?php if (!empty($overdueTasks)): ?>
                <div class="activities-header">
                    <h3 class="activities-title text-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Overdue Tasks (<?php echo count($overdueTasks); ?>)
                    </h3>
                </div>
                <ul class="activities-list">
                    <?php foreach ($overdueTasks as $task): ?>
                        <li class="activity-item">
                            <div class="activity-content">
                                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                   class="text-danger">
                                    <?php echo htmlspecialchars($task['title']); ?>
                                </a>
                                <br><small class="text-muted">
                                    Due: <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                </small>
                            </div>
                            <div class="activity-meta">
                                <span class="badge bg-danger">Overdue</span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Toggle task status
function toggleTaskStatus(taskId) {
    if (confirm('Are you sure you want to change the task status?')) {
        // If checkbox is checked (completed), move to in_progress. If not checked, move to completed.
        var isCompleted = document.querySelector('[data-task-id="' + taskId + '"]').classList.contains('checked');
        var nextStatus = isCompleted ? 'in_progress' : 'completed';

        var formData = new FormData();
        formData.append('status', nextStatus);

        fetch('<?php echo BASE_URL; ?>/controller/TaskController.php?action=change_status&id=' + taskId, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.ok) {
                location.reload();
            } else {
                alert('Error updating task status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task status');
        });
    }
}

// Helper functions for activity display
function getActivityType(action) {
    if (action.includes('task')) return 'task';
    if (action.includes('comment')) return 'comment';
    if (action.includes('user')) return 'user';
    return 'task';
}

function getActivityIcon(action) {
    if (action.includes('created')) return 'bi-plus-circle';
    if (action.includes('updated')) return 'bi-pencil';
    if (action.includes('completed')) return 'bi-check-circle';
    if (action.includes('comment')) return 'bi-chat';
    return 'bi-circle';
}

function timeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diff = now - past;

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return minutes + 'm ago';
    if (hours < 24) return hours + 'h ago';
    return days + 'd ago';
}
</script>

<?php
// Include footer
require_once __DIR__ . '/../layout/footer.php';
?>