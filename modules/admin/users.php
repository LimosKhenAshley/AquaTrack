<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$message = "";
$messageType = "success";

/* =========================
   ADD USER
========================= */
if (isset($_POST['add_user'])) {
    $full_name    = trim($_POST['full_name']    ?? '');
    $address      = trim($_POST['address']      ?? '');
    $email        = trim($_POST['email']        ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $role_id      = (int)($_POST['role_id']     ?? 0);
    $raw_password = $_POST['password']          ?? '';

    if ($full_name === '' || $email === '' || $raw_password === '' || $role_id === 0) {
        $message     = "All required fields must be filled in.";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message     = "Please enter a valid email address.";
        $messageType = "danger";
    } elseif (strlen($raw_password) < 8) {
        $message     = "Password must be at least 8 characters.";
        $messageType = "danger";
    } else {
        // Check duplicate email
        $chk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $message     = "That email address is already in use.";
            $messageType = "danger";
        } else {
            try {
                $pdo->beginTransaction();

                $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
                $roleStmt->execute([$role_id]);
                $role_name = $roleStmt->fetchColumn();

                if (!$role_name) {
                    throw new Exception("Invalid role selected.");
                }

                $password = password_hash($raw_password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("
                    INSERT INTO users (full_name, address, email, phone, password, role_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$full_name, $address, $email, $phone, $password, $role_id]);
                $user_id = $pdo->lastInsertId();

                switch ($role_name) {
                    case 'staff':
                        $pdo->prepare("INSERT INTO staffs (user_id) VALUES (?)")->execute([$user_id]);
                        break;
                    case 'customer':
                        $pdo->prepare("INSERT INTO customers (user_id, area_id, meter_number) VALUES (?, ?, ?)")
                            ->execute([$user_id, $_POST['area_id'] ?? null, $_POST['meter_number'] ?? null]);
                        break;
                    case 'admin':
                        $pdo->prepare("INSERT INTO admins (user_id) VALUES (?)")->execute([$user_id]);
                        break;
                    case 'owner':
                        $pdo->prepare("INSERT INTO owners (user_id) VALUES (?)")->execute([$user_id]);
                        break;
                }

                $pdo->commit();
                $message = "User <strong>" . htmlspecialchars($full_name) . "</strong> added successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $message     = "Error: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

/* =========================
   FETCH ROLES & AREAS
========================= */
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
$areas = $pdo->query("SELECT id, area_name FROM areas ORDER BY area_name")->fetchAll(PDO::FETCH_ASSOC);

/* =============================
   SEARCH, SORT & PAGINATION
============================= */
$search  = trim($_GET['search'] ?? '');
$_limitRaw = (int)($_GET['limit'] ?? 10);
$limit     = in_array($_limitRaw, [10, 25, 50, 100]) ? $_limitRaw : 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $limit;

// Whitelist sort columns & directions
$sortMap = [
    'id'        => 'u.id',
    'full_name' => 'u.full_name',
    'email'     => 'u.email',
    'phone'     => 'u.phone',
    'role_name' => 'r.role_name',
];
$sortCol = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortMap)
    ? $_GET['sort'] : 'id';
$sortDir = (isset($_GET['dir']) && strtoupper($_GET['dir']) === 'ASC') ? 'ASC' : 'DESC';
$orderBy = $sortMap[$sortCol] . ' ' . $sortDir;

$searchParam = "%$search%";

if ($search !== '') {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE full_name LIKE ? OR email LIKE ?");
    $countStmt->execute([$searchParam, $searchParam]);
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
}
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalUsers / $limit));
$page       = min($page, $totalPages);

$sql = "
    SELECT u.id, u.full_name, u.address, u.email, u.phone, u.role_id, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
";
if ($search !== '') {
    $sql .= " WHERE u.full_name LIKE :search OR u.email LIKE :search";
}
$sql .= " ORDER BY {$orderBy} LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if ($search !== '') {
    $stmt->bindValue(':search', $searchParam);
}
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management – AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Syne:wght@700&display=swap" rel="stylesheet">

    <style>
        :root {
            --aqua:   #0ea5e9;
            --aqua-d: #0284c7;
            --surface:#f0f2f5;
            --border: #bae6fd;
            --text:   #0f172a;
            --muted:  #64748b;
            --danger: #ef4444;
            --success:#22c55e;
            --warn:   #f59e0b;
        }

        body { background: var(--surface); color: var(--text); font-family: 'DM Sans', sans-serif; }

        /* ── Page Header (matches areas.php / rates.php style) ── */
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

        /* ── Toolbar ── */
        .toolbar {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .search-wrap { position: relative; flex: 1; min-width: 220px; }
        .search-wrap input {
            padding-left: 38px;
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #fff;
            height: 42px;
            width: 100%;
        }
        .search-wrap input:focus { border-color: var(--aqua); box-shadow: 0 0 0 3px #bae6fd; outline: none; }
        .search-icon {
            position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
            color: var(--muted); pointer-events: none;
        }
        #searchSpinner { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); display: none; }

        /* ── Table ── */
        .table-card { background: #fff; border-radius: 16px; box-shadow: 0 2px 12px rgba(14,165,233,.08); overflow: hidden; }
        .table thead th {
            background: #0f172a; color: #fff;
            font-size: .78rem; text-transform: uppercase; letter-spacing: .06em;
            border: none; padding: 14px 16px;
        }
        .table tbody tr { transition: background .15s; }
        .table tbody tr:hover { background: #f0f9ff; }
        .table tbody td { vertical-align: middle; padding: 12px 16px; border-color: #f1f5f9; }

        /* role badges */
        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        .role-admin    { background:#fee2e2; color:#dc2626; }
        .role-staff    { background:#dbeafe; color:#2563eb; }
        .role-customer { background:#dcfce7; color:#16a34a; }
        .role-owner    { background:#fef9c3; color:#ca8a04; }
        .role-default  { background:#f1f5f9; color:#475569; }

        /* ── Buttons ── */
        .btn-aqua  { background: var(--aqua); color: #fff; border: none; border-radius: 10px; }
        .btn-aqua:hover { background: var(--aqua-d); color: #fff; }
        .btn-edit  { background: #fef3c7; color: #92400e; border: none; border-radius: 8px; font-size: .8rem; padding: 5px 12px; }
        .btn-edit:hover { background: var(--warn); color: #fff; }
        .btn-del   { background: #fee2e2; color: #991b1b; border: none; border-radius: 8px; font-size: .8rem; padding: 5px 12px; }
        .btn-del:hover  { background: var(--danger); color: #fff; }
        .btn-self  { background: #f1f5f9; color: var(--muted); border: none; border-radius: 8px; font-size: .75rem; padding: 5px 10px; cursor: default; }

        /* ── Modals ── */
        .modal-content { border: none; border-radius: 18px; overflow: hidden; }
        .modal-header  { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; }
        .modal-body    { padding: 24px; }
        .modal-footer  { padding: 16px 24px; border-top: 1px solid #f1f5f9; }
        .modal-add .modal-header  { background: linear-gradient(135deg, var(--aqua-d), #38bdf8); color: #fff; }
        .modal-edit .modal-header { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

        .form-control, .form-select {
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-size: .9rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--aqua);
            box-shadow: 0 0 0 3px #bae6fd;
        }
        .form-label { font-size: .82rem; font-weight: 600; color: var(--muted); margin-bottom: 4px; }

        /* ── Pagination ── */
        .page-link { border-radius: 8px !important; margin: 0 2px; border-color: var(--border); color: var(--aqua-d); }
        .page-item.active .page-link { background: var(--aqua); border-color: var(--aqua); }

        /* ── Toast ── */
        #liveToast { min-width: 300px; }

        /* ── Fade-in rows ── */
        @keyframes rowIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
        #usersTbody tr { animation: rowIn .2s ease both; }

        /* ── Sort arrows ── */
        .sortable { cursor: pointer; user-select: none; white-space: nowrap; }
        .sortable:hover { background: #1e293b; }
        .sort-arrow { font-size: .65rem; margin-left: 4px; opacity: .5; }
        .sort-arrow.active { opacity: 1; color: #38bdf8; }

        /* ── Show entries ── */
        .entries-select {
            border-radius: 10px;
            border: 1.5px solid var(--border);
            background: #fff;
            height: 42px;
            padding: 0 10px;
            font-size: .88rem;
            color: var(--text);
            cursor: pointer;
        }
        .entries-select:focus { border-color: var(--aqua); box-shadow: 0 0 0 3px #bae6fd; outline: none; }
        .entries-label { font-size: .85rem; color: var(--muted); white-space: nowrap; align-self: center; }

        /* ── Responsive ── */
        @media(max-width:576px){
            .page-header { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

    <!-- ── Header (consistent with areas.php / rates.php) ── -->
    <div class="page-header">
        <i class="bi bi-people-fill fs-4 text-primary"></i>
        <div>
            <h4>User Management</h4>
            <small class="text-muted"><?= $totalUsers ?> total users</small>
        </div>
        <div class="ms-auto">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-lg me-1"></i>Add User
            </button>
        </div>
    </div>

<div class="container-fluid px-4 pb-5" style="max-width:1200px;">

    <!-- ── Flash message ── -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-3" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Toolbar ── -->
    <div class="toolbar">
        <!-- Show entries -->
        <span class="entries-label">Show</span>
        <select id="limitSelect" class="entries-select">
            <?php foreach ([10, 25, 50, 100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
        <span class="entries-label">entries</span>

        <!-- Search -->
        <div class="search-wrap">
            <span class="search-icon">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
            </span>
            <input type="text" id="liveSearch" placeholder="Search name or email…"
                   value="<?= htmlspecialchars($search) ?>" autocomplete="off">
            <div id="searchSpinner" class="spinner-border spinner-border-sm text-secondary" role="status"></div>
        </div>

        <?php if ($search): ?>
            <a href="users.php" class="btn btn-outline-secondary rounded-3">✕ Clear</a>
        <?php endif; ?>
    </div>

    <!-- ── Table ── -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <?php
                        function sortLink($label, $col, $currentSort, $currentDir, $search, $limit, $page) {
                            $isActive = $currentSort === $col;
                            $nextDir  = ($isActive && $currentDir === 'DESC') ? 'ASC' : 'DESC';
                            $url = "?sort={$col}&dir={$nextDir}&search=" . urlencode($search) . "&limit={$limit}&page={$page}";
                            $arrow = $isActive
                                ? ($currentDir === 'ASC' ? '▲' : '▼')
                                : '⇅';
                            $arrowClass = $isActive ? 'sort-arrow active' : 'sort-arrow';
                            return "<th class='sortable'><a href='{$url}' class='text-white text-decoration-none'>{$label}<span class='{$arrowClass}'>{$arrow}</span></a></th>";
                        }
                        ?>
                        <th style="width:60px" class="sortable">
                            <a href="?sort=id&dir=<?= ($sortCol==='id' && $sortDir==='DESC') ? 'ASC' : 'DESC' ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= $page ?>" class="text-white text-decoration-none">
                                #<span class="sort-arrow <?= $sortCol==='id' ? 'active' : '' ?>"><?= $sortCol==='id' ? ($sortDir==='ASC' ? '▲' : '▼') : '⇅' ?></span>
                            </a>
                        </th>
                        <?= sortLink('Name',  'full_name', $sortCol, $sortDir, $search, $limit, $page) ?>
                        <?= sortLink('Email', 'email',     $sortCol, $sortDir, $search, $limit, $page) ?>
                        <?= sortLink('Phone', 'phone',     $sortCol, $sortDir, $search, $limit, $page) ?>
                        <?= sortLink('Role',  'role_name', $sortCol, $sortDir, $search, $limit, $page) ?>
                        <th style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTbody">
                    <?php foreach ($users as $u): ?>
                        <?= renderUserRow($u, (int)($_SESSION['user_id'] ?? 0)) ?>
                    <?php endforeach; ?>

                    <?php if (empty($users)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Pagination ── -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center flex-wrap">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sortCol ?>&dir=<?= $sortDir ?>&limit=<?= $limit ?>">‹ Prev</a>
                </li>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1)           echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            for ($i = $start; $i <= $end; $i++):
            ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sortCol ?>&dir=<?= $sortDir ?>&limit=<?= $limit ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sortCol ?>&dir=<?= $sortDir ?>&limit=<?= $limit ?>">Next ›</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>

</div><!-- /container -->


<!-- ═══════════════════════════════
     ADD USER MODAL
═══════════════════════════════ -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-add">
            <form method="POST" id="addUserForm" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Add New User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" placeholder="John Dela Cruz" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="123 Street, City">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" placeholder="user@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" id="addPhone" name="phone" class="form-control" placeholder="+639XXXXXXXXX">
                            <div class="invalid-feedback">Enter a valid PH mobile number (e.g. 09XX or +63XX).</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" name="password" id="addPassword" class="form-control" placeholder="Min. 8 characters" required minlength="8">
                                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="addPassword" tabindex="-1">👁</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role *</label>
                            <select name="role_id" id="roleSelect" class="form-select" required>
                                <option value="">— Select Role —</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" data-role="<?= htmlspecialchars($r['role_name']) ?>">
                                        <?= ucfirst(htmlspecialchars($r['role_name'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Customer-only fields -->
                    <div id="customerFields" class="mt-3 p-3 rounded-3" style="display:none; background:#f0f9ff; border:1.5px solid var(--border);">
                        <p class="fw-semibold mb-2" style="color:var(--aqua-d);">📍 Customer Details</p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Service Area</label>
                                <select name="area_id" class="form-select">
                                    <option value="">— Select Area —</option>
                                    <?php foreach ($areas as $a): ?>
                                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Meter Number</label>
                                <input type="text" name="meter_number" class="form-control" placeholder="e.g. WM-001234">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" class="btn btn-aqua px-5 fw-semibold">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════
     EDIT USER MODAL
═══════════════════════════════ -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-edit">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="edit_id">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" id="edit_name" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Address</label>
                        <input type="text" id="edit_address" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" id="edit_phone" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select id="edit_role" class="form-select">
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= ucfirst(htmlspecialchars($r['role_name'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" id="edit_password" class="form-control" placeholder="Leave blank to keep current">
                            <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="edit_password" tabindex="-1">👁</button>
                        </div>
                    </div>
                </div>
                <div id="editError" class="alert alert-danger mt-3 d-none"></div>
            </div>

            <div class="modal-footer gap-2">
                <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="saveUserBtn" class="btn btn-warning px-5 fw-semibold">
                    <span id="saveBtnText">Save Changes</span>
                    <span id="saveBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════
     TOAST NOTIFICATION
═══════════════════════════════ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast align-items-center text-white border-0 rounded-3" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastMsg"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ================================================================
   UTILITIES
================================================================ */
function showToast(msg, type = 'success') {
    const el = document.getElementById('liveToast');
    const colors = { success: '#22c55e', danger: '#ef4444', warning: '#f59e0b' };
    el.style.background = colors[type] || colors.success;
    document.getElementById('toastMsg').innerHTML = msg;
    bootstrap.Toast.getOrCreateInstance(el, { delay: 3500 }).show();
}

function formatPHPhone(raw) {
    let v = raw.replace(/[\s\-]/g, '');
    if (v.startsWith('09') && v.length === 11) return '+63' + v.slice(1);
    if (/^9\d{9}$/.test(v))                    return '+63' + v;
    if (/^\+639\d{9}$/.test(v))                return v;
    return null;
}

function roleBadge(roleName) {
    const map = { admin:'role-admin', staff:'role-staff', customer:'role-customer', owner:'role-owner' };
    const cls = map[roleName] || 'role-default';
    return `<span class="role-badge ${cls}">${roleName}</span>`;
}

/* ================================================================
   ROLE-DEPENDENT CUSTOMER FIELDS (Add modal)
================================================================ */
document.getElementById('roleSelect').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex].dataset.role;
    document.getElementById('customerFields').style.display =
        selected === 'customer' ? 'block' : 'none';
});

/* ================================================================
   PHONE VALIDATION (Add modal)
================================================================ */
document.getElementById('addPhone').addEventListener('blur', function () {
    const val = this.value.trim();
    if (!val) return;
    const formatted = formatPHPhone(val);
    if (!formatted) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
        this.value = formatted;
    }
});

/* ================================================================
   PASSWORD TOGGLE
================================================================ */
document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        this.textContent = input.type === 'password' ? '👁' : '🙈';
    });
});

/* ================================================================
   LIVE SEARCH  (debounced)
================================================================ */
let searchTimer;
const searchInput   = document.getElementById('liveSearch');
const searchSpinner = document.getElementById('searchSpinner');

let currentLimit = <?= $limit ?>;
let currentSort  = '<?= $sortCol ?>';
let currentDir   = '<?= $sortDir ?>';

document.getElementById('limitSelect').addEventListener('change', function () {
    currentLimit = this.value;
    const url = new URL(window.location.href);
    url.searchParams.set('limit', currentLimit);
    url.searchParams.set('page',  1);
    window.location.href = url.toString();
});

searchInput.addEventListener('input', function () {
    clearTimeout(searchTimer);
    const q = this.value.trim();
    searchSpinner.style.display = 'block';

    searchTimer = setTimeout(() => {
        const params = new URLSearchParams({
            search: q,
            limit:  currentLimit,
            sort:   currentSort,
            dir:    currentDir
        });
        fetch(`ajax_search_users.php?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                searchSpinner.style.display = 'none';
                const tbody = document.getElementById('usersTbody');
                if (!data.users || data.users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No users found.</td></tr>`;
                    return;
                }
                tbody.innerHTML = data.users.map(u => buildRow(u)).join('');
                attachRowEvents();
            })
            .catch(() => { searchSpinner.style.display = 'none'; });
    }, 320);
});

/* ================================================================
   BUILD A TABLE ROW FROM JS
================================================================ */
const currentUserId = <?= (int)($_SESSION['user_id'] ?? 0) ?>;

function buildRow(u) {
    const actionBtn = (u.id != currentUserId)
        ? `<button class="btn btn-del btn-sm deleteUser" data-id="${u.id}">Delete</button>`
        : `<button class="btn btn-self btn-sm" disabled>You</button>`;

    return `<tr data-id="${u.id}">
        <td>${u.id}</td>
        <td>${escHtml(u.full_name)}</td>
        <td>${escHtml(u.email)}</td>
        <td>${escHtml(u.phone || '—')}</td>
        <td>${roleBadge(u.role_name)}</td>
        <td class="d-flex gap-1">
            <button class="btn btn-edit btn-sm editBtn"
                data-id="${u.id}"
                data-name="${escAttr(u.full_name)}"
                data-address="${escAttr(u.address || '')}"
                data-email="${escAttr(u.email)}"
                data-phone="${escAttr(u.phone || '')}"
                data-role="${u.role_id}"
                data-bs-toggle="modal"
                data-bs-target="#editUserModal">Edit</button>
            ${actionBtn}
        </td>
    </tr>`;
}

function escHtml(s)  { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s)  { return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

/* ================================================================
   ATTACH ROW EVENTS
================================================================ */
function attachRowEvents() {
    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('edit_id').value      = this.dataset.id;
            document.getElementById('edit_name').value    = this.dataset.name;
            document.getElementById('edit_address').value = this.dataset.address;
            document.getElementById('edit_email').value   = this.dataset.email;
            document.getElementById('edit_phone').value   = this.dataset.phone;
            document.getElementById('edit_role').value    = this.dataset.role;
            document.getElementById('edit_password').value = '';
            document.getElementById('editError').classList.add('d-none');
        });
    });

    document.querySelectorAll('.deleteUser').forEach(btn => {
        btn.addEventListener('click', function () {
            const id  = this.dataset.id;
            const row = this.closest('tr');

            Swal.fire({
                title: 'Delete this user?',
                text:  'This cannot be undone.',
                icon:  'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete',
                cancelButtonText:  'Cancel'
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch('ajax_delete_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded',
                               'X-Requested-With': 'XMLHttpRequest' },
                    body: 'id=' + encodeURIComponent(id)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        row.style.transition = 'opacity .3s';
                        row.style.opacity    = '0';
                        setTimeout(() => row.remove(), 320);
                        showToast('User deleted successfully.', 'danger');
                    } else {
                        Swal.fire('Error', data.message || 'Could not delete user.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Network error.', 'error'));
            });
        });
    });
}

attachRowEvents();

/* ================================================================
   SAVE (EDIT) USER — AJAX
================================================================ */
document.getElementById('saveUserBtn').addEventListener('click', function () {
    const id       = document.getElementById('edit_id').value;
    const name     = document.getElementById('edit_name').value.trim();
    const email    = document.getElementById('edit_email').value.trim();
    const errorBox = document.getElementById('editError');

    if (!name || !email) {
        errorBox.textContent = 'Name and email are required.';
        errorBox.classList.remove('d-none');
        return;
    }
    errorBox.classList.add('d-none');

    const saveTxt  = document.getElementById('saveBtnText');
    const saveSpin = document.getElementById('saveBtnSpinner');
    saveTxt.textContent = 'Saving…';
    saveSpin.classList.remove('d-none');
    this.disabled = true;

    const formData = new FormData();
    formData.append('id',        id);
    formData.append('full_name', name);
    formData.append('address',   document.getElementById('edit_address').value.trim());
    formData.append('email',     email);
    formData.append('phone',     document.getElementById('edit_phone').value.trim());
    formData.append('role_id',   document.getElementById('edit_role').value);
    formData.append('password',  document.getElementById('edit_password').value);

    fetch('ajax_update_user.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        saveTxt.textContent = 'Save Changes';
        saveSpin.classList.add('d-none');
        this.disabled = false;

        if (data.status === 'success') {
            const modalEl = document.getElementById('editUserModal');
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.hide();
            modalEl.addEventListener('hidden.bs.modal', function onHidden() {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                modalEl.removeEventListener('hidden.bs.modal', onHidden);
            }, { once: true });
            showToast('User updated successfully.', 'success');

            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.children[1].textContent = name;
                row.children[2].textContent = email;
                row.children[3].textContent = document.getElementById('edit_phone').value.trim() || '—';

                const roleSelect = document.getElementById('edit_role');
                const roleName   = roleSelect.options[roleSelect.selectedIndex].textContent.toLowerCase().trim();
                row.children[4].innerHTML = roleBadge(roleName);

                const editBtn = row.querySelector('.editBtn');
                if (editBtn) {
                    editBtn.dataset.name    = name;
                    editBtn.dataset.email   = email;
                    editBtn.dataset.address = document.getElementById('edit_address').value.trim();
                    editBtn.dataset.phone   = document.getElementById('edit_phone').value.trim();
                    editBtn.dataset.role    = document.getElementById('edit_role').value;
                }

                row.style.outline = '2px solid #22c55e';
                setTimeout(() => row.style.outline = '', 1800);
            }
        } else {
            errorBox.textContent = data.message || 'Update failed.';
            errorBox.classList.remove('d-none');
        }
    })
    .catch(() => {
        saveTxt.textContent = 'Save Changes';
        saveSpin.classList.add('d-none');
        this.disabled = false;
        errorBox.textContent = 'Network error. Please try again.';
        errorBox.classList.remove('d-none');
    });
});

/* ================================================================
   ADD FORM — client-side validation
================================================================ */
document.getElementById('addUserForm').addEventListener('submit', function (e) {
    const phone = document.getElementById('addPhone');
    if (phone.value.trim()) {
        const fmt = formatPHPhone(phone.value.trim());
        if (!fmt) {
            e.preventDefault();
            phone.classList.add('is-invalid');
            phone.focus();
            return;
        }
        phone.value = fmt;
    }
    if (!this.checkValidity()) {
        e.preventDefault();
        this.classList.add('was-validated');
    }
});
</script>

</body>
</html>

<?php
function renderUserRow(array $u, int $currentUserId): string {
    $roleColors = [
        'admin'    => 'role-admin',
        'staff'    => 'role-staff',
        'customer' => 'role-customer',
        'owner'    => 'role-owner',
    ];
    $cls = $roleColors[$u['role_name']] ?? 'role-default';

    $actionBtn = ($u['id'] !== $currentUserId)
        ? '<button class="btn btn-del btn-sm deleteUser" data-id="' . $u['id'] . '">Delete</button>'
        : '<button class="btn btn-self btn-sm" disabled>You</button>';

    return '<tr data-id="' . $u['id'] . '">
        <td>' . htmlspecialchars($u['id']) . '</td>
        <td>' . htmlspecialchars($u['full_name']) . '</td>
        <td>' . htmlspecialchars($u['email']) . '</td>
        <td>' . htmlspecialchars($u['phone'] ?: '—') . '</td>
        <td><span class="role-badge ' . $cls . '">' . htmlspecialchars($u['role_name']) . '</span></td>
        <td class="d-flex gap-1">
            <button class="btn btn-edit btn-sm editBtn"
                data-id="'      . $u['id'] . '"
                data-name="'    . htmlspecialchars($u['full_name'], ENT_QUOTES) . '"
                data-address="' . htmlspecialchars($u['address']  ?? '', ENT_QUOTES) . '"
                data-email="'   . htmlspecialchars($u['email'],    ENT_QUOTES) . '"
                data-phone="'   . htmlspecialchars($u['phone']    ?? '', ENT_QUOTES) . '"
                data-role="'    . $u['role_id'] . '"
                data-bs-toggle="modal"
                data-bs-target="#editUserModal">Edit</button>
            ' . $actionBtn . '
        </td>
    </tr>';
}

require_once __DIR__ . '/../../app/layouts/footer.php';
?>