<?php
declare(strict_types=1);

/**
 * HANDBUCH UND „WAS IST NADOKU" ALS SEITEN DER ANWENDUNG (P5b/AP8,
 * E-P5b-08, -22).
 *
 * Die Quelle bleibt Markdown im Repositorium — `docs/Handbuch.md` und
 * `docs/Was-ist-NAdoku.md`. Das ist die ganze Idee: auf GitHub editierbar,
 * die Wortliste laeuft darueber, das Prueftor prueft die Rendertauglichkeit,
 * und es gibt keine zweite Fassung in einer Datenbank, die auseinanderlaeuft.
 * Die Anwendung rendert zur Laufzeit.
 *
 * ---------------------------------------------------------------------------
 * WO DIE DATEIEN LIEGEN — ZWEI ORTE, IN DIESER REIHENFOLGE
 * ---------------------------------------------------------------------------
 *
 *   1. `../docs/`   Selbsthosterinnen laden das Repositorium hoch; dann liegt
 *                   `docs/` neben `server/` und ist die frischeste Quelle.
 *   2. `doku/`      Die Auslieferungskette kopiert beide Dateien vor dem Sync
 *                   nach `server/doku/`, weil der FTPS-Schritt nur `server/`
 *                   hochlaedt — auf dem Produktivserver gibt es kein `docs/`.
 *
 * FEHLT BEIDES, ist das kein Absturz, sondern eine Auskunft: `doku_pfad()`
 * gibt `null`, die Seite zeigt einen Hinweis, und `betrieb_status.php` meldet
 * es. Eine leere Seite waere die schlechteste der drei Antworten — sie sieht
 * aus wie ein kaputtes Handbuch und nicht wie eine fehlende Datei.
 *
 * ---------------------------------------------------------------------------
 * WARUM HIER KEIN CACHE STEHT, OBWOHL DAS KONZEPT EINEN VERLANGT
 * ---------------------------------------------------------------------------
 *
 * E-P5b-22 sagt: „Cache in `app_state` unter `doku:<name>:<hash>`". Das geht
 * nicht, und es ist auch nicht noetig:
 *
 *   ES GEHT NICHT. `app_state.v` ist `VARCHAR(190)` (`APP_STATE_MAX` in
 *   `db.php`). Das gerenderte Handbuch ist **303 KB**. `app_state_setzen()`
 *   haette den Wert abgelehnt und eine Zeile ins Fehlerprotokoll geschrieben
 *   — bei jedem Aufruf, still fuer die Nutzerin.
 *
 *   ES IST NICHT NOETIG. Gemessen am 17.09.2026 gegen `docs/Handbuch.md`
 *   (266 KB Markdown): **11 bis 12 ms** je Lauf, viermal nacheinander. Ein
 *   Cache spart hier eine Zehntelsekunde nicht, sondern eine Hundertstel —
 *   und kostet dafuer einen Invalidierungsweg, einen beschreibbaren Ort und
 *   eine Fehlerquelle, die erst auffaellt, wenn jemand ein veraltetes
 *   Handbuch liest.
 *
 * Die Zahl stammt von der Arbeitsumgebung, nicht vom Hoster. Selbst um den
 * Faktor fuenf langsamer bleibt sie unter 60 ms; das ist weniger, als die
 * Datenbankverbindung der meisten Seiten kostet. Sollte sich das aendern,
 * ist der richtige Ort eine **Datei** neben der Quelle, keine Spalte mit 190
 * Zeichen — und der Befund gehoert dann ins Konzept, nicht in einen Kommentar.
 *
 * ---------------------------------------------------------------------------
 * WAS AN SICHERHEIT HIER HAENGT
 * ---------------------------------------------------------------------------
 *
 * Diese Seiten sind **oeffentlich, ohne Anmeldung**. Sie rendern eine Datei,
 * die im Repositorium liegt — also nichts, was eine Angreiferin einschleusen
 * koennte, ohne vorher Schreibrecht auf das Repositorium zu haben. Trotzdem
 * gilt hier dieselbe Strenge wie bei `rt_html()`, aus einem einfachen Grund:
 * Eine Selbsthosterin darf ihr eigenes Handbuch aendern, und dann ist die
 * Datei genau das, was sie bei `rt_html()` waere — fremder Text.
 *
 *   `setMarkupEscaped(true)`  rohes HTML im Markdown wird maskiert, nicht
 *                             ausgefuehrt. `<script>` erscheint als Text.
 *   `setSafeMode(true)`       dazu die Zielpruefung an Links und Bildern:
 *                             `javascript:`-Ziele und Attribute fallen weg.
 *
 * BEIDES, NICHT EINES. `setMarkupEscaped` allein liesse `[x](javascript:...)`
 * durch, `setSafeMode` allein laesst rohe HTML-Bloecke stehen.
 *
 * DAZU DREI EIGENE REGELN (`DokuMarkdown` unten): Sprungmarken an h2 und h3,
 * `rel="noopener"` an fremden Zielen, und Bilder nur relativ. Die dritte ist
 * die wichtigste — ein `![](https://fremd/...)` im Handbuch holte eine fremde
 * Quelle zur Laufzeit und braeche damit eine feste Zusage (CLAUDE.md 4) auf
 * einer Seite, die jede Besucherin vor der Anmeldung sieht.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/Parsedown.php';

/**
 * Die Dokumente, die diese Anwendung rendert. Eine geschlossene Liste.
 *
 * SIE IST GESCHLOSSEN, WEIL DER NAME AUS DER ADRESSZEILE KOMMEN KOENNTE.
 * Heute tut er es nicht — `hilfe.php` und `ueber.php` setzen ihn fest —, aber
 * eine Bibliothek, die einen Dateinamen entgegennimmt und daraus einen Pfad
 * baut, ist genau die Stelle, an der aus `../config.php` irgendwann ein
 * Einbruch wird. Wer ein drittes Dokument will, traegt es hier ein.
 */
