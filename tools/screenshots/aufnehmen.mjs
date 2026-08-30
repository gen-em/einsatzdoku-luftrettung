/* Bildaufnahme aller Seiten in acht Breiten (P3, Anlage F).
 * ===========================================================================
 *
 * WOFUER. P3 baut die Oberfläche neu, und die Frage nach jedem Arbeitspaket
 * ist immer dieselbe: Sieht die Seite in ALLEN Breiten so aus, wie sie soll —
 * und ist unterwegs nichts verlorengegangen? Diese Frage beantwortet kein
 * Kaskadenvergleich; sie beantwortet nur ein Bild.
 *
 * Der Stilvergleich (tools/stilvergleich/) misst berechnete Werte und ist auf
 * „nichts hat sich geändert" gebaut. In einem beabsichtigten Redesign liefert
 * er Tausende Abweichungen, die niemand gegen einen Plan hält. Er ruht während
 * P3 und wird in O12 neu geeicht. Dieses Werkzeug tritt an seine Stelle — mit
 * einer anderen Frage: nicht „was hat sich geändert?", sondern „stimmt es?"
 *
 * WAS ES MISST (und damit belegt, statt zu behaupten):
 *   - waagerechter Überlauf   scrollWidth > innerWidth  je Seite und Breite
 *   - Konsolenfehler          je Seite und Breite
 *   - Knopfhöhen              jede .knopf-Regel muss 44 px hoch sein (P-P3-04);
 *                             einzige benannte Ausnahme: der Filterknopf
 *                             neben dem 48-px-Suchfeld der Suche (O6)
 *   - Kontraste der Token     aus dem Stylesheet gerechnet (P-P3-05)
 *
 * AUFRUF
 *   sh tools/referenzdatensatz/einspielen/lokal_starten.sh    (einmal)
 *   node tools/screenshots/aufnehmen.mjs
 *   node tools/screenshots/aufnehmen.mjs --nur 10-,12-        (Teilmenge)
 *   node tools/screenshots/aufnehmen.mjs --klein              (1x statt 2x)
 *
 * AUSGABE unter tools/screenshots/ausgabe/ (steht in .gitignore):
 *   einzeln/<seite>-<breite>.png      die Einzelbilder
 *   bogen/<seite>.png                 der Kontaktbogen, acht Breiten nebeneinander
 *   bericht.md, bericht.json          Zahlen und Befunde
 *
 * GRENZEN. Gemessen wird Chromium. WebKit (Safari, iOS) und Gecko (Firefox)
 * stehen in dieser Umgebung nicht zur Verfügung; was nur dort auffiele, fällt
 * hier nicht auf. Bedienzustände sind nur so weit erfasst, wie die Seitenliste
 * sie als `vorher`-Schritte führt.
 */
import { mkdirSync, writeFileSync, readFileSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

/* Hinter einem HTTPS_PROXY (etwa in der Claude-Umgebung) braucht Nodes
 * eingebautes fetch die Variable NODE_USE_ENV_PROXY — und die wird nur beim
 * Prozessstart gelesen. Nachtraeglich gesetzt gehen die Abrufe am Proxy
 * vorbei und laufen in die Egress-Sperre. Deshalb startet sich das Skript
 * einmal selbst neu, wenn die Variable fehlt. Ohne Proxy: kein Neustart. */
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

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = join(HIER, '..', '..');
const AUSGABE = join(HIER, 'ausgabe');

const argv = process.argv.slice(2);
const flag = (n) => argv.includes(n);
const wert = (n, s) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : s; };

const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const DEMO   = { email: wert('--demo', 'demo@gen-em.org'),  pw: wert('--demo-pw', 'nadokudemo0815') };
const ADMIN  = { email: wert('--admin', 'admin@gen-em.org'), pw: wert('--admin-pw', 'adminlokal2026') };
const SKALA  = flag('--klein') ? 1 : 2;
const FILTER = (wert('--nur', '') || '').split(',').filter(Boolean);

/* Acht Breiten, je mit einer realistischen Höhe. Die Höhe entscheidet nur
 * darüber, wie viel ohne Scrollen sichtbar ist — aufgenommen wird die ganze
 * Seite; sie steuert aber, was `position:sticky` und `100vh` tun. */
const BREITEN = [
  { b:  360, h:  800, art: 'Handy'   },
  { b:  390, h:  844, art: 'Handy'   },
  { b:  420, h:  900, art: 'Handy'   },
  { b:  768, h: 1024, art: 'Tablet'  },
  { b: 1024, h:  768, art: 'Tablet'  },
  { b: 1280, h:  900, art: 'Desktop' },
  { b: 1440, h:  900, art: 'Desktop' },
  { b: 1920, h: 1080, art: 'Desktop' },
];

