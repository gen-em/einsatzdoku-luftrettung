<?php
declare(strict_types=1);
/**
 * Komplett-Backup der Installation (S2/AP8, E-S2-19 bis E-S2-21).
 *
 * WAS ES IST UND WOGEGEN ES HILFT. Das Admin-Backup (`adminbackup_lib.php`)
 * sichert ein KONTO. Dieses hier sichert die INSTALLATION: alle Konten,
 * Stammdaten, Geraete, Schluesselhuellen, `app_state`, den Migrationsstand —
 * jede Tabelle, die in dieser Datenbank steht. Der Fall, gegen den es hilft,
 * ist nicht „ein Konto hat sich vertan", sondern „der Webspace ist weg".
 *
 * WAS NICHT DRIN IST: `config.php`. Sie traegt das Datenbankpasswort und den
 * Serverschluessel — also genau das, womit sich diese Datei oeffnen laesst.
 * Beides in dieselbe Datei zu legen hiesse, das Schloss an den Schluessel zu
 * binden. `config.php` gehoert ins getrennt aufbewahrte WIEDERANLAUFPAKET
 * (docs/Technik.md, Abschnitt 7). Ebenfalls nicht drin: die Dateiablage unter
 * `sicherungen/` — die Kontopakete sichern nichts, was nicht ohnehin in der
 * Datenbank steht, und wuerden die Datei vervielfachen.
 *
 * WARUM EIN EIGENER DUMP UND NICHT `mysqldump` (E-S2-20). Auf geteiltem
 * Webspace gibt es keine Kommandozeile und kein `exec()`; `mysqldump` ist dort
 * nicht vorhanden und nicht nachruestbar. Der Dump entsteht deshalb in PHP,
 * ueber genau die Verbindung, die die Anwendung ohnehin hat.
 *
 * DIE FORM IST ABSICHTLICH SCHLICHT: EIN STATEMENT JE ZEILE. Damit laesst sich
 * die Datei zeilenweise abarbeiten — vom Rueckweg dieser Anwendung
 * (`wiederherstellen.php`) genauso wie von `mysql` oder phpMyAdmin. Ein
 * mehrzeiliges Statement braeuchte einen SQL-Zerleger, und ein selbstgebauter
 * SQL-Zerleger ist die Sorte Code, die genau einmal falsch liegt: naemlich
 * dann, wenn ein Semikolon in einer Zeichenkette steht.
 *
 * DREI SCHICHTEN, IN DIESER REIHENFOLGE:
 *
 *   1. SQL-Text      ein Statement je Zeile, INSERT-Stapel <= 1 MB
 *   2. gzip          je Haeppchen ein eigenes gzip-Glied (siehe unten)
 *   3. EDKOMP1       AES-256-GCM je Block, Zaehler in den Zusatzdaten
 *
 * WARUM JE HAEPPCHEN EIN EIGENES GZIP-GLIED. Der Dump entsteht in Haeppchen
 * ueber mehrere Anfragen hinweg (E-S2-20: „nie als Array am Stueck"). Ein
 * `deflate_init()`-Zustand laesst sich zwischen zwei Anfragen nicht
 * aufbewahren — er ist keine Zahl, sondern ein Fenster ueber die letzten 32 KB.
 * Deshalb schliesst jedes Haeppchen sein gzip-Glied ab und das naechste
 * haengt ein neues an. Aneinandergehaengte gzip-Glieder sind ein gueltiges
 * gzip: `gunzip`, `zcat` und PHPs `gzopen()`/`gzgets()` lesen ueber die
 * Grenzen hinweg. Der Preis ist ein neues Woerterbuch je Haeppchen, also
 * einige Promille Groesse — gemessen an einem Haeppchen von rund 1 MB.
 *
 * WARUM DIE VERSIEGELUNG EIN ZWEITER GANG IST und nicht im selben Zug
 * geschieht: Der Dump waechst zeilenweise, die Versiegelung arbeitet in
 * Bloecken fester Groesse. Beides zugleich hiesse, einen halb gefuellten
 * Block zwischen zwei Anfragen aufbewahren zu muessen. So ist der Zustand der
 * Versiegelung eine einzige Zahl — der Blockindex —, und ein Wiederanlauf
 * setzt exakt dort auf, wo er aufgehoert hat. Der Klartext-Dump liegt dabei
 * fuer die Dauer des Baus im Bauordner; er wird geloescht, sobald die
 * versiegelte Fassung steht.
 *
 * DER SCHNAPPSCHUSS IST NICHT SCHARF, UND DAS WIRD HIER GESAGT. `mysqldump`
 * haelt mit `--single-transaction` einen Lesestand ueber den ganzen Lauf.
 * Das geht nur INNERHALB EINER VERBINDUNG, und dieses Backup laeuft ueber
 * viele Anfragen. Eine Zeile, die waehrend des Laufs entsteht, kann deshalb
 * enthalten sein oder nicht — je nachdem, ob der Cursor an ihrer Stelle schon
 * vorbei war. Was NICHT passieren kann, ist eine uebersprungene Altzeile: Der
 * Cursor laeuft ueber den Primaerschluessel und nicht ueber `LIMIT/OFFSET`,
 * und ein geloeschter Vorgaenger verschiebt ihn deshalb nicht. Fuer den
 * Zweck — „der Webspace ist weg" — ist das die richtige Abwaegung; wer einen
 * scharfen Schnappschuss braucht, faehrt das Backup nachts.
 *
 * DER CURSOR IST AUFGEFAECHERT UND NICHT EIN ZEILENKONSTRUKTOR, und das ist
 * gemessen und kein Geschmack (S2/AP8, 5000er-Bestand, `track_points` mit
 * 917 331 Zeilen):
 *
 *   WHERE (a,b) > (?,?)                          type=index   0,1486 s
 *   WHERE a > ? OR (a = ? AND b > ?)             type=range   0,0010 s
 *
 * MariaDB macht aus dem Zeilenkonstruktor keinen Bereichszugriff, sondern
 * laeuft den Index von vorn ab — bei 459 Haeppchen also 459-mal die halbe
 * Tabelle. EINE AUSNAHME: Steht eine ENUM-Spalte VORN im Primaerschluessel,
 * hilft auch das Auffaechern nichts (type=index, 0,0125 s); wird sie dagegen
 * mit `=` festgenagelt, greift der Bereichszugriff wieder (0,0005 s). Deshalb
 * werden fuehrende ENUM-Spalten hier ueber ihre Werteliste durchlaufen und
 * der Cursor gilt nur fuer den Rest. Das betrifft `track_points` und
 * `track_blobs` mit je zwei Werten, kostet also eine Abfrage mehr je Tabelle.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';

/* ---- Ablage --------------------------------------------------------------- */

/** Unterordner in `sicherungen/`. Fester Name — der Zufall steckt im Dateinamen. */
const KOMP_ORDNER = 'komplett';

/** Praefix eines Bauordners. Ein Punkt voran: kein Stand, sondern eine Baustelle. */
const KOMP_BAU_PRAEFIX = '.bau-';

/**
 * Wie viele Staende aufbewahrt werden (Vorgabe).
 *
 * DIESELBE ZWEI WIE BEI DEN KONTO-BACKUPS (E-S2-14) und aus demselben
 * Grund: Zwei Staende erlauben den Griff auf den vorletzten, wenn der letzte
 * beim Erzeugen etwas abbekommen hat. Drei erlauben das auch — und kosten ein
 * weiteres Mal die volle Datenbank auf einem Webspace, dessen Platz die
 * knappste Groesse der ganzen Anlage ist (E-S2-15).
 */
const KOMP_AUFBEWAHRUNG_VORGABE = 2;

/** Wurzel der Komplett-Backups: `sicherungen/komplett/`. */
function komp_wurzel(): string
{
    return edbak_wurzel() . '/' . KOMP_ORDNER;
}

/**
 * Ablage bereitstellen. Liefert `[bool, ?string]` — wie `edbak_ablage_bereit()`.
 *
 * DIESELBE RÜCKGABEFORM WIE DORT, und zwar aus Erfahrung: Die erste Fassung
 * gab hier `['ok' => bool, 'meldung' => string]` zurück und reichte das Ergebnis
 * `edbak_ablage_bereit()` unbesehen durch. Das ist eine Liste. Herausgekommen
 * ist eine Fehlermeldung ohne Text — die schlechteste Sorte.
 *
 * Die `.htaccess` der Wurzel (`sicherungen/.htaccess`) deckt diesen Ordner
 * mit ab — sie gilt für den ganzen Teilbaum. Die zweite Schranke ist wie bei
 * den Kontopaketen der Name: Er trägt 32 Bit Zufall und ist damit auch dann
 * nicht zu erraten, wenn die `.htaccess` nicht greift.
 *
 * @return array{0:bool,1:?string}
 */
function komp_bereit(): array
{
    [$ok, $meldung] = edbak_ablage_bereit();
    if (!$ok) { return [false, $meldung]; }
    $pfad = komp_wurzel();
    if (!is_dir($pfad) && !@mkdir($pfad, 0770, true) && !is_dir($pfad)) {
        return [false, 'Der Ordner „' . KOMP_ORDNER
            . '" lässt sich nicht anlegen: ' . $pfad];
    }
    if (!is_writable($pfad)) {
        return [false, 'Der Ordner „' . KOMP_ORDNER
            . '" ist nicht beschreibbar: ' . $pfad];
    }
    return [true, null];
}

