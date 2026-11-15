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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#df1d84">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="icons/logo.png">
    <?php include_once 'styles/header-include.php'; ?>
</head>
<body>
    <nav>
        <div class="pure-g" style="align-items: center;">
            <div class="pure-u-md-1-2 pure-u-8-24">
                <a href="home.php">
                    <h2 class="c-margin-not" style="margin-top:0;">SX</h2>
                </a>
            </div>
            <div class="pure-u-md-1-2 pure-u-16-24" style="text-align: end;">
                <p class="c-margin-not" style="margin-top:0; font-size: 0.7em;">
                    <?= date('d/m/Y H:i') ?><br>
                    <span class="w3-tag w3-round <?= $tipoGiorno ? 'w3-pink' : 'w3-dark-grey' ?>">
                        <?= $tipoGiorno ? "FESTIVO" : "FERIALE" ?>
                    </span>
                </p>
            </div>
        </div>

        <div style="margin-top: 8px;">
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

            <form id="searchForm" class="pure-form pure-g" method="get" action="partenza.php" style="gap: 4px 0;">
                <input type="hidden" name="fest" value="<?= $tipoGiorno ?>">

                <div class="pure-u-1 pure-u-md-2-5" style="padding-right: 4px;">
                    <select id="selectFermata" class="pure-input-1" name="fermata" required aria-label="Fermata di partenza">
                        <option value="" disabled <?= $fermata === '' ? 'selected' : '' ?>>Partenza da...</option>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?= htmlspecialchars($l['id']) ?>" <?= $fermata === $l['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($l['denominazione']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pure-u-3-5 pure-u-md-2-5" style="padding-right: 4px;">
                    <select id="selectDestinazione" class="pure-input-1" name="destinazione" aria-label="Destinazione (opzionale)">
                        <option value="">Qualsiasi destinazione</option>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?= htmlspecialchars($l['id']) ?>" <?= $destinazione === $l['id'] ? 'selected' : '' ?>>
                                Verso: <?= htmlspecialchars($l['denominazione']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="pure-u-2-5 pure-u-md-1-5">
                    <input class="pure-input-1" type="time" name="orario" value="<?= htmlspecialchars($oraInput) ?>" aria-label="Orario" />
                </div>

                <div class="pure-u-1" style="margin-top: 6px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                    <button type="submit" class="cbutt button-small pure-button pure-button-primary">
                        <i class="fa-solid fa-magnifying-glass"></i> Cerca
                    </button>

                    <button type="button" onclick="ricercaRapida('cxc', '')" class="cbutt button-small pure-button" title="Partenze dal CampusX">
                        <i class="fa-solid fa-house"></i> Da CampusX
                    </button>

                    <button type="button" onclick="ricercaRapida('', 'cxc')" class="cbutt button-small pure-button" title="Ritorni verso CampusX">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Verso CampusX
                    </button>

                    <button form="switch" type="submit" class="cbutt button-small pure-button pure-button-secondary">
                        Vedi <?= $tipoGiorno ? "FERIALE" : "FESTIVO" ?>
                    </button>

                    <button form="showall" type="submit" class="cbutt button-small pure-button pure-button-secondary">
                        Tutte
                    </button>
                </div>
            </form>
        </div>
    </nav>

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
            } else if (!selPartenza.value) {
                const fallback = localStorage.getItem('sx_last_fermata');
                selPartenza.value = (fallback && fallback !== 'cxc') ? fallback : 'pol';
            }
            selDest.value = dest;
            form.submit();
        };
    })();
    </script>

<?php include_once 'manutenzione.php'; ?>