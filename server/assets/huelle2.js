/* Gen-EM NAdoku — die Gerätehülle, Fassung 2: Herkunft, Zeit, Zähler, Folge.
 *
 * ZWECK DIESER DATEI. Sie öffnet die Gerätehülle der Fassung 2, verschließt
 * sie für den Absender „Konto" (Formular, Schnitt, Wartungslauf), hält die
 * Leseregeln für Spur und Einsatzhülle und rechnet Zusage und Vergleichswert
 * der Kopplung. Format und Prüfvektoren stehen im Kopf von
 * `tools/proben/huelle/referenz.py` und nur dort; die Hüllenprobe hält diese
 * Datei byte-gleich dagegen. Fassung 1 bleibt in crypto.js.
 *
 * WAS DAS PROBLEM WAR (Audit KA, F-KA-12, Nr. 563). Eine Hülle der Fassung 1
 * baut, wer den öffentlichen Kontoschlüssel kennt — der Server, jedes
 * gekoppelte Gerät. Die Zeiten standen relativ zu einem Klartextanker und
 * ließen sich verschieben; ein fehlendes Stück und eine ältere Einsatzhülle
 * fielen nicht auf.
 *
 * WAS FASSUNG 2 TUT.
 *
 *     0x02 · SID 8 · EPK 65 · IV 16 · AES-256-CBC (PKCS#7) · HMAC-SHA256
 *     SID   = SHA-256("nadoku-absender-1" ‖ SPK)[0:8]
 *     Z_e   = ECDH(flüchtig, Konto)      Z_s = ECDH(Absender statisch, Konto)
 *     OKM   = HKDF-SHA256(Z_e ‖ Z_s, salt = EPK,
 *                         info = "nadoku-huelle-2" ‖ Konto ‖ SPK)
 *
 * Der Prüfwert stimmt nur, wenn der Absender den privaten Teil zu SPK hatte:
 * Herkunft ohne Signatur (die Garmin-Uhr kann nicht signieren). ABSENDER ist
 * ein Gerät aus dem Gerätebuch (standbuch.js) — oder das Konto selbst, dann
 * ist SPK der öffentliche Kontoschlüssel. Die Zeiten stehen absolut IN der
 * Hülle, die Einsatzhülle trägt einen Zähler, und die Stücke einer Spur
 * müssen ihre Nummern lückenlos decken.
 *
 * WAS DAS NICHT BEWEIST: nichts gegenüber dem Server und nichts gegenüber
 * Dritten. Es ist ein gemeinsames Geheimnis, keine Signatur — wer den
 * privaten Kontoschlüssel hat, kann jede Gerätehülle auch selbst bauen.
 *
 * FEHLER tragen `.grund`:
 *   an der Hülle   laenge, fassung, absender, schluessel, pruefwert,
 *                  polsterung, ohne-herkunft (Fassung 1, wo 2 verlangt ist)
 *   an der Folge   luecke, ohne-schluss, zeit, zurueckgelegt,
 *                  absender-gesperrt, innen
 *   Kopplung       laenge, zusage
 * Was aufgeht, trägt seinen ABSENDER: null (Fassung 1 — „ohne
 * Herkunftsnachweis"), 'konto' oder die SID des Geräts (hex).
 *
 * DIE SCHREIBWEGE STEHEN HINTER DER WEICHE (`EdInhalt.schreibt2()`,
 * inhalt2.js): `stueckSchreiben()` und `einsatzSchreiben()` schreiben
 * Fassung 1, solange sie aus steht.
 *
 * Braucht: EdCrypto (crypto.js), EdInhalt (inhalt2.js). Legt `EdHuelle2` an.
 */
