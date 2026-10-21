<?php
session_start();

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- 1. ADD TO CART ---
    if ($action === 'add') {
        $id = $_POST['product_id'];
        $qty = (int)$_POST['quantity'];
        
        // If item already in cart, increase quantity
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] += $qty;
        } else {
            $_SESSION['cart'][$id] = $qty;
        }
        header("Location: cart.php");
        exit();
    }

    // --- 2. UPDATE QUANTITY ---
    if ($action === 'update') {
        $id = $_POST['product_id'];
        $qty = (int)$_POST['quantity'];
        
        if ($qty > 0) {
            $_SESSION['cart'][$id] = $qty;
        } else {
            unset($_SESSION['cart'][$id]); // Remove if 0
        }
        header("Location: cart.php");
        exit();
    }

    // --- 3. REMOVE ITEM ---
    if ($action === 'remove') {
        $id = $_POST['product_id'];
        unset($_SESSION['cart'][$id]);
        header("Location: cart.php");
        exit();
    }
}
header("Location: index.php");
?>