/* Belegt, was der Abmeldeweg im sessionStorage zuruecklaesst — vor und nach
   der Aenderung aus S22-3. Aufruf:  node pruefe.mjs  (aus diesem Verzeichnis) */
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const hier  = path.dirname(fileURLToPath(import.meta.url));
const wurzel = path.resolve(hier, '../..');           // Projektwurzel
const PORT  = 8731;

// sessionStorage braucht eine echte Herkunft — file:// genuegt nicht.
const srv = spawn('npx', ['--yes', 'http-server', wurzel, '-p', String(PORT), '-s'],
                  { stdio: 'ignore' });
await new Promise(r => setTimeout(r, 1500));

const browser = await chromium.launch();
const seite   = await browser.newPage();
const fehler  = [];
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));

await seite.goto(`http://localhost:${PORT}/tools/abmelde-probe/probe.html`);
await seite.waitForFunction(() => window.__fertig === true);
const e = await seite.evaluate(() => window.__ergebnis);

console.log('== Belegte Fächer vor dem Abmelden ==');
e.gefuellt.forEach(k => console.log('   ' + k.padEnd(8) + ' — ' + e.faecher[k].was));

console.log('\n== Nach dem Abmeldeweg — Stand Web 7.2.0 ==');
console.log('   übrig: ' + (e.nachAlt.join(', ') || 'nichts'));
console.log('   davon Schlüsselmaterial: ' + (e.materialAlt.join(', ') || 'keines'));

console.log('\n== Nach dem Abmeldeweg — Stand Web 7.2.1 ==');
console.log('   übrig: ' + (e.nachNeu.join(', ') || 'nichts'));
console.log('   davon Schlüsselmaterial: ' + (e.materialNeu.join(', ') || 'keines'));

console.log('\n== Urteil ==');
console.log('  V-10 (kein Schlüsselmaterial nach dem Abmelden): '
  + (e.materialNeu.length === 0 ? 'ERFUELLT' : 'NICHT ERFUELLT — ' + e.materialNeu.join(', ')));
console.log('  vorher war V-10: '
  + (e.materialAlt.length === 0 ? 'erfüllt' : 'VERLETZT durch ' + e.materialAlt.join(', ')));
console.log('  pckb/pckt bleiben liegen (bewusst, kein Schlüsselmaterial): '
  + (e.nachNeu.includes('pckb') && e.nachNeu.includes('pckt') ? 'ja' : 'nein'));
console.log('  Seitenfehler: ' + (fehler.length ? fehler.join(' / ') : 'keine'));

await browser.close();
srv.kill();
process.exit(0);
