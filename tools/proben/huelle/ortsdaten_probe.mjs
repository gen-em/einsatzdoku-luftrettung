/**
 * Die Ortsdaten der Einsatzansicht über den Rückweg (KA-10, E-KA-70, -88).
 *
 * Anlass: Nr. 563 — die Hüllenprobe (`probe.mjs`) lädt `ortsdaten.js` nicht;
 * Fortschreiben, Hinweis, Bestätigung und Befunde über `EdOrtsdaten` maß
 * niemand. Diese Probe fährt sie mit umgelegter Weiche (nur in dieser Seite)
 * gegen die Fälle `rueckweg` aus `vektoren-2.json`, Standbuch im Speicher.
 * Aufruf: node ortsdaten_probe.mjs [--motor chromium|firefox|webkit]
 */
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const HIER = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.join(HIER, '../../..');
const A = path.join(ROOT, 'server/assets');
const { motorWahl, starten } = await import(path.join(ROOT, 'tools/motor.mjs'));
const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const pw = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);
const browser = await starten(pw, motorWahl(process.argv));
const kx = await browser.newContext();
await kx.route('https://huelle.invalid/**', (r) => r.fulfill({ status: 200, contentType: 'text/html', body: '<!doctype html><title>x</title>' }));
const s = await kx.newPage();
await s.goto('https://huelle.invalid/');
s.on('pageerror', (e) => console.log('Seitenfehler', e.message));
for (const n of ['crypto.js', 'inhalt2.js', 'huelle2.js', 'standbuch.js', 'ortsdaten.js']) {
  let t = readFileSync(path.join(A, n), 'utf-8');
  if (n === 'inhalt2.js') { t = t.replace(/const SCHREIBT_FASSUNG_2 = (?:true|false);/, 'const SCHREIBT_FASSUNG_2 = true;'); }
  await s.addScriptTag({ content: t });
}
const v2 = JSON.parse(readFileSync(path.join(ROOT, 'tools/proben/huelle/vektoren-2.json'), 'utf-8'));
const vi = JSON.parse(readFileSync(path.join(ROOT, 'tools/proben/huelle/vektoren-inhalt.json'), 'utf-8'));
const erg = await s.evaluate(async ([v2, vi]) => {
  const zeilen = []; let rot = 0;
  const melde = (ok, t) => { zeilen.push((ok ? '  ok    ' : '  FEHL  ') + t); if (!ok) { rot++; } };
  const hex = (h) => new Uint8Array((h.match(/../g) || []).map((x) => parseInt(x, 16)));
  const b64url = (b) => btoa(String.fromCharCode(...b)).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
  const gleich = (a, b) => JSON.stringify(a) === JSON.stringify(b);
  const pub = hex(v2.konto.oeffentlich_hex), d = hex(v2.konto.privat_hex);
  const priv = await crypto.subtle.importKey('jwk', { kty: 'EC', crv: 'P-256', d: b64url(d), x: b64url(pub.slice(1, 33)), y: b64url(pub.slice(33, 65)), ext: true }, { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']);
  const ck = await crypto.subtle.importKey('raw', hex(vi.schluessel.ck_hex), { name: 'AES-GCM' }, false, ['encrypt', 'decrypt']);
  const k = { priv, pub, ck };
  let buchJetzt = null;
  EdStandbuch.seitenbuch = async () => buchJetzt;
  let nr = 0;
  const neu = async (tr) => {
    const b = EdStandbuch.oeffnen({ konto: 'od-' + (nr++) + Math.random(), schluessel: ck, transport: tr });
    await b.abgleichen();
    return b;
  };
  const geraete = async (b) => { for (const g of ['uhr', 'handy']) { await b.geraetEintragen({ spk: hex(v2.geraete[g].oeffentlich_hex), geraet: g, seit: 1 }); } };
  const setze = (stand) => (e) => { for (const f of ['o', 'k', 'a', 'f', 'u']) { delete e[f]; } Object.assign(e, stand); };
  const fall = (n) => v2.rueckweg.einsaetze.find((f) => f.name === n);
  const M = (f) => ({ ort_huelle: f.huelle_b64, client_ref: f.client_ref, started_ts: f.started_ts });
  const standVon = async (b, ref) => { const e = await b.eintrag(ref); const a = {}; for (const f of ['o', 'k', 'a', 'f', 'u']) { if (e[f] != null) { a[f] = e[f]; } } return a; };
  const ruhe = () => new Promise((r) => setTimeout(r, 150));

  /* 1. Hinweis kommt unverändert an; bestätigen schreibt den Stand der Referenz. */
  const fh = fall('anderer-absender-kleiner'), fb = fall('anderer-absender-kleiner-bestaetigt');
  let tr = EdStandbuch.speicherTransport();
  let a = await neu(tr); await geraete(a);
  await a.fortschreiben(fh.client_ref, setze(fh.stand));
  buchJetzt = a;
  let h = null;
  try { await EdOrtsdaten.einsatzOeffnen(M(fh), k); melde(false, 'Hinweis: ging auf'); }
  catch (e) { h = e.hinweis; melde(e.grund === 'uhrzeit-pruefen' && gleich(e.hinweis, fh.hinweis) && e.inhalt && e.inhalt.z === fh.hinweis.z && e.art === 'einsatz', 'einsatzOeffnen: Hinweis-Fehler mit hinweis, inhalt, art'); }
  try { await EdOrtsdaten.einsatz(M(fh), k); melde(false, 'einsatz(): ging auf'); } catch (e) { melde(e.grund === 'uhrzeit-pruefen', 'einsatz(): derselbe Hinweis-Fehler'); }
  await ruhe();
  melde(gleich(await standVon(a, fh.client_ref), fh.stand), 'Hinweis: am Stand hat sich nichts geändert');
  const r = await EdOrtsdaten.einsatzUebernehmen(M(fh), k, h, { jetzt: fb.jetzt });
  melde(r.inhalt.z === fb.erwartet.z && r.absender === fb.erwartet.absender && gleich(r.befund, fb.befund), 'einsatzUebernehmen: angenommen, Befund ' + JSON.stringify(r.befund));
  melde(gleich(await standVon(a, fh.client_ref), fb.stand_danach), 'einsatzUebernehmen: Stand danach wie Referenz ' + JSON.stringify(await standVon(a, fh.client_ref)));
  const c = await neu(tr);
  melde(gleich(await standVon(c, fh.client_ref), fb.stand_danach), 'einsatzUebernehmen: der Stand liegt in der Abschrift (zweiter Browser)');
  const r2 = await EdOrtsdaten.einsatzOeffnen(M(fh), k);
  melde(gleich(r2.befund, ['stand-uebernommen']) && r2.absender === fb.erwartet.absender, 'danach dieselbe Hülle: angenommen, Befund stand-uebernommen');
  await ruhe();
  melde(gleich(await standVon(a, fh.client_ref), fb.stand_danach), 'danach dieselbe Hülle: Stand unverändert');

  /* 2. Konflikt: Ein zweiter Browser hat den Stand inzwischen gehoben — die Bestätigung gilt nicht mehr. */
  tr = EdStandbuch.speicherTransport();
  a = await neu(tr); await geraete(a);
  await a.fortschreiben(fh.client_ref, setze(fh.stand));
  const b = await neu(tr);
  const fa = fall('bestaetigung-gegen-einen-anderen-stand');
  await b.fortschreiben(fh.client_ref, setze(fa.stand));      // a weiß davon nichts
  buchJetzt = a;
  const versionVor = (await tr.stand()).blaetter.slice();
  try { await EdOrtsdaten.einsatzUebernehmen(M(fh), k, h, { jetzt: fb.jetzt }); melde(false, 'Konflikt: ging durch'); }
  catch (e) { melde(e.grund === 'uhrzeit-pruefen' && gleich(e.hinweis, fa.hinweis), 'Konflikt beim Übernehmen: neuer Hinweis gegen den Stand des Servers (' + (e.grund || e.message) + ')'); }
  melde(gleich((await tr.stand()).blaetter, versionVor), 'Konflikt: nichts geschrieben');
  melde(gleich(await standVon(await neu(tr), fh.client_ref), fa.stand), 'Konflikt: der Stand des anderen Browsers steht');

  /* 3. Altblatt ohne a: beim Lesen wird nur der Absender nachgetragen. */
  const fg = fall('altblatt-gleich');
  tr = EdStandbuch.speicherTransport();
  a = await neu(tr); await geraete(a);
  await a.fortschreiben(fg.client_ref, setze(fg.stand));
  buchJetzt = a;
  const r3 = await EdOrtsdaten.einsatzOeffnen(M(fg), k);
  await ruhe();
  melde(gleich(r3.befund, fg.befund) && gleich(await standVon(await neu(tr), fg.client_ref), fg.stand_danach), 'Altblatt: a nachgetragen ' + JSON.stringify(fg.stand_danach));

  /* 4. Lesen ohne Bestätigung, Konflikt hebt den Stand so, dass ein Hinweis nötig wäre: nichts geschrieben. */
  const fho = fall('altblatt-hoeher');                 // Uhr, z_uhr, gegen {o: z_handy}
  tr = EdStandbuch.speicherTransport();
  a = await neu(tr); await geraete(a);
  await a.fortschreiben(fho.client_ref, setze(fho.stand));
  const b4 = await neu(tr);
  const hoch = { o: fho.erwartet.z + 1000, a: v2.geraete.handy.sid_hex };
  await b4.fortschreiben(fho.client_ref, setze(hoch));
  buchJetzt = a;
  const r4 = await EdOrtsdaten.einsatzOeffnen(M(fho), k);
  await ruhe();
  melde(r4.inhalt.z === fho.erwartet.z && gleich(await standVon(await neu(tr), fho.client_ref), hoch), 'Lesen, Konflikt mit höherem Stand eines anderen Geräts: nicht per Math.max überschrieben, nichts geschrieben');

  /* 5. fuerAnsicht: Befund und Absender am Datensatz; Bestätigung über o.bestaetigt. */
  window.EdKontopaar = { bereit: async () => {}, privat: async () => priv, oeffentlich: () => pub };
  window.EdFormat = { uhrzeit: () => '' };
  tr = EdStandbuch.speicherTransport();
  a = await neu(tr); await geraete(a);
  await a.fortschreiben(fh.client_ref, setze(fh.stand));
  buchJetzt = a;
  const m = Object.assign(M(fh), { spur_stuecke: [], phases: [] });
  let h5 = null;
  try { await EdOrtsdaten.fuerAnsicht(m, ck); melde(false, 'fuerAnsicht: ging auf'); } catch (e) { h5 = e.hinweis; melde(e.grund === 'uhrzeit-pruefen', 'fuerAnsicht: wirft den Hinweis'); }
  await EdOrtsdaten.fuerAnsicht(m, ck, { bestaetigt: h5, jetzt: fb.jetzt });
  melde(gleich(m.ort_befund, ['stand-uebernommen']) && m.ort_absender === fb.erwartet.absender && gleich(await standVon(a, fh.client_ref), fb.stand_danach), 'fuerAnsicht mit bestaetigt: m.ort_befund, m.ort_absender, Stand geschrieben');

  /* 6. Schreiben scheitert (Transport wirft): Übernehmen gilt nicht. */
  tr = EdStandbuch.speicherTransport();
  a = await neu(tr); await geraete(a);
  await a.fortschreiben(fh.client_ref, setze(fh.stand));
  buchJetzt = a;
  const ablegen = tr.ablegen; tr.ablegen = async () => { throw new Error('netz'); };
  try { await EdOrtsdaten.einsatzUebernehmen(M(fh), k, h, { jetzt: fb.jetzt }); melde(false, 'Netzfehler: ging durch'); }
  catch (e) { melde(e.message === 'netz', 'Übernehmen ohne Netz: wirft, gilt nicht'); }
  tr.ablegen = ablegen;
  melde(gleich(await standVon(a, fh.client_ref), fh.stand), 'Übernehmen ohne Netz: Stand im Browser unverändert');
  return { zeilen, rot };
}, [v2, vi]);
await browser.close();
console.log(erg.zeilen.join('\n'));
console.log(`${erg.zeilen.length} Prüfungen, ${erg.rot} Abweichungen`);
process.exit(erg.rot ? 1 : 0);
