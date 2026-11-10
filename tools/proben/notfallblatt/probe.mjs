/* Notfallblattprobe — verlässt der Wiederherstellungsschlüssel den Tab?
 * Anlass: Nr. 560 — der Druck des Notfallblatts schickte den Schlüssel per POST an den Server (F-KA-01).
 *
 * WAS SIE MISST (KA-02). Drei Wege, auf denen ein Druckblatt entsteht, je in
 * einem frischen Browserkontext, der JEDE Anfrage mitschneidet — die der
 * Seite und die des Blattfensters, Adresse und Rumpf:
 *
 *   1  Erstvergabe   `pw_handling.php`: Passwort setzen, Schlüssel lesen,
 *                    „Notfallblatt drucken", Blatt lesen, abschließen
 *   2  Erneuerung    Einstellungen → Profil → neuer Schlüssel, Blatt drucken
 *   3  Codeblatt     Zweitfaktor einrichten, „Codeblatt drucken"
 *
 * Belegt wird je Weg: Der Wert steht auf dem Blatt (die Übergabe trägt), und
 * KEINE mitgeschnittene Anfrage enthält ihn — in keiner Schreibweise
 * (mit/ohne Bindestrich, klein, URL-kodiert). Dazu: Ein POST an beide Blätter
 * bekommt 400 und gibt nichts wieder; ein Blatt ohne Ursprungsfenster bleibt
 * leer. Die Zahl der mitgeschnittenen Anfragen steht dabei — null Treffer in
 * null Anfragen wäre keine Messung.
 *
 * MIT DEM ALTEN WEG WÄRE SIE ROT: Bis Web 24.10.1 stand der Schlüssel im
 * Rumpf eines POST an `notfallblatt.php`.
 *
 * Aufruf: node tools/proben/notfallblatt/probe.mjs [--basis URL] [--admin E-MAIL]
 *         [--admin-pw-aus VAR] [--admin-totp-aus VAR] [--motor chromium|firefox|webkit]
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht, 2 = nicht gelaufen.
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, rmSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { naechsterCode } from '../../zweitfaktor/totp.mjs';
import { motorWahl, starten } from '../../motor.mjs';

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = join(HIER, '..', '..', '..');
const ARG = process.argv.slice(2);
const BEKANNT = new Set(['--basis', '--admin', '--admin-pw-aus', '--admin-totp-aus', '--motor']);
for (let i = 0; i < ARG.length; i += 2) {
  if (!BEKANNT.has(ARG[i])) { console.error(`Unbekannter Schalter: ${ARG[i]}`); process.exit(2); }
}
const wert = (name, vorgabe) => { const i = ARG.indexOf(name); return i >= 0 ? ARG[i + 1] : vorgabe; };
const ausUmgebung = (schalter, vorgabe) => {
  const name = wert(schalter, '');
  if (!name) { return vorgabe; }
  const w = process.env[name] || '';
  if (!w.trim()) { console.error(`${schalter} ${name}: die Umgebungsvariable ist leer.`); process.exit(2); }
  return w;
};
const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const ADMIN  = [wert('--admin', 'admin@example.invalid'),
                ausUmgebung('--admin-pw-aus', 'pruefstandzugang2026'),
                ausUmgebung('--admin-totp-aus', '')];
const KONTO_WERKZEUG = join(WURZEL, 'tools', 'referenzdatensatz', 'vergleich', 'pruefkonto.py');
const ADRESSE  = 'umlauf-notfallblatt@example.invalid';
const PASSWORT = 'umlaufpruefung2026-notfallblatt';
const lokal = /^https?:\/\/(127\.0\.0\.1|localhost)(:|\/|$)/.test(BASIS);
const MOTOR = motorWahl(process.argv);
const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = (m => m.default ?? m)(await import('file://' + MODUL));

let gesamt = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  gesamt++; if (!ok) { offen++; }
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(66)} ${wert}`);
};

function konto(befehl, extra = []) {
  const a = [KONTO_WERKZEUG, befehl, ADRESSE, '--basis', BASIS, '--admin-email', ADMIN[0],
             '--admin-passwort-aus', 'NOTFALLBLATT_ADMIN_PW', ...extra];
  if (ADMIN[2]) { a.push('--admin-totp-aus', 'NOTFALLBLATT_ADMIN_TOTP'); }
  execFileSync('python3', a, { stdio: ['ignore', 'ignore', 'inherit'], env: { ...process.env,
    PLAYWRIGHT_MODUL: MODUL, NOTFALLBLATT_ADMIN_PW: ADMIN[1], NOTFALLBLATT_ADMIN_TOTP: ADMIN[2] || '' } });
}

/** Ein Kontext, der jede Anfrage aller seiner Fenster mitschneidet. */
async function kontext(browser) {
  const k = await browser.newContext({ ignoreHTTPSErrors: lokal });
  await k.route('**/tile.openstreetmap.org/**', r => r.abort());
  const anfragen = [];
  k.on('request', r => {
    let rumpf = '';
    try { rumpf = r.postData() || ''; } catch (_) { rumpf = ''; }
    anfragen.push({ adresse: r.url(), rumpf });
  });
  return { k, anfragen };
}

