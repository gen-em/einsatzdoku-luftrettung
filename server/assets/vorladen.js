/**
 * Eine Anfrage früh starten — noch während die Seite ihre Skripte lädt.
 *
 * WOFÜR. Einsatz und Diensttag holen ihre Daten aus `api/`. Ohne diese Datei
 * beginnt die Anfrage erst, wenn alle Skripte der Seite geladen und
 * ausgeführt sind; gemessen lag dazwischen bei Drossel 6× rund ein
 * Sechstel des Seitenaufbaus. Diese Datei steht im Kopf der Seite, liest die
 * Adresse aus `data-vorladen` ihres eigenen `<script>` und startet die
 * Anfrage sofort. Die Seite holt sie mit `EdVorladen.holen(url)` ab — und
 * bekommt dieselbe Antwort, die sie sonst selbst geholt hätte, mit derselben
 * Fehlerbehandlung an derselben Stelle.
 *
 * EINE EIGENE DATEI UND KEIN INLINE-SKRIPT: Die CSP lässt Inline-Code nur
 * mit Nonce zu, und `ui_seite_start()` gibt dem Kopf keinen.
 *
 * Jede vorgeladene Antwort wird genau EINMAL abgegeben (ein Antwortrumpf
 * lässt sich nur einmal lesen); jeder weitere Aufruf fragt neu.
 */
(function () {
  'use strict';
  /* WIEDERHOLBAR: Eine Seite kann die Datei im Kopf (mit Adresse) und im
   * Rumpf (ohne) einbinden. Der zweite Lauf ergänzt, statt das schon
   * gestartete Ergebnis zu überschreiben. */
  const V = window.EdVorladen || (function () {
    const offen = {};
    return {
      starten(u) {
        if (!Object.prototype.hasOwnProperty.call(offen, u)) {
          offen[u] = fetch(u, { credentials: 'same-origin' });
        }
      },
      holen(u) {
        if (Object.prototype.hasOwnProperty.call(offen, u)) {
          const p = offen[u];
          delete offen[u];
          return p;
        }
        return fetch(u, { credentials: 'same-origin' });
      },
    };
  })();
  window.EdVorladen = V;
  const s = document.currentScript;
  const url = s && s.dataset ? s.dataset.vorladen : '';
  if (url) { V.starten(url); }
})();
