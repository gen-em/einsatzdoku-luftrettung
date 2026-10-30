/* Motorwahl — ein Prüfmittel, drei Engines.
 * ===========================================================================
 *
 * WARUM ES DIESE DATEI GIBT. Seit dem 14.09.2026 liegen drei Playwright-
 * Engines auf dem Prüfstand: Chromium, Firefox (Gecko) und WebKit. Drei
 * Werkzeuge fahren einen Browser — Bilderlauf, Klickprobe, Stilvergleich —,
 * und alle drei brauchen dieselben zwei Griffe: die Wahl des Motors und die
 * Firefox-Voreinstellung unten. Stünden sie dreimal da, stünde die
 * Voreinstellung früher oder später an zwei Stellen richtig und an einer
 * falsch. Sie steht deshalb hier, einmal.
 *
 * DIE FIREFOX-VOREINSTELLUNG IST KEINE FEINHEIT, SONDERN DIE BEDINGUNG DAFÜR,
 * DASS FIREFOX ÜBERHAUPT DASSELBE STYLESHEET MISST. Gemessen am 14.09.2026,
 * leere Seite, 1280 px:
 *
 *     chromium  (hover:hover)=true   (pointer:fine)=true
 *     firefox   (hover:hover)=false  (pointer:fine)=false   <-- ohne Voreinstellung
 *     webkit    (hover:hover)=true   (pointer:fine)=true
 *
 * Headless Firefox meldet also "kein Zeiger, kein Hover". Damit ist der
 * gesamte Media-Block der 36-px-Bedienhöhe
 * `(hover:hover) and (pointer:fine) and (min-width:1024px)`
 * für Firefox unsichtbar — die Klickprobe meldete prompt "Zeile 44 px statt
 * 36", und der Bilderlauf hätte auf jeder Seite ab 1024 px falsche
 * Knopfhöhen gemeldet. Schlimmer als die falsche Meldung ist der stumme Fall:
 * Ein Stilvergleich, der diesen Block nie betritt, meldet dort keine
 * Abweichung — eine schmeichelhafte Null.
 *
 * Gecko bildet die Zeigermerkmale auf zwei Voreinstellungen ab, als Bitmaske:
 * 1 = grob, 2 = fein, 4 = Hover. Gemessen: 2 ergibt `pointer:fine` OHNE
 * `hover:hover`, erst 6 ergibt beides. Es muss also 6 sein.
 *
 * IM FINGERLAUF NICHT. `hasTouch: true` am Kontext setzt in ALLEN DREI
 * Engines `pointer:coarse` und `hover:none` (gemessen, ebenfalls 14.09.2026);
 * dafür braucht Firefox keine Voreinstellung — und bekäme sie es doch, würde
 * sie den Fingerlauf zum Zeigerlauf machen. Deshalb hängt `starten()` am
 * Schalter `finger`.
 */

/* Der Code-Rechner des Zweitfaktors — einer je Sprache, nicht je Werkzeug.
 * Gebraucht wird er unten in `codeSchritt()`. */
import { naechsterCode } from './zweitfaktor/totp.mjs';

export const MOTOREN = ['chromium', 'firefox', 'webkit'];

/** Ein Schriftabruf, den das nächste Laden abgebrochen hat — in Firefox.
 *  Firefox meldet ihn als Konsolenfehler
 *  „downloadable font: download failed … status=2152398850"; das ist
 *  NS_BINDING_ABORTED, also kein Fehler der Seite, sondern ein Werkzeug,
 *  das schneller weiterlädt als die Schrift. Chromium und WebKit sagen dazu
 *  nichts. Jeder andere Schriftfehler bleibt ein Fehler. Eine Stelle für
 *  Bilderlauf und Bedienprobe. */
/** Der Hinweis „ResizeObserver loop completed with undelivered
 *  notifications" — WebKit wirft ihn als Seitenfehler,
 *  Chromium und Firefox nicht. Die Spezifikation sieht ihn vor, wenn eine
 *  Größenänderung erst im nächsten Bild gemeldet werden kann; er ist kein
 *  Fehler der Seite. Der Beobachter in `map_layers.js` bündelt über
 *  requestAnimationFrame und ändert keine Größe. Nur genau dieser Wortlaut
 *  zählt nicht; „ResizeObserver loop limit exceeded" oder jede andere
 *  Meldung bleibt ein Fehler. */
export function harmloserResizeHinweis(text) {
  return /^ResizeObserver loop completed with undelivered notifications\.?$/.test(String(text).trim());
}

