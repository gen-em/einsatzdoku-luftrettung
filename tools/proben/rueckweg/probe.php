<?php
declare(strict_types=1);
/**
 * Rückwegprobe, Server-Teil — hält der Rückweg beim Zweitfaktor, was
 * Konzept RW zusagt? (E-RW-03, -04, -05, -11, -12)
 *
 * Anlass: Nr. 319 (F-P5c-106) — der Rückweg nach E-P5c-42 prüfte gegen
 * `pat_key_check`, einen Wert, den jeder Datenbankabzug enthält; wer einen
 * Abzug hatte, legte ihn vor. Dazu Nr. 141 (Zweitfaktor).
 *
 * WAS SIE MISST, OHNE HTTP (Muster Anteilprobe).
 *   Teil A — die Bibliothek (`rueckweg_lib.php`, RW-01):
 *     1. Der Selbsttest mit erzwungener Engine, OpenSSL und reines PHP —
 *        beide müssen den festen Browser-Vektor annehmen; die Dauer steht da.
 *     2. Sechs Signaturfälle nach Konzept RW 5.3: echt → angenommen; fremd,
 *        verändert, zweckfremd, fremdes Konto, verstümmelt → abgewiesen.
 *     3. Fremde Schlüssel: P-384 und Ed25519 weist `rw_oeffentlich_pruefen()`
 *        ab, und eine gültige Signatur mit ihnen nimmt `rw_pruefen()` nicht an.
 *     4. Die Prüfregeln des Paars: die Form des öffentlichen Teils, `edk1:`
 *        für den privaten, nie `edka1:`.
 *     5. Die Statuszeile „Rückweg-Prüfung" in drei Lagen, mit gesetzten Werten.
 *     6. Die Marke: Der Selbsttest läuft einmal je Marke, ein gestellter
 *        Fehlschlag schaltet den Weg ab.
 *     7. Der Stand des Paars (RW-02): `rw_zustand()` in allen vier Lagen —
 *        'da' mit Datum, 'fehlt', 'demo', und 'spalten' gegen eine
 *        Datenbank ohne die Spalten (SQLite im Speicher; die der Anlage
 *        wird dafür nicht zurückgebaut).
 *     8. Das Kontopaket trägt das Paar nicht (E-RW-09): kein `rw_` in den
 *        Spaltenlisten von Konto-Backup und Freigabe.
 *
 *   Teil B — der Weg im Server (RW-03), über `rw_rueckweg_pruefen()`, den
 *   Prüfzweig von `login.php`, mit einer gestellten halben Sitzung:
 *     B1. echte Signatur → angenommen, die Herausforderung verbraucht — und
 *         der Zweitfaktor NOCH AN: Abgeschaltet wird erst in `login.php`,
 *         hinter dem Tor (F-RW-21; gemessen in B8)
 *     B2. dieselbe Signatur noch einmal → abgewiesen (verbraucht)
 *     B3. abgelaufen → abgewiesen, die nächste Herausforderung ist eine neue
 *     B4. fremd, verändert, zweckfremd → abgewiesen, jeder zählt im Topf
 *         `totp`; weiter bis zur Sperre, danach auch die echte abgewiesen
 *     B5. die alte Fassung: `pat_key_check` statt einer Signatur → abgewiesen
 *     B6. nicht angeboten (kein Paar; Selbsttest gescheitert) → abgewiesen
 *     B7. die Abzug-Gegenprobe (Konzept RW 5.2): jeder Wert der Kontozeile,
 *         jeder aus `app_state`, `server_key`, `kdf_anteil` und der Anteil
 *         des Kontos — als Hex und als Rohbytes — als Schlüssel für
 *         `rw_privat`: 0 Erfolge. Der richtige Inhaltsschlüssel öffnet ihn
 *         (Gegenprobe des Öffners, sonst wäre „0" kein Beleg).
 *     B8. über HTTP gegen die örtliche Anlage, mit gestellter Sitzung:
 *         Verweis im Code-Schritt 1-mal mit Paar, 0-mal ohne; ein POST mit
 *         gültiger Signatur an ein Konto ohne Paar → 400; die echte
 *         Signatur → Erfolgskarte, Zweitfaktor aus, Codes 0, Protokoll
 *         `totp_zurueckgesetzt` mit `weg=schluessel` +1, Mail +1; dieselbe
 *         an einem GESPERRTEN Konto → die Seite des Tors, und der
 *         Zweitfaktor bleibt an, ohne Protokoll und ohne Mail (F-RW-21).
 *
 * DAS KONTO VON TEIL B BAUT DIE PROBE SELBST, mit derselben Verpackung wie
 * `crypto.js` (`edk1:` = AES-256-GCM, IV vorn, Tag hinten): Sie muss den
 * Inhaltsschlüssel KENNEN, um zu zeigen, dass nichts anderes ihn ersetzt.
 * Den echten Weg im Browser, mit der Krypto der Anwendung, fährt
 * `probe.mjs`; das Anlegen über den Endpunkt der Bedienweg
 * `einstellungen-profil-rueckweg`.
 *
 * SIE RÄUMT AUF: Die Marke in `app_state` steht danach wie vorher; die
 * Probekonten, ihre Protokolleinträge, Mails, Zähler und die gestellte
 * Sitzung sind danach weg.
 *
 * Aufruf:  php tools/proben/rueckweg/probe.php [basisadresse]
 *          (Vorgabe http://127.0.0.1:8080, nur für B8)
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht.
 */
