/* Das Blatt — Aktionsmenü, Sortieren, Zeilenaktionen.
 * ===========================================================================
 *
 * WOFUER. Mobil ein „⋯", das ein Blatt von unten öffnet: Griff, Titel, grosse
 * Zeilen, „Löschen" rot und abgesetzt, „Abbrechen". Am Desktop derselbe
 * Vorrat als Aufklappmenü unter dem Knopf „Aktionen".
 *
 * EIN MARKUP, ZWEI FORMEN — und dieses Skript entscheidet nicht, welche.
 * Es öffnet und schliesst; ob daraus ein Blatt oder ein Aufklappmenü wird,
 * sagt das Stylesheet (Abschnitt 10 und 18). Das ist der Grund, warum es so
 * kurz ist: Jede Zeile, die hier eine Fensterbreite abfragte, waere eine
 * zweite Stelle, an der die Schwelle 1024 steht.
 *
 * IMMER NUR EINES OFFEN. Zwei offene Menues uebereinander sind kein Zustand,
 * den jemand herbeifuehren will.
 *
 * SEIT WEB 19.6.0 FAEHRT ES AUF (Backlog Nr. 124, Weg b). Die Bewegung selbst
 * steht im Stylesheet — `.blatt{transform:translateY(100%)}` und
 * `.blatt.blatt-auf{transform:none}`. Dieses Skript tut dafuer genau zwei
 * Dinge, und beide sind noetig:
 *
 *   AUF: erst `hidden=false`, dann im NAECHSTEN Frame die Klasse. Beides im
 *   selben Frame rechnet der Browser zusammen und zeichnet nur den
 *   Endzustand — es gaebe keine Bewegung, nur einen Sprung.
 *
 *   ZU: erst die Klasse weg, `hidden` erst NACH der Rueckfahrt. Umgekehrt
 *   waere die Rueckfahrt unsichtbar: `display:none` haelt keine Bewegung an,
 *   es beendet sie.
 *
 * OB UEBERHAUPT GEFAHREN WIRD, ENTSCHEIDET DAS STYLESHEET — und das Skript
 * fragt es. Ab 1024 px ist das Blatt ein Aufklappmenue und hat
 * `transition:none`; wer Bewegung abbestellt hat, bekommt 0,01 ms
 * (Abschnitt 3 des Stylesheets). In beiden Faellen gibt es nichts abzuwarten,
 * und ein Skript, das trotzdem wartet, laesst das Aufklappmenue eine
 * Viertelsekunde zu lange stehen. Gefragt wird die GERECHNETE
 * `transition-duration` des Blattes; ist sie ~0, geht `hidden` sofort.
 *
 * UND EIN NACHLAUF, WEIL `transitionend` AUCH SONST AUSBLEIBEN KANN — ein
 * Frame, der verschluckt wird, ein Element, dessen Fahrt unterbrochen wird.
 * Bliebe das Skript dann stehen, waere das Blatt fuer immer im Fluss:
 * unsichtbar, aber klickbar und im Vorlesebaum. Die Dauer des Nachlaufs
 * kommt aus derselben gerechneten Zahl, nicht aus einer zweiten im Code.
 */
