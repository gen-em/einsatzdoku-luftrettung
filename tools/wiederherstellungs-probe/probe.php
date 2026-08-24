<?php
declare(strict_types=1);

/**
 * Wiederherstellungsprobe — Beleg zu E-S1-04, E-S1-19 und Backlog Nr. 31/33/34/35.
 *
 * WOFUER. Der Papierkorb und der Rueckweg einer Sicherung haben Grenzfaelle,
 * die sich im Browser nur muehsam herstellen lassen und die man dem Ergebnis
 * nicht ansieht. Vier Teile:
 *
 *   TEIL 1 — PAPIERKORB. Seit Nutzlast 7 traegt die Sicherungsdatei den
 *   Papierkorb mit (E-S1-01), und der Rueckweg muss zwei Regeln einhalten:
 *
 *     E-S1-04  `deleted_with_day` ist eine UND-Verknuepfung: der Wert aus der
 *              DATEI sagt, ob der Eintrag am Tag hing, der ZIELTAG sagt, ob
 *              das hier ueberhaupt gelten kann. Wer nur den Zieltag prueft,
 *              macht aus jedem einzeln geloeschten Einsatz einen
 *              mitgeloeschten — er verschwindet aus `trash_list_missions()`
 *              und wird beim Wiederherstellen des Tages ungewollt mit aktiv.
 *     E-S1-19  Ein in der Datei AKTIVER Eintrag, dessen Zieltag hier im
 *              Papierkorb liegt, wird abgelehnt und gezaehlt. Sonst stuende
 *              er an einem Tag, den die Tagesliste nicht zeigt.
 *
 *   TEIL 2 — EINE KAPUTTE DATEI DARF NUR SICH SELBST KOSTEN. Der Lauf haengt
 *   an EINER Transaktion: Was eine Ausnahme ausloest, reisst alles mit. Ein
 *   unbrauchbarer Zeitwert in einem Ruhesegment (Nr. 31) und eine doppelte
 *   Spurnummer (Nr. 35) taten das bis Web 8.0.0. Beide muessen die eine Zeile
 *   beziehungsweise den einen Punkt kosten, nicht den Lauf.
 *
 *   TEIL 3 — KEIN WEG MEHR ZUM HALB SICHTBAREN EINSATZ (Nr. 33). Aktiv an
 *   einem GELOESCHTEN Diensttag ist derselbe Zustand, den Teil 1 beim
 *   Einspielen ablehnt — und die Anwendung selbst konnte ihn herstellen: ueber
 *   das Zurueckholen im Papierkorb, ueber eine Uhr-Kennung in `day_refs` und
 *   ueber das endgueltige Loeschen, das ein Waisenkind zuruecklies.
 *
 *   TEIL 4 — SCHRITT 1 DER WIEDERERKENNUNG RAET NICHT MEHR (Nr. 34). Der
 *   erste gefundene Einsatz bestimmte den Zieltag fuer den ganzen Datei-Tag.
 *   Jetzt zaehlen alle Kennungen; nur ein eindeutiges Ergebnis gilt.
 *
 * Geprueft wird `edbak_restore()` unmittelbar — derselbe Weg, den
 * `api/backup_restore.php` und der Demo-Reset nehmen. NICHT geprueft ist der
 * Weg davor (Entschluesseln im Browser, Hochladen) und die Anzeige danach;
 * dafuer ist der Kreislauf unter `tools/referenzdatensatz/` da.
 *
 * AUFRUF
 *
 *     php tools/wiederherstellungs-probe/probe.php [pfad-zu-server]
 *
 * Ohne Argument wird `server/` dieses Arbeitsstands genommen. Das Argument ist
 * der Vorher-Vergleich: eine GANZE Kopie von `server/` aus dem
 * Vergleichsstand — die Aenderungen liegen in mehreren Dateien.
 *
 *     mkdir /tmp/vorher
 *     git archive <stand> server | tar -x -C /tmp/vorher --strip-components=1
 *     cp server/config.php /tmp/vorher/
 *     php tools/wiederherstellungs-probe/probe.php /tmp/vorher
 *
 * Erwartet: **30 von 30** mit dem heutigen Stand, Rueckgabe 0.
 *
 * WAS SIE ANFASST. Die Probe legt in der Datenbank aus `config.php` fuenf
 * Wegwerfkonten unterhalb von `@example.invalid` an und loescht sie am Ende
 * wieder — samt allem, was daran haengt (Fremdschluessel mit ON DELETE
 * CASCADE). Sie ruehrt kein anderes Konto an. Trotzdem gilt: gegen eine
 * Testinstallation fahren, nicht gegen den Produktivserver.
 */

