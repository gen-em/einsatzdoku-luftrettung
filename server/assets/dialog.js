/* Dialoge aus dem Markup öffnen und schliessen.
 * ===========================================================================
 *
 * WOFUER. Manche Handlungen brauchen mehr als eine Rückfrage: Das Einspielen
 * eines Backups will ein Zielkonto und eine abgetippte Adresse (E-P3-41).
 * confirm.js kann das nicht — es baut seinen Dialog selbst und kennt nur Text
 * und zwei Knöpfe. Hier steht das Gegenstück: Der Dialog steht als <dialog
 * class="dialog"> IM MARKUP, mit Formular und allem, was er braucht; dieses
 * Skript öffnet und schliesst ihn nur.
 *
 * EIN DIALOG FUER VIELE ZEILEN. Drei Backups bekommen nicht drei
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
 * EIN AUSWAHLFELD, DESSEN EINTRÄGE ERST DER ÖFFNER KENNT (S8/AP3). „Backups
 * ohne Konto" trägt eine Zeile je ORDNER, und welches PAKET eingespielt
 * werden soll, wird im Dialog gewählt. Die Pakete unterscheiden sich je
 * Ordner — ein festes <option>-Gerüst im Markup gäbe es also nicht. Deshalb
 * baut ein `<select data-fuell-optionen="pakete">` seine Einträge aus dem
 * Wert des Öffners:
 *
 *     data-w-pakete="datei1|03.08.2026 · 22:10 · vollständig\ndatei2|…"
 *
 * Eine Zeile je Eintrag, `wert|Beschriftung`. Der erste ist vorgewählt — die
 * Liste kommt jüngstes zuerst, und das ist fast immer das gemeinte. Steht
 * kein Wert bereit, bleibt das Feld leer statt mit einem geratenen Eintrag
 * gefüllt.
 *
 * OHNE showModal() PASSIERT NICHTS. <dialog> ist seit 2022 überall da; sollte
 * es doch fehlen, öffnet sich kein Dialog, statt dass ein halb sichtbares
 * Formular ohne Schleier stehen bleibt.
 */
(function () {
  'use strict';

  /* Einträge eines Auswahlfelds aus „wert|Text" je Zeile bauen. */
  function optionenSetzen(feld, wert) {
    feld.innerHTML = '';
    var zeilen = String(wert || '').split('\n');
    for (var i = 0; i < zeilen.length; i++) {
      if (!zeilen[i]) { continue; }
      var trenn = zeilen[i].indexOf('|');
      var o = document.createElement('option');
      o.value = trenn < 0 ? zeilen[i] : zeilen[i].slice(0, trenn);
      o.textContent = trenn < 0 ? zeilen[i] : zeilen[i].slice(trenn + 1);
      feld.appendChild(o);
    }
    if (feld.options.length) { feld.selectedIndex = 0; }
  }

  function fuellen(dialog, oeffner) {
    var attrs = oeffner.attributes;
    for (var i = 0; i < attrs.length; i++) {
      var name = attrs[i].name;
      if (name.indexOf('data-w-') !== 0) { continue; }
      var schluessel = name.slice(7);
      var liste = dialog.querySelector('[data-fuell-optionen="' + schluessel + '"]');
      if (liste) { optionenSetzen(liste, attrs[i].value); }
      var ziel = dialog.querySelector('[data-fuell="' + schluessel + '"]');
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
