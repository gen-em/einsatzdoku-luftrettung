<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../backup_lib.php';

/**
 * POST api/backup_restore.php
 * Body: das im Browser entsiegelte Backup-JSON. Die verschluesselten Angaben
 * hat der Browser bereits mit dem Inhaltsschluessel DIESES Kontos neu
 * verschluesselt (`pat_blob`), deshalb laesst sich ein Backup in jedes Konto
 * einspielen. Header X-CSRF muss zum Session-Token passen.
 *
 * Antwort: { ok: true, stats: {...} }
 */

api_methode();
csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)

$data = api_rumpf();
if (($data['format'] ?? '') !== 'einsatzdoku-backup') {
    json_out(['error' => 'format'], 400);
}

/* NUR NOCH NUTZLAST 13 (S11-12, E-S11-85).
 *
 * Bis Web 22.7.0 nahm dieser Weg jede Nutzlast ab 6 an: Der Browser
 * verschloss Orte, Reanimation und Spur aus jeder Fassung, und SPUR1 las er
 * selbst (`EdOrtsdaten.spur1Lesen()`). Aeltere Sicherungen gibt es nach
 * Auskunft der Betreiberin nicht mehr; mit ihnen ist der Rueckweg gegangen —
 * er waere der einzige Weg gewesen, auf dem noch SPUR1 und Klartextspalten
 * in die Anlage kamen.
 *
 * Die Ablehnung ist ausdruecklich und benannt, nicht ein Fehler beim
 * Einlesen; der Container liess sich entsiegeln, und genau darum kommt die
 * Meldung hier an. Der Browser fragt dieselbe Zahl schon vor dem Senden
 * (`einstellungen.php`), dieser Satz ist der Riegel dahinter.
 *
 * (Bis Web 5 stand die Schranke bei 6 — ab Web 6.0.0 mit Diensttagen, E23.
 * Die einteilige Datei bis Nutzlast 7 nimmt nur noch der Demo-Reset, aus
 * seiner eigenen Fixture und nicht ueber diesen Endpunkt.) */
$nutzlast = isset($data['version']) ? (int)$data['version'] : 0;
if ($nutzlast < 13) {
    json_out(['error' => 'version_alt',
              'meldung' => 'Dieses Backup hat das Format ' . $nutzlast
                         . ' und stammt aus einer Fassung vor Web 22.5.0. Seit Web 23 lassen '
                         . 'sich nur noch Sicherungen ab Format 13 einspielen — ältere trugen '
                         . 'die Ortsdaten unverschlüsselt. Es wurde nichts geändert.'], 409);
}

/* UND EINE SCHRANKE NACH OBEN (S2/AP5).
 *
 * Die gab es bisher nicht, und das war so lange harmlos, wie es nur eine
 * Nutzlast gab. Mit Fassung 8 (Kern ohne Punktlisten) ist es das nicht mehr:
 * Eine kuenftige Fassung 9, die etwas anders ablegt, liefe hier in den
 * Fassung-8-Zweig und legte still einen halben Bestand an.
 *
 * Die Meldung sagt deshalb, was los ist — dieselbe Linie wie bei einem
 * Container aus einer neueren Fassung (`assets/crypto.js`).
 *
 * DER SATZ DARUEBER HAT SICH BEWAEHRT: Fassung 9 ist mit Web 14.2.0
 * gekommen (Geraete-Momentaufnahme und Sperrvermerke, R64 / Backlog Nr. 63),
 * und die Schranke hat genau dafuer gearbeitet — eine 9er-Datei wird von
 * jedem aelteren Stand ABGEWIESEN statt halb eingelesen. Wer die Zahl hier
 * hochsetzt, ohne den Rueckweg auf die neuen Felder vorzubereiten, hebelt
 * genau das aus. Die Schranke nach UNTEN bleibt bei 6; alle Felder ab
 * Fassung 7 sind optional, und was fehlt, wird NULL.
 *
 * FASSUNG 10 (Web 16.0.0, E-S9-09): Typ und Kurzname am Rettungsmittel. Der
 * Rueckweg ist darauf vorbereitet — `edbak_restore()` schickt beide durch
 * `pruef_rettungsmittel()`, ein fehlender Typ wird 'standard'. Und hier
 * arbeitet die Schranke besonders deutlich: Ohne sie legte ein aelterer Stand
 * ein Bergwacht-Rettungsmittel als STANDARD-Rettungsmittel an — mit
 * Standortpflicht und Rollenvorlagen, und ohne dass man dem Datensatz den
 * Verlust ansaehe.
 *
 * FASSUNG 12 (Web 21.0.0, P5c/AP8): ohne die Auswahl zentraler Standorte
 * (R39). Der Rueckweg ist darauf vorbereitet — er ueberliest das Feld in
 * aelteren Dateien und fragt es in neueren nicht. Eine 11er-Datei bleibt
 * hier lesbar; ein Stand vor Web 21.0.0 weist eine 12er-Datei ab, und das
 * ist richtig: Sein Code laeuft auf diesem Schema ohnehin nicht.
 *
 * FASSUNG 13 (Web 22.5.0, S11-06a): Orte der Phasen und Reanimation stehen
 * offen im verschlossenen Kern, die Spuren als Punktlisten statt SPUR1. Der
 * Rueckweg ist darauf vorbereitet — der Browser verschliesst beides vor dem
 * Senden, aus jeder Fassung von 6 bis 13 gleich, und dieser Endpunkt nimmt
 * nur noch Verschlossenes an. Ein Stand vor Web 22.5.0 weist eine 13er-Datei
 * ab; er kennt die Punktlisten im Spurteil nicht und liefe still ohne Spuren. */
