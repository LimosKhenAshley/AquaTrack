<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$message = "";

/* =========================
   DELETE RATE
========================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM rates WHERE id=?");
    $stmt->execute([$id]);

    header("Location: rates.php");
    exit;
}

/* =========================
   UPDATE RATE
========================= */
if (isset($_POST['update_rate'])) {
    $id = $_POST['id'];
    $rate = $_POST['rate_per_unit'];

    $stmt = $pdo->prepare("UPDATE rates SET rate_per_unit=? WHERE id=?");
    $stmt->execute([$rate, $id]);

    header("Location: rates.php");
    exit;
}

/* =========================
   ADD RATE
========================= */
if (isset($_POST['add_rate'])) {
    $rate = $_POST['rate_per_unit'];

    $stmt = $pdo->prepare("
        INSERT INTO rates (rate_per_unit, effective_from)
        VALUES (?, NOW())
    ");
    $stmt->execute([$rate]);

    $message = "Rate added successfully!";
}

/* =========================
   FETCH RATES
========================= */
$rates = $pdo->query("
    SELECT * FROM rates
    ORDER BY effective_from DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rate Management - AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <h3 class="mb-3">Water Rates</h3>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!-- ================= ADD RATE ================= -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Add New Rate
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2">
                    <div class="col-md-10">
                        <input type="number" step="0.01" min="0"
                               name="rate_per_unit" class="form-control"
                               placeholder="Rate per cubic meter" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button name="add_rate" class="btn btn-success">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= RATES TABLE ================= -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered shadow-sm table-striped align-middle">
            <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Rate (₱)</th>
                <th>Effective From</th>
                <th width="260">Actions</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($rates as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>

                    <td>
                        <!-- EDIT FORM -->
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <input type="number" step="0.01" min="0"
                                   name="rate_per_unit"
                                   value="<?= $r['rate_per_unit'] ?>"
                                   class="form-control form-control-sm" required>

                            <button name="update_rate" class="btn btn-warning btn-sm">
                                Update
                            </button>
                        </form>
                    </td>

                    <td><?= date('M d, Y', strtotime($r['effective_from'])) ?></td>

                    <td>
                        <a href="rates.php?delete=<?= $r['id'] ?>"
                           onclick="return confirm('Delete this rate?')"
                           class="btn btn-danger btn-sm">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>

            </tbody>
        </table>
    </div>

</div>

</body>
</html>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>