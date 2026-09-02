/* Der Marker-Satz der Karten — EINE Fassung für alle drei Kartenseiten.
 * ===========================================================================
 *
 * WOFUER (P3/O3, E-P3-40). Tages-, Einsatz- und Zeitraumkarte zeigten ihre
 * Marker bisher jede fuer sich: index.php und einsatz.php trugen denselben
 * SVG-Pfad fuer den Karten-Pin WOERTLICH doppelt, und Standort wie Zielklinik
 * waren nur als farbige Pins zu ahnen. Jetzt definiert dieses Modul den Satz
 * einmal:
 *
 *   Standort      Haus im weissen, dunkelblau umrandeten Schild + Namensschild
 *   Zielklinik    Klinik im selben Schild + Namensschild
 *   Einsatzort    oranger Kreis mit Pin
 *   Start         blauer Ring UM das Schild des Ortes, an dem die Spur beginnt
 *   Ende          roter Ring; beides am selben Ort ein Doppelring
 *   Pfeile        Richtungspfeile auf der Spur, erst ab einer Zoomstufe, bei
 *                 der sie nicht gedraengt stehen
 *
 * FARBEN AUS DEN TOKEN. Die Spurfarben stehen in :root (--spur-1..8,
 * --spur-ruhe); dieses Modul liest sie ueber getComputedStyle. Die alte
 * COLORS-Liste in index.php mit fuenf markenfremden Werten (F-P3-H) ist damit
 * fort — eine zweite Farbliste im Code gibt es nicht mehr.
 *
 * Die Zeichnung der Marker steht im STYLESHEET (Abschnitt 21), nicht hier:
 * Dieses Modul baut nur Markup aus edSymbol() und den Klassen des Bausteins.
 * Erwartet: Leaflet (L), assets/symbol.js (edSymbol).
 */
