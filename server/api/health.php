<?php
declare(strict_types=1);

/**
 * HEALTH — der Zustand der Anlage für ein fremdes Monitoring.
 *
 *     GET /api/health.php            Kopfzeile  X-Health-Token: <Token>
 *     GET /api/health.php?token=<Token>          (fuer Monitore ohne Kopfzeilen)
 *
 *     <Token> = das Health-Token aus Betrieb → Hintergrundjobs
 *               (`health_token()` in `jobs_lib.php`; ein
 *               `betrieb.health_token` aus `config.php` wird beim ersten
 *               Lesen uebernommen)
 *
 * DIE KOPFZEILE IST DER EMPFOHLENE WEG. Eine Adresse steht im
 * Zugriffsprotokoll des Hosters, eine
 * Kopfzeile nicht. Die Adresse bleibt, weil viele einfache Uptime-Dienste
 * keine Kopfzeile setzen koennen; wer sie nutzt, nimmt den Protokolleintrag
 * in Kauf — das Token oeffnet nur diese karge Auskunft. Stehen beide da,
 * gilt die Kopfzeile.
 *
 * WOZU. Die BetreiberIn trägt diese Adresse in ihr eigenes Monitoring ein
 * (Uptime-Dienst, Nagios, ein `curl` im Cron). Kein Fremddienst wird von hier
 * aus angebunden; der Endpunkt antwortet nur, wenn er gefragt wird.
 *
 * DIE ANTWORT, und sie ist absichtlich karg — keine Konten, keine Mengen,
 * nichts über den Hoster:
 *
 *   ok                    Datenbank erreichbar UND keine Migration ausstehend
 *   web_version           die ausgelieferte Fassung
 *   db                    die Datenbank antwortet
 *   migration_ausstehend  `update.php` muss laufen
 *   jobs_alter_s          Sekunden seit dem letzten Lauf irgendeines Jobs
 *                         (null: nie)
 *   system_24h            Einträge im Reiter System der letzten 24 h
 *   protokoll_fehler      der Zähler gescheiterter Protokolleinträge, bis
 *                         jemand ihn quittiert
 *   speicher_pct          der höchste der drei Prozentwerte, die der tägliche
 *                         Aufräumjob merkt (null: noch nie gemessen)
 *
 * HTTP 200 bei `ok`, sonst 503 — ein Monitoring, das nur den Code liest,
 * sieht damit schon das Wesentliche.
 *
 * WAS NICHT HIER ANTWORTET: In der Wartung antwortet das Tor in
 * `db.php`, bevor diese Datei eine Zeile ausführt — 503 mit
 * `{"error":"maintenance"}`, ohne Token-Prüfung. Bei Überlast antwortet
 * `db()` beim ersten Zugriff, und das ist der Ratenschutz unten, noch vor dem
 * Token: 503 mit `{"error":"ausgelastet"}` aus `ueberlast_antwort()`. Ein
 * POST bekommt deshalb bei Überlast 405, nicht 503. Ein Feld `wartung` gäbe
 * es nie mit `true`; es entfällt. Fassung und Wartungszustand sind
 * ohnehin öffentlich (Fußzeile, Wartungsseite).
 *
 * DER TOKEN WIE BEI `jobs.php`: fehlt er, ist er falsch oder ist keiner
 * eingerichtet — einheitlich 403 `token`, verglichen mit `hash_equals()`
 * und mit angeglichener Antwortzeit. Die Antwort verrät nicht, OB ein Token
 * eingerichtet ist. Leerer Eintrag heißt: Endpunkt aus.
 *
 * TOPF `health` (60 je Minute und Adresse): Er zählt die MENGE, nicht
 * Fehlversuche — jede Anfrage, auch eine richtige (Muster `csp`). Ein
 * Monitoring fragt einmal je Minute; wer sechzigmal fragt, fragt nicht nach
 * dem Zustand.
 *
 * DIE ZAHLEN KOSTEN KEINEN VERZEICHNISLAUF. `speicher_pct` liest, was der
 * Aufräumjob gemerkt hat; alles andere sind vier kleine Abfragen. Ein Abruf je
 * Minute darf die Anlage nicht spürbar belasten.
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../ratelimit_lib.php';
require_once __DIR__ . '/../jobs_lib.php';   // health_token()

api_methode('GET');
$t0 = microtime(true);

if (!rate_erlaubt('health')) {
    rate_gleiche_dauer($t0);
    json_out(['error' => 'zu_viele_versuche'], 429);
}
rate_zaehlen('health');

$kopf     = (string)($_SERVER['HTTP_X_HEALTH_TOKEN'] ?? '');
$token    = $kopf !== '' ? $kopf : (string)($_GET['token'] ?? '');
$erwartet = health_token();
if ($erwartet === '' || $token === '' || !hash_equals($erwartet, $token)) {
    rate_gleiche_dauer($t0);
    json_out(['error' => 'token'], 403);
}

/* AB HIER DARF JEDER TEIL SCHEITERN, OHNE DIE ANTWORT ZU KIPPEN. Ein
 * Monitoring, das bei einem Datenbankausfall eine PHP-Fehlerseite bekommt,
 * weiß weniger als eines, das `db: false` liest. */
$antwort = [
    'ok'                   => false,
    'web_version'          => WEB_VERSION,
    'db'                   => false,
    'migration_ausstehend' => null,
    'jobs_alter_s'         => null,
    'system_24h'           => null,
    'protokoll_fehler'     => null,
    'speicher_pct'         => null,
];

try {
    $pdo = db();
    $pdo->query('SELECT 1')->fetchColumn();
    $antwort['db'] = true;
} catch (Throwable $ex) {
    system_rueckfall('health', 'Datenbank nicht erreichbar', $ex);
    json_out($antwort, 503);
}

try {
    require_once __DIR__ . '/../migration_lib.php';
    $antwort['migration_ausstehend'] = migrationen_ausstehend($pdo);
} catch (Throwable $ex) {
    system_melden('health', 'Migrationsstand nicht lesbar', $ex);
}

try {
    $alter = $pdo->query('SELECT TIMESTAMPDIFF(SECOND, MAX(letzter_lauf), UTC_TIMESTAMP()) FROM jobs')
                 ->fetchColumn();
    $antwort['jobs_alter_s'] = $alter === null ? null : max(0, (int)$alter);
} catch (Throwable $ex) {
    system_melden('health', 'Alter der Jobs nicht lesbar', $ex);
}

try {
    require_once __DIR__ . '/../protokoll_lib.php';
    $antwort['system_24h']       = protokoll_zahl('system', ['tage' => 1]);
    $antwort['protokoll_fehler'] = protokoll_fehler_zahl();
} catch (Throwable $ex) {
    system_melden('health', 'Protokollzahlen nicht lesbar', $ex);
}

try {
    require_once __DIR__ . '/../speicher_lib.php';
    $antwort['speicher_pct'] = speicher_prozent_hoechster();
} catch (Throwable $ex) {
    system_melden('health', 'Speicherstand nicht lesbar', $ex);
}

$antwort['ok'] = $antwort['db'] && $antwort['migration_ausstehend'] === false;
json_out($antwort, $antwort['ok'] ? 200 : 503);
