<?php
include_once 'database.php';
include_once 'auth.php';
requireAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// --- ENDPOINT AJAX PER SALVATAGGIO INTERATTIVO INLINE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Token CSRF non valido']);
        exit;
    }

    $azione = $_POST['azione'] ?? '';

    if ($azione === 'update_orario') {
        $id = (int)($_POST['id'] ?? 0);
        $idLuogo = trim($_POST['idLuogo'] ?? '');
        $orarioRaw = trim($_POST['orario'] ?? '');

        if ($id <= 0 || $idLuogo === '' || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $orarioRaw)) {
            echo json_encode(['ok' => false, 'error' => 'Dati non validi']);
            exit;
        }
        $orario = strlen($orarioRaw) === 5 ? $orarioRaw . ':00' : $orarioRaw;

        $stmt = $sxConn->prepare("UPDATE orari SET idLuogo = ?, orario = ? WHERE id = ?");
        $stmt->bind_param("ssi", $idLuogo, $orario, $id);
        $ok = $stmt->execute();
        $err = $stmt->error;
        $stmt->close();

        if ($ok) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Conflitto orario/fermata sulla stessa tratta (' . $err . ')']);
        }
        exit;
    }

    if ($azione === 'update_nota') {
        $tratta = (int)($_POST['tratta'] ?? 0);
        $tipo = (int)($_POST['tipo'] ?? 0);
        $nota = trim($_POST['note'] ?? '');
        $notaVal = ($nota === '') ? null : $nota;

        $stmt = $sxConn->prepare("UPDATE tratte SET note = ? WHERE numero = ? AND tipo = ?");
        $stmt->bind_param("sii", $notaVal, $tratta, $tipo);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        }
        echo json_encode(['ok' => $ok]);
        exit;
    }

    if ($azione === 'delete_orario') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $sxConn->prepare("DELETE FROM orari WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        }
        echo json_encode(['ok' => $ok]);
        exit;
    }

    echo json_encode(['ok' => false, 'error' => 'Azione sconosciuta']);
    exit;
}

// --- GESTIONE FORM TRADIZIONALI ---
$feedback = '';
$feedbackClass = 'w3-green';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token di sicurezza non valido.");
    }

    $azione = $_POST['azione'] ?? '';

    if ($azione === 'manutenzione') {
        $attiva = isset($_POST['manutenzione_attiva']) ? 1 : 0;
        $bloccata = isset($_POST['manutenzione_bloccata']) ? 1 : 0;
        $msg = trim($_POST['manutenzione_messaggio'] ?? '');

        $stmt = $sxConn->prepare("UPDATE impostazioni SET manutenzione_attiva = ?, manutenzione_bloccata = ?, manutenzione_messaggio = ? WHERE id = 1");
        $stmt->bind_param("iis", $attiva, $bloccata, $msg);
        $stmt->execute();
        $stmt->close();
        $feedback = "Impostazioni di manutenzione aggiornate.";
    }

    if ($azione === 'aggiungi_orario') {
        $tratta = (int)$_POST['tratta'];
        $tipo = (int)$_POST['tipoGiorno'];
        $luogo = $_POST['idLuogo'];
        $orario = $_POST['orario'] . ':00';

        $insT = $sxConn->prepare("INSERT IGNORE INTO tratte (numero, tipo, note) VALUES (?, ?, NULL)");
        $insT->bind_param("ii", $tratta, $tipo);
        $insT->execute();
        $insT->close();

        $stmt = $sxConn->prepare("INSERT INTO orari (orario, tipoGiorno, tratta, idLuogo) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siis", $orario, $tipo, $tratta, $luogo);
        if ($stmt->execute()) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
            $feedback = "Fermata aggiunta alla Tratta #{$tratta}.";
        } else {
            $feedback = "Errore: fermata/orario già presente su questa tratta.";
            $feedbackClass = 'w3-red';
        }
        $stmt->close();
    }

    if ($azione === 'duplica_tratta') {
        $srcTratta = (int)$_POST['src_tratta'];
        $srcTipo = (int)$_POST['src_tipo'];
        $dstTratta = (int)$_POST['dst_tratta'];
        $dstTipo = (int)$_POST['dst_tipo'];
        $offsetMin = (int)($_POST['offset_minuti'] ?? 0);

        $insT = $sxConn->prepare("INSERT IGNORE INTO tratte (numero, tipo, note) VALUES (?, ?, NULL)");
        $insT->bind_param("ii", $dstTratta, $dstTipo);
        $insT->execute();
        $insT->close();

        $stmt = $sxConn->prepare("
            INSERT IGNORE INTO orari (orario, tipoGiorno, tratta, idLuogo)
            SELECT ADDTIME(orario, SEC_TO_TIME(? * 60)), ?, ?, idLuogo
            FROM orari
            WHERE tratta = ? AND tipoGiorno = ?
        ");
        $stmt->bind_param("iiiii", $offsetMin, $dstTipo, $dstTratta, $srcTratta, $srcTipo);
        $stmt->execute();
        $copiate = $stmt->affected_rows;
        $stmt->close();

        $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        $feedback = "Duplicate {$copiate} fermate nella Tratta #{$dstTratta}.";
    }

    if ($azione === 'elimina_tratta') {
        $tratta = (int)$_POST['tratta'];
        $tipo = (int)$_POST['tipo'];
        $stmt = $sxConn->prepare("DELETE FROM tratte WHERE numero = ? AND tipo = ?");
        $stmt->bind_param("ii", $tratta, $tipo);
        $stmt->execute();
        $stmt->close();

        $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        $feedback = "Intera Tratta #{$tratta} eliminata.";
    }

    if ($azione === 'salva_utente') {
        $newUser = trim($_POST['new_username'] ?? '');
        $newPass = $_POST['new_password'] ?? '';
        $livello = (int)($_POST['livello'] ?? 1);

        if ($newUser !== '' && $newPass !== '') {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $sxConn->prepare("INSERT INTO login (username, password, livello) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), livello = VALUES(livello)");
            $stmt->bind_param("ssi", $newUser, $hash, $livello);
            $stmt->execute();
            $stmt->close();
            $feedback = "Utente '{$newUser}' salvato con successo.";
        }
    }
}

