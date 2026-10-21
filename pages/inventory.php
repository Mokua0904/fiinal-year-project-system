<?php
// eckose_pos/pages/inventory.php
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_name LIKE ? OR barcode LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
}
$products = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">
    
    <!-- Header with Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-gray-700 pb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Inventory Management</h2>
            <p class="text-gray-400">Manage stock, categories, specs, and serial numbers.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search products..." class="w-full bg-[#242B38] border border-gray-600 text-white pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:border-orange-500">
            </form>
            <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg transition flex items-center whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> Add Product
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="bg-green-900 border-l-4 border-green-500 text-green-300 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Inventory Table -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Image</th>
                        <th class="px-6 py-4">Product & Category</th>
                        <th class="px-6 py-4">Serial / Barcode</th>
                        <th class="px-6 py-4">Selling Price</th>
                        <th class="px-6 py-4 text-center">Stock</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($products)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No products found.</td></tr>
                    <?php else: ?>
                        <?php foreach($products as $p): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition items-center">
                                <td class="px-6 py-2">
                                    <img src="../assets/images/products/<?= htmlspecialchars($p['image']) ?>" alt="Product" class="w-12 h-12 object-cover rounded shadow-sm border border-gray-600">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-white"><?= htmlspecialchars($p['product_name']) ?></div>
                                    <span class="text-[10px] text-blue-400 bg-blue-900 bg-opacity-30 px-2 py-0.5 rounded border border-blue-800"><i class="fas fa-tag"></i> <?= htmlspecialchars($p['category'] ?? 'General') ?></span>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono">
                                    <?php if(!empty($p['serial_number'])): ?>
                                        <div class="text-gray-300">SN: <?= htmlspecialchars($p['serial_number']) ?></div>
                                    <?php endif; ?>
                                    <?php if(!empty($p['barcode'])): ?>
                                        <div class="text-gray-500">Bar: <?= htmlspecialchars($p['barcode']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 font-bold text-orange-400">Ksh <?= number_format($p['selling_price'], 2) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <?php if($p['stock_quantity'] <= $p['min_stock_level']): ?>
                                        <span class="bg-red-900 text-red-300 text-xs font-bold px-2.5 py-1 rounded-full border border-red-700" title="Low Stock Alert!"><i class="fas fa-exclamation-triangle mr-1"></i> <?= $p['stock_quantity'] ?></span>
                                    <?php else: ?>
                                        <span class="bg-green-900 text-green-300 text-xs font-bold px-2.5 py-1 rounded-full border border-green-700"><?= $p['stock_quantity'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center space-x-3">
                                    <button onclick='editProduct(<?= json_encode($p) ?>)' class="text-blue-400 hover:text-blue-300 transition"><i class="fas fa-edit"></i></button>
                                    <form action="../actions/product_action.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this product?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 transition"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DARK THEME MODAL -->
<div id="productModal" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-[#242B38] rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden max-h-[90vh] flex flex-col border border-gray-700">
        
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-[#1A1F2B] flex-shrink-0">
            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Product</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-400 focus:outline-none transition">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        
        <form action="../actions/product_action.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto custom-scrollbar">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="productId" value="">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Product Name *</label>
                <input type="text" name="product_name" id="product_name" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
            </div>

            <!-- NEW CATEGORY INPUT -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-orange-400 mb-1">Category *</label>
                <select name="category" id="category" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                    <option value="iPhones">iPhones</option>
                    <option value="Laptops">Laptops</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Gaming">Gaming</option>
                    <option value="Monitors">Monitors</option>
                    <option value="General">General / Other</option>
                </select>
            </div>

            <div class="mb-4">
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-sm font-medium text-gray-300">Description</label>
                    <button type="button" onclick="generateAIDescription()" id="ai-desc-btn" class="text-xs bg-indigo-900 text-indigo-300 border border-indigo-700 hover:bg-indigo-800 font-bold py-1 px-2 rounded flex items-center transition">
                        <i class="fas fa-sparkles text-yellow-500 mr-1"></i> AI Write
                    </button>
                </div>
                <textarea name="description" id="description" rows="3" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition"></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Image</label>
                <input type="file" name="product_image" id="product_image" accept="image/*" class="w-full bg-[#1A1F2B] border border-gray-600 text-gray-300 rounded-lg p-2 text-sm file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-gray-700 file:text-white hover:file:bg-gray-600 transition">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Barcode</label>
                    <input type="text" name="barcode" id="barcode" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-orange-400 mb-1">Specs (Comma separated)</label>
                    <input type="text" name="specs" id="specs" placeholder="e.g. 128GB, 256GB" class="w-full bg-[#1A1F2B] border border-orange-500 border-opacity-50 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 focus:ring-1 focus:ring-orange-500 transition">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Serial Number (High-Value Items)</label>
                <input type="text" name="serial_number" id="serial_number" placeholder="e.g. SN-IPHONE-001" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Cost Price *</label>
                    <input type="number" step="0.01" name="cost_price" id="cost_price" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Selling Price *</label>
                    <input type="number" step="0.01" name="selling_price" id="selling_price" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Stock Qty *</label>
                    <input type="number" name="stock_quantity" id="stock_quantity" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Min Stock Alert *</label>
                    <input type="number" name="min_stock_level" id="min_stock_level" value="5" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500">
                </div>
            </div>

            <div class="flex justify-end gap-3 flex-shrink-0 pt-4 border-t border-gray-700">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-orange-500 text-white font-bold rounded-lg shadow hover:bg-orange-600 transition">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('productModal');
    
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Add New Product';
        document.getElementById('formAction').value = 'add';
        document.getElementById('productId').value = '';
        document.getElementById('product_name').value = '';
        document.getElementById('category').value = 'General';
        document.getElementById('description').value = '';
        document.getElementById('product_image').value = '';
        document.getElementById('barcode').value = '';
        document.getElementById('specs').value = '';
        document.getElementById('serial_number').value = '';
        document.getElementById('cost_price').value = '';
        document.getElementById('selling_price').value = '';
        document.getElementById('stock_quantity').value = '';
        document.getElementById('min_stock_level').value = '5';
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editProduct(product) {
        document.getElementById('modalTitle').innerText = 'Edit Product';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('productId').value = product.id;
        document.getElementById('product_name').value = product.product_name;
        document.getElementById('category').value = product.category || 'General';
        document.getElementById('description').value = product.description || '';
        document.getElementById('product_image').value = ''; 
        document.getElementById('barcode').value = product.barcode || '';
        document.getElementById('specs').value = product.specs || '';
        document.getElementById('serial_number').value = product.serial_number || '';
        document.getElementById('cost_price').value = product.cost_price;
        document.getElementById('selling_price').value = product.selling_price;
        document.getElementById('stock_quantity').value = product.stock_quantity;
        document.getElementById('min_stock_level').value = product.min_stock_level;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function generateAIDescription() {
        const productName = document.getElementById('product_name').value;
        const descField = document.getElementById('description');
        const aiBtn = document.getElementById('ai-desc-btn');
        if (!productName.trim()) { alert('Type a Product Name first!'); return; }
        aiBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Writing...';
        aiBtn.disabled = true;
        fetch('../actions/generate_description.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ product_name: productName })
        })
        .then(response => response.json())
        .then(data => { if (data.success) descField.value = data.description; else alert('Error: ' + data.error); })
        .catch(error => { alert('Network error.'); })
        .finally(() => { aiBtn.innerHTML = '<i class="fas fa-sparkles text-yellow-500 mr-1"></i> AI Write'; aiBtn.disabled = false; });
    }
</script>

<?php require_once '../includes/footer.php'; ?>