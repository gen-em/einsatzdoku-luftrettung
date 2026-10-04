<?php
declare(strict_types=1);

/**
 * Zweitfaktorprobe — hält der Zweitfaktor, was E-P5c-15, -41, -42, -53, -54
 * zusagen? (P5c/AP5)
 *
 * Anlass: Nr. 303 (F-P5c-31: die Code-Abfrage muss VOR der Sitzung stehen,
 * sonst gilt `user_id` schon als angemeldet), F-P5c-37 (Datenmodell, keine
 * Wiederholung), E-P5c-54 (Demo-Reset). Die Abnahme von AP5 verlangt die
 * RFC-Vektoren 6/6.
 *
 * WAS SIE MISST.
 *   1. RFC 6238, Anhang B: sechs Zeitpunkte, SHA-1, acht Stellen — `totp_code()`
 *      rechnet sie nach. Dazu der Rechner der Werkzeuge (`tools/zweitfaktor/`)
 *      gegen dieselben Werte.
 *   2. Kein Code gilt zweimal (`totp_code_passt()` mit dem letzten Schritt), und
 *      ein Wiederherstellungscode wird normiert (Leerzeichen, Bindestrich, klein).
 *   2b. Ein Fehler der Datenbank schaltet den Zweitfaktor nicht stumm
 *      (F-P5c-166): `totp_spalten_da()` bricht mit einer gestörten
 *      Verbindung ab. `rw_zustand()` misst die Rückwegprobe (A7). Das Tor in
 *      `auth_guard.php` hat dieselbe Unterscheidung, gemessen ist es nicht
 *      (es läuft nur über HTTP, und dort lässt sich die Datenbank nicht
 *      gezielt stören).
 *   3. Über HTTP, gegen die Anlage, mit einem eigenen Konto der Rolle admin:
 *      Passwort → 303 auf `login.php`, KEINE Sitzung (API 401); falscher Code
 *      → Meldung; richtiger Code → 302 auf `index.php`; derselbe Code noch
 *      einmal → abgewiesen; ein Wiederherstellungscode → angemeldet, derselbe
 *      noch einmal → abgewiesen; `Zurück zur Anmeldung` beendet den halben
 *      Stand; fünf falsche Codes → Sperre, der halbe Stand endet.
 *   4. Das Einrichtungstor: dasselbe Konto ohne Zweitfaktor → jede Seite 302
 *      auf `zweitfaktor.php`, API 403, `zweitfaktor.php` 200 mit QR-Adresse und
 *      Geheimnis; mit Code eingeschaltet → zehn Codes, danach lässt das Tor
 *      durch.
 *   5. Die Selbstlöschung wird erst mit dem Code zurückgenommen, nicht schon
 *      mit dem Passwort.
 *   6. Der Demo-Reset leert die Spalten (E-P5c-54) — gemessen an
 *      `demo_zweitfaktor_leeren()` und am Aufruf im Reset, nicht am Reset
 *      selbst (F-P5c-117).
 *   6b. Der Demo-Reset kommt nur nach einer Aenderung (Nr. 76, E-R4-10,
 *      E-R4-42): die Rechnung `demo_reset_faellig_ab()` als Tabelle, die
 *      Marke „nur die erste zaehlt" an `app_state`, und die zwei Setzstellen
 *      am Quelltext (POST mit Token in `auth_guard.php`, nach dem Commit in
 *      `ingest.php`). Den Reset selbst loest sie auch hier nicht aus.
 *   5b. „Gerät merken" (Schritt 18, SR-02, E-SR-07, -17, -18): Haken mit der
 *      Dauer der Rollengruppe, gemerkt nur nach App-Code, danach ohne Code;
 *      „Alle vergessen", Passwortwechsel, Abschalten und Ablauf vergessen;
 *      ein fremdes Cookie zählt nicht; Dauer 0 und eine verkürzte Dauer
 *      gelten sofort; der Aufräumschritt; Protokoll.
 *   5c. Der frische Code (SR-07, E-SR-20): Schlüsselblatt und Griff ohne
 *      frischen Code → Bestätigung, zurück nur auf eine Seite der Liste.
 *   5d. Passkeys an der Anlage (SR-09, E-SR-31, -32): der Knopf im
 *      Code-Schritt, Anmeldung mit Passkey zählt wie ein App-Code (Gerät
 *      merken, frischer Code), eine falsche Signatur nicht; eine alte
 *      Herausforderung mit frischer Signatur und ein zurückgelaufener Zähler
 *      werden abgewiesen, aber nicht als Fehlversuch gezählt, der Zähler mit
 *      eigenem Text und einer Mail (seit H-SR-08); die Bestätigungsseite nimmt
 *      ihn; der Endpunkt ohne frischen Code 403 JSON, mit frischem Code legt
 *      er an (Protokoll, Mail); Entfernen über die Karte (Protokoll, Mail).
 *      Die Prüfung selbst misst die Passkeyprobe — hier geht es um die
 *      Anbindung.
 *   7. Der Bus-Faktor (E-P5c-16, -56): Ein Konto ohne Zweitfaktor zählt nicht
 *      als handlungsfähig; dazu die Tabelle der Lagen (Rollenmix → Plakette
 *      und Ton) über `status_verwaltungszeile()` mit gesetzten Zahlen —
 *      der Bestand der Anlage zeigt immer nur eine davon (F-P5c-114).
 *   8. Der Notzugang der einzigen BetreiberIn (Schritt 18, SR-04, E-SR-13,
 *      -24, -81 bis -87): jede Sitzung ihr Name, ein Aufruf schreibt keine
 *      Datei, der fehlende Wert entsteht beim Aufruf; zehn Lagen, in denen die
 *      Tür zu bleibt (ohne Datei, Datei einer anderen Sitzung, ohne Wert,
 *      falscher Wert, zwei BetreiberInnen, falsches Passwort, Admin-Konto,
 *      unbekannte Adresse, gesperrt, Zweitfaktor aus), dazu angemeldet als
 *      Admin — alle derselbe Rumpf, dieselbe Dauer, nichts geändert; richtig
 *      → Zweitfaktor aus, Protokoll, Mail, Dateien weg, Wert neu, danach
 *      Einrichtungstor; der alte Wert gilt nicht mehr; sechs Fehlversuche →
 *      Topf `notweg`. Die übrigen BetreiberInnen sind dafür kurz Admin.
 *
 * WAS SIE NICHT MISST: den Ablauf der fünf Minuten des halben Standes (sie
 * wartet nicht; eine Sitzung mit abgelaufener Frist stellt die Wartungsprobe
 * in Erwartung 12a her), den QR-Code als Bild (Bedienweg
 * `zweitfaktor-einrichten`, jsQR), und die Anmeldung der Werkzeuge (jedes
 * Werkzeug selbst).
 *
 * SIE RÄUMT AUF: ihre Konten, deren Protokoll- und Ratenzeilen — im Schluss,
 * auch nach einem Abbruch. Teil 8 stellt dazu die Rolle der übrigen
 * BetreiberInnen, den Wert in `app_state` und das Anwendungsverzeichnis
 * zurück (ohne Nachweisdatei).
 *
 * Aufruf:  php tools/proben/zweitfaktor/probe.php [basisadresse]   (Vorgabe http://127.0.0.1:8080)
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/totp_lib.php';
require_once $srv . '/ratelimit_lib.php';
require_once $srv . '/demo_lib.php';
require_once $srv . '/serverkrypto_lib.php';
require_once $srv . '/sitzung_lib.php';
require_once $srv . '/status_lib.php';
require_once $wurzel . '/tools/zweitfaktor/totp.php';
require_once $srv . '/passkey_lib.php';
require_once $wurzel . '/tools/sandbox/konfig_stellen.php';
require_once $wurzel . '/tools/proben/passkey/bauen.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo = db();
$gut = 0; $schlecht = 0;

function pruefe(bool $ok, string $was, string $sonst = ''): void
{
    global $gut, $schlecht;
    if ($ok) { $gut++; echo "  ok    $was\n"; return; }
    $schlecht++;
    echo "  FEHLT $was" . ($sonst !== '' ? " — $sonst" : '') . "\n";
}

/* ---- 1. RFC 6238, Anhang B -------------------------------------------------- */
echo "== 1. RFC 6238, Anhang B (SHA-1, acht Stellen)\n";
$rfc = [59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471',
        1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130'];
$treffer = 0; $werkzeug = 0;
foreach ($rfc as $t => $soll) {
    $treffer  += totp_code('12345678901234567890', $t, 8) === $soll ? 1 : 0;
    $werkzeug += substr(pruef_totp_code('12345678901234567890', intdiv($t, 30)), 0, 6)
               === substr(totp_code('12345678901234567890', $t, 6), 0, 6) ? 1 : 0;
}
pruefe($treffer === 6, "totp_code(): $treffer von 6 Vektoren");
pruefe($werkzeug === 6, "Rechner der Werkzeuge = totp_code(): $werkzeug von 6");

/* ---- 2. Keine Wiederholung, Normierung --------------------------------------- */
echo "== 2. Keine Wiederholung, Normierung der Codes\n";
$g = random_bytes(20);
$jetzt = time();
$c = totp_code($g, $jetzt);
$s = totp_code_passt($g, $c, $jetzt, null);
pruefe($s !== null, 'ein frischer Code passt');
pruefe(totp_code_passt($g, $c, $jetzt, $s) === null, 'derselbe Code mit seinem Schritt als letztem: abgewiesen');
pruefe(totp_code_passt($g, substr($c, 0, 3) . ' ' . substr($c, 3), $jetzt, null) !== null, '„123 456" mit Leerzeichen passt');
pruefe(totp_code_normieren(' k7qf-2mxd ') === 'K7QF2MXD', 'Wiederherstellungscode: klein, Bindestrich, Rand → normiert');
pruefe(totp_code_normieren('K7QF2MX0') === null, 'eine Null ist kein Zeichen der Codes');

/* ---- 2b. Ein Datenbankfehler schaltet den Zweitfaktor nicht stumm ----------
 *
 * F-P5c-166: `totp_spalten_da()` fing jeden Fehler und sagte dann „keine
 * Spalten" — und `login.php` meldete darauf ohne Code-Schritt an. Stumm sein
 * darf der Zweitfaktor nur, wenn die Spalte wirklich fehlt (SQLSTATE 42S22
 * bzw. `db_hat_spalte()` = nein); jeder andere Fehler bricht ab. Gemessen mit
 * einer Verbindung, deren `prepare()` wirft — eine echte Datenbank lässt sich
 * so gezielt nicht stören. */
echo "== 2b. Datenbankfehler: abbrechen statt stumm (F-P5c-166)\n";
final class ProbeKaputtePdo extends PDO
{
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        throw new PDOException('SQLSTATE[HY000]: General error: 2006 MySQL server has gone away');
    }
}
$wirft = static function (callable $f): string {
    try { $f(); return 'kein Fehler'; } catch (Throwable $e) { return 'wirft'; }
};
pruefe($wirft(static fn() => totp_spalten_da(new ProbeKaputtePdo())) === 'wirft',
       'totp_spalten_da() mit gestörter Verbindung: bricht ab, sagt nicht „keine Spalten"');
pruefe(totp_spalten_da($pdo) === true, '... und mit der echten Verbindung: Spalten da');

/* ---- HTTP mit einem eigenen Konto ------------------------------------------- */

$mail = 'zweitfaktor-probe@probe.invalid';
$pw = 'Probe-Zweitfaktor-' . bin2hex(random_bytes(6));
$salz = bin2hex(random_bytes(16));
$iter = KDF_ITER_ZIEL;
$token = bin2hex(substr(hash_pbkdf2('sha256', $pw, hex2bin($salz), $iter, 64, true), 32, 32));
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Zweitfaktorprobe', 'admin', ?, ?, ?)")
    ->execute([$mail, password_hash($token, PASSWORD_DEFAULT), $salz, $iter]);
$uid = (int)$pdo->lastInsertId();
$merkmal = rate_merkmal_kennung($mail);

