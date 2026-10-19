/**
 * Das Einmalwerkzeug im Browser (S11-10; Konzept S11 3.8 Schritt 5, E-S11-19).
 *
 * WOFÜR. Bis Web 22 lagen Spur, Koordinate jeder Phase, Reanimation,
 * Transportziel und Ortshöhe im Klartext auf dem Server. Seit S11 liest die
 * Anwendung sie nur noch verschlossen; ein Einsatz des Altbestands zeigt bis
 * zum Umzug keine Karte und kein Transportziel (E-S11-36, -42). Verschließen
 * kann nur dieser Browser — der Server kennt den Schlüssel nicht. Je
 * Datensatz:
 *
 *   1. holen, was im Klartext liegt (`api/ortsumzug.php`, höchstens 10 je
 *      Runde);
 *   2. die Spurpunkte zu Stücken verschließen (Stufe 1; eine ausgedünnte
 *      Spur als EIN gepacktes Stück der Stufe 3, wie beim Einspielen einer
 *      Sicherung) und zur Probe wieder öffnen: Kommt nicht genau heraus, was
 *      hineinging, wird nichts geschickt (Rundlauf);
 *   3. Phasenorte und Reanimation in die Einsatzhülle — trägt der Einsatz
 *      schon eine, wird zusammengeführt, und was die Hülle hat, gewinnt
 *      (wie das Formular, E-S11-45 (4));
 *   4. Transportziel und Ortshöhe in den `pat_blob` — was der Blob schon
 *      hat, gewinnt; ein Blob, der nicht aufgeht, wird nicht angefasst;
 *   5. tauschen lassen. Der Server tut es in EINER Transaktion und nur bei
 *      unverändertem Stand; der Klartext geht dabei.
 *
 * Wie der Wartungslauf läuft es STILL auf der Startseite, nach dem
 * Entsperren, und nie mit einem Passwortdialog. Es hat keinen Merker: Was
 * umgezogen ist, liefert die Liste nicht mehr; bricht es ab, setzt der
 * nächste Aufruf dort wieder auf. Was nicht aufgeht, geht an den Reiter
 * System und wird für diese Sitzung ausgelassen.
 *
 *   EdOrtsumzug.lauf({ck, runden})  bis nichts mehr offen ist oder eine
 *                                   Runde nichts schafft; liefert
 *                                   {gelesen, getauscht, liegen, fehler}
 *   EdOrtsumzug.still(wrap)         Startseite: Lauf ohne Dialog
 *   EdOrtsumzug.zahl()              {einsaetze, ruhe} oder null
 *
 * Braucht: EdApi, EdCrypto, EdOrtsdaten, EdKeyGuard.
 */
