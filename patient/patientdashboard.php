<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

$patient_name = getPatientName($conn, $_SESSION['userid']);
$patient_id = getPatientId($conn, $_SESSION['userid']);

// Get patient email
$email_sql = "SELECT u.email FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.user_id = ?";
$email_stmt = $conn->prepare($email_sql);
$email_stmt->bind_param("i", $_SESSION['userid']);
$email_stmt->execute();
$email_result = $email_stmt->get_result();
$patient_email = ($row = $email_result->fetch_assoc()) ? $row['email'] : '';

// Get upcoming appointments (registered patient)
$sql = "SELECT a.*, d.full_name as doctor_name, d.department 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.doctor_id 
        WHERE a.patient_id = ? 
        AND a.appointment_date >= CURDATE() 
        AND a.status IN ('scheduled', 'pending')
        ORDER BY a.appointment_date ASC, a.appointment_time ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get guest appointments by email
$guest_sql = "SELECT ga.*, d.full_name as doctor_name, d.department 
              FROM guest_appointments ga 
              JOIN doctors d ON ga.doctor_id = d.doctor_id 
              WHERE ga.email = ? 
              AND ga.appointment_date >= CURDATE() 
              AND ga.status IN ('pending', 'approved')
              ORDER BY ga.appointment_date ASC, ga.appointment_time ASC";
$guest_stmt = $conn->prepare($guest_sql);
$guest_stmt->bind_param("s", $patient_email);
$guest_stmt->execute();
$guest_result = $guest_stmt->get_result();
$guest_appointments = $guest_result->fetch_all(MYSQLI_ASSOC);

// Get appointment statistics
$stats_sql = "SELECT 
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'scheduled' THEN 1 END) as scheduled_count,
    COUNT(CASE WHEN status IN ('cancelled', 'rejected') THEN 1 END) as cancelled_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count
    FROM appointments 
    WHERE patient_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $patient_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get recent medical history
$history_sql = "SELECT mh.*, d.full_name as doctor_name 
                FROM medical_history mh 
                JOIN doctors d ON mh.recorded_by = d.doctor_id 
                WHERE mh.patient_id = ? 
                ORDER BY mh.record_date DESC LIMIT 3";
