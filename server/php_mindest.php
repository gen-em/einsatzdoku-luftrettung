<?php
/**
 * DIE UNTERGRENZE DER PHP-FASSUNG — an genau einer Stelle (P5a/AP2, E-PP-03).
 *
 * WARUM DIESE DATEI SO KLEIN IST UND SO ALT AUSSIEHT. Sie wird von der Weiche
 * am Kopf von `install.php` geladen, und die laeuft auf einer PHP-Fassung, die
 * es abzuweisen gilt. PHP uebersetzt eine Datei vollstaendig, bevor es die
 * erste Zeile ausfuehrt; alles hier drin muss deshalb **jedes PHP 5.3
 * uebersetzen koennen**. Kein `declare(strict_types=1)`, kein Rueckgabetyp,
 * kein `match`, keine Kurzschreibweise fuer Felder.
 *
 * WARUM ES SIE UEBERHAUPT GIBT. E-PP-03 verlangt, dass die Zahl an EINER
 * Stelle im Code steht und nicht in drei Meldungstexten.
 * `plattform_lib.php` — der natuerliche Ort — ist PHP-8-Code (`match`), und
 * die Weiche darf ihn deshalb nicht laden. Ohne diese Datei stuende die 8.2
 * zweimal da: einmal im Vergleich, einmal im Satz „Diese Anwendung braucht
 * PHP 8.2 oder neuer". Zwei Zahlen fuer dieselbe Aussage laufen beim naechsten
 * Anheben auseinander, und zwar in der Richtung, die am meisten kostet: Der
 * Vergleich stiege, der Satz bliebe stehen und logge jemanden mit einer
 * falschen Auskunft aus.
 *
 * DIE REGEL DAHINTER IST DAUERHAFT, DIE ZAHL IST EIN STAND: eine Fassung, die
 * vom Hersteller noch mit Sicherheitskorrekturen versorgt wird. Stand
 * 15.09.2026 ist das 8.2 — nicht, weil der Code mehr braeuchte (er kommt mit
 * 8.1 aus), sondern weil 8.1 seit Ende 2025 aus der Pflege ist.
 */

/* `defined()` davor, weil `plattform_lib.php` dieselbe Datei laedt und ein
 * doppeltes `const` ein Fatal waere. `require_once` allein genuegte hier
 * nicht: Die Weiche benutzt `require`, damit ein fehlgeschlagenes Laden
 * sofort auffaellt statt still weiterzulaufen. */
if (!defined('PLATTFORM_PHP_MIN')) {
    define('PLATTFORM_PHP_MIN', '8.2');
}
