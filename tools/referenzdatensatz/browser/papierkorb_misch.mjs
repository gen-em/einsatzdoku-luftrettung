/* Der Mischfall im Papierkorb — Browserprüfung zu E-S1-04 (Fund F-S1-E).
 *
 * DIE FRAGE. Ein Diensttag, an dem ein Einsatz EINZELN und ein anderer MIT
 * DEM TAG gelöscht wurde: Kommt diese Unterscheidung durch einen vollen
 * Umlauf, und sieht man sie auf der Papierkorbseite?
 *
 *   Umlaufkonto (voller Bestand)
 *     → einen Einsatz einzeln löschen, dann seinen ganzen Diensttag löschen
 *     → sichern
 *     → in ein zweites, leeres Konto einspielen
 *     → Papierkorbseite lesen, Diensttag wiederherstellen, wieder lesen
 *
 * WARUM ES DIESE PRÜFUNG BRAUCHT. Der Referenzbestand hat den Fall nicht: Sein
 * einzeln gelöschter Einsatz hängt an einem AKTIVEN Tag (nachgezählt: vier
 * mitgelöschte an Tag 3, einer einzeln an Tag 9, der aktiv ist). Der
 * Kreislauftest konnte den Unterschied deshalb gar nicht sehen — und ein
 * Fehler, der aus `deleted_with_day` der Datei fälschlich eine 1 machte,
 * lief durch alle Prüfungen. `tools/wiederherstellungs-probe/` misst
 * denselben Fall in der Datenbank; hier geht es um den Weg durch den Browser
 * und um die Anzeige.
 *
 * SIE VERÄNDERT ZWEI KONTEN und legt eines an. Beide müssen mit `umlauf-`
 * beginnen — sonst bricht das Skript ab. Der Riegel ist derselbe wie in
 * `kreislauf.py`: Die erste Fassung eines solchen Skripts hat einmal das
 * Referenzkonto erwischt.
 *
 * Aufruf (das Zielkonto muss bestehen und ein Passwort haben — beides macht
 * `python3 vergleich/kreislauf.py --art edbak --frisch` als Nebenwirkung):
 *
 *   node papierkorb_misch.mjs [basis] [quellkonto] [zielkonto]
 */
import { mkdirSync, writeFileSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const quelle  = process.argv[3] || 'umlauf-edbak@gen-em.org';
const ziel    = process.argv[4] || 'umlauf-misch@gen-em.org';
const kontoPw = process.env.UMLAUF_PASSWORT || 'umlaufpruefung2026';
const zielPw  = process.env.ZIEL_PASSWORT || kontoPw;
const bpw     = process.env.BACKUP_PASSWORT || 'nadokudemo0815';
const ordner  = process.env.AUSGABE || '/tmp/papierkorb-misch';

for (const k of [quelle, ziel]) {
  if (!k.startsWith('umlauf-')) {
    console.error(`ABBRUCH. Dieses Skript löscht Einsätze und ganze Diensttage.\n`
      + `Es arbeitet ausschliesslich auf Konten, deren Adresse mit 'umlauf-'\n`
      + `beginnt. Angefragt war '${k}'.`);
    process.exit(2);
  }
}
mkdirSync(ordner, { recursive: true });

const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, acceptDownloads: true });
const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const befunde = [];
let geprueft = 0;
const pruefe = (ok, t) => { geprueft++; if (!ok) befunde.push(t); };
let nr = 0;
const schritt = (was) => console.log(`  ${++nr}. ${was}`);
const abbruch = async (grund) => {
  console.error('ABBRUCH. ' + grund);
  await browser.close();
  process.exit(2);
};

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

async function rueckfragen(hoechstens = 4) {
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 8000 }); } catch { break; }
    await ja.first().click(); await seite.waitForTimeout(500);
  }
}

// ---- 1. Im Quellkonto den Mischfall herstellen ---------------------------
if (!await anmelden(quelle, kontoPw)) {
  await abbruch(`Anmeldung als ${quelle} gescheitert. Das Umlaufkonto muss `
    + `bestehen — 'python3 vergleich/kreislauf.py --art edbak --frisch' legt es an.`);
}
schritt(`Als ${quelle} anmelden`);

/* ZUERST DEN GRUNDSTAND ERHEBEN, nicht „leer" annehmen.
 *
 * Das Umlaufkonto trägt den vollen Referenzbestand — samt dessen eigenem
 * Papierkorb (ein gelöschter Diensttag mit vier mitgelöschten Einsätzen, dazu
 * ein einzeln gelöschter an einem AKTIVEN Tag). Wer hier „erwartet 1" schreibt,
 * misst den Referenzbestand mit und bekommt sieben Befunde, die keine sind.
 * Geprüft wird deshalb die VERÄNDERUNG gegenüber dem Grundstand. */
