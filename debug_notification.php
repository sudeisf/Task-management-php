<?php
// Debug script for notifications
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Project.php';
require_once __DIR__ . '/models/Notification.php';

// Instantiate DB for this script
$dbObj = new Database();
$conn = $dbObj->getConnection();

echo "--- Debugging Notification Logic ---\n";

// 1. Get Users
echo "1. Getting users...\n";
$result = $conn->query("SELECT id, full_name, role_id, email FROM users LIMIT 5");
$users = $result->fetch_all(MYSQLI_ASSOC);
print_r($users);

if (count($users) < 2) {
    die("Need at least 2 users to test assignment.\n");
}

$adminId = $users[0]['id']; 
$managerId = $users[1]['id']; 

echo "Using Admin ID: $adminId, Manager ID: $managerId\n";

// 2. Get a Project
echo "2. Getting a project...\n";
$result = $conn->query("SELECT id, name FROM projects LIMIT 1");
$project = $result->fetch_assoc();

if (!$project) {
    die("No projects found. Create a project first.\n");
}
echo "Using Project ID: {$project['id']} ({$project['name']})\n";

// 3. Test Notification Creation
echo "3. Testing createProjectAssignmentNotification...\n";
$notificationModel = new Notification();

// Force creation
echo "Attempting to create notification for project {$project['id']} assigned to $managerId by $adminId\n";
$result = $notificationModel->createProjectAssignmentNotification($project['id'], $managerId, $adminId);

if ($result) {
    echo "SUCCESS: Notification created. ID: $result\n";
    
    // 4. Verify in Database
    echo "4. Verifying in database...\n";
    $notif = $notificationModel->find($result);
    print_r($notif);
    
    // Check type
    if (isset($notif['type'])) {
        echo "Type: {$notif['type']}\n";
    } else {
        echo "Type field missing from returned row (fallback mode?)\n";
    }
} else {
    echo "FAILURE: Failed to create notification.\n";
    
    // Debug why
    $hasToday = $notificationModel->hasNotificationToday($managerId, $project['id'], 'project_assignment', true);
    if ($hasToday) {
        echo "Reason: Notification already sent today (duplicate check).\n";
        
        // Let's print existing notifications for today to see what matched
        echo "Existing notifications for today:\n";
        $sql = "SELECT * FROM notifications 
                WHERE user_id = $managerId 
                AND DATE(created_at) = CURDATE()";
        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) {
            print_r($row);
        }
        
    } else {
        echo "Reason: Unknown (Database insert failed?)\n";
        echo "DB Error: " . $dbObj->getError() . "\n";
    }
}
