<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../ortsumzug_lib.php';

/**
 * api/ortsumzug.php — das Einmalwerkzeug (S11-10; Konzept S11 3.8 Schritt 5,
 * E-S11-19). Die Serverhälfte des Umzugs der Klartext-Ortsdaten in die
 * Gerätehülle; den Inhalt verschließt der Browser (`assets/ortsumzug.js`).
 * Muster: `api/pat_anheben.php` und `api/wartungslauf.php`. Alle Anliegen
 * POST mit JSON und CSRF, nur das eigene Konto:
 *
 *   {aktion:'zahl', auslassen?:[[art, id], …]}
 *     -> 200 {ok:true, einsaetze, ruhe}
 *
 *   {aktion:'liste', auslassen?}
 *        Höchstens 10 Datensätze mit Altbestand (`ou_fuellen()`): Beginn,
 *        `client_ref`, Phasen mit Koordinaten, Reanimationen, Spurpunkte
 *        `[seq, lat, lon, ele, ts]` mit Stufe, bei Einsätzen `ort_huelle`,
 *        `pat_blob` und die Spalten des Transportziels; dazu `spur_stand`.
 *     -> 200 {ok:true, liste:[…]}
 *
 *   {aktion:'tauschen', art, id, spur_stand, stuecke:[…],
 *    ort?:{neu, alt}, pat?:{neu, alt}}
 *        Stücke (Stufe 1, oder EIN gepacktes der Stufe 3 für eine
 *        ausgedünnte Spur), die Einsatzhülle, der Blob — und der Klartext
 *        geht, in derselben Transaktion.
 *     -> 200 {ok:true, stuecke} · 400 eingabe · 404 fehlt ·
 *        409 stand | blob | huelle
 *
 * Ein 409 ist ein Wettlauf (ein anderes Fenster, ein Formular, ein Gerät);
 * der Datensatz bleibt liegen, der nächste Lauf nimmt ihn mit. Das
 * Werkzeug hat keinen Merker: Fertig ist es, wenn `zahl` null meldet.
 */

api_methode();
csrf_check();

/* Eine Spur von 50 000 Punkten sind als Stücke rund 1,6 MB Base64. */
$b = api_rumpf(['max_bytes' => 8 * 1024 * 1024]);
$aktion = (string)($b['aktion'] ?? '');
$pdo = db();
$aus = is_array($b['auslassen'] ?? null) ? array_slice($b['auslassen'], 0, 500) : [];

if ($aktion === 'zahl') {
    try {
        json_out(['ok' => true] + ou_zahl($pdo, $userId, $aus));
    } catch (Throwable $ex) {
        json_fehler($ex, 'ortsumzug');
    }
}

if ($aktion === 'liste') {
    try {
        $liste = ou_fuellen($pdo, $userId, ou_kandidaten($pdo, $userId, OU_LISTE_MAX, $aus));
    } catch (Throwable $ex) {
        json_fehler($ex, 'ortsumzug');
    }
    json_out(['ok' => true, 'liste' => $liste]);
}

if ($aktion !== 'tauschen') {
    json_out(['error' => 'aktion'], 400);
}

/* DIE PRÜFSCHICHT, wie an jedem Schreibweg (CLAUDE.md 4): dieselben
 * Funktionen wie Eingang, Wartungslauf und Formular. */
$pruef = new Pruefliste();
$art = (string)($b['art'] ?? '');
$id = (int)($b['id'] ?? 0);
$stand = $b['spur_stand'] ?? null;
$stuecke = [];
$roh = is_array($b['stuecke'] ?? null) ? $b['stuecke'] : [];
/* Eine Spur hat höchstens LIMIT_TRACKPUNKTE_SPUR Punkte; an jeder Lücke
 * beginnt ein Stück. Mehr Stücke als Punkte gibt es nicht. */
if (count($roh) > LIMIT_TRACKPUNKTE_SPUR) { $pruef->melde('stuecke', 'zu viele'); $roh = []; }
foreach (array_values($roh) as $i => $s) {
    $gepackt = is_array($s) && (int)($s['stufe'] ?? 1) >= 2;
    $g = $gepackt ? pruef_spurstueck_gepackt($s, 'stuecke.' . $i, $pruef)
                  : pruef_spurstueck($s, 'stuecke.' . $i, $pruef);
    if ($g === null) { $pruef->melde('stuecke.' . $i, 'Stueck unvollstaendig'); continue; }
    $stuecke[] = $g;
}
$huellen = [];
foreach (['ort' => 'huelle', 'pat' => 'blob'] as $k => $form) {
    if (!isset($b[$k])) { continue; }
    $x = $b[$k];
    if (!is_array($x)) { $pruef->melde($k, 'kein Objekt'); continue; }
    if ($k === 'ort') {
        $neu = pruef_huelle($x['neu'] ?? null, GH_EINSATZ_MAX_BYTE, 'ort.neu', $pruef);
        $alt = ($x['alt'] ?? null) === null ? null : base64_decode((string)$x['alt'], true);
        if ($alt === false) { $pruef->melde('ort.alt', 'kein Base64'); continue; }
    } else {
        $neu = pruef_pat_blob($x['neu'] ?? null, 'pat.neu', $pruef);
        $alt = ($x['alt'] ?? null) === null || $x['alt'] === '' ? null : (string)$x['alt'];
    }
    if ($neu === null) { $pruef->melde($k . '.neu', 'fehlt'); continue; }
    $huellen[$k] = ['neu' => $neu, 'alt' => $alt];
}
if (!in_array($art, ['mission', 'rest'], true) || $id < 1
    || !is_string($stand) || !preg_match('/^[0-9a-f]{64}$/', $stand)
    || ($art === 'rest' && $huellen) || !$pruef->sauber()) {
    json_out(['error' => 'eingabe', 'felder' => $pruef->nachUrsache()], 400);
}

try {
    $t = ou_tauschen($pdo, $userId, ['art' => $art, 'id' => $id, 'spur_stand' => $stand,
                                     'stuecke' => $stuecke] + $huellen);
} catch (Throwable $ex) {
    json_fehler($ex, 'ortsumzug');
}
if (!$t['ok']) {
    json_out(['error' => $t['grund']], $t['grund'] === 'fehlt' ? 404 : 409);
}
json_out($t);
