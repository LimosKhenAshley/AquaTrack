<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin']);

require_once '../../app/config/database.php';

if (!isset($_FILES['backup_file'])) {
    die("No file uploaded.");
}

$file = $_FILES['backup_file']['tmp_name'];

$sql = file_get_contents($file);

try {

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    $statements = explode(";", $sql);

    foreach ($statements as $stmt) {

        $stmt = trim($stmt);

        if ($stmt) {
            $pdo->exec($stmt);
        }

    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    header("Location: system_backup.php?success=1");

} catch (Exception $e) {

    die("Restore failed: " . $e->getMessage());

}