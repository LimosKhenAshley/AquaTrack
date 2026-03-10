<?php
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* =============================
DATE FILTER
============================= */
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-d');

/* =============================
REVENUE SUMMARY
============================= */
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) total_payments,
        SUM(amount_paid) total_revenue,
        AVG(amount_paid) avg_payment
    FROM payments
    WHERE DATE(payment_date) BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$revenue = $stmt->fetch();

$totalRevenue = $revenue['total_revenue'] ?? 0;
$totalPayments = $revenue['total_payments'] ?? 0;

/* =============================
EXPECTED REVENUE
============================= */
$stmt = $pdo->prepare("
    SELECT SUM(amount + IFNULL(penalty, 0)) expected
    FROM bills
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$expectedRevenue = $stmt->fetch()['expected'] ?? 0;

/* =============================
COLLECTION PERFORMANCE
============================= */
$stmt = $pdo->prepare("
    SELECT
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) paid_bills,
        SUM(CASE WHEN status = 'unpaid' THEN 1 ELSE 0 END) unpaid_bills,
        COUNT(*) total_bills
    FROM bills
    WHERE DATE(created_at) BETWEEN ? AND ?
");
$stmt->execute([$start, $end]);
$collection = $stmt->fetch();

$collectionRate = $collection['total_bills'] > 0
    ? ($collection['paid_bills'] / $collection['total_bills']) * 100
    : 0;

/* =============================
OUTSTANDING BALANCE
============================= */
$stmt = $pdo->prepare("
    SELECT SUM(amount + IFNULL(penalty, 0)) total
    FROM bills
    WHERE status = 'unpaid'
");
$stmt->execute();
$outstanding = $stmt->fetch()['total'] ?? 0;

/* =============================
PENALTIES COLLECTED
============================= */
$stmt = $pdo->prepare("
    SELECT SUM(penalty) penalties
    FROM bills
    WHERE status = 'paid'
");
$stmt->execute();
$penalties = $stmt->fetch()['penalties'] ?? 0;

/* =============================
MONTHLY REVENUE
============================= */
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') month,
        SUM(amount_paid) total
    FROM payments
    WHERE DATE(payment_date) BETWEEN ? AND ?
    GROUP BY month
    ORDER BY month
");
$stmt->execute([$start, $end]);
$monthly = $stmt->fetchAll();

$months = [];
$totals = [];

foreach ($monthly as $m) {
    $months[] = date('M Y', strtotime($m['month'] . '-01'));
    $totals[] = (float) $m['total'];
}

if (!$months) {
    $months = ['No Data'];
    $totals = [0];
}

/* =============================
PAYMENT METHODS
============================= */
$stmt = $pdo->prepare("
    SELECT method, COUNT(*) total
    FROM payments
    WHERE DATE(payment_date) BETWEEN ? AND ?
    GROUP BY method
");
$stmt->execute([$start, $end]);
$methods = $stmt->fetchAll();

$methodLabels = [];
$methodTotals = [];

foreach ($methods as $m) {
    $methodLabels[] = $m['method'];
    $methodTotals[] = $m['total'];
}

if (!$methodLabels) {
    $methodLabels = ['No Data'];
    $methodTotals = [0];
}

/* =============================
BILL STATUS
============================= */
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) total
    FROM bills
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start, $end]);
$status = $stmt->fetchAll();

$statusLabels = [];
$statusTotals = [];

foreach ($status as $s) {
    $statusLabels[] = $s['status'];
    $statusTotals[] = $s['total'];
}

if (!$statusLabels) {
    $statusLabels = ['No Data'];
    $statusTotals = [0];
}

/* =============================
TOP CUSTOMERS
============================= */
$stmt = $pdo->prepare("
    SELECT u.full_name,
        SUM(p.amount_paid) total
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE DATE(p.payment_date) BETWEEN ? AND ?
    GROUP BY u.id
    ORDER BY total DESC
    LIMIT 5
");
$stmt->execute([$start, $end]);
$top = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <h3 class="mb-1">📊 Financial Reports</h3>
    <p class="text-muted">Generated: <?= date('F d, Y h:i A') ?></p>

    <!-- PRESET BUTTONS -->
    <div class="mb-3">
        <a href="?start=<?= date('Y-m-d') ?>&end=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-primary">Today</a>
        <a href="?start=<?= date('Y-m-01') ?>&end=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-primary">This Month</a>
        <a href="?start=<?= date('Y-01-01') ?>&end=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-primary">This Year</a>
    </div>

    <!-- FILTER -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3">
                <div class="col-md-3">
                    <label>Start Date</label>
                    <input type="date" name="start" class="form-control" value="<?= $start ?>">
                </div>

                <div class="col-md-3">
                    <label>End Date</label>
                    <input type="date" name="end" class="form-control" value="<?= $end ?>">
                </div>

                <div class="col-md-2 align-self-end">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>

                <div class="col-md-2 align-self-end">
                    <a href="export_excel.php?start=<?= $start ?>&end=<?= $end ?>" class="btn btn-success w-100">Excel</a>
                </div>

                <div class="col-md-2 align-self-end">
                    <a href="export_pdf.php?start=<?= $start ?>&end=<?= $end ?>" target="_blank" class="btn btn-danger w-100">PDF</a>
                </div>

                <div class="col-md-2 align-self-end">
                    <button type="button" onclick="window.print()" class="btn btn-secondary w-100">Print</button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI SUMMARY -->
    <div class="alert alert-light border mb-4">
        Collected <strong>₱<?= number_format($totalRevenue, 2) ?></strong>
        from <strong><?= $totalPayments ?></strong> payments between
        <strong><?= date('M d, Y', strtotime($start)) ?></strong>
        and
        <strong><?= date('M d, Y', strtotime($end)) ?></strong>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h6>Total Revenue</h6>
                    <h3>₱<?= number_format($totalRevenue, 2) ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <h6>Total Payments</h6>
                    <h3><?= $totalPayments ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <h6>Expected Revenue</h6>
                    <h3>₱<?= number_format($expectedRevenue, 2) ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow">
                <div class="card-body">
                    <h6>Collection Rate</h6>
                    <h3><?= number_format($collectionRate, 1) ?>%</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- CHARTS -->
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Monthly Revenue</div>
                <div class="card-body">
                    <canvas id="revenueChart" height="120"></canvas>
                    <button class="btn btn-sm btn-outline-secondary mt-2" onclick="downloadChart()">Download Chart</button>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Payment Methods</div>
                <div class="card-body">
                    <canvas id="methodChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Bill Status</div>
                <div class="card-body">
                    <canvas id="statusChart" height="120"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-4 mt-4">
            <div class="card shadow-sm">
                <div class="card-header fw-bold">Top Paying Customers</div>
                <div class="card-body">
                    <?php if (!$top): ?>
                        <div class="text-muted text-center p-3">
                            No payment data
                        </div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($top as $t): ?>
                                <li class="list-group-item d-flex justify-content-between">
                                    <?= htmlspecialchars($t['full_name']) ?>
                                    <span>₱<?= number_format($t['total'], 2) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const revenueChart = new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: <?= json_encode($totals) ?>,
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: { responsive: true }
    });

    new Chart(document.getElementById('methodChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($methodLabels) ?>,
            datasets: [{ data: <?= json_encode($methodTotals) ?> }]
        }
    });

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($statusLabels) ?>,
            datasets: [{ data: <?= json_encode($statusTotals) ?> }]
        }
    });

    function downloadChart() {
        const link = document.createElement('a');
        link.href = revenueChart.toBase64Image();
        link.download = 'revenue_chart.png';
        link.click();
    }
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>