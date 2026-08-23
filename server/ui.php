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
 *   klasse   Klasse am <body> (z. B. 'login-body'); fehlt sie, hat das
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
        '<html lang="de">',
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
 * Hinweis- und Fehlerzeile ueber dem Seiteninhalt.
 *
 * WARUM ES SIE GIBT (P0/A6, Befund C2). Dieselben zwei Zeilen standen in 13
 * Dateien — 21-mal derselbe Dreisatz aus Abfrage, Klasse und Maskierung.
 * Uneinheitlich war dabei nur eines: die Klasse der Hinweiszeile. Elf Stellen
 * schrieben "alert-info", zwei "alert-ok" (Stammdaten und Nachbearbeitung,
 * beide melden dort einen Vollzug). Deshalb der dritte Parameter — nicht als
 * Vorrat fuer kuenftige Toene, sondern weil der Bestand zwei kennt.
 *
 * Reihenfolge: erst der Hinweis, dann der Fehler. Genau so stand es an allen
 * Stellen ausser login.php, wo beide einander ausschliessen ($hinweis wird nur
 * ohne $error gezeigt) — dort ist die Reihenfolge ohne Wirkung.
 *
 * $einzug ruecken die ZWEITE Zeile ein: Die erste erbt die Einrueckung des
 * <?php-Tags im Aufrufer, die zweite nicht. Nur noetig, wo beide Zeilen
 * zugleich erscheinen koennen.
 */
function ui_meldung(?string $hinweis, ?string $fehler = null,
                    string $ton = 'info', string $einzug = ''): void
{
    $zeilen = [];
    if ($hinweis !== null && $hinweis !== '') {
        $zeilen[] = '<p class="alert alert-' . ui_e($ton) . '">' . ui_e($hinweis) . '</p>';
    }
    if ($fehler !== null && $fehler !== '') {
        $zeilen[] = '<p class="alert">' . ui_e($fehler) . '</p>';
    }
    if ($zeilen === []) { return; }
    echo implode("\n" . $einzug, $zeilen), "\n";
}

function ui_user_label(): string {
    global $userName, $userEmail;
    return ($userName !== null && $userName !== '') ? $userName : (string)$userEmail;
}

/** Kopfleiste: Vogel-Icon + Titel + Name; Menü Übersicht / Suche / ⚙ */
function ui_topbar(string $active): void { ?>
<header class="topbar">
  <a class="brand" href="index.php">
    <img src="<?= asset('assets/images/gen-em_logo_helicopter_weiss.svg') ?>" alt="">
    <span>Einsatzdokumentation Notarzt – <?= e(ui_user_label()) ?></span>
  </a>
  <nav class="mainnav">
    <a href="index.php" <?= $active === 'uebersicht' ? 'class="active"' : '' ?>>Übersicht</a>
    <a href="suche.php" <?= $active === 'suche' ? 'class="active"' : '' ?>>Suche</a>
    <a class="gearlink <?= $active === 'einstellungen' ? 'active' : '' ?>"
       href="einstellungen.php?t=profil" title="Einstellungen" aria-label="Einstellungen">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">
        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
        <circle cx="12" cy="12" r="1.9"/>
      </svg></a>
  </nav>
</header>
<?php ui_demo_banner(); }

/**
 * Banner im Demo-Konto (E-P1-18).
 *
 * DAUERHAFT, nicht wegklickbar. Ein Hinweis, den man einmal schliesst, ist
 * beim zweiten Besuch nicht mehr da — und genau dann waere er noetig: Wer
 * nach einer Pause wiederkommt, findet seine Eingaben nicht mehr vor und
 * soll wissen, warum.
 *
 * Er nennt VIER Dinge, und alle vier sind noetig: dass die Daten erfunden
 * sind (sonst liest jemand sie als echte Faelle), dass Ausprobieren
 * erwuenscht ist (sonst traut sich niemand), dass alles regelmaessig
 * verworfen wird (sonst ist die Ueberraschung gross) und dass hier keine
 * echten Daten hineingehoeren (das ist der Punkt, an dem es ernst wird —
 * das Schluesselmaterial dieses Kontos liegt auf dem Server).
 *
 * Steht in der Huelle, nicht auf einzelnen Seiten: Sonst fehlte er auf der
 * einen Seite, die jemand zu ergaenzen vergisst.
 */
