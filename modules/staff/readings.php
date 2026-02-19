<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

define('RATE_PER_CUBIC_METER', 25);

$message = '';
$error = '';

$search = trim($_GET['search'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

/* =============================
   COUNT TOTAL CUSTOMERS (WITH SEARCH)
============================= */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM customers c
    JOIN users u ON c.user_id = u.id
    WHERE u.full_name LIKE :search OR c.meter_number LIKE :search
");
$countStmt->execute(['search' => "%$search%"]);
$totalCustomers = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalCustomers / $perPage);

/* =============================
   FETCH CUSTOMERS + LAST READING (WITH SEARCH & PAGINATION)
============================= */
$stmt = $pdo->prepare("
    SELECT
        c.id AS customer_id,
        c.service_status,
        u.full_name,
        c.meter_number,
        MAX(r.reading_date) AS last_reading_date,
        MAX(r.reading_value) AS last_reading,
        MAX(r.id) AS last_reading_id
    FROM customers c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN readings r ON c.id = r.customer_id
    WHERE u.full_name LIKE :search OR c.meter_number LIKE :search
    GROUP BY c.id, u.full_name, c.meter_number
    ORDER BY u.full_name
    LIMIT :offset, :perPage
");
$stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll();

/* =============================
   HANDLE ADD READING
============================= */
if (isset($_POST['add_reading'])) {
    $customer_id = $_POST['customer_id'];
    $newReading = (float) $_POST['reading_value'];
    $readingDate = $_POST['reading_date'];

    $stmt = $pdo->prepare("SELECT reading_value FROM readings WHERE customer_id = ? ORDER BY reading_date DESC LIMIT 1");
    $stmt->execute([$customer_id]);
    $lastReading = $stmt->fetch();
    $previousValue = $lastReading['reading_value'] ?? 0;

    if ($newReading <= $previousValue) {
        $error = "New reading must be greater than previous ({$previousValue}).";
    } else {
        $consumption = $newReading - $previousValue;
        $amount = $consumption * RATE_PER_CUBIC_METER;

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO readings (customer_id, reading_date, reading_value) VALUES (?, ?, ?)");
            $stmt->execute([$customer_id, $readingDate, $newReading]);
            $reading_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO bills (customer_id, reading_id, amount, status) VALUES (?, ?, ?, 'unpaid')");
            $stmt->execute([$customer_id, $reading_id, $amount]);

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
    $reading_id = $_POST['reading_id'];
    $newValue = (float) $_POST['reading_value'];
    $readingDate = $_POST['reading_date'];

    $stmt = $pdo->prepare("SELECT customer_id, reading_value FROM readings WHERE id = ?");
    $stmt->execute([$reading_id]);
    $reading = $stmt->fetch();

    if (!$reading) {
        $error = "Reading not found.";
    } elseif ($newValue <= 0) {
        $error = "Reading must be positive.";
    } else {
        $customer_id = $reading['customer_id'];
        $amount = $newValue * RATE_PER_CUBIC_METER;

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE readings SET reading_value = ?, reading_date = ? WHERE id = ?");
            $stmt->execute([$newValue, $readingDate, $reading_id]);

            $stmt = $pdo->prepare("UPDATE bills SET amount = ? WHERE reading_id = ?");
            $stmt->execute([$amount, $reading_id]);

            $pdo->commit();
            $message = "Reading updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to update reading: " . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid px-4 mt-4">
    <h3 class="mb-3">📏 Meter Readings</h3>

    <form class="d-flex mb-3" method="GET">
        <input type="text" name="search" class="form-control me-2" placeholder="Search by name or meter #" value="<?= htmlspecialchars($search) ?>">
        <button class="btn btn-primary">Search</button>
    </form>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Customer</th>
                            <th>Meter #</th>
                            <th>Last Reading</th>
                            <th>Last Date</th>
                            <th width="200">Actions</th>
                            <th>Service Status</th>
                            <th>Connection</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['full_name']) ?></td>
                                <td><?= htmlspecialchars($c['meter_number']) ?></td>
                                <td><?= $c['last_reading'] ?? '—' ?></td>
                                <td><?= $c['last_reading_date'] ?? '—' ?></td>
                                <td>
                                    <!-- Add Reading Modal Button -->
                                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addReadingModal"
                                        data-customer-id="<?= $c['customer_id'] ?>"
                                        data-customer-name="<?= htmlspecialchars($c['full_name']) ?>"
                                        data-last-reading="<?= $c['last_reading'] ?? 0 ?>">➕ Add</button>

                                    <!-- Edit Reading Modal Button -->
                                    <?php if ($c['last_reading'] !== null): ?>
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editReadingModal"
                                        data-reading-id="<?= $c['last_reading_id'] ?>"
                                        data-customer-name="<?= htmlspecialchars($c['full_name']) ?>"
                                        data-reading-date="<?= $c['last_reading_date'] ?>"
                                        data-reading-value="<?= $c['last_reading'] ?>">✏️ Edit</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-warning">
                                        <?= $c['service_status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($c['service_status'] === 'active'): ?>
                                        <button class="btn btn-danger btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#disconnectModal"
                                            data-customer-id="<?= $c['customer_id'] ?>"
                                            data-customer-name="<?= $c['full_name'] ?>">
                                            Disconnect
                                        </button>
                                    <?php endif; ?>

                                    <?php if($c['service_status'] === 'disconnected'): ?>
                                        <button class="btn btn-success btn-sm"
                                            data-bs-toggle="modal"
                                            data-bs-target="#reconnectModal"
                                            data-customer-id="<?= $c['customer_id'] ?>"
                                            data-customer-name="<?= $c['full_name'] ?>">
                                            Reconnect
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($customers) === 0): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No customers found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center mt-3">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>">Previous</a>
                    </li>
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Reading Modal -->
<div class="modal fade" id="addReadingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Meter Reading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="addCustomerId">
                    <div class="mb-3">
                        <label>Customer</label>
                        <input type="text" class="form-control" id="addCustomerName" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Reading Date</label>
                        <input type="date" name="reading_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Reading Value</label>
                        <input type="number" step="0.01" name="reading_value" class="form-control" required>
                    </div>
                    <div id="addModalMessage"></div>
                </div>
                <div class="modal-footer">
                    <button name="add_reading" class="btn btn-success w-100">Save Reading & Generate Bill</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Reading Modal -->
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
                        <label>Customer</label>
                        <input type="text" class="form-control" id="editCustomerName" readonly>
                    </div>
                    <div class="mb-3">
                        <label>Reading Date</label>
                        <input type="date" name="reading_date" class="form-control" id="editReadingDate" required>
                    </div>
                    <div class="mb-3">
                        <label>Reading Value</label>
                        <input type="number" step="0.01" name="reading_value" class="form-control" id="editReadingValue" required>
                    </div>
                    <div id="editModalMessage"></div>
                </div>
                <div class="modal-footer">
                    <button name="edit_reading" type="submit" class="btn btn-warning w-100">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="disconnectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="disconnectForm">
                <div class="modal-header">
                    <h5>Schedule Disconnection</h5>
                </div>
                
                <div class="modal-body">

                <input type="hidden" name="customer_id" id="discCustomerId">

                <div class="mb-3">
                    <label>Customer</label>
                    <input type="text" id="discCustomerName"
                        class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label>Scheduled Date</label>
                    <input type="date" name="scheduled_date"
                        class="form-control" required>
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

<div class="modal fade" id="reconnectModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="reconnectForm">
                <div class="modal-header">
                    <h5>Schedule Reconnection</h5>
                </div>
                
                <div class="modal-body">

                <input type="hidden" name="customer_id" id="reconCustomerId">

                <div class="mb-3">
                    <label>Customer</label>
                    <input type="text" id="reconCustomerName"
                        class="form-control" readonly>
                </div>

                <div class="mb-3">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control"></textarea>
                </div>

                <div class="mb-3">
                    <label>Scheduled Date</label>
                    <input type="date" name="scheduled_date"
                        class="form-control" required>
                </div>

                <div class="mb-3">
                    <label>Reconnection Fee</label>
                    <input type="number" step="0.01"
                        name="reconnection_fee"
                        class="form-control"
                        value="150"
                        required>
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


<script>
// Populate Add Modal
const addModal = document.getElementById('addReadingModal');
addModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('addCustomerId').value = button.getAttribute('data-customer-id');
    document.getElementById('addCustomerName').value = button.getAttribute('data-customer-name');
});

// Populate Edit Modal
const editModal = document.getElementById('editReadingModal');
editModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    document.getElementById('editReadingId').value = button.getAttribute('data-reading-id');
    document.getElementById('editCustomerName').value = button.getAttribute('data-customer-name');
    document.getElementById('editReadingDate').value = button.getAttribute('data-reading-date');
    document.getElementById('editReadingValue').value = button.getAttribute('data-reading-value');
});

