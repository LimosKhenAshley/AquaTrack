<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/libs/fpdf/fpdf.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("No bill selected.");
}

$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.amount,
        b.penalty,
        (b.amount + b.penalty) AS total_amount,
        b.status,
        b.created_at,
        b.due_date,
        u.full_name,
        u.email AS customer_email,
        c.meter_number,
        a.area_name,
        r.reading_value
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN areas a ON c.area_id = a.id
    JOIN readings r ON b.reading_id = r.id
    WHERE b.id = ?
");

$stmt->execute([$id]);
$bill = $stmt->fetch();

if (!$bill) {
    die("Bill not found.");
}

/* =========================
   CREATE PDF
========================= */

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'AquaTrack Water Utility', 0, 1, 'C');

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Official Water Bill', 0, 1, 'C');

$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(50, 8, 'Bill No:', 0, 0);
$pdf->Cell(0, 8, $bill['id'], 0, 1);

$pdf->Cell(0, 8, 'Customer: ' . $bill['full_name'], 0, 1);

$pdf->Cell(0, 8, 'Email: '.$bill['customer_email'], 0, 1);

$pdf->Cell(50, 8, 'Meter No:', 0, 0);
$pdf->Cell(0, 8, $bill['meter_number'], 0, 1);

$pdf->Cell(50, 8, 'Area:', 0, 0);
$pdf->Cell(0, 8, $bill['area_name'], 0, 1);

$pdf->Cell(50, 8, 'Reading:', 0, 0);
$pdf->Cell(0, 8, $bill['reading_value'] . ' m3', 0, 1);

$pdf->Cell(50, 8, 'Bill Date:', 0, 0);
$pdf->Cell(0, 8, date('M d, Y', strtotime($bill['created_at'])), 0, 1);

$pdf->Cell(50, 8, 'Due Date:', 0, 0);
$pdf->Cell(0, 8, date('M d, Y', strtotime($bill['due_date'])), 0, 1);

$pdf->Cell(50, 8, 'Base Amount', 0, 0);
$pdf->Cell(0, 8, 'PHP ' . number_format($bill['amount'], 2), 0, 1);

$pdf->Cell(50, 8, 'Penalty', 0, 0);
$pdf->Cell(0, 8, 'PHP ' . number_format($bill['penalty'], 2), 0, 1);

$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(50, 10, 'Total Due:', 0, 0);
$pdf->Cell(0, 10, 'PHP ' . number_format($bill['amount'] + $bill['penalty'],2), 0, 1);

$pdf->Ln(10);

$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 8, "Please pay your water bill on or before due date to avoid service interruption.\n\nThank you for using AquaTrack Water Utility Services.");

$pdf->Output('I', 'AquaTrack_Bill_'.$bill['id'].'.pdf');
