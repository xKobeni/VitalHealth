<?php
require_once '../config/database.php';
require_once 'includes/session.php';
require_once '../config/functions.php';

// Check if the doctor is logged in
if (!isset($_SESSION['userid'])) {
    header("Location: ../index.php");
    exit;
}

// Get doctor data
$doctor_id = getDoctorId($conn, $_SESSION['userid']);
$doctor_query = $conn->prepare("SELECT * FROM doctors WHERE doctor_id = ?");
$doctor_query->bind_param("i", $doctor_id);
$doctor_query->execute();
$doctor_result = $doctor_query->get_result();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $department = trim($_POST['department']);
    $contact_number = trim($_POST['contact_number']);
    $license = trim($_POST['license_number']);

    if (empty($full_name) || empty($department) || empty($contact_number) || empty($license)) {
        $error = "All fields are required.";
    } else {
        // Update the doctor's profile
        $update_stmt = $conn->prepare("UPDATE doctors SET full_name = ?, department = ?, contact_number = ?, license_number = ? WHERE doctor_id = ?");
        $update_stmt->bind_param("ssssi", $full_name, $department, $contact_number, $license, $doctor_id);

        if ($update_stmt->execute()) {
            $success = "Your profile has been updated successfully.";
            header("Location: wait_approval.php");
        } else {
            $error = "There was an issue updating your profile.";
        }
    }
}

// Get the doctor's current profile data for pre-filling
$doctor_data = $doctor_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Profile - VitalHealth</title>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

    <main class="p-8">
        <div class="max-w-full">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Create Your Profile</h1>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                    <strong>Error:</strong> <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="bg-green-100 text-green-700 p-4 rounded mb-6">
                    <strong>Success:</strong> <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow p-6">
                <form method="POST">
                    <div class="mb-4">
                        <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="full_name" id="full_name" placeholder="Enter Full Name (e.g John Doe)" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($doctor_data['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <input type="text" name="department" id="department" placeholder="Enter Department (e.g Cardiology)" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($doctor_data['department'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="contact_number" class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" placeholder="Enter Contact Number" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($doctor_data['contact_number'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4">
                        <label for="license_number" class="block text-sm font-medium text-gray-700 mb-2">PRC License Number</label>
                        <input type="text" name="license_number" id="license_number" placeholder="Enter License Number (e.g MD-1234567)" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            value="<?= htmlspecialchars($doctor_data['license_number'] ?? '') ?>" required>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Update Profile</button>
                </form>
            </div>
        </div>
    </main>
</body>

</html>