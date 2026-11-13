<?php
/* Hüllenprobe auf dem Server — `server/geraetehuelle_lib.php` gegen die Prüfvektoren.
 *
 * Anlass: Nr. 43 — die dritte Umsetzung der Gerätehülle (S11-04a) muss
 * byte-gleich mit Referenz und Browser schreiben. Der Server verschließt
 * nur (Demo, Proben); öffnen kann er nicht, also prüft diese Probe genau das:
 *   je Fall    die Bindung und die ganze Hülle bei festem flüchtigem
 *              Schlüssel und IV
 *   Rundlauf   eine Hülle mit zufälligem Schlüssel öffnet die Referenz
 *              (Python), mit dem privaten Schlüssel aus den Vektoren
 *   Innenform  `gh_spur_innen()` gleich `referenz.innen_binaer()`, auch
 *              für einen Punkt ohne Höhe
 *
 * Aufruf:  php tools/proben/huelle/probe_server.php
 * Rückgabe: 0 alles gleich · 1 Abweichung · 2 nicht gelaufen
 */
declare(strict_types=1);

$hier = __DIR__;
require $hier . '/../../../server/geraetehuelle_lib.php';

$v = json_decode((string)file_get_contents($hier . '/vektoren.json'), true);
if (!is_array($v)) { echo "vektoren.json nicht lesbar — nicht gelaufen\n"; exit(2); }

$schlecht = 0;
$zeilen = 0;
$melde = function (bool $gut, string $text) use (&$schlecht, &$zeilen): void {
    $zeilen++;
    echo '  ' . ($gut ? 'ok  ' : 'FEHL') . '  ' . $text . "\n";
    if (!$gut) { $schlecht++; }
};

echo "Hüllenprobe auf dem Server (PHP " . PHP_VERSION . ', ' . OPENSSL_VERSION_TEXT . ")\n";
$empf = hex2bin($v['empfaenger']['oeffentlich_hex']);
foreach ($v['faelle'] as $f) {
    $melde(bin2hex(gh_bindung($f['art'], $f['client_ref'], $f['platz'])) === $f['bindung_hex'],
           "{$f['name']}: Bindung");
    $h = gh_verschliessen(hex2bin($f['klartext_hex']), $empf, $f['art'], $f['client_ref'],
                              $f['platz'], ['d' => hex2bin($f['fluechtig_privat_hex']),
                                            'iv' => hex2bin($f['iv_hex'])]);
    $melde(base64_encode($h) === $f['huelle_b64'], "{$f['name']}: Hülle byte-gleich (" . strlen($h) . ' Byte)');
}

/* Rundlauf: die Referenz öffnet, was der Server mit Zufall verschlossen hat. */
$klar = random_bytes(700);
$h = gh_verschliessen($klar, $empf, 'spur', 'w-x', '0:50');
$py = <<<'PY'
import sys, json, base64, pathlib
sys.path.insert(0, sys.argv[1])
import referenz
from cryptography.hazmat.primitives.asymmetric import ec
v = json.loads(pathlib.Path(sys.argv[1], 'vektoren.json').read_text())
d = int(v['empfaenger']['privat_hex'], 16)
k = ec.derive_private_key(d, ec.SECP256R1())
eingabe = json.loads(sys.stdin.read())
print(referenz.oeffnen(base64.b64decode(eingabe['h']), k, 'spur', 'w-x', '0:50').hex())
PY;
$proc = proc_open(['python3', '-c', $py, $hier], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $rohr);
fwrite($rohr[0], json_encode(['h' => base64_encode($h)]));
fclose($rohr[0]);
$aus = trim((string)stream_get_contents($rohr[1]));
$err = trim((string)stream_get_contents($rohr[2]));
proc_close($proc);
$melde($aus === bin2hex($klar), 'Rundlauf: die Referenz öffnet eine Hülle mit zufälligem Schlüssel'
       . ($aus === bin2hex($klar) ? '' : ' — ' . ($err !== '' ? explode("\n", $err)[count(explode("\n", $err)) - 1] : 'falscher Inhalt')));

