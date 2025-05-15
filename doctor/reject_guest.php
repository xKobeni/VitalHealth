<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_appointment_id = filter_input(INPUT_POST, 'guest_appointment_id', FILTER_VALIDATE_INT);
    
    if ($guest_appointment_id) {
        // Update the appointment status
        $sql = "UPDATE guest_appointments SET status = 'rejected' WHERE guest_appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $guest_appointment_id);
        
        if ($stmt->execute()) {
            // Get appointment details for email
            $sql = "SELECT g.*, d.full_name as doctor_name 
                    FROM guest_appointments g 
                    JOIN doctors d ON g.doctor_id = d.doctor_id 
                    WHERE g.guest_appointment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $guest_appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $appointment = $result->fetch_assoc();
            
            // Send email notification
            $to = $appointment['email'];
            $subject = "Appointment Rejected - VitalHealth";
            $message = "Dear " . $appointment['full_name'] . ",\n\n";
            $message .= "We regret to inform you that your appointment request with Dr. " . $appointment['doctor_name'] . " has been rejected.\n";
            $message .= "Date: " . $appointment['appointment_date'] . "\n";
            $message .= "Time: " . $appointment['appointment_time'] . "\n\n";
            $message .= "Please try booking another appointment at a different time.\n\n";
            $message .= "Best regards,\nVitalHealth Team";
            
            mail($to, $subject, $message);
            
            header("Location: doctordashboard.php?success=1");
            exit;
        }
    }
    
    header("Location: doctordashboard.php?error=1");
    exit;
} else {
    header("Location: doctordashboard.php");
    exit;
}
?> 