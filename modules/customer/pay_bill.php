<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetch()['id'];

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bill_id = $_POST['bill_id'];
    $amount = $_POST['amount'];

    $pdo->beginTransaction();

    try {

        // insert payment
        $stmt = $pdo->prepare("INSERT INTO payments (bill_id, amount_paid, method)
                               VALUES (?, ?, 'online')");
        $stmt->execute([$bill_id, $amount]);

        // update bill status
        $stmt = $pdo->prepare("UPDATE bills SET status='paid' WHERE id=?");
        $stmt->execute([$bill_id]);

        $pdo->commit();
        $message = "Payment successful!";

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Payment failed.";
    }
}

// unpaid bills
$bills = $pdo->prepare("
    SELECT id, amount FROM bills
    WHERE customer_id = ? AND status='unpaid'
");
$bills->execute([$customer_id]);
$unpaid = $bills->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Pay Bill - AquaTrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>

<div class="container mt-4">

<h3>Pay Your Bill</h3>

<?php if($message): ?>
<div class="alert alert-info"><?= $message ?></div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
    <label>Select Bill</label>
    <select name="bill_id" class="form-control" required onchange="updateAmount(this)">
        <option value="">Select Bill</option>
        <?php foreach($unpaid as $b): ?>
            <option value="<?= $b['id'] ?>" data-amount="<?= $b['amount'] ?>">
                Bill #<?= $b['id'] ?> - ₱<?= number_format($b['amount'],2) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="mb-3">
    <label>Amount</label>
    <input type="text" id="amount" name="amount" class="form-control" readonly>
</div>

<button class="btn btn-success">Pay Now</button>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

</form>

</div>

<script>
function updateAmount(sel){
    let amt = sel.options[sel.selectedIndex].getAttribute("data-amount");
    document.getElementById("amount").value = amt;
}
</script>

</body>
</html>
