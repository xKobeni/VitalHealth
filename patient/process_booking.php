<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = getPatientId($conn, $_SESSION['userid']);
    $doctor_id = $_POST['doctor_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $status = 'pending';
    $reason = trim($_POST['reason']);

    // Validate input
    if (empty($reason)) {
        $_SESSION['error'] = "Please provide a reason for your visit.";
        header("Location: booked.php?doctorid=" . $doctor_id . "&date=" . $appointment_date);
        exit();
    }

    // Check if the time slot is still available
    $check_sql = "SELECT COUNT(*) as count FROM appointments 
                  WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'confirmed'";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        $_SESSION['error'] = "This time slot is no longer available. Please select another time.";
        header("Location: booked.php?doctorid=" . $doctor_id . "&date=" . $appointment_date);
        exit();
    }

    // Insert the appointment
    $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissss", $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason, $status);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment request submitted successfully. Please wait for doctor's confirmation.";
        header("Location: appointments.php");
        exit();
    } else {
        $_SESSION['error'] = "Error booking appointment: " . $stmt->error;
        header("Location: booked.php?doctorid=" . $doctor_id . "&date=" . $appointment_date);
        exit();
    }
} else {
    header("Location: booking.php");
    exit();
}
?> 