<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../spur_lib.php';

/**
 * POST api/backup_spuren.php — die Spuren eines Backups, blockweise.
 *
 * WOFUER (Konzept S2, 3.2.3). Fassung 4 legt Spuren als SPUR1-Blob in die
 * Backup-Datei. Der Browser holt sie in Bloecken, versiegelt sie zu
 * Spurteilen von hoechstens 2 MB und haengt sie ans ZIP — er packt dabei nie
 * einen einzelnen Punkt an.
 *
 * WARUM NICHT ueber `api/export_data.php`. Der liefert Punktlisten und traegt
 * drei Regeln, die HIER falsch waeren:
 *
 *   - Er filtert `deleted_at IS NULL`. Ein Backup enthaelt den
 *     Papierkorb (E-S1-01) — wer diesen Filter uebernimmt, verliert genau
 *     das, was jemand mit dem Backup retten wollte.
 *   - Er verlangt den Haken „personenbezogene Angaben" (A9). Bei einer
 *     Backup gibt es die anonyme Fassung nicht; es gaebe keinen Haken zu
 *     umgehen.
 *   - Er gibt Punkte aus. Hier soll gerade NICHT ausgepackt werden.
 *
 * DIE FALLUNTERSCHEIDUNG STEHT NICHT HIER, sondern in
 * `spur_fuer_sicherung_viele()` (spur_lib.php): Blob durchreichen, Zeilen neu
 * kodieren, oder ablehnen. Dieser Endpunkt prueft, WEM die Kennung gehoert,
 * und reicht weiter — die Spurlogik hat genau eine Stelle (CLAUDE.md 4).
 *
 * ANTWORT je Kennung eine Zeile, und zwar auch fuer die Faelle, die keine
 * Spur ergeben. Ein stilles Weglassen liesse den Browser raten, ob eine Spur
 * fehlt oder nie da war.
 *
 *   {"12": {"blob": "<Base64>", "stufe": 2, "n_original": 443, "n": 443}}
 *   {"13": {"leer": true}}
 *   {"14": {"fehler": "luecke", "grund": "…"}}
 *   {"15": {"offen": true}}          Zeit abgelaufen — erneut holen
 */

/** Hoechstens so viele Kennungen je Anfrage — dieselbe Zahl wie im Export. */
const BACKUP_SPUREN_BLOCK = 25;

/**
 * Zeitfenster einer Anfrage (Z3: hoechstens 30 s), mit Reserve.
 *
 * WARUM ES SIE GIBT. Das Neukodieren einer Stufe-1-Spur kostet Zeit, die mit
 * der Punktzahl waechst; 25 Spuren an der Obergrenze waeren rund 17 Sekunden
 * reines Rechnen. Laeuft die Anfrage in das Zeitlimit von PHP, ist das KEIN
 * `Throwable` — der `catch` unten faengt es nicht, und die Nutzerin bekommt
 * eine leere Antwort ohne Meldung. Also wird vorher aufgehoert und der Rest
 * als `offen` gemeldet; der Browser holt ihn im naechsten Zug.
 */
