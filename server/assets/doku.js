/**
 * Das Inhaltsverzeichnis der Dokumentseite (P5b/AP8, M-P5b-01).
 *
 * Zwei Dinge, mehr nicht: Das Suchfeld filtert die Liste, und der Abschnitt,
 * den man gerade liest, wird hervorgehoben.
 *
 * KEIN INLINE-SKRIPT. Die Seite ist oeffentlich und laeuft unter derselben
 * Inhaltssicherheitsrichtlinie wie der Rest; ein `onclick` im Markup waere
 * hier so falsch wie ueberall sonst.
 *
 * OHNE DIESE DATEI BLEIBT DIE SEITE BENUTZBAR. Das Verzeichnis ist eine Liste
 * echter Links auf echte Sprungmarken — die funktionieren ohne eine Zeile
 * JavaScript. Was fehlt, ist das Filtern und die Hervorhebung. Das ist der
 * Grund, warum beides HIER steht und nicht im Markup: Die Seite muss auch
 * dann etwas taugen, wenn das Skript nicht laedt.
 */
(function () {
  'use strict';

  var nav = document.querySelector('.doku-nav');
  if (!nav) { return; }

  var eintraege = Array.prototype.slice.call(nav.querySelectorAll('.doku-e'));
  if (!eintraege.length) { return; }

  /* ---- 1. Filtern ------------------------------------------------------ */

  var feld = document.getElementById('doku-filter');
  var leer = nav.querySelector('.doku-leer');

  /* KLEINSCHREIBUNG UND UMLAUTE. Wer „ubersicht" tippt, meint „Übersicht" —
   * ein Vergleich auf die rohen Zeichen fände sie nicht. `normalize('NFD')`
   * zerlegt „ü" in „u" und einen kombinierenden Punkt, den der zweite Schritt
   * wegwirft. Das ist absichtlich grober als `doku_marke()` auf der
   * Serverseite: Dort geht es um eindeutige Adressen, hier um Finden. */
  function flach(s) {
    return s.toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/ß/g, 'ss');
  }

  eintraege.forEach(function (li) {
    li.dataset.such = flach(li.textContent || '');
  });

  function filtern() {
    var q = flach((feld.value || '').trim());
    var sichtbar = 0;

    eintraege.forEach(function (li) {
      var passt = q === '' || li.dataset.such.indexOf(q) !== -1;
      li.hidden = !passt;
      if (passt) { sichtbar++; }
    });

    if (leer) { leer.hidden = sichtbar > 0; }
  }

  if (feld) {
    feld.addEventListener('input', filtern);
    /* EIN GEFUELLTES FELD NACH DEM ZURUECK-KNOPF. Firefox stellt den Inhalt
     * eines Suchfelds beim Zurueckgehen wieder her, die Liste aber nicht —
     * dann stuende ein Filter im Feld, der nichts filtert. */
    filtern();
  }

  /* ---- 2. Der Abschnitt, den man gerade liest -------------------------- */

  /* WARUM EIN OBSERVER UND KEIN `scroll`-ZAEHLER: Ein Scroll-Handler laeuft
   * bei jedem Pixel und muesste die Position von 67 Ueberschriften messen —
   * auf einem Handy ist das der Unterschied zwischen fluessig und zaeh.
   * `IntersectionObserver` meldet nur, wenn sich etwas aendert. */
  if (!('IntersectionObserver' in window)) { return; }

  var ziele = {};
  eintraege.forEach(function (li) {
    var a = li.querySelector('a');
    if (!a) { return; }
    var marke = a.getAttribute('href') || '';
    if (marke.charAt(0) === '#') { ziele[marke.slice(1)] = li; }
  });

  var ueberschriften = Object.keys(ziele)
    .map(function (id) { return document.getElementById(id); })
    .filter(Boolean);
  if (!ueberschriften.length) { return; }

  var aktiv = null;
  function setzen(li) {
    if (li === aktiv) { return; }
    if (aktiv) { aktiv.classList.remove('aktiv'); }
    aktiv = li;
    if (aktiv) { aktiv.classList.add('aktiv'); }
  }

  /* DER STREIFEN OBEN. `rootMargin` schneidet den Bildschirm auf einen
   * schmalen Streifen im oberen Drittel zu: Aktiv ist die Ueberschrift, die
   * dort gerade steht — nicht die, die irgendwo am unteren Rand auftaucht.
   * Ohne den Zuschnitt waere bei einem langen Abschnitt gar keine sichtbar
   * und die Hervorhebung spraenge zurueck auf nichts. */
  var beobachter = new IntersectionObserver(function (eintraegeIO) {
    eintraegeIO.forEach(function (e) {
      if (e.isIntersecting) { setzen(ziele[e.target.id] || null); }
    });
  }, { rootMargin: '-80px 0px -70% 0px', threshold: 0 });

  ueberschriften.forEach(function (h) { beobachter.observe(h); });
})();
