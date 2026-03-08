<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';

// Check admin role
checkRole(['admin']);

// Generate CSRF token
$_SESSION['csrf'] = bin2hex(random_bytes(32));

// Fetch backup history
$backups = $pdo->query("SELECT * FROM backup_logs ORDER BY created_at DESC")->fetchAll();
?>

<div class="container-fluid px-4 mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <h3>🗄 Database Backup & Restore</h3>
        </div>
    </div>

    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Database restored successfully
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Backup Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Create Backup</h5>
                    <p class="text-muted">Download full SQL + Excel backup</p>
                    
                    <a href="backup_database.php" class="btn btn-success" onclick="showLoader()">
                        <i class="fas fa-download me-2"></i>Create Backup
                    </a>
                    
                    <div id="backupLoader" style="display:none;" class="text-center mt-3">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Creating backup... please wait</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restore Section -->
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Restore Backup</h5>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        ⚠ Restoring will overwrite the entire database.
                    </div>

                    <form action="restore_database.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">

                        <div class="mb-3">
                            <label for="backupFile" class="form-label">Select Backup File</label>
                            <input type="file" name="backup_file" id="backupFile" class="form-control" accept=".sql" required>
                            <div id="backupPreview" class="mt-2"></div>
                        </div>

                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">Admin Password</label>
                            <input type="password" name="admin_password" id="adminPassword" class="form-control" placeholder="Confirm admin password" required>
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-undo me-2"></i>Restore Database
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Download Tables Section -->
    <div class="row mt-4">
        <div class="col-12">
            <h4 class="mb-3">Download Tables</h4>
            
            <div class="row">
                <?php
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tables as $table):
                ?>
                    <div class="col-md-3 mb-2">
                        <a href="download_table.php?table=<?= urlencode($table) ?>" 
                           class="btn btn-outline-primary w-100 text-truncate">
                            <i class="fas fa-table me-2"></i><?= htmlspecialchars($table) ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Backup History Section -->
    <?php if (!empty($backups)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h4 class="mb-3">Backup History</h4>
            
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>File Name</th>
                            <th>Size</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><?= htmlspecialchars($backup['file_name']) ?></td>
                            <td><?= formatFileSize((int)($backup['file_size'] ?? 0)) ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($backup['created_at'])) ?></td>
                            <td>
                                <a href="download_backup.php?id=<?= $backup['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Show loader function
function showLoader() {
    document.getElementById("backupLoader").style.display = "block";
}

// Backup file preview
document.getElementById("backupFile")?.addEventListener("change", function() {
    let file = this.files[0];
    
    if (!file) {
        document.getElementById("backupPreview").innerHTML = '';
        return;
    }
    
    let formData = new FormData();
    formData.append("backup_file", file);

    fetch("preview_backup.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            document.getElementById("backupPreview").innerHTML = `
                <div class="alert alert-danger mt-2">
                    <i class="fas fa-exclamation-circle me-2"></i>${data.error}
                </div>
            `;
        } else {
            document.getElementById("backupPreview").innerHTML = `
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Backup Preview:</strong><br>
                    Tables: ${data.tables || 0}<br>
                    Records: ${data.rows || 0}
                </div>
            `;
        }
    })
    .catch(error => {
        document.getElementById("backupPreview").innerHTML = `
            <div class="alert alert-danger mt-2">
                <i class="fas fa-exclamation-circle me-2"></i>Error previewing backup
            </div>
        `;
    });
});
</script>

<?php 
// Helper function for file size formatting
function formatFileSize($bytes)
{
    if (!is_numeric($bytes) || $bytes <= 0) {
        return '0 KB';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $power = floor(log($bytes, 1024));
    $power = min($power, count($units) - 1);

    $bytes /= pow(1024, $power);

    return number_format($bytes, 2) . ' ' . $units[$power];
}

require_once __DIR__ . '/../../app/layouts/footer.php'; 
?>