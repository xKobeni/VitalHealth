<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

$doctor_id = getDoctorId($conn, $_SESSION['userid']);
$name = getDoctorName($conn, $_SESSION['userid']);

// Get success/error messages
$success = isset($_GET['success']) ? $_GET['success'] : null;
$error = isset($_GET['error']) ? $_GET['error'] : null;

// Get doctor's schedule
$sql = "SELECT * FROM doctor_schedule WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$schedules = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>Doctor Schedule - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <div class="flex justify-between items-center mb-6">
            <h1 class="font-semibold text-3xl">My Schedule</h1>
            <a href="addschedule.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 flex items-center gap-2">
                <i class="fas fa-plus"></i> Add Schedule
            </a>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Schedule updated successfully!</span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Failed to update schedule. Please try again.</span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($days as $day_value => $day_name): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo $day_name; ?></h2>
                    <?php
                    $day_schedules = array_filter($schedules, function($schedule) use ($day_value) {
                        return $schedule['day_of_week'] === $day_value;
                    });
                    
                    if (empty($day_schedules)): ?>
                        <p class="text-gray-500">No schedule set</p>
                        <a href="addschedule.php?day=<?php echo urlencode($day_value); ?>" class="text-blue-500 hover:text-blue-700 mt-2 inline-block">
                            <i class="fas fa-plus"></i> Add Schedule
                        </a>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($day_schedules as $schedule): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-gray-800 font-medium">
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                     date('h:i A', strtotime($schedule['end_time'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="updateschedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                           class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="deleteschedule.php?id=<?php echo $schedule['schedule_id']; ?>" 
                                           class="text-red-600 hover:text-red-800"
                                           onclick="return confirm('Are you sure you want to delete this schedule?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>