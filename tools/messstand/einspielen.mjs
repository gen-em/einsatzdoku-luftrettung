/* Den Großbestand über den REGULAEREN Wiederherstellungsweg einspielen.
 *
 * WOFUER. `vervielfaeltigen.py` legt eine Folge einteiliger `.edbak`-Dateien
 * ab; dieses Skript spielt sie nacheinander in das Messstandkonto ein — durch
 * den Browser, über `einstellungen.php?t=backup`, so wie eine NutzerIn es
 * täte. Kein SQL, kein Sonderendpunkt (Geist von R4).
 *
 * UND ES MISST DABEI. Der Einspielweg IST einer der Prüflinge: B-S2-03 sagt,
 * dass er lange vor 5000 Einsätzen bricht. Wo genau, steht nirgends — deshalb
 * hält dieses Skript je Datei fest, wie lange es gedauert hat, was die
 * Anwendung gemeldet hat und wie hoch der Haldenverbrauch des Browsers
 * gestiegen ist. Das ist die Ausgangsmessung, gegen die S2 später antritt.
 *
 * Aufruf:
 *   node einspielen.mjs [basis] [ordner] [ausgabe.json]
 *
 * Umgebung:
 *   MESSSTAND_KONTO, MESSSTAND_PASSWORT, MESSSTAND_BACKUP_PASSWORT
 *   MESSSTAND_FREMDE_INSTALLATION=ja   Riegel für andere Adressen lösen
 */
import { readFileSync, writeFileSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const ordner  = process.argv[3] || '/tmp/messstand/bestand';
const ausgabe = process.argv[4] || `${ordner}/einspielprotokoll.json`;
const konto   = process.env.MESSSTAND_KONTO || 'messstand@gen-em.org';
const kontoPw = process.env.MESSSTAND_PASSWORT || 'messstandpruefung2026';
const bpw     = process.env.MESSSTAND_BACKUP_PASSWORT || 'nadokudemo0815';

/* ---- RIEGEL ------------------------------------------------------------
 *
 * DIESES SKRIPT FUELLT EIN KONTO MIT TAUSENDEN EINSAETZEN. In einem
 * Prüfkonto ist das der Zweck; in einem echten Konto wäre es ein Schaden, den
 * niemand mehr von Hand aufräumt, und auf der Referenzinstallation wäre der
 * Referenzstand hin — genau das ist dort schon einmal passiert (F-S1-D, und
 * die Lehre steht in `browser/demo_pruefen.mjs`).
 *
 * Der Riegel schließt deshalb NACH INNEN: Wer nicht POSITIV feststellen kann,
 * dass hier nichts kaputtgeht, bricht ab. Zwei Bedingungen, beide müssen
 * erfüllt sein:
 *
 *   1. Die Adresse ist die eigene Adresse des Messstands. Das Demo- und
 *      Referenzkonto `demo@gen-em.org` ist ausdrücklich ausgeschlossen — auch
 *      dann, wenn jemand es über die Umgebung setzt.
 *   2. Die Installation läuft auf dieser Maschine. Eine andere Adresse
 *      verlangt ein ausdrückliches `MESSSTAND_FREMDE_INSTALLATION=ja`; damit
 *      steht die Entscheidung im Aufruf und nicht in einem Vorgabewert.
 */
{
  const gruende = [];
  if (konto === 'demo@gen-em.org') {
    gruende.push('Das Zielkonto ist das Demo- und Referenzkonto. Ein '
      + 'Großbestand darin macht den Referenzstand unbrauchbar.');
  }
  if (!/^messstand[@+]/.test(konto)) {
    gruende.push(`Das Zielkonto "${konto}" ist nicht als Messstandkonto zu `
      + 'erkennen (erwartet wird eine Adresse, die mit "messstand@" oder '
      + '"messstand+" beginnt).');
  }
  const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/|$)/.test(basis);
  if (!lokal && process.env.MESSSTAND_FREMDE_INSTALLATION !== 'ja') {
    gruende.push(`Die Installation ${basis} läuft nicht auf dieser Maschine. `
      + 'Für eine andere Adresse MESSSTAND_FREMDE_INSTALLATION=ja setzen.');
  }
  if (gruende.length) {
    console.error('\nABBRUCH — es wurde nichts angefasst.\n');
    for (const g of gruende) { console.error('  · ' + g); }
    console.error('\nDer Messstand legt tausende Einsätze an. Dafür braucht er '
      + 'ein eigenes\nKonto auf einer Entwicklungsinstallation.\n');
    process.exit(2);
  }
}