const { seiten } = JSON.parse(readFileSync(join(HIER, 'seiten.json'), 'utf-8'));
const liste = FILTER.length
  ? seiten.filter(s => FILTER.some(f => s.name.startsWith(f)))
  : seiten;

/* WAS NICHT ALS KONSOLENFEHLER ZAEHLT — und warum die Unterscheidung noetig
 * ist: Ein Bericht, der jede rote Zeile meldet, wird nach zwei Laeufen
 * weggeklickt, und dann geht der echte Fehler mit unter.
 *
 * 1  Kartenkacheln und Ortssuche sind bewusste Laufzeitquellen (map_layers.js
 *    und ortsfeld.js nennen Herkunft und Lizenz). Ein gescheiterter Abruf sagt
 *    ueber die Anwendung nichts.
 * 2  Der Statuscode der SEITE SELBST. Die Abbruchseite antwortet mit 404 oder
 *    409 — das ist ihre Aufgabe, nicht ihr Fehler. Chromium meldet trotzdem
 *    "Failed to load resource". Erkannt wird das daran, dass die Fundstelle
 *    der Meldung die Seitenadresse selbst ist.
 */
const KACHELRAUSCHEN =
  /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|ERR_INTERNET_DISCONNECTED|ERR_CONNECTION_RESET|ERR_CONNECTION_CLOSED|ERR_ABORTED|tile\.|openstreetmap|opentopomap|arcgisonline|photon\.komoot/i;

function istRauschen(meldung, seitenAdresse) {
  const text = meldung.text();
  const ort = (meldung.location && meldung.location().url) || '';
  if (KACHELRAUSCHEN.test(text) || KACHELRAUSCHEN.test(ort)) return true;
  // Der Statuscode der Seite selbst.
  if (ort && seitenAdresse && ort.split('#')[0] === seitenAdresse.split('#')[0]) return true;
  return false;
}

rmSync(AUSGABE, { recursive: true, force: true });
mkdirSync(join(AUSGABE, 'einzeln'), { recursive: true });
mkdirSync(join(AUSGABE, 'bogen'), { recursive: true });

const browser = await chromium.launch();

/* Kartenkacheln liefert NODE, nicht der Browser (Fund aus O3).
 *
 * Der Pruef-Browser kommt in der Claude-Umgebung nicht an tile.openstreetmap.org:
 * Direktverbindungen setzt die Egress-Sperre zurueck, und auch mit
 * --proxy-server bricht der TLS-Handschlag nach dem CONNECT ab
 * (ERR_CONNECTION_RESET; per NetLog belegt, unabhaengig von TLS-Version und
 * Post-Quantum-Merkmalen — der Weg ist fuer diesen Browser schlicht zu).
 * Nodes fetch kommt durch den Umgebungsproxy dagegen zuverlaessig an (siehe
 * Neustart-Weiche oben). Also faengt eine Playwright-Route die Kachelabrufe ab
 * und beantwortet sie aus einem Node-Abruf — mit Lager je URL, damit 232
 * Aufnahmen die Kachelserver nicht 232-fach fragen. Nebeneffekt: Die Bilder
 * werden deterministischer, und ohne Proxy (lokaler Rechner) funktioniert
 * derselbe Weg unveraendert direkt. */
const kachelLager = new Map();
async function kachelAntwort(route) {
  const url = route.request().url();
  try {
    if (!kachelLager.has(url)) {
      const a = await fetch(url, { headers: { 'User-Agent': 'einsatzdoku-pruefwerkzeug/1.0' } });
      kachelLager.set(url, {
        status: a.status,
        ct: a.headers.get('content-type') || 'image/png',
        body: Buffer.from(await a.arrayBuffer()),
      });
    }
    const k = kachelLager.get(url);
    await route.fulfill({ status: k.status, contentType: k.ct, body: k.body });
  } catch {
    await route.abort('failed');
  }
}
const KACHELMUSTER = [
  'https://tile.openstreetmap.org/**',
  'https://*.tile.openstreetmap.org/**',
];

