<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';

/* =====================================================
   AJAX REQUEST (SEARCH + FILTER + PAGINATION)
=====================================================*/
if (isset($_GET['ajax'])) {

    $search    = trim($_GET['search']    ?? '');
    $method    = $_GET['method']         ?? '';
    $date_from = $_GET['date_from']      ?? '';
    $date_to   = $_GET['date_to']        ?? '';
    $page      = max(1, (int)($_GET['page'] ?? 1));

    $limit  = 10;
    $offset = ($page - 1) * $limit;

    $where  = [];
    $params = [];

    if ($search) {
        $where[]  = "(u.full_name LIKE ? OR c.meter_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($method) {
        $where[]  = "p.method = ?";
        $params[] = $method;
    }

    if ($date_from) {
        $where[]  = "DATE(p.payment_date) >= ?";
        $params[] = $date_from;
    }

    if ($date_to) {
        $where[]  = "DATE(p.payment_date) <= ?";
        $params[] = $date_to;
    }

    $whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

    /* ── total count (for pagination info) ── */
    $countSQL  = "
        SELECT COUNT(*)
        FROM payments p
        JOIN bills b     ON p.bill_id     = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN users u     ON c.user_id     = u.id
        $whereSQL
    ";
    $countStmt = $pdo->prepare($countSQL);
    $countStmt->execute($params);
    $totalRows  = (int) $countStmt->fetchColumn();
    $totalPages = max(1, (int) ceil($totalRows / $limit));

    /* ── rows ── */
    $sql = "
        SELECT
            p.id           AS payment_id,
            u.full_name,
            c.meter_number,
            p.amount_paid,
            p.method,
            p.payment_date
        FROM payments p
        JOIN bills b     ON p.bill_id     = b.id
        JOIN customers c ON b.customer_id = c.id
        JOIN users u     ON c.user_id     = u.id
        $whereSQL
        ORDER BY p.payment_date DESC
        LIMIT $limit OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* ── respond as JSON so JS can render rows AND pagination ── */
    $rows = '';
    foreach ($payments as $p) {
        $badge = match ($p['method']) {
            'cash'   => 'success',
            'online' => 'primary',
            'bank'   => 'warning',
            default  => 'secondary'
        };

        $rows .= "<tr>
            <td>" . date('M d, Y h:i A', strtotime($p['payment_date'])) . "</td>
            <td>" . htmlspecialchars($p['full_name'])    . "</td>
            <td>" . htmlspecialchars($p['meter_number']) . "</td>
            <td>₱" . number_format($p['amount_paid'], 2) . "</td>
            <td><span class='badge bg-$badge'>" . ucfirst($p['method']) . "</span></td>
            <td>
                <a target='_blank'
                   class='btn btn-secondary btn-sm'
                   href='../shared/receipt_pdf.php?payment_id=" . urlencode($p['payment_id']) . "'>
                   Receipt
                </a>
            </td>
        </tr>";
    }

    if (!$payments) {
        $rows = "<tr><td colspan='6' class='text-center text-muted py-4'>
                    No payment records found.
                 </td></tr>";
    }

    header('Content-Type: application/json');
    echo json_encode([
        'rows'        => $rows,
        'total'       => $totalRows,
        'page'        => $page,
        'totalPages'  => $totalPages,
    ]);
    exit;
}

