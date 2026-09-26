<?php
declare(strict_types=1);

/**
 * Wiederherstellungsprobe — Beleg zu E-S1-04, E-S1-19 und Backlog Nr. 31/33/34/35.
 *
 * Anlass: Nr. 31, 33, 34, 35 — kaputte Datei kippte den Lauf, Waisenkind, Diensttag geraten
 *
 * WOFUER. Der Papierkorb und der Rueckweg eines Backups haben Grenzfaelle,
 * die sich im Browser nur muehsam herstellen lassen und die man dem Ergebnis
 * nicht ansieht. Vier Teile:
 *
 *   TEIL 1 — PAPIERKORB. Seit Nutzlast 7 traegt die Backup-Datei den
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
 *     php tools/proben/wiederherstellung/probe.php [pfad-zu-server]
 *
 * Ohne Argument wird `server/` dieses Arbeitsstands genommen. Das Argument ist
 * der Vorher-Vergleich: eine GANZE Kopie von `server/` aus dem
 * Vergleichsstand — die Aenderungen liegen in mehreren Dateien.
 *
 *     mkdir /tmp/vorher
 *     git archive <stand> server | tar -x -C /tmp/vorher --strip-components=1
 *     cp server/config.php /tmp/vorher/
 *     php tools/proben/wiederherstellung/probe.php /tmp/vorher
 *
 * Erwartet: **115 von 115** mit dem heutigen Stand, Rueckgabe 0 (gemessen
 * 25.09.2026, P5c/AP8: vier aus Teil 13; davor 111 — die 110 hier war seit
 * P5c/AP2 um eine zu klein und stand ohne Spur, derselbe Fehler wie am
 * 16.09.).
 * (94 bis S10/AP4 — Teil 8 hat mit der Fassung 3 vier Erwartungen dazu
 *  bekommen, Teil 12 acht. Am 16.09.2026 stand hier 106 und gemessen wurden
 *  105: eine Erwartung Unterschied, entstanden ohne Spur. Mit den fuenf
 *  neuen aus P5a/AP10 — Nr. 195, die Geraeteart auf dem Rueckweg — waren es
 *  110; die 111. kam mit RP-03. Wer die Zahl hier nicht mitfuehrt, hat beim
 *  naechsten Lauf keinen Vergleich, sondern nur ein Gefuehl.
 *
 *  BIS RP-03 WAREN ZWEI DAVON AUF EINER LEEREN INSTALLATION ROT (Teil 10,
 *  Backlog Nr. 212): Der Sammelvorgang „Alle sichern" bekommt ein enges
 *  Zeitbudget und soll danach etwas offen lassen — bei zwei fast leeren
 *  Konten passten beide hinein. Seit RP-03 legt Teil 10 zwei Zusatzkonten
 *  an und faehrt eine gestellte Uhr; die Erwartung haengt damit am Bestand,
 *  nicht an der Geschwindigkeit der Maschine („2 erledigt, 2 von 4 offen").)
 *
 * (Die Zahl stand bis Web 14.2.0 auf 30 und war seit Langem falsch — die
 * Probe ist auf elf Teile gewachsen. Sie ist ausserdem seit einiger Zeit
 * MITTEN IN TEIL 9 abgestuerzt, an einem fehlenden `require` fuer
 * `smtp.php`; alles dahinter lief nie. Beides ist mit R64/AP2 behoben.)
 *
 *   TEIL 13 — EINE 11er-DATEI MIT DER STANDORTAUSWAHL (P5c/AP8, Nr. 168):
 *   Das Feld der gefallenen Tabelle wird still ueberlesen, und die Sicherung
 *   danach traegt Nutzlast 12. Vier Erwartungen.
 *
 *   TEIL 11 — NUTZLAST 9: MOMENTAUFNAHME UND SPERRVERMERKE (R64, Nr. 63).
 *   Der Regelfall — ein Vermerk faehrt hin und kommt zurueck — steht im
 *   edbak-Kreislauf des Referenzbestands. Hier stehen die Grenzfaelle, die
 *   der Referenzbestand nicht zeigt: zweites Einspielen, verwaiste Quelle,
 *   Zeiten verkehrt herum, `n_punkte` auf 0, eine unbekannte `quelle_art`
 *   (die ENUM-Falle) und eine Nutzlast-8-Datei ohne die neuen Felder.
 *
 * WAS SIE ANFASST. Die Probe legt in der Datenbank aus `config.php` fuenf
 * Wegwerfkonten unterhalb von `@example.invalid` an und loescht sie am Ende
 * wieder — samt allem, was daran haengt (Fremdschluessel mit ON DELETE
 * CASCADE). Sie ruehrt kein anderes Konto an. Trotzdem gilt: gegen eine
 * Testinstallation fahren, nicht gegen den Produktivserver.
 */

$server = realpath($argv[1] ?? (__DIR__ . '/../../../server'));
if ($server === false) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $server . '/db.php';
require_once $server . '/backup_lib.php';
require_once $server . '/trash_lib.php';
/* `smtp.php` FEHLTE HIER, und die Probe ist daran gestorben — mit einem
 * `Call to undefined function smtp_eingerichtet()` mitten in Teil 9. Alles
 * dahinter (Teil 10, der Auftrag „Alle sichern") lief seitdem NIE, ohne dass
 * es jemandem auffiel: Der Abbruch kam nach dreiundvierzig gruenen Zeilen,
 * und wer nur auf die letzte Zeile sieht, sieht gar keine. Gefunden beim
 * Anhaengen von Teil 11 (R64/AP2). */
require_once $server . '/smtp.php';

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

/* ======================================================================
 * Teil 5 — Nutzlast 8: die Spurkarte und die Wiederaufnahme (S2/AP5)
 *
 * Nutzlast 8 traegt keine Punktlisten, sondern je Spur eine `spur_ref`. Der
 * Rueckweg muss deshalb sagen, unter welcher Kennung er jede angelegt hat —
 * sonst kann der Browser die Blobs nicht zuordnen.
 *
 * DIE ZWEITE ERWARTUNG IST DIE WICHTIGE. Bricht das Einspielen zwischen Kern
 * und Spurteilen ab, ist beim zweiten Anlauf JEDER Eintrag „bereits
 * vorhanden". Fuellte die Karte sich dann nicht, waeren die Spuren NIE mehr
 * einzuspielen — sie meldeten „ohne zugehoerigen Einsatz", und der Bestand
 * bliebe fuer immer ohne sie.
 *
 * Gefunden bei der Abnahme am 5000er-Bestand: 10 431 Spuren „ohne
 * zugehoerigen Einsatz", nachdem eine Anfrage an einer Mengengrenze
 * gescheitert war.
 * ====================================================================== */
echo "\n  Teil 5 — Nutzlast 8: Spurkarte und Wiederaufnahme (S2/AP5)\n";
$uid = $konto('probe-spurkarte@example.invalid');

$nutzlast8 = [
  'version' => 8,
  'days' => [[
     'id' => 940, 'day' => '2026-07-05',
     'started_at' => '2026-07-05 05:00:00', 'ended_at' => '2026-07-05 17:00:00',
     'kind' => 'ground', 'vehicle_name' => 'Probe 8', 'base_name' => 'Probenstation',
  ]],
  'missions' => [
    ['client_ref' => 's-m1', 'day_id' => 940, 'started_at' => '2026-07-05 06:00:00',
     'ended_at' => '2026-07-05 07:00:00',
     'spur_ref' => 1, 'stufe' => 2, 'n_original' => 3, 'n' => 3],
    ['client_ref' => 's-m2', 'day_id' => 940, 'started_at' => '2026-07-05 08:00:00',
     'ended_at' => '2026-07-05 09:00:00'],          // ohne Spur: keine spur_ref
  ],
  'rest_segments' => [
    ['client_ref' => 's-r1', 'day_id' => 940, 'started_at' => '2026-07-05 10:00:00',
     'ended_at' => '2026-07-05 11:00:00',
     'spur_ref' => 2, 'stufe' => 2, 'n_original' => 2, 'n' => 2],
  ],
];

$stats = edbak_restore($uid, $nutzlast8);
$karte = $stats['spur_karte'] ?? null;
$sag('Nutzlast 8 wird angenommen und legt an',
     ($stats['missions'] ?? 0) === 2 && ($stats['rests'] ?? 0) === 1,
     'Einsaetze ' . ($stats['missions'] ?? '—') . ', Ruhesegmente ' . ($stats['rests'] ?? '—'));
$sag('Der Rueckweg liefert eine Spurkarte',
     is_array($karte), $karte === null ? 'keine' : count($karte) . ' Eintraege');
$sag('Sie nennt nur Objekte MIT spur_ref',
     is_array($karte) && count($karte) === 2
       && ($karte[1]['art'] ?? '') === 'mission' && ($karte[2]['art'] ?? '') === 'rest',
     json_encode($karte));

/* Zweiter Lauf: alles ist „bereits vorhanden" — die Karte muss trotzdem
 * stehen, sonst gibt es keine Wiederaufnahme. */
$stats2 = edbak_restore($uid, $nutzlast8);
$karte2 = $stats2['spur_karte'] ?? null;
$sag('WIEDERAUFNAHME: der zweite Lauf legt nichts an',
     ($stats2['missions'] ?? -1) === 0 && ($stats2['rests'] ?? -1) === 0,
     'uebersprungen ' . ($stats2['missions_skipped'] ?? '—') . '/'
     . ($stats2['rests_skipped'] ?? '—'));
$sag('WIEDERAUFNAHME: und liefert dieselbe Spurkarte',
     $karte2 == $karte,
     $karte2 === null ? 'keine' : (count($karte2) . ' Eintraege, gleich: '
       . ($karte2 == $karte ? 'ja' : 'NEIN')));

/* Nutzlast 7 bekommt KEINE Karte — sie traegt ihre Punkte selbst. */
$uid7 = $konto('probe-nutzlast7@example.invalid');
$stats7 = edbak_restore($uid7, [
  'version' => 7,
  'days' => [['id' => 950, 'day' => '2026-07-06', 'kind' => 'ground',
              'vehicle_name' => 'Probe 7', 'base_name' => 'Probenstation']],
  'missions' => [['client_ref' => 'a-m1', 'day_id' => 950,
                  'started_at' => '2026-07-06 06:00:00',
                  'ended_at' => '2026-07-06 07:00:00',
                  'track' => [[0, 47.1, 11.1, 700.0, 1783000000]]]],
  'rest_segments' => [],
]);
$sag('Nutzlast 7 bekommt keine Spurkarte (sie traegt ihre Punkte selbst)',
     !isset($stats7['spur_karte']),
     isset($stats7['spur_karte']) ? 'es gibt eine' : 'keine — richtig');
