<?php
declare(strict_types=1);

/**
 * Impressum und Datenschutzerklärung — Ablage und Darstellung (R32, P3/O10).
 *
 * DIE ANWENDUNG LIEFERT KEINEN RECHTSTEXT MIT. Was darin steht, ist Sache des
 * Betreibers dieser Installation; wir stellen nur die beiden Seiten, den
 * Editor und die Verweise in der Fußzeile. Solange nichts hinterlegt ist,
 * zeigt die Seite ihren Leerzustand — und das ist eine gültige Antwort, kein
 * Fehler.
 *
 * WARUM EIN EIGENER RENDERER UND KEINE BIBLIOTHEK. Das Projekt hat keine
 * composer.json und keinen Autoloader; unter `assets/vendor/` liegt
 * ausschließlich Browser-JavaScript. Eine PHP-Bibliothek hätte hier keinen
 * Ort — und der Umfang, den E-P3-38 verlangt (Überschriften, Absätze, Listen,
 * Links), ist klein genug, dass eine eigene Fassung überschaubarer bleibt als
 * die Konfiguration einer fremden.
 *
 * DAS GRUNDPRINZIP: ERST MASKIEREN, DANN STRUKTUR ERKENNEN.
 *
 * Zuerst geht der GANZE Text durch htmlspecialchars(), erst danach sucht der
 * Parser nach Überschriften, Listen und Links. Damit ist rohes HTML nicht
 * gefiltert, sondern unmöglich: Wenn der Parser das erste Zeichen ansieht,
 * ist `<` längst `&lt;`. Eine Sperrliste von Tags wäre der falsche Ansatz —
 * sie ist immer unvollständig, und die Lücke findet man erst, wenn sie jemand
 * benutzt hat.
 *
 * WAS DAS KOSTET: Wer eine HTML-Vorlage aus dem Netz in den Editor kopiert,
 * sieht ihre Tags als Text. Das ist gewollt, und die Syntaxzeile im Editor
 * sagt es ausdrücklich.
 */

/** Die beiden Dokumente: Schlüssel => Überschrift der Seite. */
const RT_TEXTE = [
    'impressum'   => 'Impressum',
    'datenschutz' => 'Datenschutzerklärung',
];

/** Die zugehörige Seite je Schlüssel — für Verweise und den Editor. */
const RT_SEITEN = [
    'impressum'   => 'impressum.php',
    'datenschutz' => 'datenschutz.php',
];

/**
 * Obergrenze der Eingabe.
 *
 * ABGELEHNT, NICHT GEKÜRZT — bewusst anders als das Hausmuster pruef_text()
 * (validate_lib.php), das zuschneidet. Bei einem Einsatzfeld ist ein
 * abgeschnittener Wert unschön; bei einem Rechtstext ist er juristisch heikel,
 * und der Fehler fällt niemandem auf, weil das Ende einer langen Seite selten
 * gelesen wird.
 */
const RT_MAX_ZEICHEN = 60000;

/* ---------------------------------------------------------------------------
 * ABLAGE
 * ------------------------------------------------------------------------ */

/**
 * Einen Rechtstext holen.
 *
 * WIRFT NIE. Diese Funktion läuft auf zwei Seiten, die JEDER erreichen kann —
 * auch jemand, der gerade die erste Fassung aufspielt und update.php noch
 * nicht aufgerufen hat. Eine fehlende Tabelle ist dann kein Ausnahmefall,
 * sondern der Normalzustand zwischen Deploy und Migration; sie darf die Seite
 * nicht kosten. Dasselbe Muster wie demo_id() (demo_lib.php).
 *
 * @return array{inhalt: string, stand: ?string}  stand = DATE oder null
 */
function rt_lesen(string $schluessel): array
{
    $leer = ['inhalt' => '', 'stand' => null];
    if (!isset(RT_TEXTE[$schluessel])) { return $leer; }
    try {
        $st = db()->prepare('SELECT inhalt, stand_am FROM rechtstexte WHERE schluessel = ?');
        $st->execute([$schluessel]);
        $zeile = $st->fetch();
        if (!$zeile) { return $leer; }
        return [
            'inhalt' => (string)($zeile['inhalt'] ?? ''),
            'stand'  => $zeile['stand_am'] !== null ? (string)$zeile['stand_am'] : null,
        ];
    } catch (Throwable) {
        // Keine Tabelle, keine Datenbank, kein Eintrag: Leerzustand.
        return $leer;
    }
}

