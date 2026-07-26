/* Verlassen-Warnung und Strg-/Cmd-Enter fuer Formulare — Opt-in per Attribut.
 *
 * Verwendung:
 *   <form data-dirty-track>…</form>            Browser-Abfrage beim Verlassen
 *                                               mit ungespeicherten Aenderungen
 *   <form data-submit-on-ctrl-enter>…</form>   Strg-/Cmd-Enter sendet ab
 * (beide Attribute lassen sich auf demselben Formular kombinieren)
 *
 * Dirty-Tracking: jede Eingabe/Aenderung innerhalb des Formulars setzt ein
 * Flag; das reguläre Absenden (Submit-Ereignis) setzt es zurueck — auch bei
 * Formularen, die selbst per fetch() speichern und dabei preventDefault()
 * aufrufen, denn das Submit-Ereignis feuert in jedem Fall zuerst. Nur beim
 * Verlassen der Seite mit weiterhin gesetztem Flag erscheint die
 * Browser-Abfrage (beforeunload) — nicht beim Absenden selbst.
 *
 * Strg-Enter ersetzt in Textareas nicht das normale Enter (Zeilenumbruch
 * bleibt Enter); nur die Kombination mit Strg/Cmd loest das Absenden aus,
 * daher keine Kollision mit Enter-Sonderbehandlungen in Autocomplete-Feldern.
 */
(function () {
  'use strict';

  const dirtyForms = new Set();

  function istFormularfeld(el) {
    return el && (el.tagName === 'INPUT' || el.tagName === 'SELECT'
                || el.tagName === 'TEXTAREA');
  }

  document.addEventListener('input', ev => {
    const f = istFormularfeld(ev.target) ? ev.target.form : null;
    if (f && f.hasAttribute('data-dirty-track')) { dirtyForms.add(f); }
  });
  document.addEventListener('change', ev => {
    const f = istFormularfeld(ev.target) ? ev.target.form : null;
    if (f && f.hasAttribute('data-dirty-track')) { dirtyForms.add(f); }
  });
  document.addEventListener('submit', ev => {
    const f = ev.target;
    if (f instanceof HTMLFormElement) { dirtyForms.delete(f); }
  });

  window.addEventListener('beforeunload', ev => {
    if (dirtyForms.size === 0) { return; }
    ev.preventDefault();
    ev.returnValue = '';   // Text wird vom Browser selbst vorgegeben
  });

  document.addEventListener('keydown', ev => {
    if (ev.key !== 'Enter' || !(ev.ctrlKey || ev.metaKey)) { return; }
    const f = ev.target.closest && ev.target.closest('form[data-submit-on-ctrl-enter]');
    if (!f) { return; }
    ev.preventDefault();
    if (typeof f.requestSubmit === 'function') { f.requestSubmit(); }
    else { f.submit(); }
  });
})();
