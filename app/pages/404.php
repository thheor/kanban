<?php
declare(strict_types=1);

/**
 * Fallback view for unknown routes. public/index.php sets the 404 status
 * before rendering this file.
 */
$pageTitle = 'Not found';
?>
<div class="py-5 text-center">
    <p class="display-6 mb-2">404</p>
    <p class="text-body-secondary mb-4">That page does not exist.</p>
    <a class="btn btn-primary" href="<?= e(url('/')) ?>">Back to the board</a>
</div>
