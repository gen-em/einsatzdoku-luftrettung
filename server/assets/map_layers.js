/* Basis-Kartenlayer mit Umschalter: der bisherige Standard-OSM-Layer, zwei
 * topographische Varianten mit Hoehenlinien (OpenHikingMap, OpenTopoMap) und
 * seit Web 7.0.0 ein Satellitenbild. Alle Anbieter liefern reine Kartenkacheln
 * anhand des sichtbaren Kartenausschnitts -- es werden dabei keine Einsatz-
 * oder Patientendaten uebertragen, genau wie beim bisherigen OSM-Standardlayer
 * (kein Verstoss gegen das Prinzip "keine externen Anfragen mit
 * Standort-/Patientendaten", das sich auf Geocoding-Dienste bezieht).
 *
 * OpenHikingMap und OpenTopoMap sind spendenfinanzierte Community-Server ohne
 * Verfuegbarkeits-Garantie -- die Attribution enthaelt deshalb bewusst den
 * jeweils geforderten Hinweis inkl. Spenden-Link (Nutzungsbedingungen
 * tile.openmaps.fr) bzw. Lizenzhinweis (OpenTopoMap, CC-BY-SA).
 *
 * DAS SATELLITENBILD kommt von Esri („World Imagery"). Es ist der einzige
 * kostenfrei und ohne Schluessel nutzbare Luftbild-Dienst mit brauchbarer
 * Abdeckung der Alpen; Bedingung ist die Nennung der Bildquellen, die deshalb
 * in der Attribution steht. Fachlich ist er die Ergaenzung, die
 * Hoehenlinienkarten nicht leisten: Ob ein Einsatzort auf einer Wiese, im Wald
 * oder auf einem Parkplatz lag, sieht man nur im Luftbild.
 *
 * Der Layer ist NICHT der Standard. Er laedt deutlich groessere Kacheln, und
 * die Karte soll beim Oeffnen einer Einsatzansicht schnell dastehen -- wer das
 * Luftbild will, waehlt es.
 *
 * Aufruf je Karte: attachBaseLayers(map). Fuegt den Standardlayer der Karte
 * hinzu und haengt das Leaflet-eigene Layer-Control (kein Plugin, Teil von
 * Leaflet selbst) oben rechts an.
 *
 * UND SEIT WEB 15.3.1 STARTET ER DIE GROESSENUEBERWACHUNG (F-S8-P-10). Der
 * Grund, warum sie ausgerechnet hier steht und nicht in einer eigenen Datei:
 * Diese Funktion ist der EINE Aufruf, den jede der fuenf Karten der Anwendung
 * macht -- Tag, Einsatz, Zeitraum, Tagesspuren, Ortswahl. Eine zweite Datei
 * haette fuenf Einbindungen und fuenf Aufrufe gebraucht, und eine Seite, die
 * einen davon vergisst, faellt nicht auf: Sie zeigt eine halbe Karte. Genau
 * das war der Fehler, den die Ueberwachung behebt. */
