<?php
/**
 * Logica per il pannello di avviso di manutenzione.
 * Questo file deve essere incluso prima di qualsiasi output HTML o logica di query.
 */

// 1. Variabile di controllo:
//    Imposta a true per attivare il pannello di avviso di manutenzione.
//    Imposta a false per disattivarlo.
$isManutenzione = false; // <-- CAMBIA A true PER ATTIVARE IL PANNELLO
$isBloccato = false; //<-- CAMBIA A true PER NON MOSTRARE ALTRO DOPO AVVISO

if ($isManutenzione) {
    // 2. Output del pannello di avviso (formattato come w3-card-4 w3-orange)
    echo '<div class="w3-container">';
    echo '  <div class="w3-panel w3-card-4 w3-orange">';
    echo '    <h3 class="w3-center">⚠️ Sito in Manutenzione</h3>';
    echo "    <p class='w3-center'>Potrebbero esserci dati non aggiornati o orari non precisi. Il servizio tornerà completamente operativo al più presto.</p>";
    echo '  </div>';
    echo '</div>';
    
    // 3. Blocco della pagina (Opzionale ma Raccomandato):
    //    Se l'accesso ai dati non è garantito durante la manutenzione,
    //    è meglio fermare l'esecuzione dello script qui.
    if($isBloccato){
    	if (file_exists('footer.php')) {
        	include_once 'footer.php'; 
    	}
    	exit; // Termina lo script dopo aver mostrato l'avviso e il footer
    }
}
?>