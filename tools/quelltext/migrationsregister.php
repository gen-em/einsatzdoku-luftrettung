<?php
declare(strict_types=1);

/**
 * Migrationsregister — steht `schema.sql` und `migration_lib.php` dasselbe? (P5a/AP1)
 *
 * WOGEGEN. `migration_lib.php` fuehrt im Kopf eine Hausregel: „NEUE MIGRATION?
 * Ans ENDE von `migrationen_katalog()` anhaengen und die Kennung zusaetzlich am
 * Ende von `schema.sql` eintragen, damit Neuinstallationen sie nicht unnoetig
 * ausfuehren." Die Regel ist richtig und wurde trotzdem schon dreimal
 * vergessen — die Kommentare am Ende von `schema.sql` sagen es selbst
 * („Nachgetragen (Web 5.9.0): Beide Migrationen fehlten hier"). Die Folge ist
 * keine Fehlermeldung, sondern eine Neuinstallation, die Migrationen ansetzt,
 * die auf ihr nichts zu tun haben — und im schlimmsten Fall an einer bereits
 * vorhandenen Spalte haengenbleibt.
 *
 * Diese Pruefung beantwortet das mit einer Zahl statt mit Aufmerksamkeit. Sie
 * ist Stufe 1 des Prueftors (E-P5a-13) und braucht deshalb **keine
 * Installation**: keine Datenbank, keine `config.php`, kein Netz. Sie liest
 * zwei Dateien.
 *
 * WIE SIE AN DEN KATALOG KOMMT, OHNE IHN ZU LADEN. `migration_lib.php` laedt
 * `db.php`, und das laedt `config.php` — die es in einem frisch ausgecheckten
 * Repositorium nicht gibt. Statt eine hinzulegen, liest diese Pruefung die
 * Datei mit `token_get_all()`: Jede Zeichenkette im Quelltext steht dann
 * einzeln da, in der Reihenfolge des Quelltextes. Eine Kennung ist eine
 * Zeichenkette der Form `JJJJ_MM_TT_stichwort`; die DDL-Anweisungen dahinter
 * gehoeren zu ihr, bis die naechste Kennung kommt. Das ist ungenauer als ein
 * Aufruf von `migrationen_katalog()` und dafuer voraussetzungslos — und die
 * Ungenauigkeit trifft nur die Anweisungen, nicht die Kennungen, um die es
 * hier vor allem geht.
 *
 * SIEBEN PRUEFUNGEN
 *   1  Jede Kennung des Katalogs steht in der Vorabliste von `schema.sql`.
 *   2  Jede Kennung der Vorabliste steht im Katalog.
 *   3  Keine Kennung steht zweimal — weder hier noch dort.
 *   4  Die Kennungen des Katalogs steigen. Die Reihenfolge IST der
 *      Mechanismus; eine Migration, die vor ihrer Voraussetzung steht,
 *      scheitert erst auf einer fremden Installation.
 *   5  Was eine Migration ANLEGT (Tabelle, Spalte), steht am Ende auch in
 *      `schema.sql` — sonst hat eine Neuinstallation es nicht.
 *   6  Was eine Migration LOESCHT, steht dort nicht mehr.
 *   7  Jeder Katalogeintrag hat `label` und entweder `sql` oder `run`.
 *
 * Pruefung 5 und 6 rechnen den Katalog durch: Sie beginnen leer, wenden jede
 * DDL-Anweisung an und vergleichen das Ergebnis mit `schema.sql`. Das Ergebnis
 * ist eine TEILMENGE des Schemas — was nie eine Migration hatte, steht nur
 * dort. Geprueft wird deshalb in eine Richtung: Was der Katalog anlegt, muss
 * drueben sein; was er loescht, darf es nicht.
 *
 * IHRE GRENZE, UND SIE IST SCHARF. Ein `$pdo->exec("ALTER TABLE `$tab` DROP
 * COLUMN `$spalte`")` mit eingesetzten Namen ist fuer einen Leser des
 * Quelltextes KEINE Zeichenkette mehr, sondern ein Stueck Text mit Loechern
 * — PHP nennt das `T_ENCAPSED_AND_WHITESPACE`, und was in den Loechern
 * steht, weiss erst die Laufzeit. Vier Spalten fallen heute genau so
 * (`2026_08_17_notarzt_erweiterung`, letzter Schritt). Sie stehen deshalb in
 * `ausnahmen.json`, mit dieser Begruendung; die Pruefung meldet sie nicht,
 * und sie tut auch nicht so, als haette sie sie gesehen.
 *
 * AUSNAHMEN stehen in `ausnahmen.json`, je mit Begruendung. Kein Ausblenden:
 * Eine ungenutzte Ausnahme ist selbst ein Befund — sonst bleibt die Liste
 * stehen, nachdem der Grund weggefallen ist.
 *
 * DIE SELBSTPROBE STEHT VOR DER PRUEFUNG, nicht daneben. Ein gruener Lauf
 * einer Pruefung, die immer gruen meldet, sieht genauso aus wie einer, der
 * nichts gefunden hat — dieselbe Ueberlegung wie bei der Integritaetswache.
 * `--selbstprobe` legt sich deshalb eine beschaedigte Kopie beider Dateien an
 * — eine Kennung aus der Vorabliste gestrichen, eine Spalte aus `schema.sql`
 * gestrichen, eine Kennung im Katalog zurueckdatiert — und verlangt, dass
 * die Pruefungen 1, 2, 4 und 5 dazu anschlagen. Sie rechnet ohne Netz und
 * braucht eine Sekunde.
 *
 * Aufruf:
 *   bash tools/quelltext/pruefen.sh migrationsregister
 *   bash tools/quelltext/pruefen.sh migrationsregister --ausfuehrlich
 *   bash tools/quelltext/pruefen.sh migrationsregister --selbstprobe
 *   bash tools/quelltext/pruefen.sh migrationsregister --katalog=… --schema=…
 *
 * Rueckgabewert: 0 = alle sieben Pruefungen sauber, 1 = mindestens ein
 * Befund, 2 = die Pruefung selbst kam nicht zustande (Datei fehlt).
 */

