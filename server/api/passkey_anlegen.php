<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth_guard.php';        // liefert $userId, zweitfaktor_frisch_verlangen()
require_once __DIR__ . '/../totp_lib.php';          // totp_an()
require_once __DIR__ . '/../passkey_lib.php';
require_once __DIR__ . '/../demo_lib.php';

/**
 * api/passkey_anlegen.php — einen Passkey ablegen (Schritt 18, SR-09;
 * E-SR-29 bis -31, -33).
 *
 * Der Browser hat mit `navigator.credentials.create()` einen Passkey erzeugt
 * (`assets/passkey.js`) und schickt als JSON:
 *
 *   antwort       rawId, clientDataJSON, attestationObject (Base64url)
 *   bezeichnung   optional, hoechstens 40 Zeichen
 *
 * VIER TORE, IN DIESER REIHENFOLGE. Der FRISCHE CODE vor dem Token, wie ein
 * Rollentor (E-SR-20, -31): Wer eine fremde Sitzung erbeutet hat, soll sich
 * damit keinen dauerhaften zweiten Faktor anlegen — 403 JSON
 * `zweitfaktor_frisch`. Dann das Token. Dann der Zweitfaktor: Ein Passkey ist
 * ein Verfahren DIESES Faktors und setzt ihn voraus (E-SR-29, -35). Dann die
 * Anlage: Tabelle da und eine Adresse, fuer die es Passkeys gibt (E-SR-42).
 *
 * DIE HERAUSFORDERUNG GILT EINMAL. Sie liegt in `$_SESSION['passkey_reg']`
 * (gestellt beim Aufbau der Karte, zehn Minuten) und wird weggenommen, sobald
 * die Pruefung beginnt, gleich welcher Ausgang — eine zweite Einsendung
 * findet keine. RUMPF UND BEZEICHNUNG WERDEN DAVOR GEPRUEFT (H-SR-08,
 * F-SR-39): Ein 400 wegen einer zu langen Bezeichnung verbrauchte sonst die
 * Herausforderung, nachdem der Browser den Passkey schon erzeugt hatte.
 *
 * NACH AUSSEN HEISST JEDE ABLEHNUNG DER PRUEFUNG „NICHT ANGENOMMEN"
 * (E-SR-45) — auch „diese Kennung gibt es schon". Mit Attestation `none` ist
 * die Kennung frei waehlbar; die eigene Antwort darauf haette verraten, ob
 * eine Kennung irgendwo auf der Anlage liegt. Der Grund steht im Protokoll
 * (`passkey_abgewiesen`), wo die Verwaltung ihn sieht.
 *
 * PROTOKOLL UND MAIL beim Anlegen (E-SR-33): `passkey_angelegt` und eine
 * Nachricht an die Kontoadresse. Ein neuer zweiter Faktor an einem erbeuteten
 * Konto bliebe sonst still.
 */

api_methode();
zweitfaktor_frisch_verlangen('passkey_anlegen');
csrf_check();

if (demo_ist_demo($userId)) {
    json_out(['error' => 'demo', 'meldung' => 'Im Demo-Konto gibt es keine Passkeys.'], 403);
}
if (!totp_an($userId)) {
    json_out(['error' => 'zweitfaktor',
              'meldung' => 'Ein Passkey setzt den eingeschalteten Zweitfaktor voraus.'], 409);
}
if (!pk_verfuegbar()) {
    json_out(['error' => 'anlage',
              'meldung' => 'Auf dieser Anlage gibt es keine Passkeys — sie braucht eine Adresse mit '
                         . 'HTTPS und Domainnamen, und update.php muss gelaufen sein.'], 409);
}

/* DECKEL UND TYPEN ZUERST (H-SR-08, F-SR-35): hoechstens PK_ANTWORT_MAX, und
 * die Bezeichnung ist Text — eine Liste wurde bis Web 21.10.0 zu „Array". */
