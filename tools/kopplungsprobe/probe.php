<?php
declare(strict_types=1);

/**
 * Kopplungsprobe — tut `pair.php` mit seinen vier Anliegen das, was der
 * JSON-Vertrag 1a/1b zusagt? (S5, Paket A; Konzept Abschnitt 10.2)
 *
 * WOFUER. Die Kopplung ist der eine Weg, auf dem ein Geraet OHNE Anmeldung
 * Zugangsdaten zu einem Konto bekommt. Seit Web 13.0.0 laeuft sie umgekehrt:
 * Das Geraet zeigt den Code, ein Mensch tippt ihn im Web, das Geraet sagt Ja.
 * Jeder der drei Schritte hat Zustaende, Fristen und Fehlerzweige — und die
 * Fehlerzweige muessen sich in Laenge, Aufbau und DAUER gleichen, sonst
 * beantwortet die Antwort die Frage, welche Kennungen es gibt.
 *
 * UEBER ECHTES HTTP, nicht ueber Funktionsaufrufe (Muster tools/ingestprobe/):
 * Geprueft wird ein ENDPUNKT — Kopfzeilen, Statuscodes, Ruempfe, Antwortzeit.
 * Nur was ueber HTTP nicht erreichbar ist, laeuft gegen die Bibliothek:
 * die Dublettenschleife (den Zufall kann man ueber die Leitung nicht patchen),
 * das Beanspruchen (die Weboberflaeche dazu ist Paket B), die Maskierung,
 * der Aufraeumjob.
 *
 * SIE LEGT IHRE EIGENEN KONTEN AN und raeumt am Ende ab — auch die
 * Ratenschutz-Toepfe der Kopplung (pair, pair_start, pair_code) fuer die
 * Adresse 127.0.0.1, denn sie fuellt sie absichtlich. Die Jobs sind waehrend
 * des Laufs angehalten. Der Aufraeumjob (Fall 28) laeuft trotzdem einmal, von
 * Hand — er entsorgt dabei auch, was sonst im Papierkorb faellig ist.
 *
 * WAS SIE NICHT PRUEFT, und warum:
 *   - den TEXT der Kopplungsmail — es gibt keinen Mailserver im Pruefstand.
 *     Belegt wird nur, dass der Versandweg NACH der Antwort betreten wird
 *     (Protokollzeile des PHP-Servers, Fall 27). Der Text steht im
 *     Pruefdokument zur Sichtpruefung.
 *   - die Code-Eingabe im Web (Fall 24, Topf pair_code am Formular) — die
 *     entsteht in Paket B; hier laeuft nur der Topf selbst (Fall 23).
 *   - die Migration als Lauf — sie ist von Hand gefahren (Konzept 9,
 *     Umsetzungsstand); hier wird nur der Zustand danach gelesen (Fall 29).
 *
 * Aufruf:
 *   php tools/kopplungsprobe/probe.php [basisadresse] [protokoll]
 *   (Vorgabe: http://127.0.0.1:8080 und /tmp/php-server.log)
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 2) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/ratelimit_lib.php';
require_once $wurzel . '/kopplung_lib.php';
require_once $wurzel . '/spur_lib.php';
require_once $wurzel . '/jobs_lib.php';

$basis     = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$protokoll = $argv[2] ?? '/tmp/php-server.log';
$pdo       = db();

$erwartungen = 0; $offen = 0; $uebergangen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-60s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}
function uebergehe(string $was, string $grund): void {
    global $uebergangen;
    $uebergangen++;
    printf("  [ -- ] %-60s %s\n", $was, 'uebergangen: ' . $grund);
}

/* ---- HTTP -------------------------------------------------------------- */

/** Eine Anfrage an pair.php, wie das Geraet sie stellt. Misst die Dauer. */
function anfrage(?array $koerper, ?string $devId = null, ?string $key = null,
                 string $methode = 'POST', string $datei = 'pair.php'): array {
    global $basis;
    $kopf = ['Content-Type: application/json'];
    if ($devId !== null) { $kopf[] = 'X-Device-Id: ' . $devId; }
    if ($key   !== null) { $kopf[] = 'X-Api-Key: '   . $key; }
    $ch = curl_init("$basis/$datei");
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $methode,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $kopf,
        CURLOPT_POSTFIELDS     => $koerper === null ? '' : json_encode($koerper),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_PROXY          => '',
    ]);
    $t0   = microtime(true);
    $roh  = (string)curl_exec($ch);
    $dauer = microtime(true) - $t0;
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $d = json_decode($roh, true);
    return ['code' => $code, 'daten' => is_array($d) ? $d : [], 'roh' => $roh, 'dauer' => $dauer];
}

