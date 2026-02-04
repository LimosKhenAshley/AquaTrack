<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';
define('RATE_PER_CUBIC_METER', 25);

header('Content-Type: application/json');

$reading_id = $_POST['reading_id'] ?? null;
$reading_value = $_POST['reading_value'] ?? null;
$reading_date = $_POST['reading_date'] ?? null;

if (!$reading_id || !$reading_value || !$reading_date) {
    echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    exit;
}

$reading_value = (float)$reading_value;

// Fetch reading info
$stmt = $pdo->prepare("SELECT customer_id FROM readings WHERE id = ?");
$stmt->execute([$reading_id]);
$reading = $stmt->fetch();
if (!$reading) {
    echo json_encode(['status' => 'error', 'message' => 'Reading not found']);
    exit;
}

$customer_id = $reading['customer_id'];
$amount = $reading_value * RATE_PER_CUBIC_METER;

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE readings SET reading_value = ?, reading_date = ? WHERE id = ?");
    $stmt->execute([$reading_value, $reading_date, $reading_id]);

    $stmt = $pdo->prepare("UPDATE bills SET amount = ? WHERE reading_id = ?");
    $stmt->execute([$amount, $reading_id]);

    $pdo->commit();

    auditLog(
        $pdo,
        'EDIT_READING',
        "Customer ID: $customer_id | Reading: $reading_value"
    );
    echo json_encode(['status' => 'success', 'message' => 'Reading updated successfully', 'reading_value' => $reading_value, 'reading_date' => $reading_date]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
}
