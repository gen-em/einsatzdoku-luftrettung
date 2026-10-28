/* Belegt, was der Abmeldeweg im Browser zuruecklaesst: im sessionStorage des
   Tabs und, seit R17-25, in der Ablage des Inhaltsschluessels (IndexedDB).
   Aufruf:  node tools/proben/abmelden/pruefe.mjs

   Rueckgabewert: 0 = nach dem Abmelden liegt kein Schluesselmaterial mehr,
   und die Faecher der Fassungen bis 23.3.x raeumt schon das Laden ·
   1 = etwas bleibt liegen (oder die Probe selbst ist gescheitert).
   Bis R17-25 endete diese Probe immer mit 0, auch wenn sie „VERLETZT"
   schrieb — eine grüne Zahl ohne Gegenstand (Grundsatz 7).

   Anlass: Nr. 22 — der neue Datenschluessel (edk_neu) ueberlebte das Abmelden
   Seit R17-25 (Nr. 370) misst sie auch die Ablage in IndexedDB, deren Loeschen asynchron ist.
*/
import * as PWM from '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = PWM.default ?? PWM;
/* Die Engine wählt der Läufer (MOTOR=… oder --motor), R87. */
const MOTOR = motorWahl(process.argv);
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import { motorWahl, starten } from '../../motor.mjs';

const hier  = path.dirname(fileURLToPath(import.meta.url));
const wurzel = path.resolve(hier, '../../..');           // Projektwurzel
const PORT  = 8731;

// sessionStorage und IndexedDB brauchen eine echte Herkunft — file:// genuegt
// nicht. Der Server aus Python bindet nur 127.0.0.1 (F-R17-20).
const srv = spawn('python3', ['-m', 'http.server', String(PORT), '--bind', '127.0.0.1',
                              '--directory', wurzel], { stdio: 'ignore' });
await new Promise(r => setTimeout(r, 1500));

const browser = await starten(PW, MOTOR);
const seite   = await browser.newPage();
const fehler  = [];
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));

await seite.goto(`http://localhost:${PORT}/tools/proben/abmelden/probe.html`);
await seite.waitForFunction(() => window.__fertig === true, null, { timeout: 30000 });
const e = await seite.evaluate(() => window.__ergebnis);
await browser.close();
srv.kill();

if (e.fehler) {
  console.log('Abmeldeprobe gescheitert: ' + e.fehler);
  process.exit(1);
}

console.log('== Fächer bis 23.3.x (Hex im Tab) ==');
console.log('   vor dem Laden belegt: ' + e.altGefuellt.join(', '));
console.log('   nach dem Laden übrig: ' + (e.altNachLaden.join(', ') || 'nichts'));
console.log('\n== Abmelden ==');
Object.keys(e.faecher).forEach(k => console.log('   ' + k.padEnd(8) + ' — ' + e.faecher[k].was));
console.log('   Ablage (IndexedDB) vorher belegt: ' + (e.ablageVorher ? 'ja' : 'NEIN'));
console.log('   der abgelegte Schlüssel extrahierbar: ' + (e.griffExtrahierbar ? 'JA' : 'nein'));
console.log('   im Tab übrig: ' + (e.imTabNachher.join(', ') || 'nichts'));
console.log('   Ablage nachher: ' + (e.ablageNachher ? 'BELEGT' : 'leer'));

const urteile = [
  ['Laden räumt die Hex-Fächer bis 23.3.x', e.altGefuellt.length === 4 && e.altNachLaden.length === 0],
  ['Die Ablage war vor dem Abmelden belegt (sonst misst Punkt 4 nichts)', e.ablageVorher],
  ['Der abgelegte Schlüssel ist nicht extrahierbar', e.griffExtrahierbar === false],
  ['Nach dem Abmelden liegt im Tab nichts mehr', e.imTabNachher.length === 0],
  ['Nach dem Abmelden ist die Ablage leer', !e.ablageNachher],
  ['Keine Seitenfehler', fehler.length === 0],
];
let ok = true;
console.log('\n== Urteil ==');
for (const [was, gut] of urteile) {
  console.log(`  ${gut ? '✔' : '✘'} ${was}`);
  if (!gut) { ok = false; }
}
if (fehler.length) { console.log('  Seitenfehler: ' + fehler.join(' / ')); }
process.exit(ok ? 0 : 1);
