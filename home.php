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

if (empty($tratte)) {
    $giornoLabel = ($tipoGiorno === 1) ? 'festivo' : 'feriale';
    $filtroOra = $showAll ? 'tutto il giorno' : 'dopo le ore ' . substr($ora, 0, 5);
    echo '<div class="w3-container">';
    echo '  <div class="w3-panel w3-card-4 w3-red w3-round">';
    echo '    <h3 class="w3-center">Nessuna Tratta Disponibile</h3>';
    echo "    <p class='w3-center'>Non sono disponibili corse {$filtroOra} (giorno <b>{$giornoLabel}</b>).</p>";
    echo '  </div>';
    echo '</div>';
} else {
    $destinazioneSelezionata = null;
    foreach ($tratte as $trattaNum => $datiTratta) {
        $noteTratta = $datiTratta['note'];
        $fermate = $datiTratta['fermate'];
        include 'components/tratta-card.php';
    }
}

include_once 'footer.php';