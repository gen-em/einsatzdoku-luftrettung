/* P-15 — Mengenbremse des Demo-Kontos (E-P1-20).
 *
 * Sie zählt GELUNGENE Anmeldungen, nicht Fehlversuche. Geprüft wird deshalb
 * nicht, wie oft ein falsches Passwort durchgeht, sondern wie oft ein
 * richtiges — und ab wann nicht mehr.
 *
 * Die Gegenprobe gehört dazu: Ein anderes Konto darf von der Sperre NICHT
 * betroffen sein. Ohne sie hieße „gesperrt" nur „irgendetwas ist kaputt".
 */
const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);
const basis = process.argv[2] || 'https://127.0.0.1:8443';
const grenze = parseInt(process.argv[3] || '20', 10);

const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: true });
const seite = await kontext.newPage();
const befunde = [];

async function versuch(mail, pw) {
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', mail);
  await seite.fill('input[name="password"]', pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
    seite.click('button[type="submit"]'),
  ]);
  const drin = !seite.url().includes('login.php');
  const text = drin ? '' : (await seite.locator('body').innerText()).replace(/\s+/g, ' ');
  return { drin, text };
}

let ersteSperre = null;
for (let i = 1; i <= grenze + 3; i++) {
  const r = await versuch('demo@gen-em.org', 'nadokudemo0815');
  if (!r.drin && ersteSperre === null) {
    ersteSperre = i;
    console.log(`  Anmeldung ${i}: abgewiesen — ${r.text.slice(0, 120)}`);
    if (!/sehr häufig genutzt/i.test(r.text)) {
      befunde.push(`Abweisung nennt nicht die Menge als Grund: ${r.text.slice(0, 120)}`);
    }
    break;
  }
  if (i % 5 === 0) console.log(`  Anmeldung ${i}: gelungen`);
}
console.log(`\nErste Abweisung bei Anmeldung: ${ersteSperre ?? 'keine'} (Grenze ${grenze})`);
if (ersteSperre === null) { befunde.push(`Bremse griff bis ${grenze + 3} nicht`); }
else if (ersteSperre !== grenze + 1) {
  befunde.push(`Bremse griff bei ${ersteSperre}, erwartet ${grenze + 1}`);
}

// Gegenprobe: ein anderes Konto bleibt unberührt.
const andere = await versuch('admin@gen-em.org', 'adminlokal2026');
console.log(`Gegenprobe admin@gen-em.org: ${andere.drin ? 'kommt herein' : 'ABGEWIESEN'}`);
if (!andere.drin) { befunde.push('Die Demo-Sperre trifft auch ein anderes Konto'); }

console.log(befunde.length ? `\nBEFUNDE (${befunde.length})\n  ` + befunde.join('\n  ')
                           : '\nKeine Befunde.');
await browser.close();
process.exit(befunde.length === 0 ? 0 : 1);
