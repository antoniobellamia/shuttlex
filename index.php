<?php 
include_once 'header.php';

$showAll = isset($_GET['show']) && $_GET['show'] === 'all';
$tipoGiorno = isset($_GET['fest']) ? (int)$_GET['fest'] : (date('N') < 6 ? 0 : 1);
$ora = date('H:i:s'); // index non ha fermata selezionata

// Query base: tutte le tratte della giornata selezionata
$query = "
    SELECT O.orario, O.tratta, O.idLuogo, L.denominazione, T.note
    FROM orari O
    JOIN luogo L ON L.id = O.idLuogo
    JOIN tratte T ON T.numero = O.tratta AND T.tipo = O.tipoGiorno
    WHERE O.tipoGiorno = $tipoGiorno
";

// Se NON showAll, filtro per orario >= ora di sistema
if (!$showAll) {
    $query .= " AND O.orario >= '$ora'";
}

$query .= " ORDER BY O.tratta, O.orario";

$result = $sxConn->query($query);

// Output HTML
$currentTratta = null;
$fermate = [];

while($row = $result->fetch_assoc()) {
    if($currentTratta !== $row['tratta']) {
        // Se sto cambiando tratta → stampo la precedente
        if($currentTratta !== null && count($fermate) > 1) {
            echo '<div class="w3-container">';
            echo '  <div class="w3-panel w3-card-4">';
            echo "    <h3 class='w3-center c-primary'>Tratta $currentTratta";
            if (!empty($noteTratta)) {
                echo " <small class='w3-text-grey'>($noteTratta)</small>";
            }
            echo "</h3>";
            echo "    <ul class='w3-ul'>";
            foreach ($fermate as $i => $f) {
                $orarioFmt = substr($f['orario'], 0, 5);
                $classeExtra = '';
                $icona = "<i class='fa-solid fa-road'></i> ";

                if ($i == 0) {
                    $classeExtra = ' w3-pale-green';
                    $icona = "<i class='fa-solid fa-bus'></i> ";
                } elseif ($i == count($fermate) - 1) {
                    $classeExtra = '';
                    $icona = "<i class='fa-solid fa-building-circle-check'></i> ";
                }

                echo "<li class='$classeExtra' data-luogo='{$f['idLuogo']}' data-orario='{$f['orario']}'>
                        $icona <b>{$f['denominazione']}</b>
                        <span class='w3-right'>{$orarioFmt}</span>
                      </li>";
            }
            echo "    </ul>";
            echo "    <h4 class='w3-center c-primary'></h4>"; 
            echo "  </div>";
            echo "</div>";
        }

        // Reset nuova tratta
        $currentTratta = $row['tratta'];
        $noteTratta = $row['note'];
        $fermate = [];
    }

    $fermate[] = $row;
}

// Stampo l’ultima tratta
if($currentTratta !== null && count($fermate) > 1) {
    echo '<div class="w3-container">';
    echo '  <div class="w3-panel w3-card-4">';
    echo "    <h3 class='w3-center c-primary'>Tratta $currentTratta";
    if (!empty($noteTratta)) {
        echo " <small class='w3-text-grey'>($noteTratta)</small>";
    }
    echo "</h3>";
    echo "    <ul class='w3-ul'>";
    foreach ($fermate as $i => $f) {
        $orarioFmt = substr($f['orario'], 0, 5);
        $classeExtra = '';
        $icona = "<i class='fa-solid fa-road'></i> ";

        if ($i == 0) {
            $classeExtra = ' w3-pale-green';
            $icona = "<i class='fa-solid fa-bus'></i> ";
        } elseif ($i == count($fermate) - 1) {
            $classeExtra = 'w3-khaki';
            $icona = "<i class='fa-solid fa-building-circle-check'></i> ";
        }

        echo "<li class='$classeExtra' data-luogo='{$f['idLuogo']}' data-orario='{$f['orario']}'>
                $icona <b>{$f['denominazione']}</b>
                <span class='w3-right'>{$orarioFmt}</span>
              </li>";
    }
    echo "    </ul>";
    echo "    <h4 class='w3-center'></h4>";
    echo "</div></div>";
}

include_once 'footer.php'; 
?>
