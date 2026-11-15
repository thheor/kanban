<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · Kanban</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="d-flex flex-column min-vh-100 bg-body">
<nav class="navbar navbar-expand bg-body-tertiary border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= e(url('/')) ?>">Kanban</a>
        <ul class="navbar-nav me-auto">
            <li class="nav-item"><a class="nav-link" href="<?= e(url('dashboard')) ?>">Dashboard</a></li>
        </ul>
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
</body>
</html>
