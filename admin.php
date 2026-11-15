<?php
include_once __DIR__ . '/auth.php';
requireAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// --- ENDPOINT AJAX PER MODIFICHE INLINE ---
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
            echo json_encode(['ok' => false, 'error' => 'Errore salvataggio']);
        }
        exit;
    }

    if ($azione === 'move_sequenza') {
        $orarioId = (int)($_POST['orario_id'] ?? 0);
        $trattaId = (int)($_POST['tratta_id'] ?? 0);
        $dir = ($_POST['direzione'] ?? '') === 'up' ? -1 : 1;

        $curr = $sxConn->query("SELECT id, sequenza FROM orari WHERE id = {$orarioId} AND tratta_id = {$trattaId}")->fetch_assoc();
        if ($curr) {
            $seqA = (int)$curr['sequenza'];
            $op = $dir < 0 ? "< {$seqA} ORDER BY sequenza DESC" : "> {$seqA} ORDER BY sequenza ASC";
            $adj = $sxConn->query("SELECT id, sequenza FROM orari WHERE tratta_id = {$trattaId} AND sequenza {$op} LIMIT 1")->fetch_assoc();

            if ($adj) {
                $idB = (int)$adj['id'];
                $seqB = (int)$adj['sequenza'];
                $sxConn->begin_transaction();
                $sxConn->query("UPDATE orari SET sequenza = 0 WHERE id = {$orarioId}");
                $sxConn->query("UPDATE orari SET sequenza = {$seqA} WHERE id = {$idB}");
                $sxConn->query("UPDATE orari SET sequenza = {$seqB} WHERE id = {$orarioId}");
                $sxConn->commit();
                $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
                echo json_encode(['ok' => true, 'swapped_with' => $idB]);
                exit;
            }
        }
        echo json_encode(['ok' => false, 'error' => 'Impossibile spostare']);
        exit;
    }

    if ($azione === 'update_tratta_meta') {
        $trattaId = (int)($_POST['tratta_id'] ?? 0);
        $giorni = (int)($_POST['giorni_attivi'] ?? 0);
        $nota = trim($_POST['note'] ?? '');
        $notaVal = ($nota === '') ? null : $nota;

        $stmt = $sxConn->prepare("UPDATE tratte SET giorni_attivi = ?, note = ? WHERE id = ?");
        $stmt->bind_param("isi", $giorni, $notaVal, $trattaId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        }
        echo json_encode(['ok' => $ok, 'warning' => $giorni === 0 ? 'Nessun giorno attivo' : '']);
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

// --- AZIONI FORM ---
$feedback = '';
$feedbackType = 'alert-success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("Token CSRF non valido.");
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

    if ($azione === 'aggiungi_fermata') {
        $trattaId = (int)$_POST['tratta_id'];
        $luogo = $_POST['idLuogo'];
        $orario = $_POST['orario'] . ':00';

        $resMax = $sxConn->query("SELECT COALESCE(MAX(sequenza), 0) + 1 AS next_seq FROM orari WHERE tratta_id = {$trattaId}");
        $nextSeq = (int)$resMax->fetch_assoc()['next_seq'];

        $stmt = $sxConn->prepare("INSERT INTO orari (tratta_id, sequenza, idLuogo, orario) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $trattaId, $nextSeq, $luogo, $orario);
        if ($stmt->execute()) {
            $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
            $feedback = "Fermata aggiunta.";
        } else {
            $feedback = "Errore nell'inserimento della fermata.";
            $feedbackType = 'alert-danger';
        }
        $stmt->close();
    }

    if ($azione === 'crea_tratta') {
        $numero = (int)$_POST['numero'];
        $giorni = 0;
        if (!empty($_POST['giorni'])) {
            foreach ($_POST['giorni'] as $g) {
                $giorni |= (int)$g;
            }
        }
        if ($giorni === 0) $giorni = 31;
        $nota = trim($_POST['note'] ?? '');
        $notaVal = $nota === '' ? null : $nota;

        $stmt = $sxConn->prepare("INSERT INTO tratte (numero, giorni_attivi, note) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $numero, $giorni, $notaVal);
        $stmt->execute();
        $newTrattaId = $stmt->insert_id;
        $stmt->close();

        $copiaDa = (int)($_POST['copia_da'] ?? 0);
        $offset = (int)($_POST['offset_minuti'] ?? 0);
        if ($copiaDa > 0 && $newTrattaId > 0) {
            $stmtC = $sxConn->prepare("
                INSERT INTO orari (tratta_id, sequenza, idLuogo, orario)
                SELECT ?, sequenza, idLuogo, ADDTIME(orario, SEC_TO_TIME(? * 60))
                FROM orari WHERE tratta_id = ? ORDER BY sequenza ASC
            ");
            $stmtC->bind_param("iii", $newTrattaId, $offset, $copiaDa);
            $stmtC->execute();
            $stmtC->close();
        }

        $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        $feedback = "Tratta #{$numero} creata.";
    }

    if ($azione === 'elimina_tratta') {
        $trattaId = (int)$_POST['tratta_id'];
        $stmt = $sxConn->prepare("DELETE FROM tratte WHERE id = ?");
        $stmt->bind_param("i", $trattaId);
        $stmt->execute();
        $stmt->close();
        $sxConn->query("UPDATE modifica SET ultimaModifica = NOW() WHERE id = 1");
        $feedback = "Tratta eliminata.";
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

function descriviGiorni(int $mask): string {
    $parti = [];
    if (($mask & 31) === 31) $parti[] = 'Lun-Ven';
    elseif ($mask & 31) $parti[] = 'Feriali parziali';
    if ($mask & 32) $parti[] = 'Sab';
    if ($mask & 64) $parti[] = 'Dom';
    return $parti ? implode(', ', $parti) : 'Disattiva';
}

$imp = $sxConn->query("SELECT * FROM impostazioni WHERE id = 1")->fetch_assoc();
$luoghi = $sxConn->query("SELECT id, denominazione FROM luogo ORDER BY denominazione ASC")->fetch_all(MYSQLI_ASSOC);
$tratte = $sxConn->query("SELECT id, numero, giorni_attivi, note FROM tratte ORDER BY numero ASC, id ASC")->fetch_all(MYSQLI_ASSOC);

$orariByTratta = [];
$resO = $sxConn->query("SELECT id, tratta_id, sequenza, idLuogo, orario FROM orari ORDER BY tratta_id ASC, sequenza ASC");
while ($o = $resO->fetch_assoc()) {
    $orariByTratta[(int)$o['tratta_id']][] = $o;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <title>Admin - ShuttleX</title>
    <?php include_once __DIR__ . '/styles/header-include.php'; ?>
</head>
<body>
    <header class="app-topbar">
        <div class="container topbar-inner">
            <a href="home.php" class="brand-logo">
                <span class="brand-badge"><i class="fa-solid fa-sliders" aria-hidden="true"></i> SX</span>
                <span>Admin</span>
            </a>
            <a href="home.php" class="btn btn-chip btn-sm">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Torna all'App
            </a>
        </div>
    </header>

    <main id="main-content" class="container">
        <?php if ($feedback !== ''): ?>
            <div class="alert <?= $feedbackType ?>" role="status" aria-live="polite">
                <strong><?= htmlspecialchars($feedback) ?></strong>
            </div>
        <?php endif; ?>

        <!-- NUOVA TRATTA / DUPLICA -->
        <section class="card">
            <h2 style="margin: 0 0 12px; font-size: 1.05rem; color: var(--primary);">
                <i class="fa-solid fa-plus-circle" aria-hidden="true"></i> Nuova Tratta (o Duplica Esistente)
            </h2>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="azione" value="crea_tratta">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; margin-bottom: 10px;">
                    <div class="field-group">
                        <label class="field-label">N° Tratta</label>
                        <input class="form-control" type="number" name="numero" min="1" required value="<?= count($tratte) + 1 ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Copia fermate da (opz.)</label>
                        <select class="form-control" name="copia_da">
                            <option value="0">-- Vuota --</option>
                            <?php foreach ($tratte as $t): ?>
                                <option value="<?= (int)$t['id'] ?>">
                                    Tratta #<?= (int)$t['numero'] ?> (<?= descriviGiorni((int)$t['giorni_attivi']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Sposta orari (min)</label>
                        <input class="form-control" type="number" name="offset_minuti" value="0">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Nota (opzionale)</label>
                        <input class="form-control" type="text" name="note" placeholder="es. Solo POLIBA">
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;">
                    <div style="display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.9rem;">
                        <label><input type="checkbox" name="giorni[]" value="31" checked> Lun–Ven</label>
                        <label><input type="checkbox" name="giorni[]" value="32"> Sabato</label>
                        <label><input type="checkbox" name="giorni[]" value="64"> Domenica</label>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Crea Tratta</button>
                </div>
            </form>
        </section>

        <!-- EDITOR INTERATTIVO PER OGNI TRATTA -->
        <?php foreach ($tratte as $t):
            $tid = (int)$t['id'];
            $g = (int)$t['giorni_attivi'];
            $fermate = $orariByTratta[$tid] ?? [];
        ?>
            <article class="card" id="tratta-card-<?= $tid ?>">
                <div class="tratta-header">
                    <h2 class="tratta-title">
                        <span>Tratta #<?= (int)$t['numero'] ?></span>
                        <span id="meta-status-<?= $tid ?>" class="status-pill" aria-live="polite"></span>
                    </h2>

                    <form method="post" onsubmit="return confirm('Eliminare definitivamente questa tratta?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="azione" value="elimina_tratta">
                        <input type="hidden" name="tratta_id" value="<?= $tid ?>">
                        <button type="submit" class="btn btn-danger btn-sm" aria-label="Elimina tratta">
                            <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 12px; padding-bottom: 10px; border-bottom: 1px solid var(--border); font-size: 0.88rem;">
                    <label><input type="checkbox" class="day-cb-<?= $tid ?>" value="31" <?= ($g & 31) === 31 ? 'checked' : '' ?> onchange="salvaMetaTratta(<?= $tid ?>)"> Lun–Ven</label>
                    <label><input type="checkbox" class="day-cb-<?= $tid ?>" value="32" <?= ($g & 32) ? 'checked' : '' ?> onchange="salvaMetaTratta(<?= $tid ?>)"> Sabato</label>
                    <label><input type="checkbox" class="day-cb-<?= $tid ?>" value="64" <?= ($g & 64) ? 'checked' : '' ?> onchange="salvaMetaTratta(<?= $tid ?>)"> Domenica</label>

                    <input type="text"
                           id="nota-<?= $tid ?>"
                           class="form-control"
                           style="min-height: 36px; padding: 4px 10px; flex: 1 1 180px;"
                           value="<?= htmlspecialchars($t['note'] ?? '') ?>"
                           placeholder="Nota tratta..."
                           onchange="salvaMetaTratta(<?= $tid ?>)">
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 68px;">Ordine</th>
                                <th>Fermata</th>
                                <th style="width: 112px;">Orario</th>
                                <th style="width: 90px; text-align: right;">Azioni</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-tratta-<?= $tid ?>">
                            <?php foreach ($fermate as $f):
                                $oid = (int)$f['id'];
                            ?>
                                <tr id="row-orario-<?= $oid ?>">
                                    <td style="white-space: nowrap;">
                                        <button type="button" onclick="spostaRiga(<?= $tid ?>, <?= $oid ?>, 'up')" class="btn btn-chip btn-sm" style="padding: 2px 7px; min-height: 30px;" title="Sposta su">↑</button>
                                        <button type="button" onclick="spostaRiga(<?= $tid ?>, <?= $oid ?>, 'down')" class="btn btn-chip btn-sm" style="padding: 2px 7px; min-height: 30px;" title="Sposta giù">↓</button>
                                    </td>
                                    <td>
                                        <select class="form-control" style="min-height: 38px; padding: 4px 8px;" id="luogo-<?= $oid ?>" onchange="salvaRigaOrario(<?= $oid ?>)">
                                            <?php foreach ($luoghi as $l): ?>
                                                <option value="<?= htmlspecialchars($l['id']) ?>" <?= $f['idLuogo'] === $l['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($l['denominazione']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="time" class="form-control" style="min-height: 38px; padding: 4px 8px;" id="ora-<?= $oid ?>" value="<?= substr($f['orario'], 0, 5) ?>" onchange="salvaRigaOrario(<?= $oid ?>)">
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <span id="status-<?= $oid ?>" class="status-pill"></span>
                                        <button type="button" onclick="eliminaRigaOrario(<?= $oid ?>)" class="btn btn-danger btn-sm" style="min-height: 32px; padding: 2px 8px;" title="Rimuovi">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <form method="post" class="admin-add-row">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="aggiungi_fermata">
                    <input type="hidden" name="tratta_id" value="<?= $tid ?>">

                    <select class="form-control" style="min-height: 38px;" name="idLuogo" required>
                        <?php foreach ($luoghi as $l): ?>
                            <option value="<?= htmlspecialchars($l['id']) ?>"><?= htmlspecialchars($l['denominazione']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input class="form-control" style="min-height: 38px;" type="time" name="orario" required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus"></i> Aggiungi
                    </button>
                </form>
            </article>
        <?php endforeach; ?>

        <!-- MANUTENZIONE & UTENTI -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
            <section class="card" style="margin-bottom: 0;">
                <h2 style="margin: 0 0 12px; font-size: 1.05rem; color: var(--primary);">
                    <i class="fa-solid fa-wrench"></i> Manutenzione
                </h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="manutenzione">
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <input type="checkbox" name="manutenzione_attiva" <?= !empty($imp['manutenzione_attiva']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        Mostra banner avviso
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <input type="checkbox" name="manutenzione_bloccata" <?= !empty($imp['manutenzione_bloccata']) ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                        Blocca consultazione orari
                    </label>
                    <div class="field-group" style="margin-bottom: 12px;">
                        <input class="form-control" type="text" name="manutenzione_messaggio" value="<?= htmlspecialchars($imp['manutenzione_messaggio'] ?? '') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Salva Stato</button>
                </form>
            </section>

            <section class="card" style="margin-bottom: 0;">
                <h2 style="margin: 0 0 12px; font-size: 1.05rem; color: var(--primary);">
                    <i class="fa-solid fa-users"></i> Utenti
                </h2>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="azione" value="salva_utente">
                    <div class="field-group" style="margin-bottom: 10px;">
                        <input class="form-control" type="text" name="new_username" required placeholder="Username">
                    </div>
                    <div class="field-group" style="margin-bottom: 10px;">
                        <input class="form-control" type="password" name="new_password" required placeholder="Password">
                    </div>
                    <div class="field-group" style="margin-bottom: 12px;">
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
            setTimeout(() => { el.textContent = ''; el.style.background = 'transparent'; }, 2200);
        }
    }

    async function salvaRigaOrario(id) {
        const idLuogo = document.getElementById('luogo-' + id).value;
        const orario = document.getElementById('ora-' + id).value;
        const badge = document.getElementById('status-' + id);

        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'update_orario', id, idLuogo, orario
        });

        const res = await fetch('admin.php', { method: 'POST', body });
        const data = await res.json();
        mostraStato(badge, data.ok, data.ok ? 'Salvato' : (data.error || 'Errore'));
    }

    async function spostaRiga(trattaId, orarioId, direzione) {
        const row = document.getElementById('row-orario-' + orarioId);
        if (!row) return;
        const target = direzione === 'up' ? row.previousElementSibling : row.nextElementSibling;
        if (!target) return;

        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'move_sequenza',
            tratta_id: trattaId, orario_id: orarioId, direzione
        });

        const res = await fetch('admin.php', { method: 'POST', body });
        const data = await res.json();
        if (data.ok) {
            if (direzione === 'up') {
                row.parentNode.insertBefore(row, target);
            } else {
                row.parentNode.insertBefore(target, row);
            }
            mostraStato(document.getElementById('status-' + orarioId), true, 'Spostato');
        }
    }

    async function salvaMetaTratta(trattaId) {
        let mask = 0;
        document.querySelectorAll('.day-cb-' + trattaId + ':checked').forEach((cb) => {
            mask |= Number(cb.value);
        });
        const note = document.getElementById('nota-' + trattaId).value;
        const badge = document.getElementById('meta-status-' + trattaId);

        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'update_tratta_meta',
            tratta_id: trattaId, giorni_attivi: mask, note
        });

        const res = await fetch('admin.php', { method: 'POST', body });
        const data = await res.json();
        if (data.warning) {
            mostraStato(badge, false, data.warning);
        } else {
            mostraStato(badge, data.ok, data.ok ? 'Aggiornato' : 'Errore');
        }
    }

    async function eliminaRigaOrario(id) {
        if (!confirm('Rimuovere questa fermata?')) return;
        const body = new URLSearchParams({
            ajax: '1', csrf_token: CSRF_TOKEN, azione: 'delete_orario', id
        });
        const res = await fetch('admin.php', { method: 'POST', body });
        const data = await res.json();
        if (data.ok) document.getElementById('row-orario-' + id)?.remove();
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