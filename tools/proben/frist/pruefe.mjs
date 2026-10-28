/* Fristprobe — Frist und Bindung des Inhaltsschlüssels in der Ablage.
 * Aufruf:
 *
 *     node tools/proben/frist/pruefe.mjs
 *
 * Rückgabewert: 0 = die Frist gleitet wie die der Sitzung, Leerlauf und
 * Laden räumen, eine fremde Hülle sperrt keinen anderen Tab, der Schlüssel
 * ist nicht extrahierbar · 1 = eins davon nicht (oder die Probe selbst ist
 * gescheitert).
 *
 * Was genau gemessen wird, steht in probe.html. Seit R17-25 gegen die
 * echten Dateien und die echte IndexedDB; bis dahin mit einem nachgebauten
 * EdCrypto, und gezählt wurden stille Neu-Entpackungen aus dem
 * Datenschlüssel, den es nach dem Entsperren nicht mehr gibt.
 *
 * Anlass: Nr. 310 — die Frist lief ab dem Entpacken: 17 stille Neu-Entpackungen je Schicht statt einer
 * Anlass: Nr. 370 — der Inhaltsschlüssel liegt seit R17-25 als CryptoKey in IndexedDB, für alle Tabs
 */
import * as PWM from '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = PWM.default ?? PWM;
/* Die Engine wählt der Läufer (MOTOR=… oder --motor), R87. */
const MOTOR = motorWahl(process.argv);
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { motorWahl, starten } from '../../motor.mjs';

const hier   = path.dirname(fileURLToPath(import.meta.url));
const wurzel = path.resolve(hier, '../../..');
const PORT   = 8733;

// IndexedDB braucht eine echte Herkunft — file:// genügt nicht.
// Seit R17-19 der Server aus Python statt `npx --yes http-server`: Der zog
// bei jedem Lauf ein npm-Paket ohne Fassung und ohne Prüfsumme nach und
// band auf allen Schnittstellen (F-R17-20). Dieser bindet nur 127.0.0.1.
const srv = spawn('python3', ['-m', 'http.server', String(PORT), '--bind', '127.0.0.1',
                              '--directory', wurzel], { stdio: 'ignore' });
await new Promise(r => setTimeout(r, 1500));

const browser = await starten(PW, MOTOR);
const seite   = await browser.newPage();
const fehler  = [];
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));

await seite.goto(`http://localhost:${PORT}/tools/proben/frist/probe.html`);
await seite.waitForFunction(() => window.__fertig === true, null, { timeout: 30000 });
const e = await seite.evaluate(() => window.__ergebnis);

await browser.close();
srv.kill();
if (e.fehler) { console.log('Fristprobe gescheitert: ' + e.fehler); process.exit(1); }

console.log(`Fristprobe — Inhaltsschlüssel in der Ablage (R44, R17-25)\n`);
console.log(`Schicht: ${e.schichtMin / 60} Stunden, alle ${e.bedienungMin} Minuten eine Seite — `
  + `${e.schicht.aufrufe} Aufrufe, Schlüssel verloren: ${e.schicht.verloren}`);
console.log(`Leerlauf über ${e.fristMin} Minuten: Schlüssel ${e.leerlauf.schluessel ? 'NOCH DA' : 'fort'}, `
  + `Eintrag ${e.leerlauf.eintragDanach ? 'LIEGT NOCH' : 'gelöscht'}`);
console.log(`Fremde Hülle: Schlüssel ${e.fremd.schluessel ? 'GELIEFERT' : 'nicht geliefert'}, `
  + `Eintrag ${e.fremd.eintragDanach ? 'bleibt' : 'GELÖSCHT'}, eigene Hülle danach `
  + `${e.fremd.eigeneDanach ? 'bekommt ihn' : 'BEKOMMT IHN NICHT'}`);
console.log(`Seite lädt nach Ablauf: Eintrag ${e.laden.geraeumt ? 'geräumt' : 'NICHT GERÄUMT'}`);
console.log(`Griff: ${e.griff.typ}, extrahierbar ${e.griff.extrahierbar}, Export ${e.griff.ausgeleitet}, `
  + `öffnet Hex-Chiffre: ${e.griff.geoeffnet === 'Probe' ? 'ja' : e.griff.geoeffnet}\n`);

const urteile = [
  ['Durchgehende Arbeit verliert den Schlüssel nie (R44)', e.schicht.verloren === 0 && e.schicht.aufrufe === e.schichtMin / e.bedienungMin + 1],
  ['Leerlauf über die Frist liefert null und löscht', !e.leerlauf.schluessel && !e.leerlauf.eintragDanach],
  ['Fremde Hülle liefert null und lässt den Eintrag stehen', !e.fremd.schluessel && e.fremd.eintragDanach && e.fremd.eigeneDanach],
  ['Eine Seite, die nach Ablauf lädt, räumt', e.laden.geraeumt],
  ['Der Schlüssel ist ein nicht extrahierbarer CryptoKey', e.griff.typ === '[object CryptoKey]' && e.griff.extrahierbar === false && e.griff.ausgeleitet === 'verweigert'],
  ['Er öffnet, was mit dem Hex verschlossen ist', e.griff.geoeffnet === 'Probe'],
  ['Keine Seitenfehler', fehler.length === 0],
];
let ok = true;
for (const [was, gut] of urteile) {
  console.log(`  ${gut ? '✔' : '✘'} ${was}`);
  if (!gut) { ok = false; }
}
if (fehler.length) { console.log('  Seitenfehler: ' + fehler.join(' / ')); }
process.exit(ok ? 0 : 1);