register_shutdown_function(static function () use ($pdo, $uid, $merkmal): void {
    $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id = ?')->execute([$uid]);
    $pdo->prepare("DELETE FROM rate_limits WHERE merkmal = ?")->execute([$merkmal]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    $pdo->exec("DELETE FROM users WHERE email LIKE 'zweitfaktor-bf-%@probe.invalid'");
});

/** Eine Anfrage mit eigener Cookieführung: Das Sitzungscookie trägt `secure`,
 *  und curl schickt es über HTTP nicht zurück — deshalb von Hand.
 *
 *  EIN BEHÄLTER, NICHT EIN COOKIE (seit Web 21.7.0, Schritt 18, SR-01): Zur
 *  Sitzung gehört das Bindungscookie `EDBIND`. Bis dahin hielt `$keks` nur
 *  das zuletzt gesetzte Cookie, und das reichte, weil es nur eines gab. Jetzt
 *  hält es alle, nach Namen; ein gelöschtes (leer oder `deleted`) fällt heraus. */
function http(string $methode, string $pfad, array $felder = [], ?string $json = null, string $csrf = ''): array
{
    global $basis, $keks;
    $ch = curl_init($basis . '/' . ltrim($pfad, '/'));
    $paare = [];
    foreach ($keks as $n => $v) { $paare[] = $n . '=' . $v; }
    $kopf = $paare !== [] ? ['Cookie: ' . implode('; ', $paare)] : [];
    /* EIN JSON-RUMPF MIT `X-CSRF` (SR-09), wie `EdApi.postJson()` ihn schickt. */
    if ($json !== null) { $kopf[] = 'Content-Type: application/json'; $kopf[] = 'X-CSRF: ' . $csrf; }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => $kopf,
        CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '']);
    if ($methode === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json ?? http_build_query($felder));
    }
    $roh = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $kl = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $k = substr($roh, 0, $kl);
    if (preg_match_all('/^Set-Cookie:\s*([^=;\s]+)=([^;\r\n]*)/mi', $k, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) {
            if ($c[2] !== '' && $c[2] !== 'deleted') { $keks[$c[1]] = $c[2]; } else { unset($keks[$c[1]]); }
        }
    }
    $ort = preg_match('/^Location:\s*(\S+)/mi', $k, $l) ? $l[1] : '';
    return ['code' => $code, 'ort' => $ort, 'rumpf' => substr($roh, $kl)];
}

function csrf_von(string $html): string
{
    return preg_match('/name="csrf" value="([0-9a-f]+)"/', $html, $m) ? $m[1] : '';
}

/** Das Passwort absenden, wie der Browser es tut (ein Token je Rundenzahl). */
function passwort(): array
{
    global $keks, $mail, $token, $iter;
    $keks = [];
    $s = http('GET', 'login.php');
    return http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'email' => $mail,
                                      'tokens' => json_encode([(string)$iter => $token])]);
}

function code_senden(string $code, bool $rc = false): array
{
    $s = http('GET', 'login.php' . ($rc ? '?art=rc' : ''));
    $f = ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code'];
    if ($rc) { $f['art'] = 'rc'; $f['rc'] = $code; } else { $f['code'] = $code; }
    return http('POST', 'login.php', $f);
}

$keks = [];

/* ---- 4. Das Einrichtungstor (vor 3: das Konto hat noch keinen Zweitfaktor) -- */
echo "== 4. Das Einrichtungstor\n";
$a = passwort();
pruefe($a['code'] === 302 && str_ends_with($a['ort'], 'index.php'),
       'ohne Zweitfaktor: Passwort → 302 auf index.php', $a['code'] . ' ' . $a['ort']);
$b = http('GET', 'index.php');
pruefe($b['code'] === 302 && str_ends_with($b['ort'], 'zweitfaktor.php'),
       'Pflichtrolle ohne Zweitfaktor: index.php → 302 auf zweitfaktor.php', $b['code'] . ' ' . $b['ort']);
$b = http('GET', 'api/range.php');
pruefe($b['code'] === 403 && str_contains($b['rumpf'], '"zweitfaktor"'),
       'API → 403 JSON „zweitfaktor"', $b['code'] . ' ' . substr($b['rumpf'], 0, 80));
$z = http('GET', 'zweitfaktor.php');
$hatQr = preg_match('/data-qr="(otpauth:\/\/totp\/[^"]+)"/', $z['rumpf'], $q) === 1;
$geheimSeite = preg_match('/class="codeblock-wert">([A-Z2-7 ]+)</', $z['rumpf'], $gs) ? str_replace(' ', '', $gs[1]) : '';
$rohDb = totp_geheimnis($uid);
pruefe($z['code'] === 200 && $hatQr, 'zweitfaktor.php → 200 mit otpauth-Adresse im QR', (string)$z['code']);
pruefe($rohDb !== null && $geheimSeite === totp_base32($rohDb)
       && str_contains(html_entity_decode($q[1] ?? ''), 'secret=' . $geheimSeite),
       'Geheimnis auf der Seite = versiegeltes in der Datenbank = das in der Adresse');
pruefe(!totp_an($uid), 'angefangen heißt noch nicht eingeschaltet (E-P5c-54)');
$z2 = http('GET', 'zweitfaktor.php');
$geheim2 = preg_match('/class="codeblock-wert">([A-Z2-7 ]+)</', $z2['rumpf'], $gs2) ? str_replace(' ', '', $gs2[1]) : '';
pruefe($geheim2 !== '' && $geheim2 === $geheimSeite, 'Neuladen behält das Geheimnis');
$e = http('POST', 'zweitfaktor.php', ['csrf' => csrf_von($z2['rumpf']),
          'code' => totp_code((string)$rohDb, time())]);
$codes = preg_match_all('/<li>([A-Z2-9]{4} [A-Z2-9]{4})<\/li>/', $e['rumpf'], $cm) ? $cm[1] : [];
pruefe($e['code'] === 200 && count($codes) === TOTP_CODES, 'Einschalten mit Code → zehn Codes einmal sichtbar',
       $e['code'] . ', ' . count($codes) . ' Codes');
pruefe(totp_an($uid), 'danach eingeschaltet');
$b = http('GET', 'index.php');
pruefe($b['code'] === 200, 'danach lässt das Tor durch', (string)$b['code']);
$roh = (string)totp_geheimnis($uid);

/* ---- 3. Der Code-Schritt ----------------------------------------------------- */
echo "== 3. Der Code-Schritt\n";
/* Der Einschalt-Code hat den jetzigen Schritt verbraucht — für die Anmeldung
 * gilt erst der nächste. Der Zähler der Probe läuft deshalb eigenständig. */
$schritt = (int)$pdo->query('SELECT totp_schritt FROM users WHERE id = ' . $uid)->fetchColumn();
$naechster = static function () use (&$schritt, $roh): string {
    $schritt++;
    while ($schritt > intdiv(time(), 30) + 1) { sleep(1); }
    return pruef_totp_code($roh, $schritt);
};
$a = passwort();
pruefe($a['code'] === 303 && str_ends_with($a['ort'], 'login.php'),
       'mit Zweitfaktor: Passwort → 303 auf login.php', $a['code'] . ' ' . $a['ort']);
$b = http('GET', 'api/range.php');
pruefe($b['code'] === 401 && str_contains($b['rumpf'], 'nicht_angemeldet'),
       'halbe Anmeldung: API → 401 JSON (E-P5c-53)', $b['code'] . ' ' . substr($b['rumpf'], 0, 80));
$b = http('GET', 'index.php');
pruefe($b['code'] === 302 && str_ends_with($b['ort'], 'login.php'), 'halbe Anmeldung: index.php → login.php');
$f = code_senden('000000');
pruefe($f['code'] === 200 && str_contains($f['rumpf'], 'Der Code passt nicht') && str_contains($f['rumpf'], 'id="codeform"'),
       'falscher Code → Meldung, Code-Schritt bleibt');
$gueltig = $naechster();
$r = code_senden($gueltig);
pruefe($r['code'] === 302 && str_ends_with($r['ort'], 'index.php'), 'richtiger Code → 302 auf index.php',
       $r['code'] . ' ' . $r['ort']);
pruefe(http('GET', 'index.php')['code'] === 200, 'danach angemeldet');
$a = passwort();
$w = code_senden($gueltig);
pruefe($w['code'] === 200 && str_contains($w['rumpf'], 'id="codeform"'), 'derselbe Code noch einmal → abgewiesen');
$rc = code_senden(strtolower(str_replace(' ', '-', $codes[0] ?? 'XXXX XXXX')), true);
pruefe($rc['code'] === 302 && str_ends_with($rc['ort'], 'index.php'),
       'Wiederherstellungscode (klein, mit Bindestrich) → angemeldet', $rc['code'] . ' ' . $rc['ort']);
$benutzt = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'totp_code_benutzt'
                              AND betroffen_user_id = $uid AND urheber_user_id = $uid")->fetchColumn();
pruefe($benutzt === 1, 'Protokoll „totp_code_benutzt" mit dem Konto als Urheber', (string)$benutzt);
$a = passwort();
$rc2 = code_senden($codes[0] ?? '', true);
pruefe($rc2['code'] === 200 && str_contains($rc2['rumpf'], 'gilt einmal'), 'derselbe Wiederherstellungscode → abgewiesen');
$ab = http('GET', 'login.php?abbrechen=1');
pruefe(str_contains($ab['rumpf'], 'id="loginform"') && str_contains($ab['rumpf'], 'data-vergessen="1"'),
       '„Zurück zur Anmeldung" → Passwortformular, Vormerkfach wird geräumt (data-vergessen="1")');
$ab2 = http('GET', 'login.php');
pruefe(str_contains($ab2['rumpf'], 'data-vergessen="0"'),
       'danach ohne halben Stand: nichts zu räumen (data-vergessen="0")');
$pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
$a = passwort();
for ($i = 0; $i < 5; $i++) { $f = code_senden('111111'); }
pruefe(str_contains($f['rumpf'], 'Zu viele falsche Codes') && str_contains($f['rumpf'], 'id="loginform"'),
       'fünf falsche Codes → Sperre, zurück aufs Passwortformular');
$a = passwort();
pruefe($a['code'] === 200 && str_contains($a['rumpf'], 'Zu viele falsche Codes'),
       'gesperrt: Passwort richtig, aber kein halber Stand');
$pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);

