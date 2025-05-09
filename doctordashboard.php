<?php
include 'config/database.php';
include 'config/functions.php';


session_start();

$id = getDoctorId($conn, $_SESSION['userid']);

$sql = "SELECT appointment_id, appointments.patient_id, patients.full_name, patients.date_of_birth, patients.gender,
patients.contact_number, patients.address, doctor_id, appointment_date, appointment_time, status FROM appointments
INNER JOIN patients
ON patients.patient_id = appointments.patient_id
WHERE appointment_date >=CURRENT_DATE() AND doctor_id = $id AND status = 'scheduled'
ORDER BY appointment_date ASC, appointment_time ASC;"; //change doctor_id
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$hasAppointment = $row !== null;
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
        <h1 class="font-semibold text-3xl mb-6 mt-3">Today's Schedule</h1>

        <div class="flex gap-6">
            <div class="w-1/3 space-y-6">
                <div class="border border-neutral-300 bg-white rounded-md shadow-lg text-center p-6 flex flex-col items-center">
                    <i class="fas fa-calendar-check text-blue-500 text-4xl mb-3"></i>
                    <h2 class="font-semibold text-lg text-gray-700 mb-2">Total Appointments</h2>
                    <p class="text-3xl font-bold text-gray-800">0</p>
                </div>

                <!-- Today's Appointments -->
                <div class="border border-neutral-300 bg-white rounded-md shadow-lg text-center p-6 flex flex-col items-center">
                    <i class="fas fa-calendar-day text-green-500 text-4xl mb-3"></i>
                    <h2 class="font-semibold text-lg text-gray-700 mb-2">Appointments Today</h2>
                    <p class="text-3xl font-bold text-gray-800">0</p>
                </div>

                <!-- Today's Schedule -->
                <div class="border border-neutral-300 bg-white rounded-md shadow-lg text-center p-6 flex flex-col items-center">
                    <i class="fas fa-clock text-yellow-500 text-4xl mb-3"></i>
                    <h2 class="font-semibold text-lg text-gray-700 mb-2">Your Schedule Today</h2>
                    <p class="text-2xl font-medium text-gray-800">9:00 to 5:00</p>
                </div>
            </div>

            <div class="w-2/3">
                <?php if ($hasAppointment): ?>
                    <!-- Appointment card -->
                    <div class="border border-neutral-300 bg-white rounded-md shadow-lg p-6 max-w-full">
                        <h2 class="font-semibold text-3xl text-center text-gray-800 mb-6">Upcoming Patient For Check-Up</h2>

                        <!-- Profile Section -->
                        <div class="flex justify-center items-center flex-col mb-6">
                            <i class="fas fa-user-circle text-7xl text-gray-500 mb-4"></i>
                            <p class="font-semibold text-2xl text-gray-800 mb-2"><?= htmlspecialchars($row["full_name"])  ?></p>
                            <p class="font-semibold text-base text-gray-500 mb-2"><?= htmlspecialchars($row["appointment_date"]) ?> * <?= htmlspecialchars($row["appointment_time"])  ?></p>
                        </div>

                        <!-- Patient Details -->
                        <div class="space-y-3">
                            <p class="flex items-center text-lg text-gray-700">
                                <i class="fas fa-birthday-cake text-green-400 mr-2"></i>
                                <?= htmlspecialchars($row["date_of_birth"])  ?>
                            </p>
                            <p class="flex items-center text-lg text-gray-700">
                                <i class="fas fa-venus-mars text-green-400 mr-2"></i>
                                <?= htmlspecialchars($row["gender"])  ?>
                            </p>
                            <p class="flex items-center text-lg text-gray-700">
                                <i class="fas fa-phone-alt text-green-400 mr-2"></i>
                                <?= htmlspecialchars($row["contact_number"])  ?>
                            </p>
                            <p class="flex items-center text-lg text-gray-700">
                                <i class="fas fa-map-marker-alt text-green-400 mr-2"></i>
                                <?= htmlspecialchars($row["address"])  ?>
                            </p>
                        </div>

                        <!-- Buttons Section -->
                        <div class="flex justify-center gap-6 mt-6">
                            <form action="medical-history.php" method="POST">
                                <input type="hidden" name="appointmentid" value="<?= htmlspecialchars($row["patient_id"])  ?>">
                                <button type="submit" class="text-white bg-green-500 px-6 py-2 rounded-full flex items-center gap-2 hover:bg-green-600 transition">
                                    <i class="fas fa-file-medical-alt"></i> Medical History
                                </button>
                            </form>
                            <form action="finalassessment.php" method="GET">
                                <input type="hidden" name="patientid" value="<?= htmlspecialchars($row["patient_id"])  ?>">
                                <input type="hidden" name="appointmentid" value="<?= htmlspecialchars($row["appointment_id"])  ?>">
                                <button type="submit" class="text-white bg-blue-500 px-6 py-2 rounded-full flex items-center gap-2 hover:bg-blue-600 transition">
                                    <i class="fas fa-stethoscope"></i> Final Assessment
                                </button>
                            </form>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- No appointments message -->
                    <div class="text-center text-gray-500 mt-10">
                        <i class="fas fa-calendar-times text-6xl mb-4"></i>
                        <p class="text-xl font-semibold">No scheduled appointments</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

</body>

</html>