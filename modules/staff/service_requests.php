<?php
require_once __DIR__.'/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__.'/../../app/config/database.php';
require_once __DIR__.'/../../app/layouts/main.php';
require_once __DIR__.'/../../app/layouts/sidebar.php';

/* Generate CSRF */
$_SESSION['csrf'] = bin2hex(random_bytes(32));

$userId = $_SESSION['user']['id'];

/* Map user → staff */
$stmt = $pdo->prepare("SELECT id FROM staffs WHERE user_id = ?");
$stmt->execute([$userId]);
$staffId = $stmt->fetchColumn();

if (!$staffId) {
    die("Staff record not found.");
}

/* Filters */
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$tab = $_GET['tab'] ?? 'assigned';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

/* WHERE builder */
$where = [];
$params = [];

if ($tab === 'assigned') {
    $where[] = "sr.assigned_staff_id = ?";
    $params[] = $staffId;
} elseif ($tab === 'unassigned') {
    $where[] = "sr.assigned_staff_id IS NULL";
} elseif ($tab === 'completed') {
    $where[] = "sr.status = 'resolved'";
} elseif ($tab === 'overdue') {
    $where[] = "sr.status NOT IN ('resolved','cancelled','rejected')
                AND TIMESTAMPDIFF(HOUR, sr.created_at, NOW()) > 24";
}

