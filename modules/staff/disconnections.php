<?php
require_once __DIR__.'/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__.'/../../app/config/database.php';
require_once __DIR__.'/../../app/layouts/main.php';
require_once __DIR__.'/../../app/layouts/sidebar.php';

$userId = $_SESSION['user']['id'];

/* ================= CSRF ================= */
if(empty($_SESSION['csrf'])){
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf'];

/* ================= PAGINATION ================= */
$limit = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$like = "%$search%";

/* ================= FETCH ================= */
$stmt = $pdo->prepare("
    SELECT
        dr.id,
        dr.action,
        dr.reason,
        dr.status,
        dr.scheduled_date,
        dr.created_at,
        u.full_name,
        c.meter_number
    FROM disconnection_requests dr
    JOIN customers c ON dr.customer_id=c.id
    JOIN users u ON c.user_id=u.id
    WHERE dr.requested_by=?
    AND (u.full_name LIKE ? OR c.meter_number LIKE ?)
    ORDER BY dr.created_at DESC
    LIMIT $limit OFFSET $offset
");

$stmt->execute([$userId,$like,$like]);
$rows = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">

    <h3>🔌 Service Disconnection Panel</h3>

    <div class="row mb-3">
        <div class="col-md-4">
            <input id="searchInput"
                class="form-control"
                placeholder="Search customer or meter..."
                value="<?= htmlspecialchars($search) ?>">
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">

                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Customer</th>
                            <th>Meter</th>
                            <th>Action</th>
                            <th>Scheduled</th>
                            <th>Status</th>
                            <th width="220">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach($rows as $r): ?>
                            <tr id="row<?= $r['id'] ?>">

                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= htmlspecialchars($r['meter_number']) ?></td>

                            <td>
                                <span class="badge <?= $r['action']=='disconnect'
                                ? 'bg-danger':'bg-success' ?>">
                                <?= ucfirst($r['action']) ?>
                                </span>
                            </td>

                            <td><?= $r['scheduled_date'] ?></td>

                            <td class="statusCell">
                                <?php if($r['status']=='scheduled'): ?>
                                <span class="badge bg-warning">⏳ Scheduled</span>
                                <?php elseif($r['status']=='completed'): ?>
                                <span class="badge bg-success">✔ Completed</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">✖ Cancelled</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if($r['status']=='scheduled'): ?>

                                    <button class="btn btn-success btn-sm completeBtn"
                                    data-id="<?= $r['id'] ?>">
                                    ✔ Complete
                                    </button>

                                    <button class="btn btn-danger btn-sm cancelBtn"
                                    data-id="<?= $r['id'] ?>">
                                    ✖ Cancel
                                    </button>

                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const CSRF = "<?= $csrf ?>";

    /* ========= LIVE SEARCH ========= */
    document.getElementById('searchInput')
    .addEventListener('keyup', function(){

        clearTimeout(window.searchTimer);

        window.searchTimer=setTimeout(()=>{
        location='?search='+encodeURIComponent(this.value);
        },500);

    });

    /* ========= COMPLETE ========= */
    document.querySelectorAll('.completeBtn').forEach(btn=>{

    btn.onclick=()=>{

        Swal.fire({
        title:'Complete request?',
        icon:'question',
        showCancelButton:true,
        confirmButtonColor:'#198754'
    }).then(res=>{

    if(!res.isConfirmed) return;

        btn.disabled=true;
        btn.innerHTML='Processing...';

    fetch('ajax_complete_request.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id=${btn.dataset.id}&csrf=${CSRF}`
    })
    .then(r=>r.json())
    .then(d=>{

    Swal.fire(d.status,d.message,d.status);

    if(d.status==='success'){
        updateRow(btn.dataset.id,'completed');
    }
    });
    });
    };
    });

    /* ========= CANCEL ========= */
    document.querySelectorAll('.cancelBtn').forEach(btn=>{

    btn.onclick=()=>{

    Swal.fire({
        title:'Cancel request?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonColor:'#dc3545'
    }).then(res=>{

    if(!res.isConfirmed) return;

    fetch('ajax_cancel_request.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:`id=${btn.dataset.id}&csrf=${CSRF}`
    })
    .then(r=>r.json())
    .then(d=>{

    Swal.fire(d.status,d.message,d.status);

    if(d.status==='success'){
    updateRow(btn.dataset.id,'cancelled');
    }
    });
    });
    };
    });

    /* ========= UPDATE ROW ========= */
    function updateRow(id,status){

    const row=document.getElementById('row'+id);

    let badge='';

    if(status==='completed')
        badge='<span class="badge bg-success">✔ Completed</span>';

    if(status==='cancelled')
        badge='<span class="badge bg-secondary">✖ Cancelled</span>';

        row.querySelector('.statusCell').innerHTML=badge;
        row.querySelectorAll('button').forEach(b=>b.remove());
    }
</script>