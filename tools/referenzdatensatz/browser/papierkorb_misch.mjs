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
 * DAS ZIELKONTO MUSS LEER SEIN. Das ist keine Bequemlichkeit, sondern folgt
 * aus dem, was geprueft wird: Der Vergleich haelt den Papierkorb des Ziels
 * ABSOLUT gegen den der Quelle, und das geht nur auf, wenn das Ziel den ganzen
 * Bestand frisch bekommt. Traegt es ihn schon, ueberspringt die
 * Wiederherstellung alles (sie ERGAENZT und ersetzt nicht, E22) — samt des
 * Papierkorbzustands, um den es hier geht. Die Probe prueft das seit S2/AP10
 * selbst und bricht mit einer Erklaerung ab, statt sechs Scheinbefunde zu
 * melden.
 *
 * DAS QUELLKONTO braucht den Referenzbestand; `python3
 * vergleich/kreislauf.py --art edbak --frisch` legt ihn an. Ein LEERES
 * Zielkonto entsteht ueber denselben Weg wie jedes andere Konto
 * (`konto_anlegen()` in kreislauf.py) — nur ohne anschliessendes Einspielen.
 *
 * Aufruf:
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
/* DER FILTER MUSS AUCH DIE ADRESSE ANSEHEN, nicht nur den Text (P3/O11).
 * Eine gescheiterte Kachel meldet sich als „Failed to load resource:
 * net::ERR_CONNECTION_RESET" — ohne jede URL im Text. Der Ausdruck oben lief
 * also ins Leere, und der Lauf zaehlte 12 Konsolenfehler, die keine waren;
 * die Bildaufnahme prueft seit O3 beides (tools/screenshots, istRauschen).
 * Der Pruef-Browser kommt in dieser Umgebung nicht an tile.openstreetmap.org. */
seite.on('console', m => {
  if (m.type() !== 'error') { return; }
  const ort = (m.location && m.location().url) || '';
  if (KACHELFEHLER.test(m.text()) || KACHELFEHLER.test(ort)) { return; }
  konsole.push(m.text() + (ort ? '  [' + ort + ']' : ''));
});
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
/* DIE SELEKTOREN FOLGEN DEM UMBAU (P3/O11). Der Papierkorb hatte zwei Tabellen
 * unter zwei <h2>; jetzt sind es zwei Karten mit Zeilen. Die Zahl steht
 * ausserdem im Kartenkopf — sie wird gegen die gezaehlten Zeilen gehalten,
 * damit ein Selektor, der ins Leere greift, nicht als „0 Eintraege" durchgeht.
 * Genau diese Sorte stiller Null hat F-S1-E fast durchrutschen lassen. */
const KARTE = (titel) => `.karte:has(.karte-titel:text-is("${titel}"))`;