const BACKUP_SPUREN_FENSTER_S = 20.0;

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)
    $t0 = microtime(true);

    $b = api_rumpf();

    $ownerType = (string)($b['owner_type'] ?? '');
    if (!in_array($ownerType, ['mission', 'rest'], true)) {
        json_out(['error' => 'owner_type'], 400);
    }

    $ids = [];
    foreach ((array)($b['ids'] ?? []) as $v) {
        $n = (int)$v;
        if ($n > 0) { $ids[$n] = true; }
    }
    $ids = array_keys($ids);
    if (count($ids) > BACKUP_SPUREN_BLOCK) {
        json_out(['error' => 'zu_viele_ids',
                  'meldung' => 'Höchstens ' . BACKUP_SPUREN_BLOCK
                             . ' Kennungen je Anfrage.'], 400);
    }
    if (!$ids) { json_out(['spuren' => new stdClass()]); }

    $pdo = db();

    /* DATENTRENNUNG (I3, I4) — und OHNE den Papierkorbfilter, mit Absicht:
     * `edbak_build()` nimmt den Papierkorb seit Web 7.3.1 ausdruecklich mit
     * (E-S1-01, Begruendung dort). Eine Spur, deren Einsatz im Papierkorb
     * liegt, gehoert also in das Backup. */
    $tabelle = $ownerType === 'mission' ? 'missions' : 'rest_segments';
    $eigeneZeilen = sql_in_bloecken($pdo,
        "SELECT id, client_ref FROM `$tabelle` WHERE user_id = ? AND id IN ({IDS})",
        $ids, [$userId]);
    $eigene = array_map(static fn($r) => (int)$r['id'], $eigeneZeilen);

    /* DIE FORM `punkte` (S11-06a, Nutzlast 13, E-S11-56). Seit S11-04 liegt
     * eine Spur verschlossen in Stuecken, die nur der Browser oeffnet; der
     * Server kann daraus kein SPUR1 mehr bauen. Der Browser bekommt deshalb
     * je Spur den Altbestand als Punkte ([seq, lat, lon, ele, ts], wie bis
     * Nutzlast 7) und die Stuecke samt Kennung und Sperrzeiten, oeffnet sie
     * und schreibt die Spur als Punktliste. Bis Web 22.4.0 kannte dieser
     * Endpunkt nur SPUR1 — eine Spur aus Stuecken kam als `leer` zurueck und
     * fehlte im Backup (F-S11-63). Die Form `blob` bleibt fuer das
     * Konto-Backup der Verwaltung (S11-06c). */
    if ((string)($b['form'] ?? '') === 'punkte') {
        $refVon = [];
        foreach ($eigeneZeilen as $r) { $refVon[(int)$r['id']] = (string)$r['client_ref']; }
        $aus = [];
        foreach ($eigene as $id) {
            $aus[(string)$id] = ['punkte' => [], 'ref' => $refVon[$id] ?? '', 'stuecke' => [],
                                 'sperren' => []];
        }
        foreach (spur_lesen_viele($pdo, $ownerType, $eigene) as $id => $punkte) {
            foreach ($punkte as $p) {
                $aus[(string)$id]['punkte'][] = [(int)$p[0], $p[1], $p[2], $p[3], (int)$p[4]];
            }
        }
        foreach (spur2_lesen_viele($pdo, $ownerType, $eigene) as $id => $stuecke) {
            $aus[(string)$id]['stuecke'] = $stuecke;
            $aus[(string)$id]['sperren'] = array_map(
                static fn($x) => ['von' => $x['von_ts'], 'bis' => $x['bis_ts']],
                schnitte_lesen($pdo, $ownerType, (int)$id));
        }
        json_out(['spuren' => $aus ?: new stdClass()]);
    }

    /* Fremde oder erfundene Kennungen fallen hier heraus. Sie werden NICHT
     * gemeldet: Der Browser hat die Liste aus dem Kern, den derselbe Server
     * gerade geliefert hat — eine Kennung, die nicht dabei ist, ist keine
     * Auskunft ueber ein anderes Konto, sondern ein veralteter Stand. */
    $spuren = spur_fuer_sicherung_viele($pdo, $ownerType, $eigene,
                                        $t0 + BACKUP_SPUREN_FENSTER_S);

    $aus = [];
    foreach ($spuren as $id => $s) {
        if (isset($s['blob'])) {
            $aus[(string)$id] = ['blob' => base64_encode($s['blob']),
                                 'stufe' => $s['stufe'],
                                 'n_original' => $s['n_original'], 'n' => $s['n']];
        } else {
            $aus[(string)$id] = $s;
        }
    }

    json_out(['spuren' => $aus ?: new stdClass()]);
} catch (Throwable $ex) {
    json_fehler($ex, 'backup');
}