/* EINE Seite je Rolle, nicht eine je Aufnahme.
 *
 * Der Inhaltsschluessel liegt nach der Anmeldung im sessionStorage — und der
 * ist an die REGISTERKARTE gebunden, nicht an den Browserkontext. Der erste
 * Entwurf oeffnete je Aufnahme eine neue Seite; jede davon startete mit
 * leerem sessionStorage, und auf jedem Bild stand der Entsperrdialog statt
 * des Inhalts. Genau die Angaben, um die es geht (Einsatzort, Diagnose,
 * Alter), waren auf keinem der 232 Bilder zu sehen.
 *
 * Also: anmelden, die Seite behalten, fuer jede Breite nur die Fenstergroesse
 * aendern. Das haelt den Schluessel und ist nebenbei erheblich schneller. */
/* Die eigentliche Anmeldung — als eigener Schritt, weil sie MITTEN IM LAUF
 * wiederholt werden muss (siehe `sitzungHalten`). */
async function anmeldenAuf(seite, rolle) {
  if (rolle === 'aus') { return true; }
  const konto = rolle === 'admin' ? ADMIN : DEMO;
  await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', konto.email);
  await seite.fill('input[name="password"]', konto.pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('button[type="submit"]'),
  ]);
  return !seite.url().includes('login.php');
}

async function anmelden(rolle) {
  const kontext = await browser.newContext({
    ignoreHTTPSErrors: true, deviceScaleFactor: SKALA,
    viewport: { width: 1280, height: 900 },
  });
  for (const muster of KACHELMUSTER) await kontext.route(muster, kachelAntwort);
  const seite = await kontext.newPage();
  if (!await anmeldenAuf(seite, rolle)) {
    const konto = rolle === 'admin' ? ADMIN : DEMO;
    throw new Error(`Anmeldung als ${konto.email} gescheitert`);
  }

  /* Die Fehlersammlung haengt an der Seite und wird je Aufnahme geleert. */
  const fehler = [];
  let adresse = '';
  seite.on('console', m => {
    if (m.type() === 'error' && !istRauschen(m, adresse)) {
      const ort = (m.location && m.location().url) || '';
      fehler.push(m.text() + (ort ? '  [' + ort + ']' : ''));
    }
  });
  seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));
  return { kontext, seite, fehler, rolle, setzeAdresse: (a) => { adresse = a; } };
}

const rollen = { aus: await anmelden('aus'), demo: await anmelden('demo'), admin: await anmelden('admin') };

/* ---- Platzhalter auflösen -------------------------------------------------
 *
 * Die Seitenliste kann keine Kennungen enthalten: Sie gehört ins
 * Repositorium, die Kennungen gehören zu EINER Installation. Sie werden
 * deshalb zur Laufzeit aus dem Bestand geholt — über dieselben Wege, die eine
 * NutzerIn ginge. */
