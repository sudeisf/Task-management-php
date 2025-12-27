<?php
/**
 * Project Details View
 */

$pageTitle = $project['name'];
?>

<div class="container-fluid py-4">
    <!-- Project Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h2 class="mb-2"><?= htmlspecialchars($project['name']) ?></h2>
            <p class="text-muted"><?= htmlspecialchars($project['description'] ?? 'No description') ?></p>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-<?= $project['status'] === 'active' ? 'success' : 'warning' ?> fs-6">
                <?= ucfirst($project['status']) ?>
            </span>
            <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
                <div class="mt-2">
                    <a href="<?= BASE_URL ?>/controller/TaskController.php?action=create&project_id=<?= $project['id'] ?>" 
                       class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>New Task
                    </a>
                    <?php if ($userRole === 'admin'): ?>
                        <a href="?action=edit&id=<?= $project['id'] ?>" class="btn btn-warning">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Tasks</h6>
                    <h2 class="mb-0"><?= $statistics['total_tasks'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">To Do</h6>
                    <h2 class="mb-0"><?= $statistics['todo_tasks'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">In Progress</h6>
                    <h2 class="mb-0"><?= $statistics['in_progress_tasks'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Completed</h6>
                    <h2 class="mb-0"><?= $statistics['completed_tasks'] ?? 0 ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Team Members -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people me-2"></i>Team Members</h5>
            <?php if ($userRole === 'admin' && isset($users)): ?>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addMemberForm">
                    <i class="bi bi-person-plus me-1"></i>Add Member
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if ($userRole === 'admin' && isset($users)): ?>
                <!-- Add Member Form -->
                <div class="collapse mb-4" id="addMemberForm">
                    <div class="p-3 border rounded bg-light">
                        <form method="POST" action="?action=assign_user&id=<?= $project['id'] ?>" class="row g-3 align-items-end">
                            <div class="col-md-9">
                                <label for="user_id" class="form-label small">Select User</label>
                                <select name="user_id" id="user_id" class="form-select" required>
                                    <option value="">Choose user...</option>
                                    <?php foreach ($users as $u): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= htmlspecialchars($u['full_name']) ?> (<?= ucfirst($u['role']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text small">Project role (Manager/Member) is assigned automatically based on system role.</div>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-plus-circle me-1"></i>Assign
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (empty($teamMembers)): ?>
                <p class="text-muted">No team members assigned yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Assigned</th>
                                <?php if ($userRole === 'admin'): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teamMembers as $member): ?>
                                <tr>
                                    <td><?= htmlspecialchars($member['full_name']) ?></td>
                                    <td><?= htmlspecialchars($member['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $member['role_in_project'] === 'manager' ? 'primary' : 'secondary' ?>">
                                            <?= ucfirst($member['role_in_project']) ?>
                                        </span>
                                    </td>
                                    <td><?= formatDateTime($member['assigned_at']) ?></td>
                                    <?php if ($userRole === 'admin'): ?>
                                        <td>
                                            <form method="POST" action="?action=remove_user&id=<?= $project['id'] ?>" 
                                                  style="display:inline;" 
                                                  onsubmit="return confirm('Remove this user from the project?')">
                                                <input type="hidden" name="user_id" value="<?= $member['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Attachments -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-paperclip me-2"></i>Project Attachments</h5>
            <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#uploadForm">
                    <i class="bi bi-plus-circle me-1"></i>Add Attachment
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <!-- Upload Form (Collapsed by default) -->
            <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
            <div class="collapse mb-4" id="uploadForm">
                <div class="p-3 border rounded bg-light">
                    <form action="<?= BASE_URL ?>/controller/AttachmentController.php?action=upload" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                        <div class="mb-3">
                            <label for="attachment" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="attachment" name="attachment" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Upload File</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($attachments)): ?>
                <p class="text-muted text-center py-3">No attachments for this project yet.</p>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <?php foreach ($attachments as $attachment): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm border-light">
                                <div class="card-body d-flex align-items-center">
                                    <div class="flex-shrink-0">
                                        <i class="bi bi-file-earmark-text fs-3 text-primary"></i>
                                    </div>
                                    <div class="flex-grow-1 ms-3 overflow-hidden">
                                        <h6 class="mb-0 text-truncate">
                                            <a href="<?= BASE_URL ?>/controller/AttachmentController.php?action=download&id=<?= $attachment['id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($attachment['file_name']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">By <?= htmlspecialchars($attachment['uploader_name']) ?></small>
                                    </div>
                                    <?php if ($userRole === 'admin'): ?>
                                    <div class="flex-shrink-0 ms-2">
                                        <form method="POST" action="<?= BASE_URL ?>/controller/AttachmentController.php?action=delete" onsubmit="return confirm('Delete this attachment?')">
                                            <input type="hidden" name="attachment_id" value="<?= $attachment['id'] ?>">
                                            <input type="hidden" name="project_id" value="<?= $project['id'] ?>">
                                            <button type="submit" class="btn btn-link text-danger p-0">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Project Tasks -->
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-list-task me-2"></i>Tasks</h5>
             <?php if ($userRole === 'admin' || $userRole === 'manager'): ?>
                <a href="<?= BASE_URL ?>/controller/TaskController.php?action=create&project_id=<?= $project['id'] ?>" 
                   class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>New Task
                </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($tasks)): ?>
                <p class="text-muted text-center py-3">No tasks created for this project yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
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
                                <tr>
                                    <td>
                                        <a href="<?= BASE_URL ?>/controller/TaskController.php?action=show&id=<?= $task['id'] ?>" 
                                           class="text-decoration-none fw-bold <?= $task['status'] === 'completed' ? 'text-decoration-line-through text-muted' : '' ?>">
                                            <?= htmlspecialchars($task['title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-priority-<?= $task['priority_name'] ?>">
                                            <?= ucfirst($task['priority_name']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-status-<?= $task['status'] ?>">
                                            <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($task['assignee_name']): ?>
                                            <?= htmlspecialchars($task['assignee_name']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($task['deadline']): ?>
                                            <span class="<?= (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'text-danger' : '' ?>">
                                                <?= date('M d, Y', strtotime($task['deadline'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/controller/TaskController.php?action=show&id=<?= $task['id'] ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
