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

            // Insert user (identity)
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, address, email, phone, password, role_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $address, $email, $phone, $password, $customerRoleId]);
            $user_id = $pdo->lastInsertId();

            // Insert customer account
            $stmt2 = $pdo->prepare("
                INSERT INTO customers (user_id, area_id, meter_number)
                VALUES (?, ?, ?)
            ");
            $stmt2->execute([$user_id, $area_id, $meter_number]);

            $pdo->commit();
            $success = true;
            $message = "Customer registered successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error registering customer: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaTrack — Customer Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #198754, #0d6efd);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .register-card {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.2);
            width: 100%;
            max-width: 500px;
        }
        .register-card h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #198754;
        }
        .register-card .btn-success {
            background: #198754;
            border: none;
        }
        .register-card .btn-success:hover {
            background: #157347;
        }
        .register-footer {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="register-card">
    <h3>💧 AquaTrack Registration</h3>

    <?php if (!empty($message)): ?>
        <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?> text-center">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>


    <form method="POST">
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
        </div>

        <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2"
                    placeholder="House No., Street, Barangay, City" required></textarea>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
            <div id="emailFeedback" class="small mt-1"></div>
        </div>

        <div class="mb-3">
            <label>Phone Number</label>
            <input 
                type="tel"
                name="phone"
                id="phone"
                class="form-control"
                placeholder="+639XXXXXXXXX"
                required
            >
            <small class="text-muted">
                Format: +639XXXXXXXXX
            </small>
            <div id="phoneFeedback" class="small mt-1"></div>
        </div>

        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" id="password" class="form-control" required>
            <div class="progress mt-2" style="height: 6px;">
                <div id="strengthBar" class="progress-bar"></div>
            </div>
            <small id="strengthText" class="text-muted"></small>
        </div>

        <div class="mb-3">
            <label>Area</label>
            <select name="area_id" class="form-select" required>
                <option value="">Select Area</option>
                <?php foreach ($areas as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['area_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Meter Number</label>
            <input type="text" id="meter_number" name="meter_number" class="form-control" placeholder="Enter meter number" required>
            <div id="meterFeedback" class="small mt-1"></div>
        </div>

        <button type="submit" class="btn btn-success w-100" name="register">Register</button>
    </form>

    <div class="register-footer">
        <span>Already have an account? <a href="login.php" class="text-decoration-underline">Login here</a></span>
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
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Registration Successful</h5>
                </div>
                <div class="modal-body text-center">
                    <p>Your account has been created successfully.</p>
                    <p>You may now log in to AquaTrack.</p>
                </div>
                <div class="modal-footer">
                    <a href="login.php" class="btn btn-success w-100">Go to Login</a>
                    <small class="text-muted">Redirecting to login in 3 seconds...</small>
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
