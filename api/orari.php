<?php
include_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Non autenticato']);
    exit;
}

// Stato manutenzione
$manutenzione = ['attiva' => false, 'bloccata' => false, 'messaggio' => ''];
$resMan = $sxConn->query("SELECT manutenzione_attiva, manutenzione_bloccata, manutenzione_messaggio FROM impostazioni WHERE id = 1 LIMIT 1");
if ($resMan && $rowM = $resMan->fetch_assoc()) {
    $manutenzione = [
        'attiva' => (int)$rowM['manutenzione_attiva'] === 1,
        'bloccata' => (int)$rowM['manutenzione_bloccata'] === 1 && !isAdmin(),
        'messaggio' => $rowM['manutenzione_messaggio']
    ];
}

// Ultima modifica
$ultimaModifica = '';
$resMod = $sxConn->query("SELECT DATE_FORMAT(ultimaModifica, '%d/%m/%Y %H:%i') AS fmt FROM modifica WHERE id = 1 LIMIT 1");
if ($resMod && $rowMod = $resMod->fetch_assoc()) {
    $ultimaModifica = $rowMod['fmt'];
}

// Elenco fermate
$luoghi = [];
$resLuoghi = $sxConn->query("SELECT id, denominazione FROM luogo ORDER BY denominazione ASC");
if ($resLuoghi) {
    while ($l = $resLuoghi->fetch_assoc()) {
        $luoghi[] = $l;
    }
}

// Tratte e fermate ordinate esplicitamente per `sequenza`
$tratteMap = [];
$resT = $sxConn->query("SELECT id, numero, giorni_attivi, note FROM tratte ORDER BY numero ASC, id ASC");
if ($resT) {
    while ($t = $resT->fetch_assoc()) {
        $id = (int)$t['id'];
        $tratteMap[$id] = [
            'id' => $id,
            'numero' => (int)$t['numero'],
            'giorni_attivi' => (int)$t['giorni_attivi'],
            'note' => $t['note'] ?? '',
            'fermate' => []
        ];
    }
}

$resO = $sxConn->query("
    SELECT O.tratta_id, O.sequenza, O.idLuogo, O.orario, L.denominazione
    FROM orari O
    JOIN luogo L ON L.id = O.idLuogo
    ORDER BY O.tratta_id ASC, O.sequenza ASC
");
if ($resO) {
    while ($o = $resO->fetch_assoc()) {
        $tid = (int)$o['tratta_id'];
        if (isset($tratteMap[$tid])) {
            $tratteMap[$tid]['fermate'][] = [
                'sequenza' => (int)$o['sequenza'],
                'idLuogo' => $o['idLuogo'],
                'denominazione' => $o['denominazione'],
                'orario' => substr($o['orario'], 0, 5)
            ];
        }
    }
}

echo json_encode([
    'ok' => true,
    'is_admin' => isAdmin(),
    'ultima_modifica' => $ultimaModifica,
    'manutenzione' => $manutenzione,
    'luoghi' => $luoghi,
    'tratte' => array_values($tratteMap)
], JSON_UNESCAPED_UNICODE);