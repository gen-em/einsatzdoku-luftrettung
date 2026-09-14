/* Der Druckknopf des Schlüsselblatts.
 * ===========================================================================
 *
 * WOFUER. `betrieb_schluesselblatt.php` ist eine Seite, die gedruckt werden
 * soll — und zwar zweimal, an zwei Orte (E-S10-10). Der Weg über das
 * Browsermenü ist da, aber er ist nicht der, den jemand geht, der die Seite
 * zum ersten Mal sieht.
 *
 * WARUM ALS EIGENE DATEI UND NICHT ALS `onclick` IM MARKUP. Ein Inline-Handler
 * waere in dieser Anwendung der einzige (nachgezaehlt am 14.09.2026:
 * `grep -c onclick= server/*.php` ergab sonst 0), und die geplante
 * Content-Security-Policy (Backlog Nr. 8, P5) verbietet ihn. Eine Zeile jetzt
 * richtig ist billiger als eine Ausnahme spaeter.
 *
 * DER KNOPF STEHT IM MARKUP AUF `hidden` UND WIRD HIER EINGEBLENDET — dasselbe
 * Muster wie bei `assets/kopieren.js`, aus demselben Grund: Ohne JavaScript
 * gaebe es sonst einen Knopf, der nichts tut. Das Blatt bleibt in jedem Fall
 * lesbar und ueber das Browsermenue druckbar; dieser Knopf ist Bequemlichkeit,
 * kein Zugang.
 */
'use strict';
(() => {
  const knopf = document.querySelector('[data-drucken]');
  if (!knopf || typeof window.print !== 'function') { return; }
  knopf.hidden = false;
  knopf.addEventListener('click', () => { window.print(); });
})();
