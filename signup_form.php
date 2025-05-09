<?php
include 'config/database.php';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $fullname = $_POST['fullname'];
    $role = $_POST['role'];
    
    // Add additional fields based on role
    if ($role === 'doctor') {
        $specialization = $_POST['specialization'];
        $contact_number = $_POST['contact_number'];
        
        // First insert into users table
        $sql = "INSERT INTO users (email, password, role) 
                VALUES ('$email', '$password', '$role')";
        
        if (mysqli_query($conn, $sql)) {
            // Get the user_id of the newly inserted user
            $user_id = mysqli_insert_id($conn);
            
            // Then insert into doctors table
            $sql2 = "INSERT INTO doctors (user_id, full_name, specialization, contact_number) 
                    VALUES ('$user_id', '$fullname', '$specialization', '$contact_number')";
            
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

            // Then insert into patients table
            $sql2 = "INSERT INTO patients (user_id, full_name) 
                    VALUES ('$user_id', '$fullname')";

            if (mysqli_query($conn, $sql2)) {
                header('Location: index.php');
                exit();
            } else {
                $error = "Failed to create doctor profile. Please try again.";
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
</head>
<body>
    <div class="flex justify-center items-center min-h-screen bg-sky-100 py-8">
        <div class="w-130 p-6 shadow-lg bg-white rounded-md">
            <i class="fas fa-heartbeat text-green-500 text-4xl"></i>
            <h1 class="text-3xl text-center font-semibold mb-3">Create <?php echo ucfirst($role); ?> Account</h1>
            <p class="text-center text-neutral-600 mb-4">Please fill in your details</p>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
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
                    <input type="password" name="password" id="password" required
                    class="border border-neutral-300 w-full p-2 rounded-md">
                </div>

                <?php if ($role === 'doctor'): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="specialization">Specialization</label>
                        <input type="text" name="specialization" id="specialization" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="contact_number">Contact Number</label>
                        <input type="text" name="contact_number" id="contact_number" required
                        class="border border-neutral-300 w-full p-2 rounded-md">
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
</body>
</html> 