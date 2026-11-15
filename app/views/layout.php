<?php
declare(strict_types=1);

/**
 * Shared page shell: document head, nav, flash messages, content slot, footer.
 *
 * Expects $pageTitle, $theme and $content, all prepared by public/index.php.
 */
?>
<!doctype html>
<html lang="en"<?= $theme === 'system' ? '' : ' data-bs-theme="' . e($theme) . '"' ?>>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · Kanban</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<?php if ($theme === 'system'): ?>
<script>
// Apply the OS preference before paint, so there is no flash of the wrong theme.
if (matchMedia('(prefers-color-scheme: dark)').matches) {
    document.documentElement.dataset.bsTheme = 'dark';
}
</script>
<?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 bg-body">
<nav class="navbar navbar-expand bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(url('/')) ?>">Kanban</a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="<?= e(url('dashboard')) ?>">Dashboard</a></li>
        </ul>
        <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center"
                data-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path d="M8 15A7 7 0 1 0 8 1zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16"/>
            </svg>
        </button>
    </div>
</nav>

<?php foreach (flash_take() as $flash): ?>
    <div class="container mt-3">
        <div class="alert alert-<?= e($flash['type']) ?> py-2 mb-0" role="alert">
            <?= e($flash['message']) ?>
        </div>
    </div>
<?php endforeach; ?>

<main class="container py-4 flex-grow-1">
<?= $content ?>
</main>

<footer class="border-top py-3 mt-auto">
    <div class="container text-center small text-body-secondary">
        Kanban · <?= date('Y') ?> · MIT licensed
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(url('assets/app.js')) ?>"></script>
</body>
</html>
