<?php
include 'config/database.php';
session_start();

if (isset($_POST['submit'])) {
    $appointment_id = $_POST['appointment_id'];
    $patientid = $_POST['patient_id'];
    $doctorid = $_POST['recorded_by'];
    $assessment = $_POST['assessment'];
    $medication = $_POST['medication'];
    $notes = $_POST['notes'];
    $currentDate = date('Y-m-d');

    // Insert into medical_history
    $sql = "INSERT INTO medical_history(patient_id, record_date, assessment, medication, notes, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssi", $patientid, $currentDate, $assessment, $medication, $notes, $doctorid);
    $stmt->execute();

    // Update appointment status to 'completed'
    $updateSql = "UPDATE appointments SET status = 'completed' WHERE appointment_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $appointment_id);
    $updateStmt->execute();

    header('Location: /Healthcare/doctordashboard.php');
    exit;
}
