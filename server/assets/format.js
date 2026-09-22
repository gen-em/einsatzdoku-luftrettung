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

    /* ---- Tag, Dauer, Strecke (Schritt 15 AP8d, Zaehlzeile Z34) ---------
     *
     * VIERZEHN DEFINITIONEN WAREN ES, IN FUENF DATEIEN -- und drei Sachen:
     * Tag, Dauer, Strecke. Der RECHENTEIL war ueber die Gruppen hinweg
     * zeichengleich; auseinander gingen sie im LEERFALL und in zwei
     * Schreibweisen. Gemessen am 22.09.2026 mit woertlichen Rumpfkopien
     * unter node:
     *
     *   - SECHS verschiedene Leer-Antworten: null, '', 'kein Ende' und
     *     zweimal ein fertiges <span class="dash">. Sieben der vierzehn
     *     hatten gar keine Wache und rechneten mit null weiter -- vier
     *     davon machten daraus still eine 0 ("0,0 km").
     *   - DREI von vierzehn warfen bei null eine TypeError.
     *   - ZWEI von vierzehn hatten keinen einzigen Aufrufer.
     *
     * DER LEERWERT IST DESHALB EIN PARAMETER und kein fester Wert. Genau
     * das war der Fehler von AP7: Eine Funktion mit festem Fruehausstieg
     * machte aus "- um -" ein "-". Hier steht der Leerwert an der
     * Aufrufstelle, wie `prozent_text($leer = '')` es auf der PHP-Seite
     * haelt -- und das Markup des Gedankenstrichs bleibt dort, wo es
     * hingehoert: an der Aufrufstelle, nicht in der Zentrale. Zwei Stellen
     * schieben ihr Ergebnis durch esc(); ein <span> aus der Zentrale
     * stuende dort buchstaeblich auf dem Bildschirm.
     *
     * KEINE PHP-ENTSPRECHUNG. `format_lib.php` kennt Datum und Zeit als
     * UTC-Zeitstempel mit Zonenumrechnung; hier geht es um einen nackten
     * Tagesstring ohne Zone und um Dauern, die es serverseitig nicht gibt.
     * Wer spaeter eine PHP-Seite dazu baut, baut sie NICHT hier nach.
     * ------------------------------------------------------------------ */

    /**
     * Tag aus 'JJJJ-MM-TT' als 'TT.MM.JJJJ'.
     *
     * DIE FORMPRUEFUNG KOMMT AUS `EdPat.datumDe` -- von den vier
     * Datumsfassungen im Bestand war sie die einzige mit einer, und die
     * vorsichtigste gewinnt. Die drei anderen warfen bei null eine
     * TypeError; das ergibt jetzt den Leerwert.
     *
     * DAS MUSTER HAT KEINEN ENDANKER, und das ist ein Unterschied zu
     * `EdPat.datumDe`, der dort `$` schrieb. Ein ISO-ZEITSTEMPEL
     * ('2026-08-14T10:00:00Z') besteht deshalb hier und ergibt
     * '14.08.2026' -- die drei alten Fassungen machten daraus
     * '14T10:00:00Z.08.2026', `datumDe` den Leerstring. Absicht: Der
     * Zeitstempel TRAEGT den Tag, und ihn wegzuwerfen hilft niemandem.
     * Wer den Endanker braucht, prueft vor dem Aufruf.
     * (Die erste Fassung dieses Kommentars behauptete, auch der
     * Zeitstempel ergebe den Leerwert. Er tut es nicht -- gefunden beim
     * Gegenlesen von AP8d.)
     */
    tag: function (iso, leer) {
      const t = String(iso == null ? '' : iso);
      if (!/^\d{4}-\d{2}-\d{2}/.test(t)) { return leer === undefined ? '' : leer; }
      return t.slice(8, 10) + '.' + t.slice(5, 7) + '.' + t.slice(0, 4);
    },

    /** Tag ohne Jahr, 'TT.MM.' -- fuer Extremwerte, deren Jahr im Titel steht. */
    tagKurz: function (iso, leer) {
      const t = String(iso == null ? '' : iso);
      if (!/^\d{4}-\d{2}-\d{2}/.test(t)) { return leer === undefined ? '' : leer; }
      return t.slice(8, 10) + '.' + t.slice(5, 7) + '.';
    },

    /**
     * Sekunden zwischen zwei ISO-Zeitmarken, oder null.
     *
     * `>= 0` faengt zugleich NaN ab -- jeder Vergleich mit NaN ist falsch.
     * Das stand schon in `durationHHMM()` so und ist absichtlich uebernommen.
     */
    spanne: function (startIso, endIso) {
      if (!startIso || !endIso) { return null; }
      const ms = new Date(endIso).getTime() - new Date(startIso).getTime();
      return ms >= 0 ? ms / 1000 : null;
    },

    /**
     * Dauer in Worten: '51min', '1h 06min'.
     *
     * ZWEI BENANNTE AENDERUNGEN gegenueber dem Bestand, beide in AP8d
     * entschieden und im Changelog einzeln aufgefuehrt:
     *
     * (1) DIE MINUTE IST IMMER ZWEISTELLIG. Die Tabellenfassung
     *     (`fmtDur`, seit Web 4.0.0) schrieb '1h 06min', die Fassung der
     *     Einsatzansicht (`fmtDauer`) '1h 6min'. Gemessen ueber 0 bis 1440
     *     Minuten: 231 von 1441 Werten (16,0 %) liefen auseinander. Der
     *     Kommentar ueber `fmtDauer` behauptete, die Schreibweise sei
     *     projektweit dieselbe; seine beiden Beispiele waren gerade die,
     *     bei denen es zufaellig stimmte. Die aeltere Fassung gewinnt,
     *     weil die Tabelle der Ort ist, an dem Dauern spaltenweise
     *     verglichen werden.
     *
     * (2) '60min' GIBT ES NICHT MEHR. `fmtDur` rechnete Stunden und
     *     Minuten GETRENNT -- floor(s/3600) und round((s%3600)/60) -- und
     *     erzeugte damit bei 3599 s ein '60min' und bei 7199 s ein
     *     '1h 60min'. Gemessen: 720 von 86 400 Sekundenwerten (0,83 %).
     *     Hier wird zuerst auf ganze Minuten gerundet und dann geteilt;
     *     3599 s ergibt '1h 00min'.
     *
     * Der Einstieg ist in SEKUNDEN. Dreizehn der vierzehn Datenwege fuehren
     * `duration_s`; die eine Stelle, die Minuten hatte, rechnet beim Aufruf
     * mal 60.
     */
    dauer: function (sekunden, leer) {
      if (sekunden == null || !(sekunden >= 0)) {
        return leer === undefined ? '' : leer;
      }
      const m = Math.round(sekunden / 60);
      const h = Math.floor(m / 60), r = m % 60;
      return h ? h + 'h ' + String(r).padStart(2, '0') + 'min' : r + 'min';
    },

    /** Dauer als 'HH:MM' -- die Schreibweise der Ausgabedateien, nicht der Oberflaeche. */
    dauerUhr: function (sekunden, leer) {
      if (sekunden == null || !(sekunden >= 0)) {
        return leer === undefined ? null : leer;
      }
      const m = Math.round(sekunden / 60);
      const zwei = (n) => String(n).padStart(2, '0');
      return zwei(Math.floor(m / 60)) + ':' + zwei(m % 60);
    },

    /** Dauer als nackte Minutenzahl -- ebenfalls fuer Ausgabedateien. */
    minuten: function (sekunden, leer) {
      if (sekunden == null || !(sekunden >= 0)) {
        return leer === undefined ? '' : leer;
      }
      return Math.round(sekunden / 60);
    },

    /**
     * Strecke aus Metern: '12,3 km', mit `o.einheit === false` nur '12,3'.
     *
     * `o.leer` ist Pflicht, wo der Leerfall vorkommt -- die Zentrale hat
     * KEINEN eigenen Gedankenstrich. Zwei Aufrufstellen brauchen dort ein
     * <span class="dash">, zwei andere die leere Zeichenkette, und eine
     * fuenfte schiebt das Ergebnis durch esc(). Ein festes Markup hier
     * waere an dreien davon falsch.
     */
    km: function (meter, o) {
      const opt = o || {};
      if (meter == null || isNaN(Number(meter))) {
        return opt.leer === undefined ? '' : opt.leer;
      }
      const z = (Number(meter) / 1000).toFixed(1).replace('.', ',');
      return opt.einheit === false ? z : z + ' km';
    },

    /**
     * Streckensumme, ganze Kilometer mit Tausenderpunkt: '1.633'.
     *
     * BENANNTE AENDERUNG: Die Startseite rechnete als einzige OHNE
     * `toLocaleString` und schrieb '1633 km', waehrend Suche und
     * Zeitraumuebersicht '1.633 km' zeigten -- dieselbe Zahl, zwei
     * Schreibweisen, auf drei Seiten derselben Anwendung. Die Einheit
     * bleibt an der Aufrufstelle, weil nicht jede von ihnen eine setzt.
     */
    kmSumme: function (meter, leer) {
      if (meter == null || isNaN(Number(meter))) {
        return leer === undefined ? '' : leer;
      }
      return EdFormat.zahl(Math.round(Number(meter) / 1000), 0);
    },
  };

  window.EdFormat = EdFormat;
})();
