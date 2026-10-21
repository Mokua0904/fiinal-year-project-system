<?php
// eckose_pos/pages/suppliers.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Only Admins can manage suppliers
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE supplier_name LIKE ? OR contact_person LIKE ? OR supplier_code LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY id DESC");
}
$suppliers = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">

    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-gray-700 pb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Supplier Management</h2>
            <p class="text-gray-400">Manage vendors and procurement details.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search suppliers..." class="w-full bg-[#242B38] border border-gray-600 text-white pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:border-orange-500">
            </form>
            <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg transition flex items-center whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> Add Supplier
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="bg-green-900 border-l-4 border-green-500 text-green-300 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_GET['error'])): ?>
        <div class="bg-red-900 border-l-4 border-red-500 text-red-300 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Company / Code</th>
                        <th class="px-6 py-4">Contact Person</th>
                        <th class="px-6 py-4">Contact Info</th>
                        <th class="px-6 py-4 text-center">Lead Time</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($suppliers)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No suppliers found.</td></tr>
                    <?php else: ?>
                        <?php foreach($suppliers as $s): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition">
                                <td class="px-6 py-4">
                                    <!-- Changed to supplier_name -->
                                    <div class="font-bold text-white text-base"><?= htmlspecialchars($s['supplier_name']) ?></div>
                                    <?php if(!empty($s['supplier_code'])): ?>
                                        <div class="text-xs text-orange-400 font-mono mt-1"><?= htmlspecialchars($s['supplier_code']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4"><?= htmlspecialchars($s['contact_person'] ?? 'N/A') ?></td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-300 font-mono text-xs"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($s['phone'] ?? 'N/A') ?></div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-gray-800 text-gray-300 px-2 py-1 rounded text-xs font-bold border border-gray-600">
                                        <?= htmlspecialchars($s['lead_time'] ?? 0) ?> Days
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(($s['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="bg-green-900 text-green-300 text-xs font-bold px-3 py-1 rounded-full border border-green-700">Active</span>
                                    <?php else: ?>
                                        <span class="bg-red-900 text-red-300 text-xs font-bold px-3 py-1 rounded-full border border-red-700">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center space-x-3">
                                    <button onclick='editSupplier(<?= json_encode($s) ?>)' class="text-blue-400 hover:text-blue-300 transition"><i class="fas fa-edit"></i></button>
                                    <form action="../actions/supplier_action.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this supplier?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
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

<!-- Dark Theme Modal -->
<div id="supplierModal" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-[#242B38] rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden border border-gray-700">
        
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-[#1A1F2B]">
            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Supplier</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-400 focus:outline-none transition">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        
        <form action="../actions/supplier_action.php" method="POST" class="p-6">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="supplierId" value="">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <!-- Changed name to supplier_name -->
                    <label class="block text-sm font-medium text-gray-300 mb-1">Company Name *</label>
                    <input type="text" name="supplier_name" id="supplier_name" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Samsung Kenya">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Supplier Code</label>
                    <input type="text" name="supplier_code" id="supplier_code" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition font-mono" placeholder="e.g. SUP-001">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" id="contact_person" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. John Kamau">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Phone Number</label>
                    <input type="text" name="phone" id="phone" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition font-mono" placeholder="07XX XXX XXX">
                </div>
            </div>

            <!-- REMOVED EMAIL FIELD HERE -->

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Physical Address</label>
                <input type="text" name="address" id="address" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Industrial Area, Nairobi">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Lead Time (Days)</label>
                    <input type="number" name="lead_time" id="lead_time" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. 3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                    <select name="status" id="status" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-orange-500 text-white font-bold rounded-lg shadow hover:bg-orange-600 transition">Save Supplier</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('supplierModal');
    
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Add New Supplier';
        document.getElementById('formAction').value = 'add';
        document.getElementById('supplierId').value = '';
        document.getElementById('supplier_name').value = '';
        document.getElementById('supplier_code').value = '';
        document.getElementById('contact_person').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('address').value = '';
        document.getElementById('lead_time').value = '';
        document.getElementById('status').value = 'Active';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editSupplier(supplier) {
        document.getElementById('modalTitle').innerText = 'Edit Supplier';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('supplierId').value = supplier.id;
        
        // Changed to supplier_name
        document.getElementById('supplier_name').value = supplier.supplier_name;
        document.getElementById('supplier_code').value = supplier.supplier_code || '';
        document.getElementById('contact_person').value = supplier.contact_person || '';
        document.getElementById('phone').value = supplier.phone || '';
        document.getElementById('address').value = supplier.address || '';
        document.getElementById('lead_time').value = supplier.lead_time || '';
        document.getElementById('status').value = supplier.status || 'Active';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
</script>

<?php require_once '../includes/footer.php'; ?>