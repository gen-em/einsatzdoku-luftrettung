<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../diensttag_lib.php';

/**
 * Import: Abgleich mit dem Bestand.
 *
 * POST api/import_commit.php   JSON-Body, Header X-CSRF wie bei api/day.php
 *
 *   { action: 'check',
 *     days: ["2026-05-21", ...] }             // Kalendertage der Datei
 *
 * Antwort:
 *   { days: { "2026-05-21": { day_id, crew: {<rolle>: name, ...},
 *                             vehicle_id, base_id, kind,
 *                             vehicle_name, base_name,
 *                             missions: [{id, hhmm, pat_blob}] } } }
 *
 *   { action: 'commit',
 *     days:     [{day, crew: {<rolle>: name, ...}, vehicle_id, base_id,
 *                 mode:'insert'|'keep'|'update'}],
 *     missions: [{day, started_local:'HH:MM', transport_dest, winch,
 *                 resources:[], crew_override, crew: {<rolle>: name, ...},
 *                 pat_blob, dup:'insert'|'overwrite'|'skip', overwrite_id,
 *
 *                 // ab Web 2.10.0, alle optional (Rueckimport der eigenen
 *                 // Exportformate; die Jahreslisten-Profile senden sie nicht)
 *                 ended_utc, site_ele_m, distance_m, ascent_m,
 *                 schockraum, secondary, winch_cycles, winch_cycles_pat,
 *                 winch_airload, bergwacht, bw_unit, bw_info, other_ema, notes,
 *                 phases: [{phase:2..9, at:'...Z'|null, local:'HH:MM'|null,
 *                           lat, lon}],
 *                 rea:    [{started_at:'...Z', events:[{type, at:'...Z'}]}] }] }
 *
 * ZEITEN IN DER NUTZLAST: 'started_local' ist Ortszeit (HH:MM) und bleibt es —
 * der Browser vergleicht damit unmittelbar die Zeiten aus der Datei. Die
 * Phasen- und Reanimationszeiten kommen dagegen als UTC-Zeitstempel, weil die
 * Quelldatei dort einen vollstaendigen Zeitpunkt samt Zonenversatz liefert;
 * eine zweite Umrechnung hier waere eine zusaetzliche Fehlerquelle. Wo eine
 * Phase nur als Ortszeit vorliegt (Standard-Excel kennt nur HH:MM), traegt sie
 * 'local' statt 'at' und wird hier wie 'started_local' umgerechnet.
 *
 * Antwort:
 *   { ok, days_inserted, days_updated, missions_inserted,
 *     missions_overwritten, missions_skipped, first_day }
 *
 * WARUM SO WENIG: Die Anfrage enthaelt ausschliesslich Datum und Uhrzeit.
 * Name, Geburtsdatum, Diagnose, Einsatzort und seit Web 2.9.0 auch die
 * Einsatznummer bleiben im Browser — der Server kann und soll nicht wissen,
 * um welche Personen es geht. Fuer die Duplikaterkennung ueber die Nummer
 * liefert 'check' deshalb je vorhandenem Einsatz den pat_blob mit; der
 * Browser entschluesselt ihn lokal und vergleicht dort (siehe
 * assets/import_ui.js, bestandEinsatznummernIndex). Erkannt werden
 * Nummerndubletten dadurch nur noch innerhalb der Diensttage, die in der
 * Importdatei vorkommen — das ist der Preis der Verschluesselung
 * (docs/Technik.md).
 *
 * Die Uhrzeiten gehen als ORTSZEIT (HH:MM) zurueck, nicht als UTC-Zeitstempel.
 * Der Browser vergleicht sie unmittelbar mit den Zeiten aus der Datei, die
 * ebenfalls Ortszeit sind; eine Umrechnung auf beiden Seiten waere eine
 * zusaetzliche Fehlerquelle.
 *
 * Geloeschte Eintraege (Papierkorb) gelten bewusst als nicht vorhanden: Ein
 * Import soll nicht an etwas scheitern, das die NutzerIn weggeworfen hat.
 */

/**
 * Uebernahme in einer einzigen Transaktion: entweder alles oder nichts.
 * Ein halb eingespielter Jahresbestand waere von Hand kaum zu bereinigen.
 */
