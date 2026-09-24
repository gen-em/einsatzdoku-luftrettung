<?php
declare(strict_types=1);

/**
 * Verbindungsprobe — was tut die Anwendung, wenn die Datenbank keine
 * Verbindung mehr annimmt? (P5a/AP9, E-P5a-18)
 *
 * WOFUER. MySQL/MariaDB weist eine Verbindung mit **1040** ab, wenn der ganze
 * Server voll ist, und mit **1203**, wenn dieses eine Datenbankkonto seine
 * `max_user_connections` ausgeschoepft hat. Auf einem geteilten Webspace ist
 * die zweite Grenze die naheliegende — sie liegt regelmaessig bei 10 bis 30.
 *
 * Bis P5a/AP9 kam in diesem Fall eine **500** heraus, mit dem ungefilterten
 * Text der PDO-Ausnahme. Darin stehen Hostname und Benutzername der
 * Datenbank. Und fuer die Uhr ist eine 500 etwas anderes als eine 503: Der
 * JSON-Vertrag (Abschnitt 5) sagt zu 5xx „spaeter unveraendert erneut" — das
 * gilt fuer beide —, aber die 503 traegt zusaetzlich `Retry-After` und sagt
 * damit, dass es kein Defekt ist.
 *
 * DIESE PROBE STELLT DIE LAGE HER, statt sie abzuwarten. Sie setzt
 * `max_user_connections` des Anwendungskontos auf einen kleinen Wert, haelt
 * selbst so viele Verbindungen offen, dass nichts mehr frei ist, und misst
 * dann, was ueber echtes HTTP herauskommt.
 *
 * SIE VERAENDERT EINE BERECHTIGUNG IN DER DATENBANK. Deshalb der Riegel
 * unten: nur gegen 127.0.0.1, nur mit Wurzelzugang ueber den Unix-Socket,
 * und der Ausgangswert wird gemerkt und im `finally` zurueckgeschrieben —
 * auch wenn die Probe mittendrin abbricht.
 *
 * ZWEI TEILE:
 *   Teil 1  Die Antwort. Alles ist belegt; Seite und Geraete-Endpunkt
 *           muessen 503 bekommen, mit `Retry-After: 5`, mit dem richtigen
 *           Rumpf und OHNE ein Wort ueber die Datenbank. Dazu: Der Zaehler
 *           in `server/ueberlast.json` zaehlt genau so viele Vorfaelle, wie
 *           es Abweisungen gab.
 *   Teil 2  Null verlorene Uploads. Ein Teil der Verbindungen bleibt frei,
 *           mehrere Pakete laufen gleichzeitig; was 503 bekommt, wird
 *           wiederholt, wie die Uhr es tut. Am Ende muss JEDES Paket in der
 *           Datenbank stehen.
 *
 * EIGENER WEBSERVER, MIT ARBEITERN. Ohne `PHP_CLI_SERVER_WORKERS` bedient
 * der eingebaute PHP-Server genau eine Anfrage nach der anderen — dann gaebe
 * es keine Gleichzeitigkeit zu messen, und Teil 2 waere eine Behauptung.
 * Die Probe startet deshalb ihren eigenen Server mit mehreren Arbeitern und
 * raeumt ihn wieder weg. Mit `--basis` laesst sich stattdessen eine laufende
 * Installation nehmen; dann sagt die Probe dazu, dass sie die
 * Gleichzeitigkeit nicht garantieren kann.
 *
 * WAS SIE NICHT MISST:
 *   - **Z2-Last.** Das Konzept nennt „gegen Z2-Last" (500 Konten a 600
 *     Einsaetze). Das ist ein Bestand von 300 000 Einsaetzen und ein Tag
 *     Rechenzeit; diese Probe misst das VERHALTEN an der Grenze, nicht das
 *     Verhalten unter Bestandsgroesse. Der Bestand ist Sache des Messstands.
 *   - **1040 und 1203.** Die serverweite Grenze (1040) laesst sich auf einer
 *     Maschine, auf der noch etwas anderes laeuft, nicht gefahrlos
 *     herstellen; die Systemvariable hinter 1203 laesst sich in MariaDB
 *     nicht zur Laufzeit setzen, wenn der Server mit 0 gestartet ist. Der
 *     Code behandelt alle drei Nummern gleich (`UEBERLAST_CODES`); diese
 *     Probe stellt **1226** her, also die GRANT-Grenze des Kontos — und das
 *     ist die, die ein Hoster setzt.
 *   - **Den echten Webspace.** Gemessen wird der eingebaute PHP-Server.
 *
 * Aufruf:
 *   php tools/proben/verbindung/probe.php [--grenze 10] [--pakete 20]
 *                                        [--arbeiter 8] [--basis http://…]
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 3) . '/server';

/* ---- Aufrufparameter ------------------------------------------------------ */
$opt = getopt('', ['grenze:', 'pakete:', 'arbeiter:', 'basis:', 'frei:']);
$grenze   = max(3, (int)($opt['grenze']   ?? 10));
$pakete   = max(1, (int)($opt['pakete']   ?? 20));
$arbeiter = max(1, (int)($opt['arbeiter'] ?? 8));
$basisArg = isset($opt['basis']) ? rtrim((string)$opt['basis'], '/') : null;
/* Wie viele Verbindungen Teil 2 FREI laesst. Weniger als Arbeiter, sonst
 * kommt jede Anfrage durch und Teil 2 misst nur noch das Gedraengel. */
