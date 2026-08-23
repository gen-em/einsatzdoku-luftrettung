/* Sichtprüfung des Referenzzustands im Browser (Abnahme B3).
 *
 * WOFUER. Alles bis hierher ist über HTTP geprüft: Zahlen in der Datenbank,
 * Antworten der Endpunkte. Was das NICHT beantwortet, ist die Frage, ob die
 * Anwendung den Bestand auch ANZEIGT — ob die Karte eine Spur zeichnet, ob
 * die geschützten Angaben nach dem Entsperren lesbar sind, ob die Konsole
 * still bleibt. Genau das ist die Stichprobe, die die Abnahme verlangt.
 *
 * Aufruf:
 *   node sichtpruefung.mjs [basis] [email] [passwort] [ausgabeordner]
 */
import { writeFileSync, mkdirSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis = process.argv[2] || 'https://127.0.0.1:8443';
const email = process.argv[3] || 'demo@gen-em.org';
const passwort = process.argv[4] || 'nadokudemo0815';
const ordner = process.argv[5] || '/tmp/sichtpruefung';
mkdirSync(ordner, { recursive: true });

const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, viewport: { width: 1400, height: 1000 } });
const seite = await kontext.newPage();

const konsole = [];
/* Kartenkacheln kommen von tile.openstreetmap.org und Nachbarn — eine
   Laufzeitquelle, die es beim Kartenhintergrund bewusst gibt (map_layers.js
   nennt Herkunft und Lizenz). In einer Umgebung ohne Netzzugang dorthin
   scheitern sie, und das sagt über die Anwendung nichts. Sie werden deshalb
   ausgenommen — und im Prüfdokument ausdrücklich als NICHT geprüft benannt. */
const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
seite.on('console', m => {
  if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) { konsole.push(m.text()); }
});
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const befunde = [];
const pruefe = (ok, text) => { if (!ok) befunde.push(text); };

// ---- 1. Anmelden ---------------------------------------------------------
await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
await seite.fill('input[name="email"]', email);
await seite.fill('input[name="password"]', passwort);
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
  seite.click('button[type="submit"]'),
]);
pruefe(!seite.url().includes('login.php'), 'Anmeldung im Browser gescheitert');

// ---- 2. Tagesübersicht ---------------------------------------------------
/* domcontentloaded statt networkidle: Solange die Kartenkacheln nicht laden
   können, wird das Netz nie „idle". */
await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(3000);
const titel = (await seite.locator('#daytitle').textContent()).trim();
pruefe(titel !== '–' && titel.length > 2, `Tagesübersicht ohne Diensttag: ${titel}`);

const zeilen = await seite.locator('#missions tbody tr').count();
pruefe(zeilen > 0, 'Tagesübersicht ohne Einsatzzeilen');

// Karte: Leaflet zeichnet die Spur als <path> in einem SVG-Overlay.
const spuren = await seite.locator('#map svg path, .leaflet-overlay-pane path').count();
pruefe(spuren > 0, 'Keine Spur auf der Tageskarte');
await seite.screenshot({ path: `${ordner}/01-tagesuebersicht.png`, fullPage: false });

// ---- 3. Ein Einsatz mit geschützten Angaben ------------------------------
/* Die Zeilen der Tagestabelle sind KEINE Links: missiontable.js setzt
   `location.href` beim Klick (Zeile 318). Ein Selektor auf <a> fände nichts —
   der erste Anlauf lief hier in einen Timeout. */
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
  seite.locator('#missions tbody tr').first().click(),
]);
await seite.waitForTimeout(2500);
pruefe(seite.url().includes('einsatz.php'), 'Einsatzseite nicht erreicht');

const spurEinsatz = await seite.locator('#map svg path, .leaflet-overlay-pane path').count();
pruefe(spurEinsatz > 0, 'Keine Spur auf der Einsatzkarte');
const phasen = await seite.locator('#phasebody tr').count();
pruefe(phasen > 0, 'Keine Phasenzeilen am Einsatz');

// Geschützte Angaben: Der Browser hat den Schlüssel aus der Anmeldung im
// sessionStorage — sie müssen ohne weiteres Zutun lesbar sein.
const text = await seite.locator('body').innerText();
const gesperrt = /gesperrt|entsperren/i.test(text);
pruefe(!gesperrt, 'Geschützte Angaben sind gesperrt, obwohl frisch angemeldet');
/* Ohne `i`: Die Beschriftungen stehen per CSS in Versalien, und innerText
   liefert den GERENDERTEN Text — „DIAGNOSE 🔒". Ein Muster mit grosser
   Anfangsbuchstabe fand hier nichts und meldete einen Fehler, den es nicht
   gab. Geprüft wird beides: dass die Beschriftung da ist UND dass ein Wert
   darunter steht (die Beschriftung allein bewiese nur, dass die Seite
   gerendert hat, nicht dass entschlüsselt wurde). */
const hatDiagnose = /diagnose\s*🔒/i.test(text);
const wert = text.match(/diagnose\s*🔒\s*\n(.+)/i);
const hatWert = !!(wert && wert[1].trim().length > 3);
pruefe(hatDiagnose, 'Keine Diagnose-Beschriftung auf der Einsatzseite');
pruefe(hatWert, 'Diagnose-Beschriftung ohne Wert — nicht entschlüsselt');
await seite.screenshot({ path: `${ordner}/02-einsatz.png`, fullPage: false });

// ---- 4. Zeitraum-Übersicht ----------------------------------------------
await seite.goto(`${basis}/zeitraum.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(3000);
const kacheln = await seite.locator('.kachel, .stat, .kpi').count();
await seite.screenshot({ path: `${ordner}/03-zeitraum.png`, fullPage: false });

// ---- Ergebnis ------------------------------------------------------------
const ergebnis = {
  basis, titel, einsatzzeilen: zeilen, spuren_tag: spuren,
  spuren_einsatz: spurEinsatz, phasenzeilen: phasen,
  geschuetzt_lesbar: !gesperrt && hatDiagnose && hatWert,
  diagnose_gelesen: wert ? wert[1].trim() : null,
  kacheln, konsolenfehler: konsole, befunde,
};
writeFileSync(`${ordner}/ergebnis.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log(JSON.stringify(ergebnis, null, 2));
await browser.close();
process.exit(befunde.length === 0 && konsole.length === 0 ? 0 : 1);
