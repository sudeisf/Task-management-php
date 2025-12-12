<?php
/**
 * Admin All Tasks View
 * Displays system-wide list of tasks
 */

// Include header (already included by controller but for standalone safety)
// require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $tasks - array of tasks
// $filters - current filter values
// $pagination - pagination data
// $categories - available categories
// $priorities - available priorities
// $users - available users
?>

<div class="dashboard-container">
    <!-- Tasks Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="dashboard-title">All Tasks (System-Wide)</h1>
            <p class="dashboard-subtitle">Overview of all tasks across all projects.</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="<?php echo BASE_URL; ?>/controller/AdminController.php" class="row g-3">
                <input type="hidden" name="action" value="all_tasks">

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

                <!-- Assigned To Filter -->
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

                <!-- Action Buttons -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                    <a href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
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
        </div>

        <div class="card-body">
            <?php if (!empty($tasks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Task</th>
                                <th>Project</th>
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
                                        <div class="task-info">
                                            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=show&id=<?php echo $task['id']; ?>"
                                               class="task-title <?php echo ($task['status'] === 'completed') ? 'text-decoration-line-through' : ''; ?>">
                                                <?php echo htmlspecialchars($task['title']); ?>
                                            </a>
                                            <small class="text-muted d-block">
                                                Created by <?php echo htmlspecialchars($task['creator_name']); ?>
                                                on <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($task['project_name'])): ?>
                                            <a href="<?php echo BASE_URL; ?>/controller/ProjectController.php?action=show&id=<?php echo $task['project_id']; ?>" class="text-decoration-none">
                                                <i class="bi bi-folder2"></i> <?php echo htmlspecialchars($task['project_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="bi bi-dash"></i></span>
                                        <?php endif; ?>
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
                                            <!-- Admin Delete -->
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
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                    <h4 class="text-muted">No Tasks Found</h4>
                    <p class="text-muted">No tasks match your current filters.</p>
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
                                <a class="page-link" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks&page=<?php echo $pagination['current_page'] - 1; ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?php echo ($i == $pagination['current_page']) ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php echo BASE_URL; ?>/controller/AdminController.php?action=all_tasks&page=<?php echo $pagination['current_page'] + 1; ?>">
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

<script>
// Delete task function (reused)
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
</script>
