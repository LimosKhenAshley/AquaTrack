<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';

/* =====================================================
   EXPORT PAYMENTS — no Composer, no libraries
   Uses the "Excel-compatible HTML" method (.xls)
   Opens natively in Excel, LibreOffice, WPS, etc.
=====================================================*/

$search    = trim($_GET['search']    ?? '');
$method    = $_GET['method']         ?? '';
$date_from = $_GET['date_from']      ?? '';
$date_to   = $_GET['date_to']        ?? '';

/* ── build query ── */
$where  = [];
$params = [];

if ($search) {
    $where[]  = "(u.full_name LIKE ? OR c.meter_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($method) {
    $where[]  = "p.method = ?";
    $params[] = $method;
}
if ($date_from) {
    $where[]  = "DATE(p.payment_date) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where[]  = "DATE(p.payment_date) <= ?";
    $params[] = $date_to;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

$sql = "
    SELECT
        p.id           AS payment_id,
        u.full_name,
        c.meter_number,
        p.amount_paid,
        p.method,
        p.payment_date
    FROM payments p
    JOIN bills b     ON p.bill_id     = b.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u     ON c.user_id     = u.id
    $whereSQL
    ORDER BY p.payment_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── filter summary label ── */
$filterParts = [];
if ($search)    $filterParts[] = "Search: \"$search\"";
if ($method)    $filterParts[] = "Method: " . ucfirst($method);
if ($date_from) $filterParts[] = "From: $date_from";
if ($date_to)   $filterParts[] = "To: $date_to";
$filterLabel = $filterParts ? implode('  |  ', $filterParts) : 'All records';

/* ── totals ── */
$totalAmount = array_sum(array_column($payments, 'amount_paid'));
$totalCount  = count($payments);

/* ── stream headers ── */
$filename = 'payments_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

/* ── output HTML table (Excel reads this perfectly) ── */
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta charset="UTF-8">
<!--[if gte mso 9]>
<xml>
  <x:ExcelWorkbook>
    <x:ExcelWorksheets>
      <x:ExcelWorksheet>
        <x:Name>Payment History</x:Name>
        <x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions>
      </x:ExcelWorksheet>
    </x:ExcelWorksheets>
  </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
    body { font-family: Arial; font-size: 11pt; }

    .title {
        font-size: 14pt;
        font-weight: bold;
        background-color: #212529;
        color: #FFFFFF;
        text-align: center;
        padding: 6px;
    }
    .subtitle {
        font-size: 9pt;
        color: #555555;
        font-style: italic;
        padding: 3px 6px;
    }
    .timestamp {
        font-size: 8pt;
        color: #888888;
        padding: 2px 6px 6px;
    }

    th {
        background-color: #343A40;
        color: #FFFFFF;
        font-weight: bold;
        text-align: center;
        border: 1px solid #CCCCCC;
        padding: 6px 10px;
        font-size: 11pt;
    }

    td {
        border: 1px solid #DEE2E6;
        padding: 5px 10px;
        font-size: 10pt;
        vertical-align: middle;
    }

    .even  { background-color: #F8F9FA; }
    .odd   { background-color: #FFFFFF; }

    .amount { text-align: right; mso-number-format:'\#\,\#\#0\.00'; }
    .center { text-align: center; }

    .total-row td {
        background-color: #E9ECEF;
        font-weight: bold;
        border: 2px solid #999999;
    }

    .badge-cash   { color: #155724; background-color: #D4EDDA; padding: 2px 8px; border-radius: 4px; }
    .badge-online { color: #004085; background-color: #CCE5FF; padding: 2px 8px; border-radius: 4px; }
    .badge-bank   { color: #856404; background-color: #FFF3CD; padding: 2px 8px; border-radius: 4px; }
    .badge-other  { color: #383D41; background-color: #E2E3E5; padding: 2px 8px; border-radius: 4px; }
</style>
</head>
<body>
<table>

    <!-- Title -->
    <tr>
        <td colspan="6" class="title">Payment History Report</td>
    </tr>

    <!-- Filter summary -->
    <tr>
        <td colspan="6" class="subtitle">Filters — <?= htmlspecialchars($filterLabel) ?></td>
    </tr>

    <!-- Timestamp -->
    <tr>
        <td colspan="6" class="timestamp">Exported: <?= date('F d, Y h:i A') ?></td>
    </tr>

    <!-- Blank spacer -->
    <tr><td colspan="6" style="border:none;">&nbsp;</td></tr>

    <!-- Header row -->
    <tr>
        <th>Date</th>
        <th>Customer Name</th>
        <th>Meter #</th>
        <th>Amount Paid (&#8369;)</th>
        <th>Method</th>
        <th>Payment ID</th>
    </tr>

    <!-- Data rows -->
    <?php foreach ($payments as $i => $p):
        $rowClass = ($i % 2 === 0) ? 'odd' : 'even';
        $badgeClass = match ($p['method']) {
            'cash'   => 'badge-cash',
            'online' => 'badge-online',
            'bank'   => 'badge-bank',
            default  => 'badge-other'
        };
    ?>
    <tr class="<?= $rowClass ?>">
        <td class="center"><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
        <td><?= htmlspecialchars($p['full_name']) ?></td>
        <td class="center"><?= htmlspecialchars($p['meter_number']) ?></td>
        <td class="amount"><?= number_format($p['amount_paid'], 2) ?></td>
        <td class="center"><span class="<?= $badgeClass ?>"><?= ucfirst($p['method']) ?></span></td>
        <td class="center"><?= (int) $p['payment_id'] ?></td>
    </tr>
    <?php endforeach; ?>

    <?php if (!$payments): ?>
    <tr>
        <td colspan="6" style="text-align:center; color:#888; padding:12px;">
            No payment records found.
        </td>
    </tr>
    <?php endif; ?>

    <!-- Blank spacer -->
    <tr><td colspan="6" style="border:none;">&nbsp;</td></tr>

    <!-- Totals row -->
    <tr class="total-row">
        <td colspan="3">TOTAL (<?= $totalCount ?> record<?= $totalCount !== 1 ? 's' : '' ?>)</td>
        <td class="amount">&#8369;<?= number_format($totalAmount, 2) ?></td>
        <td colspan="2"></td>
    </tr>

</table>
</body>
</html>