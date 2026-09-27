<?php
declare(strict_types=1);

/**
 * Rundlauf aufraeumen — was die Rundlauffaelle der Handy-App hochgeladen
 * haben, ueber die Wege der Anwendung wieder abraeumen (R4-21).
 *
 * Anlass: Nr. 95 — die Rundlauffaelle liessen Diensttage, Einsaetze und
 * Spurpunkte im Konto 1 zurueck (gemessen zuletzt 18 Diensttage,
 * 10 Einsaetze, 28 Ruhesegmente).
 *
 * WARUM NICHT DIE `mariadb`-ZEILE DER KOPPLUNGSHILFE. Ein `DELETE` auf
 * `missions` und `rest_segments` liesse die Spur als Waise zurueck:
 * `track_points` und `track_blobs` haengen an keinem Fremdschluessel, und
 * beide fasst ausschliesslich `spur_lib.php` an (CLAUDE.md 4). Genau das ist
 * der Verbindungsprobe passiert (F-R4-63). Dieses Skript geht deshalb den Weg
 * einer NutzerIn: Diensttag in den Papierkorb (`trash_delete_day()`), dann
 * endgueltig entfernen (`trash_purge_day()`) — mit Einsaetzen, Ruhesegmenten,
 * Spur, Schnitten und Sperrvermerken.
 *
 * WELCHE DIENSTTAGE. Die der GERAETE des Kontos, nicht das ganze Konto: Wer
 * den Rundlauf gegen eine andere oertliche Installation faehrt, soll dort
 * nicht alles verlieren, was in Konto 1 steht. Die Kopplungshilfe legt diese
 * Geraete an und loescht sie (`aufraeumen()` raeumt `devices` des Kontos ganz
 * ab), sie sind also schon heute als Pruefgeraete behandelt. Verbunden sind
 * Tag und Geraet ueber `day_refs.device_id`, `missions.device_id` und
 * `rest_segments.device_id`.
 *
 * DESHALB VOR DEM TRENNEN. `trennen` loescht die Geraetezeile, und die drei
 * Spalten tragen ON DELETE SET NULL — danach waere nicht mehr zu erkennen,
 * welche Daten vom Lauf stammen. Die Rundlauffaelle rufen dieses Skript
 * (ueber `Kopplungshilfe.datenAbraeumen()`) deshalb als ERSTES im `@After`.
 *
 * DIE SPERRVERMERKE GEHEN MIT. `trash_purge_day()` sperrt die Kennungen der
 * Geraete in `deleted_refs`, damit keine Nachlieferung den Tag wieder anlegt.
 * Die Tabelle hat keinen Fremdschluessel; `konto_loeschen()` raeumt sie
 * deshalb ausdruecklich ab, und dieses Skript tut es fuer die Geraete des
 * Laufs ebenso — sonst blieben Vermerke fuer Geraete liegen, die es gleich
 * nicht mehr gibt.
 *
 * AUFRUF:  php android/werkzeuge/rundlauf_aufraeumen.php <konto-id>
 * Ausgabe: eine Zeile mit den Zahlen; Rueckgabe 0, bei einem Fehler 1.
 */

require_once __DIR__ . '/../../server/db.php';
require_once __DIR__ . '/../../server/trash_lib.php';

$konto = (int)($argv[1] ?? 0);
if ($konto <= 0) {
    fwrite(STDERR, "Aufruf: php rundlauf_aufraeumen.php <konto-id>\n");
    exit(2);
}

try {
    $pdo = db();
    $st = $pdo->prepare('SELECT id FROM devices WHERE user_id = ?');
    $st->execute([$konto]);
    $geraete = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
    if (!$geraete) {
        echo "Rundlauf aufgeraeumt: 0 Geraete, nichts zu tun\n";
        exit(0);
    }
    $in = implode(',', $geraete);

    /* Die Tage aus allen drei Verbindungen: Ein Tag kann nur Ruhesegmente
     * haben, ein Einsatz auf einem Tag liegen, dessen Kennung ein anderes
     * Geraet vergeben hat. */
    $st = $pdo->prepare("SELECT DISTINCT d.id FROM days d
                          WHERE d.user_id = ?
                            AND (d.id IN (SELECT day_id FROM day_refs WHERE device_id IN ($in))
                              OR d.id IN (SELECT day_id FROM missions WHERE device_id IN ($in))
                              OR d.id IN (SELECT day_id FROM rest_segments WHERE device_id IN ($in)))");
    $st->execute([$konto]);
    $tage = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

    foreach ($tage as $tag) {
        trash_delete_day($konto, $tag);
        trash_purge_day($konto, $tag);
    }

    $vermerke = $pdo->exec("DELETE FROM deleted_refs WHERE device_id IN ($in)");

    /* Was an den Geraeten haengt und an keinem ihrer Tage lag — etwa ein
     * Einsatz ohne Diensttag. Es sollte nichts sein; steht doch etwas da,
     * sagt es die Zahl, statt es still zu loeschen. */
    $rest = (int)$pdo->query("SELECT (SELECT COUNT(*) FROM missions WHERE device_id IN ($in))
                                   + (SELECT COUNT(*) FROM rest_segments WHERE device_id IN ($in))")
                     ->fetchColumn();

    printf("Rundlauf aufgeraeumt: %d Geraete, %d Diensttage, %d Sperrvermerke, %d uebrig\n",
           count($geraete), count($tage), (int)$vermerke, $rest);
    exit($rest === 0 ? 0 : 1);
} catch (Throwable $e) {
    fwrite(STDERR, 'Rundlauf aufraeumen gescheitert: ' . $e->getMessage() . "\n");
    exit(1);
}
