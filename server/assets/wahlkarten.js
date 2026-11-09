/* Gen-EM NAdoku — Wahlkarten: erst wählen, dann füllen (`.wahlkarten`).
 * ===========================================================================
 *
 * WOFUER (Konzept OF, OF-04, E-OF-26, Bild OF-P17-C). Eine Seite mit zwei
 * Wegen — „Liste übernehmen" und „Liste ausgeben" — zeigte beide Formulare
 * untereinander. Die Wahlkarten sind Radios in Kartenform; dieses Skript
 * blendet den Teil der Seite ein, der zur Wahl gehört
 * (`[data-wahl-zu="<name>"][data-wahl="<wert>"]`), und die anderen aus.
 * Die Sprungmarken der Leiste folgen von selbst (`menue.js` beobachtet
 * `hidden`).
 *
 * OHNE SKRIPT stehen alle Teile da — die Wahl ist dann nur Anzeige. Steht
 * der Anker der Adresse in einem Teil, ist dessen Karte gewählt.
 */
(function () {
  'use strict';

  Array.prototype.forEach.call(document.querySelectorAll('[data-wahlkarten]'), function (gruppe) {
    var name = gruppe.getAttribute('data-wahlkarten');
    var teile = document.querySelectorAll('[data-wahl-zu="' + name + '"]');
    var radios = gruppe.querySelectorAll('input[type=radio][name="' + name + '"]');

    function zeigen(wert) {
      Array.prototype.forEach.call(teile, function (t) {
        t.hidden = t.getAttribute('data-wahl') !== wert;
      });
    }

    /* Der Anker wählt — beim Laden und beim Sprung innerhalb der Seite
       (eine Sprungmarke auf `#exportform` lädt nicht neu). */
    function ankerWaehlen() {
      var ziel = location.hash ? document.getElementById(decodeURIComponent(location.hash.slice(1))) : null;
      var teilMitZiel = ziel ? ziel.closest('[data-wahl-zu="' + name + '"]') : null;
      if (teilMitZiel) {
        Array.prototype.forEach.call(radios, function (r) {
          r.checked = r.value === teilMitZiel.getAttribute('data-wahl');
        });
      }
      var gewaehlt = gruppe.querySelector('input[type=radio]:checked');
      zeigen(gewaehlt ? gewaehlt.value : '');
      if (teilMitZiel) { ziel.scrollIntoView(); }
    }
    ankerWaehlen();
    window.addEventListener('hashchange', ankerWaehlen);
    gruppe.addEventListener('change', function (e) {
      if (e.target.name === name) { zeigen(e.target.value); }
    });
  });
})();
