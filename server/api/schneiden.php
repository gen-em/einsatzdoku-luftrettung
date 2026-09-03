<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';
require_once __DIR__ . '/../spur_lib.php';

/**
 * Schneidewerkzeug: aus einem Ruhesegment einen Einsatz machen (S4/A2b).
 *
 * POST api/schneiden.php   JSON-Body, Header X-CSRF wie bei api/day.php
 *
 *   { action: 'schneiden',
 *     rest_id: 17,
 *     beginn: 'HH:MM',            Ortszeit des Diensttags, Pflicht
 *     ende:   'HH:MM',            Pflicht
 *     beginn_tag: 0|1,            +1 = am Folgetag (Dienst ueber Mitternacht)
 *     ende_tag:   0|1,
 *     phasen: { "3": 'HH:MM'|null, "4": ..., "7": ... } }   optional
 *
 *   { action: 'rueckgaengig', mission_id: 42 }
 *
 * WOFUER. Wer waehrend eines Einsatzes keinen Knopf gedrueckt hat, hat den
 * Einsatz nicht — aber seine SPUR hat er: Sie liegt im Ruhesegment, in dem
 * das Geraet zu der Zeit aufgezeichnet hat. Das Nachtragen ueber
 * `einsatz_form.php` legt einen Einsatz ohne Spur an; hier wandert sie mit.
 *
 * WARUM EIN EIGENER ENDPUNKT UND NICHT EIN ZWEIG IN `einsatz_form.php`:
 * Das Formular ist ein Seitenwechsel mit vollem Feldkatalog; der Schnitt ist
 * eine Handlung an einer Zeile, die die Tagesansicht danach neu laedt. Vor
 * allem aber ist der Schnitt UNTEILBAR — Einsatz anlegen, Punkte verschieben,
 * Sperrvermerk setzen, Diensttag-Zeitraum fortschreiben. Bricht etwas davon
 * ab, darf nichts davon stehen.
 *
 * DER EINSATZ ENTSTEHT AUF DEM BESTANDSWEG. Virtuelles Geraet
 * `manual-<userId>`, `origin = 'manual'`, `manual = 1`, `client_ref` mit
 * Praefix — woertlich wie in `einsatz_form.php`. Das ist kein Zierrat: An
 * diesen drei Merkmalen haengt, ob der Einsatz durch Backup, Export und
 * Papierkorb kommt (R24), und ob `ingest.php` seine Phasen spaeter noch
 * anfasst.
 *
 * WAS ER NICHT TUT: Er fuellt keine Einsatzfelder. Einsatzort, Alter und
 * Diagnose sind Ende-zu-Ende-verschluesselt und entstehen im Browser; ein
 * Endpunkt, der sie annaehme, brauechte Klartext. Der geschnittene Einsatz ist
 * deshalb ein leerer Einsatz mit Zeiten, Phasen und Spur — den Rest traegt
 * die Bedienerin im Formular nach, das dafuer da ist.
 */

/** Die drei Phasen, die der Schneide-Bereich als Abkuerzung anbietet (A0). */
const SCHNITT_PHASEN = [3, 4, 7];

/**
 * Das virtuelle Geraet fuer von Hand entstandene Eintraege.
 *
 * WOERTLICH AUS `einsatz_form.php` uebernommen, einschliesslich der
 * Nutzerkennung IN der Abfrage (M3-12/M6-09): Dass `manual-<id>` die
 * Zugehoerigkeit im Namen traegt, ist eine Zeichenkette und keine Bedingung.
 * Ohne `user_id` in der Abfrage gehoerte die gefundene Zeile beim naechsten
 * Namensschema jemand anderem — und der Einsatz staende am Geraet einer
 * fremden Person.
 */
function schnitt_geraet(PDO $pdo, int $userId): int
{
    $devKey = 'manual-' . $userId;
    $q = $pdo->prepare('SELECT id FROM devices WHERE device_id = ? AND user_id = ?');
    $q->execute([$devKey, $userId]);
    $devId = $q->fetchColumn();
    if ($devId !== false) { return (int)$devId; }

    $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
                   VALUES (?,?,?,?,0)')
        ->execute([$userId, $devKey,
                   password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                   'Manuelle Einträge']);
    return (int)$pdo->lastInsertId();
}

/* ---- Schneiden ------------------------------------------------------------ */

