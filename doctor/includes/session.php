<?php
session_start();

function checkDoctorSession() {
    if (!isset($_SESSION['userid'])) {
        header("Location: ../index.php");
        exit;
    }

    // Check if user is a doctor
    require_once __DIR__ . '/../../config/database.php';
    global $conn; // Make the connection variable available

    if (!$conn) {
        die("Database connection failed");
    }

    $sql = "SELECT role FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $_SESSION['userid']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || $user['role'] !== 'doctor') {
        header("Location: ../index.php");
        exit;
    }
} 