$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param("i", $patient_id);
$history_stmt->execute();
$recent_history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get upcoming doctor schedules
$schedule_sql = "SELECT ds.*, d.full_name as doctor_name, d.department 
                FROM doctor_schedule ds 
                JOIN doctors d ON ds.doctor_id = d.doctor_id 
                ORDER BY FIELD(ds.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
                         ds.start_time ASC 
                LIMIT 2";
$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->execute();
$upcoming_schedules = $schedule_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <!-- Sidebar -->
    <div class="bg-green-400 w-64 min-h-screen fixed top-0 left-0 overflow-y-auto">
        <div class="flex items-center text-white p-4">
            <i class="fas fa-user-circle text-3xl"></i>
            <p class="ml-3 text-lg"><?php echo htmlspecialchars($patient_name); ?></p>
        </div>
        <hr class="border-white">
        <ul class="mt-4">
            <li class="mb-2">
                <a href="patientdashboard.php" class="flex items-center text-white p-4 bg-green-500">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="appointments.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-calendar-alt w-6"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="medical-history.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-file-medical w-6"></i>
                    <span>Medical History</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="profile.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-user-cog w-6"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="logout.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8 ml-64">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Welcome, <?php echo htmlspecialchars($patient_name); ?>!</h1>
                <p class="text-gray-600 mt-1">Here's an overview of your healthcare journey</p>
                <p class="text-gray-500 mt-2">
                    <i class="far fa-calendar-alt mr-2"></i>
                    <?php 
                    $date = new DateTime('2025-05-16');
                    echo $date->format('l, F j, Y'); 
                    ?>
                </p>
            </div>
            <a href="booking.php" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>Book New Appointment
            </a>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending Appointments</p>
                        <h3 class="text-2xl font-bold text-yellow-600"><?php echo $stats['pending_count']; ?></h3>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-clock text-yellow-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Scheduled Appointments</p>
                        <h3 class="text-2xl font-bold text-green-600"><?php echo $stats['scheduled_count']; ?></h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Completed</p>
                        <h3 class="text-2xl font-bold text-blue-600"><?php echo $stats['completed_count']; ?></h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-clipboard-check text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Cancelled/Rejected</p>
                        <h3 class="text-2xl font-bold text-red-600"><?php echo $stats['cancelled_count']; ?></h3>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Upcoming Appointments</h2>
                    <a href="appointments.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php if (empty($appointments) && empty($guest_appointments)): ?>
                    <div class="text-center py-12">
                        <div class="bg-gray-50 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-calendar-times text-4xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500 text-lg mb-4">No upcoming appointments scheduled</p>
                        <a href="booking.php" class="inline-block bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            Book an Appointment
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-all bg-white">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-lg">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($appointment['department']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full font-medium">
                                        <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                    </span>
                                </div>
                                <div class="flex items-center text-gray-600 mb-4">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 text-sm rounded-full font-medium
                                        <?php
                                        switch ($appointment['status']) {
                                            case 'pending':
                                                echo 'bg-yellow-50 text-yellow-700';
                                                break;
                                            case 'scheduled':
                                                echo 'bg-green-50 text-green-700';
                                                break;
                                            case 'cancelled':
                                                echo 'bg-red-50 text-red-700';
                                                break;
                                            default:
                                                echo 'bg-gray-50 text-gray-700';
                                        }
                                        ?>">
                                        <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                    </span>
                                    <a href="appointments.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        View Details <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($guest_appointments as $gappointment): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-all bg-yellow-50">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <h3 class="font-semibold text-lg">Dr. <?php echo htmlspecialchars($gappointment['doctor_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($gappointment['department']); ?></p>
                                        <span class="text-xs text-yellow-700 bg-yellow-100 px-2 py-1 rounded-full">Guest Booking</span>
                                    </div>
                                    <span class="px-3 py-1 text-sm bg-yellow-100 text-yellow-700 rounded-full font-medium">
                                        <?php echo date('F j, Y', strtotime($gappointment['appointment_date'])); ?>
                                    </span>
                                </div>
                                <div class="flex items-center text-gray-600 mb-4">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span><?php echo date('h:i A', strtotime($gappointment['appointment_time'])); ?></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="px-3 py-1 text-sm rounded-full font-medium
                                        <?php
                                        switch ($gappointment['status']) {
                                            case 'pending':
                                                echo 'bg-yellow-50 text-yellow-700';
                                                break;
                                            case 'scheduled':
                                                echo 'bg-green-50 text-green-700';
                                                break;
                                            case 'cancelled':
                                                echo 'bg-red-50 text-red-700';
                                                break;
                                            default:
                                                echo 'bg-gray-50 text-gray-700';
                                        }
                                        ?>">
                                        <?php echo ucfirst(htmlspecialchars($gappointment['status'])); ?>
                                    </span>
                                    <span class="text-yellow-700 text-sm font-medium">Guest Booking</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Recent Medical History -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Recent Medical History</h2>
                    <a href="medical-history.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                        View All <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php if (empty($recent_history)): ?>
                    <div class="text-center py-8">
                        <div class="bg-gray-50 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-file-medical text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">No medical history available</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_history as $history): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-all">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-semibold">Dr. <?php echo htmlspecialchars($history['doctor_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo date('F j, Y', strtotime($history['record_date'])); ?></p>
                                    </div>
                                    <span class="px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full">
                                        <?php echo htmlspecialchars($history['assessment']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-600 text-sm line-clamp-2"><?php echo htmlspecialchars($history['medication']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upcoming Doctor Schedules -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Available Doctor Schedules</h2>
                    <a href="booking.php" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                        Book Now <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                <?php if (empty($upcoming_schedules)): ?>
                    <div class="text-center py-8">
                        <div class="bg-gray-50 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-calendar-alt text-3xl text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">No upcoming schedules available</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($upcoming_schedules as $schedule): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-all">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-semibold">Dr. <?php echo htmlspecialchars($schedule['doctor_name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($schedule['department']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 text-sm bg-green-50 text-green-700 rounded-full">
                                        <?php echo htmlspecialchars($schedule['day_of_week']); ?>
                                    </span>
                                </div>
                                <div class="flex items-center text-gray-600 text-sm">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span><?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - <?php echo date('h:i A', strtotime($schedule['end_time'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>