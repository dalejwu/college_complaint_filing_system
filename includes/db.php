<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'college_comnplaint_system';

// First, connect without selecting a database
$conn = new mysqli($host, $user, $pass);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$createDbQuery = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!$conn->query($createDbQuery)) {
    die("Error creating database: " . $conn->error);
}

// Now select the database
if (!$conn->select_db($dbname)) {
    die("Error selecting database: " . $conn->error);
}
?>
