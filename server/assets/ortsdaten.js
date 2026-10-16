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
 *   EdOrtsdaten.melden(e, art, id)        eine Hülle, die nicht aufging, an
 *                                         den Reiter System (S11-04d); die
 *                                         Art aus dem Fehler geht vor
 *   EdOrtsdaten.einsatzZeiten(e)          die Zeiten des Formulars, wie der
 *                                         Server sie rechnet (S11-05b)
 *   EdOrtsdaten.einsatzInnen(z, orte)     Innenform der Einsatzhülle
 *   EdOrtsdaten.einsatzVerschliessen(i, ref)  verschließen, Base64
 *   EdOrtsdaten.ortshoehe(punkte, phasen) Höhe des Einsatzorts (S11-05b)
 *   EdOrtsdaten.stueckeBauen(punkte, art, ref, {neu})  Punkte → Stücke
 *                                         (S11-05c: Schneiden, Löschen)
 *   EdOrtsdaten.schluesselAus(pkcs8)      ein FREMDES Paar aus seinem privaten
 *                                         Teil (Freigabe, S11-06c)
 *   EdOrtsdaten.spur1Lesen(b64)           ein SPUR1-Blob einer Sicherung
 *                                         (Nutzlast 8 bis 12) → Punkte
 *                                         (S11-06b)
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

  /* Vor jedem Gebrauch des Schlüssels: ein Paar, das nach dem Aufbau der
     Seite entstand, abwarten bzw. nachholen (S11-05e, F-S11-61). */
  async function paarBereit() {
    if (typeof EdKontopaar !== 'undefined' && EdKontopaar.bereit) { await EdKontopaar.bereit(); }
  }

  async function schluessel(ck) {
    if (typeof EdKontopaar === 'undefined') {
      throw fehler('kein_paar', 'Für dieses Konto ist noch kein Schlüsselpaar eingerichtet.');
    }
    await paarBereit();
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
                                                        st.seq_from + ':' + st.punkte)
        .catch((e) => { e.art = art; throw e; });
      const dv = new DataView(innen.buffer, innen.byteOffset, innen.byteLength);
      const n = Math.floor(innen.byteLength / 14);
      for (let i = 0; i < n; i++) {
        const ts = st.ts_von + dv.getUint32(i * 14 + 10);
        if (gesperrt(ts, sperren)) { continue; }
        const seq = st.seq_from + i;
        if (nach.has(seq)) { continue; }
        const h = dv.getInt16(i * 14 + 8);
        nach.set(seq, { seq: seq, lat: dv.getInt32(i * 14) / 1e7, lon: dv.getInt32(i * 14 + 4) / 1e7,
                        ele: h === HOEHE_UNBEKANNT ? null : h, ts: ts });
      }
    }
    return [...nach.values()].sort((a, b) => a.seq - b.seq);
  }

  async function einsatz(m, k) {
    if (!m.ort_huelle) { return null; }
    const innen = await EdCrypto.geraetehuelleOeffnen(b64(m.ort_huelle), k.priv, k.pub,
                                                      'einsatz', m.client_ref, '')
      .catch((e) => { e.art = 'einsatz'; throw e; });
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

  /**
   * Eine Hülle ging nicht auf — das soll die BetreiberIn erfahren (M-S11-01,
   * Zustand d; S11-04d): ein Gerät, das falsch verschließt, oder ein Eingriff
   * in die Datenbank. Gemeldet wird nur Art, Kennung und Grund, nie Inhalt.
   * Ein fehlendes Schlüsselpaar ist kein Befund über die Hülle und wird nicht
   * gemeldet; ein Fehler ohne benannten Grund heißt `unbekannt`. Die Seite
   * wartet nicht darauf, und ein Fehlschlag der Meldung bleibt still.
   */
  const GRUENDE = ['laenge', 'fassung', 'schluessel', 'pruefwert', 'polsterung'];
  function melden(e, art, id) {
    if (e && e.grund === 'kein_paar') { return; }
    if (typeof EdApi === 'undefined' || !id) { return; }
    const grund = e && GRUENDE.includes(e.grund) ? e.grund : 'unbekannt';
    EdApi.postJson('api/kontopaar.php', { aktion: 'oeffnen_fehler', art: (e && e.art) || art, id: id,
                                          grund: grund })
      .catch(() => {});
  }

  /**
   * DIE ZEITEN DES FORMULARS, IM BROWSER GERECHNET (S11-05b, E-S11-45).
   *
   * Warum hier: Die Reanimation steht nur in der Einsatzhülle, und die
   * verschließt der Browser; ihre Zeiten (Sekunden seit Einsatzbeginn,
   * E-S11-40) muss er deshalb selbst rechnen. Die Regeln sind die von
   * `einsatz_form.php` beim Speichern, Zeile für Zeile:
   *   - Phasen: Nummer 2 bis 9, eine Zeile ohne Zeit zählt nicht; sortiert
   *     nach Nummer, bei gleicher Nummer nach Eingabereihenfolge.
   *   - Tageswechsel nur beim Nachtragen: Liegt die erste Phase vor dem
   *     Dienstbeginn, gehört der Einsatz zum Folgetag.
   *   - Mitternacht: Liegt eine Phase vor der vorigen, ein Tag weiter.
   *   - Reanimation: Beginn nie vor dem Einsatzbeginn, jedes Ereignis nie
   *     vor dem vorigen — sonst Folgetag.
   * Die Phasenzeiten gehen im Klartext mit, und der Server vergleicht sie
   * mit seiner Rechnung: Laufen die beiden je auseinander, wird nichts
   * gespeichert, statt eine Hülle mit verschobenen Zeiten abzulegen.
   *
   * @param {object} e  { tag, neu, dienstbeginn ('HH:MM'|null), tz,
   *                      phasen: [[nr, 'HH:MM'], …],
   *                      reas: [{ start, ev: [{ typ, zeit }] }],
   *                      grenzen: { sitzungen, ereignisse }, arten: {…} }
   * @returns {{ fehler: string|null, tag: string, phasen: number[][],
   *             beginn: number|null, rea: object[] }}
   */
  function einsatzZeiten(e) {
    const aus = { fehler: null, tag: e.tag, phasen: [], beginn: null, rea: [] };
    const minuten = (w) => {
      const m = /^(\d{1,2}):(\d{2})$/.exec(String(w || '').trim());
      return m ? Number(m[1]) * 60 + Number(m[2]) : null;
    };
    const zeit = (hhmm, versatz) => EdFormat.ortszeitZuEpoch(aus.tag, hhmm, versatz, e.tz);
    const gesammelt = [];
    (e.phasen || []).forEach(([nr, t], idx) => {
      t = String(t || '').trim();
      nr = parseInt(nr, 10);
      if (t === '' || !(nr >= 2 && nr <= 9)) { return; }
      gesammelt.push({ nr: nr, t: t, idx: idx });
    });
    gesammelt.sort((a, b) => (a.nr - b.nr) || (a.idx - b.idx));
    const erst = gesammelt.length ? minuten(gesammelt[0].t) : null;
    const grenze = e.dienstbeginn != null ? minuten(e.dienstbeginn) : null;
    if (e.neu && erst !== null && grenze !== null && erst < grenze) {
      const d = new Date(aus.tag + 'T00:00:00Z');
      d.setUTCDate(d.getUTCDate() + 1);
      aus.tag = d.toISOString().slice(0, 10);
    }
    let vorher = null, versatz = 0;
    for (const z of gesammelt) {
      let ts = zeit(z.t, versatz);
      if (ts !== null && vorher !== null && ts < vorher) { versatz += 1; ts = zeit(z.t, versatz); }
      if (ts === null) { aus.fehler = 'Ungültige Uhrzeit in den Phasen.'; return aus; }
      aus.phasen.push([z.nr, ts]);
      vorher = ts;
    }
    if (!aus.phasen.length) { aus.fehler = 'Mindestens eine Phase mit Uhrzeit eintragen.'; return aus; }
    aus.beginn = aus.phasen[0][1];

    const reas = e.reas || [];
    if (reas.length > e.grenzen.sitzungen) {
      aus.fehler = 'Zu viele Reanimationen (höchstens ' + e.grenzen.sitzungen + ').';
      return aus;
    }
    const reaZeit = (hhmm, nichtVor) => {
      const ts = zeit(hhmm, 0);
      if (ts === null) { return null; }
      return ts < nichtVor ? zeit(hhmm, 1) : ts;
    };
    for (const s of reas) {
      const start = String(s.start || '').trim();
      const getippt = (s.ev || []).map((x) => [String(x.typ || '').trim(), String(x.zeit || '').trim()])
        .filter((x) => x[1] !== '');
      if (start === '' && !getippt.length) { continue; }
      if (start === '') { aus.fehler = 'Zu einer Reanimation fehlt der Reanimationsbeginn.'; return aus; }
      if (getippt.length > e.grenzen.ereignisse) {
        aus.fehler = 'Zu viele Ereignisse in einer Reanimation (höchstens ' + e.grenzen.ereignisse + ').';
        return aus;
      }
      const rStart = reaZeit(start, aus.beginn);
      if (rStart === null) { aus.fehler = 'Ungültige Uhrzeit beim Reanimationsbeginn.'; return aus; }
      let vor = rStart;
      const ereignisse = [];
      for (const [art, t] of getippt) {
        if (!Object.prototype.hasOwnProperty.call(e.arten, art) || art === 'beginn') {
          aus.fehler = 'Unbekannte Art eines Reanimationsereignisses.';
          return aus;
        }
        const ts = reaZeit(t, vor);
        if (ts === null) { aus.fehler = 'Ungültige Uhrzeit bei einem Reanimationsereignis.'; return aus; }
        ereignisse.push({ art: art, t: ts - aus.beginn });
        vor = ts;
      }
      aus.rea.push({ t: rStart - aus.beginn, ereignisse: ereignisse });
    }
    return aus;
  }

  /**
   * Die Innenform der Einsatzhülle (E-S11-40) aus gerechneten Zeiten und den
   * Orten der Phasen. `orte` ist eine Liste {phase, lat, lon} in der
   * bisherigen Reihenfolge; je Phasennummer wird der Reihe nach vergeben —
   * dieselbe Regel wie beim Rückimport (`api/import_commit.php`): Wer eine
   * Zeit ändert, behält den Ort seiner Phase.
   */
  function einsatzInnen(z, orte) {
    const vorrat = new Map();
    (orte || []).forEach((o) => {
      if (!vorrat.has(o.phase)) { vorrat.set(o.phase, []); }
      vorrat.get(o.phase).push(o);
    });
    const phasen = z.phasen.map(([nr, ts]) => {
      const o = (vorrat.get(nr) || []).shift();
      return { phase: nr, t: ts - z.beginn,
               lat: o && o.lat != null ? Number(o.lat) : null,
               lon: o && o.lon != null ? Number(o.lon) : null };
    });
    return { fassung: 1, phasen: phasen, rea: z.rea };
  }

  /**
   * DIE HÖHE DES EINSATZORTS (S11-05b) — die Regel von
   * `compute_site_elevation()` (`site_elevation_lib.php`), die der Server
   * seit S11 nicht mehr rechnen kann, weil er die Spur nicht sieht:
   *   - Bezug: die früheste Phase 5, sonst die früheste Phase 6;
   *   - Wert: die Höhe des zeitlich nächsten Punkts, höchstens 300 s
   *     entfernt, und er muss selbst eine haben — kein Ausweichen auf
   *     Nachbarn; gerundet wie PHPs `round()` (von der Null weg).
   *   - Liefert `null`, wenn es keinen gibt. Ob ein vorhandener Wert dann
   *     stehen bleibt (ausgedünnte Spur), entscheidet der Aufrufer.
   * @param {Array} punkte  aus `spur()`: {ts, ele}
   * @param {Array} phasen  [[nr, ts], …]
   */
  function ortshoehe(punkte, phasen) {
    let bezug = null;
    for (const nr of [5, 6]) {
      const t = phasen.filter((p) => p[0] === nr).map((p) => p[1]).sort((a, b) => a - b);
      if (t.length) { bezug = t[0]; break; }
    }
    if (bezug === null) { return null; }
    let abstand = Infinity, hoehe = null;
    for (const p of punkte || []) {
      const d = Math.abs(p.ts - bezug);
      if (d < abstand) { abstand = d; hoehe = p.ele; }
    }
    if (hoehe == null || abstand > 300) { return null; }
    return Math.sign(hoehe) * Math.round(Math.abs(hoehe));
  }

  /** Uint8Array → Base64, in Blöcken (große Stücke sprengen sonst den Stapel). */
  function zuB64(h) {
    let s = '';
    for (let i = 0; i < h.length; i += 0x8000) { s += String.fromCharCode.apply(null, h.subarray(i, i + 0x8000)); }
    return btoa(s);
  }

  /** Rundung wie PHPs `round()`: von der Null weg. */
  const runde = (x) => Math.sign(x) * Math.round(Math.abs(x));

  /**
   * PUNKTE ZU STÜCKEN (S11-05c) — die Gegenrichtung zu `spur()`, für die
   * Schreibwege des Browsers: Schneiden (die Punkte im Zeitraum als Spur des
   * neuen Einsatzes) und Löschen (die übrigen Punkte des Segments neu).
   * Innenform wie `gh_spur_innen()` und `referenz.py`: je Punkt 14 Byte,
   * big-endian, Höhe in ganzen Metern (E-S11-66, bis zum 16.10.2026
   * Dezimeter), -32768 = keine (E-S11-39).
   *
   * `neu: true` nummeriert ab 0 durch (ein neuer Einsatz hat eine eigene
   * Zählung); sonst bleiben die Sequenznummern, und ein Stück endet an jeder
   * Lücke — Punkt i eines Stücks muss `seq_from + i` sein. Höchstens 2000
   * Punkte je Stück (`LIMIT_TRACKPUNKTE_ANFRAGE`).
   *
   * @param {Array} punkte  aus `spur()`: {seq, lat, lon, ele, ts}, sortiert
   * @returns {Promise<Array>} [{seq_from, punkte, ts_von, ts_bis, huelle}]
   */
  async function stueckeBauen(punkte, art, ref, o) {
    o = o || {};
    await paarBereit();
    const pub = (typeof EdKontopaar !== 'undefined') ? EdKontopaar.oeffentlich() : null;
    if (!pub) { throw fehler('kein_paar', 'Für dieses Konto ist noch kein Schlüsselpaar eingerichtet.'); }
    const liste = punkte.map((p, i) => Object.assign({}, p, { seq: o.neu ? i : p.seq }));
    const gruppen = [];
    for (const p of liste) {
      const g = gruppen[gruppen.length - 1];
      if (g && p.seq === g[g.length - 1].seq + 1 && g.length < 2000) { g.push(p); } else { gruppen.push([p]); }
    }
    const aus = [];
    for (const g of gruppen) {
      const tsVon = Math.min.apply(null, g.map((p) => p.ts));
      const innen = new Uint8Array(g.length * 14);
      const dv = new DataView(innen.buffer);
      g.forEach((p, i) => {
        dv.setInt32(i * 14, runde(p.lat * 1e7));
        dv.setInt32(i * 14 + 4, runde(p.lon * 1e7));
        dv.setInt16(i * 14 + 8, p.ele == null ? HOEHE_UNBEKANNT
                                              : Math.max(-32767, Math.min(32767, runde(p.ele))));
        dv.setUint32(i * 14 + 10, Math.max(0, p.ts - tsVon));
      });
      const platz = g[0].seq + ':' + g.length;
      const h = await EdCrypto.geraetehuelleVerschliessen(innen, pub, art, ref, platz);
      aus.push({ seq_from: g[0].seq, punkte: g.length, ts_von: tsVon,
                 ts_bis: Math.max.apply(null, g.map((p) => p.ts)), huelle: zuB64(h) });
    }
    return aus;
  }

  /** Die Einsatzhülle verschließen — mit dem öffentlichen Kontoschlüssel. */
  async function einsatzVerschliessen(innen, ref) {
    await paarBereit();
    const pub = (typeof EdKontopaar !== 'undefined') ? EdKontopaar.oeffentlich() : null;
    if (!pub) { throw fehler('kein_paar', 'Für dieses Konto ist noch kein Schlüsselpaar eingerichtet.'); }
    const h = await EdCrypto.geraetehuelleVerschliessen(
      new TextEncoder().encode(JSON.stringify(innen)), pub, 'einsatz', ref, '');
    return zuB64(h);
  }

  /**
   * EIN FREMDES SCHLÜSSELPAAR (S11-06c) — für den Freigabeweg: Ein Paket der
   * Verwaltung aus einem anderen Konto trägt dessen privaten Teil (PKCS8,
   * unter dem alten Inhaltsschlüssel). Geliefert wird dieselbe Form wie von
   * `schluessel()`: {priv, pub}, `pub` als SEC1 — die Hülle bindet ihn ein.
   * @param {Uint8Array} der  PKCS8
   */
  async function schluesselAus(der) {
    const alg = { name: 'ECDH', namedCurve: 'P-256' };
    const jwk = await crypto.subtle.exportKey('jwk',
      await crypto.subtle.importKey('pkcs8', der, alg, true, ['deriveBits']));
    const roh = (t) => b64(t.replace(/-/g, '+').replace(/_/g, '/')
      + '='.repeat((4 - t.length % 4) % 4));
    const pub = new Uint8Array(65);
    pub[0] = 4; pub.set(roh(jwk.x), 1); pub.set(roh(jwk.y), 33);
    return { priv: await crypto.subtle.importKey('pkcs8', der, alg, false, ['deriveBits']), pub: pub };
  }

  /**
   * EIN SPUR1-BLOB ZU PUNKTEN (S11-06b) — das Gegenstück zu
   * `spur_dekodieren()` in `spur_lib.php`, für das Einspielen einer
   * Sicherung der Nutzlast 8 bis 12. Bis Web 22.4.0 ging der Blob roh an den
   * Server; seit S11 nimmt er Punkte nur noch verschlossen an, und der
   * Browser muss sie vorher lesen.
   *
   * Format (Technik 4.97): Kopf 13 Byte — „SP", Fassung 1, Stufe,
   * Auflösung 1, Originalzahl und Punktzahl (je uint32, little-endian) —,
   * dann ein zlib-Strom mit vier Differenzreihen (int32, little-endian):
   * Breite und Länge in 1e-6 Grad, ein Bitfeld „hat Höhe", die Höhen in
   * Dezimetern, die Zeiten in Sekunden. Die Punktnummer ist die Stelle.
   *
   * @returns {Promise<{stufe:number, nOriginal:number, punkte:Array}>}
   *          punkte: [[seq, lat, lon, ele|null, ts], …]
   */
  async function spur1Lesen(b64Text) {
    const roh = b64(b64Text);
    if (roh.length < 13 || roh[0] !== 0x53 || roh[1] !== 0x50) {
      throw fehler('spur1', 'Kein SPUR-Blob (Signatur fehlt).');
    }
    if (roh[2] !== 1 || roh[4] !== 1) {
      throw fehler('spur1', 'SPUR-Blob in unbekannter Fassung oder Auflösung.');
    }
    const kopf = new DataView(roh.buffer, roh.byteOffset, 13);
    const stufe = roh[3], nOriginal = kopf.getUint32(5, true), n = kopf.getUint32(9, true);
    if (n === 0) { return { stufe: stufe, nOriginal: nOriginal, punkte: [] }; }
    const strom = new Blob([roh.subarray(13)]).stream()
      .pipeThrough(new DecompressionStream('deflate'));
    const nutz = new Uint8Array(await new Response(strom).arrayBuffer());
    const dv = new DataView(nutz.buffer, nutz.byteOffset, nutz.byteLength);
    let pos = 0;
    const reihe = (k) => {
      if (pos + 4 * k > nutz.length) { throw fehler('spur1', 'SPUR-Blob ist zu kurz.'); }
      const a = new Array(k);
      let lauf = 0;
      for (let i = 0; i < k; i++) { lauf += dv.getInt32(pos, true); pos += 4; a[i] = lauf; }
      return a;
    };
    const lat = reihe(n), lon = reihe(n);
    const bitLen = (n + 7) >> 3;
    const bits = nutz.subarray(pos, pos + bitLen);
    pos += bitLen;
    const hat = (i) => (bits[i >> 3] & (1 << (i & 7))) !== 0;
    let mit = 0;
    for (let i = 0; i < n; i++) { if (hat(i)) { mit++; } }
    const hoehen = reihe(mit), ts = reihe(n);
    const punkte = new Array(n);
    let h = 0;
    for (let i = 0; i < n; i++) {
      punkte[i] = [i, lat[i] / 1e6, lon[i] / 1e6, hat(i) ? hoehen[h++] / 10 : null, ts[i]];
    }
    return { stufe: stufe, nOriginal: nOriginal, punkte: punkte };
  }

  window.EdOrtsdaten = { schluessel: schluessel, spur: spur, einsatz: einsatz,
                         fuerAnsicht: fuerAnsicht, ladeplatz: ladeplatz, melden: melden,
                         einsatzZeiten: einsatzZeiten, einsatzInnen: einsatzInnen,
                         einsatzVerschliessen: einsatzVerschliessen, ortshoehe: ortshoehe,
                         stueckeBauen: stueckeBauen, spur1Lesen: spur1Lesen,
                         schluesselAus: schluesselAus };
})();
