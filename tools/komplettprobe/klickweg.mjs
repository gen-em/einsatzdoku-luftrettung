/* KLICKWEG DER ADMINSEITE „Komplett-Backup" (S2/AP8).
 * ===========================================================================
 *
 * WOFUER. `probe.php` prueft die Bibliothek darunter; hier wird geklickt. Die
 * Fragen, die nur ein Browser beantwortet: Kommt der Bestaetigungsdialog? Was
 * meldet die Seite nach dem Lauf? Liefert „Herunterladen" wirklich eine Datei
 * — und ist sie gzip? Liefert „Versiegelt herunterladen" wirklich EDKOMP1?
 * Wird eine zu kurze Passphrase abgewiesen, BEVOR etwas hinausgeht?
 *
 * ER SICHERT WIRKLICH. Der Lauf drueckt „Jetzt sichern" und wartet ihn ab; auf
 * dem Messbestand kostet das rund zehn Sekunden und legt einen Stand an. Auf
 * einer Installation, an der jemand haengt, ist das nichts fuer nebenbei.
 *
 * ZWEI STOLPERSTELLEN, beide hier vermerkt, weil sie Zeit gekostet haben:
 *
 *   1. UEBER TLS, nicht ueber Port 8080. Die Sitzung setzt `secure`; ueber
 *      http bleibt man auf der Anmeldeseite stehen, ohne Fehlermeldung.
 *   2. `waitForNavigation`, nicht `waitForLoadState`. Die Anmeldung leitet den
 *      Schluessel im Browser ab (PBKDF2, 320 000 Runden) und geht erst danach
 *      weiter; `waitForLoadState` faellt sofort durch, weil es auf die
 *      AKTUELLE Seite wartet. Dasselbe Muster wie in `aufnehmen.mjs`.
 *
 * AUFRUF (die lokale Installation muss laufen):
 *
 *   node tools/komplettprobe/klickweg.mjs
 *   ED_BASIS=https://127.0.0.1:8443 ED_MAIL=… ED_PW=… node …/klickweg.mjs
 *
 * Erwartet: 17 Pruefungen, 0 Befunde. Rueckgabewert 0, sonst 1.
 */

const MODUL = process.env.PW_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const BASIS = process.env.ED_BASIS || 'https://127.0.0.1:8443';
const MAIL  = process.env.ED_MAIL || 'admin@gen-em.org';
const PW    = process.env.ED_PW   || 'adminlokal2026';

const befunde = [];
const pruef = (was, ok, dazu = '') => {
  if (!ok) befunde.push(was);
  console.log(`  [${ok ? 'ok ' : 'OFFEN'}] ${was.padEnd(58)} ${dazu}`);
};

const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: true, acceptDownloads: true, viewport: { width: 1280, height: 900 } });
const seite = await kontext.newPage();
const fehler = [];
seite.on('console', (m) => { if (m.type() === 'error') fehler.push(m.text()); });
seite.on('pageerror', (e) => fehler.push(String(e)));

