<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../validate_lib.php';    // pruef_pat_blob(), WRAP_RC_RE
require_once __DIR__ . '/../standbuch_lib.php';   // Blätter mit den Blöcken

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
 * Steht die Weiche im Browser aus, ruft ihn niemand (`unlock.js`).
 */

const FORMAT_ANHEBEN_GRENZE = 200;

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
$b = api_rumpf();

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
