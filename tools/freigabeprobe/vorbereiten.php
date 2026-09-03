<?php
declare(strict_types=1);
/**
 * Schritt 1 und 3 der Freigabeprobe: das Konto herrichten und freigeben.
 *
 * Dieses Skript rechnet NICHTS aus, was mit Verschluesselung zu tun hat. Die
 * Huelle `pat_wrap_rc`, die Pruefsumme `pat_key_check` und der Chiffretext
 * `pat_blob` kommen aus dem Browser — aus `assets/crypto.js`, also aus der
 * Anwendung selbst. Ein zweiter Rechenweg in PHP waere eine zweite Umsetzung
 * derselben Krypto, und die Probe pruefte dann sich selbst.
 *
 * Aufruf: php vorbereiten.php <schritt> [json]
 *   quelle   <json mit wrap, check, blob, klartext>  -> Kennung + Datei
 *   pruefen  <json mit ziel-email>                   -> pat_blob im Zielkonto
 */
$w = dirname(__DIR__, 2) . '/server';
require_once $w . '/config.php';
require_once $w . '/db.php';
require_once $w . '/adminbackup_lib.php';

$schritt = $argv[1] ?? '';
$daten   = json_decode($argv[2] ?? '{}', true) ?: [];
$pdo     = db();
$MAIL    = 'probe-freigabe-quelle@example.invalid';

if ($schritt === 'quelle') {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$MAIL]);
    $pdo->prepare('INSERT INTO users (email, name, kdf_iter, role, session_epoch,
                                      account_key, pat_wrap_rc, pat_key_check)
                   VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$MAIL, 'Freigabeprobe', 310000, 'user', 0,
                   bin2hex(random_bytes(8)), $daten['wrap'], $daten['check']]);
    $uid = (int)$pdo->lastInsertId();
    $kennung = (string)$pdo->query("SELECT account_key FROM users WHERE id=$uid")->fetchColumn();

    edbak_restore($uid, [
        'version' => 7,
        'days' => [['id' => 990, 'day' => '2026-07-10', 'kind' => 'ground',
                    'vehicle_name' => 'Freigabeprobe', 'base_name' => 'Probenstation']],
        'missions' => [[
            'client_ref' => 'fg-rc-1', 'day_id' => 990,
            'started_at' => '2026-07-10 06:00:00', 'ended_at' => '2026-07-10 07:00:00',
            'pat_blob' => $daten['blob'],
            'track' => [[0, 47.1, 11.1, 700.0, 1783300000],
                        [1, 47.2, 11.2, 705.0, 1783300060]]]],
        'rest_segments' => [],
    ]);

    [$ok, $grund, $info] = edbak_sicherung_erzeugen($uid);
    if (!$ok) { fwrite(STDERR, "Backup: $grund\n"); exit(2); }

    $zielId = (int)$pdo->query('SELECT id FROM users WHERE email = '
        . $pdo->quote((string)$daten['ziel']))->fetchColumn();
    if (!$zielId) { fwrite(STDERR, "Zielkonto nicht gefunden\n"); exit(2); }
    /* Das Zielkonto leeren, damit die Zaehlung eindeutig ist. */
    foreach (['missions', 'rest_segments', 'days'] as $t) {
        $pdo->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$zielId]);
    }
    edbak_freigeben($kennung, (string)$info['datei'], $zielId);

    $m = edbak_paket_kopf_lesen($kennung, (string)$info['datei']);
    echo json_encode(['kennung' => $kennung, 'datei' => $info['datei'],
                      'quelle_id' => $uid, 'ziel_id' => $zielId,
                      'fassung' => $m['version'], 'geschuetzte' => $m['geschuetzte'],
                      'eintragsteile' => $m['eintragsteile'],
                      'spurteile' => $m['spurteile'],
                      'quelle_blob' => (string)$daten['blob']]), "\n";
    exit(0);
}

if ($schritt === 'pruefen') {
    $zielId = (int)$pdo->query('SELECT id FROM users WHERE email = '
        . $pdo->quote((string)$daten['ziel']))->fetchColumn();
    $r = $pdo->query("SELECT client_ref, pat_blob FROM missions
                       WHERE user_id = $zielId AND client_ref = 'fg-rc-1'")->fetch();
    require_once $w . '/spur_lib.php';
    $mid = (int)$pdo->query("SELECT id FROM missions
                              WHERE user_id = $zielId AND client_ref = 'fg-rc-1'")->fetchColumn();
    echo json_encode([
        'angekommen' => $r !== false,
        'pat_blob'   => $r['pat_blob'] ?? null,
        'punkte'     => $mid ? (spur_zahlen($pdo, 'mission', [$mid])[$mid] ?? 0) : 0,
    ]), "\n";
    exit(0);
}

if ($schritt === 'aufraeumen') {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$MAIL]);
    echo "ok\n";
    exit(0);
}
fwrite(STDERR, "Unbekannter Schritt\n");
exit(2);
