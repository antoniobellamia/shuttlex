<?php
include_once __DIR__ . '/auth.php';

if (!empty($_COOKIE['sx_remember'])) {
    $parts = explode(':', $_COOKIE['sx_remember'], 2);
    if (count($parts) === 2) {
        $stmt = $sxConn->prepare("DELETE FROM auth_tokens WHERE selector = ?");
        if ($stmt) {
            $stmt->bind_param("s", $parts[0]);
            $stmt->execute();
            $stmt->close();
        }
    }
    setcookie('sx_remember', '', time() - 3600, '/');
}

$_SESSION = [];
session_destroy();
header("Location: index.php");
exit;