/** Ein Abruf, den das VERLASSEN DER SEITE abbrach — in WebKit.
 *  WebKit meldet ihn als „Fetch API cannot load <Adresse> due to access
 *  control checks." und Playwright reicht das als `pageerror` weiter,
 *  obwohl die Anwendung den Fehler fängt (`EdApi.senden()`). Gemessen am
 *  21.10.2026 im Bilderlauf: Der stille Wartungslauf der Startseite lief
 *  noch, als das Werkzeug zur nächsten Breite navigierte; die Meldung kam
 *  zwischen der Navigationsanfrage und dem Wechsel des Dokuments. WebKit 26
 *  tut dasselbe. Chromium und Firefox sagen dazu nichts.
 *
 *  ENG GEFASST, weil ein abgebrochener Abruf am eigenen Server sonst ein
 *  Fehler ist (Selbstprobe des Bilderlaufs, Fall 5). Rauschen ist
 *  die Meldung nur, wenn ALLES zutrifft: genau dieser Wortlaut, die eigene
 *  Herkunft (Gastgeber der Seite) und eine Navigation des Hauptrahmens, die
 *  gerade läuft oder vor weniger als einer Sekunde fertig wurde. Dieselbe
 *  Meldung ohne Seitenwechsel — etwa eine echte Umleitung auf eine fremde
 *  Herkunft — bleibt ein Fehler; Chromium und Firefox scheitern daran
 *  ohnehin hörbar.
 *
 *  `seite` braucht nur `on()`, `mainFrame()` und `url()`; `jetzt` ist für
 *  die Selbstprobe einstellbar. Zurück kommt die Prüffunktion. */
const WEBKIT_ABBRUCH = /due to access control checks\.?$/;
export function abgangBeobachten(seite, { jetzt = Date.now } = {}) {
  /* `laeuft` endet mit dem Wechsel des Dokuments, mit dem Scheitern der
   * Navigationsanfrage — und spätestens nach 30 s, damit eine Navigation,
   * von der nie wieder etwas kommt, nicht für immer alles schluckt. */
  let laeuft = 0, fertigUm = 0;
  const hauptNavigation = (r) => r.isNavigationRequest() && r.frame() === seite.mainFrame();
  seite.on('request', (r) => { if (hauptNavigation(r)) { laeuft = jetzt(); } });
  seite.on('requestfailed', (r) => { if (hauptNavigation(r)) { laeuft = 0; fertigUm = jetzt(); } });
  seite.on('framenavigated', (f) => {
    if (f === seite.mainFrame()) { laeuft = 0; fertigUm = jetzt(); }
  });
  return (text) => {
    const t = String(text).trim();
    if (!WEBKIT_ABBRUCH.test(t)) { return false; }
    let gastgeber = '';
    try { gastgeber = new URL(seite.url()).host; } catch { return false; }
    if (!gastgeber || !t.includes('/' + gastgeber + '/')) { return false; }
    return (laeuft > 0 && jetzt() - laeuft < 30000) || (fertigUm > 0 && jetzt() - fertigUm < 1000);
  };
}

export function abgebrochenerSchriftabruf(text) {
  return /downloadable font: download failed/.test(text) && /status=2152398850\b/.test(text);
}

/* Fein + Hover als Gecko-Bitmaske. Siehe Kopfkommentar — 2 allein genügt
 * nicht, 6 ist die Zahl. */
const ZEIGER_FIREFOX = {
  'ui.primaryPointerCapabilities': 6,
  'ui.allPointerCapabilities': 6,
};

/** Liest `--motor <name>` aus einer Argumentliste (Vorgabe: chromium).
 *  Ein unbekannter Name ist ein Abbruch, keine stille Rückfallebene: Wer
 *  `--motor gecko` tippt, soll das erfahren und nicht ungewollt Chromium
 *  messen. */
export function motorWahl(argv) {
  const i = argv.indexOf('--motor');
  const name = i >= 0 ? argv[i + 1] : (process.env.MOTOR || 'chromium');
  if (!MOTOREN.includes(name)) {
    console.error(`Unbekannter Motor "${name}". Erlaubt: ${MOTOREN.join(', ')}.`);
    process.exit(2);
  }
  return name;
}

/* ===========================================================================
 * WebKit stolpert beim Seitenwechsel
 * ===========================================================================
 *
 * In diesem Container bricht WebKit sporadisch einen Seitenwechsel mit
 * „WebKit encountered an internal error" ab — in jedem vollen Lauf der
 * Bedienprobe an ein bis drei anderen Wegen, einzeln sind sie grün; bekannt
 * seit Langem. Ein `goto` oder `reload`, der GENAU daran scheitert,
 * wird hier einmal wiederholt; scheitert er wieder, ist er rot. Jede
 * Wiederholung steht als Zeile `WebKit-Wiederholung: …` im Protokoll, und
 * `pruefen.sh` zählt sie.
 *
 * NICHT wiederholt wird ein Seitenwechsel nach einem Klick
 * (`waitForNavigation`): Ihn zu wiederholen hieße, den Klick zu wiederholen,
 * und das ist eine andere Bedienung. Er bleibt rot und benannt.
 *
 * Gepatcht wird die Klasse der Seite, einmal je Lauf — so trifft es jede
 * Seite jedes Kontexts, ohne dass ein Werkzeug etwas tun muss. Nur WebKit;
 * Chromium und Firefox bleiben unberührt. */
