<?php
include 'config/database.php';
include 'config/functions.php';

session_start();
$doctor_id = getDoctorId($conn, $_SESSION['userid']);


if (isset($_POST['submit'])) {
    foreach ($_POST['schedule'] as $day => $data) {
        $isOff = isset($data['off']) && $data['off'] == '1';

        if ($isOff) {
            // Set both times to NULL
            $start = null;
            $end = null;
        } else {
            // Sanitize and prepare the time inputs
            $start = !empty($data['start']) ? $conn->real_escape_string($data['start']) : null;
            $end = !empty($data['end']) ? $conn->real_escape_string($data['end']) : null;
        }

        // Check if the record already exists
        $checkSql = "SELECT schedule_id FROM doctor_schedule WHERE doctor_id = $doctor_id AND day_of_week = '$day'";
        $result = $conn->query($checkSql);

        if ($result && $result->num_rows > 0) {
            // Update existing record
            $sql = "UPDATE doctor_schedule 
                    SET start_time = " . ($start ? "'$start'" : "NULL") . ",
                        end_time = " . ($end ? "'$end'" : "NULL") . "
                    WHERE doctor_id = $doctor_id AND day_of_week = '$day'";
        } else {
            // Insert new record
            $sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time)
                    VALUES ($doctor_id, '$day', " . ($start ? "'$start'" : "NULL") . ", " . ($end ? "'$end'" : "NULL") . ")";
        }

        $conn->query($sql);
    }
    header('Location: /Healthcare/doctordashboard.php');
    exit;
}
