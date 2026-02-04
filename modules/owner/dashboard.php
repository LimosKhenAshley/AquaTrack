<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['owner']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/bootstrap.php';


// total revenue
$rev = $pdo->query("
    SELECT IFNULL(SUM(amount_paid),0) AS total FROM payments
")->fetch()['total'];

// unpaid bills count
$unpaid = $pdo->query("
    SELECT COUNT(*) AS total FROM bills WHERE status='unpaid'
")->fetch()['total'];

// paid bills count
$paid = $pdo->query("
    SELECT COUNT(*) AS total FROM bills WHERE status='paid'
")->fetch()['total'];

// monthly revenue
$monthly = $pdo->query("
    SELECT MONTH(payment_date) m, SUM(amount_paid) total
    FROM payments
    GROUP BY MONTH(payment_date)
")->fetchAll();

$ownerStats = $pdo->query("
    SELECT 
        SUM(amount) AS total_revenue,
        COUNT(*) AS bills_issued
    FROM bills
    WHERE status='paid'
")->fetch();

?>

<div class="container mt-4">

<div class="row mb-4">

    <div class="col-md-4">
        <div class="card bg-success shadow text-white">
            <div class="card-body">
                <h5>Total Revenue</h5>
                <h3>₱<?= number_format($rev,2) ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-primary shadow text-white">
            <div class="card-body">
                <h5>Paid Bills</h5>
                <h3><?= $paid ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card bg-danger shadow text-white">
            <div class="card-body">
                <h5>Unpaid Bills</h5>
                <h3><?= $unpaid ?></h3>
            </div>
        </div>
    </div>

</div>

<h4>Monthly Revenue</h4>

<div class="table-responsive">
    <table class="table table-hover table-bordered shadow-sm">
        <thead class="table-dark">
            <tr>
                <th>Month</th>
                <th>Total Revenue (₱)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($monthly as $m): ?>
            <tr>
                <td><?= date("F", mktime(0,0,0,$m['m'],1)) ?></td>
                <td><?= number_format($m['total'],2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<canvas id="revenueChart" height="100"></canvas>

<script>
const labels = <?= json_encode(array_map(fn($m)=>date("F", mktime(0,0,0,$m['m'],1)), $monthly)) ?>;
const data = <?= json_encode(array_column($monthly,'total')) ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Monthly Revenue (₱)',
            data: data
        }]
    }
});
</script>

</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>