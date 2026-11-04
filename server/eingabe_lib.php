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
