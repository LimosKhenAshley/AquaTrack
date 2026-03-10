<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

/* FILTERS */
$search = $_GET['search'] ?? '';
$action = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

/* PAGINATION */
$limit = 20;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

/* BASE QUERY */
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.full_name LIKE ? OR a.description LIKE ? OR a.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($action) {
    $where[] = "a.action = ?";
    $params[] = $action;
}

if ($date_from) {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $date_to;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

/* FETCH LOGS */
$stmt = $pdo->prepare("
    SELECT a.*, u.full_name
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    $whereSQL
    ORDER BY a.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

/* TOTAL COUNT */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    $whereSQL
");
$countStmt->execute($params);
$totalLogs = $countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $limit);

/* SUMMARY */
$todayLogs = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at)=CURDATE()")->fetchColumn();
$totalAllLogs = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$totalLogins = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE action='LOGIN'")->fetchColumn();

/* DAILY CHART DATA */
$chartStmt = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as total
    FROM audit_logs
    GROUP BY DATE(created_at)
    ORDER BY day DESC
    LIMIT 7
");

$chartData = array_reverse($chartStmt->fetchAll(PDO::FETCH_ASSOC));

/* SUSPICIOUS LOGIN DETECTION */
$suspicious = $pdo->query("
    SELECT ip_address, COUNT(*) as attempts
    FROM audit_logs
    WHERE action='LOGIN'
    AND created_at > (NOW() - INTERVAL 5 MINUTE)
    GROUP BY ip_address
    HAVING attempts >= 5
")->fetchAll();

?>

<div class="container-fluid px-4 mt-4">
    <h3>🛡 Activity Logs</h3>

    <?php if ($suspicious): ?>
        <div class="alert alert-danger">
            🚨 Suspicious login activity detected!
            <ul>
                <?php foreach ($suspicious as $s): ?>
                    <li><?= $s['ip_address'] ?> (<?= $s['attempts'] ?> attempts)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- SUMMARY CARDS -->
    <div class="row mt-3">
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h4><?= $todayLogs ?></h4>
                    <p class="text-muted">Logs Today</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h4><?= $totalAllLogs ?></h4>
                    <p class="text-muted">Total Logs</p>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <h4><?= $totalLogins ?></h4>
                    <p class="text-muted">Total Logins</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card shadow-sm mt-4">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <label>Search</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                           class="form-control" placeholder="Search user, description, IP">
                </div>

                <div class="col-md-2">
                    <label>Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="LOGIN_SUCCESS" <?= $action == 'LOGIN_SUCCESS' ? 'selected' : '' ?>>LOGIN_SUCCESS</option>
                        <option value="LOGOUT" <?= $action == 'LOGOUT' ? 'selected' : '' ?>>LOGOUT</option>
                        <option value="CREATE" <?= $action == 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                        <option value="UPDATE" <?= $action == 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                        <option value="DELETE" <?= $action == 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= $date_from ?>" class="form-control">
                </div>

                <div class="col-md-2">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= $date_to ?>" class="form-control">
                </div>

                <div class="col-md-3 d-flex gap-2 align-items-end">
                    <button class="btn btn-primary w-100">Filter</button>
                    <a href="activity_logs.php" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- LOG TABLE -->
    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP</th>
                            <th>Date</th>
                            <th>View</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                            $rowClass = '';
                            if ($log['action'] == 'DELETE') $rowClass = 'table-danger';

                            $badge = "secondary";
                            if ($log['action'] == 'LOGIN_SUCCESS') $badge = "primary";
                            if ($log['action'] == 'CREATE') $badge = "success";
                            if ($log['action'] == 'UPDATE') $badge = "warning";
                            if ($log['action'] == 'DELETE') $badge = "danger";
                            ?>

                            <tr class="<?= $rowClass ?>">
                                <td><?= htmlspecialchars($log['full_name'] ?? 'System') ?></td>

                                <td>
                                    <span class="badge bg-<?= $badge ?>">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>

                                <td><?= htmlspecialchars((string)$log['description']) ?></td>
                                <td><?= htmlspecialchars((string)$log['ip_address']) ?></td>
                                <td><?= date("M d, Y h:i A", strtotime($log['created_at'])) ?></td>

                                <td>
                                    <button class="btn btn-sm btn-info"
                                            data-bs-toggle="modal"
                                            data-bs-target="#logModal"
                                            onclick="viewLog(
                                                '<?= htmlspecialchars(addslashes($log['full_name'] ?? 'System')) ?>',
                                                '<?= htmlspecialchars(addslashes($log['action'])) ?>',
                                                '<?= htmlspecialchars(addslashes((string) $log['description'])) ?>',
                                                '<?= htmlspecialchars(addslashes((string) $log['ip_address'])) ?>',
                                                '<?= $log['created_at'] ?>'
                                            )">
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">

            <!-- Previous -->
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                    Previous
                </a>
            </li>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link"
                    href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                        <?= $i ?>
                    </a>
                </li>

            <?php endfor; ?>

            <!-- Next -->
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link"
                href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&action=<?= urlencode($action) ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>">
                    Next
                </a>
            </li>

        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- LOG MODAL -->
<div class="modal fade" id="logModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Audit Log Details</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <p><b>User:</b> <span id="mUser"></span></p>
                <p><b>Action:</b> <span id="mAction"></span></p>
                <p><b>Description:</b> <span id="mDesc"></span></p>
                <p><b>IP:</b> <span id="mIP"></span></p>
                <p><b>Date:</b> <span id="mDate"></span></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    function viewLog(user, action, desc, ip, date) {
        document.getElementById('mUser').innerText = user;
        document.getElementById('mAction').innerText = action;
        document.getElementById('mDesc').innerText = desc;
        document.getElementById('mIP').innerText = ip;
        document.getElementById('mDate').innerText = date;
    }

    /* LIVE AUTO REFRESH */
    setInterval(function() {
        fetch("activity_logs.php")
            .then(res => res.text())
            .then(html => {
                let parser = new DOMParser();
                let doc = parser.parseFromString(html, 'text/html');
                document.querySelector("tbody").innerHTML = doc.querySelector("tbody").innerHTML;
            });
    }, 30000);
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>