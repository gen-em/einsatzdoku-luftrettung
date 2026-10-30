<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../spur_lib.php';

/**
 * api/wartungslauf.php — der Wartungslauf im Browser. Der Server sieht
 * keine Koordinate und kann weder packen noch ausdünnen.
 * weder packen noch ausdünnen. Er weiß aber, WAS fällig ist, und er tauscht
 * nur, was sich seit dem Lesen nicht geändert hat. Drei Anliegen, alle POST
 * mit JSON und CSRF:
 *
 *   {aktion:'liste', auslassen?:[[art, id], …]}
 *        Höchstens 20 fällige Spuren des eigenen Kontos (`spur2_faellig()`),
 *        je mit Stücken (Hüllen als Base64), Sperrzeiten, Zielstufe, bei
 *        Einsätzen Phasenzeiten und `pat_blob` samt Prüfsumme. `auslassen`:
 *        was der Browser in dieser Sitzung nicht öffnen konnte.
 *     -> 200 {ok:true, spuren:[…]}
 *
 *   {aktion:'zahl', auslassen?}
 *        Wie viele Spuren fällig sind und aus wie vielen Diensttagen — für
 *        die Karte „GPS-Daten verdichten" (Reiter Backup).
 *     -> 200 {ok:true, aufzeichnungen, diensttage}
 *
 *   {aktion:'tauschen', art, id, stand:[[seq_from, punkte], …],
 *    stueck:{seq_from, punkte, n_original, ts_von, ts_bis, stufe, huelle},
 *    pat?:{pruef, neu}}
 *        Das eine neue Stück (Stufe 2 oder 3) statt der gelesenen. `pat` nur,
 *        wenn der Browser eine fehlende Ortshöhe nachgetragen hat.
 *     -> 200 {ok:true, weg, neu, pat?} · 400 eingabe · 409 stand | stufe |
 *        marke | form | frist | offen · 404 fehlt
 *
 * DER INHALT IST SACHE DES BROWSERS. Er öffnet, packt, dünnt aus, öffnet das
 * Ergebnis noch einmal und vergleicht (Rundlauf), bevor er schickt. Der
 * Server prüft die Form (`pruef_spurstueck_gepackt()`), Besitz, Abschluss,
 * Bestand, Marke und Frist (`spur2_wartung_tauschen()`).
 *
 * EIN 409 IST KEIN FEHLER, sondern der Normalfall eines Wettlaufs: Ein Gerät
 * hat inzwischen ein Stück gesendet, oder ein anderes Fenster war schneller.
 * Der Browser lässt die Spur liegen; der nächste Lauf nimmt sie wieder mit.
 */

api_methode();
csrf_check();

$b = api_rumpf(['max_bytes' => 4 * GH_GEPACKT_MAX_BYTE]);
$aktion = (string)($b['aktion'] ?? '');
$pdo = db();

if ($aktion === 'liste') {
    try {
        $aus = is_array($b['auslassen'] ?? null) ? $b['auslassen'] : [];
        $spuren = spur2_faellig($pdo, $userId, SPUR2_WARTUNG_MAX, null, $aus);
    } catch (Throwable $ex) {
        json_fehler($ex, 'wartungslauf');
    }
    json_out(['ok' => true, 'spuren' => $spuren]);
}

if ($aktion === 'zahl') {
    try {
        $aus = is_array($b['auslassen'] ?? null) ? $b['auslassen'] : [];
        json_out(['ok' => true] + spur2_faellig_zahl($pdo, $userId, null, $aus));
    } catch (Throwable $ex) {
        json_fehler($ex, 'wartungslauf');
    }
}

if ($aktion !== 'tauschen') {
    json_out(['error' => 'aktion'], 400);
}

$pruef = new Pruefliste();
$art = (string)($b['art'] ?? '');
$id = (int)($b['id'] ?? 0);
$stueck = pruef_spurstueck_gepackt($b['stueck'] ?? null, 'stueck', $pruef);
$stand = $b['stand'] ?? null;
$pat = null;
if (isset($b['pat'])) {
    $neu = pruef_pat_blob($b['pat']['neu'] ?? null, 'pat.neu', $pruef);
    $pruefsumme = $b['pat']['pruef'] ?? null;
    if ($neu === null || !is_string($pruefsumme) || !preg_match('/^[0-9a-f]{64}$/', $pruefsumme)) {
        $pruef->melde('pat', 'unvollstaendig');
    } else {
        $pat = ['neu' => $neu, 'pruef' => $pruefsumme];
    }
}
if (!in_array($art, ['mission', 'rest'], true) || $id < 1 || $stueck === null
    || !is_array($stand) || !$stand || !$pruef->sauber()) {
    json_out(['error' => 'eingabe', 'felder' => $pruef->nachUrsache()], 400);
}
$stand = array_map(static fn($x) => [(int)($x[0] ?? -1), (int)($x[1] ?? 0)], array_values($stand));

try {
    $t = spur2_wartung_tauschen($pdo, $userId, $art, $id, $stand, $stueck, $pat);
} catch (Throwable $ex) {
    json_fehler($ex, 'wartungslauf');
}
if (!$t['ok']) {
    json_out(['error' => $t['grund']], $t['grund'] === 'fehlt' ? 404 : 409);
}
json_out(['ok' => true] + $t);
