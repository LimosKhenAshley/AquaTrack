<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
require_once __DIR__ . '/../../app/bootstrap.php';

/* =============================
   FETCH ALL BILLS
============================= */
$stmt = $pdo->query("
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
    ORDER BY b.created_at DESC
");
$bills = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <h3 class="mb-3">💳 Billing Management</h3>

    <div class="card shadow-sm">
        <div class="card-body">

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
                    <tbody>
                    <?php foreach ($bills as $bill): ?>
                        <tr>
                            <td><?= htmlspecialchars($bill['full_name']) ?></td>
                            <td><?= htmlspecialchars($bill['meter_number']) ?></td>
                            <td><?= $bill['reading_date'] ?></td>
                            <td><?= $bill['reading_value'] ?></td>
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

        </div>
    </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="markPaidForm">
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

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
