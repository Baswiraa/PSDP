<?php
require_once 'functions.php';
check_login();
$current_role = get_user_role();
?>
<nav class="bg-gray-800 p-4 shadow-md">
    <div class="container mx-auto flex justify-between items-center">
        <a class="text-white text-xl font-bold" href="#">SCM Konveksi Kain</a>
        <div class="flex items-center space-x-4">
            <span class="text-gray-300 text-sm">
                Selamat datang, <span class="font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span> (<?php echo ucfirst($current_role); ?>)
            </span>
            <a class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded-md transition duration-300" href="auth/logout.php">Logout</a>
        </div>
    </div>
</nav>