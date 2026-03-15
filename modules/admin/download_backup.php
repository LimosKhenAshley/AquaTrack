<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin']);

require_once '../../app/config/database.php';

// Validate ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid backup ID.');
}

// Fetch backup log
$stmt = $pdo->prepare("SELECT * FROM backup_logs WHERE id = ?");
$stmt->execute([$id]);
$backup = $stmt->fetch();

if (!$backup) {
    http_response_code(404);
    exit('Backup record not found.');
}

$backupDir = __DIR__ . '/../../storage/backups/';
$filePath  = $backupDir . $backup['file_name'];

if (!file_exists($filePath)) {
    http_response_code(404);
    exit('Backup file no longer exists on the server.');
}

// Serve the file
$fileName = basename($filePath);
$fileSize = filesize($filePath);

// Detect MIME type
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$mime = match($ext) {
    'zip' => 'application/zip',
    'sql' => 'application/octet-stream',
    default => 'application/octet-stream',
};

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filePath);
exit;