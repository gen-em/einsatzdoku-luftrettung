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
 * (gestellt beim Aufbau der Karte, zehn Minuten) und wird hier sofort
 * weggenommen, gleich welcher Ausgang — eine zweite Einsendung findet keine.
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

$ablage = is_array($_SESSION['passkey_reg'] ?? null) ? $_SESSION['passkey_reg'] : [];
unset($_SESSION['passkey_reg']);
if ((int)($ablage['konto'] ?? 0) !== $userId) { $ablage = []; }

$b = api_rumpf();
$antwort = is_array($b['antwort'] ?? null) ? $b['antwort'] : [];
$bezeichnung = trim((string)($b['bezeichnung'] ?? ''));
if (mb_strlen($bezeichnung) > PK_BEZEICHNUNG_MAX) {
    json_out(['error' => 'bezeichnung',
              'meldung' => 'Die Bezeichnung hat höchstens ' . PK_BEZEICHNUNG_MAX . ' Zeichen.'], 400);
}

$reg = pk_registrierung_pruefen($ablage, $antwort);
if (!$reg['ok']) {
    json_out(['error' => 'passkey',
              'meldung' => 'Der Passkey wurde nicht angenommen. Bitte die Seite neu laden und noch '
                         . 'einmal versuchen.'], 400);
}
$an = pk_anlegen($userId, $reg, $bezeichnung);
if (!$an['ok']) {
    json_out(['error' => $an['grund'], 'meldung' => match ($an['grund']) {
        'voll'      => 'Es sind schon ' . PK_HOECHSTENS . ' Passkeys angelegt — bitte zuerst einen entfernen.',
        'vorhanden' => 'Diesen Passkey gibt es hier schon.',
        default     => 'Der Passkey ließ sich nicht speichern.',
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
