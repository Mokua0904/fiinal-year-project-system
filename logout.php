<?php
// eckose_pos/logout.php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session completely
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>