/* FORMAT — die eine Stelle im Browser, an der aus einem Wert ein Text wird
 * ==========================================================================
 *
 * Entstanden in Schritt 15 AP7 (Zentralisierung, R83). Das Gegenstueck zu
 * server/format_lib.php: dieselben Regeln, dieselbe Schreibweise, damit
 * dieselbe Zahl nicht je nach Weg zweimal verschieden dasteht.
 *
 * WER HIER ETWAS AENDERT, AENDERT ES DORT MIT. Das ist keine Hoeflichkeit,
 * sondern der Zweck der Datei: Vor diesem Paket rechnete die Seite
 * `einstellungen.php` die Groesse der heruntergeladenen Sicherungsdatei in
 * einer Inline-Zeile selbst aus — und kam auf eine andere Schreibweise als
 * jede Serverseite daneben.
 *
 * AP8 BAUT SIE AUS (Zaehlzeile Z34): Dann kommen Datum, Zeit, Dauer und
 * Entfernung dazu, und `missiontable.js` wird der zweite Verbraucher. Bis
 * dahin steht hier genau, was einen Verbraucher hat — eine Funktion auf
 * Verdacht waere das Gegenteil von R83.
 *
 * KEIN MODUL, KEIN IMPORT. Die Datei wird als gewoehnliches <script> geladen
 * und legt `window.EdFormat` an — wie `EdHtml`, `EdSymbol` und die uebrigen.
 */
(function () {
  'use strict';

  const EdFormat = {
    /**
     * Zahl in deutscher Schreibweise: Komma dezimal, Punkt fuer die Tausender.
     *
     * `toLocaleString('de-DE')` und nicht von Hand: Es rundet wie PHPs
     * `number_format()` von der Null weg (halfExpand), nicht zur geraden
     * Ziffer. Nachgemessen gegen die PHP-Seite; wer es durch `toFixed()`
     * plus `replace()` ersetzt, verliert den Tausenderpunkt.
     */
    zahl: function (n, stellen) {
      const s = stellen || 0;
      return Number(n).toLocaleString('de-DE', {
        minimumFractionDigits: s,
        maximumFractionDigits: s,
      });
    },

    /**
     * Dateigroesse: KB unter einem Megabyte, MB darueber, GB ab einem Gigabyte.
     *
     * DREI STUFEN MIT DREI VERSCHIEDENEN NACHKOMMASTELLEN — GB zwei, MB eine,
     * KB null. Das ist kein Schreibfehler, sondern die Schreibweise des
     * Hauses; `groesse_text()` in `server/format_lib.php` fuehrt sie
     * genauso, und die beiden sind Wert fuer Wert gegeneinander geprueft.
     */
    groesse: function (bytes) {
      const n = Number(bytes) || 0;
      if (n >= 1024 * 1024 * 1024) {
        return EdFormat.zahl(n / (1024 * 1024 * 1024), 2) + ' GB';
      }
      return n < 1024 * 1024
        ? EdFormat.zahl(n / 1024, 0) + ' KB'
        : EdFormat.zahl(n / (1024 * 1024), 1) + ' MB';
    },
  };

  window.EdFormat = EdFormat;
})();
