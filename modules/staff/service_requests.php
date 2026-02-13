<?php
require_once __DIR__.'/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__.'/../../app/config/database.php';
require_once __DIR__.'/../../app/layouts/main.php';
require_once __DIR__.'/../../app/layouts/sidebar.php';

$userId = $_SESSION['user']['id'];

$staffStmt = $pdo->prepare("
SELECT id FROM staffs WHERE user_id = ?
");
$staffStmt->execute([$userId]);
$staff = $staffStmt->fetch();

if (!$staff) {
    die("Staff record not found.");
}

$staffId = $staff['id'];

/* Fetch assigned OR unassigned */
$rows = $pdo->prepare("
    SELECT sr.*, 
        u.full_name
    FROM service_requests sr
    JOIN customers c ON c.id = sr.customer_id
    JOIN users u ON u.id = c.user_id
    WHERE sr.assigned_staff_id = ?
    ORDER BY sr.created_at DESC
");

$rows->execute([$staffId]);
$rows = $rows->fetchAll();
?>

<div class="container mt-4">
    <h3>🛠 Service Requests</h3>

    <div class="card shadow-sm mt-3">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($rows as $r): ?>
                        <tr>
                            <td>#<?= $r['id'] ?></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['subject']) ?></td>

                            <td>
                                <span class="badge bg-<?=
                                    $r['priority']=='high'?'danger':
                                    ($r['priority']=='medium'?'warning':'secondary')
                                    ?>">
                                    <?= strtoupper($r['priority']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge bg-<?=
                                    $r['status']=='open'?'secondary':
                                    ($r['status']=='in_progress'?'info':
                                    ($r['status']=='resolved'?'success':'danger'))
                                    ?>">
                                    <?= strtoupper($r['status']) ?>
                                </span>
                            </td>

                            <td><?= date('M d Y', strtotime($r['created_at'])) ?></td>

                            <td>
                                <button class="btn btn-sm btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#updateModal"
                                    data-id="<?= $r['id'] ?>"
                                    data-subject="<?= htmlspecialchars($r['subject']) ?>"
                                    data-desc="<?= htmlspecialchars((string)$r['message']) ?>"
                                    data-status="<?= $r['status'] ?>"
                                    >
                                    Update
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="updateModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="update_request.php">
                <div class="modal-header bg-primary text-white">
                    <h5>Update Request</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="request_id" id="reqId">

                    <div class="mb-2">
                        <label>Subject</label>
                        <input class="form-control" id="reqSubject" readonly>
                    </div>

                    <div class="mb-2">
                        <label>Description</label>
                        <textarea class="form-control" id="reqDesc" rows="3" readonly></textarea>
                    </div>

                    <div class="mb-2">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label>Staff Notes</label>
                        <textarea name="staff_notes" class="form-control"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success w-100">Save Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('updateModal');

    modal.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;

    reqId.value = btn.dataset.id;
    reqSubject.value = btn.dataset.subject;
    reqDesc.value = btn.dataset.desc;
    });
</script>