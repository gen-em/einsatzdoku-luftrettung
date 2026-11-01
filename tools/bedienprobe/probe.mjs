/* Klickprobe — das Pruefmittel, das bedient statt fotografiert.
 * ===========================================================================
 *
 * WARUM ES SIE GIBT. Das Projekt hatte einmal vier Pruefmittel im Browser, und
 * keines davon hat je ein Element BEDIENT: Der Bilderlauf fotografiert, die
 * Vollstaendigkeit liest das Stylesheet, die Linkprobe folgt Adressen, die
 * Wartungsprobe zaehlt Erwartungen auf einer Seite. Zwei Fehler sind genau
 * dort hindurchgelaufen:
 *
 *   Die Trefferliste der weiteren Rettungsmittel
 *   uebernahm auf `click`, das Feld versteckte die Liste 150 ms nach `blur`.
 *   Wer die Maus laenger haelt, bekommt kein `click`. Auf jedem Bild sah die
 *   Liste richtig aus.
 *
 *   Der Knopf „Diensttage zusammenfuehren" fuehrte auf 404, weil er
 *   `?ziel=` schrieb, wo die Seite `?d=` liest. Auch das sieht man einem Bild
 *   nicht an; gefunden hat es erst die Linkprobe, die dafuer gebaut wurde.
 *
 * Beide waren ein Klick, den niemand getan hat. Diese Probe tut ihn — und
 * nennt je Weg eine ZAHL, nicht ein Urteil.
 *
 * WIE SIE WAECHST. Jedes Arbeitspaket legt seine Wege in eine eigene Datei
 * unter `wege/` (`ap1.mjs`, `ap2.mjs`, …) und exportiert `wege`. Der Laeufer
 * hier kennt keinen einzelnen Weg; er sammelt sie ein, faehrt sie nacheinander
 * und schreibt den Bericht. So bleibt der Zuwachs je Paket eine Datei, und
 * kein Paket muss den Laeufer anfassen.
 *
 * DIE PROBE KENNT ZWEI STAENDE. Ein Weg misst denselben Vorgang vor und nach
 * der Aenderung — „vorher 0 von 3, nachher 3 von 3" ist die Aussage, um die es
 * geht. Ein Selektor, der nur die neue Fassung findet, kann das nicht
 * belegen; die Wege sprechen deshalb beide Fassungen an (alte und neue
 * Klassennamen) und sagen im Bericht, welche sie vorgefunden haben.
 *
 * VORAUSSETZUNG: eine laufende lokale Installation mit Referenzbestand und
 * Demo-Konto — `sh tools/referenzdatensatz/einspielen/lokal_starten.sh`
 * (Aufbau von Null: `lokal_einrichten.sh`).
 *
 * AUFRUF
 *   node tools/bedienprobe/probe.mjs
 *   node tools/bedienprobe/probe.mjs --nur ap1
 *   node tools/bedienprobe/probe.mjs --bilder      (Bild je Weg)
 *   node tools/bedienprobe/probe.mjs --breiten 390,1280 --bilder
 *   node tools/bedienprobe/probe.mjs --breiten 390,1280 --finger --bilder --behalten
 *
 * AUSGABE unter tools/bedienprobe/ausgabe/ (Chromium; die anderen Engines
 * je in `ausgabe-<motor>/`, das in .gitignore steht):
 *   bericht.md, bericht.json  — je Weg Soll, Ist, Urteil. EINGECHECKT: Der
 *                               Bericht des Chromium-Laufs gehoert zum Baum,
 *                               den der Pruefbericht nennt.
 *   bild/<weg>.png            — nur mit --bilder; in .gitignore
 *
 * RUECKGABEWERT != 0, sobald ein Weg sein Soll verfehlt oder gar nicht
 * gefahren werden konnte. Ein Weg, der nicht gefahren werden konnte, gilt als
 * VERFEHLT und nicht als „uebersprungen": Ein Pruefmittel, das sich selbst
 * ueberspringt, meldet Null, ohne gemessen zu haben.
 *
 * GRENZEN. Die Adressabfrage laeuft gegen die Attrappe in `attrappe.mjs`, nicht
 * gegen den echten Dienst; ein Finger ist kein Zeiger — was nur mit
 * Handschuhen auffaellt, faellt hier nicht auf. Der Satz „nur Chromium“
 * gilt nicht:
 * `--motor firefox|webkit` faehrt dieselben Wege in Gecko und WebKit.
 * Was dabei NICHT gleich ist, steht in `tools/motor.mjs` —
 * headless Firefox meldet ohne Voreinstellung „kein Zeiger, kein Hover“
 * und misst damit 44 statt 36 px. Und WebKit hier ist nicht Safari:
 * derselbe Kern, anderer Unterbau (Schriften, Textrasterung).
 */
import { mkdirSync, writeFileSync, readdirSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import * as attrappe from './attrappe.mjs';

/* Hinter einem HTTPS_PROXY braucht Nodes fetch die Variable
 * NODE_USE_ENV_PROXY, und sie wird nur beim Prozessstart gelesen — dieselbe
 * Weiche wie in tools/screenshots/aufnehmen.mjs. */
if ((process.env.HTTPS_PROXY || process.env.https_proxy) && !process.env.NODE_USE_ENV_PROXY) {
  const { spawnSync } = await import('node:child_process');
  const kind = spawnSync(process.execPath, process.argv.slice(1), {
    stdio: 'inherit', env: { ...process.env, NODE_USE_ENV_PROXY: '1' },
  });
  process.exit(kind.status ?? 1);
}

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);
const { motorWahl, starten, codeSchritt, istOertlich } = await import(
  new URL('../motor.mjs', import.meta.url).href);

const HIER = dirname(fileURLToPath(import.meta.url));

const argv = process.argv.slice(2);
const flag = (n) => argv.includes(n);
const wert = (n, s) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : s; };

const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const DEMO   = { email: wert('--demo',  'demo@gen-em.org'),  pw: wert('--demo-pw',  'nadokudemo0815') };
const ADMIN  = { email: wert('--admin', 'admin@example.invalid'), pw: wert('--admin-pw', 'pruefstandzugang2026') };
const FILTER = (wert('--nur', '') || '').split(',').filter(Boolean);
const MOTOR  = motorWahl(argv);
/* Je Engine ein Ordner, wie im Bilderlauf: Der Prüfstand fährt
 * die Probe dreimal hintereinander, und ein gemeinsamer Ordner hielte nur
 * den Bericht des letzten Laufs. */
