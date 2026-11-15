<?php
date_default_timezone_set('Europe/Rome');

$isLocale = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1'], true);

if ($isLocale) {$sxHost = "localhost";
    $sxUser = "root";
    $sxPass = "";
    $sxName = "my_mels";
} else {
    $sxHost = "localhost";
    $sxUser = "mels";
    $sxPass = "";
    $sxName = "my_mels";
}

$mailAssistenza = "bellamiaantonio@protonmail.com";

mysqli_report(MYSQLI_REPORT_OFF);
$sxConn = new mysqli($sxHost,$sxUser, $sxPass,$sxName);

if ($sxConn->connect_error) {
    http_response_code(500);
    die("Errore di connessione al database.");
}

$sxConn->set_charset("utf8mb4");