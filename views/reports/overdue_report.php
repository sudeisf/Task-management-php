<?php
/**
 * Overdue Tasks Report View
 * Displays all overdue tasks
 */
?>

<div class="dashboard-container">
    <!-- Report Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title text-danger">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $title ?? 'Overdue Tasks Report'; ?>
            </h1>
            <p class="dashboard-subtitle">
                Generated on <?php echo date('M d, Y H:i', strtotime($generated_at)); ?> 
                by <?php echo htmlspecialchars($generated_by); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=index" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Reports
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=overdue_report&format=csv" class="btn btn-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="card mb-4 border-danger">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-0 text-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?php echo $total_overdue ?? 0; ?> Overdue Tasks
                    </h4>
                    <p class="text-muted mb-0 mt-2">
                        These tasks have passed their deadline and require immediate attention.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="display-4 text-danger">
                        <?php echo $total_overdue ?? 0; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overdue Tasks Table -->
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h5 class="card-title mb-0"><i class="bi bi-list-task me-2"></i>Overdue Tasks Details</h5>
        </div>
        <div class="card-body">
            <?php if (is_array($tasks) && !empty($tasks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Created By</th>
                                <th>Priority</th>
                                <th>Deadline</th>
                                <th>Days Overdue</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): 
                                $daysOverdue = floor((time() - strtotime($task['deadline'])) / (60 * 60 * 24));
                            ?>
                                <tr class="table-danger">
                                    <td><?php echo $task['id']; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>" class="text-danger fw-bold">
                                            <?php echo htmlspecialchars($task['title']); ?>
                                        </a>
                                        <?php if (!empty($task['description'])): ?>
                                            <br><small class="text-muted">
                                                <?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?>
                                                <?php echo strlen($task['description']) > 50 ? '...' : ''; ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($task['assignee_name'])): ?>
                                            <i class="bi bi-person-circle me-1"></i>
                                            <?php echo htmlspecialchars($task['assignee_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($task['creator_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-priority-<?php echo $task['priority_name'] ?? 'medium'; ?>">
                                            <?php echo ucfirst($task['priority_name'] ?? 'Medium'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-danger">
                                            <i class="bi bi-calendar-x me-1"></i>
                                            <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">
                                            <?php echo $daysOverdue; ?> day<?php echo $daysOverdue != 1 ? 's' : ''; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=edit&id=<?php echo $task['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-check-circle display-4 text-success"></i>
                    <h4 class="mt-3 text-success">Great! No Overdue Tasks</h4>
                    <p class="text-muted">All tasks are on track or completed.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
