<?php
include('../db.php');
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    die("Access denied. Please login as admin.");
}

$success = false;
$error = '';

// Run the migrations
try {
    // Check if attachments column exists
    $check1 = $conn->query("SHOW COLUMNS FROM complaints LIKE 'attachments'");
    if ($check1->num_rows == 0) {
        $conn->query("ALTER TABLE `complaints` ADD COLUMN `attachments` TEXT DEFAULT NULL COMMENT 'Comma-separated list of uploaded file paths' AFTER `description`");
        $success = true;
    }
    
    // Check if last_edited_at column exists
    $check2 = $conn->query("SHOW COLUMNS FROM complaints LIKE 'last_edited_at'");
    if ($check2->num_rows == 0) {
        $conn->query("ALTER TABLE `complaints` ADD COLUMN `last_edited_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'Timestamp of last edit for cooldown tracking' AFTER `updated_at`");
        $success = true;
    }
    
    // Check if respondents column exists
    $check3 = $conn->query("SHOW COLUMNS FROM complaints LIKE 'respondents'");
    if ($check3->num_rows == 0) {
        $conn->query("ALTER TABLE `complaints` ADD COLUMN `respondents` ENUM('Single Person','Multiple People') DEFAULT NULL AFTER `description`");
        $success = true;
    }
    // Add respondent_count column if missing
    $check3b = $conn->query("SHOW COLUMNS FROM complaints LIKE 'respondent_count'");
    if ($check3b->num_rows == 0) {
        $conn->query("ALTER TABLE `complaints` ADD COLUMN `respondent_count` INT NULL DEFAULT NULL AFTER `respondents`");
        $success = true;
    }
    
    // Add approved column to students if missing
    $check4 = $conn->query("SHOW COLUMNS FROM students LIKE 'approved'");
    if ($check4->num_rows == 0) {
        $conn->query("ALTER TABLE `students` ADD COLUMN `approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `password`");
        $success = true;
    }

    // Create categories table if it doesn't exist
    $check5 = $conn->query("SHOW TABLES LIKE 'categories'");
    if ($check5->num_rows == 0) {
        $conn->query("CREATE TABLE `categories` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(100) NOT NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        // Seed default categories
        $conn->query("INSERT IGNORE INTO `categories` (`name`) VALUES ('Facility'), ('Faculty'), ('Administrative')");
        $success = true;
    }

    // Ensure complaints.status includes 'Approved' and 'Denied' in ENUM
    $res = $conn->query("SHOW COLUMNS FROM complaints LIKE 'status'");
    if ($res && $row = $res->fetch_assoc()) {
        $type = $row['Type'];
        if (strpos($type, "'Approved'") === false || strpos($type, "'Denied'") === false) {
            // Extend enum to include Approved and Denied
            $conn->query("ALTER TABLE `complaints` MODIFY `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT 'Pending'");
            $success = true;
        }
    }

    // Create complaint_updates table if it doesn't exist
    $check6 = $conn->query("SHOW TABLES LIKE 'complaint_updates'");
    if ($check6->num_rows == 0) {
        $conn->query("CREATE TABLE `complaint_updates` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `complaint_id` INT(11) NOT NULL,
            `admin_id` INT(11) NOT NULL,
            `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT NULL,
            `message` TEXT NOT NULL,
            `attachments` TEXT DEFAULT NULL COMMENT 'Comma-separated uploaded file paths',
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_complaint_id` (`complaint_id`),
            CONSTRAINT `updates_complaint_fk` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        $success = true;
    }

    if ($success) {
        $_SESSION['message'] = "Database migration completed successfully!";
        header("Location: dashboard.php");
        exit();
    } else {
        $_SESSION['message'] = "Database is already up to date!";
        header("Location: dashboard.php");
        exit();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Migration failed: " . $e->getMessage();
    header("Location: dashboard.php");
    exit();
}
?>

