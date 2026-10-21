<?php
// eckose_pos/pages/customers.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE full_name LIKE ? OR phone LIKE ? OR email LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY id DESC");
}
$customers = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">

    <!-- Header with Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-gray-700 pb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Customer Management</h2>
            <p class="text-gray-400">Keep track of your retail clients, their contact info, and loyalty tiers.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, phone..." class="w-full bg-[#242B38] border border-gray-600 text-white pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:border-orange-500">
            </form>
            <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg transition flex items-center whitespace-nowrap">
                <i class="fas fa-user-plus mr-2"></i> Add Customer
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="bg-green-900 border-l-4 border-green-500 text-green-300 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Customer Table -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Customer Info</th>
                        <th class="px-6 py-4">Contact</th>
                        <th class="px-6 py-4 text-center">Type / Tier</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($customers)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No customers found.</td></tr>
                    <?php else: ?>
                        <?php foreach($customers as $c): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-orange-400 border border-gray-600 font-bold">
                                            <?= strtoupper(substr($c['full_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white text-base"><?= htmlspecialchars($c['full_name']) ?></div>
                                            <div class="text-xs text-gray-500">Added: <?= date('M d, Y', strtotime($c['created_at'])) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-300 font-mono text-xs"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($c['phone'] ?? 'N/A') ?></div>
                                    <?php if(!empty($c['email'])): ?>
                                        <div class="text-gray-400 text-xs mt-1"><i class="fas fa-envelope mr-1"></i> <?= htmlspecialchars($c['email']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php 
                                    $tier = $c['customer_type'] ?? 'Walk-in';
                                    if ($tier === 'VIP') {
                                        echo '<span class="bg-yellow-900 text-yellow-300 text-xs font-bold px-3 py-1 rounded-full border border-yellow-700"><i class="fas fa-crown mr-1"></i> VIP</span>';
                                    } elseif ($tier === 'Wholesale') {
                                        echo '<span class="bg-purple-900 text-purple-300 text-xs font-bold px-3 py-1 rounded-full border border-purple-700"><i class="fas fa-boxes mr-1"></i> Wholesale</span>';
                                    } else {
                                        echo '<span class="bg-gray-800 text-gray-300 text-xs font-bold px-3 py-1 rounded-full border border-gray-600">Walk-in</span>';
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(($c['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="bg-green-900 text-green-300 text-xs font-bold px-3 py-1 rounded-full border border-green-700">Active</span>
                                    <?php else: ?>
                                        <span class="bg-red-900 text-red-300 text-xs font-bold px-3 py-1 rounded-full border border-red-700">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center space-x-3">
                                    <button onclick='editCustomer(<?= json_encode($c) ?>)' class="text-blue-400 hover:text-blue-300 transition"><i class="fas fa-edit"></i></button>
                                    
                                    <?php if($c['id'] != 1): // Prevent deleting Walk-in Customer ?>
                                    <form action="../actions/customer_action.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this customer?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <button type="submit" class="text-red-400 hover:text-red-300 transition"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Dark Theme Modal -->
<div id="customerModal" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-[#242B38] rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden border border-gray-700 max-h-[90vh] flex flex-col">
        
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-[#1A1F2B] shrink-0">
            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Customer</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-400 focus:outline-none transition">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        
        <form action="../actions/customer_action.php" method="POST" class="p-6 overflow-y-auto custom-scrollbar">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="customerId" value="">

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Full Name *</label>
                <input type="text" name="full_name" id="full_name" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. John Doe">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Phone Number (M-Pesa)</label>
                    <input type="text" name="phone" id="phone" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition font-mono" placeholder="07XX XXX XXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                    <input type="email" name="email" id="email" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="customer@example.com">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Physical Address</label>
                <input type="text" name="address" id="address" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Westlands, Nairobi">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Customer Type</label>
                    <select name="customer_type" id="customer_type" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Walk-in">Walk-in / Regular</option>
                        <option value="VIP">VIP / Loyalty</option>
                        <option value="Wholesale">Wholesale</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                    <select name="status" id="status" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-300 mb-1">Notes / Preferences</label>
                <textarea name="notes" id="notes" rows="2" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Prefers Apple products, always pays via M-Pesa..."></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700 shrink-0">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-orange-500 text-white font-bold rounded-lg shadow hover:bg-orange-600 transition">Save Customer</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('customerModal');
    
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Add New Customer';
        document.getElementById('formAction').value = 'add';
        document.getElementById('customerId').value = '';
        document.getElementById('full_name').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('email').value = '';
        document.getElementById('address').value = '';
        document.getElementById('customer_type').value = 'Walk-in';
        document.getElementById('status').value = 'Active';
        document.getElementById('notes').value = '';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editCustomer(customer) {
        document.getElementById('modalTitle').innerText = 'Edit Customer';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('customerId').value = customer.id;
        document.getElementById('full_name').value = customer.full_name;
        document.getElementById('phone').value = customer.phone || '';
        document.getElementById('email').value = customer.email || '';
        document.getElementById('address').value = customer.address || '';
        document.getElementById('customer_type').value = customer.customer_type || 'Walk-in';
        document.getElementById('status').value = customer.status || 'Active';
        document.getElementById('notes').value = customer.notes || '';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
</script>

<?php require_once '../includes/footer.php'; ?>