<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

$message = "";

/* =========================
   DELETE AREA
========================= */
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    $stmt = $pdo->prepare("DELETE FROM areas WHERE id=?");
    $stmt->execute([$id]);

    header("Location: areas.php");
    exit;
}

/* =========================
   UPDATE AREA
========================= */
if (isset($_POST['update_area'])) {
    $id = $_POST['id'];
    $name = $_POST['area_name'];

    $stmt = $pdo->prepare("UPDATE areas SET area_name=? WHERE id=?");
    $stmt->execute([$name, $id]);

    header("Location: areas.php");
    exit;
}

/* =========================
   ADD AREA
========================= */
if (isset($_POST['add_area'])) {
    $name = $_POST['area_name'];

    $stmt = $pdo->prepare("INSERT INTO areas (area_name) VALUES (?)");
    $stmt->execute([$name]);

    $message = "Area added successfully!";
}

/* =========================
   FETCH AREAS
========================= */
$areas = $pdo->query("SELECT * FROM areas ORDER BY id DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Area Management - AquaTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">

    <h3 class="mb-3">Service Areas</h3>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>

    <!-- ================= ADD AREA ================= -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            Add New Area
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2">
                    <div class="col-md-10">
                        <input type="text" name="area_name" class="form-control" placeholder="Area Name" required>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button name="add_area" class="btn btn-success">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= AREAS TABLE ================= -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered shadow-sm table-striped align-middle">
            <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Area Name</th>
                <th width="260">Actions</th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ($areas as $a): ?>
                <tr>
                    <td><?= $a['id'] ?></td>

                    <td>
                        <!-- EDIT FORM -->
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <input type="text" name="area_name"
                                   value="<?= htmlspecialchars($a['area_name']) ?>"
                                   class="form-control form-control-sm" required>

                            <button name="update_area" class="btn btn-warning btn-sm">Update</button>
                        </form>
                    </td>

                    <td>
                        <a href="areas.php?delete=<?= $a['id'] ?>"
                           onclick="return confirm('Delete this area?')"
                           class="btn btn-danger btn-sm">Delete
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