$wurzel     = dirname(__DIR__, 2);
$katalogDat = $wurzel . '/server/migration_lib.php';
$schemaDat  = $wurzel . '/server/schema.sql';
$ausnDat    = __DIR__ . '/migrationsregister-ausnahmen.json';

$ausfuehrlich = false;
$selbstprobe  = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--ausfuehrlich')            { $ausfuehrlich = true; continue; }
    if ($arg === '--selbstprobe')             { $selbstprobe  = true; continue; }
    if (str_starts_with($arg, '--katalog='))  { $katalogDat = substr($arg, 10); continue; }
    if (str_starts_with($arg, '--schema='))   { $schemaDat  = substr($arg, 9);  continue; }
    if (str_starts_with($arg, '--ausnahmen=')) { $ausnDat   = substr($arg, 12); continue; }
    fwrite(STDERR, "Unbekannte Angabe: $arg\n");
    exit(2);
}

if ($selbstprobe) { exit(selbstprobe($katalogDat, $schemaDat)); }

/**
 * Findet diese Pruefung ueberhaupt etwas?
 *
 * Drei Schaeden, vier erwartete Anschlaege — der dritte Schaden loest zwei
 * Pruefungen aus. Die beschaedigten Kopien liegen in einem eigenen
 * Verzeichnis unter `sys_get_temp_dir()` und werden am Ende geloescht; die
 * echten Dateien werden nicht angefasst.
 */
