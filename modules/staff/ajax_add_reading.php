<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';
require_once '../../app/helpers/notify.php';



define('RATE_PER_CUBIC_METER', 25);

header('Content-Type: application/json');

$customer_id = $_POST['customer_id'] ?? null;
$reading_value = $_POST['reading_value'] ?? null;
$reading_date = $_POST['reading_date'] ?? null;

if (!$customer_id || !$reading_value || !$reading_date) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

$reading_value = (float) $reading_value;

if ($reading_value <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Reading must be positive']);
    exit;
}

/* =============================
   Prevent duplicate reading per month
============================= */
$monthCheck = $pdo->prepare("
    SELECT COUNT(*) FROM readings
    WHERE customer_id = ?
    AND MONTH(reading_date) = MONTH(?)
    AND YEAR(reading_date) = YEAR(?)
");
$monthCheck->execute([$customer_id, $reading_date, $reading_date]);

if ($monthCheck->fetchColumn() > 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Reading already exists for this month'
    ]);
    exit;
}

/* =============================
   Fetch last reading
============================= */
$stmt = $pdo->prepare("
    SELECT reading_value 
    FROM readings 
    WHERE customer_id = ?
    ORDER BY reading_date DESC 
    LIMIT 1
");
$stmt->execute([$customer_id]);
$lastReading = $stmt->fetch();

$previousValue = $lastReading['reading_value'] ?? 0;

if ($reading_value <= $previousValue) {
    echo json_encode([
        'status' => 'error',
        'message' => "New reading must be greater than previous ({$previousValue})"
    ]);
    exit;
}

/* =============================
   Compute bill
============================= */
$consumption = $reading_value - $previousValue;
$amount = $consumption * RATE_PER_CUBIC_METER;

/* =============================
   Fetch penalty configuration
   (Used ONLY for due date)
============================= */
$cfg = $pdo->query("SELECT grace_days FROM penalty_settings LIMIT 1")->fetch();
$grace_days = $cfg['grace_days'] ?? 10;

try {

    $pdo->beginTransaction();

    /* Insert reading */
    $stmt = $pdo->prepare("
        INSERT INTO readings (customer_id, reading_date, reading_value)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$customer_id, $reading_date, $reading_value]);

    $reading_id = $pdo->lastInsertId();

    /* Insert bill */
    $stmt = $pdo->prepare("
        INSERT INTO bills 
        (customer_id, reading_id, amount, penalty, status, due_date)
        VALUES (?, ?, ?, 0, 'unpaid', DATE_ADD(?, INTERVAL ? DAY))
    ");
    $stmt->execute([
        $customer_id,
        $reading_id,
        $amount,
        $reading_date,
        $grace_days
    ]);

    $pdo->commit();

    /* =============================
    Send notification to customer user account
    ============================= */
    $userStmt = $pdo->prepare("
        SELECT user_id 
        FROM customers 
        WHERE id = ?
    ");
    $userStmt->execute([$customer_id]);
    $user_id = $userStmt->fetchColumn();

    if ($user_id) {
        sendNotification(
            $pdo,
            $user_id,
            "New Meter Reading Recorded",
            "New meter reading of {$reading_value} recorded. Bill amount: ₱" .
            number_format($amount, 2) .
            ". Due date: " .
            date('Y-m-d', strtotime($reading_date . " + {$grace_days} days")) . ".",
            "system"
        );
    }


    auditLog(
        $pdo,
        'ADD_READING',
        "Customer ID: $customer_id | Reading: $reading_value"
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Meter reading and bill generated successfully',
        'reading_value' => $reading_value,
        'reading_date' => $reading_date
    ]);

} catch (Exception $e) {

    $pdo->rollBack();

    echo json_encode([
        'status' => 'error',
        'message' => 'Transaction failed: ' . $e->getMessage()
    ]);
}
