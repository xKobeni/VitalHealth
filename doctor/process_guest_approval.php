<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once 'includes/session.php';

checkDoctorSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id']) && isset($_POST['action'])) {
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    $doctor_id = getDoctorId($conn, $_SESSION['userid']);

    if ($appointment_id && in_array($action, ['approve', 'reject'])) {
        // Get appointment details
        $sql = "SELECT * FROM guest_appointments WHERE guest_appointment_id = ? AND doctor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $appointment_id, $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $appointment = $result->fetch_assoc();

        if ($appointment) {
            if ($action === 'approve') {
                // Update appointment status to confirmed
                $update_sql = "UPDATE guest_appointments SET status = 'scheduled' WHERE guest_appointment_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $appointment_id);
                
                if ($update_stmt->execute()) {
                    // Generate temporary password for guest account
                    $temp_password = generateTemporaryPassword();
                    
                    // Get doctor's name
                    $doctor_name = getDoctorName($conn, $_SESSION['userid']);
                    
                    // Check if email already exists in users table
                    $check_email_sql = "SELECT user_id FROM users WHERE email = ?";
                    $check_email_stmt = $conn->prepare($check_email_sql);
                    $check_email_stmt->bind_param("s", $appointment['email']);
                    $check_email_stmt->execute();
                    $email_result = $check_email_stmt->get_result();
                    
                    if ($email_result->num_rows > 0) {
                        // Email exists, get the user_id
                        $existing_user = $email_result->fetch_assoc();
                        $user_id = $existing_user['user_id'];
                        
                        // Check if patient record exists
                        $check_patient_sql = "SELECT patient_id FROM patients WHERE user_id = ?";
                        $check_patient_stmt = $conn->prepare($check_patient_sql);
                        $check_patient_stmt->bind_param("i", $user_id);
                        $check_patient_stmt->execute();
                        $patient_result = $check_patient_stmt->get_result();
                        
                        if ($patient_result->num_rows === 0) {
                            // Create patient record if it doesn't exist
                            $create_patient_sql = "INSERT INTO patients (user_id, full_name, contact_number, address, date_of_birth, gender) 
                                                 VALUES (?, ?, ?, ?, ?, ?)";
                            $create_patient_stmt = $conn->prepare($create_patient_sql);
                            
                            // Use the guest's information instead of default values
                            $address = "Not provided"; // We don't collect address in guest booking
                            $date_of_birth = $appointment['date_of_birth'];
                            $gender = $appointment['gender'];
                            
                            $create_patient_stmt->bind_param("isssss", 
                                $user_id,
                                $appointment['full_name'],
                                $appointment['phone_number'],
                                $address,
                                $date_of_birth,
                                $gender
                            );
                            
                            if (!$create_patient_stmt->execute()) {
                                header("Location: appointments.php?error=patient_creation_failed");
                                exit;
                            }
                        }
                    } else {
                        // Email doesn't exist, create new user account
                        $guest_email = $appointment['email'];
                        $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
                        
                        $create_account_sql = "INSERT INTO users (email, password, role) VALUES (?, ?, 'patient')";
                        $create_account_stmt = $conn->prepare($create_account_sql);
                        $create_account_stmt->bind_param("ss", $guest_email, $hashed_password);
                        
                        if ($create_account_stmt->execute()) {
                            $user_id = $conn->insert_id;
                            
                            // Create patient record
                            $create_patient_sql = "INSERT INTO patients (user_id, full_name, contact_number, address, date_of_birth, gender) 
                                                 VALUES (?, ?, ?, ?, ?, ?)";
                            $create_patient_stmt = $conn->prepare($create_patient_sql);
                            
                            // Use the guest's information instead of default values
                            $address = "Not provided"; // We don't collect address in guest booking
                            $date_of_birth = $appointment['date_of_birth'];
                            $gender = $appointment['gender'];
                            
                            $create_patient_stmt->bind_param("isssss", 
                                $user_id,
                                $appointment['full_name'],
                                $appointment['phone_number'],
                                $address,
                                $date_of_birth,
                                $gender
                            );
                            
                            if (!$create_patient_stmt->execute()) {
                                // If patient creation fails, delete the user account
                                $delete_user_sql = "DELETE FROM users WHERE user_id = ?";
                                $delete_user_stmt = $conn->prepare($delete_user_sql);
                                $delete_user_stmt->bind_param("i", $user_id);
                                $delete_user_stmt->execute();
                                
                                header("Location: appointments.php?error=patient_creation_failed");
                                exit;
                            }
                        } else {
                            header("Location: appointments.php?error=account_creation_failed");
                            exit;
                        }
                    }
                    
                    // Send confirmation email
                    $subject = "Appointment Confirmed - VitalHealth";
                    $message = "Dear " . $appointment['full_name'] . ",\n\n";
                    $message .= "Your appointment has been confirmed by Dr. " . $doctor_name . " for " . date('F j, Y', strtotime($appointment['appointment_date'])) . " at " . date('h:i A', strtotime($appointment['appointment_time'])) . ".\n\n";
                    $message .= "Please arrive 15 minutes before your scheduled time.\n\n";
                    
                    // Include account credentials for both new and existing users
                    $message .= "Your account credentials:\n";
                    $message .= "Email: " . $appointment['email'] . "\n";
                    
                    if (isset($existing_user)) {
                        // For existing users, remind them to use their existing password
                        $message .= "Please use your existing password to log in.\n\n";
                    } else {
                        // For new users, include the temporary password
                        $message .= "Password: " . $temp_password . "\n\n";
                        $message .= "For security reasons, please change your password after your first login.\n\n";
                    }
                    
                    $message .= "Best regards,\nVitalHealth Team";
                    
                    // Try to send email and get debug info
                    $emailSent = @sendEmail($appointment['email'], $subject, $message);
                    
                    // Read the debug log
                    $debug_log = __DIR__ . '/../config/email_debug.log';
                    $debug_info = file_exists($debug_log) ? file_get_contents($debug_log) : 'No debug log found';
                    
                    if ($emailSent) {
                        header("Location: appointments.php?success=1&email=1&debug=" . urlencode($debug_info));
                    } else {
                        header("Location: appointments.php?success=1&email=0&debug=" . urlencode($debug_info));
                    }
                    exit;
                }
            } else {
                // Update appointment status to rejected
                $update_sql = "UPDATE guest_appointments SET status = 'rejected' WHERE guest_appointment_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $appointment_id);
                
                if ($update_stmt->execute()) {
                    // Send rejection email
                    $subject = "Appointment Update - VitalHealth";
                    $message = "Dear " . $appointment['full_name'] . ",\n\n";
                    $message .= "We regret to inform you that your appointment request for " . date('F j, Y', strtotime($appointment['appointment_date'])) . " at " . date('h:i A', strtotime($appointment['appointment_time'])) . " has been rejected.\n\n";
                    $message .= "Please feel free to book another appointment at a different time.\n\n";
                    $message .= "Best regards,\nVitalHealth Team";
                    
                    // Try to send email and get debug info
                    $emailSent = @sendEmail($appointment['email'], $subject, $message);
                    
                    // Read the debug log
                    $debug_log = __DIR__ . '/../config/email_debug.log';
                    $debug_info = file_exists($debug_log) ? file_get_contents($debug_log) : 'No debug log found';
                    
                    if ($emailSent) {
                        header("Location: appointments.php?success=1&email=1&debug=" . urlencode($debug_info));
                    } else {
                        header("Location: appointments.php?success=1&email=0&debug=" . urlencode($debug_info));
                    }
                    exit;
                }
            }
        }
    }
}

// If we get here, something went wrong
header("Location: appointments.php?error=1");
exit; 