$wurzel = dirname(__DIR__, 3);
require_once $wurzel . '/server/db.php';
require_once $wurzel . '/server/rueckweg_lib.php';
require_once $wurzel . '/server/validate_lib.php';
require_once $wurzel . '/server/status_lib.php';

use phpseclib3\Crypt\EC;

$gut = 0; $schlecht = 0;
function pruefe(bool $ok, string $was, string $sonst = ''): void
{
    global $gut, $schlecht;
    if ($ok) { $gut++; echo "  ok    $was" . ($sonst !== '' ? "  ($sonst)" : '') . "\n"; return; }
    $schlecht++;
    echo "  FEHLT $was" . ($sonst !== '' ? " — $sonst" : '') . "\n";
}

/** Den öffentlichen Teil eines phpseclib-Schlüssels als SPKI in Base64. */
function spki(EC\PrivateKey $k): string
{
    $pem = $k->getPublicKey()->toString('PKCS8');
    return (string)preg_replace('/-----[^-]+-----|\s/', '', $pem);
}
function signiere(EC\PrivateKey $k, string $nachricht): string
{
    return $k->withSignatureFormat('IEEE')->withHash('sha256')->sign($nachricht);
}

/* Die Marke steht danach wie vorher — auch nach einem Abbruch. */
$markeVorher = app_state_lesen(RW_MARKE);
register_shutdown_function(static function () use ($markeVorher): void {
    if ($markeVorher === null) { app_state_loeschen(RW_MARKE); }
    else { app_state_setzen(RW_MARKE, $markeVorher); }
});

/* ---- 1. Selbsttest mit erzwungener Engine ------------------------------------ */
echo "== A1. Selbsttest mit erzwungener Engine (fester Vektor aus Chromium 141)\n";
$o = rw_selbsttest('OpenSSL');
pruefe($o['ok'] && $o['weg'] === 'openssl', 'OpenSSL: Vektor angenommen, veränderte Nachricht abgewiesen',
       sprintf('%.1f ms', $o['ms']));
$p = rw_selbsttest('PHP');
/* SOLLBEREICH 150 BIS 400 MS (Konzept RW, RW-01) — als Auskunft. Gefordert
 * ist, dass reines PHP unter einer Sekunde bleibt: Mehr trüge ein Weg nicht,
 * der am Code-Schritt auf eine Antwort wartet. */
pruefe($p['ok'] && $p['weg'] === 'php' && $p['ms'] < 1000,
       'reines PHP: Vektor angenommen, veränderte Nachricht abgewiesen, unter 1 s',
       sprintf('%.1f ms — %s Sollbereich 150–400 ms', $p['ms'],
               ($p['ms'] >= 150 && $p['ms'] <= 400) ? 'im' : 'AUSSERHALB des'));
$a = rw_selbsttest();
pruefe($a['ok'] && $a['weg'] === 'openssl', 'ohne Zwang: derselbe Weg wie rw_pruefen(), hier openssl',
       $a['weg'] . sprintf(', %.1f ms', $a['ms']));

