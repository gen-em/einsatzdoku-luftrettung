<?php
declare(strict_types=1);

/**
 * Traegt die Content-Security-Policy noch? (P5a/AP4, E-P5a-15)
 *
 * WOGEGEN. Seit Web 20.7.0 schickt jede Seite eine Richtlinie mit
 * `script-src 'self' 'nonce-…'` — ohne `'unsafe-inline'`. Ein Inline-Skript
 * ohne Nonce wird vom Browser nicht mehr ausgefuehrt. Das ist genau der
 * Zweck; der Preis ist, dass ein VERGESSENER Nonce eine Seite still
 * lahmlegt. Still heisst: kein PHP-Fehler, kein Eintrag im Protokoll, keine
 * rote Seite — der Knopf tut einfach nichts, und die Meldung steht in der
 * Konsole derjenigen, der es passiert.
 *
 * Der Fehler ist nicht selten, sondern der Normalfall: Wer eine neue Seite
 * anlegt, schreibt `<script>` — so steht es in jedem Beispiel der Welt. Die
 * Schreibweise dieses Projekts ist `<script<?= kopf_nonce_attr() ?>>`, und
 * daran denkt man beim dritten Mal nicht mehr. Deshalb zaehlt es hier eine
 * Maschine nach.
 *
 * WAS GEPRUEFT WIRD — fuenf Dinge, alle gegen dieselbe Richtlinie:
 *
 *   1. INLINE-`<script>` OHNE NONCE. Der Hauptbefund.
 *   2. INLINE-`<style>`. `style-src 'self'` laesst keinen Stilblock im
 *      Dokument zu. Es gibt derzeit keinen; die Pruefung haelt es so.
 *   3. EREIGNIS-ATTRIBUTE (`onclick=`, `onchange=`, …). Sie sind Skript im
 *      Markup und fallen unter `script-src`; ein Nonce hilft ihnen nicht.
 *      Nur `'unsafe-hashes'` wuerde sie zulassen — das steht nicht in der
 *      Richtlinie und soll auch nicht hinein.
 *   4. `javascript:`-ADRESSEN in `href`/`src`. Dasselbe in Gruen.
 *   5. FREMDE HERKUNFT in `src`/`href` — ein `https://…`-Verweis auf ein
 *      Skript, ein Stylesheet oder eine Schrift. Die Zusage „keine fremde
 *      Quelle zur Laufzeit" (CLAUDE.md 4) und `default-src 'none'` sagen
 *      dasselbe; hier faellt es frueher auf.
 *
 * WIE GEMESSEN WIRD: mit `token_get_all()`, nicht mit `grep`. Dieses Projekt
 * erklaert seine Entscheidungen in langen Kommentaren, und die reden ueber
 * `<script>`-Bloecke. Ein `grep` faende sie alle — sieben Stueck allein in
 * `db.php`, `ui.php` und `version.php` — und eine Pruefung mit falschem Alarm
 * wird nach dem zweiten Lauf abgeschaltet. Der Tokenizer trennt, was der
 * Uebersetzer trennt: `T_INLINE_HTML` und Zeichenketten sind Markup,
 * `T_COMMENT` und `T_DOC_COMMENT` sind es nicht.
 *
 * WAS SIE NICHT SIEHT — die Grenzen stehen am Ende JEDES Laufs, nicht nur
 * hier, weil eine gruene Zahl ohne ihre Grenzen eine Behauptung ist:
 *
 *   · Markup, das zur Laufzeit aus JavaScript entsteht
 *     (`el.innerHTML = '<script>…'`). Es steht in `assets/*.js`, und diese
 *     Pruefung liest nur PHP. Ein per `innerHTML` eingesetztes `<script>`
 *     fuehrt der Browser allerdings ohnehin nicht aus.
 *   · Ereignisse, die per `addEventListener` gebunden werden — die sind der
 *     richtige Weg und kein Befund.
 *   · Ob der Nonce am Ende auch WIRKT. Dass `kopf_nonce_attr()` dasteht,
 *     heisst nicht, dass `kopfzeilen_seite()` vorher lief. Das sieht nur der
 *     Browser; dafuer ist der Bilderlauf da, und dafuer gibt es
 *     `api/csp_bericht.php`.
 *   · `style="…"`-Attribute. Sie sind ERLAUBT (`style-src-attr
 *     'unsafe-inline'`, E-P5a-32) und werden nur GEZAEHLT — die Zahl steht
 *     im Bericht, damit ein Wachsen auffaellt.
 *
 * Aufruf:
 *   php tools/quelltext/csp.php
 *   php tools/quelltext/csp.php --selbstprobe
 *
 * Rueckgabewert: 0 = die Richtlinie traegt · 1 = Befund · 2 = Aufbau kaputt.
 */

