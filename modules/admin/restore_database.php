<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin']);
require_once '../../app/config/database.php';

if($_POST['csrf'] !== $_SESSION['csrf']){
    die("Invalid CSRF token");
}

if(!isset($_FILES['backup_file'])){
    die("No file uploaded");
}

$ext = pathinfo($_FILES['backup_file']['name'],PATHINFO_EXTENSION);

if($ext !== 'sql'){
    die("Only SQL files allowed");
}

if($_FILES['backup_file']['size'] > 10*1024*1024){
    die("File too large");
}

$userId = $_SESSION['user']['id'];

$pass = $_POST['admin_password'];

$stmt=$pdo->prepare("SELECT password FROM users WHERE id=?");
$stmt->execute([$userId]);
$hash=$stmt->fetchColumn();

if(!password_verify($pass,$hash)){
    die("Incorrect admin password");
}

$sql=file_get_contents($_FILES['backup_file']['tmp_name']);

try{

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    $statements = explode(";",$sql);

    foreach($statements as $stmt){

        $stmt=trim($stmt);

        if($stmt){
            $pdo->exec($stmt);
        }

    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

    header("Location: system_backup.php?success=1");

}catch(Exception $e){

    die("Restore failed: ".$e->getMessage());

}