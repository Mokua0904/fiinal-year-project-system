<?php
// eckose_pos/actions/sale_process.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['cart'])) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

$user_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'] ?? 1; // Default to 1 if not set

// We need to find if the user has an active shift to link to this sale
$stmt = $pdo->prepare("SELECT id FROM shifts WHERE user_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$shift = $stmt->fetch();
$shift_id = $shift ? $shift['id'] : null;

$receipt_no = "POS-" . strtoupper(uniqid());
$payment_method = $data['payment_method'];
$phone = $data['phone'] ?? null;

try {
    // 1. Begin Transaction (Ensures all or nothing is saved)
    $pdo->beginTransaction();

    // 2. Calculate Total
    $total_amount = 0;
    foreach ($data['cart'] as $item) {
        $total_amount += ($item['price'] * $item['qty']);
    }

    // 3. Insert into `sales` table (Including shift_id)
    $stmt = $pdo->prepare("INSERT INTO sales (receipt_no, user_id, shift_id, branch_id, total_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$receipt_no, $user_id, $shift_id, $branch_id, $total_amount, $payment_method]);
    
    // Get the ID of the sale we just created
    $sale_id = $pdo->lastInsertId();

    // 4. Insert into `sale_items` and deduct stock
    foreach ($data['cart'] as $item) {
        $product_id = $item['id'];
        $qty = $item['qty'];
        $price = $item['price'];
        $subtotal = $price * $qty;

        // Save item details
        $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$sale_id, $product_id, $qty, $price, $subtotal]);

        // Deduct stock from products table
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $stmt->execute([$qty, $product_id]);
    }

    // 5. Commit Transaction (Save everything to DB)
    $pdo->commit();

    // Return success response to the JavaScript
    echo json_encode(['success' => true, 'receipt_no' => $receipt_no]);

} catch (Exception $e) {
    // If anything fails, rollback the database changes
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>