<?php
session_start();
require_once 'config/database.php';

// 1. Get the selected category from the URL (if any)
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'All';

// 2. Fetch the unique categories for the filter buttons
$cat_stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$categories = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Fetch products based on the selected category
if ($selected_category === 'All') {
    $stmt = $pdo->query("SELECT id, product_name, description, specs, category, image, selling_price, stock_quantity FROM products WHERE stock_quantity > 0");
} else {
    $stmt = $pdo->prepare("SELECT id, product_name, description, specs, category, image, selling_price, stock_quantity FROM products WHERE stock_quantity > 0 AND category = ?");
    $stmt->execute([$selected_category]);
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Eckose Techs | Premium Electronics</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Cart Animations */
        .cart-panel { transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        .cart-open { transform: translateX(0); }
        .cart-closed { transform: translateX(100%); }
        .overlay { transition: opacity 0.4s ease-in-out; }
        
        /* Product Card Hover */
        .product-card { transition: all 0.3s ease; }
        .product-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .product-card:hover img { transform: scale(1.08); }
        .product-card img { transition: transform 0.5s ease; }

        /* Spec Button Styling */
        .spec-btn {
            border: 2px solid #e5e7eb;
            background-color: white;
            color: #4b5563;
            transition: all 0.2s;
            cursor: pointer;
        }
        .spec-btn:hover { border-color: #f97316; }
        .spec-btn.active {
            border-color: #f97316;
            background-color: #fff7ed;
            color: #ea580c;
            font-weight: bold;
            box-shadow: 0 0 0 2px #f97316;
        }

        /* Bounce animation for the cart icon */
        @keyframes bounce-cart {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }
        .cart-bounce { animation: bounce-cart 0.4s ease; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 overflow-x-hidden">

    <!-- Navbar -->
    <nav class="bg-[#1A1F2B] text-white px-8 py-4 flex justify-between items-center sticky top-0 z-40 shadow-lg">
        <a href="index.php" class="flex items-center gap-2 group">
            <div class="bg-orange-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold text-xl group-hover:scale-110 transition">E</div>
            <span class="text-2xl font-bold tracking-widest">ECKOSE</span>
        </a>
        <div class="flex items-center gap-6">
            <a href="login.php" class="text-gray-300 hover:text-white text-sm font-semibold hidden md:block">Staff Portal</a>
            <button onclick="toggleCart()" class="relative text-white hover:text-orange-400 transition">
                <i id="cart-icon" class="fas fa-shopping-cart text-2xl"></i>
                <span class="absolute -top-2 -right-2 bg-orange-500 text-xs font-bold px-2 py-0.5 rounded-full border-2 border-[#1A1F2B]" id="nav-cart-count">0</span>
            </button>
        </div>
    </nav>

    <!-- HERO BANNER -->
    <section class="relative bg-[#1A1F2B] py-24 px-8 md:px-20 overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-orange-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="max-w-7xl mx-auto relative z-10 flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 mb-10 md:mb-0">
                <h1 class="text-4xl md:text-5xl font-extrabold text-white leading-tight mb-6">
                    Discover the Future of <br>
                    <span class="text-orange-500">Tech in Kenya</span>
                </h1>
                <p class="text-gray-300 text-lg mb-8 max-w-lg">
                    Shop authentic electronics with official warranties. Enjoy fast delivery across Nairobi or pick up from our store today.
                </p>
                <a href="#products" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 px-8 rounded-full shadow-lg transition transform hover:-translate-y-1 inline-block">
                    Shop Now <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="py-16 px-8 md:px-20 max-w-7xl mx-auto">
        
        <!-- CATEGORY FILTERS -->
        <div class="mb-10">
            <h2 class="text-3xl font-bold text-gray-900 mb-6">Shop by Category</h2>
            <div class="flex flex-wrap gap-3">
                <a href="index.php?category=All#products" class="px-5 py-2 rounded-full font-semibold transition <?= $selected_category === 'All' ? 'bg-orange-500 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-300 hover:border-orange-500' ?>">
                    All Products
                </a>
                <?php foreach($categories as $cat): ?>
                    <a href="index.php?category=<?= urlencode($cat) ?>#products" class="px-5 py-2 rounded-full font-semibold transition <?= $selected_category === $cat ? 'bg-orange-500 text-white shadow-md' : 'bg-white text-gray-600 border border-gray-300 hover:border-orange-500' ?>">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <?php if(empty($products)): ?>
                <div class="col-span-full text-center py-10 text-gray-500">
                    <i class="fas fa-box-open text-4xl mb-3 text-gray-300"></i>
                    <p>No products found in this category.</p>
                </div>
            <?php else: ?>
                <?php foreach($products as $product): ?>
                    <div class="product-card bg-white rounded-2xl border border-gray-100 p-6 flex flex-col cursor-pointer" onclick='openProductModal(<?= htmlspecialchars(json_encode($product), ENT_QUOTES, "UTF-8") ?>)'>
                        <div class="w-full h-48 mb-6 rounded-xl bg-gray-50 flex items-center justify-center overflow-hidden">
                            <img src="assets/images/products/<?= htmlspecialchars($product['image'] ?? 'default.png') ?>" class="w-full h-full object-contain p-4">
                        </div>
                        
                        <span class="text-[10px] font-bold uppercase tracking-wider text-green-600 bg-green-50 px-2 py-1 rounded w-max mb-2">In Stock</span>
                        
                        <h3 class="font-bold text-gray-800 text-lg mb-2 leading-tight"><?= htmlspecialchars($product['product_name']) ?></h3>
                        
                        <div class="mt-auto flex items-center justify-between">
                            <p class="text-xl font-extrabold text-gray-900">Ksh <?= number_format($product['selling_price'], 0) ?></p>
                            <button class="bg-[#2D3748] hover:bg-black text-white w-10 h-10 rounded-full flex items-center justify-center transition">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Product Modal -->
    <div id="productModal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black bg-opacity-60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col md:flex-row overflow-hidden relative">
            
            <button onclick="closeProductModal()" class="absolute top-4 right-4 z-10 bg-gray-100 hover:bg-red-100 text-gray-500 hover:text-red-500 w-10 h-10 rounded-full flex items-center justify-center transition">
                <i class="fas fa-times text-lg"></i>
            </button>

            <div class="md:w-1/2 bg-gray-50 flex items-center justify-center p-8 relative">
                <img id="modal-img" src="" class="w-full max-w-sm object-contain drop-shadow-xl transition duration-300">
            </div>

            <div class="md:w-1/2 p-8 flex flex-col h-full overflow-y-auto">
                <h3 id="modal-name" class="text-3xl font-extrabold text-gray-900 mb-2"></h3>
                <p id="modal-price" class="text-3xl text-orange-500 font-extrabold mb-6"></p>
                
                <!-- Specs Container -->
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Select Specification</label>
                    <div id="modal-specs-container" class="flex flex-wrap gap-2">
                        <!-- JS injects buttons here -->
                    </div>
                    <input type="hidden" id="selected-spec" value="">
                </div>

                <!-- Qty & Add -->
                <div class="flex gap-4 mb-6 mt-auto">
                    <div class="w-1/3">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Qty</label>
                        <input type="number" id="modal-qty" value="1" min="1" class="w-full border-2 border-gray-200 rounded-xl p-3 text-center font-bold focus:border-orange-500 outline-none transition">
                    </div>
                    <div class="w-2/3 flex items-end">
                        <button onclick="addToCartFromModal()" id="add-to-cart-btn" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center text-lg">
                            <i id="btn-icon" class="fas fa-cart-plus mr-2"></i> <span id="btn-text">Add to Cart</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Overlay & Cart -->
    <div id="cart-overlay" onclick="toggleCart()" class="fixed inset-0 bg-black bg-opacity-60 z-40 overlay opacity-0 pointer-events-none backdrop-blur-sm"></div>

    <div id="cart-panel" class="fixed top-0 right-0 h-full w-full md:w-96 bg-white shadow-2xl z-50 cart-panel cart-closed flex flex-col">
        <div class="p-6 bg-[#1A1F2B] text-white flex justify-between items-center flex-shrink-0">
            <h2 class="text-xl font-bold"><i class="fas fa-shopping-bag text-orange-500 mr-2"></i> Your Cart</h2>
            <button onclick="toggleCart()" class="text-gray-400 hover:text-white transition"><i class="fas fa-times text-2xl"></i></button>
        </div>
        
        <div id="cart-items" class="flex-1 overflow-y-auto p-6">
            <p class="text-center text-gray-400 mt-10">Your cart is empty.</p>
        </div>

        <div class="p-6 border-t bg-gray-50 flex-shrink-0">
            <div class="space-y-1 mb-4 border-t border-gray-200 pt-3">
                <div class="flex justify-between items-center pt-2 mt-2">
                    <span class="text-gray-900 font-bold text-lg">Total:</span>
                    <span class="text-2xl font-extrabold text-orange-500" id="cart-total">Ksh 0.00</span>
                </div>
            </div>

            <a href="checkout.php" id="checkout-btn" class="w-full block text-center bg-green-600 hover:bg-green-700 text-white font-bold py-4 rounded-xl shadow-lg transition text-lg opacity-50 pointer-events-none">
                Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>

    <script>
        let currentProductId = null;
        let currentProductBasePrice = 0;
        let cartData = [];
        let parsedVariations = []; 

        function animateCartIcon() {
            const icon = document.getElementById('cart-icon');
            icon.classList.add('cart-bounce');
            setTimeout(() => {
                icon.classList.remove('cart-bounce');
            }, 400);
        }

        function openProductModal(product) {
            currentProductId = product.id;
            currentProductBasePrice = parseFloat(product.selling_price);
            
            document.getElementById('modal-img').src = 'assets/images/products/' + (product.image || 'default.png');
            document.getElementById('modal-name').innerText = product.product_name;
            document.getElementById('modal-qty').value = 1;

            const addBtn = document.getElementById('add-to-cart-btn');
            const btnIcon = document.getElementById('btn-icon');
            const btnText = document.getElementById('btn-text');
            
            addBtn.disabled = false;
            addBtn.className = 'w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl shadow-lg transition transform hover:-translate-y-1 flex justify-center items-center text-lg';
            btnIcon.className = 'fas fa-cart-plus mr-2';
            btnText.innerText = 'Add to Cart';

            const specsContainer = document.getElementById('modal-specs-container');
            const hiddenInput = document.getElementById('selected-spec');
            specsContainer.innerHTML = ''; 

            parsedVariations = [];

            if (product.variations && product.variations.trim() !== '') {
                try { parsedVariations = JSON.parse(product.variations); } catch (e) {}
            } 
            
            if (parsedVariations.length === 0 && product.specs && product.specs.trim() !== '') {
                let specsArray = product.specs.split(',').map(s => s.trim()).filter(s => s !== '');
                parsedVariations = specsArray.map(s => ({ name: s, price: currentProductBasePrice }));
            }

            if (parsedVariations.length === 0) {
                parsedVariations = [{ name: 'Standard Edition', price: currentProductBasePrice }];
            }

            document.getElementById('modal-price').innerText = 'Ksh ' + parsedVariations[0].price.toLocaleString(undefined, {minimumFractionDigits: 2});
            hiddenInput.value = parsedVariations[0].name;

            parsedVariations.forEach((variation, index) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = `spec-btn px-4 py-2 rounded-lg text-sm ${index === 0 ? 'active' : ''}`;
                btn.innerText = variation.name;
                
                btn.onclick = function() {
                    document.querySelectorAll('.spec-btn').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    hiddenInput.value = variation.name;
                    document.getElementById('modal-price').innerText = 'Ksh ' + variation.price.toLocaleString(undefined, {minimumFractionDigits: 2});
                };

                specsContainer.appendChild(btn);
            });

            document.getElementById('productModal').classList.remove('hidden');
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function toggleCart() {
            const panel = document.getElementById('cart-panel');
            const overlay = document.getElementById('cart-overlay');
            panel.classList.toggle('cart-closed');
            panel.classList.toggle('cart-open');
            overlay.classList.toggle('opacity-0');
            overlay.classList.toggle('pointer-events-none');
        }

        function addToCartFromModal() {
            const qty = parseInt(document.getElementById('modal-qty').value);
            const selectedSpec = document.getElementById('selected-spec').value;
            
            const selectedVariation = parsedVariations.find(v => v.name === selectedSpec);
            let finalPrice = selectedVariation ? selectedVariation.price : currentProductBasePrice;
            finalPrice = parseFloat(finalPrice.toString().replace(/,/g, ''));

            const addBtn = document.getElementById('add-to-cart-btn');
            const btnIcon = document.getElementById('btn-icon');
            const btnText = document.getElementById('btn-text');
            
            addBtn.disabled = true;
            btnIcon.className = 'fas fa-spinner fa-spin mr-2';
            btnText.innerText = 'Adding...';

            const formData = new FormData();
            formData.append('action', 'add_ajax');
            formData.append('product_id', currentProductId);
            formData.append('quantity', qty);
            formData.append('spec', selectedSpec);
            formData.append('custom_price', finalPrice);

            fetch('cart_ajax.php?v=3', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const jsonStart = text.indexOf('{');
                    const jsonEnd = text.lastIndexOf('}') + 1;
                    const cleanJson = text.substring(jsonStart, jsonEnd);
                    const data = JSON.parse(cleanJson);
                    
                    if(data.success) {
                        cartData = data.cart;
                        renderCart(); // This will no longer crash
                        
                        addBtn.className = 'w-full bg-green-600 text-white font-bold py-3 rounded-xl shadow-lg flex justify-center items-center text-lg cursor-default';
                        btnIcon.className = 'fas fa-check-circle mr-2';
                        btnText.innerText = 'Added to Cart!';
                        
                        animateCartIcon();

                        setTimeout(() => {
                            closeProductModal();
                        }, 1500);
                    } else {
                        alert("Error: " + data.error);
                        addBtn.disabled = false;
                        btnIcon.className = 'fas fa-cart-plus mr-2';
                        btnText.innerText = 'Add to Cart';
                    }
                } catch (error) {
                    console.error("Raw Response:", text);
                    alert("An error occurred. Please check console.");
                    addBtn.disabled = false;
                    btnIcon.className = 'fas fa-cart-plus mr-2';
                    btnText.innerText = 'Add to Cart';
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert("Network error.");
                addBtn.disabled = false;
                btnIcon.className = 'fas fa-cart-plus mr-2';
                btnText.innerText = 'Add to Cart';
            });
        }

        function removeFromCart(key) {
            const formData = new FormData();
            formData.append('action', 'remove_ajax');
            formData.append('cart_key', key);

            fetch('cart_ajax.php?v=3', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    cartData = data.cart;
                    renderCart();
                }
            });
        }

        // FIXED FUNCTION
        function renderCart() {
            const cartContainer = document.getElementById('cart-items');
            const checkoutBtn = document.getElementById('checkout-btn');
            
            // Declare variables at the top so they are ALWAYS available
            let totalItems = 0;
            let totalPrice = 0;
            
            if (cartData.length === 0) {
                cartContainer.innerHTML = '<p class="text-center text-gray-400 mt-10">Your cart is empty.</p>';
                checkoutBtn.classList.add('opacity-50', 'pointer-events-none');
                document.getElementById('nav-cart-count').innerText = '0';
            } else {
                cartContainer.innerHTML = '';
                
                cartData.forEach(item => {
                    totalItems += item.qty;
                    
                    // Because we fixed cart_ajax.php, item.subtotal is now a clean number
                    let itemSubtotal = parseFloat(item.subtotal);
                    totalPrice += itemSubtotal;
                    
                    cartContainer.innerHTML += `
                        <div class="flex justify-between items-start mb-4 pb-4 border-b border-gray-100">
                            <div class="flex-1 pr-2">
                                <p class="font-bold text-gray-800 text-sm leading-tight mb-1">${item.name}</p>
                                <span class="text-[10px] bg-gray-100 text-gray-600 border border-gray-200 px-1.5 py-0.5 rounded font-semibold">${item.spec}</span>
                                <p class="text-xs text-gray-500 mt-1">Qty: ${item.qty} x Ksh ${parseFloat(item.price).toLocaleString()}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900 mb-2">Ksh ${parseFloat(item.subtotal).toLocaleString()}</p>
                                <button onclick="removeFromCart('${item.key}')" class="text-red-500 hover:text-red-700 text-xs font-bold transition">
                                    <i class="fas fa-trash-alt"></i> Remove
                                </button>
                            </div>
                        </div>
                    `;
                });
                checkoutBtn.classList.remove('opacity-50', 'pointer-events-none');
                document.getElementById('nav-cart-count').innerText = totalItems;
            }
            
            // This line will now work perfectly every time
            document.getElementById('cart-total').innerText = 'Ksh ' + totalPrice.toLocaleString(undefined, {minimumFractionDigits: 2});
        }
    </script>
</body>
</html>