/* Kontopaarprobe — das Schlüsselpaar des Kontos im Browser (S11-03; Konzept
 * S11 3.1, E-S11-14).
 *
 * Anlass: Nr. 43 — Uhr und Handy verschließen Ortsdaten für das Konto; der
 * Schlüssel dafür muss entstehen, zum privaten passen und darf nicht
 * unbemerkt tauschbar sein.
 *
 * WAS SIE FÄHRT, gegen die örtliche Anlage:
 *   1. ein Wegwerfkonto über den Anlegeweg des Kreislaufs (`pruefkonto.py`)
 *   2. anmelden — das Paar entsteht still (`KP_STAND` fehlt → da); in der
 *      Datenbank: das Siegel (`edsk1:`), der private Teil `edk1:`, ein
 *      Eintrag `kontopaar_angelegt`
 *   3. eine Seite, die den Schlüssel braucht: Abgleich passt, keine Meldung;
 *      eine Gerätehülle mit dem ausgelieferten öffentlichen Teil
 *      verschlossen geht mit dem privaten auf (Rundlauf über die Anwendung)
 *   4. ein vertauschter privater Teil (ein zweites Paar, unter demselben
 *      Inhaltsschlüssel) → die Seite meldet die Abweichung, der Server
 *      protokolliert `kontopaar_abweichung` und meldet im Reiter System
 *   5. ein Siegel, das nicht aufgeht → `KP_STAND` 'siegel', kein
 *      öffentlicher Teil in der Seite
 *
 * ALLE KRYPTO KOMMT AUS DER ANWENDUNG (`crypto.js`, `kontopaar.js`); die
 * Probe rechnet nichts nach. Nur örtlich, weil sie die Datenbank liest und
 * verändert.
 *
 * Aufruf:   node tools/proben/kontopaar/probe.mjs [--basis URL]
 * Rückgabe: 0 alles erfüllt · 1 mindestens eine Erwartung nicht · 2 nicht gelaufen
 */
