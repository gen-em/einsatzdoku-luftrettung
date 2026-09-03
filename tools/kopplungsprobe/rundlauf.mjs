/* Einsatzdoku — der Kopplungsrundlauf im Browser (S5 Paket B).
 *
 * WOFUER, UND WARUM NEBEN probe.php. Die Kopplungsprobe daneben fragt den
 * Endpunkt: Antwortet `pair.php` richtig? Diese Datei fragt den WEG: Kann ein
 * Mensch mit einem Geraet in der Hand ein Konto damit verbinden? Das sind
 * verschiedene Fragen, und die zweite laesst sich nicht per curl beantworten —
 * sie haengt an einem Formular, an einer Umleitung, an einem Skript, das die
 * Seite von selbst holt, und an einer Sitzung, die drei Anfragen ueberlebt.
 *
 * DIE PROBE IST DAS GERAET. Sie holt sich ihre Kopplungssitzung ueber
 * `pair.php` mit `aktion=start` — genau so, wie eine Uhr es tut, ueber echtes
 * HTTP aus dem Seitenkontext. Danach tippt sie den Code ins Formular, klickt
 * sich durch die drei Zustaende der Karte, sagt am „Geraet" Ja und sieht zu,
 * ob die Seite von selbst nachlaedt. Es gibt keine Attrappe und keinen
 * SQL-Handgriff: Was hier gemessen wird, ist der Weg, den die Notaerztin geht.
 *
 * WAS SIE HINTERLAESST: nichts. Das Pruefgeraet wird am Ende ueber
 * `aktion=trennen` wieder abgemeldet, und die Zahl der Geraete vorher und
 * nachher steht im Bericht. Sie laeuft im DEMO-Konto — dort ist Ausprobieren
 * ausdruecklich erwuenscht, und ein Reset alle 30 Minuten faengt auf, was ein
 * Abbruch liegenlaesst.
 *
 * Aufruf (aus dem Wurzelverzeichnis, mit laufender lokaler Installation):
 *   sh tools/referenzdatensatz/einspielen/lokal_starten.sh
 *   node tools/kopplungsprobe/rundlauf.mjs
 *   node tools/kopplungsprobe/rundlauf.mjs --basis https://127.0.0.1:8443 --bilder /tmp/b
 *
 * Rueckgabewert: 0 = alle Erwartungen erfuellt und keine Konsolenfehler.
 */
import { mkdirSync } from 'node:fs';

/* Denselben Neustart wie der Bilderlauf: Nodes fetch liest NODE_USE_ENV_PROXY
 * nur beim Prozessstart. Ohne Proxy geschieht hier nichts. */
if ((process.env.HTTPS_PROXY || process.env.https_proxy) && !process.env.NODE_USE_ENV_PROXY) {
  const { spawnSync } = await import('node:child_process');
  const kind = spawnSync(process.execPath, process.argv.slice(1), {
    stdio: 'inherit', env: { ...process.env, NODE_USE_ENV_PROXY: '1' },
  });
  process.exit(kind.status ?? 1);
}

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const argv = process.argv.slice(2);
const wert = (n, s) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : s; };
const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const DEMO   = { email: wert('--demo', 'demo@gen-em.org'), pw: wert('--demo-pw', 'nadokudemo0815') };
const BILDER = wert('--bilder', '');
const BROWSER = process.env.CHROMIUM_PFAD || '/opt/pw-browsers/chromium';

