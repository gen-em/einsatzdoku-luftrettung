/**
 * Der Wartungslauf im Browser (S11-07; Konzept S11 3.5, E-S11-05, -25, -68
 * bis -74).
 *
 * WOFÜR. Bis Web 22 packten die Jobs `verdichtung` und `ausduennen` die Spuren
 * auf dem Server. Seit S11 sieht der Server keine Koordinate mehr; er weiß
 * nur noch, WAS fällig ist (Stufen, Zeiten, Vermerke stehen im Klartext).
 * Diese Datei tut, was die Jobs taten, mit dem Schlüssel des Kontos:
 *
 *   1. die Liste der fälligen Spuren holen (`api/wartungslauf.php`, höchstens
 *      20 je Aufruf, E-S11-68);
 *   2. je Spur die Stücke öffnen (`EdOrtsdaten.spur()`; herausgeschnittene
 *      Punkte fallen dabei weg — jetzt endgültig, E-S11-37, -47);
 *   3. nach der Frist von 31 Tagen ausdünnen (Douglas-Peucker 3D, 2 m / 3 m,
 *      Phasenpunkte geschützt — dieselbe Regel und dieselbe Prüfung wie
 *      `spur_ausduennen()` und `spur_ausduennung_pruefen()`, E-S2-05);
 *   4. EIN gepacktes Stück bauen (Stufe 2 oder 3, E-S11-71) und es zur Probe
 *      wieder öffnen: Kommt nicht genau heraus, was hineinging, wird nichts
 *      geschickt (Rundlauf);
 *   5. bei Einsätzen eine fehlende Ortshöhe nachtragen (E-S11-72);
 *   6. tauschen lassen — der Server tut es nur, wenn sich nichts geändert hat.
 *
 * Ein 409 ist der Normalfall eines Wettlaufs (ein Gerät hat inzwischen
 * gesendet): Die Spur bleibt liegen, der nächste Lauf nimmt sie mit. Eine
 * Hülle, die nicht aufgeht, geht an den Reiter System und wird für diese
 * Sitzung ausgelassen. KEINE Antwort (`status: 0`, meist ein Seitenwechsel)
 * lässt nichts aus und beendet den Durchgang (seit Web 23.0.3, Nr. 393).
 *
 *   EdWartungslauf.lauf({ck, fortschritt})   ein Durchgang; liefert
 *                                            {spuren, getauscht, liegen,
 *                                             fehler, hoehen}
 *   EdWartungslauf.still(wrap)               Startseite: ein Durchgang,
 *                                            unsichtbar (M-S11-02)
 *   EdWartungslauf.karte({wrap, faellig, zustand})
 *                                            die Karte im Reiter Backup
 *   EdWartungslauf.ausduennen(punkte, zeiten)       Behalteliste (Indizes)
 *   EdWartungslauf.ausduennungPruefen(p, b, zeiten) null oder die Abweichung
 *
 * Braucht: EdApi, EdCrypto, EdOrtsdaten, EdKeyGuard, EdHtml.
 */
