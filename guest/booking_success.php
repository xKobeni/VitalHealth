<?php
include '../config/database.php';
include '../config/functions.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <div class="mb-6">
                    <i class="fas fa-check-circle text-green-500 text-6xl"></i>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-800 mb-4">Appointment Booked Successfully!</h1>
                
                <p class="text-gray-600 mb-6">
                    Your appointment request has been submitted. The doctor will review your request and you will receive a confirmation email shortly.
                </p>

                <div class="space-y-4">
                    <a href="index.php" 
                       class="inline-block bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        View Other Doctors
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 