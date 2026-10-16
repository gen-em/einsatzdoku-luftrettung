<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../einsatz_lib.php';           // Kindtabellen (Schritt 15/AP5)
require_once __DIR__ . '/../mission_fields_lib.php';   // mf_spalten() (Schritt 15/AP6)
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
 *                             missions: [{id, hhmm, pat_blob, client_ref,
 *                                         started_ts, ort_huelle, phasen,
 *                                         alt_rea, alt_ziel, alt_hoehe}] } } }
 *
 *   { action: 'commit',
 *     days:     [{day, crew: {<rolle>: name, ...}, vehicle_id, base_id,
 *                 mode:'insert'|'keep'|'update'}],
 *     missions: [{day, started_local:'HH:MM', winch,
 *                 resources:[], crew_override, crew: {<rolle>: name, ...},
 *                 pat_blob, dup:'insert'|'overwrite'|'skip', overwrite_id,
 *
 *                 // seit S11-05e: die Ortsdaten nur noch verschlossen
 *                 client_ref: 'imp-' + 24 Hex (nur beim Anlegen),
 *                 ort_huelle: Base64 (Einsatzhuelle), ort_phasen: [[nr, ts], …],
 *
 *                 // ab Web 2.10.0, alle optional (Rueckimport der eigenen
 *                 // Exportformate; die Jahreslisten-Profile senden sie nicht)
 *                 ended_utc, final, distance_m, ascent_m,
 *                 schockraum, secondary, winch_cycles, winch_cycles_pat,
 *                 winch_airload, bergwacht, bw_unit, bw_info, other_ema,
 *                 phases: [{phase:2..9, at:'...Z'|null, local:'HH:MM'|null}] }] }
 *
 * KEINE ORTSDATEN IM KLARTEXT (S11-05e, Betreiberin 16.10.2026, E-S11-51).
 * Bis Web 22.3.0 nahm dieser Endpunkt `transport_dest`, `dest_lat`,
 * `dest_lon`, `site_ele_m`, `phases[].lat/lon` und `rea` offen entgegen und
 * schrieb sie in die Klartextspalten und -tabellen — der Weg, den das
 * Formular in S11-05b geschlossen hat, stand hier noch offen. Jetzt legt der
 * Browser Transportziel und Ortshoehe in den `pat_blob`, Phasenorte und
 * Reanimation in die Einsatzhuelle (`ort_huelle`, verschlossen mit dem
 * oeffentlichen Kontoschluessel und gebunden an `client_ref`). Steht eines
 * der alten Felder trotzdem in der Nutzlast, antwortet der Endpunkt 400
 * `klartext` und schreibt nichts — wie `ingest.php` seit S11-04a.
 *
 * DER WAECHTER UEBER DIE ZEITEN, wie im Formular: Die Zeiten der Huelle sind
 * relativ zum Beginn; der Browser rechnet sie, der Server schreibt
 * `mission_phases`. Der Browser schickt seine Rechnung der fertigen
 * Phasenliste mit (`ort_phasen`), und weicht sie von dem ab, was nach dem
 * Schreiben in `mission_phases` steht, wird NICHTS uebernommen.
 *
 * WEGGELASSEN IST NICHT LEER (Backlog Nr. 28). Fuer 'ended_utc' und 'final'
 * gilt eine Unterscheidung, die der Rest der Nutzlast nicht kennt:
 *
 *   Feld FEHLT      Die Datei fuehrt die Spalte nicht (Jahresliste, Excel).
 *                   Es bleibt beim bisherigen Verhalten: ended_at = Beginn,
 *                   final = 1 beim Anlegen; beim Ueberschreiben bleibt
 *                   stehen, was dasteht.
 *   Feld ist null   Die Spalte ist da, die Zelle leer. Das IST eine Aussage:
 *                   ended_at = NULL, der Einsatz ist nicht abgeschlossen.
 *
 * Der Browser sendet die beiden Felder deshalb nur, wenn das Profil die
 * Spalte fuehrt (assets/import_ui.js). Bis Web 7.3.1 ging 'ended_utc' immer
 * hinaus, und ein nicht abgeschlossener Einsatz kam mit Ende = Beginn und
 * final = 1 zurueck — im Ueberschreiben-Modus auch dann, wenn er im Bestand
 * richtig stand (Fund F-P1-M).
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
 *     missions_overwritten, missions_skipped, first_day_id }
 *
 * `first_day_id` ist die KENNUNG des fruehesten angelegten Diensttags
 * (`days.id`), nicht sein Datum — `index.php?d=` erwartet eine Kennung,
 * und ein Datum bestimmt seit E9 keinen Tag mehr (Backlog Nr. 151).
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
    /* Wert aus einer festen Liste (Web 6.1.0): Was nicht darin steht, wird zu
     * NULL. Gebraucht fuer die beiden ENUM-Spalten `transport_mode` und
     * `start_src` — ein unbekannter Wert liefe sonst als Datenbankfehler auf,
     * und der brächte den ganzen Import zu Fall statt nur eine Zelle. */
    $auswahl = static function ($v, array $erlaubt): ?string {
        $w = trim((string)($v ?? ''));
        return in_array($w, $erlaubt, true) ? $w : null;
    };


    /* ---- Klartext-Ortsdaten abweisen (S11-05e, E-S11-51) ----------------
     * VOR der Transaktion und fuer die ganze Nutzlast: Ein Import ist alles
     * oder nichts, und eine Abweisung mitten in der Schleife liesse eine
     * offene Transaktion zurueck. */
    $klartext = static function (array $m): ?string {
        foreach (['transport_dest', 'dest_lat', 'dest_lon', 'site_ele_m'] as $f) {
            if (($m[$f] ?? null) !== null && $m[$f] !== '') { return $f; }
        }
        if (($m['rea'] ?? null) !== null) { return 'rea'; }
        foreach ((array)($m['phases'] ?? []) as $p) {
            if (is_array($p) && ((($p['lat'] ?? null) !== null) || (($p['lon'] ?? null) !== null))) {
                return 'phases.lat/lon';
            }
        }
        return null;
    };
    $refsNeu = [];
    foreach ($einsaetze as $i => $m) {
        if (!is_array($m)) { continue; }
        $f = $klartext($m);
        if ($f !== null) {
            json_out(['error' => 'klartext',
                      'meldung' => 'Die Nutzlast enthält Ortsdaten im Klartext (' . $f . ', Zeile '
                                 . ($i + 1) . '). Seit Web 22.4.0 verschlüsselt sie der Browser; '
                                 . 'bitte die Seite neu laden. Es wurde nichts übernommen.'], 400);
        }
        if (($m['dup'] ?? 'insert') === 'skip') { continue; }
        if (($m['dup'] ?? '') !== 'overwrite') {
            $ref = (string)($m['client_ref'] ?? '');
            if (!preg_match('/^imp-[0-9a-f]{24}$/', $ref) || isset($refsNeu[$ref])) {
                json_out(['error' => 'eingabe', 'meldung' => 'Die Kennung eines neuen Einsatzes fehlt '
                          . 'oder ist doppelt (Zeile ' . ($i + 1) . '). Bitte die Seite neu laden. '
                          . 'Es wurde nichts übernommen.'], 400);
            }
            $refsNeu[$ref] = true;
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        /* Die Pruefung der Stammdaten-Kennungen steckt seit Web 6.0.0 in
         * dt_zuordnen() (aufgerufen von dt_anlegen). Eine zweite Fassung hier
         * waere die Stelle, an der beide auseinanderlaufen — bis Web 20.47.0
         * pruefte sie dazu den Standort zentraler Rettungsmittel (E16, R39).
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
        $devId = geraet_virtuell_sicherstellen($pdo, $userId);

        /* ---- Einsaetze ---------------------------------------------------- */
        // Die zusaetzlichen Felder ab Web 2.10.0 haengen hinten an. Profile,
        // die sie nicht liefern, schreiben dort NULL beziehungsweise 0 — das
        // entspricht dem Zustand vor dieser Version.
        /* `final` ist jetzt ein Platzhalter, kein Literal (Backlog Nr. 28).
         * Es stand hier als `1` — ein nicht abgeschlossener Einsatz kam damit
         * abgeschlossen zurueck, obwohl die Exportdatei die Spalte fuehrt. */
        /* SPALTEN AUS DEM REGISTER (Schritt 15/AP6, E-ZE-22), Zweck
         * `import_neu`. ZWEI WERTE SIND FEST: `uhr_gesperrt` ist 1 (ein
         * importierter Einsatz gilt wie ein von Hand angelegter) und `origin`
         * ist 'import'. Sie stehen in `$festNeu` und damit an ihrer SPALTE,
         * nicht an ihrer Stelle im Satz — die 31 Fragezeichen von Hand
         * abzuzaehlen war die Quelle, aus der sich Spalten- und Werteliste
         * auseinanderentwickeln konnten. */
        $festNeu = ['uhr_gesperrt' => '1', 'origin' => "'import'"];
        $spNeu   = mf_spalten('import_neu', '', false);
        $insE = $pdo->prepare(
            'INSERT INTO missions (' . mf_spalten_sql('import_neu', '', false) . ')
             VALUES (' . implode(',', array_map(
                 static fn(string $c): string => $festNeu[$c] ?? '?', $spNeu)) . ')');
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
        /* `final` steht AUSSERHALB der COALESCE-Schranke und wird beim
         * Ueberschreiben mitgeschrieben — aber nur, wenn die Datei die Spalte
         * fuehrt. Sonst kommt hier der Wert an, der schon dasteht (unten
         * gelesen), und das UPDATE aendert nichts. Beides zusammen ist der
         * Punkt von Backlog Nr. 28: Ein Rueckimport des eigenen Exports darf
         * einen nicht abgeschlossenen Einsatz nicht abschliessen, und ein
         * Import einer Jahresliste darf ihn nicht anfassen. */
        /* SPALTEN AUS DEM REGISTER, Zweck `import_aendern`. Drei Wertformen:
         * die vier Felder unter der Export-Schranke mit COALESCE (siehe den
         * Absatz darueber), `uhr_gesperrt` und `edited` fest auf 1, alles
         * uebrige als Platzhalter. Die Form haengt an der SPALTE, nicht an
         * ihrer Stelle — wer eine Spalte unter die Schranke nimmt, traegt sie
         * in `$schranke` ein und sonst nirgends. */
        $festAend = ['uhr_gesperrt' => '1', 'edited' => '1'];
        /* `site_ele_m` STEHT SEIT S11-05e NICHT MEHR HIER: Die Hoehe zieht der
         * Browser beim Ueberschreiben in den `pat_blob` (`ortshoehe`), und die
         * Spalte wird geleert statt gehalten. */
        $schranke = ['pat_blob', 'bw_info', 'other_ema'];
        $spAend   = mf_spalten('import_aendern', '', false);
        $updE = $pdo->prepare(
            'UPDATE missions SET ' . implode(', ', array_map(
                static function (string $c) use ($festAend, $schranke): string {
                    $q = mf_bezeichner($c);   // Nr. 239: Namen in Backticks
                    if (isset($festAend[$c]))          { return "$q = {$festAend[$c]}"; }
                    if (in_array($c, $schranke, true)) { return "$q = COALESCE(?, $q)"; }
                    return "$q = ?";
                }, $spAend)) . '
             WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
        $hatPhase2 = $pdo->prepare(
            'SELECT id FROM mission_phases WHERE mission_id = ? AND phase = 2 LIMIT 1');
        /* DIE SIEBEN ANWEISUNGEN FUER DIE KINDTABELLEN STANDEN HIER, damit sie
         * in der Schleife nur EINMAL vorbereitet werden — `db.php` setzt
         * `ATTR_EMULATE_PREPARES => false`, jedes `prepare()` ist ein
         * Roundtrip. Seit Schritt 15/AP5 halten die vier
         * `einsatz_*_ersetzen()` sie selbst vor (`einsatz_anweisung()`), und
         * zwar je Verbindung und SQL-Text. Der Vorteil bleibt, die Anweisungen
         * stehen aber nicht mehr hier. */

        $neu = 0; $ersetzt = 0; $uebersprungen = 0;
        /* ZWEI MERKER FUER DEN ERSTEN TAG, nicht einer (Backlog Nr. 151).
         * `$ersterTag` ist der KALENDERTAG und taugt nicht als Ziel eines
         * Verweises: Seit E9 koennen mehrere Diensttage auf einem Datum
         * liegen, und `index.php` erwartet in `?d=` eine KENNUNG. Die
         * Kennung liegt in `$dayIdByDate[$tag]` bereit, aber nur INNERHALB
         * der Einsatzschleife — an der Stelle, an der die Antwort entsteht,
         * ist `$tag` nicht mehr im Zugriff. Deshalb laeuft sie hier mit. */
        $ersterTag = null; $ersterTagId = 0;

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
        $gehoert = $pdo->prepare('SELECT final FROM missions
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

            /* Ortszeit mit Kalendertagspruefung (B2) — auf dem ECHTEN
             * Einsatzdatum, nicht auf dem Diensttag.
             *
             * DER UNTERSCHIED ZAEHLT NUR UEBER MITTERNACHT, und dort zaehlt er
             * ganz: Ein Einsatz um 01:38 eines Dienstes, der am Vortag begann,
             * gehoert zum Folgetag. Bis Web 7.3.1 rechnete diese Stelle mit
             * $tag (dem Diensttag) und legte ihn 24 Stunden zu frueh ab — vor
             * dem Beginn des Dienstes, zu dem er gehoert. Gemessen im
             * Kreislauf der Phase P1: zwei Einsaetze, beide exakt einen Tag
             * daneben (Fund F-P1-K).
             *
             * DIE ANGABE LAG DIE GANZE ZEIT IN DER DATEI. `export_csv_v1`
             * fuehrt `diensttag` UND `datum` getrennt; die zweite Spalte war
             * im Profil auf target:null gesetzt.
             *
             * PLAUSIBILITAETSSCHRANKE: Uebernommen wird das Datum nur, wenn es
             * der Diensttag selbst ist oder der Tag darauf. Mehr kann es nicht
             * sein — die Anwendung kennt fuer den Tageswechsel genau einen
             * Schritt (local_to_utc mit addDays 0 oder 1, so auch im
             * Formular). Eine Datei fremder Herkunft mit unsinnigem `datum`
             * verstreut damit keine Einsaetze ueber den Kalender, sondern
             * faellt auf das bisherige Verhalten zurueck.
             *
             * Fuer die Gruppierung bleibt es beim Diensttag: `day_id` haengt
             * an ihm, nicht am Einsatzdatum. Zwei Quellen fuer ZWEI
             * verschiedene Aufgaben, nicht fuer dieselbe. */
            $bezugstag = $tag;
            $datumRoh = pruef_kalendertag($m['date_local'] ?? null, 'date_local');
            if ($datumRoh !== null && $datumRoh !== $tag) {
                $folgetag = (new DateTimeImmutable($tag))
                    ->add(new DateInterval('P1D'))->format('Y-m-d');
                if ($datumRoh === $folgetag) { $bezugstag = $datumRoh; }
            }
            $startedAt = pruef_ortszeit_zu_utc($bezugstag, $hhmm, 0, 'started_local', $pruef);
            if ($startedAt === null) { $grund['uhrzeit']++; $uebersprungen++; continue; }

            // Chiffretext nur formal pruefen — der Inhalt geht den Server
            // nichts an. Grenzen jetzt aus validate_lib.php (40…60000), damit
            // alle vier Schreibwege denselben Massstab haben; hier galt bisher
            // 20 als Untergrenze, im Formular 16 — beide unterhalb dessen, was
            // ein AES-GCM-Chiffretext ueberhaupt sein kann.
            $blob = pruef_pat_blob($m['pat_blob'] ?? null, 'pat_blob', $pruef);

            /* ENDZEIT UND ABSCHLUSS: WEGGELASSEN IST NICHT LEER (Nr. 28).
             *
             * Fehlt das Feld, fuehrt die Datei die Spalte nicht (Jahresliste,
             * Excel) — dann bleibt es beim bisherigen Verhalten: Ende = Beginn,
             * abgeschlossen. Steht es auf null, ist die ZELLE leer, und das ist
             * eine Aussage: Der Einsatz ist nicht abgeschlossen, sein Ende ist
             * offen. `dt_zeitraum_fortschreiben()` vertraegt das (es zieht das
             * Dienstende nur bei einem vorhandenen Wert nach).
             *
             * Bis Web 7.3.1 stand hier `?? $startedAt` hinter der Umwandlung,
             * und der leere Fall war vom fehlenden nicht zu unterscheiden. */
            $endedAt = array_key_exists('ended_utc', $m)
                ? $utc($m['ended_utc'])
                : $startedAt;
            $dateiFinal = array_key_exists('final', $m) ? $flag($m['final']) : null;

            /* Abweichende Besatzung des Einsatzes: role_code => name (E7).
             * Sie wird NACH dem Schreiben der Zeile in `mission_crew` gefuehrt,
             * nicht als Spalten hier — siehe unten. */
            $mCrew = [];
            foreach ((array)($m['crew'] ?? []) as $rolle => $name) {
                if (!array_key_exists((string)$rolle, CREW_ROLES)) { continue; }
                $w = $txt($name, 120);
                if ($w !== null) { $mCrew[(string)$rolle] = $w; }
            }

            /* Zielklinik-Koordinate. pruef_ortspaar() setzt beide Regeln
             * durch, die auch das Formular und die Stammdatenpflege anwenden:
             * nur zusammen gueltig, ausserhalb des Bereichs leer statt gekappt,
             * Komma als Dezimaltrennzeichen zulaessig (validate_lib.php). Eine
             * eigene Fassung hier waere die vierte gewesen — und die erste, die
             * beim naechsten Umbau vergessen wird. */
            /* Die Huelle des Einsatzes (S11-05e). Fehlt sie, ist das ein Weg am
             * Browser vorbei — dann wird nichts uebernommen. */
            $ortHuelle = pruef_huelle($m['ort_huelle'] ?? null, GH_EINSATZ_MAX_BYTE,
                                      'ort_huelle', $pruef);
            if ($ortHuelle === null) {
                throw new DomainException('ortsdaten: Einsatzhülle fehlt oder ist unbrauchbar');
            }

            /* JEDER WERT TRAEGT SEINEN SPALTENNAMEN (Schritt 15/AP6). Bis
             * Web 20.30.0 war das eine Liste ohne Namen, die auf zwei
             * Anweisungen mit 31 und 28 Spalten passen musste — wer eine
             * Spalte einfuegte, zaehlte an drei Stellen nach. Jetzt ordnet
             * das Register unten zu; die Reihenfolge hier ist nur noch die
             * Lesereihenfolge. */
            $werte = [
                /* TRANSPORTZIEL, ZIELKOORDINATE UND ORTSHOEHE LIEGEN SEIT
                 * S11-05e IM `pat_blob` (der Browser fuehrt beim Ueberschreiben
                 * den Altbestand dieser Spalten hinein); die Spalten werden
                 * geleert. Nach der Aufraeumauslieferung (S11-12) gibt es sie
                 * nicht mehr. */
                'transport_dest' => null,
                'winch'          => $flag($m['winch'] ?? null),
                'crew_override'  => $flag($m['crew_override'] ?? null),
                'pat_blob'       => $blob,
                'site_ele_m'     => null,
                'distance_m'     => $zahl($m['distance_m'] ?? null, 0, 100000000),
                'ascent_m'       => $zahl($m['ascent_m'] ?? null, 0, 1000000),
                'schockraum'     => $flag($m['schockraum'] ?? null),
                'secondary'      => $flag($m['secondary'] ?? null),
                'winch_cycles'     => $zahl($m['winch_cycles'] ?? null, 0, 127),
                'winch_cycles_pat' => $zahl($m['winch_cycles_pat'] ?? null, 0, 127),
                'winch_airload'  => $flag($m['winch_airload'] ?? null),
                'bergwacht'      => $flag($m['bergwacht'] ?? null),
                'bw_unit'        => $txt($m['bw_unit'] ?? null, 120),
                'bw_info'        => $txt($m['bw_info'] ?? null, 190),
                'other_ema'      => $txt($m['other_ema'] ?? null, 190),
                /* `notes` NIMMT DIESER ENDPUNKT NICHT MEHR ENTGEGEN (S9/AP7).
                 * Der Text steht im `pat_blob` daneben, den der Browser
                 * verschluesselt liefert. Waere die Spalte hier geblieben,
                 * koennte eine Nutzlast sie weiter im Klartext fuellen — der
                 * Import waere dann das Loch, das das Formular gerade
                 * geschlossen hat, und zwar eines, das niemandem auffiele. */
                /* Etappe 2 (Web 6.1.0). Beide ENUM-Spalten werden gegen ihre
                 * Werteliste geprueft: Ein unbekannter Wert wird zu NULL statt
                 * die Zeile mit einem Datenbankfehler scheitern zu lassen — die
                 * Datei kommt von aussen, und eine Tabelle mit einer
                 * Zusatzspalte „Transport: Rettungswagen" soll die anderen
                 * dreihundert Zeilen nicht mitnehmen.
                 *
                 * Sie stehen AUSSERHALB der COALESCE-Schranke: Wie
                 * transport_dest und die Flags stehen sie in jedem Export, ein
                 * leerer Wert ist dort eine Aussage (siehe Kommentar oben). */
                'transport_mode' => $auswahl($m['transport_mode'] ?? null,
                                             ['air', 'ground', 'ambulant']),
                'na_escort'      => $flag($m['na_escort'] ?? null),
                'false_alarm'    => $flag($m['false_alarm'] ?? null),
                'dest_lat'       => null,
                'dest_lon'       => null,
                'start_src'      => $auswahl($m['start_src'] ?? null,
                                             ['base', 'prev_site', 'prev_dest', 'manual']),
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
                /* Die Abfrage liefert jetzt `final` mit, nicht nur eine 1:
                 * Fuehrt die Datei die Spalte nicht, muss das UPDATE den
                 * BESTEHENDEN Wert zurueckschreiben statt ihn zu setzen.
                 * `fetchColumn() === false` bleibt der Test auf „keine Zeile" —
                 * ein `final` von 0 ist ein gueltiger Wert, kein Fehlschlag. */
                $gehoert->execute([$id, $userId]);
                $bestandFinal = $gehoert->fetchColumn();
                if ($bestandFinal === false) {
                    $grund['fremd_oder_geloescht']++;
                    $uebersprungen++; continue;
                }
                /* DIE REIHENFOLGE KOMMT AUS DEM REGISTER, nicht aus dem
                 * Abzaehlen: fuer jede Spalte des Zwecks ein Wert — es sei
                 * denn, sie steht als Literal im Satz. Die beiden WHERE-Werte
                 * haengen hinten an. */
                $kopf = ['day_id' => $dayId, 'started_at' => $startedAt,
                         'ended_at' => $endedAt,
                         'final' => $dateiFinal ?? (int)$bestandFinal];
                $p = [];
                foreach ($spAend as $c) {
                    if (isset($festAend[$c])) { continue; }
                    $p[] = array_key_exists($c, $kopf) ? $kopf[$c] : $werte[$c];
                }
                $updE->execute(array_merge($p, [$id, $userId]));
                $ersetzt++;
            } else {
                $kopf = ['user_id' => $userId, 'device_id' => $devId,
                         /* Vom Browser gezogen (S11-05e): Die Huelle ist an
                          * die Kennung gebunden und vor der Zeile verschlossen. */
                         'client_ref' => (string)$m['client_ref'],
                         'day_id' => $dayId, 'started_at' => $startedAt,
                         'ended_at' => $endedAt, 'final' => $dateiFinal ?? 1];
                $p = [];
                foreach ($spNeu as $c) {
                    if (isset($festNeu[$c])) { continue; }
                    $p[] = array_key_exists($c, $kopf) ? $kopf[$c] : $werte[$c];
                }
                $insE->execute($p);
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
            if ($mCrew) { einsatz_besatzung_ersetzen($pdo, $id, $mCrew); }

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

                /* KOORDINATEN UEBERLEBEN DAS ERSETZEN (P10, A9) — seit S11-05e im
                 * Browser: Er traegt die Orte des Bestands (Huelle oder
                 * Altbestand) je Phasennummer der Reihe nach in die neue Huelle,
                 * wenn die Datei keine nennt. Hier entstehen nur noch die
                 * Zeiten; bis Web 22.3.0 stand der Uebertrag an dieser Stelle,
                 * im Klartext. */
                einsatz_phasen_ersetzen($pdo, $id, []);   // leere Liste = nur loeschen
                $neuePhasen = [];
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
                    $neuePhasen[] = ['phase' => $nr, 'occurred_at' => $wann];
                    $gesetzt[$nr] = true;
                }
                einsatz_phasen_ersetzen($pdo, $id, $neuePhasen, ['loeschen' => false]);
            }
            if (!isset($gesetzt[2])) {
                $hatPhase2->execute([$id]);
                if ($hatPhase2->fetchColumn() === false) {
                    einsatz_phasen_ersetzen($pdo, $id,
                        [['phase' => 2, 'occurred_at' => $startedAt]], ['loeschen' => false]);
                }
            }

            /* ---- Ortsdaten: die Huelle, der Waechter, der Altbestand -------
             *
             * DIE FERTIGE PHASENLISTE GEGEN DIE RECHNUNG DES BROWSERS (wie im
             * Formular, S11-05b): nach Zeit und Nummer sortiert, damit die
             * Reihenfolge des Schreibens keine Rolle spielt. Weicht sie ab,
             * passen die relativen Zeiten der Huelle nicht zu `mission_phases`
             * — dann lieber gar nichts als eine Huelle mit verrutschten Orten.
             *
             * DANACH DER ALTBESTAND: Phasenorte im Klartext und die Zeilen der
             * Reanimation hat der Browser in die Huelle uebernommen (er bekam
             * sie mit `check`); hier werden sie geleert. Bis Web 22.3.0 schrieb
             * dieser Block die Reanimation aus der Datei in `resus_sessions`. */
            $istPhasen = $pdo->prepare('SELECT phase, occurred_at FROM mission_phases
                                         WHERE mission_id = ? ORDER BY occurred_at, phase, id');
            $istPhasen->execute([$id]);
            $ist = [];
            $ohneOrt = [];
            foreach ($istPhasen->fetchAll() as $r) {
                $ist[] = [(int)$r['phase'], (int)strtotime($r['occurred_at'] . ' UTC')];
                $ohneOrt[] = ['phase' => (int)$r['phase'], 'occurred_at' => $r['occurred_at']];
            }
            $soll = [];
            foreach ((array)($m['ort_phasen'] ?? []) as $z) {
                if (is_array($z) && count($z) === 2) { $soll[] = [(int)$z[0], (int)$z[1]]; }
            }
            usort($soll, static fn($a, $b) => [$a[1], $a[0]] <=> [$b[1], $b[0]]);
            if ($soll !== $ist) {
                require_once __DIR__ . '/../systemmeldung_lib.php';
                system_melden('ortsdaten', 'Import: Phasenzeiten von Browser und Server weichen ab',
                              null, ['konto' => $userId, 'einsatz' => $id,
                                     'browser' => count($soll), 'server' => count($ist)]);
                throw new DomainException('ortsdaten: Phasenzeiten weichen ab');
            }
            einsatz_phasen_ersetzen($pdo, $id, $ohneOrt);
            einsatz_reas_ersetzen($pdo, $id, []);
            $pdo->prepare('UPDATE missions SET ort_huelle = ? WHERE id = ? AND user_id = ?')
                ->execute([$ortHuelle, $id, $userId]);

            // Weitere Rettungsmittel als eigene Zeilen (einzeln entfernbar),
            // doppelte und leere verworfen — gleiche Regel wie im Formular.
            $rm = is_array($m['resources'] ?? null) ? $m['resources'] : [];
            $sauber = [];
            foreach ($rm as $name) {
                $name = mb_substr(trim((string)$name), 0, 120);
                if ($name !== '' && !in_array($name, $sauber, true)) { $sauber[] = $name; }
                if (count($sauber) >= LIMIT_RESSOURCEN) { break; }
            }
            einsatz_rettungsmittel_ersetzen($pdo, $id, $sauber);

            if ($ersterTag === null || $tag < $ersterTag) {
                $ersterTag = $tag; $ersterTagId = $dayId;
            }
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
            /* DIE KENNUNG, NICHT DAS DATUM (Backlog Nr. 151). `first_day`
             * ist ersatzlos entfallen — es hatte genau einen Verbraucher
             * (`assets/import_ui.js`), und der wechselt im selben Paket.
             * Ein zurueckgelassener Schluessel ohne Leser ist die naechste
             * Falle. */
            'first_day_id'          => $ersterTagId ?: null,
        ]);
    } catch (DomainException $ex) {
        /* Die Ortsdaten passen nicht (S11-05e): fehlende Huelle oder Zeiten,
         * die der Browser anders gerechnet hat. Nichts wird uebernommen. */
        $pdo->rollBack();
        json_out(['error' => 'ortsdaten',
                  'meldung' => str_contains($ex->getMessage(), 'Phasenzeiten')
                      ? 'Die Zeiten der Phasen ließen sich im Browser nicht gleich rechnen wie auf '
                        . 'dem Server. Es wurde NICHTS übernommen; die Abweichung ist gemeldet.'
                      : 'Phasenorte und Reanimation ließen sich nicht verschlüsselt übernehmen. '
                        . 'Bitte die Seite neu laden. Es wurde NICHTS übernommen.'], 409);
    } catch (Throwable $ex) {
        $pdo->rollBack();
        json_fehler($ex, 'commit');
    }
}

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)

    $b = api_rumpf();

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
        $st = db()->prepare("SELECT x.id, d.day, x.started_at, x.pat_blob, x.client_ref,
                                    x.ort_huelle, x.transport_dest, x.dest_lat, x.dest_lon,
                                    x.site_ele_m
                             FROM missions x
                             JOIN days d ON d.id = x.day_id
                             WHERE x.user_id = ? AND x.deleted_at IS NULL
                               AND d.deleted_at IS NULL
                               AND d.day IN ($platz)
                             ORDER BY x.started_at");
        $st->execute(array_merge([$userId], $tage));
        $einsRows = $st->fetchAll();

        /* DIE ORTSDATEN DES BESTANDS (S11-05e). Ueberschreibt der Import einen
         * Einsatz, baut der Browser dessen Huelle neu und braucht dafuer, was
         * schon da ist: die Huelle selbst (mit Kennung und Beginn), die
         * Phasenzeiten und — solange es ihn gibt — den Altbestand im Klartext
         * (Phasenorte, Reanimation, Transportziel, Hoehe). Er zieht ihn in
         * Huelle und Blob um; der Commit leert danach die Klartextstellen.
         * Alles davon gehoert dem anfragenden Konto; der Altbestand ist ohnehin
         * Klartext bis S11-10. */
        $eIds = array_map(static fn($r) => (int)$r['id'], $einsRows);
        $phasenVon = []; $reaVon = [];
        if ($eIds) {
            foreach (sql_in_bloecken(db(),
                    'SELECT mission_id, phase, occurred_at, lat, lon FROM mission_phases
                     WHERE mission_id IN ({IDS}) ORDER BY mission_id, occurred_at, phase, id',
                    $eIds) as $r) {
                $phasenVon[(int)$r['mission_id']][] = [
                    'phase' => (int)$r['phase'], 'ts' => (int)strtotime($r['occurred_at'] . ' UTC'),
                    'lat' => $r['lat'] !== null ? (float)$r['lat'] : null,
                    'lon' => $r['lon'] !== null ? (float)$r['lon'] : null];
            }
            $sitz = sql_in_bloecken(db(),
                'SELECT id, mission_id, started_at FROM resus_sessions
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, started_at', $eIds);
            $evVon = [];
            $sIds = array_map(static fn($r) => (int)$r['id'], $sitz);
            if ($sIds) {
                foreach (sql_in_bloecken(db(),
                        'SELECT session_id, type, occurred_at FROM resus_events
                         WHERE session_id IN ({IDS}) ORDER BY session_id, occurred_at', $sIds) as $r) {
                    $evVon[(int)$r['session_id']][] = ['art' => (string)$r['type'],
                        'ts' => (int)strtotime($r['occurred_at'] . ' UTC')];
                }
            }
            foreach ($sitz as $r) {
                $reaVon[(int)$r['mission_id']][] = ['ts' => (int)strtotime($r['started_at'] . ' UTC'),
                                                    'ereignisse' => $evVon[(int)$r['id']] ?? []];
            }
        }

        foreach ($einsRows as $m) {
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
                'client_ref' => (string)$m['client_ref'],
                'started_ts' => (int)strtotime($m['started_at'] . ' UTC'),
                'ort_huelle' => $m['ort_huelle'] !== null ? base64_encode((string)$m['ort_huelle']) : null,
                'phasen'     => $phasenVon[(int)$m['id']] ?? [],
                'alt_rea'    => $reaVon[(int)$m['id']] ?? [],
                'alt_ziel'   => $m['transport_dest'] !== null || $m['dest_lat'] !== null
                    ? ['addr' => $m['transport_dest'],
                       'lat' => $m['dest_lat'] !== null ? (float)$m['dest_lat'] : null,
                       'lon' => $m['dest_lon'] !== null ? (float)$m['dest_lon'] : null]
                    : null,
                'alt_hoehe'  => $m['site_ele_m'] !== null ? (int)$m['site_ele_m'] : null,
            ];
        }
    }

    json_out(['days' => $antwortTage]);
} catch (Throwable $ex) {
    // Lesbare Meldung statt leerem HTTP 500 — die Seite zeigt sie an.
    json_fehler($ex, 'check');
}
