<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: doctors.php');
    exit();
}

$doctor_id = (int)$_GET['id'];

// Get doctor info with email
$sql = "SELECT d.*, u.email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE d.doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Doctor not found.";
    exit();
}

$doctor = $result->fetch_assoc();

// Handle approval or rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $update = $conn->prepare("UPDATE doctors SET is_approved = 1, is_active = 1 WHERE doctor_id = ?");
        $update->bind_param("i", $doctor_id);
        $update->execute();
        header("Location: doctors.php");
        exit();
    }

    if (isset($_POST['reject'])) {
        $update = $conn->prepare("UPDATE doctors SET is_approved = 0 WHERE doctor_id = ?");
        $update->bind_param("i", $doctor_id);
        $update->execute();
        header("Location: doctors.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Doctor Profile - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="max-w-full bg-white shadow-md rounded-lg p-6">
            <a href="doctors.php" class="text-blue-600 hover:underline mb-4 inline-block">← Back to Doctor Management</a>

            <h1 class="text-2xl font-bold text-gray-800 mb-4">Doctor Profile</h1>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p><strong class="text-gray-700">Full Name:</strong> <?= htmlspecialchars($doctor['full_name']) ?></p>
                    <p><strong class="text-gray-700">Email:</strong> <?= htmlspecialchars($doctor['email']) ?></p>
                    <p><strong class="text-gray-700">Department                     :</strong> <?= htmlspecialchars($doctor['department']) ?></p>
                </div>
                <div>
                    <p><strong class="text-gray-700">Contact Number:</strong> <?= htmlspecialchars($doctor['contact_number']) ?></p>
                    <p><strong class="text-gray-700">License Number:</strong> <?= htmlspecialchars($doctor['license_number']) ?></p>
                    <p><strong class="text-gray-700">Status:</strong>
                        <?php if ($doctor['is_approved'] == 1): ?>
                            <span class="text-green-600 font-semibold">Approved</span>
                        <?php else: ?>
                            <span class="text-yellow-600 font-semibold">Pending</span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <?php if ($doctor['is_approved'] == 0): ?>
                <form method="POST" class="mt-6 flex gap-4">
                    <button type="submit" name="approve" class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                        Approve
                    </button>
                    <button type="submit" name="reject" class="bg-red-600 text-white px-6 py-2 rounded hover:bg-red-700">
                        Reject
                    </button>
                </form>
            <?php else: ?>
                <div class="mt-6 text-sm text-gray-500">
                    This doctor has already been approved. No further action is required.
                </div>
            <?php endif; ?>
        </div>
    </main>
</body>

</html>