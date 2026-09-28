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
 *                 Backup erstellen — die drei Wege aus E-S2-24.
 *   JSON-Größe    Die größte Zeichenkette, die durch JSON.parse oder
 *                 JSON.stringify läuft. Z3 setzt hier 10 MB. Diese Zahl ist
 *                 der eigentliche Grund, aus dem der heutige Backup-Weg
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
const konto   = process.env.MESSSTAND_KONTO || 'messstand@example.invalid';
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
  const z = { pbkdf2: 0, jsonMax: 0, jsonParse: 0, jsonStringify: 0, ersteZeile: null, lang: [] };
  window.__messstand = z;

  /* DIE LANGAUFGABEN DER SEITE (R4-27): Beginn und Ende jeder Aufgabe über
   * 50 ms. Aus ihnen liest der Zeitraumschritt, wann der Hauptfaden zur
   * Ruhe kommt — die zweite Zahl neben „sichtbar". */
  try {
    new PerformanceObserver(function (liste) {
      for (const e of liste.getEntries()) { z.lang.push([e.startTime, e.startTime + e.duration]); }
    }).observe({ type: 'longtask', buffered: true });
  } catch (e) { /* ohne Langaufgaben bleibt `fertig_s` leer — gesagt, nicht geraten */ }

  /* DIE ERSTE TABELLENZEILE, VON DER SEITE SELBST GEMESSEN (R4-17). Die
   * Dauer unten ist die Zeit, bis Playwright die Zeile SIEHT — und das kann
   * es erst, wenn der gedrosselte Hauptfaden frei wird. Solange er
   * entschluesselt und das Layout rechnet, steht die Zeile schon da. Am
   * 27.09.2026 gemessen: Zeile im DOM nach 1,9 s, Dauer 9,2 s. Beides ist
   * eine Auskunft („wann sieht man etwas", „wann ist die Seite fertig"),
   * und keine ersetzt die andere. */
  new MutationObserver(function (_, beobachter) {
    const tb = document.getElementById('rangebody');
    if (tb && tb.firstElementChild) {
      z.ersteZeile = performance.now();
      beobachter.disconnect();
    }
  }).observe(document, { childList: true, subtree: true });

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

/* ENTSPERREN, OHNE DIE MESSUNG ZU VERFAELSCHEN (S2/AP9).
 *
 * DIESE FUNKTION HAT VIER MESSWERTE VERDORBEN, und zwar lautlos. Sie wartete
 * VIER SEKUNDEN auf einen Entsperr-Dialog. Ist die Sitzung bereits entsperrt
 * — der Regelfall, wenn die Anmeldung unmittelbar davor lag —, kommt der
 * Dialog nie, und die vier Sekunden liefen JEDES MAL vollstaendig ab. Sie
 * standen mitten im gemessenen Abschnitt.
 *
 * Die Ausgangsmessung von AP0 nennt „Suche 4,53 s" und „Tagesansicht 4,81 s".
 * Beide liegen dicht ueber vier Sekunden, und das ist kein Zufall: Gemessen
 * wurde `max(4 s Wartezeit, tatsaechliche Dauer)`. Nachgemessen mit dieser
 * Fassung liegt die Suche bei 3,77 s — der wahre Wert lag die ganze Zeit
 * UNTER dem, was das Protokoll auswies, und niemand konnte es sehen.
 *
 * DIE LEHRE steht in CLAUDE.md, Abschnitt 6, und gilt hier gegen das
 * Pruefmittel selbst: Eine Zahl ist erst dann ein Beleg, wenn sie benennt,
 * was sie gemessen hat. Ein Zeitlimit im gemessenen Abschnitt misst sich
 * selbst.
 *
 * JETZT: Der Dialog wird gegen die ABSCHLUSSBEDINGUNG des Schritts gerennt.
 * Kommt er zuerst, wird entsperrt; ist der Schritt zuerst fertig, war kein
 * Entsperren noetig und es wurde keine Sekunde dafuer verbraucht.
 *
 * @param {Promise} fertig  die Bedingung, auf die der Schritt ohnehin wartet
 * @returns {Promise<boolean>} ob entsperrt wurde
 */
