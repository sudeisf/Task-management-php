<?php
// Start secure connection
$host = "localhost";
$user = "root";      // change if your MySQL user is different
$pass = "";          // change if you have a password
$dbname = "task_manager";

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8mb4");

// Now $conn can be used in your models
return $conn;
