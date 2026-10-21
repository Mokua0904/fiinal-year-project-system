<?php
// eckose_pos/includes/sidebar.php
$role = $_SESSION['role'] ?? 'Cashier';
$current_page = basename($_SERVER['PHP_SELF']); 

// Check for low stock alerts (Admin only)
$low_stock_count = 0;
if ($role === 'Admin' && isset($pdo)) {
    $stmt_alerts = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level");
    $low_stock_count = $stmt_alerts->fetchColumn();
}
?>

<!-- LEFT SIDEBAR -->
<aside class="bg-[#1A1F2B] text-white w-64 flex-shrink-0 flex flex-col transition-all duration-300 border-r border-gray-800 z-50">
    <div class="h-16 flex items-center px-6 border-b border-gray-800">
        <h2 class="text-xl font-bold text-white tracking-widest"><i class="fas fa-bolt text-orange-500 mr-2"></i>ECKOSE</h2>
    </div>
    
    <nav class="flex-1 px-4 py-6 space-y-2 overflow-y-auto custom-scrollbar">
        <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'dashboard.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-tachometer-alt w-6"></i> Dashboard
        </a>
        <a href="pos.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'pos.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-cash-register w-6"></i> Point of Sale
        </a>
        <a href="orders.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'orders.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-globe w-6"></i> Online Orders
        </a>

        <?php if ($role === 'Admin'): ?>
        <div class="pt-4 pb-2">
            <p class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider">Management</p>
        </div>
        
        <a href="alerts.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'alerts.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-bell w-6"></i> Alerts
            <?php if($low_stock_count > 0): ?>
                <span class="ml-auto bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full shadow-sm"><?= $low_stock_count ?></span>
            <?php endif; ?>
        </a>
        <a href="inventory.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'inventory.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-box w-6"></i> Inventory
        </a>
        <a href="customers.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'customers.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-users w-6"></i> Customers
        </a>
        <a href="suppliers.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'suppliers.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-truck w-6"></i> Suppliers
        </a>
        <a href="branches.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'branches.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-store-alt w-6"></i> Branches
        </a>
        <a href="users.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'users.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-user-shield w-6"></i> System Users
        </a>
        <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg <?= $current_page == 'reports.php' ? 'bg-orange-500 text-white shadow-md' : 'text-gray-400 hover:bg-gray-800 hover:text-white' ?> transition-colors">
            <i class="fas fa-chart-line w-6"></i> Reports
        </a>
        <?php endif; ?>
    </nav>

    <div class="p-4 border-t border-gray-800">
        <a href="../index.php" target="_blank" class="flex items-center justify-center w-full px-4 py-2 text-sm font-bold text-gray-300 bg-gray-800 rounded-lg hover:bg-gray-700 hover:text-white transition-colors border border-gray-700">
            <i class="fas fa-external-link-alt mr-2"></i> View Storefront
        </a>
    </div>
</aside>

<!-- MAIN CONTENT WRAPPER (Pushes right of sidebar) -->
<div class="flex-1 flex flex-col h-screen overflow-hidden">
    
    <!-- TOP HEADER BAR (Appears on every page) -->
    <header class="h-16 bg-[#1A1F2B] border-b border-gray-800 flex items-center justify-between px-6 shrink-0 relative z-40">
        <h1 class="text-xl font-semibold text-white">
            <?= ucfirst(str_replace('.php', '', $current_page)) ?>
        </h1>
        
        <!-- User Profile & Logout Dropdown -->
        <div class="relative flex items-center gap-4">
            <div class="flex items-center gap-3 cursor-pointer" onclick="toggleUserMenu()">
                <div class="text-right hidden md:block">
                    <p class="text-sm font-bold text-white leading-tight"><?= htmlspecialchars($username ?? 'User'); ?></p>
                    <p class="text-xs text-orange-400 font-semibold"><?= htmlspecialchars($role); ?></p>
                </div>
                <!-- Avatar -->
                <div class="w-10 h-10 rounded-full bg-orange-500 flex items-center justify-center text-white font-bold border-2 border-gray-700 hover:border-orange-400 transition">
                    <?= strtoupper(substr($username ?? 'U', 0, 1)) ?>
                </div>
            </div>

            <!-- Dropdown Menu -->
            <div id="userMenu" class="hidden absolute top-14 right-0 w-48 bg-[#242B38] rounded-xl shadow-2xl border border-gray-700 overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-gray-700">
                    <p class="text-sm text-gray-400">Signed in as</p>
                    <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($username ?? 'User'); ?></p>
                </div>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-red-900/30 hover:text-red-300 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- PAGE CONTENT AREA STARTS HERE -->
    <main class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-[#0F131A]">