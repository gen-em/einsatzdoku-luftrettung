<?php
declare(strict_types=1);

/**
 * Sitzungsprobe — ist eine gelesene Sitzungsdatei wertlos? (Schritt 18, SR-01)
 *
 * Anlass: Nr. 242 — der Dateiname war die Sitzung; wer `sess_<id>` las, aus dem
 * Verzeichnis oder einem Webspace-Backup, war angemeldet (E-SA-09).
 *
 * WAS SIE MISST, über HTTP gegen die örtliche Anlage, mit einem eigenen Konto
 * der Rolle `user` und einem Zweitfaktor mit bekanntem Geheimnis:
 *   1. Der halbe Stand nach dem Passwort setzt `EDBIND` — `Secure`, `HttpOnly`,
 *      `SameSite=Strict`; ohne das Cookie wird er verworfen (Passwortformular,
 *      Vormerkfach geräumt), und auch mit Cookie ist er danach fort.
 *   2. Der Code schließt die Anmeldung ab und würfelt die Bindung NEU; in der
 *      Sitzungsdatei steht ihr SHA-256, nicht der Wert.
 *   3. Dieselbe Kennung ohne `EDBIND` (und mit einem falschen): Seite →
 *      Abmeldeseite mit `ende=bindung`, API → 401 `grund: bindung`; die Sitzung
 *      ist danach auch für den Browser mit Cookie beendet.
 *   4. Eine Sitzungsdatei ohne Hash — so, wie jede von vor Web 21.7.0 aussieht
 *      und wie eine gelesene aussähe — endet mit `bindung`, auch mit Cookie.
 *   5. Die lesenden Seiten (F-SR-15): Das Notfallblatt zeigt die Kontoadresse
 *      einer gebundenen Sitzung und keine ohne Bindung.
 *   6. Abmelden löscht beide Cookies.
 *   7. Die Anmeldeseite hat einen Text zum Grund `bindung` (F-SR-07).
 *
 * WAS SIE NICHT MISST: wie ein echter Browser zwei `Strict`-Cookies bei einer
 * Weiterleitung behandelt (Bedienprobe, Chromium) — hier führt die Probe die
 * Cookies selbst. Und nicht das Webspace-Backup des Hosters: Der Fall ist
 * nachgestellt (eine Datei ohne Bindung, 4), nicht hergestellt.
 *
 * SIE RÄUMT AUF: Konto, Protokoll- und Ratenzeilen, ihre Sitzungsdateien — im
 * Schluss, auch nach einem Abbruch.
 *
 * Aufruf:  php tools/proben/sitzung/probe.php [basisadresse]   (Vorgabe http://127.0.0.1:8080)
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht, 2 = nicht gelaufen.
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/totp_lib.php';
require_once $srv . '/ratelimit_lib.php';
require_once $srv . '/session_lib.php';
require_once $wurzel . '/tools/zweitfaktor/totp.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo = db();
$gut = 0; $schlecht = 0;
$BIND = SITZUNG_COOKIES['bindung']['name'];

function pruefe(bool $ok, string $was, string $sonst = ''): void
{
    global $gut, $schlecht;
    if ($ok) { $gut++; echo "  ok    $was\n"; return; }
    $schlecht++;
    echo "  FEHLT $was" . ($sonst !== '' ? " — $sonst" : '') . "\n";
}

if (!totp_spalten_da($pdo) || serverschluessel() === null) {
    fwrite(STDERR, "Nicht gelaufen: Spalten des Zweitfaktors oder Serverschlüssel fehlen.\n");
    exit(2);
}

/* ---- Konto ------------------------------------------------------------------ */

$mail = 'sitzungsprobe@probe.invalid';
$pw = 'Probe-Sitzung-' . bin2hex(random_bytes(6));
$salz = bin2hex(random_bytes(16));
$iter = KDF_ITER_ZIEL;
$token = bin2hex(substr(hash_pbkdf2('sha256', $pw, hex2bin($salz), $iter, 64, true), 32, 32));
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Sitzungsprobe', 'user', ?, ?, ?)")
    ->execute([$mail, password_hash($token, PASSWORD_DEFAULT), $salz, $iter]);
$uid = (int)$pdo->lastInsertId();
$merkmal = rate_merkmal_kennung($mail);
/* Ein Zweitfaktor mit bekanntem Geheimnis, über dieselben Funktionen wie
 * `tools/zweitfaktor/pruefkonto.php` — nur für dieses Konto. */
