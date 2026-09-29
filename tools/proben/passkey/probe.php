<?php
declare(strict_types=1);

/**
 * Passkeyprobe — liest `passkey_lib.php` richtig, und lehnt sie ab, was sie
 * ablehnen muss, AN DER STELLE, DIE SIE NENNT? (Schritt 18, SR-09; seit der
 * Gegenlesung H-SR-08 in Web 21.11.0)
 *
 * Anlass: Nr. 350 — Passkeys als zweiter Faktor, gebaut ohne
 * WebAuthn-Bibliothek: ein eigener CBOR-Leser und eine eigene Pruefung beider
 * Zeremonien (E-SR-30). Das ist der Code, bei dem ein falsch gelesenes
 * Laengenfeld einen fremden Schluessel annimmt (E-SR-36).
 *
 * JEDE ABLEHNUNG PRUEFT IHREN GRUND (seit H-SR-08, F-SR-43). Bis Web 21.10.0
 * mass die Probe nur „nicht angenommen" — ein Fall „fremde rpId", der in
 * Wahrheit an der Signatur scheiterte, waere gruen geblieben, und eine
 * Pruefung, die hinter eine andere rutscht, ebenso. Jetzt steht neben jedem
 * Fall ein Stueck der erwarteten Meldung, und bei der Anmeldung dazu ihre Art
 * (`herausforderung`, `zaehler`, `pruefung` — F-SR-37).
 *
 * WAS SIE MISST, ohne Browser und ohne HTTP, mit eigenen Konten:
 *   1. Der CBOR-Leser: Ganzzahlen (auch 8-Byte-Koepfe), Ketten, Listen,
 *      Karten; abgelehnt werden Fliesszahl, Marke, einfacher Wert,
 *      unbestimmte Laenge, reservierte Laenge, abgeschnittene Eingabe, Rest
 *      hinter dem Element, doppelter Schluessel, ein Textschluessel, der wie
 *      eine Zahl aussieht, eine Bytekette als Schluessel, Zahlen ueber 2^63,
 *      Listen und Karten, die mehr versprechen, als Bytes da sind, und neun
 *      BEHAELTER (acht mit einem Wert darin gehen).
 *   2. Registrierung ES256 und RS256 durch, auch mit einer Erweiterungskarte;
 *      abgelehnt mit Grund: fremder Ursprung, fremde rpId, fremde,
 *      abgelaufene und leere Herausforderung, vertauschte Art, fremder Rahmen
 *      (crossOrigin, topOrigin), UP oder AT nicht gesetzt, BS ohne BE,
 *      rawId ungleich Kennung, attestationObject oder COSE keine Karte,
 *      Erweiterungen keine Karte, `alg` -8, fremder `kty`, ein Label zu viel
 *      (der private Teil), ein Textlabel, Punkt nicht auf der Kurve,
 *      Koordinate ab p, RSA unter 2048 und ueber 4096 Bit, RSA-Exponent 1,
 *      2, gerade oder ueber 64 Bit, gerader Modul, Felder vom falschen Typ
 *      (ohne PHP-Warnung), zu gross, nicht kanonisches Base64url; die
 *      Adressen (IP, ohne HTTPS, Standardport, Unterverzeichnis, Umlaut in
 *      Punycode, abschliessender Punkt).
 *   3. Anmeldung ES256 und RS256 durch, der Zaehler steigt; abgelehnt mit
 *      Grund und Art: fremder Ursprung, fremde rpId (beide Verfahren),
 *      fremde und abgelaufene Herausforderung, vertauschte Art, UP nicht
 *      gesetzt, AT gesetzt, falsche Signatur (beide Verfahren), eine leere
 *      DER-Folge, unbekannte Kennung, die Kennung eines anderen Kontos und
 *      einer anderen Adresse; ein zurueckgelaufener Zaehler mit Protokoll
 *      (Name des Passkeys) und hoechstens EINER Mail je Tag (E-SR-46).
 *   4. Die Tabelle: der elfte Passkey an einer Adresse wird abgelehnt, einer
 *      einer anderen Adresse zaehlt nicht mit; eine Kennung zweimal ebenso
 *      (ueber den Hash); ein Konto ohne Zweitfaktor bekommt keinen; die
 *      Bezeichnung wird gesaeubert; `pk_entfernen()` nur am eigenen Konto;
 *      `totp_abschalten()` raeumt alle ab (mit Protokoll); verwaiste raeumt
 *      `totp_einrichtung_beginnen()`.
 *
 * WAS SIE NICHT MISST: einen echten Authenticator und einen echten Browser
 * (Bedienweg `einstellungen_profil_passkey.mjs`, Chromium mit virtuellem
 * Authenticator), die Anbindung an Anmeldung und Endpunkt (Zweitfaktorprobe,
 * Teil 5d), und das atomare Fortschreiben des Zaehlers unter zwei
 * GLEICHZEITIGEN Anmeldungen — das steht im Code (`WHERE zaehler = ?`), ein
 * echter Wettlauf laesst sich hier nicht verlaesslich herstellen.
 *
 * `app.base_url` WIRD FUER DEN LAUF AUF `https://localhost:8443` GESTELLT:
 * Die Sandbox steht auf einer IP-Adresse, und fuer eine IP gibt es keine
 * Passkeys (E-SR-42). Zurueckgelegt wird die Datei byte-gleich, auch nach
 * einem Abbruch (`konfig_stellen()`).
 *
 * Aufruf:  php tools/proben/passkey/probe.php
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht, 2 = nicht gelaufen.
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/totp_lib.php';
require_once $srv . '/passkey_lib.php';
require_once $wurzel . '/tools/sandbox/konfig_stellen.php';
require_once __DIR__ . '/bauen.php';

use phpseclib3\Crypt\PublicKeyLoader;

$pdo = db();
$gut = 0; $schlecht = 0;

function pruefe(bool $ok, string $was, string $sonst = ''): void
{
    global $gut, $schlecht;
    if ($ok) { $gut++; echo "  ok    $was" . ($sonst !== '' ? "  [$sonst]" : '') . "\n"; return; }
    $schlecht++;
    echo "  FEHLT $was" . ($sonst !== '' ? " — $sonst" : '') . "\n";
}
/** Wirft `pk_cbor_lesen()`? Liefert die Meldung oder null. */
function cbor_fehler(string $b): ?string
{
    try { pk_cbor_lesen($b); return null; } catch (PkFehler $f) { return $f->getMessage(); }
}
/** PHP-Warnungen eines Aufrufs zaehlen (Felder vom falschen Typ, F-SR-35). */
function warnungen(callable $f): array
{
    $n = 0;
    set_error_handler(static function () use (&$n): bool { $n++; return true; });
    try { $r = $f(); } finally { restore_error_handler(); }
    return [$r, $n];
}

