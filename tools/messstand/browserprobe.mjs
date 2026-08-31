/* Browserprobe des Messstands — misst die Zielzahlen aus E-S2-24 und die
 * Gerätebudgets aus Z3 (Konzept 3.5).
 *
 * WOFUER. Ein Zielwert wie „Suche erste Anzeige ≤ 5 s auf dem Referenzgerät"
 * ist wertlos, solange ihn niemand nachmisst — und „fühlt sich schnell an" ist
 * keine Zahl. Diese Probe fährt die vier Wege, um die es in S2 geht, unter
 * gedrosselter CPU und schreibt zu jedem eine Zahl.
 *
 * DAS REFERENZGERAET (Z3) ist ein fünf Jahre altes Handy. Nachgestellt wird es
 * durch `Emulation.setCPUThrottlingRate` mit Faktor 6. Das ist eine Näherung
 * und keine Messung an echter Hardware — es drosselt die Rechenzeit, nicht den
 * Speicher, nicht die GPU und nicht die Leitung. Wo das Ergebnis knapp ist,
 * gehört es an einem echten Gerät nachgeprüft; das steht auch so im
 * Prüfdokument.
 *
 * WAS GEMESSEN WIRD, und warum genau das:
 *
 *   Zeit          Suche bis zur ersten Trefferanzeige, Tagesansicht,
 *                 Sicherung erstellen — die drei Wege aus E-S2-24.
 *   JSON-Größe    Die größte Zeichenkette, die durch JSON.parse oder
 *                 JSON.stringify läuft. Z3 setzt hier 10 MB. Diese Zahl ist
 *                 der eigentliche Grund, aus dem der heutige Sicherungsweg
 *                 bricht (B-S2-03) — sie wird deshalb direkt an der Quelle
 *                 abgegriffen und nicht aus der Übertragungsgröße geschätzt.
 *   Halde         JSHeapUsedSize aus dem Protokoll der Entwicklerwerkzeuge,
 *                 Spitze über den ganzen Schritt. Z3 setzt 100 MB.
 *   PBKDF2        Wie oft `deriveBits` gerufen wurde. Z3 lässt EINE Ableitung
 *                 je Vorgang zu; jede weitere kostet auf dem Referenzgerät
 *                 eine halbe bis eine Sekunde.
 *   Übertragung   Antwortbytes je Schritt und die größte POST-Nutzlast.
 *
 * Aufruf:
 *   node browserprobe.mjs [basis] [ausgabe.json] [drossel]
 *
 * Umgebung: MESSSTAND_KONTO, MESSSTAND_PASSWORT, MESSSTAND_BACKUP_PASSWORT
 */
import { writeFileSync, statSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const ausgabe = process.argv[3] || '/tmp/messstand/browserprobe.json';
const drossel = Number(process.argv[4] || 6);
const konto   = process.env.MESSSTAND_KONTO || 'messstand@gen-em.org';
const kontoPw = process.env.MESSSTAND_PASSWORT || 'messstandpruefung2026';
const bpw     = process.env.MESSSTAND_BACKUP_PASSWORT || 'nadokudemo0815';

const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, acceptDownloads: true });

/* Die Zähler im Browser aufsetzen, BEVOR die erste Seite lädt.
 *
 * `addInitScript` läuft in jedem Dokument vor dem Seitenskript. Damit werden
 * auch die Aufrufe erfasst, die beim Laden passieren — und genau die sind
 * interessant, weil dort das Entsperren liegt. */
await kontext.addInitScript(() => {
  const z = { pbkdf2: 0, jsonMax: 0, jsonParse: 0, jsonStringify: 0 };
  window.__messstand = z;

  const echtDerive = crypto.subtle.deriveBits.bind(crypto.subtle);
  crypto.subtle.deriveBits = function (alg, ...rest) {
    if (alg && (alg.name === 'PBKDF2' || alg === 'PBKDF2')) { z.pbkdf2++; }
    return echtDerive(alg, ...rest);
  };

  const echtParse = JSON.parse;
  JSON.parse = function (text, ...rest) {
    if (typeof text === 'string' && text.length > z.jsonMax) { z.jsonMax = text.length; }
    z.jsonParse++;
    return echtParse(text, ...rest);
  };
  const echtStringify = JSON.stringify;
  JSON.stringify = function (...args) {
    const s = echtStringify(...args);
    if (typeof s === 'string' && s.length > z.jsonMax) { z.jsonMax = s.length; }
    z.jsonStringify++;
    return s;
  };
});

