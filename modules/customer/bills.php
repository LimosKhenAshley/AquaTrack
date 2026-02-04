<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/bootstrap.php';

$user_id = $_SESSION['user']['id'];

/* =============================
   FETCH CUSTOMER
============================= */
$stmt = $pdo->prepare("
    SELECT id 
    FROM customers 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetchColumn();

if (!$customer_id) {
    die('Customer record not found.');
}

/* =============================
   FETCH BILLS
============================= */
$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.amount,
        b.penalty,
        (b.amount + b.penalty) AS total_amount,
        b.status,
        b.created_at,
        b.due_date,
        r.reading_value,
        r.reading_date
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$customer_id]);
$bills = $stmt->fetchAll();
?>

<div class="col-md-10 p-4">

    <h3 class="mb-4">🧾 My Bills</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Reading</th>
                            <th>Base Amount</th>
                            <th>Penalty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th width="160">Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php if (count($bills) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                📭 No bills available yet
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($bill['created_at'])) ?></td>
                            <td><?= number_format($bill['reading_value'], 2) ?> m³</td>
                            <td>₱<?= number_format($bill['amount'], 2) ?></td>
                            <td class="text-danger">
                                ₱<?= number_format($bill['penalty'], 2) ?>
                            </td>
                            <td class="fw-bold">
                                ₱<?= number_format($bill['total_amount'], 2) ?>
                            </td>
                            <td>
                                <?php if ($bill['status'] === 'paid'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php elseif (strtotime($bill['due_date']) < time()) : ?>
                                    <span class="badge bg-warning text-dark">Overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="bill_pdf.php?id=<?= $bill['id'] ?>" 
                                   class="btn btn-sm btn-secondary"
                                   target="_blank">
                                    📄 PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
