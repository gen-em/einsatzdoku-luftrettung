/* Symbole im Browser — dieselbe Zeichenkette wie ui_symbol() in PHP.
 * ===========================================================================
 *
 * WOFUER. Grosse Teile der Oberflaeche entstehen erst im Browser: die
 * Einsatztabelle und die Kachel (missiontable.js), die Feldliste und die
 * Phasentabelle der Einsatzansicht, die Reiter der Einstellungen, die
 * Kachelsaetze der Zeitraumansicht, die Dialoge. Wenn ein Zeichen dort anders
 * eingebunden wuerde als in PHP, gaebe es das Symbol zweimal — und beim
 * naechsten Wechsel aenderte jemand eine der beiden Stellen.
 *
 * Deshalb: EINE Zeichenkette, zwei Erzeuger. Wer eine der beiden Funktionen
 * aendert, aendert die andere mit; die Vollstaendigkeitspruefung
 * (tools/vollstaendigkeit/) meldet jeden Inline-Pfad, der daran vorbeigeht.
 *
 * ERKENNUNGSWERT. PHP kennt die Aenderungszeit der Datei, der Browser nicht.
 * Beide benutzen deshalb WEB_VERSION; die Seite gibt sie als data-Attribut am
 * <html> mit. Fehlt sie, laeuft der Verweis ohne Erkennungswert — das ist kein
 * Fehler, nur ein Zwischenspeicher, der laenger haelt.
 *
 * KEINE MODULE. Die Anwendung laedt ihre Skripte als klassische <script src>;
 * eine einzelne Datei als Modul auszuliefern haette die Ladereihenfolge aller
 * uebrigen veraendert. edSymbol haengt deshalb am window-Objekt, wie
 * edEsc() in html.js daneben.
 */
(function () {
  'use strict';

  /* ORDNER, nicht BASIS: „Basis" ist in diesem Projekt der Luftfahrtbegriff
   * fuer einen Standort (P2, Wortliste). Ein Pfadname, der zufaellig so
   * heisst, kostet spaeter eine Diskussion. */
  var ORDNER = 'assets/images/symbole/';

  function version() {
    var el = document.documentElement;
    return (el && el.dataset && el.dataset.webversion) || '';
  }

  /* Maskierung: Symbolnamen kommen aus dem eigenen Code, nicht aus Daten —
   * aber edSymbol() wird in Zeichenketten eingesetzt, die per innerHTML in
   * die Seite gehen. Ein Name, der aus einem Feldkatalog oder einer
   * Datenzeile stammt, darf dort nichts anrichten koennen. */
  function sicher(s) {
    return String(s).replace(/[^a-z0-9-]/g, '');
  }

  function maskieren(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  /**
   * @param {string} name     Dateiname ohne Endung, z. B. 'haus'
   * @param {string} [klassen] zusaetzliche Klassen
   * @param {string} [titel]   gesetzt = fuer Screenreader sichtbar
   * @returns {string} Markup, identisch zu ui_symbol() in PHP
   */
  function edSymbol(name, klassen, titel) {
    var v = version();
    var pfad = ORDNER + sicher(name) + '.svg' + (v ? '?v=' + encodeURIComponent(v) : '') + '#i';
    var k = 'symbol' + (klassen ? ' ' + klassen : '');
    var a = '<svg class="' + maskieren(k) + '" viewBox="0 0 24 24" focusable="false"';
    a += (titel === undefined || titel === null)
       ? ' aria-hidden="true">'
       : ' role="img"><title>' + maskieren(titel) + '</title>';
    return a + '<use href="' + maskieren(pfad) + '"></use></svg>';
  }

  window.edSymbol = edSymbol;
})();
