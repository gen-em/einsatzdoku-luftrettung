/* ANKUENDIGUNG — Schliessen ohne Neuladen und der Bytezaehler
 * ==========================================================================
 *
 * Entstanden mit P5c/AP1 (E-P5c-13, -55, -60).
 *
 * 1. DAS KREUZ IM STREIFEN ist ein gewoehnliches Formular (`ankuendigung.php`,
 *    POST mit Token). Ohne dieses Skript schickt es ab und kommt auf dieselbe
 *    Seite zurueck — das geht ueberall, auch auf der Anmeldeseite. MIT dem
 *    Skript verschwindet der Streifen sofort und die Seite bleibt stehen;
 *    wer gerade ein Formular halb ausgefuellt hat, verliert nichts.
 *
 *    NUR WO `CSRF` STEHT. `EdApi` haengt das Token der Seite an, und das
 *    gibt es nur auf Seiten mit `ui_krypto_bootstrap()` oder
 *    `ui_csrf_bootstrap()`. Wo es fehlt
 *    (Anmeldeseite, die meisten Verwaltungsseiten), schickt das Formular selbst ab — ein Token
 *    von Hand hierher zu reichen hiesse, an EdApi vorbei einen zweiten
 *    Transport zu bauen (Register Z29).
 *
 * 2. DER BYTEZAEHLER im Formular der Servereinstellungen. `app_state` fasst
 *    190 Byte, nicht 190 Zeichen — „ü" sind zwei. `maxlength` zaehlt
 *    Zeichen und hilft deshalb nicht; der Satz unter dem Feld zaehlt mit,
 *    und der Server weist ab, was darueber liegt.
 *
 * Klassisches <script>, kein Modul. Zweimal eingebunden (Streifen UND
 * Formular auf derselben Seite) laeuft es einmal.
 */
(function () {
  'use strict';
  if (window.EdAnkuendigung) { return; }
  window.EdAnkuendigung = true;

  function schliessen(ev) {
    const form = ev.target;
    if (!form.matches || !form.matches('form[data-ankuendigung-weg]')) { return; }
    if (typeof CSRF !== 'string' || !window.EdApi) { return; }   // Formular schickt selbst ab
    ev.preventDefault();

    const streifen = form.closest('.meldung');
    const reihe = streifen ? streifen.parentElement : null;
    if (streifen) { streifen.remove(); }
    /* Die Reihe `.hinweise` verschwindet mit ihrem letzten Streifen — sonst
     * bliebe ihr Aussenabstand als Luecke ueber dem Inhalt stehen. */
    if (reihe && reihe.classList.contains('hinweise') && reihe.children.length === 0) {
      reihe.remove();
    }
    /* Das Ergebnis wird NICHT angezeigt: Scheitert das Merken, steht der
     * Streifen beim naechsten Seitenaufruf wieder da, und genau das ist die
     * ehrliche Auskunft darueber. Eine Fehlermeldung an dieser Stelle
     * waere lauter als der Streifen selbst. */
    window.EdApi.postForm(form.action, { antwort: 'json' });
  }

  function bytes(text) {
    return new TextEncoder().encode(text).length;
  }

  function zaehlerBinden(feld) {
    const grenze = parseInt(feld.getAttribute('data-bytegrenze'), 10);
    const ziel = document.getElementById(feld.getAttribute('data-bytezaehler'));
    if (!ziel || !(grenze > 0)) { return; }
    function zeigen() {
      /* Zeilenumbrueche zaehlen als Leerzeichen, wie der Server sie
       * speichert (`ankuendigung_setzen()`). */
      const rest = grenze - bytes(feld.value.replace(/\s+/g, ' ').trim());
      ziel.textContent = rest >= 0
        ? 'Noch ' + rest + ' von ' + grenze + ' Byte — Umlaute zählen doppelt.'
        : (-rest) + ' Byte zu viel — erlaubt sind ' + grenze + ', Umlaute zählen doppelt.';
      feld.setAttribute('aria-invalid', rest < 0 ? 'true' : 'false');
    }
    feld.addEventListener('input', zeigen);
    zeigen();
  }

  function binden() {
    document.querySelectorAll('textarea[data-bytegrenze]').forEach(zaehlerBinden);
  }

  document.addEventListener('submit', schliessen);
  /* Mit `defer` laeuft das Skript VOR DOMContentLoaded, ohne `defer` am
   * Seitenende ebenfalls — der Zweig fuer „schon geladen" ist fuer den Fall,
   * dass es jemand spaeter nachlaedt. */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', binden);
  } else {
    binden();
  }
})();
