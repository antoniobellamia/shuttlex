<?php
$ultimaModificaDB = 'Data non disponibile';
if (isset($sxConn) && $sxConn instanceof mysqli) {
    $resultModifica = $sxConn->query("SELECT DATE_FORMAT(ultimaModifica, '%d/%m/%Y %H:%i') AS ultimaModificaFormattata FROM modifica WHERE id = 1");
    if ($resultModifica && $resultModifica->num_rows > 0) {
        $ultimaModificaDB = $resultModifica->fetch_assoc()['ultimaModificaFormattata'];
        $resultModifica->free();
    }
}
?>
<div class="pure-g" style="margin-top: 24px;">
    <div class="pure-u-1-1 c-footer" style="padding: 16px;">
        <h4 style="margin: 4px 0;">Aggiornato al <?= htmlspecialchars($ultimaModificaDB) ?></h4>
        <h4 style="margin: 8px 0; font-size: 0.95em;">
            Credits: <a href="https://www.cx-place.com/it/campus-cxbari.html" target="_blank" rel="noopener">CampusX - Bari</a>
            <br><br>
            <?php if (function_exists('isAdmin') && isAdmin()): ?>
                <a href="admin.php" class="w3-tag w3-pink w3-round" style="color: white !important; margin-right: 12px;">
                    <i class="fa-solid fa-sliders"></i> PANNELLO ADMIN
                </a>
            <?php endif; ?>
            <a href="mailto:<?= htmlspecialchars($mailAssistenza ?? 'bellamiaantonio@protonmail.com') ?>?subject=Richiesta%20assistenza%20ShuttleX">
                Assistenza
            </a>
            &nbsp;&nbsp;&nbsp;
            <a href="disclaimer.php">Condizioni d'uso</a>
            &nbsp;&nbsp;&nbsp;
            <a href="logout.php">LOGOUT</a>
        </h4>
    </div>
</div>
</body>
</html>