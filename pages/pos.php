<?php
// eckose_pos/pages/pos.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Fetch products for the grid
$stmt = $pdo->query("SELECT id, product_name, description, specs, image, selling_price, stock_quantity FROM products WHERE stock_quantity > 0");
$products = $stmt->fetchAll();

// Check if we are processing an online order
$online_order_id = isset($_GET['online_order_id']) ? (int)$_GET['online_order_id'] : 0;
$online_order_data = null;

if ($online_order_id > 0) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                oi.product_id, 
                oi.quantity AS quantity, 
                p.product_name, 
                p.specs, 
                p.image, 
                p.selling_price, 
                p.stock_quantity 
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$online_order_id]);
        $online_order_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $online_order_data = [];
    }
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Pass PHP data to JavaScript -->
<script>
    const initialOnlineOrder = <?= json_encode($online_order_data ?? []) ?>;
    const onlineOrderId = <?= $online_order_id ?>;
</script>

<div class="bg-[#1A1F2B] min-h-[calc(100vh-100px)] p-4 rounded-2xl border border-gray-800 shadow-inner text-gray-200 flex flex-col lg:flex-row gap-4">
    
    <!-- LEFT: Product Grid -->
    <div class="w-full lg:w-[65%] flex flex-col h-[85vh]">
        <div class="mb-4 relative flex-shrink-0">
            <i class="fas fa-search absolute left-4 top-4 text-gray-400 text-lg"></i>
            <input type="text" id="pos-search" placeholder="Scan barcode or search product..." 
                   class="w-full bg-[#242B38] border border-gray-600 text-white pl-12 pr-4 py-4 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500 shadow-lg text-lg transition font-medium"
                   onkeyup="filterProducts()">
        </div>

        <div class="flex-1 overflow-y-auto pr-2 pb-4 custom-scrollbar">
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4" id="product-grid">
                <?php foreach($products as $product): ?>
                    <div class="product-card bg-[#242B38] rounded-xl p-3 cursor-pointer border-2 border-transparent hover:border-orange-500 hover:bg-[#2D3748] transition-all shadow-md flex flex-col group relative"
                         data-name="<?= strtolower(htmlspecialchars($product['product_name'])) ?>"
                         onclick='addToCart(<?= htmlspecialchars(json_encode($product), ENT_QUOTES, "UTF-8") ?>)'>
                        
                        <div class="w-full h-28 mb-3 overflow-hidden rounded-lg bg-[#1A1F2B] flex items-center justify-center relative">
                            <img src="../assets/images/products/<?= htmlspecialchars($product['image'] ?? 'default.png') ?>" class="w-full h-full object-contain p-2 group-hover:scale-105 transition-transform">
                        </div>

                        <h3 class="font-bold text-gray-100 text-sm truncate mb-1"><?= htmlspecialchars($product['product_name']) ?></h3>
                        
                        <div class="mt-auto flex items-center justify-between">
                            <p class="text-orange-400 font-bold text-sm">Ksh <?= number_format($product['selling_price'], 0) ?></p>
                            <span class="text-[10px] bg-gray-700 text-gray-300 px-2 py-0.5 rounded-full"><?= $product['stock_quantity'] ?> left</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT: Cart -->
    <div class="w-full lg:w-[35%] bg-[#242B38] rounded-xl border border-gray-700 shadow-xl flex flex-col h-[85vh] overflow-hidden">
        <div class="bg-[#1A1F2B] p-4 border-b border-gray-700 flex justify-between items-center">
            <h2 class="text-lg font-bold text-white"><i class="fas fa-shopping-cart text-orange-500 mr-2"></i> Current Sale</h2>
            <span class="bg-orange-500 text-white text-xs font-bold px-2 py-1 rounded-full" id="cart-count">0 Items</span>
        </div>
        
        <div class="flex-1 overflow-y-auto p-2 custom-scrollbar">
            <table class="w-full text-sm text-left">
                <tbody id="cart-items" class="divide-y divide-gray-700"></tbody>
            </table>
            <div id="empty-cart-msg" class="text-center text-gray-500 mt-10">
                <i class="fas fa-barcode fa-3x mb-3 text-gray-600"></i>
                <p>Scan or click a product to start.</p>
            </div>
        </div>

        <div class="bg-[#1A1F2B] p-4 border-t border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <span class="text-gray-400 font-medium">Total</span>
                <span class="text-4xl font-extrabold text-white" id="cart-total">Ksh 0</span>
            </div>
            
            <div class="grid grid-cols-3 gap-2 mb-4">
                <label class="cursor-pointer">
                    <input type="radio" name="payment" value="Cash" class="peer hidden" checked>
                    <div class="text-center py-2 rounded-lg border-2 border-gray-600 text-gray-400 peer-checked:bg-gray-700 peer-checked:text-white peer-checked:border-gray-400 transition font-bold">
                        <i class="fas fa-money-bill block mb-1"></i> Cash
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="payment" value="Mpesa" class="peer hidden">
                    <div class="text-center py-2 rounded-lg border-2 border-gray-600 text-gray-400 peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-500 transition font-bold">
                        <i class="fas fa-mobile-alt block mb-1"></i> M-Pesa
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="payment" value="Card" class="peer hidden">
                    <div class="text-center py-2 rounded-lg border-2 border-gray-600 text-gray-400 peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-500 transition font-bold">
                        <i class="fas fa-credit-card block mb-1"></i> Card
                    </div>
                </label>
            </div>

            <button onclick="processCheckout()" id="checkout-btn" disabled 
                    class="w-full bg-green-600 hover:bg-green-500 active:scale-95 text-white font-bold py-4 rounded-xl shadow-lg transition-all disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed text-xl flex justify-center items-center min-h-[60px]">
                <i class="fas fa-check-circle mr-2"></i> Complete Sale
            </button>
        </div>
    </div>
