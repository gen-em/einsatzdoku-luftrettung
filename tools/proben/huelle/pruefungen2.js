/* Hüllenprobe, Fassung 2 — die Prüfungen, die IN DER SEITE laufen.
 *
 * Anlass: Nr. 563 — Chiffretexte und Gerätehüllen waren an nichts gebunden
 * (Audit KA, F-KA-04, -12); Fassung 2 muss in jeder Umsetzung dieselben
 * Bytes schreiben und jede Gegenlage mit ihrem Grund abweisen.
 *
 * WAS. `probe.mjs` lädt diese Datei in die Seite und ruft
 * `hpFassung2(v2, vi, weicheAn)` mit `vektoren-2.json` und
 * `vektoren-inhalt.json`. Gemessen wird gegen `inhalt2.js`, `huelle2.js`,
 * `standbuch.js` und die Ableitung in `crypto.js`:
 *   byte-gleich   jeder Fall der Referenz, bei festem Nonce bzw. festem
 *                 flüchtigem Schlüssel und IV — Text gegen Text
 *   Gegenlagen    jede scheitert, und zwar mit dem Grund der Referenz
 *   Bibliothek    Standbuch und Gerätebuch gegen einen Server im Speicher
 *                 (`speicherTransport()`): Anker, Nachtragen, Konflikt (409),
 *                 Ablage über zwei Bücher hinweg — dafür gibt es keine
 *                 Vektoren, die Erwartung steht hier
 *   Weiche        aus: jeder Schreibweg ergibt Fassung 1, das Buch sendet
 *                 nichts; an (probe.mjs lädt `inhalt2.js` dafür mit
 *                 umgelegter Konstante): Fassung 2, und sie geht auf
 *
 * JEDE PRÜFUNG STEHT FÜR SICH in einem `pruefe()`: Fehlt eine Datei oder
 * eine Funktion (die Gegenprobe gegen den Stand vor KA-06b), ist jede
 * einzeln rot — nicht eine Sammelmeldung grün daneben.
 */
