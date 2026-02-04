<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* =============================
   FETCH PAYMENT HISTORY
============================= */
$stmt = $pdo->query("
    SELECT 
        p.id AS payment_id,
        u.full_name,
        c.meter_number,
        p.amount_paid,
        p.method,
        p.payment_date,
        b.id AS bill_id
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    ORDER BY p.payment_date DESC
");

$payments = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <h3 class="mb-3">💰 Payment History</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Meter #</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th width="120">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php foreach($payments as $p): ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                            <td><?= htmlspecialchars($p['full_name']) ?></td>
                            <td><?= htmlspecialchars($p['meter_number']) ?></td>
                            <td>₱<?= number_format($p['amount_paid'],2) ?></td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= ucfirst($p['method']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="../shared/receipt_pdf.php?payment_id=<?= $p['payment_id'] ?>"
                                   class="btn btn-secondary btn-sm"
                                   target="_blank">
                                   Download
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if(count($payments) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No payment records found.
                            </td>
                        </tr>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