export const WEBKIT_STOLPERN = 'WebKit encountered an internal error';
async function webkitWiederholung(browser) {
  const kontext = await browser.newContext();
  const seite = await kontext.newPage();
  const klasse = Object.getPrototypeOf(seite);
  await kontext.close();
  if (klasse.__webkitWiederholung) { return; }
  klasse.__webkitWiederholung = true;
  for (const methode of ['goto', 'reload']) {
    const roh = klasse[methode];
    klasse[methode] = async function (...args) {
      try {
        return await roh.apply(this, args);
      } catch (e) {
        if (!String((e && e.message) || e).includes(WEBKIT_STOLPERN)) { throw e; }
        console.error(`WebKit-Wiederholung: ${methode} ${methode === 'goto' ? args[0] : this.url()}`);
        return await roh.apply(this, args);
      }
    };
  }
}

/* ===========================================================================
 * WebKit, der Screenshot und die CSP
 * ===========================================================================
 *
 * Playwrights `screenshot()` legt in WebKit ein eigenes `<style>` in die
 * Seite (Textcursor und Animationen). Die CSP der Anwendung verbietet
 * Inline-Stile, WebKit meldet den Verstoß an `api/csp_bericht.php` — bei
 * JEDEM Screenshot. Gemessen am 21.10.2026 auf `login.php`, WebKit 26.0 und
 * 27.2 gleich: Laden 0 Verstöße, jeder Screenshot 1 (`style-src-elem`,
 * `blocked-uri: inline`); Chromium und Firefox 0. Folgen: die örtliche
 * `csp_berichte` voll falscher Einträge (70× `index.php` nach einem
 * Bilderlauf), und auf den Wartungsseiten 20 Konsolenfehler, weil der
 * Endpunkt dort 503 antwortet.
 *
 * Der Bericht ist von einem echten Inline-Stil der Anwendung NICHT zu
 * unterscheiden — gleicher Wortlaut, keine Quelldatei. Ein Filter auf den
 * Inhalt geht also nicht. Deshalb beantwortet ab dem ERSTEN Screenshot in
 * einem Kontext der Prüfstand selbst den Bericht mit 204, statt ihn zum
 * Server zu lassen. Die CSP bleibt dabei in Kraft (kein `bypassCSP`): Was sie
 * verbietet, wird weiter nicht angewandt, nur nicht mehr gemeldet.
 * **Preis:** Ein echter Verstoß NACH dem ersten Screenshot in einem Kontext
 * kommt in WebKit nicht mehr in `csp_berichte` an. Den Meldeweg misst die
 * CSP-Browserprobe, und zwar vor ihrem einen Screenshot (Abschnitt 7).
 * Nicht gefangen: `elementHandle.screenshot()` — kein Werkzeug nutzt es.
 * Die Zahl der abgefangenen Berichte steht am Ende des Laufs im Protokoll. */
let cspStumm = 0;
async function webkitScreenshotOhneBericht(browser) {
  const kontext = await browser.newContext();
  const seite = await kontext.newPage();
  const seitenKlasse = Object.getPrototypeOf(seite);
  const fundKlasse = Object.getPrototypeOf(seite.locator('body'));
  await kontext.close();
  if (seitenKlasse.__cspStumm) { return; }
  seitenKlasse.__cspStumm = true;
  /* AM KONTEXT, NICHT AN DER SEITE: Eine Route mit `page.route()` sieht den
   * Bericht in WebKit nicht — gemessen am 21.10.2026, zwei Screenshots, zwei
   * Berichte beim Server. WebKit schickt ihn offenbar nicht als Anfrage der
   * Seite. Die Kontext-Route fängt ihn. Sie gilt damit für alle Seiten des
   * Kontexts, ab dem ersten Screenshot in einer davon. */
  const stummSchalten = async (s) => {
    const k = s.context();
    if (k.__cspStumm) { return; }
    k.__cspStumm = true;
    await k.route('**/api/csp_bericht.php', (r) => { cspStumm++; return r.fulfill({ status: 204 }); });
  };
  const rohSeite = seitenKlasse.screenshot;
  seitenKlasse.screenshot = async function (...args) {
    await stummSchalten(this);
    return rohSeite.apply(this, args);
  };
  const rohFund = fundKlasse.screenshot;
  fundKlasse.screenshot = async function (...args) {
    await stummSchalten(this.page());
    return rohFund.apply(this, args);
  };
  process.on('exit', () => {
    if (cspStumm) { console.error(`WebKit-Screenshot: ${cspStumm} CSP-Berichte örtlich beantwortet (motor.mjs, Nr. 393)`); }
  });
}

/* Der abgebrochene Abruf beim Seitenwechsel — EINMAL für alle Werkzeuge
 * Wer in WebKit `seite.on('pageerror', …)` anmeldet, bekommt
 * ihn nicht gemeldet; was Rauschen ist, entscheidet `abgangBeobachten()`
 * oben. Bis dahin stand der Filter nur in Bilderlauf und Bedienprobe, und am
 * 21.10.2026 meldete die Wartungslaufprobe genau diese Zeile als Fehler
 * (einer von fünf Läufen). 25 Werkzeuge zählen Seitenfehler; der Filter
 * gehört an die Stelle, über die sie alle starten. Nur WebKit — die anderen
 * beiden sagen dazu nichts. Die Zahl steht am Ende im Protokoll. */