function selbstprobe(string $katalogDat, string $schemaDat): int
{
    $tmp = sys_get_temp_dir() . '/migregister-selbstprobe-' . getmypid();
    @mkdir($tmp, 0700, true);
    $ok = 0; $offen = 0;
    $pruefe = static function (bool $erfuellt, string $was) use (&$ok, &$offen): void {
        if ($erfuellt) { $ok++; } else { $offen++; }
        printf("  [%s] %s\n", $erfuellt ? 'ok ' : 'FEHL', $was);
    };

    try {
        $katalog = (string)file_get_contents($katalogDat);
        $schema  = (string)file_get_contents($schemaDat);

        /* Schaden 1: eine Kennung aus der Vorabliste streichen. */
        $s1 = preg_replace("/\(\s*'2026_08_31_jobs'\s*,\s*'skipped'\s*\),?/",
                           '', $schema, 1);
        /* Schaden 2: eine Spalte aus schema.sql streichen, die eine Migration anlegt. */
        $s2 = preg_replace('/^\s*logo_wahl\s+VARCHAR.*$/m', '', (string)$s1, 1);
        file_put_contents($tmp . '/schema.sql', (string)$s2);

        /* Schaden 3: eine Kennung im Katalog zurueckdatieren. Sie loest
         * gleich drei Pruefungen aus — die neue Kennung steht in keiner
         * Vorabliste (1), die alte in keinem Katalog mehr (2), und ihr Datum
         * rutscht hinter das der vorigen (4). */
        $k1 = str_replace("'2026_08_30_rechtstexte'", "'2026_07_01_zurueckdatiert'", $katalog);
        file_put_contents($tmp . '/migration_lib.php', $k1);

        file_put_contents($tmp . '/ausnahmen.json', '{"ausnahmen": []}');

        $befehl = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__)
                . ' --katalog=' . escapeshellarg($tmp . '/migration_lib.php')
                . ' --schema='  . escapeshellarg($tmp . '/schema.sql')
                . ' --ausnahmen=' . escapeshellarg($tmp . '/ausnahmen.json')
                . ' 2>&1';
        $ausgabe = (string)shell_exec($befehl);

        echo "Selbstprobe — findet die Pruefung drei eingebaute Schaeden?\n\n";
        $pruefe(str_contains($ausgabe, '[Pruefung 1]'),
                'Pruefung 1 schlaegt an (Kennung fehlt in der Vorabliste)');
        $pruefe(str_contains($ausgabe, '[Pruefung 2]'),
                'Pruefung 2 schlaegt an (Vorabliste kennt eine Kennung, die der Katalog nicht hat)');
        $pruefe(str_contains($ausgabe, '[Pruefung 4]'),
                'Pruefung 4 schlaegt an (Datum rutscht zurueck)');
        $pruefe(str_contains($ausgabe, '[Pruefung 5]'),
                'Pruefung 5 schlaegt an (Spalte fehlt in schema.sql)');
        echo "\n  erfuellt: $ok · offen: $offen\n";
    } finally {
        foreach (['migration_lib.php', 'schema.sql', 'ausnahmen.json'] as $f) {
            @unlink($tmp . '/' . $f);
        }
        @rmdir($tmp);
    }
    return $offen === 0 ? 0 : 1;
}

foreach ([$katalogDat, $schemaDat] as $d) {
    if (!is_file($d)) {
        fwrite(STDERR, "Datei fehlt: $d\n");
        exit(2);
    }
}

/* ---- Ausnahmen ----------------------------------------------------------- */

$ausnahmen = [];
if (is_file($ausnDat)) {
    $roh = json_decode((string)file_get_contents($ausnDat), true);
    if (!is_array($roh)) {
        fwrite(STDERR, "ausnahmen.json ist kein gueltiges JSON.\n");
        exit(2);
    }
    foreach ($roh['ausnahmen'] ?? [] as $a) {
        if (!isset($a['was'], $a['grund'])) {
            fwrite(STDERR, "Eine Ausnahme ohne `was` oder `grund` — beides ist Pflicht.\n");
            exit(2);
        }
        $ausnahmen[(string)$a['was']] = (string)$a['grund'];
    }
}
$benutzt = [];

/** Ist dieser Befund erklaert? Merkt sich die Benutzung fuer die Gegenprobe. */
function erklaert(string $schluessel): bool
{
    global $ausnahmen, $benutzt;
    if (!isset($ausnahmen[$schluessel])) { return false; }
    $benutzt[$schluessel] = true;
    return true;
}

