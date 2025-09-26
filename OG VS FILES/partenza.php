<?php
// Parametro obbligatorio
if(!isset($_GET['fermata'])) die("Parametro 'fermata' obbligatorio");

$fermata = $_GET['fermata'];
$tipoGiorno = isset($_GET['fest']) ? (int)$_GET['fest'] : (date('N') < 6 ? 0 : 1);
$ora = isset($_GET['orario']) ? $_GET['orario'] : date('H:i:s');

include_once 'header.php'; 

// Troviamo le tratte che passano per la fermata selezionata
$queryTratte = "
    SELECT DISTINCT tratta
    FROM orari
    WHERE tipoGiorno = $tipoGiorno AND idLuogo = '$fermata'
";
$tratteResult = $sxConn->query($queryTratte);

while($trattaRow = $tratteResult->fetch_assoc()) {
    $tratta = $trattaRow['tratta'];

    // Recupero note della tratta
    $queryNote = "SELECT note FROM tratte WHERE numero = $tratta AND tipo = $tipoGiorno LIMIT 1";
    $noteRes = $sxConn->query($queryNote);
    $note = "";
    if ($noteRes && $noteRes->num_rows > 0) {
        $noteVal = $noteRes->fetch_assoc()['note'];
        if (!is_null($noteVal) && trim($noteVal) !== "") {
            $note = " <small class='w3-text-grey'>($noteVal)</small>";
        }
    }

    // Recuperiamo l'orario della fermata selezionata più vicina >= ora richiesta
    $queryStart = "
        SELECT orario 
        FROM orari 
        WHERE tratta = $tratta AND tipoGiorno = $tipoGiorno AND idLuogo = '$fermata' AND orario >= '$ora'
        ORDER BY orario ASC LIMIT 1
    ";
    $startResult = $sxConn->query($queryStart);
    if($startResult->num_rows == 0) continue;
    $startOrario = $startResult->fetch_assoc()['orario'];

    // Recuperiamo tutte le fermate della tratta a partire dalla fermata selezionata
    $queryOrari = "
        SELECT O.orario, O.idLuogo, L.denominazione
        FROM orari O
        JOIN luogo L ON L.id = O.idLuogo
        WHERE O.tratta = $tratta
        AND O.tipoGiorno = $tipoGiorno
        AND O.orario >= '$startOrario'
        ORDER BY O.orario
    ";
    $resultOrari = $sxConn->query($queryOrari);

    if($resultOrari->num_rows < 2) continue;

    $fermate = [];
    while($row = $resultOrari->fetch_assoc()){
        $fermate[] = $row;
    }

    echo '<div class="w3-container">';
    echo '  <div class="w3-panel w3-card-4">';
    echo "    <h3 class='w3-center c-primary'>Tratta $tratta$note</h3>";
    echo "    <ul class='w3-ul'>";
    
    foreach ($fermate as $i => $row) {
        $orarioFmt = substr($row['orario'], 0, 5); // HH:MM
        $classeExtra = '';
        $icona = "<i class='fa-solid fa-road'></i> ";

        if ($i == 0) {
            $classeExtra = ' w3-pale-green'; 
            $icona = "<i class='fa-solid fa-bus'></i> ";
        } elseif ($i == count($fermate) - 1) {
            $classeExtra = ''; 
            $icona = "<i class='fa-solid fa-building-circle-check'></i> ";
        }

        echo "<li class='$classeExtra' data-luogo='{$row['idLuogo']}' data-orario='{$row['orario']}'>
                $icona <b>{$row['denominazione']}</b>
                <span class='w3-right'>{$orarioFmt}</span>
              </li>";
    }

    echo "    </ul>";
    echo "    <h4 class='w3-center c-primary'></h4>"; 
    echo "  </div>";
    echo "</div>";
}

include_once 'footer.php'; 
?>  
