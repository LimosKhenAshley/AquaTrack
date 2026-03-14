<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);

require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

/**
 * Get Customer ID
 */
$customer_id = getCustomerId($pdo, $user_id);

/**
 * Handle Create Request
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_request'])) {
    createServiceRequest($pdo, $customer_id, $_POST);
    header("Location: service_request.php?success=1");
    exit;
}

/**
 * Handle Cancel Request
 */
if (isset($_GET['cancel'])) {
    cancelServiceRequest($pdo, $_GET['cancel'], $customer_id);
    header("Location: service_request.php?cancelled=1");
    exit;
}

/**
 * Pagination Setup
 */
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$totalRows = getTotalRequestsCount($pdo, $customer_id);
$totalPages = ceil($totalRows / $limit);

/**
 * Fetch Requests
 */
$requests = getServiceRequests($pdo, $customer_id, $limit, $offset);

/**
 * Helper Functions
 */
function getCustomerId($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function createServiceRequest($pdo, $customer_id, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO service_requests
        (customer_id, subject, message, type, priority, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'open', NOW())
    ");
    
    $stmt->execute([
        $customer_id,
        sanitizeInput($data['subject']),
        sanitizeInput($data['message']),
        $data['type'],
        getAutoPriority($data['type'])  // ← auto-assigned, not from $_POST
    ]);
}

function getAutoPriority($type) {
    $map = [
        'leak'       => 'high',
        'connection' => 'high',
        'meter'      => 'normal',
        'billing'    => 'low',
        'other'      => 'normal',
    ];
    return $map[$type] ?? 'normal';
}

function cancelServiceRequest($pdo, $request_id, $customer_id) {
    $stmt = $pdo->prepare("
        UPDATE service_requests
        SET status = 'cancelled'
        WHERE id = ? AND customer_id = ? AND status = 'open'
    ");
    $stmt->execute([$request_id, $customer_id]);
}

function getTotalRequestsCount($pdo, $customer_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_requests WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    return $stmt->fetchColumn();
}

function getServiceRequests($pdo, $customer_id, $limit, $offset) {
    $stmt = $pdo->prepare("
        SELECT *
        FROM service_requests
        WHERE customer_id = ?
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    // Bind parameters with explicit types
    $stmt->bindParam(1, $customer_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $limit, PDO::PARAM_INT);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getPriorityBadgeClass($priority) {
    if ($priority == 'high') return 'danger';
    if ($priority == 'normal') return 'primary';
    return 'secondary';
}

function getStatusBadgeClass($status) {
    if ($status == 'open') return 'secondary';
    if ($status == 'in_progress') return 'warning';
    if ($status == 'resolved') return 'success';
    return 'dark';
}

function formatDate($date) {
    return date("M d, Y h:i A", strtotime($date));
}
?>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">🛠️My Service Requests</h2>
        </div>

        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
            <i class="bi bi-file-earmark-plus me-2"></i>
            New Request
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if(isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            Request submitted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['cancelled'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Request cancelled.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Search Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search"></i>
                </span>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="form-control" 
                    placeholder="Search by subject, type, or message..."
                >
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <small class="text-muted">
                Showing <?= count($requests) ?> of <?= $totalRows ?> requests
            </small>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="requestTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 180px">Date</th>
                            <th>Subject</th>
                            <th style="width: 120px">Type</th>
                            <th style="width: 100px">Priority</th>
                            <th style="width: 100px">Status</th>
                            <th style="width: 140px" class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if($requests): ?>
                            <?php foreach($requests as $request): ?>
                                <tr>
                                    <td class="align-middle">
                                        <small><?= formatDate($request['created_at']) ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <strong><?= htmlspecialchars($request['subject']) ?></strong>
                                    </td>
                                    <td class="align-middle">
                                        <?= ucfirst($request['type']) ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-<?= getPriorityBadgeClass($request['priority']) ?>">
                                            <?= ucfirst($request['priority']) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge bg-<?= getStatusBadgeClass($request['status']) ?>">
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <!-- VIEW BUTTON WITH TEXT -->
                                        <button 
                                            class="btn btn-sm btn-info me-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#viewModal<?= $request['id'] ?>"
                                        >
                                            View
                                        </button>

                                        <?php if($request['status'] == 'open'): ?>
                                            <a href="?cancel=<?= $request['id'] ?>"
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Are you sure you want to cancel this request?')">
                                                Cancel
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?= $request['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    Request Details
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="text-muted small">Subject</label>
                                                    <p class="fw-semibold"><?= htmlspecialchars($request['subject']) ?></p>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-6">
                                                        <label class="text-muted small">Type</label>
                                                        <p><?= ucfirst($request['type']) ?></p>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="text-muted small">Priority</label>
                                                        <p>
                                                            <span class="badge bg-<?= getPriorityBadgeClass($request['priority']) ?>">
                                                                <?= ucfirst($request['priority']) ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="row mb-3">
                                                    <div class="col-6">
                                                        <label class="text-muted small">Status</label>
                                                        <p>
                                                            <span class="badge bg-<?= getStatusBadgeClass($request['status']) ?>">
                                                                <?= ucfirst($request['status']) ?>
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="text-muted small">Date Created</label>
                                                        <p><?= formatDate($request['created_at']) ?></p>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="mb-3">
                                                    <label class="text-muted small">Message</label>
                                                    <div class="bg-light p-3 rounded">
                                                        <?= nl2br(htmlspecialchars($request['message'])) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    Close
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                                    <p class="text-muted mb-0">No service requests yet.</p>
                                    <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#requestModal">
                                        Create your first request
                                    </button>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page-1 ?>">Previous</a>
                </li>
                
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
                
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page+1 ?>">Next</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Submit Service Request
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="post">
                <input type="hidden" name="create_request" value="1">

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input 
                            type="text" 
                            name="subject" 
                            class="form-control" 
                            required 
                            maxlength="255"
                            placeholder="Brief description of your issue"
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select" required>
                            <option value="">Select type...</option>
                            <option value="billing">Billing</option>
                            <option value="meter">Meter Issue</option>
                            <option value="leak">Leak Report</option>
                            <option value="connection">Connection</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea 
                            name="message" 
                            rows="5" 
                            class="form-control" 
                            required
                            placeholder="Please provide detailed information about your request..."
                        ></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<!-- Live Search Script -->
<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#requestTable tbody tr");
    
    rows.forEach(row => {
        // Skip the "no results" row
        if (row.children.length === 1 && row.children[0].colSpan === 6) return;
        
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

<?php require_once '../../app/layouts/footer.php'; ?>