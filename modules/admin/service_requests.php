<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin','staff']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

if($_SERVER['REQUEST_METHOD']=='POST'){

    $assigned = $_POST['assigned_staff_id'] ?? null;

    $pdo->prepare("
        UPDATE service_requests
        SET status=?,
            admin_note=?,
            assigned_staff_id=?,
            updated_at = NOW()
        WHERE id=?
    ")->execute([
        $_POST['status'],
        $_POST['admin_note'],
        $assigned ?: null,
        $_POST['id']
    ]);
    
    // Redirect to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$rows = $pdo->query("
    SELECT sr.*, 
        u.full_name,
        su.full_name AS staff_name
    FROM service_requests sr
    JOIN customers c ON c.id = sr.customer_id
    JOIN users u ON u.id = c.user_id
    LEFT JOIN staffs s ON s.id = sr.assigned_staff_id
    LEFT JOIN users su ON su.id = s.user_id
    ORDER BY sr.created_at DESC
")->fetchAll();

$staffs = $pdo->query("
    SELECT s.id, u.full_name
    FROM staffs s
    JOIN users u ON u.id = s.user_id
    ORDER BY u.full_name
")->fetchAll();
?>

<div class="container mt-4">
    <h3>🛠 Service Requests</h3>

    <table class="table table-striped shadow-sm mt-3">
        <thead class="table-dark">
            <tr>
                <th>Date</th>
                <th>Customer</th>
                <th>Subject</th>
                <th>Type</th>
                <th>Status</th>
                <th>Assigned Staff</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?= $r['created_at'] ?></td>
                    <td><?= $r['full_name'] ?></td>
                    <td><?= $r['subject'] ?></td>
                    <td><?= $r['type'] ?></td>
                    <td>
                        <?php
                        $statusColors = [
                            'open' => 'info',
                            'in_progress' => 'warning',
                            'resolved' => 'success',
                            'rejected' => 'danger'
                        ];
                        $color = $statusColors[$r['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $color ?>"><?= $r['status'] ?></span>
                    </td>
                    <td>
                        <?php if($r['staff_name']): ?>
                            <span class="badge bg-secondary"><?= $r['staff_name'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">Unassigned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Modal Trigger Button -->
                        <button type="button" 
                                class="btn btn-sm btn-outline-primary" 
                                data-bs-toggle="modal" 
                                data-bs-target="#requestModal<?= $r['id'] ?>">
                            <i class="bi bi-pencil-square"></i> Update
                        </button>

                        <!-- Modal -->
                        <div class="modal fade" id="requestModal<?= $r['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $r['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalLabel<?= $r['id'] ?>">
                                                Update Request #<?= $r['id'] ?> - <?= htmlspecialchars($r['subject']) ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <div class="modal-body">
                                            <!-- Request Information Summary -->
                                            <div class="mb-3 p-3 bg-light rounded">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Customer:</small>
                                                        <p class="mb-1"><strong><?= $r['full_name'] ?></strong></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Type:</small>
                                                        <p class="mb-1"><strong><?= $r['type'] ?></strong></p>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Created:</small>
                                                        <p class="mb-0"><strong><?= date('M d, Y H:i', strtotime($r['created_at'])) ?></strong></p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <small class="text-muted">Current Status:</small>
                                                        <p class="mb-0"><span class="badge bg-<?= $color ?>"><?= $r['status'] ?></span></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select">
                                                    <option <?= $r['status']=='open'?'selected':'' ?> value="open">Open</option>
                                                    <option <?= $r['status']=='in_progress'?'selected':'' ?> value="in_progress">In Progress</option>
                                                    <option <?= $r['status']=='resolved'?'selected':'' ?> value="resolved">Resolved</option>
                                                    <option <?= $r['status']=='rejected'?'selected':'' ?> value="rejected">Rejected</option>
                                                </select>
                                            </div>

                                            <?php if($_SESSION['user']['role']=='admin'): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Assign Staff</label>
                                                    <select name="assigned_staff_id" class="form-select">
                                                        <option value="">-- Select staff to assign --</option>
                                                        <?php foreach($staffs as $s): ?>
                                                            <option value="<?= $s['id'] ?>"
                                                                <?= $r['assigned_staff_id']==$s['id']?'selected':'' ?>>
                                                                <?= $s['full_name'] ?>
                                                            </option>
                                                        <?php endforeach ?>
                                                    </select>
                                                </div>
                                            <?php endif; ?>

                                            <div class="mb-3">
                                                <label class="form-label">Admin Note</label>
                                                <textarea name="admin_note" 
                                                    class="form-control" 
                                                    rows="3"
                                                    placeholder="Add any administrative notes or comments..."><?= htmlspecialchars((string) $r['admin_note']) ?></textarea>
                                            </div>

                                            <?php if(!empty($r['admin_note'])): ?>
                                                <div class="mb-3">
                                                    <label class="form-label">Current Note</label>
                                                    <div class="p-2 border rounded bg-light">
                                                        <?= htmlspecialchars($r['admin_note']) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="bi bi-check-circle"></i> Update Request
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>

<!-- Include Bootstrap Icons (if not already included) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Make sure Bootstrap JS is included (usually in your footer) -->
<!-- This should be in your footer.php, but if not, add: -->
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->

<?php require_once '../../app/layouts/footer.php'; ?>