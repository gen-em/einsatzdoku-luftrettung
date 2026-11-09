/* Gen-EM NAdoku — die Klappkarte öffnet sich, wenn man auf sie springt.
 * ===========================================================================
 *
 * WOFUER (Konzept OF, OF-02, E-OF-42). Status, Hintergrundjobs,
 * Servereinstellungen und Komplett-Backup tragen ihre Abschnitte als
 * Klappkarten (`<details class="karte karte-klappbar">`). Nach dem Speichern
 * leitet eine Seite auf die Karte um (`…#k-anmeldung`) — die Meldung steht
 * dann IN der Karte, und eine zugeklappte Karte verschluckte sie. Dasselbe
 * gilt für die Sprungmarken der Leiste und für jeden Verweis mit Anker.
 *
 * DER SERVER KANN ES NICHT: Der Anker geht nie zum Server. Deshalb dieses
 * Skript, im Gerüst geladen, ohne Abhängigkeiten.
 *
 * Es öffnet die Karte, auf die der Anker zeigt, oder die Karte, in der das
 * Ziel liegt — beim Laden und bei jedem Wechsel des Ankers. Es schliesst
 * nie etwas.
 */
(function () {
  'use strict';

  function oeffnen() {
    var id = decodeURIComponent((location.hash || '').slice(1));
    if (!id) { return; }
    var ziel = document.getElementById(id);
    if (!ziel) { return; }
    var karte = ziel.closest('details.karte-klappbar');
    while (karte) {
      if (!karte.open) { karte.open = true; }
      karte = karte.parentElement ? karte.parentElement.closest('details.karte-klappbar') : null;
    }
    /* Erst nach dem Aufklappen hat das Ziel seine Lage; der Browser hatte
     * beim Laden auf die zugeklappte Karte gesprungen. */
    if (ziel.scrollIntoView) { ziel.scrollIntoView(); }
  }

  oeffnen();
  window.addEventListener('hashchange', oeffnen);
})();
