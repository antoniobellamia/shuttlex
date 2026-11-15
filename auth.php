<?php
include_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Ripristina automaticamente la sessione dal cookie persistente se la sessione PHP è scaduta
if (empty($_SESSION['logged_in']) && !empty($_COOKIE['sx_remember'])) {
    $parts = explode(':', $_COOKIE['sx_remember'], 2);
    if (count($parts) === 2) {
        [$selector, $validator] = $parts;
        $stmt = $sxConn->prepare("
            SELECT T.id, T.user_id, T.validator_hash, T.expires_at, L.username, L.livello
            FROM auth_tokens T
            JOIN login L ON L.id = T.user_id
            WHERE T.selector = ? AND T.expires_at > NOW()
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("s", $selector);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row && hash_equals($row['validator_hash'], hash('sha256', $validator))) {
                session_regenerate_id(true);
                $_SESSION['logged_in'] = true;
                $_SESSION['user_id'] = (int)$row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['livello'] = (int)$row['livello'];
            } else {
                setcookie('sx_remember', '', time() - 3600, '/');
            }
        }
    }
}

function creaTokenPersistente(mysqli $conn, int $userId): void {
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $hash = hash('sha256', $validator);
    $scadenza = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);

    $conn->query("DELETE FROM auth_tokens WHERE expires_at <= NOW()");
    $stmt = $conn->prepare("INSERT INTO auth_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $userId, $selector, $hash, $scadenza);
        $stmt->execute();
        $stmt->close();

        setcookie('sx_remember', $selector . ':' . $validator, [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

function controllaRateLimit(mysqli $conn, string $ip): bool {
    $stmt = $conn->prepare("SELECT attempts, TIMESTAMPDIFF(SECOND, last_attempt, NOW()) AS diff FROM login_attempts WHERE ip_address = ?");
    if (!$stmt) return true;
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return true;
    if ((int)$row['diff'] > 900) { // Reset dopo 15 minuti
        $del = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $del->bind_param("s", $ip);
        $del->execute();
        $del->close();
        return true;
    }
    return (int)$row['attempts'] < 6;
}

function registraTentativoFallito(mysqli $conn, string $ip): void {
    $stmt = $conn->prepare("
        INSERT INTO login_attempts (ip_address, attempts, last_attempt)
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            attempts = IF(TIMESTAMPDIFF(SECOND, last_attempt, NOW()) > 900, 1, attempts + 1),
            last_attempt = NOW()
    ");
    if ($stmt) {
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $stmt->close();
    }
}

function resetTentativi(mysqli $conn, string $ip): void {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    if ($stmt) {
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $stmt->close();
    }
}

function requireLogin(): void {
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
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
        include __DIR__ . '/error.php';
        exit;
    }
}