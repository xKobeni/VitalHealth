<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

$success_message = '';
$error_message = '';

// Use the correct session variable for admin ID
$admin_id = $_SESSION['admin_id'];

// Get admin information
$admin_query = "SELECT a.*, u.email 
                FROM admins a 
                JOIN users u ON a.user_id = u.user_id 
                WHERE a.user_id = ?";
$stmt = $conn->prepare($admin_query);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name']);
        $contact_number = trim($_POST['contact_number']);
        $email = trim($_POST['email']);
        
        $conn->begin_transaction();
        try {
            $update_user = "UPDATE users SET email = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_user);
            $stmt->bind_param("si", $email, $admin_id);
            $stmt->execute();
            $update_admin = "UPDATE admins SET full_name = ?, contact_number = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_admin);
            $stmt->bind_param("ssi", $full_name, $contact_number, $admin_id);
            $stmt->execute();
            $conn->commit();
            $success_message = "Profile updated successfully!";
            $stmt = $conn->prepare($admin_query);
            $stmt->bind_param("i", $admin_id);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating profile. Please try again.";
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        $verify_query = "SELECT password FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if ($result && password_verify($current_password, $result['password'])) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_password = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_password);
                $stmt->bind_param("si", $hashed_password, $admin_id);
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password. Please try again.";
                }
            } else {
                $error_message = "New passwords do not match.";
            }
        } else {
            $error_message = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Account - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <main class="ml-64 p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Your Account</h1>
            <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>
            <?php if (!$admin): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Admin profile not found. Please contact support.</span>
            </div>
            <?php endif; ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Profile Information -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Profile Information</h2>
                    <form method="POST" action="">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name'] ?? ($_SESSION['admin_name'] ?? '')); ?>" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? ($_SESSION['admin_email'] ?? '')); ?>" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                                <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($admin['contact_number'] ?? ''); ?>" 
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <button type="submit" name="update_profile" 
                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    Update Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Change Password -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Change Password</h2>
                    <form method="POST" action="">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Current Password</label>
                                <input type="password" name="current_password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">New Password</label>
                                <input type="password" name="new_password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <input type="password" name="confirm_password" required
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <button type="submit" name="change_password" 
                                        class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    Change Password
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</body>
</html> 