/** Beide Texte auf einmal — für den Editor und die Fußzeile. */
function rt_alle(): array
{
    $aus = [];
    foreach (RT_TEXTE as $k => $_) { $aus[$k] = rt_lesen($k); }
    return $aus;
}

/** Ist nichts hinterlegt? Nur Leerraum zählt als leer. */
function rt_leer(?string $inhalt): bool
{
    return trim((string)$inhalt) === '';
}

/**
 * Die Eingabe prüfen, BEVOR sie gespeichert wird.
 *
 * @return ?string  null = in Ordnung, sonst der Fehlertext für ui_meldung()
 */
function rt_pruefen(string $text, ?string $stand): ?string
{
    if (!mb_check_encoding($text, 'UTF-8')) {
        return 'Der Text enthält Zeichen, die nicht als UTF-8 lesbar sind. '
             . 'Er wurde nicht gespeichert — bitte aus einem einfachen Texteditor '
             . 'einfügen, nicht aus einem Textverarbeitungsprogramm.';
    }
    if (mb_strlen($text, 'UTF-8') > RT_MAX_ZEICHEN) {
        return 'Der Text ist mit ' . number_format(mb_strlen($text, 'UTF-8'), 0, ',', '.')
             . ' Zeichen länger als die zulässigen '
             . number_format(RT_MAX_ZEICHEN, 0, ',', '.') . '. Er wurde '
             . 'nicht gespeichert — gekürzt würde ein Rechtstext unvollständig, '
             . 'ohne dass es jemandem auffällt.';
    }
    if ($stand !== null && $stand !== ''
        && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $stand)) {
        return 'Das Standdatum ist kein gültiges Datum.';
    }
    return null;
}

/**
 * Speichern. Legt die Zeile an, wenn es sie noch nicht gibt.
 *
 * Das Standdatum wird VON HAND gesetzt und hier nur durchgereicht: Bei einem
 * Rechtstext ist das Datum eine Aussage, und eine Kommakorrektur soll ihn
 * nicht neu datieren.
 */
function rt_speichern(string $schluessel, string $text, ?string $stand): void
{
    if (!isset(RT_TEXTE[$schluessel])) { return; }
    db()->prepare('INSERT INTO rechtstexte (schluessel, inhalt, stand_am)
                   VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE inhalt = VALUES(inhalt), stand_am = VALUES(stand_am)')
        ->execute([$schluessel, $text, ($stand === '' ? null : $stand)]);
}

/* ---------------------------------------------------------------------------
 * DARSTELLUNG
 * ------------------------------------------------------------------------ */

/**
 * Die erlaubten Linkziele.
 *
 * POSITIVLISTE, KEINE SPERRLISTE. Was hier nicht steht, wird nicht zum Link —
 * `javascript:`, `data:`, `vbscript:`, `blob:`, `file:` und alles, was es
 * morgen gibt, sind damit ohne eigenen Eintrag ausgeschlossen.
 *
 * Auch `//fremde.example/pfad` fällt durch: protokollrelative Adressen sehen
 * relativ aus und sind es nicht.
 */
function rt_ziel_erlaubt(string $ziel): bool
{
    $z = trim($ziel);
    if ($z === '') { return false; }
    return (bool)(
        preg_match('~^https?://[^\s"<>]+$~', $z)                 // ins Netz
        || preg_match('~^mailto:[^\s"<>]+$~', $z)                 // fürs Impressum unverzichtbar
        || preg_match('~^[A-Za-z0-9_.-]+\.php(\?[^\s"<>]*)?$~', $z)  // eigene Seite
        || preg_match('~^#[A-Za-z0-9_-]+$~', $z)                  // Anker
    );
}

/**
 * Zeichen entfernen, die im Fließtext nichts zu suchen haben.
 *
 * NICHT NUR KOSMETIK. Die Bidi-Steuerzeichen U+202A–U+202E und U+2066–U+2069
 * drehen die Leserichtung um: Damit lässt sich ein Linktext bauen, der etwas
 * anderes anzeigt, als im Ziel steht („Trojan Source"). U+2028 und U+2029
 * brechen Zeichenketten in JavaScript. Zero-Width-Zeichen machen zwei Wörter
 * ununterscheidbar, die es nicht sind.
 */
function rt_saeubern(string $t): string
{
    $t = str_replace(["\r\n", "\r"], "\n", $t);
    // C0-Steuerzeichen außer \n und \t, dazu DEL
    $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $t) ?? '';
    // Zero-Width, Zeilen-/Absatztrenner, Bidi-Steuerung
    $t = preg_replace('/[\x{200B}-\x{200F}\x{2028}\x{2029}\x{202A}-\x{202E}\x{2066}-\x{2069}\x{FEFF}]/u',
                      '', $t) ?? '';
    return $t;
}

