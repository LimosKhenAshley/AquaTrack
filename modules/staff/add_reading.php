<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

define('RATE_PER_CUBIC_METER', 25);

$message = '';
$error = '';

$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
    die("Customer not specified.");
}

/* =============================
   FETCH CUSTOMER INFO
============================= */
$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.meter_number,
        u.full_name
    FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer not found.");
}

/* =============================
   FETCH LAST READING
============================= */
$stmt = $pdo->prepare("
    SELECT reading_value, reading_date
    FROM readings
    WHERE customer_id = ?
    ORDER BY reading_date DESC
    LIMIT 1
");
$stmt->execute([$customer_id]);
$lastReading = $stmt->fetch();

$previousValue = $lastReading['reading_value'] ?? 0;

/* =============================
   HANDLE SUBMISSION
============================= */
if (isset($_POST['save_reading'])) {

    $newReading = (float) $_POST['reading_value'];
    $readingDate = $_POST['reading_date'];

    // Prevent same-month duplicate
    $monthCheck = $pdo->prepare("
        SELECT COUNT(*) FROM readings
        WHERE customer_id = ?
        AND MONTH(reading_date) = MONTH(?)
        AND YEAR(reading_date) = YEAR(?)
    ");
    $monthCheck->execute([$customer_id, $readingDate, $readingDate]);

    if ($monthCheck->fetchColumn() > 0) {
        $error = "A reading already exists for this month.";
    } elseif ($newReading <= $previousValue) {
        $error = "New reading must be greater than the previous reading ({$previousValue}).";
    } else {

        $consumption = $newReading - $previousValue;
        $amount = $consumption * RATE_PER_CUBIC_METER;

        try {
            $pdo->beginTransaction();

            // Insert reading
            $stmt = $pdo->prepare("
                INSERT INTO readings (customer_id, reading_date, reading_value)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$customer_id, $readingDate, $newReading]);
            $reading_id = $pdo->lastInsertId();

            // Insert bill
            $stmt = $pdo->prepare("
                INSERT INTO bills (customer_id, reading_id, amount, status)
                VALUES (?, ?, ?, 'unpaid')
            ");
            $stmt->execute([$customer_id, $reading_id, $amount]);

            $pdo->commit();
            $message = "Meter reading and bill generated successfully.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}
?>

<div class="container mt-4">
    <h3>➕ Add Meter Reading</h3>

    <div class="card shadow-sm mt-3">
        <div class="card-body">

            <p><strong>Customer:</strong> <?= htmlspecialchars($customer['full_name']) ?></p>
            <p><strong>Meter Number:</strong> <?= htmlspecialchars($customer['meter_number']) ?></p>
            <p><strong>Last Reading:</strong> <?= $previousValue ?></p>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label>Reading Date</label>
                    <input type="date" name="reading_date"
                           class="form-control"
                           value="<?= date('Y-m-d') ?>"
                           required>
                </div>

                <div class="mb-3">
                    <label>New Meter Reading</label>
                    <input type="number"
                           step="0.01"
                           name="reading_value"
                           class="form-control"
                           required>
                </div>

                <button name="save_reading" class="btn btn-success">
                    Save Reading & Generate Bill
                </button>

                <a href="readings.php" class="btn btn-secondary ms-2">
                    Back
                </a>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>