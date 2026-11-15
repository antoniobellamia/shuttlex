<?php
include_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Condizioni d'uso - ShuttleX</title>
    <?php include_once __DIR__ . '/styles/header-include.php'; ?>
</head>
<body>
    <header class="app-topbar">
        <div class="container topbar-inner">
            <a href="home.php" class="brand-logo">
                <span class="brand-badge"><i class="fa-solid fa-bus-simple" aria-hidden="true"></i> SX</span>
                <span>ShuttleX</span>
            </a>
            <span class="status-meta"><?= date('d/m/Y') ?></span>
        </div>
    </header>

    <main id="main-content" class="container">
        <section class="disclaimer-box" aria-labelledby="disclaimer-heading">
            <h2 id="disclaimer-heading">
                <i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Informazioni e Condizioni d'Uso
            </h2>
            <p style="margin: 0 0 8px;">
                Questo portale è un'iniziativa indipendente sviluppata a <strong>puro scopo informativo</strong> e senza fini di lucro per agevolare la consultazione degli orari della navetta. <strong>Non riveste carattere di ufficialità.</strong>
            </p>
            <ul class="disclaimer-list">
                <li>L'accesso agli orari è limitato tramite autenticazione per tutelare la riservatezza del servizio di trasporto privato.</li>
                <li>Le credenziali vengono rilasciate su richiesta all'assistenza.</li>
                <li>Gli orari, pur aggiornati regolarmente, potrebbero differire dalle disposizioni dell'amministrazione in tempo reale.</li>
            </ul>
        </section>

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