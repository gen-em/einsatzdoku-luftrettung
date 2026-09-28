<?php
declare(strict_types=1);
/**
 * Kennzeichnungsprobe — trägt jedes verschlüsselte Feld sein Schloss und
 * jedes Klartext-Freitextfeld seine Kleinzeile, in Formular UND Leseansicht?
 *
 *   php tools/quelltext/kennzeichnung.php
 *   php tools/quelltext/kennzeichnung.php --selbstprobe
 *
 * Rückgabe 0 = jedes Kennzeichen steht · 1 = eines fehlt · 2 = nicht
 * gelaufen (Datei oder Sollliste fehlt).
 *
 * Anlass: Nr. 170 — Web 19.1.0 zählte 8 Schlösser und 9 Kleinzeilen, und
 * die Zahlen stimmten; das Schloss fehlte trotzdem an der Einsatznummer der
 * Leseansicht und an der Karte „Notizen" des Formulars (behoben mit 19.1.1).
 * Eine Zählung ohne Sollmaß bestätigt ihre eigene Liste.
 *
 * WAS SIE MISST. Das Sollmaß steht in `kennzeichnung-soll.md`: je
 * verschlüsseltem Feld (`CLAUDE.md` 4, `docs/Technik.md` 4.98) die Stelle im
 * Formular (`einsatz_form.php`) und in der Leseansicht (`einsatz.php`), an
 * der das Schloss stehen muss, in fünf Formen:
 *
 *   label:<Text>     <label>Text<?= $SCHLOSS ?> — von Hand geschriebene Felder
 *   ortsfeld:<p>     ui_ortsfeld([… 'praefix' => 'p' … 'geschuetzt' => true])
 *   karte:<Titel>    ui_karte_start([… 'titel' => 'Titel' … 'geschuetzt' => true])
 *   dt:<Text>        zeile(…, dtGeschuetzt('Text'), …) in der Leseansicht
 *   katalog:<spalte> das Feld hat im Katalog 'store' => 'pat', und die
 *                    Leseansicht zeichnet PAT_KAT mit dtGeschuetzt()
 *
 * Dazu der Katalog selbst (`mission_fields.php`, über seine Bibliothek
 * gelesen): Jedes Feld mit 'store' => 'pat' steht in der Sollliste, und
 * jedes Freitextfeld (`text`, `textarea`) ohne es trägt als 'hinweis' genau
 * die Kleinzeile „Klartext — keine Patientendaten". Die beiden Zeichen
 * schließen einander aus (`CLAUDE.md` 4) — ein Blobfeld mit Kleinzeile ist
 * ebenso ein Befund.
 *
 * KOMMENTARE ZÄHLEN NICHT. Gelesen wird der Quelltext ohne PHP-Kommentare
 * (über token_get_all) und ohne JavaScript-Kommentare im eingebetteten
 * Skript: Ein Kommentar, der `dtGeschuetzt('Diagnose')` erwähnt, ist kein
 * Schloss.
 *
 * WAS SIE NICHT MISST. Ob das Schloss im Browser SICHTBAR ist (dafür der
 * Bilderlauf), und keine Seite außer diesen zweien. Ein neues verschlüsseltes
 * Feld, das niemand in den Katalog und nicht in die Sollliste schreibt, sieht
 * sie nicht — der Weg ins Blob führt über den Katalog (`CLAUDE.md` 4), und
 * den liest sie.
 */

const KZ_WURZEL = __DIR__ . '/../..';
/* DER KATALOG OHNE ANLAGE. `mission_fields.php` braucht `CREW_ROLES`, und das
 * steht in `db.php` — das ohne `config.php` abbricht, und Stufe 1 hat keine
 * (Regel `anlage` in `bestand`). Die Konstante ist ein reines Literal; sie wird
 * deshalb aus den Tokens von `db.php` gelesen, nicht durch Laden. */
require_once __DIR__ . '/../../server/mission_fields_lib.php';
const KZ_FORM = 'server/einsatz_form.php';
const KZ_LESE = 'server/einsatz.php';
const KZ_UI = 'server/ui.php';
const KZ_SOLL = __DIR__ . '/kennzeichnung-soll.md';

