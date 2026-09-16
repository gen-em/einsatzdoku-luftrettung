<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
/* Seit Web 9.7.0 fest statt nur im Abbruchzweig: logo_stamm() entscheidet
 * ueber Kopfleiste UND Favicon (E-P3-20), und beide werden auf JEDER
 * angemeldeten Seite gebraucht — nicht nur, wenn eine Sitzung endet. */
require_once __DIR__ . '/session_lib.php';

/* ---- HTTPS ZUERST (P5a/AP4, E-P5a-16) -----------------------------------
 *
 * VOR `session_start()`, und das ist der Punkt: Das Sitzungscookie traegt
 * `secure`. Ueber HTTP sendet der Browser es nicht — die Anmeldung scheitert
 * STUMM, und wer das nicht weiss, sucht den Fehler bei sich. Die Seite von
 * `https_tor()` sagt es stattdessen. */
https_tor();

session_set_cookie_params([
    'httponly' => true, 'secure' => true, 'samesite' => 'Strict', 'path' => '/',
]);
/* `use_strict_mode` VOR `session_start()` (P5a/AP4a, E-P5a-38, Backlog
 * Nr. 205). Ohne das uebernimmt PHP eine Sitzungskennung, die der Browser
 * mitbringt, auch wenn es sie nie vergeben hat — wer eine Kennung setzen
 * kann (ueber einen Link, eine fremde Seite auf derselben Domain, ein
 * gesetztes Cookie), kennt damit die Sitzung, in der sich gleich jemand
 * anmeldet. Das ist Session-Fixation, und der Schutz dagegen hing bis
 * Web 20.9.1 an der `php.ini` des Hosters.
 *
 * `install.php` und `wiederherstellen.php` setzten die Zeile seit jeher —
 * ausgerechnet die beiden Wege, die KEINE Anmeldesitzung tragen. */
ini_set('session.use_strict_mode', '1');
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

/* ---- DER TORWAECHTER (P5a/AP3, E-P5a-20; R40 (4), Backlog Nr. 54) --------
 *
 * STEHT EINE MIGRATION AUS, SCHLIESST DIE ANWENDUNG SICH SELBST. Zwischen
 * dem Hochladen neuer Dateien und dem Aufruf von Betrieb → Updates erwartet
 * neuer Code Tabellen, die es noch nicht gibt; die Anwendung antwortet in
 * diesem Fenster mit 500. Der Unterschied zwischen 500 und 503 ist der
 * zwischen „kaputt" und „gleich wieder da": Der JSON-Vertrag sagt zu 5xx
 * „spaeter unveraendert erneut", und Uhr wie Handy halten sich daran.
 *
 * WARUM HIER UND NICHT IN `db.php` NEBEN `wartung_tor()`. Jenes Tor ist
 * ausdruecklich OHNE Datenbank gebaut — es muss antworten, WAEHREND die
 * Datenbank umgebaut wird (`wartung_lib.php`, Eigenschaft 1). Eine Abfrage
 * dort naehme ihm genau die Eigenschaft, um derentwillen es dort steht.
 * Diese Pruefung braucht eine Verbindung, also steht sie eine Ebene hoeher.
 *
 * WAS DAS KOSTET, UND WAS ES OFFEN LAESST. Die Pruefung ist gecacht (Hash des
 * Katalogs, `migration_lib.php`) und kostet im Regelfall eine Zeile aus
 * `app_state`. Offen bleibt ein Fenster: `ingest.php` und `pair.php` laden
 * `auth_guard.php` NICHT — bis zur ersten angemeldeten Anfrage bekommen die
 * Geraete also weiter 500 statt 503. Verloren geht dabei nichts (5xx ist 5xx,
 * sie puffern und liefern nach), und fuer die Auslieferungskette ist das
 * Fenster null: Sie laesst den Wartungsmodus bei ausstehender Migration von
 * sich aus an (P5a/AP1, E-P5a-12). Fuer den Weg von Hand — Dateien
 * hochladen, `update.php` — schliesst es die erste angemeldete Anfrage.
 *
 * ERST SCHALTEN, DANN DAS TOR NOCH EINMAL FRAGEN. `wartung_tor()` ist in
 * `db.php` bereits gelaufen, als es die Datei noch nicht gab. Ohne den
 * zweiten Aufruf bekaeme genau die Anfrage, die den Wartungsmodus ausloest,
 * ihre Seite noch ausgeliefert — aus einer Anwendung, die sich gerade fuer
 * geschlossen erklaert hat. Fuer die Ausnahmeseiten (Betrieb → Updates)
 * kehrt der Aufruf sofort zurueck.
 */
