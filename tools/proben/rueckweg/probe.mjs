/* Rückwegprobe, Browser-Teil — der echte Rückweg gegen die Anlage
 * (Konzept RW, RW-03; E-RW-12).
 *
 * Anlass: Nr. 319 (F-P5c-106) — der Rückweg nach E-P5c-42 prüfte gegen
 * einen Wert, den jeder Datenbankabzug enthält. Dazu Nr. 141 (Zweitfaktor).
 *
 * WAS SIE FÄHRT, zweimal — als NutzerIn und als BetreiberIn:
 *   1. ein Wegwerfkonto über den Anlegeweg des Kreislaufs (`pruefkonto.py`,
 *      E-RP-02); das Passwort setzt `passwort_setzen.mjs` im Browser und
 *      liest dabei den Wiederherstellungsschlüssel von der Seite
 *   2. anmelden — das Paar entsteht still (`RW_STAND` fehlt → da)
 *   3. den Zweitfaktor einschalten, mit dem Geheimnis von der Seite und dem
 *      Code-Rechner der Werkzeuge (E-P5c-43): NutzerIn über die Profilkarte,
 *      BetreiberIn im Einrichtungstor
 *   4. abmelden, anmelden → Code-Schritt → „Gerät und Codes verloren?"
 *   5. ein Tippfehler → das Zeichen steht in der Meldung
 *   6. ein fremder Zettel → „passt nicht zu diesem Konto", 0 Anfragen
 *   7. der richtige Zettel → NutzerIn: Erfolgskarte, Startseite 200, und
 *      die erste Seite, die den Schlüssel braucht (`suche.php`), löst das
 *      Vormerkfach auf und HAT den Datenschlüssel — eine vollständige
 *      Anmeldung, kein Entsperrdialog; Zweitfaktor aus. BetreiberIn: 302 auf
 *      `zweitfaktor.php` mit der Meldung oben
 *
 * WARUM NICHT „VORMERKFACH NACH DER STARTSEITE LEER": Die Startseite eines
 * Kontos ohne Diensttag braucht den Schlüssel nicht und lässt das Fach
 * liegen, wie nach jeder Anmeldung (F-RW-14 löst es nur auf, wenn ein Paar
 * FEHLT). Gemessen wird deshalb an der Seite, die es braucht.
 *
 * ALLE KRYPTO KOMMT AUS DER ANWENDUNG — `crypto.js` und `rueckweg.js` in der
 * Seite. Die Probe tippt, klickt und zählt; sie rechnet nichts nach, sonst
 * prüfte sie sich selbst (Muster Freigabeprobe).
 *
 * GEGEN STAGING (Stufe 2) wie gegen die örtliche Anlage: Sie braucht nur
 * die Zugangsdaten des Prüfkontos. Die Datenbank fragt sie nur örtlich
 * (Spalten, Codes, Protokoll, Mail) — auf Staging steht dafür, was die
 * Seiten sagen (die Profilkarte zeigt „Einrichten" statt „an").
 *
 * Aufruf:
 *   node tools/proben/rueckweg/probe.mjs [--basis URL] [--admin E-MAIL]
 *        [--admin-pw PASSWORT] [--admin-totp GEHEIMNIS]
 *   (Vorgabe: die Sandbox, admin@gen-em.org, ihr Passwort und ihr Geheimnis.
 *   BENANNTE SCHALTER und eine Menge `BEKANNT`, damit `tools/kettenaufrufe/`
 *   den Aufruf in Stufe 2 gegen die Schnittstelle halten kann.)
 *
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht,
 * 2 = nicht gelaufen.
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, rmSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { naechsterCode } from '../../zweitfaktor/totp.mjs';

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = join(HIER, '..', '..', '..');
const ARG = process.argv.slice(2);
const BEKANNT = new Set(['--basis', '--admin', '--admin-pw', '--admin-totp']);
for (let i = 0; i < ARG.length; i += 2) {
  if (!BEKANNT.has(ARG[i])) { console.error(`Unbekannter Schalter: ${ARG[i]}`); process.exit(2); }
}
const wert = (name, vorgabe) => { const i = ARG.indexOf(name); return i >= 0 ? ARG[i + 1] : vorgabe; };
const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const ADMIN  = [wert('--admin', 'admin@gen-em.org'), wert('--admin-pw', 'pruefstandzugang2026'),
                wert('--admin-totp', '')];
const KONTO  = join(WURZEL, 'tools', 'referenzdatensatz', 'vergleich', 'pruefkonto.py');
const PASSWORT = 'umlaufpruefung2026-rueckweg';
const lokal = /^https?:\/\/(127\.0\.0\.1|localhost)(:|\/|$)/.test(BASIS);

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import('file://' + MODUL);

let gesamt = 0, offen = 0;
const pruefe = (ok, was, wert = '') => {
  gesamt++; if (!ok) { offen++; }
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(62)} ${wert}`);
};
/** Nur örtlich: eine Zahl oder ein Wert aus der Datenbank. */
const php = code => execFileSync('php', ['-r', 'require "server/db.php"; ' + code],
                                 { cwd: WURZEL, encoding: 'utf8' }).trim();
