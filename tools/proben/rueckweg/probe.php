<?php
declare(strict_types=1);
/**
 * Rückwegprobe, Server-Teil — hält der Rückweg beim Zweitfaktor, was
 * Konzept RW zusagt? (E-RW-03, -04, -05, -11, -12)
 *
 * Anlass: F-P5c-106 — der Rückweg nach E-P5c-42 prüfte gegen
 * `pat_key_check`, einen Wert, den jeder Datenbankabzug enthält; wer einen
 * Abzug hatte, legte ihn vor. Nr. 141 (Zweitfaktor).
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
 * WAS SIE (NOCH) NICHT MISST: den Weg im Server am Code-Schritt und die
 * Abzug-Gegenprobe (Teil B, kommt mit RW-03), den Weg im Browser
 * (`probe.mjs`, RW-03). Das Anlegen über den Endpunkt misst der Bedienweg
 * `einstellungen-profil-rueckweg` — mit Sitzung, Token und Browser.
 *
 * SIE RÄUMT AUF: Die Marke in `app_state` steht danach wie vorher; das
 * Probekonto aus Teil 7 ist danach weg.
 *
 * Aufruf:  php tools/proben/rueckweg/probe.php
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

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