/** Ein Dateiname: Zeitpunkt (UTC, sortierbar) plus 32 Bit Zufall. */
function komp_dateiname(): string
{
    return gmdate('Y-m-d\TH-i-s\Z') . '_' . bin2hex(random_bytes(4)) . '.edk';
}

/** Passt der Name zum Muster? Schuetzt jeden Pfad, der aus einer Eingabe kommt. */
function komp_name_gueltig(string $name): bool
{
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}Z_[0-9a-f]{8}\.edk$/', $name);
}

/** Der Zeitpunkt aus dem Namen als ISO-8601. `null`, wenn der Name nicht passt. */
function komp_zeit_aus_name(string $name): ?string
{
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2})-(\d{2})-(\d{2})Z_/', $name, $t)) {
        return null;
    }
    return $t[1] . 'T' . $t[2] . ':' . $t[3] . ':' . $t[4] . 'Z';
}

/**
 * Die vorhandenen Staende, neueste zuerst.
 *
 * @return list<array{datei:string,groesse:int,zeit:?string}>
 */
function komp_staende(): array
{
    $pfad = komp_wurzel();
    if (!is_dir($pfad)) { return []; }
    $raus = [];
    foreach (scandir($pfad) ?: [] as $n) {
        if (!komp_name_gueltig($n)) { continue; }
        $voll = $pfad . '/' . $n;
        if (!is_file($voll)) { continue; }
        $raus[] = ['datei' => $n, 'groesse' => (int)@filesize($voll),
                   'zeit' => komp_zeit_aus_name($n)];
    }
    usort($raus, fn(array $a, array $b): int => strcmp($b['datei'], $a['datei']));
    return $raus;
}

/** Wie viele Staende aufbewahrt werden. Einstellbar wie bei den Konto-Backups. */
function komp_aufbewahrung(): int
{
    $v = (int)(edbak_marke_lesen('komplett_aufbewahrung') ?? 0);
    return $v > 0 ? $v : KOMP_AUFBEWAHRUNG_VORGABE;
}

/** Die Aufbewahrung setzen. */
function komp_aufbewahrung_setzen(int $n): bool
{
    if ($n < 1 || $n > 20) { return false; }
    return edbak_marke_setzen('komplett_aufbewahrung', (string)$n);
}

/**
 * Alte Staende verdraengen. Liefert die Namen der geloeschten Dateien.
 *
 * WIE BEI DEN KONTOPAKETEN WIRD NICHT STILL GELOESCHT: Die Rueckmeldung des
 * Laufs nennt jede verdraengte Datei. Wer beim Lesen des Berichts stutzt, hat
 * die Gelegenheit, die Aufbewahrung hochzusetzen, BEVOR der naechste Lauf den
 * naechsten Stand nimmt.
 */
function komp_verdraengen(): array
{
    $behalten = komp_aufbewahrung();
    $staende  = komp_staende();
    $weg = [];
    foreach (array_slice($staende, $behalten) as $s) {
        if (@unlink(komp_wurzel() . '/' . $s['datei'])) { $weg[] = $s['datei']; }
    }
    return $weg;
}

/** Einen Stand loeschen. */
function komp_loeschen(string $datei): bool
{
    if (!komp_name_gueltig($datei)) { return false; }
    $voll = komp_wurzel() . '/' . $datei;
    return is_file($voll) && @unlink($voll);
}

/** Einen Bauordner samt Inhalt entfernen. */
function komp_bau_weg(string $bau): bool
{
    if (!preg_match('/^' . preg_quote(KOMP_BAU_PRAEFIX, '/') . '[0-9a-f]{8}$/', $bau)) {
        return false;
    }
    $pfad = komp_wurzel() . '/' . $bau;
    if (!is_dir($pfad)) { return true; }
    foreach (scandir($pfad) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        @unlink($pfad . '/' . $n);
    }
    return @rmdir($pfad);
}

/**
 * Liegengebliebene Bauordner aufraeumen. Liefert die Zahl der entfernten.
 *
 * Ein Bauordner ueberlebt einen Absturz mitten im Lauf. Er gehoert weg,
 * sobald kein Auftrag mehr auf ihn zeigt — sonst wuechse die Ablage mit jedem
 * misslungenen Versuch um eine ganze Datenbank.
 */
function komp_baureste_aufraeumen(?string $ausser = null): int
{
    $pfad = komp_wurzel();
    if (!is_dir($pfad)) { return 0; }
    $n = 0;
    foreach (scandir($pfad) ?: [] as $b) {
        if (!str_starts_with($b, KOMP_BAU_PRAEFIX)) { continue; }
        if ($ausser !== null && $b === $ausser) { continue; }
        if (komp_bau_weg($b)) { $n++; }
    }
    return $n;
}

/* ---- Tabellen, Reihenfolge, Cursor ---------------------------------------- */

/** Datentypen, die als Binaerdaten hexadezimal ausgegeben werden. */
const KOMP_BINAER = ['blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary'];

/** Datentypen, deren Werte ohne Anfuehrungszeichen ausgegeben werden duerfen. */
const KOMP_ZAHL = ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint',
                   'decimal', 'numeric', 'float', 'double', 'year'];

/**
 * Die Tabellen dieser Datenbank mit allem, was der Dump ueber sie wissen muss.
 *
 * SIE WERDEN GEFRAGT UND NICHT AUFGEZAEHLT. Eine fest verdrahtete Liste waere
 * genau einmal richtig — naemlich bis zur naechsten Migration, die eine
 * Tabelle hinzufuegt. Ein Komplett-Backup, das eine Tabelle auslaesst, ist
 * schlimmer als keines: Es sieht vollstaendig aus.
 *
 * @return array<string,array> je Tabelle:
 *   spalten  [name => ['binaer' => bool, 'zahl' => bool]]
 *   fest     [name => [werte...]]   fuehrende ENUM-Spalten des PK
 *   cursor   [name, ...]            der Rest des PK
 *   pk       [name, ...]            der ganze PK (leer = keiner)
 */
function komp_tabellen(PDO $pdo): array
{
    $namen = [];
    foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $r) {
        if (strtoupper((string)$r[1]) !== 'BASE TABLE') { continue; }
        $namen[] = (string)$r[0];
    }

    $st = $pdo->prepare('SELECT column_name, data_type, column_type
                           FROM information_schema.columns
                          WHERE table_schema = DATABASE() AND table_name = ?
                          ORDER BY ordinal_position');
    $raus = [];
    foreach ($namen as $t) {
        $st->execute([$t]);
        $spalten = [];
        $enums   = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $name = (string)$c['column_name'];
            $typ  = strtolower((string)$c['data_type']);
            $spalten[$name] = [
                'binaer' => in_array($typ, KOMP_BINAER, true),
                'zahl'   => in_array($typ, KOMP_ZAHL, true),
            ];
            if ($typ === 'enum') { $enums[$name] = komp_enum_werte((string)$c['column_type']); }
        }

        $pk = [];
        foreach ($pdo->query("SHOW KEYS FROM `" . str_replace('`', '``', $t)
                             . "` WHERE Key_name = 'PRIMARY'")->fetchAll(PDO::FETCH_ASSOC) as $k) {
            $pk[(int)$k['Seq_in_index']] = (string)$k['Column_name'];
        }
        ksort($pk);
        $pk = array_values($pk);

        /* FUEHRENDE ENUM-SPALTEN WERDEN FESTGENAGELT, der Rest wird zum
         * Cursor. Warum: siehe Kopf dieser Datei — mit einer ENUM-Spalte vorn
         * findet MariaDB keinen Bereichszugriff, und der Cursor laeuft den
         * Index jedes Mal von vorn ab. Nur FUEHRENDE: Steht eine ENUM-Spalte
         * hinten, stoert sie den Bereichszugriff nicht. */
        $fest = [];
        $cursor = $pk;
        while ($cursor !== [] && isset($enums[$cursor[0]])) {
            $fest[$cursor[0]] = $enums[$cursor[0]];
            array_shift($cursor);
        }

        $raus[$t] = ['spalten' => $spalten, 'fest' => $fest,
                     'cursor' => $cursor, 'pk' => $pk];
    }
    return $raus;
}

/** Die Werte einer ENUM-Deklaration: `enum('a','b')` -> ['a','b']. */
function komp_enum_werte(string $columnType): array
{
    if (!preg_match("/^enum\\((.*)\\)$/is", trim($columnType), $t)) { return []; }
    $werte = [];
    if (preg_match_all("/'((?:[^']|'')*)'/", $t[1], $tr)) {
        foreach ($tr[1] as $w) { $werte[] = str_replace("''", "'", $w); }
    }
    return $werte;
}

/**
 * Die Tabellen in einspielbarer Reihenfolge (E-S2-20).
 *
 * Topologisch nach Fremdschluesseln: Was verwiesen wird, kommt vorher. Die
 * Datei setzt zwar `FOREIGN_KEY_CHECKS = 0` — ohne das koennte auch
 * `mysqldump` keine Ringe abbilden —, aber ein Dump, dessen Reihenfolge
 * SCHON stimmt, laesst sich auch dann einspielen, wenn jemand die Kopfzeilen
 * abschneidet oder ein Werkzeug sie ueberschreibt. Der Guertel ersetzt die
 * Hosentraeger nicht.
 *
 * Bleibt am Ende etwas uebrig (ein Ring), wird es alphabetisch angehaengt und
 * im Dump mit einer Zeile vermerkt — nicht stillschweigend sortiert.
 */
