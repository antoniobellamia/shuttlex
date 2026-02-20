<div class="pure-g">

<?php
// --- RECUPERO ULTIMA MODIFICA DB ---
$queryModifica = "
    SELECT 
        DATE_FORMAT(ultimaModifica, '%d/%m/%Y') AS ultimaModificaFormattata
    FROM 
        modifica
    WHERE 
        id = 1;
";

$resultModifica = $sxConn->query($queryModifica);

// Controlla se il risultato esiste e recupera la data formattata
if ($resultModifica && $resultModifica->num_rows > 0) {
    $rowModifica = $resultModifica->fetch_assoc();
    $ultimaModificaDB = $rowModifica['ultimaModificaFormattata'];
} else {
    // Valore di fallback se la query fallisce o la tabella è vuota
    $ultimaModificaDB = 'Data non disponibile'; 
}

// Libera la memoria del risultato
if (isset($resultModifica)) {
    $resultModifica->free();
}

// --- VARIABILE $ultimaModificaDB ORA CONTIENE LA DATA FORMATTATA ---

?>


<div class="pure-u-1-1 c-footer">
    <h4>Aggiornato al <?=$ultimaModificaDB?></h4>
    
    <h4>Credits: <a href="https://www.cx-place.com/it/campus-cxbari.html" target="_blank">CampusX - Bari</a> <br>
    	<br><a href="mailto:bellamiaantonio@protonmail.com?subject=Richiesta%20di%20assistenza%20sito%20web%20ShuttleX%20-%20<?=date('l, d/m/Y H:i:s')?>&body=Scrivi%20qui%20la%20tua%20richiesta%20di%20informazioni%20e/o%20assistenza...">
        Assistenza</a> &#160 &#160 &#160 <a href="logout.php">LOGOUT</a><br>
        <a href="disclaimer.php">CONDIZIONI D'USO</a></h4>
</div>
</div>
</body>

</html>