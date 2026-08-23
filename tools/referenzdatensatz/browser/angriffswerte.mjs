/* P-07 — Angriffswerte stehen inert in allen Einsatztabellen (Arbeitspaket B4).
 *
 * WORUM ES GEHT. Vier geschützte Freitextfelder des Referenzdatensatzes tragen
 * absichtlich Markup, das ein Browser ausführen WÜRDE, käme es ungeprüft in
 * die Seite (E-P1-15, R20):
 *
 *   Diagnose            <img src=x onerror="alert('R20-dx')">…
 *   Ortsbeschreibung    "><script>alert('R20-ort')</script>…
 *   Einsatznummer       <svg/onload=alert('R20-nr')>…
 *   Einsatzort-Adresse  <b onmouseover="alert('R20-adr')">…</b>
 *
 * Sie liegen verschlüsselt im Bestand; entschlüsselt und in die Seite gesetzt
 * werden sie erst im Browser. Genau deshalb ist das hier keine serverseitige
 * Frage: Wer den Wert mit innerHTML setzte statt mit textContent, hätte einen
 * XSS, den kein PHP-Escaping der Welt abfängt.
 *
 * WAS GEPRÜFT WIRD, auf jeder Seite, die Einsätze anzeigt:
 *   1. Kein Dialog. window.alert/confirm/prompt werden VOR dem ersten Skript
 *      der Seite ersetzt und zählen mit; zusätzlich hängt ein dialog-Handler
 *      von Playwright daran.
 *   2. Kein Element aus der Nutzlast — kein img[onerror], kein svg[onload],
 *      kein b[onmouseover], kein <script> mit „R20-" im Rumpf.
 *   3. Der Wert steht als TEXT da. Das ist die Gegenprobe zu 1 und 2: Ein
 *      Feld, das gar nicht angezeigt wird, wäre ebenfalls „inert" — und würde
 *      nichts beweisen.
 *   4. Die Konsole bleibt still (Kartenkacheln ausgenommen).
 *
 * Aufruf:
 *   node angriffswerte.mjs [basis] [email] [passwort] [ausgabeordner]
 */
import { writeFileSync, mkdirSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const basis = process.argv[2] || 'https://127.0.0.1:8443';
const email = process.argv[3] || 'demo@gen-em.org';
const passwort = process.argv[4] || 'nadokudemo0815';
const ordner = process.argv[5] || '/tmp/p07-angriffswerte';
mkdirSync(ordner, { recursive: true });

/* Marker und sichtbarer Text getrennt: Der Marker ist der ausführbare Teil,
   der Text der harmlose Rest. Die Anwendung darf den Wert kürzen — der Text
   ist die Stelle, die in jeder Darstellung übrig bleibt. */
const WERTE = [
  { name: 'Diagnose',           marker: "alert('R20-dx')",    text: 'Thoraxtrauma' },
  { name: 'Ortsbeschreibung',   marker: "alert('R20-ort')",   text: 'Baustelle' },
  { name: 'Einsatznummer',      marker: "alert('R20-nr')",    text: '2026-0335' },
  { name: 'Einsatzort-Adresse', marker: "alert('R20-adr')",   text: 'Talstraße 7' },
  /* Das Altersfeld hat keinen harmlosen Textanteil — es IST die Nutzlast.
     Es gehört trotzdem und gerade hierher: An ihm hing der Fund F-P1-I. */
  { name: 'Alter',              marker: "alert('R20-alter')", text: 'R20-alter' },
];

const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, viewport: { width: 1600, height: 1200 } });

/* VOR jedem Seitenskript: Die drei Dialogfunktionen werden ersetzt und
   protokolliert. Ein alert() aus einem onerror-Attribut liefe sonst gegen
   Playwrights stillen Dialog-Handler und bliebe unbemerkt. */
await kontext.addInitScript(() => {
  window.__r20 = [];
  ['alert', 'confirm', 'prompt'].forEach(nm => {
    window[nm] = function (t) { window.__r20.push(nm + ':' + String(t)); return false; };
  });
});

