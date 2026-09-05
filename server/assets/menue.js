/* Gen-EM NAdoku — die drei Blöcke der Einstellungsleiste (E-S8-07).
 * ===========================================================================
 *
 * WOFUER. Fuer eine BetreiberIn stehen siebzehn Menuepunkte untereinander.
 * Gemessen am 05.09.2026 bei 1280 x 900: Die Liste ist 883 px hoch, die
 * Leiste bot 603 px — 14 von 17 Eintraegen waren ohne Rollen erreichbar, bei
 * 720 px Fensterhoehe noch 10 von 17. Wer „Backup-Ziele" sucht, sieht nicht,
 * dass es den Eintrag gibt. Die Bloecke klappen deshalb.
 *
 * DAS MARKUP KOMMT AUS ui.php, ALLE BLOECKE OFFEN. Warum in dieser Richtung,
 * steht dort; kurz: Ab 1024 px ist offen bereits der Zielzustand, und
 * darunter liegt die Leiste als Schublade ausserhalb des Bildes — was dieses
 * Skript dort zuklappt, hat nie jemand gesehen. Umgekehrt blitzte am
 * Schreibtisch bei jedem Seitenaufruf ein zusammengeklapptes Menue auf.
 *
 * DIE VORGABE (Konzept AP5 (2)):
 *
 *   ab 1024 px      alle Bloecke offen
 *   darunter        „Einstellungen" immer, dazu der Block der aktiven Seite
 *
 * WAS DIE NUTZERIN AENDERT, GILT FUER DIE SITZUNG. Der Zustand liegt im
 * sessionStorage, je Block ein Schluessel. `sessionStorage` und nicht
 * `localStorage`: Ein zugeklappter Block ist eine Entscheidung fuer diesen
 * Arbeitsgang, keine Einstellung — wer morgen wiederkommt, soll das Menue
 * wieder vollstaendig sehen. So verlangt es die Abnahme P-28: Der Zustand
 * ueberlebt den Seitenwechsel, nicht die Sitzung.
 *
 * DER GESPEICHERTE ZUSTAND SCHLAEGT DIE VORGABE, aber nur, solange die
 * Breite dieselbe geblieben ist: Ein Block, den jemand am Handy zuklappt,
 * soll am Schreibtisch nicht zugeklappt bleiben, wenn er dort ohnehin offen
 * gehoert. Deshalb steht die Breitenklasse („breit"/„schmal") im Schluessel.
 *
 * OHNE sessionStorage (privates Fenster mit gesperrtem Speicher) faellt alles
 * auf die Vorgabe zurueck — jeder Zugriff steht in einem try/catch. Ein
 * Menue, das nicht mehr aufklappt, weil ein Speicher fehlt, waere der
 * schlechtere Tausch.
 */
(function () {
  'use strict';

  var liste = document.querySelector('[data-menue]');
  if (!liste) { return; }

  var BREIT = 1024;
  var VORSATZ = 'nadoku.menue.';

  /** Breitenklasse — Teil des Schluessels, siehe Kopfkommentar. */
  function lage() {
    return window.matchMedia('(min-width:' + BREIT + 'px)').matches ? 'breit' : 'schmal';
  }

  function lies(schluessel) {
    try { return sessionStorage.getItem(VORSATZ + lage() + '.' + schluessel); }
    catch (e) { return null; }
  }

  function schreib(schluessel, offen) {
    try { sessionStorage.setItem(VORSATZ + lage() + '.' + schluessel, offen ? '1' : '0'); }
    catch (e) { /* gesperrter Speicher: die Vorgabe traegt weiter */ }
  }

  /** Vorgabe fuer einen Block bei der aktuellen Breite. */
  function vorgabe(block) {
    if (lage() === 'breit') { return true; }
    return block.dataset.gruppe === 'einstellungen' || block.hasAttribute('data-aktiv');
  }

  var bloecke = liste.querySelectorAll('.leiste-gruppe');

  function anwenden() {
    Array.prototype.forEach.call(bloecke, function (block) {
      var gemerkt = lies(block.dataset.gruppe);
      block.open = gemerkt === null ? vorgabe(block) : gemerkt === '1';
    });
  }

  anwenden();

  Array.prototype.forEach.call(bloecke, function (block) {
    block.addEventListener('toggle', function () {
      schreib(block.dataset.gruppe, block.open);
    });
  });

  /* BEIM WECHSEL UEBER DIE SCHWELLE neu entscheiden. Das trifft das gedrehte
   * Handy und das gezogene Fensterrand — beides sind Faelle, in denen die
   * Vorgabe eine andere ist und der gemerkte Zustand zur anderen Breite
   * gehoert. `matchMedia` und nicht `resize`: Es meldet sich genau zweimal,
   * beim Ueberschreiten in jede Richtung, statt bei jedem Pixel. */
  var schwelle = window.matchMedia('(min-width:' + BREIT + 'px)');
  var horch = function () { anwenden(); };
  if (schwelle.addEventListener) { schwelle.addEventListener('change', horch); }
  else if (schwelle.addListener) { schwelle.addListener(horch); }
})();
