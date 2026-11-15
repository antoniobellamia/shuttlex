<?php
include_once __DIR__ . '/auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>ShuttleX - Orari Navetta</title>
    <meta name="robots" content="noindex, nofollow">
    <?php include_once __DIR__ . '/styles/header-include.php'; ?>
</head>
<body>
    <a href="#main-content" class="skip-link">Vai agli orari</a>

    <!-- Topbar compatta sticky (52px) -->
    <header class="app-topbar" id="topAnchor">
        <div class="container topbar-inner">
            <a href="home.php" class="brand-logo" aria-label="ShuttleX Home">
                <span class="brand-badge"><i class="fa-solid fa-bus-simple" aria-hidden="true"></i> SX</span>
                <span>ShuttleX</span>
            </a>

            <div class="status-meta">
                <span id="liveClock" class="live-clock" aria-label="Ora attuale">--:--</span>
                <select id="selectGiorno" class="day-toggle feriale" aria-label="Giorno della settimana">
                    <option value="1">Lunedì</option>
                    <option value="2">Martedì</option>
                    <option value="4">Mercoledì</option>
                    <option value="8">Giovedì</option>
                    <option value="16">Venerdì</option>
                    <option value="32">Sabato</option>
                    <option value="64">Domenica</option>
                </select>
            </div>
        </div>
    </header>

    <!-- Pannello filtri nel flusso pagina (scorre via quando leggi le tratte) -->
    <section class="filter-panel" aria-label="Filtri orari">
        <div class="container">
            <div class="chip-row" role="group" aria-label="Filtri rapidi">
                <button type="button" id="btnAdesso" class="btn btn-chip is-active">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i> Prossime
                </button>
                <button type="button" id="btnDaCxc" class="btn btn-chip">
                    <i class="fa-solid fa-house" aria-hidden="true"></i> Da CampusX
                </button>
                <button type="button" id="btnVersoCxc" class="btn btn-chip">
                    <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i> Verso CampusX
                </button>
                <button type="button" id="btnTutte" class="btn btn-chip">
                    <i class="fa-solid fa-list" aria-hidden="true"></i> Tutto il giorno
                </button>
            </div>

            <form id="filterForm" role="search" onsubmit="event.preventDefault();">
                <div class="route-picker">
                    <div class="field-group partenza-box">
                        <label for="selectFermata" class="field-label">Partenza</label>
                        <select id="selectFermata" class="form-control">
                            <option value="">Tutte le fermate</option>
                        </select>
                    </div>

                    <div class="swap-box">
                        <button type="button" id="btnSwap" class="btn btn-swap" title="Inverti partenza e destinazione" aria-label="Inverti partenza e destinazione">
                            <i class="fa-solid fa-right-left" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="field-group destinazione-box">
                        <label for="selectDestinazione" class="field-label">Destinazione</label>
                        <select id="selectDestinazione" class="form-control">
                            <option value="">Qualsiasi arrivo</option>
                        </select>
                    </div>
                </div>

                <div class="search-bottom-row">
                    <div class="field-group">
                        <label for="inputOrario" class="field-label">Partenza dalle ore</label>
                        <input id="inputOrario" class="form-control" type="time">
                    </div>

                    <button type="button" id="btnReset" class="btn btn-secondary" title="Reimposta filtri e ora attuale">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset
                    </button>
                </div>
            </form>
        </div>
    </section>

    <div id="bannerArea" class="container" style="margin-top: 12px;"></div>

    <main id="main-content" class="container">
        <h1 class="sr-only">Orari Navetta CampusX</h1>
        <div id="tratteContainer" aria-live="polite">
            <div class="card empty-state">
                <i class="fa-solid fa-circle-notch fa-spin" aria-hidden="true"></i>
                <p style="margin: 0; color: var(--text-muted);">Caricamento orari in corso...</p>
            </div>
        </div>
    </main>

    <button type="button" id="fabFilter" class="fab-filter" aria-label="Torna ai filtri">
        <i class="fa-solid fa-sliders" aria-hidden="true"></i> Filtra
    </button>

    <footer class="app-footer">
        <div class="footer-inner">
            <div>
                Orari aggiornati al <strong id="footerUpdate">--/--/----</strong> &middot;
                Credits: <a href="https://www.cx-place.com/it/campus-cxbari.html" target="_blank" rel="noopener">CampusX Bari</a>
            </div>
            <nav class="footer-links" aria-label="Link di servizio">
                <?php if (isAdmin()): ?>
                    <a href="admin.php"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Pannello Admin</a>
                <?php endif; ?>
                <a href="mailto:<?= htmlspecialchars($mailAssistenza) ?>?subject=Richiesta%20assistenza%20ShuttleX">Assistenza</a>
                <a href="disclaimer.php">Condizioni d'uso</a>
                <a href="logout.php">Esci</a>
            </nav>
        </div>
    </footer>

    <script src="assets/app.js"></script>
</body>
</html>