<?php
// eckose_pos/pages/users.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

// Only Admins can view this page
if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// Handle Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($search) {
    $stmt = $pdo->prepare("
        SELECT u.*, b.branch_name 
        FROM users u 
        LEFT JOIN branches b ON u.branch_id = b.id 
        WHERE u.username LIKE ? OR u.first_name LIKE ? OR u.role LIKE ? 
        ORDER BY u.id DESC
    ");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("
        SELECT u.*, b.branch_name 
        FROM users u 
        LEFT JOIN branches b ON u.branch_id = b.id 
        ORDER BY u.id DESC
    ");
}
$users = $stmt->fetchAll();

// Fetch branches for the dropdown
$branches = $pdo->query("SELECT id, branch_name FROM branches")->fetchAll();

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200">

    <!-- Header with Search -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 border-b border-gray-700 pb-4 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-white">System Users</h2>
            <p class="text-gray-400">Manage staff accounts, roles, and permissions.</p>
        </div>
        <div class="flex gap-3 w-full md:w-auto">
            <form method="GET" class="relative flex-1 md:w-64">
                <i class="fas fa-search absolute left-3 top-3 text-gray-500"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search users..." class="w-full bg-[#242B38] border border-gray-600 text-white pl-10 pr-4 py-2 rounded-lg focus:outline-none focus:border-orange-500">
            </form>
            <button onclick="openModal()" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg shadow-lg transition flex items-center whitespace-nowrap">
                <i class="fas fa-user-plus mr-2"></i> Add User
            </button>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="bg-green-900 border-l-4 border-green-500 text-green-300 p-4 mb-6 rounded shadow-sm">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <!-- Users Table -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700">
                    <tr>
                        <th class="px-6 py-4">User</th>
                        <th class="px-6 py-4">Role & Branch</th>
                        <th class="px-6 py-4 text-center">Permissions</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($users)): ?>
                        <tr><td colspan="5" class="px-6 py-8 text-center text-gray-500">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach($users as $u): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center text-orange-400 border border-gray-600 font-bold">
                                            <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-white text-base">
                                                <?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: htmlspecialchars($u['username']) ?>
                                            </div>
                                            <div class="text-xs text-gray-500">@<?= htmlspecialchars($u['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if($u['role'] === 'Admin'): ?>
                                        <span class="bg-purple-900 text-purple-300 text-xs font-bold px-2 py-1 rounded border border-purple-700">Admin</span>
                                    <?php else: ?>
                                        <span class="bg-blue-900 text-blue-300 text-xs font-bold px-2 py-1 rounded border border-blue-700">Cashier</span>
                                    <?php endif; ?>
                                    <div class="text-xs text-gray-400 mt-1"><i class="fas fa-store mr-1"></i> <?= htmlspecialchars($u['branch_name'] ?? 'All Branches') ?></div>
                                </td>
                                <td class="px-6 py-4 text-center space-y-1">
                                    <?php if($u['can_refund']): ?>
                                        <span class="block text-[10px] bg-gray-800 text-green-400 px-2 py-0.5 rounded border border-gray-600">Refunds</span>
                                    <?php endif; ?>
                                    <?php if($u['can_discount']): ?>
                                        <span class="block text-[10px] bg-gray-800 text-green-400 px-2 py-0.5 rounded border border-gray-600">Discounts</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if(($u['status'] ?? 'Active') === 'Active'): ?>
                                        <span class="bg-green-900 text-green-300 text-xs font-bold px-3 py-1 rounded-full border border-green-700">Active</span>
                                    <?php else: ?>
                                        <span class="bg-red-900 text-red-300 text-xs font-bold px-3 py-1 rounded-full border border-red-700">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center space-x-3">
                                    <button onclick='editUser(<?= json_encode($u) ?>)' class="text-blue-400 hover:text-blue-300 transition"><i class="fas fa-edit"></i></button>
                                    
                                    <?php if($u['id'] != 1): ?>
                                    <form action="../actions/user_action.php" method="POST" class="inline-block" onsubmit="return confirm('Delete this user?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
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
<div id="userModal" class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-sm hidden items-center justify-center z-50 transition-opacity">
    <div class="bg-[#242B38] rounded-xl shadow-2xl w-full max-w-xl mx-4 overflow-hidden border border-gray-700 max-h-[90vh] flex flex-col">
        
        <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center bg-[#1A1F2B] shrink-0">
            <h3 id="modalTitle" class="text-lg font-bold text-white">Add New User</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-red-400 focus:outline-none transition">
                <i class="fas fa-times fa-lg"></i>
            </button>
        </div>
        
        <form action="../actions/user_action.php" method="POST" class="p-6 overflow-y-auto custom-scrollbar">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="userId" value="">

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">First Name *</label>
                    <input type="text" name="first_name" id="first_name" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Faith">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Last Name *</label>
                    <input type="text" name="last_name" id="last_name" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. Wanjiku">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Username *</label>
                    <input type="text" name="username" id="username" required class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="e.g. faithw">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Quick PIN (4 digits)</label>
                    <input type="text" name="pin" id="pin" maxlength="4" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition font-mono tracking-widest" placeholder="e.g. 1234">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Password <span id="pass-helper" class="text-xs text-gray-500">(Leave blank to keep current)</span></label>
                    <input type="password" name="password" id="password" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                    <input type="email" name="email" id="email" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition" placeholder="faith@eckose.com">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Role</label>
                    <select name="role" id="role" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Cashier">Cashier</option>
                        <option value="Admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1">Assigned Branch</label>
                    <select name="branch_id" id="branch_id" class="w-full bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="">All Branches</option>
                        <?php foreach($branches as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-[#1A1F2B] rounded-lg border border-gray-700">
                <div class="space-y-3">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Permissions</p>
                    <div class="flex items-center">
                        <input type="checkbox" name="can_refund" id="can_refund" value="1" class="w-4 h-4 text-orange-500 bg-gray-700 border-gray-600 rounded focus:ring-orange-500">
                        <label for="can_refund" class="ml-2 text-sm text-gray-300">Can process refunds</label>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" name="can_discount" id="can_discount" value="1" class="w-4 h-4 text-orange-500 bg-gray-700 border-gray-600 rounded focus:ring-orange-500">
                        <label for="can_discount" class="ml-2 text-sm text-gray-300">Can apply discounts</label>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Account Status</p>
                    <select name="status" id="status" class="w-full bg-[#242B38] border border-gray-600 text-white rounded-lg p-3 focus:outline-none focus:border-orange-500 transition">
                        <option value="Active">Active</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-700 shrink-0">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 text-white rounded-lg hover:bg-gray-600 transition">Cancel</button>
                <button type="submit" class="px-6 py-2 bg-orange-500 text-white font-bold rounded-lg shadow hover:bg-orange-600 transition">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('userModal');
    
    function openModal() {
        document.getElementById('modalTitle').innerText = 'Add New User';
        document.getElementById('formAction').value = 'add';
        document.getElementById('userId').value = '';
        document.getElementById('first_name').value = '';
        document.getElementById('last_name').value = '';
        document.getElementById('username').value = '';
        document.getElementById('pin').value = '';
        document.getElementById('password').value = '';
        document.getElementById('password').required = true;
        document.getElementById('pass-helper').style.display = 'none';
        document.getElementById('email').value = '';
        document.getElementById('role').value = 'Cashier';
        document.getElementById('branch_id').value = '';
        document.getElementById('can_refund').checked = false;
        document.getElementById('can_discount').checked = false;
        document.getElementById('status').value = 'Active';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function editUser(user) {
        document.getElementById('modalTitle').innerText = 'Edit User';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('userId').value = user.id;
        document.getElementById('first_name').value = user.first_name || '';
        document.getElementById('last_name').value = user.last_name || '';
        document.getElementById('username').value = user.username;
        document.getElementById('pin').value = ''; // Don't show hashed PIN
        document.getElementById('password').value = '';
        document.getElementById('password').required = false;
        document.getElementById('pass-helper').style.display = 'inline';
        document.getElementById('email').value = user.email || '';
        document.getElementById('role').value = user.role;
        document.getElementById('branch_id').value = user.branch_id || '';
        document.getElementById('can_refund').checked = user.can_refund == 1;
        document.getElementById('can_discount').checked = user.can_discount == 1;
        document.getElementById('status').value = user.status || 'Active';
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
</script>

<?php require_once '../includes/footer.php'; ?>