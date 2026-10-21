<?php
// eckose_pos/config/database.php

// 1. Database Credentials
$host = 'localhost';
$dbname = 'eckose_pos_db'; // Ensure this matches your exact database name in PHPMyAdmin
$username = 'root';        // Default XAMPP username
$password = '';            // Default XAMPP password is empty

try {
    // 2. Create the PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // 3. Set PDO Error Mode to Exception (This helps catch errors instead of showing white screens)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 4. Set default fetch mode to Associative Array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // 5. Set the Timezone to Kenya (EAT)
    // This ensures "Today's Sales" calculates correctly based on local time.
    $pdo->exec("SET time_zone = '+03:00'");

} catch (PDOException $e) {
    // 6. Catch errors and show a clear message
    die("Database Connection failed: " . $e->getMessage());
}
?>