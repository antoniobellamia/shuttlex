<?php
include_once 'database.php';
include_once 'auth.php';

$tipoGiorno = isset($tipoGiorno) ? (int)$tipoGiorno : (isset($_GET['fest']) ? (int)$_GET['fest'] : (date('N') < 6 ? 0 : 1));
$oraInput = isset($_GET['orario']) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $_GET['orario'])
    ? substr($_GET['orario'], 0, 5)
    : date('H:i');
$fermata = $_GET['fermata'] ?? '';
$destinazione = $_GET['destinazione'] ?? '';

$luoghi = [];
$luoghiResult = $sxConn->query("SELECT id, denominazione FROM luogo ORDER BY denominazione ASC");
if ($luoghiResult) {
    while ($rowLuogo = $luoghiResult->fetch_assoc()) {
        $luoghi[] = $rowLuogo;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>ShuttleX - Orari Navetta</title>
    <meta name="robots" content="noindex, nofollow">
    <?php include_once 'styles/header-include.php'; ?>
</head>
<body>
    <a href="#main-content" class="skip-link">Vai agli orari</a>

    <header class="app-header">
        <div class="container">
            <div class="topbar">
                <a href="home.php" class="brand-logo" aria-label="ShuttleX Home">
                    <i class="fa-solid fa-bus-simple" aria-hidden="true"></i>
                    <span>SX <span class="sub">ShuttleX</span></span>
                </a>

                <div class="status-meta">
                    <span><?= date('H:i') ?></span>
                    <button form="switch"
                            type="submit"
                            class="day-toggle <?= $tipoGiorno ? 'festivo' : 'feriale' ?>"
                            title="Tocca per cambiare tra orario feriale e festivo"
                            aria-label="Modalità attuale: <?= $tipoGiorno ? 'Festivo' : 'Feriale' ?>. Tocca per cambiare">
                        <i class="fa-solid fa-repeat" aria-hidden="true"></i>
                        <?= $tipoGiorno ? "FESTIVO" : "FERIALE" ?>
                    </button>
                </div>
            </div>

            <!-- Form nascosti per azioni rapide -->
            <form id="showall" method="get" action="home.php">
                <input type="hidden" name="show" value="all">
                <input type="hidden" name="fest" value="<?= $tipoGiorno ?>">
            </form>

            <form id="switch" method="get">
                <?php $nuovoTipoGiorno = $tipoGiorno ? 0 : 1; ?>
                <input type="hidden" name="fest" value="<?= $nuovoTipoGiorno ?>">
                <?php if ($fermata !== ''): ?>
                    <input type="hidden" name="fermata" value="<?= htmlspecialchars($fermata) ?>">
                <?php endif; ?>
                <?php if ($destinazione !== ''): ?>
                    <input type="hidden" name="destinazione" value="<?= htmlspecialchars($destinazione) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['orario'])): ?>
                    <input type="hidden" name="orario" value="<?= htmlspecialchars($oraInput) ?>">
                <?php endif; ?>
                <?php if (isset($_GET['show']) && $_GET['show'] === 'all'): ?>
                    <input type="hidden" name="show" value="all">
                <?php endif; ?>
            </form>

            <!-- Form principale di ricerca -->
            <form id="searchForm" method="get" action="partenza.php" role="search">
                <input type="hidden" name="fest" value="<?= $tipoGiorno ?>">

                <div class="search-grid">
                    <div class="field-group">
                        <label for="selectFermata" class="field-label">Partenza</label>
                        <select id="selectFermata" class="form-control" name="fermata" required>
                            <option value="" disabled <?= $fermata === '' ? 'selected' : '' ?>>Seleziona fermata...</option>
                            <?php foreach ($luoghi as $l): ?>
                                <option value="<?= htmlspecialchars($l['id']) ?>" <?= $fermata === $l['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['denominazione']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="selectDestinazione" class="field-label">Destinazione (opzionale)</label>
                        <select id="selectDestinazione" class="form-control" name="destinazione">
                            <option value="">Qualsiasi fermata</option>
                            <?php foreach ($luoghi as $l): ?>
                                <option value="<?= htmlspecialchars($l['id']) ?>" <?= $destinazione === $l['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($l['denominazione']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="field-group">
                        <label for="inputOrario" class="field-label">Dalle ore</label>
                        <input id="inputOrario" class="form-control" type="time" name="orario" value="<?= htmlspecialchars($oraInput) ?>">
                    </div>
                </div>

                <div class="quick-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Cerca corsa
                    </button>

                    <button type="button" onclick="ricercaRapida('cxc', '')" class="btn btn-chip">
                        <i class="fa-solid fa-house" aria-hidden="true"></i> Da CampusX
                    </button>

                    <button type="button" onclick="ricercaRapida('', 'cxc')" class="btn btn-chip">
                        <i class="fa-solid fa-arrow-right-to-bracket" aria-hidden="true"></i> Verso CampusX
                    </button>

                    <button form="showall" type="submit" class="btn btn-secondary">
                        <i class="fa-solid fa-list" aria-hidden="true"></i> Tutte
                    </button>
                </div>
            </form>
        </div>
    </header>

    <script>
    (function() {
        const selPartenza = document.getElementById('selectFermata');
        const selDest = document.getElementById('selectDestinazione');
        const form = document.getElementById('searchForm');

        if (selPartenza && !selPartenza.value) {
            const saved = localStorage.getItem('sx_last_fermata');
            if (saved && selPartenza.querySelector(`option[value="${saved}"]`)) {
                selPartenza.value = saved;
            }
        }

        if (selPartenza) {
            selPartenza.addEventListener('change', () => {
                localStorage.setItem('sx_last_fermata', selPartenza.value);
            });
        }

        window.ricercaRapida = function(partenza, dest) {
            if (partenza) {
                selPartenza.value = partenza;
            } else if (!selPartenza.value || selPartenza.value === 'cxc') {
                const fallback = localStorage.getItem('sx_last_fermata');
                selPartenza.value = (fallback && fallback !== 'cxc') ? fallback : 'pol';
            }
            selDest.value = dest;
            form.submit();
        };
    })();
    </script>

    <?php include_once 'manutenzione.php'; ?>
    <main id="main-content" class="container">