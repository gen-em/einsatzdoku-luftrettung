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
 *   6. die Einsatzansicht öffnet selbst: Spur, Phasen, Reanimation, kein
 *      Ladeplatz und keine Fehlermeldung danach (S11-04c)
 *   7. die Diensttagsansicht öffnet die Stücke; die gesperrten Punkte des
 *      Ruhesegments fehlen (E-S11-37)
 *   8. die Spurenseite des Diensttags (`tag_spuren.php`) zeichnet beide Linien
 *   9. die Zeitrechnung des Browsers (`EdFormat.ortszeitZuEpoch`,
 *      `EdOrtsdaten.einsatzZeiten`) gleich der des Servers (`local_to_utc`),
 *      auch an beiden Zeitumstellungen (S11-05b)
 *  10. das Formular öffnet die Hülle, zeigt die Reanimation, speichert
 *      unverändert: Orte der Phasen und Reanimation bleiben, nichts im
 *      Klartext (F-S11-02, -41)
 *  11. ein im Formular nachgetragener Einsatz mit Reanimation: alles in der
 *      Hülle, keine Zeile in `resus_sessions`
 *  12. Transportziel und Höhe des Einsatzorts im Blob (S11-05b): im Formular
 *      gesetzt und gespeichert, Klartextspalten leer, in keiner Antwort des
 *      Servers im Klartext, in der Ansicht nach dem Entsperren; Höhe aus der
 *      Spur zur Phase 5; „ambulant" speichert kein Ziel
 *  13. eine Einsatzhülle, in der Datenbank um ein Byte verändert, geht nicht
 *      auf: die Ansicht sagt es, und der Reiter System hat eine Meldung mit
 *      Art, Kennung und Grund — ohne Inhalt (S11-04d, M-S11-01 d); das
 *      Formular verweigert dann das Speichern
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
  /* 6 — die Einsatzansicht öffnet selbst (S11-04c): Spur gezeichnet, Phasen
   * und Reanimation aus der Hülle, der Ladeplatz wieder fort */
  await s.goto(`${BASIS}/einsatz.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => document.querySelectorAll('#phasebody .phasen-zeile').length > 0,
                          null, { timeout: 30000 }).catch(() => null);
  const e6 = await s.evaluate(() => ({
    linien: document.querySelectorAll('#map .leaflet-overlay-pane path').length,
    phasen: document.querySelectorAll('#phasebody .phasen-zeile').length,
    rea: document.getElementById('resus-section').hidden ? 0
       : document.querySelectorAll('#resus-tables .phasen-zeile').length,
    lade: [...document.querySelectorAll('.ladeplatz')].filter(e => !e.hidden).length,
    fehlerkasten: document.querySelectorAll('[data-ortsdaten]').length,
    spur: (typeof M !== 'undefined' && M.spur_punkte) ? M.spur_punkte.length : -1,
  }));
  pruefe(e6.linien >= 1 && e6.spur === 10, '6  Einsatzansicht: Spur aus den Stücken gezeichnet',
         `${e6.linien} Linie(n), ${e6.spur} Punkte`);
  pruefe(e6.phasen === 3 && e6.rea === 3, '6  Einsatzansicht: 3 Phasen, Reanimation mit 3 Zeilen',
         `${e6.phasen} Phasen, ${e6.rea} Reanimationszeilen`);
  pruefe(e6.lade === 0 && e6.fehlerkasten === 0, '6  kein Ladeplatz mehr sichtbar, keine Fehlermeldung',
         `${e6.lade} sichtbar, ${e6.fehlerkasten} Fehler`);
  /* 7 — die Diensttagsansicht öffnet die Stücke (S11-04c): Einsatz- und
   * Ruhespur gezeichnet, der Schnitt blendet Punkte aus */
  await s.goto(`${BASIS}/index.php?d=${dayId}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => document.querySelectorAll('#map .leaflet-overlay-pane path').length >= 2,
                          null, { timeout: 30000 }).catch(() => null);
  const e7 = await s.evaluate(() => ({
    linien: document.querySelectorAll('#map .leaflet-overlay-pane path').length,
    lade: [...document.querySelectorAll('.ladeplatz')].filter(e => !e.hidden).length,
    ruhe: (typeof dayRest !== 'undefined' && dayRest[0]) ? dayRest[0].track.length : -1,
    einsatz: (typeof dayMissions !== 'undefined' && dayMissions[0]) ? dayMissions[0].track.length : -1,
  }));
  pruefe(e7.linien >= 2 && e7.einsatz === 10, '7  Diensttag: Einsatzspur und Ruhespur gezeichnet',
         `${e7.linien} Linien, Einsatz ${e7.einsatz} Punkte`);
  pruefe(e7.ruhe === 3, '7  Diensttag: die zwei gesperrten Punkte des Ruhesegments fehlen',
         `${e7.ruhe} von 5 Punkten`);
  pruefe(e7.lade === 0, '7  kein Ladeplatz mehr sichtbar', `${e7.lade}`);
  /* 8 — die Spurenseite des Diensttags (tag_spuren.php) */
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => document.querySelectorAll('#map .leaflet-overlay-pane path').length >= 2,
                          null, { timeout: 30000 }).catch(() => null);
  const e8 = await s.evaluate(() => ({
    linien: document.querySelectorAll('#map .leaflet-overlay-pane path').length,
    zeilen: document.querySelectorAll('.spur-zeile').length,
    lade: [...document.querySelectorAll('.ladeplatz')].filter(e => !e.hidden).length,
  }));
  pruefe(e8.linien >= 2 && e8.zeilen === 2 && e8.lade === 0,
         '8  tag_spuren.php: zwei Zeilen, zwei Linien, kein Ladeplatz', JSON.stringify(e8));
  /* 9 — die Zeitrechnung des Browsers gegen PHP (S11-05b). Fälle: ein
   * Sommertag, beide Zeitumstellungen direkt und über „+1 Tag". */
  const faelle = [['2026-07-01', '23:50', 0], ['2026-07-01', '00:10', 1], ['2026-03-29', '02:30', 0],
                  ['2026-03-28', '02:30', 1], ['2026-10-25', '02:30', 0], ['2026-10-24', '02:30', 1],
                  ['2026-10-25', '01:59', 0], ['2026-10-25', '03:00', 0], ['2026-12-31', '23:59', 1],
                  ['2026-02-30', '10:00', 0], ['2026-07-01', '24:00', 0]];
  const phpZeiten = JSON.parse(php(`echo json_encode(array_map(fn($f) => ($u = local_to_utc($f[0], $f[1], $f[2]))
    === null ? null : strtotime($u . ' UTC'), ${JSON.stringify(faelle)}));`));
  const jsZeiten = await s.evaluate(f => f.map(([d, h, v]) => EdFormat.ortszeitZuEpoch(d, h, v, 'Europe/Berlin')), faelle);
  const abw9 = faelle.filter((f, i) => phpZeiten[i] !== jsZeiten[i] && !(phpZeiten[i] === null && jsZeiten[i] === null)
    && !(f[0] === '2026-02-30'));
  pruefe(abw9.length === 0, '9  Ortszeit → UTC: Browser gleich PHP, auch an der Zeitumstellung',
         `${faelle.length} Fälle, ${abw9.length} abweichend ${JSON.stringify(abw9).slice(0, 60)}`);

  /* 10 — das Formular: öffnen, Reanimation da, unverändert speichern */
  const vorherOrt = await s.evaluate(async (mid) => {
    const m = await (await fetch('api/mission.php?id=' + mid)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    await EdOrtsdaten.fuerAnsicht(m, ck);
    /* Ohne Sekunden: Das Formular rechnet in ganzen Minuten (wie seit jeher),
     * die Probe sendet drei Phasen in derselben Minute. Verglichen werden
     * Nummer, Ort und Reanimation. */
    return { phasen: m.phases.map(p => [p.phase, p.lat, p.lon]), rea: m.resus };
  }, mid);
  await s.goto(`${BASIS}/einsatz_form.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => document.querySelectorAll('#rearows .rea-sitzung').length > 0,
                          null, { timeout: 30000 }).catch(() => null);
  const f10 = await s.evaluate(() => ({
    sitzungen: document.querySelectorAll('#rearows .rea-sitzung').length,
    ereignisse: document.querySelectorAll('#rearows .rea-zeit').length,
    namen: document.querySelectorAll('#rearows [name]').length,
    start: (document.querySelector('#rearows .rea-start') || {}).value,
  }));
  pruefe(f10.sitzungen === 1 && f10.ereignisse === 2 && f10.namen === 0,
         '10 Formular: Reanimation aus der Hülle, Felder ohne name', JSON.stringify(f10));
  await Promise.all([s.waitForURL(/einsatz\.php\?id=/, { timeout: 60000 }),
                     s.evaluate(() => document.getElementById('missionform').requestSubmit())]);
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const nachherOrt = await s.evaluate(async (mid) => {
    const m = await (await fetch('api/mission.php?id=' + mid)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    await EdOrtsdaten.fuerAnsicht(m, ck);
    /* Ohne Sekunden: Das Formular rechnet in ganzen Minuten (wie seit jeher),
     * die Probe sendet drei Phasen in derselben Minute. Verglichen werden
     * Nummer, Ort und Reanimation. */
    return { phasen: m.phases.map(p => [p.phase, p.lat, p.lon]), rea: m.resus };
  }, mid);
  pruefe(JSON.stringify(nachherOrt) === JSON.stringify(vorherOrt),
         '10 nach dem Speichern: Phasenorte und Reanimation unverändert', JSON.stringify(nachherOrt.phasen).slice(0, 70));
  const klar10 = php(`echo (int)db()->query("SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ${mid}")->fetchColumn()
    + (int)db()->query("SELECT COUNT(*) FROM mission_phases WHERE mission_id = ${mid} AND lat IS NOT NULL")->fetchColumn();`);
  pruefe(klar10 === '0', '10 nach dem Speichern: nichts im Klartext', `${klar10} Zeilen`);

  /* 10 — Gegenprobe zum Wächter: die Phasenzeiten des Browsers auf dem Weg
   * verfälscht (eine Minute später). Der Server muss ablehnen und melden. */
  const vorWaechter = php(`echo md5((string)db()->query("SELECT ort_huelle FROM missions WHERE id = ${mid}")->fetchColumn());`);
  await s.goto(`${BASIS}/einsatz_form.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => typeof PAT_CK !== 'undefined' && PAT_CK, null, { timeout: 30000 }).catch(() => null);
  await s.route(/einsatz_form\.php/, async (route) => {
    const req = route.request();
    if (req.method() !== 'POST') { return route.continue(); }
    const form = new URLSearchParams(req.postData() || '');
    const z = JSON.parse(form.get('ort_phasen') || '[]');
    if (z[0]) { z[0][1] += 60; }
    form.set('ort_phasen', JSON.stringify(z));
    return route.continue({ postData: form.toString() });
  });
  await Promise.all([s.waitForLoadState('networkidle'),
                     s.evaluate(() => document.getElementById('missionform').requestSubmit())]);
  await s.waitForTimeout(1500);
  await s.unroute(/einsatz_form\.php/);
  const textW = (await s.locator('main').textContent().catch(() => '')) || '';
  const nachWaechter = php(`echo md5((string)db()->query("SELECT ort_huelle FROM missions WHERE id = ${mid}")->fetchColumn());`);
  pruefe(/nicht gleich rechnen/.test(textW) && vorWaechter === nachWaechter,
         '10 Gegenprobe: verfälschte Phasenzeiten → nichts gespeichert', `${/nicht gleich rechnen/.test(textW)}, Hülle ${vorWaechter === nachWaechter ? 'gleich' : 'GEÄNDERT'}`);

  /* 10 — Altbestand: Orte und Reanimation im Klartext, keine Hülle. Das
   * Formular übernimmt sie beim Speichern in die Hülle (E-S11-45). */
  const altId = Number(php(`$pdo = db(); require_once "server/geraete_lib.php";
    $dev = geraet_virtuell_sicherstellen($pdo, ${id});
    $pdo->prepare("INSERT INTO missions (user_id, device_id, client_ref, day_id, started_at, ended_at, final, uhr_gesperrt, origin)
      VALUES (?, ?, 'man-alt0000000000001', ?, FROM_UNIXTIME(?), FROM_UNIXTIME(?), 1, 1, 'manual')")
      ->execute([${id}, $dev, ${dayId}, ${jetzt + 3600}, ${jetzt + 4200}]);
    $m = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO mission_phases (mission_id, phase, occurred_at, lat, lon) VALUES (?,2,FROM_UNIXTIME(?),47.5,10.5),(?,4,FROM_UNIXTIME(?),47.6,10.6)")
      ->execute([$m, ${jetzt + 3600}, $m, ${jetzt + 4200}]);
    $pdo->prepare("INSERT INTO resus_sessions (mission_id, started_at) VALUES (?, FROM_UNIXTIME(?))")->execute([$m, ${jetzt + 3900}]);
    $sid = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO resus_events (session_id, type, occurred_at) VALUES (?, 'rosc', FROM_UNIXTIME(?))")->execute([$sid, ${jetzt + 4000}]);
    echo $m;`));
  await s.goto(`${BASIS}/einsatz_form.php?id=${altId}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => typeof PAT_CK !== 'undefined' && PAT_CK, null, { timeout: 30000 }).catch(() => null);
  await Promise.all([s.waitForURL(/einsatz\.php\?id=/, { timeout: 60000 }),
                     s.evaluate(() => document.getElementById('missionform').requestSubmit())]);
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const alt10 = await s.evaluate(async (id) => {
    const m = await (await fetch('api/mission.php?id=' + id)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    await EdOrtsdaten.fuerAnsicht(m, ck);
    return { phasen: m.phases.map(p => [p.phase, p.lat, p.lon]), rea: (m.resus || []).map(r => r.map(e => e.label)) };
  }, altId);
  const klarAlt = php(`echo (int)db()->query("SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ${altId}")->fetchColumn()
    + (int)db()->query("SELECT COUNT(*) FROM mission_phases WHERE mission_id = ${altId} AND lat IS NOT NULL")->fetchColumn();`);
  pruefe(JSON.stringify(alt10.phasen) === '[[2,47.5,10.5],[4,47.6,10.6]]' && alt10.rea.length === 1
         && alt10.rea[0].length === 2 && klarAlt === '0',
         '10 Altbestand: Orte und Reanimation in die Hülle übernommen, Klartext fort',
         `${JSON.stringify(alt10).slice(0, 60)} · ${klarAlt} Klartextzeilen`);

  /* 11 — ein Einsatz, im Formular nachgetragen, mit Reanimation */
  await s.goto(`${BASIS}/einsatz_form.php?d=${dayId}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => typeof PAT_CK !== 'undefined' && PAT_CK, null, { timeout: 30000 }).catch(() => null);
  await s.evaluate(() => {
    const z = document.querySelector('#phaserows .phasen-eingabe .zeitfeld'); z.value = '10:00';
    document.getElementById('addrea').click();
    document.querySelector('#rearows .rea-start').value = '10:05';
  });
  await Promise.all([s.waitForURL(/einsatz\.php\?id=/, { timeout: 60000 }),
                     s.evaluate(() => document.getElementById('missionform').requestSubmit())]);
  const neuId = Number(new URL(s.url()).searchParams.get('id'));
  const n11 = JSON.parse(php(`echo json_encode(db()->query("SELECT client_ref, ort_huelle IS NOT NULL h,
    (SELECT COUNT(*) FROM resus_sessions WHERE mission_id = ${neuId}) r FROM missions WHERE id = ${neuId}")->fetch(PDO::FETCH_ASSOC));`));
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const rea11 = await s.evaluate(async (id) => {
    const m = await (await fetch('api/mission.php?id=' + id)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    await EdOrtsdaten.fuerAnsicht(m, ck);
    return m.resus;
  }, neuId);
  pruefe(neuId > 0 && /^man-[0-9a-f]{16}$/.test(n11.client_ref) && String(n11.h) === '1' && String(n11.r) === '0'
         && rea11 && rea11[0] && rea11[0][0].time === '10:05',
         '11 Nachtrag: Reanimation in der Hülle, keine Klartextzeile', JSON.stringify({ n11, rea: rea11 }).slice(0, 80));

  /* 12 — Transportziel und Ortshöhe im Blob (S11-05b). Phase 5 auf die
   * Minute von Phase 4 (früher läge sie nach der Mitternachtsregel am
   * Folgetag); erwartet ist die Höhe des zeitlich nächsten gesendeten Punkts,
   * gerechnet wie `compute_site_elevation()`. */
  const ts5 = Math.floor((jetzt + 90) / 60) * 60;
  const hhmm5 = new Intl.DateTimeFormat('de-DE', { timeZone: 'Europe/Berlin', hour: '2-digit',
    minute: '2-digit', hour12: false }).format(new Date(ts5 * 1000));
  const naechster5 = [...spurA, ...spurB].reduce((b, p) => Math.abs(p[3] - ts5) < Math.abs(b[3] - ts5) ? p : b);
  const hoeheSoll = naechster5[2] === null ? null : Math.round(naechster5[2]);
  await s.goto(`${BASIS}/einsatz_form.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => typeof PAT_CK !== 'undefined' && PAT_CK, null, { timeout: 30000 }).catch(() => null);
  await s.evaluate((hhmm) => {
    const sel = document.querySelector('select[name="f_transport_mode"]');
    sel.value = 'air'; sel.dispatchEvent(new Event('change', { bubbles: true }));
    ORTSFELD_PAT.transport_dest.steuer.setzen({ addr: 'Klinikum Probe', lat: 47.8, lon: 10.2 });
    document.getElementById('addrow').click();
    const zeilen = document.querySelectorAll('#phaserows .phasen-eingabe');
    const z = zeilen[zeilen.length - 1];
    z.querySelector('select').value = '5';
    z.querySelector('.zeitfeld').value = hhmm;
  }, hhmm5);
  await Promise.all([s.waitForURL(/einsatz\.php\?id=/, { timeout: 60000 }),
                     s.evaluate(() => document.getElementById('missionform').requestSubmit())]);
  const spalten12 = JSON.parse(php(`echo json_encode(db()->query("SELECT transport_dest, dest_lat, dest_lon, site_ele_m
    FROM missions WHERE id = ${mid}")->fetch(PDO::FETCH_ASSOC));`));
  const roh12 = await s.evaluate(async (mid) => (await (await fetch('api/mission.php?id=' + mid)).text())
    + (await (await fetch('einsatz.php?id=' + mid)).text()), mid);
  await s.waitForFunction(() => /Klinikum Probe/.test(document.querySelector('main').textContent),
                          null, { timeout: 30000 }).catch(() => null);
  const ansicht12 = await s.evaluate(() => ({
    ziel: /Klinikum Probe/.test(document.querySelector('main').textContent),
    hoehe: (document.querySelector('main').textContent.match(/Höhe (Einsatzort )?(\d+) m/) || [])[2] || null,
  }));
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const blob12 = await s.evaluate(async (mid) => {
    const m = await (await fetch('api/mission.php?id=' + mid)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    return JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob));
  }, mid);
  pruefe(Object.values(spalten12).every(v => v === null), '12 Transportziel, Koordinate, Höhe: Klartextspalten leer',
         JSON.stringify(spalten12));
  pruefe(blob12.transport_dest && blob12.transport_dest.addr === 'Klinikum Probe' && blob12.transport_dest.lat === 47.8,
         '12 im Blob: transport_dest {addr, lat, lon}', JSON.stringify(blob12.transport_dest));
  pruefe(hoeheSoll !== null && blob12.ortshoehe === hoeheSoll, '12 im Blob: ortshoehe aus der Spur zur Phase 5',
         `${blob12.ortshoehe} (Soll ${hoeheSoll})`);
  pruefe(!/Klinikum Probe/.test(roh12), '12 kein Klartext in api/mission.php und im Markup von einsatz.php',
         /Klinikum Probe/.test(roh12) ? 'GEFUNDEN' : 'nicht gefunden');
  pruefe(ansicht12.ziel, '12 Ansicht: Transportziel nach dem Entsperren', JSON.stringify(ansicht12));
  /* … und „ambulant" speichert kein Ziel (das Feld ist dann ausgeblendet). */
  await s.goto(`${BASIS}/einsatz_form.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForFunction(() => typeof PAT_CK !== 'undefined' && PAT_CK
    && document.getElementById('f_transport_dest_addr').value !== '', null, { timeout: 30000 }).catch(() => null);
  await s.evaluate(() => {
    const sel = document.querySelector('select[name="f_transport_mode"]');
    sel.value = 'ambulant'; sel.dispatchEvent(new Event('change', { bubbles: true }));
  });
  await Promise.all([s.waitForURL(/einsatz\.php\?id=/, { timeout: 60000 }),
                     s.evaluate(() => document.getElementById('missionform').requestSubmit())]);
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const amb12 = await s.evaluate(async (mid) => {
    const m = await (await fetch('api/mission.php?id=' + mid)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    return JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob));
  }, mid);
  pruefe(!amb12.transport_dest && amb12.ortshoehe === hoeheSoll, '12 „ambulant": kein Ziel im Blob, Höhe bleibt',
         JSON.stringify(amb12).slice(0, 70));

  /* 13 — eine verdorbene Einsatzhülle: das letzte Byte (Prüfwert) gekippt */
  php(`db()->exec("UPDATE missions SET ort_huelle = CONCAT(LEFT(ort_huelle, LENGTH(ort_huelle) - 1),
       CHAR(ASCII(RIGHT(ort_huelle, 1)) ^ 1)) WHERE id = ${mid}");`);
  /* Der Topf `kontopaar` zählt je Adresse, fünf in der Stunde — dieselbe
   * Grenze, die auch die Kontopaarprobe vor ihrem Schritt leert. Zwei Läufe
   * in einer Stunde hätten ihn sonst gefüllt, und die Meldung bliebe aus,
   * ohne dass am Code etwas falsch wäre. */
  php(`db()->exec("DELETE FROM rate_limits WHERE topf = 'kontopaar'");`);
  const vorher = Number(php(`echo (int)db()->query("SELECT COALESCE(MAX(id), 0) FROM protokoll_ereignisse")->fetchColumn();`));
  const meldung = s.waitForResponse(r => r.url().includes('/api/kontopaar.php'), { timeout: 30000 })
                   .catch(() => null);
  await s.goto(`${BASIS}/einsatz.php?id=${mid}`, { waitUntil: 'networkidle' });
  const antwort = await meldung;
  const e9 = await s.evaluate(() => ({
    fehlerkasten: document.querySelectorAll('[data-ortsdaten="fehler"]').length,
    lade: [...document.querySelectorAll('.ladeplatz')].filter(e => !e.hidden).length,
  }));
  pruefe(e9.fehlerkasten === 1 && e9.lade === 0, '13 verdorbene Hülle: die Ansicht sagt es, kein Ladeplatz',
         JSON.stringify(e9));
  const zeile = php(`$st = db()->prepare("SELECT text FROM protokoll_ereignisse WHERE id > ? AND reiter = 'system'
    AND text LIKE 'ortsdaten:%'"); $st->execute([${vorher}]); echo json_encode($st->fetchAll(PDO::FETCH_COLUMN));`);
  pruefe(antwort && antwort.status() === 200 && /Einsatz \d+, einsatz, Grund pruefwert/.test(zeile)
         && JSON.parse(zeile).length === 1,
         '13 Reiter System: eine Meldung mit Art, Kennung und Grund', zeile.slice(0, 80));
  /* … und das Formular speichert dann nicht (es ersetzte sonst die Hülle). */
  await s.goto(`${BASIS}/einsatz_form.php?id=${mid}`, { waitUntil: 'networkidle' });
  await s.waitForSelector('#ortfehler', { timeout: 30000 }).catch(() => null);
  const huelleVor = php(`echo md5((string)db()->query("SELECT ort_huelle FROM missions WHERE id = ${mid}")->fetchColumn());`);
  await s.evaluate(() => document.getElementById('missionform').requestSubmit());
  await s.waitForTimeout(1500);
  const huelleNach = php(`echo md5((string)db()->query("SELECT ort_huelle FROM missions WHERE id = ${mid}")->fetchColumn());`);
  pruefe(/einsatz_form\.php/.test(s.url()) && huelleVor === huelleNach && await s.locator('#ortfehler').count() === 1,
         '13 Formular mit verdorbener Hülle: Speichern gesperrt, Hülle unverändert', s.url().replace(BASIS, ''));
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
