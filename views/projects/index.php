<?php
/**
 * Projects List View
 * Shows all projects based on user role
 */

$pageTitle = 'Projects';
$currentUser = Auth::user();
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2 class="mb-0">
                <i class="bi bi-folder2-open me-2"></i>Projects
            </h2>
        </div>
        <div class="col-md-6 text-end">
            <?php if ($userRole === 'admin'): ?>
                <a href="?action=create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Create Project
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
            <?= $flash['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Projects Grid -->
    <?php if (empty($projects)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <?php if ($userRole === 'admin'): ?>
                No projects found. <a href="?action=create">Create your first project</a>
            <?php else: ?>
                No projects assigned to you yet.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($projects as $project): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-body">
                            <!-- Project Status Badge -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <a href="?action=show&id=<?= $project['id'] ?>" class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($project['name']) ?>
                                    </a>
                                </h5>
                                <?php
                                $statusColors = [
                                    'planning' => 'secondary',
                                    'in_progress' => 'primary',
                                    'completed' => 'success',
                                    'on_hold' => 'warning'
                                ];
                                $statusLabels = [
                                    'planning' => 'To Do',
                                    'in_progress' => 'In Progress',
                                    'completed' => 'Completed',
                                    'on_hold' => 'On Hold'
                                ];
                                $statusColor = $statusColors[$project['status']] ?? 'secondary';
                                $statusLabel = $statusLabels[$project['status']] ?? ucfirst($project['status']);
                                ?>
                                <span class="badge bg-<?= $statusColor ?>">
                                    <?= $statusLabel ?>
                                </span>
                            </div>

                            <!-- Project Description -->
                            <p class="card-text text-muted small mb-3">
                                <?= truncateText($project['description'] ?? 'No description', 100) ?>
                            </p>

                            <!-- Project Stats -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-list-task text-primary me-2"></i>
                                        <small>
                                            <strong><?= $project['total_tasks'] ?? 0 ?></strong> Tasks
                                        </small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <small>
                                            <strong><?= $project['completed_tasks'] ?? 0 ?></strong> Done
                                        </small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-people text-info me-2"></i>
                                        <small>
                                            <strong><?= $project['team_size'] ?? 0 ?></strong> Members
                                        </small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person text-secondary me-2"></i>
                                        <small class="text-truncate">
                                            <?= htmlspecialchars($project['creator_name'] ?? 'Unknown') ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <?php
                            $totalTasks = $project['total_tasks'] ?? 0;
                            $completedTasks = $project['completed_tasks'] ?? 0;
                            $progress = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Progress</small>
                                    <small class="text-muted"><?= $progress ?>%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?= $progress ?>%" 
                                         aria-valuenow="<?= $progress ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <a href="?action=show&id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-eye me-1"></i>View
                                </a>
                                <a href="<?= BASE_URL ?>/controller/TaskController.php?project_id=<?= $project['id'] ?>" 
                                   class="btn btn-sm btn-outline-secondary flex-fill">
                                    <i class="bi bi-list-check me-1"></i>Tasks
                                </a>
                                <?php if ($userRole === 'admin'): ?>
                                    <a href="?action=edit&id=<?= $project['id'] ?>" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Project Dates -->
                        <?php if (!empty($project['start_date']) || !empty($project['end_date'])): ?>
                            <div class="card-footer bg-light small text-muted">
                                <?php if (!empty($project['start_date'])): ?>
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= formatDate($project['start_date']) ?>
                                <?php endif; ?>
                                <?php if (!empty($project['end_date'])): ?>
                                    <i class="bi bi-arrow-right mx-1"></i>
                                    <?= formatDate($project['end_date']) ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-shadow {
    transition: box-shadow 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>
