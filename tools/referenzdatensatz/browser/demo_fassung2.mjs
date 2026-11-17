/* Der Demo-Bestand im neuen Format — geht nach einem Reset alles auf?
 *
 * Anlass: Nr. 563 — mit umgelegter Weiche zeigte ein Demo-Bestand der
 * Fassung 1 Gerätedaten ohne Herkunftsnachweis (Konzept KA, KA-13, F-KA-104).
 *
 * WAS ES MISST. Nach einem Demo-Reset und der Anmeldung als Demo-Konto, mit
 * der Weiche AN (nur im Browser dieser Probe, `page.route` auf
 * `inhalt2.js` — das Muster aus `tools/bedienprobe/wege/ka06b3.mjs`):
 *
 *   1. Jeder Einsatz und jedes Ruhesegment des Bestands geht auf — über
 *      dieselben Funktionen wie die Ansichten (`EdOrtsdaten.fuerAnsicht()`,
 *      `.spur()`, `EdStandbuch.patOeffnen()`): kein `absender`, kein
 *      `zurueckgelegt`, kein `ohne-herkunft`, kein Befund. Die Punktzahl je
 *      Spur ist die der Fixture (`sichtbar`: gespeichert ohne gesperrt).
 *   2. Kein Stück und keine Hülle ist Fassung 1; jeder Absender ist das Konto
 *      oder ein Gerät, das im Gerätebuch steht UND auf der Geräteseite.
 *   3. Das Ansehen schreibt kein Blatt: Die 17 Versionen in
 *      `stand_blaetter` sind danach dieselben — auch nach der Geräteseite,
 *      deren Abgleich einen Bucheintrag ohne Gerät austrüge.
 *   4. Der Anhebelauf hat nichts zu tun: kein Block im alten Format, keine
 *      Anfrage mit `POST` an `api/format_anheben.php`.
 *   5. `--zweimal`: Im SELBEN Browser (mit seinem Anker in IndexedDB) einen
 *      Einsatz speichern, zurücksetzen, neu anmelden — wieder geht alles auf.
 *      Das ist die BesucherIn, die nach dem Reset wiederkommt.
 *
 * Dazu, ohne Wertung: welche `POST` das bloße Ansehen auslöst und ob danach
 * die Änderungsmarke des Demo-Kontos steht (Nr. 459).
 *
 * Aufruf: node demo_fassung2.mjs [basis] [--zweimal] [--ohne-reset]
 *              [--wurzel SERVER] [--motor chromium|firefox|webkit]
 *   --wurzel  das `server/`, aus dem die Anlage unter `basis` bedient wird,
 *             wenn es nicht das des Repositoriums ist (`demo_zustand.php`)
 * Rückgabe: 0 ohne Befund · 1 Befunde · 2 nicht gelaufen
 */
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { gunzipSync } from 'node:zlib';
import { anmelden, kontextMachen, motorWahl, starten } from '../../motor.mjs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = (m => m.default ?? m)(await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL));
const MOTOR = motorWahl(process.argv);

const arg = process.argv.slice(2);
const wert = (n) => (arg.includes(n) ? arg[arg.indexOf(n) + 1] : null);
const basis = (arg[0] && !arg[0].startsWith('--') ? arg[0] : 'https://127.0.0.1:8443').replace(/\/$/, '');
const ZWEIMAL = arg.includes('--zweimal');
const OHNE_RESET = arg.includes('--ohne-reset');
const WURZEL = wert('--wurzel');
const demo = process.env.DEMO_EMAIL || 'demo@gen-em.org';
const demoPw = process.env.DEMO_PASSWORT || 'nadokudemo0815';
const HIER = new URL('.', import.meta.url).pathname;
const FIXTURE = process.env.DEMO_FIXTURE
  || (WURZEL ? WURZEL + '/demo/fixture.json.gz'
             : new URL('../../../server/demo/fixture.json.gz', import.meta.url).pathname);

const befunde = [];
const melde = (t) => console.log(t);
const pruefe = (ok, text) => { melde(`  [${ok ? 'ok ' : 'FEHL'}] ${text}`); if (!ok) { befunde.push(text); } return ok; };

