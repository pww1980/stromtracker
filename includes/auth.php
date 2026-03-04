<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
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

function isLoggedIn(): bool {
    startSecureSession();
    return !empty($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function login(string $password): bool {
    startSecureSession();
    $hash = getSetting('password_hash');
    if ($hash && password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        return true;
    }
    return false;
}

function logout(): void {
    startSecureSession();
    $_SESSION = [];
    session_destroy();
}

function isInstalled(): bool {
    try {
        $hash = getSetting('password_hash');
        return !empty($hash) && $hash !== '$2y$12$placeholder_replace_on_install';
    } catch (Exception $e) {
        return false;
    }
}
