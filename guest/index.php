<?php
include '../config/database.php';
include '../config/functions.php';

// Get all departments for filter
$dept_sql = "SELECT DISTINCT department FROM doctors ORDER BY department";
$dept_result = $conn->query($dept_sql);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['department'];
}

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Build the query with filters
$sql = "SELECT d.*, u.email 
        FROM doctors d 
        JOIN users u ON d.user_id = u.user_id 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (d.full_name LIKE ? OR d.department LIKE ?)";
}
if (!empty($department)) {
    $sql .= " AND d.department = ?";
}

$sql .= " ORDER BY d.full_name ASC";

$stmt = $conn->prepare($sql);

// Bind parameters if filters are active
if (!empty($search) && !empty($department)) {
    $search_param = "%$search%";
    $stmt->bind_param("sss", $search_param, $search_param, $department);
} elseif (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("ss", $search_param, $search_param);
} elseif (!empty($department)) {
    $stmt->bind_param("s", $department);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Doctors - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100">
    <!-- Navbar -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <i class="fas fa-hospital text-blue-600 text-2xl mr-2"></i>
                        <span class="text-xl font-bold text-gray-800">VitalHealth</span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-gray-600 hover:text-gray-900">Home</a>
                    <a href="index.php" class="text-blue-600 font-medium">Doctors</a>
                    <a href="../login.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Login</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Our Doctors</h1>

            <?php if (isset($_GET['error']) && $_GET['error'] === 'doctor_not_found'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
                    <span class="block sm:inline">Doctor not found.</span>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Search by name or department"
                                   class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <select name="department"
                                class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>"
                                        <?php echo $department === $dept ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Doctors Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if ($result->num_rows === 0): ?>
                    <div class="col-span-full text-center py-8">
                        <i class="fas fa-user-md text-gray-400 text-5xl mb-4"></i>
                        <p class="text-gray-500 text-lg">No doctors found matching your criteria.</p>
                    </div>
                <?php else: ?>
                    <?php while ($doctor = $result->fetch_assoc()): ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition hover:scale-105">
                            <div class="p-6">
                                <div class="flex items-center gap-4 mb-4">
                                    <div class="bg-blue-100 p-3 rounded-full">
                                        <i class="fas fa-user-md text-3xl text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-800">Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></h2>
                                        <p class="text-blue-600"><?php echo htmlspecialchars($doctor['department']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="space-y-2 mb-4">
                                    <p class="text-gray-600">
                                        <i class="fas fa-phone-alt text-blue-500 mr-2"></i>
                                        <?php echo htmlspecialchars($doctor['contact_number']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <i class="fas fa-envelope text-blue-500 mr-2"></i>
                                        <?php echo htmlspecialchars($doctor['email']); ?>
                                    </p>
                                </div>

                                <a href="view_doctor.php?id=<?php echo $doctor['doctor_id']; ?>" 
                                   class="block w-full text-center bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                                    View Schedule & Book
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 