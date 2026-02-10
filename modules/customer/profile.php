<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$user_id = $_SESSION['user']['id'];

/* =========================
   LOAD USER
========================= */
$stmt = $pdo->prepare("SELECT full_name,email,phone FROM users WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if(!$user){
    die("User not found");
}

$msg = "";

/* =========================
   UPDATE PROFILE
========================= */
if(isset($_POST['update_profile'])){

    $name  = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // PH E.164 validation
    if(!preg_match('/^\+63\d{10}$/', $phone)){
        $msg = "<div class='alert alert-danger'>Phone must be PH E.164 format (+639XXXXXXXXX)</div>";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $msg = "<div class='alert alert-danger'>Invalid email address</div>";
    }
    else {

        $upd = $pdo->prepare("
            UPDATE users
            SET full_name=?, email=?, phone=?
            WHERE id=?
        ");
        $upd->execute([$name,$email,$phone,$user_id]);

        // optional audit log
        if(function_exists('auditLog')){
            auditLog($pdo,$user_id,"Updated profile");
        }

        $msg = "<div class='alert alert-success'>Profile updated successfully</div>";

        // reload fresh data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    }
}

/* =========================
   CHANGE PASSWORD
========================= */
if(isset($_POST['change_password'])){

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if(strlen($new) < 6){
        $msg .= "<div class='alert alert-danger'>New password must be at least 6 characters</div>";
    }
    elseif($new !== $confirm){
        $msg .= "<div class='alert alert-danger'>Passwords do not match</div>";
    }
    else {

        // get current hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id=?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if(!password_verify($current, $hash)){
            $msg .= "<div class='alert alert-danger'>Current password is incorrect</div>";
        }
        else {

            $newHash = password_hash($new, PASSWORD_DEFAULT);

            $upd = $pdo->prepare("
                UPDATE users SET password=? WHERE id=?
            ");
            $upd->execute([$newHash, $user_id]);

            $msg .= "<div class='alert alert-success'>Password changed successfully</div>";

            if(function_exists('auditLog')){
                auditLog($pdo,'CHANGE_PASSWORD',"User ID: $user_id");
            }
        }
    }
}

?>

<div class="container mt-4">

    <h3>👤 My Profile</h3>

    <?= $msg ?>

    <div class="card shadow-sm mt-3">
        <div class="card-body">

            <form method="POST">

                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text"
                           name="full_name"
                           class="form-control"
                           required
                           value="<?= htmlspecialchars($user['full_name']) ?>">
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email"
                           name="email"
                           class="form-control"
                           required
                           value="<?= htmlspecialchars($user['email']) ?>">
                </div>

                <div class="mb-3">
                    <label>Phone (PH E.164)</label>
                    <input type="text"
                           name="phone"
                           class="form-control"
                           pattern="\+63\d{10}"
                           placeholder="+639XXXXXXXXX"
                           required
                           value="<?= htmlspecialchars((string)$user['phone']) ?>">
                    <small class="text-muted">
                        Format: +639XXXXXXXXX
                    </small>
                </div>

                <button class="btn btn-primary" name="update_profile" value="1">
                    💾 Save Changes
                </button>

            </form>

        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-body">

            <h5>🔐 Change Password</h5>

            <form method="POST">

                <div class="mb-3">
                    <label>Current Password</label>
                    <input type="password"
                        name="current_password"
                        class="form-control"
                        required>
                </div>

                <div class="mb-3">
                    <label>New Password</label>
                    <input type="password"
                        name="new_password"
                        class="form-control"
                        required>
                </div>

                <div class="mb-3">
                    <label>Confirm New Password</label>
                    <input type="password"
                        name="confirm_password"
                        class="form-control"
                        required>
                </div>

                <button class="btn btn-warning"
                        name="change_password"
                        value="1">
                    🔐 Update Password
                </button>

            </form>

        </div>
    </div>

</div>

<?php require_once '../../app/layouts/footer.php'; ?>