if (!pk_tabelle_da($pdo) || !db_hat_spalte($pdo, 'passkeys', 'rp_id')) {
    fwrite(STDERR, "Nicht gelaufen: Die Tabelle passkeys fehlt oder ist die Fassung vor H-SR-08 — update.php.\n");
    exit(2);
}

/* ---- Adresse stellen, Konten anlegen --------------------------------------- */

$app = (array)konfig('app');
$app['base_url'] = 'https://localhost:8443';
$zurueck = konfig_stellen(['app' => $app]);
$u = pk_ursprung();

/* BEIDE KONTEN MIT EINGESCHALTETEM ZWEITFAKTOR: `pk_anlegen()` legt fuer ein
 * Konto ohne ihn nichts an (F-SR-38). Das dritte hat keinen. */
$konto = static function (string $mail, string $name, bool $zf) use ($pdo): int {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
    $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter, totp_seit)
                   VALUES (?, ?, 'user', '', '', 320000, " . ($zf ? 'UTC_TIMESTAMP()' : 'NULL') . ')')
        ->execute([$mail, $name]);
    return (int)$pdo->lastInsertId();
};
$uid  = $konto('passkeyprobe@probe.invalid', 'Passkeyprobe', true);
$uid2 = $konto('passkeyprobe-2@probe.invalid', 'Passkeyprobe 2', true);
$uid3 = $konto('passkeyprobe-3@probe.invalid', 'Passkeyprobe 3', false);
try {
    $mailVorher = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn();
} catch (Throwable) { $mailVorher = null; }
register_shutdown_function(static function () use ($pdo, $uid, $uid2, $uid3, $zurueck, $mailVorher): void {
    $pdo->exec('DROP TRIGGER IF EXISTS passkeyprobe_sperre');
    $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id IN (?, ?, ?)')->execute([$uid, $uid2, $uid3]);
    $pdo->prepare('DELETE FROM users WHERE id IN (?, ?, ?)')->execute([$uid, $uid2, $uid3]);
    if ($mailVorher !== null) {
        $pdo->exec("DELETE FROM mail_warteschlange WHERE id > $mailVorher AND schluessel = 'passkey_zaehler'");
    }
    $zurueck();
});

echo "Passkeyprobe (SR-09, H-SR-08) · Ursprung " . ($u['ursprung'] ?? '—') . "\n";

/* ---- 1. Der CBOR-Leser ----------------------------------------------------- */
echo "== 1. CBOR\n";
$w = pk_cbor_lesen(pb_cbor(new PbKarte([1 => 2, -1 => 'ab', 'x' => [0, -1, 23, 24, 255, 65536],
                                        'b' => new PkBytes("\x00\xff")])));
pruefe(is_array($w) && $w[1] === 2 && $w[-1] === 'ab' && $w['x'] === [0, -1, 23, 24, 255, 65536]
       && $w['b'] instanceof PkBytes && $w['b']->b === "\x00\xff",
       'Karte mit Zahlen, Text, Liste und Bytes wird gelesen');
pruefe(cbor_fehler("\x1b\x00\x00\x00\x00\x00\x00\x00\x05") === null && pk_cbor_lesen("\x1b\x00\x00\x00\x00\x00\x00\x00\x05") === 5,
       'ein 8-Byte-Kopf wird gelesen');
$behaelter = static fn(int $n, string $innen): string => str_repeat("\x81", $n - 1) . ($innen === '' ? "\x80" : "\x81" . $innen);
pruefe(cbor_fehler($behaelter(8, '')) === null, 'acht Behaelter (innen leer) gehen');
pruefe(cbor_fehler($behaelter(8, "\x01")) === null, 'acht Behaelter mit einem Wert darin gehen (F-SR-34)',
       (string)cbor_fehler($behaelter(8, "\x01")));
