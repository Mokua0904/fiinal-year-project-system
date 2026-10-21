<?php
session_start();
require_once '../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);
$order_id = $data['order_id'] ?? 0;

if ($order_id > 0) {
    $stmt = $pdo->prepare("UPDATE orders SET order_status = 'Collected' WHERE id = ?");
    $stmt->execute([$order_id]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
?>