<?php
declare(strict_types=1);

/**
 * Zweitfaktorprobe — hält der Zweitfaktor, was E-P5c-15, -41, -42, -53, -54
 * zusagen? (P5c/AP5)
 *
 * Anlass: F-P5c-31 (die Code-Abfrage muss VOR der Sitzung stehen, sonst gilt
 * `user_id` schon als angemeldet), F-P5c-37 (Datenmodell, keine
 * Wiederholung), E-P5c-54 (Demo-Reset). Die Abnahme von AP5 verlangt die
 * RFC-Vektoren 6/6.
 *
 * WAS SIE MISST.
 *   1. RFC 6238, Anhang B: sechs Zeitpunkte, SHA-1, acht Stellen — `totp_code()`
 *      rechnet sie nach. Dazu der Rechner der Werkzeuge (`tools/zweitfaktor/`)
 *      gegen dieselben Werte.
 *   2. Kein Code gilt zweimal (`totp_code_passt()` mit dem letzten Schritt), und
 *      ein Wiederherstellungscode wird normiert (Leerzeichen, Bindestrich, klein).
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
 *  und curl schickt es über HTTP nicht zurück — deshalb von Hand. */
function http(string $methode, string $pfad, array $felder = []): array
{
    global $basis, $keks;
    $ch = curl_init($basis . '/' . ltrim($pfad, '/'));
    $kopf = $keks !== '' ? ['Cookie: ' . $keks] : [];
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
        foreach ($m as $c) { if ($c[2] !== '' && $c[2] !== 'deleted') { $keks = $c[1] . '=' . $c[2]; } }
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
    $keks = '';
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

$keks = '';

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
