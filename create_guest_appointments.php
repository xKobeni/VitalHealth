<?php
include 'config/database.php';

// SQL to create guest_appointments table
$sql = "CREATE TABLE IF NOT EXISTS guest_appointments (
    guest_appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    doctor_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    consultation_type ENUM('in-person', 'online') NOT NULL,
    reason_for_visit TEXT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table guest_appointments created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?> 