/* Abnahme der Demo-Funktion (Arbeitspaket B6).
 *
 * Fährt im Browser genau den Weg, den eine Administratorin ginge — und
 * danach den, den eine Besucherin geht. Kein SQL, keine Abkürzung.
 *
 *   1. Demo-Konto im Adminbereich ANLEGEN
 *   2. Als Demo-Konto anmelden, geschützte Angaben lesen
 *   3. Absichtlich verändern: Einsatz löschen, Stammdatum ändern
 *   4. ZURÜCKSETZEN (Adminbereich) und nachsehen, ob alles wieder da ist
 *   5. Konto-Identität: E-Mail und Passwort müssen abgewiesen werden
 *
 * Aufruf: node demo_pruefen.mjs [basis] [schritte]
 *   schritte: Kommaliste aus anlegen,lesen,veraendern,reset,sperren
 */
import { writeFileSync, mkdirSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const schritte = (process.argv[3] || 'anlegen,lesen,veraendern,reset,sperren').split(',');
const ordner  = process.env.AUSGABE || '/tmp/b6-demo';
const admin   = process.env.ADMIN_EMAIL || 'admin@gen-em.org';
const adminPw = process.env.ADMIN_PASSWORT || 'adminlokal2026';
const demo    = process.env.DEMO_EMAIL || 'demo@gen-em.org';
const demoPw  = process.env.DEMO_PASSWORT || 'nadokudemo0815';
mkdirSync(ordner, { recursive: true });

const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, viewport: { width: 1500, height: 1100 } });
const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const befunde = [];
let n = 0;
const pruefe = (ok, t) => { n++; if (!ok) befunde.push(t); };
const melde = (t) => console.log('  ' + t);
const ergebnis = { basis, schritte, pruefungen: 0, befunde, konsolenfehler: konsole };

async function anmelden(mail, pw) {
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.waitForTimeout(600);
  await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', mail);
  await seite.fill('input[name="password"]', pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('button[type="submit"]'),
  ]);
  return !seite.url().includes('login.php');
}

async function rueckfragen(hoechstens = 3) {
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 5000 }); } catch { break; }
    await ja.first().click(); await seite.waitForTimeout(400);
  }
}

/** Kennzahlen der Adminseite auslesen. */
async function zustand() {
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(700);
  const zeilen = await seite.locator('table.data tr').allInnerTexts().catch(() => []);
  const aus = {};
  for (const z of zeilen) {
    const [k, v] = z.split('\t').map(x => (x || '').trim());
    /* SCHLÜSSEL KLEINGESCHRIEBEN. Die Beschriftungen stehen per CSS in
       Versalien, und innerText liefert den GERENDERTEN Text — „EINSÄTZE",
       nicht „Einsätze". Ein Vergleich auf die Schreibweise im Markup fand
       hier nichts und meldete dreimal „undefined". Dieselbe Falle wie bei
       der Diagnose-Beschriftung in P-07. */
    if (k) { aus[k.toLowerCase()] = v; }
  }
  /* LEER IST EIN ERGEBNIS, KEIN ZUSTAND. Steht keine Tabelle auf der Seite,
     gibt es kein Demo-Konto — oder die Handlung ist gescheitert und die
     Seite trägt eine Fehlermeldung. Ohne diesen Zweig meldete die Prüfung
     fünfmal „undefined" und verschwieg den Grund, der daneben stand. */
  if (Object.keys(aus).length === 0) {
    const t = await seite.locator('body').innerText();
    const meldung = (t.match(/^.*(?:Fehler|fehlgeschlagen|nicht|bereits).*$/mi) || [''])[0];
    aus['__leer'] = meldung.trim().slice(0, 200) || 'kein Demo-Konto, keine Meldung';
  }
  return aus;
}

