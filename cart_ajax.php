<?php
// cart_ajax.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// If cart doesn't exist, create it
if (!isset($_SESSION['cart'])) { 
    $_SESSION['cart'] = []; 
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD ITEM
    if (isset($_POST['action']) && $_POST['action'] === 'add_ajax') {
        $id = (int)$_POST['product_id'];
        $qty = (int)$_POST['quantity'];
        $spec = isset($_POST['spec']) ? trim($_POST['spec']) : 'Standard';
        $custom_price = isset($_POST['custom_price']) ? (float)$_POST['custom_price'] : 0;

        $cart_key = $id . '_' . md5($spec);

        if (isset($_SESSION['cart'][$cart_key])) {
            $_SESSION['cart'][$cart_key]['qty'] += $qty;
        } else {
            $_SESSION['cart'][$cart_key] = [
                'id' => $id,
                'qty' => $qty,
                'spec' => $spec,
                'custom_price' => $custom_price
            ];
        }
    }

    // 2. REMOVE ITEM
    if (isset($_POST['action']) && $_POST['action'] === 'remove_ajax') {
        $key = $_POST['cart_key'];
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }
    }

    // 3. BUILD RESPONSE
    $cart_items = [];
    $total_price = 0;
    $total_count = 0;

    foreach ($_SESSION['cart'] as $key => $item) {
        $stmt = $pdo->prepare("SELECT product_name, selling_price FROM products WHERE id = ?");
        $stmt->execute([$item['id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $price = ($item['custom_price'] > 0) ? $item['custom_price'] : $product['selling_price'];
            $subtotal = $price * $item['qty'];
            
            $total_price += $subtotal;
            $total_count += $item['qty'];

            $cart_items[] = [
                'key' => $key,
                'name' => $product['product_name'],
                'price' => (float)$price,        // Sending raw numbers to JS
                'qty' => $item['qty'],
                'spec' => $item['spec'],
                'subtotal' => (float)$subtotal   // Sending raw numbers to JS
            ];
        }
    }

    echo json_encode([
        'success' => true, 
        'cart' => $cart_items, 
        'count' => $total_count,
        'total_price' => (float)$total_price // Added total_price
    ]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
exit;
?>