function import_commit(array $b, int $userId): never
{
    $tage      = is_array($b['days'] ?? null) ? $b['days'] : [];
    $einsaetze = is_array($b['missions'] ?? null) ? $b['missions'] : [];
    if (count($tage) > 600 || count($einsaetze) > 3000) {
        json_out(['error' => 'zu_gross',
                  'meldung' => 'Zu viele Zeilen auf einmal. Bitte in Jahresabschnitten importieren.'], 413);
    }

    /* Diese vier Kurzformen waren die URSPRUNGSFASSUNG der Pruefungen; sie
     * sind nach validate_lib.php gehoben worden, damit alle vier Schreibwege
     * denselben Massstab haben. Hier bleiben sie als duenne Weiterleitung
     * stehen, weil sie im Aufbau der Wertelisten unten dutzendfach vorkommen
     * und eine Umbenennung nur Laerm erzeugen wuerde.
     *
     * Der Unterschied zu frueher: Sie melden jetzt, WARUM sie etwas verwerfen. */
    $pruef = new Pruefliste();

    $txt  = fn ($v, int $max) => pruef_text($v, $max, 'Feld', $pruef);
    $zahl = fn ($v, int $min, int $max) => pruef_zahl($v, $min, $max, 'Zahl', $pruef);
    $flag = static fn ($v): int => pruef_flag($v);
    $utc  = fn ($v) => pruef_utc($v, 'Zeitpunkt', $pruef);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* Die Pruefung der Stammdaten-Kennungen steckt seit Web 6.0.0 in
         * dt_zuordnen() (aufgerufen von dt_anlegen): Sie muss den STANDORT
         * mitpruefen, weil ein zentrales Rettungsmittel erst zur Verfuegung
         * steht, wenn die NutzerIn seinen Standort ausgewaehlt hat (E16). Eine
         * zweite Fassung hier waere die Stelle, an der beide auseinanderlaufen.
         */

        /* ---- Diensttage ----------------------------------------------------
         *
         * JE KALENDERTAG HOECHSTENS EINER (E9). Eine Tabelle sagt nichts
         * darueber, ob zwei Einsaetze desselben Datums zu einem oder zu zwei
         * Diensten gehoeren; der Import legt deshalb einen an und ordnet alle
         * Einsaetze des Datums ihm zu. Aufteilen laesst sich das danach mit
         * einsatz_verschieben.php — eine geratene Aufteilung waere schlechter
         * als eine, die jemand bewusst vornimmt.
         *
         * Der Modus 'insert' legt nur dann an, wenn es zu diesem Datum noch
         * KEINEN offenen Diensttag gibt. Das frueher noetige
         * `ON DUPLICATE KEY UPDATE` ist mit dem Tagesschluessel entfallen — es
         * gibt keinen Schluesselkonflikt mehr, auf den es reagieren koennte,
         * und ein blindes INSERT wuerde bei einem Doppelklick zwei Dienste
         * anlegen.
         *
         * EIN TAG IM PAPIERKORB WIRD NICHT ZURUECKGEHOLT (D1). Frueher stand
         * hier deleted_at = NULL: Ein Import legte einen bewusst geloeschten
         * Diensttag stillschweigend wieder an, samt seiner alten Angaben. Das
         * Loeschen war eine bewusste Handlung; sie durch eine Nebenwirkung
         * rueckgaengig zu machen, ist eine Ueberraschung — und eine, die
         * niemand sieht. Solche Tage werden uebersprungen und in der Meldung
         * genannt. */
        $tageNeu = 0; $tageGeaendert = 0;

        // Tage im Papierkorb einmal vorab feststellen, statt je Zeile zu fragen.
        $imPapierkorb = [];
        $q2 = $pdo->prepare('SELECT day FROM days WHERE user_id = ? AND deleted_at IS NOT NULL');
        $q2->execute([$userId]);
        foreach ($q2->fetchAll(PDO::FETCH_COLUMN) as $d2) { $imPapierkorb[(string)$d2] = true; }
        $tageUebersprungen = 0;

        /* Datum -> Kennung des Diensttags. Diese Zuordnung ist das Ergebnis
         * dieses Abschnitts und die Grundlage des naechsten: Die Einsaetze
         * kommen mit einem DATUM aus der Datei und brauchen eine KENNUNG. */
        $dayIdByDate = [];
        $vorhandenQ = $pdo->prepare('SELECT id FROM days
                                      WHERE user_id = ? AND day = ? AND deleted_at IS NULL
                                      ORDER BY started_at, id LIMIT 1');

        foreach ($tage as $t) {
            $tag = pruef_kalendertag($t['day'] ?? null, 'tag.day', $pruef);
            if ($tag === null) { continue; }
            if (isset($imPapierkorb[$tag])) {
                // Ablehnen statt zurueckholen (D1) — und sagen, dass es
                // geschehen ist.
                $tageUebersprungen++;
                continue;
            }
            $modus = (string)($t['mode'] ?? 'keep');

            // Besatzung als role_code => name (E7). Unbekannte Rollen fallen
            // heraus: Der Katalog steht im Code, nicht in der Datei.
            $crew = [];
            foreach ((array)($t['crew'] ?? []) as $rolle => $name) {
                if (!array_key_exists((string)$rolle, CREW_ROLES)) { continue; }
                $w = $txt($name, 120);
                if ($w !== null) { $crew[(string)$rolle] = $w; }
            }

            $vorhandenQ->execute([$userId, $tag]);
            $vorhanden = $vorhandenQ->fetchColumn();
            $dayId = $vorhanden === false ? 0 : (int)$vorhanden;

            if ($modus === 'insert' && $dayId === 0) {
                /* Anlegen samt Einfrieren von Art, Rollensatz, Faehigkeiten und
                 * Bezeichnungen (E8) — dieselbe Funktion, die auch die Uhr und
                 * das Formular benutzen. Ohne sie traege der Diensttag eine
                 * Zuordnung ohne Art und ohne Rollen, und die Besatzung aus der
                 * Datei faende keine Zeile, in die sie geschrieben werden
                 * koennte. */
                $dayId = dt_anlegen($pdo, $userId, $tag, null,
                    isset($t['vehicle_id']) ? (int)$t['vehicle_id'] : null,
                    isset($t['base_id'])    ? (int)$t['base_id']    : null);
                $tageNeu++;
                /* Rollen, die die Datei nennt, das Rettungsmittel aber nicht
                 * vorsieht, bekommen trotzdem ihre Zeile: Ein Name aus der
                 * Datei ist eine Angabe, und sie stillschweigend zu verwerfen
                 * waere Datenverlust. Dieselbe Regel wie in der Migration. */
                dt_crew_ergaenzen($pdo, $dayId, array_keys($crew));
                dt_crew_speichern($pdo, $dayId, $crew);
            } elseif ($modus === 'update' && $dayId > 0) {
                // Ausdruecklicher Wunsch, die Besatzung aus der Datei zu
                // uebernehmen. Nur belegte Rollen werden geschrieben.
                dt_crew_ergaenzen($pdo, $dayId, array_keys($crew));
                dt_crew_speichern($pdo, $dayId, $crew);
                $tageGeaendert++;
            }
            // 'keep' = bewusst nichts tun
            if ($dayId > 0) { $dayIdByDate[$tag] = $dayId; }
        }

        /* ---- Virtuelles Geraet "Manuelle Einträge" ------------------------- */
        // Importierte Einsaetze zaehlen wie von Hand angelegte: Sie haengen am
        // selben virtuellen Geraet, damit die Uhr sie nie ueberschreibt und sie
        // in der Geraeteliste nicht auftauchen (Filter 'manual-%').
        $devKey = 'manual-' . $userId;
        /* Die Nutzerkennung gehoert IN die Abfrage (M3-12/M6-09).
         *
         * Gesucht wurde allein ueber device_id. Dass 'manual-<id>' die
         * Zugehoerigkeit im Namen traegt, machte die Abfrage praktisch
         * richtig — aber nur, weil eine Zeichenkette zufaellig dasselbe
         * aussagt wie eine Spalte. Steht die Bedingung nicht in der Abfrage,
         * gibt es auch nichts, was sie durchsetzt: Ein spaeter geaendertes
         * Namensschema, ein Tippfehler beim Zusammenbauen des Schluessels,
         * und die gefundene Zeile gehoert jemand anderem. Das Ergebnis waere
         * ein Einsatz am Geraet einer fremden Person.
         *
         * user_id ist ausserdem die Spalte, auf der die Fremdschluessel und
         * alle uebrigen Abfragen dieser Datei arbeiten. Eine Ausnahme davon
         * faellt bei der Durchsicht nicht auf. */
        $q = $pdo->prepare('SELECT id FROM devices WHERE device_id = ? AND user_id = ?');
        $q->execute([$devKey, $userId]);
        $devId = $q->fetchColumn();
        if ($devId === false) {
            $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
                           VALUES (?,?,?,?,0)')
                ->execute([$userId, $devKey,
                           password_hash(bin2hex(random_bytes(24)), PASSWORD_DEFAULT),
                           'Manuelle Einträge']);
            $devId = (int)$pdo->lastInsertId();
        }
        $devId = (int)$devId;

        /* ---- Einsaetze ---------------------------------------------------- */
        // Die zusaetzlichen Felder ab Web 2.10.0 haengen hinten an. Profile,
        // die sie nicht liefern, schreiben dort NULL beziehungsweise 0 — das
        // entspricht dem Zustand vor dieser Version.
        $insE = $pdo->prepare(
            'INSERT INTO missions (user_id, device_id, client_ref, day_id, started_at, ended_at,
                                   final, manual, origin, transport_dest, winch,
                                   crew_override, pat_blob,
                                   site_ele_m, distance_m, ascent_m,
                                   schockraum, secondary, winch_cycles, winch_cycles_pat,
                                   winch_airload, bergwacht, bw_unit, bw_info,
                                   other_ema, notes)
             VALUES (?,?,?,?,?,?,1,1,\'import\',?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        /* UEBERSCHREIBEN LOESCHT NICHTS, WAS DIE DATEI NICHT KENNT (P10, A9).
         *
         * Die Felder unter der Export-Schranke stehen hier mit
         * COALESCE(?, spalte) statt mit einer nackten Zuweisung. Fuer die
         * Besatzung gilt dieselbe Haltung, dort jetzt in dt_crew_speichern():
         * geschrieben wird nur, was die Datei nennt.
         *
         * Anlass ist Block A9: Ein Export OHNE personenbezogene Angaben laesst
         * Besatzung, bw_info, other_ema, Notizen und site_ele_m leer. Wer eine
         * solche Datei zurueckspielt und dabei "ueberschreiben" waehlt, schrieb
         * bis Web 5.7.0 NULL ueber Angaben, die im Bestand noch vollstaendig
         * waren — der Export haette die Daten damit nicht nur nicht enthalten,
         * sondern beim Rueckweg vernichtet. Bei pat_blob galt dasselbe schon
         * fuer den alten Patientendaten-Haken.
         *
         * PREIS, BEWUSST BEZAHLT: Ein Feld laesst sich per Import nicht mehr
         * gezielt LEEREN. Wer eine Notiz loswerden will, tut das im Formular.
         * Der umgekehrte Fehler waere teurer: Ein Formular vergisst einen Wert
         * je Einsatz, ein Import vergisst ihn fuer den ganzen Jahrgang.
         *
         * NICHT betroffen sind die Felder ausserhalb der Schranke
         * (transport_dest, bw_unit, distance_m, die Flags …): Sie stehen in
         * jedem Export, ein leerer Wert ist dort eine Aussage. */
        $updE = $pdo->prepare(
            'UPDATE missions SET day_id = ?, started_at = ?, ended_at = ?,
                                 transport_dest = ?, winch = ?, crew_override = ?,
                                 pat_blob    = COALESCE(?, pat_blob),
                                 site_ele_m  = COALESCE(?, site_ele_m),
                                 distance_m = ?, ascent_m = ?,
                                 schockraum = ?, secondary = ?, winch_cycles = ?,
                                 winch_cycles_pat = ?, winch_airload = ?, bergwacht = ?,
                                 bw_unit = ?,
                                 bw_info     = COALESCE(?, bw_info),
                                 other_ema   = COALESCE(?, other_ema),
                                 notes       = COALESCE(?, notes),
                                 manual = 1, edited = 1
             WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $insPhase = $pdo->prepare(
            'INSERT INTO mission_phases (mission_id, phase, occurred_at, lat, lon)
             VALUES (?,?,?,?,?)');
        $hatPhase2 = $pdo->prepare(
            'SELECT id FROM mission_phases WHERE mission_id = ? AND phase = 2 LIMIT 1');
        $delPhasen = $pdo->prepare('DELETE FROM mission_phases WHERE mission_id = ?');
        $delRes = $pdo->prepare('DELETE FROM mission_resources WHERE mission_id = ?');
        $insRes = $pdo->prepare('INSERT INTO mission_resources (mission_id, name) VALUES (?, ?)');
        // resus_events haengt per FOREIGN KEY ... ON DELETE CASCADE an
        // resus_sessions — das Loeschen der Sitzungen raeumt die Ereignisse mit.
        $delRea = $pdo->prepare('DELETE FROM resus_sessions WHERE mission_id = ?');
        $insReaS = $pdo->prepare(
            'INSERT INTO resus_sessions (mission_id, started_at) VALUES (?, ?)');
        $insReaE = $pdo->prepare(
            'INSERT INTO resus_events (session_id, type, occurred_at) VALUES (?,?,?)');

        $neu = 0; $ersetzt = 0; $uebersprungen = 0; $ersterTag = null;

        /* URSACHEN GETRENNT ZAEHLEN.
         *
         * Bisher fielen VIER voellig verschiedene Gruende in einen einzigen
         * Zaehler: ungueltiges Datum, von der NutzerIn selbst gewaehltes
         * Ueberspringen, nicht umrechenbare Uhrzeit und "gehoert jemand
         * anderem". Wer 40 uebersprungene Einsaetze sieht, kann daraus nicht
         * erkennen, ob das gut ist (alles schon da) oder schlecht (alles
         * kaputt) — und genau diese Unterscheidung braucht er. */
        $grund = ['datum' => 0, 'uhrzeit' => 0, 'auswahl' => 0,
                  'fremd_oder_geloescht' => 0, 'ohne_diensttag' => 0];

        // Zugehoerigkeit direkt feststellen (M3-03), statt sie aus der Zahl
        // geaenderter Zeilen zu erschliessen.
        $gehoert = $pdo->prepare('SELECT 1 FROM missions
                                  WHERE id = ? AND user_id = ? AND deleted_at IS NULL');

        foreach ($einsaetze as $m) {
            $tag  = pruef_kalendertag($m['day'] ?? null, 'day', $pruef);
            $hhmm = (string)($m['started_local'] ?? '');
            if ($tag === null || !preg_match('/^\d{2}:\d{2}$/', $hhmm)) {
                $grund['datum']++; $uebersprungen++; continue;
            }
            /* OHNE DIENSTTAG KEIN EINSATZ. `missions.day_id` ist ein
             * Fremdschluessel; ein Einsatz ohne ihn waere verwaist (A11). Der
             * Fall tritt ein, wenn der Tag im Papierkorb liegt oder der Modus
             * 'keep' auf ein Datum traf, zu dem es keinen offenen Diensttag
             * gibt — beides wird gezaehlt und in der Meldung genannt, nicht
             * stillschweigend uebergangen. */
            $dayId = $dayIdByDate[$tag] ?? 0;
            if ($dayId === 0) {
                $grund['ohne_diensttag'] = ($grund['ohne_diensttag'] ?? 0) + 1;
                $uebersprungen++; continue;
            }
            if (($m['dup'] ?? 'insert') === 'skip') {
                $grund['auswahl']++; $uebersprungen++; continue;
            }

            // Ortszeit mit Kalendertagspruefung (B2)
            $startedAt = pruef_ortszeit_zu_utc($tag, $hhmm, 0, 'started_local', $pruef);
            if ($startedAt === null) { $grund['uhrzeit']++; $uebersprungen++; continue; }

            // Chiffretext nur formal pruefen — der Inhalt geht den Server
            // nichts an. Grenzen jetzt aus validate_lib.php (40…60000), damit
            // alle vier Schreibwege denselben Massstab haben; hier galt bisher
            // 20 als Untergrenze, im Formular 16 — beide unterhalb dessen, was
            // ein AES-GCM-Chiffretext ueberhaupt sein kann.
            $blob = pruef_pat_blob($m['pat_blob'] ?? null, 'pat_blob', $pruef);

            // Endzeit: Liefert die Datei eine, wird sie uebernommen; sonst
            // bleibt es beim bisherigen Verhalten (Ende = Beginn), damit sich
            // fuer die Jahreslisten-Profile nichts aendert.
            $endedAt = $utc($m['ended_utc'] ?? null) ?? $startedAt;

            /* Abweichende Besatzung des Einsatzes: role_code => name (E7).
             * Sie wird NACH dem Schreiben der Zeile in `mission_crew` gefuehrt,
             * nicht als Spalten hier — siehe unten. */
            $mCrew = [];
            foreach ((array)($m['crew'] ?? []) as $rolle => $name) {
                if (!array_key_exists((string)$rolle, CREW_ROLES)) { continue; }
                $w = $txt($name, 120);
                if ($w !== null) { $mCrew[(string)$rolle] = $w; }
            }

            $werte = [
                $txt($m['transport_dest'] ?? null, 190),
                $flag($m['winch'] ?? null),
                $flag($m['crew_override'] ?? null),
                $blob,
                $zahl($m['site_ele_m'] ?? null, -500, 9000),
                $zahl($m['distance_m'] ?? null, 0, 100000000),
                $zahl($m['ascent_m'] ?? null, 0, 1000000),
                $flag($m['schockraum'] ?? null),
                $flag($m['secondary'] ?? null),
                $zahl($m['winch_cycles'] ?? null, 0, 127),
                $zahl($m['winch_cycles_pat'] ?? null, 0, 127),
                $flag($m['winch_airload'] ?? null),
                $flag($m['bergwacht'] ?? null),
                $txt($m['bw_unit'] ?? null, 120),
                $txt($m['bw_info'] ?? null, 190),
                $txt($m['other_ema'] ?? null, 190),
                $txt($m['notes'] ?? null, 2000),
            ];

            $id = null;
            if (($m['dup'] ?? '') === 'overwrite' && !empty($m['overwrite_id'])) {
                $id = (int)$m['overwrite_id'];

                /* ZUGEHOERIGKEIT DIREKT PRUEFEN.
                 *
                 * Frueher wurde sie aus der Zahl der geaenderten Zeilen
                 * erschlossen — und die Datenbank liefert die Zahl der
                 * GEAENDERTEN, nicht der getroffenen Zeilen. Wer alle Werte auf
                 * das setzt, was schon dasteht, bekommt null zurueck. Daraus
                 * schloss der Code "gehoert jemand anderem" und uebersprang den
                 * Einsatz samt der danach folgenden Bloecke fuer Phasen,
                 * Reanimation und Rettungsmittel.
                 *
                 * Praktisch wichtigster Fall: Jemand importiert erneut, weil er
                 * NUR die Phasenzeiten korrigiert hat. Die Kopfdaten sind
                 * unveraendert — genau dann greift der Fehlschluss, und genau
                 * die Korrektur, um die es ging, wird verworfen. Gemeldet wird
                 * "uebersprungen", was nach "war schon da" klingt. */
                $gehoert->execute([$id, $userId]);
                if ($gehoert->fetchColumn() === false) {
                    $grund['fremd_oder_geloescht']++;
                    $uebersprungen++; continue;
                }
                $updE->execute(array_merge([$dayId, $startedAt, $endedAt], $werte, [$id, $userId]));
                $ersetzt++;
            } else {
                $insE->execute(array_merge(
                    [$userId, $devId, 'imp-' . bin2hex(random_bytes(12)),
                     $dayId, $startedAt, $endedAt],
                    $werte));
                $id = (int)$pdo->lastInsertId();
                $neu++;
            }

            /* ---- Abweichende Besatzung (mission_crew) ---------------------
             *
             * VOLLSTAENDIG ERSETZEN, aber nur wenn die Datei ueberhaupt etwas
             * dazu sagt. Der Unterschied ist wesentlich und folgt derselben
             * Ueberlegung wie COALESCE bei den Spalten oben (A9): Ein Export
             * OHNE personenbezogene Angaben liefert keine Besatzung. Wer eine
             * solche Datei zurueckspielt, darf die vorhandene Abweichung nicht
             * verlieren — sie stand nie in der Datei, sie wurde nicht
             * aufgehoben. Nennt die Datei dagegen eine Besatzung, gilt ihre. */
            if ($mCrew) {
                $pdo->prepare('DELETE FROM mission_crew WHERE mission_id = ?')->execute([$id]);
                $insMC = $pdo->prepare('INSERT INTO mission_crew (mission_id, role_code, name)
                                        VALUES (?,?,?)');
                foreach ($mCrew as $rolle => $name) { $insMC->execute([$id, $rolle, $name]); }
            }

            // Der Diensttag muss den Einsatz umschliessen (JSON-Vertrag 4.4).
            dt_zeitraum_fortschreiben($pdo, $dayId, $startedAt, $endedAt);

            /* ---- Phasen ---------------------------------------------------
             * Liefert die Datei Phasen (Rueckimport der eigenen Exporte), wird
             * der komplette Satz ersetzt — ein Mischen aus alt und neu waere
             * nicht nachvollziehbar. Liefert sie keine (Jahreslisten), bleibt
             * es beim bisherigen Verhalten: Phase 2 anlegen, falls sie fehlt.
             *
             * Ohne wenigstens eine Phasenzeile laesst sich der Einsatz spaeter
             * nicht im Formular oeffnen — es rekonstruiert Beginn und Ende aus
             * den Phasen. Phase 2 = Alarmierung.
             */
            $phasen = is_array($m['phases'] ?? null) ? $m['phases'] : [];
            $gesetzt = [];
            if ($phasen) {
                /* KEINE ENTDOPPELUNG MEHR.
                 *
                 * Frueher wurde die zweite Zeile mit derselben Phasennummer
                 * verworfen. Das widerspricht dem JSON-Vertrag: Mehrfache
                 * Eintraege sind ausdruecklich erlaubt, weil eine erneut
                 * gesetzte Phase eine KORREKTUR ist und damit eine
                 * Information. Der Uhr-Weg speichert sie, dieser Weg warf sie
                 * weg — dieselben Daten ergaben je nach Weg einen anderen
                 * Bestand, und ein Rueckimport der eigenen Exporte verlor
                 * stillschweigend Zeilen.
                 *
                 * Statt der Entdoppelung begrenzt jetzt LIMIT_PHASEN die
                 * Menge. Sie ist bewusst hoch (500) — sie schuetzt vor einer
                 * entgleisten Nutzlast und darf nicht als Ersatz fuer die
                 * Entdoppelung dienen. */
                $phasen = pruef_menge($phasen, LIMIT_PHASEN, 'phases', $pruef);

                /* KOORDINATEN UEBERLEBEN DAS ERSETZEN (P10, A9).
                 *
                 * Der Satz wird ersetzt, nicht gemischt — das bleibt so. Aber
                 * die Koordinaten fallen unter die Export-Schranke: Ein Export
                 * ohne personenbezogene Angaben liefert die Phasenzeiten und
                 * KEINE lat/lon. Ohne diesen Uebertrag loeschte ein solcher
                 * Rueckimport den Einsatzort, obwohl er ihn nur nicht kannte.
                 *
                 * Uebertragen wird je Phasennummer der Reihe nach: die erste
                 * neue Zeile der Phase 4 erbt die erste alte, die zweite die
                 * zweite. Mehrfache Eintraege je Phase sind erlaubt (siehe
                 * oben), und der Export schreibt je Phase genau eine Spalte —
                 * die Reihenfolge ist damit die einzige Zuordnung, die es gibt.
                 *
                 * Nur wenn die Datei WEDER lat NOCH lon liefert. Gibt sie
                 * Koordinaten an, gelten ihre — auch dann, wenn sie von den
                 * bisherigen abweichen. */
                $altOrt = [];
                $selPhasen = $pdo->prepare(
                    'SELECT phase, lat, lon FROM mission_phases
                     WHERE mission_id = ? AND (lat IS NOT NULL OR lon IS NOT NULL)
                     ORDER BY occurred_at, id');
                $selPhasen->execute([$id]);
                foreach ($selPhasen->fetchAll() as $a) {
                    $altOrt[(int)$a['phase']][] = [$a['lat'], $a['lon']];
                }

                $delPhasen->execute([$id]);
                foreach ($phasen as $p) {
                    if (!is_array($p)) { continue; }
                    $nr = pruef_phase($p['phase'] ?? null, 'phases.phase', $pruef);
                    if ($nr === null) { continue; }
                    $wann = pruef_utc($p['at'] ?? null, 'phases.at', $pruef);
                    if ($wann === null && !empty($p['local'])) {
                        // Ortszeit mit Kalendertagspruefung (B2)
                        $wann = pruef_ortszeit_zu_utc($tag, (string)$p['local'], 0,
                                                      'phases.local', $pruef);
                    }
                    if ($wann === null) { continue; }
                    $lat = pruef_breite($p['lat'] ?? null, 'phases.lat', $pruef);
                    $lon = pruef_laenge($p['lon'] ?? null, 'phases.lon', $pruef);
                    if ($lat === null && $lon === null && !empty($altOrt[$nr])) {
                        [$lat, $lon] = array_shift($altOrt[$nr]);
                    }
                    $insPhase->execute([$id, $nr, $wann, $lat, $lon]);
                    $gesetzt[$nr] = true;
                }
            }
            if (!isset($gesetzt[2])) {
                $hatPhase2->execute([$id]);
                if ($hatPhase2->fetchColumn() === false) {
                    $insPhase->execute([$id, 2, $startedAt, null, null]);
                }
            }

            /* ---- Reanimation ----------------------------------------------
             * Ebenfalls ersetzend, und nur wenn die Datei etwas liefert. Ein
             * Einsatz ohne 'rea' in der Nutzlast behaelt seine vorhandene
             * Dokumentation — ein Import mit einem Format, das Reanimationen
             * gar nicht kennt, darf sie nicht loeschen.
             */
            if (is_array($m['rea'] ?? null)) {
                $delRea->execute([$id]);
                foreach (pruef_menge($m['rea'], LIMIT_REA_SESSION, 'rea', $pruef) as $s) {
                    if (!is_array($s)) { continue; }
                    $beginn = pruef_utc($s['started_at'] ?? null, 'rea.started_at', $pruef);
                    if ($beginn === null) { continue; }
                    $insReaS->execute([$id, $beginn]);
                    $sid = (int)$pdo->lastInsertId();
                    $ereignisse = pruef_menge($s['events'] ?? [], LIMIT_REA_EREIGN,
                                              'rea.events', $pruef);
                    foreach ($ereignisse as $e) {
                        if (!is_array($e)) { continue; }
                        // Nur bekannte Schluessel — ein freier Text waere im
                        // Formular spaeter nicht darstellbar.
                        $typ  = pruef_reanimationsart($e['type'] ?? null, 'rea.events.type', $pruef);
                        $wann = pruef_utc($e['at'] ?? null, 'rea.events.at', $pruef);
                        if ($typ === null || $wann === null) { continue; }
                        $insReaE->execute([$sid, $typ, $wann]);
                    }
                }
            }

            // Weitere Rettungsmittel als eigene Zeilen (einzeln entfernbar),
            // doppelte und leere verworfen — gleiche Regel wie im Formular.
            $rm = is_array($m['resources'] ?? null) ? $m['resources'] : [];
            $sauber = [];
            foreach ($rm as $name) {
                $name = mb_substr(trim((string)$name), 0, 120);
                if ($name !== '' && !in_array($name, $sauber, true)) { $sauber[] = $name; }
                if (count($sauber) >= LIMIT_RESSOURCEN) { break; }
            }
            $delRes->execute([$id]);
            foreach ($sauber as $name) { $insRes->execute([$id, $name]); }

            if ($ersterTag === null || $tag < $ersterTag) { $ersterTag = $tag; }
        }

        $pdo->commit();
        json_out([
            'ok' => true,
            'days_inserted'         => $tageNeu,
            'days_updated'          => $tageGeaendert,
            'days_skipped_trash'    => $tageUebersprungen,
            'missions_inserted'     => $neu,
            'missions_overwritten'  => $ersetzt,
            'missions_skipped'      => $uebersprungen,
            // Aufgeschluesselt nach Ursache: Die blosse Zahl war nicht
            // deutbar (M5-14).
            'skipped_reasons'       => array_filter($grund),
            'rejected'              => $pruef->nachUrsache(),
            'first_day'             => $ersterTag,
        ]);
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_fehler($ex, 'commit');
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['error' => 'method'], 405);
    }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'csrf'], 403);
    }

    $b = json_decode(file_get_contents('php://input'), true);
    if (!is_array($b)) { json_out(['error' => 'payload'], 400); }

    $action = (string)($b['action'] ?? '');
    if ($action === 'commit') {
        import_commit($b, $userId);
    }
    if ($action !== 'check') {
        json_out(['error' => 'action'], 400);
    }

    /* ---- Eingaben saeubern ------------------------------------------------ */
    $tage = [];
    foreach ((array)($b['days'] ?? []) as $d) {
        $d = (string)$d;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) { $tage[$d] = true; }
        if (count($tage) >= 500) { break; }
    }
    $tage = array_keys($tage);

    $antwortTage = [];

    /* ---- Diensttage samt vorhandener Einsaetze ---------------------------- */
    if ($tage) {
        $platz = implode(',', array_fill(0, count($tage), '?'));

        /* Datentrennung! Zusaetzlich nur nicht geloeschte Tage.
         *
         * JE DATUM DER ERSTE offene Diensttag. Mehrere sind seit E9 zulaessig,
         * aber der Import kann sie nicht unterscheiden — er gruppiert nach
         * Kalendertag (siehe gruppiere() in assets/import.js). Genommen wird
         * deshalb derselbe, dem auch der Uebernahmelauf die Einsaetze zuordnet:
         * der frueheste. Die Auskunft „Diensttag vorhanden" waere sonst nicht
         * dieselbe Zeile, die spaeter beschrieben wird. */
        $st = db()->prepare("SELECT id, day, vehicle_id, base_id, kind,
                                    vehicle_name, base_name, started_at
                             FROM days
                             WHERE user_id = ? AND deleted_at IS NULL
                               AND day IN ($platz)
                             ORDER BY day, started_at, id");
        $st->execute(array_merge([$userId], $tage));
        $tagIds = [];
        foreach ($st->fetchAll() as $t) {
            $d = (string)$t['day'];
            if (isset($antwortTage[$d])) { continue; }   // nur der erste je Datum
            $tagIds[$d] = (int)$t['id'];
            $antwortTage[$d] = [
                'day_id'       => (int)$t['id'],
                'crew'         => [],
                'vehicle_id'   => $t['vehicle_id'] !== null ? (int)$t['vehicle_id'] : null,
                'base_id'      => $t['base_id'] !== null ? (int)$t['base_id'] : null,
                'kind'         => $t['kind'] !== null ? (string)$t['kind'] : null,
                'vehicle_name' => $t['vehicle_name'] !== null ? (string)$t['vehicle_name'] : null,
                'base_name'    => $t['base_name'] !== null ? (string)$t['base_name'] : null,
                'missions'     => [],
            ];
        }
        // Besatzung der gefundenen Diensttage in EINER Abfrage.
        if ($tagIds) {
            $nachId = array_flip($tagIds);   // Kennung -> Datum
            foreach (sql_in_bloecken(db(),
                    'SELECT day_id, role_code, name FROM day_crew
                     WHERE day_id IN ({IDS})', array_values($tagIds)) as $c) {
                $d = $nachId[(int)$c['day_id']] ?? null;
                if ($d !== null && $c['name'] !== null) {
                    $antwortTage[$d]['crew'][(string)$c['role_code']] = (string)$c['name'];
                }
            }
        }

        /* Einsaetze dieser Tage. Der Join auf `days` ist seit Web 6.0.0 der
         * vorgesehene Weg (Konzept 4.11); den frueheren Fall „Einsatz ohne
         * angelegten Tag" gibt es nicht mehr — `day_id` ist ein Fremdschluessel.
         * pat_blob geht als Chiffretext mit, damit der Browser die
         * Einsatznummer fuer den Dublettenabgleich lokal entschluesseln kann
         * (siehe Kopfkommentar). */
        $st = db()->prepare("SELECT x.id, d.day, x.started_at, x.pat_blob
                             FROM missions x
                             JOIN days d ON d.id = x.day_id
                             WHERE x.user_id = ? AND x.deleted_at IS NULL
                               AND d.deleted_at IS NULL
                               AND d.day IN ($platz)
                             ORDER BY x.started_at");
        $st->execute(array_merge([$userId], $tage));
        foreach ($st->fetchAll() as $m) {
            $tag = (string)$m['day'];
            /* Der Zweig kann nur greifen, wenn der Einsatz an einem Diensttag
             * eines ANDEREN Datums haengt als die Datei annimmt — etwa nach
             * einem Verschieben. Er bleibt als Absicherung stehen: Ohne ihn
             * waere die Antwort unvollstaendig und die Dublettenpruefung
             * uebersaehe einen vorhandenen Einsatz. */
            if (!isset($antwortTage[$tag])) {
                $antwortTage[$tag] = ['day_id' => null, 'crew' => [],
                                      'vehicle_id' => null, 'base_id' => null,
                                      'kind' => null, 'vehicle_name' => null,
                                      'base_name' => null, 'missions' => []];
            }
            $antwortTage[$tag]['missions'][] = [
                'id'       => (int)$m['id'],
                'hhmm'     => fmt_local($m['started_at']),
                'pat_blob' => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
            ];
        }
    }

    json_out(['days' => $antwortTage]);
} catch (Throwable $ex) {
    // Lesbare Meldung statt leerem HTTP 500 — die Seite zeigt sie an.
    json_fehler($ex, 'check');
}
