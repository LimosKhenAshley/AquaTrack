<?php
require_once __DIR__ . '/../../app/middleware/auth.php';
checkRole(['staff']);

require_once __DIR__ . '/../../app/config/database.php';

/* =====================================================
   AJAX REQUEST (SEARCH + FILTER + PAGINATION)
=====================================================*/
if(isset($_GET['ajax'])){

    $search = $_GET['search'] ?? '';
    $method = $_GET['method'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $page = max(1, (int)($_GET['page'] ?? 1));

    $limit = 10;
    $offset = ($page - 1) * $limit;

    $where = [];
    $params = [];

    if($search){
        $where[] = "(u.full_name LIKE ? OR c.meter_number LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if($method){
        $where[] = "p.method = ?";
        $params[] = $method;
    }

    if($date_from){
        $where[] = "DATE(p.payment_date) >= ?";
        $params[] = $date_from;
    }

    if($date_to){
        $where[] = "DATE(p.payment_date) <= ?";
        $params[] = $date_to;
    }

    $whereSQL = $where ? "WHERE ".implode(" AND ", $where) : "";

    $sql = "
        SELECT 
            p.id AS payment_id,
            u.full_name,
            c.meter_number,
            p.amount_paid,
            p.method,
            p.payment_date
        FROM payments p
        JOIN bills b ON p.bill_id=b.id
        JOIN customers c ON b.customer_id=c.id
        JOIN users u ON c.user_id=u.id
        $whereSQL
        ORDER BY p.payment_date DESC
        LIMIT $limit OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /* TABLE OUTPUT */
    foreach($payments as $p){

        $badge = match($p['method']){
            'cash'=>'success',
            'online'=>'primary',
            'bank'=>'warning',
            default=>'secondary'
        };

        echo "<tr>
            <td>".date('M d, Y h:i A',strtotime($p['payment_date']))."</td>
            <td>".htmlspecialchars($p['full_name'])."</td>
            <td>".htmlspecialchars($p['meter_number'])."</td>
            <td>₱".number_format($p['amount_paid'],2)."</td>
            <td><span class='badge bg-$badge'>".ucfirst($p['method'])."</span></td>
            <td>
                <a target='_blank'
                   class='btn btn-secondary btn-sm'
                   href='../shared/receipt_pdf.php?payment_id=".urlencode($p['payment_id'])."'>
                   Receipt
                </a>
            </td>
        </tr>";
    }

    if(!$payments){
        echo "<tr><td colspan='6' class='text-center text-muted py-4'>
                No payment records found.
              </td></tr>";
    }

    exit;
}

/* =====================================================
   SUMMARY CARDS
=====================================================*/
$todayTotal = $pdo->query("
    SELECT COALESCE(SUM(amount_paid),0)
    FROM payments
    WHERE DATE(payment_date)=CURDATE()
")->fetchColumn();

$todayCount = $pdo->query("
    SELECT COUNT(*) FROM payments
    WHERE DATE(payment_date)=CURDATE()
")->fetchColumn();

require_once __DIR__ . '/../../app/layouts/main.php';
require_once __DIR__ . '/../../app/layouts/sidebar.php';
?>

<div class="container-fluid px-4 mt-4">

    <h3 class="mb-3">💰 Payment History</h3>

    <!-- SUMMARY -->
    <div class="row mb-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Today's Collection</h6>
                    <h4>₱<?= number_format($todayTotal,2) ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6>Transactions Today</h6>
                    <h4><?= $todayCount ?></h4>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <!-- FILTERS -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <input id="search" class="form-control"
                        placeholder="Search customer or meter...">
                </div>

                <div class="col-md-2">
                    <select id="method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="online">Online</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" id="date_from" class="form-control">
                </div>

                <div class="col-md-2">
                    <input type="date" id="date_to" class="form-control">
                </div>

                <div class="col-md-3 text-end">
                    <a href="export_payments.php" class="btn btn-success btn-sm">
                        Export Excel
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary btn-sm">
                        Print
                    </button>
                </div>
            </div>

            <!-- LOADING -->
            <div id="loading" class="text-center d-none mb-2">
                <div class="spinner-border text-primary"></div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Meter #</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th width="120">Receipt</th>
                        </tr>
                    </thead>
                    <tbody id="paymentTable"></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<script>
let page = 1;

function loadPayments(){
    document.getElementById('loading').classList.remove('d-none');

    const params = new URLSearchParams({
        ajax:1,
        search:document.getElementById('search').value,
        method:document.getElementById('method').value,
        date_from:document.getElementById('date_from').value,
        date_to:document.getElementById('date_to').value,
        page:page
    });

    fetch("?"+params.toString())
    .then(res=>res.text())
    .then(data=>{
        document.getElementById('paymentTable').innerHTML=data;
        document.getElementById('loading').classList.add('d-none');
    });
}

/* LIVE SEARCH */
['search','method','date_from','date_to']
.forEach(id=>{
    document.getElementById(id).addEventListener('input',()=>{
        page=1;
        loadPayments();
    });
});

loadPayments();
</script>

<?php require_once __DIR__ . '/../../app/layouts/footer.php'; ?>