/* GPX-Probe — baut und liest der Browser dieselben Dateien wie bisher der
 * Server, und kommt eine importierte Datei verschlossen an? (S11-05d; vorher
 * S2/AP4, E-S2-09, Backlog Nr. 3)
 *
 * Anlass: Nr. 130 — die DOCTYPE-Sperre des GPX-Imports liess UTF-16 durch (Teil 5); Nr. 259 — ein Demo-Reset machte den Referenzvergleich blind; Nr. 43 — seit S11 baut und liest der Browser GPX, der Server sieht keine Koordinate mehr
 *
 * WARUM NEU. Bis Web 22.2.0 war dies `probe.php`: `gpx.php` baute die Datei,
 * `gpx_lesen()` las sie, beides auf dem Server, und die Probe rief beides
 * unmittelbar. Seit S11-05d gibt es beides nicht mehr — `assets/gpx.js` baut
 * und liest im Browser, weil nur der Browser die Spur oeffnen kann. Was die
 * alte Probe belegte, belegt diese an der neuen Stelle:
 *
 *   Teil 0  Das amtliche Schema: SHA-256 des vendorierten GPX-1.1-XSD, dann
 *           `DOMDocument::schemaValidate()` (PHP, `schema.php`) fuer jede
 *           Datei, die diese Probe zu sehen bekommt.
 *   Teil 1  DIESELBE DATEI WIE VORHER. `soll/` haelt Dateien und Dateinamen,
 *           die die letzte Fassung von `gpx_lib.php` (Web 22.2.0) aus
 *           `soll/eingabe.json` gebaut hat. `EdGpx` baut aus derselben Eingabe
 *           Byte fuer Byte dasselbe — bis auf die Fassung in `creator` und
 *           EINE benannte Abweichung (F-S11-55): PHP schrieb 1e-7 als
 *           `1.0E-7`, und das ist kein xsd:decimal.
 *   Teil 2  Der Referenzexport (211 GPX-Dateien aus `assets/export.js`):
 *           jede gelesen (`EdGpx.lesen`) und neu gebaut (`EdGpx.bauen`) —
 *           dieselben Punkte, schemagueltig. Zwei Umsetzungen, eine Spur.
 *   Teil 3  Der Abruf ueber die Oberflaeche: Einsatzansicht (Menue), Spurenseite
 *           (Knopf je Zeile, Auswahl als eine Datei). Inhalt = gesendet.
 *   Teil 4  Der Import ueber den Dialog der Tagesansicht, als Ruhesegment und
 *           als Einsatz: in der Datenbank nur Stuecke, kein Klartext; der
 *           Browser oeffnet sie wieder zu den Punkten der Datei. Der Endpunkt
 *           weist eine falsche Kennung, eine doppelte Lieferung und einen
 *           fremden Diensttag ab.
 *   Teil 5  Der Eingang: was `EdGpx.lesen` abweisen muss (die Faelle der
 *           alten Teil 8) und was durchgehen muss.
 *
 * Nur oertlich, weil sie die Datenbank liest und veraendert.
 *
 * Aufruf:   node tools/proben/gpx/probe.mjs [--basis URL]
 * Rueckgabe: 0 alles erfuellt · 1 mindestens eine Erwartung nicht · 2 nicht gelaufen
 */
