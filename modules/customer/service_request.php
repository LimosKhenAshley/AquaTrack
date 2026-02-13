<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

$c = $pdo->prepare("SELECT id FROM customers WHERE user_id=?");
$c->execute([$user_id]);
$customer_id = $c->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("
        INSERT INTO service_requests
        (customer_id, subject, message, type, priority)
        VALUES (?,?,?,?,?)
    ");
    $stmt->execute([
        $customer_id,
        $_POST['subject'],
        $_POST['message'],
        $_POST['type'],
        $_POST['priority']
    ]);

    echo "<div class='alert alert-success'>Request submitted.</div>";
}
?>

<div class="container mt-4">
    <h3>🛠 Service Request</h3>

    <div class="card shadow-sm">
        <div class="card-body">

            <form method="post">

            <div class="mb-3">
                <label>Subject</label>
                <input name="subject" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Type</label>
                <select name="type" class="form-select">
                    <option value="billing">Billing</option>
                    <option value="meter">Meter Issue</option>
                    <option value="leak">Leak Report</option>
                    <option value="connection">Connection</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Priority</label>
                <select name="priority" class="form-select">
                    <option value="low">Low</option>
                    <option value="normal" selected>Normal</option>
                    <option value="high">High</option>
                </select>
            </div>

            <div class="mb-3">
                <label>Message</label>
                <textarea name="message" rows="5" class="form-control" required></textarea>
            </div>

            <button class="btn btn-primary w-100">Submit Request</button>

            </form>

        </div>
    </div>
</div>

<?php require_once '../../app/layouts/footer.php'; ?>