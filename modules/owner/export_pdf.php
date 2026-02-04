<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['owner']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/libs/fpdf/fpdf.php';

$start = $_GET['start'];
$end   = $_GET['end'];

$stmt = $pdo->prepare("
    SELECT
        u.full_name,
        b.amount,
        IFNULL(b.penalty,0) AS penalty,
        p.amount_paid,
        p.method,
        p.payment_date
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE DATE(p.payment_date) BETWEEN ? AND ?
    ORDER BY p.payment_date DESC
");

$stmt->execute([$start,$end]);
$data = $stmt->fetchAll();

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'AquaTrack Financial Report',0,1,'C');

$pdf->SetFont('Arial','',10);
$pdf->Cell(0,8,"From: $start To: $end",0,1,'C');

$pdf->Ln(5);

/* Table Header */
$pdf->SetFont('Arial','B',10);

$headers = ['Customer','Bill','Penalty','Paid','Method','Date'];
$widths = [35,25,25,25,25,35];

foreach($headers as $i=>$header){
    $pdf->Cell($widths[$i],8,$header,1);
}
$pdf->Ln();

/* Table Rows */
$pdf->SetFont('Arial','',9);

foreach($data as $row){

    $pdf->Cell($widths[0],8,$row['full_name'],1);
    $pdf->Cell($widths[1],8,$row['amount'],1);
    $pdf->Cell($widths[2],8,$row['penalty'],1);
    $pdf->Cell($widths[3],8,$row['amount_paid'],1);
    $pdf->Cell($widths[4],8,strtoupper($row['method']),1);
    $pdf->Cell($widths[5],8,$row['payment_date'],1);

    $pdf->Ln();
}

$pdf->Output();