/** Wie oft ein Geheimnis in den Anfragen steht, in jeder naheliegenden Schreibweise. */
function treffer(anfragen, geheim) {
  const roh = geheim.replace(/[\s-]+/g, '');
  const formen = new Set([geheim, roh, geheim.toLowerCase(), roh.toLowerCase(),
                          encodeURIComponent(geheim)]);
  let n = 0;
  for (const a of anfragen) {
    const text = a.adresse + '\n' + a.rumpf;
    for (const f of formen) { if (f && text.includes(f)) { n++; break; } }
  }
  return n;
}

async function blattAbwarten(blatt) {
  await blatt.waitForLoadState('networkidle').catch(() => null);
  await blatt.waitForSelector('main [data-blatt-mit]:not([hidden])', { timeout: 20000 })
    .catch(() => null);
}
/** Text eines Elements oder '' — ein fehlendes Element ist leer, kein Abbruch. */
async function text(seite, wahl) {
  const l = seite.locator(wahl);
  return (await l.count()) ? ((await l.first().textContent()) || '').trim() : '';
}

async function anmelden(s) {
  await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('#loginform input[name="email"]', ADRESSE);
  await s.fill('#loginform input[name="password"]', PASSWORT);
  await Promise.all([
    s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
    s.click('#loginform button[type="submit"]'),
  ]);
}