/* ---- 2. Sechs Signaturfälle -------------------------------------------------- */
echo "== A2. Sechs Signaturfälle (Konzept RW 5.3)\n";
$paar  = EC::createKey(RW_KURVE);
$fremd = EC::createKey(RW_KURVE);
$konto = 4711;
$hf    = rw_herausforderung();
$nachricht = rw_nachricht($konto, $hf);
$sig   = signiere($paar, $nachricht);
$pub   = spki($paar);
$faelle = [
    'echt: richtige Nachricht'            => [true,  rw_pruefen($pub, $nachricht, $sig)],
    'fremd: Signatur eines anderen Paars' => [false, rw_pruefen($pub, $nachricht, signiere($fremd, $nachricht))],
    'verändert: ein Byte der Nachricht'   => [false, rw_pruefen($pub, substr_replace($nachricht, 'X', -1, 1), $sig)],
    'zweckfremd: passwort-reset'          => [false, rw_pruefen($pub, $nachricht,
                                                   signiere($paar, RW_PRAEFIX . '|passwort-reset|' . $konto . '|' . $hf))],
    'fremdes Konto in der Nachricht'      => [false, rw_pruefen($pub, $nachricht,
                                                   signiere($paar, rw_nachricht($konto + 1, $hf)))],
    'verstümmelt: 63 Byte'                => [false, rw_pruefen($pub, $nachricht, substr($sig, 0, 63))],
];
$an = 0; $ab = 0;
foreach ($faelle as $was => [$soll, $ist]) {
    pruefe($ist === $soll, $was . ' → ' . ($soll ? 'angenommen' : 'abgewiesen'));
    $ist ? $an++ : $ab++;
}
pruefe($an === 1 && $ab === 5, "zusammen: $an angenommen, $ab abgewiesen (Soll 1 / 5)");
pruefe(strlen($sig) === 64, 'eine Signatur im IEEE-Format hat 64 Byte', strlen($sig) . ' Byte');

/* ---- 3. Fremde Schlüssel ----------------------------------------------------- */
echo "== A3. Fremde Kurve, fremdes Verfahren\n";
foreach (['secp384r1' => 'P-384', 'Ed25519' => 'Ed25519'] as $kurve => $name) {
    $k = EC::createKey($kurve);
    $kp = spki($k);
    $grund = rw_oeffentlich_pruefen($kp);
    $s = $kurve === 'Ed25519' ? $k->sign($nachricht)
                              : $k->withSignatureFormat('IEEE')->withHash('sha256')->sign($nachricht);
    pruefe($grund !== null && !rw_pruefen($kp, $nachricht, $s),
           "$name: rw_oeffentlich_pruefen() weist ab, rw_pruefen() nimmt seine gültige Signatur nicht an",
           (string)$grund);
    /* Die Kurvenprüfung selbst, ohne die Formregel davor: Ein Ed25519-SPKI
       ist kürzer als die Regel erlaubt und fiele sonst schon dort. */
    pruefe(rw_oeffentlich_laden($kp) === null, "$name: rw_oeffentlich_laden() lädt ihn nicht (Kurve)");
}
pruefe(rw_oeffentlich_pruefen('kein Base64 !') !== null && rw_oeffentlich_pruefen(str_repeat('A', 124)) !== null,
       'Unsinn in der Form des öffentlichen Teils: abgewiesen');

/* ---- 4. Prüfregeln des Paars ------------------------------------------------- */
echo "== A4. Prüfregeln (E-RW-05)\n";
pruefe((bool)preg_match(RW_OEFFENTLICH_RE, $pub) && strlen($pub) === 124,
       'RW_OEFFENTLICH_RE nimmt einen P-256-SPKI', strlen($pub) . ' Zeichen');
$chiffre = base64_encode(random_bytes(160));
pruefe((bool)preg_match(RW_PRIVAT_RE, 'edk1:' . $chiffre), 'RW_PRIVAT_RE nimmt edk1:');
pruefe(!preg_match(RW_PRIVAT_RE, 'edka1:0123456789abcdef:' . $chiffre),
       'RW_PRIVAT_RE weist edka1: ab — der private Teil hängt nie am Server-Anteil');

/* ---- 5. Statuszeile ---------------------------------------------------------- */
echo "== A5. Statuszeile „Rückweg-Prüfung\" in drei Lagen\n";
$lagen = [
    ['openssl', ['ok' => true,  'weg' => 'openssl', 'ms' => 2.0],   'blau',   'prüft',        'openssl'],
    ['php',     ['ok' => true,  'weg' => 'php',     'ms' => 214.0], 'blau',   'prüft',        'reines PHP'],
    ['fehl',    ['ok' => false, 'weg' => 'php',     'ms' => 0.0],   'orange', 'abgeschaltet', 'abgeschaltet'],
];
$treffer = 0;
foreach ($lagen as [$n, $t, $ton, $plakette, $wort]) {
    $z = status_rueckwegzeile($t);
    $ok = $z['ton'] === $ton && $z['plakette'] === $plakette && str_contains($z['klein'], $wort);
    $treffer += $ok ? 1 : 0;
    pruefe($ok, "Lage $n → $plakette / $ton", $z['klein']);
}
pruefe($treffer === 3, "$treffer von 3 Lagen");

/* ---- 6. Die Marke ------------------------------------------------------------ */
echo "== A6. Die Marke: einmal je Fassung und Plattform\n";
app_state_loeschen(RW_MARKE);
$n0 = rw_selbsttest_laeufe();
$v1 = rw_verfuegbar();
$v2 = rw_verfuegbar();
pruefe($v1 && $v2 && rw_selbsttest_laeufe() - $n0 === 1,
       'zweimal gefragt, einmal getestet', (rw_selbsttest_laeufe() - $n0) . ' Lauf');
