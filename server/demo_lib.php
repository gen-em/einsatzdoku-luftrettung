<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup_lib.php';
require_once __DIR__ . '/trash_lib.php';

/**
 * Demo-Konto (Baustein B14, Phase P1).
 *
 * WOFUER ES DAS GIBT
 * Ein Konto, in dem sich die Anwendung ohne Anmeldehuerde ausprobieren
 * laesst: Zugangsdaten oeffentlich, Daten frei erfunden, Aenderungen
 * erwuenscht — und alle 30 Minuten wieder auf den Ausgangsstand.
 *
 * DIE AUSNAHME, DIE HIER GEMACHT WIRD, UND IHRE GRENZE
 * Das Projekt verspricht Ende-zu-Ende-Verschluesselung: Der Server sieht die
 * geschuetzten Angaben nie im Klartext, und das Schluesselmaterial haengt am
 * Passwort. Fuer DIESES eine Konto gilt das nicht — sein Schluesselmaterial
 * liegt in der Fixture auf dem Server, damit ein Reset die Chiffretexte
 * wieder lesbar macht.
 *
 * Das ist eine bewusste, eng gezogene Ausnahme (E-P1-09) und nur deshalb
 * vertretbar, weil:
 *   - das Konto ausschliesslich erfundene Daten traegt,
 *   - es die Rolle `user` hat und niemals `admin`,
 *   - seine Zugangsdaten ohnehin oeffentlich sind — es gibt nichts zu
 *     schuetzen, was nicht schon offen laege,
 *   - jede Funktion hier ausschliesslich auf dem in `app_state` vermerkten
 *     Konto arbeitet und auf keinem anderen.
 *
 * Der letzte Punkt ist der wichtigste und wird an jeder Stelle erzwungen,
 * nicht nur zugesichert: `demo_id()` ist die einzige Quelle der Kontokennung,
 * und keine Funktion nimmt eine von aussen entgegen.
 *
 * WARUM DER RESET DAS SCHLUESSELMATERIAL MITSCHREIBT
 * Damit selbst eine unerwartet gelungene Aenderung der Konto-Identitaet
 * folgenlos bleibt. Die Sperren in E-P1-19 sind die erste Linie; dass der
 * Reset Passwort, Salz und Schluesselhuellen ohnehin ueberschreibt, ist die
 * zweite. Zwei Linien, weil die erste an einem einzigen vergessenen Endpunkt
 * haengen koennte.
 *
 * WARUM KEIN ZWEITER EINSPIELWEG
 * Der Bestand wird ueber `edbak_restore()` eingespielt — dieselbe Routine wie
 * bei der Wiederherstellung einer Sicherung, mit derselben Pruefung. Ein
 * eigener Weg haette eigene Fehler, und ausgerechnet der Weg, der am
 * haeufigsten laeuft, waere der ungeprueftere. Der Chiffretext wandert dabei
 * UNVERAENDERT durch: `edbak_restore()` nimmt `pat_blob` als Spalte entgegen,
 * ein Browser ist nicht beteiligt, und weil das Schluesselmaterial aus
 * derselben Fixture kommt, passt beides zusammen.
 */

/** Abstand zwischen zwei selbsttaetigen Ruecksetzungen. */
const DEMO_RESET_SEKUNDEN = 1800;

/** Schluessel in `app_state`. */
const DEMO_K_USER  = 'demo_user_id';
const DEMO_K_RESET = 'demo_letzter_reset';

/** Pfad der Fixture. Liegt unter server/, weil der Produktivserver sie
 *  braucht — alles Uebrige der Phase P1 liegt unter tools/ (E-P1-07).
 *  GEPACKT, weil sie roh rund 11 MB misst (im Wesentlichen Spurpunkte) und
 *  bei jedem Deploy ueber FTPS mitgeht. */
function demo_fixture_pfad(): string { return __DIR__ . '/demo/fixture.json.gz'; }

