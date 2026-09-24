/* CSV-Import der nachträglich erfassten Einsätze (Arbeitspaket B4).
 *
 * WARUM IM BROWSER. Die Importseite enthält bewusst KEINE Verarbeitungslogik
 * (`import.php`, Kopfkommentar): Die Datei wird nicht hochgeladen, sondern im
 * Browser gelesen, geprüft und dort verschlüsselt. Der Server bekommt die
 * geschützten Angaben ausschließlich als Chiffretext. Ein Skript, das den
 * Import serverseitig nachbaute, prüfte den Weg gar nicht, den es geben soll.
 *
 * Aufruf:
 *   node csv_import.mjs [basis] [email] [passwort] [csv-datei] [ausgabeordner]
 *
 * VON HAND — dieselbe Strecke, nachpruefbar (Voraussetzung: Einspiellauf
 * durch, generator/ausgabe/import/einsaetze.csv liegt vor):
 *   1. Anmelden als demo@gen-em.org; der Inhaltsschluessel muss im Tab sein
 *      (Warnung „Verschluesselung gesperrt" -> ab- und neu anmelden).
 *   2. Einstellungen -> Import / Export, Datei waehlen. Erwartet: „CSV
 *      (Standard)", das Profil export_csv_v1.
 *   3. Prueftabelle beim ersten Lauf: „6 Zeilen — 2 Diensttage, 4 Einsaetze,
 *      0 Hinweise, 0 Fehler, 0 Dubletten". Ein HINWEIS sperrt nicht: Die
 *      Werte fallen still weg, und die Bilanz sagt trotzdem „0 Fehler".
 *   4. „Import ausfuehren": „4 Einsaetze angelegt"; ein zweiter Lauf meldet
 *      4 Dubletten und legt nichts an — richtig so.
 *
 * ZWEI FALLEN, BEIDE ERLEBT:
 *   - Der Zonenversatz braucht einen Doppelpunkt. `PARSERS.isoTs` prueft
 *     gegen `[+-]\d{2}:\d{2}`; Pythons `%z` liefert `+0200`. Endzeit und alle
 *     acht Phasenzeiten fielen als Hinweis durch. Der Generator setzt den
 *     Versatz deshalb von Hand (`erzeugen.iso_offset`), und generator/
 *     pruefen.py haelt die Datei gegen die Parser der Anwendung.
 *   - Eine Zelle mit `=` am Anfang kommt leer an: SheetJS liest sie als
 *     Formel. Die Formel-Anfangszeichen des Bestands stehen deshalb auf dem
 *     Formularweg, nicht auf dem CSV-Weg (F-P1-G).
 */
import { writeFileSync, mkdirSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis = process.argv[2] || 'https://127.0.0.1:8443';
const email = process.argv[3] || 'demo@gen-em.org';
const passwort = process.argv[4] || 'nadokudemo0815';
const csv = process.argv[5]
  || new URL('../generator/ausgabe/import/einsaetze.csv', import.meta.url).pathname;
const ordner = process.argv[6] || '/tmp/b4-import';
mkdirSync(ordner, { recursive: true });

const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, viewport: { width: 1500, height: 1100 } });
const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const befunde = [];
const pruefe = (ok, text) => { if (!ok) befunde.push(text); };
const schritte = [];
const schritt = (nr, was) => { schritte.push(`${nr}. ${was}`); console.log(`  ${nr}. ${was}`); };

// ---- 1 ------------------------------------------------------------------
await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
await seite.fill('input[name="email"]', email);
await seite.fill('input[name="password"]', passwort);
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
  seite.click('button[type="submit"]'),
]);
schritt(1, `Anmelden als ${email}`);
pruefe(!seite.url().includes('login.php'), 'Anmeldung gescheitert');

// ---- 2 ------------------------------------------------------------------
await seite.goto(`${basis}/import.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1200);
schritt(2, 'Einstellungen → Import / Export öffnen');
const gesperrt = await seite.locator('#lockwarn').isVisible().catch(() => false);
pruefe(!gesperrt, 'Verschlüsselung gesperrt — der Import könnte nicht verschlüsseln');

// ---- 3 ------------------------------------------------------------------
await seite.setInputFiles('#datei', csv);
await seite.waitForTimeout(2500);
schritt(3, `Datei wählen: ${csv.split('/').pop()}`);

const profil = await seite.locator('#profil').inputValue().catch(() => '');
const profilText = await seite.locator('#profil option:checked').textContent().catch(() => '');
pruefe(/export_csv_v1/i.test(profil) || /eigener|export/i.test(profilText || ''),
       `Profil nicht als export_csv_v1 erkannt (${profil} / ${profilText})`);
schritt(4, `Profil erkannt: ${(profilText || profil).trim()}`);

// ---- 4: Bilanz und Prüftabelle ------------------------------------------
const bilanz = (await seite.locator('#bilanz').textContent().catch(() => '') || '').trim();
const zeilen = await seite.locator('#tabelle tbody tr').count();
schritt(5, `Prüftabelle: ${zeilen} Zeilen — ${bilanz}`);
await seite.screenshot({ path: `${ordner}/01-pruefung.png` });

// ---- 5: Import ausführen -------------------------------------------------
const bereit = (await seite.locator('#bereit').textContent().catch(() => '') || '').trim();
const commitAus = await seite.locator('#commit').isDisabled();
pruefe(!commitAus, `„Import ausführen" ist gesperrt — ${bereit}`);
if (!commitAus) {
  await seite.click('#commit');
  await seite.waitForFunction(
    () => /fertig|übernommen|importiert|angelegt|Fehler/i.test(
      document.getElementById('commitstate')?.textContent || ''),
    { timeout: 120000 });
  const stand = (await seite.locator('#commitstate').textContent()).trim();
  schritt(6, `Import ausführen → ${stand}`);
  pruefe(!/fehler/i.test(stand), `Import meldet einen Fehler: ${stand}`);
}
await seite.screenshot({ path: `${ordner}/02-nach-import.png` });

const ergebnis = { basis, csv, profil, profilText: (profilText || '').trim(),
                   bilanz, pruefzeilen: zeilen, bereit,
                   konsolenfehler: konsole, befunde, schritte };
writeFileSync(`${ordner}/ergebnis.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log(JSON.stringify({ befunde, konsolenfehler: konsole }, null, 2));
await browser.close();
process.exit(befunde.length === 0 && konsole.length === 0 ? 0 : 1);
