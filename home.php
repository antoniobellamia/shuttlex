<?php
include_once 'auth.php';
requireLogin();
include_once 'header.php';

$showAll = isset($_GET['show']) && $_GET['show'] === 'all';
$tipoGiorno = isset($_GET['fest']) ? (int)$_GET['fest'] : (date('N') < 6 ? 0 : 1);
$ora = date('H:i:s');
$isDomenica = ((int)date('N') === 7);

$sql = "
    SELECT O.orario, O.tratta, O.idLuogo, L.denominazione, T.note
    FROM orari O
    JOIN luogo L ON L.id = O.idLuogo
    JOIN tratte T ON T.numero = O.tratta AND T.tipo = O.tipoGiorno
    WHERE O.tipoGiorno = ?
";

if (!$showAll) {
    $sql .= " AND O.orario >= ?";
    if ($isDomenica && $tipoGiorno === 1) {
        $sql .= " AND (T.note IS NULL OR UPPER(T.note) NOT LIKE '%SOLO SABATO%')";
    }
}
$sql .= " ORDER BY O.tratta ASC, O.orario ASC";

$stmt = $sxConn->prepare($sql);
if (!$showAll) {
    $stmt->bind_param("is", $tipoGiorno, $ora);
} else {
    $stmt->bind_param("i", $tipoGiorno);
}
$stmt->execute();
$result = $stmt->get_result();

$tratte = [];
while ($row = $result->fetch_assoc()) {
    $num = (int)$row['tratta'];
    if (!isset($tratte[$num])) {
        $tratte[$num] = [
            'note' => $row['note'],
            'fermate' => []
        ];
    }
    $tratte[$num]['fermate'][] = $row;
}
$stmt->close();

$tratte = array_filter($tratte, fn($t) => count($t['fermate']) > 1);
?>

<h1 class="sr-only">Orari Navetta <?= $tipoGiorno ? 'Festivi' : 'Feriali' ?></h1>

<?php if (empty($tratte)):
    $giornoLabel = ($tipoGiorno === 1) ? 'festivo' : 'feriale';
    $filtroOra = $showAll ? 'tutto il giorno' : 'dopo le ore ' . substr($ora, 0, 5);
?>
    <section class="card empty-state" role="status">
        <i class="fa-solid fa-bus-simple" aria-hidden="true"></i>
        <h2 style="margin: 0 0 6px; font-size: 1.15rem;">Nessuna corsa disponibile</h2>
        <p style="margin: 0 0 16px; color: var(--text-muted);">
            Non ci sono partenze <?= $filtroOra ?> per l'orario <strong><?= $giornoLabel ?></strong>.
        </p>
        <a href="home.php?show=all&fest=<?= $tipoGiorno ?>" class="btn btn-chip">
            Mostra tutte le corse della giornata
        </a>
    </section>
<?php else:
    $destinazioneSelezionata = null;
    foreach ($tratte as $trattaNum => $datiTratta) {
        $noteTratta = $datiTratta['note'];
        $fermate = $datiTratta['fermate'];
        include 'components/tratta-card.php';
    }
endif;

include_once 'footer.php';