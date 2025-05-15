<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $doctor_id = filter_input(INPUT_POST, 'doctor_id', FILTER_VALIDATE_INT);
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone_number = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING);
    $date_of_birth = filter_input(INPUT_POST, 'date_of_birth', FILTER_SANITIZE_STRING);
    $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_STRING);
    $consultation_type = filter_input(INPUT_POST, 'consultation_type', FILTER_SANITIZE_STRING);
    $reason_for_visit = filter_input(INPUT_POST, 'reason_for_visit', FILTER_SANITIZE_STRING);
    $appointment_date = filter_input(INPUT_POST, 'appointment_date', FILTER_SANITIZE_STRING);
    $appointment_time = filter_input(INPUT_POST, 'appointment_time', FILTER_SANITIZE_STRING);

    // Validate required fields
    if (!$doctor_id || !$full_name || !$email || !$phone_number || !$date_of_birth || !$gender || !$consultation_type || !$reason_for_visit || !$appointment_date || !$appointment_time) {
        header('Location: guest_booking.php?error=missing_fields');
        exit;
    }

    // Insert into guest_appointments table
    $sql = "INSERT INTO guest_appointments (doctor_id, full_name, email, phone_number, date_of_birth, gender, consultation_type, reason_for_visit, appointment_date, appointment_time, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssss", $doctor_id, $full_name, $email, $phone_number, $date_of_birth, $gender, $consultation_type, $reason_for_visit, $appointment_date, $appointment_time);
    
    if ($stmt->execute()) {
        header('Location: guest_booking_success.php');
        exit;
    } else {
        header('Location: guest_booking.php?error=booking_failed');
        exit;
    }
} else {
    header('Location: guest_booking.php');
    exit;
}
?> 