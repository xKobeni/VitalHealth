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

// Get schedule ID from URL
if (!isset($_GET['id'])) {
    header("Location: doctorschedule.php");
    exit;
}

$schedule_id = $_GET['id'];

// Get schedule details
$sql = "SELECT * FROM doctor_schedule WHERE schedule_id = ? AND doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $schedule_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$schedule = $result->fetch_assoc();

if (!$schedule) {
    header("Location: doctorschedule.php?error=not_found");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Validate time format
    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $start_time) || 
        !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $end_time)) {
        header("Location: doctorschedule.php?error=invalid_time");
        exit;
    }

    // Check if end time is after start time
    if (strtotime($end_time) <= strtotime($start_time)) {
        header("Location: doctorschedule.php?error=invalid_time_range");
        exit;
    }

    // Check for overlapping schedules on the same day
    $check_sql = "SELECT * FROM doctor_schedule 
                  WHERE doctor_id = ? 
                  AND day_of_week = ? 
                  AND schedule_id != ? 
                  AND ((start_time <= ? AND end_time > ?) 
                       OR (start_time < ? AND end_time >= ?) 
                       OR (start_time >= ? AND end_time <= ?))";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("issssssss", 
        $doctor_id, 
        $schedule['day_of_week'], 
        $schedule_id,
        $end_time, $start_time,
        $end_time, $start_time,
        $start_time, $end_time
    );
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        header("Location: doctorschedule.php?error=overlapping");
        exit;
    }

    // Update schedule
    $sql = "UPDATE doctor_schedule SET start_time = ?, end_time = ? WHERE schedule_id = ? AND doctor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $start_time, $end_time, $schedule_id, $doctor_id);

    if ($stmt->execute()) {
        header("Location: doctorschedule.php?success=updated");
    } else {
        header("Location: doctorschedule.php?error=failed");
    }
    exit;
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
    <title>Update Schedule - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <h1 class="font-semibold text-3xl mb-6 mt-3">Update Schedule</h1>

        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md">
            <h2 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($schedule['day_of_week']); ?></h2>
            
            <form method="POST" action="updateschedule.php?id=<?php echo $schedule_id; ?>" class="space-y-4">
                <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                
                <div class="flex items-center space-x-2 mb-4">
                    <input type="checkbox" id="is_available" name="is_available" 
                           <?php echo isset($schedule['is_available']) && $schedule['is_available'] ? 'checked' : ''; ?>
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_available" class="text-sm font-medium text-gray-700">
                        Available on this day
                    </label>
                </div>

                <div id="timeInputs" class="space-y-4 <?php echo isset($schedule['is_available']) && $schedule['is_available'] ? '' : 'hidden'; ?>">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                        <input type="time" id="start_time" name="start_time" 
                               value="<?php echo date('H:i', strtotime($schedule['start_time'])); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                    </div>

                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                        <input type="time" id="end_time" name="end_time" 
                               value="<?php echo date('H:i', strtotime($schedule['end_time'])); ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                               required>
                    </div>
                </div>

                <div class="flex gap-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Update Schedule
                    </button>
                    <a href="doctorschedule.php" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle time inputs based on availability
        document.getElementById('is_available').addEventListener('change', function(e) {
            const timeInputs = document.getElementById('timeInputs');
            const startTime = document.getElementById('start_time');
            const endTime = document.getElementById('end_time');
            
            if (e.target.checked) {
                timeInputs.classList.remove('hidden');
                startTime.required = true;
                endTime.required = true;
            } else {
                timeInputs.classList.add('hidden');
                startTime.required = false;
                endTime.required = false;
            }
        });

        // Add client-side validation for time inputs
        document.querySelector('form').addEventListener('submit', function(e) {
            const isAvailable = document.getElementById('is_available').checked;
            if (isAvailable) {
                const startTime = document.getElementById('start_time').value;
                const endTime = document.getElementById('end_time').value;
                
                if (startTime >= endTime) {
                    e.preventDefault();
                    alert('End time must be after start time');
                }
            }
        });
    </script>
</body>
</html>
