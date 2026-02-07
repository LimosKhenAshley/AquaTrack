<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);

require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

/* ======================
   Load or auto-create prefs
====================== */
$stmt = $pdo->prepare("
SELECT * FROM user_contact_preferences
WHERE user_id = ?
");
$stmt->execute([$user_id]);
$prefs = $stmt->fetch();

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

/* ======================
   Save handler
====================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = isset($_POST['email_enabled']) ? 1 : 0;
    $sms   = isset($_POST['sms_enabled']) ? 1 : 0;

    $stmt = $pdo->prepare("
    UPDATE user_contact_preferences
    SET email_enabled=?, sms_enabled=?
    WHERE user_id=?
    ");
    $stmt->execute([$email, $sms, $user_id]);

    $success = true;
    $prefs['email_enabled'] = $email;
    $prefs['sms_enabled'] = $sms;
}
?>

<div class="container mt-4">

    <h3>⚙ Notification Preferences</h3>

    <?php if(!empty($success)): ?>
        <div class="alert alert-success">
            Preferences updated successfully.
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mt-3">
        <div class="card-body">

            <form method="POST">

                <!-- EMAIL -->
                <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
                    <div>
                        <h6 class="mb-1">📧 Email Notifications</h6>
                        <small class="text-muted">
                            Receive billing and system alerts via email
                        </small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               name="email_enabled"
                               <?= $prefs['email_enabled'] ? 'checked' : '' ?>>
                    </div>
                </div>

                <!-- SMS -->
                <div class="d-flex justify-content-between align-items-center border rounded p-3 mb-3">
                    <div>
                        <h6 class="mb-1">📱 SMS Notifications</h6>
                        <small class="text-muted">
                            Receive urgent alerts via text message
                        </small>
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input"
                               type="checkbox"
                               name="sms_enabled"
                               <?= $prefs['sms_enabled'] ? 'checked' : '' ?>>
                    </div>
                </div>

                <button class="btn btn-primary w-100">
                    Save Preferences
                </button>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../app/layouts/footer.php'; ?>
