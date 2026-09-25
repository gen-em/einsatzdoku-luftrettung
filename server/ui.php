<?php
declare(strict_types=1);
/**
 * Gemeinsame Layout-Bausteine: SEITENHUELLE, Topbar, Einsatztage-Leiste,
 * Fusszeile.
 *
 * Voraussetzung fuer alles ausser der Huelle: auth_guard.php ist geladen
 * ($userId, $userEmail, $userName) samt ist_admin() — die eine Rollenpruefung
 * (M1-15).
 *
 * DIESE DATEI HAT AUF OBERSTER EBENE KEINE ABHAENGIGKEIT, und das muss so
 * bleiben: install.php laedt sie VOR der Ersteinrichtung, zu einem Zeitpunkt,
 * an dem es weder config.php noch db.php gibt. Was eine Datenbank oder die
 * Konfiguration braucht, wird deshalb erst INNERHALB der jeweiligen Funktion
 * geladen (so wie ui_days_sidebar() es mit diensttag_lib.php haelt).
 */

/* ---------------------------------------------------------------------------
 * SEITENHUELLE
 *
 * WARUM ES SIE GIBT (P0/A2). Bis Web 7.0.2 baute JEDE Seite ihren Kopf selbst:
 * 28 Bloecke aus Doctype, <html>, <head> und der Eroeffnung des <body>, nahezu
 * gleich und doch uneinheitlich — zwei Schreibweisen des Viewports, zwei
 * Titeltrenner, drei Einrueckungen. Eine Aenderung am Viewport, an den
 * Stylesheets oder ein kuenftiges Mobile-Menue war damit eine 28-fache
 * Aenderung. Jetzt ist sie eine einzige.
 *
 * ENTSCHIEDENE SCHREIBWEISEN (A2 Punkt 3, am Bestand ausgezaehlt):
 *   Viewport      "width=device-width,initial-scale=1" ohne Leerzeichen
 *                 (15 Seiten so, 10 mit Leerzeichen)
 *   Titeltrenner  Gedankenstrich "—" (15 Seiten so, 10 mit "·"). Das Konzept
 *                 hatte "·" vorgeschlagen und die Bestaetigung in der
 *                 Umsetzung verlangt — die Auszaehlung sagt das Gegenteil,
 *                 also gilt der Gedankenstrich.
 *
 * REIHENFOLGE IM KOPF (unveraendert gegenueber dem Bestand):
 *   charset · viewport · Titel · 'kopf' · Leaflet-CSS · style.css · Favicon
 * Leaflet-CSS steht VOR style.css, damit eigene Regeln die des Kartenwerks
 * ueberschreiben, und nur auf Kartenseiten (AK-A2-3).
 *
 * Schluessel von $o:
 *   titel    Pflicht. Der Wortlaut VOR dem Trenner; " — Gen-EM NAdoku" haengt
 *            diese Funktion an. Der Text wird hier maskiert — Aufrufer
 *            uebergeben Klartext, kein Markup.
 *   klasse   Klasse am <body> (z. B. 'anmeldung-body'); fehlt sie, hat das
 *            <body>-Element kein Attribut.
 *   karte    true  -> Leaflet-CSS zusaetzlich einbinden (nur Kartenseiten)
 *   stil     false -> style.css NICHT einbinden. Genau ein Aufrufer: der
 *            Einrichter, der seine Gestaltung im Kopf mitbringt (s. u.).
 *   kopf     Fertiges Markup, das unmittelbar nach dem Titel in den Kopf
 *            gehoert — <noscript>-Weiterleitung, eigenes <style>. Wird NICHT
 *            maskiert; der Aufrufer verantwortet den Inhalt.
 * ------------------------------------------------------------------------- */
function ui_seite_start(array $o): void
{
    /* DIE KOPFZEILEN STEHEN VOR DER ERSTEN AUSGABEZEILE (P5a/AP4, E-P5a-15).
     *
     * Hier und nicht in jeder Seite: Diese Huelle ist die eine Stelle, durch
     * die jede Seite laeuft — dafuer hat P3 gesorgt. Drei Stellen erzeugen
     * eine eigene Huelle (`wartung_lib.php`, `betrieb_schluesselblatt.php`,
     * `apk.php`); die rufen `kopfzeilen_seite()` selbst.
     *
     * `headers_sent()` faengt den Fall ab, dass ein Aufrufer schon etwas
     * ausgegeben hat — dann waere ein `header()` eine Warnung im Protokoll
     * und sonst nichts. */
    require_once __DIR__ . '/kopfzeilen_lib.php';
    kopfzeilen_seite();

    /* Zeilenweise zusammengesetzt statt als Vorlage mit eingestreutem PHP:
       Bedingte Zeilen in einer Vorlage bringen ein Durcheinander aus
       geschluckten Zeilenumbruechen mit sich (PHP frisst den Umbruch direkt
       nach jedem "?>"). So steht hier, was ausgegeben wird, und zwar genau
       einmal je Zeile. */
    $zeilen = [
        '<!doctype html>',
        /* data-webversion traegt den Erkennungswert fuer die Symbolverweise
         * in den Browser: ui_symbol() in PHP und edSymbol() in
         * assets/symbol.js muessen dieselbe Zeichenkette erzeugen, und im
         * Browser gibt es keine Aenderungszeit einer Datei. Steht die
         * Konstante nicht bereit (der Einrichter laeuft vor config.php),
         * bleibt das Attribut leer und die Verweise laufen ohne
         * Erkennungswert — richtig, denn der Einrichter laeuft genau einmal. */
        '<html lang="de" data-webversion="'
            . (defined('WEB_VERSION') ? ui_e(WEB_VERSION) : '') . '">',
        '<head>',
        '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">',
        /* DER VORSATZ „[Staging] " (P5c/AP1, E-P5c-05): Der Reiter im Browser
         * ist oft das Einzige, was man von einer Seite sieht, und zwei Reiter
         * mit derselben Beschriftung sind zwei Anlagen, die man verwechselt. */
        '<title>' . ui_e(ui_umgebung_praefix() . (string)$o['titel']) . ' — '
            . ui_e(ui_instanz_kurz()) . '</title>',
    ];
    if (!empty($o['kopf'])) {
        $zeilen[] = rtrim((string)$o['kopf'], "\n");
    }
    if (!empty($o['karte'])) {
        $zeilen[] = '<link rel="stylesheet" href="' . ui_asset('assets/vendor/leaflet/leaflet.css') . '">';
    }
    if (($o['stil'] ?? true) !== false) {
        $zeilen[] = '<link rel="stylesheet" href="' . ui_asset('assets/style.css') . '">';
    }
    $zeilen[] = ui_favicon();

    /* `assets/api.js` (EdApi) STEHT IM KOPF, und zwar auf JEDER Seite —
     * Schritt 15 AP8b.
     *
     * ES STAND ZUERST IN DER IMMER-LISTE von ui_geruest_ende(), mit dem
     * Kommentar, das trage schon, weil jeder EdApi-Aufruf in einem Zuhoerer
     * stecke. DIESE ANNAHME WAR FALSCH, und ein Gegenleser hat sie mit
     * Zeilennummern widerlegt: Auf `einstellungen.php` und `import.php`
     * steht `ui_geruest_ende()` NACH den Seitenskripten (Z. 4672 bzw. 354),
     * und auf genau diesen beiden laeuft `unlock.js` seinen Sendeweg zur
     * LADEZEIT — `ck()` bzw. `sperrstatus()` fuehren ueber
     * `ensureContentKey()` nach `loeseVormerkung()`. `EdApi` waere dort
     * undefiniert gewesen, und der ReferenceError waere in einen
     * ABSICHTLICH STILLEN catch gefallen: Die KDF-Anhebung haette auf zwei
     * Seiten aufgehoert zu laufen, ohne dass irgendwo etwas erschienen
     * waere.
     *
     * Im Kopf gibt es die Frage nicht mehr. Die Datei haengt an nichts
     * (kein DOM, kein anderes Skript), legt nur `window.EdApi` an und ist
     * klein genug, dass ihr Abruf den Seitenaufbau nicht aufhaelt.
     *
     * WER EINEN WEITEREN BAUSTEIN HIERHER ZIEHT, pruefe beides nach: Haengt
     * er wirklich an nichts, und braucht ihn wirklich jede Seite? Der Kopf
     * ist kein Ablageplatz, sondern die Antwort auf eine Reihenfolgefrage. */
    $zeilen[] = '<script src="' . ui_asset('assets/api.js') . '"></script>';

    /* `assets/format.js` (EdFormat) STEHT AUS DEMSELBEN GRUND HIER
     * (Schritt 15 AP8d). Es setzt `window.EdFormat` und haengt an nichts.
     * Seine Verbraucher sind ueber die Seiten verstreut -- die
     * Einsatztabelle (`missiontable.js`, auf Suche und Zeitraum), die
     * Einsatzansicht, die Startseite, der Export --, und
     * `missiontable.js` liest seine Abhaengigkeiten zur LADEZEIT.
     * Dieselbe Falle also wie bei `api.js`, nur eine Datei weiter; im Kopf
     * gibt es sie nicht.
     *
     * DIE REGEL, nach der beide hier stehen: Wer `window.X` setzt, an
     * nichts haengt und von mehr als einer Seite gebraucht wird, gehoert
     * in den Kopf. Alles andere in die Immer-Liste oder zur Seite. */
    $zeilen[] = '<script src="' . ui_asset('assets/format.js') . '"></script>';
    $zeilen[] = '</head>';

    $klasse = (string)($o['klasse'] ?? '');
    $zeilen[] = '<body' . ($klasse !== '' ? ' class="' . ui_e($klasse) . '"' : '') . '>';

    echo implode("\n", $zeilen), "\n";
}

/**
 * Gegenstueck zu ui_seite_start(): Seitenabschluss.
 *
 * Es ist bewusst wenig: Die Fusszeile hat mit ui_footer() ihren eigenen
 * Baustein, und der steht auf den meisten Seiten INNERHALB des Inhalts — er
 * laesst sich hier nicht mit erledigen, ohne die Seiten umzubauen. Was bleibt,
 * ist der Abschluss selbst und eine Ablage fuer Skripte, die ganz zuletzt
 * kommen.
 *
 * Schluessel von $o:
 *   skripte  Liste von Asset-Pfaden, die als <script src> vor </body> stehen.
 *            Fuer alles Weitere (defer, Modul, Inline-Code) schreibt die Seite
 *            ihr Markup weiterhin selbst — vor dem Aufruf.
 */
function ui_seite_ende(array $o = []): void
{
    $zeilen = [];
    foreach ((array)($o['skripte'] ?? []) as $s) {
        $zeilen[] = '<script src="' . ui_asset((string)$s) . '"></script>';
    }
    $zeilen[] = '</body>';
    $zeilen[] = '</html>';
    echo implode("\n", $zeilen), "\n";
}

/**
 * Adresse einer statischen Datei — mit Erkennungswert, wenn es einen gibt.
 *
 * asset() und favicon_tags() stehen in db.php, und db.php laedt die
 * config.php. Der Einrichter laeuft aber VOR der Ersteinrichtung: Dort gibt es
 * beides noch nicht. Die Huelle darf an dieser Stelle also nichts voraussetzen
 * (benanntes Risiko zu A2). Ohne asset() fehlt nur der Erkennungswert an der
 * Adresse — der Verweis stimmt trotzdem, und der Einrichter laeuft genau
 * einmal, hat also nichts zwischenzuspeichern.
 */
function ui_asset(string $pfad): string
{
    return function_exists('asset') ? asset($pfad) : $pfad;
}