const verzeichnis = JSON.parse(readFileSync(`${ordner}/verzeichnis.json`, 'utf-8'));
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, acceptDownloads: true });
const seite = await kontext.newPage();

const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const konsole = [];
seite.on('console', m => { if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

async function anmelden(mail, pw) {
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', mail);
  await seite.fill('input[name="password"]', pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
    seite.click('button[type="submit"]'),
  ]);
  return !seite.url().includes('login.php');
}

async function rueckfragen(hoechstens = 4) {
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 8000 }); } catch { break; }
    await ja.first().click();
    await seite.waitForTimeout(400);
  }
}

/* Haldenverbrauch über das Protokoll der Entwicklerwerkzeuge.
 *
 * `performance.memory` gibt es nur in Chromium und nur grob; die Zahl aus
 * `Performance.getMetrics` ist dieselbe Größe, aber sie kommt ohne Zutun der
 * Seite und funktioniert auch, wenn die Seite gerade nicht antwortet. Genau
 * das ist hier der interessante Fall. */
const cdp = await kontext.newCDPSession(seite);
await cdp.send('Performance.enable');
async function halde() {
  const { metrics } = await cdp.send('Performance.getMetrics');
  const m = Object.fromEntries(metrics.map(x => [x.name, x.value]));
  return Math.round((m.JSHeapUsedSize || 0) / 1024 / 1024);
}

if (!await anmelden(konto, kontoPw)) {
  console.error(`ABBRUCH. Anmeldung als ${konto} auf ${basis} gescheitert.`);
  await browser.close();
  process.exit(2);
}
console.log(`Angemeldet als ${konto} auf ${basis}`);

const protokoll = { basis, konto, ordner, dateien: [], konsolenfehler: konsole };
let eingespielt = 0;

