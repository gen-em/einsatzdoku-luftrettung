<?php
declare(strict_types=1);
/**
 * NACHWEISDATEIEN — wer eine Datei im Anwendungsverzeichnis nennen oder
 * anlegen kann, hat Zugriff auf den Webspace (M1-11; Schritt 18, SR-04).
 *
 * EINE STELLE FUER DREI SEITEN (R83). Bis Web 21.12.2 stand die Mechanik
 * wortgleich in `install.php` und `wiederherstellen.php` — glob, Kennung aus
 * dem Namen, Reste weg, 128 Bit Zufall, Datei schreiben, Eingabe
 * vergleichen. Der Notzugang (`zweitfaktor_notweg.php`) waere die dritte
 * Kopie geworden; die Register-Zeile Z44 haelt fest, dass es keine wird.
 *
 * ZWEI RICHTUNGEN, DIESELBE DATEI
 *
 *   LESENACHWEIS (`install.php`, `wiederherstellen.php`): Die Seite LEGT die
 *   Datei an, die Person liest den Namen im Dateimanager ab und traegt die
 *   Kennung ein. Belegt: Sie SIEHT das Verzeichnis. Das genuegt dort, weil
 *   es noch keine Anlage gibt, die etwas zu verlieren haette — und es ist
 *   die einzige Richtung, die ohne FTP-Programm geht.
 *
 *   SCHREIBNACHWEIS (`zweitfaktor_notweg.php`, E-SR-81): Die Seite NENNT den
 *   Namen, die Person legt die Datei per FTP an, die Seite prueft nur, ob
 *   sie liegt. Belegt: Sie kann dort SCHREIBEN — dasselbe Vertrauen wie bei
 *   dem, der SQL im Datenbankwerkzeug absetzt. Hier schreibt die Anwendung
 *   auf einen unangemeldeten Aufruf NIE eine Datei; sie liest und loescht.
 *
 * DIE KENNUNG STEHT IM DATEINAMEN. Bei Einfachhosting liegt dieses
 * Verzeichnis im Web-Wurzelverzeichnis; eine Datei mit festem Namen waere
 * ueber die Adresszeile abrufbar. Einen Namen aus 128 Bit Zufall nennt nur,
 * wer das Verzeichnis sieht. Die `.htaccess` sperrt alle drei Muster
 * zusaetzlich — als zweite Schranke, nicht als erste.
 *
 * SCHLICHTE SYNTAX. `install.php` bindet diese Datei erst hinter seiner
 * Versionsweiche ein und braucht deshalb kein PHP-7-lesbares Gegenueber —
 * aber eine Bibliothek, die der Einrichter laedt, soll nicht die erste sein,
 * die daran etwas aendert. Kein `match`, kein `?->`, keine Attribute.
 */

/** Die Kennung: 32 Hexzeichen, 128 Bit. */
const NACHWEIS_KENNUNG_RE = '[0-9a-f]{32}';

/** Ist das ein Praefix, wie die drei Seiten es benutzen (`install-nachweis-`)? */
function nachweis_muster_gueltig(string $muster): bool
{
    return preg_match('/^[a-z][a-z-]*-$/', $muster) === 1;
}

/** Ist das eine Kennung, wie diese Bibliothek sie wuerfelt? */
function nachweis_kennung_gueltig(string $kennung): bool
{
    return preg_match('/^' . NACHWEIS_KENNUNG_RE . '$/', $kennung) === 1;
}

/**
 * Der Pfad der Datei. Wirft bei einem Muster oder einer Kennung, die nicht
 * von hier stammen kann — ein Pfad aus Eingaben ist ein Pfad nach anderswo.
 */
function nachweis_datei(string $muster, string $kennung): string
{
    if (!nachweis_muster_gueltig($muster) || !nachweis_kennung_gueltig($kennung)) {
        throw new InvalidArgumentException('Nachweisdatei: ungültiges Muster oder ungültige Kennung');
    }
    return __DIR__ . '/' . $muster . $kennung . '.txt';
}

/** Eine neue Kennung — 128 Bit Zufall. */
function nachweis_kennung_neu(): string
{
    return bin2hex(random_bytes(16));
}

/**
 * Die Dateien eines Musters, die nach dieser Bibliothek aussehen, als
 * Kennung => Pfad, sortiert. Fremde Namen mit demselben Anfang bleiben
 * aussen vor.
 */
