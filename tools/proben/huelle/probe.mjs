/* Hüllenprobe im Browser — die WebCrypto-Umsetzung gegen die Prüfvektoren.
 *
 * Anlass: Nr. 43, Nr. 563 — drei Umsetzungen eines Formats müssen byte-gleich
 * sein, und jede Gegenlage muss mit ihrem Grund scheitern.
 *
 * WAS. Lädt `crypto.js`, `inhalt2.js`, `huelle2.js` und `standbuch.js` in
 * eine Seite und hält sie gegen `vektoren-2.json` und `vektoren-inhalt.json`
 * aus `referenz.py` und `referenz_inhalt.py`: `edk2:`, Schlüsselhüllen,
 * Standbuch, Gerätebuch, Ableitung 2, Gerätehülle Fassung 2 mit ihren
 * Leseregeln und der Vergleichswert der Kopplung — die Prüfungen stehen in
 * `pruefungen2.js` daneben. Dazu, ohne Abnahme, die Zeiten für Verschließen
 * und Öffnen eines Spurstücks zu 200 und 500 Punkten in beiden Innenformen
 * (Median aus 9), Absender das Konto.
 *
 * FASSUNG 1 (Kennbyte 0x01, `edk1:`, `edka1:`) misst diese Probe seit
 * KA-07 (Web 27.0.0) nur noch als Gegenlage: Sie muss überall scheitern
 * (`ohne-herkunft`, `kennung`, `altformat`). Bis dahin lief vorweg eine
 * eigene Seite gegen `vektoren.json` und die Gerätehülle der Fassung 1 aus
 * `crypto.js`; beide sind entfernt.
 *
 * `--bestand <Ordner>` nimmt die Skripte aus einem anderen Ordner als
 * `server/assets/` — für die Gegenprobe gegen einen älteren Stand. Was dort
 * fehlt, fehlt der Seite; die Prüfungen dazu sind dann einzeln rot.
 *
 * WARUM EINE ERFUNDENE HTTPS-ADRESSE. WebCrypto gibt es nur in einem sicheren
 * Kontext; `about:blank` und `data:` sind es nicht in jeder Engine. Die Seite
 * wird deshalb unter `https://huelle.invalid/` aus der Probe selbst
 * beantwortet — keine Anlage, kein Netz.
 *
 * Aufruf:  node tools/proben/huelle/probe.mjs [--motor chromium|firefox|webkit] [--drossel N]
 *                                             [--bestand <Ordner>]
 * Rückgabe: 0 alles gleich · 1 Abweichung · 2 nicht gelaufen
 */
import { readFileSync, existsSync } from 'node:fs';
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
const bi = process.argv.indexOf('--bestand');
const BESTAND = bi >= 0 ? path.resolve(process.argv[bi + 1]) : path.join(HIER, '../../../server/assets');
const vektoren2 = JSON.parse(readFileSync(path.join(HIER, 'vektoren-2.json'), 'utf-8'));
const vektorenInhalt = JSON.parse(readFileSync(path.join(HIER, 'vektoren-inhalt.json'), 'utf-8'));
const pruefungen2 = readFileSync(path.join(HIER, 'pruefungen2.js'), 'utf-8');
/* Die Dateien, in der Reihenfolge, in der die Anwendung sie einbindet. Eine,
 * die im Bestand fehlt, wird nicht geladen. */
const DATEIEN = ['crypto.js', 'inhalt2.js', 'huelle2.js', 'standbuch.js'];
const fehlend = DATEIEN.filter((n) => !existsSync(path.join(BESTAND, n)));

let browser;
try { browser = await starten(pw, motor); }
catch (e) { console.log(`${motor} startet nicht — nicht gelaufen: ${String(e.message).split('\n')[0]}`); process.exit(2); }
const kontext = await browser.newContext();
await kontext.route('https://huelle.invalid/**', (r) => r.fulfill({
  status: 200, contentType: 'text/html', body: '<!doctype html><title>Hüllenprobe</title>' }));
const seite = await kontext.newPage();
const seitenfehler = [];
seite.on('pageerror', (e) => seitenfehler.push(String(e.message).split('\n')[0]));
await seite.goto('https://huelle.invalid/');
if (drossel > 1) {
  if (motor !== 'chromium') { console.log('--drossel geht nur mit chromium'); process.exit(2); }
  const cdp = await kontext.newCDPSession(seite);
  await cdp.send('Emulation.setCPUThrottlingRate', { rate: drossel });
}
for (const n of DATEIEN) {
  if (fehlend.includes(n)) { continue; }
  await seite.addScriptTag({ content: readFileSync(path.join(BESTAND, n), 'utf-8') });
}
await seite.addScriptTag({ content: pruefungen2 });
const erg = await seite.evaluate(([v2, vi]) => window.hpFassung2(v2, vi), [vektoren2, vektorenInhalt]);

