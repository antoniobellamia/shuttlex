<?php
session_start();

// Svuoto e distruggo la sessione
$_SESSION = array();
session_destroy();

// Reindirizzo immediato alla pagina di login
header("location: index.php");
exit;
?>