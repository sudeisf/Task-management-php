<?php
/**
 * Recent Activity View
 * Displays recent activities and task updates
 */

// Include header
require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $recentActivities - recent activities array
// $activityFilters - filter options
?>

<div class="dashboard-container">
    <!-- Activity Header -->
    <div class="dashboard-header">
        <h1 class="dashboard-title">Recent Activity</h1>
        <p class="dashboard-subtitle">Track all recent actions and updates in your task management system.</p>
    </div>

    <!-- Activity Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="user_filter" class="form-label">User</label>
                    <select name="user_id" id="user_filter" class="form-select">
                        <option value="">All Users</option>
                        <?php foreach ($activityFilters['users'] as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['user_id']) && $_GET['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="action_filter" class="form-label">Action Type</label>
                    <select name="action_type" id="action_filter" class="form-select">
                        <option value="">All Actions</option>
                        <option value="task_created" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'task_created') ? 'selected' : ''; ?>>Task Created</option>
                        <option value="task_updated" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'task_updated') ? 'selected' : ''; ?>>Task Updated</option>
                        <option value="task_completed" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'task_completed') ? 'selected' : ''; ?>>Task Completed</option>
                        <option value="comment_added" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'comment_added') ? 'selected' : ''; ?>>Comment Added</option>
                        <option value="attachment_uploaded" <?php echo (isset($_GET['action_type']) && $_GET['action_type'] == 'attachment_uploaded') ? 'selected' : ''; ?>>Attachment Uploaded</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" name="date_from" id="date_from" class="form-control"
                           value="<?php echo $_GET['date_from'] ?? ''; ?>">
                </div>

                <div class="col-md-3">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" name="date_to" id="date_to" class="form-control"
                           value="<?php echo $_GET['date_to'] ?? ''; ?>">
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                    <a href="<?php echo BASE_URL; ?>/controller/DashboardController.php?action=recent_activity" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Timeline -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="bi bi-activity me-2"></i>
                Activity Timeline
            </h3>
        </div>
        <div class="card-body">
            <?php if (!empty($recentActivities)): ?>
                <div class="timeline">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="timeline-item">
                            <div class="timeline-marker <?php echo getActivityTypeClass($activity['action']); ?>">
                                <i class="bi <?php echo getActivityIcon($activity['action']); ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="timeline-header">
                                    <span class="timeline-user">
                                        <?php echo htmlspecialchars($activity['full_name'] ?? 'Unknown User'); ?>
                                    </span>
                                    <span class="timeline-action">
                                        <?php echo getActivityActionText($activity['action']); ?>
                                    </span>
                                    <?php if (!empty($activity['task_title'])): ?>
                                        <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $activity['task_id']; ?>"
                                           class="timeline-task">
                                            "<?php echo htmlspecialchars($activity['task_title']); ?>"
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($activity['details'])): ?>
                                    <div class="timeline-details">
                                        <?php echo htmlspecialchars($activity['details']); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="timeline-meta">
                                    <span class="timeline-date">
                                        <i class="bi bi-clock"></i>
                                        <?php echo date('M d, Y \a\t H:i', strtotime($activity['created_at'])); ?>
                                    </span>
                                    <span class="timeline-timeago">
                                        <?php echo timeAgo($activity['created_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Load More Button -->
                <div class="text-center mt-4">
                    <button id="load-more-btn" class="btn btn-outline-primary" data-offset="<?php echo count($recentActivities); ?>">
                        <i class="bi bi-arrow-down-circle me-2"></i>
                        Load More Activities
                    </button>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-activity display-4 text-muted mb-3"></i>
                    <h4 class="text-muted">No Activities Found</h4>
                    <p class="text-muted">There are no activities matching your current filters.</p>
                    <a href="<?php echo BASE_URL; ?>/controller/DashboardController.php?action=recent_activity" class="btn btn-primary">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        Reset Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #f8f9fa;
}

.timeline-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.timeline-marker.task { background-color: #007bff; }
.timeline-marker.comment { background-color: #28a745; }
.timeline-marker.attachment { background-color: #ffc107; color: #212529; }
.timeline-marker.user { background-color: #17a2b8; }

.timeline-content {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.timeline-header {
    margin-bottom: 0.5rem;
}

.timeline-user {
    font-weight: 600;
    color: #495057;
}

.timeline-action {
    color: #6c757d;
    margin: 0 0.5rem;
}

.timeline-task {
    color: #007bff;
    text-decoration: none;
    font-weight: 500;
}

.timeline-task:hover {
    text-decoration: underline;
}

.timeline-details {
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    background-color: white;
    border-radius: 4px;
    border-left: 3px solid #007bff;
}

.timeline-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #6c757d;
}

.timeline-date,
.timeline-timeago {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

@media (max-width: 768px) {
    .timeline {
        padding-left: 20px;
    }

    .timeline-marker {
        left: -17px;
        width: 24px;
        height: 24px;
    }

    .timeline-marker i {
        font-size: 0.75rem;
    }

    .timeline-content {
        padding: 0.75rem;
    }

    .timeline-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }
}
</style>

<script>
// Load more activities
document.getElementById('load-more-btn')?.addEventListener('click', function() {
    const btn = this;
    const offset = parseInt(btn.getAttribute('data-offset'));

    btn.innerHTML = '<i class="bi bi-spinner bi-spin me-2"></i>Loading...';
    btn.disabled = true;

    const params = new URLSearchParams(window.location.search);
    params.set('offset', offset);
    params.set('ajax', '1');

    fetch(window.location.pathname + '?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.success && data.activities.length > 0) {
                // Append new activities to timeline
                const timeline = document.querySelector('.timeline');
                data.activities.forEach(activity => {
                    const activityHtml = createActivityHtml(activity);
                    timeline.insertAdjacentHTML('beforeend', activityHtml);
                });

                // Update offset
                btn.setAttribute('data-offset', offset + data.activities.length);

                // Hide button if no more activities
                if (data.activities.length < 20) {
                    btn.style.display = 'none';
                }
            } else {
                btn.style.display = 'none';
            }

            btn.innerHTML = '<i class="bi bi-arrow-down-circle me-2"></i>Load More Activities';
            btn.disabled = false;
        })
        .catch(error => {
            console.error('Error loading more activities:', error);
            btn.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error Loading';
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-arrow-down-circle me-2"></i>Load More Activities';
                btn.disabled = false;
            }, 2000);
        });
});

function createActivityHtml(activity) {
    return `
        <div class="timeline-item">
            <div class="timeline-marker ${getActivityTypeClass(activity.action)}">
                <i class="bi ${getActivityIcon(activity.action)}"></i>
            </div>
            <div class="timeline-content">
                <div class="timeline-header">
                    <span class="timeline-user">${activity.full_name || 'Unknown User'}</span>
                    <span class="timeline-action">${getActivityActionText(activity.action)}</span>
                    ${activity.task_title ? `<a href="${BASE_URL}/controller/TaskController.php?action=show&id=${activity.task_id}" class="timeline-task">"${activity.task_title}"</a>` : ''}
                </div>
                ${activity.details ? `<div class="timeline-details">${activity.details}</div>` : ''}
                <div class="timeline-meta">
                    <span class="timeline-date">
                        <i class="bi bi-clock"></i>
                        ${new Date(activity.created_at).toLocaleString()}
                    </span>
                    <span class="timeline-timeago">${timeAgo(activity.created_at)}</span>
                </div>
            </div>
        </div>
    `;
}

// Helper functions
function getActivityTypeClass(action) {
    if (action.includes('task')) return 'task';
    if (action.includes('comment')) return 'comment';
    if (action.includes('attachment')) return 'attachment';
    if (action.includes('user')) return 'user';
    return 'task';
}

function getActivityIcon(action) {
    if (action.includes('created')) return 'bi-plus-circle';
    if (action.includes('updated')) return 'bi-pencil';
    if (action.includes('completed')) return 'bi-check-circle';
    if (action.includes('comment')) return 'bi-chat';
    if (action.includes('attachment')) return 'bi-paperclip';
    return 'bi-circle';
}

function getActivityActionText(action) {
    const actions = {
        'task_created': 'created task',
        'task_updated': 'updated task',
        'task_completed': 'completed task',
        'comment_added': 'added comment to',
        'attachment_uploaded': 'uploaded attachment to'
    };
    return actions[action] || action.replace('_', ' ');
}

function timeAgo(datetime) {
    const now = new Date();
    const past = new Date(datetime);
    const diff = now - past;

    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return minutes + ' minutes ago';
    if (hours < 24) return hours + ' hours ago';
    return days + ' days ago';
}
</script>

<?php
// Include footer
require_once __DIR__ . '/../layout/footer.php';
?>