<?php
// Dati di connessione
$sxHost = "localhost";   // Altervista usa sempre localhost
$sxUser = "mels";        // Username DB Altervista
$sxPass = "";            // Password (lascia vuoto se non ne hai)
$sxName = "my_mels";     // Nome database
$mailAssistenza = "bellamiaantonio@protonmail.com";

// Connessione al database
$sxConn = new mysqli($sxHost, $sxUser, $sxPass, $sxName);

// Controllo connessione
if ($sxConn->connect_error) {
    die("Connessione fallita: " . $sxConn->connect_error);
}

// Imposto charset UTF-8 (molto importante per Altervista)
$sxConn->set_charset("utf8");

// Ora $sxConn è pronto per le query
?>

<?php
/*//FUNZIONE BETA
echo "in";
// Assicuriamoci che la connessione $sxConn esista (dallo script precedente)
if (isset($sxConn) && $sxConn instanceof mysqli) {
 echo "ok";
    // 1. Recupero i dati dalle variabili globali del Server
    $visita_ip    = $_SERVER['REMOTE_ADDR'];
    $visita_page  = $_SERVER['REQUEST_URI']; // Prende l'indirizzo completo (es. /articolo.php?id=1)
    
    // Controllo se lo User Agent esiste (alcuni script non lo inviano)
    $visita_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Sconosciuto';

    // 2. Preparo la query SQL (Uso prepare per sicurezza contro SQL Injection)
    // Non inserisco 'id' e 'data_visita' perché il database li compila in automatico
    $query = "INSERT INTO visite_stats (ip_address, pagina, user_agent) VALUES (?, ?, ?)";

    if ($stmt = $sxConn->prepare($query)) {
        
        // 3. Collego i parametri ("sss" sta per 3 stringhe)
        $stmt->bind_param("sss", $visita_ip, $visita_page, $visita_agent);
        
        // 4. Eseguo l'inserimento
        $stmt->execute();
        echo "exec";
        // 5. Chiudo lo statement per liberare risorse
        $stmt->close();
    } else {
        // Opzionale: gestione errore se la query non viene preparata
        // error_log("Errore Stats: " . $sxConn->error);
    }
}*/
?>
