<?php
require_once __DIR__ . '/includes/functions.php';

if (admin_is_authenticated()) {
    header('Location: admin.php');
    exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$errorMessage = null;
$expectedUsername = getenv('FOLENI_ADMIN_USER');
$expectedPassword = getenv('FOLENI_ADMIN_PASSWORD');
$credentialsConfigured = is_string($expectedUsername)
    && trim($expectedUsername) !== ''
    && is_string($expectedPassword)
    && $expectedPassword !== '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } elseif (!$credentialsConfigured) {
        $errorMessage = 'Business login is not configured. Set FOLENI_ADMIN_USER and FOLENI_ADMIN_PASSWORD on the server.';
    } elseif (!hash_equals((string) $expectedUsername, $username) || !hash_equals((string) $expectedPassword, $password)) {
        $errorMessage = 'The username or password is incorrect.';
    } else {
        session_regenerate_id(true);
        $_SESSION['foleni_admin'] = true;
        header('Location: admin.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#fbf8ef">
    <title>Business login | Foleni</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="subpage page-width login-page">
    <div class="page-intro"><p class="eyebrow">Sharp Cuts · Osu</p><h1>Welcome back.</h1></div>
    <form class="panel login-form" method="post" action="login.php">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label class="field-label" for="username">Username</label>
        <input class="text-input" id="username" name="username" type="text" autocomplete="username" required>
        <label class="field-label" for="password">Password</label>
        <input class="text-input" id="password" name="password" type="password" autocomplete="current-password" required>
        <?php if ($errorMessage !== null): ?><p class="inline-error" role="alert"><?= e($errorMessage) ?></p><?php endif; ?>
        <button class="button button-primary button-wide" type="submit">Sign in <span aria-hidden="true">→</span></button>
    </form>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/script.js" defer></script>
</body>
</html>