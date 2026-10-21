<?php
// eckose_pos/includes/auth_check.php
session_start();

// If the user_id session is NOT set, redirect them to the login page immediately
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
?>