async function entsperren(fertig) {
  const d = seite.locator('dialog.dialog:has-text("entsperren")');
  const sichtbar = d.waitFor({ state: 'visible', timeout: 300000 })
                    .then(() => 'dialog', () => 'nichts');
  const wer = fertig
    ? await Promise.race([sichtbar, Promise.resolve(fertig).then(() => 'fertig', () => 'fertig')])
    : await Promise.race([sichtbar,
        new Promise((r) => setTimeout(() => r('nichts'), 4000))]);
  if (wer !== 'dialog') { return false; }
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
    seite.click('#loginform button[type="submit"]'),
  ]);
  if (seite.url().includes('login.php')) { throw new Error('Anmeldung gescheitert'); }
  return {};
}));

/* Gewartet wird auf den INHALT, nicht auf das Ladeereignis: Die Tagesliste
 * der Seitenleiste ist das, was die Seite benutzbar macht. Die Leiste
 * deckelt sie bei 500 Einträgen — auch bei 1029 Diensttagen stehen also
 * höchstens 500 im Markup.
 *
 * SEIT WEB 21.4.0 SAGT SIE ES (R4-17, Nr. 37), und das misst dieser Schritt
 * mit: Stehen 500 Verweise da, muss der Hinweis „… 500 jüngsten
 * Diensttage …" darunter stehen. Der Messstandbestand hat über 1000 Tage;
 * genau 500 (dann ohne Hinweis richtig) kommen hier nicht vor. Mehr als
 * 500 hieße, der Deckel ist fort. */
messungen.push(await messen('Startseite (Tagesliste)', async () => {
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded', timeout: 180000 });
  await seite.locator('aside a[href*="?d="]').first().waitFor({ state: 'attached', timeout: 180000 });
  const tage = await seite.locator('aside a[href*="?d="]').count();
  const hinweis = await seite.locator('aside .leiste-liste .leiste-leer')
    .filter({ hasText: 'jüngsten' }).count();
  if (tage > 500) { throw new Error(`${tage} Tagesverweise — der Deckel der Leiste fehlt`); }
  if (tage === 500 && hinweis !== 1) {
    throw new Error('500 Tagesverweise, aber kein Hinweis auf ältere Diensttage (Nr. 37)');
  }
  return { tagesverweise: tage, leistenhinweis: hinweis === 1 };
}));

/* Die Tagesansicht ist erst fertig, wenn die Spur auf der Karte liegt — sie
 * kommt aus api/day.php und ist genau der Weg, den S2 umbaut. Auf das
 * Seitenladen zu warten hieße, auf die Kacheln zu warten. */
messungen.push(await messen('Tagesansicht (Spur gezeichnet)', async () => {
  const verweis = seite.locator('aside a[href*="?d="]').first();
  const ziel = await verweis.getAttribute('href').catch(() => null);
  await seite.goto(ziel ? new URL(ziel, `${basis}/`).href : `${basis}/index.php`,
                   { waitUntil: 'domcontentloaded', timeout: 180000 });
  const spurDa = seite.locator('.leaflet-overlay-pane path').first()
                      .waitFor({ state: 'attached', timeout: 180000 })
                      .catch(() => { /* ein Tag ohne Spur — dann zählt die Seite selbst */ });
  if (await entsperren(spurDa)) { await spurDa; }
  await spurDa;
  const spuren = await seite.locator('.leaflet-overlay-pane path').count();
  return { adresse: seite.url().replace(basis, ''), spurlinien: spuren };
}));

messungen.push(await messen('Suche — erste Trefferanzeige', async () => {
  await seite.goto(`${basis}/suche.php`, { waitUntil: 'domcontentloaded', timeout: 300000 });
  // Auf die erste gefüllte Trefferzeile warten. Nicht auf ein Netzereignis:
  // Die Zeit, um die es geht, ist die bis zur SICHTBAREN Anzeige, und die
  // liegt hinter dem Entschlüsseln.
  const trefferDa = seite.locator('#suchtable tbody tr, #suchkacheln > *').first()
                         .waitFor({ state: 'attached', timeout: 300000 });
  if (await entsperren(trefferDa)) { await trefferDa; }
  await trefferDa;
  const zahl = (await seite.locator('#trefferzahl').textContent().catch(() => '') || '').trim();
  return { trefferzahl: zahl };
}));

