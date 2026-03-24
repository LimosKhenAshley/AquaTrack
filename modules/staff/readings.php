<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

// Rate Configuration
$stmt = $pdo->query("
    SELECT rate_per_unit FROM rates
    WHERE effective_from <= CURDATE()
    ORDER BY effective_from DESC
    LIMIT 1
");
$currentRate = $stmt->fetchColumn();
if (!$currentRate) die("No active rate configured.");

$message = '';
$error   = '';

/* =============================
   FETCH ALL CUSTOMERS
   Sorted by meter_number ASC.
   Filtering is fully client-side.
============================= */
$stmt = $pdo->prepare("
    SELECT
        c.id              AS customer_id,
        c.service_status,
        u.full_name,
        c.meter_number,
        MAX(r.reading_date)  AS last_reading_date,
        MAX(r.reading_value) AS last_reading,
        MAX(r.id)            AS last_reading_id
    FROM customers c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN readings r ON c.id = r.customer_id
    GROUP BY c.id, u.full_name, c.meter_number, c.service_status
    ORDER BY c.meter_number ASC
");
$stmt->execute();
$customers = $stmt->fetchAll();

/* =============================
   HANDLE ADD READING
============================= */
if (isset($_POST['add_reading'])) {
    $customer_id = $_POST['customer_id'];
    $newReading  = (float)$_POST['reading_value'];
    $readingDate = $_POST['reading_date'];

    $s = $pdo->prepare("SELECT reading_value FROM readings WHERE customer_id = ? ORDER BY reading_date DESC LIMIT 1");
    $s->execute([$customer_id]);
    $lastReading   = $s->fetch();
    $previousValue = $lastReading['reading_value'] ?? 0;

    if ($newReading <= $previousValue) {
        $error = "New reading must be greater than previous ({$previousValue}).";
    } else {
        $consumption = $newReading - $previousValue;
        $amount      = $consumption * $currentRate;
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("INSERT INTO readings (customer_id, reading_date, reading_value) VALUES (?, ?, ?)");
            $s->execute([$customer_id, $readingDate, $newReading]);
            $reading_id = $pdo->lastInsertId();
            $s = $pdo->prepare("INSERT INTO bills (customer_id, reading_id, amount, status) VALUES (?, ?, ?, 'unpaid')");
            $s->execute([$customer_id, $reading_id, $amount]);
            $pdo->commit();
            $message = "Meter reading and bill generated successfully.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Transaction failed: " . $e->getMessage();
        }
    }
}

/* =============================
   HANDLE EDIT READING
============================= */
if (isset($_POST['edit_reading'])) {
    $reading_id  = $_POST['reading_id'];
    $newValue    = (float)$_POST['reading_value'];
    $readingDate = $_POST['reading_date'];

    $s = $pdo->prepare("SELECT customer_id, reading_value FROM readings WHERE id = ?");
    $s->execute([$reading_id]);
    $reading = $s->fetch();

    if (!$reading) {
        $error = "Reading not found.";
    } elseif ($newValue <= 0) {
        $error = "Reading must be positive.";
    } else {
        $amount = $newValue * $currentRate;
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("UPDATE readings SET reading_value = ?, reading_date = ? WHERE id = ?");
            $s->execute([$newValue, $readingDate, $reading_id]);
            $s = $pdo->prepare("UPDATE bills SET amount = ? WHERE reading_id = ?");
            $s->execute([$amount, $reading_id]);
            $pdo->commit();
            $message = "Reading updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update reading: " . $e->getMessage();
        }
    }
}
?>

<style>
/* ═══════════════════════════════════════
   PAGE & CARD
═══════════════════════════════════════ */
.readings-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.08);
    overflow: hidden;
}
.readings-card .card-header {
    background: #fff;
    border-bottom: 1px solid #e9ecef;
    padding: 1rem 1.25rem .75rem;
}

/* ═══════════════════════════════════════
   TABLE STYLES
═══════════════════════════════════════ */
#readingsTable {
    margin-bottom: 0;
    font-size: 0.875rem;
}

