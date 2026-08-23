/* Passwort eines Kontos ueber pw_handling.php setzen — im Browser.
 *
 * WARUM IM BROWSER UND NICHT IM SKRIPT. Hier entstehen Salz,
 * Inhaltsschluessel, beide Schluesselhuellen und der
 * Wiederherstellungsschluessel — und zwar ausschliesslich mit der WebCrypto
 * des Browsers (assets/crypto.js). Das ist die Zusage des Projekts: Der
 * Server sieht das Passwort nie. Ein Skript, das diesen Schritt nachbaut,
 * pruefte den Weg nicht mehr, den eine NutzerIn tatsaechlich geht — und
 * genau der soll hier belegt werden (E-P1-10).
 *
 * Aufruf:
 *   node passwort_setzen.mjs <url-mit-token> <passwort> [ausgabe.json]
 *
 * Gibt den Wiederherstellungsschluessel aus. Er wird gebraucht: Ohne ihn
 * kaeme man nach einem vergessenen Passwort nicht mehr an die Daten, und
 * die Admin-Sicherung fuehrt ihn als `pat_wrap_rc`.
 */
import { writeFileSync } from 'node:fs';

/* Playwright ueber einen PFAD laden, nicht ueber den Paketnamen.
 *
 * Ein ESM-Import loest den Namen relativ zur SKRIPTDATEI auf, nicht zum
 * Arbeitsverzeichnis -- und neben diesem Skript liegt kein node_modules,
 * denn es gehoert ins Repositorium und nicht in eine Installation.
 * PLAYWRIGHT_MODUL zeigt auf die vorhandene Installation; der Vorgabewert
 * passt zu einer globalen npm-Installation. */
const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const [, , url, passwort, ausgabe] = process.argv;
if (!url || !passwort) {
  console.error('Aufruf: node passwort_setzen.mjs <url-mit-token> <passwort> [ausgabe.json]');
  process.exit(2);
}

const browser = await chromium.launch();
/* Selbstsigniertes Zertifikat der lokalen Installation zulassen — und NUR
   dort. Gegen eine echte Adresse bleibt die Pruefung an; ein Skript, das sie
   pauschal abschaltet, prueft irgendwann die falsche Gegenstelle. */
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(url);
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal });
const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error') konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

await seite.goto(url, { waitUntil: 'networkidle' });

const titel = await seite.title();
if (await seite.locator('#pw1').count() === 0) {
  console.error('Kein Passwortformular auf der Seite (' + titel + ').');
  console.error(await seite.locator('body').innerText());
  await browser.close();
  process.exit(1);
}

await seite.fill('#pw1', passwort);
await seite.fill('#pw2', passwort);
await seite.click('#gobtn');

// Der Wiederherstellungsschluessel erscheint erst, wenn die Schluessel
// erzeugt sind. Er MUSS bestaetigt werden, sonst speichert die Seite nicht.
await seite.waitForSelector('#rcbox:not([hidden])', { timeout: 30000 });
const rc = (await seite.locator('#rccode').textContent()).trim();
await seite.check('#rcok');
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }),
  seite.click('#gobtn'),
]);

const ziel = seite.url();
const text = await seite.locator('body').innerText();
const erfolg = /erfolgreich|festgelegt|gespeichert|Anmelden/i.test(text) || /login\.php/.test(ziel);

console.log('Wiederherstellungsschlüssel:', rc);
console.log('Zielseite:', ziel);
console.log('Konsolenfehler:', konsole.length ? konsole : 'keine');
if (ausgabe) {
  writeFileSync(ausgabe, JSON.stringify({ recovery_code: rc, ziel, konsole }, null, 2) + '\n');
}
await browser.close();
process.exit(erfolg && konsole.length === 0 ? 0 : 1);