function zustand(was) {
  const a = [HIER + 'demo_zustand.php', was].concat(WURZEL ? ['--wurzel', WURZEL] : []);
  return JSON.parse(execFileSync('php', a, { encoding: 'utf8' }));
}

/* ---- Der Sollstand aus der Fixture -------------------------------------- */
let fx;
try { fx = JSON.parse(gunzipSync(readFileSync(FIXTURE)).toString('utf8')); }
catch (e) { console.error(`NICHT GELAUFEN: ${FIXTURE}: ${e.message}`); process.exit(2); }
if (fx.version !== 4) {
  console.error(`NICHT GELAUFEN: Die Fixture hat Format ${fx.version}; diese Probe misst Format 4.`);
  process.exit(2);
}
/* Wie viele Punkte die Anwendung je Spur zeigt, sagt die Fixture im Klartext
   (`sichtbar`, aus `verschliessen.py`): die gespeicherten ohne die gesperrten. */
const sichtbarVon = new Map(fx.spuren.map((s) => [s.client_ref, s.sichtbar]));
const sollPunkte = (e) => sichtbarVon.get(e.client_ref) || 0;
const tagWeg = new Set(fx.daten.days.filter((d) => d.deleted_at != null).map((d) => d.id));
const sichtbar = (e) => e.deleted_at == null && !tagWeg.has(e.day_id);
const SOLL = {
  einsaetze: new Map(fx.daten.missions.filter(sichtbar).map((m) => [m.client_ref, m])),
  ruhe: new Map(fx.daten.rest_segments.filter(sichtbar).map((r) => [r.client_ref, r])),
  geraete: new Set(fx.geraete.map((g) => g.device_id)),
};

