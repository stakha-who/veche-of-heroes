<?php
declare(strict_types=1);

function startSession(): void
{
    session_start([
        'use_strict_mode' => true,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'cookie_path' => '/',
    ]);
    header('Cache-Control: no-store');
}

function csrfToken(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf'];
}

function validCsrf(): bool
{
    return hash_equals(csrfToken(), postValue('csrf'));
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function requireLogin(): int
{
    $id = currentUserId();
    if ($id === null) {
        redirect('/login');
    }
    return $id;
}