for (const eintrag of verzeichnis.dateien) {
  const pfad = `${ordner}/${eintrag.datei}`;
  process.stdout.write(`  ${eintrag.datei} (${eintrag.einsaetze} Einsätze, `
    + `${eintrag.spurpunkte} Punkte) … `);

  await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(800);
  const haldeVorher = await halde();
  const t0 = Date.now();

  let zustand = '', fehler = null, tonZuletzt = '';
  try {
    await seite.setInputFiles('#bfile', pfad);
    await seite.fill('#ipw', bpw);
    await seite.click('#impbtn');
    await rueckfragen();
    /* AUF DIE MELDUNG WARTEN, NICHT AUF EINEN WORTLAUT (S2/AP5b).
     *
     * Hier stand eine Wortliste. Sie ist zweimal falsch gewesen: einmal, weil
     * der Abbruchtext keines der Woerter enthielt (kreislauf_edbak.mjs), und
     * einmal, weil `eingespielt` in einem ZWISCHENstand vorkommen kann und
     * die Schleife dann zu frueh bricht. Die Anwendung unterscheidet selbst:
     * Ein Zwischenstand ist reiner Text, ein Ergebnis ist
     * `<div class="meldung meldung-ok|warn|fehler">`.
     *
     * Bis zu 30 Minuten je Datei — bei grossen Bestaenden dauert es. */
    let ton = '';
    try {
      await seite.locator('#impstate .meldung').first()
        .waitFor({ state: 'attached', timeout: 1800000 });
      ton = (await seite.locator('#impstate .meldung').first()
        .getAttribute('class').catch(() => '') || '');
    } catch { ton = ''; }
    zustand = (await seite.locator('#impstate').textContent().catch(() => '') || '').trim();
    tonZuletzt = ton;
  } catch (e) {
    fehler = String(e.message || e).slice(0, 400);
  }
  const dauer = (Date.now() - t0) / 1000;
  const haldeNachher = await halde();
  const geglueckt = !fehler && /meldung-ok/.test(tonZuletzt);

  /* WAS DIE ANWENDUNG MELDET, NICHT WAS DIE DATEI ENTHAELT.
   *
   * Die erste Fassung addierte `eintrag.einsaetze` — die Zahl aus dem
   * Verzeichnis des Vervielfältigers. Sie meldete 5046 Einsätze, angelegt
   * waren 4744: Ab Runde 26 hatte die Regel D1 dreizehn Einsätze je Runde
   * übersprungen (Diensttag im Ziel-Papierkorb). Die Anwendung hatte das
   * korrekt berichtet — „254 Einsätze übernommen, 7 übersprungen" —, nur hat
   * niemand hingesehen.
   *
   * Eine Zahl, die nicht das misst, was sie behauptet, ist schlimmer als
   * keine: Sie marschiert durch jede weitere Auswertung durch (CLAUDE.md 6).
   * Jetzt wird die Rückmeldung GELESEN, und die Abweichung zur Erwartung steht
   * daneben. */
  const zahl = (muster) => {
    const m = zustand.match(muster);
    return m ? Number(m[1]) : null;
  };
  const uebernommen  = zahl(/(\d+)\s+Eins(?:ä|ae)tze\s+übernommen/);
  const uebersprungen = zahl(/Übersprungen:\s*(\d+)\s+Eins(?:ä|ae)tze/) || 0;
  if (geglueckt) { eingespielt += (uebernommen ?? eintrag.einsaetze); }
  const abweichung = uebernommen === null ? null : uebernommen - eintrag.einsaetze;

  protokoll.dateien.push({
    ...eintrag, dauer_s: Math.round(dauer * 10) / 10,
    halde_vorher_mb: haldeVorher, halde_nachher_mb: haldeNachher,
    einsaetze_erwartet: eintrag.einsaetze,
    einsaetze_uebernommen: uebernommen, einsaetze_uebersprungen: uebersprungen,
    abweichung, zustand, fehler, geglueckt, einsaetze_kumuliert: eingespielt,
  });
  console.log(geglueckt
    ? `${dauer.toFixed(1)} s, Halde ${haldeVorher}→${haldeNachher} MB, `
      + `${uebernommen ?? '?'} übernommen`
      + (abweichung ? ` (${abweichung > 0 ? '+' : ''}${abweichung} gegen die Erwartung!)` : '')
      + `, gesamt ${eingespielt}`
    : `GESCHEITERT nach ${dauer.toFixed(1)} s — ${fehler || zustand}`);

  if (!geglueckt) {
    console.log('\n  Abbruch der Reihe: Ab hier ist der Bestand unvollständig.');
    console.log('  Genau diese Stelle ist die gesuchte Bruchstelle (B-S2-03).');
    break;
  }
}

const erwartet = verzeichnis.dateien.reduce((s, d) => s + d.einsaetze, 0);
protokoll.einsaetze_erwartet = erwartet;
protokoll.einsaetze_eingespielt = eingespielt;
protokoll.konsolenfehler = konsole;
writeFileSync(ausgabe, JSON.stringify(protokoll, null, 2) + '\n');

console.log(`\n${eingespielt} von ${erwartet} erwarteten Einsätzen eingespielt.`);
const fehlend = erwartet - eingespielt;
if (fehlend !== 0) {
  console.log(`  ${fehlend} Einsätze fehlen. Die Rückmeldungen der Anwendung `
    + 'nennen den Grund — meist die Regel D1 (Diensttag im Ziel-Papierkorb).');
}
console.log(`Protokoll: ${ausgabe}`);
await browser.close();
/* Ein unvollständiger Bestand ist kein Erfolg, auch wenn keine Datei
 * gescheitert ist: Wer darauf misst, misst etwas anderes als 5000 Einsätze. */
process.exit(protokoll.dateien.every(d => d.geglueckt) && fehlend === 0 ? 0 : 1);
