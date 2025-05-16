<?php
require_once 'includes/session.php';
require_once '../config/database.php';
include '../config/functions.php';
checkAdminSession();

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Generate a random password
    $temp_password = generateTemporaryPassword();
    $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    // Validation
    if (empty($email)) {
        $error = "All fields are required.";
    } else {
        // Check if email already exists
        $check_query = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_query->bind_param("s", $email);
        $check_query->execute();
        $check_result = $check_query->get_result();

        if ($check_result->num_rows > 0) {
            $error = "Email is already in use.";
        } else {
            // Insert into users
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'doctor')");
            $user_stmt->bind_param("ss", $email, $hashed_password);
            if ($user_stmt->execute()) {
                $user_id = $user_stmt->insert_id;

                // Insert into doctors
                $doctor_stmt = $conn->prepare("INSERT INTO doctors (user_id) VALUES (?)");
                $doctor_stmt->bind_param("i", $user_id);
                if ($doctor_stmt->execute()) {
                    $success = "Doctor added successfully. An email has been sent with the login details.";

                    $subject = "Your Doctor Account - VitalHealth";
                    $message = "Dear Doctor,\n\nYour account has been created successfully. \n\nEmail: $email\nPassword: $temp_password\n\nPlease log in, change your password and provide required information after login for approval. \n\nBest regards,\nVitalHealth Team";

                    sendEmail($email, $subject, $message);
                } else {
                    $error = "Failed to insert into doctors table.";
                }
            } else {
                $error = "Failed to insert into users table.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Doctor - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="max-w-full mx-auto bg-white p-6 rounded shadow">
            <a href="doctors.php" class="text-blue-600 hover:underline mb-4 inline-block">← Back to Doctor Management</a>
            <h2 class="text-2xl font-bold mb-4 text-gray-800">Add New Doctor</h2>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= $success ?></div>
            <?php elseif ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full border border-gray-300 rounded px-3 py-2">
                </div>

                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Add Doctor</button>
            </form>
        </div>
    </main>
</body>

</html>