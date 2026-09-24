<?php
declare(strict_types=1);

/**
 * MAILPROBE — die Warteschlange, der Katalog und die Frist, gegen einen
 * echten SMTPS-Gegenpart (P5a/AP5).
 *
 * Anlass: Nr. 204 — smtp_letzter_fehler() nannte den Grund des vorigen Versuchs
 *
 * Aufruf:  php tools/proben/mail/probe.php
 *
 * WAS SIE MISST UND WARUM SIE NOETIG IST. Bis Web 20.7.0 rief jede der zehn
 * Versandstellen `smtp_send()` unmittelbar; scheiterte es, war die Nachricht
 * weg. Die Warteschlange behauptet, dass das nicht mehr passiert. Eine
 * Behauptung ueber Fehlerfaelle laesst sich nur gegen eine Gegenstelle
 * pruefen, die auf Kommando scheitert — deshalb `gegenstelle.py` und nicht
 * ein echter Mailserver.
 *
 * WAS SIE NICHT MISST:
 *
 * - **Ob eine Mail ankommt.** Die Gegenstelle nimmt an und wirft weg. Ob ein
 *   echter Empfaenger die Nachricht im Postfach findet, sagt nur ein
 *   Postfach.
 * - **Das Aussehen.** Der Rahmen wird auf seine Bestandteile geprueft, nicht
 *   auf seine Wirkung. Wie eine Einladung in einem Mailprogramm aussieht,
 *   sagt nur ein Mailprogramm.
 * - **Namensaufloesung.** Alles laeuft ueber 127.0.0.1. Ein haengender
 *   DNS-Server liegt ausserhalb der Frist von `smtp_send()` — das steht dort
 *   ausgeschrieben und ist hier nicht nachstellbar.
 * - **Nebenlaeufigkeit.** Zwei Jobs, die dieselbe Zeile gleichzeitig
 *   greifen, sind nicht nachgestellt.
 *
 * DIE PROBE TAUSCHT `server/config.php` AUS und stellt sie wieder her —
 * beim regulaeren Ende, bei einer Ausnahme UND bei einem Abbruch
 * (`register_shutdown_function`). Wer sie unterbricht, findet trotzdem die
 * eigene Datei wieder; die Sicherung liegt daneben als `config.php.mailprobe`.
 */

$wurzel = dirname(__DIR__, 3);
$cfgPfad = $wurzel . '/server/config.php';
$sicher  = $wurzel . '/server/config.php.mailprobe';
$tmp     = sys_get_temp_dir() . '/mailprobe-' . getmypid();
@mkdir($tmp, 0700, true);

$GLOBALS['__mp_kind'] = null;

function mp_aufraeumen(): void
{
    global $cfgPfad, $sicher;
    if (is_file($sicher)) {
        @copy($sicher, $cfgPfad);
        @unlink($sicher);
    }
    if (!empty($GLOBALS['__mp_kind'])) {
        @exec('kill ' . (int)$GLOBALS['__mp_kind'] . ' 2>/dev/null');
        $GLOBALS['__mp_kind'] = null;
    }
}
register_shutdown_function('mp_aufraeumen');

if (!is_file($cfgPfad)) {
    fwrite(STDERR, "Keine server/config.php — die Probe braucht eine eingerichtete Installation.\n");
    exit(2);
}
copy($cfgPfad, $sicher);

/* ---- Zertifikat: eigene Wurzel, damit verify_peer greift ---------------- */

$zert = $tmp . '/gegenstelle.crt';
$key  = $tmp . '/gegenstelle.key';
$cnf  = $tmp . '/openssl.cnf';
file_put_contents($cnf, "[req]\ndistinguished_name=dn\nx509_extensions=v3\nprompt=no\n"
    . "[dn]\nCN=127.0.0.1\n[v3]\nsubjectAltName=IP:127.0.0.1,DNS:localhost\n"
    . "basicConstraints=critical,CA:TRUE\n");
