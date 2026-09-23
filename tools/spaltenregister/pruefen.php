<?php
/**
 * Spaltenregister — `schema.sql` gegen `mf_missions_register()` (Schritt 15/AP6).
 *
 * Zwei Fragen, zwei Zahlen:
 *
 *   1. Fuehrt das Register JEDE Spalte von `missions` aus `schema.sql`?
 *      Eine neue Spalte, die niemand eintraegt, taucht in keiner der sechs
 *      erzeugten Listen auf — sie fehlte dann still in Export und Backup.
 *   2. Kennt das Register eine Spalte, die es nicht gibt? Dann liefe ein
 *      `SELECT` auf einen Namen, den die Datenbank nicht hat.
 *
 * Dazu die Selbstprobe von `mf_spalten()`: Die Positionen jedes Zwecks
 * muessen eine lueckenlose Folge ab 0 sein.
 *
 * TEIL 2 — DIE VOLLSTAENDIGKEITSPROBE DER DREI ABBILDUNGEN.
 *
 * Drei Stellen bilden eine Datenbankzeile auf fremde Schluessel ab (oder
 * umgekehrt) und koennen deshalb NICHT aus dem Register erzeugt werden: Jeder
 * Wert traegt dort seine eigene Umwandlung — eine Umrechnung nach Ortszeit,
 * eine Laengenbegrenzung, eine Uebersetzung des Herkunftsschluessels. Ein
 * `implode()` ueber Spaltennamen kann das nicht.
 *
 * Was hier geprueft wird, ist deshalb nicht die Erzeugung, sondern die
 * VOLLSTAENDIGKEIT: Fuehrt die Handliste genau die Spalten des Zwecks —
 * keine zu wenig, keine zu viel? Eine Spalte, die das Register kennt und die
 * Abbildung nicht, fehlte still in der Exportdatei beziehungsweise im
 * Suchindex; eine, die nur die Abbildung kennt, liefe ins Leere.
 *
 * ZWEI AUSNAHMEKLASSEN, beide mit Begruendung im Feld:
 *   `abgeleitet` — die Spalte wird verarbeitet, taucht aber unter keinem
 *                  eigenen Schluessel auf (`started_at` wird im Suchindex zu
 *                  `day`, `start_hhmm`, `start_min` und `duration_s`).
 *   `fremd`      — der Schluessel kommt nicht aus `missions` (`crew` aus
 *                  `mission_crew`, `base` aus der Momentaufnahme des Tages).
 * Eine Ausnahme OHNE Begruendung ist ein Befund; eine begruendete, die nichts
 * mehr trifft, ebenfalls — sonst waechst die Liste zu und die Probe misst
 * nichts mehr.
 *
 * Rueckgabe 0 = in Ordnung, 1 = Befund, 2 = nicht gelaufen.
 *
 *   php tools/spaltenregister/pruefen.php
 */
declare(strict_types=1);

$wurzel = dirname(__DIR__, 2) . '/server';
if (!is_dir($wurzel)) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $wurzel . '/mission_fields_lib.php';

if (in_array('--selbstprobe', $argv, true)) { exit(sr_selbstprobe()); }

$schema = @file_get_contents($wurzel . '/schema.sql');
if ($schema === false) { fwrite(STDERR, "schema.sql nicht lesbar\n"); exit(2); }

if (!preg_match('/CREATE TABLE missions \((.*?)\n\) ENGINE/s', $schema, $m)) {
    fwrite(STDERR, "CREATE TABLE missions nicht gefunden\n"); exit(2);
}
$ausSchema = [];
foreach (explode("\n", $m[1]) as $z) {
    $z = trim($z);
    if ($z === '' || str_starts_with($z, '--')) { continue; }
    if (preg_match('/^(PRIMARY|FOREIGN|INDEX|UNIQUE|KEY|CONSTRAINT)/i', $z)) { continue; }
    if (preg_match('/^`?([a-z_]+)`?\s+/i', $z, $t)) { $ausSchema[] = $t[1]; }
}
$imRegister = array_keys(mf_missions_register());

$fehlend = array_values(array_diff($ausSchema, $imRegister));
$zuviel  = array_values(array_diff($imRegister, $ausSchema));