const seite = await kontext.newPage();
const konsole = [];
seite.on('console', m => { if (m.type() === 'error' && !KACHELFEHLER.test(m.text())) konsole.push(m.text()); });
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));
seite.on('dialog', async d => { konsole.push('DIALOG: ' + d.type() + ' ' + d.message()); await d.dismiss(); });

const befunde = [];
let n = 0;
const pruefe = (ok, text) => { n++; if (!ok) befunde.push(text); };

await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
await seite.fill('input[name="email"]', email);
await seite.fill('input[name="password"]', passwort);
await Promise.all([
  seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
  seite.click('button[type="submit"]'),
]);
pruefe(!seite.url().includes('login.php'), 'Anmeldung gescheitert');

const seiten = [];

async function pruefeSeite(bez, url, vorbereiten) {
  await seite.goto(url, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1200);
  if (vorbereiten) { await vorbereiten(); }
  await seite.waitForTimeout(2600);

  const roh = await seite.evaluate(() => ({
    dialoge: window.__r20 || [],
    injiziert: {
      img: document.querySelectorAll('img[onerror], img[src="x"]').length,
      svg: document.querySelectorAll('svg[onload]').length,
      b: document.querySelectorAll('b[onmouseover]').length,
      script: Array.from(document.querySelectorAll('script'))
        .filter(s => !s.src && /R20-/.test(s.textContent || '')).length,
    },
    /* innerText UND Feldwerte: Auf einem Formular steht der Wert in
       `input.value` und taucht in innerText nicht auf — die Seite sähe
       „nichts sichtbar" aus, obwohl sie den Wert sehr wohl führt. Das ist
       zugleich eine ANDERE Einbaustelle (Attribut statt Text), und genau die
       nennt der Kopfkommentar von assets/html.js als die gefährliche. */
    feldwerte: Array.from(document.querySelectorAll('input, textarea'))
      .map(e => String(e.value || '')).filter(Boolean),
    text: document.body.innerText,
    html: document.body.innerHTML,
  }));

  const sichtbar = roh.text + '\n' + roh.feldwerte.join('\n');
  const treffer = WERTE.filter(w => sichtbar.includes(w.text) || sichtbar.includes(w.marker))
                       .map(w => w.name);

  pruefe(roh.dialoge.length === 0,
         `${bez}: ${roh.dialoge.length} Dialog(e) ausgelöst — ${roh.dialoge.join(' | ')}`);
  Object.entries(roh.injiziert).forEach(([k, v]) => {
    pruefe(v === 0, `${bez}: ${v} Element(e) <${k}> aus der Nutzlast im Dokument`);
  });
  /* Wo ein Marker im sichtbaren Text steht, darf er im Rumpf nicht als
     öffnendes Tag stehen — dann wäre er Markup und nicht Text. */
  WERTE.forEach(w => {
    if (!sichtbar.includes(w.marker)) { return; }
    const alsMarkup = new RegExp('<(img|svg|b|script)[^>]*' +
      w.marker.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'i');
    pruefe(!alsMarkup.test(roh.html), `${bez}: Marker ${w.marker} steht als Markup im Dokument`);
  });

  await seite.screenshot({ path: `${ordner}/${bez.replace(/[^a-z0-9]+/gi, '-').toLowerCase()}.png` });
  seiten.push({ seite: bez, url, dialoge: roh.dialoge.length,
                injiziert: roh.injiziert, felder_sichtbar: treffer });
  console.log(`  ${bez.padEnd(22)} Dialoge ${roh.dialoge.length} · injiziert ` +
              `${Object.values(roh.injiziert).reduce((a, b) => a + b, 0)} · ` +
              `sichtbar: ${treffer.join(', ') || '—'}`);
}

console.log('P-07 — Angriffswerte in den Einsatztabellen\n');

/* ALLE ZEILEN SICHTBAR MACHEN. Suche und Zeitraum-Übersicht zeigen zunächst
   nur die erste Seite der Trefferliste; die Einsätze mit den Angriffswerten
   liegen weiter hinten. Ohne diesen Klick hieße „kein Dialog" nur „nicht
   gerendert" — genau der Fehlschluss, gegen den die Gegenprobe am Ende steht. */