</div>

<!-- CASH PAYMENT MODAL -->
<div id="cashModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-6 w-96 shadow-2xl">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Cash Payment</h3>
        <p class="text-2xl font-extrabold text-orange-500 mb-4">Total: Ksh <span id="cash-modal-total">0</span></p>
        
        <label class="block text-sm font-bold text-gray-700 mb-2">Cash Received</label>
        <input type="number" id="cash-received" class="w-full text-2xl p-3 border-2 border-gray-300 rounded-xl mb-4 outline-none focus:border-green-500 text-gray-800" placeholder="0.00" oninput="calculateChange()">
        
        <div class="flex justify-between items-center bg-gray-100 p-3 rounded-xl mb-6">
            <span class="font-bold text-gray-600">Change Due:</span>
            <span class="text-xl font-black text-green-600" id="change-due">Ksh 0</span>
        </div>
        
        <div class="flex gap-2">
            <button onclick="closeCashModal()" class="w-1/3 bg-gray-200 text-gray-700 font-bold py-3 rounded-xl hover:bg-gray-300">Cancel</button>
            <button onclick="completeCashSale()" id="confirm-cash-btn" class="w-2/3 bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-500 disabled:opacity-50">Complete Sale</button>
        </div>
    </div>
</div>

<!-- M-PESA / CARD MODAL -->
<div id="digitalModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-70 backdrop-blur-sm">
    <div class="bg-white rounded-2xl p-6 w-96 shadow-2xl text-center">
        <h3 class="text-xl font-bold text-gray-800 mb-2" id="digital-modal-title">M-Pesa Payment</h3>
        <p class="text-2xl font-extrabold text-orange-500 mb-6">Ksh <span id="digital-modal-total">0</span></p>
        
        <div id="mpesa-input-area">
            <label class="block text-sm font-bold text-gray-700 mb-2">Customer Phone Number</label>
            <input type="tel" id="mpesa-phone" class="w-full text-lg p-3 border-2 border-gray-300 rounded-xl mb-4 outline-none focus:border-green-500 text-gray-800" placeholder="07XX XXX XXX">
            <button onclick="triggerStkPush()" id="stk-btn" class="w-full bg-green-600 text-white font-bold py-3 rounded-xl hover:bg-green-500 mb-2">Send STK Push</button>
        </div>
        
        <div id="card-input-area" class="hidden">
            <p class="text-gray-500 mb-4 text-sm">Redirecting to secure card terminal...</p>
            <div class="animate-pulse flex space-x-4 justify-center"><div class="h-12 w-12 bg-blue-400 rounded-full"></div></div>
        </div>

        <button onclick="closeDigitalModal()" class="w-full bg-gray-200 text-gray-700 font-bold py-2 rounded-xl hover:bg-gray-300 mt-2">Cancel</button>
    </div>
</div>

