<?php
/**
 * User Activity Report View
 * Displays user activity statistics and metrics
 */
?>

<div class="dashboard-container">
    <!-- Report Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title"><?php echo $title ?? 'User Activity Report'; ?></h1>
            <p class="dashboard-subtitle">
                Generated on <?php echo date('M d, Y H:i', strtotime($generated_at)); ?> 
                by <?php echo htmlspecialchars($generated_by); ?>
            </p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=index" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Back to Reports
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/ReportController.php?action=user_report&format=csv<?php 
                if (!empty($filters['date_from'])) echo '&date_from=' . urlencode($filters['date_from']);
                if (!empty($filters['date_to'])) echo '&date_to=' . urlencode($filters['date_to']);
                if (!empty($filters['user_id'])) echo '&user_id=' . urlencode($filters['user_id']);
            ?>" class="btn btn-success">
                <i class="bi bi-download"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo $statistics['total_users'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Total Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-success"><?php echo $statistics['active_users'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Active Users</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-primary"><?php echo $statistics['total_tasks_created'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Tasks Created</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h3 class="mb-0 text-info"><?php echo $statistics['total_tasks_completed'] ?? 0; ?></h3>
                    <p class="text-muted mb-0">Tasks Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-people me-2"></i>User Activity Details</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($users)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Tasks Created</th>
                                <th>Tasks Assigned</th>
                                <th>Tasks Completed</th>
                                <th>Comments</th>
                                <th>Files Uploaded</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo ucfirst($user['role'] ?? 'Member'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['tasks_created'] ?? 0; ?></td>
                                    <td><?php echo $user['tasks_assigned'] ?? 0; ?></td>
                                    <td>
                                        <span class="text-success">
                                            <?php echo $user['tasks_completed'] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $user['comments_count'] ?? 0; ?></td>
                                    <td><?php echo $user['attachments_count'] ?? 0; ?></td>
                                    <td>
                                        <?php if (!empty($user['last_activity'])): ?>
                                            <?php echo date('M d, Y H:i', strtotime($user['last_activity'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No activity</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted"></i>
                    <p class="text-muted mt-3">No user data available.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
