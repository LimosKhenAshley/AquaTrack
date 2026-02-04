    </main>
</div> <!-- col-md-10 -->
</div> <!-- row -->
</div> <!-- container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function refreshNotificationBadge() {
    fetch('/AquaTrack/modules/customer/ajax_unread_notification_count.php')
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'success') return;

            const badge = document.getElementById('notifBadge');
            const count = data.count;

            if (count > 0) {
                if (badge) {
                    badge.textContent = count;
                } else {
                    // create badge if missing
                    const link = document.querySelector('a[href*="notifications.php"]');
                    if (!link) return;

                    const span = document.createElement('span');
                    span.id = 'notifBadge';
                    span.className = 'badge bg-danger ms-1';
                    span.textContent = count;
                    link.appendChild(span);
                }
            } else {
                if (badge) badge.remove();
            }
        });
}

// poll every 10 seconds
setInterval(refreshNotificationBadge, 10000);
</script>

</body>
</html>
