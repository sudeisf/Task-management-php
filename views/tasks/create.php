<?php
/**
 * Create Task View
 * Form for creating new tasks
 */

// Include header
require_once __DIR__ . '/../layout/header.php';

// Variables passed from controller:
// $categories - available categories
// $priorities - available priorities
// $users - available users for assignment
// $formData - form data for repopulation
// $errors - validation errors
?>

<div class="dashboard-container">
    <!-- Create Task Header -->
    <div class="dashboard-header">
        <div>
            <h1 class="dashboard-title">Create New Task</h1>
            <p class="dashboard-subtitle">Add a new task to your project management system.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>
            Back to Tasks
        </a>
    </div>

    <!-- Create Task Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-plus-circle me-2"></i>
                        Task Details
                    </h3>
                </div>
                <div class="card-body">
                    <form action="<?php echo BASE_URL; ?>/controller/TaskController.php?action=store" method="POST" id="create-task-form">
                        <!-- Project Selection -->
                        <?php if (isset($project) && $project): ?>
                            <!-- Project is pre-selected -->
                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-folder2-open me-2"></i>
                                Creating task in project: <strong><?= htmlspecialchars($project['name']) ?></strong>
                            </div>
                        <?php else: ?>
                            <!-- Project selection dropdown -->
                            <div class="mb-3">
                                <label for="project_id" class="form-label">
                                    Project <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?php echo isset($errors['project_id']) ? 'is-invalid' : ''; ?>"
                                        id="project_id" name="project_id" required>
                                    <option value="">Select Project</option>
                                    <?php if (isset($projects)): ?>
                                        <?php foreach ($projects as $proj): ?>
                                            <option value="<?php echo $proj['id']; ?>"
                                                    <?php echo (isset($formData['project_id']) && $formData['project_id'] == $proj['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($proj['name']); ?>
                                                <?php if (isset($proj['status'])): ?>
                                                    (<?php echo ucfirst($proj['status']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <?php if (isset($errors['project_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['project_id']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Select the project this task belongs to.</div>
                            </div>
                        <?php endif; ?>

                        <!-- Title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">
                                Task Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                                   id="title" name="title" required
                                   placeholder="Enter task title..."
                                   value="<?php echo htmlspecialchars($formData['title'] ?? ''); ?>">
                            <?php if (isset($errors['title'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['title']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Give your task a clear, descriptive title.</div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                                      id="description" name="description" rows="4"
                                      placeholder="Describe the task in detail..."><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">Provide detailed instructions and requirements for this task.</div>
                        </div>

                        <!-- Priority Row -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="priority_id" class="form-label">
                                    Priority <span class="text-danger">*</span>
                                </label>
                                <select class="form-select <?php echo isset($errors['priority_id']) ? 'is-invalid' : ''; ?>"
                                        id="priority_id" name="priority_id" required>
                                    <option value="">Select Priority</option>
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?php echo $priority['id']; ?>"
                                                <?php echo (isset($formData['priority_id']) && $formData['priority_id'] == $priority['id']) ? 'selected' : ''; ?>>
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
                                    <option value="todo" <?php echo (isset($formData['status']) && $formData['status'] == 'todo') ? 'selected' : 'selected'; ?>>To Do</option>
                                    <option value="in_progress" <?php echo (isset($formData['status']) && $formData['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo (isset($formData['status']) && $formData['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                </select>
                                <?php if (isset($errors['status'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="deadline" class="form-label">Deadline</label>
                                <input type="date" class="form-control <?php echo isset($errors['deadline']) ? 'is-invalid' : ''; ?>"
                                       id="deadline" name="deadline"
                                       value="<?php echo htmlspecialchars($formData['deadline'] ?? ''); ?>">
                                <?php if (isset($errors['deadline'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['deadline']; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Leave empty if no specific deadline.</div>
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
                                            <?php echo (isset($formData['assigned_to']) && $formData['assigned_to'] == $user['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name']); ?> (<?php echo htmlspecialchars($user['role']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['assigned_to'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['assigned_to']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">
                                <?php if ($userRole === 'manager'): ?>
                                    You can assign tasks to yourself or team members.
                                <?php else: ?>
                                    Select a team member to assign this task to.
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="mb-4">
                            <label class="form-label">Quick Actions</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="setHighPriority()">
                                    <i class="bi bi-exclamation-triangle"></i> High Priority
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="setDueToday()">
                                    <i class="bi bi-calendar-event"></i> Due Today
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="setDueTomorrow()">
                                    <i class="bi bi-calendar-plus"></i> Due Tomorrow
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="assignToMe()">
                                    <i class="bi bi-person-check"></i> Assign to Me
                                </button>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="create_another" name="create_another">
                                <label class="form-check-label" for="create_another">
                                    Create another task after saving
                                </label>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Create Task
                                </button>
                                <button type="button" class="btn btn-outline-primary dropdown-toggle dropdown-toggle-split"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button type="submit" name="save_and_start" value="1" class="dropdown-item">
                                        <i class="bi bi-play-circle me-2"></i>
                                        Create & Mark In Progress
                                    </button></li>
                                    <li><button type="submit" name="save_and_assign" value="1" class="dropdown-item">
                                        <i class="bi bi-person-plus me-2"></i>
                                        Create & Assign to Me
                                    </button></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tips Card -->
            <div class="card mt-4 border-info">
                <div class="card-body">
                    <h5 class="card-title text-info">
                        <i class="bi bi-lightbulb me-2"></i>
                        Tips for Creating Effective Tasks
                    </h5>
                    <ul class="mb-0 text-muted">
                        <li>Use clear, actionable titles that describe what needs to be done</li>
                        <li>Provide detailed descriptions with specific requirements and acceptance criteria</li>
                        <li>Set realistic deadlines based on task complexity and team capacity</li>
                        <li>Assign tasks to the most appropriate team member for their skills</li>
                        <li>Use categories to organize and filter tasks more effectively</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quick action functions
function setHighPriority() {
    const prioritySelect = document.getElementById('priority_id');
    // Assuming high priority has ID 3 (based on sample data)
    prioritySelect.value = '3';
    prioritySelect.dispatchEvent(new Event('change'));
}

function setDueToday() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('deadline').value = today;
}

function setDueTomorrow() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('deadline').value = tomorrow.toISOString().split('T')[0];
}

function assignToMe() {
    // This would need to be set server-side or via JavaScript
    // For now, we'll assume the current user ID is available
    <?php if (isset($currentUser)): ?>
        document.getElementById('assigned_to').value = '<?php echo $currentUser['id']; ?>';
    <?php endif; ?>
}

// Form validation enhancement
document.getElementById('create-task-form').addEventListener('submit', function(e) {
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
    const submitBtn = this.querySelector('button[type="submit"]:not([name])');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="bi bi-spinner bi-spin me-2"></i>Creating...';
    submitBtn.disabled = true;

    // Re-enable after a short delay (in case of client-side validation failure)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 2000);
});

// Auto-save draft functionality (optional enhancement)
let autoSaveTimeout;
function autoSaveDraft() {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(() => {
        const formData = new FormData(document.getElementById('create-task-form'));
        // Implement auto-save to localStorage or server
        console.log('Auto-saving draft...');
    }, 30000); // Save every 30 seconds
}

// Add auto-save listeners
document.querySelectorAll('input, textarea, select').forEach(element => {
    element.addEventListener('input', autoSaveDraft);
    element.addEventListener('change', autoSaveDraft);
});
</script>

<style>
.form-check-label {
    cursor: pointer;
}

.btn-group .dropdown-toggle-split {
    border-left: 1px solid rgba(255, 255, 255, 0.15);
}

.card.border-info {
    border-color: #17a2b8 !important;
}

.card.border-info .card-title {
    color: #17a2b8 !important;
}
</style>

<?php
// Include footer
require_once __DIR__ . '/../layout/footer.php';
?>