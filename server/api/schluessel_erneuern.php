<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId
require_once __DIR__ . '/../validate_lib.php';      // WRAP_RC_RE
require_once __DIR__ . '/../serverkrypto_lib.php';  // huelle_rc_pruefen()
require_once __DIR__ . '/../ratelimit_lib.php';

/**
 * api/schluessel_erneuern.php — einen neuen Wiederherstellungsschluessel
 * setzen (P5b/AP9, E-P5b-20).
 *
 * ===========================================================================
 * WAS HIER GESCHIEHT — UND VOR ALLEM, WAS NICHT
 * ===========================================================================
 *
 * Es wird GENAU EINE Spalte geschrieben: `pat_wrap_rc`. Sonst nichts.
 *
 * Der Inhaltsschluessel (`ck`) bleibt derselbe. Er ist der Schluessel, mit
 * dem jeder Datensatz dieses Kontos verschluesselt ist; ihn zu wechseln
 * hiesse, jeden Einsatz neu zu verschluesseln. Was sich aendert, ist allein
 * seine WIEDERHERSTELLUNGS-HUELLE — also das Schloss, das der Zettel oeffnet.
 *
 * Folge, und sie ist die Abnahme dieses Pakets: **Der alte Zettel oeffnet
 * danach nichts mehr, der neue alles.** Das Passwort ist unberuehrt, die
 * Passwort-Huelle (`pat_wrap_pw`) ebenso, und `session_epoch` bleibt stehen —
 * es gibt keinen Grund, offene Sitzungen zu beenden, denn an ihrem Schluessel
 * hat sich nichts geaendert.
 *
 * NICHT ANGEFASST WERDEN:
 *   `pat_wrap_pw`    der Weg ueber das Passwort
 *   `pat_key_check`  die Pruefsumme des Inhaltsschluessels — sie ist der
 *                    MASSSTAB und darf nicht mitwandern. Der Browser haelt
 *                    den entpackten `ck` dagegen, BEVOR er neu verpackt; ein
 *                    Fehler dort waere sonst unbemerkt und endgueltig.
 *   `password_hash`, `pat_salt`, `pat_iter`, `session_epoch`
 *
 * ===========================================================================
 * WARUM DAS PASSWORT VERLANGT WIRD, OBWOHL DIE SITZUNG STEHT
 * ===========================================================================
 *
 * Eine angemeldete, entsperrte Sitzung KOENNTE den Inhaltsschluessel schon —
 * er liegt in `sessionStorage`. Der Browser braucht das Passwort also nicht
 * zwingend, um neu zu verpacken. Verlangt wird es trotzdem, aus einem Grund,
 * der nichts mit Verschluesselung zu tun hat:
 *
 * **Wer eine fremde Sitzung uebernimmt, koennte sonst den Zettel des Opfers
 * ungueltig machen.** Er kaeme an keine Daten — aber er naehme der
 * Besitzerin den Rueckweg, und zwar lautlos: Ihr Notfallblatt oeffnet
 * ploetzlich nichts mehr, und sie erfaehrt es erst, wenn sie es braucht.
 * Das ist kein Datenklau, sondern eine Datenzerstoerung auf Raten.
 *
 * Geprueft wird serverseitig — hier — und nicht im Browser: Eine Pruefung im
 * Browser waere eine Bitte, keine Wache.
 *
 * ===========================================================================
 * DER RATENSCHUTZ TEILT SICH DEN TOPF MIT DER ANMELDUNG
 * ===========================================================================
 *
 * Dieser Endpunkt prueft ein Passwort, also ist er ein Orakel. Er bekommt
 * DESHALB KEINEN EIGENEN TOPF, sondern zaehlt in `login` auf dasselbe Konto:
 * Ein Ratespiel bleibt ein Ratespiel, gleich an welcher Tuer. Ein eigener
 * Topf haette dem Angreifer ein zweites Budget geschenkt — zehn Versuche an
 * der Anmeldung, zehn weitere hier.
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'methode']);
    exit;
}
csrf_check();

/* ---- Wer ist das, und stimmt das Passwort? ------------------------------ */

