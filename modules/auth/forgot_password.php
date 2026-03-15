<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';

// PHPMailer (installed via Composer)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php';

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

$message     = '';
$messageType = '';
$emailSent   = false;

/* ==============================
   GMAIL SMTP CREDENTIALS
   Move these to a config/.env
   file — never commit to git!
============================== */
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'aquatrack.2026@gmail.com');  // <-- replace
define('MAIL_PASSWORD',  'tktv ddpg xkty jpet');   // <-- Gmail App Password (16 chars)
define('MAIL_FROM',      'aquatrack.2026@gmail.com');  // <-- replace
define('MAIL_FROM_NAME', 'AquaTrack');

/* ==============================
   HANDLE FORM SUBMISSION
============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF CHECK */
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $email = trim($_POST['email']);

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message     = "Please enter a valid email address.";
        $messageType = 'danger';
    } else {
        $stmt = $pdo->prepare("SELECT id, full_name, email, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Always show success — prevents email enumeration
        $emailSent = true;

        if ($user && $user['status'] === 'active') {

            // Generate secure reset token
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?")
                ->execute([$token, $expiresAt, $user['id']]);

            // Build reset URL
            $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $resetLink = $protocol . '://' . $_SERVER['HTTP_HOST']
                       . dirname($_SERVER['PHP_SELF'])
                       . '/reset_password.php?token=' . $token;

            // ── Send via PHPMailer + Gmail SMTP ──────────────────────
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USERNAME;
                $mail->Password   = MAIL_PASSWORD;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = MAIL_PORT;

                $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
                $mail->addAddress($user['email'], $user['full_name']);
                $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

                $mail->isHTML(true);
                $mail->Subject = 'AquaTrack – Password Reset Request';
                $mail->Body    = getEmailTemplate($user['full_name'], $resetLink);
                $mail->AltBody = "Hello {$user['full_name']},\n\n"
                               . "Reset your AquaTrack password using this link (valid 1 hour):\n"
                               . $resetLink . "\n\n"
                               . "If you didn't request this, please ignore this email.\n\n"
                               . "– The AquaTrack Team";

                $mail->send();
                auditLog($pdo, 'PASSWORD_RESET_REQUEST', 'Reset email sent to: ' . $email);

            } catch (Exception $e) {
                // Log error server-side only — never expose to user
                error_log("Mailer Error [{$email}]: " . $mail->ErrorInfo);
                auditLog($pdo, 'PASSWORD_RESET_FAILED', 'Mailer error for: ' . $email);
            }
        }
    }
}

