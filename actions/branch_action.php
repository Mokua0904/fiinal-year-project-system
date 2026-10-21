<?php
// eckose_pos/actions/branch_action.php
session_start();
require_once '../config/database.php';

// Security check - Only Admins can manage branches
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Collect all form variables
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['branch_name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $type = $_POST['branch_type'] ?? 'Branch';
    $phone = trim($_POST['phone'] ?? '');
    $manager = trim($_POST['manager_name'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    
    // Checkbox returns 1 if checked, otherwise 0
    $require_cash = isset($_POST['require_cash_count']) ? 1 : 0;

    try {
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO branches (branch_name, address, branch_type, phone, manager_name, status, require_cash_count) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $address, $type, $phone, $manager, $status, $require_cash]);
            $msg = "Branch added successfully!";
        } 
        elseif ($action === 'edit') {
            $stmt = $pdo->prepare("UPDATE branches SET branch_name=?, address=?, branch_type=?, phone=?, manager_name=?, status=?, require_cash_count=? WHERE id=?");
            $stmt->execute([$name, $address, $type, $phone, $manager, $status, $require_cash, $id]);
            $msg = "Branch updated successfully!";
        } 
        elseif ($action === 'delete') {
            // Prevent deletion of the main HQ (ID 1)
            if ($id == 1) {
                die("Error: Cannot delete the default Main HQ branch.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM branches WHERE id=?");
            $stmt->execute([$id]);
            $msg = "Branch deleted successfully!";
        }

        // Redirect back with a success message
        header("Location: ../pages/branches.php?msg=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        // Handle foreign key constraint (if branch has sales/stock attached to it)
        if ($e->getCode() == 23000) {
            die("Error: Cannot delete this branch because it is linked to existing sales or inventory records.");
        }
        die("Database Error: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file directly via URL
    header("Location: ../pages/branches.php");
    exit();
}
?>