function ui_demo_banner(): void
{
    require_once __DIR__ . '/demo_lib.php';
    $uid = $_SESSION['user_id'] ?? null;
    if (!demo_ist_demo($uid === null ? null : (int)$uid)) { return; }
    $rest = demo_reset_in();
    ?>
<div class="demobanner" role="status">
  <strong>Demo-Konto.</strong> Alle Daten hier sind <strong>frei
  erfunden</strong>. Ausprobieren ist ausdrücklich erwünscht — ändern,
  anlegen, löschen, Uhr koppeln. Der Bestand wird
  <strong>alle 30&nbsp;Minuten</strong> auf den Ausgangsstand
  zurückgesetzt<?= $rest > 0 ? ', das nächste Mal in etwa '
      . (int)ceil($rest / 60) . '&nbsp;Minuten' : '' ?>.
  <strong>Bitte niemals echte Patienten- oder Einsatzdaten erfassen.</strong>
</div>
<?php }

/**
 * Abbruchseite: Der aufgerufene Datensatz existiert nicht, gehoert einem
 * anderen Konto oder liegt im Papierkorb — hier ist Schluss.
 *
 * WARUM ES SIE GIBT (P0/A6, Befund C3). An 16 Stellen stand dafuer
 * `exit('Einsatz nicht gefunden.')`: nackter Text ohne Zeichensatzangabe, ohne
 * Kopfleiste, ohne Weg zurueck. Wer einen veralteten Link oeffnet, landete in
 * einer weissen Seite mit sechs Woertern und musste die Zurueck-Taste finden.
 * Der HTTP-Code stimmte, die Seite war trotzdem eine Sackgasse.
 *
 * Wortlaut und Code bleiben unveraendert — nur die Verpackung kommt hinzu.
 *
 * VORAUSSETZUNG: auth_guard.php ist geladen (ui_topbar() braucht $userEmail).
 * Alle 16 Aufrufstellen liegen hinter der Anmeldung; zwei davon stehen in
 * auth_guard.php selbst, in Funktionen, die erst nach dessen Durchlauf gerufen
 * werden.
 *
 * $o: titel        Titel der Seite; fehlt er, ergibt ihn der HTTP-Code
 *     zurueck      Ziel des Rueckwegs (Vorgabe: index.php)
 *     zurueck_text Beschriftung des Rueckwegs
 */
function ui_abbruch(int $code, string $text, array $o = []): never
{
    http_response_code($code);
    $titel = (string)($o['titel'] ?? match ($code) {
        404     => 'Nicht gefunden',
        403     => 'Kein Zugriff',
        default => 'Nicht möglich',
    });
    ui_seite_start(['titel' => $titel]);
    ui_topbar('');
    $ziel = (string)($o['zurueck'] ?? 'index.php');
    $wort = (string)($o['zurueck_text'] ?? 'Zur Übersicht');
    echo '<main class="page">' . "\n";
    echo '  <p class="alert">' . ui_e($text) . '</p>' . "\n";
    echo '  <p><a class="add-link" href="' . ui_e($ziel) . '">← ' . ui_e($wort) . '</a></p>' . "\n";
    echo '</main>' . "\n";
    ui_seite_ende();
    exit;
}

