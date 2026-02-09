<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/../app/helpers/notify.php';

// Fetch penalty config
$cfg = $pdo->query("SELECT * FROM penalty_settings LIMIT 1")->fetch();

if ($cfg && $cfg['enabled']) {
    $rate = $cfg['monthly_rate'] / 100;
    $grace = (int)$cfg['grace_days'];
    $cap = $cfg['max_penalty_percent'] / 100;

    $pdo->exec("
        UPDATE bills
        SET penalty = LEAST(
            ROUND(
                amount * {$rate} *
                GREATEST(
                    TIMESTAMPDIFF(
                        MONTH,
                        DATE_ADD(due_date, INTERVAL {$grace} DAY),
                        CURDATE()
                    ),
                    0
                ),
                2
            ),
            amount * {$cap}
        )
        WHERE status = 'unpaid'
          AND due_date < CURDATE()
    ");

    $overdueUsers = $pdo->query("
        SELECT u.id uid, b.id bill_id
        FROM bills b
        JOIN customers c ON b.customer_id=c.id
        JOIN users u ON c.user_id=u.id
        WHERE b.status='unpaid'
        AND b.due_date < CURDATE()
        AND b.overdue_notified = 0
    ")->fetchAll();

    foreach($overdueUsers as $o){
        sendNotification(
            $pdo,
            $o['uid'],
            "Bill Overdue",
            "Your water bill #{$o['bill_id']} is overdue. Please pay to avoid disconnection.",
            "warning"
        );

        $pdo->prepare("UPDATE bills SET overdue_notified = 1 WHERE id=?")->execute([$o['bill_id']]);
    }
}
