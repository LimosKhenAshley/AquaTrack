<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$userId = $_SESSION['user']['id'];

/* Get Staff ID */
$stmt = $pdo->prepare("SELECT id FROM staffs WHERE user_id=?");
$stmt->execute([$userId]);
$staffId = $stmt->fetchColumn();

/* Active Service Requests */
$req = $pdo->prepare("
    SELECT COUNT(*) 
    FROM service_requests
    WHERE assigned_staff_id = ?
    AND status IN ('open','in_progress')
");
$req->execute([$staffId]);
$activeRequests = $req->fetchColumn();

/* Pending Disconnections */
$disc = $pdo->prepare("
    SELECT COUNT(*)
    FROM disconnection_requests
    WHERE requested_by = ?
    AND action = 'disconnect'
    AND status = 'scheduled'
");
$disc->execute([$userId]);
$pendingDisconnections = $disc->fetchColumn();

/* Pending Reconnections */
$recon = $pdo->prepare("
    SELECT COUNT(*)
    FROM disconnection_requests
    WHERE requested_by = ?
    AND action = 'reconnect'
    AND status = 'scheduled'
");
$recon->execute([$userId]);
$pendingReconnections = $recon->fetchColumn();

/* Unified Task Preview (latest 5 tasks) */
$tasks = $pdo->prepare("
    SELECT 'Service Request' as type, sr.id, sr.status, sr.created_at, u.full_name
    FROM service_requests sr
    JOIN customers c ON c.id = sr.customer_id
    JOIN users u ON u.id = c.user_id
    WHERE sr.assigned_staff_id = ?
    AND sr.status IN ('open','in_progress')

    UNION ALL

    SELECT 
        CASE 
            WHEN d.action='disconnect' THEN 'Disconnection'
            ELSE 'Reconnection'
        END as type,
        d.id,
        d.status,
        d.created_at,
        u.full_name
    FROM disconnection_requests d
    JOIN customers c ON c.id = d.customer_id
    JOIN users u ON u.id = c.user_id
    WHERE d.requested_by = ?
    AND d.status='scheduled'

    ORDER BY created_at DESC
    LIMIT 5
");

$tasks->execute([$userId, $userId]);
$taskList = $tasks->fetchAll();
?>

<div class="container mt-4">

    <h3 class="mb-4">🧑‍🔧 Field Operations Dashboard</h3>

    <div class="row mb-4">

        <div class="col-md-4">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <h6>My Active Requests</h6>
                    <h2><?= $activeRequests ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-danger text-white shadow">
                <div class="card-body">
                    <h6>Pending Disconnections</h6>
                    <h2><?= $pendingDisconnections ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <h6>Pending Reconnections</h6>
                    <h2><?= $pendingReconnections ?></h2>
                </div>
            </div>
        </div>

    </div>

    <div class="card shadow">
        <div class="card-header bg-dark text-white">
        📋 My Latest Tasks
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($taskList): ?>
                        <?php foreach($taskList as $t): ?>
                            <tr>
                                <td><?= $t['type'] ?></td>
                                <td><?= $t['full_name'] ?></td>
                                <td>
                                <span class="badge bg-secondary">
                                <?= $t['status'] ?>
                                </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($t['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-3">No assigned tasks.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>