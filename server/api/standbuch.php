<?php
declare(strict_types=1);

/*
 * Gen-EM NAdoku — die Blätter des Standbuchs (KA-06b-2, Technik 4.42).
 *
 * POST, JSON, CSRF. Drei Aktionen, wie `apiTransport()` in
 * `assets/standbuch.js` sie erwartet:
 *
 *   { "aktion": "stand" }
 *       200 { "blaetter": [v0 … v15], "buch": v }
 *   { "aktion": "holen", "wer": 0…15 | "buch" }
 *       200 { "version": n, "text": "edk2:…" | null }
 *   { "aktion": "ablegen", "wer": …, "version_alt": n, "version": m, "text": "edk2:…" }
 *       200 { "ok": true }
 *       409 { "error": "konflikt", "version": n, "text": "edk2:…" | null }
 *       400 { "error": "form", "feld": "wer" | "version" | "text" }
 *
 * Vor `update.php` gibt es die Tabelle nicht: 409 `migration`, wie beim
 * Schlüsselpaar des Kontos.
 *
 * Jedes Konto sieht und schreibt nur seine eigenen Blätter (`$userId` aus
 * der Sitzung). Das Demo-Konto darf schreiben; sein Reset räumt die Blätter
 * mit (`demo_bestand_loeschen()`).
 */

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId
require_once __DIR__ . '/../standbuch_lib.php';

api_methode();
csrf_check();

$b = api_rumpf();
$aktion = (string)($b['aktion'] ?? '');
$pdo = db();

if (!sb_bereit($pdo)) {
    json_out(['error' => 'migration',
              'meldung' => 'Das Standbuch ist auf dieser Anlage noch nicht eingerichtet — '
                         . 'eine AdministratorIn muss update.php aufrufen.'], 409);
}

if ($aktion === 'stand') {
    json_out(sb_stand($pdo, $userId));
}

if ($aktion !== 'holen' && $aktion !== 'ablegen') { json_out(['error' => 'aktion'], 400); }

$wer = sb_wer($b['wer'] ?? null);
if ($wer === null) { json_out(['error' => 'form', 'feld' => 'wer'], 400); }

if ($aktion === 'holen') {
    json_out(sb_holen($pdo, $userId, $wer));
}

$grund = sb_ablage_pruefen($b['version_alt'] ?? null, $b['version'] ?? null, $b['text'] ?? null);
if ($grund !== null) { json_out(['error' => 'form', 'feld' => $grund], 400); }

$e = sb_ablegen($pdo, $userId, $wer, (int)$b['version_alt'], (int)$b['version'], (string)$b['text']);
if ($e['ok']) { json_out(['ok' => true]); }
json_out(['error' => 'konflikt', 'version' => $e['version'], 'text' => $e['text']], 409);
