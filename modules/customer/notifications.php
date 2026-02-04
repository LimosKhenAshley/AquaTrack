<?php
require_once '../../app/middleware/auth.php';
checkRole(['customer']);
require_once '../../app/config/database.php';
require_once '../../app/layouts/main.php';
require_once '../../app/layouts/sidebar.php';

$uid = $_SESSION['user']['id'];

// Fetch all notifications
$notesStmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$notesStmt->execute([$uid]);
$notifications = $notesStmt->fetchAll();

// Fetch unread count for badge
$unreadCountStmt = $pdo->prepare("
    SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0
");
$unreadCountStmt->execute([$uid]);
$unreadCount = $unreadCountStmt->fetchColumn();
?>

<div class="container mt-4">
    <h3>🔔 Notifications</h3>

    <div class="card shadow-sm mt-3">
        <div class="card-body">
            <?php if (count($notifications) === 0): ?>
                <p class="text-muted">No notifications.</p>
            <?php else: ?>
                <?php foreach($notifications as $n): ?>
                    <div class="border rounded p-3 mb-3 notification-item <?= $n['is_read'] ? '' : 'bg-light fw-bold' ?>"
                         data-id="<?= $n['id'] ?>">
                        <h6><?= htmlspecialchars($n['title']) ?></h6>
                        <p><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                        <small class="text-muted"><?= $n['created_at'] ?></small>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Handle marking notification as read
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function(){
        const id = this.dataset.id;
        const el = this;

        fetch('/AquaTrack/modules/customer/ajax_mark_notification_read.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id })
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success'){
                el.classList.remove('bg-light','fw-bold');
                // Update sidebar badge
                const badge = document.querySelector('.nav-link span.badge');
                if(badge){
                    const count = parseInt(badge.textContent) - 1;
                    if(count > 0){
                        badge.textContent = count;
                    } else {
                        badge.remove();
                    }
                }
            }
        });
    });
});
</script>

<?php require_once '../../app/layouts/footer.php'; ?>