/* ---- DIE BEIDEN OFFENEN MESSUNGEN AUS BACKLOG Nr. 37 (P5a/AP9) ----------
 *
 * SEIT WEB 21.4.0 HAT AUCH DIE ZEITRAUMUEBERSICHT IHREN DECKEL (R4-17): 200
 * Zeilen wie die Suche. Gemessen vorher, am 27.09.2026 im Pruefstand:
 * 88,11 s bei 4071 Einsaetzen und 4071 Zeilen (am 16.09.2026 auf einem
 * anderen Rechner 42,61 s bei 3983). Der Schritt unten ist seither auch
 * ein Riegel: Mehr als 200 Zeilen sind ein Fehler.
 *
 *
 * Nr. 37 nennt sie seit S2 als „was hier offen bleibt": die
 * Zeitraumuebersicht und die Nachbearbeitung bei 5000 Einsaetzen. Beide
 * fehlten dieser Probe, und deshalb konnte S2 sie nicht beantworten — nicht
 * weil der Bestand fehlte, sondern weil niemand hingesehen hat.
 *
 * WARUM SIE INTERESSANT SIND. Die Sondierung von P3 hat gemessen, dass
 * `zeitraum.php` `EdMissionTable.erzeuge` OHNE `seite` aufruft: Es entstehen
 * so viele `<tr>`, wie der Zeitraum Einsaetze hat — bei 3500 waren es 854 ms
 * gegen 191 ms bei 82. Die Suche daneben ist bei 200 Zeilen gedeckelt und
 * skaliert deshalb sublinear. Die Zeitraumuebersicht ist die eine Ansicht
 * ohne diesen Deckel.
 *
 * DAS JAHR WIRD NICHT GERATEN. Der Messstandbestand liegt ueber mehrere
 * Jahre verteilt (jede Runde schiebt drei Tage zurueck); ein festes Jahr
 * traefe mal viel und mal nichts. Genommen wird das Jahr des NEUESTEN
 * Diensttags — dort liegt der dichteste Teil —, und die gemessene
 * Einsatzzahl steht mit im Protokoll. Eine Zeit ohne die Zahl daneben waere
 * keine Auskunft (CLAUDE.md 6).
 */
/* SEIT R4-27 LAEUFT DIE UHR NUR UEBER DAS, WAS DIE ABNAHME MEINT (Nr. 37,
 * E-R4-64). Bis dahin begann sie mit dem Laden der STARTSEITE — die brauchte
 * der Schritt nur, um das Jahr zu finden, und sie hat ihren eigenen Schritt
 * (rund 1,1 s) — und endete erst nach dem Zaehlen der Zeilen, das wartet,
 * bis der gedrosselte Hauptfaden frei wird. Am 28.09.2026 gemessen: 1,1 s
 * Startseite, 4,4 s bis zur Zeile, dann noch 0,9 s fuer zwei Abfragen.
 *
 * Jetzt: das Jahr VOR der Uhr; die Dauer von `zeitraum.php` bis Playwright
 * die erste Zeile SIEHT; danach, ohne Uhr, der Riegel und die zweite Zahl
 * `fertig_s` — wann der Hauptfaden zur Ruhe kommt (Ende der letzten
 * Langaufgabe, von der Seite selbst ab ihrer Navigation gemessen). Die
 * erste ist die Abnahme, die zweite wird genannt, nicht gehalten: Die Seite
 * zeichnet seit Web 21.6.1 in Stuecken, und dadurch ist sie frueher zu sehen
 * (Median 3,7 statt 5,8 s), aber rund 0,7 s spaeter fertig (7,0 statt
 * 6,3 s) — gemessen am 28.09.2026 mit genau diesem Schritt, je fuenf Laeufe. */
await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded', timeout: 180000 });
const zeitraumZiel = await seite.locator('aside a[href*="?d="]').first()
                                .getAttribute('href').catch(() => null);
