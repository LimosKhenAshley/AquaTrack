<?php
session_start();

function checkRole(array $allowedRoles) {
    if (!isset($_SESSION['user'])) {
        header("Location: /AquaTrack/modules/auth/login.php");
        exit;
    }

    if (!in_array($_SESSION['user']['role'], $allowedRoles)) {
        http_response_code(403);
        die("⛔ Access Denied");
    }
}

