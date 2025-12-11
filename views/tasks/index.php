<?php
/**
 * Tasks Index View
 * Displays list of tasks with filtering, sorting, and pagination
 */

// Include header
require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $tasks - array of tasks
// $filters - current filter values
// $pagination - pagination data
// $categories - available categories
// $priorities - available priorities
// $users - available users (for admin)
?>

<div class="dashboard-container">
    <!-- Tasks Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">Tasks</h1>
            <p class="dashboard-subtitle">Manage and track all your tasks in one place.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>
            Create Task
        </a>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/controller/TaskController.php" class="row g-3">
                <input type="hidden" name="action" value="index">

                <!-- Search -->
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control"
                           placeholder="Search tasks..."
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                </div>

                <!-- Status Filter -->
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="todo" <?php echo (isset($_GET['status']) && $_GET['status'] == 'todo') ? 'selected' : ''; ?>>To Do</option>
                        <option value="in_progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="col-md-2">
                    <label for="priority_id" class="form-label">Priority</label>
                    <select name="priority_id" id="priority_id" class="form-select">
                        <option value="">All Priorities</option>
                        <?php foreach ($priorities as $priority): ?>
                            <option value="<?php echo $priority['id']; ?>" <?php echo (isset($_GET['priority_id']) && $_GET['priority_id'] == $priority['id']) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($priority['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="col-md-2">
                    <label for="category_id" class="form-label">Category</label>
                    <select name="category_id" id="category_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo (isset($_GET['category_id']) && $_GET['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Assigned To Filter (for admin/manager) -->
                <?php if (hasPermission($userRole, 'manage_users')): ?>
                    <div class="col-md-2">
                        <label for="assigned_to" class="form-label">Assigned To</label>
                        <select name="assigned_to" id="assigned_to" class="form-select">
                            <option value="">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>" <?php echo (isset($_GET['assigned_to']) && $_GET['assigned_to'] == $user['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <!-- Deadline Filters -->
                <div class="col-md-2">
                    <label for="deadline_from" class="form-label">Deadline From</label>
                    <input type="date" name="deadline_from" id="deadline_from" class="form-control"
                           value="<?php echo $_GET['deadline_from'] ?? ''; ?>">
                </div>

                <div class="col-md-2">
                    <label for="deadline_to" class="form-label">Deadline To</label>
                    <input type="date" name="deadline_to" id="deadline_to" class="form-control"
                           value="<?php echo $_GET['deadline_to'] ?? ''; ?>">
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                    <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                    <?php if (!empty($tasks)): ?>
                        <button type="button" class="btn btn-outline-success" onclick="exportTasks()">
                            <i class="bi bi-download"></i> Export CSV
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="bi bi-list-task me-2"></i>
                Tasks (<?php echo $pagination['total'] ?? count($tasks); ?>)
            </h3>

            <!-- View Toggle -->
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm active" id="list-view-btn">
                    <i class="bi bi-list"></i>
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" id="grid-view-btn">
                    <i class="bi bi-grid"></i>
                </button>
            </div>
        </div>

        <div class="card-body">
            <?php if (!empty($tasks)): ?>
                <!-- List View -->
                <div id="list-view" class="tasks-list">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="select-all" class="form-check-input">
                                    </th>
                                    <th>Task</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Deadline</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                    <tr class="task-row <?php echo ($task['status'] === 'completed') ? 'table-light' : ''; ?>">
                                        <td>
                                            <input type="checkbox" class="task-checkbox form-check-input"
                                                   value="<?php echo $task['id']; ?>">
                                        </td>
                                        <td>
                                            <div class="task-info">
                                                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                                   class="task-title <?php echo ($task['status'] === 'completed') ? 'text-decoration-line-through' : ''; ?>">
                                                    <?php echo htmlspecialchars($task['title']); ?>
                                                </a>
                                                <?php if (!empty($task['description'])): ?>
                                                    <small class="text-muted d-block">
                                                        <?php echo htmlspecialchars(substr($task['description'], 0, 100)); ?>
                                                        <?php if (strlen($task['description']) > 100): ?>...<?php endif; ?>
                                                    </small>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    Created by <?php echo htmlspecialchars($task['creator_name']); ?>
                                                    on <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                                </small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-priority-<?php echo $task['priority_name']; ?>">
                                                <?php echo ucfirst($task['priority_name']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-status-<?php echo $task['status']; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($task['assignee_name']): ?>
                                                <span class="text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($task['assignee_name']); ?>">
                                                    <?php echo htmlspecialchars($task['assignee_name']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($task['deadline']): ?>
                                                <span class="<?php echo (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'text-danger' : ''; ?>">
                                                    <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                                </span>
                                                <?php if (strtotime($task['deadline']) < time() && $task['status'] !== 'completed'): ?>
                                                    <br><small class="text-danger">Overdue</small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">No deadline</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                                   class="btn btn-sm btn-outline-primary" title="View">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=edit&id=<?php echo $task['id']; ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                        onclick="deleteTask(<?php echo $task['id']; ?>, '<?php echo htmlspecialchars(addslashes($task['title'])); ?>')"
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Grid View -->
                <div id="grid-view" class="tasks-grid d-none">
                    <div class="row">
                        <?php foreach ($tasks as $task): ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card task-card h-100 <?php echo ($task['status'] === 'completed') ? 'task-completed' : ''; ?>
                                     <?php echo ($task['deadline'] && strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'task-overdue' : ''; ?>">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge badge-priority-<?php echo $task['priority_name']; ?> badge-sm">
                                                <?php echo ucfirst($task['priority_name']); ?>
                                            </span>
                                            <span class="badge badge-status-<?php echo $task['status']; ?> badge-sm">
                                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                            </span>
                                        </div>

                                        <h5 class="card-title">
                                            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                               class="text-decoration-none <?php echo ($task['status'] === 'completed') ? 'text-decoration-line-through' : ''; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </a>
                                        </h5>

                                        <?php if (!empty($task['description'])): ?>
                                            <p class="card-text flex-grow-1">
                                                <?php echo htmlspecialchars(substr($task['description'], 0, 120)); ?>
                                                <?php if (strlen($task['description']) > 120): ?>...<?php endif; ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="task-meta mt-auto">
                                            <?php if ($task['assignee_name']): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($task['assignee_name']); ?>
                                                </small>
                                            <?php endif; ?>

                                            <?php if ($task['deadline']): ?>
                                                <small class="text-muted d-block">
                                                    <i class="bi bi-calendar"></i>
                                                    <span class="<?php echo (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'text-danger' : ''; ?>">
                                                        <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                                    </span>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div class="card-footer bg-transparent">
                                        <div class="btn-group w-100" role="group">
                                            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                               class="btn btn-sm btn-outline-primary">View</a>
                                            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=edit&id=<?php echo $task['id']; ?>"
                                               class="btn btn-sm btn-outline-secondary">Edit</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-actions mt-3 d-none">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">Selected tasks:</span>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="bulkUpdateStatus('completed')">
                            <i class="bi bi-check-circle"></i> Mark Complete
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="bulkUpdateStatus('in_progress')">
                            <i class="bi bi-play-circle"></i> Mark In Progress
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDelete()">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                    <h4 class="text-muted">No Tasks Found</h4>
                    <p class="text-muted">No tasks match your current filters. Try adjusting your search criteria.</p>
                    <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=create" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>
                        Create Your First Task
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
            <div class="card-footer">
                <nav aria-label="Tasks pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($pagination['current_page'] - 1); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $pagination['current_page']) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo buildPaginationUrl($i); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo buildPaginationUrl($pagination['current_page'] + 1); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.tasks-grid .card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.tasks-grid .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.task-info {
    max-width: 300px;
}

.task-title {
    color: #007bff;
    text-decoration: none;
}

.task-title:hover {
    text-decoration: underline;
}

.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.bulk-actions {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}
</style>

<script>
// View toggle functionality
document.getElementById('list-view-btn').addEventListener('click', function() {
    document.getElementById('list-view').classList.remove('d-none');
    document.getElementById('grid-view').classList.add('d-none');
    this.classList.add('active');
    document.getElementById('grid-view-btn').classList.remove('active');
});

document.getElementById('grid-view-btn').addEventListener('click', function() {
    document.getElementById('grid-view').classList.remove('d-none');
    document.getElementById('list-view').classList.add('d-none');
    this.classList.add('active');
    document.getElementById('list-view-btn').classList.remove('active');
});

// Select all functionality
document.getElementById('select-all').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.task-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateBulkActions();
});

// Update bulk actions visibility
document.querySelectorAll('.task-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

function updateBulkActions() {
    const checkedBoxes = document.querySelectorAll('.task-checkbox:checked');
    const bulkActions = document.querySelector('.bulk-actions');
    bulkActions.classList.toggle('d-none', checkedBoxes.length === 0);
}

// Delete task function
function deleteTask(taskId, taskTitle) {
    if (confirm(`Are you sure you want to delete the task "${taskTitle}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>/controller/TaskController.php?action=delete&id=' + taskId;

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'confirm_delete';
        input.value = '1';
        form.appendChild(input);

        document.body.appendChild(form);
        form.submit();
    }
}

// Bulk actions
function bulkUpdateStatus(status) {
    const selectedTasks = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.value);
    if (selectedTasks.length === 0) return;

    if (confirm(`Are you sure you want to mark ${selectedTasks.length} task(s) as ${status.replace('_', ' ')}?`)) {
        // Implement bulk update logic
        alert('Bulk update functionality would be implemented here');
    }
}

function bulkDelete() {
    const selectedTasks = Array.from(document.querySelectorAll('.task-checkbox:checked')).map(cb => cb.value);
    if (selectedTasks.length === 0) return;

    if (confirm(`Are you sure you want to delete ${selectedTasks.length} task(s)? This action cannot be undone.`)) {
        // Implement bulk delete logic
        alert('Bulk delete functionality would be implemented here');
    }
}

// Export tasks
function exportTasks() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    window.location.href = '<?php echo BASE_URL; ?>/controller/TaskController.php?' + params.toString();
}

// Helper function for pagination URLs
function buildPaginationUrl(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    return '<?php echo BASE_URL; ?>/controller/TaskController.php?' + params.toString();
}
</script>

<?php
// Include footer
require_once __DIR__ . '/../layout/footer.php';
?>