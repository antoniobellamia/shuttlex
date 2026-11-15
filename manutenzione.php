<?php
if (isset($sxConn) && $sxConn instanceof mysqli) {
    $resMan = $sxConn->query("SELECT manutenzione_attiva, manutenzione_bloccata, manutenzione_messaggio FROM impostazioni WHERE id = 1 LIMIT 1");
    if ($resMan && $rowMan = $resMan->fetch_assoc()) {
        if ((int)$rowMan['manutenzione_attiva'] === 1) {
            echo '<div class="w3-container">';
            echo '  <div class="w3-panel w3-card-4 w3-orange w3-round">';
            echo '    <h3 class="w3-center"><i class="fa-solid fa-triangle-exclamation"></i> Sito in Manutenzione</h3>';
            echo '    <p class="w3-center">' . htmlspecialchars($rowMan['manutenzione_messaggio']) . '</p>';
            echo '  </div>';
            echo '</div>';

            if ((int)$rowMan['manutenzione_bloccata'] === 1 && !isAdmin()) {
                include_once 'footer.php';
                exit;
            }
        }
    }
}