let n = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  n++; if (!ok) offen++;
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(58)} ${wert}`);
};

/* RAUSCHEN, das nichts ueber die Anwendung sagt — dieselbe Regel wie im
 * Bilderlauf: Die Kartenkacheln kommen von einem fremden Server, den ein
 * abgeschotteter Pruefstand nicht erreicht, und ein ERR_ABORTED entsteht,
 * wenn eine laufende Anfrage von einer Navigation ueberholt wird. */
const fehler = [];
const istRauschen = (t) => /tile\.openstreetmap\.org|ERR_ABORTED|Failed to load resource/.test(t);
const merke = (t) => { if (!istRauschen(t)) fehler.push(t); };

if (BILDER) { mkdirSync(BILDER, { recursive: true }); }
const bild = async (seite, name) => {
  if (BILDER) { await seite.screenshot({ path: `${BILDER}/${name}.png`, fullPage: true }); }
};

console.log(`Kopplungsrundlauf gegen ${BASIS} (Konto ${DEMO.email})`);

const browser = await chromium.launch({ executablePath: BROWSER });
const ctx = await browser.newContext({ ignoreHTTPSErrors: true, viewport: { width: 1280, height: 900 } });
const seite = await ctx.newPage();
seite.on('console', m => { if (m.type() === 'error') merke('console: ' + m.text()); });
seite.on('pageerror', e => merke('pageerror: ' + e.message));
seite.on('requestfailed', r => merke('requestfailed: ' + r.url().replace(BASIS, '')
                                   + ' — ' + (r.failure()?.errorText || '')));

try {

/* ---- Anmelden ------------------------------------------------------------
 * Das Anmelden dauert: Der Browser leitet das Token per PBKDF2 mit 320 000
 * Runden ab, und die Startseite baut den Demo-Bestand auf. Deshalb wird auf
 * die ADRESSE gewartet und nicht auf einen Leerlauf des Netzes — der tritt auf
 * der Anmeldeseite auch dann ein, wenn noch gar nichts abgeschickt wurde. */
await seite.goto(`${BASIS}/login.php`, { waitUntil: 'networkidle' });
await seite.fill('input[name="email"]', DEMO.email);
await seite.fill('input[name="password"]', DEMO.pw);
await seite.click('button[type="submit"]');
await seite.waitForURL(u => !u.pathname.endsWith('/login.php'), { timeout: 90000 });
pruefe(!seite.url().includes('login.php'), 'Anmeldung am Demo-Konto', seite.url().replace(BASIS, ''));

const geh = async () => {
  await seite.goto(`${BASIS}/einstellungen.php?t=geraete`, { waitUntil: 'networkidle', timeout: 60000 });
};
const weiter = async () => {
  await Promise.all([seite.waitForNavigation({ timeout: 60000 }),
                     seite.click('#koppeln .knopf-primaer')]);
  await seite.waitForLoadState('networkidle');
};
const geraeteZahl = async () => seite.locator('#geraeteliste .zeile').count();

await geh();
const vorher = await geraeteZahl();

/* ---- Zustand 1 — das Eingabefeld ---------------------------------------- */
console.log('\n  Zustand 1 — Code vom Gerät eingeben');
pruefe(await seite.locator('#koppeln input[name="code"]').count() === 1,
       'Das Feld „Code vom Gerät" steht da');
pruefe((await seite.locator('#koppeln .knopf-primaer').innerText()).trim() === 'Weiter',
       'Die eine Haupthandlung heißt „Weiter" und ist primär');
pruefe(await seite.locator('.codeblock').count() === 0,
       'Kein Kopplungscode-Kasten mehr — das Gerät zeigt ihn (S5)');
pruefe(await seite.locator('.knopf-primaer').count() === 1,
       'Genau EIN primärer Knopf auf der Seite (Design.md 9.16)',
       (await seite.locator('.knopf-primaer').count()) + ' gefunden');
await bild(seite, 'rundlauf-1-eingabe');

/* ---- Die beiden Fehlerwege ---------------------------------------------- */
await seite.fill('#koppeln input[name="code"]', 'AB0K7Q');
await weiter();
pruefe((await seite.locator('.meldung-fehler').innerText()).includes('0, O, 1 und I'),
       'Ein Code mit „0" -> eigene Meldung, kein Rateversuch (E-S5-17)');
await bild(seite, 'rundlauf-1b-formatfehler');

await seite.fill('#koppeln input[name="code"]', 'ZZZZZZ');
await weiter();
pruefe((await seite.locator('.meldung-fehler').innerText()).includes('kennt der Server nicht'),
       'Ein unbekannter Code -> „kennt der Server nicht"');

/* ---- Ein Gerät meldet sich an ------------------------------------------- */
console.log('\n  Das Gerät holt sich eine Sitzung (pair.php, aktion=start)');
const geraet = await seite.evaluate(async () => {
  const a = await fetch('pair.php', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ aktion: 'start', geraet: { art: 'uhr', teil: '006-B4261-00' } }) });
  return a.json();
});
pruefe(!!geraet.code && !!geraet.device_id && !!geraet.api_key,
       'Code, Kennung und Schlüssel sind da', geraet.code || JSON.stringify(geraet));

/* ---- Zustand 2 — die Rückfrage ------------------------------------------
 * Eingetippt wird MIT Leerzeichen und KLEIN — so, wie ein Mensch abliest, was
 * das Gerät in zwei Dreiergruppen zeigt. pair_code_normalisieren() räumt das
 * auf; ginge das schief, fände der Server den Code nicht. */
console.log('\n  Zustand 2 — die Rückfrage im Web');
const getippt = (geraet.code.slice(0, 3) + ' ' + geraet.code.slice(3)).toLowerCase();
await seite.fill('#koppeln input[name="code"]', getippt);
await weiter();
const k2 = await seite.locator('#koppeln').innerText();
pruefe(k2.includes('Dieses Gerät koppeln?'), `Eingabe „${getippt}" führt zur Rückfrage`);
pruefe(k2.includes('Venu 3S'), 'Sie zeigt Art und Modell des Geräts',
       (k2.match(/Uhr · [^\n·]+/) || [''])[0]);
pruefe(k2.includes(geraet.code.slice(0, 3) + ' ' + geraet.code.slice(3)),
       'und den Code in zwei Dreiergruppen');
