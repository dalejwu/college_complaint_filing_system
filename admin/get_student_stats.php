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

$stmt = $conn->prepare("SELECT s.*, COUNT(CASE WHEN c.deleted_at IS NULL THEN c.id END) as complaint_count FROM students s LEFT JOIN complaints c ON s.id = c.student_id WHERE s.id = ? GROUP BY s.id");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'complaint_count' => $row['complaint_count'],
        'created_at' => $row['created_at']
    ]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Student not found']);
}

$stmt->close();
?>
