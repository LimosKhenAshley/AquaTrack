<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$message = "";
$messageType = "success";

/* ── helpers ── */
function normalizePHPhone(string $raw): string {
    $p = preg_replace('/[\s\-]/', '', $raw);
    if (str_starts_with($p, '09'))    $p = '+63' . substr($p, 1);
    elseif (str_starts_with($p, '639')) $p = '+' . $p;
    return $p;
}

/* =========================
   ADD USER
========================= */
if (isset($_POST['add_user'])) {
    $full_name    = trim($_POST['full_name']    ?? '');
    $address      = trim($_POST['address']      ?? '');
    $email        = trim($_POST['email']        ?? '');
    $raw_phone    = trim($_POST['phone']        ?? '');
    $phone        = $raw_phone !== '' ? normalizePHPhone($raw_phone) : '';
    $role_id      = (int)($_POST['role_id']     ?? 0);
    $raw_password = $_POST['password']          ?? '';

    $nameRegex = "/^[a-zA-Z\s'\-]{2,100}$/";

    if ($full_name === '' || $email === '' || $raw_password === '' || $role_id === 0) {
        $message     = "All required fields must be filled in.";
        $messageType = "danger";
    } elseif (!preg_match($nameRegex, $full_name)) {
        $message     = "Full name contains invalid characters (letters, spaces, hyphens and apostrophes only).";
        $messageType = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+\-]+@gmail\.com$/', $email)) {
        $message     = "Only Gmail addresses are allowed (e.g. user@gmail.com).";
        $messageType = "danger";
    } elseif (
        strlen($raw_password) < 8 ||
        !preg_match('/[A-Z]/', $raw_password) ||
        !preg_match('/[0-9]/', $raw_password)
    ) {
        $message     = "Password must be at least 8 characters and include an uppercase letter and a number.";
        $messageType = "danger";
    } elseif ($phone !== '' && !preg_match('/^\+639\d{9}$/', $phone)) {
        $message     = "Invalid Philippine mobile number. Use 09XXXXXXXXX or +639XXXXXXXXX.";
        $messageType = "danger";
    } else {
        // ── Uniqueness checks ──
        $emailChk = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $emailChk->execute([$email]);

        $phoneChk = null;
        if ($phone !== '') {
            $phoneChk = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $phoneChk->execute([$phone]);
        }

        if ($emailChk->fetch()) {
            $message     = "That email address is already in use.";
            $messageType = "danger";
        } elseif ($phoneChk && $phoneChk->fetch()) {
            $message     = "That phone number is already registered to another user.";
            $messageType = "danger";
        } else {
            // Resolve role early so we can check meter uniqueness
            $roleStmtPre = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
            $roleStmtPre->execute([$role_id]);
            $role_name_pre = $roleStmtPre->fetchColumn();

            $meter_number = trim($_POST['meter_number'] ?? '');
            $meterError   = false;

            if ($role_name_pre === 'customer' && $meter_number !== '') {
                $meterChk = $pdo->prepare("SELECT id FROM customers WHERE meter_number = ?");
                $meterChk->execute([$meter_number]);
                if ($meterChk->fetch()) {
                    $message     = "That meter number is already assigned to another customer.";
                    $messageType = "danger";
                    $meterError  = true;
                }
            }

            if (!$meterError) {
                try {
                    $pdo->beginTransaction();

                    if (!$role_name_pre) {
                        throw new Exception("Invalid role selected.");
                    }

                    $password = password_hash($raw_password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, address, email, phone, password, role_id, status)
                        VALUES (?, ?, ?, ?, ?, ?, 'active')
                    ");
                    $stmt->execute([$full_name, $address, $email, $phone, $password, $role_id]);
                    $user_id = $pdo->lastInsertId();

                    switch ($role_name_pre) {
                        case 'staff':
                            $pdo->prepare("INSERT INTO staffs (user_id) VALUES (?)")->execute([$user_id]);
                            break;
                        case 'customer':
                            $pdo->prepare("INSERT INTO customers (user_id, area_id, meter_number) VALUES (?, ?, ?)")
                                ->execute([$user_id, $_POST['area_id'] ?? null, $meter_number ?: null]);
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
}

/* =========================
   FETCH ROLES & AREAS
========================= */
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll(PDO::FETCH_ASSOC);
$areas = $pdo->query("SELECT id, area_name FROM areas ORDER BY area_name")->fetchAll(PDO::FETCH_ASSOC);

/* =============================
   FILTER: show active/archived/all
============================= */
$statusFilter = $_GET['status_filter'] ?? 'active'; // 'active' | 'archived' | 'all'
if (!in_array($statusFilter, ['active', 'archived', 'all'])) $statusFilter = 'active';

/* =============================
   SEARCH, SORT & PAGINATION
============================= */
$search  = trim($_GET['search'] ?? '');
$_limitRaw = (int)($_GET['limit'] ?? 10);
$limit     = in_array($_limitRaw, [10, 25, 50, 100]) ? $_limitRaw : 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $limit;

$sortMap = [
    'id'        => 'u.id',
    'full_name' => 'u.full_name',
    'email'     => 'u.email',
    'phone'     => 'u.phone',
    'role_name' => 'r.role_name',
    'status'    => 'u.status',
];
$sortCol = isset($_GET['sort']) && array_key_exists($_GET['sort'], $sortMap)
    ? $_GET['sort'] : 'id';
$sortDir = (isset($_GET['dir']) && strtoupper($_GET['dir']) === 'ASC') ? 'ASC' : 'DESC';
$orderBy = $sortMap[$sortCol] . ' ' . $sortDir;

$searchParam = "%$search%";

$whereClauses = [];
$bindParams   = [];
if ($search !== '') {
    $whereClauses[] = "(u.full_name LIKE :search1 OR u.email LIKE :search2)";
    $bindParams[':search1'] = $searchParam;
    $bindParams[':search2'] = $searchParam;
}
if ($statusFilter !== 'all') {
    $whereClauses[] = "u.status = :status";
    $bindParams[':status'] = $statusFilter;
}
$whereSQL = $whereClauses ? ('WHERE ' . implode(' AND ', $whereClauses)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id $whereSQL");
$countStmt->execute($bindParams);
$totalUsers = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalUsers / $limit));
$page       = min($page, $totalPages);

$sql = "
    SELECT u.id, u.full_name, u.address, u.email, u.phone, u.role_id, r.role_name, u.status
    FROM users u
    JOIN roles r ON u.role_id = r.id
    $whereSQL
    ORDER BY {$orderBy} LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($bindParams as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$activeCount   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
$archivedCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'archived' OR status IS NULL")->fetchColumn();
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

        /* ── Status filter tabs ── */
        .status-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }
        .status-tab {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: .82rem;
            font-weight: 600;
            border: 1.5px solid #e2e8f0;
            background: #fff;
            color: var(--muted);
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .status-tab:hover { border-color: var(--aqua); color: var(--aqua-d); }
        .status-tab.active-tab { background: var(--aqua); border-color: var(--aqua); color: #fff; }
        .status-tab.archived-tab.active-tab { background: #64748b; border-color: #64748b; color: #fff; }
        .status-tab.all-tab.active-tab { background: #0f172a; border-color: #0f172a; color: #fff; }
        .tab-badge {
            background: rgba(255,255,255,.3);
            border-radius: 20px;
            padding: 1px 7px;
            font-size: .72rem;
        }
        .status-tab:not([class*="active-tab"]) .tab-badge {
            background: #f1f5f9;
            color: var(--muted);
        }

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
        .table tbody tr.row-archived { opacity: .65; background: #f8fafc; }
        .table tbody tr.row-archived:hover { background: #f1f5f9; }
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

        /* status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }
        .status-active   { background:#dcfce7; color:#15803d; }
        .status-archived { background:#f1f5f9; color:#64748b; }
        .status-dot {
            width: 6px; height: 6px; border-radius: 50%;
            display: inline-block;
        }
        .dot-active   { background: #22c55e; }
        .dot-archived { background: #94a3b8; }

        /* ── Buttons ── */
        .btn-aqua   { background: var(--aqua); color: #fff; border: none; border-radius: 10px; }
        .btn-aqua:hover { background: var(--aqua-d); color: #fff; }
        .btn-edit   { background: #fef3c7; color: #92400e; border: none; border-radius: 8px; font-size: .8rem; padding: 5px 12px; }
        .btn-edit:hover { background: var(--warn); color: #fff; }
        .btn-archive { background: #f1f5f9; color: #475569; border: none; border-radius: 8px; font-size: .8rem; padding: 5px 12px; }
        .btn-archive:hover { background: #64748b; color: #fff; }
        .btn-restore { background: #dcfce7; color: #15803d; border: none; border-radius: 8px; font-size: .8rem; padding: 5px 12px; }
        .btn-restore:hover { background: #22c55e; color: #fff; }
        .btn-self    { background: #f1f5f9; color: var(--muted); border: none; border-radius: 8px; font-size: .75rem; padding: 5px 10px; cursor: default; }

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

        /* ── Validation feedback ── */
        .field-feedback { font-size: .78rem; margin-top: 4px; min-height: 1em; }
        .field-feedback.valid   { color: #16a34a; }
        .field-feedback.invalid { color: #dc2626; }

        /* password strength bar */
        .pw-strength-bar { height: 5px; border-radius: 4px; transition: width .3s, background .3s; background: #e2e8f0; }
        .pw-strength-wrap { background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 6px; }

        @media(max-width:576px){
            .page-header { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

    <div class="page-header">
        <i class="bi bi-people-fill fs-4 text-primary"></i>
        <div>
            <h4>User Management</h4>
            <small class="text-muted"><?= $totalUsers ?> <?= $statusFilter === 'all' ? 'total' : $statusFilter ?> users</small>
        </div>
        <div class="ms-auto">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-plus-lg me-1"></i>Add User
            </button>
        </div>
    </div>

<div class="container-fluid px-4 pb-5" style="max-width:1200px;">

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show rounded-3" role="alert">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Status filter tabs ── -->
    <?php
    function tabUrl($sf, $search, $limit, $sortCol, $sortDir) {
        return '?status_filter=' . $sf . '&search=' . urlencode($search) . '&limit=' . $limit . '&sort=' . $sortCol . '&dir=' . $sortDir . '&page=1';
    }
    ?>
    <div class="status-tabs">
        <a href="<?= tabUrl('active', $search, $limit, $sortCol, $sortDir) ?>"
           class="status-tab active-tab <?= $statusFilter === 'active' ? 'active-tab' : '' ?>">
            <span class="status-dot dot-active"></span> Active
            <span class="tab-badge"><?= $activeCount ?></span>
        </a>
        <a href="<?= tabUrl('archived', $search, $limit, $sortCol, $sortDir) ?>"
           class="status-tab archived-tab <?= $statusFilter === 'archived' ? 'active-tab' : '' ?>">
            <i class="bi bi-archive" style="font-size:.75rem"></i> Archived
            <span class="tab-badge"><?= $archivedCount ?></span>
        </a>
        <a href="<?= tabUrl('all', $search, $limit, $sortCol, $sortDir) ?>"
           class="status-tab all-tab <?= $statusFilter === 'all' ? 'active-tab' : '' ?>">
            All Users
            <span class="tab-badge"><?= $activeCount + $archivedCount ?></span>
        </a>
    </div>

    <!-- ── Toolbar ── -->
    <div class="toolbar">
        <span class="entries-label">Show</span>
        <select id="limitSelect" class="entries-select">
            <?php foreach ([10, 25, 50, 100] as $opt): ?>
                <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?></option>
            <?php endforeach; ?>
        </select>
        <span class="entries-label">entries</span>

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
            <a href="?status_filter=<?= $statusFilter ?>" class="btn btn-outline-secondary rounded-3">✕ Clear</a>
        <?php endif; ?>
    </div>

    <!-- ── Table ── -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <?php
                        function sortLink($label, $col, $currentSort, $currentDir, $search, $limit, $page, $statusFilter) {
                            $isActive = $currentSort === $col;
                            $nextDir  = ($isActive && $currentDir === 'DESC') ? 'ASC' : 'DESC';
                            $url = "?sort={$col}&dir={$nextDir}&search=" . urlencode($search) . "&limit={$limit}&page={$page}&status_filter={$statusFilter}";
                            $arrow = $isActive ? ($currentDir === 'ASC' ? '▲' : '▼') : '⇅';
                            $arrowClass = $isActive ? 'sort-arrow active' : 'sort-arrow';
                            return "<th class='sortable'><a href='{$url}' class='text-white text-decoration-none'>{$label}<span class='{$arrowClass}'>{$arrow}</span></a></th>";
                        }
                        ?>
                        <th style="width:60px" class="sortable">
                            <a href="?sort=id&dir=<?= ($sortCol==='id' && $sortDir==='DESC') ? 'ASC' : 'DESC' ?>&search=<?= urlencode($search) ?>&limit=<?= $limit ?>&page=<?= $page ?>&status_filter=<?= $statusFilter ?>" class="text-white text-decoration-none">
                                #<span class="sort-arrow <?= $sortCol==='id' ? 'active' : '' ?>"><?= $sortCol==='id' ? ($sortDir==='ASC' ? '▲' : '▼') : '⇅' ?></span>
                            </a>
                        </th>
                        <?= sortLink('Name',   'full_name', $sortCol, $sortDir, $search, $limit, $page, $statusFilter) ?>
                        <?= sortLink('Email',  'email',     $sortCol, $sortDir, $search, $limit, $page, $statusFilter) ?>
                        <?= sortLink('Phone',  'phone',     $sortCol, $sortDir, $search, $limit, $page, $statusFilter) ?>
                        <?= sortLink('Role',   'role_name', $sortCol, $sortDir, $search, $limit, $page, $statusFilter) ?>
                        <?= sortLink('Status', 'status',    $sortCol, $sortDir, $search, $limit, $page, $statusFilter) ?>
                        <th style="width:160px">Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTbody">
                    <?php foreach ($users as $u): ?>
                        <?= renderUserRow($u, (int)($_SESSION['user_id'] ?? 0)) ?>
                    <?php endforeach; ?>

                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>
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
                    <a class="page-link" href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sortCol ?>&dir=<?= $sortDir ?>&limit=<?= $limit ?>&status_filter=<?= $statusFilter ?>">‹ Prev</a>
                </li>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            for ($i = $start; $i <= $end; $i++):
            ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sortCol ?>&dir=<?= $sortDir ?>&limit=<?= $limit ?>&status_filter=<?= $statusFilter ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
            ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&sort=<?= $sortCol ?>&dir=<?= $sortDir ?>&limit=<?= $limit ?>&status_filter=<?= $statusFilter ?>">Next ›</a>
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
                        <!-- Full Name -->
                        <div class="col-md-6">
                            <label class="form-label">Full Name *</label>
                            <input type="text" name="full_name" id="addFullName" class="form-control" placeholder="John Dela Cruz" required>
                            <div class="field-feedback" id="addNameFeedback"></div>
                        </div>

                        <!-- Address -->
                        <div class="col-md-6">
                            <label class="form-label">Address</label>
                            <input type="text" name="address" class="form-control" placeholder="123 Street, City">
                        </div>

                        <!-- Email -->
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" id="addEmail" class="form-control" placeholder="user@example.com" required>
                            <div class="field-feedback" id="addEmailFeedback"></div>
                        </div>

                        <!-- Phone -->
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" id="addPhone" name="phone" class="form-control" placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                            <div class="field-feedback" id="addPhoneFeedback"></div>
                        </div>

                        <!-- Password -->
                        <div class="col-md-6">
                            <label class="form-label">Password *</label>
                            <div class="input-group">
                                <input type="password" name="password" id="addPassword" class="form-control"
                                       placeholder="Min. 8 chars, 1 uppercase, 1 number" required minlength="8">
                                <button type="button" class="btn btn-outline-secondary toggle-pw"
                                        data-target="addPassword" tabindex="-1">👁</button>
                            </div>
                            <div class="pw-strength-wrap mt-1">
                                <div class="pw-strength-bar" id="addPwBar" style="width:0%"></div>
                            </div>
                            <div class="field-feedback" id="addPwFeedback"></div>
                        </div>

                        <!-- Confirm Password -->
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password *</label>
                            <div class="input-group">
                                <input type="password" id="addConfirmPassword" class="form-control"
                                       placeholder="Re-enter password" required>
                                <button type="button" class="btn btn-outline-secondary toggle-pw"
                                        data-target="addConfirmPassword" tabindex="-1">👁</button>
                            </div>
                            <div class="field-feedback" id="addConfirmFeedback"></div>
                        </div>

                        <!-- Role -->
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
                                <input type="text" name="meter_number" id="addMeterNumber"
                                       class="form-control" placeholder="e.g. WM-001234">
                                <div class="field-feedback" id="addMeterFeedback"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_user" id="addUserBtn" class="btn btn-aqua px-5 fw-semibold" disabled>Add User</button>
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
    const colors = { success: '#22c55e', danger: '#ef4444', warning: '#f59e0b', info: '#0ea5e9' };
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

function statusBadge(status) {
    if (status === 'active') {
        return `<span class="status-badge status-active"><span class="status-dot dot-active"></span>Active</span>`;
    }
    return `<span class="status-badge status-archived"><span class="status-dot dot-archived"></span>Archived</span>`;
}

/* ================================================================
   ADD MODAL — VALIDATION STATE
================================================================ */
const addValidState = {
    name:     false,
    email:    false,
    password: false,
    confirm:  false,
    phone:    true,  // optional, starts valid
    meter:    true,  // optional / shown only for customer
};

function updateAddBtn() {
    document.getElementById('addUserBtn').disabled =
        !Object.values(addValidState).every(Boolean);
}

/* ── Full Name ── */
const addFullName      = document.getElementById('addFullName');
const addNameFeedback  = document.getElementById('addNameFeedback');
const nameRegex        = /^[a-zA-Z\s'\-]{2,100}$/;

addFullName.addEventListener('input', function () {
    const v = this.value.trim();
    if (v === '') {
        addNameFeedback.textContent = '';
        addNameFeedback.className   = 'field-feedback';
        addValidState.name = false;
    } else if (!nameRegex.test(v)) {
        addNameFeedback.textContent = 'Letters, spaces, hyphens and apostrophes only.';
        addNameFeedback.className   = 'field-feedback invalid';
        addValidState.name = false;
    } else {
        addNameFeedback.textContent = '✓ Looks good';
        addNameFeedback.className   = 'field-feedback valid';
        addValidState.name = true;
    }
    updateAddBtn();
});

/* ── Email ── */
const addEmailInput    = document.getElementById('addEmail');
const addEmailFeedback = document.getElementById('addEmailFeedback');
let emailTimer;

addEmailInput.addEventListener('input', function () {
    clearTimeout(emailTimer);
    const v = this.value.trim();
    if (v === '') {
        addEmailFeedback.textContent = '';
        addEmailFeedback.className   = 'field-feedback';
        addValidState.email = false;
        updateAddBtn();
        return;
    }
    const gmailRegex = /^[a-zA-Z0-9._%+\-]+@gmail\.com$/;
    if (!gmailRegex.test(v)) {
        addEmailFeedback.textContent = 'Only Gmail addresses are allowed (e.g. user@gmail.com).';
        addEmailFeedback.className   = 'field-feedback invalid';
        addValidState.email = false;
        updateAddBtn();
        return;
    }
    addEmailFeedback.textContent = 'Checking…';
    addEmailFeedback.className   = 'field-feedback';
    emailTimer = setTimeout(() => {
        fetch(`../auth/check_availability.php?email=${encodeURIComponent(v)}`)
            .then(r => r.json())
            .then(data => {
                if (data.status === 'error') {
                    addEmailFeedback.textContent = data.message;
                    addEmailFeedback.className   = 'field-feedback invalid';
                    addValidState.email = false;
                } else {
                    addEmailFeedback.textContent = '✓ Email is available';
                    addEmailFeedback.className   = 'field-feedback valid';
                    addValidState.email = true;
                }
                updateAddBtn();
            })
            .catch(() => {
                // Network error — allow submit; server will recheck
                addEmailFeedback.textContent = '';
                addValidState.email = true;
                updateAddBtn();
            });
    }, 500);
});

/* ── Password strength ── */
const addPasswordInput = document.getElementById('addPassword');
const addPwBar         = document.getElementById('addPwBar');
const addPwFeedback    = document.getElementById('addPwFeedback');

addPasswordInput.addEventListener('input', function () {
    const v = this.value;
    let score = 0;
    if (v.length >= 8)             score++;
    if (/[A-Z]/.test(v))           score++;
    if (/[0-9]/.test(v))           score++;
    if (/[^A-Za-z0-9]/.test(v))   score++;

    const pct    = (score / 4) * 100;
    const colors = ['#ef4444','#f59e0b','#3b82f6','#22c55e'];
    const labels = ['Too weak','Moderate','Strong','Very strong'];

    addPwBar.style.width      = pct + '%';
    addPwBar.style.background = colors[score - 1] || '#e2e8f0';

    const meetsMin = v.length >= 8 && /[A-Z]/.test(v) && /[0-9]/.test(v);
    if (!v) {
        addPwFeedback.textContent = '';
        addPwFeedback.className   = 'field-feedback';
        addPwBar.style.width      = '0%';
        addValidState.password    = false;
    } else if (!meetsMin) {
        addPwFeedback.textContent = (labels[score - 1] || 'Too weak') + ' — needs 8+ chars, 1 uppercase, 1 number.';
        addPwFeedback.className   = 'field-feedback invalid';
        addValidState.password    = false;
    } else {
        addPwFeedback.textContent = '✓ ' + (labels[score - 1] || 'OK');
        addPwFeedback.className   = 'field-feedback valid';
        addValidState.password    = true;
    }
    // Re-check confirm match
    checkConfirm();
    updateAddBtn();
});

/* ── Confirm Password ── */
const addConfirmInput    = document.getElementById('addConfirmPassword');
const addConfirmFeedback = document.getElementById('addConfirmFeedback');

function checkConfirm() {
    const v = addConfirmInput.value;
    if (!v) {
        addConfirmFeedback.textContent = '';
        addConfirmFeedback.className   = 'field-feedback';
        addValidState.confirm = false;
        return;
    }
    if (v !== addPasswordInput.value) {
        addConfirmFeedback.textContent = 'Passwords do not match.';
        addConfirmFeedback.className   = 'field-feedback invalid';
        addValidState.confirm = false;
    } else {
        addConfirmFeedback.textContent = '✓ Passwords match';
        addConfirmFeedback.className   = 'field-feedback valid';
        addValidState.confirm = true;
    }
    updateAddBtn();
}
addConfirmInput.addEventListener('input', checkConfirm);

/* ── Phone ── */
const addPhoneInput    = document.getElementById('addPhone');
const addPhoneFeedback = document.getElementById('addPhoneFeedback');

addPhoneInput.addEventListener('blur', function () {
    const raw = this.value.trim();
    if (!raw) {
        addPhoneFeedback.textContent = '';
        addPhoneFeedback.className   = 'field-feedback';
        addValidState.phone = true; // optional
        updateAddBtn();
        return;
    }
    const formatted = formatPHPhone(raw);
    if (!formatted) {
        addPhoneFeedback.textContent = 'Invalid PH number. Use 09XXXXXXXXX or +639XXXXXXXXX.';
        addPhoneFeedback.className   = 'field-feedback invalid';
        addValidState.phone = false;
        updateAddBtn();
        return;
    }
    // Format valid — check for duplicates in DB
    this.value = formatted;
    addPhoneFeedback.textContent = 'Checking…';
    addPhoneFeedback.className   = 'field-feedback';

    fetch(`../auth/check_availability.php?phone=${encodeURIComponent(formatted)}`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'error') {
                addPhoneFeedback.textContent = data.message;
                addPhoneFeedback.className   = 'field-feedback invalid';
                addValidState.phone = false;
            } else {
                addPhoneFeedback.textContent = '✓ Valid phone number';
                addPhoneFeedback.className   = 'field-feedback valid';
                addValidState.phone = true;
            }
            updateAddBtn();
        })
        .catch(() => {
            // Network error — allow submit; server will recheck
            addPhoneFeedback.textContent = '✓ Valid phone number';
            addPhoneFeedback.className   = 'field-feedback valid';
            addValidState.phone = true;
            updateAddBtn();
        });
});

/* ── Role-dependent customer fields ── */
document.getElementById('roleSelect').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex].dataset.role;
    const show = selected === 'customer';
    document.getElementById('customerFields').style.display = show ? 'block' : 'none';
    // Reset meter state when hidden
    if (!show) {
        addValidState.meter = true;
        document.getElementById('addMeterFeedback').textContent = '';
        updateAddBtn();
    }
});

/* ── Meter Number (customer) ── */
const addMeterInput    = document.getElementById('addMeterNumber');
const addMeterFeedback = document.getElementById('addMeterFeedback');

addMeterInput.addEventListener('blur', function () {
    const v = this.value.trim();
    if (!v) {
        addMeterFeedback.textContent = '';
        addMeterFeedback.className   = 'field-feedback';
        addValidState.meter = true;
        updateAddBtn();
        return;
    }
    addMeterFeedback.textContent = 'Checking…';
    addMeterFeedback.className   = 'field-feedback';

    fetch(`../auth/check_availability.php?meter_number=${encodeURIComponent(v)}`)
        .then(r => r.json())
        .then(data => {
            if (data.status === 'error') {
                addMeterFeedback.textContent = data.message;
                addMeterFeedback.className   = 'field-feedback invalid';
                addValidState.meter = false;
            } else {
                addMeterFeedback.textContent = '✓ Meter number is available';
                addMeterFeedback.className   = 'field-feedback valid';
                addValidState.meter = true;
            }
            updateAddBtn();
        })
        .catch(() => {
            addMeterFeedback.textContent = '';
            addValidState.meter = true;
            updateAddBtn();
        });
});

/* ── Reset add form when modal closes ── */
document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('addUserForm').reset();
    ['addNameFeedback','addEmailFeedback','addPhoneFeedback',
     'addPwFeedback','addConfirmFeedback','addMeterFeedback'].forEach(id => {
        const el = document.getElementById(id);
        if (el) { el.textContent = ''; el.className = 'field-feedback'; }
    });
    document.getElementById('addPwBar').style.width = '0%';
    document.getElementById('customerFields').style.display = 'none';
    addValidState.name = addValidState.email = addValidState.password =
        addValidState.confirm = false;
    addValidState.phone = addValidState.meter = true;
    document.getElementById('addUserBtn').disabled = true;
});

/* ── Final submit guard ── */
document.getElementById('addUserForm').addEventListener('submit', function (e) {
    // Re-check phone format one last time
    const phone = addPhoneInput.value.trim();
    if (phone) {
        const fmt = formatPHPhone(phone);
        if (!fmt) {
            e.preventDefault();
            addPhoneFeedback.textContent = 'Invalid PH number. Use 09XXXXXXXXX or +639XXXXXXXXX.';
            addPhoneFeedback.className   = 'field-feedback invalid';
            addValidState.phone = false;
            updateAddBtn();
            return;
        }
        addPhoneInput.value = fmt;
    }
    if (!this.checkValidity()) {
        e.preventDefault();
        this.classList.add('was-validated');
    }
});

/* ================================================================
   PASSWORD TOGGLE (all modals)
================================================================ */
document.querySelectorAll('.toggle-pw').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        this.textContent = input.type === 'password' ? '👁' : '🙈';
    });
});

/* ================================================================
   LIVE SEARCH (debounced)
================================================================ */
let searchTimer;
const searchInput   = document.getElementById('liveSearch');
const searchSpinner = document.getElementById('searchSpinner');

let currentLimit        = <?= $limit ?>;
let currentSort         = '<?= $sortCol ?>';
let currentDir          = '<?= $sortDir ?>';
let currentStatusFilter = '<?= $statusFilter ?>';

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
            search:        q,
            limit:         currentLimit,
            sort:          currentSort,
            dir:           currentDir,
            status_filter: currentStatusFilter
        });
        fetch(`ajax_search_users.php?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                searchSpinner.style.display = 'none';
                const tbody = document.getElementById('usersTbody');
                if (!data.users || data.users.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No users found.</td></tr>`;
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
    const isArchived = u.status === 'archived';
    const rowClass   = isArchived ? ' class="row-archived"' : '';

    let actionBtn = '';
    if (u.id == currentUserId) {
        actionBtn = `<button class="btn btn-self btn-sm" disabled>You</button>`;
    } else if (isArchived) {
        actionBtn = `<button class="btn btn-restore btn-sm archiveBtn" data-id="${u.id}" data-action="restore">
                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                     </button>`;
    } else {
        actionBtn = `<button class="btn btn-archive btn-sm archiveBtn" data-id="${u.id}" data-action="archive">
                        <i class="bi bi-archive"></i> Archive
                     </button>`;
    }

    return `<tr data-id="${u.id}"${rowClass}>
        <td>${u.id}</td>
        <td>${escHtml(u.full_name)}</td>
        <td>${escHtml(u.email)}</td>
        <td>${escHtml(u.phone || '—')}</td>
        <td>${roleBadge(u.role_name)}</td>
        <td>${statusBadge(u.status)}</td>
        <td class="d-flex gap-1 flex-wrap">
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

    document.querySelectorAll('.archiveBtn').forEach(btn => {
        btn.addEventListener('click', function () {
            const id     = this.dataset.id;
            const action = this.dataset.action;
            const row    = this.closest('tr');
            const isArchiving = action === 'archive';

            Swal.fire({
                title: isArchiving ? 'Archive this user?' : 'Restore this user?',
                text:  isArchiving
                    ? 'The user will be deactivated and hidden from active lists.'
                    : 'The user will be restored to active status.',
                icon:  isArchiving ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: isArchiving ? '#64748b' : '#22c55e',
                confirmButtonText: isArchiving ? 'Yes, archive' : 'Yes, restore',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (!result.isConfirmed) return;

                fetch('ajax_archive_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `id=${encodeURIComponent(id)}&action=${encodeURIComponent(action)}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'success') {
                        const newStatus = data.new_status;

                        if (currentStatusFilter !== 'all') {
                            row.style.transition = 'opacity .3s';
                            row.style.opacity    = '0';
                            setTimeout(() => row.remove(), 320);
                        } else {
                            row.classList.toggle('row-archived', newStatus === 'archived');
                            row.children[5].innerHTML = statusBadge(newStatus);

                            const actionTd = row.children[6];
                            const oldBtn   = actionTd.querySelector('.archiveBtn');
                            if (oldBtn) {
                                if (newStatus === 'archived') {
                                    oldBtn.className = 'btn btn-restore btn-sm archiveBtn';
                                    oldBtn.dataset.action = 'restore';
                                    oldBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Restore';
                                } else {
                                    oldBtn.className = 'btn btn-archive btn-sm archiveBtn';
                                    oldBtn.dataset.action = 'archive';
                                    oldBtn.innerHTML = '<i class="bi bi-archive"></i> Archive';
                                }
                                attachRowEvents();
                            }
                        }

                        showToast(
                            isArchiving ? 'User archived successfully.' : 'User restored successfully.',
                            isArchiving ? 'warning' : 'success'
                        );
                    } else {
                        Swal.fire('Error', data.message || 'Action failed.', 'error');
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
    $cls    = $roleColors[$u['role_name']] ?? 'role-default';
    $status = $u['status'] ?? 'active';

    $statusBadge = ($status === 'active')
        ? '<span class="status-badge status-active"><span class="status-dot dot-active"></span>Active</span>'
        : '<span class="status-badge status-archived"><span class="status-dot dot-archived"></span>Archived</span>';

    $rowClass = $status === 'archived' ? ' class="row-archived"' : '';

    if ($u['id'] === $currentUserId) {
        $actionBtn = '<button class="btn btn-self btn-sm" disabled>You</button>';
    } elseif ($status === 'archived') {
        $actionBtn = '<button class="btn btn-restore btn-sm archiveBtn" data-id="' . $u['id'] . '" data-action="restore">
                        <i class="bi bi-arrow-counterclockwise"></i> Restore
                      </button>';
    } else {
        $actionBtn = '<button class="btn btn-archive btn-sm archiveBtn" data-id="' . $u['id'] . '" data-action="archive">
                        <i class="bi bi-archive"></i> Archive
                      </button>';
    }

    return '<tr data-id="' . $u['id'] . '"' . $rowClass . '>
        <td>' . htmlspecialchars($u['id']) . '</td>
        <td>' . htmlspecialchars($u['full_name']) . '</td>
        <td>' . htmlspecialchars($u['email']) . '</td>
        <td>' . htmlspecialchars($u['phone'] ?: '—') . '</td>
        <td><span class="role-badge ' . $cls . '">' . htmlspecialchars($u['role_name']) . '</span></td>
        <td>' . $statusBadge . '</td>
        <td class="d-flex gap-1 flex-wrap">
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