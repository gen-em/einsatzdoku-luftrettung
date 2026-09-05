/* Gen-EM NAdoku — die Geraeteseite laedt nach, wenn das Geraet Ja gesagt hat
 * (S5 Paket B, E-S5-53).
 *
 * WOFUER. Nach der Eingabe des Codes steht die Karte „Am Geraet bestaetigen"
 * und wartet — auf eine Handlung, die woanders geschieht: Die Person schaut
 * auf die Uhr, drueckt dort Ja, schaut zurueck. Ohne diese Datei zeigte der
 * Bildschirm dann noch immer den alten Stand, und die einzige Auskunft waere
 * ein Satz gewesen, der zum Neuladen auffordert. Das ist die einzige Stelle
 * des ganzen Ablaufs, an der die Person nichts tun kann und trotzdem nicht
 * weiss, ob es geklappt hat.
 *
 * OHNE DIESE DATEI BLEIBT DER WEG VOLLSTAENDIG. Sie nimmt der Person einen
 * Handgriff ab, sie ersetzt keinen: Die Karte sagt auch ohne JavaScript, was
 * am Geraet zu tun ist und was danach passiert, und ein Neuladen von Hand
 * fuehrt zum selben Ergebnis. Deshalb steht hier auch nichts, was die Seite
 * erst aufbaut — sie ist fertig, wenn diese Datei laedt.
 *
 * SECHS BEDINGUNGEN, damit aus der Bequemlichkeit kein zweites Problem wird
 * (E-S5-53). Sie stehen unten je an ihrer Stelle noch einmal:
 *   1. Ein Endpunkt, der genau eine Frage beantwortet und KEINE Eingabe nimmt.
 *   2. Gefragt wird nach dem Vorgang der eigenen Sitzung, nicht nach „hat sich
 *      irgendetwas geaendert".
 *   3. Die Abfrage endet von selbst: Erfolg, Ablehnung, Fristende, drei
 *      Fehlversuche in Folge.
 *   4. Sie ruht, solange der Reiter im Hintergrund liegt.
 *   5. Ohne JavaScript bleibt der Weg vollstaendig (siehe oben).
 *   6. Der Sprung am Ende ist eine GET-Navigation OHNE Fragment, kein
 *      reload(). Warum beides so sein muss, steht unten bei 'gekoppelt' —
 *      es ist gemessen, nicht geraten.
 */
