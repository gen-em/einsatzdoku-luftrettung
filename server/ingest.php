<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/spur_lib.php';   // Fortsetzungsmarke ueber beide Stufen (S2)
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/geraete_lib.php';  // herkunft_ableiten() (R64)

/** Gibt es die Spalte? Eine Abfrage am Informationsschema -- ingest.php
 *  laedt migration_lib.php nicht, deshalb steht die Frage hier noch einmal. */
function ingest_hat_spalte(PDO $pdo, string $tabelle, string $spalte): bool
{
    $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
                        WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
    $q->execute([$tabelle, $spalte]);
    return (int)$q->fetchColumn() > 0;
}

/**
 * Wird an diesem Diensttag noch gearbeitet?
 *
 * Anker ist das juengste `created_at` der ANDEREN Datensaetze des Tages --
 * der eigene, gerade angelegte zaehlt nicht mit, sonst waere jeder Tag offen,
 * an dem eben ein Paket ankam, und genau das war der Angriff. Hat der Tag
 * keine anderen Datensaetze, ist er frisch und offen.
 *
 * NICHT die Zeiten des Tages: Die kommen vom Absender. Die erste Fassung
 * dieser Funktion hat sie benutzt und damit den nachgelieferten Dienst
 * bestraft -- ein Dienst, der mehr als INGEST_ERSETZFENSTER_H Stunden nach
 * seinem Datum ankam, bekam nie ein `ended_at`. Serverzeit gegen Serverzeit,
 * wie beim Ersetzfenster der Datensaetze selbst.
 *
 * `rest_segments.created_at` gibt es erst nach der Migration; bis dahin
 * zaehlen nur die Einsaetze. Ein Tag, der nur Ruhesegmente traegt, gilt in
 * diesem Zwischenzustand als offen -- die Zeitpruefung gegen `day` deckt den
 * Schaden ab, den das offen laesst.
 */
function ingest_tag_offen(PDO $pdo, int $dayId, string $eigeneTabelle, int $eigeneId): bool
{
    if ($dayId <= 0) { return false; }
    $juengste = null;
    foreach (['missions', 'rest_segments'] as $tab) {
        if ($tab === 'rest_segments'
            && !ingest_hat_spalte($pdo, 'rest_segments', 'created_at')) { continue; }
        $sql  = "SELECT MAX(created_at) FROM `$tab` WHERE day_id = ?";
        $args = [$dayId];
        if ($tab === $eigeneTabelle && $eigeneId > 0) { $sql .= ' AND id <> ?'; $args[] = $eigeneId; }
        $q = $pdo->prepare($sql);
        $q->execute($args);
        $wert = $q->fetchColumn();
        if ($wert === false || $wert === null) { continue; }
        $t = strtotime($wert . ' UTC');
        if ($t !== false) { $juengste = max($juengste ?? 1, $t); }
    }
    if ($juengste === null) { return true; }
    $juengste = max(1, min($juengste, time()));
    return (time() - $juengste) <= INGEST_ERSETZFENSTER_H * 3600;
}

/**
 * Der Datensatz wandert auf den neu bestimmten Diensttag, wenn sein
 * bisheriger im Papierkorb liegt (Backlog Nr. 33; zweite Gegenpruefung,
 * Wiederaufnahme). Der Upsert traegt `day_id` nur im INSERT -- mit Absicht,
 * damit eine Nachlieferung einen im Web umgehaengten Einsatz nicht
 * zurueckzieht. Fuer den Papierkorbfall hiess das bis hierher: Es entstand
 * ein neuer, leerer Tag, und der Einsatz blieb am geloeschten haengen --
 * halb sichtbar, genau der Zustand, den Nr. 33 beschreibt. Jetzt zieht
 * er nach, und zwar nur in diesem einen Fall: bestehender Datensatz, sein
 * Tag geloescht (`$vorhandenerDayId` ist dann null), ein anderer bestimmt.
 */
function ingest_tag_nachziehen(PDO $pdo, string $tabelle, int $id, $existing, ?int $vorhandenerDayId, int $dayId): void
{
    // `$existing` ist das Ergebnis von fetch(): ein Array oder false.
    if (!is_array($existing) || $existing['day_id'] === null || $vorhandenerDayId !== null) { return; }
    if ($dayId <= 0 || (int)$existing['day_id'] === $dayId) { return; }
    $pdo->prepare("UPDATE `$tabelle` SET day_id = ? WHERE id = ?")->execute([$dayId, $id]);
}

/**
 * Aufnahme der Uhr-Daten.
 *
 * PRUEFTIEFE: Dieser Weg wird seit dieser Auslieferung ueber dieselbe
 * Pruefschicht gefuehrt wie Formular, Import und Wiedereinspielen
 * (validate_lib.php). Vorher fehlten hier Koordinatenbereiche und
 * Mengenbegrenzungen — ungeprueft gingen Koordinaten in die Phasen, in die
 * Spur UND in die Hoehenberechnung des Einsatzorts ein, wo ein Ausreisser
 * nicht nur einen Kartenpunkt verschiebt, sondern eine berechnete Zahl.
 *
 * GRUNDSATZ: Ein einzelner unbrauchbarer Wert verwirft den WERT, nicht den
 * Upload. Die Uhr kann nichts nachliefern, was sie schon geloescht hat —
 * ein Abbruch wegen einer krummen Koordinate koennte einen ganzen Einsatz
 * kosten. Verworfene Werte werden im Feld 'rejected' der Antwort genannt,
 * damit der Verlust sichtbar ist statt still.
 *
 * NICHT umgesetzt und ausdruecklich so gewollt: eine Entdoppelung mehrfacher
 * Phasennummern. Eine erneut gesetzte Phase ist eine Korrektur und damit eine
 * Information (JSON-Vertrag, Abschnitt 3).
 *
 * ZUORDNUNG ZUM DIENSTTAG (seit Web 6.0.0, JSON-Vertrag 4.4). Einsaetze und
 * Ruhe-Segmente haengen an `days.id`, nicht mehr am Datum. Zwei Wege fuehren
 * dorthin, und BEIDE bleiben:
 *
 *   1. `day_ref` — die von der Uhr bei "Einsatztag starten" erzeugte
 *      Dienstkennung. Sie wird in `day_refs` nachgeschlagen; ein Treffer
 *      liefert den Diensttag, auch wenn dieser inzwischen in einen anderen
 *      aufgenommen wurde (A8). Kein Treffer legt einen neuen Diensttag an.
 *   2. `(user_id, day)` als RUECKFALLEBENE fuer Uhr-Fassungen ohne `day_ref`.
 *      Sie ist nicht als Uebergang gedacht, sondern dauerhaft: Ein Update des
 *      Webs darf eine Uhr nicht ausser Betrieb setzen, die niemand aktualisiert
 *      hat. Der JSON-Vertrag steigt erst mit Etappe 4 auf 1.3; bis dahin
 *      schickt keine Uhr eine Kennung, und dieser Weg ist der einzige.
 *
 * Der Diensttag ist dabei immer NEUTRAL (E26): Die Uhr kennt die Einsatzart
 * nicht (E21) und traegt weder Standort noch Rettungsmittel bei. Beides wird im
 * Web nachgetragen; bis dahin fehlen Rollen und artabhaengige Felder, waehrend
 * Zeiten, Phasen, Track und Reanimation vollstaendig erfasst werden (A7a).
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['error' => 'method'], 405);

$raw = file_get_contents('php://input');
if (strlen($raw) > $CFG['app']['max_body_bytes']) json_out(['error' => 'too_large'], 413);

/* --- Geraet authentifizieren -------------------------------------------------
 *
 * ANTWORTZEIT (M4-07): Bei unbekannter Gerätekennung kam die Abweisung frueher
 * sofort, bei bekannter lief erst eine Pruefung des Schluessels. Der
 * Unterschied ist ohne jede Zugangsdaten messbar und beantwortet die Frage,
 * welche Geraetekennungen es gibt — und die Kennung ist die Haelfte dessen,
 * was ein Upload braucht. Deshalb laeuft auch der unbekannte Zweig gegen einen
 * festen Vergleichswert.
 *
 * SEIT WEB 13.0.0 IST DER SCHLUESSEL SHA-256, NICHT BCRYPT (S5, E-S5-42;
 * die Verfahrenswahl steht in db.php bei GERAET_VERGLEICHSWERT). Der
 * Schluessel sind 24 Zufallsbytes — bcrypt bremste hier nichts als den Server,
 * 228 ms je Upload. Der Blindvergleich bleibt trotzdem: Beide Zweige gehen
 * dieselben Schritte, und hash_equals() vergleicht in konstanter Zeit. Ein
 * bcrypt-Hash aus der Zeit davor passt nie mehr; das Geraet koppelt neu.
 *
 * Die Abfolge bleibt sonst unveraendert: Der Fehlerschluessel 'auth' deckt
 * beide Faelle ab und sagt nicht, welcher es war.
 */
