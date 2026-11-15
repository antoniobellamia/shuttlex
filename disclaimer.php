<?php
include_once 'database.php';
include_once 'auth.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Condizioni d'uso - ShuttleX</title>
    <?php include_once 'styles/header-include.php'; ?>
</head>
<body>
    <header class="app-header">
        <div class="container">
            <div class="topbar" style="margin-bottom: 0;">
                <a href="home.php" class="brand-logo">
                    <i class="fa-solid fa-bus-simple" aria-hidden="true"></i>
                    <span>SX <span class="sub">Condizioni d'uso</span></span>
                </a>
                <a href="home.php" class="btn btn-chip btn-sm">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Indietro
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="container">
        <?php include_once 'components/disclaimer-box.php'; ?>

        <div class="card" style="text-align: center;">
            <p style="margin: 0 0 12px; color: var(--text-muted); font-size: 0.92rem;">
                Hai bisogno di assistenza o vuoi richiedere le credenziali?
            </p>
            <a href="mailto:<?= htmlspecialchars($mailAssistenza) ?>?subject=Richiesta%20assistenza%20ShuttleX" class="btn btn-primary">
                <i class="fa-solid fa-envelope" aria-hidden="true"></i> Contatta l'Assistenza
            </a>
        </div>
    </main>
</body>
</html>