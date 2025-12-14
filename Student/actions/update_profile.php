<?php
session_start();
include('../../includes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['student_id'])) {
        header("Location: ../../Login/index.php");
        exit();
    }

    $id = $_SESSION['student_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Password fields
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name) || empty($email)) {
        $_SESSION['error'] = "Name and Email are required.";
        header("Location: ../profile.php");
        exit();
    }

    // Check if email is taken by another user
    $check = $conn->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $check->bind_param("si", $email, $id);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $_SESSION['error'] = "This email address is already in use.";
        header("Location: ../profile.php");
        exit();
    }
    $check->close();

    // Begin Transaction
    $conn->begin_transaction();

    try {
        // Update Info
        $stmt = $conn->prepare("UPDATE students SET name = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $email, $id);
        $stmt->execute();
        $stmt->close();

        // Handle Password Change
        if (!empty($new_password)) {
            if (empty($old_password)) {
                throw new Exception("Please enter your old password to set a new one.");
            }
            if ($new_password !== $confirm_password) {
                throw new Exception("New passwords do not match.");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }

            // Verify Old Password
            $pwd_query = $conn->prepare("SELECT password FROM students WHERE id = ?");
            $pwd_query->bind_param("i", $id);
            $pwd_query->execute();
            $user_data = $pwd_query->get_result()->fetch_assoc();
            $pwd_query->close();

            if (!password_verify($old_password, $user_data['password'])) {
                throw new Exception("Incorrect old password.");
            }

            // Update Password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_pwd = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
            $update_pwd->bind_param("si", $new_hash, $id);
            $update_pwd->execute();
            $update_pwd->close();
        }

        $conn->commit();
        $_SESSION['success'] = "Profile updated successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
    }

    header("Location: ../profile.php");
    exit();
} else {
    header("Location: ../dashboard.php");
    exit();
}