echo "Spaltenregister — schema.sql gegen mf_missions_register()\n";
echo str_repeat('=', 74) . "\n";
printf("  Spalten in schema.sql:            %3d\n", count($ausSchema));
printf("  Spalten im Register:              %3d\n", count($imRegister));
printf("  im Schema, nicht im Register:     %3d%s\n", count($fehlend),
       $fehlend ? '  -> ' . implode(', ', $fehlend) : '');
printf("  im Register, nicht im Schema:     %3d%s\n", count($zuviel),
       $zuviel ? '  -> ' . implode(', ', $zuviel) : '');

$zwecke = [];
foreach (mf_missions_register() as $sp => $z) { foreach (array_keys($z) as $zw) { $zwecke[$zw] = true; } }
$zwecke = array_keys($zwecke);
sort($zwecke);
echo "\n  Zwecke und ihre Listen:\n";
$fehler = count($fehlend) + count($zuviel);
foreach ($zwecke as $zw) {
    try {
        $l = mf_spalten($zw);
        printf("    %-16s %2d Spalten\n", $zw, count($l));
    } catch (Throwable $ex) {
        printf("    %-16s BEFUND: %s\n", $zw, $ex->getMessage());
        $fehler++;
    }
}

/* Jede Spalte muss einen Zweck ODER einen Grund haben. */
$gruende = mf_missions_gruende();
$ohne = [];
foreach (mf_missions_register() as $sp => $z) {
    if ($z === [] && !isset($gruende[$sp])) { $ohne[] = $sp; }
}
printf("\n  Spalten ohne Zweck und ohne Grund: %3d%s\n", count($ohne),
       $ohne ? '  -> ' . implode(', ', $ohne) : '');
$fehler += count($ohne);

/* ====================================================================== *
 * TEIL 2 — Vollstaendigkeitsprobe der drei Abbildungen                    *
 * ====================================================================== */

/**
 * Die Schluessel eines Array-Literals, das einer Variablen zugewiesen wird.
 *
 * UEBER DEN TOKENSTROM, nicht per Muster: Ein Muster ueber den Quelltext
 * faende die Spaltennamen auch in den Kommentaren daneben — und davon stehen
 * an allen drei Stellen reichlich. `token_get_all()` sieht den Unterschied.
 *
 * @return list<string>|null null = Anker nicht gefunden
 */
