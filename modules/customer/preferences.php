<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';

$user_id = $_SESSION['user']['id'];

$stmt = $pdo->prepare("
    SELECT * FROM user_contact_preferences
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$prefs = $stmt->fetch();

/* Auto-create if missing */
if (!$prefs) {
    $pdo->prepare("
        INSERT INTO user_contact_preferences (user_id)
        VALUES (?)
    ")->execute([$user_id]);

    $prefs = [
        'email_enabled' => 1,
        'sms_enabled' => 1
    ];
}

?>

<div class="container mt-4">
    <h3>⚙ Notification Preferences</h3>

    <form id="prefsForm" class="card p-4 shadow-sm mt-3">
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox"
                   name="email_enabled"
                   <?= $prefs['email_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label">
                Receive Email Notifications
            </label>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox"
                   name="sms_enabled"
                   <?= $prefs['sms_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label">
                Receive SMS Notifications
            </label>
        </div>

        <button class="btn btn-primary">Save Preferences</button>
        <div id="prefsMsg" class="mt-3"></div>
    </form>
</div>

<script>
document.getElementById('prefsForm').addEventListener('submit', e => {
    e.preventDefault();

    fetch('ajax_save_preferences.php', {
        method: 'POST',
        body: new FormData(e.target)
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('prefsMsg').innerHTML =
            `<div class="alert alert-success">${d.message}</div>`;
    });
});
</script>
