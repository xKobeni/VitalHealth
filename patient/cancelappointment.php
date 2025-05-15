<?php
include 'config/database.php';

session_start();



if (isset($_POST['submit'])) {

    $appointmentid = $_POST['appointmentid'];

    $sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentid);
    $stmt->execute();

    header('Location: /Healthcare/appointments.php');
}