const AUSGABE = join(HIER, MOTOR === 'chromium' ? 'ausgabe' : `ausgabe-${MOTOR}`);
const BILDER = flag('--bilder');
const MARKE  = wert('--marke', '');     // freie Beschriftung des Laufs im Bericht

/* ---- Die Uhr des Demo-Kontos ablesen -------------------------------------
 *
 * WARUM DAS HIERHER GEHOERT. Die Sitzungswache unten faengt den Fall ab, dass
 * der Demo-Reset eine SITZUNG beendet: Sie meldet sich neu an und faehrt die
 * Adresse noch einmal. Sie faengt NICHT den Fall ab, dass der Reset den
 * BESTAND wegnimmt, aus dem ein Weg seine Adresse erst noch holen muss —
 * dann steht in `adresse` nichts, und die Meldung lautet „Adresse nicht
 * aufgelöst (Bestand leer?)". Sie zeigt auf die Daten und nicht auf die
 * Ursache, also genau dorthin, wo nichts ist.
 *
 * Gemessen am 14.09.2026: Der Reset lief um 14:01:15, der Lauf begann in
 * derselben Minute — **37 von 43**, sechs Wege „Bestand leer?" und „Kein
 * Diensttag im Bestand". Derselbe Lauf sechs Minuten spaeter: 43 von 43.
 *
 * Diese Datei kann den Fall nicht verhindern, ohne jeden einzelnen Weg
 * umzubauen. Sie kann ihn aber BENENNEN: Sie liest die Marke vor und nach
 * dem Lauf und sagt im Bericht, ob ein Reset hineingefallen ist. Aus einer
 * irrefuehrenden Meldung wird damit eine Auskunft. */
async function demoUhr() {
  const { spawnSync: lauf } = await import('node:child_process');
  const r = lauf('php', ['-r',
    'require ' + JSON.stringify(join(HIER, '..', '..', 'server', 'db.php')) + ';'
    + 'require_once ' + JSON.stringify(join(HIER, '..', '..', 'server', 'demo_lib.php')) + ';'
    + 'echo demo_letzter_reset();'], { encoding: 'utf8' });
  const n = Number((r.stdout || '').trim());
  return Number.isFinite(n) && n > 0 ? n : null;
}
const DEMO_RESET_VOR = await demoUhr();

/* BREITE UND EINGABEART, weil ein Bild ohne beides nichts belegt. Die Abnahme
 * einer Vorschlagsliste lautet „zwei Breiten, beide Bedienhoehen" — 44 px am
 * Finger, 36 px am Zeigergeraet ab 1024 px. Dieselbe Weiche wie beim
 * Bilderlauf, und derselbe Fund dahinter: `hasTouch` allein reicht nicht,
 * `Emulation.setTouchEmulationEnabled` muss ueber CDP nachgesetzt werden, und
 * im Zeigerlauf wird gar nichts gesendet — `{enabled:false}` ist NICHT das
 * Gegenteil von `{enabled:true}` und kippt die Merkmale auf `none`/`coarse`
 * (siehe tools/screenshots/LIESMICH.md). */
const BREITEN = (wert('--breiten', '1280') || '1280')
  .split(',').map(b => parseInt(b, 10)).filter(b => b > 0);
const FINGER = flag('--finger');
const hoeheFuer = (b) => (FINGER || b < 1024) ? 44 : 36;

/* EINE ANMELDUNG, MEHRERE BREITEN — und das ist keine Bequemlichkeit.
 *
 * Das Demo-Konto traegt eine Mengenbremse: 20 Anmeldungen je
 * Fenster und Adresse, danach ist es fuer eine Stunde gesperrt. Vier Laeufe
 * mit je zwei Anmeldungen (demo und admin) haben sie gefuellt, und der
 * fuenfte Lauf endete mit „Anmeldung gescheitert" — was wie ein kaputter
 * Pruefstand aussieht und keiner war. Also: je Prozess EINE Anmeldung je
 * Rolle, und die Rolle wird erst geholt, wenn ein Weg sie braucht; zwischen
 * den Breiten aendert sich nur die Fenstergroesse. Dasselbe Verfahren wie im
 * Bilderlauf, dort aus demselben Grund (Sitzung halten). */

/* ---- Wege einsammeln ------------------------------------------------------
 *
 * Ein Paket legt `wege/<paket>.mjs` an und exportiert `wege`. Fehlt der
 * Export oder ist er leer, ist das ein Fehler und kein Achselzucken: Eine
 * Datei, die dasteht und nichts beitraegt, meldet sonst stillschweigend
 * „alles gefahren". */
const dateien = readdirSync(join(HIER, 'wege')).filter(d => d.endsWith('.mjs')).sort();
let ALLE = [];
for (const d of dateien) {
  const m = await import(join(HIER, 'wege', d));
  if (!Array.isArray(m.wege) || !m.wege.length) {
    console.error(`wege/${d} exportiert keine Wege — Abbruch.`);
    process.exit(2);
  }
  ALLE = ALLE.concat(m.wege);
}
const WEGE = FILTER.length
  ? ALLE.filter(w => FILTER.some(f => w.name.startsWith(f) || w.paket === f))
  : ALLE;
if (!WEGE.length) { console.error('Kein Weg ausgewählt.'); process.exit(2); }

/* ---- Browser und Anmeldung ------------------------------------------------ */
const browser = await starten(PW, MOTOR, { finger: FINGER });

