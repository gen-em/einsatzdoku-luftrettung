<?php
declare(strict_types=1);

/**
 * Angriffsprobe für den Rechtstext-Renderer (R32, P3/O10).
 *
 * WARUM ES DIESES WERKZEUG GIBT. `rt_html()` (server/rechtstexte_lib.php) ist
 * die einzige Stelle des Projekts, an der aus einer Eingabe HTML wird. Alles
 * andere geht durch e() und erscheint als Text. Diese eine Stelle trägt damit
 * die ganze Last — und sie ist eine reine Funktion, also lässt sie sich
 * einzeln durchspielen, ohne Browser, ohne Datenbank, ohne Sitzung.
 *
 * Aufruf:
 *   php tools/rechtstexte/pruefen.php
 *   php tools/rechtstexte/pruefen.php --ausfuehrlich    (zeigt jede Ausgabe)
 *
 * Rückgabewert ≠ 0, sobald eine Probe fehlschlägt.
 */

$ausfuehrlich = in_array('--ausfuehrlich', $argv, true);

/* rechtstexte_lib.php braucht ui_e() aus ui.php — sonst nichts. Weder
   Datenbank noch Sitzung: Die geprüften Funktionen sind rein. */
require_once __DIR__ . '/../../server/ui.php';
require_once __DIR__ . '/../../server/rechtstexte_lib.php';

$proben = [];
$fehler = 0;
$lauf   = 0;

/**
 * @param string $gruppe   Überschrift der Gruppe
 * @param string $was      Was geprüft wird
 * @param string $eingabe  Markdown-Quelle
 * @param array  $muss     Zeichenketten, die IM Ergebnis stehen müssen
 * @param array  $darfnicht Zeichenketten, die NICHT vorkommen dürfen
 */
function probe(string $gruppe, string $was, string $eingabe,
               array $muss = [], array $darfnicht = []): void
{
    global $proben, $fehler, $lauf, $ausfuehrlich;
    $lauf++;
    $ist = rt_html($eingabe);
    $mangel = [];
    foreach ($muss as $m) {
        if (!str_contains($ist, $m)) { $mangel[] = 'fehlt: ' . $m; }
    }
    foreach ($darfnicht as $d) {
        if (str_contains($ist, $d)) { $mangel[] = 'DURCHGEKOMMEN: ' . $d; }
    }
    if ($mangel) { $fehler++; }
    $proben[$gruppe][] = ['was' => $was, 'ok' => !$mangel, 'mangel' => $mangel,
                          'ein' => $eingabe, 'aus' => $ist];
    if ($ausfuehrlich) {
        printf("  %s %s\n      ein: %s\n      aus: %s\n",
               $mangel ? '✗' : '·', $was,
               str_replace("\n", '⏎', mb_substr($eingabe, 0, 90)),
               str_replace("\n", '⏎', mb_substr($ist, 0, 130)));
    }
}

/* ==========================================================================
 * A — Was er können MUSS (E-P3-38: Überschriften, Absätze, Listen, Links)
 * ======================================================================== */

probe('A Umfang', 'Überschrift ## wird h2',
    "## Angaben gemäß § 5 DDG",
    ['<h2>Angaben gemäß § 5 DDG</h2>']);

probe('A Umfang', 'Überschrift # wird ebenfalls h2 (das h1 hat die Seite)',
    "# Impressum",
    ['<h2>Impressum</h2>'], ['<h1>']);

probe('A Umfang', 'Überschrift ### wird h3',
    "### Unterpunkt",
    ['<h3>Unterpunkt</h3>']);

probe('A Umfang', '#### ist KEINE Überschrift und bleibt Text',
    "#### Zu tief",
    ['#### Zu tief'], ['<h4>']);

probe('A Umfang', 'Absatz',
    "Verantwortlich für die Datenverarbeitung ist die Gen-EM GbR.",
    ['<p>Verantwortlich für die Datenverarbeitung ist die Gen-EM GbR.</p>']);

probe('A Umfang', 'Anschrift: Zeilen ohne Leerzeile bleiben EIN Absatz',
    "Gen-EM GbR\nMusterstraße 12\n87435 Kempten",
    ['<p>Gen-EM GbR<br>Musterstraße 12<br>87435 Kempten</p>']);

