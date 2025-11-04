<?php
include('../db.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_complaint':
        $student_id = intval($_POST['student_id']);
        $title = trim($_POST['title']);
        $category = trim($_POST['category']);
        $description = trim($_POST['description']);
        
        // Handle custom category
        if ($category == 'Others' || $category == 'Other') {
            $custom_category = trim($_POST['custom_category'] ?? '');
            if (!empty($custom_category)) {
                $category = "Others: " . $custom_category;
            } else {
                $category = 'Other'; // If no custom text provided, use "Other"
            }
        }
        
        if (empty($title) || empty($category) || empty($description)) {
            $_SESSION['error'] = 'All fields are required!';
        } else {
            // Handle file uploads if provided
            $uploaded_files = array();
            $upload_dir = '../uploads/';
            
            if (!empty($_FILES['attachments']['name'][0])) {
                foreach ($_FILES['attachments']['name'] as $key => $file_name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['attachments']['tmp_name'][$key];
                        $file_size = $_FILES['attachments']['size'][$key];
                        $file_type = $_FILES['attachments']['type'][$key];
                        
                        // Validate file type (only images)
                        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg');
                        if (in_array($file_type, $allowed_types)) {
                            // Validate file size (max 5MB per file)
                            if ($file_size <= 5000000) {
                                // Generate unique filename
                                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                                $new_filename = uniqid() . '_' . time() . '_' . $key . '.' . $file_ext;
                                $upload_path = $upload_dir . $new_filename;
                                
                                // Move uploaded file
                                if (move_uploaded_file($file_tmp, $upload_path)) {
                                    $uploaded_files[] = 'uploads/' . $new_filename;
                                }
                            }
                        }
                    }
                }
            }
            
            // Prepare attachments string
            $attachments = !empty($uploaded_files) ? implode(',', $uploaded_files) : null;
            
            $stmt = $conn->prepare("INSERT INTO complaints (student_id, title, category, description, attachments) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $student_id, $title, $category, $description, $attachments);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Complaint added successfully!';
            } else {
                $_SESSION['error'] = 'Error adding complaint: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;
        
    case 'delete_complaint':
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE complaints SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Complaint deleted successfully!';
        } else {
            $_SESSION['error'] = 'Error deleting complaint: ' . $stmt->error;
        }
        $stmt->close();
        break;
        
    case 'delete_student':
        $id = intval($_POST['id']);
        
        // Soft delete all complaints for this student
        $stmt1 = $conn->prepare("UPDATE complaints SET deleted_at = NOW() WHERE student_id = ? AND deleted_at IS NULL");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Soft delete the student
        $stmt2 = $conn->prepare("UPDATE students SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL");
        $stmt2->bind_param("i", $id);
        
        if ($stmt2->execute()) {
            $_SESSION['message'] = 'Student deleted successfully!';
        } else {
            $_SESSION['error'] = 'Error deleting student: ' . $stmt2->error;
        }
        $stmt2->close();
        break;
        
    case 'reply_to_complaint':
        $id = intval($_POST['id']);
        $reply = trim($_POST['reply']);
        
        if (empty($reply)) {
            $_SESSION['error'] = 'Admin note cannot be empty!';
        } else {
            $stmt = $conn->prepare("UPDATE complaints SET admin_reply = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $reply, $id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Admin note added successfully!';
            } else {
                $_SESSION['error'] = 'Error adding admin note: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;
        
    case 'edit_admin_note':
        $id = intval($_POST['id']);
        $reply = trim($_POST['reply']);
        
        if (empty($reply)) {
            $_SESSION['error'] = 'Admin note cannot be empty!';
        } else {
            $stmt = $conn->prepare("UPDATE complaints SET admin_reply = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $reply, $id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Admin note updated successfully!';
            } else {
                $_SESSION['error'] = 'Error updating admin note: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;
        
    case 'delete_admin_note':
        $id = intval($_POST['id']);
        
        $stmt = $conn->prepare("UPDATE complaints SET admin_reply = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Admin note deleted successfully!';
        } else {
            $_SESSION['error'] = 'Error deleting admin note: ' . $stmt->error;
        }
        $stmt->close();
        break;

    case 'deny_with_reason':
        $id = intval($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        if ($id <= 0 || $reason === '') {
            $_SESSION['error'] = 'A reason is required to deny a complaint.';
        } else {
            // Ensure complaints.status supports 'Denied' and 'Approved'
            $col = $conn->query("SHOW COLUMNS FROM complaints LIKE 'status'");
            if ($col && $row = $col->fetch_assoc()) {
                if (strpos($row['Type'], "'Denied'") === false || strpos($row['Type'], "'Approved'") === false) {
                    $conn->query("ALTER TABLE `complaints` MODIFY `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT 'Pending'");
                }
            }
            // Update status to Denied and set reason
            $stmt = $conn->prepare("UPDATE complaints SET status = 'Denied', admin_reply = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $reason, $id);
            $ok = $stmt->execute();
            $err = $stmt->error;
            $stmt->close();
            if (!$ok) {
                $_SESSION['error'] = 'Error denying complaint: ' . $err;
                break;
            }
            // Verify persisted
            $check = $conn->prepare("SELECT status FROM complaints WHERE id = ?");
            $check->bind_param("i", $id);
            $check->execute();
            $res = $check->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $check->close();
            if ($row && isset($row['status']) && $row['status'] === 'Denied') {
                $_SESSION['message'] = 'Complaint denied with reason.';
            } else {
                $_SESSION['error'] = 'Complaint status did not update. Please run admin/run_migration.php and try again.';
            }
        }
        break;
        
    case 'edit_student':
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        
        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'Name and email are required!';
        } else {
            if (!empty($password)) {
                $stmt = $conn->prepare("UPDATE students SET name = ?, email = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $email, $password, $id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET name = ?, email = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $email, $id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Student updated successfully!';
            } else {
                $_SESSION['error'] = 'Error updating student: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;
        
    case 'restore_complaint':
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE complaints SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Complaint restored successfully!';
        } else {
            $_SESSION['error'] = 'Error restoring complaint: ' . $stmt->error;
        }
        $stmt->close();
        break;
        
    case 'restore_student':
        $id = intval($_POST['id']);
        
        // Restore all complaints for this student
        $stmt1 = $conn->prepare("UPDATE complaints SET deleted_at = NULL WHERE student_id = ? AND deleted_at IS NOT NULL");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Restore the student
        $stmt2 = $conn->prepare("UPDATE students SET deleted_at = NULL WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt2->bind_param("i", $id);
        
        if ($stmt2->execute()) {
            $_SESSION['message'] = 'Student restored successfully!';
        } else {
            $_SESSION['error'] = 'Error restoring student: ' . $stmt2->error;
        }
        $stmt2->close();
        break;
        
    case 'permanent_delete_complaint':
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM complaints WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Complaint permanently deleted!';
        } else {
            $_SESSION['error'] = 'Error permanently deleting complaint: ' . $stmt->error;
        }
        $stmt->close();
        break;
        
    case 'permanent_delete_student':
        $id = intval($_POST['id']);
        
        // Permanently delete all complaints for this student
        $stmt1 = $conn->prepare("DELETE FROM complaints WHERE student_id = ? AND deleted_at IS NOT NULL");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();
        
        // Permanently delete the student
        $stmt2 = $conn->prepare("DELETE FROM students WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt2->bind_param("i", $id);
        
        if ($stmt2->execute()) {
            $_SESSION['message'] = 'Student permanently deleted!';
        } else {
            $_SESSION['error'] = 'Error permanently deleting student: ' . $stmt2->error;
        }
        $stmt2->close();
        break;
        
    case 'add_student':
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        
        if (empty($name) || empty($email) || empty($password)) {
            $_SESSION['error'] = 'All fields are required!';
        } else {
            // Check if email already exists
            $check = $conn->prepare("SELECT id FROM students WHERE email = ? AND deleted_at IS NULL");
            $check->bind_param("s", $email);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $_SESSION['error'] = 'Email already exists!';
            } else {
                $stmt = $conn->prepare("INSERT INTO students (name, email, password, approved) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("sss", $name, $email, $password);
                
                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Student added successfully!';
                } else {
                    $_SESSION['error'] = 'Error adding student: ' . $stmt->error;
                }
                $stmt->close();
            }
            $check->close();
        }
        break;

    case 'add_update':
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $message = trim($_POST['message'] ?? '');
        if ($id <= 0 || empty($message)) {
            $_SESSION['error'] = 'Status and message are required!';
            break;
        }
        // Upload attachments
        $uploaded_files = array();
        $upload_dir = '../uploads/';
        if (!empty($_FILES['attachments']['name'][0])) {
            foreach ($_FILES['attachments']['name'] as $key => $file_name) {
                if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['attachments']['tmp_name'][$key];
                    $file_size = $_FILES['attachments']['size'][$key];
                    $file_type = $_FILES['attachments']['type'][$key];
                    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/jpg', 'application/pdf');
                    if (in_array($file_type, $allowed_types) && $file_size <= 5000000) {
                        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '_' . time() . '_' . $key . '.' . $file_ext;
                        $upload_path = $upload_dir . $new_filename;
                        if (move_uploaded_file($file_tmp, $upload_path)) {
                            $uploaded_files[] = 'uploads/' . $new_filename;
                        }
                    }
                }
            }
        }
        $attachments = !empty($uploaded_files) ? implode(',', $uploaded_files) : null;
        // Insert update log
        $stmt = $conn->prepare("INSERT INTO complaint_updates (complaint_id, admin_id, status, message, attachments) VALUES (?, ?, ?, ?, ?)");
        $adminId = $_SESSION['admin_id'];
        $stmt->bind_param("iisss", $id, $adminId, $status, $message, $attachments);
        $ok = $stmt->execute();
        $stmt->close();
        // Optionally update complaint status if provided
        if ($status !== '') {
            $stmt2 = $conn->prepare("UPDATE complaints SET status = ?, admin_reply = ? , updated_at = NOW() WHERE id = ?");
            $stmt2->bind_param("ssi", $status, $message, $id);
            $stmt2->execute();
            $stmt2->close();
        }
        $_SESSION['message'] = $ok ? 'Update saved.' : 'Failed to save update.';
        break;

    case 'set_status':
        $id = intval($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if ($id <= 0 || $status === '') {
            $_SESSION['error'] = 'Invalid status update.';
            break;
        }
        $allowed = ['Pending','Approved','Denied','In Progress','Resolved'];
        if (!in_array($status, $allowed)) {
            $_SESSION['error'] = 'Unsupported status.';
            break;
        }
        // Ensure ENUM supports full set
        $col = $conn->query("SHOW COLUMNS FROM complaints LIKE 'status'");
        if ($col && $row = $col->fetch_assoc()) {
            if (strpos($row['Type'], "'Denied'") === false || strpos($row['Type'], "'Approved'") === false) {
                $conn->query("ALTER TABLE `complaints` MODIFY `status` ENUM('Pending','Approved','Denied','In Progress','Resolved') DEFAULT 'Pending'");
            }
        }
        $stmt = $conn->prepare("UPDATE complaints SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();
        if (!$ok) {
            $_SESSION['error'] = 'Error updating status: ' . $err;
        } else {
            // Log to complaint_updates if table exists
            $hasUpdates = $conn->query("SHOW TABLES LIKE 'complaint_updates'");
            if ($hasUpdates && $hasUpdates->num_rows > 0) {
                $msg = 'Status set to ' . $status;
                $adminId = $_SESSION['admin_id'];
                $stmt3 = $conn->prepare("INSERT INTO complaint_updates (complaint_id, admin_id, status, message) VALUES (?, ?, ?, ?)");
                $stmt3->bind_param("iiss", $id, $adminId, $status, $msg);
                $stmt3->execute();
                $stmt3->close();
            }
            $_SESSION['message'] = 'Status updated to ' . $status . '.';
        }
        break;

    // Student approval functionality has been removed

    case 'add_category':
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['error'] = 'Category name is required!';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Category added successfully!';
            } else {
                $_SESSION['error'] = 'Error adding category: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case 'edit_category':
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id <= 0 || $name === '') {
            $_SESSION['error'] = 'Invalid category data!';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Category updated successfully!';
            } else {
                $_SESSION['error'] = 'Error updating category: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case 'delete_category':
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid category!';
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Category deleted successfully!';
            } else {
                $_SESSION['error'] = 'Error deleting category: ' . $stmt->error;
            }
            $stmt->close();
        }
        break;
        
    default:
        $_SESSION['error'] = 'Invalid action!';
}

// Preserve active section
$hash = isset($_POST['active_section']) ? '#' . $_POST['active_section'] : '';
header("Location: dashboard.php" . $hash);
exit();
?>
