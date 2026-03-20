<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';

/* =========================
   CSRF TOKEN
   Must be before any output.
========================= */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

function verifyCsrf(): void {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}

/* =========================
   SANITIZE HELPER
========================= */
function sanitizeRate(mixed $raw): float|false {
    $val = filter_var(trim((string)$raw), FILTER_VALIDATE_FLOAT);
    if ($val === false || $val < 0) return false;
    return round($val, 4);
}

$message     = '';
$messageType = 'success';

/* =========================
   ARCHIVE RATE
========================= */
if (isset($_POST['archive_rate'])) {
    verifyCsrf();
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE rates SET status = 'archived' WHERE id = ?")->execute([$id]);
    header("Location: rates.php?archived=1");
    exit;
}

/* =========================
   RESTORE RATE
========================= */
if (isset($_POST['restore_rate'])) {
    verifyCsrf();
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE rates SET status = 'active' WHERE id = ?")->execute([$id]);
    header("Location: rates.php?restored=1");
    exit;
}

/* =========================
   UPDATE RATE
========================= */
if (isset($_POST['update_rate'])) {
    verifyCsrf();
    $id   = (int) $_POST['id'];
    $rate = sanitizeRate($_POST['rate_per_unit'] ?? '');
    if ($rate === false) {
        $message     = 'Please enter a valid non-negative rate.';
        $messageType = 'danger';
    } else {
        $pdo->prepare("UPDATE rates SET rate_per_unit = ? WHERE id = ?")->execute([$rate, $id]);
        header("Location: rates.php?updated=1");
        exit;
    }
}

/* =========================
   ADD RATE
========================= */
if (isset($_POST['add_rate'])) {
    verifyCsrf();
    $rate = sanitizeRate($_POST['rate_per_unit'] ?? '');
    if ($rate === false) {
        $message     = 'Please enter a valid non-negative rate.';
        $messageType = 'danger';
    } else {
        $pdo->prepare("INSERT INTO rates (rate_per_unit, effective_from, status) VALUES (?, NOW(), 'active')")
            ->execute([$rate]);
        header("Location: rates.php?added=1");
        exit;
    }
}

/* =========================
   REDIRECT FLASH MESSAGES
========================= */
if (!$message) {
    if (isset($_GET['added']))    { $message = 'Rate added successfully!';    $messageType = 'success'; }
    if (isset($_GET['updated']))  { $message = 'Rate updated successfully!';  $messageType = 'success'; }
    if (isset($_GET['archived'])) { $message = 'Rate archived successfully.'; $messageType = 'warning'; }
    if (isset($_GET['restored'])) { $message = 'Rate restored successfully.'; $messageType = 'success'; }
}

/*
 * =====================================================
 *  LAYOUT INCLUDES — must come after ALL header()
 *  redirects above, and before any HTML output below.
 * =====================================================
 */
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* =========================
   STATUS FILTER
========================= */
$statusFilter = $_GET['status_filter'] ?? 'active';
if (!in_array($statusFilter, ['active', 'archived', 'all'])) $statusFilter = 'active';

/* =========================
   FETCH RATES
========================= */
if ($statusFilter === 'all') {
    $rates = $pdo->query("SELECT * FROM rates ORDER BY effective_from DESC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM rates WHERE status = ? ORDER BY effective_from DESC");
    $stmt->execute([$statusFilter]);
    $rates = $stmt->fetchAll();
}

// The current rate is always the most recent active one, regardless of filter view
$currentRateRow = $pdo->query(
    "SELECT * FROM rates WHERE status = 'active' ORDER BY effective_from DESC LIMIT 1"
)->fetch();

