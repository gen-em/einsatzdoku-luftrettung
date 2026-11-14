<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';    // Anmeldung, liefert $userId
require_once __DIR__ . '/../kopplung_lib.php';

/**
 * POST api/kopplung_wert.php — die Antwort der Webseite auf den
 * Vergleichswert der Kopplung (KA-06d, Mockup M-KA-01 C und E).
 *
 *   {"antwort": "ja", "im_buch": true|false}
 *       „Wert stimmt“. `im_buch`: Der Browser hat das Gerät ins Gerätebuch
 *       eingetragen (mit ausgelieferter Weiche nicht) — nur dann darf es
 *       später Fassung 2 senden. Hat das Gerät schon Ja gesagt, entsteht es
 *       hier; sonst beim Ja.
 *       -> 200 {"ok":true, "zustand":"gekoppelt"|"wartet"}
 *   {"antwort": "nein", "grund": "wert"|"zusage"}
 *       Der Wert ist anders, oder die Zusage passt nicht. Die Sitzung bleibt
 *       bis zum Ende der Frist als abgelehnt stehen, damit das Gerät den
 *       Grund erfährt; der Reiter System bekommt einen Eintrag (ohne Werte).
 *       -> 200 {"ok":true, "zustand":"abgelehnt"}
 *   409 {"error":"zustand"}  — nichts zu bestätigen
 *   409 {"error":"device_limit", "meldung"}
 *
 * WELCHE SITZUNG GEMEINT IST, steht wie in `kopplung_stand.php` in der
 * Sitzung des Browsers ($_SESSION['pair_warten']) — nicht im Rumpf. Die
 * Kontokennung steht in jeder Bedingung (`pair_wert_melden()`).
 *
 * DER SERVER PRÜFT DEN WERT NICHT — er kann es nicht, ohne N_g und SPK zu
 * glauben, die er selbst trägt. Er hält nur fest, was die Person gesagt hat.
 */

api_methode();
csrf_check();
$b = api_rumpf();

$kennung = (string)($_SESSION['pair_warten'] ?? '');
$antwort = (string)($b['antwort'] ?? '');
if ($kennung === '' || !in_array($antwort, ['ja', 'nein'], true)) {
    json_out(['error' => 'zustand'], 409);
}
$pdo = db();
if (!pair_herkunft_bereit($pdo)) { json_out(['error' => 'zustand'], 409); }

if ($antwort === 'nein') {
    $grund = ($b['grund'] ?? '') === 'zusage' ? 'zusage' : 'wert';
    if (pair_wert_melden($pdo, $kennung, $userId, false, $grund, false) !== 'ok') {
        json_out(['error' => 'zustand'], 409);
    }
    system_melden('pair', $grund === 'zusage'
        ? 'Kopplung abgebrochen: Die Zusage des Geräts passte nicht zu seiner Zufallszahl'
        : 'Kopplung abgebrochen: Im Web wurde ein anderer Vergleichswert gemeldet als am Gerät');
    json_out(['ok' => true, 'zustand' => 'abgelehnt']);
}

if (pair_wert_melden($pdo, $kennung, $userId, true, '', ($b['im_buch'] ?? false) === true) !== 'ok') {
    json_out(['error' => 'zustand'], 409);
}
$s = pair_sitzung_nach_kennung($pdo, $kennung);
if ($s === null || !pair_abschlussreif($s)) {
    json_out(['ok' => true, 'zustand' => 'wartet']);
}
try {
    $erg = pair_abschliessen($pdo, (int)$s['id']);
} catch (Throwable $ex) {
    json_fehler($ex, 'kopplung_wert');
}
if ($erg['ergebnis'] === 'device_limit') {
    unset($_SESSION['pair_warten']);
    json_out(['error' => 'device_limit', 'meldung' => 'Es sind bereits ' . MAX_GERAETE . ' Geräte mit '
              . 'diesem Konto verbunden. Erst eines löschen, dann neu koppeln.'], 409);
}
if ($erg['ergebnis'] === 'angelegt') {
    /* Die Mail wird nur eingereiht (`mail_einreihen()`), nicht versandt —
     * anders als beim Geraet wartet hier niemand auf einen Mailserver. */
    pair_gekoppelt_melden($pdo, $erg['frisch']);
    json_out(['ok' => true, 'zustand' => 'gekoppelt']);
}
json_out(['ok' => true, 'zustand' => $erg['ergebnis'] === 'verschwunden' ? 'gekoppelt' : 'wartet']);