/* KARTENKACHELN AUSSPERREN — und zwar ausdrücklich, nicht nebenbei.
 *
 * Der erste Lauf maß für die Startseite 25,6 s und für die Tagesansicht
 * 30,7 s. Beides war falsch: `waitUntil: 'load'` wartet auf JEDE Ressource,
 * und in dieser Umgebung laufen die Anfragen an tile.openstreetmap.org in
 * einen Zeitablauf (ERR_CONNECTION_RESET). Dieselbe Seite ist mit
 * `domcontentloaded` in 0,8 s da. Gemessen worden war die Netzsperre des
 * Containers, nicht die Anwendung.
 *
 * Deshalb zwei Änderungen: Die Kacheln werden hier hart abgewiesen — damit
 * hängt die Messung nicht mehr davon ab, ob und wie schnell ein fremder
 * Server antwortet —, und gewartet wird unten auf den INHALT, nicht auf das
 * Ladeereignis.
 *
 * WAS DAMIT NICHT GEMESSEN WIRD: das Zeichnen der Kartenkacheln. Das ist
 * richtig so — S2 ändert an der Karte nichts, und die Kachelzeit hängt an
 * einer fremden Quelle. Im Prüfdokument steht es als Grenze des
 * Prüfmittels. */
await kontext.route(/(^https?:\/\/[a-z]?\.?tile\.|opentopomap|arcgisonline|basemap\.at)/i,
                    r => r.abort());

const seite = await kontext.newPage();
const cdp = await kontext.newCDPSession(seite);
await cdp.send('Performance.enable');
await cdp.send('Emulation.setCPUThrottlingRate', { rate: drossel });

/* „Failed to load resource" gehört NICHT in die Konsolenfehler.
 *
 * Die Meldung nennt die Adresse nicht — sie lautet nur
 * „Failed to load resource: net::ERR_FAILED" —, und damit ist sie als
 * Konsolenfehler wertlos: Sie stand hier zunächst 20-mal (die blockierten
 * Kacheln), dann 60-mal (dieselben Kacheln, jetzt von uns abgewiesen), ohne
 * dass ein einziger davon die Anwendung betraf. Ein Zähler, der bei jeder
 * Netzsperre hochgeht, misst das Netz und nicht den Code.
 *
 * Gescheiterte Anfragen stehen deshalb getrennt und MIT ADRESSE unter
 * `gescheiterte_anfragen`; hier bleiben die Fehler, die das Skript der Seite
 * selbst erzeugt. Genau die sind gemeint. */
const RESSOURCENFEHLER = /^Failed to load resource/i;
const konsole = [];
seite.on('console', m => {
  if (m.type() === 'error' && !RESSOURCENFEHLER.test(m.text())) { konsole.push(m.text()); }
});
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

let antwortBytes = 0, postGroesste = 0;
seite.on('response', async r => {
  const l = Number(r.headers()['content-length'] || 0);
  if (l) { antwortBytes += l; }
});
seite.on('request', r => {
  const d = r.postData();
  if (d && d.length > postGroesste) { postGroesste = d.length; }
});
/* Gescheiterte Anfragen MIT ADRESSE festhalten. Die Konsolenmeldung dazu
 * lautet nur „Failed to load resource: net::ERR_CONNECTION_RESET" — ohne
 * Adresse, und damit rutschte sie am Kachelfilter vorbei und stand zwanzigmal
 * als Konsolenfehler im Protokoll. */
const gescheitert = [];
seite.on('requestfailed', r => {
  gescheitert.push(`${r.failure()?.errorText || '?'} ${r.url().slice(0, 120)}`);
});

async function halde() {
  const { metrics } = await cdp.send('Performance.getMetrics');
  const m = Object.fromEntries(metrics.map(x => [x.name, x.value]));
  return Math.round((m.JSHeapUsedSize || 0) / 1024 / 1024);
}

async function zaehler() {
  return await seite.evaluate(() => ({ ...window.__messstand })).catch(() => null);
}

/* Ein Schritt misst sich selbst: Zeit, Haldenspitze, Zähler, Übertragung.
 * Die Haldenspitze wird während des Schritts abgetastet — ein Wert nur am
 * Ende verpasst genau die Spitze, um die es geht. */
async function messen(name, tun) {
  antwortBytes = 0; postGroesste = 0;
  const vorher = await zaehler();
  let spitze = await halde();
  const wecker = setInterval(async () => {
    try { const h = await halde(); if (h > spitze) spitze = h; } catch { /* Seite beschäftigt */ }
  }, 500);
  const t0 = Date.now();
  let ergebnis = null, fehler = null;
  try { ergebnis = await tun(); } catch (e) { fehler = String(e.message || e).slice(0, 300); }
  const dauer = (Date.now() - t0) / 1000;
  clearInterval(wecker);
  const nachher = await zaehler();
  const messung = {
    schritt: name,
    dauer_s: Math.round(dauer * 100) / 100,
    halde_spitze_mb: Math.max(spitze, await halde()),
    pbkdf2: nachher && vorher ? nachher.pbkdf2 - vorher.pbkdf2 : null,
    json_groesste_mb: nachher ? Math.round(nachher.jsonMax / 1024 / 1024 * 100) / 100 : null,
    antwort_mb: Math.round(antwortBytes / 1024 / 1024 * 100) / 100,
    post_groesste_mb: Math.round(postGroesste / 1024 / 1024 * 100) / 100,
    fehler,
  };
  if (ergebnis && typeof ergebnis === 'object') { Object.assign(messung, ergebnis); }
  console.log(`  ${name}: ${messung.dauer_s} s · Halde ${messung.halde_spitze_mb} MB`
    + ` · JSON ${messung.json_groesste_mb} MB · PBKDF2 ${messung.pbkdf2}`
    + (fehler ? ` · FEHLER ${fehler}` : ''));
  return messung;
}

