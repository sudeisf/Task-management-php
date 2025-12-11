<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/core/Session.php';
require_once __DIR__ . '/core/Auth.php';

Session::start();

// Always redirect to login page first
header("Location: /views/auth/login.php");
exit;