/* ---- 5. Selbstlöschung erst mit dem Code zurücknehmen ------------------------ */
echo "== 5. Rückzug der Selbstlöschung erst mit dem Code\n";
$pdo->prepare("UPDATE users SET status = 'gesperrt', gesperrt_grund = 'selbstloeschung',
                                gesperrt_seit = UTC_TIMESTAMP() WHERE id = ?")->execute([$uid]);
$a = passwort();
$st = (string)$pdo->query('SELECT status FROM users WHERE id = ' . $uid)->fetchColumn();
pruefe($a['code'] === 303 && $st === 'gesperrt', 'nach dem Passwort: halber Stand, Konto noch gesperrt', "$st, HTTP {$a['code']}");
$r = code_senden($naechster());
$st = (string)$pdo->query('SELECT status FROM users WHERE id = ' . $uid)->fetchColumn();
pruefe($r['code'] === 302 && $st === 'aktiv', 'nach dem Code: angemeldet, Löschung zurückgenommen', "$st, HTTP {$r['code']}");

/* ---- 5b. „Gerät merken" (Schritt 18, SR-02) ---------------------------------
 *
 * DAS KONTO IST ADMIN, also Verwaltung: Vorgabe 7 Tage. Die zwei Einstellungen
 * stehen vorher fest auf ihren Vorgaben und nachher wieder auf dem Stand von
 * vorher. Die Geräte-Tabelle räumt die Kaskade mit dem Konto ab.
 *
 * DAS COOKIE `EDGERAET` BLEIBT ÜBER ANMELDUNGEN HINWEG LIEGEN, und genau das
 * ist sein Zweck. `passwort()` beginnt mit einem leeren Behälter (ein neuer
 * Browser); `$mitGeraet()` behält das Gerätecookie (derselbe Browser nach
 * dem Abmelden). */
echo "== 5b. Gerät merken (SR-02)\n";
if (!zweitfaktor_geraete_da($pdo)) {
    pruefe(false, 'Gerät merken', 'die Tabelle vertraute_geraete fehlt — update.php, nicht gemessen');
} else {
    $einst = [ZF_GERAET_K_USER => app_state_lesen(ZF_GERAET_K_USER),
              ZF_GERAET_K_VERWALTUNG => app_state_lesen(ZF_GERAET_K_VERWALTUNG)];
    register_shutdown_function(static function () use ($einst): void {
        foreach ($einst as $k => $v) {
            if ($v === null) { app_state_loeschen($k); } else { app_state_setzen($k, $v); }
        }
    });
    app_state_setzen(ZF_GERAET_K_USER, '30');
    app_state_setzen(ZF_GERAET_K_VERWALTUNG, '7');
    $zeilen = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM vertraute_geraete WHERE user_id = $uid")->fetchColumn();
    $protokollArt = static fn(string $art): int => (int)$pdo->query(
        "SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = '$art' AND betroffen_user_id = $uid")->fetchColumn();
    $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
    $mitGeraet = static function () use (&$keks): array {
        /* Derselbe Browser: nur das Gerätecookie überlebt das Abmelden. */
        $g = $keks['EDGERAET'] ?? null;
        $keks = $g !== null ? ['EDGERAET' => $g] : [];
        $s = http('GET', 'login.php');
        global $mail, $token, $iter;
        return http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'email' => $mail,
                                          'tokens' => json_encode([(string)$iter => $token])]);
    };
    $codeMerken = static function (string $code): array {
        $s = http('GET', 'login.php');
        return http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code',
                                          'code' => $code, 'merken' => '1']);
    };

    // 1. Der Haken, mit der Dauer der Rollengruppe
    passwort();
    $s = http('GET', 'login.php');
    pruefe(str_contains($s['rumpf'], 'name="merken"') && str_contains($s['rumpf'], 'Dieses Gerät 7 Tage merken'),
           'Code-Schritt (Admin): Haken „Dieses Gerät 7 Tage merken"');
    $s = http('GET', 'login.php?art=rc');
    pruefe(!str_contains($s['rumpf'], 'name="merken"'), 'im Formular des Wiederherstellungscodes kein Haken (E-SR-18)');
    $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?")->execute([$uid]);
    $s = http('GET', 'login.php');
    pruefe(str_contains($s['rumpf'], 'Dieses Gerät 30 Tage merken'), 'dasselbe Konto als NutzerIn: „30 Tage" — die andere Gruppe');
    $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);

    // 2. Merken nach App-Code, dann ohne Code
    $vorGemerkt = $protokollArt('zweitfaktor_geraet_gemerkt');
    $r = $codeMerken($naechster());
    $gerat = $keks['EDGERAET'] ?? '';
    pruefe($r['code'] === 302 && $gerat !== '' && $zeilen() === 1,
           'App-Code mit Haken: angemeldet, Cookie EDGERAET, eine Zeile', "HTTP {$r['code']}, Zeilen " . $zeilen());
    $roh = (string)$pdo->query("SELECT token_hash FROM vertraute_geraete WHERE user_id = $uid")->fetchColumn();
    pruefe($roh === hash('sha256', $gerat), 'in der Tabelle steht der SHA-256, nicht der Wert');
    pruefe($protokollArt('zweitfaktor_geraet_gemerkt') === $vorGemerkt + 1, 'Protokoll „zweitfaktor_geraet_gemerkt"');
    http('GET', 'logout.php');
    $a = $mitGeraet();
    pruefe($a['code'] === 302 && str_ends_with($a['ort'], 'index.php'),
           'derselbe Browser nach dem Abmelden: Passwort → 302 auf index.php, kein Code', $a['code'] . ' ' . $a['ort']);

    // 3. Ein fremdes Cookie an einem anderen Konto zählt nicht
    $mail2 = 'zweitfaktor-bf-geraet@probe.invalid';
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail2]);
    $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                   VALUES (?, 'Zweitfaktorprobe B', 'admin', ?, ?, ?)")
        ->execute([$mail2, password_hash($token, PASSWORD_DEFAULT), $salz, $iter]);
    $uid2 = (int)$pdo->lastInsertId();
    $pdo->prepare('UPDATE users SET totp_geheimnis = ?, totp_seit = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([sk_versiegeln(random_bytes(20), 'totp|' . $uid2), $uid2]);
    $keks = ['EDGERAET' => $gerat];
    $s = http('GET', 'login.php');
    $b2 = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'email' => $mail2,
                                     'tokens' => json_encode([(string)$iter => $token])]);
    pruefe($b2['code'] === 303, 'das Cookie von Konto A an Konto B: Code-Schritt (303)', (string)$b2['code']);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid2]);

    // 4. Ein Wiederherstellungscode merkt nicht
    $keks = [];
    passwort();
    $s = http('GET', 'login.php?art=rc');
    $rc3 = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code', 'art' => 'rc',
                                      'rc' => $codes[1] ?? '', 'merken' => '1']);
    pruefe($rc3['code'] === 302 && $zeilen() === 1 && !isset($keks['EDGERAET']),
           'Wiederherstellungscode mit handgebautem merken=1: angemeldet, nichts gemerkt',
           "HTTP {$rc3['code']}, Zeilen " . $zeilen());

    // 5. Die Dauer wird beim Prüfen gerechnet
    $keks = ['EDGERAET' => $gerat];
    $pdo->prepare('UPDATE vertraute_geraete SET angelegt_am = UTC_TIMESTAMP() - INTERVAL 10 DAY WHERE user_id = ?')
        ->execute([$uid]);
    app_state_setzen(ZF_GERAET_K_VERWALTUNG, '30');
    $a = $mitGeraet();
    pruefe($a['code'] === 302, 'Dauer 30, Gerät 10 Tage alt: ohne Code', (string)$a['code']);
    http('GET', 'logout.php');
    app_state_setzen(ZF_GERAET_K_VERWALTUNG, '7');
    $a = $mitGeraet();
    pruefe($a['code'] === 303, 'auf 7 gesenkt, dasselbe Gerät: Code-Schritt — ohne dass jemand vergisst',
           (string)$a['code']);
    pruefe(zweitfaktor_geraete_zahl($uid) === 0 && $zeilen() === 1,
           '… die Zahl im Profil ist 0, die Zeile liegt noch (Aufräumen ist Hygiene)');
    $weg = zweitfaktor_geraete_aufraeumen($pdo);
    pruefe($weg >= 1 && $zeilen() === 0, 'der Aufräumschritt löscht die abgelaufene Zeile', "$weg gelöscht");

    // 6. Dauer 0: kein Haken, gemerkte Geräte gelten nicht
    $keks = [];
    passwort();
    $r = $codeMerken($naechster());
    $gerat = $keks['EDGERAET'] ?? '';
    app_state_setzen(ZF_GERAET_K_VERWALTUNG, '0');
    $a = $mitGeraet();
    $s = http('GET', 'login.php');
    pruefe($a['code'] === 303 && !str_contains($s['rumpf'], 'name="merken"'),
           'Dauer 0: Code-Schritt trotz Cookie, und kein Haken', (string)$a['code']);
    http('GET', 'login.php?abbrechen=1');
    app_state_setzen(ZF_GERAET_K_VERWALTUNG, '7');
    app_state_setzen(ZF_GERAET_K_USER, '365');
    pruefe(zweitfaktor_geraet_tage('user') === 30, 'ein Wert außerhalb der Wahl (365) gilt als Vorgabe 30');
    app_state_setzen(ZF_GERAET_K_USER, '30');

    // 7. „Alle vergessen" im Profil
    $a = $mitGeraet();
    pruefe($a['code'] === 302, 'wieder 7 Tage: ohne Code', (string)$a['code']);
    $vorVergessen = $protokollArt('zweitfaktor_geraete_vergessen');
    $e = http('GET', 'einstellungen.php?t=profil');
    pruefe(str_contains($e['rumpf'], 'Gemerkte Geräte') && str_contains($e['rumpf'], 'form="f-zf-geraete"'),
           'Profil: Zeile „Gemerkte Geräte" mit „Alle vergessen"');
    $v = http('POST', 'einstellungen.php?t=profil', ['csrf' => csrf_von($e['rumpf']),
                                                      'action' => 'zf_geraete_vergessen']);
    pruefe($v['code'] === 302 && $zeilen() === 0 && !isset($keks['EDGERAET'])
           && $protokollArt('zweitfaktor_geraete_vergessen') === $vorVergessen + 1,
           '„Alle vergessen": 302, Zeilen 0, Cookie gelöscht, Protokoll', "HTTP {$v['code']}, Zeilen " . $zeilen());
    $keks = ['EDGERAET' => $gerat] + $keks;
    http('GET', 'logout.php');
    $keks = ['EDGERAET' => $gerat];
    $a = $mitGeraet();
    pruefe($a['code'] === 303, 'danach mit dem alten Cookie: Code-Schritt', (string)$a['code']);
    http('GET', 'login.php?abbrechen=1');

    // 8. Der Passwortwechsel vergisst
    $keks = [];
    passwort();
    $r = $codeMerken($naechster());
    $e = http('GET', 'einstellungen.php?t=profil');
    $neuToken = bin2hex(random_bytes(32));
    $p = http('POST', 'einstellungen.php?t=profil', ['csrf' => csrf_von($e['rumpf']), 'action' => 'password',
            'old_token' => $token, 'new_token' => $neuToken, 'new_salt' => bin2hex(random_bytes(16)),
            'new_iter' => (string)KDF_ITER_ZIEL]);
    $token = $neuToken;
    pruefe($zeilen() === 0 && str_contains(html_entity_decode($p['rumpf']), 'Gemerkte Geräte sind vergessen'),
           'Passwortwechsel: Zeilen 0, die Meldung sagt es', 'HTTP ' . $p['code'] . ', Zeilen ' . $zeilen());

    /* 8b. Das neue Passwort über den Link vergisst ebenso (`pw_handling.php`).
     * Bis SR-02 fuhr keine Probe diesen Weg bis zum Speichern. Das Konto hat
     * keine Hüllen, also ist es die Erstvergabe: Der Server prüft Form und
     * Anteil-Kennung der Hüllen, öffnen kann er sie nicht — gebaut werden sie
     * hier aus Zufall in der richtigen Form. Danach stehen Passwort, Salz und
     * Hüllen wieder wie vorher, damit Teil 9 auf demselben Konto weiterläuft. */
    $keks = [];
    passwort();
    $codeMerken($naechster());
    $vorZ = $zeilen();
    $vorReset = $protokollArt('zweitfaktor_geraete_vergessen');
    $sichernPw = $pdo->query("SELECT password_hash, kdf_salt, kdf_iter, pat_wrap_pw, pat_wrap_rc, pat_key_check
                              FROM users WHERE id = $uid")->fetch(PDO::FETCH_ASSOC);
    $linkTok = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                   VALUES (?, ?, NOW() + INTERVAL 1 HOUR)')->execute([$uid, hash('sha256', $linkTok)]);
    $keks = [];
    http('GET', 'pw_handling.php?token=' . $linkTok);
    http('GET', 'pw_handling.php?w=1');
    $kennung = anteil_ausgeliefert();
    $r = http('POST', 'pw_handling.php', [
        'new_token' => bin2hex(random_bytes(32)), 'new_salt' => bin2hex(random_bytes(16)),
        'new_iter'  => (string)KDF_ITER_ZIEL,
        'wrap_pw'   => ($kennung !== null ? 'edka1:' . $kennung . ':' : 'edk1:') . base64_encode(random_bytes(48)),
        'wrap_rc'   => 'edk1:' . base64_encode(random_bytes(48)), 'key_check' => '']);
    $gesetzt = (string)$pdo->query("SELECT password_hash FROM users WHERE id = $uid")->fetchColumn()
               !== $sichernPw['password_hash'];
    pruefe($vorZ === 1 && $gesetzt && $zeilen() === 0
           && $protokollArt('zweitfaktor_geraete_vergessen') === $vorReset + 1,
           'Passwort über den Link (pw_handling.php): gesetzt, Zeilen 0, Protokoll',
           "vorher $vorZ, HTTP {$r['code']}, gesetzt " . ($gesetzt ? 'ja' : 'nein') . ', Zeilen ' . $zeilen());
    $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?, kdf_iter = ?, pat_wrap_pw = ?,
                                    pat_wrap_rc = ?, pat_key_check = ? WHERE id = ?')
        ->execute([$sichernPw['password_hash'], $sichernPw['kdf_salt'], $sichernPw['kdf_iter'],
                   $sichernPw['pat_wrap_pw'], $sichernPw['pat_wrap_rc'], $sichernPw['pat_key_check'], $uid]);
    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$uid]);
    $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);

    // 9. Abschalten vergisst, auf jedem Weg
    $keks = [];
    passwort();
    $codeMerken($naechster());
    $vorZ = $zeilen();
    $roh = (string)totp_geheimnis($uid);
    $sichern = $pdo->query("SELECT totp_geheimnis, totp_seit, totp_schritt FROM users WHERE id = $uid")->fetch(PDO::FETCH_ASSOC);
    $codesSichern = $pdo->query("SELECT hash, benutzt_am FROM totp_codes WHERE user_id = $uid")->fetchAll(PDO::FETCH_ASSOC);
    totp_abschalten($uid, 'verwaltung');
    pruefe($vorZ === 1 && $zeilen() === 0, 'totp_abschalten(…, verwaltung): die Geräte gehen mit', "$vorZ → " . $zeilen());
    /* Zurückstellen, damit die Teile danach mit eingeschaltetem Zweitfaktor
     * weiterlaufen — Geheimnis und Codes wie vorher. */
    $pdo->prepare('UPDATE users SET totp_geheimnis = ?, totp_seit = ?, totp_schritt = ? WHERE id = ?')
        ->execute([$sichern['totp_geheimnis'], $sichern['totp_seit'], $sichern['totp_schritt'], $uid]);
    foreach ($codesSichern as $c) {
        $pdo->prepare('INSERT INTO totp_codes (user_id, hash, benutzt_am) VALUES (?, ?, ?)')
            ->execute([$uid, $c['hash'], $c['benutzt_am']]);
    }
    $keks = [];
}

