<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId
require_once __DIR__ . '/../validate_lib.php';      // KP_PRIVAT_RE
require_once __DIR__ . '/../kontopaar_lib.php';
require_once __DIR__ . '/../ratelimit_lib.php';

/**
 * api/kontopaar.php — das Schlüsselpaar des Kontos (S11-03; Konzept S11 3.1,
 * E-S11-14). Drei Anliegen, alle POST mit JSON und CSRF:
 *
 *   {aktion:'anlegen', token, oeffentlich, privat}
 *        oeffentlich  SPKI in Base64 (ECDH, P-256) — darf jeder kennen
 *        privat       PKCS8 unter dem INHALTSSCHLÜSSEL (`edk1:`)
 *        token        das Anmelde-Token: der Nachweis des Passworts
 *     -> 200 {ok:true} · 400 paar · 403 passwort · 409 vorhanden|migration
 *        · 429 gesperrt
 *
 *   {aktion:'abweichung'}
 *        Der Browser hat beim Entsperren aus dem privaten Teil einen anderen
 *        öffentlichen abgeleitet als den, den der Server ausliefert.
 *     -> 200 {ok:true}
 *
 *   {aktion:'zustand'}   (seit S11-05e)
 *        Der Stand des eigenen Paars, wie ihn `ui_krypto_bootstrap()` in die
 *        Seite schreibt: {stand, oeffentlich, privat} — der private Teil als
 *        Chiffretext unter dem Inhaltsschlüssel. Für eine Seite, die vor dem
 *        Anlegen aufgebaut wurde (F-S11-61). Liest nur das eigene Konto.
 *     -> 200 {ok:true, stand, oeffentlich, privat}
 *
 *   {aktion:'oeffnen_fehler', art, id, grund}   (seit S11-04d)
 *        Eine Gerätehülle dieses Kontos ging im Browser nicht auf (M-S11-01,
 *        Zustand d). `art` spur|ruhe|einsatz, `id` der Einsatz bzw. das
 *        Segment, `grund` einer der Gründe aus `crypto.js` (laenge, fassung,
 *        schluessel, pruefwert, polsterung) oder kein_paar. Kein Klartext —
 *        der Server erfährt nur, DASS und WARUM, nicht was darin stand.
 *     -> 200 {ok:true} · 400 eingabe · 429 gesperrt
 *
 * WARUM DAS PASSWORT, OBWOHL DIE SITZUNG STEHT — dieselbe Überlegung wie
 * beim Rückweg (`api/rueckweg_anlegen.php`): Wer das Paar ablegt, bestimmt,
 * für wen Uhr und Handy verschließen. Ein Paar, dessen privaten Teil eine
 * übernommene Sitzung selbst erzeugt hat, gäbe ihr alle künftigen Ortsdaten.
 * Deshalb schreibt nur, wer das Passwort nachweist, und das Orakel teilt den
 * Topf `login`.
 *
 * ANLEGEN, NICHT ERSETZEN (`kp_ablegen()`): Ein zweites Paar machte jede
 * Hülle unter dem ersten unlesbar. Ist eines da, ist die Antwort 409.
 *
 * DIE ABWEICHUNG wird gemeldet (Reiter System) und protokolliert (Reiter
 * Verwaltung), nicht behoben: Sie heißt, dass jemand den öffentlichen Teil
 * oder den privaten verändert hat — oder ein Fehler im Browser. Beides muss
 * eine Betreiberin ansehen, bevor ein Gerät weiter dafür verschließt. Sie
 * zählt im Topf `kontopaar`, damit ein Skript den Reiter nicht flutet.
 *
 * VOR `update.php` gibt es die Spalten nicht: 409, kein Datenbankfehler.
 */

api_methode();
csrf_check();

$b = api_rumpf();
$aktion = (string)($b['aktion'] ?? '');
$pdo = db();

if ($aktion === 'zustand') {
    $z = kp_zustand($userId, $pdo);
    json_out(['ok' => true, 'stand' => $z['stand'], 'oeffentlich' => $z['oeffentlich'],
              'privat' => $z['privat']]);
}

