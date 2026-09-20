<?php
declare(strict_types=1);

/**
 * ZAEHLUNG — haelt jede Sache ihre eine Stelle?
 * ===========================================================================
 *
 * Aufruf:  php tools/zaehlung/zaehlen.php [--selbstprobe] [--stellen]
 *          php tools/zaehlung/zaehlen.php --zeile=Z19 --stellen
 * Rueckgabe: 0 = jede Zeile auf oder unter ihrer Decke · 1 = eine darueber
 *            · 2 = die Zaehlung kam nicht los
 *
 * WOGEGEN. R83 sagt, zentralisiert wird beim zweiten echten Verbraucher. Der
 * Beleg, aus dem die Entscheidung entstand, ist `edbak_groesse_text()`: Die
 * Funktion lag in `adminbackup_lib.php`, neun fremde Dateien luden die
 * Backup-Bibliothek nur, um Bytes lesbar zu machen — 43 Aufrufe in zehn
 * Dateien. Das ist nicht an einem Tag passiert und niemandem aufgefallen.
 * Schritt 15 raeumt rund vierzig solcher Muster auf; dieses Werkzeug haelt
 * das Ergebnis fest. Jedes Muster bekommt eine **Decke**; liegt der Ist-Wert
 * darueber, schlaegt die Zaehlung an. So kommt eine zweite Stelle nicht
 * unbemerkt zurueck (E-ZE-24).
 *
 * WAS ES NICHT IST. Kein Linter, keine Codeanalyse, kein Urteil ueber
 * Qualitaet. Es zaehlt Vorkommen benannter Muster und vergleicht die Zahl mit
 * einer Zahl, die im Register steht. Ob ein Treffer richtig oder falsch ist,
 * entscheidet das Register ueber seine Ausnahmen — nicht das Werkzeug.
 *
 * DIE DREI SICHTEN, und warum es drei sind:
 *
 * - `php_ohne_zeichenketten` — Code ohne Kommentare UND ohne den Inhalt von
 *   Zeichenketten. Sicht fuer Funktionsaufrufe. Wer `error_log(` mit `grep`
 *   sucht, findet jede Erwaehnung in jedem Kommentar; die erste Fassung der
 *   Sitzungshaertung meldete auf diesem Weg zwei Befunde, und beide waren
 *   Kommentarzeilen, die das Werkzeug selbst beschrieben.
 * - `php_mit_zeichenketten` — Code ohne Kommentare, Zeichenketteninhalt
 *   erhalten. Sicht fuer SQL: `INSERT INTO app_state` steht in einem
 *   Literal, nicht im Code.
 * - `js_und_inline` — JavaScript. Fuer `.js` die ganze Datei, fuer `.php`
 *   die Inhalte der `<script>`-Bloecke des HTML-Anteils. Beides ohne
 *   Kommentare. Damit ist „Zeichenkette im JS-Kontext" eine Sicht und keine
 *   Schaetzung: Eine breite Suche nach `class="meldung` ueber den Quelltext
 *   zaehlt das PHP-Markup mit (35 Erwaehnungen in 15 Dateien, gemessen
 *   20.09.2026) und misst damit etwas anderes als gefragt war.
 *
 * ZEILENTREU. Alle drei Sichten haben so viele Zeilen wie die Quelle: Was
 * wegfaellt, wird durch Leerzeichen ersetzt, Zeilenumbrueche bleiben stehen.
 * Nur deshalb zeigt `--stellen` auf die Stelle im Original. Uebernommen aus
 * `tools/wortliste/zerlegen.py`, wo dieselbe Regel und derselbe Grund steht.
 *
 * DER JS-ZERLEGER IST EINE HEURISTIK, keine ECMAScript-Grammatik — Portierung
 * von `js_bereiche()` aus `tools/wortliste/zerlegen.py` samt deren Grenzen:
 * Division und regulaerer Ausdruck werden am zuletzt gesehenen
 * bedeutungstragenden Zeichen unterschieden, verschachtelte `${…}` in
 * Template-Literalen gelten als Teil der Zeichenkette. Im Zweifel bleibt
 * stehen, was nicht sicher ein Kommentar ist: Ein Treffer zu viel kostet eine
 * Ausnahme, ein Treffer zu wenig kostet die Aussage.
 *
 * WAS ES NICHT MESSEN KANN:
 *
 * - **Ob ein Treffer erreichbar ist.** Eine Anweisung in einem `if`, das nie
 *   zutrifft, zaehlt mit.
 * - **Ob zwei Stellen dieselbe Sache tun.** Das Register behauptet es; die
 *   Pruefung dieser Behauptung ist Lesearbeit und steht im Konzept.
 * - **Code, der zur Laufzeit entsteht** (`eval`, zusammengesetzte
 *   Funktionsnamen, JS aus einer PHP-Zeichenkette). Kommt in `server/` nicht
 *   vor; sollte es einmal vorkommen, sieht die Zaehlung es nicht.
 * - **`vendor/`.** Fremdcode ist ausgenommen, in `server/vendor/` wie in
 *   `server/assets/vendor/`.
 */

const ZH_WURZEL   = __DIR__;
const ZH_REPO     = __DIR__ . '/../..';
const ZH_SERVER   = __DIR__ . '/../../server';

/* ===========================================================================
 * 1. Zerleger — die drei Sichten
 * ======================================================================== */

/** Bereich durch Leerzeichen ersetzen, Zeilenumbrueche behalten. */
function zh_leeren(array &$z, int $von, int $bis): void
{
    $bis = min($bis, count($z));
    for ($k = max(0, $von); $k < $bis; $k++) {
        if ($z[$k] !== "\n") { $z[$k] = ' '; }
    }
}

