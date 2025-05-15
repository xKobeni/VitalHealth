<?php
include '../config/database.php';

$appointments = [];
$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        $sql = "SELECT ga.*, d.full_name as doctor_name, d.department 
                FROM guest_appointments ga 
                JOIN doctors d ON ga.doctor_id = d.doctor_id 
                WHERE ga.email = ? 
                ORDER BY ga.appointment_date ASC, ga.appointment_time ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
    } else {
        $error = "Please enter a valid email address";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Guest Appointments - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="text-xl font-bold text-blue-600">VitalHealth</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="guest_booking.php" class="text-gray-600 hover:text-blue-600 flex items-center gap-2">
                        <i class="fas fa-calendar-plus"></i>
                        Book New Appointment
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto p-6">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">View Your Guest Appointments</h1>

                <form method="POST" class="mb-8">
                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Enter Your Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter the email you used for booking">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                                View Appointments
                            </button>
                        </div>
                    </div>
                </form>

                <?php if ($error): ?>
                    <div class="bg-red-50 text-red-600 p-4 rounded-lg mb-6">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($appointments)): ?>
                    <div class="space-y-4">
                        <?php foreach ($appointments as $appointment): ?>
                            <div class="border rounded-lg p-6 <?php echo $appointment['status'] === 'pending' ? 'bg-yellow-50' : ($appointment['status'] === 'approved' ? 'bg-green-50' : 'bg-red-50'); ?>">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-lg font-semibold">Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($appointment['department']); ?></p>
                                    </div>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium
                                        <?php echo $appointment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($appointment['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Date</p>
                                        <p class="font-medium"><?php echo date('F j, Y', strtotime($appointment['appointment_date'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Time</p>
                                        <p class="font-medium"><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Consultation Type</p>
                                        <p class="font-medium"><?php echo ucfirst($appointment['consultation_type']); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Reason for Visit</p>
                                        <p class="font-medium"><?php echo htmlspecialchars($appointment['reason_for_visit']); ?></p>
                                    </div>
                                </div>

                                <?php if ($appointment['status'] === 'pending'): ?>
                                    <div class="bg-blue-50 p-4 rounded-lg">
                                        <p class="text-blue-700 text-sm">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            Your appointment is pending approval. You will receive an email once the doctor reviews your request.
                                        </p>
                                    </div>
                                <?php elseif ($appointment['status'] === 'approved'): ?>
                                    <div class="bg-green-50 p-4 rounded-lg">
                                        <p class="text-green-700 text-sm">
                                            <i class="fas fa-check-circle mr-2"></i>
                                            Your appointment has been approved! Check your email for login credentials.
                                        </p>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-red-50 p-4 rounded-lg">
                                        <p class="text-red-700 text-sm">
                                            <i class="fas fa-times-circle mr-2"></i>
                                            This appointment request was declined. Please try booking another appointment.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-4xl text-gray-400 mb-4"></i>
                        <p class="text-gray-600">No appointments found for this email address.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 