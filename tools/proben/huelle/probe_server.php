<?php
/* Hüllenprobe in PHP — die Gerätehilfe der Proben gegen die Referenz.
 *
 * Anlass: Nr. 43, Nr. 563 — die Proben, die den Eingang wie ein Gerät
 * bedienen, verschließen in PHP (`geraet.php`, `geraetehuelle_lib.php`);
 * was sie schreiben, muss die Referenz öffnen, und was die Referenz
 * schreibt, muss die Gerätehilfe öffnen. Gemessen wird:
 *   Bindung    `gh_bindung()` gegen jeden Fall aus `vektoren-2.json`
 *   Innenform  `gh_spur_innen()` gleich `referenz.innen_binaer()`, auch
 *              für einen Punkt ohne Höhe
 *   Rundlauf   eine Hülle der Fassung 2 vom Probegerät
 *              (`gh2_probe_verschliessen()`) öffnet die Referenz, Absender
 *              erkannt; eine der Referenz vom Probegerät öffnet
 *              `gh2_probe_oeffnen()`
 *   Gegenlage  eine Hülle der Fassung 1 (`gh_verschliessen()`) weist die
 *              Referenz als `ohne-herkunft` ab, `gh2_probe_oeffnen()` an der
 *              Länge — seit KA-07 öffnet niemand mehr Fassung 1
 *   Ableitung  `kdf_haelfte()` aus demo_lib.php gegen die Vektoren
 *
 * Bis KA-26 hielt diese Probe Fassung 1 byte-gleich gegen `vektoren.json`
 * (je Fall, gepackt, Rundlauf); die Datei und der Server-Teil sind fort.
 *
 * Aufruf:  php tools/proben/huelle/probe_server.php
 * Rückgabe: 0 alles gleich · 1 Abweichung · 2 nicht gelaufen
 */
declare(strict_types=1);

$hier = __DIR__;
require $hier . '/geraet.php';

$v2 = json_decode((string)file_get_contents($hier . '/vektoren-2.json'), true);
if (!is_array($v2)) { echo "vektoren-2.json nicht lesbar — nicht gelaufen\n"; exit(2); }

$schlecht = 0;
$zeilen = 0;
$melde = function (bool $gut, string $text) use (&$schlecht, &$zeilen): void {
    $zeilen++;
    echo '  ' . ($gut ? 'ok  ' : 'FEHL') . '  ' . $text . "\n";
    if (!$gut) { $schlecht++; }
};

/** Die Referenz mit einer Eingabe als JSON fragen; liefert ihre Ausgabe (JSON) oder null. */
$referenz = function (string $py, array $ein) use ($hier): ?array {
    $proc = proc_open(['python3', '-c', $py, $hier], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $rohr);
    fwrite($rohr[0], json_encode($ein));
    fclose($rohr[0]);
    $aus = (string)stream_get_contents($rohr[1]);
    $err = trim((string)stream_get_contents($rohr[2]));
    proc_close($proc);
    $j = json_decode($aus, true);
    if (!is_array($j) && $err !== '') { echo '    Referenz: ' . substr($err, strrpos("\n" . $err, "\n")) . "\n"; }
    return is_array($j) ? $j : null;
};

echo "Hüllenprobe in PHP (PHP " . PHP_VERSION . ', ' . OPENSSL_VERSION_TEXT . ")\n";
foreach ($v2['faelle'] as $f) {
    $melde(bin2hex(gh_bindung($f['art'], $f['client_ref'], $f['platz'])) === $f['bindung_hex'],
           "{$f['name']}: Bindung");
}

/* Innenform gegen referenz.innen_binaer(), mit einem Punkt ohne Höhe. */
$punkte = [[47.7261, 10.3159, 674.3, 1000], [47.72641, 10.31637, null, 1005], [-33.9, -70.6, -12.5, 1065]];
$soll = $referenz(<<<'PY'
import sys, json
sys.path.insert(0, sys.argv[1])
import referenz
p = json.loads(sys.stdin.read())
print(json.dumps({"hex": referenz.innen_binaer([(a, b, c, t - 1000) for a, b, c, t in p]).hex()}))
PY, $punkte);
$melde(($soll['hex'] ?? '') !== '' && bin2hex(gh_spur_innen($punkte, 1000)) === $soll['hex'],
       'Innenform binär gleich der Referenz, auch ohne Höhe (-32768)');