'use strict';
const EdHuelle2 = (() => {

  const T = EdCrypto.teile;
  const te = new TextEncoder();
  const FASSUNG1 = 1, FASSUNG2 = 2;
  const INFO2 = te.encode('nadoku-huelle-2');
  const ABSENDER_INFO = te.encode('nadoku-absender-1');
  const SID_LAENGE = 8;
  const KOPF2 = 1 + SID_LAENGE + 65 + 16;
  const TAG = 32;
  const M_SCHLUSS = 1, M_LUECKE = 2, M_UEBERNOMMEN = 4;
  const SPUR_KOPF2 = 12;
  const HERKUNFT_MAX = 8;
  const KONTO = 'konto';
  const HOEHE_UNBEKANNT = -32768;
  const ECDH = { name: 'ECDH', namedCurve: 'P-256' };

  const fehler = T.ghFehler, verbinde = T.ghVerbinde;
  const gleich = (a, b) => a.length === b.length && a.every((x, i) => x === b[i]);
  const sha256 = async (...teile) =>
    new Uint8Array(await crypto.subtle.digest('SHA-256', verbinde(...teile)));
  const bytes = h => (typeof h === 'string' ? T.fromB64(h) : h);

  /** Die Kennung eines Absenders: 8 Byte aus seinem statischen Schlüssel. */
  async function sid(spk) {
    return (await sha256(ABSENDER_INFO, spk)).slice(0, SID_LAENGE);
  }

  /* Z_s IST JE PAAR (ABSENDER, KONTO) FEST. Gemerkt wird es am privaten
   * Schlüssel, mit dem es gerechnet wurde — ein `WeakMap`, das mit ihm
   * verschwindet. Ohne das Fach kostete jede Hülle zwei ECDH statt einer;
   * eine Spur mit 300 Stücken eines Geräts rechnet so Z_s einmal. */
  const zsFach = new WeakMap();
  async function ecdh(privat, pubRoh) {
    const pub = await T.ghOeffentlich(pubRoh);
    return new Uint8Array(await crypto.subtle.deriveBits({ name: 'ECDH', public: pub }, privat, 256));
  }
  function statisch(privat, pubRoh) {
    let fach = zsFach.get(privat);
    if (!fach) { fach = new Map(); zsFach.set(privat, fach); }
    const k = T.toHex(pubRoh);
    if (!fach.has(k)) {
      const v = ecdh(privat, pubRoh);
      v.catch(() => fach.delete(k));
      fach.set(k, v);
    }
    return fach.get(k);
  }

  async function ableiten2(zE, zS, epk, kontoPub, spk) {
    const ikm = await crypto.subtle.importKey('raw', verbinde(zE, zS), 'HKDF', false, ['deriveBits']);
    const okm = new Uint8Array(await crypto.subtle.deriveBits(
      { name: 'HKDF', hash: 'SHA-256', salt: epk, info: verbinde(INFO2, kontoPub, spk) }, ikm, 512));
    return { kEnc: okm.slice(0, 32), kMac: okm.slice(32) };
  }

  /**
   * Eine Hülle der Fassung 2 bauen.
   * @param kontoPub  öffentlicher Kontoschlüssel (SEC1, 65 Byte)
   * @param absender  { privat: CryptoKey (ECDH), spk: Uint8Array(65) } — für
   *                  das Konto: sein privater Teil und `kontoPub`
   * @param fest      NUR für Prüfvektoren: { privat, epk, iv }
   * @returns {Promise<Uint8Array>}
   */
  async function verschliessen2(klar, kontoPub, absender, art, ref, platz, fest) {
    let eph, epk;
    if (fest) {
      eph = fest.privat; epk = fest.epk;
    } else {
      const paar = await crypto.subtle.generateKey(ECDH, false, ['deriveBits']);
      eph = paar.privateKey;
      epk = new Uint8Array(await crypto.subtle.exportKey('raw', paar.publicKey));
    }
    const iv = fest ? fest.iv : crypto.getRandomValues(new Uint8Array(16));
    const k = await ableiten2(await ecdh(eph, kontoPub), await statisch(absender.privat, kontoPub),
                              epk, kontoPub, absender.spk);
    const aes = await crypto.subtle.importKey('raw', k.kEnc, 'AES-CBC', false, ['encrypt']);
    const ct = new Uint8Array(await crypto.subtle.encrypt({ name: 'AES-CBC', iv }, aes, klar));
    const kopf = verbinde(new Uint8Array([FASSUNG2]), await sid(absender.spk), epk, iv);
    const tag = await T.ghHmac(k.kMac, verbinde(kopf, ct, EdCrypto.geraetehuelleBindung(art, ref, platz)));
    return verbinde(kopf, ct, tag);
  }

  /**
   * Eine Hülle der Fassung 2 öffnen. Reihenfolge: Länge, Fassung, Absender,
   * Schlüssel, Prüfwert, Polsterung — erst prüfen, dann entschlüsseln.
   * @param absenderSpk  (sid: Uint8Array) → SPK (65 Byte) oder null
   * @returns {Promise<{klar: Uint8Array, sid: Uint8Array}>}
   */
  async function oeffnen2(huelle, kontoPriv, kontoPub, absenderSpk, art, ref, platz) {
    if (huelle.length < KOPF2 + 16 + TAG || (huelle.length - KOPF2 - TAG) % 16) { throw fehler('laenge'); }
    if (huelle[0] !== FASSUNG2) { throw fehler('fassung'); }
    const s = huelle.slice(1, 9), epk = huelle.slice(9, 74), iv = huelle.slice(74, KOPF2);
    const ct = huelle.slice(KOPF2, huelle.length - TAG), tag = huelle.slice(huelle.length - TAG);
    const spk = absenderSpk(s);
    if (!spk) { throw fehler('absender'); }
    await T.ghOeffentlich(epk);                    // wirft `schluessel`
    let zE, zS;
    try {
      zE = await ecdh(kontoPriv, epk);
      zS = await statisch(kontoPriv, spk);
    } catch (e) { throw fehler('schluessel'); }
    const k = await ableiten2(zE, zS, epk, kontoPub, spk);
    const soll = await T.ghHmac(k.kMac, verbinde(huelle.slice(0, huelle.length - TAG),
                                                 EdCrypto.geraetehuelleBindung(art, ref, platz)));
    let d = 0;
    for (let i = 0; i < TAG; i++) { d |= soll[i] ^ tag[i]; }      // feste Zeit
    if (d) { throw fehler('pruefwert'); }
    const aes = await crypto.subtle.importKey('raw', k.kEnc, 'AES-CBC', false, ['decrypt']);
    try {
      return { klar: new Uint8Array(await crypto.subtle.decrypt({ name: 'AES-CBC', iv }, aes, ct)), sid: s };
    } catch (e) { throw fehler('polsterung'); }
  }

  /* ---- Innenformen ----------------------------------------------------- */

  /* 8 Byte big-endian aus einer Zahl, ohne BigInt: Sekunden passen in 53 Bit. */
  function uint64(n) {
    if (!Number.isSafeInteger(n) || n < 0) { throw fehler('innen'); }
    const b = new Uint8Array(8), dv = new DataView(b.buffer);
    dv.setUint32(0, Math.floor(n / 0x100000000));
    dv.setUint32(4, n % 0x100000000);
    return b;
  }
  function uint64Lesen(dv, o) {
    const hoch = dv.getUint32(o);
    if (hoch > 0x1fffff) { throw fehler('innen'); }
    return hoch * 0x100000000 + dv.getUint32(o + 4);
  }

  /**
   * Kopf und Nutzlast eines Spurstücks. `herkunft` (SIDs als Uint8Array(8)),
   * `luecke` und `uebernommen` setzt nur das Konto.
   */
  function spurInnen2(tsVon, nutzlast, o) {
    o = o || {};
    const herkunft = o.herkunft || [];
    if (herkunft.length > HERKUNFT_MAX || herkunft.some(h => h.length !== SID_LAENGE)) { throw fehler('innen'); }
    const mit = o.uebernommen != null;
    const merker = (o.schluss ? M_SCHLUSS : 0) | (o.luecke ? M_LUECKE : 0) | (mit ? M_UEBERNOMMEN : 0);
    return verbinde(uint64(tsVon), new Uint8Array([merker, herkunft.length, 0, 0]), ...herkunft,
                    mit ? uint64(o.uebernommen) : new Uint8Array(0), nutzlast);
  }

  function spurInnen2Lesen(innen) {
    if (innen.length < SPUR_KOPF2) { throw fehler('innen'); }
    const dv = new DataView(innen.buffer, innen.byteOffset, innen.byteLength);
    const tsVon = uint64Lesen(dv, 0), merker = innen[8], n = innen[9];
    if (dv.getUint16(10) || (merker & ~(M_SCHLUSS | M_LUECKE | M_UEBERNOMMEN)) || n > HERKUNFT_MAX) {
      throw fehler('innen');
    }
    let o = SPUR_KOPF2;
    if (innen.length < o + 8 * n + ((merker & M_UEBERNOMMEN) ? 8 : 0)) { throw fehler('innen'); }
    const herkunft = [];
    for (let i = 0; i < n; i++) { herkunft.push(innen.slice(o + 8 * i, o + 8 * i + 8)); }
    o += 8 * n;
    let uebernommen = null;
    if (merker & M_UEBERNOMMEN) { uebernommen = uint64Lesen(dv, o); o += 8; }
    return { ts_von: tsVon, merker: merker, herkunft: herkunft, uebernommen: uebernommen,
             nutzlast: innen.slice(o) };
  }

  /**
   * Die Einsatzhülle innen — die Schlüssel in genau dieser Reihenfolge.
   * WO DIE BYTES VON DER REFERENZ ABWEICHEN KÖNNEN: Eine Koordinate ohne
   * Nachkommastelle schreibt JSON hier als `47`, Python als `47.0`. Beim
   * Öffnen ist das gleich; byte-gleich nachbauen lässt sich eine solche
   * Hülle zwischen den Umsetzungen nicht.
   */
  function einsatzInnen2(z, start, schluss, phasen, rea, herkunft) {
    const d = { fassung: 2, z: z, start: start, schluss: !!schluss, phasen: phasen, rea: rea };
    if (herkunft != null) { d.herkunft = herkunft; }
    return te.encode(JSON.stringify(d));
  }

  /* ---- Lesen mit Regeln ------------------------------------------------ */

  /**
   * Was der Browser des Kontos zum Öffnen hat.
   * @param k        { priv: CryptoKey, pub: Uint8Array(65) } — das Kontopaar
   * @param geraete  das Gerätebuch: { <sid hex>: { spk: Uint8Array, bis: number|null } }
   *                 (`EdStandbuch`, `geraete()`)
   * @param verlangt2  Fassung 1 gilt nicht mehr (nach dem Stichtag, oder wo
   *                 das Standbuch schon einen Stand der Fassung 2 kennt)
   */
  async function leser(k, geraete, verlangt2) {
    const kontoSid = await sid(k.pub);
    geraete = geraete || {};
    const spkZu = s => {
      if (gleich(s, kontoSid)) { return k.pub; }
      const g = geraete[T.toHex(s)];
      return g ? g.spk : null;
    };
    return {
      /** Klartext und Absender: null (Fassung 1), 'konto' oder die SID (hex). */
      async huelle(h, art, ref, platz) {
        h = bytes(h);
        if (h[0] === FASSUNG1) {
          if (verlangt2) { throw fehler('ohne-herkunft'); }
          return { klar: await EdCrypto.geraetehuelleOeffnen(h, k.priv, k.pub, art, ref, platz || ''),
                   absender: null };
        }
        const o = await oeffnen2(h, k.priv, k.pub, spkZu, art, ref, platz || '');
        return { klar: o.klar, absender: gleich(o.sid, kontoSid) ? KONTO : T.toHex(o.sid) };
      },
      /** War dieses Gerät zur Zeit der Daten schon ausgetragen? */
      gesperrt(absender, zeit) {
        const g = geraete[absender || ''];
        return !!g && g.bis != null && zeit > g.bis;
      },
    };
  }

  /** Erklärt ein belegter Löschvermerk die Lücke zwischen zwei Zeiten?
   *  null heißt offen (vor dem ersten, nach dem letzten Punkt). */
  function ueberdeckt(sperren, tVor, tNach) {
    return sperren.some(([von, bis]) => (tNach === null || von < tNach) && (tVor === null || bis > tVor));
  }

  /** Die gepackte Innenform (Stufe 2, 3): zlib → vier Spalten int32 mit
   *  Abständen → Punkte [lat, lon, ele, ts]. */
  async function gepacktLesen(innen, n, tsVon) {
    let roh;
    try {
      const strom = new Blob([innen]).stream().pipeThrough(new DecompressionStream('deflate'));
      roh = new Uint8Array(await new Response(strom).arrayBuffer());
    } catch (e) { throw fehler('innen'); }
    if (roh.length !== 16 * n) { throw fehler('innen'); }
    const dv = new DataView(roh.buffer);
    const spalte = (s) => {
      const aus = new Array(n);
      let alt = 0;
      for (let i = 0; i < n; i++) { alt += dv.getInt32((s * n + i) * 4); aus[i] = alt; }
      return aus;
    };
    const la = spalte(0), lo = spalte(1), h = spalte(2), dt = spalte(3);
    return la.map((_, i) => [la[i] / 1e7, lo[i] / 1e7, h[i] === HOEHE_UNBEKANNT ? null : h[i], tsVon + dt[i]]);
  }

  const platzVon = st => ((st.stufe || 1) >= 2
    ? st.seq_from + ':' + st.punkte + ':' + st.stufe + ':' + st.n_original
    : st.seq_from + ':' + st.punkte);

  /**
   * DIE STÜCKE EINER AUFZEICHNUNG ÖFFNEN UND DIE FOLGE PRÜFEN.
   *
   * @param stuecke  je Stück der Klartext der Datenbank — `seq_from`,
   *                 `punkte`, `stufe`, `n_original` (ab Stufe 2), `ts_von` —
   *                 und `huelle` (Bytes oder Base64)
   * @param o  aus dem STANDBUCH, nie aus dem Klartext der Datenbank:
   *           `versatz` (Sekunden), `sperren` ([[von, bis], …] in der Zeit
   *           der Hülle), `stand` ([gedeckt bis, Stufe] oder null); dazu
   *           `abgeschlossen` (das Klartextkennzeichen `final`)
   * @returns {Promise<{punkte: Array, befund: object}>} Punkte
   *          {seq, lat, lon, ele, ts} (ts mit Versatz); Befund `herkunft`
   *          (wer verschlossen hat; 'fassung-1' heißt ohne Nachweis),
   *          `schluss`, `fassung1`, `gedeckt`
   */
  async function spurLesen(ls, art, ref, stuecke, o) {
    o = o || {};
    const versatz = o.versatz || 0, sperren = o.sperren || [];
    const offen = [];
    for (const st of stuecke || []) {
      const stufe = st.stufe || 1;
      const auf = await ls.huelle(st.huelle, art, ref, platzVon(st));
      let kopf;
      if (auf.absender === null) {                       // Fassung 1
        kopf = { ts_von: st.ts_von - versatz, merker: 0, herkunft: [], nutzlast: auf.klar };
      } else {
        kopf = spurInnen2Lesen(auf.klar);
        if (st.ts_von - kopf.ts_von !== versatz) { throw fehler('zeit'); }
        if (auf.absender !== KONTO
            && (stufe >= 2 || kopf.herkunft.length || (kopf.merker & (M_LUECKE | M_UEBERNOMMEN)))) {
          throw fehler('innen');                         // ein Gerät gibt sich als Konto aus
        }
      }
      let pkt, bis;
      if (stufe >= 2) {
        pkt = st.punkte ? await gepacktLesen(kopf.nutzlast, st.punkte, kopf.ts_von) : [];
        bis = st.seq_from + st.n_original;
      } else {
        if (kopf.nutzlast.length !== 14 * st.punkte) { throw fehler('innen'); }
        const dv = new DataView(kopf.nutzlast.buffer, kopf.nutzlast.byteOffset, kopf.nutzlast.byteLength);
        pkt = [];
        for (let i = 0; i < st.punkte; i++) {
          const h = dv.getInt16(i * 14 + 8);
          pkt.push([dv.getInt32(i * 14) / 1e7, dv.getInt32(i * 14 + 4) / 1e7,
                    h === HOEHE_UNBEKANNT ? null : h, kopf.ts_von + dv.getUint32(i * 14 + 10)]);
        }
        bis = st.seq_from + st.punkte;
      }
      const zeiten = pkt.map(p => p[3]);
      const tErst = zeiten.length ? Math.min.apply(null, zeiten) : null;
      const tLetzt = zeiten.length ? Math.max.apply(null, zeiten) : null;
      if (auf.absender !== null && auf.absender !== KONTO
          && ls.gesperrt(auf.absender, tLetzt === null ? kopf.ts_von : tLetzt)) {
        throw fehler('absender-gesperrt');
      }
      offen.push({ von: st.seq_from, bis: bis, stufe: stufe, pkt: pkt, absender: auf.absender,
                   merker: kopf.merker, tErst: tErst, tLetzt: tLetzt });
    }

    const fassung1 = offen.some(x => x.absender === null);
    offen.sort((a, b) => (a.von - b.von) || (b.bis - a.bis));
    let ende = 0, tEnde = null;
    for (const x of offen) {
      if (x.von > ende && !fassung1 && !ueberdeckt(sperren, tEnde, x.tErst)) { throw fehler('luecke'); }
      if (x.bis > ende) {
        ende = x.bis;
        tEnde = x.tLetzt !== null ? x.tLetzt : tEnde;
      }
    }
    /* SCHLUSS zählt nur an einem Stück, das bis ans Ende der Deckung reicht. */
    const schluss = offen.some(x => (x.merker & M_SCHLUSS) && x.bis === ende);
    const hoechste = offen.reduce((m, x) => Math.max(m, x.stufe), 0);
    if (o.stand && (ende < o.stand[0] || hoechste < o.stand[1])) { throw fehler('zurueckgelegt'); }
    if (o.abgeschlossen && !schluss && !fassung1 && !ueberdeckt(sperren, tEnde, null)) {
      throw fehler('ohne-schluss');
    }

    /* Entdoppeln wie bisher (ortsdaten.js): gepackte Stücke zuerst, dann
     * Gerätestücke über der Marke. */
    const nach = new Map();
    let marke = 0;
    for (const x of offen) {
      if (x.stufe < 2) { continue; }
      marke = Math.max(marke, x.bis);
      x.pkt.forEach((p, i) => { if (!nach.has(x.von + i)) { nach.set(x.von + i, p); } });
    }
    for (const x of offen) {
      if (x.stufe >= 2) { continue; }
      x.pkt.forEach((p, i) => {
        if (x.von + i >= marke && !nach.has(x.von + i)) { nach.set(x.von + i, p); }
      });
    }
    const punkte = [...nach.entries()].sort((a, b) => a[0] - b[0])
      .filter(([, p]) => !sperren.some(([von, bis]) => von <= p[3] && p[3] <= bis))
      .map(([seq, p]) => ({ seq: seq, lat: p[0], lon: p[1], ele: p[2], ts: p[3] + versatz }));
    const herkunft = [...new Set(offen.map(x => (x.absender === null ? 'fassung-1' : x.absender)))].sort();
    return { punkte: punkte, befund: { herkunft: herkunft, schluss: schluss, fassung1: fassung1, gedeckt: ende } };
  }

  /**
   * DIE EINSATZHÜLLE ÖFFNEN und gegen Klartextbeginn und Standbuch halten.
   * @param startedTs  der Klartextbeginn des Einsatzes
   * @param o  `versatz` und `stand` — der Eintrag des Standbuchs: `o` (Zähler
   *           der zuletzt abgenommenen Hülle), `k` (1: sie kam vom Konto)
   * @returns {Promise<{inhalt: object, absender: string|null}>}
   */
  async function einsatzLesen(ls, ref, huelle, startedTs, o) {
    o = o || {};
    const stand = o.stand || null;
    const auf = await ls.huelle(huelle, 'einsatz', ref, '');
    let d;
    try { d = JSON.parse(new TextDecoder('utf-8', { fatal: true }).decode(auf.klar)); }
    catch (e) { throw fehler('innen'); }
    if (auf.absender === null) {
      if (stand && stand.o) { throw fehler('zurueckgelegt'); }   // Fassung 1 nach einer abgenommenen Fassung 2
      return { inhalt: d, absender: null };
    }
    if (d === null || typeof d !== 'object' || Array.isArray(d) || d.fassung !== 2
        || !Number.isSafeInteger(d.z) || d.z < 1 || !Number.isSafeInteger(d.start)) {
      throw fehler('innen');
    }
    if (auf.absender !== KONTO && 'herkunft' in d) { throw fehler('innen'); }
    if (startedTs - d.start !== (o.versatz || 0)) { throw fehler('zeit'); }
    if (auf.absender !== KONTO && ls.gesperrt(auf.absender, d.start)) { throw fehler('absender-gesperrt'); }
    if (stand && stand.o && (d.z < stand.o || (stand.k && auf.absender !== KONTO))) {
      throw fehler('zurueckgelegt');
    }
    return { inhalt: d, absender: auf.absender };
  }

  /* ---- Schreiben für das Konto — hinter der Weiche ----------------------
   *
   * Der Browser ist der Absender „Konto": SPK ist der öffentliche
   * Kontoschlüssel, Z_s = ECDH(Konto privat, Konto öffentlich). Das kann
   * nur, wer den privaten Teil hat — anders als in Fassung 1 braucht ein
   * Schreibweg deshalb das GANZE Paar `k = { priv, pub }`
   * (`EdOrtsdaten.schluessel(ck)`), nicht nur den öffentlichen Teil. */

  /**
   * Ein Spurstück schreiben. Weiche aus: Fassung 1, innen nur die Nutzlast
   * (`kopf` wird nicht geschrieben). Weiche an: Fassung 2 mit Kopf.
   * @param kopf  { tsVon, schluss, luecke, herkunft, uebernommen }
   * @returns {Promise<Uint8Array>}
   */
  async function stueckSchreiben(k, art, ref, platz, kopf, nutzlast) {
    if (!EdInhalt.schreibt2()) {
      return EdCrypto.geraetehuelleVerschliessen(nutzlast, k.pub, art, ref, platz);
    }
    return verschliessen2(spurInnen2(kopf.tsVon, nutzlast, kopf), k.pub,
                          { privat: k.priv, spk: k.pub }, art, ref, platz);
  }

  /**
   * Die Einsatzhülle schreiben. Weiche aus: Fassung 1 mit `innen1` (die
   * Form aus `EdOrtsdaten.einsatzInnen()`), unverändert. Weiche an:
   * Fassung 2 — `kopf` trägt { z, start, schluss, herkunft }, Phasen und
   * Reanimation kommen aus `innen1`.
   * @returns {Promise<Uint8Array>}
   */
  async function einsatzSchreiben(k, ref, kopf, innen1) {
    if (!EdInhalt.schreibt2()) {
      return EdCrypto.geraetehuelleVerschliessen(te.encode(JSON.stringify(innen1)), k.pub, 'einsatz', ref, '');
    }
    return verschliessen2(
      einsatzInnen2(kopf.z, kopf.start, kopf.schluss, innen1.phasen || [], innen1.rea || [], kopf.herkunft),
      k.pub, { privat: k.priv, spk: k.pub }, 'einsatz', ref, '');
  }

  /* ---- Kopplung: Zusage und Vergleichswert ------------------------------
   *
   * Der zweite Kanal für beide öffentlichen Schlüssel (F-KA-17): sechs
   * Ziffern, die Gerät und Webseite zeigen. Kurz darf der Wert nur sein,
   * weil sich das Gerät FESTLEGT (die Zusage), bevor es die Zufallszahl des
   * Browsers kennt — sonst rechnete, wer den Server beschreibt, in
   * Millisekunden ein Schlüsselpaar aus, dessen Wert passt.
   *
   * DIE REIHENFOLGE IST DIE SICHERHEIT, und sie liegt beim Aufrufer:
   *   1. SPK und Zusage zum Code holen,
   *   2. DANN `kopplungNonce()` erzeugen und schicken,
   *   3. N_g abwarten, `kopplungWert(…, zusage)` rechnen und zeigen,
   *   4. erst nach dem eigenen Klick „Wert stimmt" das Gerät ins Gerätebuch
   *      eintragen (`EdStandbuch`, `geraetEintragen()`).
   * Wer N_b schickt, bevor er die Zusage hat, hat keine. */
  const ZUSAGE_INFO = te.encode('nadoku-kopplung-zusage-1');
  const WERT_INFO = te.encode('nadoku-kopplung-wert-1');
  const NONCE_LAENGE = 16;

  /** N_b: 16 Byte Zufall des Browsers. */
  function kopplungNonce() { return crypto.getRandomValues(new Uint8Array(NONCE_LAENGE)); }

  /** Was das Gerät bei `start` schickt, bevor es N_g offenlegt. */
  async function kopplungZusage(nG, spk) {
    if (nG.length !== NONCE_LAENGE || spk.length !== 65) { throw fehler('laenge'); }
    return sha256(ZUSAGE_INFO, nG, spk);
  }

  /**
   * Der Vergleichswert: sechs Ziffern als Text. Mit `zusage` prüft er sie
   * zuerst und wirft `zusage`, wenn sie nicht passt — der Browser ruft ihn
   * NIE ohne.
   */
  async function kopplungWert(kontoPub, spk, nG, nB, zusage) {
    if (kontoPub.length !== 65 || spk.length !== 65
        || nG.length !== NONCE_LAENGE || nB.length !== NONCE_LAENGE) { throw fehler('laenge'); }
    if (zusage != null) {
      const soll = await kopplungZusage(nG, spk);
      let d = zusage.length ^ soll.length;
      for (let i = 0; i < soll.length; i++) { d |= soll[i] ^ (zusage[i] || 0); }
      if (d) { throw fehler('zusage'); }
    }
    const h = await sha256(WERT_INFO, kontoPub, spk, nG, nB);
    const zahl = new DataView(h.buffer).getUint32(0) % 1000000;
    return String(zahl).padStart(6, '0');
  }

  return { sid, leser, spurLesen, einsatzLesen,
           stueckSchreiben, einsatzSchreiben,
           kopplungNonce, kopplungZusage, kopplungWert,
           /* die Bausteine einzeln — für den Anhebelauf und die Probe */
           verschliessen2, oeffnen2, spurInnen2, spurInnen2Lesen, einsatzInnen2,
           KONTO, M_SCHLUSS, M_LUECKE, M_UEBERNOMMEN };
})();
