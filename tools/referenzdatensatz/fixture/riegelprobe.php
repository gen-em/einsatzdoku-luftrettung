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
 * Erwartet: **4 von 4**, Rueckgabe 0.
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
      . "mit tools/anteilprobe/huelle_stellen.py <konto> <passwort> edka1.\n",
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

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $gesamt, $offen);
exit($offen === 0 ? 0 : 1);
