<?php
if (session_status() === PHP_SESSION_NONE) {
    $durata = 60 * 60 * 24 * 30; // 30 giorni
    ini_set('session.gc_maxlifetime', (string)$durata);
    session_set_cookie_params([
        'lifetime' => $durata,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function requireLogin(): void {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        header("Location: index.php?redirect=home");
        exit;
    }
}

function isAdmin(): bool {
    return isset($_SESSION['livello']) && (int)$_SESSION['livello'] === 0;
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        http_response_code(403);
        include 'error.php';
        exit;
    }
}