/** Quelltext ohne PHP-Kommentare und ohne JS-Kommentare im HTML-Teil. */
function kz_ohne_kommentare(string $quelle): string
{
    $aus = '';
    foreach (token_get_all($quelle) as $t) {
        if (is_array($t)) {
            if ($t[0] === T_COMMENT || $t[0] === T_DOC_COMMENT) {
                $aus .= str_repeat("\n", substr_count($t[1], "\n"));
                continue;
            }
            if ($t[0] === T_INLINE_HTML) {
                $h = preg_replace_callback('~/\*.*?\*/~s', fn($m) => str_repeat("\n", substr_count($m[0], "\n")), $t[1]);
                $h = preg_replace('~(?<![:\'"\\\\])//[^\n]*~', '', (string)$h);
                $aus .= (string)$h;
                continue;
            }
            $aus .= $t[1];
        } else {
            $aus .= $t;
        }
    }
    return $aus;
}

/** Die Argumentliste eines Aufrufs `name([ … ])` ab der öffnenden Klammer. */
function kz_arrays(string $text, string $funktion): array
{
    $aus = [];
    if (!preg_match_all('~\b' . preg_quote($funktion, '~') . '\s*\(\s*\[~', $text, $m, PREG_OFFSET_CAPTURE)) {
        return $aus;
    }
    foreach ($m[0] as [$treffer, $pos]) {
        $i = $pos + strlen($treffer) - 1;
        $tiefe = 0;
        for ($j = $i; $j < strlen($text); $j++) {
            if ($text[$j] === '[') { $tiefe++; }
            elseif ($text[$j] === ']') { $tiefe--; if ($tiefe === 0) { $aus[] = substr($text, $i, $j - $i + 1); break; } }
        }
    }
    return $aus;
}

/** Die Sollliste: [[Feld, Formular, Leseansicht], …] aus der Markdown-Tabelle. */
function kz_soll_lesen(string $md): array
{
    $aus = [];
    foreach (preg_split('~\R~', $md) as $z) {
        if (!preg_match('~^\|(.+)\|\s*$~', trim($z), $m)) { continue; }
        $zellen = array_map(fn($c) => trim(trim($c), '`'), explode('|', $m[1]));
        if (count($zellen) < 3 || $zellen[0] === 'Feld' || preg_match('~^-+$~', $zellen[0])) { continue; }
        $aus[] = array_slice($zellen, 0, 3);
    }
    return $aus;
}

/**
 * Der Kern: Befunde als Liste von Zeichenketten. Rein — alles kommt herein,
 * damit die Selbstprobe dieselbe Funktion mit eingebauten Fehlern fahren kann.
 *
 * @param array $katalog [spalte => ['label', 'type', 'store', 'hinweis']] flach
 */
