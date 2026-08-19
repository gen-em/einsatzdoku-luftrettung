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
 * Leaflet selbst) oben rechts an. */
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
  };
})();
