/* Gen-EM NAdoku — die abwählbare Segmentwahl (`.segment-abwaehlbar`).
 * ===========================================================================
 *
 * WOFUER (Konzept OF, OF-02, E-OF-33). Die Transportart (Luft, Boden,
 * Ambulant) ist eine Wahl aus dreien ODER keine — „offen" ist ein Zustand,
 * kein viertes Feld. Ein Radio laesst sich im Browser aber nicht abwaehlen.
 * Dieses Skript tut genau das: Ein zweiter Klick (oder Leertaste) auf das
 * gewaehlte Feld waehlt das verborgene Radio ohne Wert (`.segment-leer`),
 * das `ui_segment(['abwaehlbar' => true])` vorn in die Gruppe setzt.
 *
 * WARUM DER ZUSTAND VOR DEM KLICK: Beim `click` ist das Radio schon
 * gewaehlt; ob es das VORHER war, weiss nur, wer es vorher gefragt hat —
 * `pointerdown` merkt es. Die Leertaste wird gesondert behandelt (unten).
 *
 * Ohne Skript bleibt das Formular gueltig; abwaehlen geht dann nicht.
 */
(function () {
  'use strict';

  function merken(e) {
    var box = e.target.closest ? e.target.closest('.segment-abwaehlbar') : null;
    if (!box) { return; }
    var radio = e.target.matches('input') ? e.target
              : (e.target.htmlFor ? document.getElementById(e.target.htmlFor) : null);
    if (radio) { radio.dataset.war = radio.checked ? '1' : ''; }
  }

  function abwaehlen(radio) {
    var leer = radio.closest('.segment-abwaehlbar').querySelector('.segment-leer');
    if (!leer) { return; }
    leer.checked = true;
    leer.dispatchEvent(new Event('change', { bubbles: true }));
    leer.dispatchEvent(new Event('input', { bubbles: true }));
  }

  document.addEventListener('pointerdown', merken, true);
  /* DIE LEERTASTE SELBST: Auf einem schon gewaehlten Radio loesen Chromium
   * und WebKit keinen `click` aus, Firefox schon — gemessen am 08.11.2026.
   * Deshalb wird sie hier abgefangen und der Klick unterdrueckt, in allen
   * drei gleich. Die Pfeiltasten bleiben dem Browser. */
  document.addEventListener('keydown', function (e) {
    if (e.key !== ' ' && e.key !== 'Spacebar') { return; }
    var radio = e.target;
    if (!radio.matches || !radio.matches('.segment-abwaehlbar input.segment-box:not(.segment-leer)')) { return; }
    if (!radio.checked) { return; }
    e.preventDefault();
    abwaehlen(radio);
  }, true);

  document.addEventListener('click', function (e) {
    var radio = e.target;
    if (!radio.matches || !radio.matches('.segment-abwaehlbar input.segment-box:not(.segment-leer)')) { return; }
    if (radio.dataset.war !== '1') { return; }
    radio.dataset.war = '';
    abwaehlen(radio);
  });
})();
