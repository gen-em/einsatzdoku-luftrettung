/* Die Schublade — das mobile Menü.
 * ===========================================================================
 *
 * WOFUER. Unter 1024 px liegt die Seitenleiste als Schublade von links über
 * dem Inhalt. Markup und Inhalt sind dieselben wie am Desktop; nur das
 * Stylesheet entscheidet, welche Form erscheint. Dieses Skript öffnet und
 * schliesst sie — und sonst nichts.
 *
 * ES HAENGT AN DER KLASSE, NICHT AN EINER SEITE. Die Vormerkliste aus Konzept
 * P0 (10.5) nennt genau die Falle: „Wird die Seitenleiste zur Schublade, muss
 * der Mechanismus an der KLASSE haengen und nicht an ui_days_sidebar() —
 * sonst bleibt die Suchseite als einzige ohne Mobile-Menue." Deshalb sucht
 * dieses Skript `.leiste` und `[data-schublade]`, und alle drei
 * Leisteninhalte (Diensttage, Einstellungen, Filter) bedienen sich daraus.
 *
 * DREI WEGE HINAUS, weil jeder von ihnen erwartet wird: der X-Knopf, der
 * Schleier und die Escape-Taste.
 *
 * TASTATUR. Solange die Schublade offen ist, bleibt der Fokus darin — sonst
 * wandert er hinter den Schleier in Bedienelemente, die niemand sieht. Beim
 * Schliessen kehrt er auf den Knopf zurueck, der sie geoeffnet hat.
 */
(function () {
  'use strict';

  var koerper = document.body;
  var leiste  = document.querySelector('.leiste');
  if (!leiste) { return; }

  var oeffner = null;

  function fokussierbare() {
    return Array.prototype.filter.call(
      leiste.querySelectorAll('a[href],button:not([disabled]),input,select,textarea,summary,[tabindex]:not([tabindex="-1"])'),
      function (el) { return el.offsetParent !== null; });
  }

  function offen() { return koerper.classList.contains('schublade-auf'); }

  function auf(knopf) {
    if (offen()) { return; }
    oeffner = knopf || null;
    koerper.classList.add('schublade-auf');
    var schleier = document.querySelector('.schleier');
    if (schleier) { schleier.hidden = false; }
    setzeZustand(true);
    /* Fokus auf die LEISTE (tabindex="-1"), nicht auf ihr erstes
     * Bedienelement: Sonst truege das X beim Oeffnen einen Fokusring, den
     * niemand bestellt hat (F-P3-V). Wer per Tab weitergeht, landet trotzdem
     * als Erstes auf dem X. */
    leiste.focus();
  }

  function zu() {
    if (!offen()) { return; }
    koerper.classList.remove('schublade-auf');
    setzeZustand(false);
    /* Der Schleier wird erst nach dem Ausblenden ausgehaengt — sonst
       verschwindet er hart statt zu verblassen. Ohne prefers-reduced-motion
       dauert das --dauer; die Zahl hier ist grosszuegiger als die im
       Stylesheet, damit sie auch bei langsamerem Geraet reicht. */
    var schleier = document.querySelector('.schleier');
    if (schleier) { window.setTimeout(function () {
      if (!offen()) { schleier.hidden = true; }
    }, 250); }
    if (oeffner) { oeffner.focus(); oeffner = null; }
  }

  function setzeZustand(auf) {
    Array.prototype.forEach.call(
      document.querySelectorAll('[data-schublade="auf"]'),
      function (k) { k.setAttribute('aria-expanded', auf ? 'true' : 'false'); });
  }

  document.addEventListener('click', function (ev) {
    var ziel = ev.target.closest ? ev.target.closest('[data-schublade]') : null;
    if (!ziel) { return; }
    ev.preventDefault();
    if (ziel.getAttribute('data-schublade') === 'auf') { auf(ziel); } else { zu(); }
  });

  document.addEventListener('keydown', function (ev) {
    if (!offen()) { return; }
    if (ev.key === 'Escape') { ev.preventDefault(); zu(); return; }
    if (ev.key !== 'Tab') { return; }
    var liste = fokussierbare();
    if (!liste.length) { return; }
    var erste = liste[0], letzte = liste[liste.length - 1];
    if (ev.shiftKey && document.activeElement === erste) {
      ev.preventDefault(); letzte.focus();
    } else if (!ev.shiftKey && document.activeElement === letzte) {
      ev.preventDefault(); erste.focus();
    }
  });

  /* Wird das Fenster breit genug, ist die Schublade keine mehr — der Zustand
     muss dann weg, sonst bleibt `overflow:hidden` am Koerper haengen und die
     Seite laesst sich nicht mehr scrollen. */
  var breit = window.matchMedia('(min-width: 1024px)');
  var beiWechsel = function (m) { if (m.matches) { zu(); } };
  if (breit.addEventListener) { breit.addEventListener('change', beiWechsel); }
  else if (breit.addListener) { breit.addListener(beiWechsel); }
})();