/**
 * Untermenue der Einstellungen — identisch auf einstellungen.php, admin_users.php,
 * admin_user.php und admin_stammdaten.php. Die Administration (eigener,
 * abgesetzter Block) erscheint nur fuer Admins.
 *
 * $active: profil | standorte | rettungsmittel | backup | import | geraete
 *          | admin | admin_standorte | admin_rettungsmittel | admin_sicherungen
 *          | wartung
 *
 * ZWEI EINTRAEGE STATT EINEM (Web 7.0.0). „Standortdaten" hiess der Punkt, an
 * dem sich alles sammelte: Standorte, Rettungsmittel, Besatzungen, Zielkliniken,
 * Bergwacht. Der Name war irrefuehrend — Standortdaten sind die Daten EINES
 * Standorts, hier standen aber die Standorte selbst und ihr gesamter Inhalt.
 *
 * Jetzt trennt der Schnitt danach, was man tut:
 *   Standorte       Standorte anlegen und auswaehlen — und sonst nichts.
 *   Rettungsmittel  was an den ausgewaehlten Standorten haengt.
 * Dieselbe Zweiteilung gilt in der Administration fuer die systemweiten
 * Eintraege.
 */
function ui_settings_sidebar(string $active): void {
    $items = [
        'profil'         => ['einstellungen.php?t=profil', 'Profil'],
        'standorte'      => ['einstellungen.php?t=standorte', 'Standorte'],
        'rettungsmittel' => ['einstellungen.php?t=rettungsmittel', 'Rettungsmittel'],
        'backup'         => ['einstellungen.php?t=backup', 'Backup'],
        // Eigene Seite (import.php), erscheint aber als Eintrag der
        // Einstellungen — wie admin_stammdaten.php.
        'import'         => ['import.php', 'Import / Export'],
        'geraete'        => ['einstellungen.php?t=geraete', 'Geräte'],
    ];
    ?>
  <aside class="daylist">
    <h2>Einstellungen</h2>
    <ul>
      <?php foreach ($items as $key => [$href, $label]): ?>
        <li><a href="<?= $href ?>" <?= $active === $key ? 'class="active"' : '' ?>><?= $label ?></a></li>
      <?php endforeach; ?>
      <li><a href="logout.php" data-confirm="Wirklich abmelden?" data-confirm-ok="Abmelden"
             data-confirm-tone="normal">Abmelden</a></li>
    </ul>
    <?php if (ist_admin()): ?>
      <h2 class="sidebar-subhead">Administration</h2>
      <ul>
        <li><a href="admin_users.php" <?= $active === 'admin' ? 'class="active"' : '' ?>>NutzerInnenverwaltung</a></li>
        <?php /* Aufgeteilt wie in der Kontoansicht (Web 7.0.0): erst die
                 Standorte, dann was an ihnen haengt. „Zentrale Stammdaten" war
                 EIN Eintrag fuer sechs Datenarten. */ ?>
        <li><a href="admin_stammdaten.php?t=standorte" <?= $active === 'admin_standorte' ? 'class="active"' : '' ?>>Standorte systemweit</a></li>
        <li><a href="admin_stammdaten.php?t=rettungsmittel" <?= $active === 'admin_rettungsmittel' ? 'class="active"' : '' ?>>Rettungsmittel systemweit</a></li>
        <li><a href="admin_sicherungen.php" <?= $active === 'admin_sicherungen' ? 'class="active"' : '' ?>>Sicherungen</a></li>
        <?php /* Eigener Eintrag statt eines Abschnitts in der
                 NutzerInnenverwaltung: Dort stehen Konten in einer Liste,
                 hier geht es um EIN Konto mit eigenen Regeln, dessen Zustand
                 man sehen will, bevor man etwas tut. */ ?>
        <li><a href="admin_demo.php" <?= $active === 'admin_demo' ? 'class="active"' : '' ?>>Demo-Konto</a></li>
        <?php /* Wartung war bis Web 4.5.2 nur ueber die direkte Adresse
           erreichbar. Das machte die Auskunft aus M3-05 wertlos: Sie meldet,
           dass der Aufraeumjob dauerhaft scheitert — auf einer Seite, die
           niemand oeffnet, meldet sie das niemandem.

           Der Eintrag ist gefahrlos: Die Seite fuehrt beim blossen Aufrufen
           NICHTS aus (Abnahmekriterium A19), sie zeigt nur an, was anstuende. */ ?>
        <li><a href="update.php" <?= $active === 'wartung' ? 'class="active"' : '' ?>>Wartung</a></li>
      </ul>
    <?php endif; ?>
  </aside>
<?php }

