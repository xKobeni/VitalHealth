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