$m = json_decode((string)app_state_lesen(RW_MARKE), true);
pruefe(is_array($m) && ($m['marke'] ?? '') === rw_marke_schluessel(), 'die Marke trägt Fassung und Plattform');
app_state_setzen(RW_MARKE, (string)json_encode(['ok' => true, 'weg' => 'openssl', 'ms' => 1, 'marke' => 'alt']));
$n0 = rw_selbsttest_laeufe();
rw_verfuegbar();
pruefe(rw_selbsttest_laeufe() - $n0 === 1, 'eine fremde Marke (anderer Deploy) → einmal neu getestet');
app_state_setzen(RW_MARKE, (string)json_encode(['ok' => false, 'weg' => 'php', 'ms' => 0,
                                                 'marke' => rw_marke_schluessel()]));
pruefe(rw_verfuegbar() === false, 'ein gemerkter Fehlschlag schaltet den Weg ab');

/* ---- 7. Der Stand des Paars ------------------------------------------------- */
echo "== A7. rw_zustand() in vier Lagen (RW-02)\n";
$adresse = 'rueckwegprobe@probe.invalid';
$pdo = db();
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$adresse]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Rückwegprobe', 'user', '', '', 320000)")->execute([$adresse]);
$probeId = (int)$pdo->lastInsertId();
register_shutdown_function(static function () use ($pdo, $probeId): void {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$probeId]);
});
$lagen = [];
$lagen['fehlt'] = rw_zustand($probeId);
$pdo->prepare('UPDATE users SET rw_oeffentlich = ?, rw_privat = ?, rw_seit = UTC_TIMESTAMP()
               WHERE id = ?')->execute([$pub, 'edk1:' . $chiffre, $probeId]);
$lagen['da'] = rw_zustand($probeId);
$ohne = new PDO('sqlite::memory:');
$ohne->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, email TEXT)');
$lagen['spalten'] = rw_zustand($probeId, $ohne);
require_once $wurzel . '/server/demo_lib.php';
$demo = demo_id();
$lagen['demo'] = $demo === null ? ['stand' => 'kein Demo-Konto vermerkt'] : rw_zustand($demo);
$treffer = 0;
foreach ($lagen as $soll => $z) {
    $ok = ($z['stand'] ?? '') === $soll && ($soll !== 'da' || !empty($z['seit']));
    $treffer += $ok ? 1 : 0;
    pruefe($ok, "Lage $soll", ($z['stand'] ?? '?') . ($soll === 'da' ? ', seit ' . ($z['seit'] ?? '—') : ''));
}
pruefe($treffer === 4, "$treffer von 4 Lagen");

/* ---- 8. Das Kontopaket trägt das Paar nicht ---------------------------------- */
echo "== A8. Kontopaket und Freigabe ohne das Paar (E-RW-09)\n";
/* GEZÄHLT IN DEN ZEICHENKETTEN DES QUELLTEXTS, nicht in Kommentaren: Die
 * Spaltenlisten stehen als SQL in Zeichenketten, und ein Kommentar, der die
 * Spalten nennt, ist keine Spaltenliste. */
$treffer = [];
foreach (['server/adminbackup_lib.php', 'server/backup_lib.php'] as $datei) {
    foreach (token_get_all((string)file_get_contents($wurzel . '/' . $datei)) as $t) {
        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING && preg_match('/\brw_[a-z]/', $t[1])) {
            $treffer[] = basename($datei) . ':' . $t[2];
        }
    }
}
pruefe($treffer === [], 'rw_ in den Zeichenketten von adminbackup_lib.php und backup_lib.php',
       count($treffer) . ' Treffer' . ($treffer === [] ? '' : ' — ' . implode(', ', $treffer)));

/* ============================================================================
 * TEIL B — der Weg im Server (RW-03)
 * ========================================================================== */
echo "\n== Teil B: der Rückweg am Code-Schritt (RW-03)\n";
require_once $wurzel . '/server/totp_lib.php';
require_once $wurzel . '/server/ratelimit_lib.php';
require_once $wurzel . '/server/serverkrypto_lib.php';

/** `crypto.js` encrypt(): edk1: + Base64(IV 12 || Chiffre || Tag 16). */
function edk_verpacken(string $schluessel, string $klartext): string
{
    $iv = random_bytes(12);
    $tag = '';
    $c = openssl_encrypt($klartext, 'aes-256-gcm', $schluessel, OPENSSL_RAW_DATA, $iv, $tag);
    return 'edk1:' . base64_encode($iv . $c . $tag);
}
function edk_oeffnen(string $schluessel, string $blob): ?string
{
    $roh = base64_decode(substr($blob, 5), true);
    if ($roh === false || strlen($roh) < 29) { return null; }
    $k = openssl_decrypt(substr($roh, 12, -16), 'aes-256-gcm', $schluessel, OPENSSL_RAW_DATA,
                         substr($roh, 0, 12), substr($roh, -16));
    return $k === false ? null : $k;
}

