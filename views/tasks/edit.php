<?php
/**
 * Edit Task View
 * Form for editing existing tasks
 */

// Include header
require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $task - the task data to edit
// $categories - available categories
// $priorities - available priorities
// $users - available users for assignment
// $errors - validation errors (if any)
?>

<div class="dashboard-container">
    <!-- Edit Task Header -->
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Edit Task</h1>
            <p class="dashboard-subtitle">Update task details and settings.</p>
        </div>
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-eye me-2"></i>
                View Task
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Tasks
            </a>
        </div>
    </div>

    <!-- Edit Task Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-pencil-square me-2"></i>
                        Edit Task: <?php echo htmlspecialchars($task['title']); ?>
                    </h3>
                    <span class="badge badge-status-<?php echo $task['status']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                    </span>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/controller/TaskController.php?action=update&id=<?php echo $task['id']; ?>" method="POST" id="edit-task-form">
                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Task Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                                   id="title" name="title" required
                                   placeholder="Enter task title..."
                                   value="<?php echo htmlspecialchars($task['title'] ?? $_POST['title'] ?? ''); ?>">
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                                      id="description" name="description" rows="4"
                                      placeholder="Describe the task in detail..."><?php echo htmlspecialchars($task['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Category and Priority Row -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>"
                                        id="category_id" name="category_id">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"
                                                <?php echo (($task['category_id'] ?? $_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['category_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['category_id']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="priority_id" class="form-label">
                                    Priority <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?php echo isset($errors['priority_id']) ? 'is-invalid' : ''; ?>"
                                        id="priority_id" name="priority_id" required>
                                    <option value="">Select Priority</option>
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?php echo $priority['id']; ?>"
                                                <?php echo (($task['priority_id'] ?? $_POST['priority_id'] ?? '') == $priority['id']) ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($priority['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['priority_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['priority_id']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status and Deadline Row -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>"
                                        id="status" name="status" required>
                                    <option value="todo" <?php echo (($task['status'] ?? $_POST['status'] ?? '') == 'todo') ? 'selected' : ''; ?>>To Do</option>
                                    <option value="in_progress" <?php echo (($task['status'] ?? $_POST['status'] ?? '') == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo (($task['status'] ?? $_POST['status'] ?? '') == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="deadline" class="form-label">Deadline</label>
                                <input type="date" class="form-control <?php echo isset($errors['deadline']) ? 'is-invalid' : ''; ?>"
                                       id="deadline" name="deadline"
                                       value="<?php echo htmlspecialchars($task['deadline'] ?? $_POST['deadline'] ?? ''); ?>">
                                <?php if (isset($errors['deadline'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['deadline']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Assigned To -->
                        <div class="mb-3">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-select <?php echo isset($errors['assigned_to']) ? 'is-invalid' : ''; ?>"
                                    id="assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($users as $user): ?>
                                    <?php 
                                    // Exclude admins from assignment
                                    if ($user['role'] === 'admin') continue;
                                    
                                    // If current user is a manager, exclude other managers (but allow self-assignment)
                                    if ($userRole === 'manager' && $user['role'] === 'manager' && $user['id'] != $currentUser['id']) {
                                        continue;
                                    }
                                    ?>
                                    <option value="<?php echo $user['id']; ?>"
                                            <?php echo (($task['assigned_to'] ?? $_POST['assigned_to'] ?? '') == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['assigned_to'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['assigned_to']; ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Task Metadata (Read-only) -->
                        <div class="mb-4">
                            <h5 class="mb-3">Task Information</h5>
                            <div class="row text-muted small">
                                <div class="col-md-6">
                                    <strong>Created by:</strong> <?php echo htmlspecialchars($task['creator_name']); ?><br>
                                    <strong>Created on:</strong> <?php echo date('M d, Y H:i', strtotime($task['created_at'])); ?>
                                </div>
                                <div class="col-md-6">
                                    <strong>Last updated:</strong> <?php echo date('M d, Y H:i', strtotime($task['updated_at'])); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Status Updates -->
                        <div class="mb-4">
                            <label class="form-label">Quick Status Updates</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="markCompleted()">
                                    <i class="bi bi-check-circle"></i> Mark Completed
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="markInProgress()">
                                    <i class="bi bi-play-circle"></i> Mark In Progress
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="markTodo()">
                                    <i class="bi bi-circle"></i> Mark To Do
                                </button>
                                <?php if ($task['status'] !== 'completed'): ?>
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="setDueToday()">
                                        <i class="bi bi-calendar-event"></i> Due Today
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_assignee" name="notify_assignee" checked>
                                    <label class="form-check-label" for="notify_assignee">
                                        Notify assignee of changes
                                    </label>
                                </div>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Update Task
                                </button>
                                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=delete&id=<?php echo $task['id']; ?>"
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to delete this task? This action cannot be undone.')">
                                    <i class="bi bi-trash me-2"></i>
                                    Delete Task
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activity History -->
            <?php if (!empty($task['activities'])): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            Recent Activity
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="activity-timeline">
                            <?php foreach (array_slice($task['activities'], 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="bi <?php echo getActivityIcon($activity['action']); ?>"></i>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">
                                            <?php echo htmlspecialchars($activity['action']); ?>
                                            <?php if (!empty($activity['details'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($activity['details']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-time">
                                            <?php echo timeAgo($activity['created_at']); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.activity-timeline {
    position: relative;
    padding-left: 40px;
}

.activity-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.activity-item {
    position: relative;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
}

.activity-item:last-child {
    margin-bottom: 0;
    border-bottom: none;
}

.activity-icon {
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
    background-color: #007bff;
    border: 3px solid white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.activity-content {
    background-color: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
}

.activity-text {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.8rem;
    color: #6c757d;
}
</style>

<script>
// Quick status update functions
function markCompleted() {
    document.getElementById('status').value = 'completed';
    showStatusChangeAlert('Task marked as completed!');
}

function markInProgress() {
    document.getElementById('status').value = 'in_progress';
    showStatusChangeAlert('Task marked as in progress!');
}

function markTodo() {
    document.getElementById('status').value = 'todo';
    showStatusChangeAlert('Task marked as to do!');
}

function setDueToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('deadline').value = today;
    showStatusChangeAlert('Deadline set to today!');
}

function showStatusChangeAlert(message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.status-alert');
    existingAlerts.forEach(alert => alert.remove());

    // Create and show new alert
    const alert = document.createElement('div');
    alert.className = 'alert alert-info alert-dismissible fade show status-alert';
    alert.innerHTML = `
        <i class="bi bi-info-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const form = document.getElementById('edit-task-form');
    form.parentNode.insertBefore(alert, form);

    // Auto-dismiss after 3 seconds
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    }, 3000);
}

// Form validation
document.getElementById('edit-task-form').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    const priority = document.getElementById('priority_id').value;

    if (!title) {
        e.preventDefault();
        alert('Please enter a task title.');
        document.getElementById('title').focus();
        return;
    }

    if (!priority) {
        e.preventDefault();
        alert('Please select a priority level.');
        document.getElementById('priority_id').focus();
        return;
    }

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-spinner bi-spin me-2"></i>Updating...';
    submitBtn.disabled = true;
});

// Helper functions
function getActivityIcon(action) {
    if (action.includes('created')) return 'bi-plus-circle';
    if (action.includes('updated')) return 'bi-pencil';
    if (action.includes('completed')) return 'bi-check-circle';
    if (action.includes('comment')) return 'bi-chat';
    return 'bi-circle';
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