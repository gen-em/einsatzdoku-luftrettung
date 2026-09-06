<?php
declare(strict_types=1);

/**
 * WARTUNGSMODUS (S5 Paket W) — die Installation voruebergehend fuer alle
 * ausser der Administration schliessen.
 *
 * WOZU. Ein Update laeuft heute so: Push auf `main`, FTPS laedt `server/`
 * hoch, danach ruft eine Administratorin `update.php` und laesst die
 * Migration laufen. Zwischen der ersten und der letzten hochgeladenen Datei
 * stehen alte und neue nebeneinander, und zwischen dem Hochladen und der
 * Migration erwartet neuer Code Tabellen, die es noch nicht gibt. In diesem
 * Fenster antwortet die Anwendung mit 500 — einer Uhr gegenueber, einem
 * Handy gegenueber, einer Notaerztin gegenueber, die gerade dokumentiert.
 *
 * Mit dem Wartungsmodus antwortet sie stattdessen mit **503**. Das ist der
 * Unterschied zwischen „kaputt" und „gleich wieder da": Der JSON-Vertrag
 * sagt zu 5xx „spaeter unveraendert erneut versuchen" (Abschnitt 5), und
 * Uhr wie Handy halten sich daran — sie puffern und liefern nach. **Kein
 * Client wird dafuer geaendert** (E-S5W-08); das Verhalten ist seit S4 da
 * und im S4-Pruefprotokoll gemessen.
 *
 * DREI EIGENSCHAFTEN, DIE DIESE DATEI TRAGEN MUSS
 *
 *   1. OHNE DATENBANK. Der Zustand steht in einer DATEI (`wartung.lock`,
 *      E-S5W-02), nicht in `app_state`. Der Wartungsmodus wird gerade
 *      dann gebraucht, wenn die Datenbank umgebaut wird oder eine Migration
 *      auf halber Strecke gescheitert ist. Ein Schalter, der die Datenbank
 *      fragt, ob er schalten darf, ist im entscheidenden Moment stumm.
 *
 *   2. OHNE ABHAENGIGKEITEN. Diese Datei laedt NICHTS — kein `db.php`, kein
 *      `ui.php`, kein `session_lib.php`. Sie wird aus `db.php` heraus
 *      aufgerufen, bevor irgendetwas eine Verbindung aufbaut, und sie
 *      antwortet auch dann noch, wenn alles andere gerade ersetzt wird.
 *      Deshalb schreibt sie ihre drei Kopfzeilen selbst, statt `json_out()`
 *      zu benutzen: Die eine Stelle, die im Umbau antworten muss, darf
 *      nicht davon abhaengen, dass der Umbau schon fertig ist.
 *
 *   3. DIE DATEI IST DER SCHALTER, NICHT IHR INHALT. Ist `wartung.lock` da,
 *      aber unlesbar oder kein gueltiges JSON, gilt die Wartung TROTZDEM;
 *      der Balken zeigt dann „seit unbekannt" (Konzept 4.1). Andersherum
 *      waere es falsch: Ein Tippfehler im Inhalt darf keine Installation
 *      oeffnen, die jemand ausdruecklich geschlossen hat.
 *
 * WAS SIE NICHT IST. Kein Ersatz fuer `install.lock` (das sperrt nur den
 * Einrichter), keine Zeitsteuerung, kein automatisches Ausschalten
 * (E-S5W-05). Der Torwaechter aus Rahmenplan R40 (4) — Wartung automatisch
 * bei ausstehender Migration — ist P5 und wird denselben Zustand setzen.
 *
 * Konzept: `docs/konzepte/Konzept-S5-Zusatz-Wartungsmodus.md`.
 * Betriebsablauf: `docs/Technik.md`, Abschnitt 7 (Runbook).
 */

/** Der Schalter. Liegt neben `install.lock` und ist wie diese nur auf dem
 *  Server — `.gitignore` UND Ausnahmeliste des Deploys (E-S5W-02). */
const WARTUNG_DATEI = __DIR__ . '/wartung.lock';

