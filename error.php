<?php
$codice = (int)($_SERVER['REDIRECT_STATUS'] ?? http_response_code() ?: 404);
if ($codice < 400) {
    $codice = 404;
}

$mappaErrori = [
    400 => ["Richiesta Errata (400)", "Il server non riesce a comprendere la richiesta.", "fa-triangle-exclamation"],
    401 => ["Non Autorizzato (401)", "È necessaria l'autenticazione per accedere a questa pagina.", "fa-user-lock"],
    403 => ["Accesso Negato (403)", "Non hai i permessi necessari per visualizzare questa risorsa.", "fa-ban"],
    404 => ["Pagina Non Trovata (404)", "La pagina che cerchi non esiste o è stata spostata.", "fa-map-location-dot"],
    500 => ["Errore del Server (500)", "Si è verificato un problema tecnico temporaneo.", "fa-server"],
];

[$titolo, $messaggio, $icona] = $mappaErrori[$codice] ?? ["Errore ({$codice})", "Si è verificato un problema imprevisto.", "fa-circle-exclamation"];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title><?= $codice ?> - Errore ShuttleX</title>
    <?php include_once 'styles/header-include.php'; ?>
</head>
<body>
    <header class="app-header">
        <div class="container">
            <div class="topbar" style="margin-bottom: 0;">
                <a href="home.php" class="brand-logo">
                    <i class="fa-solid fa-bus-simple" aria-hidden="true"></i>
                    <span>SX <span class="sub">ShuttleX</span></span>
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="container" style="max-width: 520px; padding-top: 32px;">
        <section class="card empty-state" role="alert">
            <i class="fa-solid <?= $icona ?>" aria-hidden="true"></i>
            <h1 style="margin: 0 0 8px; font-size: 1.4rem;"><?= htmlspecialchars($titolo) ?></h1>
            <p style="margin: 0 0 22px; color: var(--text-muted);"><?= htmlspecialchars($messaggio) ?></p>
            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                <button onclick="history.back()" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Indietro
                </button>
                <a href="home.php" class="btn btn-primary" style="flex: 0 1 auto;">
                    <i class="fa-solid fa-house" aria-hidden="true"></i> Vai alla Home
                </a>
            </div>
        </section>
    </main>
</body>
</html>