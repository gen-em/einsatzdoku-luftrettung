<?php
declare(strict_types=1);

/**
 * Wartungslaufprobe, Serverteil — Liste der fälligen Spuren und Tausch
 * (S11-07b; Konzept S11 E-S11-05, -25, -68 bis -73).
 *
 * Anlass: Nr. 43 — seit S11 sieht der Server keine Koordinate mehr; Packen und
 * Ausdünnen wandern aus den Jobs in den Browser, und der Server darf dabei
 * nur tauschen, was er sicher beurteilen kann.
 *
 * WAS ES MISST. Die Liste (`spur2_faellig()`): wer hineingehört (abgeschlossen,
 * Stufe 1, Frist, neuer Schnitt) und wer nicht (offen, Papierkorb, Altbestand);
 * die Zielstufe einen Tag vor und einen Tag nach der Frist von 31 Tagen. Der
 * Tausch (`spur2_wartung_tauschen()`): jeder Ablehnungsgrund einmal, ein
 * Tausch, der gelingt, ein Stück der Stufe 1, das danach ankommt und die Spur
 * wieder fällig macht, und der `pat_blob`, der nur bei passender Prüfsumme
 * geschrieben wird.
 *
 * WAS ES NICHT MISST: den Inhalt eines Stücks. Den sieht der Server nicht;
 * die Hüllen hier sind wohlgeformt, aber mit einem Wegwerfschlüssel
 * verschlossen. Packen, Ausdünnen und Rundlauf misst die Browserprobe.
 *
 * ES ÄNDERT NICHTS: Der ganze Lauf steht in einer Transaktion, die am Ende
 * zurückgerollt wird.
 *
 * Aufruf: php tools/proben/wartungslauf/probe.php [konto]
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 3) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/validate_lib.php';
require_once $wurzel . '/spur_lib.php';
require_once $wurzel . '/geraetehuelle_lib.php';

$konto = $argv[1] ?? 'demo@gen-em.org';
$pdo = db();
$q = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$q->execute([$konto]);
$uid = (int)$q->fetchColumn();
if (!$uid) { fwrite(STDERR, "Konto $konto gibt es auf dieser Installation nicht.\n"); exit(2); }

$erwartungen = 0;
$offen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-64s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}

$schluessel = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
$sec1 = gh_sec1_von($schluessel);
/** Ein wohlgeformtes gepacktes Stück — der Inhalt ist hier gleichgültig. */
function stueck(string $sec1, string $art, string $ref, int $seq, int $n, int $nOrig, int $stufe,
                int $von, int $bis): array {
    $klar = gzcompress(str_repeat("\0", 16 * $n), 6);
    $platz = "$seq:$n:$stufe:$nOrig";
    return ['seq_from' => $seq, 'punkte' => $n, 'n_original' => $nOrig, 'ts_von' => $von,
            'ts_bis' => $bis, 'stufe' => $stufe,
            'huelle' => gh_verschliessen($klar, $sec1, $art, $ref, $platz)];
}
function stand(PDO $pdo, string $art, int $id): array {
    $q = $pdo->prepare('SELECT seq_from, punkte FROM track_stuecke WHERE owner_type = ? AND owner_id = ?
                        ORDER BY seq_from');
    $q->execute([$art, $id]);
    return array_map(static fn($r) => [(int)$r['seq_from'], (int)$r['punkte']], $q->fetchAll(PDO::FETCH_ASSOC));
}
function umriss(PDO $pdo, string $art, int $id): array {
    $q = $pdo->prepare('SELECT MIN(seq_from) s, MIN(ts_von) v, MAX(ts_bis) b, MAX(seq_from + punkte) m
                          FROM track_stuecke WHERE owner_type = ? AND owner_id = ?');
    $q->execute([$art, $id]);
    return array_map('intval', $q->fetch(PDO::FETCH_ASSOC));
}
function in_liste(array $liste, string $art, int $id): ?array {
    foreach ($liste as $e) { if ($e['art'] === $art && $e['id'] === $id) { return $e; } }
    return null;
}

echo "Wartungslaufprobe (Server) gegen $konto\n\n";
$pdo->beginTransaction();
try {
    /* Ein Einsatz mit Stücken der Stufe 1, abgeschlossen. */
    $m = $pdo->prepare("SELECT m.id, m.client_ref, m.pat_blob FROM missions m
                         WHERE m.user_id = ? AND m.final = 1 AND m.deleted_at IS NULL
                           AND EXISTS (SELECT 1 FROM track_stuecke s WHERE s.owner_type = 'mission'
                                        AND s.owner_id = m.id AND s.stufe = 1)
                         ORDER BY (SELECT COUNT(*) FROM track_stuecke s WHERE s.owner_type = 'mission'
                                    AND s.owner_id = m.id) DESC, m.id LIMIT 1");
    $m->execute([$uid]);
    $e = $m->fetch(PDO::FETCH_ASSOC);
    if (!$e) { fwrite(STDERR, "Kein abgeschlossener Einsatz mit Stücken — Demo zurücksetzen.\n"); exit(2); }
    $mid = (int)$e['id'];
    $ref = (string)$e['client_ref'];
    /* Die Liste nur dieses Einsatzes: alle anderen Spuren des Kontos fallen
     * für die Dauer des Laufs aus der Liste (offen gesetzt). */
    $pdo->prepare('UPDATE missions SET final = 0 WHERE user_id = ? AND id <> ?')->execute([$uid, $mid]);
    $pdo->prepare('UPDATE rest_segments SET final = 0 WHERE user_id = ?')->execute([$uid]);
    $jetzt = time();
    $setzeEnde = function (int $tageZurueck) use ($pdo, $mid, $jetzt): void {
        $pdo->prepare('UPDATE missions SET ended_at = ? WHERE id = ?')
            ->execute([gmdate('Y-m-d H:i:s', $jetzt - $tageZurueck * 86400), $mid]);
    };

    echo "Teil 1 — die Liste\n";
    $setzeEnde(30);
    $l = in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid);
    pruefe($l !== null, 'abgeschlossen mit Stufe 1: in der Liste');
    pruefe(($l['ziel_stufe'] ?? 0) === 2, '30 Tage nach dem Ende: Ziel Stufe 2', (string)($l['ziel_stufe'] ?? '—'));
    pruefe(isset($l['stuecke'][0]['huelle'], $l['pat_pruef']) && array_key_exists('pat_blob', $l)
           && is_array($l['phases'] ?? null) && is_array($l['sperren'] ?? null),
           'trägt Stücke, Sperren, Phasen, pat_blob, Prüfsumme');
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt, [['mission', $mid]]), 'mission', $mid) === null,
           'ausgelassen (in dieser Sitzung nicht zu öffnen): nicht in der Liste');
    $setzeEnde(32);
    $l = in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid);
    pruefe(($l['ziel_stufe'] ?? 0) === 3, '32 Tage nach dem Ende: Ziel Stufe 3', (string)($l['ziel_stufe'] ?? '—'));
    $pdo->prepare('UPDATE missions SET final = 0 WHERE id = ?')->execute([$mid]);
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid) === null, 'offen (final = 0): nicht in der Liste');
    $pdo->prepare('UPDATE missions SET final = 1, deleted_at = UTC_TIMESTAMP() WHERE id = ?')->execute([$mid]);
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid) === null, 'im Papierkorb: nicht in der Liste');
    $pdo->prepare('UPDATE missions SET deleted_at = NULL WHERE id = ?')->execute([$mid]);
    $pdo->exec('SAVEPOINT altbestand');
    $pdo->prepare("INSERT INTO track_points (owner_type, owner_id, seq, lat, lon, ele, ts)
                   VALUES ('mission', ?, 999999, 47.5, 11.0, NULL, 1)")->execute([$mid]);
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid) === null, 'mit Altbestand: nicht in der Liste (E-S11-70)');
    $pdo->exec('ROLLBACK TO SAVEPOINT altbestand');
    pruefe(count(spur2_faellig($pdo, $uid, 20, $jetzt)) <= SPUR2_WARTUNG_MAX, 'höchstens ' . SPUR2_WARTUNG_MAX . ' je Anfrage');

    echo "\nTeil 2 — der Tausch\n";
    $setzeEnde(30);
    /* Ein zweites Stück der Stufe 1, damit wirklich zusammengefügt wird (die
     * Demo trägt je Spur eines). */
    $u0 = umriss($pdo, 'mission', $mid);
    spur2_stueck_schreiben($pdo, 'mission', $mid, $u0['m'], 4, $u0['b'], $u0['b'] + 30,
        gh_verschliessen(str_repeat("\0", 56), $sec1, 'spur', $ref, $u0['m'] . ':4'));
    $u = umriss($pdo, 'mission', $mid);
    $st = stand($pdo, 'mission', $mid);
    $nGes = $u['m'] - $u['s'];
    $gut = stueck($sec1, 'mission', $ref, $u['s'], max(1, $nGes - 5), $nGes, 2, $u['v'], $u['b']);
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st, ['stufe' => 3] + $gut, null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'frist', 'Stufe 3 vor der Frist: frist', $t['grund'] ?? 'ok');
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st,
                                ['seq_from' => $u['s'] + 1] + $gut, null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'form', 'anderes seq_from: form', $t['grund'] ?? 'ok');
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st,
                                ['ts_bis' => $u['b'] + 60] + $gut, null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'form', 'Zeitraum über den alten hinaus: form', $t['grund'] ?? 'ok');
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st,
                                ['n_original' => $nGes - 1, 'punkte' => 1] + $gut, null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'marke', 'Marke fällt: marke', $t['grund'] ?? 'ok');
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, array_slice($st, 1), $gut, null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'stand', 'gelesener Stand weicht ab: stand', $t['grund'] ?? 'ok');
    $t = spur2_wartung_tauschen($pdo, $uid, 'rest', $mid, $st, $gut, null, $jetzt);
    pruefe(!$t['ok'] && in_array($t['grund'], ['fehlt', 'offen'], true), 'falsche Art: fehlt/offen', $t['grund'] ?? 'ok');
    $pdo->prepare('UPDATE missions SET final = 0 WHERE id = ?')->execute([$mid]);
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st, $gut, null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'offen', 'nicht abgeschlossen: offen', $t['grund'] ?? 'ok');
    $pdo->prepare('UPDATE missions SET final = 1 WHERE id = ?')->execute([$mid]);
    pruefe(stand($pdo, 'mission', $mid) === $st, 'nach sieben Ablehnungen: Bestand unverändert', count($st) . ' Stücke');

    $pat = $e['pat_blob'];
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st, $gut,
                                ['neu' => 'edk1:probe-neu', 'pruef' => str_repeat('0', 64)], $jetzt);
    pruefe($t['ok'] && ($t['pat'] ?? '') === 'geaendert', 'Tausch gelingt; Blob mit falscher Prüfsumme bleibt', ($t['pat'] ?? '—'));
    $q = $pdo->prepare('SELECT pat_blob FROM missions WHERE id = ?');
    $q->execute([$mid]);
    pruefe($q->fetchColumn() === $pat, 'pat_blob unverändert');
    $neuStand = stand($pdo, 'mission', $mid);
    pruefe($neuStand === [[$u['s'], max(1, $nGes - 5)]], 'ein Stück der Stufe 2 statt ' . count($st), json_encode($neuStand));
    $sp = spur2_stand($pdo, 'mission', $mid);
    pruefe($sp['naechste_seq'] === $u['m'] && $sp['stufe'] === 2, 'Fortsetzungsmarke bleibt, Stufe 2', $sp['naechste_seq'] . '/' . $u['m']);
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid) === null, 'danach nicht mehr in der Liste');

    /* Ein Gerät liefert nach: ein Stück der Stufe 1 über der Marke. */
    spur2_stueck_schreiben($pdo, 'mission', $mid, $u['m'], 3, $u['b'], $u['b'] + 20,
        gh_verschliessen(str_repeat("\0", 42), $sec1, 'spur', $ref, $u['m'] . ':3'));
    $l = in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid);
    pruefe($l !== null && count($l['stuecke']) === 2, 'Nachlieferung macht die Spur wieder fällig', $l ? count($l['stuecke']) . ' Stücke' : '—');

    $setzeEnde(32);
    $st2 = stand($pdo, 'mission', $mid);
    $duenn = stueck($sec1, 'mission', $ref, $u['s'], 10, $nGes + 3, 3, $u['v'], $u['b'] + 20);
    $pruefsumme = spur2_pat_pruefsumme($pat);
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, $st2, $duenn,
                                ['neu' => 'edk1:probe-neu', 'pruef' => $pruefsumme], $jetzt);
    pruefe($t['ok'] && ($t['pat'] ?? '') === 'geschrieben', 'nach der Frist: Stufe 3 in einem Tausch, Blob geschrieben', ($t['pat'] ?? ($t['grund'] ?? '—')));
    $q->execute([$mid]);
    pruefe($q->fetchColumn() === 'edk1:probe-neu', 'pat_blob ersetzt');
    $t = spur2_wartung_tauschen($pdo, $uid, 'mission', $mid, stand($pdo, 'mission', $mid),
                                stueck($sec1, 'mission', $ref, $u['s'], 10, $nGes + 3, 2, $u['v'], $u['b'] + 20), null, $jetzt);
    pruefe(!$t['ok'] && $t['grund'] === 'stufe', 'von Stufe 3 zurück auf 2: stufe', $t['grund'] ?? 'ok');
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid) === null, 'ausgedünnt: nicht mehr in der Liste');

    echo "\nTeil 3 — ein Schnitt, jünger als das Stück\n";
    $vermerk = $pdo->prepare("INSERT INTO track_cuts (user_id, owner_type, owner_id, mission_id, von_ts, bis_ts,
                                     n_punkte, erstellt_am)
                              VALUES (?, 'mission', ?, ?, ?, ?, 1, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 1 MINUTE))");
    $vermerk->execute([$uid, $mid, null, $u['v'] + 20, $u['v'] + 30]);
    pruefe(in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid) === null,
           'jüngerer Löschvermerk (ohne Einsatz): nicht in der Liste');
    $vermerk->execute([$uid, $mid, $mid, $u['v'], $u['v'] + 10]);
    $l = in_liste(spur2_faellig($pdo, $uid, 20, $jetzt), 'mission', $mid);
    pruefe($l !== null && ($l['ziel_stufe'] ?? 0) === 3 && count($l['sperren']) === 2,
           'jüngerer Schnitt: wieder in der Liste, Ziel bleibt 3, beide Sperren dabei');

    echo "\nTeil 4 — die Prüfschicht\n";
    $roh = $duenn;
    $roh['huelle'] = base64_encode($roh['huelle']);
    pruefe(pruef_spurstueck_gepackt($roh) !== null, 'wohlgeformtes Stück Stufe 3 geht durch');
    pruefe(pruef_spurstueck_gepackt(['stufe' => 1] + $roh) === null, 'Stufe 1 abgewiesen');
    pruefe(pruef_spurstueck_gepackt(['n_original' => 5] + $roh) === null, 'n_original < punkte abgewiesen');
    pruefe(pruef_spurstueck_gepackt(['punkte' => LIMIT_TRACKPUNKTE_SPUR + 1, 'n_original' => LIMIT_TRACKPUNKTE_SPUR + 1] + $roh) === null,
           'mehr Punkte als eine Spur abgewiesen');
} finally {
    $pdo->rollBack();
}

printf("\n%d Erwartungen, %d nicht erfüllt.\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
