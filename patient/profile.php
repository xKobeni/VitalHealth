<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

$patient_name = getPatientName($conn, $_SESSION['userid']);
$patient_id = getPatientId($conn, $_SESSION['userid']);

// Get patient information
$sql = "SELECT p.*, u.email 
        FROM patients p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['userid']);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();

$message = '';
$error = '';

// Remove form submission handling since we're making it read-only
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <!-- Sidebar -->
    <div class="bg-green-400 w-64 min-h-screen">
        <div class="flex items-center text-white p-4">
            <i class="fas fa-user-circle text-3xl"></i>
            <p class="ml-3 text-lg"><?php echo htmlspecialchars($patient_name); ?></p>
        </div>
        <hr class="border-white">
        <ul class="mt-4">
            <li class="mb-2">
                <a href="patientdashboard.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="appointments.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-calendar-alt w-6"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="medical-history.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-file-medical w-6"></i>
                    <span>Medical History</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="profile.php" class="flex items-center text-white p-4 bg-green-500">
                    <i class="fas fa-user-cog w-6"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="logout.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Profile</h1>
            <a href="patientdashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-6">
            <!-- Profile Information -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">Profile Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($patient['full_name']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($patient['email']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <input type="tel" value="<?php echo htmlspecialchars($patient['contact_number']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                        <input type="date" value="<?php echo htmlspecialchars($patient['date_of_birth']); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50" readonly>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender</label>
                        <input type="text" value="<?php echo ucfirst(htmlspecialchars($patient['gender'])); ?>" 
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-50" readonly>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript functionality here if needed
    </script>
</body>
</html> 