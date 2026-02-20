<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';

if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
    exit;
}

header('Content-Type: application/json');

$bill_id = $_POST['bill_id'] ?? null;
$method  = $_POST['method'] ?? 'cash';

if (!$bill_id) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid bill ID.']);
    exit;
}

/* =============================
   FETCH BILL + PENALTY
============================= */
$stmt = $pdo->prepare("
    SELECT amount, penalty, status
    FROM bills
    WHERE id = ?
");
$stmt->execute([$bill_id]);
$bill = $stmt->fetch();

if (!$bill) {
    echo json_encode(['status' => 'error', 'message' => 'Bill not found.']);
    exit;
}

if ($bill['status'] === 'paid') {
    echo json_encode(['status' => 'error', 'message' => 'Bill already paid.']);
    exit;
}

$amount   = (float)$bill['amount'];
$penalty  = (float)$bill['penalty'];
$total    = $amount + $penalty;

try {
    $pdo->beginTransaction();

    /* =============================
       INSERT PAYMENT (TOTAL)
    ============================= */
    $stmt = $pdo->prepare("
        INSERT INTO payments (bill_id, amount_paid, method)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$bill_id, $total, $method]);

    $payment_id = $pdo->lastInsertId();

    /* =============================
       UPDATE BILL STATUS
    ============================= */
    $stmt = $pdo->prepare("
        UPDATE bills
        SET status = 'paid'
        WHERE id = ?
    ");
    $stmt->execute([$bill_id]);

    $pdo->commit();

    auditLog(
        $pdo,
        'PAYMENT',
        "Bill ID: $bill_id marked as paid. Total Paid: ₱" . number_format($total, 2)
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Payment recorded successfully.',
        'payment_id' => $payment_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'Payment failed.'
    ]);
}