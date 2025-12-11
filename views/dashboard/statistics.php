<?php
/**
 * Dashboard Statistics View
 * Detailed statistics and charts for the dashboard
 */

// Include header
require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $stats - dashboard statistics
// $priorityDistribution - priority distribution data
// $categoryStats - category statistics
// $monthlyStats - monthly task completion stats
?>

<div class="dashboard-container">
    <!-- Statistics Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Statistics</h1>
        <p class="dashboard-subtitle">Detailed analytics and insights about your tasks and productivity.</p>
    </div>

    <!-- Statistics Overview -->
    <div class="stats-grid">
        <div class="stats-card">
            <div class="stats-icon primary">
                <i class="bi bi-list-task"></i>
            </div>
            <div class="stats-number"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="stats-label">Total Tasks</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon success">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-number"><?php echo $stats['completed'] ?? 0; ?></div>
            <div class="stats-label">Completed</div>
            <?php if ($stats['total'] > 0): ?>
                <div class="progress mt-2">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: <?php echo ($stats['completed'] / $stats['total']) * 100; ?>%">
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="stats-card">
            <div class="stats-icon warning">
                <i class="bi bi-clock"></i>
            </div>
            <div class="stats-number"><?php echo $stats['in_progress'] ?? 0; ?></div>
            <div class="stats-label">In Progress</div>
        </div>

        <div class="stats-card">
            <div class="stats-icon danger">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div class="stats-number"><?php echo $stats['overdue'] ?? 0; ?></div>
            <div class="stats-label">Overdue</div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="dashboard-grid">
        <!-- Priority Distribution -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">Tasks by Priority</h3>
                <p class="chart-subtitle">Distribution of tasks across priority levels</p>
            </div>
            <div class="chart-body">
                <?php if (!empty($priorityDistribution)): ?>
                    <div class="priority-chart">
                        <?php foreach ($priorityDistribution as $priority): ?>
                            <div class="priority-item mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="priority-label"><?php echo ucfirst($priority['name']); ?></span>
                                    <span class="priority-count"><?php echo $priority['count']; ?> tasks</span>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar priority-<?php echo $priority['name']; ?>"
                                         role="progressbar"
                                         style="width: <?php echo ($priority['count'] / array_sum(array_column($priorityDistribution, 'count'))) * 100; ?>%">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bar-chart display-4 mb-3"></i>
                        <p>No priority data available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Task Status Distribution -->
        <div class="chart-container">
            <div class="chart-header">
                <h3 class="chart-title">Task Status Overview</h3>
                <p class="chart-subtitle">Current status of all tasks</p>
            </div>
            <div class="chart-body">
                <div class="status-chart">
                    <div class="status-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="status-label">
                                <span class="badge badge-status-todo me-2"></span>
                                To Do
                            </span>
                            <span class="status-count"><?php echo $stats['todo'] ?? 0; ?> tasks</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-secondary" role="progressbar"
                                 style="width: <?php echo $stats['total'] > 0 ? ($stats['todo'] / $stats['total']) * 100 : 0; ?>%">
                            </div>
                        </div>
                    </div>

                    <div class="status-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="status-label">
                                <span class="badge badge-status-in-progress me-2"></span>
                                In Progress
                            </span>
                            <span class="status-count"><?php echo $stats['in_progress'] ?? 0; ?> tasks</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar"
                                 style="width: <?php echo $stats['total'] > 0 ? ($stats['in_progress'] / $stats['total']) * 100 : 0; ?>%">
                            </div>
                        </div>
                    </div>

                    <div class="status-item mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="status-label">
                                <span class="badge badge-status-completed me-2"></span>
                                Completed
                            </span>
                            <span class="status-count"><?php echo $stats['completed'] ?? 0; ?> tasks</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: <?php echo $stats['total'] > 0 ? ($stats['completed'] / $stats['total']) * 100 : 0; ?>%">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Statistics -->
    <div class="row">
        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Productivity Metrics</h3>
                    <p class="chart-subtitle">Key performance indicators</p>
                </div>
                <div class="chart-body">
                    <div class="metric-grid">
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $stats['due_today'] ?? 0; ?></div>
                            <div class="metric-label">Due Today</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value"><?php echo $stats['due_this_week'] ?? 0; ?></div>
                            <div class="metric-label">Due This Week</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">
                                <?php
                                $completionRate = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100, 1) : 0;
                                echo $completionRate . '%';
                                ?>
                            </div>
                            <div class="metric-label">Completion Rate</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-value">
                                <?php
                                $overdueRate = $stats['total'] > 0 ? round(($stats['overdue'] / $stats['total']) * 100, 1) : 0;
                                echo $overdueRate . '%';
                                ?>
                            </div>
                            <div class="metric-label">Overdue Rate</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="chart-container">
                <div class="chart-header">
                    <h3 class="chart-title">Quick Insights</h3>
                    <p class="chart-subtitle">Important observations</p>
                </div>
                <div class="chart-body">
                    <div class="insights-list">
                        <?php if (($stats['overdue'] ?? 0) > 0): ?>
                            <div class="insight-item alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                You have <?php echo $stats['overdue']; ?> overdue tasks that need immediate attention.
                            </div>
                        <?php endif; ?>

                        <?php if (($stats['due_today'] ?? 0) > 0): ?>
                            <div class="insight-item alert alert-warning">
                                <i class="bi bi-calendar-event me-2"></i>
                                <?php echo $stats['due_today']; ?> tasks are due today.
                            </div>
                        <?php endif; ?>

                        <?php if (($stats['in_progress'] ?? 0) === 0 && ($stats['total'] ?? 0) > 0): ?>
                            <div class="insight-item alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No tasks are currently in progress. Consider starting a task.
                            </div>
                        <?php endif; ?>

                        <?php if (($stats['completed'] ?? 0) === 0): ?>
                            <div class="insight-item alert alert-success">
                                <i class="bi bi-trophy me-2"></i>
                                Complete your first task to get started with productivity tracking!
                            </div>
                        <?php else: ?>
                            <div class="insight-item alert alert-success">
                                <i class="bi bi-trophy me-2"></i>
                                Great job! You've completed <?php echo $stats['completed']; ?> tasks.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.priority-chart .progress-bar {
    height: 20px;
}

.priority-low { background-color: #17a2b8 !important; }
.priority-medium { background-color: #ffc107 !important; }
.priority-high { background-color: #dc3545 !important; }

.status-chart .progress-bar {
    height: 20px;
}

.metric-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.metric-item {
    text-align: center;
    padding: 1rem;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background-color: #f8f9fa;
}

.metric-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 0.25rem;
}

.metric-label {
    font-size: 0.875rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.insights-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.insight-item {
    padding: 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    margin-bottom: 0;
}

.insight-item i {
    font-size: 1rem;
}
</style>

<?php
// Include footer
require_once __DIR__ . '/../layout/footer.php';
?>