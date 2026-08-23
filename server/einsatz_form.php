<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/mission_fields_lib.php';
require_once __DIR__ . '/diensttag_lib.php';
$FIELDS = require __DIR__ . '/mission_fields.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;

/* ---- Diensttag bestimmen --------------------------------------------------
 *
 * Beim Bearbeiten kommt er aus dem Einsatz, beim Nachtragen aus `?d=<Kennung>`.
 * ER IST PFLICHT: Seit E9 bestimmt ein Datum keinen Diensttag mehr, und ohne
 * Diensttag gaebe es keinen Standort, aus dem sich Vorschlagslisten ableiten,
 * und keinen Rollensatz, aus dem sich die Besatzungsfelder ergeben. Wer einen
 * Einsatz nachtragen will, waehlt vorher den Dienst — in der Uebersicht oder
 * ueber „+ Diensttag anlegen". */
$dayId = 0;
if ($editing) {
    $dq = db()->prepare('SELECT day_id FROM missions
                         WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $dq->execute([$id, $userId]);
    $w = $dq->fetchColumn();
    if ($w === false) { http_response_code(404); exit('Einsatz nicht gefunden.'); }
    $dayId = $w === null ? 0 : (int)$w;
} else {
    $dayId = (int)($_GET['d'] ?? $_POST['day_id'] ?? 0);
}
$tag = $dayId > 0 ? dt_laden($userId, $dayId) : null;
if ($tag === null) {
    http_response_code(400);
    exit('Kein Diensttag gewählt. Bitte den Einsatz aus der Diensttagübersicht '
       . 'heraus nachtragen.');
}
$dayBaseId = $tag['base_id'] !== null ? (int)$tag['base_id'] : null;

/* Rollen dieses Diensttags: der EINGEFRORENE Rollensatz aus `day_crew` (E8).
 * Er steuert, welche Besatzungsfelder sichtbar sind ('role_gate'). Ein
 * neutraler Diensttag hat keine Rollen (E26) — dann sind alle verborgen ausser
 * den bereits belegten. */
$dayRoles = dt_crew($dayId);
/* Art und Faehigkeiten desselben Diensttags, beide EINGEFROREN (E8). Sie
 * steuern 'kind_gate' und 'cap_gate' genauso, wie `day_crew` 'role_gate'
 * steuert — gefragt wird immer der Dienst, nie das heutige Rettungsmittel.
 * Damit verlieren dokumentierte Einsaetze nichts, wenn Jahre spaeter der
 * Windenhaken am Hubschrauber fällt (A13e). */
$dayKind = $tag['kind'] !== null ? (string)$tag['kind'] : null;
$dayCaps = dt_faehigkeiten($dayId);
$CREW_FELDER = mf_crew_felder();

/* Abweichende Besatzung dieses Einsatzes (mission_crew). Sie ist keine Spalte
 * mehr, deshalb wird sie eigens geladen: role_code => name. */
$missionCrew = [];
if ($editing) {
    $cq = db()->prepare('SELECT c.role_code, c.name FROM mission_crew c
                          JOIN missions m ON m.id = c.mission_id
                         WHERE c.mission_id = ? AND m.user_id = ?');
    $cq->execute([$id, $userId]);
    foreach ($cq->fetchAll() as $z) { $missionCrew[(string)$z['role_code']] = (string)$z['name']; }
}

/* Andere Rettungsmittel: Vorbelegungen DES STANDORTS (E15) und bereits
 * zugeordnete Eintraege. Ohne Standort am Diensttag gibt es keine
 * Vorschlagsliste — Freitext bleibt uneingeschraenkt moeglich. */
$rmVorlagen = [];
if ($dayBaseId !== null) {
    $q = db()->prepare('SELECT DISTINCT name FROM resources
                        WHERE base_id = ? AND (user_id = ? OR user_id IS NULL) ORDER BY name');
    $q->execute([$dayBaseId, $userId]);
    $rmVorlagen = $q->fetchAll(PDO::FETCH_COLUMN);
}
$rmGewaehlt = [];
if ($editing) {
    $q = db()->prepare('SELECT r.name FROM mission_resources r
                        JOIN missions m ON m.id = r.mission_id
                        WHERE r.mission_id = ? AND m.user_id = ? ORDER BY r.id');
    $q->execute([$id, $userId]);
    $rmGewaehlt = $q->fetchAll(PDO::FETCH_COLUMN);
}
// Nach fehlgeschlagenem Absenden die Eingaben behalten
if (($_POST['f_other_resources'] ?? null) !== null && is_array($_POST['f_other_resources'])) {
    $rmGewaehlt = array_values(array_filter(array_map('trim', $_POST['f_other_resources'])));
}
$error = null;

/* ---- Helfer: lokale Uhrzeit (Berlin) -> UTC-DATETIME ----------------------
   local_to_utc() steht seit Web 2.8.0 in db.php (neben fmt_local), weil der
   Import denselben Weg braucht. Hier bewusst KEINE zweite Definition — PHP
   wuerde das mit einem Fatal Error quittieren. */

/* ---- Bestehenden Einsatz laden (nur eigene!) ------------------------------ */
$mission = null; $phases = [];
if ($editing) {
    $st = db()->prepare('SELECT * FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
    $st->execute([$id, $userId]);
    $mission = $st->fetch();
    if (!$mission) { http_response_code(404); exit('Einsatz nicht gefunden.'); }
    $ph = db()->prepare('SELECT phase, occurred_at FROM mission_phases
                         WHERE mission_id = ? ORDER BY occurred_at');
    $ph->execute([$id]);
    $phases = $ph->fetchAll();
}

/* ---- GPS-Aufzeichnung vorhanden? (Web 7.0.0) ------------------------------
 *
 * Entscheidet, ob der ABFAHRTORT ueberhaupt im Formular steht. Er ist
 * ausschliesslich dazu da, ohne Track eine Linie auf die Karte zu bekommen
 * (E34) — liegt ein Track vor, zeichnet die Karte den tatsaechlich geflogenen
 * oder gefahrenen Weg, und die Auswahl daneben ist eine Frage ohne Wirkung.
 *
 * Zwei Punkte reichen als Schwelle, weil erst zwei Punkte eine Linie ergeben:
 * dieselbe Bedingung, die die Einsatzansicht fuer ihre Luftlinie anlegt
 * (`m.track.length > 1`). Ein einzelner Punkt ist kein Weg.
 *
 * BEIM NACHTRAGEN gibt es noch keinen Track — das Feld steht dann immer da.
 * Reicht die Uhr spaeter einen nach, verschwindet es beim naechsten
 * Bearbeiten; die gespeicherte Regel bleibt unangetastet in der Datenbank und
 * wird von der Karte nur nicht mehr gebraucht. */
$hatTrack = false;
if ($editing) {
    $tq = db()->prepare('SELECT COUNT(*) FROM track_points
                          WHERE owner_type = \'mission\' AND owner_id = ?');
    $tq->execute([$id]);
    $hatTrack = (int)$tq->fetchColumn() > 1;
}

/* ---- Bezugsdatum der Uhrzeiten -------------------------------------------
 *
 * ES IST KEIN EINGABEFELD MEHR (Web 7.0.0). Bis Web 6.3.0 stand „Einsatzdatum"
 * als eigenes Feld im Formular — beim Bearbeiten schreibgeschuetzt, beim
 * Nachtragen aenderbar. Direkt darueber stand der Diensttag mit SEINEM Datum,
 * und in aller Regel waren beide gleich: zwei Datumsangaben, von denen die
 * zweite nichts hinzufuegte und trotzdem gelesen und geprueft werden musste.
 *
 * Der eine Fall, fuer den das Feld da war, bleibt vollstaendig abgedeckt: der
 * Einsatz NACH MITTERNACHT an einem Dienst, der am Vortag begann. Er wird
 * jetzt erkannt statt eingetippt (siehe unten, „Tageswechsel"). Das ist
 * ohnehin die verlaesslichere Quelle — der Dienst weiss, wann er angefangen
 * hat, und ein von Hand gesetztes Datum war eine Fehlerquelle mehr.
 *
 * Beim BEARBEITEN bleibt es beim gespeicherten Datum aus `started_at` in
 * Ortszeit: Was einmal dokumentiert ist, verschiebt kein Formularaufruf.
 */
$day = $editing ? fmt_local((string)$mission['started_at'], 'Y-m-d') : (string)$tag['day'];

/* Beginn des Dienstes als Ortszeit „HH:MM" — Grenze des Tageswechsels beim
 * Nachtragen. Ohne Beginn (Altbestand, von Hand angelegter Tag ohne Zeit)
 * gibt es keine Grenze und damit keinen Wechsel. */
$tagStartHhmm = ($tag['started_at'] ?? null) !== null
    ? fmt_local((string)$tag['started_at']) : null;

/* ---- Speichern ------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    /* $day steht bereits fest (siehe oben) und kommt NICHT mehr aus dem
     * Formular. Die Pruefung bleibt trotzdem: Sie faengt einen unmoeglichen
     * Kalendertag ab, bevor local_to_utc() ihn stillschweigend verschiebt —
     * das Muster allein liess den 30. Februar durch, und die Phasenzeiten
     * eines ganzen Einsatzes lagen danach am 2. Maerz (B2). */
    if (pruef_kalendertag($day, 'Datum') === null) { $error = 'Ungültiges Datum.'; }

    // Phasenzeilen einsammeln. Vor der Mitternachts-Logik wird nach
    // Phasennummer aufsteigend sortiert (stabil: Index als Tie-Breaker bei
    // doppelten Nummern) — Phasen 2..9 sind fachlich chronologisch, waehrend
    // eine reine Eingabereihenfolge-Verarbeitung eine nachtraeglich am Ende
    // ergaenzte, zeitlich fruehere Zeile faelschlich als Tagesueberschritt
    // deutet (z. B. 23:50 -> 00:10). Danach lasst die bestehende Prefill-
    // Abfrage (ORDER BY occurred_at) die Liste beim naechsten Oeffnen sortiert
    // erscheinen.
    $eingesammelt = [];
    if (!$error) {
        $nos = $_POST['ph_no'] ?? []; $times = $_POST['ph_time'] ?? [];
        foreach ((array)$nos as $i => $no) {
            $t = trim((string)($times[$i] ?? ''));
            if ($t === '') continue;
            $no = (int)$no;
            if ($no < 2 || $no > 9) continue;
            $eingesammelt[] = ['no' => $no, 'time' => $t, 'idx' => $i];
        }
        usort($eingesammelt, fn($a, $b) => $a['no'] <=> $b['no'] ?: $a['idx'] <=> $b['idx']);

        /* ---- TAGESWECHSEL (Web 7.0.0, Ersatz fuer das Feld „Einsatzdatum") --
         *
         * Ein Dienst laeuft ueber Mitternacht. Wird zu ihm ein Einsatz
         * nachgetragen, dessen erste Phase VOR dem Dienstbeginn liegt, kann
         * er nur zum Folgetag gehoeren: Frueher als der Dienst kann er nicht
         * begonnen haben, und derselbe Uhrzeitwert kommt an einem Tag nur
         * einmal vor.
         *
         * Beispiel: Dienst am 12.03. ab 07:00, nachgetragen wird eine
         * Alarmierung um 01:30. 01:30 des 12.03. laege fuenfeinhalb Stunden
         * vor Dienstbeginn — gemeint ist der 13.03.
         *
         * NUR BEIM NACHTRAGEN. Ein bestehender Einsatz behaelt sein Datum;
         * verschoben wird er ueber „Aktionen -> Verschieben", nicht durch das
         * erneute Speichern eines Formulars (E4). Und nur MIT bekanntem
         * Dienstbeginn — ohne ihn gibt es keine Grenze, an der sich der
         * Wechsel festmachen liesse. */
        $minuten = static function (string $hhmm): ?int {
            /* Verglichen werden MINUTEN, nicht Zeichenketten: „1:30" ist eine
             * gueltige Eingabe (die Maske in assets/zeitfeld.js fuellt die
             * fuehrende Null erst beim Verlassen des Feldes), und als Text
             * stuende sie hinter „07:00". Der Tageswechsel griffe dann
             * ausgerechnet in dem Fall nicht, fuer den er da ist. */
            if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($hhmm), $t)) { return null; }
            return (int)$t[1] * 60 + (int)$t[2];
        };
        $erst = $eingesammelt ? $minuten($eingesammelt[0]['time']) : null;
        $grenze = $tagStartHhmm !== null ? $minuten($tagStartHhmm) : null;
        if (!$editing && !$error && $erst !== null && $grenze !== null && $erst < $grenze) {
            $day = date('Y-m-d', strtotime($day . ' +1 day'));
        }
    }

    $rows = [];
    if (!$error) {
        $prev = null; $dayOffset = 0;
        foreach ($eingesammelt as $z) {
            $ts = local_to_utc($day, $z['time'], $dayOffset);
            if ($ts !== null && $prev !== null && $ts < $prev) {
                $dayOffset += 1;                       // Mitternacht ueberschritten
                $ts = local_to_utc($day, $z['time'], $dayOffset);
            }
            if ($ts === null) { $error = 'Ungültige Uhrzeit in den Phasen.'; break; }
            $rows[] = [$z['no'], $ts];
            $prev = $ts;
        }
        if (!$error && count($rows) === 0) { $error = 'Mindestens eine Phase mit Uhrzeit eintragen.'; }
    }

    if (!$error && count($rows) === 0) {
        /* Doppelt gesichert (M5-11).
         *
         * Oben steht bereits eine Pruefung auf mindestens eine Phase — aber
         * nur im else-Zweig einer Fallunterscheidung. Kommt spaeter ein
         * dritter Weg zu $rows hinzu, greift sie nicht mehr, und $rows[0][1]
         * ist dann ein Zugriff auf einen nicht vorhandenen Index: In PHP 8
         * eine Warnung und ein Nullwert, der als started_at in die Datenbank
         * ginge. Der Zugriff auf die erste Zeile prueft deshalb selbst, ob es
         * sie gibt — direkt dort, wo er stattfindet. */
        $error = 'Mindestens eine Phase mit Uhrzeit eintragen.';
    }

    if (!$error) {
        $startedAt = $rows[0][1];
        $endedAt   = $rows[count($rows) - 1][1];

        /* ---- Reanimationen einsammeln (A4.3, Backlog Nr. 1) -----------------
         *
         * Aufbau im Formular: rea[<n>][start] sowie rea[<n>][ev][<m>][typ] und
         * [zeit]. Die Nummern sind laufende Zaehler des Browsers ohne eigene
         * Bedeutung — gewertet wird die Reihenfolge, in der sie ankommen.
         * Deshalb muss beim Entfernen einer Zeile auch nichts umnummeriert
         * werden.
         *
         * ZEITRECHNUNG. Wie bei den Phasen gehoert eine Zeit, die vor ihrer
         * Bezugszeit liegt, dem Folgetag. Bezug ist beim Reanimationsbeginn
         * der Beginn des Einsatzes — frueher kann nicht reanimiert worden
         * sein — und bei jedem Ereignis das vorhergehende. Ohne diese Regel
         * landete eine Reanimation, die um 23:50 beginnt und um 00:10 endet,
         * mit dem Ende zwanzig Stunden vor ihrem Anfang.
         *
         * EINE ZEILE OHNE ZEIT IST KEIN EREIGNIS. Dieselbe Regel gilt oben
         * fuer die Phasen. Wer eine Zeile hinzufuegt und sie doch nicht
         * braucht, soll sie nicht erst wieder entfernen muessen. */
        $reaSitzungen = [];
        $reaRoh = $_POST['rea'] ?? [];
        if (!is_array($reaRoh)) { $reaRoh = []; }
        if (count($reaRoh) > LIMIT_REA_SESSION) {
            $error = 'Zu viele Reanimationen (höchstens ' . LIMIT_REA_SESSION . ').';
        }
        // Zeit einordnen: nie vor der Bezugszeit; sonst Folgetag.
        $reaZeit = function (string $hhmm, string $nichtVor) use ($day): ?string {
            $ts = local_to_utc($day, $hhmm, 0);
            if ($ts === null) { return null; }
            return $ts < $nichtVor ? local_to_utc($day, $hhmm, 1) : $ts;
        };
        foreach ($reaRoh as $sitz) {
            if ($error) { break; }
            if (!is_array($sitz)) { continue; }
            $start = trim((string)($sitz['start'] ?? ''));
            $evRoh = (isset($sitz['ev']) && is_array($sitz['ev'])) ? $sitz['ev'] : [];

            $getippt = [];
            foreach ($evRoh as $ev) {
                if (!is_array($ev)) { continue; }
                $t = trim((string)($ev['zeit'] ?? ''));
                if ($t === '') { continue; }
                $getippt[] = [trim((string)($ev['typ'] ?? '')), $t];
            }
            // Vollstaendig leere Reanimation: still verwerfen, nicht bemaengeln.
            if ($start === '' && !$getippt) { continue; }
            if ($start === '') {
                $error = 'Zu einer Reanimation fehlt der Reanimationsbeginn.';
                break;
            }
            if (count($getippt) > LIMIT_REA_EREIGN) {
                $error = 'Zu viele Ereignisse in einer Reanimation (höchstens '
                       . LIMIT_REA_EREIGN . ').';
                break;
            }

            $rStart = $reaZeit($start, $startedAt);
            if ($rStart === null) {
                $error = 'Ungültige Uhrzeit beim Reanimationsbeginn.';
                break;
            }
            $vorher = $rStart; $ereignisse = [];
            foreach ($getippt as [$typ, $t]) {
                /* 'beginn' steht nicht in resus_events — der Beginn steckt in
                 * started_at der Sitzung (JSON-Vertrag 3.3). Die Auswahl im
                 * Formular bietet ihn deshalb gar nicht erst an; die Pruefung
                 * hier ist die Absicherung dagegen, dass er auf anderem Weg
                 * hereinkommt. */
                $art = pruef_reanimationsart($typ, 'Reanimationsart');
                if ($art === null || $art === 'beginn') {
                    $error = 'Unbekannte Art eines Reanimationsereignisses.';
                    break;
                }
                $ts = $reaZeit($t, $vorher);
                if ($ts === null) {
                    $error = 'Ungültige Uhrzeit bei einem Reanimationsereignis.';
                    break;
                }
                $ereignisse[] = [$art, $ts];
                $vorher = $ts;
            }
            if ($error) { break; }
            $reaSitzungen[] = ['start' => $rStart, 'ereignisse' => $ereignisse];
        }

        /* Zusatzfelder generisch aus der zentralen Definition uebernehmen.
         * Checkbox-Unterfelder werden nur gespeichert, wenn der Haken gesetzt
         * ist — sonst geleert (kein Geister-Inhalt hinter "Nein").
         *
         * ZWEI ZIELE statt einem (Web 6.0.0): Felder mit 'store' => 'crew'
         * liegen nicht in `missions`, sondern als Zeile in `mission_crew`
         * (E7, siehe mission_fields.php). Sie landen deshalb in $crewVals und
         * nicht in $fieldCols — ein Feldname, der in beiden Listen stuende,
         * ergaebe ein UPDATE auf eine Spalte, die es nicht gibt. */
        $fieldCols = []; $fieldVals = []; $crewVals = [];
        $readField = function (string $col, array $f, bool $parentOn = true) use (&$readField, &$fieldCols, &$fieldVals, &$crewVals, &$error) {
            $type = $f['type'] ?? 'text';
            if ($type === 'resources') { return; }   // eigene Tabelle, siehe unten
            if ($type === 'checkbox') {
                $v = ($parentOn && isset($_POST['f_' . $col])) ? 1 : 0;
                $fieldCols[] = $col; $fieldVals[] = $v;
                /* Kinder einer Checkbox haengen am HAKEN, nicht an 'show_if'
                 * (Vorpruefung V4). Dieser Zweig bleibt unveraendert — er ist
                 * der, an dem der Windenblock haengt, und der darf sich nicht
                 * nebenbei aendern. */
                foreach (($f['children'] ?? []) as $cc => $cf) {
                    $readField($cc, $cf, $v === 1);
                }
                return;
            }
            $raw = trim((string)($_POST['f_' . $col] ?? ''));
            if (!$parentOn) { $raw = ''; }
            if ($type === 'number') {
                $v = ($raw === '') ? null : (string)(float)str_replace(',', '.', $raw);
            } elseif ($type === 'select') {
                /* Geprueft wird gegen die WERTE, nicht gegen die Beschriftungen
                 * (mf_optionen, Web 6.1.0): Bei der Transportart sind das zwei
                 * verschiedene Listen. 'options_src' bleibt ungeprueft — dort
                 * sind Stammdaten die Quelle, und die sind aenderbar. */
                $opts = $f['options'] ?? null;
                $v = ($raw === '') ? null : mb_substr($raw, 0, (int)($f['max'] ?? 120));
                if ($opts !== null && $v !== null
                    && !array_key_exists($v, mf_optionen($opts))) { $v = null; }
            } else {
                $v = mb_substr($raw, 0, (int)($f['max'] ?? 190));
                if ($v === '') { $v = null; }
            }
            if (($f['store'] ?? null) === 'crew') {
                $crewVals[(string)($f['role_code'] ?? substr($col, 5))] = $v;
            } else {
                $fieldCols[] = $col; $fieldVals[] = $v;
            }

            /* ---- Ortsfeld: die beiden Koordinatenspalten daneben (E37) -----
             *
             * Sie sind FREIWILLIG und nur zusammen gueltig; pruef_ortspaar()
             * setzt beide Regeln durch. KOORDINATEN OHNE BEZEICHNUNG werden
             * abgewiesen — dieselbe Regel wie beim Einsatzort (A13j): Sonst
             * stuende in den Listen ein Zahlenfragment, wo eine Klinik stehen
             * sollte. Der Browser faengt den Fall bereits ab; hier wird er
             * GEMELDET statt still bereinigt, damit ein Weg an der
             * Formularpruefung vorbei nicht unbemerkt Daten verliert. */
            if ($type === 'loc') {
                $ort = mf_ort_spalten($f);
                [$la, $lo] = pruef_ortspaar(
                    $parentOn ? ($_POST['f_' . $col . '_lat'] ?? null) : null,
                    $parentOn ? ($_POST['f_' . $col . '_lon'] ?? null) : null);
                if ($v === null && ($la !== null || $lo !== null)) {
                    $error = 'Zu den Koordinaten bei „' . (string)($f['label'] ?? $col)
                           . '“ fehlt die Bezeichnung.';
                    $la = null; $lo = null;
                }
                if (isset($ort['lat'])) { $fieldCols[] = $ort['lat']; $fieldVals[] = $la; }
                if (isset($ort['lon'])) { $fieldCols[] = $ort['lon']; $fieldVals[] = $lo; }
            }

            /* WERTABHAENGIGE UNTERFELDER (V4, A5). Der Checkbox-Zweig oben ist
             * vorher zurueckgekehrt; hier fallen alle uebrigen Typen durch, und
             * hier — und nur hier — wird 'show_if' ausgewertet. Ein
             * ausgeblendetes Unterfeld wird dadurch GELEERT statt einen
             * Geisterinhalt zu behalten: „Ambulant" mit eingetragener Zielklinik
             * waere ein Widerspruch in den Daten. */
            foreach (($f['children'] ?? []) as $cc => $cf) {
                $readField($cc, $cf, $parentOn && mf_show_if($cf, $v, $col));
            }
        };
        foreach ($FIELDS as $col => $f) { $readField($col, $f); }

        /* ---- Abfahrtort: die REGEL, nicht der Ort (E34, Konzept 4.6.1) -----
         *
         * Gespeichert wird in `missions.start_src` ausschliesslich, WOHER die
         * Koordinate stammt. Der Klartextwert verraet damit keinen Ort: Ein
         * Standort ist ohnehin kein Geheimnis, ein Einsatzort sehr wohl — und
         * 'prev_site' wie 'manual' zeigen auf verschluesselte Quellen.
         *
         * Kein Katalogfeld, weil die Beschriftungen nicht die gespeicherten
         * Werte sind (siehe mission_fields.php). */
        $startSrc = trim((string)($_POST['start_src'] ?? ''));
        if (!in_array($startSrc, ['base', 'prev_site', 'prev_dest', 'manual'], true)) {
            $startSrc = null;
        }
        $fieldCols[] = 'start_src'; $fieldVals[] = $startSrc;


        // PatientInnendaten: der Browser liefert NUR Chiffretext (pat_blob).
        // Leerer Wert = Blob nicht anfassen (z. B. Sitzung nicht entsperrt).
        if ($patReady) {
            $pb = (string)($_POST['pat_blob'] ?? '');
            if ($pb === '__CLEAR__') {
                $fieldCols[] = 'pat_blob'; $fieldVals[] = null;
            } elseif ($pb !== '') {
                /* MUSTERVERLETZUNG MELDEN STATT UEBERGEHEN.
                 *
                 * Frueher wurde die Spalte bei einem unpassenden Wert einfach
                 * nicht in die Aktualisierung aufgenommen: kein Fehler, keine
                 * Meldung, der bisherige Block blieb stehen. Wer eine Diagnose
                 * korrigiert und "gespeichert" liest, hatte danach die ALTE
                 * Diagnose in der Datenbank — und keinen Anhaltspunkt dafuer.
                 *
                 * Dieselbe Stelle ist beim Passwortwechsel bereits so geloest;
                 * dort steht das stille Uebergehen ausdruecklich als frueherer
                 * Fehler im Kommentar. Hier war es noch drin.
                 *
                 * Grenzen jetzt aus validate_lib.php (40…60000 statt 16…8000):
                 * Die Untergrenze ist hergeleitet, nicht geschaetzt — kuerzer
                 * als 40 Zeichen KANN ein AES-GCM-Chiffretext nicht sein. */
                $geprueft = new Pruefliste();
                $ok = pruef_pat_blob($pb, 'Geschützte Angaben', $geprueft);
                if ($ok === null) {
                    $error = 'Die geschützten Angaben konnten nicht gespeichert werden ('
                           . $geprueft->text() . '). Es wurde NICHTS geändert — bitte die '
                           . 'Seite neu laden und erneut versuchen.';
                } else {
                    $fieldCols[] = 'pat_blob'; $fieldVals[] = $ok;
                }
            }
        }

        /* Ein hier erst entstandener Fehler MUSS das Speichern verhindern.
         * Der umgebende Block laeuft unter !$error, geprueft VOR dem Einlesen
         * der Felder. Ohne diese zweite Abfrage wuerde ein Fehler aus der
         * Blockpruefung oben gemeldet UND gespeichert — die schlechteste aller
         * Kombinationen. */
        if (!$error) {

        $pdo = db(); $pdo->beginTransaction();
        try {
            if ($editing) {
                $set = 'started_at = ?, ended_at = ?, manual = 1, edited = 1';
                foreach ($fieldCols as $c) { $set .= ", `$c` = ?"; }
                $pdo->prepare("UPDATE missions SET $set WHERE id = ? AND user_id = ?")
                    ->execute(array_merge([$startedAt, $endedAt], $fieldVals, [$id, $userId]));
            } else {
                // Virtuelles Geraet "Manuelle Einträge" (deaktiviert: kann nie hochladen)
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
                $cols = 'user_id, device_id, client_ref, day_id, started_at, ended_at, final, manual, origin';
                $qms  = "?,?,?,?,?,?,1,1,'manual'";
                foreach ($fieldCols as $c) { $cols .= ", `$c`"; $qms .= ',?'; }
                $pdo->prepare("INSERT INTO missions ($cols) VALUES ($qms)")
                    ->execute(array_merge(
                        [$userId, (int)$devId, 'man-' . uniqid(), $dayId, $startedAt, $endedAt],
                        $fieldVals));
                $id = (int)$pdo->lastInsertId();
            }

            /* ---- Abweichende Besatzung (mission_crew) ----------------------
             *
             * VOLLSTAENDIG ERSETZEN, wie Phasen und Reanimationen: Ein Rollenfeld,
             * das geleert wurde, muss seine Zeile verlieren — sonst blieb der alte
             * Name als Abweichung stehen, obwohl im Formular nichts mehr stand.
             *
             * Geschrieben werden nur BELEGTE Rollen. Eine Zeile mit name = NULL
             * hat in `mission_crew` keine Bedeutung: Anders als bei `day_crew`,
             * wo die Zeilenmenge den Rollensatz bildet (E8), ist hier jede Zeile
             * eine Abweichung — und keine Abweichung ist keine Zeile. Ohne
             * gesetzten Haken raeumt $readField die Werte ohnehin ab
             * (Checkbox-Unterfelder), es bleibt dann nichts uebrig. */
            $pdo->prepare('DELETE FROM mission_crew WHERE mission_id = ?')->execute([$id]);
            $insC = $pdo->prepare('INSERT INTO mission_crew (mission_id, role_code, name)
                                   VALUES (?,?,?)');
            foreach ($crewVals as $role => $name) {
                if ($name === null || trim((string)$name) === '') { continue; }
                $insC->execute([$id, $role, mb_substr(trim((string)$name), 0, 120)]);
            }

            /* Der Diensttag muss den Einsatz umschliessen (JSON-Vertrag 4.4).
             * Ein nachgetragener Einsatz um 00:40 verlaengert den Dienst bis
             * dahin; ohne das laege er ausserhalb des Zeitraums seines eigenen
             * Dienstes. */
            dt_zeitraum_fortschreiben($pdo, $dayId, $startedAt, $endedAt);

            // Phasen vollstaendig ersetzen
            $pdo->prepare('DELETE FROM mission_phases WHERE mission_id = ?')->execute([$id]);
            $ins = $pdo->prepare('INSERT INTO mission_phases (mission_id, phase, occurred_at) VALUES (?,?,?)');
            foreach ($rows as $r) { $ins->execute([$id, $r[0], $r[1]]); }

            /* Reanimationen ebenso vollstaendig ersetzen (A4.3). Die Ereignisse
             * raeumt der Fremdschluessel mit ab (ON DELETE CASCADE), sie
             * brauchen kein eigenes DELETE. Beim Nachtragen laeuft das DELETE
             * ins Leere — das ist billiger als eine Fallunterscheidung, die
             * beim naechsten Umbau vergessen wuerde.
             *
             * Ein ueber dieses Formular gespeicherter Einsatz traegt danach
             * manual = 1; ingest.php ruehrt seine Reanimationen dann nicht mehr
             * an. Eine nachliefernde Uhr kann die hier eingetragenen Zeiten
             * also nicht ueberschreiben. */
            $pdo->prepare('DELETE FROM resus_sessions WHERE mission_id = ?')->execute([$id]);
            if ($reaSitzungen) {
                $insS = $pdo->prepare('INSERT INTO resus_sessions (mission_id, started_at) VALUES (?,?)');
                $insE = $pdo->prepare('INSERT INTO resus_events (session_id, type, occurred_at) VALUES (?,?,?)');
                foreach ($reaSitzungen as $sitz) {
                    $insS->execute([$id, $sitz['start']]);
                    $sid = (int)$pdo->lastInsertId();
                    foreach ($sitz['ereignisse'] as $e2) { $insE->execute([$sid, $e2[0], $e2[1]]); }
                }
            }

            $pdo->commit();

            // Einsatzort-Hoehe neu ermitteln: Der Track bleibt unveraendert,
            // aber die Phasenzeiten (Referenz Phase 5/6) koennen sich gerade
            // geaendert haben — eine einzige Implementierung, siehe
            // site_elevation_lib.php.
            require_once __DIR__ . '/site_elevation_lib.php';
            compute_site_elevation(db(), $id);

            // Rettungsmittel als eigene Zeilen speichern (einzeln entfernbar).
            // Doppelte und leere Eintraege werden dabei verworfen.
            $rm = $_POST['f_other_resources'] ?? [];
            if (!is_array($rm)) { $rm = []; }
            $sauber = [];
            foreach ($rm as $name) {
                $name = mb_substr(trim((string)$name), 0, 120);
                if ($name !== '' && !in_array($name, $sauber, true)) { $sauber[] = $name; }
            }
            db()->prepare('DELETE FROM mission_resources WHERE mission_id = ?')->execute([$id]);
            $insR = db()->prepare('INSERT INTO mission_resources (mission_id, name) VALUES (?, ?)');
            foreach ($sauber as $name) { $insR->execute([$id, $name]); }

            header('Location: einsatz.php?id=' . $id . ($editing ? '' : '&nachtrag=1'));
            exit;
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $error = 'Speichern fehlgeschlagen.';
        }

        }   // Ende: nur speichern, wenn kein Fehler entstanden ist
    }
}

/* ---- Vorbelegung fuer die Anzeige ----------------------------------------- */
$prefillRows = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nos = (array)($_POST['ph_no'] ?? []); $times = (array)($_POST['ph_time'] ?? []);
    foreach ($nos as $i => $no) { $prefillRows[] = [(int)$no, (string)($times[$i] ?? '')]; }
} elseif ($editing) {
    foreach ($phases as $p) { $prefillRows[] = [(int)$p['phase'], fmt_local($p['occurred_at'])]; }
} else {
    $prefillRows[] = [2, ''];                          // Alarmierung als Startzeile
}

/* Vorbelegung der Reanimationen (A4.3). Aufbau je Sitzung:
   ['start' => 'HH:MM', 'ev' => [['typ' => 'adrenalin', 'zeit' => 'HH:MM'], …]]
   Nach einem fehlgeschlagenen Absenden gilt die Eingabe, nicht der Bestand —
   sonst verschwaende die Arbeit an der Fehlermeldung. */
$reaPrefill = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ((array)($_POST['rea'] ?? []) as $sitz) {
        if (!is_array($sitz)) { continue; }
        $ev = [];
        foreach ((array)($sitz['ev'] ?? []) as $e2) {
            if (!is_array($e2)) { continue; }
            $ev[] = ['typ'  => (string)($e2['typ'] ?? ''),
                     'zeit' => (string)($e2['zeit'] ?? '')];
        }
        $reaPrefill[] = ['start' => (string)($sitz['start'] ?? ''), 'ev' => $ev];
    }
} elseif ($editing) {
    $rs = db()->prepare('SELECT id, started_at FROM resus_sessions
                         WHERE mission_id = ? ORDER BY started_at');
    $rs->execute([$id]);
    $evQ = db()->prepare('SELECT type, occurred_at FROM resus_events
                          WHERE session_id = ? ORDER BY occurred_at');
    foreach ($rs->fetchAll() as $sitz) {
        $evQ->execute([(int)$sitz['id']]);
        $ev = [];
        foreach ($evQ->fetchAll() as $e2) {
            $ev[] = ['typ' => (string)$e2['type'], 'zeit' => fmt_local($e2['occurred_at'])];
        }
        $reaPrefill[] = ['start' => fmt_local($sitz['started_at']), 'ev' => $ev];
    }
}

/* Auswahl der Ereignisarten: RESUS_LABELS ohne 'beginn'. Der Beginn ist kein
   Ereignis, sondern die Sitzung selbst (JSON-Vertrag 3.3) — stuende er in der
   Liste, liesse sich eine Reanimation mit zwei Anfaengen eintragen. */
$REA_ARTEN = RESUS_LABELS;
unset($REA_ARTEN['beginn']);
/**
 * Anzeigewert eines Katalogfeldes.
 *
 * ZWEI QUELLEN, seit die Besatzung normalisiert ist (E7): Felder mit
 * 'store' => 'crew' stehen in `mission_crew` und nicht in der Zeile aus
 * `missions`. Ohne diese Unterscheidung waere jedes Besatzungsfeld beim
 * Bearbeiten leer — und beim Speichern waere der Name dann weg.
 */
function fieldValue(string $col) {
    global $mission, $missionCrew, $CREW_FELDER;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return isset($_POST['f_' . $col]) ? (string)$_POST['f_' . $col] : '';
    }
    if (isset($CREW_FELDER[$col])) {
        return (string)($missionCrew[$CREW_FELDER[$col]] ?? '');
    }
    return $mission !== null ? (string)($mission[$col] ?? '') : '';
}

/**
 * Anzeigewert einer KOORDINATENSPALTE eines Ortsfeldes.
 *
 * Eigene Funktion, weil Formularname und Spaltenname hier auseinandergehen: Das
 * Feld heisst `f_transport_dest_lat`, die Spalte `dest_lat` (Katalogschluessel
 * 'lat_col'). fieldValue() koennte nur eines von beiden.
 */
function ortWert(string $col, string $achse, string $spalte): string {
    global $mission;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return (string)($_POST['f_' . $col . '_' . $achse] ?? '');
    }
    return ($mission !== null && ($mission[$spalte] ?? null) !== null)
        ? (string)$mission[$spalte] : '';
}
ui_seite_start(['titel' => $editing ? 'Einsatz bearbeiten' : 'Einsatz nachtragen']);
ui_topbar('uebersicht');
?>

<div class="layout">
  <?php ui_days_sidebar($dayId); ?>

<main class="page">
  <h1><?= $editing ? 'Einsatz bearbeiten' : 'Einsatz nachtragen' ?></h1>
  <?php if ($editing && !(int)$mission['manual']): ?>
    <p class="alert alert-info">Dieser Einsatz stammt von der Uhr. Nach dem Speichern gilt er als
       manuell bearbeitet — spätere Uhr-Uploads überschreiben ihn dann nicht mehr
       (GPS-Track wird weiterhin ergänzt).</p>
  <?php endif; ?>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

  <form method="post" id="missionform" class="formcol" data-dirty-track data-submit-on-ctrl-enter>
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

    <?php /* ---- Feldrenderer und Stammdatenlisten ---------------------------
             Der Block STEHT HIER, weil die Gruppen unten ihn brauchen — die
             erste („Einsatz") noch vor dem verschlüsselten Teil. Bis Web 6.3.0
             stand er weiter unten und wurde in einem Rutsch über den ganzen
             Katalog aufgerufen; jetzt ruft ihn jede Gruppe für ihre eigenen
             Felder auf. Ausgegeben wird hier nichts, es sind nur
             Definitionen. */ ?>
    <?php
      /* Optionslisten aus Stammdaten aufloesen (options_src).
       *
       * STANDORTBEZOGEN (E15): Gezeigt werden die Eintraege des Standorts, der
       * am Diensttag hinterlegt ist — persoenliche UND zentrale. Eine
       * standortuebergreifende Ebene gibt es nicht; ohne Standort am Diensttag
       * bleibt die Liste leer, und Freitext bleibt uneingeschraenkt moeglich. */
      $optSrc = function (array $f) use ($userId, $dayBaseId): array {
          $src = (string)($f['options_src'] ?? '');
          if ($src === 'bw_units') {
              if ($dayBaseId === null) { return []; }
              $q = db()->prepare('SELECT DISTINCT name FROM bw_units
                                  WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)
                                  ORDER BY name');
              $q->execute([$dayBaseId, $userId]);
              return $q->fetchAll(PDO::FETCH_COLUMN);
          }
          // 'crew:<rolle>' — Besatzungs-Vorbelegungen der Rolle. Rollenkennungen
          // stammen aus CREW_ROLES (db.php), nicht aus einer zweiten Liste hier.
          if (str_starts_with($src, 'crew:')) {
              $role = substr($src, 5);
              if (!array_key_exists($role, CREW_ROLES) || $dayBaseId === null) { return []; }
              $q = db()->prepare('SELECT DISTINCT name FROM crew_presets
                                  WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)
                                    AND role_code = ? ORDER BY name');
              $q->execute([$dayBaseId, $userId, $role]);
              return $q->fetchAll(PDO::FETCH_COLUMN);
          }
          return $f['options'] ?? [];
      };
      // Vorschlagslisten fuer Text-Felder mit suggest_src (Konzept Abschnitt 6.4):
      // persoenlich + zentral, dedupliziert, alphabetisch — natives <datalist>,
      // Freitext bleibt uneingeschraenkt moeglich.
      //
      // Seit Web 5.5.0 auch 'crew:<rolle>' (E8). Die Abfrage ist dieselbe wie
      // in $optSrc; der Unterschied liegt nicht in den Daten, sondern darin,
      // was mit ihnen geschieht: Bei 'options_src' IST die Liste die Auswahl,
      // bei 'suggest_src' ist sie nur ein Vorschlag.
      //
      // Ergebnis je Quelle gemerkt: Die fuenf Besatzungsfelder fragen fuenf
      // verschiedene Rollen ab, ein Formular mit mehreren Feldern derselben
      // Quelle wuerde sie sonst mehrfach laden.
      //
      // JEDE ZEILE TRAEGT NAME UND KOORDINATE (Web 6.1.0, E38). Bis dahin kam
      // hier eine reine Namensliste; die Zielklinik hat seit Web 6.0.0
      // optionale Koordinaten, und ohne sie in derselben Antwort muesste das
      // Ortsfeld sie beim Uebernehmen eines Vorschlags nachladen. Rollen liefern
      // keine — dort bleiben beide Werte null, statt zwei Formen derselben
      // Liste zu haben.
      $suggestCache = [];
      $suggestSrc = function (array $f) use ($userId, $dayBaseId, &$suggestCache): array {
          $src = (string)($f['suggest_src'] ?? '');
          if ($src === '') { return []; }
          if (array_key_exists($src, $suggestCache)) { return $suggestCache[$src]; }

          $liste = [];
          if ($src === 'transport_dests' && $dayBaseId !== null) {
              $q = db()->prepare('SELECT name, MAX(lat) AS lat, MAX(lon) AS lon
                                    FROM transport_dests
                                   WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)
                                   GROUP BY name ORDER BY name');
              $q->execute([$dayBaseId, $userId]);
              foreach ($q->fetchAll() as $z) {
                  $liste[] = ['name' => (string)$z['name'],
                              'lat'  => $z['lat'] !== null ? (float)$z['lat'] : null,
                              'lon'  => $z['lon'] !== null ? (float)$z['lon'] : null];
              }
          } elseif (str_starts_with($src, 'crew:') && $dayBaseId !== null) {
              $role = substr($src, 5);
              if (array_key_exists($role, CREW_ROLES)) {
                  $q = db()->prepare('SELECT DISTINCT name FROM crew_presets
                                      WHERE base_id = ? AND (user_id = ? OR user_id IS NULL)
                                        AND role_code = ? ORDER BY name');
                  $q->execute([$dayBaseId, $userId, $role]);
                  foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $n) {
                      $liste[] = ['name' => (string)$n, 'lat' => null, 'lon' => null];
                  }
              }
          }
          return $suggestCache[$src] = $liste;
      };
      /* ROLLEN DES DIENSTTAGS — der eingefrorene Rollensatz aus `day_crew` (E8),
       * oben als $dayRoles geladen. Er steuert, welche Besatzungsfelder sichtbar
       * sind ('role_gate').
       *
       * WAS SICH GEAENDERT HAT: Bis Web 5.10.0 wurde hier der HUBSCHRAUBER des
       * Flugtags befragt, und war keiner hinterlegt, wurden ALLE fuenf Rollen
       * gezeigt — sonst waere der Haken funktionslos gewesen. Mit zwei
       * Rettungsmittelarten waere das falsch: Ein neutraler Diensttag zeigte
       * dann Flugretter UND Fahrer. Ein Diensttag ohne Zuordnung hat keine
       * Rollen (E26), also wird auch keine gezeigt — ausser den bereits
       * belegten, die immer sichtbar bleiben. */
      /* ---- Unterfelder mit 'show_if' einfassen (V4, A5) ---------------------
       *
       * Die Regel steht am KIND, nicht am Kindercontainer: Zwei Geschwister
       * duerfen verschiedene Bedingungen tragen, und ein gemeinsamer Container
       * koennte nur eine davon abbilden. Der Rahmen traegt sie als
       * Datenattribute; das Umschalten steht weiter unten in einer Schleife
       * ueber `.showif` — dieselbe Bauart wie `.parentcheck` bei den
       * Checkbox-Kindern.
       *
       * Anfangszustand kommt VOM SERVER, nicht vom Skript: Wer das Formular mit
       * „Ambulant" oeffnet, soll die Zielklinik nicht erst aufblitzen sehen. */
      $showIfAuf = function (string $elternCol, array $cf, $elternWert): void {
          $regel = $cf['show_if'] ?? null;
          if (!is_array($regel)) { return; }
          $aus = array_map('strval', (array)($regel['not_in'] ?? []));
          $an  = mf_show_if($cf, $elternWert, $elternCol);
          echo '<div class="showif" data-if-field="' . e($elternCol) . '"'
             . ' data-if-not="' . e(implode('|', $aus)) . '"'
             . ($an ? '' : ' hidden') . '>';
      };
      $showIfZu = function (array $cf): void {
          if (is_array($cf['show_if'] ?? null)) { echo '</div>'; }
      };

      /* Ortsfelder dieses Formulars, fuer die Belebung im Browser gesammelt:
       * Praefix, Vorschlaege und Beschriftung. So steht die Liste an EINER
       * Stelle statt als zweite, von Hand gepflegte Aufzaehlung im Skript. */
      $LOC_FELDER = [];

      $renderField = function (string $col, array $f, int $depth = 0) use (&$renderField, $optSrc, $suggestSrc, $dayRoles, $dayKind, $dayCaps, $showIfAuf, $showIfZu, &$LOC_FELDER): void {
          $type = $f['type'] ?? 'text';
          $val = fieldValue($col);
          /* FILTER: verstecken, aber immer rendern (siehe mission_fields.php).
           * Ein belegtes Feld bleibt sichtbar, sonst kaeme man an einen Wert
           * nicht mehr heran, wenn der Diensttag spaeter das Rettungsmittel
           * wechselt, die Art die Rolle nicht vorsieht oder die Faehigkeit
           * abgewaehlt wurde (A13e).
           *
           * WAS „BELEGT" HEISST, haengt am Typ: Ein Textfeld ist belegt, wenn
           * etwas drinsteht; ein Haken erst, wenn er gesetzt ist. Ohne diese
           * Unterscheidung waere JEDE Checkbox eines bearbeiteten Einsatzes
           * belegt — ihr Wert ist dann „0" und nicht die leere Zeichenkette —
           * und kein 'cap_gate' haette je gegriffen. */
          $belegt = $type === 'checkbox' ? ($val === '1' || $val === 1) : ($val !== '');
          $hide = !$belegt && !mf_gates_erfuellt($f, $dayRoles, $dayKind, $dayCaps);
          $hideAttr = $hide ? ' hidden' : '';
          if ($type === 'resources') { ?>
            <?php /* CHIPS IM FELD (Web 7.0.0). Die bereits gewählten
                     Rettungsmittel standen als eigene Zeile ÜBER dem
                     Eingabefeld — man tippte unten und sah oben, was schon da
                     war. Jetzt sitzen sie im Feld selbst und die Eingabe läuft
                     rechts daneben weiter, wie bei den Empfängern eines
                     Mailprogramms. Der Rahmen liegt deshalb um `.rmfeld` und
                     nicht mehr um das <input>; das Eingabefeld selbst hat
                     keinen eigenen mehr (style.css). */ ?>
            <label class="fld">
              <span><?= e($f['label']) ?></span>
              <div class="rmbox">
                <div class="rmfeld" id="rmfeld">
                  <div class="rmchips" id="rmchips"></div>
                  <input type="text" id="rminput" class="rmeingabe" autocomplete="off"
                         placeholder="Tippen zum Suchen, Enter zum Übernehmen">
                </div>
                <div class="rmlist" id="rmlist" hidden></div>
              </div>
            </label>
          <?php return; }
          if ($type === 'checkbox') {
              $on = ($val === '1' || $val === 1); ?>
            <?php /* VORBELEGUNG (Web 7.0.0). Regel und Zielwert wandern als
                     Datenattribute mit; ausgewertet werden sie im Skript unten.

                     AUCH BEIM BEARBEITEN (berichtigt in Web 7.0.1). Zuerst
                     stand hier `!$editing` — aus der Sorge, eine Vorbelegung
                     koenne einen gespeicherten Wert still ueberschreiben. Die
                     Sorge war unbegruendet, und die Bedingung hat das Feld
                     genau dort abgeschaltet, wo man es am ehesten ausprobiert:
                     beim Oeffnen eines vorhandenen Einsatzes.
                     Der Haken setzt sich NUR auf ein `change`-Ereignis der
                     Transportart hin — also nur, wenn jemand sie gerade
                     umstellt. Beim blossen Laden der Seite passiert nichts, ein
                     gespeicherter Wert bleibt also unangetastet. Und wer die
                     Transportart bewusst aendert, trifft ohnehin eine
                     Entscheidung; ein Vorschlag dazu ist Hilfe, keine
                     Datenaenderung hinter dem Ruecken. */
                  $vorbelegt = !empty($f['vorbelegt_bei'])
                      ? (array)$f['vorbelegt_bei'] : []; ?>
            <div class="fld-check<?= $depth ? ' fld-sub' : '' ?>"<?= $hideAttr ?>>
              <label class="checklabel">
                <input type="checkbox" name="f_<?= e($col) ?>" class="parentcheck"
                       data-target="ch_<?= e($col) ?>" <?= $on ? 'checked' : '' ?>
                       <?php foreach ($vorbelegt as $vFeld => $vWert): ?>
                         data-vor-feld="<?= e((string)$vFeld) ?>"
                         data-vor-wert="<?= e((string)$vWert) ?>"
                       <?php endforeach; ?>>
                <?= e($f['label']) ?></label>
              <?php if (!empty($f['children'])): ?>
                <div class="childfields" id="ch_<?= e($col) ?>" <?= $on ? '' : 'hidden' ?>>
                  <?php foreach ($f['children'] as $cc => $cf) { $renderField($cc, $cf, $depth + 1); } ?>
                </div>
              <?php endif; ?>
            </div>
          <?php return; }
          if ($type === 'select') { $opts = mf_optionen($optSrc($f));
              // Stammdaten sind aenderbar: Ein gespeicherter Wert, der nicht
              // mehr in der Liste steht (Person ausgeschieden, Bereitschaft
              // umbenannt), wuerde sonst unmarkiert bleiben und beim naechsten
              // Speichern still verloren gehen. Deshalb voranstellen. Gilt nur
              // fuer options_src-Listen — feste 'options' bleiben streng.
              if (isset($f['options_src']) && $val !== '' && !array_key_exists($val, $opts)) {
                  $opts = [$val => $val] + $opts;
              } ?>
            <label class="<?= $depth ? 'fld-sub' : '' ?>"<?= $hideAttr ?>><?= e($f['label']) ?>
              <select name="f_<?= e($col) ?>">
                <option value="">–</option>
                <?php /* Wert und Beschriftung koennen auseinandergehen — bei der
                         Transportart tun sie es (mf_optionen). */ ?>
                <?php foreach ($opts as $wert => $text): ?>
                  <option value="<?= e((string)$wert) ?>" <?= $val === (string)$wert ? 'selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <?php if (!empty($f['children'])): ?>
              <div class="childfields">
                <?php foreach ($f['children'] as $cc => $cf) {
                        $showIfAuf($col, $cf, $val);
                        $renderField($cc, $cf, $depth + 1);
                        $showIfZu($cf);
                      } ?>
              </div>
            <?php endif; ?>
          <?php return; }
          /* ---- ORTSFELD (E37) ------------------------------------------------
           * Bezeichnung, versteckte Koordinaten, Chip und Zustandszeile kommen
           * aus ui_ortsfeld(); belebt wird das Ganze von assets/ortsfeld.js. Die
           * Vorschlagsliste traegt hier KOORDINATEN mit — wer einen Eintrag
           * uebernimmt, bekommt sie vorbelegt und kann sie ueberschreiben
           * (A13l). */
          if ($type === 'loc') {
              $sugg = $suggestSrc($f);
              $ort  = mf_ort_spalten($f);
              $praefix = 'f_' . $col . '_';
              $LOC_FELDER[] = [
                  'praefix'     => $praefix,
                  'vorschlaege' => $sugg,
                  'label'       => (string)($f['label'] ?? $col),
              ];
              ui_ortsfeld([
                  'praefix'     => $praefix,
                  'klasse'      => $depth ? 'fld-sub' : '',
                  'label'       => (string)($f['label'] ?? $col),
                  'hinweis'     => 'Freitext; Koordinaten sind freiwillig',
                  'name'        => 'f_' . $col,
                  'max'         => (int)($f['max'] ?? 190),
                  'platzhalter' => (string)($f['placeholder'] ?? ''),
                  'wert'        => $val,
                  'such'        => true,
                  /* Beschriftung des Suchfeldes aus dem Katalog ('such_label',
                   * Web 7.0.0). Beim Transportziel heisst es „Lokalisation
                   * Transportziel" — „Koordinaten" beschrieb, was hinten
                   * herauskommt, nicht was vorne einzugeben ist. */
                  'such_hinweis'     => (string)($f['such_label'] ?? 'Koordinaten (optional)'),
                  'such_platzhalter' => 'Adresse suchen — auch Koordinaten oder Plus Code',
                  'lat_name'    => 'f_' . $col . '_lat',
                  'lon_name'    => 'f_' . $col . '_lon',
                  'versteckt'   => $hide,
                  'lat'         => isset($ort['lat']) ? ortWert($col, 'lat', $ort['lat']) : '',
                  'lon'         => isset($ort['lon']) ? ortWert($col, 'lon', $ort['lon']) : '',
                  'datalist'    => array_map(static fn(array $s): string => $s['name'], $sugg),
              ]);
              if (!empty($f['children'])) { ?>
                <div class="childfields">
                  <?php foreach ($f['children'] as $cc => $cf) {
                          $showIfAuf($col, $cf, $val);
                          $renderField($cc, $cf, $depth + 1);
                          $showIfZu($cf);
                        } ?>
                </div>
              <?php }
              return;
          }
          if ($type === 'textarea') { ?>
            <label class="<?= $depth ? 'fld-sub' : '' ?>"<?= $hideAttr ?>><?= e($f['label']) ?>
              <textarea name="f_<?= e($col) ?>" rows="3" maxlength="<?= (int)($f['max'] ?? 190) ?>"
                placeholder="<?= e($f['placeholder'] ?? '') ?>"><?= e($val) ?></textarea>
            </label>
          <?php return; } ?>
            <label class="<?= $depth ? 'fld-sub' : '' ?>"<?= $hideAttr ?>><?= e($f['label']) ?>
              <input type="<?= $type === 'number' ? 'number' : 'text' ?>"
                name="f_<?= e($col) ?>" value="<?= e($val) ?>"
                <?= isset($f['max']) ? 'maxlength="' . (int)$f['max'] . '"' : '' ?>
                <?= isset($f['suggest_src']) ? 'list="dl_' . e($col) . '"' : '' ?>
                placeholder="<?= e($f['placeholder'] ?? '') ?>" step="any">
              <?php if (isset($f['suggest_src'])): $sugg = $suggestSrc($f); ?>
                <datalist id="dl_<?= e($col) ?>">
                  <?php foreach ($sugg as $s): ?><option value="<?= e($s['name']) ?>"><?php endforeach; ?>
                </datalist>
              <?php endif; ?>
            </label>
            <?php if (!empty($f['children'])): ?>
              <div class="childfields">
                <?php foreach ($f['children'] as $cc => $cf) {
                        $showIfAuf($col, $cf, $val);
                        $renderField($cc, $cf, $depth + 1);
                        $showIfZu($cf);
                      } ?>
              </div>
            <?php endif; ?>
      <?php };

      /* ---- Gruppen (Web 7.0.0) ------------------------------------------
       *
       * Der Katalog sagt, WOHIN ein Feld gehoert ('gruppe'); diese beiden
       * Helfer holen die Felder einer Gruppe und geben sie aus. Die
       * Reihenfolge innerhalb der Gruppe ist die des Katalogs.
       *
       * 'nebeneinander' fasst unmittelbar aufeinanderfolgende Felder mit
       * diesem Schluessel in EINE Zeile (`.fld-reihe`). Es sind bewusst nur
       * unmittelbare Nachbarn: Sonst haenge die Anordnung davon ab, was
       * dazwischen steht — und das ist beim Lesen des Katalogs nicht zu sehen.
       */
      $gruppeFelder = static function (string $name) use ($FIELDS): array {
          $raus = [];
          foreach ($FIELDS as $col => $f) {
              if ((string)($f['gruppe'] ?? '') === $name) { $raus[$col] = $f; }
          }
          return $raus;
      };
      /* Hat die Gruppe ueberhaupt etwas zu zeigen?
       *
       * Gefragt wird nach SICHTBAREN Feldern, nicht nach vorhandenen: Die
       * Gruppe „Bergrettung" besteht aus zwei Feldern, die beide an einer
       * Faehigkeit des Diensttags haengen. An einem NEF-Dienst waere sie ein
       * Rahmen mit einer Ueberschrift und nichts darin.
       *
       * Ein BELEGTES Feld zaehlt immer als sichtbar — dieselbe Regel wie im
       * Renderer (A13e): Sonst kaeme man an einen dokumentierten Windeneinsatz
       * nicht mehr heran, nachdem der Windenhaken am Hubschrauber gefallen ist. */
      $gruppeSichtbar = function (string $name) use ($gruppeFelder, $dayRoles, $dayKind, $dayCaps): bool {
          foreach ($gruppeFelder($name) as $col => $f) {
              $val = fieldValue($col);
              $belegt = ($f['type'] ?? 'text') === 'checkbox'
                  ? ($val === '1' || $val === 1) : ($val !== '');
              if ($belegt || mf_gates_erfuellt($f, $dayRoles, $dayKind, $dayCaps)) { return true; }
          }
          return false;
      };
      $gruppeRendern = function (string $name) use ($gruppeFelder, &$renderField): void {
          $felder = $gruppeFelder($name);
          $offen = false;                 // laeuft gerade eine `.fld-reihe`?
          foreach ($felder as $col => $f) {
              $reihe = !empty($f['nebeneinander']);
              if ($reihe && !$offen) { echo '<div class="fld-reihe">'; $offen = true; }
              if (!$reihe && $offen)  { echo '</div>'; $offen = false; }
              $renderField($col, $f);
          }
          if ($offen) { echo '</div>'; }
      };

      /* Gewaehlte Regel des Abfahrtorts. Sie steht hier und nicht im Markup,
       * weil das Markup sie nur noch OHNE GPS-Aufzeichnung ausgibt — der Wert
       * wird aber auch dann gebraucht, wenn kein Feld erscheint (Vorbelegung
       * des versteckten Zustands). */
      $startWert = $_SERVER['REQUEST_METHOD'] === 'POST'
          ? (string)($_POST['start_src'] ?? '')
          : (string)($mission['start_src'] ?? '');
    ?>

    <?php /* Der Diensttag steht FEST und wandert als Kennung mit. Er ist keine
             Eingabe: Welchem Dienst ein Einsatz gehört, ändert man über
             „Aktionen → Verschieben" (einsatz_verschieben.php) — dort ist die
             Nebenwirkung benannt, an einem frei beschreibbaren Feld wäre sie es
             nicht (E4).

             DAS FELD „EINSATZDATUM" IST ENTFALLEN (Web 7.0.0). Es stand direkt
             unter dieser Zeile und zeigte in aller Regel dasselbe Datum noch
             einmal. Der Fall, für den es gedacht war — der Einsatz nach
             Mitternacht —, wird jetzt aus dem Dienstbeginn erkannt (siehe
             „Tageswechsel" oben). Weicht das Datum des Einsatzes vom Datum des
             Dienstes ab, steht es hier ausdrücklich daneben; sonst wäre nicht
             zu sehen, auf welchen Tag sich die Uhrzeiten beziehen. */ ?>
    <input type="hidden" name="day_id" value="<?= (int)$dayId ?>">
    <p class="muted">Diensttag:
      <strong><?= e(dt_lesbar($tag, true)) ?></strong><?php
        if ($tag['vehicle_name'] !== null && $tag['vehicle_name'] !== '') {
            echo ' · ' . e((string)$tag['vehicle_name']);
        }
        if ($tag['base_name'] !== null && $tag['base_name'] !== '') {
            echo ' · ' . e((string)$tag['base_name']);
        }
        if ($day !== (string)$tag['day']) {
            echo ' · Einsatzdatum: <strong>'
               . e(date('d.m.Y', strtotime($day))) . '</strong>';
        }
        if ($tag['kind'] === null) {
            echo ' — <em>ohne Zuordnung: keine Art, keine Besatzungsrollen, '
               . 'keine artabhängigen Felder</em>';
        } ?></p>

    <input type="hidden" name="pat_blob" id="pat_blob">
    <div id="patlocked" class="alert" hidden>Entschlüsselung nicht möglich —
      die geschützten Angaben sind in dieser Sitzung gesperrt. Vorhandene
      verschlüsselte Angaben bleiben beim Speichern unverändert.
      <button type="button" class="btn-plain unlockbtn" id="unlockbtn">Entsperren</button></div>

    <?php /* ---- GRUPPE 1: PatientInnendaten --------------------------------
             Alles, was die Person betrifft, und sonst nichts. Vollständig
             Ende-zu-Ende-verschlüsselt — deshalb tragen die Felder hier keine
             `name`-Attribute: Sie wandern beim Absenden in den `pat_blob`
             (Skript unten), nicht als Formularwerte zum Server. */ ?>
    <fieldset class="fgruppe">
      <legend>PatientInnendaten
        <span class="fgruppe-hinweis">Ende-zu-Ende-verschlüsselt</span></legend>
      <div id="patfields">
        <label>Einsatznummer
          <input type="text" id="pat_mission_no" maxlength="64" autocomplete="off"
                 placeholder="z. B. Leitstellen-Nr."></label>
        <div class="patname">
          <label>Nachname <input type="text" id="pat_last" maxlength="120" autocomplete="off"></label>
          <label>Vorname <input type="text" id="pat_first" maxlength="120" autocomplete="off"></label>
        </div>
        <div class="fld-reihe">
          <label>Geburtsdatum
            <input type="date" id="pat_dob" max="<?= e(date('Y-m-d')) ?>"></label>
          <label>Alter
            <input type="number" id="pat_age" min="0" max="120" step="1">
            <span class="muted small" id="agehint"></span></label>
        </div>
        <label>Diagnose <input type="text" id="pat_dx" maxlength="190"></label>
      </div>
    </fieldset>

    <?php /* ---- GRUPPE 2: Einsatz ------------------------------------------
             Wo, welcher Art, und woher gestartet wurde. Die beiden Haken oben
             stehen nebeneinander ('nebeneinander' im Katalog): Sie sind zwei
             Wörter lang und kosten untereinander zwei Zeilen für nichts.

             Der Einsatzort und der manuelle Abfahrtort liegen im
             verschlüsselten Block — deshalb der eigene Rahmen `#patort`, den
             das Skript zusammen mit `#patfields` sperrt, solange der Schlüssel
             zu ist. Die Auswahl des Abfahrtorts daneben ist Klartext und bleibt
             bedienbar (sie speichert nur eine REGEL, siehe unten). */ ?>
    <fieldset class="fgruppe">
      <legend>Einsatz</legend>
      <?php $gruppeRendern('einsatz'); ?>
      <div id="patort">
        <?php /* EINSATZORT — erste Verwendung der Ortsfeld-Komponente (V8).
                 Hier stand bis Web 6.0.0 das Markup ausgeschrieben, und rund 25
                 getElementById-Aufrufe weiter unten hingen an seinen Kennungen.
                 Die Kennungen sind unverändert (locaddr, loclat, loclon,
                 locsuggest, locstate, locchips) — nur erzeugt sie jetzt
                 ui_ortsfeld() aus dem Präfix „loc", und die Bedienung steht in
                 assets/ortsfeld.js.

                 OHNE getrennte Suche: Beim Einsatzort IST die Adresse die
                 Bezeichnung. Das Verhalten bleibt damit exakt das bisherige. */
              ui_ortsfeld([
                  'praefix'     => 'loc',
                  'label'       => 'Einsatzort',
                  'hinweis'     => 'Adresse, Koordinaten oder Plus Code',
                  'max'         => 255,
                  'platzhalter' => 'tippen für Vorschläge — auch Koordinaten oder Plus Code',
              ]); ?>
        <label>Beschreibung Einsatzort
          <span class="muted small">Zufahrt, Besonderheiten, Lage vor Ort</span>
          <input type="text" id="pat_site_desc" maxlength="190" autocomplete="off">
        </label>

        <?php /* ---- ABFAHRTORT (E34, Konzept 3.5.1) --------------------------
                 Fällt die Uhr aus, fehlt der Track — die Karte bleibt leer,
                 obwohl der Einsatzort bekannt ist. Was fehlt, ist der Gegenpunkt.

                 Gespeichert wird die REGEL, nicht die Koordinate: In der Regel
                 genügt damit eine einzige Auswahl statt einer Adresseingabe je
                 Einsatz. Nur „Manueller Ort" braucht ein eigenes Feld — und das
                 steht hier im verschlüsselten Block, weil ein frei gewählter
                 Abfahrtort so schutzwürdig ist wie der Einsatzort (4.6.1).

                 NUR OHNE GPS-AUFZEICHNUNG (Web 7.0.0). Liegt ein Track vor,
                 zeichnet die Karte den tatsächlich zurückgelegten Weg, und
                 diese Auswahl bliebe folgenlos — eine Frage, die nichts
                 bewirkt, gehört nicht ins Formular. Die gespeicherte Regel
                 bleibt in der Datenbank unangetastet. */ ?>
        <?php if (!$hatTrack): ?>
          <label>Abfahrtort
            <span class="muted small">erzeugt die gestrichelte Luftlinie auf der
              Karte — dieser Einsatz hat keine GPS-Aufzeichnung</span>
            <select name="start_src" id="start_src">
              <option value="">– nicht angegeben (keine Linie)</option>
              <?php
                /* Die Beschriftungen sind NICHT die gespeicherten Werte (siehe
                   mission_fields.php) — deshalb ist das hier auch kein Katalogfeld.
                   Bezugspunkt der beiden Vorgänger-Auswahlen ist der zeitlich
                   unmittelbar vorangehende Einsatz DESSELBEN Diensttags. */
                $startArten = [
                    /* „Standort" statt „Standort des Diensttags" (Web 7.0.0):
                       Ein anderer Standort steht gar nicht zur Wahl, der Zusatz
                       war also kein Unterschied, sondern nur Länge — und für
                       die Bodenrettung klang „Diensttag" nach Flugbetrieb. */
                    'base'      => 'Standort',
                    'prev_site' => 'Letzter Einsatzort (vorheriger Einsatz)',
                    'prev_dest' => 'Letzte Zielklinik (vorheriger Einsatz)',
                    'manual'    => 'Manueller Ort',
                ];
                foreach ($startArten as $wert => $text): ?>
                  <option value="<?= e($wert) ?>" <?= $startWert === $wert ? 'selected' : '' ?>><?= e($text) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <div id="startfields" <?= $startWert === 'manual' ? '' : 'hidden' ?>>
            <?php ui_ortsfeld([
                    'praefix'     => 'start',
                    'klasse'      => 'fld-sub',
                    'label'       => 'Manueller Abfahrtort',
                    'hinweis'     => 'Adresse, Koordinaten oder Plus Code',
                    'max'         => 255,
                    'platzhalter' => 'tippen für Vorschläge — auch Koordinaten oder Plus Code',
                  ]); ?>
          </div>
        <?php endif; ?>
      </div>
    </fieldset>

    <?php /* ---- GRUPPE 3: Transport -----------------------------------------
             Transportart, NA-Begleitung, Transportziel samt Schockraum. Die
             Abhängigkeiten stehen im Katalog ('show_if'): „Ambulant" blendet
             NA-Begleitung und Transportziel aus, und der Server LEERT sie dann
             auch (A5). */ ?>
    <fieldset class="fgruppe">
      <legend>Transport</legend>
      <?php $gruppeRendern('transport'); ?>
    </fieldset>

    <?php /* ---- GRUPPE 4: Bergrettung ---------------------------------------
             Bergwacht und Winde hängen beide an einer FÄHIGKEIT des Diensttags
             ('cap_gate'). Bringt der Dienst keine davon mit und ist auch nichts
             belegt, hat die Gruppe keinen Inhalt — dann fällt sie ganz weg
             statt als leerer Rahmen dazustehen. */ ?>
    <?php if ($gruppeSichtbar('bergrettung')): ?>
      <fieldset class="fgruppe">
        <legend>Bergrettung</legend>
        <?php $gruppeRendern('bergrettung'); ?>
      </fieldset>
    <?php endif; ?>

    <?php /* ---- GRUPPE 5: Weitere Rettungsmittel ---------------------------- */ ?>
    <fieldset class="fgruppe">
      <legend>Weitere Rettungsmittel</legend>
      <?php $gruppeRendern('mittel'); ?>
    </fieldset>

    <?php /* ---- GRUPPE 6: Abweichende Besatzung ----------------------------- */ ?>
    <fieldset class="fgruppe">
      <legend>Abweichende Besatzung</legend>
      <?php $gruppeRendern('besatzung'); ?>
    </fieldset>

    <?php /* ---- GRUPPE 7: Notizen -------------------------------------------- */ ?>
    <fieldset class="fgruppe">
      <legend>Notizen</legend>
      <?php $gruppeRendern('notizen'); ?>
    </fieldset>

    <?php /* Felder ohne Gruppe — es gibt derzeit keine. Der Block steht da,
             damit ein neu angelegtes Katalogfeld ohne 'gruppe' sichtbar bleibt
             statt aus dem Formular zu fallen. */ ?>
    <?php if ($gruppeSichtbar('')): ?>
      <fieldset class="fgruppe">
        <legend>Weitere Angaben</legend>
        <?php $gruppeRendern(''); ?>
      </fieldset>
    <?php endif; ?>

    <?php /* ---- GRUPPE 8: Einsatzphasen --------------------------------------
             NACH UNTEN GEWANDERT (Web 7.0.0). Die Phasen standen ganz oben, vor
             den PatientInnendaten — an der Stelle, an der man sie beim
             Nachtragen zuerst braucht. Beim BEARBEITEN, dem häufigeren Fall,
             stehen sie meist schon vollständig da und schoben alles andere nach
             unten. Jetzt stehen sie dort, wo sie hingehören: bei den
             Zeitangaben, unmittelbar über der Reanimation. */ ?>
    <fieldset class="fgruppe">
      <legend>Einsatzphasen</legend>
      <p class="muted">In chronologischer Reihenfolge eintragen. Zeiten nach Mitternacht
         werden automatisch dem Folgetag zugerechnet.</p>
      <div id="phaserows"></div>
      <p><a href="#" id="addrow" class="add-link">+ Phase hinzufügen</a></p>
    </fieldset>


    <?php /* ---- GRUPPE 9: Reanimation ---------------------------------- */ ?>
    <fieldset class="fgruppe">
      <legend>Reanimation</legend>
      <p class="muted">Nur ausfüllen, wenn reanimiert wurde. Mehrere Reanimationen
         je Einsatz sind möglich. Zeiten nach Mitternacht werden automatisch dem
         Folgetag zugerechnet; eine Zeile ohne Uhrzeit wird nicht gespeichert.</p>
      <div id="rearows"></div>
      <p><a href="#" id="addrea" class="add-link">+ Reanimation hinzufügen</a></p>
    </fieldset>

    <button type="submit" class="btn-primary"><?= $editing ? 'Änderungen speichern' : 'Einsatz anlegen' ?></button>
    <?php /* Abbrechen in BEIDEN Zustaenden (A4.1). Beim Nachtragen fehlte der
             Weg bisher ganz — wer das Formular offen hatte, kam nur ueber die
             Seitenleiste oder den Zurueck-Knopf des Browsers heraus.
             Rücksprungziel ist fest (E7): beim Bearbeiten der Einsatz, beim
             Nachtragen die Tagesansicht. Ein Rücksprung auf die tatsaechlich
             zuletzt besuchte Seite waere fehleranfaellig und der Gewinn gering.
             Die Rückfrage erscheint nur bei tatsaechlichen Eingaben — die
             Bedingung steckt in assets/forms.js. */ ?>
    <p class="login-aux"><a
       href="<?= $editing ? 'einsatz.php?id=' . $id : 'index.php?d=' . (int)$dayId ?>"
       data-cancel-form="missionform"
       data-cancel-confirm="<?= $editing
           ? 'Die Änderungen an diesem Einsatz gehen verloren. Trotzdem abbrechen?'
           : 'Der nachgetragene Einsatz wird nicht gespeichert. Trotzdem abbrechen?' ?>"
       >Abbrechen</a></p>
  </form>
<?php ui_footer(); ?>
</main>
</div>

<script src="<?= asset('assets/crypto.js') ?>"></script>
<script src="<?= asset('assets/keyguard.js') ?>"></script>
<script src="<?= asset('assets/unlock.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script src="<?= asset('assets/openlocationcode.js') ?>"></script>
<script src="<?= asset('assets/locparse.js') ?>"></script>
<script src="<?= asset('assets/ortsfeld.js') ?>"></script>
<script src="<?= asset('assets/zeitfeld.js') ?>"></script>
<script>
const PHASE_LABELS = <?= json_encode(PHASE_LABELS) ?>;
const START_ROWS = <?= json_encode($prefillRows) ?>;
const REA_ARTEN = <?= json_encode($REA_ARTEN, JSON_UNESCAPED_UNICODE) ?>;
const REA_START = <?= json_encode($reaPrefill, JSON_UNESCAPED_UNICODE) ?>;

function addRow(no, time) {
  const div = document.createElement('div');
  div.className = 'phase-row';
  const sel = document.createElement('select');
  sel.name = 'ph_no[]';
  for (let p = 2; p <= 9; p++) {
    const o = document.createElement('option');
    o.value = p; o.textContent = p + ' ' + PHASE_LABELS[p];
    if (p === no) o.selected = true;
    sel.appendChild(o);
  }
  const t = document.createElement('input');
  // Textfeld statt type="time" (E1): Native Zeitfelder zeigen je nach
  // Systemsprache 12 Stunden mit AM/PM. Format und Maske sichert
  // assets/zeitfeld.js — die Klasse genuegt, das Feld wird auch nachtraeglich
  // erfasst. Serverseitig prueft weiterhin local_to_utc().
  t.type = 'text'; t.className = 'zeitfeld';
  t.name = 'ph_time[]'; t.value = time || '';
  const rm = document.createElement('button');
  rm.type = 'button'; rm.className = 'btn-danger'; rm.textContent = '✕';
  rm.addEventListener('click', () => div.remove());
  div.append(sel, t, rm);
  document.getElementById('phaserows').appendChild(div);
  return sel;
}

/* ---- Reanimationen (A4.3) ------------------------------------------------
 * Aufbau wie bei den Phasen: Die Zeilen entstehen im Browser, die Zeitfelder
 * tragen nur die Klasse 'zeitfeld' — assets/zeitfeld.js erfasst sie ueber
 * seinen Beobachter, ohne dass hier etwas davon zu wissen ist.
 *
 * Die Namen der Felder tragen laufende Zaehler (rea[<n>][ev][<m>]…). Sie
 * werden nie wiederverwendet und beim Entfernen einer Zeile auch nicht
 * nachgezogen: Die Serverseite wertet die Reihenfolge aus, in der die Felder
 * ankommen, nicht die Zahlen darin. Umnummerieren waere Arbeit, die niemand
 * sieht — und eine Fehlerquelle, sobald zwei Zeilen gleichzeitig verschwinden.
 */
let reaZaehler = 0;

function reaEreignisZeile(box, evBox, daten) {
  const n = box.dataset.nr;
  const m = Number(box.dataset.evNr || 0);
  box.dataset.evNr = m + 1;

  const row = document.createElement('div');
  row.className = 'rea-row';
  const sel = document.createElement('select');
  sel.name = `rea[${n}][ev][${m}][typ]`;
  Object.keys(REA_ARTEN).forEach(k => {
    const o = document.createElement('option');
    o.value = k; o.textContent = REA_ARTEN[k];
    if (daten && daten.typ === k) { o.selected = true; }
    sel.appendChild(o);
  });
  const t = document.createElement('input');
  t.type = 'text'; t.className = 'zeitfeld';
  t.name = `rea[${n}][ev][${m}][zeit]`;
  t.value = (daten && daten.zeit && daten.zeit !== '–') ? daten.zeit : '';
  const weg = document.createElement('button');
  weg.type = 'button'; weg.className = 'btn-danger'; weg.textContent = '✕';
  weg.title = 'Dieses Ereignis entfernen';
  weg.addEventListener('click', () => row.remove());

  row.append(sel, t, weg);
  evBox.appendChild(row);
  return t;
}

function reaSitzung(daten) {
  const n = reaZaehler++;
  const box = document.createElement('div');
  box.className = 'rea-sitzung';
  box.dataset.nr = n;

  const kopf = document.createElement('div');
  kopf.className = 'rea-row rea-kopf';
  const lab = document.createElement('label');
  lab.textContent = 'Reanimationsbeginn';
  const start = document.createElement('input');
  start.type = 'text'; start.className = 'zeitfeld';
  start.name = `rea[${n}][start]`;
  start.value = (daten && daten.start && daten.start !== '–') ? daten.start : '';
  lab.appendChild(start);
  const weg = document.createElement('button');
  weg.type = 'button'; weg.className = 'btn-danger'; weg.textContent = '✕';
  weg.title = 'Diese Reanimation entfernen';
  weg.addEventListener('click', () => box.remove());
  kopf.append(lab, weg);

  const evBox = document.createElement('div');
  evBox.className = 'rea-ereignisse';

  const add = document.createElement('a');
  add.href = '#'; add.className = 'add-link';
  add.textContent = '+ Ereignis hinzufügen';
  add.addEventListener('click', ev => {
    ev.preventDefault();
    reaEreignisZeile(box, evBox, null).focus();   // direkt per Tastatur bedienbar
  });
  const addP = document.createElement('p');
  addP.appendChild(add);

  box.append(kopf, evBox, addP);
  document.getElementById('rearows').appendChild(box);
  ((daten && daten.ev) || []).forEach(ev => reaEreignisZeile(box, evBox, ev));
  return start;
}

// ---- PatientInnendaten & Einsatzort: lokale Ver-/Entschluesselung ------
const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
const KDF_SALT = <?= json_encode($kdfSalt) ?>;
/* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
   gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
   bekommt einen anderen Schluessel. */
const KDF_ITER      = <?= json_encode($kdfIter) ?>;
const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;
const PAT_PREV = <?= json_encode($mission['pat_blob'] ?? null) ?>;
/* Bezugstag fuer die Altersberechnung: das ECHTE Einsatzdatum, nicht heute und
   nicht das Datum des Diensttags. Bei einem Dienst ueber Mitternacht sind das
   zwei verschiedene Tage, und gefragt ist der, an dem der Einsatz lief. */
const MISSION_DAY = <?= json_encode($day) ?>;
let PAT_CK = null;

/* ---- ORTSFELDER (assets/ortsfeld.js) -------------------------------------
 *
 * Hier standen bis Web 6.0.0 rund 180 Zeilen: Photon-Abfrage,
 * Plus-Code-Erkennung, Chip, Zustandszeile, Platzhalterwechsel — alles fest an
 * die Kennungen `locaddr`, `loclat`, `loclon`, `locstate` gebunden
 * (Vorpruefung V8). Mit dem Abfahrtort und der Zielklinik waeren daraus drei
 * fast gleiche Faelle auf DIESER Seite geworden und drei weitere in der
 * Stammdatenpflege. Sie sind jetzt eine Komponente; hier steht nur noch, WELCHE
 * Verwendungen es gibt und wie sie sich unterscheiden.
 */

// Einsatzort: das Textfeld ist zugleich das Suchfeld — die Adresse IST hier die
// Bezeichnung. Unveraendertes Verhalten seit Web 3.x.
const ortEinsatz = EdOrtsfeld.init({
  praefix: 'loc',
  bezeichnungPlatzhalter: 'Bezeichnung des Einsatzortes'
});

// Manueller Abfahrtort: gleiche Bedienung, eigener Blob-Schluessel.
const ortStart = EdOrtsfeld.init({
  praefix: 'start',
  bezeichnungPlatzhalter: 'Bezeichnung des Abfahrtortes'
});

/* Ortsfelder aus dem Feldkatalog (derzeit die Zielklinik). Sie tragen einen
 * NAMEN und daneben eine Koordinate, deshalb getrennte Suche: „Klinikum
 * Kempten" ist keine Adresse, und eine Adresssuche im selben Feld schriebe den
 * Namen weg. Trifft die Eingabe einen Stammdatensatz, kommen dessen Koordinaten
 * mit und bleiben ueberschreibbar (A13l). */
const LOC_FELDER = <?= json_encode($LOC_FELDER, JSON_UNESCAPED_UNICODE) ?>;
const ORTSFELDER = LOC_FELDER.map(lf => EdOrtsfeld.init({
  praefix: lf.praefix,
  getrennteSuche: true,
  vorschlaege: lf.vorschlaege,
  bezeichnungPlatzhalter: 'Bezeichnung'
})).filter(Boolean);

/* ---- Abfahrtort: Regel waehlen, manuelles Feld ein-/ausblenden (E34) ------
 *
 * Der manuelle Ort bleibt beim Umschalten STEHEN und wird nur verborgen. Er
 * wandert erst beim Speichern in den Blob, und auch das nur bei gewaehlter
 * Regel „Manueller Ort" — wer versehentlich umschaltet, verliert seine Eingabe
 * also nicht, und wer bewusst umschaltet, laesst keine Angabe zurueck, die
 * nirgends mehr gilt. */
/* BEIDE ELEMENTE KOENNEN FEHLEN (Web 7.0.0): Hat der Einsatz eine
 * GPS-Aufzeichnung, gibt das Formular den ganzen Block gar nicht erst aus. Das
 * Skript darf daran nicht scheitern — alles Weitere unten fragt deshalb
 * `startSel` ab, statt seine Existenz vorauszusetzen. */
const startSel = document.getElementById('start_src');
const startBox = document.getElementById('startfields');
if (startSel && startBox) {
  startSel.addEventListener('change', () => {
    startBox.hidden = startSel.value !== 'manual';
  });
}

/* ---- Wertabhaengige Unterfelder (V4, A5) ---------------------------------
 *
 * Gegenstueck zu `show_if` im Feldkatalog. Der Anfangszustand kommt vom Server;
 * hier wird nur nachgezogen, was sich waehrend des Ausfuellens aendert.
 *
 * Anders als bei den Filtern (`role_gate` und Geschwister) wird hier nicht nur
 * versteckt: Der Server LEERT ein ausgeblendetes Unterfeld beim Speichern.
 * Deshalb ist die Aenderung sichtbar, bevor sie wirkt — das Feld verschwindet
 * vor dem Absenden und nicht danach (A5). */
document.querySelectorAll('.showif').forEach(box => {
  const eltern = document.querySelector('[name="f_' + box.dataset.ifField + '"]');
  if (!eltern) { return; }
  const aus = (box.dataset.ifNot || '').split('|').filter(Boolean);
  const pruefe = () => { box.hidden = aus.includes(eltern.value); };
  eltern.addEventListener('change', pruefe);
  eltern.addEventListener('input', pruefe);
  pruefe();
});

/* Geschuetzte Angaben laden. Bei gesperrtem Schluessel bietet EdUnlock den
 * Entsperrdialog an; wird er abgebrochen, bleibt es beim bisherigen Verhalten
 * (Hinweis sichtbar, Felder gesperrt) — und damit auch beim Schutz aus
 * speicherePat(): ohne PAT_CK wird der vorhandene Blob nicht angefasst. */
/* WAS DER RIEGEL SPERRT: alles, was im pat_blob landet. Das sind seit dem
 * Umbau in Gruppen (Web 7.0.0) zwei getrennte Bereiche der Seite — die
 * PatientInnendaten (`#patfields`) und der Ortsteil der Gruppe „Einsatz"
 * (`#patort`: Einsatzort, Beschreibung, manueller Abfahrtort).
 *
 * NICHT gesperrt wird die Auswahl des Abfahrtorts daneben: Sie speichert eine
 * REGEL im Klartext und bleibt bedienbar. Sie ist ein <select> und faellt schon
 * deshalb nicht unter diesen Selektor — die Auswahl ist trotzdem ausdruecklich
 * gemeint und keine Nachlaessigkeit. */
const PAT_INPUTS = '#patfields input, #patort input';

async function patLaden(){
  PAT_CK = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
  if (!PAT_CK) {
    document.getElementById('patlocked').hidden = false;
    document.querySelectorAll(PAT_INPUTS).forEach(i => i.disabled = true);
    return;
  }
  document.getElementById('patlocked').hidden = true;
  document.querySelectorAll(PAT_INPUTS).forEach(i => i.disabled = false);
  if (PAT_PREV) {
    let o = {};
    try { o = JSON.parse(await EdCrypto.decrypt(PAT_CK, PAT_PREV)) || {}; } catch (e) { }
    if (o.mission_no != null) document.getElementById('pat_mission_no').value = o.mission_no;
    if (o.last != null) document.getElementById('pat_last').value = o.last;
    if (o.first != null) document.getElementById('pat_first').value = o.first;
    if (o.dob != null) document.getElementById('pat_dob').value = o.dob;
    if (o.dx != null) document.getElementById('pat_dx').value = o.dx;
    if (o.site_desc != null) document.getElementById('pat_site_desc').value = o.site_desc;
    if (o.age != null) document.getElementById('pat_age').value = o.age;
    zeigeAlter();
    if (o.loc) {
      // addr steht unveraendert im Textfeld — auch dann, wenn dort noch eine
      // Zahlendarstellung aus einem Altdatensatz liegt (E11, kein stilles
      // Umschreiben). Erst beim naechsten Speichern verlangt E4 eine Bezeichnung.
      ortEinsatz.setzen(o.loc);
    }
    // Manueller Abfahrtort, analog zum Einsatzort (Konzept 4.6.1). Er bleibt
    // gespeichert, auch wenn gerade eine andere Regel gewaehlt ist — das
    // Umschalten auf „Manueller Ort" soll ihn nicht verlangen, nur weil
    // zwischendurch der Standort galt.
    if (o.start) { ortStart.setzen(o.start); }
  }
  zeigeAlter();   // sperrt das Altersfeld wieder, wenn ein Geburtsdatum steht
}
patLaden();
document.getElementById('unlockbtn').addEventListener('click', () => patLaden());

// Alter aus dem Geburtsdatum: Feld fuellen und sperren, solange ein
// Geburtsdatum gesetzt ist. Ohne Geburtsdatum (unbekannte Person) bleibt es
// von Hand eintragbar.
function zeigeAlter(){
  const dob = document.getElementById('pat_dob').value.trim();
  const feld = document.getElementById('pat_age');
  const hint = document.getElementById('agehint');
  const berechnet = EdPat.alterAm(dob, MISSION_DAY);
  if (berechnet !== null) {
    feld.value = berechnet;
    feld.readOnly = true;
    hint.textContent = 'aus Geburtsdatum';
  } else {
    feld.readOnly = false;
    hint.textContent = dob !== '' ? 'Geburtsdatum unvollständig' : '';
  }
}
// Zweistellige Jahreszahlen (z. B. "23.04.33"): Der native Date-Picker
// liefert dafuer teils "0033-04-23" statt "1933-04-23". Korrektur per
// gleitender Fensterregel: zunaechst 2000+JJ; laege das Datum damit in der
// Zukunft, stattdessen 1900+JJ. Greift vor der Altersberechnung; die
// bestehende max-Grenze (heute) des Feldes bleibt unangetastet.
function korrigiereZweistelligesJahr(){
  const feld = document.getElementById('pat_dob');
  const m = feld.value.match(/^(\d{1,4})-(\d{2})-(\d{2})$/);
  if (!m || parseInt(m[1], 10) >= 100) return;
  const jj = parseInt(m[1], 10);
  let jahr = 2000 + jj;
  const kandidat = `${String(jahr).padStart(4, '0')}-${m[2]}-${m[3]}`;
  if (new Date(kandidat + 'T00:00:00') > new Date()) { jahr = 1900 + jj; }
  feld.value = `${String(jahr).padStart(4, '0')}-${m[2]}-${m[3]}`;
}
document.getElementById('pat_dob').addEventListener('input', () => { korrigiereZweistelligesJahr(); zeigeAlter(); });
document.getElementById('pat_dob').addEventListener('change', () => { korrigiereZweistelligesJahr(); zeigeAlter(); });
zeigeAlter();

const BEZ_FEHLT = 'Bezeichnung fehlt — bitte zu den Koordinaten einen Namen '
  + 'eintragen (z. B. „Talstation Nebelhorn“).';

document.getElementById('missionform').addEventListener('submit', async ev => {
  const f = ev.target;
  if (f.dataset.patDone === '1') return;

  /* KLARTEXT-ORTSFELDER ZUERST (A13j). Die Zielklinik ist ein gewoehnliches
   * Formularfeld; ob ihre Koordinate eine Bezeichnung hat, darf nicht davon
   * abhaengen, ob der Patientenschluessel gerade offen ist. Erst danach der
   * Riegel unten, hinter dem der verschluesselte Teil liegt. */
  for (const feld of ORTSFELDER) {
    if (!feld.pruefe(BEZ_FEHLT)) { ev.preventDefault(); return; }
  }

  if (!PAT_CK) return;   // gesperrt: Blob bleibt unveraendert
  ev.preventDefault();
  // E4: Koordinaten ohne Bezeichnung ergaeben in den Listen wieder ein
  // Zahlenfragment. Die Pruefung steht VOR dem Verschluesseln, damit erst gar
  // kein Blob entsteht — und hinter dem PAT_CK-Riegel oben: bei gesperrter
  // Verschluesselung sind die Felder leer und gesperrt, dort waere die
  // Forderung nach einer Bezeichnung nicht erfuellbar (V5).
  if (!ortEinsatz.pruefe(BEZ_FEHLT)) { return; }
  if (startSel && startSel.value === 'manual' && !ortStart.pruefe(BEZ_FEHLT)) { return; }
  const o = {};
  const missionNo = document.getElementById('pat_mission_no').value.trim();
  const last  = document.getElementById('pat_last').value.trim();
  const first = document.getElementById('pat_first').value.trim();
  const dob   = document.getElementById('pat_dob').value.trim();
  const dx    = document.getElementById('pat_dx').value.trim();
  const siteDesc = document.getElementById('pat_site_desc').value.trim();
  const age   = document.getElementById('pat_age').value.trim();
  if (missionNo !== '') o.mission_no = missionNo;
  if (last !== '')  o.last  = last;
  if (first !== '') o.first = first;
  if (dob !== '')   o.dob   = dob;
  if (dx !== '')    o.dx    = dx;
  // Eigener Schluessel auf oberster Ebene, NICHT in loc: 'loc' entsteht nur bei
  // gefuellter Adresse, eine Beschreibung ohne Ortsangabe ginge sonst verloren (E5).
  if (siteDesc !== '') o.site_desc = siteDesc;
  // Alter nur speichern, wenn es NICHT aus dem Geburtsdatum folgt — sonst
  // muesste es bei jeder Korrektur des Geburtsdatums nachgezogen werden.
  if (age !== '' && EdPat.alterAm(dob, MISSION_DAY) === null) o.age = parseInt(age, 10);
  const ort = ortEinsatz.werte();
  if (ort.addr !== '') {
    o.loc = { addr: ort.addr };
    if (ort.lat !== null) { o.loc.lat = ort.lat; o.loc.lon = ort.lon; }
  }
  /* MANUELLER ABFAHRTORT — derselbe Aufbau wie `loc`, eigener Schluessel
   * `start` (Konzept 4.6.1). Er wandert NUR bei gewaehlter Regel „Manueller
   * Ort" in den Blob: Sonst bliebe eine Ortsangabe stehen, die nirgends mehr
   * gilt, und der naechste Blick in die Daten fragte sich, wozu. */
  if (startSel && startSel.value === 'manual') {
    const st = ortStart.werte();
    if (st.addr !== '') {
      o.start = { addr: st.addr };
      if (st.lat !== null) { o.start.lat = st.lat; o.start.lon = st.lon; }
    }
  }
  document.getElementById('pat_blob').value =
    Object.keys(o).length === 0 ? '__CLEAR__' : await EdCrypto.encrypt(PAT_CK, JSON.stringify(o));
  f.dataset.patDone = '1';
  f.submit();
});

// Unterfelder ein-/ausblenden, wenn der zugehoerige Haken wechselt
document.querySelectorAll('.parentcheck').forEach(cb => {
  cb.addEventListener('change', () => {
    const t = document.getElementById(cb.dataset.target);
    if (t) t.hidden = !cb.checked;
  });
});

/* ---- Vorbelegte Haken (Web 7.0.0, Katalogschlüssel 'vorbelegt_bei') -------
 *
 * Anwendungsfall: NA-Begleitung bei luftgebundenem Transport. Ein Lufttransport
 * ohne Notarzt an Bord ist die Ausnahme — der Haken war deshalb der am
 * häufigsten vergessene des Formulars.
 *
 * DIE VORBELEGUNG IST EIN VORSCHLAG, KEINE REGEL. Sobald jemand den Haken
 * selbst anfasst, ist sie für dieses Formular erledigt: Wer bewusst „Luft ohne
 * NA" dokumentiert und dabei zusieht, wie der Haken von selbst zurückspringt,
 * traut dem Formular danach nicht mehr. Umgekehrt greift sie beim ERSTEN
 * Umschalten auch dann, wenn vorher eine andere Transportart gewählt war.
 *
 * SIE HÄNGT AM `change`-EREIGNIS DER TRANSPORTART, nicht am Laden der Seite.
 * Deshalb gilt sie beim Bearbeiten genauso wie beim Nachtragen (Web 7.0.1):
 * Ein gespeicherter Wert wird nie beim Öffnen überschrieben — es passiert nur
 * etwas, wenn jemand die Transportart gerade umstellt. */
document.querySelectorAll('input[data-vor-feld]').forEach(cb => {
  const quelle = document.querySelector('[name="f_' + cb.dataset.vorFeld + '"]');
  if (!quelle) { return; }
  let vonHand = false;
  cb.addEventListener('change', () => { vonHand = true; });
  quelle.addEventListener('change', () => {
    if (vonHand) { return; }
    if (quelle.value === cb.dataset.vorWert && !cb.checked) {
      cb.checked = true;
      // Unterfelder eines vorbelegten Hakens müssen mit aufgehen.
      cb.dispatchEvent(new Event('change', { bubbles: true }));
      vonHand = false;   // das eigene Ereignis zählt nicht als Handgriff
    }
  });
});


START_ROWS.forEach(r => addRow(r[0], r[1] === '–' ? '' : r[1]));
document.getElementById('addrow').addEventListener('click', ev => {
  ev.preventDefault();
  // Auswahlfelder AUS DEM PHASENBLOCK, nicht alle der Seite: Seit Web 5.5.0
  // stehen weiter unten die Reanimationsereignisse, ebenfalls mit Auswahl.
  const rows = document.querySelectorAll('#phaserows .phase-row select');
  const last = rows.length ? parseInt(rows[rows.length - 1].value) : 1;
  addRow(Math.min(last + 1, 10), '').focus();   // direkt per Tastatur bedienbar
});

REA_START.forEach(s => reaSitzung(s));
document.getElementById('addrea').addEventListener('click', ev => {
  ev.preventDefault();
  reaSitzung(null).focus();
});

/* ---- Andere Rettungsmittel: Eingabe mit Vorschlaegen ------------------- */
(function(){
  const box   = document.getElementById('rmchips');
  const input = document.getElementById('rminput');
  const liste = document.getElementById('rmlist');
  if (!box || !input) { return; }

  const vorlagen = <?= json_encode($rmVorlagen, JSON_UNESCAPED_UNICODE) ?>;
  let gewaehlt   = <?= json_encode($rmGewaehlt, JSON_UNESCAPED_UNICODE) ?>;

  function zeichneChips(){
    box.innerHTML = '';
    gewaehlt.forEach((name, i) => {
      const chip = document.createElement('span');
      chip.className = 'rmchip';
      chip.appendChild(document.createTextNode(name));
      const x = document.createElement('button');
      x.type = 'button'; x.className = 'rmx'; x.textContent = '\u00d7';
      x.title = name + ' entfernen';
      x.addEventListener('click', () => { gewaehlt.splice(i, 1); zeichneChips(); suche(); });
      chip.appendChild(x);
      // Wert mitschicken: eigene Zeile je Rettungsmittel
      const feld = document.createElement('input');
      feld.type = 'hidden'; feld.name = 'f_other_resources[]'; feld.value = name;
      chip.appendChild(feld);
      box.appendChild(chip);
    });
  }

  function hinzu(name){
    name = name.trim();
    if (!name) { return; }
    if (gewaehlt.some(g => g.toLowerCase() === name.toLowerCase())) { return; }  // keine Dubletten
    gewaehlt.push(name);
    input.value = '';
    zeichneChips();
    suche();
    input.focus();
  }

  function suche(){
    const q = input.value.trim();
    liste.innerHTML = '';
    if (q.length < 2) { liste.hidden = true; return; }      // erst ab zwei Zeichen

    const ql = q.toLowerCase();
    const treffer = vorlagen.filter(v =>
      v.toLowerCase().includes(ql) &&
      !gewaehlt.some(g => g.toLowerCase() === v.toLowerCase()));

    treffer.slice(0, 8).forEach(v => {
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'rmopt'; b.textContent = v;
      b.addEventListener('click', () => hinzu(v));
      liste.appendChild(b);
    });

    // Freie Eingabe immer anbieten, wenn sie nicht exakt schon dabei ist
    const exakt = treffer.some(v => v.toLowerCase() === ql)
               || gewaehlt.some(g => g.toLowerCase() === ql);
    if (!exakt) {
      const b = document.createElement('button');
      b.type = 'button'; b.className = 'rmopt rmneu';
      b.textContent = '\u201e' + q + '\u201c \u00fcbernehmen';
      b.addEventListener('click', () => hinzu(q));
      liste.appendChild(b);
    }
    liste.hidden = liste.children.length === 0;
  }

  input.addEventListener('input', suche);
  input.addEventListener('keydown', ev => {
    if (ev.key === 'Enter') {
      ev.preventDefault();
      const erster = liste.querySelector('.rmopt');
      if (erster) { erster.click(); }
    } else if (ev.key === 'Escape') {
      liste.hidden = true;
    } else if ((ev.key === 'Backspace' || ev.key === 'Delete')
               && input.value === '' && gewaehlt.length) {
      /* Rücktaste im LEEREN Feld nimmt den letzten Eintrag zurück — dasselbe
         Verhalten wie bei Empfängerfeldern im Mailprogramm. Ohne diese Zeile
         wäre der einzige Weg zurück das kleine ✕ mit der Maus, und die
         Tastaturbedienung endete an der Stelle, an der sie am meisten
         gebraucht wird. */
      ev.preventDefault();
      gewaehlt.pop();
      zeichneChips();
      suche();
    }
  });
  input.addEventListener('blur', () => setTimeout(() => { liste.hidden = true; }, 150));
  input.addEventListener('focus', suche);

  zeichneChips();
})();
</script>
<?php ui_seite_ende(); ?>
