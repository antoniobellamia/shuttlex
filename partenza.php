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
?>

<h1 class="sr-only">Risultati ricerca navetta</h1>

<?php if (empty($corseValide)):
    $giornoLabel = ($tipoGiorno === 1) ? 'festivo' : 'feriale';
    $oraFmt = substr($ora, 0, 5);
?>
    <section class="card empty-state" role="status">
        <i class="fa-solid fa-route" aria-hidden="true"></i>
        <h2 style="margin: 0 0 6px; font-size: 1.15rem;">Nessuna corsa trovata</h2>
        <p style="margin: 0 0 16px; color: var(--text-muted);">
            Nessuna partenza disponibile dalle ore <strong><?= $oraFmt ?></strong> per l'orario <strong><?= $giornoLabel ?></strong> con i filtri scelti.
        </p>
        <a href="home.php?show=all&fest=<?= $tipoGiorno ?>" class="btn btn-chip">
            Vedi tutte le tratte
        </a>
    </section>
<?php else:
    $destinazioneSelezionata = $destinazione !== '' ? $destinazione : null;
    foreach ($corseValide as $corsa) {
        $trattaNum = $corsa['tratta'];
        $noteTratta = $corsa['note'];
        $fermate = $corsa['fermate'];
        include 'components/tratta-card.php';
    }
endif;

include_once 'footer.php';