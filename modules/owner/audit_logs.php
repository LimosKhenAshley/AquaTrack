<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['owner']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$stmt = $pdo->query("
    SELECT
        a.*,
        u.full_name
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    ORDER BY a.created_at DESC
");

$logs = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">

<h3>🛡 Audit Logs</h3>

<div class="card shadow-sm mt-3">
<div class="card-body">

<div class="table-responsive">
<table class="table table-bordered table-hover">

<thead class="table-dark">
<tr>
    <th>User</th>
    <th>Action</th>
    <th>Description</th>
    <th>IP</th>
    <th>Date</th>
</tr>
</thead>

<tbody>
<?php foreach($logs as $log): ?>
<tr>
    <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>
    <td><?= htmlspecialchars($log['action']) ?></td>
    <td><?= htmlspecialchars($log['description']) ?></td>
    <td><?= $log['ip_address'] ?></td>
    <td><?= $log['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>

</table>
</div>

</div>
</div>

</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
