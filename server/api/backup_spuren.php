<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../spur_lib.php';

/**
 * POST api/backup_spuren.php — die Spuren eines Backups, blockweise.
 *
 * WOFUER (Konzept S2, 3.2.3; seit S11-06a Nutzlast 13). Der Browser holt je
 * Block von Kennungen die verschlossenen Stuecke samt Kennung (`ref`) und
 * Sperrzeiten, oeffnet sie und schreibt die Spur als Punktliste in den
 * Spurteil der Sicherung. Der Server oeffnet nichts.
 *
 * WARUM NICHT ueber `api/export_data.php`. Der filtert `deleted_at IS NULL`
 * und verlangt den Haken „personenbezogene Angaben" (A9). Ein Backup enthaelt
 * den Papierkorb (E-S1-01), und eine anonyme Fassung gibt es dort nicht.
 *
 * Rumpf: {owner_type, ids: [..], form: 'punkte'}. Antwort je eigener Kennung:
 *
 *   {"12": {"ref": "m-…", "stuecke": [{seq_from, punkte, …, huelle}],
 *           "sperren": [{von, bis}]}}
 *
 * BIS WEB 22.7.0 gab es daneben die Form `blob` (SPUR1, aus Zeilen und Blob
 * neu kodiert, mit Zeitgrenze und `offen`) und in der Form `punkte` den
 * Altbestand als Klartextpunkte. Beides ist mit dem Klartext gegangen
 * (S11-12); eine andere Form als `punkte` antwortet 400.
 */

/** Hoechstens so viele Kennungen je Anfrage — dieselbe Zahl wie im Export. */
const BACKUP_SPUREN_BLOCK = 25;

try {
    api_methode();
    csrf_check();   // Feld ODER Kopfzeile X-CSRF (Nr. 67)

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

    if ((string)($b['form'] ?? '') !== 'punkte') {
        json_out(['error' => 'form', 'meldung' => 'Erwartet wird die Form „punkte".'], 400);
    }
    /* Fremde oder erfundene Kennungen fallen oben heraus. Sie werden NICHT
     * gemeldet: Der Browser hat die Liste aus dem Kern, den derselbe Server
     * gerade geliefert hat — eine Kennung, die nicht dabei ist, ist keine
     * Auskunft ueber ein anderes Konto, sondern ein veralteter Stand. */
    $refVon = [];
    foreach ($eigeneZeilen as $r) { $refVon[(int)$r['id']] = (string)$r['client_ref']; }
    $aus = [];
    foreach ($eigene as $id) {
        $aus[(string)$id] = ['ref' => $refVon[$id] ?? '', 'stuecke' => [], 'sperren' => []];
    }
    foreach (spur2_lesen_viele($pdo, $ownerType, $eigene) as $id => $stuecke) {
        $aus[(string)$id]['stuecke'] = $stuecke;
        $aus[(string)$id]['sperren'] = array_map(
            static fn($x) => ['von' => $x['von_ts'], 'bis' => $x['bis_ts']],
            schnitte_lesen($pdo, $ownerType, (int)$id));
    }
    json_out(['spuren' => $aus ?: new stdClass()]);
} catch (Throwable $ex) {
    json_fehler($ex, 'backup');
}