async function platzhalter() {
  const s = rollen.demo.seite;
  /* UEBER DIE SITZUNGSWACHE (Web 9.10.1). Diese Funktion laeuft als erste im
     Lauf und ist damit die erste, die einen faelligen Demo-Reset ausloest.
     Landete sie auf der Anmeldeseite, fand sie keine Einsatzzeile, lieferte
     ein leeres Verzeichnis — und alle vier Einsatzseiten wurden mit ihrem
     eigenen Platzhalter als Adresse aufgerufen. Der lokale Server antwortet
     darauf mit 200 und der Startseite; acht Bilder je Seite, alle falsch,
     kein Fehler. Genau die Falle aus F-P3-AH, eine Ebene tiefer. */
  await gehZu(rollen.demo, `${BASIS}/index.php`, 'index.php');

  /* Auf die ERSTE Einsatzzeile warten, nicht auf eine feste Zeit. Die
   * Tagesübersicht holt ihre Einsätze über api/day.php nach; eine Wartezeit
   * ist geraten, ein Selektor nicht. Der erste Anlauf wartete 1,5 s und lief
   * in einen Timeout beim Klick — die Tabelle war noch leer. */
  await s.waitForSelector('#missions tbody tr, .kachel', { timeout: 30000 }).catch(() => {});
  await s.waitForTimeout(300);

  /* Kennungen aus dem Seitenzustand lesen statt aus einem Klick: Ein Klick
   * prüft den Bedienweg, und der wird anderswo geprüft — hier soll er nur
   * eine Adresse liefern, und dabei darf er nicht die Aufnahme aufhalten. */
  const kennung = await s.evaluate(() => ({
    tag: (typeof currentDayId !== 'undefined' && currentDayId) || null,
    einsatz: (typeof dayMissions !== 'undefined' && dayMissions[0]) ? dayMissions[0].id : null,
  })).catch(() => ({ tag: null, einsatz: null }));

  let { tag, einsatz } = kennung;
  if (!einsatz) {                       // Rückfall: über den Klick
    await Promise.all([
      s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
      s.locator('#missions tbody tr').first().click(),
    ]).catch(() => {});
    einsatz = new URL(s.url()).searchParams.get('id');
  }

  /* NULL STATT EINES RUECKFALLS. Bis Web 9.10.1 lieferte ein nicht
     aufloesbarer Platzhalter entweder 'index.php' (Tag-Seiten) oder gar
     keinen Eintrag (Einsatz-Seiten). Beides endete in einem Bild der
     falschen Seite unter dem richtigen Namen: einmal die Startseite als
     „Diensttag Datum aendern", einmal die Startseite als „Einsatzformular".
     Ein fehlender Wert ist jetzt ausdruecklich `null` und fuehrt dazu, dass
     die Seite NICHT fotografiert wird. */
  const p = {
    '__EINSATZ__':     einsatz ? `einsatz.php?id=${einsatz}`            : null,
    '__FORMULAR__':    einsatz ? `einsatz_form.php?id=${einsatz}`       : null,
    '__VERSCHIEBEN__': einsatz ? `einsatz_verschieben.php?id=${einsatz}`: null,
    '__LOESCHEN__':    einsatz ? `einsatz_loeschen.php?id=${einsatz}`   : null,
    '__TAG_DATUM__':    tag ? `diensttag_datum.php?d=${tag}`    : null,
    '__TAG_LOESCHEN__': tag ? `diensttag_loeschen.php?d=${tag}` : null,
    /* Die Zusammenfuehrung braucht ihren Zieltag genauso — sie stand bis O11
       OHNE Parameter in der Seitenliste und lieferte deshalb 404 mit der
       Abbruchseite. Acht Bilder, alle von der falschen Seite, und der Lauf
       meldete „kein Ueberlauf". Dieselbe Falle wie F-P3-AH und F-P3-AQ, ein
       drittes Mal. */
    '__TAG_ZUSAMMEN__': tag ? `diensttag_zusammenfuehren.php?d=${tag}` : null,
  };

  const a = rollen.admin.seite;
  await gehZu(rollen.admin, `${BASIS}/admin_users.php`, 'admin_users.php');
  const href = await a.locator('a[href*="admin_user.php?id="]').first()
                      .getAttribute('href').catch(() => null);
  p['__KONTO__'] = href || null;

  const fehlend = Object.entries(p).filter(([, v]) => v === null).map(([k]) => k);
  if (fehlend.length) {
    console.log('NICHT AUFGELÖST (diese Seiten werden nicht fotografiert): '
                + fehlend.join(', '));
  }
  return p;
}

const PLATZ = await platzhalter();

/* ---- Bedienschritte vor der Aufnahme -------------------------------------- */
/* EIN UNBEKANNTER SCHRITT IST EIN FEHLER, kein Achselzucken (O11).
 *
 * Diese Funktion hatte kein `else`: Wer `"vorher": ["dialog"]` in die
 * Seitenliste schrieb, bekam anstandslos acht Bilder OHNE Dialog und einen
 * Bericht „0 Ueberlauf, 0 Konsolenfehler". Dieselbe Falle wie F-P3-AH
 * (Seite ohne Parameter), F-P3-AQ (verlorene Sitzung) und F-P3-AV (falscher
 * Statuscode) — zum vierten Mal, und jedes Mal ist das Muster dasselbe: Das
 * Werkzeug tut etwas anderes als bestellt und meldet Erfolg.
 *
 * Rueckgabe: null bei Erfolg, sonst der Fehlertext. */
async function vorher(seite, schritte, fehlerSammler) {
  const BEKANNT = ['schublade'];
  for (const schritt of schritte || []) {
    if (!BEKANNT.includes(schritt)) {
      fehlerSammler.push(`Unbekannter Bedienschritt „${schritt}" — bekannt sind: `
                       + BEKANNT.join(', '));
      continue;
    }
    if (schritt === 'schublade') {
      /* Die Schublade gibt es nur unter 1024 px — darueber steht die Leiste
         fest daneben, und der Menueknopf ist ausgeblendet. Ein Klick darauf
         lief in einen Timeout und meldete einen Fehler, den es nicht gibt.
         Deshalb: erst fragen, ob es das Bedienelement gerade gibt. */
      const knopf = seite.locator('[data-schublade="auf"]').first();
      if (await knopf.count() && await knopf.isVisible()) {
        await knopf.click();
        await seite.waitForTimeout(350);
      }
    }
  }
}

