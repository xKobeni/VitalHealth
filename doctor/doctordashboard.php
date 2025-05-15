<?php
include '../config/database.php';
include '../config/functions.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

$doctor_id = getDoctorId($conn, $_SESSION['userid']);
$name = getDoctorName($conn, $_SESSION['userid']);

// Get today's date for filtering
$today = date('Y-m-d');

// Get all appointments (both regular and guest) for today and future dates
$sql = "SELECT 
            'regular' as appointment_type,
            a.appointment_id,
            a.patient_id,
            p.full_name,
            p.date_of_birth,
            p.gender,
            p.contact_number,
            p.address,
            a.doctor_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            NULL as consultation_type,
            NULL as email
        FROM appointments a
        INNER JOIN patients p ON p.patient_id = a.patient_id
        WHERE a.appointment_date >= CURRENT_DATE() 
        AND a.doctor_id = ? 
        AND a.status IN ('scheduled', 'confirmed')
        
        UNION ALL
        
        SELECT 
            'guest' as appointment_type,
            ga.guest_appointment_id as appointment_id,
            NULL as patient_id,
            ga.full_name,
            NULL as date_of_birth,
            NULL as gender,
            ga.phone_number as contact_number,
            NULL as address,
            ga.doctor_id,
            ga.appointment_date,
            ga.appointment_time,
            ga.status,
            ga.consultation_type,
            ga.email
        FROM guest_appointments ga
        WHERE ga.appointment_date >= CURRENT_DATE() 
        AND ga.doctor_id = ? 
        AND ga.status IN ('pending', 'scheduled')
        
        ORDER BY appointment_date ASC, appointment_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $doctor_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}
$hasAppointments = !empty($appointments);

// Get today's appointments count
$todayAppointments = array_filter($appointments, function($apt) use ($today) {
    return $apt['appointment_date'] === $today && 
           ($apt['status'] === 'scheduled' || $apt['status'] === 'confirmed');
});
$todayCount = count($todayAppointments);

// Get upcoming appointments (excluding today)
$upcomingAppointments = array_filter($appointments, function($apt) use ($today) {
    return $apt['appointment_date'] > $today && 
           ($apt['status'] === 'scheduled' || $apt['status'] === 'confirmed');
});
$upcomingCount = count($upcomingAppointments);

// Get pending requests count (both regular and guest)
$pending_sql = "SELECT 
    (SELECT COUNT(*) 
     FROM appointments 
     WHERE doctor_id = ? AND status = 'pending') +
    (SELECT COUNT(*) 
     FROM guest_appointments 
     WHERE doctor_id = ? AND status = 'pending') as total_pending";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("ii", $doctor_id, $doctor_id);
$pending_stmt->execute();
$pending_count = $pending_stmt->get_result()->fetch_assoc()['total_pending'];

// Get doctor's schedule for today
$current_day = date('l'); // Gets current day name (e.g., Monday, Tuesday, etc.)
$schedule_sql = "SELECT 
    TIME_FORMAT(start_time, '%h:%i %p') as start_time,
    TIME_FORMAT(end_time, '%h:%i %p') as end_time
FROM doctor_schedule 
WHERE doctor_id = ? 
AND day_of_week = ?
AND is_available = 1
ORDER BY start_time ASC
LIMIT 1";

$schedule_stmt = $conn->prepare($schedule_sql);
$schedule_stmt->bind_param("is", $doctor_id, $current_day);
$schedule_stmt->execute();
$schedule_result = $schedule_stmt->get_result();
$schedule = $schedule_result->fetch_assoc();

// Format schedule display
$schedule_display = "No schedule set for today";
if ($schedule) {
    $schedule_display = "Your schedule: " . $schedule['start_time'] . " to " . $schedule['end_time'];
}

// Get total patients count
$patients_sql = "SELECT COUNT(DISTINCT patient_id) as total_patients 
                FROM appointments 
                WHERE doctor_id = ?";
$patients_stmt = $conn->prepare($patients_sql);
$patients_stmt->bind_param("i", $doctor_id);
$patients_stmt->execute();
$total_patients = $patients_stmt->get_result()->fetch_assoc()['total_patients'];

// Get completed appointments count
$completed_sql = "SELECT COUNT(*) as completed_count 
                 FROM appointments 
                 WHERE doctor_id = ? AND status = 'completed'";
