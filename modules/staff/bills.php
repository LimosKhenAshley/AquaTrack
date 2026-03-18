<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/bootstrap.php';

/* =============================
   PAGINATION + SEARCH
============================= */

// Search
$search = trim($_GET['search'] ?? '');

// Pagination setup
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10; // records per page
$offset = ($page - 1) * $limit;

// Base WHERE
$where = "";
$params = [];

if (!empty($search)) {
    $where = "WHERE 
        u.full_name LIKE :search 
        OR c.meter_number LIKE :search
        OR b.status LIKE :search";
    $params[':search'] = "%$search%";
}

/* =============================
   COUNT TOTAL RECORDS
============================= */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN readings r ON b.reading_id = r.id
    $where
");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

/* =============================
   FETCH PAGINATED DATA
============================= */
$stmt = $pdo->prepare("
    SELECT
        b.id AS bill_id,
        u.full_name,
        c.meter_number,
        r.reading_date,
        r.reading_value,
        b.amount,
        b.penalty,
        (b.amount + b.penalty) AS total_amount,
        b.status,
        p.method AS payment_method,
        b.created_at
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN readings r ON b.reading_id = r.id
    LEFT JOIN payments p ON p.bill_id = b.id
    $where
    ORDER BY b.created_at DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$bills = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <h3 class="mb-3">💳 Billing Management</h3>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <input type="text" 
                        id="liveSearch"
                        class="form-control"
                        placeholder="Search customer, meter #, status...">
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="unpaid">Unpaid</option>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>Customer</th>
                        <th>Meter #</th>
                        <th>Reading Date</th>
                        <th>Reading</th>
                        <th>Base Amount (₱)</th>
                        <th>Penalty</th>
                        <th>Total Amount (₱)</th>
                        <th>Status</th>
                        <th width="160">Action</th>
                    </tr>
                    </thead>
                    <tbody id="billingTableBody">
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?= htmlspecialchars($bill['full_name']) ?></td>
                            <td><?= htmlspecialchars($bill['meter_number']) ?></td>
                            <td><?= htmlspecialchars($bill['reading_date']) ?></td>
                            <td><?= htmlspecialchars($bill['reading_value']) ?></td>
                            <td>₱<?= number_format($bill['amount'], 2) ?></td>
                            <td class="text-danger">
                                ₱<?= number_format($bill['penalty'], 2) ?>
                            </td>
                            <td class="fw-bold">
                                ₱<?= number_format($bill['total_amount'], 2) ?>
                            </td>
                            <td>
                                <?php if ($bill['status'] === 'paid'): ?>
                                    <span class="badge bg-success">Paid</span>

                                <?php elseif ($bill['status'] === 'pending'): ?>
                                    <span class="badge bg-warning text-dark">Pending</span>

                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bill['status'] === 'pending'): ?>

                                    <button class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#verifyPaymentModal"
                                            data-bill-id="<?= $bill['bill_id'] ?>"
                                            data-customer="<?= htmlspecialchars($bill['full_name']) ?>"
                                            data-amount="<?= number_format($bill['total_amount'],2) ?>">
                                            🔎 Verify Payment
                                    </button>
                                <?php elseif ($bill['status'] === 'unpaid'): ?>
                                    <button class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#markPaidModal"
                                            data-bill-id="<?= $bill['bill_id'] ?>"
                                            data-customer="<?= htmlspecialchars($bill['full_name']) ?>"
                                            data-amount="<?= number_format($bill['total_amount'],2) ?>">
                                            ✔ Mark Paid
                                    </button>
                                <?php elseif ($bill['status'] === 'paid'): ?>

                                <button class="btn btn-secondary btn-sm" disabled>
                                Paid
                                </button>

                                <?php else: ?>

                                <span class="text-muted">Waiting Payment</span>

                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (count($bills) === 0): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                No bills found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div id="paginationContainer" class="mt-3"></div>
        </div>
    </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="markPaidForm">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Confirm Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="markPaidBillId">

                    <p>
                        Mark bill for <strong id="markPaidCustomer"></strong>  
                        Amount: <strong>₱<span id="markPaidAmount"></span></strong>  
                        as <span class="badge bg-success">PAID</span>?
                    </p>

                    <div class="mb-3">
                        <label>Payment Method</label>
                        <select name="method" class="form-select">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                    <div id="markPaidMessage"></div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">
                        ✔ Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="verifyPaymentModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form id="verifyPaymentForm">

                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="bill_id" id="verifyBillId">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Verify Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <p>
                        Confirm payment for
                        <strong id="verifyCustomer"></strong>
                        Amount:
                        <strong>₱<span id="verifyAmount"></span></strong>
                    </p>

                    <div class="mb-3">
                        <label>Verification Result</label>
                        <select name="result" class="form-select">
                            <option value="approve">Approve Payment</option>
                            <option value="reject">Reject Payment</option>
                        </select>
                    </div>

                    <div id="verifyMessage"></div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-100">
                        Confirm Verification
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ── Mark Paid Modal ──────────────────────────────────────────────
const markPaidModalEl = document.getElementById('markPaidModal');
const markPaidModal   = new bootstrap.Modal(markPaidModalEl);