/* ---- RIEGEL: LAEUFT DAS HIER GEGEN DAS RICHTIGE KONTO? ------------------
 *
 * DIESES SKRIPT IST GEFAEHRLICH, und zwar an einer Stelle, die man ihm nicht
 * ansieht. Schritt 3 loescht einen Einsatz, Schritt 5 versucht die
 * E-Mail-Adresse zu aendern — beides in dem Konto, das unter `demo` erreichbar
 * ist. Beim Demo-Konto ist das folgenlos (der Reset holt alles zurueck, und
 * die Aenderung wird abgewiesen). Bei JEDEM ANDEREN Konto derselben Adresse
 * ist es keins von beidem.
 *
 * Genau das ist auf der Referenzinstallation passiert: Dort traegt das
 * REFERENZKONTO die Adresse demo@gen-em.org, und das Skript hat sie in
 * `gekapert@example.org` geaendert und einen Einsatz geloescht. Der Befund
 * „E-Mail-Aenderung wurde NICHT abgewiesen" stand danach im Bericht — richtig
 * gemeldet, aber zu spaet: Der Schaden war schon angerichtet, und der
 * Referenzstand musste neu aufgebaut werden.
 *
 * Der Riegel prueft VOR allem anderen: Gibt es ein Konto mit dieser Adresse,
 * das NICHT das Demo-Konto ist? Dann bricht der Lauf ab, ohne etwas zu
 * beruehren. Eine Pruefung, die ihren Pruefling zerstoeren kann, braucht eine
 * Grenze, die nicht davon abhaengt, dass die Bedienerin aufpasst. */
{
  pruefe(await anmelden(admin, adminPw), 'Anmeldung als Administration gescheitert');
  /* zustand() statt eigener Textsuche: Es kennt die Falle mit den Versalien
     (die Beschriftungen stehen per CSS in Grossbuchstaben). */
  const alsDemo = ((await zustand())['konto'] || '') === demo;
  await seite.goto(`${basis}/admin_users.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(500);
  const kontoDa = (await seite.locator('body').innerText()).includes(demo);
  if (kontoDa && !alsDemo) {
    console.error(`\nABBRUCH. Auf ${basis} gibt es ein Konto ${demo}, das NICHT als\n`
      + `Demo-Konto gekennzeichnet ist — vermutlich das Referenzkonto.\n`
      + `Dieses Skript würde darin einen Einsatz löschen und die Adresse ändern.\n`
      + `Es wurde nichts angefasst. Für die Demo-Abnahme eine eigene Installation\n`
      + `verwenden (oder das Konto vorher im Adminbereich entfernen).`);
    await browser.close();
    process.exit(2);
  }
}

// ---- 1. Anlegen ---------------------------------------------------------
if (schritte.includes('anlegen')) {
  pruefe(await anmelden(admin, adminPw), 'Anmeldung als Administration gescheitert');
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(700);
  const knopf = seite.locator('button', { hasText: 'Demo-Konto anlegen' });
  pruefe(await knopf.count() > 0, 'Knopf „Demo-Konto anlegen" fehlt');
  if (await knopf.count() > 0) {
    await knopf.first().click();
    await seite.waitForTimeout(1000);
    for (let i = 0; i < 120; i++) {
      if (!/Demo-Konto anlegen/.test(await seite.locator('body').innerText())) break;
      await seite.waitForTimeout(2000);
    }
  }
  const z = await zustand();
  melde(`angelegt: ${JSON.stringify(z)}`);
  ergebnis.nach_anlegen = z;
  pruefe(!z.__leer, `Anlegen ohne Wirkung: ${z.__leer}`);
  pruefe((z['einsätze'] || '') === '82', `Einsätze nach Anlegen: ${z['einsätze']}`);
  pruefe((z['diensttage'] || '') === '15', `Diensttage: ${z['diensttage']}`);
  pruefe((z['ruhesegmente'] || '') === '95', `Ruhesegmente: ${z['ruhesegmente']}`);
  pruefe((z['im papierkorb'] || '') === '5', `Papierkorb: ${z['im papierkorb']}`);
  pruefe((z['geräte'] || '') === '3', `Geräte: ${z['geräte']}`);
  await seite.screenshot({ path: `${ordner}/01-angelegt.png` });
}

// ---- 2. Als Demo anmelden, geschützte Angaben lesen ---------------------
if (schritte.includes('lesen')) {
  pruefe(await anmelden(demo, demoPw), `Anmeldung als ${demo} gescheitert`);
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(3000);
  const text = await seite.locator('body').innerText();
  pruefe(/Demo-Konto\.?\s/i.test(text), 'Kein Demo-Banner auf der Übersicht');
  pruefe(/frei\s*erfunden/i.test(text), 'Banner nennt die Daten nicht als erfunden');
  pruefe(/30\s*Minuten/i.test(text), 'Banner nennt das Reset-Fenster nicht');
  const zeilen = await seite.locator('#missions tbody tr').count();
  pruefe(zeilen > 0, 'Keine Einsatzzeilen im Demo-Konto');
  // Geschützte Angaben: nur lesbar, wenn das Schlüsselmaterial passt
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.locator('#missions tbody tr').first().click(),
  ]);
  await seite.waitForTimeout(2500);
  const t2 = await seite.locator('body').innerText();
  const wert = t2.match(/diagnose\s*🔒\s*\n(.+)/i);
  pruefe(!!(wert && wert[1].trim().length > 3),
         'Diagnose nicht lesbar — Schlüsselmaterial passt nicht zum Chiffretext');
  melde(`Diagnose gelesen: ${wert ? wert[1].trim().slice(0, 60) : '—'}`);
  ergebnis.diagnose = wert ? wert[1].trim() : null;
  await seite.screenshot({ path: `${ordner}/02-demo-einsatz.png` });
}

// ---- 3. Absichtlich verändern ------------------------------------------
if (schritte.includes('veraendern')) {
  pruefe(await anmelden(demo, demoPw), 'Anmeldung als Demo gescheitert');
  // Einen Einsatz löschen
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(2500);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.locator('#missions tbody tr').first().click(),
  ]);
  const id = new URL(seite.url()).searchParams.get('id');
  await seite.goto(`${basis}/einsatz_loeschen.php?id=${id}`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(600);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
    seite.click('button[type="submit"], button.btn-red'),
  ]);
  await seite.waitForTimeout(800);
  melde(`Einsatz ${id} gelöscht`);
  // Ein Stammdatum ändern
  await seite.goto(`${basis}/einstellungen.php?t=standorte`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1200);
  const feld = seite.locator('input[name="name"]').first();
  if (await feld.count() > 0) {
    await feld.fill('VERÄNDERT durch Prüfung');
    await seite.locator('button').filter({ hasText: /Speichern|Anlegen|Hinzufügen/ }).first()
      .click().catch(() => {});
    await seite.waitForTimeout(1000);
    melde('Standort angelegt/geändert');
  }
  ergebnis.veraendert = { geloeschter_einsatz: id };
  await seite.screenshot({ path: `${ordner}/03-veraendert.png` });
}

// ---- 4. Zurücksetzen ----------------------------------------------------
if (schritte.includes('reset')) {
  pruefe(await anmelden(admin, adminPw), 'Anmeldung als Administration gescheitert');
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(700);
  const vorher = await zustand();
  melde(`vor dem Reset: ${JSON.stringify(vorher)}`);
  ergebnis.vor_reset = vorher;
  const knopf = seite.locator('button', { hasText: 'Auf Standard zurücksetzen' });
  pruefe(await knopf.count() > 0, 'Knopf „Auf Standard zurücksetzen" fehlt');
  if (await knopf.count() > 0) {
    await knopf.first().click();
    await rueckfragen();
    for (let i = 0; i < 120; i++) {
      const t = await seite.locator('body').innerText();
      if (/zurückgesetzt|Fehler|konnte nicht/i.test(t)) break;
      await seite.waitForTimeout(2000);
    }
  }
  const nachher = await zustand();
  melde(`nach dem Reset: ${JSON.stringify(nachher)}`);
  ergebnis.nach_reset = nachher;
  pruefe((nachher['einsätze'] || '') === '82', `Einsätze nach Reset: ${nachher['einsätze']}`);
  pruefe((nachher['im papierkorb'] || '') === '5', `Papierkorb nach Reset: ${nachher['im papierkorb']}`);
  pruefe((nachher['geräte'] || '') === '3', `Geräte nach Reset: ${nachher['geräte']}`);
  // Nach dem Reset müssen die geschützten Angaben WEITER lesbar sein
  pruefe(await anmelden(demo, demoPw), 'Anmeldung nach Reset gescheitert');
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(3000);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.locator('#missions tbody tr').first().click(),
  ]);
  await seite.waitForTimeout(2500);
  const t3 = await seite.locator('body').innerText();
  const w3 = t3.match(/diagnose\s*🔒\s*\n(.+)/i);
  pruefe(!!(w3 && w3[1].trim().length > 3),
         'Nach dem Reset sind die geschützten Angaben NICHT lesbar');
  await seite.screenshot({ path: `${ordner}/04-nach-reset.png` });
}

// ---- 5. Konto-Identität gesperrt ---------------------------------------
if (schritte.includes('sperren')) {
  pruefe(await anmelden(demo, demoPw), 'Anmeldung als Demo gescheitert');
  await seite.goto(`${basis}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1200);
  const mailFeld = seite.locator('input[name="email"]').first();
  if (await mailFeld.count() > 0) {
    await mailFeld.fill('gekapert@example.org');
    await seite.locator('form').filter({ has: seite.locator('input[name="email"]') })
      .locator('button').first().click();
    await seite.waitForTimeout(1500);
  }
  const t = await seite.locator('body').innerText();
  pruefe(/lassen sich E-Mail-Adresse und Passwort nicht ändern/i.test(t),
         'E-Mail-Änderung wurde NICHT abgewiesen');
  const st = await seite.evaluate(async () => {
    const r = await fetch('api/kdf_upgrade.php', { method: 'POST',
      headers: { 'Content-Type': 'application/json',
                 'X-CSRF': (window.CSRF || document.querySelector('meta[name=csrf]')?.content || '') },
      body: '{}' });
    return { status: r.status, text: (await r.text()).slice(0, 200) };
  }).catch(e => ({ status: 0, text: String(e) }));
  melde(`kdf_upgrade: ${st.status} ${st.text}`);
  ergebnis.kdf_upgrade = st;
  // Passwort-Reset für die Demo-Adresse
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.goto(`${basis}/reset_request.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', demo);
  await seite.click('button[type="submit"]');
  await seite.waitForTimeout(1500);
  ergebnis.reset_request = (await seite.locator('body').innerText()).slice(0, 200);
  await seite.screenshot({ path: `${ordner}/05-sperren.png` });
}

ergebnis.pruefungen = n;
writeFileSync(`${ordner}/ergebnis.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log(`\nEinzelprüfungen: ${n}`);
console.log(`Konsolenfehler:  ${konsole.length}`);
console.log(befunde.length ? `BEFUNDE (${befunde.length})\n  ` + befunde.join('\n  ')
                           : 'Keine Befunde.');
await browser.close();
process.exit(befunde.length === 0 && konsole.length === 0 ? 0 : 1);
