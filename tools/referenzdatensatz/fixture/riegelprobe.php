<?php
declare(strict_types=1);

/**
 * Riegelprobe — haelt `erzeugen.php` eine Fixture mit Anteil auf? (S10/AP5)
 *
 * DIE FRAGE. `fixture/erzeugen.php` schreibt die Schluesselhuelle des
 * Demo-Kontos unveraendert in die Fixture, und `demo_lib.php` schreibt sie
 * alle 30 Minuten unveraendert zurueck ins Konto — auf der
 * PRODUKTIVinstallation, die ihren eigenen Server-Anteil fuehrt. Eine Huelle
 * mit Anteil (`edka1:<kennung>:`) ist dort nicht zu oeffnen: Das Demo-Konto
 * kaeme herein und saehe nichts, alle 30 Minuten aufs Neue, ohne Meldung.
 *
 * E-S10-15 verlangt deshalb den Riegel in `erzeugen.php`. Diese Probe misst
 * ihn — beide Richtungen, denn ein Riegel, der IMMER zuschlaegt, ist ebenso
 * kaputt wie einer, der es nie tut:
 *
 *   1. POSITIV — das Demo-Konto traegt `edk1:`, und `erzeugen.php` laeuft
 *      durch (Rueckgabe 0, eine Datei entsteht).
 *   2. NEGATIV — ein Konto mit `edka1:`-Huelle wird abgewiesen
 *      (Rueckgabe 2, Meldung nennt das gefundene Praefix, KEINE Datei).
 *
 * WARUM DER NEGATIVFALL NICHTS UMSTELLT. Der naheliegende Weg waere, die
 * Huelle des Demo-Kontos kurz auf `edka1:` zu setzen und danach
 * zurueckzulegen. Genau dieser Weg hat in AP3 ein Konto gekostet
 * (F-S10-AP3-08): Eine Umhuellung ist nicht rueckrechenbar, und wer den
 * Schnappschuss eine Handlung zu spaet nimmt, sperrt das Konto dauerhaft aus.
 *
 * Deshalb wird hier NICHTS geschrieben. `erzeugen.php` nimmt das Konto als
 * Argument — der Negativfall zeigt es einfach auf ein Konto des
 * Referenzbestands, das seine `edka1:`-Huelle regulaer beim Anmelden bekommen
 * hat. Ein echter Fall statt eines nachgestellten, und die Datenbank bleibt
 * unberuehrt.
 *
 * AUFRUF
 *
 *     php tools/referenzdatensatz/fixture/riegelprobe.php
 *     php tools/referenzdatensatz/fixture/riegelprobe.php umlauf-csv@gen-em.org
 *
 * Das Argument ist das Konto fuer den NEGATIVfall; es muss eine
 * `edka1:`-Huelle tragen, die Rolle `user` haben und auf `KDF_ITER_ZIEL`
 * stehen — sonst schlaegt ein anderer Riegel vorher zu, und die Probe sagt
 * das, statt gruen zu melden.
 *
 * Erwartet: **10 von 10**, Rueckgabe 0.
 *
 * DIE POSITIVE HAELFTE SCHREIBT EINE DATEI — in den Systemtempordner, nicht
 * nach `server/demo/`. Die echte Fixture wird von dieser Probe nie angefasst.
 */

$wurzel  = dirname(__DIR__, 3);
$server  = $wurzel . '/server';
$erzeuge = __DIR__ . '/erzeugen.php';

require_once $server . '/db.php';

$negativKonto = $argv[1] ?? 'umlauf-csv@gen-em.org';

$gesamt = 0; $offen = 0;
function pruef(string $was, bool $ok, string $dazu = ''): void {
    global $gesamt, $offen;
    $gesamt++; if (!$ok) { $offen++; }
    printf("  [%s] %-58s %s\n", $ok ? 'ok ' : 'FEHL', $was, $dazu);
}

