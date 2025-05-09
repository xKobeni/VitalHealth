<?php
require_once 'includes/session.php';
require_once '../config/database.php';
checkAdminSession();

// Get patients with their assigned doctors through appointments
$patients_query = "SELECT p.*, 
                  GROUP_CONCAT(DISTINCT d.full_name) as doctor_names,
                  GROUP_CONCAT(DISTINCT d.contact_number) as doctor_phones
                  FROM patients p 
                  LEFT JOIN appointments a ON p.patient_id = a.patient_id
                  LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
                  GROUP BY p.patient_id
                  ORDER BY p.full_name ASC";
$patients_result = $conn->query($patients_query);

// Get all doctors
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
    <title>Admin Assistance - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    
    <main class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-2xl font-bold text-gray-800 mb-6">Admin Assistance</h1>
            
            <!-- Search Bar -->
            <div class="mb-6">
                <input type="text" id="searchInput" placeholder="Search patients or doctors..." 
                       class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Tabs -->
            <div class="mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <button onclick="showTab('patients')" class="tab-button border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Patients
                        </button>
                        <button onclick="showTab('doctors')" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            Doctors
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Patients Tab -->
            <div id="patientsTab" class="tab-content">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Doctors</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php while($patient = $patients_result->fetch_assoc()): ?>
                            <tr class="patient-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($patient['full_name']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($patient['contact_number']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($patient['doctor_names']): ?>
                                        <?php 
                                        $doctors = explode(',', $patient['doctor_names']);
                                        $phones = explode(',', $patient['doctor_phones']);
                                        foreach($doctors as $index => $doctor): 
                                        ?>
                                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor); ?></div>
                                            <?php if(isset($phones[$index])): ?>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($phones[$index]); ?></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-500">No doctors assigned</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="resetPassword(<?php echo $patient['patient_id']; ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Reset Password</button>
                                    <button onclick="contactPatient(<?php echo $patient['patient_id']; ?>)" class="text-green-600 hover:text-green-900">Contact</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Doctors Tab -->
            <div id="doctorsTab" class="tab-content hidden">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialization</th>
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
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['email']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($doctor['contact_number']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($doctor['specialization']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="contactDoctor(<?php echo $doctor['doctor_id']; ?>)" class="text-blue-600 hover:text-blue-900">Contact</button>
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
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.add('hidden');
            });
            
            // Show selected tab content
            document.getElementById(tabName + 'Tab').classList.remove('hidden');
            
            // Update tab button styles
            document.querySelectorAll('.tab-button').forEach(button => {
                button.classList.remove('border-blue-500', 'text-blue-600');
                button.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Style active tab button
            event.currentTarget.classList.remove('border-transparent', 'text-gray-500');
            event.currentTarget.classList.add('border-blue-500', 'text-blue-600');
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            
            // Search in patients table
            document.querySelectorAll('.patient-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
            
            // Search in doctors table
            document.querySelectorAll('.doctor-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });

        function resetPassword(patientId) {
            if(confirm('Are you sure you want to reset this patient\'s password?')) {
                // Add AJAX call to reset password endpoint
                alert('Password reset functionality will be implemented here');
            }
        }

        function contactPatient(patientId) {
            // Add contact functionality
            alert('Contact patient functionality will be implemented here');
        }

        function contactDoctor(doctorId) {
            // Add contact functionality
            alert('Contact doctor functionality will be implemented here');
        }
    </script>
</body>
</html> 