/** `start` mit Block; merkt sich die Sitzung fuer das Aufraeumen. */
$angelegt = [];   // device_ids aller Sitzungen, die diese Probe angelegt hat
function start(mixed $geraet = null): array {
    global $angelegt;
    $k = ['aktion' => 'start'];
    if ($geraet !== null) { $k['geraet'] = $geraet; }
    $a = anfrage($k);
    if (isset($a['daten']['device_id'])) { $angelegt[] = (string)$a['daten']['device_id']; }
    return $a;
}

/* ---- Datenbank-Handgriffe ---------------------------------------------- */

function sitzung(PDO $pdo, string $devId): ?array {
    $st = $pdo->prepare('SELECT * FROM pair_sessions WHERE device_id = ?');
    $st->execute([$devId]);
    $z = $st->fetch();
    return $z === false ? null : $z;
}
function geraet(PDO $pdo, string $devId): ?array {
    $st = $pdo->prepare('SELECT * FROM devices WHERE device_id = ?');
    $st->execute([$devId]);
    $z = $st->fetch();
    return $z === false ? null : $z;
}
function toepfe_leeren(PDO $pdo): void {
    $pdo->exec("DELETE FROM rate_limits WHERE topf IN ('pair', 'pair_start', 'pair_code')");
}
function versuche(PDO $pdo, string $topf): int {
    $st = $pdo->prepare("SELECT COALESCE(SUM(versuche), 0) FROM rate_limits WHERE topf = ? AND merkmal = 'ip:127.0.0.1'");
    $st->execute([$topf]);
    return (int)$st->fetchColumn();
}
/** Sitzung per SQL um elf Minuten altern lassen (Frist: zehn). */
function altern(PDO $pdo, string $devId): void {
    $pdo->prepare('UPDATE pair_sessions SET erstellt_am = DATE_SUB(NOW(), INTERVAL 11 MINUTE)
                   WHERE device_id = ?')->execute([$devId]);
}

/* ---- Konten ------------------------------------------------------------ */

$email  = 'kopplungsprobe@gen-em.org';
$email2 = 'kopplungsprobe-zwei@gen-em.org';
$pdo->prepare('DELETE FROM users WHERE email IN (?, ?)')->execute([$email, $email2]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Kopplungsprobe', 'user', '', '', 320000)")->execute([$email]);
$uid = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Kopplungsprobe zwei', 'user', '', '', 320000)")->execute([$email2]);
$uid2 = (int)$pdo->lastInsertId();

echo "Kopplungsprobe gegen $basis\n";
echo "  Konten $email (uid $uid), $email2 (uid $uid2)\n";
echo "  PAIR_TTL_MIN=" . PAIR_TTL_MIN . ", PAIR_SITZUNGEN_MAX=" . PAIR_SITZUNGEN_MAX
   . ", Toepfe pair " . RATE_GRENZEN['pair']['max'] . ", pair_start " . RATE_GRENZEN['pair_start']['max']
   . ", pair_code " . RATE_GRENZEN['pair_code']['max'] . "\n";

jobs_pause(900);
toepfe_leeren($pdo);
$geraeteLimit = [];   // device_ids der Hilfsgeraete fuer Fall 18
$lastIds      = [];   // ids der 1000 Sitzungen fuer Fall 22

try {

/* ======================================================================
 * Teil 1 — start (Faelle 1 bis 6, 33)
 * ====================================================================== */
echo "\n  Teil 1 — start: Sitzung anlegen\n";

$g = anfrage(null, null, null, 'GET');
pruefe($g['code'] === 405 && ($g['daten']['error'] ?? '') === 'method',
       '33  GET -> 405 method', "HTTP {$g['code']} {$g['roh']}");

$a = start();
$s1 = $a['daten'];
pruefe($a['code'] === 200, '1   start ohne Block -> 200', "HTTP {$a['code']}");
pruefe(preg_match(PAIR_RE, (string)($s1['code'] ?? '')) === 1,
       '1   Code nach PAIR_RE', (string)($s1['code'] ?? '?'));
pruefe(strlen((string)($s1['device_id'] ?? '')) === 36 && str_starts_with((string)($s1['device_id'] ?? ''), 'dev-'),
       '1   device_id: dev- + 32 Hex = 36 Zeichen', (string)($s1['device_id'] ?? '?'));
pruefe(preg_match('/^[0-9a-f]{48}$/', (string)($s1['api_key'] ?? '')) === 1,
       '1   api_key: 48 Hexzeichen');
pruefe(($s1['frist_s'] ?? 0) === 600, '1   frist_s = 600', (string)($s1['frist_s'] ?? '?'));
$z = sitzung($pdo, (string)$s1['device_id']);
pruefe($z !== null && $z['geraet_art'] === null && $z['geraet_modell'] === null
       && $z['geraet_teil'] === null && $z['user_id'] === null,
       '1   Zeile in pair_sessions mit drei NULL-Werten und user_id NULL');
pruefe($z !== null && $z['api_key_hash'] === hash('sha256', (string)$s1['api_key']),
       '1   api_key_hash ist SHA-256 des Schluessels (E-S5-41)');

$b = start(['art' => 'uhr', 'teil' => '006-B4261-00', 'br' => 390, 'ho' => 390]);
$s2 = $b['daten'];
$z = sitzung($pdo, (string)($s2['device_id'] ?? ''));
pruefe($b['code'] === 200 && $z !== null && $z['geraet_art'] === 'uhr'
       && $z['geraet_modell'] === 'Venu 3S' && $z['geraet_teil'] === '006-B4261-00',
       '2   start Uhr-Form -> uhr / Venu 3S / 006-B4261-00',
       $z ? "{$z['geraet_art']} / {$z['geraet_modell']} / {$z['geraet_teil']}" : 'keine Zeile');

$c = start(['art' => 'handy', 'hersteller' => 'Google', 'modell' => 'Pixel 8']);
$s3 = $c['daten'];
$z = sitzung($pdo, (string)($s3['device_id'] ?? ''));
pruefe($c['code'] === 200 && $z !== null && $z['geraet_art'] === 'handy'
       && $z['geraet_modell'] === 'Google Pixel 8',
       '3   start Handy-Form -> handy / Google Pixel 8',
       $z ? "{$z['geraet_art']} / {$z['geraet_modell']}" : 'keine Zeile');

$d = start(7);
$z = sitzung($pdo, (string)($d['daten']['device_id'] ?? ''));
pruefe($d['code'] === 200 && $z !== null && $z['geraet_art'] === null && $z['geraet_modell'] === null,
       '4   start mit Zahl als Block -> 200, NULL-Werte');
$d2 = start('Unsinn');
$z = sitzung($pdo, (string)($d2['daten']['device_id'] ?? ''));
pruefe($d2['code'] === 200 && $z !== null && $z['geraet_art'] === null,
       '4   start mit Zeichenkette als Block -> 200, NULL-Werte');

$e = anfrage(['geraet' => ['art' => 'uhr']]);
pruefe($e['code'] === 400 && ($e['daten']['error'] ?? '') === 'aktion'
       && ($e['daten']['meldung'] ?? '') === 'Uhr-App aktualisieren',
       '5   Rumpf ohne aktion -> 400 aktion + Meldung', $e['roh']);
pruefe(mb_strlen((string)($e['daten']['meldung'] ?? '')) === 21,
       '5   Meldung hat 21 Zeichen (< ZEILE_MAX 26)');
$f = anfrage(['code' => 'AB3K7Q']);
pruefe($f['code'] === 400 && ($f['daten']['error'] ?? '') === 'aktion',
       '6   alte Uhr {"code":...} -> 400 aktion', $f['roh']);
pruefe(versuche($pdo, 'pair') === 0, '5/6 zaehlen NICHT im Topf pair (E-S5-17)',
       'versuche ' . versuche($pdo, 'pair'));

/* ======================================================================
 * Teil 2 — status und bestaetigen am Weg zum Geraet (7 bis 16, 31, 32, 26)
 * ====================================================================== */
echo "\n  Teil 2 — status, beanspruchen, bestaetigen\n";
$dev = (string)$s2['device_id']; $key = (string)$s2['api_key']; $code = (string)$s2['code'];

$st = anfrage(['aktion' => 'status'], $dev, $key);
$rest = (int)($st['daten']['rest_s'] ?? -1);
pruefe($st['code'] === 200 && ($st['daten']['zustand'] ?? '') === 'offen' && $rest <= 600 && $rest > 590,
       '7   status offen, rest_s in (590, 600]', "zustand {$st['daten']['zustand']}, rest_s $rest");

$o = anfrage(['aktion' => 'status']);
pruefe($o['code'] === 401 && ($o['daten']['error'] ?? '') === 'auth',
       '31  status ohne Kopfzeilen -> 401 auth', $o['roh']);
$o2 = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'ja']);
pruefe($o2['code'] === 401, '31  bestaetigen ohne Kopfzeilen -> 401');

$p = anfrage(['aktion' => 'bestaetigen'], $dev, $key);
pruefe($p['code'] === 400 && ($p['daten']['error'] ?? '') === 'payload',
       '32  bestaetigen ohne antwort -> 400 payload', $p['roh']);
$p2 = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'vielleicht'], $dev, $key);
pruefe($p2['code'] === 400 && ($p2['daten']['error'] ?? '') === 'payload',
       '32  bestaetigen "vielleicht" -> 400 payload');

