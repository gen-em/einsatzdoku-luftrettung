/* Dialoge aus dem Markup öffnen und schliessen.
 * ===========================================================================
 *
 * WOFUER. Manche Handlungen brauchen mehr als eine Rückfrage: Das Einspielen
 * einer Sicherung will ein Zielkonto und eine abgetippte Adresse (E-P3-41).
 * confirm.js kann das nicht — es baut seinen Dialog selbst und kennt nur Text
 * und zwei Knöpfe. Hier steht das Gegenstück: Der Dialog steht als <dialog
 * class="dialog"> IM MARKUP, mit Formular und allem, was er braucht; dieses
 * Skript öffnet und schliesst ihn nur.
 *
 * EIN DIALOG FUER VIELE ZEILEN. Drei Sicherungen bekommen nicht drei
 * Dialoge — sonst stünde dasselbe Formular dreimal in der Seite und die
 * Kennungen müssten durchnummeriert werden. Stattdessen trägt der Öffner die
 * Werte, die den Fall ausmachen:
 *
 *     <button data-dialog="dlg-einspielen"
 *             data-w-datei="2026-08-03T22-10-00Z_ab12.json"
 *             data-w-zeit="03.08.2026 · 22:10">
 *
 * und im Dialog holt sie sich, wer `data-fuell` trägt:
 *
 *     <input type="hidden" name="datei" data-fuell="datei">
 *     <strong data-fuell="zeit"></strong>
 *
 * Bei Formularfeldern wird `value` gesetzt, sonst der Text. Ein Feld, zu dem
 * der Öffner nichts sagt, bleibt unberührt — das ist der Grund für die
 * Schleife über die Attribute des Öffners und nicht über die Felder.
 *
 * OHNE showModal() PASSIERT NICHTS. <dialog> ist seit 2022 überall da; sollte
 * es doch fehlen, öffnet sich kein Dialog, statt dass ein halb sichtbares
 * Formular ohne Schleier stehen bleibt.
 */
(function () {
  'use strict';

  function fuellen(dialog, oeffner) {
    var attrs = oeffner.attributes;
    for (var i = 0; i < attrs.length; i++) {
      var name = attrs[i].name;
      if (name.indexOf('data-w-') !== 0) { continue; }
      var ziel = dialog.querySelector('[data-fuell="' + name.slice(7) + '"]');
      if (!ziel) { continue; }
      if ('value' in ziel && (ziel.tagName === 'INPUT' || ziel.tagName === 'SELECT'
          || ziel.tagName === 'TEXTAREA')) {
        ziel.value = attrs[i].value;
      } else {
        ziel.textContent = attrs[i].value;
      }
    }
  }

  document.addEventListener('click', function (ev) {
    if (!ev.target.closest) { return; }

    var oeffner = ev.target.closest('[data-dialog]');
    if (oeffner) {
      var d = document.getElementById(oeffner.getAttribute('data-dialog'));
      if (!d || typeof d.showModal !== 'function') { return; }
      ev.preventDefault();
      fuellen(d, oeffner);
      if (!d.open) { d.showModal(); }
      /* Der Fokus auf das erste Feld, das etwas verlangt — bei der
         Adressbestätigung ist das genau das Feld, um dessentwillen es den
         Dialog gibt. */
      var erstes = d.querySelector('input:not([type=hidden]),select,textarea');
      if (erstes) { erstes.focus(); }
      return;
    }

    var schliesser = ev.target.closest('[data-dialog-zu]');
    if (schliesser) {
      var offen = schliesser.closest('dialog');
      ev.preventDefault();
      if (offen && offen.open) { offen.close(); }
    }
  });
})();
