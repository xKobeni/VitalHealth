<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

$patient_name = getPatientName($conn, $_SESSION['userid']);
$patient_id = getPatientId($conn, $_SESSION['userid']);

$doctor = null;
$doctor_id = isset($_GET['doctorid']) ? intval($_GET['doctorid']) : 0;
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

if ($doctor_id) {
    $sql = "SELECT d.full_name, d.department, d.contact_number, u.email FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $doctor = $result->fetch_assoc();

    // Get doctor's schedule
    $schedule_sql = "SELECT * FROM doctor_schedule WHERE doctor_id = ?";
    $schedule_stmt = $conn->prepare($schedule_sql);
    $schedule_stmt->bind_param("i", $doctor_id);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    $doctor_schedules = [];
    while ($row = $schedule_result->fetch_assoc()) {
        $doctor_schedules[$row['day_of_week']][] = $row;
    }

    // Get booked slots for the selected date
    $sql = "SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status = 'confirmed'";
        $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $doctor_id, $date);
        $stmt->execute();
    $result = $stmt->get_result();
    $booked_slots = [];
    while ($row = $result->fetch_assoc()) {
        $booked_slots[] = $row['appointment_time'];
    }

    // Determine the day of week for the selected date
    $day_of_week = date('l', strtotime($date));

    // Build available slots for this day based on schedule
    $available_slots = [];
    $all_slots = [];
    if (!empty($doctor_schedules[$day_of_week])) {
        foreach ($doctor_schedules[$day_of_week] as $sched) {
            $start = strtotime($sched['start_time']);
            $end = strtotime($sched['end_time']);
            while ($start < $end) {
                $slot = date('H:i:00', $start);
                $all_slots[] = $slot;
                if (!in_array($slot, $booked_slots)) {
                    $available_slots[] = $slot;
                }
                $start += 30 * 60; // 30-minute intervals
            }
        }
    }
}

// Generate next 7 days for the date selector
$days = [];
for ($i = 0; $i < 7; $i++) {
    $d = date('Y-m-d', strtotime("+$i day"));
    $days[] = $d;
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

<body class="bg-sky-100 flex">
    <!-- Sidebar -->
    <div class="bg-green-400 w-64 min-h-screen">
        <div class="flex items-center text-white p-4">
            <i class="fas fa-user-circle text-3xl"></i>
            <p class="ml-3 text-lg"><?php echo htmlspecialchars($patient_name); ?></p>
        </div>
        <hr class="border-white">
        <ul class="mt-4">
            <li class="mb-2">
                <a href="patientdashboard.php" class="flex items-center text-white p-4 hover:bg-green-500">
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
    <div class="flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Book Appointment</h1>
            <a href="booking.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Doctors
            </a>
        </div>

        <?php if ($doctor): ?>
        <div class="bg-white rounded-lg shadow p-6 mb-8 max-w-2xl mx-auto max-h-[80vh] overflow-y-auto">
            <div class="flex flex-col md:flex-row md:items-center md:gap-8 mb-6">
                <div class="flex-shrink-0 flex flex-col items-center mb-4 md:mb-0">
                    <div class="bg-blue-100 rounded-full p-4 mb-2"><i class="fas fa-user-doctor text-3xl text-blue-400"></i></div>
                </div>
                <div>
                    <h2 class="text-2xl font-semibold mb-1">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                    <p class="text-blue-600 mb-1 font-medium"><?php echo htmlspecialchars($doctor['department']); ?></p>
                    <p class="text-gray-700 mb-1"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($doctor['contact_number']); ?></p>
                    <p class="text-gray-700 mb-1"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($doctor['email']); ?></p>
                </div>
            </div>
            <div class="mb-6">
                <h3 class="text-lg font-semibold mb-2">Select a Date:</h3>
                <div class="flex gap-2 overflow-x-auto pb-2">
                    <?php foreach ($days as $d): ?>
                        <a href="?doctorid=<?php echo $doctor_id; ?>&date=<?php echo $d; ?>"
                           class="px-4 py-2 rounded-lg border <?php echo $d === $date ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-blue-100'; ?> font-semibold transition-colors">
                            <?php echo date('D, M j', strtotime($d)); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h3 class="text-lg font-semibold mb-2">Available Time Slots for <?php echo date('F d, Y', strtotime($date)); ?>:</h3>
                <?php if (!empty($doctor_schedules[$day_of_week]) && !empty($all_slots)): ?>
                <form method="post" action="process_booking.php" class="space-y-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <?php foreach ($all_slots as $slot): ?>
                            <?php $is_booked = in_array($slot, $booked_slots); ?>
                            <label class="block">
                                <input type="radio" name="appointment_time" value="<?php echo $slot; ?>" class="peer hidden" <?php echo $is_booked ? 'disabled' : ''; ?> required>
                                <span class="block w-full py-2 rounded text-center font-semibold transition-colors cursor-pointer
                                    <?php echo $is_booked ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'bg-green-500 text-white hover:bg-green-600'; ?>
                                    peer-checked:ring-2 peer-checked:ring-blue-500">
                                    <?php echo date('h:i A', strtotime($slot)); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="doctor_id" value="<?php echo htmlspecialchars($doctor_id); ?>">
                    <input type="hidden" name="appointment_date" value="<?php echo htmlspecialchars($date); ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Visit</label>
                        <input type="text" name="reason" placeholder="Enter reason for visit..." required
                               class="w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Book Appointment
                    </button>
                </form>
                <div class="text-gray-500 text-sm mt-2">* Gray slots are already booked. Select a green slot and enter a reason to book.</div>
                <?php else: ?>
                    <div class="text-red-500">Doctor not available on this day.</div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                Invalid doctor selection.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
