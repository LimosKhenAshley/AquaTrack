<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';


header('Content-Type: application/json');

$bill_id = $_POST['bill_id'] ?? null;
$method  = $_POST['method'] ?? 'cash';

if (!$bill_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid bill ID.']);
    exit;
}

/* =============================
   FETCH BILL AMOUNT
============================= */
$stmt = $pdo->prepare("SELECT amount FROM bills WHERE id = ?");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if (!$bill) {
    echo json_encode(['status' => 'error', 'message' => 'Bill not found.']);
    exit;
}

$amount = $bill['amount'];

try {
    $pdo->beginTransaction();

    // Insert payment
    $stmt = $pdo->prepare("
        INSERT INTO payments (bill_id, amount_paid, method)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$bill_id, $amount, $method]);

    // Update bill status
    $stmt = $pdo->prepare("
        UPDATE bills SET status='paid' WHERE id = ?
    ");
    $stmt->execute([$bill_id]);

    $pdo->commit();

    auditLog(
        $pdo,
        'PAYMENT',
        "Bill ID: $bill_id marked as paid"
    );

    echo json_encode(['status' => 'success', 'message' => 'Payment recorded successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Payment failed.']);
}
