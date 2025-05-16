<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit();
}

$patientid = $_GET['patientid'];
$appointmentid = $_GET['appointmentid'];
$is_guest = isset($_GET['is_guest']) && $_GET['is_guest'] === '1';

// Get patient information
if ($is_guest) {
    // Get guest appointment information
    $sql = "SELECT * FROM guest_appointments WHERE guest_appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentid);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    
    if ($appointment) {
        $patient = [
            'full_name' => $appointment['full_name'],
            'email' => $appointment['email'],
            'phone_number' => $appointment['phone_number']
        ];
        
        // Find the patient ID using the guest's email
        $find_patient_sql = "SELECT p.patient_id 
                            FROM patients p 
                            JOIN users u ON p.user_id = u.user_id 
                            WHERE u.email = ?";
        $find_patient_stmt = $conn->prepare($find_patient_sql);
        $find_patient_stmt->bind_param("s", $appointment['email']);
        $find_patient_stmt->execute();
        $patient_result = $find_patient_stmt->get_result();
        
        if ($patient_result->num_rows > 0) {
            $patient_data = $patient_result->fetch_assoc();
            // Get medical history for the found patient ID
            $sql = "SELECT * FROM medical_history WHERE patient_id = ? ORDER BY record_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $patient_data['patient_id']);
            $stmt->execute();
            $medical_history = $stmt->get_result();
        } else {
            $medical_history = [];
        }
    } else {
        header("Location: appointments.php?error=1");
        exit;
    }
} else {
    // Get regular patient information
    $sql = "SELECT p.*, u.email 
            FROM patients p 
            JOIN users u ON p.user_id = u.user_id 
            WHERE p.patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patientid);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();

    if (!$patient) {
        header("Location: appointments.php?error=1");
        exit;
    }

    // Get regular appointment information
    $sql = "SELECT * FROM appointments WHERE appointment_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $appointmentid);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    if (!$appointment) {
        header("Location: appointments.php?error=1");
        exit;
    }

    // Get medical history for regular patients
    $sql = "SELECT * FROM medical_history WHERE patient_id = ? ORDER BY record_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patientid);
    $stmt->execute();
    $medical_history = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Assessment - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-6">Final Assessment</h1>

                <?php if (isset($_GET['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <?php if ($_GET['error'] == '1'): ?>
                            <span class="block sm:inline">Failed to save assessment. Please try again.</span>
                        <?php elseif ($_GET['error'] == '2'): ?>
                            <span class="block sm:inline">Please fill in all required fields.</span>
                        <?php endif; ?>
                        <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Patient Information -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Patient Information</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600"><span class="font-medium">Name:</span> <?php echo htmlspecialchars($patient['full_name']); ?></p>
                            <?php if (!$is_guest): ?>
                                <p class="text-gray-600"><span class="font-medium">Date of Birth:</span> <?php echo htmlspecialchars($patient['date_of_birth']); ?></p>
                                <p class="text-gray-600"><span class="font-medium">Gender:</span> <?php echo htmlspecialchars($patient['gender']); ?></p>
                                <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($patient['email']); ?></p>
                            <?php else: ?>
                                <p class="text-gray-600"><span class="font-medium">Email:</span> <?php echo htmlspecialchars($patient['email']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-gray-600"><span class="font-medium">Contact:</span> <?php echo htmlspecialchars($is_guest ? $patient['phone_number'] : $patient['contact_number']); ?></p>
                            <?php if (!$is_guest): ?>
                                <p class="text-gray-600"><span class="font-medium">Address:</span> <?php echo htmlspecialchars($patient['address']); ?></p>
                                <p class="text-gray-600"><span class="font-medium">Age:</span> 
                                    <?php 
                                        $dob = new DateTime($patient['date_of_birth']);
                                        $now = new DateTime();
                                        $age = $now->diff($dob)->y;
                                        echo $age . ' years old';
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Assessment Form -->
                <form action="saveassessment.php" method="POST" class="space-y-6" onsubmit="return validateForm()">
                    <input type="hidden" name="patient_id" value="<?php echo htmlspecialchars($patientid); ?>">
                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointmentid); ?>">
                    <input type="hidden" name="is_guest" value="<?php echo $is_guest ? '1' : '0'; ?>">

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Assessment <span class="text-red-500">*</span></label>
                        <textarea name="assessment" id="assessment" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Medication <span class="text-red-500">*</span></label>
                        <textarea name="medication" id="medication" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-medium mb-2">Notes</label>
                        <textarea name="notes" id="notes" rows="3" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="doctordashboard.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</a>
                        <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Save Assessment</button>
                    </div>
                </form>

                <script>
                function validateForm() {
                    const assessment = document.getElementById('assessment').value.trim();
                    const medication = document.getElementById('medication').value.trim();
                    if (!assessment || !medication) {
                        alert('Please fill in all required fields (Assessment and Medication)');
                        return false;
                    }
                    return true;
                }
                </script>

                <!-- Medical History -->
                <div class="mt-8">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Medical History</h2>
                    <div class="space-y-4">
                        <?php if ($is_guest && empty($medical_history)): ?>
                            <p class="text-gray-600">No medical history available for this patient.</p>
                        <?php else: ?>
                            <?php while ($history = $medical_history->fetch_assoc()): ?>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <p class="text-gray-600"><span class="font-medium">Date:</span> <?php echo date('F j, Y', strtotime($history['record_date'])); ?></p>
                                    <p class="text-gray-600"><span class="font-medium">Assessment:</span> <?php echo htmlspecialchars($history['assessment']); ?></p>
                                    <p class="text-gray-600"><span class="font-medium">Medication:</span> <?php echo htmlspecialchars($history['medication']); ?></p>
                                    <?php if (!empty($history['notes'])): ?>
                                    <p class="text-gray-600"><span class="font-medium">Notes:</span> <?php echo htmlspecialchars($history['notes']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>