<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$cfg = $pdo->query("SELECT * FROM penalty_settings LIMIT 1")->fetch();

if (isset($_POST['save'])) {
    $stmt = $pdo->prepare("
        UPDATE penalty_settings
        SET monthly_rate = ?,
            grace_days = ?,
            max_penalty_percent = ?,
            enabled = ?
    ");

    $stmt->execute([
        $_POST['monthly_rate'],
        $_POST['grace_days'],
        $_POST['max_penalty_percent'],
        isset($_POST['enabled']) ? 1 : 0
    ]);

    header("Location: penalties.php?success=1");

    auditLog(
        $pdo,
        'PENALTY_UPDATE',
        'Penalty settings modified'
    );

    exit;
}
?>

<div class="container mt-4">
    <h3>⚠️ Penalty Configuration</h3>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Settings updated successfully.</div>
    <?php endif; ?>

    <div class="card shadow-sm mt-3">
        <div class="card-body">

            <form method="POST">

                <div class="mb-3">
                    <label>Monthly Penalty Rate (%)</label>
                    <input type="number" step="0.01" name="monthly_rate"
                           class="form-control"
                           value="<?= $cfg['monthly_rate'] ?>" required>
                </div>

                <div class="mb-3">
                    <label>Grace Period (days)</label>
                    <input type="number" name="grace_days"
                           class="form-control"
                           value="<?= $cfg['grace_days'] ?>" required>
                </div>

                <div class="mb-3">
                    <label>Maximum Penalty (%)</label>
                    <input type="number" step="0.01" name="max_penalty_percent"
                           class="form-control"
                           value="<?= $cfg['max_penalty_percent'] ?>" required>
                </div>

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                           name="enabled" <?= $cfg['enabled'] ? 'checked' : '' ?>>
                    <label class="form-check-label">Enable Penalties</label>
                </div>

                <button name="save" class="btn btn-primary">
                    💾 Save Settings
                </button>

            </form>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>