/** `erzeugen.php` fahren und Rueckgabe, Ausgabe und Zieldatei einsammeln. */
function lauf(string $erzeuge, string $konto, string $ziel): array
{
    @unlink($ziel);
    $d = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $p = proc_open(['php', $erzeuge, $konto, $ziel], $d, $rohr);
    $aus = stream_get_contents($rohr[1]); fclose($rohr[1]);
    $err = stream_get_contents($rohr[2]); fclose($rohr[2]);
    $rc  = proc_close($p);
    return ['rc' => $rc, 'aus' => (string)$aus, 'err' => (string)$err,
            'datei' => is_file($ziel) ? filesize($ziel) : 0];
}

/* ---- Voraussetzungen, und zwar gemessen ---------------------------------- */
echo "Riegelprobe — der Huellen-Riegel in fixture/erzeugen.php (S10/AP5)\n\n";

$st = db()->prepare('SELECT email, role, kdf_iter, LEFT(pat_wrap_pw, 7) AS praefix
                     FROM users WHERE email = ?');
$st->execute(['demo@gen-em.org']);
$demo = $st->fetch(PDO::FETCH_ASSOC);
$st->execute([$negativKonto]);
$neg = $st->fetch(PDO::FETCH_ASSOC);

if (!$demo || !$neg) {
    fwrite(STDERR, "Ein Konto fehlt: demo@gen-em.org oder $negativKonto.\n");
    exit(2);
}
if (!str_starts_with((string)$neg['praefix'], 'edka1:')) {
    fwrite(STDERR, sprintf(
        "Das Negativkonto %s traegt `%s`, gebraucht wird `edka1:`.\n"
      . "Herzustellen, indem sich das Konto einmal im Browser anmeldet -- oder\n"
      . "mit tools/proben/anteil/huelle_stellen.py <konto> <passwort> edka1.\n",
        $negativKonto, (string)$neg['praefix']));
    exit(2);
}

echo "  Positiv  demo@gen-em.org        Praefix {$demo['praefix']}\n";
echo "  Negativ  $negativKonto  Praefix {$neg['praefix']}\n\n";

/* ---- 1. Positiv: das Demo-Konto laeuft durch ------------------------------ */
$ziel = sys_get_temp_dir() . '/riegelprobe-' . bin2hex(random_bytes(4)) . '.json.gz';
$a = lauf($erzeuge, 'demo@gen-em.org', $ziel);
pruef('1  Demo-Konto mit `edk1:` laeuft durch', $a['rc'] === 0,
      'Rueckgabe ' . $a['rc'] . ($a['rc'] !== 0 ? ' — ' . trim($a['err']) : ''));
pruef('1  ... und legt eine Fixture an', $a['datei'] > 0,
      $a['datei'] > 0 ? round($a['datei'] / 1024) . ' KB' : 'keine Datei');
@unlink($ziel);

/* ---- 2. Negativ: ein Konto mit Anteil wird abgewiesen --------------------- */
$ziel2 = sys_get_temp_dir() . '/riegelprobe-' . bin2hex(random_bytes(4)) . '.json.gz';
$b = lauf($erzeuge, $negativKonto, $ziel2);
pruef('2  Konto mit `edka1:` wird abgewiesen', $b['rc'] === 2,
      'Rueckgabe ' . $b['rc']);
/* Die Meldung muss das GEFUNDENE Praefix nennen. Ein „stimmt nicht" ohne den
 * Wert schickt die naechste Person in die Datenbank, um nachzusehen. */
pruef('2  ... die Meldung nennt `edka1:` und schreibt KEINE Datei',
      str_contains($b['err'], 'edka1:') && $b['datei'] === 0,
      trim(explode("\n", $b['err'])[0] ?? ''));
@unlink($ziel2);

/* ---- 3. Der ZWEITE Riegel: demo_fixture_laden() weist sie ab ------------- */
/*
 * ZWEI RIEGEL, ZWEI ORTE. Teil 1 und 2 messen den Riegel im ERZEUGER — er
 * verhindert, dass eine unbrauchbare Fixture entsteht. Der hier sitzt im
 * EINSPIELER (`demo_fixture_laden()`, Web 20.2.1) und verhindert, dass sie
 * eingespielt wird. Der zweite ist nicht ueberfluessig: Der Erzeuger laeuft
 * auf der Referenzmaschine, die Datei kommt auf dem Produktivserver an.
 * Dieselbe Paarung wie bei der Rundenzahl (Backlog Nr. 155).
 *
 * WAS DIESER TEIL ANFASST — und warum das vertretbar ist. Er legt die echte
 * `server/demo/fixture.json.gz` beiseite, schreibt eine verbogene an ihre
 * Stelle, laesst sie abweisen und legt die echte im `finally` zurueck. Danach
 * wird die Pruefsumme verglichen und genannt.
 *
 * Drei Sicherungen dagegen, dass daraus ein Schaden wird:
 *   1. Die Datei ist VERSIONIERT (`git ls-files server/demo/`). Geht etwas
 *      schief, stellt `git checkout -- server/demo/fixture.json.gz` sie her.
 *   2. Der Schaden waere ohnehin begrenzt: `demo_reset_wenn_faellig()` faengt
 *      jede Ausnahme ab und schreibt ins `error_log`. Das Demo-Konto hoerte
 *      auf, sich zuruecksetzen — es ginge nichts verloren.
 *   3. Das `finally` legt zurueck, auch bei einem Abbruch, und die letzte
 *      Zeile dieses Teils nennt die Pruefsumme vorher/nachher.
 */
$fxPfad   = $wurzel . '/server/demo/fixture.json.gz';
$fxSicher = sys_get_temp_dir() . '/riegelprobe-fixture-' . bin2hex(random_bytes(4)) . '.gz';
$summeVor = @hash_file('sha256', $fxPfad);

if ($summeVor === false || !copy($fxPfad, $fxSicher)) {
    fwrite(STDERR, "Die Fixture liess sich nicht beiseitelegen — Teil 3 faellt aus.\n");
    exit(2);
}

/** Die Fixture mit einer verbogenen Huelle neu schreiben. */
$verbiegen = static function (string $spalte) use ($fxSicher, $fxPfad): void {
    $j = json_decode((string)gzdecode((string)file_get_contents($fxSicher)), true);
    $alt = (string)$j['konto'][$spalte];
    /* `edk1:` durch `edka1:<acht hex>:` ersetzen — die Form, die eine Huelle
     * mit Server-Anteil traegt. Der Inhalt bleibt, er wird nie geoeffnet. */
    $j['konto'][$spalte] = 'edka1:' . bin2hex(random_bytes(4)) . ':'
                         . substr($alt, strlen('edk1:'));
    file_put_contents($fxPfad, (string)gzencode(
        (string)json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 9));
};

/** `demo_fixture_laden()` in einem EIGENEN Prozess rufen. */
$laden = static function () use ($wurzel): array {
    $d = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $p = proc_open(['php', '-r',
        'require ' . var_export($wurzel . '/server/db.php', true) . ';'
      . 'require ' . var_export($wurzel . '/server/demo_lib.php', true) . ';'
      . 'try { demo_fixture_laden(); echo "ANGENOMMEN"; }'
      . 'catch (Throwable $e) { echo "ABGEWIESEN: " . $e->getMessage(); }'], $d, $rohr);
    $aus = stream_get_contents($rohr[1]); fclose($rohr[1]);
    $err = stream_get_contents($rohr[2]); fclose($rohr[2]);
    proc_close($p);
    return ['aus' => (string)$aus, 'err' => (string)$err];
};

try {
    /* Gegenprobe zuerst: Die ECHTE Fixture geht durch. Ohne diese Zeile
     * saehe ein Riegel, der ALLES abweist, genauso gruen aus wie ein
     * richtiger. */
    $r0 = $laden();
    pruef('3  Die echte Fixture wird angenommen', str_contains($r0['aus'], 'ANGENOMMEN'),
          trim(mb_substr($r0['aus'], 0, 60)));

    $verbiegen('pat_wrap_pw');
    $r1 = $laden();
    pruef('3  Eine Fixture mit `edka1:`-Schluesselhuelle wird abgewiesen',
          str_contains($r1['aus'], 'ABGEWIESEN'),
          trim(mb_substr($r1['aus'], 0, 72)));
    pruef('3  ... und die Meldung nennt den Server-Anteil',
          str_contains($r1['aus'], 'Server-Anteil'),
          str_contains($r1['aus'], 'Server-Anteil') ? 'ja' : 'die Meldung erklaert nichts');

    copy($fxSicher, $fxPfad);
    $verbiegen('pat_wrap_rc');
    $r2 = $laden();
    pruef('4  Auch eine `edka1:`-Wiederherstellungs-Huelle wird abgewiesen (E-S10-04)',
          str_contains($r2['aus'], 'ABGEWIESEN'),
          trim(mb_substr($r2['aus'], 0, 72)));

    /* DER ABBRUCH DARF DIE INSTALLATION NICHT KOSTEN — und das ist die
     * Zusage, die das `throw` ueberhaupt vertretbar macht.
     *
     * `demo_reset_wenn_faellig()` laeuft HUCKEPACK auf jeder Anfrage des
     * Demo-Kontos (`auth_guard.php`, `ingest.php`). Wuerde die Ausnahme von
     * dort nach oben durchschlagen, machte eine verbogene Fixture die
     * oeffentliche Demo unbenutzbar — aus einem Riegel wuerde ein Ausfall.
     * Die Funktion faengt deshalb `Throwable` und schreibt ins `error_log`.
     *
     * Gemessen statt gelesen: Der Reset wird faellig gemacht, gerufen, und es
     * wird nachgesehen, dass er `false` liefert und das Konto UNVERAENDERT
     * dasteht. Zurueckgestellt wird die Marke im selben Zug. */
    $r3 = (static function () use ($wurzel): string {
        $d = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $p = proc_open(['php', '-r',
            'require ' . var_export($wurzel . '/server/db.php', true) . ';'
          . 'require ' . var_export($wurzel . '/server/demo_lib.php', true) . ';'
          . '$m = (int)db()->query("SELECT v FROM app_state WHERE k = \'demo_letzter_reset\'")'
          . '  ->fetchColumn();'
          . '$n = (int)db()->query("SELECT COUNT(*) FROM missions WHERE user_id = "'
          . '  . (int)demo_id())->fetchColumn();'
          . 'demo_reset_marke_setzen(time() - 4000);'
          . '$ok = demo_reset_wenn_faellig();'
          . '$n2 = (int)db()->query("SELECT COUNT(*) FROM missions WHERE user_id = "'
          . '  . (int)demo_id())->fetchColumn();'
          . 'if ($m > 0) { demo_reset_marke_setzen($m); }'
          . 'echo ($ok ? "RESET" : "ABGEFANGEN") . "|" . $n . "|" . $n2;'], $d, $rohr);
        $aus = stream_get_contents($rohr[1]); fclose($rohr[1]);
        fclose($rohr[2]); proc_close($p);
        return (string)$aus;
    })();
    [$lage, $vorN, $nachN] = array_pad(explode('|', trim($r3)), 3, '');
    pruef('5  Ein Reset mit verbogener Fixture wird ABGEFANGEN, nicht durchgereicht',
          $lage === 'ABGEFANGEN' && $vorN !== '' && $vorN === $nachN,
          $lage . ', Einsaetze ' . $vorN . ' -> ' . $nachN
          . ' (der Grund steht im error_log)');
} finally {
    copy($fxSicher, $fxPfad);
    @unlink($fxSicher);
    $summeNach = @hash_file('sha256', $fxPfad);
    pruef('6  Die echte Fixture liegt unveraendert zurueck',
          $summeNach !== false && $summeNach === $summeVor,
          $summeNach === $summeVor
            ? 'SHA-256 gleich (' . substr((string)$summeVor, 0, 12) . '…)'
            : 'ABWEICHUNG — `git checkout -- server/demo/fixture.json.gz`');
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $gesamt, $offen);
exit($offen === 0 ? 0 : 1);
