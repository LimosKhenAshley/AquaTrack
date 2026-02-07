<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';

$user_id = $_SESSION['user']['id'];

$email = isset($_POST['email_enabled']) ? 1 : 0;
$sms   = isset($_POST['sms_enabled']) ? 1 : 0;

$pdo->prepare("
    UPDATE user_contact_preferences
    SET email_enabled=?, sms_enabled=?
    WHERE user_id=?
")->execute([$email, $sms, $user_id]);

echo json_encode([
    'status' => 'success',
    'message' => 'Preferences saved'
]);