function komp_reihenfolge(PDO $pdo, array $tabellen): array
{
    $namen = array_keys($tabellen);
    sort($namen);
    $braucht = array_fill_keys($namen, []);
    $q = $pdo->query('SELECT DISTINCT table_name, referenced_table_name
                        FROM information_schema.key_column_usage
                       WHERE table_schema = DATABASE()
                         AND referenced_table_name IS NOT NULL');
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $f) {
        $von = (string)$f['table_name'];
        $auf = (string)$f['referenced_table_name'];
        if ($von === $auf) { continue; }                       // Selbstbezug: kein Hindernis
        if (!isset($braucht[$von]) || !isset($braucht[$auf])) { continue; }
        $braucht[$von][$auf] = true;
    }

    $raus = []; $erledigt = [];
    $runde = 0;
    while (count($raus) < count($namen) && $runde++ < count($namen) + 1) {
        $fortschritt = false;
        foreach ($namen as $t) {
            if (isset($erledigt[$t])) { continue; }
            foreach (array_keys($braucht[$t]) as $auf) {
                if (!isset($erledigt[$auf])) { continue 2; }
            }
            $raus[] = $t; $erledigt[$t] = true; $fortschritt = true;
        }
        if (!$fortschritt) { break; }
    }
    $rest = [];
    foreach ($namen as $t) { if (!isset($erledigt[$t])) { $rest[] = $t; } }
    return ['reihenfolge' => array_merge($raus, $rest), 'ring' => $rest];
}

/* ---- SQL-Literale --------------------------------------------------------- */

/**
 * Eine Zeichenkette als SQL-Literal — und NIEMALS mit einem echten Zeilenumbruch.
 *
 * Das ist die Bedingung, auf der die ganze Form steht: „ein Statement je
 * Zeile". Ein Zeilenumbruch mitten in einem Wert wuerde die Zeile teilen, und
 * die zweite Haelfte waere fuer jeden zeilenweisen Leser ein Statement fuer
 * sich — eines, das nicht laeuft, und im schlimmsten Fall eines, das laeuft.
 *
 * NICHT `PDO::quote()`: Das braeuchte eine offene Verbindung (die
 * Wiederherstellung hat spaeter eine andere) und ist an die Zeichensatzlage
 * der Verbindung gebunden. Hier steht stattdessen eine Abbildung, die man
 * lesen und pruefen kann.
 *
 * Die Datei setzt dazu passend `SQL_MODE` ohne `NO_BACKSLASH_ESCAPES`.
 */
function komp_quote(string $s): string
{
    return "'" . strtr($s, [
        "\\"   => "\\\\",
        "'"    => "\\'",
        "\""   => "\\\"",
        "\n"   => "\\n",
        "\r"   => "\\r",
        "\0"   => "\\0",
        "\x1a" => "\\Z",
    ]) . "'";
}

/** Ein Feldwert als SQL-Literal. */
function komp_wert(mixed $v, bool $binaer, bool $zahl): string
{
    if ($v === null) { return 'NULL'; }
    $s = (string)$v;
    if ($binaer) {
        /* `0x` ohne Ziffern ist kein gueltiges Literal — eine leere
         * Binaerspalte wird deshalb zur leeren Zeichenkette. */
        return $s === '' ? "''" : '0x' . bin2hex($s);
    }
    if ($zahl && preg_match('/^-?(?:\d+(?:\.\d+)?|\.\d+)(?:[eE][-+]?\d+)?$/', $s)) {
        return $s;
    }
    return komp_quote($s);
}

/* ---- Der Dump in Haeppchen ------------------------------------------------ */

/** Groesse eines INSERT-Stapels in Byte (E-S2-20: „INSERT-Stapel <= 1 MB"). */
const KOMP_STAPEL_BYTES = 1048576;

/** Zeilen je Abfrage — und deutlich weniger, wenn eine Binaerspalte dabei ist. */
const KOMP_ZEILEN_BLOCK      = 2000;
const KOMP_ZEILEN_BLOCK_BLOB = 200;

/** Der Dateiname des Klartext-Dumps im Bauordner. */
const KOMP_ROHNAME = 'dump.sql.gz';

/** Die Zeile, an der ein vollstaendiger Dump zu erkennen ist. */
const KOMP_ENDMARKE = '-- EDKOMP-ENDE';

/**
 * Ein Haeppchen Dump. Aendert `$z` und liefert ['zeilen' => int, 'fertig' => bool].
 *
 * `fertig` heisst hier: der SQL-Text ist vollstaendig. Die Versiegelung ist
 * ein eigener Gang (`komp_siegel_schub()`).
 */
function komp_dump_schub(PDO $pdo, array &$z, callable $zeitLinks, float $reserve): array
{
    $bauPfad = komp_wurzel() . '/' . $z['bau'];
    if (!is_dir($bauPfad) && !@mkdir($bauPfad, 0770, true) && !is_dir($bauPfad)) {
        throw new RuntimeException('Der Bauordner liess sich nicht anlegen: ' . $bauPfad);
    }
    $roh = $bauPfad . '/' . KOMP_ROHNAME;

    /* ERST ABSCHNEIDEN, DANN ANHAENGEN — und das ist keine Vorsicht, sondern
     * die Behebung eines Datenverlusts, den die erste Fassung gehabt haette.
     *
     * Der Zustand wird vom Job-Rahmen erst NACH einem geglueckten Haeppchen
     * gespeichert. Bricht ein Haeppchen mittendrin ab (Zeitlimit, Absturz),
     * stehen seine Zeilen schon in der Datei, der Zustand zeigt aber davor.
     * Der naechste Lauf schriebe sie ein zweites Mal — samt `DROP TABLE` und
     * `CREATE TABLE` der gerade laufenden Tabelle. Beim Einspielen wuerde
     * dieses zweite `DROP` genau das wegwerfen, was das erste Haeppchen
     * eingefuegt hat: ein Backup, das vollstaendig aussieht und es nicht
     * ist.
     *
     * Deshalb fuehrt der ZUSTAND die Laenge des gueltigen Teils, und jedes
     * Haeppchen schneidet zuerst darauf zurueck. Das geht, weil jedes
     * Haeppchen seine gzip-Glieder abschliesst: Die gemerkte Laenge ist immer
     * eine Gliedgrenze. */
    $gueltig = (int)($z['roh_bytes'] ?? 0);
    clearstatcache(true, $roh);
    $da = is_file($roh) ? (int)filesize($roh) : 0;
    if ($da > $gueltig) {
        $fh = fopen($roh, 'r+b');
        if ($fh !== false) { ftruncate($fh, $gueltig); fclose($fh); }
    } elseif ($da < $gueltig) {
        /* DER BAUSTAND IST KUERZER, ALS DER ZUSTAND BEHAUPTET — also weg oder
         * beschaedigt. Dann wird VON VORN begonnen und nicht weitergeschrieben.
         *
         * Der Fall ist nicht ausgedacht. Er tritt regelmaessig nach einer
         * Wiederherstellung auf: Der Dump schreibt seinen eigenen Zustand mit
         * (die Zeile `komplett` in `jobs` gehoert zur Datenbank wie jede
         * andere), also traegt die eingespielte Datenbank den Stand „Dump
         * laeuft“ samt einem Bauordner, den es auf dem neuen Server nie gab.
         * Ohne diesen Zweig haenge der naechste Lauf mitten in der
         * Tabellenliste an eine LEERE Datei an — heraus kaeme ein Dump ohne
         * Kopf und ohne die ersten Tabellen, der sich am Ende selbst fuer
         * vollstaendig erklaert. Dieselbe Lage entsteht, wenn der Hoster
         * `sicherungen/` aufraeumt oder die Platte zwischendurch voll war. */
        @unlink($roh);
        foreach (['folge', 'ring', 'i', 'kopf', 'f', 'nach', 'zeilen', 'warnung',
                  'warnung_ohne_pk', 'kopf_da', 'kopfzeile', 'siegel_i',
                  'siegel_bytes'] as $k) { unset($z[$k]); }
        $z['roh_bytes'] = 0;
        $z['neu_begonnen'] = gmdate('Y-m-d\TH:i:s\Z');
        $gueltig = 0;
    }

    /* ERST JETZT DIE ERSTBELEGUNG — nach dem Zurueckschneiden und nach dem
     * moeglichen Neuanlauf.
     *
     * SIE STAND FRUEHER DAVOR, und das war falsch: Der Neuanlauf loescht
     * `folge`, `i` und die uebrigen Marken, und danach lief die Schleife in
     * ein `count(null)`. Aufgefallen ist es der Probe (Teil 8) und nicht dem
     * Lesen — im Regelfall gibt es den Zweig ja nicht. Die Reihenfolge ist
     * jetzt die, in der man sie erzaehlen wuerde: zurueckschneiden, notfalls
     * neu anfangen, dann belegen, was fehlt. */
    $tabellen = komp_tabellen($pdo);
    if (!isset($z['folge'])) {
        $r = komp_reihenfolge($pdo, $tabellen);
        $z['folge']   = $r['reihenfolge'];
        $z['ring']    = $r['ring'];
        $z['i']       = 0;
        $z['kopf']    = false;
        $z['f']       = 0;
        $z['nach']    = null;
        $z['zeilen']  = 0;
        $z['warnung'] = [];
    }

    $gz = gzopen($roh, 'ab6');
    if ($gz === false) {
        throw new RuntimeException('Der Dump liess sich nicht schreiben: ' . $roh);
    }
    $schreib = function (string $zeile) use ($gz): void { gzwrite($gz, $zeile . "\n"); };

    $geschrieben = 0;
    try {
        /* Der Kopf steht genau einmal, vor der ersten Tabelle. */
        if (!($z['kopf_da'] ?? false)) {
            foreach (komp_kopfzeilen($pdo, $tabellen, $z) as $zeile) { $schreib($zeile); }
            $z['kopf_da'] = true;
        }

        while (true) {
            if ($zeitLinks() < $reserve) { break; }

            if ($z['i'] >= count($z['folge'])) {
                /* Fertig: Fuss schreiben. Die Endmarke ist der Beleg dafuer,
                 * dass die Datei nicht mitten im Lauf abgebrochen ist —
                 * sonst waere ein halber Dump von einem ganzen nicht zu
                 * unterscheiden. */
                $schreib('SET FOREIGN_KEY_CHECKS = 1;');
                $schreib('SET UNIQUE_CHECKS = 1;');
                $schreib(KOMP_ENDMARKE . ' ' . (int)$z['zeilen'] . ' Zeilen in '
                         . count($z['folge']) . ' Tabellen');
                gzclose($gz);
                $gz = null;
                clearstatcache(true, $roh);
                $z['roh_bytes'] = (int)filesize($roh);
                return ['zeilen' => $geschrieben, 'fertig' => true];
            }

            $tab  = (string)$z['folge'][$z['i']];
            $info = $tabellen[$tab] ?? null;
            if ($info === null) {
                /* Die Tabelle ist waehrend des Laufs verschwunden. Das ist
                 * kein Absturzgrund, aber es gehoert in die Datei. */
                $schreib('-- Tabelle `' . $tab . '` war beim Sichern nicht mehr vorhanden.');
                $z['warnung'][] = 'Tabelle „' . $tab . '" war beim Sichern nicht mehr vorhanden.';
                $z['i']++; $z['kopf'] = false; $z['f'] = 0; $z['nach'] = null;
                continue;
            }

            if (!$z['kopf']) {
                foreach (komp_tabellenkopf($pdo, $tab) as $zeile) { $schreib($zeile); }
                $z['kopf'] = true;
                $z['f'] = 0; $z['nach'] = null;
            }

            $komb = komp_kombinationen($info['fest']);
            if ($z['f'] >= count($komb)) {
                $z['i']++; $z['kopf'] = false; $z['f'] = 0; $z['nach'] = null;
                continue;
            }

            $limit = komp_blockgroesse($info);
            $zeilen = komp_hol($pdo, $tab, $info, $komb[$z['f']], $z['nach'], $limit);
            if ($zeilen === []) {
                $z['f']++; $z['nach'] = null;
                continue;
            }

            if ($info['pk'] === []) {
                $z['nach'] = ['__versatz' => (int)($z['nach']['__versatz'] ?? 0) + count($zeilen)];
                if (!in_array($tab, $z['warnung_ohne_pk'] ?? [], true)) {
                    $z['warnung_ohne_pk'][] = $tab;
                    $z['warnung'][] = 'Tabelle „' . $tab . '" hat keinen Primärschlüssel; '
                        . 'sie wird über den Versatz gelesen und kann bei gleichzeitigen '
                        . 'Löschungen Zeilen auslassen.';
                }
            } else {
                $z['nach'] = komp_cursor_aus($zeilen[count($zeilen) - 1], $info);
            }
            $z['zeilen'] += count($zeilen);
            $geschrieben += count($zeilen);
            foreach (komp_inserts($tab, $info, $zeilen) as $zeile) { $schreib($zeile); }

            /* Weniger Zeilen als angefragt heisst: diese Kombination ist
             * ausgeschoepft. Das spart die Leerabfrage, die sonst folgen
             * wuerde — bei `track_points` waeren das zwei von 460. */
            if (count($zeilen) < $limit) { $z['f']++; $z['nach'] = null; }
        }
    } finally {
        if ($gz !== null) { gzclose($gz); }
    }
    clearstatcache(true, $roh);
    $z['roh_bytes'] = (int)filesize($roh);
    return ['zeilen' => $geschrieben, 'fertig' => false];
}

