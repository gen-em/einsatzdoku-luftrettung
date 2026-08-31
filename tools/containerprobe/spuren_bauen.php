<?php
declare(strict_types=1);

/**
 * Prueffutter fuer die Containerprobe: echte SPUR1-Blobs aus spur_lib.php.
 *
 * WARUM PHP UND NICHT EIN NACHBAU. Die Probe will belegen, dass eine Spur
 * durch Fassung 4 hindurch unveraendert ankommt. Wuerde sie ihre Blobs selbst
 * bauen, pruefte sie ihren eigenen Nachbau — und genau die Sorte Zahl, die
 * etwas anderes misst, als ihre Beschriftung sagt, sammelt dieses Projekt in
 * CLAUDE.md 6.
 *
 * Ausgabe auf stdout: JSON mit `spuren` (spur_ref, stufe, n_original, n,
 * blob als Base64) und `punkte` (dieselbe spur_ref -> Punktliste), damit die
 * Gegenseite Punkt fuer Punkt vergleichen kann.
 *
 * Aufruf: php tools/containerprobe/spuren_bauen.php [anzahl] [punkte_je_spur]
 */

require_once dirname(__DIR__, 2) . '/server/spur_lib.php';

$anzahl = max(1, (int)($argv[1] ?? 6));
$jeSpur = max(2, (int)($argv[2] ?? 300));

$spuren = [];
$punkte = [];

for ($s = 0; $s < $anzahl; $s++) {
    $liste = [];
    for ($i = 0; $i < $jeSpur; $i++) {
        /* Absichtlich unrunde Werte: 0,1-m-Hoehen (drei von vier Punkten des
         * Referenzbestands tragen eine, F-S2-01) und jeder fuenfte Punkt ohne
         * Hoehe. Eine Probe mit glatten Zahlen belegt die Aufloesung nicht. */
        $liste[] = [
            $i,
            47.0 + $s * 0.01 + $i * 0.000123,
            11.0 + $s * 0.01 + $i * 0.000071,
            $i % 5 === 0 ? null : round(700.0 + $s * 3 + $i * 0.7, 1),
            1772000000 + $s * 86400 + $i * 7,
        ];
    }
    /* Durch die Quantisierung schicken — genau das legt der Blob ab, und
     * genau das muss auf der anderen Seite herauskommen. `spur_quantisieren()`
     * nimmt EINEN Punkt, nicht die Liste. */
    $soll = array_map('spur_quantisieren', $liste);
    $blob = spur_kodieren($liste, SPUR_STUFE_ROH, $jeSpur);

    $fehler = spur_rundlauf_pruefen($liste, $blob);
    if ($fehler !== null) {
        fwrite(STDERR, "Rundlauf schon in PHP nicht in Ordnung: $fehler\n");
        exit(1);
    }

    $ref = $s + 1;
    $spuren[] = ['spur_ref' => $ref, 'stufe' => SPUR_STUFE_ROH,
                 'n_original' => $jeSpur, 'n' => $jeSpur,
                 'blob' => base64_encode($blob)];
    $punkte[(string)$ref] = $soll;
}

echo json_encode(['spuren' => $spuren, 'punkte' => $punkte],
                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
