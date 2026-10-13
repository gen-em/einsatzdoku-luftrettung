/* Hüllenprobe im Browser — die WebCrypto-Umsetzung gegen die Prüfvektoren.
 *
 * Anlass: Nr. 43 — drei Umsetzungen eines Formats müssen byte-gleich sein.
 *
 * WAS. Lädt `server/assets/crypto.js` (seit S11-03; bis dahin die Vorlage
 * `huelle.js`) in eine Seite und prüft dessen Gerätehülle gegen
 * `vektoren.json` aus `referenz.py`:
 *   je Fall    Z (ECDH), k_enc und k_mac (HKDF), die ganze Hülle byte-gleich
 *              bei festem flüchtigem Schlüssel und IV, und das Öffnen
 *   je Störung das Öffnen scheitert, und zwar mit dem erwarteten Grund
 *   Rundlauf   eine Hülle mit zufälligem Schlüssel und IV geht auf
 * Dazu, ohne Abnahme, die Zeiten für Verschließen und Öffnen eines
 * Spurstücks zu 200 und 500 Punkten in beiden Innenformen (Median aus 9).
 *
 * WARUM EINE ERFUNDENE HTTPS-ADRESSE. WebCrypto gibt es nur in einem sicheren
 * Kontext; `about:blank` und `data:` sind es nicht in jeder Engine. Die Seite
 * wird deshalb unter `https://huelle.invalid/` aus der Probe selbst
 * beantwortet — keine Anlage, kein Netz.
 *
 * Aufruf:  node tools/proben/huelle/probe.mjs [--motor chromium|firefox|webkit] [--drossel N]
 * Rückgabe: 0 alles gleich · 1 Abweichung · 2 nicht gelaufen
 */
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const HIER = path.dirname(fileURLToPath(import.meta.url));
const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { motorWahl, starten } = await import(path.join(HIER, '../../motor.mjs'));

let pw;
try { pw = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL); }
catch (e) { console.log(`Playwright fehlt (${MODUL}) — nicht gelaufen`); process.exit(2); }

const motor = motorWahl(process.argv);
const di = process.argv.indexOf('--drossel');
const drossel = di >= 0 ? Number(process.argv[di + 1]) : 1;
const vektoren = JSON.parse(readFileSync(path.join(HIER, 'vektoren.json'), 'utf-8'));
const quelle = readFileSync(path.join(HIER, '../../../server/assets/crypto.js'), 'utf-8');

let browser;
try { browser = await starten(pw, motor); }
catch (e) { console.log(`${motor} startet nicht — nicht gelaufen: ${String(e.message).split('\n')[0]}`); process.exit(2); }
const kontext = await browser.newContext();
await kontext.route('https://huelle.invalid/**', (r) => r.fulfill({
  status: 200, contentType: 'text/html', body: '<!doctype html><title>Hüllenprobe</title>' }));
const seite = await kontext.newPage();
await seite.goto('https://huelle.invalid/');
if (drossel > 1) {
  if (motor !== 'chromium') { console.log('--drossel geht nur mit chromium'); process.exit(2); }
  const cdp = await kontext.newCDPSession(seite);
  await cdp.send('Emulation.setCPUThrottlingRate', { rate: drossel });
}
await seite.addScriptTag({ content: quelle });

