<?php
session_start();
require_once 'config/database.php'; // Adjust path if your config is elsewhere

// Handle Item Removal
if (isset($_GET['remove_key'])) {
    $key = $_GET['remove_key'];
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
    }
    header("Location: checkout.php");
    exit;
}

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: index.php");
    exit;
}

$cart_items = [];
$total_price = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    // Assuming your database has a 'products' table
    $stmt = $pdo->prepare("SELECT product_name, selling_price FROM products WHERE id = ?");
    $stmt->execute([$item['id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($product) {
        $price = ($item['custom_price'] > 0) ? $item['custom_price'] : $product['selling_price'];
        $subtotal = $price * $item['qty'];
        $total_price += $subtotal;

        $cart_items[] = [
            'key' => $key, 
            'name' => $product['product_name'],
            'price' => $price,
            'qty' => $item['qty'],
            'spec' => $item['spec'],
            'subtotal' => $subtotal
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Eckose Techs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .payment-option { transition: all 0.2s ease; cursor: pointer; border: 2px solid #e5e7eb; }
        .payment-option.active { border-color: #22c55e; background-color: #f0fdf4; }
        .sticky-footer { position: fixed; bottom: 0; left: 0; right: 0; z-index: 50; }
        .spinner { border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top: 3px solid #fff; width: 20px; height: 20px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="text-gray-800 pb-24 md:pb-0"> 

    <!-- HEADER WITH BACK BUTTON -->
    <header class="bg-white shadow-sm py-4 px-6 flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-gray-500 hover:text-orange-500 transition bg-gray-100 p-2 rounded-full w-10 h-10 flex items-center justify-center">
                <i class="fas fa-arrow-left"></i>
            </a>
            <a href="index.php" class="flex items-center gap-2">
                <div class="bg-orange-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm">E</div>
                <span class="text-xl font-bold tracking-widest text-gray-900">ECKOSE</span>
            </a>
        </div>
        <div class="text-sm font-semibold text-gray-500 hidden sm:flex items-center gap-2">
            <i class="fas fa-lock text-green-500"></i> Secure Checkout
        </div>
    </header>

    <main class="max-w-6xl mx-auto p-4 md:p-8 flex flex-col md:flex-row gap-8 mt-4">
        
        <div class="w-full md:w-3/5 order-2 md:order-1">
            <form id="checkoutForm" onsubmit="processOrder(event)">
                <!-- Customer Details -->
                <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8 mb-6">
                    <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                        <span class="bg-gray-100 text-gray-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span>
                        Customer Details
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Full Name</label>
                            <div class="relative">
                                <i class="fas fa-user absolute left-4 top-4 text-gray-400"></i>
                                <input type="text" id="customerName" required placeholder="e.g. John Doe" class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-orange-500 outline-none transition font-medium">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">M-Pesa Phone Number</label>
                            <div class="relative">
                                <i class="fas fa-phone absolute left-4 top-4 text-gray-400"></i>
                                <input type="tel" id="customerPhone" required placeholder="07XX XXX XXX" inputmode="numeric" pattern="[0-9]{10}" class="w-full pl-11 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:border-orange-500 outline-none transition font-medium">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8">
                    <h2 class="text-xl font-extrabold text-gray-900 mb-6 flex items-center gap-2">
                        <span class="bg-gray-100 text-gray-600 w-8 h-8 rounded-full flex items-center justify-center text-sm">2</span>
                        Payment Method
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="payment-option active rounded-xl p-4 flex items-center gap-4" onclick="selectPayment('mpesa')">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-xl">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">Lipa na M-Pesa</p>
                            </div>
                            <div class="ml-auto w-5 h-5 rounded-full border-2 border-green-500 flex items-center justify-center bg-green-500" id="radio-mpesa">
                                <i class="fas fa-check text-white text-xs"></i>
                            </div>
                        </div>
                        <div class="payment-option rounded-xl p-4 flex items-center gap-4" onclick="selectPayment('pickup')">
                            <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center text-gray-600 text-xl">
                                <i class="fas fa-store"></i>
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">Pay on Pickup</p>
                            </div>
                            <div class="ml-auto w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center" id="radio-pickup"></div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Order Summary with DELETE BUTTON -->
        <div class="w-full md:w-2/5 order-1 md:order-2">
            <div class="bg-white rounded-2xl shadow-sm p-6 md:p-8 md:sticky md:top-8">
                <h2 class="text-xl font-extrabold text-gray-900 mb-6">Order Summary</h2>
                
                <div class="space-y-4 mb-6 max-h-64 overflow-y-auto pr-2">
                    <?php foreach($cart_items as $item): ?>
                        <div class="flex justify-between items-start border-b border-gray-100 pb-4">
                            <div class="flex-1 pr-4">
                                <p class="font-bold text-gray-900 text-sm leading-tight"><?= htmlspecialchars($item['name']) ?></p>
                                <span class="inline-block mt-1 text-[10px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-bold uppercase"><?= htmlspecialchars($item['spec']) ?></span>
                                <p class="text-xs text-gray-500 mt-1">Qty: <?= $item['qty'] ?> × Ksh <?= number_format($item['price'], 2) ?></p>
                                
                                <!-- DELETE BUTTON -->
                                <a href="checkout.php?remove_key=<?= $item['key'] ?>" class="inline-block mt-2 text-red-500 hover:text-red-700 text-xs font-bold transition">
                                    <i class="fas fa-trash-alt mr-1"></i> Remove
                                </a>
                            </div>
                            <p class="font-bold text-gray-900">Ksh <?= number_format($item['subtotal'], 2) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="space-y-3 text-sm font-medium border-t border-gray-100 pt-4">
                    <div class="flex justify-between items-center pt-3 mt-3 border-t border-gray-200">
                        <span class="text-lg font-bold text-gray-900">Total:</span>
                        <span class="text-2xl font-extrabold text-orange-500">Ksh <?= number_format($total_price, 2) ?></span>
                    </div>
                </div>

                <button type="submit" form="checkoutForm" id="desktopSubmitBtn" class="hidden md:flex w-full mt-6 bg-[#1A1F2B] hover:bg-black text-white font-bold py-4 rounded-xl shadow-lg justify-center items-center gap-2 text-lg">
                    <i class="fas fa-lock text-green-400"></i> Pay with M-Pesa
                </button>
            </div>
        </div>
    </main>

    <!-- Mobile Button -->
    <div class="sticky-footer bg-white border-t border-gray-200 p-4 md:hidden">
        <button type="submit" form="checkoutForm" id="mobileSubmitBtn" class="w-full bg-[#1A1F2B] text-white font-bold py-4 rounded-xl shadow-lg flex justify-center items-center gap-2 text-lg">
            <i class="fas fa-lock text-green-400"></i> Pay with M-Pesa
        </button>
    </div>

    <!-- Processing Modal -->
    <div id="processingModal" class="fixed inset-0 z-[100] hidden flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl relative">
            <div id="state-loading">
                <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-6">
                    <div class="spinner border-green-500"></div>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Check your phone!</h3>
                <p class="text-gray-500 mb-6 text-sm">We are sending an M-Pesa prompt to <strong id="displayPhone" class="text-gray-900"></strong>.</p>
            </div>

            <!-- Success Screen with Return to Home button -->
            <div id="state-success" class="hidden">
                <div class="w-20 h-20 mx-auto bg-green-500 rounded-full flex items-center justify-center mb-6 text-white text-3xl">
                    <i class="fas fa-check"></i>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Payment Received!</h3>
                <p class="text-gray-500 mb-6 text-sm">Your order has been placed successfully.</p>
                
                <a href="index.php" class="block w-full bg-[#1A1F2B] hover:bg-black text-white font-bold py-3 rounded-xl transition">
                    <i class="fas fa-home mr-2"></i> Return to Home
                </a>
            </div>
        </div>
    </div>

    <script>
        let selectedMethod = 'mpesa';

        function selectPayment(method) {
            selectedMethod = method;
            document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('active'));
            
            if (method === 'mpesa') {
                document.querySelectorAll('.payment-option')[0].classList.add('active');
                document.getElementById('radio-mpesa').className = 'ml-auto w-5 h-5 rounded-full border-2 border-green-500 flex items-center justify-center bg-green-500';
                document.getElementById('radio-mpesa').innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                document.getElementById('radio-pickup').className = 'ml-auto w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center';
                document.getElementById('radio-pickup').innerHTML = '';
            } else {
                document.querySelectorAll('.payment-option')[1].classList.add('active');
                document.getElementById('radio-pickup').className = 'ml-auto w-5 h-5 rounded-full border-2 border-green-500 flex items-center justify-center bg-green-500';
                document.getElementById('radio-pickup').innerHTML = '<i class="fas fa-check text-white text-xs"></i>';
                document.getElementById('radio-mpesa').className = 'ml-auto w-5 h-5 rounded-full border-2 border-gray-300 flex items-center justify-center';
                document.getElementById('radio-mpesa').innerHTML = '';
            }
        }

        function processOrder(event) {
            event.preventDefault();

            const name = document.getElementById('customerName').value;
            const phone = document.getElementById('customerPhone').value;
            const totalAmount = <?= $total_price ?>; 

            if (selectedMethod === 'mpesa') {
                document.getElementById('processingModal').classList.remove('hidden');
                document.getElementById('state-loading').classList.remove('hidden');
                document.getElementById('state-success').classList.add('hidden');
                document.getElementById('displayPhone').innerText = phone;

                let formData = new FormData();
                formData.append('action', 'place_online_order'); 
                formData.append('name', name);
                formData.append('phone', phone);
                formData.append('total', totalAmount);

                // FIXED: Pointing to the actions/ folder
                fetch('actions/order_action.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(orderData => {
                    
                    if (orderData.success) {
                        // Trigger M-Pesa API
                        fetch('mpesa_api.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                phone: phone, 
                                amount: totalAmount, 
                                name: name,
                                order_id: orderData.order_id 
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('state-loading').classList.add('hidden');
                                document.getElementById('state-success').classList.remove('hidden');
                            } else {
                                alert("M-Pesa Error: " + data.message);
                                document.getElementById('processingModal').classList.add('hidden');
                            }
                        })
                        .catch(error => {
                            alert("Network error reaching M-Pesa.");
                            document.getElementById('processingModal').classList.add('hidden');
                        });
                    } else {
                        alert("Error saving order to database: " + orderData.message);
                        document.getElementById('processingModal').classList.add('hidden');
                    }
                })
                .catch(error => {
                    alert("Could not connect to actions/orders_action.php.");
                    document.getElementById('processingModal').classList.add('hidden');
                });

            } else {
                alert("Order Reserved! Please pick up your items at our store.");
            }
        }
    </script>
</body>
</html>