/** Maskierung — e() aus db.php, wo es sie gibt (s. ui_asset()). */
function ui_e(string $s): string
{
    return function_exists('e') ? e($s) : htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Der Kurzname dieser Installation — instanz_kurz(), wo es sie gibt.
 *
 * Dasselbe Muster wie ui_asset(): `install.php` laedt diese Datei VOR der
 * Ersteinrichtung, und dann gibt es weder Datenbank noch `app_state`. Der
 * Rueckfall ist die Vorgabe, nicht ein leerer Titel.
 */
function ui_instanz_kurz(): string
{
    if (function_exists('instanz_kurz')) { return instanz_kurz(); }
    return defined('INSTANZ_KURZ_VORGABE') ? INSTANZ_KURZ_VORGABE : 'Gen-EM NAdoku';
}

/**
 * Das Etikett dieser Anlage (P5c/AP1, E-P5c-05) — `umgebung()`, oder `null`.
 *
 * `umgebung_lib.php` laedt nur `konfig_lib.php`, und die laedt nichts; beide
 * vertragen den Einrichter vor `config.php` (dann gibt es kein Etikett). Das
 * `require_once` steht trotzdem HIER und nicht oben in der Datei: Diese Datei
 * hat auf oberster Ebene keine Abhaengigkeit, und das bleibt so.
 */
function ui_umgebung(): ?array
{
    require_once __DIR__ . '/umgebung_lib.php';
    return umgebung();
}

/** „[Staging] " oder leer — siehe `umgebung_praefix()`. */
function ui_umgebung_praefix(): string
{
    require_once __DIR__ . '/umgebung_lib.php';
    return umgebung_praefix();
}

/** Favicon-Verweise — favicon_tags() aus db.php, wo es sie gibt (s. ui_asset()). */
function ui_favicon(): string
{
    if (function_exists('favicon_tags')) { return favicon_tags(); }
    // Rueckfall ohne config.php: seitenrelativ. Der Einrichter liegt im selben
    // Verzeichnis wie die Anwendung, damit zeigen die Pfade richtig.
    return '<link rel="icon" type="image/png" href="assets/images/favicon_helicopter.png">' . "\n"
         . '<link rel="icon" href="favicon.ico">'
         . '<link rel="apple-touch-icon" href="assets/images/favicon_helicopter.png">';
}

/**
 * SYMBOLE — ein Zeichen, eine Datei, ein Aufruf.
 *
 * WARUM ES SIE GIBT (P3/O1, E-P3-18). Der Bestand trug seine Zeichen an vier
 * verschiedenen Orten: fuenf Inline-SVG mit Pfaddaten mitten im PHP und JS
 * (das Zahnrad zweimal, der Karten-Pin sogar wortgleich in zwei Dateien), rund
 * zwoelf Unicode-Zeichen als Symbol (▸ ▾ ✓ ⚠ ★ ◌ ← + –) und die Emoji 🚁 und
 * 🚑 als Artkennzeichen. Die Emoji waren dabei das Schlimmste: Sie werden je
 * Betriebssystem in anderer Zeichnung, Farbe und Groesse gerendert, lassen
 * sich weder faerben noch auf Kontrast pruefen — und in der Tagesleiste, den
 * Tabellen und der Rettungsmittel-Auswahl waren sie die einzige Artauskunft
 * neben dem Tooltip.
 *
 * Jetzt liegt jedes Zeichen als eigene Datei unter assets/images/symbole/,
 * 24 x 24, Strich 2 px, Farbe ueber currentColor. Grundlage ist Tabler Icons
 * (MIT, Lizenztext liegt daneben); ein Zeichen (Luftlinie) ist ein eigener
 * Entwurf im selben Stil. Die Zuordnung Datei -> Tabler-Name -> Verwendung
 * steht in LIESMICH.md im selben Ordner.
 *
 * EINBINDUNG PER VERWEIS, nicht per Einbetten. Das <use> holt das <g id="i">
 * aus der Datei; der Browser laedt jede Datei genau einmal und benutzt sie
 * beliebig oft. Kein Sprite, kein Bauschritt — die Datei bleibt am PC einzeln
 * zu oeffnen und zu aendern, und genau das war die Anforderung.
 *
 * DIE STRICHATTRIBUTE STEHEN IM STYLESHEET (.symbol), nicht hier: Der Verweis
 * holt das <g>, nicht das <svg> darum, und die Attribute fill/stroke stehen in
 * der Datei am <svg>. Ohne den Ersatz in .symbol malte der Browser schwarze
 * Klumpen. Details im Stylesheet, Abschnitt 3.
 *
 * DER ERKENNUNGSWERT IST WEB_VERSION, nicht die Aenderungszeit der Datei —
 * anders als bei asset(). Grund: edSymbol() in assets/symbol.js muss dieselbe
 * Zeichenkette erzeugen wie diese Funktion, und im Browser gibt es keine
 * Aenderungszeit. WEB_VERSION steigt bei jeder Auslieferung ohnehin (CLAUDE.md
 * Abschnitt 2), damit ist der Zwischenspeicher zuverlaessig erneuert.
 *
 * @param string      $name     Dateiname ohne Endung, z. B. 'haus'
 * @param string      $klassen  zusaetzliche Klassen: 'symbol-gross',
 *                              'symbol-links', 'symbol-gefuellt' …
 * @param string|null $titel    Wenn gesetzt, ist das Symbol fuer Screenreader
 *                              sichtbar und traegt diesen Namen. Ohne Titel
 *                              gilt es als Schmuck (aria-hidden) — richtig
 *                              ueberall dort, wo daneben Text steht.
 */
function ui_symbol(string $name, string $klassen = '', ?string $titel = null): string
{
    $v = defined('WEB_VERSION') ? WEB_VERSION : '';
    $pfad = 'assets/images/symbole/' . $name . '.svg' . ($v !== '' ? '?v=' . $v : '') . '#i';
    $k = 'symbol' . ($klassen !== '' ? ' ' . $klassen : '');

    $a = '<svg class="' . ui_e($k) . '" viewBox="0 0 24 24" focusable="false"';
    $a .= $titel === null
        ? ' aria-hidden="true">'
        : ' role="img"><title>' . ui_e($titel) . '</title>';
    return $a . '<use href="' . ui_e($pfad) . '"></use></svg>';
}

/* ===========================================================================
 * BAUSTEINE (P3/O2)
 *
 * Ab hier steht das Gerüst der Oberfläche und der Vorrat, aus dem jede Seite
 * gebaut wird. Die Regel dazu ist knapp und gilt ohne Ausnahme:
 *
 *   Eine Seite setzt vorhandene Bausteine zusammen und definiert nichts
 *   Eigenes. Ein neuer Baustein wird vorher beschrieben, mit Mockup
 *   vorgelegt, freigegeben und in docs/Design.md aufgenommen (E-P3-06,
 *   CLAUDE.md Abschnitt 9).
 *
 * WARUM DAS HIER STEHT UND NICHT IN DEN SEITEN. Bis Web 8.0.1 baute jede
 * Seite ihre Karten, Zeilen, Knöpfe und Meldungen selbst. Das Ergebnis waren
 * sechs Schaltflächenvarianten für vier Bedeutungen, zwei Farben für dieselbe
 * Handlung („Bearbeiten" orange in der Einsatzansicht, gelb in den
 * Stammdaten) und eine Mindesttrefferfläche, die keine Zeilenaktion erreichte.
 * Nicht aus Nachlässigkeit — sondern weil es keine Stelle gab, an der ein Knopf
 * EINMAL beschrieben ist.
 *
 * DIE JS-ERZEUGER BENUTZEN DIESELBEN KLASSEN. Große Teile der Oberfläche
 * entstehen erst im Browser (missiontable.js, die Reiter der Einstellungen,
 * die Feldliste der Einsatzansicht). Wer hier eine Klasse ändert, ändert sie
 * dort mit; die Vollständigkeitsprüfung meldet jede Klasse, die nur an einer
 * der beiden Stellen vorkommt.
 * ======================================================================== */

/**
 * Merkzettel: Steht auf dieser Seite die Diensttage-Leiste?
 *
 * ui_geruest_start() trägt es ein, ui_geruest_ende() liest es — damit
 * daylist.js nur dort mitkommt, wo es ein Akkordeon zu verkoppeln gibt.
 */
function ui_hat_tagesleiste(?bool $setzen = null): bool
{
    static $ja = false;
    if ($setzen !== null) { $ja = $setzen; }
    return $ja;
}

/**
 * Dasselbe für die Einstellungsleiste — sie braucht `assets/menue.js`
 * (Akkordeonzustand der drei Blöcke).
 */
function ui_hat_menueleiste(?bool $setzen = null): bool
{
    static $ja = false;
    if ($setzen !== null) { $ja = $setzen; }
    return $ja;
}

/** Anzeigename der angemeldeten Person — Name, sonst E-Mail. */
function ui_user_label(): string {
    global $userName, $userEmail;
    return ($userName !== null && $userName !== '') ? $userName : (string)$userEmail;
}

/**
 * Pfad zum Logo für die Kopfleiste (weiße Fassung) bzw. für helle Flächen.
 *
 * Die Wahl je Profil (E-P3-20: Standard / Hubschrauber / Fahrzeug /
 * wechselnd) steht seit Web 9.7.0 in `users.logo_wahl` und wird bei der
 * Anmeldung EINMAL aufgelöst (session_lib.php). Diese Funktion liest nur
 * das Ergebnis — die Vorarbeit aus O1 hat sich bewährt: Der Umbau in O8
 * war eine Zuweisung und keine Suche über 25 Seiten.
 */
function ui_logo(bool $weiss = false): string
{
    /* Seit Web 9.7.0 entscheidet logo_stamm() (session_lib.php) — dieselbe
     * Stelle, die auch favicon_tags() fragt. So können Kopfleiste und
     * Browser-Symbol nicht auseinanderlaufen (E-P3-20). Ohne Sitzung
     * (Anmeldung, Einrichter) liefert sie den Standard. */
    $stamm = function_exists('logo_stamm') ? logo_stamm() : 'gen-em_logo_helicopter';
    return ui_asset('assets/images/' . $stamm . ($weiss ? '_weiss' : '') . '.svg');
}


/**
 * Bildmasse des gewaehlten Logos bei gegebener Hoehe: ['breite' => …, 'hoehe' => …].
 *
 * WARUM DIE BREITE NICHT FEST STEHEN DARF (S3/AP11). Bis Web 12.4.1 stand in
 * der Kopfleiste `width="54" height="34"` — fuer BEIDE Logos. 54:34 ist das
 * Verhaeltnis des Luftlogos; das Bodenlogo ist 420:335 und damit nur 42,6 px
 * breit. Der Browser reservierte also einen Kasten, in den das Bild nicht
 * passt, und rueckte beim Laden nach: ein Layoutsprung, den man nur sieht,
 * wenn man darauf wartet.
 *
 * Die Verhaeltnisse stehen HIER als Zahlen und nicht im Stylesheet: Sie sind
 * eine Eigenschaft der DATEI, nicht der Gestaltung, und `width`/`height` am
 * Bild-Tag sind das einzige, was der Browser VOR dem Laden kennt.
 */
function ui_logo_masse(int $hoehe): array
{
    $stamm = function_exists('logo_stamm') ? logo_stamm() : 'gen-em_logo_helicopter';
    /* Rahmen der SVG, nach dem Beschnitt in S3/AP11 deckungsgleich mit der
     * Zeichnung: Luft 400,16 x 249,81 · Boden 420 x 335. */
    $verhaeltnis = str_contains($stamm, 'nef') ? 420 / 335 : 400.16 / 249.81;
    return ['breite' => (int)round($hoehe * $verhaeltnis), 'hoehe' => $hoehe];
}


/**
 * Artzeichen eines Diensttags — Symbol mit Textalternative.
 *
 * Die EINE Stelle, an der aus `days.kind` ein sichtbares Zeichen wird. Bis
 * Web 8.0.1 stand an vierzehn Stellen dieselbe Zeile mit einem Emoji darin;
 * jetzt steht hier ein Aufruf, und die Zeichnung kommt aus dem Symbolvorrat
 * (E-P3-18).
 *
 * WO KEIN SVG HINEINPASST — in einem <option> etwa —, nimmt man nicht dieses
 * Markup, sondern das WORT aus dt_art_symbol()['text'].
 */
function ui_artzeichen(?string $kind, string $klassen = '', ?string $typ = null): string
{
    require_once __DIR__ . '/diensttag_lib.php';
    /* DER TYP STEHT AN DRITTER STELLE, nicht an zweiter. `$klassen` ist die
     * dokumentierte zweite Stelle (`docs/Design.md` 9.15) — wer den Typ dorthin
     * schoebe, braeche jeden kuenftigen Aufruf mit Klassen, ohne dass etwas
     * meldet. Seit AP4 (Web 16.0.0) uebergeben ihn alle sechs Aufrufer:
     * Leiste, Papierkorb, Diensttag loeschen, Zusammenfuehren, Nachbearbeitung
     * und die Stammdatenliste. NULL bleibt zulaessig und bedeutet „kein Typ
     * bekannt" — dann zeichnet die Betriebsart. */
    $sym = dt_art_symbol($kind, $typ);
    /* OHNE DIE KLASSE `artzeichen` (P3/O11). Sie stammt aus der Zeit, als das
     * Artzeichen ein EMOJI war, und war dessen Korsett:
     * `width:1.4em;text-align:center;font-size:1.05em;cursor:help`. Seit O2
     * kommt das Zeichen aus dem Symbolvorrat (E-P3-18) und bringt seine Groesse
     * selbst mit; im neuen Stylesheet hatte die Klasse folgerichtig keine Regel
     * mehr. Eine Klasse ohne Regel im Markup ist kein Schaden, aber auch keine
     * Auskunft — und die naechste, die sie wieder mit Leben fuellt, baut das
     * Korsett um ein SVG. Auf der Streichliste. */
    return ui_symbol($sym['symbol'], trim($klassen), $sym['text']);
}


/* ---------------------------------------------------------------------------
 * KOPFLEISTE  (.kopf)
 *
 * Mobil: Menüknopf links, Logo und Name in der Mitte, Zahnrad rechts.
 * Ab 1024: Logo, Name und Nutzername links; rechts „Startseite" und „Suche"
 * mit Symbol (aktiv mit orangem Strich) und das Zahnrad.
 *
 * Der Menüpunkt heißt STARTSEITE, nicht „Übersicht" (E-P3-07) — im Suchmenü
 * daneben war „Übersicht" missverständlich. Der Seitentitel bleibt
 * „Tagesübersicht", wie das Handbuch ihn nennt.
 *
 * Die Kopfleiste bleibt voll breit; ihr Inhalt sitzt auf demselben Raster wie
 * Leiste und Inhalt darunter (E-P3-12).
 *
 * $o: aktiv   'start' | 'suche' | 'einstellungen' | ''
 *     menue   false = kein Menüknopf und kein Zahnrad (öffentliche Hülle)
 *     zurueck ['text' => …, 'href' => …] statt der Hauptpunkte (öffentlich)
 * ------------------------------------------------------------------------ */
function ui_kopf(array $o = []): void
{
    $aktiv  = (string)($o['aktiv'] ?? '');
    $menue  = ($o['menue'] ?? true) !== false;
    $zurueck = $o['zurueck'] ?? null;
    /* DIE FARBE DER UMGEBUNG (P5c/AP1, E-P5c-05, -55, -59). Eine Stelle fuer
     * jede Seite mit Kopfleiste. `farbe` ist eine geschlossene Liste, und
     * heute steht darin nur `rot` — ein unbekannter Wert kommt aus
     * `umgebung()` schon als `rot` zurueck und steht auf der Statusseite. */
    $umgebung = ui_umgebung();
    $kopfKlasse = 'kopf' . ($umgebung !== null ? ' kopf-umgebung' : '');
    ?>
<header class="<?= $kopfKlasse ?>">
  <div class="kopf-innen">
    <?php if ($menue): ?>
    <button type="button" class="knopf knopf-symbol kopf-menue" data-schublade="auf"
            aria-expanded="false" aria-controls="leiste" aria-label="Menü öffnen">
      <?= ui_symbol('menu', 'symbol-gross') ?>
    </button>
    <?php endif; ?>

    <a class="kopf-marke" href="index.php">
      <?php $lm = ui_logo_masse(34); ?>
      <img src="<?= ui_e(ui_logo(true)) ?>" alt=""
           width="<?= $lm['breite'] ?>" height="<?= $lm['hoehe'] ?>">
      <span class="kopf-name"><?= ui_e(ui_instanz_kurz()) ?></span>
      <?php if ($menue): ?><span class="kopf-nutzer"><?= ui_e(ui_user_label()) ?></span><?php endif; ?>
    </a>

    <nav class="kopf-punkte" aria-label="Hauptbereiche">
      <?php if ($zurueck !== null): ?>
        <a class="kopf-punkt kopf-zurueck" href="<?= ui_e((string)$zurueck['href']) ?>">
          <?= ui_symbol('zurueck') ?><span><?= ui_e((string)$zurueck['text']) ?></span>
        </a>
      <?php elseif ($menue): ?>
        <a class="kopf-punkt<?= $aktiv === 'start' ? ' aktiv' : '' ?>" href="index.php"
           <?= $aktiv === 'start' ? 'aria-current="page"' : '' ?>>
          <?= ui_symbol('kalender') ?><span>Startseite</span>
        </a>
        <a class="kopf-punkt<?= $aktiv === 'suche' ? ' aktiv' : '' ?>" href="suche.php"
           <?= $aktiv === 'suche' ? 'aria-current="page"' : '' ?>>
          <?= ui_symbol('lupe') ?><span>Suche</span>
        </a>
        <?php /* HILFE LINKS VOM ZAHNRAD (P5b/AP8, M-P5b-01). Auf dem Handy
                 bleibt er stehen — anders als „Startseite" und „Suche", die
                 dort in die Schublade wandern: Ein Fragezeichen ist 24 px
                 breit und der eine Knopf, den jemand sucht, der gerade nicht
                 weiterweiss. Genau dann will man nicht erst ein Menue
                 aufziehen. */ ?>
        <a class="knopf knopf-symbol kopf-hilfe<?= $aktiv === 'hilfe' ? ' aktiv' : '' ?>"
           href="hilfe.php" aria-label="Hilfe und Handbuch"
           <?= $aktiv === 'hilfe' ? 'aria-current="page"' : '' ?>>
          <?= ui_symbol('hilfe', 'symbol-gross') ?>
        </a>
        <a class="knopf knopf-symbol kopf-zahnrad<?= $aktiv === 'einstellungen' ? ' aktiv' : '' ?>"
           href="einstellungen.php" aria-label="Einstellungen"
           <?= $aktiv === 'einstellungen' ? 'aria-current="page"' : '' ?>>
          <?= ui_symbol('zahnrad', 'symbol-gross') ?>
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php }


/* ---------------------------------------------------------------------------
 * SEITENGERÜST  (.rahmen, .leiste, .inhalt)
 *
 * EINE Markup-Fassung für Leiste und Schublade. Unter 1024 px liegt dieselbe
 * `<aside class="leiste">` als Schublade über dem Inhalt, darüber steht sie
 * fest daneben — der Unterschied ist ausschließlich CSS.
 *
 * Das ist die Lehre aus der Vormerkliste von P0 (10.5): „Wird die
 * Seitenleiste zur Schublade, muss der Mechanismus an der KLASSE hängen und
 * nicht an ui_days_sidebar() — sonst bleibt die Suchseite als einzige ohne
 * Mobile-Menü." Genau deshalb kennt dieses Gerüst drei Leisteninhalte und
 * behandelt sie gleich.
 *
 * $o: aktiv    Hauptpunkt in der Kopfleiste ('start' | 'suche' | 'einstellungen')
 *     leiste   'diensttage' | 'einstellungen' | 'filter' | null
 *     tag      Kennung des gewählten Diensttags (nur bei 'diensttage')
 *     zeitraum ['jahr'=>'2026','monat'=>'08'] — markiert Jahres- bzw.
 *              Monatszeile der Leiste (nur bei 'diensttage', E-P3-37)
 *     menue    aktiver Eintrag des Einstellungsmenüs (nur bei 'einstellungen')
 *     lesespalte  true = Inhaltsspalte auf Lesebreite (760 px) begrenzen
 *     titel    Überschrift der Leiste bei 'filter'
 *
 * Bei 'filter' gibt diese Funktion die Leiste NICHT aus: Die Suchseite füllt
 * sie selbst und ruft danach ui_leiste_ende(). Bei allen anderen Werten ist
 * nach diesem Aufruf `<main class="inhalt">` offen.
 * ------------------------------------------------------------------------ */
function ui_geruest_start(array $o = []): void
{
    $leiste = (string)($o['leiste'] ?? '');
    ui_hat_tagesleiste($leiste === 'diensttage');
    ui_hat_menueleiste($leiste === 'einstellungen');
    ui_kopf(['aktiv' => (string)($o['aktiv'] ?? '')]);
    /* LESESPALTE (Web 15.1.0, E-S8-18): Eine Seite mit wenigen Karten und viel
     * Erklärtext liest sich auf 760 px besser als über die volle Breite eines
     * 1920er Bildschirms. Die Regel `.rahmen-lesespalte` gab es längst — sie
     * war nur für Seiten OHNE Leiste gebaut (Anmeldung, Rechtstexte,
     * Wiederherstellung) und über das Gerüst nicht erreichbar. */
    $rahmen = 'rahmen' . (!empty($o['lesespalte']) ? ' rahmen-lesespalte' : '');
    ?>
<div class="schleier" data-schublade="zu" hidden></div>
<div class="<?= ui_e($rahmen) ?>">
  <?php /* tabindex="-1": Beim Öffnen der Schublade fokussiert schublade.js
           die Leiste SELBST, nicht ihr erstes Bedienelement — sonst trüge
           das X beim Öffnen einen Fokusring, den niemand bestellt hat
           (F-P3-V). Per Tab ist die Leiste dadurch nicht erreichbar; ihre
           Einträge sind es. */ ?>
  <?php /* DIE FILTERLEISTE DER SUCHE IST BREITER (E-P3-Anlage G, nachgezogen
           in O12). Sie traegt Auswahlfelder und Zahlenpaare, die
           Diensttage-Leiste nur Zeilen; Anlage G fuehrt sie deshalb mit
           240 px (1024–1199) und 280 px (ab 1200) statt 220/260.

           Die beiden Token dafuer standen seit O1 in :root und wurden von
           NIEMANDEM benutzt — aufgefallen ist das der erzeugten Tokentabelle
           in O12 (F-P3-BC). Gemessen war die Filterleiste 220 bzw. 260 px
           breit, also so breit wie die Tagesliste. */ ?>
  <aside class="leiste<?= $leiste === 'filter' ? ' leiste-filter' : '' ?>"
         id="leiste" aria-label="Bereichsmenü" tabindex="-1">
    <div class="leiste-kopf nur-schublade">
      <button type="button" class="knopf knopf-symbol" data-schublade="zu" aria-label="Menü schließen">
        <?= ui_symbol('schliessen', 'symbol-gross') ?>
      </button>
      <span class="kopf-name"><?= ui_e(ui_instanz_kurz()) ?></span>
    </div>
    <nav class="leiste-haupt nur-schublade" aria-label="Hauptbereiche">
      <a class="eintrag<?= ($o['aktiv'] ?? '') === 'start' ? ' aktiv' : '' ?>" href="index.php">
        <?= ui_symbol('kalender') ?><span class="eintrag-text">Startseite</span>
      </a>
      <a class="eintrag<?= ($o['aktiv'] ?? '') === 'suche' ? ' aktiv' : '' ?>" href="suche.php">
        <?= ui_symbol('lupe') ?><span class="eintrag-text">Suche</span>
      </a>
    </nav>
<?php
    if ($leiste === 'diensttage') {
        ui_leiste_diensttage(isset($o['tag']) ? (int)$o['tag'] : null,
                             (array)($o['zeitraum'] ?? []));
        ui_leiste_ende();
    } elseif ($leiste === 'einstellungen') {
        ui_leiste_einstellungen((string)($o['menue'] ?? ''));
        ui_leiste_ende();
        /* „‹ Einstellungen" über dem Titel jeder Unterseite (E-P3-11,
         * Mockup 07). Nur unter 1024 px sichtbar — am Desktop steht das Menü
         * daneben, und ein Rückweg auf eine Seite, die man sieht, wäre
         * Rauschen. Auf der Übersicht selbst (menue = '') entfällt er. */
        if ((string)($o['menue'] ?? '') !== '') {
            echo '    <a class="rueckweg nur-schublade" href="einstellungen.php">'
               . ui_symbol('winkel', 'symbol-links')
               . "<span>Einstellungen</span></a>\n";
        }
    } elseif ($leiste === '') {
        ui_leiste_ende();
    } else {
        // 'filter' — die Seite füllt die Leiste selbst.
        echo '    <h2 class="leiste-kopfzeile">' . ui_e((string)($o['titel'] ?? 'Filter')) . "</h2>\n";
    }
}

/** Schließt die Leiste und öffnet den Inhalt. Siehe ui_geruest_start(). */
function ui_leiste_ende(): void
{
    echo "  </aside>\n";
    echo '  <main class="inhalt" id="inhalt">' . "\n";
    ui_hinweise();
}



/**
 * Schließt Inhalt und Rahmen, setzt die Fußzeile darunter und lädt die
 * Skripte des Gerüsts.
 *
 * DIE VIER SKRIPTE DES GERÜSTS stehen hier und nicht auf den Seiten: Sie
 * gehören zur Hülle, und eine Seite, die eines davon zu laden vergisst, fällt
 * nicht auf — sie verhält sich nur an einer Stelle anders als alle anderen.
 * Genau so war es bis Web 8.0.1 mit confirm.js, das auf drei Seiten zweimal
 * und auf drei anderen gar nicht eingebunden war.
 *
 * daylist.js kommt nur mit, wo es eine Diensttage-Leiste gibt: Auf
 * Einstellungen, Import, Administration und Wartung sucht es sein Akkordeon,
 * findet nichts und kehrt zurück — eine Anfrage und ein Parse-Durchgang für
 * nichts.
 */
function ui_geruest_ende(array $o = []): void
{
    echo "  </main>\n</div>\n";
    ui_fuss_seite($o);

    /* `api.js` STAND HIER EINEN NACHMITTAG LANG und gehoert nicht hierher —
     * es steht jetzt im <head> (ui_seite_start()). Warum, sagt der Kommentar
     * dort. Kurz: Diese Liste kommt auf zwei Seiten NACH den Seitenskripten,
     * und auf genau diesen beiden ruft `unlock.js` seinen Sendeweg zur
     * LADEZEIT. */
    $skripte = ['assets/symbol.js', 'assets/schublade.js', 'assets/blatt.js',
                'assets/confirm.js'];
    if (ui_hat_tagesleiste()) { $skripte[] = 'assets/daylist.js'; }
    if (ui_hat_menueleiste()) { $skripte[] = 'assets/menue.js'; }
    foreach ($skripte as $s) {
        echo '<script src="' . ui_e(ui_asset($s)) . '"></script>' . "\n";
    }
}


/* ---------------------------------------------------------------------------
 * LEISTENINHALT: DIENSTTAGE
 *
 * SIE LISTET DIENSTTAGE, NICHT KALENDERTAGE (E9, Web 6.0.0): Jeder Einsatz
 * hängt an einer Zeile in `days`, und diese Zeile IST der Eintrag. Zwei
 * Dienste an einem Kalendertag stehen als zwei Zeilen untereinander;
 * auseinandergehalten werden sie durch die Uhrzeit des Dienstbeginns — aber
 * nur dann, denn im Regelfall kostet sie nur Breite.
 *
 * DREI ÄNDERUNGEN GEGENÜBER DEM BESTAND (E-P3-09):
 *
 *  1  Die ganze Zeile klappt das Akkordeon, nicht nur das Dreieck. Bisher war
 *     der TEXT der Link auf die Jahres-/Monatsübersicht und nur das Dreieck
 *     der Schalter — auf einem Touchgerät nicht zu unterscheiden. Jetzt
 *     klappt die Zeile, und der Weg in die Übersicht ist ein eigenes Symbol
 *     rechts (Balken).
 *  2  Der Winkel steht in Sand: Er ist Mechanik, keine Botschaft.
 *  3  Lange Rettungsmittelnamen werden mit Ellipse abgeschnitten; der volle
 *     Name steht im Tooltip und im Seitentitel. Im Band 1024 bis 1199 px ist
 *     die Leiste 220 px schmal: Dort steht nur noch, was auch hineinpasst —
 *     ein gesetzter KURZNAME (Klasse `kurz`), sonst nichts. Unterhalb von
 *     1024 px liegt die Leiste als Schublade und ist mit 320 px wieder breit
 *     genug für den vollen Namen; sie zeigt ihn seit jeher und behält ihn
 *     (S9/AP4a, Freigabe M-S9-08 Variante 2). Das Artzeichen bleibt in jeder
 *     Breite.
 *
 * Das Artzeichen kommt aus dem Symbolvorrat statt als Emoji (E-P3-18) — es
 * lässt sich damit färben und auf Kontrast prüfen, und es sieht auf jedem
 * Betriebssystem gleich aus.
 * ------------------------------------------------------------------------ */
function ui_leiste_diensttage(?int $currentDayId, array $zeitraum = []): void
{
    global $userId;
    require_once __DIR__ . '/diensttag_lib.php';
    $tage = dt_liste($userId, 500);

    $monatsnamen = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $baum = [];
    foreach ($tage as $t) {
        $d = (string)$t['day'];
        $baum[substr($d, 0, 4)][substr($d, 5, 2)][] = $t;
    }

    // Welches Jahr und welcher Monat sollen offen sein? Der gewählte Diensttag
    // hat Vorrang, sonst der jüngste vorhandene.
    $aktuellesDatum = null;
    foreach ($tage as $t) {
        if ($currentDayId !== null && (int)$t['id'] === $currentDayId) {
            $aktuellesDatum = (string)$t['day'];
            break;
        }
    }
    $offenesJahr = null; $offenerMonat = null;
    if ($aktuellesDatum !== null) {
        $offenesJahr  = substr($aktuellesDatum, 0, 4);
        $offenerMonat = substr($aktuellesDatum, 5, 2);
    } elseif ($tage) {
        $offenesJahr  = substr((string)$tage[0]['day'], 0, 4);
        $offenerMonat = substr((string)$tage[0]['day'], 5, 2);
    }

    /* DER ANGEZEIGTE ZEITRAUM WIRD MARKIERT (E-P3-37). Wer auf der
       Jahres- oder Monatsübersicht steht, sah in der Leiste bisher nichts
       davon — der aktive Eintrag war stets ein Diensttag, und den gibt es
       dort nicht. Jetzt trägt die Jahres- bzw. Monatszeile die Markierung,
       und der Zweig wird dafür aufgeklappt. */
    $aktivesJahr  = isset($zeitraum['jahr'])  ? (string)$zeitraum['jahr']  : '';
    $aktiverMonat = isset($zeitraum['monat']) ? (string)$zeitraum['monat'] : '';
    if ($aktivesJahr !== '') {
        $offenesJahr = $aktivesJahr;
        if ($aktiverMonat !== '') { $offenerMonat = $aktiverMonat; }
    }
    ?>
    <h2 class="leiste-kopfzeile">Diensttage</h2>
    <div class="leiste-liste">
      <?php if (!$baum): ?><p class="leiste-leer">noch keine</p><?php endif; ?>
      <?php foreach ($baum as $jahr => $monate):
          /* PHP macht aus numerischen Array-Schlüsseln Integer ("2026" -> 2026,
             "07" bleibt String). Deshalb überall ausdrücklich nach String
             wandeln — sonst bricht ui_e() unter strict_types ab und
             Monatsvergleiche schlagen ab Oktober fehl. */
          $jahrS = (string)$jahr; ?>
        <?php /* Aktiv ist die JAHRESzeile nur bei der Jahresübersicht — steht
                 ein Monat an, trägt der Monat die Markierung. */
              $jahrAktiv = $aktivesJahr === $jahrS && $aktiverMonat === ''; ?>
        <details class="akkordeon<?= $jahrAktiv ? ' aktiv' : '' ?>"
                 <?= $jahrS === $offenesJahr ? 'open' : '' ?>>
          <?php /* Der Balken-Link steht IM summary: Als Kind des <details>
                   wäre er an jeder zugeklappten Zeile unsichtbar — der Inhalt
                   eines geschlossenen <details> wird nicht gerendert
                   (F-P3-R). daylist.js fängt den Klick ab, damit er nicht
                   zusätzlich auf- und zuklappt. */ ?>
          <summary class="akkordeon-zeile">
            <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
            <span class="akkordeon-text"><?= ui_e($jahrS) ?></span>
            <a class="akkordeon-uebersicht" href="zeitraum.php?y=<?= ui_e($jahrS) ?>"
               <?= $jahrAktiv ? 'aria-current="page"' : '' ?>
               aria-label="Jahresübersicht <?= ui_e($jahrS) ?>" title="Jahresübersicht">
              <?= ui_symbol('balken') ?>
            </a>
          </summary>
          <div class="akkordeon-inhalt">
          <?php foreach ($monate as $monat => $monatsTage):
              $monatS = str_pad((string)$monat, 2, '0', STR_PAD_LEFT); ?>
            <?php $monatAktiv = $aktivesJahr === $jahrS && $aktiverMonat === $monatS; ?>
            <details class="akkordeon akkordeon-monat<?= $monatAktiv ? ' aktiv' : '' ?>"
                     <?= ($jahrS === $offenesJahr && $monatS === $offenerMonat) ? 'open' : '' ?>>
              <summary class="akkordeon-zeile">
                <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
                <span class="akkordeon-text"><?= ui_e($monatsnamen[(int)$monatS]) ?></span>
                <a class="akkordeon-uebersicht"
                   href="zeitraum.php?y=<?= ui_e($jahrS) ?>&amp;m=<?= ui_e($monatS) ?>"
                   <?= $monatAktiv ? 'aria-current="page"' : '' ?>
                   aria-label="Monatsübersicht <?= ui_e($monatsnamen[(int)$monatS]) ?>"
                   title="Monatsübersicht"><?= ui_symbol('balken') ?></a>
              </summary>
              <div class="akkordeon-inhalt">
              <?php foreach ($monatsTage as $t):
                  $kind = $t['kind'] === null ? null : (string)$t['kind'];
                  $typ  = $t['vehicle_typ'] === null ? null : (string)$t['vehicle_typ'];
                  $sym  = dt_art_symbol($kind, $typ);
                  /* DIE LEISTE ZEIGT DEN KURZNAMEN (Nr. 69, E-S9-09): Sie ist
                     die schmalste Stelle der Anwendung, und „C1" statt
                     „Christoph 1" ist genau dafuer gedacht. Der TITEL nennt
                     weiterhin die volle Bezeichnung — wer den Kurznamen nicht
                     zuordnen kann, findet sie im Tooltip. */
                  $name = dt_rm_kurz($t);
                  $voll = trim((string)($t['vehicle_name'] ?? ''));
                  /* NUR EIN ECHTER KURZNAME TRAEGT DIE KLASSE. `dt_rm_kurz()`
                     faellt auf die volle Bezeichnung zurueck, sein Ergebnis
                     sagt also nicht, ob ein Kurzname gesetzt ist — dafuer die
                     Spalte selbst lesen. Sonst stuende im schmalen Band genau
                     das wieder da, was die Regel dort ausblendet: ein voller
                     Name als Ellipse.
                     UND NICHT AM MEHRFACHEN TAG. Teilen sich zwei Diensttage
                     ein Datum, traegt die Zeile Datum UND Uhrzeit
                     („28.03.2026 06:30" statt „28.03.2026"); `.eintrag-text`
                     schrumpft nicht (`flex:1 0 auto`), also geht der Platz
                     vom Nebentext ab. Gemessen bei 1024, 1100 und 1199 px:
                     3 px fuer einen Kurznamen, der 55 braucht — eine Ellipse
                     ohne Buchstaben. Die ist schlechter als kein Nebentext,
                     also bleibt er dort aus, wie vor S9/AP4a. */
                  $mehrfach = (bool)$t['mehrfach'];
                  $hatKurz = !$mehrfach
                          && trim((string)($t['vehicle_kurz'] ?? '')) !== '';
                  $titel = $voll !== '' ? $voll . ' — ' . $sym['text'] : $sym['text'];
                  $ist = (int)$t['id'] === $currentDayId; ?>
                <a class="eintrag<?= $ist ? ' aktiv' : '' ?>"
                   href="index.php?d=<?= (int)$t['id'] ?>"
                   <?= $ist ? 'aria-current="page"' : '' ?> title="<?= ui_e($titel) ?>">
                  <?= ui_artzeichen($kind, '', $typ) ?>
                  <span class="eintrag-text"><?= ui_e(dt_lesbar($t, $mehrfach)) ?></span>
                  <?php if ($name !== ''): ?>
                    <span class="eintrag-neben<?= $hatKurz ? ' kurz' : '' ?>"><?= ui_e($name) ?></span>
                  <?php else: ?>
                    <span class="eintrag-neben">—</span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
              </div>
            </details>
          <?php endforeach; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
    <?php
      require_once __DIR__ . '/trash_lib.php';
      $trashLeer = !trash_list_days($userId) && !trash_list_missions($userId);
      require_once __DIR__ . '/nachbearbeitung_lib.php';
      $nbOffen = nb_offen_gesamt($userId);
    ?>
    <div class="leiste-fuss">
      <?php /* Die Nachbearbeitung erscheint NUR, solange etwas offen ist (E24,
               A12, bestätigt in E-P3-09). Ein dauerhafter Eintrag für eine
               einmalige Aufgabe wäre genau der Hinweis, den man nicht
               loswird. */ ?>
      <?php if ($nbOffen > 0): ?>
        <a class="eintrag eintrag-offen" href="nachbearbeitung.php">
          <?= ui_symbol('warnung') ?>
          <span class="eintrag-text">Zuordnung offen</span>
          <span class="zaehler"><?= (int)$nbOffen ?></span>
        </a>
      <?php endif; ?>
      <a class="eintrag eintrag-anlegen" href="diensttag_neu.php">
        <?= ui_symbol('plus') ?><span class="eintrag-text">Diensttag anlegen</span>
      </a>
      <a class="eintrag eintrag-leise" href="papierkorb.php"
         title="<?= $trashLeer ? 'Papierkorb ist leer' : 'Papierkorb' ?>">
        <?= ui_symbol('korb') ?><span class="eintrag-text">Papierkorb</span>
      </a>
    </div>
<?php }


/* ---------------------------------------------------------------------------
 * DAS MENÜ DER EINSTELLUNGEN — EINE QUELLE (S8/AP5, E-S8-04, löst B-S8-01)
 *
 * ES GAB DIESE LISTE ZWEIMAL. `ui_leiste_einstellungen()` führte sie für die
 * Seitenleiste, `ui_einstellungen_uebersicht()` noch einmal für die
 * Übersichtsseite — dieselben Einträge, dieselben Symbole, zwei Stellen. Sie
 * sind auseinandergelaufen, und zwar messbar: Bis Web 15.3.0 stand
 * „Stammdaten systemweit" in beiden, „Komplett-Backup" nur in einer, und die
 * Reihenfolge unterschied sich. Wer einen Punkt hinzufügte, fügte ihn an
 * einer Stelle hinzu und merkte es nie.
 *
 * `ui_einstellungen_punkte()` ist jetzt die eine Quelle. Beide Darstellungen
 * lesen daraus; ein neuer Punkt kostet eine Zeile.
 *
 * DREI BLÖCKE NACH ROLLE (E-S8-04, Zielbild 5.1):
 *
 *   Einstellungen   alle             das eigene Konto und seine Daten
 *   Verwaltung      Admin +          andere Konten und was die Anlage zeigt
 *   Betrieb         BetreiberIn      der Server selbst
 *
 * Die Reihenfolge ist die der Zuständigkeit, nicht die der Häufigkeit: Wer
 * den dritten Block sieht, sieht auch die ersten beiden, und wer nur den
 * ersten sieht, soll nicht raten müssen, ob es weitere gibt.
 *
 * „STAMMDATEN SYSTEMWEIT" GIBT ES NICHT MEHR (S9/AP5b, Rahmenplan R39).
 * Erst nahm E-S8-14 der Seite den Menüpunkt — sie wurde einmal bei der
 * Einrichtung gepflegt und danach jahrelang nicht, und ein Menüpunkt, den man
 * einmal benutzt, kostet siebzehn Mal Platz. Seit S9/AP5b ist die Seite
 * ersatzlos gestrichen: Es gibt eine Installation, dort steht kein zentraler
 * Standort mehr, und es soll keinen neuen geben können. Standorte pflegt jedes
 * Konto selbst unter „Standorte". Wer hier einen Eintrag vermisst, sucht eine
 * Seite, die es nicht mehr gibt — nicht einen vergessenen Menüpunkt.
 * ------------------------------------------------------------------------ */

/**
 * Die Menüpunkte der Einstellungen, nach Blöcken.
 *
 * Rückgabe: Liste von Blöcken, je
 *   ['schluessel' => 'einstellungen'|'verwaltung'|'betrieb',
 *    'titel' => string,          // '' beim ersten Block: er braucht keine Überschrift
 *    'punkte' => [ ['key','href','text','symbol','zaehler'] … ] ]
 *
 * `zaehler` ist null oder ['n' => int, 'ton' => 'rot'|'orange'|'neutral'] —
 * die Zahl am Menüpunkt (S8/AP5, Konzept (3)). Sie kommt aus
 * `status_lib.php` und steht nur da, wo sie über null steht; welche vier
 * Punkte eine tragen und warum, steht dort.
 *
 * WER WAS SIEHT, entscheidet diese Funktion und niemand sonst — die Wächter
 * der Seiten prüfen es ein zweites Mal (`require_admin()`,
 * `require_betreiberin()`). Ein Menü, das mehr zeigt als erreichbar ist,
 * führt ins 403; eines, das weniger zeigt, verschweigt eine Funktion.
 */
/**
 * Die Zahl an einem Menüpunkt — oder nichts (S8/AP5).
 *
 * `$z` ist ['n' => int, 'ton' => 'rot'|'orange'|'neutral'] oder null.
 * Sie steht NUR, wenn es etwas zu tun gibt: Eine „0" am Menüpunkt ist keine
 * Auskunft, sondern eine Verzierung, und sie nimmt dem Fall, in dem wirklich
 * etwas ansteht, die Aufmerksamkeit.
 *
 * `aria-label` statt der nackten Zahl: Ein Vorleseprogramm sagt sonst
 * „Status 3" und lässt offen, was die Drei zählt.
 */
function ui_zaehler(?array $z): string
{
    if ($z === null || (int)$z['n'] < 1) { return ''; }
    $ton = (string)($z['ton'] ?? 'rot');
    $wort = $ton === 'neutral' ? 'ausstehend' : 'offen';
    return '<span class="zaehler' . ($ton === 'rot' ? '' : ' zaehler-' . ui_e($ton)) . '"'
         . ' aria-label="' . (int)$z['n'] . ' ' . $wort . '">' . (int)$z['n'] . '</span>';
}


function ui_einstellungen_punkte(): array
{
    $bloecke = [[
        'schluessel' => 'einstellungen',
        'titel'      => '',
        'punkte'     => [
            ['profil',         'einstellungen.php?t=profil',         'Profil',          'profil'],
            /* GERÄTE VOR DEN STAMMDATEN (Zielbild 5.1): Das Koppeln ist der
             * erste Schritt jeder neuen NutzerIn; Standorte und
             * Rettungsmittel werden einmal gepflegt. */
            ['geraete',        'einstellungen.php?t=geraete',        'Geräte',          'uhr'],
            /* NUR NOCH EIN PUNKT FUER DIE STAMMDATEN (S9/AP5, PS-12).
             * „Rettungsmittel" stand hier seit Web 7.0.0 daneben; der Schnitt
             * nach Taetigkeit hat sich nicht bewaehrt, weil beide Reiter
             * DENSELBEN Bestand luden und man zwischen ihnen hin und her
             * ging, um einen Standort einzurichten. Jetzt fuehrt „Standorte"
             * auf die Liste und die Liste auf je eine Standortseite, die
             * alles traegt, was an diesem Standort haengt. Der alte Reiter
             * bleibt als Weiche in `einstellungen.php` erreichbar. */
            ['standorte',      'einstellungen.php?t=standorte',      'Standorte',       'standort'],
            ['backup',         'einstellungen.php?t=backup',         'Backup',          'sicherung'],
            ['import',         'import.php',                         'Import / Export', 'tausch'],
        ],
    ]];

    /* DIE ZAEHLER KOSTEN NUR, WO SIE STEHEN. `status_lib.php` wird erst
     * geladen, wenn die Rolle den Block ueberhaupt sieht — `install.php`
     * laedt `ui.php` ohne Datenbank, und ein `require` auf oberster Ebene
     * haette den Einrichter mitgerissen. */
    $zaehler = [];
    if (function_exists('ist_admin') && ist_admin()) {
        require_once __DIR__ . '/status_lib.php';
        $zaehler = menue_zaehler_konto();
        if (function_exists('ist_betreiberin') && ist_betreiberin()) {
            $zaehler += menue_zaehler_betrieb();
        }
    }
    $z = static fn(string $key): ?array => $zaehler[$key] ?? null;

    if (function_exists('ist_admin') && ist_admin()) {
        $bloecke[] = [
            'schluessel' => 'verwaltung',
            'titel'      => 'Verwaltung',
            'punkte'     => [
                ['admin',              'admin_users.php',        'NutzerInnen',   'gruppe'],
                ['admin_sicherungen',  'admin_sicherungen.php',  'Konto-Backups', 'sicherung',
                                                                  $z('admin_sicherungen')],
                /* `haus` fuer „Installation" (Mockup 13, freigegeben
                 * 05.09.2026), und seit P5c/AP9 wieder eine eigene Seite
                 * „Rechtstexte" mit dem Zeichen, das seit P3 im Vorrat lag
                 * (E-P5c-28): zwischen Installation und Demo-Konto. */
                ['admin_installation', 'admin_installation.php', 'Installation',  'haus'],
                ['admin_rechtstexte',  'admin_rechtstexte.php',  'Rechtstexte',   'rechtstexte'],
                ['admin_demo',         'admin_demo.php',         'Demo-Konto',    'kolben'],
                /* PROTOKOLL UNTER VERWALTUNG, nicht unter Betrieb (E-P5c-10,
                 * R74 (1)): Der Admin sieht vier seiner Reiter, und ein
                 * Admin sieht Betrieb nicht. Menueeintrag und Zeichen kommen
                 * mit der Seite (F-P5c-47) — ohne Eintrag waere „keine
                 * Unterpunkte in der Leiste" gruen, ohne gemessen zu haben. */
                ['admin_protokoll',    'admin_protokoll.php',    'Protokoll',     'protokoll'],
            ],
        ];
    } elseif (function_exists('ist_support') && ist_support()) {
        /* DER SUPPORT (P5c/AP4, E-P5c-14): unter Verwaltung genau die zwei
         * Seiten, die er betritt — NutzerInnen (nur Konten der Rolle `user`)
         * und Protokoll (Verwaltung und E-Mail). Keine Zaehler: Sie zaehlen
         * Konto-Backups, und die sind nicht seine Sache. */
        $bloecke[] = [
            'schluessel' => 'verwaltung',
            'titel'      => 'Verwaltung',
            'punkte'     => [
                ['admin',           'admin_users.php',     'NutzerInnen', 'gruppe'],
                ['admin_protokoll', 'admin_protokoll.php', 'Protokoll',   'protokoll'],
            ],
        ];
    }

    if (function_exists('ist_betreiberin') && ist_betreiberin()) {
        $bloecke[] = [
            'schluessel' => 'betrieb',
            'titel'      => 'Betrieb',
            'punkte'     => [
                /* FÜNF NEUE ZEICHEN (Mockup 13, freigegeben 05.09.2026).
                 * AP3 hatte für die neuen Betriebsseiten aus dem Vorrat
                 * geliehen — dabei entstanden vier Doppelbelegungen, und in
                 * einer Leiste mit siebzehn Einträgen untereinander ist das
                 * keine Sparsamkeit mehr, sondern eine Verwechslungsgefahr.
                 * Alle fünf sind unveränderte Tabler Icons (MIT), wie die
                 * übrigen 44. Der Zylinder (`datenbank`) bleibt dem
                 * Komplett-Backup: dort wird wirklich die Datenbank
                 * gesichert. */
                ['betrieb_status',    'betrieb_status.php',    'Status',              'status',
                                                                      $z('betrieb_status')],
                ['betrieb_statistik', 'betrieb_statistik.php', 'Statistik',           'balken'],
                ['betrieb_updates',   'betrieb_updates.php',   'Updates',             'aktualisieren',
                                                                      $z('betrieb_updates')],
                ['betrieb_jobs',      'betrieb_jobs.php',      'Hintergrundjobs',     'uhrzeit',
                                                                      $z('betrieb_jobs')],
                ['betrieb_server',    'betrieb_server.php',    'Servereinstellungen', 'server'],
                ['admin_komplettsicherung', 'admin_komplettsicherung.php',
                                                               'Komplett-Backup',     'datenbank'],
                ['admin_sicherungsziele',   'admin_sicherungsziele.php',
                                                               'Backup-Ziele',        'ziel-fern'],
            ],
        ];
    }

    return $bloecke;
}

/**
 * Die Seitenleiste des Einstellungsbereichs.
 *
 * Sie zeigt dieselben Punkte wie die Übersichtsseite, in derselben
 * Reihenfolge, mit denselben Symbolen — beide lesen aus
 * `ui_einstellungen_punkte()`.
 *
 * FETTDRUCK NUR FÜR DEN AKTIVEN EINTRAG (Backlog Nr. 75, E-S8-07). Bis Web
 * 15.3.0 waren in der Administration ALLE Einträge fett, weil `.leiste-liste`
 * dort eine eigene Regel trug; der aktive war damit nicht mehr zu erkennen.
 *
 * DIE BLÖCKE KLAPPEN (E-S8-07, Konzept AP5 (2)). Für eine BetreiberIn stehen
 * siebzehn Einträge untereinander, und das passt in kein übliches
 * Browserfenster: Gemessen am 05.09.2026 bei 1280 × 900 waren **14 von 17**
 * ohne Rollen erreichbar, bei 720 px Fensterhöhe noch **10 von 17** — die
 * Liste ist 883 px hoch, die Leiste bot 603. Wer „Backup-Ziele" sucht, sieht
 * nicht, dass es den Eintrag gibt.
 *
 * Gebaut aus dem vorhandenen Akkordeon-Baustein (`.akkordeon-zeile`,
 * `-winkel`, `-inhalt`) — demselben, den die Diensttage-Leiste benutzt.
 * SEIT P5c/AP9 STEHT DER WINKEL RECHTS (Option 1 „Linie", E-P5c-29, Nr. 244):
 * Bis Web 21.0.0 stand er links, damit beide Leisten denselben Griff haben
 * (E-S8-07) — und genau dort, wo die Einträge ihr Symbol tragen, so dass sich
 * die Überschrift wie ein Eintrag las. Das Markup ist unverändert; die
 * Regeln an `.leiste-gruppe` setzen Winkel, Schriftstufe, Farbe und
 * Trennlinie. Die Diensttage-Leiste trägt die Klasse nicht und bleibt, wie
 * sie war.
 *
 * OFFEN IST: „Einstellungen" und der Block der aktiven Seite — in JEDER
 * Breite. Das Konzept sah ab 1024 px alle Blöcke offen vor; damit blieb es
 * bei 1280 × 900 bei 14 von 17 erreichbaren Einträgen, also bei genau dem
 * Zustand, gegen den das Akkordeon gebaut wurde. So waren es 17 von 17.
 *
 * NACHGEMESSEN IN P5c/AP9 (F-P5c-157), mit 18 Einträgen: bei 1280 × 900 auf
 * 13 von 14 Seiten alle erreichbar (die Servereinstellungen mit acht
 * Sprungmarken 16), bei 1280 × 720 auf 6 von 14. Seither fallen die
 * Sprungmarken unter 800 px Fensterhöhe weg (E-P5c-131, style.css) — dann
 * 14 von 14. Gezählt wird ein Eintrag, wenn er im Bild steht oder der Kopf
 * seiner zugeklappten Gruppe; nach der Box allein zu fragen zählt die Einträge
 * zugeklappter Gruppen mit, denn Chromium gibt ihnen eine.
 */
function ui_leiste_einstellungen(string $aktiv): void
{
    $bloecke = ui_einstellungen_punkte();
    ?>
    <div class="leiste-liste" data-menue>
      <?php foreach ($bloecke as $b): ?>
        <?php
        /* JEDER BLOCK IST EIN <details>, UND DER SERVER RENDERT DIE VORGABE:
         * „Einstellungen" ist offen, dazu der Block der aktiven Seite; die
         * uebrigen sind zu.
         *
         * DIESELBE VORGABE IN JEDER BREITE. Das Konzept sah ab 1024 px alle
         * Bloecke offen vor; gemessen loest das den Grund fuer das Akkordeon
         * nicht: Bei 1280 x 900 blieben mit allen offenen Bloecken 14 von 17
         * Eintraegen erreichbar — genau der Zustand, gegen den das Akkordeon
         * gebaut wurde. Mit dieser Vorgabe waren es 17 von 17 (bei 17 Einträgen;
         * nachgemessen P5c/AP9, siehe Kopf). Entschieden am
         * 05.09.2026 auf Nachfrage; die Abweichung steht im Konzept.
         *
         * WARUM PHP UND NICHT DAS SKRIPT. Der Serverzustand ist damit schon
         * der Zielzustand — in jeder Breite, ohne Skript. `menue.js` legt nur
         * noch darueber, was in dieser Sitzung von Hand geaendert wurde. Ein
         * Skript, das die Vorgabe selbst herstellt, laesst am Schreibtisch
         * bei jedem Seitenaufruf kurz den anderen Zustand aufblitzen.
         *
         * `data-gruppe` ist der Schluessel im sessionStorage. */
        $titel  = $b['titel'] !== '' ? $b['titel'] : 'Einstellungen';
        $hatAkt = false;
        foreach ($b['punkte'] as $pk) { if ($aktiv === $pk[0]) { $hatAkt = true; } }
        $offen  = $hatAkt || $b['schluessel'] === 'einstellungen';
        ?>
        <details class="akkordeon leiste-gruppe"<?= $offen ? ' open' : '' ?>
                 data-gruppe="<?= ui_e($b['schluessel']) ?>">
          <summary class="akkordeon-zeile">
            <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
            <span class="akkordeon-text"><?= ui_e($titel) ?><span
              class="gruppen-zahl" aria-hidden="true"> · <?= count($b['punkte']) ?></span></span>
          </summary>
          <div class="akkordeon-inhalt">
            <?php foreach ($b['punkte'] as $punkt): ?>
              <?php [$key, $href, $text, $sym] = $punkt; $zz = $punkt[4] ?? null; ?>
              <a class="eintrag<?= $aktiv === $key ? ' aktiv' : '' ?>" href="<?= ui_e($href) ?>"
                 <?= $aktiv === $key ? 'aria-current="page"' : '' ?>>
                <?= ui_symbol($sym) ?><span class="eintrag-text"><?= ui_e($text) ?></span>
                <?= ui_zaehler($zz) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
    <div class="leiste-fuss">
      <a class="eintrag eintrag-leise" href="logout.php"
         data-confirm="Wirklich abmelden?" data-confirm-ok="Abmelden"
         data-confirm-tone="normal">
        <?= ui_symbol('abmelden') ?><span class="eintrag-text">Abmelden</span>
      </a>
    </div>
<?php }


/**
 * Einstellungs-Übersicht — die Eingangsseite des Bereichs (E-P3-11).
 *
 * Sie führt dieselben Punkte wie die Leiste, aber als Liste im Inhalt: Symbol,
 * Text, Winkel. Auf dem Handy ist sie der einzige Weg, der zeigt, WAS es
 * gibt — dort ist die Leiste eine Schublade, und ein Zahnrad, das ungefragt
 * auf „Profil" landet, verschweigt die übrigen sechzehn Punkte.
 *
 * Jeder Bereich ist eine Bereichskarte (seit P5c/AP9). „Abmelden" steht
 * getrennt am Ende, darunter nur der Name der angemeldeten Person.
 */
function ui_einstellungen_uebersicht(): void
{
    $bloecke = ui_einstellungen_punkte();

    ui_seite_start(['titel' => 'Einstellungen']);
    ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => '']);
    ui_titelzeile(['titel' => 'Einstellungen', 'unter' => ui_e(ui_user_label())]);

    /* AM SCHREIBTISCH DREI SPALTEN (Konzept AP5 (6)). Gestapelt sind es für
     * eine BetreiberIn drei Karten mit achtzehn Zeilen — anderthalb
     * Bildschirme, auf denen nur die erste Karte ohne Rollen zu sehen ist.
     * Nebeneinander passt der ganze Bereich auf einen Blick. Das Raster
     * füllt sich nach Rolle von selbst: eine Spalte für eine NutzerIn, zwei
     * für eine Admin, drei für eine BetreiberIn. */
    echo '  <div class="uebersicht-raster">' . "\n";
    foreach ($bloecke as $b) {
        /* JE BEREICH EINE BEREICHSKARTE (P5c/AP9, E-P5c-29, Mockup M-P5c-01c).
         * Bis Web 21.0.0 stand der Bereichsname als gesperrte Versalzeile
         * ÜBER einer titellosen Karte (`.uebersicht-block`, Mockup 07), und
         * der erste Block trug sie nur nebeneinander — gestapelt hätte sie
         * die Seitenüberschrift wiederholt. Jetzt ist der Name der
         * Kartentitel, mit Zeichen und Zahl der Einträge, mittig auf Rauch;
         * die Sonderregel für den ersten Block entfällt, weil ein Kartentitel
         * etwas anderes ist als eine zweite Überschrift darüber: Er benennt
         * die Karte, nicht die Seite.
         *
         * DIE ZEICHEN gehören zur Freigabe: `profil` (Einstellungen — ich),
         * `gruppe` (Verwaltung — die Konten), `server` (Betrieb — die Anlage).
         * Sie doppeln je einen Eintrag darunter; das ist in Kauf genommen.
         *
         * DIE KARTE IST SELBST DAS KIND DES RASTERS. Bis Web 21.0.0 stand um
         * Überschrift und Karte ein Behälter `.uebersicht-gruppe`; ohne die
         * Überschrift hielt er nur noch die Karte und trug keine Regel mehr. */
        $zeichen = ['einstellungen' => 'profil', 'verwaltung' => 'gruppe',
                    'betrieb' => 'server'][$b['schluessel']] ?? 'zahnrad';
        ui_karte_start(['titel' => $b['titel'] !== '' ? $b['titel'] : 'Einstellungen',
                        'zahl' => (string)count($b['punkte']),
                        'klasse' => 'karte-bereich', 'symbol' => $zeichen]);
        foreach ($b['punkte'] as $punkt) {
            [$key, $href, $text, $sym] = $punkt;
            echo '    <a class="uebersicht-zeile" href="' . ui_e($href) . '">'
               . ui_symbol($sym, 'symbol-gross')
               . '<span class="uebersicht-text">' . ui_e($text) . '</span>'
               . ui_zaehler($punkt[4] ?? null)
               . ui_symbol('winkel', 'symbol-rechts uebersicht-winkel') . "</a>\n";
        }
        ui_karte_ende();
    }
    echo '  </div>' . "\n";
    ui_karte_start();
    echo '    ' . ui_knopf(['text' => 'Abmelden', 'href' => 'logout.php', 'art' => 'leise',
        'symbol' => 'abmelden', 'breit' => true,
        'attr' => ' data-confirm="Wirklich abmelden?" data-confirm-ok="Abmelden"'
                . ' data-confirm-tone="normal"']) . "\n";
    ui_karte_ende();
    ui_geruest_ende();
    ui_seite_ende();
}