const DOKU_DATEIEN = [
    'handbuch'       => 'Handbuch.md',
    'was-ist-nadoku' => 'Was-ist-NAdoku.md',
];

/** Die beiden Orte, in dieser Reihenfolge. Siehe Kopf. */
const DOKU_ORTE = [
    __DIR__ . '/../docs/',
    __DIR__ . '/doku/',
];

/**
 * Wo liegt dieses Dokument? `null`, wenn nirgends.
 *
 * @param string $name Schluessel aus DOKU_DATEIEN
 */
function doku_pfad(string $name): ?string
{
    $datei = DOKU_DATEIEN[$name] ?? null;
    if ($datei === null) { return null; }

    foreach (DOKU_ORTE as $ort) {
        $pfad = $ort . $datei;
        if (is_file($pfad) && is_readable($pfad)) { return $pfad; }
    }

    return null;
}

/**
 * Sind beide Dokumente da? Fuer die Statusseite.
 *
 * @return array<string, ?string> Schluessel => Pfad oder null
 */
function doku_bestand(): array
{
    $aus = [];
    foreach (array_keys(DOKU_DATEIEN) as $name) { $aus[$name] = doku_pfad($name); }
    return $aus;
}

/**
 * Eine Sprungmarke aus einem Ueberschriftentext — deterministisch und
 * umlautfest.
 *
 * DETERMINISTISCH, WEIL DIE ADRESSE EIN VERSPRECHEN IST. Auf
 * `hilfe.php#konto-und-anmeldung` zeigen Hilfe-Verweise aus der Oberflaeche,
 * und jemand legt sich das Lesezeichen an. Eine Marke, die sich beim naechsten
 * Rendern aendert, bricht beides still.
 *
 * UMLAUTFEST, UND ZWAR AUSGESCHRIEBEN: „Zurueck" und nicht „Zurck". Ein
 * `preg_replace` auf `[^a-z0-9]` frisst Umlaute ersatzlos und macht aus
 * „Schluessel" und „Schlssel" zwei Marken, die keiner unterscheidet. Deshalb
 * erst ersetzen, dann saeubern.
 */
function doku_marke(string $text): string
{
    $t = mb_strtolower(trim(strip_tags($text)), 'UTF-8');

    /* ERST DIE BUCHSTABEN, DIE EINE ENTSPRECHUNG HABEN. `ß` wird `ss`, nicht
     * `s` — sonst faellt „Massnahme" mit „Maßnahme" zusammen. */
    $t = strtr($t, [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ç' => 'c', 'ñ' => 'n',
    ]);

    $t = preg_replace('/[^a-z0-9]+/u', '-', $t) ?? '';
    $t = trim($t, '-');

    /* EINE LEERE MARKE GIBT ES NICHT. Eine Ueberschrift, die nur aus Zeichen
     * besteht, die hier wegfallen (etwa „###  —  ###"), bekaeme sonst `id=""`
     * — und zwei davon waeren dasselbe Ziel. */
    return $t !== '' ? $t : 'abschnitt';
}