async function anmelden(rolle) {
  const konto = rolle === 'admin' ? ADMIN : DEMO;
  const kontext = await browser.newContext({
    ignoreHTTPSErrors: istOertlich(BASIS), viewport: { width: BREITEN[0], height: 900 },
    hasTouch: FINGER,
  });
  /* DER WARTUNGSLAUF BLEIBT AUS: Er packt und dünnt im
     Browser aus, sobald eine Seite ihn anstößt (Startseite, Reiter Backup),
     und das Ausdünnen ändert die Punkte — mitten in einem Vergleich wäre das
     eine Abweichung, die keine ist. Die Antwort sagt „nichts fällig" — ein
     Abbruch schriebe einen Fehler in die Konsole. Gemessen wird er in seiner
     eigenen Probe. */
  await kontext.route('**/api/wartungslauf.php', (r) => r.fulfill({ status: 200,
    contentType: 'application/json', body: '{"ok":true,"spuren":[],"aufzeichnungen":0,"diensttage":0}' }));
  /* Die Attrappe VOR der ersten Seite setzen: Eine Anfrage, die vor der Route
   * hinausgeht, laeuft in die Egress-Sperre und kostet die Zeitgrenze. */
  for (const muster of attrappe.MUSTER) { await kontext.route(muster, attrappe.route); }
  /* Kartenkacheln bleiben aussen vor — die Klickprobe braucht keine Karte,
   * und ein grauer Kartenrahmen aendert an keinem Klick etwas. Geblockt statt
   * geladen, damit kein Weg auf einen Kachelserver wartet. */
  await kontext.route('**/tile.openstreetmap.org/**', r => r.abort());

  const seite = await kontext.newPage();
  const fehler = [];
  seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));
  seite.on('console', m => {
    if (m.type() !== 'error') { return; }
    const t = m.text();
    if (/tile\.|openstreetmap|ERR_/i.test(t)) { return; }
    fehler.push(t);
  });

  await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', konto.email);
  await seite.fill('input[name="password"]', konto.pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('#loginform button[type="submit"]'),
  ]);
  /* DER CODE-SCHRITT DES ZWEITFAKTORS. Das Pruefkonto
   * `admin@example.invalid` ist eine BetreiberIn mit Zweitfaktor; nach dem
   * Passwort fragt `login.php` nach dem Code. `codeSchritt()` (motor.mjs)
   * geht ihn — und meldet das Einrichtungstor als Scheitern: Dessen Adresse
   * enthaelt `login.php` nicht, und ohne diese Pruefung liefe jeder Admin-Weg
   * auf `zweitfaktor.php` statt auf seiner Seite. */
  const zf = await codeSchritt(seite);
  if (!zf.ok) {
    throw new Error(`Anmeldung als ${konto.email} gescheitert — ${zf.meldung}`);
  }
  if (seite.url().includes('login.php')) {
    throw new Error(`Anmeldung als ${konto.email} gescheitert. Läuft die lokale `
      + 'Installation (sh tools/referenzdatensatz/einspielen/lokal_starten.sh)? '
      + 'Und steht die Mengenbremse des Demo-Kontos voll? Sie lässt 20 '
      + 'Anmeldungen je Fenster und Adresse zu (E-P1-20) und sperrt danach '
      + 'für eine Stunde — mehrere Läufe hintereinander füllen sie.');
  }
  /* Die Eingabeart haelt nicht von selbst: `hasTouch` am
   * Kontext setzt sie, ein Vollseiten-Screenshot verliert sie wieder. Sie
   * wird deshalb vor jeder Breite erneut gesendet — und NUR im Fingerlauf:
   * `{enabled:false}` ist nicht das Gegenteil von `{enabled:true}` und kippt
   * an einem Zeigerkontext die Merkmale auf `none`/`coarse`. */
  /* NUR CHROMIUM HAT CDP. Die Probe faehrt auch Firefox und WebKit;
   * dort wirft `newCDPSession`. Gebraucht wird die Sitzung ohnehin
   * nur im Fingerlauf — und nur in Chromium, weil nur Chromium die
   * Eingabeart am Vollseiten-Screenshot verliert (gemessen: Firefox und
   * WebKit behalten pointer:coarse darueber hinweg). */
  const cdp = (FINGER && MOTOR === 'chromium') ? await kontext.newCDPSession(seite) : null;
  const eingabeart = async () => {
    if (!FINGER || !cdp) { return; }
    await cdp.send('Emulation.setTouchEmulationEnabled',
      { enabled: true, maxTouchPoints: 5 }).catch(() => {});
  };
  await eingabeart();
  return { kontext, seite, fehler, rolle, eingabeart };
}

/* Faul: Eine Rolle wird angemeldet, wenn der erste Weg sie verlangt. Die Gruppe
 * `AP1` braucht nur `demo` — eine Anmeldung als `admin` waere ein Zug aus der
 * Mengenbremse fuer nichts. */
const rollenSpeicher = {};
async function rolleHolen(name) {
  if (!rollenSpeicher[name]) { rollenSpeicher[name] = await anmelden(name); }
  return rollenSpeicher[name];
}
const rollen = { demo: await rolleHolen('demo') };

/* EINE ZEITGRENZE JE WEG. Am 20.10.2026 hing die Probe in
 * WebKit nach `betrieb-updates-neuladen` 43 Minuten, ohne eine Zeile zu
 * schreiben; erst ein Abbruch von Hand gab den Prüfstand frei, und 16 Wege
 * galten danach als verfehlt, ohne gefahren zu sein. Ein Weg braucht sonst
 * Sekunden, der längste wartet 90 s auf eine Adresse. Wer die Grenze reißt,
 * ist verfehlt und benannt — und seine Rolle wird VERWORFEN: Der hängende
 * Aufruf läuft auf ihrer Seite weiter, und der nächste Weg derselben Rolle
 * bekäme sonst eine Seite, die ein anderer gerade bedient. */
/* `WEG_FRIST_MS` nur für die Gegenprobe: mit 1000 muss jeder Weg an der
 * Grenze scheitern und die Probe trotzdem sauber enden. */
const WEG_FRIST = Number(process.env.WEG_FRIST_MS) || 300000;
/* Das Schließen bekommt eine eigene Frist: Es läuft außerhalb der
 * Zeitgrenze des Wegs, und ein Kontext, dessen Seite nicht mehr antwortet,
 * hielte sonst die ganze Probe an. Gemessen schloss er in 50 ms. */
async function rolleVerwerfen(name) {
  const r = rollenSpeicher[name];
  delete rollenSpeicher[name];
  if (r) {
    await Promise.race([r.kontext.close().catch(() => {}),
                        new Promise((ok) => setTimeout(ok, 10000))]);
  }
  if (name === 'demo') { rollen.demo = await rolleHolen('demo'); }
}

