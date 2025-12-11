<?php
/**
 * Profile Index View
 * Display user profile information
 */
?>

<div class="dashboard-container">
    <!-- Profile Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">My Profile</h1>
            <p class="dashboard-subtitle">View and manage your account information</p>
        </div>
        <div>
            <a href="<?php echo BASE_URL; ?>/controller/ProfileController.php?action=edit" class="btn btn-primary">
                <i class="bi bi-pencil"></i> Edit Profile
            </a>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo BASE_URL; ?>/public/uploads/avatars/<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="Profile Picture" 
                                 class="rounded-circle" 
                                 style="width: 150px; height: 150px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-placeholder rounded-circle mx-auto d-flex align-items-center justify-content-center" 
                                 style="width: 150px; height: 150px; background-color: #e9ecef; font-size: 3rem; color: #6c757d;">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h4 class="mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h4>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                </div>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Full Name:</strong>
                        </div>
                        <div class="col-sm-8">
                            <?php echo htmlspecialchars($user['full_name']); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Email Address:</strong>
                        </div>
                        <div class="col-sm-8">
                            <?php echo htmlspecialchars($user['email']); ?>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Role:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="badge bg-primary"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Account Status:</strong>
                        </div>
                        <div class="col-sm-8">
                            <span class="badge bg-success"><?php echo ucfirst($user['status'] ?? 'active'); ?></span>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong>Member Since:</strong>
                        </div>
                        <div class="col-sm-8">
                            <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
