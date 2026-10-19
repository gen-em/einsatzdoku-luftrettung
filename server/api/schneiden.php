<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';   // liefert $userId
require_once __DIR__ . '/../validate_lib.php';
require_once __DIR__ . '/../einsatz_lib.php';
require_once __DIR__ . '/../mission_fields_lib.php';   // mf_spalten() (Schritt 15/AP6)
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
 *     phasen: { "3": 'HH:MM'|null, "4": ..., "7": ... },   optional
 *     client_ref: 'cut-<16 Hex>',  seit S11-05c: die Kennung des neuen
 *                                  Einsatzes, an die die Stuecke gebunden sind
 *     stuecke: [ {seq_from, punkte, ts_von, ts_bis, huelle}, … ] }
 *
 *   { action: 'rueckgaengig', mission_id: 42 }
 *
 *   { action: 'loeschen',          seit S11-05c (Backlog Nr. 161)
 *     rest_id, beginn, ende, beginn_tag, ende_tag   wie oben
 *     stand:   [[seq_from, punkte], …]   was der Browser gelesen hat
 *     stuecke: [ … ]                     die uebrigen Punkte, neu verschlossen
 *     geloescht: 37 }                    wie viele Punkte wegfallen
 *
 * SEIT S11-05c SCHNEIDET DER BROWSER (E-S11-47). Der Server sieht die Punkte
 * eines Ruhesegments nicht mehr — sie liegen verschlossen in `track_stuecke`.
 * Beim Schneiden oeffnet der Browser das Segment, nimmt die Punkte im Zeitraum
 * und verschliesst sie als Spur des neuen Einsatzes; das Segment bleibt, wie
 * es ist, und der Zeitraum wird dort gesperrt (`track_cuts`, E-S11-37) — beim
 * Oeffnen fallen die Punkte darin weg. Die Ruecknahme loescht Einsatz und
 * Vermerk; die Punkte waren im Segment nie fort. Physisch fallen die
 * doppelten Punkte erst, wenn der Wartungslauf (S11-07) das Segment neu
 * packt. Bis Web 22.1.0 verschob der Server die Punkte selbst
 * (`spur_teilen()`); seit S11-04 fand er dabei keine mehr und antwortete
 * „kein einziger Punkt" (F-S11-50). Seit Web 23 gibt es die Funktion nicht
 * mehr (S11-12).
 *
 * EIN SCHNITT VON VOR WEB 22.2.0 LAESST SICH NICHT MEHR ZURUECKNEHMEN
 * (F-S11-94). Er hat die Punkte aus dem Segment VERSCHOBEN; sie liegen seit
 * dem Einmalwerkzeug verschlossen am Einsatz, und das Segment hat sie nicht
 * mehr. Ein Rueckgaengig loeschte den Einsatz samt Punkten — sie waeren fort.
 * Erkannt wird ein solcher Schnitt an der Zeit: aelter als der Vermerk der
 * Migration `2026_10_15_loeschvermerk`, mit der S11-05c auf die Anlage kam.
 *
 * LOESCHEN IST ENDGUELTIG (Nr. 161). Dort verschliesst der Browser die
 * Punkte AUSSERHALB des Zeitraums neu, und der Server ersetzt die Stuecke des
 * Segments — nur, wenn sie noch so liegen, wie der Browser sie gelesen hat
 * (`spur2_tauschen()`). Ein Sperrvermerk ohne Einsatz haelt eine erneute
 * Lieferung desselben Zeitraums auf. Rueckfrage im Browser, Zeile im
 * Protokoll.
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
 * `manual-<userId>`, `uhr_gesperrt = 1`, `client_ref` mit Praefix — woertlich
 * wie in `einsatz_form.php`. Das ist kein Zierrat: An diesen Merkmalen haengt, ob
 * der Einsatz durch Backup, Export und Papierkorb kommt (R24), und ob
 * `ingest.php` seine Phasen spaeter noch anfasst.
 *
 * SEINE HERKUNFT IST SEIT WEB 14.0.0 `schnitt` UND NICHT MEHR `manual`
 * (R64, E-R64-06). `uhr_gesperrt = 1` bleibt davon unberuehrt — das ist der
 * Schutzschalter gegen das Ueberschreiben durch die Uhr, nicht eine Aussage
 * darueber, wer den Einsatz angelegt hat. Beides zusammenzuwerfen war der
 * Fehler: Ein geschnittener Einsatz ist gerade NICHT von Hand eingegeben, er
 * ist aus einer aufgezeichneten Spur herausgeschnitten.
 *
 * UND ER ERBT ART UND MODELL DER QUELLE. Das virtuelle Geraet weiss nichts;
 * das Segment, aus dem geschnitten wurde, traegt seine Momentaufnahme seit
 * R64 selbst. Am geschnittenen Einsatz steht damit beides: DASS er durch
 * einen Schnitt entstand, und WELCHES Geraet die Spur aufgezeichnet hat, die
 * er jetzt fuehrt.
 *
 * WAS ER NICHT TUT: Er fuellt keine Einsatzfelder. Einsatzort, Alter und
 * Diagnose sind Ende-zu-Ende-verschluesselt und entstehen im Browser; ein
 * Endpunkt, der sie annaehme, brauechte Klartext. Der geschnittene Einsatz ist
 * deshalb ein leerer Einsatz mit Zeiten, Phasen und Spur — den Rest traegt
 * die Bedienerin im Formular nach, das dafuer da ist.
 */

