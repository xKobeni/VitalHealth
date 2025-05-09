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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialization</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while($doctor = $doctors_result->fetch_assoc()): ?>
                            <tr class="doctor-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($doctor['full_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['contact_number']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewDoctor(<?php echo $doctor['doctor_id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">View</button>
                                    <button onclick="editDoctor(<?php echo $doctor['doctor_id']; ?>)" class="text-green-600 hover:text-green-900 mr-3">Edit</button>
                                    <button onclick="deleteDoctor(<?php echo $doctor['doctor_id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
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

        function deleteDoctor(doctorId) {
            if(confirm('Are you sure you want to delete this doctor? This action cannot be undone.')) {
                // Add AJAX call to delete doctor
                alert('Delete functionality will be implemented here');
            }
        }
    </script>
</body>
</html> 