$sag('und ihre Punkte kommen weiterhin an',
     spur_zahlen($pdo, 'mission', [(int)$pdo->query("SELECT id FROM missions
        WHERE user_id = $uid7 AND client_ref = 'a-m1'")->fetchColumn()])[
        (int)$pdo->query("SELECT id FROM missions
        WHERE user_id = $uid7 AND client_ref = 'a-m1'")->fetchColumn()] === 1,
     'Punkte in der Datenbank');
$weg($uid7);
$weg($uid);

/* ---- Die Zahl, die den Fehler ausgeloest hat, an beiden Orten ---------- */
echo "\n  Teil 6 — Eine Mengengrenze, zwei Orte (S2/AP5)\n";

/* WOFUER. Der Browser buendelt die Spurteile fuer den Rueckweg; der Endpunkt
 * deckelt, wie viele er je Anfrage annimmt. Der erste Entwurf buendelte NUR
 * nach Groesse — und scheiterte bei der Abnahme am 5000er-Bestand, weil in
 * 1,5 MB weit mehr als 500 kurze Ruhespuren passen.
 *
 * Die Zahl steht seither an zwei Orten, und das ist ein bekannter Mangel.
 * Diese Pruefung haelt sie zusammen: Laufen sie auseinander, faellt es hier
 * auf und nicht erst an einem grossen Bestand. */
$endpunkt = file_get_contents(dirname(__DIR__, 3) . '/server/api/backup_spuren_restore.php');
$browser  = file_get_contents(dirname(__DIR__, 3) . '/server/einstellungen.php');
preg_match('/BACKUP_SPUREN_RESTORE_MAX\s*=\s*(\d+)/', $endpunkt, $mE);
preg_match('/HAPPEN_ZAHL\s*=\s*(\d+)/', $browser, $mB);
$sag('Endpunkt und Browser nennen dieselbe Hoechstzahl je Anfrage',
     isset($mE[1], $mB[1]) && $mE[1] === $mB[1],
     'Endpunkt ' . ($mE[1] ?? '—') . ', Browser ' . ($mB[1] ?? '—'));

/* DASSELBE FUER DIE EINTRAGSFENSTER (S2/AP5b). Der Browser holt den Kern in
 * Fenstern zu FENSTER Eintraegen; `api/backup_data.php` nimmt hoechstens
 * BACKUP_EINTRAEGE_MAX je Anfrage. Waere FENSTER groesser, antwortete der
 * Endpunkt mit 400 und das Backup braeche ab — laut, aber erst im Betrieb
 * und ausgerechnet beim grossen Bestand, fuer den die Fenster da sind. */
$abruf = file_get_contents(dirname(__DIR__, 3) . '/server/api/backup_data.php');
preg_match('/BACKUP_EINTRAEGE_MAX\s*=\s*(\d+)/', $abruf, $mA);
preg_match('/const FENSTER\s*=\s*(\d+)/', $browser, $mF);
$sag('Das Eintragsfenster des Browsers passt in die Grenze des Endpunkts',
     isset($mA[1], $mF[1]) && (int)$mF[1] >= 1 && (int)$mF[1] <= (int)$mA[1],
     'Fenster ' . ($mF[1] ?? '—') . ' von hoechstens ' . ($mA[1] ?? '—'));

/* UND DASS DER BROWSER NACHZAEHLT. Die Schleife rueckt um FENSTER weiter,
 * gleichgueltig wie viel zurueckkam; ohne diese Pruefung fehlten zu kurz
 * gelieferte Eintraege in der Datei, waehrend die Meldung „Fertig" lautet. */
$sag('Der Browser zaehlt nach, wie viele Eintraege ein Fenster brachte',
     (bool)preg_match('/statt \$\{soll\} Eintr/u', $browser),
     'Schranke gegen ein zu kurz geliefertes Fenster');

/* ---- Der Widerspruch: Nutzlast 8 MIT Punktlisten (S2/AP6, F-S2-E) ------
 *
 * WOFUER. Nutzlast 8 sagt zu, dass die Punkte in eigenen Teilen nachkommen.
 * Traegt eine Datei beides — Fassung 8 UND `track`-Listen —, wurden die
 * Punkte bis Web 11.1.0 stillschweigend uebergangen: Der Eintrag entstand
 * ohne Spur, die Meldung lautete „fertig".
 *
 * Gefunden beim Aufbau des Pruefbestands fuer AP6: Der Vervielfaeltiger des
 * Messstands erbte seit Web 11.0.0 die Fassung aus der Referenz und schrieb
 * `version: 8` in eine einteilige Datei. Ein Lauf legte 164 Einsaetze an und
 * verlor 91 208 Punkte.
 *
 * Diese Pruefung haelt beides fest: dass die Punkte weiterhin NICHT
 * geschrieben werden (die Fassung entscheidet, nicht das Feld) und dass es
 * GESAGT wird. */
echo "\n  Teil 7 — Nutzlast 8 mit Punktlisten faellt auf (S2/AP6, F-S2-E)\n";
$uid8 = $konto('probe-widerspruch@example.invalid');
$stats8 = edbak_restore($uid8, [
  'version' => 8,
  'days' => [['id' => 960, 'day' => '2026-07-07', 'kind' => 'ground',
              'vehicle_name' => 'Probe 8w', 'base_name' => 'Probenstation']],
  'missions' => [['client_ref' => 'w-m1', 'day_id' => 960,
                  'started_at' => '2026-07-07 06:00:00',
                  'ended_at' => '2026-07-07 07:00:00',
                  'track' => [[0, 47.1, 11.1, 700.0, 1783000000],
                              [1, 47.2, 11.2, 705.0, 1783000060]]]],
  'rest_segments' => [['client_ref' => 'w-r1', 'day_id' => 960,
                       'started_at' => '2026-07-07 08:00:00',
                       'ended_at' => '2026-07-07 09:00:00',
                       'track' => [[0, 47.3, 11.3, 710.0, 1783000120]]]],
]);
$mid8 = (int)$pdo->query("SELECT id FROM missions
    WHERE user_id = $uid8 AND client_ref = 'w-m1'")->fetchColumn();
$sag('Der Eintrag entsteht trotzdem — ein Teilverlust wird kein Totalverlust',
     ($stats8['missions'] ?? 0) === 1 && ($stats8['rests'] ?? 0) === 1,
     'Einsaetze ' . ($stats8['missions'] ?? '—') . ', Ruhesegmente '
     . ($stats8['rests'] ?? '—'));
$sag('Die Punkte werden NICHT geschrieben (die Fassung entscheidet)',
     $mid8 > 0 && (spur_zahlen($pdo, 'mission', [$mid8])[$mid8] ?? 0) === 0,
     'Punkte in der Datenbank: '
     . ($mid8 > 0 ? (spur_zahlen($pdo, 'mission', [$mid8])[$mid8] ?? 0) : '—'));
$abgelehnt = $stats8['rejected'] ?? [];
$treffer = [];
foreach ($abgelehnt as $grund => $zahl) {
    /* GESUCHT WIRD DIE EIGENSCHAFT, NICHT DIE ZAHL (Web 14.2.0).
     * Bis dahin stand hier `str_contains($grund, 'Nutzlast 8')` — der einzige
     * faktische Gleichheitsvergleich gegen die 8 im ganzen Repositorium, und
     * zwar als Zeichenkettensuche 1200 Zeilen und zwei Verzeichnisse von der
     * Meldung entfernt, die er sucht. Mit Nutzlast 9 haette er nichts mehr
     * gefunden, und der Teil waere still gruen geblieben. */
    if (str_contains((string)$grund, 'trotzdem im Eintrag')) { $treffer[(string)$grund] = $zahl; }
}
$sag('...und es wird GESAGT, je Art einmal',
     count($treffer) === 2 && array_sum($treffer) === 2,
     $treffer ? json_encode($treffer, JSON_UNESCAPED_UNICODE) : 'KEINE Meldung');
$sag('GEGENPROBE: Nutzlast 8 OHNE Punktlisten meldet nichts dergleichen',
     !array_filter(array_keys((array)($stats['rejected'] ?? [])),
                   fn($g) => str_contains((string)$g, 'trotzdem im Eintrag')),
     'aus Teil 5, dieselbe Fassung, aber mit spur_ref');
$weg($uid8);

/* ==========================================================================
 * Teil 8 — Das Adminpaket: Rundlauf und Siegel (S2/AP6, Fassung 3 seit S10/AP4)
 *
 * WOFUER. Das Admin-Backup ist seit Web 11.2.0 ein mehrteiliges ZIP und
 * wird in Fenstern gebaut. Zwei Dinge muessen dabei stimmen, und beide gehen
 * STILL schief, wenn sie es nicht tun:
 *
 *   1. Die Spuren. Ein Fassung-2-Kern traegt Nutzlast 8 — die Punkte stehen
 *      in eigenen Teilen. Wer ihn durch den einteiligen Weg schickt, bekommt
 *      jeden Einsatz und keine einzige Spur (F-S2-E, 91 208 Punkte).
 *   2. Die Sperre aus E20. `edbak_paket_hat_geschuetzte()` sah frueher in die
 *      Einsatzliste des Pakets; die steht im gefensterten Kern nicht mehr.
 *      Ohne die Zahl im Manifest liefert sie still `false`, und ein Paket mit
 *      unlesbaren Angaben ginge als „direkt einspielbar" durch.
 * ====================================================================== */
echo "\n  Teil 8 — Adminpaket Fassung 3: Rundlauf und Siegel (S2/AP6, S10/AP4)\n";
require_once $server . '/adminbackup_lib.php';
require_once $server . '/format_lib.php';   // groesse_text() (Schritt 15/AP7)

$quelle = $konto('probe-adminquelle@example.invalid');
$kennung = bin2hex(random_bytes(8));
$pdo->prepare('UPDATE users SET account_key = ?, pat_key_check = ? WHERE id = ?')
    ->execute([$kennung, str_repeat('a', 32), $quelle]);

/* Bestand ueber den regulaeren Rueckweg anlegen — Nutzlast 7 mit Punkten. */
edbak_restore($quelle, [
  'version' => 7,
  'days' => [['id' => 970, 'day' => '2026-07-08', 'kind' => 'ground',
              'vehicle_name' => 'Probe A6', 'base_name' => 'Probenstation']],
  'missions' => [
    ['client_ref' => 'a-m1', 'day_id' => 970, 'started_at' => '2026-07-08 06:00:00',
          /* Ein Chiffretext in der Form, die validate_lib.php verlangt
      * (`edk1:` plus mindestens 40 base64-Zeichen). Inhalt beliebig — er
      * wird hier nie geoeffnet, und genau das ist der Punkt: Die Sperre aus
      * E20 haengt daran, DASS er da ist, nicht daran, was drinsteht. */
     'ended_at' => '2026-07-08 07:00:00',
     'pat_blob' => 'edk1:' . str_repeat('QUJD', 16),
     'track' => [[0, 47.10, 11.10, 700.0, 1783100000],
                 [1, 47.11, 11.11, 705.0, 1783100060],
                 [2, 47.12, 11.12, 710.0, 1783100120]]],
    ['client_ref' => 'a-m2', 'day_id' => 970, 'started_at' => '2026-07-08 08:00:00',
     'ended_at' => '2026-07-08 09:00:00'],                       // ohne Spur
  ],
  'rest_segments' => [
    ['client_ref' => 'a-r1', 'day_id' => 970, 'started_at' => '2026-07-08 10:00:00',
     'ended_at' => '2026-07-08 11:00:00',
     'track' => [[0, 47.20, 11.20, 600.0, 1783103000],
                 [1, 47.21, 11.21, 605.0, 1783103060]]],
  ],
]);

[$ok6, $grund6, $info6] = edbak_sicherung_erzeugen($quelle);
$sag('Das Backup entsteht', $ok6 === true, $ok6 ? (string)$info6['datei'] : (string)$grund6);
$datei6 = $ok6 ? (string)$info6['datei'] : '';
$sag('Sie ist ein ZIP (mehrteilig, am Namen erkennbar)',
     $datei6 !== '' && edbak_paket_fassung($datei6) === 2, $datei6 ?: '—');

/* ZWEI ZAHLEN, DIE „FASSUNG" HEISSEN. `edbak_paket_fassung()` liest die
 * ENDUNG (2 = mehrteiliges ZIP) und bleibt bei 2, auch fuer ein Paket der
 * Fassung 3 — sie unterscheidet einteiliges JSON von mehrteiligem ZIP. Die
 * Fassung des FORMATS steht im Manifest und ist seit S10/AP4 die 3. Wer die
 * beiden verwechselt, bricht sechs Vergleichsstellen in adminbackup_lib.php. */
$manifest = $datei6 ? edbak_paket_kopf_lesen($kennung, $datei6) : null;
$sag('Das Manifest laesst sich ohne den Bestand lesen',
     is_array($manifest) && (int)($manifest['version'] ?? 0) === 3,
     is_array($manifest) ? 'Fassung ' . $manifest['version'] : 'nicht lesbar');
/* DIE NUMMER ALLEIN IST KEIN BELEG (S10/AP4). Ein Paket, das „3" ins
 * Manifest schreibt und die Teile offen ablegt, kaeme hier durch. Gezaehlt
 * wird das Siegel an JEDEM Eintrag, am rohen Archiv. */
$zipSiegel = [0, 0];
if ($datei6 !== '') {
    $zs = new ZipArchive();
    if ($zs->open(edbak_ordner($kennung) . '/' . $datei6) === true) {
        for ($i = 0; $i < $zs->numFiles; $i++) {
            $zipSiegel[0]++;
            if (sk_versiegelt((string)$zs->getFromIndex($i, 8))) { $zipSiegel[1]++; }
        }
        $zs->close();
    }
}
$sag('Jeder Eintrag im ZIP traegt das Siegel edsk1:',
     $zipSiegel[0] > 0 && $zipSiegel[1] === $zipSiegel[0],
     $zipSiegel[1] . ' von ' . $zipSiegel[0] . ' Eintraegen');
$sag('Und die Begleitdatei konto.json daneben ebenso',
     sk_versiegelt((string)@file_get_contents(edbak_ordner($kennung) . '/konto.json')),
     'edsk1: im ganzen Ablageordner');
$sag('Es nennt Eintraege, Spuren und Punkte',
     is_array($manifest) && (int)$manifest['eintraege'] === 3
       && (int)$manifest['spuren'] === 2 && (int)$manifest['punkte'] === 5,
     is_array($manifest)
       ? "{$manifest['eintraege']} Eintraege, {$manifest['spuren']} Spuren, {$manifest['punkte']} Punkte"
       : '—');
$sag('Es zaehlt die Einsaetze mit geschuetzten Angaben',
     is_array($manifest) && (int)($manifest['geschuetzte'] ?? -1) === 1,
     is_array($manifest) ? 'geschuetzte=' . ($manifest['geschuetzte'] ?? '—') : '—');
$sag('E20-Sperre greift ueber das Manifest, nicht ueber die Einsatzliste',
     is_array($manifest) && edbak_paket_hat_geschuetzte($manifest) === true,
     'ein Paket mit Chiffretext gilt als nicht direkt einspielbar');

/* Der Rundlauf: in ein FRISCHES Konto. */
$ziel6 = $konto('probe-adminziel@example.invalid');
[$ok7, $grund7, $st7] = $datei6
    ? edbak_paket_einspielen($kennung, $datei6, $ziel6)
    : [false, 'keine Datei', null];
$sag('Das Paket spielt sich in ein frisches Konto ein', $ok7 === true, (string)$grund7);
$sag('Einsaetze, Ruhesegmente und Diensttage kommen an',
     is_array($st7) && ($st7['missions'] ?? 0) === 2 && ($st7['rests'] ?? 0) === 1
       && ($st7['days'] ?? 0) === 1,
     is_array($st7) ? sprintf('%d/%d/%d', $st7['missions'] ?? 0, $st7['rests'] ?? 0,
                              $st7['days'] ?? 0) : '—');

/* DIE ENTSCHEIDENDE ZAHL — ueber spur_lib.php gezaehlt, nicht per SQL: Die
 * Punkte liegen je nach Alter als Zeilen ODER als Blob (CLAUDE.md 4). */
$mids = $pdo->query("SELECT id FROM missions WHERE user_id = $ziel6 ORDER BY client_ref")
            ->fetchAll(PDO::FETCH_COLUMN);
$rids = $pdo->query("SELECT id FROM rest_segments WHERE user_id = $ziel6")
            ->fetchAll(PDO::FETCH_COLUMN);
$punkte = array_sum(spur_zahlen($pdo, 'mission', array_map('intval', $mids)))
        + array_sum(spur_zahlen($pdo, 'rest', array_map('intval', $rids)));
$sag('UND DIE SPUREN KOMMEN MIT (F-S2-E: hier ging es einmal still verloren)',
     $punkte === 5, "$punkte von 5 Punkten");
$sag('Der Lauf meldet, wie viele Spuren er geschrieben hat',
     is_array($st7) && (int)($st7['spuren']['geschrieben'] ?? -1) === 2
       && (int)($st7['spuren']['ohne_ziel'] ?? -1) === 0,
     is_array($st7) ? json_encode($st7['spuren'] ?? null) : '—');

/* WIEDERAUFNAHME: derselbe Lauf noch einmal darf nichts doppeln. */
[$ok8, , $st8] = edbak_paket_einspielen($kennung, $datei6, $ziel6);
$sag('WIEDERAUFNAHME: ein zweiter Lauf legt nichts an und ueberschreibt nichts',
     $ok8 === true && ($st8['missions'] ?? -1) === 0 && ($st8['rests'] ?? -1) === 0
       && (int)($st8['spuren']['uebersprungen'] ?? -1) === 2,
     is_array($st8) ? sprintf('angelegt %d/%d, Spuren uebersprungen %d',
        $st8['missions'] ?? 0, $st8['rests'] ?? 0,
        $st8['spuren']['uebersprungen'] ?? 0) : '—');

/* Ein fehlendes Teil faellt auf, BEVOR etwas geschrieben wird.
 *
 * AM PAKET SELBST, NICHT AN EINER KOPIE (S10/AP4). Hier wurde bis dahin
 * unter einem anderen Namen kopiert und aus der Kopie ein Teil geloescht.
 * Seit E-S10-U-11 bindet das Siegel den PAKETNAMEN — die Kopie ist damit
 * schon deshalb unlesbar, und die Probe maass den Namen statt das fehlende
 * Teil. Gearbeitet wird jetzt am Original, mit einer Sicherung ausserhalb
 * der Ablage; der Namensfall steht als eigene Erwartung darunter. */
$pfad6 = edbak_ordner($kennung) . '/' . $datei6;
$sicher6 = sys_get_temp_dir() . '/whprobe-' . bin2hex(random_bytes(4)) . '.zip';
copy($pfad6, $sicher6);
$zz = new ZipArchive(); $zz->open($pfad6);
$zz->deleteName('spuren/0001.json');
$zz->close();
$ziel9 = $konto('probe-adminfehlt@example.invalid');
[$ok9, $grund9, ] = edbak_paket_einspielen($kennung, $datei6, $ziel9);
$sag('Ein fehlendes Teil wird benannt und nichts geschrieben',
     $ok9 === false && str_contains((string)$grund9, 'Teilen')
       && (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE user_id = $ziel9")
                   ->fetchColumn() === 0,
     substr((string)$grund9, 0, 60));
copy($sicher6, $pfad6);
@unlink($sicher6);

/* ---- Was das Siegel der Fassung 3 leisten soll (S10/AP4) ---------------
 *
 * Zwei Faelle, und beide gab es vorher nicht. Der erste ist der Preis von
 * E-S10-U-11 und muss deshalb belegt sein; der zweite ist ihr Zweck (F-7). */
$umbenannt = '2026-07-08T00-00-00Z_deadbeef.zip';
copy($pfad6, edbak_ordner($kennung) . '/' . $umbenannt);
$ziel10 = $konto('probe-adminname@example.invalid');
[$okU, $grundU, ] = edbak_paket_einspielen($kennung, $umbenannt, $ziel10);
$sag('Ein UMBENANNTES Paket wird abgewiesen (der Name gehoert zum Siegel)',
     $okU === false && str_contains((string)$grundU, 'umbenannt')
       && (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE user_id = $ziel10")
                   ->fetchColumn() === 0,
     substr((string)$grundU, 0, 60));
@unlink(edbak_ordner($kennung) . '/' . $umbenannt);

/* Ein Teil aus einem ANDEREN Paket desselben Kontos — genau die Luecke, die
 * F-7 benennt: Das Manifest fuehrt nur Namen, keine Pruefsummen je Teil. */
[$okZ, , $infoZ] = edbak_sicherung_erzeugen($quelle);
$fremdTeil = null;
if ($okZ) {
    $zf = new ZipArchive();
    if ($zf->open(edbak_ordner($kennung) . '/' . $infoZ['datei']) === true) {
        $fremdTeil = $zf->getFromName('kopf.json');
        $zf->close();
    }
    @unlink(edbak_ordner($kennung) . '/' . $infoZ['datei']);
}
$sicher7 = sys_get_temp_dir() . '/whprobe-' . bin2hex(random_bytes(4)) . '.zip';
copy($pfad6, $sicher7);
if (is_string($fremdTeil)) {
    $zd = new ZipArchive(); $zd->open($pfad6); $zd->deleteName('kopf.json'); $zd->close();
    $ze = new ZipArchive(); $ze->open($pfad6);
    $ze->addFromString('kopf.json', $fremdTeil);
    $ze->setCompressionName('kopf.json', ZipArchive::CM_STORE);
    $ze->close();
}
$ziel11 = $konto('probe-adminmisch@example.invalid');
[$okM, $grundM, ] = edbak_paket_einspielen($kennung, $datei6, $ziel11);
$sag('Ein Teil aus einem ANDEREN Paket desselben Kontos wird abgewiesen',
     is_string($fremdTeil) && $okM === false
       && str_contains((string)$grundM, 'gehört nicht zu diesem Paket')
       && (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE user_id = $ziel11")
                   ->fetchColumn() === 0,
     substr((string)$grundM, 0, 62));
copy($sicher7, $pfad6);
@unlink($sicher7);

/* Baureste blockieren die Ordnerloeschung nicht mehr. */
@mkdir(edbak_ordner($kennung) . '/' . EDBAK_BAU_PRAEFIX . 'deadbeef');
file_put_contents(edbak_ordner($kennung) . '/' . EDBAK_BAU_PRAEFIX . 'deadbeef/0001.part', 'x');
file_put_contents(edbak_ordner($kennung) . '/rest.tmp', 'x');
$geloescht = edbak_ordner_loeschen($kennung);
$sag('Ein liegengebliebener Bauordner blockiert die Loeschung nicht mehr',
     $geloescht === true && !is_dir(edbak_ordner($kennung)),
     $geloescht ? 'Ordner weg' : ' NICHT geloescht');

/* ALLE FUENF WEGWERFKONTEN, nicht drei. `$ziel10` und `$ziel11` sind bei
 * den beiden Negativfaellen aus S10/AP4 entstanden (umbenanntes Paket,
 * untergeschobener Teil) und hier nie geloescht worden — sie standen nach
 * jedem Lauf in `users` und wuchsen nicht weiter auf, weil `$konto()` sie
 * beim naechsten Mal ueberschreibt. Aufgefallen in AP5 beim Nachzaehlen der
 * `@example.invalid`-Konten: zwei uebrig, wo keines uebrig sein sollte. Der
 * Kopf dieser Datei sagt „loescht sie am Ende wieder" — seit jetzt stimmt es. */
$weg($ziel9); $weg($ziel6); $weg($ziel10); $weg($ziel11); $weg($quelle);

/* ==========================================================================
 * Teil 9 — Speichergrenze und Schwellen (S2/AP6, E-S2-14/15)
 *
 * ZWEI ZUSAGEN STEHEN HIER AUF DEM PRUEFSTAND:
 *
 *   „Bei Erreichen der Speichergrenze wird ABGELEHNT MIT MELDUNG, nie still
 *    verdraengt" (E-S2-14) — die Ablehnung muss VOR dem Bau kommen, sonst
 *    kostet sie beim grossen Konto vierzehn Sekunden fuer nichts.
 *
 *   „Je ueberschrittener Schwelle EINMAL melden" (E-S2-15) — und ohne
 *    eingerichtetes SMTP stattdessen ein dauerhafter Hinweis.
 *
 * Die Marken liegen in app_state und gelten fuer die ganze Installation; sie
 * werden am Ende dieses Teils wieder auf ihren alten Wert gesetzt.
 * ====================================================================== */
echo "\n  Teil 9 — Speichergrenze und Schwellen (S2/AP6)\n";

$markeVorher = static function (PDO $pdo, string $k): ?string {
    $st = $pdo->prepare('SELECT v FROM app_state WHERE k = ?');
    $st->execute([$k]);
    $v = $st->fetchColumn();
    return $v === false ? null : (string)$v;
};
$markeZurueck = static function (PDO $pdo, string $k, ?string $v): void {
    if ($v === null) { $pdo->prepare('DELETE FROM app_state WHERE k = ?')->execute([$k]); }
    else {
        $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
    }
};
$sicher = [];
foreach (['adminbackup_grenze_gb', 'adminbackup_schwellen',
          'adminbackup_schwellen_gemeldet', 'adminbackup_schwellen_offen'] as $k) {
    $sicher[$k] = $markeVorher($pdo, $k);
}

/* Die Vorgaben stehen, solange nichts gesetzt ist. */
$pdo->exec("DELETE FROM app_state WHERE k IN ('adminbackup_grenze_gb',
            'adminbackup_schwellen','adminbackup_schwellen_gemeldet',
            'adminbackup_schwellen_offen')");
$c = &edbak_marken_speicher(); $c = [];      // Zwischenspeicher der Marken leeren
$sag('Ohne Einstellung gilt die Vorgabe: 2 GB',
     edbak_grenze_bytes() === 2 * 1024 * 1024 * 1024,
     groesse_text(edbak_grenze_bytes()));
$sag('...und die Schwellen 70 und 90 Prozent',
     edbak_schwellen() === [70, 90], implode(' / ', edbak_schwellen()) . ' %');

/* Die Zaehlung misst das GANZE Verzeichnis, nicht nur die Pakete. */
$standA = edbak_speicherstand(true);
$restOrdner = edbak_wurzel() . '/' . EDBAK_BAU_PRAEFIX . 'pruefrest';
@mkdir(edbak_wurzel(), 0770, true);
@mkdir($restOrdner);
file_put_contents($restOrdner . '/gross.part', str_repeat('x', 300000));
$standB = edbak_speicherstand(true);
$sag('Ein liegengebliebener Bauordner zaehlt gegen die Grenze mit',
     $standB['bytes'] - $standA['bytes'] >= 300000,
     '+' . groesse_text($standB['bytes'] - $standA['bytes']) . ' erkannt');
$sag('...und er wird als „sonstiges" ausgewiesen, nicht in den Paketen versteckt',
     $standB['sonstige_bytes'] >= 300000,
     groesse_text($standB['sonstige_bytes']) . ' ausserhalb der Pakete');
edbak_ordner_leeren($restOrdner); @rmdir($restOrdner);

/* Grenze auf einen Wert, den der vorhandene Bestand schon ueberschreitet. */
$q9 = $konto('probe-grenze@example.invalid');
$k9 = bin2hex(random_bytes(8));
$pdo->prepare('UPDATE users SET account_key = ? WHERE id = ?')->execute([$k9, $q9]);
edbak_marke_setzen('adminbackup_grenze_gb', '0.000001');   // ~1 KB
$c = &edbak_marken_speicher(); $c['adminbackup_grenze_gb'] = '0.000001';
[$okG, $grundG, ] = edbak_sicherung_erzeugen($q9);
$sag('Bei erreichter Grenze wird ABGELEHNT, nicht verdraengt',
     $okG === false && str_contains((string)$grundG, 'Speichergrenze'),
     substr((string)$grundG, 0, 58));
$sag('...und die Meldung sagt ausdruecklich, dass nichts geloescht wurde',
     str_contains((string)$grundG, 'NICHTS'), 'E-S2-14: nie still verdraengt');
$sag('...und es liegt kein halbes Paket herum',
     !is_dir(edbak_ordner($k9)) || count(edbak_pakete($k9)) === 0,
     'kein Paket im Kontoordner');

/* Schwellen: ohne eingerichtetes SMTP ein Hinweis, keine Mail.
 *
 * ES MUSS ETWAS DALIEGEN, sonst prueft dieser Fall nichts. Die Schwellen
 * greifen ab 70 % der Grenze; nach den Zeilen darueber ist die Ablage LEER —
 * der Bauordner ist weg, und die Sicherung wurde abgelehnt, bevor sie etwas
 * schrieb. `edbak_schwellen_melden()` sah damit 0 % und meldete nichts, und
 * die Erwartung stand auf [70, 90].
 *
 * DASS DAS NIEMANDEM AUFFIEL, hat einen eigenen Grund: Die Probe ist eine
 * Zeile darueber an einem fehlenden `require` gestorben (`smtp.php`, s. o.),
 * und zwar nach dreiundvierzig gruenen Zeilen. Ein Abbruch mitten im Lauf
 * sieht aus wie ein Ende. Gefunden beim Anhaengen von Teil 11 (R64/AP2). */
edbak_marke_setzen('adminbackup_grenze_gb', '0.000001');   // ~1 KB
$schwellRest = edbak_wurzel() . '/' . EDBAK_BAU_PRAEFIX . 'schwelle';
@mkdir(edbak_wurzel(), 0770, true);
@mkdir($schwellRest);
file_put_contents($schwellRest . '/belegt.part', str_repeat('x', 2000));
$m9 = edbak_schwellen_melden();
/* DIESER FALL HAENGT AN DER `config.php` DER PRUEFINSTALLATION, und das
 * gehoert gesagt statt umgangen. Ist dort kein SMTP-Host eingetragen, laesst
 * sich die Zusage messen: Es wird NICHT gemailt, sondern vermerkt. Ist einer
 * eingetragen, geht `edbak_schwellen_melden()` den Mailweg — dann ist
 * messbar, dass die Schwellen ueberhaupt ANSCHLAGEN, aber nicht, was ohne
 * SMTP geschieht.
 *
 * Bis Web 14.2.0 stand hier die Erwartung fuer den ersten Fall unbedingt da.
 * Sie war auf einer Installation mit SMTP nicht erfuellbar — und ist nie
 * aufgefallen, weil die Probe eine Zeile darueber abbrach. */
if (!smtp_eingerichtet()) {
    $sag('Ohne eingerichtetes SMTP wird nicht gemailt, sondern vermerkt',
         $m9['gemeldet'] === [] && $m9['hinweis'] === [70, 90],
         'Hinweis fuer ' . implode('/', $m9['hinweis']) . ' %, Mails: '
         . count($m9['gemeldet']));
} else {
    $sag('Die Schwellen schlagen an (SMTP ist hier eingerichtet — '
         . 'der Hinweisweg ist damit NICHT messbar)',
         $m9['hinweis'] === [] && (count($m9['gemeldet']) + count($m9['fehler'])) === 2,
         'gemeldet ' . count($m9['gemeldet']) . ', fehlgeschlagen '
         . count($m9['fehler']) . ' von 2 Schwellen');
}
edbak_ordner_leeren($schwellRest); @rmdir($schwellRest);
$sag('smtp_eingerichtet() unterscheidet „nicht eingerichtet" von „fehlgeschlagen"',
     function_exists('smtp_eingerichtet'),
     'ohne sie kann E-S2-15 seine zwei Wege nicht auseinanderhalten');

foreach ($sicher as $k => $v) { $markeZurueck($pdo, $k, $v); }
$c = &edbak_marken_speicher(); $c = [];
edbak_ablage_zahlen(true);
$weg($q9);

/* ==========================================================================
 * Teil 10 — Der Auftrag „Alle sichern" (S2/AP6, E-S2-14)
 *
 * DREI ZUSAGEN:
 *
 *   Jedes Konto genau einmal. Die frühere Fassung hatte keinen Merkzettel,
 *   sondern sortierte nach dem Alter des letzten Backups — gerechnet in
 *   TAGEN. Wer heute alle Konten sichert, hat danach lauter Nullen; bei
 *   Gleichstand ist die Reihenfolge beliebig, und die letzten Konten kommen
 *   nie dran.
 *
 *   Ein Abbruch verliert höchstens das laufende Konto. Der Zeiger wird nach
 *   JEDEM Konto fortgeschrieben.
 *
 *   Die Marke passt in die Spalte. `app_state.v` ist varchar(190) — die erste
 *   Fassung legte die Kennungen aller offenen Konten hinein, das INSERT
 *   scheiterte, und weil `edbak_marke_setzen()` jeden Fehler schluckte,
 *   meldete die Schaltfläche „0 von 0 Konten gesichert".
 * ====================================================================== */
echo "\n  Teil 10 — Der Auftrag \"Alle sichern\" (S2/AP6)\n";

$sicherAuftrag = $markeVorher($pdo, 'adminbackup_auftrag');
$pdo->prepare('DELETE FROM app_state WHERE k = ?')->execute(['adminbackup_auftrag']);
$c = &edbak_marken_speicher(); $c = [];

/* ZWEI KONTEN MEHR, NUR FUER DIESEN TEIL (RP-03, F-RP-10). Die Pruefung
 * braucht mehr Konten mit Kontokennung, als der knappe Schub schafft, und
 * danach noch zwei offene fuer die Wiederaufnahme. Eine frische Anlage hat
 * genau ZWEI (Verwaltung und Demo): Der Schub sicherte beide, und „hoert
 * dann auf" konnte nicht stimmen — rot seit PK-03 (F-PK-18), ohne dass die
 * Anwendung falsch war. Sie stehen HINTER dem Bestand (hoehere id) und gehen
 * nach dem Teil wieder. */
$zusatz10 = [];
foreach (['probe-auftrag-1@example.invalid', 'probe-auftrag-2@example.invalid'] as $m) {
    $u = $konto($m);
    $pdo->prepare('UPDATE users SET account_key = ? WHERE id = ?')
        ->execute([bin2hex(random_bytes(8)), $u]);
    $zusatz10[] = $u;
}

$a10 = edbak_auftrag_starten();
$kontenMitKennung = (int)$pdo->query("SELECT COUNT(*) FROM users
    WHERE account_key IS NOT NULL AND account_key <> ''")->fetchColumn();
$sag('Der Auftrag umfasst alle Konten mit Kontokennung',
     (int)$a10['ges'] === $kontenMitKennung,
     $a10['ges'] . ' von ' . $kontenMitKennung);

$roh10 = (string)$pdo->query("SELECT v FROM app_state WHERE k='adminbackup_auftrag'")
                     ->fetchColumn();
$sag('Die Marke steht wirklich in der Datenbank und passt in die Spalte',
     $roh10 !== '' && strlen($roh10) <= EDBAK_MARKE_MAX,
     strlen($roh10) . ' von hoechstens ' . EDBAK_MARKE_MAX . ' Zeichen');

/* EINE UHR, DIE TICKT. Die erste Fassung dieser Pruefung gab eine KONSTANTE
 * zurueck (`fn() => 0.5`); damit war `$zeitLinks() < $reserve` nie wahr, der
 * Schub lief durch alle 31 Konten, und die Pruefung „hoert dann auf" bestand,
 * ohne irgendetwas zu pruefen. Jetzt zaehlt sie herunter: genug fuer ein
 * Konto, danach nicht mehr. */
$vorher10 = edbak_auftrag_offen($a10);
$tick = 2;
$uhr = static function () use (&$tick): float { return $tick-- > 0 ? 9.9 : 0.0; };
$e10 = edbak_auftrag_schub($uhr, 0.2);
$sag('Ein knapper Schub sichert wenigstens ein Konto und hoert dann auf',
     $e10['erledigt'] >= 1 && $e10['erledigt'] < $vorher10 && $e10['offen'] > 0,
     $e10['erledigt'] . ' erledigt, ' . $e10['offen'] . ' von ' . $vorher10 . ' offen');

/* WIEDERAUFNAHME: Der naechste Schub faengt NICHT von vorn an.
 *
 * DER ZWEITE SCHUB NIMMT GENAU EIN KONTO (S10/AP4, 14.09.2026). Hier stand
 * `$tick = 2`, und das war an die Installation von damals gebunden: Der
 * Kommentar oben spricht von „alle 31 Konten" — mit dem Pruefkonten-Bestand
 * blieb nach zwei mal zwei Konten reichlich offen. Der Referenzbestand hat
 * VIER Konten; zwei mal zwei raeumt die Warteschlange leer,
 * `edbak_auftrag_lesen()` gibt dann `null`, und drei Erwartungen meldeten
 * `cur 2 -> —`. Das sah wie ein Fehler der Anwendung aus und war die
 * Arithmetik der Probe.
 *
 * Mit einem Konto je Schub traegt die Pruefung ab ZWEI offenen Konten. Sind
 * es weniger, laesst sich Wiederaufnahme nicht messen — und NICHT GEMESSEN IST
 * ROT (E-KH-12, E-PK-46). Bis BR-03 stand im Zweig darunter `true`: Die Probe
 * meldete eine Erwartung als erfuellt, die sie nicht geprueft hatte (F-BR-04).
 * Seit RP-03 greift der Zweig auf frischer Anlage nicht mehr; greift er doch,
 * sagt die Probe es mit Zahl und rot. */
$a11 = edbak_auftrag_lesen();
$sag('Der Zeiger steht auf dem zuletzt gesicherten Konto',
     is_array($a11) && (int)$a11['cur'] > 0, 'cur=' . ($a11['cur'] ?? '—'));
$offen11 = is_array($a11) ? edbak_auftrag_offen($a11) : 0;
if ($offen11 < 2) {
    $sag('WIEDERAUFNAHME: NICHT GEMESSEN — zu wenige Konten offen', false,
         $offen11 . ' offen, gebraucht werden 2 — Ueberspringen ist rot (E-PK-46)');
} else {
    $tick = 1;
    $e11 = edbak_auftrag_schub($uhr, 0.2);
    $a12 = edbak_auftrag_lesen();
    $sag('WIEDERAUFNAHME: der zweite Schub nimmt ein ANDERES Konto',
         is_array($a12) && (int)$a12['cur'] > (int)$a11['cur'],
         'cur ' . $a11['cur'] . ' -> ' . ($a12['cur'] ?? '—'));
    $sag('...und die Zahl der gesicherten Konten waechst mit',
         is_array($a12) && (int)$a12['gut'] + (int)$a12['feh']
           === (int)$a11['gut'] + (int)$a11['feh'] + $e11['erledigt'],
         'gut+feh ' . ((int)$a11['gut'] + (int)$a11['feh']) . ' -> '
         . ((int)$a12['gut'] + (int)$a12['feh']));
}

/* Eine zu lange Marke wird abgewiesen und benannt, nicht still gekuerzt. */
$sag('Eine zu lange Marke wird abgewiesen, nicht still gekuerzt',
     edbak_marke_setzen('probe_zu_lang', str_repeat('x', EDBAK_MARKE_MAX + 1)) === false
       && (int)$pdo->query("SELECT COUNT(*) FROM app_state WHERE k='probe_zu_lang'")
                   ->fetchColumn() === 0,
     'nichts geschrieben, Rueckgabe false');

/* Der Job kennt den Auftrag und meldet den Rueckstand. */
require_once $server . '/jobs_lib.php';
$sag('Der Job „adminbackup" steht im Katalog',
     array_key_exists('adminbackup', jobs_katalog()),
     implode(', ', array_keys(jobs_katalog())));
/* DEN STAND HIER FRISCH LESEN, nicht aus `$a12` weiterreichen: Ob ein
 * zweiter Schub gelaufen ist, haengt am Bestand (siehe oben), und eine
 * Erwartung, die an einer Variablen aus einem bedingten Zweig haengt,
 * scheitert dann an der Probe statt an der Anwendung. Ohne Auftrag ist der
 * Rueckstand `null`, und das ist die richtige Antwort — nicht 0. */
$aR = edbak_auftrag_lesen();
$sag('Sein Rueckstand ist die Zahl der offenen Konten',
     job_adminbackup_rueckstand($pdo, [])
       === (is_array($aR) ? edbak_auftrag_offen($aR) : null),
     is_array($aR)
       ? (string)job_adminbackup_rueckstand($pdo, [])
       : 'kein Auftrag offen -> null (richtig)');
foreach ($zusatz10 as $u) { $weg($u); }
$sag('Der Rahmen misst jetzt auch den Speicher, nicht nur die Zeit',
     function_exists('jobs_speicher_knapp') && !jobs_speicher_knapp(),
     'Deckel ' . JOB_SPEICHER_DECKEL_MB . ' MB, belegt '
     . round(memory_get_usage(true) / 1048576, 1) . ' MB');

edbak_auftrag_schreiben(null);
$markeZurueck($pdo, 'adminbackup_auftrag', $sicherAuftrag);
$c = &edbak_marken_speicher(); $c = [];


/* ==========================================================================
 * TEIL 11 — NUTZLAST 9: MOMENTAUFNAHME UND SPERRVERMERKE (R64, Backlog Nr. 63)
 *
 * WOFUER. Bis Nutzlast 8 ueberlebte der Sperrvermerk eines Schnitts die
 * Konto-Sicherung nicht. Nach einem Wiedereinspielen lieferte ein Geraet mit
 * gepufferten Punkten den geschnittenen Zeitraum nach, und die Fahrt lag in
 * Einsatz UND Segment — ohne ein Wort. Nutzlast 9 nimmt die Vermerke mit,
 * und zwar ueber VERWEISE (`quelle_ref` = die `client_ref` der Quelle), weil
 * die Datenbankkennungen beim Einspielen neu vergeben werden.
 *
 * Der Regelfall — ein Vermerk faehrt hin und kommt zurueck — steht im
 * edbak-Kreislauf des Referenzbestands. HIER stehen die Grenzfaelle, die der
 * Referenzbestand nicht zeigt, und die Momentaufnahme.
 * ====================================================================== */
echo "\n  Teil 11 — Nutzlast 9: Momentaufnahme und Sperrvermerke (R64 / Nr. 63)\n";
require_once $server . '/spur_lib.php';
$uid11 = $konto('probe-schnitte@example.invalid');

/** Die Kennung eines Datensatzes im Zielkonto. */
$holeId = function (string $tab, string $ref, int $u) use ($pdo): int {
    $st = $pdo->prepare("SELECT id FROM `$tab` WHERE user_id = ? AND client_ref = ?");
    $st->execute([$u, $ref]);
    return (int)$st->fetchColumn();
};

/** Die Vermerke eines Kontos, ueber spur_lib.php gelesen. */
$vermerke = function (int $u) use ($pdo): array {
    $st = $pdo->prepare('SELECT id FROM missions WHERE user_id = ? ORDER BY id');
    $st->execute([$u]);
    $aus = [];
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $mid) {
        foreach (schnitte_zum_einsatz($pdo, (int)$mid) as $c) { $aus[] = $c; }
    }
    return $aus;
};

/** Ein vollstaendiges Nutzlast-9-Paket: ein Tag, zwei Einsaetze, ein Segment. */
$paket9 = function (array $schnitte, array $zusatz = []) : array {
    return array_merge([
      'version' => 9,
      'days' => [[
         'id' => 910, 'day' => '2026-08-03',
         'started_at' => '2026-08-03 05:00:00', 'ended_at' => '2026-08-03 17:00:00',
         'kind' => 'air', 'vehicle_name' => 'Probe 11', 'base_name' => 'Probenstation',
      ]],
      'missions' => [
        /* Der geschnittene Einsatz: traegt die Vermerke UND die
         * Momentaufnahme, die er nach E-R64-06 von seiner Quelle geerbt hat. */
        ['client_ref' => 'cut-s1', 'day_id' => 910,
         'started_at' => '2026-08-03 09:00:00', 'ended_at' => '2026-08-03 09:30:00',
         'origin' => 'schnitt', 'manual' => 1,
         'geraet_art' => 'uhr', 'geraet_modell' => 'fēnix 7 / fēnix 7 Solar',
         'schnitte' => $schnitte],
        /* Der Gegenbeleg: kein Geraet, keine Vermerke. */
        ['client_ref' => 'man-s2', 'day_id' => 910,
         'started_at' => '2026-08-03 12:00:00', 'ended_at' => '2026-08-03 12:30:00',
         'origin' => 'manual', 'schnitte' => []],
      ],
      'rest_segments' => [
        ['client_ref' => 'r-s1', 'day_id' => 910,
         'started_at' => '2026-08-03 06:00:00', 'ended_at' => '2026-08-03 11:00:00',
         'geraet_art' => 'uhr', 'geraet_modell' => 'fēnix 7 / fēnix 7 Solar'],
        ['client_ref' => 'r-s2', 'day_id' => 910,
         'started_at' => '2026-08-03 13:00:00', 'ended_at' => '2026-08-03 14:00:00'],
      ],
    ], $zusatz);
};

$guterVermerk = ['quelle_art' => 'rest', 'quelle_ref' => 'r-s1',
                 'von_ts' => 1786000000, 'bis_ts' => 1786001800,
                 'n_punkte' => 143, 'erstellt_am' => '2026-08-03 09:31:07'];

$s11 = edbak_restore($uid11, $paket9([$guterVermerk]));

$sag('Eine Nutzlast-9-Datei wird angenommen',
     ($s11['missions'] ?? 0) === 2 && ($s11['rests'] ?? 0) === 2,
     ($s11['missions'] ?? 0) . ' Einsaetze, ' . ($s11['rests'] ?? 0) . ' Segmente');

/* ---- Die Momentaufnahme ------------------------------------------------- */
$holeGeraet = function (string $tab, string $ref) use ($pdo, &$uid11): array {
    $st = $pdo->prepare("SELECT geraet_art, geraet_modell FROM `$tab`
                          WHERE user_id = ? AND client_ref = ?");
    $st->execute([$uid11, $ref]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
};
$g1 = $holeGeraet('missions', 'cut-s1');
$sag('Art und Modell eines Einsatzes kommen zurueck',
     ($g1['geraet_art'] ?? null) === 'uhr'
       && ($g1['geraet_modell'] ?? null) === 'fēnix 7 / fēnix 7 Solar',
     json_encode($g1, JSON_UNESCAPED_UNICODE));
$g2 = $holeGeraet('missions', 'man-s2');
/* `??` WAERE HIER FALSCH und war es im ersten Anlauf: Der
 * Null-Verschmelzungsoperator kann „steht auf NULL" nicht von „gibt es
 * nicht" unterscheiden — genau die Unterscheidung, um die es geht. */
$sag('GEGENPROBE: ein Einsatz ohne Angabe bleibt NULL',
     array_key_exists('geraet_art', $g2) && $g2['geraet_art'] === null
       && $g2['geraet_modell'] === null,
     json_encode($g2));
$g3 = $holeGeraet('rest_segments', 'r-s1');
$sag('Dasselbe am Ruhesegment',
     ($g3['geraet_art'] ?? null) === 'uhr'
       && ($g3['geraet_modell'] ?? null) === 'fēnix 7 / fēnix 7 Solar',
     json_encode($g3, JSON_UNESCAPED_UNICODE));

/* ---- Nr. 195: die Geraeteart auf dem RUECKWEG (P5a/AP10) ------------------
 *
 * WAS FALSCH WAR. Beim Koppeln verengt `geraete_lib.php` die Geraeteart auf
 * die drei Werte aus `GERAET_ARTEN`; `docs/Technik.md` fuehrt das als Zusage.
 * Auf dem Rueckweg der Sicherung galt sie nicht — geprueft wurde allein die
 * LAENGE (16 Zeichen), also ging jede Zeichenkette durch. Beim Nachbarfeld
 * `origin` war es anders; die Asymmetrie stand ohne Begruendung da.
 *
 * WARUM DAS ZAEHLT: Genau diese Spalte soll der offene R42-Rest auswerten
 * (R64). Eine Zaehlung, die man durch das Bearbeiten der EIGENEN Sicherung
 * verunreinigen kann, taugt nicht als Betriebszahl.
 *
 * ZWEI ERWARTUNGEN, nicht eine: Der Wert muss NULL werden, UND es muss
 * dastehen. Ein stilles NULL saehe aus wie „stand nicht drin". */
$uid195 = $konto('probe-195@example.invalid');
$s195 = edbak_restore($uid195, $paket9([], ['missions' => [
    ['client_ref' => 'art-195', 'day_id' => 910,
     'started_at' => '2026-08-03 09:00:00', 'ended_at' => '2026-08-03 09:30:00',
     'origin' => 'manual', 'manual' => 1,
     'geraet_art' => 'radcomputer', 'geraet_modell' => 'Irgendein Radcomputer'],
    ['client_ref' => 'art-ok', 'day_id' => 910,
     'started_at' => '2026-08-03 11:00:00', 'ended_at' => '2026-08-03 11:30:00',
     'origin' => 'manual', 'manual' => 1,
     'geraet_art' => 'HANDY', 'geraet_modell' => 'Google Pixel 8'],
], 'rest_segments' => [
    ['client_ref' => 'r-195', 'day_id' => 910,
     'started_at' => '2026-08-03 06:00:00', 'ended_at' => '2026-08-03 07:00:00',
     'geraet_art' => 'fahrrad'],
]]));
$hole195 = function (string $tab, string $ref) use ($pdo, $uid195): array {
    $st = $pdo->prepare("SELECT geraet_art, geraet_modell FROM `$tab`
                          WHERE user_id = ? AND client_ref = ?");
    $st->execute([$uid195, $ref]);
    return $st->fetch(PDO::FETCH_ASSOC) ?: [];
};
$a195 = $hole195('missions', 'art-195');
$sag('Nr. 195: eine unbekannte Geraeteart landet als NULL, nicht als Text',
     array_key_exists('geraet_art', $a195) && $a195['geraet_art'] === null,
     json_encode($a195, JSON_UNESCAPED_UNICODE));
$sag('...das MODELL daneben bleibt stehen — es ist Freitext und keine Liste',
     ($a195['geraet_modell'] ?? null) === 'Irgendein Radcomputer',
     json_encode($a195, JSON_UNESCAPED_UNICODE));
$aOk = $hole195('missions', 'art-ok');
$sag('GEGENPROBE: ein bekannter Wert kommt durch — auch in Grossbuchstaben',
     ($aOk['geraet_art'] ?? null) === 'handy', json_encode($aOk));
$r195 = $hole195('rest_segments', 'r-195');
$sag('Dasselbe am Ruhesegment', array_key_exists('geraet_art', $r195)
     && $r195['geraet_art'] === null, json_encode($r195));
$gruende195 = implode(' | ', array_keys((array)($s195['rejected'] ?? [])));
$sag('...und es steht im Pruefprotokoll, statt still zu geschehen',
     str_contains($gruende195, 'geraet_art: keine bekannte Geräteart')
     && str_contains($gruende195, 'rest.geraet_art: keine bekannte Geräteart'),
     $gruende195 !== '' ? $gruende195 : '(leer)');

/* ---- Der Vermerk selbst -------------------------------------------------- */
$v = $vermerke($uid11);
$sag('Der Sperrvermerk ist angekommen — genau einer',
     count($v) === 1 && ($s11['schnitte']['uebernommen'] ?? 0) === 1,
     count($v) . ' Vermerke, uebernommen ' . ($s11['schnitte']['uebernommen'] ?? 0));
$sag('Zeiten und Punktzahl unveraendert',
     count($v) === 1 && $v[0]['von_ts'] === 1786000000
       && $v[0]['bis_ts'] === 1786001800 && $v[0]['n_punkte'] === 143,
     count($v) === 1 ? json_encode($v[0]) : '—');
$sag('Die Quelle zeigt auf das eingespielte Ruhesegment, nicht auf eine alte Kennung',
     count($v) === 1 && $v[0]['owner_type'] === 'rest'
       && $v[0]['owner_id'] === $holeId('rest_segments', 'r-s1', $uid11),
     count($v) === 1 ? ($v[0]['owner_type'] . ' ' . $v[0]['owner_id']) : '—');
$stErst = $pdo->prepare('SELECT erstellt_am FROM track_cuts WHERE user_id = ?');
$stErst->execute([$uid11]);
$sag('Der urspruengliche Zeitpunkt reist mit (E-R64-12)',
     (string)$stErst->fetchColumn() === '2026-08-03 09:31:07',
     'erstellt_am aus der Datei, nicht vom Einspielen');

/* ---- (1) Zweites Einspielen derselben Datei ------------------------------ */
$s11b = edbak_restore($uid11, $paket9([$guterVermerk]));
$sag('Zweites Einspielen: uebersprungen statt verdoppelt',
     ($s11b['schnitte']['uebersprungen'] ?? 0) === 1
       && ($s11b['schnitte']['uebernommen'] ?? 0) === 0,
     'uebersprungen ' . ($s11b['schnitte']['uebersprungen'] ?? 0));
$sag('...und es steht weiterhin GENAU EIN Vermerk da',
     count($vermerke($uid11)) === 1, count($vermerke($uid11)) . ' Vermerke');

/* ---- (2)-(5) Die Grenzfaelle, jeder in einem frischen Konto -------------- */
$grenzfall = function (string $mail, array $vermerk) use ($konto, $paket9, $vermerke, $weg) {
    $u = $konto($mail);
    $st = edbak_restore($u, $paket9([$vermerk]));
    $n = count($vermerke($u));
    $weg($u);
    return [$st, $n];
};

[$sA, $nA] = $grenzfall('probe-schnitt-a@example.invalid',
    array_merge($guterVermerk, ['quelle_ref' => 'r-gibt-es-nicht']));
$sag('(2) Verwaiste Quelle: verworfen, Lauf laeuft durch',
     ($sA['schnitte']['verworfen'] ?? 0) === 1 && $nA === 0 && ($sA['missions'] ?? 0) === 2,
     'verworfen ' . ($sA['schnitte']['verworfen'] ?? 0) . ', ' . $nA . ' Vermerke');
$sag('(2) ...und die Kennung wird GENANNT, nicht nur gezaehlt',
     str_contains(json_encode($sA['rejected'] ?? []), 'r-gibt-es-nicht'),
     json_encode($sA['rejected'] ?? [], JSON_UNESCAPED_UNICODE));
$sag('(2) ...mit eigenem Grund in der Aufschluesselung',
     (int)($sA['skipped_reasons']['schnitt_ohne_quelle'] ?? 0) === 1,
     json_encode($sA['skipped_reasons'] ?? []));

[$sB, $nB] = $grenzfall('probe-schnitt-b@example.invalid',
    array_merge($guterVermerk, ['von_ts' => 1786001800, 'bis_ts' => 1786000000]));
$sag('(3) bis_ts vor von_ts: verworfen',
     ($sB['schnitte']['verworfen'] ?? 0) === 1 && $nB === 0
       && (int)($sB['skipped_reasons']['schnitt_werte'] ?? 0) === 1,
     'verworfen ' . ($sB['schnitte']['verworfen'] ?? 0));

[$sC, $nC] = $grenzfall('probe-schnitt-c@example.invalid',
    array_merge($guterVermerk, ['n_punkte' => 0]));
$sag('(4) n_punkte = 0: verworfen',
     ($sC['schnitte']['verworfen'] ?? 0) === 1 && $nC === 0,
     'verworfen ' . ($sC['schnitte']['verworfen'] ?? 0));

/* Die ENUM-Falle: `track_cuts.owner_type` ist ein ENUM, und die Verbindung
 * setzt keinen `sql_mode`. Ein Wert aus der Datei, der ungeprueft dort
 * hineinginge, wuerde je nach Servereinstellung still zu '' oder wuerfe —
 * das erste ergaebe einen Vermerk, den kein Leseweg je wiederfaende. */
[$sD, $nD] = $grenzfall('probe-schnitt-d@example.invalid',
    array_merge($guterVermerk, ['quelle_art' => 'irgendwas']));
$sag('(4a) Unbekannte quelle_art: verworfen, nichts geschrieben (ENUM-Falle)',
     ($sD['schnitte']['verworfen'] ?? 0) === 1 && $nD === 0 && ($sD['missions'] ?? 0) === 2,
     'verworfen ' . ($sD['schnitte']['verworfen'] ?? 0) . ', ' . $nD . ' Vermerke');

/* ---- (5) Eine Nutzlast-8-Datei ohne `schnitte` --------------------------- */
$uid11c = $konto('probe-schnitt-alt@example.invalid');
$alt = $paket9([]);
$alt['version'] = 8;
foreach ($alt['missions'] as $i => $m) { unset($alt['missions'][$i]['schnitte']); }
foreach ($alt['missions'] as $i => $m) {
    unset($alt['missions'][$i]['geraet_art'], $alt['missions'][$i]['geraet_modell']);
}
foreach ($alt['rest_segments'] as $i => $r) {
    unset($alt['rest_segments'][$i]['geraet_art'], $alt['rest_segments'][$i]['geraet_modell']);
}
$sAlt = edbak_restore($uid11c, $alt);
$sag('(5) Nutzlast-8-Datei: keine Vermerke, keine Zaehler, keine Meldung',
     ($sAlt['schnitte']['uebernommen'] ?? -1) === 0
       && ($sAlt['schnitte']['uebersprungen'] ?? -1) === 0
       && ($sAlt['schnitte']['verworfen'] ?? -1) === 0
       && count($vermerke($uid11c)) === 0,
     json_encode($sAlt['schnitte'] ?? []));
$g8 = $pdo->prepare('SELECT geraet_art FROM missions WHERE user_id = ? AND client_ref = ?');
$g8->execute([$uid11c, 'cut-s1']);
$sag('(5) ...und die Momentaufnahme bleibt NULL statt leer',
     $g8->fetchColumn() === null, 'geraet_art IS NULL');
$weg($uid11c);
$weg($uid11);

/* ==========================================================================
 * TEIL 12 — DER RUECKWEG, WENN DER SERVER-ANTEIL WEG IST (S10/AP5, E-S10-15)
 *
 * DIE LAGE. `config.php` fuehrt seit S10 zwei Geheimnisse: den
 * Serverschluessel (oeffnet Sicherungen) und den Server-Anteil (geht in den
 * Datenschluessel jedes Kontos ein). Geht die Datei verloren und kommt aus
 * einer Sicherung ohne sie zurueck, steht die Marke `kdf_anteil_kennung` noch
 * in `app_state`, der Wert aber nicht mehr in der Datei. `anteil_zustand()`
 * nennt das `abweichend` — und genau das ist der Ernstfall, fuer den der
 * Wiederherstellungsweg da ist.
 *
 * WAS HIER GEMESSEN WIRD, und was ausdruecklich anderswo steht. Die fuenf
 * Zustaende selbst rechnet `tools/proben/anteil/probe.php` nach, die
 * Oberflaeche dazu `betriebslauf.mjs` im Browser. Hier steht die Frage, die
 * nur diese Probe stellen kann: **Bleibt der Rueckweg offen, waehrend der
 * Anteil fehlt?** Ein Backup einzuspielen ist die Handlung, die man in dieser
 * Lage vornimmt; waere sie gesperrt, stuende die Rettung hinter der Tuer, die
 * man ohne den Anteil nicht aufbekommt.
 *
 * WIE DIE LAGE HERGESTELLT WIRD — IM SPEICHER, NICHT AUF DER PLATTE.
 * `kdf_anteil()`, `kdf_anteil_alt()` und `anteil_zustand()` lesen aus `$CFG`
 * und halten ihr Ergebnis in einem `static`, das sich mit `true` verwerfen
 * laesst. Der Eintrag wird deshalb nur aus dem Feld genommen und danach
 * zurueckgelegt: `config.php` wird nicht angefasst, `app_state` nicht
 * beschrieben (der Zweig `abweichend` in `anteil_zustand()` kehrt VOR
 * `schluessel_marke_setzen()` um). Stirbt der Prozess mittendrin, ist auf der
 * Platte nichts geschehen — anders als bei einem Prueflauf, der die Datei
 * schreibt und auf sein `finally` angewiesen ist.
 * ====================================================================== */
echo "\n  Teil 12 — Der Rueckweg, wenn der Server-Anteil weg ist (S10/AP5)\n";
require_once $server . '/serverkrypto_lib.php';
require_once __DIR__ . '/../../sandbox/konfig_stellen.php';

/* BIS WEB 20.26.3 WURDE DER ANTEIL IM SPEICHER VERSTELLT (`$CFG`). Die
 * globale `$CFG` ist mit Schritt 15 AP2 entfallen; die Anwendung liest ueber
 * `konfig()` aus der Datei, und eine Zuweisung erreicht niemanden mehr —
 * sie scheitert nicht, sie tut nur nichts. Teil 12 stand danach still auf
 * „nicht erfuellt" (gemessen 21.09.2026). `konfig_stellen()` geht denselben
 * Weg wie die Anwendung und legt den Urstand bytegleich zurueck, auch bei
 * einem Abbruch. */
$anteilVorher = konfig('kdf_anteil');
/* MARKE ZUERST LESEN, DANN DEN ZUSTAND FRAGEN (Gegenprobe zu AP5).
 *
 * `anteil_zustand()` ist nicht nur eine Auskunft: Fehlt die Marke in
 * `app_state` und steht ein Wert in `config.php`, SCHREIBT sie die Kennung
 * nach (E-S10-U-02). Das ist richtig — der erste Seitenaufruf taete es
 * ohnehin —, aber ein Pruefmittel soll den Zustand nicht herstellen, den es
 * misst. Die Marke wird deshalb vorher gelesen, und ohne sie laeuft Teil 12
 * gar nicht erst an. */
$markeVorher  = schluessel_marke_lesen('kdf_anteil_kennung');
$standVorher  = $markeVorher === null ? ['stand' => 'ohne Marke'] : anteil_zustand(true);

if ($anteilVorher === null || ($standVorher['stand'] ?? '') !== 'bereit') {
    /* KEINE GRUENE ZAHL FUER EINE NICHT GEMESSENE LAGE. Ohne eingerichteten
     * Anteil gibt es nichts wegzunehmen, und ohne Marke waere der Zustand
     * `fehlt` statt `abweichend` — ein anderer Fall. */
    $sag('Teil 12 uebersprungen: kein eingerichteter Anteil', false,
         'Stand „' . (string)($standVorher['stand'] ?? '?') . '" statt „bereit" — '
         . 'erst `Server-Anteil anlegen` auf Betrieb -> Servereinstellungen');
} else {
    $uid12 = $konto('probe-anteilweg@example.invalid');
    $huelleNeu = 'edka1:' . $standVorher['kennung'] . ':' . str_repeat('QUJD', 16);
    $huelleRc  = 'edk1:' . str_repeat('WFla', 16);
    /* `account_key` wie in Teil 8: Ohne Kontokennung legt
     * `edbak_sicherung_erzeugen()` kein Paket an — die Kennung ist der
     * Ordnername der Ablage und geht in den Siegelzweck ein (S10/AP4). */
    $kennung12 = bin2hex(random_bytes(8));
    $pdo->prepare('UPDATE users SET pat_wrap_pw = ?, pat_wrap_rc = ?, pat_key_check = ?,
                          account_key = ? WHERE id = ?')
        ->execute([$huelleNeu, $huelleRc, str_repeat('b', 32), $kennung12, $uid12]);

    try {
        /* ---- Der Anteil verschwindet (in der Datei, und zwar kurz) -------- */
        $zurueck12konfig = konfig_stellen(['kdf_anteil' => null]);
        kdf_anteil(true); kdf_anteil_alt(true);
        $weg12 = anteil_zustand(true);

        $sag('(1) Ohne Wert, aber mit Marke: Stand „abweichend"',
             ($weg12['stand'] ?? '') === 'abweichend',
             'Stand „' . (string)($weg12['stand'] ?? '?') . '", Marke '
             . (string)($weg12['erwartet'] ?? '—'));
        $sag('(2) Es wird kein Anteil mehr ausgeliefert',
             anteil_ausgeliefert() === null && konto_anteile($uid12) === [],
             'anteil_ausgeliefert() = null, KONTO_ANTEILE leer');

        /* Die Hülle des Kontos ist jetzt unbrauchbar — aber NICHT still: Die
         * Prüfschicht sagt, warum, und nennt den Anteil. Ein stiller
         * Fehlschlag sähe aus wie ein falsches Passwort (das ist die ganze
         * Begründung der Zustandsmaschine, E-S10-03). */
        $grund12 = huelle_pw_pruefen($huelleNeu);
        $sag('(3) Die `edka1:`-Huelle wird abgewiesen — MIT Begruendung',
             is_string($grund12) && str_contains($grund12, 'Server-Anteil'),
             is_string($grund12) ? mb_substr($grund12, 0, 52) . '…' : 'null (durchgelassen!)');

        /* DIE ZUSAGE AUS E-S10-04, und sie ist der Kern dieses Teils:
         * `pat_wrap_rc` haengt NICHT am Anteil. Der Verlust des Anteils ist
         * deshalb kein Datenverlust, sondern ein Passwort-Reset fuer alle. */
        $sag('(4) Die Wiederherstellungs-Huelle bleibt offen (E-S10-04)',
             huelle_rc_pruefen($huelleRc) === null,
             'huelle_rc_pruefen() = null — der Rueckweg steht');

        /* ---- Und jetzt die Handlung, die man in dieser Lage vornimmt ------ */
        $st12 = edbak_restore($uid12, [
          'version' => 7,
          'days' => [['id' => 9120, 'day' => '2026-07-20', 'kind' => 'ground',
                      'vehicle_name' => 'Probe A12', 'base_name' => 'Probenstation']],
          'missions' => [
            ['client_ref' => 'anteil-1', 'day_id' => 9120,
             'started_at' => '2026-07-20 06:00:00', 'ended_at' => '2026-07-20 07:00:00',
             'pat_blob' => 'edk1:' . str_repeat('QUJD', 16),
             'track' => [[0, 47.30, 11.30, 500.0, 1784000000],
                         [1, 47.31, 11.31, 505.0, 1784000060]]],
          ],
        ]);
        $g12 = $pdo->prepare('SELECT pat_blob FROM missions WHERE user_id = ? AND client_ref = ?');
        $g12->execute([$uid12, 'anteil-1']);
        $blob12 = $g12->fetchColumn();
        $mid12 = (int)$pdo->query('SELECT id FROM missions WHERE user_id = ' . $uid12
                                  . " AND client_ref = 'anteil-1'")->fetchColumn();
        /* Die Spur wird ueber `spur_lib.php` gezaehlt und nicht per SQL —
         * seit Web 10.0.0 liegt sie je nach Alter in `track_points` ODER als
         * Blob in `track_blobs`, und wer eine der beiden Tabellen unmittelbar
         * liest, zaehlt frueher oder spaeter eine halbe Spur (CLAUDE.md 4). */
        $punkte12 = spur_zahlen($pdo, 'mission', [$mid12])[$mid12] ?? 0;
        $sag('(5) Ein Backup laesst sich TROTZDEM einspielen',
             (int)($st12['missions'] ?? 0) === 1 && $punkte12 === 2
               && $blob12 === 'edk1:' . str_repeat('QUJD', 16),
             (int)($st12['missions'] ?? 0) . ' Einsatz, ' . $punkte12
             . ' Spurpunkte, Chiffretext unveraendert');

        /* Der Rueckweg schreibt Einsaetze, keine Schluessel. Ruehrte er die
         * Huelle an, waere aus „Anteil weg" ein Datenverlust geworden. */
        $h12 = $pdo->prepare('SELECT pat_wrap_pw, pat_wrap_rc, pat_key_check FROM users WHERE id = ?');
        $h12->execute([$uid12]);
        $nach12 = $h12->fetch(PDO::FETCH_ASSOC);
        $sag('(6) ...und ruehrt die drei Schluesselfelder des Kontos nicht an',
             $nach12['pat_wrap_pw'] === $huelleNeu
               && $nach12['pat_wrap_rc'] === $huelleRc
               && $nach12['pat_key_check'] === str_repeat('b', 32),
             'pat_wrap_pw, pat_wrap_rc, pat_key_check unveraendert');

        /* ZWEI GEHEIMNISSE IN EINER DATEI — und nur eines fehlt. Der
         * Serverschluessel versiegelt die Adminpakete (S10/AP4); mit dem
         * Anteil hat er nichts zu tun. Waeren die beiden verquickt, waere der
         * Verlust des Anteils zugleich der Verlust aller Sicherungen. */
        [$ok12, $grundP12, $infoP12] = edbak_sicherung_erzeugen($uid12);
        $kopf12 = $ok12 ? edbak_paket_kopf_lesen($kennung12, (string)$infoP12['datei']) : null;
        $sag('(7) Ein Adminpaket entsteht und oeffnet — der Serverschluessel ist ein ANDERES Geheimnis',
             $ok12 === true && is_array($kopf12) && (int)($kopf12['version'] ?? 0) === 3,
             $ok12 ? 'Fassung ' . (int)($kopf12['version'] ?? 0) : (string)$grundP12);
    } finally {
        /* ZURUECKLEGEN UND NACHZAEHLEN. Das `finally` steht hier, weil jede
         * der Erwartungen darueber eine Ausnahme werfen kann — und weil ein
         * Prozess, der mit fehlendem Anteil im Speicher weiterliefe, den Rest
         * der Probe still falsch messen wuerde. */
        if (isset($zurueck12konfig)) { $zurueck12konfig(); }
        kdf_anteil(true); kdf_anteil_alt(true);
        $zurueck12 = anteil_zustand(true);
        $sag('(8) Nach dem Zuruecklegen steht der Stand wieder auf „bereit"',
             ($zurueck12['stand'] ?? '') === ($standVorher['stand'] ?? '')
               && ($zurueck12['kennung'] ?? null) === ($standVorher['kennung'] ?? null),
             'Stand „' . (string)($zurueck12['stand'] ?? '?') . '", Kennung '
             . (string)($zurueck12['kennung'] ?? '—'));
        /* Auch die Ablage raeumen — wie Teil 8. Ein geloeschtes Konto laesst
         * seinen Paketordner sonst stehen, und `server/sicherungen/` fuellt
         * sich bei jedem Lauf um einen weiteren Ordner mit Siegeln, die
         * niemand mehr oeffnen will. */
        if (isset($kennung12)) { @edbak_ordner_loeschen($kennung12); }
        if (isset($uid12)) { $weg($uid12); }
    }
}

/* ==========================================================================
 * TEIL 13 — EINE ALTE DATEI MIT DER STANDORTAUSWAHL (P5c/AP8, R39, Nr. 168)
 *
 * DIE LAGE. Bis Nutzlast 11 trug jede Sicherung unter `stammdaten` die
 * Auswahl zentraler Standorte (E16), `user_bases`. Die Tabelle dazu ist mit
 * der Migration `2026_09_25_zentrale_stammdaten` gefallen. Stuende die
 * Schleife noch im Rueckweg, die das Feld las, braeche JEDE Wiederherstellung
 * einer alten Datei mit 42S02 ab — auch die Kontosicherung und der
 * Demo-Reset. Das Feld wird deshalb still ueberlesen; diese Probe haelt das
 * fest, mit einem Namen darin, den es nirgends gibt.
 *
 * DAZU DER TAG MIT TAGESRETTUNGSMITTEL (E-P5c-123). Die Migration zieht fuer
 * den BESTAND die Rollen der Betriebsart nach; die Wiederherstellung tut es
 * nicht — sie legt an, was in der Datei steht (E8). Ein Tag ohne `crew` aus
 * einer alten Datei kommt also ohne Rollensatz zurueck, und das ist die
 * Entscheidung, nicht ein Versehen.
 *
 * UND DER RUECKWEG DER ZAHL: Die Sicherung desselben Kontos traegt danach
 * Nutzlast 12 und das Feld nicht mehr.
 * ====================================================================== */
echo "\n  Teil 13 — Eine 11er-Datei mit der Standortauswahl (P5c/AP8, Nr. 168)\n";
$uid13 = $konto('probe-standortauswahl@example.invalid');
try {
    $ausnahme13 = null; $s13 = [];
    try {
        $s13 = edbak_restore($uid13, [
            'version' => 11,
            'stammdaten' => [
                'bases'      => [['name' => 'Probenstation 13', 'lat' => null, 'lon' => null,
                                  'is_default' => 0]],
                'vehicles'   => [['name' => 'Probe 13', 'kind' => 'air', 'typ' => 'standard',
                                  'base_ref' => 'Probenstation 13',
                                  'roles' => ['p1', 'hems'], 'capabilities' => []]],
                'user_bases' => ['Zentrale Station, die es nirgends gibt'],
            ],
            'days' => [[
                'id' => 1300, 'day' => '2026-07-13',
                'started_at' => '2026-07-13 06:00:00', 'ended_at' => '2026-07-13 18:00:00',
                'kind' => 'ground', 'vehicle_name' => 'Aushilfe 13',
                'vehicle_typ' => 'sonstiges', 'base_name' => 'Irgendwo',
            ]],
            'missions' => [], 'rest_segments' => [],
        ]);
    } catch (Throwable $e) { $ausnahme13 = $e; }
    $sag('(1) Eine 11er-Datei MIT der Standortauswahl spielt ein (kein 42S02)',
         $ausnahme13 === null,
         $ausnahme13 === null ? 'ohne Ausnahme'
             : get_class($ausnahme13) . ': ' . mb_substr($ausnahme13->getMessage(), 0, 60));
    $sag('(2) ...Standort und Rettungsmittel stehen da, nichts uebersprungen',
         (int)($s13['stammdaten'] ?? -1) === 2 && (int)($s13['stammdaten_skipped'] ?? -1) === 0,
         'angelegt ' . (int)($s13['stammdaten'] ?? -1) . ', uebersprungen '
         . (int)($s13['stammdaten_skipped'] ?? -1));
    $c13 = $pdo->prepare('SELECT COUNT(*) FROM day_crew c JOIN days d ON d.id = c.day_id
                           WHERE d.user_id = ?');
    $c13->execute([$uid13]);
    $crew13 = (int)$c13->fetchColumn();
    $sag('(3) Der Tag mit Tagesrettungsmittel kommt wie in der Datei zurueck (E8, E-P5c-123)',
         $crew13 === 0, $crew13 . ' Rollenzeilen');
    $j13 = json_decode(edbak_build($uid13, true), true);
    $sag('(4) Die Sicherung dieses Kontos traegt Nutzlast 12 und die Auswahl nicht mehr',
         is_array($j13) && (int)($j13['version'] ?? 0) === 12
           && is_array($j13['stammdaten'] ?? null)
           && !array_key_exists('user_bases', $j13['stammdaten']),
         'Nutzlast ' . (int)($j13['version'] ?? 0) . ', Schluessel '
         . implode(', ', array_keys((array)($j13['stammdaten'] ?? []))));
} finally {
    $weg($uid13);
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $gesamt, $fehler);
exit($fehler === 0 ? 0 : 1);
