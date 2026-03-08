<?php
require_once __DIR__ . '/../../app/config/database.php';

$backupDir = __DIR__ . '/../../storage/backups/';
$backupName = "weekly_backup_" . date("Y-m-d_H-i-s") . ".sql";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$sqlDump = "";

foreach ($tables as $table) {

    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    $sqlDump .= "\n\n" . $create['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {

        $values = array_map(function($v) use ($pdo) {
            return $v === null ? "NULL" : $pdo->quote($v);
        }, array_values($row));

        $sqlDump .= "INSERT INTO `$table` VALUES (" . implode(",", $values) . ");\n";
    }
}

file_put_contents($backupDir . $backupName, $sqlDump);