/* ---- DIE SITZUNGSWACHE (Web 9.10.1) ---------------------------------------
 *
 * WAS HIER SCHIEFGING, UND WARUM ES NIEMAND SAH.
 *
 * Der Lauf meldete „31 Seiten, 0 Ueberlauf, 0 Konsolenfehler" — und 22 der
 * 31 Seiten waren Bilder der ANMELDESEITE. 176 von 248 Einzelbildern. Sie
 * waren byteweise identisch; nachgewiesen mit `md5sum`, 23 Dateien je Breite
 * mit derselben Pruefsumme.
 *
 * Die Ursache steht nicht in diesem Werkzeug, sondern in der Anwendung:
 * Das Demo-Konto setzt sich alle 30 Minuten zurueck, und dabei erhoeht
 * `demo_zuruecksetzen()` die Sitzungs-Epoche (server/demo_lib.php,
 * `session_epoch = session_epoch + 1`). `auth_guard.php` beendet daraufhin
 * jede offene Sitzung dieses Kontos — auch unsere. Der Lauf braucht
 * mehrere Minuten und loest den faelligen Reset durch seine EIGENEN
 * Anfragen aus; ab da fotografiert er die Anmeldeseite.
 *
 * Die alte Pruefung (`if (seite.url().includes('login.php')) throw`) stand
 * EINMAL, unmittelbar nach dem Anmelden. Danach hat nichts mehr hingesehen.
 *
 * ZWEI DINGE SIND NOETIG, UND BEIDE STEHEN HIER:
 *
 *   1. Bemerken. Nach jedem `goto` wird geprueft, ob die Seite noch die
 *      gemeinte ist. Eine Umleitung auf die Anmeldung ist kein Bild wert.
 *   2. Weitermachen. Ein Sitzungsverlust ist im Demo-Betrieb NORMAL, nicht
 *      aussergewoehnlich — der Reset gehoert zum Konto. Also wird neu
 *      angemeldet und die Aufnahme einmal wiederholt.
 *
 * Hilft auch das nicht, wird NICHT fotografiert, sondern ein Fehler
 * vermerkt. Ein fehlendes Bild ist eine Auskunft; ein falsches ist eine
 * Luege, die durch jede weitere Pruefung durchmarschiert.
 */
function istAnmeldung(seite) {
  return seite.url().includes('login.php');
}

/* Bringt die Seite auf `adresse` und stellt sicher, dass sie auch dort ist.
 * Rueckgabe: { status, verloren } — `verloren` sagt, dass die Sitzung neu
 * aufgebaut werden musste (fuer den Bericht). Wirft nicht; Fehler landen in
 * `rolle.fehler`. */
async function gehZu(rolle, adresse, zielPfad) {
  const seite = rolle.seite;
  let status = 0, verloren = false;
  for (let versuch = 0; versuch < 2; versuch++) {
    try {
      const antwort = await seite.goto(adresse, { waitUntil: 'domcontentloaded', timeout: 45000 });
      status = antwort ? antwort.status() : 0;
    } catch (e) {
      rolle.fehler.push('laden: ' + e.message);
      return { status, verloren };
    }
    /* Die Anmeldeseite ist nur dann die richtige Antwort, wenn sie auch
       gemeint war (01-anmeldung, Rolle „aus"). */
    if (!istAnmeldung(seite) || zielPfad.startsWith('login.php')) {
      return { status, verloren };
    }
    if (versuch === 1) { break; }
    verloren = true;
    if (!await anmeldenAuf(seite, rolle.rolle)) {
      rolle.fehler.push('Sitzung verloren und Neuanmeldung gescheitert');
      return { status, verloren };
    }
  }
  rolle.fehler.push('Sitzung verloren: die Seite leitet auf die Anmeldung um '
                  + '(auch nach Neuanmeldung) — kein Bild aufgenommen');
  return { status, verloren, abbruch: true };
}

/* ---- Eine Aufnahme --------------------------------------------------------- */
const bericht = { basis: BASIS, skala: SKALA, seiten: [], knopf: [], stand: new Date().toISOString() };
/* Aufnahmen, bei denen die Sitzung mitten im Lauf neu aufgebaut werden
 * musste (Demo-Reset), und solche, die deshalb GAR NICHT entstanden. */
const verlorene = [];
const ausgefallen = [];

