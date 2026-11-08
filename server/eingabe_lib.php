<?php
declare(strict_types=1);

/**
 * EIN FORMULARFELD ALS TEXT LESEN — die eine Stelle (Backlog Nr. 355, R83).
 * ===========================================================================
 *
 * WOGEGEN. `(string)($_POST['code'] ?? '')` stand an 125 Stellen, dazu 25-mal
 * dasselbe fuer `$_GET`. Schickt jemand `code[]=x` statt `code=x`, macht der
 * Cast aus der Liste den Text „Array" — mit der Warnung „Array to string
 * conversion", die der Behandler in den Reiter System schreibt, eine Zeile je
 * Stelle und Anfrage. Wo die Liste ohne Cast an eine Funktion mit `string`
 * ging (`email_normalisieren()` in `login.php`, bis Web 21.14.0 auch in
 * `registrieren.php`), war es unter `strict_types` ein TypeError, also
 * eine 500. Zwei Seiten hatten sich dafuer schon eine lokale Huelle `$feld`
 * gebaut; beide gehen hier auf.
 *
 * WAS ES LIEFERT: den Text, wenn das Feld ein Text ist — sonst die Vorgabe,
 * ob es fehlt oder eine Liste ist. Keine Warnung, kein Eintrag im Reiter
 * System. Ein leeres Pflichtfeld meldet die Seite ohnehin selbst; aus einer
 * Liste wird so derselbe Formularfehler wie aus einem leeren Feld.
 *
 * WARUM EINE EIGENE DATEI, die nichts laedt: `install.php` laeuft ohne
 * `config.php` und damit ohne `db.php`. `db.php` bindet diese Datei ein, damit
 * jede andere Seite sie ohne eigenes `require` findet.
 *
 * DAS REGISTER ZAEHLT DIE ALTEN FORMEN (`tools/zaehlung/register.php`, Z47
 * und Z48): ein `(string)` vor `$_POST[…]` oder `$_GET[…]`, und ein
 * `is_string($_POST[…])` ausserhalb dieser Datei.
 */

/** Ein Feld aus `$_POST` als Text, sonst `$vorgabe`. */
function post_text(string $schluessel, string $vorgabe = ''): string
{
    $v = $_POST[$schluessel] ?? null;
    return is_string($v) ? $v : $vorgabe;
}

/** Ein Feld aus `$_GET` als Text, sonst `$vorgabe`. */
function get_text(string $schluessel, string $vorgabe = ''): string
{
    $v = $_GET[$schluessel] ?? null;
    return is_string($v) ? $v : $vorgabe;
}


/* ---------------------------------------------------------------------------
 * EIN DATUM LESEN — `TT.MM.JJJJ` oder `JJJJ-MM-TT` (Konzept OF, OF-03,
 * E-OF-06, -44, -54).
 *
 * WARUM HIER. Die Datumsfelder sind seit Web 24.4 Textfelder mit der Maske
 * `TT.MM.JJJJ` statt `<input type="date">`, das je nach Browser
 * „dd/mm/yyyy" zeigte und einen Kalender aufklappte. Gespeichert und geprueft
 * wird weiter `JJJJ-MM-TT`; die Umrechnung steht EINMAL, hier. Das Register
 * (`tools/zaehlung/register.php`) zaehlt `createFromFormat('d.m.Y'…)`
 * ausserhalb dieser Datei.
 *
 * BEIDE FORMEN GELTEN: Eine Probe, ein altes Lesezeichen oder ein Aufruf ohne
 * Skript schickt ISO, das Feld schickt die deutsche Form. Ein Tag, den es
 * nicht gibt (31.02.), ist keiner.
 * ------------------------------------------------------------------------ */

/** 'TT.MM.JJJJ' (auch 'T.M.JJJJ') oder 'JJJJ-MM-TT' als 'JJJJ-MM-TT', sonst null. */
function datum_de_lesen(string $text): ?string
{
    $t = trim($text);
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $t, $m)) {
        [$tag, $monat, $jahr] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $t, $m)) {
        [$jahr, $monat, $tag] = [(int)$m[1], (int)$m[2], (int)$m[3]];
    } else {
        return null;
    }
    if (!checkdate($monat, $tag, $jahr)) { return null; }
    return sprintf('%04d-%02d-%02d', $jahr, $monat, $tag);
}

/**
 * Ein Datumsfeld aus `$_POST`: als 'JJJJ-MM-TT', wenn es ein Tag ist — sonst
 * der Text unveraendert (getrimmt). So meldet die Pruefung der Seite
 * (`pruef_kalendertag()` und andere) einen falschen Wert weiter mit ihrem
 * eigenen Satz, statt dass er hier still zu leer wird.
 */
function post_datum(string $schluessel, string $vorgabe = ''): string
{
    $t = post_text($schluessel, $vorgabe);
    return datum_de_lesen($t) ?? trim($t);
}

/** Dasselbe fuer `$_GET`. */
function get_datum(string $schluessel, string $vorgabe = ''): string
{
    $t = get_text($schluessel, $vorgabe);
    return datum_de_lesen($t) ?? trim($t);
}