import { execFileSync } from 'node:child_process';
import { rmSync, mkdtempSync, readFileSync, writeFileSync, readdirSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { randomBytes, createHash } from 'node:crypto';

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = join(HIER, '..', '..', '..');
const ARG = process.argv.slice(2);
for (let i = 0; i < ARG.length; i += 2) {
  if (ARG[i] !== '--basis') { console.error(`Unbekannter Schalter: ${ARG[i]}`); process.exit(2); }
}
const BASIS = ARG.includes('--basis') ? ARG[ARG.indexOf('--basis') + 1] : 'https://127.0.0.1:8443';
if (!/^https?:\/\/(127\.0\.0\.1|localhost)(:|\/|$)/.test(BASIS)) {
  console.log('Nur gegen die örtliche Anlage — die Probe verändert die Datenbank. Nicht gelaufen.');
  process.exit(2);
}
const ADRESSE  = 'umlauf-gpx@example.invalid';
const PASSWORT = 'umlaufpruefung2026-gpx';
const KONTO = join(WURZEL, 'tools', 'referenzdatensatz', 'vergleich', 'pruefkonto.py');
const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import('file://' + MODUL);

/** Das vendorierte XSD und seine Pruefsumme (Herkunft: docs/Lizenzen.md). */
const XSD     = join(HIER, 'gpx11.xsd');
const XSD_SHA = '9e4d1988b862edbe556305b130f8f6f1b29864fefd0dc02d5dab04ccdd1f34d6';

let gesamt = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  gesamt++; if (!ok) { offen++; }
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(64)} ${wert}`);
};
const php = code => execFileSync('php', ['-r', 'require "server/db.php"; ' + code],
                                 { cwd: WURZEL, encoding: 'utf8' }).trim();
function konto(befehl, rcDatei) {
  const a = [KONTO, befehl, ADRESSE, '--basis', BASIS, '--admin-email', 'admin@example.invalid',
             '--admin-passwort', 'pruefstandzugang2026'];
  if (befehl === 'anlegen') { a.push('--passwort', PASSWORT, '--rolle', 'user', '--rc-datei', rcDatei); }
  execFileSync('python3', a, { stdio: ['ignore', 'ignore', 'inherit'],
                               env: { ...process.env, PLAYWRIGHT_MODUL: MODUL } });
}
const ordner = mkdtempSync(join(tmpdir(), 'gpxprobe-'));
/** Schemalauf fuer eine Liste von Dateien: {datei: [Fehler…]} */
function schema(dateien) {
  if (!dateien.length) { return {}; }
  return JSON.parse(execFileSync('php', [join(HIER, 'schema.php'), XSD, ...dateien], { encoding: 'utf8' }));
}
let nr = 0;
const ablegen = (text, name) => { const p = join(ordner, `${++nr}-${name}`); writeFileSync(p, text); return p; };
/** Punkte einer GPX-Datei als [lat, lon, ele, ts] — unabhaengig von EdGpx gelesen. */
function punkteAus(xml) {
  const aus = [];
  for (const m of xml.matchAll(/<trkpt lat="([^"]+)" lon="([^"]+)">(?:<ele>([^<]+)<\/ele>)?<time>([^<]+)<\/time><\/trkpt>/g)) {
    aus.push([Number(m[1]), Number(m[2]), m[3] === undefined ? null : Number(m[3]),
              Math.floor(Date.parse(m[4]) / 1000)]);
  }
  return aus;
}
const gleich = (a, b, tolH = 1e-9) => a.length === b.length && a.every((p, i) =>
  Math.abs(p[0] - b[i][0]) < 1e-7 && Math.abs(p[1] - b[i][1]) < 1e-7
  && (p[2] === null ? b[i][2] === null : (b[i][2] !== null && Math.abs(p[2] - b[i][2]) < tolH))
  && p[3] === b[i][3]);

/* ---- Das Gesendete (Geraet) — ein Einsatz und ein Ruhesegment ------------- */
const jetzt = Math.floor(Date.now() / 1000) - 3600;
const iso = t => new Date(t * 1000).toISOString().replace(/\.\d+Z$/, 'Z');
const tag = iso(jetzt).slice(0, 10);
const spurM = Array.from({ length: 6 }, (_, s) =>
  [47.7261 + s * 0.00031, 10.3159 + s * 0.00047, s === 2 ? null : 674.3 + s * 0.7, jetzt + s * 10]);
const spurR = Array.from({ length: 4 }, (_, s) =>
  [47.70 + s * 0.001, 10.30 + s * 0.001, 700 + s, jetzt - 600 + s * 60]);
const einsatz = { kind: 'mission', client_ref: 'm-gpx-1', day: tag, day_ref: 'd-gpx-1',
  started_at: iso(jetzt), ended_at: iso(jetzt + 60), final: true,
  phases: [{ phase: 2, at: iso(jetzt), lat: 47.7261, lon: 10.3159 }],
  track: { seq_from: 0, points: spurM } };
const ruhe = { kind: 'rest_segment', client_ref: 'r-gpx-1', day: tag, day_ref: 'd-gpx-1',
  started_at: iso(jetzt - 600), ended_at: iso(jetzt - 400), final: true,
  track: { seq_from: 0, points: spurR } };

/* Eine Importdatei: fuenf Punkte, absichtlich unsortiert, einer ohne Hoehe,
   einer ohne Zeit (faellt weg), einer mit unbrauchbarer Breite (faellt weg). */
const impT = jetzt + 3000;
const impPunkte = [[47.81, 10.41, 701.5, impT + 120], [47.80, 10.40, 700, impT],
                   [47.805, 10.405, null, impT + 60], [47.82, 10.42, 703, impT + 180]];
const gpxImport = '<?xml version="1.0" encoding="UTF-8"?>\n'
  + '<gpx version="1.1" creator="gpxprobe" xmlns="http://www.topografix.com/GPX/1/1">'
  + '<trk><name>Probe-Import</name><trkseg>'
  + impPunkte.map(p => `<trkpt lat="${p[0]}" lon="${p[1]}">${p[2] === null ? '' : `<ele>${p[2]}</ele>`}`
                       + `<time>${iso(p[3])}</time></trkpt>`).join('')
  + '<trkpt lat="47.83" lon="10.43"><ele>9</ele></trkpt>'
  + `<trkpt lat="95" lon="10.43"><time>${iso(impT + 30)}</time></trkpt>`
  + '</trkseg></trk></gpx>\n';
const impSortiert = [...impPunkte].sort((a, b) => a[3] - b[3]);

const browser = await chromium.launch();
console.log(`GPX-Probe (Browser, chromium) gegen ${BASIS}`);
try {
  /* ---- Teil 0 — das Schema selbst ---------------------------------------- */
  console.log('\n  Teil 0 — das amtliche Schema');
  const sha = createHash('sha256').update(readFileSync(XSD)).digest('hex');
  pruefe(sha === XSD_SHA, '0  das vendorierte XSD ist unverändert', sha.slice(0, 16) + '…');

  try { konto('loeschen'); } catch (e) { /* gab es nicht */ }
  konto('anlegen', join(ordner, 'rc.json'));
  const id = Number(php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute(['${ADRESSE}']); echo (int)$st->fetchColumn();`));
  const kontext = await browser.newContext({ ignoreHTTPSErrors: true, acceptDownloads: true });
  await kontext.route(/tile\.|opentopomap|arcgisonline|basemap\.at/i, r => r.abort());
  const s = await kontext.newPage();
  const fehler = [];
  s.on('pageerror', e => fehler.push(e.message));
  const anlage = s.waitForResponse(r => r.url().includes('/api/kontopaar.php'), { timeout: 120000 })
                  .catch(() => null);
  await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('#loginform input[name="email"]', ADRESSE);
  await s.fill('#loginform input[name="password"]', PASSWORT);
  await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
                     s.click('#loginform button[type="submit"]')]);
  await anlage;
  const sec1 = php(`require_once "server/kontopaar_lib.php"; echo (string)kp_fuer_geraet(db(), ${id});`);
  pruefe(id > 0 && sec1.length > 80, '0  Wegwerfkonto angelegt, Kontopaar da', `Konto ${id}`);

  /* Das Geraet sendet — verschlossen, wie seit S11-04 */
  const devId = 'dev-gpxprobe', devKey = randomBytes(24).toString('hex');
  php(`db()->prepare("INSERT INTO devices (user_id, device_id, api_key_hash, label, active, geraet_art, geraet_modell)
       VALUES (?,?,?,?,1,'uhr','fēnix 6 Pro')")->execute([${id}, '${devId}', geraet_schluessel_hash('${devKey}'), 'GPX-Probe']);`);
  const pakete = JSON.parse(execFileSync('php', [join(WURZEL, 'tools/proben/huelle/geraet_cli.php')],
    { input: JSON.stringify({ sec1, pakete: [einsatz, ruhe] }), encoding: 'utf8' }));
  const antw = [];
  for (const p of pakete) {
    antw.push(await s.evaluate(async ([k, d, key]) => {
      const r = await fetch('ingest.php', { method: 'POST', body: JSON.stringify(k),
        headers: { 'Content-Type': 'application/json', 'X-Device-Id': d, 'X-Api-Key': key } });
      return { code: r.status, daten: await r.json().catch(() => null) };
    }, [p, devId, devKey]));
  }
  const mid = Number(antw[0].daten && antw[0].daten.id);
  const rid = Number(antw[1].daten && antw[1].daten.id);
  const dayId = Number(php(`echo (int)db()->query("SELECT day_id FROM missions WHERE id = ${mid}")->fetchColumn();`));
  pruefe(antw.every(a => a.code === 200) && mid > 0 && rid > 0 && dayId > 0,
         '0  ein Einsatz und ein Ruhesegment, verschlossen gesendet', `Einsatz ${mid}, Ruhe ${rid}, Tag ${dayId}`);

  /* ---- Teil 1 — dieselbe Datei wie gpx_lib.php ---------------------------- */
  console.log('\n  Teil 1 — dieselbe Datei wie bis Web 22.2.0 (soll/)');
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const eingabe = JSON.parse(readFileSync(join(HIER, 'soll', 'eingabe.json'), 'utf8'));
  const gebaut = await s.evaluate((e) => {
    const aus = {};
    for (const [n, d] of Object.entries(e.dateien)) {
      aus[n] = EdGpx.bauenViele(d.spuren, d.name, d.erzeugtAm);
    }
    const namen = e.dateinamen.map(x => x[0] === 'tag' ? EdGpx.dateinameTag(x[1], x[2], x[3])
                                                        : EdGpx.dateiname(x[0], x[1], x[2], x[3]));
    return { aus, namen, fassung: document.documentElement.getAttribute('data-webversion') };
  }, eingabe);
  /* DIE BENANNTE ABWEICHUNG (F-S11-55): PHP setzt Fliesskommazahlen unter
     1e-4 in Exponentschreibweise, und `1.0E-7` ist kein xsd:decimal — die
     alte Datei war an dieser Stelle ungueltig. Gezaehlt, nicht verschwiegen. */
  let byteGleich = 0, abweichung = 0; const teil1 = [];
  for (const n of Object.keys(eingabe.dateien)) {
    let soll = readFileSync(join(HIER, 'soll', n + '.gpx'), 'utf8');
    const vorher = soll;
    soll = soll.replace(/lat="1\.0E-7"/g, 'lat="0.0000001"');
    if (soll !== vorher) { abweichung++; }
    const ist = gebaut.aus[n].replace(/creator="Gen-EM NAdoku [^"]*"/, 'creator="Gen-EM NAdoku X"');
    soll = soll.replace(/creator="Gen-EM NAdoku [^"]*"/, 'creator="Gen-EM NAdoku X"');
    if (ist === soll) { byteGleich++; } else {
      let i = 0; while (i < ist.length && ist[i] === soll[i]) { i++; }
      console.log(`         ${n}: erste Abweichung bei Byte ${i}: …${ist.slice(i - 20, i + 30)}… gegen …${soll.slice(i - 20, i + 30)}…`);
    }
    teil1.push(ablegen(gebaut.aus[n], n + '.gpx'));
  }
  pruefe(byteGleich === Object.keys(eingabe.dateien).length,
         '1  EdGpx baut Byte für Byte die Datei von gpx_lib.php',
         `${byteGleich} von ${Object.keys(eingabe.dateien).length} gleich; benannte Abweichung 1.0E-7: ${abweichung}`);
  pruefe(gebaut.aus.einzeln_original.includes(`creator="Gen-EM NAdoku ${gebaut.fassung}"`) && gebaut.fassung,
         '1  creator trägt die Fassung der Seite', gebaut.fassung);
  const sollNamen = JSON.parse(readFileSync(join(HIER, 'soll', 'dateinamen.json'), 'utf8'));
  pruefe(JSON.stringify(gebaut.namen) === JSON.stringify(sollNamen),
         '1  Dateinamen wie gpx_dateiname() / gpx_dateiname_tag()', `${sollNamen.length} Namen`);
  const sch1 = schema(teil1);
  const ung1 = Object.entries(sch1).filter(([, f]) => f.length);
  pruefe(ung1.length === 0, '0  die gebauten Dateien sind schemagültig',
         ung1.length ? `${ung1[0][0]}: ${ung1[0][1][0]}` : `${teil1.length} Dateien, 0 ungültig`);

  /* ---- Teil 2 — der Referenzexport, gelesen und neu gebaut ----------------- */
  console.log('\n  Teil 2 — Referenzexport (assets/export.js): lesen, neu bauen, vergleichen');
  const zipPfad = readdirSync(join(WURZEL, 'tools/referenzdatensatz/referenz'))
    .find(n => /csv.*\.zip$/.test(n));
  if (!zipPfad) {
    pruefe(false, '2  Referenzexport gefunden', 'kein *csv*.zip');
  } else {
    const ref = JSON.parse(execFileSync('python3', ['-c',
      'import zipfile,sys,json;z=zipfile.ZipFile(sys.argv[1]);'
      + 'print(json.dumps({n:z.read(n).decode("utf-8") for n in z.namelist() if n.endswith(".gpx")}))',
      join(WURZEL, 'tools/referenzdatensatz/referenz', zipPfad)], { encoding: 'utf8', maxBuffer: 1 << 28 }));
    const namen = Object.keys(ref);
    const ergebnis = await s.evaluate((dateien) => {
      const aus = [];
      for (const [n, x] of Object.entries(dateien)) {
        try {
          const g = EdGpx.lesen(new TextEncoder().encode(x).buffer);
          aus.push({ n, neu: EdGpx.bauen(g.punkte, g.name || 'x', 1), ohneZeit: g.ohneZeit,
                     verworfen: g.verworfen });
        } catch (e) { aus.push({ n, fehler: e.message }); }
      }
      return aus;
    }, ref);
    let gleichN = 0, abw = []; const neuDateien = [];
    for (const r of ergebnis) {
      if (r.fehler) { abw.push(`${r.n}: ${r.fehler}`); continue; }
      const alt = punkteAus(ref[r.n]).sort((a, b) => a[3] - b[3]);
      const neu = punkteAus(r.neu);
      if (gleich(alt, neu) && !r.ohneZeit && !r.verworfen) { gleichN++; }
      else { abw.push(`${r.n}: ${alt.length} gegen ${neu.length} Punkte`); }
      neuDateien.push(ablegen(r.neu, r.n.replace(/\//g, '_')));
    }
    pruefe(namen.length > 0 && gleichN === namen.length,
           '2  jede Referenzdatei: gelesen und neu gebaut, dieselben Punkte',
           abw.length ? `${abw.length} Abweichungen — erste: ${abw[0]}`
                      : `${gleichN} von ${namen.length} Dateien, ${ergebnis.reduce((a, r) => a + punkteAus(r.neu || '').length, 0)} Punkte`);
    const refDateien = namen.map(n => ablegen(ref[n], 'ref_' + n.replace(/\//g, '_')));
    const schR = schema([...refDateien, ...neuDateien]);
    const ungR = Object.entries(schR).filter(([, f]) => f.length);
    pruefe(ungR.length === 0, '0  Referenzdateien und Neubauten sind schemagültig',
           ungR.length ? `${ungR.length} ungültig — erste: ${ungR[0][0]}: ${ungR[0][1][0]}`
                       : `${refDateien.length + neuDateien.length} Dateien, 0 ungültig`);
  }

  /* ---- Teil 3 — Abruf ueber die Oberflaeche -------------------------------- */
  console.log('\n  Teil 3 — Abruf über die Oberfläche');
  const laden = async (ausloeser) => {
    const [dl] = await Promise.all([s.waitForEvent('download', { timeout: 60000 }), ausloeser()]);
    const p = join(ordner, `${++nr}-${dl.suggestedFilename()}`);
    await dl.saveAs(p);
    return { name: dl.suggestedFilename(), text: readFileSync(p, 'utf8'), pfad: p };
  };
  await s.goto(`${BASIS}/einsatz.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => typeof M !== 'undefined' && M && M.spur_punkte, null, { timeout: 30000 })
    .catch(() => null);
  const a = await laden(async () => {
    await s.evaluate(() => document.querySelector('[data-gpx-laden]').click());
    await s.click('dialog[open] [data-act="yes"]');
  });
  const a3 = punkteAus(a.text);
  pruefe(gleich(a3, spurM, 0.051), '3  Einsatzansicht: die Datei enthält die gesendete Spur',
         `${a3.length} Punkte, Datei ${a.name}`);
  pruefe(new RegExp(`^einsatz_${String(mid).padStart(6, '0')}_\\d{4}-\\d{2}-\\d{2}_\\d{4}_original\\.gpx$`).test(a.name)
         && /<desc>Originalspur — 6 Punkte<\/desc>/.test(a.text) && /<name>Einsatz \d+ — \d\d\.\d\d\.\d{4} \d\d:\d\d<\/name>/.test(a.text),
         '3  Name, Beschreibung und Dateiname wie bisher', a.name);

  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const knoepfe = await s.locator('[data-gpx-spur]').count();
  const b = await laden(() => s.locator('[data-gpx-spur]').first().click());
  pruefe(knoepfe === 2 && /^(ruhezeit|einsatz)_\d{6}_/.test(b.name) && punkteAus(b.text).length > 0,
         '3  Spurenseite: ein Knopf je Spur, lädt eine Datei', `${knoepfe} Knöpfe, ${b.name}`);
  await s.locator('[data-spurwahl]').evaluateAll(l => l.forEach(c => { c.checked = true; c.dispatchEvent(new Event('change', { bubbles: true })); }));
  const c = await laden(() => s.evaluate(() => document.getElementById('f-gpxwahl').requestSubmit()));
  const trks = [...c.text.matchAll(/<trk><name>([^<]+)<\/name>/g)].map(m => m[1]);
  pruefe(trks.length === 2 && /^Ruhezeit /.test(trks[0]) && /^Einsatz /.test(trks[1])
         && c.name === `diensttag_${tag}_2-spuren_original.gpx`
         && /<metadata><name>Diensttag \d\d\.\d\d\.\d{4}<\/name><desc>2 Aufzeichnungen — 10 Punkte insgesamt · alle im Original/.test(c.text),
         '3  Auswahl: eine Datei, zwei Spuren in zeitlicher Folge', `${c.name}: ${trks.join(' | ')}`);
  const sch3 = schema([a.pfad, b.pfad, c.pfad]);
  pruefe(Object.values(sch3).every(f => !f.length), '0  die heruntergeladenen Dateien sind schemagültig',
         `${Object.keys(sch3).length} Dateien`);

  /* ---- Teil 4 — Import ueber den Dialog ----------------------------------- */
  console.log('\n  Teil 4 — Import über den Dialog der Tagesansicht');
  const impDatei = ablegen(gpxImport, 'import.gpx');
  const importieren = async (ziel) => {
    await s.goto(`${BASIS}/index.php?d=${dayId}`, { waitUntil: 'networkidle' });
    await s.evaluate(() => document.getElementById('daygpximport').click());
    await s.waitForSelector('#gpxdialog[open]', { timeout: 15000 });
    await s.setInputFiles('#gpx-datei', impDatei);
    await s.waitForFunction(() => !document.getElementById('gpx-los').disabled, null, { timeout: 15000 });
    if (ziel === 'einsatz') { await s.evaluate(() => { document.getElementById('gpx-einsatz').checked = true; }); }
    const [resp] = await Promise.all([
      s.waitForResponse(r => r.url().includes('api/gpx_import.php'), { timeout: 60000 }),
      s.click('#gpx-los')]);
    const d = await resp.json().catch(() => ({}));
    await s.waitForTimeout(800);
    const meldung = await s.evaluate(() => [...document.querySelectorAll('.meldung')]
      .map(m => m.textContent.trim().replace(/\s+/g, ' ')).filter(t => /übernommen/.test(t)).join(' / '));
    return { code: resp.status(), d, meldung };
  };
  for (const ziel of ['ruhe', 'einsatz']) {
    const r = await importieren(ziel);
    const typ = ziel === 'einsatz' ? 'mission' : 'rest';
    const tab = ziel === 'einsatz' ? 'missions' : 'rest_segments';
    pruefe(r.code === 200 && r.d.ok && r.d.art === typ && r.d.punkte === 4,
           `4  ${ziel}: angenommen, 4 Punkte`, `HTTP ${r.code} ${JSON.stringify(r.d).slice(0, 80)}`);
    pruefe(/1 Punkte ohne Zeitstempel/.test(r.meldung) && /1 Punkte mit unbrauchbaren Koordinaten/.test(r.meldung),
           `4  ${ziel}: die Meldung nennt, was nicht mitkam`, r.meldung.slice(0, 90));
    const db = JSON.parse(php(`$r = db()->query("SELECT id, client_ref FROM ${tab} WHERE id = ${Number(r.d.id) || 0}")->fetch(PDO::FETCH_ASSOC) ?: [];
      $st = db()->query("SELECT COUNT(*) n, COALESCE(SUM(punkte),0) p, MIN(stufe) s FROM track_stuecke WHERE owner_type = '${typ}' AND owner_id = ${Number(r.d.id) || 0}")->fetch(PDO::FETCH_ASSOC);
      $tp = (int)db()->query("SELECT COUNT(*) FROM track_points WHERE owner_type = '${typ}' AND owner_id = ${Number(r.d.id) || 0}")->fetchColumn();
      $tb = (int)db()->query("SELECT COUNT(*) FROM track_blobs WHERE owner_type = '${typ}' AND owner_id = ${Number(r.d.id) || 0}")->fetchColumn();
      echo json_encode(['ref' => $r['client_ref'] ?? null, 'st' => $st, 'tp' => $tp, 'tb' => $tb]);`));
    pruefe(/^imp-[0-9a-f]{24}$/.test(db.ref || '') && Number(db.st.n) >= 1 && Number(db.st.p) === 4
           && db.tp === 0 && db.tb === 0,
           `4  ${ziel}: nur verschlossene Stücke, keine Zeile in track_points/-blobs`,
           `${db.ref}, ${db.st.n} Stück(e), ${db.st.p} Punkte, Klartext ${db.tp}/${db.tb}`);
    const offenGel = await s.evaluate(async ([dayId, typ, id]) => {
      const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
      const k = await EdOrtsdaten.schluessel(ck);
      const d = await (await fetch('api/day.php?d=' + dayId)).json();
      const x = (typ === 'mission' ? d.missions : d.rest_segments).find(e => Number(e.id) === id) || {};
      const p = await EdOrtsdaten.spur(x.spur_stuecke || [], typ === 'mission' ? 'spur' : 'ruhe',
                                       x.client_ref, [], k);
      return p.map(q => [q.lat, q.lon, q.ele, q.ts]);
    }, [dayId, typ, Number(r.d.id)]);
    pruefe(gleich(offenGel, impSortiert, 0.051), `4  ${ziel}: der Browser öffnet die Punkte der Datei, nach Zeit sortiert`,
           `${offenGel.length} Punkte`);
  }
  const ab = await s.evaluate(async ([dayId, fremdTag]) => {
    const ruf = async (b) => { const r = await EdApi.postJson('api/gpx_import.php', b, { vorgang: 'x', still: true });
                               return r.status || (r.ok ? 200 : 0); };
    const st = await EdOrtsdaten.stueckeBauen([{ lat: 1, lon: 2, ele: null, ts: 1792130000 }], 'ruhe',
                                              'imp-' + '0'.repeat(24), { neu: true });
    const ohne = await ruf({ day_id: dayId, ziel: 'ruhe', client_ref: 'imp-kurz', stuecke: st });
    const erst = await ruf({ day_id: dayId, ziel: 'ruhe', client_ref: 'imp-' + '0'.repeat(24), stuecke: st });
    const doppelt = await ruf({ day_id: dayId, ziel: 'ruhe', client_ref: 'imp-' + '0'.repeat(24), stuecke: st });
    const fremd = await ruf({ day_id: fremdTag, ziel: 'ruhe', client_ref: 'imp-' + '1'.repeat(24), stuecke: st });
    const leer = await ruf({ day_id: dayId, ziel: 'ruhe', client_ref: 'imp-' + '2'.repeat(24), stuecke: [] });
    const kaputt = await ruf({ day_id: dayId, ziel: 'ruhe', client_ref: 'imp-' + '3'.repeat(24),
                              stuecke: [{ seq_from: 0, punkte: 1, ts_von: 5, ts_bis: 5, huelle: 'AAAA' }] });
    return { ohne, erst, doppelt, fremd, leer, kaputt };
  }, [dayId, Number(php(`echo (int)db()->query("SELECT id FROM days WHERE user_id <> ${id} ORDER BY id LIMIT 1")->fetchColumn();`))]);
  pruefe(ab.ohne === 400 && ab.erst === 200 && ab.doppelt === 409 && ab.fremd === 404 && ab.leer === 400 && ab.kaputt === 400,
         '4  Endpunkt: falsche Kennung 400, doppelt 409, fremder Tag 404, leer/kaputt 400',
         JSON.stringify(ab));

  /* ---- Teil 5 — der Eingang: was lesen() abweisen muss ---------------------- */
  console.log('\n  Teil 5 — Der Eingang: EdGpx.lesen gegen Umgehungen der DOCTYPE-Sperre');
  const rein = '<?xml version="1.0" encoding="UTF-8"?>'
    + '<gpx version="1.1" creator="gpxprobe" xmlns="http://www.topografix.com/GPX/1/1">'
    + '<trk><name>Probe</name><trkseg>'
    + '<trkpt lat="48.1" lon="11.5"><ele>500</ele><time>2026-03-01T10:00:00Z</time></trkpt>'
    + '<trkpt lat="48.2" lon="11.6"><ele>510</ele><time>2026-03-01T10:01:00Z</time></trkpt>'
    + '</trkseg></trk></gpx>';
  const mitDoctype = rein.replace('<gpx version', '<!DOCTYPE gpx [<!ENTITY a "AAAAAAAAAA">]><gpx version');
  const u16 = (t, be) => { const b = Buffer.from(t, 'utf16le'); if (be) { b.swap16(); } return b; };
  const utf7 = (t) => t.replace(/[<>!\[\]"]/g, ch => '+' + Buffer.from(ch, 'utf16le').swap16().toString('base64').replace(/=+$/, '') + '-');
  const faelle = {
    'UTF-16LE mit BOM, DOCTYPE und interner Entität': Buffer.concat([Buffer.from([0xFF, 0xFE]), u16(mitDoctype)]),
    'UTF-16BE mit BOM, DOCTYPE und interner Entität': Buffer.concat([Buffer.from([0xFE, 0xFF]), u16(mitDoctype, true)]),
    'UTF-16LE ohne BOM, mit XML-Deklaration': u16(mitDoctype.replace('UTF-8', 'UTF-16')),
    'UTF-8 mit DOCTYPE (die Sperre, die es schon gab)': Buffer.from(mitDoctype),
    'UTF-8 mit DOCTYPE in Kleinschreibung': Buffer.from(mitDoctype.replace('<!DOCTYPE', '<!doctype')),
    'UTF-8 mit Bytefolgemarke und DOCTYPE': Buffer.concat([Buffer.from([0xEF, 0xBB, 0xBF]), Buffer.from(mitDoctype)]),
    'Nullbyte in einer sonst gültigen Datei': Buffer.from(rein.replace('<trk>', '<trk>\0')),
    'UTF-7 über die Kodierungsdeklaration': Buffer.from('<?xml version="1.0" encoding="UTF-7"?>'
      + utf7(mitDoctype.replace(/^<\?xml[^>]*\?>/, ''))),
    'EBCDIC (IBM037) über die Kodierungsdeklaration, Bytes UTF-8': Buffer.from(mitDoctype.replace('encoding="UTF-8"', 'encoding="IBM037"')),
    'UTF-16 über die Kodierungsdeklaration, Bytes UTF-8': Buffer.from(mitDoctype.replace('encoding="UTF-8"', 'encoding="UTF-16"')),
    'kein einziger Punkt mit Zeit': Buffer.from(rein.replace(/<time>[^<]*<\/time>/g, '')),
    'kein Trackpunkt, nur Wegpunkte': Buffer.from(rein.replace(/<trk>.*<\/trk>/, '<wpt lat="1" lon="2"/>')),
    'kein GPX (Wurzel <kml>)': Buffer.from('<?xml version="1.0"?><kml/>'),
    'nicht wohlgeformt': Buffer.from(rein.slice(0, -10)),
  };
  const gut = {
    'eine saubere UTF-8-Datei ohne DOCTYPE': Buffer.from(rein),
    'kleingeschriebenes utf-8 in einfachen Anführungszeichen': Buffer.from(rein.replace('encoding="UTF-8"', "encoding='utf-8'")),
    'ganz ohne XML-Deklaration': Buffer.from(rein.replace(/^<\?xml[^>]*\?>/, '')),
    'windows-1252 als Deklaration (ASCII-verträglich, Bytes UTF-8)': Buffer.from(rein.replace('encoding="UTF-8"', "encoding='windows-1252'")),
    'Latin-1 mit Umlaut im Namen (wie bisher über readAsText)': Buffer.concat([
      Buffer.from(rein.replace('encoding="UTF-8"', 'encoding="ISO-8859-1"').split('Probe')[0], 'latin1'),
      Buffer.from('Gr\u00fcnwald', 'latin1'),
      Buffer.from(rein.replace('encoding="UTF-8"', 'encoding="ISO-8859-1"').split('Probe')[1], 'latin1')]),
    'GPX 1.0 (anderer Namensraum)': Buffer.from(rein.replace('GPX/1/1', 'GPX/1/0').replace('1.1', '1.0')),
  };
  const lese = await s.evaluate(([f, g]) => {
    const r = (b64) => { const b = Uint8Array.from(atob(b64), c => c.charCodeAt(0));
      try { const x = EdGpx.lesen(b.buffer); return { ok: true, n: x.punkte.length, name: x.name }; }
      catch (e) { return { ok: false, m: e.message.slice(0, 60) }; } };
    return { boese: Object.fromEntries(Object.entries(f).map(([k, v]) => [k, r(v)])),
             gut: Object.fromEntries(Object.entries(g).map(([k, v]) => [k, r(v)])) };
  }, [Object.fromEntries(Object.entries(faelle).map(([k, v]) => [k, v.toString('base64')])),
      Object.fromEntries(Object.entries(gut).map(([k, v]) => [k, v.toString('base64')]))]);
  let durch = 0;
  for (const [k, r] of Object.entries(lese.boese)) {
    if (r.ok) { durch++; }
    pruefe(!r.ok, '5  abgewiesen: ' + k, r.ok ? `durchgelassen, ${r.n} Punkte` : r.m);
  }
  pruefe(durch === 0, '5  keiner der bösen Fälle kommt durch', `${Object.keys(faelle).length} Fälle, ${durch} durch`);
  for (const [k, r] of Object.entries(lese.gut)) {
    pruefe(r.ok && r.n === 2, '5  geht durch: ' + k, r.ok ? `${r.n} Punkte, Name ${r.name}` : r.m);
  }
  pruefe(lese.gut['Latin-1 mit Umlaut im Namen (wie bisher über readAsText)'].name === 'Gr\uFFFDnwald',
         '5  Latin-1: der Name trägt das Ersatzzeichen', String(lese.gut['Latin-1 mit Umlaut im Namen (wie bisher über readAsText)'].name));

  pruefe(fehler.length === 0, '–  keine Skriptfehler auf den Seiten', fehler.slice(0, 2).join(' | ') || '0');
} catch (e) {
  pruefe(false, 'Probe ohne Ausnahme durchgelaufen', e.message.split('\n')[0]);
} finally {
  await browser.close();
  try { konto('loeschen'); } catch (e) { console.log(`  Konto ${ADRESSE} nicht gelöscht: ${e.message}`); }
  rmSync(ordner, { recursive: true, force: true });
}
console.log(`\n  -> ${gesamt} Erwartungen, ${offen} nicht erfüllt`);
process.exit(offen === 0 ? 0 : 1);
