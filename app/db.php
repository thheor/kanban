<?php
/**
 * PDO connection, created once per request.
 * Credentials live in app/config.php (gitignored, copied from config.example.php).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configFile = __DIR__ . '/config.php';
    if (!is_file($configFile)) {
        http_response_code(500);
        exit('Missing app/config.php — copy config.example.php and fill in your database details.');
    }

    $db = require $configFile;
    $db = $db['db'];

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $db['host'],
        $db['port'],
        $db['name']
    );

    try {
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Log the detail, show nothing that could leak credentials or host info.
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('Database unavailable. Check app/config.php and that MySQL is running.');
    }

    return $pdo;
}
