/**
 * Ortsdaten öffnen — Spurstücke und Einsatzhülle im Browser.
 *
 * WOFÜR. Spur, Koordinaten der Phasen und Reanimation liegen nur als
 * Gerätehüllen auf dem Server (Format: `tools/proben/huelle/
 * referenz.py`); `api/mission.php` und `api/day.php` liefern sie aus, wie sie
 * liegen. Öffnen kann sie nur der Browser, mit dem privaten Schlüssel des
 * Kontos (`EdKontopaar.privat`). Diese Datei ist die EINE Stelle, die das tut
 * und daraus macht, was die Ansichten schon kennen: eine Punktfolge, Phasen
 * mit `lat`/`lon` und `track_idx`, Reanimationen als Ereignislisten. Die
 * Zeichenwege der Seiten brauchen deshalb keine eigene Fassung.
 *
 *   EdOrtsdaten.schluessel(ck)            privater und öffentlicher Teil
 *   EdOrtsdaten.spur(stuecke, art, ref, sperren, k)
 *                                         Punkte {seq, lat, lon, ele, ts}
 *   EdOrtsdaten.einsatz(m, k)             die Einsatzhülle, geöffnet, oder null
 *   EdOrtsdaten.fuerAnsicht(m, ck)        füllt m.track, m.phases, m.resus
 *   EdOrtsdaten.melden(e, art, id)        eine Hülle, die nicht aufging, an
 *                                         den Reiter System; die
 *                                         Art aus dem Fehler geht vor
 *   EdOrtsdaten.einsatzZeiten(e)          die Zeiten des Formulars, wie der
 *                                         Server sie rechnet
 *   EdOrtsdaten.einsatzInnen(z, orte)     Innenform der Einsatzhülle
 *   EdOrtsdaten.einsatzVerschliessen(i, ref)  verschließen, Base64
 *   EdOrtsdaten.ortshoehe(punkte, phasen) Höhe des Einsatzorts
 *   EdOrtsdaten.stueckeBauen(punkte, art, ref, {neu})  Punkte → Stücke
 *                                         (Schneiden, Löschen)
 *   EdOrtsdaten.gepacktBauen(punkte, art, ref, {stufe, seqFrom, nOriginal})
 *                                         ein gepacktes Stück der Stufe 2/3
 *   EdOrtsdaten.schluesselAus(pkcs8)      ein FREMDES Paar aus seinem privaten
 *                                         Teil (Freigabe)
 *
 * REGELN BEIM ÖFFNEN: Punkt i eines Stücks der
 * Stufe 1 hat die Sequenznummer `seq_from + i`; ein Stück der Stufe 2 oder 3
 * (gepackt) deckt `seq_from` bis `seq_from + n_original − 1`, und Punkte
 * eines Gerätestücks darunter stecken schon darin; doppelte Nummern (ein Gerät, das seine Marke
 * verloren hat und anders stückelt) zählen einmal. Punkte in einer Sperrzeit
 * (ein herausgeschnittener Zeitraum) fallen weg. Höhe -32768 heißt: keine.
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
     Seite entstand, abwarten bzw. nachholen. */
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
    /* `ck` reist mit (KA-06b-3): Wer eine Hülle der Fassung 2 öffnet, braucht
       das Standbuch des Kontos — Gerätebuch, Versatz, Sperren, Zähler. */
    return { priv: priv, pub: pub, ck: ck };
  }

  function gesperrt(ts, sperren) {
    return (sperren || []).some((s) => ts >= s.von && ts <= s.bis);
  }

  /** Die gepackte Innenform öffnen: zlib-Strom → vier Spalten
      int32 big-endian mit Abständen → Punkte ohne eigene Nummer. */
  async function gepacktLesen(innen, n, tsVon) {
    const strom = new Blob([innen]).stream().pipeThrough(new DecompressionStream('deflate'));
    const roh = new Uint8Array(await new Response(strom).arrayBuffer());
    if (roh.byteLength !== 16 * n) {
      throw fehler('format', 'Gepacktes Stück mit ' + roh.byteLength + ' Byte für ' + n + ' Punkte.');
    }
    const dv = new DataView(roh.buffer, roh.byteOffset, roh.byteLength);
    const spalte = (k) => {
      const aus = new Array(n);
      let alt = 0;
      for (let i = 0; i < n; i++) { alt += dv.getInt32((k * n + i) * 4); aus[i] = alt; }
      return aus;
    };
    const la = spalte(0), lo = spalte(1), h = spalte(2), dt = spalte(3);
    const aus = new Array(n);
    for (let i = 0; i < n; i++) {
      aus[i] = { lat: la[i] / 1e7, lon: lo[i] / 1e7, ele: h[i] === HOEHE_UNBEKANNT ? null : h[i],
                 ts: tsVon + dt[i] };
    }
    return aus;
  }

  /** Der Ort einer Hülle der Stufe 2 oder 3. */
  const platzGepackt = (st) => st.seq_from + ':' + st.punkte + ':' + st.stufe + ':' + st.n_original;

  /* FASSUNG 2 (KA-06b-3) — erkannt am Kennbyte der Hülle (0x02). Steht die
     * Weiche aus, gibt es keine; dann läuft jeder Leseweg unten wie bisher. */
  function istFassung2(huelle) {
    if (huelle instanceof Uint8Array) { return huelle[0] === 2; }
    try { return typeof huelle === 'string' && atob(huelle.slice(0, 4)).charCodeAt(0) === 2; }
    catch (e) { return false; }
  }

  /** Was das Standbuch zu einem Datensatz weiß — leer ohne Inhaltsschlüssel
   *  (ein fremdes Paar im Freigabeweg hat kein Buch dieses Kontos). */
  async function standbuchFuer(k, ref) {
    if (!k.ck || typeof EdStandbuch === 'undefined') { return { buch: null, e: {} }; }
    const buch = await EdStandbuch.seitenbuch(k.ck);
    return { buch: buch, e: await buch.eintrag(ref) };
  }

  /** Eine Spur mit mindestens einer Hülle der Fassung 2 — über `EdHuelle2`,
   *  mit den Regeln für Folge, Zeit und Herkunft. Die Sperren des Klartexts
   *  gelten zusätzlich: Sie blenden aus, sie belegen nichts. */
  async function spur2(stuecke, art, ref, sperren, k) {
    const sb = await standbuchFuer(k, ref);
    const ls = await EdHuelle2.leser(k, sb.buch ? await sb.buch.geraete() : {}, false);
    const r = await EdHuelle2.spurLesen(ls, art, ref,
      (stuecke || []).map((st) => Object.assign({}, st, { huelle: b64(st.huelle) })),
      { versatz: sb.e.d || 0, sperren: sb.e.x || [], stand: sb.e.s || null })
      .catch((e) => { e.art = art; throw e; });
    return r.punkte.filter((p) => !gesperrt(p.ts, sperren));
  }

  async function spur(stuecke, art, ref, sperren, k) {
    if ((stuecke || []).some((st) => istFassung2(st.huelle))) {
      return spur2(stuecke, art, ref, sperren, k);
    }
    const nach = new Map();
    /* ZUERST DIE GEPACKTEN (Stufe 2 und 3). Ihre Punkte tragen keine
       eigene Nummer; das Stück deckt seq_from bis seq_from + n_original − 1,
       und was ein Gerätestück darunter trägt, steckt schon darin. */
    let marke = 0;
    for (const st of stuecke || []) {
      if (!(st.stufe >= 2)) { continue; }
      const innen = await EdCrypto.geraetehuelleOeffnen(b64(st.huelle), k.priv, k.pub, art, ref,
                                                        platzGepackt(st))
        .catch((e) => { e.art = art; throw e; });
      marke = Math.max(marke, st.seq_from + st.n_original);
      (await gepacktLesen(innen, st.punkte, st.ts_von)).forEach((p, i) => {
        if (gesperrt(p.ts, sperren)) { return; }
        const seq = st.seq_from + i;
        if (!nach.has(seq)) { nach.set(seq, Object.assign({ seq: seq }, p)); }
      });
    }
    for (const st of stuecke || []) {
      if (st.stufe >= 2) { continue; }
      const innen = await EdCrypto.geraetehuelleOeffnen(b64(st.huelle), k.priv, k.pub, art, ref,
                                                        st.seq_from + ':' + st.punkte)
        .catch((e) => { e.art = art; throw e; });
      const dv = new DataView(innen.buffer, innen.byteOffset, innen.byteLength);
      const n = Math.floor(innen.byteLength / 14);
      for (let i = 0; i < n; i++) {
        const ts = st.ts_von + dv.getUint32(i * 14 + 10);
        if (gesperrt(ts, sperren)) { continue; }
        const seq = st.seq_from + i;
        if (seq < marke || nach.has(seq)) { continue; }
        const h = dv.getInt16(i * 14 + 8);
        nach.set(seq, { seq: seq, lat: dv.getInt32(i * 14) / 1e7, lon: dv.getInt32(i * 14 + 4) / 1e7,
                        ele: h === HOEHE_UNBEKANNT ? null : h, ts: ts });
      }
    }
    return [...nach.values()].sort((a, b) => a.seq - b.seq);
  }

  async function einsatz(m, k) {
    if (!m.ort_huelle) { return null; }
    if (istFassung2(m.ort_huelle)) {
      const sb = await standbuchFuer(k, m.client_ref);
      const ls = await EdHuelle2.leser(k, sb.buch ? await sb.buch.geraete() : {}, false);
      /* Der Klartextbeginn: `started_ts` (Epoche) oder `started_at` (UTC,
         „JJJJ-MM-TT hh:mm:ss") — je nach Quelle der Zeile. Fehlt beides,
         lässt sich die Zeit der Hülle nicht halten, und sie geht nicht auf. */
      const beginnTs = Number.isFinite(m.started_ts) ? m.started_ts
        : (typeof m.started_at === 'string'
           ? Math.floor(Date.parse(m.started_at.replace(' ', 'T') + 'Z') / 1000) : NaN);
      const r = await EdHuelle2.einsatzLesen(ls, m.client_ref, b64(m.ort_huelle), beginnTs,
                                             { versatz: sb.e.d || 0, stand: { o: sb.e.o || 0, k: sb.e.k || 0 } })
        .catch((e) => { e.art = 'einsatz'; throw e; });
      /* DER ZÄHLER STEIGT BEIM LESEN mit (wie `patLesen()`): Ein späteres
         Zurücklegen auf eine ältere Hülle fällt dann auf. Mit der Weiche aus
         schreibt das Buch nichts. */
      if (sb.buch && r.absender !== null && r.inhalt.z > (sb.e.o || 0)) {
        sb.buch.fortschreiben(m.client_ref, (e) => {
          e.o = Math.max(e.o || 0, r.inhalt.z);
          if (r.absender === EdHuelle2.KONTO) { e.k = 1; }
        }).catch(() => {});
      }
      return r.inhalt;
    }
    const innen = await EdCrypto.geraetehuelleOeffnen(b64(m.ort_huelle), k.priv, k.pub,
                                                      'einsatz', m.client_ref, '')
      .catch((e) => { e.art = 'einsatz'; throw e; });
    return JSON.parse(new TextDecoder().decode(innen));
  }

  /** Der Index des zeitlich nächsten Punkts — daraus wird `track_idx`. */
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
   * Eine Hülle ging nicht auf — das soll die BetreiberIn erfahren: ein Gerät, das falsch verschließt, oder ein Eingriff
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
   * DIE ZEITEN DES FORMULARS, IM BROWSER GERECHNET.
   *
   * Warum hier: Die Reanimation steht nur in der Einsatzhülle, und die
   * verschließt der Browser; ihre Zeiten (Sekunden seit Einsatzbeginn)
   * muss er deshalb selbst rechnen. Die Regeln sind die von
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
   * Die Innenform der Einsatzhülle aus gerechneten Zeiten und den
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
   * DIE HÖHE DES EINSATZORTS — hier und nicht im Server, weil er die Spur
   * nicht sieht:
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
   * PUNKTE ZU STÜCKEN — die Gegenrichtung zu `spur()`, für die
   * Schreibwege des Browsers: Schneiden (die Punkte im Zeitraum als Spur des
   * neuen Einsatzes) und Löschen (die übrigen Punkte des Segments neu).
   * Innenform wie `gh_spur_innen()` und `referenz.py`: je Punkt 14 Byte,
   * big-endian, Höhe in ganzen Metern, -32768 = keine.
   *
   * `neu: true` nummeriert ab 0 durch (ein neuer Einsatz hat eine eigene
   * Zählung); sonst bleiben die Sequenznummern, und ein Stück endet an jeder
   * Lücke — Punkt i eines Stücks muss `seq_from + i` sein. Höchstens 2000
   * Punkte je Stück (`LIMIT_TRACKPUNKTE_ANFRAGE`).
   *
   * @param {Array} punkte  aus `spur()`: {seq, lat, lon, ele, ts}, sortiert
   * @returns {Promise<Array>} [{seq_from, punkte, ts_von, ts_bis, huelle}]
   */
  /* DAS PAAR ZUM SCHREIBEN (KA-06b-3). Mit der Weiche aus genügt der
   * öffentliche Teil, und `EdHuelle2` schreibt Fassung 1 wie bisher. Mit
   * der Weiche an ist der Browser der Absender „Konto" — dafür braucht er
   * den privaten Teil, also den Inhaltsschlüssel (`o.ck`, F-KA-88). */
  async function schreibpaar(o) {
    await paarBereit();
    const pub = (typeof EdKontopaar !== 'undefined') ? EdKontopaar.oeffentlich() : null;
    if (!pub) { throw fehler('kein_paar', 'Für dieses Konto ist noch kein Schlüsselpaar eingerichtet.'); }
    if (!EdInhalt.schreibt2()) { return { pub: pub, priv: null }; }
    /* Ohne `o.ck` der Inhaltsschlüssel der Seite — jeder Schreibweg steht auf
       einer Seite mit dem Rüstzeug (`PAT_WRAP`). */
    const ck = (o && o.ck) || ((typeof EdUnlock !== 'undefined' && typeof PAT_WRAP !== 'undefined')
      ? await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER) : null);
    if (!ck) {
      throw fehler('kein_schluessel', 'Zum Verschließen fehlt der Inhaltsschlüssel — bitte die Seite neu laden.');
    }
    return schluessel(ck);
  }

  /**
   * EINEN GESPERRTEN ZEITRAUM INS STANDBUCH (KA-06b-3, F-KA-87). Ein Schnitt
   * oder ein Löschen lässt eine Lücke in der Folge der Stücke; in Fassung 2
   * gilt sie nur, wenn ein Vermerk des Kontos sie deckt — sonst wäre
   * „ein Stück entfernt" nicht von einem Schnitt zu unterscheiden. Die
   * Vermerke stehen in der Zeit der Hülle (Klartext minus Versatz). Mit der
   * Weiche aus schreibt das Buch nichts. Still: Ein Fehlschlag hier lässt
   * den Schnitt stehen; die Lücke fällt dann beim Lesen auf.
   */
  async function sperreVermerken(ref, von, bis) {
    if (!EdInhalt.schreibt2() || typeof PAT_WRAP === 'undefined') { return null; }
    try {
      const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
      if (!ck) { return null; }
      const buch = await EdStandbuch.seitenbuch(ck);
      return await buch.fortschreiben(ref, (e) => {
        const d = e.d || 0;
        e.x = (e.x || []).concat([[von - d, bis - d]]);
      });
    } catch (e) { return null; }
  }

  async function stueckeBauen(punkte, art, ref, o) {
    o = o || {};
    const k = await schreibpaar(o);
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
      /* Fassung 2: absolute Zeit im Kopf; SCHLUSS trägt, wer es verlangt
         (`o.schluss`, das letzte Stück einer abgeschlossenen Aufzeichnung). */
      const h = await EdHuelle2.stueckSchreiben(k, art, ref, platz,
        { tsVon: tsVon, schluss: !!(o.schluss && g === gruppen[gruppen.length - 1]) }, innen);
      aus.push({ seq_from: g[0].seq, punkte: g.length, ts_von: tsVon,
                 ts_bis: Math.max.apply(null, g.map((p) => p.ts)), huelle: zuB64(h) });
    }
    return aus;
  }

  /**
   * EIN GEPACKTES STÜCK — Stufe 2 (zusammengefügt) oder 3
   * (ausgedünnt), für Wartungslauf, Löschen auf einer gepackten Spur und das
   * Einspielen einer ausgedünnten. Eine Spur, ein Stück; die Punkte bekommen
   * keine Nummer, `seqFrom` und `nOriginal` sagen, welchen Bereich es deckt.
   * Innenform wie `referenz.py` `innen_gepackt()`.
   *
   * @param {Array} punkte  {lat, lon, ele, ts}, in ihrer Reihenfolge
   * @returns {Promise<object>} {seq_from, punkte, n_original, stufe, ts_von, ts_bis, huelle}
   */
  async function gepacktBauen(punkte, art, ref, o) {
    const k = await schreibpaar(o);
    const n = punkte.length;
    if (!n) { throw fehler('leer', 'Ein Stück ohne Punkte gibt es nicht.'); }
    const tsVon = Math.min.apply(null, punkte.map((p) => p.ts));
    const roh = new Uint8Array(16 * n);
    const dv = new DataView(roh.buffer);
    const werte = [
      punkte.map((p) => runde(p.lat * 1e7)), punkte.map((p) => runde(p.lon * 1e7)),
      punkte.map((p) => p.ele == null ? HOEHE_UNBEKANNT : Math.max(-32767, Math.min(32767, runde(p.ele)))),
      punkte.map((p) => p.ts - tsVon)];
    werte.forEach((sp, k) => {
      let alt = 0;
      sp.forEach((v, i) => { dv.setInt32((k * n + i) * 4, v - alt); alt = v; });
    });
    const strom = new Blob([roh]).stream().pipeThrough(new CompressionStream('deflate'));
    const innen = new Uint8Array(await new Response(strom).arrayBuffer());
    const st = { seq_from: o.seqFrom, punkte: n, n_original: o.nOriginal, stufe: o.stufe, ts_von: tsVon,
                 ts_bis: Math.max.apply(null, punkte.map((p) => p.ts)) };
    st.huelle = zuB64(await EdHuelle2.stueckSchreiben(k, art, ref, platzGepackt(st),
                                                      { tsVon: tsVon, schluss: !!o.schluss }, innen));
    return st;
  }

  /** Die Einsatzhülle verschließen. Weiche aus: Fassung 1 mit dem
   *  öffentlichen Kontoschlüssel, wie bisher. Weiche an: Fassung 2 als
   *  Absender „Konto", mit dem nächsten Zähler aus dem Standbuch und dem
   *  absoluten Beginn (`o.start`, Unix-Sekunden) — `o.ck` ist dann Pflicht.
   *  Den Zähler hebt das Standbuch beim nächsten Lesen (`einsatz()`). */
  async function einsatzVerschliessen(innen, ref, o) {
    o = o || {};
    const k = await schreibpaar(o);
    let kopf = null;
    if (EdInhalt.schreibt2()) {
      if (!Number.isSafeInteger(o.start)) {
        throw fehler('beginn', 'Zum Verschließen fehlt der Beginn des Einsatzes.');
      }
      const sb = await standbuchFuer(k, ref);
      kopf = { z: (sb.e.o || 0) + 1, start: o.start, schluss: !!o.schluss };
    }
    return zuB64(await EdHuelle2.einsatzSchreiben(k, ref, kopf, innen));
  }

  /**
   * EIN FREMDES SCHLÜSSELPAAR — für den Freigabeweg: Ein Paket der
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

  window.EdOrtsdaten = { schluessel: schluessel, spur: spur, einsatz: einsatz,
                         fuerAnsicht: fuerAnsicht, melden: melden,
                         einsatzZeiten: einsatzZeiten, einsatzInnen: einsatzInnen,
                         einsatzVerschliessen: einsatzVerschliessen, ortshoehe: ortshoehe,
                         stueckeBauen: stueckeBauen, gepacktBauen: gepacktBauen,
                         schluesselAus: schluesselAus, sperreVermerken: sperreVermerken };
})();
