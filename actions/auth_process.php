<?php
// Turn on errors so we don't get white screens
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
// Go up one folder to find the database config
require_once '../config/database.php';

if (isset($_POST['login_btn'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        header("Location: ../login.php?error=emptyfields");
        exit();
    }

    try {
        // Use PDO prepared statements (Correct syntax for PDO)
        $stmt = $pdo->prepare("SELECT id, username, password, role, branch_id, status FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if the account is suspended (if you added the status column)
            if (isset($user['status']) && $user['status'] === 'Suspended') {
                header("Location: ../login.php?error=suspended");
                exit();
            }

            // Verify the hashed password
            if (password_verify($password, $user['password'])) {
                
                // Password is correct, start the session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['branch_id'] = $user['branch_id'] ?? null;

                // Redirect to the dashboard
                header("Location: ../pages/dashboard.php");
                exit();
            } else {
                // Wrong password
                header("Location: ../login.php?error=wrongpassword");
                exit();
            }
        } else {
            // User not found
            header("Location: ../login.php?error=nouser");
            exit();
        }
    } catch (PDOException $e) {
        // If there's a database error, show it
        die("Database Error: " . $e->getMessage());
    }
} else {
    // If someone tries to access this file directly, send them back to login
    header("Location: ../login.php");
    exit();
}
?>