<?php
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

Session::start();

if (!Auth::check()) {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
<h1>Welcome, <?php echo Auth::user()['name']; ?>!</h1>
<p>This is your dashboard.</p>
<a href="../controller/AuthController.php?action=logout">Logout</a>
</body>
</html>