$j = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'ja'], $dev, $key);
pruefe($j['code'] === 409 && ($j['daten']['error'] ?? '') === 'nicht_beansprucht' && sitzung($pdo, $dev) !== null,
       '10  ja im Zustand offen -> 409 nicht_beansprucht, Sitzung bleibt', $j['roh']);

pruefe(pair_sitzung_beanspruchen($pdo, $code, $uid) === true,
       '8   Beanspruchen (Bibliothek, Muster des Web-Klicks) -> rowCount 1');
$st = anfrage(['aktion' => 'status'], $dev, $key);
pruefe($st['code'] === 200 && ($st['daten']['zustand'] ?? '') === 'beansprucht'
       && ($st['daten']['konto'] ?? '') === 'ko***@gen-em.org' && ($st['daten']['rest_s'] ?? 0) > 0,
       '8   status beansprucht, konto maskiert (E-S5-21)', $st['roh']);
pruefe(pair_sitzung_beanspruchen($pdo, $code, $uid2) === false,
       '9   zweite Beanspruchung desselben Codes -> rowCount 0');
pruefe(pair_sitzung_nach_code($pdo, $code) === null,
       '9   nach Code ist die beanspruchte Sitzung nicht mehr zu finden');

$paket = ['kind' => 'mission', 'client_ref' => 'kopplungsprobe-1', 'day' => '2026-03-01',
          'started_at' => '2026-03-01T06:00:00Z', 'ended_at' => '2026-03-01T07:00:00Z',
          'final' => true,
          'track' => ['seq_from' => 0, 'points' => [[47.0, 11.0, 700.0, 1750000000],
                                                    [47.001, 11.001, 701.0, 1750000010]]]];