/* ---- 5c. Der frische Code (Schritt 18, SR-07, E-SR-20) ----------------------
 *
 * DAS KONTO WIRD FUER DIESEN TEIL BETREIBERIN: Das Schluesselblatt ist die
 * eine Handlung der Liste, die ein GET ist und bei Erfolg 200 antwortet —
 * die Probe kann „durch" an der Seite selbst sehen. Danach wieder Admin.
 *
 * DIE FRIST WIRD IN DER SITZUNGSDATEI GESTELLT, nicht abgewartet: 15 Minuten
 * sind zu lang fuer eine Probe. Gestellt wird die eine Zahl hinter
 * `zf_frisch_bis|i:`, sonst nichts. */
echo "== 5c. Frischer Code (SR-07)\n";
if (!zweitfaktor_geraete_da($pdo)) {
    pruefe(false, 'Frischer Code', 'die Tabelle vertraute_geraete fehlt — update.php, nicht gemessen');
} else {
    $pdo->prepare("UPDATE users SET role = 'betreiberin' WHERE id = ?")->execute([$uid]);
    $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
    $frischStellen = static function (int $bis) use (&$keks): bool {
        $datei = sitzung_ablage_pfad() . '/sess_' . ($keks['PHPSESSID'] ?? '');
        $inhalt = @file_get_contents($datei);
        if ($inhalt === false) { return false; }
        $neu = preg_replace('/zf_frisch_bis\|i:\d+;/', 'zf_frisch_bis|i:' . $bis . ';', $inhalt, 1, $n);
        return $n === 1 && file_put_contents($datei, $neu) !== false;
    };
    $umweg = static function (array $r): array {
        parse_str((string)parse_url($r['ort'], PHP_URL_QUERY), $q);
        return $q;
    };
    try {
        // 1. Mit Code angemeldet: sofort frisch
        $keks = [];
        passwort();
        $s = http('GET', 'login.php');
        $r = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code',
                                        'code' => $naechster(), 'merken' => '1']);
        $b = http('GET', 'betrieb_schluesselblatt.php');
        pruefe($r['code'] === 302 && $b['code'] === 200,
               'mit Code angemeldet: Schlüsselblatt sofort (200)', "Anmeldung {$r['code']}, Blatt {$b['code']}");

        // 2. Über das gemerkte Gerät: kein Code, also nicht frisch
        $g = $keks['EDGERAET'] ?? null;
        http('GET', 'logout.php');
        $keks = $g !== null ? ['EDGERAET' => $g] : [];
        $s = http('GET', 'login.php');
        $a = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'email' => $mail,
                                        'tokens' => json_encode([(string)$iter => $token])]);
        $b = http('GET', 'betrieb_schluesselblatt.php');
        $q = $umweg($b);
        pruefe($a['code'] === 302 && $b['code'] === 303 && str_starts_with($b['ort'], 'zweitfaktor.php?bestaetigen=1&')
               && ($q['zurueck'] ?? '') === 'betrieb_schluesselblatt.php' && !isset($q['nochmal'])
               && ($q['abbruch'] ?? '') === 'betrieb_server.php#k-schluessel',
               'über das gemerkte Gerät: Schlüsselblatt → 303 auf die Bestätigung, ohne „nochmal", Abbrechen in die Karte',
               "Anmeldung {$a['code']}, Blatt {$b['code']} → {$b['ort']}");

        // 3. Ein Griff als POST: Umweg mit „nochmal", zurück in die Karte
        $p = http('POST', 'betrieb_server.php', ['csrf' => 'absichtlich-falsch', 'action' => 'schluessel_anteil_wechseln']);
        $q = $umweg($p);
        pruefe($p['code'] === 303 && ($q['zurueck'] ?? '') === 'betrieb_server.php#k-schluessel'
               && ($q['nochmal'] ?? '') === '1',
               'Schlüsselgriff (POST) ohne frischen Code: 303, zurück auf #k-schluessel, „nochmal"',
               "{$p['code']} → {$p['ort']}");

        // 4. Die Bestätigung: falscher Code, dann der richtige
        $seite = http('GET', $p['ort']);
        $falsch = http('POST', $p['ort'], ['csrf' => csrf_von($seite['rumpf']), 'code' => '000000']);
        pruefe($seite['code'] === 200 && str_contains($seite['rumpf'], 'Code bestätigen')
               && $falsch['code'] === 200 && str_contains(html_entity_decode($falsch['rumpf']), 'Der Code passt nicht'),
               'Bestätigungsseite: 200 mit Codefeld; ein falscher Code bleibt dort',
               "Seite {$seite['code']}, falsch {$falsch['code']}");
        $richtig = http('POST', $p['ort'], ['csrf' => csrf_von($falsch['rumpf']), 'code' => $naechster()]);
        $karte = http('GET', 'betrieb_server.php');
        $b = http('GET', 'betrieb_schluesselblatt.php');
        pruefe($richtig['code'] === 303 && $richtig['ort'] === 'betrieb_server.php#k-schluessel'
               && str_contains(html_entity_decode($karte['rumpf']), 'Code bestätigt — bitte die Handlung noch einmal auslösen.')
               && $b['code'] === 200,
               'richtiger Code: zurück in die Karte, Meldung „noch einmal auslösen", Blatt jetzt 200',
               "Code {$richtig['code']} → {$richtig['ort']}, Blatt {$b['code']}");

        // 5. Die Frist läuft ab
        $gestellt = $frischStellen(time() - 1);
        $b = http('GET', 'betrieb_schluesselblatt.php');
        pruefe($gestellt && $b['code'] === 303, 'Frist gestellt auf abgelaufen: wieder 303',
               ($gestellt ? 'gestellt' : 'NICHT gestellt') . ", Blatt {$b['code']}");

        // 6. Nur Rücksprünge auf eine Seite der Liste
        $frischStellen(time() + 600);
        $faelle = ['https://boese.invalid/'                  => 'index.php',
                   '//boese.invalid/betrieb_server.php'      => 'index.php',
                   'admin_users.php'                         => 'index.php',
                   'betrieb_server.php?x=<script>#k-schluessel' => 'betrieb_server.php#k-schluessel',
                   'admin_user.php?id=' . $uid               => 'admin_user.php?id=' . $uid];
        $ist = [];
        foreach ($faelle as $roh => $soll) {
            $z = http('GET', 'zweitfaktor.php?bestaetigen=1&zurueck=' . rawurlencode($roh));
            $ist[] = $z['code'] === 303 && $z['ort'] === $soll;
        }
        pruefe(!in_array(false, $ist, true),
               'zurück nur auf eine Seite der Liste: fremde Adresse, // und fremde Seite → index.php, Abfrage mit Sonderzeichen fällt weg',
               implode(' ', array_map(static fn(bool $b): string => $b ? 'ok' : 'NEIN', $ist)));
    } finally {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);
        $pdo->prepare('DELETE FROM vertraute_geraete WHERE user_id = ?')->execute([$uid]);
        $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
        $keks = [];
    }
}

/* ---- 5d. Passkeys an der Anlage (Schritt 18, SR-09) -----------------------
 *
 * DIE ADRESSE WIRD GESTELLT: `app.base_url` auf `https://localhost:8443`, weil
 * es fuer eine IP-Adresse keine Passkeys gibt (E-SR-42). Die Anfragen gehen
 * weiter an `$basis` — geprueft wird der Ursprung in `clientDataJSON` gegen
 * die Konfiguration, nicht gegen den Host der Anfrage. Nach dem Stellen und
 * nach dem Zuruecklegen wartet die Probe 3,2 Sekunden: OPcache sieht eine
 * geaenderte `config.php` erst nach `revalidate_freq` (F-P5c-69), und er
 * rechnet mit der Anfragezeit in GANZEN Sekunden — nach einer Pruefung in
 * Sekunde L gilt die alte Fassung bis einschliesslich L+2. Die erste Fassung
 * wartete 2,2 s und war in einem von zwei Laeufen rot (F-SR-31; dieselbe
 * Falle wie F-P5c-123 in der Ratenprobe).
 *
 * DER PASSKEY WIRD UNMITTELBAR ABGELEGT, nicht ueber den Endpunkt: Diese
 * Probe misst die Anbindung an Anmeldung und Bestaetigung. Den Endpunkt
 * misst sie danach eigens, die Pruefung selbst die Passkeyprobe.
 *
 * DAS KONTO WIRD BETREIBERIN wie in 5c — das Schluesselblatt zeigt die
 * Frische an der Seite selbst. */
