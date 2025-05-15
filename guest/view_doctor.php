<?php
include '../config/database.php';
include '../config/functions.php';

// Get doctor ID from URL
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$doctor_id = $_GET['id'];

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

// Days of the week
$days = [
    'Monday' => 'Monday',
    'Tuesday' => 'Tuesday',
    'Wednesday' => 'Wednesday',
    'Thursday' => 'Thursday',
    'Friday' => 'Friday',
    'Saturday' => 'Saturday',
    'Sunday' => 'Sunday'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Information - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Back Button -->
            <a href="index.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Doctors List
            </a>

            <!-- Doctor Information Card -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        <i class="fas fa-user-md text-6xl text-blue-500"></i>
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h1>
                        <p class="text-lg text-gray-600 mb-4"><?php echo htmlspecialchars($doctor['department']); ?></p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600">
                                    <i class="fas fa-phone-alt text-blue-500 mr-2"></i>
                                    <?php echo htmlspecialchars($doctor['contact_number']); ?>
                                </p>
                                <p class="text-gray-600">
                                    <i class="fas fa-envelope text-blue-500 mr-2"></i>
                                    <?php echo htmlspecialchars($doctor['email']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schedule Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Available Schedule</h2>
                
                <?php if (empty($schedules)): ?>
                    <p class="text-gray-500 text-center py-4">No schedule available</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($days as $day_value => $day_name): ?>
                            <div class="border rounded-lg p-4">
                                <h3 class="font-semibold text-gray-700 mb-2"><?php echo $day_name; ?></h3>
                                <?php
                                $day_schedules = array_filter($schedules, function($schedule) use ($day_value) {
                                    return $schedule['day_of_week'] === $day_value;
                                });
                                
                                if (empty($day_schedules)): ?>
                                    <p class="text-gray-500 text-sm">Not available</p>
                                <?php else: ?>
                                    <div class="space-y-2">
                                        <?php foreach ($day_schedules as $schedule): ?>
                                            <p class="text-gray-600">
                                                <?php 
                                                echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                     date('h:i A', strtotime($schedule['end_time'])); 
                                                ?>
                                            </p>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Book Appointment Button -->
                <?php if (!empty($schedules)): ?>
                <div class="mt-6 text-center">
                    <a href="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>" 
                       class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        Book an Appointment
                    </a>
                </div>
                <?php else: ?>
                <div class="mt-6 text-center">
                    <p class="text-gray-600">Appointments are currently not available as there are no schedules set.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 