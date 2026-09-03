<?php
declare(strict_types=1);

require_once __DIR__ . '/version.php';
// E-Mail-Normalisierung (M1-13). Eigene Datei ohne Abhaengigkeiten, weil
// install.php sie ebenfalls braucht und dort noch keine config.php existiert.
require_once __DIR__ . '/email_lib.php';

$CFG = require __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    global $CFG;
    if ($pdo === null) {
        $pdo = new PDO($CFG['db']['dsn'], $CFG['db']['user'], $CFG['db']['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
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
         *                    Gueltigkeit von Tokens und Kopplungscodes. Diese
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

/** Anzahl PERSOENLICHER Eintraege (aller NutzerInnen) mit gleichem
 *  (Vergleichs-)Namen wie der uebergebene — fuer die Duplikat-Warnung
 *  (Nutzer-Ansicht) bzw. den Admin-Hinweis "N Nutzer haben ...". */
function stammdaten_dup_personal_count(string $table, string $col, string $val,
                                        ?string $extraCol = null, ?string $extraVal = null): int {
    $sql = "SELECT COUNT(*) FROM $table WHERE user_id IS NOT NULL AND LOWER($col) = LOWER(?)";
    $params = [$val];
    if ($extraCol !== null) { $sql .= " AND $extraCol = ?"; $params[] = $extraVal; }
    $st = db()->prepare($sql);
    $st->execute($params);
    return (int)$st->fetchColumn();
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
    global $CFG;
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
    $pfad = (string)($CFG['app']['logo_path'] ?? '');
    $eigen = $pfad !== ''
        && !str_contains($pfad, 'gen-em_logo_helicopter')
        && !str_contains($pfad, 'gen-em_logo_nef')
        && is_file(__DIR__ . '/' . ltrim($pfad, '/'));
    if ($eigen) { return asset($pfad); }
    $stamm = function_exists('logo_stamm') ? logo_stamm() : 'gen-em_logo_helicopter';
    return asset('assets/images/' . $stamm . '.svg');
}

/** UTC-DATETIME (aus DB) -> Anzeige in App-Zeitzone */
function fmt_local(?string $utc, string $format = 'H:i'): string {
    global $CFG;
    if ($utc === null || $utc === '') return '–';
    $dt = new DateTime($utc, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone($CFG['app']['timezone']));
    return $dt->format($format);
}

/**
 * Ortszeit (App-Zeitzone) -> UTC-DATETIME. Gegenstueck zu fmt_local().
 *
 * Lag frueher in einsatz_form.php. Seit dem Import (import_commit.php) gibt es
 * einen zweiten Aufrufer; zwei Kopien derselben Zeitrechnung waeren die
 * sicherste Art, sich spaeter eine Stunde Versatz einzuhandeln.
 *
 * $addDays deckt Zeiten nach Mitternacht ab, die noch zum Diensttag gehoeren.
 */
function local_to_utc(string $day, string $hhmm, int $addDays = 0): ?string {
    global $CFG;
    // Nicht nur das Muster pruefen, sondern auch den Wertebereich: "25:00"
    // passt auf \d{2}:\d{2}, und DateTime rechnet daraus klaglos den naechsten
    // Tag 00:00. Eine Falscheingabe waere so als stiller Datumssprung
    // durchgerutscht statt als Fehler aufzufallen.
    if (!preg_match('/^(\d{2}):(\d{2})$/', $hhmm, $t)) return null;
    if ((int)$t[1] > 23 || (int)$t[2] > 59) return null;
    $dt = DateTime::createFromFormat('Y-m-d H:i', "$day $hhmm",
        new DateTimeZone($CFG['app']['timezone']));
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

function json_out(array $data, int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json');
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
    echo json_encode($data);
    exit;
}

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
 * Beides loest dieselbe Aenderung: Der volle Text geht ins Fehlerprotokoll
 * des Webspace, nach aussen geht eine Kennung. Sie ist kurz genug, um sie am
 * Telefon durchzugeben, und lang genug, um im Protokoll eindeutig zu sein.
 *
 * Bewusst NICHT geaendert: install.php und update.php zeigen ihre Ausnahmen
 * weiterhin im Klartext. Beide laufen nur fuer Verwaltende, beide in Lagen
 * (Ersteinrichtung, Migration), in denen der genaue Text die eigentliche
 * Auskunft ist — und bei install.php gibt es noch kein Fehlerprotokoll, in
 * dem man nachsehen koennte.
 */
function fehler_kennung(Throwable $ex, string $bereich): string
{
    $kennung = strtoupper(bin2hex(random_bytes(4)));
    error_log('[' . $kennung . '] ' . $bereich . ': ' . $ex->getMessage()
              . ' @ ' . $ex->getFile() . ':' . $ex->getLine());
    return $kennung;
}

/**
 * Die Standardantwort eines Endpunkts auf einen unerwarteten Fehler (M3-10).
 * Beendet die Anfrage mit 500 und nennt nur die Kennung.
 */
function json_fehler(Throwable $ex, string $bereich): never
{
    $kennung = fehler_kennung($ex, $bereich);
    json_out(['error'   => $bereich,
              'kennung' => $kennung,
              'meldung' => 'Es ist ein unerwarteter Fehler aufgetreten (Kennung '
                         . $kennung . '). Er steht im Fehlerprotokoll des Webspace.'], 500);
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
 * DIE NOTAERZTIN IST KEINE ROLLE — sie ist die Nutzerin.
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
 * stehen — und umgekehrt. Sie kommen ausschliesslich an luftgebundenen
 * Rettungsmitteln vor und steuern die zugehoerigen Einsatzfelder allein; eine
 * zusaetzliche Pruefung auf die Art ist deshalb weder noetig noch vorgesehen.
 */
const VEHICLE_CAPABILITIES = [
    'winch'     => 'Winde',
    'bergwacht' => 'Bergwacht',
];

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
 * Erzeugen (einstellungen.php), beim Einloesen (pair.php) und beim Aufraeumen
 * (unten). Frueher standen sie dreimal verschieden im Code — das Pruefmuster
 * liess vier bis acht Zeichen zu und ausdruecklich auch solche, die das
 * Alphabet gar nicht enthaelt.
 *
 * SECHS Zeichen aus 32 sind 30 Bit, also rund 1,07 Milliarden Moeglichkeiten.
 * Die eigentliche Arbeit macht aber der Ratenschutz (ratelimit_lib.php): Ohne
 * ihn war der frühere Coderaum (5 Zeichen, 60 Minuten gueltig, keine Bremse
 * ausser 0,3 s je Anfrage) mit genuegend parallelen Anfragen in gut einer
 * Stunde vollstaendig durchlaufbar. Der Ratenschutz ist deshalb PFLICHT und
 * keine Ergaenzung.
 *
 * Zehn Minuten Gueltigkeit statt sechzig: Die Kopplung geschieht mit der Uhr
 * in der Hand: Wer den Code erzeugt, tippt ihn unmittelbar danach ein.
 */
const PAIR_CHARS   = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';   // ohne 0/O und 1/I
const PAIR_LEN     = 6;
const PAIR_TTL_MIN = 10;
const PAIR_RE      = '/^[' . PAIR_CHARS . ']{' . PAIR_LEN . '}$/';

/* ---- Fester Vergleichswert fuer unbekannte Kennungen ---------------------
 *
 * An zwei Stellen wird ein Geheimnis gegen einen gespeicherten bcrypt-Hash
 * geprueft: die Anmeldung (login.php, Auth-Token) und die Uhr (ingest.php,
 * Geraeteschluessel). An beiden lief die Pruefung nur, WENN es die Kennung
 * gab. Bei unbekannter Kennung kam die Abweisung sofort — und dieser
 * Zeitunterschied beantwortet dieselbe Frage wie eine unterschiedliche
 * Meldung: Gibt es dieses Konto, gibt es dieses Geraet?
 *
 * Deshalb laeuft auch der unbekannte Zweig gegen diesen Wert. Er ist kein
 * Geheimnis — er darf offen im Code stehen, weil zu ihm kein Passwort
 * gehoert, das jemand einsetzen koennte. Seine einzige Aufgabe ist, denselben
 * Rechenaufwand zu erzeugen.
 *
 * ZUR RUNDENZAHL ($2y$10$): Sie entspricht der, mit der PASSWORD_DEFAULT auf
 * PHP 8.1 bis 8.3 arbeitet — also der Rundenzahl aller hier gespeicherten
 * Hashes. Legt eine spaetere PHP-Fassung teurere Hashes an, gehoert dieser
 * Wert nachgezogen, sonst faellt der unbekannte Zweig wieder aus dem Takt.
 */
const AUTH_VERGLEICHSWERT = '$2y$10$ZX1Xrc9GGuRDFtXcHFnamOR.a5ztKtqmvlaxsdApTgxVKhLdRmbJy';

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
 * REIHENFOLGE: Der Zielwert steht VORNE. Der Browser probiert nicht der Reihe
 * nach (er schickt alle Token), aber die Reihenfolge ist die Lesart.
 */
const KDF_ITER_ZIEL  = 320000;
const KDF_ITER_LISTE = [320000];

/* ---- Geraete je Konto: Obergrenze und Hinweisfenster ---------------------
 *
 * WARUM ES EINE OBERGRENZE GIBT
 * Ein Geraet ist ein Satz Zugangsdaten, mit dem sich Einsaetze in ein Konto
 * schreiben lassen. Ohne Obergrenze konnte ein Konto beliebig viele davon
 * ansammeln, und niemand haette es bemerkt: Wer einen Kopplungscode abfaengt,
 * legt sich ein Geraet an, das neben den echten unauffaellig in der Liste
 * steht. Die Grenze macht aus "faellt niemandem auf" ein "geht nicht mehr,
 * ohne dass jemand aufraeumt".
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
 *  2. Fehler landen im Fehlerprotokoll des Webspace. Weiterhin still
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
     * (190 Mio. Zeilen) waeren es Minuten, und die erste Nutzerin des Tages
     * saehe eine haengende Seite, ohne zu erfahren, warum.
     *
     * WARUM ES DIESEN WEG WEITERHIN GIBT. Eine frisch aufgesetzte
     * Installation hat weder Cron noch eingerichteten Abruf. Ohne den
     * Rueckfall stuende sie still — der Papierkorb bliebe voll, die
     * Kopplungscodes ewig gueltig. Wer einen der beiden anderen Ausloeser
     * eingerichtet hat, merkt diesen hier nicht: Dann ist nichts mehr zu tun,
     * und der Aufruf kostet zwei Abfragen.
     *
     * STILL GEGENUEBER DER ANFRAGE, wie bisher. Die Wartung darf keine Seite
     * kaputtmachen; was scheitert, steht im Fehlerprotokoll UND seit AP2 in
     * der Tabelle `jobs`, wo die Wartungsseite es zeigt.
     */
    try {
        require_once __DIR__ . '/jobs_lib.php';
        jobs_lauf('anfrage');
    } catch (Throwable $ex) {
        error_log('cleanup: Job-Einstieg fehlgeschlagen: ' . $ex->getMessage());
    }
}
