<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';

Session::start();

if (Auth::check()) {
    // User logged in → go to dashboard
    header("Location: /views/Dashboard.php");
} else {
    // Not logged in → go to login
    header("Location: /views/auth/login.php");
}
exit;