/** Ereignis-Attribute, die als Skript gelten. Nicht abschliessend — Grenze. */
const CSP_EREIGNISSE = [
    'onclick', 'ondblclick', 'onchange', 'oninput', 'onsubmit', 'onreset',
    'onload', 'onerror', 'onfocus', 'onblur', 'onkeyup', 'onkeydown',
    'onkeypress', 'onmouseover', 'onmouseout', 'onmousedown', 'onmouseup',
    'onpaste', 'oncut', 'oncopy', 'oncontextmenu', 'ontoggle', 'onscroll',
    'ondrop', 'ondragover', 'onwheel', 'onanimationend', 'ontransitionend',
];

/**
 * Aus einer PHP-Quelle das MARKUP-BILD bauen — das, was ein Browser saehe.
 *
 * DER ERSTE VERSUCH WAR FALSCH, und der Fehler ist lehrreich genug, um hier
 * zu stehen: Er sammelte die `T_INLINE_HTML`-Stuecke einzeln ein und suchte
 * in jedem nach `<script…>`. Ergebnis: **null** Skript-Stellen bei 116
 * tatsaechlichen. Denn die Schreibweise dieses Projekts ist
 *
 *     <script src="<?= asset('assets/html.js') ?>"></script>
 *
 * und die zerfaellt in DREI Stuecke: `<script src="` — PHP — `"></script>`.
 * Kein einziges davon ist ein vollstaendiges Tag. Eine Pruefung, die null
 * meldet, weil sie nichts ansieht, sieht aus wie eine, die nichts gefunden
 * hat (CLAUDE.md 6: „eine gruene Zahl ist erst dann ein Beleg, wenn sie das
 * Gemessene benennt").
 *
 * DESHALB EIN DURCHGEHENDER TEXT je Datei, Zeichen fuer Zeichen so lang wie
 * die Quelle, damit Zeilennummern stimmen:
 *
 *   · Markup und Zeichenketten stehen VERBATIM — `ui.php` schreibt sein
 *     Markup aus PHP heraus, und das ist genauso Markup.
 *   · Kommentare werden GELEERT (Zeilenumbrueche bleiben). Dieses Projekt
 *     erklaert sich in langen Kommentaren, und sieben davon reden ueber
 *     `<script>`-Bloecke.
 *   · `<?php`, `<?=` und `?>` werden zu Leerzeichen — sie sind die Klammern,
 *     nicht der Inhalt.
 *   · Uebriger PHP-Code bleibt lesbar, aber `<` und `>` werden zu `_`. So
 *     kann ein `=>` in einem Feldliteral kein Tag vorzeitig schliessen,
 *     waehrend `kopf_nonce_attr` als Wort erhalten bleibt — und genau
 *     danach wird gesucht.
 *
 * Aus `<script<?= kopf_nonce_attr() ?>>` wird damit
 * `<script    kopf_nonce_attr()   >`: ein vollstaendiges Tag mit dem Wort
 * darin.
 */
function csp_sicht(string $quelle): string
{
    $aus = '';
    /** Text auf gleiche Laenge leeren, Zeilenumbrueche behalten. */
    $leeren = static fn(string $t): string => preg_replace('/[^\n]/', ' ', $t) ?? '';

    foreach (token_get_all($quelle) as $t) {
        if (!is_array($t)) {
            $aus .= strtr($t, ['<' => '_', '>' => '_']);
            continue;
        }
        $name = token_name($t[0]);
        $text = $t[1];
        switch ($name) {
            case 'T_COMMENT':
            case 'T_DOC_COMMENT':
            case 'T_OPEN_TAG':
            case 'T_OPEN_TAG_WITH_ECHO':
            case 'T_CLOSE_TAG':
                $aus .= $leeren($text);
                break;
            case 'T_INLINE_HTML':
            case 'T_CONSTANT_ENCAPSED_STRING':
            case 'T_ENCAPSED_AND_WHITESPACE':
            case 'T_HEREDOC':
                $aus .= $text;
                break;
            default:
                $aus .= strtr($text, ['<' => '_', '>' => '_']);
        }
    }
    return $aus;
}

/** Zeilennummer einer Fundstelle im Markup-Bild. */
function csp_zeile(string $sicht, int $pos): int
{
    return substr_count(substr($sicht, 0, $pos), "\n") + 1;
}

/**
 * Ein Markup-Bild gegen die fuenf Regeln halten.
 *
 * @return list<array{zeile:int,art:string,was:string}>
 */
