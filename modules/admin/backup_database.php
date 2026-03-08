<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin']);

require_once '../../app/config/database.php';

$dbname = $pdo->query("SELECT DATABASE()")->fetchColumn();

$filename = "aquatrack_backup_" . date("Y-m-d_H-i-s") . ".sql";

header('Content-Type: application/sql');
header("Content-Disposition: attachment; filename=$filename");

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {

    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    echo "\n\n" . $create['Create Table'] . ";\n\n";

    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {

        $values = array_map(function($v) use ($pdo) {
            return $v === null ? "NULL" : $pdo->quote($v);
        }, array_values($row));

        echo "INSERT INTO `$table` VALUES (" . implode(",", $values) . ");\n";
    }
}