if ($statusFilter) {
    $where[] = "sr.status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $where[] = "(u.full_name LIKE ? OR sr.subject LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? "WHERE " . implode(" AND ", $where) : "";

/* Count */
$countStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM service_requests sr
    JOIN customers c ON c.id = sr.customer_id
    JOIN users u ON u.id = c.user_id
    $whereSql
");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

/* Fetch */
$stmt = $pdo->prepare("
    SELECT sr.*, u.full_name
    FROM service_requests sr
    JOIN customers c ON c.id = sr.customer_id
    JOIN users u ON u.id = c.user_id
    $whereSql
    ORDER BY sr.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Metrics */
$metricStmt = $pdo->prepare("
    SELECT status, COUNT(*) total
    FROM service_requests
    WHERE assigned_staff_id = ?
    GROUP BY status
");
$metricStmt->execute([$staffId]);
$metrics = $metricStmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="container-fluid px-4 mt-4">
    <!-- Header with Icon -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0">
                <i class="bi bi-tools me-2 text-primary"></i>
                Service Requests
            </h2>
        </div>
        <div>
            <span class="badge bg-light text-dark p-3">
                <i class="bi bi-person-badge me-2"></i>
                Staff ID: #<?= $staffId ?>
            </span>
        </div>
    </div>

    <!-- Enhanced Metrics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Open Requests</h6>
                            <h3 class="fw-bold mb-0"><?= $metrics['open'] ?? 0 ?></h3>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-envelope-open fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">In Progress</h6>
                            <h3 class="fw-bold mb-0"><?= $metrics['in_progress'] ?? 0 ?></h3>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-arrow-repeat fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Resolved</h6>
                            <h3 class="fw-bold mb-0"><?= $metrics['resolved'] ?? 0 ?></h3>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-check-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-gradient-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Requests</h6>
                            <h3 class="fw-bold mb-0"><?= array_sum($metrics) ?></h3>
                        </div>
                        <div class="bg-white bg-opacity-25 rounded-circle p-3">
                            <i class="bi bi-clipboard-data fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Filters Card -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form class="row g-3">
                <div class="col-lg-4">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-search me-2"></i>Search
                    </label>
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>" 
                           class="form-control form-control-lg"
                           placeholder="Search by customer or subject...">
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-tag me-2"></i>Status
                    </label>
                    <select name="status" class="form-select form-select-lg">
                        <option value="">All Status</option>
                        <option value="open" <?= $statusFilter == 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="in_progress" <?= $statusFilter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="resolved" <?= $statusFilter == 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="rejected" <?= $statusFilter == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="cancelled" <?= $statusFilter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-funnel me-2"></i>View
                    </label>
                    <select name="tab" class="form-select form-select-lg">
                        <option value="assigned" <?= $tab == 'assigned' ? 'selected' : '' ?>>Assigned to Me</option>
                        <option value="unassigned" <?= $tab == 'unassigned' ? 'selected' : '' ?>>Unassigned</option>
                        <option value="completed" <?= $tab == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="overdue" <?= $tab == 'overdue' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <button class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-filter me-2"></i>Apply Filters
                    </button>
                </div>
                <div class="col-lg-2 d-flex align-items-end">
                    <a href="?" class="btn btn-outline-secondary btn-lg w-100">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Enhanced Table Card -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold">
                    <i class="bi bi-table me-2"></i>
                    Request List
                </h5>
                <span class="badge bg-primary"><?= $totalRows ?> Total Requests</span>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4">ID</th>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>SLA</th>
                            <th class="text-end px-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                                    <h5 class="text-muted">No requests found</h5>
                                    <p class="text-muted">Try adjusting your filters</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): 
                                $hours = floor((time() - strtotime($r['created_at'])) / 3600);
                                /* SLA limit depending on priority */
                                $slaLimit = match($r['priority']) {
                                    'high' => 4,
                                    'medium' => 12,
                                    default => 24
                                };

                                $overdue = $hours > $slaLimit && $r['status'] != 'resolved';
                                $remaining = $slaLimit - $hours;
                                $deadline = date("M d, Y H:i", strtotime($r['created_at']) + ($slaLimit * 3600));
                                $priorityClass = match($r['priority']) { 'high' => 'danger', 'medium' => 'warning', default => 'secondary' };
                                $statusClass = match($r['status']) {
                                    'resolved' => 'success',
                                    'in_progress' => 'info',
                                    'rejected' => 'dark',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>
                                <tr class="<?= $overdue ? 'table-danger' : '' ?>">
                                    <?php if ($overdue): ?>
                                    <span class="badge bg-danger ms-2">
                                        ⚠ SLA Breach
                                    </span>
                                    <?php endif; ?>
                                    <td class="px-4 fw-semibold">#<?= $r['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="bg-light rounded-circle p-2 me-2">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <?= htmlspecialchars($r['full_name']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($r['subject']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $priorityClass ?> px-3 py-2">
                                            <?= strtoupper($r['priority']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusClass ?> px-3 py-2">
                                            <?= str_replace('_', ' ', strtoupper($r['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] == 'resolved'): ?>
                                            <span class="text-success fw-bold">Resolved</span>

                                        <?php elseif ($overdue): ?>
                                            <span class="text-danger fw-bold">
                                                Overdue (<?= $hours - $slaLimit ?> hrs)
                                            </span>

                                        <?php else: ?>
                                            <span class="text-warning fw-semibold">
                                                <?= $remaining ?> hrs remaining
                                            </span>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            Due: <?= $deadline ?>
                                        </small>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if (!$r['assigned_staff_id']): ?>
                                                <form method="POST" action="claim_request.php" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                                    <button class="btn btn-warning btn-sm" title="Claim Request">
                                                        <i class="bi bi-hand-index-thumb"></i> Claim
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-primary btn-sm"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateModal"
                                                    data-id="<?= $r['id'] ?>"
                                                    data-subject="<?= htmlspecialchars($r['subject']) ?>"
                                                    data-desc="<?= htmlspecialchars($r['message'] ?? 'No description') ?>"
                                                    data-status="<?= $r['status'] ?>"
                                                    data-note="<?= htmlspecialchars($r['admin_note'] ?? 'No admin notes available.') ?>"
                                                    title="Update Request">
                                                <i class="bi bi-pencil-square"></i> Update
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Enhanced Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <p class="text-muted mb-0">
                        Showing page <?= $page ?> of <?= $totalPages ?>
                    </p>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page-1 ?>&tab=<?= $tab ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php 
                            $start = max(1, $page - 2);
                            $end = min($totalPages, $page + 2);
                            
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&tab=<?= $tab ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page+1 ?>&tab=<?= $tab ?>&search=<?= urlencode($search) ?>&status=<?= $statusFilter ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Update Modal -->
<div class="modal fade" id="updateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="bi bi-pencil-square me-2"></i>
                    Update Service Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            
            <form method="POST" action="update_request.php">
                <div class="modal-body">
                    <input type="hidden" name="request_id" id="reqId">
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-tag me-2"></i>Subject
                            </label>
                            <input class="form-control bg-light" id="reqSubject" readonly>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-hourglass-split me-2"></i>Status
                            </label>
                            <select name="status" id="reqStatus" class="form-select">
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="rejected">Rejected</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-chat-dots me-2"></i>Description
                            </label>
                            <textarea class="form-control bg-light" id="reqDesc" rows="4" readonly></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-clock-history me-2"></i>Admin Notes
                            </label>
                            <textarea class="form-control bg-light" id="reqAdminNote" rows="4" readonly></textarea>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-journal-text me-2"></i>Staff Notes
                            </label>
                            <textarea name="staff_notes" class="form-control" rows="4" 
                                      placeholder="Add your notes about this update..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-2"></i>Save Update
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Bootstrap Icons if not already included -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- Custom Styles -->
<style>
    .bg-gradient-primary {
        background: linear-gradient(45deg, #0d6efd, #0b5ed7);
    }
    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #ffca2c);
    }
    .bg-gradient-success {
        background: linear-gradient(45deg, #198754, #157347);
    }
    .bg-gradient-info {
        background: linear-gradient(45deg, #0dcaf0, #31d2f2);
    }
    .table th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
    }
    .btn-sm {
        padding: 0.4rem 0.8rem;
    }
</style>

<script>
    const modal = document.getElementById('updateModal');
    
    if (modal) {
        modal.addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            
            document.getElementById('reqId').value = btn.dataset.id;
            document.getElementById('reqSubject').value = btn.dataset.subject;
            document.getElementById('reqDesc').value = btn.dataset.desc;
            document.getElementById('reqStatus').value = btn.dataset.status;
            document.getElementById('reqAdminNote').value = btn.dataset.note;
        });
    }
</script>