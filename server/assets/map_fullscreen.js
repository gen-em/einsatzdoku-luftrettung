/* Karten-Bedienelemente: Vollbild fuer alle Kartenseiten, dritte Groesse nur
 * fuer die Tagesuebersicht (Backlog Nr. 45, AP3 der Mockup-Runde).
 *
 * Vollbildmodus fuer Leaflet-Karten: ein wiederverwendbares Control fuer
 * alle Kartenseiten (Tagesuebersicht, Einsatzansicht, Zeitraum-Uebersicht).
 * Primaer die native Fullscreen-API auf dem Karten-Container. Faellt sie
 * aus -- relevant v. a. iOS Safari, das requestFullscreen() fuer beliebige
 * Elemente nicht unterstuetzt --, greift ein CSS-Overlay-Fallback (Klasse
 * "map-fs" auf dem Container, siehe style.css). In beiden Faellen wird nach
 * dem Umschalten map.invalidateSize() aufgerufen, sonst bleibt die
 * Kacheldarstellung bis zum naechsten Resize unvollstaendig.
 *
 * Aufruf je Karte: attachFullscreenControl(map). Der Zustand (Fallback
 * aktiv? Button-Referenz?) lebt in eigenen Closures pro Aufruf -- keine
 * globalen Variablen, damit mehrere Karten auf einer Seite nicht
 * kollidieren wuerden. */