/* ==============================
   HTML EMAIL TEMPLATE
============================== */
function getEmailTemplate(string $name, string $resetLink): string {
    $year = date('Y');
    $safeName = htmlspecialchars($name);
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head><meta charset="UTF-8"></head>
    <body style="margin:0;padding:0;background:#f0f9ff;font-family:Inter,-apple-system,sans-serif;">
      <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
        <tr><td align="center">
          <table width="100%" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">

            <!-- Header -->
            <tr>
              <td style="background:linear-gradient(135deg,#0ea5e9,#0284c7);padding:36px 40px;text-align:center;">
                <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:50%;display:inline-block;line-height:64px;margin-bottom:16px;font-size:32px;">
                  💧
                </div>
                <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">AquaTrack</h1>
                <p style="margin:6px 0 0;color:rgba(255,255,255,0.85);font-size:14px;">Password Reset Request</p>
              </td>
            </tr>

            <!-- Body -->
            <tr>
              <td style="padding:36px 40px;">
                <p style="margin:0 0 16px;color:#2d3748;font-size:15px;">Hello, <strong>{$safeName}</strong></p>
                <p style="margin:0 0 24px;color:#4a5568;font-size:14px;line-height:1.7;">
                  We received a request to reset your AquaTrack password. Click the button below to choose a new password. This link is valid for <strong>1 hour</strong>.
                </p>

                <!-- CTA Button -->
                <table width="100%" cellpadding="0" cellspacing="0">
                  <tr>
                    <td align="center" style="padding:8px 0 28px;">
                      <a href="{$resetLink}"
                         style="display:inline-block;background:linear-gradient(135deg,#0ea5e9,#0284c7);color:#ffffff;text-decoration:none;font-size:15px;font-weight:700;padding:14px 36px;border-radius:10px;letter-spacing:0.5px;">
                        Reset My Password
                      </a>
                    </td>
                  </tr>
                </table>

                <!-- Fallback link -->
                <p style="margin:0 0 8px;color:#718096;font-size:12px;">If the button doesn't work, copy and paste this link:</p>
                <p style="margin:0 0 24px;word-break:break-all;">
                  <a href="{$resetLink}" style="color:#0284c7;font-size:12px;">{$resetLink}</a>
                </p>

                <!-- Warning box -->
                <div style="background:#fff7ed;border:1.5px solid #fed7aa;border-radius:10px;padding:14px 16px;">
                  <p style="margin:0;color:#9a3412;font-size:13px;line-height:1.6;">
                    ⚠️ <strong>Didn't request this?</strong> You can safely ignore this email. Your password won't change unless you click the link above.
                  </p>
                </div>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td style="background:#f8fafc;padding:20px 40px;border-top:1px solid #e2e8f0;text-align:center;">
                <p style="margin:0;color:#a0aec0;font-size:12px;">
                  &copy; {$year} AquaTrack &nbsp;&middot;&nbsp; This is an automated message, please do not reply.
                </p>
              </td>
            </tr>

          </table>
        </td></tr>
      </table>
    </body>
    </html>
HTML;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password – AquaTrack</title>
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
        .form-control { border: 2px solid #e2e8f0; border-left: none; padding: 0.75rem 1rem; font-size: 1rem; transition: all 0.3s ease; }
        .form-control:focus { border-color: var(--primary-color); box-shadow: none; }
        .btn-login {
            background: var(--primary-gradient); color: white; font-weight: 700;
            padding: 0.875rem; border-radius: 12px; font-size: 1rem;
            letter-spacing: 0.5px; text-transform: uppercase; border: none;
            margin-top: 1.5rem; transition: all 0.3s ease; width: 100%;
        }
        .btn-login:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(2,132,199,0.4); color: white; }
        .btn-login:disabled { opacity: 0.7; cursor: not-allowed; }
        .btn-login .spinner-border { width: 1.2rem; height: 1.2rem; margin-right: 0.5rem; }
        .alert { border-radius: 12px; border: none; padding: 1rem; margin-bottom: 1.5rem; }
        .steps { background: #f0f9ff; border-radius: 12px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; border-left: 4px solid var(--primary-color); }
        .steps p { margin: 0; color: #4a5568; font-size: 0.875rem; line-height: 1.7; }
        .steps strong { color: var(--primary-color); }
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
        .back-link { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #edf2f7; }
        .back-link a {
            color: var(--primary-color); font-weight: 600; text-decoration: none;
            display: inline-flex; align-items: center; gap: 0.5rem; transition: gap 0.2s ease;
        }
        .back-link a:hover { gap: 0.75rem; }
    </style>
</head>
<body>
    <div class="bubbles">
        <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
    </div>

    <div class="login-container">
        <div class="login-card">

            <?php if ($emailSent): ?>
                <div class="success-state">
                    <div class="success-icon"><i class="bi bi-envelope-check"></i></div>
                    <h3>Check Your Email</h3>
                    <p>If an account exists for that email address, we've sent password reset instructions. Please check your inbox and spam folder.</p>
                    <p style="font-size:0.8rem;color:#a0aec0;margin-top:1rem;">The reset link expires in <strong>1 hour</strong>.</p>
                </div>
                <div class="back-link">
                    <a href="login.php"><i class="bi bi-arrow-left"></i> Back to Sign In</a>
                </div>

            <?php else: ?>
                <div class="login-header">
                    <div class="logo"><i class="bi bi-key"></i></div>
                    <h1>Forgot Password?</h1>
                    <p>Enter your email and we'll send you reset instructions</p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="steps">
                    <p>
                        <strong>Step 1:</strong> Enter your registered email below.<br>
                        <strong>Step 2:</strong> Check your inbox for a reset link.<br>
                        <strong>Step 3:</strong> Follow the link to create a new password.
                    </p>
                </div>

                <form method="POST" id="forgotForm" novalidate>
                    <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                    <div class="mb-4">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="name@example.com"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   required autocomplete="email" autofocus>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-login" id="submitBtn">
                        <span class="spinner-border spinner-border-sm d-none" id="spinner"></span>
                        <span id="btnText"><i class="bi bi-send me-2"></i>Send Reset Link</span>
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
        const form    = document.getElementById('forgotForm');
        const btn     = document.getElementById('submitBtn');
        const spinner = document.getElementById('spinner');
        const btnText = document.getElementById('btnText');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!document.getElementById('email').value.trim()) { e.preventDefault(); return; }
                spinner.classList.remove('d-none');
                btn.disabled = true;
                btnText.textContent = 'Sending...';
            });
        }
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', () => input.closest('.input-group').style.transform = 'scale(1.02)');
            input.addEventListener('blur',  () => input.closest('.input-group').style.transform = 'scale(1)');
        });
    </script>
</body>
</html>