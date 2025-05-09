<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get total patients count
$patients_query = "SELECT COUNT(*) as total FROM patients";
$patients_result = $conn->query($patients_query);
$total_patients = $patients_result->fetch_assoc()['total'];

// Get total doctors count
$doctors_query = "SELECT COUNT(*) as total FROM doctors";
$doctors_result = $conn->query($doctors_query);
$total_doctors = $doctors_result->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <main class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Dashboard</h1>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Patients -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <span class="material-icons">people</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Patients</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_patients; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Total Doctors -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <span class="material-icons">local_hospital</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Doctors</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_doctors; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Appointments -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <span class="material-icons">calendar_today</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Appointments</p>
                            <p class="text-2xl font-semibold text-gray-800">0</p>
                        </div>
                    </div>
                </div>
                
                <!-- Revenue -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <span class="material-icons">payments</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Revenue</p>
                            <p class="text-2xl font-semibold text-gray-800">$0</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Recent Activity</h2>
                </div>
                <div class="p-6">
                    <p class="text-gray-500 text-center">No recent activity</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>