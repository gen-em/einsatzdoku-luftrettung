<?php
declare(strict_types=1);

require_once __DIR__ . '/version.php';
// E-Mail-Normalisierung (M1-13). Eigene Datei ohne Abhaengigkeiten, weil
// install.php sie ebenfalls braucht und dort noch keine config.php existiert.
require_once __DIR__ . '/email_lib.php';
/* Der eine Leser fuer config.php (Schritt 15 AP2, E-ZE-02/-14). Laedt
 * seinerseits NICHTS — Bedingung, nicht Sparsamkeit: `install.php` und
 * `sitzung_lib.php` brauchen ihn, ohne `db.php` zu laden.
 *
 * BIS WEB 20.26.3 STAND HIER `$CFG = require __DIR__ . '/config.php';` und
 * legte die Konfiguration als GLOBALE ab. Elf Dateien griffen mit
 * `global $CFG` darauf zu, fuenf weitere lasen die Datei ein zweites Mal —
 * zusammen 46 Zugriffe und 7 Lesestellen. Jetzt: `konfig('app.timezone')`.
 *
 * UND ES IST KEIN `require` MEHR, das scheitern kann: Fehlt `config.php`
 * (die Anlage ist noch nicht eingerichtet), liefert jede Abfrage ihre
 * Vorgabe, statt dass die Datei mit einem Fatal abbricht. */
require_once __DIR__ . '/konfig_lib.php';
require_once __DIR__ . '/format_lib.php';   // fmt_local() u. a. (Schritt 15/AP7)
/* Der Transaktionsrahmen `db_transaktion()` (Schritt 15/AP5, E-ZE-20). Stand
 * bis Web 20.37.2 hier; eigene Datei, weil der Einrichter ihn ohne
 * `config.php` braucht und diese Datei ohne sie abbricht (Nr. 288). */
require_once __DIR__ . '/transaktion_lib.php';

/* UND `db.php` VERLANGT `config.php` WEITERHIN HART.
 *
 * `konfig_lib.php` TOLERIERT die fehlende Datei — es muss, weil
 * `install.php` auf einer Anlage laeuft, die noch keine hat. `db.php` darf
 * das nicht erben: Bis Web 20.26.3 brach sie mit einem Fatal ab
 * (`require ... config.php: Failed to open stream`), und genau dabei bleibt
 * es. Ohne diese Zeile waere aus dem klaren Befund „config.php fehlt" eine
 * PDO-Ausnahme auf einem leeren DSN geworden — also die Meldung
 * „Datenbank nicht erreichbar" fuer ein Problem, das nichts mit der
 * Datenbank zu tun hat. Eine falsche Diagnose ist teurer als ein Abbruch.
 *
 * DAMIT AENDERT DIESES PAKET AUCH HIER KEIN VERHALTEN (E-ZE-10): Wer
 * `db.php` ohne `config.php` laedt, kommt nicht weiter — vorher wie
 * nachher. Was sich aendert, ist allein der Satz, den er dabei liest. */
if (!is_file(__DIR__ . '/config.php')) {
    throw new RuntimeException(
        'server/config.php fehlt. Die Anwendung ist nicht eingerichtet — '
      . 'install.php anlegen und aufrufen (docs/Technik.md, Runbook).');
}

/* ---- DAS FEHLERPROTOKOLL (P5c/AP3, E-P5c-12, -58) --------------------------
 *
 * HIER UND GENAU EINMAL. Jede Seite, jeder Endpunkt und jeder Job laedt diese
 * Datei; ab dieser Zeile landet jede Ausnahme, die niemand faengt, und jede
 * Warnung im Reiter System — mit Kennung, ohne Anfragedaten. Die Fehlerseite
 * nennt die Kennung und die Kontaktadresse. `tools/quelltext/behandler.php`
 * zaehlt die beiden Zeilen: je eine, in dieser Datei.
 *
 * HINTER der Pruefung auf `config.php`: Fehlt sie, soll ihr Satz dastehen und
 * nicht eine Fehlerseite, die eine Datenbank fragt, die es nicht gibt. */
require_once __DIR__ . '/systemmeldung_lib.php';
set_exception_handler('system_ausnahme_behandeln');
set_error_handler('system_fehler_behandeln');
register_shutdown_function('system_abbruch_pruefen');

