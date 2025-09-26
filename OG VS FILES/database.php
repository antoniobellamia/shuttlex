<?php
    
    $sxHost = "127.0.0.1";  # Indirizzo "localhost"
    $sxUser = "root";       # L'utente del DBMS
    $sxPass = "";           # La password dell'utente del DBMS
    $sxName = "shuttlex"; # Il nome del database da selezionare
    
    try{
    // Effettuo la connessione al DB

        $sxConn = mysqli_connect(
            $sxHost,
            $sxUser,
            $sxPass,
            $sxName
        );

    }catch(Exception $exc){
        header("Location: //" . $_SERVER['SERVER_NAME'] . "/shuttlex/errors/500.php");
    }
?>