$tief = cbor_fehler($behaelter(9, ''));
pruefe($tief !== null && str_contains($tief, 'tiefer als'), 'neun Behaelter: Ablehnung', (string)$tief);
$faelle = [
    'Fliesszahl (half float)'          => ["\xf9\x3c\x00", 'Fliesszahlen'],
    'Fliesszahl (double)'              => ["\xfb" . pack('E', 1.5), 'Fliesszahlen'],
    'einfacher Wert (true)'            => ["\xf5", 'einfache Werte'],
    'Marke (Datum)'                    => ["\xc1\x1a\x00\x00\x00\x00", 'Marken'],
    'unbestimmte Laenge (Liste)'       => ["\x9f\x01\xff", 'unbestimmte Laenge'],
    'unbestimmte Laenge (Bytes)'       => ["\x5f\x41\x00\xff", 'unbestimmte Laenge'],
    'reservierte Laenge'               => ["\x1c", 'reservierte'],
    'leere Eingabe'                    => ["", 'abgeschnitten'],
    'abgeschnitten (Kopf)'             => ["\x19\x01", 'abgeschnitten'],
    'abgeschnitten (Kette)'            => ["\x45\x00\x01", 'Kette laenger'],
    'abgeschnitten (in einer Liste)'   => ["\x81\x19\x01", 'abgeschnitten'],
    'Rest hinter dem Element'          => ["\x01\x02", 'Rest hinter'],
    'doppelter Schluessel'             => ["\xa2\x01\x01\x01\x02", 'doppelter Schluessel'],
    'Textschluessel "1"'               => ["\xa1\x61\x31\x02", 'sieht aus wie eine Zahl'],
    'Textschluessel "-2"'              => ["\xa1\x62\x2d\x32\x02", 'sieht aus wie eine Zahl'],
    'Schluessel 1 und "1"'             => ["\xa2\x01\x01\x61\x31\x02", 'sieht aus wie eine Zahl'],
    'Bytekette als Schluessel'         => ["\xa1\x41\x00\x01", 'weder Zahl noch Text'],
    'Schluessel ist eine Liste'        => ["\xa1\x80\x01", 'weder Zahl noch Text'],
    'Zahl ueber 2^63 (Major 0)'        => ["\x1b\x80\x00\x00\x00\x00\x00\x00\x00", 'Zahl zu gross'],
    'Zahl ueber 2^63 (Major 1)'        => ["\x3b\xff\xff\xff\xff\xff\xff\xff\xff", 'Zahl zu gross'],
    'Liste verspricht zu viel'         => ["\x9a\x7f\xff\xff\xff\x01", 'Liste laenger'],
    'Karte verspricht zu viel'         => ["\xba\x00\x01\x00\x00\x01\x01", 'Karte laenger'],
    'Textkette kein UTF-8'             => ["\x62\xc3\x28", 'kein UTF-8'],
];
foreach ($faelle as $was => [$b, $erwartet]) {
    $f = cbor_fehler($b);
    pruefe($f !== null && str_contains($f, $erwartet), "Ablehnung: $was", $f ?? 'angenommen');
}

/* ---- 2. Registrierung ------------------------------------------------------ */
echo "== 2. Registrierung\n";
$ec  = pb_paar(-7);
$rsa = pb_paar(-257);
$h = static function (): array { $a = []; pk_herausforderung_stellen($a); return $a; };

$ablage = $h();
$r = pb_registrierung($ec, $ablage['herausforderung'], $u);
$regEc = pk_registrierung_pruefen($ablage, $r['antwort']);
pruefe($regEc['ok'] && $regEc['alg'] === -7 && str_contains($regEc['spki'], 'BEGIN PUBLIC KEY')
       && $regEc['rp_id'] === 'localhost' && $regEc['hash'] === hash('sha256', $r['kennung']),
       'ES256: angenommen, SPKI, rp_id und Hash der Kennung', $regEc['grund'] ?? '');
$kennEc = $r['kennung'];

$ablage = $h();
$r = pb_registrierung($rsa, $ablage['herausforderung'], $u);
$regRsa = pk_registrierung_pruefen($ablage, $r['antwort']);
pruefe($regRsa['ok'] && $regRsa['alg'] === -257, 'RS256: angenommen', $regRsa['grund'] ?? '');
$kennRsa = $r['kennung'];

/* EINE ERWEITERUNGSKARTE hinter dem Schluessel (ED), wie echte Authenticatoren
 * sie mit credProtect liefern — geht durch (F-SR-43). */
$ablage = $h();
$mitExt = pb_registrierung($ec, $ablage['herausforderung'], $u,
    ['flags' => 0xC1, 'cose' => new PbRoh(pb_cbor($ec['cose']) . pb_cbor(new PbKarte(['credProtect' => 2])))]);
$e = pk_registrierung_pruefen($ablage, $mitExt['antwort']);
pruefe($e['ok'], 'mit Erweiterungskarte {credProtect: 2} (ED): angenommen', $e['grund'] ?? '');

/** Eine Ablehnung der Registrierung — mit dem Stueck der erwarteten Meldung. */
$nein = static function (string $was, string $erwartet, array $o, ?array $paar = null, ?array $ablage = null,
                         ?callable $antwortAendern = null) use ($ec, $u, $h): void {
    $ablage ??= $h();
    $r = pb_registrierung($paar ?? $ec, $ablage['herausforderung'], $u, $o);
    $antwort = $antwortAendern ? $antwortAendern($r['antwort']) : $r['antwort'];
    [$e, $warn] = warnungen(static fn() => pk_registrierung_pruefen($ablage, $antwort));
    pruefe(!$e['ok'] && str_contains($e['grund'], $erwartet) && $warn === 0,
           "Registrierung abgelehnt: $was", ($e['ok'] ? 'ANGENOMMEN' : $e['grund']) . ($warn ? ", $warn PHP-Warnungen" : ''));
};
$rsaJwk = json_decode($rsa['privat']->getPublicKey()->toString('JWK'), true)['keys'][0];
$rsaN = pk_b64u_lesen($rsaJwk['n']);
$rsaCose = static fn(string $n, string $e): PbKarte => new PbKarte([1 => 3, 3 => -257, -1 => new PkBytes($n), -2 => new PkBytes($e)]);
$ecJwk = json_decode($ec['privat']->getPublicKey()->toString('JWK'), true)['keys'][0];
$ecX = pk_b64u_lesen($ecJwk['x']); $ecY = pk_b64u_lesen($ecJwk['y']);
$nein('fremder Ursprung', 'fremder Ursprung', ['ursprung' => 'https://boese.invalid']);
$nein('fremde rpId', 'fremde rpId', ['rp_id' => 'boese.invalid']);
$nein('fremde Herausforderung', 'fremde Herausforderung', ['herausforderung' => pk_b64u(random_bytes(32))]);
$abgelaufen = $h(); $abgelaufen['bis'] = time() - 1;
$nein('abgelaufene Herausforderung', 'abgelaufen', [], null, $abgelaufen);
$nein('leere Herausforderung in der Ablage', 'Ablage ungueltig', [], null, ['herausforderung' => '', 'bis' => time() + 60]);
$nein('Art webauthn.get', 'falsche Art', ['art' => 'webauthn.get']);
$nein('crossOrigin true', 'fremden Rahmen', ['client' => ['crossOrigin' => true]]);
$nein('crossOrigin als Text "true"', 'fremden Rahmen', ['client' => ['crossOrigin' => 'true']]);
$nein('topOrigin gesetzt', 'fremden Rahmen', ['client' => ['topOrigin' => 'https://boese.invalid']]);
$nein('UP nicht gesetzt', 'UP fehlt', ['flags' => 0x40]);
$nein('AT nicht gesetzt', 'AT fehlt', ['flags' => 0x01]);
$nein('BS ohne BE', 'BS ohne BE', ['flags' => 0x51]);
$nein('rawId ungleich Kennung', 'rawId', ['rawId' => random_bytes(32)]);
$nein('attestationObject ist eine Liste', 'attestationObject: keine Karte', [], null, null,
      static fn(array $a): array => ['attestationObject' => pk_b64u(pb_cbor([1, 2]))] + $a);
