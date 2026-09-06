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
  /* Je Formular die Karten, in denen etwas geaendert wurde — fuer den
     Hinweistext der Leiste (S8/AP3, Mockup 09). */
  const schmutzigeKarten = new WeakMap();

  function istFormularfeld(el) {
    return el && (el.tagName === 'INPUT' || el.tagName === 'SELECT'
                || el.tagName === 'TEXTAREA');
  }

  /* Die Speichern-Leiste des Formulars (ui_speichern_leiste, E-P3-29) haengt
   * am selben Kennzeichen: Sie erscheint mit der ersten Aenderung und
   * verschwindet mit dem Absenden. Ein Formular ohne Leiste bleibt davon
   * unberuehrt. */
  function leisteZeigen(f, an) {
    const l = f.querySelector('[data-speichern]');
    if (l) { l.hidden = !an; }
  }

  /* DIE LEISTE NENNT, WAS UNGESPEICHERT IST (S8/AP3, Mockup 09).
   *
   * „Es gibt ungespeicherte Änderungen" beantwortet die Frage nicht, die man
   * auf einer Seite mit drei Karten hat: WELCHE? Auf „Installation" stehen
   * Impressum und Datenschutz in einem Formular, und wer nach zehn Minuten
   * wiederkommt, weiss nicht mehr, woran er war.
   *
   * Der Text entsteht aus den Kartentiteln der geaenderten Felder. Traegt der
   * Hinweis ein `data-hinweis-vorlage`, wird daraus „<Vorlage>: A und B";
   * ohne Vorlage bleibt der Text im Markup stehen. Findet sich keine Karte
   * (ein Formular ohne Karten), bleibt es ebenfalls beim Ausgangstext — ein
   * Doppelpunkt ohne Aufzaehlung waere schlechter als der allgemeine Satz. */
  function hinweisSetzen(f) {
    const l = f.querySelector('[data-speichern]');
    if (!l) { return; }
    const p = l.querySelector('.speichern-hinweis');
    if (!p || !p.hasAttribute('data-hinweis-vorlage')) { return; }
    const namen = Array.from(schmutzigeKarten.get(f) || []);
    if (!namen.length) { return; }
    const liste = namen.length === 1 ? namen[0]
                : namen.slice(0, -1).join(', ') + ' und ' + namen[namen.length - 1];
    p.textContent = p.getAttribute('data-hinweis-vorlage') + ': ' + liste;
  }

  /* Der Titel der Karte, in der dieses Feld steht — oder nichts. */
  function kartenTitel(el) {
    const k = el.closest('.karte');
    if (!k) { return null; }
    const t = k.querySelector('.karte-titel');
    return t ? t.textContent.trim() : null;
  }

  function merken(f, feld) {
    if (!f || !f.hasAttribute('data-dirty-track')) { return; }
    dirtyForms.add(f);
    if (feld) {
      const titel = kartenTitel(feld);
      if (titel) {
        if (!schmutzigeKarten.has(f)) { schmutzigeKarten.set(f, new Set()); }
        schmutzigeKarten.get(f).add(titel);
      }
    }
    leisteZeigen(f, true);
    hinweisSetzen(f);
  }

  document.addEventListener('input', ev => {
    if (istFormularfeld(ev.target)) { merken(ev.target.form, ev.target); }
  });
  document.addEventListener('change', ev => {
    if (istFormularfeld(ev.target)) { merken(ev.target.form, ev.target); }
  });
  document.addEventListener('submit', ev => {
    const f = ev.target;
    if (f instanceof HTMLFormElement) {
      dirtyForms.delete(f); schmutzigeKarten.delete(f); leisteZeigen(f, false);
    }
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
    /* Fuer Aenderungen, die kein Feld-Ereignis feuern (eine entfernte
     * Phasenzeile): dasselbe Kennzeichen von aussen setzen. */
    markieren(f)    { if (f) { dirtyForms.add(f); leisteZeigen(f, true); } },
  };
})();