/* ---- Den Katalog aus dem Quelltext lesen --------------------------------- */

/**
 * Alle Zeichenketten der Datei, in der Reihenfolge des Quelltextes.
 *
 * Doppelt quotierte Zeichenketten mit Interpolation (T_ENCAPSED_AND_WHITESPACE)
 * bleiben aussen vor: Sie kommen im Katalog nicht als DDL vor, und ein halb
 * aufgeloester Text waere schlechter als keiner.
 *
 * @return list<string>
 */
function quelltext_zeichenketten(string $datei): array
{
    $raus = [];
    foreach (token_get_all((string)file_get_contents($datei)) as $t) {
        if (!is_array($t) || $t[0] !== T_CONSTANT_ENCAPSED_STRING) { continue; }
        $s     = $t[1];
        $quote = $s[0];
        $inner = substr($s, 1, -1);
        $inner = $quote === "'"
            ? str_replace(["\\'", '\\\\'], ["'", '\\'], $inner)
            : stripcslashes($inner);
        $raus[] = $inner;
    }
    return $raus;
}

const KENNUNG_RE = '/^\d{4}_\d{2}_\d{2}_[a-z0-9_]+$/';

$ketten   = quelltext_zeichenketten($katalogDat);
$katalog  = [];            // Kennung => list<DDL>
$folge    = [];            // Kennungen in Reihenfolge, mit Doppelungen
$aktuell  = null;
foreach ($ketten as $s) {
    if (preg_match(KENNUNG_RE, $s)) {
        $aktuell = $s;
        $folge[] = $s;
        $katalog[$s] ??= [];
        continue;
    }
    if ($aktuell !== null && preg_match('/^\s*(CREATE|ALTER|DROP|RENAME)\s+(TABLE|INDEX)/i', $s)) {
        $katalog[$aktuell][] = $s;
    }
}

/* `label`, `sql` und `run` je Eintrag — ueber den Rohtext, weil die
 * Zeichenkettenfolge oben die Schluessel nicht mehr von den Werten
 * unterscheidet. Ein Eintrag beginnt bei seiner Kennung und endet vor der
 * naechsten. */
$roh = (string)file_get_contents($katalogDat);
$bloecke = [];
$vorher = null; $vorherPos = null;
foreach ($folge as $k) {
    $pos = strpos($roh, "'" . $k . "'");
    if ($pos === false) { continue; }
    if ($vorher !== null) { $bloecke[$vorher] = substr($roh, $vorherPos, $pos - $vorherPos); }
    $vorher = $k; $vorherPos = $pos;
}
if ($vorher !== null) { $bloecke[$vorher] = substr($roh, $vorherPos); }

/* ---- Die Vorabliste aus schema.sql --------------------------------------- */

$schema = (string)file_get_contents($schemaDat);
$vorab  = [];
if (preg_match_all("/\(\s*'(\d{4}_\d{2}_\d{2}_[a-z0-9_]+)'\s*,\s*'[a-z]+'\s*\)/", $schema, $m)) {
    $vorab = $m[1];
}

/* ---- Tabellen und Spalten aus schema.sql --------------------------------- */

/**
 * @return array<string, array<string,true>> Tabelle => Spaltenname => true
 */
function schema_tabellen(string $sql): array
{
    $raus = [];
    if (!preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?\s*\((.*?)\n\)\s*ENGINE/is', $sql, $m, PREG_SET_ORDER)) {
        return $raus;
    }
    foreach ($m as $t) {
        $tab = strtolower($t[1]);
        $raus[$tab] = [];
        foreach (explode("\n", $t[2]) as $zeile) {
            $zeile = trim(preg_replace('/--.*$/', '', $zeile) ?? '');
            if ($zeile === '') { continue; }
            if (!preg_match('/^`?(\w+)`?\s+/', $zeile, $s)) { continue; }
            $wort = strtoupper($s[1]);
            if (in_array($wort, ['PRIMARY', 'UNIQUE', 'KEY', 'INDEX', 'CONSTRAINT',
                                 'FOREIGN', 'FULLTEXT', 'CHECK'], true)) { continue; }
            $raus[$tab][strtolower($s[1])] = true;
        }
    }
    return $raus;
}

