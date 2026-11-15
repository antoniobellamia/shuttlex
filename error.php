<?php
$codice = (int)($_SERVER['REDIRECT_STATUS'] ?? http_response_code() ?: 404);
if ($codice < 400) {
    $codice = 404;
}

$mappaErrori = [
    400 => ["Richiesta Errata (400)", "Il server non riesce a comprendere la richiesta a causa di una sintassi non valida.", "fa-triangle-exclamation", "w3-orange"],
    401 => ["Non Autorizzato (401)", "È necessaria l'autenticazione per accedere a questa risorsa.", "fa-user-lock", "w3-purple"],
    403 => ["Accesso Negato (403)", "Non hai i permessi necessari per visualizzare questa pagina.", "fa-ban", "w3-red"],
    404 => ["Pagina Non Trovata (404)", "Sembra che la pagina che cerchi non esista o sia stata spostata.", "fa-map-location-dot", "w3-blue"],
    500 => ["Errore Interno del Server (500)", "C'è un problema tecnico sul server. Riprova più tardi.", "fa-server", "w3-deep-orange"],
];

[$titolo, $messaggio, $icona, $colore] = $mappaErrori[$codice] ?? ["Errore ({$codice})", "Si è verificato un problema imprevisto.", "fa-circle-exclamation", "w3-red"];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $codice ?> - Errore ShuttleX</title>
    <?php include_once 'styles/header-include.php'; ?>
    <style>
        .btn-container { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .btn-container > * { flex: 1 1 200px; }
    </style>
</head>
<body class="w3-light-grey">
    <nav class="w3-white">
        <div class="pure-g" style="align-items: center;">
            <div class="pure-u-1-2">
                <a href="home.php" style="text-decoration: none;"><h2 class="c-margin-not" style="margin-top:0;">SX</h2></a>
            </div>
            <div class="pure-u-1-2" style="text-align: right;">
                <p class="c-margin-not" style="color: #666; margin-top:0;"><?= date('d/m/Y') ?></p>
            </div>
        </div>
    </nav>

    <div class="w3-container w3-padding-48" style="max-width: 600px; margin: auto;">
        <div class="w3-card-4 w3-white w3-round-large w3-center w3-animate-top" style="overflow: hidden;">
            <header class="w3-container <?= $colore ?> w3-padding-24">
                <h1 style="margin: 0; font-size: 3em;"><i class="fa-solid <?= $icona ?>"></i></h1>
                <h2 style="margin: 10px 0 0 0;"><?= htmlspecialchars($titolo) ?></h2>
            </header>
            <div class="w3-container w3-padding-32">
                <p class="w3-large w3-text-dark-grey"><?= htmlspecialchars($messaggio) ?></p>
                <hr class="w3-border-grey" style="margin: 30px auto; width: 50%;">
                <div class="btn-container">
                    <button onclick="history.back()" class="w3-button w3-light-grey w3-hover-grey w3-round w3-large">
                        <i class="fa-solid fa-arrow-left"></i> Indietro
                    </button>
                    <a href="home.php" class="w3-button w3-blue w3-hover-blue-grey w3-round w3-large" style="text-decoration: none;">
                        <i class="fa-solid fa-house"></i> Vai alla Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>