/* ---------------------------------------------------------------------------
 * FUSSZEILE  (.fuss-seite)
 *
 * Zweizeilig und zentriert, auf JEDER Seite — auch auf Anmeldung, Passwort
 * zurücksetzen, Abbruchseite und Einrichter (R32, E-P3-14).
 *
 * Sie steht AUSSERHALB von <main>. Bis Web 8.0.1 stand sie mitten im Inhalt;
 * für ein Gerüst mit klebender Leiste unten ist das der falsche Ort — und es
 * war der Grund, warum sie auf Seiten ohne Inhalt fehlte.
 *
 * DIE VERWEISE STEHEN SEIT WEB 9.11.0 IMMER. Bis dahin prüfte diese Funktion
 * mit is_file(), ob es die Seiten überhaupt gibt — richtig, solange sie noch
 * nicht existierten (sie entstanden in O10), und danach tote Logik: zwei
 * Dateisystemzugriffe je Seitenaufruf für eine Frage, deren Antwort feststeht.
 *
 * Sie sagte auch die falsche Sache. „Die Datei ist da" heißt nicht „ein Text
 * ist hinterlegt" — der Leerzustand ist eine gültige Antwort und gehört
 * erreichbar, gerade weil er der Administration sagt, dass noch etwas fehlt.
 *
 * AUSNAHME EINRICHTER ('rechtslinks' => false). Er läuft VOR der
 * Ersteinrichtung, die beiden Seiten brauchen aber eine Datenbank. Der
 * Verweis führte dort in eine Weiterleitung zurück auf den Einrichter — eine
 * Schleife auf der einen Seite, auf der niemand eine gebrauchen kann.
 *
 * $o: dunkel       true = helle Schrift auf Dunkelblau (Anmeldung)
 *     rechtslinks  false = zweite Zeile weglassen (nur der Einrichter)
 * ------------------------------------------------------------------------ */