/** Die drei Phasen, die der Schneide-Bereich als Abkuerzung anbietet (A0). */
const SCHNITT_PHASEN = [3, 4, 7];

/* ---- Schneiden ------------------------------------------------------------ */

/**
 * Segment und Zeitraum aus der Anfrage — fuer Schneiden und Loeschen gleich:
 * das Segment dieses Kontos, nicht im Papierkorb, Beginn vor Ende, beides als
 * Ortszeit des Diensttags. Bricht mit der Antwort ab, wenn etwas fehlt.
 *
 * @return array{seg:array,tag:string,beginn:string,ende:string,von:int,bis:int}
 */
function schnitt_segment_und_zeit(array $b, int $userId, Pruefliste $pruef): array
{
    $pdo = db();

    $restId = pruef_zahl($b['rest_id'] ?? null, 1, PHP_INT_MAX, 'rest_id', $pruef);
    if ($restId === null) { json_out(['error' => 'eingabe',
        'meldung' => 'Kein Ruhesegment angegeben.'], 400); }

    /* DAS SEGMENT UND SEIN DIENSTTAG IN EINER ABFRAGE, und beides mit
     * `user_id` gebunden. Ein Schnitt am Segment einer fremden Person waere
     * der schwerste Fehler, den dieser Endpunkt machen kann; die Bedingung
     * gehoert deshalb in die Abfrage und nicht in eine Pruefung danach. */
    $q = $pdo->prepare('SELECT r.id, r.day_id, r.started_at, r.ended_at,
                               r.geraet_art, r.geraet_modell, r.client_ref,
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

    return ['seg' => $seg, 'tag' => $tag, 'beginn' => $beginn, 'ende' => $ende,
            'von' => (int)strtotime($beginn . ' UTC'), 'bis' => (int)strtotime($ende . ' UTC')];
}

/**
 * Die Stuecke aus dem Browser pruefen (`pruef_spurstueck()`, dieselbe Regel wie
 * im Eingang). `$imZeitraum`: jedes Stueck muss ganz in [von, bis] liegen —
 * beim Schneiden; beim Loeschen darf keines ganz darin liegen.
 *
 * @return list<array{seq_from:int,punkte:int,ts_von:int,ts_bis:int,huelle:string}>
 */
function schnitt_stuecke(array $b, int $von, int $bis, bool $imZeitraum, Pruefliste $pruef): array
{
    $roh = $b['stuecke'] ?? [];
    if (!is_array($roh) || count($roh) > LIMIT_TRACKPUNKTE_SPUR) {
        json_out(['error' => 'eingabe', 'meldung' => 'Die GPS-Daten kamen nicht vollständig an.'], 400);
    }
    $aus = []; $summe = 0;
    foreach (array_values($roh) as $i => $t) {
        /* EIN GEPACKTES STUECK (Stufe 2, 3) nur beim Loeschen (S11-07,
         * E-S11-74): Der Browser hat ein zusammengefuegtes oder ausgeduenntes
         * Segment geoeffnet und verschliesst den Rest in derselben Stufe. Ein
         * herausgeschnittener Einsatz faengt mit Geraetestuecken an. */
        $st = is_array($t) && (int)($t['stufe'] ?? 1) >= SPUR2_STUFE_GEPACKT && !$imZeitraum
            ? pruef_spurstueck_gepackt($t, 'stuecke[' . $i . ']', $pruef)
            : pruef_spurstueck($t, 'stuecke[' . $i . ']', $pruef);
        if ($st === null) {
            json_out(['error' => 'eingabe', 'meldung' => 'Ein Stück der GPS-Daten ist unbrauchbar: '
                      . ($pruef->text() ?: 'Stück unvollständig') . '. Es wurde nichts geändert.'], 400);
        }
        $drin = $st['ts_von'] >= $von && $st['ts_bis'] <= $bis;
        if ($imZeitraum ? !$drin : $drin) {
            json_out(['error' => 'eingabe', 'meldung' => 'Die GPS-Daten passen nicht zum gewählten '
                      . 'Zeitraum. Bitte die Seite neu laden. Es wurde nichts geändert.'], 400);
        }
        $summe += $st['punkte'];
        $aus[] = $st;
    }
    if ($summe > LIMIT_TRACKPUNKTE_SPUR) {
        json_out(['error' => 'eingabe', 'meldung' => 'Zu viele Punkte für eine Aufzeichnung.'], 400);
    }
    return $aus;
}

function schnitt_ausfuehren(array $b, int $userId): never
{
    $pdo = db();
    $pruef = new Pruefliste();
    ['seg' => $seg, 'tag' => $tag, 'beginn' => $beginn, 'ende' => $ende,
     'von' => $vonTs, 'bis' => $bisTs] = schnitt_segment_und_zeit($b, $userId, $pruef);

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

    /* ---- Kennung und Stuecke aus dem Browser (S11-05c) -------------------
     *
     * Die Stuecke sind an die Kennung des NEUEN Einsatzes gebunden (Art
     * `spur`, Konzept 3.2); der Browser vergibt sie deshalb, bevor es die
     * Zeile gibt. `cut-` und 16 Hexziffern — dasselbe Praefix wie bisher. */
    $ref = (string)($b['client_ref'] ?? '');
    if (!preg_match('/^cut-[0-9a-f]{16}$/', $ref)) {
        json_out(['error' => 'eingabe', 'meldung' => 'Die Kennung des neuen Einsatzes fehlt. '
                  . 'Bitte die Seite neu laden.'], 400);
    }
    $stuecke = schnitt_stuecke($b, $vonTs, $bisTs, true, $pruef);

    /* EIN SCHNITT OHNE PUNKTE IST KEIN SCHNITT.
     *
     * Er entstuende bei einem zweiten Schnitt ueber denselben Bereich
     * oder ueber eine Aufzeichnungsluecke. Was dabei herauskaeme, waere
     * ein leerer Einsatz — und einer, den die Ruecknahme nicht von einem
     * nachgetragenen unterscheiden koennte. Der Browser weiss es zuerst
     * (er hat die Punkte geoeffnet) und schickt dann keine Stuecke; die
     * Pruefung steht trotzdem hier, weil nur hier entschieden wird. Wer
     * trotzdem einen Einsatz zu dieser Zeit will, traegt ihn nach. */
    if (!$stuecke) {
        json_out(['error' => 'leer',
                  'meldung' => 'In diesem Zeitraum liegt kein einziger '
                             . 'Punkt — entweder ist er schon geschnitten, '
                             . 'oder das Gerät hat dort nicht aufgezeichnet. '
                             . 'Es ist nichts entstanden. Für einen Einsatz '
                             . 'ohne GPS-Daten ist „Nachtragen" der richtige Weg.'], 409);
    }
    $genommen = array_sum(array_column($stuecke, 'punkte'));

    /* ---- Und jetzt in EINEM Zug ------------------------------------------ */
    try {
        $misId = db_transaktion($pdo, function (PDO $pdo) use ($userId, $seg, $ref, $beginn, $ende,
                                                              $phasen, $stuecke, $vonTs, $bisTs,
                                                              $genommen): int {
        $devId = geraet_virtuell_sicherstellen($pdo, $userId);
        /* SPALTEN AUS DEM REGISTER (Schritt 15/AP6, E-ZE-22), Zweck
         * `schnitt_neu`. DREI WERTE SIND FEST und stehen deshalb als Literal
         * in der Werteliste, nicht als Fragezeichen: `final` und
         * `uhr_gesperrt` sind 1 (ein geschnittener Einsatz ist fertig und
         * von Hand entstanden), `origin` ist 'schnitt'. Die Zuordnung steht
         * in `$fest` und wird beim Bauen nachgeschlagen — so bleibt sie an
         * die Spalte gebunden und nicht an die Stelle im Satz. */
        $fest = ['final' => '1', 'uhr_gesperrt' => '1', 'origin' => "'schnitt'"];
        $sp   = mf_spalten('schnitt_neu', '', false);
        $wert = array_map(static fn(string $c): string => $fest[$c] ?? '?', $sp);
        $pdo->prepare('INSERT INTO missions
                         (' . mf_spalten_sql('schnitt_neu', '', false) . ')
                       VALUES (' . implode(',', $wert) . ')')
            ->execute([$userId, $devId, $ref, (int)$seg['day_id'],
                       $beginn, $ende, $seg['geraet_art'], $seg['geraet_modell']]);
        $misId = (int)$pdo->lastInsertId();

        /* `loeschen => false`: Der Einsatz ist eine Zeile alt, es gibt nichts
         * zu ersetzen. Die Phasen tragen keine Koordinate, wie bisher. */
        $neuePhasen = [];
        foreach ($phasen as $ph => $wann) {
            $neuePhasen[] = ['phase' => $ph, 'occurred_at' => $wann];
        }
        einsatz_phasen_ersetzen($pdo, $misId, $neuePhasen, ['loeschen' => false]);

        /* DIE SPUR DES EINSATZES: die Stuecke, die der Browser aus den Punkten
         * im Zeitraum verschlossen hat. Das Segment bleibt unberuehrt; der
         * Vermerk darunter sperrt den Zeitraum dort (E-S11-47). */
        foreach ($stuecke as $st) {
            spur2_stueck_schreiben($pdo, 'mission', $misId, $st['seq_from'], $st['punkte'],
                                   $st['ts_von'], $st['ts_bis'], $st['huelle']);
        }
        schnitt_vermerken($pdo, $userId, 'rest', (int)$seg['id'], $misId,
                          $vonTs, $bisTs, $genommen);

        /* Der Diensttag muss den Einsatz umschliessen (JSON-Vertrag 4.4) —
         * dieselbe Regel wie beim Nachtragen im Formular. */
        dt_zeitraum_fortschreiben($pdo, (int)$seg['day_id'], $beginn, $ende);
        return $misId;
        });
    } catch (Throwable $ex) {
        json_fehler($ex, 'schneiden');
    }

    /* Hier stand bis Web 22.1.0 `compute_site_elevation()`. Der Server sieht die
     * Spur nicht mehr; die Hoehe des Einsatzorts entsteht im Browser — beim
     * Speichern im Formular (S11-05b) und im Wartungslauf (S11-07). */
    $antwort = ['ok' => true, 'mission_id' => $misId, 'genommen' => $genommen];
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

    if (einsatz_laden($misId, $userId, ['spalten' => 'id, day_id']) === null) {
        json_out(['error' => 'nicht_gefunden',
                  'meldung' => 'Diesen Einsatz gibt es nicht (mehr).'], 404);
    }

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
              ['mission_resources', 'weitere Rettungsmittel']] as [$tab, $wort]) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM `$tab` WHERE mission_id = ?");
        $c->execute([$misId]);
        if ((int)$c->fetchColumn() > 0) { $haengt[] = $wort; }
    }
    $c = $pdo->prepare('SELECT edited, pat_blob IS NOT NULL AS hatPat,
                               ort_huelle IS NOT NULL AS hatOrt FROM missions WHERE id = ?');
    $c->execute([$misId]);
    $m = $c->fetch(PDO::FETCH_ASSOC);
    if ((int)($m['edited'] ?? 0) === 1) { $haengt[] = 'Änderungen im Einsatzformular'; }
    if ((int)($m['hatPat'] ?? 0) === 1) { $haengt[] = 'geschützte Angaben'; }
    /* Die Reanimation liegt in der Einsatzhuelle (S11-05c; bis Web 22.7.0
     * zaehlte hier zusaetzlich `resus_sessions`); ein geschnittener Einsatz
     * hat keine, bis jemand ihn im Formular speichert. */
    if ((int)($m['hatOrt'] ?? 0) === 1) { $haengt[] = 'Phasenorte oder eine Reanimation'; }

    if ($haengt) {
        json_out(['error' => 'nicht_leer',
                  'meldung' => 'Am Einsatz hängt inzwischen ' . implode(', ', $haengt)
                             . '. Rückgängig würde das mitlöschen — bitte den Einsatz '
                             . 'über den Papierkorb löschen, wenn er weg soll.'], 409);
    }

    /* DER ALTE SCHNITT (F-S11-94, siehe Kopf). Ohne Vermerk der Migration
     * (eine Anlage vor Web 22.2.0) gibt es nur alte Schnitte. */
    $q = $pdo->prepare('SELECT applied_at FROM schema_migrations WHERE id = ?');
    $q->execute(['2026_10_15_loeschvermerk']);
    $s11Seit = $q->fetchColumn();
    foreach ($schnitte as $sn) {
        if ($s11Seit === false || $sn['erstellt_am'] < (string)$s11Seit) {
            json_out(['error' => 'altschnitt',
                      'meldung' => 'Dieser Einsatz wurde vor der Verschlüsselung der GPS-Daten '
                                 . 'geschnitten. Dabei wanderten die Punkte aus dem Ruhesegment in den '
                                 . 'Einsatz; Rückgängig würde sie mitlöschen. Wenn der Einsatz weg '
                                 . 'soll: über den Papierkorb löschen.'], 409);
        }
    }

    try {
        $zurueck = db_transaktion($pdo, function (PDO $pdo) use ($schnitte, $misId, $userId): int {
        /* Die Punkte waren im Segment nie fort (seit S11-05c); zurueck
         * „kommen" sie, indem die Sperre faellt. Gemeldet wird, wie viele es
         * beim Schnitt waren. Bis Web 22.7.0 schob hier `spur_teilen()` die
         * Klartextpunkte alter Schnitte zurueck. */
        $zurueck = array_sum(array_column($schnitte, 'n_punkte'));
        /* DER VERMERK FAELLT MIT (E-S4-53). Bliebe er stehen, waere der
         * Zeitraum fuer immer gesperrt — und zwar unsichtbar. */
        schnitte_loeschen($pdo, 'ziel', [$misId]);

        /* Der Einsatz selbst geht restlos, nicht in den Papierkorb: Er ist
         * eine Minute alt und hat nie etwas enthalten, was jemand
         * wiederfinden wollte. Die Spur ist oben schon abgeraeumt worden. */
        spur_loeschen($pdo, 'mission', [$misId]);
        einsatz_phasen_ersetzen($pdo, $misId, []);   // leere Liste = loeschen
        $pdo->prepare('DELETE FROM missions WHERE id = ? AND user_id = ?')
            ->execute([$misId, $userId]);
        return $zurueck;
        });
    } catch (Throwable $ex) {
        json_fehler($ex, 'schneiden');
    }

    json_out(['ok' => true, 'zurueck' => $zurueck]);
}

