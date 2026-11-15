<?php
/**
 * Shared helpers. BASE_PATH is defined by public/index.php: it is '' under
 * `php -S -t public` and '/kanban' when XAMPP serves htdocs/kanban.
 */

function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));

    return ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
}

function url(string $path = '/'): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_take(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flash;
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('CSRF token missing or invalid. Reload the page and try again.');
    }
}
