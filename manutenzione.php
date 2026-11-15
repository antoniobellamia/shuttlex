<?php
if (isset($sxConn) && $sxConn instanceof mysqli) {
    $resMan = $sxConn->query("SELECT manutenzione_attiva, manutenzione_bloccata, manutenzione_messaggio FROM impostazioni WHERE id = 1 LIMIT 1");
    if ($resMan && $rowMan = $resMan->fetch_assoc()) {
        if ((int)$rowMan['manutenzione_attiva'] === 1) {
            echo '<div class="container" style="margin-top: 14px;">';
            echo '  <div class="alert alert-warning" role="alert">';
            echo '    <strong><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Avviso di Manutenzione:</strong> ';
            echo      htmlspecialchars($rowMan['manutenzione_messaggio']);
            echo '  </div>';
            echo '</div>';

            if ((int)$rowMan['manutenzione_bloccata'] === 1 && !isAdmin()) {
                echo '</main>';
                include_once 'footer.php';
                exit;
            }
        }
    }
}