function nachweis_vorhandene(string $muster): array
{
    if (!nachweis_muster_gueltig($muster)) { return []; }
    $treffer = glob(__DIR__ . '/' . $muster . '*.txt') ?: [];
    sort($treffer);
    $aus = [];
    foreach ($treffer as $datei) {
        if (preg_match('/' . preg_quote($muster, '/') . '(' . NACHWEIS_KENNUNG_RE . ')\.txt$/',
                       $datei, $tr)) {
            $aus[$tr[1]] = $datei;
        }
    }
    return $aus;
}

/**
 * LESENACHWEIS: die geltende Kennung — eine vorhandene Datei, sonst neu.
 *
 * DIE KENNUNG HAENGT AN DER DATEI, NICHT AN DER SITZUNG (install.php, erste
 * Fassung): Sonst laege nach jedem Aufruf — auch dem eines Neugierigen, auch
 * einem Vorschau-Abruf des Browsers — eine weitere Datei da, und niemand
 * wuesste, welche die seine ist. Die erste gilt; weitere sind Reste aus
 * alten Staenden und gehen.
 *
 * Fuer den Schreibnachweis ist das die falsche Funktion: Dort haengt der
 * Name an der Sitzung (E-SR-82), und die Seite schreibt nichts.
 */
function nachweis_finden_oder_wuerfeln(string $muster): string
{
    $kennung = '';
    foreach (nachweis_vorhandene($muster) as $k => $datei) {
        if ($kennung === '') { $kennung = (string)$k; }
        else { @unlink($datei); }
    }
    return $kennung !== '' ? $kennung : nachweis_kennung_neu();
}

/**
 * LESENACHWEIS: die Datei anlegen, wenn sie fehlt. Die erste Zeile ist die
 * Kennung, danach `$text` (wofuer die Datei da ist). Rechte 0640.
 *
 * `false` ohne Schreibrecht auf das Verzeichnis — auch wenn die Datei schon
 * liegt: Beide Seiten brauchen das Schreibrecht danach ohnehin (config.php,
 * der Arbeitsstand), und die Meldung gehoert an den Anfang.
 */
function nachweis_anlegen(string $muster, string $kennung, string $text): bool
{
    if (!is_writable(__DIR__)) { return false; }
    $datei = nachweis_datei($muster, $kennung);
    if (file_exists($datei)) { return true; }
    if (@file_put_contents($datei, $kennung . "\n\n" . $text, LOCK_EX) === false) { return false; }
    @chmod($datei, 0640);
    return true;
}

/** Liegt die Datei? (Beide Richtungen.) */
function nachweis_steht(string $muster, string $kennung): bool
{
    if (!nachweis_muster_gueltig($muster) || !nachweis_kennung_gueltig($kennung)) { return false; }
    $datei = nachweis_datei($muster, $kennung);
    clearstatcache(true, $datei);
    return is_file($datei);
}

/**
 * LESENACHWEIS: passt, was eingetragen wurde?
 *
 * GROSSZUEGIG BEIM FORMAT: Wer statt der Zeichenfolge den ganzen Dateinamen
 * hineinkopiert, hat verstanden, was gemeint war. Verglichen wird ueber
 * `hash_equals()` — die Kennung ist ein Geheimnis.
 */
function nachweis_eingabe_passt(string $muster, string $kennung, string $eingabe): bool
{
    $e = strtolower(trim($eingabe));
    $e = (string)preg_replace('/^' . preg_quote($muster, '/') . '/', '', $e);
    $e = (string)preg_replace('/\.txt$/', '', $e);
    return $kennung !== '' && hash_equals($kennung, $e);
}

/**
 * Die Datei entfernen — eine bestimmte oder, ohne Kennung, alle des Musters.
 * Gibt zurueck, wie viele gingen. Eine Datei, die nicht zu loeschen ist
 * (andere Eigentuemerin beim Hoster), bleibt liegen und wird nicht
 * mitgezaehlt; der Aufrufer entscheidet, ob er es sagt.
 */
function nachweis_entfernen(string $muster, ?string $kennung = null): int
{
    $dateien = $kennung === null
        ? array_values(nachweis_vorhandene($muster))
        : (nachweis_steht($muster, $kennung) ? [nachweis_datei($muster, $kennung)] : []);
    $n = 0;
    foreach ($dateien as $datei) {
        if (@unlink($datei)) { $n++; }
    }
    return $n;
}
