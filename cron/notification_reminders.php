<?php
/**
 * Automated Notification Cron Job
 * 
 * This script should be run daily (or hourly) via cron to send:
 * - Due date reminders for upcoming tasks
 * - Overdue notifications for past-due tasks
 * 
 * Setup (Windows Task Scheduler):
 * 1. Open Task Scheduler
 * 2. Create Basic Task
 * 3. Set trigger to Daily at desired time (e.g., 9:00 AM)
 * 4. Action: Start a program
 * 5. Program: C:\path\to\php.exe
 * 6. Arguments: C:\path\to\Task-management-php\cron\notification_reminders.php
 * 
 * Setup (Linux/Mac Cron):
 * Add to crontab: 0 9 * * * /usr/bin/php /path/to/Task-management-php/cron/notification_reminders.php
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Notification.php';

echo "Starting notification reminder job at " . date('Y-m-d H:i:s') . "\n";

$taskModel = new Task();
$notificationModel = new Notification();

// Get all active (non-completed) tasks with deadlines
$sql = "SELECT id, title, deadline, assigned_to, status 
        FROM tasks 
        WHERE status != 'completed' 
        AND deadline IS NOT NULL 
        AND assigned_to IS NOT NULL
        ORDER BY deadline ASC";

$db = new Database();
$db->prepare($sql);
$db->execute([]);
$tasks = $db->getRows();

$dueSoonCount = 0;
$overdueCount = 0;
$today = date('Y-m-d');

foreach ($tasks as $task) {
    $deadline = $task['deadline'];
    $taskId = $task['id'];
    $userId = $task['assigned_to'];
    
    // Calculate days difference
    $deadlineTime = strtotime($deadline);
    $todayTime = strtotime($today);
    $daysDiff = floor(($deadlineTime - $todayTime) / (60 * 60 * 24));
    
    // Check if task is overdue
    if ($daysDiff < 0) {
        // Task is overdue
        // Check if we already sent an overdue notification today
        if (!hasNotificationToday($notificationModel, $userId, $taskId, 'overdue')) {
            $notificationModel->createOverdueNotification($taskId, $userId);
            $overdueCount++;
            echo "  - Sent overdue notification for task #{$taskId}: {$task['title']}\n";
        }
    }
    // Check if task is due soon (0-3 days)
    elseif ($daysDiff >= 0 && $daysDiff <= 3) {
        // Task is due soon
        // Check if we already sent a due date reminder today
        if (!hasNotificationToday($notificationModel, $userId, $taskId, 'due')) {
            $notificationModel->createDueDateReminder($taskId, $userId);
            $dueSoonCount++;
            echo "  - Sent due date reminder for task #{$taskId}: {$task['title']} (due in {$daysDiff} days)\n";
        }
    }
}

echo "\nJob completed at " . date('Y-m-d H:i:s') . "\n";
echo "Summary:\n";
echo "  - Due date reminders sent: $dueSoonCount\n";
echo "  - Overdue notifications sent: $overdueCount\n";
echo "  - Total notifications: " . ($dueSoonCount + $overdueCount) . "\n";

/**
 * Check if a notification was already sent today for this task
 * to avoid duplicate notifications
 */
function hasNotificationToday($notificationModel, $userId, $taskId, $type)
{
    $db = new Database();
    
    $searchTerm = $type === 'overdue' ? 'overdue' : 'due';
    
    $sql = "SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? 
            AND task_id = ? 
            AND message LIKE ?
            AND DATE(created_at) = CURDATE()";
    
    $db->prepare($sql);
    $db->execute([$userId, $taskId, "%{$searchTerm}%"]);
    $result = $db->getRow();
    
    return ($result['count'] ?? 0) > 0;
}
