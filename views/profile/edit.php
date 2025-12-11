<?php
/**
 * Profile Edit View
 * Form for editing user profile
 */
?>

<div class="dashboard-container">
    <!-- Profile Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">Edit Profile</h1>
            <p class="dashboard-subtitle">Update your account information</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/controller/ProfileController.php?action=index" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Profile
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Avatar Upload -->
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Profile Picture</h5>
                </div>
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo BASE_URL; ?>/public/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="Profile Picture" 
                                 class="rounded-circle" 
                                 id="avatar-preview"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-placeholder rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                 id="avatar-preview"
                                 style="width: 150px; height: 150px; background-color: #e9ecef; font-size: 3rem; color: #6c757d;">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <form action="<?php echo BASE_URL; ?>/controller/ProfileController.php?action=uploadAvatar" 
                          method="POST" 
                          enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <input type="file" 
                                   class="form-control" 
                                   id="avatar" 
                                   name="avatar" 
                                   accept="image/*"
                                   onchange="previewAvatar(this)">
                            <small class="text-muted">Max 2MB. JPG, PNG, or GIF</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload"></i> Upload Picture
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Profile Information Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/controller/ProfileController.php?action=update" 
                          method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Full Name -->
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control" 
                                   id="full_name" 
                                   name="full_name" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                   required>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                        </div>

                        <hr class="my-4">
                        
                        <h6 class="mb-3">Change Password</h6>
                        <p class="text-muted small">Leave blank if you don't want to change your password</p>

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="current_password" 
                                   name="current_password">
                        </div>

                        <!-- New Password -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="new_password" 
                                   name="new_password"
                                   minlength="6">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <!-- Confirm Password -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password"
                                   minlength="6">
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo BASE_URL; ?>/controller/ProfileController.php?action=index" 
                               class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                // Replace placeholder with image
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'Profile Picture';
                img.className = 'rounded-circle';
                img.id = 'avatar-preview';
                img.style.cssText = 'width: 150px; height: 150px; object-fit: cover;';
                preview.parentNode.replaceChild(img, preview);
            }
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
