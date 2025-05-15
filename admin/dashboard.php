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

// Get total appointments count
$appointments_query = "SELECT COUNT(*) as total FROM appointments";
$appointments_result = $conn->query($appointments_query);
$total_appointments = $appointments_result->fetch_assoc()['total'];

// Get today's appointments count
$today_appointments_query = "SELECT COUNT(*) as total FROM appointments WHERE DATE(appointment_date) = CURDATE()";
$today_appointments_result = $conn->query($today_appointments_query);
$today_appointments = $today_appointments_result->fetch_assoc()['total'];

// Get monthly appointment trends for the last 6 months
$monthly_appointments_query = "SELECT 
    DATE_FORMAT(appointment_date, '%Y-%m') as month,
    COUNT(*) as count
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month";
$monthly_appointments_result = $conn->query($monthly_appointments_query);
$monthly_appointments_data = [];
$monthly_appointments_labels = [];
while($row = $monthly_appointments_result->fetch_assoc()) {
    $monthly_appointments_data[] = $row['count'];
    $monthly_appointments_labels[] = date('M Y', strtotime($row['month'] . '-01'));
}

// Get appointment status distribution
$appointment_status_query = "SELECT status, COUNT(*) as count FROM appointments GROUP BY status";
$appointment_status_result = $conn->query($appointment_status_query);
$appointment_status_data = [];
$appointment_status_labels = [];
while($row = $appointment_status_result->fetch_assoc()) {
    $appointment_status_data[] = $row['count'];
    $appointment_status_labels[] = ucfirst($row['status']);
}

// Get doctor workload distribution
$doctor_workload_query = "SELECT d.full_name, COUNT(a.appointment_id) as appointment_count 
    FROM doctors d 
    LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
    GROUP BY d.doctor_id 
    ORDER BY appointment_count DESC 
    LIMIT 5";
$doctor_workload_result = $conn->query($doctor_workload_query);
$doctor_names = [];
$doctor_appointments = [];
while($row = $doctor_workload_result->fetch_assoc()) {
    $doctor_names[] = $row['full_name'];
    $doctor_appointments[] = $row['appointment_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <p class="text-sm text-gray-500">Today's Appointments</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $today_appointments; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Total Appointments -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <span class="material-icons">event_note</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Appointments</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_appointments; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Appointment Trends -->
            <div class="bg-white rounded-lg shadow mb-8">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Appointment Trends</h2>
                </div>
                <div class="p-6">
                    <canvas id="appointmentTrendsChart" height="100"></canvas>
                </div>
            </div>

            <!-- Statistics Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Appointment Status Distribution -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-lg font-semibold text-gray-800">Appointment Status Distribution</h2>
                    </div>
                    <div class="p-6">
                        <canvas id="appointmentStatusChart"></canvas>
                    </div>
                </div>

                <!-- Doctor Workload -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b">
                        <h2 class="text-lg font-semibold text-gray-800">Top 5 Doctors by Appointments</h2>
                    </div>
                    <div class="p-6">
                        <canvas id="doctorWorkloadChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Appointment Trends Chart
        const trendsCtx = document.getElementById('appointmentTrendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($monthly_appointments_labels); ?>,
                datasets: [{
                    label: 'Monthly Appointments',
                    data: <?php echo json_encode($monthly_appointments_data); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Appointment Status Distribution Chart
        const statusCtx = document.getElementById('appointmentStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($appointment_status_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($appointment_status_data); ?>,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Doctor Workload Chart
        const workloadCtx = document.getElementById('doctorWorkloadChart').getContext('2d');
        new Chart(workloadCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($doctor_names); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode($doctor_appointments); ?>,
                    backgroundColor: 'rgb(59, 130, 246)'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>