(function () {
  'use strict';

  window.attachBaseLayers = function (map) {
    const standard = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap'
    });

    const hiking = L.tileLayer('https://tile.openmaps.fr/openhikingmap/{z}/{x}/{y}.png', {
      maxZoom: 18,
      attribution:
        '&copy; <a href="https://wiki.openstreetmap.org/wiki/OpenHikingMap">OpenHikingMap</a> ' +
        '&middot; <a href="https://openmaps.fr/donate">Spenden</a> ' +
        '&middot; &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    });

    const topo = L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
      maxZoom: 17,
      subdomains: 'abc',
      attribution:
        'Kartendaten: &copy; OpenStreetMap-Mitwirkende, SRTM &middot; Kartendarstellung: ' +
        '&copy; <a href="https://opentopomap.org">OpenTopoMap</a> (CC-BY-SA)'
    });

    /* Achtung Reihenfolge der Platzhalter: Dieser Dienst erwartet {z}/{y}/{x},
     * nicht {z}/{x}/{y} wie die drei anderen. Vertauscht liefert er
     * kommentarlos falsche oder leere Kacheln. */
    const satellit = L.tileLayer(
      'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19,
        attribution:
          'Luftbild: &copy; <a href="https://www.esri.com">Esri</a> ' +
          '&middot; Quellen: Esri, Maxar, Earthstar Geographics und die GIS-Nutzergemeinschaft'
      });

    standard.addTo(map);

    L.control.layers({
      'Standard': standard,
      'Wanderkarte (OpenHikingMap)': hiking,
      'Topographisch (OpenTopoMap)': topo,
      'Satellitenbild (Esri)': satellit
    }, null, { position: 'topright' }).addTo(map);

    groesseBeobachten(map);
  };

  /* ---------------------------------------------------------------------
   * DIE KARTE MUSS IHRE GROESSE NACHMESSEN  (F-S8-P-10)
   *
   * DER FEHLER. Leaflet misst seinen Behaelter EINMAL, bei L.map(), und
   * rechnet danach mit dem gemerkten Wert weiter. Es merkt von selbst nur
   * eines: dass sich das FENSTER aendert. Waechst der Behaelter, ohne dass
   * das Fenster sich ruehrt, laedt Leaflet Kacheln nur fuer den alten
   * Ausschnitt -- der Rest bleibt der Hintergrund von `.geo`, und
   * Herauszoomen hilft nicht, weil auch der Kachelbereich aus der gemerkten
   * Groesse gerechnet wird. Erst ein Verschieben deckt die Flaeche nach und
   * nach auf.
   *
   * WO ER AUFTRAT. Auf der Tagesuebersicht ab 1600 px: Dort steht die Karte
   * in der rechten Spalte des Rasters (E-P3-31) und waechst mit der
   * Einsatztabelle daneben -- und die entsteht erst, wenn die Daten da sind,
   * also NACH L.map(). Gemessen am 05.09.2026 bei 1920 x 1080:
   * Behaelter 400 x 840 px, Leaflet rechnete mit 400 x 324 px. 516 px,
   * 61 Prozent der Hoehe, bekamen nie eine Kachel. Unter 1600 px hat die
   * Karte eine feste Hoehe, und dort stimmte sie.
   *
   * DIE LOESUNG ist ein ResizeObserver auf dem Behaelter. Er greift bei
   * jedem Grund, aus dem eine Karte waechst: nachgeladene Daten, ein
   * aufgeklapptes Formular daneben, die Schublade, der Wechsel in den
   * Vollbildmodus, ein gedrehtes Handy. invalidateSize() aendert die
   * Kastengroesse nicht, es liest sie nur -- eine Rueckkopplung gibt es
   * nicht; der Vergleich mit der letzten Groesse und das
   * requestAnimationFrame buendeln trotzdem, damit ein Zug an der
   * Fensterkante nicht dreissig Neuberechnungen ausloest.
   *
   * OHNE ResizeObserver (Browser vor 2020) bleibt es beim alten Verhalten.
   * Das ist Absicht: Ein Zeitgeber, der sekundenweise nachmisst, kostet auf
   * jeder Karte dauerhaft Rechenzeit fuer einen Fall, den es dort nicht
   * mehr gibt.
   * ------------------------------------------------------------------- */
  function groesseBeobachten(map) {
    if (typeof ResizeObserver !== 'function') { return; }
    const el = map.getContainer();
    let breit = el.clientWidth, hoch = el.clientHeight, bild = 0;
    const beobachter = new ResizeObserver(function () {
      if (el.clientWidth === breit && el.clientHeight === hoch) { return; }
      breit = el.clientWidth;
      hoch  = el.clientHeight;
      if (bild) { cancelAnimationFrame(bild); }
      bild = requestAnimationFrame(function () {
        bild = 0;
        /* Ein Behaelter mit Hoehe 0 ist ein verborgener Behaelter (Dialog zu,
         * Karte eingeklappt). Dann waere die Rechnung sinnlos -- und beim
         * Aufklappen kommt der Beobachter ohnehin noch einmal. */
        if (el.clientWidth > 0 && el.clientHeight > 0) { map.invalidateSize(); }
      });
    });
    beobachter.observe(el);
    map.on('unload', function () { beobachter.disconnect(); });
  }
})();