/* EIN LEBENSZEICHEN VOR JEDEM WEG — NUR IN WEBKIT.
 *
 * In den vollen Läufen hing WebKit dreimal am ERSTEN Aufruf auf der Seite
 * einer Rolle, die untätig gestanden hatte, während ein anderer Kontext
 * arbeitete: `setViewportSize` (`da-faehigkeiten-nach-typ` nach dem
 * Admin-Block), wohl ebenso in `mr-oeffner-markiert` (nach dem
 * eigenen Kontext von `einstellungen-profil-rueckweg`; nicht protokolliert)
 * und am 25.10.2026 ein `goto` (wieder `da-faehigkeiten-nach-typ`).
 * Das Aufrufprotokoll dieses Laufs zeigt, dass es nicht an der Anwendung
 * lag: Die Navigation sagte 30 s lang nicht zu, und der PHP-Server nahm in
 * der Zeit KEINE Verbindung an — die Anfrage verließ WebKit nie. Danach kam
 * ein `evaluate` auf derselben Seite 120 s nicht zurück. Erst mit neuem
 * Kontext lief es wieder, und der nächste Weg war verfehlt, ohne etwas
 * falsch gemacht zu haben. Ein offener `beforeunload`-Dialog war es nicht
 * (nachgestellt: ohne Dialog-Horcher navigieren alle drei Engines, und
 * diese Probe hat keinen); der stille Wartungslauf steht hier ohnehin still.
 *
 * Reparieren lässt sich die Seite nicht, erkennen schon: Ein `evaluate`
 * kehrt auf einer gesunden Seite in Millisekunden zurück. Kommt 10 s nichts,
 * wird die Rolle neu angemeldet, BEVOR der Weg beginnt, und das steht als
 * Zeile `WebKit-Neuanmeldung:` im Protokoll; `pruefen.sh` zählt sie mit den
 * Wiederholungen eines Seitenwechsels (`tools/motor.mjs`). Wirft das `evaluate`, navigiert die Seite
 * gerade; dann wird einmal auf `load` gewartet und neu gefragt.
 *
 * NICHT IN CHROMIUM UND FIREFOX: Dort ist es nie aufgetreten, und eine
 * stille Neuanmeldung dort hätte keinen Befund, der sie begründet. */
const LEBENSZEICHEN_MS = 10000;
async function lebtDieSeite(seite) {
  for (let versuch = 0; versuch < 2; versuch++) {
    let wecker;
    const frist = new Promise((ok) => { wecker = setTimeout(() => ok('stumm'), LEBENSZEICHEN_MS); });
    const antwort = seite.evaluate(() => 1).then(() => 'da', () => 'navigiert');
    const stand = await Promise.race([antwort, frist]);
    clearTimeout(wecker);
    if (stand === 'da') { return true; }
    if (stand === 'stumm') { return false; }
    await seite.waitForLoadState('load', { timeout: 5000 }).catch(() => {});
  }
  return false;
}

/* ---- Adressen, die zum Bestand gehoeren ----------------------------------
 *
 * Dieselbe Regel wie beim Bilderlauf: Kennungen gehoeren zu EINER
 * Installation und stehen nicht in einer eingecheckten Datei. Sie werden ueber
 * dieselben Wege geholt, die eine NutzerIn ginge — und ein nicht aufgeloester
 * Wert ist `null` und laesst den Weg scheitern, statt ihn auf der falschen
 * Seite fahren zu lassen. */
async function kennungen() {
  const s = rollen.demo.seite;
  await s.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
  await s.waitForSelector('#missions tbody tr, .kachel', { timeout: 30000 }).catch(() => {});
  /* GEWARTET WIRD AUF `currentDayId`, NICHT AUF EINE FESTE ZEIT.
   * `loadDay()` setzt die Kennung erst, wenn die Antwort von `api/day.php`
   * da ist (index.php 739); die 300 ms davor waren geraten. Unter Last kam
   * die Antwort spaeter, `k.kennung.tag` blieb `null`, und JEDER Weg meldete
   * „Kein Diensttag im Bestand" — zweimal an einem Nachmittag gesehen und
   * beide Male fuer eine Stoerung gehalten. */
  await s.waitForFunction(
    () => typeof currentDayId !== 'undefined' && currentDayId !== null,
    null, { timeout: 30000 }).catch(() => {});
  const k = await s.evaluate(() => ({
    tag: (typeof currentDayId !== 'undefined' && currentDayId) || null,
    einsatz: (typeof dayMissions !== 'undefined' && dayMissions[0]) ? dayMissions[0].id : null,
  })).catch(() => ({ tag: null, einsatz: null }));
  return {
    tag: k.tag,
    einsatz: k.einsatz,
    formular: k.einsatz ? `${BASIS}/einsatz_form.php?id=${k.einsatz}` : null,
    tagesuebersicht: k.tag ? `${BASIS}/index.php?d=${k.tag}` : null,
  };
}
const KENNUNG = await kennungen();

/* ---- Werkzeugkasten fuer die Wege ---------------------------------------- */
/* Ein Weg bekommt genau diese Handreichungen und sonst nichts. Was hier nicht
 * steht, gehoert in den Weg — und was drei Wege brauchen, gehoert hierher. */
/**
 * EINE VERLORENE SITZUNG WIEDER AUFBAUEN.
 *
 * Nur die Anmeldung, nicht der ganze Kontext: Kontext und Seite bleiben
 * stehen, damit Eingabeart, Attrappe und Kachelsperre erhalten bleiben — sie
 * haengen am Kontext und waeren mit einem neuen weg.
 */
async function neuAnmelden(r) {
  const konto = r.rolle === 'admin' ? ADMIN : DEMO;
  await r.seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await r.seite.fill('input[name="email"]', konto.email);
  await r.seite.fill('input[name="password"]', konto.pw);
  await Promise.all([
    r.seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    r.seite.click('#loginform button[type="submit"]'),
  ]);
  /* Derselbe Code-Schritt wie in `anmelden()`. Ein gescheiterter wirft
   * hier, statt dem Aufrufer eine Seite zu hinterlassen, die er nach der
   * Adresse fuer angemeldet hielte (das Einrichtungstor). */
  const zf = await codeSchritt(r.seite);
  if (!zf.ok) {
    throw new Error(`Neuanmeldung als ${konto.email} gescheitert — ${zf.meldung}`);
  }
}