const ergebnis = await seite.evaluate(async (v) => {
  const hex = (h) => new Uint8Array((h.match(/../g) || []).map((x) => parseInt(x, 16)));
  const zuHex = (b) => Array.from(b, (x) => x.toString(16).padStart(2, '0')).join('');
  const ausB64 = (t) => Uint8Array.from(atob(t), (c) => c.charCodeAt(0));
  /* Die Gerätehülle aus crypto.js; den privaten Schlüssel aus d baut die
   * Probe selbst (JWK) — die Anwendung holt ihn aus seiner Hülle. */
  const E = EdCrypto;
  const b64url = (b) => btoa(String.fromCharCode(...b)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  const H = {
    privat: (d, pub) => crypto.subtle.importKey('jwk', { kty: 'EC', crv: 'P-256', d: b64url(d),
      x: b64url(pub.slice(1, 33)), y: b64url(pub.slice(33, 65)), ext: true },
      { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']),
    bindung: E.geraetehuelleBindung,
    ableiten: E.geraetehuelleAbleiten,
    verschliessen: async (klar, pub, art, ref, platz, fest) => E.geraetehuelleVerschliessen(klar, pub, art, ref, platz,
      fest ? { privat: await H.privat(fest.fluechtigD, fest.fluechtigPub), epk: fest.fluechtigPub, iv: fest.iv } : undefined),
    oeffnen: E.geraetehuelleOeffnen,
  };
  const zeilen = [];
  let schlecht = 0;
  const melde = (gut, text) => { zeilen.push(`  ${gut ? 'ok  ' : 'FEHL'}  ${text}`); if (!gut) { schlecht++; } };

  const empfPub = hex(v.empfaenger.oeffentlich_hex);
  const empfPriv = await H.privat(hex(v.empfaenger.privat_hex), empfPub);

  for (const f of v.faelle) {
    const fl = hex(f.fluechtig_privat_hex), epk = hex(f.epk_hex);
    const flKey = await H.privat(fl, epk);
    const empfKey = await crypto.subtle.importKey('raw', empfPub, { name: 'ECDH', namedCurve: 'P-256' }, false, []);
    const k = await H.ableiten(flKey, empfKey, epk, empfPub);
    melde(zuHex(k.z) === f.z_hex, `${f.name}: ECDH (Z)`);
    melde(zuHex(k.kEnc) === f.k_enc_hex && zuHex(k.kMac) === f.k_mac_hex, `${f.name}: HKDF (k_enc, k_mac)`);
    melde(zuHex(H.bindung(f.art, f.client_ref, f.platz)) === f.bindung_hex, `${f.name}: Bindung`);
    const h = await H.verschliessen(hex(f.klartext_hex), empfPub, f.art, f.client_ref, f.platz,
                                    { fluechtigD: fl, fluechtigPub: epk, iv: hex(f.iv_hex) });
    melde(zuHex(h) === zuHex(ausB64(f.huelle_b64)), `${f.name}: Hülle byte-gleich (${h.length} Byte)`);
    try {
      const k2 = await H.oeffnen(ausB64(f.huelle_b64), empfPriv, empfPub, f.art, f.client_ref, f.platz);
      melde(zuHex(k2) === f.klartext_hex, `${f.name}: öffnen`);
    } catch (e) { melde(false, `${f.name}: öffnen — ${e.message}`); }
  }
  for (const st of v.stoerungen) {
    try {
      await H.oeffnen(ausB64(st.huelle_b64), empfPriv, empfPub, st.art, st.client_ref, st.platz);
      melde(false, `Störung ${st.name}: ging auf`);
    } catch (e) {
      melde(e.grund === st.grund, `Störung ${st.name}: abgewiesen (${e.grund || e.message})`);
    }
  }
  const zufall = crypto.getRandomValues(new Uint8Array(1000));
  const hz = await H.verschliessen(zufall, empfPub, 'spur', 'w-x', '0:70');
  const kz = await H.oeffnen(hz, empfPriv, empfPub, 'spur', 'w-x', '0:70');
  melde(zuHex(kz) === zuHex(zufall), 'Rundlauf mit zufälligem Schlüssel und IV');

  /* Zeiten — Auskunft, keine Abnahme. Dieselben Innenformen wie referenz.py. */
  const zeiten = {};
  for (const n of [200, 500]) {
    const bin = new Uint8Array(n * 14);
    const dv = new DataView(bin.buffer);
    const pkt = [];
    for (let i = 0; i < n; i++) {
      const la = 47.7261 + i * 0.00031, lo = 10.3159 + i * 0.00047, he = 674 + (i % 37) * 0.7;
      dv.setInt32(i * 14, Math.round(la * 1e7)); dv.setInt32(i * 14 + 4, Math.round(lo * 1e7));
      dv.setInt16(i * 14 + 8, Math.round(he * 10)); dv.setUint32(i * 14 + 10, i * 5);
      pkt.push([+la.toFixed(6), +lo.toFixed(6), +he.toFixed(1), i * 5]);
    }
    const json = new TextEncoder().encode(JSON.stringify({ p: pkt }));
    for (const [form, klar] of [['binaer', bin], ['json', json]]) {
      const zu = [], auf = [];
      let h;
      for (let r = 0; r < 9; r++) {
        let t = performance.now(); h = await H.verschliessen(klar, empfPub, 'spur', 'w-x', `0:${n}`); zu.push(performance.now() - t);
        t = performance.now(); await H.oeffnen(h, empfPriv, empfPub, 'spur', 'w-x', `0:${n}`); auf.push(performance.now() - t);
      }
      const med = (a) => Math.round(a.sort((x, y) => x - y)[4] * 100) / 100;
      zeiten[`${form}-${n}`] = { innen_byte: klar.length, huelle_byte: h.length,
                                 verschliessen_ms: med(zu), oeffnen_ms: med(auf) };
    }
  }
  return { zeilen, schlecht, zeiten };
}, vektoren);

await browser.close();
console.log(`Hüllenprobe in ${motor}${drossel > 1 ? `, Drossel ${drossel}×` : ''}`);
console.log(ergebnis.zeilen.join('\n'));
for (const [k, z] of Object.entries(ergebnis.zeiten)) {
  console.log(`  Zeit  ${k}: innen ${z.innen_byte} B, Hülle ${z.huelle_byte} B, `
    + `verschließen ${z.verschliessen_ms} ms, öffnen ${z.oeffnen_ms} ms`);
}
const n = ergebnis.zeilen.length;
console.log(`${n} Prüfungen, ${ergebnis.schlecht} Abweichungen`);
process.exit(ergebnis.schlecht ? 1 : 0);