let abgangStumm = 0;
async function webkitAbgangLeise(browser) {
  const kontext = await browser.newContext();
  const seite = await kontext.newPage();
  const klasse = Object.getPrototypeOf(seite);
  await kontext.close();
  if (klasse.__abgangLeise) { return; }
  klasse.__abgangLeise = true;
  /* `addListener`, NICHT `on`: Playwright setzt im Konstruktor jeder Seite
   * `this.on = this.addListener` als EIGENE Eigenschaft (eventEmitter.ts) —
   * ein Patch an `on` des Prototyps wird davon verdeckt. Gemessen am
   * 21.10.2026: am Prototyp gepatcht, an der Seite nicht angekommen, und
   * der Bilderlauf meldete die Zeile weiter. */
  const roh = klasse.addListener;
  klasse.addListener = function (ereignis, fn) {
    if (ereignis !== 'pageerror') { return roh.call(this, ereignis, fn); }
    if (!this.__abgang) { this.__abgang = abgangBeobachten(this); }
    return roh.call(this, ereignis, (e) => {
      if (this.__abgang(e && e.message)) { abgangStumm++; return; }
      fn(e);
    });
  };
  process.on('exit', () => {
    if (abgangStumm) { console.error(`WebKit-Seitenwechsel: ${abgangStumm} abgebrochene Abrufe nicht als Seitenfehler gezählt (motor.mjs, Nr. 393)`); }
  });
}

/** Startet den gewählten Motor. `pw` ist das geladene Playwright-Modul.
 *  `finger` sagt, ob der Lauf ein Fingergerät nachstellt; `optionen` reicht
 *  weitere Startangaben durch (der Stilvergleich setzt so seinen
 *  `executablePath` für Chromium). */
export async function starten(pw, name, { finger = false, optionen = {} } = {}) {
  const auf = (name === 'firefox' && !finger)
    ? { ...optionen, firefoxUserPrefs: { ...ZEIGER_FIREFOX, ...(optionen.firefoxUserPrefs || {}) } }
    : { ...optionen };
  const browser = await pw[name].launch(auf);
  if (name === 'webkit') {
    await webkitWiederholung(browser);
    await webkitScreenshotOhneBericht(browser);
    await webkitAbgangLeise(browser);
  }
  /* DER BELEG, WELCHE ENGINE LIEF. Eine Probe, die
   * „grün in Firefox" meldet, aber still Chromium startete, wäre die
   * schmeichelhafte Null aus dem Kopf dieser Datei. Die Zeile geht nach
   * stderr, damit sie keine Ausgabe stört, die ein Aufrufer liest, und
   * steht in jedem Protokoll des Prüfstands. */
  console.error(`Motor: ${name} ${browser.version()}`);
  return browser;
}

/* ===========================================================================
 * Der Weg nach draußen — ohne die Zertifikatsprüfung abzuschalten
 * ===========================================================================
 *
 * Der ausgehende Verkehr der Arbeitsumgebung läuft über einen Proxy mit
 * eigener Zertifizierungsstelle. Node kennt sie (`NODE_EXTRA_CA_CERTS`), die
 * Browser nicht — und zwar zwei von dreien. Gemessen am 21.09.2026 gegen die
 * Prüfanlage:
 *
 *     chromium  ERR_CERT_AUTHORITY_INVALID
 *     firefox   SEC_ERROR_UNKNOWN_ISSUER
 *     webkit    HTTP 200          (nimmt den Systemspeicher)
 *     node      HTTP 200
 *
 * `ignoreHTTPSErrors` wäre die kurze Antwort und die falsche: Ein Prüfmittel,
 * das jedes Zertifikat nimmt, kann die Prüfanlage nicht mehr von einer
 * untergeschobenen unterscheiden — es misst dann nichts mehr über TLS.
 * `route.fetch()` führt die Anfrage stattdessen im Node-Prozess aus, der die
 * Stelle kennt; geprüft wird weiterhin, nur an einer anderen Stelle.
 */

/* Örtliche Adressen gehen NICHT über die Route. Gemessen am 21.09.2026: Mit
 * Umleitung scheiterte `https://127.0.0.1:8443/login.php` in allen drei
 * Engines (`ERR_FAILED`, `NS_ERROR_FAILURE`, „Blocked by Web Inspector"),
 * weil der Node-Stack auch sie durch den Proxy schickt — und der kennt den
 * Wirt nicht. Die örtliche Anlage braucht die Route ohnehin nicht: Ihre
 * Zertifizierungsstelle liegt im Systemspeicher. */
const OERTLICH = /^https?:\/\/(127\.0\.0\.1|localhost|\[::1\])(:|\/|$)/i;

/** Leitet die Anfragen eines Kontexts nach DRAUSSEN durch den Node-Stack.
 *  Ohne `ignoreHTTPSErrors` — siehe Kommentar oben. Örtliche Adressen laufen
 *  unverändert durch. Ein Fehlschlag bricht die Anfrage ab, statt sie
 *  stillschweigend durchzulassen. */
