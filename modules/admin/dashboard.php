<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']); // Only admin

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM users) AS users,
        (SELECT COUNT(*) FROM customers) AS customers,
        (SELECT COUNT(*) FROM bills WHERE status='unpaid') AS unpaid_bills
")->fetch();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - AquaTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<div class="container mt-4">

    <div class="row">
        <!-- Users Card -->
        <div class="col-md-3">
            <div class="card text-white bg-info shadow mb-3">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
                    $count = $stmt->fetch();
                    echo "<p class='card-text'>{$count['total']} Users</p>";
                    ?>
                </div>
            </div>
        </div>

        <!-- Customers Card -->
        <div class="col-md-3">
            <div class="card text-white bg-success shadow mb-3">
                <div class="card-body">
                    <h5 class="card-title">Customers</h5>
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
                    $count = $stmt->fetch();
                    echo "<p class='card-text'>{$count['total']} Customers</p>";
                    ?>
                </div>
            </div>
        </div>

        <!-- Staff Card -->
        <div class="col-md-3">
            <div class="card text-white bg-warning shadow mb-3">
                <div class="card-body">
                    <h5 class="card-title">Staff</h5>
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM staffs");
                    $count = $stmt->fetch();
                    echo "<p class='card-text'>{$count['total']} Staff</p>";
                    ?>
                </div>
            </div>
        </div>

        <!-- Bills Card -->
        <div class="col-md-3">
            <div class="card text-white bg-danger shadow mb-3">
                <div class="card-body">
                    <h5 class="card-title">Bills</h5>
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bills");
                    $count = $stmt->fetch();
                    echo "<p class='card-text'>{$count['total']} Bills</p>";
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>