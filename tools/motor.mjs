/* Motorwahl — ein Prüfmittel, drei Engines (AP3b, Backlog Nr. 183).
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
 * `(hover:hover) and (pointer:fine) and (min-width:1024px)` (E-S8-09/R76)
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

export const MOTOREN = ['chromium', 'firefox', 'webkit'];

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

/** Startet den gewählten Motor. `pw` ist das geladene Playwright-Modul.
 *  `finger` sagt, ob der Lauf ein Fingergerät nachstellt; `optionen` reicht
 *  weitere Startangaben durch (der Stilvergleich setzt so seinen
 *  `executablePath` für Chromium). */
export async function starten(pw, name, { finger = false, optionen = {} } = {}) {
  const auf = (name === 'firefox' && !finger)
    ? { ...optionen, firefoxUserPrefs: { ...ZEIGER_FIREFOX, ...(optionen.firefoxUserPrefs || {}) } }
    : { ...optionen };
  return await pw[name].launch(auf);
}

/* ===========================================================================
 * Der Weg nach draußen — ohne die Zertifikatsprüfung abzuschalten (PK-02)
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
 *  Das verlässliche Merkmal ist das Verschwinden des Passwortfeldes.
 *  Gemessene Dauer danach: 1,1 s.
 *
 *  Gibt `dialog` zurück (lag der Schlüsselblatt-Dialog davor?) und im
 *  Fehlerfall den Zustandstext der Seite — „angemeldet: nein" allein sagt
 *  nicht, woran es lag. */
export async function anmelden(seite, { basis, konto, pass, frist = 90000 }) {
  const wurzel = basis.replace(/\/$/, '');
  await seite.goto(`${wurzel}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', konto);
  await seite.fill('input[name="password"]', pass);
  await seite.click('button[type="submit"]');
  let meldung = '';
  try {
    await seite.waitForFunction(
      () => !document.querySelector('input[name="password"]'), null, { timeout: frist });
  } catch {
    meldung = (await seite.locator('#state, .feld-fehler, [role="alert"]')
      .allTextContents().catch(() => [])).join(' ').replace(/\s+/g, ' ').trim()
      || '(kein Zustandstext — die Seite rechnet noch oder das Feld blieb stehen)';
  }
  const angemeldet = (await seite.locator('input[name="password"]').count()) === 0;
  const dialog = angemeldet ? await blattDialogSchliessen(seite) : false;
  return { angemeldet, dialog, url: seite.url(), meldung };
}
