<?php
include 'config/database.php';
include 'config/functions.php';


session_start();

$doctorid = getDoctorId($conn, $_SESSION['userid']);
$patientid = $_GET['patientid'];
$appointmentid = $_GET['appointmentid'];
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
        <div class="max-w-full mx-auto bg-white p-8 rounded shadow-md">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-notes-medical text-blue-500 text-2xl"></i> Final Assessment Form
            </h2>

            <form action="saveassessment.php" method="POST" class="space-y-6">
                <!-- Hidden Inputs -->
                <input type="hidden" name="patient_id" value="<?= htmlspecialchars($patientid) ?>">
                <input type="hidden" name="recorded_by" value="<?= htmlspecialchars($doctorid) ?>">
                <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointmentid) ?>">


                <!-- Condition -->
                <div>
                    <label for="condition" class="block text-sm font-medium text-gray-700 mb-1">Patient Condition</label>
                    <textarea id="assessment" name="assessment" rows="3" placeholder="Describe the condition..."
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <!-- Medication -->
                <div>
                    <label for="medication" class="block text-sm font-medium text-gray-700 mb-1">Prescribed Medication</label>
                    <textarea id="medication" name="medication" rows="2" placeholder="List medications or treatments..."
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <!-- Notes -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="3" placeholder="Any additional remarks or observations..."
                        class="w-full border border-gray-300 rounded px-4 py-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-right">
                    <button type="submit" name="submit" class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 transition">
                        <i class="fas fa-save mr-2"></i> Save Assessment
                    </button>
                </div>
            </form>
        </div>



    </div>

</body>

</html>