async function papierkorbZaehlen() {
  await seite.goto(`${basis}/papierkorb.php`, { waitUntil: 'domcontentloaded' });
  return {
    tage: await seite.locator('h2:text("Diensttage") + table tbody tr').count().catch(() => 0),
    einsaetze: await seite.locator('h2:text("Einsätze") + table tbody tr').count().catch(() => 0),
  };
}
const grund = await papierkorbZaehlen();
schritt(`Grundstand des Papierkorbs im Quellkonto: `
        + `${grund.tage} Diensttag(e), ${grund.einsaetze} einzelne(r) Einsatz/Einsätze`);

/* Einen Diensttag mit mindestens zwei Einsätzen suchen. Über die API, nicht
 * über die Tabelle: Die Einsatzzeilen tragen keine Anker, und die Kennungen
 * braucht das Skript sowieso. */
const tage = await seite.evaluate(async () => {
  const r = await fetch('api/day.php', { credentials: 'same-origin' });
  return r.ok ? await r.json() : null;
});
if (!tage) { await abbruch('api/day.php nicht lesbar.'); }
const liste = Array.isArray(tage) ? tage : (tage.days || tage.items || []);
let tagId = null, einsaetze = [], tagDatum = null;
for (const t of liste) {
  const id = t.id ?? t.day_id;
  if (!id) { continue; }
  const d = await seite.evaluate(async (i) => {
    const r = await fetch('api/day.php?d=' + i, { credentials: 'same-origin' });
    return r.ok ? await r.json() : null;
  }, id);
  const ms = (d && (d.missions || d.einsaetze)) || [];
  if (ms.length >= 2) {
    tagId = id; einsaetze = ms.map(m => m.id);
    tagDatum = (d.day && (d.day.day || d.day.datum)) || d.datum || t.day || null;
    break;
  }
}
if (!tagId) { await abbruch('Kein Diensttag mit mindestens zwei Einsätzen gefunden.'); }
const einzeln = einsaetze[0];
const mitTag  = einsaetze.slice(1);
schritt(`Diensttag ${tagId} mit ${einsaetze.length} Einsätzen gewählt `
        + `(einzeln: ${einzeln}, mit dem Tag: ${mitTag.join(', ')})`);