exec('openssl req -x509 -newkey rsa:2048 -nodes -days 2 -keyout ' . escapeshellarg($key)
     . ' -out ' . escapeshellarg($zert) . ' -config ' . escapeshellarg($cnf) . ' 2>&1', $o, $rc);
if ($rc !== 0) {
    fwrite(STDERR, "openssl gescheitert:\n" . implode("\n", $o) . "\n");
    exit(2);
}
/* `verify_peer => true` prueft gegen die Wurzelliste des Systems. Die eigene
 * Wurzel kommt ueber SSL_CERT_FILE hinzu — das ist der einzige Weg, der ohne
 * Schreibrecht im Systemspeicher auskommt und der EINGEBAUTEN Pruefung nichts
 * nimmt: Ein falsches Zertifikat wuerde weiterhin abgewiesen. */
putenv('SSL_CERT_FILE=' . $zert);
$_ENV['SSL_CERT_FILE'] = $zert;

/* ---- Gegenstelle starten ------------------------------------------------ */

$PORT = 2465;

function mp_gegenstelle(string $art, float $verzug = 0.0): void
{
    global $tmp, $zert, $key, $PORT;
    if (!empty($GLOBALS['__mp_kind'])) {
        exec('kill ' . (int)$GLOBALS['__mp_kind'] . ' 2>/dev/null');
        $GLOBALS['__mp_kind'] = null;
        usleep(250000);
    }
    $log = $tmp . '/gegenstelle.log';
    $cmd = 'python3 ' . escapeshellarg(__DIR__ . '/gegenstelle.py')
         . ' --port ' . $PORT . ' --art ' . escapeshellarg($art)
         . ' --verzug ' . $verzug
         . ' --zert ' . escapeshellarg($zert) . ' --schluessel ' . escapeshellarg($key)
         . ' > ' . escapeshellarg($log) . ' 2>&1 & echo $!';
    $pid = (int)trim((string)shell_exec($cmd));
    $GLOBALS['__mp_kind'] = $pid;
    /* Auf das Horchen warten statt blind zu schlafen. */
    for ($i = 0; $i < 100; $i++) {
        $s = @fsockopen('127.0.0.1', $PORT, $e1, $e2, 0.1);
        if ($s) { fclose($s); return; }
        usleep(50000);
    }
    fwrite(STDERR, "Gegenstelle kam nicht hoch:\n" . (string)@file_get_contents($log) . "\n");
    exit(2);
}

/* ---- config.php auf die Gegenstelle zeigen lassen ----------------------- */

$cfg = require $sicher;
$cfg['smtp'] = ['host' => '127.0.0.1', 'port' => $PORT,
                'user' => 'probe', 'pass' => 'probe',
                'from' => 'noreply@mailprobe.invalid', 'from_name' => 'Mailprobe'];
file_put_contents($cfgPfad, "<?php return " . var_export($cfg, true) . ";\n");

require_once $wurzel . '/server/mail_lib.php';

/* ---- Zaehlwerk ---------------------------------------------------------- */

$GEPRUEFT = 0; $BEFUNDE = [];
function pruef(string $was, bool $ok, string $gemessen = ''): void
{
    global $GEPRUEFT, $BEFUNDE;
    $GEPRUEFT++;
    printf("  %s  %-58s %s\n", $ok ? 'ok  ' : 'FEHL', $was, $gemessen);
    if (!$ok) { $BEFUNDE[] = $was . ($gemessen !== '' ? ' — ' . $gemessen : ''); }
}
function abschnitt(string $t): void { echo "\n" . $t . "\n" . str_repeat('-', strlen($t)) . "\n"; }

$pdo = db();
$pdo->exec('DELETE FROM mail_warteschlange');
$adr = 'probe@mailprobe.invalid';