/* Zeichen, nach denen ein `/` einen regulaeren Ausdruck beginnen kann und
 * keine Division ist. Nach `)` oder `]` steht ein Wert — dort ist `/`
 * Division. Diese Unterscheidung ist der ganze Trick. (aus zerlegen.py) */
const ZH_VOR_REGEX_ZEICHEN = "(,=:[!&|?{};+-*%~^<>\n";
const ZH_VOR_REGEX_WOERTER = ['return','typeof','case','in','of','new','delete',
                              'void','instanceof','do','else','yield','await','throw'];

/** Das Wort unmittelbar vor Stelle $i (fuer `return /…/`). */
function zh_wort_davor(string $text, int $i): string
{
    $j = $i;
    while ($j > 0 && (ctype_alnum($text[$j - 1]) || $text[$j - 1] === '_')) { $j--; }
    return substr($text, $j, $i - $j);
}

/** Kommentarbereiche in JavaScript, als [start, ende]. */
function zh_js_kommentare(string $text, int $von = 0, ?int $bis = null): array
{
    $n = $bis ?? strlen($text);
    $i = $von;
    $zuletzt = "\n";          // als stuende der Zerleger am Zeilenanfang
    $bereiche = [];
    while ($i < $n) {
        $c = $text[$i];
        if ($c === '"' || $c === "'") {
            $j = $i + 1;
            while ($j < $n) {
                if ($text[$j] === '\\') { $j += 2; continue; }
                if ($text[$j] === $c || $text[$j] === "\n") { break; }
                $j++;
            }
            $i = $j + 1; $zuletzt = $c; continue;
        }
        if ($c === '`') {
            $j = $i + 1;
            while ($j < $n) {
                if ($text[$j] === '\\') { $j += 2; continue; }
                if ($text[$j] === '`') { break; }
                $j++;
            }
            $i = $j + 1; $zuletzt = '`'; continue;
        }
        if ($c === '/' && $i + 1 < $n && $text[$i + 1] === '/') {
            $j = strpos($text, "\n", $i);
            if ($j === false || $j > $n) { $j = $n; }
            $bereiche[] = [$i, $j]; $i = $j; continue;
        }
        if ($c === '/' && $i + 1 < $n && $text[$i + 1] === '*') {
            $j = strpos($text, '*/', $i + 2);
            $j = ($j === false || $j + 2 > $n) ? $n : $j + 2;
            $bereiche[] = [$i, $j]; $i = $j; continue;
        }
        if ($c === '/' && (str_contains(ZH_VOR_REGEX_ZEICHEN, $zuletzt)
                           || in_array(strtolower(zh_wort_davor($text, $i)), ZH_VOR_REGEX_WOERTER, true))) {
            $j = $i + 1; $klasse = false; $gefunden = -1;
            while ($j < $n) {
                if ($text[$j] === '\\') { $j += 2; continue; }
                if ($text[$j] === "\n") { break; }
                if ($text[$j] === '[')      { $klasse = true; }
                elseif ($text[$j] === ']')  { $klasse = false; }
                elseif ($text[$j] === '/' && !$klasse) { $gefunden = $j; break; }
                $j++;
            }
            if ($gefunden >= 0) { $i = $gefunden + 1; $zuletzt = '/'; continue; }
            /* Kein Abschluss in derselben Zeile: dann war es doch Division. */
        }
        if (!ctype_space($c) || $c === "\n") { $zuletzt = $c; }
        $i++;
    }
    return $bereiche;
}

/* HIER ist die kurze Form `[^>]*` richtig, und das steht da, damit sie beim
 * naechsten Durchgang durch CLAUDE.md 6 nicht „mitkorrigiert" wird: Das
 * Muster laeuft NICHT ueber den Quelltext, sondern ueber die Inline-Sicht —
 * den Text, aus dem der Tokenizer die PHP-Inseln vorher durch Leerzeichen
 * ersetzt hat. Dort gibt es kein `?>` mehr, an dem ein Tag zu frueh enden
 * koennte. Aus `<script<?= kopf_nonce_attr() ?>>` ist bis hierher
 * `<script                        >` geworden. */
const ZH_SCRIPT_AUF = '~<script\b[^>]*>~i';

/**
 * Die drei Sichten einer Datei. Alle zeilentreu zur Quelle.
 *
 * @return array{php_mit_zeichenketten:string, php_ohne_zeichenketten:string, js_und_inline:string}
 */
