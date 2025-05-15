<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid medical history record.');
}

$history_id = intval($_GET['id']);

$sql = "SELECT mh.*, p.full_name as patient_name, p.date_of_birth, p.gender 
        FROM medical_history mh 
        JOIN patients p ON mh.patient_id = p.patient_id 
        WHERE mh.history_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $history_id);
$stmt->execute();
$result = $stmt->get_result();
$history = $result->fetch_assoc();

if (!$history) {
    die('Medical history record not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Medical History - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>
    <div class="flex-1 p-5 ml-64">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="font-semibold text-3xl">Medical History Details</h1>
            <a href="medicalhistory.php" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back
            </a>
        </div>
        <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl mx-auto">
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($history['patient_name']); ?></h2>
                <div class="text-gray-600 text-sm mb-1">
                    <?php 
                    $dob = new DateTime($history['date_of_birth']);
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;
                    echo $age . ' years old, ' . htmlspecialchars($history['gender']);
                    ?>
                </div>
                <div class="text-gray-500 text-sm">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    <?php echo date('F j, Y', strtotime($history['record_date'])); ?>
                </div>
            </div>
            <div class="mb-6">
                <h3 class="font-medium text-gray-700 mb-1">Assessment</h3>
                <p class="text-gray-800 bg-gray-50 rounded p-3"><?php echo nl2br(htmlspecialchars($history['assessment'])); ?></p>
            </div>
            <div class="mb-6">
                <h3 class="font-medium text-gray-700 mb-1">Medication</h3>
                <p class="text-gray-800 bg-gray-50 rounded p-3"><?php echo nl2br(htmlspecialchars($history['medication'])); ?></p>
            </div>
            <?php if (!empty($history['notes'])): ?>
            <div class="mb-6">
                <h3 class="font-medium text-gray-700 mb-1">Notes</h3>
                <p class="text-gray-800 bg-gray-50 rounded p-3"><?php echo nl2br(htmlspecialchars($history['notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 