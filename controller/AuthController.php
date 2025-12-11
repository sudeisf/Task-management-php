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
        $fullName = htmlspecialchars(trim($_POST['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Validate
        if (!$fullName || !$email || !$password || !$confirm) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: ../views/auth/register.php");
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: ../views/auth/register.php");
            exit;
        }

        if ($password !== $confirm) {
            $_SESSION['error'] = "Passwords do not match.";
            header("Location: ../views/auth/register.php");
            exit;
        }

        // Password strength
        $passwordErrors = [];
        if (strlen($password) < 8) $passwordErrors[] = "at least 8 characters";
        if (!preg_match('/[A-Z]/', $password)) $passwordErrors[] = "one uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $passwordErrors[] = "one lowercase letter";
        if (!preg_match('/[0-9]/', $password)) $passwordErrors[] = "one number";
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) $passwordErrors[] = "one special character";

        if (!empty($passwordErrors)) {
            $_SESSION['error'] = "Password must contain " . implode(', ', $passwordErrors) . ".";
            header("Location: ../views/auth/register.php");
            exit;
        }

        if ($userModel->create($fullName, $email, $password)) {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/auth/login.php");
        } else {
            $_SESSION['error'] = "Email already exists or registration failed.";
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
            Auth::login($user);
            // Redirect to dashboard (fixed path)
            header("Location: ../views/dashboard/index.php");
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
