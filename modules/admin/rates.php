<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* =========================
   CSRF TOKEN
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
    return round($val, 4); // store up to 4 decimal places
}

$message     = '';
$messageType = 'success';

/* =========================
   DELETE RATE  (POST-based, CSRF-protected)
========================= */
if (isset($_POST['delete_rate'])) {
    verifyCsrf();

    $id = (int) $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM rates WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: rates.php?deleted=1");
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
        $stmt = $pdo->prepare("UPDATE rates SET rate_per_unit = ? WHERE id = ?");
        $stmt->execute([$rate, $id]);
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
        $stmt = $pdo->prepare("INSERT INTO rates (rate_per_unit, effective_from) VALUES (?, NOW())");
        $stmt->execute([$rate]);
        header("Location: rates.php?added=1");
        exit;
    }
}

/* =========================
   REDIRECT FLASH MESSAGES
========================= */
if (!$message) {
    if (isset($_GET['added']))   { $message = 'Rate added successfully!';   $messageType = 'success'; }
    if (isset($_GET['updated'])) { $message = 'Rate updated successfully!'; $messageType = 'success'; }
    if (isset($_GET['deleted'])) { $message = 'Rate deleted.';              $messageType = 'success'; }
}

/* =========================
   FETCH RATES
========================= */
$rates = $pdo->query("SELECT * FROM rates ORDER BY effective_from DESC")->fetchAll();

/* Mark the latest/current rate */
$latestId = !empty($rates) ? $rates[0]['id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Management – AquaTrack</title>
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

        /* Highlight current rate row */
        tr.current-rate td { background: #eaf4ff !important; }

        tbody tr { animation: rowIn .18s ease both; }
        @keyframes rowIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

        #emptyState { display: none; }

        /* Rate value styling */
        .rate-value { font-family: 'Courier New', monospace; font-weight: 600; font-size: 1rem; }
    </style>
</head>
<body>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header d-flex align-items-center gap-3">
    <i class="bi bi-currency-exchange fs-4 text-primary"></i>
    <div>
        <h4>Water Rates</h4>
        <small class="text-muted">Rate history per cubic meter (₱)</small>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRateModal">
            <i class="bi bi-plus-lg me-1"></i>Add Rate
        </button>
    </div>
</div>

<div class="container-fluid px-4 pb-5">

    <!-- Flash message -->
    <?php if ($message): ?>
        <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Current rate callout -->
    <?php if (!empty($rates)): ?>
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-info-circle-fill fs-5"></i>
            <div>
                <strong>Current Rate:</strong>
                ₱<?= number_format($rates[0]['rate_per_unit'], 2) ?> / m³
                &nbsp;·&nbsp;
                <small class="text-muted">
                    Effective <?= date('M d, Y', strtotime($rates[0]['effective_from'])) ?>
                </small>
            </div>
        </div>
    <?php endif; ?>

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
                       placeholder="Search by rate or date…" autocomplete="off">
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
                        <th>Rate per m³</th>
                        <th>Effective From</th>
                        <th>Status</th>
                        <th style="width:140px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="rateTableBody">
                <?php if (empty($rates)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">No rates found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($rates as $i => $r): ?>
                        <?php
                            $isLatest       = ($r['id'] == $latestId);
                            $formattedDate  = date('M d, Y', strtotime($r['effective_from']));
                            $searchIndex    = strtolower($r['rate_per_unit'] . ' ' . $formattedDate);
                        ?>
                        <tr class="<?= $isLatest ? 'current-rate' : '' ?>"
                            data-search="<?= htmlspecialchars($searchIndex) ?>">
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td>
                                <span class="rate-value text-primary">₱<?= number_format((float)$r['rate_per_unit'], 4) ?></span>
                            </td>
                            <td><?= htmlspecialchars($formattedDate) ?></td>
                            <td>
                                <?php if ($isLatest): ?>
                                    <span class="badge bg-success">Current</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Historical</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <!-- Edit -->
                                <button class="btn btn-outline-warning btn-sm me-1 btn-edit"
                                        data-id="<?= (int)$r['id'] ?>"
                                        data-rate="<?= htmlspecialchars($r['rate_per_unit'], ENT_QUOTES) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRateModal">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                <!-- Delete -->
                                <button class="btn btn-outline-danger btn-sm btn-delete"
                                        data-id="<?= (int)$r['id'] ?>"
                                        data-rate="₱<?= number_format((float)$r['rate_per_unit'], 2) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteRateModal">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
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

</div><!-- /container -->


<!-- ===================================================
     MODAL: ADD RATE
=================================================== -->
<div class="modal fade" id="addRateModal" tabindex="-1" aria-labelledby="addRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addRateModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Rate
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">
                        Rate per Cubic Meter (₱) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" name="rate_per_unit" class="form-control"
                               step="0.0001" min="0" max="99999"
                               placeholder="e.g. 12.50" required autofocus>
                        <span class="input-group-text">/ m³</span>
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


<!-- ===================================================
     MODAL: EDIT RATE
=================================================== -->
<div class="modal fade" id="editRateModal" tabindex="-1" aria-labelledby="editRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="editRateId">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editRateModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Rate
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Editing a rate updates it in place. Consider adding a new rate instead to preserve history.
                    </div>
                    <label class="form-label fw-semibold">
                        Rate per Cubic Meter (₱) <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">₱</span>
                        <input type="number" name="rate_per_unit" id="editRateValue" class="form-control"
                               step="0.0001" min="0" max="99999" required>
                        <span class="input-group-text">/ m³</span>
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


<!-- ===================================================
     MODAL: DELETE CONFIRMATION
=================================================== -->
<div class="modal fade" id="deleteRateModal" tabindex="-1" aria-labelledby="deleteRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="deleteRateId">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteRateModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">You are about to delete rate:</p>
                    <p class="fw-bold fs-5 text-danger" id="deleteRateValue"></p>
                    <p class="text-muted small">This may affect billing records. This action cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="delete_rate" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Yes, Delete
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ---- Populate Edit Modal ---- */
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('editRateId').value    = btn.dataset.id;
        document.getElementById('editRateValue').value = btn.dataset.rate;
    });
});

/* ---- Populate Delete Modal ---- */
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteRateId').value        = btn.dataset.id;
        document.getElementById('deleteRateValue').textContent = btn.dataset.rate;
    });
});

/* ---- Live Search / Filter ---- */
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