<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get all doctors
$doctors_query = "SELECT doctor_id, full_name, department FROM doctors ORDER BY full_name";
$doctors_result = $conn->query($doctors_query);

$selected_doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;

// Days of the week
$days = [
    0 => 'Sunday',
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
];

// Get doctor schedule if doctor_id is provided
if ($selected_doctor_id) {
    $schedule_query = "SELECT * FROM doctor_schedule WHERE doctor_id = ? ORDER BY day_of_week, start_time";
    $stmt = $conn->prepare($schedule_query);
    $stmt->bind_param("i", $selected_doctor_id);
    $stmt->execute();
    $schedule_result = $stmt->get_result();
    $doctor_schedules = [];
    while ($row = $schedule_result->fetch_assoc()) {
        $doctor_schedules[$row['day_of_week']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Schedules - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    <main class="ml-64 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Doctor Schedules</h1>
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="flex gap-4 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Doctor</label>
                        <select name="doctor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a Doctor</option>
                            <?php $doctors_result->data_seek(0); while ($doctor = $doctors_result->fetch_assoc()): ?>
                                <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo $selected_doctor_id == $doctor['doctor_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($doctor['full_name'] . ' (' . $doctor['department'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">View Schedule</button>
                </form>
            </div>
            <?php if ($selected_doctor_id): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Weekly Schedule</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($days as $day_num => $day_name): ?>
                            <div>
                                <h3 class="font-semibold text-blue-700 mb-2"><?php echo $day_name; ?></h3>
                                <?php if (!empty($doctor_schedules[$day_num])): ?>
                                    <ul class="space-y-2">
                                        <?php foreach ($doctor_schedules[$day_num] as $slot): ?>
                                            <li class="flex items-center gap-3 p-3 bg-gray-50 rounded shadow-sm">
                                                <span class="material-icons text-green-500">event_available</span>
                                                <span class="text-gray-700 font-medium">
                                                    <?php echo date('h:i A', strtotime($slot['start_time'])); ?> - <?php echo date('h:i A', strtotime($slot['end_time'])); ?>
                                                </span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-gray-400 text-sm">No slots</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html> 