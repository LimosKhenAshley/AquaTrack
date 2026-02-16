<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/bootstrap.php';

$user_id = $_SESSION['user']['id'];

// Get customer record from customers table and full_name from users table
$stmt = $pdo->prepare("
    SELECT 
        c.id AS customer_id,
        u.full_name
    FROM customers c
    INNER JOIN users u ON c.user_id = u.id
    WHERE c.user_id = ?
");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();


if (!$customer) {
    die("Customer record not found.");
}

$customer_id = $customer['customer_id'];

// Get bills
$bills = $pdo->prepare("
    SELECT b.id, b.amount, b.penalty, (b.amount + b.penalty) AS total_amount, 
           b.status, b.created_at, b.due_date, r.reading_value
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
    LIMIT 3
");

$bills->execute([$customer_id]);
$billList = $bills->fetchAll();

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_bills,
        SUM(CASE WHEN status = 'unpaid' THEN (amount + penalty) ELSE 0 END) AS unpaid_total
    FROM bills
    WHERE customer_id = ?
");

$stmt->execute([$customer_id]);
$summary = $stmt->fetch();

// Monthly usage data (last 6 months)
$usageStmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(r2.reading_date, '%b %Y') AS month,
        (r2.reading_value - IFNULL(r1.reading_value, 0)) AS total_usage
    FROM readings r2
    LEFT JOIN readings r1
        ON r1.customer_id = r2.customer_id
        AND r1.reading_date = (
            SELECT MAX(reading_date)
            FROM readings
            WHERE customer_id = r2.customer_id
              AND reading_date < r2.reading_date
        )
    WHERE r2.customer_id = ?
    ORDER BY r2.reading_date ASC
    LIMIT 6
");

$usageStmt->execute([$customer_id]);
$usageData = $usageStmt->fetchAll();

// Prepare arrays for Chart.js
$months = [];
$usages = [];

foreach ($usageData as $row) {
    $months[] = $row['month'];
    $usages[] = (float)$row['total_usage'];
}


$statusRow = $pdo->prepare("
    SELECT service_status 
    FROM customers 
    WHERE id = ?
");
$statusRow->execute([$customer_id]);
$accountStatus = $statusRow->fetchColumn();

$nextDue = $pdo->prepare("
    SELECT MIN(due_date)
    FROM bills
    WHERE customer_id = ?
    AND status = 'unpaid'
");
$nextDue->execute([$customer_id]);
$nextDueDate = $nextDue->fetchColumn();

$lastPay = $pdo->prepare("
    SELECT MAX(payment_date) FROM payments p
    JOIN bills b ON b.id = p.bill_id
    WHERE b.customer_id = ?
");
$lastPay->execute([$customer_id]);
$lastPaymentDate = $lastPay->fetchColumn();


?>

<div class="container mt-4">

    <h5 class="text-muted">Welcome, <?= htmlspecialchars($customer['full_name']) ?></h5>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Your Bills & Usage</h3>
    </div>

    <div class="row mb-4">

        <!-- Account Status -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h6>Account Status</h6>

                    <?php if($accountStatus === 'active'): ?>
                        <span class="badge bg-success">Active</span>
                    <?php elseif($accountStatus === 'disconnected'): ?>
                        <span class="badge bg-danger">Disconnected</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">
                            <?= htmlspecialchars($accountStatus) ?>
                        </span>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Next Due Date -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6>Next Due Date</h6>
                    <h5>
                        <?= $nextDueDate 
                            ? date('M d, Y', strtotime($nextDueDate))
                            : '—' ?>
                    </h5>
                </div>
            </div>
        </div>

        <!-- Last Payment -->
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6>Last Payment</h6>
                    <h5>
                        <?= $lastPaymentDate 
                            ? date('M d, Y', strtotime($lastPaymentDate))
                            : '—' ?>
                    </h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Total Bills</h6>
                    <h3><?= $summary['total_bills'] ?? 0 ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Unpaid Balance</h6>
                    <h3 class="text-danger">
                        ₱<?= number_format($summary['unpaid_total'] ?? 0, 2) ?>
                    </h3>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered shadow-sm table-striped mt-3">
            <thead class="table-dark">
                <tr>
                    <th>Date</th>
                    <th>Consumption</th>
                    <th>Base Amount (₱)</th>
                    <th>Penalty</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($billList) == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            📭 No bills yet<br>
                            <small>Your water usage will appear once a meter reading is recorded.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach($billList as $bill): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($bill['created_at'])) ?></td>
                        <td><?= $bill['reading_value'] ?> m³</td>
                        <td>₱<?= number_format($bill['amount'], 2) ?></td>
                        <td class="text-danger">
                            ₱<?= number_format($bill['penalty'], 2) ?>
                        </td>

                        <td class="fw-bold">
                            ₱<?= number_format($bill['total_amount'], 2) ?>
                        </td>
                        <td>
                            <?php if ($bill['status'] === 'paid'): ?>
                                <button class="btn btn-secondary btn-sm" disabled>Paid</button>
                            <?php elseif (strtotime($bill['due_date']) < time()) : ?>
                                <button class="btn btn-warning btn-sm text-dark"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payBillModal"
                                    data-bill-id="<?= $bill['id'] ?>"
                                    data-amount="<?= $bill['total_amount'] ?>">
                                    Overdue (Pay Now)
                                </button>
                            <?php else: ?>
                                <button class="btn btn-success btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#payBillModal"
                                    data-bill-id="<?= $bill['id'] ?>"
                                    data-amount="<?= $bill['total_amount'] ?>">
                                    Pay
                                </button>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="bill_pdf.php?id=<?= $bill['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">Download PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-white fw-bold">
            💧 Water Usage (Last 6 Months)
        </div>
        <div class="card-body">
            <canvas id="usageChart" height="120"></canvas>
        </div>
    </div>
</div>

<div class="modal fade" id="payBillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="payBillForm">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Pay Bill</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="payBillId">
                    <div class="mb-3">
                        <label>Amount (₱)</label>
                        <input type="text" class="form-control" id="payBillAmount" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Payment Method</label>
                        <select name="method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div id="payBillMessage"></div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const payBillModal = document.getElementById('payBillModal');

    payBillModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        document.getElementById('payBillId').value = button.getAttribute('data-bill-id');
        document.getElementById('payBillAmount').value = button.getAttribute('data-amount');
    });

    document.getElementById('payBillForm').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);

        fetch('/AquaTrack/modules/customer/ajax_pay_bill.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            const msg = document.getElementById('payBillMessage');

            if (data.status === 'success') {

                const modalInstance = bootstrap.Modal.getInstance(payBillModal);

                payBillModal.addEventListener('hidden.bs.modal', function () {

                    // 🔥 Force cleanup (guaranteed fix)
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';

                    Swal.fire({
                        icon: 'success',
                        title: 'Payment Successful',
                        text: data.message,
                        confirmButtonColor: '#198754'
                    });

                    const btn = document.querySelector(
                        `button[data-bill-id="${formData.get('bill_id')}"]`
                    );

                    if (btn) {
                        const row = btn.closest('tr');
                        row.cells[5].innerHTML =
                            '<button class="btn btn-secondary btn-sm" disabled>Paid</button>';
                    }

                }, { once: true });

                modalInstance.hide();
            } else {
                msg.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('usageChart');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Water Usage (m³)',
            data: <?= json_encode($usages) ?>,
            borderWidth: 2,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Cubic Meters (m³)'
                }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>