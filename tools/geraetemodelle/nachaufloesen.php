<?php
declare(strict_types=1);

/**
 * Gerätekennungen nachträglich auflösen — für Zeilen, die beim Koppeln auf
 * eine Modelltabelle trafen, die ihr Gerät noch nicht kannte.
 *
 * WOFUER. `pair.php` löst die Teilenummer im Moment der Kopplung auf, und nur
 * dann. Trifft sie dabei auf eine leere oder ältere Tabelle, bleibt
 * `geraet_modell` leer — und, was schwerer wiegt, `geraet_art` steht auf der
 * ungeprüften Selbstauskunft des Geräts. Die Garmin-App sendet dort fest
 * `"uhr"`; ein Radcomputer wäre damit dauerhaft als Uhr gezählt, obwohl die
 * Gerätedateien es besser wissen. Genau dafür steht die Rohangabe in einer
 * eigenen Spalte (E-S6-1): Sie hält den Schlüssel bereit, mit dem sich jede
 * Zeile später erneut auflösen lässt. **Dieses Skript ist das „später".**
 *
 * WANN ES GEBRAUCHT WIRD. Nach jedem Lauf von `erzeugen.py` — also wenn die
 * Gerätedateien erstmals vorliegen oder ein neues Uhrmodell dazugekommen ist.
 * Vorher meldet es schlicht, dass es nichts zu tun gibt.
 *
 * ES ÄNDERT NUR, WAS DIE TABELLE WIRKLICH KENNT. Eine Zeile, deren Rohangabe
 * unbekannt bleibt, wird nicht angefasst — kein Leeren, kein „unbekannt", kein
 * Zurücksetzen. Und die Rohangabe selbst wird NIE verändert: Sie ist die
 * Auskunft des Geräts und muss die einzige Spalte bleiben, die man nicht
 * nachrechnet.
 *
 * HANDY-ZEILEN BLEIBEN UNBERUEHRT. Ein Handy kennt seinen Modellnamen selbst;
 * seine Rohangabe IST der Klarname, und die Modelltabelle enthält keine
 * Handys (eine Connect-IQ-App läuft nicht auf einem Handy). Erkannt wird das
 * daran, dass die Tabelle die Rohangabe nicht führt — dieselbe Regel wie für
 * jede andere unbekannte Angabe, kein Sonderfall.
 *
 * AUFRUF
 *
 *     php tools/geraetemodelle/nachaufloesen.php            (nur zeigen)
 *     php tools/geraetemodelle/nachaufloesen.php --schreiben
 *
 * Optional als weiteres Argument der Pfad zu `server/`.
 *
 * ZEIGEN IST DIE VORGABE, und das ist kein Zierrat: Das Skript ändert Zeilen
 * einer Produktivdatenbank. Wer sieht, was es vorhat, sieht auch, wenn eine
 * frisch erzeugte Tabelle Unsinn enthält — bevor er ihn einträgt.
 *
 * BRAUCHT SHELL-ZUGRIFF. Auf einem Webspace ohne SSH gibt es diesen Weg
 * nicht; dort holen die betroffenen Geräte ihre Angabe bei der nächsten
 * Kopplung nach. Das ist eine Grenze und keine Ausrede — sie steht deshalb
 * auch in `LIESMICH.md` und in `docs/Technik.md`, Abschnitt 7.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur über die Kommandozeile.\n");
}

$argumente  = array_slice($argv, 1);
$schreiben  = in_array('--schreiben', $argumente, true);
$pfade      = array_values(array_filter($argumente, static fn($a) => $a[0] !== '-'));
$serverPfad = $pfade[0] ?? dirname(__DIR__, 2) . '/server';

if (!is_file($serverPfad . '/db.php')) {
    exit("Kein server/ unter: $serverPfad\n");
}
require_once $serverPfad . '/db.php';
require_once $serverPfad . '/geraete_lib.php';

$pdo = db();
$st  = $pdo->query('SELECT id, device_id, label, geraet_art, geraet_modell, geraet_teil
                    FROM devices
                    WHERE geraet_teil IS NOT NULL
                    ORDER BY id');
$zeilen = $st->fetchAll();

$bekannt = count(GERAETE_MODELLE);
echo "Modelltabelle: $bekannt Teilenummern.\n";
echo 'Geräte mit Rohangabe: ' . count($zeilen) . "\n\n";

if ($bekannt === 0) {
    echo "Die Modelltabelle ist leer — es gibt nichts aufzulösen.\n";
    echo "Zuerst: python3 tools/geraetemodelle/erzeugen.py <Gerätedateien>\n";
    exit(0);
}

$aendern = [];
foreach ($zeilen as $z) {
    $treffer = geraet_modell_aufloesen((string)$z['geraet_teil']);
    if ($treffer === null) { continue; }

    $neuModell = $treffer['modell'];
    $neuArt    = $treffer['art'] ?? $z['geraet_art'];
    if ((string)$z['geraet_modell'] === (string)$neuModell
        && (string)$z['geraet_art'] === (string)$neuArt) {
        continue;                        // steht schon richtig da
    }
    $aendern[] = ['zeile' => $z, 'modell' => $neuModell, 'art' => $neuArt];
}

if ($aendern === []) {
    echo "Nichts zu tun — jede auflösbare Zeile steht bereits richtig.\n";
    exit(0);
}

foreach ($aendern as $a) {
    $z = $a['zeile'];
    printf("  #%-4d %-24s %s\n", (int)$z['id'], (string)$z['geraet_teil'],
        sprintf('%s / %s  →  %s / %s',
            $z['geraet_art']    ?? '—', $z['geraet_modell'] ?? '—',
            $a['art']           ?? '—', $a['modell']));
}
echo "\n" . count($aendern) . " Zeile(n) betroffen.\n";

if (!$schreiben) {
    echo "Nichts geschrieben. Mit --schreiben eintragen.\n";
    exit(0);
}

$up = $pdo->prepare('UPDATE devices SET geraet_art = ?, geraet_modell = ? WHERE id = ?');
$pdo->beginTransaction();
try {
    foreach ($aendern as $a) {
        $up->execute([$a['art'], $a['modell'], (int)$a['zeile']['id']]);
    }
    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    exit('Fehlgeschlagen, nichts geändert: ' . $ex->getMessage() . "\n");
}
echo count($aendern) . " Zeile(n) eingetragen.\n";