function zh_sichten(string $pfad, string $quelle): array
{
    $leer = str_repeat(' ', strlen($quelle));
    /* Zeilentreue Leerfassung: nur die Umbrueche bleiben. */
    $leer = preg_replace('~[^\n]~', ' ', $quelle);

    if (str_ends_with($pfad, '.js')) {
        $z = str_split($quelle === '' ? ' ' : $quelle);
        foreach (zh_js_kommentare($quelle) as [$a, $b]) { zh_leeren($z, $a, $b); }
        return ['php_mit_zeichenketten' => $leer,
                'php_ohne_zeichenketten' => $leer,
                'js_und_inline' => implode('', $z)];
    }

    $mit   = str_split($quelle === '' ? ' ' : $quelle);
    $ohne  = $mit;
    $inline = str_split($leer === '' ? ' ' : $leer);

    $tok = @token_get_all($quelle);
    $pos = 0;
    foreach ($tok as $t) {
        $text = is_array($t) ? $t[1] : $t;
        $len  = strlen($text);
        $art  = is_array($t) ? $t[0] : null;

        if ($art === T_COMMENT || $art === T_DOC_COMMENT) {
            zh_leeren($mit, $pos, $pos + $len);
            zh_leeren($ohne, $pos, $pos + $len);
        } elseif ($art === T_INLINE_HTML) {
            zh_leeren($mit, $pos, $pos + $len);
            zh_leeren($ohne, $pos, $pos + $len);
            for ($k = 0; $k < $len; $k++) { $inline[$pos + $k] = $text[$k]; }
        } elseif ($art === T_CONSTANT_ENCAPSED_STRING) {
            /* Anfuehrungszeichen stehen lassen, Inhalt leeren — so bleibt die
             * Sicht syntaktisch lesbar und `'manual-` verschwindet wirklich. */
            zh_leeren($ohne, $pos + 1, $pos + $len - 1);
        } elseif ($art === T_ENCAPSED_AND_WHITESPACE || $art === T_STRING_VARNAME) {
            zh_leeren($ohne, $pos, $pos + $len);
        }
        $pos += $len;
    }

    /* Aus dem HTML-Anteil bleibt nur, was in einem <script>-Block steht —
     * das ist die Regel „Zeichenkette im JS-Kontext". Kommentare darin weg. */
    $html = implode('', $inline);
    $nur_js = str_split($leer === '' ? ' ' : $leer);
    $klein = strtolower($html);
    if (preg_match_all(ZH_SCRIPT_AUF, $html, $tr, PREG_OFFSET_CAPTURE)) {
        foreach ($tr[0] as [$treffer, $off]) {
            $anfang = $off + strlen($treffer);
            $ende = strpos($klein, '</script>', $anfang);
            if ($ende === false) { $ende = strlen($html); }
            for ($k = $anfang; $k < $ende; $k++) { $nur_js[$k] = $html[$k]; }
            foreach (zh_js_kommentare($html, $anfang, $ende) as [$a, $b]) {
                zh_leeren($nur_js, $a, $b);
            }
        }
    }

    return ['php_mit_zeichenketten' => implode('', $mit),
            'php_ohne_zeichenketten' => implode('', $ohne),
            'js_und_inline' => implode('', $nur_js)];
}

/* ===========================================================================
 * 2. Aufrufe aus dem Tokenstrom
 * ======================================================================== */

/**
 * Echte Aufrufe der genannten Funktionen — Zeilennummern.
 *
 * Kein Treffer ist: der Name in einem Kommentar, der Name in einer
 * Zeichenkette, die Definition (`function foo`), ein Methodenaufruf
 * (`$o->foo(`, `Foo::foo(`) und ein Name ohne folgende Klammer.
 */
function zh_aufrufe(string $quelle, array $namen): array
{
    $namen = array_map('strtolower', $namen);
    $tok = @token_get_all($quelle);
    $treffer = [];
    foreach ($tok as $i => $t) {
        if (!is_array($t) || $t[0] !== T_STRING) { continue; }
        if (!in_array(strtolower($t[1]), $namen, true)) { continue; }
        $vor = $i - 1;
        while ($vor >= 0 && is_array($tok[$vor]) && $tok[$vor][0] === T_WHITESPACE) { $vor--; }
        if ($vor >= 0 && is_array($tok[$vor])
            && in_array($tok[$vor][0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW], true)) { continue; }
        if ($vor >= 0 && !is_array($tok[$vor]) && $tok[$vor] === '$') { continue; }
        $nach = $i + 1;
        while (isset($tok[$nach]) && is_array($tok[$nach]) && $tok[$nach][0] === T_WHITESPACE) { $nach++; }
        if (!isset($tok[$nach]) || $tok[$nach] !== '(') { continue; }
        $treffer[] = $t[2];
    }
    return $treffer;
}

/** Definitionen der genannten Funktionen — Zeilennummern. */
function zh_definitionen(string $quelle, array $namen): array
{
    $namen = array_map('strtolower', $namen);
    $tok = @token_get_all($quelle);
    $treffer = [];
    foreach ($tok as $i => $t) {
        if (!is_array($t) || $t[0] !== T_STRING) { continue; }
        if (!in_array(strtolower($t[1]), $namen, true)) { continue; }
        $vor = $i - 1;
        while ($vor >= 0 && is_array($tok[$vor]) && $tok[$vor][0] === T_WHITESPACE) { $vor--; }
        if ($vor >= 0 && is_array($tok[$vor]) && $tok[$vor][0] === T_FUNCTION) { $treffer[] = $t[2]; }
    }
    return $treffer;
}

/** Echte Methodenaufrufe `->name(` — Zeilennummern (fuer `$pdo->beginTransaction()`). */
function zh_methodenaufrufe(string $quelle, array $namen): array
{
    $namen = array_map('strtolower', $namen);
    $tok = @token_get_all($quelle);
    $treffer = [];
    foreach ($tok as $i => $t) {
        if (!is_array($t) || $t[0] !== T_STRING) { continue; }
        if (!in_array(strtolower($t[1]), $namen, true)) { continue; }
        $vor = $i - 1;
        while ($vor >= 0 && is_array($tok[$vor]) && $tok[$vor][0] === T_WHITESPACE) { $vor--; }
        if ($vor < 0 || !is_array($tok[$vor])
            || !in_array($tok[$vor][0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) { continue; }
        $nach = $i + 1;
        while (isset($tok[$nach]) && is_array($tok[$nach]) && $tok[$nach][0] === T_WHITESPACE) { $nach++; }
        if (!isset($tok[$nach]) || $tok[$nach] !== '(') { continue; }
        $treffer[] = $t[2];
    }
    return $treffer;
}

/* ===========================================================================
 * 3. Bestand und Messung
 * ======================================================================== */

/** Zeilennummer zu einem Byte-Versatz. */
function zh_zeile(string $text, int $versatz): int
{
    return substr_count($text, "\n", 0, min($versatz, strlen($text))) + 1;
}

/** Alle gezaehlten Dateien mit ihren Sichten. `vendor/` bleibt draussen. */
function zh_bestand(): array
{
    $wurzel = realpath(ZH_SERVER);
    if ($wurzel === false || !is_dir($wurzel)) {
        fwrite(STDERR, "server/ nicht gefunden.\n");
        exit(2);
    }
    $pfade = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wurzel));
    foreach ($it as $f) {
        if (!$f->isFile()) { continue; }
        $p = $f->getPathname();
        if (str_contains($p, '/vendor/')) { continue; }
        if (!in_array($f->getExtension(), ['php', 'js'], true)) { continue; }
        $pfade[] = $p;
    }
    sort($pfade);

    $bestand = [];
    foreach ($pfade as $p) {
        $rel = 'server/' . substr($p, strlen($wurzel) + 1);
        $quelle = (string)file_get_contents($p);
        $bestand[$rel] = ['quelle' => $quelle, 'sichten' => zh_sichten($p, $quelle)];
    }
    return $bestand;
}