// Einsatz einzeln löschen — über die reguläre Zwischenseite.
await seite.goto(`${basis}/einsatz_loeschen.php?id=${einzeln}`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(400);
await seite.locator('form button.btn-red, form button[type="submit"]').first().click();
await rueckfragen();
await seite.waitForTimeout(800);
schritt(`Einsatz ${einzeln} einzeln gelöscht`);

// Danach den ganzen Diensttag.
await seite.goto(`${basis}/diensttag_loeschen.php?d=${tagId}`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(400);
await seite.locator('form button.btn-red, form button[type="submit"]').first().click();
await rueckfragen();
await seite.waitForTimeout(800);
schritt(`Diensttag ${tagId} gelöscht`);

/* Gegenprobe im QUELLKONTO: Genau so soll es die Anwendung selbst anlegen —
 * der einzeln gelöschte Einsatz steht in der Einsatzliste des Papierkorbs,
 * die mitgelöschten nicht. Steht er hier schon nicht, ist der Fehler nicht
 * im Rückweg, sondern im Löschweg, und alles Weitere wäre irreführend. */
const nachher = await papierkorbZaehlen();
pruefe(nachher.einsaetze === grund.einsaetze + 1,
  `Quellkonto: einzeln gelöschte Einsätze ${grund.einsaetze} → ${nachher.einsaetze}, `
  + `erwartet +1`);
pruefe(nachher.tage === grund.tage + 1,
  `Quellkonto: Diensttage im Papierkorb ${grund.tage} → ${nachher.tage}, erwartet +1`);
schritt(`Papierkorb im Quellkonto: ${nachher.tage} Diensttag(e), `
        + `${nachher.einsaetze} einzelne(r) Einsatz/Einsätze`);

// ---- 2. Sichern ----------------------------------------------------------
await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.fill('#bpw1', bpw);
await seite.fill('#bpw2', bpw);
const warten = seite.waitForEvent('download', { timeout: 900000 });
await seite.click('#expbtn');
await rueckfragen();
const dl = await warten;
const datei = `${ordner}/${dl.suggestedFilename()}`;
await dl.saveAs(datei);
schritt(`Gesichert → ${dl.suggestedFilename()}`);

// ---- 3. In das leere Zielkonto einspielen --------------------------------
if (!await anmelden(ziel, zielPw)) {
  await abbruch(`Anmeldung als ${ziel} gescheitert. Das Konto muss bestehen `
    + `und ein Passwort haben.`);
}
schritt(`Als ${ziel} anmelden`);

await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.setInputFiles('#bfile', datei);
await seite.fill('#ipw', bpw);
await seite.click('#impbtn');
await rueckfragen();
let impZustand = '';
for (let i = 0; i < 100; i++) {
  await seite.waitForTimeout(3000);
  impZustand = (await seite.locator('#impstate').textContent().catch(() => '') || '').trim();
  if (/fertig|eingespielt|fehlgeschlagen|Fehler|falsch/i.test(impZustand)) { break; }
}
schritt(`Eingespielt — ${impZustand}`);
pruefe(!/fehlgeschlagen|falsch|Fehler/i.test(impZustand),
       `Einspielen gescheitert: ${impZustand}`);
pruefe(/In den Papierkorb übernommen/.test(impZustand),
       'Die Rückmeldung nennt den Papierkorbanteil nicht');

// ---- 4. Die eigentliche Frage: was steht im Papierkorb? ------------------
/* Verglichen wird gegen den Quellstand, nicht gegen null: Das Zielkonto hat
 * den GANZEN Bestand bekommen, also auch den Papierkorb, den das Quellkonto
 * schon vorher hatte. Die Aussage lautet „was drüben lag, liegt hier auch" —
 * und zwar mit derselben Unterscheidung einzeln/mitgelöscht. */
const zielStand = await papierkorbZaehlen();
schritt(`Papierkorb im Zielkonto: ${zielStand.tage} Diensttag(e), `
        + `${zielStand.einsaetze} einzelne(r) Einsatz/Einsätze`);

pruefe(zielStand.tage === nachher.tage,
  `Diensttage im Papierkorb: Quelle ${nachher.tage}, Ziel ${zielStand.tage}`);
pruefe(zielStand.einsaetze === nachher.einsaetze,
  `Einzeln gelöschte Einsätze im Papierkorb: Quelle ${nachher.einsaetze}, `
  + `Ziel ${zielStand.einsaetze}. Ein Ziel-Wert von ${nachher.einsaetze - 1} hiesse: `
  + `der einzeln gelöschte Einsatz trägt fälschlich deleted_with_day = 1 und ist `
  + `unsichtbar — genau der Fehler F-S1-E.`);

/* Der gelöschte Diensttag muss GENAU die mitgelöschten Einsätze nennen, nicht
 * alle. Die Zeile wird über das Datum gesucht — `nth(0)` träfe den Tag, der
 * schon im Referenzbestand im Papierkorb lag. */
const tagZeile = tagDatum
  ? seite.locator('h2:text("Diensttage") + table tbody tr')
         .filter({ hasText: tagDatum.split('-').reverse().join('.') })
  : seite.locator('h2:text("Diensttage") + table tbody tr').last();
const tagTreffer = await tagZeile.count();
pruefe(tagTreffer === 1,
  `Der gelöschte Diensttag (${tagDatum}) ist im Papierkorb ${tagTreffer}× zu finden, erwartet 1×`);
if (tagTreffer === 1) {
  const tagZelle = await tagZeile.locator('td').nth(2).innerText().catch(() => '');
  pruefe(parseInt(tagZelle, 10) === mitTag.length,
    `Der Diensttag im Papierkorb nennt ${tagZelle} Einsätze, erwartet ${mitTag.length} `
    + `(der einzeln gelöschte darf NICHT mitgezählt werden)`);
}

// ---- 4b. Zurückholen des einzelnen wird abgelehnt (Backlog Nr. 33) ------
/* Solange sein Diensttag im Papierkorb liegt, darf „Wiederherstellen" beim
 * einzeln gelöschten Einsatz NICHTS tun: Sonst stünde er aktiv an einem
 * gelöschten Tag — in der Suche sichtbar, in der Tagesübersicht nicht. Bis
 * Web 8.0.0 ging genau das mit einem Klick.
 *
 * Die Zeile wird über das DATUM gesucht, nicht über nth(0): Der
 * Referenzbestand bringt einen zweiten einzeln gelöschten Einsatz mit, und
 * DER hängt an einem aktiven Tag — bei ihm ist das Zurückholen richtig. */
const deTag = tagDatum ? tagDatum.split('-').reverse().join('.') : null;
const meineZeile = deTag
  ? seite.locator('h2:text("Einsätze") + table tbody tr').filter({ hasText: deTag })
  : null;
const meineTreffer = meineZeile ? await meineZeile.count() : 0;
pruefe(meineTreffer === 1,
  `Der einzeln gelöschte Einsatz vom ${deTag} ist ${meineTreffer}× im Papierkorb, erwartet 1×`);
if (meineTreffer === 1) {
  await meineZeile.locator('button.btn-primary').first().click();
  await rueckfragen();
  await seite.waitForTimeout(800);
  const text = await seite.locator('main').innerText();
  pruefe(/Diensttag dieses Einsatzes liegt ebenfalls im Papierkorb/.test(text),
    'Das Zurückholen wurde ohne Begründung abgetan — erwartet wird die Meldung '
    + '„Der Diensttag dieses Einsatzes liegt ebenfalls im Papierkorb."');
  const nachVersuch = await papierkorbZaehlen();
  const bliebLiegen = nachVersuch.einsaetze === zielStand.einsaetze;
  pruefe(bliebLiegen,
    `Nach dem abgelehnten Zurückholen stehen ${nachVersuch.einsaetze} statt `
    + `${zielStand.einsaetze} einzeln gelöschte Einsätze im Papierkorb. Ein Rückgang `
    + `hiesse: Er wurde doch aktiv — an einem gelöschten Diensttag.`);
  schritt(bliebLiegen
    ? 'Zurückholen bei gelöschtem Diensttag wurde abgelehnt und begründet'
    : 'Zurückholen bei gelöschtem Diensttag ging durch — der Einsatz ist jetzt '
      + 'aktiv an einem gelöschten Tag');
}

// ---- 5. Diensttag wiederherstellen — der einzelne bleibt liegen ----------
if (tagTreffer === 1) {
  await tagZeile.locator('button.btn-primary').first().click();
  await rueckfragen();
  await seite.waitForTimeout(1000);
}
const nachRestore = await papierkorbZaehlen();
schritt(`Nach dem Wiederherstellen: ${nachRestore.tage} Diensttag(e), `
        + `${nachRestore.einsaetze} Einsatz/Einsätze`);

pruefe(nachRestore.tage === zielStand.tage - 1,
  `Der wiederhergestellte Diensttag steht noch im Papierkorb `
  + `(${zielStand.tage} → ${nachRestore.tage}, erwartet −1)`);
pruefe(nachRestore.einsaetze === zielStand.einsaetze,
  `Der einzeln gelöschte Einsatz muss im Papierkorb LIEGENBLEIBEN `
  + `(${zielStand.einsaetze} → ${nachRestore.einsaetze}, erwartet unverändert). `
  + `Ein Rückgang hiesse: er ist mit dem Tag wieder aktiv geworden, obwohl ihn `
  + `jemand ausdrücklich gelöscht hatte.`);
/* Und die Gegenprobe zu 4b: Jetzt, wo der Tag wieder aktiv ist, MUSS das
 * Zurückholen gehen. Ohne sie belegte 4b nur, dass der Knopf nichts tut. */
if (meineTreffer === 1) {
  const zeile = seite.locator('h2:text("Einsätze") + table tbody tr').filter({ hasText: deTag });
  if (await zeile.count() === 1) {
    await zeile.locator('button.btn-primary').first().click();
    await rueckfragen();
    await seite.waitForTimeout(800);
  }
  const endstand = await papierkorbZaehlen();
  pruefe(endstand.einsaetze === nachRestore.einsaetze - 1,
    `GEGENPROBE: Nach dem Wiederherstellen des Tages muss sich der Einsatz `
    + `zurückholen lassen (${nachRestore.einsaetze} → ${endstand.einsaetze}, erwartet −1)`);
  schritt(`Nach dem Wiederherstellen des Tages ließ er sich zurückholen: `
          + `${endstand.einsaetze} Einsatz/Einsätze übrig`);
}

const tageZeilen = zielStand.tage, einsatzZeilen = zielStand.einsaetze;
const tageNach = nachRestore.tage, einsatzNach = nachRestore.einsaetze;

const ergebnis = { basis, quelle, ziel, datei, tagId, einzeln, mitTag,
                   einspielen: impZustand,
                   papierkorb: { tage: tageZeilen, einsaetze: einsatzZeilen },
                   nachWiederherstellen: { tage: tageNach, einsaetze: einsatzNach },
                   geprueft, konsolenfehler: konsole, befunde };
writeFileSync(`${ordner}/lauf.json`, JSON.stringify(ergebnis, null, 2) + '\n');

console.log(`\nEinzelprüfungen: ${geprueft}`);
console.log(`Konsolenfehler:  ${konsole.length}`);
if (befunde.length) { console.log(`BEFUNDE (${befunde.length})`); befunde.forEach(b => console.log('  ' + b)); }
else { console.log('Keine Befunde.'); }
await browser.close();
process.exit(befunde.length === 0 ? 0 : 1);