$i1 = anfrage($paket, $dev, $key, 'POST', 'ingest.php');
pruefe($i1['code'] === 401, '14a ingest.php mit schwebenden Zugangsdaten -> 401', "HTTP {$i1['code']}");

$logVorher = is_file($protokoll) ? filesize($protokoll) : null;
$j = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'ja'], $dev, $key);
pruefe($j['code'] === 200 && ($j['daten']['ok'] ?? false) === true,
       '11  ja beansprucht -> 200 ok', $j['roh']);
$gz = geraet($pdo, $dev);
pruefe($gz !== null && (int)$gz['user_id'] === $uid && $gz['api_key_hash'] === hash('sha256', $key)
       && $gz['label'] === 'Uhr' && $gz['geraet_art'] === 'uhr' && $gz['geraet_modell'] === 'Venu 3S'
       && $gz['geraet_teil'] === '006-B4261-00' && (int)$gz['active'] === 1,
       '11  devices-Zeile: Konto, SHA-256-Hash, Vorgabename "Uhr", drei Geraetewerte, aktiv',
       $gz ? "label {$gz['label']}, art {$gz['geraet_art']}, modell {$gz['geraet_modell']}" : 'keine Zeile');
pruefe(sitzung($pdo, $dev) === null, '11  Sitzung ist weg');

if ($logVorher !== null) {
    usleep(300000);
    $neu = (string)file_get_contents($protokoll, false, null, $logVorher);
    pruefe(str_contains($neu, 'SMTP'),
           '27  Versandweg nach der Antwort betreten (Protokollzeile SMTP)',
           trim(substr($neu, 0, 90)));
} else {
    uebergehe('27  Versandweg (Protokollzeile)', "kein Protokoll unter $protokoll");
}

$j2 = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'ja'], $dev, $key);
pruefe($j2['code'] === 200 && ($j2['daten']['ok'] ?? false) === true,
       '12  ja wiederholt -> 200 ok (Idempotenz, E-S5-15)', $j2['roh']);
$st = anfrage(['aktion' => 'status'], $dev, $key);
pruefe($st['code'] === 200 && ($st['daten']['zustand'] ?? '') === 'gekoppelt',
       '13  status nach Anlage -> gekoppelt', $st['roh']);
