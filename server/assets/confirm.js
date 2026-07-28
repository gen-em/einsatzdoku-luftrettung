/* Bestätigungsdialoge im Seiteninhalt.
 *
 * Ersetzt window.confirm(): Browser bieten bei nativen Dialogen an, „keine
 * weiteren Dialoge dieser Seite anzeigen" — danach verschwinden alle
 * Rückfragen stillschweigend und Löschungen liefen ohne Nachfrage durch.
 * Ein Dialog im Seiteninhalt lässt sich nicht abschalten.
 *
 * Verwendung:
 *   <form data-confirm="Wirklich löschen?">…</form>
 *   <a href="…" data-confirm="Wirklich abmelden?" data-confirm-ok="Abmelden">…</a>
 * Optional:
 *   data-confirm-ok    Beschriftung des Bestätigungsknopfes (Standard: „Löschen")
 *   data-confirm-tone  "danger" (Standard) oder "normal"
 */
(function () {
  'use strict';

  /* Je Aufruf ein EIGENES <dialog>. Vorher gab es ein einziges,
   * wiederverwendetes Element — das ist bei zwei aufeinanderfolgenden
   * Rückfragen stillschweigend schiefgegangen:
   *
   *   1. Dialog 1, Klick auf OK -> done() entfernt den eigenen
   *      close-Zuhörer und ruft d.close(). Das close-Ereignis wird dabei nur
   *      EINGEREIHT, es feuert nicht sofort.
   *   2. Das Versprechen löst auf, der Aufrufer macht weiter und öffnet
   *      Dialog 2 — der seinen eigenen close-Zuhörer anmeldet.
   *   3. Jetzt erst wird das alte Ereignis aus Schritt 1 zugestellt und
   *      landet beim Zuhörer von Dialog 2: done(false). Dialog 2 schließt
   *      sich sofort selbst und meldet „abgebrochen".
   *
   * Der Aufrufer sah daraufhin einen stillen Abbruch ohne jede Meldung. Ein
   * eigenes Element je Aufruf schließt diesen ganzen Fall aus: Ein
   * nachlaufendes Ereignis kann keinen späteren Aufruf mehr erreichen.
   */
  function build() {
    const dlg = document.createElement('dialog');
    dlg.className = 'confirmbox';
    dlg.innerHTML =
      '<p class="confirmtext"></p>' +
      '<div class="confirmbtns">' +
      '  <button type="button" class="btn-plain" data-act="no">Abbrechen</button>' +
      '  <button type="button" class="btn-red" data-act="yes">Löschen</button>' +
      '</div>';
    document.body.appendChild(dlg);
    return dlg;
  }

  /** Zeigt die Rückfrage; liefert ein Promise mit true/false. */
  function ask(text, okLabel, tone) {
    // Sehr alte Browser ohne <dialog>: lieber nativ fragen als gar nicht.
    if (typeof HTMLDialogElement === 'undefined') {
      return Promise.resolve(window.confirm(text));
    }
    const d = build();
    d.querySelector('.confirmtext').textContent = text;
    const ok = d.querySelector('[data-act="yes"]');
    ok.textContent = okLabel || 'Löschen';
    ok.className = (tone === 'normal') ? 'btn-primary' : 'btn-red';

    return new Promise(resolve => {
      let erledigt = false;
      function done(v) {
        // Zweite Schutzschicht gegen Doppelklicks und nachlaufende Ereignisse.
        if (erledigt) { return; }
        erledigt = true;
        if (d.open) { d.close(); }
        d.remove();
        resolve(v);
      }
      d.addEventListener('close', () => done(false));   // Escape-Taste
      ok.onclick = () => done(true);
      d.querySelector('[data-act="no"]').onclick = () => done(false);
      d.showModal();
      d.querySelector('[data-act="no"]').focus();   // Abbrechen vorausgewählt
    });
  }

  // Formulare abfangen
  document.addEventListener('submit', ev => {
    const f = ev.target;
    if (!(f instanceof HTMLFormElement)) return;
    const text = f.getAttribute('data-confirm');
    if (!text || f.dataset.confirmed === '1') return;
    ev.preventDefault();
    ev.stopPropagation();
    ask(text, f.getAttribute('data-confirm-ok'), f.getAttribute('data-confirm-tone'))
      .then(ja => {
        if (!ja) return;
        f.dataset.confirmed = '1';
        f.submit();                 // löst kein erneutes submit-Ereignis aus
      });
  }, true);

  // Links abfangen
  document.addEventListener('click', ev => {
    const a = ev.target.closest && ev.target.closest('a[data-confirm]');
    if (!a) return;
    ev.preventDefault();
    ev.stopPropagation();
    ask(a.getAttribute('data-confirm'), a.getAttribute('data-confirm-ok'),
        a.getAttribute('data-confirm-tone'))
      .then(ja => { if (ja) location.href = a.href; });
  }, true);

  window.edConfirm = ask;      // für eigene Aufrufe
})();
