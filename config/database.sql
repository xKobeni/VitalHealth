CREATE TABLE guest_appointments (
    guest_appointment_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    reason_for_visit TEXT NOT NULL,
    consultation_type ENUM('in-person', 'online') NOT NULL,
    doctor_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(doctor_id)
);

ALTER TABLE appointments 
ADD COLUMN is_guest BOOLEAN DEFAULT FALSE,
ADD COLUMN guest_appointment_id INT,
ADD FOREIGN KEY (guest_appointment_id) REFERENCES guest_appointments(guest_appointment_id); 