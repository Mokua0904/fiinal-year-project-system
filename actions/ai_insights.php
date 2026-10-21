<?php
// eckose_pos/actions/ai_insights.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // 1. Gather Business Data to evaluate
    $stmt_sales = $pdo->query("SELECT SUM(total_amount) FROM sales WHERE DATE(created_at) = CURDATE()");
    $todays_sales = $stmt_sales->fetchColumn() ?: 0;

    $stmt_stock = $pdo->query("SELECT product_name FROM products WHERE stock_quantity <= min_stock_level LIMIT 5");
    $low_stock_items = $stmt_stock->fetchAll(PDO::FETCH_COLUMN);
    $low_stock_string = empty($low_stock_items) ? "None" : implode(", ", $low_stock_items);

    // 2. SIMULATED AI RESPONSE (Bypassing OpenAI for testing)
    // We create a fake, dynamic response based on your actual stats
    
    if ($todays_sales > 0) {
        $ai_message = "Great job today! You've already made Ksh " . number_format($todays_sales) . " in sales. Since your low stock items include: " . $low_stock_string . ", I highly recommend contacting your suppliers immediately to ensure you don't miss out on future revenue.";
    } else {
        $ai_message = "It looks like sales are a bit slow today. Consider running a quick flash sale or social media promotion on your best-selling electronics! Also, don't forget to restock: " . $low_stock_string . ".";
    }

    // 3. Send the success response back to the dashboard widget
    echo json_encode(['success' => true, 'insight' => $ai_message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>