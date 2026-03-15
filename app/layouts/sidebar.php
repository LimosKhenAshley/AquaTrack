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

// For staff, fetch count of open/in-progress service requests assigned to them or unassigned
if ($role === 'staff') {
    // Get the actual staff ID (not user ID)
    $staffLookup = $pdo->prepare("SELECT id FROM staffs WHERE user_id = ?");
    $staffLookup->execute([$_SESSION['user']['id']]);
    $staffId = $staffLookup->fetchColumn();

    if ($staffId) {
        $reqCountStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM service_requests
            WHERE status IN ('open', 'in_progress')
            AND (
                assigned_staff_id IS NULL 
                OR assigned_staff_id = ?
            )
        ");
        $reqCountStmt->execute([$staffId]); // ← staff ID, not user ID
        $reqCount = $reqCountStmt->fetchColumn();
    }
}
?>

<div class="col-12 col-md-2 bg-dark text-white min-vh-100 p-3 collapse d-md-block"
     id="sidebar">
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
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/service_requests.php"
                class="nav-link <?= $currentPage === 'service_requests.php' ? 'active bg-primary text-white' : 'text-white' ?>">🛠 Service Requests</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/reports.php" 
                class="nav-link <?= $currentPage === 'reports.php' ? 'active bg-primary text-white' : 'text-white' ?>">📈 Reports</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/activity_logs.php"
                class="nav-link <?= $currentPage === 'activity_logs.php' ? 'active bg-primary text-white' : 'text-white' ?>">🛡 Activity Logs</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/admin/system_backup.php"
                class="nav-link <?= $currentPage === 'system_backup.php' ? 'active bg-primary text-white' : 'text-white' ?>">💾 System Backup</a>
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
                class="nav-link <?= $currentPage === 'payments.php' ? 'active bg-primary text-white' : 'text-white' ?>">💵 Payments</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/disconnections.php"
                class="nav-link <?= $currentPage === 'disconnections.php' ? 'active bg-primary text-white' : 'text-white' ?>">🔌 Disconnections</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/staff/service_requests.php"
                class="nav-link <?= $currentPage === 'service_requests.php' ? 'active bg-primary text-white' : 'text-white' ?>">
                🛠 Service Requests
                <?php if ($reqCount > 0): ?>
                    <span id="reqBadge" class="badge bg-danger rounded-pill ms-1"><?= $reqCount ?></span>
                <?php endif; ?>
                </a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'customer'): ?>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/profile.php" 
                class="nav-link <?= $currentPage === 'profile.php' ? 'active bg-primary text-white' : 'text-white' ?>">👤 My Profile</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/dashboard.php" 
                class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-primary text-white' : 'text-white' ?>">🏠 My Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/bills.php"
                class="nav-link <?= $currentPage === 'bills.php' ? 'active bg-primary text-white' : 'text-white' ?>">🧾 My Bills</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/service_request.php"
                class="nav-link <?= $currentPage === 'service_request.php' ? 'active bg-primary text-white' : 'text-white' ?>">🛠 Service Request</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/payment_history.php"
                class="nav-link <?= $currentPage === 'payment_history.php' ? 'active bg-primary text-white' : 'text-white' ?>">💳 Payment History</a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/notifications.php" 
                class="nav-link <?= $currentPage === 'notifications.php' ? 'active bg-primary text-white' : 'text-white' ?>"><span>🔔 Notifications</span>
                <?php if ($unreadCount > 0): ?>
                    <span id="notifBadge" class="badge bg-danger rounded-pill ms-1"><?= $unreadCount ?></span>
                <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a href="/AquaTrack/modules/customer/preferences.php" 
                class="nav-link <?= $currentPage === 'preferences.php' ? 'active bg-primary text-white' : 'text-white' ?>">⚙ Preferences</a>
            </li>
        <?php endif; ?>

        <?php if ($role === 'owner'): ?>
            <li class="nav-item">
                <a href="/AquaTrack/modules/owner/dashboard.php" 
                class="nav-link <?= $currentPage === 'dashboard.php' ? 'active bg-primary text-white' : 'text-white' ?>">📊 Dashboard</a>
            </li>
        <?php endif; ?>

        <hr class="text-secondary">

        <!-- Logout Link place on the bottom of the sidebar -->
        <!-- SweetAlert2 CDN — add to your main layout <head> if not already there -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <a href="#" id="logoutBtn" class="nav-link text-danger fw-bold">
            🚪 Logout
        </a>

        <!-- Spinner overlay shown while logging out -->
        <div id="logoutOverlay" style="display:none; position:fixed; inset:0; background:rgba(255,255,255,0.75);
            backdrop-filter:blur(4px); z-index:9999; flex-direction:column;
            align-items:center; justify-content:center; gap:1rem;">
            <div style="width:48px; height:48px; border:4px solid #e0e0e0;
                        border-top-color:#dc3545; border-radius:50%;
                        animation:spin .75s linear infinite;"></div>
            <p style="font-family:system-ui; color:#555; margin:0;">Signing you out…</p>
        </div>
        <style>@keyframes spin { to { transform:rotate(360deg); } }</style>

        <script>
            document.getElementById('logoutBtn').addEventListener('click', function (e) {
                e.preventDefault();

                Swal.fire({
                    title: 'Log out?',
                    text: 'You will be signed out of AquaTrack.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, log me out',
                    cancelButtonText: 'Stay logged in',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    focusCancel: true,
                }).then(function (result) {
                    if (!result.isConfirmed) return;

                    // Show spinner
                    var overlay = document.getElementById('logoutOverlay');
                    overlay.style.display = 'flex';

                    fetch('/AquaTrack/modules/auth/logout.php', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.success) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: 'Logged out successfully',
                                showConfirmButton: false,
                                timer: 1500,
                                timerProgressBar: true,
                            }).then(function () {
                                window.location.href = data.redirect || '/AquaTrack/modules/auth/login.php';
                            });
                        } else {
                            overlay.style.display = 'none';
                            Swal.fire('Error', 'Something went wrong. Please try again.', 'error');
                        }
                    })
                    .catch(function () {
                        overlay.style.display = 'none';
                        Swal.fire('Network Error', 'Could not reach the server.', 'error');
                    });
                });
            });
        </script>

    </ul>
</div>

<div class="col-md-10">
    <main>
