<?php
// Navbar for Admin Dashboard
// Get admin details from session
$full_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<nav class="flex items-center justify-between h-20 px-8 bg-white shadow-sm ml-64">
    <div class="flex items-center gap-4">
        <form action="search.php" method="GET" class="w-full">
            <input type="text" name="query" placeholder="Search..." class="px-4 py-2 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-200 w-72" />
        </form>
    </div>
    <div class="flex items-center gap-6">
        <div class="flex items-center gap-3">
            <div class="flex flex-col">
                <span class="font-semibold text-gray-700 text-sm"><?php echo htmlspecialchars($full_name); ?></span>
                <span class="text-xs text-green-500 flex items-center gap-1"><span class="material-icons text-xs">circle</span>Online</span>
            </div>
            <button class="focus:outline-none" id="settings-btn">
                <span class="material-icons text-gray-400">settings</span>
            </button>
        </div>
    </div>
</nav>

<!-- Settings Dropdown -->
<div id="settings-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50">
    <div class="py-2">
        <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
        <hr class="my-2">
        <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Logout</a>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Settings dropdown
    const settingsBtn = document.getElementById('settings-btn');
    const settingsDropdown = document.getElementById('settings-dropdown');
    
    settingsBtn.addEventListener('click', function() {
        settingsDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('#settings-btn')) {
            settingsDropdown.classList.add('hidden');
        }
    });
});
</script>

<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> 