/* Zeiten — Auskunft, keine Abnahme. Dieselben Innenformen wie referenz.py;
 * verschlossen wird als Konto (wie Formular, Wartungslauf und Schnitt). */
let zeiten = {};
try {
  zeiten = await seite.evaluate(async (v2) => {
    const hex = (h) => new Uint8Array((h.match(/../g) || []).map((x) => parseInt(x, 16)));
    const b64url = (b) => btoa(String.fromCharCode(...b)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    const pub = hex(v2.konto.oeffentlich_hex), d = hex(v2.konto.privat_hex);
    const priv = await crypto.subtle.importKey('jwk', { kty: 'EC', crv: 'P-256', d: b64url(d),
      x: b64url(pub.slice(1, 33)), y: b64url(pub.slice(33, 65)), ext: true },
      { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']);
    const ls = await EdHuelle2.leser({ priv, pub }, {});
    const aus = {};
    for (const n of [200, 500]) {
      const bin = new Uint8Array(n * 14);
      const dv = new DataView(bin.buffer);
      const pkt = [];
      for (let i = 0; i < n; i++) {
        const la = 47.7261 + i * 0.00031, lo = 10.3159 + i * 0.00047, he = 674 + (i % 37) * 0.7;
        dv.setInt32(i * 14, Math.round(la * 1e7)); dv.setInt32(i * 14 + 4, Math.round(lo * 1e7));
        dv.setInt16(i * 14 + 8, Math.round(he)); dv.setUint32(i * 14 + 10, i * 5);
        pkt.push([+la.toFixed(6), +lo.toFixed(6), +he.toFixed(1), i * 5]);
      }
      const json = new TextEncoder().encode(JSON.stringify({ p: pkt }));
      for (const [form, klar] of [['binaer', bin], ['json', json]]) {
        const zu = [], auf = [];
        let h;
        for (let r = 0; r < 9; r++) {
          let t = performance.now();
          h = await EdHuelle2.verschliessen2(klar, pub, { privat: priv, spk: pub }, 'spur', 'w-x', `0:${n}`);
          zu.push(performance.now() - t);
          t = performance.now(); await ls.huelle(h, 'spur', 'w-x', `0:${n}`); auf.push(performance.now() - t);
        }
        const med = (a) => Math.round(a.sort((x, y) => x - y)[4] * 100) / 100;
        aus[`${form}-${n}`] = { innen_byte: klar.length, huelle_byte: h.length,
                                verschliessen_ms: med(zu), oeffnen_ms: med(auf) };
      }
    }
    return aus;
  }, vektoren2);
} catch (e) { zeiten = { fehler: String(e.message).split('\n')[0] }; }
await browser.close();

console.log(`Hüllenprobe in ${motor}${drossel > 1 ? `, Drossel ${drossel}×` : ''}`
  + (bi >= 0 ? ` — Bestand ${BESTAND}` : ''));
for (const f of fehlend) { console.log(`  fehlt im Bestand: ${f}`); }
console.log(erg.zeilen.join('\n'));
for (const f of new Set(seitenfehler)) { console.log(`  Seitenfehler: ${f}`); }
for (const a of erg.ausgenommen || []) { console.log(`  ----  nicht gemessen: ${a}`); }
for (const [k, z] of Object.entries(zeiten)) {
  if (k === 'fehler') { console.log(`  Zeit  nicht gemessen: ${z}`); continue; }
  console.log(`  Zeit  ${k}: innen ${z.innen_byte} B, Hülle ${z.huelle_byte} B, `
    + `verschließen ${z.verschliessen_ms} ms, öffnen ${z.oeffnen_ms} ms`);
}
/* Ausgenommen heißt: Der Vektor verlangt es, die Seite kann es noch nicht.
 * Es zählt nicht als Prüfung und steht in der Schlusszeile, solange es das gibt. */
const offen = (erg.ausgenommen || []).length;
console.log(`${erg.zeilen.length} Prüfungen, ${erg.schlecht} Abweichungen`
  + (offen ? `, ${offen} ausgenommen (nicht gemessen)` : ''));
process.exit(erg.schlecht ? 1 : 0);