$deviceId = (string)($_SERVER['HTTP_X_DEVICE_ID'] ?? '');
$apiKey   = (string)($_SERVER['HTTP_X_API_KEY']   ?? '');
/* Die Abfrage holt seit Web 14.0.0 zwei Spalten mehr: `geraet_art` und
 * `geraet_modell` wandern als MOMENTAUFNAHME an jeden Einsatz und jedes
 * Segment, das hier entsteht (R64). Sie kosten nichts — die Zeile wird
 * ohnehin gelesen —, und sie sind der einzige Augenblick, in dem die Angabe
 * ueberhaupt zu haben ist: Danach kann das Geraet getrennt werden (R47),
 * und `device_id` steht auf ON DELETE SET NULL. */
$st = db()->prepare('SELECT id, user_id, api_key_hash, active, geraet_art, geraet_modell
                     FROM devices WHERE device_id = ?');
$st->execute([$deviceId]);
$dev = $st->fetch();
if (!$dev) {
    geraet_schluessel_gueltig($apiKey, GERAET_VERGLEICHSWERT);
    json_out(['error' => 'auth'], 401);
}
if (!geraet_schluessel_gueltig($apiKey, (string)$dev['api_key_hash'])) json_out(['error' => 'auth'], 401);
if (!(int)$dev['active']) json_out(['error' => 'device_disabled'], 403);

/* Demo-Konto: faelliger Reset VOR der Verarbeitung (E-P1-18).
 *
 * Der zweite von zwei Ausloesepunkten — der andere steht in auth_guard.php
 * fuer Web-Anfragen. Ohne diesen hier bliebe ein Demo-Konto, mit dem nur
 * gekoppelte Uhren sprechen, beliebig lange im zuletzt hinterlassenen
 * Zustand stehen.
 *
 * ZUERST ZURUECKSETZEN, DANN AUFNEHMEN. Die Reihenfolge ist gewollt: Der
 * gerade eintreffende Upload gehoert zum NEUEN Fenster und soll nicht vom
 * Reset gleich wieder mitgenommen werden.
 *
 * Erst NACH der Geraetepruefung, damit eine unauthentifizierte Anfrage keinen
 * Reset ausloesen kann — sonst waere die Ruecksetzung ein Hebel fuer jeden,
 * der die Adresse kennt. */
require_once __DIR__ . '/demo_lib.php';
if (demo_ist_demo((int)$dev['user_id'])) { demo_reset_wenn_faellig(); }

// --- Nutzlast pruefen ---------------------------------------------------------
$b = json_decode($raw, true);
$pruef = new Pruefliste();

$kind = $b['kind'] ?? '';
$clientRef = pruef_text($b['client_ref'] ?? null, 64, 'client_ref', $pruef);

/* Der Kalendertag wird jetzt als KALENDERTAG geprueft, nicht nur als Muster.
 * Das alte Muster liess den 30. Februar durch; die Umwandlung haette ihn
 * anschliessend stillschweigend auf den 2. Maerz verschoben (B2). */
$day       = pruef_kalendertag($b['day'] ?? null, 'day', $pruef);
$startedAt = pruef_utc($b['started_at'] ?? null, 'started_at', $pruef);

/* Dienstkennung, falls die Uhr eine schickt (JSON-Vertrag 4.4). Sie ist
 * OPTIONAL und bleibt es: Fehlt sie, greift die Rueckfallebene ueber
 * (user_id, day). Geprueft wird sie wie `client_ref` — gleiches Muster, gleiche
 * Laenge, gleiche Idempotenz-Eigenschaft. Ein unbrauchbarer Wert verwirft den
 * WERT, nicht den Upload: Ohne Kennung landet der Einsatz auf dem Rueckfallweg
 * an einem Diensttag, statt gar nicht anzukommen. */
$dayRef = null;
if (($b['day_ref'] ?? null) !== null && $b['day_ref'] !== '') {
    $dayRef = pruef_text($b['day_ref'], 64, 'day_ref', $pruef);
}

/* Diese vier Angaben sind das Geruest des Datensatzes. Fehlt eines, ist die
 * Nachricht als Ganzes unbrauchbar — hier ist ein Abbruch richtig. */
if (!is_array($b) || $clientRef === null || $startedAt === null || $day === null
    || !in_array($kind, ['mission', 'rest_segment'], true)) {
    json_out(['error' => 'payload', 'grund' => $pruef->text()], 400);
}

$endedAt = pruef_utc($b['ended_at'] ?? null, 'ended_at', $pruef);

/* PASSEN DIE ZEITEN ZUM TAG? (zweite Gegenpruefung, Wiederaufnahme.)
 *
 * `day` und die Zeitpunkte kommen aus derselben Quelle, und bis hierher hat
 * niemand nachgesehen, ob sie einander widersprechen. Ein Paket mit
 * `day` 2026-08-09 und `started_at` 2001-01-01 lief durch, und der Zeitraum
 * des Diensttags wurde darauf gezogen -- ueber ingest.php nicht rueckholbar.
 *
 * DAS PAKET WIRD TROTZDEM ANGENOMMEN. Die erste Fassung dieser Pruefung hat
 * es mit 400 abgewiesen, und das war falsch: Es haette die falsch gestellte
 * Uhr ausgesperrt -- genau die, die Fund 2 der ersten Gegenpruefung wieder
 * hereingeholt hat. Ein Geraet, dessen Kalender nach einer Tiefentladung auf
 * 1970 steht, muss seine Daten loswerden koennen; sein Einsatz ist sichtbar
 * und loeschbar, und das ist die Zusage. Verworfen wird deshalb nicht der
 * Upload, sondern der WERT -- und zwar nur fuer das eine, wofuer er nicht
 * taugt: das Fortschreiben des Diensttags.
 *
 * Zwei Fragen entscheiden darueber. Passt der Zeitpunkt zu dem Kalendertag,
 * unter dem er gemeldet wird (pruef_zeit_zum_tag, Fenster sehr weit -- die
 * vier Gruende stehen dort)? Und liegt das Ende nach dem Beginn
 * (pruef_ende_nach_beginn)? Was durchfaellt, steht als `rejected` in der
 * Antwort: Der Upload war erfolgreich, aber nicht alles daran wurde
 * verwendet. */
$tagStart = pruef_zeit_zum_tag($startedAt, $day, 'started_at', $pruef) ? $startedAt : null;
$tagEnde  = $endedAt;
if (!pruef_zeit_zum_tag($tagEnde, $day, 'ended_at', $pruef)) { $tagEnde = null; }
if ($tagEnde !== null && !pruef_ende_nach_beginn($startedAt, $tagEnde, 'ended_at', $pruef)) {
    $tagEnde = null;
}

$final   = pruef_flag($b['final'] ?? null);

// Strecke und Steigung: bei Unsinn NULL statt 0 — eine 0 taeuschte eine
// Messung vor, die es nie gab, und landet in jeder Jahresstatistik.
$distanceM = pruef_zahl($b['distance_m'] ?? null, 0, 100000000, 'distance_m', $pruef);
$ascentM   = pruef_zahl($b['ascent_m']   ?? null, 0, 100000,    'ascent_m',   $pruef);

$points  = $b['track']['points'] ?? [];
$seqFrom = (int)($b['track']['seq_from'] ?? 0);
if ($seqFrom < 0) json_out(['error' => 'payload'], 400);
// Muss eine LISTE sein: Ein JSON-Objekt mit den Schluesseln "0", "1" wird in
// PHP zum selben Feldtyp und liefe sonst unbemerkt durch.
if (!ist_liste($points)) { json_out(['error' => 'payload'], 400); }
// Die Punkte EINER ANFRAGE (F-S2-02). Die Uhr sendet in Stuecken zu 500;
// 2000 sind vierfache Reserve und schuetzen vor einer entgleisten Nutzlast.
$points = pruef_menge($points, LIMIT_TRACKPUNKTE_ANFRAGE, 'track.points', $pruef);

/* Uebergangene Listen (M4-02). Bleibt leer, wenn nichts uebergangen wurde;
 * nur dann erscheinen die Felder kept_* in der Antwort. */
$behalten = [];

$pdo = db();
$pdo->beginTransaction();
try {
    /* ---- Sperrliste und Papierkorb: fuer BEIDE Arten ----------------------
     *
     * Diese beiden Pruefungen standen frueher INNERHALB des Einsatz-Zweigs und
     * galten damit nur fuer Einsaetze. Zwei Luecken auf dem Ruhe-Weg:
     *
     *   1. Ein endgueltig geloeschtes Ruhe-Segment wurde von der naechsten
     *      Nachlieferung wieder angelegt — und beim erneuten Loeschen wieder.
     *      Wer eine Uhr im Einsatz hat, kam aus dieser Schleife nicht heraus.
     *   2. Ein Segment im Papierkorb sammelte weiter Spurpunkte, weil auch die
     *      Papierkorb-Pruefung fehlte.
     *
     * Deshalb stehen sie jetzt VOR der Fallunterscheidung. Die Sperrliste
     * unterscheidet seit Web 4.0.0 ueber owner_type, welche Art gemeint ist.
     */
    $ownerTypePruef = $kind === 'mission' ? 'mission' : 'rest';

    // Im Web geloescht: Empfang bestaetigen, Daten aber verwerfen. Die Uhr
    // soll ihren Puffer freigeben duerfen — sonst versucht sie es endlos.
    $bl = $pdo->prepare('SELECT 1 FROM deleted_refs
                         WHERE device_id = ? AND owner_type = ? AND client_ref = ?');
    $bl->execute([$dev['id'], $ownerTypePruef, $clientRef]);
    if ($bl->fetchColumn()) {
        $pdo->commit();
        json_out(['ok' => true, 'id' => 0, 'stored_points' => 0,
                  'next_seq' => $seqFrom + count($points)]);
    }

    // Im Papierkorb: ebenfalls bestaetigen und verwerfen — sonst wuerde ein
    // geloeschter Datensatz durch Nachlieferungen wieder wachsen. Erst das
    // endgueltige Loeschen traegt ihn in die Sperrliste ein.
    $tabelle = $kind === 'mission' ? 'missions' : 'rest_segments';
    /* `created_at` gibt es bei rest_segments erst seit der Migration
     * 2026_09_07_rest_segments_created_at (Nr. 134). Zwischen Deploy und
     * update.php fehlt die Spalte -- und ein SELECT, der sie nennt, wirft
     * 1054 und antwortet 500, fuer JEDES Ruhesegment-Paket, auch ein neues
     * (zweite Gegenpruefung, Wiederaufnahme). Deshalb wird sie nur genannt,
     * wenn sie da ist; fehlt sie, faellt der Anker unten auf `started_at`
     * zurueck, wie der Kommentar dort verspricht. Eine Abfrage je Paket am
     * Informationsschema, nur fuer Ruhesegmente -- der Preis fuer einen
     * Deploy, der kein Paket verliert. */
    $hatCreated = $kind === 'mission' || ingest_hat_spalte($pdo, 'rest_segments', 'created_at');
    $chk = $pdo->prepare("SELECT id, day_id, deleted_at, started_at" . ($hatCreated ? ', created_at' : '')
                       . ($kind === 'mission' ? ', manual' : '')
                       . " FROM `$tabelle` WHERE device_id = ? AND client_ref = ?");
    $chk->execute([$dev['id'], $clientRef]);
    $existing = $chk->fetch();

    if ($existing && $existing['deleted_at'] !== null) {
        $pdo->commit();
        json_out(['ok' => true, 'id' => 0, 'stored_points' => 0,
                  'next_seq' => $seqFrom + count($points)]);
    }

    /* ---- Ersetzfenster (Backlog Nr. 134, K-14, F-SP-8) -------------------
     *
     * Ein BESTEHENDER Datensatz laesst sich nur INGEST_ERSETZFENSTER_H Stunden
     * lang von seinem Geraet veraendern -- gerechnet ab dem Augenblick, in
     * dem der Server ihn ZUM ERSTEN MAL GESEHEN hat (`created_at`). Nicht ab
     * dem gesendeten `started_at`: Den bestimmt der Absender, und genau der
     * ist hier der Unsichere.
     *
     * WARUM NICHT `started_at` (Nachbesserung 07.09.2026, Gegenpruefung des
     * Web-Teils, Funde 2 und 5): Auch das gespeicherte `started_at` stammt
     * beim Anlegen vom Geraet. Eine Uhr mit falsch gestellter Zeit (Reset
     * ohne Zeitabgleich) legte ihren Einsatz mit einem Datum von vor Jahren
     * an -- das Fenster war im selben Augenblick zu, der LAUFENDE Einsatz
     * verlor Punkte und Phasen, und weil `next_seq` weiterwanderte, loeschte
     * die Uhr sie als quittiert. Umgekehrt haette ein `started_at` in der
     * Zukunft das Fenster nie geschlossen.
     *
     * UND WARUM NICHT DAS SPAETERE AUS BEIDEN (zweite Gegenpruefung, auf die
     * Nachbesserung selbst): So stand es einen Nachmittag lang hier --
     * `max(started_at, created_at)`, ein `started_at` in der Zukunft zaehlt
     * nicht. Aber "in der Zukunft" wurde bei jedem Paket neu gegen jetzt
     * gerechnet: Ein `started_at`, das beim Anlegen 99 Stunden vorn lag,
     * zaehlte nicht, solange es vorn lag -- und wurde zum Anker, sobald die
     * Zeit es eingeholt hatte. Das laengst geschlossene Fenster ging dann
     * noch einmal fuer 72 Stunden auf, und zwar zu einem Zeitpunkt, den das
     * Geraet bestimmt hatte. Wendet man "Zukunft zaehlt nicht" dagegen auf
     * den Augenblick des Anlegens an, ist ein `started_at` spaeter als
     * `created_at` immer Zukunft, und das Spaetere aus beiden ist immer
     * `created_at`. Also steht es hier so: `created_at` ist der Anker. Es
     * kennt keine Geraeteuhr, weder eine nach- noch eine vorgehende.
     * `started_at` dient nur als Rueckfall, solange die Migration
     * `2026_09_07_rest_segments_created_at` nicht gelaufen ist -- und auch
     * dann nie spaeter als jetzt.
     *
     * Innerhalb des Fensters bleibt alles wie bisher, damit eine Nachlieferung
     * nach einem Funkloch ankommt. Danach: `ok` ohne Ersetzen, ohne Anhaengen,
     * ohne Fehler -- die Uhr wiederholte sonst endlos. Was uebergangen wurde,
     * steht in der Antwort (`kept_*`).
     *
     * NEUE Datensaetze sind nicht betroffen: `$existing` ist dann null. Eine
     * verlorene Uhr kann also weiterhin Einsaetze ANLEGEN -- die sind sichtbar
     * und loeschbar und ueberschreiben nichts. Der Weg dagegen ist das Trennen
     * des Geraets (Handbuch 12). */
    $fensterZu = false;
    if ($existing) {
        $lies = static function (?string $wert): ?int {
            $t = $wert !== null ? strtotime($wert . ' UTC') : false;
            return $t === false ? null : $t;
        };
        $anker = $lies($existing['created_at'] ?? null)
              ?? $lies($existing['started_at'] ?? null);
        /* Nie spaeter als jetzt: `created_at` ist Serverzeit und liegt nie
         * vorn -- ausser eine Zeile hat es aus der Migration von einem
         * `started_at` geerbt, das vorn lag (die Migration kappt das, aber
         * der Boden steht auch hier). Und der Rueckfall `started_at` ist
         * Geraetezeit. Ein Anker in der Zukunft hielte das Fenster offen,
         * bis die Zukunft vorbei ist; gekappt schliesst es in 72 h.
         *
         * UND NIE FRUEHER ALS SEKUNDE 1 DER EPOCHE (zweite Gegenpruefung,
         * Wiederaufnahme): strtotime('1970-01-01 00:00:00') ist 0, und 0 sah
         * in der Fassung davor wie "kein Anker" aus -- ein Segment mit
         * genau diesem Wert, dem einer Uhr ohne Zeitabgleich, hielt sein
         * Fenster im Rueckfall fuer immer offen, waehrend eines mit 00:00:01
         * nach 72 h zu war. "Kein Anker" ist jetzt null, nicht 0. */
        if ($anker !== null) { $anker = max(1, min($anker, time())); }
        $fensterZu = $anker !== null && (time() - $anker) > INGEST_ERSETZFENSTER_H * 3600;
    }

    /* ---- Diensttag bestimmen (JSON-Vertrag 4.4) ---------------------------
     *
     * VOR der Fallunterscheidung, weil beide Arten ihn brauchen — und nach den
     * Pruefungen oben, damit ein verworfener Upload keinen leeren Diensttag
     * hinterlaesst.
     *
     * Ein Datensatz, der bereits an einem Diensttag haengt, BEHAELT ihn. Das ist
     * dieselbe Zusicherung, auf die sich tz_einsatz_verschieben() stuetzt: Eine
     * Nachlieferung darf einen im Web umgehaengten Einsatz nicht zurueckziehen
     * (Akzeptanzkriterium 28). Beim Upsert unten steht `day_id` deshalb nur im
     * INSERT, nicht im ON DUPLICATE KEY UPDATE. */
    $vorhandenerDayId = ($existing && $existing['day_id'] !== null)
        ? (int)$existing['day_id'] : null;

    /* … ES SEI DENN, DIESER TAG LIEGT IM PAPIERKORB (Backlog Nr. 33).
     *
     * Dann greift die normale Zuordnung, und die uebergeht Papierkorbeintraege
     * (dt_rueckfall, dt_zu_dayref) — es entsteht also ein anderer oder ein
     * neuer Diensttag. Ohne diese Zeile schriebe die Nachlieferung einen
     * AKTIVEN Einsatz an einen GELOESCHTEN Tag, und der ist danach halb
     * sichtbar: in der Suche ja, in der Tagesuebersicht nein.
     *
     * Der Fall ist selten, weil trash_delete_day() alles mitmarkiert — er
     * braucht einen Datensatz, der NACH dem Loeschen des Tages aktiv wurde.
     * Genau solche Datensaetze gibt es aus aelteren Staenden noch. */
    if ($vorhandenerDayId !== null) {
        $vq = $pdo->prepare('SELECT id FROM days
                              WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $vq->execute([$vorhandenerDayId, (int)$dev['user_id']]);
        if ($vq->fetchColumn() === false) { $vorhandenerDayId = null; }
    }

    if ($fensterZu) {
        /* FENSTER ZU: Es wird nichts umgehaengt, also wird auch kein Diensttag
         * bestimmt. Sonst legte dt_rueckfall() einen neuen, LEEREN Tag an,
         * sobald der bisherige im Papierkorb liegt -- und der Datensatz
         * bliebe trotzdem am alten haengen (Gegenpruefung, Fund 4). Der Wert
         * wird unten nicht mehr gebraucht; er steht nur, damit kein Zweig
         * auf eine undefinierte Variable trifft. */
        $dayId = (int)($existing['day_id'] ?? 0);
    } elseif ($dayRef !== null) {
        $dayId = dt_zu_dayref($pdo, (int)$dev['user_id'], (int)$dev['id'], $dayRef,
                              $day, $startedAt, $vorhandenerDayId);
    } else {
        $dayId = $vorhandenerDayId
              ?? dt_rueckfall($pdo, (int)$dev['user_id'], $day, $startedAt);
    }

    if ($kind === 'mission') {
        // Manuell bearbeitete Einsaetze schuetzen: Uhr-Uploads duerfen
        // Metadaten/Phasen/Rea nicht mehr ueberschreiben; Trackpunkte werden
        // weiterhin ergaenzt (Append-only, unkritisch).
        if ($existing && ((int)$existing['manual'] === 1 || $fensterZu)) {
            /* Zwei Gruende, derselbe Weg: Der Einsatz ist im Web bearbeitet
               worden (`manual`), oder das Ersetzfenster ist zu (Nr. 134). In
               beiden Faellen bleiben Metadaten, Phasen und Reanimation stehen.
               Der Unterschied steht weiter unten: Bei `manual` werden Punkte
               weiterhin ANGEHAENGT (append-only, unkritisch), bei
               geschlossenem Fenster nicht -- dort ist der Absender der
               Unsichere, nicht der Inhalt. */
            $ownerId = (int)$existing['id'];
            $ownerType = 'mission';
            /* Was uebergangen wurde, NENNEN (Nr. 134, JSON-Vertrag 5). Der
               Zweig ueberspringt Metadaten, Phasen und Reanimation in einem;
               ohne diese Zahlen saehe der Upload wie ein Erfolg aus, und die
               ersetzten Phasen fehlten stillschweigend. Gezaehlt wird der
               VORHANDENE Stand -- das ist die Zahl, die stehen bleibt. */
            if (isset($b['phases']) && is_array($b['phases'])) {
                $zp = $pdo->prepare('SELECT COUNT(*) FROM mission_phases WHERE mission_id = ?');
                $zp->execute([$ownerId]);
                $behalten['kept_phases'] = (int)$zp->fetchColumn();
            }
            if (isset($b['resus_sessions']) || isset($b['resus'])) {
                $zr = $pdo->prepare('SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ?');
                $zr->execute([$ownerId]);
                $behalten['kept_resus'] = (int)$zr->fetchColumn();
            }
            /* AUCH DIE METADATEN WERDEN GENANNT (Gegenpruefung, Fund 3). Ein
             * echtes Abschlusspaket traegt weder Phasen noch Punkte, sondern
             * Ende, `final`, Strecke und Anstieg -- ohne dieses Feld saehe es
             * bei geschlossenem Fenster wie ein Erfolg aus, und der Einsatz
             * bliebe fuer immer "laeuft noch", ohne dass jemand es erfuehre.
             * `manual` nennt es nicht: Dort ist der Inhalt bewusst im Web
             * gesetzt, und das Geraet soll ihn nicht fuer verloren halten. */
            if ($fensterZu && ($endedAt !== null || $final || $distanceM !== null || $ascentM !== null)) {
                $behalten['kept_meta'] = 1;
            }
        } else {
        /* Upsert des Einsatzes (idempotent ueber device_id+client_ref)
         *
         * ---- COALESCE UND NICHT VALUES: EIN SPAETES PAKET DARF NICHTS
         *      LOESCHEN (Web 13.0.1) ------------------------------------------
         *
         * Bis 13.0.0 stand hier `ended_at = VALUES(ended_at)` und dasselbe fuer
         * distance_m und ascent_m — waehrend `final` schon immer mit GREATEST
         * geschuetzt war. Die drei Spalten sind aber genau die, die ein
         * NICHT-finales Paket gar nicht traegt: Solange der Einsatz laeuft,
         * kennt die Uhr weder Ende noch Strecke noch Anstieg und sendet null.
         *
         * Kommt so ein Paket NACH dem finalen an, setzte der Upsert alle drei
         * auf NULL zurueck — und `final` blieb wegen GREATEST auf 1. Uebrig
         * blieb ein abgeschlossener Einsatz ohne Ende, ohne Strecke, ohne
         * Anstieg. Kein Fehler, keine Meldung, die Antwort lautete "ok".
         *
         * DASS DAS VORKOMMT, IST KEIN GEDANKENSPIEL: Jede Wiederholung eines
         * frueheren Teilstuecks ist so ein Paket. Die Ingestprobe schickt
         * genau das seit S2 (Teil 3, "Wiederholung unterhalb n_original") —
         * sie hat nur nie hingesehen. Mit dem Nachsende-Speicher der
         * Handy-App (S5-Zusatz, E2) wird die Reihenfolge vollends
         * unzuverlaessig: Was beim Funkabriss liegenblieb, geht hinterher
         * heraus, und zwar nach dem, was inzwischen gesendet wurde.
         *
         * COALESCE(VALUES(x), x) heisst: Ein Wert ueberschreibt, ein NULL
         * laesst stehen. Eine Berichtigung bleibt damit moeglich (die Uhr
         * sendet ein anderes Ende, und das gilt), nur das Vergessen ist weg.
         * Der umgekehrte Fall — ein Ende soll wieder verschwinden — ist keiner:
         * `final` geht aus demselben Grund nie zurueck.
         *
         * Gefunden bei der Gegenlesung des S5-Zusatzes (B5.3), nachgestellt
         * gegen eine laufende Installation, seither Teil 7 der Ingestprobe. */
        /* HERKUNFT UND MOMENTAUFNAHME STEHEN NUR IM INSERT-TEIL, nicht im
         * Upsert (R64, E-R64-01/-05). Beides gilt "beim Anlegen" — wie
         * `origin` seit jeher, das hier bis Web 13.3.0 gar nicht vorkam und
         * still auf der Spaltenvorgabe 'watch' landete. Genau daher stammt der
         * Fehler, den dieses Paket behebt: Ein Einsatz vom Android-Handy trug
         * die Plakette "Uhr".
         *
         * WARUM NICHT AUCH IM UPSERT: Ein zweites Paket zum selben Einsatz
         * kaeme vom selben Geraet, brauchte also nichts zu aendern — es sei
         * denn, jemand hat das Geraet zwischendurch neu gekoppelt und anders
         * aufgeloest. Dann traegt der Einsatz weiter, was beim Anlegen galt.
         * Das IST die Momentaufnahme. */
        $pdo->prepare('INSERT INTO missions (user_id, device_id, client_ref, day_id, started_at, ended_at, distance_m, ascent_m, final, origin, geraet_art, geraet_modell)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE
                         ended_at   = COALESCE(VALUES(ended_at),   ended_at),
                         distance_m = COALESCE(VALUES(distance_m), distance_m),
                         ascent_m   = COALESCE(VALUES(ascent_m),   ascent_m),
                         final = GREATEST(final, VALUES(final)),
                         id = LAST_INSERT_ID(id)')
            ->execute([$dev['user_id'], $dev['id'], $clientRef, $dayId, $startedAt, $endedAt,
                       $distanceM, $ascentM, $final,
                       herkunft_ableiten($clientRef, $dev['geraet_art']),
                       $dev['geraet_art'], $dev['geraet_modell']]);
        $ownerId = (int)$pdo->lastInsertId();
        $ownerType = 'mission';
        ingest_tag_nachziehen($pdo, 'missions', $ownerId, $existing, $vorhandenerDayId, $dayId);

        /* ---- Phasenliste ersetzen — aber nur, wenn dabei nichts verlorengeht
         *      (M4-02, JSON-Vertrag 3.1) ------------------------------------
         *
         * WAS HIER FALSCH WAR
         * Die Bedingung lautete "Schluessel vorhanden und ein Feld". Eine
         * LEERE Liste besteht beide Pruefungen: Sie loeschte den vorhandenen
         * Stand und fuegte nichts ein. Aus "dazu sage ich nichts" und "es gibt
         * keine" wurde dasselbe — und die Antwort lautete "ok".
         *
         * Der Weg zu einer leeren Liste ist viel wahrscheinlicher ein Fehler
         * beim Aufbau der Nachricht als der Wunsch, eine dokumentierte Phase
         * wieder loszuwerden. Wer wirklich loeschen will, tut das im Web.
         *
         * WARUM ES NICHT BEI DER LEEREN LISTE BLEIBT
         * Eine halb aufgebaute Nachricht ist derselbe Fehler, nur unauffaellig:
         * Sie kommt mit drei Phasen an, wo acht stehen, und der Verlust faellt
         * niemandem auf. Die Uhr fuegt Phasen ausschliesslich HINZU
         * (Model.mc: setPhase() haengt an, ein erneutes Setzen ist eine
         * Korrektur und damit ein weiterer Eintrag) — eine kuerzere Liste kann
         * bei ihr also gar nicht entstehen. Die Regel kostet den einzigen
         * vorhandenen Client damit nichts und faengt beide Faelle.
         *
         * Gezaehlt wird nach der PRUEFUNG: Zehn Eintraege, von denen neun
         * unbrauchbar sind, sind ein Eintrag.
         *
         * MEHRFACHE EINTRAEGE DERSELBEN NUMMER BLEIBEN ERHALTEN — eine erneut
         * gesetzte Phase ist eine Korrektur, keine Dublette (JSON-Vertrag 3).
         */
        if (isset($b['phases']) && is_array($b['phases'])) {
            $phasen = pruef_menge($b['phases'], LIMIT_PHASEN, 'phases', $pruef);
            $neuePhasen = [];
            foreach ($phasen as $p) {
                if (!is_array($p)) { $pruef->melde('phases', 'kein Objekt'); continue; }
                $at = pruef_utc($p['at'] ?? null, 'phases.at', $pruef);
                $ph = pruef_phase($p['phase'] ?? null, 'phases.phase', $pruef);
                if ($at === null || $ph === null) continue;
                // Koordinaten geprueft: sie gehen nicht nur in die Karte, sondern
                // auch in die Hoehenberechnung des Einsatzorts ein.
                $neuePhasen[] = [$ph, $at,
                    pruef_breite($p['lat'] ?? null, 'phases.lat', $pruef),
                    pruef_laenge($p['lon'] ?? null, 'phases.lon', $pruef)];
            }
            $zaehl = $pdo->prepare('SELECT COUNT(*) FROM mission_phases WHERE mission_id = ?');
            $zaehl->execute([$ownerId]);
            $vorhandenePhasen = (int)$zaehl->fetchColumn();

            if (count($neuePhasen) >= $vorhandenePhasen) {
                $pdo->prepare('DELETE FROM mission_phases WHERE mission_id = ?')->execute([$ownerId]);
                $ins = $pdo->prepare('INSERT INTO mission_phases (mission_id, phase, occurred_at, lat, lon) VALUES (?,?,?,?,?)');
                foreach ($neuePhasen as $np) {
                    $ins->execute([$ownerId, $np[0], $np[1], $np[2], $np[3]]);
                }
            } else {
                // Behalten und NENNEN — sonst waere der uebergangene Upload von
                // einem uebernommenen nicht zu unterscheiden (JSON-Vertrag 5).
                // Der Grund hier ist immer derselbe: weniger Phasen als
                // gespeichert. Das geschlossene Ersetzfenster (Nr. 134) kommt
                // gar nicht bis hierher -- es faengt oben ab, VOR dem Upsert.
                $behalten['kept_phases'] = $vorhandenePhasen;
            }
        }

        // Reanimationen vollstaendig ersetzen (mehrere Sitzungen moeglich).
        // "resus_sessions" (Liste) ist aktuell; ein altes "resus"-Objekt wird
        // als Liste mit einem Eintrag behandelt.
        $sessions = null;
        if (isset($b['resus_sessions']) && is_array($b['resus_sessions'])) {
            $sessions = $b['resus_sessions'];
        } elseif (!empty($b['resus']) && is_array($b['resus'])) {
            $sessions = [$b['resus']];
        }
        if ($sessions !== null) {
            $sessions = pruef_menge($sessions, LIMIT_REA_SESSION, 'resus_sessions', $pruef);
            // Erst pruefen und sammeln, dann entscheiden — dieselbe Regel wie
            // bei den Phasen (M4-02). Verglichen werden SITZUNGEN; sie sind
            // die Eintraege dieser Liste.
            $neueSitzungen = [];
            foreach ($sessions as $sess) {
                if (!is_array($sess)) { $pruef->melde('resus_sessions', 'kein Objekt'); continue; }
                $rStart = pruef_utc($sess['started_at'] ?? null, 'resus.started_at', $pruef);
                if ($rStart === null) continue;
                $events = pruef_menge($sess['events'] ?? [], LIMIT_REA_EREIGN, 'resus.events', $pruef);
                $gepruefteEreignisse = [];
                foreach ($events as $ev) {
                    if (!is_array($ev)) { $pruef->melde('resus.events', 'kein Objekt'); continue; }
                    $at = pruef_utc($ev['at'] ?? null, 'resus.events.at', $pruef);
                    // 'beginn' wird nicht als Ereignis gefuehrt — der Beginn
                    // steckt in started_at der Sitzung (JSON-Vertrag 3.3).
                    $ty = pruef_reanimationsart($ev['type'] ?? null, 'resus.events.type', $pruef);
                    if ($at !== null && $ty !== null && $ty !== 'beginn') {
                        $gepruefteEreignisse[] = [$ty, $at];
                    }
                }
                $neueSitzungen[] = ['start' => $rStart, 'events' => $gepruefteEreignisse];
            }
            $zaehl = $pdo->prepare('SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ?');
            $zaehl->execute([$ownerId]);
            $vorhandeneSitzungen = (int)$zaehl->fetchColumn();

            if (count($neueSitzungen) >= $vorhandeneSitzungen) {
                $pdo->prepare('DELETE FROM resus_sessions WHERE mission_id = ?')->execute([$ownerId]);
                $insS = $pdo->prepare('INSERT INTO resus_sessions (mission_id, started_at) VALUES (?,?)');
                $insE = $pdo->prepare('INSERT INTO resus_events (session_id, type, occurred_at) VALUES (?,?,?)');
                foreach ($neueSitzungen as $ns) {
                    $insS->execute([$ownerId, $ns['start']]);
                    $sid = (int)$pdo->lastInsertId();
                    foreach ($ns['events'] as $ne) {
                        $insE->execute([$sid, $ne[0], $ne[1]]);
                    }
                }
            } else {
                $behalten['kept_resus'] = $vorhandeneSitzungen;
            }
        }
        }   // Ende: nicht-manueller Einsatz
    } else { // rest_segment
        /* COALESCE wie beim Einsatz oben (Web 13.0.1): Ein spaeter
         * eintreffendes nicht-finales Paket traegt kein Ende und darf keines
         * loeschen.
         *
         * WIE ES SICH ZEIGTE: Die Spurenseite eines Diensttags setzt die Zeile
         * eines Ruhe-Segments aus zwei unabhaengigen Angaben zusammen —
         * "12:00 – 13:00 Uhr" aus `ended_at`, und den Zusatz "· laeuft noch"
         * aus `final` (assets/schneiden.js 341-346). Nach dem Fehler standen
         * beide im Widerspruch: "12:00 – offen Uhr" OHNE "laeuft noch" — ein
         * Segment, das der Server fuer abgeschlossen haelt und dem trotzdem das
         * Ende fehlt. Wer die Seite las, sah einen Widerspruch ohne Ursache. */
        /* Momentaufnahme wie beim Einsatz — aber KEINE Herkunftsspalte
         * (E-R64-04): Gezaehlt werden Einsaetze, und woher ein Segment kommt,
         * sagt sein Praefix (`r-` gegen `ar-`). Eine Spalte, die niemand
         * abfragt, waere geschrieben und nie gelesen. */
        if ($existing && $fensterZu) {
            /* Ersetzfenster zu (Nr. 134): Der Stand bleibt, wie er ist -- und
             * ein Ende, das das Paket bringt, wird GENANNT, damit ein spaetes
             * Abschlusspaket nicht wie ein Erfolg aussieht (Gegenpruefung,
             * Fund 3; das Segment bliebe sonst still fuer immer offen). */
            $ownerId = (int)$existing['id'];
            if ($endedAt !== null || $final) { $behalten['kept_meta'] = 1; }
        } else {
            $pdo->prepare('INSERT INTO rest_segments (user_id, device_id, client_ref, day_id, started_at, ended_at, final, geraet_art, geraet_modell)
                           VALUES (?,?,?,?,?,?,?,?,?)
                           ON DUPLICATE KEY UPDATE
                             ended_at = COALESCE(VALUES(ended_at), ended_at),
                             final = GREATEST(final, VALUES(final)),
                             id = LAST_INSERT_ID(id)')
                ->execute([$dev['user_id'], $dev['id'], $clientRef, $dayId, $startedAt, $endedAt, $final,
                           $dev['geraet_art'], $dev['geraet_modell']]);
            $ownerId = (int)$pdo->lastInsertId();
            ingest_tag_nachziehen($pdo, 'rest_segments', $ownerId, $existing, $vorhandenerDayId, $dayId);
        }
        $ownerType = 'rest';
    }

    /* ---- Trackpunkte anhaengen (M4-06) ----------------------------------
     *
     * WARUM HIER KEIN "INSERT IGNORE" MEHR STEHT
     * IGNORE unterdrueckt nicht nur den Schluesselkonflikt, sondern JEDEN
     * Fehler dieser Anweisung. Gedacht war es fuer die Wiederholung: Laedt die
     * Uhr dieselben Punkte erneut hoch, sollen die bekannten Sequenznummern
     * stillschweigend uebergangen werden. Getan hat es mehr.
     *
     * Der Schaden ist dauerhaft, und das ist der Punkt. Die Fortsetzungsmarke,
     * die die Uhr zurueckbekommt, ist MAX(seq)+1. Ein Punkt, der beim
     * Einfuegen scheitert, hinterlaesst eine Luecke — die Marke springt
     * darueber hinweg, die Uhr setzt dahinter fort und sendet ihn NIE WIEDER.
     * Der Upload meldete dabei Erfolg. Aus einem vollstaendigen Flugweg wurde
     * ein Flugweg mit einem Loch, von dem niemand etwas erfuhr.
     *
     * Jetzt: Der Schluesselkonflikt wird weiterhin uebergangen — das ist die
     * Wiederholung, und die soll funktionieren. Jeder andere Fehler bricht den
     * Upload ab; die Transaktion wird zurueckgerollt und die Uhr versucht es
     * beim naechsten Mal erneut, mit derselben Fortsetzungsmarke wie zuvor.
     * Ein sichtbar gescheiterter Upload ist besser als ein stillschweigend
     * unvollstaendiger.
     *
     * Punkte, die an der WERTEPRUEFUNG scheitern, bleiben ein anderer Fall:
     * Sie werden gezaehlt und in 'rejected' benannt (seit Web 4.2.0). Sie
     * erneut zu senden brauchte niemand — sie wuerden wieder abgelehnt.
     */
    /* DER ZUSTAND DER SPUR, EINMAL — vor der Schleife (S2/AP3, E-S2-08).
     *
     * `spur_stand()` ERSETZT den bisherigen Aufruf von spur_naechste_seq()
     * weiter unten, er kommt nicht dazu: zwei Abfragen vorher, zwei nachher.
     * Gebraucht wird zusaetzlich die STUFE, und die entscheidet, was mit
     * eingehenden Punkten geschieht. */
    $stand = spur_stand($pdo, $ownerType, $ownerId);

    /* DIE SPERRVERMERKE, EBENFALLS EINMAL — vor der Schleife (S4/A2, E-S4-53).
     *
     * Wurde aus dieser Spur ein Einsatz geschnitten, sind die Punkte des
     * geschnittenen Zeitraums dorthin gewandert. Das Geraet weiss davon
     * nichts: Es hatte sie moeglicherweise noch im Puffer und liefert sie
     * jetzt nach. Ohne diese Pruefung faenden sie in die Quelle zurueck, und
     * der Schnitt loeste sich still wieder auf.
     *
     * `n_original` FAENGT DAS NICHT AB, auch wenn es auf den ersten Blick so
     * aussieht. Die Nummern vergibt die Schleife unten aus `seq_from` — der
     * Marke, die das Geraet zuletzt bekommen hat. Gepufferte Punkte kommen
     * deshalb OBERHALB der Sperrgrenze an und laufen glatt daran vorbei;
     * `n_original` faengt nur die Wiederholung schon gelieferter Punkte ab.
     * Was sie kenntlich macht, ist ihre `ts`.
     *
     * EINE ABFRAGE JE UPLOAD, nicht eine je Punkt. Das ist der heisseste
     * Schreibweg der Anwendung; eine Spur hat ueblicherweise null Vermerke,
     * und dann kostet die Pruefung in der Schleife einen Test gegen ein
     * leeres Feld. */
    $schnitte = schnitte_lesen($pdo, $ownerType, $ownerId);

    $stored = 0;
    $verworfen = 0;
    $gesperrt = 0;
    /* ---- Ersetzfenster: auch keine Punkte mehr (Backlog Nr. 134) ---------
     *
     * ANDERS ALS BEI `manual`. Dort werden Punkte weiter angehaengt, weil das
     * Anhaengen unkritisch ist -- der Inhalt ist bearbeitet, die Spur nicht.
     * Hier ist der ABSENDER der Unsichere: Ein Finder mit der Uhr in der Hand
     * schriebe sonst seine eigene Fahrt in die Spur eines drei Wochen alten
     * Einsatzes. Angehaengt wird deshalb nichts mehr.
     *
     * `next_seq` wandert trotzdem weiter (unten): Die Uhr soll aufhoeren zu
     * senden, nicht in einer Schleife haengen bleiben. Was uebergangen wurde,
     * steht als `kept_points` in der Antwort -- ein stiller Verlust waere
     * genau der Fehler, gegen den die uebrigen `kept_*`-Felder gebaut sind. */
    if ($points && $fensterZu) {
        $behalten['kept_points'] = count($points);
    } elseif ($points) {
        $ins = $pdo->prepare('INSERT INTO track_points (owner_type, owner_id, seq, lat, lon, ele, ts)
                              VALUES (?,?,?,?,?,?,?)');
        foreach ($points as $i => $pt) {
            $seq = $seqFrom + $i;

            /* DIE REGEL GILT JE PUNKT, NICHT JE ANFRAGE. Ein Teilstueck kann
             * die Grenze ueberschreiten; das Paket wird dann an n_original
             * geteilt.
             *
             *   seq <  n_original   Wiederholung — still uebergehen.
             *   seq >= n_original   Stufe 1/2: annehmen (Nachzuegler, E-S2-08)
             *                       Stufe 3:   verwerfen und zaehlen
             *
             * Der untere Teil ist kein Verlust: Die Punkte stehen im Blob.
             * Bislang fing das der Schluesselkonflikt ab — aber nur, solange
             * die Zeilen noch dastanden. Nach der Verdichtung gibt es keinen
             * Konflikt mehr, die Zeile wird angelegt und ist danach
             * unsichtbar (spur_lesen_viele() uebergeht sie, spur_zahlen()
             * zaehlt sie nicht) und belegt trotzdem 62,4 Byte. Ausgeloest von
             * einer Uhr, die ihre Marke verloren hat und ab 0 neu sendet.
             *
             * WICHTIG IST DIE STUFE, NICHT „hat einen Blob": Wer nur auf
             * „Blob vorhanden" prueft, wirft bei Stufe 2 genau die Punkte weg,
             * die der naechste Verdichtungslauf einarbeiten soll — und
             * quittiert sie, so dass die Uhr sie loescht. Unwiederbringlich. */
            if ($seq < $stand['n_original']) { continue; }

            /* GESCHNITTEN HEISST WEG (E-S4-53). Der Punkt gehoert in den
             * Einsatz, der aus dieser Spur herausgeschnitten wurde; dort
             * steht er bereits, denn der Schnitt hat ihn mitgenommen. Kaeme
             * er hier noch einmal an, laege er zweimal.
             *
             * `ts` LIEGT IN $pt[3] UND IST HIER NOCH UNGEPRUEFT. Das ist in
             * Ordnung: Ein Wert, der keine Zahl ist, wird zu 0 und faellt
             * damit aus jedem Sperrbereich heraus — er laeuft weiter in die
             * Wertepruefung unten und wird dort behandelt wie bisher. Die
             * Sperre entscheidet also nie ueber einen Punkt, den sie nicht
             * versteht.
             *
             * QUITTIERT WIRD TROTZDEM. Der Punkt zaehlt nicht als
             * gespeichert, aber die Fortsetzungsmarke wandert ueber ihn
             * hinweg (sie ist mindestens `seq_from` + Punktzahl). Sonst
             * liefert das Geraet endlos nach — dieselbe Regel wie bei der
             * Sperrliste `deleted_refs`. */
            if ($schnitte && is_array($pt) && count($pt) >= 4
                && schnitt_gesperrt($schnitte, (int)$pt[3])) {
                $gesperrt++;
                continue;
            }

            if (spur_ist_ausgeduennt($stand)) {
                /* Die Wertepruefung laeuft hier BEWUSST NICHT. Sonst landeten
                 * planmaessig verworfene Punkte mit krummen Koordinaten in
                 * 'rejected' und liessen einen normalen Vorgang wie einen
                 * Datenfehler aussehen. */
                $verworfen++;
                continue;
            }

            if (!is_array($pt) || count($pt) < 4) {
                $pruef->melde('track.points', 'kein Punkt aus vier Werten');
                continue;
            }
            // Ein Punkt ohne brauchbare Koordinaten ist kein Punkt. Frueher
            // wurde er mit (float)"Unfug" = 0.0 gespeichert — als Position im
            // Golf von Guinea, mitten in der Flugspur.
            $la = pruef_breite($pt[0], 'track.lat', $pruef);
            $lo = pruef_laenge($pt[1], 'track.lon', $pruef);
            if ($la === null || $lo === null) { continue; }
            try {
                $ins->execute([$ownerType, $ownerId, $seq, $la, $lo,
                    $pt[2] === null ? null : (float)$pt[2], (int)$pt[3]]);
                $stored += $ins->rowCount();
            } catch (PDOException $ex) {
                // Diese Sequenznummer gibt es schon: erneuter Upload derselben
                // Punkte. Genau dafuer war IGNORE gedacht.
                if (!ist_dublettenfehler($ex)) { throw $ex; }
            }
        }
    }

    /* DIE ANKUNFTSZEIT (S2/AP3, Grundlage der Karenz aus E-S2-06).
     *
     * `$stored > 0` und nicht `count($points) > 0`: Eine reine Wiederholung
     * schon gespeicherter Punkte laeuft in den Dublettenzweig und zaehlt
     * nicht. Sonst hielte eine Uhr, die endlos dasselbe Teilstueck
     * wiederholt, ihre Einsaetze dauerhaft aus der Verdichtung heraus.
     *
     * Eine eigene Anweisung statt eines Feldes im Upsert: Der missions-Upsert
     * wird bei manual = 1 komplett uebersprungen, Punkte werden aber weiter
     * angenommen. Hier hinter der Schleife sind alle Wege abgedeckt. */
    if ($stored > 0) {
        $tabelle = $ownerType === 'mission' ? 'missions' : 'rest_segments';
        $pdo->prepare("UPDATE `$tabelle` SET letzter_punkt_am = UTC_TIMESTAMP() WHERE id = ?")
            ->execute([$ownerId]);
    }

    /* ---- Zeitraum des Diensttags fortschreiben (JSON-Vertrag 4.4) ---------
     *
     * Der Diensttag traegt echte Start- und Endzeiten. Die Uhr sendet sie nicht
     * eigens — sie sendet Einsaetze und Ruhe-Segmente, und der Dienst ist das,
     * was sie umschliesst. `started_at` wandert deshalb nur nach vorne,
     * `ended_at` nur nach hinten (siehe dt_zeitraum_fortschreiben()).
     *
     * Das gilt AUCH fuer einen Diensttag, dessen Zeitraum die Migration
     * gesetzt hat, und auch fuer einen von Hand angelegten: Ein Einsatz, der
     * um 00:40 des Folgetags endet, verlaengert den Dienst bis dahin — genau
     * der Fall, den der Testbestand als "Dienst ueber Mitternacht" fuehrt.
     *
     * NICHT BEI GESCHLOSSENEM FENSTER (Gegenpruefung, Fund 1): Die Zeiten
     * kommen vom Absender, und der ist dann der Unsichere. Bis zur
     * Nachbesserung lief diese Zeile unbedingt -- ein Paket mit
     * started_at 2001 und ended_at 2097 liess den Einsatz stehen, schrieb
     * aber den Diensttag um, und ueber ingest.php ist das nicht rueckholbar
     * (started_at wandert nur nach vorn, ended_at nur nach hinten).
     *
     * UND NICHT AN EINEM DIENSTTAG, AN DEM NICHT MEHR GEARBEITET WIRD
     * (zweite Gegenpruefung, Wiederaufnahme): Das Fenster oben gilt nur fuer
     * einen BESTEHENDEN Datensatz. Ein Paket mit NEUEM client_ref hat keinen,
     * wird ueber `day` oder `day_ref` auf den alten Tag aufgeloest -- und
     * schrieb dessen Zeitraum genauso um, derselbe Schaden auf dem anderen
     * Weg. Deshalb fragt ingest_tag_offen(), wann die uebrigen Datensaetze
     * des Tages ANGELEGT wurden: Serverzeit, nicht die Zeiten des Absenders.
     * Ein frischer Tag und ein Tag, an dem gerade nachgetragen wird, sind
     * offen; ein Tag, dessen Datensaetze alle aelter als das Fenster sind,
     * nicht. Der neue Datensatz wird trotzdem angelegt -- sichtbar,
     * loeschbar, und er ueberschreibt nichts.
     *
     * Die erste Fassung fragte den Tag nach SEINEN Zeiten, und die kommen
     * vom Absender: Ein Dienst, der spaeter als das Fenster nach seinem
     * Datum hochgeladen wurde (Uhr lange ohne Netz), bekam damit nie ein
     * `ended_at`. Gemessen an der eigenen Probe, nicht vermutet -- und der
     * Grund, warum der Anker jetzt am Anlegen haengt. Was absurde Zeiten
     * angeht, sitzt der Schutz ohnehin frueher: pruef_zeit_zum_tag() weist
     * ein Paket ab, dessen Zeiten nicht zu seinem `day` passen. */
    $eigeneTabelle = $ownerType === 'mission' ? 'missions' : 'rest_segments';
    if (!$fensterZu && ingest_tag_offen($pdo, $dayId, $eigeneTabelle, (int)$ownerId)) {
        dt_zeitraum_fortschreiben($pdo, $dayId, $tagStart, $tagEnde);
    }

    /* DIE FORTSETZUNGSMARKE UEBER spur_lib.php (S2/AP1).
     *
     * Bis Web 9.14.0 stand hier `MAX(seq)+1` ueber die Zeilen. Sobald die
     * Punkte einer abgeschlossenen Spur im Blob liegen, gibt es diese Zeilen
     * nicht mehr — die Marke fiele auf 0 zurueck, und die Uhr saendte den
     * ganzen Dienst noch einmal. spur_naechste_seq() nimmt deshalb das
     * Groessere aus `n_original` des Blobs und der hoechsten Zeilennummer.
     *
     * Fuer die Uhr ist das ununterscheidbar vom bisherigen Verhalten; der
     * JSON-Vertrag bleibt unveraendert (E-S2-08).
     *
     * DIE UNTERGRENZE `seq_from + Zahl der gesendeten Punkte` ist mit AP3
     * dazugekommen und gilt ALLGEMEIN, nicht nur nach der Ausduennung
     * (E-S2-25). Sie ist zugleich die Behebung eines vorhandenen Fehlers:
     * Scheiterte der LETZTE Punkt eines Teilstuecks an der Wertepruefung,
     * meldete der Server eine Marke kleiner als die Punktzahl des Pakets —
     * und die Uhr raeumt erst bei `next_seq >= pointCount` auf
     * (watch/source/Uploader.mc). Sie sandte dasselbe Stueck endlos.
     *
     * Der Preis, offen gesagt: Ein an der Wertepruefung gescheiterter Punkt
     * kann danach nicht mehr berichtigt nachkommen. Fuer jeden Punkt AUSSER
     * dem letzten eines Teilstuecks galt das ohnehin schon — die Aenderung
     * macht das Verhalten einheitlich, nicht schlechter.
     *
     * Gezaehlt wird NACH pruef_menge(): Mit der Rohzahl quittierte der Server
     * Punkte, die er nie gesehen hat. */
    $nextSeq = max(spur_naechste_seq($pdo, $ownerType, $ownerId),
                   $seqFrom + count($points));

    $pdo->prepare('UPDATE devices SET last_seen = NOW() WHERE id = ?')->execute([$dev['id']]);
    $pdo->commit();

    if ($kind === 'mission' && $ownerType === 'mission') {
        try {
            require_once __DIR__ . '/site_elevation_lib.php';
            compute_site_elevation($pdo, $ownerId);
        } catch (Throwable $ex) {
            // Hoehe ist ein Komfortwert; ein Fehler hier darf den Upload von
            // der Uhr nicht gefaehrden (bewusst still, wie run_cleanup_if_due).
        }
    }

    run_cleanup_if_due();   // taegliche Wartung, huckepack auf Uhr-Uploads

    /* Verworfene Werte NENNEN.
     *
     * 'ok' => true mit gefuelltem 'rejected' heisst: angekommen, aber nicht
     * vollstaendig uebernommen. Ohne diese Angabe waere ein verworfener Wert
     * von einem uebernommenen nicht zu unterscheiden — der Upload meldete
     * Erfolg, und die Phase fehlte trotzdem. Das Feld erscheint nur, wenn es
     * etwas zu berichten gibt (JSON-Vertrag, Abschnitt 5). */
    $antwort = ['ok' => true, 'id' => $ownerId,
                'stored_points' => $stored, 'next_seq' => $nextSeq];
    /* Verworfene Punkte NENNEN, aber nicht in 'rejected' (S2/AP3, E-S2-08).
     *
     * 'rejected' haengt an $pruef->sauber() und bedeutet laut Vertrag
     * „verworfene Einzelwerte", also einen Fehler in den Daten. Ein
     * planmaessiges Verwerfen dort einzutragen liesse jeden Upload einer
     * ausgeduennten Spur wie einen Datenfehler aussehen. Das Feld erscheint
     * wie die anderen nur, wenn es etwas zu berichten gibt. */
    if ($verworfen > 0) {
        $antwort['dropped_points'] = $verworfen;
    }
    /* Punkte in einem geschnittenen Bereich NENNEN (S4/A2, Konzept 14,
     * offener Punkt 3).
     *
     * EIGENES FELD UND NICHT `dropped_points`: Dort steht die Ausduennung —
     * „diese Spur ist fertig verdichtet". Hier steht etwas anderes: „diesen
     * Zeitraum hat jemand herausgeschnitten". Beides in einen Zaehler zu
     * legen hiesse, in der Fehlersuche nicht mehr unterscheiden zu koennen,
     * ob eine Spur verdichtet oder beschnitten wurde.
     *
     * KEINE VERTRAGSAENDERUNG: Der Client muss damit nichts tun. Das Feld
     * erscheint wie die anderen nur, wenn es etwas zu berichten gibt. */
    if ($gesperrt > 0) {
        $antwort['cut_points'] = $gesperrt;
    }
    if (!$pruef->sauber()) {
        $antwort['rejected'] = $pruef->nachUrsache();
    }
    /* Eine uebergangene Liste ist genauso zu nennen wie ein verworfener Wert
     * (M4-02): Der vorhandene Stand blieb, die gesendete Liste wurde NICHT
     * uebernommen. Ohne diese Angabe sieht das aus wie ein Erfolg. */
    foreach ($behalten as $feld => $zahl) { $antwort[$feld] = $zahl; }
    json_out($antwort);
} catch (Throwable $ex) {
    $pdo->rollBack();
    /* Kennung statt Schweigen (M3-10/M4-06).
     *
     * Die Uhr zeigt nur, DASS der Upload scheiterte — mehr braucht sie auch
     * nicht. Wer der Ursache nachgehen will, hat jetzt aber eine: Der volle
     * Text steht im Fehlerprotokoll des Webspace unter dieser Kennung.
     *
     * Seit M4-06 landet hier auch ein gescheitertes Einfuegen von Spurpunkten.
     * Der Rollback ist dabei wesentlich: Die Fortsetzungsmarke bleibt, wo sie
     * war, und die Uhr sendet dieselben Punkte beim naechsten Versuch erneut. */
    json_out(['error' => 'server', 'kennung' => fehler_kennung($ex, 'ingest')], 500);
}
