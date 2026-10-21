<?php
// eckose_pos/actions/orders_action.php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_online_order') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $total = (float)$_POST['total'];
    
    // Generate the Receipt Number (e.g., ECK-8X9B2A)
    $receipt_no = "ECK-" . strtoupper(substr(md5(uniqid()), 0, 6));

    try {
        // 1. Insert into the 'orders' table
        $stmt = $pdo->prepare("INSERT INTO orders (receipt_no, customer_name, customer_phone, total_amount, order_status) VALUES (?, ?, ?, ?, 'Pending')");
        $stmt->execute([$receipt_no, $name, $phone, $total]);
        $order_id = $pdo->lastInsertId();

        // 2. Insert items into 'order_items'
        if (isset($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $p_stmt = $pdo->prepare("SELECT selling_price FROM products WHERE id = ?");
                $p_stmt->execute([$item['id']]);
                $product = $p_stmt->fetch(PDO::FETCH_ASSOC);
                
                $price = ($item['custom_price'] > 0) ? $item['custom_price'] : $product['selling_price'];

                // Using 'qty' and 'price' based on your order_action.php structure
                $i_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $i_stmt->execute([$order_id, $item['id'], $item['qty'], $price]);
            }
        }

        echo json_encode(['success' => true, 'order_id' => $order_id]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
    }
}
?>