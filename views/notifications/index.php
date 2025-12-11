<?php
/**
 * Notifications Index View
 * Display all notifications with filtering
 */
?>

<div class="dashboard-container">
    <!-- Notifications Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">Notifications</h1>
            <p class="dashboard-subtitle">
                <?php echo $unreadCount; ?> unread notification<?php echo $unreadCount != 1 ? 's' : ''; ?>
            </p>
        </div>
        <div>
            <?php if ($unreadCount > 0): ?>
                <form action="<?php echo BASE_URL; ?>/controller/NotificationController.php?action=markAllAsRead" 
                      method="POST" 
                      style="display: inline;">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-check-all"></i> Mark All as Read
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" 
               href="<?php echo BASE_URL; ?>/controller/NotificationController.php?filter=all">
                All
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $filter === 'unread' ? 'active' : ''; ?>" 
               href="<?php echo BASE_URL; ?>/controller/NotificationController.php?filter=unread">
                Unread <?php if ($unreadCount > 0): ?><span class="badge bg-primary"><?php echo $unreadCount; ?></span><?php endif; ?>
            </a>
        </li>
    </ul>

    <!-- Notifications List -->
    <div class="card">
        <div class="card-body p-0">
            <?php if (is_array($notifications) && !empty($notifications)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($notifications as $notification): ?>
                        <div class="list-group-item <?php echo $notification['is_read'] ? '' : 'list-group-item-primary'; ?>">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-<?php echo getActivityIcon($notification['type']); ?> me-2"></i>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($notification['message']); ?></h6>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo timeAgo($notification['created_at']); ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if (!$notification['is_read']): ?>
                                        <button class="btn btn-sm btn-outline-primary mark-read-btn" 
                                                data-id="<?php echo $notification['id']; ?>">
                                            <i class="bi bi-check"></i> Mark Read
                                        </button>
                                    <?php endif; ?>
                                    <form action="<?php echo BASE_URL; ?>/controller/NotificationController.php?action=delete" 
                                          method="POST" 
                                          style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this notification?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash display-4 text-muted"></i>
                    <p class="text-muted mt-3">No notifications to display</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Mark notification as read via AJAX
document.querySelectorAll('.mark-read-btn').forEach(button => {
    button.addEventListener('click', function() {
        const notificationId = this.dataset.id;
        const formData = new FormData();
        formData.append('id', notificationId);

        fetch('<?php echo BASE_URL; ?>/controller/NotificationController.php?action=markAsRead', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update UI
                location.reload();
            } else {
                alert('Failed to mark notification as read');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });
});
</script>
