<?php
require_once __DIR__ . '/includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$services = foleni_services();
$activeQueue = null;
$waitingNow = null;
try {
    $activeQueue = get_active_queue();
    $waitingNow = count($activeQueue);
} catch (RuntimeException $exception) {
    $queueError = $exception->getMessage();
}
$selectedService = isset($_GET['service']) && is_string($_GET['service']) && isset($services[$_GET['service']])
    ? $_GET['service']
    : '';
$customerName = '';
$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = trim((string) ($_POST['customer_name'] ?? ''));
    $selectedService = (string) ($_POST['service'] ?? '');
    $nameLength = preg_match_all('/./us', $customerName);
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session expired. Please refresh the page and try again.';
    } elseif ($customerName === '' || $nameLength === false || $nameLength > 100) {
        $errorMessage = 'Enter your name (up to 100 characters) to join the queue.';
    } elseif (!isset($services[$selectedService])) {
        $errorMessage = 'Choose one of the listed services to continue.';
    } else {
        try {
            $connection = database_connection();
            $statement = $connection->prepare(
                "INSERT INTO queue_entries (customer_name, service, status) VALUES (:customer_name, :service, 'Waiting')"
            );
            $statement->execute(['customer_name' => $customerName, 'service' => $selectedService]);
            header('Location: queue_status.php?id=' . (int) $connection->lastInsertId());
            exit;
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $errorMessage = 'We could not add you to the queue. Please try again in a moment.';
        } catch (RuntimeException $exception) {
            $errorMessage = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fbf8ef">
    <title>Join the queue | Foleni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="subpage page-width">
    <div class="page-intro">
        <p class="eyebrow">Join from anywhere</p>
        <h1>Get in line, not in a crowd.</h1>
    </div>
    <div class="join-layout">
        <form class="panel join-form" method="post" action="join_queue.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <fieldset class="service-picker">
                <legend>Choose your service</legend>
                <div class="choice-grid">
                    <?php foreach ($services as $name => $service): ?>
                        <label class="service-choice">
                            <input type="radio" name="service" value="<?= e($name) ?>" <?= $selectedService === $name ? 'checked' : '' ?> required>
                            <span class="choice-content">
                                <span class="choice-title"><?= e($name) ?><strong>₵<?= e((string) $service['price']) ?></strong></span>
                                <span class="choice-time"><?= e((string) $service['minutes']) ?> min</span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <label class="field-label" for="customer-name">Your name</label>
            <input class="text-input" id="customer-name" name="customer_name" type="text" maxlength="100" autocomplete="name" placeholder="e.g. Ama Mensah" value="<?= e($customerName) ?>" required>
            <p class="field-hint">We’ll only use this to identify you in today’s queue.</p>
            <?php if ($errorMessage !== null): ?><p class="inline-error" role="alert"><?= e($errorMessage) ?></p><?php endif; ?>
            <button class="button button-primary button-wide" type="submit">Join queue <span aria-hidden="true">→</span></button>
        </form>
        <aside class="panel queue-summary">
            <span class="live-label"><i></i> Live queue</span>
            <h2><?= $waitingNow === null ? 'Queue status unavailable.' : e((string) $waitingNow) . ' people are waiting.' ?></h2>
            <p class="summary-copy">You can leave after joining and follow your position online.</p>
            <?php if ($activeQueue !== null): ?>
                <div class="summary-stat"><span aria-hidden="true">◷</span><div><strong>About <?= e((string) ($waitingNow * 10)) ?> minutes</strong><small>Current estimated wait</small></div></div>
                <div class="summary-stat"><span aria-hidden="true">⌖</span><div><strong>Sharp Cuts, Osu</strong><small>14 Oxford Street, Accra</small></div></div>
                <p class="queue-count"><?= e((string) $waitingNow) ?> people waiting right now</p>
            <?php else: ?>
                <p class="inline-error" role="status"><?= e($queueError ?? 'The live queue is temporarily unavailable.') ?></p>
            <?php endif; ?>
            <a class="text-link" href="queue_status.php">View the live queue <span aria-hidden="true">→</span></a>
        </aside>
    </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/script.js" defer></script>
</body>
</html>