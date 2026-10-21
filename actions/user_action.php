<?php
// eckose_pos/actions/user_action.php
session_start();
require_once '../config/database.php';

// Security check - Only Admins can manage users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Collect variables
    $id = $_POST['id'] ?? null;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $pin = trim($_POST['pin'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'Cashier';
    $branch_id = !empty($_POST['branch_id']) ? $_POST['branch_id'] : null;
    
    // Checkboxes
    $can_refund = isset($_POST['can_refund']) ? 1 : 0;
    $can_discount = isset($_POST['can_discount']) ? 1 : 0;
    $status = $_POST['status'] ?? 'Active';

    try {
        if ($action === 'add') {
            // Check if username exists
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                die("Error: Username already exists.");
            }

            // Hash password and PIN
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_pin = !empty($pin) ? password_hash($pin, PASSWORD_DEFAULT) : null;

            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, username, password, pin, email, role, branch_id, can_refund, can_discount, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $username, $hashed_password, $hashed_pin, $email, $role, $branch_id, $can_refund, $can_discount, $status]);
            $msg = "User added successfully!";
        } 
        elseif ($action === 'edit') {
            // Prevent editing the main super admin's role/status
            if ($id == 1 && $role !== 'Admin') {
                die("Error: Cannot change the role of the main administrator.");
            }

            // Build query dynamically based on whether password/pin are updated
            if (!empty($password) && !empty($pin)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, username=?, password=?, pin=?, email=?, role=?, branch_id=?, can_refund=?, can_discount=?, status=? WHERE id=?");
                $stmt->execute([$first_name, $last_name, $username, $hashed_password, $hashed_pin, $email, $role, $branch_id, $can_refund, $can_discount, $status, $id]);
            } elseif (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, username=?, password=?, email=?, role=?, branch_id=?, can_refund=?, can_discount=?, status=? WHERE id=?");
                $stmt->execute([$first_name, $last_name, $username, $hashed_password, $email, $role, $branch_id, $can_refund, $can_discount, $status, $id]);
            } elseif (!empty($pin)) {
                $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, username=?, pin=?, email=?, role=?, branch_id=?, can_refund=?, can_discount=?, status=? WHERE id=?");
                $stmt->execute([$first_name, $last_name, $username, $hashed_pin, $email, $role, $branch_id, $can_refund, $can_discount, $status, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET first_name=?, last_name=?, username=?, email=?, role=?, branch_id=?, can_refund=?, can_discount=?, status=? WHERE id=?");
                $stmt->execute([$first_name, $last_name, $username, $email, $role, $branch_id, $can_refund, $can_discount, $status, $id]);
            }
            $msg = "User updated successfully!";
        } 
        elseif ($action === 'delete') {
            if ($id == 1) {
                die("Error: Cannot delete the main system administrator.");
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            $msg = "User deleted successfully!";
        }

        header("Location: ../pages/users.php?msg=" . urlencode($msg));
        exit();

    } catch (PDOException $e) {
        die("Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../pages/users.php");
    exit();
}
?>