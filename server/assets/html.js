/* Gen-EM NAdoku — HTML-Maskierung im Browser (Baustein B7).
 *
 * WARUM ES DIESE DATEI GIBT
 * Dieselbe Aufgabe wurde im Browser an vier Stellen geloest, in zwei
 * verschiedenen Fassungen:
 *
 *   - drei Kopien ueber ein Hilfselement (textContent -> innerHTML) in
 *     missiontable.js, einsatz.php und index.php,
 *   - eine Kopie mit Ersetzungen in import_ui.js.
 *
 * Alle vier maskierten nur DREI Zeichen; die serverseitige Entsprechung
 * (e() in db.php) maskiert FUENF — zusaetzlich beide Anfuehrungszeichen. Zwei
 * Bausteine mit demselben Zweck und unterschiedlichem Umfang, ohne dass der
 * Unterschied irgendwo stand.
 *
 * Fuer Textpositionen reichen drei Zeichen, und heute gibt es keine
 * Attributposition. Genau deshalb ist der Unterschied gefaehrlich: Wer das
 * naechste Mal einen Wert in ein title="…" schreibt, hat keinen Anhaltspunkt,
 * dass die alte Fassung dafuer nicht taugt.
 *
 * WARUM EINE EIGENE DATEI UND NICHT missiontable.js
 * Die kanonische Fassung stand seit Web 4.0.0 in der gemeinsamen
 * Tabellenkomponente. Die wird aber nur von suche.php und zeitraum.php
 * geladen — einsatz.php, index.php und import.php brauchen die Maskierung
 * ebenfalls und haetten dafuer die vollstaendige Tabellenkomponente laden
 * muessen. Deshalb steht der Baustein jetzt fuer sich; EdMissionTable.escape
 * bleibt als Weiterleitung bestehen, damit vorhandene Aufrufe gueltig bleiben.
 *
 * NICHT hierher gehoert xmlEscape() aus export.js: XML kennt &apos;, HTML5
 * kennt es erst seit HTML5 — und vor allem ist die GPX-Datei ein anderes
 * Zielformat mit eigenen Regeln. Zwei Aufgaben, die sich aehneln, sind nicht
 * dieselbe Aufgabe.
 *
 * Eingebunden von: einsatz.php, import.php, index.php, suche.php,
 * zeitraum.php (ueber missiontable.js dort ohnehin vorhanden).
 */