function mp_letzte(): ?array
{
    $r = db()->query('SELECT * FROM mail_warteschlange ORDER BY id DESC LIMIT 1')
             ->fetch(PDO::FETCH_ASSOC);
    return $r === false ? null : $r;
}

/* ======================================================================== */
abschnitt('1  Katalog — jeder Eintrag erzeugt Betreff und Text');

$katalog = mail_katalog();
pruef('Katalog hat Eintraege', count($katalog) > 0, count($katalog) . ' Eintraege');

$beispiel = [
    'link'       => 'https://beispiel.invalid/pw_handling.php?token=' . str_repeat('a', 43),
    'kern'       => 'Ein Kern fuer die Betriebsmails.',
    'alt'        => 'alt@beispiel.invalid',
    'neu'        => 'neu@beispiel.invalid',
    'durch'      => 'Die Verwaltung',
    'geraet'     => 'Dienstuhr',
    'geraet_id'  => 'AB3K7Q',
    'zeitpunkt'  => '16.09.2026, 12:00 Uhr',
    'titel'      => 'Backups',
    'prozent'    => 80,
    'belegt'     => '1,2 GB',
    'kontingent' => '1,5 GB',
    'grenze'     => '1,5 GB',
    'pakete'     => 42,
    'ordner'     => 7,
    'rat'        => 'Alte Pakete loeschen.',
    // Seit P5b: Loeschantrag (termin) und Kontomenge (einsaetze, speicher).
    // Fehlten sie hier, meldete die Probe rot, ohne dass ein Text falsch war (F-RP-04).
    'termin'     => '23.10.2026',
    'einsaetze'  => '1 234 von 1 500',
    'speicher'   => '412 MB von 500 MB',
];
$fehlend = [];
foreach ($katalog as $k => $e) {
    foreach ($e['pflicht'] as $p) {
        if (!array_key_exists($p, $beispiel)) { $fehlend[] = $k . '/' . $p; }
    }
}
pruef('Alle Pflichtwerte im Beispielsatz abgedeckt', $fehlend === [],
      $fehlend === [] ? '' : implode(', ', $fehlend));

$leer = [];
foreach ($katalog as $k => $e) {
    $b = ($e['betreff'])($beispiel);
    $t = ($e['text'])($beispiel);
    if (trim($b) === '' || trim($t) === '') { $leer[] = $k; }
}
pruef('Kein Eintrag erzeugt leeren Betreff oder Text', $leer === [],
      count($katalog) . ' Eintraege gerendert');

/* ======================================================================== */
abschnitt('2  Rahmen — Name und Kontakt kommen aus den Einstellungen');

/* `instanz_name()`, `instanz_kontakt()` und `betrieb_mail()` merken sich
 * ihren Wert je Anfrage (`static`). Ein Wechsel mitten im Lauf waere deshalb
 * NICHT messbar — die Probe misst ihn in einem EIGENEN Prozess. Das ist
 * keine Umstaendlichkeit, sondern die einzige ehrliche Messung: Genau so
 * verhaelt sich die Anwendung auch, wenn eine Betreiberin den Namen
 * umstellt — die naechste Anfrage sieht ihn, die laufende nicht. */
$messeRahmen = static function (string $name, string $kontakt) use ($wurzel): string {
    $code = 'require "' . $wurzel . '/server/mail_lib.php";'
          . 'app_state_setzen("instanz_name", ' . var_export($name, true) . ');'
          . 'app_state_setzen("instanz_kontakt", ' . var_export($kontakt, true) . ');'
          . 'echo mail_rahmen("Hallo,", "Die Sache.");';
    return (string)shell_exec('php -r ' . escapeshellarg($code) . ' 2>&1');
};

$mit = $messeRahmen('Rettungsdoku Musterkreis', 'kontakt@musterkreis.invalid');
pruef('Der Name aus den Einstellungen steht unter der Grussformel',
      str_contains($mit, "Viele Grüße\nRettungsdoku Musterkreis"),
      'Rahmen ' . strlen($mit) . ' Byte');
