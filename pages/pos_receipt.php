<?php
// eckose_pos/pages/pos_receipt.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Get the receipt number from the URL
$receipt_no = isset($_GET['receipt']) ? $_GET['receipt'] : '';

if (empty($receipt_no)) {
    die("No receipt number provided.");
}

// Fetch the sale header
$stmt = $pdo->prepare("
    SELECT s.*, u.username as cashier_name, b.branch_name 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.id 
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE s.receipt_no = ?
");
$stmt->execute([$receipt_no]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Receipt not found.");
}

// Fetch the items for this sale
$stmt = $pdo->prepare("
    SELECT si.*, p.product_name 
    FROM sale_items si 
    JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt->execute([$sale['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= htmlspecialchars($receipt_no) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            body { background: white; color: black; }
            .no-print { display: none !important; }
            .receipt-box { box-shadow: none !important; border: none !important; margin: 0; padding: 0; }
        }
        /* Monospace font for that authentic receipt look */
        .receipt-font { font-family: 'Courier New', Courier, monospace; }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4 receipt-font">

    <!-- Receipt Container -->
    <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm receipt-box text-sm text-gray-800">
        
        <!-- Header -->
        <div class="text-center mb-6 border-b border-dashed border-gray-400 pb-4">
            <h2 class="text-2xl font-bold uppercase tracking-widest">Eckose Techs</h2>
            <p>Nairobi CBD, Kenya</p>
            <p>Tel: +254 700 000 000</p>
            <p class="mt-2 text-xs">Branch: <?= htmlspecialchars($sale['branch_name'] ?? 'Main HQ') ?></p>
        </div>

        <!-- Sale Details -->
        <div class="mb-4">
            <div class="flex justify-between"><span>Receipt:</span> <span class="font-bold"><?= htmlspecialchars($sale['receipt_no']) ?></span></div>
            <div class="flex justify-between"><span>Date:</span> <span><?= date('d/m/Y h:i A', strtotime($sale['created_at'])) ?></span></div>
            <div class="flex justify-between"><span>Cashier:</span> <span><?= htmlspecialchars($sale['cashier_name'] ?? 'Admin') ?></span></div>
            <div class="flex justify-between"><span>Payment:</span> <span class="uppercase"><?= htmlspecialchars($sale['payment_method']) ?></span></div>
        </div>

        <!-- Items -->
        <table class="w-full mb-4 border-t border-b border-dashed border-gray-400 py-2">
            <thead>
                <tr class="border-b border-gray-300">
                    <th class="text-left py-1">Item</th>
                    <th class="text-center py-1">Qty</th>
                    <th class="text-right py-1">Price</th>
                    <th class="text-right py-1">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                <tr class="border-b border-gray-100 py-1">
                    <td class="text-left py-1 truncate max-w-[100px]"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="text-center py-1"><?= $item['quantity'] ?></td>
                    <td class="text-right py-1"><?= number_format($item['unit_price'], 0) ?></td>
                    <td class="text-right py-1"><?= number_format($item['subtotal'], 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="mb-6 border-b border-dashed border-gray-400 pb-4">
            <div class="flex justify-between font-bold text-lg mt-2">
                <span>TOTAL:</span>
                <span>Ksh <?= number_format($sale['total_amount'], 2) ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center text-xs mt-6">
            <p class="font-bold">Thank you for shopping with us!</p>
            <p>Goods sold are not returnable after 7 days.</p>
            <p class="mt-2">Powered by Eckose POS</p>
        </div>
    </div>

    <!-- Print & Return Buttons -->
    <div class="fixed bottom-6 left-0 right-0 flex justify-center gap-4 no-print">
        <button onclick="window.print()" class="bg-orange-500 text-white font-bold py-2 px-6 rounded shadow-lg hover:bg-orange-600 transition flex items-center">
            <i class="fas fa-print mr-2"></i> Print Receipt
        </button>
        
        <!-- FIXED: Changed to return to pos.php -->
        <button onclick="window.location.href='pos.php'" class="bg-gray-700 text-white font-bold py-2 px-6 rounded shadow-lg hover:bg-gray-800 transition flex items-center">
            <i class="fas fa-arrow-left mr-2"></i> Return to POS
        </button>
    </div>

</body>
</html>