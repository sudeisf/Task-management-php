<?php
/**
 * Edit Project View
 */

$pageTitle = 'Edit Project';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Project</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=update&id=<?= $project['id'] ?>">
                        <!-- Project Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($project['name']) ?>" required>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $project['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="on_hold" <?= $project['status'] === 'on_hold' ? 'selected' : '' ?>>On Hold</option>
                                <option value="completed" <?= $project['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="archived" <?= $project['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>

                        <!-- Dates -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" 
                                       value="<?= $project['start_date'] ?? '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" 
                                       value="<?= $project['end_date'] ?? '' ?>">
                            </div>
                        </div>

                        <!-- Assign Managers -->
                        <div class="mb-3">
                            <label class="form-label">Assign Managers</label>
                            <?php
                            $currentManagerIds = array_column($currentManagers, 'id');
                            ?>
                            <select class="form-select" name="managers[]" multiple size="5">
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['role'] === 'manager' || $user['role'] === 'admin'): ?>
                                        <option value="<?= $user['id'] ?>" 
                                                <?= in_array($user['id'], $currentManagerIds) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['full_name']) ?> (<?= $user['role'] ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple managers</small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-check-circle me-1"></i>Update Project
                            </button>
                            <a href="?action=show&id=<?= $project['id'] ?>" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                            <button type="button" class="btn btn-danger ms-auto" 
                                    onclick="if(confirm('Delete this project? All tasks will be deleted!')) { document.getElementById('deleteForm').submit(); }">
                                <i class="bi bi-trash me-1"></i>Delete Project
                            </button>
                        </div>
                    </form>

                    <!-- Delete Form (separate) -->
                    <form id="deleteForm" method="POST" action="?action=delete&id=<?= $project['id'] ?>" style="display:none;">
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
