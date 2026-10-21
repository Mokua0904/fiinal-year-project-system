<?php
// eckose_pos/pages/alerts.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Fetch products that are low OR out of stock
// Added a calculation for "Days of Supply" using a simulated sales velocity for demo
$stmt = $pdo->query("
    SELECT id, product_name, stock_quantity, min_stock_level,
    -- Simulate sales velocity (e.g., selling 2 units per day on average)
    -- In a real system, this would be calculated from the 'sales' or 'sale_items' table
    2 as daily_velocity
    FROM products 
    WHERE stock_quantity <= min_stock_level 
    ORDER BY stock_quantity ASC
");
$alerts = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">
    
    <div class="mb-8 border-b border-gray-700 pb-4 flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-wide">Replenishment Alerts</h2>
            <p class="text-gray-400 mt-1">Actionable items that require restocking.</p>
        </div>
        <div class="text-sm text-gray-400">
            <i class="fas fa-sync-alt mr-1"></i> Auto-updated
        </div>
    </div>

    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700 flex items-center bg-[#2D3748]">
            <i class="fas fa-exclamation-triangle text-orange-500 mr-3 text-lg"></i>
            <h3 class="text-lg font-bold text-white">Low Stock Watchlist</h3>
            <span class="ml-auto text-xs bg-orange-900 text-orange-300 px-2 py-1 rounded-full"><?= count($alerts) ?> Items</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Product Name</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Stock</th>
                        <th class="px-6 py-4 text-center">Days Left</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($alerts)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-900 bg-opacity-50 mb-4 border border-green-700">
                                    <i class="fas fa-check-circle text-2xl text-green-400"></i>
                                </div>
                                <h4 class="text-lg font-bold text-white mb-1">All inventory levels are healthy!</h4>
                                <p class="text-gray-400">No products have reached their minimum stock threshold.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($alerts as $alert): 
                            $isOutOfStock = $alert['stock_quantity'] <= 0;
                            $daysLeft = $alert['daily_velocity'] > 0 ? round($alert['stock_quantity'] / $alert['daily_velocity']) : '∞';
                        ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition-colors">
                                <td class="px-6 py-4 font-bold text-white"><?= htmlspecialchars($alert['product_name']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php if($isOutOfStock): ?>
                                        <span class="bg-red-900 text-red-300 text-xs font-bold px-3 py-1 rounded-full border border-red-700">
                                            <i class="fas fa-times-circle mr-1"></i> Out of Stock
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-yellow-900 text-yellow-300 text-xs font-bold px-3 py-1 rounded-full border border-yellow-700">
                                            <i class="fas fa-exclamation-circle mr-1"></i> Low Stock
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center font-mono font-bold text-lg"><?= $alert['stock_quantity'] ?></td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-semibold <?= $daysLeft < 3 ? 'text-red-400' : 'text-gray-300' ?>">
                                        <?= $daysLeft ?> days
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="inventory.php?search=<?= urlencode($alert['product_name']) ?>" class="inline-block bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold px-4 py-2 rounded-lg transition shadow">
                                        <i class="fas fa-plus-circle mr-1"></i> Restock Now
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>