<html>
<head>
    <title>Disclaimer & Condizioni d'uso</title>
    <?php include_once 'styles/header-include.php' ?>
    
    <style>
        /* CSS per centrare tutto perfettamente al centro della pagina */
        body, html {
            height: 100%;
            margin: 0;
            background-color: #f5f7fa; /* Grigio molto chiaro professionale */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .legal-card {
            background-color: white;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08); /* Ombra leggera */
            border-top: 5px solid #2196F3; /* Barra blu in alto */
        }
        
        .legal-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .legal-list li:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <div class="main-wrapper">
        
        <div class="w3-card legal-card w3-round">
            
            <div class="w3-container w3-padding-large w3-border-bottom">
                <div class="w3-row">
                    <div class="w3-col s6">
                        <h2 style="margin:0; font-weight:bold; color:#333;">SX <span style="font-size:0.5em; color:#777; font-weight:normal;">/ Condizioni d'uso</span></h2>
                    </div>
                    <div class="w3-col s6 w3-right-align">
                        <span class="w3-tag w3-light-grey w3-round"><?= date('d/m/Y') ?></span>
                    </div>
                </div>
            </div>

            <div class="w3-container w3-padding-large">
                
                <h4 class="w3-text-dark-grey" style="margin-top:0;">
                    <i class="fa-solid fa-file-contract w3-text-blue w3-margin-right"></i>
                    Termini del Servizio
                </h4>

                <p class="w3-text-grey" style="text-align: justify; line-height: 1.6;">
                    Questo portale è un'iniziativa indipendente sviluppata a <strong>puro scopo informativo</strong> e non ha alcun fine di lucro, in modo da agevolare la consultazione degli orari della navetta. <strong>Non riveste carattere di ufficialità.</strong>
                </p>

                <div class="w3-margin-top w3-margin-bottom">
                    <ul class="w3-ul legal-list w3-text-dark-grey" style="font-size: 0.95em;">
                        <li>
                            <i class="fa-solid fa-lock w3-text-blue w3-margin-right" style="width:20px"></i>
                            Trattandosi di dati relativi a un servizio di trasporto privato, l'accesso agli orari è stato volontariamente limitato tramite autenticazione.
                        </li>
                        <li>
                            <i class="fa-solid fa-user-shield w3-text-blue w3-margin-right" style="width:20px"></i>
                            Le credenziali sono rilasciate su richiesta all'assistenza per tutelare la riservatezza del servizio.
                        </li>
                        <li>
                            <i class="fa-solid fa-triangle-exclamation w3-text-blue w3-margin-right" style="width:20px"></i>
                            L'utilizzo del servizio implica l'accettazione che i dati, pur se aggiornati regolarmente, potrebbero differire dalle disposizioni dell'amministrazione in tempo reale.
                        </li>
                    </ul>
                </div>

                <div class="w3-center w3-padding-16 w3-light-grey w3-round w3-margin-top">
                    <p class="w3-small w3-text-grey" style="margin:0 0 10px 0;">Non possiedi le credenziali? Contatta l'assistenza:</p>
                    
                    <a href="mailto:bellamiaantonio@protonmail.com?subject=Richiesta%20di%20assistenza%20LOGIN%20sito%20web%20ShuttleX%20-%20<?=date('l, d/m/Y H:i:s')?>&body=Scrivi%20qui%20la%20tua%20richiesta%20di%20informazioni%20e/o%20assistenza..."
                       class="w3-button w3-white w3-border w3-border-blue w3-text-blue w3-hover-blue w3-round w3-small">
                        <i class="fa-solid fa-envelope"></i> Invia Richiesta
                    </a>
                </div>

            </div>
            
            <div class="w3-container w3-padding w3-center w3-border-top">
                <a href="home.php" class="w3-text-grey w3-hover-text-black" style="text-decoration:none; font-size:0.9em;">
                    <i class="fa-solid fa-arrow-left"></i> Torna alla Home
                </a>
            </div>

        </div>
        </div>

</body>
</html>