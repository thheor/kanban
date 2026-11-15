<?php
/**
 * Session guard. Login/logout arrive in T5, ownership helpers in T7.
 */

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function require_login(): void
{
    if (current_user_id() === null) {
        flash_set('warning', 'Please sign in first.');
        redirect('login');
    }
}