$adresseB = 'rueckwegprobe-b@probe.invalid';
$merkmaleB = [rate_merkmal_kennung($adresseB)];
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$adresseB]);
$mailVorher = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn();

/* Das Konto: Inhaltsschlüssel, Wiederherstellungs-Hülle, Paar — wie der
 * Browser sie baut. Der Code des Zettels ist erfunden; die Probe braucht
 * nur, was aus ihm folgt. */
$ck    = bin2hex(random_bytes(32));
$rk    = hash('sha256', 'edk-rc:ABCDEFGHJKMNPQRSTVWX', true);
$paarB = EC::createKey(RW_KURVE);
$pkcs8 = (string)preg_replace('/-----[^-]+-----|\s/', '', $paarB->toString('PKCS8'));
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter,
                                  pat_wrap_rc, pat_key_check, rw_oeffentlich, rw_privat, rw_seit)
               VALUES (?, 'Rückwegprobe B', 'user', ?, ?, 600000, ?, ?, ?, ?, UTC_TIMESTAMP())")
    ->execute([$adresseB, password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
               bin2hex(random_bytes(16)), edk_verpacken($rk, $ck),
               substr(hash('sha256', 'edk-ckchk:' . $ck), 0, 32),
               spki($paarB), edk_verpacken(hex2bin($ck), $pkcs8)]);
$idB = (int)$pdo->lastInsertId();
$sitzungB = null;
register_shutdown_function(static function () use ($pdo, $idB, $merkmaleB, $mailVorher, &$sitzungB): void {
    $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id = ?')->execute([$idB]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$idB]);
    foreach ($merkmaleB as $m) {
        $pdo->prepare("DELETE FROM rate_limits WHERE topf = 'totp' AND merkmal = ?")->execute([$m]);
    }
    $pdo->exec("DELETE FROM mail_warteschlange WHERE id > $mailVorher AND schluessel = 'totp_zurueckgesetzt'");
    if ($sitzungB !== null) { @unlink($sitzungB); }
});
$zfAn = static function () use ($pdo, $idB): void {
    $pdo->prepare('UPDATE users SET totp_seit = UTC_TIMESTAMP(), totp_schritt = 1 WHERE id = ?')->execute([$idB]);
    $pdo->prepare('DELETE FROM totp_codes WHERE user_id = ?')->execute([$idB]);
    $pdo->prepare("INSERT INTO totp_codes (user_id, hash) VALUES (?, 'x'), (?, 'y')")->execute([$idB, $idB]);
};
$halb = ['konto' => $idB, 'email' => $adresseB, 'bis' => time() + 300];
$signieren = static fn(string $nachricht, ?EC\PrivateKey $k = null): string
    => base64_encode(($k ?? $paarB)->withSignatureFormat('IEEE')->withHash('sha256')->sign($nachricht));
$versuche = static function () use ($pdo, $merkmaleB): int {
    $st = $pdo->prepare("SELECT COALESCE(MAX(versuche), 0) FROM rate_limits WHERE topf = 'totp' AND merkmal = ?");
    $st->execute([$merkmaleB[0]]);
    return (int)$st->fetchColumn();
};
$protokollB = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse
    WHERE art = 'totp_zurueckgesetzt' AND betroffen_user_id = $idB
      AND JSON_UNQUOTE(JSON_EXTRACT(daten, '$.weg')) = 'schluessel'")->fetchColumn();
app_state_loeschen(RW_MARKE);   // frisch testen; der Schluss stellt die alte Marke wieder her