/** Hinweis an Browser und Werkzeuge, in Sekunden (E-S5W-12, F-S5W-04).
 *  Die Geraete halten ihren eigenen Backoff; dieser Wert steuert sie nicht. */
const WARTUNG_RETRY_S = 300;

/**
 * Skripte, die auch im Wartungsmodus antworten (E-S5W-04).
 *
 * Verglichen wird der DATEINAME des laufenden Skripts, nicht die Adresse:
 * `login.php` laedt `db.php` als allererstes (Zeile 4) — das Tor muss die
 * Ausnahme also kennen, bevor irgendetwas die Datenbank beruehrt, und ein
 * Pfadmuster waere an dieser Stelle zu spaet und zu ungenau.
 *
 * Warum jede einzelne:
 *   betrieb_updates.php  die Arbeit selbst (seit Web 15.1.0) — sie traegt den
 *                        Schalter UND die Migrationen. Ohne sie schaltete man
 *                        die Wartung ein und saesse davor: Die Seite, auf der
 *                        der Ausschalter steht, waere die erste, die 503
 *                        antwortet. Genau das ist in der Bedienpruefung von
 *                        S8/AP2 passiert (F-S8-P-04).
 *   update.php           der Notausgang und die alte Adresse. Sie wird in AP3
 *                        eine Weiterleitung (Nr. 77) und bleibt so lange in
 *                        der Liste: Eine Weiterleitung, die im Wartungsmodus
 *                        503 antwortet, fuehrt niemanden mehr auf die neue
 *                        Seite. Der CLI-Aufruf ist ohnehin nie getort.
 *   betrieb_jobs.php     der Zustand der Jobs waehrend der Wartung — das
 *                        Komplett-Backup der Kette laeuft GENAU DANN
 *                        (`jobs.php` unten), und wer wissen will, ob es
 *                        durchgelaufen ist, braucht diese Seite offen.
 *   betrieb_server.php   die Belegung. Wer waehrend eines Updates merkt, dass
 *                        die Grenze erreicht ist, muss sie hier anheben
 *                        koennen — sonst scheitert das Backup, das dem
 *                        Update vorausgehen soll.
 *   wiederherstellen.php der Rueckweg, wenn die Migration schiefging.
 *   jobs.php             der Token-Weg. Das Komplett-Backup der Kette laeuft
 *                        WAEHREND der Wartung — genau dann ist es
 *                        konsistent, weil niemand sonst schreibt.
 *   login.php            damit eine abgemeldete Administratorin hineinkommt.
 *                        Was danach geschieht, entscheidet login.php selbst
 *                        (E-S5W-09): Admin weiter, alles andere sofort
 *                        wieder abgemeldet und auf die Wartungsseite.
 *   logout.php           wer drin ist, muss auch wieder hinaus.
 *   install.php          hat mit `install.lock` seine eigene Sperre.
 *
 * NICHT in der Liste und trotzdem nie betroffen: alles unter `assets/` —
 * Stylesheet, Schriften, Symbole laufen gar nicht durch PHP.
 */
const WARTUNG_AUSNAHMEN = [
    'betrieb_status.php',
    'betrieb_statistik.php',
    'betrieb_updates.php',
    'betrieb_jobs.php',
    'betrieb_server.php',
    'update.php',
    'wiederherstellen.php',
    'jobs.php',
    'login.php',
    'logout.php',
    'install.php',
];

/** Steht der Wartungsmodus? Eine Dateipruefung, sonst nichts. */
function wartung_aktiv(): bool
{
    /* clearstatcache(), weil derselbe Prozess die Datei kurz zuvor
     * geschrieben oder geloescht haben kann (betrieb_updates.php schaltet und
     * zeigt danach den Balken). Ohne den Aufruf zeigte die Seite den Zustand
     * von vor dem Klick. */
    clearstatcache(true, WARTUNG_DATEI);
    return file_exists(WARTUNG_DATEI);
}

