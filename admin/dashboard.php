<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get current date and time
$current_date = date('Y-m-d');
$current_month = date('Y-m');

// Get total counts
$total_doctors_query = "SELECT COUNT(*) as count FROM doctors";
$total_patients_query = "SELECT COUNT(*) as count FROM patients";
$total_appointments_query = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ?";
$total_departments_query = "SELECT COUNT(DISTINCT department) as count FROM doctors";

$total_doctors = $conn->query($total_doctors_query)->fetch_assoc()['count'];
$total_patients = $conn->query($total_patients_query)->fetch_assoc()['count'];
$stmt = $conn->prepare($total_appointments_query);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['count'];
$total_departments = $conn->query($total_departments_query)->fetch_assoc()['count'];

// Get today's appointments with status
$today_appointments_query = "SELECT 
                           a.*, 
                           p.full_name as patient_name,
                           d.full_name as doctor_name,
                           d.department
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.patient_id
                           JOIN doctors d ON a.doctor_id = d.doctor_id
                           WHERE a.appointment_date = ?
                           ORDER BY a.appointment_time";
$stmt = $conn->prepare($today_appointments_query);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get department-wise appointment distribution
$department_stats_query = "SELECT 
                         d.department,
                         COUNT(a.appointment_id) as total_appointments,
                         SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                         SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                         FROM doctors d
                         LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
                         AND a.appointment_date = ?
                         GROUP BY d.department";
$stmt = $conn->prepare($department_stats_query);
$stmt->bind_param("s", $current_date);
$stmt->execute();
$department_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent patient registrations
$recent_patients_query = "SELECT * FROM patients ORDER BY patient_id DESC LIMIT 5";
$recent_patients = $conn->query($recent_patients_query)->fetch_all(MYSQLI_ASSOC);

// Get appointment status distribution
$status_distribution_query = "SELECT 
                            status,
                            COUNT(*) as count,
                            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM appointments WHERE appointment_date = ?), 1) as percentage
                            FROM appointments 
                            WHERE appointment_date = ?
                            GROUP BY status";
$stmt = $conn->prepare($status_distribution_query);
$stmt->bind_param("ss", $current_date, $current_date);
$stmt->execute();
$status_distribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent doctors
$recent_doctors_query = "SELECT * FROM doctors ORDER BY doctor_id DESC LIMIT 5";
$recent_doctors = $conn->query($recent_doctors_query)->fetch_all(MYSQLI_ASSOC);

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
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <main class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800">Admin Dashboard</h1>
                <p class="text-gray-600 mt-2">Welcome back! Here's what's happening today.</p>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Doctors -->
                <div class="stat-card card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <span class="material-icons">medical_services</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Active Doctors</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_doctors; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Patients -->
                <div class="stat-card card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <span class="material-icons">people</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Patients</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_patients; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Today's Appointments -->
                <div class="stat-card card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <span class="material-icons">calendar_today</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Today's Appointments</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_appointments; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Total Departments -->
                <div class="stat-card card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <span class="material-icons">business</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Departments</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_departments; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <!-- Today's Appointments -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Today's Appointments</h2>
                        <a href="appointments.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <?php if (empty($today_appointments)): ?>
                            <div class="text-center py-8">
                                <span class="material-icons text-gray-400 text-4xl mb-2">event_busy</span>
                                <p class="text-gray-500 text-lg">No appointments scheduled for today</p>
                            </div>
                        <?php else: ?>
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach($today_appointments as $appointment): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo date('g:i A', strtotime($appointment['appointment_time'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($appointment['patient_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($appointment['doctor_name']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($appointment['department']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo match($appointment['status']) {
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'cancelled' => 'bg-red-100 text-red-800',
                                                    'no-show' => 'bg-yellow-100 text-yellow-800',
                                                    default => 'bg-blue-100 text-blue-800'
                                                }; ?>">
                                                <?php echo ucfirst($appointment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Department Performance -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 mb-6">Department Performance</h2>
                    <div class="chart-container">
                        <canvas id="departmentChart"></canvas>
                    </div>
                </div>

                <!-- Recent Patients -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Patients</h2>
                        <a href="patients.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach($recent_patients as $patient): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($patient['full_name']); ?></h3>
                                <p class="text-xs text-gray-500">Patient ID: <?php echo $patient['patient_id']; ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Gender</p>
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($patient['gender'] ?? 'N/A'); ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Doctors -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Recent Doctors</h2>
                        <a href="doctors.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
                    </div>
                    <div class="space-y-4">
                        <?php foreach($recent_doctors as $doctor): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div>
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($doctor['department']); ?></p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Doctor ID</p>
                                <p class="text-sm font-medium text-gray-900">
                                    <?php echo $doctor['doctor_id']; ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Chart.js global defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.legend.labels.padding = 20;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;

        // Department Performance Chart
        const departmentCtx = document.getElementById('departmentChart').getContext('2d');
        new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($department_stats, 'department')); ?>,
                datasets: [{
                    label: 'Total Appointments',
                    data: <?php echo json_encode(array_column($department_stats, 'total_appointments')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($department_stats, 'completed')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)'
                }, {
                    label: 'Cancelled',
                    data: <?php echo json_encode(array_column($department_stats, 'cancelled')); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        },
                        grid: {
                            display: true,
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>