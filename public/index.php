<?php
session_start();

require_once __DIR__ . '/../app/config/database.php';

if (!isset($_SESSION['user'])) {
    header("Location: ../modules/auth/login.php");
    exit;
}

$role = $_SESSION['user']['role'];

switch ($role) {
    case 'admin':
        header("Location: ../modules/admin/dashboard.php");
        break;
    case 'staff':
        header("Location: ../modules/staff/dashboard.php");
        break;
    case 'customer':
        header("Location: ../modules/customer/dashboard.php");
        break;
    case 'owner':
        header("Location: ../modules/owner/dashboard.php");
        break;
    default:
        echo "Invalid role.";
}
exit;
