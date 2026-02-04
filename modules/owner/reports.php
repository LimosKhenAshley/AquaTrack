<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['owner']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* =============================
   REVENUE SUMMARY
============================= */
$revenue = $pdo->query("
    SELECT 
        COUNT(*) AS total_payments,
        SUM(amount_paid) AS total_revenue,
        AVG(amount_paid) AS avg_payment
    FROM payments
")->fetch();

/* =============================
   COLLECTION PERFORMANCE
============================= */
$collection = $pdo->query("
    SELECT
        SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) AS paid_bills,
        SUM(CASE WHEN status='unpaid' THEN 1 ELSE 0 END) AS unpaid_bills,
        COUNT(*) AS total_bills
    FROM bills
")->fetch();

$collectionRate = $collection['total_bills'] > 0
    ? ($collection['paid_bills'] / $collection['total_bills']) * 100
    : 0;

/* =============================
   OUTSTANDING BALANCE
============================= */
$outstanding = $pdo->query("
    SELECT 
        SUM(amount + IFNULL(penalty,0)) AS total_unpaid
    FROM bills
    WHERE status='unpaid'
")->fetch()['total_unpaid'] ?? 0;

/* =============================
   MONTHLY REVENUE TREND
============================= */
$monthly = $pdo->query("
    SELECT 
        DATE_FORMAT(payment_date, '%Y-%m') AS month,
        SUM(amount_paid) AS total
    FROM payments
    GROUP BY month
    ORDER BY month
")->fetchAll();

$months = [];
$totals = [];

foreach ($monthly as $m) {
    $months[] = date('M Y', strtotime($m['month'].'-01'));
    $totals[] = (float)$m['total'];
}
?>

<div class="container-fluid px-4 mt-4">

<h3 class="mb-4">📊 Financial Reports</h3>

<?php
$start = $_GET['start'] ?? date('Y-m-01');
$end   = $_GET['end'] ?? date('Y-m-d');
?>

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

            <div class="col-md-3 align-self-end">
                <button class="btn btn-primary w-100">Filter</button>
            </div>

            <div class="col-md-3 align-self-end">
                <a href="export_excel.php?start=<?= $start ?>&end=<?= $end ?>"
                   class="btn btn-success w-100">
                   📊 Export Excel
                </a>
            </div>

            <div class="col-md-3 align-self-end">
                <a href="export_pdf.php?start=<?= $start ?>&end=<?= $end ?>"
                   class="btn btn-danger w-100" target="_blank">
                   📄 Export PDF
                </a>
            </div>

        </form>

    </div>
</div>

<!-- SUMMARY CARDS -->
<div class="row mb-4">

    <div class="col-md-3">
        <div class="card bg-success text-white shadow">
            <div class="card-body">
                <h6>Total Revenue</h6>
                <h3>₱<?= number_format($revenue['total_revenue'] ?? 0,2) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-primary text-white shadow">
            <div class="card-body">
                <h6>Total Payments</h6>
                <h3><?= $revenue['total_payments'] ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-info text-white shadow">
            <div class="card-body">
                <h6>Collection Rate</h6>
                <h3><?= number_format($collectionRate,1) ?>%</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-danger text-white shadow">
            <div class="card-body">
                <h6>Outstanding Balance</h6>
                <h3>₱<?= number_format($outstanding,2) ?></h3>
            </div>
        </div>
    </div>

</div>

<!-- MONTHLY CHART -->
<div class="card shadow-sm">
    <div class="card-header fw-bold">
        Monthly Revenue Trend
    </div>
    <div class="card-body">
        <canvas id="revenueChart" height="110"></canvas>
    </div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('revenueChart'), {
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
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