/** Faellt diese Datei in den Bereich der Registerzeile? */
function zh_gilt(string $rel, array $zeile): bool
{
    $bereich = $zeile['bereich'] ?? 'php';
    $ist_js  = str_ends_with($rel, '.js');
    if ($bereich === 'php' && $ist_js) { return false; }
    if ($bereich === 'js_und_inline' && !$ist_js && !str_ends_with($rel, '.php')) { return false; }
    if ($bereich === 'api' && !str_starts_with($rel, 'server/api/')) { return false; }
    if (isset($zeile['nur']) && $rel !== $zeile['nur']) { return false; }
    foreach (($zeile['ausser'] ?? []) as $aus) {
        if ($rel === $aus || str_starts_with($rel, rtrim($aus, '/') . '/')) { return false; }
    }
    return true;
}

/**
 * Eine Registerzeile ueber dem Bestand messen.
 *
 * @return array{treffer:int, dateien:int, stellen:array<int,string>}
 */
function zh_messen(array $zeile, array $bestand): array
{
    $regel = $zeile['regel'];
    $sicht = $zeile['sicht'];
    $stellen = []; $treffer = 0; $dateien = 0;

    foreach ($bestand as $rel => $d) {
        if (!zh_gilt($rel, $zeile)) { continue; }
        $nummern = [];

        switch ($regel['art']) {
            case 'aufruf':
                if (!str_ends_with($rel, '.php')) { break; }
                $nummern = zh_aufrufe($d['quelle'], $regel['namen']);
                break;
            case 'methode':
                if (!str_ends_with($rel, '.php')) { break; }
                $nummern = zh_methodenaufrufe($d['quelle'], $regel['namen']);
                break;
            case 'definition':
                if (!str_ends_with($rel, '.php')) { break; }
                $nummern = zh_definitionen($d['quelle'], $regel['namen']);
                break;
            case 'muster':
                $text = $d['sichten'][$sicht];
                if (preg_match_all($regel['muster'], $text, $tr, PREG_OFFSET_CAPTURE)) {
                    foreach ($tr[0] as [$_, $off]) {
                        if (isset($regel['nicht'])) {
                            /* Zeile des Treffers gegen ein Ausschlussmuster halten. */
                            $za = strrpos(substr($text, 0, $off), "\n");
                            $za = $za === false ? 0 : $za + 1;
                            $ze = strpos($text, "\n", $off);
                            $ze = $ze === false ? strlen($text) : $ze;
                            if (preg_match($regel['nicht'], substr($text, $za, $ze - $za))) { continue; }
                        }
                        $nummern[] = zh_zeile($text, $off);
                    }
                }
                break;
            case 'eigen':
                $fn = 'zh_regel_' . $regel['name'];
                $nummern = $fn($rel, $d['sichten'], $d['quelle']);
                break;
            default:
                fwrite(STDERR, "Unbekannte Regelart: {$regel['art']}\n");
                exit(2);
        }

        if ($nummern === []) { continue; }
        $dateien++;
        $treffer += count($nummern);
        foreach ($nummern as $nr) { $stellen[] = $rel . ':' . $nr; }
    }

    if (($zeile['zaehlt'] ?? 'treffer') === 'dateien') { $treffer = $dateien; }
    return ['treffer' => $treffer, 'dateien' => $dateien, 'stellen' => $stellen];
}

/* ===========================================================================
 * 4. Eigene Regeln — was ein Muster nicht trifft
 * ======================================================================== */

/**
 * Z03 — `config.php` lesend einbinden.
 *
 * Nicht jede Erwaehnung des Pfades ist eine Lesestelle: `is_file()`,
 * `file_get_contents()` und das Schreiben in `serverkrypto_lib.php` nennen
 * ihn auch. Gezaehlt wird ein `require`/`include`, dessen Ziel die Datei ist —
 * unmittelbar (`require __DIR__ . '/config.php'`) ODER ueber eine Variable,
 * die den Pfad wenige Zeilen davor bekommen hat (`instanz_lib.php`: `$datei`
 * auf der einen, `require $datei` auf der naechsten Zeile).
 */
function zh_regel_konfig_lesestelle(string $rel, array $sichten, string $quelle): array
{
    $tok = @token_get_all($quelle);
    $treffer = [];
    foreach ($tok as $i => $t) {
        if (!is_array($t)) { continue; }
        if (!in_array($t[0], [T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE], true)) { continue; }
        /* Die Anweisung bis zum naechsten Semikolon einsammeln. */
        $stueck = ''; $var = null;
        for ($j = $i + 1; $j < count($tok); $j++) {
            $s = is_array($tok[$j]) ? $tok[$j][1] : $tok[$j];
            if ($s === ';') { break; }
            if (is_array($tok[$j]) && $tok[$j][0] === T_VARIABLE && $var === null) { $var = $tok[$j][1]; }
            $stueck .= $s;
        }
        if (str_contains($stueck, '/config.php')) { $treffer[] = $t[2]; continue; }
        if ($var === null) { continue; }
        /* Ueber die Variable: wo wurde sie zuletzt gesetzt? */
        $zeilen = explode("\n", $sichten['php_mit_zeichenketten']);
        $von = max(0, $t[2] - 1 - 10);
        $davor = implode("\n", array_slice($zeilen, $von, $t[2] - 1 - $von));
        if (preg_match('~' . preg_quote($var, '~') . '\s*=[^;]*?/config\.php~s', $davor)) {
            $treffer[] = $t[2];
        }
    }
    return $treffer;
}

