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
        $badgeHtml = "<span class='badge badge-now'><i class='fa-solid fa-circle-dot' aria-hidden='true'></i> In partenza</span>";
    } elseif ($diffMinuti <= 15) {
        $badgeHtml = "<span class='badge badge-soon'><i class='fa-regular fa-clock' aria-hidden='true'></i> Tra {$diffMinuti} min</span>";
    } else {
        $badgeHtml = "<span class='badge badge-next'><i class='fa-regular fa-clock' aria-hidden='true'></i> Tra {$diffMinuti} min</span>";
    }
}
?>
<article class="card" aria-label="Tratta <?= (int)$trattaNum ?>">
    <header class="tratta-header">
        <h2 class="tratta-title">
            <span>Tratta <?= (int)$trattaNum ?></span>
            <?php if (!empty($noteTratta)): ?>
                <span class="tratta-note"><?= htmlspecialchars($noteTratta) ?></span>
            <?php endif; ?>
        </h2>
        <?= $badgeHtml ?>
    </header>

    <ol class="timeline">
        <?php foreach ($fermate as $i => $f):
            $orarioFmt = substr($f['orario'], 0, 5);
            $isFirst = ($i === 0);
            $isLast = ($i === count($fermate) - 1);
            $isDest = (!empty($destinazioneSelezionata) && $f['idLuogo'] === $destinazioneSelezionata && !$isFirst);

            $itemClass = '';
            if ($isFirst) {
                $itemClass = 'is-start';
            } elseif ($isDest) {
                $itemClass = 'is-dest';
            } elseif ($isLast) {
                $itemClass = 'is-end';
            }
        ?>
            <li class="timeline-item <?= $itemClass ?>" data-luogo="<?= htmlspecialchars($f['idLuogo']) ?>">
                <span class="stop-name">
                    <?= htmlspecialchars($f['denominazione']) ?>
                    <?php if ($isFirst): ?>
                        <span class="sr-only">(Partenza)</span>
                    <?php elseif ($isDest): ?>
                        <span class="badge badge-arrival">Arrivo</span>
                    <?php endif; ?>
                </span>
                <time class="stop-time" datetime="<?= htmlspecialchars($orarioFmt) ?>"><?= $orarioFmt ?></time>
            </li>
        <?php endforeach; ?>
    </ol>
</article>