export async function proxyRoute(kontext) {
  await kontext.route('**/*', async (route) => {
    if (OERTLICH.test(route.request().url())) { await route.fallback(); return; }
    try {
      await route.fulfill({ response: await route.fetch() });
    } catch {
      await route.abort();
    }
  });
}

/** Sagt, ob eine Basisadresse auf diesen Rechner zeigt. */
export function istOertlich(basis) { return OERTLICH.test(String(basis)); }

/* EINSATZLISTE AUF DEM WEG IN DEN BROWSER VERVIELFACHEN.
 *
 * Die Zeitraumuebersicht zeigt hoechstens 200 Zeilen, und
 * was dann geschieht — Nachladezeile, „200 angezeigt", der Sprung aus einer
 * Extremwert-Kachel zu einer Zeile jenseits der 200 —, ist am Demo-Konto
 * nicht zu sehen: Es hat 109 Einsaetze. Einen groesseren Bestand dafuer
 * einzuspielen hiesse, das Demo-Konto zu veraendern, das Bilderlauf und
 * Bedienprobe unveraendert brauchen.
 *
 * Deshalb wie in einer frueheren Sondierung: Die Antwort wird auf dem
 * Weg in den Browser vervielfacht, und der Browser geht damit den echten
 * Weg — entschluesseln, sortieren, zeichnen. Jede Kopie bekommt eine eigene
 * Kennung (Versatz 10^6 je Kopie), alles andere bleibt, auch der
 * verschluesselte Block: Er ist je Einsatz verschluesselt und geht deshalb
 * auch als Kopie auf. Eine Kopie ist kein Einsatz auf dem Server — ein
 * Klick auf ihre Zeile fuehrt ins Leere, und dafuer ist sie nicht da.
 *
 * DIE ANWENDUNG BEKOMMT DAFUER NICHTS. Kein Parameter, keine Prueftuer:
 * Das Pruefmittel aendert, was ankommt, nicht, was der Server tut.
 *
 * `ziel` ist eine Seite oder ein Kontext; zurueck kommt eine Funktion, die
 * die Route wieder entfernt. */
export async function einsaetzeVervielfachen(ziel, muster, faktor) {
  const umleiten = async (route) => {
    const antwort = await route.fetch();
    const daten = await antwort.json();
    if (Array.isArray(daten.missions)) {
      const vorlage = daten.missions;
      daten.missions = [];
      for (let k = 0; k < faktor; k++) {
        for (const m of vorlage) {
          daten.missions.push(k === 0 ? m : { ...m, id: m.id + k * 1000000 });
        }
      }
    }
    await route.fulfill({ response: antwort, json: daten });
  };
  await ziel.route(muster, umleiten);
  return () => ziel.unroute(muster, umleiten);
}

/** Macht den Kontext, der zur Basisadresse passt — und trifft damit die
 *  TLS-Entscheidung EINMAL statt in jedem Werkzeug neu.
 *
 *  ZWEI LAGEN, ZWEI ANTWORTEN, und die Unterscheidung ist der Kern:
 *
 *  - ÖRTLICH: `lokal_starten.sh` erzeugt eine eigene Zertifizierungsstelle
 *    und legt sie in den Systemspeicher. WebKit nimmt sie von dort, Chromium
 *    und Firefox nicht (gemessen 21.09.2026: `ERR_CERT_AUTHORITY_INVALID`
 *    bzw. `SEC_ERROR_UNKNOWN_ISSUER`). Hier ist `ignoreHTTPSErrors` richtig
 *    und harmlos: Die Stelle ist die, die dieser Behälter vor Minuten selbst
 *    angelegt hat, und auf 127.0.0.1 sitzt niemand dazwischen. Es ist der
 *    Weg, den die vorhandenen Prüfmittel längst gehen.
 *  - NACH DRAUSSEN: Dort wäre dasselbe falsch — ein Prüfmittel, das jedes
 *    Zertifikat nimmt, kann die Prüfanlage nicht mehr von einer
 *    untergeschobenen unterscheiden. Deshalb die Route: geprüft wird weiter,
 *    nur im Node-Prozess, der die Proxy-Stelle kennt. */
export async function kontextMachen(browser, { basis, optionen = {} } = {}) {
  const oertlich = istOertlich(basis);
  const kontext = await browser.newContext({ ignoreHTTPSErrors: oertlich, ...optionen });
  if (!oertlich) await proxyRoute(kontext);
  return kontext;
}

/* Der Dialog „Schlüsselblatt bestätigen" (server/blatt_dialog.php) erscheint
 * alle drei Monate nach der Anmeldung und legt sich vor die Seite. Jedes
 * Werkzeug, das sich anmeldet, muss ihn schließen können — sonst hängt es
 * alle drei Monate an einer Stelle, die mit seiner Messung nichts zu tun hat,
 * und meldet einen Fehlschlag, den niemand zuordnet. „Später" heißt: bis zur
 * nächsten Anmeldung; es hinterlässt nichts. */
