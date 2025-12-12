<?php
/**
 * Admin User Form View
 * Create or edit user
 */

$pageTitle = isset($user) ? 'Edit User' : 'Create User';
$isEdit = isset($user);
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-0">
                <i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?> me-2"></i><?= $pageTitle ?>
            </h2>
        </div>
    </div>

    <!-- User Form -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="?action=<?= $isEdit ? 'update_user' : 'store_user' ?><?= $isEdit ? '&id=' . $user['id'] : '' ?>">
                        
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?= $isEdit ? htmlspecialchars($user['full_name']) : '' ?>" 
                                   required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?= $isEdit ? htmlspecialchars($user['email']) : '' ?>" 
                                   required>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="admin" <?= ($isEdit && $user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="manager" <?= ($isEdit && $user['role'] === 'manager') ? 'selected' : '' ?>>Manager</option>
                                <option value="member" <?= ($isEdit && $user['role'] === 'member') ? 'selected' : '' ?>>Member</option>
                            </select>
                            <div class="form-text">
                                <strong>Admin:</strong> Full system access<br>
                                <strong>Manager:</strong> Can manage projects and tasks<br>
                                <strong>Member:</strong> Can only work on assigned tasks
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= ($isEdit && $user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($isEdit && $user['status'] === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <?php if (!$isEdit): ?>
                            <!-- Password (Create Only) -->
                            <div class="mb-3">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control" 
                                       id="password" 
                                       name="password" 
                                       required>
                                <div class="form-text">
                                    Password must contain at least 8 characters, including uppercase, lowercase, number, and special character.
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" 
                                       class="form-control" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       required>
                            </div>
                        <?php else: ?>
                            <!-- Change Password (Edit Only) -->
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="change_password" 
                                           name="change_password"
                                           onchange="togglePasswordFields()">
                                    <label class="form-check-label" for="change_password">
                                        Change Password
                                    </label>
                                </div>
                            </div>

                            <div id="password_fields" style="display: none;">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password" 
                                           name="new_password">
                                    <div class="form-text">
                                        Password must contain at least 8 characters, including uppercase, lowercase, number, and special character.
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_new_password" class="form-label">Confirm New Password</label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_new_password" 
                                           name="confirm_new_password">
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="?action=users" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-<?= $isEdit ? 'check' : 'plus' ?>-circle me-1"></i>
                                <?= $isEdit ? 'Update User' : 'Create User' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordFields() {
    const checkbox = document.getElementById('change_password');
    const fields = document.getElementById('password_fields');
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_new_password');
    
    if (checkbox.checked) {
        fields.style.display = 'block';
        newPassword.required = true;
        confirmPassword.required = true;
    } else {
        fields.style.display = 'none';
        newPassword.required = false;
        confirmPassword.required = false;
        newPassword.value = '';
        confirmPassword.value = '';
    }
}

// Password validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password') || document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password') || document.getElementById('confirm_new_password');
    
    if (password && password.value) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            alert('Passwords do not match!');
            return false;
        }
    }
});
</script>

<style>
.form-label {
    font-weight: 600;
    color: #495057;
}

.form-text {
    font-size: 0.875rem;
}

.card {
    border-radius: 8px;
}
</style>