function demo_fixture_vorhanden(): bool { return is_file(demo_fixture_pfad()); }

/**
 * Fixture lesen und auf Brauchbarkeit pruefen.
 *
 * Die Pruefung ist knapp, aber nicht keine: Eine unvollstaendige Fixture
 * wuerde ein Konto ohne Schluesselmaterial anlegen — und das faellt erst auf,
 * wenn jemand sich anmeldet und nichts lesen kann.
 */
function demo_fixture_laden(): array
{
    $pfad = demo_fixture_pfad();
    if (!is_file($pfad)) {
        throw new RuntimeException('Keine Demo-Fixture unter server/demo/fixture.json.');
    }
    $roh = file_get_contents($pfad);
    if ($roh === false) {
        throw new RuntimeException('server/demo/fixture.json.gz ist nicht lesbar.');
    }
    /* Auch ungepackt annehmen: Wer die Datei zum Nachsehen entpackt und so
       ablegt, soll keinen Fehler bekommen, den er nicht versteht. */
    if (substr($roh, 0, 2) === "\x1f\x8b") {
        $entpackt = @gzdecode($roh);
        if ($entpackt === false) {
            throw new RuntimeException('server/demo/fixture.json.gz laesst sich nicht entpacken.');
        }
        $roh = $entpackt;
    }
    $fx = json_decode((string)$roh, true);
    if (!is_array($fx) || ($fx['format'] ?? '') !== 'einsatzdoku-demo-fixture') {
        throw new RuntimeException('server/demo/fixture.json hat nicht das erwartete Format.');
    }
    foreach (['konto', 'daten'] as $pflicht) {
        if (!is_array($fx[$pflicht] ?? null)) {
            throw new RuntimeException("Fixture unvollstaendig: '$pflicht' fehlt.");
        }
    }
    foreach (['email', 'password_hash', 'kdf_salt', 'kdf_iter', 'pat_wrap_pw'] as $pflicht) {
        if (($fx['konto'][$pflicht] ?? null) === null) {
            throw new RuntimeException("Fixture unvollstaendig: konto.$pflicht fehlt.");
        }
    }
    return $fx;
}

/** Kennung des Demo-Kontos, oder null. EINZIGE Quelle dieser Kennung. */
function demo_id(): ?int
{
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([DEMO_K_USER]);
        $v = $st->fetchColumn();
        if ($v === false || $v === null || (int)$v <= 0) { return null; }
        // Gegenprobe: Steht das Konto ueberhaupt noch? Eine verwaiste Kennung
        // waere schlimmer als keine — sie zeigte auf eine spaeter neu
        // vergebene ID und damit auf ein fremdes Konto.
        $q = db()->prepare('SELECT id FROM users WHERE id = ?');
        $q->execute([(int)$v]);
        return $q->fetchColumn() === false ? null : (int)$v;
    } catch (Throwable $ex) {
        return null;   // app_state fehlt (Migration noch nicht gelaufen)
    }
}

/**
 * Gehoert diese E-Mail-Adresse dem Demo-Konto?
 *
 * Fuer die Anmeldeseite: Dort ist zum Zeitpunkt der Mengenbremse noch nicht
 * nachgeschlagen, WER sich anmeldet — und das soll so bleiben, damit der
 * Zweig „Adresse unbekannt" nicht schneller ist als der andere.
 *
 * Eine Abfrage statt eines Vergleichs mit der Fixture: Die Adresse steht in
 * der Kontozeile, und nur die zaehlt. Waere sie dort eine andere als in der
 * Fixture, griffe die Bremse sonst am falschen Konto.
 */
function demo_ist_demo_adresse(string $email): bool
{
    $id = demo_id();
    if ($id === null || $email === '') { return false; }
    $st = db()->prepare('SELECT 1 FROM users WHERE id = ? AND LOWER(email) = LOWER(?)');
    $st->execute([$id, $email]);
    return $st->fetchColumn() !== false;
}