'use strict';
(() => {

  const kasten = document.getElementById('kopplung-warten');
  if (!kasten) { return; }   // andere Seite, anderer Zustand — nichts zu tun

  const anzeige = document.getElementById('kopplung-restzeit');
  const quelle  = kasten.dataset.quelle;
  const ziel    = kasten.dataset.ziel;
  if (!quelle || !ziel) { return; }

  /* DER TAKT. Fuenf Sekunden waeren zu traege fuer jemanden, der auf den
   * Bildschirm sieht; eine waere Betriebsamkeit. Drei Sekunden heissen im
   * Regelfall zwei bis vier Anfragen — so lange dauert es, an der Uhr Ja zu
   * druecken. Die Obergrenze zieht ohnehin die Frist: Nach zehn Minuten ist
   * Schluss, ganz gleich, was hier steht. */
  const TAKT_MS = 3000;
  const FEHLER_MAX = 3;

  let rest = parseInt(kasten.dataset.rest || '0', 10);
  let fehler = 0;
  let laeuft = true;
  let zeitgeber = null;

  /* Dieselbe Regel wie pair_restzeit_text() in kopplung_lib.php: volle
   * Minuten, unter einer Minute Sekunden. Zwei Fassungen derselben Regel, und
   * das ist hier nicht zu vermeiden — die eine rechnet beim Aufbau der Seite,
   * die andere waehrend sie steht. Wer eine aendert, aendert beide. */
  function restText(s) {
    if (s <= 0) { return 'abgelaufen'; }
    if (s < 60) { return s + ' Sekunden'; }
    const min = Math.floor(s / 60);
    return min === 1 ? 'eine Minute' : min + ' Minuten';
  }

  /* Der Text der Karte wird ERSETZT, nicht ergaenzt: Ein „Am Geraet
   * bestaetigen" neben einem „Am Geraet abgelehnt" waere ein Widerspruch auf
   * einem Bildschirm. Gebaut wird mit textContent, nicht mit innerHTML —
   * hier entsteht kein Markup, und wo keines entsteht, kann auch keines
   * eingeschleust werden. */
  function schlussText(text) {
    kasten.textContent = text;
  }

  function anhalten() {
    laeuft = false;
    if (zeitgeber !== null) { clearTimeout(zeitgeber); zeitgeber = null; }
  }

  function planen() {
    if (!laeuft) { return; }
    if (zeitgeber !== null) { clearTimeout(zeitgeber); }
    zeitgeber = setTimeout(fragen, TAKT_MS);
  }

  async function fragen() {
    if (!laeuft) { return; }

    /* BEDINGUNG 4: Im Hintergrund wird nicht gefragt. Ein Reiter, der seit
     * Stunden offen liegt, soll den Server nicht alle drei Sekunden anrufen;
     * und niemand sieht die Antwort. Wird der Reiter wieder sichtbar, holt
     * das Ereignis unten die Abfrage sofort nach — die Person erwartet dann
     * einen aktuellen Stand, nicht einen in drei Sekunden. */
    if (document.hidden) { planen(); return; }

    let antwort;
    try {
      const a = await fetch(quelle, { credentials: 'same-origin' });
      if (!a.ok) { throw new Error('HTTP ' + a.status); }
      antwort = await a.json();
      fehler = 0;
    } catch (e) {
      /* BEDINGUNG 3, erster Teil: Drei Fehlversuche in Folge, dann Ruhe. Ein
       * Skript, das gegen einen unerreichbaren Server endlos weiterfragt,
       * beschaeftigt das Geraet und sagt der Person nichts. Der Vorgang selbst
       * laeuft davon unberuehrt weiter — die Sitzung lebt auf dem Server, und
       * ein Neuladen von Hand zeigt den Stand. */
      fehler += 1;
      if (fehler >= FEHLER_MAX) {
        anhalten();
        schlussText('Die Verbindung zum Server ist gerade gestört. '
                  + 'Bestätige am Gerät wie gewohnt und lade diese Seite danach neu.');
        return;
      }
      planen();
      return;
    }

    switch (antwort.zustand) {
      case 'wartet':
        rest = parseInt(antwort.rest_s, 10) || 0;
        if (anzeige) { anzeige.textContent = restText(rest); }
        if (rest <= 0) {
          /* BEDINGUNG 3, zweiter Teil: Die Frist ist die harte Grenze. Der
           * Server sagt dasselbe beim naechsten Mal von selbst; hier wird
           * nicht darauf gewartet. */
          anhalten();
          schlussText('Die Zeit ist abgelaufen. Hol dir am Gerät einen neuen Code.');
          return;
        }
        planen();
        return;

      case 'gekoppelt':
        /* BEDINGUNG 6, und beide Haelften sind gemessen.
         *
         * KEIN reload(): Diese Karte steht im Regelfall auf einer Seite, die
         * aus einer Umleitung kam — dort waere Neuladen harmlos. Sie steht
         * aber AUCH auf der Antwort eines POST, wenn jemand waehrend des
         * Wartens ein anderes Geraet umbenennt, deaktiviert oder loescht.
         * Ein reload() haette dann „Formular erneut senden?" gefragt und im
         * schlimmsten Fall das Loeschen wiederholt.
         *
         * UND DAS ZIEL TRAEGT KEIN FRAGMENT: Die wartende Seite steht unter
         * `…?t=geraete#koppeln`. Eine Navigation, die nur das Fragment
         * aendert, ist KEINE Navigation — der Browser scrollt und fragt den
         * Server nicht (nachgemessen: „#koppeln → #geraeteliste" laedt nicht,
         * „#koppeln → ohne Fragment" laedt). Die Karte haette weiter auf ein
         * Geraet gewartet, das schon in der Liste stand. */
        anhalten();
        window.location.assign(ziel);
        return;

      case 'verworfen':
        anhalten();
        schlussText('Am Gerät wurde die Kopplung abgelehnt. Es ist nichts geschehen — '
                  + 'wenn du es doch verbinden willst, hol dir dort einen neuen Code.');
        return;

      case 'abgelaufen':
        anhalten();
        schlussText('Die Zeit ist abgelaufen. Hol dir am Gerät einen neuen Code.');
        return;

      default:
        /* 'keine' und alles Unbekannte: Diese Seite wartet nicht mehr auf
         * etwas, das der Server kennt. Stillschweigend aufhoeren — die Karte
         * sagt beim naechsten Aufbau ohnehin das Richtige. */
        anhalten();
        return;
    }
  }

  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && laeuft) { fragen(); }
  });

  /* Der erste Ruf kommt nach dem vollen Takt, nicht sofort: Die Seite wurde
   * gerade vom Server gebaut, ihre Auskunft ist keine Sekunde alt. Ein Ruf
   * beim Laden fragte dasselbe noch einmal. */
  planen();

})();
