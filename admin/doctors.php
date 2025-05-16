<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get all doctors with their user information
$doctors_query = "SELECT d.*, u.email 
                 FROM doctors d 
                 JOIN users u ON d.user_id = u.user_id 
                 ORDER BY d.full_name ASC";
$doctors_result = $conn->query($doctors_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <main class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Doctor Management</h1>
                <button onclick="window.location.href='add_doctor.php'" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
                    <span class="material-icons mr-2">person_add</span>
                    Add New Doctor
                </button>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <input type="text" id="searchInput" placeholder="Search doctors..."
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
            </div>

            <!-- Doctors Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while ($doctor = $doctors_result->fetch_assoc()): ?>
                                <tr class="doctor-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['department']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['contact_number']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($doctor['is_approved'] == 1): ?>
                                            <span class="inline-block px-3 py-1 text-sm font-semibold text-green-700 bg-green-100 rounded-full">Approved</span>
                                        <?php else: ?>
                                            <span class="inline-block px-3 py-1 text-sm font-semibold text-yellow-700 bg-yellow-100 rounded-full">Pending</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewDoctor(<?php echo $doctor['doctor_id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>

                                        <?php if ($doctor['is_active']): ?>
                                            <button onclick="toggleDoctorStatus(<?php echo $doctor['doctor_id']; ?>, 0)" class="text-red-600 hover:text-red-900">Deactivate</button>
                                        <?php else: ?>
                                            <button onclick="toggleDoctorStatus(<?php echo $doctor['doctor_id']; ?>, 1)" class="text-yellow-600 hover:text-yellow-900">Reactivate</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            document.querySelectorAll('.doctor-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        function viewDoctor(doctorId) {
            window.location.href = `view_doctor.php?id=${doctorId}`;
        }

        function editDoctor(doctorId) {
            window.location.href = `edit_doctor.php?id=${doctorId}`;
        }

        function toggleDoctorStatus(doctorId, newStatus) {
            const action = newStatus === 1 ? 'reactivate' : 'deactivate';
            if (confirm(`Are you sure you want to ${action} this doctor?`)) {
                fetch('toggle_doctor_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            doctor_id: doctorId,
                            status: newStatus
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Doctor successfully ${action}d.`);
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            }
        }
    </script>
</body>

</html>