require_once __DIR__ . '/migration_lib.php';
if (migrationen_ausstehend(db())) {
    wartung_einschalten('torwaechter');
    wartung_tor();
}

/* Formular-Token bereitstellen. Die Erzeugung steht seit Web 15.6.0 in
   `session_lib.php`, damit auch die Anmeldeseite sie hat (Backlog Nr. 127);
   hier wird sie einmal je angemeldeter Anfrage angestossen, damit
   `ui_krypto_bootstrap()` und die Formulare ein Token vorfinden. */
csrf_token();

/**
 * Ist der Aufruf ein Datenabruf des Browser-Skripts (server/api/...)?
 *
 * Gebraucht, wenn die Sitzung MITTEN in einer Anfrage endet. session_beenden()
 * liefert eine HTML-Seite aus — die raeumt die Schluessel im Browser und ist
 * fuer eine Seitenanfrage genau richtig. Ein fetch() aus dem Skript bekommt
 * damit aber HTML, wo es JSON erwartet: Der Aufrufer sieht einen Syntaxfehler
 * beim Auswerten und meldet irgendetwas Allgemeines statt "die Sitzung ist
 * beendet".
 *
 * Fuer diese Aufrufe gibt es deshalb 401 mit JSON und einem lesbaren Grund.
 * Das Raeumen der Schluessel uebernimmt die naechste Seitenanfrage, die
 * ohnehin auf der Anmeldeseite landet.
 */
function ist_api_aufruf(): bool {
    $pfad = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    return str_contains($pfad, '/api/');
}

/** Sitzung beenden — als JSON, wenn das Gegenueber JSON erwartet. */
function sitzung_beenden_passend(string $grund): never {
    require_once __DIR__ . '/session_lib.php';   // oben bereits geladen; idempotent
    if (ist_api_aufruf()) {
        session_verwerfen();
        json_out(['error'   => 'session_ende',
                  'grund'   => $grund,
                  'meldung' => session_ende_text($grund)], 401);
    }
    session_beenden($grund);
}

// Inaktivitaets-Timeout: nach 30 Minuten ohne Anfrage neu anmelden
const SESSION_TIMEOUT_S = 1800;
if (isset($_SESSION['last_seen']) && (time() - (int)$_SESSION['last_seen']) > SESSION_TIMEOUT_S) {
    /* ABGELAUFENE SITZUNG: ueber den gemeinsamen Weg beenden.
     *
     * Frueher stand hier eine reine Weiterleitung per Kopfzeile. Die fuehrt
     * NIE JavaScript aus — Daten- und Inhaltsschluessel blieben also im
     * sessionStorage des Tabs liegen, obwohl die Sitzung abgelaufen war. Wer
     * seinen Rechner nach der Frist stehen laesst, hatte eine abgelaufene
     * Sitzung und einen liegengebliebenen Schluessel.
     *
     * Der Abmeldeweg loeste dasselbe Problem bereits richtig; session_lib.php
     * ist die eine Fassung fuer beide, damit sie nicht wieder auseinander-
     * laufen. Sie nennt ausserdem den GRUND: Der frueher angehaengte
     * Parameter ?timeout=1 wurde von der Anmeldeseite gar nicht ausgewertet.
     */
    sitzung_beenden_passend('abgelaufen');
}
$_SESSION['last_seen'] = time();