/**
 * Was in der Datei steht: `seit` (ISO-Zeit) und `von` (Anzeigename).
 *
 * Beide koennen null sein — die Datei ist der Schalter, nicht ihr Inhalt
 * (Konzept 4.1). Wer hier null bekommt, zeigt „seit unbekannt" und schaltet
 * nicht etwa die Wartung ab.
 */
function wartung_daten(): array
{
    $leer = ['seit' => null, 'von' => null];
    if (!wartung_aktiv()) { return $leer; }
    $roh = @file_get_contents(WARTUNG_DATEI);
    if ($roh === false || $roh === '') { return $leer; }
    $d = json_decode($roh, true);
    if (!is_array($d)) { return $leer; }
    $seit = isset($d['seit']) && is_string($d['seit']) && $d['seit'] !== '' ? $d['seit'] : null;
    $von  = isset($d['von'])  && is_string($d['von'])  && $d['von']  !== '' ? $d['von']  : null;
    return ['seit' => $seit, 'von' => $von];
}

/**
 * Einschalten. Idempotent: Ein zweiter Aufruf ueberschreibt Zeitpunkt und
 * Konto nicht — sonst verlore ein versehentlicher zweiter Klick die
 * Auskunft, seit wann die Wartung wirklich steht.
 *
 * Rueckgabe false = die Datei liess sich nicht schreiben (Rechte). Der
 * Aufrufer sagt das MIT PFAD; nichts Stilles (Konzept 4.2).
 */
