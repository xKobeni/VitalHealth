<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$department = isset($_GET['department']) ? $_GET['department'] : 'all';
$time_period = isset($_GET['time_period']) ? $_GET['time_period'] : 'custom';

// Adjust dates based on time period selection
switch($time_period) {
    case 'today':
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'last_week':
        $start_date = date('Y-m-d', strtotime('monday last week'));
        $end_date = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('-1 month'));
        $end_date = date('Y-m-t', strtotime('-1 month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
}

// Get all departments for filter
$departments_query = "SELECT DISTINCT department FROM doctors ORDER BY department";
$departments = $conn->query($departments_query)->fetch_all(MYSQLI_ASSOC);

// Modify queries to include department filter
$department_condition = $department !== 'all' ? "AND d.department = ?" : "";

// Get department-wise revenue (if applicable)
$revenue_query = "SELECT 
                 d.department,
                 COUNT(a.appointment_id) as total_appointments,
                 SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
                 ROUND(AVG(TIMESTAMPDIFF(MINUTE, a.appointment_time, a.appointment_time + INTERVAL 30 MINUTE)), 1) as avg_duration
                 FROM doctors d
                 LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
                 AND a.appointment_date BETWEEN ? AND ?
                 WHERE 1=1 $department_condition
                 GROUP BY d.department
                 ORDER BY total_appointments DESC";
$stmt = $conn->prepare($revenue_query);
if ($department !== 'all') {
    $stmt->bind_param("sss", $start_date, $end_date, $department);
} else {
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$department_analytics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get peak hours analysis with more detailed time slots
$peak_hours_query = "SELECT 
                    HOUR(appointment_time) as hour,
                    COUNT(*) as total_count,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(CASE WHEN status = 'no-show' THEN 1 ELSE 0 END) as no_show_count,
                    ROUND(AVG(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100, 1) as completion_rate
                    FROM appointments 
                    WHERE appointment_date BETWEEN ? AND ?
                    GROUP BY HOUR(appointment_time)
                    ORDER BY hour";
$stmt = $conn->prepare($peak_hours_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$peak_hours = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format hours for display
$formatted_hours = array_map(function($hour) {
    $h = intval($hour['hour']);
    return [
        'hour' => $hour['hour'],
        'label' => date('g A', strtotime("$h:00")),
        'total_count' => $hour['total_count'],
        'completed_count' => $hour['completed_count'],
        'cancelled_count' => $hour['cancelled_count'],
        'no_show_count' => $hour['no_show_count'],
        'completion_rate' => $hour['completion_rate']
    ];
}, $peak_hours);

// Get patient retention rate
$retention_query = "SELECT 
                   COUNT(DISTINCT patient_id) as total_patients,
                   COUNT(DISTINCT CASE WHEN visit_count > 1 THEN patient_id END) as returning_patients
                   FROM (
                       SELECT patient_id, COUNT(*) as visit_count
                       FROM appointments
                       WHERE appointment_date BETWEEN ? AND ?
                       GROUP BY patient_id
                   ) as patient_visits";
$stmt = $conn->prepare($retention_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$retention_stats = $stmt->get_result()->fetch_assoc();

// Calculate retention rate
$retention_rate = $retention_stats['total_patients'] > 0 
    ? round(($retention_stats['returning_patients'] / $retention_stats['total_patients']) * 100, 1)
    : 0;

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
$doctor_stats_query = "SELECT d.full_name, d.department,
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

// Initialize demographics array with default values
$demographics = [
    'total_patients' => 0,
    'male_count' => 0,
    'female_count' => 0,
    'avg_age' => 0
];

// Get patient demographics with error handling
try {
    $demographics_query = "SELECT 
                          COUNT(DISTINCT p.patient_id) as total_patients,
                          SUM(CASE WHEN p.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                          SUM(CASE WHEN p.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
                          AVG(TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE())) as avg_age
                          FROM patients p
                          JOIN appointments a ON p.patient_id = a.patient_id
                          WHERE a.appointment_date BETWEEN ? AND ?";
    $stmt = $conn->prepare($demographics_query);
    if ($stmt) {
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $demographics = [
                'total_patients' => $row['total_patients'] ?? 0,
                'male_count' => $row['male_count'] ?? 0,
                'female_count' => $row['female_count'] ?? 0,
                'avg_age' => $row['avg_age'] ?? 0
            ];
        }
    }
} catch (Exception $e) {
    // Log error but continue execution
    error_log("Error in demographics query: " . $e->getMessage());
}

// Get appointment status distribution for pie chart
$status_distribution_query = "SELECT 
                            status,
                            COUNT(*) as count,
                            ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM appointments WHERE appointment_date BETWEEN ? AND ?), 1) as percentage
                            FROM appointments 
                            WHERE appointment_date BETWEEN ? AND ?
                            GROUP BY status";
$stmt = $conn->prepare($status_distribution_query);
$stmt->bind_param("ssss", $start_date, $end_date, $start_date, $end_date);
$stmt->execute();
$status_distribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get monthly trend data
$monthly_trend_query = "SELECT 
                       DATE_FORMAT(appointment_date, '%Y-%m') as month,
                       COUNT(*) as total_appointments,
                       SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                       SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
                       FROM appointments 
                       WHERE appointment_date >= DATE_SUB(?, INTERVAL 6 MONTH)
                       GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
                       ORDER BY month";
$stmt = $conn->prepare($monthly_trend_query);
$stmt->bind_param("s", $end_date);
$stmt->execute();
$monthly_trend = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get doctor performance metrics
$doctor_performance_query = "SELECT 
                           d.full_name,
                           COUNT(a.appointment_id) as total_appointments,
                           SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                           ROUND(SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) * 100.0 / 
                           NULLIF(COUNT(a.appointment_id), 0), 1) as completion_rate
                           FROM doctors d
                           LEFT JOIN appointments a ON d.doctor_id = a.doctor_id 
                           AND a.appointment_date BETWEEN ? AND ?
                           GROUP BY d.doctor_id
                           HAVING total_appointments > 0
                           ORDER BY completed DESC
                           LIMIT 5";
$stmt = $conn->prepare($doctor_performance_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$doctor_performance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get patient age distribution
$age_distribution_query = "SELECT 
                          CASE 
                              WHEN TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) < 18 THEN 'Under 18'
                              WHEN TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                              WHEN TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 31 AND 45 THEN '31-45'
                              WHEN TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 46 AND 60 THEN '46-60'
                              ELSE 'Over 60'
                          END as age_group,
                          COUNT(DISTINCT p.patient_id) as count
                          FROM patients p
                          JOIN appointments a ON p.patient_id = a.patient_id
                          WHERE a.appointment_date BETWEEN ? AND ?
                          GROUP BY age_group
                          ORDER BY FIELD(age_group, 'Under 18', '18-30', '31-45', '46-60', 'Over 60')";
$stmt = $conn->prepare($age_distribution_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$age_distribution = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
                <h1 class="text-3xl font-bold text-gray-800">Reports & Analytics</h1>
                <p class="text-gray-600 mt-2">Comprehensive overview of hospital performance and statistics</p>
            </div>
            
            <!-- Date Range Filter -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                <form method="GET" class="space-y-4">
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                            <select name="time_period" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                                <option value="custom" <?php echo $time_period === 'custom' ? 'selected' : ''; ?>>Custom Range</option>
                                <option value="today" <?php echo $time_period === 'today' ? 'selected' : ''; ?>>Today</option>
                                <option value="yesterday" <?php echo $time_period === 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                                <option value="this_week" <?php echo $time_period === 'this_week' ? 'selected' : ''; ?>>This Week</option>
                                <option value="last_week" <?php echo $time_period === 'last_week' ? 'selected' : ''; ?>>Last Week</option>
                                <option value="this_month" <?php echo $time_period === 'this_month' ? 'selected' : ''; ?>>This Month</option>
                                <option value="last_month" <?php echo $time_period === 'last_month' ? 'selected' : ''; ?>>Last Month</option>
                                <option value="this_year" <?php echo $time_period === 'this_year' ? 'selected' : ''; ?>>This Year</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <select name="department" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                                <option value="all" <?php echo $department === 'all' ? 'selected' : ''; ?>>All Departments</option>
                                <?php foreach($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" 
                                        <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4 items-end" id="customDateRange" <?php echo $time_period === 'custom' ? '' : 'style="display: none;"'; ?>>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $start_date; ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $end_date; ?>" 
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <button type="submit" class="gradient-bg text-white px-6 py-2 rounded-lg hover:opacity-90 transition-opacity">
                            Update Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Additional Analytics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Patient Retention -->
                <div class="stat-card card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <span class="material-icons">people</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Patient Retention</p>
                            <p class="text-2xl font-semibold text-gray-800"><?php echo $retention_rate; ?>%</p>
                        </div>
                    </div>
                </div>

                <!-- Average Appointment Duration -->
                <div class="stat-card card-hover">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <span class="material-icons">schedule</span>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-500">Avg. Duration</p>
                            <p class="text-2xl font-semibold text-gray-800">
                                <?php 
                                $avg_duration = array_sum(array_column($department_analytics, 'avg_duration')) / count($department_analytics);
                                echo round($avg_duration);
                                ?> min
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Department Analytics -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Department Performance</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Appointments</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($department_analytics as $dept): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dept['department']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($dept['total_appointments']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo number_format($dept['completed_appointments']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo round($dept['avg_duration']); ?> min</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $completion_rate = $dept['total_appointments'] > 0 
                                        ? round(($dept['completed_appointments'] / $dept['total_appointments']) * 100, 1)
                                        : 0;
                                    ?>
                                    <div class="flex items-center">
                                        <div class="text-sm text-gray-900 mr-2"><?php echo $completion_rate; ?>%</div>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?php echo $completion_rate; ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Peak Hours Analysis -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Peak Hours Analysis</h2>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="chart-container">
                        <canvas id="peakHoursChart"></canvas>
                    </div>
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Busiest Hours</h3>
                            <div class="space-y-2">
                                <?php 
                                // Sort by total count and get top 3
                                usort($formatted_hours, function($a, $b) {
                                    return $b['total_count'] - $a['total_count'];
                                });
                                $top_hours = array_slice($formatted_hours, 0, 3);
                                foreach($top_hours as $hour): 
                                ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600"><?php echo $hour['label']; ?></span>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $hour['total_count']; ?> appointments</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full" 
                                                 style="width: <?php echo ($hour['total_count'] / $top_hours[0]['total_count'] * 100); ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Best Completion Rates</h3>
                            <div class="space-y-2">
                                <?php 
                                // Sort by completion rate and get top 3
                                usort($formatted_hours, function($a, $b) {
                                    return $b['completion_rate'] - $a['completion_rate'];
                                });
                                $top_completion = array_slice($formatted_hours, 0, 3);
                                foreach($top_completion as $hour): 
                                ?>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600"><?php echo $hour['label']; ?></span>
                                    <div class="flex items-center">
                                        <span class="text-sm font-medium text-gray-900 mr-2"><?php echo $hour['completion_rate']; ?>%</span>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-green-600 h-1.5 rounded-full" 
                                                 style="width: <?php echo $hour['completion_rate']; ?>%"></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Appointments -->
                <div class="stat-card card-hover">
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
                <div class="stat-card card-hover">
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

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Daily Appointments Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Daily Appointments</h2>
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- Status Distribution Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Appointment Status Distribution</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="flex flex-col justify-center">
                            <?php foreach($status_distribution as $status): ?>
                            <div class="mb-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700"><?php echo ucfirst($status['status']); ?></span>
                                    <span class="text-sm font-medium text-gray-700"><?php echo $status['percentage']; ?>%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $status['percentage']; ?>%"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Monthly Trend Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">6-Month Appointment Trend</h2>
                    <div class="chart-container">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>

                <!-- Doctor Performance Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Top 5 Doctors by Performance</h2>
                    <div class="chart-container">
                        <canvas id="doctorPerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Patient Demographics -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-6">Patient Demographics</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="stat-card">
                        <h3 class="font-medium text-gray-900 mb-4">Gender Distribution</h3>
                        <div class="flex justify-between items-center">
                            <div class="text-center">
                                <p class="text-2xl font-semibold text-blue-600"><?php echo number_format($demographics['male_count']); ?></p>
                                <p class="text-sm text-gray-600">Male</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-semibold text-pink-600"><?php echo number_format($demographics['female_count']); ?></p>
                                <p class="text-sm text-gray-600">Female</p>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3 class="font-medium text-gray-900 mb-4">Total Patients</h3>
                        <p class="text-3xl font-semibold text-gray-800"><?php echo number_format($demographics['total_patients']); ?></p>
                        <p class="text-sm text-gray-600 mt-2">Unique patients in selected period</p>
                    </div>
                    <div class="stat-card">
                        <h3 class="font-medium text-gray-900 mb-4">Average Age</h3>
                        <p class="text-3xl font-semibold text-gray-800"><?php echo round($demographics['avg_age']); ?></p>
                        <p class="text-sm text-gray-600 mt-2">Years</p>
                    </div>
                </div>
            </div>

            <!-- Age Distribution Chart -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8 border border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Patient Age Distribution</h2>
                <div class="chart-container">
                    <canvas id="ageDistributionChart"></canvas>
                </div>
            </div>

            <!-- Doctor Statistics Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">Doctor Statistics</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Appointments</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cancelled</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completion Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($doctor_stats as $stat): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($stat['full_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($stat['department']); ?></div>
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
                                    <div class="flex items-center">
                                        <div class="text-sm text-gray-900 mr-2"><?php echo $completion_rate; ?>%</div>
                                        <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                            <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?php echo $completion_rate; ?>%"></div>
                                        </div>
                                    </div>
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
        // Chart.js global defaults
        Chart.defaults.font.family = "'Inter', sans-serif";
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        Chart.defaults.plugins.tooltip.padding = 12;
        Chart.defaults.plugins.tooltip.cornerRadius = 8;
        Chart.defaults.plugins.legend.labels.padding = 20;
        Chart.defaults.plugins.legend.labels.usePointStyle = true;

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
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
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

        // Status distribution chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($status) { 
                    return ucfirst($status['status']); 
                }, $status_distribution)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($status_distribution, 'count')); ?>,
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',  // green for completed
                        'rgba(239, 68, 68, 0.8)',  // red for cancelled
                        'rgba(234, 179, 8, 0.8)',  // yellow for no-show
                        'rgba(59, 130, 246, 0.8)'  // blue for others
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                },
                cutout: '70%'
            }
        });

        // Monthly trend chart
        const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
        new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($month) { 
                    return date('M Y', strtotime($month['month'] . '-01')); 
                }, $monthly_trend)); ?>,
                datasets: [{
                    label: 'Total Appointments',
                    data: <?php echo json_encode(array_column($monthly_trend, 'total_appointments')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($monthly_trend, 'completed')); ?>,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
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

        // Doctor performance chart
        const doctorPerformanceCtx = document.getElementById('doctorPerformanceChart').getContext('2d');
        new Chart(doctorPerformanceCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($doctor_performance, 'full_name')); ?>,
                datasets: [{
                    label: 'Completed Appointments',
                    data: <?php echo json_encode(array_column($doctor_performance, 'completed')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)'
                }, {
                    label: 'Total Appointments',
                    data: <?php echo json_encode(array_column($doctor_performance, 'total_appointments')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }, {
                    label: 'Completion Rate (%)',
                    data: <?php echo json_encode(array_column($doctor_performance, 'completion_rate')); ?>,
                    type: 'line',
                    borderColor: 'rgba(234, 179, 8, 0.8)',
                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                    fill: true,
                    yAxisID: 'percentage'
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
                        title: {
                            display: true,
                            text: 'Number of Appointments'
                        },
                        grid: {
                            display: true,
                            drawBorder: false
                        }
                    },
                    percentage: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Completion Rate (%)'
                        },
                        grid: {
                            display: false
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

        // Age distribution chart
        const ageDistributionCtx = document.getElementById('ageDistributionChart').getContext('2d');
        new Chart(ageDistributionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($age_distribution, 'age_group')); ?>,
                datasets: [{
                    label: 'Number of Patients',
                    data: <?php echo json_encode(array_column($age_distribution, 'count')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Peak hours chart
        const peakHoursCtx = document.getElementById('peakHoursChart').getContext('2d');
        new Chart(peakHoursCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($formatted_hours, 'label')); ?>,
                datasets: [{
                    label: 'Total Appointments',
                    data: <?php echo json_encode(array_column($formatted_hours, 'total_count')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    order: 1
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column($formatted_hours, 'completed_count')); ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    order: 2
                }, {
                    label: 'Cancelled',
                    data: <?php echo json_encode(array_column($formatted_hours, 'cancelled_count')); ?>,
                    backgroundColor: 'rgba(239, 68, 68, 0.8)',
                    order: 3
                }, {
                    label: 'No Show',
                    data: <?php echo json_encode(array_column($formatted_hours, 'no_show_count')); ?>,
                    backgroundColor: 'rgba(234, 179, 8, 0.8)',
                    order: 4
                }, {
                    label: 'Completion Rate (%)',
                    data: <?php echo json_encode(array_column($formatted_hours, 'completion_rate')); ?>,
                    type: 'line',
                    borderColor: 'rgba(139, 92, 246, 0.8)',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    fill: true,
                    yAxisID: 'percentage',
                    order: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.dataset.yAxisID === 'percentage') {
                                    label += context.parsed.y + '%';
                                } else {
                                    label += context.parsed.y + ' appointments';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Appointments'
                        },
                        grid: {
                            display: true,
                            drawBorder: false
                        }
                    },
                    percentage: {
                        beginAtZero: true,
                        max: 100,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Completion Rate (%)'
                        },
                        grid: {
                            display: false
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