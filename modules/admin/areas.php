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
function sanitizeAreaName(string $raw): string {
    $name = trim(strip_tags($raw));
    return mb_substr($name, 0, 100);
}

$message     = '';
$messageType = 'success';

/* =========================
   ARCHIVE AREA
========================= */
if (isset($_POST['archive_area'])) {
    verifyCsrf();
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE areas SET status = 'archived' WHERE id = ?")->execute([$id]);
    header("Location: areas.php?archived=1");
    exit;
}

/* =========================
   RESTORE AREA
========================= */
if (isset($_POST['restore_area'])) {
    verifyCsrf();
    $id = (int) $_POST['id'];
    $pdo->prepare("UPDATE areas SET status = 'active' WHERE id = ?")->execute([$id]);
    header("Location: areas.php?restored=1");
    exit;
}

/* =========================
   UPDATE AREA
========================= */
if (isset($_POST['update_area'])) {
    verifyCsrf();
    $id   = (int) $_POST['id'];
    $name = sanitizeAreaName($_POST['area_name'] ?? '');
    if ($name === '') {
        $message     = 'Area name cannot be empty.';
        $messageType = 'danger';
    } else {
        $pdo->prepare("UPDATE areas SET area_name = ? WHERE id = ?")->execute([$name, $id]);
        header("Location: areas.php?updated=1");
        exit;
    }
}

/* =========================
   ADD AREA
========================= */
if (isset($_POST['add_area'])) {
    verifyCsrf();
    $name = sanitizeAreaName($_POST['area_name'] ?? '');
    if ($name === '') {
        $message     = 'Area name cannot be empty.';
        $messageType = 'danger';
    } else {
        $check = $pdo->prepare("SELECT id FROM areas WHERE LOWER(area_name) = LOWER(?)");
        $check->execute([$name]);
        if ($check->fetch()) {
            $message     = 'An area with that name already exists.';
            $messageType = 'danger';
        } else {
            $pdo->prepare("INSERT INTO areas (area_name, status) VALUES (?, 'active')")->execute([$name]);
            header("Location: areas.php?added=1");
            exit;
        }
    }
}