(function () {
  'use strict';

  var offenes = null;
  var offenerKnopf = null;
  var faehrtZu = null;      // Blatt, dessen Rueckfahrt laeuft
  var nachlauf = null;      // Zeitgeber dieser Rueckfahrt

  /* Wie lange faehrt DIESES Blatt gerade? Gefragt wird nicht das Token,
   * sondern der gerechnete Wert am Element — nur der kennt die Media-Abfrage
   * ab 1024 px (`transition:none`) und die abbestellte Bewegung (0,01 ms).
   * Zurueck kommen Millisekunden; 0 heisst „keine Fahrt". */
  function fahrtMs(el) {
    var roh = '';
    try {
      roh = (getComputedStyle(el).transitionDuration || '').split(',')[0].trim();
    } catch (e) { return 0; }
    var zahl = parseFloat(roh);
    if (!zahl) { return 0; }
    return /ms$/.test(roh) ? zahl : zahl * 1000;
  }

  /* Eine laufende Rueckfahrt vergessen, OHNE zu verstecken — fuer den Fall,
   * dass dasselbe Blatt wieder geoeffnet wird, waehrend es noch zufaehrt. */
  function rueckfahrtVergessen() {
    if (nachlauf) { clearTimeout(nachlauf); nachlauf = null; }
    if (faehrtZu) { faehrtZu.removeEventListener('transitionend', beiFahrtende); }
    faehrtZu = null;
  }

  /* Die Rueckfahrt zu Ende bringen: jetzt erst raus aus dem Fluss. */
  function verstecken() {
    var blatt = faehrtZu;
    rueckfahrtVergessen();
    if (blatt) { blatt.hidden = true; }
  }

  function beiFahrtende(ev) {
    /* Nur die eine Eigenschaft, die faehrt. Ohne die Abfrage beendete jede
     * andere Transition am selben Element die Rueckfahrt vorzeitig. */
    if (ev.propertyName !== 'transform') { return; }
    verstecken();
  }

  function zu() {
    if (!offenes) { return; }
    var blatt = offenes;
    verstecken();                       // ein anderes, das noch zufaehrt, sofort raus
    blatt.classList.remove('blatt-auf');
    var ms = fahrtMs(blatt);
    if (ms < 20) {
      /* Keine Fahrt — Aufklappmenue am Schreibtisch, oder Bewegung
       * abbestellt. Sofort raus; warten hiesse hier nur „spaeter". */
      blatt.hidden = true;
    } else {
      faehrtZu = blatt;
      blatt.addEventListener('transitionend', beiFahrtende);
      nachlauf = setTimeout(verstecken, Math.round(ms) + 120);
    }
    if (offenerKnopf) {
      offenerKnopf.setAttribute('aria-expanded', 'false');
      offenerKnopf.focus();
    }
    offenes = null; offenerKnopf = null;
  }

  function auf(knopf) {
    var id = knopf.getAttribute('data-blatt');
    var blatt = id && document.getElementById(id);
    if (!blatt) { return; }
    if (offenes === blatt) { zu(); return; }
    zu();
    /* Faehrt GENAU DIESES Blatt noch zu, wird es eingefangen statt versteckt;
     * faehrt ein anderes zu, hat `zu()` es schon aus dem Fluss genommen. */
    if (faehrtZu === blatt) { rueckfahrtVergessen(); }
    blatt.hidden = false;
    knopf.setAttribute('aria-expanded', 'true');
    offenes = blatt; offenerKnopf = knopf;
    /* ZWEI FRAMES, NICHT EINER. Nach `hidden=false` muss der Browser das
     * Element erst einmal gerechnet haben; ein einzelnes
     * `requestAnimationFrame` liegt in manchen Faellen noch vor diesem
     * Schritt, und dann sieht er keinen Wechsel, sondern nur den Endwert. */
    requestAnimationFrame(function () {
      requestAnimationFrame(function () { blatt.classList.add('blatt-auf'); });
    });
    var erste = blatt.querySelector('a[href],button:not([disabled])');
    if (erste) { erste.focus(); }
  }

  document.addEventListener('click', function (ev) {
    var ziel = ev.target.closest ? ev.target.closest('[data-blatt]') : null;
    if (ziel) { ev.preventDefault(); auf(ziel); return; }
    if (ev.target.closest && ev.target.closest('[data-blatt-zu]')) {
      ev.preventDefault(); zu(); return;
    }
    /* Ein Klick daneben schliesst — aber nicht der Klick auf einen Eintrag im
       Blatt selbst, der ja gerade seinen Weg gehen soll. */
    if (offenes && !offenes.contains(ev.target)) { zu(); }
  });

  document.addEventListener('keydown', function (ev) {
    if (offenes && ev.key === 'Escape') { ev.preventDefault(); zu(); }
  });

  /* Kleine Schliess-API fuer Seiten, die nach einer Wahl im Blatt selbst
   * schliessen wollen (Sortierblatt: Wahl getroffen -> Blatt zu). Mehr gibt
   * es absichtlich nicht — oeffnen laeuft ueber data-blatt. */
  window.edBlatt = { zu: zu };
})();