import { execFileSync } from 'node:child_process';
import { readFileSync, rmSync, mkdtempSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

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
const ADRESSE  = 'umlauf-kontopaar@example.invalid';
const PASSWORT = 'umlaufpruefung2026-kontopaar';
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
const q = s => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

function konto(befehl, rcDatei) {
  const a = [KONTO, befehl, ADRESSE, '--basis', BASIS, '--admin-email', 'admin@example.invalid',
             '--admin-passwort', 'pruefstandzugang2026'];
  if (befehl === 'anlegen') { a.push('--passwort', PASSWORT, '--rolle', 'user', '--rc-datei', rcDatei); }
  execFileSync('python3', a, { stdio: ['ignore', 'ignore', 'inherit'],
                               env: { ...process.env, PLAYWRIGHT_MODUL: MODUL } });
}

const kontoId = () => Number(php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?");
  $st->execute([${q(ADRESSE)}]); echo (int)$st->fetchColumn();`));
const zeile = id => JSON.parse(php(`echo json_encode(db()->query("SELECT kp_oeffentlich, kp_privat, kp_seit
  FROM users WHERE id = ${id}")->fetch(PDO::FETCH_ASSOC));`));
const protokoll = (id, art) => Number(php(`echo (int)db()->query("SELECT COUNT(*) FROM protokoll_ereignisse
  WHERE art = '${art}' AND (urheber_user_id = ${id} OR betroffen_user_id = ${id})")->fetchColumn();`));
const systemMeldungen = nach => Number(php(`echo (int)db()->query("SELECT COUNT(*) FROM protokoll_ereignisse
  WHERE reiter = 'system' AND id > ${nach} AND text LIKE 'kontopaar:%'")->fetchColumn();`));
const marke = () => Number(php('echo (int)db()->query("SELECT COALESCE(MAX(id), 0) FROM protokoll_ereignisse")->fetchColumn();'));

console.log(`Kontopaarprobe (Browser, chromium) gegen ${BASIS}`);
const ordner = mkdtempSync(join(tmpdir(), 'kontopaar-'));
const browser = await chromium.launch();
try {
  try { konto('loeschen'); } catch (e) { /* gab es nicht */ }
  konto('anlegen', join(ordner, 'rc.json'));
  const id = kontoId();
  pruefe(id > 0, '1  Wegwerfkonto angelegt', `Konto ${id}`);

  const kontext = await browser.newContext({ ignoreHTTPSErrors: true });
  await kontext.route(/tile\.|opentopomap|arcgisonline|basemap\.at/i, r => r.abort());
  const s = await kontext.newPage();
  const fehler = [];
  s.on('pageerror', e => fehler.push(e.message));

  /* 2 — anmelden, das Paar entsteht still */
  const anlage = s.waitForResponse(r => r.url().includes('/api/kontopaar.php'), { timeout: 120000 })
                  .catch(() => null);
  await s.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('#loginform input[name="email"]', ADRESSE);
  await s.fill('#loginform input[name="password"]', PASSWORT);
  await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
                     s.click('#loginform button[type="submit"]')]);
  /* Die Startseite eines Kontos ohne Diensttag braucht den Schlüssel nicht;
   * `nachDerAnmeldung()` löst das Vormerkfach trotzdem auf, wenn ein Paar
   * fehlt. Erst die Antwort, dann neu laden (F-RW-22). */
  const antwort = await anlage;
  pruefe(antwort !== null && antwort.status() === 200, '2  api/kontopaar.php anlegen -> 200',
         antwort ? `HTTP ${antwort.status()}` : 'keine Anfrage');
  let stand = null;
  for (let i = 0; i < 20 && stand !== 'da'; i++) {
    await s.goto(`${BASIS}/suche.php`, { waitUntil: 'networkidle' });
    stand = await s.evaluate(() => typeof KP_STAND === 'undefined' ? null : KP_STAND);
  }
  pruefe(stand === 'da', '2  KP_STAND da nach dem Entsperren', String(stand));
  const z = zeile(id);
  pruefe(String(z.kp_oeffentlich).startsWith('edsk1:') && String(z.kp_privat).startsWith('edk1:')
         && z.kp_seit !== null,
         '2  Datenbank: Siegel edsk1:, privat edk1:, Zeitpunkt',
         `${String(z.kp_oeffentlich).slice(0, 6)} / ${String(z.kp_privat).slice(0, 5)}`);
  pruefe(protokoll(id, 'kontopaar_angelegt') === 1, '2  Protokoll kontopaar_angelegt, einmal');

  /* 3 — Abgleich passt; Rundlauf einer Gerätehülle über die Anwendung */
  const r3 = await s.evaluate(async () => {
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    const pass = await EdKontopaar.abgleichen(ck);
    const priv = await EdKontopaar.privat(ck);
    const pub = EdKontopaar.oeffentlich();
    const klar = crypto.getRandomValues(new Uint8Array(500));
    const h = await EdCrypto.geraetehuelleVerschliessen(klar, pub, 'spur', 'w-probe', '0:35');
    const auf = await EdCrypto.geraetehuelleOeffnen(h, priv, pub, 'spur', 'w-probe', '0:35');
    let fremd = null;
    try { await EdCrypto.geraetehuelleOeffnen(h, priv, pub, 'spur', 'w-anders', '0:35'); }
    catch (e) { fremd = e.grund; }
    return { pass, gleich: auf.length === klar.length && auf.every((b, i) => b === klar[i]),
             fremd, meldungen: document.querySelectorAll('[data-kontopaar] .meldung-fehler').length,
             texte: [...document.querySelectorAll('[data-kontopaar] .meldung-fehler')].map(e => e.textContent.trim().slice(0, 50)),
             extrahierbar: priv.extractable };
  });
  pruefe(r3.pass !== false && r3.meldungen === 0, '3  Abgleich passt, keine Meldung',
         `abgleichen ${r3.pass}, ${r3.meldungen} Meldungen ${JSON.stringify(r3.texte)}`);
  pruefe(r3.gleich, '3  Gerätehülle mit dem Kontoschlüssel: verschlossen und geöffnet');
  pruefe(r3.fremd === 'pruefwert', '3  dieselbe Hülle an einem anderen Einsatz: abgewiesen', String(r3.fremd));
  pruefe(r3.extrahierbar === false, '3  der private Schlüssel ist nicht extrahierbar');
  pruefe(protokoll(id, 'kontopaar_abweichung') === 0, '3  kein Eintrag kontopaar_abweichung');

  /* 4 — ein vertauschter privater Teil */
  const fremdPrivat = await s.evaluate(async () => {
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    return (await EdKontopaar.paarErzeugen(ck)).privat;
  });
  php(`db()->prepare("UPDATE users SET kp_privat = ? WHERE id = ?")->execute([${q(fremdPrivat)}, ${id}]);`);
  /* Der Topf `kontopaar` zählt je Adresse, fünf in der Stunde: Wer die Probe
   * öfter fährt, bekäme hier 429 statt der Meldung. Die Probe räumt ihn
   * deshalb vorher — auf der Prüfinstallation stehen darin nur ihre Läufe. */
  php(`db()->exec("DELETE FROM rate_limits WHERE topf = 'kontopaar'");`);
  const vor = marke();
  const abw = s.waitForResponse(r => r.url().includes('/api/kontopaar.php'), { timeout: 60000 }).catch(() => null);
  await s.goto(`${BASIS}/suche.php`, { waitUntil: 'networkidle' });
  await s.evaluate(async () => { await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER); });
  const a4 = await abw;
  const text = await s.locator('[data-kontopaar] .meldung-fehler').first().textContent({ timeout: 10000 }).catch(() => '');
  pruefe(/passt nicht/.test(text || ''), '4  vertauschter privater Teil: die Seite meldet es',
         (text || '—').trim().slice(0, 60));
  pruefe(a4 !== null && a4.status() === 200 && protokoll(id, 'kontopaar_abweichung') === 1
         && systemMeldungen(vor) >= 1,
         '4  Server: Protokoll kontopaar_abweichung und Reiter System',
         `HTTP ${a4 ? a4.status() : '—'}, ${systemMeldungen(vor)} Meldung(en)`);

  /* 5 — ein Siegel, das nicht aufgeht */
  php(`db()->prepare("UPDATE users SET kp_oeffentlich = 'edsk1:AAAA' WHERE id = ?")->execute([${id}]);`);
  await s.goto(`${BASIS}/suche.php`, { waitUntil: 'domcontentloaded' });
  const r5 = await s.evaluate(() => [typeof KP_STAND === 'undefined' ? null : KP_STAND,
                                     typeof KP_OEFFENTLICH === 'undefined' ? 'fehlt' : KP_OEFFENTLICH]);
  pruefe(r5[0] === 'siegel' && r5[1] === null, '5  Siegel geht nicht auf: KP_STAND siegel, kein Schlüssel',
         JSON.stringify(r5));

  pruefe(fehler.length === 0, '   keine Skriptfehler in der Seite', fehler.join(' | ').slice(0, 80));
  php(`db()->prepare("DELETE FROM protokoll_ereignisse WHERE reiter = 'system' AND id > ? AND text LIKE 'kontopaar:%'")->execute([${vor}]);`);
  await kontext.close();
} catch (e) {
  console.log(`\nNICHT GELAUFEN: ${e && e.message ? e.message : e}`);
  await browser.close();
  process.exit(2);
} finally {
  try { konto('loeschen'); } catch (e) { console.log(`  Konto ${ADRESSE} nicht gelöscht: ${e.message}`); }
  rmSync(ordner, { recursive: true, force: true });
}
await browser.close();
console.log(`\n${gesamt - offen} ok, ${offen} fehlen`);
process.exit(offen === 0 ? 0 : 1);
