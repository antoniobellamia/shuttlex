<?php
include_once 'database.php';
include_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Disclaimer & Condizioni d'uso - ShuttleX</title>
    <?php include_once 'styles/header-include.php'; ?>
    <style>
        body, html { height: 100%; margin: 0; background-color: #f5f7fa; }
        .main-wrapper { display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .legal-card { background-color: white; max-width: 700px; width: 100%; box-shadow: 0 4px 15px rgba(0,0,0,0.08); border-top: 5px solid var(--primary-color); }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <div class="w3-card legal-card w3-round">
            <div class="w3-container w3-padding-large w3-border-bottom">
                <div class="w3-row">
                    <div class="w3-col s8">
                        <h2 style="margin:0; font-weight:bold; color:#333;">SX <span style="font-size:0.5em; color:#777; font-weight:normal;">/ Condizioni d'uso</span></h2>
                    </div>
                    <div class="w3-col s4 w3-right-align">
                        <span class="w3-tag w3-light-grey w3-round"><?= date('d/m/Y') ?></span>
                    </div>
                </div>
            </div>

            <div class="w3-container w3-padding-large">
                <h4 class="w3-text-dark-grey" style="margin-top:0;">
                    <i class="fa-solid fa-file-contract c-primary w3-margin-right"></i>
                    Termini del Servizio
                </h4>

                <?php include_once 'components/disclaimer-box.php'; ?>

                <div class="w3-center w3-padding-16 w3-light-grey w3-round w3-margin-top">
                    <p class="w3-small w3-text-grey" style="margin:0 0 10px 0;">Non possiedi le credenziali? Contatta l'assistenza:</p>
                    <a href="mailto:<?= htmlspecialchars($mailAssistenza) ?>?subject=Richiesta%20assistenza%20ShuttleX"
                       class="w3-button w3-white w3-border w3-border-blue w3-text-blue w3-hover-blue w3-round w3-small">
                        <i class="fa-solid fa-envelope"></i> Invia Richiesta
                    </a>
                </div>
            </div>

            <div class="w3-container w3-padding w3-center w3-border-top">
                <a href="home.php" class="w3-text-grey w3-hover-text-black" style="text-decoration:none; font-size:0.9em;">
                    <i class="fa-solid fa-arrow-left"></i> Torna alla Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>