/* Header row */
#readingsTable thead tr.col-headers th {
    background: #212529;
    color: #fff;
    font-weight: 600;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 11px 12px;
    border-color: #343a40;
    white-space: nowrap;
    vertical-align: middle;
}

/* Sortable headers */
#readingsTable thead tr.col-headers th.sortable {
    cursor: pointer;
    user-select: none;
}
#readingsTable thead tr.col-headers th.sortable:hover {
    background: #343a40;
}
.sort-icon {
    display: inline-block;
    width: 14px;
    font-size: 0.65rem;
    opacity: .45;
    margin-left: 2px;
}
th.sortable.asc  .sort-icon { opacity: 1; }
th.sortable.desc .sort-icon { opacity: 1; }
th.sortable.asc  .sort-icon::after { content: '▲'; }
th.sortable.desc .sort-icon::after { content: '▼'; }
th.sortable:not(.asc):not(.desc) .sort-icon::after { content: '⇅'; }

/* Inline filter controls in card header */
.header-filter {
    font-size: 0.78rem;
    height: 32px;
    padding: 4px 8px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    background: #fff;
    color: #495057;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
}
.header-filter:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 3px rgba(13,110,253,.12);
}
.filter-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #6c757d;
    white-space: nowrap;
}

/* Body rows */
#readingsTable tbody tr {
    transition: background .1s;
}
#readingsTable tbody tr:hover {
    background: #f8f9ff !important;
}
#readingsTable tbody td {
    padding: 10px 12px;
    vertical-align: middle;
    border-color: #e9ecef;
}

/* Meter number pill */
.meter-pill {
    display: inline-block;
    background: #e8f0fe;
    color: #1a56db;
    font-weight: 600;
    font-size: 0.8rem;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: .02em;
}

/* Status badges */
.status-badge {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
}

/* Result info bar */
.result-bar {
    font-size: 0.82rem;
    color: #6c757d;
}

/* No-results */
#noResults {
    padding: 3rem 0;
    color: #adb5bd;
}
#noResults .no-res-icon { font-size: 2.5rem; display: block; margin-bottom: .5rem; }
</style>

