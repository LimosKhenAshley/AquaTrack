<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

/* Get Customer ID */
$stmt = $pdo->prepare("
    SELECT id FROM customers WHERE user_id = ?
");
$stmt->execute([$user_id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer record not found.");
}

$customer_id = $customer['id'];

/* =============================
   FETCH CUSTOMER PAYMENTS
============================= */
$stmt = $pdo->prepare("
    SELECT 
        p.id AS payment_id,
        p.amount_paid,
        p.method,
        p.payment_date,
        b.id AS bill_id,
        b.status AS bill_status
    FROM payments p
    JOIN bills b ON p.bill_id = b.id
    WHERE b.customer_id = ?
    ORDER BY p.payment_date DESC
");

$stmt->execute([$customer_id]);
$payments = $stmt->fetchAll();

/* =============================
   COMPUTE SUMMARY STATS
============================= */
$total_paid    = array_sum(array_column(
    array_filter($payments, fn($p) => $p['bill_status'] === 'paid'),
    'amount_paid'
));
$paid_count    = count(array_filter($payments, fn($p) => $p['bill_status'] === 'paid'));
$pending_count = count(array_filter($payments, fn($p) => $p['bill_status'] === 'pending'));
$last_payment  = !empty($payments) ? $payments[0]['payment_date'] : null;
?>

<style>
/* ── Summary Cards ──────────────────────────────── */
.summary-card {
    border: none;
    border-radius: 14px;
    transition: transform .2s, box-shadow .2s;
}
.summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1) !important;
}
.summary-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
}

/* ── Table ──────────────────────────────────────── */
.payments-table thead th {
    font-size: .75rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    border-bottom-width: 2px;
}
.payments-table tbody tr {
    transition: background .15s;
}
.payments-table tbody tr:hover {
    background: rgba(13,110,253,.04);
}
.payments-table td {
    vertical-align: middle;
    font-size: .92rem;
}

/* ── Method badge with icon ─────────────────────── */
.method-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .8rem;
    padding: .32em .7em;
    border-radius: 20px;
}

/* ── Search box ─────────────────────────────────── */
#searchInput {
    max-width: 260px;
    border-radius: 20px;
    font-size: .88rem;
}

/* ── Empty state ────────────────────────────────── */
.empty-state {
    padding: 3.5rem 1rem;
    color: #adb5bd;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: .75rem;
    display: block;
    opacity: .45;
}
</style>

