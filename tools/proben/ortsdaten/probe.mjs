/* Ortsdatenprobe — die Kette Gerät → Server → Browser mit Gerätehüllen
 * (S11-04b; Konzept S11 3.4, 3.5, E-S11-36 bis -40).
 *
 * Anlass: Nr. 43 — Spur, Phasenkoordinaten und Reanimation gehen seit Web 22
 * verschlossen an den Server; ob der Browser sie aus den Antworten der
 * Anwendung wieder öffnet, belegt keine Einzelprobe für sich.
 *
 * WAS SIE FÄHRT, gegen die örtliche Anlage:
 *   1. ein Wegwerfkonto (`pruefkonto.py`), anmelden — das Kontopaar entsteht
 *   2. ein Gerät (per SQL, wie die Ingestprobe) sendet über `ingest.php` einen
 *      Einsatz (zwei Spurstücke, drei Phasen, eine Reanimation) und ein
 *      Ruhesegment, verschlossen mit dem Kontoschlüssel (`geraet_cli.php`)
 *   3. `api/mission.php` liefert Stücke, Einsatzhülle, client_ref, Beginn;
 *      der Browser öffnet sie mit seinem privaten Schlüssel
 *      (`EdKontopaar.privat`, `EdCrypto.geraetehuelleOeffnen`) und vergleicht
 *      mit dem Gesendeten — Punkt für Punkt, auch eine fehlende Höhe
 *   4. `api/day.php` liefert dasselbe für den Diensttag; ein Schnitt im
 *      Ruhesegment steht als Sperrzeit daneben (E-S11-37)
 *   5. ein Stück an einem anderen Einsatz geht nicht auf (Bindung)
 *
 * Nur örtlich, weil sie die Datenbank liest und verändert.
 *
 * Aufruf:   node tools/proben/ortsdaten/probe.mjs [--basis URL]
 * Rückgabe: 0 alles erfüllt · 1 mindestens eine Erwartung nicht · 2 nicht gelaufen
 */
