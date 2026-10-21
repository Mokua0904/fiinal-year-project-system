<?php
// eckose_pos/actions/export_pdf.php
session_start();
require_once '../config/database.php';

// Ensure TCPDF is loaded (Adjust the path if you saved it differently!)
require_once '../assets/tcpdf/tcpdf.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized access.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $end_date = $_POST['end_date'] ?? date('Y-m-d');

    // Fetch the data exactly like we did in the UI
    $stmt = $pdo->prepare("
        SELECT s.receipt_no, s.total_amount, s.payment_method, s.created_at, 
               u.username AS cashier, b.branch_name
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN branches b ON s.branch_id = b.id
        WHERE DATE(s.created_at) >= ? AND DATE(s.created_at) <= ?
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $sales = $stmt->fetchAll();

    // Calculate Totals
    $total_revenue = 0;
    foreach ($sales as $sale) {
        $total_revenue += $sale['total_amount'];
    }

    // Initialize TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator('Eckose Techs POS');
    $pdf->SetAuthor('Admin');
    $pdf->SetTitle('Sales Report - ' . $start_date . ' to ' . $end_date);

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Add a page
    $pdf->AddPage();

    // Build the HTML content for the PDF
    $html = '
    <h1 style="text-align:center; color:#1e3a8a;">Eckose Techs POS</h1>
    <h3 style="text-align:center; color:#4b5563;">Sales Report</h3>
    <p style="text-align:center;"><strong>Period:</strong> ' . $start_date . ' to ' . $end_date . '</p>
    <hr><br>
    <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr style="background-color:#f3f4f6; font-weight:bold;">
            <th width="20%">Date</th>
            <th width="25%">Receipt No</th>
            <th width="20%">Branch</th>
            <th width="15%">Payment</th>
            <th width="20%" align="right">Amount (Ksh)</th>
        </tr>';

    if (empty($sales)) {
        $html .= '<tr><td colspan="5" align="center">No sales found in this period.</td></tr>';
    } else {
        foreach ($sales as $s) {
            $html .= '<tr>
                <td width="20%">' . date('M d, Y', strtotime($s['created_at'])) . '</td>
                <td width="25%">' . $s['receipt_no'] . '</td>
                <td width="20%">' . ($s['branch_name'] ?? 'N/A') . '</td>
                <td width="15%">' . $s['payment_method'] . '</td>
                <td width="20%" align="right">' . number_format($s['total_amount'], 2) . '</td>
            </tr>';
        }
    }

    $html .= '
        <tr style="background-color:#eff6ff; font-weight:bold;">
            <td colspan="4" align="right">TOTAL REVENUE:</td>
            <td align="right">Ksh ' . number_format($total_revenue, 2) . '</td>
        </tr>
    </table>';

    // Print text using writeHTMLCell()
    $pdf->writeHTML($html, true, false, true, false, '');

    // Close and output PDF document to the browser
    // 'I' means inline (opens in browser tab), 'D' means force download
    $pdf->Output('Sales_Report_' . $start_date . '.pdf', 'I');
    exit();
}
?>