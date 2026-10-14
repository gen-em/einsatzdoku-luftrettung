/**
 * Ortsdaten öffnen — Spurstücke und Einsatzhülle im Browser (S11-04c).
 *
 * WOFÜR. Seit Web 22 liegen Spur, Koordinaten der Phasen und Reanimation nur
 * noch als Gerätehüllen auf dem Server (Format: `tools/proben/huelle/
 * referenz.py`); `api/mission.php` und `api/day.php` liefern sie aus, wie sie
 * liegen. Öffnen kann sie nur der Browser, mit dem privaten Schlüssel des
 * Kontos (`EdKontopaar.privat`). Diese Datei ist die EINE Stelle, die das tut
 * und daraus macht, was die Ansichten schon kennen: eine Punktfolge, Phasen
 * mit `lat`/`lon` und `track_idx`, Reanimationen als Ereignislisten. Die
 * Zeichenwege der Seiten bleiben, wie sie waren.
 *
 *   EdOrtsdaten.schluessel(ck)            privater und öffentlicher Teil
 *   EdOrtsdaten.spur(stuecke, art, ref, sperren, k)
 *                                         Punkte {seq, lat, lon, ele, ts}
 *   EdOrtsdaten.einsatz(m, k)             die Einsatzhülle, geöffnet, oder null
 *   EdOrtsdaten.fuerAnsicht(m, ck)        füllt m.track, m.phases, m.resus
 *   EdOrtsdaten.ladeplatz(el)             der Ladeplatz (ui_ladeplatz) mit
 *                                         300 ms Schwelle
 *
 * REGELN BEIM ÖFFNEN (E-S11-37, Konzept 3.4): Punkt i eines Stücks hat die
 * Sequenznummer `seq_from + i`; doppelte Nummern (ein Gerät, das seine Marke
 * verloren hat und anders stückelt) zählen einmal. Punkte in einer Sperrzeit
 * (ein herausgeschnittener Zeitraum) fallen weg. Höhe -32768 heißt: keine
 * (E-S11-39).
 *
 * Braucht: EdCrypto (crypto.js), EdKontopaar (kontopaar.js), EdFormat.
 */
