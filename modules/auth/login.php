<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/helpers/audit_helper.php';

auditLog($pdo, 'LOGIN', 'User logged in');


$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.address, u.email, u.password, r.role_name AS role
                           FROM users u
                           JOIN roles r ON u.role_id = r.id
                           WHERE u.email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'full_name' => $user['full_name'],
            'address' => $user['address'],
            'email' => $user['email'],
            'role' => $user['role']
        ];

        switch ($user['role']) {
            case 'admin':
                header("Location: ../admin/dashboard.php");
                break;
            case 'staff':
                header("Location: ../staff/dashboard.php");
                break;
            case 'customer':
                header("Location: ../customer/dashboard.php");
                break;
            case 'owner':
                header("Location: ../owner/dashboard.php");
                break;
            default:
                $message = "Invalid role.";
        }
        exit;
    } else {
        $message = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AquaTrack Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #0d6efd, #198754);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .login-card {
            background: #fff;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        .login-card h3 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #0d6efd;
        }
        .login-card .btn-primary {
            background: #0d6efd;
            border: none;
        }
        .login-card .btn-primary:hover {
            background: #0b5ed7;
        }
        .login-card a {
            text-decoration: none;
        }
        .login-footer {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h3>💧 AquaTrack Login</h3>

    <?php if($message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <div class="login-footer">
        <span>Don't have a customer account yet? <a href="register.php" class="text-decoration-underline">Register here</a></span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
