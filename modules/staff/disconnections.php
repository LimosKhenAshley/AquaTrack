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

/* ================= FILTERS ================= */
$limit  = 10;
$page   = max(1, (int)($_GET['page']   ?? 1));
$offset = ($page - 1) * $limit;

$search     = trim($_GET['search']      ?? '');
$filterStatus = $_GET['status']         ?? '';
$filterAction = $_GET['action']         ?? '';
$dateFrom   = $_GET['date_from']        ?? '';
$dateTo     = $_GET['date_to']          ?? '';

/* ================= BUILD WHERE CLAUSE ================= */
$where  = ['dr.requested_by = :uid'];
$params = [':uid' => $userId];

if($search !== ''){
    $where[]          = '(u.full_name LIKE :search OR c.meter_number LIKE :search)';
    $params[':search'] = "%$search%";
}
if($filterStatus !== ''){
    $where[]           = 'dr.status = :status';
    $params[':status'] = $filterStatus;
}
if($filterAction !== ''){
    $where[]           = 'dr.action = :action';
    $params[':action'] = $filterAction;
}
if($dateFrom !== ''){
    $where[]             = 'dr.scheduled_date >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if($dateTo !== ''){
    $where[]           = 'dr.scheduled_date <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

/* ================= TOTAL COUNT (for pagination) ================= */
$countStmt = $pdo->prepare("
    SELECT COUNT(*) FROM disconnection_requests dr
    JOIN customers c ON dr.customer_id = c.id
    JOIN users    u ON c.user_id        = u.id
    $whereSQL
");
$countStmt->execute($params);
$totalRows  = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $limit));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $limit;

/* ================= FETCH ROWS ================= */
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
    JOIN customers c ON dr.customer_id = c.id
    JOIN users    u ON c.user_id        = u.id
    $whereSQL
    ORDER BY dr.created_at DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

/* ================= HELPER: preserve query params ================= */
function buildQuery(array $overrides = []): string {
    $base = [
        'search'    => $_GET['search']    ?? '',
        'status'    => $_GET['status']    ?? '',
        'action'    => $_GET['action']    ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'page'      => $_GET['page']      ?? 1,
    ];
    return '?' . http_build_query(array_merge($base, $overrides));
}
?>