$roh = random_bytes(20);
$pdo->prepare('UPDATE users SET totp_geheimnis = ?, totp_seit = UTC_TIMESTAMP(), totp_schritt = NULL
               WHERE id = ?')->execute([sk_versiegeln($roh, 'totp|' . $uid), $uid]);
$schritt = intdiv(time(), 30) - 1;
$naechster = static function () use (&$schritt, $roh): string {
    $schritt++;
    while ($schritt > intdiv(time(), 30) + 1) { sleep(1); }
    return pruef_totp_code($roh, $schritt);
};

$ort = sitzung_ablage_pfad();
$gesehen = [];   // Sitzungskennungen, deren Dateien im Schluss weg müssen
register_shutdown_function(static function () use ($pdo, $uid, $merkmal, $ort, &$gesehen): void {
    $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id = ? OR urheber_user_id = ?')
        ->execute([$uid, $uid]);
    $pdo->prepare('DELETE FROM rate_limits WHERE merkmal = ?')->execute([$merkmal]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    foreach (array_unique($gesehen) as $sid) { @unlink($ort . '/sess_' . $sid); }
});

/* ---- HTTP mit eigener Cookieführung ------------------------------------------
 *
 * Beide Cookies tragen `Secure`, und curl schickt sie über HTTP nicht zurück —
 * deshalb von Hand, in einem Behälter nach Namen. `$ohne` lässt Cookies für
 * EINE Anfrage weg, `$statt` ersetzt Werte; der Behälter selbst bleibt. */
$keks = [];
$setzt = [];     // die rohen Set-Cookie-Zeilen der letzten Antwort
function http(string $methode, string $pfad, array $felder = [], array $ohne = [], array $statt = []): array
{
    global $basis, $keks, $setzt, $gesehen;
    $senden = array_diff_key(array_replace($keks, $statt), array_flip($ohne));
    $paare = [];
    foreach ($senden as $n => $v) { $paare[] = $n . '=' . $v; }
    $ch = curl_init($basis . '/' . ltrim($pfad, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '',
        CURLOPT_HTTPHEADER => $paare !== [] ? ['Cookie: ' . implode('; ', $paare)] : []]);
    if ($methode === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($felder));
    }
    $roh = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $kl = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $k = substr($roh, 0, $kl);
    $setzt = [];
    if (preg_match_all('/^Set-Cookie:\s*([^=;\s]+)=([^;\r\n]*)([^\r\n]*)/mi', $k, $m, PREG_SET_ORDER)) {
        foreach ($m as $c) {
            $setzt[$c[1]] = $c[0];
            if ($c[2] !== '' && $c[2] !== 'deleted') {
                $keks[$c[1]] = $c[2];
                if ($c[1] === session_name()) { $gesehen[] = $c[2]; }
            } else {
                unset($keks[$c[1]]);
            }
        }
    }
    $ort = preg_match('/^Location:\s*(\S+)/mi', $k, $l) ? $l[1] : '';
    return ['code' => $code, 'ort' => $ort, 'rumpf' => substr($roh, $kl)];
}

function csrf_von(string $html): string
{
    return preg_match('/name="csrf" value="([0-9a-f]+)"/', $html, $m) ? $m[1] : '';
}

function passwort(): array
{
    global $keks, $mail, $token, $iter;
    $keks = [];
    $s = http('GET', 'login.php');
    return http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'email' => $mail,
                                      'tokens' => json_encode([(string)$iter => $token])]);
}

function code_senden(string $code, array $ohne = []): array
{
    $s = http('GET', 'login.php', [], $ohne);
    return http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code',
                                      'code' => $code], $ohne);
}

/** Vollständig anmelden; liefert die Cookies danach. */
function anmelden(): array
{
    global $keks, $naechster;
    passwort();
    $r = code_senden($naechster());
    return ['code' => $r['code'], 'ort' => $r['ort'], 'keks' => $keks];
}

/** Eine Sitzungsdatei mit genau diesem Inhalt — UNMITTELBAR geschrieben, im
 *  Format des Standard-Serialisierers `php` (`schluessel|serialize(wert)`),
 *  wie die Rückwegprobe: `session_start()` ginge nach der ersten Ausgabe
 *  nicht mehr. So sieht eine Datei aus, die jemand gelesen hätte. */
function sitzung_schreiben(array $inhalt): string
{
    global $ort, $gesehen;
    $sid = 'sitzungsprobe' . bin2hex(random_bytes(10));
    $roh = '';
    foreach ($inhalt as $k => $v) { $roh .= $k . '|' . serialize($v); }
    file_put_contents($ort . '/sess_' . $sid, $roh);
    chmod($ort . '/sess_' . $sid, 0600);
    $gesehen[] = $sid;
    return $sid;
}

