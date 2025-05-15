<?php
function getPatientName(mysqli $conn, int $user_id): ?string
{
    $sql = "SELECT full_name FROM patients WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['full_name'];
    }

    return null;
}
function getPatientId(mysqli $conn, int $user_id): ?string
{
    $sql = "SELECT patient_id FROM patients WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['patient_id'];
    }

    return null;
}
function getDoctorId(mysqli $conn, int $user_id): ?string
{
    $sql = "SELECT doctor_id FROM doctors WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['doctor_id'];
    }

    return null;
}

function getDoctorName(mysqli $conn, int $user_id): ?string
{
    $sql = "SELECT full_name FROM doctors WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return $row['full_name'];
    }

    return null;
}

function sendEmail($to, $subject, $message) {
    // Create debug information
    $debug_info = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'subject' => $subject
    ];

    try {
        // Include PHPMailer files
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';

        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        // TODO: Replace these with your actual Gmail credentials
        // For Gmail, you need to:
        // 1. Enable 2-factor authentication on your Google account
        // 2. Generate an App Password: Google Account -> Security -> App Passwords
        $mail->Username = 'johnadrian.perce@gmail.com'; // Your Gmail address
        $mail->Password = 'ivad ktgs djku pxcw';    // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('johnadrian.perce@gmail.com', 'VitalHealth'); // Use the same email as Username
        $mail->addAddress($to);
        $mail->addReplyTo('johnadrian.perce@gmail.com', 'VitalHealth');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($message);
        $mail->AltBody = strip_tags($message);

        // Send email
        $mail->send();
        
        $debug_info['result'] = 'Success';
        $debug_info['error'] = null;
        
    } catch (Exception $e) {
        $debug_info['result'] = 'Failed';
        $debug_info['error'] = [
            'message' => $mail->ErrorInfo,
            'code' => $e->getCode()
        ];
        
        // Log to file
        $debug_log = __DIR__ . '/email_debug.log';
        file_put_contents($debug_log, json_encode($debug_info, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
        
        return false;
    }

    // Log to file
    $debug_log = __DIR__ . '/email_debug.log';
    file_put_contents($debug_log, json_encode($debug_info, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    
    return true;
}

function generateTemporaryPassword() {
    $length = 12;
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