/* ---- Die Nutzerzeile ist die Wahrheit, nicht die Sitzung (M1-05) ----------
 *
 * Die Rolle wurde bei der Anmeldung EINMAL in die Sitzung geschrieben und nie
 * wieder geprueft. Zwei Folgen, beide unbegrenzt haltbar, solange die Sitzung
 * offen blieb:
 *
 *   - Wem die Administratorrolle entzogen wird, behaelt seine Rechte.
 *   - Wessen Konto geloescht wird, bleibt angemeldet und arbeitet weiter.
 *     Die Einsaetze sind ueber die Fremdschluesselkaskade zwar fort, die
 *     Oberflaeche merkt davon aber nichts.
 *
 * Die Zeile wurde OHNEHIN bei jeder Anfrage gelesen — nur eben erst weiter
 * unten und nur fuer den Anzeigenamen. Sie wandert deshalb hierher: Sie kostet
 * keine zusaetzliche Abfrage, und die Sitzungskopie der Rolle entfaellt
 * ersatzlos.
 */
/* Spalten benennen statt SELECT * (M1-20).
 *
 * Diese Abfrage laeuft bei JEDER Anfrage. Mit * kam die ganze Zeile ins
 * Gedaechtnis des Prozesses, darunter password_hash — der Hash des
 * Anmeldetokens, der hier nirgends gebraucht wird. Ein Speicherabbild, ein
 * Fehlerbericht mit vollem Kontext oder ein var_dump beim Suchen enthielt ihn
 * damit ebenfalls, und zwar auf jeder einzelnen Seite.
 *
 * Der zweite Grund ist Lesbarkeit: Was diese Datei aus der Nutzerzeile
 * braucht, steht jetzt hier und nicht verteilt in acht Zugriffen weiter
 * unten. Kommt eine Spalte hinzu, wandert sie nicht mehr automatisch mit.
 *
 * (name existiert seit der Migration von Web 2.x; wer die nicht gefahren hat,
 * kann sich schon heute nicht anmelden — die Spalte wird in ui.php gelesen.) */
