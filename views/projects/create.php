<?php
/**
 * Create Project View
 */

$pageTitle = 'Create Project';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Project</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=store" enctype="multipart/form-data">
                        <!-- Project Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                        </div>

                        <!-- Dates -->
                        <?php $today = date('Y-m-d'); ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" min="<?= $today ?>">
                                <small class="text-muted">Cannot be in the past</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" min="<?= $today ?>">
                                <small class="text-muted">Cannot be in the past or before start date</small>
                            </div>
                        </div>

<script>
// Update end_date min value when start_date changes
document.getElementById('start_date').addEventListener('change', function() {
    const endDateInput = document.getElementById('end_date');
    if (this.value) {
        endDateInput.min = this.value;
        // Clear end_date if it's before the new start_date
        if (endDateInput.value && endDateInput.value < this.value) {
            endDateInput.value = '';
        }
    }
});
</script>

                        <!-- Assign Managers -->
                        <div class="mb-3">
                            <label class="form-label">Assign Managers</label>
                            <select class="form-select" name="managers[]" multiple size="5">
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['role'] === 'manager' || $user['role'] === 'admin'): ?>
                                        <option value="<?= $user['id'] ?>">
                                            <?= htmlspecialchars($user['full_name']) ?> (<?= $user['role'] ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple managers</small>
                        </div>

                        <!-- Attachments -->
                        <div class="mb-3">
                            <label for="attachment" class="form-label">Attachments</label>
                            <input type="file" class="form-control" id="attachment" name="attachment">
                            <small class="text-muted">You can attach a file (e.g., project proposal, guidelines)</small>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Create Project
                            </button>
                            <a href="?" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
