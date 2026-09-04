<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';

/**
 * Einsaetze eines Jahres oder Monats — Grundlage der Zeitraum-Uebersicht.
 * Bewusst OHNE Trackpunkte: Bei einem ganzen Jahr waeren das schnell
 * hunderttausende Koordinaten. Die Kartenansicht (Einsatzort-Pins) kommt
 * stattdessen aus den Koordinaten im `pat_blob`, die der Browser fuer die
 * Tabellenspalten ohnehin entschluesselt. Verschluesselte Angaben gehen wie
 * ueberall als `pat_blob` an den Browser, der sie selbst entschluesselt.
 */

/* Nur lesen (M3-11). Die Uebersicht war fuer jede Methode offen — ein POST
 * mit einem Formular von fremder Seite bekam dieselbe Antwort wie ein GET.
 * Gelesen wird dabei nichts Fremdes (die Abfrage haengt an $userId), aber
 * ein lesender Endpunkt, der POST beantwortet, ist eine Einladung, die
 * niemand aussprechen wollte. */
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

$jahr  = (string)($_GET['y'] ?? '');
$monat = (string)($_GET['m'] ?? '');
if (!preg_match('/^\d{4}$/', $jahr)) { json_out(['error' => 'Ungültiges Jahr'], 400); }
/* Der Wertebereich zaehlt, nicht nur die Ziffernzahl (M3-06).
 *
 * Vorher genuegten zwei Ziffern. "00" und "13" kamen damit durch, und dann
 * ging es schief: strtotime('2026-00-01') scheitert und liefert false,
 * date('Y-m-t', false) rechnet mit dem Zeitpunkt 0 weiter. Herausgekommen ist
 * kein Fehler, sondern ein FALSCHER ZEITRAUM — bei m=00 der Dezember des
 * Vorjahres. Eine Uebersicht, die stillschweigend einen anderen Monat zeigt
 * als den angefragten, ist schlimmer als eine, die sich weigert. */
if ($monat !== '' && !preg_match('/^(0[1-9]|1[0-2])$/', $monat)) {
    json_out(['error' => 'Ungültiger Monat'], 400);
}

if ($monat !== '') {
    $von  = sprintf('%s-%s-01', $jahr, $monat);
    $bis  = date('Y-m-t', strtotime($von));
} else {
    $von = $jahr . '-01-01';
    $bis = $jahr . '-12-31';
}