$nein('COSE-Schluessel ist eine Liste', 'COSE-Schluessel ist keine Karte', ['cose' => new PbRoh(pb_cbor([1, 2]))]);
$nein('Erweiterungen sind eine Zahl (ED)', 'Erweiterungen sind keine Karte',
      ['flags' => 0xC1, 'cose' => new PbRoh(pb_cbor($ec['cose']) . "\x05")]);
$nein('alg -8 (EdDSA)', 'Verfahren nicht erlaubt', ['cose' => new PbKarte([1 => 1, 3 => -8, -1 => 6, -2 => new PkBytes(random_bytes(32))])]);
$nein('kty fremd (OKP bei -7)', 'kein EC2-Schluessel', ['cose' => new PbKarte([1 => 1, 3 => -7, -1 => 1,
      -2 => new PkBytes($ecX), -3 => new PkBytes($ecY)])]);
$nein('ein Label zu viel (-4, der private Teil)', 'Labels passen nicht', ['cose' => new PbKarte([1 => 2, 3 => -7, -1 => 1,
      -2 => new PkBytes($ecX), -3 => new PkBytes($ecY), -4 => new PkBytes(random_bytes(32))])]);
$nein('Textlabel "1" im COSE-Schluessel', 'sieht aus wie eine Zahl',
      ['cose' => new PbRoh("\xa5\x61\x31\x02\x03\x26\x20\x01\x21\x58\x20" . $ecX . "\x22\x58\x20" . $ecY)]);
$nein('Punkt nicht auf der Kurve', 'phpseclib lehnt', ['cose' => new PbKarte([1 => 2, 3 => -7, -1 => 1,
      -2 => new PkBytes(str_repeat("\x01", 32)), -3 => new PkBytes(str_repeat("\x02", 32))])]);
$nein('Koordinate ab p', 'nicht kleiner als p', ['cose' => new PbKarte([1 => 2, 3 => -7, -1 => 1,
      -2 => new PkBytes(str_repeat("\xff", 32)), -3 => new PkBytes($ecY)])]);
$nein('COSE mit Fliesszahl', 'Fliesszahlen', ['cose' => new PbRoh("\xa1\x01\xf9\x3c\x00")]);
$nein('RSA mit 1024 Bit', 'zu kurz', [], pb_paar(-257, 1024));
$lang = random_bytes(640); $lang[0] = "\xc0"; $lang[639] = chr(ord($lang[639]) | 1);
$nein('RSA mit 5120 Bit', 'zu lang', ['cose' => $rsaCose($lang, "\x01\x00\x01")], $rsa);
$nein('RSA-Exponent 1 (Signatur ohne Geheimnis)', 'Exponent', ['cose' => $rsaCose($rsaN, "\x01")], $rsa);
$nein('RSA-Exponent 2', 'Exponent', ['cose' => $rsaCose($rsaN, "\x02")], $rsa);
$nein('RSA-Exponent gerade (65536)', 'Exponent', ['cose' => $rsaCose($rsaN, "\x01\x00\x00")], $rsa);
$nein('RSA-Exponent ueber 64 Bit', 'Exponent', ['cose' => $rsaCose($rsaN, "\x01" . str_repeat("\x00", 7) . "\x01")], $rsa);
$gerade = $rsaN; $gerade[strlen($gerade) - 1] = chr(ord($gerade[-1]) & 0xfe);
$nein('RSA-Modul gerade', 'Modul gerade', ['cose' => $rsaCose($gerade, "\x01\x00\x01")], $rsa);
/* DIE GRENZEN VON INNEN (Nachpruefung A-3): was echte Authenticatoren liefern
 * und die Werte genau an der Kante gehen durch; ein Bit, ein Byte darueber
 * nicht. Registrierung prueft keine Signatur — der COSE-Schluessel genuegt. */