probe('A Umfang', 'Leerzeile trennt zwei Absätze',
    "Erster Absatz.\n\nZweiter Absatz.",
    ['<p>Erster Absatz.</p>', '<p>Zweiter Absatz.</p>']);

probe('A Umfang', 'Aufzählung mit -',
    "- Konto: E-Mail-Adresse\n- Einsätze: Zeiten",
    ['<ul>', '<li>Konto: E-Mail-Adresse</li>', '<li>Einsätze: Zeiten</li>', '</ul>']);

probe('A Umfang', 'Aufzählung mit *',
    "* Erstens\n* Zweitens",
    ['<ul>', '<li>Erstens</li>', '</ul>']);

probe('A Umfang', 'Nummerierung',
    "1. Verantwortlicher\n2. Zweck",
    ['<ol>', '<li>Verantwortlicher</li>', '<li>Zweck</li>', '</ol>']);

probe('A Umfang', 'Liste endet an der Leerzeile',
    "- Eins\n\nDanach.",
    ['</ul>', '<p>Danach.</p>']);

probe('A Umfang', 'Link ins Netz',
    "Quelltext: [github.com/gen-em](https://github.com/gen-em)",
    ['<a href="https://github.com/gen-em">github.com/gen-em</a>']);

probe('A Umfang', 'mailto — fürs Impressum unverzichtbar',
    "E-Mail: [kontakt@beispiel.de](mailto:kontakt@beispiel.de)",
    ['<a href="mailto:kontakt@beispiel.de">kontakt@beispiel.de</a>']);

probe('A Umfang', 'Verweis auf eine eigene Seite',
    "Siehe [Datenschutz](datenschutz.php).",
    ['<a href="datenschutz.php">Datenschutz</a>']);

probe('A Umfang', 'Link in einer Überschrift und in einer Liste',
    "## [Kontakt](mailto:a@b.de)\n- [Seite](impressum.php)",
    ['<h2><a href="mailto:a@b.de">Kontakt</a></h2>',
     '<li><a href="impressum.php">Seite</a></li>']);

/* ==========================================================================
 * B — Rohes HTML (der Abnahmefall aus dem Konzept)
 * ======================================================================== */

probe('B Rohes HTML', '<script> wird sichtbarer Text — der Abnahmefall',
    '<script>alert(1)</script>',
    ['&lt;script&gt;'], ['<script>']);

probe('B Rohes HTML', '<img onerror=…>',
    '<img src=x onerror=alert(1)>',
    ['&lt;img src=x onerror=alert(1)&gt;'], ['<img']);

probe('B Rohes HTML', '<svg onload=…>',
    '<svg onload=alert(1)>',
    [], ['<svg']);

probe('B Rohes HTML', '<iframe>',
    '<iframe src="https://fremd.example"></iframe>',
    [], ['<iframe']);

probe('B Rohes HTML', '<base href> — kippt alle relativen Verweise der Seite',
    '<base href="https://fremd.example/">',
    [], ['<base']);

probe('B Rohes HTML', '<meta http-equiv=refresh>',
    '<meta http-equiv="refresh" content="0;url=https://fremd.example">',
    [], ['<meta']);

probe('B Rohes HTML', '<style>',
    '<style>body{display:none}</style>',
    [], ['<style>']);

probe('B Rohes HTML', 'style-Attribut in einem Pseudo-Tag',
    '<p style="position:fixed;inset:0">weg</p>',
    [], ['<p style']);

probe('B Rohes HTML', 'HTML-Kommentar',
    '<!-- versteckt --><!--[if IE]>x<![endif]-->',
    [], ['<!--']);

probe('B Rohes HTML', '</textarea> — Ausbruch aus dem Editorfeld',
    '</textarea><script>alert(1)</script>',
    ['&lt;/textarea&gt;'], ['</textarea>']);

probe('B Rohes HTML', '<form action=…>',
    '<form action="https://fremd.example"><input name="pw"></form>',
    [], ['<form', '<input']);

probe('B Rohes HTML', '<object> und <embed>',
    '<object data="x"></object><embed src="y">',
    [], ['<object', '<embed']);

/* ==========================================================================
 * C — Linkziele
 * ======================================================================== */

probe('C Linkziele', 'javascript: wird nicht zum Link',
    '[Klick](javascript:alert(1))',
    ['[Klick]'], ['<a href="javascript']);

