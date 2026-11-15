<?php
include_once 'auth.php';
requireLogin();

if (empty($_GET['fermata'])) {
    header("Location: home.php");
    exit;
}

$fermata = trim($_GET['fermata']);
$destinazione = trim($_GET['destinazione'] ?? '');
$tipoGiorno = isset($_GET['fest']) ? (int)$_GET['fest'] : (date('N') < 6 ? 0 : 1);
$ora = isset($_GET['orario']) && preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $_GET['orario'])
    ? (strlen($_GET['orario']) === 5 ? $_GET['orario'] . ':00' : $_GET['orario'])
    : date('H:i:s');

include_once 'header.php';

$sqlTratte = "
    SELECT DISTINCT O1.tratta, O1.orario AS orarioPartenza, T.note
    FROM orari O1
    JOIN tratte T ON T.numero = O1.tratta AND T.tipo = O1.tipoGiorno
    WHERE O1.tipoGiorno = ?
      AND O1.idLuogo = ?
      AND O1.orario >= ?
    ORDER BY O1.orario ASC
";

$stmt = $sxConn->prepare($sqlTratte);
$stmt->bind_param("iss", $tipoGiorno, $fermata, $ora);
$stmt->execute();
$resTratte = $stmt->get_result();

$corseValide = [];

$stmtFermate = $sxConn->prepare("
    SELECT O.orario, O.idLuogo, L.denominazione
    FROM orari O
    JOIN luogo L ON L.id = O.idLuogo
    WHERE O.tratta = ?
      AND O.tipoGiorno = ?
      AND O.orario >= ?
    ORDER BY O.orario ASC
");

while ($rowTratta = $resTratte->fetch_assoc()) {
    $trattaNum = (int)$rowTratta['tratta'];
    $startOrario = $rowTratta['orarioPartenza'];

    $stmtFermate->bind_param("iis", $trattaNum, $tipoGiorno, $startOrario);
    $stmtFermate->execute();
    $resFermate = $stmtFermate->get_result();

    $fermate = [];
    $raggiungeDestinazione = ($destinazione === '');

    while ($f = $resFermate->fetch_assoc()) {
        $fermate[] = $f;
        if ($destinazione !== '' && count($fermate) > 1 && $f['idLuogo'] === $destinazione) {
            $raggiungeDestinazione = true;
        }
    }

    if (count($fermate) >= 2 && $raggiungeDestinazione) {
        $corseValide[] = [
            'tratta' => $trattaNum,
            'note' => $rowTratta['note'],
            'fermate' => $fermate
        ];
    }
}

$stmtFermate->close();
$stmt->close();

if (empty($corseValide)) {
    $giornoLabel = ($tipoGiorno === 1) ? 'festivo' : 'feriale';
    $oraFmt = substr($ora, 0, 5);
    echo '<div class="w3-container">';
    echo '  <div class="w3-panel w3-card-4 w3-red w3-round">';
    echo '    <h3 class="w3-center">Nessuna Tratta Trovata</h3>';
    echo "    <p class='w3-center'>Non ci sono corse disponibili per i criteri selezionati a partire dalle ore <b>{$oraFmt}</b> (giorno <b>{$giornoLabel}</b>).</p>";
    echo '  </div>';
    echo '</div>';
} else {
    $destinazioneSelezionata = $destinazione !== '' ? $destinazione : null;
    foreach ($corseValide as $corsa) {
        $trattaNum = $corsa['tratta'];
        $noteTratta = $corsa['note'];
        $fermate = $corsa['fermate'];
        include 'components/tratta-card.php';
    }
}

include_once 'footer.php';