const q = s => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

function konto(befehl, adresse, rolle, rcDatei) {
  const a = [KONTO, befehl, adresse, '--basis', BASIS, '--admin-email', ADMIN[0],
             '--admin-passwort', ADMIN[1]];
  if (ADMIN[2]) { a.push('--admin-totp', ADMIN[2]); }
  if (befehl === 'anlegen') { a.push('--passwort', PASSWORT, '--rolle', rolle, '--rc-datei', rcDatei); }
  execFileSync('python3', a, { stdio: ['ignore', 'ignore', 'inherit'], env: { ...process.env,
    PLAYWRIGHT_MODUL: MODUL } });
}

async function anmelden(s, adresse) {
  await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('#loginform input[name="email"]', adresse);
  await s.fill('#loginform input[name="password"]', PASSWORT);
  await Promise.all([
    s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
    s.click('#loginform button[type="submit"]'),
  ]);
}
const seite = s => new URL(s.url()).pathname.split('/').pop();

/** Bis das Paar da ist: `RW_STAND` einer Seite mit Rüstzeug, neu geladen. */
async function paarAbwarten(s) {
  for (let i = 0; i < 20; i++) {
    await s.goto(`${BASIS}/index.php`, { waitUntil: 'networkidle' });
    const st = await s.evaluate(() => typeof RW_STAND === 'undefined' ? null : RW_STAND);
    if (st === 'da') { return st; }
    await s.waitForTimeout(500);
  }
  return s.evaluate(() => typeof RW_STAND === 'undefined' ? null : RW_STAND);
}

/** Das Geheimnis von der Seite, der Code aus dem Rechner der Werkzeuge. */
async function einschalten(s, knopf) {
  const geheimnis = (await s.locator('.codeblock-wert').first().textContent()).replace(/\s+/g, '');
  await s.fill('#f-totp', await naechsterCode(geheimnis));
  await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }), s.click(knopf)]);
  return geheimnis;
}

