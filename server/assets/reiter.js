/* Reiter — der aktive ins Bild, der Rand verrät das Weiterrollen.
 * ===========================================================================
 *
 * Entstanden mit P5c/AP2 (E-P5c-25, Bild M-P5c-01a). Der Baustein selbst
 * braucht kein Skript: Jeder Reiter ist ein Verweis, der Server zeichnet den
 * aktiven (`ui_reiter()`). Dieses Skript tut zwei Dinge, die nur der Browser
 * weiß:
 *
 * 1. DER AKTIVE REITER KOMMT INS BILD. Unter 720 px rollt die Reihe in ihrem
 *    eigenen Behälter; wer auf „System" (dem siebten) steht und die Seite neu
 *    lädt, sähe sonst die ersten drei und keinen markierten. Gerollt wird nur
 *    die Reihe, nie die Seite (`scrollLeft`, nicht `scrollIntoView`, das
 *    auch die Seite verschöbe).
 *
 * 2. DER RAND ZEIGT, DASS ES WEITERGEHT. `.rollt` (rechts ist mehr) und
 *    `.rollt-links` (links ist mehr) setzen einen Verlauf auf
 *    `.reiter-rahmen`. Eine Reihe, die abgeschnitten aussieht wie zu Ende,
 *    versteckt ihre letzten Reiter.
 *
 * Ohne Skript bleibt die Reihe vollständig bedienbar: Sie rollt, nur ohne
 * Verlauf und beginnt links.
 */
(function () {
  'use strict';

  function rand(rahmen, reihe) {
    var rest = reihe.scrollWidth - reihe.clientWidth - reihe.scrollLeft;
    rahmen.classList.toggle('rollt', rest > 1);
    rahmen.classList.toggle('rollt-links', reihe.scrollLeft > 1);
  }

  Array.prototype.forEach.call(document.querySelectorAll('.reiter-rahmen'), function (rahmen) {
    var reihe = rahmen.querySelector('.reiter');
    if (!reihe) { return; }
    var aktiv = reihe.querySelector('.reiter-punkt.aktiv');
    if (aktiv && reihe.scrollWidth > reihe.clientWidth) {
      /* Mittig, soweit es geht: Links daneben steht dann der Reiter, von dem
       * man meist kommt, rechts der nächste. */
      reihe.scrollLeft = Math.max(0, aktiv.offsetLeft - (reihe.clientWidth - aktiv.offsetWidth) / 2);
    }
    rand(rahmen, reihe);
    reihe.addEventListener('scroll', function () { rand(rahmen, reihe); }, { passive: true });
    window.addEventListener('resize', function () { rand(rahmen, reihe); });
  });
})();