(function () {
  'use strict';

  const AUSGELASSEN = 'nadoku-ortsumzug-ausgelassen';
  const API = 'api/ortsumzug.php';

  const zahlOderNull = (v) => (v === null || v === undefined || v === '' || !Number.isFinite(Number(v)))
    ? null : Number(v);
  /* Zeitpunkt der Datenbank (SQL-Form, UTC) → Sekunden. */
  const sek = (z) => Math.floor(Date.parse(String(z).replace(' ', 'T') + 'Z') / 1000);
  const runde = (x) => Math.sign(x) * Math.round(Math.abs(x));

  function ausgelassen() {
    try { return JSON.parse(sessionStorage.getItem(AUSGELASSEN) || '[]'); } catch (e) { return []; }
  }
  function auslassen(art, id) {
    try {
      const l = ausgelassen();
      l.push([art, id]);
      sessionStorage.setItem(AUSGELASSEN, JSON.stringify(l.slice(-500)));
    } catch (e) { /* ohne Speicher: dann eben beim nächsten Mal wieder */ }
  }

  /** Gleich auf der Auflösung der Innenform: 1e-7 Grad, ganze Meter, Sekunden. */
  function gleich(a, b, mitNummer) {
    if (a.length !== b.length) { return false; }
    for (let i = 0; i < a.length; i++) {
      const x = a[i], y = b[i];
      if ((mitNummer && x.seq !== y.seq)
          || runde(x.lat * 1e7) !== runde(y.lat * 1e7) || runde(x.lon * 1e7) !== runde(y.lon * 1e7)
          || x.ts !== y.ts || (x.ele == null) !== (y.ele == null)
          || (x.ele != null && Math.max(-32767, Math.min(32767, runde(x.ele))) !== y.ele)) { return false; }
    }
    return true;
  }

  /** Die Spurpunkte → Stücke, mit Rundlauf. Liefert [] ohne Punkte. */
  async function stuecke(e, artH, k) {
    const nach = new Map();
    for (const p of (e.spur.punkte || [])) {
      const q = { seq: Number(p[0]), lat: Number(p[1]), lon: Number(p[2]), ele: zahlOderNull(p[3]), ts: Number(p[4]) };
      if (!Number.isInteger(q.seq) || q.seq < 0 || !Number.isInteger(q.ts) || q.ts < 0
          || !(Math.abs(q.lat) <= 90) || !(Math.abs(q.lon) <= 180)) { continue; }
      if (!nach.has(q.seq)) { nach.set(q.seq, q); }
    }
    const punkte = [...nach.values()].sort((a, b) => a.seq - b.seq);
    if (!punkte.length) { return []; }
    let liste;
    if (e.spur.stufe === 3) {
      /* Eine ausgedünnte Spur bleibt ausgedünnt (wie `rwStuecke()` beim
         Einspielen, E-S11-71). */
      const von = punkte[0].seq;
      liste = [await EdOrtsdaten.gepacktBauen(punkte, artH, e.client_ref, {
        stufe: 3, seqFrom: von,
        nOriginal: Math.max(punkte[punkte.length - 1].seq - von + 1, (e.spur.n_original || 0) - von) })];
    } else {
      liste = await EdOrtsdaten.stueckeBauen(punkte, artH, e.client_ref);
    }
    /* DER RUNDLAUF. Ein gepacktes Stück trägt keine eigenen Nummern. */
    const wieder = await EdOrtsdaten.spur(liste, artH, e.client_ref, [], k);
    if (!gleich(punkte, wieder, e.spur.stufe !== 3)) {
      throw Object.assign(new Error('Der Rundlauf der GPS-Daten weicht ab — nichts geschickt.'), { grund: 'rundlauf' });
    }
    return liste;
  }

  /** Phasenorte und Reanimation → Einsatzhülle {neu, alt}, oder null. */
  async function ort(e, k) {
    const beginn = sek(e.started_at);
    const phasen = (e.phases || []).filter((p) => !isNaN(sek(p.occurred_at)));
    const offen = (e.resus || []).length || phasen.some((p) => p.lat !== null || p.lon !== null);
    if (!offen || isNaN(beginn)) { return null; }
    const klar = {
      fassung: 1,
      phasen: phasen.map((p) => ({ phase: p.phase, t: sek(p.occurred_at) - beginn, lat: p.lat, lon: p.lon })),
      rea: (e.resus || []).filter((r) => !isNaN(sek(r.started_at))).map((r) => ({
        t: sek(r.started_at) - beginn,
        ereignisse: (r.events || []).filter((x) => x.type && !isNaN(sek(x.occurred_at)))
          .map((x) => ({ art: x.type, t: sek(x.occurred_at) - beginn })) })),
    };
    let innen = klar;
    if (e.ort_huelle) {
      /* ZUSAMMENFÜHREN: Die Hülle ist der neuere Stand; aus dem Klartext
         kommt nur, was ihr fehlt. Geht sie nicht auf, wirft `einsatz()`. */
      innen = await EdOrtsdaten.einsatz(e, k) || { fassung: 1, phasen: [], rea: [] };
      const frei = new Map();
      for (const p of klar.phasen) { frei.set(p.phase + ':' + p.t, p); }
      for (const p of (innen.phasen || [])) {
        const q = frei.get(p.phase + ':' + p.t);
        if (q && p.lat == null && p.lon == null && q.lat !== null) { p.lat = q.lat; p.lon = q.lon; }
      }
      if (!(innen.rea || []).length && klar.rea.length) { innen.rea = klar.rea; }
    }
    const neu = await EdOrtsdaten.einsatzVerschliessen(innen, e.client_ref);
    /* Zur Probe öffnen: dieselbe Innenform. */
    const wieder = await EdOrtsdaten.einsatz({ ort_huelle: neu, client_ref: e.client_ref }, k);
    if (JSON.stringify(wieder) !== JSON.stringify(innen)) {
      throw Object.assign(new Error('Der Rundlauf der Einsatzhülle weicht ab.'), { grund: 'rundlauf', art: 'einsatz' });
    }
    return { neu: neu, alt: e.ort_huelle || null };
  }

  /** Transportziel und Ortshöhe → Blob {neu, alt}, oder null (auch: Blob geht nicht auf). */
  async function blob(e, ck) {
    const ziel = (typeof e.transport_dest === 'string' && e.transport_dest.trim()) ? e.transport_dest : null;
    if (ziel === null && e.dest_lat === null && e.dest_lon === null && e.site_ele_m === null) { return null; }
    let o = {};
    if (e.pat_blob) {
      try { o = JSON.parse(await EdCrypto.decrypt(ck, e.pat_blob)) || {}; } catch (x) { return 'unlesbar'; }
    }
    if (!o.transport_dest && (ziel !== null || e.dest_lat !== null)) {
      o.transport_dest = { addr: ziel || '' };
      if (e.dest_lat !== null && e.dest_lon !== null) { o.transport_dest.lat = e.dest_lat; o.transport_dest.lon = e.dest_lon; }
    }
    if (o.ortshoehe == null && e.site_ele_m !== null) { o.ortshoehe = e.site_ele_m; }
    return { neu: await EdCrypto.encrypt(ck, JSON.stringify(o)), alt: e.pat_blob || null };
  }

  /** Ein Datensatz. Liefert 'getauscht', 'liegt' oder wirft. */
  async function eins(e, k, ck) {
    const artH = e.art === 'rest' ? 'ruhe' : 'spur';
    const rumpf = { aktion: 'tauschen', art: e.art, id: e.id, spur_stand: e.spur_stand, stuecke: [] };
    if (!e.spur.konflikt) {
      rumpf.stuecke = await stuecke(e, artH, k).catch((x) => { x.art = x.art || artH; throw x; });
    }
    if (e.art === 'mission') {
      const o = await ort(e, k);
      if (o) { rumpf.ort = o; }
      const b = await blob(e, ck);
      if (b && b !== 'unlesbar') { rumpf.pat = b; }
    }
    if (!rumpf.stuecke.length && !rumpf.ort && !rumpf.pat) { return 'liegt'; }
    const a = await EdApi.postJson(API, rumpf, { vorgang: 'Der Umzug der Ortsdaten' });
    if (a.ok) { return 'getauscht'; }
    if (a.status === 409) { return 'liegt'; }
    throw Object.assign(new Error(a.meldung || 'Tausch abgelehnt'), { grund: 'server' });
  }

  async function lauf(o) {
    o = o || {};
    const z = { gelesen: 0, getauscht: 0, liegen: 0, fehler: 0 };
    let k = null;
    for (let r = 0; r < (o.runden || 200); r++) {
      const l = await EdApi.postJson(API, { aktion: 'liste', auslassen: ausgelassen() },
                                     { vorgang: 'Der Umzug der Ortsdaten' });
      const liste = (l.ok && l.daten && l.daten.liste) || [];
      if (!liste.length) { break; }
      k = k || await EdOrtsdaten.schluessel(o.ck);
      let geschafft = 0;
      for (const e of liste) {
        z.gelesen++;
        try {
          const t = await eins(e, k, o.ck);
          if (t === 'getauscht') { z.getauscht++; geschafft++; } else { z.liegen++; auslassen(e.art, e.id); }
        } catch (x) {
          z.fehler++;
          auslassen(e.art, e.id);
          if (x && x.art) { EdOrtsdaten.melden(x, x.art, e.id); }
        }
        await new Promise((res) => setTimeout(res, 0));
      }
      if (!geschafft) { break; }
    }
    return z;
  }

  async function zahl() {
    const a = await EdApi.postJson(API, { aktion: 'zahl', auslassen: ausgelassen() }, { vorgang: 'Das Zählen' });
    return a.ok ? a.daten : null;
  }

  /** STARTSEITE: ohne Dialog, ohne Meldung. Fehler bleiben still; eine
   *  Hülle, die nicht aufgeht, ist gemeldet. */
  async function still(wrap) {
    if (!wrap || typeof EdKeyGuard === 'undefined') { return null; }
    let ck = null;
    try { ck = await EdKeyGuard.contentKey(wrap); } catch (e) { return null; }
    if (!ck) { return null; }
    try { return await lauf({ ck: ck }); } catch (e) { return null; }
  }

  window.EdOrtsumzug = { lauf: lauf, still: still, zahl: zahl };
})();
