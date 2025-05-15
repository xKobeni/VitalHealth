<?php
include '../config/database.php';
include '../config/functions.php';

// Check if doctor_id is provided
if (!isset($_GET['doctor_id'])) {
    header("Location: index.php");
    exit;
}

$doctor_id = $_GET['doctor_id'];

// Get doctor information
$sql = "SELECT d.*, u.email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE d.doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

if (!$doctor) {
    header("Location: index.php?error=doctor_not_found");
    exit;
}

// Get doctor's schedule
$schedule_sql = "SELECT * FROM doctor_schedule WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("i", $doctor_id);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedules = [];
while ($row = $schedule_result->fetch_assoc()) {
    $schedules[] = $row;
}

// Get booked appointments for the next 30 days
$booked_sql = "SELECT appointment_date, appointment_time 
               FROM guest_appointments 
               WHERE doctor_id = ? 
               AND appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
               AND status != 'rejected'";
$booked_stmt = $conn->prepare($booked_sql);
$booked_stmt->bind_param("i", $doctor_id);
$booked_stmt->execute();
$booked_result = $booked_stmt->get_result();
$booked_slots = [];
while ($row = $booked_result->fetch_assoc()) {
    $booked_slots[$row['appointment_date']][] = $row['appointment_time'];
}

// Consultation types
$consultation_types = [
    'in-person' => 'In-person',
    'online' => 'Online'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate full name
    $full_name = trim($_POST['full_name']);
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 50) {
        $errors[] = "Full name must be between 2 and 50 characters";
    }

    // Validate email
    $email = trim($_POST['email']);
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    // Validate phone number
    $phone_number = trim($_POST['phone_number']);
    if (empty($phone_number)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone_number)) {
        $errors[] = "Phone number must be between 10 and 15 digits";
    }

    // Validate gender
    $gender = $_POST['gender'];
    if (empty($gender)) {
        $errors[] = "Gender is required";
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'])) {
        $errors[] = "Invalid gender selection";
    }

    // Validate date of birth
    $date_of_birth = $_POST['date_of_birth'];
    if (empty($date_of_birth)) {
        $errors[] = "Date of birth is required";
    } else {
        $dob = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        if ($age < 0 || $age > 120) {
            $errors[] = "Invalid date of birth";
        }
    }

    // Validate appointment date
    $appointment_date = $_POST['appointment_date'];
    if (empty($appointment_date)) {
        $errors[] = "Appointment date is required";
    } else {
        $app_date = new DateTime($appointment_date);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        if ($app_date < $today) {
            $errors[] = "Appointment date cannot be in the past";
        }
    }

    // Validate appointment time
    $appointment_time = $_POST['appointment_time'];
    if (empty($appointment_time)) {
        $errors[] = "Appointment time is required";
    }

    // Validate consultation type
    $consultation_type = $_POST['consultation_type'];
    if (empty($consultation_type)) {
        $errors[] = "Consultation type is required";
    } elseif (!array_key_exists($consultation_type, $consultation_types)) {
        $errors[] = "Invalid consultation type";
    }

    // Validate reason for visit
    $reason_for_visit = trim($_POST['reason_for_visit']);
    if (empty($reason_for_visit)) {
        $errors[] = "Reason for visit is required";
    } elseif (strlen($reason_for_visit) < 10 || strlen($reason_for_visit) > 500) {
        $errors[] = "Reason for visit must be between 10 and 500 characters";
    }

    // Check if the selected time slot is available
    if (!empty($appointment_date) && !empty($appointment_time)) {
        if (isset($booked_slots[$appointment_date]) && in_array($appointment_time, $booked_slots[$appointment_date])) {
            $errors[] = "Selected time slot is already booked";
        }
    }

    if (empty($errors)) {
    // Insert the appointment
    $insert_sql = "INSERT INTO guest_appointments (doctor_id, full_name, email, phone_number, 
                  gender, date_of_birth, appointment_date, appointment_time, consultation_type, reason_for_visit, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("isssssssss", $doctor_id, $full_name, $email, $phone_number, 
                           $gender, $date_of_birth, $appointment_date, $appointment_time, $consultation_type, $reason_for_visit);
    
    if ($insert_stmt->execute()) {
        header("Location: booking_success.php");
        exit;
    } else {
            $errors[] = "Error booking appointment. Please try again.";
        }
    }
}

// Generate available dates for the next 30 days
$available_dates = [];
$current_date = new DateTime();
$end_date = (new DateTime())->modify('+30 days');

