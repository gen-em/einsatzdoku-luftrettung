/* Listenkopf — ein Auswahlfeld schickt die Suche ab.
 * ===========================================================================
 *
 * Entstanden mit P5c/AP2 (E-P5c-26, Bild M-P5c-01a). Der Listenkopf
 * (`ui_listenkopf()`) hat ein Suchfeld und Filterpillen; die Pillen sind
 * Verweise, das Suchfeld schickt mit der Eingabetaste ab. Ein Auswahlfeld
 * („Art") hat beides nicht — ohne dieses Skript steht daneben ein Knopf
 * „Filtern", mit ihm geht die Auswahl sofort ab und der Knopf ist fort.
 *
 * `data-absenden` am Feld, das Formular über das `form`-Attribut: Das Feld
 * steht in der Filterreihe, das Formular um das Suchfeld herum — im Bild
 * sind es zwei Zeilen, im Formular eine Anfrage.
 *
 * Seit Web 21.5.0 auch die Zeitraumwahl der Statistik (unten).
 */
(function () {
  'use strict';

  Array.prototype.forEach.call(document.querySelectorAll('select[data-absenden]'), function (feld) {
    var form = feld.form;
    if (!form) { return; }
    Array.prototype.forEach.call(document.querySelectorAll('[data-absenden-knopf]'), function (k) {
      if (k.form === form) { k.hidden = true; }
    });
    feld.addEventListener('change', function () {
      if (typeof form.requestSubmit === 'function') { form.requestSubmit(); }
      else { form.submit(); }
    });
  });

  /* ZEITRAUMWAHL (`ui_zeitraumwahl()`, R4-23, Bild M-R4-23). Zwei
   * Datumsfelder schicken ab, sobald der Fokus das PAAR verlässt, beide
   * gefüllt sind und sich etwas geändert hat — nicht bei jeder Änderung: Ein
   * Datumsfeld meldet `change` schon, während die Jahreszahl getippt wird,
   * und die Seite liefe dann viermal. Der Knopf „Anwenden" ist mit Skript
   * fort; ohne Skript steht er da. Ein Wechsel von „Von" nach „Bis" ist
   * kein Verlassen. */
  Array.prototype.forEach.call(document.querySelectorAll('[data-zeitraum]'), function (paar) {
    var form = paar.closest('form');
    var felder = paar.querySelectorAll('input[type="date"]');
    if (!form || felder.length !== 2) { return; }
    var stand = function () { return felder[0].value + '|' + felder[1].value; };
    var vorher = stand();
    Array.prototype.forEach.call(paar.querySelectorAll('[data-absenden-knopf]'), function (k) {
      k.hidden = true;
    });
    paar.addEventListener('focusout', function (e) {
      if (e.relatedTarget && paar.contains(e.relatedTarget)) { return; }
      if (!felder[0].value || !felder[1].value || stand() === vorher) { return; }
      vorher = stand();
      if (typeof form.requestSubmit === 'function') { form.requestSubmit(); }
      else { form.submit(); }
    });
  });
})();
