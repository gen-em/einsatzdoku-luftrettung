<?php
declare(strict_types=1);

require_once __DIR__ . '/instanz_lib.php';   // nur fuer INSTANZ_KURZ_VORGABE — die Datei laedt nichts

/**
 * WARTUNGSMODUS (S5 Paket W) — die Installation voruebergehend fuer alle
 * ausser der Administration schliessen.
 *
 * WOZU. Ein Update laeuft heute so: Push auf `main`, FTPS laedt `server/`
 * hoch, danach ruft eine AdministratorIn `update.php` und laesst die
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
 *                        Update vorausgehen soll. Seit S10/AP3 traegt sie
 *                        dazu die Karte „Schluessel des Servers".
 *   betrieb_schluesselblatt.php
 *                        DAS BLATT ZUR KARTE (S10/AP3). Es steht hier aus
 *                        demselben Grund, aus dem betrieb_updates.php hier
 *                        steht: Die Karte ist erreichbar, ihr Druckknopf
 *                        fuehrte sonst auf eine 503-Seite — derselbe Griff
 *                        ins Leere wie F-S8-P-04, nur eine Ebene tiefer.
 *                        Und die Lage, in der man das Blatt braucht, ist
 *                        genau eine Wartungslage: Eine Sicherung ist auf
 *                        einen neuen Server eingespielt, `config.php` ist
 *                        nicht mit dabei, der Anteil steht auf `abweichend`
 *                        — und der Wert, der nachzutragen ist, steht auf
 *                        dem Ausdruck, den diese Seite gemacht hat.
 *   wiederherstellen.php der Rueckweg, wenn die Migration schiefging.
 *   jobs.php             der Token-Weg. Das Komplett-Backup der Kette laeuft
 *                        WAEHREND der Wartung — genau dann ist es
 *                        konsistent, weil niemand sonst schreibt.
 *   login.php            damit eine abgemeldete AdministratorIn hineinkommt.
 *                        Was danach geschieht, entscheidet login.php selbst
 *                        (E-S5W-09): Admin weiter, alles andere sofort
 *                        wieder abgemeldet und auf die Wartungsseite.
 *   auth_salt.php        OHNE DIESE ZEILE IST login.php NUTZLOS (Nr. 171,
 *                        Web 19.1.2). Die Anmeldeseite kam waehrend der
 *                        Wartung, aber das Formular laesst sich nicht
 *                        abschicken: Der Browser holt zuerst das Salt und
 *                        die Rundenzahlen von hier, und ohne sie leitet er
 *                        kein Token ab. Der Endpunkt laedt `db.php` und
 *                        liegt nicht unter `/api/`, bekam also die
 *                        HTML-Wartungsseite — `login.php` las daraus
 *                        „Anmeldung derzeit nicht möglich" und blieb
 *                        stehen. Damit war der Rueckweg, den diese Liste
 *                        oeffnen soll, in Wahrheit zu; das Handbuch (12.3)
 *                        versprach ihn trotzdem. Das Risiko ist kein neues:
 *                        `login.php` liest dieselbe Tabelle und steht seit
 *                        jeher hier.
 *   logout.php           wer drin ist, muss auch wieder hinaus.
 *   install.php          hat mit `install.lock` seine eigene Sperre.
 *
 * NICHT in der Liste und trotzdem nie betroffen: alles unter `assets/` —
 * Stylesheet, Schriften, Symbole laufen gar nicht durch PHP.
 */
