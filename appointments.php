<?php
include 'config/database.php';
include 'config/functions.php';
session_start();

$name = getPatientName($conn, $_SESSION['userid']);
$id = getPatientId($conn, $_SESSION['userid']);

$sql = "SELECT appointment_id, patient_id, doctors.full_name, appointment_date, appointment_time, status FROM appointments
        INNER JOIN doctors
        ON appointments.doctor_id = doctors.doctor_id
        WHERE patient_id = $id";

$result = $conn->query($sql);
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

        <h2 class="text-3xl font-bold mb-8">Your Appointments</h2>
        <div class="flex items-center gap-x-3">
            <input type="text" placeholder="Search by Doctor Name" class="bg-white p-2 rounded-md border border-gray-400 shadow-lg w-80">
            <select class="border border-neutral-300 p-2 rounded-md bg-white shadow-lg w-50">
                <option value="" disabled selected hidden>Enter Status</option>
                <option value="">Scheduled</option>
                <option value="">Completed</option>
                <option value="">Cancelled</option>
            </select>
            <button type="submit" name="submit" class="bg-green-400 p-2 text-white rounded-full w-30">Search</button>
        </div>


        <?php
        if ($result->num_rows > 0) {

            echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 max-h-[500px] overflow-y-auto p-3">';
            while ($row = $result->fetch_assoc()) {
                echo '
            <form method="POST" action="cancelappointment.php" class="border border-neutral-300 bg-white mt-6 rounded-md shadow-lg w-90 text-center p-3">
        <i class="fas fa-calendar-alt text-5xl"></i>
        <h3 class="font-semibold text-2xl">' . htmlspecialchars($row["appointment_time"]) . '</h3>
        <p>' . htmlspecialchars($row["appointment_date"]) . '</p>
        <p>Doctor: Dr. ' . htmlspecialchars($row["full_name"]) . '</p>
        <p class="font-semibold">' . htmlspecialchars($row["status"]) . '</p>
        <input type="hidden" name="appointmentid" value="' . htmlspecialchars($row["appointment_id"]) . '">
        <button type="submit" name="submit" class="text-center text-white border indigo-600 bg-red-400 p-1 w-50 rounded-full mt-5">Cancel Appointment</button>
        </form> ';
            }
            echo '</div';
        } else {
            echo "<p class='text-center text-gray-500'>No Appointments Found.</p>";
        }

        $conn->close();
        ?>

    </div>
</body>

</html>