async function entsperren() {
  const d = seite.locator('dialog.dialog:has-text("entsperren")');
  try { await d.waitFor({ state: 'visible', timeout: 4000 }); } catch { return false; }
  await d.locator('input[type="password"]').fill(kontoPw);
  await d.locator('[data-act="yes"]').click();
  await d.waitFor({ state: 'hidden', timeout: 120000 });
  return true;
}

// ---------------------------------------------------------------- Ablauf
console.log(`Browserprobe gegen ${basis}, Drossel ${drossel}×, Konto ${konto}`);
const messungen = [];

messungen.push(await messen('Anmelden', async () => {
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', konto);
  await seite.fill('input[name="password"]', kontoPw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 120000 }),
    seite.click('button[type="submit"]'),
  ]);
  if (seite.url().includes('login.php')) { throw new Error('Anmeldung gescheitert'); }
  return {};
}));

/* Gewartet wird auf den INHALT, nicht auf das Ladeereignis: Die Tagesliste
 * der Seitenleiste ist das, was die Seite benutzbar macht. `dt_liste()`
 * deckelt sie bei 500 Einträgen — auch bei 928 Diensttagen stehen also
 * höchstens 500 im Markup. */
messungen.push(await messen('Startseite (Tagesliste)', async () => {
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded', timeout: 180000 });
  await seite.locator('aside a[href*="?d="]').first().waitFor({ state: 'attached', timeout: 180000 });
  const tage = await seite.locator('aside a[href*="?d="]').count();
  return { tagesverweise: tage };
}));

/* Die Tagesansicht ist erst fertig, wenn die Spur auf der Karte liegt — sie
 * kommt aus api/day.php und ist genau der Weg, den S2 umbaut. Auf das
 * Seitenladen zu warten hieße, auf die Kacheln zu warten. */
messungen.push(await messen('Tagesansicht (Spur gezeichnet)', async () => {
  const verweis = seite.locator('aside a[href*="?d="]').first();
  const ziel = await verweis.getAttribute('href').catch(() => null);
  await seite.goto(ziel ? new URL(ziel, `${basis}/`).href : `${basis}/index.php`,
                   { waitUntil: 'domcontentloaded', timeout: 180000 });
  await entsperren();
  await seite.locator('.leaflet-overlay-pane path').first()
             .waitFor({ state: 'attached', timeout: 180000 })
             .catch(() => { /* ein Tag ohne Spur — dann zählt die Seite selbst */ });
  const spuren = await seite.locator('.leaflet-overlay-pane path').count();
  return { adresse: seite.url().replace(basis, ''), spurlinien: spuren };
}));

messungen.push(await messen('Suche — erste Trefferanzeige', async () => {
  await seite.goto(`${basis}/suche.php`, { waitUntil: 'domcontentloaded', timeout: 300000 });
  await entsperren();
  // Auf die erste gefüllte Trefferzeile warten. Nicht auf ein Netzereignis:
  // Die Zeit, um die es geht, ist die bis zur SICHTBAREN Anzeige, und die
  // liegt hinter dem Entschlüsseln.
  await seite.locator('#suchtable tbody tr, #suchkacheln > *').first()
             .waitFor({ state: 'attached', timeout: 300000 });
  const zahl = (await seite.locator('#trefferzahl').textContent().catch(() => '') || '').trim();
  return { trefferzahl: zahl };
}));

messungen.push(await messen('Sicherung erstellen', async () => {
  await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded', timeout: 180000 });
  await seite.waitForTimeout(800);
  await seite.fill('#bpw1', bpw);
  await seite.fill('#bpw2', bpw);
  const warten = seite.waitForEvent('download', { timeout: 1800000 });
  await seite.click('#expbtn');
  for (let i = 0; i < 8; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 5000 }); } catch { break; }
    await ja.first().click();
    await seite.waitForTimeout(400);
  }
  const dl = await warten;
  const ziel = `/tmp/messstand/${dl.suggestedFilename()}`;
  await dl.saveAs(ziel);
  return { datei: ziel,
           datei_mb: Math.round(statSync(ziel).size / 1024 / 1024 * 100) / 100 };
}));

const bestand = await seite.evaluate(() => document.title).catch(() => '');
const ergebnis = {
  basis, konto, drossel, gemessen_am: new Date().toISOString(),
  messungen, konsolenfehler: konsole, titel: bestand,
  gescheiterte_anfragen: [...new Set(gescheitert)],
  budgets_z3: { json_mb: 10, halde_mb: 100, pbkdf2_je_vorgang: 1, post_mb: 2 },
};
writeFileSync(ausgabe, JSON.stringify(ergebnis, null, 2) + '\n');
console.log(`\nProtokoll: ${ausgabe}`);
if (konsole.length) { console.log(`Konsolenfehler: ${konsole.length}`); }
await browser.close();
process.exit(messungen.some(m => m.fehler) ? 1 : 0);
