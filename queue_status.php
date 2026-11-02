<?php
require_once __DIR__ . '/includes/functions.php';

$queue = [];
$ticket = null;
$queueError = null;
try {
    $queue = get_active_queue();
    if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        foreach ($queue as $index => $entry) {
            if ((int) $entry['id'] === (int) $_GET['id']) {
                $ticket = $entry;
                $ticket['position'] = $index + 1;
                break;
            }
        }
    }
} catch (RuntimeException $exception) {
    $queueError = $exception->getMessage();
}
$serving = null;
foreach ($queue as $entry) {
    if ($entry['status'] === 'Serving') {
        $serving = $entry;
        break;
    }
}
$waitingCount = count(array_filter($queue, static fn(array $entry): bool => $entry['status'] === 'Waiting'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fbf8ef">
    <meta http-equiv="refresh" content="30">
    <title>Live queue | Foleni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="subpage page-width">
    <div class="page-intro">
        <p class="eyebrow"><span class="status-dot"></span> Updated just now</p>
        <h1>See who’s up next.</h1>
    </div>
    <?php if ($queueError !== null): ?>
        <p class="inline-error" role="alert"><?= e($queueError) ?></p>
    <?php elseif ($queueError === null && isset($_GET['id']) && $ticket === null): ?>
        <p class="inline-notice" role="status">That queue spot is no longer active. Check the current line below.</p>
    <?php endif; ?>
    <?php if ($ticket !== null): ?>
        <section class="ticket-card">
            <div><p class="eyebrow">Your place in line</p><strong class="ticket-number">#<?= e(str_pad((string) $ticket['position'], 2, '0', STR_PAD_LEFT)) ?></strong></div>
            <div><h2><?= e($ticket['customer_name']) ?></h2><p><?= e($ticket['service']) ?> · <?= e($ticket['status']) ?></p></div>
            <p class="ticket-wait"><?= $ticket['status'] === 'Serving' ? 'You’re up now' : 'About ' . max(0, ($ticket['position'] - 1) * 10) . ' min' ?></p>
        </section>
    <?php endif; ?>
    <div class="live-layout">
        <aside class="panel serving-panel">
            <p class="eyebrow">Now serving</p>
            <strong class="serving-number"><?= $serving !== null ? '#01' : '—' ?></strong>
            <h2><?= $queueError !== null ? 'Unavailable' : ($serving !== null ? e($serving['customer_name']) : 'Queue is open') ?></h2>
            <p class="serving-service"><?= $queueError !== null ? 'Please refresh shortly.' : ($serving !== null ? e($serving['service']) : 'Be the first to join today.') ?></p>
            <div class="summary-stat"><span aria-hidden="true">◷</span><div><strong><?= $queueError === null ? e((string) ($waitingCount * 10)) . ' min' : 'Unavailable' ?></strong><small>Estimated wait for new arrivals</small></div></div>
            <div class="summary-stat"><span aria-hidden="true">↗</span><div><strong><?= $queueError === null ? e((string) $waitingCount) . ' waiting' : 'Queue unavailable' ?></strong><small><?= $queueError === null ? 'Queue is moving normally' : 'Refresh shortly to try again' ?></small></div></div>
            <a class="button button-primary button-wide" href="join_queue.php">Join the queue <span aria-hidden="true">→</span></a>
        </aside>
        <section class="panel queue-list-panel">
            <div class="list-heading"><div><h2>Today’s queue</h2><p>Sharp Cuts · Osu</p></div><span class="live-label"><i></i> Live</span></div>
            <?php if ($queue === []): ?>
                <div class="empty-state"><span aria-hidden="true">✳</span><p><?= $queueError === null ? 'The queue is clear. Join whenever you’re ready.' : 'Queue information is temporarily unavailable.' ?></p></div>
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
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/script.js" defer></script>
</body>
</html>