probe('C Linkziele', 'JaVaScRiPt: — Groß-/Kleinschreibung hilft nicht',
    '[Klick](JaVaScRiPt:alert(1))',
    [], ['<a href="JaVaScRiPt']);

probe('C Linkziele', 'Leerzeichen davor',
    '[Klick]( javascript:alert(1))',
    [], ['<a href=" javascript']);

probe('C Linkziele', 'java\\tscript: mit eingeschobenem Tabulator',
    "[Klick](java\tscript:alert(1))",
    [], ['<a href="java']);

probe('C Linkziele', '&#106;avascript: — Zahlenentität',
    '[Klick](&#106;avascript:alert(1))',
    [], ['<a href="&#106;']);

probe('C Linkziele', 'data:text/html',
    '[Klick](data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==)',
    [], ['<a href="data:']);

probe('C Linkziele', 'data:image/svg+xml',
    '[Bild](data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=)',
    [], ['<a href="data:']);

probe('C Linkziele', 'vbscript:',
    '[Klick](vbscript:msgbox(1))',
    [], ['<a href="vbscript']);

probe('C Linkziele', 'file:',
    '[Klick](file:///etc/passwd)',
    [], ['<a href="file:']);

probe('C Linkziele', 'blob: und about:',
    "[a](blob:https://x/y)\n[b](about:blank)",
    [], ['<a href="blob:', '<a href="about:']);

probe('C Linkziele', 'protokollrelativ // sieht relativ aus und ist es nicht',
    '[Klick](//fremde.example/pfad)',
    [], ['<a href="//']);

probe('C Linkziele', 'Pfad ohne .php ist kein erlaubtes Ziel',
    '[Klick](../../etc/passwd)',
    [], ['<a href="..']);

probe('C Linkziele', 'Anker ist erlaubt',
    '[Nach oben](#kopf)',
    ['<a href="#kopf">Nach oben</a>']);

/* ==========================================================================
 * D — Attribut-Injektion
 * ======================================================================== */

probe('D Attribute', 'Anführungszeichen im Ziel bricht nicht aus',
    '[x](https://a" onmouseover="alert(1))',
    [], ['onmouseover="alert']);

probe('D Attribute', 'HTML im Linktext',
    '[<img src=x onerror=alert(1)>](https://example.org)',
    [], ['<img']);

probe('D Attribute', 'Titel-Zusatz wird nicht unterstützt',
    '[x](https://example.org "Titel")',
    [], ['title=']);

probe('D Attribute', 'Kein target, also auch kein Fensterzugriff',
    '[x](https://example.org)',
    ['<a href="https://example.org">'], ['target=']);

probe('D Attribute', 'Überschriften bekommen keine automatische id',
    "## Kapitel",
    ['<h2>Kapitel</h2>'], ['id=']);

/* ==========================================================================
 * E — Autolinks, Bilder, Referenzen
 * ======================================================================== */

probe('E Nicht unterstützt', 'Autolink <https://…> bleibt Text',
    '<https://example.org>',
    ['&lt;https://example.org&gt;'], ['<a href']);

probe('E Nicht unterstützt', 'Autolink mit javascript:',
    '<javascript:alert(1)>',
    [], ['<a href']);

probe('E Nicht unterstützt', 'E-Mail in spitzen Klammern',
    '<admin@example.de>',
    ['&lt;admin@example.de&gt;'], ['<a href']);

probe('E Nicht unterstützt', 'Bild wird nicht zum img und nicht zum Link',
    '![alt](https://fremd.example/x.png)',
    ['!'], ['<img', '<a href="https://fremd.example/x.png"']);

probe('E Nicht unterstützt', 'Referenzlink bleibt Text',
    "[x][1]\n\n[1]: javascript:alert(1)",
    [], ['<a href="javascript']);

probe('E Nicht unterstützt', 'Fett und kursiv bleiben Zeichen',
    'Das ist **fett** und *kursiv*.',
    ['**fett**', '*kursiv*'], ['<strong>', '<em>']);

/* ==========================================================================
 * F — Zeichen und Kodierung
 * ======================================================================== */

probe('F Zeichen', 'CRLF aus dem Textfeld wird zu LF',
    "Zeile eins\r\nZeile zwei",
    ['<p>Zeile eins<br>Zeile zwei</p>'], ["\r"]);