/* Innenform gegen referenz.innen_binaer(), mit einem Punkt ohne Höhe. */
$punkte = [[47.7261, 10.3159, 674.3, 1000], [47.72641, 10.31637, null, 1005], [-33.9, -70.6, -12.5, 1065]];
$py2 = <<<'PY'
import sys, json
sys.path.insert(0, sys.argv[1])
import referenz
p = json.loads(sys.stdin.read())
print(referenz.innen_binaer([(a, b, c, t - 1000) for a, b, c, t in p]).hex())
PY;
$proc = proc_open(['python3', '-c', $py2, $hier], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $rohr);
fwrite($rohr[0], json_encode($punkte));
fclose($rohr[0]);
$soll = trim((string)stream_get_contents($rohr[1]));
proc_close($proc);
$melde(bin2hex(gh_spur_innen($punkte, 1000)) === $soll && $soll !== '',
       'Innenform binär gleich der Referenz, auch ohne Höhe (-32768)');

/* Gepackt (Stufe 2, 3): `gh_gepackt_bauen()` byte-gleich der Referenz —
 * Innenform vor dem Packen gleich `gepackt_roh()`, Hülle mit festem
 * Schlüssel und IV gleich `verschliessen(innen_gepackt(…))` (Nr. 500). */
foreach ($v['gepackt'] ?? [] as $g) {
    $pk = array_map(static fn($p) => [(int)$p[0], (float)$p[1], (float)$p[2],
                                      $p[3] === null ? null : (float)$p[3], (int)$p[4]], $g['punkte']);
    $ts = min(array_map(static fn($p) => $p[4], $pk));
    $roh = gzuncompress(gh_gepackt_innen($pk, $ts));
    $melde($roh !== false && bin2hex($roh) === $g['roh_hex'],
           $g['name'] . ': Innenform „B fest“ gleich der Referenz (' . strlen((string)$roh) . ' Byte)');
    $st = gh_gepackt_bauen($empf, $g['art'], $g['client_ref'], $pk, (int)$g['stufe'], (int)$g['seq_from'],
                           (int)$g['n_original'], ['d' => hex2bin($g['fluechtig_privat_hex']),
                                                   'iv' => hex2bin($g['iv_hex'])]);
    $melde(base64_encode($st['huelle']) === $g['huelle_b64'],
           "{$g['name']}: Hülle byte-gleich (" . strlen($st['huelle']) . ' Byte, Bindung ' . $g['platz'] . ')');
}
if (empty($v['gepackt'])) { $melde(false, 'vektoren.json trägt keine gepackten Fälle — neu erzeugen'); }

/* ABLEITUNG 2 IN PHP (KA-06b-3, Anlass Nr. 563): `kdf_haelfte()` aus
 * demo_lib.php gegen die Vektoren — Fassung 1 bei 600 000, Fassung 2 bei
 * 1 200 000 Runden. */
require_once $hier . '/../../../server/db.php';
require_once $hier . '/../../../server/demo_lib.php';
$vi = json_decode((string)file_get_contents($hier . '/vektoren-inhalt.json'), true);
foreach (($vi['ableitungen'] ?? []) as $i => $a) {
    $salz = hex2bin($a['salz_hex']);
    $melde(bin2hex(kdf_haelfte($a['passwort'], $salz, $a['fassung_1']['runden'])) === $a['fassung_1']['haelfte_hex'],
           "Ableitung $i: Fassung 1 ({$a['fassung_1']['runden']} Runden), Hälfte");
    $melde(bin2hex(kdf_haelfte($a['passwort'], $salz, $a['fassung_2']['runden'])) === $a['fassung_2']['haelfte_hex'],
           "Ableitung $i: Fassung 2 ({$a['fassung_2']['runden']} Runden), Hälfte");
}

echo "$zeilen Prüfungen, $schlecht Abweichungen\n";
exit($schlecht ? 1 : 0);
