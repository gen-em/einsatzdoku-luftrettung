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

  return { escape };
})();
