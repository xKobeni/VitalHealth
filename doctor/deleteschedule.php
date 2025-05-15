<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

$doctor_id = getDoctorId($conn, $_SESSION['userid']);

// Check if schedule ID is provided
if (!isset($_GET['id'])) {
    header("Location: doctorschedule.php");
    exit;
}

$schedule_id = $_GET['id'];

// Verify that the schedule belongs to the doctor
$check_sql = "SELECT * FROM doctor_schedule WHERE schedule_id = ? AND doctor_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $schedule_id, $doctor_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header("Location: doctorschedule.php?error=unauthorized");
    exit;
}

// Delete the schedule
$sql = "DELETE FROM doctor_schedule WHERE schedule_id = ? AND doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $schedule_id, $doctor_id);

if ($stmt->execute()) {
    header("Location: doctorschedule.php?success=deleted");
} else {
    header("Location: doctorschedule.php?error=failed");
}
exit; 