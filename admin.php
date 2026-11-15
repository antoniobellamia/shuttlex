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
        $stmt->close();

        if ($ok) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Orario/fermata già presente']);
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
$feedbackType = 'alert-success';

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
            $feedbackType = 'alert-danger';
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
            $feedback = "Utente '{$newUser}' salvato.";
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
</head>
<body>
    <header class="app-header">
        <div class="container">
            <div class="topbar" style="margin-bottom: 0;">
                <a href="home.php" class="brand-logo">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    <span>SX <span class="sub">Pannello Admin</span></span>
                </a>
                <a href="home.php" class="btn btn-chip btn-sm">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Torna all'App
                </a>
            </div>
        </div>
    </header>

    <main id="main-content" class="container">
        <?php if ($feedback !== ''): ?>
            <div class="alert <?= $feedbackType ?>" role="status" aria-live="polite">
                <strong><?= htmlspecialchars($feedback) ?></strong>
            </div>
        <?php endif; ?>

        <!-- SELETTORE GIORNO E DUPLICAZIONE -->
        <section class="card">
            <div style="display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px;">
                <div style="display: flex; gap: 8px;">
                    <a href="admin.php?tipo=0" class="btn btn-sm <?= $filtroTipo === 0 ? 'btn-primary' : 'btn-chip' ?>">
                        Feriale
                    </a>
                    <a href="admin.php?tipo=1" class="btn btn-sm <?= $filtroTipo === 1 ? 'btn-primary' : 'btn-chip' ?>">
                        Festivo
                    </a>
                </div>
                <button type="button"
                        onclick="document.getElementById('boxDuplica').hidden = !document.getElementById('boxDuplica').hidden"
                        class="btn btn-chip btn-sm">
                    <i class="fa-solid fa-copy" aria-hidden="true"></i> Duplica Tratta
                </button>
            </div>

            <div id="boxDuplica" hidden style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border);">
                <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="duplica_tratta">
                    <input type="hidden" name="src_tipo" value="<?= $filtroTipo ?>">

                    <div class="field-group">
                        <label class="field-label">Copia da Tratta N°</label>
                        <input class="form-control" type="number" name="src_tratta" min="1" required value="1">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Nuova Tratta N°</label>
                        <input class="form-control" type="number" name="dst_tratta" min="1" required value="<?= count($tratteMap) + 1 ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Giorno Destinazione</label>
                        <select class="form-control" name="dst_tipo">
                            <option value="0" <?= $filtroTipo === 0 ? 'selected' : '' ?>>Feriale</option>
                            <option value="1" <?= $filtroTipo === 1 ? 'selected' : '' ?>>Festivo</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Sposta orari (min)</label>
                        <input class="form-control" type="number" name="offset_minuti" value="0">
                    </div>
                    <div class="field-group" style="justify-content: flex-end;">
                        <button type="submit" class="btn btn-primary">Duplica</button>
                    </div>
                </form>
            </div>
        </section>

        <!-- EDITOR INTERATTIVO TRATTE -->
        <?php foreach ($tratteMap as $numTratta => $dati): ?>
            <article class="card" id="card-tratta-<?= $numTratta ?>">
                <div class="tratta-header">
                    <h2 class="tratta-title">
                        <span>Tratta #<?= $numTratta ?></span>
                        <span id="nota-status-<?= $numTratta ?>" class="status-pill" aria-live="polite"></span>
                    </h2>

                    <div style="display: flex; gap: 8px; flex: 1 1 260px; justify-content: flex-end;">
                        <input type="text"
                               class="form-control"
                               style="min-height: 38px; padding: 6px 10px;"
                               aria-label="Nota tratta <?= $numTratta ?>"
                               value="<?= htmlspecialchars($dati['note']) ?>"
                               placeholder="Nota (es. Solo POLIBA)..."
                               onchange="salvaNota(<?= $numTratta ?>, <?= $filtroTipo ?>, this.value)">

                        <form method="post" onsubmit="return confirm('Eliminare l\'intera Tratta #<?= $numTratta ?>?');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="azione" value="elimina_tratta">
                            <input type="hidden" name="tratta" value="<?= $numTratta ?>">
                            <input type="hidden" name="tipo" value="<?= $filtroTipo ?>">
                            <button type="submit" class="btn btn-danger btn-sm" aria-label="Elimina intera tratta <?= $numTratta ?>">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Fermata</th>
                                <th style="width: 120px;">Orario</th>
                                <th style="width: 110px; text-align: right;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dati['fermate'] as $f): ?>
                                <tr id="row-orario-<?= (int)$f['id'] ?>">
                                    <td>
                                        <select class="form-control"
                                                style="min-height: 40px; padding: 6px 10px;"
                                                aria-label="Fermata"
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
                                               class="form-control"
                                               style="min-height: 40px; padding: 6px 8px;"
                                               aria-label="Orario"
                                               id="ora-<?= (int)$f['id'] ?>"
                                               value="<?= substr($f['orario'], 0, 5) ?>"
                                               onchange="salvaRigaOrario(<?= (int)$f['id'] ?>)">
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <span id="status-<?= (int)$f['id'] ?>" class="status-pill" aria-live="polite"></span>
                                        <button type="button"
                                                onclick="eliminaRigaOrario(<?= (int)$f['id'] ?>)"
                                                class="btn btn-danger btn-sm"
                                                aria-label="Elimina fermata">
                                            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- AGGIUNGI FERMATA IN CODA -->
                <form method="post" style="display: grid; grid-template-columns: 1fr 120px auto; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px dashed var(--border);">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="aggiungi_orario">
                    <input type="hidden" name="tipoGiorno" value="<?= $filtroTipo ?>">
                    <input type="hidden" name="tratta" value="<?= $numTratta ?>">

                    <select class="form-control" style="min-height: 40px;" name="idLuogo" aria-label="Nuova fermata" required>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?= htmlspecialchars($l['id']) ?>"><?= htmlspecialchars($l['denominazione']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" style="min-height: 40px;" type="time" name="orario" aria-label="Orario nuova fermata" required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus" aria-hidden="true"></i> Aggiungi
                    </button>
                </form>
            </article>
        <?php endforeach; ?>

        <!-- CREA NUOVA TRATTA -->
        <section class="card">
            <h2 style="margin: 0 0 12px; font-size: 1.05rem; color: var(--brand-dark);">
                <i class="fa-solid fa-plus-circle" aria-hidden="true"></i> Crea Nuova Tratta
            </h2>
            <form method="post" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="azione" value="aggiungi_orario">
                <input type="hidden" name="tipoGiorno" value="<?= $filtroTipo ?>">

                <div class="field-group">
                    <label class="field-label">N° Tratta</label>
                    <input class="form-control" type="number" name="tratta" min="1" max="99" required value="<?= count($tratteMap) + 1 ?>">
                </div>
                <div class="field-group">
                    <label class="field-label">Prima Fermata</label>
                    <select class="form-control" name="idLuogo" required>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?= htmlspecialchars($l['id']) ?>"><?= htmlspecialchars($l['denominazione']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label class="field-label">Orario</label>
                    <input class="form-control" type="time" name="orario" required>
                </div>
                <div class="field-group" style="justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary">Crea Tratta</button>
                </div>
            </form>
        </section>

        <!-- MANUTENZIONE & UTENTI -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <section class="card" style="margin-bottom: 0;">
                <h2 style="margin: 0 0 12px; font-size: 1.05rem; color: var(--brand-dark);">
                    <i class="fa-solid fa-wrench" aria-hidden="true"></i> Manutenzione
                </h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="manutenzione">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; min-height: 36px; cursor: pointer;">
                        <input type="checkbox" name="manutenzione_attiva" <?= !empty($imp['manutenzione_attiva']) ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                        Mostra banner avviso
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px; min-height: 36px; cursor: pointer;">
                        <input type="checkbox" name="manutenzione_bloccata" <?= !empty($imp['manutenzione_bloccata']) ? 'checked' : '' ?> style="width: 20px; height: 20px;">
                        Blocca consultazione orari
                    </label>
                    <div class="field-group" style="margin-bottom: 12px;">
                        <label class="field-label">Messaggio banner</label>
                        <input class="form-control" type="text" name="manutenzione_messaggio" value="<?= htmlspecialchars($imp['manutenzione_messaggio'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Salva Stato</button>
                </form>
            </section>

            <section class="card" style="margin-bottom: 0;">
                <h2 style="margin: 0 0 12px; font-size: 1.05rem; color: var(--brand-dark);">
                    <i class="fa-solid fa-users" aria-hidden="true"></i> Gestione Utenti
                </h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="salva_utente">
                    <div class="field-group" style="margin-bottom: 10px;">
                        <label class="field-label">Username</label>
                        <input class="form-control" type="text" name="new_username" required placeholder="es. mario">
                    </div>
                    <div class="field-group" style="margin-bottom: 10px;">
                        <label class="field-label">Password</label>
                        <input class="form-control" type="password" name="new_password" required placeholder="Nuova password">
                    </div>
                    <div class="field-group" style="margin-bottom: 12px;">
                        <label class="field-label">Ruolo</label>
                        <select class="form-control" name="livello">
                            <option value="1">Utente standard</option>
                            <option value="0">Amministratore</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Salva Utente</button>
                </form>
            </section>
        </div>
    </main>

    <script>
    const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;

    function mostraStato(el, ok, msg) {
        if (!el) return;
        el.textContent = msg;
        el.style.background = ok ? 'var(--success-bg)' : 'var(--danger-bg)';
        el.style.color = ok ? 'var(--success-text)' : 'var(--danger-text)';
        if (ok) {
            setTimeout(() => { el.textContent = ''; el.style.background = 'transparent'; }, 2500);
        }
    }

    async function salvaRigaOrario(id) {
        const idLuogo = document.getElementById('luogo-' + id).value;
        const orario = document.getElementById('ora-' + id).value;
        const badge = document.getElementById('status-' + id);

        badge.textContent = '...';
        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'update_orario', id, idLuogo, orario
        });

        try {
            const res = await fetch('admin.php', { method: 'POST', body });
            const data = await res.json();
            mostraStato(badge, data.ok, data.ok ? 'Salvato' : (data.error || 'Errore'));
        } catch (e) {
            mostraStato(badge, false, 'Errore rete');
        }
    }

    async function salvaNota(tratta, tipo, note) {
        const badge = document.getElementById('nota-status-' + tratta);
        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'update_nota', tratta, tipo, note
        });

        try {
            const res = await fetch('admin.php', { method: 'POST', body });
            const data = await res.json();
            mostraStato(badge, data.ok, data.ok ? 'Nota salvata' : 'Errore');
        } catch (e) {
            mostraStato(badge, false, 'Errore rete');
        }
    }

    async function eliminaRigaOrario(id) {
        if (!confirm('Rimuovere questa fermata dalla tratta?')) return;
        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'delete_orario', id
        });

        try {
            const res = await fetch('admin.php', { method: 'POST', body });
            const data = await res.json();
            if (data.ok) {
                document.getElementById('row-orario-' + id)?.remove();
            } else {
                alert('Errore durante l\'eliminazione.');
            }
        } catch (e) {
            alert('Errore di rete.');
        }
    }
    </script>

    <footer class="app-footer" style="margin-top: 24px;">
        <div class="footer-inner">
            <nav class="footer-links">
                <a href="home.php">Torna all'App</a>
                <a href="logout.php">Esci</a>
            </nav>
        </div>
    </footer>
</body>
</html>