// Populate Disconnect Modal
document.getElementById('disconnectModal')
.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    discCustomerId.value = btn.dataset.customerId;
    discCustomerName.value = btn.dataset.customerName;
});

// Add Reading AJAX
const addModalForm = document.querySelector('#addReadingModal form');
addModalForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/AquaTrack/modules/staff/ajax_add_reading.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        const messageDiv = document.getElementById('addModalMessage');
        if(data.status === 'success'){
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            // Optionally, update the table row inline
            const customerRow = document.querySelector(`button[data-customer-id="${formData.get('customer_id')}"]`).closest('tr');
            customerRow.querySelector('td:nth-child(3)').textContent = data.reading_value;
            customerRow.querySelector('td:nth-child(4)').textContent = data.reading_date;
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    });
});

// Edit Reading AJAX
const editModalForm = document.querySelector('#editReadingModal form');
editModalForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('/AquaTrack/modules/staff/ajax_edit_reading.php', {
        method: 'POST',
        body: formData
    }).then(res => res.json()).then(data => {
        const messageDiv = document.getElementById('editModalMessage');
        if(data.status === 'success'){
            messageDiv.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
            // Update table row inline
            const row = document.querySelector(`button[data-reading-id="${formData.get('reading_id')}"]`).closest('tr');
            row.querySelector('td:nth-child(3)').textContent = data.reading_value;
            row.querySelector('td:nth-child(4)').textContent = data.reading_date;
        } else {
            messageDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
    });
});

