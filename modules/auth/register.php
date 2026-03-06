<?php
require_once __DIR__ . '/../../app/config/database.php';

function normalizePHPhone($phone)
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '09')) {
            $phone = '63' . substr($phone, 1);
        }

        if (str_starts_with($phone, '639')) {
            $phone = '+' . $phone;
        }

        return $phone;
    }

$message = "";
$success = false;

// Fetch areas
$areas = $pdo->query("SELECT id, area_name FROM areas ORDER BY area_name")->fetchAll();

// Fetch customer role
$role = $pdo->prepare("SELECT id FROM roles WHERE role_name='customer'");
$role->execute();
$customerRoleId = $role->fetchColumn();


if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $raw_phone = $_POST['phone'];
    $phone = normalizePHPhone($raw_phone);
    $password_input = $_POST['password'];
    $area_id = $_POST['area_id'];
    $meter_number = trim($_POST['meter_number']);

    // Check if email already exists
    $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $emailCheck->execute([$email]);
    $emailExists = $emailCheck->fetchColumn();

    // Check if meter number already exists
    $meterCheck = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE meter_number = ?");
    $meterCheck->execute([$meter_number]);
    $meterExists = $meterCheck->fetchColumn();

    if ($emailExists) {
        $message = "Email is already registered.";
    } elseif ($meterExists) {
        $message = "Meter number already exists.";
    } elseif ($full_name === '' || $address === '' || $email === '' || $password_input === '' || $area_id === '' || $meter_number === '') {
        $message = "All fields are required.";
    } elseif (
        strlen($_POST['password']) < 8 ||
        !preg_match('/[A-Z]/', $_POST['password']) ||
        !preg_match('/[0-9]/', $_POST['password'])
    ) {
        $message = "Password must be at least 8 characters, include a number and uppercase letter.";
    }elseif (!preg_match('/^\+639\d{9}$/', $phone)) {
        $message = "Invalid Philippine mobile number format.";
    }else {
        $password = password_hash($password_input, PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, address, email, phone, password, role_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $address, $email, $phone, $password, $customerRoleId]);
            $user_id = $pdo->lastInsertId();

            // Insert customer
            $stmt2 = $pdo->prepare("
                INSERT INTO customers (user_id, area_id, meter_number)
                VALUES (?, ?, ?)
            ");
            $stmt2->execute([$user_id, $area_id, $meter_number]);

            // Insert contact preferences (MOVE IT HERE)
            $stmt3 = $pdo->prepare("
                INSERT INTO user_contact_preferences (user_id)
                VALUES (?)
            ");
            $stmt3->execute([$user_id]);

            $pdo->commit();

            $success = true;
            $message = "Customer registered successfully!";

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $message = "Error registering customer: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaTrack - Customer Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            --secondary-gradient: linear-gradient(135deg, #38bdf8 0%, #0ea5e9 100%);
            --primary-color: #0284c7;
            --water-dark: #0369a1;
        }

        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

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
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: rise 12s infinite ease-in;
        }

        @keyframes rise {
            0% { bottom: -100px; transform: translateX(0); }
            100% { bottom: 1200px; transform: translateX(-200px); }
        }

        .bubble:nth-child(1){ left:10%; width:80px;height:80px;}
        .bubble:nth-child(2){ left:30%; width:40px;height:40px;}
        .bubble:nth-child(3){ left:60%; width:100px;height:100px;}
        .bubble:nth-child(4){ left:80%; width:50px;height:50px;}

        .register-container {
            width: 100%;
            max-width: 520px;
            position: relative;
            z-index: 10;
        }

        .register-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp .5s ease-out;
        }

        @keyframes slideUp {
            from {opacity:0; transform:translateY(30px);}
            to {opacity:1; transform:translateY(0);}
        }

        .register-header {
            text-align:center;
            margin-bottom:2rem;
        }

        .logo {
            width:80px;
            height:80px;
            background: var(--primary-gradient);
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            margin:0 auto 1rem;
            box-shadow:0 10px 30px rgba(102,126,234,0.4);
        }

        .logo i {
            font-size:40px;
            color:white;
        }

        .form-label {
            font-weight:600;
            text-transform:uppercase;
            font-size:0.8rem;
            color:#4a5568;
        }

        .form-control, .input-group-text, .form-select {
            border:2px solid #e2e8f0;
            transition:0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow:none;
        }

        .input-group-text {
            background:transparent;
            border-right:none;
            color:#a0aec0;
        }

        .form-control {
            border-left:none;
        }

        .btn-register {
            background: var(--primary-gradient);
            color:white;
            font-weight:700;
            padding:0.9rem;
            border-radius:12px;
            text-transform:uppercase;
            border:none;
            margin-top:1rem;
            transition:0.3s;
        }

        .btn-register:hover {
            transform:translateY(-2px);
            box-shadow:0 10px 30px rgba(2,132,199,0.4);
        }

        .alert {
            border-radius:12px;
        }

        .login-link {
            text-align:center;
            margin-top:1.5rem;
            padding-top:1rem;
            border-top:2px solid #edf2f7;
        }

        .login-link a {
            color:var(--primary-color);
            font-weight:600;
            text-decoration:none;
        }

        .login-link a:hover {
            text-decoration:underline;
        }
    </style>