/**
 * Parsedown mit den drei Hausregeln.
 *
 * KEINE AENDERUNG AN DER VENDORIERTEN DATEI. Die Bibliothek ist dafuer
 * gebaut, ueberschrieben zu werden; ein Patch in `vendor/` waere beim
 * naechsten Update verloren, und zwar unbemerkt.
 */
final class DokuMarkdown extends Parsedown
{
    /** @var array<int, array{stufe:int, text:string, marke:string}> */
    public array $inhalt = [];

    /** @var array<string, int> Wie oft eine Marke schon vergeben ist. */
    private array $vergeben = [];

    /**
     * Wohin zeigen relative Bildpfade? Siehe `inlineImage()`.
     */
    public string $bildBasis = 'doku/';

    /**
     * Ueberschriften bekommen eine Sprungmarke — und h2/h3 landen im
     * Inhaltsverzeichnis.
     *
     * NUR h2 UND h3 (E-P5b-22). Das Handbuch hat 13 h2, 54 h3 und 12 h4;
     * mit den h4 haette das Verzeichnis 79 Eintraege und waere laenger als
     * mancher Abschnitt. Eine Marke bekommen die h4 trotzdem — verlinken
     * koennen soll man sie, nur nicht durch sie blaettern.
     */
    protected function blockHeader($Line)
    {
        $Block = parent::blockHeader($Line);
        if ($Block === null || !isset($Block['element']['name'])) { return $Block; }

        $stufe = (int)substr($Block['element']['name'], 1);
        $text  = (string)($Block['element']['text'] ?? '');
        $marke = doku_marke($text);

        /* DOPPELTE TITEL. Heute hat das Handbuch keine (nachgezaehlt am
         * 17.09.2026: 0 Doppelungen unter 67 h2/h3). Das ist eine Eigenschaft
         * des heutigen Textes, keine Regel — und ohne diesen Zaehler waeren
         * zwei „Beispiel"-Ueberschriften dasselbe Sprungziel, wovon eines nie
         * erreichbar waere. */
        if (isset($this->vergeben[$marke])) {
            $this->vergeben[$marke]++;
            $marke .= '-' . $this->vergeben[$marke];
        } else {
            $this->vergeben[$marke] = 1;
        }

        $Block['element']['attributes'] = ['id' => $marke];

        if ($stufe === 2 || $stufe === 3) {
            $this->inhalt[] = ['stufe' => $stufe, 'text' => $text, 'marke' => $marke];
        }

        return $Block;
    }

    /**
     * Fremde Ziele bekommen `rel="noopener"`.
     *
     * KEIN `target="_blank"`, und deshalb ist `noopener` hier streng genommen
     * ueberfluessig — dieselbe Ueberlegung steht schon bei `rt_html()`. Es
     * steht trotzdem da, weil E-P5b-22 es verlangt und weil es nichts kostet:
     * Setzt jemand spaeter ein `target`, ist das Attribut schon an der
     * richtigen Stelle, statt dann vergessen zu werden.
     */
    protected function inlineLink($Excerpt)
    {
        $L = parent::inlineLink($Excerpt);
        if ($L === null) { return $L; }

        $ziel = (string)($L['element']['attributes']['href'] ?? '');
        if (preg_match('#^https?://#i', $ziel)) {
            $L['element']['attributes']['rel'] = 'noopener';
        }

        return $L;
    }