$imp = $sxConn->query("SELECT * FROM impostazioni WHERE id = 1")->fetch_assoc();
$luoghiRes = $sxConn->query("SELECT id, denominazione FROM luogo ORDER BY denominazione ASC");
$luoghi = $luoghiRes ? $luoghiRes->fetch_all(MYSQLI_ASSOC) : [];

$filtroTipo = isset($_GET['tipo']) ? (int)$_GET['tipo'] : 0;

$tratteMap = [];
$resT = $sxConn->prepare("SELECT numero, tipo, note FROM tratte WHERE tipo = ? ORDER BY numero ASC");
$resT->bind_param("i", $filtroTipo);
$resT->execute();
$rT = $resT->get_result();
while ($row = $rT->fetch_assoc()) {
    $tratteMap[(int)$row['numero']] = [
        'note' => $row['note'] ?? '',
        'fermate' => []
    ];
}
$resT->close();

$resO = $sxConn->prepare("
    SELECT O.id, O.tratta, O.orario, O.idLuogo, L.denominazione
    FROM orari O
    JOIN luogo L ON L.id = O.idLuogo
    WHERE O.tipoGiorno = ?
    ORDER BY O.tratta ASC, O.orario ASC
");
$resO->bind_param("i", $filtroTipo);
$resO->execute();
$rO = $resO->get_result();
while ($row = $rO->fetch_assoc()) {
    $num = (int)$row['tratta'];
    if (!isset($tratteMap[$num])) {
        $tratteMap[$num] = ['note' => '', 'fermate' => []];
    }
    $tratteMap[$num]['fermate'][] = $row;
}
$resO->close();
ksort($tratteMap);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Admin - ShuttleX</title>
    <?php include_once 'styles/header-include.php'; ?>
    <style>
        .status-pill { font-size: 0.8em; padding: 2px 8px; border-radius: 4px; display: inline-block; }
        .tratta-admin-card { border-top: 4px solid var(--primary-color); }
        .inline-input { padding: 4px 6px !important; height: auto !important; }
    </style>
</head>
<body class="w3-light-grey">
    <nav class="w3-white">
        <div class="pure-g" style="align-items: center;">
            <div class="pure-u-1-2">
                <a href="home.php"><h2 class="c-margin-not" style="margin-top:0;">SX <small style="font-size:0.5em;">Admin</small></h2></a>
            </div>
            <div class="pure-u-1-2" style="text-align: right;">
                <a href="home.php" class="w3-button w3-small w3-blue w3-round">
                    <i class="fa-solid fa-arrow-left"></i> Torna all'App
                </a>
            </div>
        </div>
    </nav>

    <div class="w3-container" style="max-width: 960px; margin: auto; padding-top: 16px;">
        <?php if ($feedback !== ''): ?>
            <div class="w3-panel <?= $feedbackClass ?> w3-round w3-padding">
                <b><?= htmlspecialchars($feedback) ?></b>
            </div>
        <?php endif; ?>

        <!-- BARRA SELETTORE GIORNO + NUOVA TRATTA -->
        <div class="w3-panel w3-card-4 w3-white w3-round w3-padding-16">
            <div class="w3-row-padding" style="align-items: center;">
                <div class="w3-col s12 m6">
                    <h4 class="c-primary" style="margin-top:0;"><i class="fa-solid fa-calendar-days"></i> Editor Interattivo Orari</h4>
                    <a href="admin.php?tipo=0" class="w3-button w3-round <?= $filtroTipo === 0 ? 'w3-dark-grey' : 'w3-light-grey' ?>">
                        FERIALE
                    </a>
                    <a href="admin.php?tipo=1" class="w3-button w3-round <?= $filtroTipo === 1 ? 'w3-pink' : 'w3-light-grey' ?>">
                        FESTIVO
                    </a>
                </div>
                <div class="w3-col s12 m6 w3-margin-top">
                    <button onclick="document.getElementById('boxNuovaTratta').classList.toggle('w3-hide')" class="w3-button w3-blue w3-round w3-right">
                        <i class="fa-solid fa-copy"></i> Crea / Duplica Tratta
                    </button>
                </div>
            </div>

            <!-- BOX DUPLICA / CREA TRATTA -->
            <div id="boxNuovaTratta" class="w3-hide w3-margin-top w3-padding w3-light-grey w3-round w3-border">
                <h5 style="margin-top:0;"><b>Duplica una tratta esistente (con eventuale shift orario)</b></h5>
                <form method="post" class="w3-row-padding">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="duplica_tratta">
                    <input type="hidden" name="src_tipo" value="<?= $filtroTipo ?>">

                    <div class="w3-col s6 m3">
                        <label class="w3-small">Copia da Tratta N°</label>
                        <input class="w3-input w3-border w3-round" type="number" name="src_tratta" min="1" required value="1">
                    </div>
                    <div class="w3-col s6 m3">
                        <label class="w3-small">Nuova Tratta N°</label>
                        <input class="w3-input w3-border w3-round" type="number" name="dst_tratta" min="1" required value="<?= count($tratteMap) + 1 ?>">
                    </div>
                    <div class="w3-col s6 m3">
                        <label class="w3-small">Destinazione Giorno</label>
                        <select class="w3-select w3-border w3-round" name="dst_tipo">
                            <option value="0" <?= $filtroTipo === 0 ? 'selected' : '' ?>>Feriale</option>
                            <option value="1" <?= $filtroTipo === 1 ? 'selected' : '' ?>>Festivo</option>
                        </select>
                    </div>
                    <div class="w3-col s6 m3">
                        <label class="w3-small">Sposta orari (minuti)</label>
                        <input class="w3-input w3-border w3-round" type="number" name="offset_minuti" value="0" placeholder="es. 60 o -30">
                    </div>
                    <div class="w3-col s12 w3-margin-top">
                        <button type="submit" class="w3-button w3-green w3-round">Duplica Tratta</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- CARD INTERATTIVE PER OGNI TRATTA -->
        <?php foreach ($tratteMap as $numTratta => $dati): ?>
            <div class="w3-panel w3-card-4 w3-white w3-round w3-padding-16 tratta-admin-card" id="card-tratta-<?= $numTratta ?>">
                <div class="w3-row-padding" style="align-items: center; margin-bottom: 10px;">
                    <div class="w3-col s12 m4">
                        <h4 class="c-primary" style="margin:0;">
                            <b>Tratta #<?= $numTratta ?></b>
                            <span id="nota-status-<?= $numTratta ?>" class="status-pill"></span>
                        </h4>
                    </div>
                    <div class="w3-col s9 m6">
                        <input type="text"
                               class="w3-input w3-border w3-round inline-input"
                               value="<?= htmlspecialchars($dati['note']) ?>"
                               placeholder="Nota tratta (es. Solo POLIBA, SOLO SABATO)..."
                               onchange="salvaNota(<?= $numTratta ?>, <?= $filtroTipo ?>, this.value)">
                    </div>
                    <div class="w3-col s3 m2 w3-right-align">
                        <form method="post" style="display:inline;" onsubmit="return confirm('Eliminare l\'intera Tratta #<?= $numTratta ?> e tutte le sue fermate?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="azione" value="elimina_tratta">
                            <input type="hidden" name="tratta" value="<?= $numTratta ?>">
                            <input type="hidden" name="tipo" value="<?= $filtroTipo ?>">
                            <button type="submit" class="w3-button w3-small w3-red w3-round" title="Elimina intera tratta">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="w3-responsive">
                    <table class="w3-table w3-bordered w3-small">
                        <thead>
                            <tr class="w3-light-grey">
                                <th style="width: 50%;">Fermata</th>
                                <th style="width: 25%;">Orario</th>
                                <th style="width: 25%;" class="w3-right-align">Stato / Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dati['fermate'] as $f): ?>
                                <tr id="row-orario-<?= (int)$f['id'] ?>">
                                    <td>
                                        <select class="w3-select w3-border w3-round inline-input"
                                                id="luogo-<?= (int)$f['id'] ?>"
                                                onchange="salvaRigaOrario(<?= (int)$f['id'] ?>)">
                                            <?php foreach ($luoghi as $l): ?>
                                                <option value="<?= htmlspecialchars($l['id']) ?>" <?= $f['idLuogo'] === $l['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($l['denominazione']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time"
                                               class="w3-input w3-border w3-round inline-input"
                                               id="ora-<?= (int)$f['id'] ?>"
                                               value="<?= substr($f['orario'], 0, 5) ?>"
                                               onchange="salvaRigaOrario(<?= (int)$f['id'] ?>)">
                                    </td>
                                    <td class="w3-right-align" style="vertical-align: middle;">
                                        <span id="status-<?= (int)$f['id'] ?>" class="status-pill"></span>
                                        <button type="button"
                                                onclick="eliminaRigaOrario(<?= (int)$f['id'] ?>)"
                                                class="w3-button w3-tiny w3-pale-red w3-text-red w3-hover-red w3-round"
                                                title="Rimuovi fermata">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- AGGIUNTA RAPIDA FERMATA IN CODA ALLA TRATTA -->
                <form method="post" class="w3-row-padding w3-margin-top w3-padding-small w3-light-grey w3-round" style="align-items: center;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="aggiungi_orario">
                    <input type="hidden" name="tipoGiorno" value="<?= $filtroTipo ?>">
                    <input type="hidden" name="tratta" value="<?= $numTratta ?>">

                    <div class="w3-col s6 m6">
                        <select class="w3-select w3-border w3-round inline-input" name="idLuogo" required>
                            <?php foreach ($luoghi as $l): ?>
                                <option value="<?= htmlspecialchars($l['id']) ?>"><?= htmlspecialchars($l['denominazione']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w3-col s3 m3">
                        <input class="w3-input w3-border w3-round inline-input" type="time" name="orario" required>
                    </div>
                    <div class="w3-col s3 m3">
                        <button type="submit" class="w3-button w3-small w3-blue w3-round w3-block">
                            <i class="fa-solid fa-plus"></i> Aggiungi
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>

        <!-- AGGIUNGI UNA NUOVA TRATTA DA ZERO -->
        <div class="w3-panel w3-card-4 w3-white w3-round w3-padding-16">
            <h4 class="c-primary"><i class="fa-solid fa-plus-circle"></i> Aggiungi Fermata su Nuova Tratta</h4>
            <form method="post" class="w3-row-padding">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="azione" value="aggiungi_orario">
                <input type="hidden" name="tipoGiorno" value="<?= $filtroTipo ?>">

                <div class="w3-col s4 m2">
                    <label class="w3-small">N° Tratta</label>
                    <input class="w3-input w3-border w3-round" type="number" name="tratta" min="1" max="99" required value="<?= count($tratteMap) + 1 ?>">
                </div>
                <div class="w3-col s8 m5">
                    <label class="w3-small">Fermata</label>
                    <select class="w3-select w3-border w3-round" name="idLuogo" required>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?= htmlspecialchars($l['id']) ?>"><?= htmlspecialchars($l['denominazione']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w3-col s6 m3">
                    <label class="w3-small">Orario</label>
                    <input class="w3-input w3-border w3-round" type="time" name="orario" required>
                </div>
                <div class="w3-col s6 m2">
                    <label class="w3-small">&nbsp;</label>
                    <button type="submit" class="w3-button w3-blue w3-round w3-block">Crea</button>
                </div>
            </form>
        </div>

        <!-- MANUTENZIONE E GESTIONE UTENTI -->
        <div class="w3-row-padding" style="margin: 0 -8px;">
            <div class="w3-col s12 m6">
                <div class="w3-panel w3-card-4 w3-white w3-round w3-padding-16">
                    <h4 class="c-primary"><i class="fa-solid fa-wrench"></i> Stato Manutenzione</h4>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="azione" value="manutenzione">
                        <p>
                            <label>
                                <input class="w3-check" type="checkbox" name="manutenzione_attiva" <?= !empty($imp['manutenzione_attiva']) ? 'checked' : '' ?>>
                                Mostra banner avviso
                            </label>
                        </p>
                        <p>
                            <label>
                                <input class="w3-check" type="checkbox" name="manutenzione_bloccata" <?= !empty($imp['manutenzione_bloccata']) ? 'checked' : '' ?>>
                                Blocca app agli utenti non admin
                            </label>
                        </p>
                        <p>
                            <input class="w3-input w3-border w3-round" type="text" name="manutenzione_messaggio" value="<?= htmlspecialchars($imp['manutenzione_messaggio'] ?? '') ?>">
                        </p>
                        <button type="submit" class="w3-button w3-orange w3-round">Salva Stato</button>
                    </form>
                </div>
            </div>

            <div class="w3-col s12 m6">
                <div class="w3-panel w3-card-4 w3-white w3-round w3-padding-16">
                    <h4 class="c-primary"><i class="fa-solid fa-users"></i> Crea Utente / Reset Password</h4>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="azione" value="salva_utente">
                        <p>
                            <input class="w3-input w3-border w3-round" type="text" name="new_username" required placeholder="Username">
                        </p>
                        <p>
                            <input class="w3-input w3-border w3-round" type="password" name="new_password" required placeholder="Nuova Password">
                        </p>
                        <p>
                            <select class="w3-select w3-border w3-round" name="livello">
                                <option value="1">Utente standard</option>
                                <option value="0">Amministratore</option>
                            </select>
                        </p>
                        <button type="submit" class="w3-button w3-blue w3-round">Salva Utente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;

    function mostraStato(el, ok, msg) {
        if (!el) return;
        el.textContent = msg;
        el.className = 'status-pill ' + (ok ? 'w3-pale-green w3-text-green' : 'w3-pale-red w3-text-red');
        if (ok) {
            setTimeout(() => { el.textContent = ''; el.className = 'status-pill'; }, 2500);
        }
    }

    async function salvaRigaOrario(id) {
        const idLuogo = document.getElementById('luogo-' + id).value;
        const orario = document.getElementById('ora-' + id).value;
        const badge = document.getElementById('status-' + id);

        badge.textContent = 'Salvataggio...';
        badge.className = 'status-pill w3-light-grey';

        const body = new URLSearchParams({
            ajax: '1',
            csrf_token: CSRF_TOKEN,
            azione: 'update_orario',
            id: id,
            idLuogo: idLuogo,
            orario: orario
        });

        try {
            const res = await fetch('admin.php', { method: 'POST', body });
            const data = await res.json();
            mostraStato(badge, data.ok, data.ok ? 'Salvato ✓' : (data.error || 'Errore'));
        } catch (e) {
            mostraStato(badge, false, 'Errore rete');
        }
    }

    async function salvaNota(tratta, tipo, note) {
        const badge = document.getElementById('nota-status-' + tratta);
        const body = new URLSearchParams({
            ajax: '1',
            csrf_token: CSRF_TOKEN,
            azione: 'update_nota',
            tratta: tratta,
            tipo: tipo,
            note: note
        });

        try {
            const res = await fetch('admin.php', { method: 'POST', body });
            const data = await res.json();
            mostraStato(badge, data.ok, data.ok ? 'Nota salvata ✓' : 'Errore');
        } catch (e) {
            mostraStato(badge, false, 'Errore rete');
        }
    }

    async function eliminaRigaOrario(id) {
        if (!confirm('Rimuovere questa fermata dalla tratta?')) return;

        const body = new URLSearchParams({
            ajax: '1',
            csrf_token: CSRF_TOKEN,
            azione: 'delete_orario',
            id: id
        });

        try {
            const res = await fetch('admin.php', { method: 'POST', body });
            const data = await res.json();
            if (data.ok) {
                const row = document.getElementById('row-orario-' + id);
                if (row) row.remove();
            } else {
                alert('Errore durante l\'eliminazione.');
            }
        } catch (e) {
            alert('Errore di rete.');
        }
    }
    </script>

    <?php include_once 'footer.php'; ?>