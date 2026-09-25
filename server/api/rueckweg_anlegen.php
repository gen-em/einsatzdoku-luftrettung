<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId
require_once __DIR__ . '/../validate_lib.php';      // RW_PRIVAT_RE
require_once __DIR__ . '/../rueckweg_lib.php';      // rw_oeffentlich_pruefen(), rw_spalten_da()
require_once __DIR__ . '/../ratelimit_lib.php';
require_once __DIR__ . '/../demo_lib.php';

/**
 * api/rueckweg_anlegen.php — das Schlüsselpaar des Rückwegs ablegen
 * (Konzept RW, RW-02; E-RW-02, -05, -06, -14, -15).
 *
 * ===========================================================================
 * WAS HIER ANKOMMT, UND WAS DER SERVER DAVON PRÜFEN KANN
 * ===========================================================================
 *
 * Der Browser hat ein ECDSA-Paar auf P-256 erzeugt (`assets/rueckweg.js`)
 * und schickt:
 *
 *   oeffentlich  der öffentliche Teil, SPKI in Base64 — darf jeder kennen
 *   privat       der private Teil (PKCS8), verpackt mit dem INHALTSSCHLÜSSEL
 *                (`edk1:`) — den der Server nicht kennt
 *   token        das Anmelde-Token: der Nachweis des Passworts
 *   ersetzen     1, wenn ein vorhandenes Paar ersetzt werden soll
 *
 * Prüfen kann der Server den öffentlichen Teil auf Form UND Kurve
 * (`rw_oeffentlich_pruefen()` lädt ihn und verlangt `secp256r1`) und den
 * privaten auf die Form (`RW_PRIVAT_RE`: nur `edk1:`, nie `edka1:` — der
 * private Teil muss mit dem Wiederherstellungsschlüssel allein aufgehen, wie
 * `pat_wrap_rc`). Ob der private Teil zum öffentlichen GEHÖRT, kann er nicht
 * prüfen — er kann ihn nicht öffnen.
 *
 * ===========================================================================
 * WARUM DAS PASSWORT, OBWOHL DIE SITZUNG STEHT
 * ===========================================================================
 *
 * Wer das Paar ablegt, bestimmt, wessen Signatur am Code-Schritt den
 * Zweitfaktor abschaltet. Ein Paar, dessen privaten Teil ein Fremder
 * selbst erzeugt hat, braucht keinen Wiederherstellungsschlüssel mehr —
 * er signiert die Herausforderung unmittelbar. Deshalb schreibt nur, wer
 * das Passwort nachweist (E-RW-02, -06), und deshalb bekommt dieses
 * Orakel keinen eigenen Topf, sondern teilt `login` (E-RW-14, wie
 * `schluessel_erneuern.php`).
 *
 * ===========================================================================
 * ANLEGEN UND ERSETZEN
 * ===========================================================================
 *
 *   ohne `ersetzen`  nur, wenn noch keins da ist — sonst 409. Das ist der
 *                    stille Weg nach der Anmeldung (`unlock.js`). Das
 *                    UPDATE trägt die Bedingung selbst (`rw_oeffentlich IS
 *                    NULL`): Zwei Fenster, die gleichzeitig anlegen, legen
 *                    genau ein Paar ab, und das zweite bekommt 409.
 *   mit `ersetzen`   der Knopf „Rückweg erneuern" (E-RW-06). Protokoll
 *                    `rueckweg_erneuert` und die Mail `rueckweg_erneuert`
 *                    an die Kontoadresse — wer den Rückweg an sich nähme,
 *                    hinterließe eine Spur im Postfach.
 *
 * Protokoll und Mail richten sich danach, ob VORHER ein Paar da war, nicht
 * nach dem Schalter: Ein `ersetzen` ohne Bestand ist ein Anlegen.
 *
 * DAS DEMO-KONTO BEKOMMT KEINS (E-RW-15): 403, mit und ohne `ersetzen`. Sein
 * Zweitfaktor ist gesperrt; ein Paar hätte dort keinen Verbraucher.
 *
 * VOR `update.php` gibt es die Spalten nicht. Der Browser fragt dann gar
 * nicht erst (`RW_STAND` ist `'spalten'`); kommt trotzdem eine Anfrage,
 * antwortet der Endpunkt 409 statt mit einem Datenbankfehler.
 */