/** Ist DIESES Konto das Demo-Konto? */
function demo_ist_demo(?int $userId): bool
{
    if ($userId === null || $userId <= 0) { return false; }
    return demo_id() === $userId;
}

/** Zeitpunkt des letzten Resets als Unix-Sekunden, oder 0. */
function demo_letzter_reset(): int
{
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([DEMO_K_RESET]);
        $v = $st->fetchColumn();
        return $v === false || $v === null ? 0 : (int)$v;
    } catch (Throwable $ex) {
        return 0;
    }
}

function demo_reset_marke_setzen(?int $wann = null): void
{
    db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE v = VALUES(v)')
        ->execute([DEMO_K_RESET, (string)($wann ?? time())]);
}

/** Sekunden bis zum naechsten faelligen Reset (0 = jetzt faellig). */
function demo_reset_in(): int
{
    $rest = DEMO_RESET_SEKUNDEN - (time() - demo_letzter_reset());
    return $rest > 0 ? $rest : 0;
}

/**
 * Anfragegetriebener Reset — das Muster der vorhandenen Aufraeumjobs (B-13).
 *
 * ZUERST ZURUECKSETZEN, DANN ANTWORTEN. Wer nach laengerer Ruhe kommt, soll
 * den Ausgangsstand sehen und nicht die Hinterlassenschaft der letzten
 * Besucherin. Die Hoechstdrift ist damit 30 Minuten RELATIV ZU JEDER
 * Aktivitaet; ein Zeitdienst wird nicht vorausgesetzt.
 *
 * Aufzurufen an genau zwei Stellen: bei Web-Anfragen des Demo-Kontos
 * (auth_guard.php) und bei `ingest.php` von einem Demo-Geraet.
 *
 * Scheitert still gegenueber der Anfrage — eine Wartung darf keine Seite
 * kaputtmachen —, aber nicht spurlos: Der Grund landet im Fehlerprotokoll.
 */
function demo_reset_wenn_faellig(): bool
{
    $id = demo_id();
    if ($id === null || demo_reset_in() > 0) { return false; }
    try {
        // Marke ZUERST: verhindert, dass zwei gleichzeitige Anfragen beide
        // zuruecksetzen. Dasselbe Vorgehen wie in run_cleanup_if_due().
        demo_reset_marke_setzen();
        demo_zuruecksetzen();
        return true;
    } catch (Throwable $ex) {
        error_log('demo: Reset fehlgeschlagen: ' . $ex->getMessage());
        return false;
    }
}

/* ------------------------------------------------------------------ Anlegen */

/**
 * Demo-Konto anlegen. Verlangt, dass es noch keines gibt.
 *
 * Die E-Mail-Adresse kommt aus der Fixture — sie gehoert zum
 * Schluesselmaterial: `auth_salt.php` und die Ableitung des Anmeldetokens
 * haengen an ihr, ein anderer Wert machte den gespeicherten Hash wertlos.
 */
function demo_anlegen(): array
{
    $fx = demo_fixture_laden();
    $pdo = db();

    if (demo_id() !== null) {
        throw new RuntimeException('Es gibt bereits ein Demo-Konto. '
            . 'Zum Erneuern „Auf Standard zurücksetzen" verwenden.');
    }
    $k = $fx['konto'];
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([(string)$k['email']]);
    if ($st->fetchColumn() !== false) {
        throw new RuntimeException('Ein Konto mit der Adresse ' . (string)$k['email']
            . ' besteht bereits, ist aber nicht als Demo-Konto gekennzeichnet. '
            . 'Bitte zuerst im Adminbereich entfernen.');
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO users (email, name, password_hash, kdf_salt, kdf_iter,
                                          pat_wrap_pw, pat_wrap_rc, pat_key_check,
                                          role, account_key)
                       VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                (string)$k['email'], $k['name'] ?? null, (string)$k['password_hash'],
                $k['kdf_salt'] ?? null, (int)($k['kdf_iter'] ?? 0),
                $k['pat_wrap_pw'] ?? null, $k['pat_wrap_rc'] ?? null,
                $k['pat_key_check'] ?? null,
                'user',                      // NIEMALS admin (E-P1-09)
                $k['account_key'] ?? null,
            ]);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute([DEMO_K_USER, (string)$id]);
        $stats = demo_bestand_einspielen($pdo, $id, $fx);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    $stats['papierkorb'] = demo_nachlauf($id, (array)($fx['nachlauf'] ?? []));
    demo_reset_marke_setzen();
    return ['user_id' => $id] + $stats;
}

