<?php
require_once __DIR__ . '/includes/functions.php';

$queue = [];
$queueError = null;
try {
    $queue = get_active_queue();
} catch (RuntimeException $exception) {
    $queueError = $exception->getMessage();
}

$waitingCount = count($queue);
$waitMinutes = count($queue) * 10;
$serving = count(array_filter($queue, static fn(array $entry): bool => $entry['status'] === 'Serving')) > 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fbf8ef">
    <meta name="description" content="Join your barber's queue from anywhere with Foleni. See the live wait and arrive when it is nearly your turn.">
    <title>Foleni | Less waiting. More living.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main>
    <section class="hero page-width">
        <div class="hero-copy">
            <p class="open-label"><span></span> Open today · 8:00 – 19:00</p>
            <h1>Your time belongs <span>to you.<i class="underline-swoosh" aria-hidden="true"></i></span></h1>
            <p class="hero-description">Join your barber’s queue from anywhere. We’ll hold your place, show you the wait, and let you know when it’s nearly your turn.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="join_queue.php">Join the queue <span aria-hidden="true">→</span></a>
                <a class="button button-secondary" href="queue_status.php"><span class="play-icon" aria-hidden="true">▶</span> View live queue</a>
            </div>
            <div class="hero-stats">
                <div><strong><?= $queueError === null ? $waitingCount : '—' ?></strong><span>waiting now</span></div>
                <div><strong>~10</strong><span>min per person</span></div>
            </div>
            <?php if ($queueError !== null): ?>
                <p class="inline-error" role="status"><?= e($queueError) ?></p>
            <?php endif; ?>
        </div>
        <div class="hero-visual">
            <img class="hero-photo" src="https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&amp;fit=crop&amp;w=1200&amp;q=85" alt="A barber carefully giving a customer a haircut">
            <div class="wait-card">
                <span class="wait-icon" aria-hidden="true">◷</span>
                <div><span>Current wait</span><strong><?= $queueError === null ? 'About ' . $waitMinutes . ' min' : 'Unavailable' ?></strong></div>
            </div>
            <span class="live-pill"><i></i> Live · <?= $queueError !== null ? 'unavailable' : ($serving ? '#01 serving' : 'Queue open') ?></span>
        </div>
    </section>

    <section class="how-section page-width">
        <div class="section-heading">
            <p class="eyebrow">How it works</p>
            <h2>Skip the wait,<br>not your turn.</h2>
        </div>
        <div class="steps-grid">
            <article class="step-card"><span>01</span><h3>Pick a service</h3><p>Choose what you need and see the estimated service time.</p></article>
            <article class="step-card"><span>02</span><h3>Join from anywhere</h3><p>Add your name and get a queue number instantly.</p></article>
            <article class="step-card"><span>03</span><h3>Arrive right on time</h3><p>Follow the live queue and return when you’re nearly up.</p></article>
        </div>
    </section>

    <section class="services-section page-width" id="services">
        <div class="section-heading services-heading">
            <p class="eyebrow">Today at Sharp Cuts</p>
            <h2>A great cut, without the long sit.</h2>
        </div>
        <div class="services-grid">
            <?php foreach (foleni_services() as $name => $service): ?>
                <a class="service-card" href="join_queue.php?service=<?= rawurlencode($name) ?>">
                    <span class="service-symbol" aria-hidden="true">✳</span>
                    <span class="service-price">₵<?= e((string) $service['price']) ?></span>
                    <h3><?= e($name) ?></h3>
                    <p><?= e((string) $service['minutes']) ?> min</p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/script.js" defer></script>
</body>
</html>