$ja = static function (string $was, array $o, array $paar) use ($u, $h): void {
    $ablage = $h();
    $r = pb_registrierung($paar, $ablage['herausforderung'], $u, $o);
    $e = pk_registrierung_pruefen($ablage, $r['antwort']);
    pruefe($e['ok'], "Registrierung angenommen: $was", $e['grund'] ?? '');
};
$rsa4096 = pb_paar(-257, 4096);
$ja('RSA mit 4096 Bit und 65537', [], $rsa4096);
$n4096 = pk_b64u_lesen(json_decode($rsa4096['privat']->getPublicKey()->toString('JWK'), true)['keys'][0]['n']);
$nein('RSA mit 4097 Bit', 'zu lang', ['cose' => $rsaCose("\x01" . $n4096, "\x01\x00\x01")], $rsa);
$ja('RSA-Exponent 3 (Untergrenze)', ['cose' => $rsaCose($rsaN, "\x03")], $rsa);
$ja('RSA-Exponent mit 64 Bit (Obergrenze)', ['cose' => $rsaCose($rsaN, str_repeat("\xff", 8))], $rsa);
$nein('RSA-Exponent mit fuehrendem Nullbyte', 'nicht minimal', ['cose' => $rsaCose($rsaN, "\x00\x01\x00\x01")], $rsa);
$nein('RSA-Modul mit fuehrendem Nullbyte', 'nicht minimal', ['cose' => $rsaCose("\x00" . $rsaN, "\x01\x00\x01")], $rsa);
$nein('attStmt ist eine Liste', 'attestationObject: Aufbau', [], null, null,
      static function (array $a): array {
          $obj = pk_cbor_lesen(pk_b64u_lesen($a['attestationObject']));
          return ['attestationObject' => pk_b64u(pb_cbor(new PbKarte(['fmt' => 'none', 'attStmt' => [1, 2],
                   'authData' => $obj['authData']])))] + $a;
      });
$nein('rawId als Liste (keine PHP-Warnung)', 'rawId fehlt oder ist kein Text', [], null, null,
      static fn(array $a): array => ['rawId' => ['x']] + $a);
$nein('clientDataJSON zu gross', 'zu gross', [], null, null,
      static fn(array $a): array => ['clientDataJSON' => str_repeat('A', 50000)] + $a);
$nein('rawId mit Zeilenumbruch', 'kein Base64url', [], null, null,
      static fn(array $a): array => ['rawId' => $a['rawId'] . "\n"] + $a);
pruefe(pk_b64u_lesen('QR') === null && pk_b64u_lesen('QQ') === "A", 'Base64url nur kanonisch („QR" abgelehnt, „QQ" gelesen)');
/* DER FELDDECKEL AUF DAS BYTE (Nachpruefung B-2): PK_FELD_MAX geht, eins mehr nicht. */
$feld = static function (int $n): string {
    try { return (string)strlen(pk_feld(['x' => pk_b64u(str_repeat("\0", $n))], 'x')); }
    catch (PkFehler $f) { return $f->getMessage(); }
};
pruefe($feld(PK_FELD_MAX) === (string)PK_FELD_MAX && str_contains($feld(PK_FELD_MAX + 1), 'zu gross'),
       'Felddeckel: ' . PK_FELD_MAX . ' Byte gehen, ' . (PK_FELD_MAX + 1) . ' nicht', $feld(PK_FELD_MAX + 1));
/* DER GESPEICHERTE SCHLUESSEL (Nachpruefung A-6): ausserhalb der Grenzen laedt er nicht. */
$spki = static fn(string $n, string $e): string => PublicKeyLoader::loadPublicKey((string)json_encode(
    ['kty' => 'RSA', 'n' => pk_b64u($n), 'e' => pk_b64u($e)]))->toString('PKCS8');
pruefe(pk_spki_laden($spki($rsaN, "\x01\x00\x01"), -257) !== null
       && pk_spki_laden($spki($rsaN, "\x01" . str_repeat("\x00", 7) . "\x01"), -257) === null
       && pk_spki_laden($spki("\x01" . $n4096, "\x01\x00\x01"), -257) === null,
       'gespeicherter RSA-Schluessel: in den Grenzen geladen, Exponent ueber 64 Bit und 4097 Bit nicht');

/* KEINE DOMAIN: die Anlage auf einer IP-Adresse (und einmal ohne HTTPS). In
 * EINEM EIGENEN PROZESS — `app_url()` merkt sich die Adresse je Anfrage, und
 * in diesem Prozess steht sie schon fest (die erste Fassung fragte hier und
 * bekam die alte Antwort). */
$ursprungBei = static function (string $basis) use ($app, $srv): string {
    $a = $app; $a['base_url'] = $basis;
    $zurueck = konfig_stellen(['app' => $a]);
    $aus = shell_exec('php -r ' . escapeshellarg('require "' . $srv . '/db.php"; require "' . $srv
                      . '/passkey_lib.php"; echo json_encode(pk_ursprung(), JSON_UNESCAPED_SLASHES);') . ' 2>&1');
    $zurueck();
    return trim((string)$aus);
};
pruefe($ursprungBei('https://127.0.0.1:8443') === 'null', 'eine IP-Adresse als base_url: keine Passkeys');
pruefe($ursprungBei('http://beispiel.invalid') === 'null', 'ohne HTTPS (ausser localhost): keine Passkeys');
$x = $ursprungBei('https://beispiel.invalid:443/einsatz');
pruefe($x === '{"ursprung":"https://beispiel.invalid","rp_id":"beispiel.invalid"}',
       'Standardport und Unterverzeichnis: Ursprung ohne beides', $x);
$x = $ursprungBei('https://beispiel.invalid.');
pruefe($x === '{"ursprung":"https://beispiel.invalid","rp_id":"beispiel.invalid"}', 'abschliessender Punkt faellt weg (F-SR-40)', $x);
$x = $ursprungBei('https://münchen.invalid');
pruefe($x === (function_exists('idn_to_ascii')
                 ? '{"ursprung":"https://xn--mnchen-3ya.invalid","rp_id":"xn--mnchen-3ya.invalid"}' : 'null'),
       'Umlaut-Name in Punycode (ohne intl: keine Passkeys)', $x);
$x = $ursprungBei('https://straße.invalid');
pruefe($x === (function_exists('idn_to_ascii')
                 ? '{"ursprung":"https://xn--strae-oqa.invalid","rp_id":"xn--strae-oqa.invalid"}' : 'null'),
       'ß ohne Uebergangsregeln wie der Browser (xn--strae-oqa, nicht strasse — Nachpruefung D-1)', $x);