if ($aktion === 'abweichung') {
    if (!rate_erlaubt('kontopaar')) { json_out(['error' => 'gesperrt'], 429); }
    rate_zaehlen('kontopaar');
    require_once __DIR__ . '/../protokoll_lib.php';
    system_melden('kontopaar', 'Der öffentliche Kontoschlüssel passt nicht zum privaten '
        . '(Abgleich beim Entsperren).', null, ['konto' => $userId]);
    protokoll('verwaltung', 'kontopaar_abweichung',
              'Kontoschlüssel: öffentlicher und privater Teil passen nicht zusammen', [], $userId);
    json_out(['ok' => true]);
}
if ($aktion === 'oeffnen_fehler') {
    /* DIE MELDUNG AN DEN REITER SYSTEM (S11-04d, M-S11-01 d). Eine Huelle, die
     * nicht aufgeht, ist entweder ein Geraet, das falsch verschliesst, oder
     * ein Eingriff in die Datenbank (vertauschte Huelle, fremder Schluessel)
     * — beides will die Betreiberin wissen, und die Nutzerin sieht nur „laesst
     * sich nicht oeffnen". Gemeldet wird nur, was der Server ohnehin kennt:
     * Art, Kennung, Grund. Derselbe Topf wie die Abweichung: Eine Seite, die
     * dreissig Huellen nicht oeffnen kann, soll nicht dreissig Meldungen
     * schreiben. */
    $art = (string)($b['art'] ?? '');
    $grund = (string)($b['grund'] ?? '');
    $id = (int)($b['id'] ?? 0);
    if (!in_array($art, ['spur', 'ruhe', 'einsatz'], true) || $id <= 0
        || !in_array($grund, ['laenge', 'fassung', 'schluessel', 'pruefwert', 'polsterung',
                              'kein_paar', 'unbekannt'], true)) {
        json_out(['error' => 'eingabe'], 400);
    }
    if (!rate_erlaubt('kontopaar')) { json_out(['error' => 'gesperrt'], 429); }
    rate_zaehlen('kontopaar');
    require_once __DIR__ . '/../systemmeldung_lib.php';
    system_melden('ortsdaten', 'Eine Gerätehülle geht im Browser nicht auf ('
        . ($art === 'ruhe' ? 'Ruhesegment ' : 'Einsatz ') . $id . ', ' . $art . ', Grund ' . $grund . ').',
        null, ['konto' => $userId]);
    json_out(['ok' => true]);
}
if ($aktion !== 'anlegen') { json_out(['error' => 'aktion'], 400); }

if (kp_zustand($userId, $pdo)['stand'] === 'spalten') {
    json_out(['error' => 'migration',
              'meldung' => 'Das Schlüsselpaar ist auf dieser Anlage noch nicht eingerichtet — '
                         . 'eine AdministratorIn muss update.php aufrufen.'], 409);
}

$st = $pdo->prepare('SELECT email, password_hash FROM users WHERE id = ?');
$st->execute([$userId]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) { json_out(['error' => 'konto'], 404); }
$email = (string)$u['email'];

if (!rate_erlaubt('login', $email)) {
    json_out(['error' => 'gesperrt',
              'meldung' => 'Zu viele Fehlversuche. Bitte später erneut.'], 429);
}
$token = (string)($b['token'] ?? '');
if ($token === '' || !password_verify($token, (string)$u['password_hash'])) {
    rate_misserfolg('login', $email);
    json_out(['error' => 'passwort', 'meldung' => 'Das Passwort ist nicht korrekt.'], 403);
}

$oeffentlich = (string)($b['oeffentlich'] ?? '');
$privat      = (string)($b['privat'] ?? '');
$grund = kp_oeffentlich_pruefen($oeffentlich);
if ($grund === null && !preg_match(KP_PRIVAT_RE, $privat)) {
    $grund = 'Der private Teil hat nicht die erwartete Form.';
}
if ($grund !== null) { json_out(['error' => 'paar', 'meldung' => $grund], 400); }

if (!kp_ablegen($pdo, $userId, $oeffentlich, $privat)) {
    json_out(['error' => 'vorhanden',
              'meldung' => 'Für dieses Konto ist schon ein Schlüsselpaar eingerichtet.'], 409);
}
require_once __DIR__ . '/../protokoll_lib.php';
protokoll('verwaltung', 'kontopaar_angelegt', 'Schlüsselpaar des Kontos eingerichtet', [], $userId);
json_out(['ok' => true]);
