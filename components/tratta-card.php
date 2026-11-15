<?php
if (empty($fermate) || count($fermate) < 2) {
    return;
}

$oraAttualeTs = time();
$primaPartenzaStr = date('Y-m-d') . ' ' . $fermate[0]['orario'];
$primaPartenzaTs = strtotime($primaPartenzaStr);
$diffMinuti = (int)round(($primaPartenzaTs - $oraAttualeTs) / 60);

$badgeHtml = '';
if ($diffMinuti >= -1 && $diffMinuti <= 90) {
    if ($diffMinuti <= 0) {
        $badgeHtml = "<span class='w3-tag w3-green w3-round w3-small' style='margin-left:8px;'>In partenza</span>";
    } elseif ($diffMinuti <= 15) {
        $badgeHtml = "<span class='w3-tag w3-orange w3-text-white w3-round w3-small' style='margin-left:8px;'>Tra {$diffMinuti} min</span>";
    } else {
        $badgeHtml = "<span class='w3-tag w3-blue w3-round w3-small' style='margin-left:8px;'>Tra {$diffMinuti} min</span>";
    }
}
?>
<div class="w3-container">
    <div class="w3-panel w3-card-4 w3-white w3-round">
        <h3 class="w3-center c-primary" style="margin-bottom: 6px;">
            Tratta <?= (int)$trattaNum ?>
            <?php if (!empty($noteTratta)): ?>
                <small class="w3-text-grey">(<?= htmlspecialchars($noteTratta) ?>)</small>
            <?php endif; ?>
            <?= $badgeHtml ?>
        </h3>
        <ul class="w3-ul">
            <?php foreach ($fermate as $i => $f):
                $orarioFmt = substr($f['orario'], 0, 5);
                $isFirst = ($i === 0);
                $isLast = ($i === count($fermate) - 1);
                $isDest = (!empty($destinazioneSelezionata) && $f['idLuogo'] === $destinazioneSelezionata && !$isFirst);

                $classeExtra = '';
                $icona = "<i class='fa-solid fa-road w3-text-grey'></i> ";

                if ($isFirst) {
                    $classeExtra = 'w3-pale-green';
                    $icona = "<i class='fa-solid fa-bus w3-text-green'></i> ";
                } elseif ($isDest) {
                    $classeExtra = 'w3-pale-blue';
                    $icona = "<i class='fa-solid fa-location-dot w3-text-blue'></i> ";
                } elseif ($isLast) {
                    $icona = "<i class='fa-solid fa-building-circle-check'></i> ";
                }
            ?>
                <li class="<?= $classeExtra ?>" data-luogo="<?= htmlspecialchars($f['idLuogo']) ?>" data-orario="<?= htmlspecialchars($f['orario']) ?>">
                    <?= $icona ?>
                    <b><?= htmlspecialchars($f['denominazione']) ?></b>
                    <?php if ($isDest): ?>
                        <span class="w3-tag w3-blue w3-tiny w3-round" style="margin-left:4px;">Arrivo</span>
                    <?php endif; ?>
                    <span class="w3-right"><b><?= $orarioFmt ?></b></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>