$completed_stmt = $conn->prepare($completed_sql);
$completed_stmt->bind_param("i", $doctor_id);
$completed_stmt->execute();
$completed_count = $completed_stmt->get_result()->fetch_assoc()['completed_count'];

// Get cancelled appointments count
$cancelled_sql = "SELECT COUNT(*) as cancelled_count 
                 FROM appointments 
                 WHERE doctor_id = ? AND status = 'cancelled'";
$cancelled_stmt = $conn->prepare($cancelled_sql);
$cancelled_stmt->bind_param("i", $doctor_id);
$cancelled_stmt->execute();
$cancelled_count = $cancelled_stmt->get_result()->fetch_assoc()['cancelled_count'];

// Get pending requests (both regular and guest)
$pending_requests = [];
$pending_sql = "SELECT 'regular' as appointment_type, a.appointment_id, a.patient_id, p.full_name, p.contact_number, a.appointment_date, a.appointment_time, NULL as email
FROM appointments a
INNER JOIN patients p ON p.patient_id = a.patient_id
WHERE a.doctor_id = ? AND a.status = 'pending'
UNION ALL
SELECT 'guest' as appointment_type, ga.guest_appointment_id as appointment_id, NULL as patient_id, ga.full_name, ga.phone_number as contact_number, ga.appointment_date, ga.appointment_time, ga.email
FROM guest_appointments ga
WHERE ga.doctor_id = ? AND ga.status = 'pending'
ORDER BY appointment_date ASC, appointment_time ASC";
$pending_stmt = $conn->prepare($pending_sql);
$pending_stmt->bind_param("ii", $doctor_id, $doctor_id);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
while ($row = $pending_result->fetch_assoc()) {
    $pending_requests[] = $row;
}
$hasPendingRequests = !empty($pending_requests);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <div class="flex justify-between items-center mb-6 mt-3">
            <h1 class="font-semibold text-3xl">Doctor Dashboard</h1>
            <div class="text-gray-600">
                <i class="fas fa-calendar-alt mr-2"></i>
                <?php echo date('F j, Y'); ?>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex items-center">
                <div class="flex-1">
                    <h2 class="text-2xl font-semibold text-gray-800">Welcome, Dr. <?php echo htmlspecialchars($name); ?></h2>
                    <p class="text-gray-600 mt-1">Here's your schedule for <?php echo date('l, F j, Y'); ?> and upcoming appointments.</p>
                </div>
                <div class="text-right">
                    <a href="appointments.php" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-calendar-check mr-2"></i>
                        View All Appointments
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-users text-purple-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="font-semibold text-lg text-gray-700">Total Patients</h2>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_patients; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="font-semibold text-lg text-gray-700">Completed</h2>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $completed_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-full">
                        <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="font-semibold text-lg text-gray-700">Cancelled</h2>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $cancelled_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i class="fas fa-user-clock text-yellow-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h2 class="font-semibold text-lg text-gray-700">Pending Requests</h2>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $pending_count; ?></p>
                        <p class="text-sm text-gray-500">Awaiting approval</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            <div class="w-1/3 space-y-6">
                <!-- Stats Cards -->
                <div class="border border-neutral-300 bg-white rounded-md shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-calendar-check text-blue-500 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="font-semibold text-lg text-gray-700">Today's Appointments</h2>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $todayCount; ?></p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i>
                        <?php echo $schedule_display; ?>
                    </div>
                </div>

                <div class="border border-neutral-300 bg-white rounded-md shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-calendar-day text-green-500 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="font-semibold text-lg text-gray-700">Upcoming Appointments</h2>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $upcomingCount; ?></p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-info-circle mr-1"></i>
                        Scheduled for future dates
                    </div>
                </div>

                <div class="border border-neutral-300 bg-white rounded-md shadow-lg p-6">
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-yellow-100 rounded-full">
                            <i class="fas fa-user-clock text-yellow-500 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <h2 class="font-semibold text-lg text-gray-700">Pending Requests</h2>
                            <p class="text-3xl font-bold text-gray-800"><?php echo $pending_count; ?></p>
                            <p class="text-sm text-gray-500">Awaiting approval</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-2/3">
                <?php if ($hasAppointments): ?>
                    <div class="space-y-6">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                                <div class="flex items-start justify-between mb-4">
                                    <div>
                                        <h3 class="text-xl font-semibold text-gray-800">
                                            <?php echo htmlspecialchars($appointment['full_name']); ?>
                                            <?php if ($appointment['appointment_type'] === 'guest'): ?>
                                                <span class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Guest</span>
                                            <?php endif; ?>
                                        </h3>
                                        <div class="flex items-center text-gray-600 mt-1">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                                            <i class="fas fa-clock ml-4 mr-2"></i>
                                            <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                        </div>
                                        <?php if ($appointment['consultation_type']): ?>
                                            <p class="text-sm text-gray-500 mt-1">
                                                <i class="fas fa-stethoscope mr-2"></i>
                                                <?php echo ucfirst($appointment['consultation_type']); ?> Consultation
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($appointment['appointment_date'] === $today): ?>
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                            Today
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <?php if ($appointment['appointment_type'] === 'regular'): ?>
                                        <div>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-birthday-cake mr-2"></i>
                                                <?php echo htmlspecialchars($appointment['date_of_birth']); ?>
                                            </p>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-venus-mars mr-2"></i>
                                                <?php echo htmlspecialchars($appointment['gender']); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            <i class="fas fa-phone mr-2"></i>
                                            <?php echo htmlspecialchars($appointment['contact_number']); ?>
                                        </p>
                                        <?php if ($appointment['email']): ?>
                                            <p class="text-sm text-gray-600">
                                                <i class="fas fa-envelope mr-2"></i>
                                                <?php echo htmlspecialchars($appointment['email']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    <?php if ($appointment['appointment_type'] === 'regular'): ?>
                                        <form action="medical-history.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointmentid" value="<?php echo $appointment['patient_id']; ?>">
                                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                                <i class="fas fa-file-medical-alt mr-2"></i> Medical History
                                            </button>
                                        </form>
                                        <form action="finalassessment.php" method="GET" class="flex-1">
                                            <input type="hidden" name="patientid" value="<?php echo $appointment['patient_id']; ?>">
                                            <input type="hidden" name="appointmentid" value="<?php echo $appointment['appointment_id']; ?>">
                                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center justify-center">
                                                <i class="fas fa-stethoscope mr-2"></i> Final Assessment
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="process_guest_approval.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                                <i class="fas fa-check mr-2"></i> Approve
                                            </button>
                                        </form>
                                        <form action="process_guest_approval.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $appointment['appointment_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center justify-center">
                                                <i class="fas fa-times mr-2"></i> Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($hasPendingRequests): ?>
                    <div class="bg-white rounded-lg shadow-lg p-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Pending Requests</h2>
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="mb-6 border-b pb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($request['full_name']); ?></span>
                                        <?php if ($request['appointment_type'] === 'guest'): ?>
                                            <span class="ml-2 px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">Guest</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-gray-600">
                                        <i class="fas fa-calendar-alt mr-1"></i> <?php echo date('F j, Y', strtotime($request['appointment_date'])); ?>
                                        <i class="fas fa-clock ml-3 mr-1"></i> <?php echo date('h:i A', strtotime($request['appointment_time'])); ?>
                                    </div>
                                </div>
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-phone mr-2"></i> <?php echo htmlspecialchars($request['contact_number']); ?>
                                    <?php if ($request['email']): ?>
                                        <span class="ml-4"><i class="fas fa-envelope mr-2"></i><?php echo htmlspecialchars($request['email']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex gap-3">
                                    <?php if ($request['appointment_type'] === 'regular'): ?>
                                        <form action="appointments.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $request['appointment_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                                <i class="fas fa-check mr-2"></i> Approve
                                            </button>
                                        </form>
                                        <form action="appointments.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $request['appointment_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center justify-center">
                                                <i class="fas fa-times mr-2"></i> Reject
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="process_guest_approval.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $request['appointment_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                                <i class="fas fa-check mr-2"></i> Approve
                                            </button>
                                        </form>
                                        <form action="process_guest_approval.php" method="POST" class="flex-1">
                                            <input type="hidden" name="appointment_id" value="<?php echo $request['appointment_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition flex items-center justify-center">
                                                <i class="fas fa-times mr-2"></i> Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                        <i class="fas fa-calendar-times text-6xl text-gray-400 mb-4"></i>
                        <p class="text-xl font-semibold text-gray-600 mb-2">No scheduled appointments</p>
                        <p class="text-gray-500">You don't have any appointments scheduled for today or upcoming dates.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>