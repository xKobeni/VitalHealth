<?php
include 'config/database.php';

session_start();


if (isset($_POST['submit'])) {
    $userid = $_SESSION['userid'];

    $idsql = "SELECT patient_id FROM patients WHERE user_id = ?";
    $stmt = $conn->prepare($idsql);
    $stmt->bind_param("i", $userid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $patientid = $row['patient_id'];
        $doctorid = $_POST['doctor_id'];
        $appointmentdate = $_POST['appointment_date'];
        $appointmenttime = $_POST['appointment_time'];

        $sql = "INSERT INTO appointments(patient_id, doctor_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $patientid, $doctorid, $appointmentdate, $appointmenttime);
        $stmt->execute();

        header('Location: /Healthcare/patientdashboard.php');
    } else {
        echo 'Error! Patient ID not Found';
    }
}
