<?php
include('../includes/db.php');

$username = 'admin';
$password = '123456';

// Check if admin already exists
$stmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin
    $stmt->close();
    $updateStmt = $conn->prepare("UPDATE admins SET password = ? WHERE username = ?");
    $updateStmt->bind_param("ss", $password, $username);
    if ($updateStmt->execute()) {
        echo "Admin '$username' updated with new password.\n";
    } else {
        echo "Error updating admin: " . $conn->error . "\n";
    }
    $updateStmt->close();
} else {
    // Create new admin
    $stmt->close();
    $insertStmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
    $insertStmt->bind_param("ss", $username, $password);
    if ($insertStmt->execute()) {
        echo "Admin '$username' created successfully.\n";
    } else {
        echo "Error creating admin: " . $conn->error . "\n";
    }
    $insertStmt->close();
}