markPaidModalEl.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('markPaidBillId').value        = button.getAttribute('data-bill-id');
    document.getElementById('markPaidCustomer').textContent = button.getAttribute('data-customer');
    document.getElementById('markPaidAmount').textContent   = button.getAttribute('data-amount');
    document.getElementById('markPaidMessage').innerHTML    = '';
});

document.getElementById('markPaidForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    const confirmBtn = this.querySelector('[type="submit"]');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Processing...`;

    fetch('/AquaTrack/modules/staff/ajax_mark_paid.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '✔ Confirm Payment';

        if (data.status === 'success') {
            const btn = document.querySelector(`button[data-bill-id="${formData.get('bill_id')}"]`);
            if (btn) {
                const row = btn.closest('tr');
                row.querySelector('td:nth-child(8)').innerHTML = '<span class="badge bg-success">Paid</span>';
                row.querySelector('td:nth-child(9)').innerHTML = '<button class="btn btn-secondary btn-sm" disabled>Paid</button>';
            }

            markPaidModal.hide();

            markPaidModalEl.addEventListener('hidden.bs.modal', function handler() {
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

                if (data.payment_id) {
                    window.open(`/AquaTrack/modules/shared/receipt_pdf.php?payment_id=${data.payment_id}`, '_blank');
                }

                markPaidModalEl.removeEventListener('hidden.bs.modal', handler);
            });
        } else {
            document.getElementById('markPaidMessage').innerHTML =
                `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '✔ Confirm Payment';
        console.error("Error:", error);
        alert("Something went wrong.");
    });
});

// ── Verify Payment Modal ─────────────────────────────────────────
const verifyModalEl = document.getElementById('verifyPaymentModal');

verifyModalEl.addEventListener('show.bs.modal', event => {
    const btn = event.relatedTarget;
    document.getElementById('verifyBillId').value          = btn.dataset.billId;
    document.getElementById('verifyCustomer').textContent  = btn.dataset.customer;
    document.getElementById('verifyAmount').textContent    = btn.dataset.amount;
    document.getElementById('verifyMessage').innerHTML     = '';
});

document.getElementById('verifyPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    // ← Capture this SYNCHRONOUSLY before any async call
    const selectedResult = document.getElementById('verifyPaymentForm')
        .querySelector('select[name="result"]').value;

    const confirmBtn = this.querySelector('[type="submit"]');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status"></span>Verifying...`;

    fetch('/AquaTrack/modules/staff/ajax_verify_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'Confirm Verification';

        if (data.status === 'success') {

            const isApproved = selectedResult === 'approve'; // ← use captured value

            Swal.fire({
                icon: isApproved ? 'success' : 'warning',
                title: isApproved ? 'Payment Approved' : 'Payment Rejected',
                text: isApproved
                    ? 'The payment has been approved and the bill is now marked as PAID.'
                    : 'The payment has been rejected and the bill is back to unpaid.',
                confirmButtonColor: isApproved ? '#198754' : '#dc3545'
            }).then(() => location.reload());

        } else {
            document.getElementById('verifyMessage').innerHTML =
                `<div class="alert alert-danger">${data.message}</div>`;
        }
    })
    .catch(error => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'Confirm Verification';
        console.error("Error:", error);
        alert("Something went wrong.");
    });
});

