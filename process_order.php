<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['cart'])) {
    
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'Mpesa';
    $delivery_fee = isset($_POST['delivery_fee']) ? (float)$_POST['delivery_fee'] : 0;
    
    $total_amount = 0;
    $items_to_process = [];

    // 1. Extract the REAL product ID from the combined cart key
    foreach ($_SESSION['cart'] as $cart_key => $item) {
        $parts = explode('_', $cart_key);
        $real_product_id = (int)$parts[0]; 
        $qty = (int)$item['qty'];

        $stmt = $pdo->prepare("SELECT id, selling_price, stock_quantity FROM products WHERE id = ?");
        $stmt->execute([$real_product_id]);
        $product = $stmt->fetch();

        if ($product) {
            $item_total = $product['selling_price'] * $qty;
            $total_amount += $item_total;

            $items_to_process[] = [
                'product_id' => $product['id'],
                'qty' => $qty,
                'price' => $product['selling_price'],
                'new_stock' => $product['stock_quantity'] - $qty
            ];
        }
    }

    $final_total = $total_amount + $delivery_fee;
    $receipt_no = "ECK-" . strtoupper(substr(uniqid(), -6)); 

    // Trigger M-Pesa (Assuming you have your mpesa_api.php ready)
    if ($payment_method === 'Mpesa') {
        require_once 'mpesa_api.php';
        $clean_phone = preg_replace('/[^0-9]/', '', $customer_phone);
        if (strlen($clean_phone) == 10 && strpos($clean_phone, '0') === 0) {
            $clean_phone = '254' . substr($clean_phone, 1); 
        }
        
        $stk_result = initiateSTKPush($clean_phone, $final_total, $receipt_no, 'Eckose Techs');
        
        if (!$stk_result['success']) {
            // Uncomment the line below if you want to bypass API errors during presentation
            // $stk_result['success'] = true; 
            die("M-Pesa Error: " . $stk_result['error']);
        }
    }

    // ==========================================
    // SAVE TO DATABASE (Using Transactions)
    // ==========================================
    try {
        $pdo->beginTransaction();

        // 1. Save Order Header
        $insert = $pdo->prepare("INSERT INTO orders (receipt_no, customer_name, customer_phone, total_amount, payment_status) VALUES (?, ?, ?, ?, 'Paid')");
        $insert->execute([$receipt_no, $customer_name, $customer_phone, $final_total]);
        
        // Get the ID of the order we just created
        $order_id = $pdo->lastInsertId();

        // 2. Save Order Items & Deduct Stock
        foreach ($items_to_process as $item) {
            // Save Item
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['qty'], $item['price']]);

            // Deduct Stock
            $update = $pdo->prepare("UPDATE products SET stock_quantity = ? WHERE id = ?");
            $update->execute([$item['new_stock'], $item['product_id']]);
        }

        $pdo->commit();
        unset($_SESSION['cart']);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }

    // ==========================================
    // SHOW RECEIPT
    // ==========================================
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Receipt - Eckose Techs</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            @media print {
                .no-print { display: none !important; }
                body { background: white; }
            }
        </style>
    </head>
    <body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
        
        <div class="bg-white p-8 rounded-xl shadow-2xl max-w-sm w-full border-t-8 border-orange-500">
            <div class="text-center mb-6">
                <i class="fas fa-check-circle text-5xl text-green-500 mb-3"></i>
                <h2 class="text-2xl font-bold text-gray-800">Payment Successful</h2>
                <p class="text-sm text-gray-500">Show this receipt at the counter.</p>
            </div>

            <div class="border-t border-b border-dashed border-gray-300 py-4 mb-4 space-y-3 text-sm">
                <div class="flex justify-between"><span class="text-gray-500">Receipt No:</span> <span class="font-bold"><?= $receipt_no ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Customer:</span> <span class="font-semibold"><?= htmlspecialchars($customer_name) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Phone:</span> <span><?= htmlspecialchars($customer_phone) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Payment:</span> <span class="font-bold uppercase"><?= htmlspecialchars($payment_method) ?></span></div>
                <div class="flex justify-between"><span class="text-gray-500">Delivery:</span> <span><?= $delivery_fee > 0 ? 'Ksh ' . number_format($delivery_fee, 2) : 'Store Pickup' ?></span></div>
            </div>

            <div class="flex justify-between items-center text-lg font-extrabold mb-6">
                <span>Total Paid:</span>
                <span class="text-green-600">Ksh <?= number_format($final_total, 2) ?></span>
            </div>

            <button onclick="window.print()" class="no-print w-full bg-gray-800 text-white font-bold py-3 rounded-lg hover:bg-black transition flex justify-center items-center">
                <i class="fas fa-print mr-2"></i> Print Receipt
            </button>
            
            <a href="index.php" class="no-print block text-center text-sm text-blue-500 mt-4 hover:underline">Return to Home</a>
        </div>

    </body>
    </html>
    <?php
} else {
    header("Location: index.php");
    exit();
}
?>