<script>
    let cart = [];
    let currentPaymentMethod = 'Cash';
    let saleTotal = 0;

    function filterProducts() {
        const searchTerm = document.getElementById('pos-search').value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const name = card.getAttribute('data-name');
            card.style.display = name.includes(searchTerm) ? 'flex' : 'none';
        });
    }

    function addToCart(product) {
        let existingItem = cart.find(item => item.id === product.id);
        if (existingItem) {
            if (existingItem.qty < product.stock_quantity) existingItem.qty += 1;
            else alert("Max stock reached!");
        } else {
            cart.push({ id: product.id, name: product.product_name, specs: product.specs, price: parseFloat(product.selling_price), qty: 1, maxStock: parseInt(product.stock_quantity) });
        }
        updateCartUI();
    }

    function changeQty(id, newQty) {
        let item = cart.find(i => i.id === id);
        if (item) {
            let val = parseInt(newQty);
            item.qty = isNaN(val) || val < 1 ? 1 : Math.min(val, item.maxStock);
        }
        updateCartUI();
    }

    function removeFromCart(id) {
        cart = cart.filter(item => item.id !== id);
        updateCartUI();
    }

    function updateCartUI() {
        const cartContainer = document.getElementById('cart-items');
        const totalContainer = document.getElementById('cart-total');
        const checkoutBtn = document.getElementById('checkout-btn');
        const emptyMsg = document.getElementById('empty-cart-msg');
        const cartCount = document.getElementById('cart-count');
        
        cartContainer.innerHTML = '';
        let total = 0;
        let totalItems = 0;

        emptyMsg.style.display = cart.length === 0 ? 'block' : 'none';

        cart.forEach(item => {
            let itemTotal = item.price * item.qty;
            total += itemTotal;
            totalItems += item.qty;
            
            let specsBadge = item.specs ? `<span class="inline-block mt-1 text-[10px] bg-gray-700 text-gray-300 px-1.5 py-0.5 rounded border border-gray-600 truncate max-w-[120px]"><i class="fas fa-microchip mr-1"></i>${item.specs}</span>` : '';

            cartContainer.innerHTML += `
                <tr class="group hover:bg-[#2D3748] transition flex-wrap">
                    <td class="py-3 pl-2 pr-1 w-full block sm:table-cell sm:w-1/2 mb-2 sm:mb-0 border-b sm:border-0 border-gray-700">
                        <p class="font-bold text-white text-sm truncate">${item.name}</p>
                        ${specsBadge}
                        <p class="text-xs text-orange-400 mt-1">Ksh ${item.price.toLocaleString()}</p>
                    </td>
                    <td class="py-2 px-1 text-center w-full block sm:table-cell sm:w-1/3">
                        <div class="flex items-center justify-center bg-gray-700 rounded-lg overflow-hidden border border-gray-600 w-32 mx-auto">
                            <button onclick="changeQty(${item.id}, ${item.qty - 1})" class="px-3 py-2 text-gray-300 hover:bg-gray-600"><i class="fas fa-minus text-xs"></i></button>
                            <input type="number" value="${item.qty}" min="1" max="${item.maxStock}" onchange="changeQty(${item.id}, this.value)" class="w-12 bg-transparent text-center font-bold text-white outline-none text-lg appearance-none">
                            <button onclick="changeQty(${item.id}, ${item.qty + 1})" class="px-3 py-2 text-gray-300 hover:bg-gray-600"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                    </td>
                    <td class="py-3 px-1 text-right font-bold text-white w-full block sm:table-cell sm:w-1/6">${itemTotal.toLocaleString()}</td>
                    <td class="py-3 pr-2 text-center w-full block sm:table-cell sm:w-1/12">
                        <button onclick="removeFromCart(${item.id})" class="text-red-400 hover:text-red-300 p-1"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
            `;
        });

        totalContainer.innerText = `Ksh ${total.toLocaleString()}`;
        cartCount.innerText = `${totalItems} Items`;
        
        if (cart.length === 0) {
            checkoutBtn.disabled = true;
            checkoutBtn.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Complete Sale';
        } else {
            checkoutBtn.disabled = false;
            checkoutBtn.innerHTML = `<i class="fas fa-check-circle mr-2"></i> Pay Ksh ${total.toLocaleString()}`;
        }
    }

    document.querySelectorAll('input[name="payment"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            currentPaymentMethod = e.target.value;
        });
    });

    function processCheckout() {
        if (cart.length === 0) return alert("Cart is empty!");
        
        saleTotal = parseFloat(document.getElementById('cart-total').innerText.replace(/[^0-9.-]+/g,""));

        if (currentPaymentMethod === 'Cash') {
            document.getElementById('cash-modal-total').innerText = saleTotal.toLocaleString();
            document.getElementById('cashModal').classList.remove('hidden');
            document.getElementById('cashModal').classList.add('flex');
            document.getElementById('cash-received').value = '';
            document.getElementById('change-due').innerText = 'Ksh 0';
            document.getElementById('confirm-cash-btn').disabled = true;
        } else if (currentPaymentMethod === 'Mpesa') {
            document.getElementById('digital-modal-title').innerText = "M-Pesa Payment";
            document.getElementById('digital-modal-total').innerText = saleTotal.toLocaleString();
            document.getElementById('mpesa-input-area').classList.remove('hidden');
            document.getElementById('card-input-area').classList.add('hidden');
            document.getElementById('digitalModal').classList.remove('hidden');
            document.getElementById('digitalModal').classList.add('flex');
        } else if (currentPaymentMethod === 'Card') {
            document.getElementById('digital-modal-title').innerText = "Card Payment";
            document.getElementById('digital-modal-total').innerText = saleTotal.toLocaleString();
            document.getElementById('mpesa-input-area').classList.add('hidden');
            document.getElementById('card-input-area').classList.remove('hidden');
            document.getElementById('digitalModal').classList.remove('hidden');
            document.getElementById('digitalModal').classList.add('flex');
            
            setTimeout(() => { 
                closeDigitalModal();
                completeSale('Card'); 
            }, 2500);
        }
    }

    function calculateChange() {
        let received = parseFloat(document.getElementById('cash-received').value) || 0;
        let change = received - saleTotal;
        document.getElementById('change-due').innerText = change >= 0 ? 'Ksh ' + change.toLocaleString() : 'Insufficient';
        document.getElementById('confirm-cash-btn').disabled = change < 0;
    }

    function closeCashModal() {
        document.getElementById('cashModal').classList.add('hidden');
        document.getElementById('cashModal').classList.remove('flex');
    }

    function completeCashSale() {
        closeCashModal();
        completeSale('Cash');
    }

    function triggerStkPush() {
        const phone = document.getElementById('mpesa-phone').value;
        if (phone.length < 10) return alert("Enter a valid M-Pesa phone number (e.g. 07XXXXXXXX)");
        
        const btn = document.getElementById('stk-btn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        btn.disabled = true;

        fetch('../mpesa_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: phone, amount: saleTotal })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("STK Push sent successfully to " + phone + ".\n\nPlease tell the customer to enter their PIN.");
                closeDigitalModal();
                completeSale('Mpesa');
            } else {
                alert("M-Pesa Error: " + data.message);
                btn.innerHTML = 'Send STK Push';
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert("Network error. Could not reach M-Pesa API.");
            btn.innerHTML = 'Send STK Push';
            btn.disabled = false;
        });
    }

    function closeDigitalModal() {
        document.getElementById('digitalModal').classList.add('hidden');
        document.getElementById('digitalModal').classList.remove('flex');
        document.getElementById('stk-btn').innerHTML = 'Send STK Push';
        document.getElementById('stk-btn').disabled = false;
    }

    // --- COMPLETE SALE (POINTING TO YOUR MARK COLLECTED SCRIPT) ---
    function completeSale(method) {
        const btn = document.getElementById('checkout-btn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Finalizing...';

        fetch('../actions/sale_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                cart: cart, 
                payment_method: method,
                mpesa_phone: method === 'Mpesa' ? document.getElementById('mpesa-phone').value : null
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                
                // If it's an online order, mark it as collected using your specific file
                if (typeof onlineOrderId !== 'undefined' && onlineOrderId > 0) {
                    
                    fetch('../actions/mark_order_collected.php', { // CHANGED TO YOUR FILE
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ order_id: onlineOrderId })
                    })
                    .then(() => {
                        window.location.href = `pos_receipt.php?receipt=${data.receipt_no}`;
                    });
                    
                } else {
                    window.location.href = `pos_receipt.php?receipt=${data.receipt_no}`;
                }
                
            } else {
                alert("Error: " + data.error);
                btn.disabled = false;
                updateCartUI();
            }
        })
        .catch(error => {
            alert("Error processing sale in database.");
            btn.disabled = false;
            updateCartUI();
        });
    }

    // Initialize the cart with the online order items if they exist
    if (initialOnlineOrder && initialOnlineOrder.length > 0) {
        initialOnlineOrder.forEach(item => {
            let existingItem = cart.find(i => i.id === item.product_id);
            if (existingItem) {
                existingItem.qty += parseInt(item.quantity);
            } else {
                cart.push({
                    id: item.product_id,
                    name: item.product_name,
                    specs: item.specs,
                    price: parseFloat(item.selling_price),
                    qty: parseInt(item.quantity),
                    maxStock: parseInt(item.stock_quantity)
                });
            }
        });
        updateCartUI();
        alert("Online order loaded into POS. Please select payment method.");
    } else {
        updateCartUI();
    }
</script>

<?php require_once '../includes/footer.php'; ?>