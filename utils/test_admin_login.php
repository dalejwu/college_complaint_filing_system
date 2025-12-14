<?php
include('../includes/db.php');

$username = 'admin';
$password = '123456';

$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    if ($password === $admin['password']) {
        echo "SUCCESS: Logged in as Admin '$username'\n";
    } else {
        echo "FAIL: Incorrect password for Admin '$username'\n";
    }
} else {
    echo "FAIL: Admin '$username' not found\n";
}
