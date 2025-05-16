<?php
include 'config/database.php';
?>

<?php
if (isset($_POST['submit'])) {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Get user with email
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $logged_in = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $logged_in['password'])) {
            // For doctors, check if they are active
            if ($logged_in['role'] === 'doctor') {
                session_start();
                $_SESSION['userid'] = $logged_in['user_id'];
                header('Location: /Healthcare/doctor/doctordashboard.php');
                exit;
            } else {
                session_start();
                $_SESSION['userid'] = $logged_in['user_id'];
                
                // Redirect based on role
                if ($logged_in['role'] === 'patient') {
                    header('Location: /Healthcare/patient/patientdashboard.php');
                } else {
                    $_SESSION['email'] = $email;
                    header('Location: /Healthcare/dashboard.php');
                }
                exit;
            }
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
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
    <title>Login - VitalHealth</title>
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
        .toast.deactivated {
            border-left: 4px solid #f59e0b;
            background-color: #fffbeb;
        }
    </style>
</head>

<body>
    <div class="flex justify-center items-center h-screen bg-sky-100">
        <div class="w-130 p-6 shadow-lg bg-white rounded-md">
            <i class="fas fa-heartbeat text-green-500 text-4xl"></i>
            <h1 class="text-3xl text-center font-semibold mb-3">Log in to your account</h1>
            <p class="text-center text-neutral-600 mb-4">Welcome! Please enter your credentials</p>

            <?php if (isset($error)): ?>
                <div id="errorToast" class="toast <?php echo isset($error_details) ? 'deactivated' : 'error'; ?>">
                    <div class="flex items-center">
                        <?php if (isset($error_details)): ?>
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <?php endif; ?>
                        <div>
                            <div class="font-semibold"><?= htmlspecialchars($error) ?></div>
                            <?php if (isset($error_details)): ?>
                                <div class="text-sm text-gray-600"><?= htmlspecialchars($error_details) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="index.php" method="post">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" required
                    class="border border-neutral-300 w-full p-2 rounded-md" placeholder="Enter your email">
                </div>

                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                        class="border border-neutral-300 w-full p-2 rounded-md" placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" name="submit" 
                        class="text-center text-white border indigo-600 bg-green-600 w-full p-2 rounded-md hover:bg-green-700 transition-colors">
                    Sign In
                </button>
            </form>
            <p class="text-center mt-4">
                <a href="landingpage.php" class="text-green-600 font-semibold hover:text-green-700">Back to Homepage</a>
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