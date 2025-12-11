<?php
/**
 * Task Report View
 * Displays detailed task report with filters and statistics
 */
?>

<div class="dashboard-container">
    <!-- Report Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title"><?php echo $title ?? 'Task Report'; ?></h1>
            <p class="dashboard-subtitle">
                Generated on <?php echo date('M d, Y H:i', strtotime($generated_at)); ?> 
                by <?php echo htmlspecialchars($generated_by); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=index" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Reports
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=task_report&format=csv<?php 
                if (!empty($filters['date_from'])) echo '&date_from=' . urlencode($filters['date_from']);
                if (!empty($filters['date_to'])) echo '&date_to=' . urlencode($filters['date_to']);
                if (!empty($filters['status'])) echo '&status=' . urlencode($filters['status']);
                if (!empty($filters['priority_id'])) echo '&priority_id=' . urlencode($filters['priority_id']);
                if (!empty($filters['category_id'])) echo '&category_id=' . urlencode($filters['category_id']);
            ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/controller/ReportController.php" class="row g-3">
                <input type="hidden" name="action" value="task_report">
                
                <div class="col-md-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="todo" <?php echo ($filters['status'] ?? '') === 'todo' ? 'selected' : ''; ?>>To Do</option>
                        <option value="in_progress" <?php echo ($filters['status'] ?? '') === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo ($filters['status'] ?? '') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="priority_id" class="form-label">Priority</label>
                    <select class="form-select" id="priority_id" name="priority_id">
                        <option value="">All Priorities</option>
                        <option value="1" <?php echo ($filters['priority_id'] ?? '') == '1' ? 'selected' : ''; ?>>Low</option>
                        <option value="2" <?php echo ($filters['priority_id'] ?? '') == '2' ? 'selected' : ''; ?>>Medium</option>
                        <option value="3" <?php echo ($filters['priority_id'] ?? '') == '3' ? 'selected' : ''; ?>>High</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $statistics['total_tasks'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Tasks</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?php echo $statistics['completed_tasks'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?php echo $statistics['in_progress_tasks'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">In Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-danger"><?php echo $statistics['overdue_tasks'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Overdue</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Completion Rate -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span>Completion Rate</span>
                <strong><?php echo $statistics['completion_rate'] ?? 0; ?>%</strong>
            </div>
            <div class="progress" style="height: 25px;">
                <div class="progress-bar bg-success" role="progressbar" 
                     style="width: <?php echo $statistics['completion_rate'] ?? 0; ?>%"
                     aria-valuenow="<?php echo $statistics['completion_rate'] ?? 0; ?>" 
                     aria-valuemin="0" aria-valuemax="100">
                    <?php echo $statistics['completion_rate'] ?? 0; ?>%
                </div>
            </div>
        </div>
    </div>

    <!-- Tasks Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-list-task me-2"></i>Tasks</h5>
        </div>
        <div class="card-body">
            <?php if (is_array($tasks) && !empty($tasks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Category</th>
                                <th>Assigned To</th>
                                <th>Deadline</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?php echo $task['id']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-status-<?php echo $task['status']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-priority-<?php echo $task['priority_name'] ?? 'medium'; ?>">
                                            <?php echo ucfirst($task['priority_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['category_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($task['assignee_name'] ?? 'Unassigned'); ?></td>
                                    <td>
                                        <?php if ($task['deadline']): ?>
                                            <span class="<?php echo (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'text-danger' : ''; ?>">
                                                <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No deadline</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($task['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-3">No tasks found matching the selected filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
