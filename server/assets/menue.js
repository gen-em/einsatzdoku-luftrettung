/* Gen-EM NAdoku — die drei Blöcke der Einstellungsleiste (E-S8-07).
 * ===========================================================================
 *
 * WOFUER. Fuer eine BetreiberIn stehen siebzehn Menuepunkte untereinander.
 * Gemessen am 05.09.2026 bei 1280 x 900: Die Liste ist 896 px hoch, die
 * Leiste bietet 783 px — 14 von 17 Eintraegen waren ohne Rollen erreichbar,
 * bei 720 px Fensterhoehe noch 10 von 17. Wer „Backup-Ziele" sucht, sieht
 * nicht, dass es den Eintrag gibt. Die Bloecke klappen deshalb.
 *
 * DIE VORGABE MACHT PHP, NICHT DIESES SKRIPT: „Einstellungen" ist offen,
 * dazu der Block der aktiven Seite; die uebrigen sind zu — in jeder Breite.
 * Der Serverzustand ist damit schon der Zielzustand, und es blitzt beim
 * Seitenaufruf nichts anderes auf. Warum die Vorgabe nicht mehr nach der
 * Breite unterscheidet, steht in `ui.php` an der erzeugenden Stelle.
 *
 * DIESES SKRIPT MACHT GENAU ZWEIERLEI:
 *
 *   1  Es legt ueber die Vorgabe, was in dieser Sitzung von Hand geaendert
 *      wurde.
 *   2  Es merkt sich jede Aenderung.
 *
 * WAS DIE NUTZERIN AENDERT, GILT FUER DIE SITZUNG. Der Zustand liegt im
 * sessionStorage, je Block ein Schluessel. `sessionStorage` und nicht
 * `localStorage`: Ein zugeklappter Block ist eine Entscheidung fuer diesen
 * Arbeitsgang, keine Einstellung — wer morgen wiederkommt, soll das Menue
 * wieder in der Vorgabe sehen. So verlangt es die Abnahme P-28: Der Zustand
 * ueberlebt den Seitenwechsel, nicht die Sitzung.
 *
 * DIE VORGABE HAT VORRANG BEIM WECHSEL DER AKTIVEN SEITE — genauer: Der
 * gemerkte Zustand gilt nur fuer Bloecke, die nicht gerade die aktive Seite
 * tragen. Sonst waere ein Block, den jemand einmal zugeklappt hat,
 * ausgerechnet dann zu, wenn er die Seite enthaelt, auf der man steht; der
 * aktive Eintrag stuende unsichtbar darin.
 *
 * OHNE sessionStorage (privates Fenster mit gesperrtem Speicher) bleibt es
 * bei der Vorgabe aus PHP — jeder Zugriff steht in einem try/catch. Ein
 * Menue, das nicht mehr aufklappt, weil ein Speicher fehlt, waere der
 * schlechtere Tausch.
 */
(function () {
  'use strict';

  var liste = document.querySelector('[data-menue]');
  if (!liste) { return; }

  var VORSATZ = 'nadoku.menue.';
  var bloecke = liste.querySelectorAll('.leiste-gruppe');

  function lies(schluessel) {
    try { return sessionStorage.getItem(VORSATZ + schluessel); }
    catch (e) { return null; }
  }

  function schreib(schluessel, offen) {
    try { sessionStorage.setItem(VORSATZ + schluessel, offen ? '1' : '0'); }
    catch (e) { /* gesperrter Speicher: die Vorgabe aus PHP traegt weiter */ }
  }

  Array.prototype.forEach.call(bloecke, function (block) {
    /* Der Block der aktiven Seite bleibt, wie PHP ihn gesetzt hat — offen.
     * `.aktiv` steht am Eintrag, nicht am Block; hier wird danach gesucht. */
    var traegtAktiv = !!block.querySelector('.eintrag.aktiv');
    var gemerkt = lies(block.dataset.gruppe);
    if (gemerkt !== null && !traegtAktiv) { block.open = gemerkt === '1'; }

    block.addEventListener('toggle', function () {
      schreib(block.dataset.gruppe, block.open);
    });
  });
})();
