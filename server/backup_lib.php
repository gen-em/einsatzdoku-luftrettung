<?php
declare(strict_types=1);
/**
 * Backup: Serialisierung und Wiederherstellung aller Daten einer NutzerIn.
 * Format-Doku: docs/Backup-Format.md
 *
 * Seit Format 2 versiegelt und oeffnet der BROWSER die Datei (crypto.js):
 * Er entschluesselt die geschuetzten Angaben vor dem Export und verschluesselt
 * sie beim Import mit dem Schluessel des Zielkontos neu — dadurch laesst sich
 * ein Backup in jedes Konto einspielen. Der Container ist in
 * docs/Backup-Format.md beschrieben; diese Datei serialisiert nur noch die
 * Daten (edbak_build) und spielt sie zurueck (edbak_restore).
 */

/* Pruefschicht ausdruecklich laden.
 *
 * WARUM DAS HIER STEHEN MUSS
 * edbak_restore() benutzt Pruefliste und die pruef_*-Funktionen. Sie standen
 * nur zufaellig zur Verfuegung, wenn die aufrufende Seite validate_lib.php
 * schon geladen hatte — api/backup_restore.php tat das NICHT. Das
 * Wiedereinspielen brach deshalb mit "Class Pruefliste not found" ab, und zwar
 * seit die Pruefschicht eingefuehrt wurde.
 *
 * Gefunden auf der Testinstallation, nicht durch die automatische Pruefung:
 * Deren Skript hatte validate_lib.php selbst geladen und den fehlenden
 * require damit verdeckt. Wer eine Bibliothek prueft, muss sie so laden, wie
 * die Anwendung sie laedt — sonst prueft er seinen eigenen Aufbau mit.
 */
require_once __DIR__ . '/einsatz_lib.php';   // Kindtabellen
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/spur_lib.php';   // Spuren: Zeilen UND Blob
require_once __DIR__ . '/mission_fields_lib.php';   // mf_ist_spalte(), mf_ort_spalten(), mf_spalten()
require_once __DIR__ . '/geraete_lib.php';         // HERKUNFT_WERTE, herkunft_ableiten()

/**
 * Wie viele Einsaetze beziehungsweise Ruhesegmente `edbak_build()` auf einmal
 * zusammensetzt.
 *
 * NICHT DIE ABFRAGEN, SONDERN DER SPEICHER entscheidet diese Zahl. Die
 * Kindtabellen werden weiterhin gebuendelt geholt — ein N+1 kommt
 * nicht zurueck; sie laufen nur je Fenster statt je
 * Konto. Bei 500 kostet ein 5000er-Bestand elf Durchgaenge zu vier Abfragen
 * statt vier ueber alles, und die Speicherspitze faellt von 92 MB unter das
 * Budget von 64 MB. Gemessen, nicht geschaetzt.
 */
const EDBAK_FENSTER = 500;

/**
 * Die Nutzlast, die diese Fassung schreibt (Backup-Format 2; die Geschichte
 * der Zahlen steht bei `'version'` in `edbak_build()`).
 *
 * EINE STELLE. Gebraucht wird die Zahl hier, im Manifest der
 * Admin-Sicherung und in den zwei Wegen, die Eintragsteile einspielen; eine
 * Zahl, die an mehreren Stellen nachgezogen werden muss, wird an einer
 * vergessen. `NUTZLAST_HOECHSTENS` (api/backup_restore.php) steht bewusst
 * NICHT hierauf: Die Obergrenze hebt nur, wer den Rueckweg vorbereitet hat.
 */
const EDBAK_NUTZLAST = 13;

/**
 * Inneres Backup-JSON aufbauen.
 *
 * DER PAPIERKORB IST TEIL JEDES BACKUPS. Keine Abfrage hier filtert auf
 * `deleted_at IS NULL`, und es gibt keinen Schalter dafuer.
 *
 * WARUM DIE ENTSCHEIDUNG UMGEDREHT WURDE. Die alte Begruendung lautete: „Wer
 * ein Backup erstellt, sichert seinen Bestand, nicht seinen Abfall." Das
 * klingt einleuchtend und ist trotzdem falsch, denn der Papierkorb ist kein
 * Abfall — er ist ein WIEDERHERSTELLBARER Zustand mit einer laufenden Frist
 * (TRASH_DAYS = 90). Wer am Tag nach einem versehentlichen Loeschen sichert
 * und das Backup spaeter zurueckspielt, verliert genau das, was er
 * zurueckholen wollte, und zwar endgueltig und ohne Hinweis. Ein Backup
 * ist ein ABBILD; ein Abbild, das einen Teil des Bestands weglaesst, ist
 * keines.
 *
 * KEINE WAHLMOEGLICHKEIT AUF DER BACKUP-SEITE. Ein Haken
 * „Papierkorb mitsichern" verschoebe die Entscheidung auf den Zeitpunkt, an
 * dem am wenigsten ueberlegt wird. Stattdessen nennen die Umfangsangaben
 * (Admin-Tabelle, Freigabe-Hinweis, `umfang.papierkorb` des Admin-Backups),
 * wie viel davon im Papierkorb liegt.
 *
 * Die Spalten `deleted_at` und `deleted_with_day` standen schon vorher in der
 * Datei — bisher stets `null` beziehungsweise `0`. Seit Nutzlast 7 tragen sie
 * einen Zustand, und `edbak_restore()` wertet ihn aus: Was hier geloescht ist,
 * kommt als Papierkorbeintrag zurueck. Der ZEITPUNKT wandert dabei nicht mit
 * — der Eintrag entsteht in der Zielinstallation neu und bekommt volle
 * 90 Tage (docs/Backup-Format.md 2 und 3).
 *
 * `$ohneSpuren` IST DER KERN DER FASSUNG 4. Statt der
 * Punktlisten traegt jedes spurtragende Objekt dann eine fortlaufende
 * `spur_ref` und die Angaben `stufe`, `n_original` und `n`; die Punkte kommen
 * ueber `api/backup_spuren.php` in eigene Teile (seit Nutzlast 13 als
 * Punktlisten, die der Browser aus den Stuecken oeffnet; bis 12 als SPUR1).
 *
 * WARUM DIE ZAHLEN IN DEN KERN GEHOEREN und nicht nur in die Spurteile: Sie
 * sind die einzige Stelle, an der beim Vergleich zu sehen ist, dass eine Spur
 * AUSGEDUENNT ist statt verlorengegangen. Und sie kosten nichts — `spur_zahlen()`
 * und `spur_umriss()` holen sie, ohne einen Punkt zu lesen.
 *
 * WARUM DIE NUMMER FORTLAUFEND IST und nicht die Datenbankkennung: Die
 * Kennung gilt nur in der Datenbank, aus der das Backup stammt — dieselbe
 * Ueberlegung wie beim Diensttag und beim Standortnamen. `spur_ref`
 * ist eine Nummer DIESES Vorgangs und sonst nichts.
 */
