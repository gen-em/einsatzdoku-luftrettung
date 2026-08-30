/* Das Blatt — Aktionsmenü, Sortieren, Zeilenaktionen.
 * ===========================================================================
 *
 * WOFUER. Mobil ein „⋯", das ein Blatt von unten öffnet: Griff, Titel, grosse
 * Zeilen, „Löschen" rot und abgesetzt, „Abbrechen". Am Desktop derselbe
 * Vorrat als Aufklappmenü unter dem Knopf „Aktionen".
 *
 * EIN MARKUP, ZWEI FORMEN — und dieses Skript entscheidet nicht, welche.
 * Es öffnet und schliesst; ob daraus ein Blatt oder ein Aufklappmenü wird,
 * sagt das Stylesheet (Abschnitt 10 und 18). Das ist der Grund, warum es so
 * kurz ist: Jede Zeile, die hier eine Fensterbreite abfragte, waere eine
 * zweite Stelle, an der die Schwelle 1024 steht.
 *
 * IMMER NUR EINES OFFEN. Zwei offene Menues uebereinander sind kein Zustand,
 * den jemand herbeifuehren will.
 */
(function () {
  'use strict';

  var offenes = null;
  var offenerKnopf = null;

  function zu() {
    if (!offenes) { return; }
    offenes.hidden = true;
    if (offenerKnopf) {
      offenerKnopf.setAttribute('aria-expanded', 'false');
      offenerKnopf.focus();
    }
    offenes = null; offenerKnopf = null;
  }

  function auf(knopf) {
    var id = knopf.getAttribute('data-blatt');
    var blatt = id && document.getElementById(id);
    if (!blatt) { return; }
    if (offenes === blatt) { zu(); return; }
    zu();
    blatt.hidden = false;
    knopf.setAttribute('aria-expanded', 'true');
    offenes = blatt; offenerKnopf = knopf;
    var erste = blatt.querySelector('a[href],button:not([disabled])');
    if (erste) { erste.focus(); }
  }

  document.addEventListener('click', function (ev) {
    var ziel = ev.target.closest ? ev.target.closest('[data-blatt]') : null;
    if (ziel) { ev.preventDefault(); auf(ziel); return; }
    if (ev.target.closest && ev.target.closest('[data-blatt-zu]')) {
      ev.preventDefault(); zu(); return;
    }
    /* Ein Klick daneben schliesst — aber nicht der Klick auf einen Eintrag im
       Blatt selbst, der ja gerade seinen Weg gehen soll. */
    if (offenes && !offenes.contains(ev.target)) { zu(); }
  });

  document.addEventListener('keydown', function (ev) {
    if (offenes && ev.key === 'Escape') { ev.preventDefault(); zu(); }
  });

  /* Kleine Schliess-API fuer Seiten, die nach einer Wahl im Blatt selbst
   * schliessen wollen (Sortierblatt: Wahl getroffen -> Blatt zu). Mehr gibt
   * es absichtlich nicht — oeffnen laeuft ueber data-blatt. */
  window.edBlatt = { zu: zu };
})();