function csp_pruefen(string $quelle): array
{
    $sicht   = csp_sicht($quelle);
    $befunde = [];

    /* 1. <script> ohne src und ohne Nonce */
    if (preg_match_all('/<script\b([^>]*)>/i', $sicht, $tr, PREG_OFFSET_CAPTURE)) {
        foreach ($tr[1] as $i => [$attr, $_]) {
            if (preg_match('/\bsrc\s*=/i', $attr)) { continue; }
            if (stripos($attr, 'kopf_nonce_attr') !== false
                || preg_match('/\bnonce\s*=/i', $attr)) { continue; }
            $befunde[] = ['zeile' => csp_zeile($sicht, $tr[0][$i][1]),
                          'art'   => 'Skript ohne Nonce',
                          'was'   => trim(preg_replace('/\s+/', ' ', $tr[0][$i][0]) ?? '')];
        }
    }

    /* 2. <style> im Dokument */
    if (preg_match_all('/<style\b[^>]*>/i', $sicht, $tr, PREG_OFFSET_CAPTURE)) {
        foreach ($tr[0] as [$ganz, $pos]) {
            $befunde[] = ['zeile' => csp_zeile($sicht, $pos),
                          'art'   => 'Stilblock im Dokument',
                          'was'   => trim(preg_replace('/\s+/', ' ', $ganz) ?? '')];
        }
    }

    /* 3. Ereignis-Attribute */
    $muster = '/(?<![\w-])(' . implode('|', CSP_EREIGNISSE) . ')\s*=\s*["\']/i';
    if (preg_match_all($muster, $sicht, $tr, PREG_OFFSET_CAPTURE)) {
        foreach ($tr[1] as [$name, $pos]) {
            $befunde[] = ['zeile' => csp_zeile($sicht, $pos),
                          'art'   => 'Ereignis im Markup',
                          'was'   => strtolower($name) . '='];
        }
    }

    /* 4. javascript:-Adressen */
    if (preg_match_all('/(?:href|src|action)\s*=\s*["\']\s*javascript:/i',
                       $sicht, $tr, PREG_OFFSET_CAPTURE)) {
        foreach ($tr[0] as [$ganz, $pos]) {
            $befunde[] = ['zeile' => csp_zeile($sicht, $pos),
                          'art'   => 'javascript:-Adresse',
                          'was'   => trim($ganz)];
        }
    }

    /* 5. Fremde Herkunft in src/href */
    if (preg_match_all('#(?:href|src)\s*=\s*["\'](https?:)?//([A-Za-z0-9.\-]+)#i',
                       $sicht, $tr, PREG_OFFSET_CAPTURE)) {
        foreach ($tr[2] as $i => [$host, $_]) {
            $befunde[] = ['zeile' => csp_zeile($sicht, $tr[0][$i][1]),
                          'art'   => 'Fremde Herkunft',
                          'was'   => $host];
        }
    }
    return $befunde;
}

/** `style="…"` zaehlen — erlaubt, aber im Blick behalten. */
function csp_stilattribute(string $quelle): int
{
    return preg_match_all('/\bstyle\s*=\s*["\']/i', csp_sicht($quelle));
}

// ---------------------------------------------------------------- Selbstprobe

/**
 * Prueft die Pruefung.
 *
 * Ein gruener Lauf einer Pruefung, die immer gruen meldet, sieht genauso aus
 * wie einer, der nichts gefunden hat. Acht Faelle: vier muessen anschlagen,
 * vier duerfen es nicht.
 */
function csp_selbstprobe(): int
{
    $faelle = [
        ['Skript ohne Nonce', true,
         '<?php ?><script>alert(1)</script>'],
        ['Stilblock', true,
         '<?php ?><style>.a{color:red}</style>'],
        ['Ereignis im Markup', true,
         '<?php ?><button onclick="f()">x</button>'],
        ['Fremde Herkunft', true,
         '<?php ?><script src="https://cdn.example.com/a.js"></script>'],
        ['Nonce vorhanden', false,
         '<?php ?><script<?= kopf_nonce_attr() ?>>alert(1)</script>'],
        ['Eigenes Skript', false,
         '<?php ?><script src="<?= asset(\'assets/html.js\') ?>"></script>'],
        ['Kommentar, der <script> nennt', false,
         '<?php /* Ein <script>-Block ohne Nonce laeuft nicht. */ ?>'],
        ['Wort mit on-Anfang', false,
         '<?php ?><input data-onload="1" name="onlineform">'],
    ];
    $fehler = 0;
    echo "Selbstprobe der CSP-Probe\n\n";
    foreach ($faelle as [$name, $erwartet, $quelle]) {
        $b = csp_pruefen($quelle);
        $traf = $b !== [];
        $ok   = $traf === $erwartet;
        if (!$ok) { $fehler++; }
        printf("  %-32s %s (%s, %d Befunde)\n", $name . ':',
               $ok ? 'gut' : 'FEHLER',
               $erwartet ? 'muss anschlagen' : 'darf nicht anschlagen',
               count($b));
    }
    printf("\n  %d von %d Faellen richtig.\n", count($faelle) - $fehler, count($faelle));
    return $fehler ? 1 : 0;
}

