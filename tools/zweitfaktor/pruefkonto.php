<?php
declare(strict_types=1);

/**
 * DAS PRÜFKONTO BEKOMMT EINEN ZWEITFAKTOR MIT BEKANNTEM GEHEIMNIS
 * (P5c/AP5, E-P5c-43).
 *
 * Anlass: Nr. 320 (F-P5c-33) — das Prüfkonto `admin@gen-em.org` ist eine BetreiberIn;
 * ohne Zweitfaktor landete jedes Werkzeug nach der Anmeldung im
 * Einrichtungstor, mit einem unbekannten nicht über den Code-Schritt.
 *
 * KEIN SCHALTER, DER DIE PFLICHT ABSCHALTET (E-P5c-43): Das Konto hat einen
 * echten Zweitfaktor, eingerichtet über dieselben Funktionen wie im Tor —
 * nur mit einem Geheimnis, das die Rechner daneben kennen. Die Anmeldung
 * geht durch denselben Code-Schritt wie jede andere.
 *
 * NUR IN DER SANDBOX. Die Adresse der Anlage muss auf 127.0.0.1 oder
 * localhost zeigen; sonst bricht das Skript ab. Auf Staging richtet die
 * BetreiberIn das Konto im Browser ein und trägt das Geheimnis als Secret
 * `STAGING_TOTP` ein.
 *
 * Aufruf: php tools/zweitfaktor/pruefkonto.php [adresse]   (Vorgabe admin@gen-em.org)
 */

$wurzel = dirname(__DIR__, 2);
require_once $wurzel . '/server/db.php';
require_once $wurzel . '/server/instanz_lib.php';
require_once $wurzel . '/server/totp_lib.php';
require_once __DIR__ . '/totp.php';

/* Zehn feste Wiederherstellungscodes, damit auch der Weg über einen Code
 * prüfbar bleibt und der Bilderlauf stets dasselbe Bild bekommt. Nur Zeichen
 * aus `TOTP_CODE_ZEICHEN`. */
const PRUEF_CODES = ['PRFA2345', 'PRFB2345', 'PRFC2345', 'PRFD2345', 'PRFE2345',
                     'PRFF2345', 'PRFG2345', 'PRFH2345', 'PRFJ2345', 'PRFK2345'];

$adresse = $argv[1] ?? 'admin@gen-em.org';
$host = (string)parse_url(app_url(), PHP_URL_HOST);
if (!in_array($host, ['127.0.0.1', 'localhost'], true)) {
    fwrite(STDERR, "Abbruch: Die Anlage heißt „{$host}\" — dieses Skript läuft nur in der Sandbox.\n");
    exit(2);
}
if (!totp_spalten_da()) {
    fwrite(STDERR, "Abbruch: Die Spalten des Zweitfaktors fehlen — erst update.php.\n");
    exit(2);
}
if (serverschluessel() === null) {
    fwrite(STDERR, "Abbruch: kein Serverschlüssel in config.php.\n");
    exit(2);
}
$st = db()->prepare('SELECT id FROM users WHERE email = ?');
$st->execute([$adresse]);
$uid = (int)($st->fetchColumn() ?: 0);
if ($uid === 0) {
    fwrite(STDERR, "Abbruch: kein Konto {$adresse}.\n");
    exit(2);
}

$roh = pruef_totp_base32(PRUEF_TOTP_SANDBOX);
db_transaktion(db(), static function (PDO $pdo) use ($uid, $roh): void {
    $pdo->prepare('UPDATE users SET totp_geheimnis = ?, totp_seit = UTC_TIMESTAMP(),
                          totp_schritt = NULL WHERE id = ?')
        ->execute([sk_versiegeln($roh, 'totp|' . $uid), $uid]);
    totp_codes_schreiben($pdo, $uid, PRUEF_CODES);
});
/* DER ZÄHLER FÄNGT NEU AN: `totp_schritt` ist geleert, also nimmt der Server
 * wieder jeden Schritt im Fenster. Die Zählerdatei der Rechner darf dann
 * nicht vorauslaufen — sie ließe sonst bis zu einem Fenster warten. */
@unlink(pruef_totp_zaehlerdatei(PRUEF_TOTP_SANDBOX));
echo "   Zweitfaktor für {$adresse}: Geheimnis der Sandbox, " . count(PRUEF_CODES) . " Codes\n";
