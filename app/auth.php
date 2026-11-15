<?php
declare(strict_types=1);

/**
 * Session helpers used by the router before a protected page runs.
 *
 * Login and logout arrive in the next phase; board membership checks arrive with
 * the board UI. Until then a request is either a guest or the account owner.
 */

/**
 * Returns the id of the signed-in user.
 *
 * @return int|null Null for a guest.
 */
function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

/**
 * Sends guests to the login page with a flash message.
 *
 * @return void
 */
function require_login(): void
{
    if (current_user_id() === null) {
        flash_set('warning', 'Please sign in first.');
        redirect('login');
    }
}
