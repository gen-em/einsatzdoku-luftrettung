<?php
declare(strict_types=1);

/**
 * Prueffutter fuer die Containerprobe: Spurteile der Nutzlast 13.
 *
 * Ein Spurteil traegt je Spur eine PUNKTLISTE
 * ([seq, lat, lon, ele|null, ts]), die der Browser beim Sichern aus den
 * verschlossenen Stuecken oeffnet. Die Probe belegt, dass
 * eine Punktliste durch Fassung 4 hindurch unveraendert ankommt.
 *
 * Ausgabe auf stdout: JSON mit `spuren` (spur_ref, stufe, n_original, n,
 * punkte) und `punkte` (dieselbe spur_ref -> Punktliste), damit die
 * Gegenseite Punkt fuer Punkt vergleichen kann.
 *
 * Aufruf: php tools/proben/container/spuren_bauen.php [anzahl] [punkte_je_spur]
 */

$anzahl = max(1, (int)($argv[1] ?? 6));
$jeSpur = max(2, (int)($argv[2] ?? 300));

$spuren = [];
$punkte = [];

for ($s = 0; $s < $anzahl; $s++) {
    $liste = [];
    for ($i = 0; $i < $jeSpur; $i++) {
        /* Absichtlich unrunde Werte und jeder fuenfte Punkt ohne Hoehe. Eine
         * Probe mit glatten Zahlen belegt nichts ueber die Darstellung. */
        $liste[] = [
            $i,
            round(47.0 + $s * 0.01 + $i * 0.000123, 7),
            round(11.0 + $s * 0.01 + $i * 0.000071, 7),
            $i % 5 === 0 ? null : (float)round(700.0 + $s * 3 + $i * 0.7),
            1772000000 + $s * 86400 + $i * 7,
        ];
    }
    $ref = $s + 1;
    $spuren[] = ['spur_ref' => $ref, 'stufe' => 2,
                 'n_original' => $jeSpur, 'n' => $jeSpur, 'punkte' => $liste];
    $punkte[(string)$ref] = $liste;
}

echo json_encode(['spuren' => $spuren, 'punkte' => $punkte],
                 JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