export async function blattDialogSchliessen(seite, { frist = 8000 } = {}) {
  /* ER ÖFFNET SICH NACH DER ANMELDUNG, NICHT MIT IHR. `schluesselblatt.js`
   * ruft `showModal()` erst, wenn das Skript gelaufen ist. Gemessen am
   * 21.09.2026 in allen drei Engines: unmittelbar nach dem Verschwinden des
   * Passwortfeldes ist `dialog.open === false`, zwei Sekunden später `true`.
   * Wer sofort nachsieht, findet nichts und meldet „kein Dialog" — und
   * stolpert dann eine Messung später über ihn.
   *
   * Deshalb zwei Stufen: Liegt das Element gar nicht im Markup, ist der Fall
   * erledigt (der Server bindet es nur ein, wenn er fragen will) und es wird
   * NICHT gewartet. Liegt es da, wird auf `open` gewartet. */
  const dialog = seite.locator('[data-blatt-dialog]');
  if (await dialog.count() === 0) return false;
  try {
    await seite.waitForFunction(() => {
      const d = document.querySelector('[data-blatt-dialog]');
      return !!d && d.open === true;
    }, null, { timeout: frist });
  } catch {
    return false;             // im Markup, aber er fragt heute nicht
  }
  await seite.locator('[data-blatt-dialog] [data-blatt-spaeter]').click();
  await seite.waitForFunction(() => {
    const d = document.querySelector('[data-blatt-dialog]');
    return !d || d.open === false;
  }, null, { timeout: 5000 }).catch(() => {});
  return true;
}

/** Meldet an und räumt den Schlüsselblatt-Dialog weg.
 *
 *  ZWEI GRIFFE, DIE BEIDE NAHELIEGEN UND BEIDE FALSCH SIND — gemessen am
 *  21.09.2026 gegen die Prüfanlage:
 *
 *  1. NICHT AUF `domcontentloaded` WARTEN. Der Absenden-Vorgang wird von
 *     `login.php` abgefangen: Die Seite holt erst das Salz, leitet dann für
 *     jede genannte Rundenzahl einen Schlüssel ab (310 000 Runden, während
 *     einer Anhebung zweimal) und schickt das Formular erst danach ab. Wer
 *     auf den Klick hin sofort weiterliest, sieht `tokens` leer und hält eine
 *     laufende Anmeldung für eine gescheiterte.
 *  2. NICHT DIE ADRESSE PRÜFEN. Nach der Anmeldung steht die Tagesübersicht
 *     UNTER `/login.php` — es gibt keine Umleitung. Eine Prüfung auf
 *     „Adresse enthält login.php nicht mehr" wartet neunzig Sekunden auf
 *     etwas, das nie eintritt, und meldet dann „nicht angemeldet", während
 *     der Seitentitel „Tagesübersicht" lautet.
 *
 *  Früher hieß es hier: Das verlässliche Merkmal ist das Verschwinden
 *  des Passwortfeldes (gemessene Dauer danach: 1,1 s). ALLEIN IST ES DAS
 *  NICHT MEHR, und daraus folgt ein dritter falscher Griff — nicht
 *  gemessen, sondern aus `login.php` und `auth_guard.php` gelesen:
 *
 *  3. NICHT NUR AUF DAS PASSWORTFELD SEHEN. Auch der Code-Schritt
 *     des Zweitfaktors hat keines, und das Einrichtungstor ebenso wenig. Wer
 *     nur darauf sieht, meldet für ein Konto mit Zweitfaktor „angemeldet",
 *     steht in Wahrheit vor der Frage nach dem Code — und misst von da an die
 *     Anmeldeseite. Genau das ist die alte Falle: eine grüne Zahl über Bildern der
 *     Anmeldeseite.
 *
 *  Gewartet wird deshalb auf einen von DREI Ausgängen (`nachDemPasswort()`
 *  darunter):
 *    - der Code-Schritt (`#codeform` da): Code rechnen, eintragen, absenden
 *      — `codeSchritt()`;
 *    - angemeldet: weder Passwortfeld noch `#codeform`, und die Seite ist
 *      nicht das Einrichtungstor;
 *    - das Passwortfeld bleibt: gescheitert, nach Ablauf der Frist.
 *
 *  Gibt `dialog` zurück (lag der Schlüsselblatt-Dialog davor?), `code` (kam
 *  ein Code-Schritt?) und im Fehlerfall den Zustandstext der Seite —
 *  „angemeldet: nein" allein sagt nicht, woran es lag. */