$schemaTab = schema_tabellen($schema);

/* ---- Den Katalog durchrechnen -------------------------------------------- */

/** Spaltennamen aus dem Rumpf eines CREATE TABLE. @return list<string> */
function spalten_aus_rumpf(string $rumpf): array
{
    $raus = [];
    foreach (explode("\n", $rumpf) as $zeile) {
        $zeile = trim(preg_replace('/--.*$/', '', $zeile) ?? '');
        if ($zeile === '' || !preg_match('/^`?(\w+)`?\s+/', $zeile, $s)) { continue; }
        $wort = strtoupper($s[1]);
        /* REFERENCES GEHOERT DAZU, und zwar wegen des Zeilenumbruchs.
         *
         * Diese Funktion liest ZEILENWEISE. Ein Fremdschluessel, der auf zwei
         * Zeilen steht — und das tut er, sobald er laenger wird —, faengt in
         * der zweiten mit `REFERENCES` an:
         *
         *     CONSTRAINT fk_kew_user FOREIGN KEY (user_id)
         *       REFERENCES users (id) ON DELETE CASCADE
         *
         * Die erste Zeile faengt `CONSTRAINT` ab, die zweite wurde als Spalte
         * `references` gelesen. Gefunden am 17.09.2026 an
         * `konto_einwilligungen` (P5b/AP4): Der Schritt „Migrationsregister"
         * in Stufe 1 war seit Web 20.19.0 rot, gemeldet wurde eine Spalte,
         * die es nie gab.
         *
         * EINE SPALTE NAMENS `references` GAEBE ES NUR MIT RUECKSTRICHEN
         * (`references` ist in SQL reserviert). Die wuerde hier ebenfalls
         * uebersprungen — ein Preis, den dieses Projekt zahlen kann: Es hat
         * keine solche Spalte, und eine anzulegen waere fuer sich genommen
         * schon eine schlechte Entscheidung. */
        if (in_array($wort, ['PRIMARY', 'UNIQUE', 'KEY', 'INDEX', 'CONSTRAINT',
                             'FOREIGN', 'FULLTEXT', 'CHECK', 'REFERENCES'], true)) { continue; }
        $raus[] = strtolower($s[1]);
    }
    return $raus;
}

$gerechnet = [];    // Tabelle => Spalte => true   (Endstand nach allen Migrationen)
$geloescht = [];    // 'tabelle' oder 'tabelle.spalte' => Kennung der letzten Loeschung