const zeitraumJahr = (zeitraumZiel && /d=(\d{4})-/.exec(zeitraumZiel)?.[1])
  || String(new Date().getUTCFullYear());
const zeitraum = await messen('Zeitraumübersicht (ganzes Jahr)', async () => {
  await seite.goto(`${basis}/zeitraum.php?y=${zeitraumJahr}`,
                   { waitUntil: 'domcontentloaded', timeout: 300000 });
  /* Gewartet wird auf die erste ENTSCHLUESSELTE Zeile, nicht auf das
     Seitenladen: Die Tabelle entsteht im Browser aus `api/range.php`, und
     genau dieser Weg ist der Prüfling. */
  const zeileDa = seite.locator('#rangetable tbody tr, #rangekacheln > *').first()
                       .waitFor({ state: 'attached', timeout: 300000 })
                       .catch(() => { /* ein Jahr ohne Einsätze — dann zählt die Seite selbst */ });
  if (await entsperren(zeileDa)) { await zeileDa; }
  await zeileDa;
  return { jahr: zeitraumJahr };
});
try {
  const zeilen = await seite.locator('#rangetable tbody tr').count();
  const zahl = (await seite.locator('#einsatzzahl').textContent().catch(() => '') || '').trim();
  /* RUHE: eine Sekunde ohne neue Langaufgabe, hoechstens 60 s gewartet. */
  const stand = await seite.evaluate(() => new Promise((fertig) => {
    const z = window.__messstand;
    const bis = performance.now() + 60000;
    let zuletzt = -1;
    const pruefe = () => {
      if (z.lang.length === zuletzt || performance.now() > bis) {
        const ende = z.lang.reduce((m, l) => Math.max(m, l[1]), 0);
        fertig({ ersteZeile: z.ersteZeile, ende: z.lang.length ? Math.max(ende, z.ersteZeile || 0) : null });
        return;
      }
      zuletzt = z.lang.length;
      setTimeout(pruefe, 1000);
    };
    pruefe();
  }));
  Object.assign(zeitraum, {
    tabellenzeilen: zeilen, einsatzzahl: zahl,
    erste_zeile_im_dom_s: stand.ersteZeile == null ? null : Math.round(stand.ersteZeile / 10) / 100,
    fertig_s: stand.ende == null ? null : Math.round(stand.ende / 10) / 100,
  });
  if (zeilen > 200) {
    zeitraum.fehler = zeitraum.fehler || `${zeilen} Tabellenzeilen — die Seitengrenze der Zeitraumübersicht fehlt`;
  }
} catch (e) {
  zeitraum.fehler = zeitraum.fehler || String(e.message || e).slice(0, 300);
}
console.log(`    sichtbar ${zeitraum.dauer_s} s · erste Zeile im DOM ${zeitraum.erste_zeile_im_dom_s} s`
  + ` · fertig ${zeitraum.fertig_s} s · ${zeitraum.einsatzzahl || '—'}`
  + (zeitraum.fehler ? ` · FEHLER ${zeitraum.fehler}` : ''));
messungen.push(zeitraum);

/* Die Nachbearbeitung („Zuordnung nachtragen") ist das Gegenstueck: Sie
 * entsteht vollstaendig auf dem SERVER und laedt kein JSON nach. Was hier
 * waechst, ist die Abfrage ueber alle Diensttage ohne Zuordnung — und ihre
 * Zahl haengt am Bestand, nicht an einer Seitengrenze. */
messungen.push(await messen('Nachbearbeitung (Zuordnung nachtragen)', async () => {
  await seite.goto(`${basis}/nachbearbeitung.php`,
                   { waitUntil: 'domcontentloaded', timeout: 300000 });
  await seite.locator('h1, .titelzeile').first().waitFor({ state: 'attached', timeout: 300000 });
  const karten = await seite.locator('.karte').count();
  const formulare = await seite.locator('.listen-form-titel').count();
  return { karten, offene_zuordnungen: formulare };
}));

messungen.push(await messen('Backup erstellen', async () => {
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
