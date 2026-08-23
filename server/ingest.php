<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

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
 * sofort, bei bekannter lief erst eine bcrypt-Pruefung. Der Unterschied ist
 * ohne jede Zugangsdaten messbar und beantwortet die Frage, welche
 * Geraetekennungen es gibt — und die Kennung ist die Haelfte dessen, was ein
 * Upload braucht. Deshalb laeuft auch der unbekannte Zweig gegen einen festen
 * Vergleichswert (AUTH_VERGLEICHSWERT, db.php).
 *
 * Die Abfolge bleibt sonst unveraendert: Der Fehlerschluessel 'auth' deckt
 * beide Faelle ab und sagt nicht, welcher es war.
 */
$deviceId = $_SERVER['HTTP_X_DEVICE_ID'] ?? '';
$apiKey   = $_SERVER['HTTP_X_API_KEY']   ?? '';
$st = db()->prepare('SELECT id, user_id, api_key_hash, active FROM devices WHERE device_id = ?');
$st->execute([$deviceId]);
$dev = $st->fetch();
if (!$dev) {
    password_verify($apiKey, AUTH_VERGLEICHSWERT);
    json_out(['error' => 'auth'], 401);
}
if (!password_verify($apiKey, $dev['api_key_hash'])) json_out(['error' => 'auth'], 401);
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
$points = pruef_menge($points, LIMIT_TRACKPUNKTE, 'track.points', $pruef);

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
    $chk = $pdo->prepare("SELECT id, day_id, deleted_at" . ($kind === 'mission' ? ', manual' : '')
                       . " FROM `$tabelle` WHERE device_id = ? AND client_ref = ?");
    $chk->execute([$dev['id'], $clientRef]);
    $existing = $chk->fetch();

    if ($existing && $existing['deleted_at'] !== null) {
        $pdo->commit();
        json_out(['ok' => true, 'id' => 0, 'stored_points' => 0,
                  'next_seq' => $seqFrom + count($points)]);
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
    if ($dayRef !== null) {
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
        if ($existing && (int)$existing['manual'] === 1) {
            $ownerId = (int)$existing['id'];
            $ownerType = 'mission';
        } else {
        // Upsert des Einsatzes (idempotent ueber device_id+client_ref)
        $pdo->prepare('INSERT INTO missions (user_id, device_id, client_ref, day_id, started_at, ended_at, distance_m, ascent_m, final)
                       VALUES (?,?,?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE
                         ended_at = VALUES(ended_at), distance_m = VALUES(distance_m),
                         ascent_m = VALUES(ascent_m), final = GREATEST(final, VALUES(final)),
                         id = LAST_INSERT_ID(id)')
            ->execute([$dev['user_id'], $dev['id'], $clientRef, $dayId, $startedAt, $endedAt,
                       $distanceM, $ascentM, $final]);
        $ownerId = (int)$pdo->lastInsertId();
        $ownerType = 'mission';

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
        $pdo->prepare('INSERT INTO rest_segments (user_id, device_id, client_ref, day_id, started_at, ended_at, final)
                       VALUES (?,?,?,?,?,?,?)
                       ON DUPLICATE KEY UPDATE
                         ended_at = VALUES(ended_at), final = GREATEST(final, VALUES(final)),
                         id = LAST_INSERT_ID(id)')
            ->execute([$dev['user_id'], $dev['id'], $clientRef, $dayId, $startedAt, $endedAt, $final]);
        $ownerId = (int)$pdo->lastInsertId();
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
    $stored = 0;
    if ($points) {
        $ins = $pdo->prepare('INSERT INTO track_points (owner_type, owner_id, seq, lat, lon, ele, ts)
                              VALUES (?,?,?,?,?,?,?)');
        foreach ($points as $i => $pt) {
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
                $ins->execute([$ownerType, $ownerId, $seqFrom + $i, $la, $lo,
                    $pt[2] === null ? null : (float)$pt[2], (int)$pt[3]]);
                $stored += $ins->rowCount();
            } catch (PDOException $ex) {
                // Diese Sequenznummer gibt es schon: erneuter Upload derselben
                // Punkte. Genau dafuer war IGNORE gedacht.
                if (!ist_dublettenfehler($ex)) { throw $ex; }
            }
        }
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
     * der Fall, den der Testbestand als "Dienst ueber Mitternacht" fuehrt. */
    dt_zeitraum_fortschreiben($pdo, $dayId, $startedAt, $endedAt);

    $q = $pdo->prepare('SELECT COALESCE(MAX(seq)+1, 0) AS next FROM track_points WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$ownerType, $ownerId]);
    $nextSeq = (int)$q->fetchColumn();

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
