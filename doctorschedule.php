<?php
include 'config/database.php';
include 'config/functions.php';


session_start();

$id = getDoctorId($conn, $_SESSION['userid']);

$sql = "SELECT * FROM doctor_schedule WHERE doctor_id = $id";
$result = $conn->query($sql);

$schedule = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedule[$row['day_of_week']] = [
            'start' => $row['start_time'],
            'end' => $row['end_time']
        ];
    }
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
                <a href="doctordashboard.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
                    <i class="fas fa-home"></i>Dashboard
                </a>
            </li>
            <li class="mb-3">
                <a href="doctorschedule.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
                    <i class="fas fa-calendar-alt"></i>Schedules
                </a>
            </li>
            <li class="mb-3">
                <a href="medicalhistory.php" class="flex items-center gap-x-5 hover:bg-sky-200 hover:cursor-pointer px-3 py-2 rounded text-white">
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

        <div class="max-w-full bg-white p-6 rounded shadow">
            <h2 class="text-2xl font-semibold mb-4">Manage Weekly Schedule</h2>
            <form method="POST" action="updateschedule.php">
                <table class="w-full text-left border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-2">Day</th>
                            <th class="p-2">Start Time</th>
                            <th class="p-2">End Time</th>
                            <th class="p-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                        foreach ($days as $day):
                            $start = $schedule[$day]['start'] ?? '';
                            $end = $schedule[$day]['end'] ?? '';
                        ?>
                            <tr class="border-t">
                                <td class="p-2 font-medium"><?= $day ?></td>
                                <td class="p-2">
                                    <input type="time" step="1800" name="schedule[<?= $day ?>][start]" class="border rounded px-2 py-1 w-full" value="<?= htmlspecialchars($start) ?>">
                                </td>
                                <td class="p-2">
                                    <input type="time" step="1800" name="schedule[<?= $day ?>][end]" class="border rounded px-2 py-1 w-full" value="<?= htmlspecialchars($end) ?>">
                                </td>
                                <td class="p-2">
                                    <label>
                                        <input type="checkbox" name="schedule[<?= $day ?>][off]" value="1"
                                            <?= (empty($schedule[$day]['start']) && empty($schedule[$day]['end'])) ? 'checked' : '' ?>>
                                        Set as Day Off
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
                <div class="text-right mt-4">
                    <button type="submit" name="submit" class="bg-green-500 text-white px-4 py-2 rounded">Save Schedule</button>
                </div>
            </form>
        </div>

    </div>

</body>

</html>