/**
 * Eingeschränktes Markdown → sicheres HTML.
 *
 * REIN UND OHNE DATENBANK: Diese Funktion ist die eine Stelle, die geprüft
 * werden muss, und sie lässt sich einzeln durchspielen — siehe
 * `tools/rechtstexte/`.
 *
 * WAS SIE KENNT (E-P3-38, mehr nicht):
 *   `#` und `##`  →  <h2>          (die Seite hat ihr <h1> aus der Titelzeile)
 *   `###`         →  <h3>
 *   `- ` / `* `   →  <ul><li>
 *   `1. `         →  <ol><li>
 *   Leerzeile     →  Absatzgrenze
 *   [Text](Ziel)  →  <a href="Ziel">Text</a>, wenn das Ziel erlaubt ist
 *
 * WAS SIE NICHT KENNT, UND WARUM:
 *   **fett**, *kursiv*  — E-P3-38 nennt sie nicht. Jede Erweiterung ist eine
 *                         Vertragsänderung, keine Formatierung.
 *   ![Bild](url)        — holte eine fremde Quelle zur Laufzeit und bräche
 *                         damit eine feste Zusage des Projekts (CLAUDE.md §4).
 *   <https://…>         — Autolinks umgehen die Zielprüfung.
 *   [x][1] mit [1]: …   — Referenzlinks ebenso.
 *   Titel: [x](u "T")   — nicht vorgesehen; ein Attribut mehr ist eine
 *                         Angriffsfläche mehr.
 *
 * KEIN target="_blank": Auf einer Rechtstextseite ist der Zurück-Weg des
 * Browsers die richtige Antwort — und ohne target braucht es auch kein
 * rel="noopener".
 */
