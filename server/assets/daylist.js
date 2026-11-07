/* Akkordeon der Seitenleiste — Jahre und Monate schliessen sich gegenseitig.
 * ===========================================================================
 *
 * Das Auf- und Zuklappen selbst kann der Browser: Die Leiste ist aus
 * <details>/<summary> gebaut, und die ganze Zeile ist der Schalter.
 * Was er nicht kann, ist die Verkopplung — dass beim Aufklappen eines Jahres
 * die uebrigen Jahre zugehen. Genau dafuer ist dieses Skript da, und fuer
 * sonst nichts.
 *
 * KEINE SONDERBEHANDLUNG FUER DEN KLICK AUF DIE UEBERSICHT. Der Weg in die
 * Zeitraum-Uebersicht ist ein eigenes Symbol RECHTS in der Zeile und liegt
 * ausserhalb des <summary>, in `.akkordeon-rahmen` (seit Web 23.11.2).
 */
(function () {
  'use strict';

  function verkoppeln(elemente) {
    elemente.forEach(function (el) {
      el.addEventListener('toggle', function () {
        if (!el.open) { return; }
        elemente.forEach(function (andere) {
          if (andere !== el) { andere.open = false; }
        });
      });
    });
  }

  var wurzel = document.querySelector('.leiste-liste');
  if (!wurzel) { return; }

  // Jahre: die Akkordeons in den Rahmen unmittelbar unter der Liste. Der
  // Rahmen traegt daneben den Weg in die Uebersicht (seit Web 23.11.2;
  // bis dahin stand er im <summary>, und dieses Skript fing seinen Klick ab).
  var jahre = Array.from(wurzel.querySelectorAll(':scope > .akkordeon-rahmen > .akkordeon'));
  verkoppeln(jahre);

  // Monate: je Jahr getrennt, damit nur die Monate DESSELBEN Jahres sich
  // gegenseitig schliessen.
  jahre.forEach(function (jahr) {
    verkoppeln(Array.from(jahr.querySelectorAll(':scope > .akkordeon-inhalt > .akkordeon-rahmen > .akkordeon-monat')));
  });
})();