function schnitt_ausfuehren(array $b, int $userId): never
{
    $pdo = db();
    $pruef = new Pruefliste();

    $restId = pruef_zahl($b['rest_id'] ?? null, 1, PHP_INT_MAX, 'rest_id', $pruef);
    if ($restId === null) { json_out(['error' => 'eingabe',
        'meldung' => 'Kein Ruhesegment angegeben.'], 400); }

    /* DAS SEGMENT UND SEIN DIENSTTAG IN EINER ABFRAGE, und beides mit
     * `user_id` gebunden. Ein Schnitt am Segment einer fremden Person waere
     * der schwerste Fehler, den dieser Endpunkt machen kann; die Bedingung
     * gehoert deshalb in die Abfrage und nicht in eine Pruefung danach. */
    $q = $pdo->prepare('SELECT r.id, r.day_id, r.started_at, r.ended_at,
                               d.day, d.deleted_at AS tag_geloescht
                          FROM rest_segments r
                          JOIN days d ON d.id = r.day_id AND d.user_id = r.user_id
                         WHERE r.id = ? AND r.user_id = ? AND r.deleted_at IS NULL');
    $q->execute([$restId, $userId]);
    $seg = $q->fetch(PDO::FETCH_ASSOC);
    if (!$seg) { json_out(['error' => 'nicht_gefunden',
        'meldung' => 'Dieses Ruhesegment gibt es nicht (mehr).'], 404); }
    if ($seg['tag_geloescht'] !== null) { json_out(['error' => 'papierkorb',
        'meldung' => 'Der Diensttag liegt im Papierkorb. Erst wiederherstellen.'], 409); }

    $tag = (string)$seg['day'];

    /* ---- Die Zeiten ------------------------------------------------------
     *
     * ORTSZEIT HEREIN, UTC HINAUS — wie ueberall in dieser Anwendung
     * (`pruef_ortszeit_zu_utc()`). Der Tagesversatz ist kein Luxus: Ein
     * Dienst laeuft ueber Mitternacht, und ein Einsatz um 00:40 gehoert zum
     * Vortag. Ohne ihn laege er zwoelf Stunden vor dem Dienstbeginn. */
    $beginn = pruef_ortszeit_zu_utc($tag, $b['beginn'] ?? null,
        (int)($b['beginn_tag'] ?? 0), 'Einsatzbeginn', $pruef);
    $ende   = pruef_ortszeit_zu_utc($tag, $b['ende'] ?? null,
        (int)($b['ende_tag'] ?? 0), 'Einsatzende', $pruef);

    if ($beginn === null || $ende === null) {
        json_out(['error' => 'eingabe',
                  'meldung' => 'Beginn und Ende sind Pflicht und müssen Uhrzeiten sein.',
                  'rejected' => $pruef->nachUrsache()], 400);
    }
    if ($ende <= $beginn) {
        json_out(['error' => 'eingabe',
                  'meldung' => 'Das Ende liegt vor dem Beginn oder gleichauf.'], 400);
    }

    /* ---- Die Phasen ------------------------------------------------------
     *
     * OPTIONAL, UND DREI DAVON. Die vollstaendige Phasenliste wohnt im
     * Einsatzformular (A0); hier stehen die drei, die man beim Schneiden
     * ohnehin weiss. Was durchkommt, geht durch `pruef_phase()` — ein Wert
     * ausserhalb 2 bis 9 ist kein Feld, das man stillschweigend uebergeht. */
    $phasen = [];
    foreach ((array)($b['phasen'] ?? []) as $nr => $hhmm) {
        if ($hhmm === null || $hhmm === '') { continue; }
        $ph = pruef_phase($nr, 'Phase', $pruef);
        if ($ph === null || !in_array($ph, SCHNITT_PHASEN, true)) { continue; }
        $wann = pruef_ortszeit_zu_utc($tag, $hhmm, (int)($b['beginn_tag'] ?? 0),
                                      'Phase ' . $ph, $pruef);
        if ($wann === null) { continue; }
        /* AUSSERHALB DES SCHNITTS IST KEINE PHASE DIESES EINSATZES. Sie
         * staende sonst an einem Einsatz, dessen Spur sie nicht enthaelt —
         * und die Hoehenermittlung suchte einen Punkt, den es dort nie gab. */
        if ($wann < $beginn || $wann > $ende) {
            $pruef->melde('Phase ' . $ph, 'liegt ausserhalb von Beginn und Ende');
            continue;
        }
        $phasen[$ph] = $wann;
    }

    $vonTs = (int)strtotime($beginn . ' UTC');
    $bisTs = (int)strtotime($ende . ' UTC');

    /* ---- Und jetzt in EINEM Zug ------------------------------------------ */
    $pdo->beginTransaction();
    try {
        $devId = schnitt_geraet($pdo, $userId);
        $pdo->prepare("INSERT INTO missions
                         (user_id, device_id, client_ref, day_id, started_at,
                          ended_at, final, manual, origin)
                       VALUES (?,?,?,?,?,?,1,1,'manual')")
            ->execute([$userId, $devId, 'cut-' . uniqid(), (int)$seg['day_id'],
                       $beginn, $ende]);
        $misId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare('INSERT INTO mission_phases (mission_id, phase, occurred_at)
                              VALUES (?,?,?)');
        foreach ($phasen as $ph => $wann) { $ins->execute([$misId, $ph, $wann]); }

        /* DIE PUNKTE WANDERN (E-S4-53). `spur_teilen()` schliesst sich dieser
         * Transaktion an — deshalb steht hier kein zweites
         * `beginTransaction()`. */
        $erg = spur_teilen($pdo, 'rest', (int)$seg['id'], 'mission', $misId,
                           $vonTs, $bisTs);

        /* EIN SCHNITT OHNE PUNKTE IST KEIN SCHNITT.
         *
         * Er entstuende bei einem zweiten Schnitt ueber denselben Bereich
         * oder ueber eine Aufzeichnungsluecke. Was dabei herauskaeme, waere
         * ein leerer Einsatz — und zwar einer, den das Rueckgaengig nicht
         * anfassen kann: Ohne gewanderte Punkte gibt es keinen Vermerk, und
         * ohne Vermerk findet es den Weg zurueck nicht. Die Bedienerin
         * bliebe mit einem Einsatz sitzen, den sie ueber den Papierkorb
         * loeschen muss, ohne dass ihr jemand gesagt hat, warum.
         *
         * Der Vermerk waere hier auch nicht die Loesung: Ein gesperrter
         * Bereich ohne Punkte saegte eine Nachlieferung ab, die nie doppelt
         * gewesen waere.
         *
         * Also: nichts anlegen und sagen, warum. Wer trotzdem einen Einsatz
         * zu dieser Zeit will, trägt ihn nach — dafuer gibt es das Formular,
         * und ohne Spur tut es genau dasselbe. */
        if ($erg['genommen'] === 0) {
            $pdo->rollBack();
            json_out(['error' => 'leer',
                      'meldung' => 'In diesem Zeitraum liegt kein einziger '
                                 . 'Punkt — entweder ist er schon geschnitten, '
                                 . 'oder das Gerät hat dort nicht aufgezeichnet. '
                                 . 'Es ist nichts entstanden. Für einen Einsatz '
                                 . 'ohne Spur ist „Nachtragen" der richtige Weg.'], 409);
        }
        schnitt_vermerken($pdo, $userId, 'rest', (int)$seg['id'], $misId,
                          $vonTs, $bisTs, $erg['genommen']);

        /* Der Diensttag muss den Einsatz umschliessen (JSON-Vertrag 4.4) —
         * dieselbe Regel wie beim Nachtragen im Formular. */
        dt_zeitraum_fortschreiben($pdo, (int)$seg['day_id'], $beginn, $ende);

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_fehler($ex, 'schneiden');
    }

    /* Die Hoehe des Einsatzorts NACH dem Commit und still, wie in
     * `ingest.php`: Sie ist ein Komfortwert, und ein Fehler darin darf einen
     * abgeschlossenen Schnitt nicht nachtraeglich fragwuerdig machen. */
    try {
        require_once __DIR__ . '/../site_elevation_lib.php';
        compute_site_elevation($pdo, $misId);
    } catch (Throwable $ex) { /* bewusst still */ }

    $antwort = ['ok' => true, 'mission_id' => $misId,
                'genommen' => $erg['genommen'], 'geblieben' => $erg['geblieben']];
    if (!$pruef->sauber()) { $antwort['rejected'] = $pruef->nachUrsache(); }
    json_out($antwort);
}

/* ---- Rückgängig (E-S4-17) ------------------------------------------------- */

function schnitt_rueckgaengig(array $b, int $userId): never
{
    $pdo = db();

    $misId = pruef_zahl($b['mission_id'] ?? null, 1, PHP_INT_MAX);
    if ($misId === null) { json_out(['error' => 'eingabe',
        'meldung' => 'Kein Einsatz angegeben.'], 400); }

    $q = $pdo->prepare('SELECT id, day_id FROM missions
                         WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $q->execute([$misId, $userId]);
    if (!$q->fetch()) { json_out(['error' => 'nicht_gefunden',
        'meldung' => 'Diesen Einsatz gibt es nicht (mehr).'], 404); }

    $schnitte = schnitte_zum_einsatz($pdo, $misId);
    if (!$schnitte) { json_out(['error' => 'kein_schnitt',
        'meldung' => 'Dieser Einsatz ist nicht aus einem Segment geschnitten.'], 409); }

    /* WAS AM EINSATZ HAENGT, HAELT DAS RUECKGAENGIG AUF (A0: „solange am
     * Einsatz nichts Weiteres hängt").
     *
     * Der Grund ist nicht Vorsicht, sondern Arithmetik: Das Rueckgaengig
     * LOESCHT den Einsatz. Wer bis dahin Einsatzort, Diagnose und eine
     * Reanimation eingetragen hat, verlöre sie — und zwar auf einen Knopf,
     * der „rückgängig" heisst und nach der harmlosesten Handlung der Seite
     * klingt. Ein Einsatz mit Inhalt wird stattdessen ueber den Papierkorb
     * geloescht, wo die Frist von 30 Tagen laeuft.
     *
     * GEZAEHLT WIRD, WAS EIN MENSCH EINGETRAGEN HAT — nicht, was der Schnitt
     * selbst angelegt hat: Die drei Phasen aus dem Schneide-Bereich zaehlen
     * nicht, `edited` schon. */
    $haengt = [];
    foreach ([['mission_crew', 'eine abweichende Besatzung'],
              ['mission_resources', 'weitere Rettungsmittel'],
              ['resus_sessions', 'eine Reanimation']] as [$tab, $wort]) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM `$tab` WHERE mission_id = ?");
        $c->execute([$misId]);
        if ((int)$c->fetchColumn() > 0) { $haengt[] = $wort; }
    }
    $c = $pdo->prepare('SELECT edited, pat_blob IS NOT NULL AS hatPat FROM missions WHERE id = ?');
    $c->execute([$misId]);
    $m = $c->fetch(PDO::FETCH_ASSOC);
    if ((int)($m['edited'] ?? 0) === 1) { $haengt[] = 'Änderungen im Einsatzformular'; }
    if ((int)($m['hatPat'] ?? 0) === 1) { $haengt[] = 'geschützte Angaben'; }

    if ($haengt) {
        json_out(['error' => 'nicht_leer',
                  'meldung' => 'Am Einsatz hängt inzwischen ' . implode(', ', $haengt)
                             . '. Rückgängig würde das mitlöschen — bitte den Einsatz '
                             . 'über den Papierkorb löschen, wenn er weg soll.'], 409);
    }

    $pdo->beginTransaction();
    try {
        $zurueck = 0;
        foreach ($schnitte as $sn) {
            /* DIE PUNKTE ZURUECK, ueber denselben Weg wie hin — nur mit
             * vertauschten Enden (E-S4-55). `spur_teilen()` ERGAENZT das
             * Ziel; das Segment ist inzwischen weitergelaufen, und ein
             * Ersetzen wuerfe genau das weg, was seither dazugekommen ist. */
            $erg = spur_teilen($pdo, 'mission', $misId,
                               $sn['owner_type'], $sn['owner_id'],
                               $sn['von_ts'], $sn['bis_ts']);
            $zurueck += $erg['genommen'];
        }
        /* DER VERMERK FAELLT MIT (E-S4-53). Bliebe er stehen, waere der
         * Zeitraum fuer immer gesperrt — und zwar unsichtbar. */
        schnitte_loeschen($pdo, 'ziel', [$misId]);

        /* Der Einsatz selbst geht restlos, nicht in den Papierkorb: Er ist
         * eine Minute alt und hat nie etwas enthalten, was jemand
         * wiederfinden wollte. Die Spur ist oben schon abgeraeumt worden. */
        spur_loeschen($pdo, 'mission', [$misId]);
        $pdo->prepare('DELETE FROM mission_phases WHERE mission_id = ?')->execute([$misId]);
        $pdo->prepare('DELETE FROM missions WHERE id = ? AND user_id = ?')
            ->execute([$misId, $userId]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_fehler($ex, 'schneiden');
    }

    json_out(['ok' => true, 'zurueck' => $zurueck]);
}

/* ---- Eingang -------------------------------------------------------------- */

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'method'], 405);
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'csrf'], 403);
    }
    $b = json_decode(file_get_contents('php://input'), true);
    if (!is_array($b)) { json_out(['error' => 'payload'], 400); }

    switch ((string)($b['action'] ?? '')) {
        case 'schneiden':    schnitt_ausfuehren($b, $userId);
        case 'rueckgaengig': schnitt_rueckgaengig($b, $userId);
        default:             json_out(['error' => 'action'], 400);
    }
} catch (Throwable $ex) {
    json_fehler($ex, 'schneiden');
}
