<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/libs/fpdf/fpdf.php';

$payment_id = isset($_GET['payment_id']) ? (int) $_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    die("Invalid payment.");
}

if ($_SESSION['user']['role'] === 'customer') {

    $user_id = $_SESSION['user']['id'];

    $stmt = $pdo->prepare("
        SELECT c.user_id
        FROM payments p
        JOIN bills b ON p.bill_id = b.id
        JOIN customers c ON b.customer_id = c.id
        WHERE p.id = ?
    ");

    $stmt->execute([$payment_id]);
    $owner = $stmt->fetch();

    if (!$owner || $owner['user_id'] != $user_id) {
        die("Unauthorized.");
    }
}

/* =============================
   FETCH RECEIPT DATA
============================= */
$stmt = $pdo->prepare("
    SELECT 
        p.id AS payment_id,
        p.amount_paid,
        p.method,
        p.payment_date,

        b.amount AS bill_amount,
        b.penalty,
        b.due_date,

        r.reading_value,
        r.reading_date,

        u.full_name,
        c.meter_number

    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN readings r ON b.reading_id = r.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id

    WHERE p.id = ?
");

$stmt->execute([$payment_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Receipt not found.");
}

/* =============================
   PREP DATA
============================= */
$penalty = $data['penalty'] ?? 0;
$total = $data['bill_amount'] + $penalty;

/* =============================
   CREATE PDF
============================= */
$pdf = new FPDF();
$pdf->AddPage();

/* ---------- HEADER ---------- */
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'AquaTrack Water Utility',0,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,6,'Official Payment Receipt',0,1,'C');
$pdf->Ln(5);

/* ---------- CUSTOMER INFO ---------- */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Customer Details',0,1);

$pdf->SetFont('Arial','',11);

$pdf->Cell(50,7,'Customer Name:',0);
$pdf->Cell(0,7,$data['full_name'],0,1);

$pdf->Cell(50,7,'Meter Number:',0);
$pdf->Cell(0,7,$data['meter_number'],0,1);

$pdf->Ln(5);

/* ---------- BILL DETAILS ---------- */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Billing Details',0,1);

$pdf->SetFont('Arial','',11);

$pdf->Cell(50,7,'Reading Date:',0);
$pdf->Cell(0,7,date('M d, Y', strtotime($data['reading_date'])),0,1);

$pdf->Cell(50,7,'Consumption:',0);
$pdf->Cell(0,7,$data['reading_value'].' m³',0,1);

$pdf->Cell(50,7,'Due Date:',0);
$pdf->Cell(0,7,date('M d, Y', strtotime($data['due_date'])),0,1);

$pdf->Ln(5);

/* ---------- PAYMENT BREAKDOWN ---------- */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Payment Breakdown',0,1);

$pdf->SetFont('Arial','',11);

$pdf->Cell(80,7,'Bill Amount',1);
$pdf->Cell(0,7,'PHP '.number_format($data['bill_amount'],2),1,1);

$pdf->Cell(80,7,'Penalty',1);
$pdf->Cell(0,7,'PHP '.number_format($penalty,2),1,1);

$pdf->Cell(80,7,'Total Due',1);
$pdf->Cell(0,7,'PHP '.number_format($total,2),1,1);

$pdf->Cell(80,7,'Amount Paid',1);
$pdf->Cell(0,7,'PHP '.number_format($data['amount_paid'],2),1,1);

$pdf->Ln(5);

/* ---------- PAYMENT INFO ---------- */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,'Payment Information',0,1);

$pdf->SetFont('Arial','',11);

$pdf->Cell(50,7,'Payment Method:',0);
$pdf->Cell(0,7,ucfirst($data['method']),0,1);

$pdf->Cell(50,7,'Payment Date:',0);
$pdf->Cell(0,7,date('M d, Y h:i A', strtotime($data['payment_date'])),0,1);

$pdf->Ln(15);

/* ---------- FOOTER ---------- */
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0,6,'Thank you for your payment!',0,1,'C');

/* OUTPUT PDF */
$pdf->Output("Receipt_".$payment_id.".pdf","I");
