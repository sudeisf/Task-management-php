<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';

Session::start();

if (Auth::check()) {
    // User is logged in - include dashboard
    require_once __DIR__ . '/views/Dashboard.php';
} else {
    // User not logged in - include login page
    require_once __DIR__ . '/views/auth/login.php';
}