/* Rundlauf in beiden Richtungen, Konto der Vektoren, Absender das Probegerät. */
$konto = gh_privat_aus_d((string)hex2bin($v2['konto']['privat_hex']));
$kontoSec1 = (string)hex2bin($v2['konto']['oeffentlich_hex']);
$g = gh_probe_geraet();
$klar = random_bytes(700);
$h2 = gh2_probe_verschliessen($klar, $kontoSec1, 'spur', 'w-x', '0:50');
$h1 = gh_verschliessen(random_bytes(70), $kontoSec1, 'spur', 'w-x', '0:5');
$aus = $referenz(<<<'PY'
import sys, json, base64, hashlib
sys.path.insert(0, sys.argv[1])
import referenz as r
from cryptography.hazmat.primitives.asymmetric import ec
e = json.loads(sys.stdin.read())
v2 = json.loads(open(sys.argv[1] + '/vektoren-2.json').read())
konto = ec.derive_private_key(int(v2['konto']['privat_hex'], 16), ec.SECP256R1())
geraet = ec.derive_private_key(int.from_bytes(hashlib.sha256(b'nadoku-probegeraet-1').digest(), 'big'),
                               ec.SECP256R1())
spk = r.sec1(geraet.public_key())
leser = r.Leser(konto, {r.sid(spk).hex(): {"spk": spk, "bis": None}})
aus = {"sid": r.sid(spk).hex()}
klar, wer = leser.huelle(base64.b64decode(e['h2']), 'spur', 'w-x', '0:50')
aus["klar"], aus["absender"] = klar.hex(), wer
try:
    leser.huelle(base64.b64decode(e['h1']), 'spur', 'w-x', '0:5')
    aus["fassung1"] = "ging auf"
except r.HuelleFehler as f:
    aus["fassung1"] = str(f)
aus["zurueck"] = base64.b64encode(r.verschliessen2(bytes.fromhex(e['k']), r.sec1(konto.public_key()), geraet,
                                                    'einsatz', 'w-y', '')).decode()
print(json.dumps(aus))
PY, ['h2' => base64_encode($h2), 'h1' => base64_encode($h1), 'k' => bin2hex('{"fassung":2}')]);
$melde(($aus['sid'] ?? '') === bin2hex($g['sid']),
       'Probegerät: dieselbe Kennung (SID) in PHP und in der Referenz');
$melde(($aus['klar'] ?? '') === bin2hex($klar) && ($aus['absender'] ?? '') === bin2hex($g['sid']),
       'Rundlauf: die Referenz öffnet eine Hülle der Fassung 2 vom Probegerät, Absender erkannt');
try {
    $zurueck = gh2_probe_oeffnen((string)base64_decode((string)($aus['zurueck'] ?? '')), $konto, 'einsatz', 'w-y');
    $melde($zurueck === '{"fassung":2}', 'Rundlauf: gh2_probe_oeffnen() öffnet eine Hülle der Referenz');
} catch (Throwable $e) {
    $melde(false, 'Rundlauf: gh2_probe_oeffnen() öffnet eine Hülle der Referenz — ' . $e->getMessage());
}
$melde(($aus['fassung1'] ?? '') === 'ohne-herkunft',
       'Gegenlage: Fassung 1 öffnet die Referenz nicht (' . ($aus['fassung1'] ?? 'keine Antwort') . ')');
try {
    gh2_probe_oeffnen($h1, $konto, 'spur', 'w-x', '0:5');
    $melde(false, 'Gegenlage: gh2_probe_oeffnen() öffnet Fassung 1');
} catch (Throwable $e) {
    /* Die Länge fällt zuerst: Eine Hülle der Fassung 1 hat 82 Byte Kopf,
     * nie die Form 90 + 16·n + 32. */
    $melde(in_array($e->getMessage(), ['laenge', 'fassung'], true),
           'Gegenlage: gh2_probe_oeffnen() weist Fassung 1 ab (' . $e->getMessage() . ')');
}

/* ABLEITUNG 2 IN PHP (KA-06b-3, Anlass Nr. 563): `kdf_haelfte()` aus
 * demo_lib.php gegen die Vektoren — Fassung 1 bei 600 000, Fassung 2 bei
 * 1 200 000 Runden (die Fassungen der Ableitung, nicht der Hülle). */
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
