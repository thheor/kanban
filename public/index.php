<?php
declare(strict_types=1);

/**
 * Single entry point. Every request is routed here, so session setup, CSRF and
 * the layout cannot be forgotten by an individual page.
 *
 * Flow: helpers → session → route lookup → CSRF for POSTs → login guard →
 * render the page into $content → wrap it in the layout.
 */

require __DIR__ . '/../app/helpers.php';
require __DIR__ . '/../app/db.php';
define('BASE_PATH', base_path());

session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_name('kanban_session');
session_start();

require __DIR__ . '/../app/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rtrim('/' . trim(substr($uri, strlen(BASE_PATH)), '/'), '/') ?: '/';

/**
 * Route table: 'METHOD /path' => [page file in app/pages, requires login].
 */
$routes = [
    'GET /' => ['dashboard', true],
    'GET /login' => ['login', false],
    'GET /signup' => ['signup', false],
    'GET /dashboard' => ['dashboard', true],
];

// Runs before any page sees the request, so no POST endpoint can skip CSRF.
if ($method === 'POST') {
    csrf_check();
}

[$page, $needsLogin] = $routes[$method . ' ' . $path] ?? ['404', false];

if ($page === '404') {
    http_response_code(404);
} elseif ($needsLogin) {
    require_login();
}

$theme = theme_current();
$pageTitle = ucfirst($page);
ob_start();
require __DIR__ . '/../app/pages/' . $page . '.php';
$content = ob_get_clean();

require __DIR__ . '/../app/views/layout.php';
