/* Wartungslaufprobe, Browserteil (S11-07; Konzept S11 E-S11-68 bis -74).
 *
 * Anlass: Nr. 43 — Packen und Ausdünnen wandern aus den Jobs in den Browser;
 * was der Server nicht mehr prüfen kann (den Inhalt), prüft diese Probe.
 *
 * WAS ES MISST, mit einem Wegwerfkonto und einem Gerät, das verschlossen sendet:
 *   A  das gepackte Format (E-S11-71): ein Stück aus `referenz.py` öffnet im
 *      Browser Punkt für Punkt gleich; ein Stück aus `gepacktBauen()` öffnet
 *      wieder (Rundlauf, mit Randwerten: keine Höhe, negative, 5000 m, Pol)
 *   B  die Karte im Reiter Backup (M-S11-02): zählt, packt alles zu Stufe 2,
 *      verlustfrei — außer den Punkten im Schnitt, die jetzt fallen —, die
 *      Marke bleibt, die fehlende Ortshöhe steht danach im Blob
 *   C  „Zeitraum löschen" auf einer gepackten Spur (E-S11-74): bleibt Stufe 2,
 *      Marke bleibt, die Punkte im Zeitraum sind fort
 *   D  der stille Lauf der Startseite dünnt nach der Frist aus (Stufe 3) —
 *      die Behalteliste gegen `spur_ausduennung_pruefen()` in PHP, also
 *      unabhängig von der Rechnung im Browser
 *   E  eine Nachlieferung nach dem Ausdünnen wird verworfen und gezählt
 *
 * Nur gegen die örtliche Anlage. Rückgabewert 0 = alles erfüllt.
 */