function edbak_build(int $userId, bool $ohneSpuren = false,
                     ?array $fenster = null): string {
    $pdo = db();

    /* ZWEI BETRIEBSARTEN, EINE FUNKTION.
     *
     *   ['kopf' => true]            nur der Kopf: Stammdaten und Diensttage
     *   ['ab' => x, 'anzahl' => y]  ein Fenster von Eintraegen
     *
     * Eine dritte Form mit Punktlisten und Reanimation gibt es nicht: Der
     * Server sieht keine Punkte und keine Reanimation.
     * `$ohneSpuren` ist seitdem immer wahr.
     *
     * WARUM NICHT ZWEI FUNKTIONEN. Weil dann zwei Stellen wuessten, wie aus
     * einem Einsatz JSON wird — und die naechste neue Spalte in zweien davon
     * landete. Die Spaltenliste, die Kindtabellen, die Reihenfolge und die
     * Sonderfaelle sind der eigentliche Inhalt dieser Funktion; die
     * Betriebsart entscheidet nur, WELCHE Teile davon laufen. */
    $nurKopf    = !empty($fenster['kopf']);
    $nurFenster = isset($fenster['ab']);
    if (!$ohneSpuren || (!$nurKopf && !$nurFenster)) {
        throw new InvalidArgumentException('edbak_build: nur Kopf oder Fenster, ohne Punktlisten (seit Web 23)');
    }
    $q = function (string $sql, array $p) use ($pdo): array {
        $st = $pdo->prepare($sql); $st->execute($p); return $st->fetchAll(PDO::FETCH_ASSOC);
    };

    $u = $q('SELECT email, name, pat_key_check FROM users WHERE id = ?', [$userId])[0];

    /* Umwandeln nur, wenn dabei auch eine Zahl herauskommt.
     *
     * (float)$p['ele'] liefert fuer jede Eingabe klaglos ein Ergebnis: aus
     * "" wird 0.0, aus "Unfug" ebenfalls. Die Hoehe 0 ist aber ein GUELTIGER
     * Wert (Meereshoehe) — im Backup waere danach nicht mehr zu
     * unterscheiden, ob dort eine gemessene Null stand oder ein Rest, den die
     * Umwandlung erzeugt hat.
     *
     * Aus der Datenbank kommen an dieser Stelle nur Zahlen oder NULL; die
     * Spalten sind DOUBLE. Der Befund zielt auf den Fall, in dem das einmal
     * nicht mehr stimmt — ein Backup ist das letzte, was stillschweigend
     * Ersatzwerte erfinden darf. */
    $zahl = fn($v, bool $ganz = false) =>
        (is_numeric($v) ? ($ganz ? (int)$v : (float)$v) : null);

    /* FASSUNG 4: statt der Punkte nur ihr Umriss — kein gelesener Punkt, und
     * die laufende Nummer, unter der der Spurteil die Stuecke wiederfindet.
     *
     * DIE ZAHLEN BESCHREIBEN, WAS IN DER DATEI STEHT: Eine
     * Spur aus Geraetestuecken oder zusammengefuegt steht dort als Stufe 2
     * mit `n_original = n`, eine ausgeduennte als Stufe 3 mit der Zahl VOR
     * der Ausduennung. `n` ist die Summe der gespeicherten Punkte. */
    $spurVerzeichnis = [];
    $umrisse = function (string $type, array $ids) use ($pdo, &$indexVon): array {
        $nach = [];
        if (!$ids) { return $nach; }
        $z = spur_zahlen($pdo, $type, $ids);
        $s2 = spur2_umriss($pdo, $type, $ids);
        foreach ($ids as $id) {
            $n = (int)($z[$id] ?? 0);
            if ($n === 0 || !isset($s2[$id])) { continue; }
            $duenn = $s2[$id]['stufe'] === SPUR2_STUFE_DUENN;
            $nach[$id] = ['spur_ref' => $indexVon[$type . '-' . $id],
                          'stufe' => $duenn ? SPUR2_STUFE_DUENN : SPUR2_STUFE_GEPACKT,
                          'n_original' => $duenn ? $s2[$id]['n_original'] : $n,
                          'n' => $n];
        }
        return $nach;
    };

    $missions = [];
    /* SPALTEN AUFZAEHLEN, NICHT ALLE NEHMEN.
     *
     * Vorher stand hier SELECT * und darunter ein unset() fuer die drei
     * internen Spalten. Das FORMAT des Backups war damit nicht definiert,
     * sondern ERGAB SICH: Was in der Tabelle stand, landete in der Datei.
     *
     * Zwei Folgen, beide unangenehm:
     *  - Jede neue Spalte war automatisch in jedem Backup — auch eine,
     *    die dort nichts zu suchen hat. Wer eine interne Spalte ergaenzt,
     *    denkt nicht daran, dass er damit das Ausgabeformat aendert.
     *  - Das Wiedereinspielen und der Export prueften gegen eine Liste, die
     *    hier nirgends stand. Ob beide dasselbe meinten, liess sich nur
     *    durch Ausprobieren feststellen.
     *
     * Kommt kuenftig eine Spalte hinzu, die mitgesichert werden soll, ist sie
     * hier einzutragen — und genau das ist der Punkt: Es ist eine
     * Entscheidung, keine Nebenwirkung. */
    /* `uhr_gesperrt AS manual` — DER ALIAS IST PFLICHT, NICHT KOSMETIK.
     * Die Spalte heisst `uhr_gesperrt`, der
     * SCHLUESSEL IN DER DATEI bleibt `manual`: Jede bisher ausgelieferte
     * Sicherung traegt ihn, der Demo-Bestand ist selbst eine solche Datei,
     * und `edbak_restore()` weiter unten liest ihn unter diesem Namen.
     * Ohne den Alias hiesse der Schluessel in jeder NEUEN Datei still
     * `uhr_gesperrt` — und das Einspielen faende das Feld nicht mehr, ohne
     * eine einzige Fehlermeldung. */
    /* AUS DEM REGISTER. Die Liste stand hier von
     * Hand; die Gruende, warum id, user_id, device_id und die tote
     * other_resources fehlen, stehen jetzt in `mf_missions_gruende()` und
     * damit neben den Spalten selbst statt in diesem Kommentar.
     *
     * DER ALIAS STEHT IN BACKTICKS, und zwar in `mf_spalten()` statt hier: `manual` ist auf
     * MySQL 8.4.0 bis 8.4.10 auch als ALIAS ein reserviertes Wort. Ohne die
     * Backticks antwortete der Export dort mit 1064 und die Sicherung kam
     * nie an (Staging, MySQL 8.4.10, Kennung 097D7622). Die Behebung stand
     * an ZWEI Stellen — hier und in api/export_data.php —; sie steht jetzt
     * an der einen, die aus dem Register SQL macht, und gilt damit auch fuer
     * jeden kuenftigen Alias. */
    $missionSpalten = mf_spalten_sql('backup');
    /* NICHT in der Liste, und zwar mit Absicht:
     *
     *   id, user_id, device_id   Interne Verweise. Sie gelten nur in DIESER
     *                            Datenbank; ein Backup soll sich auch in
     *                            eine andere einspielen lassen.
     *
     *   other_resources          TOTE ALTSPALTE. Seit der Migration
     *                            2026_07 liegen die weiteren Rettungsmittel
     *                            als einzelne Zeilen in mission_resources
     *                            (dort einzeln entfernbar) und werden unten
     *                            als 'resources' mitgesichert. Die Spalte
     *                            wurde damals nur nicht geloescht. Mit
     *                            SELECT * ging sie trotzdem in jede
     *                            Backup — ein Feld, das seit Monaten
     *                            niemand mehr fuellt und das beim
     *                            Einspielen verworfen wird.
     *
     * DIE EINSATZORT-HOEHE reist im `pat_blob` (`ortshoehe`) — eine
     * Spalte dafuer gibt es nicht. */
    /* IN FENSTERN, NICHT AUF EINMAL.
     *
     * WARUM. Hier standen vier Abfragen ueber ALLE Einsaetze eines Kontos —
     * Phasen, Rettungsmittel, Reanimation, abweichende Besatzung —, und ihre
     * Ergebnisse lagen gleichzeitig im Speicher. Am 5000er-Bestand gemessen:
     * 92 MB Spitze gegen ein Budget von 64 MB (Z3). Mit `memory_limit=64M`
     * bricht der Lauf ab; das Backup eines grossen Kontos scheitert also
     * auf genau der Sorte Webspace, fuer die diese Anwendung gebaut ist.
     *
     * Die Abfragen bleiben gebuendelt — ein N+1 kommt nicht zurueck. Sie laufen
     * nur je Fenster statt je Konto: aus vier
     * Abfragen werden vier je 500 Einsaetzen, also elf mal vier statt vier.
     * Das ist der Preis, und er ist klein gegen einen Abbruch.
     *
     * Die REIHENFOLGE ist die des Formats und kommt aus der Kennungsabfrage
     * oben; die Fenster halten sie ein. Sie ist Teil des Dateiformats, nicht
     * Zufall.
     */
    /* DIE KENNUNGEN BEIDER ARTEN, in der Reihenfolge des Formats. Sie sind
     * die Grundlage des Fensters: Ein Eintrag ist ein Einsatz ODER ein
     * Ruhesegment, und `ab`/`anzahl` laufen ueber beide zusammen — sonst
     * muesste der Browser wissen, wo die eine Liste endet und die andere
     * beginnt, und das ist eine Auskunft, die er nicht braucht. */
    $alleMissionIds = array_map(static fn($r) => (int)$r['id'],
        $q('SELECT id FROM missions WHERE user_id = ? ORDER BY started_at', [$userId]));
    $alleRestIds = array_map(static fn($r) => (int)$r['id'],
        $q('SELECT id FROM rest_segments WHERE user_id = ? ORDER BY started_at', [$userId]));
    $eintraegeGesamt = count($alleMissionIds) + count($alleRestIds);

    /* `spur_ref` IST DER LAUFENDE INDEX DES EINTRAGS, nicht ein eigener
     * Zaehler. Das ist der Unterschied, der das Fenstern moeglich macht: Ein
     * Zaehler muesste ueber alle Fenster hinweg fortgefuehrt werden, und wer
     * ein Fenster einzeln zieht, bekaeme falsche Nummern. Der Index steht
     * fest, sobald die Reihenfolge steht.
     *
     * Eintraege OHNE Spur bekommen gar keine `spur_ref`; die Nummernfolge hat
     * deshalb Luecken. Das ist richtig so — sie ist eine Kennung, keine
     * Zaehlung. */
    $ab     = $nurFenster ? max(0, (int)$fenster['ab']) : 0;
    $anzahl = $nurFenster ? max(1, (int)($fenster['anzahl'] ?? EDBAK_FENSTER)) : 0;
    $bis    = $nurKopf ? 0 : min($eintraegeGesamt, $ab + $anzahl);

    $missionIds = $nurKopf ? []
        : array_slice($alleMissionIds, $ab, max(0, $bis - $ab));
    $restAb  = max(0, $ab - count($alleMissionIds));
    $restBis = max(0, $bis - count($alleMissionIds));
    $restIdsFenster = $nurKopf ? []
        : array_slice($alleRestIds, $restAb, max(0, $restBis - $restAb));

    /* Der Index eines Eintrags in der Gesamtfolge — die `spur_ref`. */
    $indexVon = [];
    foreach ($alleMissionIds as $i => $mid) { $indexVon['mission-' . $mid] = $i; }
    foreach ($alleRestIds as $i => $rid) {
        $indexVon['rest-' . $rid] = count($alleMissionIds) + $i;
    }

    /* Die Spurangaben je Einsatz stehen VOR den Fenstern: `spur_ref` zaehlt
     * ueber den ganzen Vorgang durch, und die Reihenfolge dieser Nummern ist
     * die der Einsaetze. Sie kosten wenig — vier Abfragen, kein Punkt. */
    $spurNachEinsatz = $umrisse('mission', $missionIds);

    /* JE EINSATZ KODIEREN UND FREIGEBEN, statt erst alle zu sammeln.
     *
     * Mit `$missions[] = $m;` laegen drei Kopien desselben Bestands im
     * Speicher: die Zeilen aus der Datenbank,
     * das zusammengesetzte Feld und am Ende die JSON-Ausgabe.
     *
     * DAS FORMAT AENDERT SICH DABEI NICHT. Es entsteht dasselbe JSON; nur die
     * REIHENFOLGE der Schluessel im Kopf ist eine andere, und JSON kennt keine
     * Reihenfolge. Der Kreislauf misst das nach. */
    $missionsJson = '';
    /* Karteileichen unter den Sperrvermerken, ueber alle Fenster gezaehlt
     * (s. u. bei `schnitte_zu_einsaetzen()`). Steht nur dann im Kopf, wenn es
     * welche gab. */
    $schnitteWaisen = 0;
    foreach (array_chunk($missionIds, EDBAK_FENSTER) as $fenster) {
        $zeilen = [];
        foreach (sql_in_bloecken($pdo,
                "SELECT id, $missionSpalten FROM missions
                  WHERE user_id = ? AND id IN ({IDS})", $fenster, [$userId]) as $r) {
            /* Die Einsatzhuelle ist binaer; JSON nimmt sie nur als
             * Base64. Ohne Huelle bleibt das Feld weg — ein `null` saehe aus
             * wie „Huelle verloren", und das ist sie nicht. */
            if ($r['ort_huelle'] === null) { unset($r['ort_huelle']); }
            else { $r['ort_huelle'] = base64_encode((string)$r['ort_huelle']); }
            $zeilen[(int)$r['id']] = $r;
        }

        /* Abweichende Besatzung je Einsatz (`mission_crew`). Frueher waren es
         * fuenf Spalten in `missions`; sie stehen deshalb im Format
         * jetzt als Objekt role_code => name. */
        $einsatzCrewNach = [];
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, role_code, name FROM mission_crew
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, role_code',
                $fenster) as $c) {
            $einsatzCrewNach[(int)$c['mission_id']][(string)$c['role_code']] = $c['name'];
        }

        /* Phasen und Rettungsmittel: je eine Abfrage statt je Einsatz.
         * Die Orte der Phasen und die Reanimation liegen in der
         * Einsatzhuelle (`ort_huelle`); der Browser legt sie beim Sichern
         * offen in den Kern. */
        $phasenNach = $mittelNach = [];
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, phase, occurred_at FROM mission_phases
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, occurred_at',
                $fenster) as $p) {
            $phasenNach[(int)$p['mission_id']][] = [
                'phase' => $zahl($p['phase'], true), 'occurred_at' => $p['occurred_at']];
        }
        foreach (sql_in_bloecken($pdo,
                'SELECT mission_id, name FROM mission_resources
                 WHERE mission_id IN ({IDS}) ORDER BY mission_id, id',
                $fenster) as $r) {
            $mittelNach[(int)$r['mission_id']][] = (string)$r['name'];
        }
        /* ---- Die Sperrvermerke des Schnitts (Nutzlast 9) --
         *
         * EINE ABFRAGE JE FENSTER, ueber `spur_lib.php` — `track_cuts` ist
         * eine der drei Tabellen, an die nur diese Datei heranreicht
         * (CLAUDE.md 4). Die Funktion loest die Quelle gleich zur `client_ref`
         * auf: Eine Datenbankkennung gilt nur in DIESER Datenbank, und genau
         * daran ist der Vermerk bisher gescheitert.
         *
         * Sie zaehlt dabei Vermerke, deren Quelle sich nicht mehr aufloesen
         * laesst. Es kann sie nicht geben — `schnitte_loeschen_quelle()`
         * raeumt beim Loeschen einer Quelle auf —, aber „kann nicht" ist
         * keine Zusicherung, und eine stumm weggelassene Zeile ist die
         * schlechteste Art, damit umzugehen. */
        $schnitteNach = schnitte_zu_einsaetzen($pdo, $userId, $fenster, $schnitteWaisen);

        foreach ($fenster as $mid) {
            if (!isset($zeilen[$mid])) { continue; }
            $m = $zeilen[$mid];
            unset($zeilen[$mid], $m['id']);   // id nur fuer die Zuordnung
            $m['phases']    = $phasenNach[$mid]    ?? [];
            $m['resources'] = $mittelNach[$mid]    ?? [];
            $m['crew']      = $einsatzCrewNach[$mid] ?? [];
            /* `schnitte` STEHT AN JEDEM EINSATZ, leer erlaubt — wie die vier
             * Listen darueber und ausdruecklich anders als `track`/`spur_ref`.
             * Der Unterschied hat einen Grund: Ein fehlendes
             * `schnitte` waere zweideutig — „dieser Einsatz hat keine
             * Vermerke" oder „diese Fassung kennt sie nicht". Ein leeres
             * Feld sagt das erste. */
            $m['schnitte']  = $schnitteNach[$mid]  ?? [];
            /* KEIN `track` UND KEIN LEERES `track`. Ein leeres Feld saehe aus
             * wie „hat keine Spur"; die Fassung sagt, dass die Punkte woanders
             * stehen. Wer keine Spur hat, bekommt gar keine `spur_ref`. */
            if (isset($spurNachEinsatz[$mid])) {
                $m += $spurNachEinsatz[$mid];
                $spurVerzeichnis[] = ['spur_ref' => $m['spur_ref'], 'art' => 'mission',
                                      'id' => $mid, 'n' => $m['n']];
            }
            unset($spurNachEinsatz[$mid]);
            $missionsJson .= ($missionsJson === '' ? '' : ',')
                           . json_encode($m, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            unset($m);
        }
        unset($zeilen, $phasenNach, $mittelNach, $einsatzCrewNach,
              $schnitteNach);
    }
    unset($spurNachEinsatz);


    $restsJson = '';
    $restZeilen = [];
    if ($restIdsFenster) {
        /* NUR DIE DES FENSTERS. Ruhesegmente haben keine Kindtabellen; ein
         * Fenster genuegt hier ohne die Staffelung, die die Einsaetze
         * brauchen. Die Reihenfolge kommt aus der Kennungsliste oben. */
        $nach = [];
        foreach (sql_in_bloecken($pdo,
                'SELECT id, client_ref, day_id, started_at, ended_at, final,
                        geraet_art, geraet_modell,
                        deleted_at, deleted_with_day
                   FROM rest_segments WHERE user_id = ? AND id IN ({IDS})',
                $restIdsFenster, [$userId]) as $r) {
            $nach[(int)$r['id']] = $r;
        }
        foreach ($restIdsFenster as $rid) {
            if (isset($nach[$rid])) { $restZeilen[] = $nach[$rid]; }
        }
        unset($nach);
    }
    $spurNachRuhe = $umrisse('rest', $restIdsFenster);
    /* Die Loeschvermerke des Segments (Nutzlast 13). Wie `schnitte`
     * am Einsatz steht das Feld an JEDEM Segment, leer erlaubt: Ein fehlendes
     * hiesse in einer 13er-Datei nichts anderes als in einer 12er — „nicht
     * erhoben". */
    $loeschNachRuhe = loeschvermerke_zu_quellen($pdo, $userId, 'rest', $restIdsFenster);
    while ($restZeilen) {                       // dieselbe Begruendung wie oben
        $r = array_shift($restZeilen);
        $rid = (int)$r['id'];
        unset($r['id']);
        $r['loeschungen'] = $loeschNachRuhe[$rid] ?? [];
        if (isset($spurNachRuhe[$rid])) {
            $r += $spurNachRuhe[$rid];
            $spurVerzeichnis[] = ['spur_ref' => $r['spur_ref'], 'art' => 'rest',
                                  'id' => $rid, 'n' => $r['n']];
        }
        unset($spurNachRuhe[$rid]);
        $restsJson .= ($restsJson === '' ? '' : ',')
                    . json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        unset($r);
    }
    unset($restZeilen, $spurNachRuhe, $loeschNachRuhe);

    /* ---- Diensttage -------------------------------------------------------
     *
     * DIE KENNUNG MUSS MIT. Das Datum benennt keine Zeile: Mehrere
     * Diensttage koennen auf einem Kalendertag liegen. Einsaetze und
     * Ruhe-Segmente verweisen mit `day_id` hierher, und beim Einspielen wird die
     * Kennung auf die neu vergebene umgeschrieben.
     *
     * ANGEZEIGT UND GESICHERT WERDEN DIE SNAPSHOT-SPALTEN: `vehicle_name`,
     * seit Nutzlast 10 auch `vehicle_typ` und `vehicle_kurz`,
     * und `base_name` stehen im Diensttag selbst. Der frueher noetige Join auf
     * `aircraft` und `bases` ist damit entfallen — und mit ihm die Luecke, dass
     * ein geloeschtes Rettungsmittel ein Backup ohne Bezeichnung hinterliess.
     * Die Verweise auf die Stammdaten werden zusaetzlich als NAMEN mitgefuehrt
     * (`vehicle_ref`, `base_ref`), damit das Einspielen sie wieder verknuepfen
     * kann; sie zeigen auf denselben Namen, koennen aber leer sein, wenn der
     * Stammdatensatz inzwischen fehlt.
     *
     * Rollensatz und Faehigkeiten sind Teil des Snapshots und muessen mit —
     * ohne sie waere nach dem Einspielen nicht mehr erkennbar, welche Rollen ein
     * Dienst anbot.
     *
     * `day_refs` MUSS ins Backup: Sonst legt ein spaeter
     * eintreffender Upload derselben Uhr den Diensttag nach einer
     * Wiederherstellung erneut an. */
    $days = [];
    /* IM FENSTERMODUS NICHT. Stammdaten und Diensttage stehen im KOPF, und der
     * kommt genau einmal. Sie in jedes Fenster zu legen hiesse, sie bei elf
     * Fenstern elfmal zu holen, zu kodieren und zu uebertragen. */
    $dayZeilen = $nurFenster ? [] : $q('SELECT d.id, d.day, d.started_at, d.ended_at, d.kind,
                            d.base_name, d.base_lat, d.base_lon, d.vehicle_name,
                            d.vehicle_typ, d.vehicle_kurz,
                            d.notes, d.deleted_at,
                            v.name AS vehicle_ref, b.name AS base_ref
                     FROM days d
                     LEFT JOIN vehicles v ON v.id = d.vehicle_id
                     LEFT JOIN bases b ON b.id = d.base_id
                     WHERE d.user_id = ?
                     ORDER BY d.day, d.started_at, d.id', [$userId]);
    $dayIds = array_map(static fn($d) => (int)$d['id'], $dayZeilen);

    $tagesCrewNach = $tagesCapsNach = $dayRefsNach = [];
    if ($dayIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT day_id, role_code, name FROM day_crew
                 WHERE day_id IN ({IDS}) ORDER BY day_id, role_code', $dayIds) as $c) {
            $tagesCrewNach[(int)$c['day_id']][(string)$c['role_code']] = $c['name'];
        }
        foreach (sql_in_bloecken($pdo,
                'SELECT day_id, capability FROM day_capabilities
                 WHERE day_id IN ({IDS}) ORDER BY day_id, capability', $dayIds) as $c) {
            $tagesCapsNach[(int)$c['day_id']][] = (string)$c['capability'];
        }
        /* Die Uhr-Kennungen samt oeffentlicher Geraetekennung. Die interne
         * device_id gilt nur in dieser Datenbank; der Name des Geraets ist der
         * portable Verweis — dieselbe Regel wie bei den Stammdaten. */
        foreach (sql_in_bloecken($pdo,
                'SELECT r.day_id, r.day_ref, dev.device_id
                 FROM day_refs r LEFT JOIN devices dev ON dev.id = r.device_id
                 WHERE r.day_id IN ({IDS}) ORDER BY r.day_id, r.id', $dayIds) as $r) {
            $dayRefsNach[(int)$r['day_id']][] = [
                'day_ref'   => (string)$r['day_ref'],
                'device_id' => $r['device_id'] !== null ? (string)$r['device_id'] : null,
            ];
        }
    }
    foreach ($dayZeilen as $d) {
        $did = (int)$d['id'];
        $d['crew']         = $tagesCrewNach[$did] ?? [];
        $d['capabilities'] = $tagesCapsNach[$did] ?? [];
        $d['refs']         = $dayRefsNach[$did]   ?? [];
        $days[] = $d;
    }

    // Standard-Markierungen liegen seit user_defaults nicht mehr an der Zeile
    // selbst, werden im Exportformat aber weiterhin als is_default-Flag je
    // Zeile abgebildet (Abwaertskompatibilitaet, s. docs/Backup-Format.md)
    $defBaseId = (int)($q('SELECT item_id FROM user_defaults WHERE user_id = ? AND kind = "base"', [$userId])[0]['item_id'] ?? 0);
    $defVehId  = (int)($q('SELECT item_id FROM user_defaults WHERE user_id = ? AND kind = "vehicle"', [$userId])[0]['item_id'] ?? 0);

    /* Standorte: mit Koordinaten. Der Name bleibt der portable
     * Schluessel, an dem die uebrigen Stammdaten haengen. */
    $bases = $q('SELECT id, name, lat, lon FROM bases WHERE user_id = ? ORDER BY name', [$userId]);
    $baseNameById = [];
    foreach ($bases as &$b) {
        $baseNameById[(int)$b['id']] = (string)$b['name'];
        $b['is_default'] = (int)$b['id'] === $defBaseId ? 1 : 0;
        unset($b['id']);
    }
    unset($b);

    /* Rettungsmittel (`aircraft`). Art, Rollen und
     * Faehigkeiten gehoeren dazu; der Standort als NAME, weil Kennungen nur in
     * dieser Datenbank gelten. */
    $vehZeilen = $q('SELECT id, name, kurz, kind, typ, base_id FROM vehicles
                     WHERE user_id = ? ORDER BY name', [$userId]);
    $vehIds = array_map(static fn($v) => (int)$v['id'], $vehZeilen);
    $vehRollen = $vehCaps = [];
    if ($vehIds) {
        foreach (sql_in_bloecken($pdo,
                'SELECT vehicle_id, role_code FROM vehicle_roles
                 WHERE vehicle_id IN ({IDS}) ORDER BY vehicle_id, role_code', $vehIds) as $r) {
            $vehRollen[(int)$r['vehicle_id']][] = (string)$r['role_code'];
        }
        foreach (sql_in_bloecken($pdo,
                'SELECT vehicle_id, capability FROM vehicle_capabilities
                 WHERE vehicle_id IN ({IDS}) ORDER BY vehicle_id, capability', $vehIds) as $c) {
            $vehCaps[(int)$c['vehicle_id']][] = (string)$c['capability'];
        }
    }
    $vehicles = [];
    foreach ($vehZeilen as $v) {
        $vid = (int)$v['id'];
        $vehicles[] = [
            'name'         => (string)$v['name'],
            /* Kurzname und Typ seit Nutzlast 10. `kurz` bleibt NULL,
             * wenn keiner gesetzt ist — ein Leerstring waere eine Angabe, wo
             * keine ist, und die Gegenseite muesste beide Faelle unterscheiden. */
            'kurz'         => $v['kurz'] !== null ? (string)$v['kurz'] : null,
            'kind'         => (string)$v['kind'],
            'typ'          => (string)$v['typ'],
            'base_ref'     => $v['base_id'] !== null ? ($baseNameById[(int)$v['base_id']] ?? null) : null,
            'roles'        => $vehRollen[$vid] ?? [],
            'capabilities' => $vehCaps[$vid] ?? [],
            'is_default'   => $vid === $defVehId ? 1 : 0,
        ];
    }

    /* Die Auswahl zentraler Standorte steht nicht in der Datei — es gibt
     * keine zentralen Standorte; der Rueckweg ueberliest das Feld in
     * alten Dateien (bis Nutzlast 11). */

    /* Die uebrigen Stammdaten tragen jetzt ihren Standort — ebenfalls als
     * Name. Ohne ihn liesse sich nach dem Einspielen nicht entscheiden, zu
     * welchem Standort eine Zielklinik gehoert, und die Auswahllisten blieben
     * leer. */
    $mitBase = static function (array $zeilen, array $namen): array {
        foreach ($zeilen as &$z) {
            $z['base_ref'] = isset($z['base_id']) && $z['base_id'] !== null
                ? ($namen[(int)$z['base_id']] ?? null) : null;
            unset($z['base_id']);
        }
        unset($z);
        return $zeilen;
    };

    /* ---- Ein Fenster von Eintraegen: hier ist die Arbeit schon getan ------
     *
     * Es traegt keinen Kopf, keine Stammdaten, keine Diensttage — nur die
     * Eintraege und, wo noetig, ihr Spurverzeichnis. `ab`, `anzahl` und
     * `gesamt` stehen dabei, damit der Browser nicht mitzaehlen muss und ein
     * abgerissener Lauf sich wieder einordnen kann. */
    if ($nurFenster) {
        $stueck = ['ab' => $ab, 'anzahl' => $bis - $ab, 'gesamt' => $eintraegeGesamt];
        /* DAS VERZEICHNIS DER SPUREN ist ein ARBEITSFELD, das nicht in die
         * Datei gehoert: Der Browser braucht die Datenbankkennung, um die
         * Stuecke zu holen (`api/backup_spuren.php`); die Datei darf sie nicht
         * tragen, denn sie gilt nur in DIESER Datenbank. Der Unterstrich ist
         * die Marke fuer Arbeitsfelder; der Backup-Lauf loescht es vor dem
         * Versiegeln, und die Containerprobe sieht nach. Im Kopf steht es
         * nicht — er traegt keine Eintraege. */
        $stueck['_spur_index'] = $spurVerzeichnis;
        $rumpf = json_encode($stueck, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return substr($rumpf, 0, -1)
             . ',"missions":[' . $missionsJson . ']'
             . ',"rest_segments":[' . $restsJson . ']}';
    }

    $data = [
        'format' => 'einsatzdoku-backup',
        /* Nutzlastversion 7: Die Datei fuehrt jetzt den Papierkorb.
         * Der Container bleibt 3 und die Signatur `EDBAK2` unveraendert — nur
         * der INHALT hat sich geaendert.
         *
         * DER SPRUNG IST KENNZEICHNUNG, KEINE SPERRE, und das gehoert deutlich
         * gesagt: api/backup_restore.php nimmt weiterhin alles ab Version 6 an,
         * und ein sehr alter AUSGELIEFERTER Stand tut das
         * auch. Der wertet `deleted_at` aber nicht aus und braechte den
         * Papierkorb einer Version-7-Datei als AKTIVEN Bestand zurueck. Das
         * laesst sich nachtraeglich nicht verhindern — eine Sperre haette in
         * jenen Staenden stehen muessen. Es steht deshalb als Warnung in
         * docs/Backup-Format.md 4.
         *
         * Umgekehrt bleiben Version-6-Dateien vollstaendig einspielbar: Sie
         * enthalten keinen Papierkorb, `deleted_at` fehlt oder ist null, und
         * der Rueckweg legt sie als aktiven Bestand an — genau wie bisher. */
        /* NUTZLAST 9: wie 8, dazu die
         * Geraete-Momentaufnahme je Einsatz und Segment und die
         * Sperrvermerke des Schnitts je Einsatz (`schnitte`).
         *
         * DIE ZAHL SAGT, WIE DIE SPUREN DRINSTEHEN, und der Rueckweg
         * entscheidet daran, welchen der beiden Wege er nimmt: 6 und 7 tragen
         * Punktlisten, 8 und 9 tragen Verweise. Das ist der Unterschied, an
         * dem es haengt — nicht die Anwesenheit eines `track`-Feldes, denn
         * eine Spur ohne Punkte saehe genauso aus. Der Vergleich dafuer steht
         * auf `>= 8` und bleibt es; eine Anhebung auf `>= 9`
         * wuerfe jede vorhandene 8er-Datei in den Punktlisten-Zweig und
         * verloere still alle Spuren.
         *
         * WARUM DIE 9 UEBERHAUPT KOMMT, wenn alle neuen Felder optional sind:
         * Die Nummer sagt einem Leser, was in der Datei stehen KANN. Und sie
         * ist der einzige Weg, `NUTZLAST_HOECHSTENS` seine Arbeit tun zu
         * lassen — eine aeltere Installation soll eine 9er-Datei ABWEISEN
         * statt sie halb einzulesen (der Kommentar dort ist genau fuer
         * diesen Sprung geschrieben).
         *
         * NUTZLAST 7 BLEIBT DIE PUNKTLISTEN-VARIANTE und traegt die neuen
         * Felder ebenfalls — sie unterscheidet sich weiter nur durch die
         * Punkte. Sie wird weiterhin GELESEN und nur noch fuer die
         * Demo-Fixture geschrieben. Mit NaDoku 1.0 faellt sie weg.
         * 
         *
         * NUTZLAST 10: wie 9, dazu am
         * Rettungsmittel `typ` und `kurz`. Beides ist optional — eine 9er-Datei
         * bleibt lesbar, `typ` fehlt dann und wird 'standard', `kurz` bleibt
         * NULL. Die Zahl steigt trotzdem, und zwar aus demselben Grund wie bei
         * der 9: Sie sagt einem Leser, was in der Datei stehen KANN, und sie
         * laesst `NUTZLAST_HOECHSTENS` arbeiten — eine aeltere Installation
         * weist eine 10er-Datei AB, statt ein Bergwacht-Rettungsmittel
         * stillschweigend als Standard-Rettungsmittel anzulegen. Genau das
         * waere der stille Schaden: Der Datensatz saehe unauffaellig aus.
         *
         * DER SPURVERGLEICH BLEIBT AUF `>= 8`. Er entscheidet, ob Punktlisten
         * oder Verweise in der Datei stehen, und daran aendert die 10 nichts —
         * dieselbe Falle, vor der schon der Absatz zur 9 warnt.
         *
         * 11: Die Notizen des Einsatzes sind aus der Spalte
         * `missions.notes` in den verschluesselten `pat`-Block gewandert. Eine
         * 11er-Datei traegt sie deshalb NUR dort — eine aeltere Installation,
         * die sie in der Spalte sucht, faende nichts und meldete trotzdem
         * Erfolg. Das ist genau der stille Schaden, gegen den diese Zahl da
         * ist: Die alte Installation weist die Datei jetzt ab.
         *
         * Umgekehrt bleibt der Weg offen: Eine 10er-Datei traegt den Klartext
         * in der Spalte, und der Einspielweg schreibt sie weiter (siehe
         * `$extraCols` weiter unten) — der Anhebelauf holt sie danach in den
         * Blob.
         *
         * 12: Die Auswahl zentraler Standorte in
         * `stammdaten` faellt weg, und ein Tag mit einem Rettungsmittel NUR FUER DIESEN TAG
         * traegt die Rollen seiner Betriebsart in `crew`. Eine
         * aeltere Installation laese beides ohne Schaden — ihr fehlte ein
         * Feld, das sie ueberliest, und die Rollen blendete sie aus. Die Zahl
         * steigt trotzdem: Sie sagt, was in der Datei stehen KANN, und dahinter
         * liegt kein Rueckweg — aelterer Code laeuft auf diesem Schema
         * nicht (Hauptstufe). Umgekehrt bleiben 6 bis 11 lesbar;
         * die Standortauswahl darin wird still ueberlesen.
         *
         * 13: Orte der Phasen und Reanimation
         * legt der Browser offen in den Kern, die Spurteile tragen
         * Punktlisten. Der Rueckweg liest nur 13; eine Punktlisten-Variante 7
         * fuer die Demo-Fixture gibt es nicht — die Fixture
         * liegt eingefroren im Repositorium. Was oben zu 6 bis 12 steht, ist
         * Geschichte. */
        'version' => EDBAK_NUTZLAST,
        'created_at' => gmdate('c'),
        'app' => 'einsatzdoku-notarzt',
        'user' => ['email' => $u['email'], 'name' => $u['name']],
        /* Pruefsumme des Inhaltsschluessels dieses Kontos.
         *
         * Sie steht hier, damit sich beim Wiedereinspielen entscheiden laesst,
         * ob ein in der Datei MITGEFUEHRTER Chiffretext (Einsaetze, die beim
         * Sichern nicht entschluesselt werden konnten) in diesem Konto
         * ueberhaupt lesbar waere. Ohne diese Angabe bliebe nur Raten.
         *
         * Sie verraet nichts: Der Inhaltsschluessel ist 256 Bit Zufall, aus
         * seinem Hashwert nicht zurueckrechenbar. Bei sehr alten Konten kann
         * sie fehlen (null) — dann ist die Zuordnung eben
         * unbekannt, und das Einspielen sagt das auch. */
        'pat_key_check' => $u['pat_key_check'] ?? null,
        'stammdaten' => [
            'bases'           => $bases,
            'vehicles'        => $vehicles,
            'crew_presets'    => $mitBase($q('SELECT role_code, name, base_id FROM crew_presets
                                              WHERE user_id = ? ORDER BY role_code, name', [$userId]), $baseNameById),
            'bw_units'        => $mitBase($q('SELECT name, base_id FROM bw_units
                                              WHERE user_id = ? ORDER BY name', [$userId]), $baseNameById),
            'resources'       => $mitBase($q('SELECT name, base_id FROM resources
                                              WHERE user_id = ? ORDER BY name', [$userId]), $baseNameById),
            'transport_dests' => $mitBase($q('SELECT name, lat, lon, base_id FROM transport_dests
                                              WHERE user_id = ? ORDER BY name', [$userId]), $baseNameById),
        ],
        'days' => $days,
        /* `missions` und `rest_segments` stehen hier NICHT — sie kommen in
         * den Fenstern (oben). */
    ];

    /* KARTEILEICHEN UNTER DEN SPERRVERMERKEN — nur, wenn es welche gab
     * (Nutzlast 9). Ein Vermerk, dessen Quelle sich nicht mehr aufloesen
     * laesst, wird nicht geschrieben; dass er da war, gehoert trotzdem in die
     * Datei. Dasselbe Muster wie bei `spur_karte` im Rueckweg: Ein Feld, das
     * im Regelfall fehlt, ist kein Ballast — und ein Feld, das immer auf 0
     * stuende, waere eins.
     *
     * Wer das hier liest, hat einen Befund: Entweder hat ein Loeschweg
     * `schnitte_loeschen_quelle()` uebergangen, oder jemand hat von Hand in
     * `track_cuts` geschrieben. Beides gehoert nachgesehen. */
    if ($schnitteWaisen > 0) { $data['schnitte_verwaist'] = $schnitteWaisen; }

    /* Der Kopf endet auf `}`; die schliessende Klammer wird abgeschnitten,
     * die Zahl der Eintraege angehaengt und sie wieder gesetzt. `$data`
     * traegt immer mindestens `format` und `version`, ist also nie `{}` —
     * die Pruefung darunter sagt es trotzdem, statt sich darauf zu
     * verlassen. */
    $kopf = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($kopf) || substr($kopf, -1) !== '}' || strlen($kopf) < 3) {
        throw new RuntimeException('Der Kopf des Backups liess sich nicht bauen.');
    }
    /* NUR DER KOPF: ohne Eintraege, dafuer mit ihrer Zahl. Der Browser
     * braucht sie, um die Fenster zu planen — und die Teilezahl muss
     * feststehen, bevor das erste Teil versiegelt wird (die Zusatzdaten
     * tragen `<nr>/<gesamt>`). Das Fenster ist oben schon zurueckgegeben;
     * hier kommt nur noch der Kopf an. */
    return substr($kopf, 0, -1)
         . ',"eintraege_gesamt":' . $eintraegeGesamt . '}';
}

/* ======================= Import ======================= */

/**
 * Leitet Herkunft (origin) und Bearbeitungsstatus (edited) fuer einen
 * wiederherzustellenden Einsatz ab. Enthaelt der Datensatz gueltige Werte,
 * werden diese uebernommen (Formatversion 4). Fehlen sie (Version <= 3),
 * gilt die Ableitung aus dem `client_ref`-Praefix; `edited` = 1 nur, wenn
 * der Dateischluessel `manual` (Spalte `uhr_gesperrt`) 1 ist und kein
 * solches Praefix vorliegt.
 *
 * DIE REGEL STEHT NICHT HIER, sondern EINMAL, in `herkunft_ableiten()`
 * (geraete_lib.php); die Migration 2026_09_04 spiegelt sie in SQL und sagt
 * das dort.
 *
 * DIE GERAETEART BLEIBT HIER NULL, und das ist kein Versehen: Die Sicherung
 * traegt `device_id` nicht (Backup-Format 4). Was sie ueber das Geraet weiss,
 * steht als Momentaufnahme in der Datei — nicht in einem Verweis, aus dem
 * sich eine Art nachschlagen liesse. Der Rueckfall ohne Praefix ist damit
 * `watch`, genau wie bisher.
 */
/**
 * Die Geraeteart auf dem Rueckweg — dieselbe Verengung wie beim Koppeln.
 * 
 *
 * WAS FALSCH WAR. `geraete_lib.php` verengt beim Koppeln auf die drei Werte
 * aus `GERAET_ARTEN`: Was nicht dazugehoert, wird `NULL`, und
 * `docs/Technik.md` fuehrt das ausdruecklich als Zusage. Auf dem Rueckweg
 * der Konto-Sicherung galt sie nicht: `pruef_text()` mit `GERAET_MAX_ART`
 * prueft allein die LAENGE, und jede Zeichenkette bis 16 Zeichen ging
 * durch. Beim Nachbarfeld `origin` war es anders (es wird gegen
 * `HERKUNFT_WERTE` gehalten); die Asymmetrie stand ohne Begruendung da.
 *
 * WARUM DAS ZAEHLT, obwohl es heute nirgends auffaellt: Genau diese Spalte
 * soll eine Auswertung zaehlen („Herkunft je Einsatz"). Eine
 * Zaehlung, die man durch das Bearbeiten der EIGENEN Sicherung verunreinigen
 * kann, taugt nicht als Betriebszahl. Ein Sicherheitsproblem ist es nicht.
 *
 * ES WIRD GEMELDET, NICHT VERSCHWIEGEN. Die Pruefliste bekommt eine Zeile —
 * wer eine Sicherung einspielt, in der eine Geraeteart steht, die es nicht
 * gibt, soll erfahren, dass sie nicht uebernommen wurde. Ein stilles `NULL`
 * saehe aus wie „stand nicht drin".
 */
function edbak_geraet_art($wert, string $feld, ?Pruefliste $pruef = null): ?string
{
    $art = pruef_text($wert, GERAET_MAX_ART, $feld, $pruef);
    if ($art === null) { return null; }
    $art = strtolower($art);
    if (!in_array($art, GERAET_ARTEN, true)) {
        $pruef?->melde($feld, 'keine bekannte Geräteart — als „unbekannt" übernommen');
        return null;
    }
    return $art;
}

function edbak_origin_edited(array $m): array {
    $ref = (string)($m['client_ref'] ?? '');

    if (isset($m['origin']) && in_array($m['origin'], HERKUNFT_WERTE, true)) {
        $origin = $m['origin'];
    } else {
        $origin = herkunft_ableiten($ref, null);
    }

    if (isset($m['edited'])) {
        $edited = (int)$m['edited'];
    } else {
        /* `cut-` FEHLT HIER MIT ABSICHT. Ein geschnittener Einsatz traegt
         * die Uhr-Sperre und wuerde nach dieser Regel als "bearbeitet" gelten,
         * obwohl ihn niemand angefasst hat. Der Zweig greift aber nur fuer
         * Dateien der Formatversion <= 3, und die sind aelter als der Schnitt
         * — es kann keine solche Datei geben. Die Zeile
         * einzubauen hiesse, toten Code gegen einen unmoeglichen Fall zu
         * stellen; der Hinweis genuegt. */
        $edited = ((int)($m['manual'] ?? 0) === 1
                   && !str_starts_with($ref, 'man-')
                   && !str_starts_with($ref, 'imp-')) ? 1 : 0;
    }

    return ['origin' => $origin, 'edited' => $edited];
}

/**
 * Klartext-Ortsdaten in einer Nutzlast des Browsers —
 * der erste Fund als Pfad, sonst null. Dieselbe Liste wie im Eingang der
 * Geraete und im CSV-Import: Phasenorte, Reanimation, Transportziel samt
 * Koordinaten, Ortshoehe und Punktlisten. Ein leerer Text gilt nicht als
 * Fund; aeltere Dateien tragen `""` statt `null`.
 */
function edbak_klartext_fund(array $data): ?string
{
    $belegt = static fn($v): bool => $v !== null && $v !== '' && $v !== [];
    foreach ((array)($data['missions'] ?? []) as $i => $m) {
        if (!is_array($m)) { continue; }
        foreach (['transport_dest', 'dest_lat', 'dest_lon', 'site_ele_m', 'resus', 'track'] as $f) {
            if ($belegt($m[$f] ?? null)) { return "missions[$i].$f"; }
        }
        foreach ((array)($m['phases'] ?? []) as $p) {
            if (is_array($p) && ($belegt($p['lat'] ?? null) || $belegt($p['lon'] ?? null))) {
                return "missions[$i].phases";
            }
        }
    }
    foreach ((array)($data['rest_segments'] ?? []) as $i => $r) {
        if (is_array($r) && $belegt($r['track'] ?? null)) { return "rest_segments[$i].track"; }
    }
    return null;
}

/**
 * Der Satz zu den verworfenen Werten eines Einspielberichts — leer, wenn es
 * keine gab.
 *
 * `edbak_restore()` nennt in `rejected` jede Beanstandung der Prüfschicht
 * („feld: grund" → Anzahl). Als Rohtext im Bericht und blau
 * waere das zu wenig: Eine abgewiesene Ortshülle nimmt Orte und
 * Reanimation des Einsatzes mit, und das soll dastehen. Das
 * Gegenstück im Browser ist `verworfenSatz()` in `einstellungen.php`.
 */
function edbak_verworfen_satz(array $bericht): string
{
    $teile = [];
    $summe = 0;
    foreach ((array)($bericht['rejected'] ?? []) as $ursache => $n) {
        $n = (int)$n;
        if ($n <= 0) { continue; }
        $teile[] = $ursache . ' (' . $n . '×)';
        $summe  += $n;
    }
    if ($teile === []) { return ''; }
    return ' ACHTUNG: Verworfen hat die Prüfung ' . $summe
         . ($summe === 1 ? ' Wert' : ' Werte') . ' — ' . implode(', ', $teile)
         . '. Diese Angaben fehlen jetzt im Konto.';
}

/** @return array Zusammenfassung (Zaehler) */
/**
 * DREI BETRIEBSARTEN, EINE FUNKTION — dieselbe Linie wie bei
 * `edbak_build()`:
 *
 *   $dayMap === null, `days` in $data       alles auf einmal (Nutzlast 6/7)
 *                                           ODER nur der Kopf, wenn $data
 *                                           keine Eintraege traegt
 *   $dayMap !== null                        ein Fenster von Eintraegen; die
 *                                           Zuordnung Datei-Diensttag ->
 *                                           angelegter Diensttag kommt mit
 *
 * WARUM NICHT ZWEI FUNKTIONEN. Weil dann zwei Stellen die D1-Regeln, den
 * Papierkorb, die Pruefschicht und die Wiedererkennung kennen muessten — und
 * die naechste Aenderung landete in einer davon.
 *
 * DIE MITGEGEBENE ZUORDNUNG WIRD GEPRUEFT, nicht geglaubt. Sie kommt vom
 * Browser, und ein Browser kann alles schicken: Ohne Pruefung liesse sich ein
 * Einsatz an den Diensttag eines FREMDEN Kontos haengen. Geprueft wird gegen
 * `user_id`, in einer Abfrage; was nicht diesem Konto gehoert, faellt heraus,
 * und die betroffenen Eintraege werden uebersprungen und gezaehlt.
 *
 * ZWEI HERKUENFTE DER ORTSDATEN (`$ortsdaten`):
 *
 *   'browser'  Der Browser hat Orte, Reanimation und Spur schon verschlossen
 *              (Einsatzhuelle `ort_huelle`; Stuecke ueber die Spurteile) und
 *              Transportziel und Ortshoehe in den `pat_blob` gelegt. Eine
 *              Nutzlast mit Klartext-Ortsdaten wird ABGEWIESEN
 *              (`DomainException('klartext: …')`, der Endpunkt antwortet 400),
 *              vor jedem Schreiben — wie `ingest.php` und der CSV-Import.
 *              Auch das direkte Einspielen einer Kontosicherung durch
 *              die Verwaltung geht diesen Weg: Ihre Pakete
 *              tragen Huellen und Stuecke, wie sie liegen.
 *   'server'   NUR NOCH DER DEMO-RESET: Die Fixture liegt im
 *              Klartext im Repositorium (Nutzlast 7, mit Punktlisten), und
 *              der Server verschliesst Orte, Reanimation und Spur mit dem
 *              oeffentlichen Schluessel des Kontos.
 *              Transportziel und Ortshoehe legt `$blobZiel` in den Blob —
 *              ohne ihn ist dieser Weg nicht erlaubt: Es gibt keine
 *              Spalten, in denen sie liegen bleiben koennten.
 *
 * `$blobZiel` (nur der Demo-Reset): Wer den Inhaltsschluessel des Kontos
 * hat, kann Transportziel und Ortshoehe in den Blob legen. Der Rueckruf
 * bekommt den `pat_blob` der Datei (oder null) und die Angaben und gibt den
 * neuen Blob zurueck. Das Demo-Konto ist das einzige, dessen Passwort der
 * Server kennt — es steht im Handbuch.
 */
function edbak_restore(int $userId, array $data, ?array $dayMap = null,
                       string $ortsdaten = 'browser', ?Closure $blobZiel = null,
                       bool $offenZurueck = false): array {
    if (!in_array($ortsdaten, ['browser', 'server'], true)
        || ($ortsdaten === 'server' && $blobZiel === null)) {
        throw new InvalidArgumentException('ortsdaten: ' . $ortsdaten);
    }
    if ($ortsdaten === 'browser') {
        $fund = edbak_klartext_fund($data);
        if ($fund !== null) { throw new DomainException('klartext: ' . $fund); }
    }
    $pdo = db();
    require_once __DIR__ . '/geraetehuelle_lib.php';
    require_once __DIR__ . '/kontopaar_lib.php';
    /* Der oeffentliche Schluessel des Kontos — erst geholt, wenn etwas zu
     * verschliessen ist: Ein Konto ohne Paar spielt eine Sicherung ohne
     * Ortsdaten weiter ein. */
    $sec1 = null;
    $sec1Holen = static function () use ($pdo, $userId, &$sec1): string {
        if ($sec1 === null) {
            $b64 = kp_fuer_geraet($pdo, $userId);     // SEC1 als Base64, wie fuer das Geraet
            $sec1 = $b64 === null ? null : base64_decode($b64);
            if ($sec1 === null) {
                throw new DomainException('kein_paar: Das Zielkonto hat noch kein Schlüsselpaar. '
                    . 'Es entsteht beim ersten Entsperren nach einer Anmeldung; danach lässt '
                    . 'sich die Sicherung einspielen.');
            }
        }
        return $sec1;
    };
    $stats = ['missions' => 0, 'missions_skipped' => 0, 'rests' => 0, 'rests_skipped' => 0,
              'days' => 0, 'stammdaten' => 0, 'stammdaten_skipped' => 0,
              /* Wie viel davon in den PAPIERKORB gegangen ist. Die
               * Zahlen stecken in den drei Zaehlern darueber MIT drin — sie
               * sagen nicht "zusaetzlich", sondern "davon". */
              'papierkorb' => ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0],
              /* DIE SPERRVERMERKE DES SCHNITTS (Nutzlast 9).
               *
               * DREI AUSGAENGE UND NICHT ZWEI, weil sie Verschiedenes
               * bedeuten: `uebersprungen` heisst „der Einsatz stand schon da"
               * — dann stehen seine Vermerke auch schon, und ein Restore darf
               * keine Sperre wiederbeleben, die jemand bewusst
               * zurueckgenommen hat. `verworfen` heisst dagegen „konnte
               * nicht", und das ist ein Befund. Beides in eine Zahl zu legen
               * waere derselbe Fehler wie bei den uebersprungenen Einsaetzen:
               * nicht deutbar.
               *
               * Der Block bleibt bis zum Schluss auf null, wenn die Datei
               * keine Vermerke trug — die Oberflaeche zeigt die Zeile dann
               * gar nicht. */
              'schnitte' => ['uebernommen' => 0, 'uebersprungen' => 0, 'verworfen' => 0]];

    /* URSACHEN GETRENNT ZAEHLEN.
     * "40 uebersprungen" ist nicht deutbar: Es kann heissen "alles war schon
     * da" (gut) oder "alles war kaputt" (schlecht). Genau diese Unterscheidung
     * braucht, wer eine Wiederherstellung beurteilen muss. */
    $grund = ['bereits_vorhanden' => 0, 'datum_oder_zeit' => 0, 'aufbau' => 0,
              'tag_im_papierkorb' => 0, 'tag_unbrauchbar' => 0,
              /* NEU: Einsaetze und Ruhesegmente, deren Diensttag aus
               * der Datei uebersprungen wurde. Sie liefen bisher unter
               * `datum_oder_zeit` — irrefuehrend, denn an ihrem Datum ist
               * nichts auszusetzen; es fehlt ihnen der Tag. Wer die Meldung
               * liest, suchte den Fehler an der falschen Stelle. */
              'tag_uebersprungen' => 0,
              /* Die vier Gruende, aus denen ein Sperrvermerk verworfen wird
               * (Nutzlast 9). Sie stehen im selben Topf wie die uebrigen,
               * damit die Oberflaeche sie ohne zweiten Weg anzeigen kann;
               * `GRUND_TEXT` in einstellungen.php uebersetzt sie. */
              'schnitt_ohne_ziel' => 0, 'schnitt_ohne_quelle' => 0,
              'schnitt_werte' => 0, 'schnitt_aufbau' => 0,
              /* NEU: Die Einsaetze eines Datei-Diensttags
               * liegen im Ziel an MEHREREN verschiedenen Tagen. Schritt 1 der
               * Wiedererkennung liefert dann kein Ergebnis, der Fingerabdruck
               * entscheidet — und dass geraten werden musste, gehoert in die
               * Rueckmeldung statt in die Stille. Zaehlt Diensttage, nicht
               * Einsaetze. */
              'tag_mehrdeutig' => 0];

    /* EIN Loeschzeitpunkt fuer den ganzen Lauf.
     *
     * Aus der Datei wird der ZUSTAND uebernommen (geloescht ja/nein), nicht
     * der Zeitpunkt: Der Eintrag entsteht in dieser Installation neu und
     * bekommt volle TRASH_DAYS Tage — dieselbe Linie wie bei `herkunft`.
     *
     * Der Gegenentwurf waere, den Zeitpunkt aus der Datei zu uebernehmen.
     * Dann koennte ein aelteres Backup Eintraege mitbringen, deren Frist
     * laengst abgelaufen ist, und der naechste Aufraeumjob loeschte sie
     * endgueltig — eine Wiederherstellung, die einspielt und Stunden spaeter
     * selbst wieder entfernt.
     *
     * In PHP gerechnet und nicht als UTC_TIMESTAMP() ins SQL geschrieben,
     * damit ALLE Eintraege eines Laufs denselben Zeitpunkt tragen (die
     * Verbindung steht auf UTC, siehe db.php). Das ist auch die ehrlichere
     * Angabe: Sie sind in EINEM Vorgang entstanden. */
    $loeschZeit = gmdate('Y-m-d H:i:s');

    // Diensttage im Papierkorb DES ZIELKONTOS einmal vorab feststellen (D1).
    $tageImPapierkorb = [];
    $qT = $pdo->prepare('SELECT day FROM days WHERE user_id = ? AND deleted_at IS NOT NULL');
    $qT->execute([$userId]);
    foreach ($qT->fetchAll(PDO::FETCH_COLUMN) as $dTrash) {
        $tageImPapierkorb[(string)$dTrash] = true;
    }
    $pruef = new Pruefliste();
    /* Die Hoehe des Einsatzorts traegt der `pat_blob` (`ortshoehe`). */

    /* DIE SPURKARTE.
     *
     * Nutzlast 8 traegt keine Punktlisten, sondern je Spur eine `spur_ref`.
     * Der Browser schickt die Blobs danach in eigenen Anfragen — und braucht
     * dafuer die Kennung, unter der der Datensatz HIER angelegt wurde. Die
     * kennt nur dieser Lauf.
     *
     * Sie steht bewusst nicht in `$stats`: `$stats` ist die Rueckmeldung an
     * die Nutzerin und wird angezeigt; das hier ist eine Arbeitsangabe. */
    $spurKarte = [];

    /* DIE KENNUNGSKARTE (Nutzlast 9).
     *
     * `client_ref` aus der Datei -> Kennung im Zielkonto, je Art. Sie ist der
     * Anker, mit dem der dritte Durchgang die QUELLE eines Sperrvermerks
     * wiederfindet: In der Datei steht `quelle_ref`, in der Datenbank eine
     * frisch vergebene Nummer, und dazwischen liegt genau diese Karte.
     *
     * SIE FUEHRT AUCH DIE UEBERSPRUNGENEN. Dieselbe Begruendung wie bei der
     * Spurkarte eine Zeile darueber: Ein Einsatz oder Segment, das im Ziel
     * schon steht, ist als Quelle genauso gueltig — und ein zweiter Anlauf
     * nach einem Abbruch faende sonst gar nichts mehr.
     *
     * NUR NICHTLEERE KENNUNGEN. Fehlt die `client_ref` in der Datei, vergibt
     * der Rueckweg unten eine Ersatzkennung (`bak-…`); der Schluessel waere
     * dann fuer alle solchen Eintraege derselbe leere String, und zwei
     * Eintraege ueberschrieben einander in der Karte. Ein Vermerk landete
     * danach an der falschen Quelle — ohne Meldung, weil die Aufloesung ja
     * „gelungen" waere. Wer keine Kennung hat, kann auch keine Quelle sein. */
    $refKarte = ['mission' => [], 'rest' => []];
    /* EIN SCHLUESSEL, EINE SCHREIBWEISE. Der Rueckweg schneidet die Kennung
     * beim Anlegen ueber `pruef_text()` zu (Leerraum weg); die Karte muss
     * dieselbe Schreibweise fuehren, sonst findet der dritte Durchgang einen
     * Eintrag nicht wieder, den es gibt. */
    $refSchluessel = static fn($ref): string => is_string($ref) ? trim($ref) : '';
    $merke = static function (string $art, $ref, int $id)
                             use (&$refKarte, $refSchluessel): void {
        $k = $refSchluessel($ref);
        if ($k === '') { return; }
        $refKarte[$art][$k] = $id;
    };
    /* Welche Einsaetze in DIESEM Lauf NEU entstanden sind. Der dritte
     * Durchgang unterscheidet daran „uebersprungen" von „uebernommen". */
    $neueEinsaetze = [];

    $nutzlast = (int)($data['version'] ?? 0);
    /* DER BROWSERWEG NIMMT NUR NUTZLAST 13. Die Endpunkte
     * weisen eine aeltere Datei schon vorher ab (`409 version_alt`); diese
     * Zeile ist die Schranke, die nicht davon abhaengt. Der Serverweg ist
     * nur noch der Demo-Reset mit seiner Nutzlast 7 aus dem Repositorium. */
    if ($ortsdaten === 'browser' && $nutzlast < EDBAK_NUTZLAST) {
        throw new DomainException('version_alt: Nutzlast ' . $nutzlast);
    }
    $mitVerweisen = $nutzlast >= 8;

    /* VERSCHACHTELUNGSFAEHIG.
     *
     * Diese Funktion hat ihre Transaktion bisher bedingungslos geoeffnet. Das
     * ging gut, solange sie genau einen Aufrufer hatte — api/backup_restore.php,
     * das nichts weiter tut. Der Demo-Reset (demo_lib.php) muss aber MEHR in
     * dieselbe Klammer nehmen: Kontomaterial, Geraete und Bestand. Zerfiele das
     * in mehrere Transaktionen, koennte ein Fehler in der Mitte ein Konto mit
     * halbem Bestand hinterlassen — und ausgerechnet der Reset laeuft
     * unbeaufsichtigt, huckepack auf einer Anfrage des Demo-Kontos.
     *
     * PDO kennt keine echten verschachtelten Transaktionen; ein zweites
     * beginTransaction() wirft. Deshalb wird geprueft, ob schon eine laeuft,
     * und Commit wie Rollback bleiben dem ueberlassen, der sie geoeffnet hat.
     * Ein Fehler kommt als Ausnahme weiterhin heraus — der aeussere Aufrufer
     * setzt zurueck. */
    $eigeneTransaktion = !$pdo->inTransaction();
    if ($eigeneTransaktion) { $pdo->beginTransaction(); }
    try {
        /* Stammdaten (INSERT IGNORE ueber die Unique-Schluessel). Was nicht
         * angelegt werden kann — kein Standort, eine Pruefung schlaegt an —,
         * wird uebersprungen und gezaehlt (Backup-Format 6.3/8). Bis Web
         * 20.47.0 zaehlte dort auch „systemweit schon vorhanden". */
        $sd = $data['stammdaten'] ?? [];
        /* Vorbereitete Anweisung, nicht eingesetzter Wert.
         *
         * $userId ist hier ein int aus der Sitzung — hineingeschmuggelt
         * werden kann nichts. Der Befund zielt auch nicht darauf: Diese vier
         * Stellen waren die EINZIGEN im Projekt, an denen ein Wert im
         * SQL-Text stand. Eine Regel mit vier Ausnahmen ist keine Regel mehr;
         * beim naechsten Umbau ist nicht mehr zu sehen, welche Ausnahme
         * geprueft wurde und welche nur so aussieht. */
        $hd = $pdo->prepare("SELECT COUNT(*) FROM user_defaults WHERE user_id = ? AND kind = 'base'");
        $hd->execute([$userId]);
        $hasDefBase = (bool)$hd->fetchColumn();
        $newDefBaseName = null;
        /* DIE STAMMDATEN GEHEN DURCH DIE PRUEFSCHICHT.
         * Gingen Name und Koordinaten der Standorte, Namen der Besatzung,
         * Bereitschaften, Ressourcen und Zielkliniken roh in die Datenbank,
         * braeche ein Name ueber der Spaltenlaenge im strikten SQL-Modus den
         * ganzen Lauf mit 500 ab, im laxen wuerde er still gekappt und passte danach nicht mehr
         * zum `base_ref` der Rettungsmittel. Jetzt: Leerraum weg, Laenge wie
         * im Schema (`SD_NAME_MAX`, `SD_ZIEL_MAX`), Koordinaten als Zahl im
         * Bereich; was nicht taugt, wird gemeldet. Ein Eintrag ohne Namen
         * wird uebersprungen und gezaehlt. Dieselbe Schreibweise gilt fuer
         * die Verweise (`$baseIdByName`), sonst faende ein gekuerzter
         * Standort seine Rettungsmittel nicht wieder. */
        foreach (($sd['bases'] ?? []) as $b) {
            if (!is_array($b)) { $stats['stammdaten_skipped']++; continue; }
            $name = pruef_text($b['name'] ?? null, SD_NAME_MAX, 'bases.name', $pruef);
            if ($name === null) { $stats['stammdaten_skipped']++; continue; }
            // Koordinaten kommen mit; sie duerfen leer bleiben.
            $st = $pdo->prepare('INSERT IGNORE INTO bases (user_id, name, lat, lon) VALUES (?,?,?,?)');
            $st->execute([$userId, $name,
                          pruef_breite($b['lat'] ?? null, 'bases.lat', $pruef),
                          pruef_laenge($b['lon'] ?? null, 'bases.lon', $pruef)]);
            $stats['stammdaten'] += $st->rowCount();
            if (!$hasDefBase && (int)($b['is_default'] ?? 0)) { $newDefBaseName = $name; }
        }
        /* Standortkennung zum Namen. Der Name ist der portable Schluessel:
         * Die Kennung aus der Backup-Datei gilt nur in der Datenbank, aus der
         * sie stammt. Gesucht wird unter den eigenen Standorten. */
        $baseIdByName = function (?string $name) use ($pdo, $userId): ?int {
            $name = pruef_text($name, SD_NAME_MAX);       // wie beim Anlegen oben
            if ($name === null) { return null; }
            $x = $pdo->prepare('SELECT id FROM bases WHERE name = ? AND user_id = ?');
            $x->execute([$name, $userId]);
            $id = $x->fetchColumn();
            return $id === false ? null : (int)$id;
        };

        /* DIE AUSWAHL ZENTRALER STANDORTE (bis Nutzlast 11) WIRD
         * UEBERLESEN, nicht gelesen: Die Tabelle, in die sie gehoerte, gibt
         * es nicht. Eine Schleife hier liefe gegen sie mit 42S02 — in jeder
         * Wiederherstellung, jeder
         * Kontosicherung und jedem Demo-Reset. Toleranz, die mit NaDoku 1.0
         * faellt. */

        /* Rettungsmittel samt Typ, Art, Rollen und Faehigkeiten.
         *
         * UEBER DIESELBE PRUEFUNG WIE DIE FORMULARE
         * (`pruef_rettungsmittel()`, validate_lib.php) — nicht ueber eine
         * duennere eigene Fassung. Sonst gaelte die Reihenfolge, die der Kopf
         * dieser Datei beklagt: je unvertrauenswuerdiger die Quelle, desto
         * weniger Pruefung.
         *
         * OHNE STANDORT WIRD ANGELEGT, WENN DER TYP ES ERLAUBT: Fuer die drei
         * Typen ausser 'standard' ist ein Rettungsmittel ohne Standort ein
         * gueltiger Zustand. Ein Standard-Rettungs-
         * mittel ohne aufloesbaren Standort wird weiterhin uebersprungen — die
         * Pruefschicht sagt das, nicht diese Schleife.
         *
         * DIE URSACHEN WERDEN GEZAEHLT, nicht stillschweigend uebergangen: Die
         * `Pruefliste` sammelt sie, und `stammdaten_skipped` bleibt die Zahl,
         * die der Bericht nennt. */
        $hv = $pdo->prepare("SELECT COUNT(*) FROM user_defaults WHERE user_id = ? AND kind = 'vehicle'");
        $hv->execute([$userId]);
        $hasDefVeh = (bool)$hv->fetchColumn();
        $newDefVehName = null;
        foreach (($sd['vehicles'] ?? []) as $v) {
            $geprueft = pruef_rettungsmittel([
                'name'    => $v['name'] ?? null,
                'kurz'    => $v['kurz'] ?? null,
                'typ'     => $v['typ']  ?? null,
                'kind'    => $v['kind'] ?? null,
                'base_id' => $baseIdByName(isset($v['base_ref']) ? (string)$v['base_ref'] : null),
                'roles'   => $v['roles'] ?? [],
                'caps'    => $v['capabilities'] ?? [],
            ], $pruef);
            $rm = $geprueft['daten'];
            if ($rm === null) { $stats['stammdaten_skipped']++; continue; }
            $name = $rm['name'];
            $st = $pdo->prepare('INSERT IGNORE INTO vehicles (user_id, base_id, name, kurz, kind, typ)
                                 VALUES (?,?,?,?,?,?)');
            $st->execute([$userId, $rm['base_id'], $name, $rm['kurz'], $rm['kind'], $rm['typ']]);
            $stats['stammdaten'] += $st->rowCount();

            $x = $pdo->prepare('SELECT id FROM vehicles WHERE user_id = ? AND name = ?');
            $x->execute([$userId, $name]);
            $vid = $x->fetchColumn();
            if ($vid !== false) {
                $insR = $pdo->prepare('INSERT IGNORE INTO vehicle_roles (vehicle_id, role_code)
                                       VALUES (?,?)');
                foreach ($rm['roles'] as $rc) { $insR->execute([(int)$vid, $rc]); }
                /* Was der Bestand an Faehigkeiten fuehren darf, entscheidet
                 * `veh_caps_erlaubt()` — Typ und Betriebsart zusammen (mit der
                 * benannten Ausnahme fuer den Typ Bergwacht). Was die
                 * Regel nicht zulaesst, wird verworfen statt gespeichert: sonst
                 * traege der Bestand einen Zustand, den die Oberflaeche nicht
                 * herstellen kann. Das Verwerfen geschieht in
                 * `pruef_rettungsmittel()`; hier steht nur noch das Schreiben,
                 * und deshalb ERBT dieser Weg jede Aenderung der Regel, statt
                 * sie zu kopieren. */
                if ($rm['caps']) {
                    $insC = $pdo->prepare('INSERT IGNORE INTO vehicle_capabilities
                                           (vehicle_id, capability) VALUES (?,?)');
                    foreach ($rm['caps'] as $cap) { $insC->execute([(int)$vid, $cap]); }
                }
            }
            if (!$hasDefVeh && (int)($v['is_default'] ?? 0)) { $newDefVehName = $name; }
        }
        // is_default-Flags aus dem Backup in user_defaults schreiben (nur wenn
        // noch kein Default des Typs existiert — bestehende Semantik $hasDefBase/$hasDefAc)
        if ($newDefBaseName !== null) {
            $x = $pdo->prepare('SELECT id FROM bases WHERE user_id = ? AND name = ?');
            $x->execute([$userId, $newDefBaseName]);
            if ($bid = $x->fetchColumn()) {
                $pdo->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"base",?)
                               ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')->execute([$userId, $bid]);
            }
        }
        if ($newDefVehName !== null) {
            $x = $pdo->prepare('SELECT id FROM vehicles WHERE user_id = ? AND name = ?');
            $x->execute([$userId, $newDefVehName]);
            if ($vid = $x->fetchColumn()) {
                $pdo->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"vehicle",?)
                               ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')->execute([$userId, $vid]);
            }
        }
        /* Die uebrigen Stammdaten haengen jetzt an einem Standort. Ohne
         * ihn wird nicht angelegt — die Spalte traegt nach der Nachbearbeitung
         * NOT NULL, und ein Eintrag ohne Standort erschiene in keiner
         * Auswahlliste. Der Fall wird gezaehlt. */
        foreach (($sd['crew_presets'] ?? []) as $c) {
            $rc = (string)($c['role_code'] ?? $c['role'] ?? '');
            if (!array_key_exists($rc, CREW_ROLES)) { continue; }
            $bid = $baseIdByName(isset($c['base_ref']) ? (string)$c['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $cName = pruef_text($c['name'] ?? null, SD_NAME_MAX, 'crew_presets.name', $pruef);
            if ($cName === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO crew_presets
                                 (user_id, base_id, role_code, name) VALUES (?,?,?,?)');
            $st->execute([$userId, $bid, $rc, $cName]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['bw_units'] ?? []) as $w) {
            $bid = $baseIdByName(isset($w['base_ref']) ? (string)$w['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $wName = pruef_text($w['name'] ?? null, SD_NAME_MAX, 'bw_units.name', $pruef);
            if ($wName === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO bw_units (user_id, base_id, name) VALUES (?,?,?)');
            $st->execute([$userId, $bid, $wName]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['resources'] ?? []) as $r) {
            $bid = $baseIdByName(isset($r['base_ref']) ? (string)$r['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $rName = pruef_text($r['name'] ?? null, SD_NAME_MAX, 'resources.name', $pruef);
            if ($rName === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO resources (user_id, base_id, name) VALUES (?,?,?)');
            $st->execute([$userId, $bid, $rName]);
            $stats['stammdaten'] += $st->rowCount();
        }
        foreach (($sd['transport_dests'] ?? []) as $t) {
            $bid = $baseIdByName(isset($t['base_ref']) ? (string)$t['base_ref'] : null);
            if ($bid === null) { $stats['stammdaten_skipped']++; continue; }
            $tName = pruef_text($t['name'] ?? null, SD_ZIEL_MAX, 'transport_dests.name', $pruef);
            if ($tName === null) { $stats['stammdaten_skipped']++; continue; }
            $st = $pdo->prepare('INSERT IGNORE INTO transport_dests
                                 (user_id, base_id, name, lat, lon) VALUES (?,?,?,?,?)');
            $st->execute([$userId, $bid, $tName,
                          pruef_breite($t['lat'] ?? null, 'transport_dests.lat', $pruef),
                          pruef_laenge($t['lon'] ?? null, 'transport_dests.lon', $pruef)]);
            $stats['stammdaten'] += $st->rowCount();
        }

        /* ---- Diensttage -------------------------------------------------------
         *
         * DIE KENNUNG WIRD NEU VERGEBEN, und die Zuordnung von alter auf neue
         * Kennung ist das Ergebnis dieses Abschnitts: Einsaetze und
         * Ruhe-Segmente der Datei verweisen mit der ALTEN `day_id`, in der
         * Datenbank gilt die neue.
         *
         * KEIN `INSERT IGNORE`, und die Wiedererkennung braucht mehr als
         * das Datum. Es gibt keinen Tagesschluessel — ein blindes INSERT
         * legte bei jedem Einspielen derselben Datei neue Diensttage an.
         *
         * ERKANNT WIRD IN ZWEI SCHRITTEN, und der erste ist der belastbare:
         *
         *   1. UEBER DIE EINSAETZE. `client_ref` ist geraeteweit eindeutig und
         *      wandert unveraendert durch jedes Backup. Existiert einer der
         *      Einsaetze dieses Diensttags im Ziel schon, IST sein `day_id` der
         *      gesuchte Tag — daran gibt es nichts zu raten.
         *   2. UEBER EINEN FINGERABDRUCK aus Datum, Dienstbeginn, Dienstende,
         *      Art und den eingefrorenen Bezeichnungen. Er greift fuer
         *      Diensttage OHNE Einsatz, die Schritt 1 nicht sehen kann. Zwei
         *      Tage, die in all dem uebereinstimmen, sind nicht
         *      unterscheidbar — sie zu vereinen ist dann kein Verlust.
         *
         * Datum und Dienstbeginn allein genuegen NICHT: Zwei Dienste eines
         * Kalendertags koennen denselben Beginn tragen, sobald ein Einsatz
         * zwischen ihnen verschoben wurde (dt_zeitraum_fortschreiben zieht den
         * Beginn nach vorne). Genau dieser Fall verschmolz beim Pruefen zwei
         * Diensttage zu einem.
         *
         * ANGABEN WERDEN NICHT UEBERSCHRIEBEN. Ein vorhandener Diensttag bleibt
         * unangetastet — auch das ist bestehendes Verhalten (INSERT IGNORE tat
         * genau das). Nur die Zuordnung wird gemerkt, damit fehlende Einsaetze
         * an ihm landen. */
        $dayIdMap = [];   // alte Kennung aus der Datei -> Kennung in dieser DB

        /* LIEGT DER ZIELTAG IM PAPIERKORB?
         *
         * Die Antwort entscheidet ueber `deleted_with_day` der Einsaetze und
         * Ruhesegmente, die an ihm landen — und sie haengt am ZIEL, nicht an
         * der Datei: Ein in der Datei mitgeloeschter Einsatz, dessen Zieltag
         * hier aktiv ist, wird EINZELN geloescht. Andernfalls entstuende ein
         * Eintrag mit `deleted_with_day = 1` an einem aktiven Tag, und der
         * waere unsichtbar (trash_list_missions() zeigt nur
         * `deleted_with_day = 0`) und unwiederbringlich (trash_restore_day()
         * holt nur zurueck, was am geloeschten Tag haengt). */
        $zieltagGeloescht = [];   // Kennung in dieser DB -> bool
        $nurEintraege = $dayMap !== null;
        if ($nurEintraege) {
            /* DIE ZUORDNUNG AUS DEM VORIGEN SCHRITT — geprueft gegen dieses
             * Konto. Was hier nicht steht, gehoert nicht hierher. */
            $wunsch = [];
            foreach ($dayMap as $alt => $neu) {
                $n = (int)$neu;
                if ($n > 0) { $wunsch[$n][] = (int)$alt; }
            }
            if ($wunsch) {
                foreach (sql_in_bloecken($pdo,
                    'SELECT id, deleted_at FROM days
                      WHERE user_id = ? AND id IN ({IDS})',
                    array_keys($wunsch), [$userId]) as $z) {
                    $zid = (int)$z['id'];
                    foreach ($wunsch[$zid] as $altId) { $dayIdMap[$altId] = $zid; }
                    $zieltagGeloescht[$zid] = $z['deleted_at'] !== null;
                }
            }
        }
        $tagZustand = $pdo->prepare('SELECT deleted_at FROM days WHERE id = ? AND user_id = ?');

        // Schritt 1 braucht die Einsatzkennungen JE QUELL-DIENSTTAG, also vor
        // der Schleife: Die Einsaetze selbst werden erst danach verarbeitet.
        $refsJeQuelltag = [];
        foreach (($data['missions'] ?? []) as $mm) {
            if (!is_array($mm)) { continue; }
            $q = isset($mm['day_id']) ? (int)$mm['day_id'] : 0;
            $ref = (string)($mm['client_ref'] ?? '');
            if ($q > 0 && $ref !== '') { $refsJeQuelltag[$q][] = $ref; }
        }
        /* SCHRITT 1 BELEGT, ER RAET NICHT MEHR.
         *
         * Hier stand `… AND client_ref = ? AND day_id IS NOT NULL LIMIT 1`,
         * abgefragt fuer eine Kennung nach der anderen, und der ERSTE Treffer
         * bestimmte den Zieltag fuer ALLE Einsaetze und Ruhesegmente des
         * Datei-Tags. Drei Dinge waren daran falsch:
         *
         *  - Hat jemand im Ziel EINEN dieser Einsaetze auf einen anderen Tag
         *    verschoben, wanderte der ganze Datei-Tag mit — auch wenn er im
         *    Ziel unveraendert daneben lag.
         *  - Fuehrte der Treffer auf einen Tag im PAPIERKORB, wurden seither
         *    alle aktiven Eintraege des Datei-Tags abgelehnt. Richtig
         *    gezaehlt, aber angekommen ist nichts.
         *  - `LIMIT 1` ohne `ORDER BY`, und `client_ref` ist nur je
         *    `device_id` eindeutig: Bei zwei Uhren kann derselbe Wert zweimal
         *    vorkommen, und welcher gewinnt, sagte niemand.
         *
         * Jetzt werden ALLE Kennungen des Datei-Tags nachgeschlagen, und zwar
         * nur auf AKTIVE Zieltage:
         *
         *   genau ein Zieltag  -> benutzen (das bisherige Verhalten, belegt)
         *   mehrere Zieltage   -> Schritt 1 gilt als ergebnislos, der
         *                         Fingerabdruck entscheidet; der Widerspruch
         *                         wird als `tag_mehrdeutig` gezaehlt
         *   keiner              -> Fingerabdruck wie bisher
         *
         * Die richtige Antwort auf „raten" ist nicht, anders zu raten, sondern
         * zu merken, dass man es nicht weiss. Der Fingerabdruck bleibt Schritt
         * 2 und nicht Schritt 1: Er ist der SPROEDERE Anker — er bricht,
         * sobald jemand am Zieltag Beginn, Ende, Art, Rettungsmittel oder
         * Station berichtigt hat, und das ist der haeufige Fall. `client_ref`
         * ist stabil. */
        $findeUeberEinsatz = $pdo->prepare('SELECT DISTINCT m.day_id
                                              FROM missions m
                                              JOIN days d ON d.id = m.day_id
                                             WHERE m.user_id = ? AND m.client_ref = ?
                                               AND d.deleted_at IS NULL');
        $findeTag = $pdo->prepare(
            'SELECT id FROM days
              WHERE user_id = ? AND day = ?
                AND ((started_at IS NULL AND ? IS NULL) OR started_at = ?)
                AND ((ended_at IS NULL AND ? IS NULL) OR ended_at = ?)
                AND ((kind IS NULL AND ? IS NULL) OR kind = ?)
                AND ((vehicle_name IS NULL AND ? IS NULL) OR vehicle_name = ?)
                AND ((base_name IS NULL AND ? IS NULL) OR base_name = ?)
              ORDER BY id LIMIT 1');
        $tagAnkerDa = db_hat_spalte($pdo, 'days', 'created_at');
        foreach (($data['days'] ?? []) as $d) {
            $tagWert = pruef_kalendertag($d['day'] ?? null, 'days.day', $pruef);
            if ($tagWert === null) { $grund['tag_unbrauchbar']++; continue; }
            $altId = isset($d['id']) ? (int)$d['id'] : 0;

            /* IST DIESER TAG IN DER DATEI GELOESCHT?
             *
             * Seit Nutzlast 7 traegt die Datei den Papierkorb. Der ZUSTAND
             * wird uebernommen, der Zeitpunkt nicht — siehe $loeschZeit oben. */
            $dateiGeloescht = ($d['deleted_at'] ?? null) !== null;

            /* D1 — UND DER FALL HAT ZWEI HAELFTEN.
             *
             * (1) EIN AKTIVER DATEI-TAG, dessen DATUM im Zielkonto einen Tag
             *     im Papierkorb trifft, wird uebersprungen und gezaehlt. Das
             *     Loeschen war eine bewusste
             *     Handlung, und ein Einspielen soll sie nicht nebenbei
             *     rueckgaengig machen ("Ablehnen statt Zurueckholen").
             *
             *     Ohne diese Pruefung tat INSERT IGNORE hier zwar nichts — der
             *     eindeutige Schluessel griff —, aber eben STILL: Der Tag wurde
             *     weder eingespielt noch gezaehlt noch erwaehnt. Wer eine
             *     Backup zurueckspielt und seine Diensttage vermisst, hatte
             *     keinen Anhaltspunkt.
             *
             * (2) EIN IN DER DATEI GELOESCHTER TAG durchlaeuft die normale
             *     Wiedererkennung. Er will gar nicht aktiv werden — ihn am
             *     Ziel-Papierkorb zu messen ergaebe keinen Sinn. Wird er nicht
             *     gefunden, entsteht er ALS PAPIERKORBEINTRAG samt seinen
             *     mitgeloeschten Einsaetzen und Ruhesegmenten.
             *
             * Zwei geloeschte Tage desselben Datums (einer in der Datei, einer
             * im Ziel, verschiedener Fingerabdruck) duerfen damit nebeneinander
             * bestehen. Das ist richtig so: Der Papierkorb kennt keine
             * Eindeutigkeit je Datum, und auch bei den aktiven Tagen gibt
             * es sie nicht. */
            if (!$dateiGeloescht && isset($tageImPapierkorb[$tagWert])) {
                $grund['tag_im_papierkorb']++;
                continue;
            }

            $startedAt = $d['started_at'] ?? null;
            $endedAt   = $d['ended_at'] ?? null;
            $kindWert  = in_array($d['kind'] ?? null, ['air', 'ground'], true) ? $d['kind'] : null;
            $vName     = $d['vehicle_name'] ?? null;
            $bName     = $d['base_name'] ?? null;
            /* TYP UND KURZNAME KOMMEN AUS DER DATEI, NICHT AUS DEN HEUTIGEN
             * STAMMDATEN (Nutzlast 10). Ein Blick auf `vehicles` waere
             * genau der Durchgriff, den das Einfrieren ausschliesst — und er
             * schluege bei jedem Tag fehl, dessen Rettungsmittel es hier gar
             * nicht gibt. Eine Datei aelterer Fassung hat die Felder nicht:
             * `vehicle_typ` bleibt dann NULL, und die Anzeige faellt auf die
             * Betriebsart zurueck. Ein unbekannter Wert wird ebenso NULL — das
             * ENUM naehme ihn ohnehin nicht an, und ein stilles 'standard'
             * behauptete etwas. */
            $vTyp      = array_key_exists((string)($d['vehicle_typ'] ?? ''), VEHICLE_TYPEN)
                       ? (string)$d['vehicle_typ'] : null;
            if (($d['vehicle_typ'] ?? null) !== null && $vTyp === null) {
                $pruef->melde('days.vehicle_typ', 'unbekannter Wert — leer gelassen');
            }
            $vKurz     = pruef_text($d['vehicle_kurz'] ?? null, RM_KURZ_MAX,
                                    'days.vehicle_kurz', $pruef);

            // Schritt 1: ueber einen Einsatz, der im Ziel schon liegt.
            $vorhanden = false;
            $kandidaten = [];
            foreach (($refsJeQuelltag[$altId] ?? []) as $ref) {
                $findeUeberEinsatz->execute([$userId, $ref]);
                foreach ($findeUeberEinsatz->fetchAll(PDO::FETCH_COLUMN) as $w) {
                    $kandidaten[(int)$w] = true;
                }
            }
            if (count($kandidaten) === 1) {
                $vorhanden = (int)array_key_first($kandidaten);
            } elseif (count($kandidaten) > 1) {
                /* WIDERSPRUCH — und der wird gemeldet, nicht aufgeloest. Die
                 * Einsaetze dieses Datei-Tags liegen im Ziel an verschiedenen
                 * Diensttagen; welcher davon „der" Zieltag ist, sagt die Datei
                 * nicht. Schritt 2 entscheidet, und die Zahl steht in der
                 * Rueckmeldung, damit jemand nachsehen kann. */
                $grund['tag_mehrdeutig']++;
            }
            // Schritt 2: Fingerabdruck.
            if ($vorhanden === false) {
                $findeTag->execute([$userId, $tagWert, $startedAt, $startedAt,
                                    $endedAt, $endedAt, $kindWert, $kindWert,
                                    $vName, $vName, $bName, $bName]);
                $w = $findeTag->fetchColumn();
                if ($w !== false) { $vorhanden = (int)$w; }
            }
            if ($vorhanden !== false) {
                /* ANGABEN WERDEN NICHT UEBERSCHRIEBEN — und das gilt auch fuer
                 * den Loeschzustand. Ein hier vorhandener Tag bleibt,
                 * wie er ist: Ein aktiver Zieltag wird nicht wegen der Datei
                 * geloescht, ein geloeschter nicht wegen der Datei
                 * zurueckgeholt. Was aus der Datei kommt, sind die FEHLENDEN
                 * Einsaetze und Ruhesegmente — und die richten sich nach dem
                 * Zustand des Zieltags. */
                $zielId = (int)$vorhanden;
                if ($altId > 0) { $dayIdMap[$altId] = $zielId; }
                if (!array_key_exists($zielId, $zieltagGeloescht)) {
                    $tagZustand->execute([$zielId, $userId]);
                    /* fetchColumn() liefert `false` ohne Zeile und `null` bei
                     * einem leeren Feld — beides heisst "nicht im Papierkorb",
                     * und beide muessen ausdruecklich dastehen. `!== null`
                     * allein haette die fehlende Zeile als geloescht gewertet. */
                    $w = $tagZustand->fetchColumn();
                    $zieltagGeloescht[$zielId] = ($w !== false && $w !== null);
                }
                continue;
            }

            /* Angelegt wird mit den EINGEFRORENEN Angaben aus der Datei —
             * nicht ueber dt_zuordnen(), das sie aus den heutigen Stammdaten
             * frisch holen wuerde. Ein Backup soll den Bestand abbilden,
             * nicht ihn neu ableiten: Ein inzwischen umbenanntes Rettungsmittel
             * traegt in der Datei seinen damaligen Namen, und der gehoert
             * zurueck. Die Fremdschluessel werden daneben ueber den
             * NAMEN wieder verknuepft, soweit der Stammdatensatz existiert. */
            $vehId  = null;
            if (!empty($d['vehicle_ref'])) {
                $x = $pdo->prepare('SELECT id FROM vehicles WHERE name = ? AND user_id = ?');
                $x->execute([(string)$d['vehicle_ref'], $userId]);
                $w = $x->fetchColumn();
                $vehId = $w === false ? null : (int)$w;
            }
            $baseId = $baseIdByName(isset($d['base_ref']) ? (string)$d['base_ref'] : null);

            /* DER ANKER DES TAGES steht NICHT in
             * der Datei — die Nutzlast bleibt, wie sie ist. Er wird
             * gesetzt wie der Rueckfall der Migration: `started_at`, ohne
             * eines der Tag um 00:00, hoechstens jetzt. Mit der Vorgabe der
             * Spalte stuende an jedem eingespielten Tag die Zeit des
             * Einspielens, und Geraete schrieben seinen Zeitraum drei Tage
             * lang wieder fort. Ohne Spalte (Deploy-Fenster) bleibt sie weg. */
            $tagSpalten = 'user_id, day, started_at, ended_at, vehicle_id, base_id, kind,
                 base_name, base_lat, base_lon, vehicle_name, vehicle_typ,
                 vehicle_kurz, notes, deleted_at';
            $tagWerte = [$userId, $tagWert, $startedAt, $endedAt,
                $vehId, $baseId, $kindWert,
                $bName, $d['base_lat'] ?? null, $d['base_lon'] ?? null,
                $vName, $vTyp, $vKurz, $d['notes'] ?? null,
                $dateiGeloescht ? $loeschZeit : null];
            if ($tagAnkerDa) {
                $anker = iso_utc_lesen(is_string($startedAt) ? $startedAt : null)
                      ?? iso_utc_lesen($tagWert . ' 00:00:00') ?? time();
                $tagSpalten .= ', created_at';
                $tagWerte[]  = gmdate('Y-m-d H:i:s', max(1, min($anker, time())));
            }
            $st = $pdo->prepare('INSERT INTO days (' . $tagSpalten . ')
                VALUES (' . implode(',', array_fill(0, count($tagWerte), '?')) . ')');
            $st->execute($tagWerte);
            $neuId = (int)$pdo->lastInsertId();
            $stats['days']++;
            $zieltagGeloescht[$neuId] = $dateiGeloescht;
            if ($dateiGeloescht) { $stats['papierkorb']['diensttage']++; }
            if ($altId > 0) { $dayIdMap[$altId] = $neuId; }

            // Rollensatz und Faehigkeiten sind Teil des Snapshots.
            $insDC = $pdo->prepare('INSERT IGNORE INTO day_crew (day_id, role_code, name)
                                    VALUES (?,?,?)');
            foreach ((array)($d['crew'] ?? []) as $rc => $nm) {
                if (!array_key_exists((string)$rc, CREW_ROLES)) { continue; }
                $insDC->execute([$neuId, (string)$rc,
                                 ($nm === null || $nm === '') ? null : mb_substr((string)$nm, 0, 120)]);
            }
            $insDCap = $pdo->prepare('INSERT IGNORE INTO day_capabilities (day_id, capability)
                                      VALUES (?,?)');
            foreach ((array)($d['capabilities'] ?? []) as $cap) {
                if (array_key_exists((string)$cap, VEHICLE_CAPABILITIES)) {
                    $insDCap->execute([$neuId, (string)$cap]);
                }
            }

            /* UHR-KENNUNGEN WIEDERHERSTELLEN.
             *
             * Ohne sie legt ein spaeter eintreffender Upload derselben Uhr den
             * Diensttag erneut an — genau der Fall, den es auszuschliessen gilt. Die
             * Geraetekennung ist der oeffentliche Name; ein Geraet, das es hier
             * nicht (mehr) gibt, ergibt device_id = NULL, und die Kennung bleibt
             * trotzdem gespeichert. */
            $insRef = $pdo->prepare('INSERT IGNORE INTO day_refs (day_id, device_id, day_ref)
                                     VALUES (?,?,?)');
            $findeDev = $pdo->prepare('SELECT id FROM devices WHERE device_id = ? AND user_id = ?');
            foreach ((array)($d['refs'] ?? []) as $ref) {
                if (!is_array($ref) || empty($ref['day_ref'])) { continue; }
                $devId = null;
                if (!empty($ref['device_id'])) {
                    $findeDev->execute([(string)$ref['device_id'], $userId]);
                    $w = $findeDev->fetchColumn();
                    $devId = $w === false ? null : (int)$w;
                }
                $insRef->execute([$neuId, $devId, mb_substr((string)$ref['day_ref'], 0, 64)]);
            }
        }

        /* Einsaetze: Dublette = gleiche client_ref bei dieser NutzerIn */
        $exists = $pdo->prepare('SELECT id FROM missions WHERE user_id = ? AND client_ref = ?');

        /* SPUR SCHREIBEN — EINE STELLE FUER BEIDE ARTEN.
         *
         * Es waren zwei, und sie waren verschieden: Der Einsatz begrenzte die
         * Menge, pruefte den Aufbau und liess pruef_breite/pruef_laenge ueber
         * die Koordinaten laufen; das Ruhesegment schrieb roh, was in der
         * Datei stand. `(float)"Unfug"` ist 0.0 — aus einem unbrauchbaren
         * Punkt wurde damit still eine gueltige Koordinate im Golf von Guinea,
         * und die Punktzahl war unbegrenzt.
         *
         * Zwei Kopien einer Pruefung sind eine Kopie zu viel: Die zweite
         * bleibt zurueck. Jetzt gibt es eine.
         *
         * ZUSAETZLICH GEPRUEFT werden `seq` und `ts` gegen den Wertebereich
         * ihrer Spalten (beide INT UNSIGNED NOT NULL) und `ele` auf
         * Numerik. Auch das war auf BEIDEN Wegen offen: Ein negatives `seq`
         * oder ein Text in `ele` bringt nicht einen Punkt zu Fall, sondern
         * ueber die Ausnahme die ganze Wiederherstellung.
         *
         * UND DIE EINDEUTIGKEIT VON `seq`. Der Wertebereich
         * allein reicht nicht: eine Spur ist durch
         * (owner_type, owner_id, seq) bestimmt, und zwei Stuecke mit
         * derselben Nummer widersprechen einander heute ebenso; zwei Punkte
         * mit derselben Nummer loesten also einen Schluesselkonflikt aus — und der reisst wieder den
         * gesamten Lauf mit. Ein eigener Export erzeugt keine Wiedergaenger;
         * eine von Hand bearbeitete oder fremde Datei kann es. Der zweite
         * Punkt wird deshalb uebersprungen und gemeldet.
         *
         * Der kuerzere Weg waere INSERT IGNORE gewesen. Er ist der stille:
         * Die Datei behielte einen Fehler, den niemand zu sehen bekommt.
         * $gesehen wird je Eigentuemer neu angelegt — der Schluessel gilt nur
         * innerhalb einer Spur. */
        $spurSchreiben = function (string $typ, int $ownerId, $liste, string $ref)
                              use ($pdo, $pruef, $sec1Holen): void {
            $gesehen = [];
            $gueltig = [];
            /* ABLEHNEN STATT KAPPEN.
             *
             * Nicht pruef_menge() mit derselben Konstante, die ingest.php je
             * ANFRAGE anwendet — hier gaelte sie je SPUR. Was die Uhr ueber
             * viele Anfragen aufbauen darf, wuerde beim Zurueckspielen bei
             * 2000 Punkten abgeschnitten; die Datei trug
             * die ganze Spur, zurueck kam ihr Anfang. In ein frisches Konto
             * eingespielt war der Verlust endgueltig.
             *
             * Jetzt: eigene, hohe Grenze — und eine Spur darueber wird GANZ
             * abgelehnt und benannt. Eine halbe Spur sieht aus wie eine ganze. */
            $punkte = pruef_menge_streng($liste ?? [], LIMIT_TRACKPUNKTE_SPUR,
                                         $typ . '.track', $pruef);
            if ($punkte === null) { return; }
            foreach ($punkte as $p) {
                if (!is_array($p) || count($p) < 5) { continue; }
                $la = pruef_breite($p[1], $typ . '.track.lat', $pruef);
                $lo = pruef_laenge($p[2], $typ . '.track.lon', $pruef);
                $seq = pruef_zahl($p[0], 0, 4294967295, $typ . '.track.seq', $pruef);
                $ts  = pruef_zahl($p[4], 0, 4294967295, $typ . '.track.ts', $pruef);
                if ($la === null || $lo === null || $seq === null || $ts === null) { continue; }
                if (isset($gesehen[$seq])) {
                    $pruef->melde($typ . '.track.seq', 'Nummer doppelt');
                    continue;
                }
                $gesehen[$seq] = true;
                // Hoehe darf fehlen, aber nicht Text sein.
                $ele = is_numeric($p[3]) ? (float)$p[3] : null;
                $gueltig[] = [(int)$seq, (float)$la, (float)$lo, $ele, (int)$ts];
            }
            if (!$gueltig) { return; }
            /* VERSCHLOSSEN, NICHT ALS ZEILEN. Hierher kommt nur
             * noch der Demo-Reset ('server'); der Browserweg schickt Stuecke,
             * und eine Punktliste haette ihn oben abgewiesen. */
            usort($gueltig, static fn($a, $b) => $a[0] <=> $b[0]);
            foreach (gh_stuecke_bauen($sec1Holen(), $typ === 'rest' ? 'ruhe' : 'spur', $ref,
                                      $gueltig) as $st) {
                spur2_stueck_schreiben($pdo, $typ, $ownerId, $st['seq_from'], $st['punkte'],
                                       $st['ts_von'], $st['ts_bis'], $st['huelle']);
            }
        };

        /* Kein `$stueckeSchreiben`: Der Browserweg nimmt nur Nutzlast 13,
         * deren Spuren ueber `api/backup_spuren_restore.php` kommen. */

        /* EINE PUNKTLISTE IN NUTZLAST 8 IST EIN WIDERSPRUCH — UND WIRD GESAGT
         *
         * Nutzlast 8 sagt zu, dass die Punkte NICHT im Eintrag stehen,
         * sondern als Blob in eigenen Teilen nachkommen. Der Zweig darueber
         * entscheidet deshalb an der Fassung und nicht am Vorhandensein eines
         * `track`-Feldes — mit gutem Grund: Eine Spur ohne Punkte saehe
         * genauso aus wie ein Verweis.
         *
         * Die Kehrseite hat gefehlt. Traegt eine Datei Nutzlast 8 UND
         * Punktlisten, wurden die Punkte hier stillschweigend uebergangen:
         * Der Eintrag entstand ohne Spur, und die Meldung lautete „fertig".
         * Gefunden beim Aufbau eines Pruefbestands — der
         * Vervielfaeltiger des Messstands erbte die Fassung
         * aus der Referenz und schrieb `version: 8` in eine einteilige Datei
         * mit `track`-Listen. Ergebnis eines Laufs: 164 Einsaetze angelegt,
         * 91 208 Punkte verloren, ohne ein Wort.
         *
         * Eine solche Datei schreibt diese Anwendung nicht — sie kann nur von
         * Hand oder aus einem Werkzeug kommen. Abgewiesen wird sie trotzdem
         * nicht: Der uebrige Bestand ist brauchbar, und ihn wegen der Spuren
         * zu verweigern hiesse, aus einem Teilverlust einen Totalverlust zu
         * machen. Gemeldet wird er ueber die gemeinsame Pruefschicht, also
         * dort, wo die Nutzerin die Ablehnungen ohnehin liest. */
        /* DIE MELDUNG NENNT DIE FASSUNG NICHT MEHR ALS ZAHL.
         * Sie lautete woertlich „Die Datei nennt Nutzlast 8 …", und mit
         * Nutzlast 9 waere sie schlicht falsch gewesen. Sie nennt jetzt die
         * EIGENSCHAFT statt der Zahl — die gilt fuer 8, 9 und jede weitere
         * Fassung ohne Punktlisten. Wer den Wortlaut aendert, aendert
         * `tools/proben/wiederherstellung/` mit: Teil 7 sucht diesen Text
         * als Zeichenkette, und die beiden stehen weit auseinander. */
        $spurWiderspruch = function (string $typ, array $eintrag) use ($pruef, $nutzlast): void {
            $liste = $eintrag['track'] ?? null;
            if (is_array($liste) && $liste) {
                $pruef->melde($typ . '.track',
                    'Die Datei nennt Nutzlast ' . $nutzlast . ' und traegt die Punkte '
                  . 'trotzdem im Eintrag. Ab Nutzlast 8 kommen sie als eigene Teile — '
                  . 'diese Punkte wurden NICHT uebernommen');
            }
        };
        $FIELDS = require __DIR__ . '/mission_fields.php';
        /* WELCHE SPALTEN AUS DER DATEI UEBERNOMMEN WERDEN.
         *
         * Grundlage ist der Feldkatalog — er ist die eine Liste, gegen die auch
         * das Formular arbeitet. Drei Ergaenzungen, die er nicht hergibt:
         *
         *  - mf_ist_spalte() statt einer Pruefung auf 'resources' allein: Auch
         *    die BESATZUNG liegt nicht in `missions`
         *    (`crew_p1` … als 'store' => 'crew'). Stuende sie in der Liste,
         *    haette eine Datei mit einem Schluessel `crew_p1` — etwa eine
         *    von Hand bearbeitete — ein INSERT auf eine Spalte erzeugt,
         *    die es nicht mehr gibt, und damit nicht eine Zeile, sondern die
         *    ganze Wiederherstellung zum Scheitern gebracht. Die Besatzung
         *    kommt unten aus `crew`.
         *  - Die KOORDINATENSPALTEN der Ortsfelder: Sie heissen
         *    nicht wie ihr Feld (`transport_dest` -> `dest_lat`/`dest_lon`) und
         *    stehen deshalb in mf_ort_spalten().
         *  - `start_src`, die Abfahrtortregel: kein Katalogfeld (siehe
         *    mission_fields.php), aber eine gewoehnliche Spalte, die gesichert
         *    wird und zurueckkommen muss.
         *  - `created_at`, der Anlegezeitpunkt der ZEILE. Er wird gesichert und muss zurueck: Sonst
         *    trugen nach einer Wiederherstellung alle Einsaetze den Zeitpunkt
         *    des Einspielens — am Referenzdatensatz gemessen 79 verschiedene Werte
         *    davor, 5 danach. Die Angabe ist keine fachliche Zeit (das ist
         *    `started_at`), aber sie ist eine ANGABE, und ein Backup, das
         *    eine Angabe stillschweigend fallenlaesst, ist keine. Der Wert
         *    laeuft durch pruef_utc_oder_sql(); ist er unbrauchbar, wird die
         *    Spalte weggelassen und die Datenbank-Vorgabe greift — die Zeile
         *    bleibt.
         *
         * Wer eine Spalte hinzufuegt, die NICHT im Katalog steht, traegt sie
         * hier ein — sonst wird sie gesichert und beim Einspielen verworfen.
         *
         * NICHT hier stehen `deleted_at` und `deleted_with_day`: Sie werden
         * nicht uebernommen, sondern NEU BESTIMMT (Zustand aus
         * der Datei, Zeitpunkt und Bindung an den Tag aus diesem Lauf). Sie
         * stehen deshalb unten ausdruecklich im INSERT. */
        $extraCols = [];
        $collectCols = function (array $fs) use (&$collectCols, &$extraCols) {
            foreach ($fs as $col => $f) {
                if (mf_ist_spalte($f)) { $extraCols[] = $col; }
                foreach (mf_ort_spalten($f) as $ortCol) { $extraCols[] = $ortCol; }
                if (!empty($f['children'])) { $collectCols($f['children']); }
            }
        };
        $collectCols($FIELDS);
        // Alt-Backups: loc_* wird ignoriert
        /* `notes` STEHT HIER, obwohl es kein Katalogfeld mit
         * Spalte mehr ist (mf_ist_spalte() sagt nein) — und genau deshalb
         * muss es von Hand dazu.
         *
         * Eine Sicherungsdatei mit Nutzlast 10 oder aelter traegt die Notiz im
         * KLARTEXT in dieser Spalte. Faellt sie hier aus der Liste, wird sie
         * beim Einspielen stillschweigend verworfen: kein Fehler, keine
         * Meldung, eine Notiz weniger. Sie wird deshalb weiter geschrieben —
         * und der Anhebelauf holt sie beim naechsten Entsperren
         * in den Blob. Eine Datei mit Nutzlast 11 oder neuer traegt an dieser
         * Stelle NULL und den Text im `pat`-Block; dann schreibt diese Spalte
         * NULL, was richtig ist.
         *
         * Wenn die Spalte entfernt wird, faellt dieser Eintrag mit ihr —
         * und mit ihm die Faehigkeit, eine 10er-Datei vollstaendig
         * einzuspielen. Das gehoert dann ausdruecklich entschieden. */
        /* `transport_dest` gibt es als Spalte nicht; das Transportziel kommt
         * im `pat_blob`. */
        $extraCols = array_merge($extraCols, ['start_src', 'pat_blob', 'notes', 'created_at']);

        foreach (($data['missions'] ?? []) as $m) {
            if (!is_array($m)) { $stats['missions_skipped']++; $grund['aufbau']++; continue; }
            $exists->execute([$userId, (string)($m['client_ref'] ?? '')]);
            $vorhandenId = $exists->fetchColumn();
            if ($vorhandenId) {
                /* DIE SPURKARTE BEKOMMT IHN TROTZDEM.
                 *
                 * Sonst gibt es keine Wiederaufnahme, und das ist kein
                 * Schoenheitsfehler: Bricht das Einspielen zwischen Kern und
                 * Spurteilen ab — Netz weg, Fenster zu, ein Fehler wie der,
                 * der diese Zeilen ausgeloest hat —, dann steht der Bestand,
                 * und die Spuren fehlen. Beim zweiten Anlauf waere JEDER
                 * Einsatz „bereits vorhanden", die Karte bliebe leer, und
                 * alle Spuren meldeten „ohne zugehoerigen Einsatz". Sie
                 * waeren damit NIE mehr einzuspielen — es sei denn, man
                 * loescht den halben Bestand von Hand.
                 *
                 * Gefunden bei der Abnahme am 5000er-Bestand: 10 431 Spuren
                 * „ohne zugehoerigen Einsatz", nachdem eine Anfrage an einer
                 * Mengengrenze gescheitert war. */
                if (isset($m['spur_ref'])) {
                    $spurKarte[(int)$m['spur_ref']] = ['art' => 'mission',
                                                       'id' => (int)$vorhandenId,
                                                       'ref' => trim((string)$m['client_ref'])];
                }
                $merke('mission', $m['client_ref'] ?? null, (int)$vorhandenId);
                $stats['missions_skipped']++; $grund['bereits_vorhanden']++; continue;
            }

            /* PRUEFUNG DIESES WEGES — sie fehlte bisher VOLLSTAENDIG.
             *
             * Das ist die auffaelligste Stelle des ganzen Reviews: Von den
             * neun Pruefungen, die der Import durchfuehrt, fand hier keine
             * einzige statt — obwohl die Datei aus BELIEBIGER Herkunft stammen
             * kann, waehrend der Uhr-Weg immerhin einen Schluessel verlangt.
             * Die Pruefsorgfalt verlief genau umgekehrt zur
             * Vertrauenswuerdigkeit der Quelle.
             *
             * Vier Folgen hatte das:
             *  - Phase 10 kehrte ueber diesen Weg in die Datenbank zurueck.
             *    Nach der Migration war das der einzige verbliebene Weg dorthin.
             *  - Reanimationsarten waren ungeprueft.
             *  - Der Patientenblock hatte keine Laengengrenze; die Datenbank
             *    haette ihn abgeschnitten — ein abgeschnittener Chiffretext ist
             *    DAUERHAFT unlesbar.
             *  - Ein einziger ungueltiger Wert brach die GESAMTE
             *    Wiederherstellung ab, statt die eine Zeile zu ueberspringen.
             *    Bei einer Wiederherstellung ist das die falsche Richtung:
             *    Wer sie startet, hat meist keinen zweiten Versuch.
             */
            $startedAt = pruef_utc_oder_sql($m['started_at'] ?? null, 'started_at', $pruef);
            /* DER DIENSTTAG IST PFLICHT. `missions.day_id` ist ein
             * Fremdschluessel; ohne ihn waere der Einsatz verwaist. Die
             * Kennung aus der Datei wird ueber $dayIdMap auf die hier vergebene
             * umgeschrieben — fehlt sie dort, wurde der Diensttag uebersprungen
             * (Papierkorb, unbrauchbares Datum), und der Einsatz gehoert
             * ebenfalls uebersprungen und gezaehlt. Ein Einsatz traegt kein
             * eigenes Datum und kann ohne Tag nicht existieren. */
            $altDayId = isset($m['day_id']) ? (int)$m['day_id'] : 0;
            $dayId    = $dayIdMap[$altDayId] ?? 0;
            if ($dayId === 0) {
                /* ZWEI VERSCHIEDENE GRUENDE, GETRENNT GEZAEHLT.
                 *
                 * Beide unter `datum_oder_zeit` laufen zu lassen, waere
                 * irrefuehrend: Am Datum dieser Einsaetze ist nichts
                 * auszusetzen — ihr Diensttag wurde uebersprungen (Papierkorb
                 * im Ziel, unbrauchbares Datum am Tag). Wer die Rueckmeldung
                 * las, suchte den Fehler an der falschen Stelle und fand
                 * nichts.
                 *
                 * Nennt die Datei ueberhaupt keinen Tag, ist es dagegen
                 * wirklich ein Datumsproblem der Zeile. */
                $stats['missions_skipped']++;
                $grund[$altDayId > 0 ? 'tag_uebersprungen' : 'datum_oder_zeit']++;
                continue;
            }
            if ($startedAt === null) {
                $stats['missions_skipped']++; $grund['datum_oder_zeit']++; continue;
            }
            $endedAt = pruef_utc_oder_sql($m['ended_at'] ?? null, 'ended_at', $pruef);

            /* LOESCHZUSTAND. Aus der Datei kommt das OB, aus
             * diesem Lauf der Zeitpunkt.
             *
             * `deleted_with_day` braucht BEIDE Seiten, und die erste Fassung
             * las nur eine: Sie setzte 1, sobald Einsatz und Zieltag geloescht
             * sind. Das ist zu grob, denn die Kombination „Einsatz EINZELN
             * geloescht, Tag danach geloescht" ist ein regulaerer Zustand —
             * `trash_delete_day()` fasst bereits geloeschte Einsaetze nicht an
             * (`WHERE deleted_at IS NULL`), sie behalten also
             * `deleted_with_day = 0` an einem geloeschten Tag. Wer so einen
             * Einsatz einspielte, bekam ihn als „mit dem Tag geloescht"
             * zurueck: aus der Einzelliste des Papierkorbs verschwunden und
             * beim Wiederherstellen des Tages wieder aktiv, obwohl er es
             * vorher nicht wurde.
             *
             * Richtig ist die UND-Verknuepfung: Der Wert aus der Datei sagt,
             * ob der Eintrag am Tag hing; der Zieltag sagt, ob das hier
             * ueberhaupt gelten kann. Das heisst „nur wenn" —
             * eine notwendige, keine hinreichende Bedingung. */
            $mGeloescht = ($m['deleted_at'] ?? null) !== null;
            $zielTagWeg = !empty($zieltagGeloescht[$dayId]);
            $mitTag     = ($mGeloescht && $zielTagWeg
                           && (int)($m['deleted_with_day'] ?? 0) === 1) ? 1 : 0;

            /* AKTIVER EINTRAG AN EINEM GELOESCHTEN ZIELTAG: ABLEHNEN.
             *
             * Die Gegenrichtung der Invariante. Landet ein in der Datei
             * AKTIVER Einsatz auf einem Zieltag, der hier im Papierkorb liegt,
             * dann stuende er an einem Tag, den die Tagesliste nicht zeigt —
             * in der Suche sichtbar, in der Uebersicht nicht, im Papierkorb
             * auch nicht, und beim endgueltigen Loeschen des Tages bliebe er
             * ohne Diensttag zurueck.
             *
             * Das ist dieselbe Regel wie D1, nur eine Ebene tiefer: Was hier
             * im Papierkorb liegt, nimmt nichts Neues auf. Die Datumspruefung
             * oben kann den Fall nicht abfangen — sie vergleicht Kalenderdaten,
             * und die Zuordnung ueber `client_ref` kann auf einen
             * Tag ANDEREN Datums fuehren. */
            if (!$mGeloescht && $zielTagWeg) {
                $stats['missions_skipped']++; $grund['tag_im_papierkorb']++; continue;
            }

            $oe = edbak_origin_edited($m);

            /* TRANSPORTZIEL UND ORTSHOEHE IN DEN BLOB — nur mit `$blobZiel`
             * (Demo-Reset). Danach stehen sie nicht mehr in `$m`, und
             * die Spalten bleiben leer. */
            if ($blobZiel !== null) {
                $zusatz = [];
                $ziel = trim((string)($m['transport_dest'] ?? ''));
                $zLat = is_numeric($m['dest_lat'] ?? null) ? (float)$m['dest_lat'] : null;
                $zLon = is_numeric($m['dest_lon'] ?? null) ? (float)$m['dest_lon'] : null;
                if ($ziel !== '' || $zLat !== null) {
                    $zusatz['transport_dest'] = ['addr' => $ziel]
                        + ($zLat !== null && $zLon !== null ? ['lat' => $zLat, 'lon' => $zLon] : []);
                }
                if (is_numeric($m['site_ele_m'] ?? null)) {
                    $zusatz['ortshoehe'] = (int)$m['site_ele_m'];
                }
                if ($zusatz) {
                    $blob = $m['pat_blob'] ?? null;
                    $m['pat_blob'] = $blobZiel(is_string($blob) && $blob !== '' ? $blob : null, $zusatz);
                }
                unset($m['transport_dest'], $m['dest_lat'], $m['dest_lon'], $m['site_ele_m']);
            }

            /* `geraet_art` und `geraet_modell` stehen in der FESTEN Liste und
             * NICHT in `$extraCols`. `$extraCols` kommt aus dem
             * Formular-Feldkatalog (`mission_fields.php`) — die beiden stehen
             * dort nicht und koennten es auch nicht: Sie sind keine
             * Eingabefelder, sondern eine Momentaufnahme des Geraets. Wer sie
             * dorthin legte, machte sie von einem Katalog abhaengig, der eine
             * andere Frage beantwortet.
             *
             * FEHLEN SIE IN DER DATEI (Nutzlast <= 8), wird NULL geschrieben —
             * und das ist richtig: „unbekannt" ist genau die Aussage. */
            /* HIER WIRD AUS DEM DATEISCHLUESSEL WIEDER DIE SPALTE.
             * Die SCHLUESSEL sind Spaltennamen (`uhr_gesperrt`), die WERTE
             * lesen den Dateischluessel (`$m['manual']`).
             *
             * NICHT ZWEI LISTEN — Spalten oben, Werte darunter,
             * positionsgebunden. Wer eine der beiden Listen ergaenzt und die
             * andere nicht, verschoebe stumm alle Werte dahinter — die
             * Wiederherstellung schriebe dann die Geraeteart ins Loeschdatum,
             * ohne dass irgendetwas rot wuerde. Deshalb traegt jeder Wert
             * seinen Spaltennamen; die REIHENFOLGE kommt aus dem Register
             * (Zweck `backup_restore`).
             *
             * DIE SCHREIBREIHENFOLGE HIER BLEIBT DIE ALTE, und das mit
             * Absicht: `pruef_text()`, `pruef_zahl()` und `edbak_geraet_art()`
             * haengen ihre Beanstandungen an `$pruef` an. Wer sie umsortiert,
             * sortiert den Pruefbericht um, den jemand neben die Datei legt. */
            $werte = [
                'user_id'     => $userId,
                'client_ref'  => pruef_text($m['client_ref'] ?? null, 64, 'client_ref', $pruef)
                                    ?? ('bak-' . bin2hex(random_bytes(6))),
                'day_id'      => $dayId,
                'started_at'  => $startedAt,
                'ended_at'    => $endedAt,
                /* pruef_flag statt (int): Beide Spalten sind TINYINT(1),
                 * und (int) einer Zahl jenseits von 127 laeuft dort ueber
                 * — ein Fehler, der die ganze Transaktion kostet. Fuer
                 * gueltige Werte ist das Ergebnis dasselbe. */
                'uhr_gesperrt' => pruef_flag($m['manual'] ?? 0),
                'origin'      => $oe['origin'],
                'edited'      => $oe['edited'],
                'final'       => pruef_flag($m['final'] ?? 1),
                'distance_m'  => pruef_zahl($m['distance_m'] ?? null, 0, 100000000,
                                            'distance_m', $pruef),
                'ascent_m'    => pruef_zahl($m['ascent_m'] ?? null, 0, 100000,
                                            'ascent_m', $pruef),
                'geraet_art'  => edbak_geraet_art($m['geraet_art'] ?? null, 'geraet_art', $pruef),
                'geraet_modell' => pruef_text($m['geraet_modell'] ?? null, GERAET_MAX_MODELL,
                                              'geraet_modell', $pruef),
                'deleted_at'  => $mGeloescht ? $loeschZeit : null,
                'deleted_with_day' => $mitTag,
            ];
            $cols = mf_spalten('backup_restore', '', false);
            $vals = [];
            foreach ($cols as $c) { $vals[] = $werte[$c]; }
            foreach ($extraCols as $c) {
                if (!array_key_exists($c, $m)) { continue; }
                if ($c === 'created_at') {
                    /* Unbrauchbarer Wert -> Spalte WEGLASSEN, nicht NULL
                     * schreiben. `missions.created_at` traegt eine
                     * Datenbank-Vorgabe; ein ausdrueckliches NULL wuerde sie
                     * umgehen und die Zeile ohne Anlegezeitpunkt hinterlassen.
                     * Die Zeile bleibt in jedem Fall — ein Komfortwert darf
                     * eine Wiederherstellung nicht kosten. */
                    $wert = pruef_utc_oder_sql($m[$c], 'created_at', $pruef);
                    if ($wert === null) { continue; }
                    $cols[] = $c; $vals[] = $wert;
                    continue;
                }
                // Der Patientenblock bekommt dieselbe Grenze wie ueberall
                // sonst; alles andere wird auf seine Spaltenlaenge gestutzt.
                $wert = $c === 'pat_blob'
                    ? pruef_pat_blob($m[$c], 'pat_blob', $pruef)
                    : $m[$c];
                $cols[] = $c; $vals[] = $wert;
            }
            /* In Backticks ueber `mf_liste_sql()`: In
             * `$cols` fliessen ueber `$extraCols` Namen, die noch dazukommen. */
            $sql = 'INSERT INTO missions (' . mf_liste_sql($cols) . ') VALUES ('
                 . implode(',', array_fill(0, count($cols), '?')) . ')';
            $pdo->prepare($sql)->execute($vals);
            $mid = (int)$pdo->lastInsertId();
            /* $werte['client_ref'] und nicht $m['client_ref']: Das ist die
             * Kennung, die TATSAECHLICH in der Zeile steht — die aus der
             * Datei oder die Ersatzkennung. Beide sollen wiederfindbar sein. */
            $merke('mission', $werte['client_ref'], $mid);
            $neueEinsaetze[$werte['client_ref']] = true;

            /* Abweichende Besatzung (`mission_crew`): Zeilen, nicht
             * Spalten. Nur belegte Rollen: `mission_crew` fuehrt Abweichungen,
             * keine Leerzeilen. */
            /* `loeschen => false` ueberall hier: Der Einsatz ist gerade erst
             * angelegt worden, es gibt nichts zu ersetzen. `ignorieren` bei der
             * Besatzung, weil ein doppelter Rollenschluessel im Paket den Lauf
             * nicht abbrechen soll. */
            $besatzung = [];
            foreach ((array)($m['crew'] ?? []) as $rc => $nm) {
                if (!array_key_exists((string)$rc, CREW_ROLES)) { continue; }
                if ($nm === null || trim((string)$nm) === '') { continue; }
                $besatzung[(string)$rc] = mb_substr(trim((string)$nm), 0, 120);
            }
            einsatz_besatzung_ersetzen($pdo, $mid, $besatzung,
                                       ['loeschen' => false, 'ignorieren' => true]);

            $rNamen = [];
            foreach (($m['resources'] ?? []) as $rname) {
                $rname = mb_substr(trim((string)$rname), 0, 120);
                if ($rname !== '') { $rNamen[] = $rname; }
            }
            einsatz_rettungsmittel_ersetzen($pdo, $mid, $rNamen, ['loeschen' => false]);
            // Phasen: Nummer 2 bis 9. Mehrfache Eintraege derselben Nummer
            // bleiben erhalten — sie sind Korrekturen (JSON-Vertrag 3).
            $neuePhasen = [];
            foreach (pruef_menge($m['phases'] ?? [], LIMIT_PHASEN, 'phases', $pruef) as $p) {
                if (!is_array($p)) { continue; }
                $nr   = pruef_phase($p['phase'] ?? null, 'phases.phase', $pruef);
                $wann = pruef_utc_oder_sql($p['occurred_at'] ?? null, 'phases.occurred_at', $pruef);
                if ($nr === null || $wann === null) { continue; }
                $neuePhasen[] = ['phase' => $nr, 'occurred_at' => $wann,
                                 'lat' => pruef_breite($p['lat'] ?? null, 'phases.lat', $pruef),
                                 'lon' => pruef_laenge($p['lon'] ?? null, 'phases.lon', $pruef)];
            }
            $neueReas = [];
            foreach (pruef_menge($m['resus'] ?? [], LIMIT_REA_SESSION, 'resus', $pruef) as $r) {
                if (!is_array($r)) { continue; }
                $rStart = pruef_utc_oder_sql($r['started_at'] ?? null, 'resus.started_at', $pruef);
                if ($rStart === null) { continue; }
                $ereignisse = [];
                foreach (pruef_menge($r['events'] ?? [], LIMIT_REA_EREIGN, 'resus.events', $pruef) as $e2) {
                    if (!is_array($e2)) { continue; }
                    $typ  = pruef_reanimationsart($e2['type'] ?? null, 'resus.events.type', $pruef);
                    $wann = pruef_utc_oder_sql($e2['occurred_at'] ?? null, 'resus.events.at', $pruef);
                    if ($typ === null || $wann === null) { continue; }
                    $ereignisse[] = [$typ, $wann];
                }
                $neueReas[] = ['started_at' => $rStart, 'events' => $ereignisse];
            }

            /* DIE EINSATZHUELLE. Orte der Phasen und Reanimation
             * liegen nur darin; die Phasen behalten Nummer und Zeit im
             * Klartext. Fuer beides gibt es keine Tabelle.
             *
             * Aus der Datei kommt sie, wenn der Browser sie gebaut hat oder
             * die Sicherung sie unlesbar mitbrachte (`ort_unlesbar`). Im
             * Demo-Reset baut der Server sie aus dem Klartext der Fixture.
             * Im Browserweg kann es Klartext hier nicht geben —
             * `edbak_klartext_fund()` haette die Nutzlast oben abgewiesen. */
            $huelle = pruef_huelle($m['ort_huelle'] ?? null, GH_EINSATZ_MAX_BYTE,
                                   'ort_huelle', $pruef);
            $klarOrt = $neueReas !== []
                || array_filter($neuePhasen, static fn($p) => $p['lat'] !== null || $p['lon'] !== null);
            if ($huelle === null && $klarOrt) {
                $sek = static fn(string $t): int => (int)strtotime($t . ' UTC');
                $huelle = gh_einsatz_verschliessen($sec1Holen(), $werte['client_ref'], $sek($startedAt),
                    array_map(static fn($p) => ['phase' => $p['phase'], 'ts' => $sek($p['occurred_at']),
                                                'lat' => $p['lat'], 'lon' => $p['lon']], $neuePhasen),
                    array_map(static fn($r) => ['ts' => $sek($r['started_at']),
                        'ereignisse' => array_map(static fn($e) => ['art' => $e[0], 'ts' => $sek($e[1])],
                                                  $r['events'])], $neueReas));
            }
            if ($huelle !== null) {
                $pdo->prepare('UPDATE missions SET ort_huelle = ? WHERE id = ? AND user_id = ?')
                    ->execute([$huelle, $mid, $userId]);
            }
            /* Ohne `lat`/`lon` — `einsatz_phasen_ersetzen()` schreibt nur
             * Nummer und Zeit. */
            einsatz_phasen_ersetzen($pdo, $mid, $neuePhasen, ['loeschen' => false]);
            /* ZWEI WEGE, UND DIE FASSUNG ENTSCHEIDET — nicht das Vorhandensein
             * eines `track`-Feldes. Eine Spur ohne Punkte saehe genauso aus
             * wie ein Verweis, und dann liefe eine Fassung-8-Datei still in
             * den Altweg und verloere alle Spuren. */
            if ($mitVerweisen) {
                if (isset($m['spur_ref'])) {
                    $spurKarte[(int)$m['spur_ref']] = ['art' => 'mission', 'id' => $mid,
                                                       'ref' => $werte['client_ref']];
                }
                $spurWiderspruch('mission', $m);
            } else {
                $spurSchreiben('mission', $mid, $m['track'] ?? [], $werte['client_ref']);
            }


            $stats['missions']++;
            if ($mGeloescht) { $stats['papierkorb']['einsaetze']++; }
        }

        /* Ruhesegmente.
         *
         * SIE ZAEHLEN IHRE GRUENDE JETZT MIT. Bisher erhoehte sich
         * hier nur `rests_skipped`, und in einem Fall auch `datum_oder_zeit` —
         * „bereits vorhanden" fiel unter den Tisch. Bei 95 Ruhesegmenten und
         * einem wiederholten Einspielen ist das die haeufigste Ursache
         * ueberhaupt, und sie war die einzige, die nicht dastand. */
        $rexists = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ? AND client_ref = ?');
        foreach (($data['rest_segments'] ?? []) as $r) {
            if (!is_array($r)) { $stats['rests_skipped']++; $grund['aufbau']++; continue; }
            $rexists->execute([$userId, (string)($r['client_ref'] ?? '')]);
            $vorhandenRId = $rexists->fetchColumn();
            if ($vorhandenRId) {
                // Wiederaufnahme, dieselbe Begruendung wie beim Einsatz oben.
                if (isset($r['spur_ref'])) {
                    $spurKarte[(int)$r['spur_ref']] = ['art' => 'rest',
                                                       'id' => (int)$vorhandenRId,
                                                       'ref' => trim((string)$r['client_ref'])];
                }
                $merke('rest', $r['client_ref'] ?? null, (int)$vorhandenRId);
                $stats['rests_skipped']++; $grund['bereits_vorhanden']++; continue;
            }
            // Wie beim Einsatz: ohne Diensttag kein Ruhe-Segment, und
            // die beiden Gruende dafuer werden getrennt gezaehlt.
            $altRDayId = isset($r['day_id']) ? (int)$r['day_id'] : 0;
            $rDayId    = $dayIdMap[$altRDayId] ?? 0;
            if ($rDayId === 0) {
                $stats['rests_skipped']++;
                $grund[$altRDayId > 0 ? 'tag_uebersprungen' : 'datum_oder_zeit']++;
                continue;
            }
            /* PRUEFSCHICHT — SIE FEHLTE HIER VOLLSTAENDIG.
             *
             * `started_at` und `ended_at` gingen roh ins INSERT, `client_ref`
             * ohne Laengengrenze. `rest_segments.started_at` ist
             * DATETIME NOT NULL und `client_ref` VARCHAR(64) NOT NULL: Ein
             * unbrauchbarer Zeitwert oder eine zu lange Kennung kostete damit
             * nicht die eine Zeile, sondern ueber die Ausnahme die GANZE
             * Wiederherstellung — der Aufrufer sah nur noch eine
             * Fehlermeldung. Beim Einsatz war genau diese Richtung im Review
             * ausdruecklich umgedreht worden (siehe Kommentar oben); die
             * Ruhesegmente sind damals uebersehen worden.
             *
             * Jetzt gilt hier dasselbe Muster: pruefen, im Zweifel die Zeile
             * ueberspringen und den Grund zaehlen. */
            $rRef = pruef_text($r['client_ref'] ?? null, 64, 'rest.client_ref', $pruef)
                    ?? ('bak-' . bin2hex(random_bytes(6)));
            $rStart = pruef_utc_oder_sql($r['started_at'] ?? null, 'rest.started_at', $pruef);
            if ($rStart === null) {
                $stats['rests_skipped']++; $grund['datum_oder_zeit']++; continue;
            }
            $rEnde = pruef_utc_oder_sql($r['ended_at'] ?? null, 'rest.ended_at', $pruef);

            // Loeschzustand nach denselben Regeln wie beim Einsatz,
            // einschliesslich der UND-Verknuepfung mit dem
            // Wert aus der Datei und der Ablehnung aktiver Eintraege an einem
            // geloeschten Zieltag.
            $rGeloescht = ($r['deleted_at'] ?? null) !== null;
            $rZielTagWeg = !empty($zieltagGeloescht[$rDayId]);
            $rMitTag    = ($rGeloescht && $rZielTagWeg
                           && (int)($r['deleted_with_day'] ?? 0) === 1) ? 1 : 0;
            if (!$rGeloescht && $rZielTagWeg) {
                $stats['rests_skipped']++; $grund['tag_im_papierkorb']++; continue;
            }
            $pdo->prepare('INSERT INTO rest_segments
                (user_id, client_ref, day_id, started_at, ended_at, final,
                 geraet_art, geraet_modell,
                 deleted_at, deleted_with_day)
                VALUES (?,?,?,?,?,?,?,?,?,?)')
                ->execute([$userId, $rRef, $rDayId, $rStart, $rEnde,
                           pruef_flag($r['final'] ?? 1),
                           edbak_geraet_art($r['geraet_art'] ?? null,
                                            'rest.geraet_art', $pruef),
                           pruef_text($r['geraet_modell'] ?? null, GERAET_MAX_MODELL,
                                      'rest.geraet_modell', $pruef),
                           $rGeloescht ? $loeschZeit : null, $rMitTag]);
            $rid = (int)$pdo->lastInsertId();
            $merke('rest', $rRef, $rid);
            /* Die Loeschvermerke (Nutzlast 13): ein Sperrvermerk ohne
             * Ziel an seiner Quelle. Der Zeitpunkt kommt aus der Datei, wie bei
             * den Schnitten. */
            foreach (pruef_menge($r['loeschungen'] ?? [], 1000, 'rest.loeschungen', $pruef) as $lv) {
                $von = is_array($lv) ? pruef_zahl($lv['von_ts'] ?? null, 0, 4294967295,
                                                  'rest.loeschungen.von_ts', $pruef) : null;
                $bis = is_array($lv) ? pruef_zahl($lv['bis_ts'] ?? null, 0, 4294967295,
                                                  'rest.loeschungen.bis_ts', $pruef) : null;
                if ($von === null || $bis === null || $bis < $von) {
                    $stats['schnitte']['verworfen']++; $grund['schnitt_werte']++; continue;
                }
                schnitt_vermerken($pdo, $userId, 'rest', $rid, null, (int)$von, (int)$bis,
                                  (int)(pruef_zahl($lv['n_punkte'] ?? 0, 0, 100000000,
                                                   'rest.loeschungen.n_punkte', $pruef) ?? 0),
                                  pruef_utc_oder_sql($lv['erstellt_am'] ?? null,
                                                     'rest.loeschungen.erstellt_am', $pruef));
                $stats['schnitte']['uebernommen']++;
            }
            if ($mitVerweisen) {
                if (isset($r['spur_ref'])) {
                    $spurKarte[(int)$r['spur_ref']] = ['art' => 'rest', 'id' => $rid, 'ref' => $rRef];
                }
                $spurWiderspruch('rest', $r);
            } else {
                $spurSchreiben('rest', $rid, $r['track'] ?? [], $rRef);
            }
            $stats['rests']++;
            if ($rGeloescht) { $stats['papierkorb']['ruhezeiten']++; }
        }

        /* ---- DRITTER DURCHGANG: die Sperrvermerke des Schnitts ------------
         *      (Nutzlast 9)
         *
         * ER LAEUFT NACH BEIDEN SCHLEIFEN UND IN DERSELBEN TRANSAKTION. Nach
         * beiden, weil ein Vermerk auf ZWEI Datensaetze zeigt — das Ziel
         * (einen Einsatz) und die Quelle (einen Einsatz ODER ein Segment).
         * Solange die Segmentschleife nicht durch ist, gibt es Quellen, die
         * es gleich geben wird. In derselben Transaktion, weil ein
         * halb gesetzter Sperrvermerk schlimmer waere als keiner: Er sperrt
         * einen Zeitraum, den niemand mehr zuordnen kann.
         *
         * DREI AUSGAENGE, und der Reihe nach:
         *
         *   1. DAS ZIEL WURDE NICHT ANGELEGT -> verworfen. Das trifft ALLE
         *      Abbruchgruende der Einsatzschleife und nicht nur einen: Ein
         *      Einsatz kann wegen eines kaputten Aufbaus, eines unbrauchbaren
         *      Datums, eines uebersprungenen oder eines geloeschten Diensttags
         *      liegenbleiben. Der Vermerk hat dann kein Ziel, und ein Vermerk
         *      ohne Ziel ist keiner.
         *
         *   2. DAS ZIEL STAND SCHON DA -> uebersprungen. Ein Restore darf
         *      keine Sperre wiederbeleben, die jemand im Zielkonto bewusst
         *      zurueckgenommen hat (das Rueckgaengig des Schnitts loescht sie).
         *      Und fuer einen unveraenderten Bestand stehen sie ohnehin.
         *
         *   3. Sonst wird geprueft und geschrieben -> uebernommen.
         *
         * JEDE ZEILE KOSTET NUR SICH SELBST (Web-8.0.0-Linie). Ein Vermerk mit
         * unbrauchbaren Werten faellt heraus und wird gezaehlt; er nimmt weder
         * die anderen Vermerke desselben Einsatzes mit noch die
         * Wiederherstellung.
         */
        foreach (($data['missions'] ?? []) as $m) {
            if (!is_array($m)) { continue; }
            $liste = $m['schnitte'] ?? null;
            if (!is_array($liste) || !$liste) { continue; }

            $zielRef = $refSchluessel($m['client_ref'] ?? null);
            $zielId  = $zielRef !== '' ? ($refKarte['mission'][$zielRef] ?? 0) : 0;
            if ($zielId === 0) {
                $stats['schnitte']['verworfen'] += count($liste);
                $grund['schnitt_ohne_ziel'] += count($liste);
                continue;
            }
            if (empty($neueEinsaetze[$zielRef])) {
                $stats['schnitte']['uebersprungen'] += count($liste);
                continue;
            }

            foreach (pruef_menge($liste, LIMIT_SCHNITTE, 'schnitte', $pruef) as $c) {
                if (!is_array($c)) {
                    $stats['schnitte']['verworfen']++; $grund['schnitt_aufbau']++; continue;
                }

                /* DIE QUELLART GEHT GEGEN EINE LISTE, NICHT ROH IN DIE SPALTE.
                 * `track_cuts.owner_type` ist ein ENUM; ein unbekannter Wert
                 * wird darin je nach `sql_mode` des Servers still zu einem
                 * Leerstring ODER wirft — und die Verbindung setzt kein
                 * `sql_mode` (db.php). Beides waere falsch: Der Leerwert
                 * ergaebe einen Vermerk, den weder `schnitte_lesen()` noch
                 * `schnitte_loeschen_quelle()` je wiederfaende; der Fehler
                 * kostete die ganze Wiederherstellung. Genau diese Falle
                 * steht in `update.php` bei der Migration 2026_09_04
                 * beschrieben — hier ist die zweite Stelle, an der sie
                 * zuschlagen koennte. */
                $art = (string)($c['quelle_art'] ?? '');
                if ($art !== 'mission' && $art !== 'rest') {
                    $stats['schnitte']['verworfen']++;
                    $pruef->melde('schnitte.quelle_art', 'weder mission noch rest');
                    $grund['schnitt_aufbau']++;
                    continue;
                }

                $qRef = $refSchluessel($c['quelle_ref'] ?? null);
                $quelleId = $qRef !== '' ? ($refKarte[$art][$qRef] ?? 0) : 0;
                /* NICHT IN DIESER DATEI — DANN IM KONTO. Ein Fenster kennt nur
                 * seine eigenen Eintraege; eine
                 * Quelle aus einem FRUEHEREN Fenster steht schon in der
                 * Datenbank, unter derselben Kennung. */
                if ($quelleId === 0 && $qRef !== '') {
                    $quelleId = edbak_schnitt_quelle_suchen($pdo, $userId, $art, $qRef);
                }
                if ($quelleId === 0 && $qRef !== '' && $offenZurueck) {
                    /* NOCH NICHT DA — NACHREICHEN, NICHT VERWERFEN.
                     * Die Sicherung schreibt alle Einsaetze vor allen
                     * Ruhesegmenten; ein Vermerk, dessen Quelle ein Segment
                     * ist, kommt deshalb fast immer VOR ihr an. Der Aufrufer
                     * sammelt diese Vermerke und reicht sie nach dem letzten
                     * Fenster an `edbak_schnitte_nachreichen()` — erst dann
                     * gilt eine fehlende Quelle als fehlend. Gezaehlt wird
                     * dort, nicht hier, damit kein Vermerk zweimal zaehlt. */
                    $stats['schnitte_offen'][] = ['ziel_ref' => $zielRef] + $c;
                    continue;
                }
                if ($quelleId === 0) {
                    /* DIE QUELLE IST NICHT AUFLOESBAR: Sie stand nicht in der
                     * Datei, oder sie ist beim Einspielen liegengeblieben. Die
                     * Kennung wird GENANNT — ohne sie liesse sich nicht
                     * nachsehen, welcher Zeitraum jetzt ungesperrt ist. */
                    $stats['schnitte']['verworfen']++;
                    $pruef->melde('schnitte.quelle_ref',
                                  'Quelle nicht gefunden: ' . ($qRef !== '' ? $qRef : '(leer)'));
                    $grund['schnitt_ohne_quelle']++;
                    continue;
                }

                $w = edbak_schnitt_werte($c, $pruef);
                if ($w === null) {
                    $stats['schnitte']['verworfen']++; $grund['schnitt_werte']++;
                    continue;
                }
                schnitt_vermerken($pdo, $userId, $art, $quelleId, $zielId,
                                  $w['von'], $w['bis'], $w['n'], $w['wann']);
                $stats['schnitte']['uebernommen']++;
            }
        }

        // Hinweis: Bis Formatversion 1 enthielt das Backup einen Block
        // `pat_module` mit den Schluessel-Huellen des Ursprungskontos, der hier
        // uebernommen wurde. Seit Version 2 liegen die geschuetzten Angaben im
        // (selbst verschluesselten) Container als Klartext und werden vom
        // Browser mit dem Schluessel des ZIELKONTOS verschluesselt — fremde
        // Huellen werden nie mehr geschrieben.

        // Aufschluesselung mitgeben: Der Aufrufer zeigt sie an.
        $stats['skipped_reasons'] = array_filter($grund);
        $stats['rejected'] = $pruef->nachUrsache();

        if ($eigeneTransaktion) { $pdo->commit(); }
    } catch (Throwable $ex) {
        if ($eigeneTransaktion) { $pdo->rollBack(); }
        throw $ex;
    }

    /* Einsatzort-Hoehe: Die rechnet nur das direkte Einspielen, beim
     * Verschliessen der Spur (`$spurSchreiben`); ein Fehlschlag kostet nur
     * die Anzeige und wird gezaehlt. */

    if ($mitVerweisen) { $stats['spur_karte'] = $spurKarte; }
    /* DIE ZUORDNUNG DER DIENSTTAGE GEHT ZURUECK, damit die Fenster
     * der Eintraege wissen, an welchen Tag sie gehoeren. Nur, wenn dieser Lauf
     * Diensttage vor sich hatte — sonst waere es die Zuordnung, die er selbst
     * mitbekommen hat, und die kennt der Aufrufer schon. */
    if (!$nurEintraege && isset($data['days'])) { $stats['day_map'] = $dayIdMap; }
    return $stats;
}

/**
 * Die Werte eines Sperrvermerks aus einer Datei, geprueft — oder null.
 *
 * Eine eigene Funktion, weil zwei Stellen sie brauchen: der
 * Fensterlauf in `edbak_restore()` und das Nachreichen danach. Zwei Kopien
 * derselben Pruefung wuerden auseinanderlaufen.
 *
 * Die Zeiten sind Sekunden seit 1970 und stehen als BIGINT in der Tabelle.
 * Die Obergrenze ist der 01.01.2100 — reichlich und trotzdem eine Grenze;
 * ein Wert daneben ist kein Zeitpunkt, sondern Unfug.
 *
 * `erstellt_am` REIST MIT, anders als `deleted_at`. Ein Vermerk
 * sagt, WANN geschnitten wurde — ein Ereignis der Vergangenheit. Ist der Wert
 * unbrauchbar, entsteht der Vermerk trotzdem, dann eben mit dem Zeitpunkt des
 * Einspielens: Ein Komfortwert darf keine Sperre kosten.
 *
 * @return array{von:int, bis:int, n:int, wann:?string}|null
 */
function edbak_schnitt_werte(array $c, Pruefliste $pruef): ?array
{
    $von = pruef_zahl($c['von_ts'] ?? null, 0, 4102444800, 'schnitte.von_ts', $pruef);
    $bis = pruef_zahl($c['bis_ts'] ?? null, 0, 4102444800, 'schnitte.bis_ts', $pruef);
    $nPk = pruef_zahl($c['n_punkte'] ?? null, 1, LIMIT_TRACKPUNKTE_SPUR,
                      'schnitte.n_punkte', $pruef);
    if ($von === null || $bis === null || $nPk === null || $bis < $von) {
        if ($bis !== null && $von !== null && $bis < $von) {
            $pruef->melde('schnitte.bis_ts', 'liegt vor von_ts');
        }
        return null;
    }
    $wann = pruef_utc_oder_sql($c['erstellt_am'] ?? null, 'schnitte.erstellt_am', $pruef);
    return ['von' => (int)$von, 'bis' => (int)$bis, 'n' => (int)$nPk, 'wann' => $wann];
}

/**
 * Die Kennnummer einer Quelle (Einsatz oder Ruhesegment) im Zielkonto, ueber
 * ihre `client_ref` — 0, wenn es sie nicht gibt.
 *
 * Die Kennung ist je Konto eindeutig und reist mit jeder Sicherung; dieselbe
 * Kennung im selben Konto ist derselbe Eintrag. Der Papierkorb zaehlt mit:
 * Ein Vermerk an einer geloeschten Quelle ist einer, den das Wiederherstellen
 * der Quelle braucht.
 */
function edbak_schnitt_quelle_suchen(PDO $pdo, int $userId, string $art, string $ref): int
{
    $tabelle = match ($art) {
        'mission' => 'missions',
        'rest'    => 'rest_segments',
        default   => throw new InvalidArgumentException('art: ' . $art),
    };
    $q = $pdo->prepare("SELECT id FROM $tabelle WHERE user_id = ? AND client_ref = ?");
    $q->execute([$userId, $ref]);
    $id = $q->fetchColumn();
    return $id === false ? 0 : (int)$id;
}

/** Wie viele offene Vermerke ein Nachreichen-Aufruf hoechstens nimmt. */
const EDBAK_SCHNITTE_NACHREICHEN_MAX = 1000;

/**
 * Sperrvermerke nachreichen, deren Quelle beim Einspielen ihres Fensters noch
 * nicht da war.
 *
 * WARUM ES DAS GIBT. Die Sicherung schreibt alle Einsaetze vor allen
 * Ruhesegmenten (`edbak_build()`), und das Einspielen geht in Fenstern. Baute
 * jedes Fenster seine Karte Kennung → Kennnummer neu, wuerde ein Vermerk,
 * dessen Ruhesegment spaeter kommt, verworfen — gemessen am Messstand 147
 * von 147. Der herausgeschnittene Zeitraum waere danach im Segment wieder
 * sichtbar.
 *
 * WAS SIE NIMMT: die Eintraege, die `edbak_restore(…, offenZurueck: true)` in
 * `schnitte_offen` zurueckgab — der Vermerk aus der Datei, dazu `ziel_ref`.
 * Sie kommen ueber den Browser zurueck und werden deshalb NEU geprueft, nicht
 * geglaubt: Ziel und Quelle werden im Konto gesucht, die Werte gehen durch
 * dieselbe Pruefung wie im Fenster. Was der Browser schicken koennte, kann
 * die NutzerIn ohnehin — einen Zeitraum ihrer eigenen Spur sperren.
 *
 * WAS JETZT NOCH FEHLT, FEHLT: Eine Quelle, die auch nach dem letzten Fenster
 * nicht da ist, stand nicht in der Datei oder blieb liegen; der Vermerk wird
 * verworfen und mit Kennung genannt, wie im Fenster.
 *
 * @return array{schnitte: array{uebernommen:int, verworfen:int},
 *               skipped_reasons: array<string,int>, rejected: array<string,int>}
 */
function edbak_schnitte_nachreichen(int $userId, array $offen): array
{
    $pdo = db();
    $pruef = new Pruefliste();
    $stats = ['uebernommen' => 0, 'verworfen' => 0];
    $grund = ['schnitt_aufbau' => 0, 'schnitt_ohne_ziel' => 0,
              'schnitt_ohne_quelle' => 0, 'schnitt_werte' => 0];
    $ref = static fn($r): string => is_string($r) ? trim($r) : '';

    /* Was ueber der Grenze liegt, zaehlt als verworfen statt still zu fehlen;
     * die Aufrufer schicken in Stuecken zu hoechstens dieser Zahl. */
    if (count($offen) > EDBAK_SCHNITTE_NACHREICHEN_MAX) {
        $stats['verworfen'] += count($offen) - EDBAK_SCHNITTE_NACHREICHEN_MAX;
        $grund['schnitt_aufbau'] += count($offen) - EDBAK_SCHNITTE_NACHREICHEN_MAX;
    }
    db_transaktion($pdo, function (PDO $pdo) use ($userId, $offen, $pruef, $ref,
                                                &$stats, &$grund): void {
        foreach (pruef_menge($offen, EDBAK_SCHNITTE_NACHREICHEN_MAX,
                             'schnitte_offen', $pruef) as $c) {
            if (!is_array($c)) { $stats['verworfen']++; $grund['schnitt_aufbau']++; continue; }
            $art = (string)($c['quelle_art'] ?? '');
            if ($art !== 'mission' && $art !== 'rest') {
                $stats['verworfen']++; $grund['schnitt_aufbau']++;
                $pruef->melde('schnitte.quelle_art', 'weder mission noch rest');
                continue;
            }
            $zRef = $ref($c['ziel_ref'] ?? null);
            $zielId = $zRef !== '' ? edbak_schnitt_quelle_suchen($pdo, $userId, 'mission', $zRef) : 0;
            if ($zielId === 0) {
                $stats['verworfen']++; $grund['schnitt_ohne_ziel']++;
                continue;
            }
            $qRef = $ref($c['quelle_ref'] ?? null);
            $quelleId = $qRef !== '' ? edbak_schnitt_quelle_suchen($pdo, $userId, $art, $qRef) : 0;
            if ($quelleId === 0) {
                $stats['verworfen']++; $grund['schnitt_ohne_quelle']++;
                $pruef->melde('schnitte.quelle_ref',
                              'Quelle nicht gefunden: ' . ($qRef !== '' ? $qRef : '(leer)'));
                continue;
            }
            $w = edbak_schnitt_werte($c, $pruef);
            if ($w === null) { $stats['verworfen']++; $grund['schnitt_werte']++; continue; }
            schnitt_vermerken($pdo, $userId, $art, $quelleId, $zielId,
                              $w['von'], $w['bis'], $w['n'], $w['wann']);
            $stats['uebernommen']++;
        }
    });
    return ['schnitte' => $stats, 'skipped_reasons' => array_filter($grund),
            'rejected' => $pruef->nachUrsache()];
}

/**
 * Die Spuren einer Sicherung in ein Konto schreiben (verschlossen).
 *
 * WOFUER ES DIESE FUNKTION GIBT. `api/backup_spuren_restore.php` und das
 * Admin-Backup mit seinem mehrteiligen Rohpaket brauchen denselben Ablauf — und
 * ein zweiter Weg waere
 * ein zweiter Ort, an dem die drei Dinge unten zu vergessen sind. Der
 * Endpunkt ist eine duenne Schale um diese Funktion.
 *
 * EINE FORM JE EINTRAG: `stuecke`, verschlossene Stuecke, wie sie der
 * Browser baut (`EdOrtsdaten.stueckeBauen()`) oder wie sie in einer
 * Kontosicherung liegen. Ein `blob` (SPUR1 im Klartext) wird
 * abgelehnt.
 *
 * DREI DINGE, DIE HIER PASSIEREN MUESSEN:
 *
 * 1. EIGENTUM PRUEFEN. Jede Kennung wird gegen `user_id` geprueft, auch wenn
 *    sie aus einer Antwort dieses Servers stammt. Wer sich darauf verlaesst,
 *    dass nur zurueckkommt, was hinausging, hat einen Weg gebaut, der fremde
 *    Spuren ueberschreibt.
 *
 * 2. PRUEFEN, BEVOR ES LIEGT: Stuecke ueber `pruef_spurstueck()` und
 *    `pruef_spurstueck_gepackt()`. CLAUDE.md 4: „alle Schreibwege, ohne
 *    Ausnahme".
 *
 * 3. VORHANDENE UEBERSPRINGEN. Eine abgebrochene Wiederherstellung soll sich
 *    fortsetzen lassen, ohne doppelt zu schreiben. Ein Stueck, dessen `seq_from` schon liegt,
 *    laesst `spur2_stueck_schreiben()` aus. Ein Stueck, das NICHT schon liegt,
 *    wird geschrieben, auch wenn andere derselben Spur schon da sind: Der
 *    Browser teilt eine lange Spur auf mehrere Anfragen.
 *
 * Rumpf je Eintrag: {owner_type, owner_id, stuecke: [{seq_from, punkte,
 * ts_von, ts_bis, huelle, n_original?, stufe?}], stufe?, fortsetzung?}.
 * Rueckgabe: {geschrieben, uebersprungen, abgelehnt[]}.
 */
function edbak_spuren_schreiben(PDO $pdo, int $userId, array $liste): array
{
    /* Eigentum in EINER Abfrage je Art, nicht je Spur. */
    $wunsch = ['mission' => [], 'rest' => []];
    foreach ($liste as $s) {
        $art = (string)($s['owner_type'] ?? '');
        $id  = (int)($s['owner_id'] ?? 0);
        if (isset($wunsch[$art]) && $id > 0) { $wunsch[$art][$id] = true; }
    }
    $eigen = ['mission' => [], 'rest' => []];
    foreach ($wunsch as $art => $ids) {
        if (!$ids) { continue; }
        $tabelle = $art === 'mission' ? 'missions' : 'rest_segments';
        foreach (sql_in_bloecken($pdo,
            "SELECT id, client_ref FROM `$tabelle` WHERE user_id = ? AND id IN ({IDS})",
            array_keys($ids), [$userId]) as $r) {
            $eigen[$art][(int)$r['id']] = (string)$r['client_ref'];
        }
    }

    $geschrieben = 0; $uebersprungen = 0; $abgelehnt = [];
    $ablehnen = static function (string $art, int $id, string $grund) use (&$abgelehnt): void {
        $abgelehnt[] = ['owner_type' => $art, 'owner_id' => $id, 'grund' => $grund];
    };

    foreach ($liste as $s) {
        $art = (string)($s['owner_type'] ?? '');
        $id  = (int)($s['owner_id'] ?? 0);
        if (!isset($eigen[$art][$id])) {
            /* Fremd, geloescht oder erfunden — dieselbe Antwort, wie ueberall
             * in dieser Anwendung: Ein eigener Code verriete, welcher Fall es
             * ist. Gezaehlt wird er trotzdem, sonst faellt ein Fehler nicht
             * auf. */
            $ablehnen($art, $id, 'nicht vorhanden');
            continue;
        }
        $stuecke = [];
        if (isset($s['stuecke']) && is_array($s['stuecke'])) {
            /* DIE STUFE JE STUECK. Ein gepacktes Stueck
             * (Stufe 2, 3) traegt seine Stufe selbst und laeuft durch
             * `pruef_spurstueck_gepackt()`; daneben koennen Geraetestuecke
             * liegen, die nach dem Zusammenfuegen ankamen. Ohne Stufe am Stueck
             * (aeltere Pakete) zaehlt es als Stufe 1 — eine Stufe 3 in
             * Zeilenform gab es nur dort, und ausgeliefert wurde sie nie. */
            $kaputt = false;
            foreach ($s['stuecke'] as $t) {
                $stufeT = is_array($t) ? (int)($t['stufe'] ?? 0) : 0;
                if ($stufeT >= SPUR2_STUFE_GEPACKT) {
                    $st = pruef_spurstueck_gepackt($t, 'spur_stuecke');
                    if ($st === null) { $kaputt = true; break; }
                    $stuecke[] = $st;
                    continue;
                }
                $st = pruef_spurstueck($t, 'spur_stuecke');
                $nO = is_array($t) ? (int)($t['n_original'] ?? 0) : 0;
                if ($st === null || ($nO !== 0 && $nO < $st['punkte'])) { $kaputt = true; break; }
                $st['n_original'] = $st['punkte'];
                $st['stufe'] = SPUR2_STUFE_GERAET;
                $stuecke[] = $st;
            }
            if ($kaputt || !$stuecke) { $ablehnen($art, $id, 'Stueck unvollstaendig'); continue; }
        } elseif (isset($s['blob'])) {
            $ablehnen($art, $id, 'klartext');
            continue;
        } else {
            $ablehnen($art, $id, 'keine Stuecke');
            continue;
        }

        $neu = 0;
        foreach ($stuecke as $st) {
            $neu += spur2_stueck_schreiben($pdo, $art, $id, $st['seq_from'], $st['punkte'],
                                           $st['ts_von'], $st['ts_bis'], $st['huelle'],
                                           $st['stufe'], $st['n_original']);
        }
        /* Eine Fortsetzung (der Browser hat eine lange Spur geteilt) zaehlt
         * nicht noch einmal — die Spur ist mit ihrem ersten Teil gezaehlt. */
        if (empty($s['fortsetzung'])) {
            if ($neu > 0) { $geschrieben++; } else { $uebersprungen++; }
        }
    }

    return ['geschrieben' => $geschrieben, 'uebersprungen' => $uebersprungen,
            'abgelehnt' => $abgelehnt];
}