for (const eintrag of liste) {
  /* Ein Platzhalter, der in PLATZ steht, MUSS einen Wert haben — sonst gibt
     es diese Seite im Bestand nicht, und ein Bild waere geraten. */
  const aufgeloest = Object.prototype.hasOwnProperty.call(PLATZ, eintrag.pfad)
    ? PLATZ[eintrag.pfad] : eintrag.pfad;
  if (aufgeloest === null) {
    for (const { b } of BREITEN) { ausgefallen.push(`${eintrag.name} @ ${b}`); }
    console.log(`${eintrag.name.padEnd(34)} OHNE BILD — Platzhalter ${eintrag.pfad} nicht auflösbar`);
    continue;
  }
  const pfad = aufgeloest;
  const rolle = rollen[eintrag.rolle || 'demo'];
  const seite = rolle.seite;
  const zeile = { name: eintrag.name, gruppe: eintrag.gruppe, pfad, breiten: [] };
  const bilder = [];

  for (const { b, h, art } of BREITEN) {
    const adresse = `${BASIS}/${pfad}`;
    rolle.fehler.length = 0;
    rolle.setzeAdresse(adresse);
    await seite.setViewportSize({ width: b, height: h });

    const hin = await gehZu(rolle, adresse, pfad);
    const status = hin.status;
    /* DER STATUS MUSS STIMMEN (O11). Eine Seite, die 404 liefert, zeigt die
       Abbruchseite — ein Bild davon unter dem Namen einer anderen Seite ist
       so wertlos wie ein Bild der Anmeldung. Erwartet wird 200; eine Seite,
       die es anders meint (03-abbruchseite), sagt das in der Seitenliste
       ausdruecklich mit "status". */
    const sollStatus = eintrag.status || 200;
    if (status && status !== sollStatus && !hin.abbruch) {
      rolle.fehler.push(`Status ${status}, erwartet ${sollStatus} — kein Bild aufgenommen`);
      hin.abbruch = true;
    }
    if (hin.verloren && !hin.abbruch) {
      /* Kein Fehler, aber eine Auskunft: Der Reset des Demo-Kontos ist im
         Lauf normal, und wer den Bericht liest, soll wissen, dass hier neu
         angemeldet wurde. */
      verlorene.push(`${eintrag.name} @ ${b}`);
    }
    if (!hin.abbruch) {
      try {
        await seite.waitForTimeout(eintrag.karte ? 900 : 400);
        await vorher(seite, eintrag.vorher, rolle.fehler);
        await seite.waitForTimeout(150);
      } catch (e) {
        rolle.fehler.push('laden: ' + e.message);
      }
    }

    const mass = await seite.evaluate(() => ({
      scrollWidth: document.documentElement.scrollWidth,
      innerWidth: window.innerWidth,
      /* WER ueberlaeuft? Ohne diese Auskunft ist „Ueberlauf bei 360" eine
         Zahl, mit der niemand etwas anfangen kann: Man weiss, DASS die Seite
         zu breit ist, nicht WOVON. Gesucht wird das Element, das am weitesten
         nach rechts reicht und dessen Elternteil das nicht auch tut — also
         der Verursacher, nicht die Kette darueber. */
      taeter: (function () {
        var grenze = window.innerWidth, bester = null, weiteste = grenze;
        var alle = document.querySelectorAll('body *');
        for (var i = 0; i < alle.length; i++) {
          var el = alle[i];
          var r = el.getBoundingClientRect();
          if (r.width === 0 || r.right <= grenze + 1) { continue; }
          var pr = el.parentElement ? el.parentElement.getBoundingClientRect() : null;
          if (pr && pr.right > grenze + 1) { continue; }   // Elternteil laeuft auch ueber
          if (r.right > weiteste) {
            weiteste = r.right;
            bester = el.tagName.toLowerCase()
                   + (el.id ? '#' + el.id : '')
                   + (el.className && typeof el.className === 'string'
                      ? '.' + el.className.trim().split(/\s+/).join('.') : '');
          }
        }
        return bester ? bester + '  (' + Math.round(weiteste) + ' px)' : null;
      })(),
      /* NUR SICHTBARE KNOEPFE messen. Der erste Entwurf mass alle und meldete
         Dutzende mit Hoehe 0: den X-Knopf der Schublade, der ab 1024 px
         `display:none` ist, und die Eintraege in einem geschlossenen
         Aktionsblatt. Ein Knopf, den es gerade nicht gibt, ist nicht zu hoch
         und nicht zu niedrig — er ist nicht da. */
      knoepfe: Array.from(document.querySelectorAll('.knopf'))
        .filter(el => el.offsetParent !== null || el.getClientRects().length > 0)
        .map(el => ({
          text: (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 24)
                || el.getAttribute('aria-label') || '(ohne Text)',
          hoehe: Math.round(el.getBoundingClientRect().height),
          /* Zwilling des grossen Suchfeldes: Der Filterknopf steht daneben
           * und ist so hoch wie es — 48 statt 44 px. Die Regel P-P3-04
           * sichert eine MINDESTflaeche; groesser ist kein Verstoss, nur
           * ungleich. Damit die Zahl im Bericht trotzdem etwas heisst, wird
           * dieser eine Fall benannt statt stillschweigend geduldet. */
          suchzwilling: !!el.closest('.suchzeile'),
        })),
    })).catch(() => ({ scrollWidth: 0, innerWidth: b, knoepfe: [], taeter: null }));

    const datei = join(AUSGABE, 'einzeln', `${eintrag.name}-${b}.png`);
    if (hin.abbruch) {
      /* KEIN BILD. Ein Bild der Anmeldeseite unter dem Namen einer anderen
         Seite ist schlimmer als gar keines: Es sieht wie ein Beleg aus. */
      ausgefallen.push(`${eintrag.name} @ ${b}`);
      rmSync(datei, { force: true });
    } else {
      await seite.screenshot({ path: datei, fullPage: true }).catch(() => {});
      bilder.push({ datei, b, art });
    }

    zeile.breiten.push({
      breite: b, status,
      ueberlauf: mass.scrollWidth > mass.innerWidth ? mass.scrollWidth - mass.innerWidth : 0,
      taeter: mass.taeter || null,
      konsole: rolle.fehler.slice(),
    });
    for (const k of mass.knoepfe) {
      const soll = k.suchzwilling ? 48 : 44;   // siehe Kommentar oben
      if (k.hoehe !== soll) bericht.knopf.push({ seite: eintrag.name, breite: b, soll, ...k });
    }
  }

  await kontaktbogen(eintrag.name, bilder);
  bericht.seiten.push(zeile);
  const ueber = zeile.breiten.filter(x => x.ueberlauf).map(x => x.breite);
  const kons  = zeile.breiten.reduce((n, x) => n + x.konsole.length, 0);
  console.log(`${eintrag.name.padEnd(34)} ${ueber.length ? 'Überlauf bei ' + ueber.join(', ') : 'kein Überlauf'}` +
              `${kons ? '  ·  ' + kons + ' Konsolenfehler' : ''}`);
}