/* ---- 3. Anlegen und Anmeldung ---------------------------------------------- */
echo "== 3. Anlegen und Anmeldung\n";
$aEc  = pk_anlegen($uid, $regEc, 'Handy');
$aRsa = pk_anlegen($uid, $regRsa, '');
pruefe($aEc['ok'] && $aRsa['ok'] && pk_zahl($uid) === 2, 'zwei angelegt', json_encode([$aEc, $aRsa]));
pruefe(str_starts_with(pk_anzeigename(pk_liste($uid)[1]), 'Passkey vom '),
       'ohne Bezeichnung: „Passkey vom <Datum>"', pk_anzeigename(pk_liste($uid)[1]));
$doppelt = pk_anlegen($uid2, $regEc, 'fremd');
pruefe(!$doppelt['ok'] && $doppelt['grund'] === 'vorhanden', 'dieselbe Kennung an einem zweiten Konto: abgelehnt (Hash)');
pruefe(!pk_anlegen($uid, $regEc, str_repeat('x', 41))['ok'], 'Bezeichnung mit 41 Zeichen: abgelehnt');
$ohneZf = pk_anlegen($uid3, $regRsa, 'x');
pruefe(!$ohneZf['ok'] && $ohneZf['grund'] === 'zweitfaktor', 'Konto ohne Zweitfaktor: kein Passkey (F-SR-38)', json_encode($ohneZf));

$ablage = $h();
$an = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 5]));
$z = (int)$pdo->query("SELECT zaehler FROM passkeys WHERE id = {$aEc['id']}")->fetchColumn();
pruefe($an['ok'] && $z === 5, 'ES256: Anmeldung durch, Zaehler 5 gespeichert', $an['grund'] ?? "Zaehler $z");
$ablage = $h();
$an = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($rsa, $kennRsa, $ablage['herausforderung'], $u, ['zaehler' => 0]));
pruefe($an['ok'], 'RS256: Anmeldung durch (beide Zaehler 0 — synchronisierter Passkey)', $an['grund'] ?? '');
$ablage = $h();
$an = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($rsa, $kennRsa, $ablage['herausforderung'], $u, ['zaehler' => 0]));
pruefe($an['ok'], '… und noch einmal mit 0', $an['grund'] ?? '');

/** Eine Ablehnung der Anmeldung — mit Grund und Art. */
$nicht = static function (string $was, string $erwartet, string $art, array $o, int $konto = 0, ?array $ablage = null,
                          ?array $paar = null, ?string $kennung = null, ?callable $aendern = null)
                          use ($ec, $kennEc, $u, $h, $uid): void {
    $ablage ??= $h();
    $antwort = pb_anmeldung($paar ?? $ec, $o['kennung'] ?? $kennung ?? $kennEc, $ablage['herausforderung'], $u, $o + ['zaehler' => 9]);
    if ($aendern) { $antwort = $aendern($antwort); }
    [$e, $warn] = warnungen(static fn() => pk_anmeldung_pruefen($konto ?: $uid, $ablage, $antwort));
    pruefe(!$e['ok'] && str_contains($e['grund'], $erwartet) && $e['art'] === $art && $warn === 0,
           "Anmeldung abgelehnt: $was", $e['ok'] ? 'ANGENOMMEN' : $e['grund'] . ' · ' . $e['art'] . ($warn ? ", $warn Warnungen" : ''));
};
$nicht('fremder Ursprung', 'fremder Ursprung', 'pruefung', ['ursprung' => 'https://boese.invalid']);
$nicht('fremde rpId', 'fremde rpId', 'pruefung', ['rp_id' => 'boese.invalid']);
$nicht('fremde rpId (RS256)', 'fremde rpId', 'pruefung', ['rp_id' => 'boese.invalid'], 0, null, $rsa, $kennRsa);
$nicht('fremde Herausforderung (zaehlt nicht)', 'fremde Herausforderung', 'herausforderung',
       ['herausforderung' => pk_b64u(random_bytes(32))]);
$nicht('Art webauthn.create', 'falsche Art', 'pruefung', ['art' => 'webauthn.create']);
$nicht('UP nicht gesetzt', 'UP fehlt', 'pruefung', ['flags' => 0x04]);
$nicht('AT gesetzt', 'AT bei einer Anmeldung', 'pruefung', ['flags' => 0x41]);
$nicht('falsche Signatur', 'Signatur passt nicht', 'pruefung', ['falsch' => true]);
$nicht('falsche Signatur (RS256)', 'Signatur passt nicht', 'pruefung', ['falsch' => true], 0, null, $rsa, $kennRsa);
$nicht('leere DER-Folge als Signatur', 'keine DER-Folge', 'pruefung', [], 0, null, null, null,
       static fn(array $a): array => ['signature' => pk_b64u("\x30\x00")] + $a);
/* DIE FORM DER ES256-SIGNATUR VOR PHPSECLIB (Nachpruefung A-1, A-2): jede
 * dieser Eingaben kostete dort Speicher, eine Ausnahme oder eine PHP-Warnung. */
foreach (['4 KiB geschachtelte unbestimmte Laengen' => str_repeat("\x30\x80", 2048),
          'Zeitangabe mit Nullbyte statt s'         => "\x30\x06\x02\x01\x01\x17\x01\x00",
          'fremdes Etikett statt r'                 => "\x30\x06\x82\x01\x01\x02\x01\x01",
          'konstruierte Bitkette'                   => "\x30\x04\x23\x02\x02\x00",
          'Rest hinter der Folge'                   => "\x30\x06\x02\x01\x01\x02\x01\x01\x00"] as $was => $sig) {
    $nicht("Signatur: $was", 'keine DER-Folge', 'pruefung', [], 0, null, null, null,
           static fn(array $a): array => ['signature' => pk_b64u($sig)] + $a);
}
$nicht('RS256-Signatur mit falscher Laenge', 'Laenge passt nicht', 'pruefung', [], 0, null, $rsa, $kennRsa,
       static fn(array $a): array => ['signature' => pk_b64u(random_bytes(255))] + $a);
