<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/functions.php';
require_once __DIR__ . '/includes/session.php';

checkPatientSession();

$patient_name = getPatientName($conn, $_SESSION['userid']);
$patient_id = getPatientId($conn, $_SESSION['userid']);

// Get all doctors
$sql = "SELECT d.doctor_id, d.full_name, d.department, d.contact_number, u.email
        FROM doctors d
        JOIN users u ON d.user_id = u.user_id
        ORDER BY d.full_name";
$result = $conn->query($sql);

// Get all unique departments
$dept_sql = "SELECT DISTINCT department FROM doctors ORDER BY department ASC";
$dept_result = $conn->query($dept_sql);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row['department'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - VitalHealth</title>
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
                <a href="appointments.php" class="flex items-center text-white p-4 hover:bg-green-500">
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
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">Our Doctors</h1>
        <div class="bg-white rounded-lg shadow p-6 mb-8 flex flex-col gap-4">
            <div class="flex flex-col md:flex-row md:items-end md:gap-6 gap-4">
                <div class="flex-1">
                    <label class="block text-gray-700 font-medium mb-1">Search</label>
                    <div class="relative">
                        <input type="text" id="searchDoctor" placeholder="Search by name or department" 
                               class="w-full p-3 pl-10 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-search"></i></span>
                    </div>
                </div>
                <div class="flex-1">
                    <label class="block text-gray-700 font-medium mb-1">Department</label>
                    <select id="department" class="w-full p-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex-1 flex items-end">
                    <button type="button" onclick="searchDoctors()" 
                            class="w-full bg-blue-600 text-white font-semibold p-3 rounded-md hover:bg-blue-700 transition-colors">
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="doctorsList">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '
                    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center gap-3">
                        <div class="flex flex-col items-center">
                            <div class="bg-blue-100 rounded-full p-4 mb-2"><i class="fas fa-user-doctor text-3xl text-blue-400"></i></div>
                            <h3 class="font-bold text-xl">Dr. ' . htmlspecialchars($row["full_name"]) . '</h3>
                            <div class="text-sm text-gray-600">
                                <a href="#" class="text-blue-600 hover:underline">' . htmlspecialchars($row["department"]) . '</a>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 w-full mt-2">
                            <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-phone"></i> <span>' . htmlspecialchars($row["contact_number"]) . '</span></div>
                            <div class="flex items-center gap-2 text-gray-700"><i class="fas fa-envelope"></i> <span>' . htmlspecialchars($row["email"]) . '</span></div>
                        </div>
                        <form method="get" action="booked.php" class="w-full mt-4">
                            <input type="hidden" name="doctorid" value="' . htmlspecialchars($row["doctor_id"]) . '">
                            <button type="submit" class="w-full bg-blue-600 text-white font-semibold py-3 rounded-lg hover:bg-blue-700 transition-colors">View Schedule & Book</button>
                        </form>
                    </div>';
                }
            } else {
                echo "<p class='text-center text-gray-500 col-span-3'>No doctors found.</p>";
            }
            ?>
        </div>
    </div>

    <script>
        function searchDoctors() {
            const searchTerm = document.getElementById('searchDoctor').value.toLowerCase();
            const department = document.getElementById('department').value.toLowerCase();
            const doctors = document.querySelectorAll('#doctorsList > div');

            doctors.forEach(doctor => {
                const doctorName = doctor.querySelector('h3').textContent.toLowerCase();
                const doctorDepartment = doctor.querySelector('a').textContent.toLowerCase();
                const matchesSearch = doctorName.includes(searchTerm) || doctorDepartment.includes(searchTerm);
                const matchesDepartment = !department || doctorDepartment === department;
                doctor.style.display = matchesSearch && matchesDepartment ? 'block' : 'none';
            });
        }

        document.getElementById('searchDoctor').addEventListener('input', searchDoctors);
        document.getElementById('department').addEventListener('change', searchDoctors);
    </script>
</body>
</html>