$b = api_rumpf(['max_bytes' => PK_ANTWORT_MAX]);
$antwort = is_array($b['antwort'] ?? null) ? $b['antwort'] : [];
$bezeichnungRoh = $b['bezeichnung'] ?? '';
$bezeichnung = is_string($bezeichnungRoh) ? pk_bezeichnung_saeubern($bezeichnungRoh) : null;
if ($bezeichnung === null || mb_strlen($bezeichnung) > PK_BEZEICHNUNG_MAX) {
    json_out(['error' => 'bezeichnung',
              'meldung' => 'Die Bezeichnung hat höchstens ' . PK_BEZEICHNUNG_MAX . ' Zeichen.'], 400);
}

$ablage = is_array($_SESSION['passkey_reg'] ?? null) ? $_SESSION['passkey_reg'] : [];
unset($_SESSION['passkey_reg']);
if (!pk_ablage_passt($ablage, $userId)) { $ablage = []; }

/** Eine Ablehnung der Pruefung: nach aussen ein Satz, der Grund ins Protokoll. */
$abweisen = static function (string $grund) use ($userId): never {
    require_once __DIR__ . '/../protokoll_lib.php';
    protokoll('verwaltung', 'passkey_abgewiesen', 'Passkey nicht angenommen — ' . $grund,
              ['grund' => $grund], $userId);
    json_out(['error' => 'passkey',
              'meldung' => 'Der Passkey wurde nicht angenommen. Bitte die Seite neu laden und noch '
                         . 'einmal versuchen.'], 400);
};
$reg = pk_registrierung_pruefen($ablage, $antwort);
if (!$reg['ok'] && $reg['art'] === 'herausforderung') {
    /* KEINE, ABGELAUFENE ODER VERDRAENGTE HERAUSFORDERUNG: nichts geprueft,
     * also keine Zeile „abgewiesen" (Nachpruefung von H-SR-08, E-1) — sonst
     * schriebe jeder POST eine, und ein zweiter Reiter saehe in der
     * Verwaltung aus wie ein Angriff. Dieselbe Regel wie beim Fehlversuch
     * (E-SR-50). Verraten wird dabei nichts: Die Kennung ist noch nicht
     * gelesen. */
    json_out(['error' => 'abgelaufen',
              'meldung' => 'Die Anfrage ist abgelaufen. Bitte die Seite neu laden und noch einmal versuchen.'], 400);
}
if (!$reg['ok']) { $abweisen($reg['grund']); }
$an = pk_anlegen($userId, $reg, $bezeichnung);
if (!$an['ok']) {
    if ($an['grund'] === 'vorhanden') { $abweisen('Kennung gibt es schon'); }
    json_out(['error' => $an['grund'], 'meldung' => match ($an['grund']) {
        'voll'        => 'Es sind schon ' . PK_HOECHSTENS . ' Passkeys angelegt — bitte zuerst einen entfernen.',
        'zweitfaktor' => 'Ein Passkey setzt den eingeschalteten Zweitfaktor voraus.',
        default       => 'Der Passkey ließ sich nicht speichern.',
    }], 409);
}

/* Der Name aus der eben geschriebenen Zeile — ohne Bezeichnung „Passkey vom
 * <Datum>" mit dem Datum, das dort steht. */
$zeile = array_values(array_filter(pk_liste($userId), static fn(array $p): bool => $p['id'] === $an['id']));
$name = $zeile !== [] ? pk_anzeigename($zeile[0]) : 'Passkey';
require_once __DIR__ . '/../protokoll_lib.php';
protokoll('verwaltung', 'passkey_angelegt', 'Passkey hinzugefügt: ' . $name,
          ['passkey' => $an['id'], 'alg' => PK_ALGS[$reg['alg']]], $userId);
$st = db()->prepare('SELECT email FROM users WHERE id = ?');
$st->execute([$userId]);
require_once __DIR__ . '/../mail_lib.php';
mail_einreihen('passkey_angelegt', (string)$st->fetchColumn(),
               ['link' => app_url('/einstellungen.php?t=profil'), 'bezeichnung' => $name]);
flash_setzen('notice', 'Passkey „' . $name . '" hinzugefügt. Bei der nächsten Anmeldung genügt er '
                     . 'statt des Codes; eine Nachricht ist an deine Adresse unterwegs.');
json_out(['ok' => true, 'id' => $an['id']]);
