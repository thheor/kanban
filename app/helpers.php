<?php
declare(strict_types=1);

/**
 * Request- and view-level helpers shared by every page.
 *
 * BASE_PATH is defined by public/index.php: '' under `php -S -t public`, and
 * '/kanban' when XAMPP serves htdocs/kanban. Nothing here touches the database.
 */

/**
 * Detects the directory the app is served from.
 *
 * @return string '' at the domain root, '/kanban' under htdocs/kanban.
 */
function base_path(): string
{
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));

    return ($dir === '/' || $dir === '.' || $dir === '') ? '' : rtrim($dir, '/');
}

/**
 * Builds a base-path-aware URL for a route or asset.
 *
 * @param string $path Path relative to the app root, with or without a leading slash.
 * @return string Absolute path starting at the app root.
 */
function url(string $path = '/'): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

/**
 * Sends a redirect and ends the request.
 *
 * @param string $path Route relative to the app root.
 * @return never
 */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/**
 * Escapes a value for HTML output.
 *
 * Every value that reaches a template goes through this. Accepts mixed input so
 * ids and integers need no cast at the call site.
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Queues a one-shot message for the next rendered page.
 *
 * @param string $type Bootstrap alert variant: primary, success, danger, warning or info.
 * @param string $message Text; escaped when the layout renders it.
 * @return void
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Takes the queued messages and clears the queue.
 *
 * @return array<int, array{type: string, message: string}>
 */
function flash_take(): array
{
    $flash = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flash;
}

/**
 * Returns the session CSRF token, creating it on first use.
 *
 * @return string 64 hex characters.
 */
function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

/**
 * Rejects a POST whose token does not match the session token.
 *
 * public/index.php calls this for every POST before any page runs, so no
 * endpoint can forget it. Expects the token in `csrf` or `X-CSRF-Token`.
 *
 * @return void
 */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(419);
        exit('CSRF token missing or invalid. Reload the page and try again.');
    }
}

/**
 * Resolves the theme the layout should render.
 *
 * Cookie only, no database: 'light', 'dark', or 'system' when unset or unrecognised.
 *
 * @return string
 */
function theme_current(): string
{
    $theme = $_COOKIE['kanban_theme'] ?? 'system';

    return in_array($theme, ['light', 'dark', 'system'], true) ? $theme : 'system';
}
