/* Basis-Kartenlayer mit Umschalter: der bisherige Standard-OSM-Layer sowie
 * zwei topographische Varianten mit Hoehenlinien (OpenHikingMap, OpenTopoMap).
 * Beide zusaetzlichen Anbieter liefern reine Kartenkacheln anhand des
 * sichtbaren Kartenausschnitts -- es werden dabei keine Einsatz- oder
 * Patientendaten uebertragen, genau wie beim bisherigen OSM-Standardlayer
 * (kein Verstoss gegen das Prinzip "keine externen Anfragen mit
 * Standort-/Patientendaten", das sich auf Geocoding-Dienste bezieht).
 *
 * Beide Anbieter sind spendenfinanzierte Community-Server ohne
 * Verfuegbarkeits-Garantie -- die Attribution enthaelt deshalb bewusst den
 * jeweils geforderten Hinweis inkl. Spenden-Link (Nutzungsbedingungen
 * tile.openmaps.fr) bzw. Lizenzhinweis (OpenTopoMap, CC-BY-SA).
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

    standard.addTo(map);

    L.control.layers({
      'Standard': standard,
      'Wanderkarte (OpenHikingMap)': hiking,
      'Topographisch (OpenTopoMap)': topo
    }, null, { position: 'topright' }).addTo(map);
  };
})();
