/* Probe des Einmalwerkzeugs (S11-10; Konzept S11 3.8 Schritt 5, E-S11-19).
 *
 * Anlass: Nr. 43 — der Altbestand im Klartext zieht in die Gerätehülle um,
 * und nur der Browser kann verschließen. Was der Server nicht prüfen kann
 * (den Inhalt), prüft diese Probe: Kommt nach dem Umzug genau heraus, was
 * vorher im Klartext lag?
 *
 * DER ALTBESTAND wird von Hand gelegt, wie ihn Web 21 hinterließ — in einem
 * Wegwerfkonto, die Zeilen per SQL neben das, was ein Gerät verschlossen
 * gesendet hat:
 *   M1  Phasenorte, zwei Reanimationen, Transportziel mit Koordinate und
 *       Ortshöhe, 300 Spurpunkte in `track_points`
 *   M2  im Papierkorb: eine ausgedünnte SPUR1-Spur (Stufe 3, 30 von 90
 *       Punkten) und eine Reanimation
 *   M3  trägt schon eine Einsatzhülle (Phase 2 mit Ort A, Phase 4 ohne) und
 *       daneben Klartext (Phase 2 Ort B, Phase 4 Ort C) — zusammenführen:
 *       A gewinnt, C kommt hinzu
 *   M4  Transportziel neben einem Blob, der nicht aufgeht — bleibt liegen
 *   R1  Ruhesegment: SPUR1-Blob Stufe 2 (Punkte 0–99) und 50 Zeilen dahinter
 *
 * WAS ES MISST
 *   A  die Zählung vorher (Konto, Anlage, Statuszeile orange)
 *   B  der stille Lauf der Startseite: danach kein Klartext außer M4, und
 *      `uhr_gesperrt`/`edited` unberührt
 *   C  geöffnet = vorher: Spurpunkte auf der Auflösung der Innenform,
 *      Phasenorte, Reanimationen, Transportziel und Höhe im Blob
 *   D  M2 ausgedünnt geblieben: ein gepacktes Stück der Stufe 3, Marke 90
 *   E  Wächter: ein Tausch mit falschem Stand ist 409 und ändert nichts
 *   F  ein zweiter Lauf ändert nichts; M4 bleibt gezählt
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
const ADRESSE  = 'umlauf-ortsumzug@example.invalid';
const PASSWORT = 'umlaufpruefung2026-ortsumzug';
const KONTO = join(WURZEL, 'tools', 'referenzdatensatz', 'vergleich', 'pruefkonto.py');
const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import('file://' + MODUL);

let gesamt = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  gesamt++; if (!ok) { offen++; }
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(64)} ${wert}`);
};
const php = code => execFileSync('php', ['-r', 'require "server/db.php"; require_once "server/spur_lib.php"; '
  + 'require_once "server/ortsumzug_lib.php"; ' + code], { cwd: WURZEL, encoding: 'utf8' }).trim();
const phpJson = code => JSON.parse(php('echo json_encode((function () { ' + code + ' })());'));
function konto(befehl, rcDatei) {
  const a = [KONTO, befehl, ADRESSE, '--basis', BASIS, '--admin-email', 'admin@example.invalid',
             '--admin-passwort', 'pruefstandzugang2026'];
  if (befehl === 'anlegen') { a.push('--passwort', PASSWORT, '--rolle', 'user', '--rc-datei', rcDatei); }
  execFileSync('python3', a, { stdio: ['ignore', 'ignore', 'inherit'],
                               env: { ...process.env, PLAYWRIGHT_MODUL: MODUL } });
}

const jetzt = Math.floor(Date.now() / 1000) - 7200;
const iso = t => new Date(t * 1000).toISOString().replace(/\.\d+Z$/, 'Z');
const sql = t => new Date(t * 1000).toISOString().slice(0, 19).replace('T', ' ');
const tag = iso(jetzt).slice(0, 10);
const paket = (ref, o) => Object.assign({ kind: 'mission', client_ref: ref, day: tag, day_ref: 'd-ortsumzug-1',
  started_at: iso(jetzt), ended_at: iso(jetzt + 3600), final: true }, o);

const ordner = mkdtempSync(join(tmpdir(), 'ortsumzug-'));
const browser = await chromium.launch();
console.log(`Probe des Einmalwerkzeugs (Browser, chromium) gegen ${BASIS}`);
try {
  try { konto('loeschen'); } catch (e) { /* gab es nicht */ }
  konto('anlegen', join(ordner, 'rc.json'));
  const uid = Number(php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?");
    $st->execute(['${ADRESSE}']); echo (int)$st->fetchColumn();`));

  const kontext = await browser.newContext({ ignoreHTTPSErrors: true });
  await kontext.route(/tile\.|opentopomap|arcgisonline|basemap\.at/i, r => r.abort());
  /* Bis Teil B bleibt das Werkzeug aus: Die Anmeldung landet auf der
     Startseite, und der stille Lauf soll nicht vorgreifen. */
  let sperre = true;
  await kontext.route('**/api/ortsumzug.php', (r) => sperre
    ? r.fulfill({ status: 200, contentType: 'application/json', body: '{"ok":true,"liste":[],"einsaetze":0,"ruhe":0}' })
    : r.continue());
  const s = await kontext.newPage();
  const fehler = [];
  s.on('pageerror', e => fehler.push(e.message));
  s.on('console', m => { if (m.type() === 'error' && !/net::ERR_FAILED|status of 40[09]/.test(m.text())) { fehler.push(m.text()); } });
  const anlage = s.waitForResponse(r => r.url().includes('/api/kontopaar.php'), { timeout: 120000 }).catch(() => null);
  await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('#loginform input[name="email"]', ADRESSE);
  await s.fill('#loginform input[name="password"]', PASSWORT);
  await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
                     s.click('#loginform button[type="submit"]')]);
  await anlage;
  for (let i = 0; i < 40; i++) {
    if (php(`echo (int)db()->query("SELECT kp_oeffentlich IS NOT NULL FROM users WHERE id = ${uid}")->fetchColumn();`) === '1') { break; }
    await s.waitForTimeout(500);
  }
  const sec1 = php(`require_once "server/kontopaar_lib.php"; echo (string)kp_fuer_geraet(db(), ${uid});`);

  /* ---- Gesendet (verschlossen) ------------------------------------------ */
  const devId = 'dev-ortsumzug', devKey = randomBytes(24).toString('hex');
  php(`db()->prepare("INSERT INTO devices (user_id, device_id, api_key_hash, label, active, geraet_art, geraet_modell)
       VALUES (?,?,?,?,1,'uhr','fēnix 6 Pro')")->execute([${uid}, '${devId}', geraet_schluessel_hash('${devKey}'), 'Probe Einmalwerkzeug']);`);
  const roh = JSON.parse(execFileSync('php', [join(WURZEL, 'tools/proben/huelle/geraet_cli.php')], {
    encoding: 'utf8', input: JSON.stringify({ sec1, pakete: [
      paket('m-ou-1', {}), paket('m-ou-2', {}),
      paket('m-ou-3', { phases: [{ phase: 2, at: iso(jetzt), lat: 47.111111, lon: 10.111111 },
                                 { phase: 4, at: iso(jetzt + 600), lat: null, lon: null }] }),
      paket('m-ou-4', {}),
      { kind: 'rest_segment', client_ref: 'r-ou-1', day: tag, day_ref: 'd-ortsumzug-1',
        started_at: iso(jetzt + 4000), ended_at: iso(jetzt + 5000), final: true }] }) }));
  const ant = [];
  for (const p of roh) {
    ant.push(await s.evaluate(async ([k, d, key]) => {
      const r = await fetch('ingest.php', { method: 'POST', body: JSON.stringify(k),
        headers: { 'Content-Type': 'application/json', 'X-Device-Id': d, 'X-Api-Key': key } });
      return { code: r.status, daten: await r.json().catch(() => null) };
    }, [p, devId, devKey]));
  }
  if (!ant.every(a => a.code === 200 && a.daten && a.daten.ok)) {
    throw new Error('Gesendetes nicht angenommen: ' + JSON.stringify(ant.map(a => a.code)));
  }
  const [m1, m2, m3, m4, r1] = ant.map(a => Number(a.daten.id));

  /* ---- Der Altbestand, von Hand ------------------------------------------ */
  const punkte = (n, la, lo, t0, dt, h) => Array.from({ length: n }, (_, i) =>
    [i, +(la + i * 0.00031).toFixed(6), +(lo + i * 0.00047).toFixed(6), i % 17 === 5 ? null : +(h + (i % 37) * 0.7).toFixed(1), t0 + i * dt]);
  const p1 = punkte(300, 47.7261, 10.3159, jetzt, 10, 674);
  const p2 = punkte(30, 47.6, 10.2, jetzt, 60, 500);
  const pr = punkte(150, 47.5, 10.1, jetzt + 4000, 5, 800);
  const datei = join(ordner, 'alt.json');
  execFileSync('bash', ['-c', `cat > ${datei}`], { input: JSON.stringify({ p1, p2, pr }) });
  php(`$d = json_decode(file_get_contents('${datei}'), true); $pdo = db();
    $ph = $pdo->prepare("INSERT INTO mission_phases (mission_id, phase, occurred_at, lat, lon) VALUES (?,?,?,?,?)");
    $ph->execute([${m1}, 2, '${sql(jetzt)}', 47.7261, 10.3159]);
    $ph->execute([${m1}, 4, '${sql(jetzt + 900)}', 47.7301, 10.3201]);
    $ph->execute([${m1}, 5, '${sql(jetzt + 1200)}', 47.7302, 10.3202]);
    $ph->execute([${m1}, 9, '${sql(jetzt + 3000)}', null, null]);
    einsatz_reas_ersetzen($pdo, ${m1}, [
      ['started_at' => '${sql(jetzt + 1300)}', 'events' => [['adrenalin', '${sql(jetzt + 1400)}'], ['rhythmuskontrolle', '${sql(jetzt + 1420)}']]],
      ['started_at' => '${sql(jetzt + 2000)}', 'events' => []]]);
    $pdo->prepare("UPDATE missions SET transport_dest = 'Klinikum Kempten', dest_lat = 47.722131, dest_lon = 10.334512,
                   site_ele_m = 701 WHERE id = ?")->execute([${m1}]);
    $tp = $pdo->prepare("INSERT INTO track_points (owner_type, owner_id, seq, lat, lon, ele, ts) VALUES (?,?,?,?,?,?,?)");
    foreach ($d['p1'] as $p) { $tp->execute(['mission', ${m1}, $p[0], $p[1], $p[2], $p[3], $p[4]]); }
    spur_blob_schreiben($pdo, 'mission', ${m2}, spur_kodieren($d['p2'], 3, 90), 3, 90, 30);
    einsatz_reas_ersetzen($pdo, ${m2}, [['started_at' => '${sql(jetzt + 100)}', 'events' => [['schock', '${sql(jetzt + 160)}']]]]);
    $pdo->prepare("UPDATE missions SET deleted_at = UTC_TIMESTAMP() WHERE id = ?")->execute([${m2}]);
    $pdo->prepare("UPDATE mission_phases SET lat = 47.222222, lon = 10.222222 WHERE mission_id = ? AND phase = 2")->execute([${m3}]);
    $pdo->prepare("UPDATE mission_phases SET lat = 47.333333, lon = 10.333333 WHERE mission_id = ? AND phase = 4")->execute([${m3}]);
    $pdo->prepare("UPDATE missions SET transport_dest = 'Klinik X', pat_blob = 'edk1:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA' WHERE id = ?")->execute([${m4}]);
    spur_blob_schreiben($pdo, 'rest', ${r1}, spur_kodieren(array_slice($d['pr'], 0, 100), 2, 100), 2, 100, 100);
    foreach (array_slice($d['pr'], 100) as $p) { $tp->execute(['rest', ${r1}, $p[0], $p[1], $p[2], $p[3], $p[4]]); }
    echo 'ok';`);
  const merker = () => phpJson(`return db()->query("SELECT id, uhr_gesperrt, edited FROM missions WHERE user_id = ${uid} ORDER BY id")->fetchAll(PDO::FETCH_NUM);`);
  const merkerVor = merker();
  /* Das Erwartete, so wie der Server es VOR dem Umzug liest. */
  const soll = {
    m1: phpJson(`return spur_lesen(db(), 'mission', ${m1});`),
    m2: phpJson(`return spur_lesen(db(), 'mission', ${m2});`),
    r1: phpJson(`return spur_lesen(db(), 'rest', ${r1});`),
  };

  /* ---- A  Zählung vorher ------------------------------------------------- */
  console.log('\nA — Zählung vorher');
  const zVor = phpJson(`return ou_zahl(db(), ${uid});`);
  pruefe(zVor.einsaetze === 4 && zVor.ruhe === 1, 'A1 Konto: 4 Einsätze, 1 Ruhesegment mit Altbestand', JSON.stringify(zVor));
  const zaehl = () => phpJson(`$p = db(); $u = ${uid};
    $q = fn($s) => (int)$p->query($s)->fetchColumn();
    return ['phasen' => $q("SELECT COUNT(*) FROM mission_phases p JOIN missions m ON m.id = p.mission_id WHERE m.user_id = $u AND (p.lat IS NOT NULL OR p.lon IS NOT NULL)"),
            'reas' => $q("SELECT COUNT(*) FROM resus_sessions r JOIN missions m ON m.id = r.mission_id WHERE m.user_id = $u"),
            'ziel' => $q("SELECT COUNT(*) FROM missions WHERE user_id = $u AND (transport_dest IS NOT NULL OR dest_lat IS NOT NULL OR site_ele_m IS NOT NULL)"),
            'zeilen' => $q("SELECT COUNT(*) FROM track_points WHERE (owner_type = 'mission' AND owner_id IN (${m1},${m2},${m3},${m4})) OR (owner_type = 'rest' AND owner_id = ${r1})"),
            'blobs' => $q("SELECT COUNT(*) FROM track_blobs WHERE (owner_type = 'mission' AND owner_id IN (${m1},${m2},${m3},${m4})) OR (owner_type = 'rest' AND owner_id = ${r1})")];`);
  const kVor = zaehl();
  pruefe(JSON.stringify(kVor) === JSON.stringify({ phasen: 5, reas: 3, ziel: 2, zeilen: 350, blobs: 2 }),
         'A2 je Tabelle: 5 Phasenorte, 3 Reas, 2 Ziele, 350 Zeilen, 2 Blobs', JSON.stringify(kVor));
  const status = phpJson(`require_once "server/status_lib.php"; foreach (status_karten() as $k) { foreach ($k['zeilen'] as $z) {
    if ($z['text'] === 'Ortsdaten') { return $z; } } } return null;`);
  pruefe(status && status.ton === 'orange' && /Konto|Konten/.test(status.klein), 'A3 Statusseite: Zeile „Ortsdaten" orange',
         status ? status.klein.slice(0, 60) : 'fehlt');

  /* ---- B  Der stille Lauf ------------------------------------------------ */
  console.log('\nB — der stille Lauf der Startseite');
  sperre = false;
  await s.goto(`${BASIS}/index.php`, { waitUntil: 'load' });
  let zNach = zVor;
  for (let i = 0; i < 120 && zNach.einsaetze + zNach.ruhe > 1; i++) {
    await s.waitForTimeout(500);
    zNach = phpJson(`return ou_zahl(db(), ${uid});`);
  }
  pruefe(zNach.einsaetze === 1 && zNach.ruhe === 0, 'B1 danach nur noch M4 (Blob geht nicht auf)', JSON.stringify(zNach));
  const kNach = zaehl();
  pruefe(JSON.stringify(kNach) === JSON.stringify({ phasen: 0, reas: 0, ziel: 1, zeilen: 0, blobs: 0 }),
         'B2 je Tabelle: 0 · 0 · 1 (M4) · 0 · 0', JSON.stringify(kNach));
  pruefe(JSON.stringify(merker()) === JSON.stringify(merkerVor), 'B3 uhr_gesperrt und edited unberührt', JSON.stringify(merker().map(x => x.slice(1).join(''))));

  /* ---- C  Geöffnet = vorher ---------------------------------------------- */
  console.log('\nC — geöffnet wie vorher');
  const roheZeile = (art, id) => phpJson(`$t = $art = '${art}'; $tab = $art === 'mission' ? 'missions' : 'rest_segments';
    $r = db()->query("SELECT client_ref, " . ($art === 'mission' ? "ort_huelle, pat_blob" : "NULL ort_huelle, NULL pat_blob") . " FROM $tab WHERE id = ${id}")->fetch(PDO::FETCH_ASSOC);
    $st = db()->query("SELECT seq_from, punkte, n_original, ts_von, ts_bis, stufe, huelle FROM track_stuecke WHERE owner_type = '${art}' AND owner_id = ${id} ORDER BY seq_from")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($st as &$x) { foreach (['seq_from','punkte','n_original','ts_von','ts_bis','stufe'] as $k) { $x[$k] = (int)$x[$k]; } $x['huelle'] = base64_encode($x['huelle']); }
    return ['client_ref' => $r['client_ref'], 'ort_huelle' => $r['ort_huelle'] === null ? null : base64_encode($r['ort_huelle']),
            'pat_blob' => $r['pat_blob'], 'stuecke' => $st];`);
  const offenIm = async (z, artH) => s.evaluate(async ([z, artH]) => {
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    const k = await EdOrtsdaten.schluessel(ck);
    return { punkte: await EdOrtsdaten.spur(z.stuecke, artH, z.client_ref, [], k),
             innen: await EdOrtsdaten.einsatz(z, k),
             pat: z.pat_blob ? JSON.parse(await EdCrypto.decrypt(ck, z.pat_blob)) : null };
  }, [z, artH]);
  const r7 = x => Math.round(x * 1e7);
  const gleich = (ist, soll, mitSeq) => ist.length === soll.length && ist.every((p, i) =>
    (!mitSeq || p.seq === soll[i][0]) && r7(p.lat) === r7(soll[i][1]) && r7(p.lon) === r7(soll[i][2]) && p.ts === soll[i][4]
    && (soll[i][3] === null ? p.ele === null : p.ele === Math.sign(soll[i][3]) * Math.round(Math.abs(soll[i][3]))));
  const o1 = await offenIm(roheZeile('mission', m1), 'spur');
  pruefe(gleich(o1.punkte, soll.m1, true), 'C1 M1: 300 Spurpunkte gleich (Nummer, 1e-7°, ganze Meter, Sekunde)', `${o1.punkte.length} Punkte`);
  const ph1 = (o1.innen && o1.innen.phasen) || [];
  pruefe(ph1.length === 4 && ph1[0].lat === 47.7261 && ph1[1].t === 900 && ph1[2].lon === 10.3202 && ph1[3].lat === null,
         'C2 M1: vier Phasen, Orte und Zeiten wie vorher', JSON.stringify(ph1.map(p => [p.phase, p.t, p.lat])));
  const rea1 = (o1.innen && o1.innen.rea) || [];
  pruefe(rea1.length === 2 && rea1[0].t === 1300 && rea1[0].ereignisse.length === 2 && rea1[0].ereignisse[1].art === 'rhythmuskontrolle'
         && rea1[0].ereignisse[1].t === 1420 && rea1[1].ereignisse.length === 0, 'C3 M1: zwei Reanimationen mit Ereignissen', JSON.stringify(rea1.map(r => [r.t, r.ereignisse.length])));
  pruefe(o1.pat && o1.pat.transport_dest && o1.pat.transport_dest.addr === 'Klinikum Kempten' && o1.pat.transport_dest.lat === 47.722131
         && o1.pat.transport_dest.lon === 10.334512 && o1.pat.ortshoehe === 701, 'C4 M1: Transportziel, Koordinate und Höhe im Blob',
         JSON.stringify(o1.pat && o1.pat.transport_dest));
  const o3 = await offenIm(roheZeile('mission', m3), 'spur');
  const ph3 = (o3.innen && o3.innen.phasen) || [];
  pruefe(ph3.length === 2 && ph3[0].lat === 47.111111 && ph3[1].lat === 47.333333,
         'C5 M3: zusammengeführt — Hülle gewinnt (A), C kommt hinzu', JSON.stringify(ph3.map(p => [p.phase, p.lat])));
  const or = await offenIm(roheZeile('rest', r1), 'ruhe');
  pruefe(gleich(or.punkte, soll.r1, true) && or.punkte.length === 150, 'C6 R1: Blob Stufe 2 und 50 Zeilen → 150 Punkte gleich', `${or.punkte.length} Punkte`);

  /* ---- D  Ausgedünnt bleibt ausgedünnt ----------------------------------- */
  console.log('\nD — ausgedünnte Spur im Papierkorb');
  const z2 = roheZeile('mission', m2);
  pruefe(z2.stuecke.length === 1 && z2.stuecke[0].stufe === 3 && z2.stuecke[0].punkte === 30 && z2.stuecke[0].n_original === 90,
         'D1 M2: ein Stück der Stufe 3, 30 Punkte, Marke 90', JSON.stringify(z2.stuecke.map(x => [x.stufe, x.punkte, x.n_original])));
  const o2 = await offenIm(z2, 'spur');
  pruefe(gleich(o2.punkte, soll.m2, false), 'D2 M2: Punkte gleich', `${o2.punkte.length} Punkte`);
  pruefe(o2.innen && o2.innen.rea.length === 1 && o2.innen.rea[0].ereignisse[0].art === 'schock', 'D3 M2: Reanimation in der Hülle',
         JSON.stringify(o2.innen && o2.innen.rea));

  /* ---- E  Der Wächter ---------------------------------------------------- */
  console.log('\nE — der Wächter');
  const e1 = await s.evaluate(async (m4) => {
    const r = await EdApi.postJson('api/ortsumzug.php', { aktion: 'tauschen', art: 'mission', id: m4,
      spur_stand: '0'.repeat(64), stuecke: [] });
    return [r.status, r.daten && r.daten.error];
  }, m4);
  pruefe(e1[0] === 409, 'E1 falscher Stand → 409, nichts geändert', JSON.stringify(e1));
  const e2 = await s.evaluate(async (m1) => {
    const r = await EdApi.postJson('api/ortsumzug.php', { aktion: 'tauschen', art: 'mission', id: m1,
      spur_stand: 'f'.repeat(64), stuecke: [{ seq_from: 0, punkte: 1, ts_von: 1, ts_bis: 1, huelle: 'eA==' }] });
    return r.status;
  }, m1);
  pruefe(e2 === 400, 'E2 Stück ohne gültige Hülle → 400 (Prüfschicht)', String(e2));

  /* ---- F  Zweiter Lauf --------------------------------------------------- */
  console.log('\nF — ein zweiter Lauf');
  const vorF = JSON.stringify([roheZeile('mission', m1), roheZeile('rest', r1)]);
  await s.evaluate(() => sessionStorage.clear());
  await s.goto(`${BASIS}/index.php`, { waitUntil: 'load' });
  await s.waitForTimeout(6000);
  pruefe(JSON.stringify([roheZeile('mission', m1), roheZeile('rest', r1)]) === vorF, 'F1 nichts geändert');
  pruefe(JSON.stringify(phpJson(`return ou_zahl(db(), ${uid});`)) === JSON.stringify({ einsaetze: 1, ruhe: 0 }), 'F2 M4 bleibt gezählt');

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