$n = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'nein'], $dev, $key);
pruefe($n['code'] === 200 && geraet($pdo, $dev) !== null,
       'E48 nein mit Geraetezugang -> 200, Geraet bleibt (E-S5-48)');
$i2 = anfrage($paket, $dev, $key, 'POST', 'ingest.php');
pruefe($i2['code'] === 200 && ($i2['daten']['ok'] ?? false) === true,
       '14b ingest.php nach dem Ja -> 200', "HTTP {$i2['code']}");

/* Vor dem Trennen zwei Fehlversuche setzen: Der Topf `pair` gehoert nicht
 * uns allein — jobs.php und gpx.php zaehlen darin mit. Ein gelungenes
 * `trennen` darf ihn deshalb NICHT leeren (Web 13.1.1). */
anfrage(['aktion' => 'status'], $dev, 'falsch-vor-trennen-1');
anfrage(['aktion' => 'status'], $dev, 'falsch-vor-trennen-2');
$vorTrennen = versuche($pdo, 'pair');

$t = anfrage(['aktion' => 'trennen'], $dev, $key);
pruefe($t['code'] === 200 && ($t['daten']['ok'] ?? false) === true && geraet($pdo, $dev) === null,
       '26  trennen -> 200, Geraet weg (R47 unveraendert)', $t['roh']);
pruefe($vorTrennen >= 2 && versuche($pdo, 'pair') === $vorTrennen,
       'E51 ein gelungenes trennen leert den Topf `pair` NICHT',
       "vorher $vorTrennen, nachher " . versuche($pdo, 'pair')
       . ' — der Topf gehoert auch jobs.php und gpx.php');
$t2 = anfrage(['aktion' => 'trennen'], $dev, $key);
pruefe($t2['code'] === 401, '26  trennen danach -> 401 (Kennung unbekannt)');
toepfe_leeren($pdo);

$s4 = start()['daten'];
$n = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'nein'], $s4['device_id'], $s4['api_key']);
pruefe($n['code'] === 200 && sitzung($pdo, (string)$s4['device_id']) === null,
       '15  nein im Zustand offen -> 200, Sitzung weg', $n['roh']);

$s4b = start()['daten'];
$tr = anfrage(['aktion' => 'trennen'], $s4b['device_id'], $s4b['api_key']);
pruefe($tr['code'] === 200 && sitzung($pdo, (string)$s4b['device_id']) === null,
       'E49 trennen mit schwebenden Zugangsdaten wirkt wie nein (E-S5-49)', $tr['roh']);

$s5 = start(['art' => 'uhr', 'teil' => '006-B4261-00'])['daten'];
pair_sitzung_beanspruchen($pdo, (string)$s5['code'], $uid);
$n = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'nein'], $s5['device_id'], $s5['api_key']);
pruefe($n['code'] === 200 && sitzung($pdo, (string)$s5['device_id']) === null
       && geraet($pdo, (string)$s5['device_id']) === null,
       '16  nein beansprucht -> 200, Sitzung weg, KEIN Geraet');
$n2 = anfrage(['aktion' => 'status'], $s5['device_id'], $s5['api_key']);
pruefe($n2['code'] === 401, '16  status nach dem Nein -> 401 (V-S5-09: verworfen = unbekannt)');
toepfe_leeren($pdo);

/* ======================================================================
 * Teil 3 — Frist (17)
 * ====================================================================== */
echo "\n  Teil 3 — Frist\n";
$s6 = start()['daten'];
altern($pdo, (string)$s6['device_id']);
$st = anfrage(['aktion' => 'status'], $s6['device_id'], $s6['api_key']);
pruefe($st['code'] === 410 && ($st['daten']['error'] ?? '') === 'abgelaufen',
       '17  status nach elf Minuten -> 410 abgelaufen', $st['roh']);
pruefe($st['dauer'] < 0.30, '17  ... ohne Verzoegerung (E-S5-31)', sprintf('%.3f s', $st['dauer']));
$j = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'ja'], $s6['device_id'], $s6['api_key']);
pruefe($j['code'] === 410, '17  ja nach elf Minuten -> 410');
pruefe(pair_sitzung_beanspruchen($pdo, (string)$s6['code'], $uid) === false,
       '17  Beanspruchen nach elf Minuten -> rowCount 0');
pruefe(sitzung($pdo, (string)$s6['device_id']) !== null,
       '17  die verfallene Zeile bleibt bis zum Aufraeumen liegen (E-S5-11)');