/**
 * Z07 — „Der Rumpf ist kein JSON-Objekt", von Hand beantwortet.
 *
 * Gemeint ist NUR der Eingang, nicht die inhaltliche Pruefung danach
 * (E-ZE-15). Der Unterschied haengt an der geprueften Variablen: `$b` ist der
 * Rumpf (aus `json_decode`), `$m` in `api/pat_anheben.php` ist ein Element
 * daraus — dessen `'format'` bleibt, wo es ist. Deshalb: Die Fehlerantwort
 * zaehlt, wenn ihre Bedingung `is_array()` auf eine Variable anwendet, die
 * in dieser Datei aus `json_decode(` kommt.
 */
function zh_regel_rumpf_kein_objekt(string $rel, array $sichten, string $quelle): array
{
    $text = $sichten['php_mit_zeichenketten'];
    if (!preg_match_all('~\$(\w+)\s*=\s*json_decode\s*\(~', $text, $v)) { return []; }
    $rumpf = array_unique($v[1]);
    $zeilen = explode("\n", $text);
    $treffer = [];
    if (!preg_match_all('~[\'"]error[\'"]\s*=>\s*[\'"](payload|format)[\'"]~', $text, $tr, PREG_OFFSET_CAPTURE)) {
        return [];
    }
    foreach ($tr[0] as [$_, $off]) {
        $nr = zh_zeile($text, $off);
        /* Die Bedingung steht auf derselben oder einer der drei Zeilen davor. */
        $von = max(0, $nr - 4);
        $umfeld = implode("\n", array_slice($zeilen, $von, $nr - $von));
        foreach ($rumpf as $r) {
            if (preg_match('~is_array\s*\(\s*\$' . preg_quote($r, '~') . '\b~', $umfeld)) {
                $treffer[] = $nr;
                break;
            }
        }
    }
    return $treffer;
}

/** Die Spalten von `missions` — aus `schema.sql`, einmal gelesen. */
function zh_missions_spalten(): array
{
    static $spalten = null;
    if ($spalten !== null) { return $spalten; }
    $s = (string)@file_get_contents(ZH_SERVER . '/schema.sql');
    if (!preg_match('~CREATE TABLE missions \((.*?)\n\)~s', $s, $m)) {
        fwrite(STDERR, "schema.sql: Tabelle missions nicht gefunden.\n");
        exit(2);
    }
    $spalten = [];
    foreach (explode("\n", $m[1]) as $z) {
        $z = trim($z);
        if ($z === '' || str_starts_with($z, '--')) { continue; }
        if (preg_match('~^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|FOREIGN)~i', $z)) { continue; }
        if (preg_match('~^([a-z_][a-z0-9_]*)\s~i', $z, $t)) { $spalten[] = $t[1]; }
    }
    return $spalten;
}

const ZH_HANDLISTE_LUECKE  = 60;   // Zeichen zwischen zwei Spaltennamen
const ZH_HANDLISTE_SCHWELLE = 10;  // so viele verschiedene Namen machen eine Liste

/**
 * Z18 — Handlisten der `missions`-Spalten.
 *
 * NICHT „zehn Spaltennamen irgendwo in der Datei": `id`, `final`, `notes`
 * und `origin` stehen ueberall. Eine Handliste ist eine AUFZAEHLUNG — zehn
 * verschiedene Namen, zwischen denen nie mehr als vierzig Zeichen liegen.
 * Das trifft die SQL-Spaltenliste und das Ausgabefeld-Array gleichermassen
 * und laesst zehn Namen, die ueber zweihundert Zeilen verstreut sind, liegen.
 *
 * UND DIE TABELLE MUSS STIMMEN: `rest_segments` teilt sich den halben
 * Spaltensatz mit `missions` (`user_id, client_ref, day_id, started_at,
 * ended_at, final, geraet_art, geraet_modell, deleted_at, deleted_with_day`
 * — genau zehn). Ohne diese Pruefung meldet die Zeile zwei Ruhesegment-
 * Anweisungen als Handlisten der Einsatztabelle. Gesucht wird die
 * Tabellenangabe vor dem Lauf (INSERT INTO x, UPDATE x) und dahinter
 * (SELECT … FROM x); findet sich keine, ist es eine PHP-Liste
 * (`$missionSpalten`, `$missions[] = […]`) und zaehlt.
 */