/* ---- B1. Die echte Signatur ---- */
echo "== B1. echte Signatur\n";
$zfAn();
$h = rw_herausforderung_stellen($halb);
$sig = $signieren(rw_nachricht($idB, $h));
$r = rw_rueckweg_pruefen($halb, $sig);
$zfStand = static fn(): array => $pdo->query("SELECT totp_seit IS NOT NULL AS an,
        (SELECT COUNT(*) FROM totp_codes WHERE user_id = $idB) AS codes FROM users WHERE id = $idB")
    ->fetch(PDO::FETCH_ASSOC);
$z = $zfStand();
pruefe($r['ok'] && (int)$z['an'] === 1 && (int)$z['codes'] === 2 && $protokollB() === 0,
       'angenommen — und die Bibliothek schaltet nichts ab (das tut login.php hinter dem Tor)',
       json_encode($r) . ', an=' . $z['an'] . ', ' . (int)$z['codes'] . ' Codes, Protokoll ' . $protokollB());
pruefe(!isset($halb['rw_herausforderung']), 'die Herausforderung ist verbraucht');

/* ---- B2. Dieselbe Signatur noch einmal ---- */
echo "== B2. wiederholt\n";
$zfAn();
$r = rw_rueckweg_pruefen($halb, $sig);
pruefe(!$r['ok'] && $r['grund'] === 'abgelaufen',
       'dieselbe Signatur ein zweites Mal → abgewiesen', ($r['grund'] ?? 'ok'));

/* ---- B3. Abgelaufen ---- */
echo "== B3. abgelaufen\n";
$h1 = rw_herausforderung_stellen($halb);
$halb['rw_bis'] = time() - 1;
$r = rw_rueckweg_pruefen($halb, $signieren(rw_nachricht($idB, $h1)));
$h2 = rw_herausforderung_stellen($halb);
pruefe(!$r['ok'] && $r['grund'] === 'abgelaufen' && $h2 !== $h1 && $versuche() === 0,
       'Frist gestellt → abgewiesen, nicht gezählt, die nächste ist eine neue', ($r['grund'] ?? 'ok'));

/* ---- B4. Falsche Signaturen, bis zur Sperre ---- */
echo "== B4. falsche Signaturen im Topf totp\n";
$fremdB = EC::createKey(RW_KURVE);
$faelle = [
    'fremd'      => static fn(string $h) => $signieren(rw_nachricht($idB, $h), $fremdB),
    'verändert'  => static fn(string $h) => $signieren(substr_replace(rw_nachricht($idB, $h), 'X', -1, 1)),
    'zweckfremd' => static fn(string $h) => $signieren(RW_PRAEFIX . '|passwort-reset|' . $idB . '|' . $h),
];
foreach ($faelle as $was => $bau) {
    $vor = $versuche();
    $h = rw_herausforderung_stellen($halb);
    $r = rw_rueckweg_pruefen($halb, $bau($h));
    pruefe(!$r['ok'] && $r['grund'] === 'signatur' && $versuche() === $vor + 1,
           "$was → abgewiesen, gezählt", ($r['grund'] ?? 'ok') . ', Zähler ' . $versuche());
}
$runden = 0;
do {
    $h = rw_herausforderung_stellen($halb);
    $r = rw_rueckweg_pruefen($halb, $faelle['fremd']($h));
    $runden++;
} while (($r['grund'] ?? '') === 'signatur' && $runden < 20);
$h = rw_herausforderung_stellen($halb);
$echt = rw_rueckweg_pruefen($halb, $signieren(rw_nachricht($idB, $h)));
pruefe(($r['grund'] ?? '') === 'gesperrt' && !$echt['ok'] && ($echt['grund'] ?? '') === 'gesperrt'
       && $pdo->query("SELECT totp_seit IS NOT NULL FROM users WHERE id = $idB")->fetchColumn() == 1,
       'Sperre im Topf totp: danach auch die echte Signatur abgewiesen, Zweitfaktor bleibt',
       (3 + $runden) . ' falsche bis zur Sperre');
foreach ($merkmaleB as $m) {
    $pdo->prepare("DELETE FROM rate_limits WHERE topf = 'totp' AND merkmal = ?")->execute([$m]);
}

/* ---- B5. Die alte Fassung ---- */
echo "== B5. alte Fassung (pat_key_check statt Signatur)\n";
$check = (string)$pdo->query("SELECT pat_key_check FROM users WHERE id = $idB")->fetchColumn();
$ergebnisse = [];
foreach ([$check, base64_encode($check), base64_encode((string)hex2bin($check)), ''] as $vorlage) {
    rw_herausforderung_stellen($halb);
    $ergebnisse[] = rw_rueckweg_pruefen($halb, $vorlage)['ok'];
}
pruefe(!in_array(true, $ergebnisse, true), 'pat_key_check (roh, Base64, Hex→Base64) und leer: 4 von 4 abgewiesen');
foreach ($merkmaleB as $m) {
    $pdo->prepare("DELETE FROM rate_limits WHERE topf = 'totp' AND merkmal = ?")->execute([$m]);
}

/* ---- B6. Nicht angeboten ---- */
echo "== B6. nicht angeboten\n";
$pdo->prepare('UPDATE users SET rw_oeffentlich = NULL WHERE id = ?')->execute([$idB]);
$h = rw_herausforderung_stellen($halb);
$r1 = rw_rueckweg_pruefen($halb, $signieren(rw_nachricht($idB, $h)));
$pdo->prepare('UPDATE users SET rw_oeffentlich = ? WHERE id = ?')->execute([spki($paarB), $idB]);
app_state_setzen(RW_MARKE, (string)json_encode(['ok' => false, 'weg' => 'php', 'ms' => 0,
                                                 'marke' => rw_marke_schluessel()]));
$h = rw_herausforderung_stellen($halb);
$r2 = rw_rueckweg_pruefen($halb, $signieren(rw_nachricht($idB, $h)));
app_state_loeschen(RW_MARKE);
pruefe(($r1['grund'] ?? '') === 'nicht_angeboten' && ($r2['grund'] ?? '') === 'nicht_angeboten',
       'ohne Paar und bei gescheitertem Selbsttest: 2 von 2 abgewiesen',
       ($r1['grund'] ?? 'ok') . ' / ' . ($r2['grund'] ?? 'ok'));

/* ---- B7. Die Abzug-Gegenprobe ---- */
echo "== B7. Abzug-Gegenprobe (Konzept RW 5.2)\n";
$zeile = $pdo->query("SELECT * FROM users WHERE id = $idB")->fetch(PDO::FETCH_ASSOC);
$werte = array_values(array_filter(array_map('strval', $zeile), static fn($v) => $v !== ''));
foreach ($pdo->query('SELECT v FROM app_state')->fetchAll(PDO::FETCH_COLUMN) as $v) {
    if ((string)$v !== '') { $werte[] = (string)$v; }
}
foreach (['server_key', 'kdf_anteil'] as $k) {
    $v = (string)(konfig($k) ?? '');
    if ($v !== '') { $werte[] = $v; }
}
foreach (konto_anteile($idB) as $v) { $werte[] = (string)$v; }
$versuchtB = 0; $offenB = 0;
foreach ($werte as $v) {
    $kandidaten = [$v];
    if (ctype_xdigit($v) && strlen($v) % 2 === 0) { $kandidaten[] = (string)hex2bin($v); }
    foreach ($kandidaten as $schluessel) {
        $versuchtB++;
        if (edk_oeffnen($schluessel, (string)$zeile['rw_privat']) !== null) { $offenB++; }
    }
}
pruefe($offenB === 0, "jeder Wert des Abzugs als Schlüssel für rw_privat: $versuchtB Versuche",
       "$offenB Erfolge");
pruefe(edk_oeffnen((string)hex2bin($ck), (string)$zeile['rw_privat']) === $pkcs8,
       'Gegenprobe des Öffners: der Inhaltsschlüssel selbst öffnet rw_privat');

/* ---- B8. Über HTTP: der Verweis und die 400 ---- */
echo "== B8. über HTTP gegen die örtliche Anlage\n";
require_once $wurzel . '/server/sitzung_lib.php';
$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$ort = is_dir(sitzung_ablage_pfad()) ? sitzung_ablage_pfad() : (string)(session_save_path() ?: sys_get_temp_dir());
$sid = 'rueckwegprobe' . bin2hex(random_bytes(10));
$csrf = bin2hex(random_bytes(16));
$sitzungB = $ort . '/sess_' . $sid;
/* DIE SITZUNGSDATEI WIRD UNMITTELBAR GESCHRIEBEN, im Format des Standard-
 * Serialisierers `php` (`schluessel|serialize(wert)`). `session_start()`
 * ginge hier nicht mehr: Die Probe hat schon ausgegeben, und danach legt PHP
 * keine Sitzung mehr an (die Rollenprobe legt ihre deshalb VOR jeder Ausgabe
 * an). Eine halbe Sitzung, wie `login.php` sie nach dem Passwort hinterlässt. */
$halbSetzen = static function (?string $herausforderung = null) use ($sitzungB, $csrf, $idB, $adresseB): void {
    $h = ['konto' => $idB, 'email' => $adresseB, 'bis' => time() + 300];
    if ($herausforderung !== null) {
        $h['rw_herausforderung'] = $herausforderung;
        $h['rw_bis'] = time() + RW_HERAUSFORDERUNG_S;
    }
    file_put_contents($sitzungB, 'csrf|' . serialize($csrf) . 'totp_halb|' . serialize($h));
    chmod($sitzungB, 0600);
};
/* Eine gelungene Anmeldung zieht eine neue Sitzungskennung
 * (`session_regenerate_id`); deren Datei räumt die Probe am Schluss mit weg. */
$neueSitzungen = [];
register_shutdown_function(static function () use ($ort, &$neueSitzungen): void {
    foreach ($neueSitzungen as $n) { @unlink($ort . '/sess_' . $n); }
});
$hole = static function (string $pfad, ?array $koerper = null) use ($basis, $sid, &$neueSitzungen): array {
    $ch = curl_init("$basis/$pfad");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => ['Cookie: PHPSESSID=' . $sid], CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '',
        CURLOPT_HEADERFUNCTION => static function ($ch, string $zeile) use ($sid, &$neueSitzungen): int {
            if (preg_match('/^Set-Cookie:\s*PHPSESSID=([A-Za-z0-9,-]+)/i', $zeile, $m) && $m[1] !== $sid) {
                $neueSitzungen[] = $m[1];
            }
            return strlen($zeile);
        }]);
    if ($koerper !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($koerper));
    }
    $rumpf = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'rumpf' => $rumpf];
};
$zfAn();
$halbSetzen();
$mit = $hole('login.php');
$pdo->prepare('UPDATE users SET rw_oeffentlich = NULL WHERE id = ?')->execute([$idB]);
$ohne = $hole('login.php');
$post = $hole('login.php', ['csrf' => $csrf, 'schritt' => 'schluessel',
                            'signatur' => $signieren(rw_nachricht($idB, str_repeat('a', 64)))]);