function kz_pruefen(string $form, string $lese, string $ui, array $katalog, string $klartext, array $soll): array
{
    $befunde = [];
    $form = kz_ohne_kommentare($form);
    $lese = kz_ohne_kommentare($lese);
    $ui = kz_ohne_kommentare($ui);

    // Die Zeichen selbst: Jede Form stützt sich auf eine Stelle, die das
    // Schloss wirklich zeichnet. Fehlt sie, trüge jede Zeile ein leeres Zeichen.
    if (!preg_match('~\$SCHLOSS\s*=\s*ui_symbol\(\s*\'schloss\'~', $form)) {
        $befunde[] = KZ_FORM . ': $SCHLOSS ist nicht ui_symbol(\'schloss\', …)';
    }
    if (!preg_match('~function\s+dtGeschuetzt\s*\([^)]*\)\s*\{[^}]*schloss~s', $lese)) {
        $befunde[] = KZ_LESE . ': dtGeschuetzt() zeichnet kein Schloss';
    }
    foreach (['ui_karte_start', 'ui_ortsfeld'] as $f) {
        if (!preg_match('~function\s+' . $f . '\s*\(.*?\n\}~s', $ui, $m)
                || !preg_match('~\'geschuetzt\'~', $m[0]) || !preg_match('~\'schloss\'~', $m[0])) {
            $befunde[] = KZ_UI . ": $f() setzt für 'geschuetzt' kein Schloss";
        }
    }

    $patSoll = [];
    foreach ($soll as [$feld, $f, $l]) {
        foreach (['Formular' => [$f, $form, KZ_FORM], 'Leseansicht' => [$l, $lese, KZ_LESE]] as $wo => [$kz, $text, $datei]) {
            [$art, $wert] = array_pad(explode(':', $kz, 2), 2, '');
            $ok = match ($art) {
                'label'   => (bool)preg_match('~<label\b[^>]*>\s*' . preg_quote($wert, '~') . '\s*<\?=\s*\$SCHLOSS\s*\?>~', $text),
                'ortsfeld'=> (bool)array_filter(kz_arrays($text, 'ui_ortsfeld'), fn($a) =>
                                 preg_match("~'praefix'\s*=>\s*'" . preg_quote($wert, '~') . "'~", $a)
                                 && preg_match("~'geschuetzt'\s*=>\s*true~", $a)),
                'karte'   => (bool)array_filter(kz_arrays($text, 'ui_karte_start'), fn($a) =>
                                 preg_match("~'titel'\s*=>\s*'" . preg_quote($wert, '~') . "'~", $a)
                                 && preg_match("~'geschuetzt'\s*=>\s*true~", $a)),
                'dt'      => (bool)preg_match('~\bzeile\([^;]*?\bdtGeschuetzt\(\s*\'' . preg_quote($wert, '~') . '\'\s*\)~s', $text),
                'katalog' => ($katalog[$wert]['store'] ?? null) === 'pat'
                             && (bool)preg_match('~\bdtGeschuetzt\(\s*PAT_KAT\[\w+\]\.label\s*\)~', $text)
                             && (bool)preg_match('~PAT_KAT\s*=\s*<\?=\s*json_js\(\s*mf_pat_felder\(\)\s*\)~', $text),
                default   => null,
            };
            if ($art === 'katalog') { $patSoll[] = $wert; }
            if ($ok === null) {
                $befunde[] = "Sollliste: „{$feld}\" ({$wo}) — unbekannte Form „{$kz}\"";
            } elseif (!$ok) {
                $befunde[] = "{$datei}: „{$feld}\" ({$wo}) — kein Schloss an „{$kz}\"";
            }
        }
    }

    foreach ($katalog as $spalte => $f) {
        $pat = ($f['store'] ?? null) === 'pat';
        if ($pat && !in_array($spalte, $patSoll, true)) {
            $befunde[] = "Katalog: {$spalte} liegt im Blob ('store' => 'pat'), steht aber nicht in der Sollliste";
        }
        if ($pat && isset($f['hinweis'])) {
            $befunde[] = "Katalog: {$spalte} liegt im Blob und trägt trotzdem die Kleinzeile — die Zeichen schließen einander aus";
        }
        if (!$pat && in_array($f['type'] ?? '', ['text', 'textarea'], true) && ($f['hinweis'] ?? null) !== $klartext) {
            $befunde[] = "Katalog: {$spalte} ({$f['label']}) ist ein Klartext-Freitextfeld ohne die Kleinzeile „{$klartext}\"";
        }
    }
    return $befunde;
}

/** Ein Konstantenliteral aus einer Quelldatei — nur Zeichenketten, Zahlen,
 *  Klammern, `=>` und Kommas; alles andere ist null, und nichts wird geladen. */
function kz_konstante(string $quelle, string $name)
{
    $t = token_get_all($quelle);
    for ($i = 0, $n = count($t); $i < $n; $i++) {
        if (!is_array($t[$i]) || $t[$i][0] !== T_CONST) { continue; }
        $j = $i + 1;
        while (is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) { $j++; }
        if (!is_array($t[$j]) || $t[$j][1] !== $name) { continue; }
        while ($t[$j] !== '=') { $j++; }
        $code = '';
        for ($k = $j + 1; $k < $n && $t[$k] !== ';'; $k++) {
            $x = $t[$k];
            if (is_array($x)) {
                if ($x[0] === T_COMMENT || $x[0] === T_WHITESPACE) { continue; }
                if (!in_array($x[0], [T_CONSTANT_ENCAPSED_STRING, T_LNUMBER, T_DOUBLE_ARROW], true)) { return null; }
                $code .= $x[1];
            } elseif (in_array($x, ['[', ']', ','], true)) {
                $code .= $x;
            } else {
                return null;
            }
        }
        return eval('return ' . $code . ';');
    }
    return null;
}

/** [Katalog flach mit Kindern als [spalte => Feld], Kleinzeile]. */
function kz_katalog(): array
{
    if (!defined('CREW_ROLES')) {
        $rollen = kz_konstante(kz_lies('server/db.php'), 'CREW_ROLES');
        if (!is_array($rollen)) { fwrite(STDERR, "CREW_ROLES in server/db.php nicht lesbar\n"); exit(2); }
        define('CREW_ROLES', $rollen);
    }
    $roh = require __DIR__ . '/../../server/mission_fields.php';
    $klartext = $mf_hinweis_klartext ?? null;
    $aus = [];
    $flach = function (array $felder) use (&$flach, &$aus): void {
        foreach ($felder as $spalte => $f) {
            if (!is_array($f)) { continue; }
            $aus[(string)$spalte] = $f;
            if (!empty($f['children']) && is_array($f['children'])) { $flach($f['children']); }
        }
    };
    $flach(is_array($roh) ? $roh : []);
    return [$aus, $klartext];
}

