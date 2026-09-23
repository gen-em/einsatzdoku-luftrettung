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
 *   node tools/proben/kopplung/rundlauf.mjs
 *   node tools/proben/kopplung/rundlauf.mjs --basis https://127.0.0.1:8443 --bilder /tmp/b
 *   node tools/proben/kopplung/rundlauf.mjs --finger          (Fingergeraet, 44 px)
 *   node tools/proben/kopplung/rundlauf.mjs --breite 768      (andere Breite)
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
const FINGER = argv.includes('--finger');
const BREITE = Number(wert('--breite', '1280'));

/* ---- ZWEI SOLLWERTE FUER DIE KNOPFHOEHE (Backlog Nr. 180) ----------------
 *
 * Hier stand bis zum 13.09.2026 ein fest verdrahtetes `=== 44`. Seit
 * Web 15.5.0 gelten ZWEI Sollwerte (E-S8-09, R76): 44 px am Fingergeraet und
 * unter 1024 px, 36 px am Zeigergeraet ab 1024 px. Dieser Rundlauf oeffnet
 * seinen Browser mit 1280 px als Zeigergeraet, mass also zu Recht 36 px und
 * meldete sie als Fehler -- ROT SEIT DEM 06.09.2026, und aufgefallen ist es
 * erst am 13.09.2026, weil ein anderer Punkt dazu fuehrte, ihn zu fahren.
 *
 * Der Fehler lag im Pruefmittel, nicht in der Anwendung; belegt vom
 * Bilderlauf, der beide Sollwerte kennt und ueber 360 Aufnahmen 0 Knoepfe
 * falscher Hoehe meldet. Die Weiche ist jetzt dieselbe wie dort. */
const KNOPF_SOLL = (!FINGER && BREITE >= 1024) ? 36 : 44;
const BROWSER = process.env.CHROMIUM_PFAD || '/opt/pw-browsers/chromium';

