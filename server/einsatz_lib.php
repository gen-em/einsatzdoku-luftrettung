<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Einsaetze: laden mit Besitzpruefung (Schritt 15/AP4, E-ZE-19).
 *
 * WARUM DIESE DATEI ES GIBT. „Einen Einsatz per Kennung holen und dabei
 * pruefen, dass er dem angemeldeten Konto gehoert" stand bis Web 20.28.0 an
 * ZWOELF Stellen in neun Dateien, jede mit ihrer eigenen Abfrage. Der
 * Unterschied zwischen ihnen war nie die Sache, sondern nur die
 * Spaltenliste und die Frage, wie der Papierkorb behandelt wird — mal
 * `deleted_at IS NULL`, mal `IS NOT NULL`, mal gar nicht. Genau das sind
 * jetzt die beiden Optionen.
 *
 * DIE BESITZPRUEFUNG IST DER PUNKT, nicht das Laden (M3-03, M3-12/M6-09).
 * Sie gehoert IN die Abfrage und nicht davor oder danach: Eine Bedingung,
 * die nicht in der Abfrage steht, wird von nichts durchgesetzt. Bei zwoelf
 * handgeschriebenen Abfragen ist „steht `user_id = ?` da?" eine Frage, die
 * man zwoelfmal stellen muss; bei einer Funktion ist sie einmal beantwortet.
 *
 * ZWEI STELLEN BLEIBEN, UND ZWAR NAMENTLICH:
 *
 *   `trash_restore_mission()` (`trash_lib.php`) — sie fragt den Einsatz UND
 *   den Loeschzustand seines Diensttags in einem Verbund mit `days`. Ein
 *   zweiter Zugriff waere eine zweite Abfrage fuer eine Entscheidung, die
 *   zusammen getroffen werden muss (E-ZE-19).
 *
 *   `api/import_commit.php` — dort ist die Abfrage eine EINMAL vorbereitete
 *   Anweisung, die in der Import-Schleife bis zu 3000-mal ausgefuehrt wird,
 *   neben `$insE` und `$updE`. Ein Funktionsaufruf je Zeile bereitete sie
 *   3000-mal neu vor. Das ist keine Zentralisierung mehr, sondern ein
 *   Messstand-Thema (Schritt 15/AP4, AP4-b).
 */

/**
 * Einen Einsatz laden — mit Besitzpruefung, ohne Umwege.
 *
 * @param array{spalten?:string, papierkorb?:string} $o
 *        `spalten`     Spaltenliste fuer das `SELECT`. Vorgabe `'*'`.
 *                      **Sie kommt aus dem Code, nie aus einer Anfrage** —
 *                      sie wird eingesetzt und nicht gebunden.
 *        `papierkorb`  `'nein'` (Vorgabe, `deleted_at IS NULL`) ·
 *                      `'ja'` (`deleted_at IS NOT NULL`) ·
 *                      `'egal'` (keine Bedingung)
 * @return array<string,mixed>|null `null` = gibt es nicht, gehoert nicht
 *         diesem Konto, oder liegt auf der falschen Seite des Papierkorbs.
 *         Die drei Faelle sind bewusst NICHT unterscheidbar: Wer sie
 *         unterscheidet, sagt einem Fremden, dass es die Kennung gibt.
 */
function einsatz_laden(int $id, int $userId, array $o = []): ?array
{
    $spalten = (string)($o['spalten'] ?? '*');
    $papierkorb = (string)($o['papierkorb'] ?? 'nein');

    $wo = match ($papierkorb) {
        'nein' => ' AND deleted_at IS NULL',
        'ja'   => ' AND deleted_at IS NOT NULL',
        'egal' => '',
        default => throw new InvalidArgumentException(
            'einsatz_laden: unbekannter Wert fuer papierkorb: ' . $papierkorb),
    };

    $st = db()->prepare("SELECT $spalten FROM missions
                          WHERE id = ? AND user_id = ?" . $wo);
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    return $m === false ? null : $m;
}