    /**
     * Bilder nur relativ — alles andere wird zu Text.
     *
     * DAS IST DIE ZUSAGE „KEINE FREMDE QUELLE ZUR LAUFZEIT" (CLAUDE.md 4) an
     * der Stelle, an der sie am leichtesten fiele: Ein `![Bild](https://...)`
     * im Handbuch sieht harmlos aus und laedt bei jeder Besucherin von einem
     * fremden Server — auf einer Seite, die man OHNE Anmeldung sieht, also
     * auch von jeder, die sich nur umsieht.
     *
     * DASS SAFEMODE DAS NICHT TUT, IST NACHGEMESSEN und nicht vermutet.
     * Gegenprobe am 17.09.2026, `![B](https://fremd.example/b.png)`:
     *
     *   Parsedown mit SafeMode:  <p><img src="https://fremd.example/b.png" ...>
     *   DokuMarkdown:            <p>!<a href="https://fremd.example/b.png" ...>B</a>
     *
     * Die Bibliothek prueft in SafeMode das SCHEMA (kein `javascript:`), nicht
     * die HERKUNFT. Ein `https:`-Bild ist fuer sie sauber; fuer dieses Projekt
     * ist es der Bruch einer Zusage. Dieser Ueberschreiber ist deshalb keine
     * Verzierung, sondern das Einzige, was zwischen dem Handbuch und einem
     * fremden Server steht.
     *
     * WAS STATTDESSEN ERSCHEINT: Parsedown faellt auf die Link-Regel zurueck,
     * das Ergebnis ist ein `!` als Text und ein normaler Link auf dieselbe
     * Adresse. Geladen wird nichts — wer klickt, geht selbst hin. Das ist
     * gewollt: Der Fehler bleibt sichtbar und damit meldbar, statt still zu
     * verschwinden.
     */
    protected function inlineImage($Excerpt)
    {
        $B = parent::inlineImage($Excerpt);
        if ($B === null) { return $B; }

        $quelle = (string)($B['element']['attributes']['src'] ?? '');
        if ($quelle === ''
            || preg_match('#^[a-z][a-z0-9+.-]*:#i', $quelle)   // Schema: http:, data:, ...
            || str_starts_with($quelle, '//')                   // schemenrelativ
            || str_starts_with($quelle, '/')                    // absolut auf diesem Server
            || str_contains($quelle, '..')) {                   // aus dem Ordner heraus
            return null;
        }

        /* UND JETZT DER PFAD — DIE STELLE, AN DER ZWEI WELTEN AUFEINANDER-
         * TREFFEN, UND DIE MICH EINEN BILDERLAUF GEKOSTET HAT.
         *
         * `doku_pfad()` liest die Markdown-Datei mit PHP aus dem
         * DATEISYSTEM, und dort ist `../docs/` ein voellig normaler Ort.
         * Ein Bild holt aber nicht PHP, sondern DER BROWSER — und der sieht
         * nur, was unterhalb des Dokumentenstamms liegt, also `server/`.
         * `../docs/` ist fuer ihn unerreichbar, und das soll auch so
         * bleiben: Ein Webserver, der eine Ebene ueber seinem Stamm
         * ausliefert, ist ein Fehler und kein Merkmal.
         *
         * Ein `![](bilder/x.png)` im Handbuch loeste der Browser deshalb zu
         * `/bilder/x.png` auf — also `server/bilder/`, wo nichts liegt. Der
         * Bilderlauf meldete am 17.09.2026 dafuer **24 Konsolenfehler**
         * (dreimal je Breite, achtmal), und auf dem Produktivserver waeren
         * es drei kaputte Bilder gewesen, die niemandem auffallen, weil das
         * Handbuch ohne sie noch lesbar ist.
         *
         * BILDER LIEGEN DESHALB IMMER UNTER `server/doku/`. Die
         * Auslieferungskette kopiert `docs/bilder/` dorthin (beide
         * Sync-Jobs). Wer selbst hostet und nur `docs/` neben `server/`
         * legt, bekommt den TEXT (den liest PHP) und keine BILDER (die holt
         * der Browser) — dann fehlt derselbe Kopierschritt, und das steht in
         * `docs/Technik.md`. */
        $B['element']['attributes']['src'] = $this->bildBasis . $quelle;

        return $B;
    }
}

/**
 * Ein Dokument rendern.
 *
 * @return array{html: string, inhalt: array<int, array{stufe:int, text:string, marke:string}>, titel: ?string}|null
 *         `null`, wenn die Datei fehlt.
 */
function doku_seite(string $name): ?array
{
    $pfad = doku_pfad($name);
    if ($pfad === null) { return null; }

    $md = file_get_contents($pfad);
    if ($md === false) { return null; }

    /* DIE ERSTE `#`-ZEILE IST DER TITEL UND WIRD HERAUSGENOMMEN. Die Seite
     * hat ihr `<h1>` aus der Seitenhuelle (so zeichnet es M-P5b-01); ein
     * zweites im Text waere zwei Ueberschriften erster Ordnung auf einer
     * Seite — fuer eine Vorleserin eine kaputte Gliederung. */
    $titel = null;
    if (preg_match('/^#\s+(.+?)\s*$/m', $md, $m, PREG_OFFSET_CAPTURE)) {
        if ($m[0][1] === 0) {
            $titel = $m[1][0];
            $md = substr($md, strlen($m[0][0]));
        }
    }

    $p = new DokuMarkdown();
    $p->setSafeMode(true);       // Zielpruefung an Links und Attributen
    $p->setMarkupEscaped(true);  // rohes HTML wird Text, nicht Markup
    $p->setBreaksEnabled(false); // ein Zeilenumbruch im Quelltext ist keiner

    $html = $p->text($md);

    return ['html' => $html, 'inhalt' => $p->inhalt, 'titel' => $titel];
}