probe('F Zeichen', 'U+2028 verschwindet',
    "Text\u{2028}mehr",
    [], ["\u{2028}"]);

probe('F Zeichen', 'Bidi-Steuerzeichen verschwinden (Trojan Source)',
    "[Harmlos\u{202E}txt.exe](https://example.org)",
    [], ["\u{202E}"]);

probe('F Zeichen', 'Zero-Width verschwindet',
    "ja\u{200B}vascript",
    [], ["\u{200B}"]);

probe('F Zeichen', 'NUL-Byte verschwindet',
    "Text\x00mehr",
    [], ["\x00"]);

probe('F Zeichen', 'Kaufmanns-Und wird genau EINMAL maskiert',
    'Meier & Söhne',
    ['Meier &amp; Söhne'], ['&amp;amp;']);

probe('F Zeichen', 'Umlaute und § bleiben unangetastet',
    '## Angaben gemäß § 5 DDG — Größe, Straße, Öl',
    ['gemäß § 5 DDG — Größe, Straße, Öl']);

probe('F Zeichen', 'Ein bereits maskierter Text wird nicht doppelt maskiert',
    'Fünf &lt; sechs',
    ['Fünf &amp;lt; sechs']);

/* ==========================================================================
 * G — Ränder
 * ======================================================================== */

probe('G Ränder', 'Leerer Text ergibt leere Ausgabe',
    '', [], ['<p>']);

probe('G Ränder', 'Nur Leerraum ergibt leere Ausgabe',
    "   \n\n  \t ", [], ['<p>']);

probe('G Ränder', 'Eine sehr lange Zeile mit vielen Klammern bricht nichts',
    str_repeat('[a](b) ', 500),
    []);

probe('G Ränder', 'Offene Klammer bleibt Text',
    '[unfertig](https://example.org',
    ['[unfertig]']);

probe('G Ränder', 'Verschachtelte Klammern werden nicht zum Link',
    '[[x]](javascript:alert(1))',
    [], ['<a href="javascript']);

probe('G Ränder', 'Zeilenumbruch im Linktext bricht das Muster',
    "[Text\nmehr](https://example.org)",
    [], ['<a href="https://example.org">Text']);

/* ==========================================================================
 * Z — DIE SCHARFE SCHRANKE
 *
 * Jede einzelne Probe oben nennt, was NICHT durchkommen darf — und jede
 * einzelne kann etwas übersehen, weil sie nach einer bestimmten Zeichenkette
 * sucht. Diese Prüfung dreht die Frage um: Sie geht durch die Ausgabe JEDER
 * Probe und verlangt, dass darin ausschliesslich die sieben erlaubten Tags
 * vorkommen. Was der Renderer je erzeugen kann, steht damit in EINER Liste,
 * und ein neues Tag müsste hier eingetragen werden, bevor es durchginge.
 *
 * Der zweite Teil prüft dasselbe für Attribute: Es gibt genau eines, `href`
 * am `<a>`. Ein `onerror`, `style`, `srcset` oder `formaction` in irgendeiner
 * Ausgabe wäre ein Treffer — gleich, welches Tag es trägt.
 * ======================================================================== */

const ERLAUBTE_TAGS = ['h2', 'h3', 'p', 'br', 'ul', 'ol', 'li', 'a'];

$zFehler = 0;
$zTags   = [];
$zAttr   = [];
foreach ($proben as $gruppe => $liste) {
    foreach ($liste as $p) {
        if (preg_match_all('/<\s*\/?\s*([A-Za-z][A-Za-z0-9]*)/', $p['aus'], $m)) {
            foreach ($m[1] as $tag) {
                if (!in_array(strtolower($tag), ERLAUBTE_TAGS, true)) {
                    $zTags[strtolower($tag)] = $p['was'];
                    $zFehler++;
                }
            }
        }
        /* Attribute: alles, was innerhalb eines Tags vor einem = steht. */
        if (preg_match_all('/<[a-z][^>]*?\s([a-zA-Z-]+)\s*=/', $p['aus'], $m2)) {
            foreach ($m2[1] as $attr) {
                if (strtolower($attr) !== 'href') {
                    $zAttr[strtolower($attr)] = $p['was'];
                    $zFehler++;
                }
            }
        }
    }
}