<div class="container-fluid px-4 mt-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="mb-0 fw-bold"> 📏Meter Readings</h3>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            ✅ <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            ⚠️ <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card readings-card">

        <!-- Card header: search + filters + result count all in one bar -->
        <div class="card-header">
            <div class="d-flex align-items-center gap-3 flex-wrap">

                <!-- Global search -->
                <div class="input-group input-group-sm" style="max-width:240px; flex-shrink:0">
                    <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                    <input type="text" id="globalSearch"
                        class="form-control border-start-0 ps-0"
                        placeholder="Search name or meter #…"
                        style="box-shadow:none">
                </div>

                <!-- Divider -->
                <div class="vr opacity-25 d-none d-md-block"></div>

                <!-- Status filter -->
                <div class="d-flex align-items-center gap-1">
                    <span class="filter-label">Status</span>
                    <select id="filterStatus" class="header-filter" style="width:130px">
                        <option value="">All</option>
                        <option value="active">Active</option>
                        <option value="disconnected">Disconnected</option>
                    </select>
                </div>

                <!-- Reading filter -->
                <div class="d-flex align-items-center gap-1">
                    <span class="filter-label">Reading</span>
                    <select id="filterReading" class="header-filter" style="width:140px">
                        <option value="">All</option>
                        <option value="has">Has reading</option>
                        <option value="none">No reading yet</option>
                    </select>
                </div>

                <!-- Date range -->
                <div class="d-flex align-items-center gap-1">
                    <span class="filter-label">Date</span>
                    <input type="date" id="dateFrom" class="header-filter" title="From date" style="width:140px">
                    <span class="text-muted" style="font-size:0.75rem">–</span>
                    <input type="date" id="dateTo"   class="header-filter" title="To date"   style="width:140px">
                </div>

                <!-- Spacer + result info + clear -->
                <div class="ms-auto d-flex align-items-center gap-2 result-bar flex-shrink-0">
                    <span id="resultInfo"></span>
                    <button id="clearAllFilters"
                        class="btn btn-sm btn-outline-secondary py-0 px-2 d-none"
                        style="font-size:0.78rem">
                        ✕ Clear
                    </button>
                </div>

            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" id="readingsTable">
                    <thead>
                        <!-- ── Column headers ── -->
                        <tr class="col-headers">
                            <th class="sortable asc" data-col="0" data-sort="alpha">
                                Meter # <span class="sort-icon"></span>
                            </th>
                            <th class="sortable" data-col="1" data-sort="alpha">
                                Customer <span class="sort-icon"></span>
                            </th>
                            <th class="sortable" data-col="2" data-sort="num">
                                Last Reading <span class="sort-icon"></span>
                            </th>
                            <th class="sortable" data-col="3" data-sort="date">
                                Last Date <span class="sort-icon"></span>
                            </th>
                            <th>Actions</th>
                            <th>Status</th>
                            <th>Connection</th>
                        </tr>
                    </thead>
                    <tbody id="readingsTableBody">
                        <?php foreach ($customers as $c): ?>
                        <tr
                            data-meter="<?= htmlspecialchars(strtolower($c['meter_number'])) ?>"
                            data-name="<?= htmlspecialchars(strtolower($c['full_name'])) ?>"
                            data-reading="<?= $c['last_reading'] !== null ? 'has' : 'none' ?>"
                            data-date="<?= $c['last_reading_date'] ?? '' ?>"
                            data-status="<?= $c['service_status'] ?>"
                        >
                            <td>
                                <span class="meter-pill">
                                    <?= htmlspecialchars($c['meter_number']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($c['full_name']) ?></td>
                            <td>
                                <?php if ($c['last_reading'] !== null): ?>
                                    <span class="fw-semibold"><?= htmlspecialchars($c['last_reading']) ?></span>
                                    <small class="text-muted ms-1">m³</small>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= $c['last_reading_date'] ?? '—' ?></td>
                            <td>
                                <button class="btn btn-success btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#addReadingModal"
                                    data-customer-id="<?= $c['customer_id'] ?>"
                                    data-customer-name="<?= htmlspecialchars($c['full_name']) ?>"
                                    data-last-reading="<?= $c['last_reading'] ?? 0 ?>">
                                    ➕ Add
                                </button>
                                <?php if ($c['last_reading'] !== null): ?>
                                <button class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#editReadingModal"
                                    data-reading-id="<?= $c['last_reading_id'] ?>"
                                    data-customer-name="<?= htmlspecialchars($c['full_name']) ?>"
                                    data-reading-date="<?= $c['last_reading_date'] ?>"
                                    data-reading-value="<?= $c['last_reading'] ?>">
                                    ✏️ Edit
                                </button>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge badge <?= $c['service_status'] === 'active' ? 'bg-success' : 'bg-danger' ?>">
                                    <?= ucfirst($c['service_status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($c['service_status'] === 'active'): ?>
                                    <button class="btn btn-outline-danger btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#disconnectModal"
                                        data-customer-id="<?= $c['customer_id'] ?>"
                                        data-customer-name="<?= htmlspecialchars($c['full_name']) ?>">
                                        Disconnect
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline-success btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#reconnectModal"
                                        data-customer-id="<?= $c['customer_id'] ?>"
                                        data-customer-name="<?= htmlspecialchars($c['full_name']) ?>">
                                        Reconnect
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (count($customers) === 0): ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">No customers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- No-results overlay (shown by JS) -->
            <div id="noResults" class="text-center d-none">
                <span class="no-res-icon">🔍</span>
                No customers match the current filters.
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-center py-3 border-top" id="paginationWrapper">
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ MODALS ══════════════════════ -->

<!-- Add Reading -->
<div class="modal fade" id="addReadingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Meter Reading</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="addCustomerId">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input type="text" class="form-control" id="addCustomerName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reading Date</label>
                        <input type="date" name="reading_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Previous Reading</label>
                        <input type="text" class="form-control" id="addPreviousReading" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reading Value</label>
                        <input type="number" step="0.01" name="reading_value" class="form-control" required>
                    </div>
                    <div class="alert alert-info py-2 mb-2">
                        <strong>Current Rate:</strong> ₱<?= number_format($currentRate, 2) ?> / m³
                    </div>
                    <small class="text-muted">Estimated Bill: <strong>₱<span id="estimatedBill">0.00</span></strong></small>
                    <div id="addModalMessage" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button name="add_reading" class="btn btn-success w-100">Save Reading & Generate Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Reading -->
<div class="modal fade" id="editReadingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">Edit Meter Reading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="reading_id" id="editReadingId">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input type="text" class="form-control" id="editCustomerName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reading Date</label>
                        <input type="date" name="reading_date" class="form-control" id="editReadingDate" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reading Value</label>
                        <input type="number" step="0.01" name="reading_value" class="form-control" id="editReadingValue" required>
                    </div>
                    <div class="alert alert-info py-2 mb-2">
                        <strong>Current Rate:</strong> ₱<?= number_format($currentRate, 2) ?> / m³
                    </div>
                    <small class="text-muted">Updated Bill: <strong>₱<span id="editEstimatedBill">0.00</span></strong></small>
                    <div id="editModalMessage" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button name="edit_reading" type="submit" class="btn btn-warning w-100">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Disconnect -->
<div class="modal fade" id="disconnectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="disconnectForm">
                <div class="modal-header"><h5 class="modal-title">Schedule Disconnection</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="discCustomerId">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input type="text" id="discCustomerName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control" required>
                    </div>
                    <div id="discMsg"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger">Confirm Disconnect</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reconnect -->
<div class="modal fade" id="reconnectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reconnectForm">
                <div class="modal-header"><h5 class="modal-title">Schedule Reconnection</h5></div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="reconCustomerId">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <input type="text" id="reconCustomerName" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Scheduled Date</label>
                        <input type="date" name="scheduled_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reconnection Fee</label>
                        <input type="number" step="0.01" name="reconnection_fee" class="form-control" value="150" required>
                    </div>
                    <div id="reconMsg"></div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Confirm Reconnect</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════ SCRIPTS ══════════════════════ -->
<script>
const CURRENT_RATE = <?= $currentRate ?>;

/* ═══════════════════════════════════════════════════
   CLIENT-SIDE FILTERING, SORTING & PAGINATION
═══════════════════════════════════════════════════ */
const tbody           = document.getElementById('readingsTableBody');
const noResults       = document.getElementById('noResults');
const resultInfo      = document.getElementById('resultInfo');
const clearBtn        = document.getElementById('clearAllFilters');
const globalInput     = document.getElementById('globalSearch');
const filterStatus    = document.getElementById('filterStatus');
const filterReading   = document.getElementById('filterReading');
const dateFrom        = document.getElementById('dateFrom');
const dateTo          = document.getElementById('dateTo');
const paginationEl    = document.getElementById('pagination');
const paginationWrap  = document.getElementById('paginationWrapper');

const PER_PAGE = 10;
let currentPage = 1;
let sortCol = 0, sortDir = 'asc';

// All data rows (never changes)
const allRows = () => Array.from(tbody.querySelectorAll('tr[data-meter]'));

/* ── Get rows that pass current filters (regardless of page) ── */
function getFilteredRows() {
    const global   = globalInput.value.trim().toLowerCase();
    const readingF = filterReading.value;
    const statusF  = filterStatus.value;
    const fromDate = dateFrom.value;
    const toDate   = dateTo.value;

    return allRows().filter(row => {
        const meter   = row.dataset.meter;
        const name    = row.dataset.name;
        const reading = row.dataset.reading;
        const date    = row.dataset.date;
        const status  = row.dataset.status;

        if (global   && !meter.includes(global) && !name.includes(global)) return false;
        if (readingF && reading !== readingF)                               return false;
        if (statusF  && status  !== statusF)                                return false;
        if (fromDate && (date === '' || date < fromDate))                   return false;
        if (toDate   && (date === '' || date > toDate))                     return false;
        return true;
    });
}

/* ── Apply filters + paginate ── */
function applyFilters(resetPage = true) {
    if (resetPage) currentPage = 1;

    const filtered   = getFilteredRows();
    const total      = allRows().length;
    const totalPages = Math.ceil(filtered.length / PER_PAGE) || 1;

    // Clamp currentPage
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * PER_PAGE;
    const end   = start + PER_PAGE;

    // Show/hide all rows based on filter + page window
    allRows().forEach(row => row.style.display = 'none');
    filtered.slice(start, end).forEach(row => row.style.display = '');

    // Result info
    const showing = filtered.slice(start, end).length;
    resultInfo.textContent = filtered.length < total
        ? `Showing ${showing} of ${filtered.length} filtered (${total} total)`
        : `Showing ${showing} of ${total} customer(s)`;

    // No-results
    noResults.classList.toggle('d-none', filtered.length > 0);

    // Clear button
    const anyActive = globalInput.value || filterReading.value || filterStatus.value || dateFrom.value || dateTo.value;
    clearBtn.classList.toggle('d-none', !anyActive);

    // Render pagination
    renderPagination(totalPages);
}

/* ── Render Bootstrap pagination ── */
function renderPagination(totalPages) {
    paginationEl.innerHTML = '';
    paginationWrap.style.display = totalPages <= 1 ? 'none' : '';

    const mkLi = (label, page, disabled, active) => {
        const li  = document.createElement('li');
        li.className = `page-item${disabled ? ' disabled' : ''}${active ? ' active' : ''}`;
        const a   = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.innerHTML = label;
        if (!disabled && !active) {
            a.addEventListener('click', e => {
                e.preventDefault();
                currentPage = page;
                applyFilters(false);
                // Scroll table into view smoothly
                tbody.closest('.table-responsive').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        }
        li.appendChild(a);
        return li;
    };

    paginationEl.appendChild(mkLi('&laquo;', currentPage - 1, currentPage === 1, false));

    // Show max 7 page numbers with ellipsis
    const range = [];
    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= currentPage - 2 && p <= currentPage + 2)) {
            range.push(p);
        }
    }
    let prev = null;
    range.forEach(p => {
        if (prev && p - prev > 1) {
            paginationEl.appendChild(mkLi('…', null, true, false));
        }
        paginationEl.appendChild(mkLi(p, p, false, p === currentPage));
        prev = p;
    });

    paginationEl.appendChild(mkLi('&raquo;', currentPage + 1, currentPage === totalPages, false));
}

/* ── Sort ── */
function sortTable(colIndex, dir, sortType) {
    const rows = allRows();
    rows.sort((a, b) => {
        let aVal, bVal;
        if (colIndex === 0) { aVal = a.dataset.meter; bVal = b.dataset.meter; }
        else if (colIndex === 1) { aVal = a.dataset.name; bVal = b.dataset.name; }
        else { aVal = a.cells[colIndex]?.textContent.trim() ?? ''; bVal = b.cells[colIndex]?.textContent.trim() ?? ''; }

        if (sortType === 'num') {
            const aN = parseFloat(aVal.replace(/[^\d.]/g, '')) || 0;
            const bN = parseFloat(bVal.replace(/[^\d.]/g, '')) || 0;
            return dir === 'asc' ? aN - bN : bN - aN;
        }
        const cmp = aVal.localeCompare(bVal, undefined, { numeric: true, sensitivity: 'base' });
        return dir === 'asc' ? cmp : -cmp;
    });
    rows.forEach(r => tbody.appendChild(r));
}

/* ── Sort header click ── */
document.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
        const col      = parseInt(th.dataset.col);
        const sortType = th.dataset.sort ?? 'alpha';
        if (sortCol === col) { sortDir = sortDir === 'asc' ? 'desc' : 'asc'; }
        else { sortCol = col; sortDir = 'asc'; }
        document.querySelectorAll('th.sortable').forEach(h => h.classList.remove('asc', 'desc'));
        th.classList.add(sortDir);
        sortTable(sortCol, sortDir, sortType);
        applyFilters();
    });
});