const WARTUNG_AUSNAHMEN = [
    'betrieb_status.php',
    /* SEIT P5a/AP8. Aus demselben Grund wie die Elternseite, nur schaerfer:
     * Auf der Sicherheitsseite steht der Knopf, mit dem sich eine Sperre
     * aufheben laesst. Wer im Wartungsmodus jemanden wieder hereinlassen
     * muss, braucht genau diese Seite — sie hinter der Sperre zu lassen
     * hiesse, sie dann zu schliessen, wenn man sie braucht. */
    'betrieb_sicherheit.php',
    'betrieb_statistik.php',
    'betrieb_updates.php',
    'betrieb_jobs.php',
    'betrieb_server.php',
    'betrieb_schluesselblatt.php',
    /* SEIT P5c/AP9 (E-P5c-134): Komplett-Backup und Backup-Ziele. Bis dahin
     * antworteten beide im Wartungsmodus mit 503 — und genau dann braucht man
     * sie: Schliesst der Torwaechter, weil eine Migration aussteht, sagt die
     * Seite Updates „vorher sichern" und bot einen Knopf zum Komplett-Backup
     * an, der in die Sperre fuehrte. Ebenso nannte die Vorbedingung der
     * FTP-Migration die Backup-Ziele als Weg, und die waren zu. Beide Seiten
     * erreicht nur die BetreiberIn (`require_betreiberin()`), und beide
     * zeigen den Balken. */
    'admin_komplettsicherung.php',
    'admin_sicherungsziele.php',
    'update.php',
    'wiederherstellen.php',
    'jobs.php',
    'login.php',
    'auth_salt.php',
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
    /* DIE AUSWAHL IST EINE WEISSE LISTE, und das bleibt so: Was hier
     * herauskommt, geht in eine Seite. Ein neuer Schluessel muss deshalb
     * ausdruecklich aufgenommen werden — `wer` kam mit P5a/AP5 dazu und
     * wurde beim ersten Versuch still verschluckt, weil nur die Datei ihn
     * trug und diese Liste nicht. */
    $leer = ['seit' => null, 'von' => null, 'wer' => null];
    if (!wartung_aktiv()) { return $leer; }
    $roh = @file_get_contents(WARTUNG_DATEI);
    if ($roh === false || $roh === '') { return $leer; }
    $d = json_decode($roh, true);
    if (!is_array($d)) { return $leer; }
    $str = static fn(string $k): ?string =>
        isset($d[$k]) && is_string($d[$k]) && $d[$k] !== '' ? $d[$k] : null;
    return ['seit' => $str('seit'), 'von' => $str('von'), 'wer' => $str('wer')];
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
    /* DER NAME WIRD MITGESCHRIEBEN, WEIL DIE SEITE IHN SPAETER NICHT MEHR
     * HOLEN KANN (P5a/AP5). Seit dem Instanznamen koennte die Wartungsseite
     * „Gen-EM NAdoku" zeigen, waehrend die Installation „BW-Doku" heisst —
     * ausgerechnet auf der Seite, die Fremde zu sehen bekommen. Ihn dort
     * nachzuschlagen geht aber nicht: Diese Seite ist ohne Datenbank gebaut,
     * und ein Verbindungsversuch waehrend eines Schemaumbaus laeuft im
     * schlechten Fall in die Zeitgrenze statt in eine Ausnahme.
     *
     * BEIM EINSCHALTEN steht die Datenbank dagegen noch — also wird der Name
     * hier festgehalten. Er bleibt ein ZUSATZ: Fehlt er oder ist die Datei
     * unlesbar, zeigt die Seite die Vorgabe. „Die Datei ist der Schalter,
     * nicht ihr Inhalt" gilt unveraendert. */
    $inhalt = json_encode([
        'seit' => gmdate('Y-m-d\TH:i:s\Z'),
        'von'  => $von,
        'wer'  => function_exists('instanz_kurz') ? instanz_kurz() : INSTANZ_KURZ_VORGABE,
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
/**
 * Die Skripte ausserhalb von `/api/`, die JSON antworten.
 *
 * VIER, NICHT ZWEI — seit P5a/AP9. Fuer den WARTUNGSMODUS genuegten zwei:
 * `auth_salt.php` und `jobs.php` stehen in `WARTUNG_AUSNAHMEN`, das Tor
 * kehrt bei ihnen vorher um, und die Frage stellte sich nie.
 *
 * DIE UEBERLAST KENNT KEINE AUSNAHMEN. Wenn die Datenbank keine Verbindung
 * mehr annimmt, ist jede Seite betroffen — auch die, die im Wartungsmodus
 * absichtlich offen bleiben. Mit der alten Liste haette `auth_salt.php` eine
 * HTML-Seite an ein `fetch()` geliefert, das JSON erwartet, und die
 * Anmeldeseite haette daraus „Anmeldung derzeit nicht möglich" gemacht statt
 * „ausgelastet". Das ist genau der Fehler aus Backlog Nr. 171, einen Stock
 * tiefer.
 *
 * NICHT DABEI: `gpx.php`. Es meldet Fehler zwar als JSON, wird aber vom
 * Browser ANGESTEUERT (ein Download-Verweis, kein `fetch()`); wer dort
 * landet, soll eine lesbare Seite sehen und kein `{"error": ...}`.
 *
 * Die Liste aendert am Wartungsmodus nichts: Beide neuen Eintraege stehen
 * ohnehin in `WARTUNG_AUSNAHMEN`.
 */
const JSON_SKRIPTE_AUSSERHALB_API = ['ingest.php', 'pair.php',
                                     'auth_salt.php', 'jobs.php'];

function wartung_json_gefragt(): bool
{
    $pfad = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (str_contains($pfad, '/api/')) { return true; }
    $skript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    return in_array($skript, JSON_SKRIPTE_AUSSERHALB_API, true);
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
    /* DIE SICHERHEITSKOPFZEILEN GELTEN AUCH HIER (P5a/AP4, E-P5a-15).
     *
     * Diese Datei laedt nichts (Eigenschaft 2 im Kopf) — `kopfzeilen_lib.php`
     * ist die eine Ausnahme, und sie ist es, weil jene Datei ihrerseits ohne
     * Datenbank auskommt: Jede Einstellung hat eine Vorgabe, und faellt die
     * Abfrage aus, gilt die. Genau dafuer ist sie so gebaut.
     *
     * OHNE NONCE: Die Wartungsseite traegt KEIN Skript, und das ist Absicht
     * (siehe `wartung_antwort_seite()`). Ein Nonce ohne Block waere eine
     * Erlaubnis ohne Empfaenger. */
    if (function_exists('kopfzeilen_seite')) { kopfzeilen_seite(false); }

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
function wartung_antwort_seite(bool $rueckweg = true): never
{
    wartung_kopfzeilen();
    header('Content-Type: text/html; charset=utf-8');
    echo wartung_seite_html($rueckweg);
    exit;
}

/**
 * DAS GERUEST BEIDER STOERUNGSSEITEN (P5a/AP9).
 *
 * Es gibt seit AP9 ZWEI Seiten, die ohne Datenbank antworten muessen: die
 * Wartungsseite (503, die Installation ist absichtlich zu) und die
 * Ausgelastet-Seite (503, die Datenbank nimmt gerade keine Verbindung mehr
 * an, E-P5a-18). Sie unterscheiden sich in den WORTEN, nicht im Aufbau —
 * Rahmen, Lesespalte, Logo, Stylesheet und der Verzicht auf jedes Skript
 * sind bei beiden dieselbe Ueberlegung.
 *
 * Deshalb steht der Aufbau hier EINMAL. Die zweite Fassung waere nicht
 * falsch gewesen, sie waere nur beim naechsten Mal auseinandergelaufen: Wer
 * das Logo austauscht oder das Stylesheet anders verlinkt, tut es sonst an
 * einer von zwei Stellen und merkt es nicht, weil beide Seiten selten zu
 * sehen sind.
 *
 * $innen ist FERTIGES Markup — der Aufrufer maskiert selbst. Das Geruest
 * kann nicht wissen, welcher Teil seiner Woerter ein Verweis sein soll.
 *
 * KEIN NEUER BAUSTEIN (Design.md 9): `.rahmen`, `.rahmen-lesespalte`,
 * `.inhalt`, `.text` sind dieselben wie bisher, das Markup ist woertlich
 * das der Wartungsseite. Diese Funktion verschiebt es, sie erfindet nichts.
 */
function stoerung_seite_html(string $titel, string $innen): string
{
    /* DAS LOGO WIRFT EINE MUENZE — siehe wartung_seite_html(). */
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

    /* NUR DER VORSATZ „[Staging] ", keine Farbe und kein Streifen (P5c/AP1,
     * E-P5c-55): Diese Seiten haben keine Kopfleiste, die rot werden
     * koennte, und sie sollen so wenig wie moeglich voraussetzen. Das
     * Etikett kommt aus `config.php` allein — `umgebung_lib.php` laedt nur
     * `konfig_lib.php`, keine Datenbank. */
    require_once __DIR__ . '/umgebung_lib.php';

    return '<!doctype html>' . "\n"
      . '<html lang="de">' . "\n"
      . '<head>' . "\n"
      . '<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' . "\n"
      . '<title>' . $h(umgebung_praefix() . $titel) . '</title>' . "\n"
      . '<link rel="stylesheet" href="' . $h($v('assets/style.css')) . '">' . "\n"
      . '</head>' . "\n"
      . '<body>' . "\n"
      . '<div class="rahmen rahmen-lesespalte">' . "\n"
      . '  <main class="inhalt">' . "\n"
      . '    <div class="text">' . "\n"
      . '      <p><img src="' . $h($v($logo)) . '" alt="" width="180" height="60"></p>' . "\n"
      . $innen
      . '    </div>' . "\n"
      . '  </main>' . "\n"
      . '</div>' . "\n"
      . '</body>' . "\n"
      . '</html>' . "\n";
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
function wartung_seite_html(bool $rueckweg = true): string
{
    /* DER GRUND STEHT AUF DER SEITE, WENN ES EINEN GIBT (P5a/AP3, E-P5a-20).
     *
     * `von` traegt seit dem Torwaechter nicht nur einen Namen, sondern auch
     * eine Herkunft: `torwaechter` (die Anwendung hat selbst geschlossen) und
     * `kette` (der Auslieferungslauf, P5a/AP1). Beide sind etwas anderes als
     * „jemand hat den Schalter umgelegt", und wer davorsteht, soll es
     * erfahren — sonst sieht eine automatisch geschlossene Installation aus
     * wie eine vergessene.
     *
     * OHNE DATENBANK, wie alles hier: `wartung_daten()` liest die Datei. */
    $daten = wartung_daten();
    $von = (string)($daten['von'] ?? '');
    $grund = match ($von) {
        'torwaechter' => 'Es ist eine neue Fassung eingespielt worden, und die '
                       . 'Datenbank ist noch nicht nachgezogen. Die Anwendung hat '
                       . 'deshalb selbst geschlossen — das ist kein Fehler, sondern '
                       . 'die Vorsorge dagegen, dass jemand in eine halb umgebaute '
                       . 'Datenbank schreibt. Die BetreiberIn ist informiert.',
        'kette'       => 'Eine Auslieferung laeuft gerade. Sie schaltet die Wartung '
                       . 'hinterher von selbst wieder aus.',
        default       => '',
    };

    $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

    /* Der Name aus dem Schalter — siehe wartung_einschalten(). Fehlt er
     * (Schalter aus einer aelteren Fassung, unlesbarer Inhalt), gilt die
     * Vorgabe. */
    $wer = trim((string)($daten['wer'] ?? ''));
    if ($wer === '') { $wer = INSTANZ_KURZ_VORGABE; }

    return stoerung_seite_html('Wartung — ' . $wer,
        '      <h1>Wartung</h1>' . "\n"
      . '      <div class="meldung meldung-warn" role="status">' . "\n"
      . '        <p><strong>NAdoku wird gerade aktualisiert</strong> und ist in wenigen'
      . ' Minuten wieder da. Deine Uhr und dein Handy liefern ihre Daten danach'
      . ' von selbst nach.</p>' . "\n"
      . '      </div>' . "\n"
      . ($grund !== '' ? '      <p>' . $h($grund) . '</p>' . "\n" : '')
      . '      <p>Hast du gerade ein Formular abgeschickt: Geh im Browser'
      . ' <strong>zurück</strong> — die Eingaben stehen noch im Formular — und'
      . ' schick es später erneut ab.</p>' . "\n"
      /* DER RUECKWEG IN DIE VERWALTUNG (Backlog Nr. 126).
       *
       * Bis hierher endete die Seite im Nichts: Wer sich waehrend der
       * Wartung anmeldete, landete auf der Startseite, und die zeigt
       * diese Seite — ohne einen einzigen Verweis. Der einzige Weg war,
       * `betrieb_updates.php` von Hand in die Adresszeile zu tippen.
       *
       * FAELLIG GEWORDEN IST DAS ERST MIT NR. 171. Bis Web 19.1.2 liess
       * sich das Anmeldeformular gar nicht abschicken (auth_salt.php
       * stand nicht in der Ausnahmeliste) — wer nicht hereinkam, stand
       * auch nicht vor der Sackgasse. Dies ist die zweite Haelfte
       * derselben Reparatur.
       *
       * REINES MARKUP, KEIN ui_knopf(). Diese Datei laedt nichts —
       * kein db.php, kein ui.php (Eigenschaft 2 im Dateikopf). Das
       * Stylesheet ist verlinkt, also tragen `.knopf` und
       * `.knopf-neutral` hier genauso wie ueberall (Design.md 9.4).
       *
       * $rueckweg = false an der EINEN Stelle, an der wir sicher
       * wissen, dass er ins Leere zeigt: login.php verwirft die
       * Sitzung eines Kontos OHNE Verwaltungsrecht und zeigt diese
       * Seite. Sonst steht der Verweis fuer jeden da — das ist
       * verkraftbar, weil hinter der Adresse `require_betreiberin()`
       * steht und sie ohnehin im Handbuch 12.3 genannt wird. */
      . ($rueckweg
          ? '      <p><a class="knopf knopf-neutral" href="betrieb_updates.php">'
            . '<span>Zur Verwaltung</span></a></p>' . "\n"
          : ''));
}

/**
 * Der Balken fuer die Ausnahmeseiten: die SECHS Betriebsseiten
 * (`betrieb_status.php`, `betrieb_sicherheit.php`, `betrieb_statistik.php`,
 * `betrieb_updates.php`, `betrieb_jobs.php`, `betrieb_server.php`) und
 * `login.php`. NICHT
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
         * nichts laden darf (siehe Kopf): Der Balken laeuft auf den
         * Ausnahmeseiten mit Geruest und auf `login.php`, und dort ist
         * `db.php` laengst geladen. Faellt sie doch einmal aus, bleibt die Rohzeit stehen —
         * eine Stunde daneben ist besser als eine leere Zeile. */
        $utc = str_replace(['T', 'Z'], [' ', ''], $d['seit']);
        $seit = 'seit ' . (function_exists('fmt_local')
            ? fmt_local($utc, 'd.m.Y, H:i')   // derselbe Trenner wie datum_zeit_text() (E-P5c-37)
            : $utc . ' UTC');
    }
    $von = $d['von'] !== null ? ' von ' . $h($d['von']) : '';

    /* ZWEI URHEBER SIND KEINE NAMEN (P5a). `torwaechter` und `kette` stehen
     * im selben Feld wie „Philipp Chadid" — und „von torwaechter" laese sich
     * wie eine Person. Der Balken sagt deshalb, was gemeint ist. */
    $zusatz = match ((string)($d['von'] ?? '')) {
        'torwaechter' => ' Grund: eine ausstehende Migration. Betrieb → Updates '
                       . 'ausführen, danach hier beenden.',
        'kette'       => ' Grund: eine laufende Auslieferung.',
        default       => '',
    };
    if ($zusatz !== '') { $von = ' — automatisch geschaltet'; }

    return '<div class="meldung meldung-warn" role="status">'
         . '<p><strong>Wartungsmodus ' . $h($seit) . $von . '</strong> — alle anderen '
         . 'Anfragen bekommen 503. Geräte liefern nach.' . $h($zusatz) . '</p></div>';
}

/* ==========================================================================
 * DIE ZWEITE STOERUNG: AUSGELASTET            P5a/AP9, E-P5a-18, E-P5a-50/51
 * ==========================================================================
 *
 * WOGEGEN. MySQL/MariaDB kennt Grenzen fuer gleichzeitige Verbindungen:
 * `max_connections` (der ganze Server, Fehler **1040**) und
 * `max_user_connections` (dieses eine Datenbankkonto) — letztere in zwei
 * Ausfuehrungen mit zwei Fehlernummern, **1203** und **1226**; welche wann,
 * steht bei `UEBERLAST_CODES`. Auf einem geteilten Webspace ist die
 * Kontogrenze die naheliegende: Sie liegt regelmaessig bei 10 bis 30, und
 * sie wird erreicht, wenn eine Handvoll Uhren gleichzeitig nachliefert,
 * waehrend jemand eine Sicherung zieht.
 *
 * Bis AP9 kam in diesem Fall eine **500** — mit dem ungefilterten Text der
 * PDO-Ausnahme, in dem der Hostname und der Benutzername der Datenbank
 * stehen. Zwei Fehler in einem: Die Anfrage ist nicht kaputt, sie ist
 * verfrueht; und was auf dem Bildschirm stand, ging niemanden etwas an.
 *
 * JETZT: **503** mit `Retry-After: 5`. Fuer die Geraete ist das dieselbe
 * Zusage wie der Wartungsmodus (JSON-Vertrag 5: 5xx heisst „spaeter
 * unveraendert erneut"), und sie halten sich schon heute daran — **kein
 * Client wird dafuer geaendert**.
 *
 * WARUM 5 SEKUNDEN UND TROTZDEM „in einer Minute". Die Kopfzeile richtet
 * sich an Maschinen, und eine Ueberlast dauert Sekunden, nicht Minuten. Der
 * Satz auf der Seite richtet sich an einen Menschen, der gerade ein Formular
 * abgeschickt hat; „in fuenf Sekunden" laese sich dort wie ein Versprechen,
 * das niemand einloesen kann. Beide Zahlen sind richtig, sie beantworten
 * verschiedene Fragen.
 *
 * ---------------------------------------------------------------------------
 * WARUM DER ZAEHLER IN EINER DATEI STEHT UND NICHT IN `app_state` (E-P5a-50)
 * ---------------------------------------------------------------------------
 * Das Konzept sagt „Zaehler `db_ueberlast` in `app_state`". Das geht nicht,
 * und zwar aus dem Grund, der den Zaehler ueberhaupt erst interessant macht:
 * **In dem Augenblick, in dem gezaehlt werden muesste, gibt es keine
 * Verbindung zur Datenbank.** `app_state_setzen()` braucht genau eine.
 *
 * Es ist derselbe Satz, der weiter oben ueber `wartung.lock` steht: Ein
 * Schalter, der die Datenbank fragt, ob er schalten darf, ist im
 * entscheidenden Moment stumm. Ein Zaehler, der die Datenbank braucht, um
 * eine fehlende Datenbank zu zaehlen, zaehlt nie.
 *
 * Erwogen und verworfen: den Vorfall in eine Datei schreiben und ihn beim
 * naechsten gelungenen Verbindungsaufbau nach `app_state` **nachtragen**.
 * Das haette den Buchstaben des Konzepts erfuellt und zwei Speicher fuer
 * eine Zahl gebraucht — genau die Bauform, die `app_state_lesen()` in
 * `db.php` gerade abgeloest hat („Fuenf Fassungen derselben zwei Zeilen").
 * Eine Zahl, ein Ort.
 *
 * WAS DAS KOSTET: Die Datei liegt nur auf dem Server (`.gitignore` UND
 * Ausnahmeliste beider FTPS-Schritte, wie `wartung.lock`), sie ueberlebt
 * keinen Serverumzug, und sie steht nicht in der Sicherung. Fuer eine
 * Betriebszahl, die sagt „heute war es dreimal eng", ist das der richtige
 * Preis — sie ist ein Hinweis auf eine Einstellung des Hosters, kein
 * Bestandsdatum.
 *
 * WENN SIE SICH NICHT SCHREIBEN LAESST, sagt die Statusseite das (Feld
 * `schreibbar`). „0 Vorfaelle" und „nicht gezaehlt" sehen sonst gleich aus,
 * und das waere die gruene Zahl, die nichts gemessen hat.
 */

/** Der Zaehler. Liegt neben `wartung.lock` und ist wie diese nur auf dem
 *  Server — `.gitignore` UND Ausnahmeliste des Deploys. */
const UEBERLAST_DATEI = __DIR__ . '/ueberlast.json';

/** Hinweis an Browser und Werkzeuge, in Sekunden (E-P5a-18). */
const UEBERLAST_RETRY_S = 5;

/** Ab wie vielen Vorfaellen je Stunde die Statuszeile orange wird
 *  (E-P5a-18). Darunter ist es ein Ereignis, darueber eine Einstellung,
 *  die nicht passt. */
const UEBERLAST_ORANGE = 10;

/**
 * Die DREI Fehlernummern, auf die es ankommt — E-P5a-18 nennt zwei (E-P5a-51).
 *
 * 1040 = `Too many connections` — der ganze Datenbankserver ist voll.
 * 1203 = `User %s already has more than 'max_user_connections' active
 *         connections` — die SYSTEMVARIABLE `max_user_connections` ist
 *         ausgeschoepft. Sie gilt fuer alle Konten gleich.
 * 1226 = `User '%s' has exceeded the '%s' resource (current value: %d)` —
 *         die GRANT-Grenze dieses einen Kontos
 *         (`ALTER USER ... WITH MAX_USER_CONNECTIONS n`).
 *
 * WARUM 1226 DAZUGEHOERT, OBWOHL DAS KONZEPT SIE NICHT NENNT. 1203 und 1226
 * sehen wie dieselbe Grenze aus und sind zwei verschiedene: Die eine steht in
 * der Serverkonfiguration, die andere am Datenbankkonto. **Ein geteilter
 * Webspace setzt die zweite** — der Hoster gibt jedem Kunden seine Zahl, und
 * dafuer ist die GRANT-Grenze da. Gemessen am 16.09.2026 gegen MariaDB 10.11:
 * `ALTER USER 'nadoku'@'127.0.0.1' WITH MAX_USER_CONNECTIONS 3` und eine
 * vierte Verbindung ergeben **1226**, nicht 1203. Ohne diese Zeile haette
 * AP9 also genau den Fall nicht abgedeckt, fuer den es gebaut ist — und zwar
 * still, mit einer gruenen Probe daneben, weil die Probe dieselbe Annahme
 * geteilt haette.
 *
 * ALLES ANDERE IST WEITER EIN FEHLER und bleibt einer: ein falsches
 * Passwort, ein fehlender Socket, eine geloeschte Datenbank. Wer die Liste
 * erweitert, verwandelt einen Defekt in ein „gleich wieder da" — und dann
 * wartet jemand auf etwas, das von selbst nicht wiederkommt.
 */
const UEBERLAST_CODES = [1040, 1203, 1226];

/** Was 1226 noch bedeuten kann — und was das fuer die Wartezeit heisst. */
const UEBERLAST_RETRY_STUNDE_S = 300;

/**
 * Wie lange das Gegenueber warten soll.
 *
 * 1226 traegt nicht nur die Verbindungsgrenze. Dieselbe Nummer kommt, wenn
 * ein Konto seine STUNDENGRENZEN reisst — `MAX_QUERIES_PER_HOUR`,
 * `MAX_UPDATES_PER_HOUR`, `MAX_CONNECTIONS_PER_HOUR`. Das ist ebenfalls
 * „ausgelastet", aber es loest sich nicht in fuenf Sekunden: Diese Zaehler
 * laufen bis zur vollen Stunde weiter.
 *
 * Die Unterscheidung steht in der MELDUNG, die den Namen der Ressource
 * nennt. Fuenf Sekunden fuer die Verbindungsgrenze, fuenf Minuten fuer die
 * Stundengrenzen — dieselbe Zahl wie beim Wartungsmodus.
 *
 * Fuer die GERAETE aendert das nichts: Der JSON-Vertrag sagt zu 5xx Backoff
 * und unveraendert erneut, und `Retry-After` ist dort ausdruecklich ein
 * Hinweis, kein Auftrag. Die Zahl richtet sich an Browser und Werkzeuge.
 */
function ueberlast_retry_s(Throwable $ex): int
{
    return preg_match("/exceeded the '(max_questions|max_updates|max_connections)' resource/",
                      $ex->getMessage()) === 1
        ? UEBERLAST_RETRY_STUNDE_S
        : UEBERLAST_RETRY_S;
}

/**
 * Ist diese Ausnahme eine der beiden Verbindungsgrenzen?
 *
 * DREI WEGE, WEIL PDO SICH NICHT FESTLEGT. Bei einem Fehler waehrend einer
 * ABFRAGE traegt `PDOException::getCode()` den SQLSTATE als Zeichenkette
 * (`'HY000'`), und `errorInfo[1]` die Treibernummer. Beim Fehler waehrend
 * des VERBINDENS — also hier — ist `errorInfo` je nach PHP-Fassung gar nicht
 * gesetzt, und `getCode()` traegt mal die Treibernummer als Zahl, mal den
 * SQLSTATE. Verlaesslich ist nur die MELDUNG: `SQLSTATE[HY000] [1040] Too
 * many connections`.
 *
 * Deshalb werden alle drei gefragt, und die Meldung zuletzt. Ein Vergleich,
 * der nur auf `getCode()` sieht, arbeitet auf der einen Maschine und auf der
 * naechsten nicht mehr — und zwar still: Die Seite zeigte dann wieder 500.
 */
function ueberlast_erkannt(Throwable $ex): bool
{
    if ($ex instanceof PDOException && is_array($ex->errorInfo)
        && isset($ex->errorInfo[1])
        && in_array((int)$ex->errorInfo[1], UEBERLAST_CODES, true)) {
        return true;
    }
    if (in_array((int)$ex->getCode(), UEBERLAST_CODES, true)) { return true; }
    return preg_match('/\[(1040|1203)\]/', $ex->getMessage()) === 1;
}

/**
 * Einen Vorfall zaehlen — ohne Datenbank, ohne Ausnahme, ohne Rueckfrage.
 *
 * Die Datei ist klein und bleibt es: Sie traegt die laufende Stunde, die
 * Zahl darin, die groesste je gemessene Stunde und die Gesamtzahl. **Kein
 * Protokoll** — wer wann abgewiesen wurde, ist hier nicht die Frage, und
 * eine wachsende Datei waere das naechste Betriebsproblem.
 *
 * `flock`, weil eine Ueberlast per Definition viele Prozesse gleichzeitig
 * trifft. Ohne die Sperre zaehlten zehn Prozesse dieselbe Sekunde auf
 * denselben Ausgangswert und schrieben eine 1 statt einer 10 — der Zaehler
 * unterschaetzte genau dann am staerksten, wenn er gebraucht wird.
 *
 * Die Stunde ist eine UTC-Stunde (`gmdate`), wie alles Gespeicherte in
 * dieser Anwendung. Die Anzeige rechnet um.
 */
function ueberlast_vermerken(): void
{
    $stunde = gmdate('Y-m-d H');
    $f = @fopen(UEBERLAST_DATEI, 'c+');
    if ($f === false) {
        /* Nicht still. Wenn die Datei nicht schreibbar ist, ist der Vorfall
         * trotzdem passiert — er steht dann wenigstens im Fehlerprotokoll. */
        require_once __DIR__ . '/systemmeldung_lib.php';
        system_rueckfall('ueberlast', UEBERLAST_DATEI . ' lässt sich nicht '
                       . 'schreiben; der Vorfall ist nur hier vermerkt.');
        return;
    }
    try {
        if (!flock($f, LOCK_EX)) { return; }
        $roh = stream_get_contents($f);
        $d   = is_string($roh) && $roh !== '' ? json_decode($roh, true) : null;
        if (!is_array($d)) { $d = []; }

        $st = isset($d['st']) && is_string($d['st']) ? $d['st'] : '';
        $n  = ($st === $stunde ? (int)($d['n'] ?? 0) : 0) + 1;

        $sp   = (int)($d['sp'] ?? 0);
        $spst = isset($d['spst']) && is_string($d['spst']) ? $d['spst'] : '';
        if ($n > $sp) { $sp = $n; $spst = $stunde; }

        $neu = ['st'    => $stunde,
                'n'     => $n,
                'sp'    => $sp,
                'spst'  => $spst,
                'ges'   => (int)($d['ges'] ?? 0) + 1,
                'letzt' => gmdate('Y-m-d H:i:s')];

        ftruncate($f, 0);
        rewind($f);
        fwrite($f, (string)json_encode($neu));
        fflush($f);
    } finally {
        flock($f, LOCK_UN);
        fclose($f);
    }
}

/**
 * Was der Zaehler sagt — fuer die Statusseite.
 *
 * `stunde` ist die zuletzt gezaehlte Stunde, `n` die Zahl darin. Liegt sie
 * in der Vergangenheit, ist `n` KEINE Aussage ueber jetzt; die Seite sagt
 * deshalb das Alter dazu und nicht nur die Zahl.
 *
 * `schreibbar` ist das Gegenstueck zur Null: Ohne dieses Feld liest sich
 * eine Installation, in der die Datei nicht angelegt werden kann, wie eine
 * ohne Vorfaelle.
 */
function ueberlast_stand(): array
{
    clearstatcache(true, UEBERLAST_DATEI);
    $da = file_exists(UEBERLAST_DATEI);
    $leer = ['stunde' => null, 'n' => 0, 'spitze' => 0, 'spitze_stunde' => null,
             'gesamt' => 0, 'letzt' => null,
             'schreibbar' => $da ? is_writable(UEBERLAST_DATEI)
                                 : is_writable(__DIR__)];
    if (!$da) { return $leer; }
    $roh = @file_get_contents(UEBERLAST_DATEI);
    if ($roh === false || $roh === '') { return $leer; }
    $d = json_decode($roh, true);
    if (!is_array($d)) { return $leer; }
    $str = static fn(string $k): ?string =>
        isset($d[$k]) && is_string($d[$k]) && $d[$k] !== '' ? $d[$k] : null;
    return ['stunde'        => $str('st'),
            'n'             => (int)($d['n'] ?? 0),
            'spitze'        => (int)($d['sp'] ?? 0),
            'spitze_stunde' => $str('spst'),
            'gesamt'        => (int)($d['ges'] ?? 0),
            'letzt'         => $str('letzt'),
            'schreibbar'    => $leer['schreibbar']];
}

/**
 * Die Antwort: 503, JSON oder Seite — und Schluss.
 *
 * Sie benutzt `wartung_json_gefragt()`, weil die Frage hier dieselbe ist und
 * die Antwort dieselbe sein MUSS: `ingest.php` und `pair.php` liegen nicht
 * unter `/api/`, und eine HTML-Seite an ihrer Stelle liesse den
 * Nachlieferungsweg der Uhr ins Leere laufen. Zwei Fassungen dieser
 * Unterscheidung waeren zwei Gelegenheiten, genau das zu vergessen.
 *
 * OHNE `kopfzeilen_seite()` — und das ist der Unterschied zur Wartung.
 * `kopfzeilen_lib.php` liest zwei Einstellungen aus `app_state`, also aus
 * der Datenbank. Im Wartungsfall ist die Datenbank in Ordnung und der
 * Aufruf kostet zwei Abfragen; hier ist sie es gerade nicht, und der Aufruf
 * liefe ueber `db()` zurueck in genau diese Funktion. Der Rueckfall in
 * `db()` faengt die Schleife ab (dort steht, wie) — aber eine Kopfzeile, die
 * eine Schleife braucht, um nicht zu entstehen, ist die falsche Kopfzeile.
 * Die drei Zeilen unten sind das, was diese Seite braucht: Sie traegt kein
 * Skript, keinen Verweis nach aussen und kein Formular.
 */
function ueberlast_antwort(int $retry = UEBERLAST_RETRY_S): never
{
    http_response_code(503);
    header('Retry-After: ' . max(1, $retry));
    /* Kein Zwischenspeichern: Eine 503 ist ein Zustand von Sekunden. Was ein
     * Zwischenspeicher davon behielte, ueberlebte die Ueberlast. */
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');

    if (wartung_json_gefragt()) {
        header('Content-Type: application/json');
        echo json_encode([
            'error'   => 'ausgelastet',
            'meldung' => 'Der Server ist gerade ausgelastet. Bitte später erneut.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo ueberlast_seite_html();
    exit;
}

/**
 * Das Markup der Ausgelastet-Seite — getrennt von der Ausgabe, damit eine
 * Probe es lesen kann, ohne einen Prozess zu beenden (wie bei der Wartung).
 *
 * KEIN KNOPF „Zur Verwaltung". Die Wartungsseite bietet ihn an, weil
 * `betrieb_updates.php` waehrend der Wartung ausdruecklich antwortet. Hier
 * antwortet sie nicht: Die Verbindungsgrenze trifft jede Seite gleich, und
 * ein Verweis, der ins selbe 503 fuehrt, ist ein Griff ins Leere — derselbe
 * Fehler wie F-S8-P-04, nur andersherum.
 *
 * DER NAME DER INSTALLATION kommt aus der Vorgabe und nicht aus
 * `instanz_kurz()`: Jenes liest `app_state`, also die Datenbank, die gerade
 * nicht antwortet. Der Aufruf kaeme mit derselben Vorgabe zurueck — nur
 * ueber einen Umweg, der scheitern muss.
 */
function ueberlast_seite_html(): string
{
    return stoerung_seite_html('Ausgelastet — ' . INSTANZ_KURZ_VORGABE,
        '      <h1>Ausgelastet</h1>' . "\n"
      . '      <div class="meldung meldung-warn" role="status">' . "\n"
      . '        <p><strong>Der Server ist gerade ausgelastet</strong> — bitte in'
      . ' einer Minute noch einmal. Deine Uhr und dein Handy liefern ihre Daten'
      . ' von selbst nach.</p>' . "\n"
      . '      </div>' . "\n"
      . '      <p>Hast du gerade ein Formular abgeschickt: Geh im Browser'
      . ' <strong>zurück</strong> — die Eingaben stehen noch im Formular — und'
      . ' schick es gleich noch einmal ab.</p>' . "\n");
}

/* --------------------------------------------------------------------------
 * GEDRAENGEL IST AUCH KEIN DEFEKT          P5a/AP9, E-P5a-52 (Fund der Probe)
 * --------------------------------------------------------------------------
 *
 * GEFUNDEN AM 16.09.2026 von `tools/proben/verbindung/`, und zwar nebenbei:
 * Zwanzig Uploads desselben Geraets auf denselben Diensttag, gleichzeitig
 * abgeschickt, ergaben **zwoelfmal HTTP 500** — Ursache
 * `SQLSTATE[40001] 1213 Deadlock found when trying to get lock; try
 * restarting transaction`, an zwei Stellen: dem `UPDATE days` in
 * `dt_zeitraum_fortschreiben()` und dem `INSERT ... ON DUPLICATE KEY` auf
 * `missions` in `ingest.php`. Alle Uploads eines Diensttags fassen dieselbe
 * `days`-Zeile an; InnoDB bricht dann eine der beteiligten Transaktionen ab,
 * um den Kreis zu loesen.
 *
 * DAS IST DERSELBE FEHLER WIE 1040/1203, nur eine Ebene hoeher: Die Anfrage
 * ist nicht kaputt, sie ist zu frueh. Die Fehlermeldung von InnoDB sagt es
 * woertlich — „try restarting transaction". Eine 500 dagegen heisst
 * „Defekt", sie erzeugt eine Fehlerkennung fuer etwas, das niemand
 * nachschlagen wird, und sie steht am Ende in jeder Fehlerstatistik.
 *
 * WAS HIER GETAN WIRD UND WAS NICHT. Hier wird die ANTWORT berichtigt: 503
 * `ausgelastet` statt 500, also dieselbe Zusage wie an der
 * Verbindungsgrenze. Fuer die Uhr aendert sich dadurch nichts an der
 * Datensicherheit — 5xx heisst im JSON-Vertrag ohnehin „spaeter unveraendert
 * erneut", und genau das tut sie seit S4. Es aendert sich, was die Meldung
 * BEHAUPTET.
 *
 * NICHT getan wird die eigentliche Abhilfe: die Transaktion selbst zu
 * wiederholen, statt sie dem Aufrufer zurueckzugeben. Das ist ein Eingriff
 * in den Ablauf von `ingest.php` und gehoert in ein eigenes Paket —
 * **Backlog Nr. 210**. Bis dahin kostet ein Gedraengel einen zweiten Anlauf
 * des Geraets, und der kommt von selbst.
 *
 * DIESE VORFAELLE ZAEHLT `ueberlast.json` NICHT. Der Zaehler beantwortet die
 * Frage „steht `max_user_connections` zu eng?", und ein Gedraengel um eine
 * Tabellenzeile beantwortet sie nicht. Zwei Ursachen in einer Zahl waeren
 * eine Zahl, aus der sich keine der beiden mehr ablesen liesse. Das
 * Gedraengel steht im Fehlerprotokoll, mit Datei und Zeile.
 */

/** 1205 = Lock wait timeout exceeded, 1213 = Deadlock found.
 *  Beide sind Aufforderungen, es noch einmal zu versuchen. */
const GEDRAENGEL_CODES = [1205, 1213];

/** Ist diese Ausnahme ein Gedraengel um eine Sperre? Dieselben drei Wege wie
 *  bei `ueberlast_erkannt()`, aus demselben Grund. */
function gedraengel_erkannt(Throwable $ex): bool
{
    if ($ex instanceof PDOException && is_array($ex->errorInfo)
        && isset($ex->errorInfo[1])
        && in_array((int)$ex->errorInfo[1], GEDRAENGEL_CODES, true)) {
        return true;
    }
    if (in_array((int)$ex->getCode(), GEDRAENGEL_CODES, true)) { return true; }
    return preg_match('/\[(1205|1213)\]/', $ex->getMessage()) === 1;
}

/**
 * Ins Fehlerprotokoll des Webspace — und NICHT in den Reiter System.
 *
 * Ein Gedraengel ist nichts, wonach jemand fragt — es ist behoben, bevor die
 * Meldung gelesen wird. Was zaehlt, ist die STELLE: Haeuft sich dieselbe
 * Datei und Zeile, ist dort ein Engpass, und den findet man durch Zaehlen
 * gleicher Zeilen, nicht durch Nachschlagen von Kennungen.
 *
 * RUECKFALL UND NICHT `system_melden()` (P5c/AP3, E-P5c-58): Die Anfrage
 * haengt gerade an einer Sperre; ein weiterer Schreibzugriff in derselben
 * Lage ist das Letzte, was sie braucht. Die Kennung, die
 * `system_rueckfall()` seither jeder Zeile gibt, schadet nicht — nach ihr
 * fragt nur niemand.
 */
function gedraengel_vermerken(Throwable $ex, string $bereich): void
{
    require_once __DIR__ . '/systemmeldung_lib.php';
    system_rueckfall('gedraengel', $bereich, $ex);
}