async function weg(browser, rolle) {
  const adresse = rolle === 'user' ? 'umlauf-rueckweg@gen-em.org' : 'umlauf-rueckweg-b@gen-em.org';
  const ordner = mkdtempSync(join(tmpdir(), 'rueckweg-'));
  const rcDatei = join(ordner, 'rc.json');
  console.log(`\n== ${rolle}: ${adresse}`);
  konto('anlegen', adresse, rolle, rcDatei);
  const rc = JSON.parse(readFileSync(rcDatei, 'utf8')).recovery_code;
  pruefe(/^[A-Z0-9-]{20,}$/.test(rc), 'Wiederherstellungsschlüssel von der Seite gelesen', rc.length + ' Zeichen');

  const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal });
  await kontext.route('**/tile.openstreetmap.org/**', r => r.abort());
  const s = await kontext.newPage();
  const fehler = [];
  s.on('pageerror', e => fehler.push(e.message));
  let mailVorher = 0;
  if (lokal) { mailVorher = Number(php('echo (int)db()->query("SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange")->fetchColumn();')); }
  try {
    /* 2 + 3: anmelden, Paar, Zweitfaktor */
    await anmelden(s, adresse);
    if (rolle === 'user') {
      const st = await paarAbwarten(s);
      pruefe(st === 'da', 'anmelden: das Paar entsteht still', `RW_STAND ${st}`);
      await s.goto(`${BASIS}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
      await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                         s.click('#k-zweitfaktor button[value="zf_beginnen"]')]);
      await einschalten(s, '#k-zweitfaktor button[value="zf_einschalten"]');
    } else {
      pruefe(seite(s) === 'zweitfaktor.php', 'BetreiberIn ohne Zweitfaktor: ins Einrichtungstor', seite(s));
      await einschalten(s, 'form button[type="submit"]');
      await s.check('[data-zf-gesichert]');
      await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }), s.click('[data-zf-weiter]')]);
      const st = await paarAbwarten(s);
      pruefe(st === 'da', 'nach dem Tor: das Paar entsteht still', `RW_STAND ${st}`);
    }
    const codes = await s.goto(`${BASIS}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' })
      .then(() => s.locator('#k-zweitfaktor .zeile', { hasText: 'Wiederherstellungscodes' }).count());
    pruefe(codes === 1, 'Zweitfaktor eingeschaltet', `${codes} Zeile Codes`);

    /* 4: der dritte Weg */
    await s.goto(`${BASIS}/logout.php`, { waitUntil: 'domcontentloaded' });
    await anmelden(s, adresse);
    const verweis = s.locator('a[href="login.php?weg=schluessel"]');
    pruefe(await s.locator('#codeform').count() === 1 && await verweis.count() === 1,
           'Code-Schritt mit dem Verweis „Gerät und Codes verloren?"');
    await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }), verweis.click()]);
    pruefe(await s.locator('#schluesselform').count() === 1, 'Schlüsselschritt');

    /* 5: Tippfehler */
    await s.fill('#f-rs', 'K7QF-2MXD-9TRA-4HWO-6ZNC');
    const tipp = (await s.locator('#rcstate').textContent()) || '';
    pruefe(tipp.includes('„O"'), 'Tippfehler: das Zeichen steht in der Meldung', tipp.slice(0, 40) + '…');

    /* 6: ein fremder Zettel — und keine Anfrage */
    let anfragen = 0;
    const zaehler = r => { if (r.method() === 'POST') { anfragen++; } };
    s.on('request', zaehler);
    const fremd = await s.evaluate(() => EdCrypto.newRecoveryCode());
    await s.fill('#f-rs', fremd);
    await s.click('#schluesselform button[type="submit"]');
    await s.waitForFunction(() => /passt aber nicht/.test(document.getElementById('rcstate').textContent),
                            null, { timeout: 30000 });
    s.off('request', zaehler);
    pruefe(anfragen === 0, 'fremder Zettel: „passt nicht zu diesem Konto", nichts gesendet',
           `${anfragen} Anfragen`);

    /* 7: der richtige Zettel */
    await s.fill('#f-rs', rc);
    const [antwort] = await Promise.all([
      s.waitForResponse(r => r.request().method() === 'POST' && /login\.php/.test(r.url())),
      s.click('#schluesselform button[type="submit"]'),
    ]);
    await s.waitForLoadState('domcontentloaded');
    if (rolle === 'user') {
      const titel = (await s.locator('.anmeldung-schritt').first().textContent().catch(() => '')) || '';
      pruefe(antwort.status() === 200 && titel.includes('Zweitfaktor zurückgesetzt'),
             'richtiger Zettel: Erfolgskarte', `HTTP ${antwort.status()}, „${titel.trim()}"`);
      const [start] = await Promise.all([
        s.waitForResponse(r => /index\.php/.test(r.url())),
        s.click('a.knopf[href="index.php"]'),
      ]);
      await s.goto(`${BASIS}/suche.php`, { waitUntil: 'networkidle' });
      const fach = await s.evaluate(() => ({ vor: sessionStorage.getItem('edkvor'),
                                             dk: !!EdCrypto.getDataKey() }));
      pruefe(start.status() === 200 && fach.vor === null && fach.dk,
             'Startseite 200; die Suche löst das Vormerkfach auf, Datenschlüssel da',
             `HTTP ${start.status()}, Fach ${fach.vor === null ? 'leer' : 'belegt'}, `
             + `Datenschlüssel ${fach.dk ? 'da' : 'fehlt'}`);
      await s.goto(`${BASIS}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
      const aus = await s.locator('#k-zweitfaktor button[value="zf_beginnen"]').count();
      pruefe(aus === 1, 'Profilkarte: Zweitfaktor aus („Einrichten")', `${aus} Knopf`);
    } else {
      const ort = antwort.headers()['location'] || '';
      const meldung = ((await s.locator('.meldung', { hasText: 'Zweitfaktor zurückgesetzt' }).first()
        .textContent().catch(() => '')) || '').replace(/\s+/g, ' ').trim();
      pruefe(antwort.status() === 302 && /zweitfaktor\.php$/.test(ort) && seite(s) === 'zweitfaktor.php'
             && meldung !== '', 'richtiger Zettel: 302 ins Einrichtungstor, Meldung oben',
             `HTTP ${antwort.status()} → ${ort}`);
    }

    /* Örtlich: die Datenbank */
    if (lokal) {
      const id = Number(php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?"); $st->execute([${q(adresse)}]); echo (int)$st->fetchColumn();`));
      const z = JSON.parse(php(`echo json_encode(db()->query("SELECT totp_seit, totp_geheimnis,
        (SELECT COUNT(*) FROM totp_codes WHERE user_id = ${id}) AS codes,
        (SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'totp_zurueckgesetzt' AND betroffen_user_id = ${id}
           AND JSON_UNQUOTE(JSON_EXTRACT(daten, '$.weg')) = 'schluessel') AS protokoll,
        (SELECT COUNT(*) FROM mail_warteschlange WHERE id > ${mailVorher} AND schluessel = 'totp_zurueckgesetzt') AS mails
        FROM users WHERE id = ${id}")->fetch(PDO::FETCH_ASSOC));`));
      /* `totp_geheimnis` nur bei der NutzerIn: Das Einrichtungstor legt für
         die BetreiberIn sofort ein neues an — eine angefangene Einrichtung,
         ohne `totp_seit`. Aus ist der Zweitfaktor, wenn `totp_seit` leer ist. */
      pruefe(z.totp_seit === null && (rolle !== 'user' || z.totp_geheimnis === null)
             && Number(z.codes) === 0 && Number(z.protokoll) === 1 && Number(z.mails) === 1,
             'Datenbank: Zweitfaktor aus, Codes 0, Protokoll +1 (weg=schluessel), Mail +1',
             `${z.codes} Codes, ${z.protokoll} Eintrag, ${z.mails} Mail`);
    }
    pruefe(fehler.length === 0, 'keine Skriptfehler in der Seite', fehler.join(' | ').slice(0, 80));
  } finally {
    await kontext.close();
    try { konto('loeschen', adresse); } catch (e) { console.log(`  Konto ${adresse} nicht gelöscht: ${e.message}`); }
    rmSync(ordner, { recursive: true, force: true });
  }
}

console.log(`Rückwegprobe (Browser) gegen ${BASIS}${lokal ? ' — örtlich, mit Datenbank' : ''}`);
const browser = await chromium.launch();
try {
  await weg(browser, 'user');
  await weg(browser, 'betreiberin');
} catch (e) {
  console.log(`\nNICHT GELAUFEN: ${e && e.message ? e.message : e}`);
  await browser.close();
  process.exit(2);
}
await browser.close();
console.log(`\n${gesamt - offen} ok, ${offen} fehlen`);
process.exit(offen === 0 ? 0 : 1);