api_methode();
csrf_check();

if (demo_ist_demo($userId)) {
    json_out(['error' => 'demo',
              'meldung' => 'Im Demo-Konto gibt es keinen Rückweg.'], 403);
}
if (!rw_spalten_da()) {
    json_out(['error' => 'migration',
              'meldung' => 'Der Rückweg ist auf dieser Anlage noch nicht eingerichtet — '
                         . 'eine AdministratorIn muss update.php aufrufen.'], 409);
}

$st = db()->prepare('SELECT email, password_hash, rw_oeffentlich FROM users WHERE id = ?');
$st->execute([$userId]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) { json_out(['error' => 'konto'], 404); }
$email = (string)$u['email'];

if (!rate_erlaubt('login', $email)) {
    json_out(['error' => 'gesperrt',
              'meldung' => 'Zu viele Fehlversuche. Bitte später erneut.'], 429);
}

$b = api_rumpf();
$token = (string)($b['token'] ?? '');
if ($token === '' || !password_verify($token, (string)$u['password_hash'])) {
    rate_misserfolg('login', $email);
    json_out(['error' => 'passwort',
              'meldung' => 'Das Passwort ist nicht korrekt.'], 403);
}

$oeffentlich = (string)($b['oeffentlich'] ?? '');
$privat      = (string)($b['privat'] ?? '');
$grund = rw_oeffentlich_pruefen($oeffentlich);
if ($grund === null && !preg_match(RW_PRIVAT_RE, $privat)) {
    $grund = 'Der private Teil hat nicht die erwartete Form.';
}
if ($grund !== null) { json_out(['error' => 'paar', 'meldung' => $grund], 400); }

$ersetzen = !empty($b['ersetzen']);
$vorher   = $u['rw_oeffentlich'] !== null && $u['rw_oeffentlich'] !== '';
if ($vorher && !$ersetzen) {
    json_out(['error' => 'vorhanden',
              'meldung' => 'Für dieses Konto ist schon ein Rückweg eingerichtet.'], 409);
}

$sql = 'UPDATE users SET rw_oeffentlich = ?, rw_privat = ?, rw_seit = UTC_TIMESTAMP() WHERE id = ?'
     . ($ersetzen ? '' : ' AND rw_oeffentlich IS NULL');
$up = db()->prepare($sql);
$up->execute([$oeffentlich, $privat, $userId]);
if ($up->rowCount() !== 1) {
    json_out(['error' => 'vorhanden',
              'meldung' => 'Für dieses Konto ist schon ein Rückweg eingerichtet.'], 409);
}

require_once __DIR__ . '/../protokoll_lib.php';
if ($vorher) {
    protokoll('verwaltung', 'rueckweg_erneuert',
              'Rückweg mit dem Wiederherstellungsschlüssel erneuert', [], $userId);
    require_once __DIR__ . '/../mail_lib.php';
    mail_einreihen('rueckweg_erneuert', $email, ['link' => app_url('/einstellungen.php?t=profil')]);
    /* DIE MELDUNG FÜR DIE NÄCHSTE SEITE. Der Dialog lädt die Seite nach dem
     * Erfolg neu; ohne diese Zeile stünde dort nur ein neues Datum. */
    flash_setzen('notice', 'Rückweg erneuert. Dein Notfallblatt bleibt gültig; '
                         . 'eine Nachricht ist an deine Adresse unterwegs.');
} else {
    protokoll('verwaltung', 'rueckweg_angelegt',
              'Rückweg mit dem Wiederherstellungsschlüssel eingerichtet', [], $userId);
}
json_out(['ok' => true]);
