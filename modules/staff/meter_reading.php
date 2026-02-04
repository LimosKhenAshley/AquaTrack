<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/layouts/footer.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $reading_value = $_POST['reading_value'];
    $date = date('Y-m-d');

    $pdo->beginTransaction();

    try {

        $reading = filter_input(INPUT_POST, 'reading_value', FILTER_VALIDATE_FLOAT);

        if ($reading === false || $reading < 0) {
            setFlash('danger', 'Invalid meter reading.');
            header('Location: meter_reading.php');
            exit;
        }
        // 1. Insert reading
        $stmt = $pdo->prepare("INSERT INTO readings (customer_id, reading_date, reading_value)
                            VALUES (?, ?, ?)");
        $stmt->execute([$customer_id, $date, $reading_value]);

        $reading_id = $pdo->lastInsertId();

        // 2. Get latest rate
        $rateStmt = $pdo->query("SELECT rate_per_unit FROM rates ORDER BY effective_from DESC LIMIT 1");
        $rate = $rateStmt->fetch()['rate_per_unit'];

        // 3. Compute bill amount
        $amount = $reading_value * $rate;

        // 4. Insert bill
        $billStmt = $pdo->prepare("INSERT INTO bills (customer_id, reading_id, amount, status)
                                VALUES (?, ?, ?, 'unpaid')");
        $billStmt->execute([$customer_id, $reading_id, $amount]);

        
        $pdo->commit();
        $message = "Reading saved and bill generated! Amount: ₱" . number_format($amount, 2);

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Transaction failed: " . $e->getMessage();
    }

}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Meter Reading</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<div class="container mt-4">

<h3>Add Meter Reading</h3>

<?php if($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST">

    <div class="mb-3">
        <label>Customer</label>
        <select name="customer_id" class="form-control" required>
            <option value="">Select Customer</option>
            <?php
            $customers = $pdo->query("SELECT id, meter_number FROM customers")->fetchAll();
            foreach ($customers as $c) {
                echo "<option value='{$c['id']}'>Meter: {$c['meter_number']}</option>";
            }
            ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Reading Value</label>
        <input type="number" step="0.01" name="reading_value" class="form-control" required>
    </div>

    <button class="btn btn-success">Save Reading</button>
    <a href="dashboard.php" class="btn btn-secondary">Back</a>

</form>

</div>
</body>
</html>