function wartung_einschalten(string $von): bool
{
    if (wartung_aktiv()) { return true; }
    $inhalt = json_encode([
        'seit' => gmdate('Y-m-d\TH:i:s\Z'),
        'von'  => $von,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $ok = @file_put_contents(WARTUNG_DATEI, (string)$inhalt . "\n", LOCK_EX);
    clearstatcache(true, WARTUNG_DATEI);
    return $ok !== false;
}

/** Ausschalten. Idempotent. Rueckgabe false = Loeschen scheiterte. */
function wartung_ausschalten(): bool
{
    if (!wartung_aktiv()) { return true; }
    $ok = @unlink(WARTUNG_DATEI);
    clearstatcache(true, WARTUNG_DATEI);
    return $ok;
}

/** Laeuft dieses Skript ueberhaupt im Web? Auf der Kommandozeile nie tor. */
function wartung_cli(): bool
{
    return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
}

/** Ist das laufende Skript von der Wartung ausgenommen (E-S5W-04)? */
function wartung_ausnahme(): bool
{
    $skript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return in_array($skript, WARTUNG_AUSNAHMEN, true);
}

/**
 * Erwartet das Gegenueber JSON?
 *
 * Zwei Gruende, warum das nicht `ist_api_aufruf()` aus `auth_guard.php` ist:
 * Die Datei laedt nichts (siehe Kopf), und die Frage ist hier weiter — die
 * Geraete-Endpunkte liegen NICHT unter `/api/`, sondern als `ingest.php`
 * und `pair.php` im Wurzelverzeichnis. Genau die beiden muessen ihr 503 als
 * JSON bekommen, sonst laeuft der Nachlieferungsweg der Uhr in eine
 * HTML-Seite.
 */
function wartung_json_gefragt(): bool
{
    $pfad = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (str_contains($pfad, '/api/')) { return true; }
    $skript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return $skript === 'ingest.php' || $skript === 'pair.php';
}

/**
 * DAS TOR. Genau ein Aufruf, in `db.php`, hinter `json_out()` und vor jeder
 * Datenbankverbindung (E-S5W-06).
 *
 * Es steht dort und nicht in `auth_guard.php`, weil `auth_guard.php` nur die
 * SEITEN durchlaufen. `ingest.php` und `pair.php` laden `db.php` direkt —
 * und das sind die beiden, auf die es ankommt: Sie bringen die Daten der
 * Uhr. Ein Tor, das sie nicht sieht, sperrt die Menschen aus und laesst die
 * Geraete in die Baustelle laufen.
 */
function wartung_tor(): void
{
    if (wartung_cli())      { return; }
    if (!wartung_aktiv())   { return; }
    if (wartung_ausnahme()) { return; }
    if (wartung_json_gefragt()) { wartung_antwort_json(); }
    wartung_antwort_seite();
}

/** Die gemeinsamen Kopfzeilen beider Antworten. */
function wartung_kopfzeilen(): void
{
    http_response_code(503);
    header('Retry-After: ' . WARTUNG_RETRY_S);
    /* Kein Zwischenspeichern: Eine 503 ist ein Zustand von Minuten. Was ein
     * Zwischenspeicher davon behielte, ueberlebte das Ausschalten. */
    header('Cache-Control: no-store');
}

/**
 * 503 als JSON — fuer Geraete und Browser-Skripte.
 *
 * `error` ist die Zusage an die Clients (Vertrag 5xx: spaeter unveraendert
 * erneut). `meldung` ist fuer den Menschen vor dem Browser: `export.js`,
 * `import_ui.js` und `schneiden.js` zeigen sie heute schon an, ohne auf den
 * Zahlencode zu sehen — deshalb steht sie hier und deshalb war E-S5W-10
 * kostenlos.
 */
function wartung_antwort_json(): never
{
    wartung_kopfzeilen();
    header('Content-Type: application/json');
    echo json_encode([
        'error'   => 'maintenance',
        'meldung' => 'NAdoku wird gerade aktualisiert. Bitte später erneut.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 503 als Seite.
 *
 * OHNE `ui.php`: Dessen Seitenhuelle zieht ueber `ui_favicon()` und
 * `logo_stamm()` die Datenbank herein, und genau die ist im Wartungsfall
 * das, was umgebaut wird. Das Stylesheet darf verlinkt werden — es ist eine
 * statische Datei und laeuft nicht durch PHP.
 *
 * DAS LOGO WIRFT EINE MUENZE. `logo_stamm()` faellt aus (Datenbank), und ein
 * fest eingebautes Logo waere eine Aussage: Diese Anwendung dokumentiert
 * Luft- UND bodengebundene Notarzteinsaetze, und die Wahl „wechselnd" ist
 * genau deshalb der Installationsstandard. Der Wurf hier ist derselbe wie in
 * `logo_aufloesen()` — nur ohne Sitzung und ohne Datenbank. Wer ein eigenes
 * Logo eingestellt hat, sieht waehrend der Wartung eines der beiden
 * Standardlogos; das ist der Preis dafuer, dass diese Seite ohne Datenbank
 * auskommt (Konzept 4.3).
 *
 * KEIN SKRIPT auf dieser Seite. Sie soll auch dann stehen, wenn die
 * Skriptdateien gerade zur Haelfte hochgeladen sind.
 */
function wartung_antwort_seite(): never
{
    wartung_kopfzeilen();
    header('Content-Type: text/html; charset=utf-8');
    echo wartung_seite_html();
    exit;
}

/**
 * Das Markup der Wartungsseite — getrennt von der Ausgabe, damit die
 * Wartungsprobe es prüfen kann, ohne einen Prozess zu beenden.
 *
 * Bausteine aus dem Vorrat (Design.md 9): `.rahmen`, `.rahmen-lesespalte`,
 * `.inhalt`, `.text`, `.meldung`/`.meldung-warn`. Kein neuer Baustein —
 * deshalb brauchte diese Seite keine eigene Freigabe mit Mockup; ihr Text
 * steht wortgleich im Konzept (4.3) und ist damit freigegeben.
 */
function wartung_seite_html(): string
{
    $stamm = random_int(0, 1) === 1 ? 'gen-em_logo_nef' : 'gen-em_logo_helicopter';
    $logo  = 'assets/images/' . $stamm . '.svg';
    /* Erkennungswert wie asset() ihn setzt, aber ohne db.php: die
     * Aenderungszeit der Datei. Faellt sie aus, bleibt der Verweis nackt —
     * schlimmstenfalls zeigt ein Browser ein altes Stylesheet, und die Seite
     * bleibt lesbar. */
    $v = static function (string $rel): string {
        $abs = __DIR__ . '/' . $rel;
        $t = @filemtime($abs);
        return $rel . ($t !== false ? '?v=' . $t : '');
    };
    $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    return '<!doctype html>' . "\n"
      . '<html lang="de">' . "\n"
      . '<head>' . "\n"
      . '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . "\n"
      . '<title>Wartung — Gen-EM NAdoku</title>' . "\n"
      . '<link rel="stylesheet" href="' . $h($v('assets/style.css')) . '">' . "\n"
      . '</head>' . "\n"
      . '<body>' . "\n"
      . '<div class="rahmen rahmen-lesespalte">' . "\n"
      . '  <main class="inhalt">' . "\n"
      . '    <div class="text">' . "\n"
      . '      <p><img src="' . $h($v($logo)) . '" alt="" width="180" height="60"></p>' . "\n"
      . '      <h1>Wartung</h1>' . "\n"
      . '      <div class="meldung meldung-warn" role="status">' . "\n"
      . '        <p><strong>NAdoku wird gerade aktualisiert</strong> und ist in wenigen'
      . ' Minuten wieder da. Deine Uhr und dein Handy liefern ihre Daten danach'
      . ' von selbst nach.</p>' . "\n"
      . '      </div>' . "\n"
      . '      <p>Hast du gerade ein Formular abgeschickt: Geh im Browser'
      . ' <strong>zurück</strong> — die Eingaben stehen noch im Formular — und'
      . ' schick es später erneut ab.</p>' . "\n"
      . '    </div>' . "\n"
      . '  </main>' . "\n"
      . '</div>' . "\n"
      . '</body>' . "\n"
      . '</html>' . "\n";
}

/**
 * Der Balken fuer die Ausnahmeseiten: die FUENF Betriebsseiten
 * (`betrieb_status.php`, `betrieb_statistik.php`, `betrieb_updates.php`,
 * `betrieb_jobs.php`, `betrieb_server.php`) und `login.php`. NICHT
 * `update.php` — die ist im Web seit S8/AP3 nur noch eine Weiterleitung.
 *
 * Er ist die einzige Stelle, an der ein stehengebliebener Wartungsmodus
 * auffaellt — es gibt kein automatisches Ausschalten (E-S5W-05). Deshalb
 * steht er oben und nennt Zeitpunkt und Konto; „seit unbekannt", wenn die
 * Datei keinen auswertbaren Inhalt hat.
 *
 * Leerstring, wenn keine Wartung laeuft — der Aufrufer kann ihn bedingungslos
 * ausgeben.
 */
function wartung_balken(): string
{
    if (!wartung_aktiv()) { return ''; }
    $d = wartung_daten();
    $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    $seit = 'seit unbekannt';
    if ($d['seit'] !== null) {
        /* DIE ZEITRECHNUNG BLEIBT EINE. `fmt_local()` (db.php) rechnet UTC in
         * die Zeitzone der Anwendung um und ist die einzige Stelle, an der
         * das geschieht — zwei Umrechnungen waeren die sicherste Art, sich
         * eine Stunde Versatz einzuhandeln. Sie steht hier ueber
         * `function_exists()` und nicht ueber `require`, weil diese Datei
         * nichts laden darf (siehe Kopf): Der Balken laeuft nur auf
         * `update.php` und `login.php`, und dort ist `db.php` laengst
         * geladen. Faellt sie doch einmal aus, bleibt die Rohzeit stehen —
         * eine Stunde daneben ist besser als eine leere Zeile. */
        $utc = str_replace(['T', 'Z'], [' ', ''], $d['seit']);
        $seit = 'seit ' . (function_exists('fmt_local')
            ? fmt_local($utc, 'd.m.Y H:i')
            : $utc . ' UTC');
    }
    $von = $d['von'] !== null ? ' von ' . $h($d['von']) : '';

    return '<div class="meldung meldung-warn" role="status">'
         . '<p><strong>Wartungsmodus ' . $h($seit) . $von . '</strong> — alle anderen '
         . 'Anfragen bekommen 503. Geräte liefern nach.</p></div>';
}
