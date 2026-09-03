/* Freigabeprobe — der Weg MIT Wiederherstellungsschlüssel (S2/AP6).
 *
 * DIE FRAGE. Eine Administration darf ein Backup mit geschützten Angaben
 * nicht unmittelbar in ein neu aufgesetztes Konto spielen (E20): Die Angaben
 * sind mit einem Inhaltsschlüssel verschlüsselt, den nur der
 * Wiederherstellungsschlüssel öffnet — und der liegt ausschliesslich bei der
 * NutzerIn. Sie gibt das Backup deshalb frei, und das Umschlüsseln
 * geschieht im Browser der NutzerIn.
 *
 * Dieser Weg war bis Web 12.0.0 NIE geprüft, und er hat auch nie
 * funktioniert (F-S2-F): Der Kasten, in dem der Schlüssel einzugeben ist,
 * wurde von einem stillen TypeError verschluckt. Nach der Behebung wurde der
 * Fensterweg mit einer Quelle OHNE geschützte Angaben belegt — der Zweig mit
 * Schlüssel blieb offen, weil die Prüfkonten ihren Wiederherstellungsschlüssel
 * nicht aufbewahrt haben.
 *
 * Diese Probe schliesst die Lücke. Sie belegt drei Dinge:
 *
 *   1. Die Rückfrage nach dem Schlüssel erscheint überhaupt (F-S2-F).
 *   2. Ein FALSCHER Schlüssel wird abgewiesen, und es wird nichts geschrieben.
 *   3. Mit dem richtigen kommen die Angaben an — mit einem ANDEREN
 *      Chiffretext als in der Quelle (umgeschlüsselt) und demselben Klartext.
 *
 * ALLE KRYPTO KOMMT AUS DER ANWENDUNG. Hülle, Prüfsumme und Chiffretext
 * entstehen im Browser über `assets/crypto.js`; PHP legt sie nur ab. Ein
 * zweiter Rechenweg wäre eine zweite Umsetzung derselben Krypto, und die
 * Probe prüfte dann sich selbst.
 *
 * Aufruf:
 *   node tools/freigabeprobe/probe.mjs [basis] [ziel-email] [ziel-passwort]
 */
import { execFileSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const HIER   = dirname(fileURLToPath(import.meta.url));
const BASIS  = process.argv[2] || 'https://127.0.0.1:8443';
const ZIEL   = process.argv[3] || 'umlauf-edbak@gen-em.org';
const ZIELPW = process.argv[4] || 'umlaufpruefung2026';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import('file://' + MODUL);

let gesamt = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  gesamt++; if (!ok) offen++;
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(58)} ${wert}`);
};
const php = (schritt, daten) => JSON.parse(execFileSync('php',
  [join(HIER, 'vorbereiten.php'), schritt, JSON.stringify(daten)],
  { encoding: 'utf8', maxBuffer: 1 << 26 }));

console.log(`Freigabeprobe gegen ${BASIS}`);
console.log(`  Zielkonto: ${ZIEL}`);

const lokal = /^https?:\/\/(127\.0\.0\.1|localhost)(:|\/|$)/.test(BASIS);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal });
const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error') konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

try {
  /* ---- 1. Schlüsselmaterial in der Anwendung erzeugen ------------------ */
  await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  pruefe(await seite.evaluate(() => typeof EdCrypto === 'object'),
         'crypto.js ist auf der Anmeldeseite geladen');

  const KLARTEXT = { diag: 'Freigabeprobe-Diagnose', alter: 42 };
  const mat = await seite.evaluate(async (klar) => {
    const code = EdCrypto.newRecoveryCode();
    const ck   = EdCrypto.randomHex(32);
    const rc   = await EdCrypto.recoveryKeyHex(code);
    return {
      code,
      wrap:  await EdCrypto.encrypt(rc, ck),
      check: await EdCrypto.contentKeyCheck(ck),
      blob:  await EdCrypto.encrypt(ck, JSON.stringify(klar)),
    };
  }, KLARTEXT);
  pruefe(typeof mat.code === 'string' && mat.code.length > 0
         && mat.wrap && mat.check && mat.blob,
         'Hülle, Prüfsumme und Chiffretext kommen aus assets/crypto.js',
         'Code ' + mat.code.slice(0, 4) + '…');

  /* ---- 2. Quelle herrichten, sichern, freigeben ------------------------ */
  const q = php('quelle', { ...mat, ziel: ZIEL });
  pruefe(q.fassung === 2, 'Das Backup ist ein Fassung-2-Paket',
         `Fassung ${q.fassung}, ${q.eintragsteile} Eintrags-, ${q.spurteile} Spurteile`);
  pruefe(q.geschuetzte === 1, 'Das Manifest zählt den Einsatz mit geschützten Angaben',
         'geschuetzte=' + q.geschuetzte);

  /* ---- 3. Als Zielkonto: der Kasten muss ERSCHEINEN -------------------- */
  await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', ZIEL);
  await seite.fill('input[name="password"]', ZIELPW);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('button[type="submit"]'),
  ]);
  if (seite.url().includes('login.php')) {
    throw new Error(`Anmeldung als ${ZIEL} gescheitert. Das Konto muss bestehen `
      + `und dieses Passwort haben (drittes Argument).`);
  }
  await seite.goto(`${BASIS}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1500);
  pruefe(await seite.locator('#freigabebtn').isVisible(),
         'Der Freigabekasten erscheint (F-S2-F: er tat es nie)');
  pruefe(await seite.locator('#freigabecode').isVisible(),
         'Und er fragt nach dem Wiederherstellungsschlüssel',
         'das Paket trägt geschützte Angaben');

  /* ---- 4. Ein FALSCHER Schlüssel schreibt nichts ----------------------- */
  const falsch = await seite.evaluate(() => EdCrypto.newRecoveryCode());
  await seite.fill('#freigabecode', falsch);
  await seite.click('#freigabebtn');
  await seite.waitForTimeout(2500);
  const textFalsch = (await seite.locator('#freigabestate').textContent() || '').trim();
  pruefe(/passt nicht/i.test(textFalsch),
         'Ein falscher Schlüssel wird abgewiesen', textFalsch.slice(0, 52));
  const nachFalsch = php('pruefen', { ziel: ZIEL });
  pruefe(nachFalsch.angekommen === false,
         '...und es wurde NICHTS geschrieben', 'kein Einsatz im Zielkonto');

  /* ---- 5. Der richtige Schlüssel ---------------------------------------- */
  await seite.fill('#freigabecode', mat.code);
  await seite.click('#freigabebtn');
  let ton = '';
  for (let i = 0; i < 90; i++) {
    await seite.waitForTimeout(1000);
    if (await seite.locator('#freigabestate .meldung').count() > 0) {
      ton = await seite.locator('#freigabestate .meldung').first().getAttribute('class') || '';
      break;
    }
  }
  const text = (await seite.locator('#freigabestate').textContent() || '').trim();
  pruefe(/meldung-ok/.test(ton), 'Mit dem richtigen Schlüssel läuft es durch',
         text.slice(0, 60));

  const nach = php('pruefen', { ziel: ZIEL });
  pruefe(nach.angekommen === true, 'Der Einsatz ist im Zielkonto angekommen');
  pruefe(nach.punkte === 2, 'Und seine Spur mit ihm', `${nach.punkte} von 2 Punkten`);
  pruefe(typeof nach.pat_blob === 'string' && nach.pat_blob !== ''
         && nach.pat_blob !== q.quelle_blob,
         'Der Chiffretext ist ein ANDERER als in der Quelle',
         'umgeschlüsselt, nicht durchgereicht');

  /* ---- 6. Und der Klartext ist derselbe --------------------------------- */
  const klarZurueck = await seite.evaluate(async (blob) => {
    const ck = await EdCrypto.getContentKey();
    if (!ck) { return { fehler: 'Inhaltsschlüssel in dieser Sitzung nicht verfügbar' }; }
    try { return { klar: JSON.parse(await EdCrypto.decrypt(ck, blob)) }; }
    catch (e) { return { fehler: String(e.message || e) }; }
  }, nach.pat_blob);
  pruefe(klarZurueck.klar
         && klarZurueck.klar.diag === KLARTEXT.diag
         && klarZurueck.klar.alter === KLARTEXT.alter,
         'Er öffnet sich mit dem Schlüssel DIESES Kontos, mit demselben Klartext',
         klarZurueck.klar ? JSON.stringify(klarZurueck.klar) : String(klarZurueck.fehler));

  pruefe(konsole.length === 0, 'Keine Konsolenfehler', konsole.slice(0, 2).join(' · '));
} finally {
  await browser.close();
  try { execFileSync('php', [join(HIER, 'vorbereiten.php'), 'aufraeumen', '{}']); } catch (e) {}
}

console.log(`\n  -> ${gesamt} Erwartungen, ${offen} nicht erfuellt`);
process.exit(offen === 0 ? 0 : 1);
