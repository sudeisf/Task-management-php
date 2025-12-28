<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

Session::start();

$action = $_GET['action'] ?? '';

$userModel = new User($conn);

switch ($action) {

    // ---------------- REGISTER ----------------
    case 'register':
        require_once __DIR__ . '/../core/Validator.php';
        $validator = new Validator();
        
        $rules = [
            'full_name' => 'required|not_numeric|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|password|confirmed',
            'role' => 'in:member,manager'
        ];

        $labels = [
            'full_name' => 'Full Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'role' => 'Role'
        ];

        if (!$validator->validate($_POST, $rules, $labels)) {
            $_SESSION['errors'] = $validator->getErrors();
            $_SESSION['form_data'] = $_POST;
            header("Location: ../views/auth/register.php");
            exit;
        }

        $sanitized = $validator->getSanitizedData();
        $fullName = $sanitized['full_name'];
        $email = $sanitized['email'];
        $password = $_POST['password']; // Use raw password for hashing
        $role = $sanitized['role'] ?? 'member';

        if ($userModel->create($fullName, $email, $password, $role)) {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/auth/login.php");
        } else {
            $_SESSION['error'] = "Registration failed. Please try again.";
            header("Location: ../views/auth/register.php");
        }
        exit;

    // ---------------- LOGIN ----------------
    case 'login':
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $_SESSION['error'] = "Email and password are required.";
            header("Location: ../views/auth/login.php");
            exit;
        }

        $user = $userModel->verify($email, $password);

        if ($user) {
            // Check if user is active
            if ($user['status'] !== 'active') {
                $_SESSION['error'] = "Your account is inactive. Please contact administrator.";
                header("Location: ../views/auth/login.php");
                exit;
            }

            Auth::login($user);
            // Redirect to dashboard (fixed path)
            header("Location: ../controller/DashboardController.php?action=index");
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            header("Location: ../views/auth/login.php");
        }
        exit;

    // ---------------- LOGOUT ----------------
    case 'logout':
        Auth::logout();
        header("Location: ../views/auth/login.php");
        exit;

    default:
        echo "Invalid action.";
}
