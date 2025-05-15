<?php
include '../config/database.php';
include '../config/functions.php';

function generateAvailableTimeSlots($start_time, $end_time, $duration_minutes, $booked_times = [])
{
    $slots = [];
    $current = strtotime($start_time);
    $end = strtotime($end_time);

    $now = time();

    while ($current + ($duration_minutes * 60) <= $end) {
        $time_str = date("H:i", $current);

        if (strtotime($time_str) < $now) {
            $current += $duration_minutes * 60;
            continue;
        }

        if (!in_array($time_str, $booked_times)) {
            $slots[] = $time_str;
        }

        $current += $duration_minutes * 60;
    }

    return $slots;
}

// Get all doctors with their schedules
$doctors_sql = "SELECT d.*, ds.day_of_week, ds.start_time, ds.end_time 
                FROM doctors d 
                LEFT JOIN doctor_schedule ds ON d.doctor_id = ds.doctor_id 
                ORDER BY d.full_name";
$doctors_result = $conn->query($doctors_sql);

$doctors = [];
while ($row = $doctors_result->fetch_assoc()) {
    if (!isset($doctors[$row['doctor_id']])) {
        $doctors[$row['doctor_id']] = [
            'doctor_id' => $row['doctor_id'],
            'full_name' => $row['full_name'],
            'department' => $row['department'],
            'schedule' => []
        ];
    }
    if ($row['day_of_week']) {
        $doctors[$row['doctor_id']]['schedule'][$row['day_of_week']] = [
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }
}

// Get booked appointments for today and tomorrow
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$booked_sql = "SELECT doctor_id, appointment_date, appointment_time 
               FROM appointments 
               WHERE appointment_date IN (?, ?) AND status != 'cancelled'
               UNION
               SELECT doctor_id, appointment_date, appointment_time 
               FROM guest_appointments 
               WHERE appointment_date IN (?, ?) AND status = 'pending'";
$stmt = $conn->prepare($booked_sql);
$stmt->bind_param("ssss", $today, $tomorrow, $today, $tomorrow);
$stmt->execute();
$booked_result = $stmt->get_result();

$booked_times = [];
while ($row = $booked_result->fetch_assoc()) {
    $booked_times[$row['doctor_id']][$row['appointment_date']][] = date('H:i', strtotime($row['appointment_time']));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment as Guest - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="text-xl font-bold text-blue-600">VitalHealth</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="guest_appointments.php" class="text-gray-600 hover:text-blue-600 flex items-center gap-2">
                        <i class="fas fa-calendar-check"></i>
                        View My Appointments
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto p-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Book Appointment as Guest</h1>

                <!-- Doctor List Section -->
                <div class="space-y-8">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="border rounded-lg p-6">
                            <div class="flex items-center gap-4 mb-6">
                                <i class="fas fa-user-md text-4xl text-blue-500"></i>
                                <div>
                                    <h2 class="text-xl font-semibold">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($doctor['department']); ?></p>
                                </div>
                            </div>

                            <!-- Available Time Slots -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Today's Slots -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Today's Available Slots</h3>
                                    <?php
                                    $today_weekday = date('l');
                                    $today_slots = [];
                                    if (isset($doctor['schedule'][$today_weekday])) {
                                        $schedule = $doctor['schedule'][$today_weekday];
                                        $booked = $booked_times[$doctor['doctor_id']][$today] ?? [];
                                        $today_slots = generateAvailableTimeSlots($schedule['start_time'], $schedule['end_time'], 45, $booked);
                                    }
                                    ?>
                                    <?php if (!empty($today_slots)): ?>
                                        <div class="grid grid-cols-3 gap-2">
                                            <?php foreach ($today_slots as $slot): ?>
                                                <form method="POST" action="process_guest_booking.php" class="contents">
                                                    <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctor_id']; ?>">
                                                    <input type="hidden" name="appointment_date" value="<?php echo $today; ?>">
                                                    <input type="hidden" name="appointment_time" value="<?php echo $slot; ?>">
                                                    <button type="submit" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-200 text-sm">
                                                        <?php echo date('h:i A', strtotime($slot)); ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-gray-500 text-sm">No available slots for today</p>
                                    <?php endif; ?>
                                </div>

                                <!-- Tomorrow's Slots -->
                                <div>
                                    <h3 class="text-lg font-semibold mb-4">Tomorrow's Available Slots</h3>
                                    <?php
                                    $tomorrow_weekday = date('l', strtotime('+1 day'));
                                    $tomorrow_slots = [];
                                    if (isset($doctor['schedule'][$tomorrow_weekday])) {
                                        $schedule = $doctor['schedule'][$tomorrow_weekday];
                                        $booked = $booked_times[$doctor['doctor_id']][$tomorrow] ?? [];
                                        $tomorrow_slots = generateAvailableTimeSlots($schedule['start_time'], $schedule['end_time'], 45, $booked);
                                    }
                                    ?>
                                    <?php if (!empty($tomorrow_slots)): ?>
                                        <div class="grid grid-cols-3 gap-2">
                                            <?php foreach ($tomorrow_slots as $slot): ?>
                                                <form method="POST" action="process_guest_booking.php" class="contents">
                                                    <input type="hidden" name="doctor_id" value="<?php echo $doctor['doctor_id']; ?>">
                                                    <input type="hidden" name="appointment_date" value="<?php echo $tomorrow; ?>">
                                                    <input type="hidden" name="appointment_time" value="<?php echo $slot; ?>">
                                                    <button type="submit" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-lg hover:bg-blue-200 text-sm">
                                                        <?php echo date('h:i A', strtotime($slot)); ?>
                                                    </button>
                                                </form>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-gray-500 text-sm">No available slots for tomorrow</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 