<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../validate_lib.php';    // pruef_pat_blob(), WRAP_RC_RE
require_once __DIR__ . '/../standbuch_lib.php';   // Blätter mit den Blöcken
require_once __DIR__ . '/../spur_lib.php';        // die Übernahme der Spuren (06e)

/**
 * api/format_anheben.php — der Anhebelauf (KA-06b-4, E-KA-40, Technik 4.42).
 *
 * WOZU. Mit umgelegter Weiche schreibt der Browser jeden Block im gebundenen
 * Format (`edk2:`). Was davor geschrieben wurde, liegt weiter als `edk1:`
 * (oder ohne Kennung) da — an nichts gebunden, vertauschbar und
 * zurücklegbar. Dieser Weg hebt den Altbestand eines Kontos still nach der
 * Anmeldung: die Patientenblöcke und die privaten Teile von Kontopaar und
 * Rückweg. Die Passworthülle hebt die stille Umstellung beim Anmelden, die
 * Wiederherstellungshülle das nächste Erneuern; Gerätedaten gehören zu 06e.
 *
 * DER SERVER KANN NICHTS HEBEN — er hat keinen Schlüssel. Er nennt, was noch
 * im alten Format liegt, und schreibt, was der Browser gehoben hat, wenn
 * dort noch GENAU der alte Block steht (`blob_alt`, wie `pat_anheben.php`).
 * Hat ein anderes Fenster dazwischen gespeichert, bleibt dessen Stand.
 *
 *   GET  -> {missions: [{id, client_ref, pat_blob}], offen, rest_je_blatt: [16],
 *            kp: "edk1:…"|null, rw: "edk1:…"|null}
 *        Höchstens GRENZE Einsätze je Aufruf, auch aus dem Papierkorb.
 *        `rest_je_blatt`: wie viele Blöcke im alten Format je Blatt des
 *        Standbuchs noch liegen — ein Blatt mit 0 darf der Browser schließen.
 *   POST {missions: [{id, pat_blob, blob_alt}], kp?: {neu, alt}, rw?: {neu, alt},
 *         blaetter?: […]}
 *        -> {ok, angehoben, uebersprungen} · 400 format · 409 blatt
 *        Angenommen wird NUR das gebundene Format — dieser Weg hebt, er
 *        schreibt nichts Altes zurück.
 *
 * DIE GERÄTEDATEN DER FASSUNG 1 (06e, KA-18, E-KA-97). Was ein Gerät vor
 * Web 25.0.0 ohne Herkunftsnachweis verschlossen hat, hebt der Browser als
 * Konto, mit dem Kennzeichen „übernommen am …" (E-KA-81) — er ist danach
 * der einzige Leser der Fassung 1:
 *
 *   GET ?geraete=1 -> {einsaetze: [{id, client_ref, started_ts, final, huelle, pruef}],
 *                      spuren: [{art, id, ref, final, stuecke: [{…, huelle, pruef}]}],
 *                      offen: {einsaetze, spuren}}
 *        Höchstens GRENZE Einsätze und GRENZE_SPUREN Spuren je Aufruf.
 *        `pruef`: SHA-256 der Hülle, wie sie liegt.
 *   POST {einsaetze: [{id, huelle, alt}]} oder {spur: {art, id, stuecke: [{seq_from, huelle, alt}]}}
 *        -> {ok, angehoben, uebersprungen} · 400 format
 *        Angenommen wird NUR Fassung 2. Getauscht wird nur, wo noch GENAU
 *        die gelesene Hülle liegt (`alt` = `pruef`); eine Spur ganz oder gar
 *        nicht (`spur2_uebernahme_tauschen()`).
 */

const FORMAT_ANHEBEN_GRENZE = 200;
const FORMAT_ANHEBEN_GRENZE_SPUREN = 10;

/** Eine Gerätehülle der Fassung 2 in Base64, in der Form geprüft. */
function fa_huelle2($wert, int $max): ?string
{
    $h = pruef_huelle($wert, $max, 'huelle', new Pruefliste());
    return ($h !== null && ord($h[0]) === 2) ? (string)$wert : null;
}

/** Die Nummer des Blatts zu einer Kennung — dieselbe Rechnung wie
 *  `blattNr()` in assets/standbuch.js: SHA-256("nadoku-stand-1" ‖ ref),
 *  erstes Byte, obere vier Bit. */
function fa_blatt(string $ref): int
{
    return ord(hash('sha256', 'nadoku-stand-1' . $ref, true)[0]) >> 4;
}

