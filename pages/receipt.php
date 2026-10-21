<?php
// eckose_pos/pages/receipt.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

if (!isset($_GET['receipt_no'])) {
    die("Invalid Receipt Number.");
}

$receipt_no = $_GET['receipt_no'];

// Fetch Sale Data
$stmt = $pdo->prepare("SELECT s.*, u.username as cashier_name 
                       FROM sales s 
                       JOIN users u ON s.user_id = u.id 
                       WHERE s.receipt_no = ?");
$stmt->execute([$receipt_no]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Receipt not found.");
}

// Fetch Sale Items
$stmt_items = $pdo->prepare("SELECT si.*, p.product_name 
                             FROM sale_items si 
                             JOIN products p ON si.product_id = p.id 
                             WHERE si.sale_id = ?");
$stmt_items->execute([$sale['id']]);
$items = $stmt_items->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= htmlspecialchars($receipt_no) ?></title>
    <style>
        /* CSS tailored for an 80mm thermal receipt printer */
        body {
            font-family: 'Courier New', Courier, monospace; /* Monospace looks like a real receipt */
            font-size: 12px;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 10px;
            width: 300px; /* Approx 80mm */
            margin: auto;
        }
        .header { text-align: center; margin-bottom: 15px; }
        .header h2 { margin: 0; font-size: 18px; }
        .header p { margin: 2px 0; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        .meta-data { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        th { text-align: left; border-bottom: 1px dashed #000; padding-bottom: 5px; }
        td { padding: 4px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .totals { margin-top: 10px; font-weight: bold; }
        .totals p { margin: 4px 0; display: flex; justify-content: space-between; }
        .footer { text-align: center; margin-top: 20px; font-size: 11px; }
        
        /* Hide everything else when printing */
        @media print {
            .no-print { display: none; }
            body { width: 100%; padding: 0; }
        }
    </style>
</head>
<body>

    <div class="no-print text-center" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 15px; cursor: pointer; background: #3b82f6; color: white; border: none; border-radius: 5px;">Print Receipt</button>
        <button onclick="window.close()" style="padding: 8px 15px; cursor: pointer; background: #ef4444; color: white; border: none; border-radius: 5px; margin-left: 5px;">Close</button>
    </div>

    <div class="header">
        <h2>ECKOSE TECHS</h2>
        <p>Nairobi CBD / Lamu Branch</p>
        <p>Tel: +254 700 000000</p>
        <p>PIN: P000000000Z</p>
    </div>

    <div class="divider"></div>

    <div class="meta-data">
        <p><strong>Receipt No:</strong> <?= htmlspecialchars($sale['receipt_no']) ?></p>
        <p><strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($sale['created_at'])) ?></p>
        <p><strong>Cashier:</strong> <?= htmlspecialchars($sale['cashier_name']) ?></p>
    </div>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-center">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $item): ?>
            <tr>
                <td style="max-width: 150px;"><?= htmlspecialchars($item['product_name']) ?> <br><small>@ <?= number_format($item['unit_price'], 2) ?></small></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-right"><?= number_format($item['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="divider"></div>

    <div class="totals">
        <p><span>Total Amount:</span> <span>Ksh <?= number_format($sale['total_amount'], 2) ?></span></p>
        <p><span>Payment Method:</span> <span><?= htmlspecialchars($sale['payment_method']) ?></span></p>
    </div>

    <div class="divider"></div>

    <div class="footer">
        <p>*** Thank You for Shopping with Us! ***</p>
        <p>Goods once sold are not returnable.</p>
        <p>System Powered by Eckose Techs</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</body>
</html>