$pdo->prepare('UPDATE users SET rw_oeffentlich = ? WHERE id = ?')->execute([spki($paarB), $idB]);
$zaehle = static fn(string $rumpf): int => substr_count($rumpf, 'href="login.php?weg=schluessel"');
pruefe($mit['code'] === 200 && $zaehle($mit['rumpf']) === 1 && $zaehle($ohne['rumpf']) === 0,
       'Verweis im Code-Schritt: mit Paar 1, ohne 0',
       "HTTP {$mit['code']}, " . $zaehle($mit['rumpf']) . ' / ' . $zaehle($ohne['rumpf']));
pruefe($post["code"] === 400 && str_contains($post['rumpf'], 'Dieser Weg steht nicht bereit'),
       'POST mit Signatur an ein Konto ohne Paar → 400', 'HTTP ' . $post['code']);

/* Die echte Signatur über den ganzen Weg: Hier erst schaltet `login.php` ab,
 * hinter dem Tor. Die Zahl der Mails zählt nur Zeilen dieser Probe. */
$mails = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
    WHERE id > $mailVorher AND schluessel = 'totp_zurueckgesetzt'")->fetchColumn();
$zfAn();
$protokollVor = $protokollB(); $mailVor = $mails();
$h = rw_herausforderung();
$halbSetzen($h);
$echt = $hole('login.php', ['csrf' => $csrf, 'schritt' => 'schluessel',
                            'signatur' => $signieren(rw_nachricht($idB, $h))]);
