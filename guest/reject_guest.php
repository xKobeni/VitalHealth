<?php
include '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    
    if ($appointment_id) {
        // Update appointment status to rejected
        $sql = "UPDATE guest_appointments SET status = 'rejected' WHERE guest_appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $appointment_id);
        
        if ($stmt->execute()) {
            // Get appointment details for email
            $sql = "SELECT ga.*, d.full_name as doctor_name, d.email as doctor_email 
                    FROM guest_appointments ga 
                    JOIN doctors d ON ga.doctor_id = d.doctor_id 
                    WHERE ga.guest_appointment_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $appointment = $result->fetch_assoc();
            
            if ($appointment) {
                // Send email to guest
                $to = $appointment['email'];
                $subject = "Appointment Request Declined - VitalHealth";
                $message = "Dear " . $appointment['full_name'] . ",\n\n";
                $message .= "We regret to inform you that your appointment request with Dr. " . $appointment['doctor_name'] . " has been declined.\n";
                $message .= "Date: " . date('F j, Y', strtotime($appointment['appointment_date'])) . "\n";
                $message .= "Time: " . date('h:i A', strtotime($appointment['appointment_time'])) . "\n\n";
                $message .= "Please try booking another appointment at a different time.\n\n";
                $message .= "Best regards,\nVitalHealth Team";
                
                $headers = "From: noreply@vitalhealth.com";
                
                mail($to, $subject, $message, $headers);
                
                header('Location: ../doctordashboard.php?success=appointment_rejected');
                exit;
            }
        }
    }
    
    header('Location: ../doctordashboard.php?error=rejection_failed');
    exit;
} else {
    header('Location: ../doctordashboard.php');
    exit;
}
?> 