$frei2 = max(1, (int)($opt['frei'] ?? 2));

/* ---- Der Riegel ----------------------------------------------------------- */
$cfg = require $wurzel . '/config.php';
$dsn = (string)$cfg['db']['dsn'];
if (!preg_match('/host=(127\.0\.0\.1|localhost)/', $dsn)
    && !str_contains($dsn, 'unix_socket')) {
    fwrite(STDERR, "Riegel: Diese Probe aendert max_user_connections und darf nur\n"
                 . "gegen eine lokale Datenbank laufen. Gefunden: $dsn\n");
    exit(2);
}
if ($basisArg !== null
    && !preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?$#', $basisArg)) {
    fwrite(STDERR, "Riegel: --basis muss auf 127.0.0.1 zeigen. Gefunden: $basisArg\n");
    exit(2);
}

/* Wurzelzugang ueber den Unix-Socket. Ueber TCP meldet MariaDB heute
 * `Access denied` — die Voreinstellung ist `unix_socket`-Authentisierung. */
$rootPdo = null;
foreach (['/run/mysqld/mysqld.sock', '/var/run/mysqld/mysqld.sock',
          '/tmp/mysql.sock', '/var/lib/mysql/mysql.sock'] as $sock) {
    if (!file_exists($sock)) { continue; }
    try {
        $rootPdo = new PDO('mysql:unix_socket=' . $sock, 'root', '',
                           [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        break;
    } catch (Throwable $e) { /* naechster */ }
}
if ($rootPdo === null) {
    fwrite(STDERR, "Kein Wurzelzugang zur Datenbank ueber den Unix-Socket.\n"
                 . "Ohne ihn laesst sich max_user_connections nicht setzen.\n");
    exit(2);
}

require_once $wurzel . '/db.php';


$pdo = db();

/* ---- Zaehlwerk ------------------------------------------------------------ */
$erfuellt = 0; $offen = 0; $nummer = 0;
function pruefe(bool $ok, string $was, string $zusatz = ''): void {
    global $erfuellt, $offen;
    if ($ok) { $erfuellt++; echo "    [ok]   $was"; }
    else     { $offen++;    echo "    [OFFEN] $was"; }
    if ($zusatz !== '') { echo "  ($zusatz)"; }
    echo "\n";
}

/* ---- Eigener Webserver, wenn keine Basis genannt wurde --------------------- */
$serverPid = null;
$basis = $basisArg;
if ($basis === null) {
    $port = 8123;
    for ($v = 0; $v < 20; $v++) {
        $probe = @fsockopen('127.0.0.1', $port + $v, $e1, $e2, 0.3);
        if ($probe === false) { $port = $port + $v; break; }
        fclose($probe);
    }
    $basis = 'http://127.0.0.1:' . $port;
    $log = sys_get_temp_dir() . '/verbindungsprobe-server.log';
    $cmd = 'PHP_CLI_SERVER_WORKERS=' . $arbeiter . ' php -S 127.0.0.1:' . $port
         . ' -t ' . escapeshellarg($wurzel) . ' > ' . escapeshellarg($log) . ' 2>&1 & echo $!';
    $serverPid = (int)trim((string)shell_exec($cmd));
    for ($v = 0; $v < 100; $v++) {
        usleep(100000);
        $s = @fsockopen('127.0.0.1', $port, $e1, $e2, 0.3);
        if ($s !== false) { fclose($s); break; }
    }
}

echo "Verbindungsprobe gegen $basis\n";
echo "  Grenze $grenze · Arbeiter " . ($serverPid !== null ? $arbeiter : 'unbekannt (fremde Basis)')
   . " · Pakete $pakete\n";

/* ---- Konto und Geraet ----------------------------------------------------- */
$email = 'verbindungsprobe@gen-em.org';
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Verbindungsprobe', 'user', '', '', 320000)")->execute([$email]);
$uid = (int)$pdo->lastInsertId();
$dev = 'dev-verbindungsprobe';
$key = bin2hex(random_bytes(24));
$pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active,
                                    geraet_art, geraet_modell)
               VALUES (?,?,?,?,1,?,?)')
    ->execute([$uid, $dev, geraet_schluessel_hash($key), 'Verbindungsprobe',
               'uhr', 'Forerunner 955']);
/* Die Mengenbremse aus AP7 darf Teil 2 nicht dazwischenfunken: Sie laesst je
 * Kennung 30 Anfragen in 15 Minuten zu, und Teil 2 stellt mehr. */
$pdo->exec("DELETE FROM rate_limits WHERE topf IN ('ingest','ingest_ip')");

/* ---- Ausgangswert merken -------------------------------------------------- */
$st = $rootPdo->prepare("SELECT User AS user, Host AS host, max_user_connections FROM mysql.user
                          WHERE user = ?");
$st->execute([(string)$cfg['db']['user']]);
$vorher = $st->fetchAll(PDO::FETCH_ASSOC);
if ($vorher === []) {
    fwrite(STDERR, "Das Datenbankkonto '{$cfg['db']['user']}' steht nicht in mysql.user.\n");
    exit(2);
}

$zaehlDatei = $wurzel . '/ueberlast.json';
$gehalten = [];

/**
 * Plaetze belegen, BIS KEINER MEHR FREI IST — und melden, wie viele es waren.
 *
 * WARUM NICHT EINFACH `$grenze - 1`. Genau daran ist der erste Entwurf
 * gescheitert, und zwar still: Die Verbindung, die die Probe selbst fuer
 * `db()` haelt, entstand VOR dem `ALTER USER`, und `FLUSH PRIVILEGES` baut
 * die Zaehlstruktur von MariaDB neu auf. Die Rechnung „eigene plus
 * $grenze - 1 = voll" stimmte damit nicht, der Webserver bekam noch einen
 * Platz, und die Probe meldete HTTP 200 statt 503 — ohne dass an der Sache
 * etwas falsch gewesen waere.
 *
 * Wer bis zum Anschlag belegt, braucht die Rechnung nicht. Die Obergrenze
 * ist nur ein Riegel gegen eine Endlosschleife.
 *
 * @return array{belegt:int,fehler:string}
 */
function belegen_bis_voll(array &$gehalten, array $cfg, int $hoechstens): array {
    $fehler = '';
    for ($i = 0; $i < $hoechstens; $i++) {
        try {
            $gehalten[] = new PDO($cfg['db']['dsn'], $cfg['db']['user'],
                                  $cfg['db']['pass'],
                                  [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (Throwable $e) { $fehler = $e->getMessage(); break; }
    }
    return ['belegt' => count($gehalten), 'fehler' => $fehler];
}

/** Eine einzelne Anfrage, mit Kopfzeilen. */
function hole(string $url, array $kopfzeilen = [], ?string $rumpf = null): array {
    $kopf = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => $kopfzeilen,
        CURLOPT_HEADERFUNCTION => static function ($ch, string $z) use (&$kopf): int {
            $t = explode(':', $z, 2);
            if (count($t) === 2) { $kopf[strtolower(trim($t[0]))] = trim($t[1]); }
            return strlen($z);
        },
    ]);
    if ($rumpf !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rumpf);
    }
    $roh  = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'rumpf' => $roh, 'kopf' => $kopf,
            'daten' => json_decode($roh, true)];
}

/** Ein Uhr-Paket mit n Punkten. */
function paket(string $ref, int $n): array {
    $ts = gmdate('Y-m-d\TH:i:00\Z', time() - 3600);
    $punkte = [];
    for ($i = 0; $i < $n; $i++) {
        $punkte[] = [47.0 + $i * 0.0002, 11.0 + $i * 0.0002, 700.0 + $i * 0.5,
                     time() - 3600 + $i * 5];
    }
    return ['kind' => 'mission', 'client_ref' => $ref,
            'day' => substr($ts, 0, 10), 'started_at' => $ts,
            'ended_at' => null, 'final' => false,
            'track' => ['seq_from' => 0, 'points' => $punkte]];
}

try {

$rootPdo->exec("ALTER USER '{$vorher[0]['user']}'@'{$vorher[0]['host']}' "
             . "WITH MAX_USER_CONNECTIONS $grenze");
foreach (array_slice($vorher, 1) as $z) {
    $rootPdo->exec("ALTER USER '{$z['user']}'@'{$z['host']}' "
                 . "WITH MAX_USER_CONNECTIONS $grenze");
}
$rootPdo->exec('FLUSH PRIVILEGES');

/* ======================================================================
 * Teil 1 — die Antwort an der Grenze
 * ====================================================================== */
echo "\n  Teil 1 — alles belegt: die Antwort\n";

@unlink($zaehlDatei);
/* Eine Verbindung haelt die Probe selbst (`$pdo`), die uebrigen werden
 * belegt. Zusammen sind es genau `$grenze` — der Webserver bekommt keine. */
/* WER SONST NOCH AN DIESEM DATENBANKKONTO HAENGT (16.09.2026).
 *
 * Diese Probe rechnet damit, dass sie die einzige ist. Laeuft nebenher ein
 * Job, ein zweiter Webserver oder eine andere Probe auf demselben
 * Datenbankkonto, belegt der Plaetze, die hier nicht mitgezaehlt werden — und
 * dann stimmen die Zahlen in Teil 1 nicht mehr: Der Webserver bekommt
 * womoeglich doch noch eine Verbindung, und „genau drei Abweisungen" wird zu
 * vier oder zwei.
 *
 * GENAU DAS IST EINMAL PASSIERT: einer von dreizehn Laeufen am 16.09.2026
 * meldete 23 von 24, die uebrigen zwoelf 24 von 24. Statt die Probe
 * unzuverlaessig zu nennen, sagt sie jetzt, WER sonst noch da ist. Eine Zahl
 * ueber null ist kein Abbruch — sie ist die Erklaerung, die sonst fehlt. */
$stFremd = $rootPdo->prepare('SELECT COUNT(*) FROM information_schema.processlist
                               WHERE USER = ?');
$stFremd->execute([(string)$cfg['db']['user']]);
$fremdVorher = max(0, (int)$stFremd->fetchColumn() - 1);   // die eigene abziehen
if ($fremdVorher > 0) {
    echo "    ACHTUNG: $fremdVorher weitere Verbindung(en) dieses Datenbankkontos "
       . "sind offen —
             die Zahlen unten koennen dadurch abweichen.
";
}

$b = belegen_bis_voll($gehalten, $cfg, $grenze * 3);
pruefe($b['fehler'] !== '' && $b['belegt'] > 0,
       'Die Grenze laesst sich ausschoepfen',
       $b['belegt'] . ' Verbindungen belegt, dann: '
       . ($b['fehler'] !== '' ? $b['fehler'] : 'kein Fehler — die Grenze greift nicht'));
/* DIE NUMMER GEHOERT IN DEN BERICHT. E-P5a-18 nennt 1040 und 1203; der Fall,
 * den ein Hoster herstellt, ist 1226 (siehe UEBERLAST_CODES). Eine Probe,
 * die nur „es hat geklappt" meldete, haette den Unterschied verschluckt. */
pruefe(preg_match('/\[(1040|1203|1226)\]/', $b['fehler']) === 1,
       '... und meldet eine der drei Nummern aus UEBERLAST_CODES',
       preg_match('/\[(\d+)\]/', $b['fehler'], $mm) === 1 ? 'Fehler ' . $mm[1] : '-');

/* GEMESSEN WIRD `login.php` UND NICHT `index.php`.
 *
 * `index.php` laeuft durch `auth_guard.php`, und das leitet ohne Sitzung in
 * Zeile 34 auf die Anmeldeseite um — VOR dem ersten `db()`. Eine Anfrage,
 * die die Datenbank nie beruehrt, kann von ihrer Ueberlast auch nichts
 * merken; sie antwortet 302, und das ist richtig so. Die Erwartung darunter
 * misst das ausdruecklich mit, damit die Zahl nicht spaeter als Luecke
 * gelesen wird.
 *
 * `login.php` dagegen liest Konto und Rundenzahl und ist damit die erste
 * SEITE, an der eine Ueberlast sichtbar wird — und die, auf der jemand
 * steht, der gerade hereinwill. */
$seite = hole($basis . '/login.php');
pruefe($seite['code'] === 503, 'Eine Seite antwortet 503', 'HTTP ' . $seite['code']);
pruefe(($seite['kopf']['retry-after'] ?? '') === '5',
       '... mit Retry-After: 5', 'Retry-After ' . ($seite['kopf']['retry-after'] ?? '-'));
pruefe(str_contains($seite['rumpf'], 'Der Server ist gerade ausgelastet'),
       '... und dem Satz aus E-P5a-18');
pruefe(str_contains($seite['kopf']['content-type'] ?? '', 'text/html'),
       '... als HTML', $seite['kopf']['content-type'] ?? '-');
pruefe(($seite['kopf']['cache-control'] ?? '') === 'no-store',
       '... ohne Zwischenspeicher');
/* KEIN WORT UEBER DIE DATENBANK. Das war der eigentliche Fehler der alten
 * 500: Der Ausnahmetext nennt Host und Benutzer. */
$verraten = ['SQLSTATE', '1203', '1040', 'PDO', $cfg['db']['user'],
             'max_user_connections', 'Stack trace'];
$gefunden = array_values(array_filter($verraten,
    static fn(string $w): bool => str_contains($seite['rumpf'], $w)));
pruefe($gefunden === [], '... und verraet nichts ueber die Datenbank',
       $gefunden === [] ? 'keiner von ' . count($verraten) . ' Begriffen'
                        : implode(', ', $gefunden));
pruefe(!str_contains($seite['rumpf'], '<script'),
       '... und traegt kein Skript (wie die Wartungsseite)');

$ing = hole($basis . '/ingest.php',
            ['Content-Type: application/json', 'X-Device-Id: ' . $dev,
             'X-Api-Key: ' . $key],
            (string)json_encode(paket('t1-verbindungsprobe', 5)));
pruefe($ing['code'] === 503, 'ingest.php antwortet 503', 'HTTP ' . $ing['code']);
pruefe(is_array($ing['daten']) && ($ing['daten']['error'] ?? '') === 'ausgelastet',
       '... als JSON mit error=ausgelastet',
       is_array($ing['daten']) ? json_encode($ing['daten']) : substr($ing['rumpf'], 0, 60));
pruefe(($ing['kopf']['retry-after'] ?? '') === '5', '... mit Retry-After: 5');
pruefe(str_contains($ing['kopf']['content-type'] ?? '', 'application/json'),
       '... und dem richtigen Inhaltstyp', $ing['kopf']['content-type'] ?? '-');

/* `auth_salt.php` — der zweite JSON-Weg ausserhalb von `/api/` und der
 * Grund, warum `JSON_SKRIPTE_AUSSERHALB_API` in AP9 von zwei auf vier
 * gewachsen ist. Die Anmeldeseite holt hier ihr Salt per `fetch()`; eine
 * HTML-Seite an dieser Stelle laese sie „Anmeldung derzeit nicht möglich"
 * schreiben statt „ausgelastet" (Backlog Nr. 171, eine Ebene tiefer). */
$api = hole($basis . '/auth_salt.php', ['Content-Type: application/json'],
            (string)json_encode(['email' => 'verbindungsprobe@gen-em.org']));
pruefe($api['code'] === 503
       && is_array($api['daten']) && ($api['daten']['error'] ?? '') === 'ausgelastet',
       'auth_salt.php ebenso — als JSON', 'HTTP ' . $api['code']
       . ', ' . substr($api['rumpf'], 0, 40));

/* UND DIE GEGENPROBE: Was die Datenbank nie erreicht, bekommt kein 503. */
$ohne = hole($basis . '/index.php');
pruefe($ohne['code'] === 302,
       'index.php bleibt bei 302 — es erreicht die Datenbank nie',
       'HTTP ' . $ohne['code']);

/* ---- Der Zaehler ---------------------------------------------------------- */
$zaehlerRoh = @file_get_contents($zaehlDatei);
$z = is_string($zaehlerRoh) ? json_decode($zaehlerRoh, true) : null;
pruefe(is_array($z), 'Der Zaehler ist angelegt worden',
       is_string($zaehlerRoh) ? $zaehlerRoh : 'keine Datei');
/* SO VIELE, WIE ES ABWEISUNGEN GAB — KEINE MEHR. Jede hoehere Zahl waere ein
 * Zeichen dafuer, dass eine Anfrage MEHRFACH zaehlt: Alles, was unterhalb der
 * 503 noch eine Einstellung nachsehen will, landet ueber `app_state_lesen()`
 * wieder in `db()`. Der Riegel dort faengt die Schleife ab — und DAS ist die
 * Zahl, an der man sieht, ob er greift. */
$abgewiesen = count(array_filter([$seite, $ing, $api, $ohne],
    static fn(array $a): bool => $a['code'] === 503));
pruefe(is_array($z) && ($z['ges'] ?? -1) === $abgewiesen,
       "... und zaehlt genau die $abgewiesen Abweisungen, nicht mehr",
       'ges ' . (is_array($z) ? ($z['ges'] ?? '-') : '-') . ", erwartet $abgewiesen");
pruefe(is_array($z) && ($z['n'] ?? -1) === $abgewiesen
       && ($z['sp'] ?? -1) === $abgewiesen,
       '... in der laufenden Stunde, mit passender Spitze',
       is_array($z) ? 'n ' . ($z['n'] ?? '-') . ', sp ' . ($z['sp'] ?? '-') : '-');

/* ======================================================================
 * Teil 2 — null verlorene Uploads
 * ====================================================================== */
echo "\n  Teil 2 — Enge statt Sperre: $pakete Pakete gleichzeitig\n";

/* Plaetze freigeben, bis `$frei2` wieder frei sind. Gerechnet wird gegen die
 * GEMESSENE Belegung aus Teil 1, nicht gegen `$grenze` — siehe
 * `belegen_bis_voll()`. */
while (count($gehalten) > max(0, $b['belegt'] - $frei2)) { array_pop($gehalten); }
echo "    " . count($gehalten) . " von " . $b['belegt'] . " belegt, "
   . ($b['belegt'] - count($gehalten)) . " frei, $arbeiter Arbeiter, "
   . "$pakete Pakete gleichzeitig\n";
/* Der Zaehlerstand VOR jeder Runde — die Differenz sagt, wie viele der 503
 * aus der Verbindungsgrenze kamen und wie viele aus dem Gedraengel um die
 * `days`-Zeile. Zwei Ursachen, ein Statuscode; ohne diese Differenz waere
 * „12 x 503" eine Zahl, die nicht sagt, was sie gemessen hat.
 *
 * BIS ZU DREI RUNDEN (P5c/AP3, F-P5c-98). Ob bei zwei freien Plaetzen eine
 * Anfrage an der Grenze abprallt, haengt daran, ob drei gleichzeitig eine
 * Verbindung halten — gemessen am 24.09.2026: 0, 1, 12 und 13 Abweisungen
 * in vier Laeufen. Mit EINEM freien Platz prallte es jedes Mal (16, 11, 18),
 * aber dann gibt es kein Gedraengel mehr, und um das geht es im Anlass dieser
 * Probe (Nr. 210). Deshalb: zwei Plaetze, und eine neue Runde mit neuen
 * Paketen, solange noch keine Abweisung an der Grenze gemessen ist.
 * Wiederholt wird das HERSTELLEN der Lage, nicht die Bewertung — jede Zusage
 * unten gilt fuer alle Pakete aller Runden. */
$ausGrenze = 0;
$runden = 0;
$antworten = [];
do {
    $runden++;
    $vorTeil2 = (int)((json_decode((string)@file_get_contents($zaehlDatei), true)
                       ?: ['ges' => 0])['ges'] ?? 0);
    $mh = curl_multi_init();
    $hs = [];
    for ($i = 0; $i < $pakete; $i++) {
        $ref = 'vp-' . $runden . '-' . $i;
        $ch = curl_init($basis . '/ingest.php');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json',
                                       'X-Device-Id: ' . $dev, 'X-Api-Key: ' . $key],
            CURLOPT_POSTFIELDS     => (string)json_encode(paket($ref, 20)),
        ]);
        curl_multi_add_handle($mh, $ch);
        $hs[$ref] = $ch;
    }
    do {
        $status = curl_multi_exec($mh, $laufend);
        if ($laufend) { curl_multi_select($mh, 1.0); }
    } while ($laufend && $status === CURLM_OK);
    foreach ($hs as $ref => $ch) {
        $antworten[$ref] = ['code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
                            'rumpf' => (string)curl_multi_getcontent($ch)];
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    $nachTeil2 = (int)((json_decode((string)@file_get_contents($zaehlDatei), true)
                        ?: ['ges' => 0])['ges'] ?? 0);
    $ausGrenze += $nachTeil2 - $vorTeil2;
} while ($ausGrenze === 0 && $runden < 3);

$ok503 = $ok200 = $sonst = 0;
$nachzuholen = [];
$andere = [];
foreach ($antworten as $ref => $a) {
    if ($a['code'] === 200)      { $ok200++; }
    elseif ($a['code'] === 503)  { $ok503++; $nachzuholen[] = $ref; }
    else {
        $sonst++;
        $andere[$a['code']] = ($andere[$a['code']] ?? 0) + 1;
        /* Auch sie werden wiederholt — sonst zaehlte der Nachweis „0
         * verlorene Uploads" nur die Faelle, die ohnehin gutgingen. */
        $nachzuholen[] = $ref;
    }
}
$andereText = '';
foreach ($andere as $c => $n) { $andereText .= ($andereText === '' ? '' : ', ') . $n . ' x ' . $c; }
echo "    Ergebnis in $runden Runde(n) zu $pakete: $ok200 x 200, $ok503 x 503"
   . ($sonst > 0 ? ", $sonst anderes ($andereText)" : '') . "\n";
echo "    Davon aus der Verbindungsgrenze: $ausGrenze, aus Gedraengel um "
   . "dieselbe Zeile: " . max(0, $ok503 - $ausGrenze) . "\n";
pruefe($ausGrenze > 0,
       'Die Enge schlaegt tatsaechlich auf die Verbindungen durch',
       "$ausGrenze Abweisungen am Zaehler");
/* DIE 0 IST DIE AUSSAGE. Jede andere Zahl hiesse, dass die Enge einen
 * Ausgang hat, den der JSON-Vertrag nicht kennt — eine 500 etwa waere fuer
 * die Uhr ein Defekt und kein „gleich noch einmal". */
pruefe($sonst === 0, 'Keine Antwort ausserhalb von 200 und 503',
       $sonst === 0 ? '0' : $andereText);
$alle503jsonOk = true;
foreach ($nachzuholen as $ref) {
    $d = json_decode($antworten[$ref]['rumpf'], true);
    if (!is_array($d) || ($d['error'] ?? '') !== 'ausgelastet') { $alle503jsonOk = false; }
}
pruefe($alle503jsonOk, 'Jede 503 traegt error=ausgelastet',
       $ok503 . ' geprueft');

/* ---- Nachliefern, wie die Uhr es tut -------------------------------------- */
$gehalten = [];   // alle Plaetze freigeben
$nachOk = 0;
foreach ($nachzuholen as $ref) {
    $a = hole($basis . '/ingest.php',
              ['Content-Type: application/json', 'X-Device-Id: ' . $dev,
               'X-Api-Key: ' . $key],
              (string)json_encode(paket($ref, 20)));
    if ($a['code'] === 200) { $nachOk++; }
}
pruefe($nachOk === count($nachzuholen),
       'Jedes abgewiesene Paket kommt bei der Wiederholung an',
       "$nachOk von " . count($nachzuholen));

/* ---- Und steht alles in der Datenbank? ------------------------------------ */
$q = $pdo->prepare('SELECT COUNT(*) FROM missions WHERE user_id = ? AND client_ref LIKE ?');
$q->execute([$uid, 'vp-%']);
$inDb = (int)$q->fetchColumn();
pruefe($inDb === $pakete * $runden, '0 verlorene Uploads: jedes Paket steht in der Datenbank',
       "$inDb von " . ($pakete * $runden));

$q = $pdo->prepare('SELECT COUNT(*) FROM track_points tp
                      JOIN missions m ON m.id = tp.owner_id
                     WHERE tp.owner_type = ? AND m.user_id = ? AND m.client_ref LIKE ?');
$q->execute(['mission', $uid, 'vp-%']);
$punkte = (int)$q->fetchColumn();
pruefe($punkte === $pakete * $runden * 20, '... samt aller Spurpunkte',
       "$punkte von " . ($pakete * $runden * 20));

} finally {
    /* ---- Aufraeumen: Berechtigung, Verbindungen, Konto, Server ------------- */
    $gehalten = [];
    foreach ($vorher as $z) {
        try {
            $rootPdo->exec("ALTER USER '{$z['user']}'@'{$z['host']}' "
                         . "WITH MAX_USER_CONNECTIONS " . (int)$z['max_user_connections']);
        } catch (Throwable $e) {
            fwrite(STDERR, "ACHTUNG: max_user_connections fuer {$z['user']}@{$z['host']} "
                         . "liess sich nicht zuruecksetzen: " . $e->getMessage() . "\n");
        }
    }
    try { $rootPdo->exec('FLUSH PRIVILEGES'); } catch (Throwable $e) { }
    try { db()->prepare('DELETE FROM users WHERE email = ?')->execute([$email]); }
    catch (Throwable $e) { }
    if ($serverPid !== null && $serverPid > 0) {
        /* Der eingebaute Server startet mit Arbeitern KINDPROZESSE. `kill`
         * auf die Gruppe erwischt sie mit; `pkill -P` ist der Rueckweg, wenn
         * die Prozessgruppe nicht die eigene ist. */
        @shell_exec('pkill -P ' . (int)$serverPid . ' 2>/dev/null');
        @shell_exec('kill ' . (int)$serverPid . ' 2>/dev/null');
    }
}

echo "\n  " . ($offen === 0 ? "Alle $erfuellt Erwartungen erfuellt."
                            : "$erfuellt erfuellt, $offen OFFEN.") . "\n";
$stand = @file_get_contents($zaehlDatei);
if (is_string($stand)) { echo "  Zaehler: $stand\n"; }
exit($offen === 0 ? 0 : 1);