// Initial sort: meter # asc
sortTable(0, 'asc', 'alpha');

/* ── Filter listeners ── */
globalInput.addEventListener('input',   () => applyFilters());
filterStatus.addEventListener('change', () => applyFilters());
filterReading.addEventListener('change',() => applyFilters());
dateFrom.addEventListener('change',     () => applyFilters());
dateTo.addEventListener('change',       () => applyFilters());

clearBtn.addEventListener('click', () => {
    globalInput.value   = '';
    filterStatus.value  = '';
    filterReading.value = '';
    dateFrom.value      = '';
    dateTo.value        = '';
    applyFilters();
});

// Boot
applyFilters();

/* ═══════════════════════════════════════════════════
   MODAL POPULATION
═══════════════════════════════════════════════════ */
document.getElementById('addReadingModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('addCustomerId').value   = btn.dataset.customerId;
    document.getElementById('addCustomerName').value = btn.dataset.customerName;
    document.getElementById('addPreviousReading').value = btn.dataset.lastReading || '0';
    document.querySelector('#addReadingModal input[name="reading_value"]').value = '';
    document.getElementById('estimatedBill').textContent = '0.00';
    document.getElementById('addModalMessage').innerHTML = '';
});

document.getElementById('editReadingModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('editReadingId').value    = btn.dataset.readingId;
    document.getElementById('editCustomerName').value = btn.dataset.customerName;
    document.getElementById('editReadingDate').value  = btn.dataset.readingDate;
    document.getElementById('editReadingValue').value = btn.dataset.readingValue;
    document.getElementById('editModalMessage').innerHTML = '';
});

