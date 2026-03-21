<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';

/* ==============================
   PREVENT ACCESS IF LOGGED IN
============================== */
if (isset($_SESSION['user'])) {
    header("Location: ../" . $_SESSION['user']['role'] . "/dashboard.php");
    exit;
}

/* ==============================
   GENERATE CSRF TOKEN
============================== */
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$message        = '';
$messageType    = '';
$resetSuccess   = false;
$tokenValid     = false;
$token          = trim($_GET['token'] ?? '');

/* ==============================
   VALIDATE TOKEN (GET REQUEST)
============================== */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($token)) {
        $message     = "No reset token provided.";
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, full_name, email, reset_token_expires
            FROM users
            WHERE reset_token = ? AND status = 'active'
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $message     = "This reset link is invalid or has already been used.";
            $messageType = 'danger';
        } elseif (strtotime($user['reset_token_expires']) < time()) {
            $message     = "This reset link has expired. Please request a new one.";
            $messageType = 'warning';
        } else {
            $tokenValid = true;
        }
    }
}

/* ==============================
   HANDLE FORM SUBMISSION (POST)
============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF CHECK */
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $token    = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // Re-validate token on POST
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, reset_token_expires
        FROM users
        WHERE reset_token = ? AND status = 'active'
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user || strtotime($user['reset_token_expires']) < time()) {
        $message     = "This reset link is invalid or has expired. Please request a new one.";
        $messageType = 'danger';
    } elseif (empty($password)) {
        $message     = "Please enter a new password.";
        $messageType = 'danger';
        $tokenValid  = true;
    } elseif (strlen($password) < 8) {
        $message     = "Password must be at least 8 characters.";
        $messageType = 'danger';
        $tokenValid  = true;
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $message     = "Password must contain at least one uppercase letter.";
        $messageType = 'danger';
        $tokenValid  = true;
    } elseif (!preg_match('/[0-9]/', $password)) {
        $message     = "Password must contain at least one number.";
        $messageType = 'danger';
        $tokenValid  = true;
    } elseif ($password !== $confirm) {
        $message     = "Passwords do not match.";
        $messageType = 'danger';
        $tokenValid  = true;
    } else {
        // All good — update password and clear token
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare("
            UPDATE users
            SET password = ?, reset_token = NULL, reset_token_expires = NULL
            WHERE id = ?
        ")->execute([$hashed, $user['id']]);

        auditLog($pdo, 'PASSWORD_RESET_SUCCESS', 'Password reset for user ID: ' . $user['id']);

        // Regenerate CSRF to prevent re-use
        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        $resetSuccess = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password – AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            --primary-color: #0284c7;
        }
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 20px; position: relative; overflow-x: hidden;
        }

        /* Bubbles */
        .bubbles { position: absolute; width: 100%; height: 100%; overflow: hidden; top: 0; left: 0; pointer-events: none; }
        .bubble  { position: absolute; bottom: -100px; background: rgba(255,255,255,0.1); border-radius: 50%; animation: rise 10s infinite ease-in; }
        @keyframes rise {
            0%   { bottom: -100px; transform: translateX(0); }
            50%  { transform: translateX(100px); }
            100% { bottom: 1080px; transform: translateX(-200px); }
        }
        .bubble:nth-child(1){left:10%;width:80px; height:80px; animation-duration:8s;}
        .bubble:nth-child(2){left:20%;width:40px; height:40px; animation-duration:12s;animation-delay:2s;}
        .bubble:nth-child(3){left:35%;width:120px;height:120px;animation-duration:16s;animation-delay:4s;}
        .bubble:nth-child(4){left:50%;width:60px; height:60px; animation-duration:10s;animation-delay:1s;}
        .bubble:nth-child(5){left:70%;width:100px;height:100px;animation-duration:14s;animation-delay:3s;}
        .bubble:nth-child(6){left:85%;width:50px; height:50px; animation-duration:9s; animation-delay:5s;}

        .login-container { width: 100%; max-width: 440px; position: relative; z-index: 10; }
        .login-card {
            background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);
            border-radius: 20px; padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.5s ease-out;
            border: 1px solid rgba(255,255,255,0.2);
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .login-header { text-align: center; margin-bottom: 2rem; }
        .login-header .logo {
            width: 80px; height: 80px; background: var(--primary-gradient);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; box-shadow: 0 10px 30px rgba(2,132,199,0.4);
        }
        .login-header .logo i { font-size: 40px; color: white; }
        .login-header h1 { font-size: 26px; font-weight: 700; color: #2d3748; margin-bottom: 0.5rem; }
        .login-header p  { color: #718096; font-size: 14px; }

        .form-label { font-weight: 600; color: #4a5568; margin-bottom: 0.5rem; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .input-group-text { background: transparent; border-right: none; color: #a0aec0; border: 2px solid #e2e8f0; }
        .input-group-text.toggle-pw { border-left: none; border-right: 2px solid #e2e8f0; cursor: pointer; transition: color 0.2s; }
        .input-group-text.toggle-pw:hover { color: var(--primary-color); }
        .form-control {
            border: 2px solid #e2e8f0; border-left: none; border-right: none;
            padding: 0.75rem 1rem; font-size: 1rem; transition: all 0.3s ease;
        }
        .form-control:focus { border-color: var(--primary-color); box-shadow: none; }
        .input-group:focus-within .input-group-text { border-color: var(--primary-color); }

        .btn-login {
            background: var(--primary-gradient); color: white; font-weight: 700;
            padding: 0.875rem; border-radius: 12px; font-size: 1rem;
            letter-spacing: 0.5px; text-transform: uppercase; border: none;
            margin-top: 1.5rem; transition: all 0.3s ease; width: 100%;
        }
        .btn-login:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(2,132,199,0.4); color: white; }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }

        .alert { border-radius: 12px; border: none; padding: 1rem; margin-bottom: 1.5rem; }

        /* Strength bar */
        .strength-bar-wrap { height: 6px; background: #e2e8f0; border-radius: 99px; margin-top: 0.5rem; overflow: hidden; }
        .strength-bar { height: 100%; width: 0; border-radius: 99px; transition: width 0.4s ease, background 0.4s ease; }
        .strength-label { font-size: 0.75rem; color: #718096; margin-top: 0.3rem; min-height: 1rem; }

        /* Requirements */
        .requirements { background: #f0f9ff; border-radius: 12px; padding: 0.875rem 1rem; margin-top: 0.75rem; border-left: 4px solid var(--primary-color); }
        .req-item { font-size: 0.8rem; color: #718096; display: flex; align-items: center; gap: 0.4rem; line-height: 1.8; }
        .req-item i { font-size: 0.85rem; transition: color 0.2s; }
        .req-item.met { color: #38a169; }
        .req-item.met i { color: #38a169; }

        /* Success state */
        .success-state { text-align: center; padding: 1rem 0; }
        .success-state .success-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #48bb78, #38a169);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; box-shadow: 0 10px 30px rgba(72,187,120,0.4);
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn { from{transform:scale(0);opacity:0;} to{transform:scale(1);opacity:1;} }
        .success-state .success-icon i { font-size: 36px; color: white; }
        .success-state h3 { font-weight: 700; color: #2d3748; margin-bottom: 0.75rem; }
        .success-state p  { color: #718096; font-size: 0.95rem; line-height: 1.6; }

        /* Error state */
        .error-state { text-align: center; padding: 1rem 0; }
        .error-state .error-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #fc8181, #e53e3e);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; box-shadow: 0 10px 30px rgba(229,62,62,0.35);
            animation: scaleIn 0.5s ease-out;
        }
        .error-state .error-icon i { font-size: 36px; color: white; }
        .error-state h3 { font-weight: 700; color: #2d3748; margin-bottom: 0.75rem; }
        .error-state p  { color: #718096; font-size: 0.95rem; line-height: 1.6; }

        .back-link { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #edf2f7; }
        .back-link a {
            color: var(--primary-color); font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem; transition: gap 0.2s ease;
        }
        .back-link a:hover { gap: 0.75rem; }
        #countdown { font-weight: 700; color: var(--primary-color); }
    </style>
</head>
<body>
    <div class="bubbles">
        <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
    </div>

    <div class="login-container">
        <div class="login-card">

            <?php if ($resetSuccess): ?>
            <!-- SUCCESS STATE -->
            <div class="success-state">
                <div class="success-icon"><i class="bi bi-shield-check"></i></div>
                <h3>Password Updated!</h3>
                <p>Your password has been reset successfully. You can now sign in with your new password.</p>
                <p style="font-size:0.82rem;color:#a0aec0;margin-top:1rem;">
                    Redirecting to login in <span id="countdown">5</span> seconds…
                </p>
                <a href="login.php" class="btn btn-login" style="margin-top:1.25rem!important;display:inline-block;width:auto;padding:0.75rem 2rem;">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In Now
                </a>
            </div>

            <?php elseif (!$tokenValid): ?>
            <!-- INVALID / EXPIRED TOKEN STATE -->
            <div class="error-state">
                <div class="error-icon"><i class="bi bi-x-circle"></i></div>
                <h3><?= $messageType === 'warning' ? 'Link Expired' : 'Invalid Link' ?></h3>
                <p><?= htmlspecialchars($message) ?></p>
            </div>
            <div class="back-link">
                <a href="forgot_password.php"><i class="bi bi-arrow-clockwise"></i> Request a New Link</a>
            </div>

            <?php else: ?>
            <!-- RESET FORM -->
            <div class="login-header">
                <div class="logo"><i class="bi bi-shield-lock"></i></div>
                <h1>Set New Password</h1>
                <p>Create a strong password for your account</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="resetForm" novalidate>
                <input type="hidden" name="csrf"  value="<?= $_SESSION['csrf'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <!-- New Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password"
                               placeholder="Min. 8 characters" required autocomplete="new-password">
                        <span class="input-group-text toggle-pw" id="togglePw" title="Show password">
                            <i class="bi bi-eye" id="togglePwIcon"></i>
                        </span>
                    </div>
                    <div class="strength-bar-wrap mt-2"><div class="strength-bar" id="strengthBar"></div></div>
                    <div class="strength-label" id="strengthLabel"></div>
                    <div class="requirements mt-2">
                        <div class="req-item" id="req-len">  <i class="bi bi-circle"></i> At least 8 characters</div>
                        <div class="req-item" id="req-upper"><i class="bi bi-circle"></i> At least one uppercase letter</div>
                        <div class="req-item" id="req-num">  <i class="bi bi-circle"></i> At least one number</div>
                        <div class="req-item" id="req-match"><i class="bi bi-circle"></i> Passwords match</div>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div class="mb-2">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                               placeholder="Re-enter your password" required autocomplete="new-password">
                        <span class="input-group-text toggle-pw" id="toggleCf" title="Show password">
                            <i class="bi bi-eye" id="toggleCfIcon"></i>
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-login" id="submitBtn" disabled>
                    <span class="spinner-border spinner-border-sm d-none" id="spinner"></span>
                    <span id="btnText"><i class="bi bi-shield-check me-2"></i>Reset Password</span>
                </button>
            </form>

            <div class="back-link">
                <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    /* Show/hide toggles */
    function makeToggle(btnId, iconId, inputId) {
        document.getElementById(btnId)?.addEventListener('click', function () {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }
    makeToggle('togglePw', 'togglePwIcon', 'password');
    makeToggle('toggleCf', 'toggleCfIcon', 'confirm_password');

    /* Strength + requirements */
    const pwInput   = document.getElementById('password');
    const cfInput   = document.getElementById('confirm_password');
    const bar       = document.getElementById('strengthBar');
    const lbl       = document.getElementById('strengthLabel');
    const submitBtn = document.getElementById('submitBtn');

    const reqs = {
        len:   document.getElementById('req-len'),
        upper: document.getElementById('req-upper'),
        num:   document.getElementById('req-num'),
        match: document.getElementById('req-match'),
    };

    function setReq(el, met) {
        if (!el) return;
        el.classList.toggle('met', met);
        el.querySelector('i').className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle';
    }

    function assess() {
        const pw = pwInput ? pwInput.value : '';
        const cf = cfInput ? cfInput.value : '';

        const hasLen   = pw.length >= 8;
        const hasUpper = /[A-Z]/.test(pw);
        const hasNum   = /[0-9]/.test(pw);
        const matches  = pw.length > 0 && pw === cf;

        setReq(reqs.len,   hasLen);
        setReq(reqs.upper, hasUpper);
        setReq(reqs.num,   hasNum);
        setReq(reqs.match, matches);

        const score = [hasLen, hasUpper, hasNum,
                        /[^A-Za-z0-9]/.test(pw),
                        pw.length >= 12].filter(Boolean).length;

        const levels = [
            { w: '0%',   bg: 'transparent', txt: '' },
            { w: '25%',  bg: '#fc8181',     txt: 'Weak' },
            { w: '50%',  bg: '#f6ad55',     txt: 'Fair' },
            { w: '75%',  bg: '#68d391',     txt: 'Good' },
            { w: '100%', bg: '#48bb78',     txt: 'Strong' },
        ];
        const lvl = levels[Math.min(score, 4)];
        if (bar) { bar.style.width = pw.length ? lvl.w : '0%'; bar.style.background = lvl.bg; }
        if (lbl)   lbl.textContent = pw.length ? lvl.txt : '';

        if (submitBtn) submitBtn.disabled = !(hasLen && hasUpper && hasNum && matches);
    }

    pwInput?.addEventListener('input', assess);
    cfInput?.addEventListener('input', assess);

    /* Focus scale animation */
    document.querySelectorAll('.form-control').forEach(input => {
        input.addEventListener('focus', () => { const ig = input.closest('.input-group'); if (ig) ig.style.transform = 'scale(1.02)'; });
        input.addEventListener('blur',  () => { const ig = input.closest('.input-group'); if (ig) ig.style.transform = 'scale(1)'; });
    });

    /* Submit spinner */
    document.getElementById('resetForm')?.addEventListener('submit', function () {
        if (submitBtn) { submitBtn.disabled = true; }
        document.getElementById('spinner')?.classList.remove('d-none');
        const bt = document.getElementById('btnText');
        if (bt) bt.textContent = 'Updating…';
    });

    /* Auto-redirect countdown after success */
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
        let secs = 5;
        const iv = setInterval(() => {
            secs--;
            countdownEl.textContent = secs;
            if (secs <= 0) { clearInterval(iv); window.location.href = 'login.php'; }
        }, 1000);
    }
    </script>
</body>
</html>