async function papierkorbZaehlen() {
  await seite.goto(`${basis}/papierkorb.php`, { waitUntil: 'domcontentloaded' });
  const eine = async (titel) => {
    const karte = seite.locator(KARTE(titel));
    if (await karte.count() === 0) { return 0; }
    const zeilen = await karte.locator('.zeile').count();
    const kopf = parseInt((await karte.locator('.karte-zahl').first().innerText()
                            .catch(() => '')) || '0', 10);
    if (!Number.isNaN(kopf) && kopf !== zeilen) {
      befunde.push(`Papierkorb „${titel}": Kartenkopf nennt ${kopf}, gezaehlt wurden `
        + `${zeilen} Zeilen — einer der beiden Wege misst falsch.`);
    }
    return zeilen;
  };
  return { tage: await eine('Diensttage'), einsaetze: await eine('Einsätze') };
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
await seite.locator('form button.knopf-gefahr, form button[type="submit"]').first().click();
await rueckfragen();
await seite.waitForTimeout(800);
schritt(`Einsatz ${einzeln} einzeln gelöscht`);

// Danach den ganzen Diensttag.
await seite.goto(`${basis}/diensttag_loeschen.php?d=${tagId}`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(400);
await seite.locator('form button.knopf-gefahr, form button[type="submit"]').first().click();
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

/* DIE VORAUSSETZUNG DES ZIELKONTOS PRUEFEN (S2/AP10).
 *
 * Der Vergleich weiter unten haelt den Papierkorb des Ziels ABSOLUT gegen den
 * der Quelle. Das ist richtig — aber nur, wenn das Zielkonto vorher LEER ist:
 * Dann bekommt es den ganzen Bestand samt Papierkorb, und beide Zahlen muessen
 * uebereinstimmen.
 *
 * Diese Voraussetzung stand nirgends und wurde nicht geprueft. Laeuft die
 * Probe ein zweites Mal gegen dasselbe Zielkonto, meldete sie SECHS Befunde,
 * darunter den schwersten, den sie kennt (F-S1-E) — und keiner war ein Mangel
 * der Anwendung. Belegt am 01.09.2026 durch die Rueckmeldung des Einspielens:
 *
 *   „0 Einsaetze uebernommen ... Uebersprungen: 87 Einsaetze,
 *    100 Ruhesegmente — bereits vorhanden 187."
 *
 * Der Grund liegt in der Wiederherstellung selbst: Sie ERGAENZT und ersetzt
 * nicht (E22). Was schon da ist, wird uebersprungen — samt des
 * Papierkorbzustands, um den es hier geht.
 *
 * Sechs rote Zeilen aus einer unerfuellten Voraussetzung sind schlimmer als
 * gar keine Probe: Der schwerste Befund des Werkzeugs verliert seine
 * Glaubwuerdigkeit, wenn er auch dann erscheint, wenn nichts kaputt ist.
 *
 * ZUM VERGLEICH DIE VERWORFENE ALTERNATIVE: statt absolut die VERAENDERUNG auf
 * beiden Seiten zu messen. Das klingt sauberer und ist falsch — bei leerem
 * Ziel waechst dessen Papierkorb um den GANZEN Bestand der Quelle (hier +2),
 * der der Quelle nur um das eben Geloeschte (+1). Gemessen und verworfen.
 */
const zielGrund = await papierkorbZaehlen();
schritt(`Grundstand des Papierkorbs im Zielkonto: `
        + `${zielGrund.tage} Diensttag(e), ${zielGrund.einsaetze} einzelne(r) `
        + `Einsatz/Einsätze`);
if (zielGrund.tage !== 0 || zielGrund.einsaetze !== 0) {
  await abbruch(
    `Das Zielkonto ${ziel} hat schon etwas im Papierkorb `
    + `(${zielGrund.tage} Diensttag(e), ${zielGrund.einsaetze} Einsatz/Einsätze). `
    + `Diese Probe braucht ein LEERES Zielkonto — sonst vergleicht sie den `
    + `Papierkorb der Quelle gegen einen, der schon vorher gefüllt war.\n`
    + `Abhilfe: ein frisches Konto anlegen (Adresse muss mit 'umlauf-' `
    + `beginnen) und als drittes Argument übergeben:\n`
    + `  node papierkorb_misch.mjs <basis> <quellkonto> <frisches-zielkonto>`);
}

await seite.goto(`${basis}/einstellungen.php?t=backup`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
await seite.setInputFiles('#bfile', datei);
await seite.fill('#ipw', bpw);
await seite.click('#impbtn');
await rueckfragen();
/* Auf die MELDUNG warten, nicht auf einen Wortlaut (S2/AP5b). Begründung
   ausführlich in kreislauf_edbak.mjs: Ein Fortschrittstext ist reiner Text,
   ein Ergebnis ist `<div class="meldung meldung-…">`. Der frühere Ausdruck
   kannte den Abbruch nicht und wartete dann 300 s auf einen Zustand, den es
   längst gab. */
const impMeldung = seite.locator('#impstate .meldung');
try {
  await impMeldung.first().waitFor({ state: 'attached', timeout: 900000 });
} catch { /* der Befund unten nennt den letzten Zustand */ }
const impZustand = (await seite.locator('#impstate').textContent().catch(() => '') || '').trim();
const impTon = (await impMeldung.first().getAttribute('class').catch(() => '') || '');
schritt(`Eingespielt — ${impZustand}`);
pruefe(/meldung-ok/.test(impTon), `Einspielen nicht sauber: ${impZustand || '(kein Ergebnis)'}`);
/* DIE VORAUSSETZUNG PRUEFEN, BEVOR GEPRUEFT WIRD (S2/AP10).
 *
 * Diese Probe braucht ein Zielkonto, das den Bestand NOCH NICHT hat. Der
 * Grund liegt in der Wiederherstellung selbst: Sie ERGAENZT und ersetzt nicht
 * (E22). Was schon da ist, wird uebersprungen — und mit ihm der geaenderte
 * Papierkorbzustand, um den es hier geht.
 *
 * Laeuft die Probe ein zweites Mal gegen dasselbe Zielkonto, meldete sie
 * SECHS Befunde, darunter den schwersten, den sie kennt (F-S1-E). Keiner
 * davon war ein Mangel der Anwendung. Belegt am 01.09.2026 durch die
 * Rueckmeldung des Einspielens selbst:
 *
 *   „0 Einsaetze uebernommen, 0 Ruhesegmente, 0 Diensttage.
 *    Uebersprungen: 87 Einsaetze, 100 Ruhesegmente — bereits vorhanden 187."
 *
 * Sechs rote Zeilen aus einer unerfuellten Voraussetzung sind schlimmer als
 * gar keine Probe: Der schwerste Befund des Werkzeugs verliert seine
 * Glaubwuerdigkeit, wenn er auch dann erscheint, wenn nichts kaputt ist.
 *
 * Also: Hat das Einspielen NICHTS uebernommen, wird hier abgebrochen — mit
 * dem Weg zum leeren Zielkonto in der Meldung.
 */
const nichtsUebernommen = /\b0 Einsätze übernommen/.test(impZustand);
const allesSchonDa      = /bereits vorhanden/.test(impZustand);
if (nichtsUebernommen && allesSchonDa) {
  await abbruch(
    `Das Zielkonto ${ziel} traegt den Bestand bereits — das Einspielen hat `
    + `nichts uebernommen ("${impZustand}").\n`
    + `Diese Probe braucht ein LEERES Zielkonto: Die Wiederherstellung `
    + `ergaenzt und ersetzt nicht (E22), und was schon da ist, wird `
    + `uebersprungen — samt des Papierkorbzustands, um den es hier geht.\n`
    + `Abhilfe: ein frisches Zielkonto anlegen und als drittes Argument `
    + `uebergeben, oder das vorhandene leeren.\n`
    + `  node papierkorb_misch.mjs <basis> <quellkonto> <frisches-zielkonto>`);
}

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
  `Diensttage im Papierkorb: Quelle ${nachher.tage}, Ziel ${zielStand.tage} `
  + `(Zielkonto war vorher leer)`);
pruefe(zielStand.einsaetze === nachher.einsaetze,
  `Einzeln gelöschte Einsätze im Papierkorb: Quelle ${nachher.einsaetze}, `
  + `Ziel ${zielStand.einsaetze}. Ein Ziel-Wert von ${nachher.einsaetze - 1} hiesse: `
  + `der einzeln gelöschte Einsatz trägt fälschlich deleted_with_day = 1 und ist `
  + `unsichtbar — genau der Fehler F-S1-E.`);

/* Der gelöschte Diensttag muss GENAU die mitgelöschten Einsätze nennen, nicht
 * alle. Die Zeile wird über das Datum gesucht — `nth(0)` träfe den Tag, der
 * schon im Referenzbestand im Papierkorb lag. */
const tagZeile = tagDatum
  ? seite.locator(`${KARTE('Diensttage')} .zeile`)
         .filter({ hasText: tagDatum.split('-').reverse().join('.') })
  : seite.locator(`${KARTE('Diensttage')} .zeile`).last();
const tagTreffer = await tagZeile.count();
pruefe(tagTreffer === 1,
  `Der gelöschte Diensttag (${tagDatum}) ist im Papierkorb ${tagTreffer}× zu finden, erwartet 1×`);
if (tagTreffer === 1) {
  /* Die Einsatzzahl steht nicht mehr in einer eigenen Spalte, sondern in der
   * Kleinzeile („Alpenfalke 2 · 4 Einsätze · gelöscht am …"). Gesucht wird
   * genau dieses Stueck; findet der Ausdruck nichts, ist das ein Befund und
   * kein stilles 0. */
  const klein = await tagZeile.locator('.zeile-klein').innerText().catch(() => '');
  const zahl = klein.match(/(\d+)\s+Eins\u00e4tze?/);
  pruefe(zahl !== null,
    `In der Kleinzeile des Diensttags steht keine Einsatzzahl: „${klein}"`);
  if (zahl) {
    pruefe(parseInt(zahl[1], 10) === mitTag.length,
      `Der Diensttag im Papierkorb nennt ${zahl[1]} Einsätze, erwartet ${mitTag.length} `
      + `(der einzeln gelöschte darf NICHT mitgezählt werden)`);
  }
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
  ? seite.locator(`${KARTE('Einsätze')} .zeile`).filter({ hasText: deTag })
  : null;
const meineTreffer = meineZeile ? await meineZeile.count() : 0;
pruefe(meineTreffer === 1,
  `Der einzeln gelöschte Einsatz vom ${deTag} ist ${meineTreffer}× im Papierkorb, erwartet 1×`);
if (meineTreffer === 1) {
  /* „Wiederherstellen" steht ZWEIMAL im Markup: in der Knopfreihe
     (`.zeile-knoepfe`, ab 720 px sichtbar) und noch einmal im Aktionsblatt
     fuer schmale Geraete. Der Lauf arbeitet in der Vorgabebreite 1280 px, also
     wird ausdruecklich der sichtbare aus der Knopfreihe genommen — ein
     `.first()` ueber beide traefe je nach Reihenfolge den unsichtbaren im
     Blatt, und ein Klick darauf tut nichts. */
  await meineZeile.locator('.zeile-knoepfe button').first().click();
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
  await tagZeile.locator('.zeile-knoepfe button').first().click();
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
  const zeile = seite.locator(`${KARTE('Einsätze')} .zeile`).filter({ hasText: deTag });
  if (await zeile.count() === 1) {
    await zeile.locator('.zeile-knoepfe button').first().click();
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