$z = $zfStand();
pruefe($echt['code'] === 200 && str_contains($echt['rumpf'], 'Zweitfaktor zurückgesetzt')
       && (int)$z['an'] === 0 && (int)$z['codes'] === 0
       && $protokollB() === $protokollVor + 1 && $mails() === $mailVor + 1,
       'echte Signatur über HTTP: Erfolgskarte, Zweitfaktor aus, Codes 0, Protokoll +1, Mail +1',
       "HTTP {$echt['code']}, an={$z['an']}, {$z['codes']} Codes, Protokoll "
       . ($protokollB() - $protokollVor) . ', Mail ' . ($mails() - $mailVor));

/* F-RW-21: Dieselbe echte Signatur an einem gesperrten Konto. Das Tor
 * `login_zugang()` weist es ab — und weil das Abschalten dahinter steht,
 * bleibt der Zweitfaktor an. In der ersten Fassung stand es davor: Er war
 * weg, ohne Anmeldung und ohne Mail. */
$zfAn();
$pdo->prepare("UPDATE users SET status = 'gesperrt', gesperrt_seit = NOW(), gesperrt_grund = 'verwaltung'
                WHERE id = ?")->execute([$idB]);
$protokollVor = $protokollB(); $mailVor = $mails();
$h = rw_herausforderung();
$halbSetzen($h);
$tor = $hole('login.php', ['csrf' => $csrf, 'schritt' => 'schluessel',
                           'signatur' => $signieren(rw_nachricht($idB, $h))]);
$pdo->prepare("UPDATE users SET status = 'aktiv', gesperrt_seit = NULL, gesperrt_grund = NULL
                WHERE id = ?")->execute([$idB]);
$z = $zfStand();
pruefe(str_contains($tor['rumpf'], 'Kein Zugang') && !str_contains($tor['rumpf'], 'Zweitfaktor zurückgesetzt')
       && (int)$z['an'] === 1 && (int)$z['codes'] === 2
       && $protokollB() === $protokollVor && $mails() === $mailVor,
       'gesperrtes Konto, echte Signatur: das Tor weist ab, Zweitfaktor bleibt an, kein Protokoll, keine Mail',
       "HTTP {$tor['code']}, an={$z['an']}, {$z['codes']} Codes, Protokoll "
       . ($protokollB() - $protokollVor) . ', Mail ' . ($mails() - $mailVor));

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