const NUTZLAST_HOECHSTENS = 13;
if ($nutzlast > NUTZLAST_HOECHSTENS) {
    json_out(['error' => 'version_neu',
              'meldung' => 'Dieses Backup hat das Format ' . $nutzlast
                         . ' und stammt aus einer neueren Fassung der Anwendung. '
                         . 'Diese Installation kennt höchstens Format '
                         . NUTZLAST_HOECHSTENS . '. Es wurde nichts geändert — '
                         . 'bitte die Anwendung aktualisieren.'], 409);
}

try {
    $stats = edbak_restore($userId, $data, null, 'browser');
    /* DIE SPURKARTE GEHT GETRENNT ZURUECK, nicht in `stats`: `stats` ist die
     * Rueckmeldung an die Nutzerin und wird angezeigt; die Karte ist eine
     * Arbeitsangabe fuer den naechsten Schritt des Browsers (Konzept 3.2.4). */
    $karte = $stats['spur_karte'] ?? null;
    $tage  = $stats['day_map'] ?? null;
    unset($stats['spur_karte'], $stats['day_map']);
    $antwort = ['ok' => true, 'stats' => $stats];
    if ($karte !== null) { $antwort['spur_karte'] = $karte ?: new stdClass(); }
    /* DIE ZUORDNUNG DER DIENSTTAGE (S2/AP5b). Der Kopf einer Fassung-4-Datei
     * bringt Stammdaten und Diensttage; die Eintraege kommen danach in
     * eigenen Anfragen und muessen wissen, an welchen Tag sie gehoeren. */
    if ($tage !== null) { $antwort['day_map'] = $tage ?: new stdClass(); }
    json_out($antwort);
} catch (DomainException $ex) {
    /* Die zweite Schranke in `edbak_restore()` (S11-12); oben steht die erste. */
    if (str_starts_with($ex->getMessage(), 'version_alt')) {
        json_out(['error' => 'version_alt',
                  'meldung' => 'Seit Web 23 lassen sich nur noch Sicherungen ab Format 13 '
                             . 'einspielen. Es wurde nichts geändert.'], 409);
    }
    /* Klartext-Ortsdaten (S11-06b): Der Browser verschliesst seit Web 22.5.0
     * Orte, Reanimation und Spur vor dem Senden; eine Nutzlast mit Klartext
     * kommt aus einer veralteten Seite oder von Hand. Nichts ist geschrieben
     * — `edbak_restore()` prueft vor der Transaktion. */
    json_out(['error' => 'klartext',
              'meldung' => 'Das Backup enthielt Ortsdaten im Klartext ('
                         . preg_replace('/^klartext: /', '', $ex->getMessage())
                         . '). Seit Web 22.5.0 verschlüsselt sie der Browser vor dem Senden; '
                         . 'bitte die Seite neu laden. Es wurde nichts geändert.'], 400);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