function ui_fuss_seite(array $o = []): void
{
    $lizenz = 'https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE';
    $rechts = ($o['rechtslinks'] ?? true)
        ? ['<a href="impressum.php">Impressum</a>',
           '<a href="datenschutz.php">Datenschutz</a>']
        : [];
    ?>
<footer class="fuss-seite<?= !empty($o['dunkel']) ? ' fuss-dunkel' : '' ?>">
  <p class="fuss-zeile">© Gen-EM · Open Source
    <a href="<?= ui_e($lizenz) ?>" target="_blank" rel="noopener">AGPL-3.0</a>
    <?php /* OHNE VERSION KEIN „v". Im Einrichter ist WEB_VERSION nicht
             definiert — version.php kommt über db.php, und das braucht die
             config.php, die es dort noch nicht gibt. Die Fußzeile zeigte
             deshalb ein nacktes „v" ohne Zahl: eine Auskunft, die keine ist. */ ?>
    <?php if (defined('WEB_VERSION')): ?>
    <span class="fuss-version">v<?= ui_e(WEB_VERSION) ?></span><?php endif; ?></p>
  <?php if ($rechts): ?>
  <p class="fuss-zeile fuss-rechts"><?= implode("\n    ", $rechts) ?></p>
  <?php endif; ?>
</footer>
<?php }


/* ---------------------------------------------------------------------------
 * DIE HINWEISE ÜBER DEM INHALT  (.hinweise)  — P5c/AP1, E-P5c-55, M-P5c-02
 *
 * VIER STREIFEN, IN DIESER REIHENFOLGE: Umgebung → Ankündigung → Demo →
 * Datenschutz. Vom Allgemeinen zum Eigenen: welche Anlage, was auf ihr
 * geschieht, was für dieses Konto gilt.
 *
 * EIN BEHÄLTER STATT VIER AUSSENABSTÄNDEN. `.demo-hinweis` und `.meldung`
 * bringen je einen Abstand nach unten mit; gestapelt ergäbe das Lücken
 * zwischen Zeilen, die zusammengehören. Die Reihe setzt ihren eigenen
 * Abstand und nimmt den der Kinder zurück (style.css).
 *
 * AN DER STELLE DES DEMO-HINWEISES, nicht unter der Kopfleiste: Dort
 * verschob ein Streifen die klebende Leiste (F-P3-G), und der Platz ist seit
 * P3 geräumt. Die rote Kopfleiste kennzeichnet jede Seite mit Kopfleiste
 * ohnehin; die Zeile sagt, was Rot heißt.
 *
 * DIE SEITEN OHNE GERÜST rufen diese Funktion selbst, als erstes Kind ihres
 * `<main>` — die Anmeldeseiten über der Karte (E-P5c-60), die übrigen über
 * dem Text. Ohne Streifen gibt sie nichts aus, auch keinen leeren Behälter.
 *
 * `$datenschutz = false` nur auf `einwilligung.php`: Die Seite ist das Ziel,
 * auf das der Datenschutz-Streifen zeigt.
 * ------------------------------------------------------------------------ */
function ui_hinweise(bool $datenschutz = true): void
{
    ob_start();
    ui_umgebung_hinweis();
    $kreuz = ui_ankuendigung();
    ui_demo_hinweis();
    if ($datenschutz) { ui_datenschutz_hinweis(); }
    $innen = (string)ob_get_clean();
    if (trim($innen) === '') { return; }
    echo '<div class="hinweise">' . "\n" . $innen . "</div>\n";
    /* DAS SKRIPT STEHT HINTER DER REIHE, nicht darin: `ankuendigung.js`
     * nimmt die Reihe mit ihrem letzten Streifen weg und zaehlt dafuer ihre
     * Kinder. Ein <script> darin waere ein Kind, das nie verschwindet — die
     * leere Reihe bliebe mit ihrem Aussenabstand stehen (gemessen beim
     * ersten Lauf). */
    if ($kreuz) {
        echo '<script src="' . ui_e(ui_asset('assets/ankuendigung.js')) . '" defer></script>' . "\n";
    }
}

/**
 * DER UMGEBUNGSSTREIFEN  (.demo-hinweis.hinweis-umgebung)
 *
 * Eine Variante des Hinweisstreifens im Ton der Fehlermeldung (Rot-tief auf
 * Rosa, 6,27 : 1) — kein neuer Baustein (M-P5c-02, E-P5c-66). Nicht
 * wegklickbar, aus demselben Grund wie der Demo-Hinweis: Wer eine Pause
 * macht und zurückkommt, soll wieder sehen, wo er ist.
 */
function ui_umgebung_hinweis(): void
{
    $u = ui_umgebung();
    if ($u === null) { return; }
    ?>
<div class="demo-hinweis hinweis-umgebung" role="status">
  <?= ui_symbol('server', 'symbol-gross') ?>
  <p><strong><?= ui_e($u['name']) ?></strong> — Testdaten, kein Echtbetrieb.</p>
</div>
<?php }

/**
 * DIE ANKÜNDIGUNG  (.meldung.meldung-ankuendigung)  — E-P5c-13, -60
 *
 * Gibt zurück, ob ein Kreuz dasteht — dann braucht die Seite das Skript
 * (ui_hinweise() bindet es hinter der Reihe ein).
 *
 * Die vorhandene Meldung in ihrem Ton (info/warn), mit einem Kreuz in der
 * Aktionsspalte. Das Kreuz ist ein FORMULAR und kein Knopf mit Skript: Es
 * geht damit auch dort, wo kein Skript das Token kennt (Anmeldeseite);
 * `assets/ankuendigung.js` macht daraus, wo es kann, ein Schließen ohne
 * Neuladen.
 *
 * KEIN KREUZ OHNE SITZUNG. Die lesenden Seiten (Handbuch, Rechtstexte)
 * starten ohne Cookie keine, und die Setzseite hat eine eigene
 * (`sitzung_starten('passwort')`, E-ZE-12) — ein Kreuz dort schlösse in
 * einer Sitzung, die die nächste Seite nicht liest. Der Streifen steht dann
 * ohne Kreuz da; schließen lässt er sich auf jeder anderen Seite.
 *
 * DER ERSTE SATZ WIRD FETT, wie im Bild (M-P5c-02 a): „Wartung am Dienstag,
 * 30.09.2026, 20:00 bis 21:00." ist der Satz, den man beim Vorbeiscrollen
 * lesen soll. Erkannt wird er an einem Satzzeichen mit Leerzeichen und
 * Großbuchstaben dahinter, frühestens nach zwölf Zeichen — sonst bräche
 * „z. B. Wartung" hinter dem „z.". Ein einziger Satz bleibt ganz normal.
 */
