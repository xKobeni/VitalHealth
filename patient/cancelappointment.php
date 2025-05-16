<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

header('Content-Type: application/json');

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

if (!isset($_POST['appointmentId'])) {
    $response['error'] = 'Missing appointment ID';
    echo json_encode($response);
    exit();
}

$appointment_id = (int)$_POST['appointmentId'];
$patient_id = getPatientId($conn, $_SESSION['userid']);

// Get appointment status
$check_sql = "SELECT status FROM appointments WHERE appointment_id = ? AND patient_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $appointment_id, $patient_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    $response['error'] = 'Appointment not found';
    echo json_encode($response);
    exit();
}

$appointment = $result->fetch_assoc();
if (!in_array($appointment['status'], ['pending', 'scheduled'])) {
    $response['error'] = 'Only pending or scheduled appointments can be cancelled';
    echo json_encode($response);
    exit();
}

// Handle the cancellation based on status
if ($appointment['status'] === 'pending') {
    // For pending appointments, use a default reason
    $reason = 'Cancelled by patient';
} else {
    // For approved appointments, require a reason
    if (!isset($_POST['reason']) || empty(trim($_POST['reason']))) {
        $response['error'] = 'Please provide a reason for cancellation';
        echo json_encode($response);
        exit();
    }
    $reason = trim($_POST['reason']);
}

// Update the appointment status and add the cancellation reason
$sql = "UPDATE appointments SET status = 'cancelled', remark = ? WHERE appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $reason, $appointment_id);

if ($stmt->execute()) {
    $response['success'] = true;
    $response['remark'] = $reason;
} else {
    $response['error'] = 'Failed to cancel appointment';
}

echo json_encode($response);