(function () {
  'use strict';

  const HOEHE_UNBEKANNT = -32768;
  const b64 = (t) => Uint8Array.from(atob(t), (c) => c.charCodeAt(0));

  /** Ein Fehler mit benanntem Grund, damit die Seite sagen kann, was fehlt. */
  function fehler(grund, text) {
    const e = new Error(text);
    e.grund = grund;
    return e;
  }

  async function schluessel(ck) {
    if (typeof EdKontopaar === 'undefined') {
      throw fehler('kein_paar', 'Für dieses Konto ist noch kein Schlüsselpaar eingerichtet.');
    }
    const priv = await EdKontopaar.privat(ck);
    const pub = EdKontopaar.oeffentlich();
    if (!priv || !pub) {
      throw fehler('kein_paar', 'Für dieses Konto ist noch kein Schlüsselpaar eingerichtet.');
    }
    return { priv: priv, pub: pub };
  }

  function gesperrt(ts, sperren) {
    return (sperren || []).some((s) => ts >= s.von && ts <= s.bis);
  }

  async function spur(stuecke, art, ref, sperren, k) {
    const nach = new Map();
    for (const st of stuecke || []) {
      const innen = await EdCrypto.geraetehuelleOeffnen(b64(st.huelle), k.priv, k.pub, art, ref,
                                                        st.seq_from + ':' + st.punkte);
      const dv = new DataView(innen.buffer, innen.byteOffset, innen.byteLength);
      const n = Math.floor(innen.byteLength / 14);
      for (let i = 0; i < n; i++) {
        const ts = st.ts_von + dv.getUint32(i * 14 + 10);
        if (gesperrt(ts, sperren)) { continue; }
        const seq = st.seq_from + i;
        if (nach.has(seq)) { continue; }
        const h = dv.getInt16(i * 14 + 8);
        nach.set(seq, { seq: seq, lat: dv.getInt32(i * 14) / 1e7, lon: dv.getInt32(i * 14 + 4) / 1e7,
                        ele: h === HOEHE_UNBEKANNT ? null : h / 10, ts: ts });
      }
    }
    return [...nach.values()].sort((a, b) => a.seq - b.seq);
  }

  async function einsatz(m, k) {
    if (!m.ort_huelle) { return null; }
    const innen = await EdCrypto.geraetehuelleOeffnen(b64(m.ort_huelle), k.priv, k.pub,
                                                      'einsatz', m.client_ref, '');
    return JSON.parse(new TextDecoder().decode(innen));
  }

  /** Der Index des zeitlich nächsten Punkts — wie früher `track_idx` am Server. */
  function naechster(punkte, ts) {
    if (ts == null || !punkte.length) { return null; }
    let best = null, abstand = Infinity;
    punkte.forEach((p, i) => {
      const d = Math.abs(p.ts - ts);
      if (d < abstand) { abstand = d; best = i; }
    });
    return best;
  }

  /**
   * Eine Antwort von `api/mission.php` für die Einsatzansicht aufbereiten:
   * `m.track` ([lat, lon] je Punkt), `m.spur_punkte` (mit Höhe und Zeit),
   * `m.phases[i].lat/lon/track_idx` aus der Einsatzhülle, `m.resus` als
   * Ereignislisten wie bisher. Die Phasen werden über Nummer und Zeit
   * zugeordnet (beide Klartext); was nicht passt, über die Reihenfolge.
   */
  async function fuerAnsicht(m, ck) {
    const k = await schluessel(ck);
    const punkte = await spur(m.spur_stuecke, 'spur', m.client_ref, m.spur_sperren, k);
    m.spur_punkte = punkte;
    m.track = punkte.map((p) => [p.lat, p.lon]);
    const innen = await einsatz(m, k);
    const phasen = (innen && innen.phasen) || [];
    const frei = phasen.slice();
    (m.phases || []).forEach((p, i) => {
      let ort = null;
      const j = frei.findIndex((x) => x && x.phase === p.phase && m.started_ts + x.t === p.ts);
      if (j >= 0) { ort = frei[j]; frei[j] = null; } else if (phasen[i] && phasen[i].phase === p.phase) { ort = phasen[i]; }
      p.lat = ort && ort.lat != null ? Number(ort.lat) : null;
      p.lon = ort && ort.lon != null ? Number(ort.lon) : null;
      p.track_idx = naechster(punkte, p.ts);
    });
    const art = m.reanimationsarten || {};
    const uhr = (t) => EdFormat.uhrzeit(m.started_ts + t, m.zeitzone);
    const rea = (innen && innen.rea) || [];
    m.resus = rea.length ? rea.map((s) => [{ label: art.beginn || 'Beginn', time: uhr(s.t) }]
      .concat((s.ereignisse || []).map((e) => ({ label: art[e.art] || e.art, time: uhr(e.t) }))))
      : null;
    return m;
  }

  /**
   * Der Ladeplatz einer Stelle (`ui_ladeplatz()`): `zeige(text, still)`
   * merkt sich den Zustand und zeigt ihn erst nach 300 ms, `weg()` nimmt
   * ihn — und einen noch nicht abgelaufenen Zeitgeber — wieder fort.
   */
  function ladeplatz(el) {
    let uhr = null;
    const setze = (text, still) => {
      if (!el) { return; }
      const t = el.querySelector('.ladeplatz-text');
      if (t) { t.textContent = text; }
      el.classList.toggle('ladeplatz-still', !!still);
    };
    return {
      zeige(text, still) {
        setze(text, still);
        if (el && el.hidden && uhr === null) {
          uhr = setTimeout(() => { uhr = null; el.hidden = false; }, 300);
        }
      },
      weg() {
        if (uhr !== null) { clearTimeout(uhr); uhr = null; }
        if (el) { el.hidden = true; }
      },
    };
  }

  window.EdOrtsdaten = { schluessel: schluessel, spur: spur, einsatz: einsatz,
                         fuerAnsicht: fuerAnsicht, ladeplatz: ladeplatz };
})();