</head>
<body>

<div class="bubbles">
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
</div>

<div class="register-container">
    <div class="register-card">

        <div class="register-header">
            <div class="logo">
                <i class="bi bi-droplet"></i>
            </div>
            <h2>Create Account</h2>
            <p class="text-muted">Register to start using AquaTrack</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $success ? 'success' : 'danger' ?> alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST">

            <!-- Full Name -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text" name="full_name" class="form-control" placeholder="Juan Dela Cruz" required>
                </div>
            </div>

            <!-- Address -->
            <div class="mb-3">
                <label class="form-label">Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
                    <textarea name="address" class="form-control" rows="2" placeholder="House No., Street, Barangay, City" required ></textarea>
                </div>
            </div>

            <!-- Email -->
            <div class="mb-3">
                <label class="form-label">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="email" class="form-control" placeholder="juan.delacruz@example.com" required>
                </div>
                <div id="emailFeedback" class="small mt-1"></div>
            </div>

            <!-- Phone -->
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="+639123456789" required>
                </div>
                <div id="phoneFeedback" class="small mt-1"></div>
            </div>

            <!-- Password -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Create a strong password" required>
                </div>
                <div class="progress mt-2" style="height:6px;">
                    <div id="strengthBar" class="progress-bar"></div>
                </div>
                <small id="strengthText" class="text-muted"></small>
            </div>

            <!-- Area -->
            <div class="mb-3">
                <label class="form-label">Area</label>
                <select name="area_id" class="form-select" required>
                    <option value="">Select Area</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Meter -->
            <div class="mb-3">
                <label class="form-label">Meter Number</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-speedometer2"></i></span>
                    <input type="text" name="meter_number" id="meter_number" class="form-control" placeholder="Enter meter number" required>
                </div>
                <div id="meterFeedback" class="small mt-1"></div>
            </div>

            <button type="submit" name="register" class="btn-register w-100">
                Create Account
            </button>

            <div class="login-link">
                Already have an account?
                <a href="login.php">Sign In</a>
            </div>

        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const emailInput = document.getElementById('email');
        const emailFeedback = document.getElementById('emailFeedback');

        emailInput.addEventListener('blur', () => {
            if (!emailInput.value) return;

            fetch(`check_availability.php?email=${encodeURIComponent(emailInput.value)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'error') {
                        emailFeedback.innerHTML = data.message;
                        emailFeedback.className = 'text-danger';
                        emailInput.classList.add('is-invalid');
                    } else {
                        emailFeedback.innerHTML = 'Email is available';
                        emailFeedback.className = 'text-success';
                        emailInput.classList.remove('is-invalid');
                        emailInput.classList.add('is-valid');
                    }
                });
        });

        const meterInput = document.getElementById('meter_number');
        const meterFeedback = document.getElementById('meterFeedback');

        meterInput.addEventListener('blur', () => {
            if (!meterInput.value) return;

            fetch(`check_availability.php?meter_number=${encodeURIComponent(meterInput.value)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'error') {
                        meterFeedback.innerHTML = data.message;
                        meterFeedback.className = 'text-danger';
                        meterInput.classList.add('is-invalid');
                    } else {
                        meterFeedback.innerHTML = 'Meter number is available';
                        meterFeedback.className = 'text-success';
                        meterInput.classList.remove('is-invalid');
                        meterInput.classList.add('is-valid');
                    }
                });
        });
    </script>

    <script>
    const phoneInput = document.getElementById('phone');
    const phoneFeedback = document.getElementById('phoneFeedback');

    phoneInput.addEventListener('blur', function () {

        let v = this.value.trim();

        if (v === '') {
            phoneFeedback.textContent = '';
            this.classList.remove('is-invalid','is-valid');
            return;
        }

        v = v.replace(/\s+/g,'').replace(/-/g,'');

        if (v.startsWith('09')) v = '+63' + v.substring(1);
        else if (v.startsWith('639')) v = '+' + v;

        this.value = v;

        const phRegex = /^\+639\d{9}$/;

        if (!phRegex.test(v)) {
            phoneFeedback.textContent = 'Invalid PH number. Use +639XXXXXXXXX';
            phoneFeedback.className = 'text-danger small';
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        } else {
            phoneFeedback.textContent = 'Valid phone number';
            phoneFeedback.className = 'text-success small';
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        }
    });
    </script>

    <script>
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');

        passwordInput.addEventListener('input', () => {
            const val = passwordInput.value;
            let score = 0;

            if (val.length >= 8) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^A-Za-z0-9]/.test(val)) score++;

            let percent = (score / 4) * 100;
            strengthBar.style.width = percent + '%';

            strengthBar.className = 'progress-bar';

            if (score <= 1) {
                strengthBar.classList.add('bg-danger');
                strengthText.textContent = 'Weak password';
            } else if (score === 2) {
                strengthBar.classList.add('bg-warning');
                strengthText.textContent = 'Moderate password';
            } else if (score === 3) {
                strengthBar.classList.add('bg-info');
                strengthText.textContent = 'Strong password';
            } else {
                strengthBar.classList.add('bg-success');
                strengthText.textContent = 'Very strong password';
            }
        });
    </script>

