<?php
require_once __DIR__ . '/config.php';

function start_session(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function is_logged_in(): bool {
    start_session();
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /views/login.php');
        exit;
    }
}

function current_user_id(): int {
    start_session();
    return (int)($_SESSION['user_id'] ?? 0);
}

function login_user(int $user_id, string $username): void {
    start_session();
    session_regenerate_id(true);
    $_SESSION['user_id']  = $user_id;
    $_SESSION['username'] = $username;
}

function logout_user(): void {
    start_session();
    $_SESSION = [];
    session_destroy();
}