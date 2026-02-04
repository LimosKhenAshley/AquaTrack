<?php
require_once __DIR__ . '/../config/database.php';

$currentPage = basename($_SERVER['PHP_SELF']);

// Fetch unread notification count
$unreadCountStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notifications
    WHERE user_id = ? AND is_read = 0
");
$unreadCountStmt->execute([$_SESSION['user']['id']]);
$unreadCount = $unreadCountStmt->fetchColumn();

?>

<div class="col-md-2 bg-dark text-white min-vh-100 p-3" collapse d-md-block" id="sidebar">
    <ul class="nav flex-column gap-1">

        <?php if ($role === 'admin'): ?>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/dashboard.php" 
                class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-primary text-white' : 'text-white' ?>">📊 Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/users.php" 
                class="nav-link <?= $currentPage === 'users.php' ? 'active bg-primary text-white' : 'text-white' ?>">👥 Users</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/areas.php" 
                class="nav-link <?= $currentPage === 'areas.php' ? 'active bg-primary text-white' : 'text-white' ?>">📍 Areas</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/rates.php" 
                class="nav-link <?= $currentPage === 'rates.php' ? 'active bg-primary text-white' : 'text-white' ?>">💰 Rates</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/penalties.php"
                class="nav-link <?= $currentPage === 'penalties.php' ? 'active bg-primary text-white' : 'text-white' ?>">⚠️ Penalties</a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'staff'): ?>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/dashboard.php" 
                class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-primary text-white' : 'text-white' ?>">📊 Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/readings.php" 
                class="nav-link <?= $currentPage === 'readings.php' ? 'active bg-primary text-white' : 'text-white' ?>">🚰 Meter Readings</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/bills.php" 
                class="nav-link <?= $currentPage === 'bills.php' ? 'active bg-primary text-white' : 'text-white' ?>">💳 Billing</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/payments.php"
                class="nav-link <?= $currentPage === 'payments.php' ? 'active bg-primary text-white' : 'text-white' ?>">💵 Payment History</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/disconnections.php"
                class="nav-link <?= $currentPage === 'disconnections.php' ? 'active bg-primary text-white' : 'text-white' ?>">🔌 Disconnections</a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'customer'): ?>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/dashboard.php" 
                class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-primary text-white' : 'text-white' ?>">🏠 My Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/bills.php"
                class="nav-link <?= $currentPage === 'bills.php' ? 'active bg-primary text-white' : 'text-white' ?>">🧾 My Bills</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/payment_history.php"
                class="nav-link <?= $currentPage === 'payment_history.php' ? 'active bg-primary text-white' : 'text-white' ?>">💳 Payment History</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/notifications.php" 
                class="nav-link <?= $currentPage === 'notifications.php' ? 'active bg-primary text-white' : 'text-white' ?>"><span>🔔 Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span id="notifBadge" class="badge bg-danger rounded-pill ms-1"><?= $count ?></span>
                <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'owner'): ?>
            <li class="nav-item">
                <a href="/AquaTrack/modules/owner/dashboard.php" 
                class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-primary text-white' : 'text-white' ?>">📊 Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/owner/reports.php" 
                class="nav-link <?= $currentPage === 'reports.php' ? 'active bg-primary text-white' : 'text-white' ?>">📈 Reports</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/owner/audit_logs.php"
                class="nav-link <?= $currentPage === 'audit_logs.php' ? 'active bg-primary text-white' : 'text-white' ?>">🛡 Audit Logs</a>
            </li>
        <?php endif; ?>

        <hr class="text-secondary">

        <!-- Logout Link place on the bottom of the sidebar -->
        <a href="/AquaTrack/modules/auth/logout.php"
        class="nav-link text-danger fw-bold"
        onclick="return confirm('Logout from AquaTrack?')">
        🚪 Logout
        </a>

    </ul>
</div>

<div class="col-md-10">
    <main>
