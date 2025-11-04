<?php
include('../db.php');
session_start();

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID is required']);
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT c.*, s.name as student_name FROM complaints c LEFT JOIN students s ON c.student_id = s.id WHERE c.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    if (isset($row['attachments']) && !empty($row['attachments'])) {
        $row['attachments_array'] = explode(',', $row['attachments']);
    } else {
        $row['attachments_array'] = array();
    }
    
    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Complaint not found']);
}

$stmt->close();
?>
