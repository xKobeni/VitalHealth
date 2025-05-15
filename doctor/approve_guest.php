<?php
require_once '../config/database.php';
require_once '../config/functions.php';
require_once 'includes/session.php';

checkDoctorSession();

$doctor_id = getDoctorId($conn, $_SESSION['userid']);

// Get pending guest appointments
$sql = "SELECT ga.*, d.full_name as doctor_name 
        FROM guest_appointments ga 
        JOIN doctors d ON ga.doctor_id = d.doctor_id 
        WHERE ga.doctor_id = ? AND ga.status = 'scheduled'
        ORDER BY ga.appointment_date ASC, ga.appointment_time ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Guest Appointments - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5">
        <h1 class="font-semibold text-3xl mb-6 mt-3">Pending Guest Appointments</h1>

        <?php if (empty($appointments)): ?>
            <div class="bg-white rounded-lg shadow p-6 text-center text-gray-500">
                <p>No pending guest appointments found.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($appointments as $appointment): ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($appointment['full_name']); ?>
                                </h3>
                                <p class="text-sm text-gray-600">
                                    <?php echo htmlspecialchars($appointment['email']); ?>
                                </p>
                            </div>
                            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full">
                                Pending
                            </span>
                        </div>

                        <div class="space-y-2 mb-4">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-clock mr-2"></i>
                                <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-phone mr-2"></i>
                                <?php echo htmlspecialchars($appointment['phone_number']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-stethoscope mr-2"></i>
                                <?php echo ucfirst($appointment['consultation_type']); ?> Consultation
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <form method="POST" action="process_guest_approval.php" class="flex-1">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['guest_appointment_id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="w-full bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                    Approve
                                </button>
                            </form>
                            <form method="POST" action="process_guest_approval.php" class="flex-1">
                                <input type="hidden" name="appointment_id" value="<?php echo $appointment['guest_appointment_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 