try {
    /* DIE STATISTIK RECHNET NACH DIENSTTAG (E14). Der Zeitraum filtert deshalb
     * `days.day`, nicht `missions.started_at`: Ein Einsatz um 01:30 eines
     * Dienstes, der am Vortag begonnen hat, zaehlt zum Vortag — und faellt am
     * Monatsersten damit noch in den Vormonat. Die Einsatzsuche macht es
     * ausdruecklich anders (api/suchindex.php); der Unterschied ist gewollt und
     * im Handbuch erklaert.
     *
     * Der Join auf `days` ist seit Web 6.0.0 der vorgesehene Weg (Konzept
     * 4.11). Bis dahin trug jeder Einsatz sein Tagesdatum selbst. */
    /* `ended_at` STATT DER PHASE-9-UNTERABFRAGE (Web 14.2.2, F-R64-05).
     * Sie war die einzige Verwenderin von `p9_at` hier -- und eine
     * korrelierte Unterabfrage je Zeile fuer einen Wert, der als Spalte
     * danebensteht. */
    $st = db()->prepare('SELECT m.id, m.day_id, d.day, d.kind, m.started_at, m.ended_at,
                           m.distance_m,
                           m.winch, m.bergwacht, m.secondary, m.winch_cycles,
                           m.false_alarm, m.site_ele_m, m.pat_blob
                         FROM missions m
                         JOIN days d ON d.id = m.day_id
                         WHERE m.user_id = ? AND d.day BETWEEN ? AND ?
                           AND m.deleted_at IS NULL AND d.deleted_at IS NULL
                         ORDER BY m.started_at');
    $st->execute([$userId, $von, $bis]);

    $missions = [];
    foreach ($st->fetchAll() as $m) {
        /* DAUER = BEGINN BIS ENDE (Web 14.2.2, F-R64-05). Vorher stand hier
         * Phase 9; ein geschnittener oder importierter Einsatz hat keine und
         * galt damit als „kein Ende", obwohl er abgeschlossen ist und ein
         * `ended_at` traegt. Begruendung und Messung in `api/day.php`. */
        $dur = null;
        if ($m['ended_at'] !== null) {
            $dur = (new DateTime($m['ended_at']))->getTimestamp()
                 - (new DateTime($m['started_at']))->getTimestamp();
        }
        $missions[] = [
            'id'         => (int)$m['id'],
            'day_id'     => (int)$m['day_id'],
            'day'        => (string)$m['day'],
            /* Die ART DES DIENSTTAGS, an dem der Einsatz haengt (Etappe 3).
             * 'air' | 'ground' | null (neutraler Diensttag, E26). Sie steuert
             * im Browser den Tab, die Kacheln und die Karte — ohne sie muesste
             * die Uebersicht je Tab nachladen. */
            'kind'       => $m['kind'] !== null ? (string)$m['kind'] : null,
            'start_hhmm' => fmt_local($m['started_at']),
            'duration_s' => $dur,
            'distance_m' => $m['distance_m'] !== null ? (int)$m['distance_m'] : null,
            'winch'      => (int)$m['winch'] === 1,
            'bergwacht'  => (int)$m['bergwacht'] === 1,
            'secondary'  => (int)$m['secondary'] === 1,
            'false_alarm' => (int)$m['false_alarm'] === 1,
            'winch_cycles' => $m['winch_cycles'] !== null ? (int)$m['winch_cycles'] : null,
            'site_ele_m'   => $m['site_ele_m']   !== null ? (int)$m['site_ele_m']   : null,
            'pat_blob'   => !empty($m['pat_blob']) ? (string)$m['pat_blob'] : null,
        ];
    }

    // Kennzahl 'tage': alle im Zeitraum ANGELEGTEN Diensttage, auch ohne Einsatz —
    // bewusste Semantikaenderung (vorher: COUNT(DISTINCT day) aus missions, zaehlte
    // also nur Tage mit dokumentiertem Einsatz). Divisor der Durchschnittswerte
    // in der Statistiktabelle der Zeitraum-Uebersicht.
    //
    // Gezaehlt werden ZEILEN, nicht Kalendertage: Zwei Dienste an einem Tag sind
    // seit E9 zwei Diensttage, und ein Durchschnitt „Einsaetze je Diensttag"
    // waere sonst um den Faktor der Doppeltage zu hoch.
    //
    // AUFGETEILT NACH ART (Etappe 3, E28/E31). Die Tabs rechnen mit
    // unterschiedlichen Divisoren: „Ø Einsaetze / Flugtag" im Luftrettungs-Tab
    // darf nur durch die luftgebundenen Diensttage teilen. Neutrale Diensttage
    // (ohne Rettungsmittel, E26) tragen keine Art und stehen deshalb in einer
    // eigenen Zahl — der Tab „Gemischt" zaehlt sie mit und weist sie aus, die
    // beiden Artentabs nicht. Ohne diese Zahl waere die Abweichung zwischen
    // „Gemischt" und der Summe der Artentabs nicht erklaerbar.
    //
    // GERECHNET WIRD IN SQL, nicht aus der Einsatzliste: Ein Diensttag OHNE
    // Einsatz zaehlt mit (siehe oben), taucht in `missions` aber nicht auf.
    $tage = db()->prepare("SELECT COALESCE(kind, '') AS art, COUNT(*) AS n FROM days
                            WHERE user_id = ? AND day BETWEEN ? AND ? AND deleted_at IS NULL
                            GROUP BY COALESCE(kind, '')");
    $tage->execute([$userId, $von, $bis]);
    $jeArt = ['air' => 0, 'ground' => 0, 'neutral' => 0];
    $gesamt = 0;
    foreach ($tage->fetchAll() as $z) {
        $art = (string)$z['art'];
        $n   = (int)$z['n'];
        $gesamt += $n;
        $jeArt[$art === 'air' || $art === 'ground' ? $art : 'neutral'] += $n;
    }

    /* Ueber json_out() wie die uebrigen neun Endpunkte. Diese Datei schrieb
       ihre Antworten selbst und ging damit auch am `Cache-Control: no-store`
       vorbei, das json_out() setzt — obwohl der Kommentar dort „Zeitraum"
       ausdruecklich als einen der Endpunkte nennt, die es brauchen. Der
       Unterschied ist nicht theoretisch: Die Antwort traegt Datum, Uhrzeit,
       Einsatznummer und Koordinaten im Klartext.
       Mit der Umstellung entfaellt JSON_UNESCAPED_UNICODE — Umlaute stehen
       jetzt als \uXXXX in der Antwort. Fuer den Abnehmer ist das dasselbe:
       zeitraum.php liest mit res.json(). */
    /* ---- Standorte des Zeitraums (E-P3-40, ab Web 9.6.0) -----------------
     *
     * Das Standort-Haus gehoert seit P3 auf JEDE Karte, sobald Koordinaten
     * vorliegen — Tages-, Einsatz- und Zeitraumkarte. Die Zeitraumkarte
     * bekommt dafuer die eingefrorenen Standorte der DIENSTTAGE des
     * Zeitraums, entdupliziert nach Koordinate: Ein Monat mit fuenf Diensten
     * derselben Wache hat einen Standort, nicht fuenf uebereinander liegende
     * Haeuser.
     *
     * KLARTEXT, und das ist richtig so: `base_name`/`base_lat`/`base_lon`
     * sind der eingefrorene Standort des Dienstes (E8), kein Patientendatum.
     * Sie stehen unverschluesselt in `days` und reisen wie `kind` und
     * `vehicle_name` mit. Die verschluesselten Angaben bleiben davon
     * unberuehrt im `pat_blob`. */
    $orte = db()->prepare('SELECT DISTINCT d.base_name, d.base_lat, d.base_lon, d.kind
                             FROM days d
                            WHERE d.user_id = ? AND d.day BETWEEN ? AND ?
                              AND d.deleted_at IS NULL
                              AND d.base_lat IS NOT NULL AND d.base_lon IS NOT NULL');
    $orte->execute([$userId, $von, $bis]);
    $bases = [];
    $gesehen = [];
    foreach ($orte->fetchAll() as $b) {
        /* Auf sechs Nachkommastellen entduplizieren — dieselbe Genauigkeit,
           mit der die Ortswahl Koordinaten schreibt (assets/ortswahl.js). */
        $schluessel = round((float)$b['base_lat'], 6) . ',' . round((float)$b['base_lon'], 6);
        if (isset($gesehen[$schluessel])) { continue; }
        $gesehen[$schluessel] = true;
        $bases[] = [
            'name' => (string)($b['base_name'] ?? ''),
            'lat'  => (float)$b['base_lat'],
            'lon'  => (float)$b['base_lon'],
            'kind' => $b['kind'] === null ? null : (string)$b['kind'],
        ];
    }

    json_out([
        'jahr'     => $jahr,
        'monat'    => $monat !== '' ? $monat : null,
        'von'      => $von,
        'bis'      => $bis,
        'tage'     => $gesamt,
        'tage_art' => $jeArt,
        'bases'    => $bases,
        'missions' => $missions,
    ]);
} catch (Throwable $ex) {
    // Statt eines leeren HTTP 500 (z. B. fehlende Spalte nach vergessener
    // Migration) eine lesbare Fehlermeldung — das Frontend zeigt sie an.
    json_fehler($ex, 'range');
}