function kz_lies(string $rel): string
{
    $p = KZ_WURZEL . '/' . $rel;
    if (!is_file($p)) { fwrite(STDERR, "$rel fehlt\n"); exit(2); }
    return (string)file_get_contents($p);
}

function kz_selbstprobe(): int
{
    $form = kz_lies(KZ_FORM); $lese = kz_lies(KZ_LESE); $ui = kz_lies(KZ_UI);
    [$katalog, $klartext] = kz_katalog();
    $soll = kz_soll_lesen((string)file_get_contents(KZ_SOLL));
    $faelle = [
        ['GEGENPROBE: der heutige Stand', 0, fn() => [$form, $lese, $katalog]],
        ['Leseansicht: Einsatznummer ohne dtGeschuetzt() (der Fehler aus 19.1.0)', 1,
         fn() => [$form, str_replace("dtGeschuetzt('Einsatznummer')", "'Einsatznummer'", $lese), $katalog]],
        ['… und ein Kommentar nennt den alten Aufruf — zählt nicht', 1,
         fn() => [$form, str_replace("dtGeschuetzt('Einsatznummer')", "'Einsatznummer' /* dtGeschuetzt('Einsatznummer') */", $lese), $katalog]],
        ['Formular: Diagnose ohne $SCHLOSS', 1,
         fn() => [preg_replace('~Diagnose<\?= \$SCHLOSS \?>~', 'Diagnose', $form, 1), $lese, $katalog]],
        ['Formular: Karte „Notizen" ohne geschuetzt (der zweite Fehler aus 19.1.0)', 1,
         fn() => [preg_replace("~('titel' => 'Notizen'.*?)'geschuetzt' => true,~s", '$1', $form, 1), $lese, $katalog]],
        ['Formular: Einsatzort ohne geschuetzt', 1,
         fn() => [preg_replace("~('praefix'\s*=>\s*'loc',.*?)'geschuetzt'\s*=>\s*true,~s", '$1', $form, 1), $lese, $katalog]],
        ['Katalog: ein Freitextfeld verliert seine Kleinzeile', 1,
         fn() => [$form, $lese, array_map(fn($f) => ($f['label'] ?? '') === 'Weitere NotärztIn'
                   ? array_diff_key($f, ['hinweis' => 1]) : $f, $katalog)]],
        ['Katalog: ein neues Blobfeld, das die Sollliste nicht kennt', 1,
         fn() => [$form, $lese, $katalog + ['neu_geheim' => ['label' => 'Neu', 'type' => 'text', 'store' => 'pat']]]],
    ];
    $fehl = 0;
    foreach ($faelle as [$name, $soll_n, $bau]) {
        [$f, $l, $k] = $bau();
        $n = count(kz_pruefen($f, $l, $ui, $k, $klartext, $soll));
        $ok = $soll_n === 0 ? $n === 0 : $n >= $soll_n;
        $fehl += $ok ? 0 : 1;
        echo '  [' . ($ok ? 'ok  ' : 'FEHL') . "] $name ($n Befunde)\n";
    }
    echo count($faelle) . " Fälle, $fehl Fehlschläge\n";
    return $fehl ? 1 : 0;
}

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    if (in_array('--selbstprobe', $argv, true)) { exit(kz_selbstprobe()); }
    if (!is_file(KZ_SOLL)) { fwrite(STDERR, "kennzeichnung-soll.md fehlt\n"); exit(2); }
    $soll = kz_soll_lesen((string)file_get_contents(KZ_SOLL));
    [$katalog, $klartext] = kz_katalog();
    if (!is_string($klartext)) { fwrite(STDERR, "\$mf_hinweis_klartext nicht gefunden\n"); exit(2); }
    $befunde = kz_pruefen(kz_lies(KZ_FORM), kz_lies(KZ_LESE), kz_lies(KZ_UI), $katalog, $klartext, $soll);
    $frei = count(array_filter($katalog, fn($f) => ($f['store'] ?? null) !== 'pat'
                                && in_array($f['type'] ?? '', ['text', 'textarea'], true)));
    $n = 2 * count($soll);
    echo 'Kennzeichen: ' . ($n - count(array_filter($befunde, fn($b) => str_contains($b, 'kein Schloss')))) . " von $n (" . count($soll) . " Felder, Formular und Leseansicht)\n";
    echo "Klartext-Freitextfelder im Katalog: $frei, jedes mit Kleinzeile geprüft\n";
    foreach ($befunde as $b) { echo "  ! $b\n"; }
    echo count($befunde) . " Befunde\n";
    exit($befunde ? 1 : 0);
}
