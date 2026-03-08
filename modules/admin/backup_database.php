<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin']);

require_once '../../app/config/database.php';

$userId = $_SESSION['user']['id'];

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$backupName = "aquatrack_backup_" . date("Ymd_His");

$backupDir = "../../storage/backups/";
$sqlFile = $backupDir . $backupName . ".sql";

$sqlDump = "";

foreach ($tables as $table) {

    // CREATE TABLE
    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
    $sqlDump .= "\n\n" . $create['Create Table'] . ";\n\n";

    // DATA
    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {

        $values = array_map(function($v) use ($pdo) {
            return $v === null ? "NULL" : $pdo->quote($v);
        }, array_values($row));

        $sqlDump .= "INSERT INTO `$table` VALUES (" . implode(",", $values) . ");\n";
    }

    /* CREATE CSV FOR EXCEL */
    $csvFile = fopen($backupDir . $table . ".csv", "w");

    if (!empty($rows)) {

        // headers
        fputcsv($csvFile, array_keys($rows[0]));

        // rows
        foreach ($rows as $row) {
            fputcsv($csvFile, $row);
        }

    }

    fclose($csvFile);
}

file_put_contents($sqlFile, $sqlDump);

/* CREATE ZIP */
$zipFile = $backupDir . $backupName . ".zip";

$zip = new ZipArchive();
$zip->open($zipFile, ZipArchive::CREATE);

$zip->addFile($sqlFile, basename($sqlFile));

foreach ($tables as $table) {

    $csv = $backupDir . $table . ".csv";

    if (file_exists($csv)) {
        $zip->addFile($csv, basename($csv));
    }

}

$zip->close();

/* SAVE LOG */

$fileSize = filesize($zipFile); // store raw bytes

$stmt = $pdo->prepare("
    INSERT INTO backup_logs (user_id,file_name,file_size,type)
    VALUES (?,?,?,?)
");

$stmt->execute([
$userId,
basename($zipFile),
$fileSize,
'manual'
]);

/* DOWNLOAD */

header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=" . basename($zipFile));
readfile($zipFile);
exit;