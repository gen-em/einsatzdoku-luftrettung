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
 *   titel    Pflicht. Der Wortlaut VOR dem Trenner; " — Einsatzdoku" haengt
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
        '<title>' . ui_e((string)$o['titel']) . ' — Einsatzdoku</title>',
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

/** Favicon-Verweise — favicon_tags() aus db.php, wo es sie gibt (s. ui_asset()). */
function ui_favicon(): string
{
    if (function_exists('favicon_tags')) { return favicon_tags(); }
    // Rueckfall ohne config.php: seitenrelativ. Der Einrichter liegt im selben
    // Verzeichnis wie die Anwendung, damit zeigen die Pfade richtig.
    return '<link rel="icon" type="image/png" href="assets/images/favicon.png">' . "\n"
         . '<link rel="icon" href="favicon.ico">'
         . '<link rel="apple-touch-icon" href="assets/images/favicon.png">';
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

/** Anzeigename der angemeldeten Person — Name, sonst E-Mail. */
function ui_user_label(): string {
    global $userName, $userEmail;
    return ($userName !== null && $userName !== '') ? $userName : (string)$userEmail;
}

/**
 * Pfad zum Logo für die Kopfleiste (weiße Fassung) bzw. für helle Flächen.
 *
 * Die Wahl je Profil (E-P3-20: Standard / Hubschrauber / Fahrzeug /
 * wechselnd) entsteht in O8. Diese Funktion ist die eine Stelle, an der sie
 * dann greift — sie fragt schon jetzt die Sitzung, findet dort nichts und
 * fällt auf den Standard zurück. Damit ist der Umbau in O8 eine Zuweisung
 * und keine Suche über 25 Seiten.
 */
function ui_logo(bool $weiss = false): string
{
    $wahl = (string)($_SESSION['logo_wahl'] ?? '');
    $stamm = match ($wahl) {
        'fahrzeug'      => 'gen-em_logo_fahrzeug',
        'hubschrauber'  => 'gen-em_logo_helicopter',
        default         => 'gen-em_logo_helicopter',
    };
    return ui_asset('assets/images/' . $stamm . ($weiss ? '_weiss' : '') . '.svg');
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
function ui_artzeichen(?string $kind, string $klassen = ''): string
{
    require_once __DIR__ . '/diensttag_lib.php';
    $sym = dt_art_symbol($kind);
    return ui_symbol($sym['symbol'], trim('artzeichen ' . $klassen), $sym['text']);
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
    ?>
<header class="kopf">
  <div class="kopf-innen">
    <?php if ($menue): ?>
    <button type="button" class="knopf knopf-symbol kopf-menue" data-schublade="auf"
            aria-expanded="false" aria-controls="leiste" aria-label="Menü öffnen">
      <?= ui_symbol('menu', 'symbol-gross') ?>
    </button>
    <?php endif; ?>

    <a class="kopf-marke" href="index.php">
      <img src="<?= ui_e(ui_logo(true)) ?>" alt="" width="40" height="25">
      <span class="kopf-name">Einsatzdoku</span>
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
 *     menue    aktiver Eintrag des Einstellungsmenüs (nur bei 'einstellungen')
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
    ui_kopf(['aktiv' => (string)($o['aktiv'] ?? '')]);
    ?>
<div class="schleier" data-schublade="zu" hidden></div>
<div class="rahmen">
  <?php /* tabindex="-1": Beim Öffnen der Schublade fokussiert schublade.js
           die Leiste SELBST, nicht ihr erstes Bedienelement — sonst trüge
           das X beim Öffnen einen Fokusring, den niemand bestellt hat
           (F-P3-V). Per Tab ist die Leiste dadurch nicht erreichbar; ihre
           Einträge sind es. */ ?>
  <aside class="leiste" id="leiste" aria-label="Bereichsmenü" tabindex="-1">
    <div class="leiste-kopf nur-schublade">
      <button type="button" class="knopf knopf-symbol" data-schublade="zu" aria-label="Menü schließen">
        <?= ui_symbol('schliessen', 'symbol-gross') ?>
      </button>
      <span class="kopf-name">Einsatzdoku</span>
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
        ui_leiste_diensttage(isset($o['tag']) ? (int)$o['tag'] : null);
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
    ui_demo_hinweis();
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

    $skripte = ['assets/symbol.js', 'assets/schublade.js', 'assets/blatt.js',
                'assets/confirm.js'];
    if (ui_hat_tagesleiste()) { $skripte[] = 'assets/daylist.js'; }
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
 *     Name steht im Tooltip und im Seitentitel. Unter 1200 px entfällt der
 *     Name ganz, das Artzeichen bleibt.
 *
 * Das Artzeichen kommt aus dem Symbolvorrat statt als Emoji (E-P3-18) — es
 * lässt sich damit färben und auf Kontrast prüfen, und es sieht auf jedem
 * Betriebssystem gleich aus.
 * ------------------------------------------------------------------------ */
function ui_leiste_diensttage(?int $currentDayId): void
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
        <details class="akkordeon" <?= $jahrS === $offenesJahr ? 'open' : '' ?>>
          <?php /* Der Balken-Link steht IM summary: Als Kind des <details>
                   wäre er an jeder zugeklappten Zeile unsichtbar — der Inhalt
                   eines geschlossenen <details> wird nicht gerendert
                   (F-P3-R). daylist.js fängt den Klick ab, damit er nicht
                   zusätzlich auf- und zuklappt. */ ?>
          <summary class="akkordeon-zeile">
            <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
            <span class="akkordeon-text"><?= ui_e($jahrS) ?></span>
            <a class="akkordeon-uebersicht" href="zeitraum.php?y=<?= ui_e($jahrS) ?>"
               aria-label="Jahresübersicht <?= ui_e($jahrS) ?>" title="Jahresübersicht">
              <?= ui_symbol('balken') ?>
            </a>
          </summary>
          <div class="akkordeon-inhalt">
          <?php foreach ($monate as $monat => $monatsTage):
              $monatS = str_pad((string)$monat, 2, '0', STR_PAD_LEFT); ?>
            <details class="akkordeon akkordeon-monat"
                     <?= ($jahrS === $offenesJahr && $monatS === $offenerMonat) ? 'open' : '' ?>>
              <summary class="akkordeon-zeile">
                <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
                <span class="akkordeon-text"><?= ui_e($monatsnamen[(int)$monatS]) ?></span>
                <a class="akkordeon-uebersicht"
                   href="zeitraum.php?y=<?= ui_e($jahrS) ?>&amp;m=<?= ui_e($monatS) ?>"
                   aria-label="Monatsübersicht <?= ui_e($monatsnamen[(int)$monatS]) ?>"
                   title="Monatsübersicht"><?= ui_symbol('balken') ?></a>
              </summary>
              <div class="akkordeon-inhalt">
              <?php foreach ($monatsTage as $t):
                  $kind = $t['kind'] === null ? null : (string)$t['kind'];
                  $sym  = dt_art_symbol($kind);
                  $name = (string)($t['vehicle_name'] ?? '');
                  $titel = $name !== '' ? $name . ' — ' . $sym['text'] : $sym['text'];
                  $ist = (int)$t['id'] === $currentDayId; ?>
                <a class="eintrag<?= $ist ? ' aktiv' : '' ?>"
                   href="index.php?d=<?= (int)$t['id'] ?>"
                   <?= $ist ? 'aria-current="page"' : '' ?> title="<?= ui_e($titel) ?>">
                  <?= ui_artzeichen($kind) ?>
                  <span class="eintrag-text"><?= ui_e(dt_lesbar($t, (bool)$t['mehrfach'])) ?></span>
                  <?php if ($name !== ''): ?>
                    <span class="eintrag-neben"><?= ui_e($name) ?></span>
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
 * LEISTENINHALT: EINSTELLUNGEN
 *
 * Derselbe Baustein wie die Diensttage-Leiste — bis Web 8.0.1 trug das
 * Einstellungsmenü buchstäblich die Klasse `.daylist` und erbte damit jede
 * Regel der Tagesleiste, einschließlich der 100-vh-Höhe, die auf dem Handy
 * anderthalb Bildschirme vor den Inhalt schob.
 *
 * Die Administration steht als abgesetzter zweiter Block und nur für Admins.
 * „Abmelden" steht getrennt am Ende (E-P3-11).
 * ------------------------------------------------------------------------ */
function ui_leiste_einstellungen(string $aktiv): void
{
    $punkte = [
        'profil'         => ['einstellungen.php?t=profil',         'Profil',          'profil'],
        'standorte'      => ['einstellungen.php?t=standorte',      'Standorte',       'standort'],
        'rettungsmittel' => ['einstellungen.php?t=rettungsmittel', 'Rettungsmittel',  'fahrzeug'],
        'geraete'        => ['einstellungen.php?t=geraete',        'Geräte',          'uhr'],
        'backup'         => ['einstellungen.php?t=backup',         'Backup',          'sicherung'],
        'import'         => ['import.php',                         'Import / Export', 'tausch'],
    ];
    $admin = [
        'admin'                => ['admin_users.php',                       'NutzerInnen',            'gruppe'],
        'admin_standorte'      => ['admin_stammdaten.php?t=standorte',      'Standorte systemweit',   'datenbank'],
        'admin_rettungsmittel' => ['admin_stammdaten.php?t=rettungsmittel', 'Rettungsmittel systemweit', 'datenbank'],
        'admin_sicherungen'    => ['admin_sicherungen.php',                 'Sicherungen',            'sicherung'],
        'admin_demo'           => ['admin_demo.php',                        'Demo-Konto',             'kolben'],
        'wartung'              => ['update.php',                            'Wartung',                'werkzeug'],
    ];
    ?>
    <h2 class="leiste-kopfzeile">Einstellungen</h2>
    <div class="leiste-liste">
      <?php foreach ($punkte as $key => [$href, $text, $sym]): ?>
        <a class="eintrag<?= $aktiv === $key ? ' aktiv' : '' ?>" href="<?= ui_e($href) ?>"
           <?= $aktiv === $key ? 'aria-current="page"' : '' ?>>
          <?= ui_symbol($sym) ?><span class="eintrag-text"><?= ui_e($text) ?></span>
        </a>
      <?php endforeach; ?>

      <?php if (function_exists('ist_admin') && ist_admin()): ?>
        <h2 class="leiste-kopfzeile">Administration</h2>
        <?php foreach ($admin as $key => [$href, $text, $sym]): ?>
          <a class="eintrag<?= $aktiv === $key ? ' aktiv' : '' ?>" href="<?= ui_e($href) ?>"
             <?= $aktiv === $key ? 'aria-current="page"' : '' ?>>
            <?= ui_symbol($sym) ?><span class="eintrag-text"><?= ui_e($text) ?></span>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
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
 * auf „Profil" landet, verschweigt die übrigen elf Punkte.
 *
 * Die Administration steht als abgesetzter zweiter Block. „Abmelden" steht
 * getrennt am Ende, darunter nur der Name der angemeldeten Person.
 */
function ui_einstellungen_uebersicht(): void
{
    $bloecke = [['', [
        ['einstellungen.php?t=profil',         'Profil',           'profil'],
        ['einstellungen.php?t=standorte',      'Standorte',        'standort'],
        ['einstellungen.php?t=rettungsmittel', 'Rettungsmittel',   'fahrzeug'],
        ['einstellungen.php?t=geraete',        'Geräte',           'uhr'],
        ['einstellungen.php?t=backup',         'Backup',           'sicherung'],
        ['import.php',                         'Import / Export',  'tausch'],
    ]]];
    if (function_exists('ist_admin') && ist_admin()) {
        $bloecke[] = ['Administration', [
            ['admin_users.php',                       'NutzerInnen',               'gruppe'],
            ['admin_stammdaten.php?t=standorte',      'Standorte systemweit',      'datenbank'],
            ['admin_stammdaten.php?t=rettungsmittel', 'Rettungsmittel systemweit', 'datenbank'],
            ['admin_sicherungen.php',                 'Sicherungen',               'sicherung'],
            ['admin_demo.php',                        'Demo-Konto',                'kolben'],
            ['update.php',                            'Wartung',                   'werkzeug'],
        ]];
    }

    ui_seite_start(['titel' => 'Einstellungen']);
    ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => '']);
    ui_titelzeile(['titel' => 'Einstellungen', 'unter' => ui_e(ui_user_label())]);
    foreach ($bloecke as [$titel, $punkte]) {
        /* Die Blocküberschrift steht ÜBER der Karte, nicht in ihr — Mockup 07
         * zeigt „ADMINISTRATION" als gesperrte Versalzeile außerhalb
         * (Fable-Kontrolle, F-P3-W). */
        if ($titel !== '') {
            echo '  <h2 class="uebersicht-block">' . ui_e($titel) . "</h2>\n";
        }
        ui_karte_start([]);
        foreach ($punkte as [$href, $text, $sym]) {
            echo '    <a class="uebersicht-zeile" href="' . ui_e($href) . '">'
               . ui_symbol($sym, 'symbol-gross')
               . '<span class="uebersicht-text">' . ui_e($text) . '</span>'
               . ui_symbol('winkel', 'symbol-rechts uebersicht-winkel') . "</a>\n";
        }
        ui_karte_ende();
    }
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
 * Die Verweise auf Impressum und Datenschutz erscheinen erst, wenn es die
 * Seiten gibt (sie entstehen in O10). Ein Verweis, der ins Leere führt, ist
 * schlimmer als keiner: Er sieht aus wie ein Angebot.
 *
 * $o: dunkel  true = helle Schrift auf Dunkelblau (Anmeldung)
 * ------------------------------------------------------------------------ */
function ui_fuss_seite(array $o = []): void
{
    $lizenz = 'https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE';
    $rechts = [];
    foreach (['impressum' => 'Impressum', 'datenschutz' => 'Datenschutz'] as $d => $text) {
        if (is_file(__DIR__ . '/' . $d . '.php')) {
            $rechts[] = '<a href="' . $d . '.php">' . $text . '</a>';
        }
    }
    ?>
<footer class="fuss-seite<?= !empty($o['dunkel']) ? ' fuss-dunkel' : '' ?>">
  <p class="fuss-zeile">© Gen-EM · Open Source
    <a href="<?= ui_e($lizenz) ?>" target="_blank" rel="noopener">AGPL-3.0</a>
    <span class="fuss-version">v<?= ui_e(defined('WEB_VERSION') ? WEB_VERSION : '') ?></span></p>
  <?php if ($rechts): ?>
  <p class="fuss-zeile fuss-rechts"><?= implode("\n    ", $rechts) ?></p>
  <?php endif; ?>
</footer>
<?php }


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
    if (!function_exists('demo_ist_demo')) {
        if (!is_file(__DIR__ . '/demo_lib.php')) { return; }
        require_once __DIR__ . '/demo_lib.php';
    }
    $uid = $_SESSION['user_id'] ?? null;
    if (!demo_ist_demo($uid === null ? null : (int)$uid)) { return; }
    $rest = demo_reset_in();
    ?>
<div class="demo-hinweis" role="status">
  <?= ui_symbol('kolben', 'symbol-gross') ?>
  <p><strong>Demo-Konto.</strong> Alle Daten hier sind <strong>frei
  erfunden</strong>. Ausprobieren ist ausdrücklich erwünscht — ändern,
  anlegen, löschen, Uhr koppeln. Der Bestand wird
  <strong>alle 30&nbsp;Minuten</strong> auf den Ausgangsstand
  zurückgesetzt<?= $rest > 0 ? ', das nächste Mal in etwa '
      . (int)ceil($rest / 60) . '&nbsp;Minuten' : '' ?>.
  <strong>Bitte niemals echte Patienten- oder Einsatzdaten erfassen.</strong></p>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * MELDUNG  (.meldung)
 *
 * VIER TÖNE, jeder mit Symbol und optionalem fettem Auftakt (E-P3-16):
 *
 *   fehler   rosa/rot   Dreieck    „Nicht gespeichert."
 *   info     hellblau   Kreis-i    Erklärung, Zustand
 *   ok       hellblau   Haken      Vollzug: „Sicherung erstellt."
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

/** Markup einer einzelnen Meldung. Auch von den JS-Erzeugern nachgebaut. */
function ui_meldung_markup(string $ton, string $text, string $auftakt = '',
                           string $knopf = ''): string
{
    $symbole = ['fehler' => 'warnung', 'warn' => 'warnung',
                'ok' => 'haken', 'info' => 'hinweis'];
    $sym = $symbole[$ton] ?? 'hinweis';
    $m = '<div class="meldung meldung-' . ui_e($ton) . '" role="' . ($ton === 'fehler' ? 'alert' : 'status') . '">';
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
 * $o: titel, zahl, aktion ['text','href','symbol','art'], zu (bool),
 *     vorschau, klasse, id
 * ------------------------------------------------------------------------ */
function ui_karte_start(array $o = []): void
{
    $zu = !empty($o['zu']) || isset($o['vorschau']);
    $k  = 'karte' . (!empty($o['klasse']) ? ' ' . (string)$o['klasse'] : '');
    $id = !empty($o['id']) ? ' id="' . ui_e((string)$o['id']) . '"' : '';

    if ($zu) {
        echo '<details class="' . $k . ' karte-klappbar"' . $id
           . (!empty($o['offen']) ? ' open' : '') . ">\n";
        echo '  <summary class="karte-kopf">' . "\n";
        echo '    ' . ui_symbol('winkel', 'akkordeon-winkel') . "\n";
        echo '    <h2 class="karte-titel">' . ui_e((string)($o['titel'] ?? '')) . "</h2>\n";
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
        echo '    <h2 class="karte-titel">' . ui_e((string)$o['titel']) . "</h2>\n";
        if (isset($o['zahl'])) {
            echo '    <span class="karte-zahl">' . ui_e((string)$o['zahl']) . "</span>\n";
        }
        if (!empty($o['aktion'])) {
            $a = $o['aktion'];
            $art = (string)($a['art'] ?? 'blau');
            echo '    <a class="karte-aktion karte-aktion-' . ui_e($art) . '" href="'
               . ui_e((string)($a['href'] ?? '#')) . '"'
               . (!empty($a['attr']) ? ' ' . (string)$a['attr'] : '') . '>'
               . (!empty($a['symbol']) ? ui_symbol((string)$a['symbol']) : '')
               . '<span>' . ui_e((string)($a['text'] ?? '')) . "</span></a>\n";
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
 * ZEILE  (.zeile)
 *
 * Text links (fett plus Kleinzeile), Plaketten, Aktionen rechts. Am Desktop
 * sind die Aktionen Knöpfe zu 44 px, mobil ein einziges „⋯" je Zeile, das
 * dasselbe Aktionsblatt öffnet (E-P3-26).
 *
 * $o: text, klein, plaketten (Markup), aktionen (Markup), href, klasse
 * ------------------------------------------------------------------------ */
function ui_zeile(array $o): void
{
    $k = 'zeile' . (!empty($o['klasse']) ? ' ' . (string)$o['klasse'] : '');
    echo '<div class="' . $k . '">' . "\n";
    echo '  <div class="zeile-text">' . "\n";
    $t = '<span class="zeile-haupt">' . ui_e((string)($o['text'] ?? '')) . '</span>';
    echo '    ' . (!empty($o['href'])
        ? '<a href="' . ui_e((string)$o['href']) . '">' . $t . '</a>'
        : $t) . "\n";
    if (!empty($o['klein'])) {
        echo '    <span class="zeile-klein">' . ui_e((string)$o['klein']) . "</span>\n";
    }
    echo "  </div>\n";
    if (!empty($o['plaketten'])) {
        echo '  <div class="zeile-plaketten">' . (string)$o['plaketten'] . "</div>\n";
    }
    if (!empty($o['aktionen'])) {
        echo '  <div class="zeile-aktionen">' . (string)$o['aktionen'] . "</div>\n";
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
  <div class="titelzeile-haupt">
    <div class="titelzeile-text">
      <h1<?= !empty($o['titel_mobil']) ? ' data-mobil="' . ui_e((string)$o['titel_mobil']) . '"' : '' ?>><?= ui_e((string)($o['titel'] ?? '')) ?></h1>
      <?php if (!empty($o['unter'])): ?>
        <p class="titelzeile-unter"><?= (string)$o['unter'] ?></p>
      <?php endif; ?>
    </div>
    <?php if (!empty($o['aktionen'])): ?>
      <div class="titelzeile-aktionen"><?= (string)$o['aktionen'] ?></div>
    <?php endif; ?>
  </div>
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
    $id = 'aktionen-' . substr(sha1((string)($o['titel'] ?? '') . count((array)($o['eintraege'] ?? []))), 0, 8);
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
        $m .= '<a class="' . $k . '" href="' . ui_e((string)($e['href'] ?? '#')) . '"'
            . (!empty($e['attr']) ? ' ' . (string)$e['attr'] : '') . '>'
            . (!empty($e['symbol']) ? ui_symbol((string)$e['symbol']) : '')
            . '<span>' . ui_e((string)($e['text'] ?? '')) . '</span></a>';
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
 * $o: name, label, wert, art (text|date|time|number|email|password|select|
 *     textarea), optionen, klein, pflicht, attr, klasse, platzhalter
 * ------------------------------------------------------------------------ */
function ui_feld(array $o): void
{
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
      if (!empty($o['pflicht'])): ?> <span class="feld-pflicht" aria-hidden="true">*</span><?php endif; ?></label>
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
    $name = (string)($o['name'] ?? '');
    ?>
<div class="segment<?= !empty($o['klasse']) ? ' ' . ui_e((string)$o['klasse']) : '' ?>"
     role="group"<?= !empty($o['label']) ? ' aria-label="' . ui_e((string)$o['label']) . '"' : '' ?>>
  <?php $i = 0; foreach ((array)($o['optionen'] ?? []) as $wert => $text):
      $id = 'sg-' . preg_replace('/[^\w-]/', '-', $name . '-' . $wert . '-' . $i++); ?>
    <input type="radio" class="segment-box" id="<?= ui_e($id) ?>" name="<?= ui_e($name) ?>"
           value="<?= ui_e((string)$wert) ?>"
           <?= (string)$wert === (string)($o['wert'] ?? '') ? 'checked' : '' ?><?= (string)($o['attr'] ?? '') ?>>
    <label class="segment-taste" for="<?= ui_e($id) ?>"><?= ui_e((string)$text) ?></label>
  <?php endforeach; ?>
</div>
<?php }


/* ---------------------------------------------------------------------------
 * SPEICHERN-LEISTE  (.speichern)
 *
 * Klebt am unteren Rand und erscheint, sobald das Formular schmutzig ist —
 * das Dirty-Tracking dafür liegt seit Web 7.0.0 in assets/forms.js
 * (`data-dirty-track`). Mobil ein breiter Primärknopf; am Desktop der Knopf
 * links plus der Hinweis „Ungespeicherte Änderungen · Strg + Enter speichert".
 *
 * KEIN „VERWERFEN". Der Rückweg oben genügt, und ein Verwerfen-Knopf neben
 * einem Speichern-Knopf ist die Stelle, an der man sich vergreift (E-P3-29).
 *
 * $o: text, hinweis, name, wert, attr
 * ------------------------------------------------------------------------ */
function ui_speichern_leiste(array $o = []): void
{
    ?>
<div class="speichern" data-speichern hidden>
  <div class="speichern-innen">
    <?= ui_knopf([
        'text' => (string)($o['text'] ?? 'Speichern'),
        'art' => 'primaer', 'symbol' => 'haken', 'breit' => true,
        'name' => (string)($o['name'] ?? ''), 'wert' => (string)($o['wert'] ?? ''),
        'attr' => (string)($o['attr'] ?? ''),
    ]) ?>
    <p class="speichern-hinweis"><?= ui_e((string)($o['hinweis']
        ?? 'Ungespeicherte Änderungen · Strg + Enter speichert')) ?></p>
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
    $k = 'kennzahl' . (!empty($o['aktiv']) ? ' aktiv' : '')
       . (!empty($o['extrem']) ? ' kennzahl-extrem' : '');
    $m  = '<div class="' . $k . '"' . (string)($o['attr'] ?? '') . '>';
    $m .= '<p class="kennzahl-wert">' . ui_e((string)($o['wert'] ?? '–'));
    if (!empty($o['einheit'])) {
        $m .= '<span class="kennzahl-einheit">' . ui_e((string)$o['einheit']) . '</span>';
    }
    $m .= '</p>';
    $m .= '<p class="kennzahl-label">' . ui_e((string)($o['label'] ?? ''));
    if (!empty($o['extrem'])) {
        $m .= '<span class="kennzahl-tag">' . ui_e((string)$o['extrem']) . '</span>';
    }
    $m .= '</p></div>';
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
 *               `<p>suggest`, `<p>state`, `<p>chips`, `<p>dl`.
 *   such        eigenes Suchfeld erzeugen (getrennte Suche, siehe ortsfeld.js)
 *   such_hinweis / such_platzhalter
 *   label, hinweis, platzhalter, max, wert           (nur bei feld = true)
 *   name        POST-Name des Bezeichnungsfeldes; null = keiner (der Wert
 *               wandert dann verschluesselt in den pat_blob)
 *   lat_name / lon_name, lat / lon                   Koordinatenfelder
 *   datalist    Liste von Namen fuer eine <datalist> (Stammdaten-Vorschlaege)
 *   klasse      zusaetzliche Klasse am Rahmen (z. B. 'loc-inline')
 */
function ui_ortsfeld(array $o): void
{
    $p = (string)$o['praefix'];
    $mitFeld = ($o['feld'] ?? true) !== false;
    $mitSuche = !empty($o['such']);
    $dl = $o['datalist'] ?? null;

    $versteckt = !empty($o['versteckt']) ? ' hidden' : '';

    if ($mitFeld): ?>
      <div class="loc-widget <?= e((string)($o['klasse'] ?? '')) ?>"<?= $versteckt ?>>
        <label><?= e((string)($o['label'] ?? '')) ?>
          <?php if (!empty($o['hinweis'])): ?>
            <span class="muted small"><?= e((string)$o['hinweis']) ?></span>
          <?php endif; ?>
          <input type="text" id="<?= e($p) ?>addr" autocomplete="off"
                 <?= isset($o['name']) && $o['name'] !== null ? 'name="' . e((string)$o['name']) . '"' : '' ?>
                 <?= isset($o['max']) ? 'maxlength="' . (int)$o['max'] . '"' : '' ?>
                 <?= $dl !== null ? 'list="' . e($p) . 'dl"' : '' ?>
                 placeholder="<?= e((string)($o['platzhalter'] ?? '')) ?>"
                 value="<?= e((string)($o['wert'] ?? '')) ?>">
        </label>
    <?php else: ?>
      <div class="loc-widget <?= e((string)($o['klasse'] ?? '')) ?>"<?= $versteckt ?>>
    <?php endif; ?>

    <?php if ($mitSuche): ?>
      <?php /* Getrennte Suche: Das Namensfeld darüber bleibt der Name
               („Standort Kempten" ist keine Adresse). Hier wird gesucht oder
               eine Koordinate eingefügt; übernommen werden nur die
               Koordinaten. */ ?>
      <label class="fld-sub"><?= e((string)($o['such_hinweis'] ?? 'Koordinaten (optional)')) ?>
        <input type="text" id="<?= e($p) ?>such" autocomplete="off"
               placeholder="<?= e((string)($o['such_platzhalter']
                   ?? 'Adresse suchen — auch Koordinaten oder Plus Code')) ?>">
      </label>
    <?php endif; ?>

      <ul id="<?= e($p) ?>suggest" class="loc-suggest" hidden></ul>
      <?php /* Meldungszeile unmittelbar unter dem Feld: Sie sagt etwas über
               DIESES Eingabefeld aus („Koordinaten gesetzt — dieses Feld ist
               die Bezeichnung", „Bezeichnung fehlt"), nicht über den Chip
               darunter. */ ?>
      <p class="locstate" id="<?= e($p) ?>state"></p>
      <?php /* Bestätigte Koordinaten stehen als Chip UNTER dem Textfeld, nicht
               darin — sonst vernichtet die erste getippte Bezeichnung sie. */ ?>
      <div class="rmchips" id="<?= e($p) ?>chips"></div>
      <input type="hidden" id="<?= e($p) ?>lat"
             <?= isset($o['lat_name']) ? 'name="' . e((string)$o['lat_name']) . '"' : '' ?>
             value="<?= e((string)($o['lat'] ?? '')) ?>">
      <input type="hidden" id="<?= e($p) ?>lon"
             <?= isset($o['lon_name']) ? 'name="' . e((string)$o['lon_name']) . '"' : '' ?>
             value="<?= e((string)($o['lon'] ?? '')) ?>">
      <?php if ($dl !== null): ?>
        <datalist id="<?= e($p) ?>dl">
          <?php foreach ((array)$dl as $s): ?><option value="<?= e((string)$s) ?>"><?php endforeach; ?>
        </datalist>
      <?php endif; ?>
      </div>
<?php }

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
 * $patKeyCheck, $kdfSalt und $kdfIter; KDF_ITER_ZIEL kommt aus db.php.
 *
 * $o: skripte  Liste der Verweise. Vorgabe: crypto.js, keyguard.js, unlock.js.
 *              Ein leeres Feld gibt keinen Verweis aus (fuer Seiten, die ihre
 *              Verweise aus anderem Grund selbst setzen muessen).
 *     guete    true  -> zusaetzlich pwquality.js (Passwortguete, Baustein B9)
 *     wrap     false -> KEIN PAT_WRAP. Genau ein Aufrufer: einsatz.php, das
 *              die Huelle aus der API-Antwort bezieht (m.pat_wrap).
 *     keycheck true  -> zusaetzlich PAT_KEY_CHECK (Herkunftsabgleich beim
 *              Einspielen einer Sicherung)
 *     csrf     true  -> zusaetzlich CSRF
 *     einzug   Einrueckung der ausgegebenen Zeilen
 */
function ui_krypto_bootstrap(array $o = []): void
{
    global $patWrapPw, $patKeyCheck, $kdfSalt, $kdfIter;

    static $schon = false;
    if ($schon) {
        error_log('ui_krypto_bootstrap() zweimal aufgerufen — der zweite Aufruf '
                . 'wurde uebergangen. Beide Zweige einer Seite duerfen das '
                . 'Ruestzeug nur EINMAL anfordern.');
        return;
    }
    $schon = true;

    $ein = (string)($o['einzug'] ?? '');
    $skripte = $o['skripte'] ?? ['assets/crypto.js', 'assets/keyguard.js', 'assets/unlock.js'];
    if (!empty($o['guete'])) { $skripte[] = 'assets/pwquality.js'; }

    $zeilen = [];
    foreach ($skripte as $s) {
        $zeilen[] = '<script src="' . ui_asset((string)$s) . '"></script>';
    }
    $zeilen[] = '<script>';
    if (($o['wrap'] ?? true) !== false) {
        $zeilen[] = 'const PAT_WRAP = ' . json_encode($patWrapPw) . ';';
    }
    if (!empty($o['keycheck'])) {
        $zeilen[] = 'const PAT_KEY_CHECK = ' . json_encode($patKeyCheck) . ';';
    }
    $zeilen[] = 'const KDF_SALT = ' . json_encode($kdfSalt) . ';';
    /* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
       gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
       bekommt einen anderen Schluessel. */
    $zeilen[] = 'const KDF_ITER      = ' . json_encode($kdfIter) . ';';
    $zeilen[] = 'const KDF_ITER_ZIEL = ' . json_encode(KDF_ITER_ZIEL) . ';';
    if (!empty($o['csrf'])) {
        $zeilen[] = 'const CSRF = ' . json_encode($_SESSION['csrf'] ?? '') . ';';
    }
    $zeilen[] = '</script>';

    echo $ein, implode("\n" . $ein, $zeilen), "\n";
}