try {
  await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', MAIL);
  await seite.fill('input[name="password"]', PW);
  /* Die Anmeldung leitet den Schluessel im Browser ab (PBKDF2, 320 000 Runden).
   *  faellt dabei sofort durch — es wartet auf die AKTUELLE
   * Seite. Es braucht  mit Frist, wie im Bilderlauf. */
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
    seite.click('button[type="submit"]'),
  ]);
  pruef('Anmeldung', !seite.url().includes('login.php'), seite.url());

  await seite.goto(`${BASIS}/admin_komplettsicherung.php`, { waitUntil: 'domcontentloaded' });
  pruef('Die Seite ist erreichbar', await seite.locator('h1', { hasText: 'Komplett-Backup' }).count() > 0);
  pruef('Der Menüpunkt ist markiert',
        await seite.locator('.leiste-liste a.aktiv', { hasText: 'Komplett-Backup' }).count() > 0);

  const staendeVor = await seite.locator('.zeile-aktionen').count();

  // 1. „Jetzt sichern" mit Bestaetigungsdialog
  await seite.click('button[form="f-sichern"]');
  const dialog = seite.locator('dialog[open]');
  const hatDialog = await dialog.count() > 0;
  pruef('„Jetzt sichern" fragt vorher nach', hatDialog,
        hatDialog ? (await dialog.innerText()).slice(0, 60).replace(/\s+/g, ' ') : 'kein Dialog');
  if (hatDialog) {
    await Promise.all([seite.waitForLoadState('domcontentloaded'),
                       dialog.locator('button', { hasText: 'Sichern' }).first().click()]);
  }
  const meldung = (await seite.locator('.meldung').first().innerText().catch(() => '')).replace(/\s+/g, ' ');
  pruef('Der Lauf meldet ein Ergebnis', meldung.length > 0, meldung.slice(0, 110));
  pruef('...und es ist kein Fehler', !(await seite.locator('.meldung-fehler').count()));

  const staendeNach = await seite.locator('.zeile-aktionen').count();
  pruef('Es liegt ein Stand mehr da (oder die Aufbewahrung greift)',
        staendeNach >= 1, `${staendeVor} -> ${staendeNach}`);

  // 2. Download unverschlüsselt
  const [dl] = await Promise.all([
    seite.waitForEvent('download', { timeout: 120000 }),
    seite.locator('button[form^="f-klar-"]').first().click(),
  ]);
  const pfad = await dl.path();
  const fs = await import('node:fs');
  const gr = fs.statSync(pfad).size;
  const kopf = fs.readFileSync(pfad).subarray(0, 2);
  pruef('„Herunterladen" liefert eine Datei', gr > 1000,
        `${dl.suggestedFilename()} — ${(gr / 1048576).toFixed(1)} MB`);
  pruef('...und sie ist gzip (1f 8b)', kopf[0] === 0x1f && kopf[1] === 0x8b);
  pruef('...und heisst .sql.gz', dl.suggestedFilename().endsWith('.sql.gz'),
        dl.suggestedFilename());

  // 3. Download mit Passphrase
  await seite.fill('input[name="passphrase"]', 'klickprobe-2026');
  const [dl2] = await Promise.all([
    seite.waitForEvent('download', { timeout: 120000 }),
    seite.locator('button', { hasText: 'Versiegelt herunterladen' }).first().click(),
  ]);
  const pfad2 = await dl2.path();
  const roh = fs.readFileSync(pfad2);
  pruef('„Versiegelt herunterladen" liefert eine Datei', roh.length > 1000,
        `${dl2.suggestedFilename()} — ${(roh.length / 1048576).toFixed(1)} MB`);
  pruef('...und sie beginnt mit EDKOMP1', roh.subarray(0, 8).toString() === 'EDKOMP1\n');
  pruef('...und der Kopf nennt PBKDF2',
        roh.subarray(0, 400).toString().includes('"pbkdf2"'));

  // 4. Zu kurze Passphrase wird abgewiesen
  await seite.goto(`${BASIS}/admin_komplettsicherung.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="passphrase"]', 'kurz');
  await Promise.all([seite.waitForLoadState('domcontentloaded'),
                     seite.locator('button', { hasText: 'Versiegelt herunterladen' }).first().click()]);
  const fehlermeldung = (await seite.locator('.meldung-fehler').first().innerText().catch(() => ''))
                        .replace(/\s+/g, ' ');
  pruef('Eine zu kurze Passphrase wird abgewiesen', fehlermeldung.includes('8 Zeichen'),
        fehlermeldung.slice(0, 90));

  // 5. Zeitplan speichern und wieder zurückstellen
  await seite.selectOption('select[name="plan"]', 'woechentlich');
  await Promise.all([seite.waitForLoadState('domcontentloaded'),
                     seite.locator('button', { hasText: 'Speichern' }).first().click()]);
  pruef('Der Zeitplan lässt sich speichern',
        await seite.locator('select[name="plan"]').inputValue() === 'woechentlich');
  await seite.selectOption('select[name="plan"]', 'aus');
  await Promise.all([seite.waitForLoadState('domcontentloaded'),
                     seite.locator('button', { hasText: 'Speichern' }).first().click()]);
  pruef('...und wieder zurückstellen',
        await seite.locator('select[name="plan"]').inputValue() === 'aus');

  pruef('Keine Konsolenfehler auf dem ganzen Weg', fehler.length === 0,
        fehler.slice(0, 2).join(' | '));
} finally {
  await browser.close();
}
console.log(`\n-> ${befunde.length} Befunde`);
process.exit(befunde.length === 0 ? 0 : 1);
