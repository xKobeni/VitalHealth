<?php
function checkPatientSession() {
    global $conn;
    session_start();
    if (!isset($_SESSION['userid'])) {
        header("Location: /Healthcare/index.php");
        exit;
    }
    
    // Include database connection
    require_once __DIR__ . '/../../config/database.php';
    
    // Verify that the user is actually a patient
    $sql = "SELECT role FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user || $user['role'] !== 'patient') {
        session_destroy();
        header("Location: /Healthcare/index.php");
        exit;
    }
} 