/** Ein Text im alten Format: `edk1:` oder ohne Kennung — nie `edk2:`. */
const FA_ALT_WHERE = "pat_blob IS NOT NULL AND pat_blob <> '' AND pat_blob NOT LIKE 'edk2:%'";

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['geraete'] ?? '') === '1') {
    $st = $pdo->prepare("SELECT id, client_ref, started_at, final, ort_huelle FROM missions
                          WHERE user_id = ? AND ort_huelle IS NOT NULL AND SUBSTRING(ort_huelle, 1, 1) = X'01'
                          ORDER BY id LIMIT " . FORMAT_ANHEBEN_GRENZE);
    $st->execute([$userId]);
    $einsaetze = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $einsaetze[] = ['id' => (int)$m['id'], 'client_ref' => (string)$m['client_ref'],
                        'started_ts' => $m['started_at'] !== null ? (int)strtotime($m['started_at'] . ' UTC') : null,
                        'final' => (int)$m['final'] === 1,
                        'huelle' => base64_encode((string)$m['ort_huelle']),
                        'pruef' => hash('sha256', (string)$m['ort_huelle'])];
    }
    $n = $pdo->prepare("SELECT COUNT(*) FROM missions WHERE user_id = ? AND ort_huelle IS NOT NULL
                          AND SUBSTRING(ort_huelle, 1, 1) = X'01'");
    $n->execute([$userId]);
    json_out(['einsaetze' => $einsaetze,
              'spuren' => spur2_uebernahme_liste($pdo, $userId, FORMAT_ANHEBEN_GRENZE_SPUREN),
              'offen' => ['einsaetze' => (int)$n->fetchColumn(),
                          'spuren' => spur2_uebernahme_zahl($pdo, $userId)]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $st = $pdo->prepare('SELECT id, client_ref, pat_blob FROM missions
                          WHERE user_id = ? AND ' . FA_ALT_WHERE . '
                          ORDER BY id LIMIT ' . FORMAT_ANHEBEN_GRENZE);
    $st->execute([$userId]);
    $liste = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $m) {
        $liste[] = ['id' => (int)$m['id'], 'client_ref' => (string)$m['client_ref'],
                    'pat_blob' => (string)$m['pat_blob']];
    }
    $rest = array_fill(0, SB_BLAETTER, 0);
    $alle = $pdo->prepare('SELECT client_ref FROM missions WHERE user_id = ? AND ' . FA_ALT_WHERE);
    $alle->execute([$userId]);
    foreach ($alle->fetchAll(PDO::FETCH_COLUMN) as $ref) { $rest[fa_blatt((string)$ref)]++; }

    $u = $pdo->prepare('SELECT kp_privat, rw_privat FROM users WHERE id = ?');
    $u->execute([$userId]);
    $z = $u->fetch(PDO::FETCH_ASSOC) ?: [];
    $alt = static fn($v) => ($v !== null && $v !== '' && !str_starts_with((string)$v, 'edk2:')) ? (string)$v : null;
    json_out(['missions' => $liste, 'offen' => array_sum($rest), 'rest_je_blatt' => $rest,
              'kp' => $alt($z['kp_privat'] ?? null), 'rw' => $alt($z['rw_privat'] ?? null)]);
}

api_methode();
csrf_check();
$b = api_rumpf(['max_bytes' => 4 * GH_GEPACKT_MAX_BYTE]);

/* Gerätedaten der Fassung 1 (06e): Einsatzhüllen oder EINE Spur. */
if (isset($b['einsaetze']) || isset($b['spur'])) {
    $posten = [];
    foreach (is_array($b['einsaetze'] ?? null) ? $b['einsaetze'] : [] as $e) {
        $h = is_array($e) ? fa_huelle2($e['huelle'] ?? null, GH_EINSATZ_MAX_BYTE) : null;
        if ($h === null || (int)($e['id'] ?? 0) <= 0 || !preg_match('/^[0-9a-f]{64}$/', (string)($e['alt'] ?? ''))) {
            json_out(['error' => 'format', 'feld' => 'einsaetze'], 400);
        }
        $posten[] = ['id' => (int)$e['id'], 'huelle' => base64_decode($h, true), 'alt' => (string)$e['alt']];
    }
    $spur = null;
    if (isset($b['spur'])) {
        $sp = $b['spur'];
        $art = is_array($sp) ? (string)($sp['art'] ?? '') : '';
        if (!in_array($art, ['mission', 'rest'], true) || (int)($sp['id'] ?? 0) <= 0
            || !is_array($sp['stuecke'] ?? null) || !$sp['stuecke']) {
            json_out(['error' => 'format', 'feld' => 'spur'], 400);
        }
        $stuecke = [];
        foreach ($sp['stuecke'] as $x) {
            $h = is_array($x) ? fa_huelle2($x['huelle'] ?? null, GH_GEPACKT_MAX_BYTE) : null;
            if ($h === null || !is_int($x['seq_from'] ?? null) || !preg_match('/^[0-9a-f]{64}$/', (string)($x['alt'] ?? ''))) {
                json_out(['error' => 'format', 'feld' => 'stuecke'], 400);
            }
            $stuecke[] = ['seq_from' => $x['seq_from'], 'huelle' => $h, 'alt' => (string)$x['alt']];
        }
        $spur = ['art' => $art, 'id' => (int)$sp['id'], 'stuecke' => $stuecke];
    }
    $angehoben = 0; $uebersprungen = 0;
    try {
        if ($posten) {
            [$angehoben, $uebersprungen] = db_transaktion($pdo, function (PDO $pdo) use ($userId, $posten): array {
                $a = 0; $u = 0;
                $upd = $pdo->prepare('UPDATE missions SET ort_huelle = ? WHERE id = ? AND user_id = ?
                                        AND SHA2(ort_huelle, 256) = ?');
                foreach ($posten as $p) {
                    $upd->execute([$p['huelle'], $p['id'], $userId, $p['alt']]);
                    if ($upd->rowCount() === 1) { $a++; } else { $u++; }
                }
                return [$a, $u];
            });
        }
        if ($spur !== null) {
            try {
                $ok = db_transaktion($pdo, fn(PDO $pdo): bool => spur2_uebernahme_tauschen(
                    $pdo, $userId, $spur['art'], $spur['id'], $spur['stuecke']));
            } catch (SpurUebernahmeStand $s) {
                $ok = false;
            }
            if ($ok) { $angehoben++; } else { $uebersprungen++; }
        }
    } catch (Throwable $ex) {
        json_fehler($ex, 'format_anheben');
    }
    json_out(['ok' => true, 'angehoben' => $angehoben, 'uebersprungen' => $uebersprungen]);
}

$missions = $b['missions'] ?? [];
if (!is_array($missions) || !array_is_list($missions) || count($missions) > FORMAT_ANHEBEN_GRENZE) {
    json_out(['error' => 'format', 'feld' => 'missions'], 400);
}
$posten = [];
foreach ($missions as $m) {
    if (!is_array($m) || (int)($m['id'] ?? 0) <= 0) { json_out(['error' => 'format', 'feld' => 'id'], 400); }
    $neu = (string)($m['pat_blob'] ?? '');
    $p = new Pruefliste();
    if (!str_starts_with($neu, 'edk2:') || pruef_pat_blob($neu, 'Geschützte Angaben', $p) === null) {
        json_out(['error' => 'format', 'feld' => 'pat_blob'], 400);
    }
    $alt = $m['blob_alt'] ?? null;
    if (!is_string($alt) || $alt === '') { json_out(['error' => 'format', 'feld' => 'blob_alt'], 400); }
    $posten[] = ['id' => (int)$m['id'], 'neu' => $neu, 'alt' => $alt];
}
/* Kontopaar und Rückweg: nur `edk2:`, nie am Anteil (WRAP_RC_RE). */
$spalten = [];
foreach (['kp' => 'kp_privat', 'rw' => 'rw_privat'] as $feld => $spalte) {
    if (!isset($b[$feld])) { continue; }
    $neu = (string)($b[$feld]['neu'] ?? ''); $alt = (string)($b[$feld]['alt'] ?? '');
    if (!str_starts_with($neu, 'edk2:') || !preg_match(WRAP_RC_RE, $neu) || $alt === '') {
        json_out(['error' => 'format', 'feld' => $feld], 400);
    }
    $spalten[$spalte] = ['neu' => $neu, 'alt' => $alt];
}
$blaetter = sb_blaetter_lesen($b['blaetter'] ?? null);
if (is_string($blaetter)) { json_out(['error' => 'format', 'feld' => $blaetter], 400); }

try {
    [$angehoben, $uebersprungen] = db_transaktion($pdo, function (PDO $pdo) use ($userId, $posten, $spalten, $blaetter): array {
        $angehoben = 0; $uebersprungen = 0;
        $upd = $pdo->prepare('UPDATE missions SET pat_blob = ? WHERE id = ? AND user_id = ? AND pat_blob <=> ?');
        foreach ($posten as $p) {
            $upd->execute([$p['neu'], $p['id'], $userId, $p['alt']]);
            if ($upd->rowCount() === 1) { $angehoben++; } else { $uebersprungen++; }
        }
        foreach ($spalten as $spalte => $w) {
            $u = $pdo->prepare("UPDATE users SET `$spalte` = ? WHERE id = ? AND `$spalte` <=> ?");
            $u->execute([$w['neu'], $userId, $w['alt']]);
            if ($u->rowCount() === 1) { $angehoben++; } else { $uebersprungen++; }
        }
        sb_blaetter_schreiben($pdo, $userId, $blaetter, array_column($posten, 'neu'));
        return [$angehoben, $uebersprungen];
    });
} catch (SbKonflikt $k) {
    json_out($k->antwort, 409);
} catch (Throwable $ex) {
    json_fehler($ex, 'format_anheben');
}
json_out(['ok' => true, 'angehoben' => $angehoben, 'uebersprungen' => $uebersprungen]);