export async function anmelden(seite, { basis, konto, pass, frist = 90000 }) {
  const wurzel = basis.replace(/\/$/, '');
  await seite.goto(`${wurzel}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', konto);
  await seite.fill('input[name="password"]', pass);
  await seite.click('#loginform button[type="submit"]');
  const { angemeldet, code, meldung } = await nachDemPasswort(seite, { frist });
  const dialog = angemeldet ? await blattDialogSchliessen(seite) : false;
  return { angemeldet, dialog, url: seite.url(), meldung, code };
}

/* ===========================================================================
 * Der Code-Schritt des Zweitfaktors
 * ===========================================================================
 *
 * WAS SICH GEÄNDERT HAT. Ein Konto mit eingeschaltetem Zweitfaktor bekommt
 * nach dem richtigen Passwort KEINE Sitzung, sondern eine Weiterleitung (303)
 * zurück auf `login.php`, die dann nach dem Code fragt (`#codeform`). Das
 * Prüfkonto `admin@example.invalid` ist eine BetreiberIn und hat deshalb einen —
 * mit bekanntem Geheimnis, damit die Werkzeuge den Code selbst rechnen
 * können (`tools/zweitfaktor/totp.mjs`). Jedes Werkzeug, das sich mit ihm
 * anmeldet, muss diesen Schritt gehen. Er steht hier und nicht in jedem
 * Werkzeug, aus demselben Grund wie die Firefox-Voreinstellung oben: in
 * jedem Werkzeug einzeln geschrieben, stünde er früher oder später in einem
 * falsch.
 *
 * DAS EINRICHTUNGSTOR IST KEIN ERFOLG. Eine Pflichtrolle (Support, Admin,
 * BetreiberIn) OHNE Zweitfaktor bekommt zwar eine Sitzung, landet aber auf
 * `zweitfaktor.php`, und keine andere Seite ist erreichbar. Kein
 * Passwortfeld, kein Code-Formular, und die Adresse enthält nicht einmal
 * `login.php` — nach jedem der alten Merkmale also „angemeldet", und jede
 * folgende Messung mäße das Tor. Es wird deshalb ausdrücklich als Scheitern
 * gemeldet, und zwar mit dem Weg hinaus.
 *
 * AN DER ADRESSE, UND HIER IST DAS RICHTIG (anders als Griff 2 oben):
 * `auth_guard.php` schickt per `Location` dorthin, die Adresse wechselt also
 * wirklich. */
export const EINRICHTUNGSTOR_MELDUNG = 'Einrichtungstor des Zweitfaktors (zweitfaktor.php): '
  + 'Prüfkonto ohne Zweitfaktor — `php tools/zweitfaktor/pruefkonto.php` fahren';

/** Steht die Seite im Einrichtungstor des Zweitfaktors? */
export function istEinrichtungstor(seite) {
  try { return new URL(seite.url()).pathname.endsWith('/zweitfaktor.php'); } catch { return false; }
}

/* Die Meldung der Seite in einer Zeile — Zustandszeile, Feldfehler,
 * Meldung. Der Code-Schritt trägt seine („Der Code passt nicht.") in
 * derselben `[role="alert"]` wie das Passwortformular. */
async function seitenMeldung(seite) {
  return (await seite.locator('#state, .feld-fehler, [role="alert"]')
    .allTextContents().catch(() => [])).join(' ').replace(/\s+/g, ' ').trim();
}

/** Geht den Code-Schritt, wenn die Seite ihn zeigt, und sieht danach nach,
 *  ob das Einrichtungstor im Weg steht.
 *
 *  FÜR WERKZEUGE MIT EIGENEM ANMELDEWEG. Sie schicken das Passwort selbst ab
 *  und warten selbst (meist `waitForNavigation`); danach rufen sie dies —
 *  und ERST DANN ihre eigene Prüfung, etwa auf `login.php` in der Adresse.
 *  Zeigt die Seite keinen Code-Schritt, bleibt es bei der Torprüfung; ein
 *  Konto ohne Zweitfaktor kostet es also nichts.
 *
 *  `ok` HEISST „DER ZWEITE FAKTOR STEHT NICHT IM WEG", NICHT „ANGEMELDET".
 *  Ein falsches Passwort lässt `ok` stehen; das erkennt das Werkzeug wie
 *  bisher. `code` sagt, ob ein Code verlangt wurde, `meldung` im
 *  Fehlerfall, woran es lag. Die Funktion wirft nicht.
 *
 *  EIN ZWEITER VERSUCH, KEIN DRITTER. Der Server nimmt keinen Zeitschritt
 *  zweimal. Der Rechner führt dafür einen Zähler über alle
 *  Prozesse (Kopf von `tools/zweitfaktor/totp.php`) — aber er sieht nur, was
 *  über ihn lief: einen Code von Hand oder einen Rechner mit anderem
 *  `TMPDIR` nicht. Erscheint `#codeform` nach dem Absenden wieder, ist das
 *  meist genau dieser Fall, und der nächste Code löst ihn. Ein dritter
 *  Versuch wäre keine Abhilfe mehr, sondern ein Schritt auf die Sperre nach
 *  fünf Fehlversuchen zu, die dann auch den nächsten Lauf träfe. */
export async function codeSchritt(seite, { frist = 30000 } = {}) {
  let code = false;
  try {
    if (await seite.locator('#codeform').count() > 0) {
      code = true;
      for (let versuch = 0; versuch < 2; versuch++) {
        await seite.fill('#codeform input[name="code"]', await naechsterCode());
        /* Ein schlichtes POST ohne Skript davor — anders als beim
         * Passwortformular (Griff 1 oben) ist `waitForNavigation` hier also
         * richtig. Richtiger Code: 302 auf `index.php`; falscher: dieselbe
         * Seite mit `#codeform` und Meldung. */
        await Promise.all([
          seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: frist }),
          seite.click('#codeform button[type="submit"]'),
        ]);
        if (await seite.locator('#codeform').count() === 0) { break; }
      }
      if (await seite.locator('#codeform').count() > 0) {
        return { ok: false, code, meldung: 'Code-Schritt: zweimal abgewiesen — '
          + ((await seitenMeldung(seite)) || 'ohne Meldung der Seite') };
      }
      /* ZURÜCK AUF DEM PASSWORTFORMULAR heißt: gesperrt nach fünf
       * Fehlversuchen, oder die halbe Anmeldung (fünf Minuten) ist
       * abgelaufen. Beides ist kein „angemeldet", auch wenn kein
       * `#codeform` mehr dasteht. */
      if (await seite.locator('input[name="password"]').count() > 0) {
        return { ok: false, code, meldung: 'Code-Schritt: zurück auf dem Passwortformular — '
          + ((await seitenMeldung(seite)) || 'ohne Meldung der Seite') };
      }
    }
  } catch (e) {
    return { ok: false, code,
             meldung: 'Code-Schritt: ' + String((e && e.message) || e).split('\n')[0] };
  }
  if (istEinrichtungstor(seite)) { return { ok: false, code, meldung: EINRICHTUNGSTOR_MELDUNG }; }
  return { ok: true, code, meldung: '' };
}

