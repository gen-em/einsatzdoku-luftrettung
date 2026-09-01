<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../spur_lib.php';

/**
 * POST api/backup_spuren.php — die Spuren einer Sicherung, blockweise.
 *
 * WOFUER (Konzept S2, 3.2.3). Fassung 4 legt Spuren als SPUR1-Blob in die
 * Sicherungsdatei. Der Browser holt sie in Bloecken, versiegelt sie zu
 * Spurteilen von hoechstens 2 MB und haengt sie ans ZIP — er packt dabei nie
 * einen einzelnen Punkt an.
 *
 * WARUM NICHT ueber `api/export_data.php`. Der liefert Punktlisten und traegt
 * drei Regeln, die HIER falsch waeren:
 *
 *   - Er filtert `deleted_at IS NULL`. Eine Sicherung enthaelt den
 *     Papierkorb (E-S1-01) — wer diesen Filter uebernimmt, verliert genau
 *     das, was jemand mit der Sicherung retten wollte.
 *   - Er verlangt den Haken „personenbezogene Angaben" (A9). Bei einer
 *     Sicherung gibt es die anonyme Fassung nicht; es gaebe keinen Haken zu
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
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['error' => 'method'], 405); }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'csrf'], 403);
    }
    $t0 = microtime(true);

    $b = json_decode(file_get_contents('php://input'), true);
    if (!is_array($b)) { json_out(['error' => 'payload'], 400); }

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
     * liegt, gehoert also in die Sicherung. */
    $tabelle = $ownerType === 'mission' ? 'missions' : 'rest_segments';
    $eigene = array_map(static fn($r) => (int)$r['id'], sql_in_bloecken($pdo,
        "SELECT id FROM `$tabelle` WHERE user_id = ? AND id IN ({IDS})",
        $ids, [$userId]));

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
