<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/functions.php';

Session::start();

// Check authentication
if (!Auth::check()) {
    header("Location: ../views/auth/login.php");
    exit;
}

class ProfileController
{
    private $userModel;
    private $currentUser;

    public function __construct()
    {
        $db = new Database();
        $this->userModel = new User($db->getConnection());
        $this->currentUser = Auth::user();
    }

    // Display user profile
    public function index()
    {
        $userId = $this->currentUser['id'];
        $user = $this->userModel->getById($userId);

        if (!$user) {
            setFlashMessage('error', 'User not found.');
            redirect(BASE_URL . '/controller/DashboardController.php');
            return;
        }

        // Extract data for view
        extract([
            'user' => $user,
            'userRole' => $this->currentUser['role'] ?? 'member'
        ]);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/profile/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Show edit profile form
    public function edit()
    {
        $userId = $this->currentUser['id'];
        $user = $this->userModel->getById($userId);

        if (!$user) {
            setFlashMessage('error', 'User not found.');
            redirect(BASE_URL . '/controller/DashboardController.php');
            return;
        }

        // Extract data for view
        extract([
            'user' => $user,
            'userRole' => $this->currentUser['role'] ?? 'member'
        ]);

        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/profile/edit.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }

    // Update user profile
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        $userId = $this->currentUser['id'];
        
        // Sanitize input
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        $errors = [];

        if (empty($fullName)) {
            $errors[] = 'Full name is required.';
        }

        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }

        // Check if email is already taken by another user
        $currentUser = $this->userModel->getById($userId);
        if ($this->userModel->exists($email) && $currentUser['email'] !== $email) {
            $errors[] = 'Email is already in use by another account.';
        }

        // Password change validation
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $errors[] = 'Current password is required to set a new password.';
            } else {
                // Verify current password - need to get password from database
                $conn = Database::getInstance()->getConnection();
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $userData = $result->fetch_assoc();
                
                if (!$userData || !password_verify($currentPassword, $userData['password'])) {
                    $errors[] = 'Current password is incorrect.';
                }
            }

            if (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            }

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            }
        }

        if (!empty($errors)) {
            setFlashMessage('error', implode('<br>', $errors));
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        // Prepare update data
        $updateData = [
            'full_name' => $fullName,
            'email' => $email
        ];

        // Add password to update if changing
        if (!empty($newPassword)) {
            $updateData['password'] = password_hash($newPassword, PASSWORD_BCRYPT);
        }

        // Update user
        $result = $this->userModel->updateProfile($userId, $updateData);

        if ($result) {
            // Update session data
            $_SESSION['user']['name'] = $fullName;
            $_SESSION['user']['email'] = $email;

            setFlashMessage('success', 'Profile updated successfully.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=index');
        } else {
            setFlashMessage('error', 'Failed to update profile. Please try again.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
        }
    }

    // Upload avatar
    public function uploadAvatar()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            setFlashMessage('error', 'Invalid security token.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            setFlashMessage('error', 'Please select a valid image file.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        $file = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            setFlashMessage('error', 'Only JPG, PNG, and GIF images are allowed.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            setFlashMessage('error', 'Image size must be less than 2MB.');
            redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
            return;
        }

        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar_' . $this->currentUser['id'] . '_' . time() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Delete old avatar if exists
            $user = $this->userModel->getById($this->currentUser['id']);
            if (!empty($user['avatar']) && file_exists($uploadDir . $user['avatar'])) {
                unlink($uploadDir . $user['avatar']);
            }

            // Update user avatar in database
            $result = $this->userModel->updateProfile($this->currentUser['id'], ['avatar' => $filename]);

            if ($result) {
                // Update session to reflect new avatar
                Session::set('user_avatar', $filename);
                
                setFlashMessage('success', 'Profile picture updated successfully.');
            } else {
                setFlashMessage('error', 'Failed to update profile picture in database.');
            }
        } else {
            setFlashMessage('error', 'Failed to upload image. Please try again.');
        }

        redirect(BASE_URL . '/controller/ProfileController.php?action=edit');
    }
}

// Handle actions
$controller = new ProfileController();
$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'index':
        $controller->index();
        break;
    case 'edit':
        $controller->edit();
        break;
    case 'update':
        $controller->update();
        break;
    case 'uploadAvatar':
        $controller->uploadAvatar();
        break;
    default:
        $controller->index();
        break;
}