/* ---- Kontaktbogen ---------------------------------------------------------
 *
 * Acht Bilder nebeneinander, jedes mit seiner Breite beschriftet, auf einem
 * Blatt. Gebaut wird er im Browser selbst: Die Einzelbilder gehen als
 * data:-Adressen in eine Seite, die anschliessend fotografiert wird. Ein
 * Bildbearbeitungswerkzeug als weitere Abhängigkeit wäre für diese eine
 * Aufgabe zu viel. */
async function kontaktbogen(name, bilder) {
  const SPALTE = 300;
  const teile = bilder.map(({ datei, b, art }) => {
    const daten = 'data:image/png;base64,' + readFileSync(datei).toString('base64');
    return `<figure style="margin:0;width:${SPALTE}px">
      <figcaption style="font:600 13px/1.4 system-ui;color:#1A2E4D;padding:6px 0">
        ${b} px <span style="color:#6E6459;font-weight:400">· ${art}</span></figcaption>
      <img src="${daten}" style="width:100%;display:block;border:1px solid #E3DAC6;background:#fff">
    </figure>`;
  });
  const seite = await rollen.aus.kontext.newPage();
  await seite.setViewportSize({ width: SPALTE * 8 + 9 * 12, height: 800 });
  await seite.setContent(`<body style="margin:0;padding:12px;background:#F7F5ED">
    <h1 style="font:600 18px/1.4 system-ui;color:#1A2E4D;margin:0 0 10px">${name}</h1>
    <div style="display:flex;gap:12px;align-items:flex-start">${teile.join('')}</div></body>`);
  await seite.waitForTimeout(250);
  await seite.screenshot({ path: join(AUSGABE, 'bogen', `${name}.png`), fullPage: true });
  await seite.close();
}

/* ---- Bericht --------------------------------------------------------------- */
const gesamtUeberlauf = bericht.seiten.reduce((n, s) => n + s.breiten.filter(b => b.ueberlauf).length, 0);
const gesamtKonsole   = bericht.seiten.reduce((n, s) => n + s.breiten.reduce((m, b) => m + b.konsole.length, 0), 0);
const bilderZahl      = bericht.seiten.length * BREITEN.length;