async function kasten(rolle, weg, breite) {
  let r = await rolleHolen(rolle || 'demo');
  if (MOTOR === 'webkit' && !(await lebtDieSeite(r.seite))) {
    console.error(`WebKit-Neuanmeldung: Seite der Rolle ${rolle || 'demo'} antwortete `
      + `${LEBENSZEICHEN_MS / 1000} s nicht (vor ${weg.name}) — neu angemeldet (Nr. 393)`);
    await rolleVerwerfen(rolle || 'demo');
    r = await rolleHolen(rolle || 'demo');
  }
  const BEDIENHOEHE = hoeheFuer(breite);
  /* NUR WENN DIE GRÖSSE NICHT SCHON STIMMT. In WebKit kehrt
   * `setViewportSize` nicht zurück, wenn die Seite gerade neu lädt — gemessen
   * am 21.10.2026: Die Demo-Seite stand im Hintergrund auf der Startseite,
   * `betrieb-updates-neuladen` schaltete die Wartung ein und aus, die
   * Startseite lud sich selbst neu, und der nächste Weg der Demo-Rolle hing
   * 64 ms nach Beginn des Aufrufs für immer (`da-faehigkeiten-nach-typ`,
   * zweimal im vollen Lauf). Im Prüfstand ist es eine Breite, und die stimmt
   * seit dem Anlegen des Kontexts; der Aufruf war dort nie nötig.
   * Seit dem 25.10.2026 ist klar, dass das Neuladen wohl nur die Wirkung
   * war: Am selben Weg war die Seite in fünf von neun vollen Läufen schon
   * VOR dem Weg stumm, ohne einen einzigen Aufruf (`lebtDieSeite()` oben). */
  const jetzt = r.seite.viewportSize();
  if (!jetzt || jetzt.width !== breite || jetzt.height !== 900) {
    await r.seite.setViewportSize({ width: breite, height: 900 });
  }
  await r.eingabeart();
  return {
    seite: r.seite,
    basis: BASIS,
    kennung: KENNUNG,
    attrappe,
    konsolenfehler: () => r.fehler.slice(),

    /**
     * Seite oeffnen und auf das Stylesheet warten (sonst misst man ungestaltet).
     *
     * MIT SITZUNGSWACHE — dieselbe, die der Bilderlauf hat. Das
     * Demo-Konto setzt sich 30 Minuten nach der ersten Aenderung zurueck
     * (`DEMO_RESET_SEKUNDEN`, demo_lib.php) und erhoeht dabei die
     * Sitzungs-Epoche; `auth_guard.php` beendet daraufhin jede offene Sitzung
     * dieses Kontos — auch die der Probe. Ein voller Lauf ueber zwei Breiten
     * dauert laenger als das Fenster und traf den Reset am 09.09.2026 mitten
     * im Lauf: **12 von 76 Wegen** verfehlt, gemeldet als „Kein Standort in
     * der Liste" und „waitForSelector timeout". Beide Meldungen zeigen auf
     * den Bestand und nicht auf die Ursache — genau die Sorte Fehlmeldung,
     * die einen halben Prueflauf kostet.
     *
     * Die Wache meldet sich EINMAL neu an und faehrt die Adresse noch einmal
     * an. Gelingt auch das nicht, wirft sie — dann ist es kein Reset, sondern
     * ein echter Fehlschlag, und der gehoert gemeldet.
     */
    async gehZu(adresse) {
      if (!adresse) {
        throw new Error('Adresse nicht aufgelöst — entweder ist der Bestand '
          + 'wirklich leer, oder das Demo-Konto hat sich mitten im Lauf '
          + 'zurückgesetzt (30 min nach der ersten Änderung, demo_lib.php). Der Bericht sagt am '
          + 'Ende, welcher der beiden Fälle vorliegt.');
      }
      await r.seite.goto(adresse, { waitUntil: 'domcontentloaded' });
      if (r.seite.url().includes('login.php') && !adresse.includes('login.php')) {
        await neuAnmelden(r);
        await r.seite.goto(adresse, { waitUntil: 'domcontentloaded' });
        if (r.seite.url().includes('login.php')) {
          throw new Error('Sitzung verloren und Neuanmeldung gescheitert — '
            + 'das Demo-Konto setzt sich 30 Minuten nach der ersten Änderung zurück (demo_lib.php)');
        }
      }
      await r.seite.waitForFunction(
        () => getComputedStyle(document.documentElement).getPropertyValue('--knopf').trim() !== '',
        null, { timeout: 5000 });
    },

    /**
     * DER KLICK MIT GEHALTENER MAUS — der Kern dieser Probe.
     *
     * `locator.click()` von Playwright haelt die Taste rund 10 ms; genau
     * deshalb faellt der Fehler der Trefferliste damit NICHT auf. Hier wird die Folge von Hand
     * gefahren: zeigen, druecken, `ms` warten, loslassen. 300 ms sind der
     * Wert aus der Abnahme — laenger als die 150 ms des Blur-Aufschubs und
     * kuerzer als ein Klick, den jemand fuer absichtlich haelt.
     */
    async haltenUndKlicken(element, ms = 300) {
      const kasten = await element.boundingBox();
      if (!kasten) { throw new Error('Element hat keine Fläche (unsichtbar?)'); }
      const x = kasten.x + kasten.width / 2;
      const y = kasten.y + kasten.height / 2;
      await r.seite.mouse.move(x, y);
      await r.seite.mouse.down();
      await r.seite.waitForTimeout(ms);
      await r.seite.mouse.up();
      await r.seite.waitForTimeout(120);
    },

    /**
     * Zeichen fuer Zeichen tippen, damit `input` je Zeichen faellt.
     *
     * `fill('')` IST KEIN NEUTRALES LEEREN, und das hat diese Probe einen
     * halben Lauf gekostet: Playwright raeumt das Feld ueber die Tastatur —
     * an einem BEREITS LEEREN Feld kommt trotzdem ein `Delete` an. Das
     * Chipfeld der weiteren Rettungsmittel hoert genau darauf: Ruecktaste
     * oder Entfernen im leeren Feld nimmt den letzten Chip zurueck (Web
     * 7.0.0, gewollt). Der Lauf mass daraufhin „1 → 1 Chips" und hielt die
     * Uebernahme faelschlich fuer gescheitert — sie hatte funktioniert, nur
     * war vorher ein Chip verschwunden, den das Werkzeug selbst geloescht
     * hatte. Geleert wird deshalb nur, was nicht schon leer ist.
     */
    async tippe(auswahl, text) {
      const e = r.seite.locator(auswahl).first();
      await e.click();
      if ((await e.inputValue()) !== '') { await e.fill(''); }
      await e.type(text, { delay: 40 });
    },

    /**
     * Werkzeugkasten fuer eine ANDERE Rolle, bei derselben Breite.
     *
     * Geschrieben fuer die Wege in `ap2.mjs`: DERSELBE Kartendialog sass an fuenf Stellen, und
     * die fuenfte — die systemweiten Standorte — stand nur der Verwaltung
     * offen. Die ist gefallen; die Einbauorte stehen seither
     * alle in einer Rolle (`wege/ap2.mjs`), der Werkzeugkasten bleibt fuer
     * den naechsten Weg in einer anderen. Die Rolle wird faul geholt wie
     * sonst auch: Wer sie nie verlangt, meldet sie nie an und zieht nichts
     * aus der Mengenbremse.
     */
    async rolle(name) { return kasten(name, weg, breite); },

    /**
     * ABMELDEN UND NEU ANMELDEN, in derselben Rolle und demselben Kontext.
     * Die Ankuendigung laesst sich je SITZUNG wegklicken und
     * kommt beim naechsten Anmelden wieder — belegen laesst sich
     * das nur mit einer neuen Sitzung im selben Browser. Ein zweiter
     * Kontext waere eine zweite Sitzung, aber keine NEU-Anmeldung: Er haette
     * die alte nie gesehen.
     */
    async neuAnmelden() {
      await r.seite.goto(`${BASIS}/logout.php`, { waitUntil: 'domcontentloaded' });
      await neuAnmelden(r);
    },

    /**
     * Den KONTOSCHALTER der Adresssuche stellen (Profil → Datenschutz).
     *
     * UEBER DAS FORMULAR, NICHT PER SQL. Was hier zu pruefen ist, ist die
     * Wirkung des Schalters — ein `UPDATE users SET adresssuche = 0` haette
     * genau den Weg uebersprungen, der die Frage beantwortet, und „0 Anfragen"
     * waere der Beleg fuer eine Spalte statt fuer einen Schalter.
     *
     * Geklickt wird das LABEL: `.schalter-box` ist ein Ankreuzfeld mit
     * `opacity:0;width:0;height:0` (Stylesheet Z. 1360) — Playwright fasst es
     * nicht an, eine NutzerIn auch nicht.
     *
     * NACHGELESEN WIRD IMMER. Ein Formular, das still nicht gespeichert hat,
     * liefe sonst als gruene Zahl durch.
     */
    async schalterKonto(an) {
      const s = r.seite;
      await s.goto(`${BASIS}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
      const box = s.locator('#k-datenschutz .schalter-box').first();
      if (!(await box.count())) { throw new Error('Karte „Datenschutz" fehlt im Profil'); }
      if (await box.isDisabled()) {
        throw new Error('Der Kontoschalter ist gesperrt — steht der Schalter der '
          + 'Installation aus? (Betrieb → Servereinstellungen → Adresssuche)');
      }
      if ((await box.isChecked()) !== an) {
        await s.locator('#k-datenschutz .schalter-label').first().click();
      }
      await Promise.all([
        s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }),
        s.locator('#k-datenschutz form button[type="submit"]').first().click(),
      ]);
      const jetzt = await s.locator('#k-datenschutz .schalter-box').first()
        .isChecked().catch(() => null);
      if (jetzt !== an) {
        throw new Error(`Kontoschalter steht nach dem Speichern auf ${jetzt}, `
          + `verlangt war ${an}`);
      }
    },

    /**
     * Den INSTALLATIONSSCHALTER stellen (Betrieb → Servereinstellungen →
     * Adresssuche). Er gehoert der Verwaltung, also holt sich diese
     * Handreichung die Rolle `admin` selbst — ein Weg im Demo-Konto soll
     * nicht wissen muessen, wer wofuer zustaendig ist.
     */
    async schalterInstallation(an) {
      const a = await rolleHolen('admin');
      const s = a.seite;
      await s.goto(`${BASIS}/betrieb_server.php`, { waitUntil: 'domcontentloaded' });
      const box = s.locator('#k-adresssuche .schalter-box').first();
      if (!(await box.count())) { throw new Error('Karte „Adresssuche" fehlt im Betrieb'); }
      if ((await box.isChecked()) !== an) {
        await s.locator('#k-adresssuche .schalter-label').first().click();
      }
      await Promise.all([
        s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }),
        s.locator('#k-adresssuche form button[type="submit"]').first().click(),
      ]);
      const jetzt = await s.locator('#k-adresssuche .schalter-box').first()
        .isChecked().catch(() => null);
      if (jetzt !== an) {
        throw new Error(`Installationsschalter steht nach dem Speichern auf ${jetzt}, `
          + `verlangt war ${an}`);
      }
    },

    /** Breite und Bedienhoehe stehen IM DATEINAMEN — ein Bild ohne beides
     *  belegt nichts (die Abnahme lautet „zwei Breiten, beide Bedienhoehen"). */
    breite,
    bedienhoehe: BEDIENHOEHE,

    /** `seite`: die Seite, die ins Bild soll, wenn es nicht die der Rolle
     *  ist — ein Weg mit eigenem Kontext (`eigenerKontext()`) gibt seine mit.
     *  Kannte `bild()` nur die Seite der Rolle, und die drei Bilder
     *  des Zweitfaktor-Wegs zeigten die Startseite des Demo-Kontos statt Tor,
     *  Codes und Haken — unter dem richtigen Namen. */
    async bild(name, seite = null) {
      if (!BILDER) { return; }
      mkdirSync(join(AUSGABE, 'bild'), { recursive: true });
      /* Eingabeart MIT IN DEN NAMEN: Unter 1024 px gilt 44 px fuer beide, und
       * ohne sie schreiben der Zeiger- und der Fingerlauf dieselbe Datei —
       * zwei Laeufe, ein Bild, und niemand sieht es. */
      const art = FINGER ? 'finger' : 'zeiger';
      await (seite ?? r.seite).screenshot({
        path: join(AUSGABE, 'bild', `${name}-${breite}-${art}-${BEDIENHOEHE}px.png`),
        fullPage: false,
      });
    },

    /** Die tatsaechlich gemessene Bedienhoehe — nicht die erwartete. */
    async gemesseneHoehe(auswahl) {
      return r.seite.evaluate((sel) => {
        const e = document.querySelector(sel);
        return e ? Math.round(e.getBoundingClientRect().height) : null;
      }, auswahl);
    },
  };
}

/* ---- Lauf ----------------------------------------------------------------- */
/* `--behalten` laesst die Ausgabe stehen: Vier Laeufe (zwei Breiten × zwei
 * Bedienhoehen) sollen vier Bildersaetze hinterlassen, nicht den letzten. Ohne
 * den Schalter wird geraeumt, damit ein Bild aus einem alten Lauf nicht als
 * Beleg fuer einen neuen durchgeht. */
if (!flag('--behalten')) { rmSync(AUSGABE, { recursive: true, force: true }); }
mkdirSync(AUSGABE, { recursive: true });

/** Einen Weg EINMAL fahren, mit Zeitgrenze. Zurück kommen das Ergebnis,
 *  ob er an einer Zeitüberschreitung scheiterte (`zeit`) und ob seine Rolle
 *  dabei schon verworfen wurde (`verworfen`). */
async function einmalFahren(weg, breite) {
  let e, wecker, zeit = false, verworfen = false;
  const frist = new Promise((_, nein) => {
    wecker = setTimeout(() => nein(Object.assign(
      new Error(`Zeitgrenze ${WEG_FRIST / 1000} s überschritten — der Weg hing`), { gehangen: true })),
      WEG_FRIST);
  });
  /* Der abgehängte Lauf darf später noch scheitern, ohne den Prozess zu
   * beenden — Node wertet eine unbehandelte Ablehnung als Absturz. */
  const lauf = (async () => weg.fahren(await kasten(weg.rolle, weg, breite)))();
  lauf.catch(() => {});
  try {
    e = await Promise.race([lauf, frist]);
    if (!e || typeof e !== 'object') { throw new Error('Weg lieferte kein Ergebnis'); }
  } catch (f) {
    zeit = !!(f && (f.gehangen || f.name === 'TimeoutError'));
    /* NACH EINEM FEHLSCHLAG IN WEBKIT DIE SEITE BEFRAGEN. Stirbt
     * sie MITTEN im Weg, ist der Weg verfehlt — das Lebenszeichen in
     * `kasten()` kommt dafür zu spät. Dann sagt es die Bemerkung, und die
     * Rolle wird gleich neu angemeldet, statt dem nächsten Weg dieselbe
     * Seite zu geben. Eine Seite, die nach dem Fehlschlag antwortet,
     * bleibt, wie sie ist: Dann war es der Weg, nicht WebKit. */
    let stumm = false;
    if (f && f.gehangen) {
      await rolleVerwerfen(weg.rolle || 'demo');
      verworfen = true;
      /* Der hängende Lauf geht auf der geschlossenen Seite zu Ende, und sein
       * `finally` räumt auf. Das soll nicht in einen zweiten Versuch fallen
       * — höchstens 15 s darauf warten. */
      await Promise.race([lauf.catch(() => {}), new Promise((ok) => setTimeout(ok, 15000))]);
    } else if (MOTOR === 'webkit' && rollenSpeicher[weg.rolle || 'demo']
             && !(await lebtDieSeite(rollenSpeicher[weg.rolle || 'demo'].seite))) {
      stumm = true;
      console.error(`WebKit-Neuanmeldung: Seite der Rolle ${weg.rolle || 'demo'} antwortete `
        + `nach dem Fehlschlag von ${weg.name} ${LEBENSZEICHEN_MS / 1000} s nicht — neu angemeldet (Nr. 393)`);
      await rolleVerwerfen(weg.rolle || 'demo');
      verworfen = true;
    }
    e = { ist: '—', ok: false,
          bemerkung: 'nicht gefahren: ' + (f && f.message ? f.message : f)
            + (stumm ? ' — danach antwortete die Seite nicht mehr (WebKit, Nr. 393)' : '') };
  } finally {
    clearTimeout(wecker);
  }
  return { e, zeit, verworfen };
}

const ergebnisse = [];
for (const breite of BREITEN) {
  if (BREITEN.length > 1) {
    console.log(`\n== ${breite} px · ${FINGER ? 'Finger' : 'Zeiger'} · `
              + `Sollhöhe ${hoeheFuer(breite)} px ==`);
  }
  for (const weg of WEGE) {
    let { e, zeit, verworfen } = await einmalFahren(weg, breite);
    /* EINMAL VON VORN — NUR IN WEBKIT, NUR NACH EINER ZEITÜBERSCHREITUNG
     * (entschieden von der Betreiberin am 25.10.2026). In neun vollen
     * WebKit-Läufen blieb in vier eine Seite
     * MITTEN in einem Weg stehen, jedes Mal in einem anderen: keine Anfrage
     * beim Server, kein rechnender WebKit-Prozess, der Prozess schlief. Das
     * Lebenszeichen in `kasten()` fängt nur, was VOR dem Weg schon stumm
     * ist. Ein solcher Weg wird deshalb einmal ganz wiederholt, mit frisch
     * angemeldeter Rolle — nicht der eine Klick (den schließt `tools/motor.mjs` weiter
     * aus), sondern der Weg von seinem Anfang an, nachdem sein `finally`
     * aufgeräumt hat. Ein Fehler der Anwendung scheitert auch beim zweiten
     * Mal. Wiederholt wird NUR, was geworfen hat (Playwrights
     * `TimeoutError` oder die Zeitgrenze des Wegs) — ein Weg, der ein
     * falsches Ist misst, ist verfehlt, ohne zweite Gelegenheit. Die Zeile
     * `WebKit-Wiederholung:` zählt `pruefen.sh`, und die Bemerkung des Wegs
     * nennt den ersten Fehlschlag. */
    if (MOTOR === 'webkit' && zeit) {
      const erst = String(e.bemerkung).split('\n')[0];
      console.error(`WebKit-Wiederholung: Weg ${weg.name} von vorn, mit neu angemeldeter Rolle `
        + `— der erste Versuch: ${erst.slice(0, 160)} (Nr. 393)`);
      if (!verworfen && rollenSpeicher[weg.rolle || 'demo']) { await rolleVerwerfen(weg.rolle || 'demo'); }
      ({ e } = await einmalFahren(weg, breite));
      e = { ...e, bemerkung: `zweiter Versuch (WebKit, Nr. 393; der erste: ${erst})`
                             + (e.bemerkung ? ' · ' + e.bemerkung : '') };
    }
    const zeile = {
      name: weg.name, paket: weg.paket, punkt: weg.punkt || '',
      was: weg.was, soll: weg.soll, ist: String(e.ist),
      breite, eingabeart: FINGER ? 'finger' : 'zeiger', bedienhoehe: hoeheFuer(breite),
      ok: !!e.ok, bemerkung: e.bemerkung || '',
    };
    ergebnisse.push(zeile);
    console.log(`${zeile.ok ? 'ok  ' : 'FEHL'}  ${zeile.name.padEnd(28)} `
              + `Soll ${String(zeile.soll).padEnd(14)} Ist ${zeile.ist}`
              + (zeile.bemerkung ? `   (${zeile.bemerkung})` : ''));
  }
}

const verfehlt = ergebnisse.filter(e => !e.ok).length;

const md = [];
md.push('# Klickprobe — Bericht');
md.push('');
md.push(`Lauf gegen \`${BASIS}\`${MARKE ? ` · ${MARKE}` : ''} — `
      + `${BREITEN.join(', ')} px als ${FINGER ? 'Fingergerät' : 'Zeigergerät'} `
      + `(Sollhöhe ${BREITEN.map(hoeheFuer).join('/')} px).`);
md.push(`**${ergebnisse.length - verfehlt} von ${ergebnisse.length} Wegen erfüllt**, `
      + `${verfehlt} verfehlt.`);
md.push('');
md.push('| Weg | Paket | Prüfpunkt | Breite | Soll | Ist | |');
md.push('|---|---|---|--:|---|---|---|');
for (const e of ergebnisse) {
  md.push(`| \`${e.name}\` | ${e.paket} | ${e.punkt} | ${e.breite} px `
        + `(${e.bedienhoehe}) | ${e.soll} | **${e.ist}** | `
        + `${e.ok ? 'erfüllt' : 'VERFEHLT'} |`);
}
const mitBemerkung = ergebnisse.filter(e => e.bemerkung);
if (mitBemerkung.length) {
  md.push('');
  md.push('## Bemerkungen');
  md.push('');
  for (const e of mitBemerkung) { md.push(`- \`${e.name}\`: ${e.bemerkung}`); }
}
md.push('');
/* Die Auskunft steht VOR den Grenzen und nicht darin: Sie ist kein
 * dauerhafter Vorbehalt, sondern eine Aussage ueber DIESEN Lauf. */
const DEMO_RESET_NACH = await demoUhr();
if (DEMO_RESET_VOR !== null && DEMO_RESET_NACH !== null
    && DEMO_RESET_NACH !== DEMO_RESET_VOR) {
  const uhr = new Date(DEMO_RESET_NACH * 1000).toISOString().slice(11, 19);
  md.push('## Achtung: der Demo-Reset lief WÄHREND dieses Laufs');
  md.push('');
  md.push(`Das Demo-Konto hat sich um **${uhr} UTC** zurückgesetzt `
        + '(`DEMO_RESET_SEKUNDEN` = 1800, `demo_lib.php`). Dabei verschwindet '
        + 'der Bestand kurz und die Sitzungs-Epoche steigt.');
  md.push('');
  md.push('**Jeder verfehlte Weg oben ist damit zunächst verdächtig**, und '
        + 'zwar besonders die mit „Bestand leer?", „Kein Diensttag im '
        + 'Bestand" oder einer Zeitgrenze. Die Sitzungswache fängt nur die '
        + 'verlorene Sitzung ab, nicht den fehlenden Bestand. **Vor dem '
        + 'Melden einer Zahl den Lauf wiederholen** — er dauert weniger als '
        + 'das 30-Minuten-Fenster.');
  md.push('');
  console.log('');
  console.log(`ACHTUNG: Der Demo-Reset lief um ${uhr} UTC mitten in diesem `
            + `Lauf. Verfehlte Wege sind verdaechtig — bitte wiederholen.`);
}
md.push('## Grenzen dieses Laufs');
md.push('');
md.push(`- Gefahren in ${MOTOR}; die anderen beiden Engines haben je einen eigenen Lauf`);
md.push('  und Bericht (`ausgabe-firefox/`, `ausgabe-webkit/`). Ein echter Safari ist es nicht.');
md.push('- Die Adressabfrage lief gegen die Attrappe (`attrappe.mjs`), nicht gegen');
md.push('  den echten Dienst — der Prüfstand hat dorthin keinen Netzzugang.');
md.push(`- Gemessen wurde bei **${BREITEN.join(', ')} px** als `
      + `**${FINGER ? 'Fingergerät' : 'Zeigergerät'}** — nicht mit Handschuhen und`);
md.push('  nicht an einem echten Gerät.');
md.push('');

writeFileSync(join(AUSGABE, 'bericht.md'), md.join('\n'), 'utf-8');
writeFileSync(join(AUSGABE, 'bericht.json'),
  JSON.stringify({ basis: BASIS, marke: MARKE, breiten: BREITEN,
                   eingabeart: FINGER ? 'finger' : 'zeiger', ergebnisse }, null, 2), 'utf-8');

console.log('');
console.log(`${ergebnisse.length - verfehlt} von ${ergebnisse.length} Wegen erfüllt, `
          + `${verfehlt} verfehlt.  →  ${join(AUSGABE, 'bericht.md')}`);

await browser.close();
process.exit(verfehlt ? 1 : 0);
