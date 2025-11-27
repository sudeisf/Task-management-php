<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

// Start session
Session::start();

$action = $_GET['action'] ?? '';

$userModel = new User($conn);

switch ($action) {

    // -------------------------------------------------
    // REGISTER USER
    // -------------------------------------------------
    case 'register':
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        // Basic validation
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

        // Create user
        if ($userModel->create($fullName, $email, $password)) {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: ../views/auth/login.php");
        } else {
            $_SESSION['error'] = "Email already exists or registration failed.";
            header("Location: ../views/auth/register.php");
        }
        exit;

    // -------------------------------------------------
    // LOGIN USER
    // -------------------------------------------------
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            $_SESSION['error'] = "Email and password are required.";
            header("Location: ../views/auth/login.php");
            exit;
        }

        $user = $userModel->verify($email, $password);
        if ($user) {
            // Login success
            Auth::login($user);
            header("Location: ../views/dashboard/index.php");
        } else {
            $_SESSION['error'] = "Invalid email or password.";
            header("Location: ../views/auth/login.php");
        }
        exit;

    // -------------------------------------------------
    // LOGOUT USER
    // -------------------------------------------------
    case 'logout':
        Auth::logout();
        header("Location: ../views/auth/login.php");
        exit;

    default:
        echo "Invalid action.";
}
