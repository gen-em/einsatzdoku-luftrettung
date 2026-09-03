/* Kreislauftest des Backups (Arbeitspaket B5, P-09).
 *
 * DIE FRAGE. Kommt derselbe Bestand nach einem vollstaendigen Umlauf
 * unveraendert wieder heraus?
 *
 *   Referenz-edbak  →  frisches Konto  →  erneut exportieren  →  vergleichen
 *
 * WARUM EIN FRISCHES KONTO. Ein Rueckspielen in dasselbe Konto beantwortet
 * die Frage nicht: Die Dublettenerkennung ueberspringt dort alles, und was
 * herauskaeme, waere der unveraenderte Ausgangsbestand — ein Vergleich mit
 * sich selbst. Erst ein leeres Konto zwingt jeden Datensatz durch den
 * Einspielweg.
 *
 * Das Konto entsteht ueber den REGULAEREN Einladungsweg; das Passwort wird im
 * Browser gesetzt, weil dort auch das Schluesselmaterial entsteht. Die
 * geschuetzten Angaben des Backups liegen im inneren JSON im Klartext und
 * werden beim Einspielen mit dem Inhaltsschluessel DIESES Kontos neu
 * verschluesselt — der Chiffretext ist danach ein anderer, der Klartext
 * derselbe. Genau deshalb vergleicht das Werkzeug Klartext.
 *
 * Aufruf:
 *   node kreislauf_edbak.mjs [basis] [referenz.edbak] [backup-pw] [zielordner]
 */
import { mkdirSync, writeFileSync, readdirSync } from 'node:fs';

/* Die Referenzdatei NICHT mit Namen fest verdrahten.
 *
 * Hier stand `einsatzdoku-backup-2026-08-23.edbak`. Der Name trägt das
 * Erzeugungsdatum, und der Referenzordner wird bei jedem Neuaufbau des
 * Referenzstands neu befüllt — die Vorgabe zeigte danach ins Leere, ohne dass
 * es jemandem auffiel (kreislauf.py übergibt den Pfad selbst und deckte das
 * zu). Gesucht wird deshalb die eine `.edbak` im Ordner; sind es mehrere,
 * bricht der Lauf ab statt zu raten — dieselbe Regel wie in kreislauf.py. */
function referenzDatei(ordner) {
  const treffer = readdirSync(ordner).filter(n => n.endsWith('.edbak')).sort();
  if (treffer.length !== 1) {
    throw new Error(`In ${ordner} liegen ${treffer.length} .edbak-Dateien — `
      + 'erwartet wird genau eine. Pfad ausdrücklich angeben.');
  }
  return `${ordner}/${treffer[0]}`;
}

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const quelle  = process.argv[3]
  || referenzDatei(new URL('../referenz', import.meta.url).pathname);
const bpw     = process.argv[4] || 'nadokudemo0815';
const ordner  = process.argv[5] || '/tmp/kreislauf-edbak';
const konto   = process.env.UMLAUF_KONTO || 'umlauf-edbak@gen-em.org';
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

async function anmelden(mail, pw) {
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.waitForTimeout(600);
  await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', mail);
  await seite.fill('input[name="password"]', pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('button[type="submit"]'),
  ]);
  return !seite.url().includes('login.php');
}

async function rueckfragen(hoechstens = 4) {
  let n = 0;
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 8000 }); } catch { break; }
    await ja.first().click(); await seite.waitForTimeout(500); n++;
  }
  return n;
}

/* Das Konto ist zu diesem Zeitpunkt schon angelegt und hat ein Passwort —
   beides erledigt kreislauf.py ueber die vorhandenen, geprueften Bausteine
   (einspielen.py --stufen konto, passwort_setzen.mjs). Hier wird nichts
   nachgebaut: Ein zweiter Weg zum Anlegen eines Kontos waere ein zweiter
   Weg, den niemand pflegt. */

// ---- 3. Backup in das frische Konto einspielen ------------------------
pruefe(await anmelden(konto, kontoPw), `Anmeldung als ${konto} gescheitert`);
schritt(`Als ${konto} anmelden`);

await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.setInputFiles('#bfile', quelle);
await seite.fill('#ipw', bpw);
await seite.click('#impbtn');
await rueckfragen();

/* AUF DIE MELDUNG WARTEN, NICHT AUF EINEN WORTLAUT (S2/AP5b, F-S2-D).
 *
 * Hier stand `/fertig|eingespielt|fehlgeschlagen|Fehler|falsch/`. Das ist
 * eine Wette darauf, welche Wörter die Anwendung benutzt — und sie ging
 * verloren: Der Abbruch heisst „Abgebrochen — es wurde nichts übernommen.",
 * kommt in keinem dieser Wörter vor, und der Lauf wartete die vollen 300
 * Sekunden auf einen Zustand, den es schon gab. Danach hätte er ein LEERES
 * Konto exportiert und verglichen.
 *
 * Die Anwendung unterscheidet Zwischenstand und Ergebnis selbst: Ein
 * Fortschrittstext wird als reiner Text gesetzt, ein Ergebnis über
 * `melde(el, text, ton)` als `<div class="meldung meldung-ok|warn|fehler">`.
 * Auf dieses Element wird gewartet — es ist da oder nicht, und es trägt
 * seinen Ton gleich mit. Ein künftiger Ergebnistext, an den hier niemand
 * gedacht hat, wird damit von selbst erkannt. */
const impMeldung = seite.locator('#impstate .meldung');
try {
  await impMeldung.first().waitFor({ state: 'attached', timeout: 900000 });
} catch {
  pruefe(false, 'Das Einspielen kam in 900 s zu keinem Ergebnis. '
              + `Letzter Zustand: ${(await seite.locator('#impstate').textContent()
                  .catch(() => '') || '').trim()}`);
}
const impZustand = (await seite.locator('#impstate').textContent().catch(() => '') || '').trim();
const impTon = (await impMeldung.first().getAttribute('class').catch(() => '') || '');
const herkunft = (await seite.locator('#impherkunft').textContent().catch(() => '') || '').trim();
schritt(`Backup eingespielt — ${impZustand}`);
if (herkunft) { console.log(`     Herkunft: ${herkunft}`); }
/* Nur `meldung-ok` ist ein bestandenes Einspielen. `meldung-warn` deckt
   zweierlei ab, das beides kein Erfolg ist: den Abbruch vor dem ersten
   Schreiben und ein Einspielen mit abgelehnten oder heimatlosen Spuren. */
pruefe(/meldung-ok/.test(impTon), `Einspielen nicht sauber: ${impZustand}`);

// ---- 4. Aus dem frischen Konto erneut sichern ----------------------------
await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.fill('#bpw1', bpw);
await seite.fill('#bpw2', bpw);
const warten = seite.waitForEvent('download', { timeout: 900000 });
await seite.click('#expbtn');
await rueckfragen();
const dl = await warten;
const ziel = `${ordner}/${dl.suggestedFilename()}`;
await dl.saveAs(ziel);
const expZustand = (await seite.locator('#expstate').textContent().catch(() => '') || '').trim();
schritt(`Erneut gesichert → ${dl.suggestedFilename()} — ${expZustand}`);

const ergebnis = { basis, konto, quelle, ergebnisdatei: ziel,
                   einspielen: impZustand, herkunft, exportieren: expZustand,
                   konsolenfehler: konsole, befunde };
writeFileSync(`${ordner}/lauf.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log('\n' + JSON.stringify({ ergebnisdatei: ziel, konsolenfehler: konsole, befunde }, null, 2));
await browser.close();
process.exit(befunde.length === 0 ? 0 : 1);
