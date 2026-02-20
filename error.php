<?php
// Recupera il codice errore dal server (se non c'è, presume 404)
$codice = $_SERVER['REDIRECT_STATUS'] ?? 404;

// Configurazioni messaggi in base all'errore
$titolo = "Errore Sconosciuto";
$messaggio = "Si è verificato un problema imprevisto.";
$icona = "fa-circle-exclamation";
$colore = "w3-red"; // Colore base

switch ($codice) {
    case 400:
        $titolo = "Richiesta Errata (400)";
        $messaggio = "Il server non riesce a comprendere la richiesta a causa di una sintassi non valida.";
        $icona = "fa-triangle-exclamation";
        $colore = "w3-orange";
        break;
    case 401:
        $titolo = "Non Autorizzato (401)";
        $messaggio = "È necessaria l'autenticazione per accedere a questa risorsa.";
        $icona = "fa-user-lock";
        $colore = "w3-purple";
        break;
    case 403:
        $titolo = "Accesso Negato (403)";
        $messaggio = "Non hai i permessi necessari per visualizzare questa pagina o cartella.";
        $icona = "fa-ban";
        $colore = "w3-red";
        break;
    case 404:
        $titolo = "Pagina Non Trovata (404)";
        $messaggio = "Sembra che la pagina che cerchi non esista o sia stata spostata.";
        $icona = "fa-map-location-dot";
        $colore = "w3-blue";
        break;
    case 500:
        $titolo = "Errore Interno del Server (500)";
        $messaggio = "C'è un problema tecnico sui nostri server. Riprova più tardi.";
        $icona = "fa-server";
        $colore = "w3-deep-orange";
        break;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $codice; ?> - Errore ShuttleX</title>
    <?php include_once 'styles/header-include.php'; ?>
    <style>
        /* Flexbox per i pulsanti: li mantiene affiancati su desktop e li impila su mobile */
        .btn-container {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
        }
        .btn-container > * {
            flex: 1 1 200px; /* Base 200px: se lo schermo è troppo piccolo, vanno a capo */
        }
    </style>
</head>
<body class="w3-light-grey">

    <?php include_once 'header_login.php'; ?>

    <div class="w3-container w3-padding-48" style="max-width: 600px; margin: auto;">
        
        <div class="w3-card-4 w3-white w3-round-large w3-center w3-animate-top" style="overflow: hidden;">
            
            <header class="w3-container <?php echo $colore; ?> w3-padding-24">
                <h1 style="margin: 0; font-size: 3em;"><i class="fa-solid <?php echo $icona; ?>"></i></h1>
                <h2 style="margin: 10px 0 0 0;"><?php echo $titolo; ?></h2>
            </header>

            <div class="w3-container w3-padding-32">
                <p class="w3-large w3-text-dark-grey"><?php echo $messaggio; ?></p>
                <hr class="w3-border-grey" style="margin: 30px auto; width: 50%;">
                
                <div class="btn-container">
                    <button onclick="history.back()" class="w3-button w3-light-grey w3-hover-grey w3-round w3-large">
                        <i class="fa-solid fa-arrow-left"></i> Indietro
                    </button>
                    <a href="index.php" class="w3-button w3-blue w3-hover-blue-grey w3-round w3-large" style="text-decoration: none;">
                        <i class="fa-solid fa-house"></i> Vai alla Home
                    </a>
                </div>
            </div>

        </div>
    </div>

</body>
</html>