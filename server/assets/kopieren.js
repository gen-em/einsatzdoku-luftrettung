/* Wertekasten kopieren — der Knopf am `.codeblock-lang`.
 * ===========================================================================
 *
 * WOFUER. Cron-Zeile, Token-Adresse, Setz-Link, Serverschluessel-Zeile,
 * Geraete-ID und API-Schluessel sind sechzig bis hundert Zeichen lang und
 * werden von einem Bildschirm in ein anderes Fenster uebertragen. Wer sie
 * abschreibt, macht Tippfehler; wer sie markiert, erwischt bei
 * `word-break:break-all` gern eine Zeile zu wenig. Ein Knopf macht daraus
 * einen Klick (E-S8-10, Backlog Nr. 78).
 *
 * DER KNOPF STEHT IM MARKUP AUF `hidden` UND WIRD HIER EINGEBLENDET. Ohne
 * JavaScript gaebe es sonst einen Knopf, der nichts tut — und das ist
 * schlechter als keiner. Der Wert selbst bleibt in beiden Faellen lesbar und
 * markierbar; das Kopieren ist Bequemlichkeit, kein Zugang.
 *
 * ZWEI WEGE, WEIL EINER NICHT IMMER DA IST. `navigator.clipboard` gibt es nur
 * in sicheren Kontexten (HTTPS oder localhost), und selbst dort kann die
 * Berechtigung verweigert werden. Faellt er aus, MARKIERT das Skript den Wert
 * — dann liegt er unter Strg+C, und der Knopf sagt es auch. Ein stiller
 * Fehlschlag waere hier besonders teuer: Man merkt ihn erst, wenn man
 * woanders einfuegt und nichts kommt.
 *
 * KEIN SYMBOL. Der Symbolvorrat hat keines fuer „kopieren", und ein neues
 * braucht Freigabe mit Mockup (Design.md 9). Das Wort tut es.
 */
(function () {
  'use strict';

  var kaesten = document.querySelectorAll('.codeblock-lang');
  if (!kaesten.length) { return; }

  Array.prototype.forEach.call(kaesten, function (kasten) {
    var knopf = kasten.querySelector('[data-kopieren]');
    var wert  = kasten.querySelector('[data-kopierwert]');
    if (!knopf || !wert) { return; }

    knopf.hidden = false;
    var beschriftung = knopf.querySelector('span') || knopf;
    var urtext = beschriftung.textContent;
    var uhr = null;

    /* Die Rueckmeldung steht IM KNOPF und nicht daneben: Sie gehoert zu der
     * Handlung, die man gerade ausgeloest hat, und ein Kasten, der aufklappt,
     * verschiebt den Rest der Seite. Nach zwei Sekunden steht wieder da, was
     * vorher dastand. */
    function sagen(text) {
      beschriftung.textContent = text;
      if (uhr) { clearTimeout(uhr); }
      uhr = setTimeout(function () { beschriftung.textContent = urtext; }, 2000);
    }

    function markieren() {
      try {
        var bereich = document.createRange();
        bereich.selectNodeContents(wert);
        var auswahl = window.getSelection();
        auswahl.removeAllRanges();
        auswahl.addRange(bereich);
        sagen('markiert — Strg+C');
      } catch (e) {
        sagen('bitte von Hand markieren');
      }
    }

    knopf.addEventListener('click', function () {
      var text = wert.textContent || '';
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          sagen('kopiert');
        }, markieren);
      } else {
        markieren();
      }
    });
  });
})();