/* ---- Browser (die Weiche ist seit Web 26.0.0 zurückgebaut, 06e) --------- */
const browser = await starten(PW, MOTOR);
const kontext = await kontextMachen(browser, { basis, optionen: { viewport: { width: 1400, height: 1000 } } });
const weicheGesehen = 'zurückgebaut (Web 26)';
const seite = await kontext.newPage();
const posts = [];
seite.on('request', (r) => {
  if (r.method() === 'POST' && r.url().startsWith(basis)) {
    let aktion = '';
    try { aktion = (JSON.parse(r.postData() || '{}').aktion) || ''; } catch (e) { aktion = ''; }
    posts.push(new URL(r.url()).pathname.replace(/^\//, '') + (aktion ? ` (${aktion})` : ''));
  }
});
const konsole = [];
seite.on('pageerror', (e) => konsole.push(String(e.message || e)));

/* ---- Alles öffnen, im Browser, mit den Funktionen der Ansichten ---------- */
async function allesOeffnen() {
  const tage = await seite.evaluate(async () => (await (await fetch('api/day.php', { credentials: 'same-origin' })).json()).days);
  const erster = await seite.evaluate(async (ids) => {
    for (const d of ids) {
      const j = await (await fetch('api/day.php?d=' + d, { credentials: 'same-origin' })).json();
      if ((j.missions || []).length) { return j.missions[0].id; }
    }
    return null;
  }, tage.map((t) => t.id));
  if (!erster) { throw new Error('kein Einsatz im Bestand'); }
  /* Die Einsatzansicht lädt das ganze Rüstzeug (EdOrtsdaten, EdStandbuch,
     EdHuelle2, EdUnlock) — von dort aus wird der Bestand geöffnet. */
  await seite.goto(`${basis}/einsatz.php?id=${erster}`, { waitUntil: 'load' });
  await seite.waitForFunction(() => typeof EdOrtsdaten !== 'undefined' && typeof EdStandbuch !== 'undefined'
    && typeof EdUnlock !== 'undefined', null, { timeout: 30000 });
  return seite.evaluate(async (tagIds) => {
    const holen = async (u) => (await fetch(u, { credentials: 'same-origin' })).json();
    const aus = { einsaetze: [], ruhe: [], weiche: true, buch: {}, befundeBuch: [] };
    const fassung = (h) => { try { return atob(h.slice(0, 4)).charCodeAt(0); } catch (e) { return 0; } };
    let ck = null, k = null;
    for (const d of tagIds) {
      const tag = await holen('api/day.php?d=' + d);
      for (const kurz of (tag.missions || [])) {
        const m = await holen('api/mission.php?id=' + kurz.id);
        const z = { ref: m.client_ref, fehler: null, pat: null, absender: null, befund: [], punkte: 0,
                    orte: 0, rea: 0, alt: 0 };
        try {
          if (!ck) {
            ck = await EdUnlock.ensureContentKey(m.pat_wrap, KDF_SALT, KDF_ITER);
            if (!ck) { throw Object.assign(new Error('gesperrt'), { grund: 'gesperrt' }); }
            k = await EdOrtsdaten.schluessel(ck);
            const buch = await EdStandbuch.seitenbuch(ck);
            aus.befundeBuch = buch.befunde || [];
            const g = await buch.geraete();
            for (const sid of Object.keys(g)) { aus.buch[sid] = { geraet: g[sid].geraet, bis: g[sid].bis }; }
          }
          z.alt = (m.spur_stuecke || []).filter((st) => fassung(st.huelle) !== 2).length
            + (m.ort_huelle && fassung(m.ort_huelle) !== 2 ? 1 : 0);
          await EdOrtsdaten.fuerAnsicht(m, ck);
          z.absender = m.ort_absender; z.befund = m.ort_befund || [];
          z.punkte = (m.spur_punkte || []).length;
          z.orte = (m.phases || []).filter((p) => p.lat != null).length;
          z.rea = (m.resus || []).length;
          z.huelle = !!m.ort_huelle;
        } catch (e) { z.fehler = (e && (e.grund || e.message)) || 'unbekannt'; }
        if (m.pat_blob && ck) {
          try { JSON.parse(await EdStandbuch.patOeffnen(ck, m.pat_blob, m.client_ref)); z.pat = m.pat_blob.slice(0, 5); }
          catch (e) { z.pat = 'FEHLER ' + ((e && (e.grund || e.message)) || ''); }
        }
        aus.einsaetze.push(z);
      }
      for (const r of (tag.rest_segments || [])) {
        const z = { ref: r.client_ref, fehler: null, punkte: 0, alt: 0, absender: [] };
        try {
          z.alt = (r.spur_stuecke || []).filter((st) => fassung(st.huelle) !== 2).length;
          const sperren = (r.schnitte || []).map((x) => ({ von: x.von_ts, bis: x.bis_ts }));
          z.punkte = (await EdOrtsdaten.spur(r.spur_stuecke, 'ruhe', r.client_ref, sperren, k)).length;
        } catch (e) { z.fehler = (e && (e.grund || e.message)) || 'unbekannt'; }
        aus.ruhe.push(z);
      }
    }
    /* Wer hat die Stücke verschlossen? Die SID steht im Kopf jeder Hülle. */
    return aus;
  }, tage.map((t) => t.id));
}

function auswerten(r, was) {
  melde(`${was}: ${r.einsaetze.length} Einsätze, ${r.ruhe.length} Ruhesegmente geöffnet (Weiche ${r.weiche ? 'an' : 'AUS'})`);
  pruefe(r.weiche === true, 'die Weiche steht im Browser der Probe an');
  pruefe(r.befundeBuch.length === 0, `Standbuch ohne Befund beim Abgleich${r.befundeBuch.length ? ': ' + JSON.stringify(r.befundeBuch.slice(0, 3)) : ''}`);
  const fe = r.einsaetze.filter((z) => z.fehler), fr = r.ruhe.filter((z) => z.fehler);
  const gruende = (l) => [...new Set(l.map((z) => z.fehler))].join(', ');
  pruefe(fe.length === 0, `${r.einsaetze.length - fe.length} von ${r.einsaetze.length} Einsätzen gehen auf${fe.length ? ' — ' + gruende(fe) + ', z. B. ' + fe[0].ref : ''}`);
  pruefe(fr.length === 0, `${r.ruhe.length - fr.length} von ${r.ruhe.length} Ruhesegmenten gehen auf${fr.length ? ' — ' + gruende(fr) + ', z. B. ' + fr[0].ref : ''}`);
  const fp = r.einsaetze.filter((z) => z.pat && z.pat !== 'edk2:');
  const np = r.einsaetze.filter((z) => z.pat === 'edk2:').length;
  pruefe(fp.length === 0, `${np} Patientenblöcke gehen auf, alle edk2:${fp.length ? ' — ' + fp[0].ref + ': ' + fp[0].pat : ''}`);
  const alt = r.einsaetze.concat(r.ruhe).reduce((s, z) => s + z.alt, 0);
  pruefe(alt === 0, `kein Stück und keine Hülle in Fassung 1 (${alt})`);
  const mitBefund = r.einsaetze.filter((z) => z.befund.length);
  pruefe(mitBefund.length === 0, `kein Befund an einer Einsatzhülle${mitBefund.length ? ' — ' + mitBefund[0].ref + ': ' + mitBefund[0].befund : ''}`);
  /* Bestand und Punktzahl gegen die Fixture. */
  const refs = (l) => new Set(l.map((z) => z.ref));
  const gleich = (a, b) => a.size === b.size && [...a].every((x) => b.has(x));
  pruefe(gleich(refs(r.einsaetze), new Set(SOLL.einsaetze.keys())), `die Einsätze sind die der Fixture (${SOLL.einsaetze.size})`);
  pruefe(gleich(refs(r.ruhe), new Set(SOLL.ruhe.keys())), `die Ruhesegmente sind die der Fixture (${SOLL.ruhe.size})`);
  let punkte = 0, falsch = [];
  for (const z of r.einsaetze.concat(r.ruhe)) {
    const e = SOLL.einsaetze.get(z.ref) || SOLL.ruhe.get(z.ref);
    if (!e || z.fehler) { continue; }
    punkte += z.punkte;
    if (z.punkte !== sollPunkte(e)) { falsch.push(`${z.ref}: ${z.punkte} statt ${sollPunkte(e)}`); }
  }
  pruefe(falsch.length === 0, `${punkte} Spurpunkte, je Spur so viele wie in der Fixture${falsch.length ? ' — ' + falsch.slice(0, 3).join('; ') : ''}`);
  /* Herkunft: das Konto oder ein Gerät des Gerätebuchs, das es auch gibt. */
  const sids = new Set(r.einsaetze.map((z) => z.absender).filter((a) => a && a !== 'konto'));
  const fremd = [...sids].filter((s) => !r.buch[s] || !SOLL.geraete.has(r.buch[s].geraet) || r.buch[s].bis != null);
  const vomGeraet = r.einsaetze.filter((z) => z.absender && z.absender !== 'konto').length;
  const vomKonto = r.einsaetze.filter((z) => z.absender === 'konto').length;
  pruefe(Object.keys(r.buch).length === SOLL.geraete.size && fremd.length === 0,
    `Gerätebuch: ${Object.keys(r.buch).length} Demo-Geräte, keines ausgetragen; Einsatzhüllen ${vomGeraet} vom Gerät, ${vomKonto} vom Konto`);
  return r;
}

let rueck = 0;
try {
  if (!OHNE_RESET) { zustand('reset'); }
  zustand('bremse');
  const vor = zustand('stand');
  pruefe(vor.konto.huelle === 'edk2:' && vor.konto.kp === 'edk2:',
    `nach dem Reset: Passworthülle ${vor.konto.huelle}, Kontopaar ${vor.konto.kp}, ${vor.konto.kdf_iter} Runden`);
  pruefe(vor.bloecke.every((b) => b.art === 'edk2:'), `kein Patientenblock im alten Format (${JSON.stringify(vor.bloecke)})`);
  pruefe(vor.geraete.length === SOLL.geraete.size && vor.geraete.every((g) => g.herkunft === 1 && g.spk === 1),
    `${vor.geraete.length} Geräte mit Geräteschlüssel und Herkunft`);

  const a = await anmelden(seite, { basis, konto: demo, pass: demoPw });
  if (!a.angemeldet) { throw new Error('Anmeldung als Demo-Konto gescheitert: ' + (a.meldung || a.url)); }
  await seite.waitForTimeout(1500);                 // der Anhebelauf läuft still nach dem Entsperren
  auswerten(await allesOeffnen(), 'Nach dem Reset');
  melde(`  Weiche: ${weicheGesehen}`);

  /* Die Geräteseite: Ihr Abgleich trüge einen Bucheintrag ohne Gerät aus. */
  await seite.goto(`${basis}/einstellungen.php?t=geraete`, { waitUntil: 'load' });
  await seite.waitForTimeout(2500);
  const nach = zustand('stand');
  pruefe(JSON.stringify(nach.blaetter) === JSON.stringify(vor.blaetter),
    `das Ansehen schreibt kein Blatt (Versionen ${Object.values(nach.blaetter).join(',')})`);
  const anheben = posts.filter((p) => p.startsWith('api/format_anheben.php'));
  pruefe(anheben.length === 0, `der Anhebelauf sendet nichts (${anheben.length} POST)`);
  pruefe(konsole.length === 0, `keine Ausnahme in der Seite${konsole.length ? ': ' + konsole[0] : ''}`);
  const zaehl = {};
  for (const p of posts) { if (p !== 'login.php') { zaehl[p] = (zaehl[p] || 0) + 1; } }
  melde(`  Hinweis: POST beim bloßen Ansehen: ${JSON.stringify(zaehl)}; Änderungsmarke `
      + `${nach.geaendert ? 'GESETZT — der Reset käme in 30 Minuten (Nr. 459)' : 'nicht gesetzt'}`);

  if (ZWEIMAL) {
    /* Einen Einsatz im Formular speichern (Block und Blatt steigen), dann
       zurücksetzen und im SELBEN Browser wiederkommen. */
    const id = await seite.evaluate(async () => {
      const j = await (await fetch('api/suchindex.php', { credentials: 'same-origin' })).json();
      const m = (j.missions || []).find((x) => x.pat_blob && x.id);
      return m ? m.id : null;
    });
    await seite.goto(`${basis}/einsatz_form.php?id=${id}`, { waitUntil: 'load' });
    await seite.waitForTimeout(2500);
    await Promise.all([
      seite.waitForURL(/einsatz\.php/, { timeout: 60000 }),
      seite.evaluate(() => document.getElementById('missionform').requestSubmit()),
    ]);
    await seite.waitForTimeout(1000);
    const geaendert = zustand('stand');
    const gestiegen = Object.keys(geaendert.blaetter).filter((w) => geaendert.blaetter[w] !== vor.blaetter[w]);
    pruefe(gestiegen.length >= 1, `nach dem Speichern eines Einsatzes ist ein Blatt gestiegen (${gestiegen.join(',') || 'keines'})`);
    zustand('reset');
    const b = await anmelden(seite, { basis, konto: demo, pass: demoPw });
    if (!b.angemeldet) { throw new Error('zweite Anmeldung gescheitert: ' + (b.meldung || b.url)); }
    await seite.waitForTimeout(1500);
    auswerten(await allesOeffnen(), 'Nach Änderung, Reset und Wiederkommen im selben Browser');
    const dann = zustand('stand');
    pruefe(JSON.stringify(dann.blaetter) === JSON.stringify(vor.blaetter),
      `auch danach schreibt das Ansehen kein Blatt (Versionen ${Object.values(dann.blaetter).join(',')})`);
  }
} catch (e) {
  console.error(`NICHT GELAUFEN: ${e.message}`);
  rueck = 2;
}
await browser.close();
if (rueck === 0) {
  melde(befunde.length ? `\n${befunde.length} Befund(e).` : '\nOhne Befund.');
  rueck = befunde.length ? 1 : 0;
}
process.exit(rueck);