/**
 * Diensttage-Leiste (serverseitig, auf allen Inhaltsseiten identisch).
 *
 * SIE LISTET JETZT DIENSTTAGE, NICHT KALENDERTAGE (E9, Web 6.0.0). Bis dahin
 * entstand die Liste aus den vorkommenden DATEN in drei Tabellen — sie musste
 * es, weil ein Flugtag ohne eigene Zeile Einsaetze haben konnte. Seit
 * `missions.day_id` ein Fremdschluessel ist, gibt es das nicht mehr: Jeder
 * Einsatz haengt an einer Zeile in `days`, und diese Zeile IST der Eintrag.
 *
 * Zwei Dienste an einem Kalendertag stehen deshalb als zwei Zeilen
 * untereinander. Auseinandergehalten werden sie durch die Uhrzeit des
 * Dienstbeginns — aber nur DANN: Im Regelfall, ein Dienst am Tag, kostet sie
 * nur Breite in einer Leiste, die auf schmalen Geraeten ohnehin knapp ist.
 *
 * Das Symbol der Art (E27, A7c) steht am Anfang der Zeile mit einer
 * Textalternative in `title` und `aria-label` — die Auskunft haengt nicht an
 * der Grafik.
 */
function ui_days_sidebar(?int $currentDayId): void {
    global $userId;
    ui_hat_tagesleiste(true);   // fuer ui_footer(), s. dort
    require_once __DIR__ . '/diensttag_lib.php';
    $tage = dt_liste($userId, 500);

    // Nach Jahr -> Monat gruppieren (je Y => M => [Diensttage]), Reihenfolge
    // bleibt absteigend, da $tage bereits absteigend sortiert aus der DB kommt.
    $monatsnamen = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $baum = [];
    foreach ($tage as $t) {
        $d = (string)$t['day'];
        $baum[substr($d, 0, 4)][substr($d, 5, 2)][] = $t;
    }

    // Welches Jahr/Monat soll offen sein? Der aktuell gewaehlte Diensttag hat
    // Vorrang, sonst der juengste vorhandene (oberstes Jahr/oberster Monat).
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
<aside class="daylist">
  <h2>Diensttage</h2>
  <div class="dayyears">
    <?php if (!$baum): ?><p class="muted daylist-empty">noch keine</p><?php endif; ?>
    <?php foreach ($baum as $jahr => $monate):
        // Achtung: PHP macht aus numerischen Array-Schluesseln Integer
        // ("2026" -> 2026, "12" -> 12, "07" bleibt String). Deshalb ueberall
        // ausdruecklich nach String wandeln — sonst bricht e() unter
        // strict_types ab und Monatsvergleiche schlagen ab Oktober fehl.
        $jahrS = (string)$jahr; ?>
      <details class="yearblock" <?= $jahrS === $offenesJahr ? 'open' : '' ?>>
        <summary><a class="zeitlink" href="zeitraum.php?y=<?= e($jahrS) ?>"><?= e($jahrS) ?></a></summary>
        <?php foreach ($monate as $monat => $monatsTage):
            $monatS = str_pad((string)$monat, 2, '0', STR_PAD_LEFT); ?>
          <details class="monthblock"
                    <?= ($jahrS === $offenesJahr && $monatS === $offenerMonat) ? 'open' : '' ?>>
            <summary><a class="zeitlink"
                        href="zeitraum.php?y=<?= e($jahrS) ?>&amp;m=<?= e($monatS) ?>"><?= e($monatsnamen[(int)$monatS]) ?></a></summary>
            <ul>
              <?php foreach ($monatsTage as $t):
                  $sym = dt_art_symbol($t['kind'] === null ? null : (string)$t['kind']);
                  $titel = $sym['text'];
                  if ($t['vehicle_name'] !== null && $t['vehicle_name'] !== '') {
                      $titel = (string)$t['vehicle_name'] . ' — ' . $sym['text'];
                  } ?>
                <li><a href="index.php?d=<?= (int)$t['id'] ?>"
                       <?= (int)$t['id'] === $currentDayId ? 'class="active"' : '' ?>><span
                       class="artzeichen" title="<?= e($titel) ?>"
                       aria-label="<?= e($titel) ?>"><?= e($sym['zeichen']) ?></span>
                       <?= e(dt_lesbar($t, (bool)$t['mehrfach'])) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </details>
        <?php endforeach; ?>
      </details>
    <?php endforeach; ?>
  </div>
    <?php
      require_once __DIR__ . '/trash_lib.php';
      $trashLeer = !trash_list_days($userId) && !trash_list_missions($userId);
      require_once __DIR__ . '/nachbearbeitung_lib.php';
      $nbOffen = nb_offen_gesamt($userId);
    ?>
    <?php /* Die Nachbearbeitung erscheint NUR, solange etwas offen ist (E24,
             A12). Ein dauerhafter Eintrag fuer eine einmalige Aufgabe waere
             genau der Hinweis, den man nicht loswird. */ ?>
    <?php if ($nbOffen > 0): ?>
      <a class="dayadd nachbearbeitung" href="nachbearbeitung.php"
         title="Zuordnungen nachtragen">
        Zuordnung offen (<?= (int)$nbOffen ?>)
      </a>
    <?php endif; ?>
    <a class="dayadd" href="diensttag_neu.php" title="Diensttag von Hand anlegen">
      + Diensttag anlegen
    </a>
    <a class="trashlink<?= $trashLeer ? ' leer' : '' ?>" href="papierkorb.php"
       title="<?= $trashLeer ? 'Papierkorb ist leer' : 'Papierkorb' ?>">
      <!-- viewBox auf die Zeichnung zugeschnitten (x 4-20, y 3-21): Ohne den
           Leerraum entspricht die CSS-Hoehe direkt der sichtbaren Groesse. -->
      <svg viewBox="4 3 16 18" xmlns="http://www.w3.org/2000/svg"
           fill="currentColor" aria-hidden="true">
        <path d="M9 3h6l1 1h4v2H4V4h4l1-1zM6 7h12l-1 13.1c-.06.5-.5.9-1 .9H8c-.5 0-.94-.4-1-.9L6 7zm3.5 2.6v9h1.6v-9H9.5zm3.4 0v9h1.6v-9h-1.6z"/>
      </svg>
      <span>Papierkorb</span>
    </a>

</aside>
<?php }

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