function ui_ankuendigung(): bool
{
    if (!function_exists('app_state_mehrere')) { return false; }   // Einrichter: keine Datenbank
    require_once __DIR__ . '/ankuendigung_lib.php';
    $a = ankuendigung();
    if ($a === null || ankuendigung_weggeklickt($a)) { return false; }

    $auftakt = '';
    $text = $a['text'];
    if (preg_match('/^(.{12,}?[.!?])\s+(?=\p{Lu})(.+)$/su', $text, $m)) {
        $auftakt = $m[1];
        $text = $m[2];
    }

    $kreuz = '';
    $sitzungApp = session_status() === PHP_SESSION_ACTIVE
        && !(defined('PW_SESSION_NAME') && session_name() === PW_SESSION_NAME)
        && function_exists('csrf_field');
    if ($sitzungApp) {
        $seite = basename((string)($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
        $abfrage = (string)($_SERVER['QUERY_STRING'] ?? '');
        $kreuz = '<form method="post" action="ankuendigung.php" data-ankuendigung-weg>'
               . csrf_field()
               . '<input type="hidden" name="zurueck" value="'
               . ui_e($seite . ($abfrage !== '' ? '?' . $abfrage : '')) . '">'
               . ui_knopf(['art' => 'symbol', 'symbol' => 'schliessen',
                           'titel' => 'Ankündigung ausblenden'])
               . '</form>';
    }
    echo ui_meldung_markup($a['ton'], $text, $auftakt, $kreuz, 'meldung-ankuendigung'), "\n";
    return $kreuz !== '';
}


/* ---------------------------------------------------------------------------
 * DEMO-HINWEIS  (.demo-hinweis)
 *
 * DAUERHAFT, nicht wegklickbar. Ein Hinweis, den man einmal schließt, ist beim
 * zweiten Besuch nicht mehr da — und genau dann wäre er nötig: Wer nach einer
 * Pause wiederkommt, findet seine Eingaben nicht mehr vor und soll wissen,
 * warum.
 *
 * Er nennt VIER Dinge, und alle vier sind nötig: dass die Daten erfunden sind
 * (sonst liest jemand sie als echte Fälle), dass Ausprobieren erwünscht ist
 * (sonst traut sich niemand), dass alles regelmäßig verworfen wird (sonst ist
 * die Überraschung groß) und dass hier keine echten Daten hineingehören — das
 * ist der Punkt, an dem es ernst wird: Das Schlüsselmaterial dieses Kontos
 * liegt auf dem Server.
 *
 * NEU IN P3: Er steht INNERHALB des Inhalts, nicht zwischen Kopfleiste und
 * Gerüst. Vorher verschob er die klebende Leiste um seine eigene Höhe, und im
 * Demo-Konto rutschte sie unter der Kopfleiste hervor (F-P3-G).
 * ------------------------------------------------------------------------ */
function ui_demo_hinweis(): void
{
    /* ERST DIE SITZUNG, DANN DIE BIBLIOTHEK (P5c/AP1, F-P5c-71). Seit die
     * Streifen auch auf den Seiten ohne Gerüst stehen, läuft diese Funktion
     * im Einrichter — dort gibt es noch keine `config.php`, und
     * `demo_lib.php` zieht `db.php`, das ohne sie wirft. Ohne Anmeldung gibt
     * es kein Demo-Konto; die Frage braucht dann keine Datenbank. Die zweite
     * Wache ist dieselbe wie in `ui_ankuendigung()`: keine Datenbank geladen,
     * kein Streifen, der eine braucht. */
    $uid = $_SESSION['user_id'] ?? null;
    if ($uid === null || !function_exists('db')) { return; }
    if (!function_exists('demo_ist_demo')) {
        if (!is_file(__DIR__ . '/demo_lib.php')) { return; }
        require_once __DIR__ . '/demo_lib.php';
    }
    if (!demo_ist_demo((int)$uid)) { return; }
    $rest = demo_reset_in();
    ?>
<div class="demo-hinweis" role="status">
  <?= ui_symbol('kolben', 'symbol-gross') ?>
  <p><strong>Demo-Konto.</strong> Alle Daten hier sind <strong>frei
  erfunden</strong>. Ausprobieren ist ausdrücklich erwünscht — ändern,
  anlegen, löschen, Gerät koppeln. Der Bestand wird
  <strong>alle 30&nbsp;Minuten</strong> auf den Ausgangsstand
  zurückgesetzt<?= $rest > 0 ? ', das nächste Mal in etwa '
      . (int)ceil($rest / 60) . '&nbsp;Minuten' : '' ?>.
  <strong>Bitte niemals echte Patienten- oder Einsatzdaten erfassen.</strong></p>
</div>
<?php }


/**
 * DER HINWEIS AUF EINE NEUE DATENSCHUTZERKLAERUNG (P5b/AP4, E-P5b-05).
 *
 * WARUM HIER UND NICHT ALS TOR. Eine Datenschutzerklaerung wird nicht
 * angenommen, sondern zur Kenntnis genommen — Widerspruch dagegen ist kein
 * Vertragsschluss, sondern ein Recht. Sie darf deshalb NICHT den Zugang
 * sperren; sie muss aber auffallen, sonst ist die Kenntnisnahme eine
 * Behauptung.
 *
 * DERSELBE PLATZ WIE DER DEMO-HINWEIS, und das ist kein Zufall: Beide sind
 * Aussagen ueber den Zustand dieses Kontos, die auf JEDER Seite gelten und
 * keine Handlung der Seite betreffen. Ein `ui_meldung()` waere falsch — das
 * gehoert zur Handlung, die die Seite gerade ausfuehrt.
 *
 * `$einwilligungOffen` KOMMT AUS `auth_guard.php` und wird hier nicht neu
 * gelesen: Die Abfrage liefe sonst zweimal je Seitenaufbau. Steht die
 * Variable nicht (Seiten ohne Wache, oder ein API-Aufruf), zeigt die
 * Funktion nichts — richtig so, denn dann gibt es auch keine Sitzung.
 */
function ui_datenschutz_hinweis(): void
{
    $offen = $GLOBALS['einwilligungOffen']['hinweis'] ?? [];
    if (!$offen) { return; }
    ?>
<div class="demo-hinweis" role="status">
  <?= ui_symbol('hinweis', 'symbol-gross') ?>
  <p><strong>Die Datenschutzerklärung hat eine neue Fassung.</strong> Sie sagt,
  was mit deinen Daten geschieht — was verschlüsselt liegt, was im Klartext,
  wie lange und warum. <a href="einwilligung.php">Ansehen und bestätigen</a>;
  bis dahin bleibt dieser Hinweis stehen. <strong>Gesperrt wird dafür
  nichts</strong> — eine Kenntnisnahme ist keine Zustimmung.</p>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * MELDUNG  (.meldung)
 *
 * VIER TÖNE, jeder mit Symbol und optionalem fettem Auftakt (E-P3-16):
 *
 *   fehler   rosa/rot   Dreieck    „Nicht gespeichert."
 *   info     hellblau   Kreis-i    Erklärung, Zustand
 *   ok       hellblau   Haken      Vollzug: „Backup erstellt."
 *   warn     hellorange Dreieck    Warnung, die kein Fehler ist
 *
 * GRÜN IST FORT. Die Vollzugsmeldung war grün — eine Farbe, die es in der
 * Marke nicht gibt. Sie ist jetzt blau wie der Hinweis und unterscheidet sich
 * durch das SYMBOL: Haken gegen Kreis-i. Das ist zugleich die Einlösung des
 * Vorbehalts E-A6-02 aus Konzept P0, der die Tonart ausdrücklich P3
 * überlassen hat.
 *
 * Farbe ist nie der einzige Träger (Grundregel 3) — deshalb trägt jede
 * Meldung ein Symbol, und das war vorher bei keiner der Fall.
 *
 * Signatur wie im Bestand, damit die 21 Aufrufstellen unverändert bleiben:
 *   $hinweis, $fehler, $ton, $einzug
 * $o ergänzt sie um:
 *   auftakt         fetter Auftakt der Hinweiszeile
 *   auftakt_fehler  fetter Auftakt der Fehlerzeile (Vorgabe „Nicht gespeichert.")
 *   knopf           Markup rechts in der Meldung (z. B. „Entsperren")
 * ------------------------------------------------------------------------ */
function ui_meldung(?string $hinweis, ?string $fehler = null,
                    string $ton = 'info', string $einzug = '',
                    array $o = []): void
{
    $zeilen = [];
    if ($hinweis !== null && $hinweis !== '') {
        $zeilen[] = ui_meldung_markup($ton, $hinweis,
            (string)($o['auftakt'] ?? ''), (string)($o['knopf'] ?? ''));
    }
    if ($fehler !== null && $fehler !== '') {
        $zeilen[] = ui_meldung_markup('fehler', $fehler,
            (string)($o['auftakt_fehler'] ?? ''), '');
    }
    if ($zeilen === []) { return; }
    echo implode("\n" . $einzug, $zeilen), "\n";
}

/**
 * Markup einer einzelnen Meldung. Auch von den JS-Erzeugern nachgebaut.
 *
 * `$klasse` BENENNT EINEN VERWENDER, keine Variante (P5c/AP1): Die
 * Ankündigung trägt `meldung-ankuendigung`, damit ihr Kreuz oben rechts
 * stehen bleibt, statt beim Umbruch allein in eine Zeile zu fallen
 * (style.css). Ton und Symbol bleiben die geschlossene Liste unten.
 */
function ui_meldung_markup(string $ton, string $text, string $auftakt = '',
                           string $knopf = '', string $klasse = ''): string
{
    /* FUENF TOENE, UND DIE LISTE IST GESCHLOSSEN (Design.md 9.5). Ein Ton,
     * den es nicht gibt, ergab bis S3 eine Klasse ohne Regel im Stylesheet —
     * also einen ungestalteten Kasten, und zwar ohne jede Fehlermeldung. Die
     * Spurenseite trug so zwei Jahre lang zwei weisse Meldungen mit dem Ton
     * „hinweis", den diese Funktion nie gekannt hat. Weil die Klasse hier
     * ZUSAMMENGESETZT wird, sieht die Vollstaendigkeitspruefung sie nicht;
     * das kann nur diese Stelle selbst pruefen. */
    $symbole = ['fehler' => 'warnung', 'warn' => 'warnung',
                'ok' => 'haken', 'info' => 'hinweis', 'schutz' => 'schloss'];
    if (!isset($symbole[$ton])) {
        throw new InvalidArgumentException(
            'Unbekannter Meldungston „' . $ton . '". Erlaubt: '
            . implode(', ', array_keys($symbole)) . '.');
    }
    $sym = $symbole[$ton];
    $m = '<div class="meldung meldung-' . ui_e($ton) . ($klasse !== '' ? ' ' . ui_e($klasse) : '')
       . '" role="' . ($ton === 'fehler' ? 'alert' : 'status') . '">';
    $m .= ui_symbol($sym, 'symbol-gross');
    $m .= '<p>';
    if ($auftakt !== '') { $m .= '<strong>' . ui_e($auftakt) . '</strong> '; }
    $m .= ui_e($text) . '</p>';
    if ($knopf !== '') { $m .= '<div class="meldung-aktion">' . $knopf . '</div>'; }
    return $m . '</div>';
}


/* ---------------------------------------------------------------------------
 * KNOPF  (.knopf)
 *
 * EINE HÖHE: 44 px, mobil wie Desktop, auch für Zeilenaktionen. Es gibt keine
 * Kompaktvariante — was kleiner ist, ist kein Knopf, sondern ein Link mit
 * Symbol (E-P3-22). Der Bestand hatte sechs Varianten und sechs
 * ortsgebundene Größen; `.btn-primary` trug global `width:100%` und wurde an
 * zehn Stellen zurückgenommen.
 *
 * VIER ARTEN, nach Bedeutung und nicht nach Aussehen:
 *   primaer   Orange, dunkelblaue Schrift — die eine Haupthandlung
 *   neutral   Rahmen — alles Übrige, auch „Bearbeiten"
 *   gefahr    roter Rahmen, rote Schrift — Löschen
 *   leise     nur Schrift — Abbrechen, Nebenwege
 *   symbol    44 x 44, nur ein Zeichen (braucht 'titel')
 *
 * $o: text, href, symbol, art, titel, klasse, typ, name, wert, attr, breit
 * ------------------------------------------------------------------------ */
function ui_knopf(array $o): string
{
    $art  = (string)($o['art'] ?? 'neutral');
    $text = (string)($o['text'] ?? '');
    $k = 'knopf knopf-' . $art;
    if ($art === 'symbol') { $k = 'knopf knopf-symbol'; }
    if (!empty($o['breit']))  { $k .= ' knopf-breit'; }
    if (!empty($o['klasse'])) { $k .= ' ' . (string)$o['klasse']; }

    $inneres = '';
    if (!empty($o['symbol'])) { $inneres .= ui_symbol((string)$o['symbol'], 'symbol-gross'); }
    if ($text !== '') {
        $inneres .= '<span>' . ui_e($text) . '</span>';
    } elseif (!empty($o['titel'])) {
        $inneres .= '<span class="nur-vorlesen">' . ui_e((string)$o['titel']) . '</span>';
    }

    $attr = (string)($o['attr'] ?? '');
    if (!empty($o['titel'])) { $attr .= ' title="' . ui_e((string)$o['titel']) . '"'; }

    if (!empty($o['href'])) {
        return '<a class="' . $k . '" href="' . ui_e((string)$o['href']) . '"' . $attr . '>'
             . $inneres . '</a>';
    }
    $b = '<button class="' . $k . '" type="' . ui_e((string)($o['typ'] ?? 'submit')) . '"';
    if (!empty($o['name'])) { $b .= ' name="' . ui_e((string)$o['name']) . '"'; }
    if (isset($o['wert']))  { $b .= ' value="' . ui_e((string)$o['wert']) . '"'; }
    return $b . $attr . '>' . $inneres . '</button>';
}


/* ---------------------------------------------------------------------------
 * WERTEKASTEN, KLEINE STUFE  (.codeblock.codeblock-lang)
 *
 * Fuer LANGE Werte: Cron-Zeile, Token-Adresse, Setz-Link,
 * Serverschluessel-Zeile, Geraete-ID und API-Schluessel. Die grosse Stufe
 * (`.codeblock-wert`) ist fuer sechs Zeichen gemacht und sperrt sie zusaetzlich
 * — bei hundert Zeichen ergibt das drei Zeilen in Plakatgroesse (Backlog
 * Nr. 78, E-S8-10).
 *
 * DER KNOPF IST TEIL DES BAUSTEINS und nicht Sache der Seite: Ein Wert, den man
 * kopieren soll, und ein Knopf, der ihn kopiert, gehoeren zusammen — sonst
 * baut ihn die naechste Seite anders. Er braucht `assets/kopieren.js`; wer
 * diesen Baustein benutzt, nimmt das Skript in `ui_seite_ende(['skripte' =>
 * …])` mit.
 *
 * OHNE JAVASCRIPT bleibt der Wert lesbar und markierbar — der Knopf verschwindet
 * dann (das Skript blendet ihn ein). Ein Knopf, der nichts tut, waere schlechter
 * als keiner.
 *
 * $wert  der Wert selbst (wird maskiert)
 * $titel optionale Kleinzeile darueber ("Adresse")
 * ------------------------------------------------------------------------ */
function ui_codeblock_lang(string $wert, string $titel = ''): string
{
    $h  = '<div class="codeblock codeblock-lang">' . "\n";
    $h .= '  <div class="codeblock-text">' . "\n";
    if ($titel !== '') {
        $h .= '    <p class="codeblock-titel">' . ui_e($titel) . "</p>\n";
    }
    $h .= '    <p class="codeblock-wert-lang" data-kopierwert>' . ui_e($wert) . "</p>\n";
    $h .= "  </div>\n";
    $h .= '  ' . ui_knopf(['text' => 'Kopieren', 'art' => 'leise', 'typ' => 'button',
                            'attr' => ' data-kopieren hidden']) . "\n";
    return $h . "</div>\n";
}


/* ---------------------------------------------------------------------------
 * PLAKETTE  (.plakette)
 *
 * Plaketten tragen KEIN Häkchen: Ihr Vorhandensein ist das Häkchen. Und sie
 * sind KEINE Bedienelemente — wer eine anklickbar braucht, nimmt einen Knopf
 * (E-P3-17).
 *
 * Töne: neutral · orange (Winde, Bergwacht) · blau (Sekundär,
 * Rettungsmittel, aktuell, freigegeben) · rot (Fehleinsatz, kein Ende, nie
 * gesichert, leer).
 * ------------------------------------------------------------------------ */
function ui_plakette(string $text, array $o = []): string
{
    $k = 'plakette plakette-' . ui_e((string)($o['ton'] ?? 'neutral'));
    $p = '<span class="' . $k . '">';
    if (!empty($o['symbol'])) { $p .= ui_symbol((string)$o['symbol']); }
    $p .= ui_e($text);
    if (!empty($o['entfernen'])) {
        $p .= '<button type="button" class="plakette-weg" '
            . 'aria-label="' . ui_e($text . ' entfernen') . '" '
            . (string)($o['entfernen_attr'] ?? '') . '>' . ui_symbol('schliessen') . '</button>';
    }
    return $p . '</span>';
}


/* ---------------------------------------------------------------------------
 * KARTE  (.karte) — der Inhaltsblock
 *
 * Jeder Inhaltsblock ist eine Karte mit Titel in Bricolage, optionaler Zahl
 * (gedämpft) und GENAU EINER Kopfaktion rechts als Link mit Symbol:
 * „Bearbeiten" (blau) oder ein Anlegen-Weg („+ Nachtragen", orange tief).
 * Eine zweite Kopfaktion gibt es nicht — was mehr braucht, bekommt ein
 * Aktionsmenü (E-P3-25).
 *
 * Zugeklappte Karten tragen den Winkel links im Kopf und eine Vorschau rechts
 * („keine", „vom Diensttag", „3 · 1 ausgewählt").
 *
 * $o: titel, zahl, aktion ['text','href','symbol','art','form','attr'],
 *     zu (bool), vorschau, klasse, id, plakette, geschuetzt (bool),
 *     symbol (Bereichszeichen vor dem Titel — nur mit 'klasse' => 'karte-bereich')
 *
 * DIE BEREICHSKARTE (P5c/AP9, E-P5c-29, Mockup M-P5c-01c). `symbol` setzt ein
 * rundes Zeichen in Dunkelblau vor den Titel; zusammen mit der Klasse
 * `.karte-bereich` steht der Kopf auf Rauch und die Gruppe aus Zeichen, Titel
 * und Zahl MITTIG. Gedacht für die Einstellungen-Übersicht; ein zweiter
 * Verwender braucht einen Grund (Design.md 9.1). Eine Bereichskarte trägt
 * KEINE Kopfaktion — mittig gesetzt hätte sie keinen Platz. Das Zeichen ist
 * `aria-hidden` wie jedes Symbol; der Titel daneben sagt, was es meint.
 *
 * DIE KOPFAKTION KANN AUCH EIN ABSENDEKNOPF SEIN (S8/AP3). „Jetzt sichern"
 * auf der Kontoseite ist ein POST, kein Link — mit `form` wird aus dem <a>
 * ein <button type="submit" form="…">, gleiche Klasse, gleiches Aussehen.
 * Ein <form> um den Knopf ginge nicht: Der Kartenkopf steht bereits in einem
 * Formular, und verschachtelte Formulare gibt es in HTML nicht.
 * ------------------------------------------------------------------------ */
function ui_karte_start(array $o = []): void
{
    $zu = !empty($o['zu']) || isset($o['vorschau']);

    /* 'geschuetzt' => true haengt das SCHLOSS an den Kartentitel (Web 19.1.1).
     *
     * Gedacht ist es fuer eine Karte, deren Inhalt vollstaendig im `pat_blob`
     * liegt, ohne dass ein einzelnes Feld das Zeichen tragen KANN: Die Karte
     * „Notizen" enthaelt genau ein Feld, und dessen Beschriftung heisst wie die
     * Karte — $labelSichtbar() blendet sie deshalb aus (sie stuende zweimal
     * da), und mit ihr verschwand das Schloss. Uebrig blieb die Kleinzeile
     * „Ende-zu-Ende-verschluesselt", also ein Text, wo die Karte „PatientIn"
     * daneben acht Schloesser zeigt. Wer das nebeneinander sieht, liest den
     * Unterschied als Aussage ueber die Sache — und liest falsch.
     *
     * DAS ZEICHEN STEHT IM <h2>, nicht daneben. `.karte-kopf` ist ein
     * Flex-Kasten mit `gap`; ein eigenes Flex-Kind bekaeme den Abstand zweimal
     * (gap plus das `margin-left` von `.symbol-schutz`). Im Titel verhaelt es
     * sich genau wie in einer Feldbeschriftung: Wort, dann Symbol. Keine neue
     * CSS-Regel, keine neue Darstellung — derselbe Baustein an einer weiteren
     * Stelle. */
    $titel = ui_e((string)($o['titel'] ?? ''))
           . (!empty($o['geschuetzt'])
               ? ui_symbol('schloss', 'symbol-schutz', 'Ende-zu-Ende-verschlüsselt')
               : '');
    $k  = 'karte' . (!empty($o['klasse']) ? ' ' . (string)$o['klasse'] : '');
    $id = !empty($o['id']) ? ' id="' . ui_e((string)$o['id']) . '"' : '';

    if ($zu) {
        echo '<details class="' . $k . ' karte-klappbar"' . $id
           . (!empty($o['offen']) ? ' open' : '') . ">\n";
        echo '  <summary class="karte-kopf">' . "\n";
        echo '    ' . ui_symbol('winkel', 'akkordeon-winkel') . "\n";
        echo '    <h2 class="karte-titel">' . $titel . "</h2>\n";
        if (isset($o['zahl'])) {
            echo '    <span class="karte-zahl">' . ui_e((string)$o['zahl']) . "</span>\n";
        }
        if (isset($o['vorschau'])) {
            echo '    <span class="karte-vorschau">' . ui_e((string)$o['vorschau']) . "</span>\n";
        }
        echo "  </summary>\n";
        echo '  <div class="karte-inhalt">' . "\n";
        return;
    }

    echo '<section class="' . $k . '"' . $id . ">\n";
    if (isset($o['titel'])) {
        echo '  <div class="karte-kopf">' . "\n";
        if (!empty($o['symbol'])) {
            echo '    <span class="bereich-zeichen">' . ui_symbol((string)$o['symbol'])
               . "</span>\n";
        }
        echo '    <h2 class="karte-titel">' . $titel . "</h2>\n";
        if (isset($o['zahl'])) {
            echo '    <span class="karte-zahl">' . ui_e((string)$o['zahl']) . "</span>\n";
        }
        /* Eine Plakette neben Titel und Zahl (O9, Mockup 40: „Backups 3
         * [überfällig · 23 Tage]"). Sie sagt den ZUSTAND des Karteninhalts —
         * und gehört deshalb dorthin, wo man den Titel liest, nicht in die
         * erste Zeile darunter. Fertiges Markup aus ui_plakette(). */
        if (!empty($o['plakette'])) {
            echo '    ' . (string)$o['plakette'] . "\n";
        }
        if (!empty($o['aktion'])) {
            $a = $o['aktion'];
            $art = (string)($a['art'] ?? 'blau');
            $k   = 'karte-aktion karte-aktion-' . ui_e($art);
            $inhalt = (!empty($a['symbol']) ? ui_symbol((string)$a['symbol']) : '')
                    . '<span>' . ui_e((string)($a['text'] ?? '')) . '</span>';
            $extra = !empty($a['attr']) ? ' ' . (string)$a['attr'] : '';
            if (!empty($a['form'])) {
                echo '    <button type="submit" class="' . $k . '" form="'
                   . ui_e((string)$a['form']) . '"' . $extra . '>' . $inhalt . "</button>\n";
            } else {
                echo '    <a class="' . $k . '" href="'
                   . ui_e((string)($a['href'] ?? '#')) . '"' . $extra . '>'
                   . $inhalt . "</a>\n";
            }
        }
        echo "  </div>\n";
    }
    echo '  <div class="karte-inhalt">' . "\n";
}

function ui_karte_ende(bool $klappbar = false): void
{
    echo "  </div>\n" . ($klappbar ? "</details>\n" : "</section>\n");
}


/* ---------------------------------------------------------------------------
 * ZUM ANFANG  (.nach-oben)                                    S9/AP5, M-S9-06
 *
 * Der Rueckweg am Ende eines langen Abschnitts. Eine Standortseite mit zehn
 * Rettungsmitteln und drei Dutzend Zielkliniken ist mehrere Bildschirme lang;
 * wer unten ankommt, will nicht dorthin zurueckwischen, wo das
 * Inhaltsverzeichnis steht.
 *
 * EIGENE FUNKTION UND KEINE OPTION AN `ui_karte_ende()`: Die hat als einziger
 * Baustein kein `array $o`, dafuer 115 Aufrufstellen — eine Signaturaenderung
 * kostete 115 Zeilen fuer eine Zeile Gewinn. Und so steht sie in der
 * erzeugten Bausteintabelle.
 *
 * `.knopf knopf-leise` GIBT ES SCHON, und die Klasse ist hier kein Zierrat:
 * Der Bilderlauf misst Bedienhoehen an `.knopf`. Ein eigener Klassenname
 * waere aus seiner Messung gefallen — genau so ist der Export-Knopf vier
 * Monate ungestaltet geblieben (F-P3-BA).
 *
 * KEIN `scroll-margin-top`: `html` traegt bereits `scroll-padding-top`; die
 * zweite Angabe war schon einmal gebaut und wieder ausgebaut, weil sie sich
 * addierte (gemessen 140 statt 72 px).
 *
 * DAS ZIEL IST `#inhalt` — die Kennung, die `ui_leiste_ende()` ohnehin an das
 * `<main>` haengt. Zuerst stand hier `#seitenanfang`, eine Kennung, die es in
 * dieser Anwendung nirgends gibt: Der Knopf sprang nach nirgendwo, und weil
 * ein Verweis auf ein fehlendes Ziel weder Fehler noch Meldung erzeugt, waere
 * das erst jemandem aufgefallen, der ihn drueckt. Eine zweite Kennung
 * anzulegen hiesse, dieselbe Stelle zweimal zu benennen.
 * ------------------------------------------------------------------------ */
function ui_nach_oben(string $ziel = '#inhalt'): void
{
    echo '  <p class="nach-oben">'
       . ui_knopf(['text' => 'Zum Anfang', 'symbol' => 'pfeil-hoch',
                   'art' => 'leise', 'href' => $ziel])
       . "</p>\n";
}


/* ---------------------------------------------------------------------------
 * SPRUNGLISTE  (.sprungliste / .sprungziel)                  S9/AP5, M-S9-05
 *
 * Eine umbrechende Zeile runder Marken ueber einer langen Liste: Artzeichen
 * plus Name, ein Klick springt zur Zeile. Sie ersetzt kein Inhaltsverzeichnis
 * der SEITE (das sind die Kennzahlen am Kopf und die Unterpunkte der Leiste),
 * sondern fuehrt INNERHALB einer Liste — deshalb steht sie in der Karte und
 * nicht darueber.
 *
 * AB SECHS EINTRAEGEN (`SD_HILFE_AB`, stammdaten_ui.php). Darunter sieht man
 * die ganze Liste ohne zu rollen, und eine Sprungliste waere eine zweite
 * Aufzaehlung derselben Namen.
 *
 * SIE IST EIN `<nav>` MIT `<a>`, KEIN KNOPF. Ein Sprungziel ist Navigation:
 * Es aendert nichts, es steht im Verlauf, und der Rueckwaertsknopf bringt
 * einen zurueck. Dieselbe Ueberlegung traegt `.listenfilter` auf der
 * Suchseite, die ebenfalls als `<a>` gebaut ist.
 *
 * DIE PILLE HEISST IM ZIELZUSTAND `.aktiv` UND NICHT `.ziel`. Das Mockup
 * schreibt `.ziel`, aber `.aktiv` ist in dieser Anwendung seit Langem das
 * Wort fuer „hier stehst du" — Kopfleiste, Leiste, Kennzahl, Listenfilter,
 * Blattzeile und Seitenknopf tragen es, und `.kennzahl.aktiv` ist Zeichen
 * fuer Zeichen dieselbe Deklaration. Ein zweiter Name fuer denselben Zustand
 * ist eine zweite Sprache.
 *
 * KEIN `scroll-margin-top` AN DEN ZIELEN: `html` traegt
 * `scroll-padding-top` (style.css), und das gilt fuer jedes Sprungziel der
 * Seite. Die zweite Angabe war einmal gebaut und addierte sich (gemessen
 * 140 statt 72 px).
 *
 * $o: eintraege [ ['text', 'href', 'vorn' (fertiges Markup, meist ein
 *     Artzeichen)] ], label (Beschriftung fuer die Vorlesesoftware)
 * ------------------------------------------------------------------------ */
function ui_sprungliste(array $o): void
{
    $eintraege = (array)($o['eintraege'] ?? []);
    if (!$eintraege) { return; }
    echo '  <nav class="sprungliste" aria-label="'
       . ui_e((string)($o['label'] ?? 'Zu einem Eintrag springen')) . '">' . "\n";
    foreach ($eintraege as $e) {
        echo '    <a class="sprungziel" href="' . ui_e((string)$e['href']) . '">'
           . (string)($e['vorn'] ?? '')
           . '<span class="sprungziel-text">' . ui_e((string)$e['text']) . '</span>'
           . "</a>\n";
    }
    echo "  </nav>\n";
}


/* ---------------------------------------------------------------------------
 * KARTENFILTER  (.kartenfilter)                              S9/AP5, M-S9-06
 *
 * Ein Feld mit Lupe ueber einer langen Liste in einer Karte: Tippen blendet
 * aus, was nicht passt — im Browser, ohne Anfrage. Erst ab `SD_HILFE_AB`
 * Eintraegen; darunter ist die Liste kuerzer als das Feld darueber.
 *
 * ER HEISST NICHT `.filterfeld`. Das Stylesheet fuehrt seit P3
 * `.filterfelder` (Mehrzahl) als Innenabstand einer aufgeklappten
 * Filtergruppe der Suchseite. Zwei Klassen, die sich um ein `r`
 * unterscheiden und Verschiedenes meinen, sind derselbe Fehler, den
 * `.listenfilter-zahl` einmal ausdruecklich umgangen hat („SIE HEISST NICHT
 * `.filterzahl`. Diese Klasse ist seit O6 vergeben", style.css). `.kartenfilter`
 * sagt zugleich, wo er steht — in einer Karte, nicht am Seitenkopf.
 *
 * ER IST NICHT DAS GROSSE SUCHFELD. `.suchfeld` ist 48 px hoch
 * (`--suchfeld`), und das ist die eine benannte Ausnahme von der
 * 44/36-Regel: Es ist die Haupthandlung SEINER Seite. Ein Filter in einer
 * von sechs Karten ist das nicht — hier gilt die Regel, nicht die Ausnahme.
 * Uebernommen ist von dort, was dort schon richtig ist: die Lupe absolut
 * links mit `pointer-events:none` in einem `align-items:center`-Behaelter
 * (also OHNE `top`, das sich in der zweiten Bedienhoehe verrechnete), das
 * Loeschkreuz rechts und die Beschriftung fuer die Vorlesesoftware.
 *
 * DER LEERZUSTAND STEHT IM MARKUP, nicht im Skript. Weder Mockup noch
 * Konzept sagen, was in der Karte steht, wenn nichts uebrig bleibt; ohne
 * Antwort sieht eine gefilterte Liste ohne Treffer aus wie eine leere Liste.
 * Der Absatz steht deshalb hier, verborgen, und das Skript blendet ihn ein.
 *
 * $o: id (Kennung des Eingabefelds), ziel (Kennung des Listenbehaelters),
 *     label (Beschriftung fuer die Vorlesesoftware), platzhalter
 * ------------------------------------------------------------------------ */
function ui_kartenfilter(array $o): void
{
    $id   = (string)$o['id'];
    $ziel = (string)$o['ziel'];
    echo '  <div class="kartenfilter" data-kartenfilter="' . ui_e($ziel) . '">' . "\n";
    echo '    ' . ui_symbol('lupe', 'kartenfilter-lupe') . "\n";
    echo '    <label class="nur-vorlesen" for="' . ui_e($id) . '">'
       . ui_e((string)($o['label'] ?? 'Liste filtern')) . "</label>\n";
    /* `type="search"` und NICHT `type="text"`: Die Tastatur des Handys zeigt
       dann eine Suchtaste statt einer Zeilenschaltung, und Vorlesesoftware
       nennt das Feld ein Suchfeld. Das browsereigene Kreuz stellt `style.css`
       ab — es sitzt je nach Browser woanders, und daneben stuende unseres. */
    echo '    <input type="search" id="' . ui_e($id) . '" autocomplete="off"'
       . ' spellcheck="false" placeholder="'
       . ui_e((string)($o['platzhalter'] ?? 'Filtern')) . '">' . "\n";
    echo '    <button type="button" class="kartenfilter-x" hidden title="Filter leeren">'
       . ui_symbol('schliessen', '', 'Filter leeren') . "</button>\n";
    echo "  </div>\n";
    /* DER ZWEITE SATZ IST MIT WEB 17.0.0 EIN ANDERER. Er lautete „Leere den
       Filter, um etwas anzulegen" — richtig, solange die Anlegen-Formulare in
       der Liste standen und beim Filtern mit verschwanden. Angelegt wird
       seither im Dialog, und dessen Oeffner steht im KARTENKOPF, also
       ausserhalb der gefilterten Liste: „Anlegen" ist auch bei null Treffern
       da. Der Satz sagt jetzt, was der Filter tatsaechlich verdeckt — alles
       Uebrige — und nicht mehr eine Sackgasse, die es nicht mehr gibt. */
    echo '  <p class="kartenfilter-leer feld-hinweis" data-leer-fuer="' . ui_e($ziel)
       . '" hidden>Kein Eintrag passt dazu. Leere den Filter, um wieder alle'
       . ' zu sehen.</p>' . "\n";
}


/* ---------------------------------------------------------------------------
 * ZEILE  (.zeile)
 *
 * Text links (fett plus Kleinzeile), Plaketten, Aktionen rechts. Am Desktop
 * sind die Aktionen Knöpfe zu 44 px, mobil ein einziges „⋯" je Zeile, das
 * dasselbe Aktionsblatt öffnet (E-P3-26).
 *
 * DIE GANZE ZEILE ALS VERWEIS (`href_ganz`, S9/AP5, Mockup M-S9-06). Die
 * Standortliste fuehrt auf je eine Seite; dort ist nicht der Name der Link,
 * sondern die Zeile, und rechts steht ein Winkel statt eines Knopfes. Zwei
 * Dinge folgen daraus:
 *   - Der Behaelter ist dann ein `<a>`, kein `<div>`. Ein `<a>` DARF keine
 *     Knoepfe oder Links enthalten; `aktionen` und `href` bleiben deshalb in
 *     dieser Form leer, und wer sie doch mitgibt, bekommt sie nicht
 *     gerendert — lieber eine fehlende Schaltflaeche als verschachteltes
 *     Markup, das je nach Browser anders zerfaellt.
 *   - Der Winkel steht in `zeile-aktionen`, also am selben Platz wie sonst
 *     die Knoepfe. Er ist Zierde und traegt keinen eigenen Namen: Was die
 *     Zeile tut, sagt ihr Text.
 *
 * DIE AUFKLAPPBARE ZEILE (`daten`, P5c/AP2, E-P5c-26, M-P5c-01a). Hat eine
 * Zeile Angaben, die nicht in den Satz passen (die `daten` eines
 * Protokolleintrags), wird sie ein `<details class="zeile-mehr">`, dessen
 * `<summary>` die Zeile IST; die Angaben stehen darunter als Liste. Der
 * Winkel steht in der Aktionsspalte. Kein „⋯"-Blatt auf dem Handy:
 * Aufklappen ist keine Handlung, sondern Lesen. `aktionsspalte => true`
 * haelt die leere Spalte in Zeilen OHNE Angaben, damit alle Plaketten einer
 * Liste auf einer Kante enden (Vorgabe 17.09.2026).
 *
 * $o: vorn (Markup), text, klein, plaketten (Markup), aktionen (Markup),
 *     href, href_ganz, klasse, attr, daten (Liste von [Schluessel, Wert]),
 *     aktionsspalte (bool)
 * ------------------------------------------------------------------------ */
function ui_zeile(array $o): void
{
    $daten = (array)($o['daten'] ?? []);
    if ($daten !== []) {
        ui_zeile_mehr($o, $daten);
        return;
    }
    $ganz = trim((string)($o['href_ganz'] ?? ''));
    $k = 'zeile' . (!empty($o['klasse']) ? ' ' . (string)$o['klasse'] : '');
    /* `attr` wie bei ui_knopf() und ui_aktionen(): fertige Attribute, die der
     * Aufrufer anhaengt — etwa `data-…` und `tabindex` fuer eine Zeile, die
     * mit etwas anderem auf der Seite verknuepft ist (S2/AP4, tag_spuren.php).
     * Keine neue Darstellung, nur dieselbe Zusatzoption an einem dritten
     * Baustein. */
    echo ($ganz !== ''
        ? '<a class="' . $k . '" href="' . ui_e($ganz) . '"'
        : '<div class="' . $k . '"') . (string)($o['attr'] ?? '') . '>' . "\n";
    /* VORN steht, was VOR dem Text gehört (O9b): in der NutzerInnen-Liste das
     * Auswahlkästchen. Es gehört nicht zu den Aktionen rechts — es wählt die
     * Zeile aus, statt an ihr zu handeln, und in der Tabellenfassung derselben
     * Liste steht es ebenfalls in der ersten Spalte. Fertiges Markup. */
    if (!empty($o['vorn'])) {
        echo '  <div class="zeile-vorn">' . (string)$o['vorn'] . "</div>\n";
    }
    echo '  <div class="zeile-text">' . "\n";
    $t = '<span class="zeile-haupt">' . ui_e((string)($o['text'] ?? '')) . '</span>';
    echo '    ' . (!empty($o['href']) && $ganz === ''
        ? '<a href="' . ui_e((string)$o['href']) . '">' . $t . '</a>'
        : $t) . "\n";
    if (!empty($o['klein'])) {
        echo '    <span class="zeile-klein">' . ui_e((string)$o['klein']) . "</span>\n";
    }
    echo "  </div>\n";
    if (!empty($o['plaketten'])) {
        echo '  <div class="zeile-plaketten">' . (string)$o['plaketten'] . "</div>\n";
    }
    if ($ganz !== '') {
        echo '  <div class="zeile-aktionen">'
           . ui_symbol('winkel', 'symbol-rechts zeile-weiter') . "</div>\n";
    } elseif (!empty($o['aktionen'])) {
        echo '  <div class="zeile-aktionen">' . (string)$o['aktionen'] . "</div>\n";
    } elseif (!empty($o['aktionsspalte'])) {
        echo '  <div class="zeile-aktionen"></div>' . "\n";
    }
    echo ($ganz !== '' ? "</a>\n" : "</div>\n");
}

/**
 * Die aufklappbare Fassung von `ui_zeile()` — siehe dort.
 *
 * DAS `<summary>` IST IMMER ERSTES KIND seines `<details>`. `.zeile:first-child`
 * naehme ihm deshalb den oberen Innenabstand, und aufklappbare Zeilen waeren
 * 13 px niedriger als die uebrigen (F-P5c-13, gemessen in M-P5c-01).
 * `style.css` setzt die Gegenregel an `.zeile-mehr > summary.zeile`.
 */
function ui_zeile_mehr(array $o, array $daten): void
{
    $k = 'zeile' . (!empty($o['klasse']) ? ' ' . (string)$o['klasse'] : '');
    echo '<details class="zeile-mehr"' . (string)($o['attr'] ?? '') . '>'
       . '<summary class="' . $k . '">' . "\n";
    echo '  <div class="zeile-text">' . "\n";
    echo '    <span class="zeile-haupt">' . ui_e((string)($o['text'] ?? '')) . "</span>\n";
    if (!empty($o['klein'])) {
        echo '    <span class="zeile-klein">' . ui_e((string)$o['klein']) . "</span>\n";
    }
    echo "  </div>\n";
    if (!empty($o['plaketten'])) {
        echo '  <div class="zeile-plaketten">' . (string)$o['plaketten'] . "</div>\n";
    }
    echo '  <div class="zeile-aktionen">' . ui_symbol('winkel', 'zeile-winkel') . "</div>\n";
    echo "</summary>\n" . '<dl class="zeile-daten">';
    foreach ($daten as [$schluessel, $wert]) {
        echo '<dt>' . ui_e((string)$schluessel) . '</dt><dd>' . ui_e((string)$wert) . '</dd>';
    }
    echo "</dl></details>\n";
}


/* ---------------------------------------------------------------------------
 * REITER  (.reiter-rahmen, .reiter, .reiter-punkt, .reiter-abgesetzt)
 *                                          P5c/AP2, E-P5c-25, Bild M-P5c-01a
 *
 * Wechsel zwischen gleichrangigen Sichten EINER Seite — serverseitig, jeder
 * Reiter ist ein Verweis. Orange unterstrichen wie der aktive Punkt der
 * Kopfleiste: „hier stehst du". Drei Verwender sind geplant: Protokoll (hier
 * entstanden), Statistik (AP7), Rechtstexte (AP9).
 *
 * WARUM NICHT DAS SEGMENT UND NICHT DIE FILTERPILLEN (M-P5c-01a, geprüft und
 * verworfen): Das Segment ist fuer wenige kurze Moeglichkeiten, sieben Woerter
 * passen bei 400 px nicht. Die Pillen stehen eine Zeile tiefer als
 * Zeitraumfilter — zwei Reihen gleich aussehender Pillen mit verschiedener
 * Bedeutung waeren die Verwechslung.
 *
 * UNTER 720 px ROLLT DIE REIHE IN IHREM EIGENEN BEHAELTER; die Seite laeuft
 * nie waagerecht aus dem Bild. `assets/reiter.js` holt den aktiven Reiter ins
 * Bild und setzt den Verlauf am Rand — ohne Skript rollt die Reihe trotzdem.
 *
 * SEITEN MIT REITERN TRAGEN KEINE UNTERPUNKTE IN DER LEISTE: `menue.js` baut
 * keine, wenn `#inhalt` eine `.reiter`-Reihe enthaelt (E-P5c-25).
 *
 * $o: label (fuer die Vorlesesoftware), punkte [ ['text', 'href',
 *     'aktiv' => bool, 'abgesetzt' => bool (am rechten Rand, fuer eine
 *     Ablage wie „Archiv"), 'attr' => fertige Attribute] ]
 * ------------------------------------------------------------------------ */
function ui_reiter(array $o): void
{
    $punkte = (array)($o['punkte'] ?? []);
    if (!$punkte) { return; }
    echo '<div class="reiter-rahmen"><nav class="reiter" aria-label="'
       . ui_e((string)($o['label'] ?? 'Bereiche dieser Seite')) . '">';
    foreach ($punkte as $p) {
        $k = 'reiter-punkt' . (!empty($p['abgesetzt']) ? ' reiter-abgesetzt' : '')
           . (!empty($p['aktiv']) ? ' aktiv' : '');
        echo '<a class="' . $k . '" href="' . ui_e((string)$p['href']) . '"'
           . (!empty($p['aktiv']) ? ' aria-current="page"' : '')
           . (string)($p['attr'] ?? '') . '>' . ui_e((string)$p['text']) . '</a>';
    }
    echo "</nav></div>\n";
    echo '<script src="' . ui_e(ui_asset('assets/reiter.js')) . '" defer></script>' . "\n";
}


/* ---------------------------------------------------------------------------
 * LISTENKOPF UND LISTENFUSS  (.listenkopf, .listensuche, .filterreihe,
 * .listenfilter · .listenfuss, .listenzahl, .seitenwahl, .seitenknopf)
 *                                                P5c/AP2, R83, F-P5c-54
 *
 * BIS WEB 20.38.0 STANDEN SUCHFELD, FILTERPILLEN UND SEITENWAHL NUR ALS
 * HANDGESCHRIEBENES MARKUP IN `admin_users.php`. Die Protokollseite waere die
 * zweite Kopie gewesen — und zwei Kopien derselben Seitenwahl laufen
 * auseinander, sobald eine von beiden eine Ellipse mehr bekommt. Jetzt ist
 * es EIN Weg; das Register zaehlt die Klassen ausserhalb dieser Datei und
 * haelt die Zahl auf null.
 *
 * ui_listenkopf($o):
 *   form_id    Kennung des Suchformulars (fuer `form=` am Auswahlfeld)
 *   suche      ['name' => 'q', 'wert', 'label', 'platzhalter']
 *   versteckt  [Name => Wert] — was die Suche mitnehmen soll (Filter,
 *              Sortierung); leere Werte fallen weg
 *   filter     [ ['text', 'href', 'aktiv' => bool, 'zahl' => ?int,
 *              'kreuz' => bool] ] — `kreuz` fuer einen Filter aus der
 *              Adresse, der sich zuruecknehmen laesst (Kontofilter der
 *              Protokollseite): aktive Pille mit Kreuz, der Verweis nimmt
 *              ihn weg
 *   auswahl    optional ['name', 'label', 'wert', 'optionen' => [Wert =>
 *              Text]] — ein Auswahlfeld in der Filterreihe; mit Skript
 *              (`assets/listenkopf.js`) schickt es sofort ab, ohne steht
 *              ein Knopf „Filtern" daneben
 * ------------------------------------------------------------------------ */
function ui_listenkopf(array $o): void
{
    $id = (string)($o['form_id'] ?? 'f-suche');
    $s  = (array)($o['suche'] ?? []);
    $name = (string)($s['name'] ?? 'q');
    /* Die Kennung des Feldes ist sein Name, wie in `admin_users.php` bis Web
     * 20.38.0 — eine andere Kennung braeche jeden Verweis darauf, still. */
    $fid0 = (string)($s['id'] ?? $name);
    echo '<div class="listenkopf">' . "\n";
    echo '  <form method="get" class="listensuche" role="search" id="' . ui_e($id) . '">' . "\n";
    foreach ((array)($o['versteckt'] ?? []) as $n => $v) {
        if ((string)$v === '') { continue; }
        echo '    <input type="hidden" name="' . ui_e((string)$n) . '" value="'
           . ui_e((string)$v) . '">' . "\n";
    }
    echo '    <label class="nur-vorlesen" for="' . ui_e($fid0) . '">'
       . ui_e((string)($s['label'] ?? 'Suchen')) . "</label>\n";
    echo '    <div class="suchfeld">' . ui_symbol('lupe', 'suchfeld-lupe')
       . '<input type="search" id="' . ui_e($fid0) . '" name="' . ui_e($name)
       . '" value="' . ui_e((string)($s['wert'] ?? '')) . '" placeholder="'
       . ui_e((string)($s['platzhalter'] ?? '')) . '" autocomplete="off"></div>' . "\n";
    echo '    <button class="knopf knopf-neutral nur-vorlesen" type="submit">Suchen</button>' . "\n";
    echo "  </form>\n";

    $filter = (array)($o['filter'] ?? []);
    $auswahl = $o['auswahl'] ?? null;
    if ($filter || $auswahl) {
        echo '  <div class="filterreihe">' . "\n";
        foreach ($filter as $f) {
            $aktiv = !empty($f['aktiv']);
            echo '    <a class="listenfilter' . ($aktiv ? ' aktiv' : '') . '" href="'
               . ui_e((string)$f['href']) . '"' . ($aktiv ? ' aria-current="true"' : '')
               . '><span>' . ui_e((string)$f['text']) . '</span>'
               . (isset($f['zahl']) && $f['zahl'] !== null
                   ? '<span class="listenfilter-zahl">' . (int)$f['zahl'] . '</span>' : '')
               . (!empty($f['kreuz']) ? ui_symbol('schliessen', '', 'Filter entfernen') : '')
               . "</a>\n";
        }
        if (is_array($auswahl)) {
            $an = (string)$auswahl['name'];
            $fid = $id . '-' . $an;
            echo '    <label class="nur-vorlesen" for="' . ui_e($fid) . '">'
               . ui_e((string)($auswahl['label'] ?? '')) . "</label>\n";
            echo '    <select id="' . ui_e($fid) . '" name="' . ui_e($an) . '" form="'
               . ui_e($id) . '" class="feld-eingabe" data-absenden>';
            foreach ((array)$auswahl['optionen'] as $w => $t) {
                echo '<option value="' . ui_e((string)$w) . '"'
                   . ((string)$w === (string)($auswahl['wert'] ?? '') ? ' selected' : '') . '>'
                   . ui_e((string)$t) . '</option>';
            }
            echo "</select>\n";
            echo '    ' . ui_knopf(['text' => 'Filtern', 'art' => 'neutral', 'typ' => 'submit',
                                   'attr' => ' form="' . ui_e($id) . '" data-absenden-knopf'])
               . "\n";
            echo '    <script src="' . ui_e(ui_asset('assets/listenkopf.js')) . '" defer></script>' . "\n";
        }
        echo "  </div>\n";
    }
    echo "</div>\n";
}

/**
 * Der Fuss einer langen Liste: die Zaehlung und, ab zwei Seiten, die
 * Seitenwahl.
 *
 * ERSTE, LETZTE UND DIE NACHBARN DER AKTUELLEN SEITE; dazwischen eine
 * Ellipse. Bei sieben Seiten stehen alle da, bei siebzig nicht — eine
 * Leiste, die mit dem Bestand waechst, ist keine Leiste.
 *
 * $o: zahl (fertiger Satz, z. B. „Konten 1–50 von 312"), seite, seiten,
 *     weg (callable: int $seite => Adresse)
 */
function ui_listenfuss(array $o): void
{
    $seite  = max(1, (int)($o['seite'] ?? 1));
    $seiten = max(1, (int)($o['seiten'] ?? 1));
    $weg    = $o['weg'];
    echo '<div class="listenfuss">' . "\n";
    echo '  <p class="listenzahl">' . ui_e((string)($o['zahl'] ?? '')) . "</p>\n";
    if ($seiten > 1) {
        echo '  <nav class="seitenwahl" aria-label="Seiten">' . "\n";
        echo '    <a class="seitenknopf' . ($seite <= 1 ? ' aus' : '') . '" '
           . ($seite > 1 ? 'href="' . ui_e($weg($seite - 1)) . '"' : 'aria-disabled="true"')
           . ' aria-label="Vorige Seite">' . ui_symbol('winkel', 'symbol-links') . "</a>\n";
        $zeigen = [1, $seiten, $seite, $seite - 1, $seite + 1];
        $zeigen = array_values(array_unique(array_filter($zeigen,
            static fn($n) => $n >= 1 && $n <= $seiten)));
        sort($zeigen);
        $vorher = 0;
        foreach ($zeigen as $n) {
            if ($vorher && $n > $vorher + 1) {
                echo '    <span class="seitenluecke" aria-hidden="true">…</span>' . "\n";
            }
            $vorher = $n;
            echo '    <a class="seitenknopf' . ($n === $seite ? ' aktiv' : '') . '" href="'
               . ui_e($weg($n)) . '"' . ($n === $seite ? ' aria-current="page"' : '') . '>'
               . $n . "</a>\n";
        }
        echo '    <a class="seitenknopf' . ($seite >= $seiten ? ' aus' : '') . '" '
           . ($seite < $seiten ? 'href="' . ui_e($weg($seite + 1)) . '"' : 'aria-disabled="true"')
           . ' aria-label="Nächste Seite">' . ui_symbol('winkel', 'symbol-rechts') . "</a>\n";
        echo "  </nav>\n";
    }
    echo "</div>\n";
}


/* ---------------------------------------------------------------------------
 * TITELZEILE  (.titelzeile)
 *
 * Rückweg, Titel, Unterzeile, Aktionen rechts — der Kopf fast jeder Seite.
 *
 * $o: zurueck ['text','href'], titel, titel_mobil, unter, aktionen (Markup)
 * ------------------------------------------------------------------------ */
function ui_titelzeile(array $o): void
{
    ?>
<div class="titelzeile">
  <?php if (!empty($o['zurueck'])): ?>
    <a class="rueckweg" href="<?= ui_e((string)$o['zurueck']['href']) ?>">
      <?= ui_symbol('winkel', 'symbol-links') ?><span><?= ui_e((string)$o['zurueck']['text']) ?></span>
    </a>
  <?php endif; ?>
  <?php /* Die Unterzeile steht NACH der Hauptzeile, nicht im Flex-Block:
           Sonst bestimmt ihre Breite die des Titelblocks, und die Aktionen
           brechen unter einen kurzen Titel („Einsatz 1"), obwohl neben ihm
           Platz ist (Fund aus O4, Mockups 02/19). */ ?>
  <div class="titelzeile-haupt">
    <div class="titelzeile-text">
      <h1<?= !empty($o['titel_mobil']) ? ' data-mobil="' . ui_e((string)$o['titel_mobil']) . '"' : '' ?>><?= ui_e((string)($o['titel'] ?? '')) ?></h1>
    </div>
    <?php if (!empty($o['aktionen'])): ?>
      <div class="titelzeile-aktionen"><?= (string)$o['aktionen'] ?></div>
    <?php endif; ?>
  </div>
  <?php if (!empty($o['unter'])): ?>
    <p class="titelzeile-unter"><?= (string)$o['unter'] ?></p>
  <?php endif; ?>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * AKTIONSMENÜ  (.aktionen) und BLATT  (.blatt)
 *
 * Mobil ein „⋯" neben dem Titel, das ein Blatt von unten öffnet: Griff,
 * Titel, große Zeilen zu 50 px, „Löschen" rot und durch eine Linie
 * abgesetzt, „Abbrechen". Am Desktop derselbe Vorrat als „Aktionen ▾" in
 * einem Aufklappmenü. Der Anlegen-Weg steht auch dort als erste Zeile
 * (E-P3-27).
 *
 * EIN Markup für beide Formen; assets/blatt.js entscheidet nichts, es öffnet
 * und schließt nur. Welche Form erscheint, sagt das Stylesheet.
 *
 * $o: titel, eintraege [ ['text','href','symbol','gefahr'=>bool,'attr'] ]
 * ------------------------------------------------------------------------ */
function ui_aktionen(array $o): string
{
    /* Eine feste Kennung ('id'), wenn die Seite das Blatt ansprechen will —
     * etwa um seinen Titel nachzutragen („Diensttag 22.08.2026"). Sonst eine
     * aus dem Inhalt abgeleitete. */
    $id = (string)($o['id'] ?? ('aktionen-' . substr(sha1((string)($o['titel'] ?? '')
        . count((array)($o['eintraege'] ?? []))), 0, 8)));
    $m  = '<div class="aktionen">';
    $m .= '<button type="button" class="knopf knopf-neutral aktionen-knopf" '
        . 'aria-expanded="false" aria-controls="' . $id . '" data-blatt="' . $id . '">'
        . ui_symbol('punkte', 'symbol-gross nur-schmal')
        . '<span class="nur-breit">Aktionen</span>'
        . ui_symbol('winkel', 'nur-breit') . '</button>';
    $m .= '<div class="blatt" id="' . $id . '" hidden>';
    $m .= '<div class="blatt-griff" aria-hidden="true"></div>';
    $m .= '<h2 class="blatt-titel">' . ui_e((string)($o['titel'] ?? 'Aktionen')) . '</h2>';
    $m .= '<div class="blatt-liste">';
    foreach ((array)($o['eintraege'] ?? []) as $e) {
        $k = 'blatt-zeile' . (!empty($e['gefahr']) ? ' blatt-gefahr' : '')
           . (!empty($e['anlegen']) ? ' blatt-anlegen' : '');
        $inhalt = (!empty($e['symbol']) ? ui_symbol((string)$e['symbol']) : '')
                . '<span>' . ui_e((string)($e['text'] ?? '')) . '</span>';
        $attr = !empty($e['attr']) ? ' ' . (string)$e['attr'] : '';
        /* EIN EINTRAG KANN AUCH EINE HANDLUNG SEIN, nicht nur ein Weg (O9).
         * „Setz-Link senden" auf der Kontoseite ist ein POST — als
         * <a href> wäre es entweder wirkungslos oder ein Zustandswechsel auf
         * einen GET hin, und genau das ist an anderer Stelle schon einmal
         * teuer geworden (update.php, Kopf „Zweistufiger Ablauf").
         *
         * Der Knopf verweist über `form` auf ein Formular an anderer Stelle
         * der Seite — dasselbe Verfahren wie in ui_zeilenaktionen(): Ein
         * <form> um den Eintrag herum ginge nicht, weil das Blatt selbst in
         * einem Formular stehen kann. `button.blatt-zeile` trägt seit O5
         * dieselbe Gestalt wie der Verweis (Stylesheet, Abschnitt 23). */
        if (!empty($e['form'])) {
            $m .= '<button type="submit" class="' . $k . '" form="'
                . ui_e((string)$e['form']) . '"' . $attr . '>' . $inhalt . '</button>';
            continue;
        }
        $m .= '<a class="' . $k . '" href="' . ui_e((string)($e['href'] ?? '#')) . '"'
            . $attr . '>' . $inhalt . '</a>';
    }
    $m .= '</div>';
    $m .= '<button type="button" class="knopf knopf-leise blatt-abbrechen" data-blatt-zu>'
        . '<span>Abbrechen</span></button>';
    $m .= '</div></div>';
    return $m;
}


/* ---------------------------------------------------------------------------
 * FELD  (.feld)
 *
 * Beschriftung oben, Eingabe 44 px hoch, blauer Fokusring, optionale
 * Kleinzeile darunter. Reihen zu zweit oder dritt entstehen über
 * `.feld-reihe` um mehrere Felder.
 *
 * Die Beschriftung steht in NORMALSCHRIFT. Im Bestand waren Feldnamen,
 * Tabellenköpfe und Legenden gesperrte Versalien — das prägende Stilmittel
 * und zugleich das, was auf 360 px am meisten Breite kostete (E-P3-21).
 *
 * $o: name, label, wert, art (text|date|number|email|password|file|select|
 *     textarea), optionen, klein, pflicht, attr, klasse, platzhalter
 *
 * Die vollständige Liste der Schlüssel steht unten in der Funktion; diese
 * hier nennt die häufigen. Beide führen dieselben Arten — sie sind schon
 * einmal auseinandergelaufen: `file` fehlte, `time` stand da, ohne dass es
 * irgendwo benutzt wird.
 * ------------------------------------------------------------------------ */
function ui_feld(array $o): void
{
    /* $o: name, id, label, label_zusatz (gedämpft, in der Beschriftung),
     *     art (text|email|number|date|password|file|select|textarea), wert,
     *     optionen, zeilen (nur textarea, Vorgabe 3), platzhalter, pflicht,
     *     klein, klasse (an der HÜLLE .feld, nicht am Eingabefeld), attr
     *
     * `password` und `file` fehlten in dieser Aufzählung, obwohl beide seit
     * Langem benutzt werden (14- bzw. 2-mal). Eine Liste, die sich für
     * vollständig ausgibt und es nicht ist, ist schlechter als keine.
     * `file` trägt zusätzlich eine eigene Regel im Stylesheet
     * (input[type=file].feld-eingabe) — ohne sie hängt der native Knopf
     * am oberen Feldrand. */
    $name = (string)($o['name'] ?? '');
    $id   = (string)($o['id'] ?? ($name !== '' ? 'f-' . preg_replace('/[^\w-]/', '-', $name) : ''));
    $art  = (string)($o['art'] ?? 'text');
    $attr = (string)($o['attr'] ?? '');
    if (!empty($o['pflicht'])) { $attr .= ' required'; }
    if (!empty($o['platzhalter'])) { $attr .= ' placeholder="' . ui_e((string)$o['platzhalter']) . '"'; }
    ?>
<div class="feld<?= !empty($o['klasse']) ? ' ' . ui_e((string)$o['klasse']) : '' ?>">
  <?php if (isset($o['label'])): ?>
    <label class="feld-label" for="<?= ui_e($id) ?>"><?= ui_e((string)$o['label']) ?><?php
      if (!empty($o['pflicht'])): ?> <span class="feld-pflicht" aria-hidden="true">*</span><?php endif;
      /* EIN GEDAEMPFTER ZUSATZ IN DER BESCHRIFTUNG (O10, Mockup 35):
         „Text (Markdown: Überschriften, Absätze, Listen, Links)". Er gehört
         zur Beschriftung, nicht unter das Feld — dort steht schon `klein`.
         Als eigener Schlüssel und nicht als Markup in `label`: Der Wert
         geht durch ui_e(), und das soll so bleiben. */
      if (!empty($o['label_zusatz'])): ?> <span class="feld-klein-inline"><?= ui_e((string)$o['label_zusatz']) ?></span><?php endif; ?></label>
  <?php endif; ?>
  <?php if ($art === 'select'): ?>
    <select class="feld-eingabe" id="<?= ui_e($id) ?>" name="<?= ui_e($name) ?>"<?= $attr ?>>
      <?php foreach ((array)($o['optionen'] ?? []) as $wert => $text): ?>
        <option value="<?= ui_e((string)$wert) ?>"
          <?= (string)$wert === (string)($o['wert'] ?? '') ? 'selected' : '' ?>><?= ui_e((string)$text) ?></option>
      <?php endforeach; ?>
    </select>
  <?php elseif ($art === 'textarea'): ?>
    <textarea class="feld-eingabe feld-mehrzeilig" id="<?= ui_e($id) ?>"
              name="<?= ui_e($name) ?>" rows="<?= (int)($o['zeilen'] ?? 3) ?>"<?= $attr ?>><?= ui_e((string)($o['wert'] ?? '')) ?></textarea>
  <?php else: ?>
    <input class="feld-eingabe" type="<?= ui_e($art) ?>" id="<?= ui_e($id) ?>"
           name="<?= ui_e($name) ?>" value="<?= ui_e((string)($o['wert'] ?? '')) ?>"<?= $attr ?>>
  <?php endif; ?>
  <?php if (!empty($o['klein'])): ?>
    <p class="feld-klein"><?= ui_e((string)$o['klein']) ?></p>
  <?php endif; ?>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * SCHALTER  (.schalter)
 *
 * Ja/Nein-Felder werden Schalter in 44-px-Zeilen: Beschriftung links, an in
 * Orange. Abhängige Felder klappen darunter auf, eingerückt mit orangem
 * Randstrich (E-P3-28).
 *
 * Gebaut aus einer echten Checkbox — die Tastaturbedienung, der
 * Vorlesezustand und das Absenden im Formular kommen damit vom Browser und
 * nicht aus einem Skript.
 *
 * $o: name, label, an (bool), klein, wert, attr, id
 * ------------------------------------------------------------------------ */
function ui_schalter(array $o): void
{
    $name = (string)($o['name'] ?? '');
    $id   = (string)($o['id'] ?? 'sw-' . preg_replace('/[^\w-]/', '-', $name));
    ?>
<div class="schalter">
  <input type="checkbox" class="schalter-box" id="<?= ui_e($id) ?>"
         name="<?= ui_e($name) ?>" value="<?= ui_e((string)($o['wert'] ?? '1')) ?>"
         <?= !empty($o['an']) ? 'checked' : '' ?><?= (string)($o['attr'] ?? '') ?>>
  <label class="schalter-label" for="<?= ui_e($id) ?>">
    <span class="schalter-text"><?= ui_e((string)($o['label'] ?? '')) ?>
      <?php if (!empty($o['klein'])): ?><span class="schalter-klein"><?= ui_e((string)$o['klein']) ?></span><?php endif; ?>
    </span>
    <span class="schalter-griff" aria-hidden="true"></span>
  </label>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * SEGMENTWAHL  (.segment)
 *
 * Tastenreihe mit orangem Aktivzustand — für Gemischt/Luft/Boden,
 * egal/ja/nein und die Wochentage. Mobil vollbreit (E-P3-30).
 *
 * Als Radiogruppe gebaut, nicht als Knopfreihe mit Skript: Pfeiltasten,
 * Vorlesezustand und Absenden kommen damit vom Browser.
 *
 * $o: name, wert, optionen [wert => text], klasse, attr
 * ------------------------------------------------------------------------ */
function ui_segment(array $o): void
{
    echo ui_segment_markup($o);
}

/**
 * Dieselbe Segmentwahl als Zeichenkette (O9c).
 *
 * WARUM BEIDES: `ui_segment()` gibt aus und passt damit ueberall dorthin, wo
 * ein Formular Zeile fuer Zeile geschrieben wird. Die Titelzeile
 * (`ui_titelzeile`) dagegen NIMMT Markup entgegen — sie setzt es zwischen
 * Ueberschrift und Aktionen. Wie bei `ui_meldung` / `ui_meldung_markup` ist
 * die ausgebende Fassung die kurze, und das Markup entsteht an einer Stelle.
 */
function ui_segment_markup(array $o): string
{
    ob_start();
    $name = (string)($o['name'] ?? '');
    ?>
<div class="segment<?= !empty($o['klasse']) ? ' ' . ui_e((string)$o['klasse']) : '' ?>"
     <?= !empty($o['id']) ? 'id="' . ui_e((string)$o['id']) . '" ' : '' ?>role="group"<?=
        !empty($o['label']) ? ' aria-label="' . ui_e((string)$o['label']) . '"' : '' ?>>
  <?php $i = 0; foreach ((array)($o['optionen'] ?? []) as $wert => $text):
      $id = 'sg-' . preg_replace('/[^\w-]/', '-', $name . '-' . $wert . '-' . $i++); ?>
    <input type="radio" class="segment-box" id="<?= ui_e($id) ?>" name="<?= ui_e($name) ?>"
           value="<?= ui_e((string)$wert) ?>"
           <?= (string)$wert === (string)($o['wert'] ?? '') ? 'checked' : '' ?><?= (string)($o['attr'] ?? '') ?>>
    <label class="segment-taste" for="<?= ui_e($id) ?>"><?= ui_e((string)$text) ?></label>
  <?php endforeach; ?>
</div>
<?php return (string)ob_get_clean();
}


/* ---------------------------------------------------------------------------
 * SPEICHERN-LEISTE  (.speichern)
 *
 * Klebt am unteren Rand und erscheint, sobald das Formular schmutzig ist —
 * das Dirty-Tracking dafür liegt seit Web 7.0.0 in assets/forms.js
 * (`data-dirty-track`). Mobil ein breiter Primärknopf; am Desktop der Knopf
 * links plus der Hinweis „Es gibt ungespeicherte Änderungen · Strg + Enter
 * speichert".
 *
 * KEIN „VERWERFEN". Der Rückweg oben genügt, und ein Verwerfen-Knopf neben
 * einem Speichern-Knopf ist die Stelle, an der man sich vergreift (E-P3-29).
 *
 * $o: text, hinweis, name, wert, attr
 * ------------------------------------------------------------------------ */
/* ---------------------------------------------------------------------------
 * WAHLLISTE  (.wahlliste)
 *
 * Eine Reihe großer Auswahlzeilen mit Radio links, Beschriftung und einem
 * gedämpften Zusatz rechts; die gewählte Zeile wird hell orange mit orangem
 * Rahmen. Freigegeben mit Mockup 13 (Logo-Wahl, E-P3-20).
 *
 * WARUM NICHT DIE SEGMENTWAHL: Ein Segment trägt kurze Wörter nebeneinander
 * („Gemischt / Luft / Boden"). Hier stehen vier Zeilen mit Erklärung
 * daneben („Standard der Installation — zurzeit Hubschrauber"), und die
 * längste ist breiter als ein Viertel des Bildschirms. Untereinander mit
 * 44 px Höhe ist das auf jedem Gerät zu treffen.
 *
 * WARUM NICHT DER SCHALTER: Der schaltet EINES ein oder aus. Hier ist eine
 * aus vieren zu wählen — das ist eine Radiogruppe, und der Browser bringt
 * die Pfeiltastenbedienung dafür mit.
 *
 * $o: name, wert, optionen [ wert => ['text', 'zusatz'] ], label, attr
 * ------------------------------------------------------------------------ */
function ui_wahlliste(array $o): void
{
    $name = (string)($o['name'] ?? '');
    ?>
<div class="wahlliste" role="radiogroup"<?=
    !empty($o['label']) ? ' aria-label="' . ui_e((string)$o['label']) . '"' : '' ?>>
  <?php $i = 0; foreach ((array)($o['optionen'] ?? []) as $wert => $eintrag):
      $text   = is_array($eintrag) ? (string)($eintrag['text'] ?? '') : (string)$eintrag;
      $zusatz = is_array($eintrag) ? (string)($eintrag['zusatz'] ?? '') : '';
      $id     = 'wl-' . preg_replace('/[^\w-]/', '-', $name . '-' . $wert . '-' . $i++);
      $an     = (string)$wert === (string)($o['wert'] ?? ''); ?>
    <input type="radio" class="wahl-box" id="<?= ui_e($id) ?>" name="<?= ui_e($name) ?>"
           value="<?= ui_e((string)$wert) ?>"<?= $an ? ' checked' : '' ?><?= (string)($o['attr'] ?? '') ?>>
    <label class="wahl-zeile" for="<?= ui_e($id) ?>">
      <span class="wahl-punkt" aria-hidden="true"></span>
      <span class="wahl-text"><?= ui_e($text) ?></span>
      <?php if ($zusatz !== ''): ?>
        <span class="wahl-zusatz"><?= ui_e($zusatz) ?></span>
      <?php endif; ?>
    </label>
  <?php endforeach; ?>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * ZEILENAKTIONEN  (.zeile-aktionen)  — E-P3-26
 *
 * Am Schreibtisch stehen die Handlungen einer Zeile als Knöpfe nebeneinander
 * (Mockup 08); unter 720 px steht dort EIN „⋯", das ein Aktionsblatt öffnet
 * (Mockup 07). Ein Dutzend Knöpfe untereinander wäre auf dem Handy eine
 * Bildschirmlänge je Zeile.
 *
 * FORMULARE STEHEN NUR EINMAL IM MARKUP. Die meisten Zeilenaktionen sind
 * POSTs mit Token (löschen, Vorbelegung setzen) — sie zweimal auszugeben,
 * einmal für den Knopf und einmal für das Blatt, wäre dieselbe Handlung an
 * zwei Stellen, und die nächste Änderung käme nur an einer an. Stattdessen
 * trägt der Eintrag die `form`-Kennung: HTML erlaubt einem Knopf, ein
 * Formular abzusenden, in dem er gar nicht steht. Die Seite gibt das
 * Formular einmal versteckt aus, beide Knöpfe zeigen darauf.
 *
 * $o: titel (für das Blatt), eintraege [ ['text','symbol','art','href'|'form','attr'] ]
 *     art: 'neutral' (Vorgabe) | 'gefahr' | 'leise' | 'leise-orange'
 * ------------------------------------------------------------------------ */
function ui_zeilenaktionen(array $o): string
{
    $eintraege = (array)($o['eintraege'] ?? []);
    if ($eintraege === []) { return ''; }

    /* Ein Knopf oder Link je Eintrag — dieselbe Bauart für beide Formen,
     * damit Blatt und Knopfreihe nicht auseinanderlaufen können. */
    $knopf = static function (array $e, string $klasse) : string {
        $art  = (string)($e['art'] ?? 'neutral');
        /* ZWEI VOKABELN FÜR DIESELBE SACHE, und sie sind nicht austauschbar
         * (O11). Die Knopfreihe am Desktop kennt `knopf-gefahr`, das Blatt
         * kennt `blatt-gefahr` — und die Blattzeile setzt ihre Schriftfarbe
         * SELBST (`.blatt-zeile{color:var(--asphalt)}`, Stylesheet
         * Abschnitt 11). Beide Regeln haben Spezifität (0,1,0); die spätere
         * gewinnt, und das ist `.blatt-zeile`.
         *
         * Bis Web 9.11.0 bekam die Blattzeile deshalb `knopf-gefahr` und sah
         * aus wie jede andere: kein Rot, dunkelblaues Symbol statt rotem,
         * keine abgesetzte Trennlinie. Gemessen an „Löschen" in der
         * Stammdatenliste: rgb(26,5,0) — dieselbe Farbe wie „Bearbeiten".
         *
         * Betroffen waren sechs abgenommene Aufrufstellen, darunter
         * „Gerät entkoppeln" und „Konto löschen". Am Schreibtisch stimmte
         * alles, weil `.knopf` keine Farbe setzt; mobil sah die
         * unumkehrbarste Handlung der Anwendung harmlos aus. Aufgefallen
         * ist es niemandem, weil die Bildaufnahme kein Blatt öffnet. */
        $blatt = str_contains($klasse, 'blatt-zeile');
        $k   = $klasse . ' ' . match ($art) {
            'gefahr'       => $blatt ? 'blatt-gefahr' : 'knopf-gefahr',
            'leise'        => $blatt ? '' : 'knopf-leise',
            'leise-orange' => $blatt ? 'blatt-anlegen' : 'knopf-leise knopf-leise-orange',
            default        => $blatt ? '' : 'knopf-neutral',
        };
        $k = rtrim($k);
        $inhalt = (!empty($e['symbol']) ? ui_symbol((string)$e['symbol']) : '')
                . '<span>' . ui_e((string)($e['text'] ?? '')) . '</span>';
        $attr = (string)($e['attr'] ?? '');
        if (!empty($e['href'])) {
            return '<a class="' . $k . '" href="' . ui_e((string)$e['href']) . '"' . $attr . '>'
                 . $inhalt . '</a>';
        }
        /* `form` statt eines eigenen <form> um den Knopf: siehe Kopf. */
        return '<button type="submit" class="' . $k . '"'
             . (!empty($e['form']) ? ' form="' . ui_e((string)$e['form']) . '"' : '')
             . $attr . '>' . $inhalt . '</button>';
    };

    /* LAUFENDE NUMMER, KEIN HASH AUS DEM INHALT. Ein Hash über Titel und
     * Aktionstexte kollidiert, sobald zwei Zeilen dasselbe heißen und
     * dieselben Handlungen tragen — und genau das ist in einer
     * Stammdatenliste der Normalfall, nicht die Ausnahme (zwei Standorte mit
     * einer gleichnamigen Zielklinik). Zwei Blätter mit derselben Kennung
     * öffnet `data-blatt` beide oder keines.
     *
     * `id` von außen setzen kann jede Aufrufstelle weiterhin; die Nummer ist
     * nur der Rückfall. */
    static $lfd = 0;
    $id = (string)($o['id'] ?? ('za-' . (++$lfd)));

    /* Desktop: die Knöpfe. Mobil: das „⋯" und dasselbe wieder im Blatt.
     *
     * `blatt_immer` LÄSST DIE KNOPFREIHE WEG (S8/AP6, Mockup 10, freigegeben):
     * Dann steht der Punkte-Knopf in jeder Breite, und ab 1024 px klappt
     * daran das Aufklappmenü auf — dasselbe Markup, das Stylesheet entscheidet.
     *
     * WOFÜR. Die Geräteliste trägt drei Handlungen, von denen eine
     * unumkehrbar ist. Als Knopfreihe stünde „Entkoppeln" in Rot unmittelbar
     * neben „Deaktivieren", und zwar in jeder Zeile — drei Knöpfe mal drei
     * Geräte sind neun Ziele für drei Wege. Im Menü liegt die gefährliche
     * Handlung eine Ebene tiefer, abgesetzt und rot.
     *
     * Es bleibt die Ausnahme: Wo die Handlungen harmlos und häufig sind
     * (Stammdaten, Papierkorb), ist die Knopfreihe der schnellere Weg. */
    $m = '';
    if (empty($o['blatt_immer'])) {
        $m .= '<div class="zeile-knoepfe nur-ab-720">';
        foreach ($eintraege as $e) { $m .= $knopf($e, 'knopf'); }
        $m .= '</div>';
    }

    $m .= '<div class="aktionen' . (empty($o['blatt_immer']) ? ' nur-unter-720' : '') . '">';
    $m .= '<button type="button" class="knopf knopf-symbol" data-blatt="' . $id . '"'
        . ' aria-expanded="false" aria-controls="' . $id . '"'
        . ' title="Weitere Handlungen">' . ui_symbol('punkte', '', 'Weitere Handlungen') . '</button>';
    $m .= '<div class="blatt" id="' . $id . '" hidden>';
    $m .= '<div class="blatt-griff" aria-hidden="true"></div>';
    if (!empty($o['titel'])) {
        $m .= '<h2 class="blatt-titel">' . ui_e((string)$o['titel']) . '</h2>';
    }
    $m .= '<div class="blatt-liste">';
    foreach ($eintraege as $e) { $m .= $knopf($e, 'blatt-zeile'); }
    $m .= '</div>';
    $m .= '<button type="button" class="knopf knopf-leise blatt-abbrechen" data-blatt-zu>'
        . '<span>Abbrechen</span></button>';
    $m .= '</div></div>';
    return $m;
}


function ui_speichern_leiste(array $o = []): void
{
    /* ZWEI VERWENDUNGEN, EIN BAUSTEIN (O9b). Die Leiste ist ursprünglich die
     * Speichern-Leiste eines schmutzigen Formulars (forms.js hängt an
     * `data-speichern`). Die Sammelleiste der NutzerInnen-Liste ist derselbe
     * Baustein mit anderem Inhalt: unten klebend, ein Hauptknopf, ein Text
     * daneben — nur wird sie nicht von forms.js geschaltet, sondern von der
     * Auswahl, und ihr Text ist die Zahl der ausgewählten Konten und deshalb
     * IMMER sichtbar (der Hinweis eines Formulars erscheint erst ab 720 px).
     *
     * $o: text, symbol, name, wert, attr, hinweis, id, form, zahl (Hinweis
     *     immer sichtbar), kein_haken (nicht an forms.js hängen),
     *     hinweis_vorlage (S8/AP3: forms.js ersetzt den Hinweis dann durch
     *     „<Vorlage>: Karte A und Karte B" — die Titel der Karten, in denen
     *     etwas geändert wurde) */
    $id = !empty($o['id']) ? ' id="' . ui_e((string)$o['id']) . '"' : '';
    ?>
<div class="speichern"<?= $id ?><?= empty($o['kein_haken']) ? ' data-speichern' : '' ?> hidden>
  <?php /* HINWEIS ZUERST, KNOPF DANACH (E-R43-1). Bis Web 12.2.2 stand der
           Knopf im Markup vorn und damit links, die Zaehlung rechts davon —
           umgekehrt zu allem anderen in dieser Oberflaeche, wo die
           Haupthandlung rechts sitzt. Die Reihenfolge im Markup ist zugleich
           die Vorlesereihenfolge: erst „12 ausgewaehlt", dann „Auswahl
           sichern". Ausgerichtet wird ueber `justify-content:flex-end` an
           `.speichern-innen`, nicht ueber `order` — sonst liefen Seh- und
           Vorlesereihenfolge auseinander. */ ?>
  <div class="speichern-innen">
    <p class="speichern-hinweis<?= !empty($o['zahl']) ? ' speichern-zahl' : '' ?>"
       <?= !empty($o['hinweis_vorlage']) ? 'data-hinweis-vorlage="' . ui_e((string)$o['hinweis_vorlage']) . '"' : '' ?>
       <?= !empty($o['zahl']) ? 'id="' . ui_e((string)$o['zahl']) . '"' : '' ?>><?= ui_e((string)($o['hinweis']
        ?? 'Es gibt ungespeicherte Änderungen · Strg + Enter speichert')) ?></p>
    <?= ui_knopf([
        'text' => (string)($o['text'] ?? 'Speichern'),
        'art' => 'primaer', 'symbol' => (string)($o['symbol'] ?? 'haken'),
        /* MIT ZAHL IST DER KNOPF NICHT BREIT. `knopf-breit` (width:100%) wird
         * erst ab 720 px zurueckgenommen (`.speichern .knopf-breit{width:auto}`)
         * — passend zum Hinweis, der genau dort erscheint. Die Zahl steht in
         * JEDER Breite; ein 100 % breiter Knopf daneben drueckt sie auf zwei
         * Zeilen und die Leiste auf die doppelte Hoehe. */
        'breit' => empty($o['zahl']),
        'name' => (string)($o['name'] ?? ''), 'wert' => (string)($o['wert'] ?? ''),
        'attr' => (string)($o['attr'] ?? '')
                . (!empty($o['form']) ? ' form="' . ui_e((string)$o['form']) . '"' : ''),
    ]) ?>
  </div>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * KENNZAHL  (.kennzahl)
 *
 * Wert in Bricolage mit Einheit, darunter die Beschriftung. Extremwerte
 * tragen einen Punkt oben rechts und den Tag in der Beschriftung; die aktive
 * Kachel wird hell orange mit orangem Rahmen (E-P3-37).
 *
 * Die Hervorhebung war rot und ist jetzt orange: Rot heißt in dieser
 * Oberfläche „Aufmerksamkeit" (Fehler, Löschen), und ein Höchstwert ist kein
 * Fehler.
 *
 * $o: wert, einheit, label, extrem (Text des Tages), aktiv, attr
 * ------------------------------------------------------------------------ */
function ui_kennzahl(array $o): string
{
    /* TON UND VERWEIS (O9b, Mockup 41). Eine Statuskachel sagt nicht nur eine
     * Zahl, sondern auch, ob sie in Ordnung ist („27 Backup überfällig" in
     * Orange, „9 nie gesichert" in Rot) — und sie ist ein WEG: Ein Klick
     * öffnet die Liste, auf die sie sich bezieht. Ein <a> statt eines <div>,
     * weil ein Klickziel, das kein Link ist, weder Tastatur noch Kontextmenü
     * bedient. Die Töne heissen wie die der Plakette (neutral/orange/rot). */
    $k = 'kennzahl' . (!empty($o['aktiv']) ? ' aktiv' : '')
       . (!empty($o['extrem']) ? ' kennzahl-extrem' : '')
       . (!empty($o['ton']) ? ' kennzahl-' . ui_e((string)$o['ton']) : '');
    $tag = !empty($o['href']) ? 'a' : 'div';
    $m  = '<' . $tag . ' class="' . $k . '"'
        . (!empty($o['href']) ? ' href="' . ui_e((string)$o['href']) . '"' : '')
        . (string)($o['attr'] ?? '') . '>';
    $m .= '<p class="kennzahl-wert">' . ui_e((string)($o['wert'] ?? '–'));
    if (!empty($o['einheit'])) {
        $m .= '<span class="kennzahl-einheit">' . ui_e((string)$o['einheit']) . '</span>';
    }
    $m .= '</p>';
    $m .= '<p class="kennzahl-label">' . ui_e((string)($o['label'] ?? ''));
    if (!empty($o['extrem'])) {
        $m .= '<span class="kennzahl-tag">' . ui_e((string)$o['extrem']) . '</span>';
    }
    $m .= '</p></' . $tag . '>';
    return $m;
}


/* ---------------------------------------------------------------------------
 * ABBRUCHSEITE
 *
 * Der aufgerufene Datensatz existiert nicht, gehört einem anderen Konto oder
 * liegt im Papierkorb — hier ist Schluss.
 *
 * An 16 Stellen stand dafür einmal `exit('Einsatz nicht gefunden.')`: nackter
 * Text ohne Zeichensatzangabe, ohne Kopfleiste, ohne Weg zurück. Der HTTP-Code
 * stimmte, die Seite war trotzdem eine Sackgasse.
 *
 * $o: titel, zurueck, zurueck_text
 * ------------------------------------------------------------------------ */
function ui_abbruch(int $code, string $text, array $o = []): never
{
    http_response_code($code);
    $titel = (string)($o['titel'] ?? match ($code) {
        404     => 'Nicht gefunden',
        403     => 'Kein Zugriff',
        default => 'Nicht möglich',
    });
    $ziel = (string)($o['zurueck'] ?? 'index.php');
    $wort = (string)($o['zurueck_text'] ?? 'Zur Startseite');

    ui_seite_start(['titel' => $titel]);
    ui_kopf(['menue' => false, 'zurueck' => ['text' => $wort, 'href' => $ziel]]);
    echo '<div class="rahmen rahmen-lesespalte">' . "\n";
    echo '  <main class="inhalt">' . "\n";
    ui_hinweise();
    echo '    <div class="text">' . "\n";
    echo '      <h1>' . ui_e($titel) . "</h1>\n";
    echo '      ' . ui_meldung_markup('fehler', $text) . "\n";
    echo '      <p>' . ui_knopf(['text' => $wort, 'href' => $ziel, 'art' => 'neutral',
                                 'symbol' => 'zurueck']) . "</p>\n";
    echo "    </div>\n  </main>\n</div>\n";
    ui_fuss_seite();
    ui_seite_ende();
    exit;
}
/**
 * Markup eines ORTSFELDES — Bezeichnung plus optionale Koordinaten (E37/E39).
 *
 * Gegenstueck zu assets/ortsfeld.js: Diese Funktion erzeugt die Elemente, das
 * Skript belebt sie. Beide bilden die Kennungen aus demselben PRAEFIX; wer eine
 * siebte Verwendung braucht, schreibt einen Aufruf hier und ein
 * EdOrtsfeld.init() dort — und nicht wieder 250 Zeilen (Vorpruefung V8).
 *
 * ZWEI FORMEN, gesteuert ueber 'feld':
 *
 *   feld = true   Vollstaendiges Widget mit eigener Beschriftung und
 *                 Textfeld. So steht es im Einsatzformular.
 *   feld = false  NUR das Zubehoer — Suchfeld, Vorschlagsliste, Zustandszeile,
 *                 Chip und die versteckten Koordinatenfelder. Das
 *                 Bezeichnungsfeld existiert dann bereits und traegt lediglich
 *                 die Kennung `<praefix>addr`. Gebraucht in den
 *                 Stammdatenformularen: Dort ist der Name ein gewachsenes
 *                 Eingabefeld einer Flex-Zeile, und es einzufassen haette das
 *                 Layout gebrochen, ohne etwas zu gewinnen.
 *
 * Schluessel:
 *   praefix     Pflicht. Bildet `<p>addr`, `<p>such`, `<p>lat`, `<p>lon`,
 *               `<p>suggest`, `<p>state`, `<p>chips`.
 *   such        eigenes Suchfeld erzeugen (getrennte Suche, siehe ortsfeld.js)
 *   such_hinweis / such_platzhalter
 *   label, hinweis, platzhalter, max, wert           (nur bei feld = true)
 *   name        POST-Name des Bezeichnungsfeldes; null = keiner (der Wert
 *               wandert dann verschluesselt in den pat_blob)
 *   lat_name / lon_name, lat / lon                   Koordinatenfelder
 *   ortswahl    Pin-Knopf mit dem Blatt „Meine Position / Auf der Karte" —
 *               seit Web 15.8.0 in BEIDEN Fassungen (E-S9-06 c)
 *   klasse      zusaetzliche Klasse am Rahmen (z. B. 'loc-inline')
 *
 * KEINE `<datalist>` MEHR (S9/AP1, E-S9-07). Bis Web 15.5.2 nahm der
 * Schluessel 'datalist' eine Namensliste entgegen und haengte sie als native
 * Vorschlagsliste an das Feld. Am Transportziel standen damit ZWEI Listen
 * uebereinander — die native, vom Browser ueber dem Feld gezeichnet, und die
 * eigene darunter (PS-6); mobil zeigte die native nichts (Backlog 68). Die
 * Stammdaten kommen jetzt als GRUPPE in die eine Liste; uebergeben werden sie
 * dem Skript (`EdOrtsfeld.init({vorschlaege: […]})`), nicht dem Markup.
 */
/**
 * DAS TOKEN FUER `EdApi` AUF SEITEN OHNE VERSCHLUESSELUNG (P5c/AP9, E-P5c-130).
 *
 * `EdApi.postJson()` und `.postForm()` lesen die Konstante `CSRF`. Bis Web
 * 21.1.0 schrieb sie nur `ui_krypto_bootstrap()` — und der bringt Salz,
 * Rundenzahl und den Server-Anteil des Kontos mit. Die Seite „Rechtstexte"
 * braucht nichts davon, aber `EdApi` fuer ihre Vorschau; das ganze Ruestzeug
 * dafuer zu laden hiesse, einer Verwaltungsseite Schluesselmaterial zu geben,
 * nur um ein Token zu bekommen.
 *
 * EINE STELLE, ZWEI EINGAENGE: `ui_csrf_zeile()` baut die Zeile, und zwar
 * EINMAL je Seitenaufbau; der Krypto-Baustein und `ui_csrf_bootstrap()` holen
 * sie beide dort. Ein zweites `const CSRF` auf derselben Seite waere ein
 * SyntaxError im zweiten Skript — und der fiele erst auf, wenn eine Seite
 * beide Wege nimmt. Deshalb liefert der zweite Abruf eine leere Zeile.
 *
 * NICHT von Hand ein verstecktes Feld an `EdApi` vorbei reichen (Register
 * Z29): Das waere ein zweiter Transport.
 */
function ui_csrf_zeile(): string
{
    static $schon = false;
    if ($schon) { return ''; }
    $schon = true;
    return 'const CSRF = ' . json_js(csrf_token()) . ';';
}

function ui_csrf_bootstrap(): void
{
    $zeile = ui_csrf_zeile();
    if ($zeile !== '') {
        echo '<script' . kopf_nonce_attr() . '>' . $zeile . "</script>\n";
    }
}

/**
 * Die Einstellungen der Adresssuche fuer den Browser (S9/AP2, E-S9-05).
 *
 * WARUM NICHT IM KRYPTO-BOOTSTRAP, wie das Konzept es vorsah. Dort stehen
 * schon Konstanten fuer den Browser, und der Gedanke war richtig — nur haengt
 * die Adresssuche nicht an der Verschluesselung: Eine Seite kann Ortsfelder
 * tragen, ohne `ui_krypto_bootstrap()` zu rufen. Bis S9/AP5b war die
 * systemweite Stammdatenpflege genau so ein Fall; sie brauchte keine
 * Verschluesselung, trug aber zwei Ortsfelder, und die Adresssuche haette dort
 * ohne Einstellung dagestanden und waere auf den Rueckfall gefallen. Die Seite
 * ist gestrichen, die Moeglichkeit nicht: Der naechste Ortsfeld-Einbau kann
 * wieder einer sein. Also ein eigener, kleiner Bootstrap — und er wird nicht
 * von den Seiten gerufen, sondern von `ui_ortsfeld()` selbst: Wo ein Ortsfeld
 * steht, stehen seine Einstellungen, und keine Seite kann sie vergessen.
 *
 * EINMAL JE SEITENAUFBAU. Merkzettel wie beim Krypto-Bootstrap: Sieben
 * Ortsfelder auf einer Seite sind der Regelfall, nicht die Ausnahme, und
 * siebenmal dasselbe Skript ist siebenmal Ballast.
 *
 * `window.` UND NICHT `const` — daran ist AP2 einmal vorbeigelaufen. Der
 * Krypto-Bootstrap schreibt `const PAT_WRAP = …`, und das geht dort gut, weil
 * seine Leser INLINE-Skripte derselben Seite sind: Ein `const` auf oberster
 * Ebene liegt im globalen LEXIKALISCHEN Bereich, den ein Skript sieht — aber
 * es wird KEINE Eigenschaft von `window`. `assets/geocoder.js` ist eine
 * eigene Datei und liest `global.GEO_DIENST`; die stand damit auf
 * `undefined`, `EdGeocoder.an()` lieferte `false`, und der Kartendialog kam
 * ohne Suchfeld — auf einer Seite, deren Hinweiszeile daneben sagte, die
 * Suche sei an. In der Konsole war nichts zu sehen: Wer dort `GEO_DIENST`
 * eintippt, bekommt den lexikalischen Wert und damit die Antwort, die er
 * erwartet. Gefunden hat es die Klickprobe (F-S9-P-08). Eine Zuweisung an
 * `window` ist ausserdem beim zweiten Mal harmlos — der Merkzettel bleibt
 * trotzdem, denn zweimal dasselbe auszugeben ist auch dann falsch.
 */
function ui_geocoder_bootstrap(): void
{
    static $schon = false;
    if ($schon) { return; }
    $schon = true;

    require_once __DIR__ . '/geocoder_lib.php';
    echo '<script' . kopf_nonce_attr() . '>window.GEO_AN = ' . json_encode(geocoder_an())
       . '; window.GEO_DIENST = ' . json_encode(geocoder_dienst(),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
       . ";</script>\n";
}

/**
 * Der Hinweis unter dem Ortsfeld — EINMAL je Seite (S9/AP2, E-S9-05, Nr. 137).
 *
 * WARUM NUR EINMAL. „Ein Hinweis am Ortsfeld" heisst es im Auftrag, und beim
 * Einsatzformular waeren das drei gleiche Saetze auf einer Seite. Auf
 * `einstellungen.php?t=standorte` waeren es zehn und mehr: Dort steht ein
 * Ortsfeld je Standort UND je Zielklinik. Zehnmal derselbe Datenschutzhinweis
 * ist keine Auskunft mehr, sondern Tapete — und Tapete liest niemand.
 *
 * Er steht deshalb am ERSTEN Ortsfeld der Seite, und sein Satz ist auf die
 * Seite bezogen formuliert, nicht auf das Feld. Die vollstaendige Erklaerung
 * mit dem Schalter steht in der Karte „Datenschutz" im Profil, der Absatz mit
 * der Dienstadresse im Datenschutztext.
 *
 * Ist die Suche aus, erscheint er GAR NICHT: Er sagt aus, dass etwas das
 * Geraet verlaesst — und dann verlaesst nichts das Geraet.
 */
function ui_geocoder_hinweis(): void
{
    static $schon = false;
    if ($schon) { return; }
    require_once __DIR__ . '/geocoder_lib.php';
    if (!geocoder_an()) { return; }
    $schon = true;
    echo '<p class="feld-klein loc-datenschutz">Vorschläge und Umkehrsuche kommen von '
       . ui_e(geocoder_host()) . '; getippter Text verlässt das Gerät. '
       . 'Abschalten: Einstellungen → Profil, Karte „Datenschutz".</p>' . "\n";
}

function ui_ortsfeld(array $o): void
{
    $p = (string)$o['praefix'];
    $mitFeld = ($o['feld'] ?? true) !== false;

    $versteckt = !empty($o['versteckt']) ? ' hidden' : '';

    /* SEIT O5 (E-P3-34) GIBT ES KEIN ZWEITES SUCHFELD MEHR: An seine Stelle
     * tritt der Lupen-Knopf neben dem Feld — er sucht mit dem, was im Feld
     * steht (bei getrennter Suche uebernimmt der Treffer weiterhin NUR die
     * Koordinaten, assets/ortsfeld.js). 'ortswahl' ergaenzt den Pin-Knopf mit
     * dem Blatt „Meine Position uebernehmen / Auf der Karte waehlen"
     * (assets/ortswahl.js). */
    $mitWahl = !empty($o['ortswahl']);

    /* DIE EINSTELLUNGEN DER ADRESSSUCHE gehen mit dem ersten Ortsfeld der
     * Seite in den Browser — nicht die Seite bestellt sie, sondern das Feld
     * bringt sie mit (S9/AP2). */
    ui_geocoder_bootstrap();

    /* DER PIN-KNOPF STEHT IN BEIDEN FASSUNGEN (E-S9-06 c). Bis Web 15.7.1
     * rendete ihn nur der `feld = true`-Zweig; die Nur-Lage-Fassung der
     * Stammdaten hatte deshalb keine Karte — und Backlog Nr. 70 („Karte fuer
     * Standorte") war genau das. Der Block steht jetzt einmal hier und wird
     * zweimal ausgegeben. */
    $pinKnopf = static function () use ($o, $p): void {
        ?>
            <span class="aktionen ortsfeld-aktionen">
              <button type="button" class="knopf knopf-symbol" title="Ort setzen"
                      aria-expanded="false" aria-controls="<?= e($p) ?>ortsblatt"
                      data-blatt="<?= e($p) ?>ortsblatt"><?= ui_symbol('position', 'symbol-gross') ?><span
                      class="nur-vorlesen">Ort setzen</span></button>
              <div class="blatt" id="<?= e($p) ?>ortsblatt" hidden>
                <div class="blatt-griff" aria-hidden="true"></div>
                <h2 class="blatt-titel"><?= e((string)($o['label'] ?? $o['such_hinweis'] ?? 'Ort')) ?> setzen</h2>
                <div class="blatt-liste">
                  <button type="button" class="blatt-zeile"
                          data-ortswahl="position" data-praefix="<?= e($p) ?>">
                    <?= ui_symbol('position') ?><span>Meine Position übernehmen</span></button>
                  <button type="button" class="blatt-zeile"
                          data-ortswahl="karte" data-praefix="<?= e($p) ?>">
                    <?= ui_symbol('karte') ?><span>Auf der Karte wählen</span></button>
                </div>
                <button type="button" class="knopf knopf-leise blatt-abbrechen"
                        data-blatt-zu>Abbrechen</button>
              </div>
            </span>
        <?php
    };

    if ($mitFeld): ?>
      <div class="loc-widget <?= e((string)($o['klasse'] ?? '')) ?>"<?= $versteckt ?>>
        <?php /* 'geschuetzt' => true haengt das Schloss an die Beschriftung
                 (S9/AP7, E-S9-02). Der Einsatzort und der manuelle Abfahrtort
                 liegen im `pat_blob`, das Transportziel und der Standort eines
                 Rettungsmittels nicht — und man sieht es einem Ortsfeld sonst
                 nicht an. Ein eigener Schluessel und kein HTML im 'label':
                 Jenes wird escaped, und das soll es bleiben. */ ?>
        <label for="<?= e($p) ?>addr"><?= e((string)($o['label'] ?? '')) ?><?=
            !empty($o['geschuetzt'])
              ? ui_symbol('schloss', 'symbol-schutz', 'Ende-zu-Ende-verschlüsselt')
              : '' ?>
          <?php if (!empty($o['hinweis'])): ?>
            <span class="feld-klein-inline"><?= e((string)$o['hinweis']) ?></span>
          <?php endif; ?>
        </label>
        <div class="ortsfeld-zeile">
          <input type="text" id="<?= e($p) ?>addr" autocomplete="off"
                 <?= isset($o['name']) && $o['name'] !== null ? 'name="' . e((string)$o['name']) . '"' : '' ?>
                 <?= isset($o['max']) ? 'maxlength="' . (int)$o['max'] . '"' : '' ?>
                 placeholder="<?= e((string)($o['platzhalter'] ?? '')) ?>"
                 value="<?= e((string)($o['wert'] ?? '')) ?>">
          <button type="button" class="knopf knopf-symbol" id="<?= e($p) ?>lupe"
                  title="Suchen"><?= ui_symbol('lupe', 'symbol-gross') ?><span
                  class="nur-vorlesen">Suchen</span></button>
          <?php if ($mitWahl) { $pinKnopf(); } ?>
        </div>
    <?php else: ?>
      <?php /* NUR-LAGE-FASSUNG (feld = false): ein Suchfeld ohne Namensfeld.
               Die Verwaltungslisten führen den Namen in einem EIGENEN Feld
               („Standort Talwang"); hier wird nur die Lage gesucht, und ein
               Treffer setzt ausschließlich die Koordinaten (ortsfeld.js,
               `getrennteSuche`).

               BIS WEB 9.6.0 STAND HIER GAR KEIN FELD. Diese Fassung gab nur
               das Zubehör aus — Vorschlagsliste, Zustandszeile, Chips, die
               versteckten Koordinatenfelder —, weil das sichtbare Suchfeld
               getrennt daneben stand ('such' => true). Mit O5 ist das zweite
               Suchfeld ausgebaut worden (der Lupen-Knopf am Namensfeld trat
               an seine Stelle), und dabei ist diese Fassung leer
               zurückgeblieben: Die Lage eines Standorts oder einer Zielklinik
               ließ sich seither nicht mehr eingeben, nur noch behalten
               (F-P3-AI). */ ?>
      <div class="loc-widget <?= e((string)($o['klasse'] ?? '')) ?>"<?= $versteckt ?>>
        <?php if (!empty($o['such_hinweis'])): ?>
          <label class="feld-label" for="<?= e($p) ?>addr"><?= e((string)$o['such_hinweis']) ?></label>
        <?php endif; ?>
        <div class="ortsfeld-zeile">
          <input type="text" id="<?= e($p) ?>addr" autocomplete="off"
                 placeholder="<?= e((string)($o['platzhalter'] ?? 'Adresse oder Ort suchen')) ?>">
          <button type="button" class="knopf knopf-symbol" id="<?= e($p) ?>lupe"
                  title="Suchen"><?= ui_symbol('lupe', 'symbol-gross') ?><span
                  class="nur-vorlesen">Suchen</span></button>
          <?php if ($mitWahl) { $pinKnopf(); } ?>
        </div>
    <?php endif; ?>

      <?php /* Die Trefferliste — EIN Baustein fuer Stammdaten, Adressen und
               erkannte Koordinaten (assets/vorschlagsliste.js, E-S9-07). Das
               Markup ist leer; gefuellt wird es beim Tippen. */ ?>
      <ul id="<?= e($p) ?>suggest" class="vorschlaege" hidden></ul>
      <?php /* Meldungszeile unmittelbar unter dem Feld: Sie sagt etwas über
               DIESES Eingabefeld aus („Koordinaten gesetzt — dieses Feld ist
               die Bezeichnung", „Bezeichnung fehlt"), nicht über den Chip
               darunter. */ ?>
      <p class="locstate" id="<?= e($p) ?>state"></p>
      <?php /* Bestätigte Koordinaten stehen als Chip UNTER dem Textfeld, nicht
               darin — sonst vernichtet die erste getippte Bezeichnung sie. */ ?>
      <div class="rmchips" id="<?= e($p) ?>chips"></div>
      <?php ui_geocoder_hinweis(); ?>
      <input type="hidden" id="<?= e($p) ?>lat"
             <?= isset($o['lat_name']) ? 'name="' . e((string)$o['lat_name']) . '"' : '' ?>
             value="<?= e((string)($o['lat'] ?? '')) ?>">
      <input type="hidden" id="<?= e($p) ?>lon"
             <?= isset($o['lon_name']) ? 'name="' . e((string)$o['lon_name']) . '"' : '' ?>
             value="<?= e((string)($o['lon'] ?? '')) ?>">
      </div>
<?php }

/**
 * Die Vorgaben, die `assets/missiontable.js` braucht — EINMAL, fuer alle
 * drei Seiten mit einer Einsatztabelle (Schritt 15 AP9b).
 *
 * Bis dahin standen `ART_SYMBOLE` und `TYP_SYMBOLE` wortgleich in
 * `suche.php` und `zeitraum.php`, und `index.php` hatte statt dessen seine
 * eigene Liste `DAY_COLS`. Drei Seiten, drei Vorspaenne, eine Tabelle —
 * genau die Bauform, die Schritt 15 abschafft.
 *
 * `KATALOG_SPALTEN` ist `mf_tagesspalten()`, also die Felder mit 'day_col'
 * aus `mission_fields.php`. Das Modul gleicht seine Hakenspalten dagegen ab:
 * Der Katalog bestimmt, WELCHE es gibt, das Modul, wie sie aussehen und in
 * welcher Reihenfolge sie stehen (E-ZE-34). `label` darf Auszeichnung
 * tragen und geht deshalb unmaskiert hinaus — der Wert stammt aus einer
 * Datei des Projekts, nie aus einer Eingabe.
 *
 * MUSS VOR `assets/missiontable.js` STEHEN. Das Modul fragt die drei Namen
 * erst beim Zeichnen ab und traegt fuer jeden einen benannten Rueckfall,
 * aber ein Vorspann, der nach dem Modul kommt, ist ein Vorspann, der beim
 * ersten Zeichnen fehlt.
 */
function ui_tabellen_bootstrap(): void
{
    /* Die Funktion holt sich, was sie braucht -- wie ui_days_sidebar() mit
     * diensttag_lib.php. `zeitraum.php` bindet mission_fields_lib.php nicht
     * ein und muss es auch nicht: Wer einen Baustein ruft, soll nicht dessen
     * Abhaengigkeiten kennen muessen. */
    require_once __DIR__ . '/diensttag_lib.php';
    require_once __DIR__ . '/mission_fields_lib.php';

    $spalten = array_map(
        static fn(array $dc): array => ['col' => $dc['col'], 'art' => $dc['art'],
                                        'label' => $dc['label'], 'klasse' => $dc['klasse'],
                                        'cap' => $dc['cap']],
        mf_tagesspalten());
    ?>
<script<?= kopf_nonce_attr() ?>>const ART_SYMBOLE = <?= json_js(dt_art_symbole(), JSON_UNESCAPED_UNICODE) ?>;
        /* Die Zeichen der Diensttag-TYPEN daneben (E-S9-13, Web 16.0.0) — sonst
           zeichnet diese Tabelle die Betriebsart, waehrend die Leiste den Typ
           zeichnet. Dieselbe Quelle wie auf der Serverseite. */
        const TYP_SYMBOLE = <?= json_js(dt_typ_symbole(), JSON_UNESCAPED_UNICODE) ?>;
        const KATALOG_SPALTEN = <?= json_js($spalten, JSON_UNESCAPED_UNICODE) ?>;</script>
<?php
}

/**
 * Ruestzeug der Ende-zu-Ende-Verschluesselung: die Skripte und die Werte,
 * die sie aus der Nutzerzeile brauchen.
 *
 * WARUM ES SIE GIBT (P0/A6, Befund C1 und F-12). Acht Stellen in sieben
 * Dateien schrieben denselben Block: drei <script>-Verweise und vier
 * Konstanten, dazu jedes Mal derselbe achtzeilige Kommentar. Zwei Folgen
 * hatte das schon:
 *
 *   1. NAMENSDRIFT. einstellungen.php nannte die Huelle im Profilreiter
 *      WRAP_PW, ueberall sonst heisst sie PAT_WRAP. Ein Baustein, der aus
 *      diesem Reiter etwas uebernimmt, greift ins Leere.
 *   2. DOPPELTE EINBINDUNG (F-12). Dieselbe Datei band crypto.js zweimal ein
 *      und pwquality.js ebenfalls — einmal je Reiter. Beide Dateien
 *      deklarieren auf oberster Ebene ein `const`; eine zweite Deklaration im
 *      selben Dokument ist ein SyntaxError, der das GANZE zweite Skript
 *      verwirft. Dass nichts geschah, hing allein daran, dass die Reiter
 *      einander ausschliessen — eine nirgends aufgeschriebene Bedingung in
 *      einer Datei mit ueber 2000 Zeilen.
 *
 * Gegen (2) haelt diese Funktion einen Merkzettel: Ein zweiter Aufruf im
 * selben Seitenaufbau gibt NICHTS aus und schreibt eine Zeile ins Fehlerlog.
 * Aus der stillen Bedingung wird damit eine, die sich meldet.
 *
 * REIHENFOLGE. Die Konstanten stehen in einem EIGENEN <script>-Block, direkt
 * hinter den Verweisen und damit vor dem Seitenskript. Das ist unbedenklich:
 * Klassische Skripte teilen sich eine gemeinsame oberste Bindungsebene, und
 * gelesen werden die Werte erst in Funktionen, die spaeter laufen — keine
 * Datei greift beim Laden darauf zu (nachgesehen in crypto.js, keyguard.js,
 * unlock.js, patient.js, export.js, import_ui.js).
 *
 * VORAUSSETZUNG: auth_guard.php ist geladen. Von dort kommen $patWrapPw,
 * $patKeyCheck, $kdfSalt, $kdfIter und — seit S10 — $kontoAnteile,
 * $anteilKennung und $anteilStand; KDF_ITER_ZIEL kommt aus db.php. Seit
 * RW-02 auch $userId, für RW_STAND (Konzept RW).
 *
 * DIE DREI S10-KONSTANTEN STEHEN IMMER, wie CSRF und aus demselben Grund
 * (Backlog Nr. 136, Fund F-9a-01): Ein Schalter, den drei von sieben Seiten
 * nicht stellen, nimmt der stillen Umstellung die Grundlage — und zwar
 * dauerhaft, weil `loeseVormerkung()` das Vormerkfach danach verwirft. Die
 * Lehre von damals kostet hier drei Zeilen Markup.
 *
 * $o: skripte  Liste der Verweise. Vorgabe: crypto.js, keyguard.js, unlock.js.
 *              Ein leeres Feld gibt keinen Verweis aus (fuer Seiten, die ihre
 *              Verweise aus anderem Grund selbst setzen muessen).
 *     guete    true  -> zusaetzlich pwquality.js (Passwortguete, Baustein B9)
 *     wrap     false -> KEIN PAT_WRAP. Genau ein Aufrufer: einsatz.php, das
 *              die Huelle aus der API-Antwort bezieht (m.pat_wrap).
 *     keycheck true  -> zusaetzlich PAT_KEY_CHECK (Herkunftsabgleich beim
 *              Einspielen eines Backups)
 *     csrf     ohne Wirkung seit Backlog Nr. 136 — CSRF steht immer (siehe
 *              unten). Das Feld wird noch angenommen, damit die Aufrufer
 *              nicht angefasst werden muessen.
 *     einzug   Einrueckung der ausgegebenen Zeilen
 */
function ui_krypto_bootstrap(array $o = []): void
{
    global $patWrapPw, $patKeyCheck, $kdfSalt, $kdfIter;
    global $kontoAnteile, $anteilKennung, $anteilStand;

    static $schon = false;
    if ($schon) {
        /* `ui.php` laeuft auch in `install.php`, ohne `db.php` — deshalb
         * holt die Stelle den Helfer selbst; er laedt nichts. */
        require_once __DIR__ . '/systemmeldung_lib.php';
        system_melden('ui', 'ui_krypto_bootstrap() zweimal aufgerufen — der zweite Aufruf '
                    . 'wurde übergangen. Beide Zweige einer Seite dürfen das '
                    . 'Rüstzeug nur EINMAL anfordern.');
        return;
    }
    $schon = true;

    $ein = (string)($o['einzug'] ?? '');
    $skripte = $o['skripte'] ?? ['assets/crypto.js', 'assets/keyguard.js', 'assets/unlock.js'];
    if (!empty($o['guete'])) { $skripte[] = 'assets/pwquality.js'; }

    /* DER STAND DES PAARS FÜR DEN RÜCKWEG (Konzept RW, RW-02; E-RW-02, -05,
     * -15): 'da', 'fehlt', 'spalten' oder 'demo'. Nur bei 'fehlt' legt
     * `unlock.js` nach der Anmeldung still eins an — und nur dann kommt
     * `rueckweg.js` mit, vor `unlock.js`.
     *
     * HIER GEFRAGT UND NICHT IN `auth_guard.php` (E-RW-17): Die Wache läuft
     * bei jeder Anfrage, auch bei jedem API-Aufruf; gebraucht wird der Wert
     * nur auf den Seiten, die dieses Rüstzeug anfordern. */
    global $userId;
    require_once __DIR__ . '/rueckweg_lib.php';
    $rwStand = isset($userId) ? rw_zustand((int)$userId)['stand'] : 'spalten';
    $unlockAn = array_search('assets/unlock.js', $skripte, true);
    if ($rwStand === 'fehlt' && $unlockAn !== false) {
        array_splice($skripte, (int)$unlockAn, 0, ['assets/rueckweg.js']);
    }

    $zeilen = [];
    foreach ($skripte as $s) {
        $zeilen[] = '<script src="' . ui_asset((string)$s) . '"></script>';
    }
    $zeilen[] = '<script' . kopf_nonce_attr() . '>';
    if (($o['wrap'] ?? true) !== false) {
        $zeilen[] = 'const PAT_WRAP = ' . json_js($patWrapPw) . ';';
    }
    if (!empty($o['keycheck'])) {
        $zeilen[] = 'const PAT_KEY_CHECK = ' . json_js($patKeyCheck) . ';';
    }
    $zeilen[] = 'const KDF_SALT = ' . json_js($kdfSalt) . ';';
    /* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
       gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
       bekommt einen anderen Schluessel. */
    $zeilen[] = 'const KDF_ITER      = ' . json_js($kdfIter) . ';';
    $zeilen[] = 'const KDF_ITER_ZIEL = ' . json_js(KDF_ITER_ZIEL) . ';';
    /* Der Server-Anteil dieses Kontos (S10, E-S10-06). KONTO_ANTEILE ist ein
       Objekt `{ kennung: 64 hex }` — waehrend einer Rotation zwei Eintraege —,
       oder `null` fuer das Demo-Konto. ANTEIL_KENNUNG nennt den aktuellen;
       mit ihm werden NEUE Huellen gebaut. ANTEIL_STAND ist 'bereit', 'fehlt',
       'abweichend' oder 'demo' und entscheidet, welche Meldung erscheint,
       wenn sich eine Huelle nicht oeffnen laesst. */
    $zeilen[] = 'const KONTO_ANTEILE  = ' . json_js($kontoAnteile ?? null) . ';';
    $zeilen[] = 'const ANTEIL_KENNUNG = ' . json_js($anteilKennung ?? null) . ';';
    $zeilen[] = 'const ANTEIL_STAND   = ' . json_js($anteilStand ?? 'fehlt') . ';';
    $zeilen[] = 'const RW_STAND       = ' . json_js($rwStand) . ';';
    /* CSRF IMMER, NICHT AUF ANFRAGE (Backlog Nr. 136, Fund F-9a-01).
     *
     * Bis zum Sofortpaket Sicherheit war das ein Schalter, und drei von sieben
     * Seiten stellten ihn. Das war folgenlos, solange KDF_ITER_LISTE nur einen
     * Eintrag hatte — mit dem Sprung auf 600 000 wurde daraus ein Fehler:
     *
     * Die stille Anhebung (unlock.js, loeseVormerkung) verlangt CSRF, weil sie
     * `api/kdf_upgrade.php` ruft. Fehlt die Konstante, laeuft sie nicht — und
     * schlimmer: `loeseVormerkung()` VERWIRFT danach das Vormerkfach. Die erste
     * Seite nach dem Anmelden, die den Inhaltsschluessel braucht und kein CSRF
     * traegt (`suche.php`, `zeitraum.php`, `einsatz.php`, `einsatz_form.php`),
     * nimmt der Anhebung damit dauerhaft die Grundlage. Gemessen am
     * Referenzbestand: Konto auf 320 000, Anmeldung, `suche.php` — Vormerkfach
     * weg, Rundenzahl unveraendert, und beim naechsten Anmelden dasselbe.
     *
     * Der Schalter kostete also eine Sicherheitsmassnahme und sparte eine
     * Zeile Markup. Das Feld `csrf` wird weiterhin angenommen und ignoriert;
     * die Aufrufer nennen es teils noch.
     */
    $csrf = ui_csrf_zeile();
    if ($csrf !== '') { $zeilen[] = $csrf; }
    $zeilen[] = '</script>';

    echo $ein, implode("\n" . $ein, $zeilen), "\n";
}

