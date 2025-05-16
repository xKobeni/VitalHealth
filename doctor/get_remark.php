<?php
require_once '../config/database.php';
require_once '../config/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['appointment_id']) || !isset($_GET['type'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$appointment_id = (int)$_GET['appointment_id'];
$type = $_GET['type'] === 'guest' ? 'guest_appointments' : 'appointments';
$id_field = $type === 'guest_appointments' ? 'guest_appointment_id' : 'appointment_id';
$doctor_id = getDoctorId($conn, $_SESSION['userid']);

// Get the remark
$sql = "SELECT remark FROM $type WHERE $id_field = ? AND doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $appointment_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'remark' => $row['remark']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Appointment not found'
    ]);
} 