'use strict';
window.hpFassung2 = async (v2, vi, weicheAn) => {
  const zeilen = [];
  let schlecht = 0;
  const melde = (gut, text) => { zeilen.push(`  ${gut ? 'ok  ' : 'FEHL'}  ${text}`); if (!gut) { schlecht++; } };
  /* `tun` liefert true/false oder wirft. */
  const pruefe = async (text, tun) => {
    try { melde((await tun()) === true, text); }
    catch (e) { melde(false, `${text} — ${e && (e.grund || e.message)}`); }
  };
  /* Muss mit `grund` scheitern. */
  const scheitert = async (text, grund, tun) => {
    try { await tun(); melde(false, `${text}: ging auf, erwartet ${grund}`); }
    catch (e) { melde(e && e.grund === grund, `${text}: abgewiesen (${e && (e.grund || e.message)})`); }
  };

  const hex = (h) => new Uint8Array((h.match(/../g) || []).map((x) => parseInt(x, 16)));
  const zuHex = (b) => Array.from(b, (x) => x.toString(16).padStart(2, '0')).join('');
  const ausB64 = (t) => Uint8Array.from(atob(t), (c) => c.charCodeAt(0));
  const b64url = (b) => btoa(String.fromCharCode(...b)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  const gleich = (a, b) => JSON.stringify(a) === JSON.stringify(b);
  const privat = (dHex, pubHex) => {
    const d = hex(dHex), pub = hex(pubHex);
    return crypto.subtle.importKey('jwk', { kty: 'EC', crv: 'P-256', d: b64url(d),
      x: b64url(pub.slice(1, 33)), y: b64url(pub.slice(33, 65)), ext: true },
      { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']);
  };
  const aes = (h) => crypto.subtle.importKey('raw', hex(h), { name: 'AES-GCM' }, false, ['encrypt', 'decrypt']);

  /* ================================================================ Inhalt */
  const CK = vi.schluessel.ck_hex;
  let ck = null;
  try { ck = await aes(CK); } catch (e) { /* jede Prüfung meldet es selbst */ }

  for (const f of vi.faelle) {
    const fest = { nonce: hex(f.nonce_hex) };
    await pruefe(`edk2 ${f.name}: Zusatzdaten`, async () =>
      zuHex(EdInhalt.zusatzdaten(f.zweck, f.bezug)) === f.zusatzdaten_hex);
    await pruefe(`edk2 ${f.name}: verschließen byte-gleich (innen ${f.innen_laenge})`, async () =>
      (await EdInhalt.verschliessenBytes(f.schluessel_hex, f.zweck, f.bezug, hex(f.nutzlast_hex), f.z, fest)) === f.text
      && EdInhalt.klasse(8 + f.nutzlast_hex.length / 2, f.zweck) === f.innen_laenge);
    await pruefe(`edk2 ${f.name}: öffnen (Nutzlast, Zähler ${f.z})`, async () => {
      const o = await EdInhalt.oeffnenBytes(f.schluessel_hex, f.text, f.zweck, f.bezug);
      return zuHex(o.nutzlast) === f.nutzlast_hex && o.z === f.z;
    });
  }
  await pruefe('edk2: der Inhaltsschlüssel als CryptoKey schreibt dieselben Bytes', async () => {
    const f = vi.faelle[0];
    return (await EdInhalt.verschliessenBytes(ck, f.zweck, f.bezug, hex(f.nutzlast_hex), f.z,
                                              { nonce: hex(f.nonce_hex) })) === f.text;
  });
  for (const f of vi.huellen) {
    await pruefe(`Hülle ${f.name}: bauen byte-gleich`, async () =>
      (await EdInhalt.huelleBauen(f.schluessel_hex, CK, f.art, f.anteil_kennung, { nonce: hex(f.nonce_hex) })) === f.text);
    await pruefe(`Hülle ${f.name}: öffnen`, async () =>
      (await EdInhalt.huelleOeffnen(f.schluessel_hex, f.text, f.art)) === CK);
  }
  for (const s of vi.stoerungen) {
    await scheitert(`edk2 Störung ${s.name}`, s.grund, () =>
      EdInhalt.oeffnenBytes(s.schluessel_hex, s.text, s.zweck, s.bezug));
  }
  for (const s of vi.huellen_stoerungen) {
    await scheitert(`Hülle Störung ${s.name}`, s.grund, () =>
      EdInhalt.huelleOeffnen(s.schluessel_hex, s.text, s.art));
  }
  /* Der Rückweg hängt nie am Anteil — weder beim Bauen noch beim Öffnen. */
  await scheitert('Hülle rc mit Anteil-Kennung bauen', 'kennung', () =>
    EdInhalt.huelleBauen(vi.schluessel.rc_hex, CK, 'rc', '1a2b3c4d'));
  await scheitert('Hülle edka2 mit leerer Kennung', 'kennung', () =>
    EdInhalt.huelleOeffnen(vi.schluessel.dk_hex, 'edka2::' + vi.huellen[2].text.slice(5), 'pw'));
  await scheitert('edk2 mit Leerraum im Base64', 'form', () =>
    EdInhalt.oeffnenBytes(CK, vi.faelle[0].text.replace('edk2:', 'edk2: '), 'pat', vi.faelle[0].bezug));
  await scheitert('edk2 mit unbekanntem Zweck', 'zweck', () =>
    EdInhalt.oeffnenBytes(CK, vi.faelle[0].text, 'notiz', ''));

  /* Wie crypto.js mit Fassung 2 umgeht. */
  const wpw = vi.huellen[1];
  await pruefe('EdCrypto.huelleKennung liest die Kennung aus edka2', async () =>
    EdCrypto.huelleKennung(wpw.text) === wpw.anteil_kennung && EdCrypto.huelleGebunden(wpw.text) === true
    && EdCrypto.huelleGebunden('edka1:1a2b3c4d:AAAA') === false);
  await pruefe('EdCrypto.huelleOeffnen öffnet edka2 und edk2 (pw)', async () =>
    (await EdCrypto.huelleOeffnen(wpw.schluessel_hex, wpw.text)) === CK
    && (await EdCrypto.huelleOeffnen(vi.huellen[2].schluessel_hex, vi.huellen[2].text)) === CK);
  await scheitert('EdCrypto.decrypt weist edk2 benannt ab', 'gebunden', () =>
    EdCrypto.decrypt(CK, vi.faelle[0].text));
  await pruefe('lesen() nimmt Fassung 1 (ungebunden) und Fassung 2 (gebunden)', async () => {
    const alt = await EdCrypto.encrypt(ck, 'Prüf-ß-€');
    const a = await EdInhalt.lesen(ck, alt, 'kp', '');
    const kp = vi.faelle.find((f) => f.zweck === 'kp');
    const b = await EdInhalt.lesen(kp.schluessel_hex, kp.text, 'kp', '');
    return a.text === 'Prüf-ß-€' && a.gebunden === false && a.z === 0
      && b.gebunden === true && b.text === new TextDecoder().decode(hex(kp.nutzlast_hex));
  });
  await pruefe('Hülle Fassung 1 (edka1, edk1, ohne Kennung) geht über huelleOeffnen auf', async () => {
    const dk = vi.schluessel.dk_hex;
    const roh = await EdCrypto.encrypt(dk, CK);
    return (await EdInhalt.huelleOeffnen(dk, 'edka1:1a2b3c4d:' + roh.slice(5), 'pw')) === CK
      && (await EdInhalt.huelleOeffnen(dk, roh, 'pw')) === CK
      && (await EdInhalt.huelleOeffnen(dk, roh.slice(5), 'rc')) === CK;
  });
  await scheitert('Hülle edka1 als rc', 'kennung', async () =>
    EdInhalt.huelleOeffnen(vi.schluessel.dk_hex, 'edka1:1a2b3c4d:' + (await EdCrypto.encrypt(vi.schluessel.dk_hex, CK)).slice(5), 'rc'));
  await pruefe('0 bis 504 Byte Patientendaten ergeben dieselbe Länge; 505 die nächste Klasse', async () => {
    const l = [];
    for (const n of [0, 2, 100, 400, 504, 505]) {
      l.push((await EdInhalt.verschliessenBytes(ck, 'pat', 'r', new Uint8Array(n), 1)).length);
    }
    return new Set(l.slice(0, 5)).size === 1 && l[5] > l[0];
  });
  await pruefe('Rundlauf über 40 Längen: jede in einer Klasse, jede kommt zurück', async () => {
    for (const n of [0, 1, 119, 120, 121, 247, 248, 249, 503, 504, 505, 1015, 1016, 1017, 2039, 2040, 2041,
                     4087, 4088, 4089, 8183, 8184, 8185, 20000, 7, 14, 21, 28, 35, 42, 49, 56, 63, 70, 77,
                     84, 91, 98, 105, 112]) {
      const klar = Uint8Array.from({ length: n }, (_, i) => i % 256);
      const t = await EdInhalt.verschliessenBytes(ck, 'kp', '', klar, n);
      const o = await EdInhalt.oeffnenBytes(ck, t, 'kp', '');
      if (zuHex(o.nutzlast) !== zuHex(klar) || o.z !== n || !EdInhalt.istKlasse(ausB64(t.slice(5)).length - 28)) { return false; }
    }
    return true;
  });

  /* ------------------------------------------------------------ Standbuch */
  const st = vi.stand;
  for (const [ref, nr] of Object.entries(st.blattnummern)) {
    await pruefe(`Standbuch: Blatt von ${ref} ist ${nr}`, async () => (await EdStandbuch.blattNr(ref)) === nr);
  }
  await pruefe('Standbuch: Kennung', async () => (await EdStandbuch.kennung(st.client_ref)) === st.kennung);
  await pruefe('Standbuch: Blatt bauen byte-gleich', async () =>
    (await EdStandbuch.blattBauen(CK, st.blatt, st.version, st.t, st.eintraege, { fest: { nonce: hex(st.nonce_hex) } })) === st.text);
  await pruefe('Standbuch: Blatt öffnen (Einträge, Version)', async () => {
    const o = await EdStandbuch.blattOeffnen(CK, st.text, st.blatt, st.version);
    return gleich(o.blatt.e, st.eintraege) && o.version === st.version && o.blatt.t === st.t;
  });
  for (const f of vi.lagen) {
    const lesen = async () => {
      const blatt = f.blatt_text ? (await EdStandbuch.blattOeffnen(CK, f.blatt_text, f.blatt, f.anker)).blatt : null;
      const o = await EdStandbuch.patLesen(CK, f.text, f.client_ref, blatt);
      return { z: o.z, gebunden: o.gebunden, blatt_hinkt: o.blatt_hinkt };
    };
    if (f.grund) { await scheitert(`Lage ${f.name}`, f.grund, lesen); }
    else { await pruefe(`Lage ${f.name}`, async () => gleich(await lesen(), f.erwartet)); }
  }
  const gb = vi.geraetebuch;
  await pruefe('Gerätebuch: bauen byte-gleich', async () =>
    (await EdStandbuch.geraetebuchBauen(CK, gb.version, gb.geraete, { nonce: hex(gb.nonce_hex) })) === gb.text);
  await pruefe('Gerätebuch: öffnen (Version, Schlüssel, Austragung)', async () => {
    const o = await EdStandbuch.geraetebuchOeffnen(CK, gb.text, gb.version);
    return o.version === gb.version && gb.geraete.every((g) =>
      o.geraete[g.sid] && o.geraete[g.sid].bis === g.bis && btoa(String.fromCharCode(...o.geraete[g.sid].spk)) === g.spk);
  });
  for (const g of gb.gegenlagen) {
    await scheitert(`Gerätebuch ${g.name}`, g.grund, () => EdStandbuch.geraetebuchOeffnen(CK, g.text, g.anker));
  }

  /* ------------------------------------------------------------ Ableitung */
  for (const a of vi.ableitungen) {
    for (const [n, f] of [[1, a.fassung_1], [2, a.fassung_2]]) {
      await pruefe(`Ableitung ${n} (${f.runden} Runden): Hälfte und Token`, async () => {
        const k = await EdCrypto.deriveKeys(a.passwort, a.salz_hex, f.runden);
        return k.haelfteHex === f.haelfte_hex && k.authToken === f.token_hex
          && EdCrypto.ableitungFassung(f.runden) === n;
      });
    }
  }
  await pruefe('Ableitung: die Grenze entscheidet (1 199 999 → 1, 1 200 000 → 2)', async () =>
    EdCrypto.ableitungFassung(1199999) === 1 && EdCrypto.ableitungFassung(1200000) === 2
    && EdCrypto.ABLEITUNG2_AB === 1200000);

  /* ============================================================ Gerätehülle */
  const kpub = hex(v2.konto.oeffentlich_hex);
  let k = null;
  try { k = { priv: await privat(v2.konto.privat_hex, v2.konto.oeffentlich_hex), pub: kpub }; } catch (e) { /* s. o. */ }
  const buch = (name) => Object.fromEntries(Object.entries(v2.buecher[name])
    .map(([s, e]) => [s, { spk: hex(e.spk_hex), bis: e.bis }]));
  const leser = (name, verlangt2) => EdHuelle2.leser(k, buch(name || 'standard'), !!verlangt2);
  const absender = async (wer) => (wer === 'konto' ? { privat: k.priv, spk: kpub }
    : { privat: await privat(v2.geraete[wer].privat_hex, v2.geraete[wer].oeffentlich_hex),
        spk: hex(v2.geraete[wer].oeffentlich_hex) });

  await pruefe('Absenderkennung (SID) von Konto und Geräten', async () => {
    if (zuHex(await EdHuelle2.sid(kpub)) !== v2.konto.sid_hex) { return false; }
    for (const g of Object.values(v2.geraete)) {
      if (zuHex(await EdHuelle2.sid(hex(g.oeffentlich_hex))) !== g.sid_hex) { return false; }
    }
    return true;
  });
  for (const f of v2.faelle) {
    await pruefe(`2/${f.name} (${f.absender}): Hülle byte-gleich`, async () => {
      const epk = hex(f.epk_hex);
      const h = await EdHuelle2.verschliessen2(hex(f.klartext_hex), kpub, await absender(f.absender),
        f.art, f.client_ref, f.platz,
        { privat: await privat(f.fluechtig_privat_hex, f.epk_hex), epk: epk, iv: hex(f.iv_hex) });
      return zuHex(h) === zuHex(ausB64(f.huelle_b64));
    });
    await pruefe(`2/${f.name}: öffnen, Absender erkannt`, async () => {
      const o = await (await leser()).huelle(ausB64(f.huelle_b64), f.art, f.client_ref, f.platz);
      return zuHex(o.klar) === f.klartext_hex
        && o.absender === (f.absender === 'konto' ? 'konto' : v2.geraete[f.absender].sid_hex);
    });
  }
  /* Die Innenformen: aus dem Klartext der Referenz gelesen und neu gebaut. */
  for (const name of ['spur-3', 'spur-schluss', 'ruhe-schluss-leer']) {
    const f = v2.faelle.find((x) => x.name === name);
    await pruefe(`2/${name}: Kopf des Spurstücks byte-gleich`, async () => {
      const kopf = EdHuelle2.spurInnen2Lesen(hex(f.klartext_hex));
      return zuHex(EdHuelle2.spurInnen2(kopf.ts_von, kopf.nutzlast,
        { schluss: !!(kopf.merker & 1), luecke: !!(kopf.merker & 2), herkunft: kopf.herkunft,
          uebernommen: kopf.uebernommen })) === f.klartext_hex;
    });
  }
  for (const name of ['einsatz-json', 'konto-einsatz', 'umlaut-ref']) {
    const f = v2.faelle.find((x) => x.name === name);
    await pruefe(`2/${name}: Einsatzhülle innen byte-gleich`, async () => {
      const d = JSON.parse(new TextDecoder().decode(hex(f.klartext_hex)));
      return zuHex(EdHuelle2.einsatzInnen2(d.z, d.start, d.schluss, d.phasen, d.rea, d.herkunft)) === f.klartext_hex;
    });
  }
  for (const g of v2.gepackt) {
    await pruefe(`2/${g.name}: gepackt vom Konto — Kopf byte-gleich, Punkte`, async () => {
      const ls = await leser();
      const o = await ls.huelle(ausB64(g.huelle_b64), g.art, g.client_ref, g.platz);
      const kopf = EdHuelle2.spurInnen2Lesen(o.klar);
      const neu = EdHuelle2.spurInnen2(kopf.ts_von, new Uint8Array(0),
        { schluss: !!(kopf.merker & 1), herkunft: kopf.herkunft, uebernommen: kopf.uebernommen });
      const erg = await EdHuelle2.spurLesen(ls, g.art, g.client_ref,
        [{ seq_from: g.seq_from, punkte: g.punkte.length, stufe: g.stufe, n_original: g.n_original,
           ts_von: g.ts_von, huelle: g.huelle_b64 }],
        /* Das Stück der Referenz beginnt nicht bei 0; ein Löschvermerk davor
         * erklärt den fehlenden Anfang — sonst wäre es zu Recht `luecke`. */
        { sperren: g.seq_from ? [[0, g.ts_von - 1]] : [] });
      return o.absender === 'konto' && zuHex(neu) === g.kopf_hex
        && gleich(erg.punkte.map((p) => [p.seq, p.lat, p.lon, p.ele, p.ts]), g.punkte);
    });
  }
  for (const s of v2.stoerungen) {
    await scheitert(`2/Störung ${s.name}`, s.grund, async () =>
      (await leser('standard', s.verlangt2)).huelle(ausB64(s.huelle_b64), s.art, s.client_ref, s.platz));
  }
  for (const f of v2.folgen) {
    const lesen = async () => {
      const erg = await EdHuelle2.spurLesen(await leser(f.buch, f.verlangt2), f.art, f.client_ref,
        f.stuecke.map((x) => Object.assign({}, x, { huelle: x.huelle_b64 })),
        { versatz: f.versatz, sperren: f.sperren, abgeschlossen: f.abgeschlossen, stand: f.stand });
      const p = erg.punkte;
      return { punkte: p.length, erste_zeit: p[0].ts, letzte_zeit: p[p.length - 1].ts,
               herkunft: erg.befund.herkunft, schluss: erg.befund.schluss, fassung1: erg.befund.fassung1 };
    };
    if (f.grund) { await scheitert(`2/Folge ${f.name}`, f.grund, lesen); }
    else { await pruefe(`2/Folge ${f.name}`, async () => gleich(await lesen(), f.erwartet)); }
  }
  for (const f of v2.einsaetze) {
    const lesen = async () => {
      const o = await EdHuelle2.einsatzLesen(await leser(f.buch), f.client_ref, f.huelle_b64, f.started_ts,
        { versatz: f.versatz, stand: f.stand });
      return { z: o.inhalt.z === undefined ? null : o.inhalt.z, absender: o.absender };
    };
    if (f.grund) { await scheitert(`2/Einsatz ${f.name}`, f.grund, lesen); }
    else { await pruefe(`2/Einsatz ${f.name}`, async () => gleich(await lesen(), f.erwartet)); }
  }
  const kp = v2.kopplung;
  await pruefe('Kopplung: Zusage byte-gleich', async () =>
    zuHex(await EdHuelle2.kopplungZusage(hex(kp.n_g_hex), hex(kp.spk_hex))) === kp.zusage_hex);
  await pruefe(`Kopplung: Vergleichswert ${kp.wert}`, async () =>
    (await EdHuelle2.kopplungWert(hex(kp.konto_hex), hex(kp.spk_hex), hex(kp.n_g_hex), hex(kp.n_b_hex),
                                  hex(kp.zusage_hex))) === kp.wert);
  for (const g of kp.gegenlagen) {
    const wert = () => EdHuelle2.kopplungWert(hex(g.konto_hex), hex(g.spk_hex), hex(g.n_g_hex), hex(g.n_b_hex),
                                             g.zusage_hex ? hex(g.zusage_hex) : null);
    if (g.grund) { await scheitert(`Kopplung ${g.name}`, g.grund, wert); }
    else {
      await pruefe(`Kopplung ${g.name}: anderer Wert`, async () => {
        const w = await wert();
        return w === g.anderer_wert && w !== kp.wert;
      });
    }
  }
  await scheitert('Kopplung: Nonce mit falscher Länge', 'laenge', () =>
    EdHuelle2.kopplungWert(hex(kp.konto_hex), hex(kp.spk_hex), hex(kp.n_g_hex).slice(1), hex(kp.n_b_hex), null));
  await pruefe('Rundlauf: zufällige Hülle vom Konto geht auf, Absender „konto“', async () => {
    const klar = crypto.getRandomValues(new Uint8Array(1000));
    const h = await EdHuelle2.verschliessen2(klar, kpub, { privat: k.priv, spk: kpub }, 'spur', 'w-x', '0:70');
    const o = await (await leser()).huelle(h, 'spur', 'w-x', '0:70');
    return zuHex(o.klar) === zuHex(klar) && o.absender === 'konto' && h[0] === 2;
  });

  /* ================================================================ Weiche */
  const uhr = v2.geraete.uhr;
  const stueck = () => EdHuelle2.stueckSchreiben(k, 'spur', 'w-x', '0:1', { tsVon: 1784277065, schluss: true },
                                                 new Uint8Array(14));
  const einsatz1 = { fassung: 1, phasen: [{ phase: 4, t: 10, lat: 47.5, lon: 10.5 }], rea: [] };
  if (!weicheAn) {
    await pruefe('Weiche aus: schreibt2() ist false', async () => EdInhalt.schreibt2() === false);
    await pruefe('Weiche aus: schreiben() ergibt edk1, EdCrypto.decrypt öffnet es', async () => {
      const t = await EdInhalt.schreiben(ck, 'pat', 'w-x', '{"dx":"x"}', 5);
      return t.startsWith('edk1:') && (await EdCrypto.decrypt(ck, t)) === '{"dx":"x"}';
    });
    await pruefe('Weiche aus: Hüllen bleiben edka1 / edk1', async () => {
      const dk = vi.schluessel.dk_hex;
      return (await EdCrypto.huelleBauen(dk, CK, '1a2b3c4d')).startsWith('edka1:1a2b3c4d:')
        && (await EdInhalt.huelleSchreiben(dk, CK, 'pw', '1a2b3c4d')).startsWith('edka1:1a2b3c4d:')
        && (await EdInhalt.huelleSchreiben(vi.schluessel.rc_hex, CK, 'rc', null)).startsWith('edk1:');
    });
    await pruefe('Weiche aus: Spurstück und Einsatzhülle sind Fassung 1 und gehen wie bisher auf', async () => {
      const h = await stueck();
      const e = await EdHuelle2.einsatzSchreiben(k, 'w-x', { z: 1, start: 1784277065, schluss: true }, einsatz1);
      const innen = await EdCrypto.geraetehuelleOeffnen(e, k.priv, kpub, 'einsatz', 'w-x', '');
      return h[0] === 1 && e[0] === 1 && (await EdCrypto.geraetehuelleOeffnen(h, k.priv, kpub, 'spur', 'w-x', '0:1')).length === 14
        && gleich(JSON.parse(new TextDecoder().decode(innen)), einsatz1);
    });
    await pruefe('Weiche aus: das Standbuch sendet nichts', async () => {
      const tr = EdStandbuch.speicherTransport();
      const b = EdStandbuch.oeffnen({ konto: 'aus', schluessel: ck, transport: tr });
      const a = await b.fortschreiben('w-x', (e) => { e.p = 1; });
      const v = await b.vormerken('w-x', (e) => { e.p = 1; });
      const g = await b.geraetEintragen({ spk: hex(uhr.oeffentlich_hex), geraet: 'dev-uhr', seit: 1 });
      return a === null && v === null && g === null && (await b.nachtragen()) === null && tr.lager.size === 0;
    });
    return { zeilen, schlecht };
  }

  await pruefe('Weiche an: schreiben() ergibt edk2 und geht gebunden auf', async () => {
    const t = await EdInhalt.schreiben(ck, 'pat', 'w-x', '{"dx":"x"}', 5);
    const o = await EdInhalt.lesen(ck, t, 'pat', 'w-x');
    return t.startsWith('edk2:') && o.text === '{"dx":"x"}' && o.z === 5 && o.gebunden === true;
  });
  await pruefe('Weiche an: EdCrypto.huelleBauen ergibt edka2, und EdCrypto öffnet sie', async () => {
    const dk = vi.schluessel.dk_hex;
    const w = await EdCrypto.huelleBauen(dk, CK, '1a2b3c4d');
    const ohne = await EdCrypto.huelleBauen(dk, CK, null);
    return w.startsWith('edka2:1a2b3c4d:') && (await EdCrypto.huelleOeffnen(dk, w)) === CK
      && ohne.startsWith('edk2:') && (await EdCrypto.huelleOeffnen(dk, ohne)) === CK
      && (await EdInhalt.huelleSchreiben(vi.schluessel.rc_hex, CK, 'rc', null)).startsWith('edk2:');
  });
  await scheitert('Weiche an: Rückweg mit Anteil-Kennung schreiben', 'kennung', () =>
    EdInhalt.huelleSchreiben(vi.schluessel.rc_hex, CK, 'rc', '1a2b3c4d'));
  await pruefe('Weiche an: Spurstück vom Konto ist Fassung 2, Schluss und Zeit stehen innen', async () => {
    const h = await stueck();
    const erg = await EdHuelle2.spurLesen(await leser(), 'spur', 'w-x',
      [{ seq_from: 0, punkte: 1, stufe: 1, ts_von: 1784277065, huelle: h }], { abgeschlossen: true });
    return h[0] === 2 && erg.befund.schluss === true && gleich(erg.befund.herkunft, ['konto'])
      && erg.punkte.length === 1 && erg.punkte[0].ts === 1784277065;
  });
  await pruefe('Weiche an: Einsatzhülle vom Konto ist Fassung 2 mit Zähler', async () => {
    const e = await EdHuelle2.einsatzSchreiben(k, 'w-x', { z: 7, start: 1784277065, schluss: true }, einsatz1);
    const o = await EdHuelle2.einsatzLesen(await leser(), 'w-x', e, 1784277065, { stand: { o: 7, k: 1 } });
    return e[0] === 2 && o.absender === 'konto' && o.inhalt.z === 7 && gleich(o.inhalt.phasen, einsatz1.phasen);
  });
  await scheitert('Weiche an: dieselbe Einsatzhülle gegen einen höheren Stand', 'zurueckgelegt', async () =>
    EdHuelle2.einsatzLesen(await leser(), 'w-x',
      await EdHuelle2.einsatzSchreiben(k, 'w-x', { z: 7, start: 1784277065, schluss: true }, einsatz1),
      1784277065, { stand: { o: 8 } }));

  /* ------------------------------- Standbuch als Bibliothek, ohne Server */
  const REF = st.client_ref, NR = st.blatt;
  const lage = (name) => vi.lagen.find((l) => l.name === name);
  let tr = null;
  try { tr = EdStandbuch.speicherTransport(); } catch (e) { /* fehlt die Datei, ist jede Prüfung unten einzeln rot */ }
  const konto = 'probe-' + Math.random().toString(36).slice(2);
  const neuesBuch = (kto, transport) => EdStandbuch.oeffnen({ konto: kto || konto, schluessel: ck, transport: transport || tr });
  /* Ein zweiter Datensatz im selben Blatt wie REF — gesucht, nicht geraten. */
  const imBlatt = async (nr, praefix) => {
    for (let i = 0; ; i++) { if ((await EdStandbuch.blattNr(praefix + i)) === nr) { return praefix + i; } }
  };
  let b1 = null, REF2 = null, REF3 = null;
  await pruefe('Buch: neuer Browser übernimmt die Abschrift, der Anker steht', async () => {
    tr.lager.set(String(NR), { version: st.version, text: st.text });
    b1 = neuesBuch();
    REF2 = await imBlatt(NR, 'zweiter-'); REF3 = await imBlatt(NR, 'dritter-');
    const bef = await b1.abgleichen();
    return bef.length === 0 && (await b1.anker(NR)) === st.version && gleich(await b1.eintrag(REF), st.eintraege[st.kennung])
      && (await b1.naechsterZaehler(REF)) === 4 && b1.ablage.dauerhaft === true;
  });
  await pruefe('Buch: Block am Stand geht auf, das Blatt hinkt nicht', async () => {
    const o = await b1.patLesen(lage('gut').text, REF);
    return o.z === 3 && o.gebunden && !o.blatt_hinkt && o.text === new TextDecoder().decode(hex(vi.faelle[0].nutzlast_hex));
  });
  await scheitert('Buch: zurückgelegter Block', 'zurueckgelegt', () => b1.patLesen(lage('zurueckgelegt').text, REF));
  await pruefe('Buch: ein Block über dem Stand geht auf, und der Browser merkt ihn sich', async () => {
    const o = await b1.patLesen(lage('blatt-hinkt').text, REF);
    return o.z === 4 && o.blatt_hinkt === true && (await b1.eintrag(REF)).p === 4 && tr.lager.get(String(NR)).version === st.version;
  });
  await scheitert('Buch: danach ist der vorige Block zurückgelegt', 'zurueckgelegt', () => b1.patLesen(lage('gut').text, REF));
  await pruefe('Buch: nachtragen() legt das Blatt ab — Version + 1, Zähler 4, übrige Felder bleiben', async () => {
    const geschrieben = await b1.nachtragen();
    const srv = tr.lager.get(String(NR));
    const o = await EdStandbuch.blattOeffnen(ck, srv.text, NR, null);
    return gleich(geschrieben, [NR]) && srv.version === st.version + 1 && o.version === srv.version
      && gleich(o.blatt.e[st.kennung], Object.assign({}, st.eintraege[st.kennung], { p: 4 }))
      && gleich(await b1.nachtragen(), []);
  });
  await pruefe('Buch: der Stand übersteht eine neue Seite (Ablage), auch ohne Server', async () => {
    const b = neuesBuch(konto, EdStandbuch.speicherTransport());
    return (await b.anker(NR)) === st.version + 1 && (await b.eintrag(REF)).p === 4;
  });
  await pruefe('Buch: der Server legt das alte Blatt zurück — Befund, und der Stand bleibt', async () => {
    const alt = EdStandbuch.speicherTransport();
    alt.lager.set(String(NR), { version: st.version, text: st.text });
    const b = neuesBuch(konto, alt);
    const bef = await b.abgleichen();
    return bef.length === 1 && bef[0].wer === NR && bef[0].grund === 'blatt-zurueckgelegt' && (await b.eintrag(REF)).p === 4;
  });
  await pruefe('Buch: der Server nennt eine höhere Version, liefert aber das alte Blatt — Befund', async () => {
    const lueg = EdStandbuch.speicherTransport();
    lueg.lager.set(String(NR), { version: st.version + 5, text: st.text });
    const bef = await neuesBuch(konto, lueg).abgleichen();
    return bef.length === 1 && bef[0].grund === 'blatt-zurueckgelegt';
  });
  await pruefe('Buch: ein Blatt unter fremder Nummer — Befund pruefwert', async () => {
    const falsch = EdStandbuch.speicherTransport();
    falsch.lager.set(String((NR + 1) % 16), { version: st.version, text: st.text });
    const bef = await neuesBuch('fremd-' + konto, falsch).abgleichen();
    return bef.length === 1 && bef[0].grund === 'pruefwert';
  });
  let b2 = null;
  await pruefe('Buch: zwei Browser, gleichzeitig — der zweite bekommt den Konflikt und schreibt auf dem Stand des ersten', async () => {
    b2 = neuesBuch('zweit-' + konto);
    await b2.abgleichen();                                 // beide bei Version 18
    const v1 = await b1.fortschreiben(REF, (e) => { e.o = 5; e.k = 1; });
    const v2_ = await b2.fortschreiben(REF2, (e) => { e.p = 1; });
    const o = await EdStandbuch.blattOeffnen(ck, tr.lager.get(String(NR)).text, NR, null);
    return v1 === st.version + 2 && v2_ === st.version + 3 && o.version === v2_
      && o.blatt.e[st.kennung].o === 5 && o.blatt.e[st.kennung].p === 4
      && o.blatt.e[await EdStandbuch.kennung(REF2)].p === 1;
  });
  await pruefe('Buch: vormerken() — Blatt fährt mit, 409, neue Vormerkung auf dem Stand des Servers', async () => {
    const v = await b1.vormerken(REF3, (e) => { e.p = (e.p || 0) + 1; });      // b1 steht noch bei 19
    const a = await tr.ablegen(v.wer, v);
    if (a.ok || !a.konflikt || (await b1.anker(NR)) !== st.version + 2) { return false; }
    const w = await v.konflikt({ version: a.version, text: a.text });
    const a2 = await tr.ablegen(w.wer, w);
    await w.bestaetigen();
    const o = await EdStandbuch.blattOeffnen(ck, tr.lager.get(String(NR)).text, NR, null);
    return a2.ok && w.version === st.version + 4 && (await b1.anker(NR)) === w.version
      && o.blatt.e[await EdStandbuch.kennung(REF3)].p === 1 && o.blatt.e[await EdStandbuch.kennung(REF2)].p === 1;
  });
  await pruefe('Buch: eine Vormerkung ohne Bestätigung ändert nichts', async () => {
    const vorher = await b1.anker(NR);
    await b1.vormerken(REF3, (e) => { e.p = 99; });
    return (await b1.anker(NR)) === vorher && (await b1.eintrag(REF3)).p === 1;
  });
  await pruefe('Buch: 60 Blöcke gleichzeitig über dem Stand — alle nachgetragen, keiner verloren', async () => {
    const refs = Array.from({ length: 60 }, (_, i) => 'gleichzeitig-' + i);
    const texte = await Promise.all(refs.map((r) => EdInhalt.verschliessenBytes(ck, 'pat', r, new Uint8Array(3), 2)));
    const erg = await Promise.all(refs.map((r, i) => b1.patLesen(texte[i], r)));
    await b1.nachtragen();
    for (const r of refs) {
      const nr = await EdStandbuch.blattNr(r);
      const o = await EdStandbuch.blattOeffnen(ck, tr.lager.get(String(nr)).text, nr, null);
      if ((o.blatt.e[await EdStandbuch.kennung(r)] || {}).p !== 2) { return false; }
    }
    const neu = neuesBuch(konto, EdStandbuch.speicherTransport());
    for (const r of refs) { if ((await neu.eintrag(r)).p !== 2) { return false; } }
    return erg.every((o) => o.blatt_hinkt && o.z === 2);
  });
  await pruefe('Buch: Altbestand (Fassung 1) geht auf, solange das Blatt offen ist', async () => {
    const alt = await EdCrypto.encrypt(ck, '{"dx":"uralt"}');
    const o = await b1.patLesen(alt, await imBlatt(NR, 'alt-'));
    return o.gebunden === false && o.z === 0 && o.text === '{"dx":"uralt"}' && (await b1.alleZu()) === false;
  });
  await scheitert('Buch: nach schliessen() ist Fassung 1 in diesem Blatt ein Befund', 'altformat', async () => {
    await b1.schliessen(NR);
    return b1.patLesen(await EdCrypto.encrypt(ck, '{"dx":"uralt"}'), await imBlatt(NR, 'alt-'));
  });

  /* Das Gerätebuch über denselben Weg. */
  let sidUhr = null;
  await pruefe('Gerätebuch: eintragen nach der Bestätigung — die Hülle der Uhr geht damit auf', async () => {
    sidUhr = await b1.geraetEintragen({ spk: hex(uhr.oeffentlich_hex), geraet: 'dev-uhr', seit: 1784190665 });
    const f = v2.faelle.find((x) => x.name === 'spur-3');
    const o = await (await EdHuelle2.leser(k, await b1.geraete())).huelle(f.huelle_b64, f.art, f.client_ref, f.platz);
    return sidUhr === uhr.sid_hex && o.absender === uhr.sid_hex && tr.lager.get('buch').version === 1;
  });
  await scheitert('Gerätebuch: das Handy steht nicht darin', 'absender', async () => {
    const f = v2.faelle.find((x) => x.name === 'leer');
    return (await EdHuelle2.leser(k, await b1.geraete())).huelle(f.huelle_b64, f.art, f.client_ref, f.platz);
  });
  await scheitert('Gerätebuch: ausgetragen — die Folge der Uhr danach ist gesperrt', 'absender-gesperrt', async () => {
    const f = v2.folgen.find((x) => x.name === 'geraet-ausgetragen');
    await b1.geraetAustragen(sidUhr, v2.buecher['uhr-ausgetragen'][sidUhr].bis);
    return EdHuelle2.spurLesen(await EdHuelle2.leser(k, await b1.geraete()), f.art, f.client_ref,
      f.stuecke.map((x) => Object.assign({}, x, { huelle: x.huelle_b64 })), { abgeschlossen: true });
  });
  await pruefe('Gerätebuch: zurückgelegt — Befund, und das Gerät bleibt ausgetragen', async () => {
    const alt = EdStandbuch.speicherTransport();
    alt.lager.set('buch', { version: 1, text: await EdStandbuch.geraetebuchBauen(ck, 1, []) });
    const b = neuesBuch(konto, alt);
    const bef = await b.abgleichen();
    const g = await b.geraete();
    return bef.some((x) => x.wer === 'buch' && x.grund === 'buch-zurueckgelegt') && g[sidUhr].bis !== null
      && (await b.anker('buch')) === 2;
  });
  await pruefe('Buch: zurueckschreiben() legt den eigenen Stand über einen zurückgesetzten Server', async () => {
    const alt = EdStandbuch.speicherTransport();
    alt.lager.set('buch', { version: 1, text: await EdStandbuch.geraetebuchBauen(ck, 1, []) });
    const b = neuesBuch(konto, alt);
    const v = await b.zurueckschreiben('buch');
    const o = await EdStandbuch.geraetebuchOeffnen(ck, alt.lager.get('buch').text, null);
    return v === 3 && o.version === 3 && o.geraete[sidUhr].bis !== null && (await b.abgleichen()).every((x) => x.wer !== 'buch');
  });
  return { zeilen, schlecht };
};