function zh_regel_missions_handliste(string $rel, array $sichten, string $quelle): array
{
    $text = $sichten['php_mit_zeichenketten'];
    $vor = [];
    foreach (zh_missions_spalten() as $c) {
        if (preg_match_all('~\b' . $c . '\b~', $text, $tr, PREG_OFFSET_CAPTURE)) {
            foreach ($tr[0] as [$_, $o]) { $vor[] = [$o, $c]; }
        }
    }
    if ($vor === []) { return []; }
    usort($vor, static fn($a, $b) => $a[0] <=> $b[0]);

    $treffer = []; $lauf = []; $start = 0; $letzt = -99999; $ende = 0;
    $abschluss = static function () use (&$lauf, &$start, &$ende, &$treffer, $text) {
        if (count($lauf) < ZH_HANDLISTE_SCHWELLE) { $lauf = []; return; }
        $davor  = substr($text, max(0, $start - 220), min(220, $start));
        /* VORWAERTS AB DEM LAUFANFANG, nicht ab seinem Ende: Bei einem SELECT
         * steht die Tabelle HINTER der Spaltenliste, und je groesser die
         * zugelassene Luecke, desto weiter reicht der Lauf ueber das `FROM`
         * hinaus. Ab $ende gesucht, findet die Probe dann das naechste FROM
         * statt des eigenen — und `backup_lib.php` Z. 464
         * (`… FROM rest_segments`) rutschte als Handliste der Einsatztabelle
         * durch. */
        $danach = substr($text, $start, ($ende - $start) + 220);
        $tab = '';
        if (preg_match_all('~(?:INSERT\s+(?:IGNORE\s+)?INTO|REPLACE\s+INTO|UPDATE)\s+`?(\w+)`?~i', $davor, $t)) {
            $tab = strtolower((string)end($t[1]));
        } elseif (preg_match('~\bFROM\s+`?(\w+)`?~i', $danach, $t)) {
            $tab = strtolower($t[1]);
        }
        if ($tab === '' || $tab === 'missions') { $treffer[] = zh_zeile($text, $start); }
        $lauf = [];
    };
    foreach ($vor as [$o, $c]) {
        if ($o - $letzt > ZH_HANDLISTE_LUECKE) { $abschluss(); $start = $o; }
        $lauf[$c] = 1;
        $letzt = $ende = $o + strlen($c);
    }
    $abschluss();
    return $treffer;
}

/**
 * Z27 — `date('…')` ohne Zeitstempel: das ist „jetzt", in der Zeitzone der
 * `php.ini` (FF-1). Mit zweitem Argument rechnet die Funktion einen
 * uebergebenen Zeitpunkt um und ist damit nicht gemeint.
 *
 * UND DAS FORMAT MUSS EINE ZEICHENKETTE SEIN. `date(DATE_RFC2822)` in
 * `smtp.php` setzt den `Date:`-Kopf einer ausgehenden Mail; das Format traegt
 * den UTC-Versatz mit (`+0200`), die Angabe ist also vollstaendig und in
 * JEDER Serverzeitzone richtig. Sie ist kein „heute" und gehoert nicht zu
 * `heute_lokal()` (F-ZE-1). Ohne diese Bedingung meldet die Zeile sie als
 * fuenften Treffer — richtig gezaehlt, falsch zugeordnet.
 */
function zh_regel_date_ohne_zeitstempel(string $rel, array $sichten, string $quelle): array
{
    $tok = @token_get_all($quelle);
    $treffer = [];
    foreach ($tok as $i => $t) {
        if (!is_array($t) || $t[0] !== T_STRING || strtolower($t[1]) !== 'date') { continue; }
        $vor = $i - 1;
        while ($vor >= 0 && is_array($tok[$vor]) && $tok[$vor][0] === T_WHITESPACE) { $vor--; }
        if ($vor >= 0 && is_array($tok[$vor])
            && in_array($tok[$vor][0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NEW], true)) { continue; }
        $j = $i + 1;
        while (isset($tok[$j]) && is_array($tok[$j]) && $tok[$j][0] === T_WHITESPACE) { $j++; }
        if (!isset($tok[$j]) || $tok[$j] !== '(') { continue; }
        /* Erstes Argument: eine Zeichenkette? */
        $erst = $j + 1;
        while (isset($tok[$erst]) && is_array($tok[$erst]) && $tok[$erst][0] === T_WHITESPACE) { $erst++; }
        if (!isset($tok[$erst]) || !is_array($tok[$erst])
            || $tok[$erst][0] !== T_CONSTANT_ENCAPSED_STRING) { continue; }
        /* Kommas auf oberster Ebene zaehlen — eines heisst: mit Zeitstempel. */
        $tiefe = 0; $kommas = 0;
        for ($k = $j; $k < count($tok); $k++) {
            $s = is_array($tok[$k]) ? $tok[$k][1] : $tok[$k];
            if ($s === '(' || $s === '[') { $tiefe++; }
            elseif ($s === ')' || $s === ']') { $tiefe--; if ($tiefe === 0) { break; } }
            elseif ($s === ',' && $tiefe === 1) { $kommas++; }
        }
        if ($kommas === 0) { $treffer[] = $t[2]; }
    }
    return $treffer;
}

/**
 * Z34 — die vierzehn Formatierer-Definitionen (Liste in `register.php`).
 * Eine Zuweisung aus dem Modul (`const fmtTag = EdMissionTable.fmtTag;` in
 * `zeitraum.php`) ist KEINE Definition, sondern genau das Gegenteil — der
 * Verbraucher, den dieses Paket ueberall haben will.
 */
function zh_regel_js_formatierer(string $rel, array $sichten, string $quelle): array
{
    static $namen = null;
    if ($namen === null) {
        $namen = [];
        foreach (ZH_FORMATIERER as $liste) { foreach ($liste as $n) { $namen[$n] = 1; } }
        $namen = array_keys($namen);
    }
    $text = $sichten['js_und_inline'];
    $treffer = [];
    foreach ($namen as $n) {
        $m = '~function\s+' . $n . '\s*\('
           . '|(?:const|let|var)\s+' . $n . '\s*=\s*(?:async\s+)?(?:function\b|\()~';
        if (preg_match_all($m, $text, $tr, PREG_OFFSET_CAPTURE)) {
            foreach ($tr[0] as [$_, $o]) { $treffer[] = zh_zeile($text, $o); }
        }
    }
    sort($treffer);
    return $treffer;
}