const browser = await starten(PW, MOTOR);
const ordner = mkdtempSync(join(tmpdir(), 'notfallblatt-'));
let rcErst = '';
try {
  /* ---- 1 Erstvergabe --------------------------------------------------- */
  console.log(`\n== 1 Erstvergabe (${MOTOR})`);
  const linkDatei = join(ordner, 'link.txt');
  konto('einladen', ['--link-datei', linkDatei]);
  const link = readFileSync(linkDatei, 'utf8').trim();
  {
    const { k, anfragen } = await kontext(browser);
    const s = await k.newPage();
    await s.goto(link, { waitUntil: 'networkidle' });
    await s.fill('#pw1', PASSWORT);
    await s.fill('#pw2', PASSWORT);
    await s.click('#gobtn');
    await s.waitForSelector('#rcbox:not([hidden])', { timeout: 60000 });
    rcErst = ((await s.locator('#rccode').textContent()) || '').trim();
    pruefe(/^[A-Z2-9]{4}(-[A-Z2-9]{4}){4}$/.test(rcErst), 'Schlüssel von der Seite gelesen', `${rcErst.length} Zeichen`);
    const [blatt] = await Promise.all([k.waitForEvent('page'), s.click('#rcdruck')]);
    await blattAbwarten(blatt);
    /* ZUERST ZÄHLEN: Was bis hier ging, ist der ganze Weg zum Blatt. */
    const n = treffer(anfragen, rcErst);
    pruefe(anfragen.length > 0 && n === 0, 'keine Anfrage bis zum Blatt enthält den Schlüssel',
           `${n} von ${anfragen.length} Anfragen`);
    const gruppen = (await blatt.locator('[data-blatt-gruppe]').allTextContents()).join('-');
    pruefe(gruppen === rcErst, 'das Blatt zeigt den Schlüssel', gruppen ? 'gleich' : 'leer');
    const kontoText = await text(blatt, '[data-blatt-konto]');
    pruefe(kontoText === ADRESSE, 'das Blatt zeigt die Kontoadresse (ohne Sitzung)', kontoText || 'leer');
    pruefe(await blatt.locator('[data-drucken]').isVisible(), 'der Druckknopf steht da');
    await s.check('#rcok');
    await Promise.all([s.waitForNavigation({ waitUntil: 'networkidle', timeout: 60000 }), s.click('#gobtn')]);
    const n2 = treffer(anfragen, rcErst);
    pruefe(n2 === 0, 'auch nach dem Abschluss: keine Anfrage enthält den Schlüssel',
           `${n2} von ${anfragen.length} Anfragen`);
    await k.close();
  }

  /* ---- Die Blätter nehmen nichts an ------------------------------------ */
  console.log('\n== Ohne Fenster, mit POST');
  {
    const { k } = await kontext(browser);
    const r1 = await k.request.post(`${BASIS}/notfallblatt.php`, { form: { code: rcErst } });
    const t1 = await r1.text();
    pruefe(r1.status() === 400 && !t1.includes(rcErst), 'POST an notfallblatt.php: 400, nichts wiedergegeben',
           `HTTP ${r1.status()}`);
    const r2 = await k.request.post(`${BASIS}/codeblatt.php`, { form: { 'codes[]': 'ABCD2345' } });
    const t2 = await r2.text();
    pruefe(r2.status() === 400 && !t2.includes('ABCD'), 'POST an codeblatt.php: 400, nichts wiedergegeben',
           `HTTP ${r2.status()}`);
    const s = await k.newPage();
    await s.goto(`${BASIS}/notfallblatt.php`, { waitUntil: 'networkidle' });
    const ohne = await s.locator('[data-blatt-ohne]').isVisible();
    const mit = await s.locator('main > [data-blatt-mit]').isVisible();
    pruefe(ohne && !mit, 'ohne Ursprungsfenster: „nicht nachträglich drucken", kein Blatt');
    await k.close();
  }

  /* ---- 2 Erneuerung ---------------------------------------------------- */
  console.log('\n== 2 Erneuerung');
  {
    const { k, anfragen } = await kontext(browser);
    const s = await k.newPage();
    await anmelden(s);
    await s.goto(`${BASIS}/einstellungen.php?t=profil`, { waitUntil: 'networkidle' });
    await s.click('[data-schluessel-auf]');
    await s.fill('#rf-pw', PASSWORT);
    await s.click('[data-rf-erzeugen]');
    await s.waitForFunction(() => {
      const el = document.querySelector('[data-rf-code]');
      return el && el.textContent.trim().length >= 20;
    }, null, { timeout: 90000 });
    const rc = ((await s.locator('[data-rf-code]').textContent()) || '').trim();
    pruefe(rc !== '' && rc !== rcErst, 'neuer Schlüssel im Dialog', `${rc.length} Zeichen`);
    const [blatt] = await Promise.all([k.waitForEvent('page'),
                                       s.click('[data-rf="schluessel"] .rf-druck .knopf')]);
    await blattAbwarten(blatt);
    const n = treffer(anfragen, rc);
    pruefe(anfragen.length > 0 && n === 0, 'keine Anfrage der Erneuerung enthält den Schlüssel',
           `${n} von ${anfragen.length} Anfragen`);
    const gruppen = (await blatt.locator('[data-blatt-gruppe]').allTextContents()).join('-');
    pruefe(gruppen === rc, 'das Blatt zeigt den neuen Schlüssel', gruppen ? 'gleich' : 'leer');
    const kontoText = await text(blatt, '[data-blatt-konto]');
    pruefe(kontoText === ADRESSE, 'die Adresse kommt aus der Sitzung', kontoText || 'leer');

    /* ---- 3 Codeblatt --------------------------------------------------- */
    console.log('\n== 3 Codeblatt');
    await s.goto(`${BASIS}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
    await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                       s.click('#k-zweitfaktor button[value="zf_beginnen"]')]);
    const geheimnis = (await s.locator('.codeblock-wert').first().textContent()).replace(/\s+/g, '');
    await s.fill('#f-totp', await naechsterCode(geheimnis));
    await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                       s.click('#k-zweitfaktor button[value="zf_einschalten"]')]);
    const codes = (await s.locator('.codeblock-liste li').allTextContents()).map(c => c.trim());
    pruefe(codes.length === 10, 'zehn Codes auf der Seite', `${codes.length}`);
    const ab = anfragen.length;
    const [blatt2] = await Promise.all([k.waitForEvent('page'),
                                        s.click('.codeblock .rf-druck .knopf')]);
    await blattAbwarten(blatt2);
    const danach = anfragen.slice(ab);
    const nCodes = codes.reduce((n, c) => n + treffer(danach, c), 0);
    pruefe(danach.length > 0 && nCodes === 0, 'keine Anfrage des Codeblatts enthält einen Code',
           `${nCodes} Treffer in ${danach.length} Anfragen`);
    const aufBlatt = (await blatt2.locator('[data-blatt-codes] li').allTextContents()).map(c => c.trim());
    pruefe(aufBlatt.length === 10 && aufBlatt.join() === codes.join(), 'das Codeblatt zeigt dieselben zehn Codes',
           `${aufBlatt.length}`);
    await k.close();
  }
} catch (e) {
  console.error('Die Probe kam nicht durch:', e.message);
  offen++;
} finally {
  try { konto('loeschen'); } catch (_) { console.error('Prüfkonto ließ sich nicht löschen.'); }
  rmSync(ordner, { recursive: true, force: true });
  await browser.close();
}
console.log(`\n${gesamt - Math.min(offen, gesamt)} von ${gesamt} erfüllt (${MOTOR})`);
process.exit(offen === 0 ? 0 : 1);
