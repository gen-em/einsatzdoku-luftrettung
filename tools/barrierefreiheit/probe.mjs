/* Barrierefreiheitsprobe — axe-core über die Seiten der örtlichen Anlage.
 * ===========================================================================
 *
 * Misst mit axe-core (vendoriert, `vendor/axe.min.js`) die Regeln WCAG 2.0
 * bis 2.2, Stufen A und AA, dazu „best-practice", an jeder Seite der Liste
 * unten — abgemeldet, als Demo-Konto und als BetreiberIn, bei 1440 und
 * 390 px, hell und (bei 1440 px) dunkel. Grün heißt: kein Verstoß. Was axe
 * nicht entscheiden kann („incomplete", meist Kontrast auf Bild oder
 * Verlauf), steht als Zahl im Protokoll und färbt nichts.
 *
 * Aufruf:  MOTOR=chromium|firefox|webkit node tools/barrierefreiheit/probe.mjs
 *          [--basis https://127.0.0.1:8443] [--nur <teil der Adresse>]
 *
 * Gestartet über `starten()` aus tools/motor.mjs, wie jedes Browserwerkzeug
 * (Grundsatz 8); Eintrag im Register tools/pruefstand/motoren.md.
 */
import { createHash } from 'node:crypto';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const MODUL = process.env.PLAYWRIGHT_MODUL || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);
const { motorWahl, starten, codeSchritt, istOertlich } = await import(
  new URL('../motor.mjs', import.meta.url).href);

const HIER = dirname(fileURLToPath(import.meta.url));
const argv = process.argv.slice(2);
const wert = (n, s) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : s; };
const BASIS = wert('--basis', 'https://127.0.0.1:8443');
const NUR = wert('--nur', '');
const MOTOR = motorWahl(argv);

/* DIE BIBLIOTHEK WIRD NACHGERECHNET, bevor sie misst. Eine stillschweigend
 * ausgetauschte Datei misst etwas anderes, und niemand merkte es. Summe des
 * Originals aus dem npm-Paket axe-core 4.14.0 (Datei axe.min.js), byteweise
 * unverändert; Herkunft in docs/Lizenzen.md 7.0a. */
const AXE_SHA256 = '20c09fe157a8a34a30e241aaa1fcdade657734f08ab379ecfbeb7d45cc46e878';
const AXE = readFileSync(join(HIER, 'vendor', 'axe.min.js'), 'utf8');
const ist = createHash('sha256').update(AXE).digest('hex');
if (ist !== AXE_SHA256) {
  console.error(`ROT  vendor/axe.min.js hat SHA-256 ${ist}, erwartet ${AXE_SHA256}.`);
  process.exit(1);
}

const KONTEN = {
  demo:  ['demo@gen-em.org', 'nadokudemo0815'],
  admin: ['admin@example.invalid', 'pruefstandzugang2026'],
};
/* Die Seiten. TAG, EINSATZ und FORMULAR löst die Probe aus dem Bestand des
 * Demo-Kontos auf; findet sie keinen, ist das rot, nicht übersprungen
 * (Grundsatz 7). */
const SEITEN = [
  { rolle: null,    breiten: [1440, 390], dunkel: true, liste: [
    ['Anmeldung', 'login.php'], ['Registrierung', 'registrieren.php'],
    ['Passwort vergessen', 'reset_request.php'], ['Impressum', 'impressum.php'],
    ['Datenschutz', 'datenschutz.php']] },
  { rolle: 'demo',  breiten: [1440, 390], dunkel: true, liste: [
    ['Startseite', 'index.php'], ['Tagesübersicht', 'TAG'], ['Einsatzansicht', 'EINSATZ'],
    ['Einsatzformular', 'FORMULAR'], ['Diensttag anlegen', 'diensttag_neu.php'],
    ['Suche', 'suche.php'], ['Zeitraum', 'zeitraum.php'], ['Nachbearbeitung', 'nachbearbeitung.php'],
    ['Papierkorb', 'papierkorb.php'], ['Einstellungen', 'einstellungen.php'],
    ['Import', 'import.php'], ['Hilfe', 'hilfe.php'], ['Über', 'ueber.php']] },
  { rolle: 'admin', breiten: [1440], dunkel: true, liste: [
    ['Konten', 'admin_users.php'], ['Installation', 'admin_installation.php'],
    ['Sicherungen', 'admin_sicherungen.php'], ['Protokoll', 'admin_protokoll.php'],
    ['Status', 'betrieb_status.php'], ['Statistik', 'betrieb_statistik.php'],
    ['Server', 'betrieb_server.php'], ['Sicherheit', 'betrieb_sicherheit.php'],
    ['Updates', 'betrieb_updates.php'], ['Jobs', 'betrieb_jobs.php']] },
];

const browser = await starten(PW, MOTOR);

