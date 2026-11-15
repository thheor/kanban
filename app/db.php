<?php
declare(strict_types=1);

/**
 * mysqli connection and query helpers.
 *
 * Every statement is prepared; callers never interpolate values into SQL. The
 * connection is opened on first use and reused for the rest of the request.
 * Credentials live in app/config.php (gitignored, copied from config.example.php).
 */

/**
 * Returns the shared connection, opening it on first use.
 *
 * Failures are logged and answered with a generic 500, so host names and
 * credentials never reach the browser.
 *
 * @return mysqli
 */
function db(): mysqli
{
    static $connection = null;

    if ($connection instanceof mysqli) {
        return $connection;
    }

    $configFile = __DIR__ . '/config.php';
    if (!is_file($configFile)) {
        http_response_code(500);
        exit('Missing app/config.php — copy config.example.php and fill in your database details.');
    }

    $db = (require $configFile)['db'];

    // Throw on every driver error instead of returning false, so nothing is silently ignored.
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $connection = new mysqli(
            (string) $db['host'],
            (string) $db['user'],
            (string) $db['pass'],
            (string) $db['name'],
            (int) $db['port']
        );
        $connection->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $exception) {
        error_log('DB connection failed: ' . $exception->getMessage());
        http_response_code(500);
        exit('Database unavailable. Check app/config.php and that MySQL is running.');
    }

    return $connection;
}

/**
 * Prepares, binds and executes a statement.
 *
 * Bind types are inferred from the PHP values: int → i, float → d, everything
 * else (including null) → s.
 *
 * @param array<int, int|float|string|null> $params Positional bind values.
 * @return mysqli_stmt Executed statement, ready for get_result() or affected_rows.
 * @throws mysqli_sql_exception On any driver error.
 */
function db_stmt(string $sql, array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);

    if ($params !== []) {
        $types = '';
        foreach ($params as $value) {
            $types .= is_int($value) ? 'i' : (is_float($value) ? 'd' : 's');
        }
        $stmt->bind_param($types, ...array_values($params));
    }

    $stmt->execute();

    return $stmt;
}

/**
 * Runs a statement and returns every row as an associative array.
 *
 * @param array<int, int|float|string|null> $params Positional bind values.
 * @return array<int, array<string, mixed>>
 * @throws mysqli_sql_exception On any driver error.
 */
function db_all(string $sql, array $params = []): array
{
    $result = db_stmt($sql, $params)->get_result();

    return $result === false ? [] : $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Runs a statement and returns the first row, or null when there is none.
 *
 * @param array<int, int|float|string|null> $params Positional bind values.
 * @return array<string, mixed>|null
 * @throws mysqli_sql_exception On any driver error.
 */
function db_one(string $sql, array $params = []): ?array
{
    $result = db_stmt($sql, $params)->get_result();

    return $result === false ? null : ($result->fetch_assoc() ?: null);
}

/**
 * Runs an INSERT and returns the new row id.
 *
 * @param array<int, int|float|string|null> $params Positional bind values.
 * @throws mysqli_sql_exception On any driver error.
 */
function db_insert(string $sql, array $params = []): int
{
    db_stmt($sql, $params);

    return (int) db()->insert_id;
}

/**
 * Runs a statement and returns how many rows it changed.
 *
 * @param array<int, int|float|string|null> $params Positional bind values.
 * @throws mysqli_sql_exception On any driver error.
 */
function db_exec(string $sql, array $params = []): int
{
    return (int) db_stmt($sql, $params)->affected_rows;
}
