<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['doctor_id'], $data['status'])) {
    $doctor_id = (int)$data['doctor_id'];
    $status = (int)$data['status'];

    $stmt = $conn->prepare("UPDATE doctors SET is_active = ? WHERE doctor_id = ?");
    $stmt->bind_param("ii", $status, $doctor_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
}
