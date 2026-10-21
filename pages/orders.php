<?php
// eckose_pos/pages/orders.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Fetch only PENDING orders (Reverted to 'orders' table)
$stmt = $pdo->query("SELECT * FROM orders WHERE order_status = 'Pending' OR order_status IS NULL ORDER BY created_at DESC");
$orders = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">

    <div class="mb-8 border-b border-gray-700 pb-4 flex justify-between items-end">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-wide">Pending Orders</h2>
            <p class="text-gray-400 mt-1">Click 'Process in POS' to ring up the order.</p>
        </div>
        <div class="text-sm font-bold bg-orange-900 text-orange-300 px-4 py-2 rounded-lg border border-orange-700">
            Pending: <?= count($orders) ?>
        </div>
    </div>

    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Receipt No</th>
                        <th class="px-6 py-4">Customer Name</th>
                        <th class="px-6 py-4">Phone</th>
                        <th class="px-6 py-4">Date</th>
                        <th class="px-6 py-4">Amount</th>
                        <th class="px-6 py-4 text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($orders)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-check-circle text-5xl mb-3 text-green-500 opacity-50"></i>
                                <p class="text-lg">All caught up!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($orders as $order): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition">
                                <td class="px-6 py-4 font-bold text-orange-400 text-lg"><?= htmlspecialchars($order['receipt_no']) ?></td>
                                <td class="px-6 py-4 font-semibold text-white"><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td class="px-6 py-4 font-mono text-gray-300"><?= htmlspecialchars($order['customer_phone']) ?></td>
                                <td class="px-6 py-4 text-xs"><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                <td class="px-6 py-4 font-bold text-white">Ksh <?= number_format($order['total_amount'], 2) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <a href="pos.php?online_order_id=<?= $order['id'] ?>" class="bg-purple-600 hover:bg-purple-500 text-white text-sm font-bold py-2 px-4 rounded-lg shadow transition flex items-center justify-center w-full">
                                        <i class="fas fa-cash-register mr-2"></i> Process in POS
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