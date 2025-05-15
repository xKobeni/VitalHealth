<?php
include '../config/database.php';
include '../config/functions.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

$id = getDoctorId($conn, $_SESSION['userid']);
$name = getDoctorName($conn, $_SESSION['userid']);

// Get all medical histories for patients seen by this doctor
$sql = "SELECT mh.*, p.full_name as patient_name, p.date_of_birth, p.gender 
        FROM medical_history mh 
        JOIN patients p ON mh.patient_id = p.patient_id 
        WHERE mh.recorded_by = ? 
        ORDER BY mh.record_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$histories = [];
while ($row = $result->fetch_assoc()) {
    $histories[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical History - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <h1 class="font-semibold text-3xl mb-6">Medical History</h1>

        <?php if (empty($histories)): ?>
            <div class="bg-white rounded-lg shadow-lg p-6 text-center text-gray-500">
                <p>No medical history records found.</p>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-lg shadow-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Appointment Date</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Assessment</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($histories as $history): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap">
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($history['patient_name']); ?></div>
                                    <div class="text-xs text-gray-500">
                                        <?php 
                                        $dob = new DateTime($history['date_of_birth']);
                                        $now = new DateTime();
                                        $age = $now->diff($dob)->y;
                                        echo $age . ' years old, ' . htmlspecialchars($history['gender']);
                                        ?>
                                    </div>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                    <?php echo date('F j, Y', strtotime($history['record_date'])); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-gray-700 max-w-xs truncate">
                                    <?php echo nl2br(htmlspecialchars(mb_strimwidth($history['assessment'], 0, 60, '...'))); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-right">
                                    <a href="view_medical_history.php?id=<?php echo $history['history_id']; ?>" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                        <i class="fas fa-eye mr-2"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 