/* ==========================================================================
 * H — Die übrigen Funktionen der Bibliothek
 * ======================================================================== */

$hFehler = 0;
$hLauf   = 0;
function hprobe(string $was, bool $ok): void
{
    global $hFehler, $hLauf, $ausfuehrlich;
    $hLauf++;
    if (!$ok) { $hFehler++; }
    if ($ausfuehrlich) { printf("  %s %s\n", $ok ? '·' : '✗', $was); }
}

hprobe('rt_leer erkennt Leerraum',            rt_leer("  \n\t ") === true);
hprobe('rt_leer erkennt Inhalt',              rt_leer('x') === false);
hprobe('rt_leer erkennt null',                rt_leer(null) === true);
hprobe('rt_pruefen lässt normalen Text durch', rt_pruefen('Hallo', null) === null);
hprobe('rt_pruefen lehnt zu langen Text ab',
       rt_pruefen(str_repeat('a', RT_MAX_ZEICHEN + 1), null) !== null);
hprobe('rt_pruefen nimmt die Grenze selbst an',
       rt_pruefen(str_repeat('a', RT_MAX_ZEICHEN), null) === null);
hprobe('rt_pruefen lehnt kaputtes UTF-8 ab',  rt_pruefen("\xC3\x28", null) !== null);
hprobe('rt_pruefen lehnt ein falsches Datum ab', rt_pruefen('x', '30.08.2026') !== null);
hprobe('rt_pruefen nimmt ein ISO-Datum an',   rt_pruefen('x', '2026-08-30') === null);
hprobe('rt_pruefen nimmt ein leeres Datum an', rt_pruefen('x', '') === null);
hprobe('rt_stand_markup ohne Datum ist leer', rt_stand_markup(null) === '');
hprobe('rt_stand_markup formatiert deutsch',
       str_contains(rt_stand_markup('2026-08-30'), 'Stand: 30.08.2026'));
hprobe('rt_ziel_erlaubt: https ja',           rt_ziel_erlaubt('https://a.de') === true);
hprobe('rt_ziel_erlaubt: javascript nein',    rt_ziel_erlaubt('javascript:x') === false);
hprobe('rt_ziel_erlaubt: leer nein',          rt_ziel_erlaubt('') === false);
hprobe('RT_TEXTE und RT_SEITEN haben dieselben Schlüssel',
       array_keys(RT_TEXTE) === array_keys(RT_SEITEN));

/* ==========================================================================
 * Bericht
 * ======================================================================== */

echo "\nRechtstext-Renderer — Angriffsprobe\n";
echo str_repeat('=', 62), "\n\n";
foreach ($proben as $gruppe => $liste) {
    $schlecht = array_filter($liste, static fn(array $p): bool => !$p['ok']);
    printf("%-26s %2d Proben%s\n", $gruppe, count($liste),
           $schlecht ? '   ' . count($schlecht) . ' FEHLGESCHLAGEN' : '');
    foreach ($schlecht as $p) {
        echo "    ✗ ", $p['was'], "\n";
        foreach ($p['mangel'] as $m) { echo "        ", $m, "\n"; }
        echo "        ein: ", str_replace("\n", '⏎', $p['ein']), "\n";
        echo "        aus: ", str_replace("\n", '⏎', $p['aus']), "\n";
    }
}
printf("%-26s %2d Proben%s\n", 'H Bibliothek', $hLauf,
       $hFehler ? '   ' . $hFehler . ' FEHLGESCHLAGEN' : '');
printf("%-26s %2d Ausgaben durchsucht%s\n", 'Z Scharfe Schranke', $lauf,
       $zFehler ? '   ' . $zFehler . ' FEHLGESCHLAGEN' : '');
foreach ($zTags as $tag => $was) { echo "    ✗ fremdes Tag <$tag> in: $was\n"; }
foreach ($zAttr as $a => $was)   { echo "    ✗ fremdes Attribut $a= in: $was\n"; }

$gesamt = $lauf + $hLauf;
$schlimm = $fehler + $hFehler + $zFehler;
echo "\n", str_repeat('-', 62), "\n";
printf("%d Proben gefahren, dazu %d Ausgaben gegen die Tag- und Attributliste gehalten.\n", $gesamt, $lauf);
printf("%d fehlgeschlagen.\n", $schlimm);
exit($schlimm === 0 ? 0 : 1);