pruefe(versuche($pdo, 'pair') === 0, '17  410 zaehlt nicht im Topf pair');
$n = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'nein'], $s6['device_id'], $s6['api_key']);
pruefe($n['code'] === 200 && sitzung($pdo, (string)$s6['device_id']) === null,
       '17  nein auf eine verfallene Sitzung -> 200, Zeile weg');

/* ======================================================================
 * Teil 4 — Geraetelimit beim Ja (18)
 * ====================================================================== */
echo "\n  Teil 4 — Geraetelimit\n";
$s7 = start(['art' => 'uhr', 'teil' => '006-B4261-00'])['daten'];
pair_sitzung_beanspruchen($pdo, (string)$s7['code'], $uid);
for ($i = 1; $i <= MAX_GERAETE; $i++) {
    $gid = 'dev-kopplungsprobe-limit-' . $i;
    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label) VALUES (?,?,?,?)')
        ->execute([$uid, $gid, str_repeat('0', 64), 'Limit ' . $i]);
    $geraeteLimit[] = $gid;
}
$j = anfrage(['aktion' => 'bestaetigen', 'antwort' => 'ja'], $s7['device_id'], $s7['api_key']);
pruefe($j['code'] === 409 && ($j['daten']['error'] ?? '') === 'device_limit'
       && str_contains((string)($j['daten']['meldung'] ?? ''), (string)MAX_GERAETE),
       '18  ja bei ' . MAX_GERAETE . ' Geraeten -> 409 device_limit mit Meldung', $j['roh']);
pruefe(sitzung($pdo, (string)$s7['device_id']) === null && geraet($pdo, (string)$s7['device_id']) === null,
       '18  Sitzung weg, kein Geraet');
pruefe(versuche($pdo, 'pair') === 0, '18  409 zaehlt nicht im Topf pair');
$pdo->prepare("DELETE FROM devices WHERE device_id LIKE 'dev-kopplungsprobe-limit-%'")->execute();
$geraeteLimit = [];

/* ======================================================================
 * Teil 5 — Antwortgleichheit und Toepfe (19 bis 23)
 * ====================================================================== */
echo "\n  Teil 5 — Antwortgleichheit und Ratenschutz\n";
toepfe_leeren($pdo);
$s8 = start()['daten'];
$u1 = anfrage(['aktion' => 'status'], 'dev-gibt-es-nicht-' . bin2hex(random_bytes(8)), 'falsch');
$u2 = anfrage(['aktion' => 'status'], $s8['device_id'], 'falscherschluessel');
pruefe($u1['code'] === 401 && $u2['code'] === 401 && $u1['roh'] === $u2['roh'],
       '19  unbekannte Kennung und falscher Schluessel: 401, Ruempfe byteweise gleich',
       $u1['roh'] . ' | ' . $u2['roh']);
pruefe($u1['dauer'] >= 0.35 && $u2['dauer'] >= 0.35,
       '19  beide >= 0,35 s', sprintf('%.3f s / %.3f s', $u1['dauer'], $u2['dauer']));
pruefe(versuche($pdo, 'pair') === 2, '19  beide zaehlen im Topf pair', 'versuche ' . versuche($pdo, 'pair'));
$u3 = anfrage(['aktion' => 'status'], $s8['device_id'], $s8['api_key']);
pruefe($u3['code'] === 200 && ($u3['daten']['zustand'] ?? '') === 'offen',
       '19  status mit richtigen Daten dazwischen -> 200 offen');
pruefe(versuche($pdo, 'pair') === 2, '19  ... und leert den Topf NICHT (kein rate_erfolg an status)',
       'versuche ' . versuche($pdo, 'pair'));
for ($i = 3; $i <= RATE_GRENZEN['pair']['max']; $i++) {
    anfrage(['aktion' => 'status'], $s8['device_id'], 'falsch-' . $i);
}
$g1 = anfrage(['aktion' => 'status'], $s8['device_id'], $s8['api_key']);
pruefe($g1['code'] === 429 && ($g1['daten']['error'] ?? '') === 'zu_viele_versuche',
       '20  nach ' . RATE_GRENZEN['pair']['max'] . ' x 401 -> 429, auch mit richtigen Daten', $g1['roh']);
$g2 = anfrage(['aktion' => 'trennen'], $s8['device_id'], $s8['api_key']);
pruefe($g2['code'] === 429, '20  ... gilt fuer alle kopfzeilen-ausgewiesenen Anliegen (trennen)');
toepfe_leeren($pdo);
$g3 = anfrage(['aktion' => 'status'], $s8['device_id'], $s8['api_key']);
pruefe($g3['code'] === 200, '20  nach dem Leeren des Topfes wieder 200');

