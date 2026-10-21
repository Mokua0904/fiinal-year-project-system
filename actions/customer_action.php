<?php
// eckose_pos/actions/customer_action.php
session_start();
require_once '../config/database.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Collect variables
    $id = $_POST['id'] ?? null;
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $customer_type = $_POST['customer_type'] ?? 'Walk-in';
    $status = $_POST['status'] ?? 'Active';
    $notes = trim($_POST['notes'] ?? '');

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO customers (full_name, phone, email, address, customer_type, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $phone, $email, $address, $customer_type, $status, $notes]);
            $msg = "Customer added successfully!";
        } 
        elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE customers SET full_name=?, phone=?, email=?, address=?, customer_type=?, status=?, notes=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $email, $address, $customer_type, $status, $notes, $id]);
            $msg = "Customer updated successfully!";
        } 
        elseif ($action === 'delete') {
            // Prevent deletion of Walk-in Customer (ID 1)
            if ($id == 1) {
                die("Error: Cannot delete the default Walk-in customer.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute([$id]);
            $msg = "Customer deleted successfully!";
        }

        // Redirect back to the customers page
        header("Location: ../pages/customers.php?msg=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        // If a customer is linked to a sale, MySQL will prevent deletion
        if ($e->getCode() == 23000) {
            die("Error: Cannot delete this customer because they are linked to existing sales records.");
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/customers.php");
    exit();
}
?>