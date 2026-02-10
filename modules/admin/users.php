<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$message = "";

/* =========================
   DELETE USER
========================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // prevent deleting yourself (optional safety)
    if ($id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }

    header("Location: users.php");
    exit;
}

/* =========================
   UPDATE USER ROLE
========================= */
if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $role_id = $_POST['role_id'];

    $stmt = $pdo->prepare("UPDATE users SET role_id = ? WHERE id = ?");
    $stmt->execute([$role_id, $id]);

    header("Location: users.php");
    exit;
}

/* =========================
   ADD USER
========================= */
if (isset($_POST['add_user'])) {
    $full_name = trim($_POST['full_name']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role_id = (int)$_POST['role_id'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    if ($full_name === '' || $email === '' || empty($_POST['password'])) {
        $message = "All fields are required.";
    } else {
        try {
            $pdo->beginTransaction();

            // 🔹 Get role name dynamically
            $roleStmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = ?");
            $roleStmt->execute([$role_id]);
            $role_name = $roleStmt->fetchColumn();

            if (!$role_name) {
                throw new Exception("Invalid role.");
            }

            // 1️⃣ Insert user
            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, address, email, phone, password, role_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$full_name, $address, $email, $phone, $password, $role_id]);

            $user_id = $pdo->lastInsertId();

            // 2️⃣ Role-specific inserts
            switch ($role_name) {

                case 'staff':
                    $stmt = $pdo->prepare("
                        INSERT INTO staffs (user_id)
                        VALUES (?)
                    ");
                    $stmt->execute([$user_id]);
                    break;

                case 'customer':
                    $stmt = $pdo->prepare("
                        INSERT INTO customers (user_id, area_id, meter_number)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$user_id, $_POST['area_id'] ?? null, $_POST['meter_number'] ?? null]);
                    break;

                case 'admin':
                    $stmt = $pdo->prepare("
                        INSERT INTO admins (user_id)
                        VALUES (?)
                    ");
                    $stmt->execute([$user_id]);
                    break;

                case 'owner':
                    $stmt = $pdo->prepare("
                        INSERT INTO owners (user_id)
                        VALUES (?)
                    ");
                    $stmt->execute([$user_id]);
                    break;
            }

            $pdo->commit();
            $message = "User added successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error adding user: " . $e->getMessage();
        }
    }
}

/* =========================
   FETCH ROLES
========================= */
$roles = $pdo->query("SELECT * FROM roles")->fetchAll();

/* =========================
   PAGINATION SETTINGS
========================= */
$limit = 10; // users per page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* =========================
   TOTAL USERS COUNT
========================= */
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPages = ceil($totalUsers / $limit);

/* =========================
   FETCH USERS (PAGINATED)
========================= */
$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.email, u.phone, u.role_id, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    ORDER BY u.id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <h3 class="mb-3">User Management</h3>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!----------------- ADD USER BUTTON ----------------- -->
    <div class="mb-3">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            + Add New User
        </button>
    </div>
    <!-- ADD USER MODAL -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Add New User</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
                            </div>

                            <div class="col-md-6">
                                <input type="text" name="address" class="form-control" placeholder="Address" required>
                            </div>

                            <div class="col-md-6">
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>

                            <div class="col-md-6">
                                <input type="text" id="phone" name="phone" class="form-control"
                                    placeholder="Phone Number" required>
                            </div>

                            <div class="col-md-6">
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>

                            <div class="col-md-6">
                                <select name="role_id" id="roleSelect" class="form-select" required>
                                    <option value="">Select Role</option>
                                        <?php foreach ($roles as $r): ?>
                                            <option value="<?= $r['id'] ?>"
                                                    data-role="<?= $r['role_name'] ?>">
                                                    <?= ucfirst($r['role_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                </select>
                            </div>

                        </div>
                    </div>

                    <hr>

                    <!-- ================= CUSTOMER EXTRA ================= -->
                    <div id="customerFields" style="display:none;">
                        <h6 class="text-primary">Customer Details</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <select name="area_id" class="form-select">
                                    <option value="">Select Area</option>
                                        <?php
                                            $areas = $pdo->query("SELECT id, area_name FROM areas ORDER BY area_name")->fetchAll();
                                        foreach ($areas as $a):
                                        ?>
                                        <option value="<?= $a['id'] ?>">
                                            <?= htmlspecialchars($a['area_name']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <input type="text" name="meter_number" class="form-control"
                                placeholder="Meter Number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="submit" name="add_user" class="btn btn-success">
                        Add User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= USERS TABLE ================= -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered shadow-sm table-striped align-middle">
            <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th width="280">Actions</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= ucfirst($u['role_name']) ?></td>

                    <td>
                        <!-- UPDATE ROLE -->
                        <form method="POST" class="d-inline-flex gap-1">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">

                            <select name="role_id" class="form-select form-select-sm">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"
                                        <?= $r['id'] == $u['role_id'] ? 'selected' : '' ?>>
                                        <?= ucfirst($r['role_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button name="update_user" class="btn btn-warning btn-sm">
                                Update
                            </button>
                        </form>

                        <!-- DELETE -->
                        <?php if (!isset($_SESSION['user_id']) || $u['id'] != $_SESSION['user_id']): ?>
                            <a href="users.php?delete=<?= $u['id'] ?>"
                            onclick="return confirm('Delete this user?')"
                            class="btn btn-danger btn-sm ms-2">
                                Delete
                            </a>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>
    <nav>
        <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page-1 ?>">Previous</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page+1 ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('phone').addEventListener('blur', function () {

    if (!this.value.trim()) return; // prevent empty alert

    let v = this.value.replace(/\s+/g,'').replace(/-/g,'');

    if (v.startsWith('09')) {
        v = '+63' + v.substring(1);
    }
    else if (v.startsWith('9') && v.length === 10) {
        v = '+63' + v;
    }

    if (!v.startsWith('+63') || v.length !== 13) {
        alert("Enter a valid PH mobile number");
        this.focus();
        return;
    }

    this.value = v;
});
</script>

<script>
    const roleSelect = document.getElementById('roleSelect');
    const customerFields = document.getElementById('customerFields');

    roleSelect.addEventListener('change', function () {

        const roleName = this.options[this.selectedIndex]
                            .dataset.role;

        customerFields.style.display =
            roleName === 'customer' ? 'block' : 'none';

    });
</script>

</body>
</html>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>