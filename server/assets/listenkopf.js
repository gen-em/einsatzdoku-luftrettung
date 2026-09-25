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
})();
