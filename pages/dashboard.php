<?php
// eckose_pos/pages/dashboard.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../includes/auth_check.php';
require_once '../config/database.php';

$role = $_SESSION['role'] ?? 'Cashier';
$user_id = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? 'User';
$current_page = basename($_SERVER['PHP_SELF']); 

// ==========================================
// ADMIN DASHBOARD LOGIC
// ==========================================
if ($role === 'Admin') {
    $stats = ['total_products' => 0, 'low_stock' => 0, 'todays_sales' => 0, 'online_orders' => 0];
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM products");
        $stats['total_products'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock_quantity <= min_stock_level");
        $stats['low_stock'] = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE()");
        $stats['todays_sales'] = $stmt->fetchColumn() ?: 0;

        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending' OR order_status IS NULL");
        $stats['online_orders'] = $stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) {}

    require_once '../includes/header.php';
    require_once '../includes/sidebar.php';
    ?>

    <!-- Admin Dashboard Content (No header or wrappers needed, sidebar handles it) -->
    <div class="mb-8 border-b border-gray-700 pb-4 flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-wide">Command Center</h2>
            <p class="text-gray-400 mt-1">Real-time store performance.</p>
        </div>
        <div class="text-sm text-gray-400 font-mono">
            <i class="far fa-calendar-alt mr-2"></i> <?= date('l, F j, Y') ?>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-5 relative overflow-hidden">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Today's Sales</p>
            <p class="text-3xl font-extrabold text-white">Ksh <?= number_format($stats['todays_sales']) ?></p>
        </div>
        <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-5 relative overflow-hidden">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Total Products</p>
            <p class="text-3xl font-extrabold text-white"><?= number_format($stats['total_products']) ?></p>
        </div>
        <a href="orders.php" class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-5 relative overflow-hidden block hover:border-orange-500 transition">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Pending Orders</p>
            <p class="text-3xl font-extrabold text-orange-400"><?= $stats['online_orders'] ?></p>
        </a>
        <a href="inventory.php" class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-5 relative overflow-hidden block hover:border-red-500 transition">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1">Low Stock Alerts</p>
            <p class="text-3xl font-extrabold text-red-400"><?= $stats['low_stock'] ?></p>
        </a>
    </div>

    <!-- Quick Actions -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-6">
        <h3 class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4">Quick Actions</h3>
        <div class="flex flex-wrap gap-4">
            <a href="pos.php" class="bg-[#0ea5e9] hover:bg-sky-500 text-white font-bold px-6 py-3 rounded-lg shadow transition flex items-center gap-2">
                <i class="fas fa-cash-register"></i> Open POS
            </a>
            <a href="inventory.php" class="bg-[#2D3748] hover:bg-gray-700 border border-gray-600 text-white font-bold px-6 py-3 rounded-lg shadow transition flex items-center gap-2">
                <i class="fas fa-boxes"></i> Inventory
            </a>
        </div>
    </div>

    <?php
    require_once '../includes/footer.php';
    exit(); 
}

// ==========================================
// CASHIER DASHBOARD LOGIC
// ==========================================
$active_shift = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $active_shift = $stmt->fetch();
} catch (PDOException $e) {}

$order_count = 0;
$total_sales = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(id) as order_count, SUM(total_amount) as total_sales FROM sales WHERE user_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$user_id, date('Y-m-d')]);
    $cashier_stats = $stmt->fetch();
    $order_count = $cashier_stats['order_count'] ?? 0;
    $total_sales = $cashier_stats['total_sales'] ?? 0;
} catch (PDOException $e) {}

$avg_basket = $order_count > 0 ? $total_sales / $order_count : 0;

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Cashier Dashboard Content -->
<div class="mb-8 border-b border-gray-700 pb-4">
    <h1 class="text-3xl font-bold text-white tracking-wide">Welcome, <?= htmlspecialchars($username) ?>!</h1>
    <p class="text-gray-400 mt-1">Ready for your shift? Let's get started.</p>
</div>

<?php if (!$active_shift): ?>
    <!-- No Active Shift -->
    <div class="bg-[#242B38] rounded-2xl shadow-lg border border-gray-700 p-12 text-center max-w-xl mx-auto mt-10">
        <div class="w-24 h-24 bg-blue-900 rounded-full flex items-center justify-center mx-auto mb-6 border border-blue-700 shadow-inner">
            <i class="fas fa-cash-register fa-3x text-blue-400"></i>
        </div>
        <h3 class="text-2xl font-bold text-white mb-2">Shift Not Started</h3>
        <p class="text-gray-400 mb-8">Declare your starting float to open the cash drawer.</p>
        
        <form action="../actions/shift_action.php" method="POST" class="flex gap-2 justify-center">
            <input type="hidden" name="action" value="open_shift">
            <input type="number" step="0.01" name="opening_float" placeholder="Float (Ksh)" required 
                   class="bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 w-48 text-center font-mono focus:border-green-500 outline-none">
            <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-6 rounded-lg shadow-lg transition">
                Start Shift
            </button>
        </form>
    </div>
<?php else: ?>
    <!-- Active Shift -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-[#242B38] p-6 rounded-2xl border border-gray-700 shadow-lg">
            <p class="text-gray-400 text-xs font-bold uppercase mb-1">My Sales Today</p>
            <p class="text-3xl font-extrabold text-white">Ksh <?= number_format($total_sales) ?></p>
        </div>
        <div class="bg-[#242B38] p-6 rounded-2xl border border-gray-700 shadow-lg">
            <p class="text-gray-400 text-xs font-bold uppercase mb-1">My Orders</p>
            <p class="text-3xl font-extrabold text-white"><?= number_format($order_count) ?></p>
        </div>
        <div class="bg-[#242B38] p-6 rounded-2xl border border-gray-700 shadow-lg">
            <p class="text-gray-400 text-xs font-bold uppercase mb-1">Avg. Basket</p>
            <p class="text-3xl font-extrabold text-white">Ksh <?= number_format($avg_basket) ?></p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <a href="pos.php" class="bg-blue-600 hover:bg-blue-500 rounded-2xl p-8 flex flex-col items-center justify-center shadow-lg transition transform hover:-translate-y-1">
            <i class="fas fa-cash-register fa-4x text-white mb-4"></i>
            <h2 class="text-2xl font-bold text-white">Open POS Terminal</h2>
        </a>

        <div class="bg-[#242B38] rounded-2xl p-8 border border-gray-700 shadow-lg flex flex-col justify-between">
            <div>
                <h3 class="text-xl font-bold text-white mb-2"><i class="fas fa-clock text-orange-500 mr-2"></i> Shift Details</h3>
                <p class="text-sm text-gray-400 mb-1">Started: <span class="text-white font-mono"><?= date('h:i A', strtotime($active_shift['start_time'])) ?></span></p>
                <p class="text-sm text-gray-400">Float: <span class="text-white font-mono">Ksh <?= number_format($active_shift['opening_float'], 2) ?></span></p>
            </div>
            
            <form action="../actions/shift_action.php" method="POST" class="mt-6">
                <input type="hidden" name="action" value="close_shift">
                <input type="hidden" name="shift_id" value="<?= $active_shift['id'] ?>">
                <button type="submit" class="w-full bg-red-900 bg-opacity-50 hover:bg-red-800 text-red-300 font-bold py-3 rounded-lg border border-red-700 transition flex justify-center items-center">
                    <i class="fas fa-door-closed mr-2"></i> End Shift
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>