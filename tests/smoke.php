<?php
/**
 * Assert-based checks for the pure helpers. No test framework: `php tests/smoke.php`
 * exits non-zero on the first failing run so it can gate a commit.
 */

require __DIR__ . '/../app/helpers.php';
define('BASE_PATH', '');

$failures = 0;

function check(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? 'ok   ' : 'FAIL ') . $label . PHP_EOL;
    $failures += $ok ? 0 : 1;
}

// Base path detection: '' under `php -S -t public`, '/kanban' under XAMPP.
$_SERVER['SCRIPT_NAME'] = '/index.php';
check('base_path empty when served at the domain root', base_path() === '');

$_SERVER['SCRIPT_NAME'] = '/kanban/index.php';
check('base_path /kanban when served from htdocs/kanban', base_path() === '/kanban');

$_SERVER['SCRIPT_NAME'] = '/index.php';
check('url builds a root-relative path', url('login') === '/login');
check('url of the root is /', url('/') === '/');
check('url keeps asset paths', url('assets/app.js') === '/assets/app.js');

// Escaping.
check('e() escapes tags and quotes', e('<a href="x">&\'</a>') === '&lt;a href=&quot;x&quot;&gt;&amp;&#039;&lt;/a&gt;');
check('e() turns null into an empty string', e(null) === '');

// CSRF token: stable within a session, 64 hex chars.
check('csrf_token is stable', csrf_token() === csrf_token());
check('csrf_token is 32 random bytes', preg_match('/^[0-9a-f]{64}$/', csrf_token()) === 1);

// Theme: cookie drives the rendered data-bs-theme, unknown values fall back to system.
$_COOKIE = [];
check('theme defaults to system', theme_current() === 'system');

$_COOKIE['kanban_theme'] = 'dark';
check('theme reads the cookie', theme_current() === 'dark');

$_COOKIE['kanban_theme'] = 'neon';
check('theme rejects unknown values', theme_current() === 'system');

// Schema carries every table the plan promises.
$schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
$tables = [
    'users', 'remember_tokens', 'boards', 'board_members', 'lists', 'tasks',
    'task_members', 'labels', 'task_labels', 'task_items', 'attachments',
];
foreach ($tables as $table) {
    check("schema defines {$table}", str_contains($schema, "CREATE TABLE `{$table}`"));
}
check('users no longer stores a theme', !str_contains($schema, '`theme`'));
check('cards belong to a list, not a status enum', str_contains($schema, '`list_id`'));

echo PHP_EOL . ($failures === 0 ? 'all checks passed' : "{$failures} check(s) failed") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