function db(): PDO {
    static $pdo = null;
    /* DER RIEGEL GEGEN DIE SCHLEIFE (P5a/AP9). Siehe den Block unten. */
    static $inUeberlast = false;
    if ($pdo === null) {
        try {
            $pdo = new PDO((string)konfig('db.dsn'), (string)konfig('db.user'),
                           (string)konfig('db.pass'), [
                /* KEINE PERSISTENTEN VERBINDUNGEN (E-P5a-18). Die Zeile
                 * fehlt hier mit Absicht: `PDO::ATTR_PERSISTENT` haelt die
                 * Verbindung ueber das Ende der Anfrage hinaus offen und
                 * belegt damit genau den Platz, um den es im Block unten
                 * geht. Auf einem Webspace mit `max_user_connections = 10`
                 * genuegen zehn ruhende PHP-Prozesse, und die elfte Anfrage
                 * bekommt 1203, obwohl niemand arbeitet. Gezaehlt am
                 * 16.09.2026: 0 Treffer fuer `ATTR_PERSISTENT` unter
                 * `server/` und `tools/` — es ist eine Zusage, keine
                 * Feststellung. */
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $ex) {
            /* ---- DIE VERBINDUNGSGRENZE (P5a/AP9, E-P5a-18) --------------
             *
             * 1040 und 1203 heissen nicht „kaputt", sondern „gerade kein
             * Platz". Bis hierher kamen sie als 500 heraus, mit dem
             * ungefilterten Ausnahmetext — in dem Hostname und Benutzername
             * der Datenbank stehen. Beides ist falsch; die Begruendung
             * steht bei `ueberlast_antwort()` in `wartung_lib.php`.
             *
             * DIE SCHLEIFE, DIE DER RIEGEL ABFAENGT: Alles, was unterhalb
             * einer 503-Antwort noch eine Einstellung nachsehen will
             * (`kopfzeilen_lib.php` liest zwei aus `app_state`), landet
             * ueber `app_state_lesen()` wieder hier — und zwar mit `$pdo`
             * weiterhin `null`, also mit einem zweiten Verbindungsversuch,
             * der genauso scheitert. Ohne den Riegel waere das eine
             * Endlosschleife bis zum Speicherende, und zwar ausgerechnet
             * unter Last. Mit ihm fliegt die Ausnahme beim zweiten Mal
             * einfach weiter; `app_state_lesen()` faengt sie und nimmt
             * seine Vorgabe — genau das, wofuer sie gebaut ist.
             *
             * Der Riegel wird NICHT zurueckgesetzt: `ueberlast_antwort()`
             * endet mit `exit`, und auf der Kommandozeile ist der zweite
             * Versuch ohnehin derselbe Fehler.
             *
             * AUF DER KOMMANDOZEILE wird gezaehlt, aber nicht geantwortet.
             * Ein Job, der eine HTML-Seite nach stdout schreibt und sich
             * beendet, verschluckt seinen eigenen Fehler; der Aufrufer soll
             * die Ausnahme sehen. Dieselbe Unterscheidung trifft
             * `wartung_tor()` eine Ebene hoeher. */
            if (!$inUeberlast && function_exists('ueberlast_erkannt')
                && ueberlast_erkannt($ex)) {
                $inUeberlast = true;
                ueberlast_vermerken();
                if (!wartung_cli()) { ueberlast_antwort(ueberlast_retry_s($ex)); }
            }
            throw $ex;
        }
        /* ZEITZONE DER VERBINDUNG AUSDRUECKLICH SETZEN (M5-09).
         *
         * Ohne diese Zeile rechnet NOW() in der Zeitzone des Datenbank-
         * servers — und die ist eine Einstellung des Hosters, kein Teil
         * dieser Anwendung. Sie kann sich beim naechsten Serverumzug aendern,
         * ohne dass hier jemand etwas tut.
         *
         * WARUM DAS ZWEI VERSCHIEDENE FOLGEN HAETTE
         * Die Anwendung benutzt beide Zeitfunktionen, und zwar mit Absicht:
         *
         *   UTC_TIMESTAMP()  fuer den Papierkorb. Dessen Frist laeuft ueber
         *                    30 Tage; eine Zeitumstellung mitten darin darf
         *                    nichts verschieben.
         *   NOW()            fuer alles Kurzlebige — Ratenschutz-Fenster,
         *                    Gueltigkeit von Tokens und Kopplungssitzungen. Diese
         *                    Werte werden in derselben Zeitrechnung
         *                    geschrieben und gelesen, oft im Abstand von
         *                    Sekunden.
         *
         * Solange beide dieselbe Zeitrechnung meinen, ist der Unterschied
         * folgenlos. Steht die Serverzone aber auf einer Ortszeit, laufen sie
         * um den Zonenversatz auseinander: Ein Ratenschutz-Fenster, das mit
         * NOW() geschrieben und mit UTC verglichen wird, ist eine oder zwei
         * Stunden zu frueh oder zu spaet abgelaufen.
         *
         * Mit UTC auf der Verbindung sind NOW() und UTC_TIMESTAMP() identisch.
         * Der Unterschied im Code bleibt trotzdem stehen — er sagt, WAS
         * gemeint ist, und ueberlebt damit eine kuenftige Aenderung dieser
         * Zeile.
         *
         * Die ANZEIGE ist davon unberuehrt: Sie rechnet in PHP nach
         * $CFG['app']['timezone'] um (siehe fmt_dt()).
         */
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

/**
 * Zentrale Stammdaten (Konzept: Zentrale Stammdaten & Transportziele):
 * user_id IS NULL kennzeichnet globale (Admin-)Eintraege. Die UNIQUE-Keys
 * (user_id, name) greifen bei NULL nicht (MySQL erlaubt mehrere NULLs),
 * daher muss die Duplikatpruefung in der Anwendung erfolgen.
 */

/** True, wenn bereits ein GLOBALER Eintrag mit gleichem (Vergleichs-)Namen
 *  existiert (case-insensitiv, optional zusaetzliches Gleichheitskriterium
 *  wie role/registration). $excludeId blendet den eigenen Datensatz beim
 *  Umbenennen aus. */
function stammdaten_dup_global(string $table, string $col, string $val,
                                ?string $extraCol = null, ?string $extraVal = null,
                                int $excludeId = 0): bool {
    $sql = "SELECT COUNT(*) FROM $table WHERE user_id IS NULL AND LOWER($col) = LOWER(?)";
    $params = [$val];
    if ($extraCol !== null) { $sql .= " AND $extraCol = ?"; $params[] = $extraVal; }
    if ($excludeId > 0) { $sql .= " AND id != ?"; $params[] = $excludeId; }
    $st = db()->prepare($sql);
    $st->execute($params);
    return (bool)$st->fetchColumn();
}

/* `stammdaten_dup_personal_count()` STAND HIER BIS S9/AP5b. Sie zaehlte, wie
 * viele Konten denselben Namen selbst fuehren, und beantwortete damit den
 * Admin-Hinweis „N NutzerInnen haben ..." auf der systemweiten
 * Stammdatenpflege. Ihre sechs Aufrufer standen ausnahmslos in
 * `admin_stammdaten.php`; mit der Seite verliert die Frage ihre Stelle. */

/* ---------------------------------------------------------------------------
 * EIN STANDORT WIRD GELOESCHT — WAS ES UEBERLEBT      S9/AP5-5, M-S9-10 (b)
 * ---------------------------------------------------------------------------
 *
 * `vehicles_ibfk_2` steht auf ON DELETE CASCADE, und das ist E15 woertlich:
 * Was an einem Standort haengt, geht mit ihm. Seit E-S9-09 nimmt es dabei
 * aber auch das mit, was ohne diesen Standort bestehen DUERFTE — Bergwacht,
 * Veranstaltung, Sonstiges. AP4 hat das bewusst stehen lassen und begruendet:
 * `ON DELETE SET NULL` waere falsch, weil es JEDES Standard-Rettungsmittel
 * standortlos machte, also einen Datensatz erzeugte, den die Pruefschicht nie
 * anlegen wuerde.
 *
 * VARIANTE B (freigegeben 08.09.2026): Die Ausnahme ist Anwendungslogik VOR
 * dem `DELETE`, kein Fremdschluessel. Ein `UPDATE`, das den drei Typen ohne
 * Standortpflicht den Standort abnimmt, laeuft in derselben Transaktion; der
 * Rest geht mit wie bisher.
 *
 * DIE REGEL STEHT NICHT HIER, sondern in `VEHICLE_TYPEN[...]['standort']` —
 * derselben Angabe, aus der `pruef_rettungsmittel()` entscheidet, ob ein Typ
 * ohne Standort angelegt werden darf. Zwei Fassungen davon liefen beim
 * naechsten Typ auseinander, und zwar still: Ein Rettungsmittel wuerde
 * geloescht, das man haette anlegen duerfen.
 *
 * EINE AUFRUFSTELLE SEIT S9/AP5b: `einstellungen.php` (eigene Standorte).
 * Bis dahin waren es zwei — `admin_stammdaten.php` pflegte den systemweiten
 * Bestand und uebergab dafuer `$userId === null`. Die Seite ist gestrichen
 * (R39), der Zweig `$userId === null` bleibt: Er ist billig, er trifft in
 * einer Anlage ohne zentrale Eintraege nie, und der Rueckbau in P5 (Backlog
 * Nr. 168) will genau hier nachsehen. Dieselbe Unterscheidung fuehrt
 * `stammdaten_dup_global()` darueber.
 *
 * WARUM HIER UND NICHT IN `validate_lib.php`. Das Konzept schreibt „eine
 * Funktion neben `pruef_rettungsmittel()`" — gemeint ist: EINE Fassung fuer
 * beide Seiten. Die Datei selbst sagt in ihrem Kopf „Diese Datei aendert von
 * sich aus nichts"; ein `UPDATE` darin waere der erste Verstoss dagegen.
 * `db.php` fuehrt mit `stammdaten_dup_global()` bereits genau diese Sorte
 * Helfer: eine Abfrage ueber den Stammdatenbestand, die mehrere Schreibwege
 * brauchen.
 * ------------------------------------------------------------------------ */

/**
 * Die Rettungsmittel eines Standorts, die sein Loeschen ueberleben.
 *
 * Gibt Kennung und Bezeichnung zurueck — die Rueckfrage nennt sie mit NAMEN
 * (M-S9-10, Anmerkung 3): Ein Rettungsmittel, das einen Standort verlaesst,
 * ist eine Nachricht und keine Statistik.
 *
 * @return list<array{id:int,name:string}>
 */
function stammdaten_ohne_standortpflicht(int $baseId, ?int $userId): array
{
    $typen = array_keys(array_filter(VEHICLE_TYPEN,
        static fn(array $t): bool => $t['standort'] === false));
    if ($typen === []) { return []; }
    $platz = implode(',', array_fill(0, count($typen), '?'));
    $sql = 'SELECT id, name FROM vehicles
             WHERE base_id = ? AND typ IN (' . $platz . ')
               AND user_id ' . ($userId === null ? 'IS NULL' : '= ?') . '
             ORDER BY name';
    $werte = array_merge([$baseId], $typen);
    if ($userId !== null) { $werte[] = $userId; }
    $q = db()->prepare($sql);
    $q->execute($werte);
    $raus = [];
    foreach ($q as $z) { $raus[] = ['id' => (int)$z['id'], 'name' => (string)$z['name']]; }
    return $raus;
}

/**
 * Ihnen den Standort abnehmen — VOR dem `DELETE FROM bases`.
 *
 * Muss in derselben Transaktion laufen wie das Loeschen: Sonst stuende bei
 * einem Abbruch dazwischen ein Rettungsmittel ohne Standort da, dessen
 * Standort es noch gibt.
 *
 * @return int wie viele
 */
function stammdaten_standort_loesen(int $baseId, ?int $userId): int
{
    $typen = array_keys(array_filter(VEHICLE_TYPEN,
        static fn(array $t): bool => $t['standort'] === false));
    if ($typen === []) { return 0; }
    $platz = implode(',', array_fill(0, count($typen), '?'));
    $sql = 'UPDATE vehicles SET base_id = NULL
             WHERE base_id = ? AND typ IN (' . $platz . ')
               AND user_id ' . ($userId === null ? 'IS NULL' : '= ?');
    $werte = array_merge([$baseId], $typen);
    if ($userId !== null) { $werte[] = $userId; }
    $q = db()->prepare($sql);
    $q->execute($werte);
    return $q->rowCount();
}

/**
 * Der Satz der Rueckfrage vor dem Loeschen eines Standorts (M-S9-10 b).
 *
 * Er stand an EINER Stelle, weil er an zwei gebraucht wurde, und er steht dort
 * weiter, weil er drei
 * Zahlen zusammenbringt, die leicht auseinanderlaufen: die Zahl der
 * mitgeloeschten Saetze, die Zahl der ueberlebenden Rettungsmittel und deren
 * Namen. Bis Web 17.0.0 zaehlte die Rueckfrage ALLES mit — sie sagte „6
 * werden mitgeloescht", und eines davon blieb dann doch nicht.
 *
 * $zusatz haengt hinten an (die Verwaltung nannte zusaetzlich, wie viele
 * Konten den Standort gewaehlt haben).
 *
 * SEIT S9/AP5b HAT DIESE FUNKTION EINEN AUFRUFER, NICHT ZWEI. Mit
 * `admin_stammdaten.php` (R39) faellt der Aufrufer weg, der `$systemweit =
 * true` und `$zusatz` uebergab: Beide sind seither unerreichbar. Sie bleiben
 * trotzdem stehen — die vier ausgeschriebenen Beugungsformen unten sind
 * sichtbarer Text, und den baut man nicht als Nebenwirkung eines
 * Streichpakets um. Sie fallen mit dem Modell in P5 (Backlog Nr. 168).
 */
function stammdaten_loeschfrage(string $name, int $anzahlGesamt, array $bleiben,
                                bool $systemweit, string $zusatz = ''): string
{
    $bleibt = count($bleiben);
    $mit    = max(0, $anzahlGesamt - $bleibt);
    /* DIE BEUGUNG STEHT AUSGESCHRIEBEN, sie wird nicht gerechnet. Der erste
       Entwurf schnitt das „e" von „eigene" ab und hängte ein „r" an — daraus
       wurde „Ein eigenr Stammdatensatz" (und „systemweitr"). Deutsche
       Adjektivendungen aus einer Zeichenkette abzuleiten geht schief, sobald
       jemand ein zweites Wort einsetzt; vier Formen hinzuschreiben kostet
       vier Zeilen und hält. Gefunden von der Klickprobe. */
    $einer = $systemweit ? 'Ein systemweiter Stammdatensatz' : 'Ein eigener Stammdatensatz';
    $viele = $systemweit ? ' systemweite Stammdatensätze'    : ' eigene Stammdatensätze';
    $keine = $systemweit ? 'systemweiten' : 'eigenen';
    $satz = 'Standort „' . $name . '“ ' . ($systemweit ? 'systemweit ' : '') . 'löschen? ';
    if ($mit > 0) {
        $satz .= ($mit === 1 ? $einer : $mit . $viele)
               . ' dieses Standorts (Rettungsmittel, Besatzung, Zielkliniken, weitere '
               . 'Rettungsmittel, Bergwacht) '
               . ($mit === 1 ? 'wird' : 'werden') . ' mitgelöscht. ';
    } else {
        $satz .= 'Es hängen keine ' . $keine . ' Stammdaten daran, die mitgelöscht würden. ';
    }
    if ($bleibt > 0) {
        /* MIT NAMEN, NICHT MIT ZAHL (M-S9-10, Anmerkung 3). Bei mehr als
           dreien wird die Aufzaehlung sonst laenger als der Rest des Textes;
           dann nur die Zahl und die ersten drei. */
        $namen = array_column($bleiben, 'name');
        $liste = count($namen) <= 3
            ? implode(', ', $namen)
            : implode(', ', array_slice($namen, 0, 3)) . ' und '
              . (count($namen) - 3) . ' weitere';
        $satz .= ($bleibt === 1
                ? '1 Rettungsmittel ohne Standortpflicht — ' . $liste . ' — bleibt bestehen und steht'
                : $bleibt . ' Rettungsmittel ohne Standortpflicht (' . $liste . ') bleiben bestehen und stehen')
               . ' danach unter „Ohne Standort“. ';
    }
    if ($zusatz !== '') { $satz .= $zusatz . ' '; }
    return $satz . 'Bereits dokumentierte Diensttage bleiben unverändert.';
}

/**
 * Adresse einer statischen Datei mit angehaengtem Erkennungswert.
 * Aendert sich die Datei, aendert sich die Adresse, und der Browser laedt
 * Stylesheet bzw. Skript neu — ohne dass jemand den Zwischenspeicher leeren muss.
 *
 * Seit Web 5.4.0 ist das der ZEITSTEMPEL DER DATEI, nicht mehr WEB_VERSION
 * (Backlog Nr. 9). Vorher entwertete jede Versionserhoehung den
 * Zwischenspeicher aller Dateien — auch derer, die sich nicht geaendert
 * hatten. Bei einer Korrekturfassung, die eine einzige Zeile im Stylesheet
 * anfasst, luden Besucher trotzdem saemtliche Skripte erneut.
 *
 * Zum Auslieferungsweg (Pruefschritt P8): Der FTP-Deploy uebertraegt nur
 * Dateien, deren Inhalt sich geaendert hat — er fuehrt dafuer auf dem Server
 * eine Zustandsdatei mit Pruefsummen. Unveraenderte Dateien werden also nicht
 * angefasst und behalten ihren Zeitstempel; uebertragene bekommen den
 * Zeitpunkt des Hochladens, was genau der gewuenschte Wechsel ist. Der
 * Zeitstempel muss dabei NICHT erhalten bleiben — er dient hier als
 * Aenderungsmarke, nicht als Datum.
 *
 * Rueckfall auf WEB_VERSION, wenn die Datei nicht gefunden wird: Dann ist der
 * Verweis ohnehin falsch, und ein fehlender Erkennungswert waere der
 * unangenehmere der beiden Fehler — die Adresse bliebe fuer immer dieselbe.
 *
 * Die Pfade sind seitenrelativ ('assets/style.css'); alle aufrufenden Seiten
 * liegen in diesem Verzeichnis, weshalb __DIR__ die richtige Wurzel ist.
 */
function asset(string $pfad): string {
    // Je Anfrage wird dieselbe Datei mehrfach erfragt (Kopf- und Fusszeile,
    // favicon_tags()); das Ergebnis wird deshalb gemerkt. Der stat-Aufruf
    // selbst ist billig und zusaetzlich vom Dateistatus-Zwischenspeicher von
    // PHP gedeckt.
    static $merker = [];
    if (!array_key_exists($pfad, $merker)) {
        // Ohne Anfuehrungszeichen im Fehlerfall: Eine fehlende Datei ist hier
        // kein Grund fuer eine Warnung im Protokoll, der Rueckfall darunter
        // behandelt sie.
        $zeit = @filemtime(__DIR__ . '/' . ltrim($pfad, '/'));
        $merker[$pfad] = $zeit !== false ? (string)$zeit : WEB_VERSION;
    }
    return $pfad . '?v=' . $merker[$pfad];
}

/**
 * Verweise auf das Browser-Symbol (Favicon), zentral an einer Stelle.
 *
 * Zwei Angebote, weil Browser sich unterschiedlich verhalten: das PNG mit
 * Versionsnummer (laedt nach einem Wechsel automatisch neu) und die .ico im
 * Wurzelverzeichnis. Letztere fragen Browser zusaetzlich von sich aus unter
 * /favicon.ico ab — sie greift also selbst dann, wenn der Verweis im
 * Seitenkopf einmal ins Leere laufen sollte.
 */
function favicon_tags(): string {
    // Wurzelbezogener Pfad statt eines relativen: So spielt es keine Rolle,
    // unter welcher Adresse die Seite gerade aufgerufen wird.
    $basis = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');

    /* DAS FAVICON FOLGT DER LOGO-WAHL (E-P3-20, ab Web 9.7.0). Kopfleiste
     * und Browser-Symbol wechseln gemeinsam — ein Konto mit dem Fahrzeug in
     * der Kopfleiste und dem Hubschrauber im Tab waere ein Widerspruch, den
     * niemand erklaeren koennte. Die Auswahl trifft logo_stamm()
     * (session_lib.php); ohne Sitzung — Anmeldung, Einrichter — kommt der
     * Standard zurueck, und genau das soll die Anmeldeseite zeigen.
     *
     * Die .ico bleibt unveraendert: Sie liegt als EINE Datei in der Wurzel
     * und ist der Rueckfall fuer Browser, die kein PNG-Icon nehmen. Eine
     * zweite .ico je Logo waere zwei Dateien fuer einen Rueckfall, den
     * heute kaum ein Browser braucht. */
    $png = function_exists('logo_stamm') && logo_stamm() === 'gen-em_logo_nef'
        ? 'assets/images/favicon_nef.png'
        : 'assets/images/favicon_helicopter.png';

    // PNG zuerst: Es ist die Fassung, die wir sicher ausliefern. Die .ico ohne
    // sizes-Angabe hinterher — mit sizes="any" wuerden manche Browser sie
    // bevorzugen und bei ihrem Fehlen gar kein Symbol zeigen.
    return '<link rel="icon" type="image/png" href="' . e($basis . '/' . asset($png)) . '">' . "\n"
         . '<link rel="icon" href="' . e($basis . '/favicon.ico') . '">'
         . '<link rel="apple-touch-icon" href="' . e($basis . '/' . asset($png)) . '">';
}

/**
 * Pfad zum Logo fuer Anmelde- und Einrichtungsseite.
 * Die Einstellung 'logo_path' darf auf eine EIGENE Datei zeigen; existiert
 * sie nicht, wird die mitgelieferte Bildmarke genommen. Ohne diese Pruefung
 * bliebe die Seite bei einem veralteten Eintrag in der config.php ohne Logo.
 */
function logo_src(): string {
    /* SEIT WEB 9.10.0 ENTSCHEIDET DIE LOGO-WAHL (F-P3-AN).
     *
     * Diese Funktion versorgt die beiden Seiten OHNE Sitzung — Anmeldung und
     * Passwort setzen. Genau dort soll der Standard der Installation stehen
     * (E-P3-20), und genau dort stand er nicht: Sie las `app.logo_path` aus
     * der config.php, und der Einrichter schreibt dort den Hubschrauber
     * hinein. Ein Wechsel des Standards in der Wartung wirkte damit ueberall
     * ausser auf der Anmeldeseite — auf der einen Seite, die ihn zeigen soll.
     *
     * `logo_path` bleibt, aber nur fuer seinen eigentlichen Zweck: eine
     * FREMDE Datei. Zeigt die Einstellung auf eines der beiden mitgelieferten
     * Logos (was der Einrichter vorgibt), entscheidet die Wahl.
     *
     * `function_exists`: db.php ist die untere Schicht und laedt session_lib
     * nicht. Wo sie fehlt — im Einrichter vor der ersten Einrichtung —, bleibt
     * es beim Hubschrauber. */
    $pfad = (string)konfig('app.logo_path', '');
    $eigen = $pfad !== ''
        && !str_contains($pfad, 'gen-em_logo_helicopter')
        && !str_contains($pfad, 'gen-em_logo_nef')
        && is_file(__DIR__ . '/' . ltrim($pfad, '/'));
    if ($eigen) { return asset($pfad); }
    $stamm = function_exists('logo_stamm') ? logo_stamm() : 'gen-em_logo_helicopter';
    return asset('assets/images/' . $stamm . '.svg');
}

/** UTC-DATETIME (aus DB) -> Anzeige in App-Zeitzone */
/**
 * Einen langen Hexwert in Vierergruppen — die Form zum Ablesen und Abtippen.
 *
 * WOFUER. Zwei Stellen brauchen dasselbe: die SHA-256 des APK (wer sie
 * nachrechnet, verliert 64 Zeichen am Stueck beim dritten Blockwechsel) und
 * seit S10 die beiden Geheimnisse auf dem Schluesselblatt (die werden im
 * Ernstfall von Papier abgetippt).
 *
 * ER STEHT HIER UND NICHT IN EINER DER BEIDEN BIBLIOTHEKEN, weil sonst die
 * eine die andere laden muesste — `serverkrypto_lib.php` haengt nicht an
 * `apk_lib.php` und soll es nicht. Bis Web 20.1.0 stand die Rechnung zweimal
 * da, wortgleich; gefunden beim Gegenlesen des Konzepts S10 (F-16).
 *
 * Der Wert selbst bleibt unveraendert; wer ihn kopiert, bekommt die
 * Leerzeichen mit und muss sie entfernen — beim Nachtragen vom Blatt tut das
 * `schluessel_eingabe_normalisieren()` von selbst.
 */
function hex_vierergruppen(string $hex): string
{
    return trim(chunk_split($hex, 4, ' '));
}

/* `fmt_local()` STAND HIER BIS WEB 20.31.0 und liegt jetzt in
 * `format_lib.php` (Schritt 15 AP7). Der Name ist unveraendert, und diese
 * Datei laedt die neue oben — jeder der 113 Aufrufer findet die Funktion
 * also weiter, ohne etwas zu tun.
 *
 * WARUM SIE UMGEZOGEN IST: `datum_text()` und `datum_zeit_text()` bauen auf
 * ihr auf. Waere sie hiergeblieben, muesste `format_lib.php` die
 * Datenbankdatei laden — und die darf sie nicht laden, weil `install.php`
 * sie ueber `plattform_lib.php` erreicht, bevor es eine `config.php` gibt.
 * Die Rechnung ein zweites Mal zu fuehren waere das Gegenteil dessen,
 * wofuer es diesen Schritt gibt.
 *
 * `local_to_utc()` BLEIBT HIER, und das ist kein Versehen: Sie liest einen
 * Formularwert, um damit zu RECHNEN. `format_lib.php` macht aus Werten Text
 * fuer Menschen; das ist die andere Richtung. */

/**
 * Ortszeit (App-Zeitzone) -> UTC-DATETIME. Gegenstueck zu fmt_local()
 * (jetzt in format_lib.php).
 *
 * Lag frueher in einsatz_form.php. Seit dem Import (import_commit.php) gibt es
 * einen zweiten Aufrufer; zwei Kopien derselben Zeitrechnung waeren die
 * sicherste Art, sich spaeter eine Stunde Versatz einzuhandeln.
 *
 * $addDays deckt Zeiten nach Mitternacht ab, die noch zum Diensttag gehoeren.
 */
function local_to_utc(string $day, string $hhmm, int $addDays = 0): ?string {
    // Nicht nur das Muster pruefen, sondern auch den Wertebereich: "25:00"
    // passt auf \d{2}:\d{2}, und DateTime rechnet daraus klaglos den naechsten
    // Tag 00:00. Eine Falscheingabe waere so als stiller Datumssprung
    // durchgerutscht statt als Fehler aufzufallen.
    if (!preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $t)) return null;
    if ((int)$t[1] > 23 || (int)$t[2] > 59) return null;
    $dt = DateTime::createFromFormat('Y-m-d H:i', "$day $hhmm",
        new DateTimeZone((string)konfig('app.timezone')));
    if ($dt === false) return null;
    if ($addDays > 0) { $dt->modify("+$addDays day"); }
    $dt->setTimezone(new DateTimeZone('UTC'));
    return $dt->format('Y-m-d H:i:s');
}

/* Hier stand iso_to_sql() — ISO-8601 der Uhr nach DATETIME. Ihre einzigen
   Aufrufer lagen in ingest.php und wurden mit Web 4.2.0 durch pruef_utc()
   aus validate_lib.php ersetzt; die Funktion blieb als Rest stehen und war
   seither ohne Verwendung (A4, T-01). Wer das Format wieder braucht, findet
   die Umwandlung in pruef_utc(). */

/**
 * EINE JSON-ANTWORT AUS FERTIGEM TEXT (P5a/AP4a, E-P5a-38, Backlog Nr. 203).
 *
 * WOGEGEN. Sieben Stellen gaben JSON aus, ohne durch `json_out()` zu gehen —
 * mit `header('Content-Type: application/json')` und `echo` von Hand. Zwei
 * davon (`api/export_data.php`) setzten dabei **kein** `Cache-Control:
 * no-store`, und der Export liefert GPS-Spurpunkte. Die Begruendung, die
 * unten bei `json_out()` steht („der Kopf gehoert an die Stelle, durch die
 * JEDE Antwort geht"), galt fuer sie schlicht nicht.
 *
 * WARUM SIE UEBERHAUPT AN `json_out()` VORBEIGEHEN: Sie haben den Text
 * bereits. `api/export_data.php` baut ihn stueckweise (ein Export kann
 * hunderte Megabyte umfassen), `api/backup_data.php` reicht Chiffretext
 * durch, `jobs.php` braucht eigene `json_encode`-Schalter. Sie sollen ihn
 * NICHT dekodieren muessen, nur um ihn wieder zu kodieren.
 *
 * Also: dieselben Kopfzeilen, ein anderer Rumpf. `json_out()` ist seither
 * ein Aufruf hiervon — es gibt genau EINE Stelle, die den Satz setzt.
 *
 * NICHT HIER: `wartung_lib.php`. Die Wartungsseite ist ausdruecklich ohne
 * Datenbank gebaut und darf `db.php` nicht laden (sie antwortet, waehrend die
 * Datenbank umgebaut wird). Sie setzt ihren Satz selbst — und zwar
 * einschliesslich `no-store`, nachgesehen.
 */
/**
 * NUR DIE KOPFZEILEN EINER JSON-ANTWORT — ohne Rumpf und ohne `exit`.
 *
 * WOFUER. `pair.php` antwortet an zwei Stellen und ARBEITET DANN WEITER: Es
 * schliesst die Antwort ab (`antwort_abschliessen()`) und reiht erst danach
 * die Hinweismail ein — die Uhr wartet auf das `ok`, und ein langsamer
 * Mailserver darf sie nicht in den Abbruch laufen lassen. Ein `never`
 * schliesst diese Stelle aus.
 *
 * ES BLEIBT EINE STELLE, die den Satz setzt: `json_roh_out()` ruft dies hier
 * auf, `json_out()` ruft `json_roh_out()`.
 */
function json_kopf(int $code = 200): void {
    /* Der schmale Kopfzeilensatz der Endpunkte (P5a/AP4, E-P5a-15). Keine
     * CSP — eine JSON-Antwort ist kein Dokument. `nosniff` dagegen ist genau
     * hier die wichtige Zeile. */
    require_once __DIR__ . '/kopfzeilen_lib.php';
    kopfzeilen_json();

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    /* Kein Zwischenspeichern (M3-11).
     *
     * Bisher setzte GENAU EIN Endpunkt diesen Kopf: das Backup. Vier
     * weitere liefern denselben Chiffretext aus — Tagesdaten, Zeitraum,
     * Suchindex, Einzeleinsatz —, und die durften Zwischenspeicher auf dem
     * Weg befuellen. Das ist kein theoretischer Einwand: An einem
     * gemeinsamen Rechner reicht die Zurueck-Taste, um eine Antwort aus dem
     * Speicher des Browsers zu holen, nachdem sich jemand abgemeldet hat.
     * Der Inhalt ist verschluesselt, die Huelle drumherum — Datum, Uhrzeit,
     * Einsatznummer, Koordinaten — nicht.
     *
     * Der Kopf gehoert deshalb an die Stelle, durch die JEDE Antwort geht,
     * und nicht in die Zustaendigkeit des einzelnen Endpunkts. */
    header('Cache-Control: no-store');
}

function json_roh_out(string $json, int $code = 200): never {
    json_kopf($code);
    echo $json;
    exit;
}

function json_out(array $data, int $code = 200): never {
    json_roh_out((string)json_encode($data), $code);
}

/* ---- DER EINGANG DER ENDPUNKTE (Schritt 15/AP3, E-ZE-15, F-ZE-5) ---------
 *
 * Einundzwanzig Dateien unter `api/` begannen mit derselben Handarbeit:
 * Methode pruefen, Rumpf lesen, „leer?", „ist das ein JSON-Objekt?". Vier
 * Muster, vier Schreibweisen, und sie waren auseinandergelaufen — siebzehn
 * Dateien antworteten `'method'`, drei `'methode'`; acht sagten `'payload'`,
 * drei `'format'`; der Hinweis auf `post_max_size` stand in drei Fassungen.
 * Kein einziger dieser Schluessel wird im Browser ausgewertet (gemessen: 0
 * Stellen im JavaScript), sie sind also reine Drift.
 *
 * ---- WARUM ZWEI FUNKTIONEN UND NICHT EINE --------------------------------
 *
 * Das Konzept sah EINEN Aufruf vor, `api_eingang()`, der Methode und Rumpf
 * zusammen erledigt. Das geht nicht, ohne Verhalten zu aendern, denn zwischen
 * beiden steht in ALLEN elf Rumpf-Dateien eine dritte Zeile:
 *
 *     Methode pruefen  ->  csrf_check()  ->  Rumpf lesen
 *
 * Ein Aufruf, der Methode und Rumpf zusammenfasst, schiebt das Rumpflesen vor
 * die Token-Pruefung — ein Aufrufer ohne gueltiges Token bekaeme dann `leer`
 * oder `format` statt `csrf`, und in `api/kdf_upgrade.php` liefe er am
 * Demo-Ausstieg vorbei, der zwischen csrf und Rumpf steht (200 wuerde zu
 * 400). Genau diese Klasse von Fehler hat das Projekt am 13.09.2026 schon
 * einmal behoben; der Kopfkommentar jener Datei erzaehlt es.
 *
 * Also zwei Funktionen, und die `csrf_check()`-Zeile bleibt, wo sie ist. CSRF
 * gehoert aus einem zweiten Grund nicht hier hinein: Ein Endpunkt ohne
 * Sitzung (10c AP6, `api/health.php`) braucht die Methodenpruefung und sonst
 * nichts.
 */
/**
 * Erlaubte Anfragemethode — sonst 405 `method`.
 *
 * @param string|list<string> $erlaubt Eine Methode oder mehrere.
 */
function api_methode(string|array $erlaubt = 'POST'): void {
    $liste = is_array($erlaubt) ? $erlaubt : [$erlaubt];
    if (!in_array((string)($_SERVER['REQUEST_METHOD'] ?? ''), $liste, true)) {
        json_out(['error' => 'method'], 405);
    }
}

/**
 * Den Anfragerumpf lesen und als JSON-Objekt zurueckgeben.
 *
 * Antwortet selbst und bricht ab bei: leerem Rumpf (400 `leer`, mit dem
 * Hinweis auf `post_max_size` — er steht seit AP3 einmal, hier), Rumpf ist
 * kein JSON-Objekt (400 `format`) und, WENN `max_bytes` gesetzt ist, zu
 * grossem Rumpf (413 `zu_gross`).
 *
 * `max_bytes` HAT KEINEN VORGABEWERT, und das ist Absicht: Heute begrenzt
 * keiner der elf Endpunkte die Rumpfgroesse — ein Konto-Backup kann zweistellig
 * megabytegross sein, und `app.max_body_bytes` (512 KB) ist die Grenze des
 * Geraete-Eingangs, nicht die der Weboberflaeche. Eine Vorgabe haette hier
 * eine Pruefung eingefuehrt, die es nicht gab (E-ZE-10).
 *
 * INHALTLICHE Pruefungen bleiben beim Aufrufer: ob ein `format`-Feld den
 * richtigen Wert hat, ob `eintraege` da ist, ob die Liste zu lang ist. Der
 * Eingang beantwortet nur die Frage, ob ueberhaupt ein Objekt angekommen ist.
 *
 * @param array{max_bytes?:int} $o
 * @return array<mixed>
 */
function api_rumpf(array $o = []): array {
    $roh = file_get_contents('php://input');
    if ($roh === false) { $roh = ''; }

    if (isset($o['max_bytes']) && strlen($roh) > (int)$o['max_bytes']) {
        json_out(['error' => 'zu_gross'], 413);
    }
    if ($roh === '') {
        json_out(['error' => 'leer', 'hinweis' =>
            'Es kamen keine Daten an — evtl. begrenzt der Server die Upload-Größe '
          . '(post_max_size, client_max_body_size).'], 400);
    }

    $b = json_decode($roh, true);
    if (!is_array($b)) { json_out(['error' => 'format'], 400); }
    return $b;
}

/* ---- DAS TOR DES WARTUNGSMODUS (S5 Paket W, E-S5W-06) --------------------
 *
 * Hier und nicht in `auth_guard.php`: Dort liefen nur die SEITEN durch.
 * `ingest.php` und `pair.php` laden `db.php` direkt — und das sind die
 * beiden, auf die es ankommt, weil sie die Daten der Uhr bringen.
 *
 * Und hier und nicht weiter oben: Die Zeile steht HINTER `json_out()`, damit
 * die Reihenfolge der Datei stimmt, und VOR jedem `db()` — die Verbindung
 * entsteht erst beim ersten Aufruf (statisch, siehe oben), also ist bis zu
 * dieser Zeile noch nichts an der Datenbank geschehen. Genau das ist der
 * Punkt: Der Wartungsmodus wird gebraucht, WEIL die Datenbank gerade
 * umgebaut wird.
 *
 * `wartung_lib.php` laedt seinerseits nichts (auch nicht diese Datei) und
 * kehrt auf der Kommandozeile sofort zurueck. Steht keine `wartung.lock`,
 * kostet der Aufruf einen `file_exists()`.
 */
/* DIE KOPFZEILEN STEHEN JEDER DATEI ZUR VERFUEGUNG, DIE `db.php` LAEDT
 * (P5a/AP4). `kopf_nonce_attr()` wird in 25 Inline-Bloecken gebraucht — auch
 * in `session_lib.php`, das ohne `ui.php` auskommt. Eine Datei, die nur
 * Funktionen definiert, kostet nichts. */
require_once __DIR__ . '/kopfzeilen_lib.php';
require_once __DIR__ . '/instanz_lib.php';   // Name dieser Installation (P5a/AP5)
require_once __DIR__ . '/wartung_lib.php';
wartung_tor();

/* DIE SITZUNGSBIBLIOTHEK (Schritt 16, E-SA-02; Schritt 15/AP2, E-ZE-06).
 *
 * BIS P5c/AP3 STAND HIER „EINE VON ZWEI AUFRUFSTELLEN" der Sitzungsablage,
 * die andere sei `install.php`, und eine Begruendung, warum der Aufruf hier
 * und nicht an den neun `session_start()` steht. Beides war seit Web 20.27.0
 * nicht mehr wahr: Bis Web 20.26.3 stand hier `sitzung_ablage();`; seither
 * gibt es nur noch EINEN Sitzungsstart, `sitzung_starten()`, und der richtet
 * die Ablage selbst ein, unmittelbar bevor PHP die Sitzungsdatei anlegt —
 * also nicht mehr bei jeder Anfrage, die `db.php` laedt, ohne eine Sitzung
 * zu starten (Register Z01/Z02 halten beides auf eins).
 *
 * WAS HIER BLEIBT, ist das Laden: `jobs_lib.php` braucht den Raeumteil,
 * `plattform_lib.php` die Auskunft, und beide verlassen sich darauf, dass
 * `db.php` die Datei mitbringt. Sie laedt ihrerseits nichts — die Zeile ist
 * vor und hinter `wartung_tor()` gleich billig; sie steht dahinter, weil die
 * Sperrpfade des Tors mit `exit` enden und keine Sitzung brauchen. */
require_once __DIR__ . '/sitzung_lib.php';

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/**
 * Eine Abfrage ueber eine ID-Liste, in Bloecken ausgefuehrt (M3-15, M5-12).
 *
 * WARUM SIE HIER STEHT
 * Dieselbe Aufgabe — "hole alle Unterzeilen zu diesen n Datensaetzen, aber
 * nicht mit n Abfragen" — faellt an drei Stellen an: Export, Tagesansicht und
 * Backup. Der Export hat sie als Erstes geloest und den Weg im Kommentar
 * vermerkt; die beiden anderen sind ihm nicht gefolgt und fragten je
 * Datensatz einzeln. Bei 1600 Einsaetzen waren das ueber 6000 Abfragen fuer
 * EIN Backup.
 *
 * Die Vorlage traegt {IDS} an der Stelle der Platzhalterliste. Bewusst KEINE
 * Formatzeichenkette mit %s: Damit waere jedes weitere Prozentzeichen im
 * SQL-Text ein Formatbefehl, und ein kuenftiges LIKE '%tag%' wuerde
 * stillschweigend verstuemmelt (M3-14).
 *
 * Die Blockgroesse haelt Abstand zur Parametergrenze von MySQL/MariaDB und
 * verhindert Einzelanweisungen von mehreren hundert Kilobyte.
 *
 * @param array $ids         ID-Liste; eine leere Liste liefert ein leeres
 *                           Ergebnis, ohne die Datenbank zu behelligen.
 * @param array $leadParams  Parameter, die im SQL VOR {IDS} stehen.
 */
function sql_in_bloecken(PDO $pdo, string $sqlVorlage, array $ids,
                         array $leadParams = [], int $blockGroesse = 1000): array
{
    $out = [];
    foreach (array_chunk(array_values($ids), max(1, $blockGroesse)) as $block) {
        $platz = implode(',', array_fill(0, count($block), '?'));
        $st = $pdo->prepare(str_replace('{IDS}', $platz, $sqlVorlage));
        $st->execute(array_merge($leadParams, $block));
        foreach ($st->fetchAll() as $row) { $out[] = $row; }
    }
    return $out;
}

/**
 * Einen Ausnahmefehler protokollieren und eine Kennung dafuer liefern (M3-10).
 *
 * WAS DARAN FALSCH WAR
 * Neun Endpunkte gaben den Text der Ausnahme unveraendert nach aussen:
 *
 *     json_out(['error' => 'day', 'meldung' => $ex->getMessage()], 500);
 *
 * Solche Texte nennen Tabellen- und Spaltennamen, gelegentlich Teile der
 * Abfrage, bei Verbindungsfehlern auch Hostnamen und Benutzernamen der
 * Datenbank. Das Browser-Skript zeigt `meldung` direkt an — der Text stand
 * also auf dem Bildschirm und in jedem Screenshot, den jemand zur Fehlersuche
 * verschickt.
 *
 * ZUGLEICH WAR ER FUER DIE FEHLERSUCHE UNBRAUCHBAR: Was auf dem Bildschirm
 * stand, stand nirgends sonst. Wer eine Woche spaeter nachsehen wollte, hatte
 * nur die Erinnerung an einen Screenshot.
 *
 * Beides loest dieselbe Aenderung: Der volle Text geht ins Protokoll, nach
 * aussen geht eine Kennung. Sie ist kurz genug, um sie am Telefon
 * durchzugeben, und lang genug, um im Protokoll eindeutig zu sein.
 *
 * SEIT P5c/AP3 (E-P5c-58) steht der Text im Reiter System und nicht mehr im
 * Fehlerprotokoll des Webspace, bereinigt wie jede fremde Meldung
 * (`systemmeldung_lib.php`); nur wenn die Datenbank nicht antwortet, geht er
 * mit derselben Kennung dorthin. Die ANTWORTFORM bleibt, auch auf dem
 * Geraeteweg: `{"error": …, "kennung": …}` (`JSON-Vertrag.md` 5).
 *
 * Bewusst NICHT geaendert: install.php und update.php zeigen ihre Ausnahmen
 * weiterhin im Klartext. Beide laufen nur fuer Verwaltende, beide in Lagen
 * (Ersteinrichtung, Migration), in denen der genaue Text die eigentliche
 * Auskunft ist — und bei install.php gibt es noch kein Fehlerprotokoll, in
 * dem man nachsehen koennte.
 */
function fehler_kennung(Throwable $ex, string $bereich): string
{
    return system_melden($bereich, 'unerwarteter Fehler', $ex, [], 'ausnahme');
}

/**
 * Die Standardantwort eines Endpunkts auf einen unerwarteten Fehler (M3-10).
 * Beendet die Anfrage mit 500 und nennt nur die Kennung.
 */
function json_fehler(Throwable $ex, string $bereich): never
{
    /* GEDRAENGEL IST KEIN DEFEKT (P5a/AP9, E-P5a-52). Ein Deadlock oder ein
     * abgelaufener Sperrzeitraum sagt „noch einmal versuchen", nicht
     * „kaputt". Die Begruendung und der Fund stehen bei `gedraengel_erkannt()`
     * in `wartung_lib.php`. */
    if (gedraengel_erkannt($ex)) {
        gedraengel_vermerken($ex, $bereich);
        ueberlast_antwort();
    }
    $kennung = fehler_kennung($ex, $bereich);
    json_out(['error'   => $bereich,
              'kennung' => $kennung,
              'meldung' => 'Es ist ein unerwarteter Fehler aufgetreten (Kennung '
                         . $kennung . '). ' . system_meldesatz($kennung)], 500);
}

/**
 * Beschriftungen der Phasen.
 *
 * Uebertragen und gespeichert werden ausschliesslich 2 bis 9. Phase 1 ("Frei")
 * ist ein Anzeigezustand der Uhr und erzeugt keinen Zeitstempel; sie steht hier
 * nur fuer die Anzeige.
 *
 * EINE PHASE 10 GIBT ES NICHT. Sie wurde mit der Migration
 * 2026_07_19_phase10_entfernen abgeschafft — der Abschluss eines Einsatzes
 * laeuft seither ueber das Kennzeichen 'final' und den Endzeitpunkt. Die
 * Beschriftung stand danach noch hier und liess einen Altbestand als
 * GUELTIGEN Zustand erscheinen. Ohne sie erscheint er als unbekannte Phase —
 * und das ist er.
 *
 * NEUTRALE BESCHRIFTUNGEN seit Web 6.0.0 (Entscheidung E20): Phase 3 hiess
 * "Abflug", Phase 7 "Landung Krankenhaus". Beide Woerter passen nur zur
 * Luftrettung. Nummerierung und Bedeutung sind unveraendert — es sind
 * ausschliesslich die Beschriftungen, damit die Uhr die Einsatzart nicht
 * kennen muss (E21).
 */
const PHASE_LABELS = [
    1 => 'Frei', 2 => 'Alarmierung', 3 => 'Ausrücken', 4 => 'Ankunft Einsatzort',
    5 => 'Ankunft PatientIn', 6 => 'Transportbeginn', 7 => 'Ankunft Klinik',
    8 => 'Übergabezeit', 9 => 'Endzeit des Einsatzes',
];

/**
 * Besatzungsrollen — fester Katalog im Code, NICHT in der Datenbank (E4).
 *
 * Welche Rollen ein Rettungsmittel besetzt, wird an ihm angehakt und liegt in
 * `vehicle_roles`; welche Rollen ein DIENSTTAG anbietet, ergibt sich aus der
 * Zeilenmenge in `day_crew` (eingefroren beim Anlegen, E8). Dieser Katalog
 * liefert nur Beschriftung, Zugehoerigkeit und Reihenfolge.
 *
 * 'kind' = 'air' | 'ground' | 'both'. "Sonstige" ist ausdruecklich DIESELBE
 * Rolle bei beiden Arten (E6) und nicht zwei gleichnamige.
 *
 * DIE NOTAERZTIN IST KEINE ROLLE — sie ist die NutzerIn.
 *
 * Die Reihenfolge im Array ist die Anzeigereihenfolge.
 */
const CREW_ROLES = [
    'p1'      => ['label' => 'Pilot 1',    'kind' => 'air'],
    'p2'      => ['label' => 'Pilot 2',    'kind' => 'air'],
    'hems'    => ['label' => 'HEMS-TC',    'kind' => 'air'],
    'fr'      => ['label' => 'Flugretter', 'kind' => 'air'],
    'driver'  => ['label' => 'Fahrer',     'kind' => 'ground'],
    'trainee' => ['label' => 'Praktikant', 'kind' => 'ground'],
    'other'   => ['label' => 'Sonstige',   'kind' => 'both'],
];

/**
 * Faehigkeiten eines Rettungsmittels (E29). Zwei getrennte Haken, weil ein
 * Hubschrauber eine Winde fuehren kann, ohne in einer Bergwachtkooperation zu
 * stehen — und umgekehrt. Sie steuern die zugehoerigen Einsatzfelder allein
 * (`cap_gate` in mission_fields.php); eine zweite Pruefung auf die Betriebsart
 * gibt es dort bewusst nicht.
 *
 * WO SIE VORKOMMEN DUERFEN, sagt seit dem Demo-Ausbau nicht mehr die
 * Betriebsart allein, sondern die Spalte `faehigkeiten` in VEHICLE_TYPEN —
 * ausgewertet von `veh_caps_erlaubt()`. E29 gilt weiter fuer die Typen
 * Standard, Veranstaltung und Sonstiges; der Typ BERGWACHT ist die benannte
 * Ausnahme. Der Grund steht dort.
 */
const VEHICLE_CAPABILITIES = [
    'winch'     => 'Winde',
    'bergwacht' => 'Bergwacht',
];

/**
 * Typen eines Rettungsmittels (E-S9-09, Web 16.0.0).
 *
 * ZWEI ACHSEN, NICHT EINE. `kind` ist die BETRIEBSART — Luft oder Boden — und
 * steuert weiter, was sie immer steuerte: Rollenkatalog, Faehigkeiten,
 * Kachelsatz, Hoehe. `typ` ist die ART DES DIENSTES. Die beiden sind
 * unabhaengig voneinander: Eine Bergwacht fliegt oder faehrt, und beides ist
 * ein Bergwacht-Dienst. Wer statt dessen `kind` um 'bergwacht' erweitert
 * haette, muesste an jeder Stelle, die heute air/ground unterscheidet, raten,
 * welche Betriebsart dahintersteckt.
 *
 * DIESE TABELLE IST DIE EINE QUELLE. Aus ihr ziehen die Pruefschicht
 * (`pruef_rettungsmittel()`), die Formulare, die Sicherung und die Zeichen
 * (`dt_typ_symbole()`). Ein fuenfter Typ wird hier eingetragen und kostet
 * zusaetzlich eine Migration — das ENUM in `vehicles.typ` und `days.vehicle_typ`
 * ist bewusst geschlossen (dieselbe Abwaegung wie bei `users.role`).
 *
 * Die Spalten:
 *   label         Beschriftung in Formular, Liste und Plakette
 *   betriebsart   null = frei waehlbar; sonst der eine erlaubte Wert
 *   rollen        duerfen Rollen-Vorlagen (`vehicle_roles`) hinterlegt werden?
 *   standort      ist `base_id` Pflicht?
 *   faehigkeiten  'luft'  = nur luftgebunden (E29)
 *                 'immer' = in BEIDEN Betriebsarten
 *
 * WARUM FAEHIGKEITEN JETZT EINE SPALTE SIND. Bis zum Demo-Ausbau stand hier,
 * sie brauchten keine: Faehigkeiten kaemen ausschliesslich an luftgebundenen
 * Rettungsmitteln vor, und 'veranstaltung' sei auf Boden festgelegt — beides
 * zusammen ergab die Aussage von selbst. Diese Herleitung traegt nicht mehr.
 * Ein Bergwachtnotarzt FAEHRT zum Einsatz und wird von dort GEFLOGEN; er
 * braucht die Winde, und seine Betriebsart ist Boden. Die Kopplung von Winde
 * und Luft war eine Regel ueber Hubschrauber, nicht ueber Bergwacht.
 *
 * Fuer 'veranstaltung' bleibt die Herleitung stehen und deshalb 'luft': Der Typ
 * ist auf Boden festgelegt, und damit kommt er nie an Faehigkeiten — die Spalte
 * sagt nur, was NICHT schon aus der Betriebsart folgt.
 *
 * Die Reihenfolge im Array ist die Anzeigereihenfolge; 'standard' steht
 * zuerst, weil es die Vorgabe ist.
 */
const VEHICLE_TYPEN = [
    'standard'      => ['label' => 'Standard',      'betriebsart' => null,
                        'rollen' => true,  'standort' => true,
                        'faehigkeiten' => 'luft'],
    'bergwacht'     => ['label' => 'Bergwacht',     'betriebsart' => null,
                        'rollen' => false, 'standort' => false,
                        'faehigkeiten' => 'immer'],
    'veranstaltung' => ['label' => 'Veranstaltung', 'betriebsart' => 'ground',
                        'rollen' => false, 'standort' => false,
                        'faehigkeiten' => 'luft'],
    'sonstiges'     => ['label' => 'Sonstiges',     'betriebsart' => null,
                        'rollen' => false, 'standort' => false,
                        'faehigkeiten' => 'luft'],
];

/**
 * Darf ein Rettungsmittel dieses Typs in dieser Betriebsart Faehigkeiten
 * fuehren?
 *
 * EINE STELLE FUER DREI LESER: die Pruefschicht (`pruef_rettungsmittel()`),
 * der Stammdatendialog und — ueber `VEHICLE_TYPEN` im JSON — dessen Skript.
 * Die Regel selbst ist zwei Zeilen lang; zweimal geschrieben waere sie beim
 * naechsten Typ zweierlei.
 *
 * Ohne gewaehlte Betriebsart ist die Antwort NEIN und nicht „noch nicht
 * entschieden": Was ohne Betriebsart hereinkaeme, liesse sich nicht pruefen.
 */
function veh_caps_erlaubt(?string $typ, ?string $kind): bool
{
    if ($typ === null || !isset(VEHICLE_TYPEN[$typ])) { return false; }
    if (VEHICLE_TYPEN[$typ]['faehigkeiten'] === 'immer') {
        return $kind === 'air' || $kind === 'ground';
    }
    return $kind === 'air';
}

/**
 * Rollen, die zu einer Einsatzart gehoeren, in Katalogreihenfolge.
 *
 * $kind === null (neutraler Diensttag) liefert bewusst eine LEERE Liste: Ein
 * Diensttag ohne Rettungsmittel bietet keine Rollen an (E26). Wer alle Rollen
 * braucht — etwa fuer den Export —, nimmt CREW_ROLES direkt.
 *
 * @return array<string,array{label:string,kind:string}>
 */
function crew_roles_fuer_art(?string $kind): array
{
    if ($kind !== 'air' && $kind !== 'ground') { return []; }
    return array_filter(
        CREW_ROLES,
        static fn(array $r): bool => $r['kind'] === $kind || $r['kind'] === 'both'
    );
}

/** Beschriftung einer Rollenkennung; unbekannte Kennung bleibt sichtbar. */
function crew_role_label(string $code): string
{
    return CREW_ROLES[$code]['label'] ?? $code;
}

/* ---- Kopplungscodes: Alphabet, Laenge, Gueltigkeit -----------------------
 * An EINER Stelle, weil die Angaben an DREI Stellen gebraucht werden: beim
 * Erzeugen (pair.php, Anliegen `start` — seit Web 13.0.0 zeigt das GERAET den
 * Code, R49), beim Eintippen im Web (einstellungen.php, Muster PAIR_RE) und
 * beim Aufraeumen (jobs_lib.php). Frueher standen sie dreimal verschieden im
 * Code — das Pruefmuster liess vier bis acht Zeichen zu und ausdruecklich
 * auch solche, die das Alphabet gar nicht enthaelt.
 *
 * SECHS Zeichen aus 32 sind 30 Bit, also rund 1,07 Milliarden Moeglichkeiten.
 * Die eigentliche Arbeit macht aber der Ratenschutz (ratelimit_lib.php): Ohne
 * ihn war der frühere Coderaum (5 Zeichen, 60 Minuten gueltig, keine Bremse
 * ausser 0,3 s je Anfrage) mit genuegend parallelen Anfragen in gut einer
 * Stunde vollstaendig durchlaufbar. Der Ratenschutz ist deshalb PFLICHT und
 * keine Ergaenzung.
 *
 * Zehn Minuten Gueltigkeit statt sechzig: Die Kopplung geschieht mit der Uhr
 * in der Hand. Sie zeigt den Code, und die Person tippt ihn unmittelbar
 * danach im Web ein — der Ablauf hat sich mit S5 umgedreht, die Naehe ist
 * dieselbe. EINE Frist ab `start` fuer alles (E-S5-12): Eingabe im Web und Ja
 * am Geraet muessen in dieselben zehn Minuten fallen; die Eingabe verlaengert
 * nichts, beide Seiten zeigen die Restzeit.
 */
const PAIR_CHARS   = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // ohne 0/O und 1/I
const PAIR_LEN     = 6;
const PAIR_TTL_MIN = 10;
const PAIR_RE      = '/^[' . PAIR_CHARS . ']{' . PAIR_LEN . '}$/';

/* ---- Ersetzfenster der Geraete (Backlog Nr. 134, K-14, F-SP-8) ------------
 *
 * WOGEGEN. Der Geraeteschluessel liegt auf der Garmin-Uhr im Klartext
 * (`watch/source/Pair.mc`; die Plattform hat nichts Besseres). Lesen kann ein
 * Finder nichts -- `ingest.php` ist POST-only --, aber er kann Einsaetze
 * hochladen und die Phasen BESTEHENDER Einsaetze ersetzen, bis das Geraet im
 * Web getrennt ist.
 *
 * WAS SCHON GESCHUETZT WAR: Einsaetze mit `uhr_gesperrt = 1` uebergeht
 * `ingest.php` ganz (jemand hat sie im Web bearbeitet), und Phasen werden
 * nur ersetzt, wenn der Upload mindestens so viele bringt wie gespeichert
 * sind. Offen blieb der UNBEARBEITETE Einsatz von vor drei Wochen.
 *
 * DIE ZAHL. 72 Stunden ab dem gespeicherten `started_at` des Datensatzes --
 * nicht ab dem gesendeten, den bestimmt der Absender. 48 h waeren knapper,
 * aber ein Freitagsdienst, der erst am Montag synchronisiert, kaeme nicht mehr
 * nach; 7 Tage deckten Urlaub mit Uhr im Koffer und gaeben einem Finder eine
 * ganze Woche. Entschieden am 06.09.2026 (F-SP-8).
 *
 * WAS DANACH GESCHIEHT: `ok` OHNE zu ersetzen. Kein Fehler auf der Uhr -- sie
 * wuerde sonst endlos wiederholen --, aber in der Antwort benannt
 * (`kept_phases`, `kept_resus`, `kept_points`; JSON-Vertrag 5). NEUE Einsaetze
 * werden immer angenommen: Sie sind sichtbar und loeschbar, und sie
 * ueberschreiben nichts.
 */
const INGEST_ERSETZFENSTER_H = 72;

/* ---- JSON in einem <script>-Block (Backlog Nr. 135, K-15) ----------------
 *
 * WAS DAS PROBLEM IST. `json_encode()` maskiert `<` und `>` NICHT. Steht in
 * einem Wert die Zeichenfolge `</script>` -- ein Standortname, ein
 * Fahrzeugkurzname, ein Dateiname aus einem Backup --, endet der Skriptblock
 * mitten in einer Zuweisung, und der Rest der Seite ist kaputt. Ausfuehren
 * laesst sich damit nichts (`/` wird als `\/` maskiert, also entsteht kein
 * schliessendes Tag aus dem Wert selbst), aber eine Seite, die an einem
 * Stammdatennamen zerbricht, ist ein Fehler, und der naechste Baustein waere
 * vielleicht nicht so glimpflich.
 *
 * VIER FLAGGEN, NICHT EINE. `JSON_HEX_TAG` fasst `<` und `>`, `JSON_HEX_AMP`
 * das `&` (Entitaeten in HTML-Kontexten), `JSON_HEX_APOS` und
 * `JSON_HEX_QUOT` die Anfuehrungszeichen -- damit ist dieselbe Zeichenkette
 * auch in einem Attribut sicher, und die Regel muss nicht je Stelle neu
 * bedacht werden.
 *
 * `JSON_UNESCAPED_UNICODE` steht dabei, weil Umlaute in einem UTF-8-Dokument
 * nichts zu maskieren haben; ein Teil der Aufrufer hatte es schon, ein Teil
 * nicht -- jetzt haben es alle.
 *
 * WO ES NICHT HINGEHOERT: in API-Antworten, Dateiformate, Zwischenspeicher
 * und Protokolle. Dort aendern die Flaggen die BYTES, und an Bytes haengen
 * Pruefsummen (`komplett_lib.php` bindet den Dateikopf ueber SHA-256) und
 * Formatvergleiche. Gezaehlt am 07.09.2026: 79 Aufrufe von `json_encode()`
 * unter `server/`, davon 44 in einem `<script>`-Block und 35 ausserhalb.
 */
function json_js($wert, int $mehr = 0): string
{
    return (string)json_encode($wert, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
                                    | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | $mehr);
}

/* ---- Obergrenze offener Kopplungssitzungen (S5, E-S5-14, E-S5-34) --------
 *
 * Seit Web 13.0.0 legt jedes Geraet mit `start` OHNE Anmeldung eine Sitzung
 * an. Damit sich der Coderaum nicht mit fremden Sitzungen fuellen laesst
 * (Angriff E-R49-6: Sitzungen anlegen und auf Vertipper hoffen), haelt der
 * Server hoechstens so viele UNVERFALLENE Sitzungen, wie hier steht — der
 * 1001. `start` bekommt 429 `zu_viele_sitzungen`.
 *
 * ZUR ZAHL: R36 rechnet mit 1000 Konten; mehr gleichzeitige legitime
 * Kopplungen gibt es nicht. Bei 1000 gefuellten Sitzungen trifft ein
 * Rateversuch im Web mit hoechstens 9,3e-7 — und laeuft dann noch in die
 * Rueckbestaetigung am Geraet.
 *
 * GEZAEHLT WIRD PER SQL UEBER erstellt_am, nicht ueber Zeilen: Der Aufraeumjob
 * laeuft taeglich, die Frist ist zehn Minuten. Eine Grenze, die verfallene
 * Zeilen mitzaehlte, liesse sich an einem Tag mit toten Sitzungen fuellen.
 * Und `start` raeumt NICHTS vorab auf — ein Angreifer soll den Server nicht
 * mit jedem Aufruf aufraeumen lassen; das tut der Job.
 */
const PAIR_SITZUNGEN_MAX = 1000;

/**
 * E-Mail-Adresse fuer die Rueckbestaetigung am Geraet maskieren (E-S5-21).
 *
 * `vorname@beispieldomain.de` -> `vo***@beispieldomain.de`; ein lokaler Teil aus einem
 * Zeichen zeigt dieses eine (`a@b.de` -> `a***@b.de`). Kleingeschrieben.
 *
 * DIE DOMAIN BLEIBT VOLL, mit Absicht: Sie laesst die Traegerin ihr Konto
 * erkennen — darum geht es an dieser Stelle (E-R49-4: eigenes Geraet im
 * fremden Konto, die falsche Adresse faellt auf) — und gibt einem Ableser
 * nichts, was er nicht ohnehin weiss: Er hat die Person gerade zur Eingabe
 * bewegt. Die volle Adresse dagegen will R36 nicht auf einer Uhr.
 *
 * Nur fuer den Dialog bestimmt, wird nirgends gespeichert.
 */
function email_maskieren(string $email): string
{
    $email  = mb_strtolower(trim($email));
    $at     = mb_strrpos($email, '@');
    $lokal  = $at === false ? $email : mb_substr($email, 0, $at);
    $domain = $at === false ? ''     : mb_substr($email, $at + 1);
    return mb_substr($lokal, 0, 2) . '***' . ($domain !== '' ? '@' . $domain : '');
}

/* ---- Die drei Rollen (Web 15.0.0, S8/AP1; Rahmenplan R75) ----------------
 *
 * DREI ROLLEN, ZWEI STUFEN VON RECHTEN, EINE HIERARCHIE:
 *
 *   user          dokumentiert eigene Einsaetze
 *   admin         verwaltet Konten, Konto-Backups, Rechtstexte, Demo
 *   betreiberin   dazu der Bereich BETRIEB: Server, Speicher, Updates, Jobs,
 *                 Komplett-Backup, Backup-Ziele
 *
 * BetreiberIn ⊇ Admin ⊇ NutzerIn: Wer betreibt, kann alles, was ein Admin
 * kann. Deshalb liefert ist_admin() (auth_guard.php) auch fuer eine
 * BetreiberIn wahr — es gibt genau EINE Rollenpruefung je Frage, und die
 * Frage "darf verwalten?" hat zwei richtige Antworten.
 *
 * WARUM HIER UND NICHT IN auth_guard.php. Die Wachen dort haengen an der
 * angemeldeten Sitzung. Zwei Stellen brauchen die Frage aber OHNE Sitzung:
 * login.php entscheidet vor dem Anmelden, ob der Wartungsmodus jemanden
 * durchlaesst, und rechtstext_seite.php liest die Rolle einer moeglicherweise
 * fremden Zeile. Beide koennen auth_guard.php nicht laden — es leitet auf die
 * Anmeldung um. Die reinen Praedikate stehen deshalb hier, wo sie jede Datei
 * hat, und auth_guard.php baut seine Wachen darauf.
 *
 * DIE BEZEICHNUNG IST WEIBLICH, weil die Anwendung durchgehend die weibliche
 * Form fuehrt (NutzerIn, BetreiberIn) — der Datenbankwert heisst deshalb
 * 'betreiberin' und nicht 'operator'. Das ist eine Anzeige- und keine
 * Rechteentscheidung: Die Rolle ist geschlechtsneutral gemeint.
 */
const ROLLEN = [
    'user'        => 'NutzerIn',
    'admin'       => 'Admin',
    'betreiberin' => 'BetreiberIn',
];

/** Ist das ein gueltiger Rollenwert? Alles andere wird zu 'user'. */
function rolle_gueltig(string $rolle): bool
{
    return array_key_exists($rolle, ROLLEN);
}

/**
 * Rollenwert aus einer beliebigen Quelle — Formular, Datenbank, Sicherung.
 *
 * DER RUECKFALL IST 'user', UND ZWAR IMMER. Ein unbekannter Wert (ein alter
 * Bestand, ein manipuliertes Formularfeld, eine Sicherung aus einer spaeteren
 * Fassung mit einer vierten Rolle) bekommt die geringsten Rechte, nicht die
 * hoechsten. Das ist die einzige Richtung, in die ein Irrtum billig ist.
 */
function rolle_normieren(?string $rolle): string
{
    $r = (string)$rolle;
    return rolle_gueltig($r) ? $r : 'user';
}

/** Darf diese Rolle verwalten (Konten, Konto-Backups, Texte, Demo)? */
function rolle_darf_verwalten(?string $rolle): bool
{
    $r = rolle_normieren($rolle);
    return $r === 'admin' || $r === 'betreiberin';
}

/** Darf diese Rolle den Bereich Betrieb sehen und bedienen? */
function rolle_ist_betreiberin(?string $rolle): bool
{
    return rolle_normieren($rolle) === 'betreiberin';
}

/** Beschriftung fuer die Anzeige. */
function rolle_text(?string $rolle): string
{
    return ROLLEN[rolle_normieren($rolle)];
}

/**
 * SQL-Bedingung fuer "hat Verwaltungsrechte".
 *
 * Als Konstante, damit die Liste der berechtigten Rollen an einer Stelle
 * steht. Wer sie in einer Abfrage ausschreibt, vergisst beim naechsten
 * Rollenzuwachs (Support-Rolle, R38) genau diese eine.
 */
const ROLLEN_VERWALTUNG_SQL = "role IN ('admin','betreiberin')";

/**
 * Zahl der BetreiberInnen-Konten.
 *
 * Gebraucht fuer die eine Zusage, die das Rollenmodell traegt: Das LETZTE
 * BetreiberIn-Konto laesst sich weder zuruckstufen noch loeschen. Ohne sie
 * koennte sich eine Installation aus ihrem eigenen Betriebsbereich
 * aussperren, und der Rueckweg fuehrte ueber die Datenbank.
 */
function betreiberinnen_zahl(PDO $pdo): int
{
    return (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'betreiberin'")
                    ->fetchColumn();
}

/**
 * Ist dieses Konto die letzte BetreiberIn?
 *
 * WIRD MIT DER ROLLE DES KONTOS GEFRAGT, nicht nur mit seiner Kennung: Ein
 * Konto, das gar keine BetreiberIn ist, ist nie die letzte — und die Abfrage
 * unterbleibt dann ganz.
 */
function ist_letzte_betreiberin(PDO $pdo, int $userId, ?string $rolle): bool
{
    if (!rolle_ist_betreiberin($rolle)) { return false; }
    $q = $pdo->prepare("SELECT COUNT(*) FROM users
                        WHERE role = 'betreiberin' AND id <> ?");
    $q->execute([$userId]);
    return (int)$q->fetchColumn() === 0;
}

/* ---- Fester Vergleichswert fuer unbekannte Kennungen ---------------------
 *
 * An EINER Stelle wird ein Geheimnis gegen einen gespeicherten bcrypt-Hash
 * geprueft: die Anmeldung (login.php, Auth-Token). Bis Web 12.9.4 auch an
 * den Geraetepfaden (ingest.php, pair.php) — seit 13.0.0 vergleichen die
 * gegen SHA-256 und haben ihren eigenen Vergleichswert (unten). Die Pruefung
 * lief frueher nur, WENN es die Kennung gab. Bei unbekannter Kennung kam die
 * Abweisung sofort — und dieser Zeitunterschied beantwortet dieselbe Frage
 * wie eine unterschiedliche Meldung: Gibt es dieses Konto?
 *
 * Deshalb laeuft auch der unbekannte Zweig gegen diesen Wert. Er ist kein
 * Geheimnis — er darf offen im Code stehen, weil zu ihm kein Passwort
 * gehoert, das jemand einsetzen koennte. Seine einzige Aufgabe ist, denselben
 * Rechenaufwand zu erzeugen.
 *
 * ZUR RUNDENZAHL ($2y$12$, seit Web 19.1.2 — Backlog Nr. 93): Sie entspricht
 * der, mit der PASSWORD_DEFAULT auf PHP 8.4 arbeitet, also der Rundenzahl der
 * hier neu angelegten Hashes.
 *
 * WARUM DIE ZAHL ALLEIN NICHT REICHT. Sie stand von Web 5 bis 19.1.1 auf 10
 * (PHP 8.1 bis 8.3) und fiel mit PHP 8.4 aus dem Takt — gemessen am
 * 12.09.2026: 231,3 ms fuer den bekannten Zweig gegen 57,9 ms fuer diesen
 * Wert, also Faktor 3,99. Verdeckt hat das nur die Mindestdauer von 0,35 s in
 * rate_gleiche_dauer(); auf einem langsameren Rechner kippt sie. Eine feste
 * Zahl geht beim naechsten Vorgabesprung wieder aus dem Takt, und auf einer
 * Installation mit PHP 8.1 bis 8.3 (Technik.md 11 verspricht >= 8.1) kehrt
 * sie das Leck sogar um: blind 231 ms gegen echt 58 ms, derselbe Abstand in
 * der anderen Richtung.
 *
 * DESHALB PRUEFT login.php den Wert, statt ihm zu vertrauen:
 * password_needs_rehash() sagt fuer 0,0000 ms (gemessen ueber 2000 Laeufe),
 * ob die Konstante noch zur Vorgabe passt; passt sie nicht, rechnet der
 * blinde Zweig ein password_hash() mit PASSWORD_DEFAULT und kostet damit
 * genau so viel wie der bekannte. Der Normalfall bleibt der billige
 * Vergleich.
 *
 * WAS NICHT GEHT, und warum es hier steht: BEIDES zu tun — beim Start hashen
 * UND danach pruefen — macht den blinden Zweig um 234 ms LANGSAMER als den
 * bekannten (gemessen 465,5 gegen 231,3 ms) und sprengt dazu die
 * Mindestdauer. Wer hier „sicherheitshalber" etwas hinzufuegt, dreht das
 * Leck um.
 */
const AUTH_VERGLEICHSWERT = '$2y$12$Q6vjbOl.EIszd6TOEs39Kexy6uGWwrczpmFVKXpNXU7HXBNWNndSW';

/* ---- Geraeteschluessel: SHA-256 statt bcrypt (Web 13.0.0, S5 E-S5-42) ----
 *
 * WELCHES VERFAHREN ZU WELCHEM GEHEIMNIS GEHOERT (E-S5-41):
 *
 *   aus einem Passwort abgeleitet  -> bcrypt (password_hash). Die Langsamkeit
 *       ist der Schutz, denn die Entropie bleibt die des Passworts — auch nach
 *       PBKDF2 im Browser. Das ist das Anmeldetoken (users.password_hash).
 *   Zufall mit mindestens 128 Bit  -> SHA-256 + hash_equals. Langsamkeit kauft
 *       hier nichts: Den Hash eines 192-Bit-Zufallswerts kehrt niemand um, und
 *       bcrypt kostete an diesem Pfad 228 ms JE UPLOAD (PHP 8.4, Kostenfaktor
 *       12) — fuer eine Bremse, die nichts bremst. Das sind der
 *       Geraeteschluessel (devices.api_key_hash) und der Sitzungsschluessel
 *       der Kopplung (pair_sessions.api_key_hash). Dasselbe Muster fuehren die
 *       Reset-Token (password_resets.token_hash) seit jeher.
 *   muss der Server es zurueckLESEN -> AES-256-GCM mit dem Serverschluessel
 *       (serverkrypto_lib.php): die Zugangsdaten der Backup-Ziele.
 *
 * KEIN BESTANDSSCHUTZ, mit Absicht: Ab 1.0 gibt es genau eine, frisch
 * installierte Installation (R60). Ein bcrypt-Hash in devices passt seit
 * 13.0.0 nie mehr — das eine Bestandsgeraet koppelt einmal neu. Ein
 * Umhash-Pfad ("beim naechsten Upload") waere Code fuer einen Fall, den es
 * nicht geben soll.
 *
 * DER VERGLEICHSWERT hat dieselbe Aufgabe wie AUTH_VERGLEICHSWERT: Der Zweig
 * "Kennung unbekannt" rechnet dieselben Schritte wie der Zweig "Kennung
 * bekannt", damit die Dauer nichts verraet. Bei SHA-256 sind das
 * Mikrosekunden — die Gleichheit ist trotzdem eine Eigenschaft, die man
 * nicht dem Zufall ueberlaesst. Der Wert ist sha256('edgeraet|vergleichswert|
 * kein Geraet') und kein Geheimnis: Zu ihm gehoert kein Schluessel.
 */
const GERAET_VERGLEICHSWERT = '25cd312037a32e762f924764a9cc97524c7d18c257a6575704421f8a208ee616';

/** Der gespeicherte Wert zu einem Geraete- oder Sitzungsschluessel. */
function geraet_schluessel_hash(string $schluessel): string
{
    return hash('sha256', $schluessel);
}

/**
 * Passt der Schluessel zum gespeicherten Wert? Vergleich in konstanter Zeit.
 * Ein bcrypt-Wert aus der Zeit vor 13.0.0 passt nie (andere Laenge) — siehe
 * oben, das ist gewollt.
 */
function geraet_schluessel_gueltig(string $schluessel, string $hash): bool
{
    return hash_equals($hash, hash('sha256', $schluessel));
}

/* ---- Rundenzahl der Schluesselableitung (M2-01, S1) ----------------------
 *
 * WAS DIESE ZAHL IST
 * Der Browser leitet aus dem Passwort per PBKDF2-SHA256 mit dieser Rundenzahl
 * zwei Schluessel ab: den Datenschluessel (bleibt im Browser) und das
 * Auth-Token (ersetzt das Passwort zum Server). Wer die Zahl anhebt, macht
 * das Durchprobieren gestohlener Hashes teurer — und das Anmelden langsamer.
 *
 * WARUM SIE JE KONTO GESPEICHERT WIRD (users.kdf_iter)
 * Stuende sie nur als Konstante im Browser, waere ihre Aenderung eine
 * Aussperrung aller Bestandskonten: Aus demselben Passwort entstuende ein
 * anderes Token, und der gespeicherte Hash passte nicht mehr. Der Wert steht
 * deshalb an der Nutzerzeile und wird gelesen, nicht angenommen.
 *
 * ---- WARUM EINE LISTE UND NICHT EIN WERT --------------------------------
 *
 * Der Salz-Endpunkt (auth_salt.php) ist ohne Anmeldung erreichbar und muss
 * fuer unbekannte Adressen genauso antworten wie fuer echte Konten. Nennte er
 * die Rundenzahl DES KONTOS, waere waehrend der Umstellung jede Adresse, die
 * den alten Wert zurueckliefert, nachweislich ein echtes, seither nicht
 * benutztes Konto — die Auskunftsluecke, die derselbe Endpunkt gerade
 * geschlossen hat, an neuer Stelle.
 *
 * Er nennt deshalb JEDER Adresse dieselbe Liste. Der Browser leitet fuer
 * jeden Eintrag ab und schickt alle Token; der Server nimmt das, das zur
 * gespeicherten Rundenzahl gehoert. Die Antwort ist damit fuer alle Adressen
 * buchstaeblich identisch.
 *
 * DER PREIS: Solange die Liste zwei Eintraege hat, rechnet jede Anmeldung
 * zweimal ab — aus knapp einer Sekunde werden knapp zwei. Das ist der
 * Uebergangszustand, nicht der Dauerzustand.
 *
 * ---- !!! BEIM ANHEBEN DES ZIELWERTS ZU TUN !!! ---------------------------
 *
 * WER KDF_ITER_ZIEL AENDERT, MUSS DEN BISHERIGEN WERT IN KDF_ITER_LISTE
 * STEHEN LASSEN. Beispiel fuer einen Sprung auf 600000:
 *
 *     const KDF_ITER_ZIEL  = 600000;
 *     const KDF_ITER_LISTE = [600000, 320000];
 *
 * Wird das vergessen, kann sich KEIN Bestandskonto mehr anmelden: Der Browser
 * leitet dann nur noch fuer den neuen Wert ab, und das dabei entstehende
 * Token passt zu keinem gespeicherten Hash. Die Meldung lautet "Anmeldung
 * fehlgeschlagen", und die Ursache steht nirgends.
 *
 * Die Wartungsseite prueft genau das und meldet es (update.php, Betriebslage).
 *
 * ---- WANN EIN WERT AUS DER LISTE VERSCHWINDEN DARF -----------------------
 *
 * ERST, WENN KEIN KONTO IHN MEHR TRAEGT:
 *
 *     SELECT COUNT(*) FROM users WHERE kdf_iter = <alter Wert>;
 *
 * Ist das Ergebnis nicht 0, sperrt das Entfernen genau diese Konten aus, und
 * zwar unwiderruflich fuer die geschuetzten Angaben — ihre Schluesselhuelle
 * laesst sich ohne die richtige Rundenzahl nicht mehr oeffnen. Es besteht
 * keine Eile: Ein zusaetzlicher Eintrag kostet nur Rechenzeit.
 *
 * 310000 ist am 14.08.2026 entfallen, nachdem die Abfrage 0 ergab.
 *
 * 600000 IST DER ZIELWERT SEIT DEM 07.09.2026 (Backlog Nr. 136, SP-1). 320000
 * lag unter der Empfehlung (OWASP 2023, Bitwarden); gemessen kostet der Sprung
 * je Ableitung rund das Doppelte und halbiert die Rate des Angreifers.
 *
 * 320000 IST AM 12.09.2026 ENTFALLEN (Backlog Nr. 155), nach derselben
 * Abfrage wie 310000 am 14.08.2026: `SELECT COUNT(*) FROM users WHERE
 * kdf_iter = 320000` ergab **0**. Der Weg dahin war nicht die Abfrage
 * allein, sondern die Demo-Fixture: Sie brachte die alte Rundenzahl mit,
 * der Reset schrieb sie alle 30 Minuten zurueck, und die stille Anhebung
 * ueberspringt das Demo-Konto ausdruecklich (E-P1-19). Der Altwert konnte
 * deshalb NIE von selbst verschwinden. Erst der Neubau des
 * Referenzbestands mit KDF_ITER_ZIEL hat das aufgeloest; zwei Riegel
 * halten es fest (fixture/erzeugen.php und demo_fixture_laden()).
 *
 * DAMIT RECHNET JEDE ANMELDUNG WIEDER NUR EINMAL AB — das war der Preis
 * des Uebergangs, nicht sein Ziel.
 *
 * REIHENFOLGE: Der Zielwert steht VORNE. Der Browser probiert nicht der Reihe
 * nach (er schickt alle Token), aber die Reihenfolge ist die Lesart.
 */
const KDF_ITER_ZIEL  = 600000;
const KDF_ITER_LISTE = [600000];

/* ---- Mindestlaenge des Passworts ----------------------------------------
 *
 * DIE ZAHL, DIE DER SERVER NICHT DURCHSETZEN KANN. Er sieht das Passwort nie
 * (er bekommt nur das abgeleitete Token), also ist sie hier eine ANGABE fuer
 * die Formulare — `minlength` und die Zeile unter dem Feld — und nicht die
 * Pruefung. Die Pruefung steht in `assets/pwquality.js` (`MIN_LAENGE`), und
 * beide Zahlen muessen dieselbe sein.
 *
 * ZWEI STELLEN, WEIL PWQUALITY.JS EINE STATISCHE DATEI IST. Sie wird ueber
 * `<script src>` geladen, bevor ui_krypto_bootstrap() seine Konstanten
 * ausgibt — sie kann diese Zahl also nicht von hier lesen. Gegen das
 * Auseinanderlaufen steht deshalb `EdPwQuality.beobachte()`: Es setzt
 * `minLength` des Feldes beim Anhaengen auf seinen eigenen Wert. Wer hier
 * etwas anderes hinschreibt als dort, bekommt eine falsche BESCHRIFTUNG,
 * aber keine schwaechere Pruefung.
 *
 * 12 statt 10 seit dem Sofortpaket Sicherheit (Backlog Nr. 136, SP-2): Gegen
 * einen Datenbankabzug ist das Passwort die einzige Schranke, und zwei
 * Zeichen mehr sind dort mehr wert als jede Zeichenartenregel.
 */
const PW_MIN_LAENGE = 12;

/* ---- Geraete je Konto: Obergrenze und Hinweisfenster ---------------------
 *
 * WARUM ES EINE OBERGRENZE GIBT
 * Ein Geraet ist ein Satz Zugangsdaten, mit dem sich Einsaetze in ein Konto
 * schreiben lassen. Ohne Obergrenze konnte ein Konto beliebig viele davon
 * ansammeln, und niemand haette es bemerkt: Ein eingeschleustes Geraet steht
 * neben den echten unauffaellig in der Liste. Die Grenze macht aus "faellt
 * niemandem auf" ein "geht nicht mehr, ohne dass jemand aufraeumt".
 *
 * (Bis Web 12.9.4 stand hier "wer einen Kopplungscode abfaengt, legt sich ein
 * Geraet an". Das trifft seit S5 nicht mehr: Der Code weist nichts aus, wer
 * ihn abliest, kann am Geraet nichts ausloesen — E-S5-03. Der Weg hinein ist
 * heute die Ueberredung, nicht das Abfangen; die Obergrenze wirkt gegen beide
 * gleich.)
 *
 * WAS GEZAEHLT WIRD
 * Alle Geraete eines Kontos, AKTIVE WIE DEAKTIVIERTE — ein deaktiviertes
 * Geraet ist ein weiterhin vorhandener Zugangsdatensatz, der sich mit einem
 * Klick wieder scharf schalten laesst. Loeschen gibt einen Platz frei,
 * Deaktivieren nicht.
 *
 * WAS NICHT GEZAEHLT WIRD
 * Das virtuelle Geraet "Manuelle Einträge" (device_id 'manual-<konto>'). Es
 * entsteht von selbst, sobald jemand einen Einsatz von Hand anlegt oder
 * importiert, ist dauerhaft deaktiviert und kann nie hochladen. Es taucht
 * schon in der Geraeteliste nicht auf (derselbe Filter) und darf deshalb auch
 * keinen Platz kosten — sonst haetten die Grenze und die angezeigte Liste
 * verschiedene Zahlen, und wer fuenf Geraete sieht, verstuende nicht, warum
 * das sechste abgewiesen wird.
 *
 * ZUR ZAHL FUENF: Im Betrieb traegt eine Person eine Uhr. Fuenf lassen Raum
 * fuer eine Ersatzuhr, ein Testgeraet und ein noch nicht geloeschtes Altgeraet
 * und sind trotzdem eine Zahl, bei der ein zusaetzlicher Eintrag auffaellt.
 */
/* ---------------------------------------------------------------------------
 * DIE BEIDEN STORE-ADRESSEN (S8/AP6, R65)
 *
 * Sie stehen an genau EINER Stelle, und beide sind leer: Weder der
 * Beitrittslink des internen Play-Tests noch die Adresse der Uhr-App im
 * Connect-IQ-Store liegen vor (Rahmenplan Abschnitt 6, Stand 06.09.2026).
 *
 * SOLANGE EINE LEER IST, steht ihre Zeile auf der Geraeteseite ohne Knopf da —
 * mit dem Weg als Text. Ein Knopf ins Leere waere schlechter als keiner.
 *
 * Sie stehen hier und nicht in der `config.php`: Es sind keine Einstellungen
 * dieser Installation, sondern Adressen des Programms — jede Installation
 * verweist auf denselben Store-Eintrag. Mit der Produktionsfreigabe tritt bei
 * PLAY_TEST_URL die Store-Adresse an die Stelle des Testlinks.
 */
const CONNECT_IQ_URL = '';
const PLAY_TEST_URL  = '';

const MAX_GERAETE      = 5;
const GERAETE_NEU_TAGE = 7;   // so lange gilt ein Geraet in der Oberflaeche als "neu"

/** Bedingung, die das virtuelle Geraet "Manuelle Einträge" ausschliesst. */
const GERAETE_ECHT_SQL = "device_id NOT LIKE 'manual-%'";

/**
 * Dasselbe fuer PHP: Ist das die Kennung eines virtuellen Geraets?
 *
 * ZWEI FASSUNGEN DERSELBEN REGEL, und das ist keine Nachlaessigkeit: Die eine
 * filtert in SQL, die andere eine Liste im Speicher (demo_lib.php beim
 * Einspielen der Fixture, fixture/erzeugen.php beim Erzeugen). Sie stehen
 * deshalb nebeneinander — wer die eine aendert, sieht die andere.
 */
function geraet_virtuell(string $deviceId): bool
{
    return str_starts_with($deviceId, 'manual-');
}

/* ---- DAS VIRTUELLE GERAET AN EINER STELLE (Schritt 15/AP4, E-ZE-18) ------
 *
 * „Manuelle Einträge" ist ein deaktiviertes Geraet je Konto, an dem alles
 * haengt, was nicht von einer Uhr kommt: Einsaetze aus dem Formular, aus dem
 * Import, aus dem Schneiden und aus dem GPX-Einlesen. Der Block „gibt es das
 * Geraet schon? sonst anlegen" stand bis Web 20.28.0 VIERMAL — zweimal als
 * eigene Funktion (`schnitt_geraet()`, `gpx_import_geraet()`) und zweimal
 * eingebettet (`api/import_commit.php`, `einsatz_form.php`), jedes Mal mit
 * demselben zwanzigzeiligen Kommentar darueber.
 *
 * Und die Kennung selbst — `'manual-' . $userId` — stand an sieben Stellen,
 * teils als Praefix beim Zusammenbauen, teils als `LIKE 'manual-%'` in einer
 * Abfrage. Eine davon band das Muster als Parameter statt es einzusetzen
 * (`betrieb_statistik.php`), zwei brauchten einen Tabellenalias (`d.`), den
 * die Konstante `GERAETE_ECHT_SQL` nicht traegt. Deshalb drei Formen, nicht
 * eine: die Funktion fuer die Kennung, `geraete_echt_sql($alias)` fuer die
 * Bedingung und `GERAET_VIRTUELL_MUSTER` fuer die Stelle, die bindet.
 */

/** Das `LIKE`-Muster der virtuellen Geraete — fuer Abfragen, die es binden. */
const GERAET_VIRTUELL_MUSTER = 'manual-%';

/** Die Geraetekennung des virtuellen Geraets dieses Kontos. */
function geraet_virtuell_kennung(int $userId): string
{
    return 'manual-' . $userId;
}

/**
 * Bedingung „nur echte Geraete" — mit Tabellenalias, wenn einer gebraucht wird.
 *
 * `GERAETE_ECHT_SQL` bleibt als Konstante bestehen (sie steht in zwei
 * Abfragen ohne Alias und ist dort gut lesbar); diese Funktion ist fuer die
 * Abfragen mit Verbund, wo `device_id` mehrdeutig waere.
 */
function geraete_echt_sql(string $alias = ''): string
{
    $p = $alias === '' ? '' : rtrim($alias, '.') . '.';
    return $p . "device_id NOT LIKE '" . GERAET_VIRTUELL_MUSTER . "'";
}

/**
 * Das virtuelle Geraet dieses Kontos holen — und anlegen, wenn es fehlt.
 *
 * DIE NUTZERKENNUNG GEHOERT IN DIE ABFRAGE (M3-12/M6-09), und dieser Satz
 * stand bisher vier Mal fast wortgleich daneben: Gesucht wurde einmal allein
 * ueber `device_id`. Dass `manual-<id>` die Zugehoerigkeit im Namen traegt,
 * machte die Abfrage praktisch richtig — aber nur, weil eine Zeichenkette
 * zufaellig dasselbe aussagt wie eine Spalte. Steht die Bedingung nicht in
 * der Abfrage, gibt es auch nichts, was sie durchsetzt: ein spaeter
 * geaendertes Namensschema, ein Tippfehler beim Zusammenbauen des
 * Schluessels — und der Einsatz staende am Geraet einer fremden Person.
 *
 * `active = 0`: Das Geraet kann nie hochladen. Es traegt trotzdem einen
 * Schluesselhash, weil die Spalte ihn verlangt; er wird nie geprueft.
 *
 * NIMMT EIN `PDO`, weil drei der vier Aufrufer innerhalb einer Transaktion
 * stehen und die vierte Stelle ihr `$pdo` ohnehin zur Hand hat.
 */
function geraet_virtuell_sicherstellen(PDO $pdo, int $userId): int
{
    $devKey = geraet_virtuell_kennung($userId);
    $q = $pdo->prepare('SELECT id FROM devices WHERE device_id = ? AND user_id = ?');
    $q->execute([$devKey, $userId]);
    $devId = $q->fetchColumn();
    if ($devId !== false) { return (int)$devId; }

    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
                   VALUES (?,?,?,?,0)')
        ->execute([$userId, $devKey,
                   geraet_schluessel_hash(bin2hex(random_bytes(24))),
                   'Manuelle Einträge']);
    return (int)$pdo->lastInsertId();
}

/** Zahl der echten Geraete eines Kontos (aktive und deaktivierte). */
function geraete_zahl(PDO $pdo, int $userId): int {
    $st = $pdo->prepare('SELECT COUNT(*) FROM devices
                         WHERE user_id = ? AND ' . GERAETE_ECHT_SQL);
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

/** True, wenn kein weiteres Geraet mehr angelegt werden darf. */
function geraete_grenze_erreicht(PDO $pdo, int $userId): bool {
    return geraete_zahl($pdo, $userId) >= MAX_GERAETE;
}

/**
 * Geraete, die in den letzten GERAETE_NEU_TAGE Tagen hinzugekommen sind.
 *
 * Grundlage des Hinweises in der Oberflaeche (M4-10). Die eigentliche
 * Benachrichtigung ist die E-Mail beim Koppeln — sie erreicht die Person auch
 * dann, wenn sie sich gerade nicht anmeldet, und genau das ist der Fall, um
 * den es geht. Der Hinweis hier ist die zweite, langsamere Spur fuer alle, die
 * ihre Post nicht lesen.
 *
 * @return array<int, array{device_id: string, label: ?string, created_at: string}>
 */
/**
 * Neu hinzugekommene Geraete fuer den Hinweis auf der Startseite.
 *
 * BERUECKSICHTIGT DIE BESTAETIGUNG DES HINWEISES.
 * Der Hinweis stand sonst sieben Tage lang auf jeder Seite und liess sich
 * nicht wegklicken — auch dann nicht, wenn man ihn gelesen und die Kopplung
 * als richtig erkannt hatte. Eine Warnung, die man nicht loswird, wird nach
 * dem dritten Mal nicht mehr gelesen; genau dann steht sie da, wenn sie
 * einmal wirklich gemeint ist.
 *
 * Bestaetigt wird je Zeitpunkt, nicht je Geraet: Wer bestaetigt, sagt "alles
 * bis hierher kenne ich". Ein danach gekoppeltes Geraet erzeugt den Hinweis
 * erneut.
 *
 * Das Kennzeichen "neu" in der Geraeteliste bleibt davon UNBERUEHRT — dort
 * ist es keine Warnung, sondern eine Angabe.
 */
function geraete_neu(PDO $pdo, int $userId): array {
    $seit = geraete_hinweis_stand($pdo, $userId);
    /* Art, Modell und Rohangabe seit Web 12.9.0 mit (S6/R42): Der Hinweis
     * stellt die Frage „war ich das?", und „Uhr · Venu 3S" beantwortet sie
     * besser als „Uhr". Die Angabe kostet nichts — die Zeile wird ohnehin
     * gelesen. */
    $st = $pdo->prepare('SELECT device_id, label, created_at,
                                geraet_art, geraet_modell, geraet_teil
                         FROM devices
                         WHERE user_id = ? AND ' . GERAETE_ECHT_SQL . '
                           AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)
                           AND (? IS NULL OR created_at > ?)
                         ORDER BY created_at DESC');
    $st->execute([$userId, GERAETE_NEU_TAGE, $seit, $seit]);
    return $st->fetchAll();
}

/* ---------------------------------------------------------------------------
 * `app_state` — der kleine Schluessel/Wert-Speicher, jetzt mit EINEM Zugang
 * ---------------------------------------------------------------------------
 *
 * WARUM HIER. Die Tabelle wird an acht Stellen gelesen und geschrieben, und
 * bis Web 20.6.0 brachte JEDE ihren eigenen Zugriff mit: `edbak_marke_lesen()`
 * (`adminbackup_lib.php`), `schluessel_marke_lesen()` (`serverkrypto_lib.php`),
 * `geocoder_state()` (`geocoder_lib.php`), `_tor_lesen()`
 * (`migration_lib.php`), dazu blankes SQL in `smtp.php`, `auth_salt.php`,
 * `jobs.php` und `komplett_lib.php`. Fuenf Fassungen derselben zwei Zeilen,
 * und jede mit ihrer eigenen Antwort auf die Frage, was passiert, wenn die
 * Tabelle fehlt.
 *
 * DAS IST KEIN AUFRAEUMPAKET. Diese beiden Funktionen sind der Ort, an dem
 * die anderen zusammenlaufen KOENNEN; zusammengefuehrt sind sie nicht (das
 * waere eine Aenderung an fuenf Bibliotheken fuer einen Gewinn, den niemand
 * sieht — Backlog). Neue Verbraucher nehmen diese hier.
 *
 * `v` IST `VARCHAR(190)`. Wer mehr schreibt, bekommt `false` und eine Zeile
 * im Fehlerprotokoll — nicht einen stillen Abschnitt.
 */

/** Maximale Laenge eines Werts in `app_state` (Spaltenbreite). */
const APP_STATE_MAX = 190;

/** Eine Zeile lesen. `null`, wenn es sie — oder die Tabelle — nicht gibt. */
function app_state_lesen(string $k): ?string {
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([$k]);
        $v = $st->fetchColumn();
        return $v === false || $v === null ? null : (string)$v;
    } catch (Throwable $ex) {
        return null;   // Tabelle fehlt (Migration noch nicht gelaufen)
    }
}

/**
 * Passt der Wert in die Spalte? Sonst `true` und eine Zeile im Protokoll.
 *
 * EINE STELLE FUER DIE PRUEFUNG UND IHREN SATZ (Schritt 15/AP4). Sie stand
 * dreifach, sobald es drei schreibende Helfer gab — und ein viertes Mal in
 * `edbak_marke_setzen()` mit einer eigenen Konstante derselben Zahl.
 *
 * WARUM SIE UEBERHAUPT IN PHP STEHT und nicht nur im Schema: Je nach
 * Serverbetriebsart kuerzt MySQL zu lange Werte STILL statt abzuweisen. Eine
 * stille Kuerzung ist hier das Schlimmste von allem — ein halbes JSON, das
 * beim naechsten Lesen als „kein Auftrag" durchgeht. Genau das ist einmal
 * passiert (S2/AP6): Die Warteschlange von „Alle sichern" war laenger als
 * 190 Zeichen, niemand erfuhr davon, und die Schaltflaeche meldete „0 von 0
 * Konten gesichert".
 */
function app_state_zu_lang(string $k, string $v): bool {
    if (strlen($v) <= APP_STATE_MAX) { return false; }
    system_melden('app_state', '„' . $k . '" ist ' . strlen($v) . ' Zeichen lang, '
                . 'erlaubt sind ' . APP_STATE_MAX . '.');
    return true;
}

/** Eine Zeile schreiben. `false` = zu lang oder nicht schreibbar, mit Log. */
function app_state_setzen(string $k, string $v): bool {
    if (app_state_zu_lang($k, $v)) { return false; }
    try {
        db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
        return true;
    } catch (Throwable $ex) {
        system_melden('app_state', '„' . $k . '" ließ sich nicht schreiben', $ex);
        return false;
    }
}

/* ---- VIER WEITERE HELFER (Schritt 15/AP4, E-ZE-17) -----------------------
 *
 * Der Absatz oben sagte bis Web 20.28.0: „Zusammengefuehrt sind sie nicht —
 * das waere eine Aenderung an fuenf Bibliotheken fuer einen Gewinn, den
 * niemand sieht." Gemessen waren es dann **27 Stellen in 17 Dateien**, und
 * der Gewinn ist sichtbar geworden: Jede dieser Stellen beantwortete die
 * Frage „was, wenn die Tabelle fehlt?" fuer sich, und sie beantworteten sie
 * verschieden — mal `try/catch` mit `null`, mal ohne, mal mit einem
 * `error_log`. Eine fehlende `app_state`-Tabelle (Migration noch nicht
 * gelaufen) ist kein seltener Zustand: Sie ist der Zustand JEDER Anlage
 * zwischen Deploy und `update.php`.
 *
 * DIE WRAPPER BLEIBEN. `edbak_marke_*`, `geocoder_state*`,
 * `schluessel_marke_*`, `geraete_hinweis_*`, `demo_*`, `jobs_*`, `logo_*`
 * behalten Namen und Signatur — sie tragen Bedeutung und teils einen eigenen
 * Merker; nur ihr Rumpf ruft ab hier diese Helfer.
 */

/**
 * Mehrere Zeilen auf einmal lesen.
 *
 * EINE ABFRAGE STATT N, und der Grund steht in `nb_moeglich()` nebenan:
 * Vier Einzelabfragen kosteten dort 1,071 ms, eine gemeinsame 0,355 ms.
 * Fehlende Schluessel fehlen auch im Ergebnis — wer eine Vorgabe braucht,
 * nimmt `$aus[$k] ?? ...`.
 *
 * @param list<string> $k
 * @return array<string,string> nur die gefundenen
 */
function app_state_mehrere(array $k): array {
    if ($k === []) { return []; }
    try {
        $platz = implode(',', array_fill(0, count($k), '?'));
        $st = db()->prepare("SELECT k, v FROM app_state WHERE k IN ($platz)");
        $st->execute(array_values($k));
        $aus = [];
        foreach ($st->fetchAll(PDO::FETCH_NUM) as $r) {
            $aus[(string)$r[0]] = (string)$r[1];
        }
        return $aus;
    } catch (Throwable $ex) {
        return [];   // Tabelle fehlt (Migration noch nicht gelaufen)
    }
}

/**
 * Mehrere Zeilen auf einmal schreiben.
 *
 * ALLES ODER NICHTS GIBT ES HIER NICHT, und das ist Absicht: Diese Funktion
 * laeuft teils INNERHALB einer fremden Transaktion (`demo_anlegen()`), und
 * eine eigene aufzumachen braeuchte dort eine verschachtelte — die gibt es
 * nicht. Ein zu langer Wert bricht deshalb VOR dem ersten Schreiben ab; was
 * danach schiefgeht, ist ein Datenbankfehler und kein Laengenfehler.
 *
 * @param array<string,string> $kv
 */
function app_state_setzen_mehrere(array $kv): bool {
    if ($kv === []) { return true; }
    foreach ($kv as $k => $v) {
        if (app_state_zu_lang((string)$k, $v)) { return false; }
    }
    try {
        $st = db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE v = VALUES(v)');
        foreach ($kv as $k => $v) { $st->execute([(string)$k, $v]); }
        return true;
    } catch (Throwable $ex) {
        system_melden('app_state', 'Mehrfachschreiben fehlgeschlagen', $ex);
        return false;
    }
}

/**
 * Zeilen loeschen.
 *
 * KEIN RUECKGABEWERT, und keiner wird gebraucht: Die drei Aufrufer loeschen
 * Reste (ein geloeschtes Konto, eine aufgehobene Jobpause). Ob die Zeile da
 * war, aendert nichts an dem, was danach geschieht. Eine fehlende Tabelle
 * ist derselbe Fall.
 */
function app_state_loeschen(string ...$k): void {
    if ($k === []) { return; }
    try {
        $platz = implode(',', array_fill(0, count($k), '?'));
        db()->prepare("DELETE FROM app_state WHERE k IN ($platz)")->execute($k);
    } catch (Throwable $ex) {
        system_melden('app_state', 'Löschen von „' . implode('", „', $k)
                    . '" fehlgeschlagen', $ex);
    }
}

/**
 * Einen Wert einmalig erzeugen und behalten — atomar.
 *
 * FUER DIE BEIDEN SERVERGEHEIMNISSE (`salt_secret` in `auth_salt.php`,
 * `reg_secret` in `registrieren.php`). Beide standen vorher als „lesen, und
 * wenn leer, erzeugen und schreiben" da, und beide benutzten dafuer schon
 * `INSERT IGNORE` — das ist der springende Punkt und der Grund, warum diese
 * Funktion NICHT `app_state_setzen()` ruft: Zwei gleichzeitige Anfragen
 * erzeugen beide einen Wert, aber nur EINER darf gewinnen. Mit
 * `ON DUPLICATE KEY UPDATE` gewaenne der letzte, und die Pseudo-Salts
 * aenderten sich unter der Hand. `INSERT IGNORE` laesst den ersten stehen;
 * danach wird zurueckgelesen, damit alle denselben sehen.
 *
 * ---- SIE FAENGT NICHTS, UND DAS IST DER UNTERSCHIED ZU DEN NACHBARN ------
 *
 * `app_state_lesen()`, `-setzen()`, `-loeschen()` und `-mehrere()` fangen
 * eine fehlende Tabelle ab und liefern einen brauchbaren Ersatz. Hier waere
 * das falsch: KEINER der beiden Aufrufer hatte je einen `try/catch`. Fehlt
 * `app_state` (Migration noch nicht gelaufen), brach die Anfrage ab — und
 * `auth_salt.php` gehoert zum GERAETEVERTRAG, seine Antwortform ist
 * zeichengleich zu halten (Schritt 15, Abschnitt 0). Ein stillschweigend
 * erzeugtes, NICHT gespeichertes Geheimnis waere je Anfrage ein anderes:
 * Die Pseudo-Salts einer unbekannten Adresse waeren nicht mehr stabil, und
 * genau ihre Stabilitaet ist ihr Zweck. Lieber ein Abbruch als eine Antwort,
 * die aussieht wie eine richtige.
 *
 * Deshalb liest sie auch selbst und nicht ueber `app_state_lesen()`: Das
 * wuerde den Fehler an der ersten Stelle schlucken.
 *
 * Der Erzeuger laeuft nur, wenn nichts dasteht. Er darf teuer sein.
 */
function app_state_einmalig(string $k, callable $erzeuger): string {
    $pdo = db();
    $st  = $pdo->prepare('SELECT v FROM app_state WHERE k = ?');
    $st->execute([$k]);
    $v = $st->fetchColumn();
    if ($v !== false && $v !== null && (string)$v !== '') { return (string)$v; }

    $neu = (string)$erzeuger();
    /* Zu lang: Der Aufrufer bekommt trotzdem einen brauchbaren Wert — er soll
     * nicht ohne Geheimnis dastehen —, die Zeile fehlt dann aber. */
    if (app_state_zu_lang($k, $neu)) { return $neu; }

    $pdo->prepare('INSERT IGNORE INTO app_state (k, v) VALUES (?, ?)')
        ->execute([$k, $neu]);

    /* ZURUECKLESEN, NICHT $neu ZURUECKGEBEN: Wenn zwischen Lesen und
     * Schreiben jemand anderes schneller war, hat `INSERT IGNORE` nichts
     * getan — und $neu waere ein Wert, den sonst niemand kennt. */
    $st->execute([$k]);
    $w = $st->fetchColumn();
    return ($w === false || $w === null) ? $neu : (string)$w;
}

/* ---- DAS SCHEMA FRAGEN (Schritt 15/AP4, E-ZE-04) -------------------------
 *
 * Die drei Fragen „gibt es diese Tabelle / diese Spalte / diesen Index?"
 * standen bis Web 20.28.0 doppelt: privat in `migration_lib.php`
 * (`_hat_tabelle()`, `_hat_spalte()`, `_hat_index()`) und noch einmal
 * handgeschrieben in vier Dateien, die `migration_lib.php` nicht laden —
 * `ingest.php` sagte das sogar im Kommentar dazu.
 *
 * SIE NEHMEN EIN `PDO`, UND ZWAR ZWINGEND. `db()` waere hier falsch:
 * `tools/schemaprobe/probe.php` laesst Migrationen gegen ein frisch
 * angelegtes Schema laufen (`frisch()`), also gegen eine ANDERE Verbindung
 * als `db()`. Ein Helfer, der sich seine Verbindung selbst holt, fragte dort
 * das falsche Schema — und zwar lautlos, denn `DATABASE()` haette
 * geantwortet.
 *
 * GELAUFENE MIGRATIONEN WERDEN NICHT UMGEBAUT (E-ZE-04). Die privaten
 * `_hat_*` bleiben stehen und reichen nur noch durch; die 57
 * `information_schema`-Erwaehnungen in `migration_lib.php` bleiben, wo sie
 * sind. Registerzeile Z15 haelt ihre Zahl fest: Sie darf nicht steigen.
 * NEUE Migrationen fragen ueber diese drei.
 */

/** Gibt es die Tabelle im aktuellen Schema? */
function db_hat_tabelle(PDO $pdo, string $tabelle): bool {
    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables
                        WHERE table_schema = DATABASE() AND table_name = ?');
    $q->execute([$tabelle]);
    return (int)$q->fetchColumn() > 0;
}

/** Gibt es die Spalte? */
function db_hat_spalte(PDO $pdo, string $tabelle, string $spalte): bool {
    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
                        WHERE table_schema = DATABASE()
                          AND table_name = ? AND column_name = ?');
    $q->execute([$tabelle, $spalte]);
    return (int)$q->fetchColumn() > 0;
}

/** Gibt es den Index? */
function db_hat_index(PDO $pdo, string $tabelle, string $index): bool {
    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.statistics
                        WHERE table_schema = DATABASE()
                          AND table_name = ? AND index_name = ?');
    $q->execute([$tabelle, $index]);
    return (int)$q->fetchColumn() > 0;
}

/** Zeitpunkt der letzten Bestaetigung, oder null. */
function geraete_hinweis_stand(PDO $pdo, int $userId): ?string {
    try {
        $st = $pdo->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute(['geraetehinweis:' . $userId]);
        $v = $st->fetchColumn();
        return $v === false || $v === null ? null : (string)$v;
    } catch (Throwable $ex) {
        // app_state fehlt (Migration noch nicht gelaufen) -> wie bisher
        return null;
    }
}

/** Hinweis bestaetigen: alles bis JETZT gilt als gesehen. */
function geraete_hinweis_bestaetigen(PDO $pdo, int $userId): void {
    $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE v = VALUES(v)')
        ->execute(['geraetehinweis:' . $userId, gmdate('Y-m-d H:i:s')]);
}

const RESUS_LABELS = [
    'zugang' => 'Zugang',
    'beginn' => 'Reanimationsbeginn', 'adrenalin' => 'Adrenalingabe',
    'rhythmuskontrolle' => 'Rhythmuskontrolle', 'defibrillation' => 'Defibrillation',
    'intubation' => 'Intubation', 'amiodaron' => 'Amiodaron',
    'sonographie' => 'Sonographie', 'rosc' => 'ROSC', 'tod' => 'Tod',
];

/**
 * Automatischer Aufraeumjob — laeuft hoechstens einmal pro Tag, huckepack auf
 * normalen Anfragen (Web-Login und Uhr-Uploads), daher kein Cronjob noetig.
 * Entsorgt: verwaiste Trackpunkte (Einsatz/Segment geloescht) und alte
 * Passwort-Reset-Tokens. Scheitert leise, falls die app_state-Tabelle noch
 * nicht existiert (Migration noch nicht gelaufen).
 */
/**
 * Taegliche Wartung — huckepack auf Web-Anfragen und Uhr-Uploads (M3-05).
 *
 * WAS AN DER ALTEN FASSUNG FALSCH WAR
 * Die Tagesmarke wurde VOR der Arbeit gesetzt (richtig: verhindert
 * Doppellaeufe paralleler Anfragen), und der Fehlerblock war leer. Zusammen
 * ergab das eine Falle, aus der es kein Herauskommen gab:
 *
 *   Scheitert ein Schritt, bricht der gemeinsame try-Block ab. Alle
 *   nachfolgenden Schritte entfallen. Die Marke steht aber schon auf heute,
 *   also laeuft an diesem Tag nichts mehr. Am naechsten Tag beginnt es von
 *   vorn — und scheitert an derselben Stelle wieder. Dauerhaft, und ohne
 *   dass irgendwo etwas davon stuende.
 *
 * Am spuerbarsten beim Papierkorb: Er stand als letzter Schritt vor den
 * Passwort-Tokens und wurde nie geleert. "Endgueltig nach 30 Tagen" waere
 * stillschweigend zu "nie" geworden.
 *
 * DREI ÄNDERUNGEN
 *  1. Jeder Schritt hat seinen eigenen Fehlerblock. Einer, der scheitert,
 *     haelt die anderen sechs nicht auf.
 *  2. Fehler landen im Protokoll (seit P5c/AP3 Reiter System). Weiterhin still
 *     GEGENUEBER DER ANFRAGE — die Wartung darf keine Seite kaputt machen —
 *     aber nicht mehr spurlos.
 *  3. Ein zweiter Zustandsschluessel haelt fest, wann zuletzt ein Lauf
 *     VOLLSTAENDIG durchging. update.php zeigt beides an: Klaffen die Daten
 *     auseinander, scheitert etwas dauerhaft.
 *
 * Die Marke bleibt bewusst VOR der Arbeit. Sie danach zu setzen hiesse, dass
 * zwei gleichzeitige Anfragen beide aufraeumen; das ist der teurere Fehler.
 */
function run_cleanup_if_due(): void {
    /* SEIT WEB 10.1.0 IST DAS NUR NOCH DER DRITTE AUSLOESER (E-S2-17).
     *
     * Die Arbeit steht in `jobs_lib.php` und laeuft in HAEPPCHEN mit
     * Zeitbudget. Der Grund ist gemessen: Die alte Waisenpruefung war ein
     * Anti-Join ueber die ganze Tabelle und kostete bei 9,46 Mio. Zeilen
     * **4,07 Sekunden** — in genau dieser Anfrage. Bei der Zielmenge Z2
     * (190 Mio. Zeilen) waeren es Minuten, und die erste NutzerIn des Tages
     * saehe eine haengende Seite, ohne zu erfahren, warum.
     *
     * WARUM ES DIESEN WEG WEITERHIN GIBT. Eine frisch aufgesetzte
     * Installation hat weder Cron noch eingerichteten Abruf. Ohne den
     * Rueckfall stuende sie still — der Papierkorb bliebe voll, verfallene
     * Kopplungssitzungen blieben liegen (gueltig sind sie deshalb nicht: die
     * Frist steckt im SQL, nicht im Aufraeumen — kopplung_lib.php). Wer
     * einen der beiden anderen Ausloeser eingerichtet hat, merkt diesen hier
     * nicht: Dann ist nichts mehr zu tun, und der Aufruf kostet zwei
     * Abfragen.
     *
     * STILL GEGENUEBER DER ANFRAGE, wie bisher. Die Wartung darf keine Seite
     * kaputtmachen; was scheitert, steht im Fehlerprotokoll UND seit AP2 in
     * der Tabelle `jobs`, wo die Wartungsseite es zeigt.
     */
    try {
        require_once __DIR__ . '/jobs_lib.php';
        jobs_lauf('anfrage');
    } catch (Throwable $ex) {
        system_melden('cleanup', 'Job-Einstieg fehlgeschlagen', $ex);
    }
}
