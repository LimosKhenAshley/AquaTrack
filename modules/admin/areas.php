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
function sanitizeAreaName(string $raw): string {
    $name = trim(strip_tags($raw));
    return mb_substr($name, 0, 100); // cap at 100 chars
}

$message     = '';
$messageType = 'success'; // or 'danger'

/* =========================
   DELETE AREA  (POST-based, CSRF-protected)
========================= */
if (isset($_POST['delete_area'])) {
    verifyCsrf();

    $id = (int) $_POST['id'];
    // Optional: check FK constraints before deleting
    $stmt = $pdo->prepare("DELETE FROM areas WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: areas.php?deleted=1");
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
        $stmt = $pdo->prepare("UPDATE areas SET area_name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
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
        // Prevent duplicate names
        $check = $pdo->prepare("SELECT id FROM areas WHERE LOWER(area_name) = LOWER(?)");
        $check->execute([$name]);
        if ($check->fetch()) {
            $message     = 'An area with that name already exists.';
            $messageType = 'danger';
        } else {
            $stmt = $pdo->prepare("INSERT INTO areas (area_name) VALUES (?)");
            $stmt->execute([$name]);
            header("Location: areas.php?added=1");
            exit;
        }
    }
}

/* =========================
   REDIRECT FLASH MESSAGES
========================= */
if (!$message) {
    if (isset($_GET['added']))   { $message = 'Area added successfully!';   $messageType = 'success'; }
    if (isset($_GET['updated'])) { $message = 'Area updated successfully!'; $messageType = 'success'; }
    if (isset($_GET['deleted'])) { $message = 'Area deleted.';              $messageType = 'success'; }
}

/* =========================
   FETCH AREAS
========================= */
$areas = $pdo->query("SELECT * FROM areas ORDER BY area_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Area Management – AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --brand: #0d6efd;
        }
        body { background: #f0f2f5; }

        .page-header {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h4 {
            font-weight: 700;
            margin: 0;
        }

        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.08); }

        /* Search bar highlight */
        #areaSearch:focus { box-shadow: 0 0 0 3px rgba(13,110,253,.15); }

        /* Table tweaks */
        .table th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
        .table td { vertical-align: middle; }

        /* Row fade-in */
        tbody tr { animation: rowIn .18s ease both; }
        @keyframes rowIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:none; } }

        /* Empty-state */
        #emptyState { display: none; }
    </style>
</head>
<body>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header d-flex align-items-center gap-3">
    <i class="bi bi-map fs-4 text-primary"></i>
    <div>
        <h4>Service Areas</h4>
    </div>
    <div class="ms-auto">
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAreaModal">
            <i class="bi bi-plus-lg me-1"></i>Add Area
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

    <!-- Stats + Search bar -->
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
                       placeholder="Search areas…" autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Areas Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:60px">#</th>
                        <th>Area Name</th>
                        <th style="width:160px" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="areaTableBody">
                <?php if (empty($areas)): ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted py-4">No areas found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($areas as $i => $a): ?>
                        <tr data-name="<?= htmlspecialchars(strtolower($a['area_name'])) ?>">
                            <td class="text-muted"><?= $i + 1 ?></td>
                            <td><strong><?= htmlspecialchars($a['area_name']) ?></strong></td>
                            <td class="text-center">
                                <!-- Edit button → opens modal -->
                                <button class="btn btn-outline-warning btn-sm me-1 btn-edit"
                                        data-id="<?= (int)$a['id'] ?>"
                                        data-name="<?= htmlspecialchars($a['area_name'], ENT_QUOTES) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editAreaModal">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                <!-- Delete button → opens confirm modal -->
                                <button class="btn btn-outline-danger btn-sm btn-delete"
                                        data-id="<?= (int)$a['id'] ?>"
                                        data-name="<?= htmlspecialchars($a['area_name'], ENT_QUOTES) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteAreaModal">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- Empty search state -->
        <div id="emptyState" class="text-center text-muted py-4">
            <i class="bi bi-search fs-3 d-block mb-2"></i>
            No areas match your search.
        </div>
    </div>

</div><!-- /container -->


<!-- ===================================================
     MODAL: ADD AREA
=================================================== -->
<div class="modal fade" id="addAreaModal" tabindex="-1" aria-labelledby="addAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addAreaModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Add New Area
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Area Name <span class="text-danger">*</span></label>
                    <input type="text" name="area_name" class="form-control"
                           placeholder="e.g. Zone A, Downtown, Brgy. San José"
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


<!-- ===================================================
     MODAL: EDIT AREA
=================================================== -->
<div class="modal fade" id="editAreaModal" tabindex="-1" aria-labelledby="editAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="editAreaId">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="editAreaModalLabel">
                        <i class="bi bi-pencil me-2"></i>Edit Area
                    </h5>
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


<!-- ===================================================
     MODAL: DELETE CONFIRMATION
=================================================== -->
<div class="modal fade" id="deleteAreaModal" tabindex="-1" aria-labelledby="deleteAreaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" id="deleteAreaId">
            <div class="modal-content border-danger">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteAreaModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mb-1">You are about to delete:</p>
                    <p class="fw-bold fs-5" id="deleteAreaName"></p>
                    <p class="text-muted small">This action cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button name="delete_area" class="btn btn-danger">
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
        document.getElementById('editAreaId').value   = btn.dataset.id;
        document.getElementById('editAreaName').value = btn.dataset.name;
    });
});

/* ---- Populate Delete Modal ---- */
document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('deleteAreaId').value  = btn.dataset.id;
        document.getElementById('deleteAreaName').textContent = btn.dataset.name;
    });
});

/* ---- Live Search / Filter ---- */
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