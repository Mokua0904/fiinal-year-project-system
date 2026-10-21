<?php
session_start();
require_once 'config/database.php';

$cart_items = [];
$total_price = 0;

// If cart is not empty, fetch the product details from the DB
if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $stmt = $pdo->query("SELECT id, product_name, selling_price, image FROM products WHERE id IN ($ids)");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $qty = $_SESSION['cart'][$product['id']];
        $subtotal = $product['selling_price'] * $qty;
        $total_price += $subtotal;
        
        $product['qty'] = $qty;
        $product['subtotal'] = $subtotal;
        $cart_items[] = $product;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Cart - Eckose Techs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#F7F7F7]">
    <nav class="bg-[#1A1F2B] text-white px-8 py-4 flex justify-between items-center">
        <a href="index.php" class="text-xl font-bold tracking-widest">ECKOSE</a>
        <a href="index.php" class="text-sm hover:text-orange-400"><i class="fas fa-arrow-left mr-1"></i> Continue Shopping</a>
    </nav>

    <div class="max-w-4xl mx-auto py-12 px-4">
        <h1 class="text-3xl font-extrabold mb-8">Shopping Cart</h1>

        <?php if(empty($cart_items)): ?>
            <div class="bg-white p-10 rounded-xl text-center shadow-sm">
                <i class="fas fa-shopping-cart text-5xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">Your cart is empty.</p>
                <a href="index.php" class="inline-block mt-4 bg-orange-500 text-white px-6 py-2 rounded-lg">Go Shop</a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-4 text-gray-500 font-semibold">Product</th>
                            <th class="p-4 text-gray-500 font-semibold text-center">Qty</th>
                            <th class="p-4 text-gray-500 font-semibold text-right">Subtotal</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cart_items as $item): ?>
                            <tr class="border-b">
                                <td class="p-4 flex items-center gap-4">
                                    <img src="assets/images/products/<?= htmlspecialchars($item['image'] ?? 'default.png') ?>" class="w-16 h-16 object-contain bg-gray-50 rounded">
                                    <div>
                                        <p class="font-bold text-gray-800"><?= htmlspecialchars($item['product_name']) ?></p>
                                        <p class="text-sm text-gray-500">Ksh <?= number_format($item['selling_price'], 2) ?></p>
                                    </div>
                                </td>
                                <td class="p-4 text-center">
                                    <!-- Form to update qty -->
                                    <form action="cart_action.php" method="POST" class="flex items-center justify-center gap-2">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['qty'] ?>" min="1" onchange="this.form.submit()" class="w-16 border-gray-300 rounded p-1 text-center border">
                                    </form>
                                </td>
                                <td class="p-4 text-right font-bold text-gray-800">
                                    Ksh <?= number_format($item['subtotal'], 2) ?>
                                </td>
                                <td class="p-4 text-center">
                                    <!-- Form to remove item -->
                                    <form action="cart_action.php" method="POST">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Checkout Footer -->
                <div class="p-6 bg-gray-50 flex justify-between items-center border-t">
                    <div>
                        <p class="text-gray-500 text-sm font-semibold uppercase">Total Amount</p>
                        <p class="text-3xl font-extrabold text-orange-500">Ksh <?= number_format($total_price, 2) ?></p>
                    </div>
                    <a href="checkout.php" class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-8 rounded-xl shadow-lg transition text-lg">
                        Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>