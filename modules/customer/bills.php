<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['customer']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/bootstrap.php';

$user_id = $_SESSION['user']['id'];

/* =============================
   FETCH CUSTOMER
============================= */
$stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer_id = $stmt->fetchColumn();

if (!$customer_id) {
    die('Customer record not found.');
}

/* =============================
   FETCH BILLS
============================= */
$stmt = $pdo->prepare("
    SELECT 
        b.id,
        b.amount,
        b.penalty,
        (b.amount + b.penalty) AS total_amount,
        b.status,
        b.created_at,
        b.due_date,
        r.reading_value,
        r.reading_date
    FROM bills b
    JOIN readings r ON b.reading_id = r.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$customer_id]);
$bills = $stmt->fetchAll();

/* =============================
   SUMMARY STATS
============================= */
$total_due   = 0;
$total_paid  = 0;

foreach ($bills as $bill) {
    if ($bill['status'] === 'paid') {
        $total_paid += $bill['total_amount'];
    } else {
        $total_due += $bill['total_amount'];
    }
}
?>

<div class="col p-4">

    <h3 class="mb-4">🧾 My Bills</h3>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2 fs-4">💧</div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Bills</div>
                        <div class="fs-5 fw-bold"><?= count($bills) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 rounded p-2 fs-4">📋</div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Outstanding</div>
                        <div class="fs-5 fw-bold text-warning">₱<?= number_format($total_due, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 rounded p-2 fs-4">✅</div>
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Total Paid</div>
                        <div class="fs-5 fw-bold text-success">₱<?= number_format($total_paid, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bills Table -->
    <div class="card shadow-sm">
        <div class="card-body">

            <!-- Search & Filter -->
            <div class="d-flex gap-2 mb-3 flex-wrap">
                <input type="text" id="searchInput" class="form-control form-control-sm w-auto"
                       placeholder="🔍 Search..." oninput="filterTable()">
                <select id="statusFilter" class="form-select form-select-sm w-auto" onchange="filterTable()">
                    <option value="">All Statuses</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                    <option value="overdue">Overdue</option>
                    <option value="unpaid">Unpaid</option>
                </select>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="billsTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Reading</th>
                            <th>Base Amount</th>
                            <th>Penalty</th>
                            <th>Total</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th width="160">Action</th>
                        </tr>
                    </thead>
                    <tbody id="billsBody">

                    <?php if (count($bills) === 0): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                📭 No bills available yet
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($bills as $bill):
                        $isOverdue = strtotime($bill['due_date']) < time() && $bill['status'] !== 'paid';

                        if ($bill['status'] === 'paid') {
                            $statusKey = 'paid';
                        } elseif ($bill['status'] === 'pending') {
                            $statusKey = 'pending';
                        } elseif ($isOverdue) {
                            $statusKey = 'overdue';
                        } else {
                            $statusKey = 'unpaid';
                        }
                    ?>
                        <tr data-status="<?= $statusKey ?>">
                            <td><?= date('M d, Y', strtotime($bill['created_at'])) ?></td>
                            <td><?= number_format($bill['reading_value'], 2) ?> m³</td>
                            <td>₱<?= number_format($bill['amount'], 2) ?></td>
                            <td class="<?= $bill['penalty'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                ₱<?= number_format($bill['penalty'], 2) ?>
                            </td>
                            <td class="fw-bold">
                                ₱<?= number_format($bill['total_amount'], 2) ?>
                            </td>
                            <td><?= $bill['due_date'] ? date('M d, Y', strtotime($bill['due_date'])) : '—' ?></td>
                            <td>
                                <?php if ($statusKey === 'paid'): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php elseif ($statusKey === 'pending'): ?>
                                    <span class="badge bg-info text-dark">Pending</span>
                                <?php elseif ($statusKey === 'overdue'): ?>
                                    <span class="badge bg-danger">Overdue</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="bill_pdf.php?id=<?= $bill['id'] ?>" 
                                   class="btn btn-sm btn-secondary"
                                   target="_blank">
                                    📄 PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <!-- Pagination footer -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3">
                <div class="text-muted small" id="paginationInfo"></div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationControls"></ul>
                </nav>
            </div>

        </div>
    </div>

</div>

<script>
const ROWS_PER_PAGE = 10;
let currentPage    = 1;
let filteredRows   = [];

function getAllRows() {
    return Array.from(document.querySelectorAll('#billsBody tr[data-status]'));
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;

    filteredRows = getAllRows().filter(row => {
        const matchText   = !search || row.textContent.toLowerCase().includes(search);
        const matchStatus = !status  || row.dataset.status === status;
        return matchText && matchStatus;
    });

    currentPage = 1;
    renderPage();
}

function renderPage() {
    const allRows  = getAllRows();
    const total    = filteredRows.length;
    const pages    = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
    const start    = (currentPage - 1) * ROWS_PER_PAGE;
    const end      = start + ROWS_PER_PAGE;
    const pageRows = filteredRows.slice(start, end);

    // Show/hide rows
    allRows.forEach(row => row.style.display = 'none');
    pageRows.forEach(row => row.style.display = '');

    // Empty state row
    let emptyRow = document.getElementById('emptyStateRow');
    if (total === 0) {
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.id = 'emptyStateRow';
            emptyRow.innerHTML = '<td colspan="8" class="text-center text-muted py-4">📭 No matching bills found</td>';
            document.getElementById('billsBody').appendChild(emptyRow);
        }
        emptyRow.style.display = '';
    } else if (emptyRow) {
        emptyRow.style.display = 'none';
    }

    // Info text
    const from = total === 0 ? 0 : start + 1;
    const to   = Math.min(end, total);
    document.getElementById('paginationInfo').textContent =
        `Showing ${from}–${to} of ${total} bill${total !== 1 ? 's' : ''}`;

    // Pagination buttons
    const controls = document.getElementById('paginationControls');
    controls.innerHTML = '';

    const makeItem = (label, page, disabled, active) => {
        const li  = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
        const a   = document.createElement('a');
        a.className   = 'page-link';
        a.href        = '#';
        a.innerHTML   = label;
        a.addEventListener('click', e => {
            e.preventDefault();
            if (!disabled) { currentPage = page; renderPage(); }
        });
        li.appendChild(a);
        return li;
    };

    controls.appendChild(makeItem('&laquo;', currentPage - 1, currentPage === 1, false));

    // Page number window
    const delta = 2;
    for (let p = 1; p <= pages; p++) {
        if (p === 1 || p === pages || (p >= currentPage - delta && p <= currentPage + delta)) {
            controls.appendChild(makeItem(p, p, false, p === currentPage));
        } else if (p === currentPage - delta - 1 || p === currentPage + delta + 1) {
            const li = document.createElement('li');
            li.className = 'page-item disabled';
            li.innerHTML = '<span class="page-link">…</span>';
            controls.appendChild(li);
        }
    }

    controls.appendChild(makeItem('&raquo;', currentPage + 1, currentPage === pages, false));
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    filteredRows = getAllRows();
    renderPage();
});

function filterTable() { applyFilters(); }
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>