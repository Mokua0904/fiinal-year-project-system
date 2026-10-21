<?php
session_start();
require_once 'config/database.php';

$receipt_no = $_GET['code'] ?? '';
$order = null;

if ($receipt_no) {
    // Fetch from online_orders using the exact columns from your screenshot
    $stmt = $pdo->prepare("SELECT * FROM online_orders WHERE receipt_no = ?");
    $stmt->execute([$receipt_no]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Clear the cart now that the order is placed
    if ($order) {
        unset($_SESSION['cart']); 
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt | Eckose Techs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-4 font-sans flex items-center justify-center min-h-screen">

    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl overflow-hidden">
        
        <!-- Header -->
        <div class="bg-[#1A1F2B] p-6 text-center text-white">
            <div class="w-16 h-16 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-3xl"></i>
            </div>
            <h1 class="text-2xl font-bold">Order Confirmed</h1>
            <p class="text-gray-400 text-sm mt-1">Thank you for shopping with Eckose!</p>
        </div>

        <?php if($order): ?>
        <!-- Receipt Body -->
        <div class="p-6">
            <div class="text-center border-b border-dashed border-gray-300 pb-4 mb-4">
                <p class="text-xs text-gray-500 uppercase tracking-widest mb-1">Collection Code</p>
                <h2 class="text-3xl font-black text-orange-500"><?= htmlspecialchars($order['receipt_no']) ?></h2>
                <p class="text-sm text-gray-500 mt-2">Show this code at the store to collect your items.</p>
            </div>

            <div class="mb-4 text-sm space-y-1">
                <p><span class="text-gray-500">Name:</span> <span class="font-bold"><?= htmlspecialchars($order['customer_name']) ?></span></p>
                <p><span class="text-gray-500">Phone:</span> <span class="font-bold"><?= htmlspecialchars($order['phone']) ?></span></p>
                <p><span class="text-gray-500">Date:</span> <span class="font-bold"><?= htmlspecialchars($order['date']) ?></span></p>
            </div>

            <div class="border-t border-dashed border-gray-300 pt-4 flex justify-between items-center">
                <span class="font-bold text-lg text-gray-800">Total Paid:</span>
                <span class="font-black text-xl text-[#1A1F2B]">Ksh <?= number_format($order['amount'], 2) ?></span>
            </div>
        </div>

        <div class="bg-gray-50 p-6 border-t border-gray-200">
            <button onclick="window.print()" class="w-full bg-[#1A1F2B] hover:bg-black text-white font-bold py-3 rounded-xl transition">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
        </div>
        <?php else: ?>
            <div class="p-6 text-center text-red-500 font-bold">Order not found.</div>
        <?php endif; ?>
    </div>

</body>
</html>