// ---------------------------------------------------------------------- Lauf

if (in_array('--selbstprobe', $argv, true)) { exit(csp_selbstprobe()); }

$wurzel = dirname(__DIR__, 2) . '/server';
if (!is_dir($wurzel)) {
    fwrite(STDERR, "Verzeichnis fehlt: $wurzel\n");
    exit(2);
}

$dateien = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($wurzel,
        FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    /** @var SplFileInfo $f */
    if (!$f->isFile() || $f->getExtension() !== 'php') { continue; }
    /* `vendor/` bleibt draussen. phpseclib3 bringt HTML-Hilfen mit
     * (`File/ANSI.php` malt ein Terminal mit `style="color: white"`), aber
     * diese Anwendung ruft sie nicht auf und liefert die Datei nie aus.
     * Mitzaehlen hiesse, eine Zahl zu melden, die nichts ueber die
     * Oberflaeche aussagt — und die beim naechsten Bibliotheks-Update
     * grundlos springt. */
    if (str_contains($f->getPathname(), '/vendor/')) { continue; }
    $dateien[] = $f->getPathname();
}
sort($dateien);

/* Die Laufzeit-Stellen stehen in JavaScript, nicht in PHP: Leaflet-divIcons,
 * Zeilenvorlagen per innerHTML, die Balken der Schnittleiste. Sie bleiben
 * bewusst stehen (E-P5a-32) — `style-src-attr 'unsafe-inline'` laesst sie zu.
 * Die Zahl steht hier, weil sie sonst nirgends steht. */
$stilJs = 0;
$jsWurzel = $wurzel . '/assets';
if (is_dir($jsWurzel)) {
    $jt = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jsWurzel,
            FilesystemIterator::SKIP_DOTS));
    foreach ($jt as $f) {
        /** @var SplFileInfo $f */
        if (!$f->isFile() || $f->getExtension() !== 'js') { continue; }
        if (str_contains($f->getPathname(), '/vendor/')) { continue; }
        $stilJs += preg_match_all('/\bstyle\s*=\s*["\']/i',
                                  (string)file_get_contents($f->getPathname()));
    }
}

$befunde = [];
$stil    = 0;
$skripte = 0;
foreach ($dateien as $datei) {
    $quelle = (string)file_get_contents($datei);
    $kurz   = substr($datei, strlen($wurzel) + 1);
    foreach (csp_pruefen($quelle) as $b) { $befunde[] = $b + ['datei' => $kurz]; }
    $stil    += csp_stilattribute($quelle);
    $skripte += preg_match_all('/<script\b[^>]*>/i', csp_sicht($quelle));
}

echo "Content-Security-Policy — was im Markup steht\n\n";
printf("  %-36s %d\n", 'PHP-Dateien (ohne vendor/):', count($dateien));
printf("  %-36s %d\n", '<script>-Stellen im Markup:', $skripte);
printf("  %-36s %d\n", 'style="…" in PHP (erlaubt):', $stil);
printf("  %-36s %d\n", 'style="…" in assets/*.js (erlaubt):', $stilJs);
printf("  %-36s %d\n", 'Regeln:', 5);
printf("  %-36s %d\n", 'Befunde:', count($befunde));
echo "\n";

foreach ($befunde as $b) {
    echo '  ' . $b['datei'] . ', Zeile ' . $b['zeile'] . ': '
       . $b['art'] . ' — ' . $b['was'] . "\n";
}
if ($befunde) {
    echo "\n  Diese Stellen fuehrt der Browser unter der scharfen Richtlinie nicht\n"
       . "  mehr aus — ohne Fehlermeldung. Schreibweise:\n"
       . "      <script<?= kopf_nonce_attr() ?>>\n";
}

echo "\nGRENZEN dieser Pruefung — was sie NICHT sieht:\n"
   . "  · Markup, das zur Laufzeit in assets/*.js entsteht (nur PHP wird gelesen)\n"
   . "  · ob der Nonce WIRKT — dass er dasteht, heisst nicht, dass\n"
   . "    kopfzeilen_seite() vorher lief. Das sieht nur der Browser.\n"
   . "  · Ereignisse per addEventListener — richtiger Weg, kein Befund\n"
   . "  · die Liste der Ereignis-Attribute ist nicht abschliessend ("
   . count(CSP_EREIGNISSE) . " Stueck)\n"
   . "Die style=\"…\"-Zahl ist kein Befund: style-src-attr 'unsafe-inline'\n"
   . "laesst sie zu (E-P5a-32). Sie steht da, damit ein Wachsen auffaellt.\n";

exit($befunde ? 1 : 0);
