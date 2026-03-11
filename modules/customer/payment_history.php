<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

/* Get Customer ID */
$stmt = $pdo->prepare("
    SELECT id FROM customers WHERE user_id = ?
");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer record not found.");
}

$customer_id = $customer['id'];

/* =============================
   FETCH CUSTOMER PAYMENTS
============================= */
$stmt = $pdo->prepare("
    SELECT 
        p.id AS payment_id,
        p.amount_paid,
        p.method,
        p.payment_date,
        b.id AS bill_id,
        b.status AS bill_status
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    WHERE b.customer_id = ?
    ORDER BY p.payment_date DESC
");

$stmt->execute([$customer_id]);
$payments = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <h3 class="mb-3">💳 My Payment History</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th width="120">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php foreach($payments as $p): ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                            <td>₱<?= number_format($p['amount_paid'],2) ?></td>
                            <td>
                                <?php if($p['bill_status'] === 'paid'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php elseif($p['bill_status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info text-dark">
                                    <?= ucfirst($p['method']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($p['bill_status'] === 'paid'): ?>

                                    <a href="../shared/receipt_pdf.php?payment_id=<?= $p['payment_id'] ?>"
                                    class="btn btn-success btn-sm"
                                    target="_blank">
                                    Download
                                    </a>

                                <?php elseif($p['bill_status'] === 'pending'): ?>

                                    <button class="btn btn-warning btn-sm" disabled>
                                        Pending
                                    </button>

                                <?php else: ?>

                                    <button class="btn btn-secondary btn-sm" disabled>
                                        Not Available
                                    </button>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if(count($payments) === 0): ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No payment history yet.
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
