/* Fuehrt probe.html in Chromium aus und protokolliert das Ergebnis.
   Aufruf:  node pruefe.mjs      (aus diesem Verzeichnis)  */
import { chromium } from '/opt/node22/lib/node_modules/playwright/index.mjs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const hier = path.dirname(fileURLToPath(import.meta.url));
const url  = 'file://' + path.join(hier, 'probe.html');

/* `--allow-file-access-from-files`: Die Probe laeuft aus einer file:-Adresse,
   und seit P3/O11 setzt die Tabelle fuer einen unlesbaren Datensatz ein
   <use href="assets/images/symbole/warnung.svg#i"> ein. Chromium behandelt
   jede file:-Adresse als eigene Herkunft und verweigert das Nachladen — die
   Zelle bleibt leer, und der Vergleich „legitime Werte gleich" scheitert an
   einem Sicherheitsmerkmal des Browsers statt an der Maskierung. */
const browser = await chromium.launch({ args: ['--allow-file-access-from-files'] });
const seite   = await browser.newPage();
const fehler  = [];
/* Das fehlschlagende Bild IST der Ausloeser der Nutzlast — sein
   ERR_FILE_NOT_FOUND gehoert zum Versuchsaufbau und ist kein Befund. */
seite.on('console', m => {
  if (m.type() === 'error' && !/ERR_FILE_NOT_FOUND/.test(m.text())) fehler.push(m.text());
});
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));

await seite.goto(url);
await seite.waitForFunction(() => window.__fertig === true);
// Bildfehler brauchen einen Tick, bis onerror gelaufen ist.
await seite.waitForTimeout(300);

const e = await seite.evaluate(() => ({
  treffer: window.__treffer,
  html:    window.__html,
  textVorher:  [...document.querySelectorAll('#vorher  tbody tr')].map(t => t.innerText.replace(/\t/g, ' | ')),
  textNachher: [...document.querySelectorAll('#nachher tbody tr')].map(t => t.innerText.replace(/\t/g, ' | ')),
  imgVorher:   document.querySelectorAll('#vorher  img').length,
  imgNachher:  document.querySelectorAll('#nachher img').length
}));

const NAMEN = ['Angriffswert im Alter', 'Normalfall 47', 'Alter leer', 'Alter 0',
               'nicht lesbar', 'Angriffswert in Ort und Diagnose'];

console.log('== Ausgeloestes Markup (onerror-Treffer) ==');
console.log('   vorher (Web 7.2.0): ' + e.treffer.vorher);
console.log('   nachher (Web 7.2.1): ' + e.treffer.nachher);
console.log('== Als Markup eingehaengte <img>-Elemente ==');
console.log('   vorher: ' + e.imgVorher + '   nachher: ' + e.imgNachher);

console.log('\n== Zellen-HTML je Fall ==');
NAMEN.forEach((n, i) => {
  const v = e.html.vorher[i], w = e.html.nachher[i];
  console.log('\n[' + n + ']');
  console.log('  vorher : ' + v);
  console.log('  nachher: ' + w);
  console.log('  zeichengleich: ' + (v === w));
});

console.log('\n== Sichtbarer Text je Zeile ==');
NAMEN.forEach((n, i) => {
  console.log('[' + n + ']');
  console.log('  vorher : ' + e.textVorher[i]);
  console.log('  nachher: ' + e.textNachher[i]);
});

/* --- Urteil --- */

/* WARUM „nicht lesbar" (Index 4) NICHT MEHR MITVERGLICHEN WIRD (S2/AP10).
 *
 * AK-S22-3 fragt: Werden LEGITIME Werte unveraendert dargestellt? Bei einem
 * Datensatz, der sich nicht entschluesseln laesst, gibt es keinen legitimen
 * Wert — die Zelle zeigt eine Kennzeichnung. Deren FORM hat sich in P3/O11
 * absichtlich geaendert: Aus dem Unicode-Zeichen ⚠ ist ein Symbol aus dem
 * Vorrat geworden (E-P3-18 schliesst Schriftzeichen als Symbol aus).
 *
 * Der Vergleich hat diese gewollte Aenderung als Verletzung gemeldet — das
 * Urteil stand seit Web 9.12.0 auf NICHT ERFUELLT, und niemand hat es
 * gesehen, weil die Probe davor schon an `edSymbol is not defined` starb.
 *
 * Der Fall faellt deshalb nicht weg, sondern wird ANDERS geprueft: Beide
 * Seiten muessen ihn als `patfehler` kennzeichnen. Damit bleibt belegt, dass
 * ein unlesbarer Datensatz nicht als leer durchgeht — die Sorge, um die es in
 * patient.js geht.
 */
const normalfaelle = [1, 2, 3];      // 47, leer, 0
const gleich = normalfaelle.every(i => e.html.vorher[i] === e.html.nachher[i]);
const unlesbarGekennzeichnet =
  /patfehler/.test(e.html.vorher[4] || '') && /patfehler/.test(e.html.nachher[4] || '');
console.log('\n== Urteil ==');
console.log('  AK-S22-2 (Angriff wird Text):      ' +
  (e.treffer.vorher > 0 && e.treffer.nachher === 0 ? 'ERFUELLT' : 'NICHT ERFUELLT'));
console.log('  AK-S22-3 (legitime Werte gleich):  ' + (gleich ? 'ERFUELLT' : 'NICHT ERFUELLT'));
console.log('  Unlesbares bleibt gekennzeichnet:  '
  + (unlesbarGekennzeichnet ? 'ERFUELLT' : 'NICHT ERFUELLT')
  + '  (Form absichtlich anders seit P3/O11: Symbol statt ⚠)');
console.log('  Konsolenfehler: ' + (fehler.length ? fehler.join(' / ') : 'keine'));

await browser.close();
