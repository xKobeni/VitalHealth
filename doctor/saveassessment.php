<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $patient_id = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
    $appointment_id = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $is_guest = isset($_POST['is_guest']) && $_POST['is_guest'] === '1';
    $assessment = trim(filter_input(INPUT_POST, 'assessment', FILTER_SANITIZE_STRING));
    $medication = trim(filter_input(INPUT_POST, 'medication', FILTER_SANITIZE_STRING));
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING));
    $recorded_by = getDoctorId($conn, $_SESSION['userid']);

    // Debug information
    error_log("POST Data: " . print_r($_POST, true));
    error_log("Patient ID: $patient_id");
    error_log("Appointment ID: $appointment_id");
    error_log("Is Guest: " . ($is_guest ? 'true' : 'false'));
    error_log("Doctor ID: $recorded_by");

    // Validate required fields
    if (!$patient_id || !$appointment_id || empty($assessment) || empty($medication)) {
        error_log("Validation failed - Required fields missing");
        header("Location: finalassessment.php?patientid=" . $patient_id . "&appointmentid=" . $appointment_id . "&is_guest=" . ($is_guest ? '1' : '0') . "&error=2");
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        if ($is_guest) {
            // First verify the guest appointment exists and belongs to this doctor
            $check_sql = "SELECT * FROM guest_appointments WHERE guest_appointment_id = ? AND doctor_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception("Failed to prepare guest appointment check statement: " . $conn->error);
            }
            
            $check_stmt->bind_param("ii", $appointment_id, $recorded_by);
            if (!$check_stmt->execute()) {
                throw new Exception("Failed to check guest appointment: " . $check_stmt->error);
            }
            
            $appointment_result = $check_stmt->get_result();
            if ($appointment_result->num_rows === 0) {
                throw new Exception("Guest appointment not found or not authorized");
            }

            $guest_data = $appointment_result->fetch_assoc();

            // Find the patient ID using the guest's email
            $find_patient_sql = "SELECT p.patient_id 
                               FROM patients p 
                               JOIN users u ON p.user_id = u.user_id 
                               WHERE u.email = ?";
            $find_patient_stmt = $conn->prepare($find_patient_sql);
            if (!$find_patient_stmt) {
                throw new Exception("Failed to prepare patient lookup statement: " . $conn->error);
            }

            $find_patient_stmt->bind_param("s", $guest_data['email']);
            if (!$find_patient_stmt->execute()) {
                throw new Exception("Failed to find patient: " . $find_patient_stmt->error);
            }

            $patient_result = $find_patient_stmt->get_result();
            if ($patient_result->num_rows === 0) {
                throw new Exception("No patient account found for this guest");
            }

            $patient_data = $patient_result->fetch_assoc();
            $patient_id = $patient_data['patient_id'];

            // Insert into medical history using the found patient ID
            $sql = "INSERT INTO medical_history (patient_id, record_date, assessment, medication, notes, recorded_by) 
                    VALUES (?, NOW(), ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare medical history statement: " . $conn->error);
            }
            
            $stmt->bind_param("isssi", $patient_id, $assessment, $medication, $notes, $recorded_by);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert medical history: " . $stmt->error);
            }

            // Update guest appointment status
            $update_sql = "UPDATE guest_appointments SET status = 'completed' WHERE guest_appointment_id = ? AND doctor_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Failed to prepare guest appointment update statement: " . $conn->error);
            }
            
            $update_stmt->bind_param("ii", $appointment_id, $recorded_by);
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update guest appointment status: " . $update_stmt->error);
            }
        } else {
            // Check if medical_history table exists
            $check_table = "SHOW TABLES LIKE 'medical_history'";
            $table_result = $conn->query($check_table);
            if ($table_result->num_rows === 0) {
                // Create medical_history table if it doesn't exist
                $create_table = "CREATE TABLE IF NOT EXISTS medical_history (
                    record_id INT AUTO_INCREMENT PRIMARY KEY,
                    patient_id INT NOT NULL,
                    record_date DATETIME NOT NULL,
                    assessment TEXT NOT NULL,
                    medication TEXT NOT NULL,
                    notes TEXT,
                    recorded_by INT NOT NULL,
                    FOREIGN KEY (patient_id) REFERENCES patients(patient_id),
                    FOREIGN KEY (recorded_by) REFERENCES doctors(doctor_id)
                )";
                if (!$conn->query($create_table)) {
                    throw new Exception("Failed to create medical_history table: " . $conn->error);
                }
            }

            // First verify the appointment exists and belongs to this doctor
            $check_sql = "SELECT * FROM appointments WHERE appointment_id = ? AND doctor_id = ?";
            $check_stmt = $conn->prepare($check_sql);
            if (!$check_stmt) {
                throw new Exception("Failed to prepare appointment check statement: " . $conn->error);
            }
            
            $check_stmt->bind_param("ii", $appointment_id, $recorded_by);
            if (!$check_stmt->execute()) {
                throw new Exception("Failed to check appointment: " . $check_stmt->error);
            }
            
            $appointment_result = $check_stmt->get_result();
            if ($appointment_result->num_rows === 0) {
                throw new Exception("Appointment not found or not authorized");
            }

            // Insert into medical history
            $sql = "INSERT INTO medical_history (patient_id, record_date, assessment, medication, notes, recorded_by) 
                    VALUES (?, NOW(), ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Failed to prepare medical history statement: " . $conn->error);
            }
            
            $stmt->bind_param("isssi", $patient_id, $assessment, $medication, $notes, $recorded_by);
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert medical history: " . $stmt->error);
            }

            // Update appointment status
            $update_sql = "UPDATE appointments SET status = 'completed' WHERE appointment_id = ? AND doctor_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            if (!$update_stmt) {
                throw new Exception("Failed to prepare appointment update statement: " . $conn->error);
            }
            
            $update_stmt->bind_param("ii", $appointment_id, $recorded_by);
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update appointment status: " . $update_stmt->error);
            }
        }

        if ($update_stmt->affected_rows === 0) {
            throw new Exception("No appointment was updated");
        }

        $conn->commit();
        error_log("Assessment saved successfully for " . ($is_guest ? "guest" : "patient") . "_id: $patient_id");
        header("Location: doctordashboard.php?success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Assessment save error: " . $e->getMessage());
        error_log("SQL State: " . $conn->sqlstate);
        error_log("Error Code: " . $conn->errno);
        error_log("Error Message: " . $conn->error);
        header("Location: finalassessment.php?patientid=" . $patient_id . "&appointmentid=" . $appointment_id . "&is_guest=" . ($is_guest ? '1' : '0') . "&error=1");
        exit;
    }
} else {
    header("Location: doctordashboard.php");
    exit;
}
?>