<!-- ===== PAGE STYLES ===== -->
<style>
  @import url('https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;600&family=Sora:wght@400;500;600;700&display=swap');

  :root {
    --brand:      #0f62fe;
    --brand-dark: #0043ce;
    --danger:     #da1e28;
    --success:    #198038;
    --warn:       #b28600;
    --surface:    #ffffff;
    --surface-2:  #f4f4f4;
    --border:     #e0e0e0;
    --text:       #161616;
    --text-muted: #6f6f6f;
    --radius:     6px;
  }

  body { font-family: 'Sora', sans-serif; color: var(--text); }

  /* ---- panel header ---- */
  .dp-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 24px;
  }
  .dp-header .dp-icon {
    width: 44px; height: 44px;
    background: var(--brand);
    border-radius: var(--radius);
    display: grid; place-items: center;
    font-size: 22px;
    flex-shrink: 0;
  }
  .dp-header h3 {
    margin: 0; font-size: 1.35rem; font-weight: 700;
  }
  .dp-header small {
    display: block; color: var(--text-muted); font-size: .8rem;
  }

  /* ---- filter bar ---- */
  .filter-bar {
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
  }
  .filter-bar .fg { display: flex; flex-direction: column; gap: 4px; }
  .filter-bar label {
    font-size: .72rem; font-weight: 600;
    text-transform: uppercase; letter-spacing: .06em;
    color: var(--text-muted);
  }
  .filter-bar input,
  .filter-bar select {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 7px 10px;
    font-size: .875rem;
    font-family: inherit;
    background: var(--surface);
    color: var(--text);
    height: 38px;
  }
  .filter-bar input:focus,
  .filter-bar select:focus {
    outline: 2px solid var(--brand);
    outline-offset: -2px;
    border-color: var(--brand);
  }
  .filter-bar .fg--search input { width: 220px; }
  .filter-bar .fg--date  input  { width: 150px; }
  .filter-bar .fg--sel   select { width: 140px; }

  .btn-filter {
    height: 38px; padding: 0 16px;
    border: none; border-radius: var(--radius);
    font-family: inherit; font-size: .875rem; font-weight: 600;
    cursor: pointer; transition: background .15s;
  }
  .btn-filter.apply  { background: var(--brand); color: #fff; }
  .btn-filter.apply:hover { background: var(--brand-dark); }
  .btn-filter.reset  { background: var(--border); color: var(--text); }
  .btn-filter.reset:hover { background: #d0d0d0; }

  /* ---- table ---- */
  .dp-card {
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
  }
  .dp-card .table-responsive { overflow-x: auto; }

  table.dp-table {
    width: 100%; border-collapse: collapse;
    font-size: .875rem;
  }
  .dp-table thead th {
    background: #161616;
    color: #fff;
    padding: 12px 14px;
    font-weight: 600;
    font-size: .78rem;
    text-transform: uppercase;
    letter-spacing: .07em;
    white-space: nowrap;
  }
  .dp-table tbody tr {
    border-bottom: 1px solid var(--border);
    transition: background .1s;
  }
  .dp-table tbody tr:last-child { border-bottom: none; }
  .dp-table tbody tr:hover { background: #f0f4ff; }
  .dp-table td {
    padding: 11px 14px;
    vertical-align: middle;
  }

  /* mono meter number */
  .meter-no {
    font-family: 'IBM Plex Mono', monospace;
    font-size: .8rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 4px;
    padding: 2px 6px;
    white-space: nowrap;
  }

  /* badges */
  .dp-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 100px;
    font-size: .75rem; font-weight: 600; white-space: nowrap;
  }
  .dp-badge.disconnect { background: #fff1f1; color: var(--danger); border: 1px solid #f5c6c8; }
  .dp-badge.reconnect  { background: #defbe6; color: var(--success); border: 1px solid #a7f0ba; }
  .dp-badge.scheduled  { background: #fdf6dd; color: var(--warn);    border: 1px solid #f6d860; }
  .dp-badge.completed  { background: #defbe6; color: var(--success); border: 1px solid #a7f0ba; }
  .dp-badge.cancelled  { background: #f4f4f4; color: #525252;        border: 1px solid #c6c6c6; }

  /* action buttons */
  .act-btn {
    padding: 5px 12px; border: none; border-radius: var(--radius);
    font-family: inherit; font-size: .8rem; font-weight: 600;
    cursor: pointer; transition: opacity .15s;
  }
  .act-btn:hover { opacity: .85; }
  .act-btn:disabled { opacity: .5; cursor: not-allowed; }
  .act-btn.complete { background: var(--success); color: #fff; }
  .act-btn.cancel   { background: var(--danger);  color: #fff; }

  /* ---- pagination ---- */
  .dp-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 18px;
    background: var(--surface-2);
    border-top: 1px solid var(--border);
    font-size: .83rem;
  }
  .dp-pagination .pag-info { color: var(--text-muted); }

  .pag-btns { display: flex; gap: 4px; }
  .pag-btn {
    min-width: 34px; height: 34px;
    padding: 0 8px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--text);
    font-family: inherit; font-size: .83rem; font-weight: 500;
    text-decoration: none;
    display: inline-flex; align-items: center; justify-content: center;
    transition: background .12s, color .12s, border-color .12s;
  }
  .pag-btn:hover:not(.disabled):not(.active) {
    background: #e8f0ff; border-color: var(--brand); color: var(--brand);
  }
  .pag-btn.active {
    background: var(--brand); border-color: var(--brand); color: #fff;
  }
  .pag-btn.disabled {
    opacity: .4; pointer-events: none;
  }

  /* ---- empty state ---- */
  .dp-empty {
    text-align: center; padding: 56px 20px; color: var(--text-muted);
  }
  .dp-empty .dp-empty-icon { font-size: 2.5rem; margin-bottom: 10px; }
  .dp-empty p { margin: 0; font-size: .9rem; }

  /* ---- responsive ---- */
  @media (max-width: 768px) {
    .filter-bar { flex-direction: column; }
    .filter-bar .fg--search input,
    .filter-bar .fg--date  input,
    .filter-bar .fg--sel   select { width: 100%; }
.dp-table thead th:nth-child(4),
    .dp-table tbody td:nth-child(4) { display: none; } /* hide Scheduled on small screens */
    .pag-btns .pag-btn.ellipsis { display: none; }
  }
</style>

<div class="container-fluid px-4 mt-4 pb-5">

  <!-- Header -->
  <div class="dp-header">
    <div>
      <h3 class="mb-0 fw-bold">🔌Service Disconnection Panel</h3>
    </div>
  </div>

  <!-- Filter Bar -->
  <form method="GET" action="" id="filterForm">
    <div class="filter-bar">

      <!-- Search -->
      <div class="fg fg--search">
        <label for="search">Search</label>
        <input type="text" id="search" name="search"
          placeholder="Customer or meter…"
          value="<?= htmlspecialchars($search) ?>">
      </div>

      <!-- Status -->
      <div class="fg fg--sel">
        <label for="status">Status</label>
        <select name="status" id="status">
          <option value="">All Statuses</option>
          <?php foreach(['scheduled','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $filterStatus===$s?'selected':'' ?>>
              <?= ucfirst($s) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action -->
      <div class="fg fg--sel">
        <label for="action">Action</label>
        <select name="action" id="action">
          <option value="">All Actions</option>
          <option value="disconnect" <?= $filterAction==='disconnect'?'selected':'' ?>>Disconnect</option>
          <option value="reconnect"  <?= $filterAction==='reconnect' ?'selected':'' ?>>Reconnect</option>
        </select>
      </div>

      <!-- Date From -->
      <div class="fg fg--date">
        <label for="date_from">From</label>
        <input type="date" name="date_from" id="date_from"
          value="<?= htmlspecialchars($dateFrom) ?>">
      </div>

      <!-- Date To -->
      <div class="fg fg--date">
        <label for="date_to">To</label>
        <input type="date" name="date_to" id="date_to"
          value="<?= htmlspecialchars($dateTo) ?>">
      </div>

      <!-- Buttons -->
      <a href="?" class="btn-filter reset" style="display:inline-flex;align-items:center;text-decoration:none;">Reset</a>



    </div>
  </form>

  <!-- Table Card -->
  <div class="dp-card">
    <div class="table-responsive">
      <table class="dp-table" id="dpTable">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Meter</th>
            <th>Action</th>
            <th>Scheduled</th>
            <th>Status</th>
            <th width="190">Actions</th>
          </tr>
        </thead>
        <tbody>

          <?php if(empty($rows)): ?>
          <tr>
            <td colspan="6">
              <div class="dp-empty">
                <div class="dp-empty-icon">📭</div>
                <p>No requests found. Try adjusting your filters.</p>
              </div>
            </td>
          </tr>
          <?php endif; ?>

          <?php foreach($rows as $r): ?>
          <tr id="row<?= $r['id'] ?>">

            <td><?= htmlspecialchars($r['full_name']) ?></td>

            <td><span class="meter-no"><?= htmlspecialchars($r['meter_number']) ?></span></td>

            <td>
              <span class="dp-badge <?= $r['action'] === 'disconnect' ? 'disconnect' : 'reconnect' ?>">
                <?= $r['action'] === 'disconnect' ? '⛔' : '✅' ?>
                <?= ucfirst($r['action']) ?>
              </span>
            </td>

            <td><?= htmlspecialchars($r['scheduled_date'] ?? '—') ?></td>

            <td class="statusCell">
              <?php if($r['status'] === 'scheduled'): ?>
                <span class="dp-badge scheduled">⏳ Scheduled</span>
              <?php elseif($r['status'] === 'completed'): ?>
                <span class="dp-badge completed">✔ Completed</span>
              <?php else: ?>
                <span class="dp-badge cancelled">✖ Cancelled</span>
              <?php endif; ?>
            </td>

            <td>
              <?php if($r['status'] === 'scheduled'): ?>
                <button class="act-btn complete completeBtn" data-id="<?= $r['id'] ?>">✔ Complete</button>
                <button class="act-btn cancel  cancelBtn"   data-id="<?= $r['id'] ?>">✖ Cancel</button>
              <?php else: ?>
                <span style="color:var(--text-muted)">—</span>
              <?php endif; ?>
            </td>

          </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="dp-pagination">
      <span class="pag-info">
        Showing
        <strong><?= $totalRows === 0 ? 0 : $offset + 1 ?></strong>–<strong><?= min($offset + $limit, $totalRows) ?></strong>
        of <strong><?= $totalRows ?></strong> requests
      </span>

      <div class="pag-btns">

        <!-- Prev -->
        <a href="<?= buildQuery(['page' => $page - 1]) ?>"
           class="pag-btn <?= $page <= 1 ? 'disabled' : '' ?>">‹</a>

        <?php
        // Show page buttons with ellipsis
        $range = 2; // pages around current
        $pages = [];
        for($p = 1; $p <= $totalPages; $p++){
            if($p === 1 || $p === $totalPages ||
               ($p >= $page - $range && $p <= $page + $range)){
                $pages[] = $p;
            }
        }
        $prev = null;
        foreach($pages as $p):
            if($prev !== null && $p - $prev > 1): ?>
              <span class="pag-btn ellipsis disabled">…</span>
        <?php endif; ?>
            <a href="<?= buildQuery(['page' => $p]) ?>"
               class="pag-btn <?= $p === $page ? 'active' : '' ?>">
               <?= $p ?>
            </a>
        <?php  $prev = $p;
        endforeach; ?>

        <!-- Next -->
        <a href="<?= buildQuery(['page' => $page + 1]) ?>"
           class="pag-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">›</a>

      </div>
    </div>

  </div><!-- /dp-card -->

</div><!-- /container -->

<!-- ===== JS ===== -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const CSRF = "<?= $csrf ?>";

/* ========= LIVE SEARCH (debounced) ========= */
document.getElementById('search').addEventListener('keyup', function () {
  clearTimeout(window._st);
  window._st = setTimeout(() => fetchRows(), 500);
});

function fetchRows() {
  const params = new URLSearchParams({
    search:    document.getElementById('search').value,
    status:    document.getElementById('status').value,
    action:    document.getElementById('action').value,
    date_from: document.getElementById('date_from').value,
    date_to:   document.getElementById('date_to').value,
  });

  fetch('?' + params.toString())
    .then(r => r.text())
    .then(html => {
      const parser = new DOMParser();
      const doc    = parser.parseFromString(html, 'text/html');
      document.querySelector('#dpTable tbody').innerHTML =
        doc.querySelector('#dpTable tbody').innerHTML;
    });
}

/* ========= AUTO-SUBMIT selects & date inputs ========= */
['status', 'action', 'date_from', 'date_to'].forEach(id => {
  document.getElementById(id).addEventListener('change', () => fetchRows());
});

/* ========= COMPLETE ========= */
document.querySelectorAll('.completeBtn').forEach(btn => {
  btn.onclick = () => {
    Swal.fire({
      title: 'Mark as Completed?',
      text: 'This action cannot be undone.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#198038',
      confirmButtonText: 'Yes, complete it'
    }).then(res => {
      if(!res.isConfirmed) return;
      btn.disabled = true;
      btn.textContent = 'Processing…';

      fetch('ajax_complete_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${btn.dataset.id}&csrf=${CSRF}`
      })
      .then(r => r.text())          // ← read as text first
      .then(raw => {
        console.log('complete raw:', raw);   // ← check browser console
        const d = JSON.parse(raw);
        if(d.status === 'success'){
          updateRow(btn.dataset.id, 'completed');
          Swal.fire('Success', d.message, 'success');
        } else {
          btn.disabled = false;
          btn.textContent = '✔ Complete';
          Swal.fire('Error', d.message || 'Something went wrong.', 'error');
        }
      })
      .catch(err => {
        console.error('complete error:', err);
        btn.disabled = false;
        btn.textContent = '✔ Complete';
        Swal.fire('Error', 'Request failed. Check console.', 'error');
      });
    });
  };
});

/* ========= CANCEL ========= */
document.querySelectorAll('.cancelBtn').forEach(btn => {
  btn.onclick = () => {
    Swal.fire({
      title: 'Cancel this request?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#da1e28',
      confirmButtonText: 'Yes, cancel it'
    }).then(res => {
      if(!res.isConfirmed) return;
      btn.disabled = true;
      btn.textContent = 'Processing…';

      fetch('ajax_cancel_request.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `id=${btn.dataset.id}&csrf=${CSRF}`
      })
      .then(r => r.text())          // ← read as text first
      .then(raw => {
        console.log('cancel raw:', raw);     // ← check browser console
        const d = JSON.parse(raw);
        if(d.status === 'success'){
          updateRow(btn.dataset.id, 'cancelled');
          Swal.fire('Success', d.message, 'success');
        } else {
          btn.disabled = false;
          btn.textContent = '✖ Cancel';
          Swal.fire('Error', d.message || 'Something went wrong.', 'error');
        }
      })
      .catch(err => {
        console.error('cancel error:', err);
        btn.disabled = false;
        btn.textContent = '✖ Cancel';
        Swal.fire('Error', 'Request failed. Check console.', 'error');
      });
    });
  };
});

/* ========= UPDATE ROW ========= */
function updateRow(id, status){
  const row = document.getElementById('row' + id);
  if(!row) return;

  const badges = {
    completed: '<span class="dp-badge completed">✔ Completed</span>',
    cancelled:  '<span class="dp-badge cancelled">✖ Cancelled</span>'
  };

  row.querySelector('.statusCell').innerHTML = badges[status] || '';
  row.querySelector('td:last-child').innerHTML = '<span style="color:var(--text-muted)">—</span>';
}

</script>