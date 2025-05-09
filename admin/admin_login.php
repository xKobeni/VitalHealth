<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Check if password matches directly (for plain text passwords)
        if ($password === $user['password']) {
            // Set all required session variables
            $_SESSION['admin_id'] = $user['user_id'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            $_SESSION['admin_image'] = $user['profile_image'] ?? 'https://randomuser.me/api/portraits/women/44.jpg';
            $_SESSION['role'] = 'admin';
            
            // Update last login
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();
            
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "No admin user found with this email";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <title>Admin Login</title>
    <style>
        .toast {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateX(150%);
            transition: transform 0.3s ease-in-out;
            z-index: 50;
        }
        .toast.show {
            transform: translateX(0);
        }
        .toast.error {
            border-left: 4px solid #ef4444;
        }
    </style>
</head>

<body>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-xl">
            <i class="fas fa-user-shield text-blue-500 text-4xl block text-center mb-2"></i>
            <h1 class="text-2xl font-bold text-center mb-2">Admin Login</h1>
            <p class="text-center text-neutral-600 mb-2">Welcome! Please enter your Admin account</p>
            
            <?php if (isset($error)): ?>
                <div id="errorToast" class="toast error">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 mb-2">Admin Email</label>
                    <input type="email" name="email" required 
                           class="w-full px-3 py-2 border rounded">
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required 
                               class="w-full px-3 py-2 border rounded">
                        <button type="button" onclick="togglePassword()" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" 
                        class="w-full bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700">
                    Login
                </button>
            </form>
            <p class="text-center mt-4">
                <a href="../index.php" class="text-blue-600 hover:text-blue-800">Back to Main Login</a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Toast notification functionality
        document.addEventListener('DOMContentLoaded', function() {
            const toast = document.getElementById('errorToast');
            if (toast) {
                // Show toast
                setTimeout(() => {
                    toast.classList.add('show');
                }, 100);

                // Hide toast after 5 seconds
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 5000);
            }
        });
    </script>
</body>

</html>