// Populate Reconnect Modal
document.getElementById('reconnectModal')
.addEventListener('show.bs.modal', e => {
    const btn = e.relatedTarget;
    reconCustomerId.value = btn.dataset.customerId;
    reconCustomerName.value = btn.dataset.customerName;
});

// DISCONNECT AJAX
document.getElementById('disconnectForm')
.addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('/AquaTrack/modules/staff/ajax_schedule_disconnect.php', {
        method:'POST',
        body:formData
    })
    .then(res=>res.json())
    .then(data=>{
        discMsg.innerHTML =
            `<div class="alert alert-${data.status==='success'?'success':'danger'}">
                ${data.message}
            </div>`;

        if(data.status==='success'){
            setTimeout(()=> location.reload(),1000);
        }
    });
});

// RECONNECT AJAX
document.getElementById('reconnectForm')
.addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);

    fetch('/AquaTrack/modules/staff/ajax_reconnect.php', {
        method:'POST',
        body:formData
    })
    .then(res=>res.json())
    .then(data=>{
        reconMsg.innerHTML =
            `<div class="alert alert-${data.status==='success'?'success':'danger'}">
                ${data.message}
            </div>`;

        if(data.status==='success'){
            setTimeout(()=> location.reload(),1000);
        }
    });
});
</script>



<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