/* ===========================================================================
 * 5. Selbstprobe — zaehlt die Zaehlung ueberhaupt richtig?
 * ======================================================================== */

/**
 * Faelle mit Sollergebnis. Darunter die drei, die das Konzept ausdruecklich
 * verlangt (Aufruf im Kommentar, Aufruf in einer Zeichenkette, Methodenaufruf
 * `->date(`), und die Fallen, an denen ein `grep` scheitert.
 */
function zh_selbstprobe(): int
{
    $faelle = [
        /* --- Aufrufe aus dem Tokenstrom ---------------------------------- */
        ['<?php error_log("x");',                     'aufruf', ['error_log'], 1, 'nackter Aufruf'],
        ["<?php\n/* error_log( im Kommentar */",      'aufruf', ['error_log'], 0, 'Aufruf im Kommentar zaehlt nicht'],
        ['<?php $s = "error_log(";',                  'aufruf', ['error_log'], 0, 'Aufruf in Zeichenkette zaehlt nicht'],
        ["<?php // error_log('x');",                  'aufruf', ['error_log'], 0, 'Aufruf im Zeilenkommentar zaehlt nicht'],
        ['<?php function error_log() {}',             'aufruf', ['error_log'], 0, 'Definition ist kein Aufruf'],
        ['<?php $o->date("Y");',                      'aufruf', ['date'],      0, 'Methodenaufruf ->date( zaehlt nicht'],
        ['<?php Foo::date("Y");',                     'aufruf', ['date'],      0, 'Aufruf ::date( zaehlt nicht'],
        ['<?php $x = error_logged();',                'aufruf', ['error_log'], 0, 'aehnlicher Name zaehlt nicht'],
        ['<?php @session_start();',                   'aufruf', ['session_start'], 1, 'mit @ davor'],
        ['<?php $x = date;',                          'aufruf', ['date'],      0, 'Name ohne Klammer'],
        /* --- Methodenaufrufe --------------------------------------------- */
        ['<?php $pdo->beginTransaction();',           'methode', ['beginTransaction'], 1, 'Methodenaufruf wird gezaehlt'],
        ['<?php beginTransaction();',                 'methode', ['beginTransaction'], 0, 'freie Funktion ist keine Methode'],
        ["<?php /* \$pdo->beginTransaction(); */",    'methode', ['beginTransaction'], 0, 'Methodenaufruf im Kommentar'],
        /* --- Sicht: mit Zeichenketten ------------------------------------ */
        ["<?php \$q = 'SELECT v FROM app_state';",    'muster', '~FROM\s+app_state~i', 1, 'SQL im Literal: Sicht MIT Zeichenketten'],
        ["<?php\n/* FROM app_state im Kommentar */",  'muster', '~FROM\s+app_state~i', 0, 'SQL im Kommentar zaehlt nicht'],
        /* --- Sicht: ohne Zeichenketten ----------------------------------- */
        ["<?php \$s = 'manual-7';",                   'muster_ohne', '~manual-~', 0, 'Literal in der Sicht OHNE Zeichenketten weg'],
        /* --- Sicht: JavaScript ------------------------------------------- */
        ["<?php ?>\n<script>var t = 'X-CSRF';</script>", 'js', '~[\'\"]X-CSRF[\'\"]~', 1, 'JS im <script>-Block zaehlt'],
        ["<?php ?>\n<script>// 'X-CSRF'\n</script>",  'js', '~[\'\"]X-CSRF[\'\"]~', 0, 'JS-Kommentar zaehlt nicht'],
        ["<?php ?>\n<p class=\"meldung\">x</p>",      'js', '~class\s*=\s*[\'\"]meldung~', 0, 'PHP-Markup ist kein JS'],
        ["<?php ?>\n<script>h = '<p class=\"meldung\">';</script>", 'js', '~class\s*=\s*[\'\"]meldung~', 1, 'Markup IM JS zaehlt'],
        ["<?php ?>\n<script<?= nonce() ?>>var t='X-CSRF';</script>", 'js', '~[\'\"]X-CSRF[\'\"]~', 1, 'Tag mit PHP-Insel: `?>` beendet ihn nicht'],
        ["<?php ?>\n<script>var u='https://x/y';\n// weg\nvar v='X-CSRF';</script>", 'js', '~[\'\"]X-CSRF[\'\"]~', 1, 'URL mit // ist kein Kommentar'],
        /* --- Zeilentreue -------------------------------------------------- */
        ["<?php\n\n\n\$s = 'manual-7';", 'zeile', '~manual-~', 4, 'Treffer meldet Zeile 4, nicht Zeile 1'],
        /* --- date() mit und ohne Zeitstempel ------------------------------ */
        ["<?php \$d = date('Y-m-d');",                'date1', null, 1, "date('Y-m-d') ohne Zeitstempel"],
        ["<?php \$d = date('Y-m-d', \$ts);",          'date1', null, 0, 'date() MIT Zeitstempel zaehlt nicht'],
        ["<?php \$d = date('Y-m-d', mktime(0,0,0));", 'date1', null, 0, 'Komma in der inneren Klammer taeuscht nicht'],
        ['<?php $d = date(DATE_RFC2822);',            'date1', null, 0, 'date(KONSTANTE) traegt den Versatz mit'],
        ["<?php \$o->date('Y-m-d');",                 'date1', null, 0, 'Methodenaufruf ->date( ist kein date()'],
        /* --- Handliste: die Tabelle muss stimmen -------------------------- */
        ["<?php \$p->prepare('INSERT INTO missions (user_id, client_ref, day_id, started_at, ended_at, final, origin, edited, geraet_art, geraet_modell, notes, deleted_at)');",
         'handliste', null, 1, 'zwoelf missions-Spalten sind eine Handliste'],
        ["<?php \$p->prepare('INSERT INTO rest_segments (user_id, client_ref, day_id, started_at, ended_at, final, geraet_art, geraet_modell, deleted_at, deleted_with_day)');",
         'handliste', null, 0, 'rest_segments teilt zehn Namen — zaehlt nicht'],
        ["<?php \$p->prepare('SELECT id, client_ref, day_id, started_at, ended_at, final, origin, edited, geraet_art, geraet_modell FROM rest_segments WHERE user_id = ?');",
         'handliste', null, 0, 'Tabelle steht HINTER der Liste — auch dann nicht'],
        ["<?php \$a = \$m['id']; \$b = \$m['notes'];",
         'handliste', null, 0, 'zwei Spaltennamen sind keine Liste'],
    ];

    $ok = 0;
    foreach ($faelle as [$code, $art, $arg, $soll, $was]) {
        $sichten = zh_sichten('probe.php', $code);
        $ist = 0;
        switch ($art) {
            case 'aufruf':  $ist = count(zh_aufrufe($code, $arg)); break;
            case 'methode': $ist = count(zh_methodenaufrufe($code, $arg)); break;
            case 'muster':  $ist = preg_match_all($arg, $sichten['php_mit_zeichenketten']); break;
            case 'muster_ohne': $ist = preg_match_all($arg, $sichten['php_ohne_zeichenketten']); break;
            case 'js':      $ist = preg_match_all($arg, $sichten['js_und_inline']); break;
            case 'date1':   $ist = count(zh_regel_date_ohne_zeitstempel('probe.php', $sichten, $code)); break;
            case 'handliste': $ist = count(zh_regel_missions_handliste('probe.php', $sichten, $code)); break;
            case 'zeile':
                preg_match($arg, $sichten['php_mit_zeichenketten'], $t, PREG_OFFSET_CAPTURE);
                $ist = $t ? zh_zeile($sichten['php_mit_zeichenketten'], $t[0][1]) : 0;
                break;
        }
        $passt = $ist === $soll;
        printf("  %s  %-52s erwartet %d, gemessen %d\n", $passt ? 'ok  ' : 'FEHL', $was, $soll, $ist);
        if ($passt) { $ok++; }
    }

    /* Zeilentreue ueber alle drei Sichten am echten Bestand: Jede Sicht muss
     * so viele Zeilen haben wie die Quelle — sonst zeigt jeder Befund daneben. */
    $schief = 0; $geprueft = 0;
    foreach (zh_bestand() as $rel => $d) {
        $n = substr_count($d['quelle'], "\n");
        foreach ($d['sichten'] as $s) { $geprueft++; if (substr_count($s, "\n") !== $n) { $schief++; } }
    }
    printf("  %s  %-52s erwartet %d, gemessen %d\n",
           $schief === 0 ? 'ok  ' : 'FEHL', 'Zeilentreue ueber ' . $geprueft . ' Sichten', 0, $schief);
    if ($schief === 0) { $ok++; }

    $n = count($faelle) + 1;
    printf("\nSelbstprobe: %d von %d\n", $ok, $n);
    return $ok === $n ? 0 : 1;
}