pruef('Die Kontaktadresse aus den Einstellungen steht darin',
      str_contains($mit, 'Bei Fragen wende dich an kontakt@musterkreis.invalid.'), '');

$ohne = $messeRahmen('Rettungsdoku Musterkreis', '');
pruef('Ohne Kontaktadresse faellt die Zeile WEG (statt falsch zu verweisen)',
      !str_contains($ohne, 'Bei Fragen'),
      strlen($ohne) . ' Byte, ' . (substr_count($mit, "\n") - substr_count($ohne, "\n"))
      . ' Zeilen weniger');
pruef('In keinem der beiden steht ein fest eingebauter Markenname',
      !preg_match('/gen-em|philipp@|luftrettung/i', $mit . $ohne), '');

/* Zurueck auf die Vorgabe, damit der Rest des Laufs sie sieht. */
shell_exec('php -r ' . escapeshellarg(
    'require "' . $wurzel . '/server/db.php";'
    . 'app_state_setzen("instanz_name", "");app_state_setzen("instanz_kontakt", "");') . ' 2>&1');

/* ======================================================================== */
abschnitt('3  Zustellung gegen einen antwortenden Server');

mp_gegenstelle('ok');
$pdo->exec('DELETE FROM mail_warteschlange');
$t0 = microtime(true);
$e  = mail_einreihen('einladung', $adr, ['link' => $beispiel['link']]);
$d  = microtime(true) - $t0;
pruef('mail_einreihen() meldet zugestellt', $e === MAIL_ZUGESTELLT, $e);
$z = mp_letzte();
pruef('Zustand der Zeile ist zugestellt', ($z['zustand'] ?? '') === 'zugestellt',
      (string)($z['zustand'] ?? '-'));
pruef('Empfaenger, Betreff und Rumpf sind geleert',
      $z !== null && $z['empfaenger'] === null && $z['betreff'] === null && $z['text'] === null,
      'empfaenger=' . var_export($z['empfaenger'] ?? null, true));
pruef('Zustellung unter 2 s', $d < 2.0, sprintf('%.2f s', $d));

/* ======================================================================== */
abschnitt('4  Ablehnung — Leiter, Grund, Kennung');

mp_gegenstelle('ablehnen');
$pdo->exec('DELETE FROM mail_warteschlange');
$e = mail_einreihen('passwort_reset', $adr, ['link' => $beispiel['link']]);
pruef('mail_einreihen() meldet wartet', $e === MAIL_WARTET, $e);
$z = mp_letzte();
pruef('Zeile bleibt offen', ($z['zustand'] ?? '') === 'offen', (string)($z['zustand'] ?? '-'));
pruef('Ein Versuch gezaehlt', (int)($z['versuche'] ?? 0) === 1, (string)($z['versuche'] ?? '-'));
pruef('Grund nennt den Antwortcode', str_contains((string)($z['fehler'] ?? ''), '550'),
      (string)($z['fehler'] ?? '-'));
pruef('Grund nennt eine Kennung',
      (bool)preg_match('/Kennung [0-9A-F]{8}/', (string)($z['fehler'] ?? '')), '');
pruef('Grund nennt KEINE Empfaengeradresse',
      !str_contains((string)($z['fehler'] ?? ''), '@'),
      'Die Gegenstelle haengt bewusst eine an ihre 550-Zeile.');
$abstand = strtotime((string)$z['naechster_versuch'] . ' UTC') - strtotime((string)$z['erstellt'] . ' UTC');
pruef('Naechster Versuch nach 300 s (erste Sprosse)', abs($abstand - 300) <= 2,
      $abstand . ' s');

/* ======================================================================== */
abschnitt('5  Fuenf Sprossen, dann unzustellbar — Adresse bleibt stehen');

