/* Fristprobe — misst den Unterschied, den R44 macht. Aufruf:
 *
 *     node tools/fristprobe/pruefe.mjs
 *
 * Rückgabewert: 0 = die Frist des Inhaltsschlüssels gleitet wie die der
 * Sitzung · 1 = sie tut es nicht (oder die Probe selbst ist gescheitert).
 *
 * Warum es diese Probe gibt, steht ausführlich in probe.html und in der
 * LIESMICH daneben. In einem Satz: Die im Rahmenplan zu R44 vorgeschriebene
 * Abnahme („eine Sitzung über 30 Minuten mit Bedienung bringt keinen Dialog")
 * ist VOR und NACH der Änderung grün und belegt deshalb nichts. Der
 * Unterschied ist ein anderer und lässt sich zählen.
 */
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const hier   = path.dirname(fileURLToPath(import.meta.url));
const wurzel = path.resolve(hier, '../..');
const PORT   = 8733;

// sessionStorage braucht eine echte Herkunft — file:// genügt nicht.
const srv = spawn('npx', ['--yes', 'http-server', wurzel, '-p', String(PORT), '-s'],
                  { stdio: 'ignore' });
await new Promise(r => setTimeout(r, 1500));

const browser = await chromium.launch();
const seite   = await browser.newPage();
const fehler  = [];
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));

await seite.goto(`http://localhost:${PORT}/tools/fristprobe/probe.html`);
await seite.waitForFunction(() => window.__fertig === true, null, { timeout: 30000 });
const e = await seite.evaluate(() => window.__ergebnis);

const stunden = e.schichtMin / 60;
console.log(`Fristprobe — Inhaltsschlüssel gegen Sitzungsfrist (R44)\n`);
console.log(`Durchgespielt: ${stunden} Stunden Dienst, alle ${e.bedienungMin} Minuten`);
console.log(`eine Seite aufgerufen — ${e.alt.aufrufe} Aufrufe, ohne eine einzige Pause.`);
console.log(`Frist beider Uhren: ${e.fristMin} Minuten.\n`);

console.log('                                   vorher   nachher');
console.log(`  Neu-Entpackungen des Schlüssels  ${String(e.alt.entpackt).padStart(6)}   ${String(e.neu.entpackt).padStart(7)}`);
console.log('');
console.log(`  Leerlauf über die Frist hinaus:`);
console.log(`  Neu-Entpackung danach            ${String(e.altLeerlauf.neuEntpackt).padStart(6)}   ${String(e.neuLeerlauf.neuEntpackt).padStart(7)}`);
console.log('');

const urteile = [
  ['Vorher: die Frist läuft absolut ab dem Entpacken',
   e.alt.entpackt > 1],
  [`Nachher: durchgehende Arbeit kostet genau ein Entpacken`,
   e.neu.entpackt === 1],
  ['Die Frist greift weiterhin — Leerlauf darüber entpackt neu',
   e.neuLeerlauf.neuEntpackt === 1 && e.altLeerlauf.neuEntpackt === 1],
  ['Keine Seitenfehler', fehler.length === 0],
];
let ok = true;
for (const [was, gut] of urteile) {
  console.log(`  ${gut ? '✔' : '✘'} ${was}`);
  if (!gut) { ok = false; }
}
if (e.alt.fehler || e.neu.fehler) {
  console.log('  ✘ ' + (e.alt.fehler || e.neu.fehler));
  ok = false;
}
if (fehler.length) { console.log('  Seitenfehler: ' + fehler.join(' / ')); }

console.log(`\nWAS DAS NICHT BELEGT: dass der Entsperrdialog seltener wird. Der`);
console.log(`Fristablauf kostete ein STILLES Neu-Entpacken aus \`edk\`, keinen`);
console.log(`Dialog — der fällt nur bei fehlendem \`edk\` oder nicht passender`);
console.log(`Hülle (neuer Tab, Browser-Neustart, Passwort-Reset). Das bleibt so.`);

await browser.close();
srv.kill();
process.exit(ok ? 0 : 1);