foreach ($folge as $kennung) {
    foreach ($katalog[$kennung] ?? [] as $stmt) {
        $s = preg_replace('/\s+/', ' ', trim($stmt)) ?? '';

        if (preg_match('/^CREATE TABLE (?:IF NOT EXISTS )?`?(\w+)`?\s*\((.*)\)/is', $stmt, $m)) {
            $tab = strtolower($m[1]);
            $gerechnet[$tab] = [];
            foreach (spalten_aus_rumpf($m[2]) as $sp) { $gerechnet[$tab][$sp] = true; }
            unset($geloescht[$tab]);
            continue;
        }
        if (preg_match('/^RENAME TABLE `?(\w+)`? TO `?(\w+)`?/i', $s, $m)) {
            $alt = strtolower($m[1]); $neu = strtolower($m[2]);
            $gerechnet[$neu] = $gerechnet[$alt] ?? [];
            unset($gerechnet[$alt]);
            $geloescht[$alt] = $kennung;
            unset($geloescht[$neu]);
            continue;
        }
        if (preg_match('/^DROP TABLE (?:IF EXISTS )?`?(\w+)`?/i', $s, $m)) {
            $tab = strtolower($m[1]);
            unset($gerechnet[$tab]);
            $geloescht[$tab] = $kennung;
            continue;
        }
        if (preg_match('/^ALTER TABLE `?(\w+)`?\s+(.*)$/is', $s, $m)) {
            $tab   = strtolower($m[1]);
            $teile = preg_split('/,\s*(?=ADD |DROP |CHANGE |MODIFY |RENAME )/i', $m[2]) ?: [];
            foreach ($teile as $teil) {
                $teil = trim($teil);
                if (preg_match('/^ADD (?:COLUMN )?`?(\w+)`?\s+\w/i', $teil, $c)) {
                    $wort = strtoupper($c[1]);
                    if (in_array($wort, ['INDEX', 'KEY', 'UNIQUE', 'PRIMARY', 'CONSTRAINT',
                                         'FOREIGN', 'FULLTEXT'], true)) { continue; }
                    $sp = strtolower($c[1]);
                    $gerechnet[$tab][$sp] = true;
                    unset($geloescht[$tab . '.' . $sp]);
                } elseif (preg_match('/^DROP (?:COLUMN )?`?(\w+)`?/i', $teil, $c)) {
                    $wort = strtoupper($c[1]);
                    if (in_array($wort, ['INDEX', 'KEY', 'PRIMARY', 'FOREIGN', 'CONSTRAINT'], true)) { continue; }
                    $sp = strtolower($c[1]);
                    unset($gerechnet[$tab][$sp]);
                    $geloescht[$tab . '.' . $sp] = $kennung;
                } elseif (preg_match('/^CHANGE (?:COLUMN )?`?(\w+)`?\s+`?(\w+)`?/i', $teil, $c)) {
                    $alt = strtolower($c[1]); $neu = strtolower($c[2]);
                    unset($gerechnet[$tab][$alt]);
                    $gerechnet[$tab][$neu] = true;
                    if ($alt !== $neu) { $geloescht[$tab . '.' . $alt] = $kennung; }
                    unset($geloescht[$tab . '.' . $neu]);
                }
            }
        }
    }
}

/* ---- Die sieben Pruefungen ----------------------------------------------- */

$befunde = [];
$zahlen  = [];

/** Einen Befund verbuchen, sofern er nicht erklaert ist. */
function befund(string $pruefung, string $schluessel, string $text): void
{
    global $befunde;
    if (erklaert($schluessel)) { return; }
    $befunde[] = [$pruefung, $text];
}

// 1 + 2: beide Listen gegeneinander
$kSet = array_fill_keys($folge, true);
$vSet = array_fill_keys($vorab, true);
foreach ($folge as $k) {
    if (!isset($vSet[$k])) {
        befund('1', 'vorab:' . $k,
               "Kennung `$k` steht im Katalog, aber nicht in der Vorabliste von schema.sql.");
    }
}
foreach ($vorab as $v) {
    if (!isset($kSet[$v])) {
        befund('2', 'katalog:' . $v,
               "Kennung `$v` steht in der Vorabliste von schema.sql, aber nicht im Katalog.");
    }
}
$zahlen['Kennungen im Katalog']       = count($folge);
$zahlen['Kennungen in der Vorabliste'] = count($vorab);

// 3: Doppelungen
foreach ([['Katalog', $folge], ['Vorabliste', $vorab]] as [$wo, $liste]) {
    $z = array_count_values($liste);
    foreach ($z as $k => $n) {
        if ($n > 1) {
            befund('3', 'doppelt:' . $wo . ':' . $k, "Kennung `$k` steht $n-mal im $wo.");
        }
    }
}

// 4: aufsteigend
/* NUR DAS DATUM, NICHT DAS STICHWORT. Innerhalb eines Tages ist die
 * Reihenfolge eine Abhaengigkeit und keine Sortierung: `2026_07_20_kopplung`
 * steht mit Absicht hinter `2026_07_20_stammdaten_defaults`. Sechs solche
 * Paare stehen heute im Katalog. Was nicht rutschen darf, ist der TAG —
 * eine Migration, die vor einer aelteren einsortiert wird, laeuft auf einer
 * fremden Installation vor ihrer Voraussetzung. */
