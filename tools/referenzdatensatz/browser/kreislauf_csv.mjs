/* Kreislauftest des CSV-Archivs (Arbeitspaket B5, P-08).
 *
 *   Referenz-Archiv  →  frisches Konto  →  erneut exportieren  →  vergleichen
 *
 * Die Importseite liest das ZIP unmittelbar (Export-Format.md 5.1) — die
 * enthaltene `einsaetze.csv` muss nicht ausgepackt werden.
 *
 * ZWEI ANGABEN KOMMEN NICHT AUS DER DATEI: Rettungsmittel und Standort werden
 * oben auf der Seite gewaehlt. Das ist Absicht (E15): Ein Kennzeichen aus der
 * Datei legte sonst stillschweigend neue Stammdaten an. Fuer den Umlauf heisst
 * es, dass eine Datei mit mehreren Rettungsmitteln auf EINES zusammenfaellt —
 * eine der erwarteten Abweichungen, und der Grund, warum das Zielkonto vorher
 * Stammdaten braucht.
 *
 * Aufruf:
 *   node kreislauf_csv.mjs [basis] [referenz.zip] [zielordner]
 */
import { mkdirSync, writeFileSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const quelle  = process.argv[3];
const ordner  = process.argv[4] || '/tmp/kreislauf-csv';
const konto   = process.env.UMLAUF_KONTO || 'umlauf-csv@gen-em.org';
const kontoPw = process.env.UMLAUF_PASSWORT || 'umlaufpruefung2026';
mkdirSync(ordner, { recursive: true });

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
let nr = 0;
const schritt = (was) => console.log(`  ${++nr}. ${was}`);

async function rueckfragen(hoechstens = 4) {
  let n = 0;
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 8000 }); } catch { break; }
    await ja.first().click(); await seite.waitForTimeout(500); n++;
  }
  return n;
}

await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
await seite.fill('input[name="email"]', konto);
await seite.fill('input[name="password"]', kontoPw);
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
  seite.click('button[type="submit"]'),
]);
pruefe(!seite.url().includes('login.php'), `Anmeldung als ${konto} gescheitert`);
schritt(`Als ${konto} anmelden`);

// ---- Import -------------------------------------------------------------
await seite.goto(`${basis}/import.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.setInputFiles('#datei', quelle);
await seite.waitForTimeout(4000);

const profil = await seite.locator('#profil').inputValue().catch(() => '');
pruefe(/export_csv_v1/.test(profil), `Profil nicht erkannt: ${profil}`);
const bilanz = (await seite.locator('#bilanz').textContent().catch(() => '') || '').trim();
schritt(`Archiv gewählt, Profil ${profil} — ${bilanz}`);

/* Die AUSGEWAEHLTE Angabe zaehlt, nicht die erste im Feld: Das erste
   <option> ist der leere Platzhalter, sein value also "". Die erste Fassung
   las genau den und meldete "Stammdaten fehlen", waehrend die Seite ein
   Rettungsmittel sauber vorbelegt hatte — ein Befund ueber das Pruefmittel,
   nicht ueber die Anwendung. */
const fahrzeug = await seite.locator('#vehsel').inputValue().catch(() => '');
const standort = await seite.locator('#basesel').inputValue().catch(() => '');
pruefe(!!fahrzeug && !!standort,
       `Kein Rettungsmittel oder Standort gewählt (veh=${fahrzeug}, base=${standort}) `
       + '— fehlen im Zielkonto die Stammdaten?');
const fahrzeugText = (await seite.locator('#vehsel option:checked').textContent().catch(() => '') || '').trim();
const standortText = (await seite.locator('#basesel option:checked').textContent().catch(() => '') || '').trim();
schritt(`Rettungsmittel „${fahrzeugText}", Standort „${standortText}"`);

const bereit = (await seite.locator('#bereit').textContent().catch(() => '') || '').trim();
const gesperrt = await seite.locator('#commit').isDisabled();
pruefe(!gesperrt, `„Import ausführen" gesperrt — ${bereit}`);
if (!gesperrt) {
  await seite.click('#commit');
  for (let i = 0; i < 120; i++) {
    await seite.waitForTimeout(2000);
    const s = (await seite.locator('#commitstate').textContent().catch(() => '') || '').trim();
    if (/Fertig|fehlgeschlagen|Fehler/i.test(s)) { break; }
  }
}
const commitZustand = (await seite.locator('#commitstate').textContent().catch(() => '') || '').trim();
schritt(`Import ausführen → ${commitZustand}`);
pruefe(/Fertig/i.test(commitZustand), `Import gescheitert: ${commitZustand}`);

// ---- Erneut exportieren, mit denselben Einstellungen wie die Referenz ----
await seite.goto(`${basis}/import.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.check('input[name="exp_zr"][value="all"]');
await seite.selectOption('#exp_fmt', 'b');
await seite.waitForTimeout(400);
await seite.check('#exp_pat');
await seite.waitForTimeout(400);
if (await seite.locator('#exp_gpx_row').isVisible().catch(() => false)) {
  await seite.check('#exp_gpx');
}
await seite.uncheck('#exp_pw');
await seite.waitForTimeout(300);

const warten = seite.waitForEvent('download', { timeout: 900000 });
await seite.click('#exp_go');
await rueckfragen();
const dl = await warten;
const ziel = `${ordner}/${dl.suggestedFilename()}`;
await dl.saveAs(ziel);
const expZustand = (await seite.locator('#exp_state').textContent().catch(() => '') || '').trim();
schritt(`Erneut exportieren → ${dl.suggestedFilename()} — ${expZustand}`);

const ergebnis = { basis, konto, quelle, ergebnisdatei: ziel, bilanz,
                   rettungsmittel: fahrzeugText, standort: standortText,
                   importieren: commitZustand, exportieren: expZustand,
                   konsolenfehler: konsole, befunde };
writeFileSync(`${ordner}/lauf.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log('\n' + JSON.stringify({ ergebnisdatei: ziel, konsolenfehler: konsole, befunde }, null, 2));
await browser.close();
process.exit(befunde.length === 0 ? 0 : 1);