let md = `# Bildaufnahme — Bericht\n\n`;
md += `Stand ${bericht.stand} · Basis ${BASIS} · Maßstab ${SKALA}×\n\n`;
md += `| | |\n|---|---|\n`;
md += `| Seiten | ${bericht.seiten.length} |\n`;
md += `| Breiten | ${BREITEN.map(x => x.b).join(', ')} |\n`;
md += `| Einzelbilder | ${bilderZahl} |\n`;
md += `| Waagerechter Überlauf | **${gesamtUeberlauf}** von ${bilderZahl} |\n`;
md += `| Konsolenfehler | **${gesamtKonsole}** |\n`;
md += `| Knöpfe nicht 44 px | **${bericht.knopf.length}** |\n\n`;
md += `## Je Seite\n\n| Seite | Gruppe | Überlauf bei | Verursacher | Konsole |\n|---|---|---|---|---|\n`;
for (const s of bericht.seiten) {
  const breit = s.breiten.filter(x => x.ueberlauf);
  const u = breit.map(x => `${x.breite} (+${x.ueberlauf})`).join(', ') || '—';
  const t = [...new Set(breit.map(x => x.taeter).filter(Boolean))].join('<br>') || '—';
  const k = s.breiten.reduce((n, x) => n + x.konsole.length, 0);
  md += `| ${s.name} | ${s.gruppe} | ${u} | ${t} | ${k || '—'} |\n`;
}
if (gesamtKonsole) {
  md += `\n## Konsolenfehler im Wortlaut\n\n`;
  for (const s of bericht.seiten) {
    for (const b of s.breiten) {
      for (const f of b.konsole) md += `- \`${s.name}\` @ ${b.breite}: ${f}\n`;
    }
  }
}
if (bericht.knopf.length) {
  md += `\n## Knöpfe außerhalb der 44 px\n\n| Seite | Breite | Knopf | Höhe |\n|---|---|---|---|\n`;
  for (const k of bericht.knopf) {
    md += `| ${k.seite} | ${k.breite} | ${k.text} | ${k.hoehe} px (soll ${k.soll}) |\n`;
  }
}
/* DIE SITZUNG STEHT IM BERICHT, nicht nur in der Konsole. Wer den Bericht
 * spaeter liest, muss sehen koennen, ob die Bilder ueberhaupt die gemeinten
 * Seiten zeigen — genau das war bis Web 9.10.1 nicht der Fall. */
bericht.sitzung = { neu_angemeldet: verlorene, ohne_bild: ausgefallen };
if (verlorene.length || ausgefallen.length) {
  md += `\n## Sitzung\n\n`;
  if (verlorene.length) {
    md += `Bei ${verlorene.length} Aufnahmen war die Sitzung fort und wurde neu `
       +  `aufgebaut; das Bild entstand danach. Im Demo-Konto ist das normal — `
       +  `sein Reset alle 30 Minuten erhöht die Sitzungs-Epoche.\n\n`;
    for (const v of verlorene) md += `- ${v}\n`;
  }
  if (ausgefallen.length) {
    md += `\n**${ausgefallen.length} Aufnahmen sind AUSGEFALLEN** — die Seite `
       +  `leitete auch nach einer Neuanmeldung auf die Anmeldung um. Für sie `
       +  `gibt es kein Bild; das ist Absicht.\n\n`;
    for (const a of ausgefallen) md += `- ${a}\n`;
  }
}

writeFileSync(join(AUSGABE, 'bericht.md'), md);
writeFileSync(join(AUSGABE, 'bericht.json'), JSON.stringify(bericht, null, 2) + '\n');

console.log(`\n${bilderZahl} Einzelbilder, ${bericht.seiten.length} Kontaktbögen.`);
console.log(`Überlauf: ${gesamtUeberlauf} · Konsolenfehler: ${gesamtKonsole} · Knöpfe ≠ 44 px: ${bericht.knopf.length}`);
if (verlorene.length)   { console.log(`Sitzung neu aufgebaut: ${verlorene.length}× (Demo-Reset, normal)`); }
if (ausgefallen.length) { console.log(`OHNE BILD: ${ausgefallen.length} Aufnahmen — Sitzung nicht zu halten`); }
console.log(`Bericht: ${join(AUSGABE, 'bericht.md')}`);

await browser.close();
/* Eine ausgefallene Aufnahme ist ein Fehlschlag: Der Lauf hat seine Frage
   nicht beantwortet. */
process.exit(gesamtUeberlauf === 0 && gesamtKonsole === 0
             && bericht.knopf.length === 0 && ausgefallen.length === 0 ? 0 : 1);
