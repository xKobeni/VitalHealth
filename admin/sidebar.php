<?php
// Sidebar for Admin Dashboard
?>
<aside class="w-64 h-screen bg-white shadow-md flex flex-col justify-between fixed top-0">
    <div>
        <div class="flex items-center gap-2 px-6 py-6">
            <span class="font-bold text-xl text-blue-700">VitalHealth</span>
        </div>
        <nav class="mt-4">
            <ul class="space-y-2">
                <li><a href="dashboard.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">dashboard</span>Dashboard</a></li>
                <li><a href="profile.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">person</span>Your Account</a></li>
                <li class="mt-4 px-6 text-xs text-gray-400 uppercase">Applications</li>
                <li><a href="doctors.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">local_hospital</span>Doctor</a>
                </li>
                <li><a href="patients.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">people</span>Patient</a></li>
                <li><a href="schedule.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">event</span>Schedule</a></li>
                <li><a href="appointments.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">calendar_today</span>Appointment</a></li>
                <li><a href="reports.php" class="flex items-center px-6 py-2 text-gray-700 hover:bg-blue-50 rounded transition"><span class="material-icons mr-3">assessment</span>Report</a></li>
                <li class="mt-4 px-6 text-xs text-gray-400 uppercase">Others</li>
                <li><a href="logout.php" class="flex items-center px-6 py-2 text-red-600 hover:bg-red-50 rounded transition"><span class="material-icons mr-3">logout</span>Log Out</a></li>
            </ul>
        </nav>
    </div>
</aside>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> 