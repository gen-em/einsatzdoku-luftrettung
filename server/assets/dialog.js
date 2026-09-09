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
 * HAKEN UND RADIOS (S9/AP5-4). Bis Web 16.3.0 setzte `fuellen()` an jedem
 * Ziel `value` oder `textContent` — `checked` kam nicht vor, und je Schlüssel
 * wurde genau EIN Element getroffen (`querySelector`). Für die Stammdaten
 * reicht das nicht: Ein Rettungsmittel hat eine Betriebsart (zwei Radios),
 * bis fünf Besatzungsrollen und zwei Fähigkeiten (sieben Kästchen). Jetzt
 * gilt:
 *
 *   - Ein Ziel, das `checkbox` oder `radio` ist, bekommt `checked` statt
 *     `value` — und zwar aus einer MENGE: `data-w-rollen="pilot1,hemstc"`
 *     setzt jedes Kästchen, dessen `value` darin vorkommt, und löscht jedes
 *     andere. Eine leere Menge löscht alle.
 *   - Je Schlüssel werden ALLE Ziele bedient (`querySelectorAll`), nicht das
 *     erste. Sieben Kästchen tragen denselben `data-fuell`.
 *
 * PROGRAMMATISCH ÖFFNEN (`window.edDialog.auf`). Bis hierher war die ganze
 * Schnittstelle deklarativ, und eine Seite konnte keinen Dialog von sich aus
 * öffnen. Das braucht der Fehlerweg: Schlägt das Speichern fehl, zeichnet der
 * Server die Seite mit dem vorbelegten Dialog und dessen Meldung, und das
 * Seitenskript öffnet ihn beim Laden. `blatt.js` hat für denselben Zweck
 * seit Langem `window.edBlatt.zu`.
 *
 * BEIM ÖFFNEN AUS EINEM AKTIONSBLATT schliesst sich das Blatt (`edBlatt.zu`).
 * `blatt.js` schliesst ausdrücklich NICHT, wenn der Klick einen Eintrag im
 * Blatt selbst trifft — der Dialog stünde sonst vor einem offenen Blatt, und
 * hinter dem Schleier bliebe es liegen, bis jemand daneben tippt.
 *
 * EIN KLICK-ÖFFNER SETZT DAS FORMULAR ZURÜCK, `auf()` NICHT. Ein zweites
 * Öffnen zeigte sonst die Werte des vorigen Falls an jedem Feld, zu dem der
 * neue Öffner nichts sagt. Der Fehlerweg darf das nicht — dort steht im
 * Markup gerade das, was die NutzerIn eingegeben hat.
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

  /* Ein Kaestchen oder Radio ist gesetzt, wenn sein `value` in der Menge des
     Oeffners steht. Leere Menge heisst: alle aus. */
  function istHaken(el) {
    return el.tagName === 'INPUT' && (el.type === 'checkbox' || el.type === 'radio');
  }

  function fuellen(dialog, oeffner) {
    var attrs = oeffner.attributes;
    for (var i = 0; i < attrs.length; i++) {
      var name = attrs[i].name;
      if (name.indexOf('data-w-') !== 0) { continue; }
      var schluessel = name.slice(7);
      var wert = attrs[i].value;
      var liste = dialog.querySelector('[data-fuell-optionen="' + schluessel + '"]');
      if (liste) { optionenSetzen(liste, wert); }
      /* ALLE Ziele, nicht das erste: sieben Kaestchen tragen denselben
         Schluessel. */
      var ziele = dialog.querySelectorAll('[data-fuell="' + schluessel + '"]');
      if (!ziele.length) { continue; }
      var menge = String(wert).split(',');
      for (var j = 0; j < ziele.length; j++) {
        var ziel = ziele[j];
        if (istHaken(ziel)) {
          ziel.checked = menge.indexOf(ziel.value) !== -1;
        } else if ('value' in ziel && (ziel.tagName === 'INPUT' || ziel.tagName === 'SELECT'
            || ziel.tagName === 'TEXTAREA')) {
          ziel.value = wert;
        } else {
          ziel.textContent = wert;
        }
      }
      /* Ein `change` je Menge, damit eine Seite, die auf die Auswahl hoert
         (Typ steuert Rollen und Faehigkeiten), nach dem Fuellen denselben
         Weg geht wie nach einem Klick. Von Hand gesetzte Werte loesen kein
         Ereignis aus. */
      if (ziele.length && (istHaken(ziele[0]) || ziele[0].tagName === 'SELECT')) {
        ziele[0].dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }

  /** Einen Dialog oeffnen, ohne dass jemand geklickt hat. */
  function oeffne(dialog, oeffner) {
    if (!dialog || typeof dialog.showModal !== 'function') { return false; }
    if (oeffner) { fuellen(dialog, oeffner); }
    if (!dialog.open) { dialog.showModal(); }
    var erstes = dialog.querySelector('input:not([type=hidden]),select,textarea');
    if (erstes) { erstes.focus(); }
    return true;
  }

  /* Der Namensraum. `blatt.js` hat seit Langem `window.edBlatt`; hier fehlte
     das Gegenstueck, und ohne es kann der Fehlerweg seinen Dialog nicht
     wieder aufmachen. */
  window.edDialog = {
    auf: function (id) { return oeffne(document.getElementById(id), null); }
  };

  document.addEventListener('click', function (ev) {
    if (!ev.target.closest) { return; }

    var oeffner = ev.target.closest('[data-dialog]');
    if (oeffner) {
      var d = document.getElementById(oeffner.getAttribute('data-dialog'));
      if (!d || typeof d.showModal !== 'function') { return; }
      ev.preventDefault();
      /* DAS AKTIONSBLATT SCHLIESSEN, aus dem der Klick kam. `blatt.js`
         schliesst nicht von selbst, wenn der Klick einen Eintrag IM Blatt
         trifft — der Dialog stuende sonst vor einem offenen Blatt. */
      if (window.edBlatt && typeof window.edBlatt.zu === 'function'
          && oeffner.closest('.blatt')) {
        window.edBlatt.zu();
      }
      /* ZURUECKSETZEN VOR DEM FUELLEN: Ein zweites Oeffnen zeigte sonst die
         Werte des vorigen Falls an jedem Feld, zu dem dieser Oeffner nichts
         sagt. `auf()` tut das mit Absicht NICHT — dort steht im Markup
         gerade, was die NutzerIn eingegeben hat. */
      var form = d.querySelector('form');
      if (form && typeof form.reset === 'function') { form.reset(); }
      oeffne(d, oeffner);
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