$u = db()->prepare('SELECT id, email, name, role, session_epoch,
                           pat_wrap_pw, pat_key_check, kdf_salt, kdf_iter,
                           status, gesperrt_grund
                    FROM users WHERE id = ?');
$u->execute([$userId]);
$row = $u->fetch();

if (!$row) {
    // Konto existiert nicht mehr. Nicht bloss "Kein Zugriff" melden und die
    // Sitzung stehen lassen — dann klickte man sich weiter durch eine
    // Anwendung, die einem nicht mehr gehoert.
    sitzung_beenden_passend('konto');
}

/* ---- Sitzungszaehler: Passwortwechsel beendet andere Sitzungen (M1-09/D6) --
 *
 * Wer sein Passwort wechselt, WEIL er Missbrauch vermutet, will genau eines
 * erreichen: dass der andere draussen ist. Bisher erreichte er das nicht — die
 * offene Sitzung des Angreifers lief unbeeindruckt weiter, denn sie haengt am
 * Sitzungscookie und nicht am Passwort.
 *
 * users.session_epoch (S3, seit P0 im Schema) wird bei jedem Passwortwechsel
 * erhoeht. Jede Anfrage vergleicht ihren mitgefuehrten Stand dagegen; wer noch
 * den alten hat, fliegt hier heraus.
 *
 * Die Sitzung, die den Wechsel selbst ausloest, zieht ihren Stand mit (siehe
 * einstellungen.php) und bleibt bestehen. Abnahmekriterium A5 sagt "alle
 * ANDEREN Sitzungen"; die handelnde Person mitten im eigenen Vorgang
 * abzumelden waere kein Sicherheitsgewinn, sondern nur laestig.
 *
 * Sitzungen aus der Zeit vor dieser Fassung fuehren den Wert noch nicht mit.
 * Sie werden uebernommen, statt beim Aufspielen alle Angemeldeten auszusperren
 * — der Stand wird beim ersten Zugriff aus der Zeile nachgetragen.
 */
$epocheDb = (int)($row['session_epoch'] ?? 0);
if (!isset($_SESSION['epoch'])) {
    $_SESSION['epoch'] = $epocheDb;
} elseif ((int)$_SESSION['epoch'] !== $epocheDb) {
    sitzung_beenden_passend('passwort');
}

/* ---- Kontostatus (P5b/AP2, E-P5b-12) -------------------------------------
 *
 * HIER UND NICHT IN `login.php`: Dort faellt die Entscheidung frueher und
 * verhindert, dass ein gesperrtes Konto ueberhaupt eine Sitzung bekommt.
 * Diese Pruefung hier gilt einer Sitzung, die ALTER ist als die
 * Statusaenderung — jemand ist angemeldet, und waehrenddessen wird das Konto
 * gesperrt oder die Loeschung beantragt. Ohne sie klickte er sich weiter
 * durch eine Anwendung, die ihm nicht mehr offensteht, bis die halbe Stunde
 * Untaetigkeit abgelaufen ist.
 *
 * DIE SELBSTLOESCHUNG IST DIE AUSNAHME (E-P5b-16): Waehrend der Karenz ist
 * das Konto `gesperrt`, aber die Anmeldung IST der Rueckzug. Wer sich
 * anmeldet, nimmt die Loeschung zurueck — deshalb darf diese Pruefung ihn
 * nicht hinauswerfen. `login.php` erledigt die Ruecknahme; bis dahin laesst
 * dieser Zweig ihn durch.
 *
 * WARUM `status` UND NICHT `gesperrt_seit IS NOT NULL`: Der Status ist die
 * eine Wahrheit. Eine zweite Bedingung neben ihm waere eine zweite Stelle,
 * an der „gesperrt" definiert ist. */
$kontoStatus = (string)($row['status'] ?? 'aktiv');
$kontoGrund  = $row['gesperrt_grund'] ?? null;
if ($kontoStatus === 'gesperrt'
    && $kontoGrund !== 'selbstloeschung') {
    sitzung_beenden_passend('gesperrt');
}
if ($kontoStatus === 'unbestaetigt' || $kontoStatus === 'wartet') {
    /* Diese beiden kommen ueber `login.php` gar nicht erst herein. Erreicht
     * die Anfrage sie trotzdem, ist die Sitzung aelter als der Status — dann
     * ist Abmelden die richtige Antwort und nicht eine Seite, die erklaert,
     * warum man hier ist. */
    sitzung_beenden_passend('gesperrt');
}

/* ---- Das Einwilligungstor (P5b/AP4, E-P5b-05, -15) -----------------------
 *
 * WAS ES SPERRT UND WAS NICHT. Fehlt die Annahme der aktuellen Fassung von
 * Nutzungsbedingungen oder Auftragsverarbeitung, fuehrt jeder Weg auf
 * `einwilligung.php`. Fehlt nur die Kenntnisnahme der Datenschutzerklaerung,
 * steht ein Hinweis oben auf jeder Seite und sonst nichts — der Unterschied
 * ist kein Rang, sondern die Rechtsnatur (siehe `einwilligung_lib.php`).
 *
 * DREI WEGE BLEIBEN OFFEN, und das ist Teil der Entscheidung (E-P5b-05):
 * Abmelden, Export, Konto loeschen. Wer nicht zustimmen will, muss an seine
 * Daten kommen und gehen koennen; ein Tor, das auch den Ausgang versperrt,
 * waere Noetigung.
 *
 * API UND `ingest.php` BLEIBEN UNBERUEHRT. Die Uhr fragt niemanden um
 * Zustimmung — sie hat keinen Bildschirm dafuer, und ihre Besitzerin hat der
 * Nutzung zugestimmt, als sie das Geraet gekoppelt hat. Ein Tor vor
 * `api/day.php` liesse eine laufende Aufzeichnung ins Leere laufen, ohne
 * dass irgendwo jemand einen Haken setzen koennte.
 *
 * DIE STELLE: nach dem Kontostatus (ein gesperrtes Konto ist schon draussen)
 * und vor der Rolle (das Tor gilt fuer alle, auch fuer die Verwaltung —
 * gerade sie soll die Texte gelesen haben, die sie hinterlegt).
 */
$einwilligungOffen = ['sperrt' => [], 'hinweis' => []];
if (!ist_api_aufruf()) {
    require_once __DIR__ . '/einwilligung_lib.php';
    $einwilligungOffen = einwilligung_offen($userId);

    if ($einwilligungOffen['sperrt']) {
        $hier = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
        /* Die Ausnahmeliste ist kurz und steht hier, nicht in einer
         * Konstante: Sie gehoert zum Tor und wird mit ihm gelesen. */
        $offen = ['einwilligung.php', 'logout.php', 'import.php',
                  'export.php', 'einstellungen.php'];
        if (!in_array($hier, $offen, true)) {
            header('Location: einwilligung.php');
            exit;
        }
    }
}

/* ---- Rolle: aus der Zeile, nicht aus der Sitzung -------------------------- */
$userRole = rolle_normieren($row['role'] ?? null);

/**
 * Die EINE Rollenpruefung (M1-15).
 *
 * Vorher gab es drei Schreibweisen fuer dieselbe Frage: require_admin() in
 * drei Dateien, eine handgeschriebene Pruefung mit eigener Meldung in
 * admin_user.php und zwei Vergleiche in ui.php fuer die Anzeige. Eine
 * Rollenpruefung, die an fuenf Stellen unabhaengig formuliert ist, wird beim
 * naechsten Zusatz — etwa einer dritten Rolle — an vier Stellen richtig
 * geaendert.
 *
 * DIE DRITTE ROLLE IST DA (Web 15.0.0, R75), und der Satz oben hat sich
 * bewahrheitet: Weil die Frage an einer Stelle steht, kostete sie hier eine
 * Zeile. `ist_admin()` heisst "darf verwalten" und ist fuer eine BetreiberIn
 * ebenfalls wahr — BetreiberIn ⊇ Admin ⊇ NutzerIn (Zielbild 5.3). Wer
 * ausschliesslich den Betriebsbereich meint, fragt ist_betreiberin().
 */
function ist_admin(): bool {
    global $userRole;
    return rolle_darf_verwalten($userRole);
}

function require_admin(): void {
    if (!ist_admin()) {
        if (ist_api_aufruf()) { json_out(['error' => 'forbidden'], 403); }
        ui_abbruch(403, 'Kein Zugriff.');
    }
}

/**
 * Darf die Angemeldete den Bereich BETRIEB sehen und bedienen?
 *
 * Betrieb ist alles, was die INSTALLATION betrifft und nicht ihren Inhalt:
 * Serverbetrieb und Wartungsmodus, ausstehende Updates, Hintergrundjobs,
 * Speichergrenzen, Komplett-Backup, Backup-Ziele. Eine Fehlbedienung dort
 * trifft nicht ein Konto, sondern alle — deshalb hat der Bereich eine eigene
 * Rolle und nicht nur eine eigene Seite.
 */
function ist_betreiberin(): bool {
    global $userRole;
    return rolle_ist_betreiberin($userRole);
}

function require_betreiberin(): void {
    if (!ist_betreiberin()) {
        if (ist_api_aufruf()) { json_out(['error' => 'forbidden'], 403); }
        ui_abbruch(403, 'Kein Zugriff — dieser Bereich ist der BetreiberIn vorbehalten.');
    }
}

/** Die Rolle der Angemeldeten als Wert ('user' | 'admin' | 'betreiberin'). */
function eigene_rolle(): string {
    global $userRole;
    return $userRole;
}

/**
 * Die Rollen, die die Angemeldete vergeben darf (R75).
 *
 * Nur eine BetreiberIn vergibt oder entzieht die Rolle „BetreiberIn". Ein
 * Admin bekommt die Option gar nicht erst zu sehen — er koennte sich sonst
 * selbst hochstufen, und die Rolle waere keine Grenze.
 *
 * HIER UND NICHT IN db.php, obwohl der Rollenkatalog dort steht: Die Antwort
 * haengt an der ANGEMELDETEN, also an der Sitzung. Alles, was eine Sitzung
 * braucht, steht in dieser Datei.
 */
function rollen_auswahl(): array
{
    $o = ['user' => ROLLEN['user'], 'admin' => ROLLEN['admin']];
    if (ist_betreiberin()) { $o['betreiberin'] = ROLLEN['betreiberin']; }
    return $o;
}

/* csrf_token(), csrf_field() und csrf_ok() stehen seit Web 15.6.0 in
 * `session_lib.php` — die Anmeldeseite braucht sie und laedt diese Datei
 * nicht (Backlog Nr. 127). Hier bleibt nur der Abbruchweg, den es nur fuer
 * angemeldete Seiten gibt. */
function csrf_check(): void {
    if (csrf_ok()) { return; }
    /* DER API-ZWEIG (P5a/AP4, Backlog Nr. 67, R21).
     *
     * `ui_abbruch()` liefert eine HTML-Seite aus. Ein `fetch()` bekaeme damit
     * Markup, wo es JSON erwartet, und meldete einen Syntaxfehler statt
     * „Formular-Token abgelaufen" — dieselbe Falle wie bei einer Sitzung, die
     * mitten in einer Anfrage endet (siehe `sitzung_beenden_passend()` oben).
     *
     * Bis Web 20.6.0 gab es diesen Zweig nicht, und die zwoelf Endpunkte
     * prueften deshalb jeder fuer sich. Jetzt fragen alle dieselbe Funktion:
     * `csrf_ok()` nimmt Feld UND Kopfzeile, und hier faellt die Entscheidung,
     * in welcher Sprache das Nein kommt. */
    if (ist_api_aufruf()) { json_out(['error' => 'csrf'], 403); }
    ui_abbruch(403, 'Ungültiges Formular-Token.');
}

// Anzeigename fuer die Kopfleiste (name-Spalte existiert erst nach Migration)
$userEmail = (string)$row['email'];
$userName  = $row['name'] ?? null;

// Pflicht-Verschlüsselung: aktiv, sobald der Inhaltsschluessel verpackt
// vorliegt. Seit Web 2.7.0 entstehen Passwort und beide Huellen gemeinsam in
// pw_handling.php — ein anmeldbares Konto ohne Huelle kann es nicht mehr
// geben, deshalb entfaellt die frueher hier erzwungene Ersteinrichtung.
$patWrapPw = $row['pat_wrap_pw'] ?? null;
$patReady  = $patWrapPw !== null;
// Pruefsumme des Inhaltsschluessels (NULL bei Konten aus der Zeit vor Web
// 4.0.0 — ein gueltiger Zustand, siehe M1-12).
$patKeyCheck = $row['pat_key_check'] ?? null;
$kdfSalt     = $row['kdf_salt'] ?? null;
/* Rundenzahl der Schluesselableitung dieses Kontos (M2-01).
 *
 * Sie gehoert zum Salz wie die Hausnummer zur Strasse: Wer mit dem einen
 * rechnet und das andere raet, bekommt einen anderen Schluessel. Beide werden
 * deshalb ueberall gemeinsam an den Browser gegeben.
 *
 * Der Rueckfall auf den Zielwert greift nur fuer Zeilen, die aelter sind als
 * die Spalte — sie kann seit P0 nicht mehr NULL sein. */
$kdfIter     = (int)($row['kdf_iter'] ?? KDF_ITER_ZIEL) ?: KDF_ITER_ZIEL;

/* ---- Der Server-Anteil dieses Kontos (S10, E-S10-06) ---------------------
 *
 * NUR AN DIE ANGEMELDETE SITZUNG, UND NUR FUER DAS EIGENE KONTO. Das ist der
 * ganze Zuschnitt: Es gibt keinen Endpunkt, der den Anteil eines FREMDEN
 * Kontos herausgibt, und `auth_salt.php` — der einzige unangemeldete Weg, auf
 * dem Krypto-Angaben das Haus verlassen — bleibt unberuehrt. Wer den Anteil
 * eines Kontos will, braucht dessen Sitzung; und wer die hat, hat ohnehin die
 * Huelle.
 *
 * DAS DEMO-KONTO BEKOMMT KEINEN (E-P1-19, Backlog Nr. 155). Seine Huelle
 * kommt aus der Fixture und muss auf JEDER Installation aufgehen — eine
 * Huelle, die am Anteil dieser einen Installation haengt, taete das nicht.
 * Es bekommt deshalb `null` und den Stand `'demo'`, und seine Huelle bleibt
 * `edk1:`. Dasselbe Muster wie bei der Rundenzahl, aus demselben Grund.
 *
 * `$kontoAnteile` ist ein Feld `{ kennung => 64 hex }`: im Regelfall einer,
 * waehrend einer Rotation zwei, im Zustand „abweichend" keiner. Welcher davon
 * der AKTUELLE ist — der, mit dem neue Huellen gebaut werden —, sagt
 * `$anteilKennung`; ohne diese Angabe muesste der Browser auf die
 * Reihenfolge der Schluessel im Feld vertrauen, und das waere eine Zusage,
 * die niemand aufgeschrieben hat. */
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/demo_lib.php';
if (demo_ist_demo($userId)) {
    $kontoAnteile  = null;
    $anteilKennung = null;
    $anteilStand   = 'demo';
} else {
    $anteilKennung = anteil_ausgeliefert();
    $kontoAnteile  = $anteilKennung === null ? [] : konto_anteile($userId);
    $anteilStand   = $anteilKennung !== null
        ? 'bereit'
        : (anteil_zustand()['stand'] === 'fehlt' ? 'fehlt' : 'abweichend');
}

require_once __DIR__ . '/ui.php';

run_cleanup_if_due();   // taegliche Wartung, huckepack auf Web-Anfragen

/* Demo-Konto: faelliger Reset VOR der Antwort (E-P1-18).
 *
 * Dasselbe Muster wie die Tageswartung darueber — anfragegetrieben, kein
 * Zeitdienst noetig. Der Unterschied ist die Reihenfolge: Hier wird
 * zurueckgesetzt, BEVOR die Seite ihre Daten liest. Wer nach laengerer Ruhe
 * kommt, soll den Standardzustand sehen und nicht die Hinterlassenschaft der
 * letzten Besucherin.
 *
 * Nur fuer das Demo-Konto selbst: `demo_reset_wenn_faellig()` prueft die
 * Kennung aus `app_state` und tut fuer jedes andere Konto nichts. Der Aufruf
 * steht trotzdem hinter einer eigenen Abfrage, damit eine Installation ohne
 * Demo-Konto die Datei gar nicht erst laedt.
 *
 * Scheitert der Reset, laeuft die Anfrage weiter — eine Wartung darf keine
 * Seite kaputtmachen. Der Grund landet im Fehlerprotokoll. */
require_once __DIR__ . '/demo_lib.php';
if (demo_ist_demo($userId)) { demo_reset_wenn_faellig(); }
