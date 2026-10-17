<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

try {
    $conn = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );

    // READ COMMITTED narrows the gap-lock window InnoDB otherwise takes on
    // INSERT ... ON DUPLICATE KEY UPDATE, which cuts deadlocks when many
    // people submit at the same time.
    $conn->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
} catch (PDOException $e) {
    die("Database connection failed.");
}