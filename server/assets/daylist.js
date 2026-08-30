/* Akkordeon der Seitenleiste — Jahre und Monate schliessen sich gegenseitig.
 * ===========================================================================
 *
 * Das Auf- und Zuklappen selbst kann der Browser: Die Leiste ist aus
 * <details>/<summary> gebaut, und die ganze Zeile ist der Schalter (E-P3-09).
 * Was er nicht kann, ist die Verkopplung — dass beim Aufklappen eines Jahres
 * die uebrigen Jahre zugehen. Genau dafuer ist dieses Skript da, und fuer
 * sonst nichts.
 *
 * WAS HIER FRUEHER STAND UND JETZT NICHT MEHR NOETIG IST: eine Sonderbehandlung
 * fuer den Klick auf die Beschriftung. Bis Web 8.0.1 war der TEXT der Link auf
 * die Zeitraum-Uebersicht und nur das Dreieck der Schalter; beides lag im
 * <summary>, und ohne preventDefault haette ein Klick auf den Link zusaetzlich
 * auf- und zugeklappt. Auf einem Touchgeraet war das nicht auseinanderzuhalten.
 * Der Weg in die Uebersicht ist jetzt ein eigenes Symbol RECHTS in der Zeile
 * und liegt ausserhalb des <summary> — damit erledigt sich die Sonderregel.
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

  /* Der Balken-Link (Jahres-/Monatsübersicht) steht IM <summary> — als Kind
   * des <details> wäre er an zugeklappten Zeilen unsichtbar (F-P3-R). Ein
   * Klick auf einen Link im <summary> löst aber BEIDES aus: Navigation und
   * Auf-/Zuklappen. preventDefault unterbindet beides, danach wird von Hand
   * navigiert. Mit gedrückter Strg-/Cmd-Taste oder Mittelklick bleibt der
   * Browser zuständig (neuer Tab) — das <details> klappt dann eben mit, und
   * das ist der kleinere Schaden. */
  wurzel.addEventListener('click', function (ev) {
    var link = ev.target.closest ? ev.target.closest('.akkordeon-uebersicht') : null;
    if (!link) { return; }
    if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button !== 0) { return; }
    ev.preventDefault();
    window.location.href = link.href;
  });

  // Jahre: die Akkordeons unmittelbar unter der Liste.
  var jahre = Array.prototype.filter.call(wurzel.children, function (el) {
    return el.classList && el.classList.contains('akkordeon');
  });
  verkoppeln(jahre);

  // Monate: je Jahr getrennt, damit nur die Monate DESSELBEN Jahres sich
  // gegenseitig schliessen.
  jahre.forEach(function (jahr) {
    verkoppeln(Array.from(jahr.querySelectorAll(':scope > .akkordeon-inhalt > .akkordeon-monat')));
  });
})();
