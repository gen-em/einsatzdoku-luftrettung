/* Gen-EM NAdoku — Datumseingabe als TT.MM.JJJJ (`.feld-datum`).
 * ===========================================================================
 *
 * WOFUER (Konzept OF, OF-03, E-OF-06, -44, -54). `<input type="date">` zeigte
 * je nach Browser und Systemsprache „dd/mm/yyyy" oder „mm/dd/yyyy", klappte
 * einen Kalender auf und war am Schreibtisch so schmal, dass das Jahr
 * abgeschnitten wurde. Das Datumsfeld ist ein Textfeld mit derselben Maske
 * wie die Zeitfelder (`zeitfeld.js`): Wer „07112026" tippt, sieht
 * „07.11.2026"; ohne Kalender.
 *
 * WAS ES TUT: beim Tippen die Punkte setzen (nach zwei und vier Ziffern),
 * beim Verlassen pruefen und auf zwei Stellen auffuellen („7.1.2026" →
 * „07.01.2026"), ein falsches Datum rot markieren. `data-min` und `data-max`
 * (ISO) grenzen ein. Den Wert liest, wer ihn braucht, ueber
 * `EdFormat.datumLesen()`; der Server ueber `post_datum()` / `get_datum()`.
 *
 * DELEGIERT, nicht je Feld ausgeruestet: Auch ein Feld, das ein Skript
 * spaeter einsetzt, ist dabei. Ohne Skript bleibt ein Textfeld mit `pattern`.
 */
(function () {
  'use strict';

  var LESEN = function (t) { return window.EdFormat ? EdFormat.datumLesen(t) : ''; };

  function pruefen(feld) {
    var v = feld.value.trim();
    var iso = v === '' ? '' : LESEN(v);
    var grund = '';
    if (v !== '' && !iso) { grund = 'Bitte ein Datum als TT.MM.JJJJ eintragen.'; }
    else if (iso && feld.dataset.min && iso < feld.dataset.min) {
      grund = 'Frühestens ' + EdFormat.tag(feld.dataset.min) + '.';
    } else if (iso && feld.dataset.max && iso > feld.dataset.max) {
      grund = 'Spätestens ' + EdFormat.tag(feld.dataset.max) + '.';
    }
    if (grund) { feld.setAttribute('aria-invalid', 'true'); }
    else { feld.removeAttribute('aria-invalid'); }
    if (typeof feld.setCustomValidity === 'function') { feld.setCustomValidity(grund); }
    return iso;
  }

  function beiEingabe(feld, e) {
    /* Nur beim Tippen am Ende setzen wir Punkte — wer mitten im Text
     * korrigiert oder loescht, bekommt keine Zeichen untergeschoben. */
    var vorher = feld.value;
    /* EIN EINGEFUEGTES ISO-DATUM („2026-03-01", aus einer Tabelle oder einer
     * Adresse) wird die deutsche Form — die Maske machte sonst aus den acht
     * Ziffern „20.26.0301". */
    if (/^\s*\d{4}-\d{2}-\d{2}\s*$/.test(vorher) && LESEN(vorher)) {
      feld.value = EdFormat.tag(LESEN(vorher));
      pruefen(feld);
      return;
    }
    if (e && e.inputType && e.inputType.indexOf('delete') === 0) { pruefen(feld); return; }
    if (feld.selectionStart !== vorher.length) { pruefen(feld); return; }
    var neu = vorher.replace(/[^\d.]/g, '');
    if (/^\d+$/.test(neu)) {
      var z = neu.slice(0, 8);
      neu = z.length > 4 ? z.slice(0, 2) + '.' + z.slice(2, 4) + '.' + z.slice(4)
          : z.length > 2 ? z.slice(0, 2) + '.' + z.slice(2) : z;
    } else if (/^\d{2}\.\d{2}$/.test(neu) && e && e.data && /\d/.test(e.data)) {
      /* nichts */
    } else if (/^\d{2}\.\d{3}$/.test(neu)) {
      neu = neu.slice(0, 5) + '.' + neu.slice(5);
    }
    if (neu !== vorher) {
      feld.value = neu;
      feld.setSelectionRange(neu.length, neu.length);
    }
    pruefen(feld);
  }

  function beiVerlassen(feld) {
    var iso = pruefen(feld);
    if (iso) {
      var schoen = EdFormat.tag(iso);
      if (schoen !== feld.value) {
        feld.value = schoen;
        feld.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  }

  document.addEventListener('input', function (e) {
    if (e.target.classList && e.target.classList.contains('feld-datum')) { beiEingabe(e.target, e); }
  });
  document.addEventListener('blur', function (e) {
    if (e.target.classList && e.target.classList.contains('feld-datum')) { beiVerlassen(e.target); }
  }, true);
  Array.prototype.forEach.call(document.querySelectorAll('.feld-datum'), function (f) {
    if (f.value) { pruefen(f); }
  });
})();
