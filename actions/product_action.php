<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') { die("Unauthorized access."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['product_name'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $model_name = trim($_POST['model_name'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '') ?: null;
    $description = trim($_POST['description'] ?? '');
    $specs = trim($_POST['specs'] ?? '') ?: null;
    $cost = $_POST['cost_price'] ?? 0;
    $selling = $_POST['selling_price'] ?? 0;
    $stock = $_POST['stock_quantity'] ?? 0;
    $min_stock = $_POST['min_stock_level'] ?? 5;

    $image_name = 'default.png'; 
    if ($action === 'edit') {
        $stmtOld = $pdo->prepare("SELECT image FROM products WHERE id = ?");
        $stmtOld->execute([$id]);
        if ($old_image = $stmtOld->fetchColumn()) { $image_name = $old_image; }
    }

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/products/';
        $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        if (in_array($file_extension, ['jpg', 'jpeg', 'png', 'webp'])) {
            $new_filename = uniqid('prod_') . '.' . $file_extension;
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $new_filename)) {
                $image_name = $new_filename; 
            }
        }
    }

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO products (product_name, category, model_name, barcode, description, specs, image, cost_price, selling_price, stock_quantity, min_stock_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $model_name, $barcode, $description, $specs, $image_name, $cost, $selling, $stock, $min_stock]);
            $msg = "Product added successfully!";
        } 
        elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE products SET product_name=?, category=?, model_name=?, barcode=?, description=?, specs=?, image=?, cost_price=?, selling_price=?, stock_quantity=?, min_stock_level=? WHERE id=?");
            $stmt->execute([$name, $category, $model_name, $barcode, $description, $specs, $image_name, $cost, $selling, $stock, $min_stock, $id]);
            $msg = "Product updated successfully!";
        } 
        elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id=?");
            $stmt->execute([$id]);
            $msg = "Product deleted successfully!";
        }
        header("Location: ../pages/inventory.php?msg=" . urlencode($msg));
        exit();
    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
}
?>