$st = db()->prepare('SELECT email, password_hash, pat_wrap_rc FROM users WHERE id = ?');
$st->execute([$userId]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) {
    http_response_code(404);
    echo json_encode(['error' => 'konto']);
    exit;
}

$email = (string)$u['email'];
if (!rate_erlaubt('login', $email)) {
    http_response_code(429);
    echo json_encode(['error' => 'gesperrt',
                      'text'  => 'Zu viele Fehlversuche. Bitte später erneut.']);
    exit;
}

$token = (string)($_POST['token'] ?? '');
if ($token === '' || !password_verify($token, (string)$u['password_hash'])) {
    rate_misserfolg('login', $email);
    http_response_code(403);
    echo json_encode(['error' => 'passwort',
                      'text'  => 'Das Passwort ist nicht korrekt.']);
    exit;
}

/* ---- Die neue Huelle ---------------------------------------------------- */

$wrapRc = (string)($_POST['wrap_rc'] ?? '');

/* `huelle_rc_pruefen()` UND NICHT NUR EIN FORMATTEST. Sie weist eine
 * `edka1:`-Huelle ab — also eine, die am Server-Anteil haengt. Genau die
 * waere hier der Verlust des Rueckwegs: `pat_wrap_rc` oeffnet ohne Anteil,
 * und deshalb ist dessen Verlust kein Datenverlust (E-S10-04). Eine Huelle,
 * die das aufgibt, sieht man dem Feld nicht an — bis es zu spaet ist. */
$grund = huelle_rc_pruefen($wrapRc);
if ($grund !== null) {
    http_response_code(400);
    echo json_encode(['error' => 'huelle', 'text' => $grund]);
    exit;
}

/* KEIN ERSTVERGABE-WEG. Ein Konto ohne `pat_wrap_rc` hat sein Passwort noch
 * nie gesetzt; dafuer gibt es `pw_handling.php`, und dort entsteht die Huelle
 * zusammen mit allem anderen. Hier eine anzulegen hiesse, einen zweiten
 * Erstvergabe-Weg zu bauen — und zwei Wege zu derselben Ersteinrichtung sind
 * einer zu viel. */
if ($u['pat_wrap_rc'] === null || $u['pat_wrap_rc'] === '') {
    http_response_code(409);
    echo json_encode(['error' => 'kein_bestand',
                      'text'  => 'Für dieses Konto ist noch kein Schlüssel '
                               . 'eingerichtet.']);
    exit;
}

db()->prepare('UPDATE users SET pat_wrap_rc = ? WHERE id = ?')
    ->execute([$wrapRc, $userId]);

/* PROTOKOLL OHNE WERTE. Dass ein Schluessel erneuert wurde, gehoert
 * festgehalten — was er ist, nicht.
 *
 * REITER `verwaltung`, NICHT `sicherheit`. Den zweiten gibt es nicht:
 * `PROTOKOLL_REITER` kennt verwaltung, email, jobs, sicherung, ziele und
 * system. Ein unbekannter Reiter landet stillschweigend unter `system` und
 * schreibt eine Zeile ins Fehlerprotokoll — ich hatte hier zuerst
 * `sicherheit` stehen, weil E-P5b-06 einen Reiter dieses Namens ANKUENDIGT.
 * Er kommt mit 10c; bis dahin ist er keiner.
 *
 * Und er waere hier auch der falsche: Der Reiter Sicherheit sammelt Sperren
 * und Angriffe (`sicherheit_ereignisse`, `rate_ereignis()`). Dies hier ist
 * eine Handlung der Kontoinhaberin an ihrem eigenen Konto — das ist das
 * Audit, also `verwaltung`. */
require_once __DIR__ . '/../protokoll_lib.php';
protokoll('verwaltung', 'schluessel_erneuert',
          'Wiederherstellungsschlüssel erneuert', [], $userId);

echo json_encode(['ok' => true]);
