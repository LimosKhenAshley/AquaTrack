<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['admin']);

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
?>

<div class="container-fluid px-4 mt-4">

<h3>🗄 Database Backup & Restore</h3>

<div class="row mt-4">

<!-- Backup -->
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-body">

<h5>Create Backup</h5>
<p class="text-muted">
Download a full backup of the AquaTrack database.
</p>

<a href="backup_database.php" class="btn btn-success">
⬇ Download Backup
</a>

</div>
</div>
</div>


<!-- Restore -->
<div class="col-md-6">
<div class="card shadow-sm">
<div class="card-body">

<h5>Restore Backup</h5>
<p class="text-muted">
Upload a SQL backup file to restore the system database.
</p>

<form action="restore_database.php" method="POST" enctype="multipart/form-data">

<div class="mb-3">
<input type="file" name="backup_file" class="form-control" accept=".sql" required>
</div>

<button class="btn btn-danger"
onclick="return confirm('This will overwrite the current database. Continue?')">
Restore Database
</button>

</form>

</div>
</div>
</div>

</div>

</div>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>