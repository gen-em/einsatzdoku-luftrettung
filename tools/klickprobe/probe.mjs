/* Klickprobe — das Pruefmittel, das bedient statt fotografiert (S9, E-S9-16).
 * ===========================================================================
 *
 * WARUM ES SIE GIBT. Das Projekt hatte vor S9 vier Pruefmittel im Browser, und
 * keines davon hat je ein Element BEDIENT: Der Bilderlauf fotografiert, die
 * Vollstaendigkeit liest das Stylesheet, die Linkprobe folgt Adressen, die
 * Wartungsprobe zaehlt Erwartungen auf einer Seite. Zwei Fehler sind genau
 * dort hindurchgelaufen:
 *
 *   PS-2 (Backlog 102) — die Trefferliste der weiteren Rettungsmittel
 *   uebernahm auf `click`, das Feld versteckte die Liste 150 ms nach `blur`.
 *   Wer die Maus laenger haelt, bekommt kein `click`. Auf jedem Bild sah die
 *   Liste richtig aus.
 *
 *   Nr. 148 — der Knopf „Diensttage zusammenfuehren" fuehrte auf 404, weil er
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
 *   node tools/klickprobe/probe.mjs
 *   node tools/klickprobe/probe.mjs --nur ap1
 *   node tools/klickprobe/probe.mjs --bilder      (Bild je Weg)
 *   node tools/klickprobe/probe.mjs --breiten 390,1280 --bilder
 *   node tools/klickprobe/probe.mjs --breiten 390,1280 --finger --bilder --behalten
 *
 * AUSGABE unter tools/klickprobe/ausgabe/ (steht in .gitignore):
 *   bericht.md, bericht.json  — je Weg Soll, Ist, Urteil
 *   bild/<weg>.png            — nur mit --bilder
 *
 * RUECKGABEWERT != 0, sobald ein Weg sein Soll verfehlt oder gar nicht
 * gefahren werden konnte. Ein Weg, der nicht gefahren werden konnte, gilt als
 * VERFEHLT und nicht als „uebersprungen": Ein Pruefmittel, das sich selbst
 * ueberspringt, meldet Null, ohne gemessen zu haben.
 *
 * GRENZEN. Nur Chromium (WebKit und Gecko stehen hier nicht zur Verfuegung);
 * die Adressabfrage laeuft gegen die Attrappe in `attrappe.mjs`, nicht gegen
 * den echten Dienst; ein Finger ist kein Zeiger — was nur mit Handschuhen
 * auffaellt, faellt hier nicht auf.
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
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const HIER = dirname(fileURLToPath(import.meta.url));
const AUSGABE = join(HIER, 'ausgabe');

const argv = process.argv.slice(2);
const flag = (n) => argv.includes(n);
const wert = (n, s) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : s; };

const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const DEMO   = { email: wert('--demo',  'demo@gen-em.org'),  pw: wert('--demo-pw',  'nadokudemo0815') };
const ADMIN  = { email: wert('--admin', 'admin@gen-em.org'), pw: wert('--admin-pw', 'adminlokal2026') };
const FILTER = (wert('--nur', '') || '').split(',').filter(Boolean);
const BILDER = flag('--bilder');
const MARKE  = wert('--marke', '');     // freie Beschriftung des Laufs im Bericht

/* BREITE UND EINGABEART, weil ein Bild ohne beides nichts belegt. Die Abnahme
 * einer Vorschlagsliste lautet „zwei Breiten, beide Bedienhoehen" — 44 px am
 * Finger, 36 px am Zeigergeraet ab 1024 px (R76). Dieselbe Weiche wie beim
 * Bilderlauf, und derselbe Fund dahinter: `hasTouch` allein reicht nicht,
 * `Emulation.setTouchEmulationEnabled` muss ueber CDP nachgesetzt werden, und
 * im Zeigerlauf wird gar nichts gesendet — `{enabled:false}` ist NICHT das
 * Gegenteil von `{enabled:true}` und kippt die Merkmale auf `none`/`coarse`
 * (S8/AP7, siehe tools/screenshots/LIESMICH.md). */
const BREITEN = (wert('--breiten', '1280') || '1280')
  .split(',').map(b => parseInt(b, 10)).filter(b => b > 0);
const FINGER = flag('--finger');
const hoeheFuer = (b) => (FINGER || b < 1024) ? 44 : 36;

/* EINE ANMELDUNG, MEHRERE BREITEN — und das ist keine Bequemlichkeit.
 *
 * Das Demo-Konto traegt eine Mengenbremse (E-P1-20): 20 Anmeldungen je
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
const browser = await chromium.launch();

async function anmelden(rolle) {
  const konto = rolle === 'admin' ? ADMIN : DEMO;
  const kontext = await browser.newContext({
    ignoreHTTPSErrors: true, viewport: { width: BREITEN[0], height: 900 },
    hasTouch: FINGER,
  });
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
    seite.click('button[type="submit"]'),
  ]);
  if (seite.url().includes('login.php')) {
    throw new Error(`Anmeldung als ${konto.email} gescheitert. Läuft die lokale `
      + 'Installation (sh tools/referenzdatensatz/einspielen/lokal_starten.sh)? '
      + 'Und steht die Mengenbremse des Demo-Kontos voll? Sie lässt 20 '
      + 'Anmeldungen je Fenster und Adresse zu (E-P1-20) und sperrt danach '
      + 'für eine Stunde — mehrere Läufe hintereinander füllen sie.');
  }
  /* Die Eingabeart haelt nicht von selbst (Fund aus S8/AP7): `hasTouch` am
   * Kontext setzt sie, ein Vollseiten-Screenshot verliert sie wieder. Sie
   * wird deshalb vor jeder Breite erneut gesendet — und NUR im Fingerlauf:
   * `{enabled:false}` ist nicht das Gegenteil von `{enabled:true}` und kippt
   * an einem Zeigerkontext die Merkmale auf `none`/`coarse`. */
  const cdp = await kontext.newCDPSession(seite);
  const eingabeart = async () => {
    if (!FINGER) { return; }
    await cdp.send('Emulation.setTouchEmulationEnabled',
      { enabled: true, maxTouchPoints: 5 }).catch(() => {});
  };
  await eingabeart();
  return { kontext, seite, fehler, rolle, eingabeart };
}

