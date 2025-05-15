<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

$patient_name = getPatientName($conn, $_SESSION['userid']);
$patient_id = getPatientId($conn, $_SESSION['userid']);

// Get patient email
$email_sql = "SELECT u.email FROM patients p JOIN users u ON p.user_id = u.user_id WHERE p.user_id = ?";
$email_stmt = $conn->prepare($email_sql);
$email_stmt->bind_param("i", $_SESSION['userid']);
$email_stmt->execute();
$email_result = $email_stmt->get_result();
$patient_email = ($row = $email_result->fetch_assoc()) ? $row['email'] : '';

// Get all appointments for the patient
$sql = "SELECT a.*, d.full_name as doctor_name, d.department, a.remark as cancellation_remark 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.doctor_id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$appointments = $result->fetch_all(MYSQLI_ASSOC);

// Get all guest appointments for this email
$guest_sql = "SELECT ga.*, d.full_name as doctor_name, d.department, ga.remark as cancellation_remark 
              FROM guest_appointments ga 
              JOIN doctors d ON ga.doctor_id = d.doctor_id 
              WHERE ga.email = ? 
              ORDER BY ga.appointment_date DESC, ga.appointment_time DESC";
$guest_stmt = $conn->prepare($guest_sql);
$guest_stmt->bind_param("s", $patient_email);
$guest_stmt->execute();
$guest_result = $guest_stmt->get_result();
$guest_appointments = $guest_result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - VitalHealth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body class="bg-sky-100 flex">
    <!-- Sidebar -->
    <div class="bg-green-400 w-64 min-h-screen">
        <div class="flex items-center text-white p-4">
            <i class="fas fa-user-circle text-3xl"></i>
            <p class="ml-3 text-lg"><?php echo htmlspecialchars($patient_name); ?></p>
        </div>
        <hr class="border-white">
        <ul class="mt-4">
            <li class="mb-2">
                <a href="patientdashboard.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-home w-6"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="appointments.php" class="flex items-center text-white p-4 bg-green-500">
                    <i class="fas fa-calendar-alt w-6"></i>
                    <span>Appointments</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="medical-history.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-file-medical w-6"></i>
                    <span>Medical History</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="profile.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-user-cog w-6"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="mb-2">
                <a href="logout.php" class="flex items-center text-white p-4 hover:bg-green-500">
                    <i class="fas fa-sign-out-alt w-6"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">My Appointments</h1>
            <a href="booking.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-plus mr-2"></i>Book New Appointment
            </a>
        </div>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search by doctor name..." 
                           class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="w-48">
                    <select id="statusFilter" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Appointments Table -->
        <div class="bg-white rounded-lg shadow">
            <?php if (count($appointments) > 0 || count($guest_appointments) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($appointments as $row): ?>
                                <tr class="appointment-row">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['department']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('F j, Y', strtotime($row['appointment_date'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusClass = '';
                                        switch ($row['status']) {
                                            case 'pending':
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'approved':
                                                $statusClass = 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <a href="cancelappointment.php?id=<?php echo $row['appointment_id']; ?>" 
                                               class="text-red-600 hover:text-red-900"
                                               onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                Cancel
                                            </a>
                                        <?php elseif ($row['status'] === 'cancelled' && !empty($row['cancellation_remark'])): ?>
                                            <button onclick="showCancellationRemark('<?php echo htmlspecialchars($row['cancellation_remark']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                View Remark
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php foreach ($guest_appointments as $row): ?>
                                <tr class="appointment-row bg-yellow-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">Dr. <?php echo htmlspecialchars($row['doctor_name']); ?> <span class="text-xs text-yellow-600">(Guest)</span></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['department']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('F j, Y', strtotime($row['appointment_date'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusClass = '';
                                        switch ($row['status']) {
                                            case 'pending':
                                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                                break;
                                            case 'approved':
                                                $statusClass = 'bg-green-100 text-green-800';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'bg-red-100 text-red-800';
                                                break;
                                            default:
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                        }
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                            <?php echo ucfirst(htmlspecialchars($row['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <span class="text-yellow-700">Guest Booking</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-calendar-times text-5xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500 text-lg">No appointments found.</p>
                    <a href="booking.php" class="mt-4 inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Book an Appointment
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Cancellation Remark Modal -->
    <div id="remarkModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Cancellation Remark</h3>
                <div class="mt-2 px-7 py-3">
                    <p id="remarkText" class="text-sm text-gray-500"></p>
                </div>
                <div class="items-center px-4 py-3">
                    <button id="closeModal" class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search and filter functionality
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const appointmentRows = document.querySelectorAll('.appointment-row');

        function filterAppointments() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusTerm = statusFilter.value.toLowerCase();

            appointmentRows.forEach(row => {
                const doctorName = row.querySelector('td:first-child').textContent.toLowerCase();
                const status = row.querySelector('td:nth-child(4)').textContent.toLowerCase().trim();
                
                const matchesSearch = doctorName.includes(searchTerm);
                const matchesStatus = !statusTerm || status === statusTerm;

                row.style.display = matchesSearch && matchesStatus ? '' : 'none';
            });
        }

        searchInput.addEventListener('input', filterAppointments);
        statusFilter.addEventListener('change', filterAppointments);

        // Modal functionality
        function showCancellationRemark(remark) {
            const modal = document.getElementById('remarkModal');
            const remarkText = document.getElementById('remarkText');
            remarkText.textContent = remark;
            modal.classList.remove('hidden');
        }

        document.getElementById('closeModal').addEventListener('click', function() {
            document.getElementById('remarkModal').classList.add('hidden');
        });

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('remarkModal');
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });
    </script>
</body>
</html>