<?php 
session_start();
// Se l'utente non è loggato, lo rimando alla pagina di login (index.php)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("location: index.php?redirect=home");
    exit;
}

include_once 'header.php';
include_once 'login/login.php';

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

// CONTROLLO SUBITO DOPO L'ESECUZIONE DELLA QUERY PRINCIPALE
$numRows = $result->num_rows;

// Output HTML
$currentTratta = null;
$fermate = [];

// --- LOGICA DI VISUALIZZAZIONE DEL MESSAGGIO DI AVVISO ---
if ($numRows == 0) {
    $giorno = ($tipoGiorno === 1) ? 'festivo' : 'feriale';
    $filtroOra = $showAll ? 'tutto il giorno' : " dopo le ore $ora";

    echo '<div class="w3-container">';
    echo '  <div class="w3-panel w3-card-4 w3-red">'; // Pannello rosso per l'avviso
    echo '    <h3 class="w3-center">Nessuna Tratta Disponibile</h3>';
    echo "    <p class='w3-center'>Non sono disponibili corse $filtroOra, giorno <b>$giorno</b></p>";
    echo '  </div>';
    echo '</div>';
    
    // Se non ci sono risultati, non c'è bisogno di continuare
    include_once 'footer.php'; 
    exit; // Termina lo script
}

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
            $classeExtra = '';
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