/* GEMESSEN AN EINEM EINTRAG OHNE FRIST, und das ist der Punkt: Die erste
 * Fassung dieser Probe nahm `passwort_reset` und erwartete fuenf Versuche.
 * Gekommen sind drei — voellig richtig, denn dessen Frist ist eine Stunde und
 * die dritte Sprosse liegt bei 300+1800+7200 s. Der Reset-Link stirbt also an
 * seiner Frist, nicht an der Leiter. Beides wird gemessen, jedes an dem
 * Eintrag, fuer den es gilt. */
$pdo->exec('DELETE FROM mail_warteschlange');
mail_einreihen('adresswechsel', $adr, ['alt' => $beispiel['alt'],
                                       'neu' => $beispiel['neu'],
                                       'durch' => $beispiel['durch']]);
$z  = mp_letzte();
$id = (int)$z['id'];
pruef('Eintrag ohne Frist hat kein gueltig_bis', ($z['gueltig_bis'] ?? null) === null, '');
for ($i = 2; $i <= 5; $i++) {
    $pdo->prepare('UPDATE mail_warteschlange SET naechster_versuch = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([$id]);
    mail_zeile_versuchen($id);
}
$z = mp_letzte();
pruef('Nach fuenf Versuchen unzustellbar', ($z['zustand'] ?? '') === 'unzustellbar',
      (string)($z['zustand'] ?? '-') . ', versuche=' . (int)($z['versuche'] ?? 0));
pruef('Die Adresse steht noch da (E-P5a-39)', ($z['empfaenger'] ?? null) === $adr,
      var_export($z['empfaenger'] ?? null, true));
pruef('Der Rumpf ist weg (Token!)', ($z['text'] ?? null) === null, '');

/* Und die Gegenprobe am kurzlebigen Eintrag: Der Reset-Link laeuft an der
 * Frist aus, bevor die Leiter zu Ende ist. */
$pdo->exec('DELETE FROM mail_warteschlange');
mail_einreihen('passwort_reset', $adr, ['link' => $beispiel['link']]);
$kid = (int)mp_letzte()['id'];
for ($i = 2; $i <= 5; $i++) {
    $pdo->prepare('UPDATE mail_warteschlange SET naechster_versuch = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([$kid]);
    mail_zeile_versuchen($kid);
}
$z = mp_letzte();
pruef('Ein Reset-Link stirbt an seiner Frist, nicht an der Leiter',
      ($z['zustand'] ?? '') === 'zu_spaet' && (int)($z['versuche'] ?? 0) < 5,
      (string)($z['zustand'] ?? '-') . ' nach ' . (int)($z['versuche'] ?? 0)
      . ' von 5 Versuchen (Frist 3600 s, dritte Sprosse bei 9300 s)');

/* ======================================================================== */
abschnitt('6  Ueberholte Zeilen — der zweite Link entwertet den ersten');

mp_gegenstelle('ablehnen');
$pdo->exec('DELETE FROM mail_warteschlange');
mail_einreihen('passwort_reset', $adr, ['link' => $beispiel['link']]);
$erste = (int)mp_letzte()['id'];
mail_einreihen('passwort_reset', $adr, ['link' => $beispiel['link'] . 'x']);
$st = $pdo->prepare('SELECT * FROM mail_warteschlange WHERE id = ?');
$st->execute([$erste]);
$alt = $st->fetch(PDO::FETCH_ASSOC);
pruef('Die erste Zeile ist ueberholt', ($alt['zustand'] ?? '') === 'ueberholt',
      (string)($alt['zustand'] ?? '-'));
pruef('Ihr Rumpf ist geleert', ($alt['text'] ?? null) === null, '');

/* ======================================================================== */
abschnitt('7  Frist statt Dauer — ein schweigender Server');

mp_gegenstelle('stumm');
$pdo->exec('DELETE FROM mail_warteschlange');
$t0 = microtime(true);
$e  = mail_einreihen('testmail', $adr);
$d  = microtime(true) - $t0;
pruef('Ein schweigender Server haelt hoechstens MAIL_BUDGET_S + 1 s auf',
      $d < MAIL_BUDGET_S + 1.0, sprintf('%.2f s bei Budget %d s', $d, MAIL_BUDGET_S));
pruef('Die Zeile wartet, sie ist nicht verloren', $e === MAIL_WARTET, $e);

abschnitt('8  Frist statt Dauer — zwoelf Fortsetzungszeilen je 1 s');

mp_gegenstelle('vielzeilig', 1.0);
$pdo->exec('DELETE FROM mail_warteschlange');
$t0 = microtime(true);
mail_einreihen('testmail', $adr);
$d = microtime(true) - $t0;
pruef('Vielzeilige Antwort sprengt die Frist nicht',
      $d < MAIL_BUDGET_S + 1.0,
      sprintf('%.2f s; ohne Frist waeren es ueber 13 s', $d));

/* ======================================================================== */
abschnitt('9  Gueltigkeitsfrist — kein Versuch nach Ablauf');

mp_gegenstelle('ablehnen');
$pdo->exec('DELETE FROM mail_warteschlange');
mail_einreihen('passwort_reset', $adr, ['link' => $beispiel['link']]);
$id = (int)mp_letzte()['id'];
$pdo->prepare('UPDATE mail_warteschlange
                  SET gueltig_bis = DATE_ADD(UTC_TIMESTAMP(), INTERVAL 60 SECOND),
                      naechster_versuch = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
mail_zeile_versuchen($id);
$z = mp_letzte();
pruef('Zeile wird zu_spaet statt weiterversucht', ($z['zustand'] ?? '') === 'zu_spaet',
      (string)($z['zustand'] ?? '-'));
pruef('Adresse und Rumpf sind geleert',
      ($z['empfaenger'] ?? null) === null && ($z['text'] ?? null) === null, '');

/* ======================================================================== */
abschnitt('10  Abgewiesen — gar nicht erst eingereiht');

$pdo->exec('DELETE FROM mail_warteschlange');
$vorher = (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange')->fetchColumn();
$e1 = mail_einreihen('einladung', 'kein\ntext@x', ['link' => 'x']);
$e2 = mail_einreihen('einladung', $adr, []);            // Pflichtwert fehlt
$e3 = mail_einreihen('gibtesnicht', $adr, []);
$nachher = (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange')->fetchColumn();
pruef('Adresse mit Zeilenumbruch abgelehnt', $e1 === MAIL_ABGELEHNT, $e1);
pruef('Fehlender Pflichtwert abgelehnt', $e2 === MAIL_ABGELEHNT, $e2);
pruef('Unbekannter Schluessel abgelehnt', $e3 === MAIL_ABGELEHNT, $e3);
pruef('Keine dieser drei hat eine Zeile hinterlassen', $nachher === $vorher,
      $vorher . ' -> ' . $nachher);

/* ======================================================================== */
abschnitt('11  Der Job — Budget und Menge');

mp_gegenstelle('ok');
$pdo->exec('DELETE FROM mail_warteschlange');
for ($i = 0; $i < 4; $i++) {
    $pdo->prepare('INSERT INTO mail_warteschlange
                     (schluessel, art, empfaenger, betreff, text, zustand, versuche,
                      erstellt, naechster_versuch)
                   VALUES (?,?,?,?,?,\'offen\',0, UTC_TIMESTAMP(), UTC_TIMESTAMP())')
        ->execute(['testmail', 'betrieb', 'j' . $i . '@mailprobe.invalid',
                   'Betreff ' . $i, 'Rumpf']);
}
$t0 = microtime(true);
$erg = mail_job($pdo, [], static fn(): float => 3.0 - (microtime(true) - $t0));
$d = microtime(true) - $t0;
pruef('Der Job arbeitet am Huckepack-Budget', (int)$erg['erledigt'] === 4,
      'erledigt=' . (int)$erg['erledigt'] . ' in ' . sprintf('%.2f s', $d));
$offen = (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange WHERE zustand = \'offen\'')
                  ->fetchColumn();
pruef('Keine Zeile bleibt offen', $offen === 0, (string)$offen);

mp_gegenstelle('stumm');
$pdo->exec('DELETE FROM mail_warteschlange');
for ($i = 0; $i < 6; $i++) {
    $pdo->prepare('INSERT INTO mail_warteschlange
                     (schluessel, art, empfaenger, betreff, text, zustand, versuche,
                      erstellt, naechster_versuch)
                   VALUES (?,?,?,?,?,\'offen\',0, UTC_TIMESTAMP(), UTC_TIMESTAMP())')
        ->execute(['testmail', 'betrieb', 's' . $i . '@mailprobe.invalid', 'B', 'R']);
}
$t0 = microtime(true);
$erg = mail_job($pdo, [], static fn(): float => 3.0 - (microtime(true) - $t0));
$d = microtime(true) - $t0;
pruef('Ein haengender Server reisst das Budget nicht', $d < 4.0,
      sprintf('%.2f s bei 3,0 s Vorgabe, erledigt=%d', $d, (int)$erg['erledigt']));

/* ======================================================================== */
abschnitt('12  Betriebsziele — Einstellung schlaegt Rollenliste');

app_state_setzen('betrieb_mail', '');
$ohneEinstellung = mail_betriebsziele();
$pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
               ON DUPLICATE KEY UPDATE v = VALUES(v)')
    ->execute(['betrieb_mail', 'betrieb@musterkreis.invalid']);
/* `betrieb_mail()` haelt statisch — deshalb in einem eigenen Lauf messen. */
$cmd = 'php -r ' . escapeshellarg(
    'require "' . $wurzel . '/server/mail_lib.php"; print_r(mail_betriebsziele());');
$mitEinstellung = (string)shell_exec($cmd . ' 2>&1');
pruef('Ohne Einstellung: die Rollenliste', count($ohneEinstellung) >= 1,
      count($ohneEinstellung) . ' Adressen');
pruef('Mit Einstellung: genau diese eine',
      substr_count($mitEinstellung, '=>') === 1
      && str_contains($mitEinstellung, 'betrieb@musterkreis.invalid'),
      trim(preg_replace('/\s+/', ' ', $mitEinstellung)));

/* ======================================================================== */
abschnitt('13  Das Fehlerprotokoll nennt keinen Empfaenger');

$logDatei = $tmp . '/php-fehler.log';
@unlink($logDatei);
ini_set('error_log', $logDatei);
mp_gegenstelle('ablehnen');
$pdo->exec('DELETE FROM mail_warteschlange');
mail_einreihen('einladung', 'geheimer.empfaenger@mailprobe.invalid',
               ['link' => $beispiel['link']]);
$log = (string)@file_get_contents($logDatei);
pruef('Kein "@" im erzeugten Protokoll', !str_contains($log, 'geheimer.empfaenger'),
      strlen($log) . ' Byte Protokoll, ' . substr_count($log, "\n") . ' Zeilen');
pruef('Aber eine Kennung', (bool)preg_match('/\[[0-9A-F]{8}\]/', $log),
      trim(str_replace("\n", ' | ', $log)));

/* ======================================================================== */
$pdo->exec('DELETE FROM mail_warteschlange');
app_state_setzen('instanz_name', '');
app_state_setzen('instanz_kontakt', '');
app_state_setzen('betrieb_mail', '');
mp_aufraeumen();

echo "\n" . str_repeat('=', 74) . "\n";
printf("Mailprobe: %d Pruefungen, %d Befunde\n", $GEPRUEFT, count($BEFUNDE));
foreach ($BEFUNDE as $b) { echo "  - " . $b . "\n"; }
exit($BEFUNDE === [] ? 0 : 1);
