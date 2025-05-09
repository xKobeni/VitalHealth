<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get total appointments for the period
$total_appointments_query = "SELECT COUNT(*) as total FROM appointments 
                           WHERE appointment_date BETWEEN ? AND ?";
$stmt = $conn->prepare($total_appointments_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$total_appointments = $stmt->get_result()->fetch_assoc()['total'];

// Get appointments by status
$status_query = "SELECT status, COUNT(*) as count 
                FROM appointments 
                WHERE appointment_date BETWEEN ? AND ?
                GROUP BY status";
$stmt = $conn->prepare($status_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$status_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get daily appointment counts
$daily_query = "SELECT appointment_date, COUNT(*) as count 
                FROM appointments 
                WHERE appointment_date BETWEEN ? AND ?
                GROUP BY appointment_date 
                ORDER BY appointment_date";
$stmt = $conn->prepare($daily_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$daily_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get doctor statistics
$doctor_stats_query = "SELECT d.full_name, d.specialization,
                      COUNT(a.appointment_id) as total_appointments,
                      SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                      SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
                      FROM doctors d
                      LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
                      AND a.appointment_date BETWEEN ? AND ?
                      GROUP BY d.doctor_id
                      ORDER BY total_appointments DESC";
$stmt = $conn->prepare($doctor_stats_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$doctor_stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <main class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Reports & Statistics</h1>
            
            <!-- Date Range Filter -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <form method="GET" class="flex gap-4 items-end">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                               class="border border-gray-300 rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                               class="border border-gray-300 rounded px-3 py-2">
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Update Report
                    </button>
                </form>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Appointments -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <span class="material-icons">calendar_today</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Total Appointments</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $total_appointments; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <?php foreach($status_stats as $stat): ?>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full <?php 
                            echo match($stat['status']) {
                                'completed' => 'bg-green-100 text-green-600',
                                'cancelled' => 'bg-red-100 text-red-600',
                                'no-show' => 'bg-yellow-100 text-yellow-600',
                                default => 'bg-blue-100 text-blue-600'
                            };
                        ?>">
                            <span class="material-icons"><?php 
                                echo match($stat['status']) {
                                    'completed' => 'check_circle',
                                    'cancelled' => 'cancel',
                                    'no-show' => 'warning',
                                    default => 'schedule'
                                };
                            ?></span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500"><?php echo ucfirst($stat['status']); ?></p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $stat['count']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Daily Appointments Chart -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Daily Appointments</h2>
                <canvas id="dailyChart" height="100"></canvas>
            </div>

            <!-- Doctor Statistics -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold text-gray-800">Doctor Statistics</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Specialization</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Appointments</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cancelled</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($doctor_stats as $stat): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($stat['full_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($stat['specialization']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $stat['total_appointments']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $stat['completed_appointments']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo $stat['cancelled_appointments']; ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $completion_rate = $stat['total_appointments'] > 0 
                                        ? round(($stat['completed_appointments'] / $stat['total_appointments']) * 100, 1)
                                        : 0;
                                    ?>
                                    <div class="text-sm text-gray-900"><?php echo $completion_rate; ?>%</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Daily appointments chart
        const dailyCtx = document.getElementById('dailyChart').getContext('2d');
        new Chart(dailyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_stats, 'appointment_date')); ?>,
                datasets: [{
                    label: 'Appointments',
                    data: <?php echo json_encode(array_column($daily_stats, 'count')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
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