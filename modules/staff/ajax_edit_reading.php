<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';

header('Content-Type: application/json');
ini_set('display_errors', 0);

$reading_id = $_POST['reading_id'] ?? null;
$reading_value = $_POST['reading_value'] ?? null;
$reading_date = $_POST['reading_date'] ?? null;

if (!$reading_id || !$reading_value || !$reading_date) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing data'
    ]);
    exit;
}

$reading_value = (float)$reading_value;

if ($reading_value <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Reading must be positive'
    ]);
    exit;
}

/* =============================
   Fetch reading info
============================= */
$stmt = $pdo->prepare("
    SELECT customer_id 
    FROM readings 
    WHERE id = ?
");
$stmt->execute([$reading_id]);
$reading = $stmt->fetch();

if (!$reading) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Reading not found'
    ]);
    exit;
}

$customer_id = $reading['customer_id'];

/* =============================
   Get previous reading
============================= */
$stmt = $pdo->prepare("
    SELECT reading_value
    FROM readings
    WHERE customer_id = ?
    AND id != ?
    ORDER BY reading_date DESC
    LIMIT 1
");
$stmt->execute([$customer_id, $reading_id]);

$prevReading = $stmt->fetch();
$previousValue = $prevReading['reading_value'] ?? 0;

if ($reading_value <= $previousValue) {
    echo json_encode([
        'status' => 'error',
        'message' => "Reading must be greater than previous ({$previousValue})"
    ]);
    exit;
}

/* =============================
   Fetch active rate
============================= */
$stmt = $pdo->query("
    SELECT rate_per_unit
    FROM rates
    WHERE effective_from <= CURDATE()
    ORDER BY effective_from DESC
    LIMIT 1
");

$currentRate = $stmt->fetchColumn();

if (!$currentRate) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No active rate configured.'
    ]);
    exit;
}

/* =============================
   Compute bill
============================= */
$consumption = $reading_value - $previousValue;
$amount = $consumption * $currentRate;

try {

    $pdo->beginTransaction();

    /* Update reading */
    $stmt = $pdo->prepare("
        UPDATE readings
        SET reading_value = ?, reading_date = ?
        WHERE id = ?
    ");
    $stmt->execute([$reading_value, $reading_date, $reading_id]);

    /* Update bill */
    $stmt = $pdo->prepare("
        UPDATE bills
        SET amount = ?, rate_used = ?
        WHERE reading_id = ?
    ");
    $stmt->execute([$amount, $currentRate, $reading_id]);

    $pdo->commit();

    auditLog(
        $pdo,
        'EDIT_READING',
        "Customer ID: $customer_id | Reading: $reading_value"
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Reading updated successfully',
        'reading_id' => $reading_id,
        'reading_value' => $reading_value,
        'reading_date' => $reading_date
    ]);
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'status' => 'error',
        'message' => 'Update failed'
    ]);
    exit;
}