$server = realpath($argv[1] ?? (__DIR__ . '/../../server'));
if ($server === false) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $server . '/db.php';
require_once $server . '/backup_lib.php';
require_once $server . '/trash_lib.php';

$pdo = db();
$fehler = 0; $gesamt = 0;
$sag = function (string $was, bool $ok, string $ist) use (&$fehler, &$gesamt) {
    $gesamt++;
    if (!$ok) { $fehler++; }
    printf("  [%s] %-62s %s\n", $ok ? 'ok ' : 'FEHL', $was, $ist);
};
$konto = function (string $mail) use ($pdo): int {
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
    $pdo->prepare('INSERT INTO users (email, name, kdf_iter, role, session_epoch)
                   VALUES (?,?,?,?,?)')->execute([$mail, 'Probe', 310000, 'user', 0]);
    return (int)$pdo->lastInsertId();
};
$weg = function (int $uid) use ($pdo): void {
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
};

echo "Wiederherstellungsprobe\n";
echo "  Quelle: $server\n";

/* ==========================================================================
 * TEIL 1 — PAPIERKORB AUS DER DATEI (E-S1-04 / E-S1-19)
 *
 * Ein einziger Diensttag, in der Datei GELOESCHT, und daran dreimal dieselbe
 * Staffelung — einmal als Einsatz, einmal als Ruhesegment:
 *
 *   …1  mit dem Tag geloescht   (deleted_at gesetzt, deleted_with_day = 1)
 *   …2  einzeln geloescht       (deleted_at gesetzt, deleted_with_day = 0)
 *   …3  aktiv                   (deleted_at leer)
 *
 * Die Loeschzeitpunkte in der Datei liegen absichtlich in der Vergangenheit
 * und weit auseinander: Die letzte Erwartung prueft, dass KEINER davon in die
 * Datenbank kommt (E-S1-03).
 * ====================================================================== */
echo "\n  Teil 1 — Papierkorb aus der Datei (E-S1-04 / E-S1-19)\n";
$uid = $konto('probe-papierkorb@example.invalid');

$stats = edbak_restore($uid, [
  'version' => 7,
  'days' => [[
     'id' => 900, 'day' => '2026-07-01',
     'started_at' => '2026-07-01 05:00:00', 'ended_at' => '2026-07-01 17:00:00',
     'kind' => 'air', 'vehicle_name' => 'Probe 1', 'base_name' => 'Probenstation',
     'deleted_at' => '2026-06-01 10:00:00',
  ]],
  'missions' => [
    ['client_ref' => 'p-m1', 'day_id' => 900, 'started_at' => '2026-07-01 06:00:00',
     'ended_at' => '2026-07-01 07:00:00',
     'deleted_at' => '2026-06-01 10:00:00', 'deleted_with_day' => 1],
    ['client_ref' => 'p-m2', 'day_id' => 900, 'started_at' => '2026-07-01 08:00:00',
     'ended_at' => '2026-07-01 09:00:00',
     'deleted_at' => '2026-05-20 09:00:00', 'deleted_with_day' => 0],
    ['client_ref' => 'p-m3', 'day_id' => 900, 'started_at' => '2026-07-01 10:00:00',
     'ended_at' => '2026-07-01 11:00:00',
     'deleted_at' => null,                   'deleted_with_day' => 0],
  ],
  'rest_segments' => [
    ['client_ref' => 'p-r1', 'day_id' => 900, 'started_at' => '2026-07-01 12:00:00',
     'ended_at' => '2026-07-01 12:30:00',
     'deleted_at' => '2026-06-01 10:00:00', 'deleted_with_day' => 1],
    ['client_ref' => 'p-r2', 'day_id' => 900, 'started_at' => '2026-07-01 13:00:00',
     'ended_at' => '2026-07-01 13:30:00',
     'deleted_at' => '2026-05-20 09:00:00', 'deleted_with_day' => 0],
    ['client_ref' => 'p-r3', 'day_id' => 900, 'started_at' => '2026-07-01 14:00:00',
     'ended_at' => '2026-07-01 14:30:00',
     'deleted_at' => null,                   'deleted_with_day' => 0],
  ],
]);

$hole = function (string $tabelle, string $ref) use ($pdo, &$uid) {
    $st = $pdo->prepare("SELECT id, deleted_at, deleted_with_day FROM $tabelle
                         WHERE user_id = ? AND client_ref = ?");
    $st->execute([$uid, $ref]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: null;
};
$m1 = $hole('missions', 'p-m1');
$m2 = $hole('missions', 'p-m2');
$m3 = $hole('missions', 'p-m3');
$r1 = $hole('rest_segments', 'p-r1');
$r2 = $hole('rest_segments', 'p-r2');
$r3 = $hole('rest_segments', 'p-r3');

/* Die Papierkorbliste ist die Probe aufs Exempel fuer E-S1-04: Sie zeigt
 * ausschliesslich `deleted_with_day = 0`. Steht m2 nicht darin, ist er fuer
 * die Benutzerin verschwunden. */
$refsImPapierkorb = [];
foreach (trash_list_missions($uid) as $z) {
    $s = $pdo->prepare('SELECT client_ref FROM missions WHERE id = ?');
    $s->execute([$z['id']]);
    $refsImPapierkorb[] = (string)$s->fetchColumn();
}

$sag('Diensttag kommt als Papierkorbeintrag zurueck',
     (int)($stats['papierkorb']['diensttage'] ?? 0) === 1,
     'papierkorb.diensttage=' . ($stats['papierkorb']['diensttage'] ?? '—'));
$sag('m1 (mit dem Tag geloescht) traegt deleted_with_day = 1',
     $m1 !== null && (int)$m1['deleted_with_day'] === 1,
     'deleted_with_day=' . ($m1['deleted_with_day'] ?? '—'));
$sag('m2 (einzeln geloescht) traegt deleted_with_day = 0',
     $m2 !== null && (int)$m2['deleted_with_day'] === 0,
     'deleted_with_day=' . ($m2['deleted_with_day'] ?? '—'));
$sag('m3 (aktiv, Zieltag im Papierkorb) wurde abgelehnt',
     $m3 === null, $m3 === null ? 'nicht angelegt' : 'ANGELEGT');
$sag('Ablehnung als tag_im_papierkorb gezaehlt (Einsatz + Ruhesegment)',
     (int)($stats['skipped_reasons']['tag_im_papierkorb'] ?? -1) === 2,
     'skipped_reasons.tag_im_papierkorb='
       . ($stats['skipped_reasons']['tag_im_papierkorb'] ?? '—'));
$sag('r1 (mit dem Tag geloescht) traegt deleted_with_day = 1',
     $r1 !== null && (int)$r1['deleted_with_day'] === 1,
     'deleted_with_day=' . ($r1['deleted_with_day'] ?? '—'));
$sag('r2 (einzeln geloescht) traegt deleted_with_day = 0',
     $r2 !== null && (int)$r2['deleted_with_day'] === 0,
     'deleted_with_day=' . ($r2['deleted_with_day'] ?? '—'));
$sag('r3 (aktiv, Zieltag im Papierkorb) wurde abgelehnt',
     $r3 === null, $r3 === null ? 'nicht angelegt' : 'ANGELEGT');
$sag('Papierkorbliste zeigt genau m2 (Einzelloeschung, wiederherstellbar)',
     $refsImPapierkorb === ['p-m2'],
     'Liste = [' . implode(', ', $refsImPapierkorb) . ']');
$sag('Loeschzeitpunkt stammt aus dem Lauf, nicht aus der Datei (E-S1-03)',
     $m1 !== null && (string)$m1['deleted_at'] > '2026-06-01 10:00:00',
     'deleted_at=' . ($m1['deleted_at'] ?? '—'));
$weg($uid);

/* ==========================================================================
 * TEIL 2 — EINE KAPUTTE DATEI KOSTET NUR SICH SELBST (Nr. 31 / Nr. 35)
 *
 * Ein aktiver Diensttag mit zwei Einsaetzen und zwei Ruhesegmenten. Kaputt
 * sind: die Spur des ersten Einsatzes (zweimal `seq` = 1) und das zweite
 * Ruhesegment (`started_at` ist Text). Alles andere ist in Ordnung und MUSS
 * ankommen — genau das war bis Web 8.0.0 nicht so: Beide Faelle loesten eine
 * Ausnahme aus, die ueber die Transaktion den ganzen Lauf zurueckrollte.
 * ====================================================================== */
echo "\n  Teil 2 — kaputte Datei kostet nur sich selbst (Nr. 31 / Nr. 35)\n";
$uid = $konto('probe-robust@example.invalid');

$spur = fn(array $seqs) => array_map(
    fn($s, $i) => [$s, 47.5 + $i / 1000, 11.5 + $i / 1000, 700, 1750000000 + $i],
    $seqs, array_keys($seqs));

$hin = true; $meldung = '';
try {
    $stats = edbak_restore($uid, [
      'version' => 7,
      'days' => [[
         'id' => 910, 'day' => '2026-07-02',
         'started_at' => '2026-07-02 05:00:00', 'ended_at' => '2026-07-02 17:00:00',
         'kind' => 'air', 'vehicle_name' => 'Probe 1', 'base_name' => 'Probenstation',
      ]],
      'missions' => [
        // Spur mit Wiedergaenger: 1, 2, 1, 3 -> drei Punkte, eine Meldung.
        ['client_ref' => 'q-m1', 'day_id' => 910, 'started_at' => '2026-07-02 06:00:00',
         'ended_at' => '2026-07-02 07:00:00', 'track' => $spur([1, 2, 1, 3])],
        ['client_ref' => 'q-m2', 'day_id' => 910, 'started_at' => '2026-07-02 08:00:00',
         'ended_at' => '2026-07-02 09:00:00', 'track' => $spur([1, 2])],
      ],
      'rest_segments' => [
        ['client_ref' => 'q-r1', 'day_id' => 910, 'started_at' => '2026-07-02 12:00:00',
         'ended_at' => '2026-07-02 12:30:00', 'track' => $spur([5, 5, 6])],
        // Unbrauchbarer Zeitwert: Die Spalte ist DATETIME NOT NULL.
        ['client_ref' => 'q-r2', 'day_id' => 910, 'started_at' => 'kein Datum',
         'ended_at' => '2026-07-02 13:30:00'],
      ],
    ]);
} catch (Throwable $ex) {
    $hin = false; $meldung = get_class($ex) . ': ' . $ex->getMessage();
    $stats = [];
}

$sag('Der Lauf ueberlebt die kaputten Stellen ueberhaupt',
     $hin, $hin ? 'keine Ausnahme' : $meldung);
$punkte = function (string $typ, string $ref) use ($pdo, &$uid): int {
    $tab = $typ === 'mission' ? 'missions' : 'rest_segments';
    $st = $pdo->prepare("SELECT COUNT(*) FROM track_points p
                           JOIN $tab o ON o.id = p.owner_id
                          WHERE p.owner_type = ? AND o.user_id = ? AND o.client_ref = ?");
    $st->execute([$typ, $uid, $ref]);
    return (int)$st->fetchColumn();
};
$sag('q-m1: von 1,2,1,3 kommen drei Punkte an (Nr. 35)',
     $hin && $punkte('mission', 'q-m1') === 3,
     'Punkte=' . ($hin ? $punkte('mission', 'q-m1') : '—'));
$sag('q-m2 (heile Spur) ist unversehrt angekommen',
     $hin && $punkte('mission', 'q-m2') === 2,
     'Punkte=' . ($hin ? $punkte('mission', 'q-m2') : '—'));
$sag('q-r1: von 5,5,6 kommen zwei Punkte an (Nr. 35, Ruhesegment)',
     $hin && $punkte('rest', 'q-r1') === 2,
     'Punkte=' . ($hin ? $punkte('rest', 'q-r1') : '—'));
$sag('q-r2 (unbrauchbare Zeit) wurde uebersprungen, nicht geschrieben (Nr. 31)',
     $hin && (int)($stats['rests_skipped'] ?? -1) === 1
          && (int)($stats['rests'] ?? -1) === 1,
     'rests=' . ($stats['rests'] ?? '—') . ' rests_skipped=' . ($stats['rests_skipped'] ?? '—'));
$sag('Die doppelten Nummern stehen in der Ablehnungsliste, nicht nur im Nichts',
     $hin && ($stats['rejected']['mission.track.seq: Nummer doppelt'] ?? 0) === 1
          && ($stats['rejected']['rest.track.seq: Nummer doppelt'] ?? 0) === 1,
     'rejected=' . json_encode(array_intersect_key((array)($stats['rejected'] ?? []),
        ['mission.track.seq: Nummer doppelt' => 1, 'rest.track.seq: Nummer doppelt' => 1]),
        JSON_UNESCAPED_SLASHES));
$weg($uid);

/* ==========================================================================
 * TEIL 3 — DER HALB SICHTBARE EINSATZ HAT KEINEN WEG MEHR (Backlog Nr. 33)
 *
 * Aktiv an einem GELOESCHTEN Diensttag — denselben Zustand, den E-S1-19 beim
 * Einspielen ablehnt, konnte die Anwendung selbst herstellen. Drei Wege
 * fuehrten hin, alle drei werden hier abgeklopft:
 *
 *   a) Papierkorb -> „Wiederherstellen" beim einzeln geloeschten Einsatz,
 *      dessen Tag ebenfalls im Papierkorb liegt.
 *   b) Die Uhr liefert ueber eine Kennung in `day_refs` nach, die auf einen
 *      geloeschten Tag zeigt.
 *   c) Und wenn der Zustand doch besteht (Altbestand): Das endgueltige
 *      Loeschen des Tages darf kein Waisenkind zuruecklassen.
 * ====================================================================== */
echo "\n  Teil 3 — kein Weg mehr zum halb sichtbaren Einsatz (Nr. 33)\n";
$uid = $konto('probe-papierkorb-wege@example.invalid');

require_once $server . '/diensttag_lib.php';

edbak_restore($uid, [
  'version' => 7,
  'days' => [['id' => 920, 'day' => '2026-07-03',
              'started_at' => '2026-07-03 05:00:00', 'ended_at' => '2026-07-03 17:00:00',
              'kind' => 'air', 'vehicle_name' => 'Probe 1', 'base_name' => 'Probenstation']],
  'missions' => [
    ['client_ref' => 'w-a', 'day_id' => 920, 'started_at' => '2026-07-03 06:00:00',
     'ended_at' => '2026-07-03 07:00:00'],
    ['client_ref' => 'w-b', 'day_id' => 920, 'started_at' => '2026-07-03 08:00:00',
     'ended_at' => '2026-07-03 09:00:00'],
  ],
]);
$tagId = (int)$pdo->query("SELECT id FROM days WHERE user_id = $uid")->fetchColumn();
$mA = (int)$pdo->query("SELECT id FROM missions
                         WHERE user_id = $uid AND client_ref = 'w-a'")->fetchColumn();

// (a) Einzeln loeschen, dann den ganzen Tag — und dann zurueckholen wollen.
trash_delete_mission($uid, $mA);
trash_delete_day($uid, $tagId);
$antwort = trash_restore_mission($uid, $mA);
$nochWeg = $pdo->query("SELECT deleted_at IS NOT NULL FROM missions WHERE id = $mA")
               ->fetchColumn();
$sag('(a) Zurueckholen wird abgelehnt, solange der Tag im Papierkorb liegt',
     $antwort === 'tag_im_papierkorb', "Antwort='$antwort'");
$sag('(a) und der Einsatz bleibt dabei im Papierkorb liegen',
     (int)$nochWeg === 1, 'noch geloescht=' . (int)$nochWeg);

// Tag zurueckholen -> jetzt muss es gehen. Der einzeln geloeschte bleibt
// dabei liegen (E-S1-04), lässt sich aber einzeln zurueckholen.
trash_restore_day($uid, $tagId);
$antwort2 = trash_restore_mission($uid, $mA);
$aktiv = $pdo->query("SELECT deleted_at IS NULL FROM missions WHERE id = $mA")->fetchColumn();
$sag('(a) nach dem Zurueckholen des Tages geht es',
     $antwort2 === 'ok' && (int)$aktiv === 1,
     "Antwort='$antwort2', aktiv=" . (int)$aktiv);

// (b) Uhr-Kennung auf einen geloeschten Tag.
$pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label)
               VALUES (?,?,?,?)')->execute([$uid, 'probe-dev', str_repeat('a', 64), 'Probe']);
$devId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO day_refs (day_id, device_id, day_ref) VALUES (?,?,?)')
    ->execute([$tagId, $devId, 'ref-1']);
trash_delete_day($uid, $tagId);
$neuerTag = dt_zu_dayref($pdo, $uid, $devId, 'ref-1', '2026-07-03', '2026-07-03 06:00:00');
$refZiel = (int)$pdo->query("SELECT day_id FROM day_refs
                              WHERE device_id = $devId AND day_ref = 'ref-1'")->fetchColumn();
$neuAktiv = $pdo->query("SELECT deleted_at IS NULL FROM days WHERE id = $neuerTag")->fetchColumn();
$sag('(b) Uhr-Kennung auf geloeschtem Tag loest einen NEUEN Tag aus',
     $neuerTag !== $tagId && (int)$neuAktiv === 1,
     "alt=$tagId neu=$neuerTag aktiv=" . (int)$neuAktiv);
$sag('(b) die Kennung zeigt danach auf den neuen Tag',
     $refZiel === $neuerTag && $refZiel !== $tagId,
     "day_refs.day_id=$refZiel (alt=$tagId)");

/* (c) ALTBESTAND: einen aktiven Einsatz an den geloeschten Tag haengen, wie
 *     ihn ein aelterer Stand hinterlassen haben kann. Von Hand gesetzt — die
 *     regulaeren Wege koennen es seit Web 8.0.0 nicht mehr. */
$pdo->exec("UPDATE missions SET deleted_at = NULL, deleted_with_day = 0,
                                day_id = $tagId, device_id = $devId WHERE id = $mA");
/* GEGEN EINEN AELTEREN STAND gibt es trash_aktiv_am_tag() noch nicht. Die
 * Probe darf daran nicht STERBEN — ein Vorher-Vergleich, der abbricht, sagt
 * nichts ueber die Erwartungen dahinter. Sie faellt stattdessen durch. */
$hang = function_exists('trash_aktiv_am_tag')
      ? trash_aktiv_am_tag($uid, $tagId) : ['einsaetze' => [], 'segmente' => 0];
$sag('(c) die Rueckfrage findet den aktiven Einsatz am geloeschten Tag',
     count($hang['einsaetze']) === 1 && (int)$hang['einsaetze'][0]['id'] === $mA,
     function_exists('trash_aktiv_am_tag')
       ? 'gefunden=' . count($hang['einsaetze'])
       : 'trash_aktiv_am_tag() gibt es in diesem Stand nicht');
trash_purge_day($uid, $tagId);
$waise = (int)$pdo->query("SELECT COUNT(*) FROM missions
                            WHERE user_id = $uid AND day_id IS NULL")->fetchColumn();
$nochDa = (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE id = $mA")->fetchColumn();
$sag('(c) endgueltiges Loeschen laesst KEIN Waisenkind zurueck',
     $waise === 0 && $nochDa === 0, "ohne Diensttag=$waise, Einsatz noch da=$nochDa");
/* Die Sperrliste greift nur fuer Datensaetze MIT Geraet — deshalb traegt der
 * Einsatz oben eines. Ohne das waere die Erwartung unerfuellbar und die Probe
 * ein Fehlalarm: `trash_block_ref()` ueberspringt `device_id IS NULL`
 * absichtlich (von Hand angelegte Eintraege liefert keine Uhr nach). */
$sag('(c) und der aktive Einsatz ist fuer die Uhr gesperrt',
     (int)$pdo->query("SELECT COUNT(*) FROM deleted_refs
                        WHERE device_id = $devId AND client_ref = 'w-a'")->fetchColumn() === 1,
     'Sperrlisteneintraege=' . $pdo->query("SELECT COUNT(*) FROM deleted_refs
                                             WHERE device_id = $devId")->fetchColumn());
$weg($uid);

/* ==========================================================================
 * TEIL 4 — SCHRITT 1 DER WIEDERERKENNUNG RAET NICHT MEHR (Backlog Nr. 34)
 *
 * Im Ziel liegen zwei Einsaetze desselben Datei-Diensttags an VERSCHIEDENEN
 * Tagen (jemand hat einen verschoben). Schritt 1 nahm bisher den ersten
 * Treffer und verhaengte dessen Tag ueber den ganzen Datei-Tag. Jetzt gilt er
 * als ergebnislos, der Fingerabdruck entscheidet, und der Widerspruch wird
 * gezaehlt.
 * ====================================================================== */
echo "\n  Teil 4 — Schritt 1 der Wiedererkennung (Nr. 34)\n";
$uid = $konto('probe-wiedererkennung@example.invalid');

// Ausgangslage im Ziel: zwei Tage, je ein Einsatz.
edbak_restore($uid, [
  'version' => 7,
  'days' => [
    ['id' => 930, 'day' => '2026-07-04', 'started_at' => '2026-07-04 05:00:00',
     'ended_at' => '2026-07-04 17:00:00', 'kind' => 'air',
     'vehicle_name' => 'Probe 1', 'base_name' => 'Probenstation'],
    ['id' => 931, 'day' => '2026-07-05', 'started_at' => '2026-07-05 05:00:00',
     'ended_at' => '2026-07-05 17:00:00', 'kind' => 'air',
     'vehicle_name' => 'Probe 2', 'base_name' => 'Probenstation'],
  ],
  'missions' => [
    ['client_ref' => 'v-1', 'day_id' => 930, 'started_at' => '2026-07-04 06:00:00',
     'ended_at' => '2026-07-04 07:00:00'],
    ['client_ref' => 'v-2', 'day_id' => 931, 'started_at' => '2026-07-04 08:00:00',
     'ended_at' => '2026-07-04 09:00:00'],
  ],
]);
$tagA = (int)$pdo->query("SELECT day_id FROM missions
                           WHERE user_id = $uid AND client_ref = 'v-1'")->fetchColumn();
$tagB = (int)$pdo->query("SELECT day_id FROM missions
                           WHERE user_id = $uid AND client_ref = 'v-2'")->fetchColumn();
$tageVorher = (int)$pdo->query("SELECT COUNT(*) FROM days WHERE user_id = $uid")->fetchColumn();

/* Die Datei fuehrt EINEN Tag mit v-1, v-2 und einem neuen v-3. Der
 * Fingerabdruck passt auf keinen der beiden Zieltage (anderes Rettungsmittel),
 * es MUSS also ein dritter entstehen — und v-3 gehoert dorthin, nicht an
 * Tag A oder B. */
$stats = edbak_restore($uid, [
  'version' => 7,
  'days' => [['id' => 940, 'day' => '2026-07-04',
              'started_at' => '2026-07-04 04:00:00', 'ended_at' => '2026-07-04 18:00:00',
              'kind' => 'air', 'vehicle_name' => 'Probe 9', 'base_name' => 'Probenstation']],
  'missions' => [
    ['client_ref' => 'v-1', 'day_id' => 940, 'started_at' => '2026-07-04 06:00:00',
     'ended_at' => '2026-07-04 07:00:00'],
    ['client_ref' => 'v-2', 'day_id' => 940, 'started_at' => '2026-07-04 08:00:00',
     'ended_at' => '2026-07-04 09:00:00'],
    ['client_ref' => 'v-3', 'day_id' => 940, 'started_at' => '2026-07-04 10:00:00',
     'ended_at' => '2026-07-04 11:00:00'],
  ],
]);
$tagV3 = $pdo->query("SELECT day_id FROM missions
                       WHERE user_id = $uid AND client_ref = 'v-3'")->fetchColumn();
$tageNachher = (int)$pdo->query("SELECT COUNT(*) FROM days WHERE user_id = $uid")->fetchColumn();

$sag('Der Widerspruch wird als tag_mehrdeutig gemeldet',
     (int)($stats['skipped_reasons']['tag_mehrdeutig'] ?? 0) === 1,
     'tag_mehrdeutig=' . ($stats['skipped_reasons']['tag_mehrdeutig'] ?? '—'));
$sag('Der Datei-Tag wird NICHT auf Tag A oder B verhaengt',
     $tagV3 !== false && (int)$tagV3 !== $tagA && (int)$tagV3 !== $tagB,
     "v-3 an Tag " . var_export($tagV3, true) . " (A=$tagA, B=$tagB)");
$sag('Stattdessen entsteht ein eigener Diensttag',
     $tageNachher === $tageVorher + 1,
     "Diensttage $tageVorher -> $tageNachher");
$sag('Die schon vorhandenen Einsaetze bleiben, wo sie sind',
     (int)$pdo->query("SELECT day_id FROM missions
                        WHERE user_id = $uid AND client_ref = 'v-1'")->fetchColumn() === $tagA
  && (int)$pdo->query("SELECT day_id FROM missions
                        WHERE user_id = $uid AND client_ref = 'v-2'")->fetchColumn() === $tagB,
     'v-1 und v-2 unveraendert');
$weg($uid);

/* Gegenprobe: EIN eindeutiger Kandidat -> Schritt 1 greift wie bisher. Ohne
 * sie belegte Teil 4 nur, dass die Wiedererkennung nichts mehr findet. */
$uid = $konto('probe-wiedererkennung2@example.invalid');
edbak_restore($uid, [
  'version' => 7,
  'days' => [['id' => 950, 'day' => '2026-07-06', 'started_at' => '2026-07-06 05:00:00',
              'ended_at' => '2026-07-06 17:00:00', 'kind' => 'air',
              'vehicle_name' => 'Probe 1', 'base_name' => 'Probenstation']],
  'missions' => [['client_ref' => 'e-1', 'day_id' => 950,
                  'started_at' => '2026-07-06 06:00:00', 'ended_at' => '2026-07-06 07:00:00']],
]);
$tagE = (int)$pdo->query("SELECT day_id FROM missions
                           WHERE user_id = $uid AND client_ref = 'e-1'")->fetchColumn();
// Dieselbe Datei, aber mit verändertem Fingerabdruck UND einem zweiten Einsatz:
// Nur Schritt 1 kann den Tag jetzt noch wiedererkennen.
$stats = edbak_restore($uid, [
  'version' => 7,
  'days' => [['id' => 951, 'day' => '2026-07-06', 'started_at' => '2026-07-06 04:30:00',
              'ended_at' => '2026-07-06 17:30:00', 'kind' => 'air',
              'vehicle_name' => 'Probe 1 (umbenannt)', 'base_name' => 'Probenstation']],
  'missions' => [
    ['client_ref' => 'e-1', 'day_id' => 951, 'started_at' => '2026-07-06 06:00:00',
     'ended_at' => '2026-07-06 07:00:00'],
    ['client_ref' => 'e-2', 'day_id' => 951, 'started_at' => '2026-07-06 08:00:00',
     'ended_at' => '2026-07-06 09:00:00'],
  ],
]);
$sag('GEGENPROBE: ein eindeutiger Kandidat -> Schritt 1 greift weiter',
     (int)$pdo->query("SELECT day_id FROM missions
                        WHERE user_id = $uid AND client_ref = 'e-2'")->fetchColumn() === $tagE
  && (int)$pdo->query("SELECT COUNT(*) FROM days WHERE user_id = $uid")->fetchColumn() === 1,
     'e-2 an Tag ' . $pdo->query("SELECT day_id FROM missions
                        WHERE user_id = $uid AND client_ref = 'e-2'")->fetchColumn()
     . " (erwartet $tagE), Diensttage "
     . $pdo->query("SELECT COUNT(*) FROM days WHERE user_id = $uid")->fetchColumn());
$sag('GEGENPROBE: dabei wird nichts als mehrdeutig gemeldet',
     !isset($stats['skipped_reasons']['tag_mehrdeutig']),
     'tag_mehrdeutig=' . ($stats['skipped_reasons']['tag_mehrdeutig'] ?? '—'));
$weg($uid);

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $gesamt, $fehler);
exit($fehler === 0 ? 0 : 1);