document.getElementById('disconnectModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('discCustomerId').value   = btn.dataset.customerId;
    document.getElementById('discCustomerName').value = btn.dataset.customerName;
});

document.getElementById('reconnectModal').addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    document.getElementById('reconCustomerId').value   = btn.dataset.customerId;
    document.getElementById('reconCustomerName').value = btn.dataset.customerName;
});

/* ═══════════════════════════════════════════════════
   ADD READING AJAX
═══════════════════════════════════════════════════ */
document.querySelector('#addReadingModal form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const saveBtn  = this.querySelector('button[name="add_reading"]');
    saveBtn.disabled  = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Saving…`;

    fetch('/AquaTrack/modules/staff/ajax_add_reading.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            const msgDiv = document.getElementById('addModalMessage');
            if (data.status === 'success') {
                msgDiv.innerHTML = `<div class="alert alert-success py-2">${data.message}</div>`;

                const customerId = formData.get('customer_id');
                const row = document.querySelector(`button[data-customer-id="${customerId}"]`).closest('tr');

                // Update cells
                row.cells[2].innerHTML = `<span class="fw-semibold">${data.reading_value}</span> <small class="text-muted ms-1">m³</small>`;
                row.cells[3].textContent = data.reading_date;

                // Update data attrs for filtering
                row.dataset.reading = 'has';
                row.dataset.date    = data.reading_date;

                // Inject / update Edit button
                const actionsCell = row.cells[4];
                let editBtn = actionsCell.querySelector('.btn-warning');
                if (!editBtn) {
                    editBtn = document.createElement('button');
                    editBtn.className = 'btn btn-warning btn-sm';
                    editBtn.setAttribute('data-bs-toggle', 'modal');
                    editBtn.setAttribute('data-bs-target', '#editReadingModal');
                    editBtn.innerHTML = '✏️ Edit';
                    actionsCell.appendChild(editBtn);
                }
                editBtn.dataset.readingId    = data.reading_id;
                editBtn.dataset.customerName = document.getElementById('addCustomerName').value;
                editBtn.dataset.readingDate  = data.reading_date;
                editBtn.dataset.readingValue = data.reading_value;

                row.querySelector('button[data-customer-id]').dataset.lastReading = data.reading_value;

                applyFilters();

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('addReadingModal')).hide();
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                }, 800);
            } else {
                msgDiv.innerHTML = `<div class="alert alert-danger py-2">${data.message}</div>`;
            }
        }).finally(() => {
            saveBtn.disabled  = false;
            saveBtn.innerHTML = `Save Reading & Generate Bill`;
        });
});

/* ═══════════════════════════════════════════════════
   EDIT READING AJAX
═══════════════════════════════════════════════════ */
document.querySelector('#editReadingModal form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const saveBtn  = this.querySelector('button[name="edit_reading"]');
    saveBtn.disabled  = true;
    saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Saving…`;

    fetch('/AquaTrack/modules/staff/ajax_edit_reading.php', { method: 'POST', body: formData })
        .then(r => r.json()).then(data => {
            const msgDiv = document.getElementById('editModalMessage');
            if (data.status === 'success') {
                msgDiv.innerHTML = `<div class="alert alert-success py-2">${data.message}</div>`;

                const row = document.querySelector(`button[data-reading-id="${formData.get('reading_id')}"]`).closest('tr');
                row.cells[2].innerHTML = `<span class="fw-semibold">${data.reading_value}</span> <small class="text-muted ms-1">m³</small>`;
                row.cells[3].textContent = data.reading_date;
                row.dataset.date = data.reading_date;

                const editBtn = row.querySelector('button[data-reading-id]');
                editBtn.dataset.readingDate  = data.reading_date;
                editBtn.dataset.readingValue = data.reading_value;

                applyFilters();

                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('editReadingModal')).hide();
                    document.body.classList.remove('modal-open');
                    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                }, 800);
            } else {
                msgDiv.innerHTML = `<div class="alert alert-danger py-2">${data.message}</div>`;
            }
        }).finally(() => {
            saveBtn.disabled  = false;
            saveBtn.innerHTML = `Save Changes`;
        });
});

