<?php
include('../../includes/db.php');
session_start();

if (!isset($_SESSION['student_id'])) {
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
$student_id = $_SESSION['student_id'];

$stmt = $conn->prepare("SELECT * FROM complaints WHERE id = ? AND student_id = ?");
$stmt->bind_param("ii", $id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();

    if (!empty($row['attachments'])) {
        $row['attachments_array'] = explode(',', $row['attachments']);
    } else {
        $row['attachments_array'] = array();
    }

    echo json_encode($row);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Complaint not found or access denied']);
}

$stmt->close();
