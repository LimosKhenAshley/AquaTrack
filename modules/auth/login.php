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

$message = '';
$messageType = '';

/* ==============================
   HANDLE REMEMBER ME AUTO LOGIN
============================== */
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $stmt = $pdo->prepare("SELECT id, full_name, email, role_id FROM users WHERE remember_token = ?");
    $stmt->execute([$_COOKIE['remember_token']]);
    $user = $stmt->fetch();

    if ($user) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'        => $user['id'],
            'full_name' => $user['full_name'],
            'email'     => $user['email'],
            'role'      => getRoleName($pdo, $user['role_id'])
        ];
        header("Location: ../" . $_SESSION['user']['role'] . "/dashboard.php");
        exit;
    }
}

/* ==============================
   LOGIN PROCESS
============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* CSRF CHECK */
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        die("Invalid CSRF token.");
    }

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, u.email, u.password, 
               u.failed_attempts, u.locked_until, u.status,
               r.role_name AS role
        FROM users u
        JOIN roles r ON u.role_id = r.id
        WHERE u.email = :email
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {

        /* CHECK LOCK */
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $message = "Account locked. Please try again later.";
            $messageType = 'warning';
        }

        /* VERIFY PASSWORD */
        elseif (password_verify($password, $user['password'])) {

            /* CHECK STATUS */
            if ($user['status'] !== 'active') {
                $message = "Your account is inactive. Please contact administrator.";
                $messageType = 'danger';
            } else {

                /* RESET FAILED ATTEMPTS */
                $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id=?")
                    ->execute([$user['id']]);

                /* SECURE SESSION */
                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'        => $user['id'],
                    'full_name' => $user['full_name'],
                    'email'     => $user['email'],
                    'role'      => $user['role']
                ];

                /* REMEMBER ME */
                if (!empty($_POST['remember'])) {
                    $token = bin2hex(random_bytes(32));
                    $pdo->prepare("UPDATE users SET remember_token=? WHERE id=?")
                        ->execute([$token, $user['id']]);

                    setcookie("remember_token", $token, time() + (86400 * 30), "/", "", false, true);
                }

                /* UPDATE LAST LOGIN */
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id=?")
                    ->execute([$user['id']]);

                /* AUDIT SUCCESS */
                auditLog($pdo, 'LOGIN_SUCCESS', 'User logged in: ' . $user['email']);

                header("Location: ../" . $user['role'] . "/dashboard.php");
                exit;
            }

        } else {

            /* FAILED LOGIN */
            $failedAttempts = $user['failed_attempts'] + 1;
            $pdo->prepare("UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id=?")
                ->execute([$user['id']]);

            /* LOCK IF 5 ATTEMPTS */
            if ($failedAttempts >= 5) {
                $pdo->prepare("UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE id=?")
                    ->execute([$user['id']]);
                $message = "Too many failed attempts. Account locked for 10 minutes.";
                $messageType = 'warning';
            } else {
                $remainingAttempts = 5 - $failedAttempts;
                $message = "Invalid email or password. {$remainingAttempts} attempts remaining.";
                $messageType = 'danger';
            }

            auditLog($pdo, 'LOGIN_FAILED', 'Failed login attempt: ' . $email);
        }

    } else {
        auditLog($pdo, 'LOGIN_FAILED', 'Unknown email: ' . $email);
        $message = "Invalid email or password.";
        $messageType = 'danger';
    }
}

