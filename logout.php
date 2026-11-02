<?php
require_once __DIR__ . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
    http_response_code(400);
    exit('Your session expired. Return to the dashboard and try again.');
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
}
session_destroy();
header('Location: index.php');
exit;