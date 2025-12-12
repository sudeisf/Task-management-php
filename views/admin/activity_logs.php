<?php
/**
 * Admin Activity Logs View
 * Display system-wide activity logs
 */

$pageTitle = 'Activity Logs';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Activity Logs
            </h2>
            <p class="text-muted">System-wide activity tracking</p>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <?php if (empty($activities)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No activities recorded yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">
                                            <?= isset($activity['created_at']) ? date('M d, Y H:i', strtotime($activity['created_at'])) : 'N/A' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($activity['user_name'] ?? 'System') ?></strong>
                                    </td>
                                    <td>
                                        <?php
                                        $actionColors = [
                                            'login' => 'success',
                                            'logout' => 'secondary',
                                            'create' => 'primary',
                                            'update' => 'info',
                                            'delete' => 'danger',
                                            'task_created' => 'primary',
                                            'task_updated' => 'info',
                                            'task_completed' => 'success',
                                            'task_deleted' => 'danger',
                                            'user_registered' => 'success',
                                            'user_updated' => 'info',
                                            'user_deleted' => 'danger',
                                            'project_created' => 'primary',
                                            'project_updated' => 'info'
                                        ];
                                        $actionColor = $actionColors[$activity['action']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $actionColor ?>">
                                            <?= htmlspecialchars(str_replace('_', ' ', ucfirst($activity['action']))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($activity['description'] ?? '') ?>
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace">
                                            <?= htmlspecialchars($activity['ip_address'] ?? 'N/A') ?>
                                        </small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Activity logs pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?action=activity_logs&page=<?= $page - 1 ?>">Previous</a>
                                </li>
                            <?php endif; ?>

                            <?php 
                            // Show max 5 page numbers
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?action=activity_logs&page=1">1</a>
                                </li>
                                <?php if ($startPage > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?action=activity_logs&page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="?action=activity_logs&page=<?= $totalPages ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?action=activity_logs&page=<?= $page + 1 ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Activity Count -->
                <div class="text-center text-muted mt-3">
                    <small>
                        Showing <?= count($activities) ?> of <?= $totalActivities ?> activities
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.table th {
    font-weight: 600;
    color: #6c757d;
    border-bottom: 2px solid #dee2e6;
}

.table tbody tr:hover {
    background-color: #f8f9fa;
}

.font-monospace {
    font-family: 'Courier New', Courier, monospace;
}
</style>
