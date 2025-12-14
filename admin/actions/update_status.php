<?php
include('../../includes/db.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['id']) && isset($_POST['status'])) {
    $id = intval($_POST['id']);
    $status = trim($_POST['status']);

    $allowed_statuses = ['Pending', 'Approved', 'Denied', 'In Progress', 'Resolved'];
    if (in_array($status, $allowed_statuses)) {
        // Ensure enum supports value before updating
        $col = $conn->query("SHOW COLUMNS FROM complaints LIKE 'status'");
        if ($col && $row = $col->fetch_assoc()) {
            if (strpos($row['Type'], "'Denied'") === false || strpos($row['Type'], "'Approved'") === false) {
                $conn->query("ALTER TABLE `complaints` MODIFY `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT 'Pending'");
            }
        }
        $stmt = $conn->prepare("UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            $_SESSION['message'] = 'Status updated successfully!';
        } else {
            $_SESSION['error'] = 'Error updating status: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = 'Invalid status!';
    }
}

header("Location: dashboard.php");
exit();
