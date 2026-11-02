<?php

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function foleni_services(): array
{
    return [
        'Classic haircut' => ['price' => 45, 'minutes' => 30],
        'Haircut + beard' => ['price' => 65, 'minutes' => 45],
        'Kids haircut' => ['price' => 35, 'minutes' => 25],
        'Beard trim' => ['price' => 25, 'minutes' => 20],
    ];
}

function database_connection(): PDO
{
    static $connection;
    if (!$connection instanceof PDO) {
        require __DIR__ . '/../config/database.php';
        $connection = $pdo;
    }

    return $connection;
}

function get_active_queue(): array
{
    try {
        $statement = database_connection()->query(
            "SELECT id, customer_name, service, status, created_at
             FROM queue_entries
             WHERE status IN ('Serving', 'Waiting')
             ORDER BY CASE status WHEN 'Serving' THEN 0 ELSE 1 END, created_at, id"
        );
        return $statement->fetchAll();
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        throw new RuntimeException('The live queue is temporarily unavailable. Please try again later.', 0, $exception);
    }
}

function queue_position(int $entryId): ?array
{
    $queue = get_active_queue();
    foreach ($queue as $index => $entry) {
        if ((int) $entry['id'] === $entryId) {
            $entry['position'] = $index + 1;
            return $entry;
        }
    }

    return null;
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function admin_is_authenticated(): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return ($_SESSION['foleni_admin'] ?? false) === true;
}