(function (global) {
  'use strict';

  var wurzel = null;
  function token(name) {
    if (!wurzel) { wurzel = getComputedStyle(document.documentElement); }
    return wurzel.getPropertyValue(name).trim();
  }

  /** Spurfarbe des i-ten Einsatzes eines Tages (0-basiert, zyklisch). */
  function spurFarbe(i) { return token('--spur-' + ((i % 8) + 1)); }
  function ruheFarbe()  { return token('--spur-ruhe'); }

  function esc(s) { return EdHtml.escape(String(s)); }

  /* ---- Schilder (Standort, Zielklinik) --------------------------------- */

  /* ---- Der Markerversatz, und warum er entstand (S3/AP7) ---------------
   *
   * Bis Web 12.3.1 sassen Standort und Zielklinik umso weiter oestlich, je
   * weiter herausgezoomt wurde. Die Ursache ist eine Kette aus drei Gliedern,
   * von denen jedes fuer sich richtig aussah:
   *
   *  1  `.geo-schild` ist eine Flex-SPALTE mit `align-items:center`. Das
   *     Wurzelelement wird damit so breit wie sein BREITESTES Kind.
   *  2  Das breiteste Kind war das Namensschild (`white-space:nowrap`),
   *     nicht der Kasten. Bei „Klinikum Immenstadt" rund 150 px statt 44.
   *  3  `iconSize: null` laesst Leaflet die Groesse aus dem Markup nehmen,
   *     und `iconAnchor: [22, 22]` verankerte auf 22 px vom linken Rand DES
   *     WURZELELEMENTS — also rund 50 px links der Kastenmitte.
   *
   * Das ergibt einen KONSTANTEN Pixelversatz: herausgezoomt sind dieselben
   * 50 px Kilometer, hereingezoomt Meter. Er waechst mit der Laenge des
   * Namens — was den Fehler so lange harmlos aussehen liess.
   *
   * Mit der Streichung des Namensschilds verschwaende er von selbst. Die
   * Behebung setzt `iconSize` TROTZDEM ausdruecklich: Wer dem Marker
   * kuenftig etwas danebenstellt — eine Plakette, eine Zahl —, traegt den
   * Fehler sonst wieder ein, und zwar wieder ohne Fehlermeldung.
   *
   * ring: '' | 'start' | 'ende' | 'beide' — Ringe der Spur (E-P3-33). Sie
   * liegen als box-shadow AUSSERHALB des Kastens und aendern seine Groesse
   * nicht; der Anker bleibt richtig. */
  var SCHILD_PX = 36;

  function schild(symbol, ring) {
    var k = 'geo-schild' + (ring ? ' geo-ring-' + ring : '');
    var html = '<span class="' + k + '">'
             + '<span class="geo-schild-kasten">' + edSymbol(symbol) + '</span>'
             + '</span>';
    return L.divIcon({ className: '', html: html,
      iconSize: [SCHILD_PX, SCHILD_PX],
      iconAnchor: [SCHILD_PX / 2, SCHILD_PX / 2] });
  }

  /* Der zweite Parameter ist der NAME und bleibt in der Aufrufliste, obwohl
   * das Schild ihn seit S3/AP7 nicht mehr zeigt: Die drei Kartenseiten geben
   * ihn weiter, und er landet im Popup, das ihre Aufrufer selbst binden.
   * Ihn hier zu streichen hiesse, drei Aufrufstellen anzufassen, ohne dass
   * sich etwas aendert. */
  function markerStandort(latlng, name, ring) {
    return L.marker(latlng, { icon: schild('haus', ring || ''),
      title: name || '', keyboard: false, zIndexOffset: 500 });
  }
  function markerZiel(latlng, name, ring) {
    return L.marker(latlng, { icon: schild('klinik', ring || ''),
      title: name || '', keyboard: false, zIndexOffset: 500 });
  }

  /* ---- Einsatzort: oranger Kreis mit Pin --------------------------------
   *
   * DIE MASSE STEHEN HIER UND IM STYLESHEET, und das ist bewusst: Leaflet
   * braucht sie als Zahl, das Stylesheet als Token. Wer eines aendert,
   * aendert beides — sonst wandert der Anker. Token: --geo-kreis,
   * --geo-schild. */
  var KREIS_PX = 32;

  function markerEinsatzort(latlng, titel) {
    var icon = L.divIcon({ className: '',
      html: '<span class="geo-kreis">' + edSymbol('einsatzort') + '</span>',
      iconSize: [KREIS_PX, KREIS_PX], iconAnchor: [KREIS_PX / 2, KREIS_PX / 2] });
    var m = L.marker(latlng, { icon: icon, keyboard: false, zIndexOffset: 400 });
    if (titel) { m.bindPopup(titel); }
    return m;
  }

  /* ---- Ring ohne Schild (Start/Ende der Spur abseits von Standort und
   * Ziel) — derselbe Farbcode wie die Ringe am Schild: blau = Start,
   * rot = Ende, Doppelring = beides. */
  function markerRing(latlng, art, titel) {
    var icon = L.divIcon({ className: '',
      html: '<span class="geo-ringpunkt geo-ringpunkt-' + art + '"></span>',
      iconSize: [16, 16], iconAnchor: [8, 8] });
    var m = L.marker(latlng, { icon: icon, keyboard: false, zIndexOffset: 350 });
    if (titel) { m.bindPopup(titel); }
    return m;
  }

  /* ---- Kleiner Punkt (Abfahrtort der Luftlinie) ------------------------- */
  function markerPunkt(latlng, farbe, titel) {
    var icon = L.divIcon({ className: '',
      html: '<span class="geo-punkt" style="background:' + esc(farbe) + '"></span>',
      iconSize: [12, 12], iconAnchor: [6, 6] });
    var m = L.marker(latlng, { icon: icon, keyboard: false });
    if (titel) { m.bindPopup(titel); }
    return m;
  }

  /* ---- Richtungspfeile auf der Spur (E-P3-33/40) ------------------------
   *
   * ERST AB EINER ZOOMSTUFE, BEI DER SIE NICHT GEDRAENGT STEHEN. Statt einer
   * festen Stufe wird in BILDSCHIRMPIXELN gerechnet: Ein Pfeil alle ~140 px
   * entlang der Spur, und nur, wenn die ganze Spur laenger als zwei
   * Abstaende ist. Beim Zoomen wird neu verteilt — herausgezoomt verschwinden
   * die Pfeile von selbst, weil die Spur kuerzer als die Schwelle wird.
   *
   * Kein Zusatzmodul (Konzeptvorgabe O3): rund vierzig Zeilen Projektion
   * reichen. Die Pfeile sind reine Anzeige — keyboard:false, kein Popup. */
  var ABSTAND_PX = 140;

  function pfeilIcon(winkelGrad) {
    return L.divIcon({ className: '',
      html: '<span class="geo-pfeil" style="transform:rotate(' + Math.round(winkelGrad) + 'deg)">'
          + edSymbol('pfeil-hoch') + '</span>',
      iconSize: [20, 20], iconAnchor: [10, 10] });
  }

  function pfeile(map, gruppe, latlngs) {
    var ebene = L.layerGroup().addTo(gruppe);

    function verteile() {
      ebene.clearLayers();
      if (!latlngs || latlngs.length < 2) { return; }
      var punkte = latlngs.map(function (p) { return map.latLngToLayerPoint(p); });
      var gesamt = 0;
      for (var i = 1; i < punkte.length; i++) {
        gesamt += punkte[i].distanceTo(punkte[i - 1]);
      }
      if (gesamt < ABSTAND_PX * 2) { return; }   // zu kurz: keine Pfeile
      var naechster = ABSTAND_PX;
      var gelaufen = 0;
      for (var j = 1; j < punkte.length; j++) {
        var a = punkte[j - 1], b = punkte[j];
        var stueck = b.distanceTo(a);
        while (stueck > 0 && naechster <= gelaufen + stueck) {
          var f = (naechster - gelaufen) / stueck;
          var x = a.x + (b.x - a.x) * f;
          var y = a.y + (b.y - a.y) * f;
          /* pfeil-hoch zeigt nach OBEN; atan2 liefert den Winkel zur x-Achse.
           * +90 dreht die Spitze in Laufrichtung. */
          var winkel = Math.atan2(b.y - a.y, b.x - a.x) * 180 / Math.PI + 90;
          ebene.addLayer(L.marker(map.layerPointToLatLng(L.point(x, y)),
            { icon: pfeilIcon(winkel), keyboard: false,
              interactive: false, zIndexOffset: 300 }));
          naechster += ABSTAND_PX;
        }
        gelaufen += stueck;
      }
    }

    map.on('zoomend', verteile);
    verteile();
    /* Aufraeumen haengt an der Gruppe: Wer sie leert oder entfernt, soll den
     * Zoom-Zuhoerer nicht behalten. */
    ebene.on('remove', function () { map.off('zoomend', verteile); });
    return ebene;
  }

  global.EdGeo = { spurFarbe: spurFarbe, ruheFarbe: ruheFarbe,
                   markerStandort: markerStandort, markerZiel: markerZiel,
                   markerEinsatzort: markerEinsatzort, markerPunkt: markerPunkt,
                   markerRing: markerRing, pfeile: pfeile };
})(window);
