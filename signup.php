<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <title>Choose Account Type</title>
</head>
<body>
    <div class="flex justify-center items-center h-screen bg-sky-100">
        <div class="w-130 p-6 shadow-lg bg-white rounded-md">
            <i class="fas fa-heartbeat text-green-500 text-4xl"></i>
            <h1 class="text-3xl text-center font-semibold mb-3">Create an Account</h1>
            <p class="text-center text-neutral-600 mb-4">Choose your account type</p>
            
            <div class="grid grid-cols-2 gap-4 mt-6">
                <a href="signup_form.php?role=patient" class="p-6 border rounded-lg text-center hover:bg-green-50 transition-colors">
                    <i class="fas fa-user text-4xl text-green-500 mb-3"></i>
                    <h2 class="text-xl font-semibold mb-2">Patient</h2>
                    <p class="text-sm text-gray-600">Create a patient account to book appointments and manage your healthcare</p>
                </a>
                
                <a href="signup_form.php?role=doctor" class="p-6 border rounded-lg text-center hover:bg-green-50 transition-colors">
                    <i class="fas fa-user-md text-4xl text-green-500 mb-3"></i>
                    <h2 class="text-xl font-semibold mb-2">Doctor</h2>
                    <p class="text-sm text-gray-600">Create a doctor account to manage appointments and patient records</p>
                </a>
            </div>
            
            <p class="text-center mt-6">Already have an account? <a href="index.php" class="text-green-600 font-semibold">Sign In</a></p>
        </div>
    </div>
</body>
</html> 