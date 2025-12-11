<?php
/**
 * Show Task View
 * Display detailed task information with comments and attachments
 */

// Include header
require_once __DIR__ . '/../layout/header.php';

// Assuming these variables are passed from the controller
// $task - the task data
// $comments - task comments
// $attachments - task attachments
// $activities - recent task activities
?>

<div class="dashboard-container">
    <!-- Task Header -->
    <div class="dashboard-header d-flex justify-content-between align-items-start">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-3 mb-2">
                <h1 class="dashboard-title mb-0"><?php echo htmlspecialchars($task['title']); ?></h1>
                <span class="badge badge-status-<?php echo $task['status']; ?> fs-6">
                    <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                </span>
                <?php if ($task['deadline'] && strtotime($task['deadline']) < time() && $task['status'] !== 'completed'): ?>
                    <span class="badge bg-danger fs-6">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Overdue
                    </span>
                <?php endif; ?>
            </div>
            <p class="dashboard-subtitle mb-0">
                Created by <?php echo htmlspecialchars($task['creator_name']); ?> on <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                <?php if ($task['updated_at'] !== $task['created_at']): ?>
                    • Updated <?php echo date('M d, Y', strtotime($task['updated_at'])); ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=edit&id=<?php echo $task['id']; ?>" class="btn btn-outline-primary">
                <i class="bi bi-pencil me-2"></i>
                Edit Task
            </a>
            <a href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=index" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>
                Back to Tasks
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Task Content -->
        <div class="col-lg-8">
            <!-- Task Details Card -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Task Details
                    </h3>
                    <div class="task-actions">
                        <?php if ($task['status'] !== 'completed'): ?>
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="quickComplete()">
                                <i class="bi bi-check-circle me-1"></i>
                                Complete
                            </button>
                        <?php endif; ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/controller/TaskController.php?action=edit&id=<?php echo $task['id']; ?>">
                                    <i class="bi bi-pencil me-2"></i>
                                    Edit Task
                                </a></li>
                                <li><a class="dropdown-item" href="#" onclick="duplicateTask()">
                                    <i class="bi bi-copy me-2"></i>
                                    Duplicate Task
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteTask()">
                                    <i class="bi bi-trash me-2"></i>
                                    Delete Task
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Task Metadata -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="task-meta-item">
                                <label class="text-muted small">Priority</label>
                                <div>
                                    <span class="badge badge-priority-<?php echo $task['priority_name']; ?>">
                                        <?php echo ucfirst($task['priority_name']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="task-meta-item">
                                <label class="text-muted small">Category</label>
                                <div>
                                    <?php if ($task['category_name']): ?>
                                        <span class="badge bg-secondary">
                                            <?php echo htmlspecialchars($task['category_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No category</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="task-meta-item">
                                <label class="text-muted small">Assigned To</label>
                                <div>
                                    <?php if ($task['assignee_name']): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2">
                                                <?php echo strtoupper(substr($task['assignee_name'], 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($task['assignee_name']); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">Unassigned</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="task-meta-item">
                                <label class="text-muted small">Deadline</label>
                                <div>
                                    <?php if ($task['deadline']): ?>
                                        <span class="<?php echo (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'text-danger fw-bold' : ''; ?>">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($task['deadline'])); ?>
                                            <?php if (strtotime($task['deadline']) < time() && $task['status'] !== 'completed'): ?>
                                                <br><small class="text-danger">Overdue</small>
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No deadline set</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Task Description -->
                    <?php if (!empty($task['description'])): ?>
                        <div class="task-description">
                            <h5 class="mb-3">Description</h5>
                            <div class="description-content">
                                <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="card mb-4" id="comments-section">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="bi bi-chat-square-text me-2"></i>
                        Comments (<?php echo count($comments ?? []); ?>)
                    </h3>
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#addCommentForm">
                        <i class="bi bi-plus-circle me-1"></i>
                        Add Comment
                    </button>
                </div>
                <div class="card-body">
                    <!-- Add Comment Form -->
                    <div class="collapse mb-4" id="addCommentForm">
                        <form action="<?php echo BASE_URL; ?>/controller/CommentController.php?action=store" method="POST">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Add a comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required
                                          placeholder="Write your comment here..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>
                                Post Comment
                            </button>
                        </form>
                    </div>

                    <!-- Comments List -->
                    <div class="comments-list">
                        <?php if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex">
                                        <div class="avatar-circle me-3">
                                            <?php echo strtoupper(substr($comment['full_name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($comment['full_name'] ?? 'Unknown User'); ?></strong>
                                                    <small class="text-muted ms-2">
                                                        <?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <div class="comment-actions">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            onclick="editComment(<?php echo $comment['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="comment-content mt-2">
                                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-chat-square-text display-4 mb-3"></i>
                                <p>No comments yet. Be the first to add one!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Attachments -->
            <?php if (!empty($attachments)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-paperclip me-2"></i>
                            Attachments (<?php echo count($attachments); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="attachments-list">
                            <?php foreach ($attachments as $attachment): ?>
                                <div class="attachment-item mb-2 p-2 border rounded">
                                    <div class="d-flex align-items-center">
                                        <div class="attachment-icon me-3">
                                            <i class="bi <?php echo getFileIcon($attachment['file_name']); ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <a href="<?php echo BASE_URL; ?>/controller/AttachmentController.php?action=download&id=<?php echo $attachment['id']; ?>"
                                               class="text-decoration-none fw-bold">
                                                <?php echo htmlspecialchars($attachment['file_name'] ?? 'Unnamed file'); ?>
                                            </a>
                                            <br>
                                            <small class="text-muted">
                                                Uploaded by <?php echo htmlspecialchars($attachment['uploader_name']); ?>
                                                on <?php echo date('M d, Y', strtotime($attachment['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="attachment-actions">
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    onclick="deleteAttachment(<?php echo $attachment['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>



            <!-- Quick Stats -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        Quick Stats
                    </h5>
                </div>
                <div class="card-body">
                    <div class="stats-list">
                        <div class="stat-item mb-2">
                            <span class="stat-label">Status:</span>
                            <span class="stat-value badge badge-status-<?php echo $task['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                            </span>
                        </div>
                        <div class="stat-item mb-2">
                            <span class="stat-label">Priority:</span>
                            <span class="stat-value badge badge-priority-<?php echo $task['priority_name']; ?>">
                                <?php echo ucfirst($task['priority_name']); ?>
                            </span>
                        </div>
                        <div class="stat-item mb-2">
                            <span class="stat-label">Comments:</span>
                            <span class="stat-value"><?php echo count($comments ?? []); ?></span>
                        </div>
                        <div class="stat-item mb-2">
                            <span class="stat-label">Attachments:</span>
                            <span class="stat-value"><?php echo count($attachments ?? []); ?></span>
                        </div>
                        <?php if ($task['deadline']): ?>
                            <div class="stat-item">
                                <span class="stat-label">Days until deadline:</span>
                                <span class="stat-value <?php echo (strtotime($task['deadline']) < time() && $task['status'] !== 'completed') ? 'text-danger fw-bold' : ''; ?>">
                                    <?php
                                    $days = floor((strtotime($task['deadline']) - time()) / (60*60*24));
                                    if ($days < 0 && $task['status'] !== 'completed') {
                                        echo 'Overdue by ' . abs($days) . ' days';
                                    } elseif ($days === 0) {
                                        echo 'Due today';
                                    } elseif ($days === 1) {
                                        echo 'Due tomorrow';
                                    } else {
                                        echo $days . ' days';
                                    }
                                    ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.task-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.card:hover .task-actions {
    opacity: 1;
}

.task-meta-item {
    margin-bottom: 1rem;
}

.task-meta-item label {
    display: block;
    margin-bottom: 0.25rem;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: #007bff;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.task-description {
    padding-top: 1rem;
    border-top: 1px solid #dee2e6;
}

.description-content {
    line-height: 1.6;
}

.comment-item {
    transition: background-color 0.2s ease;
}

.comment-item:hover {
    background-color: #f8f9fa;
}

.comment-actions {
    opacity: 0;
    transition: opacity 0.2s ease;
}

.comment-item:hover .comment-actions {
    opacity: 1;
}

.attachments-list .attachment-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 6px;
    color: #6c757d;
}

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
    margin-bottom: 1rem;
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

.stats-list .stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.stats-list .stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-weight: 500;
    color: #6c757d;
}

.stat-value {
    font-weight: 600;
}
</style>

<script>
// Quick actions
function quickComplete() {
    if (confirm('Mark this task as completed?')) {
        fetch('<?php echo BASE_URL; ?>/controller/TaskController.php?action=change_status&id=<?php echo $task['id']; ?>&status=completed', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating task status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating task status');
        });
    }
}

function deleteTask() {
    if (confirm('Are you sure you want to delete this task? This action cannot be undone.')) {
        window.location.href = '<?php echo BASE_URL; ?>/controller/TaskController.php?action=delete&id=<?php echo $task['id']; ?>';
    }
}

function duplicateTask() {
    // Implement task duplication logic
    alert('Task duplication feature would be implemented here');
}

function editComment(commentId) {
    // Implement comment editing
    alert('Comment editing feature would be implemented here');
}

function deleteComment(commentId) {
    if (confirm('Are you sure you want to delete this comment?')) {
        // Implement comment deletion
        alert('Comment deletion would be implemented here');
    }
}

function deleteAttachment(attachmentId) {
    if (confirm('Are you sure you want to delete this attachment?')) {
        // Implement attachment deletion
        alert('Attachment deletion would be implemented here');
    }
}

// Helper functions
function getFileIcon(filename) {
    const ext = filename.split('.').pop().toLowerCase();
    const iconMap = {
        'pdf': 'bi-file-earmark-pdf',
        'doc': 'bi-file-earmark-word',
        'docx': 'bi-file-earmark-word',
        'xls': 'bi-file-earmark-excel',
        'xlsx': 'bi-file-earmark-excel',
        'ppt': 'bi-file-earmark-ppt',
        'pptx': 'bi-file-earmark-ppt',
        'txt': 'bi-file-earmark-text',
        'jpg': 'bi-file-earmark-image',
        'jpeg': 'bi-file-earmark-image',
        'png': 'bi-file-earmark-image',
        'gif': 'bi-file-earmark-image'
    };
    return iconMap[ext] || 'bi-file-earmark';
}

function getActivityIcon(action) {
    if (action.includes('created')) return 'bi-plus-circle';
    if (action.includes('updated')) return 'bi-pencil';
    if (action.includes('completed')) return 'bi-check-circle';
    if (action.includes('comment')) return 'bi-chat';
    if (action.includes('attachment')) return 'bi-paperclip';
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