<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';    // Anmeldung, liefert $userId
require_once __DIR__ . '/../kopplung_lib.php';

/**
 * GET api/kopplung_stand.php -> {"zustand": "…"} — wartet dieses Konto noch
 * auf ein Gerät, und hat es inzwischen Ja gesagt? (S5, E-S5-53)
 *
 * DER ENDPUNKT NIMMT KEINE EINGABE. Das ist Absicht und die kleinste
 * Angriffsfläche, die dieser Zweck zulässt: Welche Kopplungssitzung gemeint
 * ist, steht in der Sitzung des Browsers ($_SESSION['pair_warten'], gesetzt
 * beim Beanspruchen in einstellungen.php) — nicht in einem Parameter, den
 * jemand mit einer fremden Gerätekennung füllen könnte. Ohne Eintrag gibt es
 * nichts zu erfahren, und die Antwort lautet „keine".
 *
 * FÜNF ZUSTÄNDE, und jeder ist ein Ende oder ein Weiter:
 *
 *   wartet      Die Sitzung steht, das Gerät hat noch nicht geantwortet.
 *               `rest_s` ist die Restgültigkeit — das Skript zeigt sie an und
 *               hört von selbst auf, wenn sie abgelaufen ist.
 *   gekoppelt   Es gibt das Gerät. Das Skript holt die Seite.
 *   verworfen   Sitzung weg, kein Gerät: Am Gerät wurde Nein gesagt.
 *   abgelaufen  Die Frist ist vorbei; die Zeile liegt noch bis zum Aufräumjob.
 *   keine       Dieses Konto wartet gerade auf nichts.
 *
 * WARUM GET UND OHNE CSRF-PRÜFUNG: Der Endpunkt liest und ändert nichts —
 * dasselbe Muster wie api/day.php im GET-Zweig. Was er verrät, weiß der
 * Aufrufer ohnehin: Es ist der Vorgang, den er selbst eine Minute zuvor
 * angestoßen hat, und es geht nur mit seiner eigenen Anmeldung.
 *
 * KEIN EIGENER RATENSCHUTZ: Es gibt hier nichts zu erraten — der Endpunkt
 * nimmt keine Eingabe. Er kostet eine Abfrage auf einen eindeutigen Schlüssel,
 * und wer angemeldet ist, kann auf jeder anderen Seite mehr Arbeit auslösen.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'GET') { json_out(['error' => 'method'], 405); }

$kennung = (string)($_SESSION['pair_warten'] ?? '');
if ($kennung === '') { json_out(['zustand' => 'keine']); }

$pdo = db();
$sitzung = pair_sitzung_nach_kennung($pdo, $kennung);

/* DIE KONTOKENNUNG STEHT IN DER BEDINGUNG. Eine Erinnerung aus einer fremden
 * Sitzung — möglich nach einem Kontowechsel im selben Browser — beantwortet
 * damit nichts über ein fremdes Gerät. */
if ($sitzung !== null && (int)($sitzung['user_id'] ?? 0) === $userId) {
    $rest = (int)$sitzung['rest_s'];
    json_out($rest > 0
        ? ['zustand' => 'wartet', 'rest_s' => $rest]
        : ['zustand' => 'abgelaufen']);
}

/* Die Sitzung ist weg. Entweder ist daraus ein Gerät geworden (das Ja auf dem
 * Gerät löscht die Sitzung und legt die Zeile an — in einer Transaktion,
 * pair.php), oder sie wurde verworfen. Beides ist ein Ende. */
json_out(['zustand' => pair_geraet_da($pdo, $kennung, $userId) ? 'gekoppelt' : 'verworfen']);