(function () {
  'use strict';

  const TOL_W = 2.0;            // SPUR_TOL_WAAGERECHT_M
  const TOL_S = 3.0;            // SPUR_TOL_SENKRECHT_M
  const DECKEL = 1000;          // SPUR_DP_ABSCHNITT_MAX
  const AUSGELASSEN = 'nadoku-wartungslauf-ausgelassen';

  /* ---- Ausdünnen — wörtlich `spur_lib.php` ---------------------------------
   * Punkte als [seq, lat, lon, ele|null, ts], wie dort. Kommentare zu Regel
   * und Herkunft stehen an den PHP-Funktionen; hier steht nur, was anders ist. */

  function ortsrahmen(P) {
    const lat0 = P.length ? P[0][1] : 0;
    return [111132.95, 111319.49 * Math.cos(lat0 * Math.PI / 180)];
  }

  function hoehenanker(P) {
    const n = P.length;
    const bekannt = [];
    for (let i = 0; i < n; i++) { if (P[i][3] !== null) { bekannt.push(i); } }
    if (!bekannt.length) { return []; }
    const anker = new Array(n);
    let j = 0;
    for (let i = 0; i < n; i++) {
      if (P[i][3] !== null) { anker[i] = P[i][3]; continue; }
      while (j < bekannt.length - 1 && bekannt[j] < i) { j++; }
      const rechts = bekannt[j];
      let links = null;
      for (let k = j; k >= 0; k--) { if (bekannt[k] < i) { links = bekannt[k]; break; } }
      if (links === null) { anker[i] = P[rechts][3]; continue; }
      if (rechts <= i) { anker[i] = P[links][3]; continue; }
      const tl = P[links][4], tr = P[rechts][4];
      const t = tr > tl ? (P[i][4] - tl) / (tr - tl) : 0;
      anker[i] = P[links][3] + t * (P[rechts][3] - P[links][3]);
    }
    return anker;
  }

  function dpAbstand(P, anker, i, j, k, ky, kx) {
    const bx = (P[j][2] - P[i][2]) * kx, by = (P[j][1] - P[i][1]) * ky;
    const px = (P[k][2] - P[i][2]) * kx, py = (P[k][1] - P[i][1]) * ky;
    const l2 = bx * bx + by * by;
    const t = l2 > 0 ? Math.max(0, Math.min(1, (px * bx + py * by) / l2)) : 0;
    const dx = px - t * bx, dy = py - t * by;
    const waag = Math.sqrt(dx * dx + dy * dy);
    let senk = 0;
    if (anker.length && P[k][3] !== null) {
      senk = Math.abs(P[k][3] - (anker[i] + t * (anker[j] - anker[i])));
    }
    return [waag, senk];
  }

  function schutzpunkte(P, zeiten) {
    const n = P.length;
    if (!n) { return []; }
    const pflicht = new Set([0, n - 1]);
    for (const ts of zeiten) {
      let best = 0, bestD = Infinity;
      for (let i = 0; i < n; i++) {
        const d = Math.abs(P[i][4] - ts);
        if (d < bestD) { bestD = d; best = i; }   // `<`, nicht `<=` — der frühere gewinnt
      }
      pflicht.add(best);
    }
    return [...pflicht].sort((a, b) => a - b);
  }

  function ausduennen(P, zeiten) {
    const n = P.length;
    if (n <= 2) { return P.map((_, i) => i); }
    const [ky, kx] = ortsrahmen(P);
    const anker = hoehenanker(P);
    const behalten = new Set(schutzpunkte(P, zeiten || []));
    let grenzen = [...behalten].sort((a, b) => a - b);
    for (let g = 0; g < grenzen.length - 1; g++) {
      for (let m = grenzen[g] + DECKEL; m < grenzen[g + 1]; m += DECKEL) { behalten.add(m); }
    }
    grenzen = [...behalten].sort((a, b) => a - b);
    for (let g = 0; g < grenzen.length - 1; g++) {
      const stapel = [[grenzen[g], grenzen[g + 1]]];
      while (stapel.length) {
        const [i, j] = stapel.pop();
        if (j <= i + 1) { continue; }
        let maxS = 0, maxK = -1;
        for (let k = i + 1; k < j; k++) {
          const [w, s] = dpAbstand(P, anker, i, j, k, ky, kx);
          const rel = Math.max(w / TOL_W, s / TOL_S);
          if (rel > maxS) { maxS = rel; maxK = k; }
        }
        if (maxS <= 1 || maxK < 0) { continue; }
        behalten.add(maxK);
        if (maxK - i >= j - maxK) { stapel.push([i, maxK], [maxK, j]); }
        else { stapel.push([maxK, j], [i, maxK]); }
      }
    }
    return [...behalten].sort((a, b) => a - b);
  }

  function ausduennungPruefen(P, behalten, zeiten) {
    const n = P.length, m = behalten.length;
    if (!m) { return 'Behalteliste ist leer'; }
    if (m > n) { return 'Behalteliste ist länger als die GPS-Daten'; }
    let vorher = -1;
    for (const i of behalten) {
      if (!Number.isInteger(i) || i < 0 || i >= n) { return 'Index ' + i + ' liegt außerhalb der GPS-Daten'; }
      if (i <= vorher) { return 'Indizes nicht streng aufsteigend'; }
      if (vorher >= 0 && P[i][4] < P[vorher][4]) { return 'Zeit fällt zwischen ' + vorher + ' und ' + i; }
      vorher = i;
    }
    if (behalten[0] !== 0) { return 'Der erste Punkt fehlt'; }
    if (behalten[m - 1] !== n - 1) { return 'Der letzte Punkt fehlt'; }
    const dabei = new Set(behalten);
    for (const i of schutzpunkte(P, zeiten || [])) {
      if (!dabei.has(i)) { return 'Schutzpunkt ' + i + ' fehlt'; }
    }
    const [ky, kx] = ortsrahmen(P);
    const anker = hoehenanker(P);
    let seg = 0;
    for (let k = 0; k < n; k++) {
      if (dabei.has(k)) { continue; }
      while (seg < m - 2 && behalten[seg + 1] < k) { seg++; }
      const [w, s] = dpAbstand(P, anker, behalten[seg], behalten[seg + 1], k, ky, kx);
      if (w > TOL_W + 1e-6) { return 'Punkt ' + k + ' liegt ' + w.toFixed(3) + ' m waagerecht daneben'; }
      if (s > TOL_S + 1e-6) { return 'Punkt ' + k + ' liegt ' + s.toFixed(3) + ' m senkrecht daneben'; }
    }
    return null;
  }

  /* ---- Ein Durchgang ------------------------------------------------------- */

  const sek = (z) => Math.floor(Date.parse(String(z).replace(' ', 'T') + 'Z') / 1000);
  const runde = (x) => Math.sign(x) * Math.round(Math.abs(x));

  /** Gleich auf der Auflösung der Innenform: 1e-7 Grad, ganze Meter, Sekunden. */
  function gleich(a, b) {
    if (a.length !== b.length) { return false; }
    for (let i = 0; i < a.length; i++) {
      const x = a[i], y = b[i];
      if (runde(x.lat * 1e7) !== runde(y.lat * 1e7) || runde(x.lon * 1e7) !== runde(y.lon * 1e7)
          || x.ts !== y.ts || (x.ele == null) !== (y.ele == null)
          || (x.ele != null && runde(x.ele) !== runde(y.ele))) { return false; }
    }
    return true;
  }

  function ausgelassen() {
    try { return JSON.parse(sessionStorage.getItem(AUSGELASSEN) || '[]'); } catch (e) { return []; }
  }
  function auslassen(art, id) {
    try {
      const l = ausgelassen();
      l.push([art, id]);
      sessionStorage.setItem(AUSGELASSEN, JSON.stringify(l.slice(-200)));
    } catch (e) { /* ohne Speicher: dann eben beim nächsten Mal wieder */ }
  }

  /** Eine Spur. Liefert 'getauscht', 'liegt' oder wirft. */
  async function eine(e, k, ck, zaehler) {
    const artH = e.art === 'rest' ? 'ruhe' : 'spur';
    const punkte = await EdOrtsdaten.spur(e.stuecke, artH, e.ref, e.sperren, k)
      .catch((x) => { x.art = artH; throw x; });
    if (!punkte.length) { return 'liegt'; }      // alles herausgeschnitten: nichts zu packen

    const stufeAlt = Math.max(...e.stuecke.map((st) => st.stufe || 1));
    const zeiten = (e.phases || []).map((p) => sek(p.occurred_at)).filter((t) => !isNaN(t));
    let behalten = punkte;
    if (e.ziel_stufe === 3 && stufeAlt < 3) {
      const P = punkte.map((p) => [p.seq, p.lat, p.lon, p.ele, p.ts]);
      const idx = ausduennen(P, zeiten);
      const f = ausduennungPruefen(P, idx, zeiten);
      if (f) { throw Object.assign(new Error('Ausdünnung: ' + f), { grund: 'ausduennung' }); }
      behalten = idx.map((i) => punkte[i]);
    }
    const von = Math.min(...e.stuecke.map((st) => st.seq_from));
    const marke = Math.max(...e.stuecke.map((st) =>
      st.seq_from + ((st.stufe || 1) >= 2 ? st.n_original : st.punkte)));
    const stueck = await EdOrtsdaten.gepacktBauen(behalten, artH, e.ref,
      { stufe: e.ziel_stufe, seqFrom: von, nOriginal: marke - von });

    /* DER RUNDLAUF: das neue Stück öffnen und Punkt für Punkt vergleichen. */
    const wieder = await EdOrtsdaten.spur([stueck], artH, e.ref, [], k);
    if (!gleich(wieder, behalten)) {
      throw Object.assign(new Error('Der Rundlauf weicht ab — nichts geschickt.'), { grund: 'rundlauf' });
    }

    let pat = null;
    if (e.art === 'mission') {
      try {
        const o = e.pat_blob ? JSON.parse(await EdCrypto.decrypt(ck, e.pat_blob)) : {};
        if (o.ortshoehe == null) {
          const h = EdOrtsdaten.ortshoehe(punkte, (e.phases || []).map((p) => [p.phase, sek(p.occurred_at)]));
          if (h !== null) {
            o.ortshoehe = h;
            pat = { pruef: e.pat_pruef, neu: await EdCrypto.encrypt(ck, JSON.stringify(o)) };
          }
        }
      } catch (x) { pat = null; }   // ein Blob, der nicht aufgeht, wird nicht angefasst
    }

    const a = await EdApi.postJson('api/wartungslauf.php', {
      aktion: 'tauschen', art: e.art, id: e.id,
      stand: e.stuecke.map((st) => [st.seq_from, st.punkte]), stueck: stueck, pat: pat || undefined,
    }, { vorgang: 'Der Wartungslauf' });
    if (a.ok) {
      if (a.daten && a.daten.pat === 'geschrieben') { zaehler.hoehen++; }
      return 'getauscht';
    }
    if (a.status === 409) { return 'liegt'; }
    /* KEINE ANTWORT IST KEIN URTEIL ÜBER DIE SPUR (Web 23.0.3, Nr. 393).
       `status: 0` heißt: Die Anfrage kam nie zurück — meist, weil die Seite
       gerade verlassen wird. Bis dahin landete die Spur dafür auf der
       Auslassliste, und zwar für den ganzen Tab: Wer die Startseite öffnete
       und gleich weiterklickte, schob die Spur, die gerade dran war, aus dem
       Wartungslauf, bis der Tab zu war. */
    if (a.status === 0) {
      throw Object.assign(new Error(a.meldung || 'Keine Antwort'), { grund: 'verbindung' });
    }
    throw Object.assign(new Error(a.meldung || 'Tausch abgelehnt'), { grund: 'server' });
  }

  async function lauf(o) {
    o = o || {};
    const z = { spuren: 0, getauscht: 0, liegen: 0, fehler: 0, hoehen: 0 };
    const l = await EdApi.postJson('api/wartungslauf.php', { aktion: 'liste', auslassen: ausgelassen() },
                                   { vorgang: 'Der Wartungslauf' });
    const spuren = (l.ok && l.daten && l.daten.spuren) || [];
    z.spuren = spuren.length;
    if (!spuren.length) { return z; }
    const k = await EdOrtsdaten.schluessel(o.ck);
    for (let i = 0; i < spuren.length; i++) {
      const e = spuren[i];
      if (o.fortschritt) { o.fortschritt(i, spuren.length, e); }
      try {
        const r = await eine(e, k, o.ck, z);
        if (r === 'getauscht') { z.getauscht++; } else { z.liegen++; }
      } catch (x) {
        z.fehler++;
        /* Ohne Verbindung hat dieser Durchgang keinen Sinn mehr, und die
           Spur ist nicht schuld: nicht auslassen, aufhören. Der nächste
           Aufruf nimmt sie wieder. */
        if (x && x.grund === 'verbindung') { break; }
        auslassen(e.art, e.id);
        if (x && x.art) { EdOrtsdaten.melden(x, x.art, e.id); }
      }
      /* Dem Hauptfaden Luft lassen: Die Seite bleibt bedienbar. */
      await new Promise((r) => setTimeout(r, 0));
    }
    if (o.fortschritt) { o.fortschritt(spuren.length, spuren.length, null); }
    return z;
  }

  /* ---- Zwei Verwender (Mockup M-S11-02, Variante 1) ----------------------- */

  /** Den Inhaltsschlüssel, OHNE zu fragen: Der Lauf drängt sich nie mit
   *  einem Passwortdialog vor. */
  async function schluesselStill(wrap) {
    if (!wrap || typeof EdKeyGuard === 'undefined') { return null; }
    try { return await EdKeyGuard.contentKey(wrap); } catch (e) { return null; }
  }

  /** STARTSEITE: ein Durchgang, unsichtbar, nach dem Entsperren (E-S11-68).
   *  Fehler bleiben still; eine Hülle, die nicht aufgeht, ist gemeldet. */
  async function still(wrap) {
    const ck = await schluesselStill(wrap);
    if (!ck) { return null; }
    try { return await lauf({ ck: ck }); } catch (e) { return null; }
  }

  const mehrzahl = (n, eins, viele) => n + ' ' + (n === 1 ? eins : viele);

  /**
   * DIE KARTE „GPS-Daten verdichten" (Reiter Backup): zählt, was fällig ist,
   * und arbeitet es ab, solange die Seite offen ist — Durchgang um
   * Durchgang, bis nichts mehr fällig ist oder ein Durchgang nichts schafft.
   * Ohne Schlüssel wartet sie (stehender Ring) und fragt alle drei Sekunden
   * nach, ob inzwischen entsperrt ist; ein Dialog geht von ihr nie aus.
   */
  async function karte(o) {
    const faellig = o.faellig, zustand = o.zustand;
    const zahl = async () => {
      const a = await EdApi.postJson('api/wartungslauf.php', { aktion: 'zahl', auslassen: ausgelassen() },
                                     { vorgang: 'Das Zählen' });
      return a.ok ? a.daten : null;
    };
    const zeige = (z) => {
      faellig.textContent = !z ? 'unbekannt'
        : (z.aufzeichnungen === 0 ? 'nichts'
           : mehrzahl(z.aufzeichnungen, 'Aufzeichnung', 'Aufzeichnungen') + ' aus '
             + mehrzahl(z.diensttage, 'Diensttag', 'Diensttagen'));
    };
    let z = await zahl();
    zeige(z);
    if (!z || z.aufzeichnungen === 0) { return; }

    let ck = await schluesselStill(o.wrap);
    if (!ck) {
      zustand.innerHTML = '<div class="ladeplatz ladeplatz-still"><span class="ladeplatz-ring" aria-hidden="true"></span>'
        + 'Wartet auf dein Passwort</div>';
      while (!ck) {
        await new Promise((r) => setTimeout(r, 3000));
        ck = await schluesselStill(o.wrap);
      }
    }

    const gesamt = z.aufzeichnungen;
    let fertig = 0;
    const summe = { getauscht: 0, fehler: 0, hoehen: 0 };
    zustand.innerHTML = '<div class="ladeplatz"><span class="ladeplatz-ring" aria-hidden="true"></span>'
      + '<span data-vd-text></span></div>'
      + '<div class="balken" role="progressbar" aria-valuemin="0" aria-valuemax="' + gesamt + '"><i></i></div>';
    const text = zustand.querySelector('[data-vd-text]');
    const balken = zustand.querySelector('.balken');
    const stand = (n) => {
      const w = Math.min(n, gesamt);
      text.textContent = 'Wird verdichtet: ' + w + ' von ' + gesamt;
      balken.setAttribute('aria-valuenow', String(w));
      balken.firstChild.style.width = (gesamt ? 100 * w / gesamt : 100) + '%';
    };
    stand(0);
    for (;;) {
      let r;
      try {
        r = await lauf({ ck: ck, fortschritt: (i) => stand(fertig + i) });
      } catch (e) {
        zustand.innerHTML = EdHtml.meldung('fehler', 'Der Wartungslauf ist abgebrochen: '
          + ((e && e.message) || 'unbekannter Fehler') + '. Er versucht es beim nächsten Öffnen wieder.');
        return;
      }
      fertig += r.spuren;
      summe.getauscht += r.getauscht; summe.fehler += r.fehler; summe.hoehen += r.hoehen;
      stand(fertig);
      if (!r.spuren || !r.getauscht) { break; }
    }
    z = await zahl();
    zeige(z);
    const teile = [];
    if (summe.fehler) {
      zustand.innerHTML = EdHtml.meldung('fehler', mehrzahl(summe.fehler, 'Aufzeichnung ließ', 'Aufzeichnungen ließen')
        + ' sich nicht öffnen und ' + (summe.fehler === 1 ? 'bleibt' : 'bleiben')
        + ', wie sie ' + (summe.fehler === 1 ? 'ist' : 'sind') + '. Gemeldet an die Betreuung.'
        + (summe.getauscht ? ' ' + mehrzahl(summe.getauscht, 'Aufzeichnung', 'Aufzeichnungen') + ' verdichtet.' : ''));
      return;
    }
    teile.push('Fertig: ' + mehrzahl(summe.getauscht, 'Aufzeichnung', 'Aufzeichnungen') + ' verdichtet');
    if (summe.hoehen) {
      teile.push('bei ' + mehrzahl(summe.hoehen, 'Einsatz', 'Einsätzen') + ' die Höhe des Einsatzorts nachgetragen');
    }
    zustand.innerHTML = EdHtml.meldung('ok', teile.join(', ') + '.'
      + (z && z.aufzeichnungen ? ' Der Rest kommt beim nächsten Öffnen dran — inzwischen kam Neues hinzu.' : ''));
  }

  window.EdWartungslauf = { lauf: lauf, still: still, karte: karte,
                            ausduennen: ausduennen, ausduennungPruefen: ausduennungPruefen };
})();