(function () {
  'use strict';

  function fsElement() {
    return document.fullscreenElement || document.webkitFullscreenElement || null;
  }
  function apiVerfuegbar(el) {
    return !!(el.requestFullscreen || el.webkitRequestFullscreen);
  }
  function requestFs(el) {
    if (el.requestFullscreen) { return el.requestFullscreen(); }
    if (el.webkitRequestFullscreen) { return el.webkitRequestFullscreen(); }
  }
  function exitFs() {
    if (document.exitFullscreen) { return document.exitFullscreen(); }
    if (document.webkitExitFullscreen) { return document.webkitExitFullscreen(); }
  }

  window.attachFullscreenControl = function (map) {
    const container = map.getContainer();
    let fallbackAktiv = false;
    let btn = null;

    function istAktiv() { return fsElement() === container || fallbackAktiv; }

    function aktualisiereButton() {
      if (!btn) { return; }
      const aktiv = istAktiv();
      btn.classList.toggle('active', aktiv);
      const label = aktiv ? 'Vollbild verlassen' : 'Vollbild';
      btn.title = label;
      btn.setAttribute('aria-label', label);
    }

    function nachUmschalten() {
      // Der Container braucht nach dem Layoutwechsel einen Tick, bis er
      // seine endgueltige Groesse hat (v. a. beim Fallback-Overlay ohne
      // Fullscreen-API, wo kein eigenes Browser-Ereignis dafuer sorgt).
      setTimeout(function () { map.invalidateSize(); }, 60);
      aktualisiereButton();
    }

    function aufEsc(ev) {
      if (ev.key === 'Escape' && fallbackAktiv) { toggleFallback(false); }
    }

    function toggleFallback(an) {
      fallbackAktiv = an;
      container.classList.toggle('map-fs', an);
      document.body.classList.toggle('map-fs-lock', an);
      if (an) { document.addEventListener('keydown', aufEsc); }
      else { document.removeEventListener('keydown', aufEsc); }
      nachUmschalten();
    }

    const FullscreenControl = L.Control.extend({
      options: { position: 'topleft' },
      onAdd: function () {
        const wrap = L.DomUtil.create('div', 'leaflet-bar map-ctrl-fs');
        btn = L.DomUtil.create('a', '', wrap);
        btn.href = '#';
        // Vollbild-Symbol (vier Ecken-Pfeile), Inline-SVG statt externer
        // Icon-Bibliothek.
        /* Das Zeichen kommt aus dem Symbolvorrat (E-P3-18) — hier stand
         * einer der Inline-SVG-Pfade des Bestands. */
        btn.innerHTML = edSymbol('vollbild', 'symbol-gross', 'Vollbild');
        L.DomEvent.disableClickPropagation(wrap);
        L.DomEvent.on(btn, 'click', L.DomEvent.stop)
          .on(btn, 'click', function () {
            if (istAktiv()) {
              if (fsElement()) { exitFs(); } else { toggleFallback(false); }
            } else if (apiVerfuegbar(container)) {
              requestFs(container);
            } else {
              toggleFallback(true);
            }
          });
        aktualisiereButton();
        return wrap;
      }
    });

    map.addControl(new FullscreenControl());

    // Sowohl echtes Verlassen/Betreten (auch per ESC, von der API selbst
    // behandelt) als auch ein Wechsel bei einer ANDEREN Karte auf derselben
    // Seite loesen dieses Ereignis global aus -- ein zusaetzlicher
    // invalidateSize()-Aufruf fuer eine unbeteiligte Karte ist unschaedlich.
    document.addEventListener('fullscreenchange', nachUmschalten);
    document.addEventListener('webkitfullscreenchange', nachUmschalten);
  };

  /* ---- Dritte Kartengroesse (Backlog Nr. 45, M-MR-03, F-MR-7 bis F-MR-10) --
   *
   * WOFUER. Zwischen der Hoehe nach Fensterbreite (160/220/300 px) und dem
   * Vollbild lag nichts. Wer mehr von der Spur sehen wollte, musste die Seite
   * verlassen. Ein Knopf, ein Zustand — und je nach Breite zwei Wirkungen:
   * bis 1599 px wird die Karte hoeher, ab 1600 px breit (E-MR-16). Beides
   * macht dieselbe Klasse `.geo-gross`; WELCHE Wirkung sie hat, entscheidet
   * das Stylesheet (Abschnitt 30). Hier steht die Schwelle 1600 NICHT.
   *
   * NUR AUF DER TAGESUEBERSICHT. `attachFullscreenControl()` haengt an vier
   * Karten; dieses Control wird ausdruecklich einzeln gerufen. Die Einsatz-
   * ansicht und die Zeitraumuebersicht haben keine Liste unter der Karte,
   * die vom Hoeherwerden etwas haette.
   *
   * DER ZUSTAND WIRD GEMERKT (F-MR-8), je Browser und Geraet, nicht je Konto:
   * Wer am Schreibtisch gross arbeitet, will das am Handy nicht zwangslaeufig.
   * `localStorage` kann werfen (privates Fenster, geblockte Seitendaten) und
   * leer zurueckkommen — beides faengt `gemerkt()`/`merken()` ab, und die
   * Karte steht dann eben klein da.
   */
  var SCHLUESSEL = 'nadoku.karte-gross';

  function gemerkt() {
    try { return window.localStorage.getItem(SCHLUESSEL) === '1'; }
    catch (e) { return false; }
  }
  function merken(gross) {
    try { window.localStorage.setItem(SCHLUESSEL, gross ? '1' : '0'); }
    catch (e) { /* ohne Gedaechtnis, aber bedienbar */ }
  }

  window.attachGroessenControl = function (map) {
    var behaelter = map.getContainer();
    var knopf = null;

    function gross() { return behaelter.classList.contains('geo-gross'); }

    function beschriften() {
      if (!knopf) { return; }
      /* ZWEI BESCHRIFTUNGEN IN EINEM SATZ. Der Knopf tut je nach Breite etwas
       * anderes, und die Schwelle steht im Stylesheet — hier waere sie ein
       * zweites Mal. „vergroessern/verkleinern" deckt beide Wirkungen; das
       * Symbol daneben sagt, welche gerade gilt. */
      var text = gross() ? 'Karte verkleinern' : 'Karte vergrößern';
      knopf.title = text;
      knopf.setAttribute('aria-label', text);
      knopf.setAttribute('aria-pressed', gross() ? 'true' : 'false');
      knopf.classList.toggle('active', gross());
    }

    function umschalten(an) {
      behaelter.classList.toggle('geo-gross', an);
      merken(an);
      beschriften();
      /* OHNE invalidateSize() BLEIBEN DIE KACHELN AUF DER ALTEN HOEHE stehen,
       * und zwar bis zur naechsten Groessenaenderung des Fensters: Leaflet
       * merkt von einer Klasse nichts. Derselbe Tick-Abstand wie beim
       * Vollbild — der Behaelter braucht einen Durchgang, bis er sein neues
       * Mass hat. */
      setTimeout(function () { map.invalidateSize(); }, 60);
    }

    var GroessenControl = L.Control.extend({
      options: { position: 'topleft' },
      onAdd: function () {
        var wrap = L.DomUtil.create('div', 'leaflet-bar map-ctrl-groesse karte-groesse');
        knopf = L.DomUtil.create('a', '', wrap);
        knopf.href = '#';
        knopf.setAttribute('role', 'button');
        /* BEIDE ZEICHEN IM KNOPF (E-MR-19), das Stylesheet blendet je Breite
         * eines aus. `aria-hidden` an beiden: Die Auskunft steht im
         * aria-label, und ein Vorleser soll nicht zwei Zeichen ansagen, von
         * denen eines unsichtbar ist. */
        knopf.innerHTML = edSymbol('karte-gross', 'symbol-gross symbol-hoch')
                        + edSymbol('karte-breit', 'symbol-gross symbol-breit');
        L.DomEvent.disableClickPropagation(wrap);
        L.DomEvent.on(knopf, 'click', L.DomEvent.stop)
          .on(knopf, 'click', function () { umschalten(!gross()); });
        beschriften();
        return wrap;
      }
    });

    map.addControl(new GroessenControl());
    if (gemerkt()) { umschalten(true); }
  };
})();
