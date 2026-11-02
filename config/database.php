<?php

$host = 'localhost';
$dbname = 'foleni_db';
$username = 'foleni_user';
$password = '1234';

try {

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {

    error_log($e->getMessage());

    throw new RuntimeException('The queue is temporarily unavailable. Please try again later.', 0, $e);

}