function sr_literal_schluessel(string $quelle, string $variable, bool $anhaengen): ?array
{
    $roh = token_get_all($quelle);
    $tok = [];
    foreach ($roh as $t) {
        if (is_array($t) && in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { continue; }
        $tok[] = is_array($t) ? [$t[0], $t[1]] : [null, $t];
    }
    /* Der Anker: `$name = [`  beziehungsweise  `$name[] = [` */
    $muster = $anhaengen
        ? [[T_VARIABLE, $variable], [null, '['], [null, ']'], [null, '='], [null, '[']]
        : [[T_VARIABLE, $variable], [null, '='], [null, '[']];
    $start = null;
    for ($i = 0, $n = count($tok) - count($muster); $i <= $n; $i++) {
        $passt = true;
        foreach ($muster as $k => [$art, $text]) {
            if ($tok[$i + $k][1] !== $text || ($art !== null && $tok[$i + $k][0] !== $art)) {
                $passt = false; break;
            }
        }
        if ($passt) { $start = $i + count($muster) - 1; break; }
    }
    if ($start === null) { return null; }

    $tiefe = 0; $schluessel = [];
    for ($i = $start, $n = count($tok); $i < $n; $i++) {
        $s = $tok[$i][1];
        if ($tok[$i][0] === null && ($s === '[' || $s === '(')) { $tiefe++; continue; }
        if ($tok[$i][0] === null && ($s === ']' || $s === ')')) {
            $tiefe--;
            if ($tiefe === 0) { break; }
            continue;
        }
        /* Nur die OBERSTE Ebene: `(object)($crewByMission[$id] ?? [])` bringt
         * eigene Klammern mit, und was darin steht, ist kein Schluessel. */
        if ($tiefe === 1 && $tok[$i][0] === T_CONSTANT_ENCAPSED_STRING
            && isset($tok[$i + 1]) && $tok[$i + 1][0] === T_DOUBLE_ARROW) {
            $schluessel[] = trim($tok[$i][1], "'\"");
        }
    }
    return $schluessel;
}

/**
 * Sollmenge, Fehlendes, Ueberzaehliges und tote Ausnahmen einer Abbildung.
 *
 * @return array{0:list<string>,1:list<string>,2:list<string>,3:list<string>}
 */
function sr_vergleich(array $a, array $ist): array
{
    $soll = [];
    foreach (mf_missions_register() as $sp => $zw) {
        if (!isset($zw[$a['zweck']])) { continue; }
        if (isset($a['abgeleitet'][$sp])) { continue; }
        $e = $zw[$a['zweck']];
        /* Der Alias gewinnt: Im Export heisst `uhr_gesperrt` in der Datei
         * `manual`, und die Abbildung fuehrt den Dateinamen. */
        $name = is_array($e) ? $e[1] : $sp;
        $soll[] = $a['umbenannt'][$name] ?? $name;
    }
    $soll = array_merge($soll, array_keys($a['fremd']));

    /* Eine Ausnahme, die nichts mehr trifft, ist selbst ein Befund — sonst
     * waechst die Liste zu und die Probe misst nichts mehr. */
    $tot = [];
    foreach (array_keys($a['abgeleitet']) as $sp) {
        if (!isset(mf_missions_register()[$sp][$a['zweck']])) { $tot[] = "abgeleitet:$sp"; }
    }
    foreach (array_keys($a['fremd']) as $k) {
        if (!in_array($k, $ist, true)) { $tot[] = "fremd:$k"; }
    }

    return [$soll,
            array_values(array_diff($soll, $ist)),
            array_values(array_diff($ist, $soll)),
            $tot];
}

$abbildungen = [
  ['datei' => 'api/export_data.php', 'variable' => '$missions', 'anhaengen' => true,
   'zweck' => 'export',
   'was'   => 'Datenbankzeile -> Exportdatei (docs/Export-Format.md)',
   'umbenannt'  => ['origin' => 'source'],
   'abgeleitet' => [],
   'fremd' => [
     'day'          => 'Das Datum des Diensttags, aus days.day — keine Spalte von missions.',
     'crew'         => 'Abweichende Besatzung: Zeilen in mission_crew, keine Spalte.',
     'track_points' => 'Anzahl der GPS-Punkte, ueber spur_lib.php gezaehlt.',
     'phases'       => 'Zeilen in mission_phases.',
     'resources'    => 'Zeilen in mission_resources.',
     'resus'        => 'Zeilen in resus_sessions und resus_events.',
   ]],
  ['datei' => 'api/import_commit.php', 'variable' => '$werte', 'anhaengen' => false,
   'zweck' => 'import_aendern',
   'was'   => 'Exportdatei -> Datenbankzeile (eine Werteliste fuer INSERT und UPDATE)',
   'umbenannt'  => [],
   'abgeleitet' => [
     'day_id'     => 'Steht im Kopf des Aufrufs: der Diensttag wird vorher zugeordnet.',
     'started_at' => 'Steht im Kopf des Aufrufs: aus der Zeitpruefung der Datei.',
     'ended_at'   => 'Steht im Kopf des Aufrufs, wie started_at.',
     'final'      => 'Steht im Kopf des Aufrufs: Dateiwert oder Bestandswert (Nr. 28).',
     'uhr_gesperrt' => 'Literal 1 im Satz — ein importierter Einsatz gilt wie ein von Hand angelegter.',
     'edited'     => 'Literal 1 im Satz.',
   ],
   'fremd' => []],
  ['datei' => 'api/suchindex.php', 'variable' => '$missions', 'anhaengen' => true,
   'zweck' => 'suchindex',
   'was'   => 'Datenbankzeile -> Suchindex des Browsers',
   'umbenannt'  => [],
   'abgeleitet' => [
     'started_at' => 'Geht in day, start_hhmm, start_min und duration_s auf — vier Schluessel aus einer Spalte.',
     'ended_at'   => 'Geht mit started_at in duration_s auf.',
     'crew_override' => 'Steuert, OB die Einsatzbesatzung gilt; das Ergebnis steht in crew.',
   ],
   'fremd' => [
     'day'          => 'Das Einsatzdatum in Ortszeit, aus started_at gerechnet (E14).',
     'dienst_day'   => 'Das Datum des Diensttags daneben, aus days.day.',
     'start_hhmm'   => 'Beginn in Ortszeit, aus started_at.',
     'start_sort'   => 'Chronologischer Sortierschluessel (Y-m-d H:i in '
                     . 'Ortszeit), aus started_at — Schritt 15 AP9b, E-ZE-32. '
                     . 'start_hhmm allein taugt nicht: Bei einem Dienst ueber '
                     . 'Mitternacht steht 01:10 als Zeichenkette vor 23:50.',
     'start_min'    => 'Minuten seit Mitternacht, aus start_hhmm.',
     'duration_s'   => 'Dauer, aus started_at und ended_at.',
     'crew'         => 'Effektive Besatzung aus day_crew und mission_crew.',
     'resources'    => 'Zeilen in mission_resources.',
     'base'         => 'Momentaufnahme des Diensttags (days.base_name), nie aus den Stammdaten.',
     'vehicle'      => 'Momentaufnahme des Diensttags (days.vehicle_name).',
     'vehicle_kurz' => 'Momentaufnahme des Diensttags (days.vehicle_kurz).',
     'kind'         => 'Momentaufnahme des Diensttags (days.kind).',
     'day_typ'      => 'Momentaufnahme des Diensttags (days.vehicle_typ).',
   ]],
];

echo "\n" . str_repeat('-', 74) . "\n";
echo "Vollstaendigkeitsprobe — die drei Abbildungen gegen das Register\n";
echo str_repeat('-', 74) . "\n";

foreach ($abbildungen as $a) {
    $pfad = $wurzel . '/' . $a['datei'];
    $q = @file_get_contents($pfad);
    if ($q === false) {
        printf("  %-26s BEFUND: nicht lesbar\n", $a['datei']); $fehler++; continue;
    }
    $ist = sr_literal_schluessel($q, $a['variable'], $a['anhaengen']);
    if ($ist === null) {
        printf("  %-26s BEFUND: Anker %s nicht gefunden\n", $a['datei'], $a['variable']);
        $fehler++; continue;
    }

    [$soll, $fehlt, $zuviel, $tot] = sr_vergleich($a, $ist);
    $n = count($fehlt) + count($zuviel) + count($tot);
    $fehler += $n;
    printf("  %-26s %-14s %2d Schluessel, %2d aus dem Register%s\n",
           $a['datei'], $a['zweck'], count($ist),
           count($soll) - count($a['fremd']), $n === 0 ? '  ok' : '');
    if ($fehlt)  { echo "      im Register, nicht in der Abbildung: " . implode(', ', $fehlt) . "\n"; }
    if ($zuviel) { echo "      in der Abbildung, nicht im Register: " . implode(', ', $zuviel) . "\n"; }
    if ($tot)    { echo "      Ausnahme ohne Treffer: " . implode(', ', $tot) . "\n"; }
}

/* EINE WERTELISTE FUER ZWEI ANWEISUNGEN. `$werte` in import_commit.php wird
 * an den INSERT UND an das UPDATE gegeben. Das geht nur, wenn beide Zwecke
 * dieselben Spalten ausserhalb von Kopf und Literalen fuehren. */
$kopfNeu  = ['user_id', 'device_id', 'client_ref', 'day_id', 'started_at', 'ended_at',
             'final', 'uhr_gesperrt', 'origin'];
$kopfAend = ['day_id', 'started_at', 'ended_at', 'final', 'uhr_gesperrt', 'edited'];
$restNeu  = array_values(array_diff(mf_spalten('import_neu', '', false), $kopfNeu));
$restAend = array_values(array_diff(mf_spalten('import_aendern', '', false), $kopfAend));
$gleich   = $restNeu === $restAend;
printf("\n  Werteliste des Imports passt auf beide Anweisungen: %s (%d / %d Spalten)\n",
       $gleich ? 'ja' : 'NEIN', count($restNeu), count($restAend));
if (!$gleich) {
    echo "      nur import_neu:     " . implode(', ', array_diff($restNeu, $restAend)) . "\n";
    echo "      nur import_aendern: " . implode(', ', array_diff($restAend, $restNeu)) . "\n";
    $fehler++;
}

echo "\n" . str_repeat('=', 74) . "\n";
echo $fehler === 0
    ? "Das Register fuehrt jede Spalte genau einmal; die drei Abbildungen sind vollstaendig.\n"
    : "BEFUNDE: $fehler\n";
exit($fehler === 0 ? 0 : 1);


/* ====================================================================== *
 * Selbstprobe — findet die Probe ueberhaupt etwas?                       *
 * ====================================================================== *
 *
 * Ein gruener Lauf einer Pruefung, die IMMER gruen meldet, sieht genauso aus
 * wie einer, der nichts gefunden hat (CLAUDE.md 6). Die Selbstprobe legt der
 * Probe deshalb hin, was sie finden MUSS — und was sie NICHT melden darf.
 *
 *   php tools/spaltenregister/pruefen.php --selbstprobe
 */
function sr_selbstprobe(): int
{
    $ok = 0; $fehl = 0;
    $pruefe = static function (string $was, $ist, $soll) use (&$ok, &$fehl): void {
        $gut = $ist === $soll;
        printf("  %s  %-54s erwartet %s, gemessen %s\n",
               $gut ? 'ok  ' : 'FEHL', $was,
               json_encode($soll, JSON_UNESCAPED_SLASHES),
               json_encode($ist,  JSON_UNESCAPED_SLASHES));
        $gut ? $ok++ : $fehl++;
    };

    echo "Selbstprobe des Spaltenregisters\n";
    echo str_repeat('=', 74) . "\n";

    /* ---- 1. Der Leser des Array-Literals ------------------------------- */
    $q = <<<'PHP'
<?php
$missions = [];
foreach ($rows as $r) {
    /* Ein Kommentar, der day_id und started_at nennt — und der NICHT
       mitgezaehlt werden darf. */
    $missions[] = [
        'id'     => (int)$r['id'],
        'day_id' => (int)$r['day_id'],
        'crew'   => (object)($crewByMission[$id] ?? ['pilot' => null]),
        'tief'   => ['innen' => 1, 'auch_innen' => 2],
        'letzt'  => $r['x'],
    ];
}
PHP;
    $pruefe('Leser: nur die oberste Ebene, Kommentar zaehlt nicht',
            sr_literal_schluessel($q, '$missions', true),
            ['id', 'day_id', 'crew', 'tief', 'letzt']);
    $pruefe('Leser: Form ohne Anhaengen ($name = [)',
            sr_literal_schluessel("<?php\n\$werte = ['a' => 1, 'b' => 2];", '$werte', false),
            ['a', 'b']);
    $pruefe('Leser: falscher Anker gibt null',
            sr_literal_schluessel("<?php\n\$werte = ['a' => 1];", '$gibtsnicht', false),
            null);
    /* GEGENPROBE: Der Anker `$missions[] = [` darf NICHT auf `$missions = [`
     * anspringen — sonst laese die Probe die leere Sammelliste aus. */
    $pruefe('Leser: $name[] = [ springt nicht auf $name = [ an',
            sr_literal_schluessel("<?php\n\$missions = ['falsch' => 1];", '$missions', true),
            null);

    /* ---- 2. Der Vergleich gegen das Register --------------------------- */
    $a = ['zweck' => 'range', 'umbenannt' => [], 'abgeleitet' => [], 'fremd' => []];
    $voll = mf_spalten('range', '', false);

    [, $fehlt, $zuviel, $tot] = sr_vergleich($a, $voll);
    $pruefe('Vergleich: vollstaendige Liste meldet nichts',
            [count($fehlt), count($zuviel), count($tot)], [0, 0, 0]);

    $ohne = $voll; array_splice($ohne, 3, 1);
    [, $fehlt] = sr_vergleich($a, $ohne);
    $pruefe('Vergleich: fehlender Schluessel wird gemeldet', $fehlt, [$voll[3]]);

    [, , $zuviel] = sr_vergleich($a, array_merge($voll, ['quatsch_xy']));
    $pruefe('Vergleich: fremder Schluessel wird gemeldet', $zuviel, ['quatsch_xy']);

    /* GEGENPROBE zur vorigen Zeile: derselbe Schluessel, diesmal begruendet
     * als `fremd` — dann darf er NICHT mehr gemeldet werden. */
    $b = $a; $b['fremd'] = ['quatsch_xy' => 'begruendet'];
    [, $fehlt, $zuviel, $tot] = sr_vergleich($b, array_merge($voll, ['quatsch_xy']));
    $pruefe('Vergleich: begruendeter fremder Schluessel wird NICHT gemeldet',
            [count($fehlt), count($zuviel), count($tot)], [0, 0, 0]);

    /* Eine Ausnahme ohne Treffer ist selbst ein Befund. */
    $c = $a; $c['fremd'] = ['nie_da' => 'begruendet, aber nicht vorhanden'];
    [, , , $tot] = sr_vergleich($c, $voll);
    $pruefe('Vergleich: tote fremd-Ausnahme wird gemeldet', $tot, ['fremd:nie_da']);

    $d = $a; $d['abgeleitet'] = ['other_resources' => 'steht in keinem Zweck'];
    [, , , $tot] = sr_vergleich($d, $voll);
    $pruefe('Vergleich: tote abgeleitet-Ausnahme wird gemeldet',
            $tot, ['abgeleitet:other_resources']);

    /* Eine echte abgeleitete Spalte darf NICHT als tot gelten. */
    $e = $a; $e['abgeleitet'] = ['pat_blob' => 'steht im Zweck range'];
    $ohnePat = array_values(array_diff($voll, ['pat_blob']));
    [, $fehlt, $zuviel, $tot] = sr_vergleich($e, $ohnePat);
    $pruefe('Vergleich: echte abgeleitet-Ausnahme gilt nicht als tot',
            [count($fehlt), count($zuviel), count($tot)], [0, 0, 0]);

    /* ---- 3. Die Selbstprobe von mf_spalten() --------------------------- */
    $lueckenhaft = false;
    try {
        /* `export` mit einem Alias: Der Alias muss mitkommen, wenn er darf,
         * und wegbleiben, wenn nicht.
         *
         * IN BACKTICKS, und die gehoeren zur Erwartung (Web 20.26.3,
         * Nr. 267). `manual` ist auf MySQL 8.4.0 bis 8.4.10 auch als ALIAS
         * ein reserviertes Wort; ohne die Backticks antwortete der Export
         * dort mit 1064. Die Behebung lag bis zum Merge von Schritt 15 in
         * `backup_lib.php` und `api/export_data.php`; seit das Register
         * beide Listen liefert, steht sie in `mf_spalten()`.
         *
         * DIESE ZEILE IST DER RIEGEL DAVOR, dass sie wieder verschwindet.
         * Sie hat angeschlagen, als der Merge den Fix verschob, und das ist
         * genau ihre Aufgabe: Wer die Backticks streicht, faellt hier auf
         * und nicht erst auf einer MySQL-8.4-Anlage. */
        $mit  = mf_spalten('export', '', true);
        $ohneAlias = mf_spalten('export', '', false);
        $pruefe('mf_spalten: Alias an, in Backticks',
                in_array('uhr_gesperrt AS `manual`', $mit, true), true);
        $pruefe('mf_spalten: Alias aus', in_array('uhr_gesperrt', $ohneAlias, true), true);
        $pruefe('mf_spalten: Praefix', mf_spalten('range', 'm.')[0], 'm.id');
    } catch (Throwable $ex) {
        $lueckenhaft = true;
    }
    $pruefe('mf_spalten: keine Ausnahme bei gueltigem Register', $lueckenhaft, false);

    $geworfen = false;
    try { mf_spalten('gibtsnicht'); } catch (InvalidArgumentException) { $geworfen = true; }
    $pruefe('mf_spalten: unbekannter Zweck wirft', $geworfen, true);

    echo str_repeat('=', 74) . "\n";
    printf("Selbstprobe: %d von %d\n", $ok, $ok + $fehl);
    return $fehl === 0 ? 0 : 1;
}
