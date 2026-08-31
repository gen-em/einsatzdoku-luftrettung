/* Referenz-Exporte erzeugen (Arbeitspaket B5, E-P1-12).
 *
 * Zwei Dateien, beide im Browser gebaut — der Server sieht die geschützten
 * Angaben nie im Klartext:
 *
 *   einsaetze_<…>.zip   CSV-Archiv, MIT personenbezogenen Angaben, OHNE
 *                       Archivpasswort, mit tracks/. Der Klartext ist hier
 *                       Absicht: Das Archiv ist die Vergleichsgrundlage der
 *                       Regressionsläufe, und ein Vergleich von Chiffretext
 *                       verglich nur den Zufall der IVs.
 *   <…>.edbak           Sicherung mit dem festen Passwort aus F-P1-01.
 *
 * Aufruf:
 *   node referenz_export.mjs [basis] [email] [passwort] [zielordner] [edbak-pw]
 */
import { mkdirSync, writeFileSync } from 'node:fs';
import { basename } from 'node:path';
/* Kontrollkästchen und Segmenttasten sind seit P3 unsichtbar; bedient
 * wird die Beschriftung. Warum und wie: bedienen.mjs. */
const { ankreuzen, abwaehlen } = await import(
  new URL('./bedienen.mjs', import.meta.url).href);

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis    = process.argv[2] || 'https://127.0.0.1:8443';
const email    = process.argv[3] || 'demo@gen-em.org';
const passwort = process.argv[4] || 'nadokudemo0815';
const ziel     = process.argv[5] || new URL('../referenz', import.meta.url).pathname;
const edbakPw  = process.argv[6] || 'nadokudemo0815';
mkdirSync(ziel, { recursive: true });

const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, acceptDownloads: true });
const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const befunde = [];
const pruefe = (ok, t) => { if (!ok) befunde.push(t); };
const schritte = [];
const schritt = (nr, was) => { schritte.push(`${nr}. ${was}`); console.log(`  ${nr}. ${was}`); };

// ---- Anmelden ------------------------------------------------------------
await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
await seite.fill('input[name="email"]', email);
await seite.fill('input[name="password"]', passwort);
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
  seite.click('button[type="submit"]'),
]);
schritt(1, `Anmelden als ${email}`);
pruefe(!seite.url().includes('login.php'), 'Anmeldung gescheitert');

// ---- 1) CSV-Archiv -------------------------------------------------------
await seite.goto(`${basis}/import.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
schritt(2, 'Einstellungen → Import / Export öffnen');

await ankreuzen(seite, 'input[name="exp_zr"][value="all"]');
schritt(3, 'Zeitraum: Alles');

await seite.selectOption('#exp_fmt', 'b');
await seite.waitForTimeout(400);
schritt(4, 'Format: CSV (Standard)');

/* Reihenfolge beachten: „Personenbezogene Angaben" schaltet die GPX-Wahl
   überhaupt erst frei (A9 — eine Flugspur endet am Einsatzort). Wer zuerst
   GPX anhakt und danach die Personenangaben, hakt ins Leere. */
await ankreuzen(seite, '#exp_pat');
await seite.waitForTimeout(400);
const patAn = await seite.isChecked('#exp_pat');
pruefe(patAn, 'Personenbezogene Angaben ließen sich nicht anhaken — Krypto gesperrt?');
schritt(5, 'Personenbezogene Angaben einschließen: an');

const gpxSichtbar = await seite.locator('#exp_gpx_row').isVisible().catch(() => false);
pruefe(gpxSichtbar, 'GPX-Wahl nicht sichtbar');
if (gpxSichtbar) { await ankreuzen(seite, '#exp_gpx'); }
schritt(6, 'GPX-Tracks einschließen: an');

await seite.uncheck('#exp_pw');
await seite.waitForTimeout(300);
schritt(7, 'Mit Passwort schützen: AUS (die Referenz muss lesbar bleiben)');

/* DREI RÜCKFRAGEN LIEGEN VOR DEM EXPORT.
   assets/confirm.js baut je Aufruf ein eigenes <dialog> in die Seite und
   wartet auf einen Klick. Der Export fragt bei personenbezogenen Angaben,
   bei gesetztem Passwort und in jedem Fall „das ersetzt kein Backup" — wer
   nur auf „Export erstellen" klickt und dann auf den Download wartet, wartet
   ewig. Das ist keine Panne, sondern der Zweck der Dialoge. */
async function rueckfragenBestaetigen(hoechstens = 4) {
  let n = 0;
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 8000 }); }
    catch { break; }
    const text = (await seite.locator('dialog[open] [data-text]').first()
      .textContent().catch(() => '') || '').trim().slice(0, 60);
    await ja.first().click();
    await seite.waitForTimeout(500);
    n++;
    console.log(`     ↳ Rückfrage bestätigt: „${text}…"`);
  }
  return n;
}

