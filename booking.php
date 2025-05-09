<?php
include 'config/database.php';
include 'config/functions.php';

session_start();

$name = getPatientName($conn, $_SESSION['userid']);

function generateAvailableTimeSlots($start_time, $end_time, $duration_minutes, $booked_times = [])
{
    $slots = [];
    $current = strtotime($start_time);
    $end = strtotime($end_time);

    $now = time(); // Current timestamp

    while ($current + ($duration_minutes * 60) <= $end) {
        $time_str = date("H:i", $current);

        // Skip slots that are in the past
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

$available_slots = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['doctorid'], $_GET['date'])) {
    $doctor_id = $_GET['doctorid'];
    $appointment_date = $_GET['date'];
    $weekday = date('l', strtotime($appointment_date)); // e.g., Monday

    // 1. Get doctor's schedule for the selected weekday
    $schedule_sql = "SELECT start_time, end_time FROM doctor_schedule 
                     WHERE doctor_id = ? AND day_of_week = ?";
    $stmt = $conn->prepare($schedule_sql);
    $stmt->bind_param("is", $doctor_id, $weekday);
    $stmt->execute();
    $schedule_result = $stmt->get_result();

    if ($row = $schedule_result->fetch_assoc()) {
        $start_time = $row['start_time'];
        $end_time = $row['end_time'];

        // 2. Get already booked times for that doctor on that date
        $booked_sql = "SELECT appointment_time FROM appointments 
                       WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
        $stmt2 = $conn->prepare($booked_sql);
        $stmt2->bind_param("is", $doctor_id, $appointment_date);
        $stmt2->execute();
        $booked_result = $stmt2->get_result();

        $booked_times = [];
        while ($b = $booked_result->fetch_assoc()) {
            $booked_times[] = date('H:i', strtotime($b['appointment_time']));
        }

        // 3. Generate available time slots
        $available_slots = generateAvailableTimeSlots($start_time, $end_time, 45, $booked_times);
    } else {
        echo "No schedule found for this doctor on $weekday.";
    }

    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <div class="bg-green-400 w-64 min-h-screen">
        <div class="flex items-center text-white">
            <i class="fas fa-user-circle text-3xl p-3"></i>
            <p class="mb-4 text-base"><?= htmlspecialchars($name ?? 'Name')  ?></p>
        </div>
        <hr class="text-neutral-300">
        <ul class="mt-3 text-white text-lg p-1">
            <li class="mb-3">
                <a href="patientdashboard.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
                    <i class="fas fa-home"></i>Home
                </a>
            </li>
            <li class="mb-3">
                <a href="appointments.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
                    <i class="fas fa-calendar-alt"></i>Appointments
                </a>
            </li>
            <li class="mb-3">
                <a href="mediacalhistory.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
                    <i class="fas fa-book-medical"></i>Medical History
                </a>
            </li>
            <li class="mb-3">
                <a href="logout.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </li>
        </ul>
    </div>
    <div class="flex-1 p-5">

        <div id="drawer" class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-lg transform translate-x-0 transition-transform duration-300 z-50">
            <div class="p-6 flex justify-between items-center border-b">
                <h2 class="text-lg font-semibold">Choose Available Time Slots</h2>
                <a href="patientdashboard.php" class="text-gray-500 hover:text-black text-3xl">
                    <button class="text-gray-500 hover:text-black text-3xl">&times;</button>
                </a>
            </div>
            <div class="p-6">
                <?php if (!empty($available_slots)): ?>
                    <form method="POST" action="booked.php">
                        <label class="block mb-2 font-semibold">Available Time Slots:</label>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <?php foreach ($available_slots as $slot): ?>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="appointment_time" value="<?= $slot ?>" required>
                                    <span><?= htmlspecialchars($slot) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="doctor_id" value="<?= htmlspecialchars($doctor_id) ?>">
                        <input type="hidden" name="appointment_date" value="<?= htmlspecialchars($appointment_date) ?>">
                        <button type="submit" name="submit" class="bg-green-500 text-white px-4 py-2 rounded w-full">Book Now</button>
                    </form>
                <?php else: ?>
                    <p class="text-red-500">No available slots for the selected date.</p>
                <?php endif; ?>

            </div>
        </div>

    </div>
</body>

</html>