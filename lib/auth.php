<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function space_start_admin_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $c = space_config();
    session_name((string)$c['session_name']);
    session_set_cookie_params([
        'lifetime'=>0,
        'path'=>'/',
        'secure'=>(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly'=>true,
        'samesite'=>'Strict',
    ]);
    session_start();
}

function space_admin_logged_in(): bool {
    space_start_admin_session();
    return !empty($_SESSION['space_ag_admin']);
}

function space_admin_login(string $password): bool {
    $c = space_config();
    if (!$c['admin_password_hash'] || !password_verify($password, (string)$c['admin_password_hash'])) return false;
    space_start_admin_session();
    session_regenerate_id(true);
    $_SESSION['space_ag_admin'] = true;
    $_SESSION['space_ag_csrf'] = bin2hex(random_bytes(24));
    return true;
}

function space_admin_logout(): void {
    space_start_admin_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function space_csrf_token(): string {
    space_start_admin_session();
    if (empty($_SESSION['space_ag_csrf'])) $_SESSION['space_ag_csrf'] = bin2hex(random_bytes(24));
    return (string)$_SESSION['space_ag_csrf'];
}

function space_require_admin_api(): void {
    if (!space_admin_logged_in()) {
        if (function_exists('space_error')) space_error('Nicht angemeldet.',403);
        http_response_code(403); exit;
    }
    $got = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!$got || !hash_equals(space_csrf_token(), (string)$got)) {
        if (function_exists('space_error')) space_error('Ungültiges CSRF-Token.',403);
        http_response_code(403); exit;
    }
}