import { execFileSync } from 'node:child_process';
import { rmSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { randomBytes } from 'node:crypto';

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = join(HIER, '..', '..', '..');
const BASIS = 'https://127.0.0.1:8443';
const ADRESSE  = 'umlauf-wartungslauf@example.invalid';
const PASSWORT = 'umlaufpruefung2026-wartungslauf';
const KONTO = join(WURZEL, 'tools', 'referenzdatensatz', 'vergleich', 'pruefkonto.py');
const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import('file://' + MODUL);

let gesamt = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  gesamt++; if (!ok) { offen++; }
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(64)} ${wert}`);
};
const php = code => execFileSync('php', ['-r', 'require "server/db.php"; require_once "server/spur_lib.php"; ' + code],
                                 { cwd: WURZEL, encoding: 'utf8' }).trim();
function konto(befehl, rcDatei) {
  const a = [KONTO, befehl, ADRESSE, '--basis', BASIS, '--admin-email', 'admin@example.invalid',
             '--admin-passwort', 'pruefstandzugang2026'];
  if (befehl === 'anlegen') { a.push('--passwort', PASSWORT, '--rolle', 'user', '--rc-datei', rcDatei); }
  execFileSync('python3', a, { stdio: ['ignore', 'ignore', 'inherit'],
                               env: { ...process.env, PLAYWRIGHT_MODUL: MODUL } });
}

/* ---- Das Gesendete -------------------------------------------------------- */
const jetzt = Math.floor(Date.now() / 1000) - 7200;
const iso = t => new Date(t * 1000).toISOString().replace(/\.\d+Z$/, 'Z');
const tag = iso(jetzt).slice(0, 10);
/* 120 Punkte, fast gerade mit einem Knick und einem Hügel — genug, dass
   Douglas-Peucker viel weglässt und einiges behält. */
const einsatzPunkte = Array.from({ length: 120 }, (_, s) => [
  47.70 + s * 0.0004 + (s > 60 ? (s - 60) * 0.0003 : 0),
  10.30 + s * 0.0005 + Math.sin(s / 7) * 0.00002,
  s === 33 ? null : 700 + (s > 80 && s < 100 ? (s - 80) * 4 : 0) + (s % 3) * 0.4,
  jetzt + s * 15]);
const phase5 = jetzt + 61 * 15 + 3;
const einsatz = {
  kind: 'mission', client_ref: 'm-wartungslauf-1', day: tag, day_ref: 'd-wartungslauf-1',
  started_at: iso(jetzt), ended_at: iso(jetzt + 120 * 15), final: true,
  phases: [{ phase: 2, at: iso(jetzt), lat: null, lon: null },
           { phase: 5, at: iso(phase5), lat: null, lon: null },
           { phase: 9, at: iso(jetzt + 119 * 15), lat: null, lon: null }],
  track: { seq_from: 0, points: einsatzPunkte } };
const ruhePunkte = Array.from({ length: 30 }, (_, s) => [47.60 + s * 0.0002, 10.20, 650 + s, jetzt + 3000 + s * 30]);
const ruhe = { kind: 'rest_segment', client_ref: 'r-wartungslauf-1', day: tag, day_ref: 'd-wartungslauf-1',
  started_at: iso(jetzt + 3000), ended_at: iso(jetzt + 3000 + 29 * 30), final: true,
  track: { seq_from: 0, points: ruhePunkte } };
const ruhe2Punkte = Array.from({ length: 10 }, (_, s) => [47.50 + s * 0.001, 10.10 + s * 0.001, 600 + s, jetzt + 5000 + s * 60]);
const ruhe2 = { kind: 'rest_segment', client_ref: 'r-wartungslauf-2', day: tag, day_ref: 'd-wartungslauf-1',
  started_at: iso(jetzt + 5000), ended_at: iso(jetzt + 5000 + 9 * 60), final: true,
  track: { seq_from: 0, points: ruhe2Punkte } };

const ordner = mkdtempSync(join(tmpdir(), 'wartungslauf-'));
const browser = await chromium.launch();
console.log(`Wartungslaufprobe (Browser, chromium) gegen ${BASIS}`);
try {
  try { konto('loeschen'); } catch (e) { /* gab es nicht */ }
  konto('anlegen', join(ordner, 'rc.json'));
  const id = Number(php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute(['${ADRESSE}']); echo (int)$st->fetchColumn();`));

  const kontext = await browser.newContext({ ignoreHTTPSErrors: true });
  await kontext.route(/tile\.|opentopomap|arcgisonline|basemap\.at/i, r => r.abort());
  /* Bis Teil B bleibt der Lauf aus: Die Anmeldung landet auf der Startseite,
     und der stille Durchgang soll nicht vorgreifen. */
  let sperre = true;
  await kontext.route('**/api/wartungslauf.php', (r) => sperre
    ? r.fulfill({ status: 200, contentType: 'application/json',
                  body: '{"ok":true,"spuren":[],"aufzeichnungen":0,"diensttage":0}' })
    : r.continue());
  const s = await kontext.newPage();
  const fehler = [];
  s.on('pageerror', e => fehler.push(e.message));
  /* Konsolenfehler zählen, außer den Kacheln, die die Probe selbst abbricht
     (`net::ERR_FAILED` ohne Netz nach draußen). */
  s.on('console', m => { if (m.type() === 'error' && !/net::ERR_FAILED/.test(m.text())) { fehler.push(m.text()); } });
  const anlage = s.waitForResponse(r => r.url().includes('/api/kontopaar.php'), { timeout: 120000 }).catch(() => null);
  await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('#loginform input[name="email"]', ADRESSE);
  await s.fill('#loginform input[name="password"]', PASSWORT);
  await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
                     s.click('#loginform button[type="submit"]')]);
  await anlage;
  const sec1 = php(`require_once "server/kontopaar_lib.php"; echo (string)kp_fuer_geraet(db(), ${id});`);

  const devId = 'dev-wartungslauf', devKey = randomBytes(24).toString('hex');
  php(`db()->prepare("INSERT INTO devices (user_id, device_id, api_key_hash, label, active, geraet_art, geraet_modell)
       VALUES (?,?,?,?,1,'uhr','fēnix 6 Pro')")->execute([${id}, '${devId}', geraet_schluessel_hash('${devKey}'), 'Wartungslaufprobe']);`);
  const senden = async (pakete) => {
    const roh = JSON.parse(execFileSync('php', [join(WURZEL, 'tools/proben/huelle/geraet_cli.php')],
      { input: JSON.stringify({ sec1, pakete }), encoding: 'utf8' }));
    const aus = [];
    for (const p of roh) {
      aus.push(await s.evaluate(async ([k, d, key]) => {
        const r = await fetch('ingest.php', { method: 'POST', body: JSON.stringify(k),
          headers: { 'Content-Type': 'application/json', 'X-Device-Id': d, 'X-Api-Key': key } });
        return { code: r.status, daten: await r.json().catch(() => null) };
      }, [p, devId, devKey]));
    }
    return aus;
  };
  const ant = await senden([einsatz, ruhe, ruhe2]);
  pruefe(ant.every(a => a.code === 200 && a.daten && a.daten.ok), '0  drei Aufzeichnungen angenommen',
         ant.map(a => `${a.code}/${a.daten && a.daten.stored_points}`).join(' '));
  const mid = Number(ant[0].daten.id), rid = Number(ant[1].daten.id), rid2 = Number(ant[2].daten.id);
  const dayId = Number(php(`echo (int)db()->query("SELECT day_id FROM missions WHERE id = ${mid}")->fetchColumn();`));
  /* Ein Schnitt im ersten Ruhesegment: die Punkte 10 bis 14 (E-S11-37). */
  const cutVon = ruhePunkte[10][3], cutBis = ruhePunkte[14][3];
  php(`db()->prepare("INSERT INTO track_cuts (user_id, owner_type, owner_id, mission_id, von_ts, bis_ts, n_punkte, erstellt_am)
       VALUES (?, 'rest', ?, ?, ?, ?, 5, UTC_TIMESTAMP())")->execute([${id}, ${rid}, ${mid}, ${cutVon}, ${cutBis}]);`);
  const stand = (art, oid) => JSON.parse(php(`echo json_encode(spur2_stand(db(), '${art}', ${oid}));`));
  const markeVor = { m: stand('mission', mid), r: stand('rest', rid), r2: stand('rest', rid2) };

  /* Die Punkte, wie der Browser sie öffnet — vor und nach. */
  const oeffnen = async () => s.evaluate(async ([dayId]) => {
    const d = await (await fetch('api/day.php?d=' + dayId)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    const k = await EdOrtsdaten.schluessel(ck);
    const aus = {};
    for (const m of d.missions) { aus['m' + m.id] = await EdOrtsdaten.spur(m.spur_stuecke, 'spur', m.client_ref, [], k); }
    for (const r of d.rest_segments) {
      aus['r' + r.id] = await EdOrtsdaten.spur(r.spur_stuecke, 'ruhe', r.client_ref,
        (r.schnitte || []).map(c => ({ von: c.von_ts, bis: c.bis_ts })), k);
    }
    return aus;
  }, [dayId]);
  await s.goto(`${BASIS}/tag_spuren.php?d=${dayId}`, { waitUntil: 'networkidle' });
  const vorher = await oeffnen();
  const punkteGleich = (a, b) => JSON.stringify(a.map(p => [p.lat, p.lon, p.ele, p.ts]))
                              === JSON.stringify(b.map(p => [p.lat, p.lon, p.ele, p.ts]));

  /* ---- A  Das Format ------------------------------------------------------ */
  console.log('\nA — das gepackte Format');
  const ref = JSON.parse(execFileSync('python3', ['-c', `
import sys, json, base64; sys.path.insert(0, 'tools/proben/huelle'); sys.path.insert(0, 'tools/referenzdatensatz/einspielen')
import referenz, ortsdaten
from cryptography.hazmat.primitives.asymmetric import ec
from cryptography.hazmat.primitives import serialization
k = ec.generate_private_key(ec.SECP256R1())
pk = base64.b64encode(k.private_bytes(serialization.Encoding.DER, serialization.PrivateFormat.PKCS8, serialization.NoEncryption())).decode()
P = [{"lat": 47.1234567, "lon": 10.7654321, "ele": None, "ts": 1000}, {"lat": -89.9999999, "lon": 179.9999999, "ele": -412, "ts": 1001},
     {"lat": 0.0000001, "lon": -0.0000001, "ele": 5000, "ts": 1061}, {"lat": 47.5, "lon": 10.5, "ele": 712, "ts": 4000000}]
st = ortsdaten.gepackt_bauen(P, "spur", "r-format", referenz.sec1(k.public_key()), 3, 7, 40)
print(json.dumps({"pk": pk, "st": st, "p": P}))`], { cwd: WURZEL, encoding: 'utf8' }));
  const a1 = await s.evaluate(async ([pk, st]) => {
    const kk = await EdOrtsdaten.schluesselAus(Uint8Array.from(atob(pk), c => c.charCodeAt(0)));
    return EdOrtsdaten.spur([st], 'spur', 'r-format', [], kk);
  }, [ref.pk, ref.st]);
  pruefe(punkteGleich(a1, ref.p), 'A1 Stück aus referenz.py öffnet im Browser gleich', `${a1.length} Punkte`);
  const a2 = await s.evaluate(async (P) => {
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    const k = await EdOrtsdaten.schluessel(ck);
    const st = await EdOrtsdaten.gepacktBauen(P, 'spur', 'r-format', { stufe: 2, seqFrom: 0, nOriginal: 9 });
    const w = await EdOrtsdaten.spur([st], 'spur', 'r-format', [], k);
    let anders = null;
    try { await EdOrtsdaten.spur([Object.assign({}, st, { stufe: 3 })], 'spur', 'r-format', [], k); }
    catch (e) { anders = 'abgewiesen'; }
    return { w, anders, st: { punkte: st.punkte, n_original: st.n_original, stufe: st.stufe } };
  }, ref.p);
  pruefe(punkteGleich(a2.w, ref.p), 'A2 gepacktBauen → spur: Rundlauf mit Randwerten', JSON.stringify(a2.st));
  pruefe(a2.anders === 'abgewiesen', 'A3 umetikettierte Stufe geht nicht auf (Ort gebunden)', String(a2.anders));

  /* ---- B  Die Karte im Reiter Backup ------------------------------------- */
  console.log('\nB — die Karte „GPS-Daten verdichten"');
  sperre = false;
  await s.goto(`${BASIS}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
  await s.waitForSelector('#vd-zustand .meldung', { timeout: 120000 }).catch(() => null);
  const ergebnis = ((await s.locator('#vd-zustand').textContent()) || '').trim();
  const faelligDanach = ((await s.locator('#vd-faellig').textContent()) || '').trim();
  pruefe(/^Fertig: 3 Aufzeichnungen verdichtet, bei 1 Einsatz die Höhe/.test(ergebnis), 'B1 Ergebnis der Karte', ergebnis.slice(0, 70));
  pruefe(faelligDanach === 'nichts', 'B2 danach nichts mehr fällig', faelligDanach);
  const stufen = JSON.parse(php(`echo json_encode(db()->query("SELECT owner_type, owner_id, stufe, COUNT(*) n FROM track_stuecke
    WHERE (owner_type = 'mission' AND owner_id = ${mid}) OR (owner_type = 'rest' AND owner_id IN (${rid}, ${rid2}))
    GROUP BY owner_type, owner_id, stufe")->fetchAll(PDO::FETCH_ASSOC));`));
  pruefe(stufen.length === 3 && stufen.every(x => Number(x.stufe) === 2 && Number(x.n) === 1),
         'B3 je Spur ein Stück der Stufe 2', JSON.stringify(stufen.map(x => x.stufe + '×' + x.n)));
  const nach = await oeffnen();
  pruefe(punkteGleich(nach['m' + mid], vorher['m' + mid]), 'B4 Einsatz: Punkte verlustfrei', `${nach['m' + mid].length} Punkte`);
  pruefe(punkteGleich(nach['r' + rid], vorher['r' + rid]) && vorher['r' + rid].length === 25,
         'B5 Ruhesegment: die 5 Punkte im Schnitt sind fort, der Rest gleich', `${nach['r' + rid].length} von 30`);
  const ohneSperre = await s.evaluate(async ([dayId, rid]) => {
    const d = await (await fetch('api/day.php?d=' + dayId)).json();
    const r = d.rest_segments.find(x => x.id === rid);
    const k = await EdOrtsdaten.schluessel(await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER));
    return (await EdOrtsdaten.spur(r.spur_stuecke, 'ruhe', r.client_ref, [], k)).length;
  }, [dayId, rid]);
  pruefe(ohneSperre === 25, 'B6 auch ohne Sperrzeiten geöffnet: physisch 25 Punkte', String(ohneSperre));
  const markeNach = { m: stand('mission', mid), r: stand('rest', rid), r2: stand('rest', rid2) };
  pruefe(['m', 'r', 'r2'].every(k => markeNach[k].naechste_seq === markeVor[k].naechste_seq),
         'B7 Fortsetzungsmarken unverändert', ['m', 'r', 'r2'].map(k => markeNach[k].naechste_seq).join('/'));
  const blob = await s.evaluate(async (mid) => {
    const m = await (await fetch('api/mission.php?id=' + mid)).json();
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    return m.pat_blob ? JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob)) : null;
  }, mid);
  const naechster = einsatzPunkte.reduce((b, p) => Math.abs(p[3] - phase5) < Math.abs(b[3] - phase5) ? p : b);
  const hoeheSoll = Math.round(naechster[2]);
  pruefe(blob && blob.ortshoehe === hoeheSoll, 'B8 Ortshöhe nachgetragen (Phase 5, nächster Punkt)', `${blob && blob.ortshoehe} / ${hoeheSoll}`);

  /* ---- C  Zeitraum löschen auf Stufe 2 ----------------------------------- */
  console.log('\nC — Zeitraum löschen auf einer gepackten Spur');
  const hhmm = t => new Intl.DateTimeFormat('de-DE', { timeZone: 'Europe/Berlin', hour: '2-digit',
    minute: '2-digit', hour12: false }).format(new Date(t * 1000));
  const von = ruhe2Punkte[3][3] - (ruhe2Punkte[3][3] % 60), bis = ruhe2Punkte[5][3] - (ruhe2Punkte[5][3] % 60);
  const soll = ruhe2Punkte.filter(p => p[3] >= von && p[3] <= bis).length;
  await s.goto(`${BASIS}/index.php?d=${dayId}`, { waitUntil: 'networkidle' });
  await s.click(`[data-schnitt-auf="${rid2}"]`);
  await s.fill(`#s-beg-${rid2}`, hhmm(von)); await s.dispatchEvent(`#s-beg-${rid2}`, 'input');
  await s.fill(`#s-end-${rid2}`, hhmm(bis)); await s.dispatchEvent(`#s-end-${rid2}`, 'input');
  await s.click('[data-zeitraum-loeschen]');
  await s.locator('dialog[open] [data-act="yes"]').click({ timeout: 30000 });
  await s.waitForSelector('[data-schnittmeldung]', { timeout: 60000 }).catch(() => null);
  const c = JSON.parse(php(`echo json_encode(db()->query("SELECT stufe, punkte, seq_from + n_original m FROM track_stuecke
    WHERE owner_type = 'rest' AND owner_id = ${rid2}")->fetchAll(PDO::FETCH_ASSOC));`));
  pruefe(c.length === 1 && Number(c[0].stufe) === 2 && Number(c[0].punkte) === 10 - soll && Number(c[0].m) === 10,
         'C1 bleibt ein Stück der Stufe 2, Marke 10, ohne den Zeitraum', JSON.stringify(c));
  const nachC = (await oeffnen())['r' + rid2];
  pruefe(nachC.length === 10 - soll && nachC.every(p => p.ts < von || p.ts > bis), 'C2 geöffnet: die Punkte im Zeitraum fehlen',
         `${nachC.length} Punkte`);

  /* ---- D  Ausdünnen über den stillen Lauf der Startseite ----------------- */
  console.log('\nD — Ausdünnen nach der Frist (Startseite, still)');
  php(`db()->exec("UPDATE missions SET started_at = DATE_SUB(started_at, INTERVAL 40 DAY),
       ended_at = DATE_SUB(ended_at, INTERVAL 40 DAY) WHERE id = ${mid}");`);
  const vorD = (await oeffnen())['m' + mid];
  await s.goto(`${BASIS}/index.php`, { waitUntil: 'load' });
  let stufeD = 0;
  for (let i = 0; i < 60 && stufeD !== 3; i++) {
    await s.waitForTimeout(500);
    stufeD = Number(php(`echo (int)db()->query("SELECT MAX(stufe) FROM track_stuecke WHERE owner_type = 'mission' AND owner_id = ${mid}")->fetchColumn();`));
  }
  pruefe(stufeD === 3, 'D1 die Startseite hat still ausgedünnt (Stufe 3)', String(stufeD));
  const nachD = (await oeffnen())['m' + mid];
  const idx = nachD.map(p => vorD.findIndex(q => q.ts === p.ts));
  const phasen = JSON.parse(php(`echo json_encode(array_map('intval', db()->query("SELECT UNIX_TIMESTAMP(occurred_at) FROM mission_phases WHERE mission_id = ${mid}")->fetchAll(PDO::FETCH_COLUMN)));`));
  const pruefDatei = join(ordner, 'd.json');
  execFileSync('bash', ['-c', `cat > ${pruefDatei}`], { input: JSON.stringify({ p: vorD.map((p, i) => [i, p.lat, p.lon, p.ele, p.ts]), b: idx, z: phasen }) });
  const urteil = php(`$d = json_decode(file_get_contents('${pruefDatei}'), true);
    $f = spur_ausduennung_pruefen($d['p'], $d['b'], $d['z']); echo $f === null ? 'in Ordnung' : $f;`);
  pruefe(nachD.length < vorD.length && !idx.includes(-1) && urteil === 'in Ordnung',
         'D2 Behalteliste: Teilmenge, PHP-Prüfung (2 m / 3 m, Schutzpunkte)', `${nachD.length} von ${vorD.length}, ${urteil}`);
  pruefe(stand('mission', mid).naechste_seq === markeVor.m.naechste_seq, 'D3 Marke bleibt nach dem Ausdünnen',
         String(stand('mission', mid).naechste_seq));

  /* ---- E  Nachlieferung nach dem Ausdünnen ------------------------------- */
  console.log('\nE — Nachlieferung nach dem Ausdünnen');
  const e = await senden([{ kind: 'mission', client_ref: 'm-wartungslauf-1', day: tag, day_ref: 'd-wartungslauf-1',
    started_at: iso(jetzt), final: true, track: { seq_from: 120, points: [[47.8, 10.4, 700, jetzt + 121 * 15]] } }]);
  const stueckeE = Number(php(`echo (int)db()->query("SELECT COUNT(*) FROM track_stuecke WHERE owner_type = 'mission' AND owner_id = ${mid}")->fetchColumn();`));
  pruefe(e[0].code === 200 && e[0].daten && Number(e[0].daten.dropped_points) === 1 && stueckeE === 1,
         'E1 verworfen und gezählt, kein neues Stück', `dropped ${e[0].daten && e[0].daten.dropped_points}, ${stueckeE} Stück`);

  pruefe(fehler.length === 0, '   keine Skriptfehler, keine Konsolenfehler', fehler.join(' | ').slice(0, 80));
  await kontext.close();
} catch (e) {
  console.log(`\nNICHT GELAUFEN: ${e && e.message ? e.message : e}`);
  await browser.close();
  try { konto('loeschen'); } catch (e2) { /* egal */ }
  rmSync(ordner, { recursive: true, force: true });
  process.exit(2);
}
try { konto('loeschen'); } catch (e) { console.log(`  Konto ${ADRESSE} nicht gelöscht: ${e.message}`); }
rmSync(ordner, { recursive: true, force: true });
await browser.close();
console.log(`\n${gesamt - offen} ok, ${offen} fehlen`);
process.exit(offen === 0 ? 0 : 1);
