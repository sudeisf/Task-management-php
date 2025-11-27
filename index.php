<?php
// Autoload or require core files
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';

// Start session
Session::start();

// Check if user is logged in
if (Auth::check()) {
    // Redirect to dashboard
    header("Location: views/Dashboard.php");
    exit;
} else {
    // Redirect to login page
    header("Location: views/auth/login.php");
    exit;
}
