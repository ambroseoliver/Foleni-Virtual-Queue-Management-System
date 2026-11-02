<?php
require_once __DIR__ . '/includes/functions.php';

if (!admin_is_authenticated()) {
    header('Location: login.php');
    exit;
}

$actionMessage = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$actionError = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $actionError = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') !== 'advance_queue') {
        $actionError = 'That queue action is not available.';
    } else {
        try {
            $connection = database_connection();
            $connection->beginTransaction();
            $activeStatement = $connection->query(
                "SELECT id, status FROM queue_entries
                 WHERE status IN ('Serving', 'Waiting')
                 ORDER BY CASE status WHEN 'Serving' THEN 0 ELSE 1 END, created_at, id
                 FOR UPDATE"
            );
            $activeEntries = $activeStatement->fetchAll();
            $currentServing = null;
            $nextWaiting = null;
            foreach ($activeEntries as $entry) {
                if ($entry['status'] === 'Serving' && $currentServing === null) {
                    $currentServing = $entry;
                } elseif ($entry['status'] === 'Waiting' && $nextWaiting === null) {
                    $nextWaiting = $entry;
                }
            }
            if ($currentServing !== null) {
                $complete = $connection->prepare("UPDATE queue_entries SET status = 'Completed' WHERE id = :id");
                $complete->execute(['id' => $currentServing['id']]);
            }
            if ($nextWaiting !== null) {
                $promote = $connection->prepare("UPDATE queue_entries SET status = 'Serving' WHERE id = :id");
                $promote->execute(['id' => $nextWaiting['id']]);
            }
            $connection->commit();
            $_SESSION['admin_flash'] = $currentServing !== null
                ? 'Current customer completed. The next person is now serving.'
                : ($nextWaiting !== null ? 'The next customer is now serving.' : 'There are no customers waiting.');
            header('Location: admin.php');
            exit;
        } catch (PDOException $exception) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log($exception->getMessage());
            $actionError = 'The queue could not be updated. Please try again.';
        } catch (RuntimeException $exception) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }
            $actionError = $exception->getMessage();
        }
    }
}

$queue = [];
$servedToday = 0;
$dashboardError = null;
try {
    $connection = database_connection();
    $queue = get_active_queue();
    $servedStatement = $connection->query(
        "SELECT COUNT(*) FROM queue_entries WHERE status = 'Completed' AND DATE(created_at) = CURRENT_DATE()"
    );
    $servedToday = (int) $servedStatement->fetchColumn();
} catch (RuntimeException $exception) {
    $dashboardError = $exception->getMessage();
}
$waitingCount = count(array_filter($queue, static fn(array $entry): bool => $entry['status'] === 'Waiting'));
$currentServing = null;
foreach ($queue as $entry) {
    if ($entry['status'] === 'Serving') {
        $currentServing = $entry;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fbf8ef">
    <title>Owner dashboard | Foleni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="subpage page-width dashboard-page">
    <div class="page-intro dashboard-intro">
        <p class="eyebrow">Owner dashboard</p>
        <h1>Keep the day moving.</h1>
    </div>
    <?php if ($actionMessage !== null): ?><p class="inline-notice" role="status"><?= e($actionMessage) ?></p><?php endif; ?>
    <?php if ($actionError !== null): ?><p class="inline-error" role="alert"><?= e($actionError) ?></p><?php endif; ?>
    <?php if ($dashboardError !== null): ?><p class="inline-error" role="alert"><?= e($dashboardError) ?></p><?php endif; ?>
    <div class="dashboard-stats">
        <article class="panel dashboard-stat"><span aria-hidden="true">♧</span><div><p>Waiting now</p><strong><?= $dashboardError === null ? e((string) $waitingCount) : '—' ?></strong></div></article>
        <article class="panel dashboard-stat"><span aria-hidden="true">✓</span><div><p>Served today</p><strong><?= $dashboardError === null ? e((string) $servedToday) : '—' ?></strong></div></article>
        <article class="panel dashboard-stat"><span aria-hidden="true">◷</span><div><p>Average wait</p><strong><?= $dashboardError === null ? e((string) ($waitingCount * 10)) . ' min' : '—' ?></strong></div></article>
    </div>
    <div class="dashboard-layout">
        <section class="panel manager-panel">
            <div class="manager-heading">
                <div><h2>Queue manager</h2><p><?= $dashboardError === null ? e((string) count($queue)) . ' active customers' : 'Queue data unavailable' ?></p></div>
                <form method="post" action="admin.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="action" value="advance_queue">
                    <button class="button button-primary" type="submit"><?= $currentServing !== null ? 'Complete current' : 'Serve next' ?></button>
                </form>
            </div>
            <?php if ($queue === []): ?>
                <div class="empty-state"><span aria-hidden="true">✳</span><p><?= $dashboardError === null ? 'No customers in the queue right now.' : 'Queue information is temporarily unavailable.' ?></p></div>
            <?php else: ?>
                <ol class="queue-list">
                    <?php foreach ($queue as $index => $entry): ?>
                        <li class="queue-row">
                            <span class="queue-number">#<?= e(str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                            <div class="queue-person"><strong><?= e($entry['customer_name']) ?></strong><span><?= e($entry['service']) ?></span></div>
                            <span class="queue-status <?= $entry['status'] === 'Serving' ? 'is-serving' : '' ?>"><?= e($entry['status']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
        <aside class="panel dashboard-note">
            <span class="note-icon" aria-hidden="true">✳</span>
            <h2><?= $waitingCount < 3 ? 'Good pace today.' : 'You’ve got this.' ?></h2>
            <p>Keep an eye on the queue and move customers along as each service is completed.</p>
            <div class="next-customer">
                <p>Next up</p>
                <?php if ($currentServing !== null): ?>
                    <strong><?= e($currentServing['customer_name']) ?></strong>
                    <span><?= e($currentServing['service']) ?></span>
                <?php elseif (isset($queue[0])): ?>
                    <strong><?= e($queue[0]['customer_name']) ?></strong>
                    <span><?= e($queue[0]['service']) ?></span>
                <?php else: ?>
                    <strong>Queue is clear</strong>
                    <span>Ready for the next customer</span>
                <?php endif; ?>
            </div>
        </aside>
    </div>
    <form class="logout-form" method="post" action="logout.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <button class="text-link" type="submit">Sign out</button>
    </form>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/script.js" defer></script>
</body>
</html>