/** Wartet nach dem Absenden des Passworts auf einen der drei Ausgänge
 *  (Kommentar über `anmelden()`) und geht den Code-Schritt, wenn er kommt.
 *  Für Werkzeuge, die das Passwort selbst abschicken, aber nicht selbst
 *  warten; `anmelden()` ist dasselbe mit dem Aufruf der Seite davor und dem
 *  Schlüsselblatt-Dialog danach. Rückgabe `{ angemeldet, code, meldung }`.
 *
 *  `document.readyState` GEHÖRT IN DIE BEDINGUNG. Ein Dokument, das der
 *  Browser noch liest, hat womöglich weder Passwortfeld noch `#codeform` und
 *  sähe aus wie „angemeldet" — ein halb gelesener Code-Schritt genauso wie
 *  eine halb gelesene Fehlerseite. Die Bedingung sieht deshalb erst hin,
 *  wenn das Dokument gelesen ist; auf Bilder und Kartenkacheln (`load`)
 *  wartet sie nicht. */
/** Auf der ersten Seite nach der Anmeldung abwarten, bis das Schlüsselpaar
 *  des Kontos steht — an einer Stelle für alle Werkzeuge.
 *
 *  Die Seite legt es still an — aber nur sie, weil nur sie das Anmelde-Token
 *  hat. Wer vorher weitergeht, bricht das Anlegen ab, und das Konto hat bis
 *  zur nächsten Anmeldung keins. In Chromium ging das
 *  meist gut, in Firefox und WebKit nie: Die Freigabeprobe und der
 *  CSV-Kreislauf scheiterten daran, der edbak-Kreislauf wartete schon
 *  selbst. Diese Schleife ist seine, hierher geholt.
 *
 *  Nichts zu warten, wenn die Seite kein Paar pflegt (kein `KP_STAND`) oder
 *  es schon eines gibt. Höchstens `frist` ms; Rückgabe true, wenn das Paar
 *  steht oder nichts zu warten war, sonst false — der Aufrufer meldet es. */
export async function paarAbwarten(seite, { frist = 20000 } = {}) {
  const bis = Date.now() + frist;
  for (;;) {
    const stand = await seite.evaluate(async () => {
      if (typeof KP_STAND === 'undefined' || KP_STAND !== 'fehlt' || typeof EdApi === 'undefined') {
        return 'nichts';
      }
      try {
        const a = await EdApi.postJson('api/kontopaar.php', { aktion: 'zustand' });
        return a && a.ok && a.daten ? a.daten.stand : null;
      } catch (e) { return null; }
    }).catch(() => null);
    if (stand === 'da' || stand === 'nichts') { return true; }
    if (Date.now() > bis) { return false; }
    await seite.waitForTimeout(500);
  }
}

export async function nachDemPasswort(seite, { frist = 90000 } = {}) {
  try {
    await seite.waitForFunction(() => document.readyState !== 'loading'
      && (!!document.querySelector('#codeform')
          || !document.querySelector('input[name="password"]')),
      null, { timeout: frist });
  } catch {
    return { angemeldet: false, code: false, meldung: (await seitenMeldung(seite))
      || '(kein Zustandstext — die Seite rechnet noch oder das Feld blieb stehen)' };
  }
  const zf = await codeSchritt(seite);
  if (!zf.ok) { return { angemeldet: false, code: zf.code, meldung: zf.meldung }; }
  const angemeldet =
    (await seite.locator('input[name="password"], #codeform').count()) === 0;
  if (angemeldet) { await paarAbwarten(seite); }
  return { angemeldet, code: zf.code,
           meldung: angemeldet ? '' : ((await seitenMeldung(seite)) || '(ohne Meldung der Seite)') };
}
