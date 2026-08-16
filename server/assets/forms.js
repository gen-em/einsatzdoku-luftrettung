/* Verlassen-Warnung, Strg-/Cmd-Enter und Abbrechen — Opt-in per Attribut.
 *
 * Verwendung:
 *   <form data-dirty-track>…</form>            Browser-Abfrage beim Verlassen
 *                                               mit ungespeicherten Aenderungen
 *   <form data-submit-on-ctrl-enter>…</form>   Strg-/Cmd-Enter sendet ab
 *   <a href="…" data-cancel-form="meinform">Abbrechen</a>
 *                                              Rueckfrage NUR, wenn im
 *                                              genannten Formular tatsaechlich
 *                                              etwas eingegeben wurde
 * (die Formular-Attribute lassen sich auf demselben Formular kombinieren)
 *
 * Abbrechen (A4.1, Web 5.5.0): Ein leeres Formular ohne Nachfrage zu verlassen
 * ist richtig — eine Rueckfrage, die immer kommt, wird weggeklickt und schuetzt
 * dann auch dort nicht mehr, wo etwas zu verlieren waere. Woran „etwas
 * eingegeben" erkannt wird, ist genau das Kennzeichen, das die Verlassen-
 * Warnung ohnehin fuehrt; eine zweite Quelle dafuer koennte abweichen.
 *
 * Die Rueckfrage laeuft ueber assets/confirm.js (window.edConfirm). Dessen
 * eigener Weg — data-confirm am Verweis — passt hier nicht: Er fragt
 * bedingungslos.
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

  /* Abbrechen-Verweise: nur fragen, wenn es etwas zu verlieren gibt.
   *
   * In der Erfassungsphase (Capture) angemeldet, damit die Entscheidung vor
   * allen anderen Klick-Zuhoerern faellt — dieselbe Reihenfolge, die
   * confirm.js fuer data-confirm benutzt. Trifft nur Verweise mit
   * data-cancel-form; alle uebrigen Klicks laufen unberuehrt weiter. */
  document.addEventListener('click', ev => {
    const a = ev.target.closest && ev.target.closest('a[data-cancel-form]');
    if (!a) { return; }
    const f = document.getElementById(a.dataset.cancelForm);
    // Kein Formular gefunden oder nichts eingegeben: dem Verweis normal folgen.
    if (!f || !dirtyForms.has(f)) { return; }
    ev.preventDefault();
    ev.stopPropagation();

    const frage = a.dataset.cancelConfirm
      || 'Die Eingaben auf dieser Seite gehen verloren. Trotzdem abbrechen?';
    const fragen = (typeof window.edConfirm === 'function')
      ? window.edConfirm(frage, 'Verwerfen', 'danger')
      : Promise.resolve(window.confirm(frage));
    fragen.then(ja => {
      if (!ja) { return; }
      /* Kennzeichen loeschen, BEVOR navigiert wird: Sonst kaeme unmittelbar
       * nach der eigenen Rueckfrage noch die des Browsers (beforeunload) —
       * zweimal dasselbe fragen heisst, die erste Frage nicht ernst zu
       * nehmen. */
      dirtyForms.delete(f);
      location.href = a.href;
    });
  }, true);

  /* Nach aussen: Wer eigene Abbruchwege baut, soll dasselbe Kennzeichen
   * lesen und loeschen koennen statt ein zweites zu fuehren. */
  window.EdForms = {
    istGeaendert(f) { return dirtyForms.has(f); },
    vergessen(f)    { dirtyForms.delete(f); },
  };
})();
