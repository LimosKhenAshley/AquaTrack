<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['owner']);

require_once __DIR__ . '/../../app/config/database.php';

$start = $_GET['start'];
$end   = $_GET['end'];

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=financial_report.xls");

/* Fetch Report Data */
$stmt = $pdo->prepare("
    SELECT
        p.id,
        u.full_name,
        b.amount,
        IFNULL(b.penalty,0) AS penalty,
        p.amount_paid,
        p.method,
        p.payment_date
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE DATE(p.payment_date) BETWEEN ? AND ?
    ORDER BY p.payment_date DESC
");

$stmt->execute([$start,$end]);
$data = $stmt->fetchAll();
?>

<table border="1">
<tr>
    <th>Customer</th>
    <th>Bill Amount</th>
    <th>Penalty</th>
    <th>Amount Paid</th>
    <th>Method</th>
    <th>Payment Date</th>
</tr>

<?php foreach($data as $row): ?>
<tr>
    <td><?= $row['full_name'] ?></td>
    <td><?= $row['amount'] ?></td>
    <td><?= $row['penalty'] ?></td>
    <td><?= $row['amount_paid'] ?></td>
    <td><?= strtoupper($row['method']) ?></td>
    <td><?= $row['payment_date'] ?></td>
</tr>
<?php endforeach; ?>

</table>