<div class="container-fluid px-4 mt-4">

    <!-- Page header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold">💳 My Payment History</h4>
            <small class="text-muted">All transactions linked to your account</small>
        </div>
    </div>

    <!-- ── Summary Cards ── -->
    <div class="row g-3 mb-4">

        <!-- Total Paid -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="summary-icon bg-success bg-opacity-15 text-success">💰</div>
                    <div>
                        <div class="text-muted small">Total Paid</div>
                        <div class="fw-bold fs-5">₱<?= number_format($total_paid, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paid Transactions -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="summary-icon bg-primary bg-opacity-15 text-primary">✅</div>
                    <div>
                        <div class="text-muted small">Paid Transactions</div>
                        <div class="fw-bold fs-5"><?= $paid_count ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="summary-icon bg-warning bg-opacity-15 text-warning">⏳</div>
                    <div>
                        <div class="text-muted small">Pending</div>
                        <div class="fw-bold fs-5"><?= $pending_count ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Payment -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card summary-card shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="summary-icon bg-info bg-opacity-15 text-info">📅</div>
                    <div>
                        <div class="text-muted small">Last Payment</div>
                        <div class="fw-bold" style="font-size:.95rem">
                            <?= $last_payment
                                ? date('M d, Y', strtotime($last_payment))
                                : '—' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    <!-- end summary cards -->

    <!-- ── Table Card ── -->
    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2 py-3 px-4 rounded-top-4">
            <span class="fw-semibold">Transactions</span>
            <input id="searchInput"
                   type="text"
                   class="form-control form-control-sm"
                   placeholder="🔍  Search by method or status…"
                   oninput="filterTable(this.value)">
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table payments-table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Method</th>
                            <th class="text-center pe-4" width="130">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsBody">

                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <!-- Date -->
                            <td class="ps-4">
                                <div class="fw-medium"><?= date('M d, Y', strtotime($p['payment_date'])) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($p['payment_date'])) ?></small>
                            </td>

                            <!-- Amount -->
                            <td class="fw-semibold">₱<?= number_format($p['amount_paid'], 2) ?></td>

                            <!-- Status -->
                            <td>
                                <?php if ($p['bill_status'] === 'paid'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        ✔ Paid
                                    </span>
                                <?php elseif ($p['bill_status'] === 'pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                        ⏳ Pending
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        ✖ Unpaid
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Method -->
                            <td>
                                <?php
                                $method = strtolower($p['method']);
                                $methodIcon = match($method) {
                                    'cash'         => '💵',
                                    'gcash'        => '📱',
                                    'credit card',
                                    'card'         => '💳',
                                    'bank transfer',
                                    'bank'         => '🏦',
                                    'check',
                                    'cheque'       => '📝',
                                    default        => '💲',
                                };
                                ?>
                                <span class="method-badge bg-info bg-opacity-10 text-info-emphasis">
                                    <?= $methodIcon ?> <?= ucfirst($p['method']) ?>
                                </span>
                            </td>

                            <!-- Receipt -->
                            <td class="text-center pe-4">
                                <?php if ($p['bill_status'] === 'paid'): ?>
                                    <a href="../shared/receipt_pdf.php?payment_id=<?= $p['payment_id'] ?>"
                                       class="btn btn-sm btn-outline-success rounded-pill px-3"
                                       target="_blank">
                                       ⬇ Download
                                    </a>
                                <?php elseif ($p['bill_status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-warning rounded-pill px-3" disabled>
                                        Pending
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" disabled>
                                        N/A
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($payments) === 0): ?>
                        <tr id="emptyRow">
                            <td colspan="5">
                                <div class="empty-state text-center">
                                    <i>💳</i>
                                    <div class="fw-medium fs-6">No payments yet</div>
                                    <small>Your payment history will appear here once a transaction is made.</small>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <!-- JS empty state (search) -->
                    <tr id="noResultsRow" style="display:none;">
                        <td colspan="5">
                            <div class="empty-state text-center">
                                <i>🔍</i>
                                <div class="fw-medium fs-6">No matching records</div>
                                <small>Try a different keyword.</small>
                            </div>
                        </td>
                    </tr>

                    </tbody>
                </table>
            </div>
        </div>

        <?php if (count($payments) > 0): ?>
        <div class="card-footer bg-white px-4 py-2 rounded-bottom-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <small class="text-muted">
                Showing <span id="rangeInfo">—</span> of <span id="totalCount"><?= count($payments) ?></span> record(s)
            </small>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>

</div>

<style>
/* ── Pagination ─────────────────────────────────── */
#pagination .page-link {
    border-radius: 8px !important;
    margin: 0 2px;
    font-size: .8rem;
    min-width: 32px;
    text-align: center;
}
</style>

<script>
const ROWS_PER_PAGE = 10;
let currentPage     = 1;
let filteredRows    = [];

function getAllDataRows() {
    return Array.from(document.querySelectorAll(
        '#paymentsBody tr:not(#noResultsRow):not(#emptyRow)'
    ));
}

function applyFilter(query) {
    const q = query.trim().toLowerCase();
    filteredRows = getAllDataRows().filter(row => {
        return !q || row.innerText.toLowerCase().includes(q);
    });
    currentPage = 1;
    render();
}

function render() {
    const noResults  = document.getElementById('noResultsRow');
    const rangeInfo  = document.getElementById('rangeInfo');
    const totalCount = document.getElementById('totalCount');
    const allRows    = getAllDataRows();

    // Hide all data rows first
    allRows.forEach(r => r.style.display = 'none');

    const total      = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * ROWS_PER_PAGE;
    const end   = Math.min(start + ROWS_PER_PAGE, total);

    filteredRows.forEach((row, idx) => {
        row.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    // Empty state
    if (noResults) noResults.style.display = (total === 0) ? '' : 'none';

    // Footer info
    if (rangeInfo)  rangeInfo.textContent  = total === 0 ? '0' : `${start + 1}–${end}`;
    if (totalCount) totalCount.textContent = total;

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const ul = document.getElementById('pagination');
    if (!ul) return;
    ul.innerHTML = '';

    const mkLi = (label, page, disabled, active) => {
        const li  = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
        const a   = document.createElement('a');
        a.className = 'page-link';
        a.href      = '#';
        a.innerHTML = label;
        if (!disabled && !active) {
            a.addEventListener('click', e => {
                e.preventDefault();
                currentPage = page;
                render();
            });
        }
        li.appendChild(a);
        return li;
    };

    // Prev
    ul.appendChild(mkLi('&laquo;', currentPage - 1, currentPage === 1, false));

    // Page numbers — show at most 5 around current
    const range = 2;
    for (let p = 1; p <= totalPages; p++) {
        if (
            p === 1 ||
            p === totalPages ||
            (p >= currentPage - range && p <= currentPage + range)
        ) {
            ul.appendChild(mkLi(p, p, false, p === currentPage));
        } else if (
            p === currentPage - range - 1 ||
            p === currentPage + range + 1
        ) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            ul.appendChild(li);
        }
    }

    // Next
    ul.appendChild(mkLi('&raquo;', currentPage + 1, currentPage === totalPages, false));
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    filteredRows = getAllDataRows();
    render();
});

// Search hook
function filterTable(query) {
    applyFilter(query);
}
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>