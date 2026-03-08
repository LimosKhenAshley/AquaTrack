<?php

if(!isset($_FILES['backup_file'])){
    exit;
}

$sql = file_get_contents($_FILES['backup_file']['tmp_name']);

$tables = substr_count($sql, "CREATE TABLE");
$rows = substr_count($sql, "INSERT INTO");

echo json_encode([
"tables"=>$tables,
"rows"=>$rows
]);