async function kontext(rolle) {
  const k = await browser.newContext({ ignoreHTTPSErrors: istOertlich(BASIS),
    viewport: { width: 1440, height: 900 } });
  /* Wie in der Bedienprobe: Der Wartungslauf bleibt aus (er ändert den
   * Bestand), Kacheln und Adresssuche gehen nicht hinaus. */
  await k.route('**/api/wartungslauf.php', (r) => r.fulfill({ status: 200,
    contentType: 'application/json', body: '{"ok":true,"spuren":[],"aufzeichnungen":0,"diensttage":0}' }));
  await k.route(/tile\.openstreetmap|photon\.komoot/, (r) => r.abort());
  const s = await k.newPage();
  if (rolle) {
    const [mail, pw] = KONTEN[rolle];
    await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
    await s.fill('input[name="email"]', mail);
    await s.fill('input[name="password"]', pw);
    await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
      s.click('#loginform button[type="submit"]')]);
    const zf = await codeSchritt(s);
    if (!zf.ok || s.url().includes('login.php')) {
      throw new Error(`Anmeldung als ${mail} gescheitert — ${zf.meldung || 'zurück auf login.php'}`);
    }
  }
  return s;
}

async function aufloesen(s, adresse) {
  if (!['TAG', 'EINSATZ', 'FORMULAR'].includes(adresse)) { return adresse; }
  await s.goto(`${BASIS}/index.php`, { waitUntil: 'load' });
  const tag = await s.$eval('a[href*="index.php?d="]', (a) => a.getAttribute('href')).catch(() => null);
  if (!tag) { throw new Error('Kein Diensttag im Bestand des Demo-Kontos'); }
  if (adresse === 'TAG') { return tag; }
  await s.goto(`${BASIS}/${tag}`, { waitUntil: 'load' });
  await s.waitForSelector('a[href*="einsatz.php?id="]', { timeout: 20000 }).catch(() => {});
  const e = await s.$eval('a[href*="einsatz.php?id="]', (a) => a.getAttribute('href')).catch(() => null);
  if (!e) { throw new Error('Kein Einsatz am ersten Diensttag des Demo-Kontos'); }
  return adresse === 'EINSATZ' ? e : e.replace('einsatz.php', 'einsatz_form.php');
}

async function messen(s, adresse) {
  await s.goto(`${BASIS}/${adresse.replace(/^\//, '')}`, { waitUntil: 'load' });
  await s.waitForTimeout(1500);   // Skripte füllen Titel, Tabellen, Leseansichten nach
  await s.evaluate(AXE);
  return s.evaluate(async () => {
    const r = await window.axe.run(document, {
      runOnly: { type: 'tag', values: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'] },
      resultTypes: ['violations', 'incomplete'],
    });
    const knapp = (v) => v.map((x) => ({ id: x.id, hilfe: x.help, knoten: x.nodes.length,
      ziele: x.nodes.slice(0, 4).map((n) => n.target.join(' ')) }));
    return { verstoesse: knapp(r.violations), unklar: r.incomplete.reduce((n, x) => n + x.nodes.length, 0) };
  });
}

let aufrufe = 0, unklar = 0;
const rot = [];
const t0 = Date.now();
for (const gruppe of SEITEN) {
  const s = await kontext(gruppe.rolle);
  for (const [name, roh] of gruppe.liste) {
    if (NUR && !roh.includes(NUR) && !name.includes(NUR)) { continue; }
    let adresse;
    try { adresse = await aufloesen(s, roh); }
    catch (e) { rot.push(`${name}: ${e.message}`); continue; }
    const lagen = gruppe.breiten.map((b) => [b, 'hell']);
    if (gruppe.dunkel) { lagen.push([1440, 'dunkel']); }
    for (const [breite, modus] of lagen) {
      await s.setViewportSize({ width: breite, height: 900 });
      await s.emulateMedia({ colorScheme: modus === 'dunkel' ? 'dark' : 'light' });
      const r = await messen(s, adresse);
      aufrufe++; unklar += r.unklar;
      for (const v of r.verstoesse) {
        rot.push(`${name} (${breite} px, ${modus}): ${v.id} — ${v.hilfe} — ${v.knoten} Knoten: `
          + v.ziele.join(' | '));
      }
    }
  }
  await s.context().close();
}
await browser.close();

const dauer = Math.round((Date.now() - t0) / 1000);
for (const z of rot) { console.log('ROT  ' + z); }
console.log(`${MOTOR}: ${aufrufe} Seitenaufrufe mit axe-core 4.14.0 (WCAG 2.0–2.2 A/AA, best-practice), `
  + `${rot.length} Befunde, ${unklar} Knoten unentschieden (nur gezählt), ${dauer} s.`);
if (aufrufe === 0) { console.log('ROT  keine Seite gemessen'); process.exit(1); }
process.exit(rot.length ? 1 : 0);
