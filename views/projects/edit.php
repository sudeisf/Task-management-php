<?php
/**
 * Edit Project View
 */

$pageTitle = 'Edit Project';
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? $_POST ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="bi bi-pencil me-2"></i>Edit Project</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="?action=update&id=<?= $project['id'] ?>" enctype="multipart/form-data">
                        <!-- Project Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo isset($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" 
                                   value="<?= htmlspecialchars($formData['name'] ?? $project['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['name'][0]; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="4"><?= htmlspecialchars($formData['description'] ?? $project['description'] ?? '') ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description'][0]; ?></div>
                            <?php endif; ?>
                        </div>


                        <!-- Dates -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="start_date" class="form-label">Start Date</label>
                                <input type="date" class="form-control <?php echo isset($errors['start_date']) ? 'is-invalid' : ''; ?>" id="start_date" name="start_date" 
                                       value="<?= $formData['start_date'] ?? $project['start_date'] ?? '' ?>">
                                <?php if (isset($errors['start_date'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['start_date'][0]; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="end_date" class="form-label">End Date</label>
                                <input type="date" class="form-control <?php echo isset($errors['end_date']) ? 'is-invalid' : ''; ?>" id="end_date" name="end_date" 
                                       value="<?= $formData['end_date'] ?? $project['end_date'] ?? '' ?>">
                                <?php if (isset($errors['end_date'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['end_date'][0]; ?></div>
                                <?php endif; ?>
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
                                    <?php 
                                    if ($user['role'] === 'manager' || $user['role'] === 'admin'): 
                                        // Strictly block self-assignment
                                        if ($user['id'] == $_SESSION['user_id']) continue;
                                    ?>
                                        <option value="<?= $user['id'] ?>" 
                                                <?= in_array($user['id'], $currentManagerIds) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['full_name']) ?> (<?= $user['role'] ?>)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple managers</small>
                        </div>

                        <!-- Attachment -->
                        <div class="mb-4">
                            <label for="attachment" class="form-label">Add Attachment (Optional)</label>
                            <input type="file" class="form-control" id="attachment" name="attachment">
                            <div class="form-text">Allowed formats: jpg, png, pdf, doc, txt, zip. Max size: 5MB.</div>
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