async function alleZeigen() {
  const knopf = seite.locator('button.btn-plain', { hasText: /^Alle \d+ anzeigen$/ });
  for (let i = 0; i < 3; i++) {
    if (await knopf.first().isVisible().catch(() => false)) {
      await knopf.first().click();
      await seite.waitForTimeout(900);
    } else { break; }
  }
}

/* Der Diensttag, an dem alle fünf Angriffswerte liegen (Quelldaten D15). Die
   Tagesübersicht zeigt immer genau einen Tag — ohne die Kennung stünde dort
   ein beliebiger anderer. */
const tagId = await seite.evaluate(async () => {
  const r = await fetch('api/suchindex.php', { credentials: 'same-origin' });
  if (!r.ok) return null;
  const j = await r.json();
  const liste = j.missions || j.rows || j.data || [];
  const t = liste.find(m => String(m.date || m.day || '').startsWith('2026-11-21'));
  return t ? (t.day_id || t.dayId || null) : null;
});

await pruefeSeite('Tagesuebersicht', `${basis}/index.php` + (tagId ? `?d=${tagId}` : ''));
await pruefeSeite('Einsatzsuche', `${basis}/suche.php`, async () => {
  await seite.locator('#suchtable').waitFor({ state: 'visible', timeout: 20000 }).catch(() => {});
  await alleZeigen();
});
await pruefeSeite('Zeitraum-Uebersicht', `${basis}/zeitraum.php?y=2026&m=11`, alleZeigen);
await pruefeSeite('Nachbearbeitung', `${basis}/nachbearbeitung.php`);

/* Einsatz- und Formularseite des betroffenen Einsatzes. Die Einsatznummer ist
   verschlüsselt — der Server kann nicht danach suchen, also wird der Tag
   durchgegangen, an dem der Wert liegt. */
const ids = await seite.evaluate(async () => {
  const r = await fetch('api/suchindex.php', { credentials: 'same-origin' });
  if (!r.ok) return [];
  const j = await r.json();
  const liste = j.missions || j.rows || j.data || [];
  return liste.filter(m => String(m.date || m.day || '').startsWith('2026-11-21'))
              .map(m => m.id);
});
let ziel = null;
for (const id of ids) {
  await seite.goto(`${basis}/einsatz.php?id=${id}`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1800);
  const t = await seite.locator('body').innerText();
  if (t.includes('2026-0335') || t.includes("alert('R20-nr')")) { ziel = id; break; }
}
if (ziel) {
  await pruefeSeite('Einsatzseite', `${basis}/einsatz.php?id=${ziel}`);
  await pruefeSeite('Einsatzformular', `${basis}/einsatz_form.php?id=${ziel}`);
} else {
  befunde.push(`Einsatz mit den Angriffswerten nicht gefunden (${ids.length} Kandidaten) — P-07 unvollständig`);
}

/* Gegenprobe: Mindestens eine Seite muss die Werte tatsächlich anzeigen.
   Sonst hieße „kein Dialog" nur „nichts gerendert". */
pruefe(seiten.some(s => s.felder_sichtbar.length > 0),
       'Kein Angriffswert war auf irgendeiner Seite sichtbar — die Prüfung wäre gegenstandslos');

const ergebnis = { basis, einzelpruefungen: n, kandidaten: ids.length, ziel,
                   seiten, konsolenfehler: konsole, befunde };
writeFileSync(`${ordner}/ergebnis.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log(`\nEinzelprüfungen: ${n}`);
console.log(`Konsolenfehler:  ${konsole.length}`);
console.log(befunde.length ? `BEFUNDE (${befunde.length})\n  ` + befunde.join('\n  ')
                           : 'Keine Befunde — alle Angriffswerte stehen inert.');
await browser.close();
process.exit(befunde.length === 0 && konsole.length === 0 ? 0 : 1);