'use strict';
const EdHtml = (() => {

  /**
   * Maskiert alle fuenf Zeichen, die in HTML eine Bedeutung haben.
   * Sicher in Text- UND in Attributpositionen.
   *
   * null und undefined ergeben die leere Zeichenkette — sie sind "keine
   * Angabe", nicht der Text "null".
   */
  function escape(t) {
    return String(t == null ? '' : t)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  /* -------------------------------------------------------------------
   * EdHtml.meldung(ton, text, o) -- das eine Meldungs-Markup im Browser
   *   (Schritt 15 AP8e, Zaehlzeile Z37)
   *
   * DAS GEGENSTUECK IST ui_meldung_markup() in server/ui.php. Wer hier
   * etwas aendert, aendert es dort mit -- sonst sehen die serverseitig
   * ausgegebene und die im Browser gebaute Meldung verschieden aus, und
   * zwar auf derselben Seite.
   *
   * WAS VORHER WAR. Sieben eigenstaendige Nachbauten, und KEINE ZWEI
   * GLEICH. Gemessen am 22.09.2026. Drei davon trugen eine eigene
   * Ton-zu-Symbol-Tabelle mit drei verschiedenen Umfaengen: PHP fuenf
   * Eintraege, einstellungen.php vier (ohne `schutz`), import_ui.js zwei
   * Zweige (alles ausser fehler/warn wurde zum Hinweiszeichen). Der Beweis,
   * dass das ein Defekt war und kein Geschmack, steht in import_ui.js
   * selbst: Zwanzig Zeilen unter der eigenen Tabelle stand die
   * Erfolgsmeldung VON HAND gebaut da, mit edSymbol('haken') ausgeschrieben
   * -- weil die eigene Tabelle fuer 'ok' den Kreis-i geliefert haette.
   * Eine Umgehung ist der Beweis fuer den Defekt.
   *
   * FUENF TOENE, DIE LISTE IST GESCHLOSSEN, UNBEKANNTES WIRFT. Genau wie
   * PHP. Ein Ton ohne Regel im Stylesheet ergibt einen ungestalteten
   * Kasten ohne jede Fehlermeldung -- die Spurenseite trug so zwei Jahre
   * lang zwei weisse Meldungen. Weil die Klasse hier ZUSAMMENGESETZT wird,
   * sieht die Vollstaendigkeitspruefung sie nicht; das kann nur diese
   * Stelle selbst pruefen. Nachgemessen: Keine der heutigen
   * Aufrufstellen uebergibt einen Ton ausserhalb der fuenf -- der Wurf ist
   * neu und aendert kein heute sichtbares Bild.
   *
   * edSymbol() WIRD ZUR AUFRUFZEIT GELESEN, nicht zur Ladezeit. Grund:
   * assets/symbol.js kommt aus der Immer-Liste von ui_geruest_ende(), und
   * die steht auf einstellungen.php und import.php NACH den Seitenskripten
   * -- also nach dieser Datei. Beim Aufruf (in einem Klickzuhoerer) ist es
   * da; beim Laden waere es das dort nicht.
   *
   * @param {string} ton   'fehler' | 'warn' | 'ok' | 'info' | 'schutz'
   * @param {string} text  Meldungstext; wird maskiert, ausser bei o.roh
   * @param {Object} [o]
   * @param {string} [o.auftakt] fetter Auftakt vor dem Text, wird maskiert
   * @param {string} [o.knopf]   fertiges Markup einer Aktion, NICHT maskiert
   * @param {boolean} [o.roh]    `text` unmaskiert einsetzen -- siehe unten
   * @returns {string} Markup, zeichengleich mit ui_meldung_markup()
   * ----------------------------------------------------------------- */
  const MELDUNG_SYMBOLE = {
    fehler: 'warnung', warn: 'warnung', ok: 'haken',
    info: 'hinweis', schutz: 'schloss'
  };

  function meldung(ton, text, o) {
    const opt = o || {};
    if (!Object.prototype.hasOwnProperty.call(MELDUNG_SYMBOLE, ton)) {
      throw new Error('Unbekannter Meldungston \u201e' + ton + '\u201c. Erlaubt: '
        + Object.keys(MELDUNG_SYMBOLE).join(', ') + '.');
    }
    /* `o.roh` IST EIN LOCH IN DER MASKIERUNG, und es hat genau einen
     * Verbraucher: die Erfolgsmeldung des Imports, deren Satz einen
     * Zeilenumbruch, eine Kleinzeile und einen Link auf den ersten Tag
     * enthaelt. Der baut sein Markup selbst und maskiert die eingesetzten
     * Werte einzeln, BEVOR es hierherkommt. Wer einen zweiten Verbraucher
     * anlegt, maskiert dort ebenso -- sonst steht ein Datenwert als
     * Markup in der Seite. Ohne diesen Weg waere die Stelle nicht
     * umstellbar gewesen und haette als achter Nachbau stehen bleiben
     * muessen; ein benanntes Loch ist besser als ein ungezaehlter Nachbau. */
    const inhalt = opt.roh ? String(text == null ? '' : text) : escape(text);
    const auftakt = opt.auftakt
      ? '<strong>' + escape(opt.auftakt) + '</strong> ' : '';
    const knopf = opt.knopf
      ? '<div class="meldung-aktion">' + opt.knopf + '</div>' : '';
    return '<div class="meldung meldung-' + escape(ton) + '" role="'
      + (ton === 'fehler' ? 'alert' : 'status') + '">'
      + edSymbol(MELDUNG_SYMBOLE[ton], 'symbol-gross')
      + '<p>' + auftakt + inhalt + '</p>'
      + knopf
      + '</div>';
  }

  return { escape, meldung };
})();
