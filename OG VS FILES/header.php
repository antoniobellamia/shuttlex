<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/shuttlex/database.php'; // definisce $sxConn


// Parametri comuni
$tipoGiorno = isset($tipoGiorno) ? $tipoGiorno : (isset($_GET['fest']) ? (int)$_GET['fest'] : (date('N') < 6 ? 0 : 1));
$oraVisualizzata = isset($ora) ? $ora : date('H:i:s');
$fermataSelezionata = isset($fermata) ? $fermata : null;

// Recupera denominazione fermata se presente
$fermataDenominazione = '';
if ($fermataSelezionata) {
    $fermataResult = $sxConn->query("SELECT denominazione FROM luogo WHERE id = '$fermataSelezionata'");
    if ($fermataResult && $fermataResult->num_rows > 0) {
        $fermataDenominazione = $fermataResult->fetch_assoc()['denominazione'];
    } else {
        $fermataDenominazione = 'N/A';
    }
}

?>


<html>

<head>
    <title>ShuttleX</title>
    <?php include_once $_SERVER['DOCUMENT_ROOT'] . '/shuttlex/styles/header-include.php' ?>
</head>

<body>
    <nav>
        <div class="pure-g">
            <div class="pure-u-md-1-2 pure-u-5-24">
                <a href="index.php">
                    <h2>SX</h2>
                </a>
            </div>

            <div class="pure-u-md-1-2 pure-u-18-24" style="text-align: end;">
                <p><?= date('d/m/Y H:i') ?> <br> <?= ($tipoGiorno ? "FESTIVO" : "FERIALE") ?></p>
            </div>
        </div>
        <div>

            <form id="showall" method="get">
                <input type="hidden" name="show" value="all">
                <input type="hidden" name="fest" value="<?php echo $tipoGiorno ?>">
            </form>

            <!-- Form 2: Switcha giorno -->
            <form id="switch" class="pure-form" method="get" style="display:inline;">
                <?php
                // Calcolo il valore opposto di tipoGiorno
                $nuovoTipoGiorno = isset($tipoGiorno) ? ($tipoGiorno ? 0 : 1) : 0;
                // Manteniamo i parametri correnti
                $fermataParam = isset($fermata) ? "<input type='hidden' name='fermata' value='$fermata'>" : "";
                $orarioParam = isset($ora) ? "<input type='hidden' name='orario' value='$ora'>" : "";
                ?>
                <input type="hidden" name="fest" value="<?php echo $nuovoTipoGiorno; ?>">
                <?php echo $fermataParam; ?>
                <?php echo $orarioParam; ?>
                
            </form>

            <!-- Form 1: Cerca -->
            <form class="pure-form pure-g" method="get" action="partenza.php">
                <div class="pure-u-3-5">
                    <select class="pure-input-1" name="fermata" required aria-placeholder="Seleziona Fermata">
                        <option value="" disabled selected>Seleziona fermata</option>
                        <?php
                        $luoghiResult = $sxConn->query("SELECT id, denominazione FROM luogo ORDER BY denominazione");
                        while ($luogo = $luoghiResult->fetch_assoc()) {
                            $selected = (isset($fermata) && $fermata == $luogo['id']) ? "selected" : "";
                            echo "<option value='{$luogo['id']}' $selected>{$luogo['denominazione']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="pure-u-2-5">
                    <input class="pure-input-1" type="time" name="orario" value="<?php echo isset($ora) ? $ora : date('H:i'); ?>" />
                </div>

                <button type="submit" class="cbutt pure-button pure-input-1-5 pure-button-primary">Cerca</button>
                <button form="switch" type="submit" class="cbutt sec pure-button pure-input-1-5 pure-button-secondary">
                    vedi <?php echo isset($tipoGiorno) && $tipoGiorno ? "FERIALE" : "FESTIVO"; ?>
                </button>

                <?php if(!isset($_GET["fermata"])) echo "
                <button form=\"showall\" type=\"submit\" class=\"cbutt sec pure-button pure-input-1-5 pure-button-secondary\">
                    Mostra tutti
                </button>"?>
            </form>




        </div>




    </nav>