/* ------------------------------------------------------------- Zuruecksetzen */

/**
 * Demo-Konto auf den Ausgangsstand bringen.
 *
 * Loescht ALLES, was am Konto haengt — auch, was Besucher angelegt haben:
 * Geraete, Kopplungscodes, Papierkorb, Sperrlisteneintraege. Und spielt
 * anschliessend die Fixture erneut ein, EINSCHLIESSLICH Konto- und
 * Schluesselmaterial.
 *
 * Wirkt ausschliesslich auf das in `app_state` vermerkte Konto. Die Kennung
 * kommt aus `demo_id()` und wird nicht von aussen entgegengenommen — die
 * Funktion kann kein anderes Konto treffen, auch nicht bei falschem Aufruf.
 */
function demo_zuruecksetzen(): array
{
    $id = demo_id();
    if ($id === null) {
        throw new RuntimeException('Kein Demo-Konto vermerkt — nichts zurückzusetzen.');
    }
    $fx = demo_fixture_laden();
    $pdo = db();

    $pdo->beginTransaction();
    try {
        demo_bestand_loeschen($pdo, $id);

        /* Konto- und Schluesselmaterial ueberschreiben. `session_epoch` wird
         * hochgezaehlt: Offene Sitzungen im Demo-Konto enden damit, und wer
         * gerade mitten in einer Aenderung war, bekommt keinen halben
         * Zustand serviert. */
        $k = $fx['konto'];
        $pdo->prepare('UPDATE users SET email = ?, name = ?, password_hash = ?,
                              kdf_salt = ?, kdf_iter = ?, pat_wrap_pw = ?,
                              pat_wrap_rc = ?, pat_key_check = ?, account_key = ?,
                              role = \'user\', session_epoch = session_epoch + 1
                       WHERE id = ?')
            ->execute([
                (string)$k['email'], $k['name'] ?? null, (string)$k['password_hash'],
                $k['kdf_salt'] ?? null, (int)($k['kdf_iter'] ?? 0),
                $k['pat_wrap_pw'] ?? null, $k['pat_wrap_rc'] ?? null,
                $k['pat_key_check'] ?? null, $k['account_key'] ?? null, $id,
            ]);

        $stats = demo_bestand_einspielen($pdo, $id, $fx);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
    $stats['papierkorb'] = demo_nachlauf($id, (array)($fx['nachlauf'] ?? []));
    demo_reset_marke_setzen();
    return $stats;
}

/**
 * Alle Bestaende des Demo-Kontos entfernen.
 *
 * Die Reihenfolge folgt den Fremdschluesseln. Das Schema raeumt vieles selbst
 * ab (ON DELETE CASCADE): mission_crew, mission_phases, mission_resources,
 * resus_sessions/-events, day_crew, day_capabilities, day_refs,
 * vehicle_roles, vehicle_capabilities. Ausdruecklich geloescht werden muss,
 * was KEIN Fremdschluessel traegt:
 *
 *   track_points   polymorph (owner_type/owner_id), ohne Verweis
 *   deleted_refs   haengt an der Geraetekennung, ohne Verweis
 *
 * `user_defaults` faellt mit: Vorbelegungen sind Bestand, und ein Besucher
 * kann sie aendern.
 */
function demo_bestand_loeschen(PDO $pdo, int $id): void
{
    /* DER RIEGEL STEHT HIER, NICHT NUR BEI DEN AUFRUFERN.
     *
     * Die drei nach aussen gedachten Funktionen (anlegen, zuruecksetzen,
     * entfernen) nehmen gar keine Kennung entgegen — sie holen sie aus
     * `app_state`. Diese hier bekommt eine, weil sie in derselben Transaktion
     * laufen muss wie ihr Aufrufer, und PHP kennt keine paketprivaten
     * Funktionen: Sie ist damit von ueberall aufrufbar.
     *
     * Eine Funktion, die den gesamten Bestand eines Kontos loescht, darf sich
     * nicht darauf verlassen, dass ihre Aufrufer aufpassen. Der Vergleich
     * kostet eine Abfrage und macht aus einer Zusage eine Eigenschaft. */
    if (!demo_ist_demo($id)) {
        throw new RuntimeException(
            'demo_bestand_loeschen() arbeitet ausschliesslich auf dem in '
            . 'app_state vermerkten Demo-Konto.');
    }

    // Spurpunkte zuerst: Danach sind ihre Eigentuemer weg und sie waeren
    // nicht mehr auffindbar (verwaiste Punkte raeumt sonst erst der
    // Tagesjob ab).
    $pdo->prepare("DELETE tp FROM track_points tp
                   JOIN missions m ON m.id = tp.owner_id
                   WHERE tp.owner_type = 'mission' AND m.user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE tp FROM track_points tp
                   JOIN rest_segments r ON r.id = tp.owner_id
                   WHERE tp.owner_type = 'rest' AND r.user_id = ?")->execute([$id]);

    // Sperrliste haengt an der Geraetekennung, nicht am Konto.
    $pdo->prepare('DELETE dr FROM deleted_refs dr
                   JOIN devices d ON d.id = dr.device_id
                   WHERE d.user_id = ?')->execute([$id]);

    foreach (['missions', 'rest_segments', 'days', 'devices', 'pair_codes',
              'password_resets', 'crew_presets', 'bw_units', 'resources',
              'transport_dests', 'vehicles', 'user_bases', 'user_defaults',
              'bases'] as $t) {
        $pdo->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$id]);
    }
}

/**
 * Geraete, Bestand und Nachlauf einspielen. Erwartet eine offene Transaktion.
 */
function demo_bestand_einspielen(PDO $pdo, int $id, array $fx): array
{
    /* Geraete VOR dem Bestand: `edbak_restore()` verknuepft die
     * Dienstkennungen (`day_refs`) ueber die oeffentliche Geraetekennung mit
     * einem Geraet DIESES Kontos. Fehlt es zu diesem Zeitpunkt, bleibt die
     * Verknuepfung leer — die Kennung stuende dann zwar noch da, aber ohne
     * Geraet, und ein Upload derselben Uhr legte den Diensttag erneut an. */
    $insDev = $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash,
                                                  label, active)
                             VALUES (?,?,?,?,1)');
    $geraete = 0;
    foreach ((array)($fx['geraete'] ?? []) as $g) {
        if (!is_array($g) || empty($g['device_id']) || empty($g['api_key_hash'])) { continue; }
        $insDev->execute([$id, (string)$g['device_id'], (string)$g['api_key_hash'],
                          $g['label'] ?? null]);
        $geraete++;
    }

    $stats = edbak_restore($id, $fx['daten']);
    $stats['geraete'] = $geraete;
    return $stats;
}

/**
 * Nachlauf-Drehbuch: benannte Einsaetze und Diensttage in den Papierkorb.
 *
 * LAEUFT NACH DEM COMMIT, nicht in der Transaktion des Einspielens. Der Grund
 * ist praktisch: `trash_delete_mission()` und `trash_delete_day()` oeffnen je
 * eine eigene Transaktion, und PDO kennt keine verschachtelten. Sie alle
 * verschachtelungsfaehig zu machen hiesse, vier Stellen eines geteilten
 * Bausteins fuer EINEN Aufrufer umzubauen.
 *
 * Der Preis ist klein und benannt: Scheitert das Drehbuch, steht der Bestand
 * vollstaendig da und der Papierkorb ist leer. Das ist sichtbar (die Zahlen
 * stehen im Bericht), harmlos und beim naechsten Reset behoben. Waere es
 * andersherum — Bestand halb, Papierkorb gefuellt —, waere es keins von
 * beidem.
 *
 * WARUM ES DAS BRAUCHT. Das Sicherungsformat kennt keine geloeschten
 * Eintraege — `backup_lib.php` filtert an drei Stellen auf
 * `deleted_at IS NULL`. Ein Papierkorb-Dauerzustand liesse sich also nur mit
 * einem Sonderformat abbilden, und das waere ein zweiter Rueckspielpfad mit
 * eigenen Fehlern.
 *
 * Stattdessen legt der Reset nach dem Einspielen ein paar benannte Eintraege
 * ueber die REGULAEREN Loeschwege (`trash_lib.php`) in den Papierkorb — so,
 * wie eine Nutzerin es taete. Die Abdeckung „Papierkorb" uebersteht damit
 * jeden Reset, und die Einspielroutine bleibt formattreu (E-P1-21).
 */
function demo_nachlauf(int $id, array $drehbuch): array
{
    $pdo = db();
    $erg = ['einsaetze' => 0, 'diensttage' => 0, 'nicht_gefunden' => []];

    foreach ((array)($drehbuch['einsaetze'] ?? []) as $ref) {
        $st = $pdo->prepare('SELECT id FROM missions
                             WHERE user_id = ? AND client_ref = ? AND deleted_at IS NULL');
        $st->execute([$id, (string)$ref]);
        $mid = $st->fetchColumn();
        if ($mid === false) { $erg['nicht_gefunden'][] = 'Einsatz ' . $ref; continue; }
        trash_delete_mission($id, (int)$mid);
        $erg['einsaetze']++;
    }

    /* Diensttage ueber ihre Dienstkennung, nicht ueber das Datum: Seit E9
     * koennen zwei Dienste auf einem Kalendertag liegen, das Datum benennt
     * also keinen Tag mehr eindeutig. */
    foreach ((array)($drehbuch['diensttage'] ?? []) as $ref) {
        $st = $pdo->prepare('SELECT d.id FROM days d
                             JOIN day_refs r ON r.day_id = d.id
                             WHERE d.user_id = ? AND r.day_ref = ? AND d.deleted_at IS NULL
                             LIMIT 1');
        $st->execute([$id, (string)$ref]);
        $did = $st->fetchColumn();
        if ($did === false) { $erg['nicht_gefunden'][] = 'Diensttag ' . $ref; continue; }
        trash_delete_day($id, (int)$did);
        $erg['diensttage']++;
    }
    return $erg;
}

/* ------------------------------------------------------------------ Loeschen */

/**
 * Demo-Konto vollstaendig entfernen (Kontozeile eingeschlossen).
 *
 * Getrennt von der Kontoverwaltung im Adminbereich, weil hier zusaetzlich
 * die Kennzeichnung in `app_state` faellt — bliebe sie stehen, zeigte sie
 * auf eine spaeter neu vergebene Kennung.
 */
function demo_entfernen(): void
{
    $id = demo_id();
    if ($id === null) { return; }
    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* Reihenfolge: erst der Bestand, dann die Kontozeile, ZULETZT die
         * Kennzeichnung. Andersherum verloere demo_bestand_loeschen() seinen
         * Riegel mitten im Vorgang — er haengt an genau dieser Kennzeichnung. */
        demo_bestand_loeschen($pdo, $id);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM app_state WHERE k IN (?, ?)')
            ->execute([DEMO_K_USER, DEMO_K_RESET]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }
}
