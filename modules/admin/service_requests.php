<?php
require_once '../../app/middleware/auth.php';
checkRole(['admin', 'staff']);

require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

/* =========================
   CSRF TOKEN
========================= */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

/* =========================
   UPDATE REQUEST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $id         = (int) $_POST['id'];
    $status     = in_array($_POST['status'], ['open', 'in_progress', 'resolved', 'rejected'])
                    ? $_POST['status'] : 'open';
    $admin_note = trim(strip_tags($_POST['admin_note'] ?? ''));
    $assigned   = ($_SESSION['user']['role'] === 'admin' && !empty($_POST['assigned_staff_id']))
                    ? (int) $_POST['assigned_staff_id'] : null;

    $pdo->prepare("
        UPDATE service_requests
        SET status            = ?,
            admin_note        = ?,
            assigned_staff_id = ?,
            updated_at        = NOW()
        WHERE id = ?
    ")->execute([$status, $admin_note, $assigned, $id]);

    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?updated=1");
    exit;
}

/* =========================
   FETCH DATA
========================= */
$rows = $pdo->query("
    SELECT sr.*,
           u.full_name,
           su.full_name AS staff_name
    FROM   service_requests sr
    JOIN   customers c  ON c.id  = sr.customer_id
    JOIN   users u      ON u.id  = c.user_id
    LEFT JOIN staffs s  ON s.id  = sr.assigned_staff_id
    LEFT JOIN users su  ON su.id = s.user_id
    ORDER  BY sr.created_at DESC
")->fetchAll();

$staffs = $pdo->query("
    SELECT s.id, u.full_name
    FROM   staffs s
    JOIN   users u ON u.id = s.user_id
    ORDER  BY u.full_name
")->fetchAll();

/* Helpers */
$statusColors = [
    'open'        => 'info',
    'in_progress' => 'warning',
    'resolved'    => 'success',
    'rejected'    => 'danger',
];
$statusLabels = [
    'open'        => 'Open',
    'in_progress' => 'In Progress',
    'resolved'    => 'Resolved',
    'rejected'    => 'Rejected',
];
$totalRequests = count($rows);
$openCount     = count(array_filter($rows, fn($r) => $r['status'] === 'open'));
$progressCount = count(array_filter($rows, fn($r) => $r['status'] === 'in_progress'));
$resolvedCount = count(array_filter($rows, fn($r) => $r['status'] === 'resolved'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Service Requests – AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }

        /* ── Page Header (consistent style) ── */
        .page-header {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-header h4 { font-weight: 700; margin: 0; }

        /* ── Stat Cards ── */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,.07);
            border-left: 4px solid transparent;
        }
        .stat-card .stat-value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-card .stat-label { font-size: .78rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px; }
        .stat-total   { border-color: #0ea5e9; }
        .stat-open    { border-color: #0dcaf0; }
        .stat-progress{ border-color: #ffc107; }
        .stat-resolved{ border-color: #22c55e; }

        /* ── Card / Table ── */
        .table-card { background: #fff; border-radius: 14px; box-shadow: 0 1px 4px rgba(0,0,0,.08); overflow: hidden; }
        .table th {
            font-size: .78rem; text-transform: uppercase;
            letter-spacing: .05em; padding: 13px 16px;
            border: none;
        }
        .table td { vertical-align: middle; padding: 12px 16px; border-color: #f1f5f9; }
        .table tbody tr { transition: background .12s; }
        .table tbody tr:hover { background: #f8faff; }

        /* ── Status badges ── */
        .status-badge {
            display: inline-block; padding: 3px 10px;
            border-radius: 20px; font-size: .72rem; font-weight: 600;
        }

        /* ── Type badge ── */
        .type-badge {
            display: inline-block; padding: 2px 9px;
            border-radius: 6px; font-size: .72rem; font-weight: 500;
            background: #f1f5f9; color: #475569;
        }

        /* ── Toolbar ── */
        .toolbar { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 16px; }
        .search-wrap { position: relative; flex: 1; min-width: 200px; }
        .search-wrap input {
            padding-left: 36px; border-radius: 10px;
            border: 1.5px solid #bae6fd; background: #fff;
            height: 40px; width: 100%; font-size: .9rem;
        }
        .search-wrap input:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px #bae6fd; outline: none; }
        .search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        .filter-select {
            height: 40px; border-radius: 10px; border: 1.5px solid #e2e8f0;
            background: #fff; padding: 0 12px; font-size: .88rem; cursor: pointer;
        }
        .filter-select:focus { border-color: #0ea5e9; box-shadow: 0 0 0 3px #bae6fd; outline: none; }

        /* ── Modal ── */
        .modal-content { border: none; border-radius: 16px; overflow: hidden; }
        .modal-header  { padding: 18px 22px; border-bottom: 1px solid #f1f5f9; background: #0284c7; color: #fff; }
        .modal-body    { padding: 22px; }
        .modal-footer  { padding: 14px 22px; border-top: 1px solid #f1f5f9; }
        .form-control, .form-select {
            border-radius: 10px; border: 1.5px solid #e2e8f0; font-size: .9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0ea5e9; box-shadow: 0 0 0 3px #bae6fd;
        }
        .form-label { font-size: .82rem; font-weight: 600; color: #64748b; margin-bottom: 4px; }

        /* Info summary box */
        .info-box { background: #f8faff; border: 1.5px solid #bae6fd; border-radius: 10px; padding: 14px 16px; margin-bottom: 16px; }
        .info-box .info-label { font-size: .75rem; color: #64748b; margin-bottom: 2px; }
        .info-box .info-value { font-weight: 600; font-size: .9rem; }

        /* Note display */
        .note-box { background: #f8faff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; font-size: .88rem; }

        /* Row fade-in */
        @keyframes rowIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; } }
        #requestsTbody tr { animation: rowIn .18s ease both; }

        #emptyState { display: none; }
    </style>
</head>
<body>

<!-- ── Page Header ── -->
<div class="page-header">
    <i class="bi bi-tools fs-4 text-primary"></i>
    <div>
        <h4>Service Requests</h4>
        <small class="text-muted">Manage and track customer service requests</small>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    <!-- Flash -->
    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>Service request updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Stat Cards ── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card stat-total">
                <div class="stat-value text-primary"><?= $totalRequests ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-open">
                <div class="stat-value text-info"><?= $openCount ?></div>
                <div class="stat-label">Open</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-progress">
                <div class="stat-value text-warning"><?= $progressCount ?></div>
                <div class="stat-label">In Progress</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card stat-resolved">
                <div class="stat-value text-success"><?= $resolvedCount ?></div>
                <div class="stat-label">Resolved</div>
            </div>
        </div>
    </div>

    <!-- ── Toolbar ── -->
    <div class="toolbar">
        <!-- Live search -->
        <div class="search-wrap">
            <span class="search-icon"><i class="bi bi-search" style="font-size:.85rem;"></i></span>
            <input type="search" id="searchInput" placeholder="Search customer, subject, type…" autocomplete="off">
        </div>

        <!-- Status filter -->
        <select id="statusFilter" class="filter-select">
            <option value="">All Statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="rejected">Rejected</option>
        </select>

        <!-- Type filter -->
        <select id="typeFilter" class="filter-select">
            <option value="">All Types</option>
            <?php
            $types = array_unique(array_column($rows, 'type'));
            sort($types);
            foreach ($types as $t): ?>
                <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars(ucfirst($t)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- ── Table ── -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Assigned Staff</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="requestsTbody">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No service requests found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r):
                        $color = $statusColors[$r['status']] ?? 'secondary';
                        $label = $statusLabels[$r['status']] ?? ucfirst($r['status']);
                    ?>
                    <tr data-search="<?= htmlspecialchars(strtolower($r['full_name'] . ' ' . $r['subject'] . ' ' . $r['type']), ENT_QUOTES) ?>"
                        data-status="<?= htmlspecialchars($r['status']) ?>"
                        data-type="<?= htmlspecialchars($r['type']) ?>">

                        <td class="text-nowrap">
                            <div><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                            <small class="text-muted"><?= date('h:i A', strtotime($r['created_at'])) ?></small>
                        </td>
                        <td><strong><?= htmlspecialchars($r['full_name']) ?></strong></td>
                        <td style="max-width:200px;">
                            <span title="<?= htmlspecialchars($r['subject']) ?>">
                                <?= htmlspecialchars(mb_strimwidth($r['subject'], 0, 50, '…')) ?>
                            </span>
                        </td>
                        <td><span class="type-badge"><?= htmlspecialchars(ucfirst($r['type'])) ?></span></td>
                        <td>
                            <span class="status-badge bg-<?= $color ?> <?= in_array($color, ['warning','info']) ? 'text-dark' : 'text-white' ?>">
                                <?= $label ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($r['staff_name']): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($r['staff_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-person-dash"></i> Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary rounded-3"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal<?= $r['id'] ?>">
                                <i class="bi bi-pencil-square me-1"></i>Update
                            </button>
                        </td>
                    </tr>

                    <!-- ══ UPDATE MODAL ══ -->
                    <div class="modal fade" id="modal<?= $r['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <form method="POST" novalidate>
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">

                                    <div class="modal-header">
                                        <div>
                                            <h5 class="modal-title mb-0">
                                                Request #<?= (int)$r['id'] ?>
                                            </h5>
                                            <small class="opacity-75"><?= htmlspecialchars($r['subject']) ?></small>
                                        </div>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">

                                        <!-- Info summary -->
                                        <div class="info-box mb-3">
                                            <div class="row g-3">
                                                <div class="col-6 col-md-3">
                                                    <div class="info-label">Customer</div>
                                                    <div class="info-value"><?= htmlspecialchars($r['full_name']) ?></div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="info-label">Type</div>
                                                    <div class="info-value"><?= htmlspecialchars(ucfirst($r['type'])) ?></div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="info-label">Submitted</div>
                                                    <div class="info-value"><?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                                                </div>
                                                <div class="col-6 col-md-3">
                                                    <div class="info-label">Current Status</div>
                                                    <div class="info-value">
                                                        <span class="status-badge bg-<?= $color ?> <?= in_array($color, ['warning','info']) ? 'text-dark' : 'text-white' ?>">
                                                            <?= $label ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Update Status -->
                                        <div class="mb-3">
                                            <label class="form-label">Update Status <span class="text-danger">*</span></label>
                                            <select name="status" class="form-select">
                                                <?php foreach ($statusLabels as $val => $lbl): ?>
                                                    <option value="<?= $val ?>" <?= $r['status'] === $val ? 'selected' : '' ?>>
                                                        <?= $lbl ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <!-- Assign Staff (admin only) -->
                                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Assign Staff</label>
                                            <select name="assigned_staff_id" class="form-select">
                                                <option value="">— Unassigned —</option>
                                                <?php foreach ($staffs as $s): ?>
                                                    <option value="<?= (int)$s['id'] ?>"
                                                        <?= (int)$r['assigned_staff_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($s['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Admin Note -->
                                        <div class="mb-3">
                                            <label class="form-label">Admin Note</label>
                                            <textarea name="admin_note" class="form-control" rows="3"
                                                      placeholder="Add administrative notes or comments…"><?= htmlspecialchars((string)$r['admin_note']) ?></textarea>
                                        </div>

                                        <!-- Staff Note (read-only) -->
                                        <div class="mb-1">
                                            <label class="form-label">Staff Note</label>
                                            <div class="note-box">
                                                <?php if (!empty($r['staff_notes'])): ?>
                                                    <?= htmlspecialchars($r['staff_notes']) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No staff note available.</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-1"></i>Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- ══ /MODAL ══ -->

                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Empty search state -->
        <div id="emptyState" class="text-center text-muted py-4">
            <i class="bi bi-search fs-3 d-block mb-2"></i>
            No requests match your filters.
        </div>
    </div>

</div><!-- /container -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Combined search + filter ── */
const searchInput  = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const typeFilter   = document.getElementById('typeFilter');
const rows         = document.querySelectorAll('#requestsTbody tr[data-search]');
const emptyState   = document.getElementById('emptyState');

function applyFilters() {
    const q      = searchInput.value.toLowerCase().trim();
    const status = statusFilter.value;
    const type   = typeFilter.value;
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !q      || row.dataset.search.includes(q);
        const matchStatus = !status || row.dataset.status === status;
        const matchType   = !type   || row.dataset.type   === type;
        const show = matchSearch && matchStatus && matchType;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    emptyState.style.display = visible === 0 ? 'block' : 'none';
}

searchInput.addEventListener('input',  applyFilters);
statusFilter.addEventListener('change', applyFilters);
typeFilter.addEventListener('change',   applyFilters);
</script>

<?php require_once '../../app/layouts/footer.php'; ?>