while ($current_date <= $end_date) {
    $day_of_week = $current_date->format('l');
    foreach ($schedules as $schedule) {
        if ($schedule['day_of_week'] === $day_of_week) {
            $date_str = $current_date->format('Y-m-d');
            $available_dates[] = [
                'date' => $date_str,
                'day' => $day_of_week,
                'display' => $current_date->format('F j, Y (l)')
            ];
            break;
        }
    }
    $current_date->modify('+1 day');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <a href="view_doctor.php?id=<?php echo $doctor_id; ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Doctor Profile
            </a>

            <!-- Doctor Information Card -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-md text-6xl text-blue-500"></i>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">Book Appointment with Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                        <p class="text-lg text-gray-600 mb-4"><?php echo htmlspecialchars($doctor['department']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                        <ul class="list-disc list-inside">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6" onsubmit="return validateForm()">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="full_name" required minlength="2" maxlength="50"
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                            <input type="email" name="email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                            <input type="tel" name="phone_number" required pattern="[0-9]{10,15}"
                                   value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                            <select name="gender" required
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                            <input type="date" name="date_of_birth" required
                                   value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Consultation Type</label>
                            <select name="consultation_type" required
                                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select Consultation Type</option>
                                <?php foreach ($consultation_types as $value => $label): ?>
                                    <option value="<?php echo $value; ?>" <?php echo (isset($_POST['consultation_type']) && $_POST['consultation_type'] === $value) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Date</label>
                        <select name="appointment_date" id="appointment_date" required
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Choose a date</option>
                            <?php foreach ($available_dates as $date): ?>
                                <option value="<?php echo $date['date']; ?>">
                                    <?php echo $date['display']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Time</label>
                        <div id="time_slots" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <p class="text-gray-500">Please select a date first</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Visit</label>
                        <textarea name="reason_for_visit" required rows="4"
                                  class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="text-center">
                        <button type="submit" 
                                class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition">
                            Book Appointment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const schedules = <?php echo json_encode($schedules); ?>;
        const bookedSlots = <?php echo json_encode($booked_slots); ?>;

        document.getElementById('appointment_date').addEventListener('change', function() {
            const selectedDate = this.value;
            const dayOfWeek = new Date(selectedDate).toLocaleDateString('en-US', { weekday: 'long' });
            const timeSlotsContainer = document.getElementById('time_slots');
            timeSlotsContainer.innerHTML = '';

            // Find schedule for selected day
            const daySchedule = schedules.find(schedule => schedule.day_of_week === dayOfWeek);
            
            if (!daySchedule) {
                timeSlotsContainer.innerHTML = '<p class="text-red-500">No available slots for this day</p>';
                return;
            }

            // Generate time slots
            const startTime = new Date(`2000-01-01 ${daySchedule.start_time}`);
            const endTime = new Date(`2000-01-01 ${daySchedule.end_time}`);
            const interval = 30; // 30 minutes interval

            while (startTime < endTime) {
                const timeStr = startTime.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: true 
                });
                const timeValue = startTime.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit',
                    hour12: false 
                });

                // Check if slot is booked
                const isBooked = bookedSlots[selectedDate] && 
                               bookedSlots[selectedDate].includes(timeValue);

                const button = document.createElement('button');
                button.type = 'button';
                button.className = `px-4 py-2 rounded-lg text-center ${
                    isBooked 
                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
                    : 'bg-blue-100 text-blue-600 hover:bg-blue-200'
                }`;
                button.textContent = timeStr;
                button.disabled = isBooked;

                if (!isBooked) {
                    button.onclick = function() {
                        // Remove selected class from all buttons
                        document.querySelectorAll('#time_slots button').forEach(btn => {
                            btn.classList.remove('bg-blue-600', 'text-white');
                            btn.classList.add('bg-blue-100', 'text-blue-600');
                        });
                        // Add selected class to clicked button
                        this.classList.remove('bg-blue-100', 'text-blue-600');
                        this.classList.add('bg-blue-600', 'text-white');
                        // Set hidden input value
                        document.querySelector('input[name="appointment_time"]').value = timeValue;
                    };
                }

                timeSlotsContainer.appendChild(button);
                startTime.setMinutes(startTime.getMinutes() + interval);
            }

            // Add hidden input for selected time
            if (!document.querySelector('input[name="appointment_time"]')) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'appointment_time';
                hiddenInput.required = true;
                timeSlotsContainer.parentNode.appendChild(hiddenInput);
            }
        });

        function validateForm() {
            const fullName = document.querySelector('input[name="full_name"]').value;
            const email = document.querySelector('input[name="email"]').value;
            const phoneNumber = document.querySelector('input[name="phone_number"]').value;
            const gender = document.querySelector('select[name="gender"]').value;
            const dateOfBirth = document.querySelector('input[name="date_of_birth"]').value;
            const appointmentDate = document.querySelector('select[name="appointment_date"]').value;
            const appointmentTime = document.querySelector('input[name="appointment_time"]:checked');
            const consultationType = document.querySelector('select[name="consultation_type"]').value;
            const reasonForVisit = document.querySelector('textarea[name="reason_for_visit"]').value;

            // Validate full name
            if (fullName.length < 2 || fullName.length > 50) {
                alert('Full name must be between 2 and 50 characters');
                return false;
            }

            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                alert('Please enter a valid email address');
                return false;
            }

            // Validate phone number
            const phoneRegex = /^[0-9]{10,15}$/;
            if (!phoneRegex.test(phoneNumber)) {
                alert('Phone number must be between 10 and 15 digits');
                return false;
            }

            // Validate date of birth
            const dob = new Date(dateOfBirth);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            if (age < 0 || age > 120) {
                alert('Invalid date of birth');
                return false;
            }

            // Validate appointment date
            const appDate = new Date(appointmentDate);
            const currentDate = new Date();
            currentDate.setHours(0, 0, 0, 0);
            if (appDate < currentDate) {
                alert('Appointment date cannot be in the past');
                return false;
            }

            // Validate reason for visit
            if (reasonForVisit.length < 10 || reasonForVisit.length > 500) {
                alert('Reason for visit must be between 10 and 500 characters');
                return false;
            }

            return true;
        }
    </script>
</body>
</html> 