$nicht('BS ohne BE (Anmeldung)', 'BS ohne BE', 'pruefung', ['flags' => 0x11]);
$nicht('Feld als Liste (keine PHP-Warnung)', 'signature fehlt oder ist kein Text', 'pruefung', [], 0, null, null, null,
       static fn(array $a): array => ['signature' => ['x']] + $a);
$nicht('unbekannte Kennung', 'unbekannte Kennung', 'pruefung', ['kennung' => random_bytes(32)]);
$nicht('die Kennung am falschen Konto', 'unbekannte Kennung', 'pruefung', [], $uid2);
$ablage = $h(); $ablage['bis'] = time() - 1;
$nicht('abgelaufene Herausforderung (zaehlt nicht)', 'abgelaufen', 'herausforderung', [], 0, $ablage);

/* EIN PASSKEY EINER ANDEREN ADRESSE (E-SR-48): gilt hier nicht, zaehlt nicht,
 * wird nicht angeboten. */
$fremdPaar = pb_paar(-7);
$fremdKenn = random_bytes(32);
$pdo->prepare("INSERT INTO passkeys (user_id, credential_id, credential_hash, rp_id, oeffentlich, alg, zaehler, bezeichnung, angelegt_am)
               VALUES (?, ?, ?, 'alt.invalid', ?, -7, 0, 'Alt', UTC_TIMESTAMP())")
    ->execute([$uid, pk_b64u($fremdKenn), hash('sha256', $fremdKenn), $fremdPaar['privat']->getPublicKey()->toString('PKCS8')]);
$fremdId = (int)$pdo->lastInsertId();
$liste = pk_liste($uid);
$fremdZeile = array_values(array_filter($liste, static fn(array $p): bool => $p['id'] === $fremdId))[0] ?? null;
pruefe(pk_zahl($uid) === 2 && pk_zahl($uid, true) === 3 && !in_array(pk_b64u($fremdKenn), pk_kennungen($uid), true)
       && $fremdZeile !== null && $fremdZeile['hier'] === false && $fremdZeile['rp_id'] === 'alt.invalid',
       'Passkey einer anderen Adresse: nicht gezaehlt, nicht angeboten, als fremd gelistet',
       pk_zahl($uid) . '/' . pk_zahl($uid, true));
$nicht('Kennung einer anderen Adresse', 'unbekannte Kennung', 'pruefung', [], 0, null, $fremdPaar, $fremdKenn);

/* DER ZAEHLER (E-SR-33, E-SR-46): zurueck → Ablehnung der Art `zaehler`,
 * Protokoll mit Namen, eine Mail; ein zweites Mal am selben Tag keine zweite
 * Mail; nach einem Tag wieder eine. */
$zaehle = static function (string $sql) use ($pdo): int { return (int)$pdo->query($sql)->fetchColumn(); };
$protSql = "SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_zaehler' AND betroffen_user_id = $uid";
$mailSql = $mailVorher === null ? 'SELECT -1'
         : "SELECT COUNT(*) FROM mail_warteschlange WHERE id > $mailVorher AND schluessel = 'passkey_zaehler'";
$vor = $zaehle($protSql);
$ablage = $h();
$e = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 3]));
$text = (string)$pdo->query("SELECT text FROM protokoll_ereignisse WHERE art = 'passkey_zaehler'
                               AND betroffen_user_id = $uid ORDER BY id DESC LIMIT 1")->fetchColumn();
pruefe(!$e['ok'] && $e['art'] === 'zaehler' && $zaehle($protSql) === $vor + 1 && str_contains($text, '„Handy"')
       && pk_zahl($uid) === 2 && $zaehle($mailSql) === 1,
       'Zaehler zurueck (5 → 3): Art zaehler, Protokoll mit Namen, eine Mail, der Passkey bleibt',
       ($e['ok'] ? 'ANGENOMMEN' : $e['grund'] . ' · ' . $e['art']) . ", Mail " . $zaehle($mailSql) . ", Text $text");
/* EINE STUNDE ZURUECK, nicht dieselbe Sekunde: Sonst aenderte auch ein
 * `UPDATE` ohne Deckel die Zeile nicht, und die Gegenprobe „Deckel
 * herausgenommen" bliebe gruen (gemessen beim Bau). */
$pdo->exec("UPDATE passkeys SET gewarnt_am = UTC_TIMESTAMP() - INTERVAL 1 HOUR WHERE id = {$aEc['id']}");
$ablage = $h();
$e = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 5]));
pruefe(!$e['ok'] && $e['art'] === 'zaehler' && $zaehle($protSql) === $vor + 2 && $zaehle($mailSql) === 1,
       'Zaehler gleich (5 → 5): abgelehnt, protokolliert, keine zweite Mail am selben Tag', 'Mail ' . $zaehle($mailSql));
$pdo->exec("UPDATE passkeys SET gewarnt_am = UTC_TIMESTAMP() - INTERVAL 25 HOUR WHERE id = {$aEc['id']}");
$ablage = $h();
$e = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 4]));
pruefe(!$e['ok'] && $zaehle($mailSql) === 2, 'nach mehr als einem Tag: wieder eine Mail', 'Mail ' . $zaehle($mailSql));
$ablage = $h();
$e = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 6]));
pruefe($e['ok'], 'danach mit hoeherem Zaehler (6) wieder durch', $e['grund'] ?? '');

