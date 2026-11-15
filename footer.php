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
    </main>

    <footer class="app-footer">
        <div class="footer-inner">
            <div>
                Orari aggiornati al <strong><?= htmlspecialchars($ultimaModificaDB) ?></strong> &middot;
                Credits: <a href="https://www.cx-place.com/it/campus-cxbari.html" target="_blank" rel="noopener">CampusX Bari</a>
            </div>
            <nav class="footer-links" aria-label="Link di servizio">
                <?php if (function_exists('isAdmin') && isAdmin()): ?>
                    <a href="admin.php"><i class="fa-solid fa-sliders" aria-hidden="true"></i> Pannello Admin</a>
                <?php endif; ?>
                <a href="mailto:<?= htmlspecialchars($mailAssistenza ?? 'bellamiaantonio@protonmail.com') ?>?subject=Richiesta%20assistenza%20ShuttleX">Assistenza</a>
                <a href="disclaimer.php">Condizioni d'uso</a>
                <a href="logout.php">Esci</a>
            </nav>
        </div>
    </footer>
</body>
</html>