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
 *   7. Der Bus-Faktor (E-P5c-16, -56): Ein Konto ohne Zweitfaktor zählt nicht
 *      als handlungsfähig; dazu die Tabelle der Lagen (Rollenmix → Plakette
 *      und Ton) über `status_verwaltungszeile()` mit gesetzten Zahlen —
 *      der Bestand der Anlage zeigt immer nur eine davon (F-P5c-114).
 *
 * WAS SIE NICHT MISST: den Ablauf der fünf Minuten des halben Standes (sie
 * wartet nicht; eine Sitzung mit abgelaufener Frist stellt die Wartungsprobe
 * in Erwartung 12a her), den QR-Code als Bild (Bedienweg
 * `zweitfaktor-einrichten`, jsQR), und die Anmeldung der Werkzeuge (jedes
 * Werkzeug selbst).
 *
 * SIE RÄUMT AUF: ihre Konten, deren Protokoll- und Ratenzeilen — im Schluss,
 * auch nach einem Abbruch.
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
function http(string $methode, string $pfad, array $felder = []): array
{
    global $basis, $keks;
    $ch = curl_init($basis . '/' . ltrim($pfad, '/'));
    $paare = [];
    foreach ($keks as $n => $v) { $paare[] = $n . '=' . $v; }
    $kopf = $paare !== [] ? ['Cookie: ' . implode('; ', $paare)] : [];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => $kopf,
        CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '']);
    if ($methode === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($felder));
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

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
