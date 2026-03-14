<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$uid      = $_SESSION['user']['id'];
$perPage  = 10;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $perPage;

// Total count for pagination
$totalStmt = $pdo->prepare("
    SELECT COUNT(*) FROM notifications WHERE user_id = ?
");
$totalStmt->execute([$uid]);
$totalCount = (int)$totalStmt->fetchColumn();
$totalPages = (int)ceil($totalCount / $perPage);
$page       = min($page, max(1, $totalPages)); // clamp to valid range

// Fetch current page of notifications
$notesStmt = $pdo->prepare("
    SELECT *,
           SUM(is_read = 0) OVER () AS unread_total
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");
$notesStmt->bindValue(1, $uid,     PDO::PARAM_INT);
$notesStmt->bindValue(2, $perPage, PDO::PARAM_INT);
$notesStmt->bindValue(3, $offset,  PDO::PARAM_INT);
$notesStmt->execute();
$notifications = $notesStmt->fetchAll();

$unreadCount = $notifications[0]['unread_total'] ?? 0;
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3>🔔 Notifications</h3>
        <?php if ($unreadCount > 0): ?>
            <button class="btn btn-sm btn-outline-secondary" onclick="markAllRead()">
                Mark all as read
            </button>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <?php if ($totalCount === 0): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">You're all caught up!</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $n): ?>
                    <div class="border rounded p-3 mb-3 notification-item <?= $n['is_read'] ? '' : 'bg-light fw-bold' ?>"
                         data-id="<?= $n['id'] ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1">
                                <?php if (!$n['is_read']): ?>
                                    <span class="text-primary me-1">●</span>
                                <?php endif; ?>
                                <?= htmlspecialchars($n['title']) ?>
                            </h6>
                            <small class="text-muted text-nowrap ms-2">
                                <?= date('M j, g:i A', strtotime($n['created_at'])) ?>
                            </small>
                        </div>
                        <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                    </div>
                <?php endforeach; ?>

                <?php if ($totalPages > 1): ?>
                    <?php
                    $window   = 2; // pages to show on each side of current
                    $start    = max(1, $page - $window);
                    $end      = min($totalPages, $page + $window);
                    $baseUrl  = '?page=';
                    ?>
                    <nav aria-label="Notifications pagination">
                        <ul class="pagination pagination-sm justify-content-center mb-0 mt-3">

                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl . ($page - 1) ?>">‹ Prev</a>
                            </li>

                            <?php if ($start > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $baseUrl . 1 ?>">1</a>
                                </li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="<?= $baseUrl . $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end < $totalPages): ?>
                                <?php if ($end < $totalPages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= $baseUrl . $totalPages ?>"><?= $totalPages ?></a>
                                </li>
                            <?php endif; ?>

                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="<?= $baseUrl . ($page + 1) ?>">Next ›</a>
                            </li>

                        </ul>
                    </nav>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateBadge(delta) {
    const badge = document.querySelector('.nav-link span.badge');
    if (!badge) return;
    const count = parseInt(badge.textContent) + delta;
    count > 0 ? badge.textContent = count : badge.remove();
}

function markNotificationRead(el) {
    if (!el.classList.contains('fw-bold')) return;

    const id = el.dataset.id;

    fetch('/AquaTrack/modules/customer/ajax_mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            el.classList.remove('bg-light', 'fw-bold');
            el.querySelector('.text-primary')?.remove();
            updateBadge(-1);
        }
    })
    .catch(err => console.error('Failed to mark notification read:', err));
}

function markAllRead() {
    fetch('/AquaTrack/modules/customer/ajax_mark_notification_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mark_all: true })
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            document.querySelectorAll('.notification-item.fw-bold').forEach(el => {
                el.classList.remove('bg-light', 'fw-bold');
                el.querySelector('.text-primary')?.remove();
            });
            document.querySelector('.nav-link span.badge')?.remove();
            document.querySelector('[onclick="markAllRead()"]')?.remove();
        }
    })
    .catch(err => console.error('Failed to mark all notifications read:', err));
}

document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function () {
        markNotificationRead(this);
    });
});
</script>

<?php require_once '../../app/layouts/footer.php'; ?>