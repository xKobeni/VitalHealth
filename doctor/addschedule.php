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

// Add debugging
error_log("Session userid: " . $_SESSION['userid']);
error_log("Doctor ID: " . $doctor_id);
error_log("Doctor Name: " . $name);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $day_of_week = $_POST['day_of_week'];
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

    // Check if schedule already exists for this day
    $check_sql = "SELECT * FROM doctor_schedule WHERE doctor_id = ? AND day_of_week = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $doctor_id, $day_of_week);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        header("Location: doctorschedule.php?error=schedule_exists");
        exit;
    }

    // Insert new schedule
    $sql = "INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $doctor_id, $day_of_week, $start_time, $end_time);

    if ($stmt->execute()) {
        header("Location: doctorschedule.php?success=added");
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

// Get pre-selected day from URL if available
$selected_day = isset($_GET['day']) ? $_GET['day'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5">
        <h1 class="font-semibold text-3xl mb-6">Add Schedule</h1>

        <div class="bg-white rounded-lg shadow-lg p-6 max-w-md">
            <form method="POST" action="addschedule.php" class="space-y-4">
                <div>
                    <label for="day_of_week" class="block text-sm font-medium text-gray-700">Day of Week</label>
                    <select name="day_of_week" id="day_of_week" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Select a day</option>
                        <?php foreach ($days as $day_value => $day_name): ?>
                            <option value="<?php echo $day_value; ?>" <?php echo $selected_day === $day_value ? 'selected' : ''; ?>>
                                <?php echo $day_name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="start_time" class="block text-sm font-medium text-gray-700">Start Time</label>
                    <input type="time" id="start_time" name="start_time" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div>
                    <label for="end_time" class="block text-sm font-medium text-gray-700">End Time</label>
                    <input type="time" id="end_time" name="end_time" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="flex gap-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Add Schedule
                    </button>
                    <a href="doctorschedule.php" class="flex-1 bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 text-center">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add client-side validation for time inputs
        document.querySelector('form').addEventListener('submit', function(e) {
            const startTime = document.getElementById('start_time').value;
            const endTime = document.getElementById('end_time').value;
            
            if (startTime >= endTime) {
                e.preventDefault();
                alert('End time must be after start time');
            }
        });
    </script>
</body>
</html> 