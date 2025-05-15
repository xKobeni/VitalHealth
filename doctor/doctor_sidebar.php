<?php
// Get doctor's name if not already set
if (!isset($name)) {
    $name = getDoctorName($conn, $_SESSION['userid']);
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="bg-green-400 w-64 fixed h-screen overflow-y-auto">
    <div class="flex items-center text-white p-4 ">
        <i class="fas fa-user-circle text-3xl"></i>
        <div class="ml-3">
            <p class="text-base font-semibold">Dr. <?= htmlspecialchars($name ?? 'Name') ?></p>
            <p class="text-sm">Doctor</p>
        </div>
    </div>
    <hr class="border-white/20">
    <ul class="mt-3 text-white text-lg p-1">
        <li class="mb-2">
            <a href="doctordashboard.php" 
               class="flex items-center gap-x-3 px-4 py-2 rounded-lg transition-colors <?php echo $current_page === 'doctordashboard.php' ? 'bg-white/10' : 'hover:bg-white/10'; ?>">
                <i class="fas fa-home w-6"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="mb-2">
            <a href="doctorschedule.php" 
               class="flex items-center gap-x-3 px-4 py-2 rounded-lg transition-colors <?php echo $current_page === 'doctorschedule.php' ? 'bg-white/10' : 'hover:bg-white/10'; ?>">
                <i class="fas fa-calendar-alt w-6"></i>
                <span>Schedules</span>
            </a>
        </li>
        <li class="mb-2">
            <a href="appointments.php" 
               class="flex items-center gap-x-3 px-4 py-2 rounded-lg transition-colors <?php echo $current_page === 'appointments.php' ? 'bg-white/10' : 'hover:bg-white/10'; ?>">
                <i class="fas fa-calendar-check w-6"></i>
                <span>Appointments</span>
            </a>
        </li>
        <li class="mb-2">
            <a href="medicalhistory.php" 
               class="flex items-center gap-x-3 px-4 py-2 rounded-lg transition-colors <?php echo $current_page === 'medicalhistory.php' ? 'bg-white/10' : 'hover:bg-white/10'; ?>">
                <i class="fas fa-book-medical w-6"></i>
                <span>Medical History</span>
            </a>
        </li>
        <li class="mb-2">
            <a href="profile.php" 
               class="flex items-center gap-x-3 px-4 py-2 rounded-lg transition-colors <?php echo $current_page === 'profile.php' ? 'bg-white/10' : 'hover:bg-white/10'; ?>">
                <i class="fas fa-user-cog w-6"></i>
                <span>Profile</span>
            </a>
        </li>
        <li class="mt-8">
            <a href="../logout.php" 
               class="flex items-center gap-x-3 px-4 py-2 rounded-lg transition-colors text-red-100 hover:bg-white/10">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</div> 