$activeCount   = (int)$pdo->query("SELECT COUNT(*) FROM rates WHERE status = 'active'")->fetchColumn();
$archivedCount = (int)$pdo->query("SELECT COUNT(*) FROM rates WHERE status = 'archived' OR status IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Management - AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; }

        .page-header {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h4 { font-weight: 700; margin: 0; }

        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

        .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
        .table td { vertical-align: middle; }

        tr.current-rate td { background: #eaf4ff !important; }
        tr.row-archived { opacity: .65; background: #f8fafc; }

        tbody tr { animation: rowIn .18s ease both; }
        @keyframes rowIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

        .rate-value { font-family: 'Courier New', monospace; font-weight: 600; font-size: 1rem; }

        /* Status filter tabs */
        .status-tabs { display:flex; gap:6px; margin-bottom:18px; flex-wrap:wrap; }
        .status-tab {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .82rem;
            font-weight: 600;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .status-tab:hover { border-color:#0ea5e9; color:#0284c7; }
        .tab-active-active   { background:#0ea5e9; border-color:#0ea5e9; color:#fff !important; }
        .tab-active-archived { background:#64748b; border-color:#64748b; color:#fff !important; }
        .tab-active-all      { background:#0f172a; border-color:#0f172a; color:#fff !important; }
        .tab-badge { background:rgba(255,255,255,.25); border-radius:20px; padding:1px 7px; font-size:.72rem; }
        .status-tab:not([class*="tab-active"]) .tab-badge { background:#f1f5f9; color:#64748b; }

        /* Status badge */
        .status-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px; font-size:.72rem; font-weight:600; }
        .s-active   { background:#dcfce7; color:#15803d; }
        .s-archived { background:#f1f5f9; color:#64748b; }
        .status-dot { width:6px; height:6px; border-radius:50%; display:inline-block; }
        .dot-active   { background:#22c55e; }
        .dot-archived { background:#94a3b8; }

        #emptyState { display: none; }
    </style>
</head>
<body>

<div class="page-header d-flex align-items-center gap-3">
    <i class="bi bi-currency-exchange fs-4 text-primary"></i>
    <div>
        <h4>Water Rates</h4>
        <small class="text-muted">Rate history per cubic meter (&#8369;)</small>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRateModal">
            <i class="bi bi-plus-lg me-1"></i>Add Rate
        </button>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Current rate callout — always shows the latest active rate -->
    <?php if ($currentRateRow): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div>
                <strong>Current Rate:</strong>
                &#8369;<?= number_format($currentRateRow['rate_per_unit'], 2) ?> / m&sup3;
                &nbsp;&middot;&nbsp;
                <small class="text-muted">
                    Effective <?= date('M d, Y', strtotime($currentRateRow['effective_from'])) ?>
                </small>
            </div>
        </div>
    <?php endif; ?>

    <!-- Status filter tabs -->
    <div class="status-tabs">
        <a href="?status_filter=active"
           class="status-tab <?= $statusFilter === 'active'   ? 'tab-active-active'   : '' ?>">
            <span class="status-dot dot-active"></span> Active
            <span class="tab-badge"><?= $activeCount ?></span>
        </a>
        <a href="?status_filter=archived"
           class="status-tab <?= $statusFilter === 'archived' ? 'tab-active-archived' : '' ?>">
            <i class="bi bi-archive" style="font-size:.75rem"></i> Archived
            <span class="tab-badge"><?= $archivedCount ?></span>
        </a>
        <a href="?status_filter=all"
           class="status-tab <?= $statusFilter === 'all'      ? 'tab-active-all'      : '' ?>">
            All Rates
            <span class="tab-badge"><?= $activeCount + $archivedCount ?></span>
        </a>
    </div>

    <!-- Stats + Search -->
    <div class="row g-3 mb-3 align-items-center">
        <div class="col-auto">
            <span class="badge bg-secondary fs-6">
                <?= count($rates) ?> rate<?= count($rates) !== 1 ? 's' : '' ?>
            </span>
        </div>
        <div class="col-md-4 ms-auto">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input id="rateSearch" type="search" class="form-control border-start-0 ps-0"
                       placeholder="Search by rate or date..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Rates Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px">#</th>
                        <th>Rate per m&sup3;</th>
                        <th>Effective From</th>
                        <th style="width:130px">Label</th>
                        <th style="width:110px" class="text-center">Status</th>
                        <th style="width:150px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="rateTableBody">
                <?php if (empty($rates)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No rates found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rates as $i => $r):
                        $rStatus       = $r['status'] ?? 'active';
                        $isCurrentRate = $currentRateRow && ($r['id'] == $currentRateRow['id']);
                        $rowClass      = $isCurrentRate ? 'current-rate' : ($rStatus === 'archived' ? 'row-archived' : '');
                        $formattedDate = date('M d, Y', strtotime($r['effective_from']));
                        $searchIndex   = strtolower($r['rate_per_unit'] . ' ' . $formattedDate);
                    ?>
                        <tr class="<?= $rowClass ?>" data-search="<?= htmlspecialchars($searchIndex) ?>">
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td>
                                <span class="rate-value text-primary">&#8369;<?= number_format((float)$r['rate_per_unit'], 2) ?></span>
                            </td>
                            <td><?= htmlspecialchars($formattedDate) ?></td>
                            <td>
                                <?php if ($isCurrentRate): ?>
                                    <span class="badge bg-success">Current</span>
                                <?php elseif ($rStatus === 'archived'): ?>
                                    <span class="badge bg-secondary">Archived</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Historical</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($rStatus === 'active'): ?>
                                    <span class="status-badge s-active">
                                        <span class="status-dot dot-active"></span>Active
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge s-archived">
                                        <span class="status-dot dot-archived"></span>Archived
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <!-- Edit -->
                                    <button class="btn btn-outline-warning btn-sm btn-edit"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-rate="<?= htmlspecialchars($r['rate_per_unit'], ENT_QUOTES) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editRateModal">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>

                                    <?php if ($rStatus === 'active'): ?>
                                    <!-- Archive -->
                                    <button class="btn btn-outline-secondary btn-sm btn-archive"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-rate="&#8369;<?= number_format((float)$r['rate_per_unit'], 2) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveRateModal">
                                        <i class="bi bi-archive-fill"></i>
                                    </button>
                                    <?php else: ?>
                                    <!-- Restore -->
                                    <button class="btn btn-outline-success btn-sm btn-restore"
                                            data-id="<?= (int)$r['id'] ?>"
                                            data-rate="&#8369;<?= number_format((float)$r['rate_per_unit'], 2) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#restoreRateModal">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="emptyState" class="text-center text-muted py-4">
            <i class="bi bi-search fs-3 d-block mb-2"></i>
            No rates match your search.
        </div>
    </div>

</div>


<!-- ADD MODAL -->
<div class="modal fade" id="addRateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Rate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">
                        Rate per Cubic Meter (&#8369;) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">&#8369;</span>
                        <input type="number" name="rate_per_unit" class="form-control"
                               step="0.0001" min="0" max="99999"
                               placeholder="e.g. 12.50" required autofocus>
                        <span class="input-group-text">/ m&sup3;</span>
                    </div>
                    <div class="form-text">The effective date will be set to today automatically.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="add_rate" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Add Rate
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- EDIT MODAL -->
<div class="modal fade" id="editRateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="editRateId">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Editing a rate updates it in place. Consider adding a new rate instead to preserve history.
                    </div>
                    <label class="form-label fw-semibold">
                        Rate per Cubic Meter (&#8369;) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">&#8369;</span>
                        <input type="number" name="rate_per_unit" id="editRateValue" class="form-control"
                               step="0.0001" min="0" max="99999" required>
                        <span class="input-group-text">/ m&sup3;</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="update_rate" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- ARCHIVE CONFIRM MODAL -->
<div class="modal fade" id="archiveRateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="archiveRateId">
            <div class="modal-content border-secondary">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="bi bi-archive me-2"></i>Archive Rate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">Archive this rate?</p>
                    <p class="fw-bold fs-5 text-secondary" id="archiveRateValue"></p>
                    <p class="text-muted small mb-0">It will be hidden from active lists but can be restored later.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="archive_rate" class="btn btn-secondary">
                        <i class="bi bi-archive me-1"></i>Yes, Archive
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- RESTORE CONFIRM MODAL -->
<div class="modal fade" id="restoreRateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="restoreRateId">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Restore Rate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">Restore this rate to active?</p>
                    <p class="fw-bold fs-5 text-success" id="restoreRateValue"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="restore_rate" class="btn btn-success">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Yes, Restore
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('editRateId').value    = btn.dataset.id;
        document.getElementById('editRateValue').value = btn.dataset.rate;
    });
});

document.querySelectorAll('.btn-archive').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('archiveRateId').value           = btn.dataset.id;
        document.getElementById('archiveRateValue').textContent  = btn.dataset.rate;
    });
});

document.querySelectorAll('.btn-restore').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('restoreRateId').value           = btn.dataset.id;
        document.getElementById('restoreRateValue').textContent  = btn.dataset.rate;
    });
});

const searchInput = document.getElementById('rateSearch');
const rows        = document.querySelectorAll('#rateTableBody tr[data-search]');
const emptyState  = document.getElementById('emptyState');

searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    let visible = 0;
    rows.forEach(row => {
        const match = row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    emptyState.style.display = visible === 0 ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>