<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard - AquaTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<div class="container mt-4">

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info shadow text-white">
                <div class="card-body">
                    <h5>Total Customers</h5>
                    <?php
                    $c = $pdo->query("SELECT COUNT(*) total FROM customers")->fetch();
                    echo "<h3>{$c['total']}</h3>";
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-warning shadow text-dark">
                <div class="card-body">
                    <h5>Pending Requests</h5>
                    <?php
                    $r = $pdo->query("SELECT COUNT(*) total FROM service_requests WHERE status='pending'")->fetch();
                    echo "<h3>{$r['total']}</h3>";
                    ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-danger shadow text-white">
                <div class="card-body">
                    <h5>Unpaid Bills</h5>
                    <?php
                    $b = $pdo->query("SELECT COUNT(*) total FROM bills WHERE status='unpaid'")->fetch();
                    echo "<h3>{$b['total']}</h3>";
                    ?>
                </div>
            </div>
        </div>
    </div>

    <a href="meter_reading.php" class="btn btn-primary">➕ Add Meter Reading</a>
    <a href="requests.php" class="btn btn-secondary">Service Requests</a>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>