echo "== 5d. Passkeys an der Anlage (SR-09)\n";
if (!pk_tabelle_da($pdo) || !zweitfaktor_geraete_da($pdo) || !db_hat_spalte($pdo, 'passkeys', 'rp_id')) {
    pruefe(false, 'Passkeys an der Anlage', 'die Tabelle passkeys fehlt oder ist alt — update.php, nicht gemessen');
} else {
    $pkU = ['ursprung' => 'https://localhost:8443', 'rp_id' => 'localhost'];
    $app = (array)konfig('app');
    $app['base_url'] = $pkU['ursprung'];
    $pkZurueck = konfig_stellen(['app' => $app]);
    usleep(3200000);
    $pdo->prepare("UPDATE users SET role = 'betreiberin' WHERE id = ?")->execute([$uid]);
    $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
    try {
        $mailVorher = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn();
    } catch (Throwable) { $mailVorher = null; }
    $paar = pb_paar(-7);
    $kennung = random_bytes(32);
    $pdo->prepare("INSERT INTO passkeys (user_id, credential_id, credential_hash, rp_id, oeffentlich, alg, zaehler,
                                         bezeichnung, angelegt_am)
                   VALUES (?, ?, ?, 'localhost', ?, -7, 0, 'Zweitfaktorprobe', UTC_TIMESTAMP())")
        ->execute([$uid, pk_b64u($kennung), hash('sha256', $kennung), $paar['privat']->getPublicKey()->toString('PKCS8')]);
    $pkId = (int)$pdo->lastInsertId();
    $zaehler = 0;
    /** Den Bereich des Knopfs aus einer Seite lesen: Herausforderung, rp.id, Kennungen. */
    $knopf = static function (string $html, string $griff): ?array {
        if (!preg_match('/<div ' . $griff . '\b[^>]*>/', $html, $m)) { return null; }
        $a = [];
        foreach (['herausforderung', 'rp-id', 'kennungen'] as $n) {
            $a[$n] = preg_match('/data-pk-' . $n . '="([^"]*)"/', $m[0], $w) ? html_entity_decode($w[1]) : '';
        }
        return $a;
    };
    $antwort = static function (?array $k, array $o = []) use ($paar, $kennung, $pkU, &$zaehler): string {
        $zaehler++;
        return (string)json_encode(pb_anmeldung($paar, $kennung, (string)($k['herausforderung'] ?? ''), $pkU,
                                                $o + ['flags' => 0x05, 'zaehler' => $zaehler]));
    };
    try {
        // 1. Der Knopf im Code-Schritt
        $keks = [];
        passwort();
        $s = http('GET', 'login.php');
        $k = $knopf($s['rumpf'], 'data-passkey-bestaetigen');
        pruefe($k !== null && $k['rp-id'] === 'localhost' && strlen($k['herausforderung']) >= 43
               && in_array(pk_b64u($kennung), (array)json_decode($k['kennungen'], true), true)
               && str_contains($s['rumpf'], 'id="passkeyform"') && str_contains($s['rumpf'], 'assets/passkey.js'),
               'Code-Schritt: Knopf „Mit Passkey bestätigen" mit Herausforderung, rp.id localhost und der Kennung',
               $k === null ? 'kein Knopf' : 'rp.id ' . $k['rp-id']);

        // 2. Eine falsche Signatur gilt nicht und ZAEHLT als Fehlversuch
        //    (E-SR-50 — die Haelfte, die bis zur Nachpruefung C-3 niemand mass)
        $versuche = static fn(): int => (int)$pdo->query("SELECT COALESCE(SUM(versuche), 0) FROM rate_limits
                                                           WHERE topf = 'totp' AND merkmal = " . $pdo->quote($merkmal))->fetchColumn();
        $systemZeilen = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse
                                                               WHERE reiter = 'system'")->fetchColumn();
        $vorV = $versuche();
        $f = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code',
                                        'passkey_antwort' => $antwort($k, ['falsch' => true])]);
        pruefe($f['code'] === 200 && str_contains(html_entity_decode($f['rumpf']), 'Der Passkey wurde nicht angenommen')
               && http('GET', 'api/range.php')['code'] === 401 && $versuche() === $vorV + 1,
               'falsche Signatur: bleibt im Code-Schritt, Meldung, nicht angemeldet, ein Fehlversuch',
               "HTTP {$f['code']}, Versuche $vorV → " . $versuche());

        // 2b. Das Feld als Liste (Nachpruefung B-1) und ueber dem Deckel
        //     (B-4): abgewiesen und gezaehlt, ohne PHP-Warnung im Reiter System
        $vorS = $systemZeilen();
        $vorV = $versuche();
        $f = http('POST', 'login.php', ['csrf' => csrf_von($f['rumpf']), 'schritt' => 'code',
                                        'passkey_antwort' => ['x']]);
        $f2 = http('POST', 'login.php', ['csrf' => csrf_von($f['rumpf']), 'schritt' => 'code',
                                         'passkey_antwort' => str_repeat('A', PK_ANTWORT_MAX + 1)]);
        pruefe($f['code'] === 200 && $f2['code'] === 200
               && str_contains(html_entity_decode($f['rumpf']), 'Der Passkey wurde nicht angenommen')
               && str_contains(html_entity_decode($f2['rumpf']), 'Der Passkey wurde nicht angenommen')
               && $versuche() === $vorV + 2 && $systemZeilen() === $vorS,
               'Feld als Liste und über dem Deckel: abgewiesen, je ein Fehlversuch, keine Zeile im Reiter System',
               "HTTP {$f['code']}/{$f2['code']}, Versuche $vorV → " . $versuche() . ", System $vorS → " . $systemZeilen());
        $f = $f2;

        // 3. Mit Passkey angemeldet, „Gerät merken" gesetzt
        $k = $knopf($f['rumpf'], 'data-passkey-bestaetigen');
        $gut1 = $antwort($k);
        $r = http('POST', 'login.php', ['csrf' => csrf_von($f['rumpf']), 'schritt' => 'code',
                                        'passkey_antwort' => $gut1, 'merken' => '1']);
        $zeile = $pdo->query('SELECT zaehler, zuletzt_am FROM passkeys WHERE id = ' . $pkId)->fetch(PDO::FETCH_ASSOC);
        $b = http('GET', 'betrieb_schluesselblatt.php');
        pruefe($r['code'] === 302 && str_ends_with($r['ort'], 'index.php') && isset($keks['EDGERAET'])
               && (int)$zeile['zaehler'] === $zaehler && $zeile['zuletzt_am'] !== null && $b['code'] === 200,
               'mit Passkey angemeldet: 302, Gerät gemerkt, Zähler und „zuletzt" geschrieben, Schlüsselblatt sofort (frisch)',
               "Anmeldung {$r['code']}, Gerät " . (isset($keks['EDGERAET']) ? 'ja' : 'nein')
               . ", Zähler {$zeile['zaehler']}, Blatt {$b['code']}");

        // 4. Die Herausforderung gilt einmal — GETRENNT vom Zaehler gemessen
        //    (H-SR-08, F-SR-43): eine frisch signierte Antwort mit hoeherem
        //    Zaehler, aber der Herausforderung von Schritt 3. Sie scheitert
        //    nur an der Herausforderung, und das zaehlt nicht als Fehlversuch
        //    (F-SR-37).
        $g = $keks['EDGERAET'] ?? null;
        $keks = [];
        passwort();
        $vorV = $versuche();
        $s = http('GET', 'login.php');
        $w = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code',
                                        'passkey_antwort' => $antwort($k)]);
        pruefe($w['code'] === 200 && http('GET', 'api/range.php')['code'] === 401
               && str_contains(html_entity_decode($w['rumpf']), 'Die Anfrage ist abgelaufen') && $versuche() === $vorV,
               'alte Herausforderung, frische Signatur: abgewiesen, kein Fehlversuch', "HTTP {$w['code']}, Versuche $vorV → " . $versuche());

        // 4b. Zaehler zurueck (eine Kopie): abgewiesen, eigener Text, kein
        //     Fehlversuch, eine Mail an die Kontoadresse (E-SR-46)
        $k = $knopf($w['rumpf'], 'data-passkey-bestaetigen');
        $w = http('POST', 'login.php', ['csrf' => csrf_von($w['rumpf']), 'schritt' => 'code',
                                        'passkey_antwort' => $antwort($k, ['zaehler' => 1])]);
        $zMail = $mailVorher === null ? -1 : (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                  WHERE id > $mailVorher AND schluessel = 'passkey_zaehler'")->fetchColumn();
        pruefe($w['code'] === 200 && str_contains(html_entity_decode($w['rumpf']), 'zuletzt auf einem anderen Gerät benutzt')
               && $versuche() === $vorV && $zMail === 1,
               'Zaehler zurueck: abgewiesen mit eigenem Text, kein Fehlversuch, eine Mail',
               "HTTP {$w['code']}, Versuche " . $versuche() . ", Mail $zMail");

        // 5. Über das gemerkte Gerät, dann die Bestätigungsseite mit Passkey
        $keks = $g !== null ? ['EDGERAET' => $g] : [];
        $s = http('GET', 'login.php');
        $a = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'email' => $mail,
                                        'tokens' => json_encode([(string)$iter => $token])]);
        $api = http('POST', 'api/passkey_anlegen.php', [], '{}', 'egal');
        pruefe($a['code'] === 302 && $api['code'] === 403
               && (json_decode($api['rumpf'], true)['error'] ?? '') === 'zweitfaktor_frisch',
               'über das gemerkte Gerät, ohne frischen Code: Endpunkt 403 JSON zweitfaktor_frisch',
               "Anmeldung {$a['code']}, Endpunkt {$api['code']} " . substr($api['rumpf'], 0, 60));
        $b = http('GET', 'betrieb_schluesselblatt.php');
        $seite = http('GET', $b['ort']);
        /* 5a. AUCH DIE BESTAETIGUNGSSEITE nimmt das Feld als Liste und ueber
         *     dem Deckel ohne PHP-Warnung (dritte Lesung 3B-1): bis dahin nur
         *     am Code-Schritt gemessen. Jede Einsendung verbraucht die
         *     Herausforderung; die naechste Seite stellt eine neue. */
        $vorS = $systemZeilen();
        $vorV = $versuche();
        $bl = http('POST', $b['ort'], ['csrf' => csrf_von($seite['rumpf']), 'passkey_antwort' => ['x']]);
        $seite = http('GET', $b['ort']);
        $bg = http('POST', $b['ort'], ['csrf' => csrf_von($seite['rumpf']),
                                       'passkey_antwort' => str_repeat('A', PK_ANTWORT_MAX + 1)]);
        pruefe($bl['code'] === 200 && $bg['code'] === 200
               && str_contains(html_entity_decode($bl['rumpf']), 'nicht angenommen')
               && str_contains(html_entity_decode($bg['rumpf']), 'nicht angenommen')
               && $versuche() === $vorV + 2 && $systemZeilen() === $vorS,
               'Bestätigungsseite: Feld als Liste und über dem Deckel abgewiesen, je ein Fehlversuch, keine Zeile im Reiter System',
               "HTTP {$bl['code']}/{$bg['code']}, Versuche $vorV → " . $versuche() . ", System $vorS → " . $systemZeilen());
        $seite = http('GET', $b['ort']);
        $k = $knopf($seite['rumpf'], 'data-passkey-bestaetigen');
        $best = http('POST', $b['ort'], ['csrf' => csrf_von($seite['rumpf']), 'passkey_antwort' => $antwort($k)]);
        $b2 = http('GET', 'betrieb_schluesselblatt.php');
        pruefe($b['code'] === 303 && $k !== null && $best['code'] === 303
               && $best['ort'] === 'betrieb_schluesselblatt.php' && $b2['code'] === 200,
               'Bestätigungsseite: Knopf da, Passkey bestätigt, zurück, Schlüsselblatt jetzt 200',
               "Blatt {$b['code']}, Knopf " . ($k !== null ? 'ja' : 'nein') . ", Bestätigung {$best['code']} → {$best['ort']}, Blatt {$b2['code']}");

        // 6. Anlegen über den Endpunkt, mit frischem Code
        $e = http('GET', 'einstellungen.php?t=profil');
        $kr = $knopf($e['rumpf'], 'data-passkey-anlegen');
        $neu = pb_paar(-257);
        $reg = pb_registrierung($neu, (string)($kr['herausforderung'] ?? ''), $pkU);
        // 6a. Rumpf ueber dem Deckel, Bezeichnung als Liste, 41 Zeichen: je
        //     abgewiesen, OHNE die Herausforderung zu verbrauchen — das gueltige
        //     Anlegen danach nimmt dieselbe (F-SR-39, Nachpruefung E-4, B-4)
        $gross = http('POST', 'api/passkey_anlegen.php', [], str_repeat(' ', PK_ANTWORT_MAX + 1), csrf_von($e['rumpf']));
        $liste = http('POST', 'api/passkey_anlegen.php', [],
                      (string)json_encode(['antwort' => $reg['antwort'], 'bezeichnung' => ['x']]), csrf_von($e['rumpf']));
        $zuLang = http('POST', 'api/passkey_anlegen.php', [],
                       (string)json_encode(['antwort' => $reg['antwort'], 'bezeichnung' => str_repeat('x', 41)]), csrf_von($e['rumpf']));
        pruefe($gross['code'] === 413 && (json_decode($gross['rumpf'], true)['error'] ?? '') === 'zu_gross'
               && $liste['code'] === 400 && (json_decode($liste['rumpf'], true)['error'] ?? '') === 'bezeichnung'
               && $zuLang['code'] === 400 && (json_decode($zuLang['rumpf'], true)['error'] ?? '') === 'bezeichnung',
               'Endpunkt: Rumpf über dem Deckel 413, Bezeichnung als Liste und mit 41 Zeichen 400 (Herausforderung bleibt)',
               "HTTP {$gross['code']}/{$liste['code']}/{$zuLang['code']}");
        $an = http('POST', 'api/passkey_anlegen.php', [],
                   (string)json_encode(['antwort' => $reg['antwort'], 'bezeichnung' => 'Probe RSA']), csrf_von($e['rumpf']));
        $zahl = (int)$pdo->query('SELECT COUNT(*) FROM passkeys WHERE user_id = ' . $uid)->fetchColumn();
        $prot = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE betroffen_user_id = $uid
                                   AND art = 'passkey_angelegt'")->fetchColumn();
        $post = $mailVorher === null ? -1 : (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                  WHERE id > $mailVorher AND schluessel = 'passkey_angelegt'")->fetchColumn();
        pruefe($kr !== null && $an['code'] === 200 && (json_decode($an['rumpf'], true)['ok'] ?? false) === true
               && $zahl === 2 && $prot === 1 && $post === 1,
               'Karte: Herausforderung am Abschnitt; Endpunkt legt an (RS256), Protokoll und Mail',
               "Abschnitt " . ($kr !== null ? 'ja' : 'nein') . ", HTTP {$an['code']}, Zahl $zahl, Protokoll $prot, Mail $post");
        $noch = http('POST', 'api/passkey_anlegen.php', [],
                     (string)json_encode(['antwort' => $reg['antwort'], 'bezeichnung' => '']), csrf_von($e['rumpf']));
        $abgew = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE betroffen_user_id = $uid
                                    AND art = 'passkey_abgewiesen'")->fetchColumn();
        pruefe($noch['code'] === 400 && (json_decode($noch['rumpf'], true)['error'] ?? '') === 'abgelaufen' && $abgew === 0,
               'dieselbe Registrierung noch einmal: 400 „abgelaufen" (Herausforderung verbraucht), keine Zeile „abgewiesen"',
               "HTTP {$noch['code']} " . substr($noch['rumpf'], 0, 60) . ", abgewiesen $abgew");
        // 6b. Eine Kennung, die es schon gibt, mit NEUER Herausforderung:
        //     nach aussen dieselbe Meldung wie jede andere Ablehnung, der
        //     Grund steht im Protokoll (E-SR-45, F-SR-39)
        $e = http('GET', 'einstellungen.php?t=profil');
        $kr2 = $knopf($e['rumpf'], 'data-passkey-anlegen');
        $reg2 = pb_registrierung($neu, (string)($kr2['herausforderung'] ?? ''), $pkU, ['kennung' => $reg['kennung']]);
        $vorh = http('POST', 'api/passkey_anlegen.php', [],
                     (string)json_encode(['antwort' => $reg2['antwort'], 'bezeichnung' => '']), csrf_von($e['rumpf']));
        $grund = (string)$pdo->query("SELECT text FROM protokoll_ereignisse WHERE betroffen_user_id = $uid
                                       AND art = 'passkey_abgewiesen' ORDER BY id DESC LIMIT 1")->fetchColumn();
        pruefe($vorh['code'] === 400 && str_contains((string)(json_decode($vorh['rumpf'], true)['meldung'] ?? ''), 'nicht angenommen')
               && str_contains($grund, 'gibt es schon'),
               'Kennung gibt es schon: 400 „nicht angenommen", der Grund im Protokoll',
               "HTTP {$vorh['code']}, Protokoll: $grund");

        // 7. Entfernen über die Karte
        $e = http('GET', 'einstellungen.php?t=profil');
        $x = http('POST', 'einstellungen.php?t=profil', ['csrf' => csrf_von($e['rumpf']),
                                                          'action' => 'passkey_entfernen', 'id' => (string)$pkId]);
        $da = (int)$pdo->query('SELECT COUNT(*) FROM passkeys WHERE id = ' . $pkId)->fetchColumn();
        $prot = $pdo->query("SELECT daten FROM protokoll_ereignisse WHERE betroffen_user_id = $uid
                              AND art = 'passkey_entfernt' ORDER BY id DESC LIMIT 1")->fetchColumn();
        $postE = $mailVorher === null ? -1 : (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                   WHERE id > $mailVorher AND schluessel = 'passkey_entfernt'")->fetchColumn();
        pruefe(in_array($x['code'], [302, 303], true) && $da === 0 && is_string($prot)
               && (json_decode($prot, true)['weg'] ?? '') === 'selbst' && $postE === 1,
               'Entfernen über die Karte: Zeile fort, Protokoll mit weg „selbst", Mail',
               "HTTP {$x['code']}, Zeile $da, Mail $postE, Protokoll " . (is_string($prot) ? $prot : '—'));
    } finally {
        $pdo->prepare('DELETE FROM passkeys WHERE user_id = ?')->execute([$uid]);
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$uid]);
        $pdo->prepare('DELETE FROM vertraute_geraete WHERE user_id = ?')->execute([$uid]);
        $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
        if ($mailVorher !== null) {
            $pdo->exec("DELETE FROM mail_warteschlange WHERE id > $mailVorher
                           AND schluessel IN ('passkey_angelegt', 'passkey_entfernt', 'passkey_zaehler')");
        }
        $pkZurueck();
        usleep(3200000);
        $keks = [];
    }
}

/* ---- 6. Der Demo-Reset leert die Spalten ------------------------------------- */
echo "== 6. Demo-Reset\n";
/* NICHT `demo_zuruecksetzen()` SELBST (F-P5c-117). Der Reset spielt den
 * Demo-Bestand neu ein, die Einsätze bekommen neue Nummern — und die
 * GPX-Probe, die im Prüfstand danach läuft, fand ihre Referenz nicht mehr
 * (204 von 204 ohne Gegenstück). Gemessen wird der Schritt, der den
 * Zweitfaktor leert, und dass der Reset ihn ruft: am Quelltext der Funktion,
 * OHNE Kommentare — ein Kommentar, der den Namen nennt, ist kein Aufruf. */
$demo = demo_id();
if ($demo === null) {
    pruefe(false, 'Demo-Reset', 'kein Demo-Konto vermerkt — nicht gemessen');
} else {
    $pdo->prepare('UPDATE users SET totp_seit = UTC_TIMESTAMP(), totp_schritt = 1 WHERE id = ?')->execute([$demo]);
    demo_zweitfaktor_leeren($pdo, $demo);
    $z = $pdo->query("SELECT totp_seit IS NULL AND totp_schritt IS NULL FROM users WHERE id = $demo")->fetchColumn();
    pruefe((int)$z === 1, 'demo_zweitfaktor_leeren(): totp_seit und totp_schritt leer');
    /* DAS PAAR DES RÜCKWEGS (Konzept RW, RW-02, E-RW-15): an derselben
     * Stelle geleert. Gestellt mit Unsinn in allen drei Spalten — geprüft
     * wird das Leeren, nicht die Form. Ohne die Spalten (vor `update.php`)
     * ist der Fall nicht gemessen und rot, statt still übersprungen. */
    if (db_hat_spalte($pdo, 'users', 'rw_seit')) {
        $pdo->prepare("UPDATE users SET rw_oeffentlich = 'probe', rw_privat = 'edk1:probe',
                              rw_seit = UTC_TIMESTAMP() WHERE id = ?")->execute([$demo]);
        demo_zweitfaktor_leeren($pdo, $demo);
        $z = $pdo->query("SELECT (rw_oeffentlich IS NULL) + (rw_privat IS NULL) + (rw_seit IS NULL)
                            FROM users WHERE id = $demo")->fetchColumn();
        pruefe((int)$z === 3, 'demo_zweitfaktor_leeren(): das Paar des Rückwegs leer', "$z von 3 Spalten NULL");
    } else {
        pruefe(false, 'demo_zweitfaktor_leeren(): das Paar des Rückwegs', 'Spalten rw_* fehlen — nicht gemessen');
    }
}
$rf = new ReflectionFunction('demo_zuruecksetzen');
$rumpf = implode('', array_slice(file((string)$rf->getFileName()), $rf->getStartLine() - 1,
                                 $rf->getEndLine() - $rf->getStartLine() + 1));
$aufrufe = 0;
$marken = token_get_all('<?php ' . $rumpf);
foreach ($marken as $i => $t) {
    if (is_array($t) && $t[0] === T_STRING && $t[1] === 'demo_zweitfaktor_leeren') {
        $n = $marken[$i + 1] ?? null;
        if ($n === '(') { $aufrufe++; }
    }
}
pruefe($aufrufe === 1, 'demo_zuruecksetzen() ruft demo_zweitfaktor_leeren() genau einmal', "$aufrufe Aufrufe im Code");

/* ---- 6b. Der Demo-Reset nur nach einer Aenderung -------------------------------- */
echo "== 6b. Demo-Reset nur nach einer Änderung\n";
/* DIE RECHNUNG ALS TABELLE, mit festen Zeitpunkten statt der Uhr. Jeder Fall
 * nennt, was er belegt; die Grenzen stehen je einmal knapp davor und genau
 * darauf. */
$T = 1_700_000_000;
$faelle = [
    [$T, 0, $T + 1800, false, 'ohne Änderung: 30 min nach dem Reset kein neuer'],
    [$T, 0, $T + 86399, false, 'ohne Änderung: nach 23:59 h noch keiner'],
    [$T, 0, $T + 86400, true, 'ohne Änderung: nach 24 h der Pflichtreset'],
    [$T, $T + 7200, $T + 8999, false, 'Änderung nach 2 h Ruhe: 29:59 min danach noch keiner'],
    [$T, $T + 7200, $T + 9000, true, 'Änderung nach 2 h Ruhe: 30 min danach fällig'],
    [$T, $T + 60, $T + 1860, true, 'Änderung gleich nach dem Reset: 30 min nach ihr'],
    [$T, $T + 86000, $T + 86400, false, 'späte Änderung: der Pflichtreset kürzt ihre Frist nicht'],
    [$T + 3600, $T, $T + 5399, false, 'Reset aufgehalten (Marke später als die Änderung)'],
    [$T + 600, $T - 3600, $T, false, 'Marke des Resets in der Zukunft: keiner'],
    [0, 0, $T, true, 'nie zurückgesetzt: sofort fällig'],
];
foreach ($faelle as [$letzter, $geaendert, $jetzt, $soll, $was]) {
    $ist = demo_reset_faellig_ab($letzter, $geaendert) <= $jetzt;
    pruefe($ist === $soll, 'faellig_ab: ' . $was, $ist ? 'fällig' : 'nicht fällig');
}

/* NUR DIE ERSTE AENDERUNG ZAEHLT — an `app_state`, mit einer Marke von
 * JETZT: Eine alte machte den Reset faellig, und die naechste Anfrage des
 * Demo-Kontos (Bilderlauf, Bedienprobe) setzte zurueck. Zurueckgestellt im
 * selben Zug. */
$gVor = demo_geaendert_seit();
$jetztProbe = time() - 5;
app_state_setzen(DEMO_K_GEAENDERT, (string)$jetztProbe);
demo_aenderung_vermerken();
pruefe(demo_geaendert_seit() === $jetztProbe, 'eine zweite Änderung verschiebt die Marke nicht',
       (string)demo_geaendert_seit());
demo_aenderung_vergessen($jetztProbe - 1);
pruefe(demo_geaendert_seit() === $jetztProbe, 'eine Änderung während des Resets bleibt markiert');
demo_aenderung_vergessen($jetztProbe);
pruefe(demo_geaendert_seit() === 0, 'nach dem Reset ist die Marke fort');
demo_aenderung_vermerken();
pruefe(abs(demo_geaendert_seit() - time()) <= 2, 'ohne Marke setzt die erste Änderung sie auf jetzt');
if ($gVor > 0) { app_state_setzen(DEMO_K_GEAENDERT, (string)$gVor); }
else { app_state_loeschen(DEMO_K_GEAENDERT); }

/* DIE ZWEI SETZSTELLEN, am Quelltext ohne Kommentare — wie der Aufruf in
 * Teil 6. Je eine, und jede hinter ihrer Pruefung: in `auth_guard.php` in
 * derselben Zeile wie POST und `csrf_ok()`, in `ingest.php` direkt nach
 * einem `commit()`. */
$codeZeilen = static function (string $datei): array {
    $zeilen = [];
    $zeile = 1;
    foreach (token_get_all((string)file_get_contents($datei)) as $t) {
        $text = is_array($t) ? $t[1] : $t;
        if (!is_array($t) || !in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) {
            foreach (explode("\n", $text) as $i => $stueck) {
                if ($i > 0) { $zeile++; }
                $zeilen[$zeile] = ($zeilen[$zeile] ?? '') . $stueck;
            }
        } else {
            $zeile += substr_count($text, "\n");
        }
    }
    return array_values(array_filter(array_map('trim', $zeilen), static fn($z) => $z !== ''));
};
$ag = $codeZeilen($srv . '/auth_guard.php');
$treffer = array_values(array_filter($ag, static fn($z) => str_contains($z, 'demo_aenderung_vermerken(')));
pruefe(count($treffer) === 1 && str_contains($treffer[0], "'POST'") && str_contains($treffer[0], 'csrf_ok()'),
       'auth_guard.php: eine Setzstelle, nur bei POST mit Token', count($treffer) . ' Stelle(n)');
$ig = $codeZeilen($srv . '/ingest.php');
$stellen = array_keys(array_filter($ig, static fn($z) => str_contains($z, 'demo_aenderung_vermerken(')));
$davor = count($stellen) === 1 ? ($ig[$stellen[0] - 1] ?? '') : '';
pruefe(count($stellen) === 1 && str_contains($davor, '->commit()'),
       'ingest.php: eine Setzstelle, nach dem Commit', count($stellen) . ' Stelle(n), davor: ' . $davor);

/* ---- 7. Bus-Faktor ------------------------------------------------------------ */
echo "== 7. Bus-Faktor\n";
/* GEZÄHLT WIRD, NICHT DER SATZ GELESEN: Welcher der vier Sätze erscheint,
 * hängt vom Bestand der Anlage ab. Die Zählung darunter nicht. */
$vorher = status_verwaltungskonten($pdo);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'BF', 'betreiberin', '', '', 310000)")
    ->execute(['zweitfaktor-bf-' . bin2hex(random_bytes(3)) . '@probe.invalid']);
$ohne = status_verwaltungskonten($pdo);
pruefe($ohne['betreiberinnen'] === $vorher['betreiberinnen']
       && $ohne['betreiberinnen_aktiv'] === $vorher['betreiberinnen_aktiv'] + 1,
       'eine aktive BetreiberIn ohne Zweitfaktor zählt nicht als handlungsfähig',
       json_encode($vorher) . ' → ' . json_encode($ohne));
totp_abschalten($uid, 'verwaltung');
$ohneAdmin = status_verwaltungskonten($pdo);
pruefe($ohneAdmin['verwaltung'] === $ohne['verwaltung'] - 1,
       'ein Admin ohne Zweitfaktor fällt aus der Zahl der handlungsfähigen',
       json_encode($ohne) . ' → ' . json_encode($ohneAdmin));
$zeile = null;
foreach (status_erhebung()['karten'][0]['zeilen'] as $z) {
    if ($z['text'] === 'Verwaltungskonten') { $zeile = $z; }
}
pruefe($zeile !== null && ($ohneAdmin['betreiberinnen'] >= 2) === ($zeile['ton'] === 'blau'),
       'Ampel: blau genau bei zwei handlungsfähigen BetreiberInnen, sonst orange',
       ($zeile['plakette'] ?? '?') . ' / ' . ($zeile['ton'] ?? '?'));

/* DIE TABELLE DER FÄLLE (Konzept P5c, AP5; Texte M-P5c-02d): Rollenmix →
 * Plakette und Ton, mit gesetzten Zahlen statt mit dem Bestand. Die Ampel
 * hängt allein an „zwei BetreiberInnen handlungsfähig" (F-P5c-60). */
$faelle = [
    // [Verwaltung, BetreiberInnen handlungsfähig, BetreiberInnen aktiv] => [Plakette, Ton, Fall]
    [[1, 1, 1], ['nur 1', 'orange', '1 BetreiberIn allein']],
    [[2, 1, 1], ['1 BetreiberIn', 'orange', '1 BetreiberIn und 1 Admin']],
    [[1, 1, 2], ['1 BetreiberIn', 'orange', '2 BetreiberInnen, eine ohne Zweitfaktor']],
    [[3, 1, 2], ['1 BetreiberIn', 'orange', 'dasselbe mit einem Admin daneben']],
    [[2, 2, 2], ['vertreten', 'blau', '2 handlungsfähige BetreiberInnen']],
    [[1, 0, 1], ['keine BetreiberIn', 'orange', 'keine BetreiberIn handlungsfähig']],
];
foreach ($faelle as [[$v, $b, $a], [$plakette, $ton, $fall]]) {
    $z = status_verwaltungszeile(['verwaltung' => $v, 'betreiberinnen' => $b,
                                  'betreiberinnen_aktiv' => $a]);
    pruefe($z['plakette'] === $plakette && $z['ton'] === $ton,
           "Fall „{$fall}\" → {$plakette} / {$ton}", $z['plakette'] . ' / ' . $z['ton']);
}

/* ---- 8. Der Notzugang der einzigen BetreiberIn (SR-04) -----------------------
 *
 * WAS ER ZUSAGT (E-SR-13, E-SR-24, E-SR-81 bis -87): Datei, deren Namen die
 * Seite nennt und die an der Sitzung hängt, Datenbankwert und Passwort — und
 * nur für das eine aktive BetreiberIn-Konto mit Zweitfaktor. Jede andere Lage
 * antwortet GLEICH (derselbe Rumpf, bis auf Token, Nonce und Dateinamen) und
 * GLEICH LANG; nichts ändert sich. Ein Aufruf schreibt keine Datei.
 *
 * DIE ÜBRIGEN BETREIBERINNEN WERDEN FÜR DIESEN TEIL ADMIN — örtlich Konto 1.
 * Anders lässt sich „genau eine" nicht herstellen. Zurückgestellt wird im
 * `finally` UND im Schluss-Handler (auch nach einem Abbruch der Probe); nur
 * ein hartes Beenden (`kill -9`) liesse sie als Admin stehen — dann steht
 * ihre Kennung oben in der Ausgabe. Die Konten aus Teil 7 gehen vorher.
 *
 * DER WERT IN `app_state` STEHT AM ENDE WIEDER WIE VORHER — der Erfolgsfall
 * würfelt ihn neu, und die örtliche Anlage soll den Wert behalten, den sie
 * vor der Probe hatte. */
echo "== 8. Notzugang der einzigen BetreiberIn (SR-04)\n";
$pdo->exec("DELETE FROM users WHERE email LIKE 'zweitfaktor-bf-%@probe.invalid'");
$nwMail = 'zweitfaktor-notweg@probe.invalid';
$nwZwei = 'zweitfaktor-notweg-zwei@probe.invalid';
$nwNiemand = 'zweitfaktor-notweg-niemand@probe.invalid';
$nwPw = 'Probe-Notweg-' . bin2hex(random_bytes(6));
$nwSalz = bin2hex(random_bytes(16));
$nwToken = bin2hex(substr(hash_pbkdf2('sha256', $nwPw, hex2bin($nwSalz), $iter, 64, true), 32, 32));
$nwFalsch = bin2hex(substr(hash_pbkdf2('sha256', $nwPw . 'x', hex2bin($nwSalz), $iter, 64, true), 32, 32));
$nwMuster = $srv . '/zweitfaktor-notweg-';
$nwWertVorher = app_state_lesen('notzugang_geheim');
$nwMailVorher = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn();
$nwProtVorher = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM protokoll_ereignisse')->fetchColumn();
$nwAndere = array_map('intval', $pdo->query("SELECT id FROM users WHERE role = 'betreiberin'")
                                     ->fetchAll(PDO::FETCH_COLUMN));
echo "  (vorübergehend Admin: Konto " . implode(', ', $nwAndere) . ")\n";
$nwAufraeumen = static function () use ($pdo, $nwAndere, $nwMail, $nwZwei, $nwNiemand, $nwMuster,
                                        $nwWertVorher, $nwMailVorher, $nwProtVorher): void {
    static $erledigt = false;
    if ($erledigt) { return; }
    $erledigt = true;
    foreach ($nwAndere as $id) {
        $pdo->prepare("UPDATE users SET role = 'betreiberin' WHERE id = ?")->execute([$id]);
    }
    $ids = $pdo->query("SELECT id FROM users WHERE email IN ('$nwMail', '$nwZwei')")
               ->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }
    /* '' dabei: Teil d2 schickt die Adresse als Liste, und die Seite zählt
     * den Versuch dann unter der leeren Adresse. */
    foreach ([$nwMail, $nwZwei, $nwNiemand, ''] as $m) {
        $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([rate_merkmal_kennung($m)]);
        $pdo->prepare("DELETE FROM sicherheit_ereignisse WHERE topf = 'notweg' AND merkmal = ?")
            ->execute([rate_merkmal_kennung($m)]);
    }
    $pdo->prepare("DELETE FROM mail_warteschlange WHERE id > ? AND empfaenger IN (?, ?)")
        ->execute([$nwMailVorher, $nwMail, $nwZwei]);
    foreach (glob($nwMuster . '*.txt') ?: [] as $d) { @unlink($d); }
    if ($nwWertVorher !== null) { app_state_setzen('notzugang_geheim', $nwWertVorher); }
};
register_shutdown_function($nwAufraeumen);

try {
    foreach ([$nwMail, $nwZwei] as $m) { $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$m]); }
    $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                   VALUES (?, 'Notwegprobe', 'betreiberin', ?, ?, ?)")
        ->execute([$nwMail, password_hash($nwToken, PASSWORD_DEFAULT), $nwSalz, $iter]);
    $nwId = (int)$pdo->lastInsertId();
    foreach ($nwAndere as $id) {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$id]);
    }
    $beg = totp_einrichtung_beginnen($nwId);
    $ein = ($beg['ok'] ?? false)
         ? totp_einrichtung_abschliessen($nwId, totp_code((string)$beg['geheimnis'], time())) : ['ok' => false];
    pruefe(($ein['ok'] ?? false) && totp_an($nwId) && betreiberinnen_zahl($pdo) === 1,
           'Ausgang: eine BetreiberIn (die der Probe), Zweitfaktor an', json_encode($beg['grund'] ?? $ein['grund'] ?? ''));

    /* Eine eigene Sitzung je Fall — ein eigener Keksbehälter. */
    $nwSitzung = static function (): array {
        global $keks;
        $keks = [];
        $g = http('GET', 'zweitfaktor_notweg.php');
        $k = preg_match('/zweitfaktor-notweg-([0-9a-f]{32})\.txt/', $g['rumpf'], $m) ? $m[1] : '';
        return ['keks' => $keks, 'kennung' => $k, 'csrf' => csrf_von($g['rumpf']), 'get' => $g];
    };
    $nwSenden = static function (array $s, array $felder): array {
        global $keks;
        $keks = $s['keks'];
        $t = microtime(true);
        $r = http('POST', 'zweitfaktor_notweg.php', $felder + ['csrf' => $s['csrf']]);
        $r['dauer'] = microtime(true) - $t;
        return $r;
    };
    $nwGleich = static fn(string $h): string => (string)preg_replace(
        ['/zweitfaktor-notweg-[0-9a-f]{32}/', '/value="[0-9a-f]{64}"/', '/nonce="[^"]*"/'],
        ['zweitfaktor-notweg-X', 'value="X"', 'nonce="X"'], $h);
    $nwDatei = static fn(string $k): string => $nwMuster . $k . '.txt';
    $nwWert = static fn(): string => (string)app_state_lesen('notzugang_geheim');
    $nwRichtig = static fn(): array => ['email' => $nwMail,
        'tokens' => json_encode([(string)$iter => $nwToken]),
        'wert' => implode(' ', str_split(strtoupper($nwWert()), 4))];   // in Vierergruppen, groß
    $nwZurueck = static function () use ($pdo, $nwMail, $nwNiemand, $nwZwei): void {
        foreach ([$nwMail, $nwNiemand, $nwZwei, ''] as $m) {
            $pdo->prepare("DELETE FROM rate_limits WHERE topf = 'notweg' AND merkmal = ?")
                ->execute([rate_merkmal_kennung($m)]);
        }
    };
    $nwZurueckgesetzt = static fn(): int => (int)$pdo->query(
        "SELECT COUNT(*) FROM protokoll_ereignisse WHERE id > $nwProtVorher
           AND art = 'totp_zurueckgesetzt' AND betroffen_user_id = $nwId")->fetchColumn();

    // a) Die Seite: zwei Sitzungen, zwei Namen; gleicher Rumpf; keine Datei geschrieben.
    $txtVorher = glob($srv . '/*.txt') ?: [];
    $s1 = $nwSitzung();
    $s2 = $nwSitzung();
    pruefe($s1['get']['code'] === 200 && $s1['kennung'] !== '' && $s2['kennung'] !== ''
           && $s1['kennung'] !== $s2['kennung'],
           'GET: 200, jede Sitzung bekommt ihren eigenen Namen (E-SR-82)',
           $s1['get']['code'] . ' ' . $s1['kennung'] . ' / ' . $s2['kennung']);
    pruefe($nwGleich($s1['get']['rumpf']) === $nwGleich($s2['get']['rumpf']),
           'GET: der Rumpf ist bis auf Token, Nonce und Namen gleich');
    $s1b = http('GET', 'zweitfaktor_notweg.php');   // $keks steht noch auf $s2
    pruefe(str_contains($s1b['rumpf'], $s2['kennung']), 'GET in derselben Sitzung: derselbe Name');
    pruefe((glob($srv . '/*.txt') ?: []) === $txtVorher,
           'GET schreibt keine Datei ins Anwendungsverzeichnis (E-SR-81)');
    pruefe(str_contains($s1['get']['rumpf'], "SELECT v FROM app_state WHERE k = 'notzugang_geheim'")
           && !str_contains($s1['get']['rumpf'], $nwWert()),
           'GET nennt die Abfrage, aber nie den Wert');

    // b) Der Wert entsteht beim ersten Aufruf, wenn er fehlt.
    $pdo->exec("DELETE FROM app_state WHERE k = 'notzugang_geheim'");
    $nwSitzung();
    $neuWert = $nwWert();
    pruefe(preg_match('/^[0-9a-f]{64}$/', $neuWert) === 1 && $neuWert !== (string)$nwWertVorher,
           'fehlt der Wert, legt der erste Aufruf ihn an (64 Hexzeichen)', strlen($neuWert) . ' Zeichen');

    // c) Jede Lage, in der die Tür zu bleibt: gleiche Antwort, gleiche Dauer, nichts geändert.
    $faelle = [
        'ohne Datei' => static fn(array $s): array => [],
        'Datei einer anderen Sitzung' => static function (array $s) use ($nwSitzung, $nwDatei): array {
            $fremd = $nwSitzung();
            file_put_contents($nwDatei($fremd['kennung']), '');
            return ['_ohne_eigene' => true];
        },
        'ohne Wert' => static fn(array $s): array => ['wert' => ''],
        'falscher Wert' => static fn(array $s): array => ['wert' => bin2hex(random_bytes(32))],
        'zwei BetreiberInnen' => static function (array $s) use ($pdo, $nwZwei): array {
            $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                           VALUES (?, 'Notwegprobe 2', 'betreiberin', '', '', 310000)")->execute([$nwZwei]);
            return [];
        },
        'falsches Passwort' => static fn(array $s): array => ['tokens' => json_encode([(string)$GLOBALS['iter'] => $GLOBALS['nwFalsch']])],
        /* DANEBEN GENAU EINE ANDERE BETREIBERIN: Ohne sie gäbe es gar keine,
         * und die Zählung hielte die Tür zu, bevor die Rolle gefragt wird —
         * der Fall mäße dann die Rolle nicht (gefunden mit der Gegenprobe,
         * die die Rollenprüfung entfernt: Sie blieb grün). */
        'Admin-Konto' => static function (array $s) use ($pdo, $nwId, $nwZwei): array {
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$nwId]);
            $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                           VALUES (?, 'Notwegprobe 2', 'betreiberin', '', '', 310000)")->execute([$nwZwei]);
            return [];
        },
        'unbekannte Adresse' => static fn(array $s): array => ['email' => $GLOBALS['nwNiemand']],
        'gesperrtes Konto' => static function (array $s) use ($pdo, $nwId): array {
            $pdo->prepare("UPDATE users SET status = 'gesperrt' WHERE id = ?")->execute([$nwId]);
            return [];
        },
        'Zweitfaktor aus' => static function (array $s) use ($pdo, $nwId): array {
            $pdo->prepare('UPDATE users SET totp_seit = NULL WHERE id = ?')->execute([$nwId]);
            return [];
        },
    ];
    $rumpfe = []; $dauern = [];
    foreach ($faelle as $name => $lage) {
        $nwZurueck();
        $s = $nwSitzung();
        $mehr = $lage($s);
        $eigene = empty($mehr['_ohne_eigene']) && $name !== 'ohne Datei';
        unset($mehr['_ohne_eigene']);
        if ($eigene) { file_put_contents($nwDatei($s['kennung']), ''); }
        $wertDavor = $nwWert();
        $r = $nwSenden($s, $mehr + $nwRichtig());
        $rumpfe[$name] = $nwGleich($r['rumpf']);
        $dauern[$name] = $r['dauer'];
        /* zurück in die offene Lage */
        $pdo->prepare("UPDATE users SET role = 'betreiberin', status = 'aktiv',
                                        totp_seit = COALESCE(totp_seit, UTC_TIMESTAMP()) WHERE id = ?")
            ->execute([$nwId]);
        $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$nwZwei]);
        $unveraendert = totp_an($nwId) && $nwZurueckgesetzt() === 0 && $nwWert() === $wertDavor
                     && (!$eigene || is_file($nwDatei($s['kennung'])));
        pruefe($r['code'] === 200 && str_contains($r['rumpf'], 'Der Notzugang steht für dieses Konto nicht bereit.')
               && $unveraendert,
               "{$name}: die eine Antwort, nichts geändert", $r['code'] . sprintf(', %.2f s', $r['dauer']));
        foreach (glob($nwMuster . '*.txt') ?: [] as $d) { @unlink($d); }
    }
    // dazu: angemeldet als Admin (das Hauptkonto der Probe), ohne Datei
    $nwZurueck();
    passwort();   // Hauptkonto, Zweitfaktor seit Teil 7 aus → angemeldet
    $g = http('GET', 'zweitfaktor_notweg.php');
    $s = ['keks' => $keks, 'csrf' => csrf_von($g['rumpf'])];
    $r = $nwSenden($s, $nwRichtig());
    $rumpfe['angemeldet als Admin'] = $nwGleich($r['rumpf']);
    $dauern['angemeldet als Admin'] = $r['dauer'];
    pruefe(str_contains($r['rumpf'], 'Der Notzugang steht für dieses Konto nicht bereit.') && totp_an($nwId),
           'angemeldet als Admin, ohne Datei: dieselbe Antwort — die Seite kennt keine Rolle');
    $verschiedene = count(array_unique($rumpfe));
    pruefe($verschiedene === 1, count($rumpfe) . ' Lagen, ' . $verschiedene . ' Rumpf (bis auf Token, Nonce, Namen)',
           implode(', ', array_keys($rumpfe)));
    $min = min($dauern); $max = max($dauern);
    pruefe($min >= 0.35 && $max - $min < 0.15,
           sprintf('gleiche Dauer: %.3f bis %.3f s (mindestens 0,35 s, Spanne unter 0,15 s)', $min, $max));

    // d) Formular-Token falsch: eigener Satz, zählt nichts.
    $nwZurueck();
    $s = $nwSitzung();
    $r = $nwSenden($s, ['csrf' => 'falsch'] + $nwRichtig());
    $gezaehlt = (int)$pdo->query("SELECT COALESCE(SUM(versuche), 0) FROM rate_limits WHERE topf = 'notweg'
                                    AND merkmal = " . $pdo->quote(rate_merkmal_kennung($nwMail)))->fetchColumn();
    pruefe(str_contains($r['rumpf'], 'Das Formular ist abgelaufen') && $gezaehlt === 0 && totp_an($nwId),
           'falsches Formular-Token: „abgelaufen", kein Versuch gezählt', (string)$gezaehlt);

    // d2) Felder als Liste statt als Zeichenkette: die eine Antwort, keine Warnung, kein Abbruch.
    $nwZurueck();
    $s = $nwSitzung();
    $r = $nwSenden($s, ['email' => [$nwMail], 'wert' => ['x'], 'tokens' => ['y']]);
    $warnungen = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE id > $nwProtVorher
                                     AND art IN ('php_warnung', 'php_hinweis', 'ausnahme')")->fetchColumn();
    pruefe($r['code'] === 200 && str_contains($r['rumpf'], 'Der Notzugang steht für dieses Konto nicht bereit.')
           && $warnungen === 0 && totp_an($nwId),
           'Felder als Liste: die eine Antwort, keine Warnung und kein Abbruch im Reiter System',
           $r['code'] . ', ' . $warnungen . ' Systemzeilen');

    // e) Richtig: Zweitfaktor aus, Protokoll, Mail, Dateien weg, Wert neu.
    $nwZurueck();
    $s = $nwSitzung();
    file_put_contents($nwDatei($s['kennung']), '');
    $rest = $nwSitzung();
    file_put_contents($nwDatei($rest['kennung']), '');   // ein liegengebliebener Versuch
    $wertAlt = $nwWert();
    $r = $nwSenden($s, $nwRichtig());
    pruefe($r['code'] === 303 && str_ends_with($r['ort'], 'login.php?ende=notweg'),
           'richtig: 303 auf login.php?ende=notweg', $r['code'] . ' ' . $r['ort']);
    pruefe(!totp_an($nwId), 'der Zweitfaktor ist aus');
    $prot = $pdo->query("SELECT urheber_art, text, daten FROM protokoll_ereignisse WHERE id > $nwProtVorher
                           AND art = 'totp_zurueckgesetzt' AND betroffen_user_id = $nwId")->fetchAll(PDO::FETCH_ASSOC);
    pruefe(count($prot) === 1 && (json_decode((string)$prot[0]['daten'], true)['weg'] ?? '') === 'notweg'
           && $prot[0]['urheber_art'] === 'job' && str_contains((string)$prot[0]['text'], 'Notzugang'),
           'Protokoll „totp_zurueckgesetzt": weg = notweg, Urheber job (E-SR-86), Text nennt den Notzugang',
           json_encode($prot));
    $mails = $pdo->prepare("SELECT text FROM mail_warteschlange WHERE id > ? AND schluessel = 'totp_zurueckgesetzt'
                              AND empfaenger = ?");
    $mails->execute([$nwMailVorher, $nwMail]);
    $mt = $mails->fetchAll(PDO::FETCH_COLUMN);
    pruefe(count($mt) === 1 && str_contains((string)$mt[0], 'Notzugang zurückgesetzt worden'),
           'Mail „totp_zurueckgesetzt" eingereiht, mit dem Satz des Notzugangs', (string)count($mt));
    pruefe(!is_file($nwDatei($s['kennung'])) && !is_file($nwDatei($rest['kennung'])),
           'beide Dateien des Musters sind weg, auch die liegengebliebene');
    $wertNeu = $nwWert();
    pruefe(preg_match('/^[0-9a-f]{64}$/', $wertNeu) === 1 && $wertNeu !== $wertAlt,
           'in app_state steht ein anderer Wert als davor');
    $l = http('GET', 'login.php?ende=notweg');
    pruefe(str_contains($l['rumpf'], 'über den Notzugang zurückgesetzt'), 'die Anmeldung sagt, was geschah');
    $keks = [];
    $lg = http('GET', 'login.php');
    $a = http('POST', 'login.php', ['csrf' => csrf_von($lg['rumpf']), 'email' => $nwMail,
                                    'tokens' => json_encode([(string)$iter => $nwToken])]);
    $b = http('GET', 'index.php');
    pruefe($a['code'] === 302 && $b['code'] === 302 && str_ends_with($b['ort'], 'zweitfaktor.php'),
           'danach: Anmeldung mit dem Passwort, dann das Einrichtungstor', $a['code'] . ' / ' . $b['code'] . ' ' . $b['ort']);

    // f) Der alte Wert gilt nicht mehr.
    $beg = totp_einrichtung_beginnen($nwId);
    totp_einrichtung_abschliessen($nwId, totp_code((string)($beg['geheimnis'] ?? ''), time()));
    $nwZurueck();
    $s = $nwSitzung();
    file_put_contents($nwDatei($s['kennung']), '');
    $r = $nwSenden($s, ['wert' => $wertAlt] + $nwRichtig());
    pruefe(totp_an($nwId) && str_contains($r['rumpf'], 'Der Notzugang steht für dieses Konto nicht bereit.'),
           'derselbe Weg mit dem ALTEN Wert: abgewiesen, Zweitfaktor bleibt an');

    // g) Sechs Fehlversuche → der Topf sperrt.
    $nwZurueck();
    $letzte = null;
    for ($i = 0; $i < 6; $i++) {
        $s = $nwSitzung();
        $letzte = $nwSenden($s, ['wert' => ''] + $nwRichtig());
    }
    $gesperrt = (int)$pdo->query("SELECT COUNT(*) FROM rate_limits WHERE topf = 'notweg' AND gesperrt_bis > UTC_TIMESTAMP()
                                    AND merkmal = " . $pdo->quote(rate_merkmal_kennung($nwMail)))->fetchColumn();
    pruefe($gesperrt === 1 && str_contains((string)$letzte['rumpf'], 'Zu viele Versuche für diese Adresse'),
           'sechs Fehlversuche: Topf „notweg" sperrt, die Seite sagt bis wann', (string)$gesperrt);
} finally {
    $nwAufraeumen();
    $rollen = $nwAndere === [] ? [] : $pdo->query('SELECT role FROM users WHERE id IN ('
                                                  . implode(',', $nwAndere) . ')')->fetchAll(PDO::FETCH_COLUMN);
    pruefe(array_unique($rollen) === ($nwAndere === [] ? [] : ['betreiberin'])
           && app_state_lesen('notzugang_geheim') === $nwWertVorher
           && (glob($nwMuster . '*.txt') ?: []) === [],
           'aufgeräumt: BetreiberInnen zurück, Wert wie vorher, keine Datei');
}

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
