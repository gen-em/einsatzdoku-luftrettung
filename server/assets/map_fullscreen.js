/* Vollbildmodus fuer Leaflet-Karten: ein wiederverwendbares Control fuer
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
        btn.innerHTML =
          '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
          'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
          '<path d="M8 3H5a2 2 0 0 0-2 2v3M16 3h3a2 2 0 0 1 2 2v3' +
          'M21 16v3a2 2 0 0 1-2 2h-3M8 21H5a2 2 0 0 1-2-2v-3"/></svg>';
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
})();
