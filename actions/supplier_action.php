<?php
// eckose_pos/actions/supplier_action.php
session_start();
require_once '../config/database.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Collect all variables
    $id = $_POST['id'] ?? null;
    $company = trim($_POST['supplier_name'] ?? ''); // Changed to match your DB
    $code = trim($_POST['supplier_code'] ?? '');
    $contact = trim($_POST['contact_person'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $lead_time = (int)($_POST['lead_time'] ?? 0);
    $status = $_POST['status'] ?? 'Active';

    try {
        if ($action === 'add') {
            // Removed 'email' and changed 'company_name' to 'supplier_name'
            $stmt = $pdo->prepare("INSERT INTO suppliers (supplier_name, supplier_code, contact_person, phone, address, lead_time, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$company, $code, $contact, $phone, $address, $lead_time, $status]);
            $msg = "Supplier added successfully!";
        } 
        elseif ($action === 'edit') {
            // Removed 'email' and changed 'company_name' to 'supplier_name'
            $stmt = $pdo->prepare("UPDATE suppliers SET supplier_name=?, supplier_code=?, contact_person=?, phone=?, address=?, lead_time=?, status=? WHERE id=?");
            $stmt->execute([$company, $code, $contact, $phone, $address, $lead_time, $status, $id]);
            $msg = "Supplier updated successfully!";
        } 
        elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id=?");
            $stmt->execute([$id]);
            $msg = "Supplier deleted successfully!";
        }

        // Redirect back with success message
        header("Location: ../pages/suppliers.php?msg=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        // Handle foreign key constraint
        if ($e->getCode() == 23000) {
            die("Error: Cannot delete this supplier because they are linked to existing products or purchases.");
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/suppliers.php");
    exit();
}
?>