/**
 * Merkzettel: Steht auf dieser Seite die Einsatztage-Leiste?
 *
 * ui_days_sidebar() traegt sich ein, ui_footer() liest es. Die Reihenfolge
 * stimmt auf allen Seiten, die beides benutzen: Die Leiste steht oben im
 * Layout, die Fusszeile unten.
 */
function ui_hat_tagesleiste(bool $setzen = false): bool
{
    static $ja = false;
    if ($setzen) { $ja = true; }
    return $ja;
}

/** Fusszeile: im Dokumentfluss, rechtsbündig unter dem Inhalt */
function ui_footer(): void { ?>
  <script src="<?= asset('assets/confirm.js') ?>"></script>
  <?php /* daylist.js belebt das Jahr/Monat-Akkordeon der Einsatztage-Leiste.
           Bis Web 7.1.0 kam es auf JEDER Seite mit — auch auf Einstellungen,
           Import, Administration und Wartung, die keine Leiste haben. Dort
           sucht das Skript .dayyears, findet nichts und kehrt zurueck: eine
           Anfrage und ein Parse-Durchgang fuer nichts. */ ?>
  <?php if (ui_hat_tagesleiste()): ?>
  <script src="<?= asset('assets/daylist.js') ?>"></script>
  <?php endif; ?>
<footer class="sitefooter">© Gen-EM – OpenSource Software –
  <a href="https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE"
     target="_blank" rel="noopener">AGPL-3.0</a>
  <span class="ver">v<?= e(WEB_VERSION) ?></span></footer>
<?php }
