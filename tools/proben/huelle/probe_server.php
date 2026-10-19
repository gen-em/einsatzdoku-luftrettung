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
 *              für einen Punkt ohne Höhe (E-S11-39)
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

echo "$zeilen Prüfungen, $schlecht Abweichungen\n";
exit($schlecht ? 1 : 0);