/* =====================================================
   SUMMARY CARDS
=====================================================*/
$todayTotal = $pdo->query("
    SELECT COALESCE(SUM(amount_paid), 0)
    FROM payments
    WHERE DATE(payment_date) = CURDATE()
")->fetchColumn();

$todayCount = $pdo->query("
    SELECT COUNT(*)
    FROM payments
    WHERE DATE(payment_date) = CURDATE()
")->fetchColumn();

require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
?>

<div class="container-fluid px-4 mt-4">

    <h3 class="mb-3">💰 Payment History</h3>

    <!-- SUMMARY CARDS -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Today's Collection</h6>
                    <h4>₱<?= number_format($todayTotal, 2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Transactions Today</h6>
                    <h4><?= $todayCount ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <!-- FILTERS -->
            <div class="row mb-3 g-2 align-items-end">
                <div class="col-md-3">
                    <label>Search:</label>
                    <input id="search" class="form-control"
                           placeholder="Search customer or meter...">
                </div>

                <div class="col-md-2">
                    <label>Payment Method:</label>
                    <select id="method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="bank">Card/Bank</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label>Date From:</label>
                    <input type="date" id="date_from" class="form-control">
                </div>

                <div class="col-md-2">
                    <label>Date To:</label>
                    <input type="date" id="date_to" class="form-control">
                </div>

                <div class="col-md-3 text-end">
                    <button id="clearFilters" class="btn btn-outline-secondary btn-sm me-1"
                            title="Clear all filters">
                        ✕ Clear
                    </button>
                    <a href="export_payments.php" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary btn-sm">
                        Print
                    </button>
                </div>
            </div>

            <!-- RESULT INFO + LOADING -->
            <div class="d-flex align-items-center mb-2 gap-3">
                <div id="resultInfo" class="text-muted small"></div>
                <div id="loading" class="d-none">
                    <div class="spinner-border spinner-border-sm text-primary"></div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Meter #</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th width="120">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="paymentTable"></tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
                <div id="pageInfo" class="text-muted small"></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                </nav>
            </div>

        </div>
    </div>
</div>

<script>
let currentPage  = 1;
let totalPages   = 1;
let debounceTimer = null;

/* ── fetch & render ── */
function loadPayments(resetPage = false) {
    if (resetPage) currentPage = 1;

    document.getElementById('loading').classList.remove('d-none');

    const params = new URLSearchParams({
        ajax      : 1,
        search    : document.getElementById('search').value.trim(),
        method    : document.getElementById('method').value,
        date_from : document.getElementById('date_from').value,
        date_to   : document.getElementById('date_to').value,
        page      : currentPage
    });

    fetch('?' + params.toString())
        .then(res => res.json())
        .then(data => {
            totalPages = data.totalPages;

            /* rows */
            document.getElementById('paymentTable').innerHTML = data.rows;

            /* result info */
            const start = data.total === 0 ? 0 : (data.page - 1) * 10 + 1;
            const end   = Math.min(data.page * 10, data.total);
            document.getElementById('resultInfo').textContent =
                data.total === 0
                    ? 'No results'
                    : `Showing ${start}–${end} of ${data.total} record${data.total !== 1 ? 's' : ''}`;

            /* pagination */
            renderPagination(data.page, data.totalPages);

            document.getElementById('loading').classList.add('d-none');
        })
        .catch(() => {
            document.getElementById('loading').classList.add('d-none');
        });
}

/* ── pagination renderer ── */
function renderPagination(page, pages) {
    const ul = document.getElementById('pagination');
    ul.innerHTML = '';

    if (pages <= 1) {
        document.getElementById('pageInfo').textContent = '';
        return;
    }

    document.getElementById('pageInfo').textContent = `Page ${page} of ${pages}`;

    const makeItem = (label, target, disabled = false, active = false) => {
        const li = document.createElement('li');
        li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
        const a = document.createElement('a');
        a.className   = 'page-link';
        a.href        = '#';
        a.innerHTML   = label;
        a.addEventListener('click', e => {
            e.preventDefault();
            if (!disabled && !active) {
                currentPage = target;
                loadPayments();
            }
        });
        li.appendChild(a);
        return li;
    };

    /* prev */
    ul.appendChild(makeItem('&laquo;', page - 1, page === 1));

    /* page numbers — show a window around current page */
    const delta = 2;
    let start = Math.max(1, page - delta);
    let end   = Math.min(pages, page + delta);

    if (start > 1) {
        ul.appendChild(makeItem('1', 1));
        if (start > 2) ul.appendChild(makeItem('…', null, true));
    }

    for (let i = start; i <= end; i++) {
        ul.appendChild(makeItem(i, i, false, i === page));
    }

    if (end < pages) {
        if (end < pages - 1) ul.appendChild(makeItem('…', null, true));
        ul.appendChild(makeItem(pages, pages));
    }

    /* next */
    ul.appendChild(makeItem('&raquo;', page + 1, page === pages));
}

/* ── filter listeners with debounce on text input ── */
document.getElementById('search').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => loadPayments(true), 350);
});

['method', 'date_from', 'date_to'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => loadPayments(true));
});

/* ── clear button ── */
document.getElementById('clearFilters').addEventListener('click', () => {
    document.getElementById('search').value    = '';
    document.getElementById('method').value    = '';
    document.getElementById('date_from').value = '';
    document.getElementById('date_to').value   = '';
    loadPayments(true);
});

/* ── initial load ── */
loadPayments();
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>