/* ===========================================================================
 * 6. Der Lauf
 * ======================================================================== */

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "Nur auf der Kommandozeile.\n"); exit(2); }
if (realpath($argv[0] ?? '') !== realpath(__FILE__)) { return; }   // eingebunden, nicht gerufen

if (in_array('--selbstprobe', $argv, true)) { exit(zh_selbstprobe()); }

$nurZeile = null;
foreach ($argv as $a) { if (str_starts_with($a, '--zeile=')) { $nurZeile = substr($a, 8); } }
$mitStellen = in_array('--stellen', $argv, true);

$register = require __DIR__ . '/register.php';
$bestand  = zh_bestand();

$phpZahl = count(array_filter(array_keys($bestand), static fn($r) => str_ends_with($r, '.php')));
$jsZahl  = count($bestand) - $phpZahl;

echo "Zaehlung — haelt jede Sache ihre eine Stelle?\n";
echo str_repeat('=', 78) . "\n";
printf("Bestand: %d PHP-Dateien, %d JS-Dateien (server/, ohne vendor/)\n\n", $phpZahl, $jsZahl);
printf("%-5s %-8s %5s %5s %5s  %-6s %s\n", 'Zeile', 'Paket', 'Start', 'Ist', 'Decke', 'Lage', 'Beschreibung');
echo str_repeat('-', 78) . "\n";

$ueber = []; $gesehen = 0;
foreach ($register as $z) {
    if ($nurZeile !== null && $z['kennung'] !== $nurZeile) { continue; }
    $gesehen++;
    $m = zh_messen($z, $bestand);
    $decke = $z['decke_jetzt'];
    $lage  = $m['treffer'] > $decke ? 'DRUEBER' : ($m['treffer'] < $decke ? 'drunter' : 'genau');
    if ($m['treffer'] > $decke) { $ueber[] = $z['kennung']; }
    printf("%-5s %-8s %5d %5d %5d  %-7s %s\n",
           $z['kennung'], $z['paket'], $z['start'], $m['treffer'], $decke, $lage, $z['beschreibung']);
    if ($mitStellen) {
        foreach ($m['stellen'] as $s) { echo "        " . $s . "\n"; }
    }
}

echo str_repeat('-', 78) . "\n";
printf("%d Zeilen gemessen, %d ueber der Decke.\n", $gesehen, count($ueber));
if ($ueber !== []) {
    echo "Ueber der Decke: " . implode(', ', $ueber) . "\n";
    echo "Eine Decke wird nicht angehoben, ohne dass es im Konzept steht (E-ZE-24).\n";
}
exit($ueber === [] ? 0 : 1);
