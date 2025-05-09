<?php
// Navbar for Admin Dashboard
// Get admin details from session
$admin_name = $_SESSION['admin_name'] ?? 'Admin User';
$admin_image = $_SESSION['admin_image'] ?? 'https://randomuser.me/api/portraits/women/44.jpg';
?>
<nav class="flex items-center justify-between h-20 px-8 bg-white shadow-sm ml-64">
    <div class="flex items-center gap-4">
        <form action="search.php" method="GET" class="w-full">
            <input type="text" name="query" placeholder="Search..." class="px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-200 w-72" />
        </form>
    </div>
    <div class="flex items-center gap-6">
        <button class="focus:outline-none" id="theme-toggle">
            <span class="material-icons text-gray-400">wb_sunny</span>
        </button>
        <button class="focus:outline-none relative" id="notifications-btn">
            <span class="material-icons text-gray-400">notifications</span>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">3</span>
        </button>
        <div class="flex items-center gap-3">
            <div class="relative">
                <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></span>
                <img src="<?php echo htmlspecialchars($admin_image); ?>" alt="Profile" class="w-10 h-10 rounded-full border-2 border-white shadow" />
            </div>
            <div class="flex flex-col">
                <span class="font-semibold text-gray-700 text-sm"><?php echo htmlspecialchars($admin_name); ?></span>
                <span class="text-xs text-green-500 flex items-center gap-1"><span class="material-icons text-xs">circle</span>Online</span>
            </div>
            <button class="focus:outline-none" id="settings-btn">
                <span class="material-icons text-gray-400">settings</span>
            </button>
        </div>
    </div>
</nav>

<!-- Notifications Dropdown -->
<div id="notifications-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg z-50">
    <div class="p-4">
        <h3 class="text-lg font-semibold mb-2">Notifications</h3>
        <div class="space-y-2">
            <div class="p-2 hover:bg-gray-50 rounded">
                <p class="text-sm">New appointment request from John Doe</p>
                <span class="text-xs text-gray-500">2 minutes ago</span>
            </div>
            <div class="p-2 hover:bg-gray-50 rounded">
                <p class="text-sm">Dr. Smith updated patient records</p>
                <span class="text-xs text-gray-500">1 hour ago</span>
            </div>
            <div class="p-2 hover:bg-gray-50 rounded">
                <p class="text-sm">System maintenance scheduled</p>
                <span class="text-xs text-gray-500">3 hours ago</span>
            </div>
        </div>
    </div>
</div>

<!-- Settings Dropdown -->
<div id="settings-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50">
    <div class="py-2">
        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile Settings</a>
        <a href="account.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Account Settings</a>
        <a href="preferences.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Preferences</a>
        <hr class="my-2">
        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    themeToggle.addEventListener('click', function() {
        document.documentElement.classList.toggle('dark');
    });

    // Notifications dropdown
    const notificationsBtn = document.getElementById('notifications-btn');
    const notificationsDropdown = document.getElementById('notifications-dropdown');
    notificationsBtn.addEventListener('click', function() {
        notificationsDropdown.classList.toggle('hidden');
        settingsDropdown.classList.add('hidden');
    });

    // Settings dropdown
    const settingsBtn = document.getElementById('settings-btn');
    const settingsDropdown = document.getElementById('settings-dropdown');
    settingsBtn.addEventListener('click', function() {
        settingsDropdown.classList.toggle('hidden');
        notificationsDropdown.classList.add('hidden');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#notifications-btn')) {
            notificationsDropdown.classList.add('hidden');
        }
        if (!event.target.closest('#settings-btn')) {
            settingsDropdown.classList.add('hidden');
        }
    });
});
</script>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> 