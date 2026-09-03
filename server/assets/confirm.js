/* Bestätigungsdialoge im Seiteninhalt.
 *
 * Ersetzt window.confirm(): Browser bieten bei nativen Dialogen an, „keine
 * weiteren Dialoge dieser Seite anzeigen" — danach verschwinden alle
 * Rückfragen stillschweigend und Löschungen liefen ohne Nachfrage durch.
 * Ein Dialog im Seiteninhalt lässt sich nicht abschalten.
 *
 * Verwendung — drei Träger, je nach dem, was gefragt werden soll:
 *   <form data-confirm="Wirklich löschen?">…</form>
 *   <a href="…" data-confirm="Wirklich abmelden?" data-confirm-ok="Abmelden">…</a>
 *   <button name="action" value="…" data-confirm="…">…</button>
 * Optional:
 *   data-confirm-ok    Beschriftung des Bestätigungsknopfes (Standard: „Löschen")
 *   data-confirm-tone  "danger" (Standard) oder "normal"
 *   data-confirm-titel Überschrift des Dialogs (Standard: „Bestätigen")
 *
 * WANN AN DEN KNOPF UND NICHT AN DAS FORMULAR: Wenn EIN Formular mehrere
 * Absendeknöpfe hat, die verschiedene Dinge tun. Am Formular hinge dann eine
 * Frage für alle — also für jeden einzelnen die falsche.
 */
