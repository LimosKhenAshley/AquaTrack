<?php

function generateReceiptNumber(PDO $pdo)
{
    $year = date('Y');

    $stmt = $pdo->prepare("
        SELECT COUNT(*) + 1 AS next_num
        FROM payments
        WHERE YEAR(payment_date) = ?
    ");

    $stmt->execute([$year]);
    $num = str_pad($stmt->fetchColumn(), 5, '0', STR_PAD_LEFT);

    return "RCT-$year-$num";
}
