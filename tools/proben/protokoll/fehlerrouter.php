<?php
declare(strict_types=1);

/**
 * Helfer der Protokollprobe, Teil 7 (P5c/AP3) — Fehler provozieren, ohne eine
 * Datei unter `server/` anzulegen.
 *
 * ALS ROUTER eines eigenen `php -S` (die Probe startet ihn auf einem freien
 * Anschluss, Wurzel `server/`): Adressen, deren letzter Teil mit `probe-`
 * beginnt, landen hier; alles andere liefert der eingebaute Server wie
 * gewohnt. Eine Datei unter `server/` waere der einfachere Weg — und einer,
 * der nach einem Abbruch der Probe liegen bliebe und mit dem naechsten Push
 * auf Staging ginge.
 *
 * AUF DER KOMMANDOZEILE zwei Faelle, die kein Webserver braucht:
 *   --ausnahme  eine ungefangene Ausnahme (Rueckgabewert 255, Kennung auf stderr)
 *   --ohne-db   die eigene Datenbankverbindung trennen und dann melden —
 *               Rueckfall statt Schleife (E-P5c-58); gibt die Kennung und
 *               die Dauer als JSON aus
 */

$srv = dirname(__DIR__, 3) . '/server';

if (PHP_SAPI === 'cli') {
    require $srv . '/db.php';
    $fall = $argv[1] ?? '';
    if ($fall === '--ausnahme') {
        throw new RuntimeException("Kommandozeile: Wert 'protokollprobe-geheim@probe.invalid' kaputt");
    }
    if ($fall === '--ohne-db') {
        /* DIE VERBINDUNG VON AUSSEN TRENNEN, nicht `config.php` tauschen: Ein
         * Tausch haette fuer die Dauer der Probe die ganze Anlage getroffen
         * (F-P5c-86). So trifft es nur diesen Prozess — `db()` haelt die
         * getrennte Verbindung in seinem `static` und oeffnet keine neue. */
        $id = (int)db()->query('SELECT CONNECTION_ID()')->fetchColumn();
        $zweite = new PDO((string)konfig('db.dsn'), (string)konfig('db.user'),
                          (string)konfig('db.pass'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $zweite->exec('KILL CONNECTION ' . $id);
        $zweite = null;
        usleep(200000);
        $t0 = microtime(true);
        $k = system_melden('protokollprobe', 'Datenbank weg',
                           "Wert 'protokollprobe-geheim@probe.invalid' kaputt");
        echo json_encode(['kennung' => $k, 'dauer_s' => round(microtime(true) - $t0, 3)]), "\n";
        exit(0);
    }
    fwrite(STDERR, "Fall fehlt: --ausnahme | --ohne-db\n");
    exit(2);
}

$fall = basename((string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
if (!str_starts_with($fall, 'probe-')) { return false; }

require $srv . '/db.php';
require_once $srv . '/sitzung_lib.php';
/* Die Sitzung der Probe, damit der Eintrag einen Urheber hat — und eine
 * Marke darin, die im Eintrag NICHT stehen darf. */
sitzung_starten('lesend');
$_SESSION['probe_marke'] = 'SITZUNGSMARKE';

switch ($fall) {
    case 'probe-ausnahme':
        throw new RuntimeException("Wert 'protokollprobe-geheim@probe.invalid' von 198.51.100.9 kaputt");

    case 'probe-halbseite':
        /* Erst ein Stueck Seite, dann die Ausnahme — mit Ausgabepuffer,
         * wie beim Hoster (F-P5c-97). Das Stueck darf nicht vor der
         * Fehlerseite stehen. */
        echo "<!doctype html><title>x</title><p>HALBE SEITE</p>\n";
        throw new RuntimeException('nach einer halben Seite');

    case 'probe-at':
        $x = @file_get_contents('/gibt/es/nicht/' . bin2hex(random_bytes(4)));
        echo $x === false ? 'ok' : 'unerwartet';
        exit;

    case 'probe-warnung':
        /* Fuenfmal dieselbe Zeile — ein Eintrag. Dann 25 verschiedene
         * Zeilen — die Decke je Anfrage greift. */
        for ($i = 0; $i < 5; $i++) { echo $gibtEsNicht ?? ''; echo $undefiniert; }
        eval(str_repeat("echo \$nichtDa;\n", 25));
        echo 'weiter';
        exit;

    case 'probe-abbruch':
        ini_set('memory_limit', '8M');
        $t = [];
        while (true) { $t[] = str_repeat('y', 100000); }
}
http_response_code(404);
echo 'unbekannter Fall';