let n = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  n++; if (!ok) offen++;
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(58)} ${wert}`);
};

/* ---- WAS NICHT ALS FEHLER ZAEHLT (Backlog Nr. 178) -----------------------
 *
 * Hier stand bis zum 13.09.2026 EIN Ausdruck fuer alle drei Kanaele, geprueft
 * nur gegen den Text:
 *
 *     /tile\.openstreetmap\.org|ERR_ABORTED|Failed to load resource/
 *
 * Die dritte Alternative verwarf damit JEDE Ressourcenmeldung -- gleich
 * welcher Herkunft und gleich welchen Grundes. An acht gebauten Faellen
 * nachgerechnet: 4 von 8 falsch, alle vier verschluckte echte Fehler, darunter
 * ein 404 und ein 500 auf der EIGENEN Basis. Fuer die feuert `requestfailed`
 * nicht (die Anfrage ist auf Transportebene gelungen), sie stehen also nur in
 * der Konsole -- und die wurde weggeworfen. Das Werkzeug konnte einen
 * Serverfehler mitten im Kopplungsrundlauf nicht sehen.
 *
 * DREI KANAELE, DREI REGELN. Die Unterscheidung ist nicht kosmetisch, sie
 * folgt daraus, was der jeweilige Kanal ueberhaupt liefert:
 *
 *   console       Text UND Fundstelle. Rauschen ist eine fremde Quelle am
 *                 Namen, oder ein Verbindungsfehler auf einer NACHWEISBAR
 *                 fremden Fundstelle. Alles andere zaehlt -- auch ein
 *                 Statuscode, auch auf der eigenen Basis.
 *   requestfailed Die Adresse ist immer dabei, ein Statuscode nie. Rauschen
 *                 ist eine fremde Quelle -- und ERR_ABORTED auf JEDER
 *                 Herkunft: Dieser Rundlauf navigiert mehrfach, und eine
 *                 laufende Anfrage, die von der naechsten Navigation
 *                 ueberholt wird, meldet genau das (gemessen im Nachtrag zu
 *                 Nr. 176: 14 solche Abbrueche in der ersten Ladung nach der
 *                 Anmeldung, 0 in den folgenden).
 *   pageerror     NIE Rauschen. Eine nicht abgefangene Ausnahme im eigenen
 *                 Code ist immer ein Fehler.
 *
 * Die Klasse "Statuscode der Seite selbst", die der Bilderlauf braucht, fehlt
 * hier mit Absicht: Dieser Rundlauf besucht keine Seite, die absichtlich mit
 * 404 oder 409 antwortet.
 *
 * SELBSTPROBE: `node tools/proben/kopplung/rundlauf.mjs --selbstprobe` haelt
 * die Regeln gegen dreizehn gebaute Faelle, ohne Browser und ohne Server. Jeder
 * Fall traegt eine Regel -- wer eine Zeile loescht, muss die Probe rot sehen
 * (Rezept in der LIESMICH). */
const FREMDE_QUELLEN =
  /tile\.|openstreetmap|opentopomap|arcgisonline|photon\.komoot/i;
const VERBINDUNGSCODES =
  /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|ERR_INTERNET_DISCONNECTED|ERR_CONNECTION_RESET|ERR_CONNECTION_CLOSED|ERR_ABORTED/i;

/* Drei Antworten, nicht zwei — eine Fundstelle, die sich nicht zuordnen
 * laesst, ist keine fremde und wird gezaehlt (dieselbe Entscheidung wie in
 * tools/screenshots/aufnehmen.mjs). */
function herkunft(ort) {
  if (!ort) return 'keine';
  let o;
  try { o = new URL(ort).origin; } catch { return 'keine'; }
  if (!o || o === 'null') return 'keine';
  return o === new URL(BASIS).origin ? 'eigen' : 'fremd';
}

const fehler = [];
function konsolenrauschen(text, ort) {
  if (FREMDE_QUELLEN.test(text) || FREMDE_QUELLEN.test(ort)) return true;
  if (VERBINDUNGSCODES.test(text) && herkunft(ort) === 'fremd') return true;
  return false;
}
function abrufrauschen(url, code) {
  if (FREMDE_QUELLEN.test(url)) return true;
  if (/ERR_ABORTED/i.test(code)) return true;
  return false;
}

if (BILDER) { mkdirSync(BILDER, { recursive: true }); }
const bild = async (seite, name) => {
  if (BILDER) { await seite.screenshot({ path: `${BILDER}/${name}.png`, fullPage: true }); }
};

/* ---- Selbstprobe der Rauschregeln ---------------------------------------
 *
 * Dreizehn Faelle, je mit Sollwert; jeder traegt EINE Regel. Die Faelle 5 und 6
 * sind der Grund fuer Nr. 178: ein 500er und ein 404 auf der eigenen Basis,
 * beide vorher verschluckt. Fall 8 haelt die Gegenrichtung fest -- ein
 * abgeraeumter Abruf bleibt Rauschen, sonst faerbt jede Navigation den Lauf
 * rot. Laeuft ohne Browser und ohne Server; das Playwright-Modul muss
 * vorhanden sein, weil die Datei es am Kopf laedt. */
const SELBSTPROBE = [
  { nr:  1, kanal: 'console', soll: 'rauschen', grund: 'Kachelserver, Verbindung zurueckgesetzt',
    a: 'Failed to load resource: net::ERR_CONNECTION_RESET', b: 'https://tile.openstreetmap.org/12/2/1.png' },
  { nr:  2, kanal: 'console', soll: 'rauschen', grund: 'Kachelserver OHNE Verbindungscode (HTTP 500)',
    a: 'Failed to load resource: the server responded with a status of 500 (Internal Server Error)',
    b: 'https://tile.openstreetmap.org/12/2/1.png' },
  { nr:  3, kanal: 'console', soll: 'rauschen', grund: 'fremde Herkunft ausserhalb der Gastgeberliste',
    a: 'Failed to load resource: net::ERR_CONNECTION_RESET', b: 'https://beispiel.example/x.js' },
  { nr:  4, kanal: 'console', soll: 'fehler',   grund: 'eigener Server, Symbol zurueckgesetzt',
    a: 'Failed to load resource: net::ERR_CONNECTION_RESET', b: 'EIGEN/assets/images/symbole/haus.svg' },
  { nr:  5, kanal: 'console', soll: 'fehler',   grund: 'eigener Server, HTTP 500 (Nr. 178)',
    a: 'Failed to load resource: the server responded with a status of 500 (Internal Server Error)',
    b: 'EIGEN/pair.php' },
  { nr:  6, kanal: 'console', soll: 'fehler',   grund: 'eigener Server, HTTP 404 (Nr. 178)',
    a: 'Failed to load resource: the server responded with a status of 404 (Not Found)',
    b: 'EIGEN/assets/style.css' },
  { nr:  7, kanal: 'console', soll: 'fehler',   grund: 'Fundstelle nicht zuordenbar — wird gezaehlt',
    a: 'Failed to load resource: net::ERR_CONNECTION_RESET', b: '' },
  { nr:  8, kanal: 'abruf',   soll: 'rauschen', grund: 'von der Navigation ueberholt (ERR_ABORTED)',
    a: 'EIGEN/api/day.php?d=32', b: 'net::ERR_ABORTED' },
  { nr:  9, kanal: 'abruf',   soll: 'fehler',   grund: 'eigener Server, Verbindung zurueckgesetzt',
    a: 'EIGEN/pair.php', b: 'net::ERR_CONNECTION_RESET' },
  { nr: 10, kanal: 'abruf',   soll: 'rauschen', grund: 'Kachelserver, Verbindung zurueckgesetzt',
    a: 'https://tile.openstreetmap.org/12/2/1.png', b: 'net::ERR_CONNECTION_RESET' },
  { nr: 11, kanal: 'ausnahme', soll: 'fehler',  grund: 'nicht abgefangene Ausnahme — nie Rauschen',
    a: 'TypeError: x is not a function', b: '' },
  /* 12 und 13 halten die beiden Zweige von herkunft(), die Fall 7 NICHT
   * beruehrt: Er kommt mit leerer Fundstelle schon an der ersten Zeile heraus.
   * Gemessen: Ohne diese zwei bleibt die Probe gruen, wenn man 'keine' auf
   * 'fremd' stellt. */
  { nr: 12, kanal: 'console', soll: 'fehler',   grund: 'Fundstelle ist keine Adresse (<anonymous>)',
    a: 'Failed to load resource: net::ERR_CONNECTION_RESET', b: '<anonymous>' },
  { nr: 13, kanal: 'console', soll: 'fehler',   grund: 'undurchsichtige Herkunft (data:) — URL.origin sagt "null"',
    a: 'Failed to load resource: net::ERR_CONNECTION_CLOSED', b: 'data:text/html,<p>x' },
];

if (argv.includes('--selbstprobe')) {
  let erfuellt = 0;
  console.log('Selbstprobe der Rauschregeln — Basis ' + BASIS + '\n');
  for (const f of SELBSTPROBE) {
    const a = f.a.replace('EIGEN', BASIS);
    const b = f.b.replace('EIGEN', BASIS);
    const rauschen = f.kanal === 'console'  ? konsolenrauschen(a, b)
                   : f.kanal === 'abruf'    ? abrufrauschen(a, b)
                   : false;                  /* ausnahme: nie Rauschen */
    const ist = rauschen ? 'rauschen' : 'fehler';
    const ok = ist === f.soll;
    if (ok) erfuellt++;
    console.log(`${ok ? ' ok ' : 'FEHL'}  ${String(f.nr).padStart(2)}  ${f.kanal.padEnd(8)} `
      + `soll ${f.soll.padEnd(8)} ist ${ist.padEnd(8)}  ${f.grund}`);
  }
  console.log(`\n${erfuellt} von ${SELBSTPROBE.length} Faellen erwartungsgemaess, `
    + `${SELBSTPROBE.length - erfuellt} nicht.`);
  process.exit(erfuellt === SELBSTPROBE.length ? 0 : 1);
}

console.log(`Kopplungsrundlauf gegen ${BASIS} (Konto ${DEMO.email})`);

const browser = await chromium.launch({ executablePath: BROWSER });
const ctx = await browser.newContext({ ignoreHTTPSErrors: true,
  viewport: { width: BREITE, height: 900 }, hasTouch: FINGER });
const seite = await ctx.newPage();
const cdp = await ctx.newCDPSession(seite);
seite.on('console', m => {
  if (m.type() !== 'error') { return; }
  const ort = (m.location && m.location().url) || '';
  if (konsolenrauschen(m.text(), ort)) { return; }
  fehler.push('console: ' + m.text() + (ort ? '  [' + ort.replace(BASIS, '') + ']' : ''));
});
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));
seite.on('requestfailed', r => {
  const code = r.failure()?.errorText || '';
  if (abrufrauschen(r.url(), code)) { return; }
  fehler.push('requestfailed: ' + r.url().replace(BASIS, '') + ' — ' + code);
});

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

/* ---- Was der Bilderlauf sonst misst, hier gleich mit ----------------------
 *
 * DIE EINGABEART HAELT NICHT VON SELBST (Fund aus S8/AP7, ausfuehrlich in
 * tools/screenshots/aufnehmen.mjs): `hasTouch` am Kontext setzt sie richtig,
 * aber ein Vollseiten-Screenshot -- und dieser Rundlauf macht mehrere --
 * schiebt sie zurueck auf `hover:hover`/`pointer:fine`. Deshalb wird sie vor
 * der Messung erneut gesendet. NUR im Fingerlauf: Am Zeigergeraet ist
 * `{enabled:false}` nicht das Gegenteil, sondern kippt beide Merkmale auf
 * `none`/`coarse` und misst denselben Fehler spiegelverkehrt. */
if (FINGER) {
  await cdp.send('Emulation.setTouchEmulationEnabled',
    { enabled: true, maxTouchPoints: 5 }).catch(() => {});
}
const mass = await seite.evaluate(() => ({
  ueberlauf: document.documentElement.scrollWidth - window.innerWidth,
  knoepfe: [...document.querySelectorAll('.knopf')].filter(k => k.offsetParent !== null)
             .map(k => Math.round(k.getBoundingClientRect().height)),
}));
pruefe(mass.ueberlauf <= 0, `Kein waagerechter Überlauf bei ${BREITE} px`, String(mass.ueberlauf));
pruefe(mass.knoepfe.length > 0 && mass.knoepfe.every(h => h === KNOPF_SOLL),
       `Alle sichtbaren Knöpfe ${KNOPF_SOLL} px (${FINGER ? 'Finger' : 'Zeiger'}, ${BREITE} px)`,
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
