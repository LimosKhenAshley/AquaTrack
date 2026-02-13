<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* =========================
   CORE STATS (single query)
========================= */
$stats = $pdo->query("
SELECT
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM customers) AS customers,
    (SELECT COUNT(*) FROM staffs) AS staffs,
    (SELECT COUNT(*) FROM bills) AS bills,
    (SELECT COUNT(*) FROM bills WHERE status='unpaid') AS unpaid_bills,
    (SELECT COUNT(*) FROM bills WHERE status='unpaid' AND due_date < NOW()) AS overdue_bills
")->fetch();

/* =========================
   MONEY METRICS
========================= */
$money = $pdo->query("
SELECT
    SUM(CASE WHEN status='paid' THEN amount+penalty ELSE 0 END) AS collected,
    SUM(CASE WHEN status='unpaid' THEN amount+penalty ELSE 0 END) AS outstanding,
    SUM(CASE 
        WHEN status='paid'
        AND MONTH(created_at)=MONTH(CURRENT_DATE())
        AND YEAR(created_at)=YEAR(CURRENT_DATE())
        THEN amount+penalty ELSE 0 END) AS this_month
FROM bills
")->fetch();

/* =========================
   DISCONNECTION RISK
========================= */
$riskCount = 0;
try {
    $riskCount = $pdo->query("
        SELECT COUNT(*) FROM customers 
        WHERE status='for_disconnection'
    ")->fetchColumn();
} catch(Exception $e) {
    $riskCount = 0; // if column not present yet
}

/* =========================
   MONTHLY REVENUE CHART (FIXED)
========================= */
$rev = $pdo->query("
SELECT 
    DATE_FORMAT(created_at, '%Y-%m') AS ym,
    DATE_FORMAT(created_at, '%b %Y') AS label,
    SUM(amount + penalty) AS total
FROM bills
WHERE status = 'paid'
GROUP BY ym, label
ORDER BY ym
")->fetchAll();

/* =========================
   RECENT AUDIT LOGS
========================= */
$logs = [];
try {
    $logs = $pdo->query("
        SELECT action, description, created_at
        FROM audit_logs
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll();
} catch(Exception $e) {
    $logs = [];
}
?>

<div class="container mt-4">

    <h3 class="mb-4">⚙️ Admin Dashboard</h3>
    <!-- =======================
         COUNT CARDS
    ======================== -->
    <div class="row g-3 mb-4">

        <div class="col-md-3">
            <div class="card shadow border-0 bg-info text-white">
                <div class="card-body">
                    <h6>Users</h6>
                    <h3><?= $stats['users'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow border-0 bg-success text-white">
                <div class="card-body">
                    <h6>Customers</h6>
                    <h3><?= $stats['customers'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow border-0 bg-warning text-dark">
                <div class="card-body">
                    <h6>Staff</h6>
                    <h3><?= $stats['staffs'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card shadow border-0 bg-danger text-white">
                <div class="card-body">
                    <h6>Total Bills</h6>
                    <h3><?= $stats['bills'] ?></h3>
                </div>
            </div>
        </div>

    </div>

    <!-- =======================
         MONEY CARDS
    ======================== -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h6>Total Collected</h6>
                    <h4 class="text-success">
                        ₱<?= number_format($money['collected'] ?? 0,2) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h6>Outstanding Balance</h6>
                    <h4 class="text-danger">
                        ₱<?= number_format($money['outstanding'] ?? 0,2) ?>
                    </h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow border-0">
                <div class="card-body">
                    <h6>This Month Revenue</h6>
                    <h4>
                        ₱<?= number_format($money['this_month'] ?? 0,2) ?>
                    </h4>
                </div>
            </div>
        </div>

    </div>

    <!-- =======================
         ALERT CARDS
    ======================== -->
    <div class="row g-3 mb-4">

        <div class="col-md-6">
            <div class="card shadow border-0 bg-dark text-white">
                <div class="card-body">
                    <h6>Overdue Bills</h6>
                    <h3><?= $stats['overdue_bills'] ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow border-0 bg-secondary text-white">
                <div class="card-body">
                    <h6>For Disconnection</h6>
                    <h3><?= $riskCount ?></h3>
                </div>
            </div>
        </div>

    </div>

    <!-- =======================
         REVENUE CHART
    ======================== -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white fw-bold">
            📈 Monthly Revenue Trend
        </div>
        <div class="card-body">
            <canvas id="revChart" height="110"></canvas>
        </div>
    </div>

    <!-- =======================
         RECENT ACTIVITY
    ======================== -->
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">
            📝 Recent System Activity
        </div>
        <div class="card-body">

            <?php if(empty($logs)): ?>
                <p class="text-muted">No audit logs yet.</p>
            <?php else: ?>
                <ul class="list-group">
                    <?php foreach($logs as $l): ?>
                    <li class="list-group-item">
                        <b><?= htmlspecialchars($l['action']) ?></b><br>
                        <small><?= htmlspecialchars($l['description']) ?></small><br>
                        <small class="text-muted"><?= $l['created_at'] ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('revChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($rev,'label')) ?>,
        datasets: [{
            label: 'Revenue',
            data: <?= json_encode(array_column($rev,'total')) ?>,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>