function rt_html(string $markdown): string
{
    $t = rt_saeubern($markdown);
    if (!mb_check_encoding($t, 'UTF-8')) {
        /* Sollte rt_pruefen() abgefangen haben. Wenn doch etwas durchkommt —
         * ein Altbestand, ein Direkteintrag in der Datenbank —, ist der
         * Leerzustand die ehrlichere Antwort als eine halb dekodierte Seite. */
        return '';
    }

    /* HIER UND NUR HIER WIRD MASKIERT. Genau einmal, über den ganzen Text,
     * bevor irgendetwas erkannt wird.
     *
     * ENT_SUBSTITUTE ausdrücklich — anders als e() (db.php). Ohne den Schalter
     * liefert PHP seit 8.1 bei ungültigem UTF-8 den LEERSTRING: Bei einem
     * Feldwert bliebe das unauffällig, bei einem Rechtstext verschwände die
     * ganze Seite wortlos, und der Leerzustand sähe aus wie „noch nichts
     * hinterlegt". */
    $t = htmlspecialchars($t, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    $aus = [];
    $absatz = [];      // gesammelte Zeilen des laufenden Absatzes
    $liste  = null;    // 'ul' | 'ol' | null

    $absatzSchliessen = function () use (&$absatz, &$aus): void {
        if ($absatz) {
            $aus[] = '<p>' . implode('<br>', $absatz) . '</p>';
            $absatz = [];
        }
    };
    $listeSchliessen = function () use (&$liste, &$aus): void {
        if ($liste !== null) { $aus[] = '</' . $liste . '>'; $liste = null; }
    };

    foreach (explode("\n", $t) as $zeile) {
        $z = rtrim($zeile);
        $roh = ltrim($z);

        if ($roh === '') {
            $absatzSchliessen(); $listeSchliessen();
            continue;
        }

        /* Überschriften. `####` und tiefer sind KEINE Überschrift: Die Seite
         * hat ihr <h1> aus der Titelzeile, und unter <h3> wird die Gliederung
         * ohnehin unlesbar. Sie bleiben Text. */
        if (preg_match('/^(#{1,3})\s+(.+)$/', $roh, $m)) {
            $absatzSchliessen(); $listeSchliessen();
            $stufe = strlen($m[1]) === 3 ? 'h3' : 'h2';
            $aus[] = '<' . $stufe . '>' . rt_inline(trim($m[2])) . '</' . $stufe . '>';
            continue;
        }

        // Aufzählung
        if (preg_match('/^[-*]\s+(.+)$/', $roh, $m)) {
            $absatzSchliessen();
            if ($liste !== 'ul') { $listeSchliessen(); $aus[] = '<ul>'; $liste = 'ul'; }
            $aus[] = '<li>' . rt_inline(trim($m[1])) . '</li>';
            continue;
        }

        // Nummerierung
        if (preg_match('/^\d{1,3}\.\s+(.+)$/', $roh, $m)) {
            $absatzSchliessen();
            if ($liste !== 'ol') { $listeSchliessen(); $aus[] = '<ol>'; $liste = 'ol'; }
            $aus[] = '<li>' . rt_inline(trim($m[1])) . '</li>';
            continue;
        }

        /* Alles übrige ist Fließtext. Mehrere Zeilen ohne Leerzeile dazwischen
         * bleiben EIN Absatz mit weichen Umbrüchen — im Impressum stehen
         * Anschriften genau so untereinander. */
        $listeSchliessen();
        $absatz[] = rt_inline($z);
    }
    $absatzSchliessen();
    $listeSchliessen();

    return implode("\n", $aus);
}

/**
 * Die Inline-Ebene: genau ein Muster, `[Text](Ziel)`.
 *
 * Der Text ist bereits maskiert, wenn diese Funktion ihn sieht — `"` ist
 * `&quot;`, `<` ist `&lt;`. Ein Ausbruch aus dem Attribut ist damit schon
 * ausgeschlossen, bevor die Zielprüfung überhaupt greift; sie ist die zweite
 * Schranke, nicht die erste.
 *
 * EIN ABGELEHNTES ZIEL LÄSST DIE GANZE KONSTRUKTION ALS TEXT STEHEN. Stilles
 * Schlucken macht aus einem Fehler eine Unsichtbarkeit: Wer `javascript:` in
 * einen Rechtstext schreibt, soll sehen, dass daraus kein Link geworden ist.
 *
 * Das führende `(?<!\!)` schließt `![alt](url)` aus — Bilder sind nicht
 * vorgesehen, und ohne die Schranke würde aus einem Bild ein Link.
 */
function rt_inline(string $maskiert): string
{
    return preg_replace_callback(
        '/(?<!\!)\[([^\]\n]{1,300})\]\(([^)\s\n]{1,500})\)/',
        static function (array $m): string {
            /* Das Ziel steht maskiert da (`&amp;`, `&quot;`). Für die Prüfung
             * wird es zurückverwandelt — sonst käme `&amp;` in einer
             * Abfragezeichenkette nie durch —, für die Ausgabe bleibt die
             * maskierte Fassung. */
            $zielRoh = html_entity_decode($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if (!rt_ziel_erlaubt($zielRoh)) { return $m[0]; }
            return '<a href="' . $m[2] . '">' . $m[1] . '</a>';
        },
        $maskiert
    ) ?? $maskiert;
}

/**
 * Die Standzeile am Ende einer Rechtstextseite.
 *
 * Leeres Datum = keine Zeile. Ein „Stand: —" wäre eine Aussage über nichts.
 */
function rt_stand_markup(?string $stand): string
{
    if ($stand === null || $stand === '') { return ''; }
    $ts = strtotime($stand);
    if ($ts === false) { return ''; }
    return '<p class="text-stand">Stand: ' . ui_e(date('d.m.Y', $ts)) . '</p>';
}