$vorige = null;
foreach ($folge as $k) {
    $tag = substr($k, 0, 10);
    if ($vorige !== null && strcmp($tag, substr($vorige, 0, 10)) < 0) {
        befund('4', 'folge:' . $k,
               "Kennung `$k` traegt ein aelteres Datum als `$vorige` davor — "
             . 'die Reihenfolge des Katalogs ist der Mechanismus.');
    }
    $vorige = $k;
}

// 5: Angelegtes steht im Schema
$gepruefteSpalten = 0;
foreach ($gerechnet as $tab => $spalten) {
    if (!isset($schemaTab[$tab])) {
        befund('5', 'fehlt:' . $tab,
               "Der Katalog legt die Tabelle `$tab` an; schema.sql kennt sie nicht — "
             . 'eine Neuinstallation haette sie nicht.');
        continue;
    }
    foreach (array_keys($spalten) as $sp) {
        $gepruefteSpalten++;
        if (!isset($schemaTab[$tab][$sp])) {
            befund('5', 'fehlt:' . $tab . '.' . $sp,
                   "Der Katalog legt `$tab.$sp` an; schema.sql kennt die Spalte nicht.");
        }
    }
}
$zahlen['Tabellen aus dem Katalog'] = count($gerechnet);
$zahlen['Spalten aus dem Katalog']  = $gepruefteSpalten;

// 6: Geloeschtes steht nicht mehr im Schema
foreach ($geloescht as $was => $kennung) {
    [$tab, $sp] = array_pad(explode('.', $was, 2), 2, null);
    if ($sp === null) {
        if (isset($schemaTab[$tab])) {
            befund('6', 'noch-da:' . $tab,
                   "`$kennung` loescht die Tabelle `$tab`; schema.sql fuehrt sie weiter.");
        }
        continue;
    }
    if (isset($schemaTab[$tab][$sp])) {
        befund('6', 'noch-da:' . $tab . '.' . $sp,
               "`$kennung` loescht `$tab.$sp`; schema.sql fuehrt die Spalte weiter.");
    }
}
$zahlen['Loeschungen im Katalog'] = count($geloescht);

// 7: Pflichtangaben je Eintrag
foreach ($bloecke as $k => $text) {
    if (!str_contains($text, "'label'")) {
        befund('7', 'label:' . $k, "Eintrag `$k` hat kein `label`.");
    }
    if (!str_contains($text, "'sql'") && !str_contains($text, "'run'")) {
        befund('7', 'tut-nichts:' . $k, "Eintrag `$k` hat weder `sql` noch `run`.");
    }
}

/* Ungenutzte Ausnahmen sind selbst ein Befund. */
$ungenutzt = array_diff_key($ausnahmen, $benutzt);

/* ---- Bericht ------------------------------------------------------------- */

echo "Migrationsregister — schema.sql gegen migration_lib.php\n\n";
foreach ($zahlen as $was => $n) {
    printf("  %-32s %d\n", $was . ':', $n);
}
printf("  %-32s %d\n", 'Ausnahmen (erklaerte Befunde):', count($benutzt));
echo "\n";

if ($befunde) {
    echo 'BEFUNDE: ' . count($befunde) . "\n";
    foreach ($befunde as [$p, $t]) { echo "  [Pruefung $p] $t\n"; }
    echo "\n";
} else {
    echo "Befunde: 0\n\n";
}

if ($ungenutzt) {
    echo 'UNGENUTZTE AUSNAHMEN: ' . count($ungenutzt) . "\n";
    foreach ($ungenutzt as $was => $grund) { echo "  $was — $grund\n"; }
    echo "  Eine Ausnahme ohne Befund hat ihren Grund verloren und gehoert entfernt.\n\n";
} else {
    echo "Ungenutzte Ausnahmen: 0\n\n";
}

if ($ausfuehrlich) {
    echo "Kennungen des Katalogs, in Reihenfolge:\n";
    foreach ($folge as $k) { echo "  $k\n"; }
    echo "\n";
}

exit($befunde || $ungenutzt ? 1 : 0);