/* Der Fortschritt wird mitgelesen. Ein Export über 82 Einsätze mit rund
   56 000 Spurpunkten baut 182 GPX-Dateien; ohne diese Ausgabe ist ein
   langsamer Lauf von einem hängenden nicht zu unterscheiden. */
async function mitFortschritt(warten, feld) {
  let letzter = '';
  const uhr = setInterval(async () => {
    const s = (await seite.locator(feld).textContent().catch(() => '') || '').trim();
    if (s && s !== letzter) { letzter = s; console.log(`     · ${s}`); }
  }, 3000);
  try { return await warten; } finally { clearInterval(uhr); }
}

/* DAS WARTEN WIRD VOR DEM KLICK ANGEMELDET.
   waitForEvent() horcht erst ab dem Aufruf. Wer es hinter das Bestätigen der
   Rückfragen setzt, verliert das Rennen, sobald der Export schneller fertig
   ist als die Schleife ihre letzten Leerläufe abwartet — der Download kommt,
   niemand hört zu, und das Skript wartet bis zum Zeitlimit auf ein Ereignis,
   das längst vorbei ist. Das sah aus wie ein Fehler der Anwendung und war
   einer des Prüfmittels. */
const wartenCsv = seite.waitForEvent('download', { timeout: 900000 });
await seite.click('#exp_go');
const fragenCsv = await rueckfragenBestaetigen();
pruefe(fragenCsv > 0, 'Keine Rückfrage erschienen — erscheint der Dialog überhaupt?');
const dlCsv = await mitFortschritt(wartenCsv, '#exp_state');
const nameCsv = dlCsv.suggestedFilename();
await dlCsv.saveAs(`${ziel}/${nameCsv}`);
const zustandCsv = (await seite.locator('#exp_state').textContent().catch(() => '') || '').trim();
schritt(8, `Export erstellen (${fragenCsv} Rückfragen) → ${nameCsv} — ${zustandCsv}`);

// ---- 2) edbak ------------------------------------------------------------
await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
schritt(9, 'Einstellungen → Sicherung öffnen');

const gesperrt = await seite.locator('#lockwarn').isVisible().catch(() => false);
pruefe(!gesperrt, 'Verschlüsselung gesperrt — die Sicherung wäre unvollständig');

await seite.fill('#bpw1', edbakPw);
await seite.fill('#bpw2', edbakPw);
schritt(10, 'Backup-Passwort zweimal eingeben');

const wartenBak = seite.waitForEvent('download', { timeout: 900000 });
await seite.click('#expbtn');
const fragenBak = await rueckfragenBestaetigen();
const dlBak = await mitFortschritt(wartenBak, '#expstate');
const nameBak = dlBak.suggestedFilename();
await dlBak.saveAs(`${ziel}/${nameBak}`);
const zustandBak = (await seite.locator('#expstate').textContent().catch(() => '') || '').trim();
schritt(11, `Backup erstellen (${fragenBak} Rückfragen) → ${nameBak} — ${zustandBak}`);

const ergebnis = {
  basis, ziel,
  csv_archiv: basename(nameCsv), csv_zustand: zustandCsv,
  edbak: basename(nameBak), edbak_zustand: zustandBak, edbak_passwort: edbakPw,
  schritte, konsolenfehler: konsole, befunde,
};
writeFileSync(`${ziel}/.export_lauf.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log('\n' + JSON.stringify({ csv_archiv: ergebnis.csv_archiv, edbak: ergebnis.edbak,
                                    konsolenfehler: konsole, befunde }, null, 2));
await browser.close();
process.exit(befunde.length === 0 && konsole.length === 0 ? 0 : 1);
