<?php

require_once '../../app/config/database.php';

$table = $_GET['table'];

$stmt = $pdo->query("SELECT * FROM `$table`");

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=$table.csv");

$output = fopen("php://output","w");

$first = true;

while($row = $stmt->fetch(PDO::FETCH_ASSOC)){

if($first){
    fputcsv($output, array_keys($row));
    $first = false;
}

fputcsv($output, $row);

}

fclose($output);