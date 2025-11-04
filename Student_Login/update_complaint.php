<?php
include('../db.php');
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$complaint_id = intval($_POST['complaint_id']);
$title = trim($_POST['title']);
$category = trim($_POST['what_category'] ?? '');
$description = trim($_POST['description']);
$respondents = trim($_POST['respondents'] ?? '');
$respondent_detail = trim($_POST['respondent_detail'] ?? '');
$respondent_count = isset($_POST['respondent_count']) && $_POST['respondent_count'] !== '' ? intval($_POST['respondent_count']) : NULL;
if ($respondents === 'Others' && !empty($respondent_detail)) {
    $respondents = $respondent_detail;
}

// Category is now free-text

// Verify the complaint belongs to the logged-in student
$check_stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ? AND student_id = ?");
$check_stmt->bind_param("ii", $complaint_id, $student_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = 'Complaint not found or access denied';
    header("Location: view_complaints.php");
    exit();
}

$complaint = $result->fetch_assoc();

// Edit timer removed: always allow updates

if (empty($title) || empty($category) || empty($description) || empty($respondents)) {
    $_SESSION['error'] = "Please fill in all required fields.";
} else {
    // Handle file uploads
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
    
    // Merge new attachments with existing ones
    $existing_attachments = !empty($complaint['attachments']) ? explode(',', $complaint['attachments']) : array();
    $all_attachments = array_merge($existing_attachments, $uploaded_files);
    $attachments = !empty($all_attachments) ? implode(',', $all_attachments) : $complaint['attachments'];
    
    // Update complaint
    $stmt = $conn->prepare("UPDATE complaints SET title = ?, category = ?, description = ?, respondents = ?, respondent_count = ?, attachments = ?, last_edited_at = NOW(), updated_at = NOW() WHERE id = ? AND student_id = ?");
    $stmt->bind_param("ssssissi", $title, $category, $description, $respondents, $respondent_count, $attachments, $complaint_id, $student_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "Complaint updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating complaint: " . $stmt->error;
    }
    $stmt->close();
}

header("Location: view_complaints.php");
exit();
?>

