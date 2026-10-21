<?php
// eckose_pos/actions/shift_action.php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_SESSION['user_id'];

    if ($action === 'open_shift') {
        $opening_float = (float)$_POST['opening_float'];
        
        try {
            // Check if there's already an open shift (just in case)
            $stmt = $pdo->prepare("SELECT id FROM shifts WHERE user_id = ? AND status = 'open'");
            $stmt->execute([$user_id]);
            if ($stmt->fetch()) {
                // Shift already open, just redirect back
                header("Location: ../pages/dashboard.php");
                exit();
            }

            // Insert new shift
            $stmt = $pdo->prepare("INSERT INTO shifts (user_id, opening_float, status) VALUES (?, ?, 'open')");
            $stmt->execute([$user_id, $opening_float]);
            
            header("Location: ../pages/dashboard.php");
            exit();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    } 
    
    elseif ($action === 'close_shift') {
        $shift_id = $_POST['shift_id'];
        
        try {
            // Get opening float to calculate expected cash
            $stmt = $pdo->prepare("SELECT opening_float FROM shifts WHERE id = ?");
            $stmt->execute([$shift_id]);
            $shift = $stmt->fetch();
            
            if ($shift) {
                // Calculate total cash sales during this shift
                $stmt = $pdo->prepare("
                    SELECT SUM(total_amount) 
                    FROM sales 
                    WHERE user_id = ? 
                    AND payment_method = 'Cash' 
                    AND DATE(created_at) = CURDATE()
                ");
                $stmt->execute([$user_id]);
                $cash_sales = $stmt->fetchColumn() ?: 0;

                // Expected cash in drawer = Opening Float + Cash Sales
                $expected_cash = $shift['opening_float'] + $cash_sales;

                // Update shift to closed
                $stmt = $pdo->prepare("UPDATE shifts SET status = 'closed', end_time = NOW(), expected_cash = ? WHERE id = ?");
                $stmt->execute([$expected_cash, $shift_id]);
            }
            
            header("Location: ../pages/dashboard.php");
            exit();
        } catch (PDOException $e) {
            die("Database Error: " . $e->getMessage());
        }
    }
}

// If accessed directly without POST, redirect to dashboard
header("Location: ../pages/dashboard.php");
exit();
?>