toepfe_leeren($pdo);
$vorherOffen = pair_sitzungen_offen($pdo);
$alle200 = true;
for ($i = 1; $i <= RATE_GRENZEN['pair_start']['max']; $i++) {
    if (start()['code'] !== 200) { $alle200 = false; }
}
$g4 = start();
pruefe($alle200 && $g4['code'] === 429 && ($g4['daten']['error'] ?? '') === 'zu_viele_versuche',
       '21  ' . RATE_GRENZEN['pair_start']['max'] . ' x start -> 200, der naechste -> 429 zu_viele_versuche',
       $g4['roh']);
pruefe(pair_sitzungen_offen($pdo) === $vorherOffen + RATE_GRENZEN['pair_start']['max'],
       '21  die abgewiesene Anfrage hat KEINE Sitzung angelegt');
toepfe_leeren($pdo);

// 22 — Obergrenze
$n = strlen(PAIR_CHARS);
$eingefuegt = 0; $i = 0;
$ins = $pdo->prepare('INSERT INTO pair_sessions (code, device_id, api_key_hash) VALUES (?,?,?)');
while ($eingefuegt < PAIR_SITZUNGEN_MAX) {
    $i++;
    // Codes aus einer Schleife, nicht aus Zufall (V-S5-10): ZZ + vier Stellen zur Basis 32
    $c = 'ZZ'; $x = $i;
    for ($k = 0; $k < 4; $k++) { $c .= PAIR_CHARS[$x % $n]; $x = intdiv($x, $n); }
    try {
        $ins->execute([$c, 'dev-kopplungsprobe-last-' . $i, str_repeat('0', 64)]);
        $lastIds[] = (int)$pdo->lastInsertId();
        $eingefuegt++;
    } catch (PDOException $ex) {
        if (!ist_dublettenfehler($ex)) { throw $ex; }
    }
}
$v = start();
pruefe($v['code'] === 429 && ($v['daten']['error'] ?? '') === 'zu_viele_sitzungen',
       '22  ' . PAIR_SITZUNGEN_MAX . ' unverfallene Sitzungen -> start 429 zu_viele_sitzungen', $v['roh']);
