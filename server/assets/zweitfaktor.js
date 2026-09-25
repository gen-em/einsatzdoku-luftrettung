/**
 * „Weiter" erst nach dem Haken — das Einrichtungstor des Zweitfaktors
 * (P5c/AP5, M-P5c-02b Bild 4, Schritt 2).
 *
 * DIE CODES SIND NUR JETZT SICHTBAR. Der Server speichert von jedem nur
 * einen Prüfwert; wer weiterklickt, ohne sie gesichert zu haben, hat keinen
 * Rückweg mehr, wenn das Handy fehlt. Deshalb wie beim
 * Wiederherstellungsschlüssel: erst der Haken, dann „Weiter".
 *
 * DER KNOPF STEHT IM MARKUP FREI und wird HIER gesperrt. Andersherum —
 * `disabled` im Markup, freigegeben per Skript — stünde ohne JavaScript ein
 * Knopf da, der nie frei wird, und das Tor wäre eine Sackgasse. So bleibt es
 * ohne Skript bei einem Hinweis, und mit Skript bei einer Sperre.
 *
 * Ohne `[data-zf-weiter]` (die Profilkarte) tut die Datei nichts: Dort gibt
 * es keinen nächsten Schritt, den der Haken freigeben könnte.
 */
(function () {
  'use strict';
  var haken = document.querySelector('[data-zf-gesichert]');
  var weiter = document.querySelector('[data-zf-weiter]');
  if (!haken || !weiter) { return; }
  function stellen() { weiter.disabled = !haken.checked; }
  haken.addEventListener('change', stellen);
  stellen();
})();