pruefe(await seite.locator('#koppeln .knopf-leise').count() === 1,
       'Daneben steht „Abbrechen" leise');
await bild(seite, 'rundlauf-2-rueckfrage');

/* ---- Zustand 3 — warten -------------------------------------------------- */
console.log('\n  Zustand 3 — beansprucht, das Gerät ist am Zug');
await weiter();
pruefe(seite.url().endsWith('t=geraete#koppeln'),
       'Nach dem Beanspruchen wird umgeleitet (GET, kein POST-Ergebnis)',
       seite.url().replace(BASIS, ''));
pruefe((await seite.locator('#koppeln').innerText()).includes('Am Gerät bestätigen'),
       'Die Karte wartet');
pruefe((await seite.locator('.meldung-info').innerText()).includes('Bestätige jetzt am Gerät'),
       'Der Hinweis steht oben auf der Seite');
pruefe(await seite.locator('#kopplung-warten').count() === 1,
       'Der Nachlade-Kasten steht (assets/kopplung.js findet ihn)');
await bild(seite, 'rundlauf-3-warten');

await geh();
pruefe((await seite.locator('#koppeln').innerText()).includes('Am Gerät bestätigen'),
       'Ein Neuladen zeigt denselben Zustand, ohne erneut zu beanspruchen');

/* ---- Das Gerät sagt Ja -------------------------------------------------- */
console.log('\n  Das Gerät sagt Ja — die Seite muss es von selbst merken');
await seite.evaluate(async (g) => {
  await fetch('pair.php', { method: 'POST',
    headers: { 'Content-Type': 'application/json',
               'X-Device-Id': g.device_id, 'X-Api-Key': g.api_key },
    body: JSON.stringify({ aktion: 'bestaetigen', antwort: 'ja' }) });
}, geraet);
const t0 = Date.now();
/* Gewartet wird auf die Vollzugsmeldung, nicht auf die Adresse: Das Ziel des
 * Nachladens ist dieselbe Adresse ohne Fragment — sie ändert sich womöglich
 * gar nicht. Was sich ändert, ist der Inhalt. */
await seite.waitForSelector('.meldung-ok', { timeout: 20000 });
await seite.waitForLoadState('networkidle');
pruefe(true, 'Die Seite lädt von selbst nach (E-S5-53)',
       ((Date.now() - t0) / 1000).toFixed(1) + ' s nach dem Ja');
pruefe((await seite.locator('.meldung-ok').innerText()).includes('jetzt mit deinem Konto verbunden'),
       'Vollzugsmeldung im Ton „ok"');
pruefe(await geraeteZahl() === vorher + 1, 'Das Gerät steht in der Liste',
       `${vorher} -> ${await geraeteZahl()}`);
pruefe((await seite.locator('#geraeteliste').innerText()).includes('Venu 3S'),
       'mit Art und Modell');
pruefe((await seite.locator('#koppeln').innerText()).includes('Code vom Gerät'),
       'Die Karte steht wieder auf Zustand 1');
await bild(seite, 'rundlauf-4-gekoppelt');

/* ---- Was der Bilderlauf sonst misst, hier gleich mit ---------------------- */
const mass = await seite.evaluate(() => ({
  ueberlauf: document.documentElement.scrollWidth - window.innerWidth,
  knoepfe: [...document.querySelectorAll('.knopf')].filter(k => k.offsetParent !== null)
             .map(k => Math.round(k.getBoundingClientRect().height)),
}));
pruefe(mass.ueberlauf <= 0, 'Kein waagerechter Überlauf bei 1280 px', String(mass.ueberlauf));
pruefe(mass.knoepfe.length > 0 && mass.knoepfe.every(h => h === 44),
       'Alle sichtbaren Knöpfe 44 px',
       mass.knoepfe.length + ' Knöpfe, ' + [...new Set(mass.knoepfe)].join('/') + ' px');

/* ---- Aufräumen ----------------------------------------------------------- */
await seite.evaluate(async (g) => {
  await fetch('pair.php', { method: 'POST',
    headers: { 'Content-Type': 'application/json',
               'X-Device-Id': g.device_id, 'X-Api-Key': g.api_key },
    body: JSON.stringify({ aktion: 'trennen' }) });
}, geraet);
await geh();
pruefe(await geraeteZahl() === vorher, 'Das Prüfgerät ist wieder abgemeldet',
       (await geraeteZahl()) + ' Geräte wie vorher');

} finally {
  await browser.close();
}

console.log(`\n  Konsolenfehler (ohne Rauschen): ${fehler.length}`);
fehler.forEach(f => console.log('    ' + f));
console.log(`\n  -> ${n} Erwartungen, ${offen} nicht erfuellt, ${fehler.length} Konsolenfehler`);
process.exit(offen === 0 && fehler.length === 0 ? 0 : 1);