(function () {
  'use strict';

  /* NUR EINMAL WIRKSAM, auch wenn die Datei zweimal eingebunden wird.
   *
   * Diese Datei ist eine IIFE ohne eigenen Namensraum: Eine zweite Einbindung
   * ist kein Fehler, sie meldet nur ein zweites Mal dieselben Zuhoerer an —
   * und dann oeffnen zwei Dialoge uebereinander. Bis Web 7.1.0 war das auf
   * drei Seiten der Fall (admin_sicherungen, admin_stammdaten,
   * nachbearbeitung), weil sie confirm.js zusaetzlich zu ui_footer() selbst
   * einbanden. Die Zeilen sind entfernt; diese Schranke sorgt dafuer, dass es
   * beim naechsten Mal nicht wieder auffaellt, sondern gar nicht erst
   * passiert. */
  if (window.edConfirm) { return; }

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
  /* Markup und Klassen sind die des Dialog-Bausteins (.dialog, .knopf) aus
   * P3/O2 — dieselben, die ui.php serverseitig benutzt. Die frueheren eigenen
   * Klassen (.confirmbox, .confirmbtns, .btn-red, .btn-plain) stehen auf der
   * Streichliste; wer hier eine aendert, aendert sie im Stylesheet-Abschnitt
   * 15 mit. */
  /* DER DIALOG HAT EINEN NAMEN (O11).
   *
   * Bis Web 9.11.0 hatte er keinen: kein Titel, kein `aria-label`, kein
   * `role`. Ausgerechnet die Rueckfrage vor dem Loeschen war damit die
   * anonymste Stelle der Oberflaeche — ein Screenreader las den Text und
   * zwei Knoepfe, ohne zu sagen, WAS da fragt.
   *
   * `alertdialog` statt `dialog`: Die Rolle ist fuer genau diesen Fall da —
   * eine Meldung, die eine Antwort verlangt und den Ablauf anhaelt. Sie
   * bewirkt, dass der Text beim Oeffnen vorgelesen wird, und nicht erst,
   * wenn der Fokus ihn erreicht.
   *
   * Der Titel ist ueberschreibbar (`data-confirm-titel`), denn „Bestaetigen"
   * ist richtig, aber allgemein; wo eine Seite es genauer sagen kann, soll
   * sie es tun. Der unlock-Dialog macht es seit jeher so
   * (assets/unlock.js: „Geschuetzte Angaben entsperren"). */
  function build(titel) {
    const dlg = document.createElement('dialog');
    dlg.className = 'dialog';
    dlg.setAttribute('role', 'alertdialog');
    dlg.innerHTML =
      '<div class="dialog-kopf"><h2 data-titel></h2></div>' +
      '<div class="dialog-inhalt"><p data-text></p></div>' +
      '<div class="dialog-fuss">' +
      '  <button type="button" class="knopf knopf-leise" data-act="no">Abbrechen</button>' +
      '  <button type="button" class="knopf knopf-gefahr" data-act="yes">Löschen</button>' +
      '</div>';
    dlg.querySelector('[data-titel]').textContent = titel || 'Bestätigen';
    document.body.appendChild(dlg);
    return dlg;
  }

  /** Zeigt die Rückfrage; liefert ein Promise mit true/false. */
  function ask(text, okLabel, tone, titel) {
    // Sehr alte Browser ohne <dialog>: lieber nativ fragen als gar nicht.
    if (typeof HTMLDialogElement === 'undefined') {
      return Promise.resolve(window.confirm(text));
    }
    const d = build(titel);
    d.querySelector('[data-text]').textContent = text;
    const ok = d.querySelector('[data-act="yes"]');
    ok.textContent = okLabel || 'Löschen';
    ok.className = (tone === 'normal') ? 'knopf knopf-primaer' : 'knopf knopf-gefahr';

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
    ask(text, f.getAttribute('data-confirm-ok'), f.getAttribute('data-confirm-tone'),
        f.getAttribute('data-confirm-titel'))
      .then(ja => {
        if (!ja) return;
        f.dataset.confirmed = '1';
        /* DEM DIRTY-TRACKING BESCHEID SAGEN (O11).
         *
         * Traegt ein Formular BEIDES — `data-confirm` und `data-dirty-track`
         * —, fragt der Browser nach der bestaetigten Rueckfrage ein zweites
         * Mal: „Aenderungen werden moeglicherweise nicht gespeichert".
         *
         * Der Grund steht drei Zeilen weiter oben: `stopPropagation()` in der
         * ERFASSUNGSPHASE. Der Zuhoerer von forms.js haengt in der
         * Blasenphase am selben `document` und laeuft deshalb nie; danach
         * sendet `f.submit()` ab, was gar kein submit-Ereignis ausloest. Das
         * Formular bleibt also fuer forms.js bis zuletzt „schmutzig", und
         * beim Verlassen der Seite feuert dessen beforeunload-Abfrage.
         *
         * Genau das, was forms.js fuer den Abbrechen-Weg ausdruecklich
         * verhindert: „zweimal dasselbe fragen heisst, die erste Frage nicht
         * ernst zu nehmen". Betroffen war diensttag_datum.php — die einzige
         * Stelle mit beiden Attributen, und dort praktisch immer, weil man
         * das Feld aendern MUSS, um etwas zu tun. */
        if (window.EdForms && window.EdForms.vergessen) { window.EdForms.vergessen(f); }
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
        a.getAttribute('data-confirm-tone'), a.getAttribute('data-confirm-titel'))
      .then(ja => { if (ja) location.href = a.href; });
  }, true);

  /* Absendeknöpfe abfangen.
   *
   * DIESER ZWEIG HAT BIS WEB 7.1.0 GEFEHLT, und das war nicht folgenlos:
   * admin_sicherungen.php trug die Rückfrage an drei <button> — „Alle
   * sichern", „Einspielen" und „Für NutzerIn freigeben". Gebunden wurde nur
   * an <form> und an <a>; die drei Dialoge erschienen also NIE. Dass die
   * Attribute dastanden, sah nach Absicherung aus und war keine — ausgerechnet
   * vor dem Einspielen eines fremden Backups in ein Konto.
   *
   * WARUM DER KNOPF ERNEUT GEKLICKT WIRD und nicht f.submit() gerufen: Nur der
   * tatsächlich betätigte Absendeknopf schickt sein name/value mit. Die drei
   * Knöpfe unterscheiden sich genau darin (name="action" value="einspielen"
   * bzw. "freigeben") — ein f.submit() ließe die Angabe weg, und der Server
   * bekäme eine Anfrage ohne Auftrag. Der zweite Klick läuft durch, weil
   * dataset.confirmed dann gesetzt ist, und nimmt den üblichen Weg samt
   * Formularprüfung.
   */
  document.addEventListener('click', ev => {
    const b = ev.target.closest && ev.target.closest('button[data-confirm]');
    if (!b || b.dataset.confirmed === '1') return;
    ev.preventDefault();
    ev.stopPropagation();
    ask(b.getAttribute('data-confirm'), b.getAttribute('data-confirm-ok'),
        b.getAttribute('data-confirm-tone'), b.getAttribute('data-confirm-titel'))
      .then(ja => {
        if (!ja) return;
        b.dataset.confirmed = '1';
        b.click();
      });
  }, true);

  window.edConfirm = ask;      // für eigene Aufrufe
})();