/* ═══════════════════════════════════════════════════
   BILL ESTIMATION
═══════════════════════════════════════════════════ */
document.querySelector('#addReadingModal input[name="reading_value"]').addEventListener('input', function () {
    const customerId  = document.getElementById('addCustomerId').value;
    const lastReading = document.querySelector(`button[data-customer-id="${customerId}"]`)?.dataset.lastReading || 0;
    const consumption = (parseFloat(this.value) || 0) - parseFloat(lastReading);
    document.getElementById('estimatedBill').textContent =
        (consumption > 0 ? consumption * CURRENT_RATE : 0).toFixed(2);
});

document.querySelector('#editReadingModal input[name="reading_value"]').addEventListener('input', function () {
    document.getElementById('editEstimatedBill').textContent =
        ((parseFloat(this.value) || 0) * CURRENT_RATE).toFixed(2);
});

/* ═══════════════════════════════════════════════════
   DISCONNECT / RECONNECT AJAX
═══════════════════════════════════════════════════ */
document.getElementById('disconnectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/AquaTrack/modules/staff/ajax_schedule_disconnect.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(data => {
            document.getElementById('discMsg').innerHTML =
                `<div class="alert alert-${data.status === 'success' ? 'success' : 'danger'} py-2">${data.message}</div>`;
            if (data.status === 'success') setTimeout(() => location.reload(), 1000);
        });
});

document.getElementById('reconnectForm').addEventListener('submit', function(e) {
    e.preventDefault();
    fetch('/AquaTrack/modules/staff/ajax_reconnect.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json()).then(data => {
            document.getElementById('reconMsg').innerHTML =
                `<div class="alert alert-${data.status === 'success' ? 'success' : 'danger'} py-2">${data.message}</div>`;
            if (data.status === 'success') setTimeout(() => location.reload(), 1000);
        });
});
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>