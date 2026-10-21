<?php
// eckose_pos/pages/reports.php
require_once '../includes/auth_check.php';
require_once '../config/database.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

// 1. Set Default Dates
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-d');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$branch_filter = isset($_GET['branch_id']) ? $_GET['branch_id'] : 'All';

// Fetch all branches for the dropdown
$branches = $pdo->query("SELECT id, branch_name FROM branches")->fetchAll();

// 2. Build the Query
$query = "
    SELECT s.*, u.username as cashier_name, c.full_name as customer_name, b.branch_name
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN branches b ON s.branch_id = b.id
    WHERE DATE(s.created_at) BETWEEN ? AND ?
";
$params = [$from_date, $to_date];

if ($branch_filter !== 'All') {
    $query .= " AND s.branch_id = ?";
    $params[] = $branch_filter;
}

$query .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// 3. Calculate Totals
$total_revenue = 0;
$total_transactions = count($sales);
foreach ($sales as $sale) {
    $total_revenue += $sale['total_amount'];
}

require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Print Header (Only visible on paper) -->
<div class="hidden print:block text-center mb-8 border-b-2 border-black pb-4">
    <h1 class="text-3xl font-extrabold text-black uppercase tracking-widest mb-1">Eckose Techs</h1>
    <h2 class="text-xl font-bold text-gray-800">Sales Report</h2>
    <p class="text-sm text-gray-600 mt-2">
        Period: <?= date('M d, Y', strtotime($from_date)) ?> to <?= date('M d, Y', strtotime($to_date)) ?>
    </p>
</div>

<!-- Main Content Area -->
<div class="bg-[#1A1F2B] min-h-screen p-8 rounded-2xl border border-gray-800 shadow-inner text-gray-200 print-container">

    <!-- Screen Only Header -->
    <div class="mb-8 border-b border-gray-700 pb-4 flex justify-between items-end print:hidden">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-wide">Sales Reports</h2>
            <p class="text-gray-400 mt-1">Filter and export transaction data.</p>
        </div>
        <button onclick="window.print()" class="bg-[#2D3748] hover:bg-gray-700 border border-gray-600 text-white text-sm font-bold py-2 px-4 rounded-lg shadow transition flex items-center">
            <i class="fas fa-file-pdf mr-2 text-red-500"></i> Export PDF
        </button>
    </div>

    <!-- Filters (Hidden in print) -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-4 mb-6 print:hidden">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">From Date</label>
                <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>" class="bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-2 focus:outline-none focus:border-orange-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">To Date</label>
                <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>" class="bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-2 focus:outline-none focus:border-orange-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-400 mb-1 uppercase">Branch</label>
                <select name="branch_id" class="bg-[#1A1F2B] border border-gray-600 text-white rounded-lg p-2 focus:outline-none focus:border-orange-500">
                    <option value="All">All Branches</option>
                    <?php foreach($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $branch_filter == $b['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['branch_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-6 rounded-lg shadow transition flex items-center">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
        </form>
    </div>

    <!-- 1. THE DATA TABLE -->
    <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 overflow-hidden print-table-wrapper mb-8">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-300 print-table">
                <thead class="text-xs text-gray-400 uppercase bg-[#1A1F2B] border-b border-gray-700 print-thead">
                    <tr>
                        <th class="px-6 py-4">Date & Time</th>
                        <th class="px-6 py-4">Receipt No</th>
                        <th class="px-6 py-4">Branch</th>
                        <th class="px-6 py-4">Cashier</th>
                        <th class="px-6 py-4">Payment Method</th>
                        <th class="px-6 py-4 text-right">Amount (Ksh)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($sales)): ?>
                        <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500 print-text">No sales found for the selected filters.</td></tr>
                    <?php else: ?>
                        <?php foreach($sales as $sale): ?>
                            <tr class="border-b border-gray-700 hover:bg-[#2D3748] transition print-row">
                                <td class="px-6 py-4 text-xs print-text"><?= date('M d, Y h:i A', strtotime($sale['created_at'])) ?></td>
                                <td class="px-6 py-4 font-mono text-orange-400 font-bold print-text"><?= htmlspecialchars($sale['receipt_no']) ?></td>
                                <td class="px-6 py-4 font-bold text-blue-400 print-text"><?= htmlspecialchars($sale['branch_name'] ?? 'Main HQ') ?></td>
                                <td class="px-6 py-4 print-text"><?= htmlspecialchars($sale['cashier_name'] ?? 'Admin') ?></td>
                                <td class="px-6 py-4 print-text">
                                    <?php 
                                    $method = $sale['payment_method'] ?? 'Cash';
                                    if ($method === 'Mpesa' || $method === 'M-Pesa') { echo 'M-Pesa'; } 
                                    elseif ($method === 'Card') { echo 'Card'; } 
                                    elseif ($method === 'Online Pickup') { echo 'Online Pickup'; } 
                                    else { echo 'Cash'; }
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-white print-text"><?= number_format($sale['total_amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. SUMMARY CARDS (AT THE BOTTOM) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 print-summary-container">
        <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-6 relative overflow-hidden print-card">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1 print-text">Total Revenue</p>
            <p class="text-4xl font-extrabold text-white print-text">Ksh <?= number_format($total_revenue, 2) ?></p>
        </div>
        <div class="bg-[#242B38] rounded-xl shadow-lg border border-gray-700 p-6 relative overflow-hidden print-card">
            <p class="text-gray-400 text-xs font-bold uppercase tracking-wider mb-1 print-text">Total Transactions</p>
            <p class="text-4xl font-extrabold text-white print-text"><?= number_format($total_transactions) ?></p>
        </div>
    </div>

</div>

<!-- THE PRINT FIX STYLESHEET -->
<style>
    @media print {
        /* 1. Force page breaks and allow scrolling */
        html, body {
            height: auto !important;
            overflow: visible !important;
            background: white !important;
        }

        /* 2. Hide screen-only elements */
        #sidebar, aside, header, nav, .print\:hidden {
            display: none !important;
        }

        /* 3. Reset container heights and backgrounds */
        .print-container {
            background: white !important;
            padding: 0 !important;
            margin: 0 !important;
            border: none !important;
            box-shadow: none !important;
            min-height: auto !important; /* Crucial: fixes the 1-page cutoff */
        }

        /* 4. Format the table */
        .print-table-wrapper {
            border: none !important;
            background: white !important;
            overflow: visible !important;
        }

        .print-table { width: 100% !important; border-collapse: collapse !important; }
        .print-thead { background-color: #f0f0f0 !important; border-bottom: 2px solid #000 !important; }

        .print-table th, .print-table td {
            border: 1px solid #000 !important;
            padding: 8px !important;
            color: #000 !important;
            font-size: 12px !important;
        }

        /* 5. Allow table rows to break across pages naturally */
        .print-row {
            page-break-inside: auto !important;
        }

        /* 6. Format Summary Cards */
        .print-summary-container {
            display: flex !important;
            justify-content: space-between !important;
            margin-top: 40px !important;
            page-break-inside: avoid !important;
        }

        .print-card {
            background: white !important;
            border: 2px solid #000 !important;
            padding: 15px !important;
            width: 48% !important;
        }

        /* 7. Force all text to be black */
        .print-text { color: #000 !important; }
    }
</style>

<?php require_once '../includes/footer.php'; ?>