// ── Live Search + Filter + Pagination ───────────────────────────
const tableBody          = document.getElementById('billingTableBody');
const paginationContainer = document.getElementById('paginationContainer');
const liveSearch         = document.getElementById('liveSearch');
const statusFilter       = document.getElementById('statusFilter');

let currentPage   = 1;
let currentSearch = '';
let currentStatus = '';

function fetchBills(page = 1, search = '', status = '') {
    fetch(`/AquaTrack/modules/staff/ajax_fetch_bills.php?page=${page}&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`)
        .then(res => res.json())
        .then(data => {
            tableBody.innerHTML = '';

            if (data.bills.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No bills found.</td>
                    </tr>`;
                paginationContainer.innerHTML = '';
                return;
            }

            data.bills.forEach(bill => {
                let statusBadge  = '';
                let actionButton = '';

                if (bill.status === 'paid') {
                    statusBadge  = '<span class="badge bg-success">Paid</span>';
                    actionButton = '<button class="btn btn-secondary btn-sm" disabled>Paid</button>';
                } else if (bill.status === 'pending') {
                    statusBadge  = '<span class="badge bg-warning text-dark">Pending</span>';
                    actionButton = `
                        <button class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#verifyPaymentModal"
                            data-bill-id="${bill.bill_id}"
                            data-customer="${bill.full_name}"
                            data-amount="${parseFloat(bill.total_amount).toFixed(2)}">
                            🔎 Verify Payment
                        </button>`;
                } else if (bill.status === 'unpaid') {
                    statusBadge  = '<span class="badge bg-danger">Unpaid</span>';
                    actionButton = `
                        <button class="btn btn-success btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#markPaidModal"
                            data-bill-id="${bill.bill_id}"
                            data-customer="${bill.full_name}"
                            data-amount="${parseFloat(bill.total_amount).toFixed(2)}">
                            ✔ Mark Paid
                        </button>`;
                }

                tableBody.innerHTML += `
                    <tr>
                        <td>${bill.full_name}</td>
                        <td>${bill.meter_number}</td>
                        <td>${bill.reading_date}</td>
                        <td>${bill.reading_value}</td>
                        <td>₱${parseFloat(bill.amount).toFixed(2)}</td>
                        <td class="text-danger">₱${parseFloat(bill.penalty).toFixed(2)}</td>
                        <td class="fw-bold">₱${parseFloat(bill.total_amount).toFixed(2)}</td>
                        <td>${statusBadge}</td>
                        <td>${actionButton}</td>
                    </tr>`;
            });

            renderPagination(data.totalPages, data.currentPage);
        });
}

function renderPagination(totalPages, currentPage) {
    let html = `<ul class="pagination justify-content-center">`;
    for (let i = 1; i <= totalPages; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" onclick="changePage(${i})">${i}</button>
                 </li>`;
    }
    html += `</ul>`;
    paginationContainer.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    fetchBills(currentPage, currentSearch, currentStatus);
}

// Debounced search
let debounceTimer;
liveSearch.addEventListener('keyup', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        currentSearch = this.value;
        currentPage   = 1;
        fetchBills(currentPage, currentSearch, currentStatus);
    }, 400);
});

// Instant filter on status change
statusFilter.addEventListener('change', function () {
    currentStatus = this.value;
    currentPage   = 1;
    fetchBills(currentPage, currentSearch, currentStatus);
});

fetchBills();
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
