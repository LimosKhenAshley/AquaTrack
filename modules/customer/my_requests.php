<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$uid = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
SELECT sr.*
FROM service_requests sr
JOIN customers c ON c.id = sr.customer_id
WHERE c.user_id = ?
ORDER BY sr.created_at DESC
");
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h3>📩 My Service Requests</h3>

    <table class="table table-bordered shadow-sm mt-3">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Subject</th>
                <th>Type</th>
                <th>Priority</th>
                <th>Status</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= $r['created_at'] ?></td>
                    <td><?= htmlspecialchars($r['subject']) ?></td>
                    <td><?= ucfirst($r['type']) ?></td>
                    <td><?= ucfirst($r['priority']) ?></td>
                    <td>
                        <span class="badge bg-<?= 
                        $r['status']=='open'?'secondary':
                        ($r['status']=='in_progress'?'warning':
                        ($r['status']=='resolved'?'success':'danger')) ?>">
                        <?= $r['status'] ?>
                        </span>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<?php require_once '../../app/layouts/footer.php'; ?>