<?php
session_start();
include_once 'database.php'; // Include la connessione $sxConn dal tuo file

// Variabile per gestire gli eventuali messaggi di errore
$errorMsg = "";

if (isset($_GET['redirect']) && $_GET['redirect'] === 'home') {
    $errorMsg = "Sessione scaduta o accesso non autorizzato.<br>Effettua nuovamente il login.";
}

// --- LOGICA DI LOGIN ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Pulizia input
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 2. Conversione password in MD5 (come da tua richiesta)
    $password_md5 = md5($password);

    // 3. Query sicura con Prepared Statements
    $sql = "SELECT username FROM login WHERE username = ? AND password = ?";
    
    if ($stmt = $sxConn->prepare($sql)) {
        // "ss" indica che i due parametri sono stringhe
        $stmt->bind_param("ss", $username, $password_md5);
        $stmt->execute();
        $stmt->store_result();

        // 4. Se troviamo esattamente 1 corrispondenza nel database
        if ($stmt->num_rows == 1) {
            // Login OK: salvo la sessione
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $username;
            
            // Reindirizzo alla pagina di destinazione richiesta
            header("location: home.php"); 
            exit;
        } else {
            // Login Fallito
            $errorMsg = "Username o Password errati.";
        }
        $stmt->close();
    } else {
        $errorMsg = "Errore tecnico di connessione al database.";
    }
}
?>


<!------------------------header v l -------------------------------->
<html>
<head>
    <title>Login</title>
    <?php include_once 'styles/header-include.php' ?>
</head>

<body>
    <nav>
        <div class="pure-g" style="align-items: center;">
            
            <div class="pure-u-1-2">
                <a href="index.php" style="text-decoration: none;">
                    <h2 class="c-margin-not">SX</h2>
                </a>
            </div>

            <div class="pure-u-1-2" style="text-align: right;">
                <p class="c-margin-not" style="color: #666;">
                    <?= date('d/m/Y') ?>
                </p>
            </div>
            
        </div>
        
        </nav>
  
<?php    
    
    if (file_exists('manutenzione.php')) {
        include_once 'manutenzione.php'; 
    }

?>
<!-------------------->
<div class="w3-container" style="max-width: 500px; margin: auto; padding-top: 60px;">
    
    <div class="w3-panel w3-card-4 w3-white w3-round-large">
        
        <h3 class="w3-center c-primary">
            <i class="fa-solid fa-user-lock"></i> Area Riservata
        </h3>
        <hr>

        <?php if (!empty($errorMsg)): ?>
            <div class="w3-panel w3-red w3-display-container w3-round w3-animate-opacity">
                <span onclick="this.parentElement.style.display='none'"
                class="w3-button w3-red w3-large w3-display-topright">&times;</span>
                <p><?php echo $errorMsg; ?></p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="w3-container">
            
            <p>
                
                <div class="w3-row"><label class="w3-text-grey"><b>Username</b></label>
                    <div class="w3-col s1 w3-center w3-padding-small">
                        <i class="fa-solid fa-user w3-large w3-text-grey"></i>
                    </div>
                    <div class="w3-col s11">
                        <input class="w3-input w3-border w3-round" type="text" name="username" required placeholder="Inserisci il tuo username">
                    </div>
                </div>
            </p>

            <p>
              
                <div class="w3-row">  <label class="w3-text-grey"><b>Password</b></label>
                    <div class="w3-col s1 w3-center w3-padding-small">
                        <i class="fa-solid fa-key w3-large w3-text-grey"></i>
                    </div>
                    <div class="w3-col s11">
                        <input class="w3-input w3-border w3-round" type="password" name="password" required placeholder="Inserisci la tua password">
                    </div>
                </div>
            </p>

            <p class="w3-center w3-padding-16">
                <button class="w3-button w3-block w3-blue w3-round w3-hover-light-blue w3-card-2" type="submit">
                    ACCEDI
                </button>
            </p>
            
            <h3 class="w3-center c-primary">
            <i class="fa-regular fa-envelope"></i> <a href="mailto:bellamiaantonio@protonmail.com?subject=Richiesta%20di%20assistenza%20LOGIN%20sito%20web%20ShuttleX%20-%20<?=date('l, d/m/Y H:i:s')?>&body=Scrivi%20qui%20la%20tua%20richiesta%20di%20informazioni%20e/o%20assistenza...">
        Assistenza</a>
        </h3>
        
            

        </form>
    </div>
</div>

<div class="w3-panel w3-leftbar w3-border-blue w3-pale-blue w3-round w3-padding-16 w3-margin-top">
    <h5 class="w3-text-blue" style="margin-top:0">
        <i class="fa-solid fa-shield-halved"></i> <b>Accesso Riservato</b>
    </h5>
    
    <div class="w3-small w3-text-dark-grey">
        <p>
            Questo portale è un'iniziativa indipendente sviluppata a <strong>puro scopo informativo</strong> 
            e non ha alcun fine di lucro, in modo da agevolare la consultazione degli orari della navetta. 
            <strong>Non riveste carattere di ufficialità.</strong>
        </p>
        <div class="w3-container w3-white w3-round w3-border w3-border-blue-grey w3-padding-small" style="opacity: 0.9;">
            <b class="w3-text-blue-grey">DISCLAIMER E CONDIZIONI D'USO</b>
            <ul class="w3-ul" style="font-size: 0.95em;">
                <li style="padding: 4px 0;">
                    <i class="fa-solid fa-lock w3-text-blue"></i> 
                    Trattandosi di dati relativi a un servizio di trasporto privato, l'accesso agli orari è stato volontariamente limitato tramite autenticazione.
                </li>
                <li style="padding: 4px 0;">
                    <i class="fa-solid fa-user-shield w3-text-blue"></i> 
                    Le credenziali sono rilasciate su richiesta all'assistenza per tutelare la riservatezza del servizio.
                </li>
                <li style="padding: 4px 0;">
                    <i class="fa-solid fa-triangle-exclamation w3-text-blue"></i> 
                    L'utilizzo del servizio implica l'accettazione che i dati, pur se aggiornati regolarmente, potrebbero differire dalle disposizioni dell'amministrazione in tempo reale.
                </li>
            </ul>
        </div>
        <hr style="border-top: 1px solid #b3cde0;">
        <p>
            <i class="fa-solid fa-circle-info"></i> 
            Non possiedi le credenziali? <br> 
            Contatta l'assistenza per richiedere l'abilitazione:
            <br><br>
            <a href="mailto:bellamiaantonio@protonmail.com?subject=Richiesta%20di%20assistenza%20LOGIN%20sito%20web%20ShuttleX%20-%20<?=date('l, d/m/Y H:i:s')?>&body=Scrivi%20qui%20la%20tua%20richiesta%20di%20informazioni%20e/o%20assistenza..."
            	class="w3-button w3-white w3-border w3-border-blue w3-round-small w3-tiny">
                <i class="fa-solid fa-envelope"></i> Contatta Assistenza
            </a>
        </p>
    </div>
</div>

</body>

</html>