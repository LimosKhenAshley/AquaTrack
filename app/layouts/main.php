<?php
require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = $_SESSION['user'] ?? null;
$role = $user['role'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <title>AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary">
    <div class="container-fluid">
        <button class="btn btn-outline-light d-md-none" data-bs-toggle="collapse" data-bs-target="#sidebar">
            ☰
        </button>
        <span class="navbar-brand fw-bold ms-2">💧 AquaTrack</span>

        <?php if ($user): ?>
            <span class="text-white me-3">
                <?= htmlspecialchars($user['full_name'] ?? 'User') ?> (<?= ucfirst($role) ?>)
            </span>
        <?php endif; ?>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar will go here -->