$pdo->exec("UPDATE pair_sessions SET erstellt_am = DATE_SUB(NOW(), INTERVAL 11 MINUTE)
            WHERE device_id LIKE 'dev-kopplungsprobe-last-%'");
$v2 = start();
pruefe($v2['code'] === 200, '22  dieselben 1000 Zeilen verfallen -> start 200 (E-S5-14)', "HTTP {$v2['code']}");
$pdo->exec("DELETE FROM pair_sessions WHERE device_id LIKE 'dev-kopplungsprobe-last-%'");
$lastIds = [];
toepfe_leeren($pdo);

// 23 — Topf pair_code (Bibliothek; das Formular dazu ist Paket B)
for ($i = 1; $i <= RATE_GRENZEN['pair_code']['max']; $i++) { rate_misserfolg('pair_code', $email); }
pruefe(rate_erlaubt('pair_code', $email) === false,
       '23  ' . RATE_GRENZEN['pair_code']['max'] . ' Fehlgriffe im Topf pair_code -> gesperrt (Konto und IP)');
pruefe(rate_gesperrt_bis('pair_code', $email) !== null, '23  rate_gesperrt_bis liefert eine Zeit');
rate_erfolg('pair_code', $email);
pruefe(rate_erlaubt('pair_code', $email) === true, '23  ein Treffer (rate_erfolg) leert den Topf');
toepfe_leeren($pdo);

// 24 — Formatfehler: die Regel, auf die Paket B sich stuetzt
pruefe(preg_match(PAIR_RE, 'AB0K7Q') === 0 && preg_match(PAIR_RE, 'ABOK7Q') === 0
       && preg_match(PAIR_RE, 'AB1K7Q') === 0 && preg_match(PAIR_RE, 'ABIK7Q') === 0
       && preg_match(PAIR_RE, 'AB3K7') === 0,
       '24  PAIR_RE lehnt 0, O, 1, I und fuenf Zeichen ab (Formatfehler zaehlt nicht: Paket B)');
pruefe(pair_code_normalisieren('ab3 k7q') === 'AB3K7Q' && pair_code_normalisieren(' AB3-K7Q ') === 'AB3K7Q',
       '24  pair_code_normalisieren: Leerzeichen, Bindestrich, Kleinschreibung');

/* ======================================================================
 * Teil 6 — Bibliothek, Job, Migration, Kaskade (25, 28, 29, 30, 34)
 * ====================================================================== */
echo "\n  Teil 6 — Bibliothek, Aufraeumjob, Migration, Kaskade\n";
$belegt = (string)$s8['code'];
$folge = [$belegt, 'PROBE2'];
$k = 0;
$codeNeu = pair_sitzung_anlegen($pdo, 'dev-kopplungsprobe-dublette', str_repeat('0', 64),
                                ['art' => null, 'modell' => null, 'teil' => null],
                                function () use (&$folge, &$k): string { return $folge[$k++]; });
pruefe($codeNeu === 'PROBE2' && $k === 2,
       '25  Dublette am Code -> zweiter Versuch gewinnt', "Code $codeNeu nach $k Versuchen");
$pdo->exec("DELETE FROM pair_sessions WHERE device_id = 'dev-kopplungsprobe-dublette'");
try {
    pair_sitzung_anlegen($pdo, 'dev-kopplungsprobe-dublette', str_repeat('0', 64),
                         ['art' => null, 'modell' => null, 'teil' => null],
                         fn(): string => $belegt);
    pruefe(false, '25  fuenf Dubletten -> RuntimeException');
} catch (RuntimeException $ex) {
    pruefe(true, '25  fuenf Dubletten -> RuntimeException', $ex->getMessage());
}

pruefe(email_maskieren('philipp@gen-em.org') === 'ph***@gen-em.org'
       && email_maskieren('a@b.de') === 'a***@b.de'
       && email_maskieren('Philipp@Gen-EM.org') === 'ph***@gen-em.org'
       && email_maskieren('ohne-at') === 'oh***',
       '30  email_maskieren: zwei Zeichen, ***, Domain; ein Zeichen; klein; ohne @');

$s9  = start()['daten'];
$s10 = start()['daten'];
altern($pdo, (string)$s9['device_id']);
job_aufraeumen($pdo, [], fn(): int => 100);
pruefe(sitzung($pdo, (string)$s9['device_id']) === null && sitzung($pdo, (string)$s10['device_id']) !== null,
       '28  Job aufraeumen: verfallene Sitzung weg, unverfallene bleibt');

$reg = $pdo->query("SELECT status FROM schema_migrations WHERE id = '2026_09_03_kopplungssitzungen'")->fetchColumn();
$tab = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()
                    AND table_name IN ('pair_sessions', 'pair_codes')")->fetchAll(PDO::FETCH_COLUMN);
pruefe($reg !== false && in_array('pair_sessions', $tab, true) && !in_array('pair_codes', $tab, true),
       '29  Register kennt die Migration, pair_sessions da, pair_codes weg',
       'status ' . var_export($reg, true) . ', Tabellen ' . implode(',', $tab));
$zahl = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
pruefe($zahl === 41, '29  Migrationsregister: 41 Kennungen (Z-19)', (string)$zahl);

$s11 = start()['daten'];
pair_sitzung_beanspruchen($pdo, (string)$s11['code'], $uid2);
$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid2]);
pruefe(sitzung($pdo, (string)$s11['device_id']) === null,
       '34  Kontoloeschung mit beanspruchter Sitzung -> Sitzung weg (FK CASCADE)');
$uid2 = 0;

} finally {
    jobs_pause(0);
    toepfe_leeren($pdo);
    if ($lastIds) { $pdo->exec("DELETE FROM pair_sessions WHERE device_id LIKE 'dev-kopplungsprobe-last-%'"); }
    $pdo->exec("DELETE FROM pair_sessions WHERE device_id LIKE 'dev-kopplungsprobe-%'");
    $pdo->exec("DELETE FROM devices WHERE device_id LIKE 'dev-kopplungsprobe-%'");
    if ($angelegt) {
        $del = $pdo->prepare('DELETE FROM pair_sessions WHERE device_id = ?');
        foreach (array_unique($angelegt) as $d) { $del->execute([$d]); }
    }
    foreach ([$uid, $uid2] as $u) {
        if ($u <= 0) { continue; }
        $ids = $pdo->prepare('SELECT id FROM missions WHERE user_id = ?');
        $ids->execute([$u]);
        $mIds = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
        if ($mIds) { spur_loeschen($pdo, 'mission', $mIds); }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$u]);
    }
    echo "\n  Konten, Sitzungen, Geraete und Toepfe der Probe wieder entfernt.\n";
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt, %d uebergangen\n", $erwartungen, $offen, $uebergangen);
exit($offen === 0 ? 0 : 1);
