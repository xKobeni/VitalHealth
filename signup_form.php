<?php
include 'config/database.php';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];
    
    // Add additional fields based on role
    if ($role === 'doctor') {
        $department = $_POST['department'];
        $contact_number = $_POST['contact_number'];
        
        // First insert into users table
        $sql = "INSERT INTO users (email, password, role) 
                VALUES ('$email', '$password', '$role')";
        
        if (mysqli_query($conn, $sql)) {
            // Get the user_id of the newly inserted user
            $user_id = mysqli_insert_id($conn);
            
            // Then insert into doctors table
            $sql2 = "INSERT INTO doctors (user_id, full_name, department, contact_number) 
                    VALUES ('$user_id', '$fullname', '$department', '$contact_number')";
            
            if (mysqli_query($conn, $sql2)) {
                header('Location: index.php');
                exit();
            } else {
                $error = "Failed to create doctor profile. Please try again.";
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    } else {
        // For patients, just insert into users table
        $sql = "INSERT INTO users (email, password, role) 
                VALUES ('$email', '$password', '$role')";
        
        if (mysqli_query($conn, $sql)) {
            // Get the user_id of the newly inserted user
            $user_id = mysqli_insert_id($conn);

            // Get additional patient information
            $date_of_birth = $_POST['date_of_birth'];
            $gender = $_POST['gender'];
            $contact_number = $_POST['contact_number'];
            $address = $_POST['address'];

            // Then insert into patients table with additional fields
            $sql2 = "INSERT INTO patients (user_id, full_name, date_of_birth, gender, contact_number, address) 
                    VALUES ('$user_id', '$fullname', '$date_of_birth', '$gender', '$contact_number', '$address')";

            if (mysqli_query($conn, $sql2)) {
                header('Location: index.php');
                exit();
            } else {
                $error = "Failed to create patient profile. Please try again.";
            }
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}

$role = isset($_GET['role']) ? $_GET['role'] : '';
if (!in_array($role, ['doctor', 'patient'])) {
    header('Location: signup.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <title>Sign Up - <?php echo ucfirst($role); ?></title>
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
        .toast.success {
            border-left: 4px solid #10b981;
        }
    </style>
</head>
<body>
    <div class="flex justify-center items-center min-h-screen bg-sky-100 py-8">
        <div class="w-130 p-6 shadow-lg bg-white rounded-md">
            <i class="fas fa-heartbeat text-green-500 text-4xl"></i>
            <h1 class="text-3xl text-center font-semibold mb-3">Create <?php echo ucfirst($role); ?> Account</h1>
            <p class="text-center text-neutral-600 mb-4">Please fill in your details</p>
            
            <?php if (isset($error)): ?>
                <div id="errorToast" class="toast error">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-500 mr-2"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <form action="signup_form.php" method="post">
                <input type="hidden" name="role" value="<?php echo $role; ?>">
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="fullname">Full Name</label>
                    <input type="text" name="fullname" id="fullname" required
                    class="border border-neutral-300 w-full p-2 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                    <input type="email" name="email" id="email" required
                    class="border border-neutral-300 w-full p-2 rounded-md">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                        <button type="button" onclick="togglePassword()" 
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <?php if ($role === 'doctor'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="department">Department</label>
                        <select name="department" id="department" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                            <option value="">Select Department</option>
                            <option value="Cardiology">Cardiology</option>
                            <option value="Dermatology">Dermatology</option>
                            <option value="Endocrinology">Endocrinology</option>
                            <option value="Family Medicine">Family Medicine</option>
                            <option value="Gastroenterology">Gastroenterology</option>
                            <option value="General Medicine">General Medicine</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Obstetrics and Gynecology">Obstetrics and Gynecology</option>
                            <option value="Ophthalmology">Ophthalmology</option>
                            <option value="Orthopedics">Orthopedics</option>
                            <option value="Pediatrics">Pediatrics</option>
                            <option value="Psychiatry">Psychiatry</option>
                            <option value="Pulmonology">Pulmonology</option>
                            <option value="Rheumatology">Rheumatology</option>
                            <option value="Urology">Urology</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                    </div>
                <?php else: ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="date_of_birth">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="gender">Gender</label>
                        <select name="gender" id="gender" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">Contact Number</label>
                        <input type="tel" name="contact_number" id="contact_number" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="address">Address</label>
                        <input name="address" id="address" required rows="3"
                        class="border border-neutral-300 w-full p-2 rounded-md"></input>
                    </div>
                <?php endif; ?>

                <button type="submit" name="submit" 
                        class="text-center text-white border indigo-600 bg-green-600 w-full mt-4 p-2 rounded-md hover:bg-green-700 transition-colors">
                    Create Account
                </button>
            </form>

            <p class="text-center mt-4">
                Already have an account? <a href="index.php" class="text-green-600 font-semibold">Sign In</a>
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