/* Faul: Eine Rolle wird angemeldet, wenn der erste Weg sie verlangt. AP1
 * braucht nur `demo` — eine Anmeldung als `admin` waere ein Zug aus der
 * Mengenbremse fuer nichts. */
const rollenSpeicher = {};
async function rolleHolen(name) {
  if (!rollenSpeicher[name]) { rollenSpeicher[name] = await anmelden(name); }
  return rollenSpeicher[name];
}
const rollen = { demo: await rolleHolen('demo') };

/* ---- Adressen, die zum Bestand gehoeren ----------------------------------
 *
 * Dieselbe Regel wie beim Bilderlauf: Kennungen gehoeren zu EINER
 * Installation und stehen nicht in einer eingecheckten Datei. Sie werden ueber
 * dieselben Wege geholt, die eine NutzerIn ginge — und ein nicht aufgeloester
 * Wert ist `null` und laesst den Weg scheitern, statt ihn auf der falschen
 * Seite fahren zu lassen (F-P3-AH, F-P3-AQ). */
async function kennungen() {
  const s = rollen.demo.seite;
  await s.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
  await s.waitForSelector('#missions tbody tr, .kachel', { timeout: 30000 }).catch(() => {});
  await s.waitForTimeout(300);
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
async function kasten(rolle, weg, breite) {
  const r = await rolleHolen(rolle || 'demo');
  const BEDIENHOEHE = hoeheFuer(breite);
  await r.seite.setViewportSize({ width: breite, height: 900 });
  await r.eingabeart();
  return {
    seite: r.seite,
    basis: BASIS,
    kennung: KENNUNG,
    attrappe,
    konsolenfehler: () => r.fehler.slice(),

    /** Seite oeffnen und auf das Stylesheet warten (sonst misst man ungestaltet). */
    async gehZu(adresse) {
      if (!adresse) { throw new Error('Adresse nicht aufgelöst (Bestand leer?)'); }
      await r.seite.goto(adresse, { waitUntil: 'domcontentloaded' });
      await r.seite.waitForFunction(
        () => getComputedStyle(document.documentElement).getPropertyValue('--knopf').trim() !== '',
        null, { timeout: 5000 });
    },

    /**
     * DER KLICK MIT GEHALTENER MAUS — der Kern dieser Probe (E-S9-08).
     *
     * `locator.click()` von Playwright haelt die Taste rund 10 ms; genau
     * deshalb faellt PS-2 damit NICHT auf. Hier wird die Folge von Hand
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
     * AP2 braucht das, weil DERSELBE Kartendialog an fuenf Stellen sitzt und
     * die fuenfte — die systemweiten Standorte — nur der Verwaltung
     * offensteht. Ein Weg, der nur die vier des Demo-Kontos faehrt, meldet
     * „4 von 4" und hat den fuenften nie gesehen. Die Rolle wird faul geholt
     * wie sonst auch: Wer sie nie verlangt, meldet sie nie an und zieht
     * nichts aus der Mengenbremse.
     */
    async rolle(name) { return kasten(name, weg, breite); },

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

    async bild(name) {
      if (!BILDER) { return; }
      mkdirSync(join(AUSGABE, 'bild'), { recursive: true });
      /* Eingabeart MIT IN DEN NAMEN: Unter 1024 px gilt 44 px fuer beide, und
       * ohne sie schreiben der Zeiger- und der Fingerlauf dieselbe Datei —
       * zwei Laeufe, ein Bild, und niemand sieht es. */
      const art = FINGER ? 'finger' : 'zeiger';
      await r.seite.screenshot({
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

const ergebnisse = [];
for (const breite of BREITEN) {
  if (BREITEN.length > 1) {
    console.log(`\n== ${breite} px · ${FINGER ? 'Finger' : 'Zeiger'} · `
              + `Sollhöhe ${hoeheFuer(breite)} px ==`);
  }
  for (const weg of WEGE) {
    let e;
    try {
      const k = await kasten(weg.rolle, weg, breite);
      e = await weg.fahren(k);
      if (!e || typeof e !== 'object') { throw new Error('Weg lieferte kein Ergebnis'); }
    } catch (f) {
      e = { ist: '—', ok: false,
            bemerkung: 'nicht gefahren: ' + (f && f.message ? f.message : f) };
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
md.push('## Grenzen dieses Laufs');
md.push('');
md.push('- Nur Chromium; WebKit und Gecko stehen im Prüfstand nicht zur Verfügung.');
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
