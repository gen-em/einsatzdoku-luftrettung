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
 *     php tools/erzeugen/geraetemodelle-nachaufloesen.php            (nur zeigen)
 *     php tools/erzeugen/geraetemodelle-nachaufloesen.php --schreiben
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
/* DIE LOGIK STECKT SEIT WEB 20.15.0 IN DER BIBLIOTHEK (P5a/AP11, E-P5a-21).
 *
 * Sie stand bis dahin HIER, und das war die Grenze der Sache: Auf einem
 * Webspace ohne SSH gibt es diesen Weg nicht, und die betroffenen Geraete
 * holten ihre Angabe erst bei der naechsten Kopplung nach — also womoeglich
 * nie. Jetzt fuehrt derselbe Kern auch der Hintergrundjob `nachaufloesen`
 * aus, und dieses Skript bleibt, was es immer war: die VORSCHAU mit Namen
 * und Zeile, und der Weg fuer den, der lieber selbst zusieht. */
require_once $serverPfad . '/geraetemodelle_lib.php';

$pdo = db();

$bekannt = count(GERAETE_MODELLE);
echo "Modelltabelle: $bekannt Teilenummern.\n";
echo 'Geräte mit Rohangabe: ' . gm_zeilen_mit_rohangabe($pdo) . "\n\n";

if ($bekannt === 0) {
    echo "Die Modelltabelle ist leer — es gibt nichts aufzulösen.\n";
    echo "Zuerst: python3 tools/erzeugen/geraetemodelle.py <Gerätedateien>\n";
    exit(0);
}

/* ERST SAMMELN, DANN SCHREIBEN — auch mit `--schreiben`.
 *
 * `gm_nachaufloesen()` koennte je Block gleich eintragen. Hier ist das
 * falsch: Die Vorschau ist der Zweck dieses Skripts, und sie soll die GANZE
 * Liste zeigen, bevor eine Zeile faellt. Wer `--schreiben` setzt, hat sie
 * beim vorigen Lauf gesehen. */
$aendern = [];
$unbekannt = 0;
$ab = 0;
while (true) {
    $e = gm_nachaufloesen($pdo, GM_BLOCK, false, $ab);
    foreach ($e['kandidaten'] as $k) { $aendern[] = $k; }
    $unbekannt += $e['unbekannt'];
    $ab = $e['letzte_id'];
    if ($e['fertig']) { break; }
}

if ($aendern === []) {
    echo "Nichts zu tun — jede auflösbare Zeile steht bereits richtig.\n";
    if ($unbekannt > 0) {
        echo "($unbekannt Zeile(n) mit einer Rohangabe, die die Tabelle nicht "
           . "kennt — Handys und unbekannte Modelle; sie bleiben unberührt.)\n";
    }
    exit(0);
}

foreach ($aendern as $a) {
    printf("  #%-4d %-24s %s\n", $a['id'], $a['teil'],
        sprintf('%s / %s  →  %s / %s',
            $a['alt_art'] ?? '—', $a['alt_modell'] ?? '—',
            $a['art'] ?? '—', $a['modell']));
}
echo "\n" . count($aendern) . " Zeile(n) betroffen";
echo $unbekannt > 0 ? ", $unbekannt unbekannt (unberührt).\n" : ".\n";

if (!$schreiben) {
    echo "Nichts geschrieben. Mit --schreiben eintragen.\n";
    exit(0);
}

/* GESCHRIEBEN WIRD UEBER DIESELBE BIBLIOTHEK, nicht mit eigenem SQL: Zwei
 * Fassungen desselben UPDATE waeren zwei Gelegenheiten, die Rohangabe
 * mitzuschreiben. */
$geschrieben = 0;
$ab = 0;
try {
    while (true) {
        $e = gm_nachaufloesen($pdo, GM_BLOCK, true, $ab);
        $geschrieben += $e['geschrieben'];
        $ab = $e['letzte_id'];
        if ($e['fertig']) { break; }
    }
} catch (Throwable $ex) {
    exit('Fehlgeschlagen: ' . $ex->getMessage() . "\n"
       . "Bereits eingetragene Blöcke bleiben stehen — jeder Block ist eine "
       . "eigene Transaktion.\n");
}

/* DER HASH WIRD MITGESCHRIEBEN. Sonst liefe der Job gleich darauf noch
 * einmal ueber denselben Bestand — er wuesste nicht, dass hier schon jemand
 * war. */
gm_stand_merken(gm_tabellen_hash(), $geschrieben, $unbekannt);
echo "$geschrieben Zeile(n) eingetragen.\n";
