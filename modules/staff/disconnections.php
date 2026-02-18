<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$userId = $_SESSION['user']['id'];

/* =========================
   FETCH REQUESTS
========================= */
$rows = $pdo->prepare("
    SELECT
        dr.*,
        u.full_name,
        c.meter_number
    FROM disconnection_requests dr
    JOIN customers c ON dr.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    WHERE dr.requested_by = ?
    ORDER BY dr.created_at DESC
");

$rows->execute([$userId]);
$rows = $rows->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <h3>🔌 Service Disconnection Panel</h3>

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Customer</th>
                            <th>Meter</th>
                            <th>Action</th>
                            <th>Reason</th>
                            <th>Scheduled</th>
                            <th>Status</th>
                            <th width="180">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach($rows as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['full_name']) ?></td>
                                <td><?= htmlspecialchars($r['meter_number']) ?></td>
                                <td>
                                    <?php if($r['action']=='disconnect'): ?>
                                        <span class="badge bg-danger">Disconnect</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Reconnect</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($r['reason']) ?></td>
                                <td><?= $r['scheduled_date'] ?></td>
                                <td>
                                    <?php if($r['status']=='scheduled'): ?>
                                        <span class="badge bg-warning">Scheduled</span>
                                    <?php elseif($r['status']=='completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Cancelled</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if($r['status']=='scheduled'): ?>
                                    <button class="btn btn-success btn-sm completeBtn"
                                        data-id="<?= $r['id'] ?>">
                                        ✔ Complete
                                    </button>

                                    <button class="btn btn-danger btn-sm cancelBtn"
                                        data-id="<?= $r['id'] ?>">
                                        ✖ Cancel
                                    </button>
                                    <?php else: ?>
                                    —
                                    <?php endif; ?>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.completeBtn').forEach(btn=>{
    btn.onclick = ()=>{
        if(!confirm('Mark as completed?')) return;

        fetch('ajax_complete_request.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'id='+btn.dataset.id
        })
        .then(r=>r.json())
        .then(d=>{
        alert(d.message);
        location.reload();
        });
        };
    });

    document.querySelectorAll('.cancelBtn').forEach(btn=>{
    btn.onclick = ()=>{
        if(!confirm('Cancel request?')) return;

        fetch('ajax_cancel_request.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+btn.dataset.id
        })
        .then(r=>r.json())
        .then(d=>{
        alert(d.message);
        location.reload();
        });
    };
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