$bindungEnde = 'login.php?ende=bindung';
$epoch = (int)$pdo->query('SELECT session_epoch FROM users WHERE id = ' . $uid)->fetchColumn();

/* ---- 1. Der halbe Stand ------------------------------------------------------ */
echo "== 1. Der halbe Stand ist gebunden\n";
$a = passwort();
$z = $setzt[$BIND] ?? '';
pruefe($a['code'] === 303 && isset($keks[$BIND]), 'Passwort → 303 und ' . $BIND . ' gesetzt',
       $a['code'] . ', ' . ($z !== '' ? 'Cookie da' : 'kein Cookie'));
pruefe(stripos($z, 'secure') !== false && stripos($z, 'httponly') !== false
       && stripos($z, 'samesite=strict') !== false, 'Secure, HttpOnly, SameSite=Strict', trim($z));
/* Das Formular holt der Browser MIT Cookie (Code-Schritt, Token); abgeschickt
 * wird OHNE — so kaeme jemand, der die Datei gelesen hat: Kennung und Token
 * stehen darin, das Cookie nicht. */
$s = http('GET', 'login.php');
pruefe(str_contains($s['rumpf'], 'id="codeform"'), 'mit Cookie: der Code-Schritt steht');
$r = http('POST', 'login.php', ['csrf' => csrf_von($s['rumpf']), 'schritt' => 'code',
                                'code' => $naechster()], [$BIND]);
pruefe($r['code'] === 200 && str_contains($r['rumpf'], 'id="loginform"')
       && str_contains($r['rumpf'], 'data-vergessen="1"') && !isset($setzt[session_name()]),
       'richtiger Code ohne ' . $BIND . ' → keine Anmeldung, Passwortformular, Vormerkfach geräumt',
       (string)$r['code']);
$r2 = http('GET', 'login.php');
pruefe(str_contains($r2['rumpf'], 'id="loginform"') && !str_contains($r2['rumpf'], 'id="codeform"'),
       '… und der halbe Stand ist fort, auch mit Cookie');

/* ---- 2. Die Anmeldung ------------------------------------------------------- */
echo "== 2. Die Anmeldung würfelt die Bindung neu\n";
passwort();
$halbBind = $keks[$BIND] ?? '';
$halbSid = $keks[session_name()] ?? '';
$r = code_senden($naechster());
$sid = $keks[session_name()] ?? '';
$bind = $keks[$BIND] ?? '';
pruefe($r['code'] === 302 && str_ends_with($r['ort'], 'index.php'), 'Code → 302 auf index.php', $r['code'] . ' ' . $r['ort']);
pruefe($sid !== '' && $sid !== $halbSid && $bind !== '' && $bind !== $halbBind,
       'neue Kennung UND neue Bindung');
$datei = (string)@file_get_contents($ort . '/sess_' . $sid);
pruefe($datei !== '' && str_contains($datei, hash('sha256', $bind)) && !str_contains($datei, $bind),
       'die Sitzungsdatei trägt den SHA-256, nicht den Wert', $datei === '' ? 'Datei nicht gefunden' : '');
pruefe(http('GET', 'einstellungen.php')['code'] === 200, 'mit beiden Cookies: einstellungen.php → 200');
pruefe(http('GET', 'api/range.php?y=2026')['code'] === 200, 'mit beiden Cookies: api/range.php → 200');

/* ---- 3. Dieselbe Kennung ohne Bindung --------------------------------------- */
echo "== 3. Dieselbe Kennung ohne " . $BIND . "\n";
$vorher = $keks;
$r = http('GET', 'einstellungen.php', [], [$BIND]);
pruefe($r['code'] === 200 && str_contains($r['rumpf'], $bindungEnde)
       && str_contains($r['rumpf'], 'Die Sitzung ließ sich nicht bestätigen'),
       'Seite ohne Cookie → Abmeldeseite mit ende=bindung', (string)$r['code']);
$keks = $vorher;
$n = http('GET', 'einstellungen.php');
pruefe($n['code'] === 302 && str_ends_with($n['ort'], 'login.php'),
       '… danach ist die Sitzung beendet, auch mit Cookie', $n['code'] . ' ' . $n['ort']);

