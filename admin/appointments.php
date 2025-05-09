<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Fetch doctors for filter
$doctors = $conn->query("SELECT doctor_id, full_name FROM doctors ORDER BY full_name");

// Fetch status options
$status_options = ['upcoming', 'completed', 'cancelled', 'no-show'];

// Get filter values
$filter_doctor = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_from = isset($_GET['from']) ? $_GET['from'] : '';
$filter_to = isset($_GET['to']) ? $_GET['to'] : '';

// Build query
$query = "SELECT a.*, d.full_name AS doctor_name, p.full_name AS patient_name
          FROM appointments a
          JOIN doctors d ON a.doctor_id = d.doctor_id
          JOIN patients p ON a.patient_id = p.patient_id
          WHERE 1";
$params = [];
$types = '';

if ($filter_doctor) {
    $query .= " AND a.doctor_id = ?";
    $params[] = $filter_doctor;
    $types .= 'i';
}
if ($filter_status && in_array($filter_status, $status_options)) {
    $query .= " AND a.status = ?";
    $params[] = $filter_status;
    $types .= 's';
}
if ($filter_from) {
    $query .= " AND a.appointment_date >= ?";
    $params[] = $filter_from;
    $types .= 's';
}
if ($filter_to) {
    $query .= " AND a.appointment_date <= ?";
    $params[] = $filter_to;
    $types .= 's';
}
$query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$appointments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Monitoring - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-50">
<?php include 'navbar.php'; ?>
<?php include 'sidebar.php'; ?>
<main class="ml-64 p-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Appointment Monitoring</h1>
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
                    <select name="doctor_id" class="border border-gray-300 rounded px-3 py-2">
                        <option value="">All Doctors</option>
                        <?php $doctors->data_seek(0); while ($doc = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doc['doctor_id']; ?>" <?php if ($filter_doctor == $doc['doctor_id']) echo 'selected'; ?>><?php echo htmlspecialchars($doc['full_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="border border-gray-300 rounded px-3 py-2">
                        <option value="">All Statuses</option>
                        <?php foreach ($status_options as $status): ?>
                            <option value="<?php echo $status; ?>" <?php if ($filter_status == $status) echo 'selected'; ?>><?php echo ucfirst($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                    <input type="date" name="from" value="<?php echo htmlspecialchars($filter_from); ?>" class="border border-gray-300 rounded px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                    <input type="date" name="to" value="<?php echo htmlspecialchars($filter_to); ?>" class="border border-gray-300 rounded px-3 py-2">
                </div>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">Filter</button>
            </form>
        </div>
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php $i = 1; while ($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700"><?php echo $i++; ?></td>
                            <td class="px-4 py-2 text-sm text-gray-700"><?php echo htmlspecialchars($row['doctor_name']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-700"><?php echo htmlspecialchars($row['patient_name']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-700"><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                            <td class="px-4 py-2 text-sm text-gray-700"><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                            <td class="px-4 py-2 text-sm">
                                <?php
                                    $status = $row['status'];
                                    $color = match($status) {
                                        'completed' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        'no-show' => 'bg-yellow-100 text-yellow-800',
                                        default => 'bg-blue-100 text-blue-800',
                                    };
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $color; ?>"><?php echo ucfirst($status); ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>
</html> 