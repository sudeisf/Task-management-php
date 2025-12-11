<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/functions.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class NotificationController
{
    private $notificationModel;
    private $currentUser;

    public function __construct()
    {
        $this->notificationModel = new Notification();
        $this->currentUser = Auth::user();
    }

    // Display all notifications
    public function index()
    {
        $userId = $this->currentUser['id'];
        $filter = $_GET['filter'] ?? 'all'; // all, unread, read

        $onlyUnread = ($filter === 'unread');
        $notifications = $this->notificationModel->getByUser($userId, null, null, $onlyUnread);

        // Get unread count
        $unreadCount = $this->notificationModel->getUnreadCount($userId);

        // Extract data for view
        extract([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filter' => $filter,
            'userRole' => $this->currentUser['role'] ?? 'member'
        ]);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/notifications/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Mark notification as read
    public function markAsRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $notificationId = $_POST['id'] ?? null;
        $userId = $this->currentUser['id'];

        if (!$notificationId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            exit;
        }

        $result = $this->notificationModel->markAsRead($notificationId, $userId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Notification marked as read' : 'Failed to mark notification as read'
        ]);
        exit;
    }

    // Mark all notifications as read
    public function markAllAsRead()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('error', 'Invalid request method');
            redirect(BASE_URL . '/controller/NotificationController.php');
            return;
        }

        $userId = $this->currentUser['id'];
        $result = $this->notificationModel->markAllAsRead($userId);

        if ($result) {
            setFlashMessage('success', 'All notifications marked as read');
        } else {
            setFlashMessage('error', 'Failed to mark notifications as read');
        }

        redirect(BASE_URL . '/controller/NotificationController.php');
    }

    // Get unread notifications (AJAX)
    public function getUnread()
    {
        $userId = $this->currentUser['id'];
        $limit = $_GET['limit'] ?? 5;

        $notifications = $this->notificationModel->getByUser($userId, $limit, null, true);
        $unreadCount = $this->notificationModel->getUnreadCount($userId);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
        exit;
    }

    // Delete notification
    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlashMessage('error', 'Invalid request method');
            redirect(BASE_URL . '/controller/NotificationController.php');
            return;
        }

        $notificationId = $_POST['id'] ?? null;
        $userId = $this->currentUser['id'];

        if (!$notificationId) {
            setFlashMessage('error', 'Notification ID required');
            redirect(BASE_URL . '/controller/NotificationController.php');
            return;
        }

        $result = $this->notificationModel->delete($notificationId, $userId);

        if ($result) {
            setFlashMessage('success', 'Notification deleted');
        } else {
            setFlashMessage('error', 'Failed to delete notification');
        }

        redirect(BASE_URL . '/controller/NotificationController.php');
    }
}

// Handle actions
$controller = new NotificationController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'markAsRead':
        $controller->markAsRead();
        break;
    case 'markAllAsRead':
        $controller->markAllAsRead();
        break;
    case 'getUnread':
        $controller->getUnread();
        break;
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->index();
        break;
}