/* ---- 4. Die Tabelle -------------------------------------------------------- */
echo "== 4. Tabelle\n";
for ($i = pk_zahl($uid); $i < PK_HOECHSTENS; $i++) {
    $ablage = $h();
    $r = pb_registrierung($ec, $ablage['herausforderung'], $u);
    pk_anlegen($uid, pk_registrierung_pruefen($ablage, $r['antwort']), 'Nr. ' . ($i + 1));
}
$ablage = $h();
$r = pb_registrierung($ec, $ablage['herausforderung'], $u);
$elfter = pk_anlegen($uid, pk_registrierung_pruefen($ablage, $r['antwort']), 'Nr. 11');
pruefe(pk_zahl($uid) === PK_HOECHSTENS && pk_zahl($uid, true) === PK_HOECHSTENS + 1 && !$elfter['ok'] && $elfter['grund'] === 'voll',
       'der elfte an dieser Adresse: abgelehnt (der der anderen Adresse zaehlt nicht mit)',
       pk_zahl($uid) . ' hier, ' . pk_zahl($uid, true) . ' insgesamt, ' . json_encode($elfter));
$ablage = $h();
$r = pb_registrierung($ec, $ablage['herausforderung'], $u);
$nbsp = pk_anlegen($uid2, pk_registrierung_pruefen($ablage, $r['antwort']), "\u{00a0}\u{200b}\u{00a0}");
$ablage = $h();
$r = pb_registrierung($ec, $ablage['herausforderung'], $u);
$umbruch = pk_anlegen($uid2, pk_registrierung_pruefen($ablage, $r['antwort']), "Hand\ny\x07");
$namen = array_column(pk_liste($uid2), 'bezeichnung');
pruefe($nbsp['ok'] && $umbruch['ok'] && $namen === ['', 'Hand y'],
       'Bezeichnung gesaeubert: nur Leerraum → leer („Passkey vom …"), Umbruch und Steuerzeichen → Leerzeichen',
       json_encode($namen, JSON_UNESCAPED_UNICODE));
pruefe(!pk_entfernen($uid2, $aEc['id']) && pk_zahl($uid) === PK_HOECHSTENS,
       'pk_entfernen() an einem fremden Konto: nichts');
pruefe(pk_entfernen($uid, $aEc['id']) && pk_zahl($uid) === PK_HOECHSTENS - 1, 'pk_entfernen() am eigenen Konto: weg');
/* ALLES ODER NICHTS (F-SR-38, Nachpruefung D-4): Scheitert das Loeschen der
 * Passkeys, bleiben Faktor, Codes und gemerkte Geraete stehen. Ein Ausloeser
 * an `passkeys` schlaegt fuer dieses Konto fehl; bis Web 21.10.0 stand das
 * Loeschen hinter der Transaktion, und der Faktor war trotzdem aus. */
$pdo->prepare("INSERT INTO totp_codes (user_id, hash) VALUES (?, 'x')")->execute([$uid]);
$pdo->prepare('INSERT INTO vertraute_geraete (user_id, token_hash, angelegt_am) VALUES (?, ?, UTC_TIMESTAMP())')
    ->execute([$uid, hash('sha256', random_bytes(16))]);
$stand = static fn(): string => (string)json_encode($pdo->query(
    "SELECT (SELECT totp_seit IS NOT NULL FROM users WHERE id = $uid),
            (SELECT COUNT(*) FROM totp_codes WHERE user_id = $uid),
            (SELECT COUNT(*) FROM vertraute_geraete WHERE user_id = $uid),
            (SELECT COUNT(*) FROM passkeys WHERE user_id = $uid)")->fetch(PDO::FETCH_NUM));
$vorher = $stand();
$pdo->exec('DROP TRIGGER IF EXISTS passkeyprobe_sperre');
$pdo->exec("CREATE TRIGGER passkeyprobe_sperre BEFORE DELETE ON passkeys FOR EACH ROW
            BEGIN IF OLD.user_id = $uid THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'passkeyprobe'; END IF; END");
$warf = false;
try { totp_abschalten($uid, 'verwaltung'); } catch (Throwable) { $warf = true; }
$pdo->exec('DROP TRIGGER IF EXISTS passkeyprobe_sperre');
pruefe($warf && $stand() === $vorher && str_starts_with($vorher, '[1,'),
       'totp_abschalten() mit scheiterndem Loeschen: wirft, Faktor, Codes, Geraete und Passkeys stehen',
       ($warf ? 'warf' : 'warf nicht') . " · vorher $vorher, nachher " . $stand());
$vorP = $zaehle("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_entfernt' AND betroffen_user_id = $uid");
totp_abschalten($uid, 'verwaltung');
$nachP = $zaehle("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_entfernt' AND betroffen_user_id = $uid");
pruefe(pk_zahl($uid, true) === 0 && $nachP === $vorP + 1 && $stand() === '[0,0,0,0]',
       'totp_abschalten(): alle Passkeys weg (auch der anderen Adresse), ein Protokolleintrag, Codes und Geraete weg',
       pk_zahl($uid, true) . " da, Protokoll $vorP → $nachP, Stand " . $stand());
/* VERWAIST: eine Zeile an einem Konto ohne Zweitfaktor (ein Weg vorbei an
 * totp_abschalten(), etwa das SQL des Notwegs) — die Einrichtung raeumt sie. */
$waise = random_bytes(32);
$pdo->prepare("INSERT INTO passkeys (user_id, credential_id, credential_hash, rp_id, oeffentlich, alg, zaehler, bezeichnung, angelegt_am)
               VALUES (?, ?, ?, 'localhost', 'x', -7, 0, 'Waise', UTC_TIMESTAMP())")
    ->execute([$uid3, pk_b64u($waise), hash('sha256', $waise)]);
$ein = totp_einrichtung_beginnen($uid3);
$prot = (string)$pdo->query("SELECT daten FROM protokoll_ereignisse WHERE art = 'passkey_entfernt'
                              AND betroffen_user_id = $uid3 ORDER BY id DESC LIMIT 1")->fetchColumn();
pruefe(($ein['ok'] ?? false) && pk_zahl($uid3, true) === 0 && (json_decode($prot, true)['weg'] ?? '') === 'einrichtung',
       'verwaiste Passkeys: beim Einrichten des Zweitfaktors geraeumt, mit Protokoll', json_encode($ein['grund'] ?? 'ok') . " $prot");

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