/* ---- Ein Stück löschen (S11-05c, Backlog Nr. 161) ------------------------- */

function stueck_loeschen(array $b, int $userId): never
{
    $pdo = db();
    $pruef = new Pruefliste();
    ['seg' => $seg, 'tag' => $tag, 'beginn' => $beginn, 'ende' => $ende,
     'von' => $vonTs, 'bis' => $bisTs] = schnitt_segment_und_zeit($b, $userId, $pruef);
    $segId = (int)$seg['id'];

    /* Bis Web 22.7.0 stand hier die Abweisung des Altbestands im Klartext
     * (`spur1_vorhanden()`, 409 `altbestand`). Seit Web 23 gibt es keinen. */
    $geloescht = pruef_zahl($b['geloescht'] ?? null, 1, LIMIT_TRACKPUNKTE_SPUR, 'geloescht', $pruef);
    $stand = $b['stand'] ?? null;
    if ($geloescht === null || !is_array($stand)) {
        json_out(['error' => 'eingabe', 'meldung' => 'Im gewählten Zeitraum liegt kein Punkt — '
                  . 'es gibt nichts zu löschen.'], 400);
    }
    $stand = array_map(static fn($x) => [(int)($x[0] ?? -1), (int)($x[1] ?? 0)], array_values($stand));
    $stuecke = schnitt_stuecke($b, $vonTs, $bisTs, false, $pruef);
    /* ALLES LOESCHEN IST KEIN STUECK. Ohne ein einziges Stueck verloere das
     * Segment auch seine Fortsetzungsmarke; ein Geraet finge von vorn an. Wer
     * die ganze Aufzeichnung loswerden will, nimmt den Papierkorb. */
    if (!$stuecke) {
        json_out(['error' => 'alles', 'meldung' => 'Damit wäre die ganze Aufzeichnung weg. Dafür '
                  . 'ist der Papierkorb da: den Diensttag löschen.'], 409);
    }

    try {
        $erg = db_transaktion($pdo, function (PDO $pdo) use ($userId, $segId, $stand, $stuecke,
                                                            $vonTs, $bisTs, $geloescht): array {
            $t = spur2_tauschen($pdo, 'rest', $segId, $stand, $stuecke);
            if (!$t['ok']) { return $t; }
            /* Die Sperre gegen eine erneute Lieferung desselben Zeitraums, ohne
             * Einsatz dahinter (`mission_id` NULL, Migration
             * 2026_10_15_loeschvermerk). */
            schnitt_vermerken($pdo, $userId, 'rest', $segId, null, $vonTs, $bisTs, $geloescht);
            return $t;
        });
    } catch (Throwable $ex) {
        json_fehler($ex, 'schneiden');
    }
    if (!$erg['ok']) {
        json_out(['error' => $erg['grund'], 'meldung' => $erg['grund'] === 'stufe'
            ? 'Diese Aufzeichnung ist inzwischen zusammengefasst worden. Bitte die Seite neu laden und '
              . 'noch einmal wählen — es wurde nichts gelöscht.'
            : 'Inzwischen sind weitere GPS-Daten angekommen. Bitte die Seite neu laden und noch '
              . 'einmal wählen — es wurde nichts gelöscht.'], 409);
    }

    require_once __DIR__ . '/../protokoll_lib.php';
    protokoll('verwaltung', 'spur_stueck_geloescht',
              'GPS-Daten eines Ruhesegments gelöscht: ' . fmt_local($beginn) . '–' . fmt_local($ende)
              . ' (' . $geloescht . ' Punkte)',
              ['segment' => $segId, 'von_ts' => $vonTs, 'bis_ts' => $bisTs, 'punkte' => $geloescht],
              $userId);
    json_out(['ok' => true, 'geloescht' => $geloescht]);
}

/* ---- Eingang -------------------------------------------------------------- */

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)
    $b = api_rumpf();

    switch ((string)($b['action'] ?? '')) {
        case 'schneiden':    schnitt_ausfuehren($b, $userId);
        case 'rueckgaengig': schnitt_rueckgaengig($b, $userId);
        case 'loeschen':     stueck_loeschen($b, $userId);
        default:             json_out(['error' => 'action'], 400);
    }
} catch (Throwable $ex) {
    json_fehler($ex, 'schneiden');
}