/* =========================
   REDIRECT FLASH MESSAGES
========================= */
if (!$message) {
    if (isset($_GET['added']))    { $message = 'Area added successfully!';    $messageType = 'success'; }
    if (isset($_GET['updated']))  { $message = 'Area updated successfully!';  $messageType = 'success'; }
    if (isset($_GET['archived'])) { $message = 'Area archived successfully.'; $messageType = 'warning'; }
    if (isset($_GET['restored'])) { $message = 'Area restored successfully.'; $messageType = 'success'; }
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
   FETCH AREAS
========================= */
if ($statusFilter === 'all') {
    $areas = $pdo->query("SELECT * FROM areas ORDER BY area_name ASC")->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM areas WHERE status = ? ORDER BY area_name ASC");
    $stmt->execute([$statusFilter]);
    $areas = $stmt->fetchAll();
}

$activeCount   = (int)$pdo->query("SELECT COUNT(*) FROM areas WHERE status = 'active'")->fetchColumn();
$archivedCount = (int)$pdo->query("SELECT COUNT(*) FROM areas WHERE status = 'archived' OR status IS NULL")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Area Management - AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --brand: #0d6efd; }
        body { background: #f0f2f5; }

        .page-header {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h4 { font-weight: 700; margin: 0; }

        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        #areaSearch:focus { box-shadow: 0 0 0 3px rgba(13,110,253,.15); }

        .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
        .table td { vertical-align: middle; }

        tbody tr { animation: rowIn .18s ease both; }
        @keyframes rowIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

        tr.row-archived { opacity: .65; background: #f8fafc; }

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

        #emptyState { display:none; }
    </style>
</head>
<body>

<div class="page-header d-flex align-items-center gap-3">
    <i class="bi bi-map fs-4 text-primary"></i>
    <div>
        <h4>Service Areas</h4>
        <small class="text-muted"><?= $activeCount ?> active &middot; <?= $archivedCount ?> archived</small>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAreaModal">
            <i class="bi bi-plus-lg me-1"></i>Add Area
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
            All Areas
            <span class="tab-badge"><?= $activeCount + $archivedCount ?></span>
        </a>
    </div>

    <!-- Stats + Search -->
    <div class="row g-3 mb-3 align-items-center">
        <div class="col-auto">
            <span class="badge bg-secondary fs-6">
                <?= count($areas) ?> area<?= count($areas) !== 1 ? 's' : '' ?>
            </span>
        </div>
        <div class="col-md-4 ms-auto">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0">
                    <i class="bi bi-search text-muted"></i>
                </span>
                <input id="areaSearch" type="search" class="form-control border-start-0 ps-0"
                       placeholder="Search areas..." autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Area Name</th>
                        <th style="width:120px" class="text-center">Status</th>
                        <th style="width:160px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="areaTableBody">
                <?php if (empty($areas)): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">No areas found.</td></tr>
                <?php else: ?>
                    <?php foreach ($areas as $i => $a):
                        $aStatus  = $a['status'] ?? 'active';
                        $rowClass = $aStatus === 'archived' ? ' class="row-archived"' : '';
                    ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($a['area_name'])) ?>"<?= $rowClass ?>>
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($a['area_name']) ?></strong></td>
                            <td class="text-center">
                                <?php if ($aStatus === 'active'): ?>
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
                                    <button class="btn btn-outline-warning btn-sm btn-edit"
                                            data-id="<?= (int)$a['id'] ?>"
                                            data-name="<?= htmlspecialchars($a['area_name'], ENT_QUOTES) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editAreaModal">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>

                                    <?php if ($aStatus === 'active'): ?>
                                    <button class="btn btn-outline-secondary btn-sm btn-archive"
                                            data-id="<?= (int)$a['id'] ?>"
                                            data-name="<?= htmlspecialchars($a['area_name'], ENT_QUOTES) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#archiveAreaModal">
                                        <i class="bi bi-archive-fill"></i>
                                    </button>
                                    <?php else: ?>
                                    <button class="btn btn-outline-success btn-sm btn-restore"
                                            data-id="<?= (int)$a['id'] ?>"
                                            data-name="<?= htmlspecialchars($a['area_name'], ENT_QUOTES) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#restoreAreaModal">
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
            No areas match your search.
        </div>
    </div>

</div>


<!-- ADD MODAL -->
<div class="modal fade" id="addAreaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Add New Area</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Area Name <span class="text-danger">*</span></label>
                    <input type="text" name="area_name" class="form-control"
                           placeholder="e.g. Zone A, Downtown, Brgy. San Jose"
                           maxlength="100" required autofocus>
                    <div class="form-text">Maximum 100 characters.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="add_area" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>Add Area
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- EDIT MODAL -->
<div class="modal fade" id="editAreaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="editAreaId">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Area</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Area Name <span class="text-danger">*</span></label>
                    <input type="text" name="area_name" id="editAreaName" class="form-control"
                           maxlength="100" required>
                    <div class="form-text">Maximum 100 characters.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="update_area" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i>Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- ARCHIVE CONFIRM MODAL -->
<div class="modal fade" id="archiveAreaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="archiveAreaId">
            <div class="modal-content border-secondary">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title"><i class="bi bi-archive me-2"></i>Archive Area</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">Archive this area?</p>
                    <p class="fw-bold fs-5" id="archiveAreaName"></p>
                    <p class="text-muted small mb-0">It can be restored later.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="archive_area" class="btn btn-secondary">
                        <i class="bi bi-archive me-1"></i>Yes, Archive
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<!-- RESTORE CONFIRM MODAL -->
<div class="modal fade" id="restoreAreaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="restoreAreaId">
            <div class="modal-content border-success">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Restore Area</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">Restore this area to active?</p>
                    <p class="fw-bold fs-5" id="restoreAreaName"></p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="restore_area" class="btn btn-success">
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
        document.getElementById('editAreaId').value   = btn.dataset.id;
        document.getElementById('editAreaName').value = btn.dataset.name;
    });
});

document.querySelectorAll('.btn-archive').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('archiveAreaId').value         = btn.dataset.id;
        document.getElementById('archiveAreaName').textContent = btn.dataset.name;
    });
});

document.querySelectorAll('.btn-restore').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('restoreAreaId').value         = btn.dataset.id;
        document.getElementById('restoreAreaName').textContent = btn.dataset.name;
    });
});

const searchInput = document.getElementById('areaSearch');
const rows        = document.querySelectorAll('#areaTableBody tr[data-name]');
const emptyState  = document.getElementById('emptyState');

searchInput.addEventListener('input', () => {
    const q = searchInput.value.toLowerCase().trim();
    let visible = 0;
    rows.forEach(row => {
        const match = row.dataset.name.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    emptyState.style.display = visible === 0 ? 'block' : 'none';
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>