anmelden();
$r = http('GET', 'api/range.php?y=2026', [], [$BIND]);
$j = json_decode($r['rumpf'], true);
pruefe($r['code'] === 401 && is_array($j) && ($j['grund'] ?? '') === 'bindung'
       && ($j['error'] ?? '') === 'session_ende', 'API ohne Cookie → 401 JSON, grund „bindung"',
       $r['code'] . ' ' . substr($r['rumpf'], 0, 80));

anmelden();
$r = http('GET', 'einstellungen.php', [], [], [$BIND => bin2hex(random_bytes(32))]);
pruefe($r['code'] === 200 && str_contains($r['rumpf'], $bindungEnde), 'falscher Wert → ende=bindung', (string)$r['code']);
anmelden();
$r = http('GET', 'einstellungen.php', [], [], [$BIND => 'kein-hex']);
pruefe($r['code'] === 200 && str_contains($r['rumpf'], $bindungEnde),
       'ein Wert ohne Form (kein Hex) → ende=bindung, kein Absturz', (string)$r['code']);

/* ---- 4. Eine Datei von vor dem Umbau ---------------------------------------- */
echo "== 4. Eine Sitzungsdatei ohne Hash (wie vor Web 21.7.0, wie gelesen)\n";
$alt = sitzung_schreiben(['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(),
                          'csrf' => bin2hex(random_bytes(16))]);
$keks = [session_name() => $alt];
$r = http('GET', 'einstellungen.php');
pruefe($r['code'] === 200 && str_contains($r['rumpf'], $bindungEnde), 'ohne Cookie → ende=bindung', (string)$r['code']);
$alt2 = sitzung_schreiben(['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(),
                           'csrf' => bin2hex(random_bytes(16))]);
$keks = [session_name() => $alt2, $BIND => bin2hex(random_bytes(32))];
$r = http('GET', 'einstellungen.php');
pruefe($r['code'] === 200 && str_contains($r['rumpf'], $bindungEnde),
       'mit irgendeinem Cookie → ende=bindung (keine Übernahme, E-SR-04)', (string)$r['code']);
pruefe(!is_file($ort . '/sess_' . $alt2) || !str_contains((string)file_get_contents($ort . '/sess_' . $alt2), 'user_id'),
       '… und die Datei trägt danach keine Anmeldung mehr');

/* ---- 5. Die lesenden Seiten (F-SR-15) --------------------------------------- */
echo "== 5. Lesende Seite: Notfallblatt\n";
$wert = bin2hex(random_bytes(32));
$gebunden = sitzung_schreiben(['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(),
                               'csrf' => bin2hex(random_bytes(16)), 'bindung' => hash('sha256', $wert)]);
$keks = [session_name() => $gebunden, $BIND => $wert];
$r = http('GET', 'notfallblatt.php');
pruefe($r['code'] === 200 && str_contains($r['rumpf'], $mail), 'gebunden: das Blatt nennt die Kontoadresse',
       (string)$r['code']);
$keks = [session_name() => $gebunden];
$r = http('GET', 'notfallblatt.php');
pruefe($r['code'] === 200 && !str_contains($r['rumpf'], $mail), 'ohne Cookie: keine Kontoadresse', (string)$r['code']);
$keks = [session_name() => $gebunden, $BIND => $wert];
$r = http('GET', 'notfallblatt.php');
pruefe(str_contains($r['rumpf'], $mail), '… und die lesende Seite hat die Sitzung nicht beendet (Gegenprobe)');

/* ---- 6. Abmelden ------------------------------------------------------------ */
echo "== 6. Abmelden löscht beide Cookies\n";
anmelden();
$r = http('GET', 'logout.php');
$weg = static fn(string $n): bool => isset($setzt[$n])
    && (preg_match('/^Set-Cookie:\s*' . preg_quote($n, '/') . '=(deleted)?;/i', $setzt[$n]) === 1);
pruefe($weg(session_name()) && $weg($BIND), 'logout.php: ' . session_name() . ' und ' . $BIND . ' gelöscht',
       implode(' | ', array_map('trim', $setzt)));
pruefe(!isset($keks[$BIND]) && !isset($keks[session_name()]), '… der Behälter der Probe ist leer');

/* ---- 7. Der Text zum Grund -------------------------------------------------- */
echo "== 7. Die Anmeldeseite kennt den Grund\n";
$r = http('GET', 'login.php?ende=bindung');
pruefe(session_ende_text('bindung') !== '' && str_contains($r['rumpf'], e(session_ende_text('bindung'))),
       'login.php?ende=bindung zeigt den Text aus session_ende_text()');
pruefe(in_array('bindung', SESSION_ENDE_GRUENDE, true), '„bindung" steht in SESSION_ENDE_GRUENDE');

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
