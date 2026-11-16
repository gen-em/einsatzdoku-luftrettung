<?php
declare(strict_types=1);

/**
 * Zustand des Demo-Kontos für `demo_fassung2.mjs` — lesen, zurücksetzen,
 * Mengenbremse leeren.
 *
 * Anlass: Nr. 563 — die Probe muss wissen, ob das bloße Ansehen ein Blatt
 * des Standbuchs geschrieben oder die Änderungsmarke gesetzt hat, und sie
 * muss zwischen zwei Anmeldungen zurücksetzen können, ohne sich dafür als
 * Verwaltung anzumelden (das wäre ein zweiter Weg durch die Oberfläche, den
 * `demo_pruefen.mjs` schon misst).
 *
 * Aufruf:  php demo_zustand.php stand | reset | bremse [--wurzel PFAD]
 *   stand    JSON: Blattversionen, Änderungsmarke, Geräte, Zahlen
 *   reset    `demo_zuruecksetzen()` (oder `demo_anlegen()`, wenn es keines
 *            gibt), danach `stand`
 *   bremse   die Mengenbremse der Demo-Anmeldung leeren (`rate_erfolg()`)
 *   --wurzel ein anderes `server/` (eine Abschrift mit umgelegten Konstanten)
 */

$wurzel = dirname(__DIR__, 3) . '/server';
foreach ($argv as $i => $a) {
    if ($a === '--wurzel' && isset($argv[$i + 1])) { $wurzel = rtrim((string)$argv[$i + 1], '/'); }
}
require_once $wurzel . '/demo_lib.php';
require_once $wurzel . '/ratelimit_lib.php';

$was = $argv[1] ?? 'stand';
try {
    if ($was === 'reset') {
        if (demo_id() === null) { demo_anlegen(); } else { demo_zuruecksetzen(); }
    } elseif ($was === 'bremse') {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        rate_erfolg('demo');
        rate_erfolg('demog');
        db()->exec("DELETE FROM rate_limits WHERE topf IN ('demo', 'demog')");
    } elseif ($was !== 'stand') {
        fwrite(STDERR, "Unbekannt: $was (stand, reset, bremse)\n");
        exit(2);
    }
    $id = demo_id();
    if ($id === null) { fwrite(STDERR, "Kein Demo-Konto.\n"); exit(1); }
    $pdo = db();
    $q = static function (string $sql) use ($pdo, $id): array {
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    };
    $blaetter = [];
    foreach ($q('SELECT wer, version FROM stand_blaetter WHERE user_id = ? ORDER BY wer') as $z) {
        $blaetter[(int)$z['wer']] = (int)$z['version'];
    }
    $konto = $q('SELECT kdf_iter, LEFT(pat_wrap_pw, 5) AS huelle, LEFT(kp_privat, 5) AS kp,
                        pat_key_check FROM users WHERE id = ?')[0];
    echo json_encode([
        'blaetter'  => $blaetter,
        'geaendert' => demo_geaendert_seit(),
        'reset'     => demo_letzter_reset(),
        'konto'     => $konto,
        'geraete'   => $q('SELECT device_id, label, herkunft, spk IS NOT NULL AS spk FROM devices
                            WHERE user_id = ? AND ' . GERAETE_ECHT_SQL . ' ORDER BY id'),
        'bloecke'   => $q("SELECT LEFT(pat_blob, 5) AS art, COUNT(*) AS n FROM missions
                            WHERE user_id = ? AND pat_blob IS NOT NULL GROUP BY art"),
    ], JSON_UNESCAPED_UNICODE), "\n";
} catch (Throwable $ex) {
    fwrite(STDERR, get_class($ex) . ': ' . $ex->getMessage() . "\n");
    exit(1);
}