import { execFileSync } from 'node:child_process';
import { rmSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { randomBytes } from 'node:crypto';

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
const ADRESSE  = 'umlauf-ortsdaten@example.invalid';
const PASSWORT = 'umlaufpruefung2026-ortsdaten';
const KONTO = join(WURZEL, 'tools', 'referenzdatensatz', 'vergleich', 'pruefkonto.py');
const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import('file://' + MODUL);

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

/* ---- Das Gesendete: im alten Format, die Gerätehilfe verschließt ---------- */
const jetzt = Math.floor(Date.now() / 1000) - 3600;
const iso = t => new Date(t * 1000).toISOString().replace(/\.\d+Z$/, 'Z');
const tag = iso(jetzt).slice(0, 10);
const punkte = n0 => Array.from({ length: 5 }, (_, i) => {
  const s = n0 + i;
  return [47.7261 + s * 0.00031, 10.3159 + s * 0.00047, s === 2 ? null : 674.3 + s * 0.7, jetzt + s * 10];
});
const spurA = punkte(0), spurB = punkte(5);
const einsatz = {
  kind: 'mission', client_ref: 'm-ortsdaten-1', day: tag, day_ref: 'd-ortsdaten-1',
  started_at: iso(jetzt), ended_at: null, final: false,
  phases: [{ phase: 2, at: iso(jetzt), lat: 47.7261, lon: 10.3159 },
           { phase: 3, at: iso(jetzt + 40), lat: 47.7270, lon: 10.3170 },
           { phase: 4, at: iso(jetzt + 90), lat: null, lon: null }],
  resus_sessions: [{ started_at: iso(jetzt + 60),
                     events: [{ type: 'adrenalin', at: iso(jetzt + 75) },
                              { type: 'rosc', at: iso(jetzt + 95) }] }],
  track: { seq_from: 0, points: spurA } };
const nachtrag = { kind: 'mission', client_ref: 'm-ortsdaten-1', day: tag, day_ref: 'd-ortsdaten-1',
  started_at: iso(jetzt), final: false, track: { seq_from: 5, points: spurB } };
const ruhe = { kind: 'rest_segment', client_ref: 'r-ortsdaten-1', day: tag, day_ref: 'd-ortsdaten-1',
  started_at: iso(jetzt - 600), ended_at: iso(jetzt - 10), final: true,
  track: { seq_from: 0, points: punkte(20) } };

const ordner = mkdtempSync(join(tmpdir(), 'ortsdaten-'));
const browser = await chromium.launch();
console.log(`Ortsdatenprobe (Browser, chromium) gegen ${BASIS}`);
try {
  try { konto('loeschen'); } catch (e) { /* gab es nicht */ }
  konto('anlegen', join(ordner, 'rc.json'));
  const id = Number(php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute(['${ADRESSE}']); echo (int)$st->fetchColumn();`));
  pruefe(id > 0, '1  Wegwerfkonto angelegt', `Konto ${id}`);

  const kontext = await browser.newContext({ ignoreHTTPSErrors: true });
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
  pruefe(sec1.length > 80, '1  Kontopaar angelegt, Kontoschlüssel für Geräte da', `${sec1.slice(0, 12)}…`);

  /* 2 — ein Gerät sendet, verschlossen mit dem Kontoschlüssel */
  const devId = 'dev-ortsdaten', devKey = randomBytes(24).toString('hex');
  php(`db()->prepare("INSERT INTO devices (user_id, device_id, api_key_hash, label, active, geraet_art, geraet_modell)
       VALUES (?,?,?,?,1,'uhr','fēnix 6 Pro')")->execute([${id}, '${devId}', geraet_schluessel_hash('${devKey}'), 'Ortsdatenprobe']);`);
  const pakete = JSON.parse(execFileSync('php', [join(WURZEL, 'tools/proben/huelle/geraet_cli.php')],
    { input: JSON.stringify({ sec1, pakete: [einsatz, nachtrag, ruhe] }), encoding: 'utf8' }));
  const antworten = [];
  for (const p of pakete) {
    antworten.push(await s.evaluate(async ([k, d, key]) => {
      const r = await fetch('ingest.php', { method: 'POST', body: JSON.stringify(k),
        headers: { 'Content-Type': 'application/json', 'X-Device-Id': d, 'X-Api-Key': key } });
      return { code: r.status, daten: await r.json().catch(() => null) };
    }, [p, devId, devKey]));
  }
  pruefe(antworten.every(a => a.code === 200 && a.daten && a.daten.ok),
         '2  drei Pakete angenommen (Einsatz, Nachtrag, Ruhesegment)',
         antworten.map(a => `${a.code}/${a.daten && a.daten.stored_points}`).join(' '));
  const mid = Number(antworten[0].daten && antworten[0].daten.id);
  const rid = Number(antworten[2].daten && antworten[2].daten.id);
  const dayId = Number(php(`echo (int)db()->query("SELECT day_id FROM missions WHERE id = ${mid}")->fetchColumn();`));
  /* Ein Schnitt im Ruhesegment: die mittleren Punkte gesperrt (E-S11-37). */
  php(`db()->prepare("INSERT INTO track_cuts (user_id, owner_type, owner_id, mission_id, von_ts, bis_ts, n_punkte, erstellt_am)
       VALUES (?, 'rest', ?, ?, ?, ?, 2, UTC_TIMESTAMP())")->execute([${id}, ${rid}, ${mid}, ${jetzt + 210}, ${jetzt + 220}]);`);

  /* 3 und 4 — der Browser holt die Antworten und öffnet */
  await s.goto(`${BASIS}/suche.php`, { waitUntil: 'networkidle' });
  const r = await s.evaluate(async ([mid, dayId]) => {
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    const priv = await EdKontopaar.privat(ck);
    const pub = EdKontopaar.oeffentlich();
    const b64 = t => Uint8Array.from(atob(t), c => c.charCodeAt(0));
    const spur = async (stuecke, art, ref) => {
      const aus = [];
      for (const st of stuecke) {
        const innen = await EdCrypto.geraetehuelleOeffnen(b64(st.huelle), priv, pub, art, ref,
                                                         `${st.seq_from}:${st.punkte}`);
        const dv = new DataView(innen.buffer, innen.byteOffset, innen.byteLength);
        for (let i = 0; i < st.punkte; i++) {
          const h = dv.getInt16(i * 14 + 8);
          aus.push([dv.getInt32(i * 14) / 1e7, dv.getInt32(i * 14 + 4) / 1e7,
                    h === -32768 ? null : h / 10, st.ts_von + dv.getUint32(i * 14 + 10)]);
        }
      }
      return aus;
    };
    const m = await (await fetch('api/mission.php?id=' + mid)).json();
    const punkteM = await spur(m.spur_stuecke, 'spur', m.client_ref);
    const ort = JSON.parse(new TextDecoder().decode(
      await EdCrypto.geraetehuelleOeffnen(b64(m.ort_huelle), priv, pub, 'einsatz', m.client_ref, '')));
    let fremd = null;
    try { await EdCrypto.geraetehuelleOeffnen(b64(m.spur_stuecke[0].huelle), priv, pub, 'spur', 'm-anders', '0:5'); }
    catch (e) { fremd = e.grund; }
    const d = await (await fetch('api/day.php?d=' + dayId)).json();
    const seg = (d.rest_segments || []).find(x => x.client_ref === 'r-ortsdaten-1') || {};
    const ein = (d.missions || []).find(x => x.client_ref === 'm-ortsdaten-1') || {};
    return { stuecke: m.spur_stuecke.map(x => [x.seq_from, x.punkte]), punkteM, ort,
             started_ts: m.started_ts, phasen_ts: m.phases.map(p => p.ts), fremd,
             tagStuecke: (ein.spur_stuecke || []).length, punkteR: await spur(seg.spur_stuecke || [], 'ruhe', seg.client_ref),
             n: seg.n, sperren: (seg.schnitte || []).map(c => [c.von_ts, c.bis_ts]) };
  }, [mid, dayId]);

  const gleich = (a, b) => a.length === b.length && a.every((p, i) =>
    Math.abs(p[0] - b[i][0]) < 1e-7 && Math.abs(p[1] - b[i][1]) < 1e-7
    && (p[2] === null ? b[i][2] === null : Math.abs(p[2] - b[i][2]) < 0.051) && p[3] === b[i][3]);
  pruefe(JSON.stringify(r.stuecke) === '[[0,5],[5,5]]', '3  api/mission.php: zwei Stücke, Platz und Punktzahl',
         JSON.stringify(r.stuecke));
  pruefe(gleich(r.punkteM, [...spurA, ...spurB]), '3  geöffnet = gesendet, Punkt für Punkt (10 Punkte)',
         `${r.punkteM.length} Punkte`);
  pruefe(r.punkteM[2] && r.punkteM[2][2] === null, '3  ein Punkt ohne Höhe bleibt ohne Höhe (-32768)',
         JSON.stringify(r.punkteM[2]));
  const ph = r.ort.phasen || [];
  pruefe(ph.length === 3 && ph[0].lat === 47.7261 && ph[1].t === 40 && ph[2].lat === null,
         '3  Einsatzhülle: drei Phasen, Zeit seit Beginn, eine ohne Ort', JSON.stringify(ph).slice(0, 70));
  const rea = (r.ort.rea || [])[0] || {};
  pruefe(rea.t === 60 && (rea.ereignisse || []).map(e => e.art + '@' + e.t).join(',') === 'adrenalin@75,rosc@95',
         '3  Einsatzhülle: die Reanimation mit ihren Ereignissen', JSON.stringify(rea));
  pruefe(r.started_ts === jetzt && r.phasen_ts.join(',') === [jetzt, jetzt + 40, jetzt + 90].join(','),
         '3  started_ts und phases[].ts als Klartext für die Zuordnung', `${r.started_ts}`);
  pruefe(r.fremd === 'pruefwert', '5  ein Stück unter fremder Bindung geht nicht auf', String(r.fremd));
  pruefe(r.tagStuecke === 2, '4  api/day.php: der Einsatz mit seinen Stücken', `${r.tagStuecke}`);
  pruefe(gleich(r.punkteR, punkte(20)) && r.n === 5, '4  api/day.php: Ruhesegment geöffnet = gesendet, n = 5',
         `${r.punkteR.length} Punkte, n ${r.n}`);
  pruefe(r.sperren.length === 1 && r.sperren[0][0] === jetzt + 210, '4  die Sperrzeit des Schnitts steht daneben',
         JSON.stringify(r.sperren));
  const inDb = php(`echo json_encode(db()->query("SELECT COUNT(*) n, SUM(punkte) p FROM track_stuecke ts
    JOIN missions m ON m.id = ts.owner_id AND ts.owner_type = 'mission' WHERE m.user_id = ${id}")->fetch(PDO::FETCH_ASSOC));`);
  pruefe(/"n":2/.test(inDb) && /"p":"?10/.test(inDb), '2  in der Datenbank: zwei Stücke, zehn Punkte', inDb);
  const klar = php(`echo (int)db()->query("SELECT COUNT(*) FROM mission_phases p JOIN missions m ON m.id = p.mission_id
    WHERE m.user_id = ${id} AND (p.lat IS NOT NULL OR p.lon IS NOT NULL)")->fetchColumn()
    + (int)db()->query("SELECT COUNT(*) FROM resus_sessions r JOIN missions m ON m.id = r.mission_id WHERE m.user_id = ${id}")->fetchColumn();`);
  pruefe(klar === '0', '2  keine Koordinate und keine Reanimation im Klartext', `${klar} Zeilen`);
  pruefe(fehler.length === 0, '   keine Skriptfehler in der Seite', fehler.join(' | ').slice(0, 80));
  await kontext.close();
} catch (e) {
  console.log(`\nNICHT GELAUFEN: ${e && e.message ? e.message : e}`);
  await browser.close();
  try { konto('loeschen'); } catch (e2) { /* egal */ }
  process.exit(2);
} finally {
  try { konto('loeschen'); } catch (e) { console.log(`  Konto ${ADRESSE} nicht gelöscht: ${e.message}`); }
  rmSync(ordner, { recursive: true, force: true });
}
await browser.close();
console.log(`\n${gesamt - offen} ok, ${offen} fehlen`);
process.exit(offen === 0 ? 0 : 1);
