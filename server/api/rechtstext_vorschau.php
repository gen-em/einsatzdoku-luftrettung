<?php
declare(strict_types=1);

/**
 * VORSCHAU EINES RECHTSTEXTS BEIM TIPPEN (P5c/AP9, E-P5c-28, Nr. 121).
 *
 * WAS ER TUT: Er nimmt Text und Standdatum aus dem Feld der Seite
 * „Rechtstexte" und gibt zurück, was `rt_html()` und `rt_stand_markup()`
 * daraus machen — dasselbe Markup, das die öffentliche Seite zeigt. Er
 * SPEICHERT NICHTS und schreibt kein Protokoll: Die Vorschau ist ein Blick,
 * keine Handlung.
 *
 * WARUM AUF DEM SERVER: Ein zweiter Renderer im Browser läse eines Tages
 * anders als der, der die öffentliche Seite baut — und die Vorschau zeigte
 * dann eine Seite, die es nicht gibt. Hier gibt es genau einen.
 *
 * DIE REIHENFOLGE IST DIE DES HAUSES (db.php, „Der Eingang der Endpunkte"):
 * Methode, ROLLE, Token, Ratenschutz, Rumpf. Die Rolle steht VOR dem Token
 * (Muster E-P5c-85), damit die Rollenprobe das Rollentor von der
 * Token-Ablehnung unterscheiden kann.
 *
 * NUR ADMIN UND BETREIBERIN — dieselben, die die Seite öffnen dürfen
 * (`require_admin()`); der Support nicht.
 *
 * DER RUMPF DARF VIERMAL SO GROSS SEIN WIE EIN TEXT (4 × `RT_MAX_ZEICHEN`
 * Bytes): Ein Zeichen braucht in UTF-8 bis zu vier Bytes, dazu kommt die
 * JSON-Maskierung. Ein zu langer TEXT ist ein Befund von `rt_pruefen()` und
 * kommt als Satz zurück; ein zu großer RUMPF ist kein Text mehr (413).
 */

require_once __DIR__ . '/../auth_guard.php';
require_once __DIR__ . '/../rechtstexte_lib.php';
require_once __DIR__ . '/../ratelimit_lib.php';

api_methode();
require_admin();
csrf_check();

/* DIE MENGE, NICHT DER FEHLVERSUCH (Topf `rt_vorschau`, siehe RATE_GRENZEN).
 * Das Skript fragt 0,4 s nach dem letzten Tastendruck — wer tippt, erzeugt
 * damit höchstens ein paar Abrufe je Minute. Die Grenze fängt ein Skript ab,
 * das den Renderer als Rechenknecht benutzt, nicht eine BetreiberIn, die einen
 * langen Text schreibt.
 *
 * NUR DAS KONTO, NICHT DIE ADRESSE (Muster Topf `totp` in `login.php`). Mit
 * dem zweiten Argument allein zählte `rate_merkmale()` die Adresse mit, und
 * zwei Verwaltungskonten im selben Haus teilten sich den Topf — so stand es
 * im ersten Wurf (F-P5c-155). */
$merkmale = [rate_merkmal_kennung($userEmail)];
if (!rate_erlaubt('rt_vorschau', null, $merkmale)) {
    json_out(['error' => 'gesperrt',
              'meldung' => 'Zu viele Abrufe in kurzer Zeit — in einer Minute geht es wieder.'], 429);
}
rate_zaehlen('rt_vorschau', null, $merkmale);

$b = api_rumpf(['max_bytes' => 4 * RT_MAX_ZEICHEN]);
$text  = is_string($b['text'] ?? null) ? $b['text'] : '';
$stand = is_string($b['stand'] ?? null) ? trim($b['stand']) : '';

$mangel = rt_pruefen($text, $stand);
if ($mangel !== null) {
    json_out(['error' => 'text', 'meldung' => $mangel], 422);
}

$text = str_replace(["\r\n", "\r"], "\n", $text);
json_out([
    'ok'   => true,
    'leer' => rt_leer($text),
    'html' => rt_leer($text) ? '' : rt_html($text) . rt_stand_markup($stand === '' ? null : $stand),
]);
