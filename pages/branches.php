<?php
// eckose_pos/pages/branches.php
require_once '../includes/auth_check.php';

// Only Admins can manage branches
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

require_once '../config/database.php';

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM branches WHERE branch_name LIKE ? OR address LIKE ? OR manager_name LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM branches ORDER BY id DESC");
}
$branches = $stmt->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">

    <!-- Header with Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-gray-700 pb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">Branch Management</h2>
            <p class="text-gray-400">Manage your store locations, managers, and HQ controls.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search branches..." class="w-full bg-[#242B38] border border-gray-600 text-white pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:border-orange-500">
            </form>
            <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg transition flex items-center whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i> Add Branch
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="bg-green-900 border-l-4 border-green-500 text-green-300 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Branch Table -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">Branch / Manager</th>
                        <th class="px-6 py-4">Contact / Address</th>
                        <th class="px-6 py-4 text-center">Type</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4">Date Added</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($branches)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No branches found. Click "Add Branch" to get started.</td></tr>
                    <?php else: ?>
                        <?php foreach($branches as $b): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white text-base"><?= htmlspecialchars($b['branch_name']) ?></div>
                                    <?php if(!empty($b['manager_name'])): ?>
                                        <div class="text-xs text-orange-400 mt-1"><i class="fas fa-user-tie mr-1"></i> <?= htmlspecialchars($b['manager_name']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-gray-300"><?= htmlspecialchars($b['address'] ?? 'N/A') ?></div>
                                    <?php if(!empty($b['phone'])): ?>
                                        <div class="text-xs text-gray-500 font-mono mt-1"><i class="fas fa-phone mr-1"></i> <?= htmlspecialchars($b['phone']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(($b['branch_type'] ?? 'Branch') === 'HQ'): ?>
                                        <span class="bg-purple-900 text-purple-300 text-xs font-bold px-3 py-1 rounded-full border border-purple-700">
                                            <i class="fas fa-crown mr-1"></i> HQ
                                        </span>
                                    <?php else: ?>
                                        <span class="bg-blue-900 text-blue-300 text-xs font-bold px-3 py-1 rounded-full border border-blue-700">
                                            <i class="fas fa-store mr-1"></i> Branch
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(($b['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="bg-green-900 text-green-300 text-xs font-bold px-3 py-1 rounded-full border border-green-700">Active</span>
                                    <?php else: ?>
                                        <span class="bg-red-900 text-red-300 text-xs font-bold px-3 py-1 rounded-full border border-red-700">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs"><?= date('M d, Y', strtotime($b['created_at'])) ?></td>
                                <td class="px-6 py-4 text-center space-x-3">
                                    <button onclick='editBranch(<?= json_encode($b) ?>)' class="text-blue-400 hover:text-blue-300 transition"><i class="fas fa-edit"></i></button>
                                    <?php if($b['id'] != 1): ?>
                                    <form action="../actions/branch_action.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this branch?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
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
<div id="branchModal" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-[#242B38] rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden border border-gray-700">
        
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-[#1A1F2B]">
            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New Branch</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-400 focus:outline-none transition">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        
        <form action="../actions/branch_action.php" method="POST" class="p-6">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="branchId" value="">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Branch Name *</label>
                    <input type="text" name="branch_name" id="branch_name" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Thika Branch">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Branch Type</label>
                    <select name="branch_type" id="branch_type" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Branch">Standard Branch</option>
                        <option value="HQ">Headquarters (HQ)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Branch Phone</label>
                    <input type="text" name="phone" id="phone" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition font-mono" placeholder="07XX XXX XXX">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Branch Manager</label>
                    <input type="text" name="manager_name" id="manager_name" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Faith W.">
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-1">Physical Address</label>
                <input type="text" name="address" id="address" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Thika, Kenya">
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                    <select name="status" id="status" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive / Temporarily Closed</option>
                    </select>
                </div>
                <div class="flex items-center mt-6">
                    <input type="checkbox" name="require_cash_count" id="require_cash_count" value="1" class="w-4 h-4 text-orange-500 bg-gray-700 border-gray-600 rounded focus:ring-orange-500">
                    <label for="require_cash_count" class="ml-2 text-sm text-gray-300">Require Cash Reconciliation</label>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-orange-500 text-white font-bold rounded-lg shadow hover:bg-orange-600 transition">Save Branch</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('branchModal');
    
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Add New Branch';
        document.getElementById('formAction').value = 'add';
        document.getElementById('branchId').value = '';
        document.getElementById('branch_name').value = '';
        document.getElementById('address').value = '';
        document.getElementById('branch_type').value = 'Branch';
        document.getElementById('phone').value = '';
        document.getElementById('manager_name').value = '';
        document.getElementById('status').value = 'Active';
        document.getElementById('require_cash_count').checked = false;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editBranch(branch) {
        document.getElementById('modalTitle').innerText = 'Edit Branch';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('branchId').value = branch.id;
        document.getElementById('branch_name').value = branch.branch_name;
        document.getElementById('address').value = branch.address || '';
        document.getElementById('branch_type').value = branch.branch_type || 'Branch';
        document.getElementById('phone').value = branch.phone || '';
        document.getElementById('manager_name').value = branch.manager_name || '';
        document.getElementById('status').value = branch.status || 'Active';
        document.getElementById('require_cash_count').checked = branch.require_cash_count == 1;
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
</script>

<?php require_once '../includes/footer.php'; ?>