/** Zeilen je Abfrage: mit Binaerspalte klein, sonst gross. */
function komp_blockgroesse(array $info): int
{
    foreach ($info['spalten'] as $s) {
        if ($s['binaer']) { return KOMP_ZEILEN_BLOCK_BLOB; }
    }
    return KOMP_ZEILEN_BLOCK;
}

/** Das Kreuzprodukt der festgenagelten Spalten. Ohne solche: eine leere Kombination. */
function komp_kombinationen(array $fest): array
{
    $raus = [[]];
    foreach ($fest as $spalte => $werte) {
        $neu = [];
        foreach ($raus as $bisher) {
            foreach ($werte as $w) { $neu[] = $bisher + [$spalte => $w]; }
        }
        $raus = $neu;
    }
    return $raus;
}

/**
 * Ein Block Zeilen ab dem Cursor.
 *
 * Die Bedingung ist aufgefaechert (`a > ? OR (a = ? AND b > ?)`) und kein
 * Zeilenkonstruktor — Begruendung samt Messwerten im Kopf dieser Datei.
 */
function komp_hol(PDO $pdo, string $tab, array $info, array $fest, ?array $nach, int $limit): array
{
    $q = fn(string $s): string => '`' . str_replace('`', '``', $s) . '`';
    $wo = []; $p = [];
    foreach ($fest as $spalte => $wert) { $wo[] = $q($spalte) . ' = ?'; $p[] = $wert; }

    $cursor = $info['cursor'];
    if ($info['pk'] === []) {
        /* KEIN PRIMAERSCHLUESSEL. Dann bleibt nur der Versatz — und mit ihm
         * die Schwaeche, dass eine geloeschte Zeile den Rest verschiebt.
         * Keine Tabelle dieser Anwendung ist so gebaut; die Zeile steht hier,
         * damit eine kuenftige es nicht STILL waere. */
        $versatz = (int)($nach['__versatz'] ?? 0);
        $sql = 'SELECT * FROM ' . $q($tab)
             . ($wo ? ' WHERE ' . implode(' AND ', $wo) : '')
             . ' LIMIT ' . $versatz . ', ' . $limit;
        $st = $pdo->prepare($sql); $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($nach !== null && $cursor !== []) {
        $zweige = [];
        foreach ($cursor as $tiefe => $_) {
            $teil = [];
            for ($k = 0; $k < $tiefe; $k++) { $teil[] = $q($cursor[$k]) . ' = ?'; $p[] = $nach[$cursor[$k]]; }
            $teil[] = $q($cursor[$tiefe]) . ' > ?'; $p[] = $nach[$cursor[$tiefe]];
            $zweige[] = '(' . implode(' AND ', $teil) . ')';
        }
        $wo[] = '(' . implode(' OR ', $zweige) . ')';
    } elseif ($nach !== null && $cursor === []) {
        /* Der ganze Primaerschluessel ist festgenagelt: hoechstens eine Zeile,
         * und die ist beim zweiten Aufruf schon geschrieben. */
        return [];
    }

    $sql = 'SELECT * FROM ' . $q($tab)
         . ($wo ? ' WHERE ' . implode(' AND ', $wo) : '')
         . ($cursor ? ' ORDER BY ' . implode(', ', array_map($q, $cursor)) : '')
         . ' LIMIT ' . $limit;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/** Aus der letzten Zeile den neuen Cursorstand bilden. */
function komp_cursor_aus(array $zeile, array $info): array
{
    if ($info['pk'] === []) { return ['__versatz' => 0]; }   // wird vom Aufrufer erhoeht
    $raus = [];
    foreach ($info['cursor'] as $c) { $raus[$c] = $zeile[$c] ?? null; }
    return $raus;
}

/**
 * Aus einem Block Zeilen die INSERT-Statements bauen — je Statement <= 1 MB.
 *
 * @return list<string> je ein Statement, ohne Zeilenumbruch
 */
function komp_inserts(string $tab, array $info, array $zeilen): array
{
    $q = fn(string $s): string => '`' . str_replace('`', '``', $s) . '`';
    $spalten = array_keys($info['spalten']);
    $kopf = 'INSERT INTO ' . $q($tab) . ' (' . implode(', ', array_map($q, $spalten)) . ') VALUES ';

    $raus = []; $stapel = []; $laenge = 0;
    foreach ($zeilen as $zeile) {
        $werte = [];
        foreach ($spalten as $s) {
            $werte[] = komp_wert($zeile[$s] ?? null,
                                 $info['spalten'][$s]['binaer'],
                                 $info['spalten'][$s]['zahl']);
        }
        $tupel = '(' . implode(',', $werte) . ')';
        /* +2 fuer das trennende Komma und das abschliessende Semikolon. Der
         * Stapel wird VOR dem Anhaengen geprueft, damit eine einzelne sehr
         * grosse Zeile ein eigenes Statement bekommt statt ein volles zu
         * sprengen. */
        if ($stapel !== [] && $laenge + strlen($tupel) + 2 > KOMP_STAPEL_BYTES) {
            $raus[] = $kopf . implode(',', $stapel) . ';';
            $stapel = []; $laenge = strlen($kopf);
        }
        if ($stapel === []) { $laenge = strlen($kopf); }
        $stapel[] = $tupel; $laenge += strlen($tupel) + 1;
    }
    if ($stapel !== []) { $raus[] = $kopf . implode(',', $stapel) . ';'; }
    return $raus;
}

/**
 * `DROP TABLE` und `CREATE TABLE` fuer eine Tabelle — jedes auf einer Zeile.
 *
 * `SHOW CREATE TABLE` liefert die Anweisung mehrzeilig; hier werden die
 * Umbrueche zu Leerzeichen. Das ist zulaessig, WEIL kein Zeilenumbruch in
 * einem Zeichenkettenliteral der Deklaration steht — kein Vorgabewert und
 * kein Spaltenkommentar dieses Schemas enthaelt einen. Belegt wird die
 * Annahme nicht durch diese Zusicherung, sondern durch die Probe: Sie
 * vergleicht nach dem Wiedereinspielen `SHOW CREATE TABLE` aller Tabellen
 * Zeichen fuer Zeichen mit dem Original.
 */
function komp_tabellenkopf(PDO $pdo, string $tab): array
{
    $q = '`' . str_replace('`', '``', $tab) . '`';
    $zeile = $pdo->query('SHOW CREATE TABLE ' . $q)->fetch(PDO::FETCH_NUM);
    $create = (string)($zeile[1] ?? '');
    $create = trim(preg_replace('/\s*\R\s*/u', ' ', $create) ?? $create);
    return [
        '',
        '-- Tabelle ' . $tab,
        'DROP TABLE IF EXISTS ' . $q . ';',
        $create . ';',
    ];
}

/**
 * Die Kopfzeilen der Datei (E-S2-20: Version, Migrationsstand, Zeitpunkt).
 *
 * DIE `SET`-ZEILEN STEHEN HIER UND WERDEN TROTZDEM VOM RUECKWEG NOCH EINMAL
 * GESETZT. Sie gelten je Verbindung; der Rueckweg arbeitet die Datei ueber
 * mehrere Anfragen ab und hat in jeder eine neue. Wer nur diese Zeilen
 * ausfuehrte und dann die Verbindung verloere, saesse ab dem zweiten
 * Haeppchen wieder mit eingeschalteter Fremdschluesselpruefung da. Doppelt
 * ist hier richtig: fuer `mysql` und phpMyAdmin steht es in der Datei, fuer
 * den Rueckweg im Code.
 */
function komp_kopfzeilen(PDO $pdo, array $tabellen, array $z): array
{
    $stand = '';
    $zahl  = 0;
    try {
        $stand = (string)($pdo->query('SELECT id FROM schema_migrations
                                        ORDER BY id DESC LIMIT 1')->fetchColumn() ?: '');
        $zahl  = (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn();
    } catch (Throwable) { /* vor der ersten Migration */ }
    $server = '';
    try { $server = (string)$pdo->getAttribute(PDO::ATTR_SERVER_VERSION); } catch (Throwable) {}

    $raus = [
        '-- Einsatzdokumentation Notarzt — Komplett-Backup der Installation',
        '-- Erzeugt am:        ' . gmdate('Y-m-d\TH:i:s\Z') . ' (UTC)',
        '-- Web-Version:       ' . (defined('WEB_VERSION') ? WEB_VERSION : 'unbekannt'),
        '-- Migrationsstand:   ' . ($stand !== '' ? $stand : 'keiner') . ' (' . $zahl . ' Einträge)',
        '-- Datenbankserver:   ' . ($server !== '' ? $server : 'unbekannt'),
        '-- Tabellen:          ' . count($tabellen),
        '--',
        '-- NICHT ENTHALTEN: config.php (Datenbankzugang, Serverschlüssel, SMTP).',
        '-- Sie gehört ins getrennt aufbewahrte Wiederanlaufpaket; ohne den',
        '-- Serverschlüssel daraus ist eine versiegelte Fassung dieser Datei',
        '-- nicht zu öffnen. Siehe docs/Technik.md, Abschnitt 7.',
        '--',
        '-- Einspielbar mit:   mysql -uNUTZER -pPASSWORT DATENBANK < dump.sql',
        '--                    oder über „Installation wiederherstellen" der Anwendung.',
        '-- Form:              ein Statement je Zeile, INSERT-Stapel bis 1 MB.',
        '',
        'SET NAMES utf8mb4;',
        "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';",
        'SET FOREIGN_KEY_CHECKS = 0;',
        'SET UNIQUE_CHECKS = 0;',
    ];
    if (($z['ring'] ?? []) !== []) {
        $raus[] = '-- Hinweis: Diese Tabellen bilden einen Fremdschlüssel-Ring und stehen';
        $raus[] = '-- deshalb in alphabetischer statt in abhängiger Reihenfolge: '
                . implode(', ', $z['ring']) . '.';
    }
    return $raus;
}

/* ---- Versiegelung: das Format EDKOMP1 ------------------------------------- */

/**
 * Das Dateiformat des versiegelten Komplett-Backups (E-S2-21).
 *
 *   "EDKOMP1\n"                                       8 Byte
 *   <Kopfzeile als JSON>"\n"                          eine Zeile, kein \n darin
 *   je Haeppchen:  <4 Byte Laenge, big endian>
 *                  <12 Byte Nonce><16 Byte Prüfsumme><N Byte Chiffre>
 *
 * WARUM HAEPPCHEN UND NICHT EIN GUSS. AES-GCM verlangt, dass Klartext und
 * Chiffre am Stueck im Speicher liegen. Bei einer Datenbank von 200 MB waeren
 * das 400 MB gegen ein Budget von 64 (Z3). Bloecke von 256 KB kosten
 * unabhaengig von der Dateigroesse denselben halben Megabyte.
 *
 * DER ZAEHLER STEHT IN DEN ZUSATZDATEN (wie E-S2-10), und zwar zusammen mit
 * der Angabe, ob es der LETZTE Block ist. Beides ist noetig:
 *   - ohne Zaehler liessen sich zwei Bloecke vertauschen, und die Prüfsumme
 *     jedes einzelnen bliebe richtig;
 *   - ohne die Endemarkierung liesse sich die Datei hinten abschneiden, und
 *     was uebrig bleibt, waere ein gueltiges, kuerzeres Backup.
 *
 * DIE ZUSATZDATEN BINDEN AUCH DEN KOPF, ueber seinen SHA-256. Wer die
 * Kopfzeile aendert — etwa den Vermerk „mit Passphrase" gegen „mit
 * Serverschlüssel" —, macht damit jeden Block unlesbar statt einen falschen
 * Schluessel zu erzwingen.
 *
 * DER SCHLUESSEL IST ENTWEDER DER SERVERSCHLUESSEL AUS `config.php` (Regelfall,
 * `kdf: null`) ODER AUS EINER PASSPHRASE ABGELEITET (Direktdownload, PBKDF2
 * mit derselben Rundenzahl wie im Browser). Beides steht im Kopf; welches
 * gilt, muss niemand raten.
 */
const KOMP_SIEGEL      = "EDKOMP1\n";
const KOMP_BLOCK       = 262144;      // 256 KiB Klartext je Haeppchen
const KOMP_NONCE_LEN   = 12;
const KOMP_TAG_LEN     = 16;
const KOMP_KOPF_MAX    = 8192;        // eine Kopfzeile ist ~300 Byte; das ist die Schranke

/** Die Bindung: der Fingerabdruck von Kennung und Kopfzeile. */
function komp_bindung(string $kopfzeile): string
{
    return hash('sha256', KOMP_SIEGEL . $kopfzeile);
}

/** Die Zusatzdaten eines Blocks. */
function komp_aad(string $bindung, int $i, bool $letzte): string
{
    return 'edkomp1|' . $bindung . '|' . $i . '|' . ($letzte ? '1' : '0');
}

/**
 * Der Schluessel aus einer Passphrase.
 *
 * DIESELBEN 320 000 RUNDEN WIE IM BROWSER (`KDF_ITER_ZIEL`). Eine zweite
 * Zahl waere eine zweite Aussage darueber, was dieses Projekt fuer sicher
 * haelt — und die eine, die irgendwann nicht mehr nachgezogen wird.
 */
function komp_schluessel_aus_passwort(string $passwort, string $salzHex, int $runden): string
{
    $salz = @hex2bin($salzHex);
    if ($salz === false || $salz === '' || $runden < 1000) {
        throw new RuntimeException('Die Schlüsselableitung der Datei ist unbrauchbar.');
    }
    return hash_pbkdf2('sha256', $passwort, $salz, $runden, 32, true);
}

/**
 * Die Kopfzeile einer neuen versiegelten Datei.
 *
 * @param ?array $kdf null = Serverschluessel; sonst ['art','hash','iter','salz']
 */
function komp_kopf_bauen(array $angaben, ?array $kdf): string
{
    $kopf = [
        'art'      => 'komplett',
        'fassung'  => 1,
        'erzeugt'  => $angaben['erzeugt'] ?? gmdate('Y-m-d\TH:i:s\Z'),
        'web'      => $angaben['web'] ?? (defined('WEB_VERSION') ? WEB_VERSION : ''),
        'migration'=> $angaben['migration'] ?? '',
        'tabellen' => (int)($angaben['tabellen'] ?? 0),
        'zeilen'   => (int)($angaben['zeilen'] ?? 0),
        'roh'      => (int)($angaben['roh'] ?? 0),
        'block'    => KOMP_BLOCK,
        'kdf'      => $kdf,
    ];
    $j = json_encode($kopf, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($j === false || str_contains($j, "\n")) {
        throw new RuntimeException('Der Dateikopf liess sich nicht bilden.');
    }
    return $j;
}

/**
 * Kopf und Bindung einer versiegelten Datei lesen.
 *
 * @return ?array ['kopf' => array, 'bindung' => string, 'ab' => int]
 *                `ab` ist der Byteversatz des ersten Blocks.
 */
function komp_kopf_lesen(string $pfad): ?array
{
    $fh = @fopen($pfad, 'rb');
    if ($fh === false) { return null; }
    try {
        $magie = fread($fh, strlen(KOMP_SIEGEL));
        if ($magie !== KOMP_SIEGEL) { return null; }
        $zeile = '';
        while (strlen($zeile) < KOMP_KOPF_MAX) {
            $c = fread($fh, 1);
            if ($c === '' || $c === false) { return null; }
            if ($c === "\n") { break; }
            $zeile .= $c;
        }
        if (strlen($zeile) >= KOMP_KOPF_MAX) { return null; }
        $kopf = json_decode($zeile, true);
        if (!is_array($kopf) || ($kopf['art'] ?? '') !== 'komplett') { return null; }
        return ['kopf' => $kopf, 'bindung' => komp_bindung($zeile . "\n"),
                'ab' => strlen(KOMP_SIEGEL) + strlen($zeile) + 1];
    } finally {
        fclose($fh);
    }
}

/**
 * Ein Haeppchen Versiegelung: aus dem Klartext-Dump wird die `.edk`-Datei.
 *
 * Der Zustand ist EINE ZAHL — der Blockindex. Block `i` deckt die Klartext-
 * Bytes [i*BLOCK, (i+1)*BLOCK). Damit setzt ein Wiederanlauf genau auf, ohne
 * dass irgendetwas zwischen zwei Anfragen aufbewahrt werden muesste.
 *
 * @return array ['bloecke' => int, 'fertig' => bool]
 */
function komp_siegel_schub(string $quelle, string $ziel, string $schluessel,
                           string $kopfzeile, array &$z,
                           callable $zeitLinks, float $reserve): array
{
    clearstatcache(true, $quelle);
    $gesamt = (int)@filesize($quelle);
    if ($gesamt <= 0) { throw new RuntimeException('Der Dump ist leer: ' . $quelle); }
    $bloecke = (int)ceil($gesamt / KOMP_BLOCK);
    $i = (int)($z['siegel_i'] ?? 0);
    $bindung = komp_bindung($kopfzeile);

    /* Wie beim Dump: erst auf den gueltigen Teil zurueckschneiden. Der ist
     * hier ausrechenbar — der Kopf plus die Bloecke, die der Zustand kennt —,
     * steht aber trotzdem im Zustand, weil die Blocklaengen im Kopf der
     * Bloecke stehen und nicht in einer Formel. */
    $gueltig = (int)($z['siegel_bytes'] ?? 0);
    if ($i === 0 || $gueltig === 0) {
        $fh = fopen($ziel, 'wb');
        if ($fh === false) { throw new RuntimeException('Die versiegelte Datei liess sich nicht anlegen: ' . $ziel); }
        fwrite($fh, KOMP_SIEGEL);
        fwrite($fh, $kopfzeile);
        $gueltig = ftell($fh);
        fclose($fh);
        $i = 0;
    } elseif (is_file($ziel) && filesize($ziel) > $gueltig) {
        $fh = fopen($ziel, 'r+b');
        if ($fh !== false) { ftruncate($fh, $gueltig); fclose($fh); }
    }

    $qh = fopen($quelle, 'rb');
    $zh = fopen($ziel, 'ab');
    if ($qh === false || $zh === false) {
        if ($qh !== false) { fclose($qh); }
        if ($zh !== false) { fclose($zh); }
        throw new RuntimeException('Die Versiegelung konnte nicht öffnen: ' . $ziel);
    }
    $getan = 0;
    try {
        while ($i < $bloecke) {
            if ($zeitLinks() < $reserve) { break; }
            fseek($qh, $i * KOMP_BLOCK);
            $klar = (string)fread($qh, KOMP_BLOCK);
            if ($klar === '') { break; }
            $letzte = ($i + 1) >= $bloecke;
            $nonce = random_bytes(KOMP_NONCE_LEN);
            $tag = '';
            $chiffre = openssl_encrypt($klar, 'aes-256-gcm', $schluessel,
                                       OPENSSL_RAW_DATA, $nonce, $tag,
                                       komp_aad($bindung, $i, $letzte), KOMP_TAG_LEN);
            if ($chiffre === false) {
                throw new RuntimeException('Die Versiegelung ist fehlgeschlagen (Block ' . $i . ').');
            }
            fwrite($zh, pack('N', strlen($chiffre)) . $nonce . $tag . $chiffre);
            $i++; $getan++;
        }
    } finally {
        fclose($qh);
        $gueltig = ftell($zh);
        fclose($zh);
    }
    $z['siegel_i'] = $i;
    $z['siegel_bytes'] = $gueltig;
    return ['bloecke' => $getan, 'fertig' => $i >= $bloecke];
}

/**
 * Eine versiegelte Datei oeffnen und den Klartext (das gzip) hinausschreiben.
 *
 * `$hinaus` bekommt jeden Block, sobald er geprueft ist — damit taugt dieselbe
 * Funktion fuer den Direktdownload (Ausgabe an den Browser) wie fuer das
 * Auspacken in eine Datei. Sie gibt NIE etwas heraus, dessen Prüfsumme nicht
 * gestimmt hat: Bei AES-GCM ist die Prüfung Teil des Entschlüsselns.
 *
 * @param callable $hinaus fn(string $klartext, int $i, bool $letzte): void
 * @return int Zahl der Bloecke
 */
function komp_oeffnen(string $pfad, string $schluessel, callable $hinaus): int
{
    $k = komp_kopf_lesen($pfad);
    if ($k === null) { throw new RuntimeException('Das ist kein Komplett-Backup im Format EDKOMP1.'); }
    $fh = fopen($pfad, 'rb');
    if ($fh === false) { throw new RuntimeException('Die Datei liess sich nicht öffnen.'); }
    fseek($fh, $k['ab']);
    $gesamt = (int)filesize($pfad);
    $i = 0;
    try {
        while (ftell($fh) < $gesamt) {
            $laengeRoh = fread($fh, 4);
            if ($laengeRoh === '' || $laengeRoh === false || strlen($laengeRoh) < 4) { break; }
            $n = (int)(unpack('N', $laengeRoh)[1] ?? 0);
            if ($n <= 0 || $n > KOMP_BLOCK) {
                throw new RuntimeException('Die Datei ist beschädigt (Blocklänge ' . $n . ').');
            }
            $nonce   = (string)fread($fh, KOMP_NONCE_LEN);
            $tag     = (string)fread($fh, KOMP_TAG_LEN);
            $chiffre = (string)fread($fh, $n);
            if (strlen($nonce) < KOMP_NONCE_LEN || strlen($tag) < KOMP_TAG_LEN
                || strlen($chiffre) < $n) {
                throw new RuntimeException('Die Datei bricht mitten in einem Block ab.');
            }
            $letzte = ftell($fh) >= $gesamt;
            $klar = openssl_decrypt($chiffre, 'aes-256-gcm', $schluessel,
                                    OPENSSL_RAW_DATA, $nonce, $tag,
                                    komp_aad($k['bindung'], $i, $letzte));
            if ($klar === false) {
                /* AES-GCM SAGT NICHT, WORAN ES LAG — und diese Meldung
                 * behauptet es deshalb auch nicht. Ein falscher Schlüssel,
                 * eine falsche Passphrase und ein veränderter Dateikopf sehen
                 * für den ersten Block gleich aus (der Kopf hängt über die
                 * Zusatzdaten an jedem Block). Wer hier „falscher Schlüssel"
                 * läse, suchte den Fehler am falschen Ende. */
                throw new RuntimeException($i === 0
                    ? 'Die Datei liess sich nicht öffnen: falscher Schlüssel, falsche '
                      . 'Passphrase — oder der Dateikopf ist verändert worden.'
                    : 'Die Datei ist verändert oder unvollständig (Block ' . $i . ').');
            }
            $hinaus($klar, $i, $letzte);
            $i++;
        }
    } finally {
        fclose($fh);
    }
    if ($i === 0) { throw new RuntimeException('Die Datei enthält keinen einzigen Block.'); }
    return $i;
}

/* ---- Der Auftrag: ein Lauf in Haeppchen ------------------------------------ */

/**
 * Reserve fuer ein Haeppchen, in Sekunden.
 *
 * SIE IST SO GROSS, DASS DER HUCKEPACK-WEG GAR NICHT ERST ANFAENGT
 * (`JOB_BUDGET_ANFRAGE` = 3 s). Ein Komplett-Backup ist die schwerste
 * Arbeit dieser Anwendung; es an der Anfrage einer NutzerIn mitlaufen zu
 * lassen hiesse, eine Seite auf zehn Sekunden zu bringen, damit im
 * Hintergrund die Datenbank abgeschrieben wird.
 */
const KOMP_RESERVE_S = 10.0;

/** Der Name des Jobs im Katalog. */
const KOMP_JOB = 'komplett';

/** Der Zeitplan: wie oft von selbst gesichert wird. */
const KOMP_PLAN_SCHLUESSEL = 'komplett_plan';
const KOMP_PLAENE = [
    'aus'          => 'Nur von Hand',
    'taeglich'     => 'Täglich',
    'woechentlich' => 'Wöchentlich',
    'monatlich'    => 'Monatlich',
];
/** Mindestabstand je Plan in Sekunden — knapp unter dem Nennwert. */
const KOMP_ABSTAND = ['taeglich' => 72000, 'woechentlich' => 590400, 'monatlich' => 2505600];

function komp_plan(): string
{
    $v = (string)(edbak_marke_lesen(KOMP_PLAN_SCHLUESSEL) ?? '');
    return isset(KOMP_PLAENE[$v]) ? $v : 'aus';
}

function komp_plan_setzen(string $plan): bool
{
    if (!isset(KOMP_PLAENE[$plan])) { return false; }
    return edbak_marke_setzen(KOMP_PLAN_SCHLUESSEL, $plan);
}

/**
 * Ist ein neues Komplett-Backup faellig?
 *
 * DER PLAN SAGT NICHT WANN, SONDERN OB — wie beim Versand (E-S2-17). Wann
 * ueberhaupt etwas laeuft, entscheidet der eingerichtete Ausloeser (Cron oder
 * Token-Aufruf); der Plan sagt nur, wie lange der letzte Stand her sein darf.
 * Zwei Uhren nebeneinander waeren zwei Wahrheiten.
 */
function komp_faellig(): bool
{
    $plan = komp_plan();
    if ($plan === 'aus') { return false; }
    $staende = komp_staende();
    if ($staende === []) { return true; }
    $zeit = komp_zeit_aus_name($staende[0]['datei']);
    if ($zeit === null) { return true; }
    $alter = time() - (int)strtotime($zeit);
    return $alter >= (KOMP_ABSTAND[$plan] ?? 72000);
}

/** Den Zustand des Jobs lesen. */
function komp_zustand(): array
{
    try {
        $st = db()->prepare('SELECT zustand FROM jobs WHERE job = ?');
        $st->execute([KOMP_JOB]);
        $j = json_decode((string)($st->fetchColumn() ?: '{}'), true);
        return is_array($j) ? $j : [];
    } catch (Throwable) {
        return [];
    }
}

/** Laeuft gerade ein Haeppchen? */
function komp_laeuft(): bool
{
    require_once __DIR__ . '/jobs_lib.php';
    try {
        $st = db()->prepare('SELECT laeuft_seit FROM jobs WHERE job = ?');
        $st->execute([KOMP_JOB]);
        $v = $st->fetchColumn();
        if ($v === false || $v === null) { return false; }
        return (int)strtotime((string)$v . ' UTC') > time() - JOB_SPERRE_VERFALL_S;
    } catch (Throwable) {
        return false;
    }
}

/** Den Zustand des Jobs schreiben. */
function komp_zustand_setzen(array $z): bool
{
    try {
        $pdo = db();
        $pdo->prepare('INSERT IGNORE INTO jobs (job) VALUES (?)')->execute([KOMP_JOB]);
        $pdo->prepare('UPDATE jobs SET zustand = ? WHERE job = ?')
            ->execute([json_encode($z), KOMP_JOB]);
        return true;
    } catch (Throwable $ex) {
        error_log('komplett: Zustand liess sich nicht schreiben: ' . $ex->getMessage());
        return false;
    }
}

/**
 * Einen Auftrag beginnen. Liefert ['ok' => bool, 'meldung' => string].
 *
 * OHNE SERVERSCHLUESSEL WIRD NICHT GESICHERT, und das ist keine Bequemlichkeit
 * der Umsetzung. Die abgelegte Datei ist eine vollstaendige Abschrift jeder
 * Tabelle — jede Patientendiagnose (verschlüsselt), jeder Schlüsselumschlag,
 * jede Adresse. Sie unversiegelt in `sicherungen/` liegen zu lassen hiesse,
 * die Ende-zu-Ende-Zusage an genau der Stelle zu unterlaufen, an der es am
 * wenigsten auffiele. E-S2-21 verlangt die Versiegelung ohnehin, sobald die
 * Datei das Haus verlaesst; hier verlaesst sie es spaetestens mit dem
 * Versand. Der Schlüssel wird auf der Seite „Backup-Ziele" nachgetragen.
 */
function komp_auftrag_starten(): array
{
    if (!serverschluessel_da()) {
        return ['ok' => false, 'meldung' => 'Es gibt noch keinen Serverschlüssel. '
            . 'Ohne ihn kann das Komplett-Backup nicht versiegelt werden, und '
            . 'unversiegelt wird es nicht abgelegt. Der Schlüssel wird auf der '
            . 'Seite „Backup-Ziele" eingetragen.'];
    }
    [$bOk, $bMeldung] = komp_bereit();
    if (!$bOk) { return ['ok' => false, 'meldung' => (string)$bMeldung]; }
    /* DIE SPEICHERGRENZE GILT AUCH HIER (E-S2-15). Ein Komplett-Backup ist
     * die groesste einzelne Datei der Ablage; es an der Grenze vorbei
     * anzulegen hiesse, den Webspace mit genau dem vollzuschreiben, was ihn
     * retten soll. */
    [$gOk, $gMeldung] = edbak_grenze_pruefen(true);
    if (!$gOk) { return ['ok' => false, 'meldung' => (string)$gMeldung]; }
    if (komp_laeuft()) {
        return ['ok' => false, 'meldung' => 'Es läuft gerade ein Häppchen. '
            . 'Bitte einen Augenblick warten und neu laden.'];
    }
    $z = komp_zustand();
    if (in_array($z['stand'] ?? '', ['dump', 'siegel'], true)) {
        return ['ok' => false, 'meldung' => 'Es läuft bereits ein Komplett-Backup.'];
    }
    $bau = KOMP_BAU_PRAEFIX . bin2hex(random_bytes(4));
    komp_baureste_aufraeumen($bau);
    $neu = [
        'stand'     => 'dump',
        'bau'       => $bau,
        'name'      => komp_dateiname(),
        'begonnen'  => gmdate('Y-m-d\TH:i:s\Z'),
        'roh_bytes' => 0,
    ];
    if (!komp_zustand_setzen($neu)) {
        return ['ok' => false, 'meldung' => 'Der Auftrag liess sich nicht vormerken.'];
    }
    return ['ok' => true, 'meldung' => 'Das Komplett-Backup ist vorgemerkt. '
        . 'Es läuft mit dem nächsten Wartungslauf in Häppchen an.'];
}

/** Einen laufenden Auftrag abbrechen und den Bauordner raeumen. */
function komp_auftrag_abbrechen(): array
{
    if (komp_laeuft()) {
        return ['ok' => false, 'meldung' => 'Es läuft gerade ein Häppchen. '
            . 'Bitte einen Augenblick warten und neu laden.'];
    }
    $z = komp_zustand();
    if (!in_array($z['stand'] ?? '', ['dump', 'siegel'], true)) {
        return ['ok' => false, 'meldung' => 'Es läuft kein Komplett-Backup.'];
    }
    if (isset($z['bau'])) { komp_bau_weg((string)$z['bau']); }
    komp_zustand_setzen(['stand' => 'abgebrochen', 'zeit' => gmdate('Y-m-d\TH:i:s\Z')]);
    return ['ok' => true, 'meldung' => 'Das Komplett-Backup ist abgebrochen; '
        . 'der halbe Stand ist entfernt.'];
}

/**
 * Ein Haeppchen des Auftrags. Aendert `$z`.
 *
 * @return array ['erledigt' => int, 'fertig' => bool]
 */
function komp_schub(PDO $pdo, array &$z, callable $zeitLinks, float $reserve = KOMP_RESERVE_S): array
{
    $stand = (string)($z['stand'] ?? '');

    /* Kein Auftrag: Faelligkeit pruefen und gegebenenfalls einen anlegen. */
    if (!in_array($stand, ['dump', 'siegel'], true)) {
        if (!komp_faellig() || !serverschluessel_da()) {
            return ['erledigt' => 0, 'fertig' => true];
        }
        [$bOk, $bMeldung] = komp_bereit();
        if (!$bOk) { throw new RuntimeException((string)$bMeldung); }
        [$gOk, $gMeldung] = edbak_grenze_pruefen(true);
        if (!$gOk) { throw new RuntimeException((string)$gMeldung); }
        $bau = KOMP_BAU_PRAEFIX . bin2hex(random_bytes(4));
        komp_baureste_aufraeumen($bau);
        $z = ['stand' => 'dump', 'bau' => $bau, 'name' => komp_dateiname(),
              'begonnen' => gmdate('Y-m-d\TH:i:s\Z'), 'roh_bytes' => 0, 'plan' => komp_plan()];
        $stand = 'dump';
    }

    $bauPfad = komp_wurzel() . '/' . (string)$z['bau'];
    $roh     = $bauPfad . '/' . KOMP_ROHNAME;
    $erledigt = 0;

    if ($stand === 'dump') {
        $e = komp_dump_schub($pdo, $z, $zeitLinks, $reserve);
        $erledigt += $e['zeilen'];
        if (!$e['fertig']) { return ['erledigt' => $erledigt, 'fertig' => false]; }
        /* Uebergang: Der Kopf der versiegelten Datei entsteht JETZT, weil er
         * Zeilenzahl und Rohgroesse nennt — beides steht erst am Ende fest. */
        clearstatcache(true, $roh);
        $z['kopfzeile'] = komp_kopf_bauen([
            'erzeugt'   => (string)($z['begonnen'] ?? gmdate('Y-m-d\TH:i:s\Z')),
            'migration' => komp_migrationsstand($pdo),
            'tabellen'  => count($z['folge'] ?? []),
            'zeilen'    => (int)($z['zeilen'] ?? 0),
            'roh'       => (int)@filesize($roh),
        ], null) . "\n";
        $z['stand'] = 'siegel';
        $z['siegel_i'] = 0;
        $z['siegel_bytes'] = 0;
        $stand = 'siegel';
        if ($zeitLinks() < $reserve) { return ['erledigt' => $erledigt, 'fertig' => false]; }
    }

    if ($stand === 'siegel') {
        $schluessel = serverschluessel();
        if ($schluessel === null) {
            throw new RuntimeException('Der Serverschlüssel ist verschwunden; '
                . 'das Backup lässt sich nicht versiegeln.');
        }
        $ziel = $bauPfad . '/ziel.edk';
        $e = komp_siegel_schub($roh, $ziel, $schluessel, (string)$z['kopfzeile'],
                               $z, $zeitLinks, $reserve);
        $erledigt += $e['bloecke'];
        if (!$e['fertig']) { return ['erledigt' => $erledigt, 'fertig' => false]; }

        /* Fertig: erst umhaengen, dann den Bauordner raeumen. Andersherum
         * stuende bei einem Absturz dazwischen weder das eine noch das andere
         * da. */
        $endziel = komp_wurzel() . '/' . (string)$z['name'];
        if (!@rename($ziel, $endziel)) {
            throw new RuntimeException('Das fertige Backup liess sich nicht ablegen: ' . $endziel);
        }
        @chmod($endziel, 0640);
        clearstatcache(true, $endziel);
        $bytes = (int)@filesize($endziel);
        komp_bau_weg((string)$z['bau']);
        $weg = komp_verdraengen();

        $z = [
            'stand'      => 'fertig',
            'name'       => (string)$z['name'],
            'begonnen'   => (string)($z['begonnen'] ?? ''),
            'beendet'    => gmdate('Y-m-d\TH:i:s\Z'),
            'zeilen'     => (int)($z['zeilen'] ?? 0),
            'tabellen'   => count($z['folge'] ?? []),
            'roh_bytes'  => (int)($z['roh_bytes'] ?? 0),
            'bytes'      => $bytes,
            'verdraengt' => $weg,
            'warnung'    => array_values(array_unique($z['warnung'] ?? [])),
        ];
        return ['erledigt' => $erledigt, 'fertig' => true];
    }

    return ['erledigt' => $erledigt, 'fertig' => true];
}

/** Der Migrationsstand als Zeichenkette — fuer den Dateikopf. */
function komp_migrationsstand(PDO $pdo): string
{
    try {
        return (string)($pdo->query('SELECT id FROM schema_migrations
                                      ORDER BY id DESC LIMIT 1')->fetchColumn() ?: '');
    } catch (Throwable) {
        return '';
    }
}

/**
 * Wie viel steht noch aus? Fuer die Rueckstandsanzeige der Wartungsseite.
 *
 * Gezaehlt werden TABELLEN und nicht Zeilen: Eine Zeilenzahl waere genauer
 * und braeuchte dafuer ein `COUNT(*)` ueber jede Tabelle bei jedem Aufruf
 * der Wartungsseite — bei `track_points` also einen Vollscan fuer eine
 * Anzeige.
 */
function komp_rueckstand(): ?int
{
    return komp_rueckstand_aus(komp_zustand());
}

/**
 * Dasselbe aus einem gegebenen Zustand.
 *
 * ES GIBT SIE ZWEIMAL, WEIL DER JOB-RAHMEN DEN FRISCHEN ZUSTAND HAT und die
 * Wartungsseite ihn erst lesen muss. Die Rechnung steht trotzdem nur hier —
 * zwei Kopien derselben Formel waeren zwei Zahlen, die auseinanderlaufen.
 */
function komp_rueckstand_aus(array $z): ?int
{
    $stand = (string)($z['stand'] ?? '');
    if ($stand === 'dump') { return max(0, count($z['folge'] ?? []) - (int)($z['i'] ?? 0)); }
    if ($stand === 'siegel') { return 1; }
    return komp_faellig() ? 1 : null;
}

/* ---- Direktdownload -------------------------------------------------------- */

/**
 * Runden der Schluesselableitung fuer die Passphrase-Fassung.
 *
 * DIESELBE ZAHL WIE IM BROWSER (`KDF_ITER_ZIEL` in db.php). Sie wird von dort
 * gelesen und nicht abgeschrieben — zwei Zahlen fuer dieselbe Aussage waeren
 * eine zu viel.
 */
function komp_kdf_runden(): int
{
    return defined('KDF_ITER_ZIEL') ? KDF_ITER_ZIEL : 320000;
}

/**
 * Ein abgelegtes Backup entsiegeln und den Klartext hinausgeben.
 *
 * DAS IST DIE FASSUNG FUER `mysql` UND phpMyAdmin (E-S2-20). Sie geht nicht
 * ueber die Leitung nach draussen, sondern an die Administratorin, die sich
 * eben angemeldet hat und ohnehin jede Zeile dieser Datenbank sehen kann.
 * Was das Haus verlaesst — der Versand aufs Backup-Ziel — ist immer die
 * versiegelte Fassung.
 *
 * @param callable $hinaus fn(string $stueck): void
 */
function komp_ausgeben_klar(string $datei, callable $hinaus): int
{
    if (!komp_name_gueltig($datei)) { throw new RuntimeException('Unbekanntes Backup.'); }
    $schluessel = serverschluessel();
    if ($schluessel === null) {
        throw new RuntimeException('Ohne Serverschlüssel lässt sich das Backup nicht öffnen.');
    }
    return komp_oeffnen(komp_wurzel() . '/' . $datei, $schluessel,
                        function (string $klar) use ($hinaus): void { $hinaus($klar); });
}

/**
 * Ein abgelegtes Backup unter einer PASSPHRASE ausgeben (E-S2-21).
 *
 * Es wird dabei nicht doppelt verschlüsselt, sondern UMGESIEGELT: mit dem
 * Serverschlüssel geoeffnet, mit dem abgeleiteten Schluessel wieder
 * verschlossen. Der Zweck ist die Weitergabe — eine Datei, die auch dann noch
 * verschlossen ist, wenn sie auf einem USB-Stick liegt oder in einem
 * Postfach, und die sich ohne `config.php` dieser Installation öffnen laesst.
 *
 * ES WIRD BLOCK FUER BLOCK UMGESIEGELT, in derselben Blockgroesse. Damit
 * bleibt der Speicherbedarf bei einer halben Megabyte, gleich wie gross die
 * Datei ist — und die Ausgabe faengt an, bevor das Ende gelesen ist.
 *
 * EINE PBKDF2 JE VORGANG (Z3), nicht eine je Block.
 *
 * @param callable $hinaus fn(string $stueck): void
 */
function komp_ausgeben_passphrase(string $datei, string $passwort, callable $hinaus): int
{
    if (!komp_name_gueltig($datei)) { throw new RuntimeException('Unbekanntes Backup.'); }
    if (strlen($passwort) < 8) {
        throw new RuntimeException('Die Passphrase muss mindestens 8 Zeichen haben.');
    }
    $schluessel = serverschluessel();
    if ($schluessel === null) {
        throw new RuntimeException('Ohne Serverschlüssel lässt sich das Backup nicht öffnen.');
    }
    $quelle = komp_wurzel() . '/' . $datei;
    $alt = komp_kopf_lesen($quelle);
    if ($alt === null) { throw new RuntimeException('Das ist kein Komplett-Backup im Format EDKOMP1.'); }

    $salz   = bin2hex(random_bytes(16));
    $runden = komp_kdf_runden();
    $kdf = ['art' => 'pbkdf2', 'hash' => 'sha256', 'iter' => $runden, 'salz' => $salz];
    $neuerKopf = komp_kopf_bauen($alt['kopf'], $kdf) . "\n";
    $neuerSchluessel = komp_schluessel_aus_passwort($passwort, $salz, $runden);
    $bindung = komp_bindung($neuerKopf);

    $hinaus(KOMP_SIEGEL);
    $hinaus($neuerKopf);
    return komp_oeffnen($quelle, $schluessel,
        function (string $klar, int $i, bool $letzte) use ($hinaus, $neuerSchluessel, $bindung): void {
            $nonce = random_bytes(KOMP_NONCE_LEN);
            $tag = '';
            $chiffre = openssl_encrypt($klar, 'aes-256-gcm', $neuerSchluessel,
                                       OPENSSL_RAW_DATA, $nonce, $tag,
                                       komp_aad($bindung, $i, $letzte), KOMP_TAG_LEN);
            if ($chiffre === false) {
                throw new RuntimeException('Das Umsiegeln ist fehlgeschlagen (Block ' . $i . ').');
            }
            $hinaus(pack('N', strlen($chiffre)) . $nonce . $tag . $chiffre);
        });
}

/**
 * Der Schluessel, mit dem sich eine gegebene Datei oeffnen laesst.
 *
 * Der Kopf sagt, welcher gilt: `kdf: null` heisst Serverschluessel, sonst
 * Passphrase. Wer das raten muesste, raete beim Wiederanlauf — also an dem
 * einen Tag, an dem niemand Zeit zum Raten hat.
 */
function komp_schluessel_fuer(array $kopf, ?string $passwort): string
{
    $kdf = $kopf['kdf'] ?? null;
    if ($kdf === null) {
        $s = serverschluessel();
        if ($s === null) {
            throw new RuntimeException('Diese Datei ist mit dem Serverschlüssel versiegelt; '
                . 'er steht aber nicht in config.php. Er gehört ins Wiederanlaufpaket.');
        }
        return $s;
    }
    if (!is_array($kdf) || ($kdf['art'] ?? '') !== 'pbkdf2') {
        throw new RuntimeException('Die Datei nennt eine unbekannte Schlüsselableitung.');
    }
    if ($passwort === null || $passwort === '') {
        throw new RuntimeException('Diese Datei ist mit einer Passphrase versiegelt.');
    }
    return komp_schluessel_aus_passwort($passwort, (string)($kdf['salz'] ?? ''),
                                        (int)($kdf['iter'] ?? 0));
}