/* ==============================
   HELPER FUNCTION
============================== */
function getRoleName($pdo, $role_id) {
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id=?");
    $stmt->execute([$role_id]);
    return $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaTrack - Secure Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background bubbles */
        .bubbles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            top: 0;
            left: 0;
            pointer-events: none;
        }
        
        .bubble {
            position: absolute;
            bottom: -100px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: rise 10s infinite ease-in;
        }
        
        @keyframes rise {
            0% {
                bottom: -100px;
                transform: translateX(0);
            }
            50% {
                transform: translateX(100px);
            }
            100% {
                bottom: 1080px;
                transform: translateX(-200px);
            }
        }
        
        .bubble:nth-child(1) {
            left: 10%;
            width: 80px;
            height: 80px;
            animation-duration: 8s;
        }
        
        .bubble:nth-child(2) {
            left: 20%;
            width: 40px;
            height: 40px;
            animation-duration: 12s;
            animation-delay: 2s;
        }
        
        .bubble:nth-child(3) {
            left: 35%;
            width: 120px;
            height: 120px;
            animation-duration: 16s;
            animation-delay: 4s;
        }
        
        .bubble:nth-child(4) {
            left: 50%;
            width: 60px;
            height: 60px;
            animation-duration: 10s;
            animation-delay: 1s;
        }
        
        .bubble:nth-child(5) {
            left: 70%;
            width: 100px;
            height: 100px;
            animation-duration: 14s;
            animation-delay: 3s;
        }
        
        .bubble:nth-child(6) {
            left: 85%;
            width: 50px;
            height: 50px;
            animation-duration: 9s;
            animation-delay: 5s;
        }
        
        .login-container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.5s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header .logo {
            width: 80px;
            height: 80px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .login-header .logo i {
            font-size: 40px;
            color: white;
        }
        
        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #718096;
            font-size: 14px;
        }
        
        .form-label {
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
            color: #a0aec0;
        }
        
        .form-control, .input-group-text {
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-control {
            border-left: none;
            padding: 0.75rem 1rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
        }
        
        .form-control:focus + .input-group-text {
            border-color: #667eea;
        }
        
        .password-toggle {
            background: transparent;
            border: 2px solid #e2e8f0;
            border-left: none;
            color: #a0aec0;
            cursor: pointer;
        }
        
        .password-toggle:hover {
            color: #667eea;
            background: #f7fafc;
        }
        
        .form-check {
            margin-top: 1.5rem;
        }
        
        .form-check-input {
            border: 2px solid #e2e8f0;
        }
        
        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .btn-login {
            background: var(--primary-gradient);
            color: white;
            font-weight: 700;
            padding: 0.875rem;
            border-radius: 12px;
            font-size: 1rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border: none;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-login .spinner-border {
            width: 1.2rem;
            height: 1.2rem;
            margin-right: 0.5rem;
        }
        
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .footer-links {
            text-align: center;
            margin-top: 2rem;
        }
        
        .footer-links a {
            color: #718096;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .footer-links a:hover {
            color: #667eea;
        }
        
        .footer-links .divider {
            color: #e2e8f0;
            margin: 0 1rem;
        }
        
        .register-prompt {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 2px solid #edf2f7;
            color: #718096;
        }
        
        .register-prompt a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
            margin-left: 0.5rem;
        }
        
        .register-prompt a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <!-- Animated background bubbles -->
    <div class="bubbles">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">
                    <i class="bi bi-droplet"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Sign in to continue to AquaTrack</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?: 'danger' ?> alert-dismissible fade show" role="alert">
                    <i class="bi bi-<?= $messageType === 'warning' ? 'exclamation-triangle' : 'info-circle' ?> me-2"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm" novalidate>
                <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

                <div class="mb-4">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="name@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                               required autocomplete="email" autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button class="btn password-toggle" type="button" id="togglePassword">
                            <i class="bi bi-eye-slash" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                               <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="remember">
                            Remember me
                        </label>
                    </div>
                    <a href="forgot_password.php" class="text-decoration-none">
                        <i class="bi bi-question-circle"></i> Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn-login w-100" id="loginBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="spinner"></span>
                    <span id="btnText">Sign In</span>
                </button>

                <div class="footer-links">
                    <a href="#" data-bs-toggle="tooltip" title="Get help">
                        <i class="bi bi-headset"></i> Need Help?
                    </a>
                    <span class="divider">|</span>
                    <a href="#" data-bs-toggle="tooltip" title="Privacy policy">
                        <i class="bi bi-shield-check"></i> Privacy
                    </a>
                </div>

                <div class="register-prompt">
                    Don't have an account?
                    <a href="register.php">
                        Create one now <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS for alert dismissal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password visibility toggle
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Toggle icon
            if (type === 'password') {
                toggleIcon.classList.remove('bi-eye');
                toggleIcon.classList.add('bi-eye-slash');
            } else {
                toggleIcon.classList.remove('bi-eye-slash');
                toggleIcon.classList.add('bi-eye');
            }
        });

        // Form submission with loading state
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const spinner = document.getElementById('spinner');
        const btnText = document.getElementById('btnText');

        loginForm.addEventListener('submit', function(e) {
            // Client-side validation
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            
            if (!email.value || !password.value) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            // Show loading state
            spinner.classList.remove('d-none');
            loginBtn.disabled = true;
            btnText.textContent = 'Signing in...';
        });

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            var alert = document.querySelector('.alert');
            if (alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);

        // Smooth focus effect on inputs
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.closest('.input-group').style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.closest('.input-group').style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>