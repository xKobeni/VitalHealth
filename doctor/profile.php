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

// Get doctor's information
$sql = "SELECT d.*, u.email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE d.doctor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// Handle form submission
$success = false;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING);
    $department = filter_input(INPUT_POST, 'department', FILTER_SANITIZE_STRING);
    $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update doctor information
        $update_sql = "UPDATE doctors SET 
                      full_name = ?, 
                      department = ?, 
                      contact_number = ? 
                      WHERE doctor_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("sssi", $full_name, $department, $contact_number, $id);
        $update_stmt->execute();

        // If password change is requested
        if (!empty($current_password) && !empty($new_password)) {
            // Verify current password
            $verify_sql = "SELECT password FROM users WHERE user_id = ?";
            $verify_stmt = $conn->prepare($verify_sql);
            $verify_stmt->bind_param("i", $_SESSION['userid']);
            $verify_stmt->execute();
            $verify_result = $verify_stmt->get_result();
            $user = $verify_result->fetch_assoc();

            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $password_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                    $password_stmt = $conn->prepare($password_sql);
                    $password_stmt->bind_param("si", $hashed_password, $_SESSION['userid']);
                    $password_stmt->execute();
                } else {
                    throw new Exception("New passwords do not match");
                }
            } else {
                throw new Exception("Current password is incorrect");
            }
        }

        $conn->commit();
        $success = true;
        
        // Refresh doctor data
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <?php include 'doctor_sidebar.php'; ?>

    <div class="flex-1 p-5 ml-64">
        <h1 class="font-semibold text-3xl mb-6 mt-3">Profile Settings</h1>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Profile updated successfully!</span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-lg p-6">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Personal Information</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                            <input type="text" value="<?php echo htmlspecialchars($doctor['full_name']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" value="<?php echo htmlspecialchars($doctor['department']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                            <input type="tel" value="<?php echo htmlspecialchars($doctor['contact_number']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>
                    </div>

                    <!-- Account Settings -->
                    <div class="space-y-4">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Account Settings</h2>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($doctor['email']); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50" disabled>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 