<?php if ($success): ?>
<div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:20px; overflow:hidden;">

            <!-- Gradient Top Banner -->
            <div style="background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                        padding: 2rem;
                        text-align:center;
                        position:relative;">

                <div style="
                    width:90px;
                    height:90px;
                    background:rgba(255,255,255,0.15);
                    border-radius:50%;
                    display:flex;
                    align-items:center;
                    justify-content:center;
                    margin:0 auto;
                    backdrop-filter:blur(6px);
                ">
                    <i class="bi bi-check-lg text-white" style="font-size:45px;"></i>
                </div>

                <h4 class="text-white mt-3 mb-0 fw-bold">
                    Registration Successful
                </h4>
            </div>

            <!-- Modal Body -->
            <div class="modal-body text-center p-4">

                <p class="mb-2 fs-5 fw-semibold">
                    🎉 Your AquaTrack account has been created!
                </p>

                <p class="text-muted mb-4">
                    You can now log in and start managing your water services.
                </p>

                <a href="login.php" class="btn btn-lg w-100"
                   style="
                       background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
                       color:white;
                       font-weight:600;
                       border-radius:12px;
                       padding:0.75rem;
                       transition:0.3s;
                   "
                   onmouseover="this.style.transform='translateY(-2px)'"
                   onmouseout="this.style.transform='translateY(0)'"
                >
                    Go to Login
                </a>

                <small class="d-block text-muted mt-3">
                    Redirecting automatically in 3 seconds...
                </small>

            </div>
        </div>
    </div>
</div>

<script>
    const modal = new bootstrap.Modal(document.getElementById('successModal'));
    modal.show();

    setTimeout(() => {
        window.location.href = "login.php";
    }, 3000);
</script>
<?php endif; ?>

</body>
</html>
