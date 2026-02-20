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
        b.created_at
    FROM bills b
    JOIN customers c ON b.customer_id = c.id
    JOIN users u ON c.user_id = u.id
    JOIN readings r ON b.reading_id = r.id
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
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($bill['status'] === 'unpaid'): ?>
                                    <button class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#markPaidModal"
                                            data-bill-id="<?= $bill['bill_id'] ?>"
                                            data-customer="<?= htmlspecialchars($bill['full_name']) ?>"
                                            data-amount="<?= number_format($bill['amount'], 2) ?>">
                                        ✔ Mark Paid
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>
                                        Paid
                                    </button>
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

<script>
const markPaidModalEl = document.getElementById('markPaidModal');
const markPaidModal = new bootstrap.Modal(markPaidModalEl);

markPaidModalEl.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;

    document.getElementById('markPaidBillId').value =
        button.getAttribute('data-bill-id');

    document.getElementById('markPaidCustomer').textContent =
        button.getAttribute('data-customer');

    document.getElementById('markPaidAmount').textContent =
        button.getAttribute('data-amount');

    document.getElementById('markPaidMessage').innerHTML = '';
});

document.getElementById('markPaidForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('/AquaTrack/modules/staff/ajax_mark_paid.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {

        if (data.status === 'success') {

            // UPDATE TABLE ROW
            const btn = document.querySelector(
                `button[data-bill-id="${formData.get('bill_id')}"]`
            );

            if (btn) {
                const row = btn.closest('tr');

                row.querySelector('td:nth-child(8)').innerHTML =
                    '<span class="badge bg-success">Paid</span>';

                row.querySelector('td:nth-child(9)').innerHTML =
                    '<button class="btn btn-secondary btn-sm" disabled>Paid</button>';
            }

            // FIRST close modal completely
            markPaidModal.hide();

            // Wait until modal fully hidden
            markPaidModalEl.addEventListener('hidden.bs.modal', function handler() {

                // Remove backdrop manually (extra safety)
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop')
                    .forEach(el => el.remove());

                // Open receipt AFTER modal fully closes
                if (data.payment_id) {
                    window.open(
                        `/AquaTrack/modules/shared/receipt_pdf.php?payment_id=${data.payment_id}`,
                        '_blank'
                    );
                }

                // Remove this event listener so it doesn't stack
                markPaidModalEl.removeEventListener('hidden.bs.modal', handler);

            });

        } else {
            document.getElementById('markPaidMessage').innerHTML =
                `<div class="alert alert-danger">${data.message}</div>`;
        }

    })
    .catch(error => {
        console.error("Error:", error);
        alert("Something went wrong.");
    });
});
</script>

<script>
const tableBody = document.getElementById('billingTableBody');
const paginationContainer = document.getElementById('paginationContainer');
const liveSearch = document.getElementById('liveSearch');

let currentPage = 1;
let currentSearch = '';

function fetchBills(page = 1, search = '') {
    fetch(`/AquaTrack/modules/staff/ajax_fetch_bills.php?page=${page}&search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(data => {

            tableBody.innerHTML = '';

            if (data.bills.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No bills found.
                        </td>
                    </tr>`;
                paginationContainer.innerHTML = '';
                return;
            }

            data.bills.forEach(bill => {
                tableBody.innerHTML += `
                    <tr>
                        <td>${bill.full_name}</td>
                        <td>${bill.meter_number}</td>
                        <td>${bill.reading_date}</td>
                        <td>${bill.reading_value}</td>
                        <td>₱${parseFloat(bill.amount).toFixed(2)}</td>
                        <td class="text-danger">₱${parseFloat(bill.penalty).toFixed(2)}</td>
                        <td class="fw-bold">₱${parseFloat(bill.total_amount).toFixed(2)}</td>
                        <td>
                            ${bill.status === 'paid'
                                ? '<span class="badge bg-success">Paid</span>'
                                : '<span class="badge bg-danger">Unpaid</span>'}
                        </td>
                        <td>
                            ${bill.status === 'unpaid'
                                ? `<button class="btn btn-success btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#markPaidModal"
                                        data-bill-id="${bill.bill_id}"
                                        data-customer="${bill.full_name}"
                                        data-amount="${parseFloat(bill.amount).toFixed(2)}">
                                        ✔ Mark Paid
                                   </button>`
                                : '<button class="btn btn-secondary btn-sm" disabled>Paid</button>'}
                        </td>
                    </tr>
                `;
            });

            renderPagination(data.totalPages, data.currentPage);
        });
}

function renderPagination(totalPages, currentPage) {
    let html = `<ul class="pagination justify-content-center">`;

    for (let i = 1; i <= totalPages; i++) {
        html += `
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <button class="page-link" onclick="changePage(${i})">${i}</button>
            </li>
        `;
    }

    html += `</ul>`;
    paginationContainer.innerHTML = html;
}

function changePage(page) {
    currentPage = page;
    fetchBills(currentPage, currentSearch);
}

/* Live search with debounce */
let debounceTimer;
liveSearch.addEventListener('keyup', function () {
    clearTimeout(debounceTimer);

    debounceTimer = setTimeout(() => {
        currentSearch = this.value;
        currentPage = 1;
        fetchBills(currentPage, currentSearch);
    }, 400); // wait 400ms before searching
});

/* Initial load */
fetchBills();
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
